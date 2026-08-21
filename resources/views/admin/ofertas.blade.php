@extends('layouts.admin')
@section('content')
<div x-data="ofertas()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Ofertas y Cupos</h1>
            <p class="page-subtitle">Gestión de ofertas académicas por período</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadOfertas()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                Actualizar
            </button>
            <button x-show="api.hasPermission('ofertas.academicas.crear')" @click="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva Oferta
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <label class="label">Período</label>
                    <select x-model="filtro.periodo" @change="loadOfertas()" class="input">
                        <option value="">Todos</option>
                        <template x-for="p in periodos" :key="p.id">
                            <option :value="p.id" x-text="p.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="filtro.sucursal" @change="loadOfertas()" class="input">
                        <option value="">Todas</option>
                        <template x-for="s in sucursales" :key="s.id">
                            <option :value="s.id" x-text="s.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Versión del Plan</label>
                    <select x-model="filtro.version_plan_estudio_id" @change="onVersionChange()" class="input">
                        <option value="">Todas</option>
                        <template x-for="v in versiones" :key="v.id">
                            <option :value="v.id" x-text="(v.plan_estudio?.nombre || '') + ' · V' + v.numero_version"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Nivel</label>
                    <select x-model="filtro.nivel_academico_id" @change="loadOfertas()" class="input">
                        <option value="">Todos</option>
                        <template x-for="n in nivelesFiltrados" :key="n.id">
                            <option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Estado</label>
                    <select x-model="filtro.estado" @change="loadOfertas()" class="input">
                        <option value="">Todos</option>
                        <option value="borrador">Borrador</option>
                        <option value="abierto">Abierto</option>
                        <option value="lleno">Lleno</option>
                        <option value="cerrado">Cerrado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    <template x-if="!loading">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nivel</th>
                            <th>Versión</th>
                            <th>Modalidad</th>
                            <th>Horario</th>
                            <th>Docente</th>
                            <th class="text-center">Cupos</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="o in ofertas" :key="o.id">
                            <tr>
                                <td class="font-mono text-xs font-semibold text-brand-600" x-text="o.codigo"></td>
                                <td class="font-medium" x-text="o.nivel_academico?.nombre || '-'"></td>
                                <td class="text-xs text-gray-500" x-text="o.nivel_academico?.version_plan_estudio?.plan_estudio?.nombre ? (o.nivel_academico.version_plan_estudio.plan_estudio.nombre + ' · V' + o.nivel_academico.version_plan_estudio.numero_version) : '-'"></td>
                                <td><span class="badge badge-info" x-text="o.modalidad?.nombre || '-'"></span></td>
                                <td x-text="o.horario?.nombre || '-'"></td>
                                <td x-text="o.docente ? o.docente.nombre + ' ' + o.docente.apellido : '-'"></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="font-semibold text-emerald-600" x-text="o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0))"></span>
                                        <span class="text-gray-400">/</span>
                                        <span x-text="o.cupo_maximo"></span>
                                    </div>
                                </td>
                                <td>
                                    <span :class="{
                                        'badge-success': o.estado === 'abierto',
                                        'badge-warning': o.estado === 'lleno',
                                        'badge-info': o.estado === 'borrador',
                                        'badge-neutral': o.estado === 'cerrado',
                                        'badge-danger': o.estado === 'cancelado'
                                    }" class="badge" x-text="o.estado"></span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button x-show="api.hasPermission('ofertas.academicas.modificar')" @click="editOferta(o)" class="btn btn-ghost btn-sm">Editar</button>
                                        <button x-show="api.hasPermission('ofertas.academicas.modificar') && o.estado === 'abierto'" @click="cambiarEstado(o, 'cerrado')" class="btn btn-ghost btn-sm text-amber-600">Cerrar</button>
                                        <button x-show="api.hasPermission('ofertas.academicas.modificar') && o.estado === 'borrador'" @click="cambiarEstado(o, 'abierto')" class="btn btn-ghost btn-sm text-emerald-600">Abrir</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    {{-- Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Editar Oferta' : 'Nueva Oferta'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form @submit.prevent="saveOferta()" class="p-6 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-3">
                        <label class="label">Código <span class="text-gray-400 text-xs font-normal">(dejar vacío para autogenerar)</span></label>
                        <input x-model="form.codigo" type="text" class="input" placeholder="Se autogenerará si se deja vacío">
                    </div>
                    <div>
                        <label class="label">Sucursal</label>
                        <select x-model="form.sucursal_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Período</label>
                        <select x-model="form.periodo_academico_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="p in periodos" :key="p.id"><option :value="p.id" x-text="p.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Versión del Plan</label>
                        <select x-model="form.version_plan_estudio_id" @change="onFormVersionChange()" class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="v in versiones" :key="v.id"><option :value="v.id" x-text="(v.plan_estudio?.nombre || '') + ' · V' + v.numero_version"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Nivel</label>
                        <select x-model="form.nivel_academico_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="n in nivelesForm" :key="n.id"><option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Modalidad de Atención</label>
                        <select x-model="form.modalidad_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="m in modalidades" :key="m.id"><option :value="m.id" x-text="m.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Horario</label>
                        <select x-model="form.horario_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="h in horarios" :key="h.id"><option :value="h.id" x-text="h.nombre + ' (' + h.hora_inicio + '-' + h.hora_fin + ')'"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Docente</label>
                        <select x-model="form.docente_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="d in docentes" :key="d.id"><option :value="d.id" x-text="d.nombre + ' ' + d.apellido"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Aula</label>
                        <select x-model="form.aula_id" required class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="a in aulas" :key="a.id"><option :value="a.id" x-text="a.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Cupo Máximo</label>
                        <input x-model.number="form.cupo_maximo" type="number" min="1" required class="input">
                    </div>
                    <div>
                        <label class="label">Plan de Cobro</label>
                        <select x-model="form.plan_cobro_id" class="input">
                            <option value="">Sin plan...</option>
                            <template x-for="pc in planesCobro" :key="pc.id"><option :value="pc.id" x-text="pc.codigo + ' · ' + pc.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Grupo WhatsApp</label>
                        <select x-model="form.grupo_whatsapp_id" class="input">
                            <option value="">Sin grupo...</option>
                            <template x-for="gw in gruposWhatsapp" :key="gw.id"><option :value="gw.id" x-text="gw.codigo + ' · ' + gw.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="form.estado" class="input">
                            <option value="borrador">Borrador</option>
                            <option value="abierto">Abierto</option>
                            <option value="lleno">Lleno</option>
                            <option value="cerrado">Cerrado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
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
function ofertas() {
    return {
        loading: true, showModal: false, editing: false, saving: false, error: '',
        ofertas: [], periodos: [], sucursales: [], niveles: [], versiones: [], modalidades: [], horarios: [], docentes: [], aulas: [], planesCobro: [], gruposWhatsapp: [],
        filtro: { periodo: '', sucursal: '', version_plan_estudio_id: '', nivel_academico_id: '', estado: '' },
        form: {}, editId: null,

        get nivelesFiltrados() {
            if (!this.filtro.version_plan_estudio_id) return this.niveles;
            return this.niveles.filter(n => String(n.version_plan_estudio_id) === String(this.filtro.version_plan_estudio_id));
        },

        get nivelesForm() {
            if (!this.form.version_plan_estudio_id) return this.niveles;
            return this.niveles.filter(n => String(n.version_plan_estudio_id) === String(this.form.version_plan_estudio_id));
        },

        onVersionChange() {
            this.filtro.nivel_academico_id = '';
            this.loadOfertas();
        },

        onFormVersionChange() {
            this.form.nivel_academico_id = '';
        },

        async init() {
            await this.loadCatalogs();
            await this.loadOfertas();
        },

        async loadCatalogs() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            const [p, s, n, v, m, ho, d, a, pc, gw] = await Promise.allSettled([
                window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                window.axios.get('/api/v1/catalogos-academicos/niveles-academicos', h),
                window.axios.get('/api/v1/catalogos-academicos/versiones-plan-estudio', h),
                window.axios.get('/api/v1/catalogos-academicos/modalidades?tipo=atencion', h),
                window.axios.get('/api/v1/catalogos-academicos/horarios', h),
                window.axios.get('/api/v1/catalogos-academicos/docentes', h),
                window.axios.get('/api/v1/catalogos-academicos/aulas', h),
                window.axios.get('/api/v1/catalogos-academicos/planes-cobro', h),
                window.axios.get('/api/v1/catalogos-academicos/grupos-whatsapp', h),
            ]);
            const extract = r => r.status === 'fulfilled' ? (r.value.data.data?.data || r.value.data.data || []) : [];
            this.periodos = extract(p); this.sucursales = extract(s); this.niveles = extract(n); this.versiones = extract(v);
            this.modalidades = extract(m); this.horarios = extract(ho); this.docentes = extract(d); this.aulas = extract(a); this.planesCobro = extract(pc); this.gruposWhatsapp = extract(gw);
            const activo = this.periodos.find(per => per.estado === 'activo');
            if (activo) this.filtro.periodo = activo.id;
        },

        async loadOfertas() {
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                let url = '/api/v1/ofertas/academicas?';
                if (this.filtro.periodo) url += `periodo_academico_id=${this.filtro.periodo}&`;
                if (this.filtro.sucursal) url += `sucursal_id=${this.filtro.sucursal}&`;
                if (this.filtro.version_plan_estudio_id) url += `version_plan_estudio_id=${this.filtro.version_plan_estudio_id}&`;
                if (this.filtro.nivel_academico_id) url += `nivel_academico_id=${this.filtro.nivel_academico_id}&`;
                if (this.filtro.estado) url += `estado=${this.filtro.estado}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.ofertas = data.data.data || data.data || [];
                }
            } catch (e) { console.error(e); } finally { this.loading = false; }
        },

        async openModal() {
            this.editing = false; this.editId = null; this.error = '';
            this.form = { codigo: '', sucursal_id: '', periodo_academico_id: '', version_plan_estudio_id: '', nivel_academico_id: '', modalidad_id: '', horario_id: '', docente_id: '', aula_id: '', plan_cobro_id: '', grupo_whatsapp_id: '', cupo_maximo: 25, estado: 'borrador' };
            this.showModal = false;
            await this.$nextTick();
            this.showModal = true;
        },

        async editOferta(o) {
            this.editing = true; this.editId = o.id; this.error = '';
            this.form = {
                codigo: o.codigo, sucursal_id: o.sucursal_id, periodo_academico_id: o.periodo_academico_id,
                version_plan_estudio_id: o.nivel_academico?.version_plan_estudio_id || '',
                nivel_academico_id: o.nivel_academico_id, modalidad_id: o.modalidad_id, horario_id: o.horario_id,
                docente_id: o.docente_id, aula_id: o.aula_id, cupo_maximo: o.cupo_maximo, plan_cobro_id: o.plan_cobro_id || '', grupo_whatsapp_id: o.grupo_whatsapp_id || '',
                estado: o.estado,
            };
            this.showModal = false;
            await this.$nextTick();
            this.showModal = true;
        },

        async saveOferta() {
            this.saving = true; this.error = '';
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editing ? `/api/v1/ofertas/academicas/${this.editId}` : '/api/v1/ofertas/academicas';
                const { data } = await window.api.actualizar(url, this.form, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.showModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Oferta guardada', type: 'success' } }));
                    await this.loadOfertas();
                } else { this.error = data.mensaje || 'Error'; }
            } catch (e) { this.error = window.extractError(e, 'Error al guardar'); } finally { this.saving = false; }
        },

        async cambiarEstado(o, nuevoEstado) {
            if (nuevoEstado === 'cerrado' && !confirm('¿Está seguro de cerrar esta oferta? Los estudiantes no podrán matricularse en este grupo.')) return;
            if (nuevoEstado === 'cancelado' && !confirm('¿Está seguro de cancelar esta oferta? Se liberarán todos los cupos reservados.')) return;
            try {
                const token = localStorage.getItem('auth_token');
                await window.axios.post(`/api/v1/ofertas/academicas/${o.id}`, { estado: nuevoEstado }, { headers: { Authorization: `Bearer ${token}` } });
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Estado actualizado', type: 'success' } }));
                await this.loadOfertas();
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al cambiar estado', type: 'error' } }));
            }
        }
    }
}
</script>
@endsection
