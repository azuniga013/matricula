<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (! Schema::hasColumn('enlaces_pago', 'monto_objetivo')) {
                $table->decimal('monto_objetivo', 10, 2)->nullable()->after('monto');
            }
            if (! Schema::hasColumn('enlaces_pago', 'estado_operativo')) {
                $table->string('estado_operativo', 20)->default('disponible')->after('estado');
            }
            if (! Schema::hasColumn('enlaces_pago', 'asignado_a_pago_id')) {
                $table->foreignId('asignado_a_pago_id')->nullable()->after('estado_operativo')->constrained('pagos');
            }
            if (! Schema::hasColumn('enlaces_pago', 'asignado_a_estudiante_id')) {
                $table->foreignId('asignado_a_estudiante_id')->nullable()->after('asignado_a_pago_id')->constrained('estudiantes');
            }
            if (! Schema::hasColumn('enlaces_pago', 'fecha_asignacion')) {
                $table->timestamp('fecha_asignacion')->nullable()->after('asignado_a_estudiante_id');
            }
            if (! Schema::hasColumn('enlaces_pago', 'fecha_uso')) {
                $table->timestamp('fecha_uso')->nullable()->after('fecha_asignacion');
            }
            if (! Schema::hasColumn('enlaces_pago', 'observaciones')) {
                $table->string('observaciones', 500)->nullable()->after('fecha_uso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (Schema::hasColumn('enlaces_pago', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
            if (Schema::hasColumn('enlaces_pago', 'fecha_uso')) {
                $table->dropColumn('fecha_uso');
            }
            if (Schema::hasColumn('enlaces_pago', 'fecha_asignacion')) {
                $table->dropColumn('fecha_asignacion');
            }
            if (Schema::hasColumn('enlaces_pago', 'asignado_a_estudiante_id')) {
                $table->dropConstrainedForeignId('asignado_a_estudiante_id');
            }
            if (Schema::hasColumn('enlaces_pago', 'asignado_a_pago_id')) {
                $table->dropConstrainedForeignId('asignado_a_pago_id');
            }
            if (Schema::hasColumn('enlaces_pago', 'estado_operativo')) {
                $table->dropColumn('estado_operativo');
            }
            if (Schema::hasColumn('enlaces_pago', 'monto_objetivo')) {
                $table->dropColumn('monto_objetivo');
            }
        });
    }
};
