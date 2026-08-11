<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_estudiante_id')->constrained('asistencias_estudiante');
            $table->foreignId('contacto_responsable_estudiante_id')->constrained('contactos_responsable_estudiante');
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->string('canal', 20)->comment('email, whatsapp');
            $table->string('tipo', 30)->comment('falta, tardanza');
            $table->string('clave_idempotente', 191)->unique();
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, enviada, fallida, omitida');
            $table->string('proveedor', 50)->nullable();
            $table->string('identificador_externo', 120)->nullable();
            $table->unsignedInteger('intentos')->default(0);
            $table->text('error_seguro')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('omitido_en')->nullable();
            $table->timestamp('fallido_en')->nullable();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['estado', 'canal'], 'notif_asistencia_estado_canal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_asistencia');
    }
};
