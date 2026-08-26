# Coordinación entre agentes

Documento de trabajo compartido para que los agentes que trabajan en paralelo
sobre el mismo repositorio colaboren sin pisarse. El registro canónico de
tareas sigue siendo `docs/PENDIENTES.md`; este documento solo contiene el
estado transitorio, los acuerdos de interfaz y las zonas de colisión.

> Actualizar este documento cuando se empiece o se suelte un área. Marcar con
> fecha (`YYYY-MM-DD`) los avances relevantes.

## Quién toca qué

| Agente / rol | Área | Archivos que NO debe editar en paralelo |
|---|---|---|
| Agente principal (P-028/029/034/035) | Modularización Pagos, Matrículas, Caja, Calificaciones, Inventario | `app/Modules/`, `app/Providers/AppServiceProvider.php`, `PagoController`, `MatriculaController`, `SesionCajaController`, `CalificacionController`, `InventarioLibroController`, `ReciboCajaController` |
| Agente P-002 | Pruebas automatizadas: cobertura RBAC y alcance | `tests/Feature/` (`PagoTest`, `MatriculaTest`, `CajaTest`, `CalificacionTest`, `InventarioLibroTest`, `PortalEstudianteTest`, `IntegracionFlujoAcademicoTest`), `docs/PENDIENTES.md`. Archivos de pruebas nuevos ya creados y con cobertura: `AlcanceApiTest.php`, `DenegacionRbacTest.php` (no duplicar). **División P-003 (2026-08-10):** el otro agente conserva `AlcanceApiTest.php`, `DenegacionRbacTest.php`, `CalificacionTest.php`, `PortalEstudianteTest.php` y `AlcanceAdministrativoApiTest.php`; este frente abrió `AlcanceAdministrativoTest.php` para alcance administrativo adicional por sucursal/propietario sin tocar esos cinco archivos. |
| Agente frontend docente (2026-08-24) | Frontend web del docente y menú administrativo restringido | `resources/views/layouts/admin.blade.php`, `resources/views/admin/mis-grupos.blade.php`, `resources/views/admin/asistencias.blade.php`, `resources/views/admin/calificaciones.blade.php`, `docs/PENDIENTES.md` |
| Agente APK docente home (P-052, 2026-08-24) | Pantalla inicial `Portal Docente`, resumen del docente y tarjetas informativas | Mientras se trabaje en paralelo, reservar `mobile-docentes/src/components/DocenteHome*`, `mobile-docentes/src/hooks/useDocenteHome*` y cualquier extractor nuevo del home. No mezclar cambios grandes directos en `mobile-docentes/App.jsx` sin coordinar el punto de montaje. |
| Agente APK docente menú (P-052, 2026-08-24) | Menú principal visual con iconos y descripciones por módulo | Reservar `mobile-docentes/src/components/MainMenu*`, `mobile-docentes/src/components/MenuCard*`, assets/iconografía local y estilos compartidos del menú. No cambiar la carga de datos del dashboard docente. |
| Agente APK docente UX módulos (P-052, 2026-08-24) | Navegación y UX de Ofertas, Asistencia, Calificaciones y Sincronización después del rediseño del home | No tocar componentes del home ni del menú; concentrarse en pantallas internas (`OfferList`, asistencia, calificaciones, sincronización) y en la transición desde el menú nuevo. |
| Agente pagos links anticipados (P-059, 2026-08-24) | Flujo de enlaces de pago anticipados por monto y reasignación automática en pagos/portal estudiante | `app/Http/Controllers/Api/V1/Pagos/EnlacePagoController.php`, `app/Models/EnlacePago.php`, `app/Http/Controllers/Api/V1/Pagos/PagoController.php`, `app/Http/Controllers/Api/V1/Estudiantes/PortalEstudianteController.php`, `resources/views/admin/pagos.blade.php`, `resources/views/estudiante/pagos.blade.php`, `tests/Feature/PagoTest.php`, `tests/Feature/PortalEstudianteTest.php`, `docs/PENDIENTES.md` |

## Acuerdos del frente docente

