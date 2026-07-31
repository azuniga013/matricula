# API de niveles académicos y modalidades

Las rutas requieren token Sanctum Bearer de un usuario `ADMIN`.

## Modalidades

- `GET|POST /api/v1/modalidades`
- `GET|PUT|PATCH /api/v1/modalidades/{modalidad}`

`tipo` solo puede ser `regimen_academico` (por ejemplo, Intensivo o Semi Intensivo) o `atencion` (Presencial o Virtual).

## Niveles académicos

- `GET|POST /api/v1/niveles-academicos`
- `GET|PUT|PATCH /api/v1/niveles-academicos/{nivelAcademico}`

Ejemplo de creación:

```json
{
  "version_plan_estudio_id": 1,
  "codigo": "ING-1",
  "nombre": "Inglés 1",
  "orden": 2,
  "nota_minima_aprobar": 80,
  "faltas_maximas_permitidas": 7,
  "modalidades": [1, 2],
  "prerrequisitos": [3],
  "estado": "activo"
}
```

Los prerrequisitos deben pertenecer a la misma versión del plan y tener un orden menor al nivel que los requiere.

Si en el formulario se cambia `version_plan_estudio_id`, la UI debe limpiar los prerrequisitos ya seleccionados para evitar validaciones cruzadas entre versiones.

Un estudiante puede saltar prerrequisitos si tiene un examen de nivelación aprobado para un nivel del mismo plan cuya `orden` sea igual o superior al prerrequisito requerido.
