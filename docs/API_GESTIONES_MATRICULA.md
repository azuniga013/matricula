# API de gestiones de matrícula

Todas las rutas son administrativas, requieren autenticación y validan el alcance de sucursal del usuario.

## Consultar gestiones

`GET /api/v1/gestiones-matricula` — permiso `matriculas.consultar`.

Filtros opcionales: `matricula_id`, `estado` y `tipo_gestion_matricula_id`.

## Solicitar una gestión

`POST /api/v1/matriculas/{matricula}/gestiones` — permiso `matriculas.modificar`.

Datos requeridos: `tipo_gestion_matricula_id` y `motivo`. Para los tipos que trasladan matrícula también se requiere `oferta_academica_destino_id`.

Tipos iniciales: `CAMBIO_HORARIO`, `RETIRO`, `CANCELACION`, `CAMBIO_MODALIDAD`, `TRASLADO_SUCURSAL` y `EXCEPCION_MATRICULA`.

Las gestiones guardan el estado y los datos académicos anteriores. Solo se permite una gestión pendiente por matrícula.

## Decidir una gestión

- `POST /api/v1/gestiones-matricula/{gestion}/aprobar` — permiso `matriculas.aprobar`.
- `POST /api/v1/gestiones-matricula/{gestion}/rechazar` — permiso `matriculas.aprobar`; requiere `motivo`.
- `POST /api/v1/gestiones-matricula/{gestion}/cancelar` — permiso `matriculas.modificar`; requiere `motivo`.

Al aprobar, la gestión se ejecuta en la misma transacción:

- Cambio de horario: conserva sucursal, período, plan, nivel y modalidad; exige horario distinto, cupo y que la oferta destino acepte cambios de horario.
- Cambio de modalidad: conserva sucursal, período, plan y nivel; exige modalidad distinta y cupo.
- Traslado de sucursal: conserva período, plan, nivel y modalidad; exige otra sucursal y cupo.
- Retiro o cancelación: libera el cupo ocupado y conserva la matrícula y la gestión como historial.
- Excepción: deja la autorización documentada; no omite validaciones de cupo ni aprueba pagos automáticamente.

La aprobación conserva los datos posteriores, el usuario, las fechas y la decisión financiera indicada. No elimina matrículas, pagos ni historial.
