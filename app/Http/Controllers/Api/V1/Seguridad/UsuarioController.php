<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsuarioRol;
use App\Models\UsuarioSucursal;
use App\Services\CachePermisosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $usuarios = User::with(['roles', 'sucursales', 'docente'])
            ->withCount(['roles' => fn ($q) => $q->where('usuario_roles.estado', 'activo')])
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
                $rol = \App\Models\Rol::where('codigo', $codigoRol)->first();
                if ($rol) {
                    UsuarioRol::create([
                        'usuario_id' => $usuario->id,
                        'rol_id' => $rol->id,
                        'estado' => 'activo',
                        'creado_por' => $request->user()->id,
                    ]);
                }
            }

            if (!empty($datos['sucursales'])) {
                foreach ($datos['sucursales'] as $codigoSucursal) {
                    $sucursal = \App\Models\Sucursal::where('codigo', $codigoSucursal)->first();
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
            'email' => 'sometimes|email|unique:users,email,' . $usuario->id,
            'telefono' => 'nullable|string|max:30',
            'docente_id' => 'nullable|exists:docentes,id|unique:users,docente_id,' . $usuario->id,
            'estado' => 'sometimes|string|in:activo,inactivo',
            'debe_cambiar_contrasena' => 'sometimes|boolean',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        $usuario->update($datos);

        if (isset($datos['estado']) && $datos['estado'] === 'inactivo') {
            $usuario->tokens()->delete();
            $usuario->sesiones()->whereNull('revocado_en')->update(['revocado_en' => now()]);
            $this->cachePermisos->invalidarPermisos($usuario->id);
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

        DB::transaction(function () use ($usuario, $datos, $request) {
            $usuario->roles()->detach();

            foreach ($datos['roles'] as $codigoRol) {
                $rol = \App\Models\Rol::where('codigo', $codigoRol)->first();
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
                $sucursal = \App\Models\Sucursal::where('codigo', $codigoSucursal)->first();
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

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Contraseña restablecida exitosamente',
        ]);
    }
}
