<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\SesionUsuario;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use App\Services\CachePermisosService;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
        protected ServicioBitacora $bitacora,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $usuarios = User::with(['roles.alcances', 'sucursales', 'docente', 'alcances'])
            ->withCount(['roles' => fn ($q) => $q->where('usuario_roles.estado', 'activo')])
            ->addSelect(['ultimo_acceso' => SesionUsuario::query()
                ->selectRaw('MAX(ultimo_acceso)')
                ->whereColumn('sesiones_usuario.usuario_id', 'users.id'),
            ])
            ->when($request->busqueda, function ($q, $busqueda) {
                $q->where(function ($query) use ($busqueda) {
                    $query->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('name')
            ->paginate(50);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $usuarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telefono' => 'nullable|string|max:30',
            'docente_id' => 'nullable|exists:docentes,id|unique:users,docente_id',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,codigo',
            'sucursales' => 'nullable|array',
            'sucursales.*' => 'exists:sucursales,codigo',
        ]);

        $usuario = null;
        $actor = $request->user() ?: auth('sanctum')->user();

        if ($error = $this->validarAsignacionRolesProtegidos($actor, $datos['roles'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_ROL_PROTEGIDO',
                'mensaje' => $error,
            ], 422);
        }

        DB::transaction(function () use ($datos, $request, &$usuario) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'telefono' => $datos['telefono'] ?? null,
                'docente_id' => $datos['docente_id'] ?? null,
                'sucursal_id' => $datos['sucursal_id'] ?? null,
                'estado' => 'activo',
                'creado_por' => $request->user()->id,
            ]);

            foreach ($datos['roles'] as $codigoRol) {
                $rol = Rol::where('codigo', $codigoRol)->first();
                if ($rol) {
                    UsuarioRol::create([
                        'usuario_id' => $usuario->id,
                        'rol_id' => $rol->id,
                        'estado' => 'activo',
                        'creado_por' => $request->user()->id,
                    ]);
                }
            }

            if (! empty($datos['sucursales'])) {
                foreach ($datos['sucursales'] as $codigoSucursal) {
                    $sucursal = Sucursal::where('codigo', $codigoSucursal)->first();
                    if ($sucursal) {
                        UsuarioSucursal::create([
                            'usuario_id' => $usuario->id,
                            'sucursal_id' => $sucursal->id,
                            'estado' => 'activo',
                            'creado_por' => $request->user()->id,
                        ]);
                    }
                }
            }
        });

        if (! $usuario) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 500,
                'mensaje' => 'No se pudo crear el usuario',
            ], 500);
        }

        $usuario->load(['roles', 'sucursales', 'docente']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Usuario creado exitosamente',
            'data' => $usuario,
        ], 201);
    }

    public function show(User $usuario): JsonResponse
    {
        $usuario->load(['roles.permisos', 'sucursales', 'alcances']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $usuario,
        ]);
    }

    public function update(Request $request, User $usuario): JsonResponse
    {
        $datos = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,'.$usuario->id,
            'telefono' => 'nullable|string|max:30',
            'docente_id' => 'nullable|exists:docentes,id|unique:users,docente_id,'.$usuario->id,
            'estado' => 'sometimes|string|in:activo,inactivo',
            'debe_cambiar_contrasena' => 'sometimes|boolean',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        if (
            ($datos['estado'] ?? null) === 'inactivo'
            && $usuario->estado === 'activo'
            && $this->esUltimoSuperadminActivo($usuario)
        ) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_ULTIMO_SUPERADMIN',
                'mensaje' => 'No puede inactivar al último superadministrador activo.',
            ], 422);
        }

        $estadoAntes = $usuario->estado;
        $usuario->update($datos);

        if (isset($datos['estado']) && $datos['estado'] === 'inactivo') {
            $usuario->tokens()->delete();
            $usuario->sesiones()->whereNull('revocado_en')->update(['revocado_en' => now()]);
            $this->cachePermisos->invalidarPermisos($usuario->id);
            $this->bitacora->registrarOperacionPermitida(
                $request->user()->id,
                'inactivar_usuario',
                'seguridad',
                $request->ip(),
                $request->userAgent() ?? '',
                $usuario->id,
                ['estado' => $estadoAntes],
                ['estado' => 'inactivo'],
            );
        }

        $usuario->load(['roles', 'sucursales', 'docente']);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Usuario actualizado exitosamente',
            'data' => $usuario,
        ]);
    }

    public function asignarRoles(Request $request, User $usuario): JsonResponse
    {
        $datos = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,codigo',
        ]);

        if (
            ! in_array('SUPERADMIN', $datos['roles'], true)
            && $this->esUltimoSuperadminActivo($usuario)
        ) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_ULTIMO_SUPERADMIN',
                'mensaje' => 'No puede retirar el rol SUPERADMIN al último superadministrador activo.',
            ], 422);
        }

        $actor = $request->user() ?: auth('sanctum')->user();

        if ($error = $this->validarAsignacionRolesProtegidos($actor, $datos['roles'])) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'codigo_error' => '422_ROL_PROTEGIDO',
                'mensaje' => $error,
            ], 422);
        }

        DB::transaction(function () use ($usuario, $datos, $request) {
            $usuario->roles()->detach();

            foreach ($datos['roles'] as $codigoRol) {
                $rol = Rol::where('codigo', $codigoRol)->first();
                if ($rol) {
                    UsuarioRol::create([
                        'usuario_id' => $usuario->id,
                        'rol_id' => $rol->id,
                        'estado' => 'activo',
                        'creado_por' => $request->user()->id,
                    ]);
                }
            }
        });

        $this->cachePermisos->invalidarPermisos($usuario->id);

        $usuario->load('roles');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Roles asignados exitosamente',
            'data' => [
                'roles' => $usuario->roles->pluck('codigo'),
            ],
        ]);
    }

    public function asignarSucursales(Request $request, User $usuario): JsonResponse
    {
        $datos = $request->validate([
            'sucursales' => 'required|array',
            'sucursales.*' => 'exists:sucursales,codigo',
        ]);

        DB::transaction(function () use ($usuario, $datos, $request) {
            $usuario->sucursales()->detach();

            foreach ($datos['sucursales'] as $codigoSucursal) {
                $sucursal = Sucursal::where('codigo', $codigoSucursal)->first();
                if ($sucursal) {
                    UsuarioSucursal::create([
                        'usuario_id' => $usuario->id,
                        'sucursal_id' => $sucursal->id,
                        'estado' => 'activo',
                        'creado_por' => $request->user()->id,
                    ]);
                }
            }
        });

        $usuario->load('sucursales');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Sucursales asignadas exitosamente',
            'data' => [
                'sucursales' => $usuario->sucursales->pluck('codigo'),
            ],
        ]);
    }

    public function restablecerContrasena(Request $request, User $usuario): JsonResponse
    {
        $datos = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $usuario->update([
            'password' => Hash::make($datos['password']),
            'debe_cambiar_contrasena' => false,
            'actualizado_por' => $request->user()->id,
        ]);

        $usuario->tokens()->delete();
        $usuario->sesiones()->whereNull('revocado_en')->update(['revocado_en' => now()]);
        $this->bitacora->registrarOperacionPermitida(
            $request->user()->id,
            'restablecer_contrasena_usuario',
            'seguridad',
            $request->ip(),
            $request->userAgent() ?? '',
            $usuario->id,
            null,
            ['debe_cambiar_contrasena' => false],
        );

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Contraseña restablecida exitosamente',
        ]);
    }

    protected function esUltimoSuperadminActivo(User $usuario): bool
    {
        $esSuperadmin = $usuario->roles()
            ->where('roles.codigo', 'SUPERADMIN')
            ->exists();

        if (! $esSuperadmin || $usuario->estado !== 'activo') {
            return false;
        }

        return User::where('estado', 'activo')
            ->whereHas('roles', fn ($q) => $q->where('roles.codigo', 'SUPERADMIN'))
            ->count() <= 1;
    }

    protected function validarAsignacionRolesProtegidos(?User $actor, array $rolesSolicitados): ?string
    {
        if (! $actor) {
            return 'No se pudo validar el usuario autenticado para asignar roles.';
        }

        if ($actor->roles()->where('roles.codigo', 'SUPERADMIN')->exists()) {
            return null;
        }

        $rolesProtegidos = ['SUPERADMIN', 'ADMIN_GENERAL', 'ADMIN_OPERATIVO', 'ADMIN_ACADEMICO'];
        $rolesBloqueados = array_values(array_intersect($rolesProtegidos, $rolesSolicitados));

        if ($rolesBloqueados === []) {
            $actorEsAdminOperativo = $actor->roles()->where('roles.codigo', 'ADMIN_OPERATIVO')->exists();
            if (! $actorEsAdminOperativo) {
                return null;
            }

            $rolesPermitidos = ['CAJA', 'MATRICULA', 'DOCENTE', 'AUDITORIA', 'ADMIN_SUCURSAL'];
            $rolesFueraDeCatalogo = array_values(array_diff($rolesSolicitados, $rolesPermitidos));
            if ($rolesFueraDeCatalogo === []) {
                return null;
            }

            return 'El rol ADMIN_OPERATIVO solo puede asignar: '.implode(', ', $rolesPermitidos).'.';
        }

        return 'Solo un SUPERADMIN puede asignar los roles protegidos: '.implode(', ', $rolesBloqueados).'.';
    }
}
