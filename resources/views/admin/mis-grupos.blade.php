@extends('layouts.admin')

@section('content')
<div x-data="misGrupos()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Mis Horarios</h1>
            <p class="page-subtitle">Administre el link de WhatsApp vigente por horario y período</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="cargarOfertas()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                Actualizar
            </button>
        </div>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">Período</label>
                    <select x-model="filtros.periodo" @change="cargarOfertas()" class="input">
                        <option value="">Todos los períodos</option>
                        <template x-for="p in periodos" :key="p.id">
                            <option :value="p.id" x-text="p.codigo + ' · ' + p.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="filtros.sucursal" @change="cargarOfertas()" class="input">
                        <option value="">Todas</option>
                        <template x-for="s in sucursales" :key="s.id">
                            <option :value="s.id" x-text="s.codigo + ' · ' + s.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Nivel</label>
                    <select x-model="filtros.nivel" @change="cargarOfertas()" class="input">
                        <option value="">Todos</option>
                        <template x-for="n in niveles" :key="n.id">
                            <option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
    </template>

    <template x-if="!loading && errorCarga">
        <div class="card mb-6"><div class="card-body"><p class="text-sm text-red-600" x-text="errorCarga"></p></div></div>
    </template>

    <template x-if="!loading && !errorCarga && ofertas.length === 0">
        <div class="card"><div class="card-body text-center text-gray-400 py-12"><p>No hay horarios disponibles con los filtros seleccionados.</p></div></div>
    </template>

    <div x-show="!loading && ofertas.length > 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <template x-for="oferta in ofertas" :key="oferta.id">
            <div class="card">
                <div class="card-body space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900" x-text="oferta.codigo + ' · ' + (oferta.nivel_academico?.nombre || 'Horario')"></h3>
                            <p class="text-sm text-gray-500" x-text="(oferta.periodo_academico?.nombre || '-') + ' · ' + (oferta.horario?.nombre || 'Sin horario')"></p>
                            <p class="text-sm text-gray-500" x-text="(oferta.sucursal?.nombre || '-') + ' · ' + docenteNombre(oferta)"></p>
                            <p class="text-sm text-gray-500" x-text="'Horario: ' + (oferta.whatsapp_grupo_nombre || oferta.grupo_whatsapp?.nombre || 'Sin nombre configurado')"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="'/admin/asistencias?oferta=' + oferta.id" class="btn btn-outline btn-sm">Asistencias</a>
                            <a :href="'/admin/calificaciones'" class="btn btn-outline btn-sm">Calificaciones</a>
                        </div>
                    </div>

                    <div>
                        <label class="label">Link WhatsApp del período</label>
                        <input x-model="links[oferta.id]" type="text" class="input" placeholder="https://chat.whatsapp.com/...">
                        <p class="mt-1 text-xs text-gray-500">Este link es el vigente para la oferta en el período seleccionado.</p>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-400" x-text="links[oferta.id] ? 'Link configurado' : 'Sin link configurado' "></p>
                        <button @click="guardarWhatsappPeriodo(oferta)" :disabled="guardandoOfertaId === oferta.id" class="btn btn-primary btn-sm">
                            <span x-text="guardandoOfertaId === oferta.id ? 'Guardando...' : 'Guardar link'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@section('scripts')
<script>
function misGrupos() {
    return {
        loading: false,
        errorCarga: '',
        guardandoOfertaId: null,
        periodos: [],
        sucursales: [],
        niveles: [],
        ofertas: [],
        links: {},
        filtros: { periodo: '', sucursal: '', nivel: '' },

        async init() {
            await this.cargarCatalogos();
            this.preseleccionarPeriodo();
            await this.cargarOfertas();
        },

        docenteNombre(oferta) {
            if (!oferta?.docente) return 'Sin docente';
            return [oferta.docente.nombre, oferta.docente.apellido].filter(Boolean).join(' ');
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
            this.periodos = extract(pRes);
            this.sucursales = extract(sRes);
            this.niveles = extract(nRes);
        },

        preseleccionarPeriodo() {
            const activo = this.periodos.find(p => p.estado === 'activo');
            if (activo) this.filtros.periodo = activo.id;
        },

        async cargarOfertas() {
            this.loading = true;
            this.errorCarga = '';
            try {
                let url = '/api/v1/asistencias/ofertas-disponibles?';
                if (this.filtros.periodo) url += `periodo_academico_id=${this.filtros.periodo}&`;
                if (this.filtros.sucursal) url += `sucursal_id=${this.filtros.sucursal}&`;
                if (this.filtros.nivel) url += `nivel_academico_id=${this.filtros.nivel}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado !== 'A') throw new Error(data.mensaje || 'No fue posible cargar los horarios');
                this.ofertas = data.data || [];
                this.links = Object.fromEntries(this.ofertas.map(oferta => [oferta.id, oferta.whatsapp_link_periodo || '']));
            } catch (e) {
                this.ofertas = [];
                this.links = {};
                this.errorCarga = window.extractError(e, 'No fue posible cargar sus horarios.');
            } finally {
                this.loading = false;
            }
        },

        async guardarWhatsappPeriodo(oferta) {
            this.guardandoOfertaId = oferta.id;
            try {
                const { data } = await window.axios.post(`/api/v1/ofertas/academicas/${oferta.id}/whatsapp-periodo`, {
                    whatsapp_link_periodo: this.links[oferta.id] || null,
                }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    oferta.whatsapp_link_periodo = data.data?.whatsapp_link_periodo || null;
                    this.links[oferta.id] = oferta.whatsapp_link_periodo || '';
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje || 'Link actualizado', type: 'success' } }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'No se pudo guardar el link de WhatsApp'), type: 'error' } }));
            } finally {
                this.guardandoOfertaId = null;
            }
        },
    }
}
</script>
@endsection
