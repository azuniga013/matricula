<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activar Acceso — Portal del Estudiante</title>
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
        .sb { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; }
        .st { font-size: 0.875rem; color: #6ee7b7; }
        .btn { display: block; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 600; color: #fff; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 0.5rem; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(16,185,129,0.25); transition: opacity 0.2s, transform 0.1s; text-align: center; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .sp { display: inline-block; width: 1.125rem; height: 1.125rem; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 9999px; animation: spin 0.6s linear infinite; vertical-align: middle; margin-right: 0.5rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .links { text-align: center; margin-top: 1.5rem; }
        .links a { font-size: 0.875rem; color: rgba(255,255,255,0.6); text-decoration: none; }
        .links a:hover { color: #34d399; }
        .ft { text-align: center; font-size: 0.75rem; color: rgba(255,255,255,0.2); margin-top: 1.5rem; }
        .hide { display: none !important; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hdr">
            <div class="ico">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
            </div>
            <h1 class="ttl">Activar Acceso</h1>
            <p class="sub">Si ya es estudiante registrado, active su acceso aquí</p>
        </div>
        <div class="card">
            <div id="errBox" class="eb hide"><p class="et" id="errText"></p></div>
            <div id="succBox" class="sb hide"><p class="st" id="succText"></p></div>
            <form id="actForm" onsubmit="return doActivate(event)">
                <div class="fg"><label class="fl">Número de identidad *</label><input class="fi" type="text" id="identidad" required placeholder="0801-1990-12345"></div>
                <div class="fg"><label class="fl">Código de alumno *</label><input class="fi" type="text" id="codigo" required placeholder="EST-XXXXXXXX"></div>
                <div class="fg"><button class="btn" type="submit" id="btnSubmit"><span id="btnLabel">Activar Acceso</span></button></div>
            </form>
            <div class="links">
                <a href="/estudiante/login">Ya tengo acceso — Iniciar sesión</a>
                <a href="/estudiante/registro">Soy nuevo — Registrarse</a>
            </div>
        </div>
        <p class="ft">&copy; {{ date('Y') }} Cursos San Vicente de Paúl.</p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios@1/dist/axios.min.js"></script>
    <script>
        window.extractError = function(err, fallback) {
            if (err && err.response && err.response.data) {
                var d = err.response.data;
                if (d.errores) {
                    var msgs = [];
                    for (var k in d.errores) {
                        if (Array.isArray(d.errores[k])) msgs.push(d.errores[k].join(', '));
                        else msgs.push(d.errores[k]);
                    }
                    return msgs.join(' | ');
                }
                return d.mensaje || d.mensaje_usuario || d.message || d.error || fallback;
            }
            return (err && err.message) || fallback;
        };

        (function() {
            localStorage.removeItem('estudiante_token');
            localStorage.removeItem('estudiante_data');
            sessionStorage.removeItem('estudiante_token');
            sessionStorage.removeItem('estudiante_data');
        })();
        function showError(m) { document.getElementById('errText').textContent = m; document.getElementById('errBox').classList.remove('hide'); document.getElementById('succBox').classList.add('hide'); }
        function showSuccess(m) { document.getElementById('succText').textContent = m; document.getElementById('succBox').classList.remove('hide'); document.getElementById('errBox').classList.add('hide'); }
        function setLoading(on) { var b = document.getElementById('btnSubmit'), l = document.getElementById('btnLabel'); if (on) { b.disabled = true; l.innerHTML = '<span class="sp"></span>Activando...'; } else { b.disabled = false; l.textContent = 'Activar Acceso'; } }
        async function doActivate(e) {
            e.preventDefault(); setLoading(true);
            try {
                var r = await axios.post('/api/v1/estudiantes/activar', { identidad: document.getElementById('identidad').value, codigo: document.getElementById('codigo').value });
                if (r.data.resultado === 'A') { showSuccess(r.data.mensaje + ' Correo enmascarado: ' + r.data.data.correo); document.getElementById('actForm').reset(); }
                else { showError(r.data.mensaje || 'Error'); }
            } catch (err) { showError(window.extractError(err, 'Error al conectar')); }
            finally { setLoading(false); }
        }
    </script>
</body>
</html>
