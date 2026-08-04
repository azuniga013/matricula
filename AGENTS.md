# AGENTS.md — Plataforma Cursos San Vicente de Paúl

## 1. Propósito del proyecto

Este repositorio corresponde a la plataforma centralizada de **Cursos San Vicente de Paúl**.

El sistema debe administrar:

- Matrícula online.
- Estudiantes de primer ingreso.
- Estudiantes existentes y activación de acceso.
- Sucursales.
- Departamentos académicos.
- Planes de estudio.
- Niveles académicos.
- Modalidades.
- Periodos académicos.
- Horarios.
- Oferta académica por periodo.
- Cupos.
- Pagos.
- Planes de cobro.
- Matrícula y cuotas.
- Comprobantes de pago.
- Recibos de caja.
- Cierre de cajero.
- Calificaciones.
- Historial académico.
- Libros e inventario.
- Grupos de WhatsApp.
- Reportes académicos y financieros.

El sistema será centralizado, pero debe permitir operación separada por sucursal.

Sucursales iniciales:

- San Pedro Sula.
- Tegucigalpa.

---

## 2. Idioma y convenciones

### Base de datos

Usar nombres de tablas y campos en español.

Ejemplos correctos:

- `sucursales`
- `bitacora_peticiones`
- `departamentos_academicos`
- `planes_estudio`
- `niveles_academicos`
- `ofertas_academicas`
- `matriculas`
- `pagos`
- `recibos_caja`
- `detalle_recibo_caja`
- `planes_cobro`
- `detalle_plan_cobro`
- `obligaciones_pago_estudiante`

No crear tablas con nombres en inglés si ya existe un nombre funcional en español.

### Código

El código puede usar convenciones propias del framework, pero los conceptos del negocio deben mantenerse en español cuando representen entidades del dominio.

Ejemplos:

- Modelo: `Sucursal`
- Modelo: `DepartamentoAcademico`
- Modelo: `OfertaAcademica`
- Modelo: `Matricula`
- Modelo: `Pago`
- Modelo: `ReciboCaja`

### Documentación

La documentación técnica y comentarios funcionales deben escribirse en español.

---

## 3. Arquitectura actual

La aplicación es un proyecto Laravel 11 monolítico (sin separación en directorios `backend/` o `frontend/`).

### Backend (API + Blade)

- Laravel 11 con rutas API en `routes/api.php` y rutas web en `routes/web.php`.
- Autenticación administrativa con Sanctum Bearer tokens.
- Autenticación de estudiante con tokens SHA-256 personalizados en `accesos_estudiante`.
- Migraciones, modelos, controladores, seeders, pruebas automatizadas.
- No existen Form Requests ni JSON Resources; la validación se hace inline en controladores.

### Web administrativa (Blade + Alpine.js + Tailwind CSS)

- Las pantallas administrativas son Blade server-rendered con Alpine.js para interactividad.
- Comunica con la API vía `window.api` (objeto global definido en `resources/js/app.js`).
- En el panel administrativo, crear y publicar `window.api` antes de ejecutar `Alpine.start()`. Los componentes con `x-init` validan el token al iniciar y se redirigirán falsamente al login si Alpine se inicia antes de que la API global exista.
- El login administrativo debe obtener el token Sanctum desde `/api/v1/login`, guardarlo en `localStorage` y el dashboard debe validarlo mediante `/api/v1/me`. No depender de `auth()->user()` ni de una sesión web paralela para decidir si el panel puede abrirse.
- El contador de vencimiento solo debe expulsar al usuario cuando exista una fecha de vencimiento válida y esta haya pasado; una fecha ausente nunca debe interpretarse como sesión vencida.
- El filtro de permisos se aplica en frontend ocultando/deshabilitando menús y acciones no autorizadas.
- No duplicar reglas críticas de negocio en frontend.
- Debe respetar roles, permisos y sucursales asignadas.
- Debe ser responsive y funcionar en navegadores de escritorio y navegadores móviles.
- Las pantallas, formularios, tablas y acciones críticas deben ser utilizables con pantalla táctil, sin depender exclusivamente de hover, teclado o resoluciones de escritorio.

### Diseño visual de pantallas

Cada pantalla debe diseñarse como producto profesional, no solo como formulario funcional. Aplicar jerarquía visual clara entre marca, propósito, acción principal y mensajes; paleta institucional consistente de azul profundo, verde académico y acento dorado; tipografía, espaciado, contraste y estados de interacción cuidados.

El diseño debe ser responsive desde móvil, con controles táctiles cómodos, formularios guiados, errores descriptivos y una sensación de confianza. Tablas, filtros, tarjetas y paneles deben mantener densidad adecuada para administración. Administración y Portal del Estudiante deben diferenciarse visualmente sin perder coherencia de marca.

### Regla prioritaria de visualización de identificadores

Los IDs internos son técnicos y no deben mostrarse en pantallas, consultas, listas, reportes visuales ni detalles. Toda entidad de catálogo o configuración se presenta mediante `codigo` y, cuando aporte contexto, `nombre` con el formato `CÓDIGO · Nombre`.

Las relaciones operativas deben resolverse visualmente a su código institucional o a una descripción funcional (por ejemplo, estudiante, oferta, recibo o método de pago). Los IDs pueden enviarse internamente en formularios, URLs y APIs, pero nunca exponerse como referencia visible al usuario final. Esta regla es prioritaria para todo componente nuevo o modificado.

### Regla de visualización de versiones de plan de estudio

En toda pantalla, grid, filtro, formulario o reporte donde se muestre una versión de plan de estudio, debe presentarse con el formato:

```text
{NOMBRE_DEL_PLAN} · V{NUMERO_VERSION}
```

Ejemplo: `Intensivo · V1`, `Semi Intensivo · V2`. No usar el código del plan ni la palabra "Versión" completa; usar siempre la abreviatura `V` mayúscula seguida del número.

### Regla de visualización de recibos

En toda pantalla, grid, filtro, formulario o reporte donde se muestre un recibo de caja, el campo `estado` debe representar únicamente el estado funcional real del recibo:

- `emitido`
- `anulado`
- `reversado`

La cantidad de reimpresiones debe mostrarse siempre en una columna o campo aparte, usando `veces_reimpreso` o su etiqueta funcional equivalente. No debe mostrarse `Finalizado` como estado de recibo ni mezclar la reimpresión con el estado principal.

### Portal del estudiante

Debe consumir la misma API del backend.

Debe permitir:

- Primer ingreso.
- Activación de estudiante existente.
- Login.
- Consulta de nivel actual.
- Matrícula online.
- Selección de horario disponible.
- Carga de comprobante.
- Consulta de pagos.
- Descarga de recibos.
- Visualización del link de WhatsApp cuando el pago esté aprobado.

El sistema debe tener dos pantallas de inicio de sesión separadas:

- Acceso administrativo: para `usuarios`, con RBAC, sucursales, docente y supervisor.
- Acceso de estudiante: para `accesos_estudiante`, limitado a matrícula, pagos, comprobantes, recibos, nivel actual y WhatsApp autorizado.

Ambas consumen la misma API central, pero deben usar autenticación, tokens, rutas y políticas separadas. Un estudiante nunca obtiene permisos administrativos por su acceso; un usuario administrativo no usa las rutas privadas del estudiante salvo autorización funcional explícita.

### App móvil

Preferencia:

- React Native con Expo.
- Debe consumir la misma API central.
- No debe tener base de datos de negocio independiente.
- Puede tener almacenamiento local solo para sesión, caché o archivos temporales.

---

## 4. Reglas críticas del negocio

Estas reglas no deben cambiarse sin autorización.

### 4.1 Sistema centralizado multi-sucursal

El sistema tendrá una sola base de datos centralizada.

Cada operación académica, financiera y administrativa debe poder identificarse por sucursal cuando aplique.

Tablas que deben tener relación con `sucursales`:

