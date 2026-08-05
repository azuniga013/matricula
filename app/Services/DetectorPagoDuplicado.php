<?php

namespace App\Services;

use App\Mail\AlertaPagoDuplicado;
use App\Models\MetodoPago;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DetectorPagoDuplicado
{
    public const DESTINATARIOS = [
        'antalma61@hotmail.com',
        'kcontreras1995@hotmail.com',
    ];

    public const METODOS_VALIDABLES = ['DEP', 'TRA'];

    public function metodoValidable(MetodoPago $metodo): bool
    {
        return in_array($metodo->codigo, self::METODOS_VALIDABLES, true);
    }

    public function detectar(int $estudianteId, ?string $referencia, ?Carbon $fechaProceso, ?int $excluirPagoId = null): array
    {
        $referencia = trim((string) $referencia);
        if ($referencia === '' || $fechaProceso === null) {
            return [];
        }

        $query = Pago::query()
            ->with(['estudiante', 'reciboCaja'])
            ->where('estudiante_id', '!=', $estudianteId)
            ->whereNotNull('referencia_externa')
            ->whereRaw('TRIM(referencia_externa) = ?', [$referencia])
            ->whereNotNull('fecha_deposito')
            ->whereDate('fecha_deposito', $fechaProceso->toDateString());

        if ($excluirPagoId !== null) {
            $query->where('id', '!=', $excluirPagoId);
        }

        return $query->orderBy('fecha_deposito')
            ->limit(10)
            ->get()
            ->all();
    }

    public function aplicar(Pago $pago, ?string $referencia, ?Carbon $fechaProceso, bool $enviarCorreo = true): array
    {
        if (!$pago->metodoPago || !$this->metodoValidable($pago->metodoPago)) {
            return ['duplicado' => false, 'coincidencias' => []];
        }

        $coincidencias = $this->detectar(
            (int) $pago->estudiante_id,
            $referencia,
            $fechaProceso,
            (int) $pago->id
        );

        if (empty($coincidencias)) {
            $pago->alerta_duplicado = false;
            $pago->alerta_duplicado_mensaje = null;
            $pago->alerta_duplicado_en = null;
            $pago->save();

            return ['duplicado' => false, 'coincidencias' => []];
        }

        $mensaje = $this->construirMensaje($coincidencias);
        $pago->alerta_duplicado = true;
        $pago->alerta_duplicado_mensaje = $mensaje;
        $pago->alerta_duplicado_en = now();
        $pago->save();

        if ($enviarCorreo) {
            $this->enviarCorreo($pago, $coincidencias);
        }

        return ['duplicado' => true, 'coincidencias' => $coincidencias];
    }

    protected function construirMensaje(array $coincidencias): string
    {
        $nombres = array_map(function (Pago $c) {
            $estudiante = $c->estudiante;
            $nombre = $estudiante
                ? trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellido ?? ''))
                : 'Estudiante #' . $c->estudiante_id;

            $recibo = $c->reciboCaja?->numero_recibo ? ' · Recibo ' . $c->reciboCaja->numero_recibo : '';

            return sprintf(
                '%s (%s · %s · %s%s)',
                $c->codigo,
                $nombre,
                $c->fecha_deposito?->format('d/m/Y'),
                $c->estado,
                $recibo
            );
        }, $coincidencias);

        return 'Referencia y fecha ya usadas por otro estudiante: ' . implode(' | ', $nombres);
    }

    protected function enviarCorreo(Pago $pago, array $coincidencias): void
    {
        $estudiante = $pago->estudiante;
        $nombreCompleto = $estudiante
            ? trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellido ?? ''))
            : 'Estudiante #' . $pago->estudiante_id;

        $coincidenciasData = array_map(function (Pago $c) {
            $cEstudiante = $c->estudiante;
            $recibo = $c->reciboCaja;

            return [
                'codigo' => (string) ($c->codigo ?? '—'),
                'codigo_estudiante' => (string) ($cEstudiante->codigo ?? '—'),
                'estudiante' => $cEstudiante
                    ? trim(($cEstudiante->nombre ?? '') . ' ' . ($cEstudiante->apellido ?? ''))
                    : 'Estudiante #' . $c->estudiante_id,
                'metodo' => (string) ($c->metodoPago?->nombre ?? '—'),
                'referencia' => (string) ($c->referencia_externa ?? '—'),
                'fecha' => $c->fecha_deposito?->format('d/m/Y'),
                'monto' => $c->monto !== null ? number_format((float) $c->monto, 2) : '—',
                'estado' => $c->estado ?: '—',
                'numero_recibo' => $recibo?->numero_recibo ? (string) $recibo->numero_recibo : null,
                'fecha_recibo' => $recibo?->fecha_recibo
                    ? $recibo->fecha_recibo->format('d/m/Y')
                    : ($recibo?->fecha_proceso?->format('d/m/Y') ?? null),
                'estado_recibo' => $recibo?->estado ?? null,
            ];
        }, $coincidencias);

        try {
            Mail::to(self::DESTINATARIOS)->send(new AlertaPagoDuplicado(
                codigoPagoNuevo: (string) ($pago->codigo ?? '—'),
                codigoEstudianteNuevo: (string) ($estudiante->codigo ?? '—'),
                nombreEstudianteNuevo: $nombreCompleto,
                metodo: (string) ($pago->metodoPago?->nombre ?? '—'),
                referencia: (string) ($pago->referencia_externa ?? ''),
                fechaPago: $pago->fecha_deposito?->format('d/m/Y'),
                coincidencias: $coincidenciasData
            ));
        } catch (\Throwable $e) {
            logger()->warning('No se pudo enviar alerta de pago duplicado: ' . $e->getMessage());
        }
    }
}