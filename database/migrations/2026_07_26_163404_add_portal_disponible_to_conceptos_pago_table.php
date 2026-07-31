<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conceptos_pago', function (Blueprint $table) {
            $table->boolean('portal_disponible')->default(true)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('conceptos_pago', function (Blueprint $table) {
            $table->dropColumn('portal_disponible');
        });
    }
};
