# AGENTS.md â€” Plataforma Cursos San Vicente de PaÃºl

## 1. PropÃ³sito del proyecto

Este repositorio corresponde a la plataforma centralizada de **Cursos San Vicente de PaÃºl**.

El sistema debe administrar:

- MatrÃ­cula online.
- Estudiantes de primer ingreso.
- Estudiantes existentes y activaciÃ³n de acceso.
- Sucursales.
- Departamentos acadÃ©micos.
- Planes de estudio.
- Niveles acadÃ©micos.
- Modalidades.
- Periodos acadÃ©micos.
- Horarios.
- Oferta acadÃ©mica por periodo.
- Cupos.
- Pagos.
- Planes de cobro.
- MatrÃ­cula y cuotas.
- Comprobantes de pago.
- Recibos de caja.
- Cierre de cajero.
- Calificaciones.
- Historial acadÃ©mico.
- Libros e inventario.
- Grupos de WhatsApp.
- Reportes acadÃ©micos y financieros.

El sistema serÃ¡ centralizado, pero debe permitir operaciÃ³n separada por sucursal.

Sucursales iniciales:

- San Pedro Sula.
- Tegucigalpa.

---

## 2. Idioma y convenciones

### Base de datos

Usar nombres de tablas y campos en espaÃ±ol.

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

No crear tablas con nombres en inglÃ©s si ya existe un nombre funcional en espaÃ±ol.

### CÃ³digo

El cÃ³digo puede usar convenciones propias del framework, pero los conceptos del negocio deben mantenerse en espaÃ±ol cuando representen entidades del dominio.

Ejemplos:

- Modelo: `Sucursal`
- Modelo: `DepartamentoAcademico`
- Modelo: `OfertaAcademica`
- Modelo: `Matricula`
- Modelo: `Pago`
- Modelo: `ReciboCaja`

### DocumentaciÃ³n

La documentaciÃ³n tÃ©cnica y comentarios funcionales deben escribirse en espaÃ±ol.

Los comentarios en el cÃ³digo (feed, docblocks, inline comments) deben mantenerse en espaÃ±ol cuando representen conceptos del dominio o expliquen lÃ³gica de negocio. Los comentarios tÃ©cnicos de framework o librerÃ­as pueden mantenerse en inglÃ©s si son estÃ¡ndar del ecosistema.

---

## 3. Arquitectura actual

La aplicaciÃ³n es un proyecto Laravel 11 monolÃ­tico (sin separaciÃ³n en directorios `backend/` o `frontend/`).

### Backend (API + Blade)

- Laravel 11 con rutas API en `routes/api.php` y rutas web en `routes/web.php`.
- AutenticaciÃ³n administrativa con Sanctum Bearer tokens.
- AutenticaciÃ³n de estudiante con tokens SHA-256 personalizados en `accesos_estudiante`.
- Migraciones, modelos, controladores, seeders, pruebas automatizadas.
- No existen Form Requests ni JSON Resources; la validaciÃ³n se hace inline en controladores.

### Web administrativa (Blade + Alpine.js + Tailwind CSS)

- Las pantallas administrativas son Blade server-rendered con Alpine.js para interactividad.
- Comunica con la API vÃ­a `window.api` (objeto global definido en `resources/js/app.js`).
- En el panel administrativo, crear y publicar `window.api` antes de ejecutar `Alpine.start()`. Los componentes con `x-init` validan el token al iniciar y se redirigirÃ¡n falsamente al login si Alpine se inicia antes de que la API global exista.
- El login administrativo debe obtener el token Sanctum desde `/api/v1/login`, guardarlo en `localStorage` y el dashboard debe validarlo mediante `/api/v1/me`. No depender de `auth()->user()` ni de una sesiÃ³n web paralela para decidir si el panel puede abrirse.
- El contador de vencimiento solo debe expulsar al usuario cuando exista una fecha de vencimiento vÃ¡lida y esta haya pasado; una fecha ausente nunca debe interpretarse como sesiÃ³n vencida.
- El filtro de permisos se aplica en frontend ocultando/deshabilitando menÃºs y acciones no autorizadas.
- No duplicar reglas crÃ­ticas de negocio en frontend.
- Debe respetar roles, permisos y sucursales asignadas.
- Debe ser responsive y funcionar en navegadores de escritorio y navegadores mÃ³viles.
- Las pantallas, formularios, tablas y acciones crÃ­ticas deben ser utilizables con pantalla tÃ¡ctil, sin depender exclusivamente de hover, teclado o resoluciones de escritorio.

### DiseÃ±o visual de pantallas

Cada pantalla debe diseÃ±arse como producto profesional, no solo como formulario funcional. Aplicar jerarquÃ­a visual clara entre marca, propÃ³sito, acciÃ³n principal y mensajes; paleta institucional consistente de azul profundo, verde acadÃ©mico y acento dorado; tipografÃ­a, espaciado, contraste y estados de interacciÃ³n cuidados.

El diseÃ±o debe ser responsive desde mÃ³vil, con controles tÃ¡ctiles cÃ³modos, formularios guiados, errores descriptivos y una sensaciÃ³n de confianza. Tablas, filtros, tarjetas y paneles deben mantener densidad adecuada para administraciÃ³n. AdministraciÃ³n y Portal del Estudiante deben diferenciarse visualmente sin perder coherencia de marca.

### Regla prioritaria de visualizaciÃ³n de identificadores

Los IDs internos son tÃ©cnicos y no deben mostrarse en pantallas, consultas, listas, reportes visuales ni detalles. Toda entidad de catÃ¡logo o configuraciÃ³n se presenta mediante `codigo` y, cuando aporte contexto, `nombre` con el formato `CÃ“DIGO Â· Nombre`.

Las relaciones operativas deben resolverse visualmente a su cÃ³digo institucional o a una descripciÃ³n funcional (por ejemplo, estudiante, oferta, recibo o mÃ©todo de pago). Los IDs pueden enviarse internamente en formularios, URLs y APIs, pero nunca exponerse como referencia visible al usuario final. Esta regla es prioritaria para todo componente nuevo o modificado.

### Regla de visualizaciÃ³n de versiones de plan de estudio

En toda pantalla, grid, filtro, formulario o reporte donde se muestre una versiÃ³n de plan de estudio, debe presentarse con el formato:

```text
{NOMBRE_DEL_PLAN} Â· V{NUMERO_VERSION}
```

Ejemplo: `Intensivo Â· V1`, `Semi Intensivo Â· V2`. No usar el cÃ³digo del plan ni la palabra "VersiÃ³n" completa; usar siempre la abreviatura `V` mayÃºscula seguida del nÃºmero.

### Regla de visualizaciÃ³n de recibos

En toda pantalla, grid, filtro, formulario o reporte donde se muestre un recibo de caja, el campo `estado` debe representar Ãºnicamente el estado funcional real del recibo:

- `emitido`
- `anulado`
- `reversado`

La cantidad de reimpresiones debe mostrarse siempre en una columna o campo aparte, usando `veces_reimpreso` o su etiqueta funcional equivalente. No debe mostrarse `Finalizado` como estado de recibo ni mezclar la reimpresiÃ³n con el estado principal.

### Portal del estudiante

Debe consumir la misma API del backend.

Debe permitir:

- Primer ingreso.
- ActivaciÃ³n de estudiante existente.
- Login.
- Consulta de nivel actual.
- MatrÃ­cula online.
- SelecciÃ³n de horario disponible.
- Carga de comprobante.
- Consulta de pagos.
- Descarga de recibos.
- VisualizaciÃ³n del link de WhatsApp cuando el pago estÃ© aprobado.

El sistema debe tener dos pantallas de inicio de sesiÃ³n separadas:

- Acceso administrativo: para `usuarios`, con RBAC, sucursales, docente y supervisor.
- Acceso de estudiante: para `accesos_estudiante`, limitado a matrÃ­cula, pagos, comprobantes, recibos, nivel actual y WhatsApp autorizado.

Ambas consumen la misma API central, pero deben usar autenticaciÃ³n, tokens, rutas y polÃ­ticas separadas. Un estudiante nunca obtiene permisos administrativos por su acceso; un usuario administrativo no usa las rutas privadas del estudiante salvo autorizaciÃ³n funcional explÃ­cita.

### App mÃ³vil

Preferencia:

- React Native con Expo.
- Debe consumir la misma API central.
- No debe tener base de datos de negocio independiente.
- Puede tener almacenamiento local solo para sesiÃ³n, cachÃ© o archivos temporales.

---

## 4. Reglas crÃ­ticas del negocio

Estas reglas no deben cambiarse sin autorizaciÃ³n.

### 4.1 Sistema centralizado multi-sucursal

