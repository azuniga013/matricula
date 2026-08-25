<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametros_globales', function (Blueprint $table) {
            $table->string('grupo', 50)
                ->default('01')
                ->comment('Código de grupo funcional del parámetro')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('parametros_globales', function (Blueprint $table) {
            $table->string('grupo', 10)
                ->default('01')
                ->comment('Código de grupo, ej 01 = Generales')
                ->change();
        });
    }
};
