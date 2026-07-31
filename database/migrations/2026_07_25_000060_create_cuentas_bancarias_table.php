<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('banco', 150);
            $table->string('numero_cuenta', 50);
            $table->string('tipo_cuenta', 50)->default('ahorro');
            $table->string('estado', 20)->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
