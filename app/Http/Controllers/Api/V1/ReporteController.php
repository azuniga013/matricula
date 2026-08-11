<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Calificacion, DetalleCierreCaja, Matricula, Pago, ReciboCaja, SesionCaja};
use App\Models\ParametroGlobal;
use App\Services\ExportacionReportesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private function aplicarAlcanceAdministrativo(Request $request, $query, string $sucursalColumna, ?string $creadoPorColumna = null)
    {
        $usuario = $request->user();

        if (! $usuario || $usuario->tieneAlcanceGlobal()) {
            return $query;
        }

        $idsSucursales = $usuario->idsSucursalesAsignadas();
        if (! empty($idsSucursales)) {
            return $query->whereIn($sucursalColumna, $idsSucursales);
        }

        if ($creadoPorColumna) {
            return $query->where($creadoPorColumna, $usuario->id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function aplicarAlcanceReporte(Request $request, $query, ?string $sucursalColumna = null, ?string $docenteColumna = null)
    {
        $usuario = $request->user();

        if (! $usuario || $usuario->tieneAlcanceGlobal()) {
            return $query;
        }

        $idsSucursales = $usuario->idsSucursalesAsignadas();
        if (! empty($idsSucursales) && $sucursalColumna) {
            if ($query instanceof EloquentBuilder && str_contains($sucursalColumna, '.')) {
                $partes = explode('.', $sucursalColumna);
                if ($partes[0] === $query->getModel()->getTable()) {
                    return $query->whereIn($sucursalColumna, $idsSucursales);
                }
                $columna = array_pop($partes);
                return $query->whereHas(implode('.', $partes), fn ($q) => $q->whereIn($columna, $idsSucursales));
            }

            return $query->whereIn($sucursalColumna, $idsSucursales);
        }

        if ($usuario->docente_id && $docenteColumna) {
            if ($query instanceof EloquentBuilder && str_contains($docenteColumna, '.')) {
                $partes = explode('.', $docenteColumna);
                if ($partes[0] === $query->getModel()->getTable()) {
                    return $query->where($docenteColumna, $usuario->docente_id);
                }
                $columna = array_pop($partes);
                return $query->whereHas(implode('.', $partes), fn ($q) => $q->where($columna, $usuario->docente_id));
            }

            return $query->where($docenteColumna, $usuario->docente_id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function expresionFecha(string $columna): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date($columna)",
            'mysql', 'mariadb' => "DATE($columna)",
            default => "($columna)::date",
        };
    }

    private function expresionNombreDocente(): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "CONCAT_WS(' ', docentes.nombre, docentes.apellido) as docente_nombre",
            default => "COALESCE(docentes.nombre, '') || ' ' || COALESCE(docentes.apellido, '') as docente_nombre",
        };
    }

    public function exportar(Request $request): StreamedResponse|\Illuminate\Http\Response|JsonResponse
    {
        $request->validate([
            'reporte' => 'required|string',
            'formato' => 'required|in:excel,pdf',
        ]);

        $mapa = [
            'academicos.por-periodo' => fn () => $this->academicosMatriculadosPorPeriodo($request)->getData(true)['data'],
            'academicos.por-sucursal' => fn () => $this->academicosMatriculadosPorSucursal($request)->getData(true)['data'],
            'academicos.por-nivel' => fn () => $this->academicosMatriculadosPorNivel($request)->getData(true)['data'],
            'academicos.por-docente' => fn () => $this->academicosMatriculadosPorDocente($request)->getData(true)['data'],
            'academicos.grupo' => fn () => $this->academicosGrupo($request)->getData(true)['data'],
            'academicos.calificaciones-por-grupo' => fn () => $this->academicosCalificacionesPorGrupo($request)->getData(true)['data'],
            'academicos.nivel-actual' => fn () => $this->academicosNivelActual($request)->getData(true)['data'],
            'financieros.por-concepto' => fn () => $this->financierosIngresosPorConcepto($request)->getData(true)['data'],
            'financieros.por-metodo' => fn () => $this->financierosIngresosPorMetodo($request)->getData(true)['data'],
            'financieros.por-sucursal' => fn () => $this->financierosIngresosPorSucursal($request)->getData(true)['data'],
            'financieros.pagos-pendientes' => fn () => $this->financierosPagosPendientes($request)->getData(true)['data'],
            'financieros.pagos-rechazados' => fn () => $this->financierosPagosRechazados($request)->getData(true)['data'],
            'recibos.por-orden' => fn () => $this->recibosPorOrden($request)->getData(true)['data'],
            'recibos.por-metodo' => fn () => $this->recibosPorMetodo($request)->getData(true)['data'],
            'recibos.por-concepto' => fn () => $this->recibosPorConcepto($request)->getData(true)['data'],
            'recibos.por-concepto-detalle' => fn () => $this->recibosPorConceptoDetalle($request)->getData(true)['data'],
            'recibos.anulados' => fn () => $this->recibosAnulados($request)->getData(true)['data'],
            'caja.por-cajero' => fn () => $this->cajaPorCajero($request)->getData(true)['data'],
            'caja.resumen-diario' => fn () => $this->cajaResumenDiario($request)->getData(true)['data'],
        ];

        if (!isset($mapa[$request->reporte])) {
            return response()->json(['resultado' => 'R', 'codigo' => 404, 'mensaje' => 'Reporte no encontrado'], 404);
        }

        $data = $mapa[$request->reporte]();
        $filas = isset($data['data']) ? ($data['data']['data'] ?? $data['data'] ?? []) : ($data ?? []);
        $nombre = str_replace(['.', '/'], '_', $request->reporte);

        // Resumen de filtros aplicados
        $filtrosArr = [];
        if ($request->filled('fecha_desde')) { $filtrosArr[] = 'Desde: ' . $request->fecha_desde; }
        if ($request->filled('fecha_hasta')) { $filtrosArr[] = 'Hasta: ' . $request->fecha_hasta; }
        if ($request->filled('sucursal_id')) { $filtrosArr[] = 'Sucursal ID: ' . $request->sucursal_id; }
        if ($request->filled('periodo_academico_id')) { $filtrosArr[] = 'Período ID: ' . $request->periodo_academico_id; }
        if ($request->filled('estado')) { $filtrosArr[] = 'Estado: ' . $request->estado; }
        if ($request->filled('oferta_academica_id')) { $filtrosArr[] = 'Oferta ID: ' . $request->oferta_academica_id; }
        if ($request->filled('estudiante_id')) { $filtrosArr[] = 'Estudiante ID: ' . $request->estudiante_id; }
        if ($request->filled('metodo_pago_id')) { $filtrosArr[] = 'Método ID: ' . $request->metodo_pago_id; }
        $filtrosResumen = implode(' · ', $filtrosArr) ?: 'Sin filtros adicionales';

        // Título legible del reporte
        $titulos = [
            'academicos.por-periodo' => 'Matriculados por Período',
            'academicos.por-sucursal' => 'Matriculados por Sucursal',
            'academicos.por-nivel' => 'Matriculados por Nivel',
            'academicos.por-docente' => 'Matriculados por Docente',
            'academicos.grupo' => 'Alumnos por Grupo',
            'academicos.calificaciones-por-grupo' => 'Calificaciones por Grupo',
            'academicos.nivel-actual' => 'Nivel Actual del Estudiante',
            'financieros.por-concepto' => 'Ingresos por Concepto',
            'financieros.por-metodo' => 'Ingresos por Método de Pago',
            'financieros.por-sucursal' => 'Ingresos por Sucursal',
            'financieros.pagos-pendientes' => 'Pagos Pendientes',
            'financieros.pagos-rechazados' => 'Pagos Rechazados',
            'recibos.por-orden' => 'Recibos por Orden Numérica',
            'recibos.por-metodo' => 'Recibos por Método de Pago',
            'recibos.por-concepto' => 'Recibos por Concepto',
            'recibos.por-concepto-detalle' => 'Recibos por Concepto (Detalle)',
            'recibos.anulados' => 'Recibos Anulados',
            'caja.por-cajero' => 'Caja por Cajero',
            'caja.resumen-diario' => 'Resumen Diario de Caja',
        ];
        $tituloReporte = $titulos[$request->reporte] ?? $request->reporte;

        if ($request->formato === 'pdf') {
            $empresa = app(ExportacionReportesService::class)->empresaParaPdf();
            $pdf = Pdf::loadView('admin.reportes_exportacion', [
                'reporte' => $tituloReporte,
                'filas' => $filas,
                'filtros' => $filtrosResumen,
                'empresa' => $empresa,
                'generado_en' => now(),
                'usuario' => $request->user()?->nombre ?? 'Sistema',
            ])->setPaper('a4', 'landscape');

            return $pdf->download($nombre . '.pdf');
        }

        // Excel real con PhpSpreadsheet
        $usuario = $request->user()?->nombre . ' ' . $request->user()?->apellido ?? 'Sistema';
        $archivoExcel = app(ExportacionReportesService::class)->generarExcel(
            $filas,
            $tituloReporte,
            $filtrosResumen,
            trim($usuario)
        );

        $contenido = file_get_contents($archivoExcel);
        @unlink($archivoExcel);

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function academicosMatriculadosPorPeriodo(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = DB::table('matriculas')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->join('sucursales', 'ofertas_academicas.sucursal_id', '=', 'sucursales.id')
            ->join('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('estudiantes', 'matriculas.estudiante_id', '=', 'estudiantes.id')
            ->where('matriculas.estado', 'matriculado')
            ->select(
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                DB::raw('COUNT(matriculas.id) as total_matriculados')
            )
            ->groupBy('periodos_academicos.id', 'periodos_academicos.codigo', 'periodos_academicos.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }
        if ($request->filled('sucursal_id')) {
            $query->where('ofertas_academicas.sucursal_id', $request->sucursal_id);
        }

        $resultados = $query->orderByDesc('total_matriculados')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $resultados,
        ]);
    }

    public function academicosMatriculadosPorSucursal(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = DB::table('matriculas')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('sucursales', 'ofertas_academicas.sucursal_id', '=', 'sucursales.id')
            ->where('matriculas.estado', 'matriculado')
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                DB::raw('COUNT(matriculas.id) as total_matriculados')
            )
            ->groupBy('sucursales.id', 'sucursales.codigo', 'sucursales.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }

        $resultados = $query->orderByDesc('total_matriculados')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $resultados,
        ]);
    }

    public function academicosMatriculadosPorNivel(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = DB::table('matriculas')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->where('matriculas.estado', 'matriculado')
            ->select(
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                DB::raw('COUNT(matriculas.id) as total_matriculados')
            )
            ->groupBy('niveles_academicos.id', 'niveles_academicos.codigo', 'niveles_academicos.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }
        if ($request->filled('sucursal_id')) {
            $query->where('ofertas_academicas.sucursal_id', $request->sucursal_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_matriculados')->get(),
        ]);
    }

    public function academicosMatriculadosPorDocente(Request $request): JsonResponse
    {
        $request->validate([
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'nivel_academico_id' => 'nullable|exists:niveles_academicos,id',
            'horario_id' => 'nullable|exists:horarios,id',
            'estado' => 'nullable|in:matriculado,retirado,cancelado',
        ]);

        $query = DB::table('matriculas')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->join('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->join('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->join('estudiantes', 'matriculas.estudiante_id', '=', 'estudiantes.id')
            ->select(
                'docentes.codigo as docente_codigo',
                'docentes.nombre as docente_nombre',
                'docentes.apellido as docente_apellido',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                DB::raw('COUNT(matriculas.id) as total_matriculados')
            )
            ->groupBy('docentes.id', 'docentes.codigo', 'docentes.nombre', 'docentes.apellido',
                'niveles_academicos.id', 'niveles_academicos.codigo', 'niveles_academicos.nombre',
                'horarios.id', 'horarios.codigo', 'horarios.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }
        if ($request->filled('sucursal_id')) {
            $query->where('ofertas_academicas.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('nivel_academico_id')) {
            $query->where('ofertas_academicas.nivel_academico_id', $request->nivel_academico_id);
        }
        if ($request->filled('horario_id')) {
            $query->where('ofertas_academicas.horario_id', $request->horario_id);
        }
        if ($request->filled('estado')) {
            $query->where('matriculas.estado', $request->estado);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_matriculados')->get(),
        ]);
    }

    public function academicosGrupo(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
        ]);

        $alumnos = DB::table('matriculas')
            ->join('estudiantes', 'matriculas.estudiante_id', '=', 'estudiantes.id')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->join('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('versiones_plan_estudio', 'niveles_academicos.version_plan_estudio_id', '=', 'versiones_plan_estudio.id')
            ->leftJoin('planes_estudio', 'versiones_plan_estudio.plan_estudio_id', '=', 'planes_estudio.id')
            ->leftJoin('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->leftJoin('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->leftJoin('sucursales', 'ofertas_academicas.sucursal_id', '=', 'sucursales.id')
            ->where('matriculas.oferta_academica_id', $request->oferta_academica_id)
            ->where('matriculas.estado', 'matriculado')
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'planes_estudio.codigo as plan_codigo',
                'planes_estudio.nombre as plan_nombre',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                'docentes.codigo as docente_codigo',
                'docentes.nombre as docente_nombre',
                'docentes.apellido as docente_apellido',
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'estudiantes.correo',
                'estudiantes.telefono',
                'matriculas.estado as estado_matricula'
            );

        $this->aplicarAlcanceReporte($request, $alumnos, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        $alumnos = $alumnos->orderBy('estudiantes.apellido')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $alumnos,
        ]);
    }

    public function academicosCalificacionesPorGrupo(Request $request): JsonResponse
    {
        $request->validate([
            'oferta_academica_id' => 'required|exists:ofertas_academicas,id',
        ]);

        $calificaciones = DB::table('calificaciones')
            ->join('estudiantes', 'calificaciones.estudiante_id', '=', 'estudiantes.id')
            ->join('ofertas_academicas', 'calificaciones.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->where('calificaciones.oferta_academica_id', $request->oferta_academica_id)
            ->select(
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'calificaciones.nota_final',
                'calificaciones.faltas',
                'calificaciones.estado as estado_calificacion'
            );

        $this->aplicarAlcanceReporte($request, $calificaciones, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        $calificaciones = $calificaciones->orderBy('estudiantes.apellido')->get();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $calificaciones,
        ]);
    }

    public function academicosNivelActual(Request $request): JsonResponse
    {
        $request->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
        ]);

        $matricula = DB::table('matriculas')
            ->join('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->join('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->join('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->where('matriculas.estudiante_id', $request->estudiante_id)
            ->where('matriculas.estado', 'matriculado')
            ->select(
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'matriculas.fecha_confirmacion'
            );

        $this->aplicarAlcanceReporte($request, $matricula, 'ofertas_academicas.sucursal_id', 'ofertas_academicas.docente_id');

        $matricula = $matricula->first();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $matricula,
        ]);
    }

    public function financierosIngresosPorConcepto(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
        ]);

        $query = DB::table('pagos')
            ->join('conceptos_pago', 'pagos.concepto_pago_id', '=', 'conceptos_pago.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereBetween(DB::raw($this->expresionFecha('pagos.creado_en')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'conceptos_pago.codigo as concepto_codigo',
                'conceptos_pago.nombre as concepto_nombre',
                DB::raw('COUNT(pagos.id) as cantidad'),
                DB::raw('SUM(pagos.monto) as total_monto')
            )
            ->groupBy('conceptos_pago.id', 'conceptos_pago.codigo', 'conceptos_pago.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'pagos.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_monto')->get(),
        ]);
    }

    public function financierosIngresosPorMetodo(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = DB::table('pagos')
            ->join('metodos_pago', 'pagos.metodo_pago_id', '=', 'metodos_pago.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereBetween(DB::raw($this->expresionFecha('pagos.creado_en')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'metodos_pago.codigo as metodo_codigo',
                'metodos_pago.nombre as metodo_nombre',
                DB::raw('COUNT(pagos.id) as cantidad'),
                DB::raw('SUM(pagos.monto) as total_monto')
            )
            ->groupBy('metodos_pago.id', 'metodos_pago.codigo', 'metodos_pago.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'pagos.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_monto')->get(),
        ]);
    }

    public function financierosIngresosPorSucursal(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
        ]);

        $query = DB::table('pagos')
            ->join('sucursales', 'pagos.sucursal_id', '=', 'sucursales.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereBetween(DB::raw($this->expresionFecha('pagos.creado_en')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                DB::raw('COUNT(pagos.id) as cantidad'),
                DB::raw('SUM(pagos.monto) as total_monto')
            )
            ->groupBy('sucursales.id', 'sucursales.codigo', 'sucursales.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'pagos.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_monto')->get(),
        ]);
    }

    public function financierosPagosPendientes(Request $request): JsonResponse
    {
        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
        ]);

        $query = DB::table('pagos')
            ->join('estudiantes', 'pagos.estudiante_id', '=', 'estudiantes.id')
            ->join('conceptos_pago', 'pagos.concepto_pago_id', '=', 'conceptos_pago.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->leftJoin('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->leftJoin('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->leftJoin('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->leftJoin('versiones_plan_estudio', 'niveles_academicos.version_plan_estudio_id', '=', 'versiones_plan_estudio.id')
            ->leftJoin('planes_estudio', 'versiones_plan_estudio.plan_estudio_id', '=', 'planes_estudio.id')
            ->leftJoin('sucursales', 'pagos.sucursal_id', '=', 'sucursales.id')
            ->where('pagos.estado', 'pendiente')
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'planes_estudio.codigo as plan_codigo',
                'planes_estudio.nombre as plan_nombre',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                'docentes.codigo as docente_codigo',
                DB::raw($this->expresionNombreDocente()),
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'estudiantes.correo',
                'estudiantes.telefono',
                'conceptos_pago.codigo as concepto_codigo',
                'conceptos_pago.nombre as concepto_nombre',
                'pagos.codigo as pago_codigo',
                'pagos.estado',
                'pagos.monto',
                'pagos.creado_en as fecha_pago',
                'pagos.fecha_proceso',
                'pagos.referencia_externa',
                'pagos.motivo_rechazo',
                'matriculas.codigo as matricula_codigo'
            )
            ->orderByDesc('pagos.creado_en');

        $this->aplicarAlcanceAdministrativo($request, $query, 'pagos.sucursal_id', 'pagos.creado_por');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('periodo_academico_id')) {
            $query->where('ofertas_academicas.periodo_academico_id', $request->periodo_academico_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->get(),
        ]);
    }

    public function financierosPagosRechazados(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = DB::table('pagos')
            ->join('estudiantes', 'pagos.estudiante_id', '=', 'estudiantes.id')
            ->join('conceptos_pago', 'pagos.concepto_pago_id', '=', 'conceptos_pago.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->leftJoin('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->leftJoin('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->leftJoin('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->leftJoin('versiones_plan_estudio', 'niveles_academicos.version_plan_estudio_id', '=', 'versiones_plan_estudio.id')
            ->leftJoin('planes_estudio', 'versiones_plan_estudio.plan_estudio_id', '=', 'planes_estudio.id')
            ->leftJoin('sucursales', 'pagos.sucursal_id', '=', 'sucursales.id')
            ->where('pagos.estado', 'rechazado')
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'planes_estudio.codigo as plan_codigo',
                'planes_estudio.nombre as plan_nombre',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                'docentes.codigo as docente_codigo',
                DB::raw($this->expresionNombreDocente()),
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'estudiantes.correo',
                'estudiantes.telefono',
                'conceptos_pago.codigo as concepto_codigo',
                'conceptos_pago.nombre as concepto_nombre',
                'pagos.codigo as pago_codigo',
                'pagos.estado',
                'pagos.monto',
                'pagos.motivo_rechazo',
                'pagos.fecha_rechazo',
                'pagos.fecha_proceso',
                'pagos.referencia_externa',
                'matriculas.codigo as matricula_codigo'
            )
            ->orderByDesc('pagos.fecha_rechazo');

        $this->aplicarAlcanceReporte($request, $query, 'pagos.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('pagos.fecha_rechazo', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('pagos.fecha_rechazo', '<=', $request->fecha_hasta);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->get(),
        ]);
    }

    public function recibosPorOrden(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'estado' => 'nullable|in:emitido,anulado,reversado',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ReciboCaja::with([
            'estudiante:id,codigo,nombre,apellido',
            'sucursal:id,codigo,nombre',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.nivelAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.horario:id,codigo,nombre,hora_inicio,hora_fin',
            'pago.matricula.ofertaAcademica.docente:id,codigo,nombre,apellido',
            'pago.matricula.ofertaAcademica.planCobro:id,codigo,nombre',
        ])
            ->whereBetween(DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)')), [$request->fecha_desde, $request->fecha_hasta]);

        $this->aplicarAlcanceReporte($request, $query, 'recibos_caja.sucursal_id', 'pago.matricula.ofertaAcademica.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('estado')) {
            $query->where('recibos_caja.estado', $request->estado);
        }

        $recibos = $query->orderBy('recibos_caja.numero_recibo')
            ->paginate($request->get('per_page', 50));

        $recibos->getCollection()->transform(function ($r) {
            $r->setAttribute('estudiante_codigo', $r->estudiante?->codigo);
            $r->setAttribute('estudiante_nombre', trim(($r->estudiante?->nombre ?? '') . ' ' . ($r->estudiante?->apellido ?? '')));
            $r->setAttribute('periodo_nombre', $r->pago?->matricula?->ofertaAcademica?->periodoAcademico?->nombre);
            $r->setAttribute('plan_nombre', $r->pago?->matricula?->ofertaAcademica?->planCobro?->nombre);
            $r->setAttribute('nivel_nombre', $r->pago?->matricula?->ofertaAcademica?->nivelAcademico?->nombre);
            $r->setAttribute('horario_nombre', $r->pago?->matricula?->ofertaAcademica?->horario?->nombre);
            $r->setAttribute('docente_nombre', trim(($r->pago?->matricula?->ofertaAcademica?->docente?->nombre ?? '') . ' ' . ($r->pago?->matricula?->ofertaAcademica?->docente?->apellido ?? '')));
            return $r;
        });

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $recibos,
        ]);
    }

    public function recibosPorMetodo(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'metodo_pago_id' => 'nullable|exists:metodos_pago,id',
        ]);

        $query = DB::table('recibos_caja')
            ->join('metodos_pago', 'recibos_caja.metodo_pago_id', '=', 'metodos_pago.id')
            ->leftJoin('pagos', 'recibos_caja.pago_id', '=', 'pagos.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->leftJoin('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->leftJoin('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->leftJoin('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->leftJoin('versiones_plan_estudio', 'niveles_academicos.version_plan_estudio_id', '=', 'versiones_plan_estudio.id')
            ->leftJoin('planes_estudio', 'versiones_plan_estudio.plan_estudio_id', '=', 'planes_estudio.id')
            ->leftJoin('estudiantes', 'recibos_caja.estudiante_id', '=', 'estudiantes.id')
            ->leftJoin('sucursales', 'recibos_caja.sucursal_id', '=', 'sucursales.id')
            ->where('recibos_caja.estado', 'emitido')
            ->whereBetween(DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'planes_estudio.codigo as plan_codigo',
                'planes_estudio.nombre as plan_nombre',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                'docentes.codigo as docente_codigo',
                DB::raw($this->expresionNombreDocente()),
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'estudiantes.correo',
                'estudiantes.telefono',
                'metodos_pago.codigo as metodo_codigo',
                'metodos_pago.nombre as metodo_nombre',
                DB::raw('COUNT(recibos_caja.id) as cantidad'),
                DB::raw('SUM(recibos_caja.monto_total) as total_monto')
            )
            ->groupBy('sucursales.id', 'sucursales.codigo', 'sucursales.nombre', 'periodos_academicos.id', 'periodos_academicos.codigo', 'periodos_academicos.nombre', 'planes_estudio.id', 'planes_estudio.codigo', 'planes_estudio.nombre', 'niveles_academicos.id', 'niveles_academicos.codigo', 'niveles_academicos.nombre', 'horarios.id', 'horarios.codigo', 'horarios.nombre', 'docentes.id', 'docentes.codigo', 'docentes.nombre', 'docentes.apellido', 'estudiantes.id', 'estudiantes.codigo', 'estudiantes.nombre', 'estudiantes.apellido', 'estudiantes.correo', 'estudiantes.telefono', 'metodos_pago.id', 'metodos_pago.codigo', 'metodos_pago.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'recibos_caja.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('metodo_pago_id')) {
            $query->where('recibos_caja.metodo_pago_id', $request->metodo_pago_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_monto')->get(),
        ]);
    }

    public function recibosPorConcepto(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
        ]);

        $query = DB::table('pagos')
            ->join('conceptos_pago', 'pagos.concepto_pago_id', '=', 'conceptos_pago.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->where('pagos.estado', 'aprobado')
            ->whereBetween(DB::raw($this->expresionFecha('pagos.fecha_aprobacion')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'conceptos_pago.codigo as concepto_codigo',
                'conceptos_pago.nombre as concepto_nombre',
                DB::raw('COUNT(pagos.id) as cantidad'),
                DB::raw('SUM(pagos.monto) as total_monto')
            )
            ->groupBy('conceptos_pago.id', 'conceptos_pago.codigo', 'conceptos_pago.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'pagos.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('pagos.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('concepto_pago_id')) {
            $query->where('pagos.concepto_pago_id', $request->concepto_pago_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('total_monto')->get(),
        ]);
    }

    public function recibosPorConceptoDetalle(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'concepto_pago_id' => 'nullable|exists:conceptos_pago,id',
        ]);

        $query = ReciboCaja::with([
            'estudiante:id,codigo,nombre,apellido',
            'sucursal:id,codigo,nombre',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.nivelAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.horario:id,codigo,nombre,hora_inicio,hora_fin',
            'pago.matricula.ofertaAcademica.docente:id,codigo,nombre,apellido',
        ])
        ->select('recibos_caja.*')
        ->whereBetween(DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)')), [$request->fecha_desde, $request->fecha_hasta]);

        $this->aplicarAlcanceReporte($request, $query, 'recibos_caja.sucursal_id', 'pago.matricula.ofertaAcademica.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('concepto_pago_id')) {
            $query->where('recibos_caja.concepto_pago_id', $request->concepto_pago_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('recibos_caja.creado_en')
                ->paginate($request->get('per_page', 50))
                ->through(function ($r) {
                    $r->setAttribute('estudiante_codigo', $r->estudiante?->codigo);
                    $r->setAttribute('estudiante_nombre', trim(($r->estudiante?->nombre ?? '') . ' ' . ($r->estudiante?->apellido ?? '')));
                    $r->setAttribute('periodo_nombre', $r->pago?->matricula?->ofertaAcademica?->periodoAcademico?->nombre);
                    $r->setAttribute('nivel_nombre', $r->pago?->matricula?->ofertaAcademica?->nivelAcademico?->nombre);
                    $r->setAttribute('horario_nombre', $r->pago?->matricula?->ofertaAcademica?->horario?->nombre);
                    $r->setAttribute('docente_nombre', trim(($r->pago?->matricula?->ofertaAcademica?->docente?->nombre ?? '') . ' ' . ($r->pago?->matricula?->ofertaAcademica?->docente?->apellido ?? '')));
                    return $r;
                }),
        ]);
    }

    public function recibosAnulados(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = ReciboCaja::with([
            'estudiante:id,codigo,nombre,apellido',
            'sucursal:id,codigo,nombre',
            'conceptoPago:id,codigo,nombre',
            'metodoPago:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.nivelAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'pago.matricula.ofertaAcademica.horario:id,codigo,nombre,hora_inicio,hora_fin',
            'pago.matricula.ofertaAcademica.docente:id,codigo,nombre,apellido',
        ])
        ->where('recibos_caja.estado', 'anulado');

        $this->aplicarAlcanceReporte($request, $query, 'recibos_caja.sucursal_id', 'pago.matricula.ofertaAcademica.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('recibos_caja.fecha_anulacion', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('recibos_caja.fecha_anulacion', '<=', $request->fecha_hasta);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('recibos_caja.fecha_anulacion')->get()->map(function ($r) {
                $r->setAttribute('estudiante_codigo', $r->estudiante?->codigo);
                $r->setAttribute('estudiante_nombre', trim(($r->estudiante?->nombre ?? '') . ' ' . ($r->estudiante?->apellido ?? '')));
                $r->setAttribute('periodo_nombre', $r->pago?->matricula?->ofertaAcademica?->periodoAcademico?->nombre);
                $r->setAttribute('nivel_nombre', $r->pago?->matricula?->ofertaAcademica?->nivelAcademico?->nombre);
                $r->setAttribute('horario_nombre', $r->pago?->matricula?->ofertaAcademica?->horario?->nombre);
                $r->setAttribute('docente_nombre', trim(($r->pago?->matricula?->ofertaAcademica?->docente?->nombre ?? '') . ' ' . ($r->pago?->matricula?->ofertaAcademica?->docente?->apellido ?? '')));
                return $r;
            }),
        ]);
    }

    public function cajaPorCajero(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = DB::table('sesiones_caja')
            ->join('users', 'sesiones_caja.usuario_cajero_id', '=', 'users.id')
            ->join('sucursales', 'sesiones_caja.sucursal_id', '=', 'sucursales.id')
            ->leftJoin('pagos', 'pagos.sesion_caja_id', '=', 'sesiones_caja.id')
            ->leftJoin('recibos_caja', 'recibos_caja.pago_id', '=', 'pagos.id')
            ->leftJoin('metodos_pago', 'recibos_caja.metodo_pago_id', '=', 'metodos_pago.id')
            ->whereBetween(DB::raw($this->expresionFecha('sesiones_caja.fecha_apertura')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                'users.name as cajero_nombre',
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'sesiones_caja.codigo as sesion_codigo',
                'metodos_pago.codigo as metodo_codigo',
                'metodos_pago.nombre as metodo_nombre',
                DB::raw('COUNT(sesiones_caja.id) as sesiones'),
                DB::raw("SUM(CASE WHEN sesiones_caja.estado = 'cerrada' THEN 1 ELSE 0 END) as sesiones_cerradas"),
                DB::raw("SUM(CASE WHEN sesiones_caja.estado = 'abierta' THEN 1 ELSE 0 END) as sesiones_abiertas"),
                DB::raw('SUM(COALESCE(recibos_caja.monto_total, 0)) as total_monto')
            )
            ->groupBy('users.id', 'users.name', 'sucursales.id', 'sucursales.codigo', 'sucursales.nombre', 'sesiones_caja.codigo', 'metodos_pago.id', 'metodos_pago.codigo', 'metodos_pago.nombre');

        $this->aplicarAlcanceReporte($request, $query, 'sesiones_caja.sucursal_id', null);

        if ($request->filled('sucursal_id')) {
            $query->where('sesiones_caja.sucursal_id', $request->sucursal_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('sesiones')->get(),
        ]);
    }

    public function cajaResumenDiario(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $query = DB::table('recibos_caja')
            ->join('sucursales', 'recibos_caja.sucursal_id', '=', 'sucursales.id')
            ->leftJoin('metodos_pago', 'recibos_caja.metodo_pago_id', '=', 'metodos_pago.id')
            ->leftJoin('users', 'recibos_caja.creado_por', '=', 'users.id')
            ->leftJoin('pagos', 'recibos_caja.pago_id', '=', 'pagos.id')
            ->leftJoin('matriculas', 'pagos.matricula_id', '=', 'matriculas.id')
            ->leftJoin('ofertas_academicas', 'matriculas.oferta_academica_id', '=', 'ofertas_academicas.id')
            ->leftJoin('periodos_academicos', 'ofertas_academicas.periodo_academico_id', '=', 'periodos_academicos.id')
            ->leftJoin('niveles_academicos', 'ofertas_academicas.nivel_academico_id', '=', 'niveles_academicos.id')
            ->leftJoin('horarios', 'ofertas_academicas.horario_id', '=', 'horarios.id')
            ->leftJoin('docentes', 'ofertas_academicas.docente_id', '=', 'docentes.id')
            ->leftJoin('versiones_plan_estudio', 'niveles_academicos.version_plan_estudio_id', '=', 'versiones_plan_estudio.id')
            ->leftJoin('planes_estudio', 'versiones_plan_estudio.plan_estudio_id', '=', 'planes_estudio.id')
            ->leftJoin('estudiantes', 'recibos_caja.estudiante_id', '=', 'estudiantes.id')
            ->where('recibos_caja.estado', 'emitido')
            ->whereBetween(DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)')), [$request->fecha_desde, $request->fecha_hasta])
            ->select(
                DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)') . ' as fecha'),
                'sucursales.codigo as sucursal_codigo',
                'sucursales.nombre as sucursal_nombre',
                'metodos_pago.codigo as metodo_codigo',
                'metodos_pago.nombre as metodo_nombre',
                'users.name as cajero_nombre',
                'periodos_academicos.codigo as periodo_codigo',
                'periodos_academicos.nombre as periodo_nombre',
                'planes_estudio.codigo as plan_codigo',
                'planes_estudio.nombre as plan_nombre',
                'niveles_academicos.codigo as nivel_codigo',
                'niveles_academicos.nombre as nivel_nombre',
                'horarios.codigo as horario_codigo',
                'horarios.nombre as horario_nombre',
                'docentes.codigo as docente_codigo',
                DB::raw($this->expresionNombreDocente()),
                'estudiantes.codigo as estudiante_codigo',
                'estudiantes.nombre',
                'estudiantes.apellido',
                'estudiantes.correo',
                'estudiantes.telefono',
                DB::raw('COUNT(recibos_caja.id) as cantidad_recibos'),
                DB::raw('SUM(recibos_caja.monto_total) as total_monto')
            )
            ->groupBy(DB::raw($this->expresionFecha('COALESCE(recibos_caja.fecha_recibo, recibos_caja.creado_en)')), 'sucursales.id', 'sucursales.codigo', 'sucursales.nombre', 'metodos_pago.id', 'metodos_pago.codigo', 'metodos_pago.nombre', 'users.id', 'users.name', 'periodos_academicos.id', 'periodos_academicos.codigo', 'periodos_academicos.nombre', 'planes_estudio.id', 'planes_estudio.codigo', 'planes_estudio.nombre', 'niveles_academicos.id', 'niveles_academicos.codigo', 'niveles_academicos.nombre', 'horarios.id', 'horarios.codigo', 'horarios.nombre', 'docentes.id', 'docentes.codigo', 'docentes.nombre', 'docentes.apellido', 'estudiantes.id', 'estudiantes.codigo', 'estudiantes.nombre', 'estudiantes.apellido', 'estudiantes.correo', 'estudiantes.telefono');

        $this->aplicarAlcanceReporte($request, $query, 'recibos_caja.sucursal_id', 'ofertas_academicas.docente_id');

        if ($request->filled('sucursal_id')) {
            $query->where('recibos_caja.sucursal_id', $request->sucursal_id);
        }

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'OK',
            'data' => $query->orderByDesc('fecha')->orderBy('sucursal_codigo')->get(),
        ]);
    }
}
