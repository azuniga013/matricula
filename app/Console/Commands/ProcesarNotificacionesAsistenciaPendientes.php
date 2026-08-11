<?php

namespace App\Console\Commands;

use App\Jobs\ProcesarNotificacionAsistencia;
use App\Models\NotificacionAsistencia;
use Illuminate\Console\Command;

class ProcesarNotificacionesAsistenciaPendientes extends Command
{
    protected $signature = 'asistencias:notificaciones-procesar {--limite= : Cantidad máxima de notificaciones a procesar}';

    protected $description = 'Despacha el procesamiento de notificaciones de asistencia pendientes o fallidas';

    public function handle(): int
    {
        $limite = (int) ($this->option('limite') ?: config('notificaciones_asistencia.lote_procesamiento', 100));

        $notificaciones = NotificacionAsistencia::query()
            ->whereIn('estado', ['pendiente', 'fallida'])
            ->orderBy('id')
            ->limit($limite)
            ->get(['id']);

        foreach ($notificaciones as $notificacion) {
            ProcesarNotificacionAsistencia::dispatch($notificacion->id);
        }

        $this->info("Notificaciones despachadas: {$notificaciones->count()}");

        return self::SUCCESS;
    }
}
