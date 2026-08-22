@extends('layouts.admin')
@section('content')
<div x-data="asistencias()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Asistencias</h1>
            <p class="page-subtitle">Pasar lista — Registro de asistencia por grupo</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadAll()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            </button>
        </div>
    </div>

    {{-- Filtros en cascada --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Período</label>
                    <select x-model="filtros.periodo" @change="onFiltroPeriodo()" class="input">
                        <option value="">Todos los períodos</option>
                        <template x-for="p in periodos" :key="p.id">
                            <option :value="p.id" x-text="p.codigo + ' · ' + p.nombre" :selected="p.estado === 'activo' && !filtros.periodo"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="filtros.sucursal" @change="onFiltroSucursal()" class="input">
                        <option value="">Todas</option>
                        <template x-for="s in sucursales" :key="s.id">
                            <option :value="s.id" x-text="s.codigo + ' · ' + s.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Nivel</label>
                    <select x-model="filtros.nivel" @change="onFiltroNivel()" class="input">
                        <option value="">Todos</option>
                        <template x-for="n in niveles" :key="n.id">
                            <option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Grupo / Oferta</label>
                    <select x-model="ofertaId" @change="onOfertaChange()" class="input" :disabled="cargandoOfertas">
                        <option value="">Seleccionar grupo...</option>
                        <template x-for="o in ofertas" :key="o.id">
                            <option :value="o.id" x-text="o.codigo + ' · ' + (o.nivel_academico?.nombre || '') + ' · ' + (o.horario?.nombre || '') + ' · ' + (o.docente?.nombre || '')"></option>
                        </template>
                    </select>
                    <p x-show="!cargandoOfertas && ofertas.length === 0 && !errorCarga" class="mt-1 text-xs text-amber-700">No hay grupos disponibles con los filtros seleccionados.</p>
                    <p x-show="errorCarga" class="mt-1 text-xs text-red-600" x-text="errorCarga"></p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="label">Fecha</label>
                    <input x-model="fecha" type="date" class="input">
                </div>
                <div class="flex items-end gap-2">
                    <button @click="cargarEstudiantes()" :disabled="!ofertaId" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        Cargar
                    </button>
                    <button x-show="estudiantes.length > 0 && api.hasPermission('asistencias.crear')" @click="cargarAsistenciaExistente()" class="btn btn-outline btn-sm" title="Cargar asistencias ya registradas">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
    </template>

    <template x-if="!loading && estudiantes.length === 0 && ofertaId">
        <div class="card"><div class="card-body text-center text-gray-400 py-12"><p>No hay estudiantes matriculados en este grupo</p></div></div>
    </template>

    <template x-if="ofertaSeleccionada">
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex-1">
                        <label class="label">Link WhatsApp del período</label>
                        <input x-model="whatsappLinkPeriodo" type="text" class="input" placeholder="https://chat.whatsapp.com/...">
                        <p class="mt-1 text-xs text-gray-500">Actualice aquí el link vigente del grupo para esta oferta y período.</p>
                    </div>
                    <div>
                        <button x-show="api.hasPermission('asistencias.crear')" @click="guardarWhatsappPeriodo()" :disabled="guardandoWhatsapp" class="btn btn-outline">
                            <span x-text="guardandoWhatsapp ? 'Guardando...' : 'Guardar link WhatsApp'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-if="estudiantes.length > 0">
        <div class="card">
            <div class="card-body border-b flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    <span x-text="estudiantes.length"></span> estudiantes · 
                    <span x-text="fecha || 'Sin fecha'"></span>
                </p>
                <button @click="guardarAsistencias()" :disabled="guardando" class="btn btn-primary btn-sm">
                    <span x-text="guardando ? 'Guardando...' : 'Guardar Asistencias'"></span>
                </button>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Código</th><th>Nombre</th><th class="text-center">Presente</th><th class="text-center">Falta</th><th class="text-center">Justificada</th><th class="text-center">Tardanza</th><th>Observación</th></tr></thead>
                    <tbody>
                        <template x-for="(e, idx) in estudiantes" :key="e.matricula_id">
                            <tr :class="e.estado === 'falta' ? 'bg-red-50' : e.estado === 'justificada' ? 'bg-amber-50' : ''">
                                <td class="font-mono text-xs font-semibold text-brand-600" x-text="e.codigo"></td>
                                <td class="font-medium" x-text="e.nombre + ' ' + e.apellido"></td>
                                <td class="text-center">
                                    <input type="radio" :name="'asist_' + e.matricula_id" value="presente" x-model="e.estado" class="w-4 h-4 text-emerald-500 border-gray-300 focus:ring-emerald-500">
                                </td>
                                <td class="text-center">
                                    <input type="radio" :name="'asist_' + e.matricula_id" value="falta" x-model="e.estado" class="w-4 h-4 text-red-500 border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="text-center">
                                    <input type="radio" :name="'asist_' + e.matricula_id" value="justificada" x-model="e.estado" class="w-4 h-4 text-amber-500 border-gray-300 focus:ring-amber-500">
                                </td>
                                <td class="text-center">
                                    <input type="radio" :name="'asist_' + e.matricula_id" value="tardanza" x-model="e.estado" class="w-4 h-4 text-blue-500 border-gray-300 focus:ring-blue-500">
                                </td>
                                <td><input x-model="e.observacion" type="text" class="input text-xs py-1" placeholder="Observación"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
function asistencias() {
    return {
        loading: false, guardando: false, cargandoOfertas: false, errorCarga: '',
        periodos: [], sucursales: [], niveles: [], ofertas: [], estudiantes: [],
        ofertaId: '', fecha: window.toLocalDateInput(),
        filtros: { periodo: '', sucursal: '', nivel: '' },
        ofertasRequestId: 0,
        estudiantesRequestId: 0,
        whatsappLinkPeriodo: '', guardandoWhatsapp: false,

        get ofertaSeleccionada() {
            return this.ofertas.find(o => String(o.id) === String(this.ofertaId)) || null;
        },

        async init() {
            await this.cargarCatalogos();
            this.preseleccionarPeriodo();
            await this.cargarOfertas();
        },

        async cargarCatalogos() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            const [pRes, sRes, nRes] = await Promise.allSettled([
                window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                window.axios.get('/api/v1/catalogos-academicos/niveles-academicos', h),
            ]);
            const extract = r => r.status === 'fulfilled' ? (r.value.data.data?.data || r.value.data.data || []) : [];
            this.periodos = extract(pRes); this.sucursales = extract(sRes); this.niveles = extract(nRes);
        },

        preseleccionarPeriodo() {
            const activo = this.periodos.find(p => p.estado === 'activo');
            if (activo) this.filtros.periodo = activo.id;
        },

        async cargarOfertas() {
            const requestId = ++this.ofertasRequestId;
            this.cargandoOfertas = true;
            this.errorCarga = '';
            try {
                let url = '/api/v1/asistencias/ofertas-disponibles?';
                if (this.filtros.periodo) url += `periodo_academico_id=${this.filtros.periodo}&`;
                if (this.filtros.sucursal) url += `sucursal_id=${this.filtros.sucursal}&`;
                if (this.filtros.nivel) url += `nivel_academico_id=${this.filtros.nivel}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (requestId !== this.ofertasRequestId) return;
                if (data.resultado !== 'A') throw new Error(data.mensaje || 'No fue posible cargar los grupos');
                this.ofertas = data.data || [];
                if (!this.ofertas.some(o => String(o.id) === String(this.ofertaId))) this.ofertaId = '';
                if (!this.ofertaId && this.ofertas.length > 0) this.ofertaId = this.ofertas[0].id;
                this.whatsappLinkPeriodo = this.ofertaSeleccionada?.whatsapp_link_periodo || '';
                if (this.ofertaId) await this.cargarEstudiantes();
                else this.estudiantes = [];
            } catch(e) {
                if (requestId !== this.ofertasRequestId) return;
                this.ofertas = [];
                this.estudiantes = [];
                this.errorCarga = window.extractError(e, 'No fue posible cargar los grupos. Verifique sus permisos de asistencia.');
            }
            finally {
                if (requestId === this.ofertasRequestId) this.cargandoOfertas = false;
            }
        },

        limpiarSeleccionGrupo() {
            this.ofertaId = '';
            this.estudiantes = [];
        },

        onFiltroPeriodo() { this.limpiarSeleccionGrupo(); this.cargarOfertas(); },
        onFiltroSucursal() { this.limpiarSeleccionGrupo(); this.cargarOfertas(); },
        onFiltroNivel() { this.limpiarSeleccionGrupo(); this.cargarOfertas(); },

        async loadAll() {
            this.preseleccionarPeriodo();
            await this.cargarOfertas();
            if (this.ofertaId) await this.cargarEstudiantes();
        },

        onOfertaChange() {
            if (!this.ofertaId) return;
            this.whatsappLinkPeriodo = this.ofertaSeleccionada?.whatsapp_link_periodo || '';
            this.cargarEstudiantes();
        },

        async guardarWhatsappPeriodo() {
            if (!this.ofertaId) return;
            this.guardandoWhatsapp = true;
            try {
                const { data } = await window.axios.post(`/api/v1/ofertas/academicas/${this.ofertaId}/whatsapp-periodo`, {
                    whatsapp_link_periodo: this.whatsappLinkPeriodo || null,
                }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    const oferta = this.ofertas.find(o => String(o.id) === String(this.ofertaId));
                    if (oferta) oferta.whatsapp_link_periodo = data.data?.whatsapp_link_periodo || null;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje || 'Link actualizado', type: 'success' } }));
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'No se pudo guardar el link de WhatsApp'), type: 'error' } }));
            } finally {
                this.guardandoWhatsapp = false;
            }
        },

        async cargarEstudiantes() {
            if (!this.ofertaId) return;
            const ofertaIdActual = this.ofertaId;
            const requestId = ++this.estudiantesRequestId;
            this.loading = true;
            try {
                const h = { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } };
                const [listaRes, existentesRes] = await Promise.allSettled([
                    window.axios.get(`/api/v1/asistencias/estudiantes-por-oferta?oferta_academica_id=${ofertaIdActual}`, h),
                    this.fecha ? window.axios.get(`/api/v1/asistencias/por-oferta?oferta_academica_id=${ofertaIdActual}&fecha=${this.fecha}`, h) : Promise.resolve({ data: { data: [] } }),
                ]);
                if (requestId !== this.estudiantesRequestId || String(ofertaIdActual) !== String(this.ofertaId)) return;

                const listaData = listaRes.status === 'fulfilled' ? listaRes.value.data : null;
                if (!listaData) throw new Error('No se pudo cargar el listado de estudiantes');
                if (listaData.resultado !== 'A') throw new Error(listaData.mensaje || 'No fue posible cargar los estudiantes');

                const estudiantes = (listaData.data || []).map(e => ({ ...e, estado: 'presente', observacion: '' }));
                const existentes = (existentesRes.status === 'fulfilled' && existentesRes.value.data?.data) || [];
                for (const ex of existentes) {
                    const idx = estudiantes.findIndex(e => e.matricula_id === ex.matricula_id);
                    if (idx >= 0) {
                        estudiantes[idx].estado = ex.estado;
                        estudiantes[idx].observacion = ex.observacion || '';
                    }
                }

                this.estudiantes = estudiantes;
            } catch(e) {
                if (requestId !== this.estudiantesRequestId || String(ofertaIdActual) !== String(this.ofertaId)) return;
                this.estudiantes = [];
                this.errorCarga = window.extractError(e, 'No fue posible cargar los estudiantes. Verifique sus permisos de asistencia.');
            }
            finally {
                if (requestId === this.estudiantesRequestId) this.loading = false;
            }
        },

        async cargarAsistenciaExistente() {
            if (!this.ofertaId || !this.fecha) return;
            try {
                const { data } = await window.axios.get(`/api/v1/asistencias/por-oferta?oferta_academica_id=${this.ofertaId}&fecha=${this.fecha}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                const existentes = data.data || [];
                if (existentes.length === 0) return;
                for (const ex of existentes) {
                    const idx = this.estudiantes.findIndex(e => e.matricula_id === ex.matricula_id);
                    if (idx >= 0) {
                        this.estudiantes[idx].estado = ex.estado;
                        this.estudiantes[idx].observacion = ex.observacion || '';
                    }
                }
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Asistencias cargadas', type: 'success' } }));
            } catch(e) { console.error(e); }
        },

        async guardarAsistencias() {
            if (!this.ofertaId || !this.fecha || this.estudiantes.length === 0) return;
            this.guardando = true;
            try {
                const asistencias = this.estudiantes.map(e => ({
                    matricula_id: e.matricula_id,
                    estado: e.estado,
                    cuenta_como_falta: e.estado === 'falta',
                    observacion: e.observacion || null,
                }));

                const { data } = await window.axios.post('/api/v1/asistencias/registrar', {
                    oferta_academica_id: this.ofertaId,
                    fecha: this.fecha,
                    asistencias: asistencias,
                }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });

                if (data.resultado === 'A') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje, type: 'success' } }));
                }
            } catch(e) {
                const msg = window.extractError(e, 'Error al guardar');
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: msg, type: 'error' } }));
            }
            finally { this.guardando = false; }
        },
    }
}
</script>
@endsection
