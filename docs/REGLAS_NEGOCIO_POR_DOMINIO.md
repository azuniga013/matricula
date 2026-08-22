# Reglas de negocio por dominio

Este documento concentra las reglas funcionales que deben respetarse al modificar
la plataforma. Separa el **Portal Académico** (operación administrativa) del
**Portal del Estudiante** (autoservicio). Las validaciones del backend son la
fuente de verdad; la interfaz solo debe guiar y reflejar esas reglas.

Si este documento contradice una migración o el comportamiento ejecutable,
primero debe corregirse la documentación o aprobarse explícitamente el cambio
de negocio. No introducir una regla solo en una vista o componente Alpine.

## 1. Modelo transversal

### 1.1 Identidad funcional

- La aplicación usa una base centralizada y todas las operaciones deben
  conservar la sucursal cuando el registro pertenece a una sucursal.
- Las entidades se relacionan mediante sus tablas de dominio. No duplicar en
  `estudiantes` datos que pertenecen a matrículas, pagos, calificaciones,
  asistencias, recibos o historial académico.
- Los identificadores internos son técnicos. En pantallas, reportes y
  documentos mostrar códigos, nombres y descripciones funcionales, no IDs.
- Para catálogos usar preferentemente `CÓDIGO · Nombre`. Una versión de plan se
  presenta como `{plan} · V{número}`.
- Los conceptos contables no codifican nivel, horario, modalidad, sucursal ni
  número de cuota. Esos datos se obtienen desde la relación operativa:
  `pago -> matrícula -> oferta académica`.

### 1.2 Sucursal y alcance

- `sucursales` es el límite operativo de estudiantes, ofertas, matrículas,
  pagos, recibos, sesiones de caja, aulas, grupos de WhatsApp e inventario.
- Un usuario administrativo puede tener una o varias sucursales asignadas.
- El alcance global, por sucursal, por docente o por propietario lo resuelve
  `App\Services\ResolutorAlcanceDatos`.
- Nunca confiar en `sucursal_id` enviado por el frontend ni en filtros visibles
  para autorizar acceso a un registro.
- Un usuario con alcance docente solo puede operar las ofertas y estudiantes
  que correspondan a sus ofertas asignadas.

### 1.3 Autenticación y autorización

- El Portal Académico usa usuarios administrativos y Sanctum (`auth:sanctum`).
- El Portal del Estudiante usa tokens SHA-256 de `accesos_estudiante` mediante
  `auth.estudiante`.
- Los tokens, permisos y rutas de ambos portales son independientes. Un acceso
  de estudiante nunca concede permisos administrativos.
- Toda operación administrativa protegida requiere permiso RBAC explícito y
  alcance de datos. El nombre de un rol no autoriza por sí solo.
- Los permisos efectivos salen de `usuario_roles` y `rol_permisos`; no usar
  `usuarios.rol_id` como fuente de autorización.
- Ocultar un botón sin permiso mejora la experiencia, pero nunca reemplaza el
  `403` del backend.

### 1.4 Estados, auditoría y transacciones

- Las transiciones de estado deben validarse desde el estado actual; no aceptar
  cualquier valor válido sin comprobar que la transición sea permitida.
- Las operaciones sensibles deben ejecutarse en transacción y conservar
  bitácora: aprobar o rechazar pagos, anular recibos, cerrar o reabrir caja,
  cambiar cupos, modificar calificaciones, ajustar inventario y matricular por
  excepción.
- Conservar usuario, fecha y motivo cuando una operación implique aprobación,
  rechazo, anulación, corrección o reapertura.
- Las respuestas nuevas deben mantener `resultado`, `codigo`, `mensaje` y,
  cuando aplique, `codigo_error` y `errores`. Usar `RespuestaError` para errores
  funcionales y no exponer excepciones, tokens, contraseñas ni SQL.

### 1.5 Flujos de matrícula parametrizables

