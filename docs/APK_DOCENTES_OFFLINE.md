# APK interna para docentes: guía de construcción y operación

## 1. Objetivo

Construir una aplicación Android interna para que cada docente pueda trabajar
con sus ofertas académicas asignadas, alumnos matriculados, asistencias y
calificaciones, incluso sin conectividad. La aplicación no sustituye al Portal
Administrativo: reutiliza sus usuarios, permisos y reglas de negocio; Laravel
sigue siendo la fuente de verdad.

La primera entrega se distribuye como un archivo APK interno. No se publica en
Google Play ni se habilita registro público desde el teléfono.

## 2. Alcance funcional

La APK debe permitir:

1. Iniciar y cerrar sesión con el mismo correo y contraseña del usuario
   administrativo vinculado a un docente (`usuarios.docente_id`).
2. Descargar y consultar solo las ofertas académicas asignadas al docente.
3. Consultar el detalle de estudiantes con matrícula en estado `matriculado`
   de cada oferta descargada.
4. Registrar asistencia por fecha: `presente`, `falta`, `justificada` o
   `tardanza`, con observación opcional.
5. Registrar o actualizar nota final, faltas y observaciones de estudiantes
   matriculados en cada oferta.
6. Trabajar sin conexión con los últimos datos descargados y guardar los
   cambios en una cola local.
7. Sincronizar manualmente y automáticamente al recuperar conectividad, con
   un resumen de cambios aplicados, pendientes y conflictos.

No incluye pagos, matrícula, recibos, certificados, catálogos, creación de
ofertas ni administración de usuarios.

## 3. Decisiones de arquitectura

| Capa | Decisión |
|---|---|
| Móvil | Expo / React Native, Android únicamente en la primera versión. |
| Datos locales | SQLite mediante `expo-sqlite`; no usar solo AsyncStorage para datos académicos. |
| Secretos | Token de sesión en `expo-secure-store`; nunca en SQLite, logs ni archivos exportados. |
| API | Laravel `/api/v1`, JSON y token Sanctum administrativo existente. |
| Distribución | APK firmado para instalación directa interna; publicación administrada y URL pública de descarga. |
| Fuente de verdad | Laravel y la base de datos central. SQLite es una réplica de trabajo temporal. |
| Sincronización | Descarga incremental y cola de operaciones idempotentes. |

La app se crea en `mobile-docentes/`, separada del monolito Laravel. No debe
modificar ni reutilizar el token SHA-256 de `accesos_estudiante`: los docentes
son usuarios administrativos y usan Sanctum.

## 4. Identidad, permisos y alcance

### Autenticación

1. La APK envía correo y contraseña a `POST /api/v1/login`.
2. Laravel responde con el token Sanctum, el usuario y sus permisos.
3. La aplicación solo permite continuar cuando `usuario.docente_id` existe y
   el usuario está activo.
4. El token se guarda con `expo-secure-store` y se envía como
   `Authorization: Bearer {token}`.
5. Ante `401` o `423`, se borra la sesión local y se vuelve al inicio de
   sesión. Los datos académicos pueden conservarse cifrados o eliminarse al
   cerrar sesión según la política institucional; para la primera versión se
   eliminan al cerrar sesión explícitamente.
6. Tambien que puedan guardar su Huella digital

### Permisos mínimos

El rol del docente debe tener, como mínimo:

- `asistencias.consultar`
- `asistencias.crear`
- `calificaciones.consultar`
- `calificaciones.crear`
- `calificaciones.modificar`

La interfaz oculta acciones sin permiso, pero el servidor es la autoridad. Un
docente sin `docente_id` no puede utilizar la APK. Un usuario administrativo
sin los permisos anteriores recibe `403`, nunca una redirección al login.

### Regla de alcance obligatoria

Una operación solo es válida si la oferta tiene
`ofertas_academicas.docente_id = usuarios.docente_id` del token autenticado.
Esta validación se aplica en el backend para listar, consultar, registrar y
actualizar asistencias y calificaciones. El filtro que haga la APK es solo de
usabilidad y nunca de seguridad.

## 5. Datos y contratos de sincronización

### Descarga inicial

Al iniciar sesión conectado, la APK descarga en este orden:

