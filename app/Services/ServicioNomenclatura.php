<?php

namespace App\Services;

use App\Models\NomenclaturaCodigo;
use Illuminate\Database\QueryException;

class ServicioNomenclatura
{
    public function generarCodigo(
        string $entidad,
        string $formato,
        int $longitudSecuencia = 6,
        ?string $anio = null
    ): array {
        $nomenclatura = $this->obtenerNomenclatura($entidad, $formato, $longitudSecuencia);

        $codigo = $nomenclatura->generarSiguiente($anio);

        return [
            'codigo' => $codigo,
            'secuencia' => $nomenclatura->secuencia_actual,
        ];
    }

    public function previewSiguienteCodigo(
        string $entidad,
        string $formato,
        int $longitudSecuencia = 6,
        ?string $anio = null
    ): array {
        $nomenclatura = $this->obtenerNomenclatura($entidad, $formato, $longitudSecuencia);

        $secuencia = $nomenclatura->secuencia_actual + 1;
        $codigoTemporal = str_replace(
            '{SECUENCIA:' . $longitudSecuencia . '}',
            str_pad((string)$secuencia, $longitudSecuencia, '0', STR_PAD_LEFT),
            $formato
        );

        if ($anio !== null) {
            $codigoTemporal = str_replace('{ANIO}', $anio, $codigoTemporal);
        }

        return [
            'codigo' => $codigoTemporal,
            'secuencia' => $secuencia,
        ];
    }

    private function obtenerNomenclatura(
        string $entidad,
        string $formato,
        int $longitudSecuencia
    ): NomenclaturaCodigo {
        $nomenclatura = NomenclaturaCodigo::where('entidad', $entidad)->first();

        if ($nomenclatura) {
            return $nomenclatura;
        }

        try {
            return NomenclaturaCodigo::create([
                'entidad' => $entidad,
                'formato' => $formato,
                'longitud_secuencia' => $longitudSecuencia,
                'secuencia_actual' => 0,
                'estado' => 'activo',
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            return NomenclaturaCodigo::where('entidad', $entidad)->firstOrFail();
        }
    }
}
