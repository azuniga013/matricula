@extends('layouts.admin')
@section('title', 'Proveedores de Pago')
@section('content')
<div x-data="proveedoresView()" x-init="init()" x-cloak>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Proveedores de Pago</h2>
            <p class="text-sm text-gray-500">Administración de procesadores de pago en línea</p>
        </div>
    </div>
    <div x-show="error" x-text="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
    </template>

    <template x-if="!loading">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <template x-for="p in proveedores" :key="p.id">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center">
                            <span class="text-lg font-bold text-brand-600" x-text="p.codigo?.charAt(0)"></span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900" x-text="p.nombre"></h3>
                            <p class="text-xs text-gray-400" x-text="p.codigo"></p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="p.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            x-text="p.activo ? 'Activo' : 'Inactivo'"></span>
                    </div>
                    <div class="p-5 space-y-3">
                        <template x-for="cfg in p.configuraciones" :key="cfg.id">
                            <div class="flex items-center justify-between py-1.5">
                                <span class="text-sm text-gray-600 font-medium" x-text="cfg.clave"></span>
                                <span class="text-sm text-gray-800" x-text="cfg.valor_enmascarado || 'No configurado'"></span>
                            </div>
                        </template>
                        <template x-if="(!p.configuraciones || p.configuraciones.length === 0)">
                            <p class="text-sm text-gray-400 text-center py-2">Sin configuraciones</p>
                        </template>
                        <button @click="editarConfig(p)" class="w-full mt-2 px-3 py-2 text-sm font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                            Configurar
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Modal: Configurar Proveedor --}}
    <template x-if="selectedProveedor">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="selectedProveedor = null"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="'Configurar: ' + selectedProveedor.nombre"></h3>
                    <button @click="selectedProveedor = null" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="space-y-4">
                    <template x-for="(cfg, idx) in configForm" :key="idx">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" x-text="cfg.clave"></label>
                            <input x-model="cfg.valor" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :placeholder="cfg.clave">
                        </div>
                    </template>
                </div>
                <template x-if="modalError"><p class="text-sm text-red-600 mt-4" x-text="modalError"></p></template>
                <div class="flex gap-3 mt-6">
                    <button @click="selectedProveedor = null" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                    <button @click="guardarConfig()" :disabled="guardando" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                        <span x-show="!guardando">Guardar</span><span x-show="guardando">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
@section('scripts')
<script>
function proveedoresView() {
    return {
        loading: true, proveedores: [], selectedProveedor: null, configForm: [], guardando: false, modalError: '', error: '',
        token() { return localStorage.getItem('auth-token') || localStorage.getItem('auth_token'); },
        async init() {
            this.error = '';
            const t = this.token();
            if (!t) { window.location.href = '/login'; return; }
            try {
                const { data } = await window.axios.get('/api/v1/proveedores-pago', { headers: { Authorization: `Bearer ${t}` } });
                if (data.resultado === 'A') this.proveedores = data.data;
        } catch(e) {
            if (e.response?.status === 403) this.error = window.extractError(e, 'No tiene permiso para consultar proveedores de pago');
            else this.error = window.extractError(e, 'No se pudieron cargar los proveedores de pago');
        }
            finally { this.loading = false; }
        },
        editarConfig(p) {
            this.selectedProveedor = p;
            this.configForm = (p.configuraciones || []).map(c => ({
                id: c.id, clave: c.clave, valor: '',
            }));
            this.modalError = '';
        },
        async guardarConfig() {
            this.guardando = true; this.modalError = '';
            const t = this.token();
            try {
                const { data } = await window.axios.post(`/api/v1/proveedores-pago/${this.selectedProveedor.id}/configuracion`,
                    { config: Object.fromEntries(this.configForm.map(c => [c.clave, c.valor])) },
                    { headers: { Authorization: `Bearer ${t}` } });
                if (data.resultado === 'A') {
                    this.selectedProveedor = null;
                    this.init();
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Configuración guardada exitosamente', type: 'success' }
                    }));
                } else {
                    this.modalError = data.mensaje;
                }
            } catch(e) {
                this.modalError = window.extractError(e, 'Error al guardar configuración');
            } finally { this.guardando = false; }
        },
    };
}
</script>
@endsection
