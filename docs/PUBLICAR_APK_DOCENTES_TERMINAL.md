# Publicar la APK de Docentes desde la terminal

Esta guía compila una APK firmada, la coloca en el servidor y la registra como
la única versión pública. No se guardan tokens, contraseñas ni credenciales en
archivos del repositorio.

## 1. Incrementar versión

Antes de cada publicación, actualizar `mobile-docentes/app.json`:

- `expo.version`: versión visible, por ejemplo `0.1.6`.
- `expo.android.versionCode`: entero mayor a cualquier versión ya publicada,
  por ejemplo `7`.

## 2. Compilar y verificar

Desde la raíz del repositorio:

```powershell
Set-Location mobile-docentes\android
.\gradlew.bat assembleRelease --no-daemon --console=plain
Set-Location ..\..

$apk = Resolve-Path 'mobile-docentes\android\app\build\outputs\apk\release\app-release.apk'
Get-FileHash -Algorithm SHA256 $apk
```

El archivo generado es `mobile-docentes/android/app/build/outputs/apk/release/app-release.apk`.

## 3. Copiar el APK al servidor

La carga directa multipart de una APK grande puede ser bloqueada por el hosting.
Usar SFTP/FTPS o el administrador de archivos de Plesk para copiar el archivo
al directorio privado de Laravel:

`storage/app/private/apk-docentes/`

Ejemplo con SFTP (usar el host y usuario de Plesk; la contraseña se solicita de
forma interactiva):

```powershell
$apk = (Resolve-Path 'mobile-docentes\android\app\build\outputs\apk\release\app-release.apk').Path
$comandos = @(
  'cd storage/app/private/apk-docentes',
  "put `"$apk`" Cursos-San-Vicente-Docentes-0.1.6.apk",
  'exit'
)
$comandos | sftp usuario@host
```

El cliente solicitará la contraseña de forma interactiva. Sustituir `usuario@host`
por la cuenta SFTP de Plesk y no incorporar esa clave en comandos, archivos ni Git.

## 4. Registrar y publicar por API

Usar un token Sanctum de un usuario que tenga los permisos
`distribucion_apk.crear` y `distribucion_apk.modificar`. Mantenerlo solo en la
sesión actual:

```powershell
$env:APK_DOCENTES_TOKEN = Read-Host 'Token Sanctum'
$headers = @{ Authorization = "Bearer $env:APK_DOCENTES_TOKEN" }
$body = @{ version = '0.1.6'; version_code = '7'; desde_servidor = '1'; publicar = '1'; notas_version = 'Publicación compilada desde terminal.' }

Invoke-RestMethod -Method Post `
  -Uri 'https://matricula.cursossanvicente.com/api/v1/distribucion-apk/docentes' `
  -Headers $headers -Body $body

Remove-Item Env:APK_DOCENTES_TOKEN
```

El parámetro `publicar=1` despublica la versión anterior y deja activa la nueva.
La API toma el archivo `.apk` más reciente del directorio privado.

## 5. Verificar la publicación

```powershell
Invoke-WebRequest -UseBasicParsing `
  -Uri 'https://matricula.cursossanvicente.com/apk/docentes'

Invoke-WebRequest -UseBasicParsing -Method Head `
  -Uri 'https://matricula.cursossanvicente.com/apk/docentes/descargar'
```

Confirmar que la página indique la versión esperada y que la descarga responda
`200` con `application/vnd.android.package-archive`.

## Si la versión ya fue registrada

No reutilizar un `versionCode`: la API lo rechaza para preservar el historial.
Incrementar versión/código, recompilar y repetir los pasos.
