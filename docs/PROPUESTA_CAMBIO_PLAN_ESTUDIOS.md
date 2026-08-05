# Propuesta: cambio de plan de estudios con trazabilidad

## Objetivo

Permitir que un estudiante cambie de plan de estudios mediante una gestión
autorizada, sin alterar ni perder sus matrículas, pagos, recibos,
calificaciones, asistencias u obligaciones históricas.

La matrícula siempre continúa perteneciendo a una oferta académica. El plan se
obtiene desde esta relación:

```text
Matrícula -> Oferta académica -> Nivel académico -> Versión del plan -> Plan de estudio
```

No se agregará `plan_estudio_id` directamente a `estudiantes` ni se modificará
la oferta de la matrícula anterior para representar el cambio.

## Alcance funcional

Se incorporará un nuevo tipo de gestión de matrícula:

| Código | Nombre | Propósito |
| --- | --- | --- |
| `CPL` | Cambio de plan de estudios | Finaliza académicamente la matrícula de origen y crea una nueva matrícula en una oferta de otro plan, tras revisión y aprobación. |

No debe reutilizarse `CAM` (cambio de horario) ni `CTR` (cambio de modalidad),
porque esos tipos conservan el mismo plan de estudio.

## Roles y responsabilidades

| Actor | Responsabilidad |
| --- | --- |
| Estudiante | Puede solicitar o consultar el estado cuando se habilite el portal; no puede aprobar ni aplicar saldos. |
| Personal administrativo | Registra la solicitud y adjunta el motivo y la oferta destino. |
| Responsable académico | Revisa equivalencias, nivel de continuidad, prerrequisitos y viabilidad académica. |
| Contabilidad | Define el tratamiento de pagos aprobados, saldos y diferencias de cobro. |
| Aprobador autorizado | Aprueba o rechaza la gestión con motivo y evidencia. |

Los permisos deben conservar el patrón RBAC de `matriculas.crear`,
`matriculas.consultar` y `matriculas.modificar`; si la institución exige doble
aprobación académica/contable, deben registrarse permisos específicos antes de
activar el flujo.

## Flujo propuesto

```text
Solicitud CPL
  -> revisión académica y contable
  -> rechazo, si no procede
  -> aprobación transaccional
       -> cerrar matrícula origen
       -> crear matrícula destino
       -> generar obligaciones destino
       -> registrar decisión financiera
       -> guardar auditoría de origen y destino
```

### 1. Registrar solicitud

La pantalla de Gestiones de Matrícula debe pedir:

- matrícula de origen activa;
- tipo `CPL`;
- motivo, obligatorio y con detalle suficiente;
- plan destino;
- nivel y oferta académica destino;
- observaciones académicas y, si procede, documentación de respaldo.

La selección de destino se filtra en el orden:

```text
Período abierto -> Plan destino -> Nivel -> Horario/grupo -> Oferta
```

La solicitud se crea con estado `pendiente`. En este punto no se cambia cupo,
matrícula, pago ni historial.

### 2. Validaciones antes de aprobar

La aprobación debe validar dentro de una transacción:

- La matrícula origen pertenece al estudiante y está en estado permitido.
- La oferta destino está abierta, pertenece a una sucursal permitida y tiene
  cupo disponible.
- La oferta destino corresponde al plan solicitado.
- No existe otra matrícula activa del estudiante en la oferta destino.
- No existe una gestión `CPL` pendiente para la misma matrícula origen.
- Se cumplen prerrequisitos, conflictos de horario y reglas del período.
- Se definió el resultado de la revisión académica: nivel de inicio en el plan
  destino y equivalencias aceptadas o rechazadas.
- Se definió la decisión financiera antes de ejecutar el cambio.

### 3. Decisión académica

La revisión académica debe dejar explícito uno de estos resultados:

| Resultado | Tratamiento |
| --- | --- |
| Sin equivalencias | El estudiante inicia en el nivel destino indicado. |
| Equivalencia parcial | Se reconocen únicamente los niveles o requisitos aprobados y documentados. |
| Equivalencia total del nivel | Se permite continuar en el nivel siguiente definido por el responsable académico. |
| Rechazado | Se conserva la solicitud con motivo; no se modifica ninguna matrícula. |

Las calificaciones, asistencias e historial del plan origen nunca se mueven ni
se editan. Una automatización futura de equivalencias deberá usar una tabla
específica y aprobaciones auditables; no debe inferirse solo por coincidencia de
nombres de niveles.

### 4. Decisión financiera

Los pagos aprobados y recibos emitidos son evidencia histórica y no se pueden
mover, borrar ni reaplicar silenciosamente.

Antes de aprobar debe registrarse una decisión financiera:

| Caso | Tratamiento requerido |
| --- | --- |
| Sin pagos aprobados | Cancelar obligaciones pendientes de la matrícula origen según la política autorizada. |
| Pagos aprobados sin saldo aplicable | Conservarlos ligados a la matrícula origen y documentar el cierre. |
| Saldo a favor | Crear un ajuste o crédito autorizado para aplicarlo al nuevo plan; nunca cambiar directamente el pago original. |
| Diferencia por pagar | Generar obligaciones de la matrícula destino y exigir el pago normal. |
| Reembolso | Tramitarlo por el proceso financiero autorizado, con referencia y auditoría. |

El detalle de la decisión, monto, responsable y referencias de ajuste o
reembolso debe quedar en la gestión, no solo en un comentario libre.

