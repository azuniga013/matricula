<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesEstudiante extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombre;
    public string $codigo;
    public string $email;
    public string $password;
    public bool $esRegistro;

    public function __construct(
        string $nombre,
        string $codigo,
        string $email,
        string $password,
        bool $esRegistro = true
    ) {
        $this->nombre = $nombre;
        $this->codigo = $codigo;
        $this->email = $email;
        $this->password = $password;
        $this->esRegistro = $esRegistro;
    }

    public function envelope(): Envelope
    {
        $asunto = $this->esRegistro
            ? 'Bienvenido a Cursos San Vicente de Paúl — Credenciales de Acceso'
            : 'Tu cuenta ha sido activada — Cursos San Vicente de Paúl';

        return new Envelope(
            subject: $asunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales-estudiante',
        );
    }
}