- `estudiantes`
- `ofertas_academicas`
- `matriculas`
- `pagos`
- `recibos_caja`
- `sesiones_caja`
- `aulas`
- `grupos_whatsapp`
- `inventario_libros`

Los usuarios administrativos pueden tener acceso a una o varias sucursales mediante `usuario_sucursales`.

---

### 4.2 Departamento académico

El concepto **Departamento** del sistema anterior debe normalizarse como **Departamento Académico**.

Un departamento académico representa únicamente un área de formación.

Ejemplos:

- Inglés.
- Computación.
- Alemán.
- Francés.
- Italiano.
- Diplomados.

No usar departamento académico para representar:

- Sucursal.
- Modalidad.
- Horario.
- Concepto de pago.
- Servicios especiales.

Relación esperada:

```text
departamento_academico
    -> plan_estudio
        -> nivel_academico
```

---

### 4.3 Planes de estudio y niveles

El sistema debe manejar planes de estudio y niveles académicos.

La institución confirmó los siguientes grupos de planes/programas:

- Intensivo.
- Semi Intensivo.
- Infantil Intensivo.
- Infantil Semi Intensivo.
- Idiomas.
- Diplomados.

Interpretación de diseño:

- Intensivo y Semi Intensivo deben manejarse como modalidad académica o régimen de estudio.
- Presencial y Virtual deben manejarse como modalidad de atención.
- Inglés, Computación, Alemán, Francés e Italiano deben manejarse como departamentos académicos.
- Los niveles deben tener orden académico y nota mínima.
- Los prerrequisitos de un nivel solo pueden seleccionarse dentro de la misma `version_plan_estudio_id` y deben tener `orden` menor que el nivel actual.
- Al cambiar la versión del plan en cualquier formulario o pantalla, deben limpiarse los prerrequisitos ya seleccionados para evitar validaciones cruzadas.

El estudiante avanza al siguiente nivel si:

- Aprueba el nivel anterior.
- Cumple la nota mínima.
- Cumple la regla de faltas.
- No tiene saldo pendiente, si la regla de bloqueo aplica.

---

### 4.4 Nota mínima y faltas

Nota mínima general:

```text
80%
```

Reglas de faltas:

```text
Intensivo: menos de 8 faltas
Semi Intensivo: menos de 4 faltas
```

Interpretación técnica:

- Intensivo: máximo permitido = 7 faltas.
- Semi Intensivo: máximo permitido = 3 faltas.

La aprobación debe considerar nota y asistencia.

```text
aprobado = nota_final >= nota_minima_aprobar
           y cantidad_faltas <= faltas_maximas_permitidas
```

---

### 4.5 Primer ingreso y examen de nivelación

Un estudiante de primer ingreso normalmente inicia en Phonics o nivel inicial.

Si ya tiene conocimiento previo, puede realizar examen de nivelación.

El sistema debe permitir:

- Registrar estudiante de primer ingreso.
- Generar usuario y contraseña.
- Registrar examen de nivelación.
- Autorizar nivel de ingreso distinto al inicial.
- Matricular por excepción administrativa.
- Guardar usuario, fecha y motivo de autorización.

---

### 4.6 Estudiante existente

Si el estudiante ya existe en la base de alumnos:

- No debe duplicarse.
- Debe buscarse por código de alumno, cuenta o identificador definido.
- El sistema debe mostrar correo y teléfono enmascarados.
- Si reconoce los datos, se envían credenciales.
- Si no reconoce los datos, debe crearse solicitud de actualización de datos.

No mostrar correo o teléfono completos sin validación.

Ejemplos:

```text
ad******@gmail.com
****-**45
```

### 4.6.1 Módulo de estudiantes

El módulo administrativo de Estudiantes debe ser una ficha integral del alumno. Debe permitir consultar, según permisos, datos personales, matrículas actuales e históricas, pagos, obligaciones pendientes, comprobantes, recibos, calificaciones, asistencias, historial académico, cambios de horario/retiros y constancias emitidas.

No duplicar estos datos dentro de `estudiantes`: cada sección consulta su tabla de origen mediante la relación del estudiante. Las constancias deben emitirse desde información histórica validada y conservar una bitácora de emisión, tipo de constancia, usuario, fecha y documento generado.

---

### 4.7 Matrícula

La matrícula no debe hacerse directamente contra el nivel ni contra el horario.

La matrícula debe hacerse contra:

```text
ofertas_academicas
```

La oferta académica representa:

```text
Sucursal
+ Periodo académico
+ Plan de estudio
+ Nivel académico
+ Modalidad
+ Horario
+ Docente
+ Aula
+ Cupo
+ Plan de cobro
+ Grupo de WhatsApp
```

El estudiante solo debe ver ofertas académicas:

- Del periodo abierto.
- De la sucursal seleccionada o asignada.
- Del nivel permitido.
- De la modalidad seleccionada.
- En estado abierto.
- Con cupo disponible.

Cálculo de cupo:

```text
cupos_disponibles = cupo_maximo - cupos_matriculados - cupos_reservados
```

---

### 4.8 Cupos

Cupo máximo inicial:

```text
25 estudiantes
```

Aunque actualmente aplica para todos los grupos, debe quedar parametrizable en `ofertas_academicas`.

Reglas:

- Si el grupo llega al cupo máximo, no debe mostrarse para matrícula.
- Si el estudiante inicia matrícula, se puede reservar cupo temporal.
- Si el pago se aprueba, el cupo pasa a matriculado.
- Si el pago se rechaza, cancela o vence, el cupo se libera.
- Si la Referencia de Pago y fecha  ambas estan  duplicada al registrar el  pago. 
  Si esta duplicada enviar correo al los destinarios   antalma61@hotmail.com,  kcontreras1995@hotmail.com


### 4.9 Horarios

Los horarios cambian cada periodo.

Por eso, el catálogo `horarios` debe ser reutilizable, pero la disponibilidad para matrícula debe definirse en `ofertas_academicas`.

No mostrar todos los horarios al estudiante.

Mostrar únicamente horarios asociados a ofertas académicas abiertas y con cupo disponible.

### 4.9.1 Filtros académicos globales

Fuera de la pantalla de creación y mantenimiento de `ofertas_academicas`, toda pantalla académica, de matrícula, caja, pagos, asistencia, calificaciones, cupos y reportes que consulte o procese información de estudiantes debe seguir este orden de filtro:

```text
Período académico → Nivel académico → Horario / Grupo académico
```

El período abierto actual se precarga por defecto y puede cambiarse para consulta histórica. Los niveles solo se muestran si tienen grupos académicos registrados para el período seleccionado; los horarios/grupos solo se muestran si pertenecen al período y nivel seleccionados. Un período cerrado permite consulta, pero no creación, modificación, matrícula, asistencia ni calificación.

La pantalla de Grupos académicos es la excepción: allí se crean las relaciones de período, nivel, horario, docente, aula, cupo y plan de cobro, por lo que no debe depender de filtros previos.

---

### 4.10 WhatsApp

Cada grupo tendrá su propio link de WhatsApp.

Los grupos cambian por periodo.

El link de WhatsApp debe asociarse preferiblemente a `ofertas_academicas`, porque un mismo nivel puede existir en varias sucursales, horarios, modalidades o grupos.

El estudiante solo debe recibir el link de WhatsApp cuando el pago esté aprobado.

El ingreso automático al grupo de WhatsApp es mejora futura y no debe implementarse como obligación de la primera versión, salvo autorización técnica explícita.

---

### 4.11 Conceptos de pago

Mantener conceptos contables limpios.

No crear conceptos contables por nivel, horario, modalidad, sucursal o número de cuota.

Conceptos esperados:

- `MAT` — Matrícula.
- `CUO` — Cuota.
- `PMA` — Pre-matrícula.
- `PEX` — Examen de nivelación.
- `VLI` — Venta de libro.
- `CHO` — Cambio de horario.
- `CAU` — Cargo por mora.
- `RGO` — Recargo por cuota vencida.
- `EOT` — Otros servicios en educación.

