<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->foreignId('proveedor_pago_id')
                ->nullable()
                ->after('portal_disponible')
                ->constrained('proveedores_pago');
        });
    }

    public function down(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_pago_id');
        });
    }
};
