@extends('layouts.portal')
@section('title', 'Pagos y Comprobantes')
@section('content')
<div x-data="pagoEstudiante()" x-init="loadData()" x-cloak>
    {{-- Wizard Steps --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Pagos y Comprobantes</h2>
        <p class="text-sm text-gray-500">Realice sus pagos y suba sus comprobantes</p>
    </div>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm font-medium">
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full"
                :class="paso === 'seleccionar' ? 'bg-brand-600 text-white' : 'bg-brand-100 text-brand-700'">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs"
                    :class="paso === 'seleccionar' ? 'bg-white/20' : 'bg-brand-600/20'">1</span>
                Seleccionar obligaciones
            </span>
            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3m0 0 3-3m-3 3v-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full"
                :class="paso === 'pagar' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-400'">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs"
                    :class="paso === 'pagar' ? 'bg-white/20' : 'bg-gray-200'">2</span>
                <span x-text="flujoPortal?.habilita_carga_comprobante ? 'Pagar y subir comprobante' : 'Pagar' "></span>
            </span>
        </div>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div class="space-y-6">

            {{-- Step 1: Matrículas con obligaciones pendientes --}}
            <template x-if="paso === 'seleccionar'">
                <div>
                    <template x-if="matriculasPendientes.length === 0">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                            <p class="text-gray-400">No tiene matrículas con pagos pendientes.</p>
                            <a href="/estudiante/matricula" class="mt-4 inline-block text-sm text-brand-600 hover:text-brand-700">Matricularme ahora →</a>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <template x-for="m in matriculasPendientes" :key="m.id">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p class="font-semibold text-gray-900" x-text="m.nivel || 'Matrícula'"></p>
                                        <p class="text-xs text-gray-400" x-text="m.codigo"></p>
                                    </div>
                                    <span :class="{'badge badge-amber': m.estado === 'reservada', 'badge badge-green': m.estado === 'matriculado'}" class="text-xs" x-text="m.estado"></span>
                                </div>
                                <div class="text-sm text-gray-600 mb-3" x-text="m.horario ? m.horario + ' · ' + m.regimen : m.regimen"></div>

                                {{-- Acciones de seleccion --}}
                                <div class="flex items-center gap-3 mb-3 text-xs">
                                    <button @click="seleccionarTodas(m)" class="text-brand-600 font-medium hover:text-brand-700">Seleccionar todo</button>
                                    <button @click="deseleccionarTodas(m)" class="text-gray-500 font-medium hover:text-gray-700">Ninguno</button>
                                    <template x-if="totalSeleccionado(m) > 0">
                                        <span class="ml-auto font-semibold text-brand-700" x-text="'Subtotal: ' + fmtMonto(totalSeleccionado(m)) + ' L.'"></span>
                                    </template>
                                </div>

                                {{-- Obligaciones con checkbox --}}
                                <div class="space-y-1">
                                    <template x-for="o in m.obligaciones" :key="o.id">
                                        <label class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-colors"
                                            :class="obligacionEstaSeleccionada(m.id, o.id) ? 'bg-brand-50 border border-brand-200' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100'">
                                            <input type="checkbox"
                                                :checked="obligacionEstaSeleccionada(m.id, o.id)"
                                                @change="toggleObligacion(m.id, o.id)"
                                                class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                            <span class="flex-1 text-sm text-gray-700" x-text="o.nombre_cargo"></span>
                                            <span class="text-sm font-semibold" :class="obligacionEstaSeleccionada(m.id, o.id) ? 'text-brand-700' : 'text-gray-900'"
                                                x-text="fmtMonto(o.saldo) + ' L.'"></span>
                                        </label>
                                    </template>
                                </div>

                                {{-- Total seleccionado y boton pagar --}}
                                <div class="flex items-center justify-between pt-3 mt-3 border-t border-gray-100">
                                    <div>
                                        <span class="text-sm text-gray-500">Total seleccionado: </span>
                                        <span class="font-bold text-gray-900" x-text="'L. ' + fmtMonto(totalSeleccionado(m))"></span>
                                    </div>
                                    <button @click="seleccionarMatricula(m)"
                                        :disabled="totalSeleccionado(m) <= 0"
                                        class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        Pagar ahora
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Step 2: Confirmar pago y subir comprobante --}}
            <template x-if="paso === 'pagar'">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-lg mx-auto">
                    <div class="flex items-center gap-2 mb-4">
                        <button @click="paso = 'seleccionar'; error = ''" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg></button>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Pago</h3>
                    </div>

                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-gray-500">Matrícula: <span class="font-semibold text-gray-900" x-text="matriculaSeleccionada.codigo"></span></p>
                        </div>

                    <div class="space-y-4">
                        <div x-show="matriculasPendientes.length > 1" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <label class="block text-sm font-medium text-amber-900 mb-2">Seleccione la matrícula a pagar</label>
                            <select x-model="matriculaSeleccionadaId" @change="cambiarMatriculaSeleccionada()" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <template x-for="m in matriculasPendientes" :key="m.id">
                                    <option :value="m.id" x-text="m.codigo + ' · ' + (m.nivel || 'Matrícula') + ' · L. ' + fmtMonto(totalSeleccionado(m))"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                            <select x-model="form.metodo_pago_id" @change="alCambiarMetodo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Seleccionar...</option>
                                <template x-for="mp in metodosPago" :key="mp.id">
                                    <option :value="mp.id" x-text="mp.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <template x-if="!esMetodoTarjeta(form.metodo_pago_id) && !esMetodoLink(form.metodo_pago_id)">
                            <div x-show="flujoPortal?.habilita_carga_comprobante">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Referencia
                                    <span x-show="esMetodoValidable(form.metodo_pago_id)" class="text-red-500">*</span>
                                    <span x-show="!esMetodoValidable(form.metodo_pago_id)" class="text-gray-400">(opcional)</span>
                                </label>
                                <input x-model="form.referencia" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Número de referencia o comprobante">
                            </div>
                        </template>
                        <template x-if="esMetodoValidable(form.metodo_pago_id)">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago <span class="text-red-500">*</span></label>
                                <input x-model="form.fecha_pago" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :max="hoyStr()">
                                <p class="text-xs text-gray-400 mt-1">Fecha en la que realizó el depósito o transferencia.</p>
                            </div>
                        </template>
                        <template x-if="!esMetodoTarjeta(form.metodo_pago_id) && !esMetodoLink(form.metodo_pago_id)">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (JPG, PNG, PDF, máx 10MB)</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="form.archivo = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                <p class="text-xs text-gray-400 mt-1">Opcional: puede subir el comprobante después desde "Mis Pagos"</p>
                            </div>
                        </template>
                        <template x-if="esMetodoTarjeta(form.metodo_pago_id)">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                                <p class="text-sm font-semibold text-blue-800 mb-1">Pago con Tarjeta</p>
                                <p class="text-xs text-blue-600">Será redirigido a PayPal para completar el pago de forma segura.</p>
                            </div>
                        </template>
                    </div>

                    <template x-if="error"><p class="text-sm text-red-600 mt-3" x-text="error"></p></template>
                    <template x-if="exito"><p class="text-sm text-green-600 mt-3" x-text="exito"></p></template>

                    <div class="flex gap-3 mt-6">
                        <button @click="paso = 'seleccionar'; error = ''" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                        <button @click="procesarPago()" :disabled="enviando" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                            <span x-show="!enviando" x-text="esMetodoTarjeta(form.metodo_pago_id) ? 'Pagar con PayPal' : 'Pagar L. ' + fmtMonto(totalSeleccionado(matriculaSeleccionada))"></span>
                            <span x-show="enviando">Procesando...</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 3: Historial de pagos --}}
            <template x-if="paso !== 'pagar'">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Historial de Pagos</h3></div>
                    <template x-if="pagos.length === 0">
                        <div class="p-8 text-center text-gray-400 text-sm">No tiene pagos registrados.</div>
                    </template>
                    <template x-if="pagos.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead><tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Código</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Concepto</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Monto</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Fecha</th>
                                    <th class="text-left px-4 py-3 font-medium text-gray-600">Comprobante</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="p in pagos" :key="p.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-mono text-xs text-gray-600" x-text="p.codigo"></td>
                                            <td class="px-4 py-3 font-medium text-gray-900" x-text="p.concepto || '-'"></td>
                                            <td class="px-4 py-3 font-semibold text-gray-900" x-text="fmtMonto(p.monto) + ' L.'"></td>
                                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="{'bg-amber-100 text-amber-700': p.estado === 'pendiente', 'bg-blue-100 text-blue-700': p.estado === 'en_revision', 'bg-green-100 text-green-700': p.estado === 'aprobado', 'bg-red-100 text-red-700': p.estado === 'rechazado'}" x-text="p.estado.replace('_', ' ')"></span></td>
                                            <td class="px-4 py-3 text-gray-500 text-xs" x-text="p.fecha"></td>
                                            <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <template x-if="p.estado === 'pendiente' || p.estado === 'rechazado'">
                                            <button x-show="flujoPortal?.habilita_carga_comprobante" @click="subirComprobantePago(p)" class="text-xs text-brand-600 font-medium hover:text-brand-700">Subir comprobante</button>
                                        </template>
                                        <template x-if="p.estado === 'rechazado' && p.motivo_rechazo">
                                            <button @click="verMotivoRechazo(p)" class="text-xs text-red-600 font-medium hover:text-red-700">Ver motivo</button>
                                        </template>
                                    </div>
                                    <template x-if="p.tiene_comprobante">
                                        <div class="space-y-1">
                                            <span class="text-xs text-green-600 font-medium">✓ Subido</span>
                                            <template x-for="c in (p.comprobantes || [])" :key="c.id">
                                                <div class="text-[11px] text-gray-600 rounded-md border border-gray-200 bg-gray-50 px-2 py-1">
                                                    <p class="font-medium text-gray-700" x-text="c.nombre_archivo"></p>
                                                    <p x-text="(c.tipo_archivo || '-') + ' · ' + (c.fecha || '-')"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                                <template x-if="p.estado !== 'pendiente' && !p.tiene_comprobante">
                                                    <span class="text-xs text-gray-400">—</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>

    {{-- Modal para subir comprobante a pago existente --}}
    <template x-if="selectedPago">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="selectedPago = null"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Subir Comprobante</h3>
                <p class="text-sm text-gray-500 mb-4">Pago: <span class="font-medium" x-text="selectedPago.codigo"></span> - <span class="font-bold" x-text="fmtMonto(selectedPago.monto) + ' L.'"></span></p>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                        <select x-model="formComp.metodo_pago_id" :disabled="!!selectedPago?.metodo_pago_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed">
                            <option value="">Seleccionar...</option>
                            <template x-for="mp in metodosPago" :key="mp.id">
                                <option :value="mp.id" x-text="mp.nombre"></option>
                            </template>
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Referencia <span x-show="esMetodoValidable(formComp.metodo_pago_id)" class="text-red-500">*</span></label><input x-model="formComp.referencia" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Número de referencia (opcional)"></div>
                    <template x-if="esMetodoValidable(formComp.metodo_pago_id)">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago <span class="text-red-500">*</span></label><input x-model="formComp.fecha_pago" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :max="hoyStr()"></div>
                    </template>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (JPG, PNG, PDF, máx 10MB) *</label><input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="formComp.archivo = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"></div>
                </div>
                <template x-if="uploadError"><p class="text-sm text-red-600 mt-3" x-text="uploadError"></p></template>
                <div class="flex gap-3 mt-6">
                    <button @click="selectedPago = null; uploadError = ''" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancelar</button>
                    <button @click="enviarComprobante()" :disabled="uploading" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        <span x-show="!uploading">Subir</span><span x-show="uploading">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="showMotivoRechazo && pagoMotivo">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showMotivoRechazo = false; pagoMotivo = null"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Motivo de rechazo</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Pago <span class="font-mono font-medium" x-text="pagoMotivo?.codigo"></span>
                </p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 whitespace-pre-wrap" x-text="pagoMotivo?.motivo_rechazo || 'Sin detalle'"></div>
                <div class="flex justify-end mt-4">
                    <button @click="showMotivoRechazo = false; pagoMotivo = null" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cerrar</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
