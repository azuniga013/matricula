<?php

namespace App\Modules\Comun;

final class ResultadoCasoUso
{
    private function __construct(
        private readonly bool $ok,
        private readonly int $codigo,
        private readonly string $mensaje,
        private readonly ?string $codigoError,
        private readonly array $data,
    ) {}

    public static function exito(string $mensaje, array $data = [], int $codigo = 200): self
    {
        return new self(true, $codigo, $mensaje, null, $data);
    }

    public static function error(int $codigo, string $mensaje, ?string $codigoError = null): self
    {
        return new self(false, $codigo, $mensaje, $codigoError, []);
    }

    public function ok(): bool
    {
        return $this->ok;
    }

    public function codigo(): int
    {
        return $this->codigo;
    }

    public function mensaje(): string
    {
        return $this->mensaje;
    }

    public function codigoError(): ?string
    {
        return $this->codigoError;
    }

    public function data(): array
    {
        return $this->data;
    }
}