La información académica debe venir desde:

```text
pago -> matricula -> oferta_academica -> nivel / horario / docente / sucursal
```

No desde el concepto contable.

---

### 4.12 Planes de cobro y cuotas

Un nivel puede tener matrícula más una o muchas cuotas.

Actualmente existe:

```text
Matrícula + 1 cuota final
```

El nuevo diseño debe permitir:

```text
Matrícula + N cuotas
```

Sin crear nuevos conceptos contables.

Usar:

- `planes_cobro`
- `detalle_plan_cobro`
- `obligaciones_pago_estudiante`
- `aplicaciones_pago`

Regla:

```text
MAT = Matrícula
CUO = Cualquier cuota
```

El número de cuota debe manejarse como detalle operativo:

- `numero_cuota`
- `nombre_cargo`
- `fecha_vencimiento`
- `monto`
- `estado`

Ejemplo:

| Concepto | Número cuota | Nombre cargo | Monto |
|---|---:|---|---:|
| MAT | 0 | Matrícula | 1200.00 |
| CUO | 1 | Cuota 1 | 1100.00 |

Un pago puede aplicarse a una o varias obligaciones.

Un pago completo no es un concepto contable nuevo; es un pago que cubre matrícula más cuota o cuotas.

---

### 4.13 Montos confirmados

Montos base actuales:

| Modalidad | Pago completo | Matrícula / 1 pago | Cuota / 2 pago |
|---|---:|---:|---:|
| Intensivo | 2300.00 | 1200.00 | 1100.00 |
| Semi Intensivo | 1300.00 | 600.00 | 700.00 |

Estos valores deben parametrizarse en planes de cobro. No deben quedar quemados en código.

---

### 4.14 Cuenta bancaria y links de pago

Cuenta bancaria oficial:

```text
BAC: 743806641
```

El sistema debe permitir administrar varios links de pago con montos diferentes.

Reglas:

- Un link de pago puede tener monto.
- Un link de pago puede tener vigencia.
- Un link de pago puede tener cantidad máxima de usos.
- Un link agotado debe poder reemplazarse.
- El link debe asociarse a concepto, obligación de pago o configuración definida.

---

### 4.15 Pagos y comprobantes

El estudiante puede pagar por:

- Link de pago.
- Depósito.
- Transferencia bancaria.

El sistema debe permitir subir comprobantes en:

- JPG.
- PNG.
- PDF.

Contabilidad podrá:

- Aprobar.
- Rechazar.
- Solicitar nuevo comprobante.

#### Flujo de pago por link

```text
Estudiante solicita link → solicita_link
Admin llena URL           → esperando_respuesta
Estudiante confirma       → en_revision
Admin aprueba/rechaza     → aprobado / rechazado
```

El grid de pagos del portal del estudiante debe mostrar:
- Badge de estado con color diferenciado por estado.
- Botón "Ya completé el pago" visible solo cuando estado = `esperando_respuesta`.
- Enlace `<a>` directo al `link_pago_url` en la columna Link.
- Link de WhatsApp como botón verde cuando el pago esté aprobado y la oferta tenga grupo WhatsApp configurado.

Si el pago es aprobado:

- Se confirma la matrícula, si corresponde.
- Se actualiza cupo.
- Se genera recibo.
- Se habilita link de WhatsApp.

Si el pago es rechazado:

- Se libera cupo reservado.
- No se entrega link de WhatsApp.

---

### 4.16 Recibos de caja

Todo pago aprobado debe generar o estar asociado a un recibo de caja.

El recibo debe conservar información histórica:

- Número de recibo.
- Fecha.
- Hora.
- Cajero.
- Alumno.
- Concepto.
- Año.
- Periodo.
- Nivel.
- Horario.
- Valor.
- Forma de pago.
- Documento de referencia.

Un recibo emitido no debe modificarse directamente.

Correcciones deben hacerse por:

- Anulación.
- Reversión.
- Ajuste autorizado.

---

### 4.17 Cierre de caja

El cierre de caja debe agrupar por:

- Sucursal.
- Fecha.
- Cajero.
- Concepto de pago.
- Forma de pago.

Formas de pago esperadas:

- Efectivo.
- Depósito.
- Transferencia.
- Tarjeta.
- Link de pago.
- Cheque, si aplica en migración o caja.

El reporte de caja debe mantenerse contable y limpio.

No agrupar caja por nivel como concepto contable.

Si se requiere reporte financiero por nivel, debe obtenerse cruzando:

```text
pagos -> matriculas -> ofertas_academicas -> niveles_academicos
```

---

### 4.18 Calificaciones e historial académico

Las calificaciones se registran por matrícula/oferta académica.

No registrar calificación solo contra código de alumno.

Debe existir relación con:

- Estudiante.
- Matrícula.
- Oferta académica.
- Nivel.
- Periodo.
- Horario.
- Docente.

El portal del estudiante puede mostrar únicamente el nivel actual, según requerimiento funcional.

Pero la base de datos debe conservar historial completo para auditoría, avance académico y validaciones.

---

### 4.19 Libros e inventario

Los libros se relacionan con niveles académicos.

El inventario debe manejarse por sucursal.

La venta de libro debe usar el concepto:

```text
VLI = Venta de libro
```

Al vender un libro:

- Validar existencia.
- Emitir recibo.
- Descontar inventario.
- Registrar movimiento de inventario.

No manejar existencia global sin sucursal.

---

### 4.20 Códigos de catálogos y nomenclaturas

Todo catálogo o entidad de configuración previa debe tener:

- `id` como llave primaria estable.
- `codigo` único, obligatorio y editable.

El código es la nomenclatura que se utilizará en reportes. No debe reutilizarse, aunque puede ser renombrado según la necesidad administrativa. Su longitud debe soportar la nomenclatura definida; usar hasta 50 caracteres como capacidad estándar.

La generación automática se configura en `nomenclaturas_codigos`. Esta tabla aplica únicamente a catálogos y configuraciones previas, nunca a transacciones como matrículas, pagos, recibos o asistencias.

Campos funcionales mínimos:

- Entidad destino.
- Formato, por ejemplo `BAN-{SECUENCIA:6}`.
- Longitud de secuencia.
- Secuencia actual.
- Estado.

La secuencia debe incrementarse dentro de una transacción con bloqueo para no generar códigos repetidos. Si administración proporciona un código manual, debe respetarse y validarse su unicidad.

---

### 4.21 Gestiones de matrícula

No crear una tabla distinta por cada operación sobre una matrícula. Usar:

- `tipos_gestion_matricula`
- `gestiones_matricula`

Tipos iniciales:

- Cambio de horario.
- Retiro.
- Cancelación.
- Cambio de modalidad.
- Traslado de sucursal.
- Excepción de matrícula.

Una gestión debe conservar matrícula afectada, estado, motivo, oferta destino cuando aplique, decisión financiera, pago asociado, datos antes/después y trazabilidad de solicitud, aprobación, rechazo, ejecución o cancelación.

La matrícula no se elimina. Un retiro debe conservar historial académico, pagos y auditoría. Un cambio de horario debe mover al estudiante únicamente a una oferta académica compatible y con cupo disponible.

---

### 4.22 Asistencia y acceso del docente

La asistencia se registra en `asistencias_estudiante`, por matrícula y fecha de clase. No permitir más de un registro para el mismo estudiante/matrícula y fecha.

Estados funcionales esperados:

- `presente`
- `falta`
- `justificada`
- `tardanza`

Cada registro debe indicar si cuenta como falta para aprobación. La cantidad de faltas se calcula desde asistencia y no debe depender de un valor ingresado manualmente en calificaciones.

La pantalla/transacción “Pasar lista” es para el docente. Solo puede mostrar las ofertas académicas asignadas al docente autenticado y los estudiantes con matrícula activa de esa oferta. No debe permitir al docente modificar matrícula, horario, pagos ni datos financieros.

