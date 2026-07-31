# Portal del estudiante: pagos y recibos

Estas rutas requieren un token emitido por `POST /api/v1/estudiantes/iniciar-sesion`. El portal solo puede consultar o modificar información asociada al estudiante autenticado.

## Resumen del portal

`GET /api/v1/estudiantes/portal`

Devuelve matrículas, obligaciones de pago, pagos y recibos disponibles del estudiante autenticado.

## Registrar pago y comprobante

`POST /api/v1/estudiantes/pagos`

Recibe `matricula_id`, `metodo_pago_id`, `monto` y opcionalmente `documento_referencia`. La matrícula debe pertenecer al estudiante autenticado y no estar cancelada, vencida o rechazada.

`POST /api/v1/estudiantes/pagos/{pago}/comprobantes`

Recibe el archivo `archivo` en formato JPG, JPEG, PNG o PDF, con máximo de 10 MB. Cambia el pago a `en_revision`.

## Descargar recibo

`GET /api/v1/estudiantes/recibos/{recibo}/descargar`

Solo permite descargar recibos emitidos o reimpresos que correspondan al estudiante autenticado. La primera versión entrega un comprobante HTML imprimible, compatible con navegador de escritorio y móvil.
