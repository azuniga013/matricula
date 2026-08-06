@extends('layouts.admin')
@section('content')
<div x-data="planesCobro()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Planes de Cobro</h1>
            <p class="page-subtitle">Configuración de planes de cobro por modalidad</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadPlanes()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            </button>
            <button x-show="api.hasPermission('catalogos.planes-cobro.crear')" @click="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo Plan de Cobro
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Buscar</label>
                    <input x-model="buscar" @input.debounce="loadPlanes()" type="text" class="input" placeholder="Código o nombre...">
                </div>
                <div>
                    <label class="label">Estado</label>
                    <select x-model="filtroEstado" @change="loadPlanes()" class="input">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Tabla --}}
    <template x-if="!loading && planes.length === 0">
        <div class="card"><div class="card-body text-center text-gray-400 py-12"><p>No hay planes de cobro registrados</p></div></div>
    </template>

    <template x-if="!loading && planes.length > 0">
        <div class="space-y-4">
            <template x-for="plan in planes" :key="plan.id">
                <div class="card">
                    <div class="card-body border-b flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold" x-text="plan.codigo + ' · ' + plan.nombre"></h3>
                            <p class="text-sm text-gray-500" x-text="plan.descripcion || 'Sin descripción'"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="plan.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="plan.estado"></span>
                            <button x-show="api.hasPermission('catalogos.planes-cobro.modificar')" @click="editPlan(plan)" class="btn btn-ghost btn-sm">Editar</button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>#</th><th>Concepto</th><th>Cargo</th><th class="text-right">Monto</th><th>Días Venc.</th></tr></thead>
                            <tbody>
                                <template x-for="d in (plan.detalles || [])" :key="d.id">
                                    <tr>
                                        <td class="font-mono text-xs" x-text="d.numero_cuota === 0 ? 'MAT' : 'CUO ' + d.numero_cuota"></td>
                                        <td x-text="d.concepto_pago?.codigo + ' · ' + (d.concepto_pago?.nombre || '')"></td>
                                        <td class="font-medium" x-text="d.nombre_cargo"></td>
                                        <td class="text-right font-semibold" x-text="'L ' + Number(d.monto).toFixed(2)"></td>
                                        <td x-text="d.dias_vencimiento ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold bg-gray-50">
                                    <td colspan="3">Total</td>
                                    <td class="text-right" x-text="'L ' + (plan.detalles || []).reduce((s, d) => s + Number(d.monto), 0).toFixed(2)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Editar Plan de Cobro' : 'Nuevo Plan de Cobro'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form @submit.prevent="savePlan()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="label">Código</label>
                        <input x-model="form.codigo" type="text" required class="input" :disabled="editing" placeholder="PLN-INT-2026">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Nombre</label>
                        <input x-model="form.nombre" type="text" required class="input" placeholder="Intensivo 2026">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Descripción</label>
                        <textarea x-model="form.descripcion" class="input" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700">Detalles del Plan</h4>
                        <button type="button" @click="agregarDetalle()" class="btn btn-outline btn-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Agregar Cuota
                        </button>
                    </div>
                    <template x-for="(d, idx) in form.detalles" :key="idx">
                        <div class="grid grid-cols-5 gap-2 items-end mb-2 p-3 bg-gray-50 rounded-lg">
                            <div>
                                <label class="label text-xs">Concepto</label>
                                <select x-model="d.concepto_pago_id" required class="input text-xs">
                                    <option value="">Seleccionar...</option>
                                    <template x-for="c in conceptosPago" :key="c.id">
                                        <option :value="c.id" x-text="c.codigo + ' · ' + c.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="label text-xs"># Cuota</label>
                                <input x-model.number="d.numero_cuota" type="number" min="0" required class="input text-xs">
                            </div>
                            <div>
                                <label class="label text-xs">Cargo</label>
                                <input x-model="d.nombre_cargo" type="text" required class="input text-xs" placeholder="Matrícula">
                            </div>
                            <div>
                                <label class="label text-xs">Monto (L)</label>
                                <input x-model.number="d.monto" type="number" step="0.01" min="0" required class="input text-xs">
                            </div>
                            <div class="flex items-end gap-1">
                                <div class="flex-1">
                                    <label class="label text-xs">Días Venc.</label>
                                    <input x-model.number="d.dias_vencimiento" type="number" min="0" class="input text-xs">
                                </div>
                                <button type="button" @click="form.detalles.splice(idx, 1)" class="btn btn-ghost btn-sm text-red-500 p-1">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary">
                        <template x-if="saving"><div class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white"></div></template>
                        <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function planesCobro() {
    return {
        loading: true, showModal: false, editing: false, saving: false, error: '',
        planes: [], conceptosPago: [], buscar: '', filtroEstado: '',
        form: { codigo: '', nombre: '', descripcion: '', detalles: [] }, editId: null,

        async init() {
            await this.loadConceptos();
            await this.loadPlanes();
        },

        async loadConceptos() {
            try {
                const { data } = await window.axios.get('/api/v1/catalogos-academicos/conceptos-pago', {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                if (data.resultado === 'A') {
                    this.conceptosPago = data.data.data || data.data || [];
                }
            } catch(e) { console.error(e); }
        },

        async loadPlanes() {
            this.loading = true;
            try {
                let url = '/api/v1/catalogos-academicos/planes-cobro?';
                if (this.buscar) url += `buscar=${encodeURIComponent(this.buscar)}&`;
                if (this.filtroEstado) url += `estado=${this.filtroEstado}&`;
                const { data } = await window.axios.get(url, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                if (data.resultado === 'A') {
                    this.planes = data.data.data || data.data || [];
                }
            } catch(e) { console.error(e); }
            finally { this.loading = false; }
        },

        openModal() {
            this.editing = false; this.editId = null; this.error = '';
            this.form = { codigo: '', nombre: '', descripcion: '', detalles: [] };
            this.agregarDetalle();
            this.showModal = true;
        },

        agregarDetalle() {
            this.form.detalles.push({ concepto_pago_id: '', numero_cuota: 0, nombre_cargo: '', monto: 0, dias_vencimiento: 0 });
        },

        editPlan(plan) {
            this.editing = true; this.editId = plan.id; this.error = '';
            this.form = {
                codigo: plan.codigo,
                nombre: plan.nombre,
                descripcion: plan.descripcion || '',
                detalles: (plan.detalles || []).map(d => ({
                    id: d.id,
                    concepto_pago_id: d.concepto_pago_id,
                    numero_cuota: d.numero_cuota,
                    nombre_cargo: d.nombre_cargo,
                    monto: d.monto,
                    dias_vencimiento: d.dias_vencimiento ?? 0,
                })),
            };
            if (this.form.detalles.length === 0) this.agregarDetalle();
            this.showModal = true;
        },

        async savePlan() {
            this.saving = true; this.error = '';
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editing
                    ? `/api/v1/catalogos-academicos/planes-cobro/${this.editId}`
                    : '/api/v1/catalogos-academicos/planes-cobro';
                const payload = {
                    codigo: this.form.codigo,
                    nombre: this.form.nombre,
                    descripcion: this.form.descripcion || null,
                    detalles: this.form.detalles.map(d => ({
                        id: d.id || null,
                        concepto_pago_id: d.concepto_pago_id,
                        numero_cuota: d.numero_cuota,
                        nombre_cargo: d.nombre_cargo,
                        monto: d.monto,
                        dias_vencimiento: d.dias_vencimiento || 0,
                    })),
                };

                const { data } = await window.api.actualizar(url, payload, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                if (data.resultado === 'A') {
                    this.showModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Plan de cobro guardado', type: 'success' } }));
                    await this.loadPlanes();
                } else { this.error = data.mensaje || 'Error'; }
            } catch(e) { this.error = window.extractError(e, 'Error al guardar'); }
            finally { this.saving = false; }
        },
    }
}
</script>
@endsection
