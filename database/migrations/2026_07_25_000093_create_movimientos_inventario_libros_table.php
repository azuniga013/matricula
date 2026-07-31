<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario_libros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_libro_id')->constrained('inventario_libros');
            $table->string('tipo_movimiento', 20)->comment('entrada, salida, ajuste');
            $table->integer('cantidad');
            $table->integer('existencia_antes');
            $table->integer('existencia_despues');
            $table->text('motivo')->nullable();
            $table->nullableMorphs('referencia', 'mov_inv_ref_idx');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario_libros');
    }
};
