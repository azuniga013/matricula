<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\RolPermiso;
use App\Services\CachePermisosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function __construct(
        protected CachePermisosService $cachePermisos,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = Rol::activos()
            ->withCount(['permisos' => fn ($q) => $q->where('permisos.estado', 'activo')])
            ->withCount(['usuarios' => fn ($q) => $q->where('usuario_roles.estado', 'activo')])
            ->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:roles,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $datos['creado_por'] = $request->user()->id;
        $datos['actualizado_por'] = $request->user()->id;
        $datos['creado_en'] = now();
        $datos['actualizado_en'] = now();

        $rol = Rol::create($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Rol creado exitosamente',
            'data' => $rol,
        ], 201);
    }

    public function show(Rol $rol): JsonResponse
    {
        $rol->load(['permisos.opcionModulo.modulo', 'alcances.sucursal']);
        $rol->loadCount(['usuarios' => fn ($q) => $q->where('usuario_roles.estado', 'activo')]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $rol,
        ]);
    }

    public function update(Request $request, Rol $rol): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ]);

        $datos['actualizado_por'] = $request->user()->id;
        $datos['actualizado_en'] = now();

        $rol->update($datos);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Rol actualizado exitosamente',
            'data' => $rol,
        ]);
    }

    public function permisos(Rol $rol): JsonResponse
    {
        $rol->load('permisos');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => [
                'rol' => $rol->only(['id', 'codigo', 'nombre']),
                'permisos' => $rol->permisos->pluck('codigo'),
            ],
        ]);
    }

    public function asignarPermisos(Request $request, Rol $rol): JsonResponse
    {
        $datos = $request->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,codigo',
        ]);

        $usuarioId = $request->user()->id;

        DB::transaction(function () use ($rol, $datos, $usuarioId) {
            $rol->permisos()->detach();

            foreach ($datos['permisos'] as $codigoPermiso) {
                $permiso = \App\Models\Permiso::where('codigo', $codigoPermiso)->first();
                if ($permiso) {
                    RolPermiso::create([
                        'rol_id' => $rol->id,
                        'permiso_id' => $permiso->id,
                        'estado' => 'activo',
                        'creado_por' => $usuarioId,
                        'actualizado_por' => $usuarioId,
                        'creado_en' => now(),
                        'actualizado_en' => now(),
                    ]);
                }
            }
        });

        $this->invalidarCachePorRol($rol);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Permisos asignados exitosamente',
            'data' => [
                'permisos_asignados' => count($datos['permisos']),
            ],
        ]);
    }

    public function copiarPermisos(Request $request, Rol $rolOrigen): JsonResponse
    {
        $datos = $request->validate([
            'rol_destino_id' => 'required|exists:roles,id|different:rolOrigen',
        ]);

        $rolDestino = Rol::findOrFail($datos['rol_destino_id']);

        DB::transaction(function () use ($rolOrigen, $rolDestino, $request) {
            $rolDestino->permisos()->detach();

            foreach ($rolOrigen->permisos as $permiso) {
                $rolDestino->permisos()->attach($permiso->id, [
                    'estado' => 'activo',
                    'creado_por' => $request->user()->id,
                    'actualizado_por' => $request->user()->id,
                    'creado_en' => now(),
                    'actualizado_en' => now(),
                ]);
            }
        });

        $this->cachePermisos->invalidarTodos();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Permisos copiados exitosamente',
        ]);
    }

    protected function invalidarCachePorRol(Rol $rol): void
    {
        $usuarioIds = $rol->usuarios()->pluck('users.id')->toArray();
        $this->cachePermisos->invalidarPermisosMasivos($usuarioIds);
    }
}