El sistema tendrÃ¡ una sola base de datos centralizada.

Cada operaciÃ³n acadÃ©mica, financiera y administrativa debe poder identificarse por sucursal cuando aplique.

Tablas que deben tener relaciÃ³n con `sucursales`:

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

### 4.2 Departamento acadÃ©mico

El concepto **Departamento** del sistema anterior debe normalizarse como **Departamento AcadÃ©mico**.

Un departamento acadÃ©mico representa Ãºnicamente un Ã¡rea de formaciÃ³n.

Ejemplos:

- InglÃ©s.
- ComputaciÃ³n.
- AlemÃ¡n.
- FrancÃ©s.
- Italiano.
- Diplomados.

No usar departamento acadÃ©mico para representar:

- Sucursal.
- Modalidad.
- Horario.
- Concepto de pago.
- Servicios especiales.

RelaciÃ³n esperada:

```text
departamento_academico
    -> plan_estudio
        -> nivel_academico
```

---

### 4.3 Planes de estudio y niveles

El sistema debe manejar planes de estudio y niveles acadÃ©micos.

La instituciÃ³n confirmÃ³ los siguientes grupos de planes/programas:

- Intensivo.
- Semi Intensivo.
- Infantil Intensivo.
- Infantil Semi Intensivo.
- Idiomas.
- Diplomados.

InterpretaciÃ³n de diseÃ±o:

- Intensivo y Semi Intensivo deben manejarse como modalidad acadÃ©mica o rÃ©gimen de estudio.
- Presencial y Virtual deben manejarse como modalidad de atenciÃ³n.
- InglÃ©s, ComputaciÃ³n, AlemÃ¡n, FrancÃ©s e Italiano deben manejarse como departamentos acadÃ©micos.
- Los niveles deben tener orden acadÃ©mico y nota mÃ­nima.
- Los prerrequisitos de un nivel solo pueden seleccionarse dentro de la misma `version_plan_estudio_id` y deben tener `orden` menor que el nivel actual.
- Al cambiar la versiÃ³n del plan en cualquier formulario o pantalla, deben limpiarse los prerrequisitos ya seleccionados para evitar validaciones cruzadas.

El estudiante avanza al siguiente nivel si:

- Aprueba el nivel anterior.
- Cumple la nota mÃ­nima.
- Cumple la regla de faltas.
- No tiene saldo pendiente, si la regla de bloqueo aplica.

---

### 4.4 Nota mÃ­nima y faltas

Nota mÃ­nima general:

```text
80%
```

Reglas de faltas:

```text
Intensivo: menos de 8 faltas
Semi Intensivo: menos de 4 faltas
```

InterpretaciÃ³n tÃ©cnica:

- Intensivo: mÃ¡ximo permitido = 7 faltas.
- Semi Intensivo: mÃ¡ximo permitido = 3 faltas.

La aprobaciÃ³n debe considerar nota y asistencia.

```text
aprobado = nota_final >= nota_minima_aprobar
           y cantidad_faltas <= faltas_maximas_permitidas
```

---

### 4.5 Primer ingreso y examen de nivelaciÃ³n

Un estudiante de primer ingreso normalmente inicia en Phonics o nivel inicial.

Si ya tiene conocimiento previo, puede realizar examen de nivelaciÃ³n.

El sistema debe permitir:

- Registrar estudiante de primer ingreso.
- Generar usuario y contraseÃ±a.
- Registrar examen de nivelaciÃ³n.
- Autorizar nivel de ingreso distinto al inicial.
- Matricular por excepciÃ³n administrativa.
- Guardar usuario, fecha y motivo de autorizaciÃ³n.

---

### 4.6 Estudiante existente

Si el estudiante ya existe en la base de alumnos:

- No debe duplicarse.
- Debe buscarse por cÃ³digo de alumno, cuenta o identificador definido.
- El sistema debe mostrar correo y telÃ©fono enmascarados.
- Si reconoce los datos, se envÃ­an credenciales.
- Si no reconoce los datos, debe crearse solicitud de actualizaciÃ³n de datos.

No mostrar correo o telÃ©fono completos sin validaciÃ³n.

Ejemplos:

```text
ad******@gmail.com
****-**45
```

### 4.6.1 MÃ³dulo de estudiantes

El mÃ³dulo administrativo de Estudiantes debe ser una ficha integral del alumno. Debe permitir consultar, segÃºn permisos, datos personales, matrÃ­culas actuales e histÃ³ricas, pagos, obligaciones pendientes, comprobantes, recibos, calificaciones, asistencias, historial acadÃ©mico, cambios de horario/retiros y constancias emitidas.

No duplicar estos datos dentro de `estudiantes`: cada secciÃ³n consulta su tabla de origen mediante la relaciÃ³n del estudiante. Las constancias deben emitirse desde informaciÃ³n histÃ³rica validada y conservar una bitÃ¡cora de emisiÃ³n, tipo de constancia, usuario, fecha y documento generado.

---

### 4.7 MatrÃ­cula

La matrÃ­cula no debe hacerse directamente contra el nivel ni contra el horario.

La matrÃ­cula debe hacerse contra:

```text
ofertas_academicas
```

La oferta acadÃ©mica representa:

```text
Sucursal
+ Periodo acadÃ©mico
+ Plan de estudio
+ Nivel acadÃ©mico
+ Modalidad
+ Horario
+ Docente
+ Aula
+ Cupo
+ Plan de cobro
+ Grupo de WhatsApp
```

El estudiante solo debe ver ofertas acadÃ©micas:

- Del periodo abierto.
- De la sucursal seleccionada o asignada.
- Del nivel permitido.
- De la modalidad seleccionada.
- En estado abierto.
- Con cupo disponible.

CÃ¡lculo de cupo:

```text
cupos_disponibles = cupo_maximo - cupos_matriculados - cupos_reservados
```

---

### 4.8 Cupos

Cupo mÃ¡ximo inicial:

```text
25 estudiantes
```

Aunque actualmente aplica para todos los grupos, debe quedar parametrizable en `ofertas_academicas`.

Reglas:

- Si el grupo llega al cupo mÃ¡ximo, no debe mostrarse para matrÃ­cula.
- Si el estudiante inicia matrÃ­cula, se puede reservar cupo temporal.
- Si el pago se aprueba, el cupo pasa a matriculado.
- Si el pago se rechaza, cancela o vence, el cupo se libera.
- Si la Referencia de Pago y fecha  ambas estan  duplicada al registrar el  pago. 
  Si esta duplicada enviar correo al los destinarios   antalma61@hotmail.com,  kcontreras1995@hotmail.com


### 4.9 Horarios

Los horarios cambian cada periodo.

Por eso, el catÃ¡logo `horarios` debe ser reutilizable, pero la disponibilidad para matrÃ­cula debe definirse en `ofertas_academicas`.

No mostrar todos los horarios al estudiante.

Mostrar Ãºnicamente horarios asociados a ofertas acadÃ©micas abiertas y con cupo disponible.

### 4.9.1 Filtros acadÃ©micos globales

Fuera de la pantalla de creaciÃ³n y mantenimiento de `ofertas_academicas`, toda pantalla acadÃ©mica, de matrÃ­cula, caja, pagos, asistencia, calificaciones, cupos y reportes que consulte o procese informaciÃ³n de estudiantes debe seguir este orden de filtro:

```text
PerÃ­odo acadÃ©mico â†’ Nivel acadÃ©mico â†’ Horario / Grupo acadÃ©mico
```

El perÃ­odo abierto actual se precarga por defecto y puede cambiarse para consulta histÃ³rica. Los niveles solo se muestran si tienen grupos acadÃ©micos registrados para el perÃ­odo seleccionado; los horarios/grupos solo se muestran si pertenecen al perÃ­odo y nivel seleccionados. Un perÃ­odo cerrado permite consulta, pero no creaciÃ³n, modificaciÃ³n, matrÃ­cula, asistencia ni calificaciÃ³n.

La pantalla de Grupos acadÃ©micos es la excepciÃ³n: allÃ­ se crean las relaciones de perÃ­odo, nivel, horario, docente, aula, cupo y plan de cobro, por lo que no debe depender de filtros previos.

---

### 4.10 WhatsApp

Cada grupo tendrÃ¡ su propio link de WhatsApp.

Los grupos cambian por periodo.

El link de WhatsApp debe asociarse preferiblemente a `ofertas_academicas`, porque un mismo nivel puede existir en varias sucursales, horarios, modalidades o grupos.

El estudiante solo debe recibir el link de WhatsApp cuando el pago estÃ© aprobado.

El ingreso automÃ¡tico al grupo de WhatsApp es mejora futura y no debe implementarse como obligaciÃ³n de la primera versiÃ³n, salvo autorizaciÃ³n tÃ©cnica explÃ­cita.

