# Generación de obligaciones de pago

Al crear por primera vez una reserva mediante `POST /api/v1/matriculas/reservar`, el sistema genera las obligaciones de pago desde el `plan_cobro_id` de la oferta académica.

La generación ocurre dentro de la misma transacción que reserva el cupo. Si el plan de cobro no está activo o no tiene detalles activos, la reserva se revierte.

Por cada detalle activo del plan se conserva en `obligaciones_pago_estudiante`:

- Concepto de pago.
- Número de cuota.
- Nombre histórico del cargo.
- Monto histórico.
- Fecha de vencimiento calculada desde la fecha de reserva más `dias_vencimiento`.
- Estado inicial `pendiente`.

La combinación matrícula y número de cuota es única. Por ello, los reintentos de reserva no duplican obligaciones ni modifican los montos históricos ya generados.
