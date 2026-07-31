<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro — Portal del Estudiante</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700;800" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; background: #064e3b; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .wrap { width: 100%; max-width: 32rem; }
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
        select.fi { color: #0f172a; background: rgba(255,255,255,0.92); }
        select.fi option { color: #0f172a; background: #fff; }
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
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .grid2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hdr">
            <div class="ico">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
            </div>
            <h1 class="ttl">Primer Ingreso</h1>
            <p class="sub">Regístrese para acceder al portal</p>
        </div>

        <div class="card">
            <div id="errBox" class="eb hide"><p class="et" id="errText"></p></div>
            <div id="succBox" class="sb hide"><p class="st" id="succText"></p></div>
            <form id="regForm" onsubmit="return doRegister(event)">
                <div class="grid2">
                    <div class="fg"><label class="fl">Nombre *</label><input class="fi" type="text" id="nombre" required placeholder="Juan"></div>
                    <div class="fg"><label class="fl">Apellido *</label><input class="fi" type="text" id="apellido" required placeholder="Pérez"></div>
                </div>
                <div class="fg"><label class="fl">Identidad *</label><input class="fi" type="text" id="identidad" required placeholder="0801-1990-12345"></div>
                <div class="fg"><label class="fl">Correo electrónico *</label><input class="fi" type="email" id="correo" required placeholder="correo@ejemplo.com"></div>
                <div class="fg"><label class="fl">Teléfono</label><input class="fi" type="text" id="telefono" placeholder="Opcional"></div>
                <div class="fg"><label class="fl">Sucursal *</label>
                    <select class="fi" id="sucursal_id" required>
                        <option value="">Seleccionar sucursal...</option>
                    </select>
                </div>
                <div class="grid2">
                    <div class="fg"><label class="fl">Contraseña *</label><input class="fi" type="password" id="password" required minlength="6" placeholder="Mínimo 6 caracteres"></div>
                    <div class="fg"><label class="fl">Confirmar contraseña *</label><input class="fi" type="password" id="password_confirmation" required placeholder="Repita la contraseña"></div>
                </div>
                <div class="fg"><button class="btn" type="submit" id="btnSubmit"><span id="btnLabel">Registrarse</span></button></div>
            </form>
            <div class="links"><a href="/estudiante/login">¿Ya tiene cuenta? Iniciar sesión</a></div>
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
            loadSucursales();
        })();
        async function loadSucursales() {
            try {
                var resp = await axios.get('/api/v1/estudiantes/sucursales');
                var data = resp.data.data?.data || resp.data.data || [];
                var sel = document.getElementById('sucursal_id');
                data.forEach(function(s) {
                    var opt = document.createElement('option');
                    opt.value = s.id; opt.textContent = s.nombre;
                    sel.appendChild(opt);
                });
            } catch(e) {}
        }
        function showError(msg) { document.getElementById('errText').textContent = msg; document.getElementById('errBox').classList.remove('hide'); document.getElementById('succBox').classList.add('hide'); }
        function showSuccess(msg) { document.getElementById('succText').textContent = msg; document.getElementById('succBox').classList.remove('hide'); document.getElementById('errBox').classList.add('hide'); }
        function setLoading(on) {
            var btn = document.getElementById('btnSubmit'); var lbl = document.getElementById('btnLabel');
            if (on) { btn.disabled = true; lbl.innerHTML = '<span class="sp"></span>Registrando...'; }
            else { btn.disabled = false; lbl.textContent = 'Registrarse'; }
        }
        async function doRegister(e) {
            e.preventDefault();
            var pw = document.getElementById('password').value;
            if (pw.length < 6) {
                showError('La contraseña debe tener al menos 6 caracteres.');
                document.getElementById('password').focus();
                return;
            }
            setLoading(true);
            try {
                var resp = await axios.post('/api/v1/estudiantes/registro', {
                    nombre: document.getElementById('nombre').value,
                    apellido: document.getElementById('apellido').value,
                    identidad: document.getElementById('identidad').value,
                    correo: document.getElementById('correo').value,
                    telefono: document.getElementById('telefono').value || null,
                    sucursal_id: document.getElementById('sucursal_id').value,
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('password_confirmation').value
                });
                if (resp.data.resultado === 'A') {
                    showSuccess('Registro exitoso. Su código de estudiante es: ' + resp.data.data.codigo + '. Ahora puede iniciar sesión.');
                    document.getElementById('regForm').reset();
                } else { showError(resp.data.mensaje || 'Error al registrar'); }
            } catch (err) { showError(window.extractError(err, 'Error al conectar')); }
            finally { setLoading(false); }
        }
    </script>
</body>
</html>