El comportamiento de matrícula y cobro no debe duplicarse en las vistas. Se
resuelve mediante `ConfiguracionFlujoMatricula` y
`ResolutorFlujoMatricula`.

- Cada configuración tiene un `codigo` único, `origen`, estado, concepto de
  pago y método de pago principal.
- Los orígenes válidos son `portal_estudiante`, `portal_administrativo` y
  `tecnico`. El origen `tecnico` sirve como respaldo de configuraciones
  antiguas, no como un tercer portal de usuario.
- Una configuración puede asociarse a varios conceptos y métodos mediante las
  tablas pivote `configuracion_flujo_matricula_conceptos` y
  `configuracion_flujo_matricula_metodos`.
- Solo se resuelven configuraciones activas. La selección considera origen,
  concepto y método de pago; si hay varias coincidencias, prevalece la de mayor
  `id`.
- Si no existe coincidencia, el resolutor usa valores seguros por defecto. En
  el Portal Académico, si no existe configuración explícita, también intenta el
  origen `tecnico` como respaldo.
- No cambiar una configuración activa para alterar operaciones históricas. Si
  se requiere un comportamiento nuevo, crear una configuración diferenciada o
  desactivar la anterior dejando trazabilidad.
- La desactivación normal conserva el registro y sus asociaciones. La
  eliminación permanente solo debe usarse mediante la acción administrativa
  explícita y después de verificar que no se necesite su historial.

Las banderas de una configuración significan:

| Bandera | Regla que controla |
|---|---|
| `habilita_reserva_cupo` | Permite crear una reserva y consumir cupo reservado. |
| `habilita_carga_comprobante` | Permite adjuntar o reemplazar comprobantes. |
| `requiere_comprobante` | Exige comprobante antes de avanzar a revisión/aprobación. |
| `habilita_revision_contable` | Permite que el pago pase por revisión administrativa. |
| `habilita_aprobacion_pago` | Permite aprobar o rechazar el pago desde administración. |
| `habilita_generacion_recibo` | Permite emitir o asociar recibo al pago aprobado. |
| `habilita_confirmacion_matricula` | Permite confirmar una matrícula reservada. |
| `habilita_seleccion_obligaciones` | Permite seleccionar obligaciones a pagar. |
| `habilita_whatsapp` | Permite entregar el grupo de WhatsApp autorizado. |
| `habilita_reenganche` | Permite reanudar o gestionar un flujo posterior de matrícula. |
| `habilita_solicitud_link` | Permite solicitar o completar un link de pago. |

Deshabilitar una bandera debe bloquear la operación en backend, no únicamente
ocultar el control en Alpine. Las banderas relacionadas deben respetar estas
dependencias:

- No aprobar si la configuración no habilita aprobación.
- No emitir recibo si no se habilita generación de recibo.
- No entregar WhatsApp si no se habilita WhatsApp y el pago no está aprobado.
- No cargar comprobante si la carga está deshabilitada; si además es requerido,
  no permitir la transición que exige ese comprobante.
- No solicitar link si el flujo o el método de pago no lo permiten.
- No seleccionar cuotas ignorando la obligación de matrícula cuando existe una
  obligación `MAT` pendiente.

## 2. Portal Académico

El Portal Académico comprende las pantallas `/admin` y sus operaciones de
seguridad, catálogos, ofertas, estudiantes, matrícula, pagos, caja,
calificaciones, asistencia, inventario y reportes.

### 2.1 Catálogo académico

La jerarquía funcional es:

```text
Sucursal
  -> Departamento académico
      -> Plan de estudio
          -> Versión del plan
              -> Nivel académico
                  -> Oferta académica
```

- Un departamento representa un área de formación; no representa una sucursal,
  modalidad, horario o concepto de pago.
- Una versión de plan permite conservar la estructura histórica del plan. No
  cambiar retroactivamente una versión usada por matrículas o historial.
