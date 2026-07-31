<?php

namespace Database\Seeders;

use App\Models\ConfiguracionFlujoMatricula;
use Illuminate\Database\Seeder;

class ConfiguracionFlujoMatriculaSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            'habilita_reserva_cupo' => true,
            'habilita_carga_comprobante' => true,
            'requiere_comprobante' => true,
            'habilita_revision_contable' => true,
            'habilita_aprobacion_pago' => true,
            'habilita_generacion_recibo' => true,
            'habilita_confirmacion_matricula' => true,
            'habilita_seleccion_obligaciones' => true,
            'habilita_whatsapp' => true,
            'habilita_reenganche' => true,
            'habilita_solicitud_link' => true,
        ];

        foreach ([
            ['codigo' => 'FLUJO-EST-DEFAULT', 'origen' => 'portal_estudiante'],
            ['codigo' => 'FLUJO-ADM-DEFAULT', 'origen' => 'portal_administrativo'],
        ] as $cfg) {
            ConfiguracionFlujoMatricula::firstOrCreate(
                ['codigo' => $cfg['codigo']],
                $cfg + [
                    'concepto_pago_id' => 1,
                    'metodo_pago_id' => null,
                    'estado' => 'activo',
                ] + $base
            );
        }
    }
}
