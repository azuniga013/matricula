<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('versiones_plan_estudio', function (Blueprint $table) {
            $table->string('codigo', 50)->unique()->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('versiones_plan_estudio', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
