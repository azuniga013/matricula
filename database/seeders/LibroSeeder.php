<?php

namespace Database\Seeders;

use App\Models\InventarioLibro;
use App\Models\Libro;
use App\Models\MovimientoInventarioLibro;
use Illuminate\Database\Seeder;

class LibroSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $libros = [
            [
                'codigo' => 'LIB-ING-001',
                'titulo' => 'English Book A1 – Phonics',
                'autor' => 'Cambridge University Press',
                'editorial' => 'Cambridge',
                'isbn' => '9781108727327',
                'precio_venta' => 350.00,
                'nivel_ids' => [1], // ING-1
            ],
            [
                'codigo' => 'LIB-ING-002',
                'titulo' => 'English Book A2 – Beginner',
                'autor' => 'Cambridge University Press',
                'editorial' => 'Cambridge',
                'isbn' => '9781108727334',
                'precio_venta' => 350.00,
                'nivel_ids' => [2], // ING-2
            ],
            [
                'codigo' => 'LIB-ING-003',
                'titulo' => 'English Book B1 – Elementary',
                'autor' => 'Cambridge University Press',
                'editorial' => 'Cambridge',
                'isbn' => '9781108727341',
                'precio_venta' => 380.00,
                'nivel_ids' => [3], // ING-3
            ],
            [
                'codigo' => 'LIB-ING-004',
                'titulo' => 'Workbook Phonics – Ejercicios',
                'autor' => 'San Vicente de Paúl',
                'editorial' => 'Ediciones SVP',
                'isbn' => null,
                'precio_venta' => 180.00,
                'nivel_ids' => [1],
            ],
            [
                'codigo' => 'LIB-ING-005',
                'titulo' => 'Workbook Beginner – Ejercicios',
                'autor' => 'San Vicente de Paúl',
                'editorial' => 'Ediciones SVP',
                'isbn' => null,
                'precio_venta' => 180.00,
                'nivel_ids' => [2],
            ],
        ];

        foreach ($libros as $item) {
            $nivelIds = $item['nivel_ids'];
            unset($item['nivel_ids']);

            $item['creado_por'] = 1;
            $item['actualizado_por'] = 1;
            $item['creado_en'] = $ahora;
            $item['actualizado_en'] = $ahora;

            $libro = Libro::create($item);

            $syncData = [];
            foreach ($nivelIds as $nid) {
                $syncData[$nid] = ['creado_por' => 1, 'actualizado_por' => 1];
            }
            $libro->niveles()->sync($syncData);
        }

        // Inventario inicial en ambas sucursales
        $sucursales = [1, 2]; // SPS, TGU
        $librosInventario = Libro::all();

        foreach ($librosInventario as $libro) {
            foreach ($sucursales as $sucursalId) {
                $inventario = InventarioLibro::create([
                    'libro_id' => $libro->id,
                    'sucursal_id' => $sucursalId,
                    'existencia_actual' => 25,
                    'existencia_minima' => 5,
                    'creado_por' => 1,
                    'creado_en' => $ahora,
                ]);

                MovimientoInventarioLibro::create([
                    'inventario_libro_id' => $inventario->id,
                    'tipo_movimiento' => 'entrada',
                    'cantidad' => 25,
                    'existencia_antes' => 0,
                    'existencia_despues' => 25,
                    'motivo' => 'Carga inicial de inventario',
                    'creado_por' => 1,
                    'creado_en' => $ahora,
                ]);
            }
        }

        $this->command->info('Libros e inventario inicial creados: ' . count($libros) . ' libros, ' . (count($libros) * count($sucursales)) . ' registros de inventario');
    }
}