---

### 4.11 Conceptos de pago

Mantener conceptos contables limpios.

No crear conceptos contables por nivel, horario, modalidad, sucursal o nÃºmero de cuota.

Conceptos esperados:

- `MAT` â€” MatrÃ­cula.
- `CUO` â€” Cuota.
- `PMA` â€” Pre-matrÃ­cula.
- `PEX` â€” Examen de nivelaciÃ³n.
- `VLI` â€” Venta de libro.
- `CHO` â€” Cambio de horario.
- `CAU` â€” Cargo por mora.
- `RGO` â€” Recargo por cuota vencida.
- `EOT` â€” Otros servicios en educaciÃ³n.

La informaciÃ³n acadÃ©mica debe venir desde:

```text
pago -> matricula -> oferta_academica -> nivel / horario / docente / sucursal
```

No desde el concepto contable.

---

### 4.12 Planes de cobro y cuotas

Un nivel puede tener matrÃ­cula mÃ¡s una o muchas cuotas.

Actualmente existe:

```text
MatrÃ­cula + 1 cuota final
```

El nuevo diseÃ±o debe permitir:

```text
MatrÃ­cula + N cuotas
```

Sin crear nuevos conceptos contables.

Usar:

- `planes_cobro`
- `detalle_plan_cobro`
- `obligaciones_pago_estudiante`
- `aplicaciones_pago`

Regla:

```text
MAT = MatrÃ­cula
CUO = Cualquier cuota
```

El nÃºmero de cuota debe manejarse como detalle operativo:

- `numero_cuota`
- `nombre_cargo`
- `fecha_vencimiento`
- `monto`
- `estado`

Ejemplo:

| Concepto | NÃºmero cuota | Nombre cargo | Monto |
|---|---:|---|---:|
| MAT | 0 | MatrÃ­cula | 1200.00 |
| CUO | 1 | Cuota 1 | 1100.00 |

Un pago puede aplicarse a una o varias obligaciones.

Un pago completo no es un concepto contable nuevo; es un pago que cubre matrÃ­cula mÃ¡s cuota o cuotas.

---

### 4.13 Montos confirmados

Montos base actuales:

| Modalidad | Pago completo | MatrÃ­cula / 1 pago | Cuota / 2 pago |
|---|---:|---:|---:|
| Intensivo | 2300.00 | 1200.00 | 1100.00 |
| Semi Intensivo | 1300.00 | 600.00 | 700.00 |

Estos valores deben parametrizarse en planes de cobro. No deben quedar quemados en cÃ³digo.

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
- Un link de pago puede tener cantidad mÃ¡xima de usos.
- Un link agotado debe poder reemplazarse.
- El link debe asociarse a concepto, obligaciÃ³n de pago o configuraciÃ³n definida.

---

### 4.15 Pagos y comprobantes

El estudiante puede pagar por:

- Link de pago.
- DepÃ³sito.
- Transferencia bancaria.

El sistema debe permitir subir comprobantes en:

- JPG.
- PNG.
- PDF.

Contabilidad podrÃ¡:

- Aprobar.
- Rechazar.
- Solicitar nuevo comprobante.

#### Flujo de pago por link

```text
Estudiante solicita link â†’ solicita_link
Admin llena URL           â†’ esperando_respuesta
Estudiante confirma       â†’ en_revision
Admin aprueba/rechaza     â†’ aprobado / rechazado
```

El grid de pagos del portal del estudiante debe mostrar:
- Badge de estado con color diferenciado por estado.
- BotÃ³n "Ya completÃ© el pago" visible solo cuando estado = `esperando_respuesta`.
- Enlace `<a>` directo al `link_pago_url` en la columna Link.
- Link de WhatsApp como botÃ³n verde cuando el pago estÃ© aprobado y la oferta tenga grupo WhatsApp configurado.

Si el pago es aprobado:

- Se confirma la matrÃ­cula, si corresponde.
- Se actualiza cupo.
- Se genera recibo.
- Se habilita link de WhatsApp.

Si el pago es rechazado:

- Se libera cupo reservado.
- No se entrega link de WhatsApp.

---

### 4.16 Recibos de caja

Todo pago aprobado debe generar o estar asociado a un recibo de caja.

El recibo debe conservar informaciÃ³n histÃ³rica:

- NÃºmero de recibo.
- Fecha.
- Hora.
- Cajero.
- Alumno.
- Concepto.
- AÃ±o.
- Periodo.
- Nivel.
- Horario.
- Valor.
- Forma de pago.
- Documento de referencia.

Un recibo emitido no debe modificarse directamente.

Correcciones deben hacerse por:

- AnulaciÃ³n.
- ReversiÃ³n.
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
- DepÃ³sito.
- Transferencia.
- Tarjeta.
- Link de pago.
- Cheque, si aplica en migraciÃ³n o caja.

El reporte de caja debe mantenerse contable y limpio.

No agrupar caja por nivel como concepto contable.

Si se requiere reporte financiero por nivel, debe obtenerse cruzando:

```text
pagos -> matriculas -> ofertas_academicas -> niveles_academicos
```

---

### 4.18 Calificaciones e historial acadÃ©mico

Las calificaciones se registran por matrÃ­cula/oferta acadÃ©mica.

No registrar calificaciÃ³n solo contra cÃ³digo de alumno.

Debe existir relaciÃ³n con:

- Estudiante.
- MatrÃ­cula.
- Oferta acadÃ©mica.
- Nivel.
- Periodo.
- Horario.
- Docente.

El portal del estudiante puede mostrar Ãºnicamente el nivel actual, segÃºn requerimiento funcional.

Pero la base de datos debe conservar historial completo para auditorÃ­a, avance acadÃ©mico y validaciones.

---

### 4.19 Libros e inventario

Los libros se relacionan con niveles acadÃ©micos.

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

### 4.20 CÃ³digos de catÃ¡logos y nomenclaturas

Todo catÃ¡logo o entidad de configuraciÃ³n previa debe tener:

- `id` como llave primaria estable.
- `codigo` Ãºnico, obligatorio y editable.

El cÃ³digo es la nomenclatura que se utilizarÃ¡ en reportes. No debe reutilizarse, aunque puede ser renombrado segÃºn la necesidad administrativa. Su longitud debe soportar la nomenclatura definida; usar hasta 50 caracteres como capacidad estÃ¡ndar.

La generaciÃ³n automÃ¡tica se configura en `nomenclaturas_codigos`. Esta tabla aplica Ãºnicamente a catÃ¡logos y configuraciones previas, nunca a transacciones como matrÃ­culas, pagos, recibos o asistencias.

Campos funcionales mÃ­nimos:

- Entidad destino.
- Formato, por ejemplo `BAN-{SECUENCIA:6}`.
- Longitud de secuencia.
- Secuencia actual.
- Estado.

La secuencia debe incrementarse dentro de una transacciÃ³n con bloqueo para no generar cÃ³digos repetidos. Si administraciÃ³n proporciona un cÃ³digo manual, debe respetarse y validarse su unicidad.

---

### 4.21 Gestiones de matrÃ­cula

No crear una tabla distinta por cada operaciÃ³n sobre una matrÃ­cula. Usar:

- `tipos_gestion_matricula`
- `gestiones_matricula`

Tipos iniciales:

- Cambio de horario.
- Retiro.
- CancelaciÃ³n.
- Cambio de modalidad.
- Traslado de sucursal.
- ExcepciÃ³n de matrÃ­cula.

Una gestiÃ³n debe conservar matrÃ­cula afectada, estado, motivo, oferta destino cuando aplique, decisiÃ³n financiera, pago asociado, datos antes/despuÃ©s y trazabilidad de solicitud, aprobaciÃ³n, rechazo, ejecuciÃ³n o cancelaciÃ³n.

La matrÃ­cula no se elimina. Un retiro debe conservar historial acadÃ©mico, pagos y auditorÃ­a. Un cambio de horario debe mover al estudiante Ãºnicamente a una oferta acadÃ©mica compatible y con cupo disponible.

---

### 4.22 Asistencia y acceso del docente

La asistencia se registra en `asistencias_estudiante`, por matrÃ­cula y fecha de clase. No permitir mÃ¡s de un registro para el mismo estudiante/matrÃ­cula y fecha.

Estados funcionales esperados:

- `presente`
- `falta`
- `justificada`
- `tardanza`

Cada registro debe indicar si cuenta como falta para aprobaciÃ³n. La cantidad de faltas se calcula desde asistencia y no debe depender de un valor ingresado manualmente en calificaciones.

