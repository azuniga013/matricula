@extends('layouts.admin')
@section('content')
<div x-data="calificaciones()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Calificaciones</h1>
            <p class="page-subtitle">Registro de notas finales por horario académico</p>
        </div>
        <button x-show="horarioSeleccionado && estudiantes.length > 0 && api.hasPermission('calificaciones.registro.crear')" @click="guardar()" :disabled="saving" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span x-text="saving ? 'Guardando...' : 'Guardar Calificaciones'"></span>
        </button>
    </div>

    {{-- Filtros en cascada: Período → Nivel → Horario (AGENTS §4.9.1) --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">1. Período Académico</label>
                    <select x-model="filtros.periodo_academico_id" @change="cambioPeriodo()" class="input">
                        <option value="">Seleccionar período...</option>
                        <template x-for="p in periodos" :key="p.id">
                            <option :value="p.id" x-text="p.codigo + ' · ' + p.nombre" :selected="filtros.periodo_academico_id == p.id"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">2. Nivel Académico</label>
                    <select x-model="filtros.nivel_academico_id" @change="cambioNivel()" class="input" :disabled="!filtros.periodo_academico_id">
                        <option value="">Seleccionar nivel...</option>
                        <template x-for="n in nivelesDisponibles" :key="n.id">
                            <option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">3. Horario</label>
                    <select x-model="filtros.oferta_academica_id" @change="cambioHorario()" class="input" :disabled="!filtros.nivel_academico_id">
                        <option value="">Seleccionar horario...</option>
                        <template x-for="o in horariosDisponibles" :key="o.id">
                            <option :value="o.id" x-text="horarioTexto(o)"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Info contextual del horario --}}
    <template x-if="horarioSeleccionado">
        <div class="card mb-6 border-l-4 border-l-brand-500">
            <div class="card-body">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs">Nivel</p>
                        <p class="font-semibold" x-text="horarioSeleccionado.nivel_academico?.codigo + ' · ' + horarioSeleccionado.nivel_academico?.nombre"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Docente</p>
                        <p class="font-semibold" x-text="horarioSeleccionado.docente ? (horarioSeleccionado.docente.nombre + ' ' + horarioSeleccionado.docente.apellido) : '-'"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Modalidad / Horario</p>
                        <p class="font-semibold" x-text="(horarioSeleccionado.modalidad?.nombre || '-') + ' · ' + (horarioSeleccionado.horario?.nombre || '-')"></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Regla de aprobación</p>
                        <p class="font-semibold">Nota ≥ <span x-text="horarioSeleccionado.nivel_academico?.nota_minima_aprobar"></span>% · Faltas ≤ <span x-text="horarioSeleccionado.nivel_academico?.faltas_maximas_permitidas"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Estado vacío --}}
    <template x-if="!loading && !horarioSeleccionado">
        <div class="card">
            <div class="card-body py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                <p class="text-gray-500 font-medium">Seleccione período, nivel y horario para cargar los estudiantes</p>
                <p class="text-gray-400 text-sm mt-1">El orden de filtro es obligatorio: Período → Nivel → Horario</p>
            </div>
        </div>
    </template>

    {{-- Tabla de estudiantes --}}
    <template x-if="!loading && horarioSeleccionado">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Estudiante</th>
                            <th class="text-center w-32">Nota Final (0-100)</th>
                            <th class="text-center w-28">Faltas</th>
                            <th class="text-center">Resultado</th>
                            <th class="text-center">Estado Registro</th>
                            <th class="text-center">Certificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="e in estudiantes" :key="e.estudiante_id">
                            <tr>
                                <td class="font-mono text-xs font-semibold text-brand-600" x-text="e.codigo"></td>
                                <td class="font-medium" x-text="e.nombre"></td>
                                <td class="text-center">
                                    <input x-model.number="e.nota_final" type="number" min="0" max="100" step="0.01" class="input !w-24 text-center mx-auto" placeholder="—">
                                </td>
                                <td class="text-center">
                                    <input x-model.number="e.faltas" type="number" min="0" step="1" class="input !w-20 text-center mx-auto" placeholder="0">
                                </td>
                                <td class="text-center">
                                    <template x-if="e.nota_final !== null && e.nota_final !== '' && !isNaN(e.nota_final)">
                                        <span :class="esAprobado(e) ? 'badge-success' : 'badge-danger'" class="badge" x-text="esAprobado(e) ? 'Aprobado' : 'Reprobado'"></span>
                                    </template>
                                    <template x-if="e.nota_final === null || e.nota_final === '' || isNaN(e.nota_final)">
                                        <span class="badge badge-neutral">Sin nota</span>
                                    </template>
                                </td>
                                <td class="text-center">
                                    <span :class="{
                                        'badge-success': e.estado_registro === 'registrado',
                                        'badge-info': e.estado_registro === 'corregido',
                                        'badge-neutral': e.estado_registro === 'pendiente'
                                    }" class="badge" x-text="e.estado_registro"></span>
                                </td>
                                <td class="text-center">
                                    <button x-show="esAprobado(e) && api.hasPermission('calificaciones.modificar') && e.calificacion_id"
                                        @click="emitirCertificado(e)" class="btn btn-ghost btn-sm text-brand-600" title="Genera el historial académico si falta y emite el certificado">Generar certificado</button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="estudiantes.length === 0">
                            <tr><td colspan="7" class="text-center py-10 text-gray-400 text-sm">No hay estudiantes matriculados en este horario</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="card-body border-t border-gray-100 flex items-center justify-between" x-show="estudiantes.length > 0">
                <p class="text-xs text-gray-400">
                    <span x-text="estudiantes.length"></span> estudiantes ·
                    <span class="text-emerald-600 font-medium" x-text="conteoAprobados() + ' aprobados'"></span> ·
                    <span class="text-red-500 font-medium" x-text="(estudiantes.filter(e => e.nota_final !== null && e.nota_final !== '' && !isNaN(e.nota_final)).length - conteoAprobados()) + ' reprobados'"></span>
                </p>
                <p class="text-xs text-gray-400">Resultado calculado con nota ≥ <span x-text="horarioSeleccionado?.nivel_academico?.nota_minima_aprobar"></span>% y faltas ≤ <span x-text="horarioSeleccionado?.nivel_academico?.faltas_maximas_permitidas"></span></p>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
