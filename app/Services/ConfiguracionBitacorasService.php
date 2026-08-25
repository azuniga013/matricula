<?php

namespace App\Services;

use App\Models\ParametroGlobal;

class ConfiguracionBitacorasService
{
    private const GRUPO = 'bitacoras';

    public function peticionesHabilitadas(): bool
    {
        $valor = ParametroGlobal::obtener('BITACORA_PETICIONES_HABILITADA', self::GRUPO);
        return $valor !== null ? filter_var($valor, FILTER_VALIDATE_BOOLEAN) : (bool) config('seguridad.bitacora.registrar_peticiones', true);
    }

    public function seguridadHabilitada(): bool
    {
        $valor = ParametroGlobal::obtener('BITACORA_SEGURIDAD_HABILITADA', self::GRUPO);
        return $valor !== null ? filter_var($valor, FILTER_VALIDATE_BOOLEAN) : (bool) config('seguridad.bitacora.registrar_seguridad', true);
    }

    public function auditoriaCentralHabilitada(): bool
    {
        $valor = ParametroGlobal::obtener('AUDITORIA_CENTRAL_HABILITADA', self::GRUPO);
        return $valor !== null ? filter_var($valor, FILTER_VALIDATE_BOOLEAN) : true;
    }

    public function correosHabilitada(): bool
    {
        $valor = ParametroGlobal::obtener('BITACORA_CORREOS_HABILITADA', self::GRUPO);
        return $valor !== null ? filter_var($valor, FILTER_VALIDATE_BOOLEAN) : true;
    }

    public function retencionAuditoriaDias(): int
    {
        return $this->obtenerRetencion('RETENCION_AUDITORIA_DIAS', 180);
    }

    public function retencionPeticionesDias(): int
    {
        return $this->obtenerRetencion('RETENCION_PETICIONES_DIAS', 30);
    }

    public function retencionSeguridadDias(): int
    {
        return $this->obtenerRetencion('RETENCION_SEGURIDAD_DIAS', 365);
    }

    public function retencionCorreosDias(): int
    {
        return $this->obtenerRetencion('RETENCION_CORREOS_DIAS', 90);
    }

    private function obtenerRetencion(string $codigo, int $default): int
    {
        $valor = ParametroGlobal::obtener($codigo, self::GRUPO);
        $dias = $valor !== null ? (int) $valor : $default;

        return max(0, $dias);
    }
}
