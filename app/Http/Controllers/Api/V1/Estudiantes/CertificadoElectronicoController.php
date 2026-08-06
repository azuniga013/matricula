<?php

namespace App\Http\Controllers\Api\V1\Estudiantes;

use App\Http\Controllers\Controller;
use App\Models\{CertificadoElectronico, HistorialAcademico, Calificacion};
use App\Helpers\RespuestaError;
use App\Services\ServicioBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class CertificadoElectronicoController extends Controller
{
    public function __construct(protected ServicioBitacora $bitacora) {}

    /**
     * Relaciones completas necesarias para enriquecer el certificado (PDF y validación).
     */
    private function cargarRelaciones(): array
    {
        return [
            'estudiante:id,codigo,nombre,apellido',
            'nivelAcademico:id,codigo,nombre,orden,version_plan_estudio_id,regimen_academico_id',
            'nivelAcademico.versionPlanEstudio:id,plan_estudio_id,numero_version',
            'nivelAcademico.versionPlanEstudio.planEstudio:id,departamento_academico_id,nombre',
            'nivelAcademico.versionPlanEstudio.planEstudio.departamentoAcademico:id,nombre',
            'nivelAcademico.regimenAcademico:id,nombre',
            'historialAcademico:id,codigo,estudiante_id,matricula_id,oferta_academica_id,nivel_academico_id,periodo_academico_id,nota_final,faltas,estado',
            'historialAcademico.ofertaAcademica:id,sucursal_id,periodo_academico_id,nivel_academico_id,modalidad_id,horario_id,docente_id',
            'historialAcademico.ofertaAcademica.sucursal:id,nombre',
            'historialAcademico.ofertaAcademica.periodoAcademico:id,codigo,nombre',
            'historialAcademico.ofertaAcademica.modalidad:id,nombre',
            'historialAcademico.ofertaAcademica.docente:id,nombre,apellido',
        ];
    }

    public function emitir(Request $request): JsonResponse
    {
        $estudiante = $request->attributes->get('estudiante');
        $datos = $request->validate(['historial_academico_id' => 'required|exists:historial_academico,id']);

        $historial = HistorialAcademico::query()
            ->where('id', $datos['historial_academico_id'])
            ->where('estudiante_id', $estudiante->id)
            ->first();

        if (!$historial) {
            return RespuestaError::noEncontrado('historial académico')->response($request);
        }

        if ($historial->estado !== 'aprobado' || $historial->nota_final === null) {
            return RespuestaError::make('422_NIVEL_NO_APROBADO', 422, 'Solo se puede emitir el certificado si el nivel está aprobado', 'estado=' . $historial->estado . ' nota_final=' . ($historial->nota_final ?? 'null'))
                ->response($request);
        }

        $cert = CertificadoElectronico::firstOrCreate(
            ['historial_academico_id' => $historial->id],
            [
                'codigo' => 'CER-' . now()->format('Y') . '-' . Str::upper(Str::random(8)),
                'token_validacion' => hash('sha256', $historial->id . '|' . $estudiante->id . '|' . Str::random(40)),
                'estudiante_id' => $estudiante->id,
                'nivel_academico_id' => $historial->nivel_academico_id,
                'nota_final' => $historial->nota_final,
                'estado' => 'emitido',
                'emitido_en' => now(),
                'codigo_verificacion' => strtoupper(substr(hash('sha256', $historial->codigo . '|' . $estudiante->codigo), 0, 12)),
            ]
        );

        $this->generarPdf($cert);
        $cert->load($this->cargarRelaciones());

        $this->bitacora->registrarOperacionPermitida(
            null,
            'emitir_certificado_electronico',
            'portal_estudiante',
            $request->ip(),
            $request->userAgent() ?? '',
            $cert->id,
            null,
            ['codigo' => $cert->codigo, 'nivel' => $cert->nivelAcademico->nombre ?? null]
        );

        return response()->json([
            'resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Certificado generado',
            'data' => $this->toCertificadoDto($cert, true),
        ]);
    }

    public function validar(Request $request, string $token): JsonResponse
    {
        $cert = CertificadoElectronico::with($this->cargarRelaciones())
            ->where('token_validacion', $token)
            ->first();

        if (!$cert) {
            return RespuestaError::make('404_CERTIFICADO_NO_ENCONTRADO', 404, 'El certificado no existe o el enlace no es válido', 'token no encontrado')
                ->response($request);
        }

        if (empty($cert->validado_en)) {
            $cert->update(['validado_en' => now()]);
            $this->bitacora->registrarOperacionPermitida(
                null, 'validar_certificado_electronico', 'certificados_electronicos',
                $request->ip(), $request->userAgent() ?? '', $cert->id, null, ['codigo' => $cert->codigo]
            );
        }

        return response()->json([
            'resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Certificado válido',
            'data' => $this->toCertificadoDto($cert, true),
        ]);
    }

    public function pdf(Request $request, string $token)
    {
        $cert = CertificadoElectronico::with($this->cargarRelaciones())
            ->where('token_validacion', $token)
            ->first();

        if (!$cert) {
            return RespuestaError::make('404_CERTIFICADO_NO_ENCONTRADO', 404, 'El certificado no existe o el enlace no es válido', 'token no encontrado')
                ->response($request);
        }

        if ($cert->ruta_pdf && Storage::disk('public')->exists($cert->ruta_pdf)) {
            return response()->download(
                Storage::disk('public')->path($cert->ruta_pdf),
                'certificado-' . $cert->codigo . '.pdf'
            );
        }

        return $this->generarPdf($cert)->download('certificado-' . $cert->codigo . '.pdf');
    }

    public function emitirAdmin(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'historial_academico_id' => 'nullable|exists:historial_academico,id',
            'calificacion_id' => 'nullable|exists:calificaciones,id',
        ]);

        $historial = null;
        if (!empty($datos['historial_academico_id'])) {
            $historial = HistorialAcademico::with(['nivelAcademico', 'estudiante'])->findOrFail($datos['historial_academico_id']);
        } elseif (!empty($datos['calificacion_id'])) {
            $calificacion = Calificacion::with(['matricula.ofertaAcademica.nivelAcademico', 'estudiante'])->findOrFail($datos['calificacion_id']);
            $historial = HistorialAcademico::with(['nivelAcademico', 'estudiante'])
                ->where('estudiante_id', $calificacion->estudiante_id)
                ->where('matricula_id', $calificacion->matricula_id)
                ->where('nivel_academico_id', $calificacion->ofertaAcademica?->nivel_academico_id)
                ->where('periodo_academico_id', $calificacion->ofertaAcademica?->periodo_academico_id)
                ->first();
            if (!$historial && $calificacion->matricula && $calificacion->ofertaAcademica) {
                $calificacion->loadMissing(['ofertaAcademica.periodoAcademico', 'ofertaAcademica.modalidad']);
                $historial = HistorialAcademico::create([
                    'codigo' => substr('HIS-' . $calificacion->codigo, 0, 50),
                    'estudiante_id' => $calificacion->estudiante_id,
                    'matricula_id' => $calificacion->matricula_id,
                    'oferta_academica_id' => $calificacion->oferta_academica_id,
                    'nivel_academico_id' => $calificacion->ofertaAcademica->nivel_academico_id,
                    'periodo_academico_id' => $calificacion->ofertaAcademica->periodo_academico_id,
                    'estado' => $calificacion->estaAprobada() ? 'aprobado' : 'reprobado',
                    'nota_final' => $calificacion->nota_final,
                    'faltas' => $calificacion->faltas ?? 0,
                    'observaciones' => $calificacion->observaciones,
                ]);
            }
            if (!$historial) {
                return RespuestaError::make('404_HISTORIAL_NO_ENCONTRADO', 404, 'No se encontró historial académico para la calificación seleccionada', 'calificacion_id=' . $datos['calificacion_id'])
                    ->response($request);
            }
        }

        if (!$historial) {
            return RespuestaError::make('422_DATOS_INSUFICIENTES', 422, 'Debe indicar un historial o una calificación válida', 'sin identificador')
                ->response($request);
        }

        if ($historial->estado !== 'aprobado' || $historial->nota_final === null) {
            return RespuestaError::make('422_NIVEL_NO_APROBADO', 422, 'Solo se puede emitir el certificado si el nivel está aprobado', 'estado=' . $historial->estado)
                ->response($request);
        }

        $cert = CertificadoElectronico::firstOrCreate(
            ['historial_academico_id' => $historial->id],
            [
                'codigo' => 'CER-' . now()->format('Y') . '-' . Str::upper(Str::random(8)),
                'token_validacion' => hash('sha256', $historial->id . '|' . $historial->estudiante_id . '|' . Str::random(40)),
                'estudiante_id' => $historial->estudiante_id,
                'nivel_academico_id' => $historial->nivel_academico_id,
                'nota_final' => $historial->nota_final,
                'estado' => 'emitido',
                'emitido_en' => now(),
                'codigo_verificacion' => strtoupper(substr(hash('sha256', $historial->codigo . '|' . $historial->estudiante_id), 0, 12)),
            ]
        );

        $this->generarPdf($cert);
        $cert->load($this->cargarRelaciones());

        $this->bitacora->registrarOperacionPermitida(
            (int) ($request->user()?->id ?? 0),
            'emitir_certificado_electronico_admin',
            'certificados_electronicos',
            $request->ip(),
            $request->userAgent() ?? '',
            $cert->id,
            null,
            ['codigo' => $cert->codigo, 'nivel' => $cert->nivelAcademico->nombre ?? null]
        );

        return response()->json([
            'resultado' => 'A', 'codigo' => 200, 'mensaje' => 'Certificado generado',
            'data' => $this->toCertificadoDto($cert, true),
        ]);
    }

    public function listarPorEstudiante(int $estudianteId): JsonResponse
    {
        $certificados = CertificadoElectronico::with(['nivelAcademico', 'historialAcademico.ofertaAcademica.sucursal', 'historialAcademico.ofertaAcademica.periodoAcademico'])
            ->where('estudiante_id', $estudianteId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($cert) => [
                'codigo' => $cert->codigo,
                'nivel' => $cert->nivelAcademico?->nombre,
                'sucursal' => $cert->historialAcademico?->ofertaAcademica?->sucursal?->nombre,
                'periodo' => $cert->historialAcademico?->ofertaAcademica?->periodoAcademico?->nombre,
                'nota_final' => $cert->nota_final,
                'emitido_en' => $cert->emitido_en,
                'pdf_url' => route('certificados.pdf', $cert->token_validacion),
                'vista_url' => route('certificados.validar', $cert->token_validacion),
            ]);

        return response()->json(['resultado' => 'A', 'codigo' => 200, 'mensaje' => 'OK', 'data' => $certificados]);
    }

    /**
     * Construye el PDF del certificado (Udemy-style), lo almacena en disco público y actualiza la ruta.
     */
    private function generarPdf(CertificadoElectronico $cert)
    {
        $cert->load($this->cargarRelaciones());
        $qrUrl = route('certificados.validar', $cert->token_validacion);

        $pdf = Pdf::loadView('estudiante.certificado_pdf', [
            'cert' => $cert,
            'qrDataUri' => $this->generarQrDataUri($qrUrl),
            'verificacionUrl' => $qrUrl,
        ])->setPaper('letter', 'landscape');

        $rutaPdf = 'certificados/' . $cert->codigo . '.pdf';
        Storage::disk('public')->put($rutaPdf, $pdf->output());
        $cert->update(['ruta_pdf' => $rutaPdf]);

        return $pdf;
    }

    private function generarQrDataUri(string $url): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(220)
            ->margin(0)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /**
     * Arma el DTO enriquecido del certificado para la API (emisión y validación pública).
     */
    private function toCertificadoDto(CertificadoElectronico $cert, bool $conUrls = false): array
    {
        $h = $cert->historialAcademico;
        $o = $h?->ofertaAcademica;
        $n = $cert->nivelAcademico;
        $version = $n?->versionPlanEstudio;
        $plan = $version?->planEstudio;
        $departamento = $plan?->departamentoAcademico;
        $regimen = $n?->regimenAcademico;
        $modalidad = $o?->modalidad;
        $sucursal = $o?->sucursal;
        $periodo = $o?->periodoAcademico;
        $docente = $o?->docente;

        $planLabel = $plan && $version ? sprintf('%s · V%d', $plan->nombre, $version->numero_version) : ($plan?->nombre);

        $data = [
            'id' => $cert->id,
            'codigo' => $cert->codigo,
            'codigo_verificacion' => $cert->codigo_verificacion,
            'token_validacion' => $cert->token_validacion,
            'estado' => $cert->estado,
            'nota_final' => $cert->nota_final !== null ? number_format((float) $cert->nota_final, 2) : null,
            'emitido_en' => optional($cert->emitido_en)->format('d/m/Y H:i'),
            'validado_en' => optional($cert->validado_en)?->format('d/m/Y H:i'),
            'estudiante' => [
                'codigo' => $cert->estudiante?->codigo,
                'nombre' => $cert->estudiante?->nombre,
                'apellido' => $cert->estudiante?->apellido,
                'nombre_completo' => trim(($cert->estudiante?->nombre ?? '') . ' ' . ($cert->estudiante?->apellido ?? '')),
            ],
            'nivel' => [
                'codigo' => $n?->codigo,
                'nombre' => $n?->nombre,
                'orden' => $n?->orden,
            ],
            'plan_estudio' => $planLabel,
            'departamento_academico' => $departamento?->nombre,
            'periodo' => $periodo?->nombre,
            'sucursal' => $sucursal?->nombre,
            'regimen' => $regimen?->nombre,
            'modalidad' => $modalidad?->nombre,
            'docente' => $docente ? trim($docente->nombre . ' ' . $docente->apellido) : null,
        ];

        if ($conUrls) {
            $data['pdf_url'] = route('certificados.pdf', $cert->token_validacion);
            $data['vista_url'] = route('certificados.validar', $cert->token_validacion);
        }

        return $data;
    }
}