function calificaciones() {
    return {
        loading: false, saving: false,
        periodos: [], ofertas: [],
        filtros: { periodo_academico_id: '', nivel_academico_id: '', oferta_academica_id: '' },
        estudiantes: [],

        async init() {
            const h = { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } };
            try {
                const [p, o] = await Promise.allSettled([
                    window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                    window.axios.get('/api/v1/ofertas/academicas?per_page=200', h),
                ]);
                this.periodos = p.status === 'fulfilled' ? (p.value.data.data?.data || p.value.data.data || []) : [];
                this.ofertas = o.status === 'fulfilled' ? (o.value.data.data?.data || o.value.data.data || []) : [];
                // Precargar período activo (AGENTS §4.9.1)
                const activo = this.periodos.find(x => x.estado === 'activo');
                if (activo) { this.filtros.periodo_academico_id = activo.id; }
                if (!this.filtros.nivel_academico_id && this.nivelesDisponibles.length > 0) {
                    this.filtros.nivel_academico_id = this.nivelesDisponibles[0].id;
                }
                if (!this.filtros.oferta_academica_id && this.horariosDisponibles.length > 0) {
                    this.filtros.oferta_academica_id = this.horariosDisponibles[0].id;
                }
                if (this.filtros.oferta_academica_id) {
                    await this.cambioHorario();
                }
            } catch(e) {}
        },

        get nivelesDisponibles() {
            if (!this.filtros.periodo_academico_id) return [];
            const mapa = new Map();
            this.ofertas
                .filter(o => o.periodo_academico_id == this.filtros.periodo_academico_id && o.nivel_academico)
                .forEach(o => mapa.set(o.nivel_academico.id, o.nivel_academico));
            return [...mapa.values()].sort((a, b) => (a.orden || 0) - (b.orden || 0));
        },

        get horariosDisponibles() {
            if (!this.filtros.nivel_academico_id) return [];
            return this.ofertas.filter(o =>
                o.periodo_academico_id == this.filtros.periodo_academico_id &&
                o.nivel_academico_id == this.filtros.nivel_academico_id
            );
        },

        get horarioSeleccionado() {
            return this.ofertas.find(o => o.id == this.filtros.oferta_academica_id) || null;
        },

        cambioPeriodo() {
            this.filtros.nivel_academico_id = '';
            this.filtros.oferta_academica_id = '';
            this.estudiantes = [];
        },

        cambioNivel() {
            this.filtros.oferta_academica_id = '';
            this.estudiantes = [];
        },

        async cambioHorario() {
            if (!this.filtros.oferta_academica_id) { this.estudiantes = []; return; }
            this.loading = true;
            const h = { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } };
            try {
                const [mRes, cRes, fRes] = await Promise.allSettled([
                    window.axios.get(`/api/v1/matriculas?oferta_academica_id=${this.filtros.oferta_academica_id}&estado=matriculado&per_page=100`, h),
                    window.axios.get(`/api/v1/calificaciones?oferta_academica_id=${this.filtros.oferta_academica_id}&per_page=100`, h),
                    window.axios.get(`/api/v1/asistencias/faltas-por-oferta?oferta_academica_id=${this.filtros.oferta_academica_id}`, h),
                ]);

                const mData = mRes.status === 'fulfilled' ? mRes.value.data : null;
                if (!mData || mData.resultado !== 'A') throw new Error(mData?.mensaje || 'No fue posible cargar los estudiantes');

                const mats = mData.data?.data || mData.data || [];
                const cals = cRes.status === 'fulfilled' ? (cRes.value.data.data?.data || cRes.value.data.data || []) : [];
                const faltas = fRes.status === 'fulfilled' ? (fRes.value.data?.data || fRes.value.data || []) : [];

                const mapaCals = new Map(cals.map(c => [c.estudiante_id, c]));
                const mapaFaltas = new Map(faltas.map(f => [f.estudiante_id, f.faltas]));

                this.estudiantes = mats.map(m => {
                    const cal = mapaCals.get(m.estudiante_id);
                    const faltasAsistencia = mapaFaltas.has(m.estudiante_id) ? mapaFaltas.get(m.estudiante_id) : (cal?.faltas ?? 0);
                    return {
                        estudiante_id: m.estudiante_id,
                        codigo: m.estudiante?.codigo || '-',
                        nombre: `${m.estudiante?.nombre || ''} ${m.estudiante?.apellido || ''}`.trim(),
                        nota_final: cal?.nota_final ?? null,
                        faltas: faltasAsistencia ?? 0,
                        calificacion_id: cal?.id || null,
                        estado_registro: cal?.estado || 'pendiente',
                    };
                }).sort((a, b) => a.nombre.localeCompare(b.nombre));
            } catch(e) {
                this.estudiantes = [];
                this.toast(window.extractError(e, 'No se pudieron cargar los estudiantes'), 'error');
            }
            finally { this.loading = false; }
        },

        esAprobado(e) {
            const nivel = this.horarioSeleccionado?.nivel_academico;
            if (!nivel) return false;
            return Number(e.nota_final) >= Number(nivel.nota_minima_aprobar)
                && Number(e.faltas || 0) <= Number(nivel.faltas_maximas_permitidas);
        },

        async emitirCertificado(e) {
            try {
                const { data } = await window.axios.post('/api/v1/estudiantes/certificados/electronicos/admin', {
                    calificacion_id: e.calificacion_id,
                }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    window.open(data.data?.pdf_url || `/certificados/${data.data.token_validacion}/pdf`, '_blank');
                    this.toast('Historial académico y certificado generados', 'success');
                }
            } catch (error) {
                this.toast(window.extractError(error, 'No se pudo emitir el certificado'), 'error');
            }
        },

        conteoAprobados() {
            return this.estudiantes.filter(e => e.nota_final !== null && e.nota_final !== '' && !isNaN(e.nota_final) && this.esAprobado(e)).length;
        },

        async guardar() {
            this.saving = true;
            try {
                const payload = {
                    oferta_academica_id: this.filtros.oferta_academica_id,
                    calificaciones: this.estudiantes
                        .filter(e => e.nota_final !== null && e.nota_final !== '' && !isNaN(e.nota_final))
                        .map(e => ({ estudiante_id: e.estudiante_id, nota_final: e.nota_final, faltas: e.faltas || 0 })),
                };
                if (payload.calificaciones.length === 0) {
                    this.toast('No hay notas para guardar', 'warning');
                    this.saving = false;
                    return;
                }
                const { data } = await window.axios.post('/api/v1/calificaciones/registrar', payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.toast(data.mensaje || 'Calificaciones registradas', 'success');
                    await this.cambioHorario();
                } else {
                    this.toast(data.mensaje || 'Error al guardar', 'error');
                }
            } catch(e) {
                this.toast(window.extractError(e, 'Error al guardar'), 'error');
            } finally { this.saving = false; }
        },

        horarioTexto(o) {
            const horario = o.horario?.nombre || 'Sin horario';
            const docente = o.docente ? `${o.docente.nombre} ${o.docente.apellido}` : '';
            return `${o.codigo} · ${horario}${docente ? ' · ' + docente : ''}`;
        },

        toast(message, type) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type } }));
        }
    }
}
</script>
@endsection
