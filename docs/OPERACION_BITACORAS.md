# Operación de Bitácoras

Guía operativa para producción sobre:

- toggles de bitácoras
- caché de parámetros globales
- retención
- purga manual
- validación del scheduler

## Parámetros globales

Grupo: `bitacoras`

### Toggles

- `AUDITORIA_CENTRAL_HABILITADA`
- `BITACORA_PETICIONES_HABILITADA`
- `BITACORA_SEGURIDAD_HABILITADA`
- `BITACORA_CORREOS_HABILITADA`

### Retención

- `RETENCION_AUDITORIA_DIAS`
- `RETENCION_PETICIONES_DIAS`
- `RETENCION_SEGURIDAD_DIAS`
- `RETENCION_CORREOS_DIAS`

## Valores sugeridos en producción

- `AUDITORIA_CENTRAL_HABILITADA = true`
- `BITACORA_SEGURIDAD_HABILITADA = true`
- `BITACORA_CORREOS_HABILITADA = true`
- `BITACORA_PETICIONES_HABILITADA = false`

- `RETENCION_AUDITORIA_DIAS = 180`
- `RETENCION_PETICIONES_DIAS = 30`
- `RETENCION_SEGURIDAD_DIAS = 365`
- `RETENCION_CORREOS_DIAS = 90`

## Bloque de comandos

### 1. Sembrar parámetros globales

```bash
php artisan db:seed --class=ParametroGlobalSeeder --force
```

### 2. Verificar parámetros cargados

```bash
php artisan tinker --execute="echo json_encode(Illuminate\Support\Facades\DB::table('parametros_globales')->where('grupo','bitacoras')->orderBy('codigo')->get(['grupo','codigo','valor'])->toArray());"
```

### 3. Probar purga sin borrar

```bash
php artisan bitacoras:purgar --dry-run
```

### 4. Ejecutar purga manual real

```bash
php artisan bitacoras:purgar
```

### 5. Ejecutar purga con override puntual

```bash
php artisan bitacoras:purgar --peticiones=15 --correos=60
```

### 6. Ver scheduler configurado

```bash
php artisan schedule:list
```

Debe aparecer una tarea similar a:

- `bitacoras:purgar` diaria a las `02:30`

### 7. Probar scheduler local/manual

```bash
php artisan schedule:run
```

## Caché de parámetros globales

El TTL de caché de `ParametroGlobal` se controla por config/env:

- variable: `PARAMETROS_GLOBALES_CACHE_TTL`
- default: `300` segundos

Si cambia en `.env`:

```bash
php artisan config:clear
php artisan cache:clear
```

## Recomendación operativa

### Producción normal

- apagar `BITACORA_PETICIONES_HABILITADA`
- mantener activas las demás

### Soporte o incidente

- encender temporalmente `BITACORA_PETICIONES_HABILITADA`
- luego volver a apagarla

## Nota

`docs/PENDIENTES.md` registra el estado del trabajo.
Este archivo es la referencia operativa para ejecutar y validar las bitácoras.
