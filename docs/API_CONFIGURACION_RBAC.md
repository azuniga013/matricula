# Configuración administrativa de RBAC

Estas rutas requieren `seguridad.consultar` para lectura y `seguridad.configurar` para creación o actualización.

## Catálogos administrables

- `GET|POST|PUT /api/v1/seguridad/modulos`
- `GET|POST|PUT /api/v1/seguridad/opciones`
- `GET|POST|PUT /api/v1/seguridad/permisos`
- `GET|POST|PUT /api/v1/seguridad/roles`

Los códigos son únicos. Las opciones pertenecen a un módulo; los permisos pertenecen a una opción. La matriz de permisos, copia entre roles, asignación múltiple de roles y configuración de alcance se mantienen en las rutas existentes bajo `/api/v1/seguridad/roles` y `/api/v1/seguridad/usuarios`.

La pantalla administrativa de Roles y permisos muestra la matriz por rol y permite actualizarla usando permisos efectivos. La API mantiene siempre la validación final.
