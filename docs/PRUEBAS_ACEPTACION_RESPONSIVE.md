# Pruebas de aceptación responsive

Ejecutar después de desplegar la versión y de aplicar las migraciones pendientes.

## Dispositivos mínimos

- Escritorio: Chrome o Edge, ancho de 1366 px o superior.
- Móvil: Chrome Android o Safari iOS, ancho entre 320 px y 430 px.

## Acceso y Portal del Estudiante

1. Abrir la pantalla de acceso; comprobar que las opciones Administración y Portal del estudiante se pueden pulsar cómodamente.
2. Iniciar sesión de estudiante y comprobar que las tarjetas se muestran en una columna en móvil.
3. Desplazar horizontalmente las tablas de obligaciones y pagos; no debe recortarse el contenido ni impedir el desplazamiento vertical de la página.
4. Cargar un comprobante JPG, PNG y PDF válido; comprobar el mensaje de resultado.
5. Descargar un recibo y una constancia de nivel actual.
6. Verificar que WhatsApp solo aparece cuando existe pago aprobado y enlace configurado para la oferta.

## Administración y seguridad

1. Iniciar sesión administrativa y navegar por Caja, Usuarios, Alcances, Auditoría y Roles y permisos.
2. Confirmar que los botones y campos tienen un área táctil cómoda y un foco visible usando teclado.
3. En móvil, comprobar que el menú, formularios y tablas no desbordan el ancho de pantalla.
4. Crear o editar un usuario, asignar varios roles y sucursales, y validar el mensaje de respuesta.
5. Configurar un alcance y comprobar que una consulta fuera de su sucursal responde con el mensaje de autorización correspondiente.
6. Revocar una sesión desde Auditoría y comprobar que el token revocado no puede volver a acceder.

## Reportes y monitor de cupos

1. En escritorio, abrir Reportes, seleccionar cada reporte disponible, indicar rango de fechas y comprobar vista en pantalla, descarga Excel y descarga `PDF oficial`.
2. Abrir Monitor de cupos, filtrar por período y sucursal; comprobar cupos máximos, matriculados, reservados, disponibles y los indicadores verde, azul, amarillo, rojo y gris.
3. En móvil, comprobar desplazamiento horizontal de ambas tablas, botones PDF/Excel utilizables al tacto y ausencia de contenido cortado.
4. Esperar o forzar actualización del monitor; los valores deben refrescarse sin recargar toda la sesión.

## Resultado esperado

Registrar navegador, tamaño de pantalla, caso ejecutado, resultado, evidencia y cualquier defecto encontrado antes de aprobar el despliegue.
