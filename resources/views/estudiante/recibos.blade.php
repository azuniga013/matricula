@extends('layouts.portal')
@section('title', 'Mis Recibos')
@section('content')
<div x-data="recibosView()" x-init="loadRecibos()" x-cloak>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Mis Recibos</h2>
        <p class="text-sm text-gray-500">Recibos de pago emitidos</p>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Recibos emitidos</p>
                    <p class="text-2xl font-bold text-brand-700" x-text="recibos.length"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Emitidos</p>
                    <p class="text-2xl font-bold text-green-700" x-text="recibos.filter(r => r.estado === 'emitido').length"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Anulados</p>
                    <p class="text-2xl font-bold text-red-700" x-text="recibos.filter(r => r.estado === 'anulado').length"></p>
                </div>
            </div>

            <template x-if="recibos.length === 0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <p class="text-gray-400">No tiene recibos emitidos.</p>
                </div>
            </template>

            <template x-if="recibos.length > 0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">N° Recibo</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Código Pago</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Fecha</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Reimpresiones</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Concepto</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Hora</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Monto</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Método</th>
                                <th class="text-right px-4 py-3 font-medium text-gray-600">Detalle</th>
                                <th class="text-right px-4 py-3 font-medium text-gray-600">Acción</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="r in recibos" :key="r.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-mono font-medium text-gray-900" x-text="r.numero_recibo"></td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600" x-text="r.codigo_pago || '-' "></td>
                                        <td class="px-4 py-3 text-gray-500"><span x-text="r.fecha"></span></td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700" x-text="r.estado === 'emitido' ? 'Emitido' : r.estado"></span></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="r.veces_reimpreso > 0 ? r.veces_reimpreso : '0'"></td>
                                        <td class="px-4 py-3 text-gray-700" x-text="r.concepto_origen || '-'"></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="r.hora || '-'"></td>
                                        <td class="px-4 py-3 font-semibold text-gray-900" x-text="fmtMonto(r.monto_total) + ' L.'"></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="r.metodo || '-'"></td>
                                        <td class="px-4 py-3 text-right">
                                            <button @click="verDetalle(r)" class="text-xs font-medium text-brand-600 hover:text-brand-700">Ver</button>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="descargarPdf(r)" class="text-brand-600 hover:text-brand-700 text-xs font-medium">PDF ↓</button>
                                                <span class="text-gray-300">|</span>
                                                <button @click="imprimir(r)" class="text-gray-500 hover:text-gray-700 text-xs font-medium">Imprimir</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <template x-if="showDetalle && reciboDetalle">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showDetalle = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalle del Recibo</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-400">N° Recibo</span><span class="font-mono font-medium" x-text="reciboDetalle.numero_recibo"></span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Código Pago</span><span class="font-mono font-medium text-brand-600" x-text="reciboDetalle.codigo_pago || '-'"></span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Concepto</span><span x-text="reciboDetalle.concepto_origen || '-' "></span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Monto</span><span class="font-semibold" x-text="fmtMonto(reciboDetalle.monto_total) + ' L.'"></span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Fecha</span><span x-text="reciboDetalle.fecha || '-' "></span></div>
                    <div class="flex justify-between"><span class="text-gray-400">Hora</span><span x-text="reciboDetalle.hora || '-' "></span></div>
                </div>
                <div class="flex justify-end mt-6">
                    <button @click="showDetalle = false" class="btn btn-outline">Cerrar</button>
                </div>
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

function recibosView() {
    return { loading: true, recibos: [], showDetalle: false, reciboDetalle: null,
        async loadRecibos() {
            const token = localStorage.getItem('estudiante_token');
            if (!token) { window.location.href = '/estudiante/login'; return; }
            try {
                const { data } = await window.axios.get('/api/v1/estudiantes/mis-recibos', { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') this.recibos = data.data;
            } catch(e) { if (e.response?.status === 401) window.location.href = '/estudiante/login'; }
            finally { this.loading = false; }
            if (!this.pollingInterval) this.pollingInterval = setInterval(() => this.loadRecibos(), 30000);
        },
        imprimir(r) {
            const token = localStorage.getItem('estudiante_token');
            if (!token) return;
            window.open(`/estudiante/recibos/${r.id}/imprimir?token=${token}&auto=1`, '_blank');
        },
        descargarPdf(r) {
            const token = localStorage.getItem('estudiante_token');
            if (!token) return;
            window.open(`/estudiante/recibos/${r.id}/imprimir?token=${token}&pdf=1`, '_blank');
        },
        verDetalle(r) {
            this.reciboDetalle = r;
            this.showDetalle = true;
        }
    };
}
</script>
@endsection