- Los niveles tienen orden académico, nota mínima y prerrequisitos.
- Los prerrequisitos deben pertenecer a la misma versión del plan y tener un
  orden anterior al nivel que los requiere.
- Un examen de nivelación aprobado del mismo plan, con orden igual o superior,
  puede satisfacer un prerrequisito.
- Las modalidades distinguen el régimen académico (por ejemplo, Intensivo o
  Semi Intensivo) de la atención (por ejemplo, Presencial o Virtual).
- Los horarios son reutilizables como catálogo, pero su disponibilidad real se
  define en la oferta académica. Sus días se representan con las columnas
  booleanas del modelo (`lunes` a `domingo`).
- Un horario no puede tener hora final anterior a la inicial, salvo el horario
  marcado como 24 horas, y debe tener al menos un día seleccionado.

### 2.2 Periodos, ofertas y cupos

Una oferta académica es la combinación operativa de:

```text
sucursal + periodo + plan/versión + nivel + modalidad + horario
+ docente + aula + cupo + plan de cobro + grupo de WhatsApp
```

- La matrícula siempre apunta a `ofertas_academicas`, nunca directamente a un
  nivel, horario o modalidad.
- Una oferta debe estar en estado `abierto` para admitir matrícula.
- Una oferta llena no se muestra como disponible ni admite nuevas reservas.
- Los estados funcionales de la oferta son `borrador`, `abierto`, `lleno`,
  `cerrado` y `cancelado`.
- `lleno` es un estado automático: no debe asignarse manualmente desde la
  operación normal. Se produce cuando el cupo disponible llega a `0` y puede
  volver a `abierto` al liberarse cupo.
- Las transiciones manuales permitidas son: `borrador -> abierto`,
  `borrador -> cancelado`, `abierto -> cerrado`, `abierto -> cancelado`,
  `lleno -> cerrado`, `lleno -> cancelado` y `cerrado -> cancelado`.
- No se debe reabrir manualmente una oferta `cerrado -> abierto` ni permitir
  `cancelado -> abierto` como operación ordinaria.
- El cupo disponible se calcula como:

  ```text
  cupo_maximo - cupos_matriculados - cupos_reservados
  ```

- Una reserva incrementa `cupos_reservados`. Una matrícula confirmada mueve el
  cupo de reservado a matriculado. Un rechazo, cancelación o vencimiento que
  corresponda libera el cupo.
- Al llegar a cero cupos, la oferta puede pasar a `lleno`; al liberar un cupo
  puede volver a `abierto`.
- El estudiante solo debe ver ofertas del periodo abierto, de su sucursal,
  permitidas por su nivel, abiertas y con cupo disponible.
- En el Portal Académico, las pantallas operativas deben filtrar en este orden:
  `periodo -> nivel -> horario/grupo`. Un periodo cerrado permite consulta
  histórica, pero no creación ni operación académica nueva.

### 2.3 Matrícula

- Una matrícula pertenece a un estudiante y a una oferta académica; conserva
  también la sucursal de la oferta.
- No debe existir más de una matrícula activa del mismo estudiante en la misma
  oferta. Estados activos para este control: `reservada`, `en_revision` y
  `matriculado`.
- No permitir que el estudiante tenga simultáneamente un plan de estudio
  activo distinto, salvo una transición autorizada por el flujo de matrícula.
- La selección de plan en matrícula filtra niveles y ofertas disponibles; la
  oferta académica elegida determina y conserva el plan efectivo de la
  matrícula. No se guarda un plan directo en el estudiante.
- Antes de reservar o confirmar validar oferta abierta, cupo, prerrequisitos y
  conflictos de horario.
- Para reservar el siguiente nivel no basta con haber tenido matrícula previa.
  El prerrequisito solo se considera cumplido cuando el nivel anterior está
  aprobado académicamente (`historial_academico` aprobado, calificación
  aprobada real o nivelación aprobada) y, cuando aplica, también finalizado
  administrativamente.