Un usuario con rol `DOCENTE` debe tener obligatoriamente `docente_id`, relacionado de forma única con `docentes`. Un docente solo puede estar asociado a un usuario.

---

### 4.23 Supervisor de docentes

Debe existir el rol `SUPERVISOR`. Tiene acceso global a todas las sucursales y a la gestión académica, incluyendo docentes, grupos, asistencias, matrículas, calificaciones, historial, pagos y reportes.

El supervisor no se limita a un `docente_id` ni a sucursales asignadas. Las políticas y middleware deben respetar este alcance global.

---

### 4.24 Origen del monto por concepto de pago

`conceptos_pago` debe definir cómo se obtiene el monto:

- `fijo`: usa `monto_fijo` configurado.
- `manual`: el cajero ingresa el monto, respetando límites y autorización cuando corresponda.
- `por_oferta`: se obtiene de la obligación/plan de cobro de la oferta académica.
- `por_inventario`: se obtiene del libro o inventario vendido.

Campos mínimos: `tipo_monto`, `monto_fijo`, `monto_minimo`, `monto_maximo` y `requiere_autorizacion_monto`.

`MAT`, `CUO` y `PMA` usan `por_oferta`; `VLI` usa `por_inventario`; `PEX` puede ser fijo; los demás conceptos se parametrizan según la política administrativa.

---

### 4.25 Monitor de cupos por período

Debe existir una consulta de monitor de cupos por período académico, con filtros por período y sucursal. No requiere una tabla transaccional adicional; calcula información en tiempo real desde `ofertas_academicas`.

Debe mostrar sucursal, plan, nivel, modalidad, horario, docente, cupo máximo, matriculados, reservados, disponibles y estado de oferta.

Estados visuales sugeridos:

- Verde: matrícula abierta y cupos disponibles.
- Azul: oferta que permite recibir cambio de horario compatible.
- Amarillo: pocos cupos disponibles.
- Rojo: sin cupos o matrícula cerrada.
- Gris: oferta cancelada.

`ofertas_academicas` debe permitir parametrizar si acepta cambios de horario. El monitor debe tener actualización automática configurable mediante el parámetro `MONITOR_CUPOS_REFRESCO_SEGUNDOS`, con valor inicial de 300 segundos. La actualización consulta la API; no requiere tarea programada.

---

### 4.26 Seguridad parametrizable: RBAC

El sistema debe utilizar seguridad parametrizable basada en roles y permisos (RBAC). No se permiten decisiones de autorización fijas como `if rol == "ADMIN"`.

Los roles, permisos, módulos y alcances se administran desde interfaz administrativa. Un usuario puede tener uno o varios roles. La autorización efectiva debe provenir de `usuario_roles` y `rol_permisos`; `usuarios.rol_id`, si existe por compatibilidad temporal, no puede ser la fuente final de autorización.

Roles iniciales de referencia, todos configurables:

- Superadministrador.
- Administrador general.
- Administrador de sucursal.
- Caja.
- Matrícula.
- Docente.
- Alumno.
- Consulta o auditoría.
- Supervisor.

El rol `SUPERVISOR` debe obtener su alcance global mediante permisos y configuración de alcance, no mediante validaciones fijas en código.

Los permisos se definen por módulo, submódulo/opción, pantalla y acción. Acciones mínimas: consultar, crear, modificar, eliminar, aprobar, anular, imprimir, exportar, importar, asignar y configurar. Cada permiso debe tener código único, por ejemplo `matriculas.crear`, `pagos.aprobar` o `reportes.caja.exportar`.

Alcances de datos soportados:

- Todas las sucursales.
- Una o varias sucursales autorizadas.
- Registros propios.
- Información del alumno autenticado.
- Grupos u ofertas asignadas al docente.

La validación es obligatoria en dos niveles:

1. Frontend: ocultar o deshabilitar menús, pantallas, botones y acciones no autorizadas.
2. Backend/API: middleware, guards, policies o mecanismo equivalente debe denegar la acción sin permiso con HTTP `403 Forbidden`.

Aplicar denegación por defecto y principio de menor privilegio. Separar permisos de consultar, modificar, aprobar y anular. Los cambios de roles o permisos deben invalidar el caché de permisos y ejecutarse dentro de transacciones.

Administración de usuarios debe permitir crear, consultar, modificar, activar/inactivar, asignar múltiples roles y sucursales, restablecer contraseña, bloquear temporalmente, obligar cambio de contraseña y consultar último acceso e intentos fallidos. Al inactivar un usuario se deben invalidar sus sesiones o tokens. No se puede eliminar o inhabilitar al último superadministrador activo.

La matriz administrativa de roles y permisos debe permitir buscar permisos, marcar/desmarcar permisos por módulo, copiar permisos entre roles y visualizar cantidad de usuarios por rol.

Auditar inicio/cierre de sesión, intentos fallidos, cambios de contraseña, usuarios, roles, permisos, activaciones/inactivaciones, aprobaciones y anulaciones. La bitácora debe guardar usuario, fecha/hora, IP, agente de usuario, acción, módulo, registro, valores antes/después, resultado y motivo de rechazo.

Antes de implementar código del módulo de seguridad se debe presentar arquitectura, modelo de datos y flujo de validación de permisos.

La implementación completa de RBAC debe incluir además:

- Endpoints REST administrativos para módulos, opciones, permisos, roles, usuarios y asignaciones de roles.
- Matriz administrativa de roles y permisos, incluyendo copiar permisos, búsqueda, selección total por módulo y cantidad de usuarios por rol.
- Aplicación del middleware `permiso:<codigo>` a todas las rutas protegidas.
- Servicio central de alcance de datos para global, sucursales asignadas, docente, alumno y registros propios.
- Bitácora automática de seguridad para operaciones permitidas y rechazadas.
- Revocación de sesiones o tokens al inactivar usuarios, bloqueo temporal, intentos fallidos y cambio obligatorio de contraseña cuando aplique.
- Protección del último superadministrador activo.
- Caché de permisos e invalidación inmediata al cambiar usuarios, roles o permisos.
- Pruebas de permisos `403`, múltiples roles, alcance por sucursal/docente/alumno, revocación de sesión y auditoría.

#### Estado de implementación RBAC

Completado:

- Tablas base de módulos, opciones, permisos, asignación múltiple de roles, sesiones, intentos y bitácora.
- Migración de compatibilidad desde `usuarios.rol_id` hacia `usuario_roles`.
- Middleware `permiso:<codigo>` con respuesta HTTP `403`.
- Endpoints de catálogo RBAC, permisos por rol, copia de permisos, roles por usuario, auditoría y configuración de alcance.
- Catálogo inicial de módulos/permisos y asignación inicial de permisos de Seguridad al rol administrativo.
- Tablas `alcances_rol` y `alcances_usuario` con tipos `global`, `sucursales`, `docente`, `alumno` y `propio`.
- Servicio `ResolutorAlcanceDatos` e integración inicial en consulta de ofertas académicas para sucursal y docente.
- Prueba API inicial de denegación `403`.

- Bitácora técnica de peticiones API en `bitacora_peticiones`, con método, ruta sin parámetros, usuario, HTTP, duración, IP, agente y fecha; no almacena cuerpos, contraseñas ni tokens.
- Pantalla inicial de Auditoría y sesiones como monitor descendente de peticiones, con búsqueda, paginación de 50 registros y refresco automático cada 15 segundos, protegida por `seguridad.consultar`.

Pendiente:

- Integrar el resolutor de alcance en estudiantes, matrículas, pagos, recibos, caja, calificaciones, inventario y reportes.
- Implementar restricciones de alumno y registros propios según cada entidad.
- Auditoría automática, revocación de sesiones/tokens, bloqueo e intentos fallidos de autenticación.
- Endpoints CRUD completos para módulos, opciones, permisos y roles.
- Pantallas completas de auditoría, sesiones, intentos y gestión visual de alcance.
- Menús y acciones frontend gobernados por permisos efectivos.
- Pruebas integrales de autorización y alcance.