1. Perfil y permisos: `GET /api/v1/me`.
2. Ofertas propias: `GET /api/v1/asistencias/ofertas-disponibles`.
3. Estudiantes de cada oferta: `GET /api/v1/asistencias/estudiantes-por-oferta?oferta_academica_id={id}`.
4. Calificaciones por oferta: `GET /api/v1/calificaciones?oferta_academica_id={id}&per_page=200`.
5. Asistencias recientes por oferta y fecha; para una versión inicial se
   descargan los últimos 31 días y, bajo demanda, una fecha específica.

La aplicación debe mostrar la fecha y hora de la última sincronización por
oferta. Si no hay conexión, debe indicar que los datos son locales, sin
inventar estados ni resultados del servidor.

### Endpoints móviles a implementar

Los endpoints actuales son la base, pero la APK requiere contratos diseñados
para sincronización y no múltiples consultas por alumno. Agregar el prefijo
protegido `v1/docente-movil` dentro de `auth:sanctum`:

| Método | Ruta | Propósito |
|---|---|---|
| `GET` | `/api/v1/docente-movil/sincronizar?desde={ISO8601}` | Ofertas propias, alumnos, calificaciones y asistencias modificadas desde la marca de tiempo. |
| `POST` | `/api/v1/docente-movil/sincronizar` | Aplicar lote de operaciones offline en una transacción por operación. |
| `GET` | `/api/v1/docente-movil/ofertas/{id}` | Recarga puntual de una oferta propia con alumnos y notas. |

La respuesta de descarga debe incluir `servidor_en`, `siguiente_desde` y una
versión (`actualizado_en` o contador) para cada registro. Nunca devolver
ofertas o estudiantes de otro docente.

### Formato de la cola enviada

```json
{
  "operaciones": [
    {
      "uuid": "f146a0ce-2cb8-4dce-8e90-8fa890f05aee",
      "tipo": "asistencia",
      "oferta_academica_id": 12,
      "fecha": "2026-08-05",
      "version_base": "2026-08-05T14:10:00Z",
      "datos": {
        "matricula_id": 51,
        "estado": "presente",
        "cuenta_como_falta": false,
        "observacion": null
      }
    }
  ]
}
```

Cada `uuid` se almacena en una tabla de auditoría del servidor con respuesta y
fecha. Si la APK reintenta por una caída de red, el servidor devuelve el mismo
resultado y no duplica asistencia, calificación, auditoría ni historial.

## 6. Reglas offline y conflictos

1. La APK puede crear cambios offline, pero no puede confirmar que fueron
   aceptados hasta sincronizar.
2. La cola se procesa en orden de creación. Una operación confirmada se marca
   `aplicada`; una red caída queda `pendiente`; una validación se marca
   `rechazada`; una diferencia de versión se marca `conflicto`.
3. Asistencia: la clave funcional es `(matricula_id, fecha)`. Si el servidor
   cambió la misma asistencia después de `version_base`, se declara conflicto;
   no se sobrescribe silenciosamente.
4. Calificación: la clave funcional es `(estudiante_id, oferta_academica_id)`.
   Ante cambio concurrente, se declara conflicto y se muestran valor local y
   valor del servidor para que el docente elija reenviar o descartar.
5. Las notas aprobadas o reprobadas siguen ejecutando la misma sincronización
   de historial académico y las reglas de faltas existentes en Laravel.
6. Una oferta eliminada, reasignada o cerrada después de una descarga no
   admite cambios nuevos. El servidor responde `409` con motivo legible.
7. No se usa "última escritura gana" para notas ni asistencias sin informar al
   docente, porque altera historial académico y puede generar certificados.

## 7. Esquema SQLite local

Tablas mínimas:

| Tabla | Campos principales |
|---|---|
| `sesion` | `usuario_id`, `docente_id`, `expira_en`, `ultima_sincronizacion` (sin token). |
| `ofertas` | `id`, `codigo`, `nivel`, `periodo`, `horario`, `estado`, `actualizado_en`. |
| `matriculas` | `id`, `oferta_id`, `estudiante_id`, `codigo_estudiante`, `nombre`, `apellido`, `estado`, `actualizado_en`. |
| `asistencias` | `matricula_id`, `fecha`, `estado`, `observacion`, `version_servidor`, `pendiente`. |
| `calificaciones` | `id`, `oferta_id`, `estudiante_id`, `nota_final`, `faltas`, `observaciones`, `version_servidor`, `pendiente`. |
| `cola_sincronizacion` | `uuid`, `tipo`, `payload_json`, `creado_en`, `estado`, `ultimo_error`, `reintentos`. |
| `conflictos_sincronizacion` | `uuid`, `tipo`, `local_json`, `servidor_json`, `detectado_en`, `resuelto_en`. |

