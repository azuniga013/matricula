<?php

namespace Tests\Feature;

use App\Models\ConceptoPago;
use App\Models\Estudiante;
use App\Models\MetodoPago;
use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Pago;
use App\Models\Permiso;
use App\Models\ReciboCaja;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoFechasHistoricasTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Sucursal $sucursal;

    private Estudiante $estudiante;

    private int $conceptoMatId;

    private int $metodoEfeId;

    protected function setUp(): void
    {
        parent::setUp();

        $modulo = Modulo::create(['codigo' => 'pagos', 'nombre' => 'Pagos', 'estado' => 'activo', 'orden' => 7]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'pagos.general', 'nombre' => 'General', 'estado' => 'activo']);
        foreach (['consultar', 'crear', 'modificar', 'aprobar'] as $accion) {
            Permiso::create([
                'opcion_modulo_id' => $opcion->id,
                'codigo' => 'pagos.'.$accion,
                'nombre' => ucfirst($accion),
                'accion' => $accion,
                'estado' => 'activo',
            ]);
        }

        $rol = Rol::create(['codigo' => 'TEST_PAGOS_FECHA', 'nombre' => 'Test Pagos Fecha', 'estado' => 'activo']);
        $rol->permisos()->attach(Permiso::pluck('id')->all(), ['estado' => 'activo']);

        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'estado' => 'activo',
        ]);
        $user->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $user->createToken('test')->plainTextToken;
        DB::table('alcances_usuario')->insert([
            'usuario_id' => $user->id,
            'tipo' => 'global',
            'estado' => 'activo',
        ]);

        $this->sucursal = Sucursal::factory()->create(['codigo' => 'SPS']);
        $this->estudiante = Estudiante::factory()->create(['sucursal_id' => $this->sucursal->id]);
        $this->conceptoMatId = ConceptoPago::create([
            'codigo' => 'MAT',
            'nombre' => 'Matricula',
            'tipo_monto' => 'por_oferta',
            'requiere_autorizacion_monto' => false,
            'estado' => 'activo',
        ])->id;
        $this->metodoEfeId = MetodoPago::create([
            'codigo' => 'EFE',
            'nombre' => 'Efectivo',
            'estado' => 'activo',
        ])->id;
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_recibos_filtran_por_fecha_recibo_y_no_por_creado_en(): void
    {
        $pago = Pago::create([
            'codigo' => 'PAG-FECHA-001',
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 1200,
            'estado' => 'aprobado',
            'fecha_proceso' => '2026-08-01 10:00:00',
            'fecha_aprobacion' => '2026-08-01 10:00:00',
            'creado_en' => '2026-08-03 09:00:00',
        ]);

        ReciboCaja::create([
            'codigo' => 'REC-FECHA-001',
            'numero_recibo' => 1,
            'pago_id' => $pago->id,
            'estudiante_id' => $this->estudiante->id,
            'sucursal_id' => $this->sucursal->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto_total' => 1200,
            'estado' => 'emitido',
            'anio' => '2026',
            'fecha_proceso' => '2026-08-01 10:00:00',
            'fecha_recibo' => '2026-08-01 10:05:00',
            'creado_por' => 1,
            'creado_en' => '2026-08-03 09:00:00',
        ]);

        $this->getJson('/api/v1/recibos-caja?fecha_desde=2026-08-01&fecha_hasta=2026-08-01', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.codigo', 'REC-FECHA-001');

        $this->getJson('/api/v1/recibos-caja?fecha_desde=2026-08-03&fecha_hasta=2026-08-03', $this->headers())
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    public function test_show_recibo_y_pago_exponen_la_misma_fecha_funcional(): void
    {
        $pago = Pago::create([
            'codigo' => 'PAG-FECHA-002',
            'estudiante_id' => $this->estudiante->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'sucursal_id' => $this->sucursal->id,
            'monto' => 900,
            'estado' => 'aprobado',
            'fecha_proceso' => null,
            'fecha_aprobacion' => null,
            'creado_en' => '2026-08-04 14:30:00',
        ]);

        $recibo = ReciboCaja::create([
            'codigo' => 'REC-FECHA-002',
            'numero_recibo' => 2,
            'pago_id' => $pago->id,
            'estudiante_id' => $this->estudiante->id,
            'sucursal_id' => $this->sucursal->id,
            'concepto_pago_id' => $this->conceptoMatId,
            'metodo_pago_id' => $this->metodoEfeId,
            'monto_total' => 900,
            'estado' => 'emitido',
            'anio' => '2026',
            'fecha_proceso' => null,
            'fecha_recibo' => null,
            'creado_por' => 1,
            'creado_en' => '2026-08-05 08:00:00',
        ]);

        $recibo = $recibo->fresh('pago');

        $this->assertSame('2026-08-04 14:30:00', $pago->fecha_proceso?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-04 14:30:00', $recibo->fecha_proceso?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-04 14:30:00', $recibo->fecha_recibo?->format('Y-m-d H:i:s'));

        $this->getJson('/api/v1/recibos-caja/'.$recibo->id, $this->headers())
            ->assertOk()
            ->assertJsonPath('data.fecha_recibo', $recibo->fecha_recibo?->toJSON());
    }
}
