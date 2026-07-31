@extends('layouts.portal')
@section('title', 'Mis Certificados')
@section('content')
<div x-data="certificadosView()" x-init="load()" x-cloak>
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Mis Certificados</h2>
            <p class="text-sm text-gray-500">Descargue sus certificados en PDF o imagen y valide cada documento con QR.</p>
        </div>
        <a href="/estudiante/historial" class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-800">
            Ver historial académico
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
        </a>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    <template x-if="!loading && certificados.length === 0">
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <p class="text-sm text-gray-500">Aún no tiene certificados emitidos.</p>
        </div>
    </template>

    <template x-if="!loading && certificados.length > 0">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <template x-for="cert in certificados" :key="cert.id">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-brand-600 font-semibold">{{ 'Cursos San Vicente de Paúl' }}</p>
                            <h3 class="mt-2 text-lg font-bold text-gray-900" x-text="cert.nivel || 'Certificado académico'"></h3>
                            <p class="text-sm text-gray-500" x-text="(cert.periodo || '-') + ' · ' + (cert.sucursal || '-')"></p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700" x-text="cert.estado === 'emitido' ? 'Emitido' : cert.estado"></span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Código</p>
                            <p class="font-mono font-semibold text-gray-900" x-text="cert.codigo"></p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Emitido</p>
                            <p class="font-semibold text-gray-900" x-text="cert.emitido_en || '-'"></p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Nota final</p>
                            <p class="font-semibold text-gray-900" x-text="cert.nota_final || '-'"></p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">Verificación</p>
                            <p class="font-mono text-xs font-semibold text-gray-900 break-all" x-text="cert.codigo_verificacion"></p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a :href="cert.vista_url" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">Ver certificado</a>
                        <a :href="cert.pdf_url" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Descargar PDF</a>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
@endsection
@section('scripts')
<script>
function certificadosView() {
    return {
        loading: true,
        certificados: [],
        async load() {
            try {
                const token = localStorage.getItem('estudiante_token');
                const { data } = await window.axios.get('/api/v1/estudiantes/mis-certificados', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') this.certificados = data.data || [];
            } catch (e) {
                this.certificados = [];
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
