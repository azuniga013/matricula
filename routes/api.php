<?php

use App\Http\Controllers\Api\V1\Academico\AsistenciaController;
use App\Http\Controllers\Api\V1\Academico\AulaController;
use App\Http\Controllers\Api\V1\Academico\CalificacionController;
use App\Http\Controllers\Api\V1\Academico\DepartamentoAcademicoController;
use App\Http\Controllers\Api\V1\Academico\DocenteMovilController;
use App\Http\Controllers\Api\V1\Academico\DocenteController;
use App\Http\Controllers\Api\V1\Academico\GrupoWhatsappController;
use App\Http\Controllers\Api\V1\Academico\HistorialAcademicoController;
use App\Http\Controllers\Api\V1\Academico\HorarioController;
use App\Http\Controllers\Api\V1\Academico\ModalidadController;
use App\Http\Controllers\Api\V1\Academico\MonitorCuposController;
use App\Http\Controllers\Api\V1\Academico\NivelAcademicoController;
use App\Http\Controllers\Api\V1\Academico\OfertaAcademicaController;
use App\Http\Controllers\Api\V1\Academico\PeriodoAcademicoController;
use App\Http\Controllers\Api\V1\Academico\PlanCobroController;
use App\Http\Controllers\Api\V1\Academico\PlanEstudioController;
use App\Http\Controllers\Api\V1\Academico\VersionPlanEstudioController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Caja\CierreCajaController;
use App\Http\Controllers\Api\V1\Caja\SesionCajaController;
use App\Http\Controllers\Api\V1\Catalogos\ConceptoPagoController;
use App\Http\Controllers\Api\V1\Catalogos\MetodoPagoController;
use App\Http\Controllers\Api\V1\Catalogos\SucursalController;
use App\Http\Controllers\Api\V1\Estudiantes\CertificadoElectronicoController;
use App\Http\Controllers\Api\V1\Estudiantes\EstudianteAuthController;
use App\Http\Controllers\Api\V1\Estudiantes\ContactoResponsableEstudianteController;
use App\Http\Controllers\Api\V1\Estudiantes\EstudianteController;
use App\Http\Controllers\Api\V1\Estudiantes\PagoTarjetaController;
use App\Http\Controllers\Api\V1\Estudiantes\PortalEstudianteController;
use App\Http\Controllers\Api\V1\Inventario\InventarioLibroController;
use App\Http\Controllers\Api\V1\Inventario\LibroController;
use App\Http\Controllers\Api\V1\Matriculas\GestionMatriculaController;
use App\Http\Controllers\Api\V1\Matriculas\MatriculaController;
use App\Http\Controllers\Api\V1\Pagos\EnlacePagoController;
use App\Http\Controllers\Api\V1\Pagos\PagoController;
use App\Http\Controllers\Api\V1\Pagos\ProveedorPagoController;
use App\Http\Controllers\Api\V1\Pagos\ReciboCajaController;
use App\Http\Controllers\Api\V1\ReporteController;
use App\Http\Controllers\Api\V1\Seguridad\AuditoriaController;
use App\Http\Controllers\Api\V1\Seguridad\ConfiguracionFlujoMatriculaController;
use App\Http\Controllers\Api\V1\Seguridad\ModuloController;
use App\Http\Controllers\Api\V1\Seguridad\OpcionModuloController;
use App\Http\Controllers\Api\V1\Seguridad\ParametroGlobalController;
use App\Http\Controllers\Api\V1\Seguridad\PermisoController;
use App\Http\Controllers\Api\V1\Seguridad\PublicacionApkDocenteController;
use App\Http\Controllers\Api\V1\Seguridad\RolController;
use App\Http\Controllers\Api\V1\Seguridad\SesionController;
use App\Http\Controllers\Api\V1\Seguridad\UsuarioController;
use App\Models\CuentaBancaria;
use App\Models\MetodoPago;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Route;

Route::post('/v1/login', [AuthController::class, 'login'])
    ->middleware(['log.peticion', 'throttle:10,1']);

Route::get('/v1/up', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]));

Route::post('/v1/pagos/webhook/paypal', [PagoTarjetaController::class, 'webhook']);

