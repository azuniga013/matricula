@extends('layouts.admin')
@section('content')
<div x-data="dashboard()" x-init="init()">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Resumen general del sistema</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadData()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                Actualizar
            </button>
        </div>
    </div>

    {{-- Loading State --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    <template x-if="!loading">
        <div class="space-y-6">
            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Matriculados --}}
                <div class="stat-card group hover:shadow-md transition-shadow">
                    <div class="stat-icon bg-brand-50 text-brand-600 group-hover:bg-brand-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                    </div>
                    <div>
                        <p class="stat-value" x-text="stats.matriculados"></p>
                        <p class="stat-label">Matriculados</p>
                    </div>
                </div>

                {{-- Ofertas Abiertas --}}
                <div class="stat-card group hover:shadow-md transition-shadow">
                    <div class="stat-icon bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
                    </div>
                    <div>
                        <p class="stat-value" x-text="stats.ofertasAbiertas"></p>
                        <p class="stat-label">Ofertas Abiertas</p>
                    </div>
                </div>

                {{-- Pagos Pendientes --}}
                <div class="stat-card group hover:shadow-md transition-shadow">
                    <div class="stat-icon bg-amber-50 text-amber-600 group-hover:bg-amber-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <div>
                        <p class="stat-value" x-text="stats.pagosPendientes"></p>
                        <p class="stat-label">Pagos Pendientes</p>
                    </div>
                </div>

                {{-- Ingresos del Mes --}}
                <div class="stat-card group hover:shadow-md transition-shadow">
                    <div class="stat-icon bg-purple-50 text-purple-600 group-hover:bg-purple-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                    </div>
                    <div>
                        <p class="stat-value" x-text="'L ' + formatNumber(stats.ingresosMes)"></p>
                        <p class="stat-label">Ingresos del Mes</p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions + Recent Activity --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Quick Actions --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-gray-900">Acciones Rápidas</h3>
                    </div>
                    <div class="card-body space-y-2">
                        <a href="/admin/matriculas" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition group">
                            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center group-hover:bg-brand-100 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Nueva Matrícula</p>
                                <p class="text-xs text-gray-500">Registrar estudiante en un grupo</p>
                            </div>
                        </a>
                        <a href="/admin/pagos" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition group">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Registrar Pago</p>
                                <p class="text-xs text-gray-500">Aprobar o registrar comprobante</p>
                            </div>
                        </a>
                        <a href="/admin/estudiantes" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition group">
                            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-100 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Buscar Estudiante</p>
                                <p class="text-xs text-gray-500">Consultar ficha del alumno</p>
                            </div>
                        </a>
                        <a href="/admin/reportes" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition group">
                            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-100 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Ver Reportes</p>
                                <p class="text-xs text-gray-500">Reportes académicos y financieros</p>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Monitor de Cupos (mini) --}}
                <div class="card lg:col-span-2">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Monitor de Cupos</h3>
                        <a href="/admin/ofertas" class="text-xs text-brand-600 hover:text-brand-700 font-medium">Ver todo →</a>
                    </div>
                    <div class="card-body p-0">
                        <template x-if="stats.cupos.length === 0">
                            <div class="px-6 py-10 text-center">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
                                <p class="text-sm text-gray-400">No hay ofertas activas</p>
                            </div>
                        </template>
                        <template x-if="stats.cupos.length > 0">
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Nivel</th>
                                            <th>Horario</th>
                                            <th>Docente</th>
                                            <th class="text-center">Cupo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="c in stats.cupos" :key="c.codigo">
                                            <tr>
                                                <td class="font-medium" x-text="c.nivel"></td>
                                                <td x-text="c.horario"></td>
                                                <td x-text="c.docente"></td>
                                                <td class="text-center">
                                                    <span class="font-semibold" x-text="c.disponibles"></span>
                                                    <span class="text-gray-400" x-text="' / ' + c.maximo"></span>
                                                </td>
                                                <td>
                                                    <span :class="c.disponibles > 5 ? 'badge-success' : c.disponibles > 0 ? 'badge-warning' : 'badge-danger'" class="badge" x-text="c.disponibles > 0 ? 'Disponible' : 'Lleno'"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Periodo + Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Periodo Activo --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-gray-900">Período Activo</h3>
                    </div>
                    <div class="card-body">
                        <template x-if="stats.periodo">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900" x-text="stats.periodo.nombre"></p>
                                        <p class="text-xs text-gray-500" x-text="stats.periodo.codigo"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600">
                                    <span>Inicio: <strong x-text="formatearFecha(stats.periodo.fecha_inicio)"></strong></span>
                                    <span>Fin: <strong x-text="formatearFecha(stats.periodo.fecha_fin)"></strong></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!stats.periodo">
                            <p class="text-sm text-gray-400">No hay período activo</p>
                        </template>
                    </div>
                </div>

                {{-- Sucursales --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-gray-900">Sucursales</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <template x-for="s in stats.sucursales" :key="s.codigo">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-xs font-bold" x-text="s.codigo.substring(0,2)"></div>
                                        <span class="text-sm font-medium text-gray-900" x-text="s.nombre"></span>
                                    </div>
                                    <span class="badge badge-success">Activa</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@endsection

@section('scripts')
<script>
function dashboard() {
    return {
        loading: true,
        stats: {
            matriculados: 0,
            ofertasAbiertas: 0,
            pagosPendientes: 0,
            ingresosMes: 0,
            cupos: [],
            periodo: null,
            sucursales: []
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                const headers = { Authorization: `Bearer ${token}` };

                const [matRes, ofertasRes, pagosRes, periodosRes, sucRes] = await Promise.allSettled([
                    window.axios.get('/api/v1/matriculas', { headers }),
                    window.axios.get('/api/v1/ofertas/academicas', { headers }),
                    window.axios.get('/api/v1/pagos?estado=pendiente', { headers }),
                    window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', { headers }),
                    window.axios.get('/api/v1/catalogos-academicos/sucursales', { headers }),
                ]);

                if (matRes.status === 'fulfilled' && matRes.value.data.resultado === 'A') {
                    this.stats.matriculados = matRes.value.data.data.total || matRes.value.data.data.length || 0;
                }
                if (ofertasRes.status === 'fulfilled' && ofertasRes.value.data.resultado === 'A') {
                    const ofertas = ofertasRes.value.data.data.data || ofertasRes.value.data.data || [];
                    this.stats.ofertasAbiertas = ofertas.filter(o => o.estado === 'abierto').length;
                    this.stats.cupos = ofertas.filter(o => o.estado === 'abierto').slice(0, 5).map(o => ({
                        codigo: o.codigo,
                        nivel: o.nivel_academico?.nombre || o.nivel?.nombre || '-',
                        horario: o.horario?.nombre || '-',
                        docente: o.docente ? o.docente.nombre + ' ' + o.docente.apellido : '-',
                        maximo: o.cupo_maximo,
                        disponibles: o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados || 0) - (o.cupos_reservados || 0))
                    }));
                }
                if (pagosRes.status === 'fulfilled' && pagosRes.value.data.resultado === 'A') {
                    const pagos = pagosRes.value.data.data;
                    this.stats.pagosPendientes = pagos.total || pagos.length || 0;
                }
                if (periodosRes.status === 'fulfilled' && periodosRes.value.data.resultado === 'A') {
                    const periodos = periodosRes.value.data.data.data || periodosRes.value.data.data || [];
                    this.stats.periodo = periodos.find(p => p.estado === 'activo') || periodos[0] || null;
                }
                if (sucRes.status === 'fulfilled' && sucRes.value.data.resultado === 'A') {
                    this.stats.sucursales = sucRes.value.data.data.data || sucRes.value.data.data || [];
                }
            } catch (e) {
                console.error('Dashboard error:', e);
            } finally {
                this.loading = false;
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
        },

        formatearFecha(fecha) {
            if (!fecha) return '-';
            return window.formatDateLocal(fecha);
        }
    }
}
</script>
@endsection