#### Pendiente de cierre RBAC

- Aplicar `bloqueado_hasta` y `debe_cambiar_contrasena` durante el inicio de sesión.
- Revocar tokens/sesiones al inactivar un usuario.
- CRUD administrativo completo para módulos, opciones, permisos y roles.
- Pantallas de auditoría, sesiones e intentos de acceso.
- Búsqueda/filtros de permisos y conteo de usuarios por rol en la interfaz.
- Menús y botones frontend dinámicos según permisos efectivos.
- Pantalla visual para administrar alcance por rol y usuario.
- Integrar el alcance en estudiantes, calificaciones, asistencias, recibos, caja, inventario y reportes; ya está iniciado en ofertas, matrículas y pagos.
- Pruebas de bloqueo, revocación, auditoría, múltiples roles y alcance completo.

---

## 5. Tablas principales esperadas

No crear nombres alternativos sin autorización.

Tablas base:

- `sucursales`
- `usuarios`
- `roles`
- `permisos`
- `modulos`
- `opciones_modulo`
- `usuario_roles`
- `rol_permisos`
- `nomenclaturas_codigos`
- `usuario_sucursales`
- `sesiones_usuario`
- `intentos_acceso`
- `bitacora_seguridad`
- `departamentos_academicos`
- `planes_estudio`
- `versiones_plan_estudio`
- `niveles_academicos`
- `prerrequisitos_nivel`
- `modalidades`
- `nivel_modalidades`
- `periodos_academicos`
- `horarios`
- `horario_dias`
- `docentes`
- `aulas`
- `grupos_whatsapp`
- `ofertas_academicas`
- `estudiantes`
- `accesos_estudiante`
- `solicitudes_actualizacion_datos`
- `matriculas`
- `tipos_gestion_matricula`
- `gestiones_matricula`
- `asistencias_estudiante`
- `historial_academico`
- `calificaciones`
- `reglas_aprobacion`
- `evaluaciones_nivelacion`
- `conceptos_pago`
- `metodos_pago`
- `cuentas_bancarias`
- `enlaces_pago`
- `planes_cobro`
- `detalle_plan_cobro`
- `obligaciones_pago_estudiante`
- `pagos`
- `aplicaciones_pago`
- `comprobantes_pago`
- `recibos_caja`
- `detalle_recibo_caja`
- `sesiones_caja`
- `detalle_cierre_caja`
- `libros`
- `libro_niveles`
- `inventario_libros`
- `movimientos_inventario_libros`

---

## 6. Estados estándar

Usar estados consistentes.

### Estado general

- `activo`
- `inactivo`

### Oferta académica

- `borrador`
- `abierto`
- `lleno`
- `cerrado`
- `cancelado`

### Matrícula

- `iniciada`
- `reservada`
- `en_revision`
- `matriculado`
- `rechazado`
- `cancelado`
- `vencido`

### Pago

- `pendiente`
- `cargado`
- `solicita_link`
- `esperando_respuesta`
- `en_revision`
- `aprobado`
- `rechazado`
- `cancelado`
- `vencido`

### Recibo

- `emitido`
- `anulado`
- `reimpreso`
- `reversado`

### Caja

- `abierta`
- `cerrada`
- `validada`
- `observada`
- `reabierta`

### Calificación

- `pendiente`
- `registrado`
- `corregido`
- `anulado`

### Historial académico

- `matriculado`
- `aprobado`
- `reprobado`
- `retirado`

---

## 7. Reglas para APIs

Las APIs deben responder en JSON.

Estructura sugerida:

```json
{
  "resultado": "A",
  "codigo": 0,
  "mensaje": "Operación exitosa",
  "data": {}
}
```

Para errores:

```json
{
  "resultado": "R",
  "codigo": 400,
  "mensaje": "Mensaje de error funcional",
  "errores": {}
}
```

No retornar errores técnicos crudos al usuario final.

---

## 8. Validaciones obligatorias

Cada endpoint de escritura debe tener validación formal mediante Request/DTO equivalente.

Validaciones mínimas:

- Campos obligatorios.
- Tipos de datos.
- Longitudes.
- Existencia de llaves foráneas.
- Estados válidos.
- Fechas válidas.
- Montos mayores o iguales a cero.
- Cupos no negativos.
- Horario con hora fin mayor que hora inicio, salvo horario 24 horas.
- No duplicar estudiante por identidad o código.
- No duplicar recibo oficial.
- No permitir pago aprobado sin concepto.
- No permitir matrícula en oferta cerrada o llena.

---

## 9. Auditoría

Toda tabla operativa debe guardar:

- `creado_por`
- `creado_en`
- `actualizado_por`
- `actualizado_en`

En operaciones sensibles, guardar además:

- Usuario que aprobó.
- Fecha de aprobación.
- Usuario que anuló.
- Fecha de anulación.
- Motivo de rechazo o anulación.

Operaciones sensibles:

- Aprobar pago.
- Rechazar pago.
- Anular recibo.
- Cerrar caja.
- Reabrir caja.
- Cambiar cupo.
- Matricular por excepción.
- Modificar calificación.
- Ajustar inventario.

---

## 10. Migración desde sistema actual

Puede existir información histórica del sistema actual.

No migrar directamente a tablas productivas sin tabla intermedia cuando haya datos inconsistentes.

Usar tablas temporales o de staging, por ejemplo:

- `pagos_migrados_sistema_anterior`
- `estudiantes_migrados_sistema_anterior`
- `niveles_migrados_sistema_anterior`

La migración debe conservar:

- Número de recibo original.
- Fecha original.
- Hora original.
- Cajero u operador original.
- Código de estudiante original.
- Nombre histórico.
- Concepto original.
- Nivel histórico.
- Horario histórico.
- Forma de pago histórica.
- Banco y cuenta original.
- Estado original.

No crear nuevos conceptos contables durante la migración si el dato puede mapearse a `MAT`, `CUO`, `VLI`, etc.

---

## 11. Reportes esperados

### Reportes académicos

- Matriculados por periodo.
- Matriculados por sucursal.
- Matriculados por departamento académico.
- Matriculados por plan de estudio.
- Matriculados por nivel.
- Matriculados por horario.
- Matriculados por docente.
- Alumnos por grupo para entregar al maestro.
- Calificaciones por grupo.
- Nivel actual del estudiante.
- Estudiantes matriculados por docente, filtrable por año, período, concepto, nivel, horario y estado.

### Reportes financieros

- Reporte de caja por cajero.
- Cierre de caja por fecha.
- Ingresos por concepto de pago.
- Ingresos por forma de pago.
- Ingresos por sucursal.
- Ingresos por periodo.
- Ingresos por nivel, cruzando matrícula.
- Pagos pendientes.
- Pagos rechazados.
- Recibos anulados.

### Reportes de recibos y caja autorizados

Se deben implementar los siguientes reportes para Educación:

1. Recibos por orden numérico.
2. Depósitos.
3. Comprobantes de caja.
4. Recibos según forma de pago.
5. Recibos con depósito.
6. Recibos de libros.
7. Recibos por matrícula.
8. Recibos por cuota.
9. Reimpresión de recibos.
10. Cambios de horarios.
11. Reposición de libreta.
12. Otros servicios de educación.
13. Consulta general de recibos.
14. Recibos de Educación por cierre diario.
15. Resumen de ingresos.

No incluir reportes de Salud, medicamentos, odontología, procedimientos ni cierres diarios de Salud dentro de esta plataforma, salvo autorización futura explícita.

Todo reporte debe requerir `fecha_desde` y `fecha_hasta`. Puede incluir filtros opcionales de sucursal, período, docente, nivel, horario, concepto de pago, forma de pago, estado y cajero, según aplique.

Los reportes deben ofrecer dos salidas:

