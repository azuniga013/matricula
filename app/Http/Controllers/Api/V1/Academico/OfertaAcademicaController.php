<?php

namespace App\Http\Controllers\Api\V1\Academico;

use App\Http\Controllers\Controller;
use App\Models\OfertaAcademica;
use App\Models\Sucursal;
use App\Services\ServicioNomenclatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfertaAcademicaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OfertaAcademica::with([
            'sucursal',
            'periodoAcademico',
            'nivelAcademico.versionPlanEstudio',
            'nivelAcademico.regimenAcademico',
            'modalidad',
            'horario',
            'docente',
            'aula',
            'planCobro',
            'grupoWhatsapp',
        ]);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('ofertas_academicas.codigo', 'like', "%{$buscar}%")
                    ->orWhere('ofertas_academicas.observaciones', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('sucursal_id')) {
            $query->porSucursal($request->sucursal_id);
        }

        if ($request->filled('periodo_academico_id')) {
            $query->porPeriodo($request->periodo_academico_id);
        }

        if ($request->filled('nivel_academico_id')) {
            $query->porNivel($request->nivel_academico_id);
        }

        if ($request->filled('version_plan_estudio_id')) {
            $query->whereHas('nivelAcademico', fn ($q) => $q->where('version_plan_estudio_id', $request->version_plan_estudio_id));
        }

        if ($request->filled('docente_id')) {
            $query->porDocente($request->docente_id);
        }

        if ($request->filled('estado')) {
            $query->where('ofertas_academicas.estado', $request->estado);
        }

        $perPage = min((int) $request->get('per_page', 25), 500);
        $ofertas = $query->orderByDesc('ofertas_academicas.creado_en')->paginate($perPage);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $ofertas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'periodo_academico_id' => 'required|exists:periodos_academicos,id',
            'nivel_academico_id' => 'required|exists:niveles_academicos,id',
            'modalidad_id' => 'required|exists:modalidades,id',
            'horario_id' => 'required|exists:horarios,id',
            'docente_id' => 'required|exists:docentes,id',
            'aula_id' => 'required|exists:aulas,id',
            'plan_cobro_id' => 'required|exists:planes_cobro,id',
            'cupo_maximo' => 'nullable|integer|min:1',
            'acepta_cambios_horario' => 'nullable|boolean',
            'grupo_whatsapp_id' => 'nullable|exists:grupos_whatsapp,id',
            'whatsapp_link_periodo' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string',
            'codigo' => 'nullable|string|max:50|unique:ofertas_academicas,codigo',
        ]);

        if (empty($datos['codigo'])) {
            $anio = date('Y');
            $resultado = app(ServicioNomenclatura::class)->generarCodigo(
                entidad: 'ofertas_academicas_' . $anio,
                formato: 'OF-{ANIO}-{SECUENCIA:6}',
                longitudSecuencia: 6,
                anio: $anio,
            );
            $datos['codigo'] = $resultado['codigo'];
        }

        $datos['creado_por'] = $request->user()->id;
        $datos['cupo_maximo'] = $datos['cupo_maximo'] ?? 25;
        $datos['estado'] = 'borrador';

        $oferta = OfertaAcademica::create($datos);
        $oferta->load([
            'sucursal', 'periodoAcademico', 'nivelAcademico.regimenAcademico',
            'modalidad', 'horario', 'docente', 'aula', 'planCobro', 'grupoWhatsapp',
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Oferta académica creada exitosamente',
            'data' => $oferta,
        ], 201);
    }

    public function show(OfertaAcademica $ofertaAcademica): JsonResponse
    {
        $ofertaAcademica->load([
            'sucursal', 'periodoAcademico', 'nivelAcademico.regimenAcademico',
            'modalidad', 'horario', 'docente', 'aula', 'planCobro.detalles.conceptoPago', 'grupoWhatsapp',
        ]);

        $ofertaAcademica->cupos_disponibles = $ofertaAcademica->cuposDisponibles();

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'OK',
            'data' => $ofertaAcademica,
        ]);
    }

    public function update(Request $request, OfertaAcademica $ofertaAcademica): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => 'sometimes|nullable|string|max:50|unique:ofertas_academicas,codigo,' . $ofertaAcademica->id,
            'sucursal_id' => 'sometimes|required|exists:sucursales,id',
            'periodo_academico_id' => 'sometimes|required|exists:periodos_academicos,id',
            'nivel_academico_id' => 'sometimes|required|exists:niveles_academicos,id',
            'modalidad_id' => 'sometimes|required|exists:modalidades,id',
            'horario_id' => 'sometimes|required|exists:horarios,id',
            'docente_id' => 'sometimes|required|exists:docentes,id',
            'aula_id' => 'sometimes|required|exists:aulas,id',
            'observaciones' => 'nullable|string',
            'plan_cobro_id' => 'sometimes|required|exists:planes_cobro,id',
            'acepta_cambios_horario' => 'nullable|boolean',
            'grupo_whatsapp_id' => 'nullable|exists:grupos_whatsapp,id',
            'whatsapp_link_periodo' => 'nullable|string|max:500',
            'cupo_maximo' => 'nullable|integer|min:1',
            'estado' => 'sometimes|string|in:borrador,abierto,cerrado,cancelado',
        ]);

        $datos['actualizado_por'] = $request->user()->id;

        if (isset($datos['cupo_maximo'])) {
            $nuevoMax = $datos['cupo_maximo'];
            $ocupados = $ofertaAcademica->cupos_matriculados + $ofertaAcademica->cupos_reservados;
            if ($nuevoMax < $ocupados) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => 'El cupo máximo no puede ser menor a los cupos ya ocupados (' . $ocupados . ')',
                    'errores' => ['cupo_maximo' => ['Mínimo ' . $ocupados . ' por cupos ocupados']],
                ], 422);
            }
        }

        if (isset($datos['estado']) && $datos['estado'] !== $ofertaAcademica->estado) {
            $permitidos = $this->transicionesManualesPermitidas()[$ofertaAcademica->estado] ?? [];
            if (! in_array($datos['estado'], $permitidos, true)) {
                return response()->json([
                    'resultado' => 'R',
                    'codigo' => 422,
                    'mensaje' => "No se permite cambiar una oferta en estado {$ofertaAcademica->estado} a {$datos['estado']} manualmente.",
                ], 422);
            }
        }

        $ofertaAcademica->update($datos);

        if ($ofertaAcademica->estado !== 'cancelado') {
            $disponibles = $ofertaAcademica->cuposDisponibles();
            if ($disponibles <= 0 && $ofertaAcademica->estado === 'abierto') {
                $ofertaAcademica->update(['estado' => 'lleno']);
            } elseif ($disponibles > 0 && $ofertaAcademica->estado === 'lleno') {
                $ofertaAcademica->update(['estado' => 'abierto']);
            }
        }

        $ofertaAcademica->load([
            'sucursal', 'periodoAcademico', 'nivelAcademico.regimenAcademico',
            'modalidad', 'horario', 'docente', 'aula', 'planCobro', 'grupoWhatsapp',
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 0,
            'mensaje' => 'Oferta académica actualizada exitosamente',
            'data' => $ofertaAcademica,
        ]);
    }

    private function transicionesManualesPermitidas(): array
    {
        return [
            'borrador' => ['abierto', 'cancelado'],
            'abierto' => ['cerrado', 'cancelado'],
            'lleno' => ['cerrado', 'cancelado'],
            'cerrado' => ['cancelado'],
            'cancelado' => [],
        ];
    }

    public function actualizarWhatsappPeriodo(Request $request, OfertaAcademica $ofertaAcademica): JsonResponse
    {
        $usuario = $request->user();
        $permisos = collect($usuario->permisosEfectivos())->pluck('codigo')->all();
        $puedeAdministrarOferta = in_array('ofertas.academicas.modificar', $permisos, true);
        $puedeDocente = $usuario->docente_id
            && $ofertaAcademica->docente_id === $usuario->docente_id
            && (in_array('asistencias.crear', $permisos, true) || in_array('calificaciones.modificar', $permisos, true));

        if (! $puedeAdministrarOferta && ! $puedeDocente) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 403,
                'mensaje' => 'No tiene permiso para actualizar el link de WhatsApp de esta oferta.',
            ], 403);
        }

        $datos = $request->validate([
            'whatsapp_link_periodo' => 'nullable|string|max:500',
        ]);

        if (! $ofertaAcademica->grupo_whatsapp_id) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'Debe asignar primero un grupo lógico de WhatsApp a la oferta.',
            ], 422);
        }

        $link = trim((string) ($datos['whatsapp_link_periodo'] ?? ''));
        if ($link !== '' && ! preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . $link;
        }
        if ($link !== '' && ! filter_var($link, FILTER_VALIDATE_URL)) {
            return response()->json([
                'resultado' => 'R',
                'codigo' => 422,
                'mensaje' => 'El link de WhatsApp no tiene un formato válido.',
            ], 422);
        }

        $ofertaAcademica->update([
            'whatsapp_link_periodo' => $link !== '' ? $link : null,
            'actualizado_por' => $usuario->id,
        ]);

        return response()->json([
            'resultado' => 'A',
            'codigo' => 200,
            'mensaje' => 'Link de WhatsApp del período actualizado correctamente.',
            'data' => $ofertaAcademica->fresh(),
        ]);
    }
}
