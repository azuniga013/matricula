@extends('layouts.admin')
@section('content')
<div x-data="monitorCupos()" x-init="init()">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Monitor de Cupos</h1>
            <p class="page-subtitle">Disponibilidad de grupos académicos en tiempo real</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Auto-refresh indicator --}}
            <div class="hidden sm:flex items-center gap-2 text-xs text-gray-400">
                <template x-if="autoRefresh">
                    <span class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Actualiza en <span class="font-semibold text-gray-600" x-text="countdown + 's'"></span>
                    </span>
                </template>
                <template x-if="!autoRefresh">
                    <span>Actualización automática pausada</span>
                </template>
            </div>
            <button @click="toggleAutoRefresh()" class="btn btn-outline btn-sm" x-text="autoRefresh ? 'Pausar' : 'Reanudar'"></button>
            <button @click="loadData()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                Actualizar
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">Período Académico</label>
                    <select x-model="filtros.periodo_academico_id" @change="loadData()" class="input">
                        <option value="">Todos los períodos</option>
                        <template x-for="p in periodos" :key="p.id">
                            <option :value="p.id" x-text="p.codigo + ' · ' + p.nombre" :selected="filtros.periodo_academico_id == p.id"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="filtros.sucursal_id" @change="loadData()" class="input">
                        <option value="">Todas las sucursales</option>
                        <template x-for="s in sucursales" :key="s.id">
                            <option :value="s.id" x-text="s.codigo + ' · ' + s.nombre"></option>
                        </template>
                    </select>
                </div>
                <div class="flex items-end">
                    <div class="text-xs text-gray-400" x-text="'Última actualización: ' + ultimaActualizacion"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-brand-50 text-brand-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
            </div>
            <div>
                <p class="stat-value" x-text="resumen.ofertas"></p>
                <p class="stat-label">Grupos Académicos</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-50 text-emerald-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            </div>
            <div>
                <p class="stat-value" x-text="resumen.matriculados"></p>
                <p class="stat-label">Matriculados</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-50 text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div>
                <p class="stat-value" x-text="resumen.reservados"></p>
                <p class="stat-label">Cupos Reservados</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-50 text-purple-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </div>
            <div>
                <p class="stat-value" x-text="resumen.disponibles"></p>
                <p class="stat-label">Cupos Disponibles</p>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-4 text-xs text-gray-500">
        <span class="font-semibold text-gray-600">Estados:</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Matrícula abierta</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Acepta cambios de horario</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Pocos cupos</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Sin cupos / cerrada</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> Cancelada / borrador</span>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Table --}}
    <template x-if="!loading">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th>Plan de Estudio</th>
                            <th>Nivel</th>
                            <th>Modalidad</th>
                            <th>Horario</th>
                            <th>Docente</th>
                            <th class="text-center">Cupo Máx.</th>
                            <th class="text-center">Matriculados</th>
                            <th class="text-center">Reservados</th>
                            <th class="text-center">Disponibles</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="o in ofertas" :key="o.codigo">
                            <tr>
                                <td class="text-gray-500 text-xs" x-text="o.sucursal?.nombre || '-'"></td>
                                <td class="text-gray-500 text-xs" x-text="planNombre(o)"></td>
                                <td>
                                    <div class="font-medium" x-text="o.nivel_academico?.codigo || '-'"></div>
                                    <div class="text-xs text-gray-400" x-text="o.nivel_academico?.nombre || ''"></div>
                                </td>
                                <td><span class="badge badge-neutral" x-text="o.modalidad?.nombre || '-'"></span></td>
                                <td class="text-xs" x-text="horarioTexto(o)"></td>
                                <td class="text-xs" x-text="o.docente ? (o.docente.nombre + ' ' + o.docente.apellido) : '-'"></td>
                                <td class="text-center" x-text="o.cupo_maximo"></td>
                                <td class="text-center font-medium text-emerald-600" x-text="o.cupos_matriculados"></td>
                                <td class="text-center font-medium text-amber-600" x-text="o.cupos_reservados"></td>
                                <td class="text-center">
                                    <span class="text-base font-bold" :class="o.cupos_disponibles > 0 ? 'text-brand-600' : 'text-red-500'" x-text="o.cupos_disponibles"></span>
                                </td>
                                <td>
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="colorDot(o.color_estado)"></span>
                                        <span class="text-xs" x-text="estadoTexto(o)"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="ofertas.length === 0">
                            <tr>
                                <td colspan="11" class="text-center py-10 text-gray-400 text-sm">No hay grupos académicos para los filtros seleccionados</td>
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
function monitorCupos() {
    return {
        loading: true,
        ofertas: [],
        periodos: [],
        sucursales: [],
        filtros: { periodo_academico_id: '', sucursal_id: '' },
        resumen: { ofertas: 0, matriculados: 0, reservados: 0, disponibles: 0 },
        refrescoSegundos: 300,
        countdown: 300,
        autoRefresh: true,
        timer: null,
        ultimaActualizacion: '-',

        async init() {
            await this.loadCatalogos();
            this.preseleccionarPeriodo();
            await this.loadData();
            this.iniciarTimer();
        },

        async loadCatalogos() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            try {
                const [pRes, sRes] = await Promise.all([
                    window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                    window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                ]);
                if (pRes.data.resultado === 'A') this.periodos = pRes.data.data.data || pRes.data.data || [];
                if (sRes.data.resultado === 'A') this.sucursales = sRes.data.data.data || sRes.data.data || [];
            } catch (e) { /* catálogos no disponibles */ }
        },

        preseleccionarPeriodo() {
            const activo = this.periodos.find(p => p.estado === 'activo');
            if (activo) this.filtros.periodo_academico_id = activo.id;
        },

        async loadData() {
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                let url = '/api/v1/ofertas/monitor?';
                if (this.filtros.periodo_academico_id) url += `periodo_academico_id=${this.filtros.periodo_academico_id}&`;
                if (this.filtros.sucursal_id) url += `sucursal_id=${this.filtros.sucursal_id}`;

                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.ofertas = data.data || [];
                    this.refrescoSegundos = data.meta?.refresco_segundos || 300;
                    this.calcularResumen();
                    this.ultimaActualizacion = new Date().toLocaleTimeString('es-HN');
                    this.countdown = this.refrescoSegundos;
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al cargar el monitor', type: 'error' } }));
            } finally {
                this.loading = false;
            }
        },

        calcularResumen() {
            this.resumen = {
                ofertas: this.ofertas.length,
                matriculados: this.ofertas.reduce((a, o) => a + (o.cupos_matriculados || 0), 0),
                reservados: this.ofertas.reduce((a, o) => a + (o.cupos_reservados || 0), 0),
                disponibles: this.ofertas.reduce((a, o) => a + Math.max(0, o.cupos_disponibles || 0), 0),
            };
        },

        iniciarTimer() {
            this.timer = setInterval(() => {
                if (!this.autoRefresh) return;
                this.countdown--;
                if (this.countdown <= 0) {
                    this.loadData();
                }
            }, 1000);
        },

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) this.countdown = this.refrescoSegundos;
        },

        planNombre(o) {
            const plan = o.nivel_academico?.version_plan_estudio?.plan_estudio;
            return plan ? (plan.codigo ? `${plan.codigo} · ${plan.nombre}` : plan.nombre) : '-';
        },

        horarioTexto(o) {
            if (!o.horario) return '-';
            const rango = (o.horario.hora_inicio && o.horario.hora_fin)
                ? `${String(o.horario.hora_inicio).substring(0,5)} - ${String(o.horario.hora_fin).substring(0,5)}`
                : '';
            return (o.horario.nombre || '') + (rango ? ' · ' + rango : '');
        },

        colorDot(color) {
            return {
                verde: 'bg-emerald-500',
                azul: 'bg-blue-500',
                amarillo: 'bg-amber-500',
                rojo: 'bg-red-500',
                gris: 'bg-gray-400',
            }[color] || 'bg-gray-400';
        },

        estadoTexto(o) {
            if (o.estado === 'abierto' && o.acepta_cambios_horario) return 'Abierta · acepta cambios';
            const estados = { abierto: 'Abierta', borrador: 'Borrador', lleno: 'Lleno', cerrado: 'Cerrada', cancelado: 'Cancelada' };
            return estados[o.estado] || o.estado;
        },
    }
}
</script>
@endsection
