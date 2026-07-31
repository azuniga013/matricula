<?php

namespace App\Services\Pagos;

use App\Contracts\EstadoTransaccion;
use App\Contracts\ProcesadorPago;
use App\Contracts\ResultadoProcesamiento;
use App\Models\Pago;
use App\Models\ProveedorPago;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalProcesador implements ProcesadorPago
{
    private ProveedorPago $proveedor;
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->proveedor = ProveedorPago::where('codigo', 'PAYPAL')
            ->with('configuraciones')
            ->firstOrFail();

        $modo = $this->config('modo', 'sandbox');
        $this->baseUrl = $modo === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = $this->config('client_id', '');
        $this->clientSecret = $this->config('client_secret', '');
    }

    public function proveedorCodigo(): string
    {
        return 'PAYPAL';
    }

    public function procesar(Pago $pago, array $datos): ResultadoProcesamiento
    {
        try {
            $token = $this->obtenerToken();
            $returnUrl = $datos['return_url'] ?? route('portal.pagos.paypal.retorno');
            $cancelUrl = $datos['cancel_url'] ?? route('portal.pagos.paypal.cancelado');

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $pago->id,
                        'description' => "Pago {$pago->codigo} - Cursos San Vicente de Paul",
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format((float) $pago->monto, 2, '.', ''),
                        ],
                    ]],
                    'payment_source' => [
                        'paypal' => [
                            'experience_context' => [
                                'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                                'landing_page' => 'LOGIN',
                                'user_action' => 'PAY_NOW',
                                'return_url' => $returnUrl,
                                'cancel_url' => $cancelUrl,
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('PayPal create order error', [
                    'pago_id' => $pago->id,
                    'response' => $response->body(),
                ]);
                return new ResultadoProcesamiento(
                    exitoso: false,
                    error: $response->json('message') ?? 'Error al crear la orden en PayPal',
                );
            }

            $data = $response->json();
            $approvalUrl = collect($data['links'] ?? [])
                ->firstWhere('rel', 'payer-action');

            return new ResultadoProcesamiento(
                exitoso: true,
                transaccionId: $data['id'],
                redirectUrl: $approvalUrl['href'] ?? null,
                datosCliente: ['order_id' => $data['id']],
            );
        } catch (\Throwable $e) {
            Log::error('PayPal procesar exception', [
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
            ]);
            return new ResultadoProcesamiento(
                exitoso: false,
                error: 'Error al conectar con PayPal: ' . $e->getMessage(),
            );
        }
    }

    public function capturar(string $transaccionId): ResultadoProcesamiento
    {
        try {
            $token = $this->obtenerToken();

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$transaccionId}/capture");

            if (!$response->successful()) {
                Log::error('PayPal capture error', [
                    'transaccion_id' => $transaccionId,
                    'response' => $response->body(),
                ]);
                return new ResultadoProcesamiento(
                    exitoso: false,
                    error: $response->json('message') ?? 'Error al capturar el pago en PayPal',
                );
            }

            $data = $response->json();
            $status = $data['status'] ?? '';
            $captureId = null;

            if ($status === 'COMPLETED') {
                $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
                $captureId = $capture['id'] ?? null;
            }

            return new ResultadoProcesamiento(
                exitoso: $status === 'COMPLETED',
                transaccionId: $captureId ?? $transaccionId,
                error: $status !== 'COMPLETED' ? "Estado PayPal: {$status}" : null,
            );
        } catch (\Throwable $e) {
            Log::error('PayPal capturar exception', [
                'transaccion_id' => $transaccionId,
                'error' => $e->getMessage(),
            ]);
            return new ResultadoProcesamiento(
                exitoso: false,
                error: 'Error al capturar con PayPal: ' . $e->getMessage(),
            );
        }
    }

    public function verificar(string $transaccionId): EstadoTransaccion
    {
        try {
            $token = $this->obtenerToken();
            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$transaccionId}");

            if (!$response->successful()) {
                return new EstadoTransaccion(
                    estado: 'desconocido',
                    error: $response->json('message') ?? 'Error al verificar',
                );
            }

            $data = $response->json();
            $status = $data['status'] ?? '';

            $estadoMap = [
                'COMPLETED' => 'completado',
                'APPROVED' => 'pendiente',
                'CREATED' => 'pendiente',
                'VOIDED' => 'fallido',
                'PAYER_ACTION_REQUIRED' => 'pendiente',
            ];

            return new EstadoTransaccion(
                estado: $estadoMap[$status] ?? 'desconocido',
                datos: $data,
            );
        } catch (\Throwable $e) {
            return new EstadoTransaccion(
                estado: 'error',
                error: $e->getMessage(),
            );
        }
    }

    private function obtenerToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Error al obtener token de PayPal: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    private function config(string $clave, mixed $default = null): mixed
    {
        return $this->proveedor->config($clave, $default);
    }
}