La pantalla/transacciÃ³n â€œPasar listaâ€ es para el docente. Solo puede mostrar las ofertas acadÃ©micas asignadas al docente autenticado y los estudiantes con matrÃ­cula activa de esa oferta. No debe permitir al docente modificar matrÃ­cula, horario, pagos ni datos financieros.

Un usuario con rol `DOCENTE` debe tener obligatoriamente `docente_id`, relacionado de forma Ãºnica con `docentes`. Un docente solo puede estar asociado a un usuario.

---

### 4.23 Supervisor de docentes

Debe existir el rol `SUPERVISOR`. Tiene acceso global a todas las sucursales y a la gestiÃ³n acadÃ©mica, incluyendo docentes, grupos, asistencias, matrÃ­culas, calificaciones, historial, pagos y reportes.

El supervisor no se limita a un `docente_id` ni a sucursales asignadas. Las polÃ­ticas y middleware deben respetar este alcance global.

---

### 4.24 Origen del monto por concepto de pago

`conceptos_pago` debe definir cÃ³mo se obtiene el monto:

- `fijo`: usa `monto_fijo` configurado.
- `manual`: el cajero ingresa el monto, respetando lÃ­mites y autorizaciÃ³n cuando corresponda.
- `por_oferta`: se obtiene de la obligaciÃ³n/plan de cobro de la oferta acadÃ©mica.
- `por_inventario`: se obtiene del libro o inventario vendido.

Campos mÃ­nimos: `tipo_monto`, `monto_fijo`, `monto_minimo`, `monto_maximo` y `requiere_autorizacion_monto`.

`MAT`, `CUO` y `PMA` usan `por_oferta`; `VLI` usa `por_inventario`; `PEX` puede ser fijo; los demÃ¡s conceptos se parametrizan segÃºn la polÃ­tica administrativa.

---

### 4.25 Monitor de cupos por perÃ­odo

Debe existir una consulta de monitor de cupos por perÃ­odo acadÃ©mico, con filtros por perÃ­odo y sucursal. No requiere una tabla transaccional adicional; calcula informaciÃ³n en tiempo real desde `ofertas_academicas`.

Debe mostrar sucursal, plan, nivel, modalidad, horario, docente, cupo mÃ¡ximo, matriculados, reservados, disponibles y estado de oferta.

Estados visuales sugeridos:

- Verde: matrÃ­cula abierta y cupos disponibles.
- Azul: oferta que permite recibir cambio de horario compatible.
- Amarillo: pocos cupos disponibles.
- Rojo: sin cupos o matrÃ­cula cerrada.
- Gris: oferta cancelada.

`ofertas_academicas` debe permitir parametrizar si acepta cambios de horario. El monitor debe tener actualizaciÃ³n automÃ¡tica configurable mediante el parÃ¡metro `MONITOR_CUPOS_REFRESCO_SEGUNDOS`, con valor inicial de 300 segundos. La actualizaciÃ³n consulta la API; no requiere tarea programada.

---

### 4.26 Seguridad parametrizable: RBAC

El sistema debe utilizar seguridad parametrizable basada en roles y permisos (RBAC). No se permiten decisiones de autorizaciÃ³n fijas como `if rol == "ADMIN"`.

Los roles, permisos, mÃ³dulos y alcances se administran desde interfaz administrativa. Un usuario puede tener uno o varios roles. La autorizaciÃ³n efectiva debe provenir de `usuario_roles` y `rol_permisos`; `usuarios.rol_id`, si existe por compatibilidad temporal, no puede ser la fuente final de autorizaciÃ³n.

Roles iniciales de referencia, todos configurables:

- Superadministrador.
- Administrador general.
- Administrador de sucursal.
- Caja.
- MatrÃ­cula.
- Docente.
- Alumno.
- Consulta o auditorÃ­a.
- Supervisor.

El rol `SUPERVISOR` debe obtener su alcance global mediante permisos y configuraciÃ³n de alcance, no mediante validaciones fijas en cÃ³digo.

Los permisos se definen por mÃ³dulo, submÃ³dulo/opciÃ³n, pantalla y acciÃ³n. Acciones mÃ­nimas: consultar, crear, modificar, eliminar, aprobar, anular, imprimir, exportar, importar, asignar y configurar. Cada permiso debe tener cÃ³digo Ãºnico, por ejemplo `matriculas.crear`, `pagos.aprobar` o `reportes.caja.exportar`.

Alcances de datos soportados:

- Todas las sucursales.
- Una o varias sucursales autorizadas.
- Registros propios.
- InformaciÃ³n del alumno autenticado.
- Grupos u ofertas asignadas al docente.

La validaciÃ³n es obligatoria en dos niveles:

1. Frontend: ocultar o deshabilitar menÃºs, pantallas, botones y acciones no autorizadas.
2. Backend/API: middleware, guards, policies o mecanismo equivalente debe denegar la acciÃ³n sin permiso con HTTP `403 Forbidden`.

Aplicar denegaciÃ³n por defecto y principio de menor privilegio. Separar permisos de consultar, modificar, aprobar y anular. Los cambios de roles o permisos deben invalidar el cachÃ© de permisos y ejecutarse dentro de transacciones.

AdministraciÃ³n de usuarios debe permitir crear, consultar, modificar, activar/inactivar, asignar mÃºltiples roles y sucursales, restablecer contraseÃ±a, bloquear temporalmente, obligar cambio de contraseÃ±a y consultar Ãºltimo acceso e intentos fallidos. Al inactivar un usuario se deben invalidar sus sesiones o tokens. No se puede eliminar o inhabilitar al Ãºltimo superadministrador activo.

La matriz administrativa de roles y permisos debe permitir buscar permisos, marcar/desmarcar permisos por mÃ³dulo, copiar permisos entre roles y visualizar cantidad de usuarios por rol.

Auditar inicio/cierre de sesiÃ³n, intentos fallidos, cambios de contraseÃ±a, usuarios, roles, permisos, activaciones/inactivaciones, aprobaciones y anulaciones. La bitÃ¡cora debe guardar usuario, fecha/hora, IP, agente de usuario, acciÃ³n, mÃ³dulo, registro, valores antes/despuÃ©s, resultado y motivo de rechazo.

Antes de implementar cÃ³digo del mÃ³dulo de seguridad se debe presentar arquitectura, modelo de datos y flujo de validaciÃ³n de permisos.

La implementaciÃ³n completa de RBAC debe incluir ademÃ¡s:

- Endpoints REST administrativos para mÃ³dulos, opciones, permisos, roles, usuarios y asignaciones de roles.
- Matriz administrativa de roles y permisos, incluyendo copiar permisos, bÃºsqueda, selecciÃ³n total por mÃ³dulo y cantidad de usuarios por rol.
- AplicaciÃ³n del middleware `permiso:<codigo>` a todas las rutas protegidas.
- Servicio central de alcance de datos para global, sucursales asignadas, docente, alumno y registros propios.
- BitÃ¡cora automÃ¡tica de seguridad para operaciones permitidas y rechazadas.
- RevocaciÃ³n de sesiones o tokens al inactivar usuarios, bloqueo temporal, intentos fallidos y cambio obligatorio de contraseÃ±a cuando aplique.
- ProtecciÃ³n del Ãºltimo superadministrador activo.
- CachÃ© de permisos e invalidaciÃ³n inmediata al cambiar usuarios, roles o permisos.
- Pruebas de permisos `403`, mÃºltiples roles, alcance por sucursal/docente/alumno, revocaciÃ³n de sesiÃ³n y auditorÃ­a.

#### Estado de implementaciÃ³n RBAC

Completado:

- Tablas base de mÃ³dulos, opciones, permisos, asignaciÃ³n mÃºltiple de roles, sesiones, intentos y bitÃ¡cora.
- MigraciÃ³n de compatibilidad desde `usuarios.rol_id` hacia `usuario_roles`.
- Middleware `permiso:<codigo>` con respuesta HTTP `403`.
- Endpoints de catÃ¡logo RBAC, permisos por rol, copia de permisos, roles por usuario, auditorÃ­a y configuraciÃ³n de alcance.
- CatÃ¡logo inicial de mÃ³dulos/permisos y asignaciÃ³n inicial de permisos de Seguridad al rol administrativo.
- Tablas `alcances_rol` y `alcances_usuario` con tipos `global`, `sucursales`, `docente`, `alumno` y `propio`.
- Servicio `ResolutorAlcanceDatos` e integraciÃ³n inicial en consulta de ofertas acadÃ©micas para sucursal y docente.
- Prueba API inicial de denegaciÃ³n `403`.

