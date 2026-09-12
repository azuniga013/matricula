# Plan de desarrollo: Exámenes de nivelación

## Propósito

Permitir que un estudiante se matricule y pague un examen de nivelación como
una oferta académica con horario y cupo; que un evaluador determine el último
nivel regular acreditado; y que el estudiante continúe después con la oferta
regular recomendada.

La nivelación es un mecanismo de ubicación, no una matrícula de curso regular
ni una certificación de niveles cursados.

## Punto de partida confirmado

- Ya existe el concepto contable `PEX` (Examen de nivelación).
- Las ofertas académicas ya proporcionan sucursal, período, horario, aula,
  cupo y plan de cobro; una matrícula sobre la oferta genera las obligaciones
  y pagos normales.
- Ya existe `evaluaciones_nivelacion`; el validador de prerrequisitos reconoce
  una evaluación aprobada del mismo plan con orden suficiente.
- Actualmente no hay una pantalla ni rutas operativas para programar,
  registrar y mostrar el resultado de la nivelación.

## Alcance y no regresión

Este desarrollo no debe modificar, regenerar ni reclasificar automáticamente
matrículas, pagos, obligaciones, recibos, cupos, calificaciones o historiales
ya existentes.

- Las nuevas columnas deben ser aditivas y seguras: valores nulos cuando
  corresponda y valor por defecto `regular` para ofertas existentes.
- Las ofertas de examen ya creadas se marcan manualmente por un usuario con
  permiso; no se infiere su tipo por nombre, código ni monto.
- No se crea `historial_academico`, certificado ni calificación regular al
  resolver un examen de nivelación.
- Un pago `PEX` aprobado conserva el ciclo actual de pago, recibo, caja y
  auditoría. El nuevo módulo no crea un segundo cobro ni altera el pago.

## Estructura funcional y de datos propuesta

### Oferta de nivelación

Añadir a `ofertas_academicas` un tipo explícito:

```text
tipo_oferta: regular | nivelacion
```

La clasificación pertenece a la oferta, no al nombre del nivel, porque una
oferta de evaluación tiene reglas operativas distintas de una oferta regular.
Mantiene sus relaciones actuales con nivel, período, sucursal, horario, aula,
docente, cupo y plan de cobro. Su plan de cobro debe incluir el concepto `PEX`.

La oferta de nivelación no se presenta como avance académico, curso actual,
grupo de WhatsApp de curso ni resultado de reportes de niveles regulares.

### Resultado de la evaluación

Reutilizar `evaluaciones_nivelacion` como el resultado auditado y agregarle,
de manera aditiva, el origen operativo:

```text
matricula_examen_id       Matrícula pagada del examen; única por resultado.
oferta_examen_id          Oferta de tipo nivelacion donde se presentó.
nivel_acreditado_id       Último nivel regular que el estudiante demostró dominar.
nivel_recomendado_id      Siguiente nivel regular calculado por el sistema.
evaluado_por / evaluado_en Evaluador y fecha de decisión.
estado                    pendiente | aprobada | rechazada | anulada.
```

El campo académico vigente puede migrarse semánticamente a
`nivel_acreditado_id` conservando compatibilidad hasta terminar el cambio; no
se debe reinterpretar ni modificar resultados históricos automáticamente.

`nivel_recomendado_id` es una fotografía calculada, no un dato elegido de
forma libre por el evaluador. Si acredita Inglés 2, el sistema recomienda
Inglés 3 del mismo plan y versión. Un resultado aprobado debe tener nivel
acreditado; uno rechazado no debe habilitar ninguno.

## Flujo del estudiante y del evaluador

```text
Oferta de nivelación abierta (PEX, horario y cupo)
  -> estudiante reserva la oferta de examen
  -> paga PEX por el flujo actual
  -> pago aprobado y matrícula del examen habilitada para evaluación
  -> evaluador registra nota y nivel acreditado
  -> sistema calcula nivel recomendado
  -> estudiante consulta resultado y elige una oferta regular recomendada
  -> reserva y paga la matrícula regular con el flujo existente
```

1. El estudiante ve las ofertas de tipo `nivelacion` disponibles para su
   sucursal y plan y se matricula normalmente.
2. La oferta consume cupo y el pago `PEX` genera recibo exactamente como hoy.
3. Solo una matrícula de examen pagada/matriculada y aún sin resultado puede
   aparecer como pendiente de evaluación.
4. El evaluador indica nota, observaciones y nivel acreditado. El backend
   valida que sea un nivel regular, del mismo plan y versión del examen.
5. En una transacción se guarda el resultado, la decisión, el usuario y su
   bitácora. No se crea historial académico.
6. El portal muestra el resultado y el botón **Ver opciones del nivel
   recomendado**. El estudiante elige horario y oferta; nunca se reserva de
   modo automático.
7. La reserva de la oferta regular conserva validaciones de cupo, ventana de
   matrícula, sucursal, duplicidad, conflictos y plan de cobro.

## Regla de nivel acreditado

El evaluador no elige directamente la matrícula destino. Elige el último nivel
que el estudiante acredita; el sistema deriva el siguiente nivel regular.

| Nivel acreditado | Nivel recomendado |
|---|---|
| Inglés 1 | Inglés 2 |
| Inglés 2 | Inglés 3 |
| Ninguno / rechazo | Inglés 1 o sin recomendación, según política visible |

