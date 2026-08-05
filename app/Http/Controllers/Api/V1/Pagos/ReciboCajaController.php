<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\ReciboCaja;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReciboCajaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'estado' => 'nullable|in:emitido,anulado,reversado',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'clasificar' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ReciboCaja::with([
            'pago:id,codigo,fecha_proceso,fecha_deposito,fecha_aprobacion,creado_en,referencia_externa',
            'estudiante:id,codigo,nombre,apellido',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'sucursal:id,codigo,nombre',
        ])->select(['recibos_caja.*']);

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('recibos_caja.estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('estado')) {
            $query->where('recibos_caja.estado', $request->estado);
        }
        if ($request->filled('fecha_desde')) {
            $query->where('recibos_caja.creado_en', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('recibos_caja.creado_en', '<=', $request->fecha_hasta . ' 23:59:59');
        }

        if ($request->boolean('clasificar')) {
            $recibos = $query->orderByDesc('recibos_caja.id')->get();

            $clasificados = [
                'emitidos' => $recibos->where('estado', 'emitido')->values(),
                'anulados' => $recibos->where('estado', 'anulado')->values(),
                'reversados' => $recibos->where('estado', 'reversado')->values(),
            ];

            return response()->json([
                'resultado' => 'A',
                'codigo' => 200,
                'mensaje' => 'OK',
                'data' => [
                    ...$clasificados,
                    'resumen' => [
                        'emitidos' => $clasificados['emitidos']->count(),
                        'anulados' => $clasificados['anulados']->count(),
                        'reversados' => $clasificados['reversados']->count(),
                    ],
                ],
            ]);
        }

        $recibos = $query->orderByDesc('recibos_caja.id')->paginate($request->get('per_page', 25));

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $recibos,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $recibo = ReciboCaja::with([
            'pago:id,codigo,estudiante_id,metodo_pago_id,monto,estado,referencia_externa,fecha_proceso,fecha_deposito,fecha_aprobacion,creado_en',
            'estudiante:id,codigo,nombre,apellido,correo,telefono',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'sucursal:id,codigo,nombre',
        ])->findOrFail($id);

        $recibo->setAttribute('codigo_pago', $recibo->pago?->codigo);

        if (!$recibo->fecha_recibo) {
            $recibo->setAttribute('fecha_recibo', $recibo->fecha_proceso ?? $recibo->pago?->fecha_proceso ?? $recibo->pago?->fecha_aprobacion ?? $recibo->pago?->creado_en ?? $recibo->creado_en);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $recibo,
        ]);
    }

    public function reimprimir(int $id): JsonResponse
    {
        $recibo = ReciboCaja::findOrFail($id);

        if ($recibo->estado === 'anulado') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'No se puede reimprimir un recibo anulado',
            ], 422);
        }

        $recibo->update([
            'veces_reimpreso' => $recibo->veces_reimpreso + 1,
            'actualizado_por' => auth()->id(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Reimpresión registrada',
            'data' => $recibo,
        ]);
    }

    public function anular(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|max:500',
        ]);

        $recibo = ReciboCaja::findOrFail($id);

        if ($recibo->estado === 'anulado') {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El recibo ya está anulado',
            ], 422);
        }

        $recibo->update([
            'estado' => 'anulado',
            'anulado_por' => auth()->id(),
            'fecha_anulacion' => now(),
            'motivo_anulacion' => $request->motivo_anulacion,
            'actualizado_por' => auth()->id(),
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Recibo anulado',
            'data' => $recibo,
        ]);
    }

    public function imprimir(int $id): View
    {
        $recibo = ReciboCaja::with([
            'estudiante', 'conceptoPago', 'metodoPago', 'sucursal',
            'pago.matricula.ofertaAcademica.nivelAcademico',
            'pago.matricula.ofertaAcademica.horario',
            'pago.matricula.ofertaAcademica.periodoAcademico',
            'pago.matricula.ofertaAcademica.nivelAcademico.regimenAcademico',
            'pago.matricula.ofertaAcademica.modalidad',
            'pago.matricula.ofertaAcademica.docente',
            'pago.movimientosInventario.inventarioLibro.libro',
        ])->findOrFail($id);

        return view('partials.recibo_print', ['recibo' => $recibo]);
    }

    public function imprimirEstudiante(int $id, Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            abort(401, 'Token requerido');
        }

        $acceso = \App\Models\AccesoEstudiante::where('token', hash('sha256', $token))->first();
        if (!$acceso) {
            abort(401, 'Token inválido');
        }

        $recibo = ReciboCaja::with([
            'estudiante', 'conceptoPago', 'metodoPago', 'sucursal',
            'pago.matricula.ofertaAcademica.nivelAcademico',
            'pago.matricula.ofertaAcademica.horario',
            'pago.matricula.ofertaAcademica.periodoAcademico',
            'pago.matricula.ofertaAcademica.nivelAcademico.regimenAcademico',
            'pago.matricula.ofertaAcademica.modalidad',
            'pago.matricula.ofertaAcademica.docente',
            'pago.movimientosInventario.inventarioLibro.libro',
        ])->where('estudiante_id', $acceso->estudiante_id)
          ->findOrFail($id);

        if ($request->query('pdf') === '1') {
            $pdf = Pdf::loadView('partials.recibo_print', ['recibo' => $recibo]);
            $nombreArchivo = 'recibo_' . $recibo->codigo . '.pdf';
            return $pdf->download($nombreArchivo);
        }

        return view('partials.recibo_print', ['recibo' => $recibo])->with('estudiante', $acceso->estudiante);
    }
}
