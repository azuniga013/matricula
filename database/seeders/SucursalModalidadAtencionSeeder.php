<?php

namespace Database\Seeders;

use App\Models\Modalidad;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalModalidadAtencionSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = Modalidad::query()->where('tipo', 'atencion')->pluck('id', 'codigo');
        $sucursales = Sucursal::query()->pluck('id', 'codigo');

        $asignaciones = [
            'SPS' => ['PRES', 'VIRT'],
            'TGU' => ['PRES', 'VIRT'],
        ];

        foreach ($asignaciones as $codigoSucursal => $codigosModalidad) {
            $sucursalId = $sucursales[$codigoSucursal] ?? null;
            if (! $sucursalId) {
                continue;
            }

            $idsModalidad = [];
            foreach ($codigosModalidad as $codigoModalidad) {
                if (isset($modalidades[$codigoModalidad])) {
                    $idsModalidad[] = $modalidades[$codigoModalidad];
                }
            }

            if (! empty($idsModalidad)) {
                Sucursal::find($sucursalId)?->modalidadesAtencion()->syncWithoutDetaching($idsModalidad);
            }
        }
    }
}