- Ver en pantalla: resultado paginado con totales y filtros aplicados.
- Exportar a Excel: resultado completo con los mismos filtros, fecha de generación, usuario generador y totales cuando correspondan.

El docente solo puede consultar o exportar información de sus propias ofertas académicas. Supervisor y administrador tienen alcance global conforme a sus permisos.

Las reimpresiones de recibos deben quedar en una bitácora con recibo, usuario, fecha, motivo y número de reimpresión.

Todo reporte, recibo de caja y constancia debe disponer de una salida oficial en PDF real. El encabezado debe incluir exactamente `Cursos San Vicente de Paul`; no se permiten documentos HTML como entrega final. La consulta en pantalla y la exportación a Excel pueden mantenerse como salidas operativas complementarias.

#### Estado actual de documentos PDF

Completado:

- Se instaló `barryvdh/laravel-dompdf` y se creó el servicio reutilizable `GeneradorPdfInstitucional`.
- Los recibos del portal y la constancia de nivel actual se descargan como PDF real.
- Los PDF incorporan el encabezado institucional exacto `Cursos San Vicente de Paul`.
- El panel administrativo ofrece PDF oficial y Excel para reportes disponibles.

Pendiente:

- Documentación Swagger/OpenAPI (no existe actualmente `public/swagger.json`).
- Validar en producción la descarga de PDFs tras los despliegues pendientes.

#### Estado actual de monitor de cupos y pruebas

La suite automatizada contiene **119 pruebas** (118 Feature + 1 Unit) con **348 aserciones** aprobadas.

Completado:

- API `GET /api/v1/monitor-cupos` protegida por `matriculas.consultar`, con alcance RBAC, filtros por período/sucursal, cálculo de cupos y colores funcionales.
- Pantalla administrativa del Monitor de cupos con período abierto preseleccionado, filtros por período y sucursal, actualización automática configurable y presentación sin IDs técnicos.

Pendiente:

- Agregar pruebas de datos para cada variante de reporte operativo.
- Aceptación en escritorio confirmada pendiente.

### Reportes de inventario

- Existencia por sucursal.
- Libros por nivel.
- Ventas de libros.
- Kardex de movimientos.
- Libros con existencia baja.

---

## 12. Pruebas mínimas

Cada módulo debe incluir pruebas.

Pruebas críticas:

- Crear sucursal.
- Crear departamento académico.
- Crear plan de estudio.
- Crear nivel.
- Crear periodo.
- Crear horario.
- Crear oferta académica.
- No permitir matrícula si no hay cupo.
- Reservar cupo al iniciar matrícula.
- Liberar cupo al rechazar pago.
- Confirmar cupo al aprobar pago.
- Generar obligaciones desde plan de cobro.
- Aplicar pago a una o varias obligaciones.
- Generar recibo al aprobar pago.
- Agrupar cierre de caja por concepto y método de pago.
- Validar nota mínima y faltas.
- Descontar inventario al vender libro.

---

## 13. Orden recomendado de construcción

No construir la app móvil antes de tener API estable.

Orden recomendado:

1. Backend API base.
2. Seguridad, usuarios, roles y sucursales.
3. Catálogos académicos.
4. Periodos, horarios y ofertas académicas.
5. Estudiantes y accesos.
6. Matrícula online.
7. Planes de cobro y obligaciones.
8. Pagos, comprobantes y recibos.
9. Caja y cierre de cajero.
10. Calificaciones e historial académico.
11. Libros e inventario.
12. Reportes.
13. Web administrativa.
14. Portal estudiante.
15. App móvil.

---

## 14. Qué no debe hacer Codex

No hacer lo siguiente sin autorización explícita:

- Cambiar nombres de tablas definidos.
- Cambiar nombres de campos de negocio ya aprobados.
- Crear conceptos contables por cada cuota.
- Crear conceptos contables por cada nivel.
- Mezclar sucursal con departamento académico.
- Mezclar modalidad con departamento académico.
- Matricular directamente contra horario sin oferta académica.
- Guardar contraseñas en texto plano.
- Mostrar correo o teléfono completo en validación de estudiante existente.
- Aprobar pagos automáticamente sin regla definida.
- Entregar link de WhatsApp antes de aprobar el pago.
- Descontar inventario sin recibo, pago aprobado o movimiento autorizado.
- Eliminar historial académico por mostrar solo nivel actual al estudiante.

---

## 15. Criterios de aceptación generales

Para cada tarea generada, cumplir:

1. Migraciones creadas.
2. Modelos creados.
3. Relaciones definidas.
4. Requests de validación creados.
5. Endpoints REST creados.
6. Resources JSON creados.
7. Seeders cuando aplique.
8. Pruebas automatizadas básicas.
9. Documentación mínima de endpoints.
10. Cumplimiento de nombres de tablas y campos en español.
11. Cumplimiento de reglas críticas del negocio.
12. No introducir datos quemados si deben ser parametrizables.

---

## 16. Prompt base para nuevas tareas

Cuando se asigne una tarea a Codex, usar este patrón:

```text
Lee primero AGENTS.md y los documentos en /docs.

Implementa el módulo: [nombre del módulo].

Respeta:
- Tablas y campos en español.
- Reglas de negocio del AGENTS.md.
- Arquitectura API centralizada.
- Sistema multi-sucursal.
- Matrícula contra ofertas_academicas.
- Pagos contra obligaciones_pago_estudiante.
- Conceptos contables limpios.

Entrega:
- Migraciones.
- Modelos.
- Relaciones.
- Controladores.
- Requests.
- Resources.
- Seeders, si aplica.
- Pruebas.
- Documentación breve.
```

---

## 17. Fuente de verdad

El archivo `AGENTS.md` define reglas permanentes para Codex.

Los documentos en `/docs` complementan el detalle funcional.

Si hay conflicto entre una tarea puntual y `AGENTS.md`, Codex debe detenerse y pedir confirmación antes de cambiar una regla crítica.

---

## 18. Seguridad de APIs y códigos de error

Toda API administrativa debe requerir autenticación Sanctum y un permiso RBAC
específico. No utilizar el rol heredado `ADMIN` o validaciones fijas por rol
como mecanismo de autorización.

Permisos mínimos por acción:

- Consulta: `<modulo>.consultar`.
- Creación: `<modulo>.crear`.
- Modificación: `<modulo>.modificar`.
- Aprobación o rechazo: `<modulo>.aprobar`.
- Configuración de seguridad, roles y usuarios: `seguridad.configurar`.

Las rutas de inicio de sesión son públicas. Las operaciones del portal del
estudiante que cambian datos, tales como reservar, confirmar o liberar una
matrícula, deben requerir token Sanctum emitido para `AccesoEstudiante` y
validar que la matrícula pertenezca al estudiante autenticado. Un usuario
administrativo no sustituye la sesión de un estudiante ni viceversa.

Las respuestas de error de la API deben conservar siempre un código funcional
compuesto por el HTTP y el identificador interno:

```text
401_CREDENCIALES_INVALIDAS
401_NO_AUTENTICADO
403_SIN_PERMISO
403_ACCESO_ESTUDIANTE_REQUERIDO
404_NO_ENCONTRADO
422_VALIDACION
500_ERROR_INTERNO
```

El campo `mensaje` debe ser comprensible para el usuario final y no exponer
detalles técnicos. Los errores de validación deben incluir adicionalmente el
objeto `errores` con el detalle de cada campo.

---

## 19. Patrón reutilizable de implementación RBAC

No duplicar la definición de permisos, middleware o lógica de rutas al crear
un módulo, API, pantalla, menú o botón. La fuente técnica del patrón es
`docs/PATRON_IMPLEMENTACION_RBAC.md`.

Reglas obligatorias:

1. Declarar el módulo inicial en `config/rbac.php` y sincronizarlo con
   `SeguridadRbacSeeder` y `RegistroPermisosService`.
