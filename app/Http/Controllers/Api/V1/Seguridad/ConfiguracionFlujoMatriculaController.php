<?php

namespace App\Http\Controllers\Api\V1\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionFlujoMatricula;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguracionFlujoMatriculaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = ConfiguracionFlujoMatricula::with(['conceptosPago:id,codigo,nombre', 'metodosPago:id,codigo,nombre'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['resultado' => 'A', 'codigo' => 0, 'mensaje' => 'OK', 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:configuraciones_flujo_matricula,codigo',
            'origen' => 'nullable|string|in:portal_administrativo,portal_estudiante,tecnico',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'metodo_pago_ids' => 'nullable|array',
            'metodo_pago_ids.*' => 'required|exists:metodos_pago,id',
            'concepto_pago_ids' => 'required|array|min:1',
            'concepto_pago_ids.*' => 'required|exists:conceptos_pago,id',
            'habilita_reserva_cupo' => 'boolean',
            'habilita_carga_comprobante' => 'boolean',
            'requiere_comprobante' => 'boolean',
            'habilita_revision_contable' => 'boolean',
            'habilita_aprobacion_pago' => 'boolean',
            'habilita_generacion_recibo' => 'boolean',
            'habilita_confirmacion_matricula' => 'boolean',
            'habilita_seleccion_obligaciones' => 'boolean',
            'habilita_whatsapp' => 'boolean',
            'habilita_reenganche' => 'boolean',
            'habilita_solicitud_link' => 'boolean',
        ]);
        $datos['concepto_pago_id'] = $datos['concepto_pago_ids'][0];
        $origen = $request->input('origen', 'tecnico');
        $existe = ConfiguracionFlujoMatricula::where('origen', $origen)
            ->where('concepto_pago_id', $datos['concepto_pago_id'])
            ->where('metodo_pago_id', $datos['metodo_pago_id'])
            ->exists();
        if ($existe) {
            return response()->json([
                'resultado' => 'R', 'codigo' => 422,
                'codigo_error' => '422_FLUJO_DUPLICADO',
                'mensaje' => 'Ya existe una configuración con el mismo origen, concepto y método de pago.',
            ], 422);
        }
        $cfg = ConfiguracionFlujoMatricula::create($datos + [
            'origen' => $origen,
            'estado' => 'activo',
            'habilita_reserva_cupo' => $request->boolean('habilita_reserva_cupo', true),
            'habilita_carga_comprobante' => $request->boolean('habilita_carga_comprobante', true),
            'requiere_comprobante' => $request->boolean('requiere_comprobante', true),
            'habilita_revision_contable' => $request->boolean('habilita_revision_contable', true),
            'habilita_aprobacion_pago' => $request->boolean('habilita_aprobacion_pago', true),
            'habilita_generacion_recibo' => $request->boolean('habilita_generacion_recibo', true),
            'habilita_confirmacion_matricula' => $request->boolean('habilita_confirmacion_matricula', true),
            'habilita_seleccion_obligaciones' => $request->boolean('habilita_seleccion_obligaciones', true),
            'habilita_whatsapp' => $request->boolean('habilita_whatsapp', true),
            'habilita_reenganche' => $request->boolean('habilita_reenganche', true),
            'habilita_solicitud_link' => $request->boolean('habilita_solicitud_link', true),
        ]);
        $cfg->conceptosPago()->sync(array_fill_keys($datos['concepto_pago_ids'], ['creado_por' => $request->user()->id, 'creado_en' => now()]));
        $metodos = array_unique(array_filter(array_map('intval', $request->input('metodo_pago_ids', [$datos['metodo_pago_id']]))));
        $cfg->metodosPago()->sync(array_fill_keys($metodos, ['creado_por' => $request->user()->id, 'creado_en' => now()]));
        return response()->json(['resultado' => 'A', 'codigo' => 201, 'mensaje' => 'Configuración creada', 'data' => $cfg], 201);
    }

    public function update(Request $request, ConfiguracionFlujoMatricula $configuracionFlujoMatricula): JsonResponse
    {
        $cfg = $configuracionFlujoMatricula;
        $datos = $request->validate([
            'codigo' => 'required|string|max:50|unique:configuraciones_flujo_matricula,codigo,' . $cfg->id,
            'origen' => 'nullable|string|in:portal_administrativo,portal_estudiante,tecnico',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'metodo_pago_ids' => 'nullable|array',
            'metodo_pago_ids.*' => 'required|exists:metodos_pago,id',
            'concepto_pago_ids' => 'required|array|min:1',
            'concepto_pago_ids.*' => 'required|exists:conceptos_pago,id',
            'estado' => 'required|in:activo,inactivo',
            'habilita_reserva_cupo' => 'boolean',
            'habilita_carga_comprobante' => 'boolean',
            'requiere_comprobante' => 'boolean',
            'habilita_revision_contable' => 'boolean',
            'habilita_aprobacion_pago' => 'boolean',
            'habilita_generacion_recibo' => 'boolean',
            'habilita_confirmacion_matricula' => 'boolean',
            'habilita_seleccion_obligaciones' => 'boolean',
            'habilita_whatsapp' => 'boolean',
            'habilita_reenganche' => 'boolean',
            'habilita_solicitud_link' => 'boolean',
        ]);
        $datos['concepto_pago_id'] = $datos['concepto_pago_ids'][0];
        $origenUpd = $request->input('origen', $cfg->origen ?? 'tecnico');
        $existeUpd = ConfiguracionFlujoMatricula::where('origen', $origenUpd)
            ->where('concepto_pago_id', $datos['concepto_pago_id'])
            ->where('metodo_pago_id', $datos['metodo_pago_id'])
            ->where('id', '!=', $cfg->id)
            ->exists();
        if ($existeUpd) {
            return response()->json([
                'resultado' => 'R', 'codigo' => 422,
                'codigo_error' => '422_FLUJO_DUPLICADO',
                'mensaje' => 'Ya existe otra configuración con el mismo origen, concepto y método de pago.',
            ], 422);
        }
        $cfg->update($datos + [
            'origen' => $request->input('origen', $cfg->origen ?? 'tecnico'),
            'habilita_reserva_cupo' => $request->boolean('habilita_reserva_cupo', true),
            'habilita_carga_comprobante' => $request->boolean('habilita_carga_comprobante', true),
            'requiere_comprobante' => $request->boolean('requiere_comprobante', true),
            'habilita_revision_contable' => $request->boolean('habilita_revision_contable', true),
            'habilita_aprobacion_pago' => $request->boolean('habilita_aprobacion_pago', true),
            'habilita_generacion_recibo' => $request->boolean('habilita_generacion_recibo', true),
            'habilita_confirmacion_matricula' => $request->boolean('habilita_confirmacion_matricula', true),
            'habilita_seleccion_obligaciones' => $request->boolean('habilita_seleccion_obligaciones', true),
            'habilita_whatsapp' => $request->boolean('habilita_whatsapp', true),
            'habilita_reenganche' => $request->boolean('habilita_reenganche', true),
            'habilita_solicitud_link' => $request->boolean('habilita_solicitud_link', true),
        ]);
        $cfg->conceptosPago()->sync(array_fill_keys($datos['concepto_pago_ids'], ['creado_por' => $request->user()->id, 'creado_en' => now()]));
        $metodos = array_unique(array_filter(array_map('intval', $request->input('metodo_pago_ids', [$datos['metodo_pago_id']]))));
        $cfg->metodosPago()->sync(array_fill_keys($metodos, ['creado_por' => $request->user()->id, 'creado_en' => now()]));
        return response()->json(['resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Configuración actualizada', 'data' => $cfg]);
    }

    public function destroy(ConfiguracionFlujoMatricula $configuracionFlujoMatricula): JsonResponse
    {
        $cfg = $configuracionFlujoMatricula;
        $cfg->update(['estado' => 'inactivo']);
        return response()->json(['resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Configuración desactivada']);
    }

    public function forceDestroy(ConfiguracionFlujoMatricula $configuracionFlujoMatricula): JsonResponse
    {
        $cfg = $configuracionFlujoMatricula;
        $cfg->conceptosPago()->detach();
        $cfg->metodosPago()->detach();
        $cfg->delete();
        return response()->json(['resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Configuración eliminada permanentemente']);
    }
}
