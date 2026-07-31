<?php

namespace Database\Seeders;

use App\Models\GrupoWhatsapp;
use App\Models\OfertaAcademica;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class GrupoWhatsappSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();

        $sucursalSPS = Sucursal::where('codigo', 'SPS')->first();
        $sucursalTGU = Sucursal::where('codigo', 'TGU')->first();

        if (!$sucursalSPS || !$sucursalTGU) {
            $this->command->error('Ejecute primero SucursalSeeder');
            return;
        }

        $grupos = [
            // San Pedro Sula
            [
                'sucursal_id' => $sucursalSPS->id,
                'codigo' => 'GRUP-SPS-ING1-INT-MAT',
                'nombre' => 'Inglés 1 Intensivo Matutino SPS',
                'link' => 'https://chat.whatsapp.com/EqXkMfVpL1K7R2Jt9ZwY3A',
                'oferta_codigo' => 'SPS-2026I-ING1-INT-MAT',
            ],
            [
                'sucursal_id' => $sucursalSPS->id,
                'codigo' => 'GRUP-SPS-ING2-INT-MAT',
                'nombre' => 'Inglés 2 Intensivo Matutino SPS',
                'link' => 'https://chat.whatsapp.com/BnRtH8sDxM4Q6WkLyZcV9B',
                'oferta_codigo' => 'SPS-2026I-ING2-INT-MAT',
            ],
            [
                'sucursal_id' => $sucursalSPS->id,
                'codigo' => 'GRUP-SPS-ING1-SEMI-VES',
                'nombre' => 'Inglés 1 Semi Intensivo Vespertino SPS',
                'link' => 'https://chat.whatsapp.com/CfUjV5wGrN8P0SaTbYdX2C',
                'oferta_codigo' => 'SPS-2026I-ING1-SEMI-VES',
            ],
            // Tegucigalpa
            [
                'sucursal_id' => $sucursalTGU->id,
                'codigo' => 'GRUP-TGU-ING1-INT-MAT',
                'nombre' => 'Inglés 1 Intensivo Matutino TGU',
                'link' => 'https://chat.whatsapp.com/DhWkZ7xHoP2R4TnBvLqY5D',
                'oferta_codigo' => 'TGU-2026I-ING1-INT-MAT',
            ],
            [
                'sucursal_id' => $sucursalTGU->id,
                'codigo' => 'GRUP-TGU-ING3-INT-MAT',
                'nombre' => 'Inglés 3 Intensivo Matutino TGU',
                'link' => 'https://chat.whatsapp.com/FjYmX1zKpQ3S6UwNeArB7E',
                'oferta_codigo' => 'TGU-2026I-ING3-INT-MAT',
            ],
        ];

        $creados = 0;
        foreach ($grupos as $item) {
            $ofertaCodigo = $item['oferta_codigo'];
            unset($item['oferta_codigo']);

            $item['estado'] = 'activo';
            $item['creado_por'] = 1;
            $item['actualizado_por'] = 1;
            $item['creado_en'] = $ahora;
            $item['actualizado_en'] = $ahora;

            $grupo = GrupoWhatsapp::updateOrCreate(
                ['codigo' => $item['codigo']],
                $item
            );

            $oferta = OfertaAcademica::where('codigo', $ofertaCodigo)->first();
            if ($oferta && !$oferta->grupo_whatsapp_id) {
                $oferta->update(['grupo_whatsapp_id' => $grupo->id]);
            }

            $creados++;
        }

        $this->command->info("{$creados} grupos de WhatsApp creados y vinculados a sus ofertas académicas.");
    }
}
