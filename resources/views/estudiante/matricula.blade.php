@extends('layouts.portal')
@section('title', 'Matrícula Online')
@section('content')
<div x-data="matricula()" x-init="loadOfertas()" x-cloak>
    {{-- Wizard Steps --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Matrícula Online</h2>
        <p class="text-sm text-gray-500">Complete los pasos para matricularse</p>
    </div>
    <div class="mb-6" x-show="!resultado">
        <div class="flex items-center gap-2 text-sm font-medium">
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-brand-600 text-white">
                <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-xs">1</span>
                Seleccionar horario
            </span>
            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3m0 0 3-3m-3 3v-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-gray-400">
                <span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-xs">2</span>
                <span x-text="'Pagar' "></span>
            </span>
        </div>
    </div>

     <template x-if="periodoActual">
         <div class="mb-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-700 border border-brand-100">
             <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                 <span class="font-semibold">Período de matrícula:</span>
                 <span x-text="(periodoActual.codigo ? periodoActual.codigo + ' · ' : '') + periodoActual.nombre"></span>
             </div>
             <div class="mt-1 text-xs text-brand-600" x-text="'Vigencia: ' + fmtFecha(periodoActual.fecha_inicio) + ' al ' + fmtFecha(periodoActual.fecha_fin)"></div>
         </div>
    </template>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading && matriculasPendientes.length > 0 && !resultado">
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="font-semibold text-amber-900 mb-1">Tiene conceptos de pago pendientes</h3>
                    <p class="text-sm text-amber-800">Revise sus reservas activas antes de crear una nueva matrícula.</p>
                </div>
                <a href="/estudiante/pagos" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">Ir a mis pagos</a>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="m in matriculasPendientes" :key="m.id">
                    <div class="bg-white border border-amber-100 rounded-lg p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900" x-text="m.codigo + ' · ' + (m.nivel || 'Matrícula')"></p>
                                <p class="text-xs text-gray-500" x-text="m.horario ? m.horario + ' · ' + m.regimen : m.regimen"></p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="m.estado === 'reservada' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'" x-text="m.estado"></span>
                        </div>
                        <p class="text-sm text-gray-600 mt-3" x-text="'Obligaciones pendientes: ' + m.obligaciones.length + ' · Total: L. ' + fmtMonto(m.obligaciones.reduce((s, o) => s + Number(o.saldo || 0), 0))"></p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Resultado --}}
    <template x-if="resultado">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-brand-100 flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="resultado.estado_pago === 'solicita_link' ? 'Solicitud de link enviada a contabilidad' : 'Matrícula Reservada'"></h3>
            <p class="text-sm text-gray-500 mb-1">Su código de matrícula es:</p>
            <p class="text-lg font-mono font-bold text-brand-600 mb-4" x-text="resultado.codigo"></p>
            <div class="max-w-md mx-auto mb-6 text-left bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Estado matrícula</span><span class="font-medium" x-text="resultado.estado_matricula === 'reservada' ? 'Pendiente' : resultado.estado_matricula"></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Estado pago</span><span class="font-medium" x-text="resultado.estado_pago === 'solicita_link' ? 'Solicita link' : resultado.estado_pago"></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Obligaciones</span><span class="font-medium" x-text="resultado.obligaciones_cantidad + ' registradas'"></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Monto total</span><span class="font-semibold text-gray-900" x-text="fmtMonto(resultado.obligaciones_total || 0) + ' L.'"></span></div>
            </div>
            <p class="text-sm text-gray-500 mb-6" x-text="resultado.estado_pago === 'solicita_link' ? 'Su solicitud fue enviada. Contabilidad cargará el enlace en la sección de pagos.' : 'Realice su pago y suba el comprobante para confirmar su matrícula.'"></p>
            <div class="flex gap-3 justify-center">
                <a href="/estudiante/pagos" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Ir a Pagos</a>
                <a href="/estudiante" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Volver al Portal</a>
            </div>
        </div>
    </template>

    {{-- Ofertas --}}
    <template x-if="!loading && !resultado">
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan de estudio</label>
                        <select x-model="planSeleccionadoId" @change="alCambiarPlan()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Seleccionar plan...</option>
                            <template x-for="plan in planesDisponibles" :key="plan.id"><option :value="plan.id" x-text="plan.codigo + ' · ' + plan.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nivel académico</label>
                        <select x-model="nivelSeleccionadoId" @change="selected = null" :disabled="!planSeleccionadoId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100">
                            <option value="">Todos los niveles</option>
                            <template x-for="nivel in nivelesDisponibles" :key="nivel.id"><option :value="nivel.id" x-text="nivel.codigo + ' · ' + nivel.nombre"></option></template>
                        </select>
                    </div>
                </div>
            </div>
            <template x-if="ofertasFiltradas.length === 0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <p class="text-gray-400" x-text="planSeleccionadoId ? 'No hay ofertas disponibles para el plan y nivel seleccionados.' : 'Seleccione un plan de estudio para ver sus ofertas.'"></p>
                </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="o in ofertasFiltradas" :key="o.id">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow" :class="selected?.id === o.id ? 'ring-2 ring-brand-500' : ''">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-900" x-text="o.nivel"></h4>
                                <p class="text-xs text-gray-400 font-mono" x-text="o.nivel_codigo"></p>
                            </div>
                            <div class="flex gap-1">
                                <span class="text-xs font-medium text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full" x-text="o.regimen"></span>
                                <span class="text-xs font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full" x-text="o.modalidad"></span>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Horario:</span><span class="font-medium text-gray-900" x-text="o.horario || '-'"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Docente:</span><span class="font-medium text-gray-900" x-text="o.docente || '-'"></span></div>
                             <div class="flex justify-between"><span class="text-gray-500">Período:</span><span class="font-medium text-gray-900 text-right" x-text="(o.periodo_codigo ? o.periodo_codigo + ' · ' : '') + (o.periodo || '-')"></span></div>
                             <div class="flex justify-between"><span class="text-gray-500">Vigencia:</span><span class="font-medium text-gray-900 text-right" x-text="fmtFecha(o.periodo_fecha_inicio) + ' al ' + fmtFecha(o.periodo_fecha_fin)"></span></div>
                             <div class="flex justify-between"><span class="text-gray-500">Cupos:</span><span class="font-medium" :class="o.cupos_disponibles <= 3 ? 'text-amber-600' : 'text-brand-600'" x-text="o.cupos_disponibles + ' disponibles'"></span></div>
                            <template x-if="o.monto_total">
                                <div class="flex justify-between"><span class="text-gray-500">Total:</span><span class="font-bold text-gray-900" x-text="fmtMonto(o.monto_total) + ' L.'"></span></div>
                            </template>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button @click="selected = o" :class="selected?.id === o.id ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors" x-text="selected?.id === o.id ? 'Seleccionado' : 'Seleccionar'"></button>
                        </div>
                    </div>
                </template>
            </div>
            <template x-if="selected">
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Confirmar Reserva</h3>
                     <p class="text-sm text-gray-500 mb-4">Está a punto de reservar una matrícula para <span class="font-medium" x-text="selected.nivel"></span> - <span x-text="selected.horario"></span> en el período <span class="font-medium" x-text="(selected.periodo_codigo ? selected.periodo_codigo + ' · ' : '') + selected.periodo"></span>, vigente del <span class="font-medium" x-text="fmtFecha(selected.periodo_fecha_inicio)"></span> al <span class="font-medium" x-text="fmtFecha(selected.periodo_fecha_fin)"></span>.</p>
                    <template x-if="error"><p class="text-sm text-red-600 mb-3" x-text="error"></p></template>
                    <button @click="reservar()" :disabled="saving" class="px-6 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                        <span x-show="!saving">Reservar Matrícula</span>
                        <span x-show="saving">Reservando...</span>
                    </button>
                </div>
            </template>
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