Route::prefix('v1/estudiantes')->group(function () {
    Route::post('/registro', [EstudianteAuthController::class, 'registro']);
    Route::post('/activar', [EstudianteAuthController::class, 'activar']);
    Route::post('/iniciar-sesion', [EstudianteAuthController::class, 'login']);
    Route::post('/reenviar-credenciales', [EstudianteAuthController::class, 'reenviarCredenciales']);

    // Catálogo público mínimo para el formulario de registro del portal.
    Route::get('/sucursales', fn () => response()->json([
        'resultado' => 'A',
        'codigo' => 0,
        'mensaje' => 'OK',
        'data' => Sucursal::activos()->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
    ]));

    Route::get('/metodos-pago', fn () => response()->json([
        'resultado' => 'A',
        'codigo' => 0,
        'mensaje' => 'OK',
        'data' => MetodoPago::disponiblesPortal()
            ->with('proveedorPago:id,codigo,nombre')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'proveedor_pago_id']),
    ]));

    // Validación pública de certificados electrónicos (sin autenticación).
    Route::get('/certificados/{token}', [CertificadoElectronicoController::class, 'validar'])
        ->middleware('throttle:30,1');
});

Route::middleware('auth.estudiante')->prefix('v1/estudiantes')->group(function () {
    Route::post('/cerrar-sesion', [EstudianteAuthController::class, 'logout']);
    Route::post('/portal', [EstudianteAuthController::class, 'portal']);

    Route::get('/mis-ofertas', [PortalEstudianteController::class, 'misOfertas']);
    Route::post('/reservar-matricula', [PortalEstudianteController::class, 'reservarMatricula']);
    Route::get('/mis-matriculas', [PortalEstudianteController::class, 'misMatriculas']);
    Route::post('/registrar-pago', [PortalEstudianteController::class, 'registrarPago']);
    Route::get('/cuentas-bancarias', fn () => response()->json([
        'resultado' => 'A',
        'codigo' => 0,
        'mensaje' => 'OK',
        'data' => CuentaBancaria::activas()->orderBy('banco')->get(['id', 'codigo', 'nombre', 'banco', 'numero_cuenta', 'tipo_cuenta']),
    ]));
    Route::post('/subir-comprobante', [PortalEstudianteController::class, 'subirComprobante']);
    Route::post('/confirmar-link-pago', [PortalEstudianteController::class, 'confirmarLinkPago']);
    Route::post('/reenganchar-flujo-pago', [PortalEstudianteController::class, 'reengancharFlujoPago']);
    Route::match(['DELETE', 'POST'], '/mis-pagos/{pago}', [PortalEstudianteController::class, 'eliminarPago']);
    Route::get('/mis-pagos', [PortalEstudianteController::class, 'misPagos']);
    Route::get('/mis-recibos', [PortalEstudianteController::class, 'misRecibos']);
    Route::get('/mi-nivel', [PortalEstudianteController::class, 'miNivel']);
    Route::get('/mis-calificaciones', [EstudianteAuthController::class, 'misCalificaciones']);
    Route::get('/mis-certificados', [PortalEstudianteController::class, 'misCertificados']);
    Route::post('/certificados/electronicos', [CertificadoElectronicoController::class, 'emitir']);
    Route::get('/whatsapp', [PortalEstudianteController::class, 'whatsapp']);
    Route::get('/enlaces-pago', [PortalEstudianteController::class, 'enlacesPago']);

    Route::post('/pago-tarjeta/iniciar', [PagoTarjetaController::class, 'iniciarPago']);
    Route::post('/pago-tarjeta/retorno', [PagoTarjetaController::class, 'retorno']);
    Route::post('/pago-tarjeta/cancelado', [PagoTarjetaController::class, 'cancelado']);
});

