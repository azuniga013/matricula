<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            if (Schema::hasColumn('ofertas_academicas', 'grupo_whatsapp_id')) {
                $table->dropConstrainedForeignId('grupo_whatsapp_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            if (! Schema::hasColumn('ofertas_academicas', 'grupo_whatsapp_id')) {
                $table->unsignedBigInteger('grupo_whatsapp_id')->nullable()->after('acepta_cambios_horario');
            }
        });
    }
};
