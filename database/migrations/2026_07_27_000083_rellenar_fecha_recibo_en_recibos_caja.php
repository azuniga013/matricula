<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('recibos_caja')
            ->leftJoin('pagos', 'recibos_caja.pago_id', '=', 'pagos.id')
            ->whereNull('recibos_caja.fecha_recibo')
            ->select([
                'recibos_caja.id as id',
                'recibos_caja.fecha_proceso as fecha_recibo_directa',
                'recibos_caja.creado_en as creado_en_recibo',
                'pagos.fecha_proceso as fecha_proceso_pago',
                'pagos.fecha_aprobacion as fecha_aprobacion_pago',
                'pagos.creado_en as creado_en_pago',
            ])
            ->orderBy('recibos_caja.id')
            ->chunkById(200, function ($recibos) {
                foreach ($recibos as $recibo) {
                    $fecha = $recibo->fecha_recibo_directa
                        ?? $recibo->fecha_proceso_pago
                        ?? $recibo->fecha_aprobacion_pago
                        ?? $recibo->creado_en_pago
                        ?? $recibo->creado_en_recibo
                        ?? now();

                    DB::table('recibos_caja')
                        ->where('id', $recibo->id)
                        ->update(['fecha_recibo' => $fecha]);
                }
            }, 'recibos_caja.id');
    }

    public function down(): void
    {
        DB::table('recibos_caja')->update(['fecha_recibo' => null]);
    }
};