Route::middleware(['admin.session', 'auth:sanctum', 'log.peticion'])->prefix('v1')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('seguridad')->group(function () {

        Route::apiResourceProtegido('modulos', ModuloController::class, 'seguridad.modulos', [
            'only' => ['index', 'store', 'show', 'update', 'destroy'],
        ]);

        Route::apiResourceProtegido('opciones', OpcionModuloController::class, 'seguridad.modulos', [
            'only' => ['index', 'store', 'show', 'update', 'destroy'],
            'parameters' => ['opciones' => 'opcionModulo'],
        ]);

        Route::apiResourceProtegido('permisos', PermisoController::class, 'seguridad.permisos', [
            'only' => ['index', 'store', 'show', 'update', 'destroy'],
        ]);

        Route::apiResourceProtegido('roles', RolController::class, 'seguridad.roles', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['roles' => 'rol'],
        ]);

        Route::get('/configuraciones-flujo-matricula', [ConfiguracionFlujoMatriculaController::class, 'index'])
            ->middleware('permission:seguridad.flujos-matricula.consultar');

        Route::post('/configuraciones-flujo-matricula', [ConfiguracionFlujoMatriculaController::class, 'store'])
            ->middleware('permission:seguridad.flujos-matricula.crear');

        Route::match(['PUT', 'PATCH', 'POST'], '/configuraciones-flujo-matricula/{configuracionFlujoMatricula}', [ConfiguracionFlujoMatriculaController::class, 'update'])
            ->middleware('permission:seguridad.flujos-matricula.modificar');

        Route::match(['DELETE', 'POST'], '/configuraciones-flujo-matricula/{configuracionFlujoMatricula}', [ConfiguracionFlujoMatriculaController::class, 'destroy'])
            ->middleware('permission:seguridad.flujos-matricula.eliminar');

        Route::match(['DELETE', 'POST'], '/configuraciones-flujo-matricula/{configuracionFlujoMatricula}/forzar', [ConfiguracionFlujoMatriculaController::class, 'forceDestroy'])
            ->middleware('permission:seguridad.flujos-matricula.eliminar');

        Route::get('/roles/{rol}/permisos', [RolController::class, 'permisos'])
            ->middleware('permission:seguridad.roles.consultar');

        Route::post('/roles/{rol}/permisos', [RolController::class, 'asignarPermisos'])
            ->middleware('permission:seguridad.roles.configurar');

        Route::post('/roles/{rolOrigen}/copiar-permisos', [RolController::class, 'copiarPermisos'])
            ->middleware('permission:seguridad.roles.configurar');

        Route::apiResourceProtegido('usuarios', UsuarioController::class, 'seguridad.usuarios', [
            'only' => ['index', 'store', 'show', 'update'],
        ]);

        Route::post('/usuarios/{usuario}/roles', [UsuarioController::class, 'asignarRoles'])
            ->middleware('permission:seguridad.usuarios.configurar');

        Route::post('/usuarios/{usuario}/sucursales', [UsuarioController::class, 'asignarSucursales'])
            ->middleware('permission:seguridad.usuarios.configurar');

        Route::post('/usuarios/{usuario}/restablecer-contrasena', [UsuarioController::class, 'restablecerContrasena'])
            ->middleware('permission:seguridad.usuarios.configurar');

        Route::get('/sesiones', [SesionController::class, 'index'])
            ->middleware('permission:seguridad.sesiones.consultar');

        Route::match(['DELETE', 'POST'], '/sesiones/{sesionId}', [SesionController::class, 'revocar'])
            ->middleware('permission:seguridad.sesiones.configurar');

        Route::get('/auditoria/peticiones', [AuditoriaController::class, 'peticiones'])
            ->middleware('permission:seguridad.auditoria.consultar');

        Route::get('/auditoria/seguridad', [AuditoriaController::class, 'seguridad'])
            ->middleware('permission:seguridad.auditoria.consultar');

        Route::get('/auditoria/correos', [AuditoriaController::class, 'correos'])
            ->middleware('permission:seguridad.auditoria.consultar');

        Route::apiResourceProtegido('parametros-globales', ParametroGlobalController::class, 'seguridad.parametros', [
            'only' => ['index', 'store', 'update', 'destroy'],
            'parameters' => ['parametros-globales' => 'parametroGlobal'],
        ]);
        Route::get('/parametros-globales/grupos', [ParametroGlobalController::class, 'grupos'])
            ->middleware('permission:seguridad.parametros.consultar');
    });

    Route::prefix('catalogos-academicos')->group(function () {

        Route::apiResourceProtegido('departamentos-academicos', DepartamentoAcademicoController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['departamentos-academicos' => 'departamentoAcademico'],
        ]);

        Route::apiResourceProtegido('planes-estudio', PlanEstudioController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['planes-estudio' => 'planEstudio'],
        ]);

        Route::apiResourceProtegido('versiones-plan-estudio', VersionPlanEstudioController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['versiones-plan-estudio' => 'versionPlanEstudio'],
        ]);

        Route::apiResourceProtegido('modalidades', ModalidadController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
        ]);

        Route::apiResourceProtegido('niveles-academicos', NivelAcademicoController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['niveles-academicos' => 'nivelAcademico'],
        ]);

        Route::apiResourceProtegido('horarios', HorarioController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
        ]);

        Route::apiResourceProtegido('docentes', DocenteController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
        ]);

        Route::apiResourceProtegido('aulas', AulaController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
        ]);

        Route::apiResourceProtegido('periodos-academicos', PeriodoAcademicoController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['periodos-academicos' => 'periodoAcademico'],
        ]);

        Route::apiResourceProtegido('sucursales', SucursalController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['sucursales' => 'sucursal'],
        ]);

        Route::apiResourceProtegido('conceptos-pago', ConceptoPagoController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['conceptos-pago' => 'conceptoPago'],
        ]);

        Route::apiResourceProtegido('metodos-pago', MetodoPagoController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['metodos-pago' => 'metodoPago'],
        ]);

        Route::apiResourceProtegido('planes-cobro', PlanCobroController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['planes-cobro' => 'planCobro'],
        ]);

        Route::apiResourceProtegido('grupos-whatsapp', GrupoWhatsappController::class, 'catalogos_academicos', [
            'only' => ['index', 'store', 'show', 'update', 'destroy'],
            'parameters' => ['grupos-whatsapp' => 'grupoWhatsapp'],
        ]);
    });

    Route::prefix('ofertas')->group(function () {

        Route::apiResourceProtegido('academicas', OfertaAcademicaController::class, 'ofertas', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['academicas' => 'ofertaAcademica'],
        ]);

        Route::post('/academicas/{ofertaAcademica}/whatsapp-periodo', [OfertaAcademicaController::class, 'actualizarWhatsappPeriodo']);

        Route::get('/monitor', [MonitorCuposController::class, 'index'])
            ->middleware('permission:ofertas.consultar')
            ->name('ofertas.monitor');
    });

    Route::prefix('docente-movil')->group(function () {
        Route::get('/sincronizar', [DocenteMovilController::class, 'sincronizar'])
            ->middleware('permission:asistencias.consultar');
        Route::post('/sincronizar', [DocenteMovilController::class, 'aplicarCola'])
            ->middleware('permission:asistencias.crear');
        Route::get('/ofertas/{id}', [DocenteMovilController::class, 'oferta'])
            ->middleware('permission:asistencias.consultar');
    });

    Route::prefix('estudiantes')->group(function () {

        Route::get('/buscar-identidad', [EstudianteController::class, 'buscarPorIdentidad'])
            ->middleware('permission:estudiantes.consultar');

        Route::get('/{id}/plan-activo', [EstudianteController::class, 'planActivo'])
            ->middleware('permission:estudiantes.consultar');

        Route::get('/{id}/contactos-responsable', [ContactoResponsableEstudianteController::class, 'index'])
            ->middleware('permission:estudiantes.consultar');

        Route::post('/{id}/contactos-responsable', [ContactoResponsableEstudianteController::class, 'store'])
            ->middleware('permission:estudiantes.modificar');

        Route::match(['PUT', 'PATCH', 'POST'], '/{id}/contactos-responsable/{contactoId}', [ContactoResponsableEstudianteController::class, 'update'])
            ->middleware('permission:estudiantes.modificar');

        Route::match(['DELETE', 'POST'], '/{id}/contactos-responsable/{contactoId}/desactivar', [ContactoResponsableEstudianteController::class, 'destroy'])
            ->middleware('permission:estudiantes.modificar');

        Route::apiResourceProtegido('', EstudianteController::class, 'estudiantes', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['' => 'estudiante'],
        ]);
    });

    Route::prefix('matriculas')->group(function () {

        Route::get('/', [MatriculaController::class, 'index'])
            ->middleware('permission:matriculas.consultar');

        Route::post('/reservar', [MatriculaController::class, 'reservar'])
            ->middleware('permission:matriculas.crear');

        Route::post('/{id}/confirmar', [MatriculaController::class, 'confirmar'])
            ->middleware('permission:matriculas.modificar');

        Route::post('/{id}/cancelar', [MatriculaController::class, 'cancelar'])
            ->middleware('permission:matriculas.modificar');

        Route::get('/{id}', [MatriculaController::class, 'show'])
            ->middleware('permission:matriculas.consultar');
    });

    Route::prefix('gestiones-matricula')->group(function () {

        Route::get('/', [GestionMatriculaController::class, 'index'])
            ->middleware('permission:matriculas.consultar');

        Route::get('/tipos', [GestionMatriculaController::class, 'tipos'])
            ->middleware('permission:matriculas.consultar');

        Route::post('/solicitar', [GestionMatriculaController::class, 'solicitar'])
            ->middleware('permission:matriculas.crear');

        Route::post('/{id}/aprobar', [GestionMatriculaController::class, 'aprobar'])
            ->middleware('permission:matriculas.modificar');

        Route::post('/{id}/rechazar', [GestionMatriculaController::class, 'rechazar'])
            ->middleware('permission:matriculas.modificar');

        Route::get('/{id}', [GestionMatriculaController::class, 'show'])
            ->middleware('permission:matriculas.consultar');
    });

    Route::prefix('pagos')->group(function () {

        Route::get('/', [PagoController::class, 'index'])
            ->middleware('permission:pagos.consultar');

        Route::get('/siguiente-recibo', [PagoController::class, 'siguienteRecibo'])
            ->middleware('permission:pagos.crear');

        Route::get('/obligaciones-estudiante', [PagoController::class, 'obligacionesEstudiante'])
            ->middleware('permission:pagos.crear');

        Route::post('/registrar', [PagoController::class, 'registrar'])
            ->middleware('permission:pagos.crear');

        Route::post('/{id}/comprobante', [PagoController::class, 'subirComprobante'])
            ->middleware('permission:pagos.crear');

        Route::post('/{id}/aprobar', [PagoController::class, 'aprobar'])
            ->middleware('permission:pagos.aprobar');

        Route::post('/{id}/rechazar', [PagoController::class, 'rechazar'])
            ->middleware('permission:pagos.aprobar');

        Route::post('/{id}/link-pago', [PagoController::class, 'actualizarLink'])
            ->middleware('permission:pagos.modificar');

        Route::match(['DELETE', 'POST'], '/{id}/eliminar-total', [PagoController::class, 'eliminarTotal'])
            ->middleware('permission:pagos.eliminar');

        Route::get('/{id}', [PagoController::class, 'show'])
            ->middleware('permission:pagos.consultar');
    });

    Route::get('/cuentas-bancarias', fn () => response()->json([
        'resultado' => 'A',
        'codigo' => 0,
        'mensaje' => 'OK',
        'data' => CuentaBancaria::where('estado', 'activo')->orderBy('banco')->get(),
    ]))->middleware('permission:pagos.consultar');

    Route::prefix('enlaces-pago')->group(function () {

        Route::get('/', [EnlacePagoController::class, 'index'])
            ->middleware('permission:pagos.consultar');

        Route::get('/disponibles', [EnlacePagoController::class, 'disponibles'])
            ->middleware('permission:pagos.consultar');

        Route::post('/', [EnlacePagoController::class, 'store'])
            ->middleware('permission:pagos.crear');

        Route::get('/{enlacePago}', [EnlacePagoController::class, 'show'])
            ->middleware('permission:pagos.consultar');

        Route::match(['PUT', 'POST'], '/{enlacePago}', [EnlacePagoController::class, 'update'])
            ->middleware('permission:pagos.modificar');

        Route::match(['DELETE', 'POST'], '/{enlacePago}', [EnlacePagoController::class, 'destroy'])
            ->middleware('permission:pagos.eliminar');

        Route::post('/{enlacePago}/usar', [EnlacePagoController::class, 'usar'])
            ->middleware('permission:pagos.crear');
    });

    Route::prefix('recibos-caja')->group(function () {

        Route::get('/', [ReciboCajaController::class, 'index'])
            ->middleware('permission:pagos.consultar');

        Route::get('/{id}', [ReciboCajaController::class, 'show'])
            ->middleware('permission:pagos.consultar');

        Route::post('/{id}/reimprimir', [ReciboCajaController::class, 'reimprimir'])
            ->middleware('permission:pagos.modificar');

        Route::post('/{id}/anular', [ReciboCajaController::class, 'anular'])
            ->middleware('permission:pagos.aprobar');
    });

    Route::prefix('proveedores-pago')->group(function () {

        Route::get('/', [ProveedorPagoController::class, 'index'])
            ->middleware('permission:configuracion.pagos.consultar');

        Route::post('/', [ProveedorPagoController::class, 'store'])
            ->middleware('permission:configuracion.pagos.modificar');

        Route::get('/{id}', [ProveedorPagoController::class, 'show'])
            ->middleware('permission:configuracion.pagos.consultar');

        Route::match(['PUT', 'POST'], '/{id}', [ProveedorPagoController::class, 'update'])
            ->middleware('permission:configuracion.pagos.modificar');

        Route::post('/{id}/configuracion', [ProveedorPagoController::class, 'guardarConfiguracion'])
            ->middleware('permission:configuracion.pagos.modificar');
    });

    Route::prefix('distribucion-apk/docentes')->group(function () {
        Route::get('/', [PublicacionApkDocenteController::class, 'index'])->middleware('permission:distribucion_apk.consultar');
        Route::post('/', [PublicacionApkDocenteController::class, 'store'])->middleware('permission:distribucion_apk.crear');
        Route::post('/{publicacionApkDocente}/publicar', [PublicacionApkDocenteController::class, 'publicar'])->middleware('permission:distribucion_apk.modificar');
    });

    Route::prefix('caja')->group(function () {

        Route::get('/sesiones', [SesionCajaController::class, 'index'])
            ->middleware('permission:caja.consultar');

        Route::post('/abrir', [SesionCajaController::class, 'abrir'])
            ->middleware('permission:caja.crear');

        Route::post('/{id}/cerrar', [SesionCajaController::class, 'cerrar'])
            ->middleware('permission:caja.modificar');

        Route::get('/{id}', [SesionCajaController::class, 'show'])
            ->middleware('permission:caja.consultar');
    });

    Route::prefix('cierre-caja')->group(function () {

        Route::get('/', [CierreCajaController::class, 'index'])
            ->middleware('permission:caja.consultar');

        Route::get('/resumen', [CierreCajaController::class, 'resumen'])
            ->middleware('permission:caja.consultar');
    });

    Route::prefix('calificaciones')->group(function () {

        Route::get('/', [CalificacionController::class, 'index'])
            ->middleware('permission:calificaciones.consultar');

        Route::post('/registrar', [CalificacionController::class, 'registrar'])
            ->middleware('permission:calificaciones.crear');

        Route::get('/{id}', [CalificacionController::class, 'show'])
            ->middleware('permission:calificaciones.consultar');

        Route::match(['PUT', 'POST'], '/{id}', [CalificacionController::class, 'actualizar'])
            ->middleware('permission:calificaciones.modificar');
    });

    Route::prefix('historial-academico')->group(function () {

        Route::get('/', [HistorialAcademicoController::class, 'index'])
            ->middleware('permission:calificaciones.consultar');

        Route::get('/nivel-actual/{estudianteId}', [HistorialAcademicoController::class, 'nivelActual'])
            ->middleware('permission:calificaciones.consultar');

        Route::get('/calificaciones/{estudianteId}', [HistorialAcademicoController::class, 'calificacionesEstudiante'])
            ->middleware('permission:calificaciones.consultar');
    });

    Route::prefix('estudiantes/certificados')->group(function () {
        Route::post('/electronicos/admin', [CertificadoElectronicoController::class, 'emitirAdmin'])
            ->middleware(['auth:sanctum', 'permission:calificaciones.modificar']);
        Route::get('/estudiante/{estudianteId}', [CertificadoElectronicoController::class, 'listarPorEstudiante'])
            ->middleware(['auth:sanctum', 'permission:estudiantes.consultar']);
    });

    Route::prefix('asistencias')->group(function () {

        Route::get('/ofertas-disponibles', [AsistenciaController::class, 'ofertasDisponibles'])
            ->middleware('permission:asistencias.consultar');

        Route::get('/estudiantes-por-oferta', [AsistenciaController::class, 'estudiantesPorOferta'])
            ->middleware('permission:asistencias.consultar');

        Route::post('/registrar', [AsistenciaController::class, 'registrar'])
            ->middleware('permission:asistencias.crear');

        Route::get('/por-oferta', [AsistenciaController::class, 'porOferta'])
            ->middleware('permission:asistencias.consultar');

        Route::get('/faltas-por-oferta', [AsistenciaController::class, 'faltasPorOferta'])
            ->middleware('permission:asistencias.consultar');

        Route::get('/resumen-faltas', [AsistenciaController::class, 'resumenFaltas'])
            ->middleware('permission:asistencias.consultar');
    });

    Route::prefix('inventario')->group(function () {

        Route::apiResourceProtegido('libros', LibroController::class, 'inventario', [
            'only' => ['index', 'store', 'show', 'update'],
            'parameters' => ['libros' => 'libro'],
        ]);

        Route::get('/stock', [InventarioLibroController::class, 'index'])
            ->middleware('permission:inventario.consultar');

        Route::post('/stock', [InventarioLibroController::class, 'store'])
            ->middleware('permission:inventario.crear');

        Route::get('/stock/{inventarioLibro}', [InventarioLibroController::class, 'show'])
            ->middleware('permission:inventario.consultar');

        Route::post('/stock/{inventarioLibro}/ajustar', [InventarioLibroController::class, 'ajustar'])
            ->middleware('permission:inventario.modificar');

        Route::post('/stock/{inventarioLibro}/vender', [InventarioLibroController::class, 'vender'])
            ->middleware('permission:inventario.modificar');

        Route::get('/kardex', [InventarioLibroController::class, 'kardex'])
            ->middleware('permission:inventario.consultar');
    });

    Route::prefix('reportes')->group(function () {

        Route::get('/exportar', [ReporteController::class, 'exportar'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/por-periodo', [ReporteController::class, 'academicosMatriculadosPorPeriodo'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/por-sucursal', [ReporteController::class, 'academicosMatriculadosPorSucursal'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/por-nivel', [ReporteController::class, 'academicosMatriculadosPorNivel'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/por-docente', [ReporteController::class, 'academicosMatriculadosPorDocente'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/grupo', [ReporteController::class, 'academicosGrupo'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/calificaciones-por-grupo', [ReporteController::class, 'academicosCalificacionesPorGrupo'])
            ->middleware('permission:reportes.consultar');

        Route::get('/academicos/nivel-actual', [ReporteController::class, 'academicosNivelActual'])
            ->middleware('permission:reportes.consultar');

        Route::get('/financieros/por-concepto', [ReporteController::class, 'financierosIngresosPorConcepto'])
            ->middleware('permission:reportes.consultar');

        Route::get('/financieros/por-metodo', [ReporteController::class, 'financierosIngresosPorMetodo'])
            ->middleware('permission:reportes.consultar');

        Route::get('/financieros/por-sucursal', [ReporteController::class, 'financierosIngresosPorSucursal'])
            ->middleware('permission:reportes.consultar');

        Route::get('/financieros/pagos-pendientes', [ReporteController::class, 'financierosPagosPendientes'])
            ->middleware('permission:reportes.consultar');

        Route::get('/financieros/pagos-rechazados', [ReporteController::class, 'financierosPagosRechazados'])
            ->middleware('permission:reportes.consultar');

        Route::get('/recibos/por-orden', [ReporteController::class, 'recibosPorOrden'])
            ->middleware('permission:reportes.consultar');

        Route::get('/recibos/por-metodo', [ReporteController::class, 'recibosPorMetodo'])
            ->middleware('permission:reportes.consultar');

        Route::get('/recibos/por-concepto', [ReporteController::class, 'recibosPorConcepto'])
            ->middleware('permission:reportes.consultar');

        Route::get('/recibos/por-concepto-detalle', [ReporteController::class, 'recibosPorConceptoDetalle'])
            ->middleware('permission:reportes.consultar');

        Route::get('/recibos/anulados', [ReporteController::class, 'recibosAnulados'])
            ->middleware('permission:reportes.consultar');

        Route::get('/caja/por-cajero', [ReporteController::class, 'cajaPorCajero'])
            ->middleware('permission:reportes.consultar');

        Route::get('/caja/resumen-diario', [ReporteController::class, 'cajaResumenDiario'])
            ->middleware('permission:reportes.consultar');
    });
});
