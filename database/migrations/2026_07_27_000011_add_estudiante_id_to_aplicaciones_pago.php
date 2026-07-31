<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aplicaciones_pago', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes');
        });
    }

    public function down(): void
    {
        Schema::table('aplicaciones_pago', function (Blueprint $table) {
            $table->dropForeign(['estudiante_id']);
            $table->dropColumn('estudiante_id');
        });
    }
};
