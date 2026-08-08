# Pendientes del proyecto

Registro operativo canónico de trabajo pendiente. Se actualiza junto con cada
tarea; los documentos fechados, `docs/avance.md` y el historial de
`docs/spec-feed-planCobro.md` son antecedentes, no la lista vigente.

## Cómo mantenerlo

- Estados permitidos: `pendiente`, `en_progreso`, `por_validar`, `bloqueado`,
  `completado` y `descartado`.
- Cada tarea debe tener un ID estable. No reutilizar IDs ni borrar tareas
  históricas; cambiar su estado y añadir una nota breve.
- Antes de comenzar una tarea, pasarla a `en_progreso`; al terminar, registrar
  evidencia (prueba, comando, pantalla o decisión) y dejarla en
  `completado`, `por_validar` o `bloqueado`.
- Si una tarea resulta obsoleta por la arquitectura actual, marcarla
  `descartado` explicando el motivo, en vez de conservarla como pendiente.
- Revisar este archivo antes de iniciar trabajo y actualizarlo antes de cerrar
  la tarea.

## Estado al 2026-08-05

### Pendientes prioritarios

| ID | Estado | Tarea | Evidencia o siguiente paso |
|---|---|---|---|
| `P-001` | `por_validar` | Completar el filtro académico reutilizable en gestiones de matrícula, recibos, cierres de caja, inventario y ficha/consultas de estudiante. | Gestiones de matrícula y Recibos de Caja aplican `período -> plan -> nivel -> grupo/oferta` en interfaz y API. Inventario y cierres de caja no dependen de oferta; la ficha conserva el historial completo. Verificado con `PagoTest`, `MatriculaTest`, caché Blade y build; falta aceptación visual. |
| `P-002` | `pendiente` | Completar pruebas automatizadas de matrícula, pagos, asistencia, calificaciones, monitor de cupos, reportes y flujos de matrícula. | Añadir casos de éxito, denegación RBAC, alcance y transiciones de estado. |
| `P-003` | `por_validar` | Cerrar la cobertura RBAC por entidad y alcance. | Verificar estudiantes, matrículas, pagos, recibos, caja, calificaciones, inventario y reportes con sucursal, docente, propietario y alumno. |
| `P-004` | `por_validar` | Revisar seguridad de sesiones administrativas. | Confirmar revocación al inactivar/cambiar contraseña, bloqueo, protección del último superadministrador y auditoría detallada. |
| `P-005` | `pendiente` | Registrar aceptación funcional de monitor de cupos, PDF y Excel en escritorio y móvil. | Ejecutar `docs/PRUEBAS_ACEPTACION_RESPONSIVE.md` y adjuntar resultado o incidencia. |
| `P-006` | `completado` | Validar despliegue en producción. | Despliegue por `git push mojahost main` completado; validación funcional en producción confirmada por el usuario el 2026-08-05. |

### Pendientes funcionales

