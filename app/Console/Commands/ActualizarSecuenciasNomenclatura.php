<?php

namespace App\Console\Commands;

use App\Models\NomenclaturaCodigo;
use App\Models\Pago;
use App\Models\Calificacion;
use App\Models\SesionCaja;
use App\Models\Matricula;
use App\Models\Estudiante;
use Illuminate\Console\Command;

class ActualizarSecuenciasNomenclatura extends Command
{
    protected $signature = 'nomenclatura:actualizar-secuencias {--anio= : Año para las entidades (por defecto año actual)}';
    protected $description = 'Actualiza secuencia_actual en nomenclaturas_codigos según registros existentes';

    public function handle(): int
    {
        $anio = $this->option('anio') ?? date('Y');

        $entidades = [
            'pagos_' . $anio => [
                'modelo' => Pago::class,
                'formato' => 'PAG-{ANIO}-{SECUENCIA:6}',
                'longitud' => 6,
            ],
            'calificaciones_' . $anio => [
                'modelo' => Calificacion::class,
                'formato' => 'CAL-{ANIO}-{SECUENCIA:6}',
                'longitud' => 6,
            ],
            'sesiones_caja_' . $anio => [
                'modelo' => SesionCaja::class,
                'formato' => 'SCA-{ANIO}-{SECUENCIA:6}',
                'longitud' => 6,
            ],
            'matriculas_' . $anio => [
                'modelo' => Matricula::class,
                'formato' => 'MAT-{ANIO}-{SECUENCIA:8}',
                'longitud' => 8,
            ],
            'estudiantes_' . $anio => [
                'modelo' => Estudiante::class,
                'formato' => 'EST-{ANIO}-{SECUENCIA:8}',
                'longitud' => 8,
            ],
        ];

        $this->line("Actualizando secuencias para el año {$anio}:");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($entidades));
        $bar->start();

        foreach ($entidades as $entidad => $config) {
            $total = $config['modelo']::count();

            NomenclaturaCodigo::updateOrCreate(
                ['entidad' => $entidad],
                [
                    'formato' => $config['formato'],
                    'longitud_secuencia' => $config['longitud'],
                    'secuencia_actual' => $total,
                    'estado' => 'activo',
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Secuencias actualizadas correctamente.');

        return self::SUCCESS;
    }
}