### 5. Ejecución al aprobar

La operación completa debe ser atómica:

1. Bloquear la matrícula origen y la oferta destino con `lockForUpdate()`.
2. Guardar una fotografía de la matrícula y oferta origen en la gestión.
3. Cerrar la matrícula origen mediante el estado autorizado por negocio
   (`cancelado` con motivo de cambio de plan, o un estado futuro
   `cambiado_plan` si se adopta formalmente).
4. Liberar el cupo de la oferta origen cuando corresponda.
5. Crear una nueva matrícula con nueva oferta, sucursal, código y fecha de
   reserva/confirmación según la decisión financiera.
6. Reservar u ocupar el cupo destino según el estado de la nueva matrícula.
7. Generar obligaciones únicamente desde el plan de cobro de la oferta destino.
8. Guardar la matrícula destino, los datos posteriores, decisión financiera,
   usuario aprobador y fecha de ejecución en la gestión.
9. Confirmar la transacción.

Si falla cualquiera de estos pasos, no debe persistir ningún cambio parcial.

## Persistencia y auditoría

La tabla `gestiones_matricula` ya conserva la matrícula origen, oferta origen,
oferta destino, estados, motivo y fotografías. Para trazabilidad inequívoca se
propone agregar:

| Campo | Tipo | Uso |
| --- | --- | --- |
| `matricula_destino_id` | FK nullable a `matriculas` | Identifica exactamente la nueva matrícula creada por la gestión. |
| `decision_academica` | string o enum | `sin_equivalencias`, `equivalencia_parcial`, `equivalencia_total`, `rechazado`. |
| `decision_financiera` | string o enum | `sin_saldo`, `saldo_a_favor`, `diferencia_por_cobrar`, `reembolso`. |
| `monto_saldo_a_favor` | decimal nullable | Monto autorizado, si existe. |
| `referencia_ajuste_financiero` | string nullable | Referencia al ajuste, crédito o reembolso. |
| `revisado_academicamente_por/en` | usuario y fecha nullable | Evidencia de la revisión académica. |
| `revisado_contablemente_por/en` | usuario y fecha nullable | Evidencia de la revisión contable. |

Los campos `datos_antes` y `despues` deben contener, como mínimo, códigos e
identificadores de plan, versión, nivel, oferta, período, sucursal, horario,
estado y cupos. Esto permite reconstruir el evento aunque los catálogos cambien
en el futuro.

## Pantallas y API

### Portal Académico

- Agregar `CPL` a Tipos de Gestión.
- Al seleccionarlo, mostrar el selector Plan -> Nivel -> Oferta destino.
- Mostrar matrícula origen, plan origen, plan destino y estado de revisiones.
- Solicitar motivo y decisión financiera antes de habilitar la aprobación.
- En el detalle, mostrar enlace a la matrícula destino, pagos históricos y
  referencias de ajustes cuando existan.

### Portal del Estudiante

- Primera fase recomendada: solo consulta de estado y solicitud con motivo.
- La selección de oferta destino puede requerir aprobación administrativa; el
  estudiante nunca puede aprobar el cambio ni definir equivalencias o saldos.

### Endpoints sugeridos

Mantener las rutas protegidas existentes y extender su contrato:

- `POST /api/v1/gestiones-matricula/solicitar` con tipo `CPL`,
  `oferta_academica_destino_id`, motivo y datos de respaldo.
- `POST /api/v1/gestiones-matricula/{id}/revisar-academicamente`.
- `POST /api/v1/gestiones-matricula/{id}/revisar-contablemente`.
- `POST /api/v1/gestiones-matricula/{id}/aprobar` solo después de las revisiones
  obligatorias.
- `POST /api/v1/gestiones-matricula/{id}/rechazar` con motivo.

Todas las rutas deben verificar RBAC y alcance de sucursal en backend.

## Pruebas de aceptación mínimas

- Solicitud CPL no cambia datos académicos ni financieros.
- Rechazo CPL conserva matrícula, pagos, recibos e historial origen.
- Aprobación crea una matrícula destino y enlaza `matricula_destino_id`.
- La matrícula origen conserva su oferta y evidencia histórica.
- No se puede aprobar sin cupo, prerrequisitos, decisión académica o decisión
  financiera requeridas.
- Los pagos originales no cambian de `matricula_id`.
- Un saldo a favor exige un ajuste autorizado y referenciado.
- La operación revierte completamente ante un error dentro de la transacción.
- Usuarios sin permiso o fuera de sucursal reciben `403`.

## Orden de implementación

1. Acordar estados permitidos para la matrícula origen y política de saldos.
2. Crear migración para la trazabilidad adicional de la gestión.
3. Crear el tipo `CPL` y permisos/revisiones necesarios.
4. Implementar servicio transaccional de cambio de plan y pruebas de dominio.
5. Incorporar la pantalla administrativa y contratos API.
6. Habilitar consulta y solicitud desde el Portal del Estudiante.
7. Ejecutar pruebas de aceptación con casos de equivalencia y pago.

## Decisiones pendientes de negocio

- ¿Qué estados exactos puede tener la matrícula origen después del cambio?
- ¿Quién aprueba equivalencias académicas?
- ¿Cuál es el proceso oficial de saldo a favor y reembolso?
- ¿Se permite el cambio dentro del mismo período o solo antes de iniciar clases?
- ¿El alumno puede solicitar el cambio desde el portal o solo administración?

