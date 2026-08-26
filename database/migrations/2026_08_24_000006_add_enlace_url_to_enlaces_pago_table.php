<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (! Schema::hasColumn('enlaces_pago', 'enlace_url')) {
                $table->string('enlace_url', 500)->nullable()->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enlaces_pago', function (Blueprint $table) {
            if (Schema::hasColumn('enlaces_pago', 'enlace_url')) {
                $table->dropColumn('enlace_url');
            }
        });
    }
};
