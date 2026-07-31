@extends('layouts.portal')
@section('title', 'Mi Portal')
@section('content')
<div x-data="dashboard()" x-init="init()" x-cloak>
    {{-- Loading --}}
    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div class="space-y-6">
            {{-- Welcome --}}
            <div class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-xl p-6 text-white">
                <h2 class="text-xl font-bold">Bienvenido, <span x-text="portal?.estudiante?.nombre"></span></h2>
                <p class="text-brand-100 text-sm mt-1">Sucursal: <span x-text="portal?.estudiante?.sucursal"></span></p>
            </div>

            <template x-if="portal?.matriculas_pendientes?.length > 0">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <h3 class="font-semibold text-amber-900 mb-1">Tiene conceptos de pago pendientes</h3>
                            <p class="text-sm text-amber-800">Revise sus reservas activas antes de crear una nueva matrícula.</p>
                        </div>
                        <a href="/estudiante/pagos" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">Ir a mis pagos</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        <template x-for="m in portal.matriculas_pendientes" :key="m.id">
                            <div class="bg-white border border-amber-100 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900" x-text="m.codigo + ' · ' + (m.nivel || 'Matrícula')"></p>
                                        <p class="text-xs text-gray-500" x-text="m.horario ? m.horario + ' · ' + m.regimen : m.regimen"></p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="m.estado === 'reservada' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'" x-text="m.estado"></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-3" x-text="'Obligaciones pendientes: ' + m.obligaciones.length + ' · Total: ' + m.obligaciones.reduce((s, o) => s + Number(o.saldo || 0), 0).toFixed(2) + ' L.'"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="portal?.periodo_actual && portal?.calificaciones?.filter(c => c.periodo === portal.periodo_actual.nombre).length > 0 && portal?.calificaciones?.filter(c => c.periodo === portal.periodo_actual.nombre).every(c => c.estado !== 'pendiente')">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">i</div>
                        <div>
                            <h3 class="font-semibold text-blue-900">Calificaciones del período actual entregadas</h3>
                            <p class="text-sm text-blue-700 mt-1">Puede revisar su historial académico para ver su avance.</p>
                            <a href="/estudiante/historial" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:text-blue-800">
                                Ir al historial
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Nivel Actual --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Mi Nivel Actual</h3>
                    <span class="text-xs font-medium text-brand-700 bg-brand-50 px-2.5 py-1 rounded-full" x-text="portal?.nivel_actual?.periodo || portal?.periodo_actual?.nombre || 'Sin periodo'"></span>
                </div>
                <template x-if="portal?.nivel_actual">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4"><p class="text-xs text-gray-500 mb-1">Nivel</p><p class="font-semibold text-gray-900" x-text="portal.nivel_actual.nombre"></p><p class="text-xs text-gray-400 font-mono" x-text="portal.nivel_actual.codigo"></p></div>
                        <div class="bg-gray-50 rounded-lg p-4"><p class="text-xs text-gray-500 mb-1">Régimen</p><p class="font-semibold text-gray-900" x-text="portal.nivel_actual.regimen || '-'"></p></div>
                        <div class="bg-gray-50 rounded-lg p-4"><p class="text-xs text-gray-500 mb-1">Modalidad</p><p class="font-semibold text-gray-900" x-text="portal.nivel_actual.modalidad || '-'"></p></div>
                        <div class="bg-gray-50 rounded-lg p-4"><p class="text-xs text-gray-500 mb-1">Horario</p><p class="font-semibold text-gray-900" x-text="portal.nivel_actual.horario || '-'"></p></div>
                        <div class="bg-gray-50 rounded-lg p-4"><p class="text-xs text-gray-500 mb-1">Docente</p><p class="font-semibold text-gray-900" x-text="portal.nivel_actual.docente || '-'"></p></div>
                    </div>
                </template>
                <template x-if="!portal?.nivel_actual">
                    <p class="text-gray-400 text-sm">No tiene nivel actual en el período vigente.</p>
                </template>
            </div>

            {{-- Obligaciones Pendientes --}}
            <template x-if="portal?.obligaciones?.length > 0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pagos Pendientes</h3>
                    <div class="space-y-3">
                        <template x-for="o in portal.obligaciones" :key="o.id">
                            <div class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                                <div><p class="text-sm font-medium text-gray-900" x-text="o.nombre_cargo"></p><p class="text-xs text-gray-500">Vence: <span x-text="o.fecha_vencimiento"></span></p></div>
                                <div class="text-right"><p class="text-sm font-bold text-amber-700" x-text="parseFloat(o.saldo).toFixed(2) + ' L.'"></p><p class="text-xs text-gray-400" x-text="'Pagado: ' + parseFloat(o.monto_pagado).toFixed(2) + ' L.'"></p></div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4" x-show="true"><a href="/estudiante/comprobante" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">Subir comprobante de pago →</a></div>
                </div>
            </template>

            {{-- WhatsApp --}}
            <template x-if="portal?.whatsapp">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center"><svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></div>
                        <div>
                            <p class="font-semibold text-gray-900" x-text="'Grupo WhatsApp: ' + (portal.whatsapp.grupo || '')"></p>
                            <p class="text-sm text-gray-500" x-text="(portal.whatsapp.periodo || '') + ' · ' + (portal.whatsapp.nivel || '') + (portal.whatsapp.horario ? ' · ' + portal.whatsapp.horario : '')"></p>
                        </div>
                        <a :href="portal.whatsapp.link" target="_blank" class="ml-auto inline-flex items-center gap-1 bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition-colors">Unirme al grupo</a>
                    </div>
                </div>
            </template>

            <template x-if="portal?.pagos?.some(p => p.estado === 'solicita_link')">
                <div class="rounded-xl border border-sky-200 bg-gradient-to-r from-sky-50 to-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-11 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 22a10 10 0 1 0-10-10" /></svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-sky-900">Tiene un link de pago pendiente</h3>
                                <p class="text-sm text-sky-800 mt-1">Su solicitud fue enviada. Contabilidad debe cargar el enlace en Mis Pagos para que pueda completarlo.</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="/estudiante/pagos" class="inline-flex items-center justify-center px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-medium hover:bg-sky-700 transition-colors">Ir a Mis Pagos</a>
                            <a href="/estudiante/comprobante" class="inline-flex items-center justify-center px-4 py-2 bg-white text-sky-700 border border-sky-200 rounded-lg text-sm font-medium hover:bg-sky-50 transition-colors">Ver comprobantes</a>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Acciones Rápidas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="/estudiante/matricula" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center"><svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></div>
                    <div><p class="font-semibold text-gray-900">Matricularme</p><p class="text-xs text-gray-500">Ver ofertas disponibles</p></div>
                </a>
                <a href="/estudiante/pagos" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center"><svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg></div>
                    <div><p class="font-semibold text-gray-900">Mis Pagos</p><p class="text-xs text-gray-500">Consulta y comprobantes</p></div>
                </a>
            </div>
        </div>
    </template>
</div>
@endsection
@section('scripts')
<script>
function dashboard() {
    return { loading: true, portal: null, async loadData() {
        const token = localStorage.getItem('estudiante_token');
        if (!token) { window.location.href = '/estudiante/login'; return; }
        try {
            const { data } = await window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } });
            if (data.resultado === 'A') { this.portal = data.data; localStorage.setItem('estudiante_data', JSON.stringify(data.data.estudiante)); }
            else { window.location.href = '/estudiante/login'; }
        } catch(e) { if (e?.response?.status === 401) window.location.href = '/estudiante/login'; }
        finally { this.loading = false; }
    }, async init() { this.loadData(); this.pollingInterval = setInterval(() => this.loadData(), 30000); }};
}
</script>
@endsection
