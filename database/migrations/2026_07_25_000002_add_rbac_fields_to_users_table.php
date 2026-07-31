<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('email');
            $table->string('estado', 20)->default('activo')->after('telefono');
            $table->timestamp('bloqueado_hasta')->nullable()->after('estado');
            $table->boolean('debe_cambiar_contrasena')->default(false)->after('bloqueado_hasta');
            $table->unsignedBigInteger('docente_id')->nullable()->after('debe_cambiar_contrasena');
            $table->unsignedBigInteger('sucursal_id')->nullable()->after('docente_id');
            $table->unsignedBigInteger('creado_por')->nullable()->after('sucursal_id');
            $table->timestamp('creado_en')->nullable()->after('creado_por');
            $table->unsignedBigInteger('actualizado_por')->nullable()->after('creado_en');
            $table->timestamp('actualizado_en')->nullable()->after('actualizado_por');

            $table->index('estado');
            $table->index('sucursal_id');
            $table->index('docente_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telefono', 'estado', 'bloqueado_hasta', 'debe_cambiar_contrasena',
                'docente_id', 'sucursal_id', 'creado_por', 'creado_en',
                'actualizado_por', 'actualizado_en',
            ]);
        });
    }
};
