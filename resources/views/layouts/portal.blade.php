<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal del Estudiante') — Cursos San Vicente de Paúl</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700;800" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#ecfdf5', 100:'#d1fae5', 200:'#a7f3d0', 300:'#6ee7b7', 400:'#34d399', 500:'#10b981', 600:'#059669', 700:'#047857', 800:'#065f46', 900:'#064e3b' },
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 min-h-screen" x-data="portalApp()" x-init="init()">
    {{-- Top Nav --}}
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <div class="flex items-center gap-3">
                    <a href="/estudiante" class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 hidden sm:block">San Vicente de Paúl</span>
                    </a>
                    <span class="text-xs font-medium text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full hidden sm:block">Portal del Estudiante</span>
                </div>
                <template x-if="sessionCountdown">
                    <span class="hidden sm:inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200">
                        Expira en <span class="ml-1 font-mono" x-text="sessionCountdown"></span>
                    </span>
                </template>
                <template x-if="estudiante">
                    <div class="flex items-center gap-3">
                        <nav class="hidden md:flex items-center gap-2 mr-2">
                            <a href="/estudiante" :class="currentSection === 'inicio' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Mi Nivel</a>
                            <a href="/estudiante/historial" :class="currentSection === 'historial' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Historial</a>
                            <a href="/estudiante/certificados" :class="currentSection === 'certificados' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Certificados</a>
                            <a href="/estudiante/matricula" :class="currentSection === 'matricula' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Matrícula</a>
                            <a href="/estudiante/pagos" :class="currentSection === 'pagos' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Obligaciones</a>
                            <a href="/estudiante/recibos" :class="currentSection === 'recibos' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Recibos</a>
                        </nav>
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-medium text-gray-900" x-text="estudiante.nombre"></p>
                            <p class="text-xs text-gray-500" x-text="estudiante.codigo"></p>
                        </div>
                        <button @click="cerrarSesion()" class="text-gray-400 hover:text-gray-600 p-1" title="Cerrar sesión">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </nav>

    {{-- Mobile Nav --}}
    <template x-if="estudiante">
        <nav class="sm:hidden bg-white border-b border-gray-200 px-4 py-2 flex gap-2 overflow-x-auto">
            <a href="/estudiante" :class="currentSection === 'inicio' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Mi Nivel</a>
            <a href="/estudiante/historial" :class="currentSection === 'historial' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Historial</a>
            <a href="/estudiante/certificados" :class="currentSection === 'certificados' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Certificados</a>
            <a href="/estudiante/matricula" :class="currentSection === 'matricula' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Matrícula</a>
            <a href="/estudiante/pagos" :class="currentSection === 'pagos' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Obligaciones</a>
            <a href="/estudiante/recibos" :class="currentSection === 'recibos' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors">Recibos</a>
        </nav>
    </template>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (!empty($currentSection) && $currentSection !== 'inicio')
        <div class="mb-4">
            <a href="/estudiante" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Volver al inicio
            </a>
        </div>
        @endif
        @yield('content')
    </main>

    {{-- Toast --}}
    <div x-show="toast.show" x-on:show-toast.window="showToast($event.detail.message, $event.detail.type)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2" class="fixed bottom-4 right-4 z-50" x-cloak>
        <div :class="toast.type === 'error' ? 'bg-red-600' : 'bg-brand-600'" class="text-white px-4 py-3 rounded-lg shadow-lg text-sm flex items-center gap-2">
            <span x-text="toast.message"></span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios@1/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
    <script>
        window.APP_TIMEZONE = @json(config('app.timezone'));
        window.formatDateLocal = function(input, options = {}) {
            if (!input) return '-';
            const d = new Date(input);
            if (isNaN(d.getTime())) return '-';
            const hasTime = Object.prototype.hasOwnProperty.call(options, 'hour') || Object.prototype.hasOwnProperty.call(options, 'minute') || Object.prototype.hasOwnProperty.call(options, 'second');
            if (!hasTime) {
                const parts = new Intl.DateTimeFormat('es-HN', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', day: '2-digit', month: '2-digit', year: 'numeric' }).formatToParts(d);
                const day = parts.find(p => p.type === 'day')?.value || '00';
                const month = parts.find(p => p.type === 'month')?.value || '00';
                const year = parts.find(p => p.type === 'year')?.value || '0000';
                return `${day}-${month}-${year}`;
            }
            return new Intl.DateTimeFormat('es-HN', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', ...options }).format(d);
        };
        window.formatDateTimeLocal = function(input) {
            return window.formatDateLocal(input, { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        };
        window.toLocalDateInput = function(input = new Date()) {
            const d = new Date(input);
            if (isNaN(d.getTime())) return '';
            const parts = new Intl.DateTimeFormat('en-CA', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(d);
            const year = parts.find(p => p.type === 'year')?.value || '';
            const month = parts.find(p => p.type === 'month')?.value || '';
            const day = parts.find(p => p.type === 'day')?.value || '';
            return `${year}-${month}-${day}`;
        };

        window.extractError = function(err, fallback) {
            const body = err?.response?.data;
            return body?.mensaje || body?.mensaje_usuario || body?.message || body?.error || 
                (body?.errores ? Object.values(body.errores).flat().join(', ') : null) || 
                fallback || 'Error inesperado';
        };
        window.extractErrorCode = function(err) {
            return err?.response?.data?.codigo_error || null;
        };

        function portalApp() {
            return {
                estudiante: null,
                currentSection: @js($currentSection ?? 'inicio'),
                toast: { show: false, message: '', type: 'success' },
                sessionExpiresAt: localStorage.getItem('estudiante_token_expires_at') || sessionStorage.getItem('estudiante_token_expires_at') || new Date(Date.now() + 5 * 60 * 1000).toISOString(),
                sessionCountdown: '',
                countdownTimer: null,

                async init() {
                    const token = localStorage.getItem('estudiante_token') || sessionStorage.getItem('estudiante_token');
                    const path = window.location.pathname;
                    const esPublica = path.includes('/estudiante/login') || path.includes('/estudiante/registro') || path.includes('/estudiante/activar') || path.startsWith('/certificados/');
                    if (!token && !esPublica) {
                        window.location.href = '/estudiante/login';
                        return;
                    }
                    if (token) {
                        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                        try {
                            const { data } = await window.axios.post('/api/v1/estudiantes/portal', {}, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                            if (data.resultado === 'A' && data.data?.estudiante) {
                                this.estudiante = data.data.estudiante;
                                localStorage.setItem('estudiante_data', JSON.stringify(data.data.estudiante));
                                this.startSessionCountdown();
                            }
                        } catch (e) {
                            if (e?.response?.status === 401) {
                                window.handleAuthExpired('estudiante');
                            }
                        }
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 3000);
                },

                async cerrarSesion() {
                    const token = localStorage.getItem('estudiante_token') || sessionStorage.getItem('estudiante_token');
                    if (token) {
                        try {
                            await window.axios.post('/api/v1/estudiantes/cerrar-sesion', {}, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                        } catch(e) {}
                    }
                    localStorage.removeItem('estudiante_token');
                    sessionStorage.removeItem('estudiante_token');
                    localStorage.removeItem('estudiante_data');
                    localStorage.removeItem('estudiante_token_expires_at');
                    sessionStorage.removeItem('estudiante_data');
                    sessionStorage.removeItem('estudiante_token_expires_at');
                    delete window.axios.defaults.headers.common['Authorization'];
                    window.location.href = '/estudiante/login';
                },

                startSessionCountdown() {
                    if (this.countdownTimer) clearInterval(this.countdownTimer);
                    const update = () => {
                        const expires = new Date(this.sessionExpiresAt || Date.now());
                        const diff = expires.getTime() - Date.now();
                        if (Number.isNaN(expires.getTime()) || diff <= 0) {
                            window.handleAuthExpired('estudiante');
                            return;
                        }
                        const mins = Math.floor(diff / 60000);
                        const secs = Math.floor((diff % 60000) / 1000);
                        this.sessionCountdown = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                    };
                    update();
                    this.countdownTimer = setInterval(update, 1000);
                },

                api() {
                    const token = localStorage.getItem('estudiante_token') || sessionStorage.getItem('estudiante_token');
                    return {
                        headers: { Authorization: `Bearer ${token}` }
                    };
                }
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
