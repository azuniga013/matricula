<?php

namespace App\Services\NotificacionesAsistencia;

use App\Mail\NotificacionAsistenciaResponsable;
use App\Models\BitacoraCorreo;
use App\Models\NotificacionAsistencia;
use Illuminate\Support\Facades\Mail;

class ProcesadorNotificacionAsistencia
{
    public function __construct(
        private readonly ConfiguracionNotificacionesAsistencia $configuracion,
    ) {}

    public function procesar(NotificacionAsistencia $notificacion): void
    {
        $notificacion->loadMissing('contacto', 'estudiante', 'asistencia.ofertaAcademica.nivelAcademico');

        if (! in_array($notificacion->estado, ['pendiente', 'fallida'], true)) {
            return;
        }

        $maxIntentos = (int) config('notificaciones_asistencia.max_intentos', 3);

        if ($notificacion->intentos >= $maxIntentos) {
            $this->marcarOmitida($notificacion, 'Máximo de intentos alcanzado.');

            return;
        }

        $notificacion->update([
            'intentos' => $notificacion->intentos + 1,
            'actualizado_en' => now(),
        ]);

        if (! config('notificaciones_asistencia.activar_envios', false)) {
            $this->marcarOmitida($notificacion, 'Los envíos reales están deshabilitados por configuración.');

            return;
        }

        if ($notificacion->canal === 'email') {
            if (! $this->configuracion->emailHabilitado()) {
                $this->marcarOmitida($notificacion, 'El canal email está deshabilitado por configuración.');

                return;
            }

            $this->enviarCorreo($notificacion);

            return;
        }

        if ($notificacion->canal === 'whatsapp') {
            $this->procesarWhatsapp($notificacion);

            return;
        }

        $this->marcarFallida($notificacion, 'No hay proveedor configurado para el canal solicitado.');
    }

    private function procesarWhatsapp(NotificacionAsistencia $notificacion): void
    {
        if (! $this->configuracion->whatsappHabilitado()) {
            $this->marcarOmitida($notificacion, 'El canal WhatsApp está deshabilitado por configuración.');

            return;
        }

        $driver = $this->configuracion->whatsappDriver();

        if ($driver === 'stub') {
            $notificacion->update([
                'estado' => 'omitida',
                'proveedor' => 'whatsapp_stub',
                'error_seguro' => 'Proveedor WhatsApp en modo stub; no se envía mensaje real.',
                'omitido_en' => now(),
                'actualizado_en' => now(),
            ]);

            return;
        }

        if (in_array($driver, ['', 'deshabilitado'], true)) {
            $this->marcarFallida($notificacion, 'No hay proveedor oficial de WhatsApp configurado.');

            return;
        }

        if (! $this->configuracion->whatsappRemitente()) {
            $this->marcarFallida($notificacion, 'Falta el remitente configurado para WhatsApp.');

            return;
        }

        $this->marcarFallida($notificacion, 'El driver de WhatsApp aún no está implementado.');
    }

    private function enviarCorreo(NotificacionAsistencia $notificacion): void
    {
        $destinatario = $notificacion->contacto?->correo;
        if (! $destinatario) {
            $this->marcarFallida($notificacion, 'El contacto no tiene correo configurado.');

            return;
        }

        try {
            Mail::to($destinatario)->send(new NotificacionAsistenciaResponsable($notificacion));

            BitacoraCorreo::create([
                'destinatario' => $destinatario,
                'asunto' => 'Aviso de asistencia',
                'tipo' => 'asistencia_familia',
                'codigo_estudiante' => $notificacion->estudiante?->codigo,
                'estado' => 'enviado',
                'error' => null,
                'cuerpo_html' => null,
                'creado_en' => now(),
            ]);

            $notificacion->update([
                'estado' => 'enviada',
                'proveedor' => 'mail',
                'enviado_en' => now(),
                'error_seguro' => null,
                'actualizado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            BitacoraCorreo::create([
                'destinatario' => $destinatario,
                'asunto' => 'Aviso de asistencia',
                'tipo' => 'asistencia_familia',
                'codigo_estudiante' => $notificacion->estudiante?->codigo,
                'estado' => 'fallido',
                'error' => $e->getMessage(),
                'cuerpo_html' => null,
                'creado_en' => now(),
            ]);

            $this->marcarFallida($notificacion, 'No se pudo enviar el correo institucional.');
        }
    }

    private function marcarOmitida(NotificacionAsistencia $notificacion, string $motivo): void
    {
        $notificacion->update([
            'estado' => 'omitida',
            'error_seguro' => $motivo,
            'omitido_en' => now(),
            'actualizado_en' => now(),
        ]);
    }

    private function marcarFallida(NotificacionAsistencia $notificacion, string $motivo): void
    {
        $notificacion->update([
            'estado' => 'fallida',
            'error_seguro' => $motivo,
            'fallido_en' => now(),
            'actualizado_en' => now(),
        ]);
    }
}
