# API de reportes académicos y financieros

Todos los reportes requieren `fecha_desde` y `fecha_hasta`. Admiten, cuando aplica, los filtros opcionales `sucursal_id`, `periodo_academico_id`, `docente_id`, `nivel_academico_id`, `horario_id`, `estado`, `concepto_pago_id`, `metodo_pago_id` y `cajero_id`.

El resultado en pantalla es paginado e incluye totales y los filtros aplicados. Las rutas de Excel aplican exactamente los mismos filtros y generan un archivo compatible con Excel, con fecha y usuario generador.

## Financieros: recibos

- `GET /api/v1/reportes/financieros/recibos`
- `GET /api/v1/reportes/financieros/recibos/excel`

El reporte cruza recibos, pagos, matrícula, oferta y detalle de recibo. Por eso permite obtener recibos de matrícula, cuotas o libros usando `concepto_pago_id`, recibos por forma de pago usando `metodo_pago_id`, depósitos según su método configurado, consulta general y resumen de ingresos mediante sus totales.

## Académicos: matriculados

- `GET /api/v1/reportes/academicos/matriculados`
- `GET /api/v1/reportes/academicos/matriculados/excel`

Incluye alumno, estado de matrícula, sucursal, período, docente, nivel, horario y resultados académicos disponibles. El docente queda limitado a sus propias ofertas; el resto de usuarios se limita por su alcance de sucursales.