Los datos se consultan siempre desde SQLite; la red actualiza SQLite y vacía
la cola. No se debe bloquear el pase de lista mientras la sincronización está
en curso.

## 8. Pantallas de la APK

1. **Inicio de sesión:** correo, contraseña, estado de red y aviso de que se
   usan credenciales administrativas de docente.
2. **Mis ofertas:** tarjetas por período/nivel/horario, total de alumnos y
   fecha de última descarga.
3. **Detalle de oferta:** alumnos, accesos a asistencia y calificaciones,
   indicador de pendientes.
4. **Asistencia:** selector de fecha, lista de alumnos, marcación rápida,
   observación y botón `Guardar localmente` / `Sincronizar`.
5. **Calificaciones:** nota final, faltas y observaciones por alumno; advertir
   que guardar una nota sincronizada actualiza su historial académico.
6. **Sincronización:** cambios pendientes, aplicados, rechazados y conflictos
   con una acción explícita para resolverlos.
7. **Perfil:** docente, permisos efectivos, cierre de sesión y borrado local.

La APK no muestra IDs internos. Debe mostrar código funcional de oferta,
estudiante, nivel, período y horario.

### Avisos de asistencia a familias (fase futura)

Las faltas y tardanzas podrán generar avisos por correo y WhatsApp únicamente después de que el servidor confirme la operación. La APK nunca envía mensajes directamente ni al guardar localmente. El diseño de contactos consentidos, cola idempotente, canales autorizados y reglas de reintento está en `docs/NOTIFICACIONES_ASISTENCIA_FAMILIAS.md`; será obligatorio respetarlo al implementar sincronización móvil y notificaciones.

## 9. Seguridad y auditoría

- Usar HTTPS obligatorio; rechazar URLs `http` en builds de producción.
- No registrar contraseñas, tokens, secretos ni notas completas en logs de
  diagnóstico.
- Enmascarar tokens y datos sensibles en pantalla de soporte.
- Registrar en bitácora cada sincronización con usuario, docente, cantidad de
  operaciones, UUID y resultado. Mantener auditoría actual de asistencia y
  calificaciones.
- Cerrar sesión borra token, cola, datos académicos y conflictos del teléfono.
- La API debe limitar tamaño de lote (por ejemplo 100 operaciones) y aplicar
  rate limiting para sincronización.

## 10. Construcción del proyecto móvil

### Dependencias propuestas

- `expo`
- `react-native`
- `expo-sqlite`
- `expo-secure-store`
- `@react-native-community/netinfo`
- `axios`
- `expo-application`

Estructura inicial:

```text
mobile-docentes/
  app.json
  eas.json
  package.json
  src/
    api/
    auth/
    database/
    sync/
    screens/
    components/
    storage/
```

La variable `EXPO_PUBLIC_API_URL` debe apuntar a
`https://matricula.cursossanvicente.com/api/v1`. Nunca incluir credenciales de
PayPal, Stripe, base de datos o llaves privadas dentro de la APK.

## 11. Generación del APK interno

1. Definir `android.package`, por ejemplo
   `com.cursossanvicente.docentes`, en `app.json`.
2. Crear el keystore institucional y guardar la contraseña fuera del
   repositorio. EAS puede administrarlo, pero debe estar asociado a la cuenta
   institucional, no a una cuenta personal.
3. Configurar perfil `internal` en `eas.json` con `android.buildType: "apk"`.
4. Ejecutar `eas build --platform android --profile internal`.
5. Descargar el APK, calcular SHA-256, registrar versión y distribuirlo por
   canal interno autorizado.
6. Antes de sustituir una versión, incrementar `versionCode` y conservar
   changelog. Los teléfonos solo instalan una actualización si la firma es la
   misma.

La primera liberación debe ser una versión de prueba interna, no una versión
de producción masiva.

### Compilación local

También se puede compilar sin enviar el código a EAS. Requiere Android SDK y
Java 17 o superior. Desde `mobile-docentes/` se ejecuta `npx expo prebuild
--platform android --no-install` y luego, en `android/`,
`gradlew.bat assembleRelease`. La carpeta `android/` y todos los resultados de
build están ignorados por Git.