| ID | Estado | Tarea | Evidencia o siguiente paso |
|---|---|---|---|
| `P-007` | `pendiente` | Probar integralmente las configuraciones de flujo de matrícula. | Cubrir origen, concepto, método, precedencia, fallback `tecnico`, banderas, link de pago, comprobante, reenganche y desactivación. |
| `P-008` | `pendiente` | Revisar la consistencia de las transiciones de matrícula, pago, obligación, cupo y recibo. | Comparar reglas en `docs/REGLAS_NEGOCIO_POR_DOMINIO.md` con controladores y pruebas; corregir divergencias. |
| `P-009` | `por_validar` | Revisar fechas y datos históricos de pagos y recibos. | Confirmar que `fecha_proceso` y `fecha_recibo` coincidan en grid, detalle, impresión y reportes. |
| `P-013` | `completado` | Exigir cuenta bancaria para depósitos y transferencias. | Migración `2026_08_05_000001`; API administrativa y portal del estudiante validan cuenta activa, la persisten en el pago y la muestran para seleccionar. Verificado con `PagoTest` enfocado y `npm run build`. |
| `P-014` | `completado` | Mostrar cuentas bancarias de depósito en Dashboard y pagos del Estudiante. | Dashboard y formulario de pagos reciben las cuentas activas en la respuesta principal del portal; se listan banco, nombre, número y tipo, y se muestra una alerta visible si no hay cuentas configuradas. La carga de comprobante también recupera las cuentas cuando se abre directamente. |
| `P-015` | `completado` | Seleccionar plan de estudio antes de la oferta al matricular. | Matrícula administrativa y portal aplican Plan → Nivel → Oferta; ambas APIs validan que la oferta pertenezca al plan enviado y conservan `oferta_academica_id` como fuente de verdad. Verificado con `MatriculaTest`, `PortalEstudianteTest`, caché Blade y build. |
| `P-016` | `completado` | Documentar el proceso de cambio de plan de estudios. | `docs/PROPUESTA_CAMBIO_PLAN_ESTUDIOS.md` define flujo, controles académicos/financieros, trazabilidad, endpoints, pruebas y decisiones pendientes. |
| `P-017` | `completado` | Corregir la carga de grupos y estudiantes en Asistencias. | La vista conserva correctamente la oferta seleccionada, muestra fallos de API y el backend incorpora ofertas cerradas o llenas que continúan requiriendo pase de lista. Se corrigió la carga del régimen académico desde el nivel de la oferta, que provocaba error 500 al consultar grupos. Verificado con `CalificacionTest` enfocado (incluye regresión de asistencias), caché Blade y `npm run build`. |
| `P-018` | `completado` | Habilitar generación de certificado desde Calificaciones para docentes. | El historial se sincroniza al guardar una calificación; el botón de Calificaciones permite generar el certificado con `calificaciones.modificar`, y el Historial del Portal permite al estudiante emitir solo su certificado aprobado. Se corrigió la búsqueda del historial al emitir, evitando duplicados, y se ubicó la ruta del portal fuera de la autenticación administrativa. Verificado en `CalificacionTest`, `PortalEstudianteTest`, caché Blade y build. |
| `P-019` | `completado` | Usar verbos compatibles con IIS/SmarterASP en pantallas administrativas. | `window.api.actualizar()` centraliza las actualizaciones por `POST`; Ofertas, Estudiantes, Inventario, Catálogos, Planes de cobro, Seguridad y configuraciones de matrícula lo usan. La protección global convierte cualquier `PUT`/`PATCH` accidental para `/api/v1` en `POST`; las rutas conservan compatibilidad de backend. Auditoría: Matrícula, Pagos y Recibos ya operaban por `POST`. Verificado con `OfertaAcademicaTest`, `MatriculaTest`, `PagoTest`, caché Blade y build. |
| `P-020` | `completado` | Corregir acceso y configuración de proveedores de pago. | La pantalla usa el token administrativo correcto y conserva la sesión; el API enmascara configuraciones sensibles y acepta el contrato `config` de la pantalla. Los permisos `configuracion.pagos.consultar` y `configuracion.pagos.modificar` están incluidos para `SUPERADMIN` y `ADMIN_GENERAL` mediante `SeguridadRbacSeeder`. Verificado con `ProveedorPagoTest`, caché Blade y build. |
| `P-021` | `en_progreso` | Crear APK offline para docentes. | Base Expo en `mobile-docentes/`: login administrativo de docente, SecureStore, SQLite, ofertas, alumnos, asistencia, notas y cola offline. Incluye menú de Ofertas Académicas, Asistencia Diaria y Calificaciones: cada módulo filtra por período, muestra ofertas del docente y después su lista de estudiantes. El Android valida por Wi-Fi el certificado Let’s Encrypt y login/`/me` entregan `docente_id`. Se corrigió la asignación de permisos mínimos del rol `DOCENTE` y los mensajes de sincronización para no ocultar rechazos del servidor. Se corrigió `build.gradle` para usar `signingConfigs.release` (firma institucional). Se desinstaló v0.1.0 (firma anterior perdida), se recreó el keystore institucional con contraseña segura ASCII, se compiló y firmó v0.1.1 con `apksigner` v2/v3 y se instaló exitosamente en el Samsung S24+ de prueba. `keystore.properties` actualizado. 2026-08-07: el hosting bloquea `POST` multipart de 61 MB por `client_max_body_size`/HTTP2 (`ERR_HTTP2_PROTOCOL_ERROR`); se añadió la modalidad **registrar desde APK ya colocado** en `storage/app/private/apk-docentes/` (raíz del disco `local` en Laravel 12, sin upload HTTP): `POST /distribucion-apk/docentes?desde_servidor=1` lee el archivo más reciente del servidor, calcula hash/tamaño y lo publica; el panel tiene checkbox "Registrar desde un APK ya colocado". Verificado con `DistribucionApkDocenteTest` (5 pruebas, luego 22 aserciones al añadir cobertura de descarga) y build. 2026-08-07: se corrigió el `500` de `/apk/docentes/descargar`: `Storage::disk('local')->download()` devuelve `StreamedResponse` en Laravel 12 y el return type solo admitía `BinaryFileResponse|Response`; se amplió el contrato y se verificó la descarga. Desplegado y **verificado en producción**: la descarga responde `200` con `application/vnd.android.package-archive` (63 939 375 bytes ≈ 60,98 MB); la URL pública muestra "Versión 0.1.0". Pendiente: confirmar que se distribuya la v0.1.1 (el registro activo es v0.1.0; la v0.1.1 quedó de borrador) y publicarla desde el panel, y probar login con credenciales docentes reales en el dispositivo. La llave y APK están ignoradas; el panel administra publicaciones y la URL pública `/apk/docentes` entrega solo la versión activa. **2026-08-07/08**: flujo de asistencia renovado en `App.jsx` (v0.1.2 / versionCode 3, SHA-256 `7923cf5d…`): al entrar muestra fecha y lista de estudiantes; al tocar un estudiante se abre una vista con solo su nombre y las **banderas** de estado (Presente/Falta/Justificada/Tardanza); desde ahí se marca y al volver la lista muestra solo el estado elegido con un badge de color. Compilada y firmada en `mobile-docentes/android` con Gradle `assembleRelease` (firma institucional), registrada y publicada en producción vía API con token Sanctum de deploy (id 10; despublicó la v0.1.1). **Verificado en producción**: `GET /apk/docentes/descargar` responde `200` con `application/vnd.android.package-archive` (63 889 088 bytes) y el panel lista la v0.1.2 publicada. **2026-08-08**: se corrigió el retorno de asistencia tras refresco: la app nunca descargaba los estados ya guardados (`/asistencias/por-oferta`), siempre reinicializaba todo en `presente`; ahora `openOffer`/`refresh` cargan los estados del servidor (y caché local por oferta+fecha) y el guardado persiste el caché. Además: ofertas sin matrículas ya no fallan (`registrar` con lista vacía responde `data.registradas=0` en vez de `422`); la app bloquea guardar sin estudiantes; y ante `401` la sesión cierra y pide reingresar en vez de reintentar el loop. Publicada **v0.1.3 / versionCode 4** en producción (id 11, SHA-256 `3f4b324c…`): `GET /apk/docentes/descargar` responde `200` con `63 913 348` bytes. `CalificacionTest` con 3 pruebas de asistencia en verde (incluida lista vacía). |
| `P-022` | `pendiente` | Notificar a responsables por falta o tardanza. | Diseño en `docs/NOTIFICACIONES_ASISTENCIA_FAMILIAS.md`: contactos con consentimiento, cola idempotente, correo, proveedor oficial WhatsApp y compatibilidad offline. No activar envíos hasta completar protección de datos, proveedor y pruebas. |
| `P-023` | `por_validar` | Crear usuarios docentes y asignar roles desde Seguridad. | El formulario envía confirmación de contraseña, uno o más roles y vínculo con docente existente; `UsuarioTest` pasó (3 pruebas, 9 aserciones) y el build Blade completó. Falta aceptación visual en producción. |
| `P-024` | `completado` | Mostrar el último acceso de los usuarios en Seguridad. | El listado de usuarios no devolvía `ultimo_acceso` (campo vacío): ese dato solo vive en `sesiones_usuario` y el `User` no lo incluye. `UsuarioController@index` ahora lo expone como subconsulta `MAX(ultimo_acceso)` por usuario y la tabla lo formatea con `formatearFecha`. Verificado con `UsuarioTest` ampliado (11 aserciones). |
| `P-025` | `completado` | Cargar el catálogo de permisos al abrir "Administrar Permisos" de un rol. | La modal se alimentaba de `todosPermisos`, que solo se llenaba al visitar la pestaña Permisos; al entrar directo a Roles la matriz salía vacía. `editRolePermisos` ahora consulta en paralelo `/seguridad/permisos` y `/roles/{id}/permisos` y agrupa por módulo dentro del modal. Verificado con `php artisan view:cache`. |
| `P-011` | `completado` | Ocultar ofertas de periodos cerrados en el Portal del Estudiante. | `mis-ofertas` devuelve vacío sin periodo abierto; la reserva rechaza periodos cerrados con `422_PERIODO_NO_ABIERTO`. Verificado en `PortalEstudianteTest`. |
| `P-012` | `completado` | Mostrar en Matrícula Online el periodo académico en el que se está registrando el estudiante. | La API entrega código, nombre y fechas; la vista muestra la vigencia en el resumen, cada oferta y la confirmación. Verificado con `PortalEstudianteTest` y `npm run build`. |

### Higiene documental

| ID | Estado | Tarea | Evidencia o siguiente paso |
|---|---|---|---|
| `P-010` | `pendiente` | Depurar documentos históricos que todavía describen `backend/`, React o rutas antiguas. | Mantenerlos como histórico si aportan contexto, pero marcar explícitamente lo obsoleto o trasladar reglas vigentes a documentos actuales. |

## Criterio de cierre

Una tarea solo se marca `completado` cuando el cambio está implementado y
verificado. Una intención, una pantalla que solo oculta el problema o una
prueba manual no reproducible no bastan para cerrar una tarea crítica.
