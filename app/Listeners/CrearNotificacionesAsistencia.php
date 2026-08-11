<?php

namespace App\Listeners;

use App\Events\AsistenciaNotificableRegistrada;
use App\Models\AsistenciaEstudiante;
use App\Models\NotificacionAsistencia;
use Illuminate\Contracts\Queue\ShouldQueue;

class CrearNotificacionesAsistencia implements ShouldQueue
{
    public function handle(AsistenciaNotificableRegistrada $event): void
    {
        $asistencias = AsistenciaEstudiante::with('matricula.estudiante.contactosResponsable')
            ->whereIn('id', $event->asistenciaIds)
            ->get();

        foreach ($asistencias as $asistencia) {
            if (! in_array($asistencia->estado, ['falta', 'tardanza'], true)) {
                continue;
            }

            $estudiante = $asistencia->matricula?->estudiante;
            if (! $estudiante) {
                continue;
            }

            $contactos = $estudiante->contactosResponsable()
                ->activos()
                ->vigentes()
                ->whereNotNull('consentimiento_asistencia_en')
                ->orderBy('prioridad')
                ->get();

            foreach ($contactos as $contacto) {
                $this->crearSiCorresponde($asistencia, $estudiante->id, $contacto->id, 'email', $contacto->recibe_asistencia_email, $contacto->correo);
                $this->crearSiCorresponde($asistencia, $estudiante->id, $contacto->id, 'whatsapp', $contacto->recibe_asistencia_whatsapp, $contacto->telefono_whatsapp);
            }
        }
    }

    private function crearSiCorresponde(AsistenciaEstudiante $asistencia, int $estudianteId, int $contactoId, string $canal, bool $recibe, ?string $destino): void
    {
        if (! $recibe || empty($destino)) {
            return;
        }

        $clave = sprintf(
            'asistencia:%d:contacto:%d:canal:%s:estado:%s',
            $asistencia->id,
            $contactoId,
            $canal,
            $asistencia->estado,
        );

        NotificacionAsistencia::firstOrCreate(
            ['clave_idempotente' => $clave],
            [
                'asistencia_estudiante_id' => $asistencia->id,
                'contacto_responsable_estudiante_id' => $contactoId,
                'estudiante_id' => $estudianteId,
                'canal' => $canal,
                'tipo' => $asistencia->estado,
                'estado' => 'pendiente',
                'intentos' => 0,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        );
    }
}
