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
| `P-019` | `completado` | Usar verbos compatibles con IIS/SmarterASP en pantallas administrativas. | Las actualizaciones de Ofertas, Estudiantes, Inventario y Usuarios usan `POST`; las rutas conservan compatibilidad de backend con `PUT`, `PATCH` y `POST`. Verificado con `OfertaAcademicaTest`, caché Blade y build. |
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