- BitÃ¡cora tÃ©cnica de peticiones API en `bitacora_peticiones`, con mÃ©todo, ruta sin parÃ¡metros, usuario, HTTP, duraciÃ³n, IP, agente y fecha; no almacena cuerpos, contraseÃ±as ni tokens.
- Pantalla inicial de AuditorÃ­a y sesiones como monitor descendente de peticiones, con bÃºsqueda, paginaciÃ³n de 50 registros y refresco automÃ¡tico cada 15 segundos, protegida por `seguridad.consultar`.

Pendiente:

- Integrar el resolutor de alcance en estudiantes, matrÃ­culas, pagos, recibos, caja, calificaciones, inventario y reportes.
- Implementar restricciones de alumno y registros propios segÃºn cada entidad.
- AuditorÃ­a automÃ¡tica, revocaciÃ³n de sesiones/tokens, bloqueo e intentos fallidos de autenticaciÃ³n.
- Endpoints CRUD completos para mÃ³dulos, opciones, permisos y roles.
- Pantallas completas de auditorÃ­a, sesiones, intentos y gestiÃ³n visual de alcance.
- MenÃºs y acciones frontend gobernados por permisos efectivos.
- Pruebas integrales de autorizaciÃ³n y alcance.

#### Pendiente de cierre RBAC

- Aplicar `bloqueado_hasta` y `debe_cambiar_contrasena` durante el inicio de sesiÃ³n.
- Revocar tokens/sesiones al inactivar un usuario.
- CRUD administrativo completo para mÃ³dulos, opciones, permisos y roles.
- Pantallas de auditorÃ­a, sesiones e intentos de acceso.
- BÃºsqueda/filtros de permisos y conteo de usuarios por rol en la interfaz.
- MenÃºs y botones frontend dinÃ¡micos segÃºn permisos efectivos.
- Pantalla visual para administrar alcance por rol y usuario.
- Integrar el alcance en estudiantes, calificaciones, asistencias, recibos, caja, inventario y reportes; ya estÃ¡ iniciado en ofertas, matrÃ­culas y pagos.
- Pruebas de bloqueo, revocaciÃ³n, auditorÃ­a, mÃºltiples roles y alcance completo.

---

### 4.27 Compatibilidad de verbos HTTP en servidores restrictivos

El entorno de despliegue (SmarterASP / IIS) puede bloquear verbos HTTP distintos de `GET` y `POST`. Para garantizar operationabilidad sin romper la semÃ¡ntica REST, todas las rutas de escritura que originalmente usan `PUT`, `PATCH` o `DELETE` deben registrarse tambiÃ©n bajo `POST`, apuntando a los mismos mÃ©todos de controlador existentes. No se duplican controladores ni se crean rutas paralelas; se usa `Route::match([...], ...)` para aceptar ambos verbos en la misma ruta.

Reglas obligatorias:

- Para `update` (`PUT`/`PATCH`): usar `Route::match(['PUT', 'PATCH', 'POST'], ...)` o, dentro del macro `apiResourceProtegido`, la rama `update` ya estÃ¡ configurada con esos tres verbos.
- Para `destroy` (`DELETE`): usar `Route::match(['DELETE', 'POST'], ...)` o la rama `destroy` del macro, igualmente configurada.
- Las rutas explÃ­citas `Route::put(...)` y `Route::delete(...)` estÃ¡n prohibidas en `routes/api.php` y `routes/web.php`; deben reescribirse con `Route::match([...], ...)`.
- El frontend puede seguir usando `window.axios.put(...)` y `window.axios.delete(...)`, o cambiar a `window.axios.post(...)` si el servidor exige POST. Ambas formas llegan al mismo controlador.
- Los tests pueden usar `$this->putJson(...)` / `$this->deleteJson(...)` o `$this->postJson(...)`; ambas rutas existen.
- Este patrÃ³n aplica Ãºnicamente a `update` y `destroy`. `store` ya es `POST`, y `index`/`show` son `GET` (no afectados).

---

## 5. Tablas principales esperadas

No crear nombres alternativos sin autorizaciÃ³n.

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

## 6. Estados estÃ¡ndar

Usar estados consistentes.

### Estado general

- `activo`
- `inactivo`

### Oferta acadÃ©mica

- `borrador`
- `abierto`
- `lleno`
- `cerrado`
- `cancelado`

### MatrÃ­cula

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

### CalificaciÃ³n

- `pendiente`
- `registrado`
- `corregido`
- `anulado`

### Historial acadÃ©mico

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
  "mensaje": "OperaciÃ³n exitosa",
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

No retornar errores tÃ©cnicos crudos al usuario final.

---

## 8. Validaciones obligatorias

Cada endpoint de escritura debe tener validaciÃ³n formal mediante Request/DTO equivalente.

Validaciones mÃ­nimas:

- Campos obligatorios.
- Tipos de datos.
- Longitudes.
- Existencia de llaves forÃ¡neas.
- Estados vÃ¡lidos.
- Fechas vÃ¡lidas.
- Montos mayores o iguales a cero.
- Cupos no negativos.
- Horario con hora fin mayor que hora inicio, salvo horario 24 horas.
- No duplicar estudiante por identidad o cÃ³digo.
- No duplicar recibo oficial.
- No permitir pago aprobado sin concepto.
- No permitir matrÃ­cula en oferta cerrada o llena.

---

## 9. AuditorÃ­a

Toda tabla operativa debe guardar:

- `creado_por`
- `creado_en`
- `actualizado_por`
- `actualizado_en`

En operaciones sensibles, guardar ademÃ¡s:

- Usuario que aprobÃ³.
- Fecha de aprobaciÃ³n.
- Usuario que anulÃ³.
- Fecha de anulaciÃ³n.
- Motivo de rechazo o anulaciÃ³n.

Operaciones sensibles:

- Aprobar pago.
- Rechazar pago.
- Anular recibo.
- Cerrar caja.
- Reabrir caja.
- Cambiar cupo.
- Matricular por excepciÃ³n.
- Modificar calificaciÃ³n.
- Ajustar inventario.

---

## 10. MigraciÃ³n desde sistema actual

Puede existir informaciÃ³n histÃ³rica del sistema actual.

No migrar directamente a tablas productivas sin tabla intermedia cuando haya datos inconsistentes.

Usar tablas temporales o de staging, por ejemplo:

- `pagos_migrados_sistema_anterior`
- `estudiantes_migrados_sistema_anterior`
- `niveles_migrados_sistema_anterior`

La migraciÃ³n debe conservar:

- NÃºmero de recibo original.
- Fecha original.
- Hora original.
- Cajero u operador original.
- CÃ³digo de estudiante original.
- Nombre histÃ³rico.
- Concepto original.
- Nivel histÃ³rico.
- Horario histÃ³rico.
- Forma de pago histÃ³rica.
- Banco y cuenta original.
- Estado original.

No crear nuevos conceptos contables durante la migraciÃ³n si el dato puede mapearse a `MAT`, `CUO`, `VLI`, etc.

---

## 11. Reportes esperados

### Reportes acadÃ©micos

- Matriculados por periodo.
- Matriculados por sucursal.
- Matriculados por departamento acadÃ©mico.
- Matriculados por plan de estudio.
- Matriculados por nivel.
- Matriculados por horario.
- Matriculados por docente.
- Alumnos por grupo para entregar al maestro.
- Calificaciones por grupo.
- Nivel actual del estudiante.
- Estudiantes matriculados por docente, filtrable por aÃ±o, perÃ­odo, concepto, nivel, horario y estado.

### Reportes financieros

- Reporte de caja por cajero.
- Cierre de caja por fecha.
- Ingresos por concepto de pago.
- Ingresos por forma de pago.
- Ingresos por sucursal.
- Ingresos por periodo.
- Ingresos por nivel, cruzando matrÃ­cula.
- Pagos pendientes.
- Pagos rechazados.
- Recibos anulados.

### Reportes de recibos y caja autorizados

Se deben implementar los siguientes reportes para EducaciÃ³n:

1. Recibos por orden numÃ©rico.
2. DepÃ³sitos.
3. Comprobantes de caja.
4. Recibos segÃºn forma de pago.
5. Recibos con depÃ³sito.
6. Recibos de libros.
7. Recibos por matrÃ­cula.
8. Recibos por cuota.
9. ReimpresiÃ³n de recibos.
10. Cambios de horarios.
11. ReposiciÃ³n de libreta.
12. Otros servicios de educaciÃ³n.
13. Consulta general de recibos.
14. Recibos de EducaciÃ³n por cierre diario.
15. Resumen de ingresos.

No incluir reportes de Salud, medicamentos, odontologÃ­a, procedimientos ni cierres diarios de Salud dentro de esta plataforma, salvo autorizaciÃ³n futura explÃ­cita.

Todo reporte debe requerir `fecha_desde` y `fecha_hasta`. Puede incluir filtros opcionales de sucursal, perÃ­odo, docente, nivel, horario, concepto de pago, forma de pago, estado y cajero, segÃºn aplique.

