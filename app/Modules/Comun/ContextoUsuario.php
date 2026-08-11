<?php

namespace App\Modules\Comun;

final class ContextoUsuario
{
    public function __construct(
        private readonly int $usuarioId,
    ) {}

    public static function desdeRequest(): self
    {
        return new self((int) (auth('sanctum')->id() ?: auth()->id()));
    }

    public function usuarioId(): int
    {
        return $this->usuarioId;
    }
}
