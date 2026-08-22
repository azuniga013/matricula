# Arquitectura RBAC parametrizable

## Objetivo

Separar autenticación de autorización. Un usuario autenticado puede ejecutar una acción únicamente cuando tiene un permiso explícito y su alcance de datos le permite acceder al registro solicitado. La política es de denegación por defecto.

## Componentes

```text
Usuario autenticado
  -> Middleware de autenticación
  -> Middleware de permisos
  -> Resolución de alcance de datos
  -> Policy/servicio del módulo
  -> Controlador API
```

- El frontend consulta los permisos efectivos para construir menús y ocultar acciones no autorizadas.
- La API es la autoridad final: toda acción protegida valida permiso y alcance; ante falta de permiso responde `403`.
- Los permisos se cachean por usuario. Todo cambio de usuario, rol o permiso invalida ese caché y las sesiones/tokens afectados cuando corresponda.

## Modelo de datos

### Tablas

| Tabla | Propósito y campos clave |
|---|---|
| `modulos` | Catálogo de módulos: `codigo`, `nombre`, `orden`, `estado`. |
| `opciones_modulo` | Submódulo/pantalla: `modulo_id`, `codigo`, `nombre`, `ruta`, `orden`, `estado`. |
| `permisos` | Acción atómica: `opcion_modulo_id`, `codigo` único, `nombre`, `accion`, `estado`. |
| `usuario_roles` | Relación muchos a muchos: `usuario_id`, `rol_id`, `estado`, auditoría; única por usuario/rol. |
| `rol_permisos` | Relación muchos a muchos: `rol_id`, `permiso_id`, `estado`, auditoría; única por rol/permiso. |
| `sesiones_usuario` | Sesiones/tokens: `usuario_id`, identificador o hash, vencimiento, revocación, IP y agente. |
| `intentos_acceso` | Intentos de login: correo/usuario, IP, resultado, motivo, fecha. |
| `bitacora_seguridad` | Auditoría: usuario, acción, módulo, registro, valores antes/después, IP, agente, resultado y motivo. |

Los catálogos `roles`, `usuarios`, `usuario_sucursales` y `docentes` ya existentes participan en el modelo. `usuarios.rol_id` queda solo como compatibilidad temporal; la asignación efectiva se maneja con `usuario_roles`.

### Relaciones

```text
usuarios --< usuario_roles >-- roles --< rol_permisos >-- permisos
modulos --< opciones_modulo --< permisos
usuarios --< usuario_sucursales >-- sucursales
usuarios --< sesiones_usuario
usuarios --< intentos_acceso
usuarios --< bitacora_seguridad
```

`usuarios.docente_id` mantiene la relación uno a uno para limitar al docente a sus ofertas, grupos, alumnos, asistencias y calificaciones.

## Alcance de datos

El permiso funcional se complementa con un alcance calculado:

- Global: todas las sucursales.
- Sucursales asignadas: se filtra por `usuario_sucursales`.
- Propio: se filtra por `creado_por` o propietario funcional.
- Alumno: se filtra por estudiante asociado al acceso autenticado.
- Docente: se filtra por `ofertas_academicas.docente_id` del usuario.
- Un usuario administrativo puede tener una o varias sucursales asignadas. Si
  tiene varias, el alcance efectivo es la unión de esas sucursales; si tiene
  una sola, queda limitado a esa sucursal.
- La parametrización operativa de sucursales por usuario se realiza desde
  Seguridad y persiste en `usuario_sucursales`. No debe usarse `users.sucursal_id`
  como sustituto del alcance efectivo.

El alcance se resuelve en un servicio central y nunca se delega al frontend. El rol `SUPERVISOR` es un rol inicial con permisos y alcance global configurables; no debe existir una excepción fija por nombre de rol.

## Flujo de validación

1. Sanctum autentica el token y verifica que el usuario esté activo.
2. El middleware recibe el permiso requerido, por ejemplo `pagos.aprobar`.
3. Se obtienen los permisos activos de todos los roles del usuario, desde caché o base de datos.
4. Si no existe el permiso, la API devuelve `403`.
5. El servicio de alcance aplica filtros a la consulta o la policy valida el registro individual.
6. El controlador ejecuta la operación dentro de transacción si es sensible.
7. Se escribe `bitacora_seguridad` con resultado exitoso o rechazado.

## Catálogo inicial

Se crearán módulos para Seguridad, Catálogos académicos, Ofertas y cupos, Estudiantes, Matrículas, Pagos, Caja, Calificaciones, Inventario y Reportes. Cada opción tendrá permisos separados de consultar, crear, modificar, eliminar, aprobar, anular, imprimir, exportar, importar, asignar y configurar según aplique.

Roles de referencia iniciales: `SUPERADMIN`, `ADMIN_GENERAL`, `ADMIN_SUCURSAL`, `CAJA`, `MATRICULA`, `DOCENTE`, `ALUMNO`, `AUDITORIA` y `SUPERVISOR`. Son registros iniciales; no son condiciones fijas de autorización.

## Reglas de transición

1. Crear las nuevas tablas sin eliminar `usuarios.rol_id`.
2. Migrar cada `usuarios.rol_id` existente a `usuario_roles`.
3. Activar middleware de permisos gradualmente por módulo.
4. Cuando todos los endpoints estén protegidos por RBAC, retirar el uso funcional de `usuarios.rol_id` mediante una migración aprobada.

## Criterios de aceptación

- Un usuario puede tener varios roles activos.
- Un permiso no asignado devuelve `403` incluso si se invoca la API directamente.
- Un docente no accede a ofertas de otro docente.
- Un usuario de sucursal no obtiene registros de una sucursal no asignada.
- Cambiar permisos invalida caché de autorización.
- Inactivar usuario revoca sus sesiones/tokens.
- La bitácora registra operaciones críticas y rechazos.
