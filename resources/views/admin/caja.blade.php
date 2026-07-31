@extends('layouts.admin')
@section('content')
<div x-data="caja()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Caja</h1>
            <p class="page-subtitle">Sesiones de caja y cierres diarios</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadData()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                Actualizar
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1 overflow-x-auto">
            <button @click="tab = 'sesiones'" :class="tab === 'sesiones' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Sesiones de Caja</button>
            <button @click="tab = 'cierres'" :class="tab === 'cierres' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Cierres de Caja</button>
        </nav>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500/20 border-t-blue-500"></div>
        </div>
    </template>

    <template x-if="!loading">
        <div>
            {{-- Sesiones de Caja --}}
            <div x-show="tab === 'sesiones'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('caja.sesiones.crear')" @click="openAbrirModal()" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Abrir Sesión
                    </button>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Sucursal</th>
                                        <th>Cajero</th>
                                        <th class="text-right">Monto Inicial</th>
                                        <th>Estado</th>
                                        <th>Apertura</th>
                                        <th>Cierre</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="s in sesiones" :key="s.id">
                                        <tr>
                                            <td class="font-mono text-xs font-medium" x-text="s.codigo"></td>
                                            <td x-text="s.sucursal?.nombre || '-'"></td>
                                            <td x-text="s.cajero?.name || '-'"></td>
                                            <td class="text-right font-mono" x-text="'L ' + formatMoney(s.monto_inicial)"></td>
                                            <td>
                                                <span :class="s.estado === 'abierta' ? 'badge-success' : 'badge-neutral'" class="badge" x-text="s.estado === 'abierta' ? 'Abierta' : 'Cerrada'"></span>
                                            </td>
                                            <td class="text-xs text-gray-500" x-text="formatDate(s.fecha_apertura)"></td>
                                            <td class="text-xs text-gray-500" x-text="s.fecha_cierre ? formatDate(s.fecha_cierre) : '-'"></td>
                                            <td class="text-right">
                                                <button x-show="api.hasPermission('caja.sesiones.modificar') && s.estado === 'abierta'" @click="openCerrarModal(s)" class="btn btn-ghost btn-sm text-red-600">Cerrar</button>
                                                <button x-show="api.hasPermission('caja.sesiones.consultar')" @click="verDetalle(s)" class="btn btn-ghost btn-sm">Ver</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="sesiones.length === 0">
                                        <tr><td colspan="8" class="text-center py-10 text-gray-400">No hay sesiones de caja registradas</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cierres de Caja --}}
            <div x-show="tab === 'cierres'" class="space-y-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-gray-900">Filtros de Consulta</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Fecha Desde</label>
                                <input x-model="filtroCierres.fecha_desde" type="date" class="input">
                            </div>
                            <div>
                                <label class="label">Fecha Hasta</label>
                                <input x-model="filtroCierres.fecha_hasta" type="date" class="input">
                            </div>
                            <div class="flex items-end">
                                <button @click="buscarCierres()" class="btn btn-primary w-full">Buscar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Sucursal</th>
                                        <th>Cajero</th>
                                        <th class="text-right">Monto Inicial</th>
                                        <th class="text-right">Monto Final</th>
                                        <th class="text-right">Total Ingresos</th>
                                        <th>Cierre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="c in cierres" :key="c.id">
                                        <tr>
                                            <td class="font-mono text-xs font-medium" x-text="c.codigo"></td>
                                            <td x-text="c.sucursal?.nombre || '-'"></td>
                                            <td x-text="c.cajero?.name || '-'"></td>
                                            <td class="text-right font-mono" x-text="'L ' + formatMoney(c.monto_inicial)"></td>
                                            <td class="text-right font-mono" x-text="c.monto_final !== null ? 'L ' + formatMoney(c.monto_final) : '-'"></td>
                                            <td class="text-right font-mono font-semibold text-emerald-600" x-text="formatMoneyValue(c.total_ingresos ?? calcTotalIngresosRaw(c))"></td>
                                            <td class="text-xs text-gray-500" x-text="formatDate(c.fecha_cierre)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="cierres.length === 0">
                                        <tr><td colspan="7" class="text-center py-10 text-gray-400">No hay cierres en el rango seleccionado</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Abrir Sesión Modal --}}
    <div x-show="showAbrirModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAbrirModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Abrir Sesión de Caja</h3>
                <button @click="showAbrirModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="abrirSesion()" class="p-6 space-y-4">
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="abrirForm.sucursal_id" required class="input">
                        <option value="">Seleccionar sucursal</option>
                        <template x-for="s in sucursales" :key="s.id">
                            <option :value="s.id" x-text="s.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Monto Inicial (L)</label>
                    <input x-model.number="abrirForm.monto_inicial" type="number" step="0.01" min="0" required class="input" placeholder="0.00">
                </div>
                <div>
                    <label class="label">Observaciones</label>
                    <textarea x-model="abrirForm.observaciones" class="input" rows="2" placeholder="Opcional"></textarea>
                </div>
                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAbrirModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary">
                        <span x-text="saving ? 'Abriendo...' : 'Abrir Sesión'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Cerrar Sesión Modal --}}
    <div x-show="showCerrarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCerrarModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Cerrar Sesión de Caja</h3>
                <button @click="showCerrarModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="cerrarSesion()" class="p-6 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-blue-700">Sesión: <strong x-text="cerrarSesionData?.codigo"></strong></p>
                    <p class="text-xs text-blue-500 mt-1">Monto inicial: <span x-text="'L ' + formatMoney(cerrarSesionData?.monto_inicial)"></span></p>
                </div>
                <div>
                    <label class="label">Monto Final en Efectivo (L)</label>
                    <input x-model.number="cerrarForm.monto_final" type="number" step="0.01" min="0" required class="input" placeholder="0.00">
                </div>
                <div>
                    <label class="label">Observaciones</label>
                    <textarea x-model="cerrarForm.observaciones" class="input" rows="2" placeholder="Opcional"></textarea>
                </div>
                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showCerrarModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-danger">
                        <span x-text="saving ? 'Cerrando...' : 'Cerrar Sesión'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Detalle Sesión Modal --}}
    <div x-show="showDetalleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showDetalleModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Detalle de Sesión</h3>
                <button @click="showDetalleModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4" x-show="detalleSesion">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Código:</span> <span class="font-mono font-medium" x-text="detalleSesion?.codigo"></span></div>
                    <div><span class="text-gray-500">Estado:</span> <span :class="detalleSesion?.estado === 'abierta' ? 'badge-success' : 'badge-neutral'" class="badge" x-text="detalleSesion?.estado === 'abierta' ? 'Abierta' : 'Cerrada'"></span></div>
                    <div><span class="text-gray-500">Sucursal:</span> <span x-text="detalleSesion?.sucursal?.nombre"></span></div>
                    <div><span class="text-gray-500">Cajero:</span> <span x-text="detalleSesion?.cajero?.name"></span></div>
                    <div><span class="text-gray-500">Monto Inicial:</span> <span class="font-mono" x-text="'L ' + formatMoney(detalleSesion?.monto_inicial)"></span></div>
                    <div><span class="text-gray-500">Monto Final:</span> <span class="font-mono" x-text="detalleSesion?.monto_final !== null ? 'L ' + formatMoney(detalleSesion?.monto_final) : '-'"></span></div>
                    <div><span class="text-gray-500">Apertura:</span> <span x-text="formatDate(detalleSesion?.fecha_apertura)"></span></div>
                    <div><span class="text-gray-500">Cierre:</span> <span x-text="detalleSesion?.fecha_cierre ? formatDate(detalleSesion?.fecha_cierre) : '-'"></span></div>
                </div>
                <template x-if="detalleSesion?.detalles?.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Resumen por Concepto y Método de Pago</h4>
                        <table class="table text-xs">
                            <thead><tr><th>Concepto</th><th>Método</th><th class="text-center">Cant.</th><th class="text-right">Total</th></tr></thead>
                            <tbody>
                                <template x-for="d in detalleSesion.detalles" :key="d.id || Math.random()">
                                    <tr>
                                        <td x-text="d.concepto_pago?.nombre || d.concepto_pago_id"></td>
                                        <td x-text="d.metodo_pago?.nombre || d.metodo_pago_id"></td>
                                        <td class="text-center" x-text="d.cantidad_transacciones"></td>
                                        <td class="text-right font-mono" x-text="'L ' + formatMoney(d.monto_total)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function caja() {
    return {
        loading: true, saving: false, tab: 'sesiones', error: '',
        sesiones: [], cierres: [], sucursales: [],
        showAbrirModal: false, showCerrarModal: false, showDetalleModal: false,
        abrirForm: { sucursal_id: '', monto_inicial: 0, observaciones: '' },
        cerrarForm: { monto_final: 0, observaciones: '' },
        cerrarSesionData: null, detalleSesion: null,
        filtroCierres: { fecha_desde: window.toLocalDateInput(), fecha_hasta: window.toLocalDateInput() },

        async init() {
            await this.loadData();
            await this.buscarCierres();
        },

        async loadData() {
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                const h = { headers: { Authorization: `Bearer ${token}` } };
                const [sesRes, sucRes] = await Promise.allSettled([
                    window.axios.get('/api/v1/caja/sesiones?per_page=50', h),
                    window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                ]);
                if (sesRes.status === 'fulfilled' && sesRes.value.data.resultado === 'A') {
                    this.sesiones = sesRes.value.data.data.data || sesRes.value.data.data || [];
                }
                if (sucRes.status === 'fulfilled' && sucRes.value.data.resultado === 'A') {
                    this.sucursales = sucRes.value.data.data.data || sucRes.value.data.data || [];
                }
                await this.buscarCierres();
            } catch(e) {} finally { this.loading = false; }
        },

        async buscarCierres() {
            const token = localStorage.getItem('auth_token');
            try {
                const { data } = await window.axios.get('/api/v1/cierre-caja', {
                    headers: { Authorization: `Bearer ${token}` },
                    params: { ...this.filtroCierres }
                });
                if (data.resultado === 'A') {
                    this.cierres = data.data.data || data.data || [];
                }
            } catch(e) { this.cierres = []; }
        },

        openAbrirModal() {
            this.error = '';
            this.abrirForm = { sucursal_id: '', monto_inicial: 0, observaciones: '' };
            this.showAbrirModal = true;
        },

        async abrirSesion() {
            this.saving = true; this.error = '';
            try {
                const { data } = await window.axios.post('/api/v1/caja/abrir', this.abrirForm, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                if (data.resultado === 'A') {
                    this.showAbrirModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Sesión de caja abierta', type: 'success' } }));
                    await this.loadData();
                } else {
                    this.error = data.mensaje || 'Error al abrir sesión';
                }
            } catch(e) { this.error = window.extractError(e, 'Error al abrir sesión'); } finally { this.saving = false; }
        },

        openCerrarModal(sesion) {
            this.error = '';
            this.cerrarSesionData = sesion;
            this.cerrarForm = { monto_final: 0, observaciones: '' };
            this.showCerrarModal = true;
        },

        async cerrarSesion() {
            this.saving = true; this.error = '';
            try {
                const { data } = await window.axios.post(`/api/v1/caja/${this.cerrarSesionData.id}/cerrar`, this.cerrarForm, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                if (data.resultado === 'A') {
                    this.showCerrarModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Sesión cerrada con éxito', type: 'success' } }));
                    await this.loadData();
                } else {
                    this.error = data.mensaje || 'Error al cerrar sesión';
                }
            } catch(e) { this.error = window.extractError(e, 'Error al cerrar sesión'); } finally { this.saving = false; }
        },

        async verDetalle(sesion) {
            const token = localStorage.getItem('auth_token');
            try {
                const { data } = await window.axios.get(`/api/v1/caja/${sesion.id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.detalleSesion = data.data;
                    this.showDetalleModal = true;
                }
            } catch(e) {}
        },

        calcTotalIngresosRaw(cierre) {
            if (cierre.detalles && cierre.detalles.length > 0) {
                return cierre.detalles.reduce((sum, d) => sum + parseFloat(d.monto_total || 0), 0);
            }
            if (cierre.pagos && cierre.pagos.length > 0) {
                return cierre.pagos.reduce((sum, p) => sum + parseFloat(p.monto || 0), 0);
            }
            return 0;
        },

        formatMoneyValue(val) {
            if (val === null || val === undefined || val === '') return '-';
            return 'L ' + this.formatMoney(val);
        },

        formatMoney(val) {
            return new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
        },

        formatDate(val) {
            if (!val) return '-';
            try {
                return window.formatDateLocal(val, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            } catch(e) { return val; }
        }
    }
}
</script>
@endsection