Los reportes deben ofrecer dos salidas:

- Ver en pantalla: resultado paginado con totales y filtros aplicados.
- Exportar a Excel: resultado completo con los mismos filtros, fecha de generaciÃ³n, usuario generador y totales cuando correspondan.

El docente solo puede consultar o exportar informaciÃ³n de sus propias ofertas acadÃ©micas. Supervisor y administrador tienen alcance global conforme a sus permisos.

Las reimpresiones de recibos deben quedar en una bitÃ¡cora con recibo, usuario, fecha, motivo y nÃºmero de reimpresiÃ³n.

Todo reporte, recibo de caja y constancia debe disponer de una salida oficial en PDF real. El encabezado debe incluir exactamente `Cursos San Vicente de Paul`; no se permiten documentos HTML como entrega final. La consulta en pantalla y la exportaciÃ³n a Excel pueden mantenerse como salidas operativas complementarias.

#### Estado actual de documentos PDF

Completado:

- Se instalÃ³ `barryvdh/laravel-dompdf` y se creÃ³ el servicio reutilizable `GeneradorPdfInstitucional`.
- Los recibos del portal y la constancia de nivel actual se descargan como PDF real.
- Los PDF incorporan el encabezado institucional exacto `Cursos San Vicente de Paul`.
- El panel administrativo ofrece PDF oficial y Excel para reportes disponibles.

Pendiente:

- DocumentaciÃ³n Swagger/OpenAPI (no existe actualmente `public/swagger.json`).
- Validar en producciÃ³n la descarga de PDFs tras los despliegues pendientes.

#### Estado actual de monitor de cupos y pruebas

La suite automatizada contiene **119 pruebas** (118 Feature + 1 Unit) con **348 aserciones** aprobadas.

Completado:

- API `GET /api/v1/monitor-cupos` protegida por `matriculas.consultar`, con alcance RBAC, filtros por perÃ­odo/sucursal, cÃ¡lculo de cupos y colores funcionales.
- Pantalla administrativa del Monitor de cupos con perÃ­odo abierto preseleccionado, filtros por perÃ­odo y sucursal, actualizaciÃ³n automÃ¡tica configurable y presentaciÃ³n sin IDs tÃ©cnicos.

Pendiente:

- Agregar pruebas de datos para cada variante de reporte operativo.
- AceptaciÃ³n en escritorio confirmada pendiente.

### Reportes de inventario

- Existencia por sucursal.
- Libros por nivel.
- Ventas de libros.
- Kardex de movimientos.
- Libros con existencia baja.

---

## 12. Pruebas mÃ­nimas

Cada mÃ³dulo debe incluir pruebas.

Pruebas crÃ­ticas:

- Crear sucursal.
- Crear departamento acadÃ©mico.
- Crear plan de estudio.
- Crear nivel.
- Crear periodo.
- Crear horario.
- Crear oferta acadÃ©mica.
- No permitir matrÃ­cula si no hay cupo.
- Reservar cupo al iniciar matrÃ­cula.
- Liberar cupo al rechazar pago.
- Confirmar cupo al aprobar pago.
- Generar obligaciones desde plan de cobro.
- Aplicar pago a una o varias obligaciones.
- Generar recibo al aprobar pago.
- Agrupar cierre de caja por concepto y mÃ©todo de pago.
- Validar nota mÃ­nima y faltas.
- Descontar inventario al vender libro.

---

## 13. Orden recomendado de construcciÃ³n

No construir la app mÃ³vil antes de tener API estable.

Orden recomendado:

1. Backend API base.
2. Seguridad, usuarios, roles y sucursales.
3. CatÃ¡logos acadÃ©micos.
4. Periodos, horarios y ofertas acadÃ©micas.
5. Estudiantes y accesos.
6. MatrÃ­cula online.
7. Planes de cobro y obligaciones.
8. Pagos, comprobantes y recibos.
9. Caja y cierre de cajero.
10. Calificaciones e historial acadÃ©mico.
11. Libros e inventario.
12. Reportes.
13. Web administrativa.
14. Portal estudiante.
15. App mÃ³vil.

---

## 14. QuÃ© no debe hacer Codex

No hacer lo siguiente sin autorizaciÃ³n explÃ­cita:

- Cambiar nombres de tablas definidos.
- Cambiar nombres de campos de negocio ya aprobados.
- Crear conceptos contables por cada cuota.
- Crear conceptos contables por cada nivel.
- Mezclar sucursal con departamento acadÃ©mico.
- Mezclar modalidad con departamento acadÃ©mico.
- Matricular directamente contra horario sin oferta acadÃ©mica.
- Guardar contraseÃ±as en texto plano.
- Mostrar correo o telÃ©fono completo en validaciÃ³n de estudiante existente.
- Aprobar pagos automÃ¡ticamente sin regla definida.
- Entregar link de WhatsApp antes de aprobar el pago.
- Descontar inventario sin recibo, pago aprobado o movimiento autorizado.
- Eliminar historial acadÃ©mico por mostrar solo nivel actual al estudiante.

---

## 15. Criterios de aceptaciÃ³n generales

Para cada tarea generada, cumplir:

1. Migraciones creadas.
2. Modelos creados.
3. Relaciones definidas.
4. Requests de validaciÃ³n creados.
5. Endpoints REST creados.
6. Resources JSON creados.
7. Seeders cuando aplique.
8. Pruebas automatizadas bÃ¡sicas.
9. DocumentaciÃ³n mÃ­nima de endpoints.
10. Cumplimiento de nombres de tablas y campos en espaÃ±ol.
11. Cumplimiento de reglas crÃ­ticas del negocio.
12. No introducir datos quemados si deben ser parametrizables.

---

## 16. Prompt base para nuevas tareas

Cuando se asigne una tarea a Codex, usar este patrÃ³n:

```text
Lee primero AGENTS.md y los documentos en /docs.

Implementa el mÃ³dulo: [nombre del mÃ³dulo].

Respeta:
- Tablas y campos en espaÃ±ol.
- Reglas de negocio del AGENTS.md.
- Arquitectura API centralizada.
- Sistema multi-sucursal.
- MatrÃ­cula contra ofertas_academicas.
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
- DocumentaciÃ³n breve.
```

---

## 17. Fuente de verdad

El archivo `AGENTS.md` define reglas permanentes para Codex.

Los documentos en `/docs` complementan el detalle funcional.

Si hay conflicto entre una tarea puntual y `AGENTS.md`, Codex debe detenerse y pedir confirmaciÃ³n antes de cambiar una regla crÃ­tica.

---

## 18. Seguridad de APIs y cÃ³digos de error

Toda API administrativa debe requerir autenticaciÃ³n Sanctum y un permiso RBAC
especÃ­fico. No utilizar el rol heredado `ADMIN` o validaciones fijas por rol
como mecanismo de autorizaciÃ³n.

Permisos mÃ­nimos por acciÃ³n:

- Consulta: `<modulo>.consultar`.
- CreaciÃ³n: `<modulo>.crear`.
- ModificaciÃ³n: `<modulo>.modificar`.
- AprobaciÃ³n o rechazo: `<modulo>.aprobar`.
- ConfiguraciÃ³n de seguridad, roles y usuarios: `seguridad.configurar`.

Las rutas de inicio de sesiÃ³n son pÃºblicas. Las operaciones del portal del
estudiante que cambian datos, tales como reservar, confirmar o liberar una
matrÃ­cula, deben requerir token Sanctum emitido para `AccesoEstudiante` y
validar que la matrÃ­cula pertenezca al estudiante autenticado. Un usuario
administrativo no sustituye la sesiÃ³n de un estudiante ni viceversa.

Las respuestas de error de la API deben conservar siempre un cÃ³digo funcional
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
detalles tÃ©cnicos. Los errores de validaciÃ³n deben incluir adicionalmente el
objeto `errores` con el detalle de cada campo.

---

## 19. PatrÃ³n reutilizable de implementaciÃ³n RBAC

No duplicar la definiciÃ³n de permisos, middleware o lÃ³gica de rutas al crear
un mÃ³dulo, API, pantalla, menÃº o botÃ³n. La fuente tÃ©cnica del patrÃ³n es
`docs/PATRON_IMPLEMENTACION_RBAC.md`.

Reglas obligatorias:

1. Declarar el mÃ³dulo inicial en `config/rbac.php` y sincronizarlo con
   `SeguridadRbacSeeder` y `RegistroPermisosService`.
2. Para recursos REST administrativos usar
   `Route::apiResourceProtegido(...)`; asigna automÃ¡ticamente el permiso segÃºn
   la acciÃ³n REST.
