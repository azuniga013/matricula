<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('configuracion_flujo_matricula_metodos')) {
            return;
        }

        Schema::create('configuracion_flujo_matricula_metodos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('configuracion_flujo_matricula_id');
            $table->unsignedBigInteger('metodo_pago_id');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->foreign('configuracion_flujo_matricula_id', 'cfg_flujo_metodo_cfg_fk')->references('id')->on('configuraciones_flujo_matricula')->cascadeOnDelete();
            $table->foreign('metodo_pago_id', 'cfg_flujo_metodo_pago_fk')->references('id')->on('metodos_pago')->cascadeOnDelete();

            $table->unique(['configuracion_flujo_matricula_id', 'metodo_pago_id'], 'cfg_flujo_metodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_flujo_matricula_metodos');
    }
};
