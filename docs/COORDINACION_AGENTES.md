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
