<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConceptoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();
        $conceptos = [
            ['codigo' => 'MAT', 'nombre' => 'Matrícula', 'tipo_monto' => 'por_oferta', 'monto_fijo' => null, 'monto_minimo' => null, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Cobro de matrícula del estudiante', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta', 'monto_fijo' => null, 'monto_minimo' => null, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Cuota del plan de estudios', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'PMA', 'nombre' => 'Pre-matrícula', 'tipo_monto' => 'por_oferta', 'monto_fijo' => null, 'monto_minimo' => null, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Cobro de pre-matrícula', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'PEX', 'nombre' => 'Examen de nivelación', 'tipo_monto' => 'fijo', 'monto_fijo' => 100.00, 'monto_minimo' => 100.00, 'monto_maximo' => 100.00, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Examen de nivelación para ingreso', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'VLI', 'nombre' => 'Venta de libro', 'tipo_monto' => 'por_inventario', 'monto_fijo' => null, 'monto_minimo' => null, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Venta de libro de texto', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'CHO', 'nombre' => 'Cambio de horario', 'tipo_monto' => 'manual', 'monto_fijo' => null, 'monto_minimo' => 0, 'monto_maximo' => 500.00, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Cobro por cambio de horario', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'CAU', 'nombre' => 'Cargo por mora', 'tipo_monto' => 'manual', 'monto_fijo' => null, 'monto_minimo' => 0, 'monto_maximo' => null, 'requiere_autorizacion_monto' => true, 'descripcion' => 'Cargo por pago tardío', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'RGO', 'nombre' => 'Recargo por cuota vencida', 'tipo_monto' => 'manual', 'monto_fijo' => null, 'monto_minimo' => 0, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Recargo automático por cuota vencida', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
            ['codigo' => 'EOT', 'nombre' => 'Otros servicios en educación', 'tipo_monto' => 'manual', 'monto_fijo' => null, 'monto_minimo' => 0, 'monto_maximo' => null, 'requiere_autorizacion_monto' => false, 'descripcion' => 'Servicios educativos varios', 'estado' => 'activo', 'creado_por' => null, 'actualizado_por' => null, 'creado_en' => $ahora, 'actualizado_en' => $ahora],
        ];

        DB::table('conceptos_pago')->upsert($conceptos, ['codigo']);
    }
}
