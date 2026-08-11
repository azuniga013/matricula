<?php

namespace App\Jobs;

use App\Models\NotificacionAsistencia;
use App\Services\NotificacionesAsistencia\ProcesadorNotificacionAsistencia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcesarNotificacionAsistencia implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $notificacionId,
    ) {}

    public function handle(ProcesadorNotificacionAsistencia $procesador): void
    {
        $notificacion = NotificacionAsistencia::find($this->notificacionId);
        if (! $notificacion) {
            return;
        }

        $procesador->procesar($notificacion);
    }
}