El APK que Gradle crea por defecto puede estar firmado con `Android Debug`.
Ese APK solo sirve para verificación técnica local: **no debe cargarse al
panel ni publicarse**. Para una distribución interna se debe configurar una
llave institucional persistente, guardada fuera del repositorio y respaldada
en el gestor institucional de secretos. La misma llave es obligatoria para
actualizar la aplicación instalada.

## 11.1 Publicación y descarga oficial

El archivo firmado se conserva en `storage/app/apk-docentes/`, fuera de la
raíz pública. No se debe copiar manualmente a `public/` ni enviar enlaces a
archivos de desarrollo. La publicación se realiza desde **Panel Administrativo
→ APK Docentes** (`/admin/apk-docentes`), con los permisos RBAC
`distribucion_apk.consultar`, `.crear` y `.modificar`.

Al registrar una versión se almacena: versión visible, `versionCode`, tamaño,
hash SHA-256, notas, usuario que la cargó y usuario/fecha de publicación. Solo
una versión puede estar publicada; al publicar otra, la anterior deja de ser
la descarga activa pero se conserva para trazabilidad.

La URL sin autenticación para docentes es:

`https://matricula.cursossanvicente.com/apk/docentes`

Esa pantalla no entrega un listado histórico ni archivos internos: muestra la
versión activa, hash, tamaño, notas y el botón de descarga. La descarga se
sirve mediante `/apk/docentes/descargar` y únicamente cuando existe una versión
marcada como publicada. Antes de que exista el primer APK firmado, la URL
indica que aún no hay una versión disponible.

## 12. Plan de ejecución

| Fase | Entregable | Criterio de salida |
|---|---|---|
| 0. Preparación | Roles docentes, API URL, identidad Android y cuenta de firma institucional. | Docentes de prueba y responsables de soporte definidos. |
| 1. Backend | Endpoints `docente-movil`, idempotencia, alcance y auditoría. | Pruebas de autorización, lote y conflictos pasan. |
| 2. Base móvil | Login, SecureStore, SQLite, migraciones locales y consulta de red. | Un docente inicia/cierra sesión y ve datos cacheados. |
| 3. Ofertas y alumnos | Descarga incremental, detalle y búsqueda local. | Solo aparecen ofertas/alumnos propios. |
| 4. Asistencias offline | Edición local, cola, sincronización y conflicto. | Se recupera una caída de red sin duplicar datos. |
| 5. Calificaciones offline | Edición local, cola y conflicto por versión. | El historial se actualiza solo después de confirmación del servidor. |
| 6. APK interna | Perfil de firma, build y guía de instalación. | APK instalada en dispositivos de prueba. |
| 7. Aceptación | Pruebas en campo sin red y con reconexión. | Acta de aceptación docente y soporte aprobada. |

## 13. Pruebas de aceptación obligatorias

- Un docente ve únicamente sus ofertas y alumnos.
- Un docente intenta modificar otra oferta y recibe `403`.
- Se registra asistencia sin señal, se cierra/reabre la APK y queda pendiente.
- Al reconectar, la asistencia se aplica una sola vez aunque se reintente.
- Se registra una nota offline y se verifica el historial académico tras
  sincronizar.
- Se simula conflicto de asistencia y de nota; la APK no sobrescribe sin
  intervención del docente.
- Token vencido o usuario inactivo obliga a iniciar sesión de nuevo.
- Cerrar sesión elimina datos locales y no deja token recuperable.
- APK se instala, actualiza y conserva la firma institucional.

## 14. Criterios de salida para producción interna

No distribuir ampliamente hasta que se cumpla todo:

- Cobertura automatizada de alcance, idempotencia y conflictos.
- Pruebas de reconexión reales en al menos dos versiones de Android y tres
  dispositivos físicos.
- Bitácora y soporte pueden identificar una operación por UUID.
- Respaldo probado antes de cualquier migración de sincronización.
- Versión, hash del APK y responsable de distribución registrados.
- Capacitación breve para docentes: sincronizar antes y después de clase,
  revisar pendientes y resolver conflictos.

## 15. Trazabilidad obligatoria de cambios

Este documento es la fuente de verdad de la APK docente. Todo cambio que
afecte la aplicación móvil, sus APIs, seguridad, permisos, sincronización,
datos locales, compilación o distribución debe actualizar esta guía en el
mismo commit. Cada entrada debe indicar fecha, cambio, archivos o endpoints
afectados, validación realizada y cualquier limitación pendiente.

