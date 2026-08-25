<?php

namespace App\Console\Commands;

use App\Models\BitacoraAuditoria;
use App\Models\BitacoraCorreo;
use App\Models\BitacoraPeticion;
use App\Models\BitacoraSeguridad;
use App\Services\ConfiguracionBitacorasService;
use Illuminate\Console\Command;

class PurgarBitacorasCommand extends Command
{
    protected $signature = 'bitacoras:purgar
        {--auditoria= : Días de retención para bitacora_auditoria}
        {--peticiones= : Días de retención para bitacora_peticiones}
        {--seguridad= : Días de retención para bitacora_seguridad}
        {--correos= : Días de retención para bitacora_correos}
        {--dry-run : Solo muestra cuántos registros se eliminarían}';

    protected $description = 'Purga registros antiguos de las distintas bitácoras según su retención';

    public function __construct(
        private readonly ConfiguracionBitacorasService $configuracion,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $retenciones = [
            'auditoria' => (int) ($this->option('auditoria') ?? $this->configuracion->retencionAuditoriaDias()),
            'peticiones' => (int) ($this->option('peticiones') ?? $this->configuracion->retencionPeticionesDias()),
            'seguridad' => (int) ($this->option('seguridad') ?? $this->configuracion->retencionSeguridadDias()),
            'correos' => (int) ($this->option('correos') ?? $this->configuracion->retencionCorreosDias()),
        ];

        $dryRun = (bool) $this->option('dry-run');

        $resultado = [
            'bitacora_auditoria' => $this->procesar(BitacoraAuditoria::query(), 'creado_en', $retenciones['auditoria'], $dryRun),
            'bitacora_peticiones' => $this->procesar(BitacoraPeticion::query(), 'created_at', $retenciones['peticiones'], $dryRun),
            'bitacora_seguridad' => $this->procesar(BitacoraSeguridad::query(), 'created_at', $retenciones['seguridad'], $dryRun),
            'bitacora_correos' => $this->procesar(BitacoraCorreo::query(), 'creado_en', $retenciones['correos'], $dryRun),
        ];

        foreach ($resultado as $tabla => $cantidad) {
            $this->line("{$tabla}: {$cantidad}");
        }

        $this->info($dryRun ? 'Dry-run completado.' : 'Purga completada.');

        return self::SUCCESS;
    }

    private function procesar($query, string $columnaFecha, int $dias, bool $dryRun): int
    {
        if ($dias <= 0) {
            return 0;
        }

        $limite = now()->subDays($dias);
        $target = (clone $query)->where($columnaFecha, '<', $limite);

        if ($dryRun) {
            return $target->count();
        }

        return $target->delete();
    }
}
