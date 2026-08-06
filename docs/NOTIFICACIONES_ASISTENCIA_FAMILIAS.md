# Notificaciones de asistencia a familias

## Estado

Diseño preparado para implementación futura. No hay envío automático activo ni se han creado integraciones de correo o WhatsApp por este documento.

## Objetivo

Informar al responsable autorizado cuando el estudiante tenga una asistencia `falta` o `tardanza`. El aviso se crea únicamente después de que Laravel haya aceptado y persistido la asistencia; nunca desde la pantalla, la APK ni una cola offline local.

## Datos existentes y evolución necesaria

Actualmente `estudiantes` ya posee `nombre_padre`, `correo_padre` y `telefono_padre`. Son útiles para una primera migración, pero representan un único contacto y no contienen consentimiento ni preferencia de canal.

Antes de activar envíos se debe crear `contactos_responsable_estudiante` con:

- estudiante, nombre, parentesco y estado;
- correo y teléfono WhatsApp normalizado E.164;
- `recibe_asistencia_email`, `recibe_asistencia_whatsapp` y evidencia/fecha de consentimiento;
- prioridad y vigencia; nunca eliminar un contacto con historial de avisos.

La ficha del estudiante deberá administrar esos contactos con permisos RBAC y mostrar siempre correo/teléfono enmascarados fuera de la edición autorizada.

## Regla de emisión

1. El docente registra o modifica una asistencia.
2. El backend valida permiso, alcance de oferta y matrícula, y guarda la asistencia en transacción.
3. Si el estado final es `falta` o `tardanza`, se emite `AsistenciaNotificableRegistrada` después de confirmar la transacción.
4. Un listener en cola crea una notificación por contacto y canal consentido.
5. Si la asistencia cambia a `presente` o `justificada`, no se envía un aviso nuevo; una futura fase puede definir una corrección explícita, nunca un mensaje silencioso.

`Faltista` no debe ser un estado que la pantalla invente: es una regla configurable basada en número o porcentaje de faltas dentro de período/oferta. La fase de activación debe definir umbral, ventana y frecuencia máxima.

## Idempotencia, APK offline y conflictos

La clave de deduplicación será `asistencia:{asistencia_id}:contacto:{contacto_id}:canal:{canal}:estado:{estado}`. Una asistencia creada offline no produce notificación hasta que la operación sea aceptada por `POST /api/v1/docente-movil/sincronizar`. Reintentos con el mismo UUID no pueden crear correos o mensajes duplicados. Los conflictos y rechazos de sincronización no notifican.

## Canales

| Canal | Preparación | Regla |
|---|---|---|
| Correo | Reutilizar la cola y `bitacora_correos`; crear plantilla específica. | Enviar solo a correo de responsable confirmado y consentido. |
| WhatsApp | Integrar un proveedor oficial con plantillas aprobadas (Meta WhatsApp Business API o proveedor autorizado). | Nunca usar grupos ni automatización de WhatsApp Web; requiere número y consentimiento explícitos. |

El mensaje mínimo debe incluir nombre del estudiante, fecha, estado, oferta o nivel funcional y canal de contacto institucional. No incluye notas, IDs, dirección ni información de otros estudiantes.

## Persistencia y auditoría a implementar

Crear `notificaciones_asistencia` con asistencia, contacto, canal, tipo, clave idempotente única, estado (`pendiente`, `enviada`, `fallida`, `omitida`), proveedor, identificador externo, intentos, error seguro y marcas de tiempo. Mantener intentos en una cola con reintento exponencial y registrar en bitácora el usuario que registró la asistencia, no los datos sensibles del mensaje.

El panel administrativo futuro debe permitir consultar el historial, estado y motivo de omisión, pero no revelar teléfonos/correos completos a usuarios sin autorización.

## Controles previos a activación

- Definir responsable institucional del tratamiento de datos y consentimiento.
- Configurar remitente de correo y proveedor WhatsApp en producción.
- Agregar límites por estudiante/contacto/día y horario permitido.
- Probar ausencia, tardanza, corrección, reintento, falta de contacto, consentimiento revocado, proveedor caído y sincronización offline.
- Aplicar permisos RBAC para configuración, consulta de historial y reintento; el docente no debe administrar contactos ni credenciales de proveedores.