Guardar por error Inglés 3 como acreditado cuando el estudiante debe empezar
en Inglés 3 habilitaría también Inglés 4. Por ello, la interfaz debe mostrar la
recomendación calculada antes de confirmar y el backend debe calcularla de
nuevo.

## Reglas de negocio afectadas

- **Prerrequisitos:** una evaluación `aprobada` y no `anulada` satisface los
  prerrequisitos de igual o menor orden dentro del mismo plan y versión.
- **P-063 (avance en período):** la nivelación es una excepción de ubicación:
  puede habilitar la matrícula del nivel recomendado en el mismo período,
  porque no representa haber cursado y aprobado el nivel anterior. Debe quedar
  explícita en el validador y en la documentación.
- **Conflictos de horario:** una matrícula de examen finalizada no bloquea una
  reserva de oferta regular posterior. Mientras el examen esté pendiente o
  coincida operativamente, se conserva la validación de conflicto.
- **Pagos y recibos:** PEX conserva todas las validaciones financieras
  existentes; no se crean obligaciones de curso ni cuotas desde el resultado.
- **Historial, certificados y reportes:** el examen se reporta por separado y
  nunca como nivel cursado/aprobado. Los certificados continúan basándose solo
  en historial o calificación válida de una oferta regular.
- **Anulación:** anular una evaluación conserva evidencia y evita que siga
  habilitando reservas futuras; no cancela una matrícula regular ya aprobada.
  Una corrección posterior requiere gestión académica autorizada y bitácora.

## Pantallas y navegación

### Portal del estudiante

1. **Examen de nivelación**: ofertas disponibles de tipo nivelación con
   horario, cupo y costo PEX; usa reserva y pago ya existentes.
2. **Mi resultado de nivelación**: estado, nota, observaciones, nivel
   acreditado y nivel recomendado.
3. **Ver opciones del nivel recomendado**: reutiliza el selector normal de
   plan, nivel y oferta, preseleccionando el nivel calculado.

### Administración > Académico > Nivelaciones

1. **Pendientes de evaluar**: matrículas de examen pagadas, filtrables por
   período, sucursal, plan, oferta, estado y evaluador.
2. **Registrar resultado**: nota, observaciones, nivel acreditado y vista
   previa del nivel recomendado; no permite seleccionar niveles de otro plan.
3. **Historial de nivelaciones**: resultados aprobados, rechazados y anulados
   con matrícula y pago PEX de origen, auditoría y enlace a la oferta regular
   elegida cuando exista.

La pantalla existente de Ofertas Académicas solo requiere el selector de tipo
de oferta; Pagos, Caja y Recibos no requieren pantallas nuevas.

## Seguridad, alcance y auditoría

- Crear módulo RBAC `nivelaciones` con permisos explícitos para consultar,
  registrar/evaluar, anular y exportar; registrar permisos con
  `SeguridadRbacSeeder`.
- Aplicar alcance de sucursal al listado, la matrícula de examen y el resultado.
- El estudiante solo puede consultar sus propios resultados y reservar la
  oferta regular recomendada, nunca aprobarse ni modificar la evaluación.
- Registrar bitácora de creación/anulación de resultado, con valores antes y
  después, usuario, fecha y motivo cuando aplique.

## Casos de uso y pruebas requeridas

La lógica nueva debe vivir en `app/Modules/Nivelaciones/` y no en controladores:

```text
CasosUso/
  RegistrarResultadoNivelacion
  AnularResultadoNivelacion
Repositorios/
  NivelacionRepositorio
Servicios/
  ResolutorNivelRecomendado
  ValidadorResultadoNivelacion
```

Pruebas mínimas:

1. La migración conserva registros existentes y toda oferta histórica queda
   `regular` por defecto.
2. Una oferta nivelación crea y cobra PEX por el flujo de matrícula normal.
3. No se puede evaluar una matrícula no pagada/no matriculada, ajena a la
   sucursal o de una oferta regular.
4. El resultado aprobado solo acepta un nivel acreditado regular del mismo
   plan y calcula correctamente el siguiente nivel.
5. El resultado aprobado permite reservar el nivel recomendado; uno rechazado
   o anulado no.
6. La nivelación no crea historial, certificado ni calificación regular.
7. Una matrícula de examen concluida no bloquea por horario la oferta regular.
8. El portal protege propiedad; RBAC y alcance devuelven `403` al usuario sin
   permiso o fuera de sucursal.
9. PEX conserva pago, recibo y auditoría; el resultado no modifica importes ni
   obligaciones históricas.

## Decisiones operativas pendientes

- Política de reintentos: se recomienda una nueva matrícula PEX por intento y
  conservar todos los resultados.
- Política ante rechazo: recomendar Nivel 1 o no recomendar ninguna oferta.
- Vigencia del resultado: definir si una nivelación aprobada vence; por defecto
  se propone que no venza salvo anulación autorizada.
- Nivel final del plan: definir si una nivelación puede marcar el plan como
  completado o si requiere una evaluación/certificación separada.
- Regla exacta para conflicto de horario cuando el examen y la oferta regular
  se solapan antes de que la evaluación esté cerrada.