2. Para recursos REST administrativos usar
   `Route::apiResourceProtegido(...)`; asigna automáticamente el permiso según
   la acción REST.
3. Para operaciones no REST usar `Route::accionProtegida(...)` con el permiso
   explícito, por ejemplo `pagos.aprobar`.
4. Usar `@hasperm` y lógica Alpine.js en Blade para menús, pantallas y
   botones, usando los permisos efectivos recibidos en login.
5. El frontend nunca reemplaza la validación backend ni la validación de
   alcance por sucursal, docente, alumno o propietario.
6. Cada módulo nuevo debe incluir prueba de permiso permitido y denegado.

---

## 20. Estado actual de implementación

### Inventario del código fuente

| Capa | Cantidad | Notas |
|------|:--------:|-------|
| Migraciones | 59 | 3 framework defaults + 56 custom (2 tablas aún pendientes) |
| Modelos | 54 | Cubre la mayoría del dominio |
| Controladores | 33 | 1 base + 32 API (Auth, Seguridad, Académico, Estudiantes, Matrículas, Pagos, Caja, Reportes, Inventario, Configuración Flujo) |
| Middleware | 3 | `VerificarPermiso`, `AutenticarEstudiante`, `RegistrarPeticion` |
| Servicios | 4 | `ResolutorAlcanceDatos`, `RegistroPermisosService`, `CachePermisosService`, `ServicioBitacora` |
| Seeders | 17 | Catálogos académicos, RBAC, usuario admin, conceptos, métodos, LibroSeeder, etc. |
| Pruebas | 133 | 132 Feature + 1 Unit en 17 clases de prueba |
| Vistas Blade | 26 | 12 admin + 8 portal estudiante + 3 layouts + 2 root + 1 auth |
| Rutas | 3 archivos | `api.php`, `web.php`, `console.php` |
| Config personalizado | 2 | `rbac.php`, `seguridad.php` |
| Documentación | 20 archivos | En `docs/` |

### Tablas creadas por migración

**RBAC/Seguridad (18 tablas):** `sucursales`, `usuarios`, `roles`, `modulos`, `opciones_modulo`, `permisos`, `usuario_roles`, `rol_permisos`, `usuario_sucursales`, `nomenclaturas_codigos`, `sesiones_usuario`, `intentos_acceso`, `bitacora_seguridad`, `bitacora_peticiones`, `alcances_rol`, `alcances_usuario`, `sessions` (modificada), `cache` (framework).

**Catálogos académicos (13 tablas):** `departamentos_academicos`, `planes_estudio`, `versiones_plan_estudio`, `modalidades`, `niveles_academicos`, `nivel_modalidades`, `prerrequisitos_nivel`, `horarios`, `horario_dias`, `docentes`, `aulas`, `periodos_academicos`, `ofertas_academicas`.

**Estudiantes (3 tablas):** `estudiantes`, `accesos_estudiante`, `solicitudes_actualizacion_datos`.

**Pagos/Cobros (10 tablas):** `conceptos_pago`, `metodos_pago`, `planes_cobro`, `detalle_plan_cobro`, `matriculas`, `obligaciones_pago_estudiante`, `tipos_gestion_matricula`, `gestiones_matricula`, `cuentas_bancarias`, `enlaces_pago`.

**Transacciones (8 tablas):** `pagos`, `aplicaciones_pago`, `comprobantes_pago`, `recibos_caja`, `sesiones_caja`, `detalle_cierre_caja`, `calificaciones`, `historial_academico`.

**Reglas/Nivelación (2 tablas):** `reglas_aprobacion`, `evaluaciones_nivelacion`.

**Inventario/Libros (4 tablas):** `libros`, `libro_niveles`, `inventario_libros`, `movimientos_inventario_libros`.

### Tablas pendientes de crear

Las siguientes tablas están definidas en la especificación pero no tienen migración:

- `grupos_whatsapp`
- `asistencias_estudiante`

### RBAC — Estado de implementación

**Completado:**

- Tablas base: módulos, opciones, permisos, asignación múltiple de roles, sesiones, intentos y bitácora.
- Migración de compatibilidad desde `usuarios.rol_id` hacia `usuario_roles`.
- Middleware `permiso:<codigo>` con respuesta HTTP `403`.
- Endpoints de catálogo RBAC, permisos por rol, copia de permisos, roles por usuario, auditoría y configuración de alcance.
- Catálogo inicial de módulos/permisos y asignación inicial de permisos de Seguridad al rol administrativo.
- Tablas `alcances_rol` y `alcances_usuario` con tipos `global`, `sucursales`, `docente`, `alumno` y `propio`.
- Servicio `ResolutorAlcanceDatos` e integración en consulta de ofertas académicas.
- Bitácora técnica de peticiones API en `bitacora_peticiones`.
- Pantalla de Auditoría y sesiones como monitor descendente de peticiones.
- Matriz administrativa de roles y permisos en `seguridad.blade.php` (modal con checkboxes, búsqueda, copiar entre roles, select-all por módulo).

**Pendiente:**

- Integrar el resolutor de alcance en estudiantes, matrículas, pagos, recibos, caja, calificaciones, inventario y reportes.
- Auditoría automática con valores antes/después, revocación de sesiones/tokens, bloqueo e intentos fallidos.
- CRUD administrativo completo para módulos, opciones, permisos y roles.
- Pantallas de auditoría, sesiones e intentos de acceso.
- Menús y botones frontend dinámicos según permisos efectivos.
- Pruebas de bloqueo, revocación, auditoría, múltiples roles y alcance completo.

### Portal del estudiante — Estado de implementación

**Completado:**

- `AutenticarEstudiante` middleware con tokens SHA-256 personalizados.
- `EstudianteAuthController`: login, registro, activar, portal, logout.
- `PortalEstudianteController`: 8 endpoints (misOfertas, reservarMatricula, misMatriculas, subirComprobante, misPagos, misRecibos, miNivel, whatsapp).
- 9 vistas Blade: login, registro, activar, dashboard, matricula, comprobante, pagos, recibos + layout portal.
- 8 rutas web para páginas del portal.
- `PortalEstudianteTest.php`: 14 pruebas cubriendo auth, portal, ofertas, matrícula, comprobantes, pagos, recibos, nivel, WhatsApp, cerrar sesión.

**Correcciones aplicadas (2026-07-26):**

- `registrarPago()` en `PortalEstudianteController.php`: se cambió `creado_por` de `$estudiante->id` (ID de `estudiantes`) a `null`, porque la FK en `aplicaciones_pago` apunta a `users` y el portal del estudiante no autentica contra `users`. El error se manifestaba como `SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed`.
- `registrarPago()`: se corrigió `monto_aplicado` de `0` al monto real de cada obligación (`$obligacion->monto`) usando un mapa de colección.
- Login del portal (`login.blade.php`): se añadió `window.extractError` y `window.extractErrorCode` inline, ya que la página es standalone y no hereda el layout `portal.blade.php` donde se definen esas funciones.
- Vista `pagos.blade.php` (Mis Pagos): se agregó botón "Nuevo Pago" con modal de selección de obligaciones pendientes, método de pago, referencia y comprobante opcional; columna "Comprobante" en la tabla de historial para subir comprobante a pagos existentes.
- Se corrigió `EstudianteAuthController.php::portal()`: se agregó `matricula_activa_id` en la respuesta JSON para identificar la matrícula activa real cuyas obligaciones se retornan, y se actualizaron `pagos.blade.php` y `comprobante.blade.php` para usar `data.matricula_activa_id` en lugar de `data.matriculas[0].id`, que podía corresponder a otra matrícula y causar el error "No hay obligaciones pendientes para esta matrícula" al pagar conceptos distintos a la matrícula inicial.

**Correcciones y mejoras (2026-07-30):**

