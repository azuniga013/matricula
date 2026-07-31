# API de sesiones y cierre de caja

Todas las rutas requieren permisos del módulo `caja` y aplican alcance por sucursal. El cajero puede operar sus propias sesiones; un usuario con alcance global puede revisar o decidir cierres de otros cajeros.

## Sesiones

- `GET /api/v1/caja/sesiones` — consulta sesiones visibles.
- `POST /api/v1/caja/sesiones` — abre una sesión. Recibe `sucursal_id` y `monto_apertura`.
- `POST /api/v1/caja/sesiones/{sesion}/cerrar` — recibe `monto_cierre`.

Un cajero solo puede tener una sesión `abierta` o `reabierta` por sucursal.

Al cerrar, el sistema toma los recibos emitidos por ese cajero y sucursal desde la apertura hasta el cierre, los agrupa por concepto de pago y forma de pago, y genera `detalle_cierre_caja`. Guarda el monto declarado, el total calculado y la diferencia.

## Revisión del cierre

- `POST /api/v1/caja/sesiones/{sesion}/validar`
- `POST /api/v1/caja/sesiones/{sesion}/observar` — requiere `motivo`.
- `POST /api/v1/caja/sesiones/{sesion}/reabrir` — requiere `motivo`.

Al reabrir, se conserva una fotografía del cierre previo y sus detalles en la sesión antes de permitir un nuevo cálculo. No se modifican ni se eliminan recibos emitidos.