3. Para operaciones no REST usar `Route::accionProtegida(...)` con el permiso
   explÃ­cito, por ejemplo `pagos.aprobar`.
4. Usar `@hasperm` y lÃ³gica Alpine.js en Blade para menÃºs, pantallas y
   botones, usando los permisos efectivos recibidos en login.
5. El frontend nunca reemplaza la validaciÃ³n backend ni la validaciÃ³n de
   alcance por sucursal, docente, alumno o propietario.
6. Cada mÃ³dulo nuevo debe incluir prueba de permiso permitido y denegado.

---

## 20. Estado actual de implementaciÃ³n

### Inventario del cÃ³digo fuente

| Capa | Cantidad | Notas |
|------|:--------:|-------|
| Migraciones | 59 | 3 framework defaults + 56 custom (2 tablas aÃºn pendientes) |
| Modelos | 54 | Cubre la mayorÃ­a del dominio |
| Controladores | 33 | 1 base + 32 API (Auth, Seguridad, AcadÃ©mico, Estudiantes, MatrÃ­culas, Pagos, Caja, Reportes, Inventario, ConfiguraciÃ³n Flujo) |
| Middleware | 3 | `VerificarPermiso`, `AutenticarEstudiante`, `RegistrarPeticion` |
| Servicios | 4 | `ResolutorAlcanceDatos`, `RegistroPermisosService`, `CachePermisosService`, `ServicioBitacora` |
| Seeders | 17 | CatÃ¡logos acadÃ©micos, RBAC, usuario admin, conceptos, mÃ©todos, LibroSeeder, etc. |
| Pruebas | 133 | 132 Feature + 1 Unit en 17 clases de prueba |
| Vistas Blade | 26 | 12 admin + 8 portal estudiante + 3 layouts + 2 root + 1 auth |
| Rutas | 3 archivos | `api.php`, `web.php`, `console.php` |
| Config personalizado | 2 | `rbac.php`, `seguridad.php` |
| DocumentaciÃ³n | 20 archivos | En `docs/` |

### Tablas creadas por migraciÃ³n

**RBAC/Seguridad (18 tablas):** `sucursales`, `usuarios`, `roles`, `modulos`, `opciones_modulo`, `permisos`, `usuario_roles`, `rol_permisos`, `usuario_sucursales`, `nomenclaturas_codigos`, `sesiones_usuario`, `intentos_acceso`, `bitacora_seguridad`, `bitacora_peticiones`, `alcances_rol`, `alcances_usuario`, `sessions` (modificada), `cache` (framework).

**CatÃ¡logos acadÃ©micos (13 tablas):** `departamentos_academicos`, `planes_estudio`, `versiones_plan_estudio`, `modalidades`, `niveles_academicos`, `nivel_modalidades`, `prerrequisitos_nivel`, `horarios`, `horario_dias`, `docentes`, `aulas`, `periodos_academicos`, `ofertas_academicas`.

**Estudiantes (3 tablas):** `estudiantes`, `accesos_estudiante`, `solicitudes_actualizacion_datos`.

**Pagos/Cobros (10 tablas):** `conceptos_pago`, `metodos_pago`, `planes_cobro`, `detalle_plan_cobro`, `matriculas`, `obligaciones_pago_estudiante`, `tipos_gestion_matricula`, `gestiones_matricula`, `cuentas_bancarias`, `enlaces_pago`.

**Transacciones (8 tablas):** `pagos`, `aplicaciones_pago`, `comprobantes_pago`, `recibos_caja`, `sesiones_caja`, `detalle_cierre_caja`, `calificaciones`, `historial_academico`.

**Reglas/NivelaciÃ³n (2 tablas):** `reglas_aprobacion`, `evaluaciones_nivelacion`.

**Inventario/Libros (4 tablas):** `libros`, `libro_niveles`, `inventario_libros`, `movimientos_inventario_libros`.

### Tablas pendientes de crear

Las siguientes tablas estÃ¡n definidas en la especificaciÃ³n pero no tienen migraciÃ³n:

- `grupos_whatsapp`
- `asistencias_estudiante`

### RBAC â€” Estado de implementaciÃ³n

**Completado:**

- Tablas base: mÃ³dulos, opciones, permisos, asignaciÃ³n mÃºltiple de roles, sesiones, intentos y bitÃ¡cora.
- MigraciÃ³n de compatibilidad desde `usuarios.rol_id` hacia `usuario_roles`.
- Middleware `permiso:<codigo>` con respuesta HTTP `403`.
- Endpoints de catÃ¡logo RBAC, permisos por rol, copia de permisos, roles por usuario, auditorÃ­a y configuraciÃ³n de alcance.
- CatÃ¡logo inicial de mÃ³dulos/permisos y asignaciÃ³n inicial de permisos de Seguridad al rol administrativo.
- Tablas `alcances_rol` y `alcances_usuario` con tipos `global`, `sucursales`, `docente`, `alumno` y `propio`.
- Servicio `ResolutorAlcanceDatos` e integraciÃ³n en consulta de ofertas acadÃ©micas.
- BitÃ¡cora tÃ©cnica de peticiones API en `bitacora_peticiones`.
- Pantalla de AuditorÃ­a y sesiones como monitor descendente de peticiones.
- Matriz administrativa de roles y permisos en `seguridad.blade.php` (modal con checkboxes, bÃºsqueda, copiar entre roles, select-all por mÃ³dulo).

**Pendiente:**

- Integrar el resolutor de alcance en estudiantes, matrÃ­culas, pagos, recibos, caja, calificaciones, inventario y reportes.
- AuditorÃ­a automÃ¡tica con valores antes/despuÃ©s, revocaciÃ³n de sesiones/tokens, bloqueo e intentos fallidos.
- CRUD administrativo completo para mÃ³dulos, opciones, permisos y roles.
- Pantallas de auditorÃ­a, sesiones e intentos de acceso.
- MenÃºs y botones frontend dinÃ¡micos segÃºn permisos efectivos.
- Pruebas de bloqueo, revocaciÃ³n, auditorÃ­a, mÃºltiples roles y alcance completo.

### Portal del estudiante â€” Estado de implementaciÃ³n

**Completado:**

- `AutenticarEstudiante` middleware con tokens SHA-256 personalizados.
- `EstudianteAuthController`: login, registro, activar, portal, logout.
- `PortalEstudianteController`: 8 endpoints (misOfertas, reservarMatricula, misMatriculas, subirComprobante, misPagos, misRecibos, miNivel, whatsapp).
- 9 vistas Blade: login, registro, activar, dashboard, matricula, comprobante, pagos, recibos + layout portal.
- 8 rutas web para pÃ¡ginas del portal.
- `PortalEstudianteTest.php`: 14 pruebas cubriendo auth, portal, ofertas, matrÃ­cula, comprobantes, pagos, recibos, nivel, WhatsApp, cerrar sesiÃ³n.

**Correcciones aplicadas (2026-07-26):**

- `registrarPago()` en `PortalEstudianteController.php`: se cambiÃ³ `creado_por` de `$estudiante->id` (ID de `estudiantes`) a `null`, porque la FK en `aplicaciones_pago` apunta a `users` y el portal del estudiante no autentica contra `users`. El error se manifestaba como `SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed`.
- `registrarPago()`: se corrigiÃ³ `monto_aplicado` de `0` al monto real de cada obligaciÃ³n (`$obligacion->monto`) usando un mapa de colecciÃ³n.
- Login del portal (`login.blade.php`): se aÃ±adiÃ³ `window.extractError` y `window.extractErrorCode` inline, ya que la pÃ¡gina es standalone y no hereda el layout `portal.blade.php` donde se definen esas funciones.
- Vista `pagos.blade.php` (Mis Pagos): se agregÃ³ botÃ³n "Nuevo Pago" con modal de selecciÃ³n de obligaciones pendientes, mÃ©todo de pago, referencia y comprobante opcional; columna "Comprobante" en la tabla de historial para subir comprobante a pagos existentes.
- Se corrigiÃ³ `EstudianteAuthController.php::portal()`: se agregÃ³ `matricula_activa_id` en la respuesta JSON para identificar la matrÃ­cula activa real cuyas obligaciones se retornan, y se actualizaron `pagos.blade.php` y `comprobante.blade.php` para usar `data.matricula_activa_id` en lugar de `data.matriculas[0].id`, que podÃ­a corresponder a otra matrÃ­cula y causar el error "No hay obligaciones pendientes para esta matrÃ­cula" al pagar conceptos distintos a la matrÃ­cula inicial.

**Correcciones y mejoras (2026-07-30):**

