<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK Docentes | Cursos San Vicente de Paúl</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-950 p-5 font-sans text-slate-100 sm:p-10">
    <main class="mx-auto max-w-2xl rounded-3xl border border-white/10 bg-white/5 p-7 shadow-2xl sm:p-10">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-300">Cursos San Vicente de Paúl</p>
        <h1 class="mt-3 text-3xl font-bold">Aplicación para docentes</h1>
        <p class="mt-3 text-slate-300">Descarga oficial para consultar ofertas, alumnos, asistencias y calificaciones, incluso sin conexión.</p>

        @if ($publicacion)
            <section class="mt-8 rounded-2xl bg-white p-6 text-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><h2 class="text-xl font-bold">Versión {{ $publicacion->version }}</h2><p class="text-sm text-slate-500">Código Android {{ $publicacion->version_code }} · Publicada {{ $publicacion->publicado_en?->format('d/m/Y H:i') }}</p></div>
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Disponible</span>
                </div>
                @if ($publicacion->notas_version)<p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $publicacion->notas_version }}</p>@endif
                @if ($qrDataUri)
                    <div class="mt-5 flex flex-col items-center gap-1 rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <img src="{{ $qrDataUri }}" alt="Código QR de descarga de la APK" class="h-36 w-36 rounded-lg bg-white shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Escanea para descargar la APK en tu teléfono</p>
                    </div>
                @endif
                <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="font-medium text-slate-500">Tamaño</dt><dd>{{ number_format($publicacion->tamano_bytes / 1048576, 2) }} MB</dd></div><div><dt class="font-medium text-slate-500">SHA-256</dt><dd class="break-all font-mono text-xs">{{ $publicacion->sha256 }}</dd></div></dl>
                <a href="{{ route('apk-docentes.descargar') }}" class="mt-6 inline-flex rounded-xl bg-brand-600 px-5 py-3 font-semibold text-white hover:bg-brand-700">Descargar APK oficial</a>
            </section>
        @else
            <section class="mt-8 rounded-2xl border border-amber-400/30 bg-amber-400/10 p-5 text-amber-100"><h2 class="font-bold">Próximamente</h2><p class="mt-1 text-sm">Aún no se ha publicado una versión firmada de la APK para docentes.</p></section>
        @endif
        <p class="mt-8 text-xs leading-relaxed text-slate-400">Instala únicamente APK descargadas desde esta URL oficial. Para utilizarla se requiere un usuario docente activo del Panel Administrativo.</p>
    </main>
</body>
</html>
