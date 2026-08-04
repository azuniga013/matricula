<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaPagoDuplicado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $codigoPagoNuevo,
        public string $codigoEstudianteNuevo,
        public string $nombreEstudianteNuevo,
        public string $metodo,
        public ?string $referencia,
        public ?string $fechaPago,
        public array $coincidencias
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta: Referencia de pago duplicada detectada — Cursos San Vicente de Paúl',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-pago-duplicado',
        );
    }
}