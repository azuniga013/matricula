<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->decimal('monto_total', 10, 2)->after('metodo_pago_id')->default(0);
        });

        DB::table('recibos_caja')->update(['monto_total' => DB::raw('monto')]);

        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('concepto_pago_id');
        });

        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->dropColumn('monto');
        });
    }

    public function down(): void
    {
        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->decimal('monto', 10, 2)->after('metodo_pago_id')->default(0);
        });

        DB::table('recibos_caja')->update(['monto' => DB::raw('monto_total')]);

        Schema::table('recibos_caja', function (Blueprint $table) {
            $table->dropColumn('monto_total');
        });
    }
};
