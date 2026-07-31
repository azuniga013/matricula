<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_correos', function (Blueprint $table) {
            $table->longText('cuerpo_html')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_correos', function (Blueprint $table) {
            $table->dropColumn('cuerpo_html');
        });
    }
};
