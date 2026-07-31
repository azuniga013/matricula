<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['confirmado_por_estudiante_id']);
            $table->foreign('confirmado_por_estudiante_id')->references('id')->on('estudiantes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['confirmado_por_estudiante_id']);
            $table->foreign('confirmado_por_estudiante_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
