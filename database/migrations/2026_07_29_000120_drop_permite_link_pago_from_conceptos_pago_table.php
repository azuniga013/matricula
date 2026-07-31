<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conceptos_pago', function (Blueprint $table) {
            if (Schema::hasColumn('conceptos_pago', 'permite_link_pago')) {
                $table->dropColumn('permite_link_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conceptos_pago', function (Blueprint $table) {
            if (! Schema::hasColumn('conceptos_pago', 'permite_link_pago')) {
                $table->boolean('permite_link_pago')->default(false)->after('requiere_autorizacion_monto');
            }
        });
    }
};
