<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionProveedorPago;
use App\Models\ProveedorPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorPagoController extends Controller
{
    private array $validacionesConfig = [
        'PAYPAL' => [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'modo' => 'required|in:sandbox,live',
            'webhook_id' => 'nullable|string',
        ],
        'STRIPE' => [
            'secret_key' => 'required|string',
            'public_key' => 'required|string',
            'modo' => 'required|in:sandbox,live',
        ],
    ];

    public function index(): JsonResponse
    {
        $proveedores = ProveedorPago::with('configuraciones')->orderBy('nombre')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $proveedores,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $proveedor = ProveedorPago::with('configuraciones')->findOrFail($id);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $proveedor,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:20|unique:proveedores_pago,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $proveedor = ProveedorPago::create([
            ...$datos,
            'activo' => $datos['activo'] ?? true,
            'creado_por' => auth()->id(),
            'creado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 201,
            'mensaje' => 'Proveedor creado',
            'data' => $proveedor,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $proveedor = ProveedorPago::findOrFail($id);

        $datos = $request->validate([
            'codigo' => "required|string|max:20|unique:proveedores_pago,codigo,{$id}",
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $proveedor->update([
            ...$datos,
            'actualizado_por' => auth()->id(),
            'actualizado_en' => now(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Proveedor actualizado',
            'data' => $proveedor,
        ]);
    }

    public function guardarConfiguracion(Request $request, int $id): JsonResponse
    {
        $proveedor = ProveedorPago::findOrFail($id);

        $reglas = $this->validacionesConfig[$proveedor->codigo] ?? [];
        $reglas['config'] = 'required|array';

        $datos = $request->validate($reglas);
        $configs = $datos['config'];

        foreach ($configs as $clave => $valor) {
            if (isset($this->validacionesConfig[$proveedor->codigo][$clave])) {
                continue;
            }
            unset($configs[$clave]);
        }

        foreach ($configs as $clave => $valor) {
            ConfiguracionProveedorPago::updateOrCreate(
                ['proveedor_pago_id' => $proveedor->id, 'clave' => $clave],
                ['valor' => (string) $valor]
            );
        }

        $proveedor->load('configuraciones');

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Configuración guardada',
            'data' => $proveedor,
        ]);
    }
}
