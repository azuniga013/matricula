@extends('layouts.portal')
@section('title', 'Procesando pago...')
@section('content')
<div x-data="retornoPayPal()" x-init="init()" x-cloak>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center max-w-md mx-auto">
        <template x-if="loading">
            <div>
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-brand-500/20 border-t-brand-500 mx-auto mb-4"></div>
                <p class="text-gray-500">Procesando su pago, por favor espere...</p>
            </div>
        </template>
        <template x-if="!loading && exito">
            <div>
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pago Aprobado</h3>
                <p class="text-sm text-gray-500 mb-1" x-text="'Código: ' + (codigo || '')"></p>
                <p class="text-sm text-gray-500 mb-6">Su pago ha sido procesado exitosamente.</p>
                <a href="/estudiante/pagos" class="inline-block px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Ir a Mis Pagos</a>
            </div>
        </template>
        <template x-if="!loading && !exito && error">
            <div>
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Error al procesar el pago</h3>
                <p class="text-sm text-red-600 mb-6" x-text="error"></p>
                <a href="/estudiante/pagos" class="inline-block px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Intentar de nuevo</a>
            </div>
        </template>
    </div>
</div>
@endsection
@section('scripts')
<script>
function retornoPayPal() {
    return {
        loading: true, exito: false, error: '', codigo: '',
        token() { return localStorage.getItem('estudiante_token'); },
        async init() {
            const params = new URLSearchParams(window.location.search);
            const token = this.token();
            if (!token) { window.location.href = '/estudiante/login'; return; }
            try {
                const { data } = await window.axios.post('/api/v1/estudiantes/pago-tarjeta/retorno', {
                    token: params.get('token'),
                    pago_id: params.get('pago_id'),
                }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.exito = true;
                    this.codigo = data.data.codigo;
                } else {
                    this.error = data.mensaje || 'Error al confirmar el pago';
                }
            } catch(e) {
                this.error = window.extractError(e, 'Error al conectar con el servidor');
            } finally { this.loading = false; }
        }
    };
}
</script>
@endsection