function fmtFecha(val) {
    if (!val) return '-';
    const partes = String(val).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : val;
}

function matricula() {
    return { loading: true, periodoActual: null, ofertas: [], matriculasPendientes: [], selected: null, saving: false, error: '', resultado: null, planSeleccionadoId: '', nivelSeleccionadoId: '',
        get planesDisponibles() {
            const planes = {};
            this.ofertas.forEach(oferta => {
                if (oferta.plan_estudio_id) planes[oferta.plan_estudio_id] = { id: oferta.plan_estudio_id, codigo: oferta.plan_estudio_codigo || '', nombre: oferta.plan_estudio_nombre || '' };
            });
            return Object.values(planes).sort((a, b) => (a.codigo || '').localeCompare(b.codigo || ''));
        },
        get nivelesDisponibles() {
            const niveles = {};
            this.ofertas.filter(oferta => String(oferta.plan_estudio_id) === String(this.planSeleccionadoId)).forEach(oferta => {
                niveles[oferta.nivel_codigo] = { id: oferta.nivel_academico_id, codigo: oferta.nivel_codigo, nombre: oferta.nivel };
            });
            return Object.values(niveles).sort((a, b) => (a.codigo || '').localeCompare(b.codigo || ''));
        },
        get ofertasFiltradas() {
            if (!this.planSeleccionadoId) return [];
            return this.ofertas.filter(oferta => String(oferta.plan_estudio_id) === String(this.planSeleccionadoId) && (!this.nivelSeleccionadoId || String(oferta.nivel_academico_id) === String(this.nivelSeleccionadoId)));
        },
        alCambiarPlan() { this.nivelSeleccionadoId = ''; this.selected = null; },
        async loadOfertas() {
            const token = localStorage.getItem('estudiante_token');
            if (!token) { window.location.href = '/estudiante/login'; return; }
            try {
                const [ofertasRes, portalRes] = await Promise.allSettled([
                    window.axios.get('/api/v1/estudiantes/mis-ofertas', { headers: { Authorization: `Bearer ${token}` } }),
                    window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } }),
                ]);
                if (ofertasRes.status === 'fulfilled' && ofertasRes.value.data.resultado === 'A') {
                    this.periodoActual = ofertasRes.value.data.data.periodo_actual || null;
                    this.ofertas = ofertasRes.value.data.data.ofertas || [];
                    if (this.planSeleccionadoId && !this.planesDisponibles.some(plan => String(plan.id) === String(this.planSeleccionadoId))) this.alCambiarPlan();
                }
                if (portalRes.status === 'fulfilled' && portalRes.value.data.resultado === 'A') this.matriculasPendientes = portalRes.value.data.data.matriculas_pendientes || [];
            } catch(e) { if (e.response?.status === 401) window.location.href = '/estudiante/login'; }
            finally { this.loading = false; }
            if (!this.pollingInterval) this.pollingInterval = setInterval(() => this.loadOfertas(), 30000);
        },
        async reservar() {
            if (!this.selected) return;
            this.saving = true; this.error = '';
            try {
                const token = localStorage.getItem('estudiante_token');
                const { data } = await window.axios.post('/api/v1/estudiantes/reservar-matricula', { oferta_academica_id: this.selected.id, plan_estudio_id: this.planSeleccionadoId }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') { this.resultado = { codigo: data.data.matricula_codigo, estado_matricula: data.data.estado_matricula || data.data.estado, estado_pago: data.data.estado_pago || data.data.estado, obligaciones_total: data.data.obligaciones_total, obligaciones_cantidad: data.data.obligaciones_cantidad }; }
                else { this.error = data.mensaje; }
            } catch(e) {
                const body = e.response?.data;
                this.error = body?.mensaje || body?.message || body?.error || 'Error al reservar. Intente de nuevo.';
            }
            finally { this.saving = false; }
        }
    };
}
</script>
@endsection