- Finalización administrativa significa que la matrícula del nivel previo ya no
  tiene obligaciones en estado `pendiente` o `parcial`. Haber pagado solo una
  parte, o estar únicamente `matriculado`, no habilita el avance.
- La reserva crea las obligaciones de pago a partir del plan de cobro de la
  oferta. Si no hay plan o detalles activos cuando el flujo lo exige, la
  operación no debe dejar una reserva incompleta.
- Una oferta académica no debe crearse ni mantenerse operativa sin
  `plan_cobro_id`. Si el plan de cobro no está activo o no tiene detalles
  activos, la reserva debe rechazarse antes de crear matrícula, cupo u
  obligaciones.
- Las obligaciones conservan una fotografía histórica del concepto, nombre del
  cargo, monto, número de cuota, vencimiento y estado inicial.
- La combinación matrícula + número de cuota es única. Reintentar una reserva
  no duplica obligaciones ni cambia montos históricos.
- Una cancelación o retiro no elimina la matrícula, pagos, obligaciones ni
  historial: cambia estados, libera cupos y deja evidencia de la operación.
- Los cambios de horario, modalidad o sucursal se tramitan como gestión de
  matrícula aprobable. Deben conservar el historial de origen y destino, exigir
  cupo y no ejecutarse parcialmente.
- Una excepción documenta quién autorizó y por qué; no omite automáticamente
  validaciones de cupo ni aprueba pagos.

### 2.4 Flujo del Portal Académico

El flujo administrativo se usa para registrar o completar operaciones desde
las pantallas de administración. Cada paso debe validar permiso RBAC, alcance
de sucursal y la configuración activa del flujo.

```text
Seleccionar estudiante y oferta
  -> reservar cupo (reservada)
  -> revisar obligaciones y pago
  -> aprobar pago (matriculado + recibo)
  -> administrar gestiones posteriores
```

- **Reserva administrativa:** requiere `matriculas.crear` y
  `habilita_reserva_cupo`. Valida oferta abierta, cupo, duplicidad,
  prerrequisitos, plan activo y conflicto de horario. Crea la matrícula
  `reservada`, incrementa `cupos_reservados` y genera obligaciones desde el
  plan de cobro.
- **Confirmación administrativa:** requiere `matriculas.modificar` y
  `habilita_confirmacion_matricula`. Solo opera una matrícula `reservada`,
  vuelve a validar cupo, prerrequisitos y horario, y la pasa a `en_revision`.
- **Revisión de pago:** requiere el permiso de pagos correspondiente y
  `habilita_revision_contable`. Aplica a pagos que llegan con comprobante o
  por flujo de link y comprueba método, referencia, fecha, monto y obligaciones
  aplicables.
- **Registro administrativo directo:** si el flujo tiene
  `habilita_aprobacion_pago`, administración puede registrar un pago aprobado
  inmediatamente. En ese caso aplica el pago a obligaciones, confirma la
  matrícula si corresponde, mueve el cupo reservado a matriculado y genera el
  recibo si `habilita_generacion_recibo` está activo.
- **Sesión de caja obligatoria para administración:** ningún pago aprobado
  desde el Portal Académico puede registrarse o aprobarse si el usuario no
  tiene una sesión de caja `abierta` en la misma sucursal del pago. Tener una
  sesión abierta en otra sucursal no satisface la regla.
- **Efectivo en administración:** para método `EFE`, el sistema debe pedir el
  total a pagar, el `monto_recibido` y calcular el `vuelto`. El monto recibido
  no puede ser menor al total y ambos valores deben quedar persistidos para
  auditoría y consulta posterior.
- **Aprobación posterior:** requiere `pagos.aprobar` y
  `habilita_aprobacion_pago`. Se usa para pagos que están `pendiente` o
  `en_revision` y completa los mismos efectos contables y académicos del flujo.