- Grid de pagos: se agregÃ³ `metodo_pago` como objeto completo en respuesta API (con `permite_link_pago`, `requiere_proveedor`) para que el badge de link funcione correctamente.
- Grid de pagos: se agregÃ³ `whatsapp_link` y `whatsapp_grupo` en respuesta API para pagos aprobados con grupo WhatsApp configurado.
- Vista `pagos.blade.php`: columna Link ahora muestra `<a>` directo al `link_pago_url` en lugar de badge "Disponible".
- Vista `pagos.blade.php`: botÃ³n verde de WhatsApp en acciones para pagos aprobados con grupo (mobile y desktop).
- Nuevo estado `esperando_respuesta` en el flujo de pago por link: Admin llena URL â†’ `esperando_respuesta`, Estudiante confirma â†’ `en_revision`.
- Vista `pagos.blade.php`: badge, banner, highlight y botÃ³n "Ya completÃ© el pago" para el estado `esperando_respuesta` con color morado.
- `actualizarLink` en `PagoController.php`: cambia a `esperando_respuesta` en lugar de `en_revision`.
- `confirmarLinkPago` en `PortalEstudianteController.php`: acepta `esperando_respuesta` como estado previo.

**Pendiente:**

- ValidaciÃ³n en producciÃ³n.
- Descarga de recibos en PDF desde el portal.

### MÃ³dulo Inventario/Libros â€” Estado de implementaciÃ³n

**Completado:**

- Migraciones: `libros`, `libro_niveles`, `inventario_libros`, `movimientos_inventario_libros`.
- Modelos: `Libro`, `LibroNivel`, `InventarioLibro`, `MovimientoInventarioLibro` con relaciones, scopes y casts.
- Controladores: `LibroController` (CRUD con bÃºsqueda, niveles) e `InventarioLibroController` (stock, ajustar con bloqueo pesimista, vender, kardex).
- 10 endpoints REST protegidos por `inventario.*`:
  - `GET/POST/PUT /api/v1/inventario/libros`
  - `GET/POST /api/v1/inventario/stock`
  - `GET /api/v1/inventario/stock/{id}`
  - `POST /api/v1/inventario/stock/{id}/ajustar`
  - `POST /api/v1/inventario/stock/{id}/vender`
  - `GET /api/v1/inventario/kardex`
- 14 tests (44 assertions): CRUD, stock, ajustes, ventas, kardex, permiso 403.
- Seeder `LibroSeeder`: 5 libros de inglÃ©s con inventario inicial (25 uds) en SPS y TGU.
- Vista admin `inventario.blade.php` con 3 tabs (CatÃ¡logo, Existencias, Kardex) y modales CRUD, ajustar, vender.
- MenÃº sidebar en `admin.blade.php` protegido por `inventario.*`.
- MÃ³dulo `inventario` en `config/rbac.php` con opciones `inventario.libros`, `inventario.stock`, `inventario.ventas`.

### Pantallas administrativas â€” Estado de implementaciÃ³n

**Completado:**

- Dashboard
- CatÃ¡logos AcadÃ©micos (sucursales, departamentos, planes, niveles, modalidades, horarios, docentes, aulas, perÃ­odos, conceptos, mÃ©todos)
- Ofertas y Cupos
- Monitor de Cupos (con auto-refresh configurable, colores funcionales, filtros por perÃ­odo/sucursal)
- Estudiantes (CRUD + Ficha Integral con tabs Datos/MatrÃ­culas/Pagos/Recibos/Calificaciones)
- MatrÃ­cula (listado, reserva, confirmaciÃ³n, cancelaciÃ³n + tab Gestiones de MatrÃ­cula)
- Calificaciones (filtros cascada PerÃ­odoâ†’Nivelâ†’Grupo, tabla editable notas/faltas, resultado en cliente)
- Pagos (visor de comprobantes, aprobar/rechazar, recibos con detalle/anular/reimprimir)
- Caja (sesiones, cierre)
- Reportes (18 endpoints cableados con columnas dinÃ¡micas, totales, paginaciÃ³n)
- Seguridad (usuarios, roles, matriz permisos con checkboxes, copiar entre roles, bÃºsqueda, ConfiguraciÃ³n Flujo con modal compacto, validaciÃ³n Ãºnica combo y eliminaciÃ³n forzada)
- Inventario y Libros (catÃ¡logo, existencias, kardex, ajustar, vender)

**Pendiente:**

- Grupos WhatsApp
- Asistencias (pasar lista docente)
- Exportar Excel en Reportes (deshabilitado con tooltip)
- MenÃºs y botones frontend gobernados 100% por permisos efectivos (avanzado, parcial)

### Correcciones de infraestructura realizadas

- Alpine.js importado e iniciado explÃ­citamente en `resources/js/app.js` (bloqueante #1, panel cargaba vacÃ­o).
- `RegistroPermisosService` extendido para generar permisos mÃ³dulo-nivel (`<modulo>.<accion>`), necesarios para middleware `permission:<codigo>`.
- Se corrigieron todas las referencias a columnas inexistentes `nombres,apellidos` â†’ `nombre,apellido` en 5 controladores (bug latente MySQL/SQLite).
- URLs rotas corregidas en vistas admin y portal.

## 21. Sistema estandarizado de cÃ³digos de error

Toda respuesta de error de la API debe usar `App\Helpers\RespuestaError` para garantizar
formato uniforme y auditorÃ­a automÃ¡tica.

### Formato de respuesta

```json
{
  "resultado": "R",
  "codigo": 422,
  "codigo_error": "422_CONFLICTO_HORARIO",
  "mensaje": "Mensaje para el usuario final",
  "mensaje_tecnico": "Detalle tÃ©cnico para depuraciÃ³n",
  "errores": { "campo": ["Error de validaciÃ³n"] }
}
```

### Uso en controladores

```php
return RespuestaError::make('422_MI_CODIGO', 422, 'Mensaje usuario', 'Mensaje tÃ©cnico')
    ->response($request);
```

### MÃ©todos de fÃ¡brica predefinidos

- `RespuestaError::validacion($errores)` â†’ `422_VALIDACION`
- `RespuestaError::noEncontrado($entidad)` â†’ `404_NO_ENCONTRADO`
- `RespuestaError::sinPermiso($permiso)` â†’ `403_SIN_PERMISO`
- `RespuestaError::noAutenticado()` â†’ `401_NO_AUTENTICADO`
- `RespuestaError::credencialesInvalidas()` â†’ `401_CREDENCIALES_INVALIDAS`
- `RespuestaError::interno($mensajeTecnico)` â†’ `500_ERROR_INTERNO`
- `RespuestaError::make($codigo, $http, $msgUsuario, $msgTecnico)` â†’ cÃ³digo personalizado

### Conteo de errores en bitÃ¡cora

`RespuestaError::response()` registra automÃ¡ticamente en `bitacora_seguridad` con:
- `accion = error_{codigo_error}`
- `resultado = rechazado`
- `motivo = mensaje_tecnico`

### Frontend

Toda captura de error debe usar `window.extractError(e, fallback)` en lugar de
acceder directamente a `e.response.data.mensaje`. La funciÃ³n prueba internamente
`mensaje`, `mensaje_usuario`, `message`, `error` y `errores` en ese orden.

```js
catch(e) {
    this.error = window.extractError(e, 'Mensaje por defecto');
}
```

Para obtener el cÃ³digo de error: `window.extractErrorCode(e)`.

### Registro de cÃ³digos de error

| CÃ³digo | HTTP | DescripciÃ³n |
|---|---|---|
| `401_NO_AUTENTICADO` | 401 | Token faltante o invÃ¡lido |
| `401_CREDENCIALES_INVALIDAS` | 401 | Credenciales incorrectas |
| `403_SIN_PERMISO` | 403 | Permiso RBAC denegado |
| `404_NO_ENCONTRADO` | 404 | Recurso no existe |
| `422_VALIDACION` | 422 | Error de validaciÃ³n de campos |
| `422_OFERTA_NO_PERTENECE_SUCURSAL` | 422 | Oferta no corresponde a sucursal del estudiante |
| `422_OFERTA_NO_ABIERTA` | 422 | Oferta no estÃ¡ en estado abierto |
| `422_SIN_CUPO` | 422 | Sin cupos disponibles |
| `422_MATRICULA_DUPLICADA` | 422 | Ya existe matrÃ­cula activa en la misma oferta |
| `422_CONFLICTO_HORARIO` | 422 | El horario choca con otra matrÃ­cula activa |
| `500_ERROR_INTERNO` | 500 | Error interno del servidor |

Cada nuevo mÃ³dulo debe registrar sus cÃ³digos de error en esta tabla.

### Despliegue

- Workflow GitHub Actions en `.github/workflows/desplegar-smarterasp.yml`.
- Despliegue FTPS a SmarterASP.
- Verificar ejecuciÃ³n correcta tras cada push a `main`.