- 2026-08-24: el frontend docente usa el menú administrativo existente, pero se ocultan las secciones no docentes cuando `sessionUser.docente_id` está presente.
- 2026-08-24: el frente docente conserva solo `Mis Horarios`, `Asistencias`, `Calificaciones` y `APK Docentes` como accesos visibles.
- 2026-08-24: si otro agente toca vistas docentes, debe respetar el mismo criterio de visibilidad y no reintroducir módulos administrativos completos en ese menú.
- 2026-08-24: si se necesita endurecer más, la siguiente capa a coordinar es el bloqueo de rutas directas, no el menú.
- 2026-08-24: el frente `P-059` debe considerar la captura mínima para enlaces de pago: `Código` y `Nombre` preferiblemente `readonly`, y todo dato que venga de otros procesos (cuenta bancaria, estado operativo inicial, referencia derivada) no debe requerirse manualmente en la pantalla.
- 2026-08-25: no se observaron cambios activos en worktree sobre el frente `P-059` al retomar la tarea. El agente actual asume el cierre integral del flujo de enlaces anticipados desde `app/Http/Controllers/Api/V1/Pagos/EnlacePagoController.php`, `PagoController`, `PortalEstudianteController`, `resources/views/admin/pagos.blade.php`, `resources/views/estudiante/pagos.blade.php` y pruebas asociadas.
- 2026-08-25: avance del frente `P-059`: se detectó que el flujo no estaba cerrado porque faltaba persistir la URL real del enlace anticipado. Se agregó la migración `2026_08_24_000006_add_enlace_url_to_enlaces_pago_table.php`, el modelo `EnlacePago` ya contempla `enlace_url`, y el modal admin en `admin/pagos` ahora captura explícitamente la URL real y muestra `estado_operativo`. También se avanzó en el ciclo `reservado -> usado/desuso`: `ResolverEnlacePagoDisponible` ya puede reservar con `asignado_a_pago_id`/`asignado_a_estudiante_id`, y los casos de uso `AprobarPago`/`RechazarPago` ya marcan enlaces `usado` o `desuso` según corresponda. Si otro agente toca este frente, no reintroducir el supuesto anterior de usar `codigo` como si fuera la URL del enlace.

## Estado actual del frente docente

- Menú restringido por `docente_id` implementado en `resources/views/layouts/admin.blade.php`.
- Vistas docentes principales revisadas: `admin/mis-grupos`, `admin/asistencias`, `admin/calificaciones`.
- `docs/PENDIENTES.md` actualizado con la nota operativa del frente docente.
- Pendiente sugerido: validar con usuario docente real que el menú y el acceso directo a rutas no docentes se comporten como se espera.

## Frente APK docente P-052

- 2026-08-24: nueva solicitud vigente del usuario para mejorar `Portal Docente` en la APK (`mobile-docentes/`).
- Objetivo funcional: la primera pantalla debe ser más informativa y el menú principal debe presentarse como iconos/tarjetas con una breve descripción por módulo.
- Partición recomendada para trabajo paralelo:
  - Home/dashboard docente: resumen del docente, métricas y accesos rápidos.
  - Menú principal: iconos, descripciones, jerarquía visual y estados.
  - UX de módulos: ajustar transición y consistencia visual en Ofertas, Asistencia, Calificaciones y Sincronización.
- Regla de colisión: si `App.jsx` sigue siendo el punto central, un solo agente debe hacer el ensamblaje final; el resto debe extraer componentes nuevos para minimizar conflictos.
- Verificación mínima del frente: `npx expo export --platform android`.

Reglas de convivencia:

- **Los casos de uso (`app/Modules/*/CasosUso`) y los repositorios son de solo
  lectura para quien no es dueño del módulo.** Si P-002 necesita cambiar
  comportamiento, debe avisarlo aquí y en `PENDIENTES.md` antes de tocar esos
  archivos; lo normal es que las pruebas se escriban contra la API existente.
- **`docs/PENDIENTES.md` lo edita quien cierra una tarea.** Si ambos lo editan
  al mismo tiempo, el último que escriba debe revisar el diff para no perder el
  avance del otro (los pendientes de cada uno son filas distintas; no
  reescribir filas ajenas).
