<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos_responsable_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->string('nombre', 150);
            $table->string('parentesco', 50)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('telefono_whatsapp', 30)->nullable();
            $table->boolean('recibe_asistencia_email')->default(false);
            $table->boolean('recibe_asistencia_whatsapp')->default(false);
            $table->timestamp('consentimiento_asistencia_en')->nullable();
            $table->text('consentimiento_evidencia')->nullable();
            $table->unsignedInteger('prioridad')->default(1);
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->index(['estudiante_id', 'estado'], 'contacto_resp_estudiante_estado_idx');
        });

        DB::table('estudiantes')
            ->where(function ($query) {
                $query->whereNotNull('nombre_padre')
                    ->orWhereNotNull('correo_padre')
                    ->orWhereNotNull('telefono_padre');
            })
            ->orderBy('id')
            ->chunkById(100, function ($estudiantes) {
                foreach ($estudiantes as $estudiante) {
                    DB::table('contactos_responsable_estudiante')->insert([
                        'estudiante_id' => $estudiante->id,
                        'nombre' => $estudiante->nombre_padre ?: 'Responsable sin nombre registrado',
                        'parentesco' => 'padre',
                        'correo' => $estudiante->correo_padre,
                        'telefono_whatsapp' => $estudiante->telefono_padre,
                        'recibe_asistencia_email' => false,
                        'recibe_asistencia_whatsapp' => false,
                        'prioridad' => 1,
                        'estado' => 'activo',
                        'creado_por' => $estudiante->creado_por,
                        'creado_en' => now(),
                        'actualizado_en' => now(),
                    ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_responsable_estudiante');
    }
};
