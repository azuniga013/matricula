<?php

namespace Tests\Feature;

use App\Models\ConceptoPago;
use App\Models\PlanCobro;
use App\Models\DetallePlanCobro;
use App\Models\User;
use App\Models\Rol;
use App\Models\Permiso;
use Database\Seeders\SeguridadRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCobroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ConceptoPago $conceptoMat;
    private ConceptoPago $conceptoCuo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadRbacSeeder::class);
        $this->seed(\Database\Seeders\ConceptoPagoSeeder::class);

        $rol = Rol::create(['codigo' => 'TEST_ADMIN', 'nombre' => 'Test Admin', 'estado' => 'activo']);
        $permisos = Permiso::where('codigo', 'like', 'catalogos_academicos.%')->get();
        $rol->permisos()->attach($permisos->pluck('id')->toArray(), ['estado' => 'activo']);

        $this->admin = User::factory()->create(['estado' => 'activo']);
        $this->admin->roles()->attach($rol->id, ['estado' => 'activo']);

        $this->conceptoMat = ConceptoPago::where('codigo', 'MAT')->first();
        $this->conceptoCuo = ConceptoPago::where('codigo', 'CUO')->first();
        if (!$this->conceptoCuo) {
            $this->conceptoCuo = ConceptoPago::create([
                'codigo' => 'CUO', 'nombre' => 'Cuota', 'tipo_monto' => 'por_oferta', 'estado' => 'activo',
            ]);
        }
    }

    private function authHeaders(): array
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    private function planData(): array
    {
        return [
            'codigo' => 'PLN-TEST-001',
            'nombre' => 'Plan de Prueba',
            'descripcion' => 'Plan para pruebas automatizadas',
            'detalles' => [
                [
                    'concepto_pago_id' => $this->conceptoMat->id,
                    'numero_cuota' => 0,
                    'nombre_cargo' => 'Matrícula',
                    'monto' => 500.00,
                    'dias_vencimiento' => 0,
                ],
                [
                    'concepto_pago_id' => $this->conceptoCuo->id,
                    'numero_cuota' => 1,
                    'nombre_cargo' => 'Cuota 1',
                    'monto' => 800.00,
                    'dias_vencimiento' => 30,
                ],
            ],
        ];
    }

    public function test_crear_plan_cobro(): void
    {
        $response = $this->postJson('/api/v1/catalogos-academicos/planes-cobro', $this->planData(), $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson(['resultado' => 'A']);

        $this->assertDatabaseHas('planes_cobro', ['codigo' => 'PLN-TEST-001']);
        $this->assertDatabaseHas('detalle_plan_cobro', ['nombre_cargo' => 'Matrícula', 'monto' => 500.00]);
        $this->assertDatabaseHas('detalle_plan_cobro', ['nombre_cargo' => 'Cuota 1', 'monto' => 800.00]);
    }

    public function test_listar_planes_cobro(): void
    {
        PlanCobro::factory()->create(['codigo' => 'PLN-LIST-01']);
        PlanCobro::factory()->create(['codigo' => 'PLN-LIST-02']);

        $response = $this->getJson('/api/v1/catalogos-academicos/planes-cobro', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['resultado' => 'A']);
        $responseData = $response->json('data');
        $this->assertCount(2, $responseData['data'] ?? $responseData);
    }

    public function test_mostrar_plan_cobro(): void
    {
        $plan = PlanCobro::factory()->create(['codigo' => 'PLN-SHOW']);
        DetallePlanCobro::create([
            'plan_cobro_id' => $plan->id,
            'concepto_pago_id' => $this->conceptoMat->id,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matrícula',
            'monto' => 500.00,
            'estado' => 'activo',
            'creado_por' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/v1/catalogos-academicos/planes-cobro/{$plan->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['resultado' => 'A']);
        $response->assertJsonPath('data.codigo', 'PLN-SHOW');
    }

    public function test_actualizar_plan_cobro(): void
    {
        $plan = PlanCobro::factory()->create(['codigo' => 'PLN-UPDATE', 'nombre' => 'Original']);
        $detalle = DetallePlanCobro::create([
            'plan_cobro_id' => $plan->id,
            'concepto_pago_id' => $this->conceptoMat->id,
            'numero_cuota' => 0,
            'nombre_cargo' => 'Matrícula',
            'monto' => 500.00,
            'estado' => 'activo',
            'creado_por' => $this->admin->id,
        ]);

        $response = $this->putJson("/api/v1/catalogos-academicos/planes-cobro/{$plan->id}", [
            'nombre' => 'Actualizado',
            'descripcion' => 'Plan actualizado',
            'detalles' => [
                [
                    'id' => $detalle->id,
                    'concepto_pago_id' => $this->conceptoMat->id,
                    'numero_cuota' => 0,
                    'nombre_cargo' => 'Matrícula',
                    'monto' => 600.00,
                    'dias_vencimiento' => 0,
                ],
                [
                    'concepto_pago_id' => $this->conceptoCuo->id,
                    'numero_cuota' => 1,
                    'nombre_cargo' => 'Cuota Nueva',
                    'monto' => 900.00,
                    'dias_vencimiento' => 30,
                ],
            ],
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['resultado' => 'A']);

        $this->assertDatabaseHas('planes_cobro', ['id' => $plan->id, 'nombre' => 'Actualizado']);
        $this->assertDatabaseHas('detalle_plan_cobro', ['plan_cobro_id' => $plan->id, 'monto' => 600.00]);
        $this->assertDatabaseHas('detalle_plan_cobro', ['plan_cobro_id' => $plan->id, 'nombre_cargo' => 'Cuota Nueva', 'monto' => 900.00]);
    }

    public function test_validar_codigo_unico(): void
    {
        PlanCobro::factory()->create(['codigo' => 'PLN-UNICO']);

        $data = $this->planData();
        $data['codigo'] = 'PLN-UNICO';
        $response = $this->postJson('/api/v1/catalogos-academicos/planes-cobro', $data, $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_requiere_permiso_para_crear(): void
    {
        $usuarioSinPermiso = User::factory()->create(['estado' => 'activo']);
        $token = $usuarioSinPermiso->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/catalogos-academicos/planes-cobro', $this->planData(), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
    }

    public function test_filtrar_planes_por_estado(): void
    {
        PlanCobro::factory()->create(['codigo' => 'PLN-ACT', 'estado' => 'activo']);
        PlanCobro::factory()->create(['codigo' => 'PLN-INACT', 'estado' => 'inactivo']);

        $response = $this->getJson('/api/v1/catalogos-academicos/planes-cobro?estado=activo', $this->authHeaders());

        $response->assertStatus(200);
        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertCount(1, $items);
        $this->assertEquals('PLN-ACT', $items[0]['codigo']);
    }

    public function test_buscar_plan_por_codigo(): void
    {
        PlanCobro::factory()->create(['codigo' => 'PLN-BUSCAR', 'nombre' => 'Buscar este']);

        $response = $this->getJson('/api/v1/catalogos-academicos/planes-cobro?buscar=BUSCAR', $this->authHeaders());

        $response->assertStatus(200);
        $data = $response->json('data');
        $items = $data['data'] ?? $data;
        $this->assertGreaterThanOrEqual(1, count($items));
    }
}
