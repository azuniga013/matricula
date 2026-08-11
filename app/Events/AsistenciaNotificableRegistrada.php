<?php

namespace App\Events;

class AsistenciaNotificableRegistrada
{
    /**
     * @param  int[]  $asistenciaIds
     */
    public function __construct(
        public readonly array $asistenciaIds,
    ) {}
}
