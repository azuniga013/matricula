<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos', 'link_pago_url')) {
                $table->string('link_pago_url', 500)->nullable()->after('referencia_externa');
            }
            if (!Schema::hasColumn('pagos', 'link_pago_estado')) {
                $table->string('link_pago_estado', 20)->nullable()->after('link_pago_url');
            }
            if (!Schema::hasColumn('pagos', 'link_generado_por')) {
                $table->foreignId('link_generado_por')->nullable()->after('link_pago_estado')->constrained('users');
            }
            if (!Schema::hasColumn('pagos', 'link_generado_en')) {
                $table->timestamp('link_generado_en')->nullable()->after('link_generado_por');
            }
            if (!Schema::hasColumn('pagos', 'confirmado_por_estudiante_id')) {
                $table->foreignId('confirmado_por_estudiante_id')->nullable()->after('link_generado_en')->constrained('users');
            }
            if (!Schema::hasColumn('pagos', 'confirmado_por_estudiante_en')) {
                $table->timestamp('confirmado_por_estudiante_en')->nullable()->after('confirmado_por_estudiante_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'confirmado_por_estudiante_en')) {
                $table->dropColumn('confirmado_por_estudiante_en');
            }
            if (Schema::hasColumn('pagos', 'confirmado_por_estudiante_id')) {
                $table->dropConstrainedForeignId('confirmado_por_estudiante_id');
            }
            if (Schema::hasColumn('pagos', 'link_generado_en')) {
                $table->dropColumn('link_generado_en');
            }
            if (Schema::hasColumn('pagos', 'link_generado_por')) {
                $table->dropConstrainedForeignId('link_generado_por');
            }
            if (Schema::hasColumn('pagos', 'link_pago_estado')) {
                $table->dropColumn('link_pago_estado');
            }
            if (Schema::hasColumn('pagos', 'link_pago_url')) {
                $table->dropColumn('link_pago_url');
            }
        });
    }
};
