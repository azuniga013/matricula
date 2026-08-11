# Patrón obligatorio para módulos RBAC

## Objetivo

Un módulo nuevo no debe inventar tablas de seguridad, repetir middleware ni decidir permisos mediante el nombre de un rol. Usa las tablas ya definidas: `modulos`, `opciones_modulo`, `permisos`, `usuario_roles`, `rol_permisos`, `alcances_rol` y `alcances_usuario`.

## 1. Declarar el módulo

Agregar el código y nombre en `config/rbac.php`. El seeder
`SeguridadRbacSeeder` usa `RegistroPermisosService` para crear, sin duplicar,
los permisos estándar:

```text
<modulo>.consultar
<modulo>.crear
<modulo>.modificar
<modulo>.eliminar
<modulo>.aprobar
<modulo>.anular
<modulo>.exportar
<modulo>.configurar
```

Ejecutar después:

```powershell
php artisan db:seed --class=SeguridadRbacSeeder --force
```

## 2. Registrar una API REST

Dentro del grupo `auth:sanctum`, usar el macro único:

```php
Route::apiResourceProtegido('libros', LibroController::class, 'inventario', [
    'parameters' => ['libros' => 'libro'],
    'only' => ['index', 'store', 'show', 'update'],
]);
```

El macro aplica automáticamente `consultar` a `index`/`show`, `crear` a `store`, `modificar` a `update` y `eliminar` a `destroy`.

Para una operación especial usar:

```php
Route::accionProtegida('post', '/pagos/{pago}/aprobar', [PagoController::class, 'aprobar'], 'pagos.aprobar');
```

El permiso no sustituye el alcance. El controlador o servicio debe aplicar `ResolutorAlcanceDatos` cuando la operación dependa de sucursal, docente, alumno o propiedad del registro.

## 3. Aplicar el permiso en la pantalla

El login administrativo entrega `usuario.permisos`. La interfaz Blade +
Alpine.js los consume desde `window.api` y decide qué acciones ocultar o
mostrar sin reemplazar la validación backend:

```html
<button x-show="api.hasPermission('inventario.crear')">Crear libro</button>
```

Ocultar una acción en frontend mejora la experiencia; la API continúa siendo la autoridad y responde `403_SIN_PERMISO` ante un acceso directo no permitido.

## 4. Prueba mínima obligatoria

Cada módulo debe probar que un usuario con el permiso requerido completa la operación y otro sin el permiso recibe `403_SIN_PERMISO`. Las APIs del portal de estudiante se prueban además contra un estudiante distinto para validar propiedad del registro.
