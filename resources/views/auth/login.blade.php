<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión — Cursos San Vicente de Paúl</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; min-height: 100vh; background: linear-gradient(135deg, #0f172a, #1e293b 55%, #0b1120); display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .wrap { width: 100%; max-width: 28rem; }
        .hdr { text-align: center; margin-bottom: 2rem; }
        .ico { display: inline-flex; align-items: center; justify-content: center; width: 4rem; height: 4rem; border-radius: 1rem; margin-bottom: 1.25rem; background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 25px 50px -12px rgba(59,130,246,0.3); }
        .ico svg { width: 2.25rem; height: 2.25rem; color: #fff; }
        .ttl { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .sub { font-size: 0.875rem; color: rgba(255,255,255,0.6); margin-top: 0.25rem; }
        .card { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .fg { margin-bottom: 1.25rem; }
        .fl { display: block; font-size: 0.875rem; font-weight: 500; color: rgba(255,255,255,0.72); margin-bottom: 0.375rem; }
        .fi { display: block; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; color: #fff; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; outline: none; }
        .fi::placeholder { color: rgba(255,255,255,0.3); }
        .fi:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .eb { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; }
        .et { font-size: 0.875rem; color: #fca5a5; }
        .btn { display: block; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 600; color: #fff; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; border-radius: 0.5rem; cursor: pointer; text-decoration: none; text-align: center; }
        .btn:hover { opacity: 0.93; }
        .note { text-align: center; font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hdr">
            <div class="ico">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" /></svg>
            </div>
            <h1 class="ttl">Cursos San Vicente de Paúl</h1>
            <p class="sub">Panel Administrativo</p>
        </div>

        <div class="card">
            @if (request('sesion') === 'expirada')
                <div class="eb" style="background:rgba(59,130,246,0.12);border-color:rgba(59,130,246,0.25);">
                    <p class="et" style="color:#bfdbfe;">Su sesión expiró. Ingrese nuevamente.</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="eb">
                    <p class="et">{{ $errors->first() }}</p>
                </div>
            @endif

            <form id="formulario-login" method="POST" action="{{ route('web.login') }}">
                @csrf
                <div class="fg">
                    <label class="fl" for="email">Correo electrónico</label>
                    <input class="fi" type="email" id="email" name="email" required autocomplete="email" value="{{ old('email') }}" placeholder="admin@cursossvp.hn">
                </div>
                <div class="fg">
                    <label class="fl" for="password">Contraseña</label>
                    <input class="fi" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                <div class="fg" style="display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.72);font-size:.875rem;">
                    <input type="checkbox" id="remember" name="remember" value="1" style="width:1rem;height:1rem;accent-color:#3b82f6;">
                    <label for="remember">Recordarme en este equipo</label>
                </div>
                <button class="btn" type="submit">Iniciar Sesión</button>
            </form>
        </div>

        <p class="note">&copy; {{ date('Y') }} Cursos San Vicente de Paúl. Todos los derechos reservados.</p>
    </div>
    <script>
        document.getElementById('formulario-login').addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const formulario = evento.currentTarget;
            const boton = formulario.querySelector('button[type="submit"]');
            const correo = formulario.email.value;
            const contrasena = formulario.password.value;
            formulario.parentElement.querySelectorAll('.eb').forEach((alerta) => alerta.remove());

            boton.disabled = true;
            boton.textContent = 'Ingresando...';

            try {
                await window.api.login(correo, contrasena);
                window.location.assign('/admin');
            } catch (error) {
                const mensaje = error.response?.data?.errors?.email?.[0]
                    || error.response?.data?.mensaje
                    || 'No fue posible iniciar sesi\\u00f3n. Verifique sus credenciales.';
                const alerta = document.createElement('div');
                alerta.className = 'eb';
                alerta.innerHTML = '<p class="et"></p>';
                alerta.querySelector('.et').textContent = mensaje;
                formulario.before(alerta);
            } finally {
                boton.disabled = false;
                boton.textContent = 'Iniciar sesi\\u00f3n';
            }
        });
    </script>
</body>
</html>
