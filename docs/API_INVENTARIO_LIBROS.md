# API de libros e inventario

Las rutas requieren permisos del módulo `inventario` y aplican alcance por sucursal al inventario y kardex.

## Catálogo de libros

- `GET /api/v1/libros`
- `POST /api/v1/libros`
- `PUT /api/v1/libros/{libro}`

Un libro contiene código, nombre, precio, estado y uno o varios niveles académicos asociados mediante `libro_niveles`.

## Inventario y kardex

- `GET /api/v1/inventario/stock`
- `POST /api/v1/inventario/stock`
- `POST /api/v1/inventario/stock/{inventario}/ajustar`
- `GET /api/v1/inventario/kardex?inventario_libro_id={inventario}`

El listado de existencias es paginado: acepta `page` y `per_page` (1 a 100,
25 por defecto). Su respuesta contiene el paginador Laravel en `data`, con las
filas en `data.data`; conserva los filtros `sucursal_id`, `libro_id` y
`stock_bajo`.

Los ajustes requieren cantidad distinta de cero y motivo. No se permite que un ajuste deje existencia negativa.

## Venta de libro

`POST /api/v1/inventario-libros/{inventario}/vender`

Recibe `matricula_id`, `metodo_pago_id`, `cantidad` y opcionalmente `documento_referencia`. La matrícula debe ser de la misma sucursal. La operación valida existencia, crea un pago aprobado de origen `inventario`, emite un recibo con concepto `VLI`, descuenta la existencia y crea el movimiento `salida_venta` dentro de una sola transacción.
