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
| `P-001` | `por_validar` | Completar el filtro académico reutilizable en gestiones de matrícula, recibos, cierres de caja, inventario y ficha/consultas de estudiante. | Auditar cada pantalla y confirmar si ya aplica `período -> nivel -> horario/grupo`. |
| `P-002` | `pendiente` | Completar pruebas automatizadas de matrícula, pagos, asistencia, calificaciones, monitor de cupos, reportes y flujos de matrícula. | Añadir casos de éxito, denegación RBAC, alcance y transiciones de estado. |
| `P-003` | `por_validar` | Cerrar la cobertura RBAC por entidad y alcance. | Verificar estudiantes, matrículas, pagos, recibos, caja, calificaciones, inventario y reportes con sucursal, docente, propietario y alumno. |
| `P-004` | `por_validar` | Revisar seguridad de sesiones administrativas. | Confirmar revocación al inactivar/cambiar contraseña, bloqueo, protección del último superadministrador y auditoría detallada. |
| `P-005` | `pendiente` | Registrar aceptación funcional de monitor de cupos, PDF y Excel en escritorio y móvil. | Ejecutar `docs/PRUEBAS_ACEPTACION_RESPONSIVE.md` y adjuntar resultado o incidencia. |
| `P-006` | `bloqueado` | Validar despliegue en producción. | Requiere ejecutar el despliegue FTPS y comprobar `/up`, API autenticada, catálogos, recibos, PDF y Excel. |

### Pendientes funcionales

| ID | Estado | Tarea | Evidencia o siguiente paso |
|---|---|---|---|
| `P-007` | `pendiente` | Probar integralmente las configuraciones de flujo de matrícula. | Cubrir origen, concepto, método, precedencia, fallback `tecnico`, banderas, link de pago, comprobante, reenganche y desactivación. |
| `P-008` | `pendiente` | Revisar la consistencia de las transiciones de matrícula, pago, obligación, cupo y recibo. | Comparar reglas en `docs/REGLAS_NEGOCIO_POR_DOMINIO.md` con controladores y pruebas; corregir divergencias. |
| `P-009` | `por_validar` | Revisar fechas y datos históricos de pagos y recibos. | Confirmar que `fecha_proceso` y `fecha_recibo` coincidan en grid, detalle, impresión y reportes. |
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
