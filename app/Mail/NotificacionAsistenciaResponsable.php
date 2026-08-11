<?php

namespace App\Mail;

use App\Models\NotificacionAsistencia;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotificacionAsistenciaResponsable extends Mailable
{
    public function __construct(
        public NotificacionAsistencia $notificacion,
    ) {}

    public function envelope(): Envelope
    {
        $asistencia = $this->notificacion->asistencia;
        $estudiante = $this->notificacion->estudiante;

        return new Envelope(
            subject: 'Aviso de asistencia: '.ucfirst($asistencia?->estado ?? 'asistencia').' de '.trim(($estudiante?->nombre ?? '').' '.($estudiante?->apellido ?? '')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion_asistencia_responsable',
        );
    }
}