- **Rechazo:** requiere permiso de aprobación, conserva el pago y el motivo,
  libera la reserva cuando corresponda y no entrega WhatsApp.
- **Reenganche o gestión posterior:** requiere `habilita_reenganche` y se
  tramita como gestión de matrícula; no debe editar directamente la matrícula
  histórica ni eliminar pagos o historial.

### 2.5 Flujo del Portal del Estudiante

El estudiante inicia el proceso, pero las decisiones contables y la aprobación
permanecen en el Portal Académico.

```text
Consultar oferta elegible
  -> reservar cupo (reservada)
  -> seleccionar obligaciones y registrar pago
  -> adjuntar comprobante o solicitar link
  -> revisión administrativa (en_revision)
  -> aprobación (matriculado + recibo + WhatsApp, si aplica)
```

- **Consulta de oferta:** aplica la configuración de origen
  `portal_estudiante` y solo muestra ofertas del periodo, sucursal, nivel y
  cupo permitidos.
- **Reserva online:** requiere `habilita_reserva_cupo`, valida propiedad del
  estudiante, duplicidad, prerrequisitos y conflicto de horario. La operación
  es transaccional: matrícula, cupo reservado y obligaciones se crean juntos.
- **Registro de pago:** resuelve nuevamente el flujo por concepto y método de
  pago; no confiar en la configuración que recibió la pantalla previamente.
  Solo permite obligaciones de una matrícula propia en estado permitido.
- **Selección de obligaciones:** aplica cuando
  `habilita_seleccion_obligaciones` está activo y el concepto lo permite. Una
  matrícula pendiente (`MAT`) debe pagarse antes de cuotas (`CUO`) cuando esa
  obligación existe.
- **Comprobante:** si `habilita_carga_comprobante` está activo, el estudiante
  puede adjuntar un archivo válido. Si `requiere_comprobante` está activo, el
  pago no puede avanzar a aprobación sin él.
- **Link de pago:** solo se ofrece cuando `habilita_solicitud_link` está activo
  y el método permite link. La secuencia es `solicita_link` ->
  `esperando_respuesta` -> confirmación del estudiante -> `en_revision`.
- **Aprobación:** nunca la ejecuta el estudiante. El Portal Académico valida y
  decide; al aprobar se aplican obligaciones, cupo, recibo y acceso posterior.
- **Reenganche:** solo permite retomar un flujo incompleto cuando
  `habilita_reenganche` está activo y el registro sigue siendo propiedad del
  estudiante.

### 2.6 Matriz de transiciones

| Momento | Portal | Estado/efecto | Condiciones principales |
|---|---|---|---|
| Inicio de matrícula | Académico o Estudiante | `reservada` | Oferta abierta, cupo, flujo habilitado, sin duplicidad. |
| Confirmación administrativa | Académico | `en_revision` | Matrícula reservada, permiso y confirmación habilitada. |
| Pago con comprobante | Estudiante o Académico | `en_revision` | Pago propio o dentro del alcance, comprobante si es requerido. |
| Solicitud de link | Estudiante o Académico | `solicita_link` | Flujo y método permiten link; no duplicar solicitud activa. |
| Link preparado | Académico | `esperando_respuesta` | URL válida y flujo de link habilitado. |
| Confirmación del link | Estudiante | `en_revision` | Solo el propietario del pago puede confirmar. |
| Aprobación | Académico | `aprobado` / matrícula `matriculado` | Permiso, revisión, obligaciones y recibo según configuración. |
| Rechazo | Académico | `rechazado` | Motivo obligatorio; liberar cupo cuando corresponda. |
| Anulación posterior | Académico | Pago `cancelado`, recibo `anulado` | Motivo y permiso; recalcular obligaciones sin borrar evidencia. |

La configuración controla si cada transición está habilitada, pero no elimina
las validaciones de propiedad, alcance, estado, cupo, saldo, auditoría o
integridad transaccional.

### 2.7 Planes de cobro y obligaciones

