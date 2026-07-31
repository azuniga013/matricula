# Despliegue en SmarterASP.NET

## Dominio único

Publicar el contenido de `backend/` en el espacio asignado a `matricula.cursossanvicente.com`.

El directorio público del sitio debe apuntar a `backend/public`, no a la raíz de Laravel. Si el panel no permite cambiar el directorio raíz, publicar el contenido de `backend/public` como raíz del subdominio y mantener el resto de Laravel fuera del directorio público.

Crear el archivo `.env` a partir de `.env.production.example`, definir `APP_KEY` con `php artisan key:generate --show` y colocar la contraseña real de MySQL únicamente en `DB_PASSWORD`.

Después de publicar, ejecutar:

```text
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

No copiar archivos generados desde otra máquina en `bootstrap/cache/`. El
archivo `backend/railpack.json` limpia el cache de rutas al finalizar el build
automático de Railpack, para no publicar rutas serializadas con la carpeta
temporal `/app`.

Confirmar permisos de escritura para `storage/` y `bootstrap/cache/`.

## Frontend y API

El frontend React se compila dentro de `backend/public` mediante `npm run build` ejecutado desde `frontend/`.

La aplicación web se sirve desde `/` y la API Laravel desde `/api/v1/...` en el mismo dominio.

## Validación

- `https://matricula.cursossanvicente.com/up` debe responder correctamente.
- El inicio de sesión en `https://matricula.cursossanvicente.com` debe poder llamar a `/api/v1/...` sin errores CORS.
