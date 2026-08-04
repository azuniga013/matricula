<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->boolean('alerta_duplicado')->default(false)->after('referencia_externa');
            $table->string('alerta_duplicado_mensaje', 500)->nullable()->after('alerta_duplicado');
            $table->timestamp('alerta_duplicado_en')->nullable()->after('alerta_duplicado_mensaje');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['alerta_duplicado', 'alerta_duplicado_mensaje', 'alerta_duplicado_en']);
        });
    }
};