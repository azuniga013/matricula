# Resumen técnico de reglas por módulo

## Matrículas

- La reserva solo procede si la oferta está `abierto`, tiene cupo y posee
  `plan_cobro_id` activo con detalles activos.
- El siguiente nivel solo puede reservarse cuando el prerrequisito anterior:
  - está aprobado académicamente, y
  - quedó finalizado administrativamente (sin obligaciones `pendiente` o
    `parcial`).
- No basta con tener una matrícula previa en `reservada`, `en_revision` o
  `matriculado` para avanzar de nivel.
- La reserva genera obligaciones dentro de la misma transacción; si falla la
  validación del plan de cobro, no se crea matrícula incompleta.

## Ofertas Académicas

- Los estados funcionales son `borrador`, `abierto`, `lleno`, `cerrado` y
  `cancelado`.
- `lleno` es automático. No se asigna manualmente en operación normal.
- Transiciones manuales permitidas:
  - `borrador -> abierto`
  - `borrador -> cancelado`
  - `abierto -> cerrado`
  - `abierto -> cancelado`
  - `lleno -> cerrado`
  - `lleno -> cancelado`
  - `cerrado -> cancelado`
- No se permite reabrir manualmente una oferta `cerrado -> abierto` ni
  `cancelado -> abierto`.

## Pagos Administrativos

- Para registrar o aprobar pagos administrativos debe existir una sesión de caja
  `abierta` del mismo usuario en la misma sucursal del pago.
- Si no existe, la API responde `422_SESION_CAJA_REQUERIDA` con el detalle de
  la sucursal requerida.
- Para `DEP` y `TRA` se exige:
  - referencia,
  - fecha,
  - cuenta bancaria activa.
- Para `EFE` se exige:
  - monto total,
  - `monto_recibido`,
  - cálculo y persistencia de `vuelto`.

## Flujo de Link de Pago

- La secuencia válida es:

```text
solicita_link -> esperando_respuesta -> en_revision -> aprobado
```

- Cargar el link no mueve el pago directamente a `en_revision`.
- Solo cuando el estudiante confirma que ya ejecutó el link, el pago pasa a
  `en_revision`.

## Portal del Estudiante

- El portal usa la misma validación de prerrequisitos del módulo de matrículas.
- La reserva online también exige oferta con plan de cobro activo y detalles
  activos.
- En producción puede deshabilitarse la eliminación de pagos desde el portal.

## Seguridad y alcance

- El usuario puede tener acceso global o por una o varias sucursales.
- El alcance por sucursal se parametriza con `usuario_sucursales`.
- Si tiene varias sucursales asignadas, el resolutor de alcance permite ver los
  registros de todas ellas.
- Si solo tiene una, queda restringido a esa sucursal.

## Roles operativos recomendados

| Rol | Enfoque | Sí puede | No debe poder |
|---|---|---|---|
| `SUPERADMIN` | Control total | Todo el sistema, RBAC, parámetros, flujos, usuarios y configuración | Sin restricciones funcionales ordinarias |
| `ADMIN_GENERAL` | Administración amplia | Operación general, pagos, reportes, inventario, configuración de pagos y parámetros globales | No es la opción recomendada para delegación operativa si se quiere evitar cambios sensibles |
| `ADMIN_OPERATIVO` | Operación diaria | Usuarios operativos, estudiantes, matrículas, pagos, caja, inventario y reportes | No asigna roles protegidos ni modifica RBAC, parámetros, flujos o proveedores |
| `ADMIN_ACADEMICO` | Configuración académica | Catálogos académicos, ofertas, monitor, planes de cobro, asistencias, calificaciones y reportes académicos | No administra RBAC sensible, caja ni pagos operativos |
| `ADMIN_SUCURSAL` | Gestión local | Operación limitada a sus sucursales asignadas | No debe tener alcance global ni configuración sensible |
| `CAJA` | Cobro y recibos | Sesiones de caja, recibos y cierre según permisos | No configura catálogo académico ni seguridad |
| `MATRICULA` | Proceso académico-administrativo | Reservas, confirmaciones y gestiones de matrícula | No configura RBAC ni pagos sensibles fuera de su alcance |
| `DOCENTE` | Ejecución académica | Asistencias y calificaciones de sus ofertas | No administra usuarios, caja ni configuración |
| `AUDITORIA` | Consulta | Reportes y consulta según permisos | No crea, modifica, aprueba ni configura |

### Regla de asignación de roles protegidos

- Solo `SUPERADMIN` puede asignar `SUPERADMIN`, `ADMIN_GENERAL`,
  `ADMIN_OPERATIVO` y `ADMIN_ACADEMICO`.
- `ADMIN_OPERATIVO` solo puede asignar roles operativos del catálogo permitido:
  `CAJA`, `MATRICULA`, `DOCENTE`, `AUDITORIA` y `ADMIN_SUCURSAL`.