- Grid de pagos: se agregó `metodo_pago` como objeto completo en respuesta API (con `permite_link_pago`, `requiere_proveedor`) para que el badge de link funcione correctamente.
- Grid de pagos: se agregó `whatsapp_link` y `whatsapp_grupo` en respuesta API para pagos aprobados con grupo WhatsApp configurado.
- Vista `pagos.blade.php`: columna Link ahora muestra `<a>` directo al `link_pago_url` en lugar de badge "Disponible".
- Vista `pagos.blade.php`: botón verde de WhatsApp en acciones para pagos aprobados con grupo (mobile y desktop).
- Nuevo estado `esperando_respuesta` en el flujo de pago por link: Admin llena URL → `esperando_respuesta`, Estudiante confirma → `en_revision`.
- Vista `pagos.blade.php`: badge, banner, highlight y botón "Ya completé el pago" para el estado `esperando_respuesta` con color morado.
- `actualizarLink` en `PagoController.php`: cambia a `esperando_respuesta` en lugar de `en_revision`.
- `confirmarLinkPago` en `PortalEstudianteController.php`: acepta `esperando_respuesta` como estado previo.

**Pendiente:**

- Validación en producción.
- Descarga de recibos en PDF desde el portal.

### Módulo Inventario/Libros — Estado de implementación

**Completado:**

- Migraciones: `libros`, `libro_niveles`, `inventario_libros`, `movimientos_inventario_libros`.
- Modelos: `Libro`, `LibroNivel`, `InventarioLibro`, `MovimientoInventarioLibro` con relaciones, scopes y casts.
- Controladores: `LibroController` (CRUD con búsqueda, niveles) e `InventarioLibroController` (stock, ajustar con bloqueo pesimista, vender, kardex).
- 10 endpoints REST protegidos por `inventario.*`:
  - `GET/POST/PUT /api/v1/inventario/libros`
  - `GET/POST /api/v1/inventario/stock`
  - `GET /api/v1/inventario/stock/{id}`
  - `POST /api/v1/inventario/stock/{id}/ajustar`
  - `POST /api/v1/inventario/stock/{id}/vender`
  - `GET /api/v1/inventario/kardex`
- 14 tests (44 assertions): CRUD, stock, ajustes, ventas, kardex, permiso 403.
- Seeder `LibroSeeder`: 5 libros de inglés con inventario inicial (25 uds) en SPS y TGU.
- Vista admin `inventario.blade.php` con 3 tabs (Catálogo, Existencias, Kardex) y modales CRUD, ajustar, vender.
- Menú sidebar en `admin.blade.php` protegido por `inventario.*`.
- Módulo `inventario` en `config/rbac.php` con opciones `inventario.libros`, `inventario.stock`, `inventario.ventas`.

### Pantallas administrativas — Estado de implementación

**Completado:**

- Dashboard
- Catálogos Académicos (sucursales, departamentos, planes, niveles, modalidades, horarios, docentes, aulas, períodos, conceptos, métodos)
- Ofertas y Cupos
- Monitor de Cupos (con auto-refresh configurable, colores funcionales, filtros por período/sucursal)
- Estudiantes (CRUD + Ficha Integral con tabs Datos/Matrículas/Pagos/Recibos/Calificaciones)
- Matrícula (listado, reserva, confirmación, cancelación + tab Gestiones de Matrícula)
- Calificaciones (filtros cascada Período→Nivel→Grupo, tabla editable notas/faltas, resultado en cliente)
- Pagos (visor de comprobantes, aprobar/rechazar, recibos con detalle/anular/reimprimir)
- Caja (sesiones, cierre)
- Reportes (18 endpoints cableados con columnas dinámicas, totales, paginación)
- Seguridad (usuarios, roles, matriz permisos con checkboxes, copiar entre roles, búsqueda, Configuración Flujo con modal compacto, validación única combo y eliminación forzada)
- Inventario y Libros (catálogo, existencias, kardex, ajustar, vender)

**Pendiente:**

- Grupos WhatsApp
- Asistencias (pasar lista docente)
- Exportar Excel en Reportes (deshabilitado con tooltip)
- Menús y botones frontend gobernados 100% por permisos efectivos (avanzado, parcial)

### Correcciones de infraestructura realizadas

- Alpine.js importado e iniciado explícitamente en `resources/js/app.js` (bloqueante #1, panel cargaba vacío).
- `RegistroPermisosService` extendido para generar permisos módulo-nivel (`<modulo>.<accion>`), necesarios para middleware `permission:<codigo>`.
- Se corrigieron todas las referencias a columnas inexistentes `nombres,apellidos` → `nombre,apellido` en 5 controladores (bug latente MySQL/SQLite).
- URLs rotas corregidas en vistas admin y portal.

## 21. Sistema estandarizado de códigos de error

Toda respuesta de error de la API debe usar `App\Helpers\RespuestaError` para garantizar
formato uniforme y auditoría automática.

### Formato de respuesta

```json
{
  "resultado": "R",
  "codigo": 422,
  "codigo_error": "422_CONFLICTO_HORARIO",
  "mensaje": "Mensaje para el usuario final",
  "mensaje_tecnico": "Detalle técnico para depuración",
  "errores": { "campo": ["Error de validación"] }
}
```

### Uso en controladores

```php
return RespuestaError::make('422_MI_CODIGO', 422, 'Mensaje usuario', 'Mensaje técnico')
    ->response($request);
```

### Métodos de fábrica predefinidos

- `RespuestaError::validacion($errores)` → `422_VALIDACION`
- `RespuestaError::noEncontrado($entidad)` → `404_NO_ENCONTRADO`
- `RespuestaError::sinPermiso($permiso)` → `403_SIN_PERMISO`
- `RespuestaError::noAutenticado()` → `401_NO_AUTENTICADO`
- `RespuestaError::credencialesInvalidas()` → `401_CREDENCIALES_INVALIDAS`
- `RespuestaError::interno($mensajeTecnico)` → `500_ERROR_INTERNO`
- `RespuestaError::make($codigo, $http, $msgUsuario, $msgTecnico)` → código personalizado

### Conteo de errores en bitácora

`RespuestaError::response()` registra automáticamente en `bitacora_seguridad` con:
- `accion = error_{codigo_error}`
- `resultado = rechazado`
- `motivo = mensaje_tecnico`

### Frontend

Toda captura de error debe usar `window.extractError(e, fallback)` en lugar de
acceder directamente a `e.response.data.mensaje`. La función prueba internamente
`mensaje`, `mensaje_usuario`, `message`, `error` y `errores` en ese orden.

```js
catch(e) {
    this.error = window.extractError(e, 'Mensaje por defecto');
}
```

Para obtener el código de error: `window.extractErrorCode(e)`.

### Registro de códigos de error

| Código | HTTP | Descripción |
|---|---|---|
| `401_NO_AUTENTICADO` | 401 | Token faltante o inválido |
| `401_CREDENCIALES_INVALIDAS` | 401 | Credenciales incorrectas |
| `403_SIN_PERMISO` | 403 | Permiso RBAC denegado |
| `404_NO_ENCONTRADO` | 404 | Recurso no existe |
| `422_VALIDACION` | 422 | Error de validación de campos |
| `422_OFERTA_NO_PERTENECE_SUCURSAL` | 422 | Oferta no corresponde a sucursal del estudiante |
| `422_OFERTA_NO_ABIERTA` | 422 | Oferta no está en estado abierto |
| `422_SIN_CUPO` | 422 | Sin cupos disponibles |
| `422_MATRICULA_DUPLICADA` | 422 | Ya existe matrícula activa en la misma oferta |
| `422_CONFLICTO_HORARIO` | 422 | El horario choca con otra matrícula activa |
| `500_ERROR_INTERNO` | 500 | Error interno del servidor |

Cada nuevo módulo debe registrar sus códigos de error en esta tabla.

### Despliegue

- Workflow GitHub Actions en `.github/workflows/desplegar-smarterasp.yml`.
- Despliegue FTPS a SmarterASP.
- Verificar ejecución correcta tras cada push a `main`.
