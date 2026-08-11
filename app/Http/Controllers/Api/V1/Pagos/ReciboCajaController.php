<?php

namespace App\Http\Controllers\Api\V1\Pagos;

use App\Http\Controllers\Controller;
use App\Models\AccesoEstudiante;
use App\Models\ReciboCaja;
use App\Modules\Caja\CasosUso\AnularRecibo;
use App\Modules\Caja\CasosUso\ReimprimirRecibo;
use App\Modules\Comun\ContextoUsuario;
use App\Services\ResolutorAlcanceDatos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReciboCajaController extends Controller
{
    private function expresionFechaRecibo(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "COALESCE(recibos_caja.fecha_recibo, recibos_caja.fecha_proceso, recibos_caja.creado_en)",
            'mysql', 'mariadb' => "COALESCE(recibos_caja.fecha_recibo, recibos_caja.fecha_proceso, recibos_caja.creado_en)",
            default => "COALESCE(recibos_caja.fecha_recibo, recibos_caja.fecha_proceso, recibos_caja.creado_en)",
        };
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'plan_estudio_id' => 'nullable|exists:planes_estudio,id',
            'nivel_academico_id' => 'nullable|exists:niveles_academicos,id',
            'oferta_academica_id' => 'nullable|exists:ofertas_academicas,id',
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
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'recibos_caja');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estudiante_id')) {
            $query->where('recibos_caja.estudiante_id', $request->estudiante_id);
        }
        if ($request->filled('periodo_academico_id')) {
            $query->whereHas('pago.matricula.ofertaAcademica', fn ($q) => $q->where('periodo_academico_id', $request->periodo_academico_id));
        }
        if ($request->filled('plan_estudio_id')) {
            $query->whereHas('pago.matricula.ofertaAcademica.nivelAcademico.versionPlanEstudio', fn ($q) => $q->where('plan_estudio_id', $request->plan_estudio_id));
        }
        if ($request->filled('nivel_academico_id')) {
            $query->whereHas('pago.matricula.ofertaAcademica', fn ($q) => $q->where('nivel_academico_id', $request->nivel_academico_id));
        }
        if ($request->filled('oferta_academica_id')) {
            $query->whereHas('pago.matricula', fn ($q) => $q->where('oferta_academica_id', $request->oferta_academica_id));
        }
        if ($request->filled('estado')) {
            $query->where('recibos_caja.estado', $request->estado);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate(DB::raw($this->expresionFechaRecibo()), '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate(DB::raw($this->expresionFechaRecibo()), '<=', $request->fecha_hasta);
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

    public function show(Request $request, int $id): JsonResponse
    {
        $recibo = ReciboCaja::with([
            'pago:id,codigo,estudiante_id,metodo_pago_id,monto,estado,referencia_externa,fecha_proceso,fecha_deposito,fecha_aprobacion,creado_en',
            'estudiante:id,codigo,nombre,apellido,correo,telefono',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'sucursal:id,codigo,nombre',
        ]);
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($recibo, $request->user(), 'recibos_caja');
        $recibo = $recibo->findOrFail($id);

        $recibo->setAttribute('codigo_pago', $recibo->pago?->codigo);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $recibo,
        ]);
    }

    public function reimprimir(int $id): JsonResponse
    {
        $query = ReciboCaja::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, request()->user(), 'recibos_caja');
        $query->findOrFail($id);

        $resultado = app(ReimprimirRecibo::class)->ejecutar($id, ContextoUsuario::desdeRequest());

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['recibo'],
        ]);
    }

    public function anular(int $id, Request $request): JsonResponse
    {
        $query = ReciboCaja::query();
        app(ResolutorAlcanceDatos::class)->aplicarAlcance($query, $request->user(), 'recibos_caja');
        $query->findOrFail($id);

        $request->validate([
            'motivo_anulacion' => 'required|string|max:500',
        ]);

        $resultado = app(AnularRecibo::class)->ejecutar(
            $id,
            $request->input('motivo_anulacion'),
            ContextoUsuario::desdeRequest(),
        );

        if (! $resultado->ok()) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => $resultado->codigo(),
                'codigo_error' => $resultado->codigoError(),
                'mensaje' => $resultado->mensaje(),
            ], $resultado->codigo());
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => $resultado->mensaje(),
            'data' => $resultado->data()['recibo'],
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
        if (! $token) {
            abort(401, 'Token requerido');
        }

        $acceso = AccesoEstudiante::where('token', hash('sha256', $token))->first();
        if (! $acceso) {
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
            $nombreArchivo = 'recibo_'.$recibo->codigo.'.pdf';

            return $pdf->download($nombreArchivo);
        }

        return view('partials.recibo_print', ['recibo' => $recibo])->with('estudiante', $acceso->estudiante);
    }
}
