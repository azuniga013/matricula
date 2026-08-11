# Guía para OpenCode

## Realidad del repositorio

- Es un monolito Laravel 12 en la raíz del repositorio; no existen `backend/`, `frontend/` ni una app React separada.
- La interfaz administrativa y el portal del estudiante son Blade + Alpine.js + Tailwind; Vite compila `resources/css/app.css` y `resources/js/app.js`.
- La API está en `routes/api.php` bajo `/api/v1`; las páginas HTML están en `routes/web.php`.
- El dominio usa nombres de tablas y conceptos de negocio en español. No inventar nombres alternativos si ya existe una tabla funcional.
- Los límites principales están en `app/Http/Controllers/Api/V1`, `app/Models`, `app/Services`, `database/migrations` y `database/seeders`.

## Comandos

Requisitos: PHP 8.2+, extensiones `gd` y `zip`, Composer, Node/npm.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

- El atajo equivalente de instalación es `composer run setup`; crea `.env`, migra y construye los assets.
- Desarrollo completo: `composer run dev` (servidor Laravel, cola, logs de Pail y Vite).
- Solo frontend: `npm run dev`; build de producción: `npm run build`.
- Suite completa: `composer test` o `php artisan test`.
- Prueba enfocada: `php artisan test tests/Feature/PagoTest.php` y, para un caso, agregar `--filter NombreDelTest`.
- Formateo PHP: `vendor/bin/pint`.
- Para una base local reproducible: `php artisan migrate:fresh --seed`. Los seeders se encadenan desde `database/seeders/DatabaseSeeder.php`.
- Antes de depurar rutas, usar `php artisan route:list --path=api/v1`.

Las pruebas fuerzan SQLite en memoria, `RefreshDatabase`, cola síncrona y correo `array` mediante `phpunit.xml`; no requieren servicios externos. CI ejecuta `composer install`, genera `.env` y `APP_KEY`, y `php artisan test` en PHP 8.2, 8.3 y 8.4.

## Arquitectura y seguridad

- Hay dos autenticaciones independientes: usuarios administrativos con Sanctum (`auth:sanctum`) y estudiantes con tokens SHA-256 de `accesos_estudiante` (`auth.estudiante`). Nunca intercambiarlas.
- Toda API administrativa debe estar en el grupo `auth:sanctum`, usar un permiso RBAC explícito y validar también el alcance de datos. La autorización efectiva proviene de `usuario_roles` y `rol_permisos`, no de `usuarios.rol_id` ni de nombres de roles.
- Para recursos REST protegidos, usar `Route::apiResourceProtegido(...)`; para acciones especiales, `Route::accionProtegida(...)`. Registrar el módulo en `config/rbac.php` y sincronizarlo con `SeguridadRbacSeeder`/`RegistroPermisosService`.
- El frontend puede ocultar acciones según `window.api.permisos`, pero la API sigue siendo la autoridad y debe responder `403` sin permiso. Consultar `docs/PATRON_IMPLEMENTACION_RBAC.md` antes de añadir un módulo.
- `resources/js/app.js` debe publicar `window.api` antes de `Alpine.start()`. No mover ese orden: los componentes Blade dependen de él.
- Las respuestas de error nuevas deben usar `App\Helpers\RespuestaError`; no exponer excepciones, tokens, contraseñas ni detalles técnicos al usuario.
- Los cambios sensibles (pagos, recibos, caja, calificaciones, inventario, permisos) deben conservar auditoría y usar transacciones cuando corresponda.

## Reglas de dominio que cambian implementaciones

- La matrícula siempre apunta a `ofertas_academicas`, no directamente a un nivel u horario. Los pagos de matrícula/cuotas se aplican a `obligaciones_pago_estudiante`.
- El alcance por sucursal, docente, alumno o propietario se resuelve en `ResolutorAlcanceDatos`; no confiar en filtros del frontend.
- No mostrar IDs internos en pantallas o reportes. Presentar códigos/nombres funcionales; una versión de plan se muestra como `{plan} · V{número}`.
- En recibos, `estado` es solo `emitido`, `anulado` o `reversado`; `veces_reimpreso` es un dato separado.
- Un recibo emitido no se edita directamente: usar anulación, reversión o ajuste autorizado. Un pago aprobado debe generar o asociar recibo.
- Los conceptos contables (`MAT`, `CUO`, `PMA`, etc.) no se duplican por nivel, cuota, horario o sucursal; esos detalles viven en planes/obligaciones.

## Rutas y compatibilidad

- SmarterASP/IIS puede bloquear `PUT`, `PATCH` y `DELETE`. Para `update`, registrar `Route::match(['PUT', 'PATCH', 'POST'], ...)`; para `destroy`, `Route::match(['DELETE', 'POST'], ...)`. No añadir `Route::put` ni `Route::delete` explícitas.
- Regla obligatoria de interfaz: toda actualización de catálogos, matrículas, pagos, recibos y procesos administrativos debe usar `window.api.actualizar(url, payload, config)`, que envía `POST`. Nunca usar `axios.put` ni `axios.patch` en Blade/JS. La protección global de `resources/js/app.js` también convierte accidentalmente esos verbos en `POST` para `/api/v1`.
- Mantener las APIs en JSON con `resultado`, `codigo`, `mensaje` y, en errores, `codigo_error`/`errores` cuando aplique.
- Las migraciones son la fuente de verdad del esquema. Añadir modelo, relaciones, seeder y pruebas junto con una tabla nueva; no modificar datos históricos destructivamente.

## Seguimiento de trabajo

- `docs/PENDIENTES.md` es el registro canónico de tareas pendientes. Leerlo al iniciar una tarea y actualizarlo antes de cerrarla.
- Cada cambio debe marcar la tarea correspondiente, registrar evidencia de verificación y añadir un pendiente nuevo si descubre trabajo relacionado.
- No usar `docs/avance.md`, documentos fechados ni `docs/spec-feed-planCobro.md` como lista vigente; sirven como historial y deben contrastarse con el código.

## Referencias

- `docs/PATRON_IMPLEMENTACION_RBAC.md`: patrón obligatorio para módulos y permisos.
- `docs/PATRON_MODULARIZACION_CASOS_USO.md`: semáforo de decisión de cuándo extraer casos de uso (P-032) y estructura de módulos.
- `docs/ARQUITECTURA_RBAC.md`: flujo de autorización y alcances.
- `docs/REGLAS_NEGOCIO_POR_DOMINIO.md`: reglas detalladas del Portal Académico y Portal del Estudiante.
- `docs/PENDIENTES.md`: registro canónico de pendientes y estado de validación.
- `docs/API_*.md`: contratos funcionales de cada módulo.
- `docs/PRUEBAS_ACEPTACION_RESPONSIVE.md`: criterios de aceptación de interfaz.
- `docs/DESPLIEGUE_SMARTERASP.md` contiene referencias antiguas a `backend/`/React; verificar siempre la configuración y los workflows antes de seguir esas rutas literalmente.
