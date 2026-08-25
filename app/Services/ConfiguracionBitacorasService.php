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
}
