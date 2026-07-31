@extends('layouts.portal')
@section('title', 'Pago cancelado')
@section('content')
<div x-data>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center max-w-md mx-auto">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Pago Cancelado</h3>
        <p class="text-sm text-gray-500 mb-6">Ha cancelado el pago. Si desea intentar de nuevo, puede hacerlo desde Mis Pagos.</p>
        <a href="/estudiante/pagos" class="inline-block px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Volver a Mis Pagos</a>
    </div>
</div>
@endsection
