<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión — Portal del Estudiante</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700;800" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; background: #064e3b; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .wrap { width: 100%; max-width: 28rem; }
        .hdr { text-align: center; margin-bottom: 2rem; }
        .ico { display: inline-flex; align-items: center; justify-content: center; width: 4rem; height: 4rem; border-radius: 1rem; margin-bottom: 1.25rem; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 25px 50px -12px rgba(16,185,129,0.3); }
        .ico svg { width: 2.25rem; height: 2.25rem; color: #fff; }
        .ttl { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .sub { font-size: 0.875rem; color: rgba(255,255,255,0.5); margin-top: 0.25rem; }
        .card { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .fg { margin-bottom: 1.25rem; }
        .fl { display: block; font-size: 0.875rem; font-weight: 500; color: rgba(255,255,255,0.7); margin-bottom: 0.375rem; }
        .fi { display: block; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; color: #fff; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.5rem; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
        .fi::placeholder { color: rgba(255,255,255,0.3); }
        .fi:focus { border-color: #34d399; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
        .eb { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; }
        .et { font-size: 0.875rem; color: #fca5a5; }
        .btn { display: block; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 600; color: #fff; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(16,185,129,0.25); transition: opacity 0.2s, transform 0.1s; text-align: center; }
        .btn:hover { opacity: 0.9; }
        .btn:active { transform: scale(0.98); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .sp { display: inline-block; width: 1.125rem; height: 1.125rem; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 9999px; animation: spin 0.6s linear infinite; vertical-align: middle; margin-right: 0.5rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .links { text-align: center; margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .links a { font-size: 0.875rem; color: rgba(255,255,255,0.6); text-decoration: none; transition: color 0.2s; }
        .links a:hover { color: #34d399; }
        .ft { text-align: center; font-size: 0.75rem; color: rgba(255,255,255,0.2); margin-top: 1.5rem; }
        .hide { display: none !important; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hdr">
            <div class="ico">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" /></svg>
            </div>
            <h1 class="ttl">Cursos San Vicente de Paúl</h1>
            <p class="sub">Portal del Estudiante</p>
        </div>

        <div class="card">
            <div id="sesionExpiradaBox" class="hide" style="background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1rem;">
                <p style="font-size:0.875rem;color:#bfdbfe;">Su sesión expiró. Ingrese nuevamente.</p>
            </div>
            <div id="errBox" class="eb hide"><p class="et" id="errText"></p></div>
            <form id="loginForm" onsubmit="return doLogin(event)">
                <div class="fg">
                    <label class="fl" for="email">Correo electrónico</label>
                    <input class="fi" type="email" id="email" name="email" required autocomplete="email" placeholder="estudiante@correo.com">
                </div>
                <div class="fg">
                    <label class="fl" for="password">Contraseña</label>
                    <input class="fi" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                <div class="fg">
                    <button class="btn" type="submit" id="btnSubmit"><span id="btnLabel">Iniciar Sesión</span></button>
                </div>
            </form>
            <div class="links">
                <a href="/estudiante/registro">¿Es nuevo? Registrarse</a>
                <a href="/estudiante/activar">¿Tiene cuenta? Activar acceso</a>
                <a href="#" onclick="showReenviar(); return false;">Reenviar credenciales</a>
            </div>

            <div id="reenviarBox" class="hide mt-4 pt-4 border-t border-white/10">
                <p class="fl" style="color:rgba(255,255,255,0.6);font-size:0.75rem;margin-bottom:0.75rem;">Ingrese su correo para recibir una nueva contraseña</p>
                <div class="fg" style="margin-bottom:0.75rem;">
                    <input class="fi" type="email" id="reenviarEmail" placeholder="estudiante@correo.com">
                </div>
                <div id="reenviarErr" class="eb hide" style="margin-bottom:0.75rem;"><p class="et" id="reenviarErrText"></p></div>
                <div id="reenviarOk" class="hide" style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:0.75rem;"><p style="font-size:0.875rem;color:#6ee7b7;" id="reenviarOkText"></p></div>
                <button class="btn" type="button" id="btnReenviar" onclick="doReenviar()">Enviar credenciales</button>
            </div>
        </div>

        <p class="ft">&copy; {{ date('Y') }} Cursos San Vicente de Paúl. Todos los derechos reservados.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios@1/dist/axios.min.js"></script>
    <script>
        window.extractError = function(err, fallback) {
            if (!err || !err.response) return fallback || 'Error de conexión';
            var data = err.response.data;
            return data.mensaje || data.mensaje_usuario || data.message || data.error || fallback || 'Error desconocido';
        };
        window.extractErrorCode = function(err) {
            if (!err || !err.response || !err.response.data) return null;
            return err.response.data.codigo_error || null;
        };
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('sesion') === 'expirada') {
                document.getElementById('sesionExpiradaBox').classList.remove('hide');
            }
            localStorage.removeItem('estudiante_token');
            localStorage.removeItem('estudiante_data');
            sessionStorage.removeItem('estudiante_token');
            sessionStorage.removeItem('estudiante_data');
            var token = localStorage.getItem('estudiante_token');
            if (token) { window.location.href = '/estudiante'; }
        })();

        function showError(msg) { document.getElementById('errText').textContent = msg; document.getElementById('errBox').classList.remove('hide'); }
        function hideError() { document.getElementById('errBox').classList.add('hide'); }
        function setLoading(on) {
            var btn = document.getElementById('btnSubmit'); var lbl = document.getElementById('btnLabel');
            if (on) { btn.disabled = true; lbl.innerHTML = '<span class="sp"></span>Ingresando...'; }
            else { btn.disabled = false; lbl.textContent = 'Iniciar Sesión'; }
        }

        async function doLogin(e) {
            e.preventDefault(); hideError(); setLoading(true);
            try {
                var resp = await axios.post('/api/v1/estudiantes/iniciar-sesion', {
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value
                });
                if (resp.data.resultado === 'A') {
                    localStorage.setItem('estudiante_token', resp.data.data.token);
                    sessionStorage.setItem('estudiante_token', resp.data.data.token);
                    if (resp.data.data.expires_at) localStorage.setItem('estudiante_token_expires_at', resp.data.data.expires_at);
                    localStorage.setItem('estudiante_data', JSON.stringify(resp.data.data.estudiante));
                    window.axios.defaults.headers.common['Authorization'] = `Bearer ${resp.data.data.token}`;
                    window.location.href = '/estudiante';
                } else { showError(resp.data.mensaje || 'Credenciales inválidas'); }
            } catch (err) { showError(window.extractError(err, 'Error al conectar con el servidor')); }
            finally { setLoading(false); }
        }

        function showReenviar() {
            document.getElementById('reenviarBox').classList.remove('hide');
            document.getElementById('reenviarErr').classList.add('hide');
            document.getElementById('reenviarOk').classList.add('hide');
            document.getElementById('reenviarEmail').value = document.getElementById('email').value;
        }

        async function doReenviar() {
            var btn = document.getElementById('btnReenviar');
            var email = document.getElementById('reenviarEmail').value;
            if (!email) { document.getElementById('reenviarErrText').textContent = 'Ingrese su correo electrónico'; document.getElementById('reenviarErr').classList.remove('hide'); return; }
            document.getElementById('reenviarErr').classList.add('hide');
            document.getElementById('reenviarOk').classList.add('hide');
            btn.disabled = true; btn.textContent = 'Enviando...';
            try {
                var resp = await axios.post('/api/v1/estudiantes/reenviar-credenciales', { email: email });
                if (resp.data.resultado === 'A') {
                    document.getElementById('reenviarOkText').textContent = resp.data.mensaje;
                    document.getElementById('reenviarOk').classList.remove('hide');
                } else {
                    document.getElementById('reenviarErrText').textContent = resp.data.mensaje || 'Error';
                    document.getElementById('reenviarErr').classList.remove('hide');
                }
            } catch (err) {
                document.getElementById('reenviarErrText').textContent = window.extractError(err, 'Error al conectar con el servidor');
                document.getElementById('reenviarErr').classList.remove('hide');
            } finally { btn.disabled = false; btn.textContent = 'Enviar credenciales'; }
        }
    </script>
</body>
</html>