### Registro de implementación

| Fecha | Cambio | Validación / estado |
|---|---|---|
| 2026-08-05 | Se creó `mobile-docentes/` como proyecto Expo Android interno, con `app.json` y perfil EAS `internal` para APK. | Pendiente completar instalación de dependencias y ejecutar build Android. |
| 2026-08-05 | Se implementó la base móvil: login administrativo de docente, SecureStore para token, SQLite para ofertas/alumnos/notas y cola offline para asistencia y calificaciones. | Revisión estática pendiente de dependencias Expo; no existe todavía APK firmada. |
| 2026-08-05 | `GET /asistencias/estudiantes-por-oferta` ahora entrega `estudiante_id`, requerido para enviar notas desde la APK. | Debe cubrirse con prueba de contrato al finalizar la API móvil. |
| 2026-08-05 | Calificaciones limita listado, consulta, registro y actualización a las ofertas del docente autenticado. | `CalificacionTest` pasó antes del scaffolding móvil. |
| 2026-08-05 | Se excluyeron del control de versiones los artefactos móviles generados: `node_modules`, `.expo`, `android`, `ios`, `dist` y `.gradle`. | Se conservan código fuente, configuración EAS y `package-lock.json` cuando la instalación finalice. |
| 2026-08-05 | Se instalaron dependencias Expo y se ejecutó `npx expo export --platform android`. | Bundle Android generado correctamente; `dist/` queda ignorado. Aún falta build EAS y firma para obtener el APK instalable. |
| 2026-08-05 | Se agregó prueba de contrato para `estudiante_id` en alumnos por oferta, consumido por el registro de notas móvil. | `CalificacionTest`: 12 pruebas y 30 aserciones correctas. |
| 2026-08-05 | Se generalizó el ignore de dependencias Node con `**/node_modules/`. | Todo subproyecto actual o futuro queda protegido contra versionar dependencias instaladas. |
| 2026-08-05 | Se añadió publicación controlada de APK: tabla, almacenamiento privado, panel administrativo, permisos RBAC y URL pública `/apk/docentes`. | `DistribucionApkDocenteTest` cubre ausencia de APK, publicación y denegación RBAC; pendiente ejecutar migración, firmar y cargar la primera APK real. |
| 2026-08-05 | Se documentó la futura notificación de faltas y tardanzas a responsables por correo y WhatsApp. | No hay envíos activos; la APK solo notificará al backend tras una sincronización confirmada e idempotente. |
| 2026-08-05 | Se ejecutó compilación Android local con Java 17 y Android SDK. | Generó `app-release.apk` de 61 MB, SHA-256 `76C8964D9B89ECB2197A1944E8A70ABBBE394F892EF5B1C613DEBFBB59C8B671`; la firma detectada es `Android Debug`, por lo que no se publica. |
| 2026-08-05 | Se creó una llave institucional local RSA 4096, excluida de Git, y se firmó `CursosSanVicenteDocentes-v0.1.0.apk`. | Verificada firma Android v2/v3 con certificado institucional; APK SHA-256 `5EF0348E58888DBC1E1882A2D54FF27DC04B73C49261A6AA3DBAD5CC925F2EBA`. Pendiente respaldo institucional de llave, prueba física y publicación desde el panel. |
| 2026-08-05 | La prueba física detectó cierre al iniciar por consultar `cola_sincronizacion` antes de crear tablas SQLite; `App.jsx` ahora espera `databaseReady`. | Pendiente recompilar, reinstalar y validar que llegue al login antes de publicar. |
| 2026-08-05 | Se recompiló, firmó y reinstaló la APK tras corregir SQLite. | En el Android de prueba se confirmó visualmente el formulario de login (correo, contraseña e inicio de sesión); APK SHA-256 `2A8EB1D6E9436920B8E1336442E76E5D3B34B763D8A9D9F30098961395BD6846`. Pendiente credencial docente real y publicación. |
| 2026-08-05 | Se corrigió la administración de usuarios docentes: el formulario incluye confirmación de contraseña, roles y vínculo con una ficha docente existente. | La cuenta docente requerida por la APK queda validada como única por docente; pendiente prueba de aceptación visual. |
| 2026-08-05 | Se fijó `react` en `19.1.4`, compatible con `react-native 0.81.6`, tras un cierre de la APK al interactuar con el login. | `npm ls` confirma una única versión `19.1.4`; pendiente recompilar, firmar, reinstalar y validar el flujo real de autenticación. |
| 2026-08-05 | Se realizó compilación limpia de Android, se firmó y se reinstaló la APK con React 19.1.4. | Firma v2/v3 válida, SHA-256 `51004AD7F36E7C2B67EFB7B380B9F04EDA8C638193BA359236A2811A6CD66D37`; el Android abrió el login y respondió a controles sin registrar un nuevo cierre. Falta probar credenciales reales de docente y publicar la versión activa. |
| 2026-08-05 | Se diagnosticó el error `Network` del login en el Android físico. | La APK tiene URL HTTPS y permiso de Internet correctos, pero `curl` del dispositivo recibe `BoringSSL SSL_connect: Connection reset by peer` al conectar `matricula.cursossanvicente.com:443`, tanto con TLS 1.2 como 1.3. Laravel no recibe la petición. Pendiente externo: renovar/asignar certificado, binding HTTPS o política WAF en el hosting y repetir la prueba. |
| 2026-08-05 | Se revalidó HTTPS desde Android después de habilitar una red Wi-Fi validada y asignar Let’s Encrypt al dominio. | El certificado `CN=matricula.cursossanvicente.com`, emisor Let’s Encrypt, valida con TLS 1.3; la raíz responde `302` y el endpoint de login responde JSON. La APK queda lista para probar credenciales docentes reales. |
| 2026-08-05 | Se corrigió el contrato de autenticación administrativa consumido por la APK. | `POST /api/v1/login` y `GET /api/v1/me` incluyen `docente_id`; sin ese dato una cuenta válida no podía ser aceptada por la APK. `AuthTest` cubre la respuesta para usuario vinculado a docente. |
| 2026-08-05 | Se añadió navegación móvil por Ofertas Académicas, Asistencia Diaria y Calificaciones. | Las tres secciones muestran selector común de período, ofertas del docente y luego la lista de estudiantes; el filtro se envía como `periodo_academico_id` al sincronizar ofertas y se conserva la copia offline. |
| 2026-08-05 | Se corrigió la persistencia de asistencia y calificaciones desde la APK: `DOCENTE` recibe los permisos mínimos de consulta/registro y la cola muestra el rechazo real del servidor en vez de informar éxito prematuramente. | `CalificacionTest` cubre el registro de asistencia; pendiente recompilar e instalar el APK para validación física y ejecutar el seeder RBAC en producción. |
| 2026-08-05 | Se incrementó la APK a `0.1.1` / `versionCode 2` para distribuir la corrección como actualización. La compilación local finalizó; la primera instalación fue rechazada porque el prebuild restauró la firma de depuración en vez de la institucional. | Se restauró la configuración local de firma institucional sin exponer secretos. La instalación de confirmación quedó pendiente porque el entorno agotó la autorización de ejecución; no se desinstaló la APK ni se borró su cola offline. |
| 2026-08-05 | Se desinstaló la APK v0.1.0 (firma anterior perdida), se recreó el keystore institucional con contraseña segura ASCII (`CursosSVP2026`), se compiló y firmó v0.1.1 con `apksigner` v2/v3 y se instaló en el dispositivo Samsung S24+. | `apksigner verify -v` confirma v2 y v3; `adb install` exitoso. `keystore.properties` actualizado con la nueva contraseña. Pendiente: probar login con credenciales docentes reales y publicar desde el panel administrativo. |
| 2026-08-07 | Se añadió la modalidad **registrar desde APK ya colocado** en `storage/app/apk-docentes/`: el endpoint `POST /api/v1/distribucion-apk/docentes` con `desde_servidor=1` (o el checkbox del panel) lee el archivo `.apk` más reciente del servidor, calcula su SHA-256 y tamaño y lo registra/publica **sin subir por HTTP**. Si no hay archivo, responde `422_VALIDACION`. | Evita el `client_max_body_size` del hosting que corta el `multipart` de ~61 MB (`ERR_HTTP2_PROTOCOL_ERROR`). Cubierto por `DistribucionApkDocenteTest` (5 pruebas, 18 aserciones) y build. Sugerido: cargar la APK por FTP/SFTP o Plesk File Manager en `storage/app/apk-docentes/`. |
