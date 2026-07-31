# Seguridad de sesiones administrativas

El acceso administrativo registra cada token Sanctum emitido en `sesiones_usuario`, almacenando únicamente el hash del token, IP, agente de usuario y último acceso.

## Bloqueo por intentos fallidos

La configuración está en `config/seguridad.php`:

- Máximo de intentos fallidos: 5.
- Ventana de conteo: 15 minutos.
- Bloqueo temporal: 15 minutos.

Al superar el límite se registra `bloqueado_hasta` y se responde `423_USUARIO_BLOQUEADO`. Los intentos siguen quedando en `intentos_acceso`.

## Revocación

Al cerrar sesión, inactivar un usuario o cambiar su contraseña se eliminan sus tokens Sanctum y se marcan sus sesiones internas como revocadas. Un token revocado recibe `401_SESION_REVOCADA`.

## Auditoría

El middleware de permiso registra en `bitacora_seguridad` cada denegación y también cada operación protegida permitida, incluyendo permiso, resultado, IP y agente de usuario.