- **No hacer `git commit` sin confirmación explícita del usuario**, como siempre.
- Antes de ejecutar la suite completa, confirmar que nadie tiene la mitad de un
  cambio a medias; si hay dudas, consultar este documento y `git status`.

## Contratos de interfaz vigentes (acuerdos)

Estos contratos ya están implementados; respetarlos en código y en pruebas.

- **Modularización (P-032, cerrado 2026-08-10):** `docs/PATRON_MODULARIZACION_CASOS_USO.md`
  es el semáforo obligatorio para decidir cuándo extraer un caso de uso. Leerlo
  antes de tocar un controlador; las lecturas simples y el CRUD de catálogos no
  se extraen.

- **Lecturas de Pagos (P-030, cerrado 2026-08-10):** `index`, `show` y
  `obligaciones-estudiante` se quedan en `PagoController` como orquestación
  ligera. No crear repositorios de consulta para lecturas simples; la única
  regla de negocio (selección de obligaciones) ya vive en `ResolutorFlujoMatricula`.

- Respuestas JSON API v1: `resultado` (`A`/`R`), `codigo`, `mensaje`, `data`;
  en errores, `codigo_error` y opcionalmente `errores`.
- Errores controlados de casos de uso: `ResultadoCasoUso::error(codigo,
  mensaje, codigoError)` con `ResultadoCasoUso::exito(...)` para éxito. El
  controlador traduce `!ok()` a JSON `resultado: R`.
- Auditoría de errores HTTP: `App\Helpers\RespuestaError` (no exponer
  excepciones, tokens ni detalles técnicos).
- Autenticaciones: administrativa con Sanctum (`auth:sanctum`) y estudiantes
  con token SHA-256 (`auth.estudiante`). Nunca intercambiarlas.
- RBAC: la autorización efectiva sale de `usuario_roles` + `rol_permisos`, no
  de `usuarios.rol_id`. La API responde `403` sin permiso aunque el frontend
  oculte acciones.
- Actualizaciones en interfaz administrativa: `window.api.actualizar(url,
  payload)` siempre por `POST` (compatibilidad IIS/SmarterASP). Nunca
  `axios.put`/`patch` a `/api/v1`.
- Errores de inventario en el registro de pago: `RegistrarPago` lanza
  `App\Modules\Pagos\Exceptions\InventarioInsuficienteException` y responde
  `422` con `codigo_error = 422_INVENTARIO_INSUFICIENTE`; la transacción hace
  rollback (no se crea el pago ni movimiento).
- `VenderLibro` (Inventario) responde `data.venta` con `inventario` y
  `total_venta` (contrato de `InventarioLibroTest`).
- Lecturas simples (`index`, `show`, `kardex`, `resumen`) se quedan en el
  controlador; solo se extraen casos de uso donde hay reglas de negocio
  (P-030 decidido, P-032/P-033).

## Trabajo en curso sin commitear (no duplicar)

Todo lo relativo a P-028, P-029, P-034 y P-035 está implementado pero **sin
commit**. `app/Modules/` y el binding en `AppServiceProvider` son nuevos. No
rehacer ese trabajo; si algo falla, reportarlo aquí. Mi último cambio pendiente
de revisión del otro agente: `tests/Feature/InventarioLibroTest.php` (10
pruebas directas de casos de uso) y `docs/PENDIENTES.md` (conteos P-002/P-035).

## Notas por módulo

- **Pagos**: `PagoRepositorio`/`EloquentPagoRepositorio` solo persistencia;
  las reglas de transición viven en `Servicios/AplicadorEfectosPago`.
- **Matrículas**: casos de uso `ReservarMatricula`, `ConfirmarMatricula`,
  `CancelarMatricula`; servicios `ValidadorPrerrequisitos`,
  `ValidadorConflictoHorario`, `GeneradorObligacionesMatricula`.
- **Caja**: `AbrirSesionCaja`, `CerrarSesionCaja`, `AnularRecibo`,
  `ReimprimirRecibo`; `Servicios/GeneradorDetallesCierre`.
- **Calificaciones**: `RegistrarCalificaciones`, `ActualizarCalificacion`;
  servicios `ValidadorAccesoOfertaDocente` y `SincronizadorHistorialAcademico`.
  Alcance docente se resuelve por `docente_id` del usuario autenticado.
