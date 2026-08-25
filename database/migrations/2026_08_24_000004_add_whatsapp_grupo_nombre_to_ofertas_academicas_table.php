<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ofertas_academicas', 'whatsapp_grupo_nombre')) {
            Schema::table('ofertas_academicas', function (Blueprint $table) {
                $table->string('whatsapp_grupo_nombre', 150)
                    ->nullable()
                    ->after('whatsapp_link_periodo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ofertas_academicas', 'whatsapp_grupo_nombre')) {
            Schema::table('ofertas_academicas', function (Blueprint $table) {
                $table->dropColumn('whatsapp_grupo_nombre');
            });
        }
    }
};
