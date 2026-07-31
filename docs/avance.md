# Avance - Plataforma Cursos San Vicente de Paúl

## 2026-07-28

### Pagos y recibos

- Revisar y validar que `fecha_proceso` y `fecha_recibo` se muestren igual en grid, detalle, impresión y reportes de pagos/recibos.

### Horarios

- La estructura de horarios cambió de tabla pivote `horario_dias` a columnas booleanas `lunes` a `domingo` dentro de `horarios`.
- La pantalla de Catálogos > Horarios fue reconstruida para capturar esos días con checkboxes individuales.
- `HorarioController`, `ValidaConflictoHorario`, seeders y pruebas ya fueron actualizados.

### Base local

- `SESSION_DRIVER` quedó en `file` para evitar dependencia de la tabla `sessions` en desarrollo local con SQLite.

## 2026-07-25

### 1. Matrícula — Grid no mostraba datos

**Causas:**
- La vista usaba `m.estudiante?.nombres` y `m.estudiante?.apellidos`, pero el modelo `Estudiante` tiene campos `nombre`/`apellido` (sin "s"). Toda la columna de nombre se renderizaba vacía.
- El eager load de `ofertaAcademica` no incluía `horario_id` ni la relación `horario`, por lo que `m.oferta_academica?.horario` siempre era `undefined`.

**Correcciones:**
- `resources/views/admin/matriculas.blade.php`: 4 ocurrencias de `nombres`/`apellidos` → `nombre`/`apellido` (líneas 58, 127, 212, 283).
- `app/Http/Controllers/Api/V1/Matriculas/MatriculaController.php`: se agregaron `horario_id`, `modalidad_id`, `sucursal_id` al `select()` y el eager load `ofertaAcademica.horario`. Se cambió a sintaxis de clausura (el shorthand `with('rel:col1,col2')` no funciona en esta versión de Laravel).

### 2. Planes de Estudio — Descripción y Estado

**Qué se hizo:**
- La BD ya tenía `descripcion` y `estado` en la migración y modelo, pero la UI no los exponía.
- Se agregó soporte para tipo `textarea` en el modal genérico de catálogos.
- Se agregaron campos `descripcion` (textarea) y `estado` (select Activo/Inactivo) al formulario de Planes.
- Se agregó columna "Descripción" en la tabla.
- Se eliminó el scope `activos()` del `index()` del controlador para listar todos.

**Archivos:**
- `resources/views/admin/catalogos.blade.php`
- `app/Http/Controllers/Api/V1/Academico/PlanEstudioController.php`

### 3. Planes de Estudio — Versiones CRUD inline

**Qué se hizo:**
- Se agregó columna "Vers." con conteo de versiones en la tabla de Planes.
- Botón "Versiones" por fila que abre un modal con la lista de versiones.
- El formulario crear/editar versión aparece **inline** dentro del mismo modal (alterna entre lista y formulario con `mostrandoFormVersion`).
- Se agregó `vigente_desde` al `update()` de `VersionPlanEstudioController`.
- Se eliminó el scope `activos()` del `index()` de versiones.

**Corrección de bug:** `editVersionId` no estaba declarado en el estado del componente Alpine, y reemplazar todo el objeto `versionForm` rompía la reactividad. Se cambió a asignación individual de propiedades y se declaró `editVersionId: null`.

**Archivos:**
- `resources/views/admin/catalogos.blade.php`
- `app/Http/Controllers/Api/V1/Academico/VersionPlanEstudioController.php`

### 4. Departamentos Académicos — Estado

**Qué se hizo:**
- Se agregó campo `estado` (select Activo/Inactivo) al formulario de Departamentos.
- Se agregó `estado` a la validación de `store()`.
- Se eliminó el scope `activos()` del `index()` para listar todos.

**Archivos:**
- `resources/views/admin/catalogos.blade.php`
- `app/Http/Controllers/Api/V1/Academico/DepartamentoAcademicoController.php`

### 5. Regla documentada

**Sintaxis `with()`**: la forma abreviada `with(['relacion: col1,col2'])` **no funciona** en la versión de Laravel del proyecto (retorna `null`). Siempre usar sintaxis de clausura: `with(['relacion' => fn($q) => $q->select('col1','col2')])`. Además, los `select()` en eager loading deben incluir las columnas FK o las relaciones se resuelven como `null`.

### Tests

**141 tests, 416 assertions — todos pasan.**
