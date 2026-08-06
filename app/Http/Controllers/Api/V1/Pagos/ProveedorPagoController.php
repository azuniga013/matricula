<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionProveedorPago;
use App\Models\ProveedorPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $proveedores = ProveedorPago::with('configuraciones')->orderBy('nombre')->get()
            ->map(fn (ProveedorPago $proveedor) => $this->presentarProveedor($proveedor));

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
            'data' => $this->presentarProveedor($proveedor),
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

        $recibidos = $request->validate([
            'config' => 'required|array',
            'config.*' => 'nullable|string',
        ])['config'];
        $reglas = $this->validacionesConfig[$proveedor->codigo] ?? [];
        $existentes = $proveedor->configuraciones()->pluck('valor', 'clave')->all();
        $configuracionFinal = [];

        foreach ($reglas as $clave => $regla) {
            $valorRecibido = array_key_exists($clave, $recibidos) ? trim((string) $recibidos[$clave]) : null;
            $configuracionFinal[$clave] = $valorRecibido !== '' && $valorRecibido !== null
                ? $valorRecibido
                : ($existentes[$clave] ?? null);
        }

        Validator::make($configuracionFinal, $reglas)->validate();

        foreach ($configuracionFinal as $clave => $valor) {
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
            'data' => $this->presentarProveedor($proveedor),
        ]);
    }

    private function presentarProveedor(ProveedorPago $proveedor): array
    {
        return [
            'id' => $proveedor->id,
            'codigo' => $proveedor->codigo,
            'nombre' => $proveedor->nombre,
            'descripcion' => $proveedor->descripcion,
            'activo' => $proveedor->activo,
            'configuraciones' => $proveedor->configuraciones->map(fn (ConfiguracionProveedorPago $configuracion) => [
                'id' => $configuracion->id,
                'clave' => $configuracion->clave,
                'valor_enmascarado' => $this->enmascararValor($configuracion->clave, $configuracion->valor),
            ])->values(),
        ];
    }

    private function enmascararValor(string $clave, ?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($clave === 'modo') {
            return $valor;
        }

        return str_repeat('•', max(4, min(12, strlen($valor))));
    }
}