@section('scripts')
<script>
function fmtMonto(val) {
    const n = parseFloat(val);
    if (isNaN(n)) return '0.00';
    const parts = n.toFixed(2).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return parts.join('.');
}

function pagoEstudiante() {
    return {
        loading: true, paso: 'seleccionar',
        matriculasPendientes: [], metodosPago: [], pagos: [], selectedPago: null,
        pagoMotivo: null, showMotivoRechazo: false,
        matriculaSeleccionada: null,
        matriculaSeleccionadaId: null,
        enviando: false, error: '', exito: '',
        form: { metodo_pago_id: '', referencia: '', fecha_pago: '', archivo: null },
        formComp: { metodo_pago_id: '', referencia: '', fecha_pago: '', archivo: null },
        uploading: false, uploadError: '',
        selectedObligaciones: {},

        token() { return localStorage.getItem('estudiante_token'); },

        esMetodoTarjeta(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return mp?.proveedor_pago?.codigo === 'PAYPAL' || mp?.requiere_proveedor;
        },

        esMetodoLink(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return !!mp?.permite_link_pago;
        },

        esMetodoValidable(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return mp?.codigo === 'DEP' || mp?.codigo === 'TRA';
        },

        hoyStr() {
            const d = new Date();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + mm + '-' + dd;
        },

        alCambiarMetodo() {
            this.error = '';
            if (this.esMetodoTarjeta(this.form.metodo_pago_id)) {
                this.form.referencia = '';
                this.form.fecha_pago = '';
                this.form.archivo = null;
            } else if (this.esMetodoLink(this.form.metodo_pago_id)) {
                this.form.referencia = '';
                this.form.fecha_pago = '';
                this.form.archivo = null;
            }
        },

        obligacionEstaSeleccionada(matriculaId, obligacionId) {
            return this.selectedObligaciones[matriculaId]?.includes(obligacionId) ?? false;
        },

        toggleObligacion(matriculaId, obligacionId) {
            if (!this.selectedObligaciones[matriculaId]) {
                this.selectedObligaciones[matriculaId] = [];
            }
            const idx = this.selectedObligaciones[matriculaId].indexOf(obligacionId);
            if (idx === -1) {
                this.selectedObligaciones[matriculaId].push(obligacionId);
            } else {
                this.selectedObligaciones[matriculaId].splice(idx, 1);
            }
        },

        seleccionarTodas(m) {
            this.selectedObligaciones[m.id] = m.obligaciones.map(o => o.id);
        },

        deseleccionarTodas(m) {
            this.selectedObligaciones[m.id] = [];
        },

        totalSeleccionado(m) {
            const ids = this.selectedObligaciones[m.id] || [];
            return m.obligaciones
                .filter(o => ids.includes(o.id))
                .reduce((s, o) => s + parseFloat(o.saldo || 0), 0);
        },

        obligacionIdsSeleccionados(matriculaId) {
            return this.selectedObligaciones[matriculaId] || [];
        },

        async loadData() {
            const token = this.token();
            if (!token) { window.location.href = '/estudiante/login'; return; }
            try {
                const [metodosRes, portalRes, pagosRes] = await Promise.allSettled([
                    window.axios.get('/api/v1/estudiantes/metodos-pago'),
                    window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } }),
                    window.axios.get('/api/v1/estudiantes/mis-pagos', { headers: { Authorization: `Bearer ${token}` } }),
                ]);

                if (metodosRes.status === 'fulfilled' && metodosRes.value.data.resultado === 'A') {
                    this.metodosPago = metodosRes.value.data.data || [];
                }

                if (portalRes.status === 'fulfilled' && portalRes.value.data.resultado === 'A') {
                    const data = portalRes.value.data.data;
                    this.matriculasPendientes = data.matriculas_pendientes || [];

                    if (this.matriculasPendientes.length > 0) {
                        this.matriculaSeleccionadaId = this.matriculasPendientes[0].id;
                        this.seleccionarTodas(this.matriculasPendientes[0]);
                    }
                }

                if (pagosRes.status === 'fulfilled' && pagosRes.value.data.resultado === 'A') {
                    this.pagos = pagosRes.value.data.data || [];
                }
            } catch(e) {
                if (e.response?.status === 401) window.location.href = '/estudiante/login';
            } finally { this.loading = false; }
        },

        seleccionarMatricula(m) {
            this.matriculaSeleccionada = m;
            this.matriculaSeleccionadaId = m.id;
            this.form = { metodo_pago_id: '', referencia: '', fecha_pago: '', archivo: null };
            this.error = '';
            this.exito = '';
            this.paso = 'pagar';
        },

        cambiarMatriculaSeleccionada() {
            this.matriculaSeleccionada = this.matriculasPendientes.find(m => m.id == this.matriculaSeleccionadaId) || null;
        },

        async procesarPago() {
            if (!this.form.metodo_pago_id) { this.error = 'Seleccione un método de pago'; return; }
            this.enviando = true; this.error = ''; this.exito = '';
            try {
                const token = this.token();
                const ids = this.obligacionIdsSeleccionados(this.matriculaSeleccionada.id);

                if (this.esMetodoTarjeta(this.form.metodo_pago_id)) {
                    const { data } = await window.axios.post('/api/v1/estudiantes/pago-tarjeta/iniciar', {
                        matricula_id: this.matriculaSeleccionada.id,
                        metodo_pago_id: this.form.metodo_pago_id,
                        obligacion_ids: ids,
                    }, { headers: { Authorization: `Bearer ${token}` } });
                    if (data.resultado === 'A') {
                        window.location.href = data.data.redirect_url;
                    } else {
                        this.error = data.mensaje;
                    }
                } else {
                    if (this.esMetodoValidable(this.form.metodo_pago_id)) {
                        if (!this.form.referencia || !this.form.referencia.trim()) { this.error = 'Ingrese el número de referencia'; this.enviando = false; return; }
                        if (!this.form.fecha_pago) { this.error = 'Ingrese la fecha de pago'; this.enviando = false; return; }
                    }
                    const payload = {
                        matricula_id: this.matriculaSeleccionada.id,
                        metodo_pago_id: this.form.metodo_pago_id,
                        referencia: this.form.referencia || '',
                    };
                    if (this.form.fecha_pago) payload.fecha_pago = this.form.fecha_pago;
                    if (this.esMetodoLink(this.form.metodo_pago_id)) {
                        payload.solicitar_link = true;
                    }
                    if (ids.length > 0) {
                        payload.obligacion_ids = ids;
                    }
                    const { data } = await window.axios.post('/api/v1/estudiantes/registrar-pago', payload, {
                        headers: { Authorization: `Bearer ${token}` }
                    });

                    if (data.resultado === 'A') {
                        const pagoId = data.data.pago_id;

                        if (this.form.archivo && !this.esMetodoLink(this.form.metodo_pago_id)) {
                            const fd = new FormData();
                            fd.append('pago_id', pagoId);
                            fd.append('metodo_pago_id', this.form.metodo_pago_id);
                            fd.append('referencia', this.form.referencia || '');
                            if (this.form.fecha_pago) fd.append('fecha_pago', this.form.fecha_pago);
                            fd.append('comprobante', this.form.archivo);
                            await window.axios.post('/api/v1/estudiantes/subir-comprobante', fd, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                        }

                    this.exito = data.data?.alerta_duplicado
                        ? 'Pago registrado. La referencia será verificada por contabilidad.'
                        : 'Pago en proceso. Verifique en el historial de pagos su estado final.';
                    setTimeout(() => {
                        this.paso = 'seleccionar';
                        this.loadData();
                    }, 1500);
                } else {
                    this.error = data.mensaje;
                }
                }
            } catch(e) {
                const body = e.response?.data;
                this.error = body?.mensaje || body?.message || body?.error || 'Error al procesar el pago. Intente de nuevo.';
            } finally { this.enviando = false; }
        },

        subirComprobantePago(p) {
            this.selectedPago = p;
            this.formComp = { metodo_pago_id: p.metodo_pago_id || '', referencia: '', fecha_pago: '', archivo: null };
            this.uploadError = '';
        },

        verMotivoRechazo(p) {
            this.pagoMotivo = p;
            this.showMotivoRechazo = true;
        },

        async enviarComprobante() {
            if (!this.formComp.archivo || !this.formComp.metodo_pago_id) { this.uploadError = 'Seleccione método de pago y archivo'; return; }
            if (this.esMetodoValidable(this.formComp.metodo_pago_id)) {
                if (!this.formComp.referencia || !this.formComp.referencia.trim()) { this.uploadError = 'Ingrese el número de referencia'; return; }
                if (!this.formComp.fecha_pago) { this.uploadError = 'Ingrese la fecha de pago'; return; }
            }
            this.uploading = true; this.uploadError = '';
            try {
                const token = this.token();
                const fd = new FormData();
                fd.append('pago_id', this.selectedPago.id);
                fd.append('metodo_pago_id', this.formComp.metodo_pago_id);
                fd.append('referencia', this.formComp.referencia);
                if (this.formComp.fecha_pago) fd.append('fecha_pago', this.formComp.fecha_pago);
                fd.append('comprobante', this.formComp.archivo);
                const { data } = await window.axios.post('/api/v1/estudiantes/subir-comprobante', fd, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.selectedPago = null;
                    this.formComp = { metodo_pago_id: '', referencia: '', fecha_pago: '', archivo: null };
                    this.loadData();
                } else {
                    this.uploadError = data.mensaje;
                }
            } catch(e) {
                const body = e.response?.data;
                this.uploadError = body?.mensaje || body?.message || body?.error || 'Error al subir el comprobante. Intente de nuevo.';
            } finally { this.uploading = false; }
        },
    };
}
</script>
@endsection
