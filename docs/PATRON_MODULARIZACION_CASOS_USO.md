# Patrón de modularización: cuándo extraer un caso de uso

Regla de decisión (semáforo) para saber cuándo la lógica debe vivir en
`app/Modules/<Modulo>/CasosUso` en vez de en el controlador. Es la
formalización de `P-032` y aplica la regla de arquitectura del 2026-08-10
registrada en `docs/PENDIENTES.md`.

La meta es un monolito Laravel modular con principios de hexagonal, no una
hexagonal pura: extraer casos de uso **solo donde hay reglas de negocio** y
dejar el CRUD simple y las lecturas en el controlador.

## Cómo decidir: semáforo

### Extraer ahora (rojo)

Extraer la operación a un caso de uso si cumple **cualquiera** de estas
condiciones:

- Cambia estados de negocio (transición de estado en una entidad: aprobar,
  anular, confirmar, cancelar, rechazar, corregir...).
- Toca más de una entidad (efecto encadenado: pago → matrícula → obligación →
  recibo → inventario).
- Requiere transacción (`DB::transaction`) o bloqueo de fila
  (`lockForUpdate`).
- Dispara efectos secundarios (sincronizar historial, generar recibo, descontar
  inventario, enviar notificación, registrar movimiento/auditoría).
- Tiene validaciones de dominio repetidas o reutilizables desde varios puntos
  (API administrativa, portal, otro caso de uso).
- Es difícil de probar a través del controlador (el caso de uso debe ser
  invocable directamente y con `ResultadoCasoUso`).

### Extraer después (amarillo)

Diferir si el módulo crecerá previsiblemente y hoy la lógica es pequeña, pero
se prevé que sume reglas (por ejemplo, un CRUD que va a ganar validaciones de
estado o efectos encadenados en el mismo ciclo).

### No extraer (verde)

Dejar en el controlador como orquestación ligera si es:

- CRUD directo de catálogo sin reglas de estado.
- Listado, lectura o consulta simple sin reglas de negocio relevantes
  (`index`, `show`, reportes de consulta/exportación).
- Endpoint que solo valida entrada y mapea a una respuesta.

## Estructura del módulo

```
app/Modules/<Modulo>/
├── CasosUso/        # Operaciones de negocio (una clase por operación, inyectables)
├── Repositorios/    # Interfaz + implementación Eloquent (solo persistencia/consultas)
├── Servicios/       # Reglas compartidas entre casos de uso (no persistencia)
└── Exceptions/      # Excepciones de dominio controladas (si aplica)
```

- `app/Modules/Comun/ContextoUsuario` y `app/Modules/Comun/ResultadoCasoUso`
  son compartidos y se usan en todos los módulos.
- Los repositorios se vinculan como `singleton` en `AppServiceProvider`.
- Los casos de uso se resuelven en el controlador con
  `app(CasoUso::class)->ejecutar(...)`; no usar inyección por constructor en
  el controlador (deja las dependencias en `null`).
- `ResultadoCasoUso::exito(...)` / `ResultadoCasoUso::error(codigo, mensaje,
  codigoError)` y el controlador traduce `!ok()` a JSON `resultado: R` con el
  `codigo_error` correspondiente.

## Precedentes aplicados en el repositorio

| Módulo | Casos de uso extraídos | Evidencia |
|---|---|---|
| Pagos (`P-028`) | `AprobarPago`, `RegistrarPago`, `RechazarPago`, `ActualizarLinkPago`, `SubirComprobantePago`, `EliminarPagoTotal` | `PagoTest` (30 pruebas, 120 aserciones) |
| Matrículas (`P-029`) | `ReservarMatricula`, `ConfirmarMatricula`, `CancelarMatricula` | `MatriculaTest` (16 pruebas) |
| Caja/Recibos (`P-035`) | `AbrirSesionCaja`, `CerrarSesionCaja`, `AnularRecibo`, `ReimprimirRecibo` | `CajaTest` (16 pruebas) |
| Calificaciones (`P-035`) | `RegistrarCalificaciones`, `ActualizarCalificacion` | `CalificacionTest` (20 pruebas) |
| Inventario (`P-035`) | `RegistrarInventario`, `AjustarExistencia`, `VenderLibro` | `InventarioLibroTest` (24 pruebas, 81 aserciones) |

### Lecturas decididas (P-030)

`index`, `show` y consultas de alcance (`obligaciones-estudiante`) se quedan en
el controlador: son lecturas puras sin transacciones ni efectos secundarios. La
única regla de negocio de `obligaciones-estudiante` ya vive en
`ResolutorFlujoMatricula`. Este criterio aplica igual a las lecturas de Caja,
Calificaciones e Inventario.

## Alcance actual de modularización (P-033)

### Modularizados

- `Pagos` (`P-028`): reglas de registro, aprobación, rechazo, comprobantes,
  links y eliminación total.
- `Matriculas` (`P-029`): reserva, confirmación, cancelación, cupos,
  prerrequisitos, conflictos y obligaciones.
- `Caja` / `Recibos` (`P-035`): apertura/cierre de sesión, anulación y
  reimpresión.
- `Calificaciones` (`P-035`): registro, actualización y sincronización de
  historial académico.
- `Inventario` (`P-035`): registro de stock, ajustes, venta y movimientos.

### Se quedan como están

- Catálogos simples y configuraciones CRUD sin transiciones de estado
  relevantes.
- Lecturas administrativas (`index`, `show`, `kardex`, `resumen`, consultas de
  alcance) mientras no disparen efectos ni concentren reglas de negocio.
- Reportes centrados en consulta/exportación.

### Diferidos

- Cualquier módulo nuevo o existente que hoy sea CRUD/lectura, pero luego gane
  transacciones, cambios de estado, efectos secundarios o validaciones de
  dominio repetidas, se reevalúa con este semáforo antes de extraer.

## Verificación esperada

Cada caso de uso debe tener al menos una prueba directa
(`test_<operacion>_mediante_caso_de_uso`) además de la prueba HTTP, cubriendo
éxito y los rechazos (código de error y `codigo_error`).
