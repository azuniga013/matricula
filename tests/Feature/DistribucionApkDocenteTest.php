<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\OpcionModulo;
use App\Models\Permiso;
use App\Models\PublicacionApkDocente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DistribucionApkDocenteTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $modulo = Modulo::create(['codigo' => 'distribucion_apk', 'nombre' => 'Distribución APK', 'estado' => 'activo', 'orden' => 1]);
        $opcion = OpcionModulo::create(['modulo_id' => $modulo->id, 'codigo' => 'distribucion_apk', 'nombre' => 'General', 'estado' => 'activo']);
        $permisos = collect(['consultar', 'crear', 'modificar'])->map(fn (string $accion) => Permiso::create([
            'opcion_modulo_id' => $opcion->id, 'codigo' => "distribucion_apk.{$accion}",
            'nombre' => ucfirst($accion), 'accion' => $accion, 'estado' => 'activo',
        ]));
        $rol = Rol::create(['codigo' => 'TEST_APK', 'nombre' => 'Distribución APK', 'estado' => 'activo']);
        $rol->permisos()->attach($permisos->pluck('id')->all(), ['estado' => 'activo']);
        $usuario = User::factory()->create(['estado' => 'activo']);
        $usuario->roles()->attach($rol->id, ['estado' => 'activo']);
        $this->token = $usuario->createToken('test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        if (is_dir(storage_path('app/apk-docentes'))) {
            File::deleteDirectory(storage_path('app/apk-docentes'), true);
        }

        parent::tearDown();
    }

    public function test_url_publica_no_muestra_descarga_si_no_hay_apk_publicada(): void
    {
        $this->get('/apk/docentes')->assertOk()->assertSee('Aún no se ha publicado');
        $this->get('/apk/docentes/descargar')->assertNotFound();
    }

    public function test_admin_registra_y_publica_apk_para_url_publica(): void
    {
        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '1.0.0', 'version_code' => 1,
            'archivo' => UploadedFile::fake()->create('docentes.apk', 32, 'application/vnd.android.package-archive'),
            'notas_version' => 'Primera prueba interna', 'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertCreated()->assertJsonPath('data.publicado', true);

        $publicacion = PublicacionApkDocente::firstOrFail();
        Storage::disk('local')->assertExists($publicacion->ruta_archivo);
        $this->get('/apk/docentes')->assertOk()->assertSee('Versión 1.0.0')->assertSee('Descargar APK oficial');

        $descarga = $this->get('/apk/docentes/descargar');
        $descarga->assertOk();
        $descarga->assertHeader('content-type', 'application/vnd.android.package-archive');
        $rutaAbsoluta = Storage::disk('local')->path($publicacion->ruta_archivo);
        $this->assertSame(file_get_contents($rutaAbsoluta), $descarga->streamedContent());
    }

    public function test_usuario_sin_permiso_no_consulta_publicaciones(): void
    {
        $token = User::factory()->create(['estado' => 'activo'])->createToken('sin-permiso')->plainTextToken;
        $this->getJson('/api/v1/distribucion-apk/docentes', ['Authorization' => "Bearer {$token}"])
            ->assertForbidden();
    }

    public function test_index_entrega_ruta_y_diagnostico_del_storage(): void
    {
        Storage::disk('local')->makeDirectory('apk-docentes');
        Storage::disk('local')->put('apk-docentes/mi-prueba.apk', 'x');

        $this->getJson('/api/v1/distribucion-apk/docentes', ['Authorization' => "Bearer {$this->token}"])
            ->assertOk()
            ->assertJsonPath('diagnostico.carpeta_existe', true)
            ->assertJsonPath('diagnostico.archivos.0.ruta', 'apk-docentes/mi-prueba.apk')
            ->assertJsonStructure(['ruta_storage']);
    }

    public function test_descarga_publica_sirve_apk_colocada_en_carpeta_vieja(): void
    {
        if (! is_dir(storage_path('app/apk-docentes'))) {
            mkdir(storage_path('app/apk-docentes'), 0777, true);
        }
        file_put_contents(storage_path('app/apk-docentes/docentes-vieja.apk'), 'apk en carpeta vieja');

        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '0.9.0', 'version_code' => 9, 'desde_servidor' => '1', 'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertCreated()->assertJsonPath('data.nombre_archivo', 'docentes-vieja.apk');

        $descarga = $this->get('/apk/docentes/descargar');
        $descarga->assertOk()->assertHeader('content-type', 'application/vnd.android.package-archive');
        $this->assertSame(file_get_contents(storage_path('app/apk-docentes/docentes-vieja.apk')), $descarga->streamedContent());
    }

    public function test_admin_registra_apk_desde_archivo_colocado_en_servidor(): void
    {
        Storage::disk('local')->put('apk-docentes/docentes-colocado.apk', 'paquete apk de prueba');

        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '0.2.0', 'version_code' => 7, 'desde_servidor' => '1',
            'notas_version' => 'Registrada sin upload HTTP', 'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertCreated()->assertJsonPath('data.publicado', true)->assertJsonPath('data.nombre_archivo', 'docentes-colocado.apk');

        $this->assertDatabaseHas('publicaciones_apk_docentes', ['version_code' => 7, 'nombre_archivo' => 'docentes-colocado.apk', 'sha256' => hash('sha256', 'paquete apk de prueba')]);
        Storage::disk('local')->assertExists('apk-docentes/docentes-colocado.apk');
    }

    public function test_admin_registra_desde_servidor_tomando_el_apk_mas_reciente(): void
    {
        Storage::disk('local')->put('apk-docentes/docentes-antiguo.apk', 'apk antiguo');
        Storage::disk('local')->put('apk-docentes/docentes-reciente.apk', 'apk reciente');

        touch(Storage::disk('local')->path('apk-docentes/docentes-antiguo.apk'), now()->subMinutes(10)->timestamp);
        touch(Storage::disk('local')->path('apk-docentes/docentes-reciente.apk'), now()->timestamp);

        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '0.3.1', 'version_code' => 31, 'desde_servidor' => '1', 'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertCreated()
            ->assertJsonPath('data.nombre_archivo', 'docentes-reciente.apk');

        $this->assertDatabaseHas('publicaciones_apk_docentes', [
            'version_code' => 31,
            'nombre_archivo' => 'docentes-reciente.apk',
            'sha256' => hash('sha256', 'apk reciente'),
        ]);
    }

    public function test_publicar_nueva_version_despublica_la_anterior_y_actualiza_url_publica(): void
    {
        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '0.1.1', 'version_code' => 11,
            'archivo' => UploadedFile::fake()->create('docentes-v011.apk', 32, 'application/vnd.android.package-archive'),
            'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])->assertCreated();

        $this->post('/api/v1/distribucion-apk/docentes', [
            'version' => '0.1.3', 'version_code' => 13,
            'archivo' => UploadedFile::fake()->create('docentes-v013.apk', 32, 'application/vnd.android.package-archive'),
            'publicar' => true,
        ], ['Authorization' => "Bearer {$this->token}"])->assertCreated();

        $this->assertDatabaseHas('publicaciones_apk_docentes', [
            'version_code' => 11,
            'publicado' => false,
        ]);
        $this->assertDatabaseHas('publicaciones_apk_docentes', [
            'version_code' => 13,
            'publicado' => true,
        ]);

        $this->get('/apk/docentes')
            ->assertOk()
            ->assertSee('Versión 0.1.3')
            ->assertDontSee('Versión 0.1.1');
    }

    public function test_no_registra_desde_servidor_si_no_hay_apk_colocado(): void
    {
        Storage::disk('local')->deleteDirectory('apk-docentes');

        $this->postJson('/api/v1/distribucion-apk/docentes', [
            'version' => '0.3.0', 'version_code' => 8, 'desde_servidor' => '1',
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertUnprocessable()->assertJsonPath('codigo_error', '422_VALIDACION');

        $this->assertDatabaseMissing('publicaciones_apk_docentes', ['version_code' => 8]);
    }
}