- Un plan de cobro se asigna a la oferta, no directamente a un nivel aislado.
- Debe soportar matrícula más una o varias cuotas sin crear nuevos conceptos
  contables por cada cuota.
- `MAT` representa matrícula y `CUO` representa cuotas; el detalle operativo es
  `numero_cuota`, `nombre_cargo`, `monto`, `fecha_vencimiento` y `estado`.
- Las obligaciones pueden estar `pendiente` o `parcial` mientras tengan saldo.
  El saldo es `monto - monto_pagado`.
- Un pago puede aplicarse a una o varias obligaciones. El pago total no es un
  concepto contable nuevo.
- Los montos y vencimientos deben salir de `planes_cobro` y
  `detalle_plan_cobro`; no quemarlos en controladores o vistas.

### 2.8 Pagos y recibos

- Un pago administrativo requiere estudiante, concepto, método y monto; la
  matrícula es obligatoria cuando el concepto se aplica a una matrícula.
- Los estados de pago usados por el flujo son `pendiente`, `en_revision`,
  `solicita_link`, `aprobado`, `rechazado` y `cancelado`.
- El flujo de link usa la secuencia `solicita_link -> esperando_respuesta ->
  en_revision -> aprobado`. Cargar el enlace no debe mover el pago
  directamente a revisión: primero debe quedar pendiente de confirmación del
  estudiante.
- Un pago en revisión necesita comprobante cuando la configuración del flujo lo
  exige. Contabilidad puede aprobarlo o rechazarlo con motivo.
- Al aprobar un pago se aplican sus importes a obligaciones pendientes, se
  actualizan saldos, se confirma la matrícula cuando corresponde, se actualiza
  el cupo y se genera o asocia un recibo.
- Al rechazar un pago se conserva la evidencia, se registra el motivo y se
  libera la reserva de cupo cuando corresponda.
- Un pago aprobado no se borra. Su anulación conserva pago, aplicaciones y
  recibo; cambia el pago a `cancelado`, anula el recibo y recalcula obligaciones
  usando solo aplicaciones de pagos aprobados.
- Para depósitos y transferencias, la referencia y fecha deben poder detectarse
  contra pagos de otro estudiante para alertar posibles duplicados. La alerta
  no debe convertirse silenciosamente en aprobación.
- Todo pago por depósito o transferencia debe indicar una cuenta bancaria activa
  de la institución; el pago conserva esa cuenta para su revisión y auditoría.
- Todo pago en efectivo registrado desde administración debe conservar también
  `monto_recibido` y `vuelto` cuando aplique.
- Un recibo emitido no se edita directamente. Las correcciones usan anulación,
  reversión o ajuste autorizado.
- Los únicos estados de recibo son `emitido`, `anulado` y `reversado`.
  `veces_reimpreso` es un contador separado y no un estado.
- Reimprimir un recibo incrementa el contador y registra usuario y fecha de la
  operación; un recibo anulado no se reimprime como válido.

### 2.9 Caja e inventario

- Un cajero solo puede tener una sesión `abierta` o `reabierta` por sucursal.
- El cierre agrupa recibos emitidos por sucursal, cajero, periodo de sesión,
  concepto y método de pago; conserva monto calculado, monto declarado y
  diferencia.
- Reabrir caja conserva una fotografía del cierre anterior y sus detalles. No
  modifica ni elimina recibos emitidos.
- El inventario se limita por sucursal. Un ajuste requiere cantidad distinta de
  cero y motivo, y nunca puede dejar existencia negativa.
- Una venta de libro valida existencia y sucursal de la matrícula, crea el pago
  aprobado con concepto `VLI`, emite recibo, descuenta existencia y registra el
  movimiento en una sola transacción.

### 2.10 Asistencia, calificaciones e historial

- Solo se trabaja con estudiantes cuya matrícula está `matriculado` en la
  oferta consultada.
