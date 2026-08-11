# Estado de implementación — 2026-07-23

> Nota 2026-08-10: documento histórico. Varias referencias describen artefactos
> de una versión previa no vigente (por ejemplo `FiltroAcademico` y archivos
> `.jsx`). El estado actual y la lista viva de trabajo están en
> `docs/PENDIENTES.md`, `docs/REGLAS_NEGOCIO_POR_DOMINIO.md` y
> `docs/PATRON_MODULARIZACION_CASOS_USO.md`.

## Finalizado en el flujo académico y financiero

- `FiltroAcademico` reutilizable para el flujo obligatorio Período → Nivel → Horario/Grupo. Carga por defecto el período abierto y solo muestra niveles disponibles en las ofertas del período seleccionado.
- Matrículas administrativas: reserva únicamente contra ofertas abiertas con cupo; evita duplicar la matrícula del estudiante en una misma oferta.
- Pagos/Caja: selección de matrícula filtrada por período, nivel y horario/grupo.
- Asistencia diaria: pantalla independiente con fecha de clase, lista de estudiantes matriculados, estado, falta computable y observación. Registra `fecha_clase` y usa permisos `asistencias.consultar` / `asistencias.modificar`.
- Calificaciones finales: pantalla independiente por período, nivel y grupo; registra nota final, motivo de corrección y muestra faltas/resultado calculado por backend.
- Monitor de cupos: consulta por período y sucursal, indicadores verde/azul/amarillo/rojo/gris, actualización automática configurable y nombres con códigos de catálogo.
- Reportes: consulta paginada de 50 registros, rango obligatorio de fechas, filtros visuales de sucursal, período, nivel, horario, concepto, método y estado; salida de pantalla, PDF institucional y Excel para los reportes disponibles.
- Identificadores visuales: las pantallas nuevas muestran código y nombre; no exponen IDs técnicos como dato funcional.
- Horarios: el catálogo quedó en columnas booleanas por día (`lunes` a `domingo`) y la pantalla de mantenimiento fue reconstruida con esa estructura.

## Pendiente de implementación o validación

- Incorporar el filtro reutilizable a las pantallas operativas restantes que aún lo requieran: gestiones de matrícula, recibos, cierres de caja, inventario y ficha/consultas de estudiante.
- Referencia histórica: la mención a `AsistenciaCalificaciones.jsx` y al flujo
  `Academico` de `PanelAdministrativo.jsx` pertenece a una propuesta anterior
  basada en componentes que ya no existen en el monolito actual.
- Agregar pruebas automatizadas de UI/API para `FiltroAcademico`, matrícula, pagos, asistencia diaria, calificaciones finales, monitor de cupos y cada variante de reporte.
- Registrar aceptación funcional en escritorio de monitor, PDF y Excel; la aceptación móvil ya fue confirmada por el usuario.
- Confirmar el despliegue FTPS de los cambios vigentes y validar en producción `/up`, Swagger, API autenticada, PDF y Excel.
- Mantener el cierre pendiente de RBAC indicado en `AGENTS.md`: alcance completo por entidad, revocación de sesiones/tokens, protección del último superadministrador, bitácora detallada y pruebas de autorización.
