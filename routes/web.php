<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'webLogin'])->name('web.login');
Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'webLogout'])->name('web.logout');

Route::get('/admin', fn () => view('admin.dashboard', ['title' => 'Dashboard']))->name('admin.dashboard');
Route::get('/admin/catalogos', fn () => view('admin.catalogos', ['title' => 'Catálogos Académicos']))->name('admin.catalogos');
Route::get('/admin/planes-cobro', fn () => view('admin.planes-cobro', ['title' => 'Planes de Cobro']))->name('admin.planes-cobro');
Route::get('/admin/ofertas', fn () => view('admin.ofertas', ['title' => 'Ofertas y Cupos']))->name('admin.ofertas');
Route::get('/admin/monitor', fn () => view('admin.monitor', ['title' => 'Monitor de Cupos']))->name('admin.monitor');
Route::get('/admin/estudiantes', fn () => view('admin.estudiantes', ['title' => 'Estudiantes']))->name('admin.estudiantes');
Route::get('/admin/matriculas', fn () => view('admin.matriculas', ['title' => 'Matrícula']))->name('admin.matriculas');
Route::get('/admin/calificaciones', fn () => view('admin.calificaciones', ['title' => 'Calificaciones']))->name('admin.calificaciones');
Route::get('/admin/mis-grupos', fn () => view('admin.mis-grupos', ['title' => 'Mis Grupos']))->name('admin.mis-grupos');
Route::get('/admin/asistencias', fn () => view('admin.asistencias', ['title' => 'Asistencias']))->name('admin.asistencias');
Route::get('/admin/inventario', fn () => view('admin.inventario', ['title' => 'Inventario y Libros']))->name('admin.inventario');
Route::get('/admin/pagos', fn () => view('admin.pagos', ['title' => 'Pagos']))->name('admin.pagos');
Route::get('/admin/caja', fn () => view('admin.caja', ['title' => 'Caja']))->name('admin.caja');
Route::get('/admin/reportes', fn () => view('admin.reportes', ['title' => 'Reportes']))->name('admin.reportes');
Route::get('/admin/seguridad', fn () => view('admin.seguridad', ['title' => 'Seguridad']))->name('admin.seguridad');
Route::get('/admin/proveedores-pago', fn () => view('admin.proveedores-pago', ['title' => 'Proveedores de Pago']))->name('admin.proveedores-pago');
Route::get('/admin/apk-docentes', fn () => view('admin.apk-docentes', ['title' => 'APK Docentes']))->name('admin.apk-docentes');
Route::get('/admin/parametros-globales', fn () => view('admin.parametros-globales', ['title' => 'Parámetros Globales']))->name('admin.parametros-globales');

Route::get('/apk/docentes', [App\Http\Controllers\ApkDocentePublicoController::class, 'index'])->name('apk-docentes.publico');
Route::get('/apk/docentes/descargar', [App\Http\Controllers\ApkDocentePublicoController::class, 'descargar'])->name('apk-docentes.descargar');

Route::get('/admin/recibos/{id}/imprimir', [App\Http\Controllers\Api\V1\Pagos\ReciboCajaController::class, 'imprimir']);

Route::get('/estudiante/recibos/{id}/imprimir', [App\Http\Controllers\Api\V1\Pagos\ReciboCajaController::class, 'imprimirEstudiante']);

Route::get('/certificados/{token}/pdf', [App\Http\Controllers\Api\V1\Estudiantes\CertificadoElectronicoController::class, 'pdf'])->name('certificados.pdf');
Route::get('/certificados/{token}', fn (string $token) => view('estudiante.certificado', ['token' => $token]))->name('certificados.validar');

// Portal del Estudiante — Auth pages (standalone)
Route::get('/estudiante/login', fn () => view('estudiante.login'))->name('estudiante.login');
Route::get('/estudiante/registro', fn () => view('estudiante.registro'))->name('estudiante.registro');
Route::get('/estudiante/activar', fn () => view('estudiante.activar'))->name('estudiante.activar');

// Portal del Estudiante — Protected pages (with layout)
Route::get('/estudiante', fn () => view('estudiante.dashboard', ['currentSection' => 'inicio']))->name('estudiante.dashboard');
Route::get('/estudiante/historial', fn () => view('estudiante.historial', ['currentSection' => 'historial']))->name('estudiante.historial');
Route::get('/estudiante/certificados', fn () => view('estudiante.certificados', ['currentSection' => 'certificados']))->name('estudiante.certificados');
Route::get('/estudiante/matricula', fn () => view('estudiante.matricula', ['currentSection' => 'matricula']))->name('estudiante.matricula');
Route::get('/estudiante/comprobante', fn () => view('estudiante.comprobante', ['currentSection' => 'pagos']))->name('estudiante.comprobante');
Route::get('/estudiante/pagos', fn () => view('estudiante.pagos', ['currentSection' => 'pagos']))->name('estudiante.pagos');
Route::get('/estudiante/recibos', fn () => view('estudiante.recibos', ['currentSection' => 'recibos']))->name('estudiante.recibos');
Route::get('/estudiante/pagos/paypal-retorno', fn () => view('estudiante.paypal-retorno', ['currentSection' => 'pagos']))->name('portal.pagos.paypal.retorno');
Route::get('/estudiante/pagos/paypal-cancelado', fn () => view('estudiante.paypal-cancelado', ['currentSection' => 'pagos']))->name('portal.pagos.paypal.cancelado');