- Una asistencia identifica matrícula, oferta y fecha de clase. Solo puede
  existir una por matrícula y fecha; un nuevo registro corrige el anterior.
- Los estados de asistencia son `presente`, `falta`, `justificada` y
  `tardanza`. `cuenta_como_falta` y el motivo explican el tratamiento.
- La aprobación académica combina nota, faltas y, si la regla lo configura,
  ausencia de saldo pendiente:

  ```text
  nota_final >= nota_minima
  y faltas <= faltas_maximas_permitidas
  y saldo pendiente = 0 cuando aplica
  ```

- La regla más específica puede ser general, por nivel, por modalidad o por
  nivel y modalidad. Los valores iniciales documentados son 80 puntos, máximo
  7 faltas para Intensivo y máximo 3 para Semi Intensivo; deben permanecer
  parametrizables.
- Registrar o corregir una calificación actualiza calificación e historial
  académico en la misma transacción y conserva el motivo de corrección.
- Un docente solo consulta y modifica la actividad de sus propias ofertas.

### 2.11 Reportes y documentos

- Todo reporte debe respetar el alcance del usuario y, cuando aplique, requerir
  rango de fechas. Los filtros visibles no amplían el alcance autorizado.
- Los filtros académicos se resuelven cruzando matrícula y oferta; no se debe
  inferir el nivel o grupo desde el concepto de pago.
- La consulta paginada y la exportación deben usar los mismos filtros y mostrar
  los totales y filtros aplicados.
- Los reportes financieros distinguen concepto y método de pago; para reportar
  por nivel, horario o docente deben cruzar `pagos -> matriculas ->
  ofertas_academicas`.
- Recibos, constancias y reportes oficiales deben conservar la información
  histórica y su auditoría de emisión o reimpresión.

## 3. Portal del Estudiante

El Portal del Estudiante es autoservicio. Solo puede consultar o modificar
registros asociados al estudiante identificado por `auth.estudiante`.

### 3.1 Registro, activación y sesión

- Primer ingreso y activación de un estudiante existente son flujos distintos.
- No duplicar estudiantes existentes. Buscar por código, cuenta o identificador
  funcional definido por el negocio.
- Antes de confirmar identidad, correo y teléfono deben mostrarse enmascarados.
- La cuenta del estudiante debe estar activa para iniciar sesión; una cuenta
  inactiva no puede operar el portal.
- El portal del estudiante no puede usar tokens Sanctum ni rutas administrativas.
- Al consultar recursos por ID, siempre restringir la consulta al estudiante del
  token. Nunca aceptar que un ID cambie esa propiedad.

### 3.2 Ofertas y reserva online

- `mis-ofertas` solo devuelve ofertas abiertas, del periodo activo, de la
  sucursal del estudiante, con cupo y nivel permitido.
- No mostrar niveles ya aprobados ni otro nivel ya matriculado en el mismo
  periodo cuando la regla de avance lo impida.
- Validar prerrequisitos y conflicto de horario antes de reservar, aunque la
  oferta haya sido mostrada previamente.
- La reserva crea una matrícula `reservada`, incrementa cupo reservado y genera
  obligaciones desde el plan de cobro de la oferta.
- No permitir reserva duplicada en la misma oferta ni en el mismo nivel y
  periodo. Una matrícula rechazada o cancelada puede reutilizarse solo bajo las
  condiciones transaccionales del flujo y sin pagos históricos que deban
  conservarse.
- El estudiante debe ver el detalle funcional de nivel, régimen, modalidad,
  horario, docente, periodo, cupo y monto, nunca IDs internos.

### 3.3 Pago y comprobante

- El estudiante solo puede pagar matrículas propias en estado `reservada` o
  `matriculado` y obligaciones pendientes de esa matrícula.
- Si existe una obligación `MAT` pendiente, debe incluirse antes de permitir el
  pago de cuotas, salvo que una configuración explícita del flujo indique otra
  cosa.
