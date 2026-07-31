<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_plan_cobro', function (Blueprint $table) {
            $table->unique(['plan_cobro_id', 'numero_cuota'], 'uq_detalle_plan_cobro_plan_cuota');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_plan_cobro', function (Blueprint $table) {
            $table->dropUnique('uq_detalle_plan_cobro_plan_cuota');
        });
    }
};
