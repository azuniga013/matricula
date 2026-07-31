<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            if (!Schema::hasColumn('metodos_pago', 'permite_link_pago')) {
                $table->boolean('permite_link_pago')->default(false)->after('portal_disponible');
            }
        });
    }

    public function down(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            if (Schema::hasColumn('metodos_pago', 'permite_link_pago')) {
                $table->dropColumn('permite_link_pago');
            }
        });
    }
};
