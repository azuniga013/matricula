<?php

namespace Database\Seeders;

use App\Models\ConfiguracionProveedorPago;
use App\Models\ProveedorPago;
use Illuminate\Database\Seeder;

class ProveedorPagoSeeder extends Seeder
{
    public function run(): void
    {
        $paypal = ProveedorPago::firstOrCreate(
            ['codigo' => 'PAYPAL'],
            [
                'nombre' => 'PayPal',
                'descripcion' => 'Pasarela de pago con tarjeta de crédito/débito a través de PayPal',
                'activo' => true,
                'creado_en' => now(),
            ]
        );

        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $paypal->id, 'clave' => 'modo'],
            ['valor' => 'sandbox']
        );
        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $paypal->id, 'clave' => 'client_id'],
            ['valor' => env('PAYPAL_CLIENT_ID', '')]
        );
        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $paypal->id, 'clave' => 'client_secret'],
            ['valor' => env('PAYPAL_CLIENT_SECRET', '')]
        );
        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $paypal->id, 'clave' => 'webhook_id'],
            ['valor' => env('PAYPAL_WEBHOOK_ID', '')]
        );

        $stripe = ProveedorPago::firstOrCreate(
            ['codigo' => 'STRIPE'],
            [
                'nombre' => 'Stripe',
                'descripcion' => 'Pasarela de pago con tarjeta de crédito/débito vía Stripe',
                'activo' => true,
                'creado_en' => now(),
            ]
        );

        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $stripe->id, 'clave' => 'modo'],
            ['valor' => 'sandbox']
        );
        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $stripe->id, 'clave' => 'secret_key'],
            ['valor' => env('STRIPE_SECRET_KEY', '')]
        );
        ConfiguracionProveedorPago::firstOrCreate(
            ['proveedor_pago_id' => $stripe->id, 'clave' => 'public_key'],
            ['valor' => env('STRIPE_PUBLIC_KEY', '')]
        );
    }
}
