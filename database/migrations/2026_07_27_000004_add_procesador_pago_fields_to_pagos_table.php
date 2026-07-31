<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('proveedor_pago_id')
                ->nullable()
                ->after('metodo_pago_id')
                ->constrained('proveedores_pago');
            $table->string('transaccion_id', 100)
                ->nullable()
                ->after('proveedor_pago_id');
            $table->text('procesador_respuesta')
                ->nullable()
                ->after('transaccion_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('procesador_respuesta');
            $table->dropColumn('transaccion_id');
            $table->dropConstrainedForeignId('proveedor_pago_id');
        });
    }
};
