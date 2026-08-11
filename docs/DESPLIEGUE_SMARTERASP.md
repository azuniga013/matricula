# Despliegue en SmarterASP.NET

> Nota 2026-08-10: este documento conserva contexto histórico, pero sus
> referencias a `backend/` y `frontend/` quedaron obsoletas. El proyecto actual
> es un monolito Laravel en la raíz del repositorio; publicar desde la raíz y
> usar `public/` como directorio público. Contrastar siempre con `AGENTS.md` y
> la configuración vigente del hosting antes de desplegar.

## Dominio único

Publicar el proyecto Laravel actual en el espacio asignado a
`matricula.cursossanvicente.com`.

El directorio público del sitio debe apuntar a `public`, no a la raíz de
Laravel. Si el panel no permite cambiar el directorio raíz, publicar el
contenido de `public` como raíz del subdominio y mantener el resto de Laravel
fuera del directorio público.

Crear el archivo `.env` a partir de `.env.production.example`, definir `APP_KEY` con `php artisan key:generate --show` y colocar la contraseña real de MySQL únicamente en `DB_PASSWORD`.

Después de publicar, ejecutar:

```text
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

No copiar archivos generados desde otra máquina en `bootstrap/cache/`. Si el
hosting ejecuta pasos automáticos de build, verificar que no deje rutas o
cachés serializados con paths temporales.

Confirmar permisos de escritura para `storage/` y `bootstrap/cache/`.

## Frontend y API

La interfaz actual es Blade + Alpine.js + Tailwind y se compila con Vite desde
la raíz del proyecto mediante `npm run build`.

La aplicación web se sirve desde `/` y la API Laravel desde `/api/v1/...` en el mismo dominio.

## Validación

- `https://matricula.cursossanvicente.com/up` debe responder correctamente.
- El inicio de sesión en `https://matricula.cursossanvicente.com` debe poder llamar a `/api/v1/...` sin errores CORS.
