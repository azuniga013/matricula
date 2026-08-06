import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.handleAuthExpired = function(context = 'admin') {
    if (context === 'estudiante') {
        localStorage.removeItem('estudiante_token');
        localStorage.removeItem('estudiante_data');
        sessionStorage.removeItem('estudiante_token');
        sessionStorage.removeItem('estudiante_data');
        window.location.href = '/estudiante/login?sesion=expirada';
        return;
    }

    localStorage.removeItem('auth-token');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_token_expires_at');
    sessionStorage.removeItem('auth-token');
    delete window.axios.defaults.headers.common['Authorization'];
    window.location.href = '/login?sesion=expirada';
};

window.axios.interceptors.response.use(
    response => response,
    error => {
        const status = error?.response?.status;
        const path = window.location.pathname || '';
        if (status === 401 || status === 423) {
            if (path.startsWith('/estudiante')) {
                window.handleAuthExpired('estudiante');
            } else if (path.startsWith('/admin') || path === '/' || path.startsWith('/certificados/')) {
                window.handleAuthExpired('admin');
            }
        }
        return Promise.reject(error);
    }
);

// IIS/SmarterASP puede bloquear PUT y PATCH antes de que Laravel procese la
// solicitud. Las actualizaciones internas siempre se envían como POST.
// Esta salvaguarda protege pantallas nuevas que accidentalmente usen esos verbos.
window.axios.interceptors.request.use(config => {
    const method = (config.method || 'get').toLowerCase();
    const url = String(config.url || '');

    if ((method === 'put' || method === 'patch') && /(?:^|\/)api\/v1\//.test(url)) {
        config.method = 'post';
    }

    return config;
});

const api = {
    token: localStorage.getItem('auth-token'),
    user: null,
    permisos: [],

    setToken(token) {
        this.token = token;
        localStorage.setItem('auth-token', token);
        localStorage.setItem('auth_token', token);
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    },

    clearToken() {
        this.token = null;
        this.user = null;
        this.permisos = [];
        localStorage.removeItem('auth-token');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_token_expires_at');
        delete window.axios.defaults.headers.common['Authorization'];
    },

    async login(email, password) {
        const { data } = await window.axios.post('/api/v1/login', { email, password });
        if (data.resultado === 'A') {
            this.setToken(data.data.token);
            if (data.data.expires_at) {
                localStorage.setItem('auth_token_expires_at', data.data.expires_at);
            }
            this.user = data.data.usuario;
            this.permisos = data.data.usuario?.permisos || [];
            return data.data;
        }
        throw new Error(data.mensaje || 'Error de autenticación');
    },

    async logout() {
        try {
            await window.axios.post('/api/v1/logout');
        } catch (e) { /* ignore */ }
        this.clearToken();
    },

    /** Actualización compatible con IIS/SmarterASP; no usar PUT ni PATCH en vistas Blade. */
    actualizar(url, payload = {}, config = {}) {
        return window.axios.post(url, payload, config);
    },

    async fetchUser() {
        const { data } = await window.axios.get('/api/v1/me');
        if (data.resultado === 'A') {
            this.user = data.data;
            this.permisos = data.data.permisos || [];
            return data.data;
        }
        throw new Error('No autenticado');
    },

    hasPermission(codigo) {
        return this.permisos.includes(codigo);
    },

    hasAnyPermission(codigos) {
        return codigos.some(c => this.permisos.includes(c));
    },

    hasAllPermissions(codigos) {
        return codigos.every(c => this.permisos.includes(c));
    },

    hasModuloPermiso(modulo, accion) {
        return this.permisos.includes(`${modulo}.${accion}`);
    },

    hasAnyPermisoModulo(modulo) {
        return this.permisos.some(p => p.startsWith(modulo + '.'));
    },

    permisosPorModulo() {
        const mapa = {};
        this.permisos.forEach(p => {
            const partes = p.split('.');
            const mod = partes[0];
            if (!mapa[mod]) mapa[mod] = [];
            mapa[mod].push(p);
        });
        return mapa;
    },

    init() {
        if (this.token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            localStorage.setItem('auth_token', this.token);
        }
    }
};

api.init();

window.api = api;
window.dispatchEvent(new Event('api-ready'));
Alpine.start();
