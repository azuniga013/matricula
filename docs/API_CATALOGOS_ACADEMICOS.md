# API de catálogos académicos

Todas las rutas requieren un token Sanctum Bearer de un usuario con rol `ADMIN`.

## Departamentos académicos

- `GET /api/v1/departamentos-academicos`
- `POST /api/v1/departamentos-academicos`
- `GET /api/v1/departamentos-academicos/{departamentoAcademico}`
- `PUT|PATCH /api/v1/departamentos-academicos/{departamentoAcademico}`

Ejemplo de escritura:

```json
{
  "codigo": "ING",
  "nombre": "Inglés",
  "descripcion": "Área de formación en inglés",
  "estado": "activo"
}
```

## Planes de estudio

- `GET /api/v1/planes-estudio`
- `POST /api/v1/planes-estudio`
- `GET /api/v1/planes-estudio/{planEstudio}`
- `PUT|PATCH /api/v1/planes-estudio/{planEstudio}`

```json
{
  "departamento_academico_id": 1,
  "codigo": "ING-GEN",
  "nombre": "Inglés General",
  "descripcion": "Plan general de inglés",
  "estado": "activo"
}
```

## Versiones de plan de estudio

- `GET /api/v1/versiones-plan-estudio`
- `POST /api/v1/versiones-plan-estudio`
- `GET /api/v1/versiones-plan-estudio/{versionPlanEstudio}`
- `PUT|PATCH /api/v1/versiones-plan-estudio/{versionPlanEstudio}`

```json
{
  "plan_estudio_id": 1,
  "numero_version": 1,
  "vigente_desde": "2026-01-01",
  "vigente_hasta": null,
  "estado": "activo"
}
```

## Horarios

- `GET /api/v1/catalogos-academicos/horarios`
- `POST /api/v1/catalogos-academicos/horarios`
- `GET /api/v1/catalogos-academicos/horarios/{horario}`
- `PUT|PATCH /api/v1/catalogos-academicos/horarios/{horario}`

El contrato de escritura usa columnas booleanas por día, no una lista `dias`:

```json
{
  "codigo": "MAT-7AM",
  "nombre": "Matutino 7:00-9:00",
  "hora_inicio": "07:00",
  "hora_fin": "09:00",
  "es_24_horas": false,
  "lunes": true,
  "martes": false,
  "miercoles": true,
  "jueves": false,
  "viernes": true,
  "sabado": false,
  "domingo": false,
  "descripcion": "Lunes, miércoles y viernes"
}
```

Las respuestas conservan el sobre estándar `resultado`, `codigo`, `mensaje` y `data`.
