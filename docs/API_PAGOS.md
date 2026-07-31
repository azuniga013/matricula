# API de pagos

Todas las rutas requieren autenticación administrativa y el permiso indicado.
Las respuestas usan la estructura estándar `resultado`, `codigo`, `mensaje` y `data`.

## Registrar pago

`POST /api/v1/pagos` — permiso `pagos.crear`.

Datos: `matricula_id`, `metodo_pago_id`, `monto` y opcionalmente `documento_referencia`.
El pago inicia en estado `cargado` y se valida contra la sucursal de la matrícula.

## Cargar comprobante

`POST /api/v1/pagos/{pago}/comprobantes` — permiso `pagos.modificar`.

Recibe el archivo `archivo` (JPG, JPEG, PNG o PDF; máximo 10 MB). Cambia el pago a `en_revision`.

## Aprobar o rechazar

`POST /api/v1/pagos/{pago}/aprobar` — permiso `pagos.aprobar`.

Al aprobar se aplican los importes a las obligaciones pendientes, se confirma el cupo y se emite un recibo.

`POST /api/v1/pagos/{pago}/rechazar` — permiso `pagos.aprobar`.

Requiere `motivo_rechazo`; libera una reserva de cupo cuando corresponde.

## Anular un pago aprobado

`POST /api/v1/pagos/{pago}/anular` — permiso `pagos.anular`.

Requiere `motivo_anulacion` (de 5 a 1000 caracteres). Solo admite pagos aprobados. Conserva el pago, sus aplicaciones y el recibo como evidencia: cambia el pago a `cancelado`, anula el recibo y recalcula las obligaciones usando únicamente aplicaciones de pagos aprobados. No cancela ni modifica la matrícula; esa gestión requiere su propio proceso autorizado.
