<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->string('tipo_oferta', 20)
                ->default('regular')
                ->after('plan_cobro_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropIndex(['tipo_oferta']);
            $table->dropColumn('tipo_oferta');
        });
    }
};
