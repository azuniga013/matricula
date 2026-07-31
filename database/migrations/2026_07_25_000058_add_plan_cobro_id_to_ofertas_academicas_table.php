<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->foreignId('plan_cobro_id')->nullable()->after('aula_id')->constrained('planes_cobro');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_academicas', function (Blueprint $table) {
            $table->dropForeign(['plan_cobro_id']);
            $table->dropColumn('plan_cobro_id');
        });
    }
};
