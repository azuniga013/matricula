<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('link_pago_url', 500)->nullable()->after('referencia_externa');
            $table->string('link_pago_estado', 20)->nullable()->after('link_pago_url');
            $table->foreignId('link_generado_por')->nullable()->after('link_pago_estado')->constrained('users');
            $table->timestamp('link_generado_en')->nullable()->after('link_generado_por');
            $table->foreignId('confirmado_por_estudiante_id')->nullable()->after('link_generado_en')->constrained('users');
            $table->timestamp('confirmado_por_estudiante_en')->nullable()->after('confirmado_por_estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmado_por_estudiante_id');
            $table->dropConstrainedForeignId('link_generado_por');
            $table->dropColumn([
                'link_pago_url',
                'link_pago_estado',
                'link_generado_en',
                'confirmado_por_estudiante_en',
            ]);
        });
    }
};
