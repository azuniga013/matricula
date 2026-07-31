# API académica: asistencia, calificación e historial

Las rutas requieren autenticación administrativa, permiso de `calificaciones` y alcance de sucursal. Cuando el usuario tiene alcance docente, solo puede operar ofertas donde está asignado como docente.

## Pasar lista

`GET /api/v1/academico/ofertas` lista las ofertas disponibles dentro del alcance.

`GET /api/v1/ofertas-academicas/{oferta}/estudiantes` devuelve únicamente estudiantes con matrícula `matriculado` en la oferta.

`POST /api/v1/ofertas-academicas/{oferta}/asistencias` requiere `fecha_clase` y el arreglo `asistencias`.

Cada asistencia contiene `matricula_id`, `estado` (`presente`, `falta`, `justificada` o `tardanza`), opcionalmente `cuenta_como_falta` y `motivo`. Existe una sola asistencia por matrícula y fecha; un nuevo envío corrige el registro sin duplicarlo.

## Calificaciones e historial

`PUT /api/v1/matriculas/{matricula}/calificacion` recibe `nota_final` y opcionalmente `motivo_correccion`.

El sistema calcula las faltas desde `asistencias_estudiante`, selecciona la regla de aprobación más específica y actualiza la calificación y `historial_academico` en una transacción. El resultado incluye si aprueba, faltas, saldo pendiente y la regla aplicada.

La aprobación exige:

```text
nota_final >= nota_minima
y faltas <= faltas_maximas_permitidas
y sin saldo, cuando la regla lo configura
```

## Reglas de aprobación

- `GET /api/v1/reglas-aprobacion`
- `POST /api/v1/reglas-aprobacion`
- `PUT /api/v1/reglas-aprobacion/{regla}`

La regla puede ser general, por nivel, por modalidad o por nivel y modalidad. Se incluye una regla general inicial de 80 puntos y máximo 7 faltas. Para Semi Intensivo debe configurarse una regla específica con máximo 3 faltas; para Intensivo se usa máximo 7 faltas.

## Prerrequisitos y nivelación

Un estudiante no puede matricularse en un nivel si no cumple los prerrequisitos del plan. La validación también acepta un examen de nivelación aprobado: si existe una evaluación aprobada para un nivel del mismo plan con `orden` igual o superior al prerrequisito, ese prerrequisito se considera cumplido.