- No permitir pagar una obligación que no pertenezca a la matrícula indicada ni
  a la cuenta del estudiante autenticado.
- Validar método de pago, referencia, fecha y reglas específicas del método.
- Para `DEP` y `TRA`, el portal y la administración deben exigir referencia,
  fecha de pago y una cuenta bancaria activa de la institución.
- Evitar solicitudes duplicadas para las mismas obligaciones cuando ya exista
  una solicitud activa (`solicita_link`, `esperando_respuesta` o `en_revision`).
- Los comprobantes aceptan JPG, JPEG, PNG o PDF, con máximo de 10 MB, según la
  validación actual. Al cargarlos, el pago pasa a `en_revision`.
- El estudiante puede eliminar o corregir un pago propio únicamente mientras
  el flujo lo permita; nunca puede borrar pagos aprobados ni recibos emitidos.
- En producción, la eliminación de pagos desde el portal del estudiante puede
  deshabilitarse completamente por política operativa, aunque el flujo exista
  en otros entornos de validación.
- La aprobación la realiza el Portal Académico. El estudiante no puede cambiar
  estados contables, aplicar pagos manualmente ni emitir recibos.

### 3.4 Consulta posterior al pago

- El estudiante solo ve sus matrículas, obligaciones, pagos, comprobantes,
  recibos, calificaciones, historial y certificados.
- Un recibo disponible debe pertenecer al estudiante autenticado y estar
  `emitido` o ser una reimpresión válida. Los recibos anulados no se presentan
  como comprobantes vigentes.
- El recibo debe mostrar información funcional e histórica: número, fecha,
  alumno, concepto, periodo, nivel, horario, importe y método de pago.
- El enlace de WhatsApp se entrega únicamente cuando el pago requerido esté
  aprobado y la configuración del flujo lo habilite. El ingreso automático al
  grupo no forma parte de esta regla.
- El nivel actual se obtiene de matrícula e historial académico, no de un dato
  duplicado en el perfil del estudiante.
- La emisión de certificados debe basarse en historial o calificación válida,
  registrar la emisión y no permitir que el estudiante fabrique o altere sus
  datos académicos.

## 4. Reglas de implementación

- Las reglas críticas deben vivir en controlador, servicio, policy o consulta
  protegida; el frontend no es una barrera de seguridad.
- Usar transacciones y `lockForUpdate()` cuando se modifiquen simultáneamente
  matrícula, cupos, obligaciones, pagos, recibos o inventario.
- Para módulos administrativos nuevos, registrar permisos en `config/rbac.php`,
  sincronizar `SeguridadRbacSeeder` y usar los macros protegidos de rutas.
- En IIS/SmarterASP, las rutas de actualización deben aceptar `PUT`, `PATCH` y
  `POST`; las de eliminación deben aceptar `DELETE` y `POST`.
- Las pruebas de cada módulo deben cubrir como mínimo: operación permitida,
  permiso denegado, alcance fuera de sucursal o propietario y propiedad del
  registro en el Portal del Estudiante.

## 5. Fuentes relacionadas

- `docs/PATRON_IMPLEMENTACION_RBAC.md`: permisos y alcance administrativo.
- `docs/ARQUITECTURA_RBAC.md`: modelo de autorización.
- `docs/OBLIGACIONES_PAGO_MATRICULA.md`: generación histórica de obligaciones.
- `docs/API_ACADEMICO.md`: asistencia, calificaciones y prerrequisitos.
- `docs/API_PAGOS.md`: revisión, aprobación y anulación de pagos.
- `docs/API_GESTIONES_MATRICULA.md`: cambios, retiros y traslados.
- `docs/API_CAJA.md`: sesiones y cierres de caja.
- `docs/API_INVENTARIO_LIBROS.md`: inventario y ventas.
- `docs/API_PORTAL_ESTUDIANTE_PAGOS.md`: pagos y recibos del estudiante.
