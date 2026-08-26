<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (! Schema::hasColumn('enlaces_pago', 'metodo_pago_id')) {
                $table->foreignId('metodo_pago_id')
                    ->nullable()
                    ->after('concepto_pago_id')
                    ->constrained('metodos_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (Schema::hasColumn('enlaces_pago', 'metodo_pago_id')) {
                $table->dropConstrainedForeignId('metodo_pago_id');
            }
        });
    }
};
