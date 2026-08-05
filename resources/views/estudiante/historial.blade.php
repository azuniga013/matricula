@extends('layouts.portal')
@section('title', 'Historial Académico')
@section('content')
<div x-data="historialView()" x-init="load()" x-cloak>
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Historial Académico</h2>
            <p class="text-sm text-gray-500">Últimas calificaciones y avance académico</p>
        </div>
        <a href="/estudiante/certificados" class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-800">
            Ir a mis certificados
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
        </a>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    <template x-if="!loading">
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Promedio</p>
                    <p class="text-2xl font-bold text-brand-700" x-text="resumen.promedio"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Aprobadas</p>
                    <p class="text-2xl font-bold text-green-700" x-text="resumen.aprobadas"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Reprobadas</p>
                    <p class="text-2xl font-bold text-red-700" x-text="resumen.reprobadas"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Pendientes</p>
                    <p class="text-2xl font-bold text-amber-700" x-text="resumen.pendientes"></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <template x-if="calificaciones.length === 0">
                    <div class="p-8 text-center text-gray-400 text-sm">Aún no tiene calificaciones registradas.</div>
                </template>
                <template x-if="calificaciones.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Periodo</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Nivel</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Nota</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Faltas</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                                <th class="text-right px-4 py-3 font-medium text-gray-600">Certificado</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="c in calificaciones" :key="c.id">
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500" x-text="c.periodo || '-'"></td>
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="c.nivel || '-'"></td>
                                        <td class="px-4 py-3 font-semibold" :class="c.estado === 'aprobado' ? 'text-green-700' : (c.estado === 'reprobado' ? 'text-red-600' : 'text-gray-500')" x-text="c.nota_final ?? '-' "></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="c.faltas ?? '-'"></td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="c.estado === 'aprobado' ? 'bg-green-100 text-green-700' : (c.estado === 'reprobado' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')" x-text="c.estado === 'aprobado' ? 'Aprobada' : (c.estado === 'reprobado' ? 'Reprobada' : 'Pendiente')"></span></td>
                                        <td class="px-4 py-3 text-right">
                                            <button x-show="c.estado === 'aprobado'" @click="emitirCertificado(c)" :disabled="emitiendoId === c.id" class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 5.25-4.5 9.75-9 9.75S3 17.25 3 12 7.5 2.25 12 2.25 21 6.75 21 12Z" /></svg>
                                                    <span x-text="emitiendoId === c.id ? 'Generando...' : 'Certificado'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
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

function historialView() {
    return {
        loading: true,
        calificaciones: [],
        resumen: { promedio: '-', aprobadas: 0, reprobadas: 0, pendientes: 0 },
        emitiendoId: null,
        async load() {
            const token = localStorage.getItem('estudiante_token');
            if (!token) { window.location.href = '/estudiante/login'; return; }
            try {
                const { data } = await window.axios.get('/api/v1/estudiantes/mis-calificaciones', { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.calificaciones = data.data || [];
                    const notas = this.calificaciones.map(c => Number(c.nota_final)).filter(n => !Number.isNaN(n));
                    this.resumen = {
                        promedio: notas.length ? fmtMonto(notas.reduce((s, n) => s + n, 0) / notas.length) : '-',
                        aprobadas: this.calificaciones.filter(c => c.estado === 'aprobado').length,
                        reprobadas: this.calificaciones.filter(c => c.estado === 'reprobado').length,
                        pendientes: this.calificaciones.filter(c => c.estado === 'pendiente').length,
                    };
                }
            } catch(e) {
                if (e.response?.status === 401) window.location.href = '/estudiante/login';
            } finally {
                this.loading = false;
            }
        },
        async emitirCertificado(calificacion) {
            this.emitiendoId = calificacion.id;
            try {
                const token = localStorage.getItem('estudiante_token');
                const { data } = await window.axios.post('/api/v1/estudiantes/certificados/electronicos', {
                    historial_academico_id: calificacion.id,
                }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    const cert = data.data;
                    const url = cert.pdf_url || `/certificados/${cert.token_validacion}/pdf`;
                    window.open(url, '_blank');
                }
            } catch (e) {
                alert(window.extractError ? window.extractError(e, 'No se pudo generar el certificado') : 'No se pudo generar el certificado');
            } finally {
                this.emitiendoId = null;
            }
        }
    };
}
</script>
@endsection
