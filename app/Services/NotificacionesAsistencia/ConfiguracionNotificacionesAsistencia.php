<?php

namespace App\Services\NotificacionesAsistencia;

use App\Models\ParametroGlobal;

class ConfiguracionNotificacionesAsistencia
{
    private function grupo(): string
    {
        return (string) config('notificaciones_asistencia.grupo_parametros', 'notificaciones_asistencia');
    }

    public function emailHabilitado(): bool
    {
        $valor = ParametroGlobal::obtener('EMAIL_HABILITADO', $this->grupo());

        return $valor !== null
            ? filter_var($valor, FILTER_VALIDATE_BOOLEAN)
            : (bool) config('notificaciones_asistencia.email.habilitado', true);
    }

    public function whatsappHabilitado(): bool
    {
        $valor = ParametroGlobal::obtener('WHATSAPP_HABILITADO', $this->grupo());

        return $valor !== null
            ? filter_var($valor, FILTER_VALIDATE_BOOLEAN)
            : (bool) config('notificaciones_asistencia.whatsapp.habilitado', false);
    }

    public function whatsappDriver(): string
    {
        return (string) (ParametroGlobal::obtener('WHATSAPP_DRIVER', $this->grupo())
            ?? config('notificaciones_asistencia.whatsapp.driver', 'deshabilitado'));
    }

    public function whatsappRemitente(): ?string
    {
        return ParametroGlobal::obtener('WHATSAPP_REMITENTE', $this->grupo())
            ?? config('notificaciones_asistencia.whatsapp.remitente');
    }

    public function whatsappPlantilla(): string
    {
        return (string) (ParametroGlobal::obtener('WHATSAPP_PLANTILLA', $this->grupo())
            ?? config('notificaciones_asistencia.whatsapp.plantilla', 'asistencia_basica'));
    }
}
