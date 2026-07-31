<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use App\Models\ProveedorPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $paypal = ProveedorPago::where('codigo', 'PAYPAL')->first();

        $metodos = [
            ['codigo' => 'EFE', 'nombre' => 'Efectivo', 'descripcion' => 'Pago en efectivo', 'estado' => 'activo', 'portal_disponible' => false, 'proveedor_pago_id' => null],
            ['codigo' => 'DEP', 'nombre' => 'Depósito', 'descripcion' => 'Depósito bancario', 'estado' => 'activo', 'portal_disponible' => true, 'proveedor_pago_id' => null],
            ['codigo' => 'TRA', 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia bancaria', 'estado' => 'activo', 'portal_disponible' => true, 'proveedor_pago_id' => null],
            ['codigo' => 'TAR', 'nombre' => 'Tarjeta', 'descripcion' => 'Pago con tarjeta de crédito/débito', 'estado' => 'activo', 'portal_disponible' => true, 'proveedor_pago_id' => $paypal?->id],
            ['codigo' => 'LNK', 'nombre' => 'Link de pago', 'descripcion' => 'Pago por enlace electrónico', 'estado' => 'activo', 'portal_disponible' => true, 'proveedor_pago_id' => null],
            ['codigo' => 'CHE', 'nombre' => 'Cheque', 'descripcion' => 'Pago con cheque', 'estado' => 'activo', 'portal_disponible' => false, 'proveedor_pago_id' => null],
        ];

        foreach ($metodos as $data) {
            MetodoPago::firstOrCreate(
                ['codigo' => $data['codigo']],
                $data
            );
        }

        MetodoPago::where('codigo', 'TAR')->update(['proveedor_pago_id' => $paypal?->id]);
    }
}