- **Inventario**: `RegistrarInventario`, `AjustarExistencia`, `VenderLibro`;
  `Servicios/RegistradorMovimientoInventario`. `InventarioLibroTest` incluye
  pruebas directas de los tres casos de uso (éxito y rechazos: duplicado,
  existencia negativa, stock insuficiente, inventario inexistente; registro con
  existencia 0 no crea movimiento).
- **P-003 (agente principal, 2026-08-10):** nueva suite aislada
  `tests/Feature/AlcanceAdministrativoApiTest.php` para alcance administrativo
  por sucursal/propietario. No mover esa cobertura a `AlcanceApiTest.php` ni a
  `DenegacionRbacTest.php` mientras ambos agentes trabajen en paralelo. Ajustes
  mínimos permitidos del agente principal en `CajaTest.php` e
  `InventarioLibroTest.php`: solo añadir alcance global al usuario admin de
  prueba para alinear fixtures viejos con el nuevo enforcement de alcance.

## Estado de validación

- Suite completa: **309 pruebas, 1039 aserciones** (2026-08-10). Incluye el
  avance previo de 253/841 más la ampliación de `DenegacionRbacTest.php`
  (**15 pruebas, 31 aserciones**) con 403 para Estudiantes, Matrículas,
  Pagos, Recibos de Caja, Caja, Cierre de Caja, Calificaciones, Inventario y
  Reportes, además de Seguridad y Gestiones de Matrícula. También suma el
  arranque de P-003 en pruebas ya soportadas por la app actual:
  `CalificacionTest` (alcance docente en listado y detalle),
  `PortalEstudianteTest` (alcance alumno/propietario en matrículas, pagos,
  recibos, historial, eliminación de pagos ajenos y registro sobre matrícula
  ajena) y las suites nuevas `AlcanceAdministrativoApiTest.php` (10 pruebas,
  44 aserciones) y `AlcanceAdministrativoTest.php` (9 pruebas, 23 aserciones)
  para alcance administrativo por sucursal/propietario. Los `por_validar` de
  `PENDIENTES.md` requieren aceptación visual en producción, no código nuevo.
- Cierre conjunto de P-003 (2026-08-10): el worktree actual ya contiene
  cobertura de alcance para `Estudiantes`, `Matrículas`, `Pagos`,
  `Recibos`, `Caja`, `Inventario`, `Calificaciones` y los reportes
  `financieros/pagos-pendientes`, `academicos/grupo` y
  `recibos/por-orden`. `PENDIENTES.md` ya lo marca como `completado`.
- Errores LSP conocidos y preexistentes (no bloquean):
  `Undefined type 'DB'` en `CalificacionTest`/`MatriculaTest`/`CajaTest` y
  `Undefined method 'id'` en `Comun\ContextoUsuario` /
  `Matriculas\Servicios\GeneradorObligacionesMatricula`; la suite pasa igual.

## Mensajes recientes para otros agentes

- 2026-08-24: La línea funcional vigente para WhatsApp ya no debe depender del
  catálogo antiguo como fuente operativa. El flujo principal quedó así:
  `Ofertas` define `whatsapp_grupo_nombre`, `Mis Horarios` / APK docente
  actualizan `whatsapp_link_periodo`, y el portal del estudiante resuelve el
  link desde la oferta. La APK docente ya compila (`expo export`) con edición
  del link del período en `Ofertas Académicas`. Si otro agente toca WhatsApp,
  no reintroducir `grupo_whatsapp_id` como requisito operativo.
- 2026-08-24: Para `P-052`, evitar rediseñar todo desde `App.jsx` en paralelo. Extraer componentes primero y dejar un agente integrador para el montaje final del `Portal Docente` y del menú principal.
- 2026-08-24: Seguridad/RBAC quedó alineado a permisos finos por submódulo en
  `routes/api.php` (`seguridad.usuarios.*`, `seguridad.roles.*`, etc.). Si otro
  agente modifica pruebas o seeders de seguridad, asumir que los permisos
  genéricos `seguridad.consultar|crear|modificar|configurar` ya no son la
  referencia principal para esas rutas.
