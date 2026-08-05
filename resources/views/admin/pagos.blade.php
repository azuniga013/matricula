@extends('layouts.admin')
@section('content')
    <div x-data="pagos()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pagos</h1>
            <p class="page-subtitle">Gestión de pagos, comprobantes y aprobaciones</p>
        </div>
        <button x-show="api.hasPermission('pagos.crear') && flujo.habilita_aprobacion_pago" @click="openModal()" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Registrar Pago
        </button>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1 overflow-x-auto">
            <button @click="cambiarTab('pendientes')" :class="tab === 'pendientes' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Reservados <span x-show="pagosPendientes.length > 0" class="ml-1 badge badge-warning" x-text="pagosPendientes.length"></span></button>
            <button @click="cambiarTab('en_revision')" :class="tab === 'en_revision' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">En Revisión <span x-show="pagosEnRevision.length > 0" class="ml-1 badge badge-info" x-text="pagosEnRevision.length"></span></button>
            <button @click="cambiarTab('aprobados')" :class="tab === 'aprobados' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Aprobados</button>
            <button @click="cambiarTab('rechazados')" :class="tab === 'rechazados' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Rechazados</button>
            <button @click="cambiarTab('recibos')" :class="tab === 'recibos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Recibos de Caja</button>
            <button @click="cambiarTab('solicita_link')" :class="tab === 'solicita_link' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Solicitudes de Link <span x-show="pagosSolicitaLink.length > 0" class="ml-1 badge badge-sky" x-text="pagosSolicitaLink.length"></span></button>
            <button @click="cambiarTab('enlaces')" :class="tab === 'enlaces' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Enlaces de Pago</button>
        </nav>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div>
            {{-- Pendientes --}}
            <div x-show="tab === 'pendientes'" class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Concepto</th><th>Monto</th><th>Comentario</th><th>Referencia</th><th>Fecha</th><th>Comprobante</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="p in pagosPendientes" :key="p.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                    <td class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="p.estudiante?.codigo"></span> <span x-text="(p.estudiante?.nombre || '') + ' ' + (p.estudiante?.apellido || '')"></span></td>
                                    <td><span class="badge badge-info" x-text="p.concepto_pago?.codigo"></span> <span class="text-gray-500 text-xs" x-text="p.concepto_pago?.nombre"></span></td>
                                    <td class="font-semibold">L <span x-text="fmtMonto(p.monto)"></span></td>
                                    <td class="text-xs text-gray-500 max-w-xs truncate" x-text="p.observaciones || '-'"></td>
                                    <td class="text-xs text-gray-500" x-text="p.referencia_externa || '-'"></td>
                                    <td class="text-gray-500 text-xs" x-text="fmtFecha(p.creado_en)"></td>
                                    <td>
                                        <template x-if="p.comprobantes && p.comprobantes.length > 0">
                                            <button @click="verComprobante(p)" class="btn btn-ghost btn-sm text-brand-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                                Ver
                                            </button>
                                        </template>
                                        <template x-if="!p.comprobantes || p.comprobantes.length === 0">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button @click="verDetalle(p)" class="btn btn-ghost btn-sm text-brand-600">Ver</button>
                                            <button x-show="api.hasPermission('pagos.aprobar')" @click="aprobar(p)" class="btn btn-ghost btn-sm text-emerald-600">Aprobar</button>
                                            <button x-show="api.hasPermission('pagos.aprobar')" @click="abrirRechazo(p)" class="btn btn-ghost btn-sm text-red-600">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="pagosPendientes.length === 0">
                                <tr><td colspan="9" class="text-center py-10 text-gray-400 text-sm">No hay pagos reservados</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'solicita_link'" class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Concepto</th><th>Monto</th><th>Estado</th><th>Link</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="p in pagosSolicitaLink" :key="p.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                    <td x-text="(p.estudiante?.nombre || '') + ' ' + (p.estudiante?.apellido || '')"></td>
                                    <td x-text="p.concepto_pago?.nombre || '-' "></td>
                                    <td class="font-semibold">L <span x-text="fmtMonto(p.monto)"></span></td>
                                    <td>
                                        <span class="badge" :class="p.link_pago_url ? 'badge-success' : (p.link_pago_estado === 'vencido' ? 'badge-danger' : 'badge-warning')"
                                            x-text="p.link_pago_url ? 'Link cargado' : (p.link_pago_estado === 'vencido' ? 'Link vencido' : 'Pendiente de enviar')"></span>
                                        <template x-if="!p.link_pago_url && p.estado === 'solicita_link'">
                                            <p class="mt-1 text-[11px] text-amber-600">Solicitado y pendiente de carga</p>
                                        </template>
                                        <template x-if="p.link_pago_estado">
                                            <p class="mt-1 text-[11px] text-gray-500" x-text="'Estado interno: ' + p.link_pago_estado"></p>
                                        </template>
                                        <template x-if="p.link_pago_estado === 'ejecutado'">
                                            <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Confirmado por estudiante
                                            </p>
                                        </template>
                                    </td>
                                    <td class="text-xs text-gray-500 truncate max-w-[240px]" x-text="p.link_pago_url || 'Pendiente de carga'"></td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button @click="abrirModalLinkPago(p)" class="btn btn-ghost btn-sm text-brand-600">Cargar enlace</button>
                                            <button x-show="flujo.habilita_aprobacion_pago && api.hasPermission('pagos.aprobar') && p.estado === 'solicita_link'" @click="abrirRechazo(p)" class="btn btn-ghost btn-sm text-red-600">Rechazar</button>
                                            <button x-show="api.hasPermission('pagos.eliminar')" @click="eliminarPagoTotal(p)" class="btn btn-danger btn-sm">Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="pagosSolicitaLink.length === 0"><tr><td colspan="6" class="text-center py-10 text-gray-400 text-sm">No hay solicitudes de link</td></tr></template>
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pb-4 text-xs text-gray-500">Se listan todos los pagos con estado <span class="font-medium">solicita_link</span>. Cuando el enlace está cargado, el pago pasa a <span class="font-medium">en_revision</span> y el estudiante puede continuar el flujo.</div>
            </div>

            {{-- En Revisión --}}
            <div x-show="tab === 'en_revision'" class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Concepto</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Comprobante</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="p in pagosEnRevision" :key="p.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                    <td class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="p.estudiante?.codigo"></span> <span x-text="(p.estudiante?.nombre || '') + ' ' + (p.estudiante?.apellido || '')"></span></td>
                                    <td><span class="badge badge-info" x-text="p.concepto_pago?.codigo || '-' "></span> <span class="text-gray-500 text-xs" x-text="p.concepto_pago?.nombre || '-'"></span></td>
                                    <td class="font-semibold">L <span x-text="fmtMonto(p.monto)"></span></td>
                                    <td x-text="p.metodo_pago?.codigo ? (p.metodo_pago.codigo + ' · ' + p.metodo_pago.nombre) : (p.metodo_pago?.nombre || '-')"></td>
                                    <td class="text-xs text-gray-500">
                                        <span x-text="p.referencia_externa || '-'"></span>
                                        <template x-if="p.alerta_duplicado">
                                            <span class="ml-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-semibold" :title="p.alerta_duplicado_mensaje">⚠ Duplicado</span>
                                        </template>
                                    </td>
                                    <td>
                                        <template x-if="p.comprobantes && p.comprobantes.length > 0">
                                            <button @click="verComprobante(p)" class="btn btn-ghost btn-sm text-brand-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                                Ver
                                            </button>
                                        </template>
                                        <template x-if="!p.comprobantes || p.comprobantes.length === 0">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button @click="verDetalle(p)" class="btn btn-ghost btn-sm text-brand-600">Ver</button>
                                            <button x-show="api.hasPermission('pagos.aprobar')" @click="aprobar(p)" class="btn btn-ghost btn-sm text-emerald-600">Aprobar</button>
                                            <button x-show="api.hasPermission('pagos.aprobar')" @click="abrirRechazo(p)" class="btn btn-ghost btn-sm text-red-600">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="pagosEnRevision.length === 0">
                                <tr><td colspan="8" class="text-center py-10 text-gray-400 text-sm">No hay pagos en revisión</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Aprobados --}}
            <div x-show="tab === 'aprobados'" class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Estado</th><th>Aprobado por</th><th>Fecha Aprobación</th></tr></thead>
                            <tbody>
                                <template x-for="p in pagosAprobados" :key="p.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                        <td class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="p.estudiante?.codigo"></span> <span x-text="(p.estudiante?.nombre || '') + ' ' + (p.estudiante?.apellido || '')"></span></td>
                                        <td><span class="badge badge-info" x-text="p.concepto_pago?.codigo || '-'"></span> <span class="text-gray-500 text-xs" x-text="p.concepto_pago?.nombre || '-'"></span></td>
                                        <td x-text="p.metodo_pago?.codigo ? (p.metodo_pago.codigo + ' · ' + p.metodo_pago.nombre) : (p.metodo_pago?.nombre || '-')"></td>
                                        <td class="font-semibold">L <span x-text="fmtMonto(p.monto)"></span></td>
                                        <td><span class="badge badge-success">Aprobado</span></td>
                                        <td class="text-gray-500 text-xs" x-text="p.aprobado_por?.name || '-'"></td>
                                        <td class="text-gray-500 text-xs" x-text="fmtFecha(p.fecha_aprobacion)"></td>
                                    </tr>
                                </template>
                                <template x-if="pagosAprobados.length === 0">
                                    <tr><td colspan="8" class="text-center py-10 text-gray-400 text-sm">No hay pagos aprobados</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Rechazados --}}
            <div x-show="tab === 'rechazados'" class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Estado</th><th>Motivo</th><th>Rechazado por</th><th>Fecha Rechazo</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="p in pagosRechazados" :key="p.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                    <td class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="p.estudiante?.codigo"></span> <span x-text="(p.estudiante?.nombre || '') + ' ' + (p.estudiante?.apellido || '')"></span></td>
                                    <td><span class="badge badge-info" x-text="p.concepto_pago?.codigo || '-' "></span> <span class="text-gray-500 text-xs" x-text="p.concepto_pago?.nombre || '-'"></span></td>
                                    <td x-text="p.metodo_pago?.codigo ? (p.metodo_pago.codigo + ' · ' + p.metodo_pago.nombre) : (p.metodo_pago?.nombre || '-')"></td>
                                    <td class="font-semibold">L <span x-text="fmtMonto(p.monto)"></span></td>
                                    <td><span class="badge badge-danger" x-text="p.estado || '-'"></span></td>
                                    <td class="text-xs text-red-600 max-w-xs truncate" x-text="p.motivo_rechazo || '-'"></td>
                                    <td class="text-gray-500 text-xs" x-text="p.rechazado_por?.name || '-'"></td>
                                    <td class="text-gray-500 text-xs" x-text="fmtFecha(p.fecha_rechazo)"></td>
                                    <td class="text-right whitespace-nowrap">
                                        <button x-show="api.hasPermission('pagos.eliminar')" @click="eliminarPagoTotal(p)" class="btn btn-danger btn-sm">Eliminar</button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="pagosRechazados.length === 0">
                                <tr><td colspan="10" class="text-center py-10 text-gray-400 text-sm">No hay pagos rechazados</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recibos --}}
            <div x-show="tab === 'recibos'">
                {{-- Filtros recibos --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div><label class="label">Desde</label><input x-model="filtroRecibos.fecha_desde" type="date" @change="loadRecibos()" class="input"></div>
                            <div><label class="label">Hasta</label><input x-model="filtroRecibos.fecha_hasta" type="date" @change="loadRecibos()" class="input"></div>
                            <div>
                                <label class="label">Estado</label>
                                <select x-model="filtroRecibos.estado" @change="loadRecibos()" class="input">
                                    <option value="">Todos</option>
                                    <option value="emitido">Emitido</option>
                                    <option value="reversado">Reversado</option>
                                    <option value="anulado">Anulado</option>
                                </select>
                            </div>
                            <div class="flex items-end"><button @click="loadRecibos()" class="btn btn-outline w-full">Actualizar</button></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th># Recibo</th><th>Estudiante</th><th>Método</th><th>Monto</th><th>Estado</th><th>Fecha</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="r in recibos" :key="r.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="r.numero_recibo"></td>
                                        <td class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="r.estudiante?.codigo"></span> <span x-text="(r.estudiante?.nombre || '') + ' ' + (r.estudiante?.apellido || '')"></span></td>
                                        <td x-text="r.metodo_pago?.nombre || '-'"></td>
                                        <td class="font-semibold">L <span x-text="fmtMonto(r.monto_total)"></span></td>
                                        <td><span :class="r.estado === 'emitido' ? 'badge-success' : r.estado === 'anulado' ? 'badge-danger' : 'badge-info'" class="badge" x-text="r.estado"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="fmtFecha(r.fecha_recibo || r.fecha_proceso || r.pago?.fecha_proceso || r.pago?.fecha_aprobacion || r.creado_en)"></td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button @click="verRecibo(r)" class="btn btn-ghost btn-sm">Ver</button>
                                                <button x-show="api.hasPermission('caja.recibos.imprimir') && r.estado !== 'anulado'" @click="imprimirRecibo(r)" class="btn btn-ghost btn-sm text-brand-600">Imprimir</button>
                                                <button x-show="api.hasPermission('caja.recibos.anular') && r.estado === 'emitido'" @click="abrirAnulacion(r)" class="btn btn-ghost btn-sm text-red-600">Anular</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="recibos.length === 0">
                                    <tr><td colspan="7" class="text-center py-10 text-gray-400 text-sm">No hay recibos para los filtros seleccionados</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Enlaces de Pago --}}
            <div x-show="tab === 'enlaces'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('pagos.enlaces-pago.crear')" @click="abrirModalEnlace()" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Enlace
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Concepto</th><th>Monto</th><th>Banco</th><th>Usos</th><th>Vence</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="e in enlacesPago" :key="e.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="e.codigo"></td>
                                        <td class="font-medium" x-text="e.nombre"></td>
                                        <td><span class="badge badge-info" x-text="e.concepto_pago?.codigo || '-'"></span></td>
                                        <td class="font-semibold" x-text="e.monto ? 'L ' + fmtMonto(e.monto) : '-'"></td>
                                        <td class="text-gray-500 text-xs" x-text="e.cuenta_bancaria?.banco || '-'"></td>
                                        <td class="text-xs" x-text="(e.usos_actuales || 0) + (e.usos_maximos ? ' / ' + e.usos_maximos : ' / ∞')"></td>
                                        <td class="text-gray-500 text-xs" x-text="e.fecha_vencimiento || '-'"></td>
                                        <td><span :class="e.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="e.estado"></span></td>
                                        <td class="text-right whitespace-nowrap">
                                            <button x-show="api.hasPermission('pagos.enlaces-pago.modificar')" @click="editarEnlace(e)" class="btn btn-ghost btn-sm">Editar</button>
                                            <button x-show="api.hasPermission('pagos.enlaces-pago.eliminar')" @click="eliminarEnlace(e)" class="btn btn-ghost btn-sm text-red-500">Eliminar</button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="enlacesPago.length === 0">
                                    <tr><td colspan="9" class="text-center py-10 text-gray-400 text-sm">No hay enlaces de pago configurados</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal Cargar Link de Pago --}}
    <div x-show="showModalLinkPago" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="cerrarModalLinkPago()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Cargar enlace de pago</h3>
                    <p class="text-sm text-gray-500" x-text="linkPagoActual ? linkPagoActual.codigo : ''"></p>
                </div>
                <button @click="cerrarModalLinkPago()" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 space-y-4">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    Pegue aquí un enlace completo. Debe iniciar con <span class="font-semibold">http://</span> o <span class="font-semibold">https://</span>.
                </div>
                <div>
                    <label class="label">Enlace de pago *</label>
                    <textarea x-model="linkPagoInput" @input="validarLinkPago()" rows="6" class="input min-h-[180px] font-mono text-sm" placeholder="https://..."></textarea>
                    <p class="mt-1 text-xs" :class="linkPagoValido ? 'text-emerald-600' : 'text-amber-600'" x-text="linkPagoValido ? 'El enlace es válido.' : 'Debe ser una URL válida con http:// o https://.'"></p>
                </div>
                <div x-show="errorLinkPago" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700" x-text="errorLinkPago"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="validarLinkPago()" class="btn btn-outline">Validar enlace</button>
                    <button type="button" @click="cerrarModalLinkPago()" class="btn btn-outline">Cancelar</button>
                    <button type="button" @click="guardarLinkPago()" :disabled="savingLinkPago || !linkPagoValido" class="btn btn-primary"><span x-text="savingLinkPago ? 'Guardando...' : 'Guardar enlace'"></span></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Enlace de Pago --}}
    <div x-show="showModalEnlace" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalEnlace = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editandoEnlace ? 'Editar Enlace' : 'Nuevo Enlace'"></h3>
                <button @click="showModalEnlace = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="guardarEnlace()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Código *</label>
                        <input x-model="formEnlace.codigo" type="text" required maxlength="50" class="input" placeholder="LNK-...">
                    </div>
                    <div>
                        <label class="label">Monto (L)</label>
                        <input x-model.number="formEnlace.monto" type="number" step="0.01" min="0" class="input" placeholder="Dejar vacío si es variable">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Nombre *</label>
                        <input x-model="formEnlace.nombre" type="text" required maxlength="150" class="input">
                    </div>
                    <div>
                        <label class="label">Concepto</label>
                        <select x-model="formEnlace.concepto_pago_id" class="input"><option value="">Seleccionar...</option><template x-for="c in conceptos" :key="c.id"><option :value="c.id" x-text="c.codigo + ' — ' + c.nombre"></option></template></select>
                    </div>
                    <div>
                        <label class="label">Cuenta Bancaria</label>
                        <select x-model="formEnlace.cuenta_bancaria_id" class="input"><option value="">Seleccionar...</option><template x-for="cb in cuentasBancarias" :key="cb.id"><option :value="cb.id" x-text="cb.banco + ' — ' + cb.numero_cuenta"></option></template></select>
                    </div>
                    <div>
                        <label class="label">Usos Máximos</label>
                        <input x-model.number="formEnlace.usos_maximos" type="number" min="1" class="input" placeholder="Sin límite">
                    </div>
                    <div>
                        <label class="label">Fecha Vencimiento</label>
                        <input x-model="formEnlace.fecha_vencimiento" type="date" class="input">
                    </div>
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="formEnlace.estado" class="input">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div x-show="errorEnlace" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorEnlace"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModalEnlace = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingEnlace" class="btn btn-primary"><span x-text="savingEnlace ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Registrar Pago --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Registrar Pago</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="registrarPago()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="label">Estudiante</label>
                        <input x-model="busquedaEstudiante" @input.debounce.300ms="buscarEstudiantes()" type="text" placeholder="Buscar estudiante..." class="input">
                        <div x-show="resultadosEstudiantes.length > 0" class="relative z-10 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="e in resultadosEstudiantes" :key="e.id">
                                <button type="button" @click="seleccionarEstudiante(e)" class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm"><span class="font-medium" x-text="e.codigo + ' — ' + (e.nombres || e.nombre || '') + ' ' + (e.apellidos || e.apellido || '')"></span></button>
                    </template>
                    <div class="col-span-2">
                        <label class="label">Código de Recibo</label>
                        <input x-model="form.codigo_recibo" type="text" class="input font-mono" placeholder="Cargando..." :disabled="!siguienteReciboCargado">
                        <p class="text-xs text-gray-400 mt-1">Se genera automáticamente. Puede editarlo si es necesario.</p>
                    </div>
                </div>
                    </div>
                    <div>
                        <label class="label">Concepto</label>
                        <select x-model="form.concepto_pago_id" @change="onConceptoChange()" required class="input"><option value="">Seleccionar...</option><template x-for="c in conceptos" :key="c.id"><option :value="c.id" x-text="c.codigo + ' — ' + c.nombre"></option></template></select>
                        <p class="text-xs mt-1" :class="metodoPermiteLink ? 'text-emerald-600' : 'text-amber-600'" x-text="metodoPermiteLink ? 'Este método permite solicitud de link.' : 'Este método no permite solicitud de link.'"></p>
                    </div>
                    <div>
                        <label class="label">Método de Pago</label>
                        <select x-model="form.metodo_pago_id" @change="onMetodoChange()" required class="input"><option value="">Seleccionar...</option><template x-for="m in metodos" :key="m.id"><option :value="m.id" x-text="m.nombre"></option></template></select>
                    </div>
                    <template x-if="esMATCUO && flujo.habilita_seleccion_obligaciones">
                        <div class="col-span-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <label class="label text-emerald-900">Obligaciones pendientes</label>
                                <button type="button" @click="seleccionarTodasObligaciones()" class="text-xs font-medium text-emerald-700">Seleccionar todas</button>
                            </div>
                            <template x-if="!form.estudiante_id">
                                <p class="text-xs text-amber-700">Seleccione primero un estudiante.</p>
                            </template>
                            <template x-if="form.estudiante_id && obligacionesPendientes.length === 0">
                                <p class="text-xs text-amber-700">No hay obligaciones pendientes para este concepto.</p>
                            </template>
                            <div class="space-y-2 max-h-48 overflow-y-auto" x-show="obligacionesPendientes.length > 0">
                                <template x-for="o in obligacionesPendientes" :key="o.id">
                                    <label class="flex items-center justify-between gap-3 rounded border border-emerald-100 bg-white px-3 py-2 text-sm">
                                        <span class="flex items-center gap-2">
                                            <input type="checkbox" :value="o.id" x-model="obligacionesSeleccionadas" @change="actualizarMontoObligaciones()" class="rounded border-gray-300 text-emerald-600">
                                            <span><span class="font-medium" x-text="o.matricula_codigo + ' · ' + o.nombre_cargo"></span><span class="block text-xs text-gray-500" x-text="'Vencimiento: ' + (o.fecha_vencimiento || '-')"></span></span>
                                        </span>
                                        <span class="font-semibold whitespace-nowrap">L <span x-text="fmtMonto(o.saldo)"></span></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div>
                        <label class="label">Monto (L)</label>
                        <input x-model.number="form.monto" type="number" step="0.01" min="0" required class="input">
                    </div>
                    <div>
                        <label class="label">Fecha de Proceso *</label>
                        <input x-model="form.fecha_proceso" type="date" required class="input">
                    </div>
                    <div class="col-span-2" x-show="flujo.habilita_solicitud_link">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input x-model="form.solicitar_link" x-show="flujo.habilita_solicitud_link" :disabled="!metodoPermiteLink" type="checkbox" class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Solicitar link de pago para este registro
                        </label>
                    </div>
                    <div>
                        <label class="label">Referencia</label>
                        <input x-model="form.referencia_externa" type="text" class="input" placeholder="N° depósito/transferencia">
                    </div>
                    <div class="col-span-2">
                        <label class="label">Comentario</label>
                        <textarea x-model="form.observaciones" rows="2" class="input" placeholder="Observaciones del pago o reserva"></textarea>
                    </div>
                    <template x-if="esVLI">
                        <div class="col-span-2 grid grid-cols-2 gap-4 p-3 bg-amber-50 rounded-lg border border-amber-200">
                            <div class="col-span-2">
                                <label class="label text-amber-800 font-semibold">📚 Venta de Libro</label>
                            </div>
                            <div>
                                <label class="label">Libro</label>
                                <select x-model="form.inventario_libro_id" required class="input"><option value="">Seleccionar...</option><template x-for="l in libros" :key="l.id"><option :value="l.id" x-text="l.codigo + ' — ' + l.titulo + ' (L ' + fmtMonto(l.precio_venta) + ')'"></option></template></select>
                            </div>
                            <div>
                                <label class="label">Cantidad</label>
                                <input x-model.number="form.cantidad_libro" type="number" min="1" value="1" required class="input">
                            </div>
                            <div class="col-span-2 text-xs text-amber-700" x-show="form.inventario_libro_id && form.cantidad_libro">
                                <span>Total sugerido: L <span x-text="fmtMonto(totalLibroSugerido)"></span></span>
                                <span x-show="form.monto != totalLibroSugerido" class="text-red-500 ml-2">(el monto no coincide)</span>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="error"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving || (!flujo.habilita_aprobacion_pago && !form.solicitar_link)" class="btn btn-primary"><span x-text="saving ? 'Registrando...' : 'Registrar Pago'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Visor de Comprobante --}}
    <div x-show="showComprobante" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showComprobante = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Comprobante de Pago</h3>
                    <p class="text-xs text-gray-400" x-text="pagoSeleccionado?.codigo + ' — ' + (pagoSeleccionado?.estudiante?.codigo || '') + ' · ' + (pagoSeleccionado?.estudiante?.nombre || '') + ' ' + (pagoSeleccionado?.estudiante?.apellido || '')"></p>
                </div>
                <button @click="showComprobante = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6">
                <template x-for="c in (pagoSeleccionado?.comprobantes || [])" :key="c.id">
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-gray-600"><span class="font-medium" x-text="c.nombre_archivo"></span> <span class="text-gray-400 text-xs" x-text="'· ' + fmtFecha(c.creado_en)"></span></p>
                            <a :href="'/storage/' + c.ruta_archivo" target="_blank" class="btn btn-outline btn-sm">Abrir original</a>
                        </div>
                        <template x-if="esImagen(c)">
                            <img :src="'/storage/' + c.ruta_archivo" :alt="c.nombre_archivo" class="w-full rounded-lg border border-gray-200">
                        </template>
                        <template x-if="!esImagen(c)">
                            <div class="flex flex-col items-center justify-center py-10 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="w-10 h-10 text-red-400 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                <p class="text-sm text-gray-500 mb-3">Documento PDF</p>
                                <a :href="'/storage/' + c.ruta_archivo" target="_blank" class="btn btn-primary btn-sm">Abrir PDF</a>
                            </div>
                        </template>
                    </div>
                </template>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100" x-show="flujo.habilita_aprobacion_pago && pagoSeleccionado?.estado === 'pendiente' && api.hasPermission('pagos.aprobar')">
                    <button @click="showComprobante = false; abrirRechazo(pagoSeleccionado)" class="btn btn-outline text-red-600">Rechazar</button>
                    <button @click="showComprobante = false; aprobar(pagoSeleccionado)" class="btn btn-primary">Aprobar Pago</button>
                </div>
                <div class="flex justify-end gap-3 pt-3" x-show="api.hasPermission('pagos.eliminar') && flujo.habilita_generacion_recibo">
                    <button @click="eliminarPagoTotal(pagoSeleccionado)" class="btn btn-danger">Eliminar por completo</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Confirmar Aprobación --}}
    <div x-show="showConfirmarAprobacion" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showConfirmarAprobacion = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <div class="text-center">
                <div class="mx-auto w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">¿Aprobar este pago?</h3>
                <p class="text-sm text-gray-500 mb-1">Pago <span class="font-mono font-semibold text-gray-800" x-text="pagoAprobar?.codigo"></span></p>
                <p class="text-sm text-gray-500 mb-1" x-text="'Estudiante: ' + (pagoAprobar?.estudiante_nombre || '-')"></p>
                <p class="text-sm text-gray-500">Monto: <span class="font-semibold text-gray-800" x-text="'L. ' + fmtMonto(pagoAprobar?.monto)"></span></p>
            </div>
            <div class="flex justify-center gap-3 mt-6">
                <button @click="showConfirmarAprobacion = false; pagoAprobar = null" class="btn btn-outline">Cancelar</button>
                <button @click="confirmarAprobacion()" class="btn btn-primary" x-show="flujo.habilita_aprobacion_pago">Confirmar Aprobación</button>
            </div>
        </div>
    </div>

    {{-- Modal Rechazar Pago --}}
    <div x-show="showRechazo" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showRechazo = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Rechazar Pago</h3>
                <button @click="showRechazo = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="rechazar()" class="p-6 space-y-4">
                <p class="text-sm text-gray-600">Pago <span class="font-mono font-semibold" x-text="pagoSeleccionado?.codigo"></span> por <span class="font-semibold">L <span x-text="fmtMonto(pagoSeleccionado?.monto)"></span></span></p>
                <p class="text-xs text-gray-500">Estado actual: <span class="font-medium" x-text="pagoSeleccionado?.estado"></span></p>
                <div>
                    <label class="label">Motivo del rechazo *</label>
                    <textarea x-model="motivoRechazo" required rows="3" maxlength="500" class="input" placeholder="Explique el motivo (se notificará al estudiante)..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showRechazo = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-danger"><span x-text="saving ? 'Rechazando...' : 'Rechazar Pago'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Detalle Recibo --}}
    <div x-show="showRecibo" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showRecibo = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Detalle de Recibo</h3>
                <button @click="showRecibo = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6" x-show="reciboSeleccionado">
                <div class="text-center pb-4 border-b border-dashed border-gray-200 mb-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Cursos San Vicente de Paul</p>
                    <p class="text-2xl font-bold font-mono text-brand-600" x-text="'#' + String(reciboSeleccionado?.numero_recibo).padStart(6, '0')"></p>
                    <span :class="reciboSeleccionado?.estado === 'emitido' ? 'badge-success' : reciboSeleccionado?.estado === 'anulado' ? 'badge-danger' : 'badge-info'" class="badge" x-text="reciboSeleccionado?.estado"></span>
                </div>
                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div><span class="text-gray-400 block">Código pago</span><span class="font-mono font-semibold text-brand-600" x-text="reciboSeleccionado?.codigo_pago || '-'"></span></div>
                        <div><span class="text-gray-400 block">Código recibo</span><span class="font-mono font-semibold text-brand-600" x-text="reciboSeleccionado?.codigo || '-'"></span></div>
                        <div><span class="text-gray-400 block">Estudiante</span><span class="font-medium"><span class="font-mono text-xs text-brand-600" x-text="reciboSeleccionado?.estudiante?.codigo"></span> <span x-text="(reciboSeleccionado?.estudiante?.nombre || '') + ' ' + (reciboSeleccionado?.estudiante?.apellido || '')"></span></span></div>
                        <div><span class="text-gray-400 block">Estado</span><span :class="reciboSeleccionado?.estado === 'emitido' ? 'badge-success' : reciboSeleccionado?.estado === 'anulado' ? 'badge-danger' : 'badge-info'" class="badge" x-text="reciboSeleccionado?.estado"></span></div>
                        <div><span class="text-gray-400 block">Concepto origen</span><span x-text="reciboSeleccionado?.conceptoPago?.codigo ? (reciboSeleccionado.conceptoPago.codigo + ' · ' + reciboSeleccionado.conceptoPago.nombre) : (reciboSeleccionado?.concepto_pago?.codigo ? (reciboSeleccionado.concepto_pago.codigo + ' · ' + reciboSeleccionado.concepto_pago.nombre) : (reciboSeleccionado?.conceptoPago?.nombre || '-'))"></span></div>
                        <div><span class="text-gray-400 block">Método de pago</span><span x-text="reciboSeleccionado?.metodo_pago?.nombre || reciboSeleccionado?.metodoPago?.nombre || '-'"></span></div>
                        <div><span class="text-gray-400 block">Sucursal</span><span x-text="reciboSeleccionado?.sucursal?.nombre || '-'"></span></div>
                        <div><span class="text-gray-400 block">Fecha</span><span x-text="fmtFecha(reciboSeleccionado?.fecha_recibo || reciboSeleccionado?.fecha_proceso || reciboSeleccionado?.pago?.fecha_proceso || reciboSeleccionado?.pago?.fecha_aprobacion || reciboSeleccionado?.creado_en)"></span></div>
                        <div><span class="text-gray-400 block">Hora</span><span class="text-gray-500" x-text="fmtHora(reciboSeleccionado?.fecha_recibo || reciboSeleccionado?.fecha_proceso || reciboSeleccionado?.pago?.fecha_proceso || reciboSeleccionado?.pago?.fecha_aprobacion || reciboSeleccionado?.creado_en)"></span></div>
                        <div><span class="text-gray-400 block">Reimpresiones</span><span x-text="reciboSeleccionado?.veces_reimpreso || 0"></span></div>
                    </div>

                    <div class="border-t border-gray-100 pt-4" x-show="reciboSeleccionado?.pago?.matricula?.ofertaAcademica">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Detalle Académico</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div><span class="text-gray-400 block">Matrícula</span><span class="font-mono text-xs text-brand-600" x-text="reciboSeleccionado?.pago?.matricula?.codigo || '-'"></span></div>
                            <div><span class="text-gray-400 block">Nivel</span><span x-text="reciboSeleccionado?.pago?.matricula?.ofertaAcademica?.nivelAcademico?.codigo ? (reciboSeleccionado.pago.matricula.ofertaAcademica.nivelAcademico.codigo + ' · ' + reciboSeleccionado.pago.matricula.ofertaAcademica.nivelAcademico.nombre) : '-'"></span></div>
                            <div><span class="text-gray-400 block">Horario</span><span x-text="reciboSeleccionado?.pago?.matricula?.ofertaAcademica?.horario?.nombre || '-'"></span></div>
                            <div><span class="text-gray-400 block">Modalidad</span><span x-text="reciboSeleccionado?.pago?.matricula?.ofertaAcademica?.modalidad?.nombre || '-'"></span></div>
                            <div><span class="text-gray-400 block">Docente</span><span x-text="reciboSeleccionado?.pago?.matricula?.ofertaAcademica?.docente ? ((reciboSeleccionado.pago.matricula.ofertaAcademica.docente.nombre || '') + ' ' + (reciboSeleccionado.pago.matricula.ofertaAcademica.docente.apellido || '')) : '-' "></span></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4" x-show="(reciboSeleccionado?.pago?.aplicaciones || []).length > 0">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Detalle de aplicación</h4>
                        <div class="overflow-x-auto">
                            <table class="table text-xs">
                                <thead><tr><th>Concepto</th><th>Cuota</th><th>Monto aplicado</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <template x-for="a in (reciboSeleccionado?.pago?.aplicaciones || [])" :key="a.id">
                                        <tr>
                                            <td><span class="badge badge-info" x-text="a.obligacion?.concepto_pago?.codigo || '-' "></span></td>
                                            <td x-text="a.obligacion?.nombre_cargo || '-'"></td>
                                            <td class="font-semibold">L <span x-text="fmtMonto(a.monto_aplicado)"></span></td>
                                            <td><span :class="a.estado === 'activo' ? 'badge-success' : a.estado === 'pendiente' ? 'badge-warning' : a.estado === 'cancelado' ? 'badge-danger' : 'badge-info'" class="badge" x-text="a.estado"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4" x-show="(reciboSeleccionado?.pago?.movimientosInventario || []).length > 0">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Detalle de venta</h4>
                        <template x-for="mov in (reciboSeleccionado?.pago?.movimientosInventario || [])" :key="mov.id">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 mb-2">
                                <div><span class="text-gray-400 block">Libro</span><span x-text="mov.inventario_libro?.libro?.codigo ? (mov.inventario_libro.libro.codigo + ' · ' + mov.inventario_libro.libro.titulo) : '-' "></span></div>
                                <div class="grid grid-cols-2 gap-3 mt-2 text-xs">
                                    <div><span class="text-gray-400 block">Cantidad</span><span x-text="mov.cantidad"></span></div>
                                    <div><span class="text-gray-400 block">Total</span><span x-text="'L. ' + fmtMonto((mov.inventario_libro?.libro?.precio_venta || 0) * Number(mov.cantidad || 0))"></span></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-2"><span>Monto</span><span>L <span x-text="fmtMonto(reciboSeleccionado?.monto_total)"></span></span></div>

                    <template x-if="reciboSeleccionado?.estado === 'anulado'">
                        <div class="mt-3 p-3 bg-red-50 rounded-lg">
                            <p class="text-xs text-red-400">Motivo de anulación</p>
                            <p class="text-sm text-red-700" x-text="reciboSeleccionado?.motivo_anulacion || '-'"></p>
                        </div>
                    </template>
                </div>
                <div class="flex justify-end pt-4">
                    <button @click="showRecibo = false" class="btn btn-outline">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalle Pago --}}
    <div x-show="showDetalle" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showDetalle = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Detalle del Pago</h3>
                <button @click="showDetalle = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 space-y-4" x-show="detallePago">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-400">Código</span><p class="font-mono font-semibold text-brand-600" x-text="detallePago?.codigo"></p></div>
                    <div><span class="text-gray-400">Estado</span><p><span :class="detallePago?.estado === 'pendiente' ? 'badge-warning' : detallePago?.estado === 'en_revision' ? 'badge-info' : detallePago?.estado === 'aprobado' ? 'badge-success' : 'badge-danger'" class="badge" x-text="detallePago?.estado"></span></p></div>
                    <div class="col-span-2"><span class="text-gray-400">Estudiante</span><p class="font-medium" x-text="(detallePago?.estudiante?.codigo || '') + ' — ' + (detallePago?.estudiante?.nombre || '') + ' ' + (detallePago?.estudiante?.apellido || '')"></p></div>
                    <div><span class="text-gray-400">Concepto</span><p x-text="detallePago?.concepto_pago?.codigo + ' — ' + detallePago?.concepto_pago?.nombre"></p></div>
                    <div><span class="text-gray-400">Método</span><p x-text="detallePago?.metodo_pago?.nombre || '-'"></p></div>
                    <div><span class="text-gray-400">Monto</span><p class="font-semibold text-lg">L <span x-text="fmtMonto(detallePago?.monto)"></span></p></div>
                    <div><span class="text-gray-400">Referencia</span><p x-text="detallePago?.referencia_externa || '-'"></p></div>
                    <div><span class="text-gray-400">Sucursal</span><p x-text="detallePago?.sucursal?.nombre || '-'"></p></div>
                    <div><span class="text-gray-400">Fecha depósito</span><p x-text="fmtFecha(detallePago?.fecha_deposito) || '-'"></p></div>
                    <div><span class="text-gray-400">Fecha proceso</span><p x-text="fmtFecha(detallePago?.fecha_proceso || detallePago?.fecha_aprobacion || detallePago?.creado_en)"></p></div>
                    <div><span class="text-gray-400">Hora</span><p x-text="fmtHora(detallePago?.fecha_proceso || detallePago?.fecha_aprobacion || detallePago?.creado_en)"></p></div>
                </div>

                <template x-if="detallePago?.alerta_duplicado">
                    <div class="border border-red-200 bg-red-50 rounded-xl p-4 space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm0-13.5L2.25 19.5h19.5L12 3Z" /></svg>
                            <h4 class="text-sm font-bold text-red-800">Alerta: Referencia y fecha duplicadas</h4>
                        </div>
                        <p class="text-xs text-red-700" x-text="detallePago?.alerta_duplicado_mensaje || 'La referencia y fecha de pago coinciden con otro pago registrado por otro estudiante.'"></p>
                        <p class="text-[11px] text-red-500">Detectada el <span x-text="fmtFecha(detallePago?.alerta_duplicado_en)"></span> a las <span x-text="fmtHora(detallePago?.alerta_duplicado_en)"></span>. Se notificó a contabilidad. Revise antes de aprobar.</p>
                    </div>
                </template>

                <template x-if="detallePago?.estado === 'solicita_link' || detallePago?.link_pago_url || detallePago?.link_pago_estado || detallePago?.confirmado_por_estudiante_en">
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <h4 class="text-sm font-semibold text-gray-700">Flujo de Link</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="bg-sky-50 rounded-lg p-3">
                                <span class="text-gray-500 block mb-1">Estado del link</span>
                                <span class="font-medium" x-text="detallePago?.link_pago_estado || 'pendiente'"></span>
                            </div>
                            <div class="bg-sky-50 rounded-lg p-3">
                                <span class="text-gray-500 block mb-1">Confirmado por estudiante</span>
                                <span class="font-medium" x-text="detallePago?.confirmado_por_estudiante_en ? fmtFecha(detallePago.confirmado_por_estudiante_en) : '-' "></span>
                            </div>
                            <div class="sm:col-span-2 bg-sky-50 rounded-lg p-3">
                                <span class="text-gray-500 block mb-1">Link generado</span>
                                <template x-if="detallePago?.link_pago_url">
                                    <a :href="detallePago.link_pago_url" target="_blank" class="text-brand-600 font-medium break-all hover:underline" x-text="detallePago.link_pago_url"></a>
                                </template>
                                <template x-if="!detallePago?.link_pago_url">
                                    <span class="font-medium">Sin enlace registrado</span>
                                </template>
                            </div>
                            <div class="sm:col-span-2 bg-sky-50 rounded-lg p-3">
                                <span class="text-gray-500 block mb-1">Matrícula asociada</span>
                                <span class="font-medium" x-text="detallePago?.matricula?.codigo || '-' "></span>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="border-t border-gray-100 pt-4" x-show="(detallePago?.aplicaciones || []).length > 0">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Aplicación a Obligaciones</h4>
                    <div class="overflow-x-auto">
                        <table class="table text-xs">
                            <thead><tr><th>Concepto</th><th>Cuota</th><th>Monto Aplicado</th><th>Estado</th></tr></thead>
                            <tbody>
                                <template x-for="a in (detallePago?.aplicaciones || [])" :key="a.id">
                                    <tr>
                                        <td><span class="badge badge-info" x-text="a.obligacion?.concepto_pago?.codigo || '-'"></span></td>
                                        <td x-text="a.obligacion?.nombre_cargo || '-'"></td>
                                        <td class="font-semibold">L <span x-text="fmtMonto(a.monto_aplicado)"></span></td>
                                        <td><span :class="a.estado === 'activo' ? 'badge-success' : a.estado === 'pendiente' ? 'badge-warning' : a.estado === 'cancelado' ? 'badge-danger' : 'badge-info'" class="badge" x-text="a.estado"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <template x-if="detallePago?.aprobado_por">
                    <div class="p-3 bg-emerald-50 rounded-lg text-sm">
                        <span class="text-emerald-600 font-medium">Aprobado por</span> <span x-text="detallePago.aprobado_por.name + ' — ' + fmtFecha(detallePago.fecha_aprobacion)"></span>
                    </div>
                </template>
                <template x-if="detallePago?.motivo_rechazo">
                    <div class="p-3 bg-red-50 rounded-lg text-sm">
                        <span class="text-red-600 font-medium">Rechazado</span>
                        <p class="text-red-700 text-xs mt-1" x-text="detallePago.motivo_rechazo"></p>
                    </div>
                </template>

                <div class="flex justify-end pt-2">
                    <button @click="showDetalle = false" class="btn btn-outline">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Anular Recibo --}}
    <div x-show="showAnulacion" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAnulacion = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Anular Recibo</h3>
                <button @click="showAnulacion = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="anular()" class="p-6 space-y-4">
                <p class="text-sm text-gray-600">Recibo <span class="font-mono font-semibold" x-text="'#' + String(reciboSeleccionado?.numero_recibo).padStart(6, '0')"></span> por <span class="font-semibold">L <span x-text="fmtMonto(reciboSeleccionado?.monto_total)"></span></span></p>
                <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">Esta acción no se puede deshacer. El recibo quedará marcado como anulado.</p>
                <div>
                    <label class="label">Motivo de anulación *</label>
                    <textarea x-model="motivoAnulacion" required rows="3" maxlength="500" class="input" placeholder="Explique el motivo de la anulación..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAnulacion = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-danger"><span x-text="saving ? 'Anulando...' : 'Anular Recibo'"></span></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pagos() {
    return {
        loading: true, showModal: false, saving: false, error: '', tab: 'pendientes',
        pagosPendientes: [], pagosEnRevision: [], pagosAprobados: [], pagosRechazados: [], pagosSolicitaLink: [], recibos: [], conceptos: [], metodos: [], libros: [], metodoPermiteLink: false,
        flujo: { habilita_aprobacion_pago: true, habilita_solicitud_link: true, habilita_carga_comprobante: true, requiere_comprobante: true, habilita_generacion_recibo: true, habilita_seleccion_obligaciones: true },
        debugPagos: { filtroActivo: 'N/D', ultimoConteo: 'N/D', respuestas: {} },
        form: { estudiante_id: '', concepto_pago_id: '', metodo_pago_id: '', monto: 0, fecha_proceso: '', referencia_externa: '', observaciones: '', inventario_libro_id: '', cantidad_libro: 1, solicitar_link: false },
        busquedaEstudiante: '', resultadosEstudiantes: [], obligacionesPendientes: [], obligacionesSeleccionadas: [],
        filtroRecibos: { fecha_desde: '', fecha_hasta: '', estado: '' },
        showComprobante: false, showRechazo: false, showRecibo: false, showAnulacion: false,
        showDetalle: false, detallePago: null,
        pagoSeleccionado: null, pagoAprobar: null, reciboSeleccionado: null,
        showConfirmarAprobacion: false,
        motivoRechazo: '', motivoAnulacion: '',
        siguienteReciboCargado: false,
        enlacesPago: [], cuentasBancarias: [],
        showModalEnlace: false, editandoEnlace: false, savingEnlace: false, errorEnlace: '',
        formEnlace: { codigo: '', nombre: '', monto: '', concepto_pago_id: '', cuenta_bancaria_id: '', fecha_vencimiento: '', usos_maximos: '', estado: 'activo' },
        editandoEnlaceId: null,
        showModalLinkPago: false, savingLinkPago: false, errorLinkPago: '', linkPagoActual: null, linkPagoInput: '', linkPagoValido: false,

        async init() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            const [f, c, m, lb, cb] = await Promise.allSettled([
                window.axios.get('/api/v1/seguridad/configuraciones-flujo-matricula', h),
                window.axios.get('/api/v1/catalogos-academicos/conceptos-pago', h),
                window.axios.get('/api/v1/catalogos-academicos/metodos-pago', h),
                window.axios.get('/api/v1/inventario/libros?per_page=200', h),
                window.axios.get('/api/v1/cuentas-bancarias', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } }).catch(() => ({ data: { data: [] } })),
            ]);
            const flujos = f.status === 'fulfilled' ? (f.value.data.data?.data || f.value.data.data || []) : [];
            this.flujo = flujos.find(c => c.origen === 'portal_administrativo' && c.estado === 'activo') || this.flujo;
            this.conceptos = c.status === 'fulfilled' ? (c.value.data.data?.data || c.value.data.data || []) : [];
            this.metodos = m.status === 'fulfilled' ? (m.value.data.data?.data || m.value.data.data || []) : [];
            this.libros = lb.status === 'fulfilled' ? (lb.value.data.data?.data || lb.value.data.data || []) : [];
            this.cuentasBancarias = cb.status === 'fulfilled' ? (cb.value.data.data || []) : [];
            await this.load();
            this.pollingInterval = setInterval(() => this.load(), 30000);
        },

        get esVLI() {
            if (!this.form.concepto_pago_id) return false;
            const c = this.conceptos.find(x => x.id == this.form.concepto_pago_id);
            return c && c.codigo === 'VLI';
        },

        get esMATCUO() {
            const concepto = this.conceptos.find(x => x.id == this.form.concepto_pago_id);
            return ['MAT', 'CUO'].includes(concepto?.codigo);
        },

        get totalLibroSugerido() {
            if (!this.form.inventario_libro_id || !this.form.cantidad_libro) return 0;
            const l = this.libros.find(x => x.id == this.form.inventario_libro_id);
            return l ? Number(l.precio_venta) * Number(this.form.cantidad_libro) : 0;
        },

        onConceptoChange() {
            const metodo = this.metodos.find(x => x.id == this.form.metodo_pago_id);
            this.metodoPermiteLink = !!metodo?.permite_link_pago;
            if (!this.esVLI) {
                this.form.inventario_libro_id = '';
                this.form.cantidad_libro = 1;
            }
            this.obligacionesPendientes = [];
            this.obligacionesSeleccionadas = [];
            this.cargarObligaciones();
        },

        seleccionarEstudiante(estudiante) {
            this.form.estudiante_id = estudiante.id;
            this.busquedaEstudiante = estudiante.codigo + ' — ' + (estudiante.nombres || estudiante.nombre || '') + ' ' + (estudiante.apellidos || estudiante.apellido || '');
            this.resultadosEstudiantes = [];
            this.cargarObligaciones();
        },

        async cargarObligaciones() {
            if (!this.form.estudiante_id || !this.esMATCUO) return;
            try {
                const params = new URLSearchParams({ estudiante_id: this.form.estudiante_id, concepto_pago_id: this.form.concepto_pago_id });
                if (this.form.metodo_pago_id) params.set('metodo_pago_id', this.form.metodo_pago_id);
                const { data } = await window.axios.get(`/api/v1/pagos/obligaciones-estudiante?${params}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.obligacionesPendientes = data.data?.obligaciones || [];
                this.obligacionesSeleccionadas = [];
                this.actualizarMontoObligaciones();
            } catch (e) {
                this.obligacionesPendientes = [];
                this.error = window.extractError(e, 'No se pudieron cargar las obligaciones');
            }
        },

        seleccionarTodasObligaciones() {
            this.obligacionesSeleccionadas = this.obligacionesPendientes.map(o => String(o.id));
            this.actualizarMontoObligaciones();
        },

        actualizarMontoObligaciones() {
            if (this.esMATCUO && this.obligacionesSeleccionadas.length > 0) {
                this.form.monto = this.obligacionesPendientes.filter(o => this.obligacionesSeleccionadas.includes(String(o.id))).reduce((total, o) => total + Number(o.saldo || 0), 0);
            }
        },

        onMetodoChange() {
            const metodo = this.metodos.find(x => x.id == this.form.metodo_pago_id);
            this.metodoPermiteLink = !!metodo?.permite_link_pago;
            this.cargarObligaciones();
        },

        cambiarTab(nuevoTab) {
            this.tab = nuevoTab;
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const token = localStorage.getItem('auth_token');
                const h = { headers: { Authorization: `Bearer ${token}` } };
                const { data } = await window.axios.get('/api/v1/pagos?clasificar=1&per_page=50', h);
                const extraerLista = (respuesta) => {
                    const cuerpo = respuesta?.data?.data ?? respuesta?.data ?? respuesta ?? [];
                    if (Array.isArray(cuerpo)) return cuerpo;
                    if (Array.isArray(cuerpo?.data)) return cuerpo.data;
                    if (Array.isArray(cuerpo?.items)) return cuerpo.items;
                    return [];
                };

                const payload = data?.data || {};
                this.pagosPendientes = extraerLista(payload.pagosPendientes);
                this.pagosEnRevision = extraerLista(payload.pagosEnRevision);
                this.pagosSolicitaLink = extraerLista(payload.pagosSolicitaLink);
                this.pagosAprobados = extraerLista(payload.pagosAprobados);
                this.pagosRechazados = extraerLista(payload.pagosRechazados);
                this.debugPagos = {
                    filtroActivo: 'clasificar=1',
                    ultimoConteo: `pendientes=${this.pagosPendientes.length}, solicita_link=${this.pagosSolicitaLink.length}, revision=${this.pagosEnRevision.length}`,
                    respuestas: payload.resumen || {},
                };
                await this.loadRecibos();
                await this.loadEnlaces();
            } catch(e) {} finally { this.loading = false; }
        },

        abrirModalLinkPago(p) {
            this.linkPagoActual = p;
            this.linkPagoInput = p?.link_pago_url || '';
            this.errorLinkPago = '';
            this.linkPagoValido = false;
            this.showModalLinkPago = true;
            this.validarLinkPago();
        },

        cerrarModalLinkPago() {
            this.showModalLinkPago = false;
            this.linkPagoActual = null;
            this.linkPagoInput = '';
            this.linkPagoValido = false;
            this.errorLinkPago = '';
        },

        validarLinkPago() {
            const valor = (this.linkPagoInput || '').trim();
            this.errorLinkPago = '';
            if (!valor) {
                this.linkPagoValido = false;
                this.errorLinkPago = 'Debe ingresar un enlace.';
                return false;
            }
            if (!/^https?:\/\//i.test(valor)) {
                this.linkPagoValido = false;
                this.errorLinkPago = 'El enlace debe iniciar con http:// o https://.';
                return false;
            }
            try {
                new URL(valor);
                this.linkPagoValido = true;
                return true;
            } catch (e) {
                this.linkPagoValido = false;
                this.errorLinkPago = 'El enlace no tiene un formato válido.';
                return false;
            }
        },

        async guardarLinkPago() {
            if (!this.linkPagoActual) return;
            if (!this.validarLinkPago()) return;
            this.savingLinkPago = true;
            this.errorLinkPago = '';
            try {
                const { data } = await window.axios.post(`/api/v1/pagos/${this.linkPagoActual.id}/link-pago`, {
                    link_pago_url: this.linkPagoInput.trim(),
                }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.toast('Link guardado', 'success');
                    this.cerrarModalLinkPago();
                    await this.load();
                } else {
                    this.errorLinkPago = data.mensaje || 'Error al guardar el enlace';
                }
            } catch(e) {
                this.errorLinkPago = window.extractError(e, 'Error al guardar link');
            } finally {
                this.savingLinkPago = false;
            }
        },

        async loadRecibos() {
            try {
                let url = '/api/v1/recibos-caja?clasificar=1&per_page=50&';
                if (this.filtroRecibos.fecha_desde) url += `fecha_desde=${this.filtroRecibos.fecha_desde}&`;
                if (this.filtroRecibos.fecha_hasta) url += `fecha_hasta=${this.filtroRecibos.fecha_hasta}&`;
                if (this.filtroRecibos.estado) url += `estado=${this.filtroRecibos.estado}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                const payload = data.data || {};
                const emitir = this.filtroRecibos.estado || '';
                if (emitir === 'emitido') this.recibos = payload.emitidos || [];
                else if (emitir === 'reversado') this.recibos = payload.reversados || [];
                else if (emitir === 'anulado') this.recibos = payload.anulados || [];
                else this.recibos = [...(payload.emitidos || []), ...(payload.anulados || []), ...(payload.reversados || [])];
            } catch(e) { this.recibos = []; }
        },

        async buscarEstudiantes() {
            if (this.busquedaEstudiante.length < 2) { this.resultadosEstudiantes = []; return; }
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes?buscar=${this.busquedaEstudiante}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.resultadosEstudiantes = data.data?.data || data.data || [];
            } catch(e) {}
        },

        async openModal() {
            const hoy = window.toLocalDateInput();
            this.form = { estudiante_id: '', concepto_pago_id: '', metodo_pago_id: '', monto: 0, fecha_proceso: hoy, referencia_externa: '', observaciones: '', inventario_libro_id: '', cantidad_libro: 1, codigo_recibo: '', solicitar_link: false };
            this.busquedaEstudiante = ''; this.resultadosEstudiantes = []; this.obligacionesPendientes = []; this.obligacionesSeleccionadas = []; this.error = '';
            this.showModal = true;
            this.siguienteReciboCargado = false;
            try {
                const { data } = await window.axios.get('/api/v1/pagos/siguiente-recibo', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A' && data.data?.codigo) {
                    this.form.codigo_recibo = data.data.codigo;
                    this.siguienteReciboCargado = true;
                }
            } catch(e) {}
        },

        async registrarPago() {
            this.saving = true; this.error = '';
            try {
                const payload = { ...this.form };
                payload.solicitar_link = !!payload.solicitar_link;
                if (!this.flujo.habilita_solicitud_link) payload.solicitar_link = false;
                if (this.esMATCUO && this.obligacionesSeleccionadas.length > 0) {
                    const seleccionadas = this.obligacionesPendientes.filter(o => this.obligacionesSeleccionadas.includes(String(o.id)));
                    payload.matricula_id = seleccionadas[0]?.matricula_id || null;
                    payload.obligaciones = seleccionadas.map(o => ({ obligacion_id: o.id, monto_aplicado: o.saldo }));
                }
                const { data } = await window.axios.post('/api/v1/pagos/registrar', payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showModal = false; this.toast('Pago registrado', 'success'); await this.load(); }
                else { this.error = data.mensaje || 'Error'; }
            } catch(e) { this.error = window.extractError(e, 'Error'); } finally { this.saving = false; }
        },

        aprobar(p) {
            this.pagoAprobar = p;
            this.showConfirmarAprobacion = true;
        },

        async confirmarAprobacion() {
            if (!this.pagoAprobar) return;
            try {
                if (!this.flujo.habilita_aprobacion_pago) throw new Error('La aprobación de pago está deshabilitada para este flujo');
                const { data } = await window.axios.post(`/api/v1/pagos/${this.pagoAprobar.id}/aprobar`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.toast('Pago aprobado y recibo generado', 'success'); this.showConfirmarAprobacion = false; this.pagoAprobar = null; await this.load(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al aprobar'), 'error'); }
        },

        abrirRechazo(p) {
            this.pagoSeleccionado = p;
            this.motivoRechazo = '';
            this.showRechazo = true;
        },

        async rechazar() {
            this.saving = true;
            try {
                const { data } = await window.axios.post(`/api/v1/pagos/${this.pagoSeleccionado.id}/rechazar`, { motivo_rechazo: this.motivoRechazo }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showRechazo = false;
                    this.toast('Pago rechazado', 'success');
                    const rechazadoId = this.pagoSeleccionado.id;
                    const marcar = (lista) => lista.map(item => item.id === rechazadoId ? { ...item, estado: 'rechazado' } : item);
                    this.pagosSolicitaLink = marcar(this.pagosSolicitaLink);
                    this.pagosEnRevision = marcar(this.pagosEnRevision);
                    this.pagosPendientes = marcar(this.pagosPendientes);
                    this.pagoSeleccionado = null;
                    setTimeout(async () => {
                        this.pagosSolicitaLink = this.pagosSolicitaLink.filter(item => item.id !== rechazadoId);
                        this.pagosEnRevision = this.pagosEnRevision.filter(item => item.id !== rechazadoId);
                        this.pagosPendientes = this.pagosPendientes.filter(item => item.id !== rechazadoId);
                        await this.load();
                    }, 900);
                }
            } catch(e) { this.toast(window.extractError(e, 'Error al rechazar'), 'error'); } finally { this.saving = false; }
        },

        async eliminarPagoTotal(p) {
            if (!p) return;
            if (!confirm(`¿Eliminar por completo el pago ${p.codigo}? Esta acción borrará pago, recibo, comprobantes y aplicaciones.`)) return;
            try {
                const { data } = await window.axios.post(`/api/v1/pagos/${p.id}/eliminar-total`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showDetalle = false;
                    this.pagoSeleccionado = null;
                    this.toast('Pago eliminado por completo', 'success');
                    await this.load();
                }
            } catch(e) {
                this.toast(window.extractError(e, 'Error al eliminar el pago'), 'error');
            }
        },

        verComprobante(p) {
            this.pagoSeleccionado = p;
            this.showComprobante = true;
        },

        async verDetalle(p) {
            this.detallePago = null;
            try {
                const { data } = await window.axios.get(`/api/v1/pagos/${p.id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.detallePago = data.data || p;
            } catch(e) { this.detallePago = p; }
            this.showDetalle = true;
        },

        esImagen(c) {
            const tipo = (c.tipo_archivo || '').toLowerCase();
            return ['jpg', 'jpeg', 'png', 'image/jpeg', 'image/png'].some(t => tipo.includes(t));
        },

        async verRecibo(r) {
            try {
                const { data } = await window.axios.get(`/api/v1/recibos-caja/${r.id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.reciboSeleccionado = data.data || r;
            } catch(e) { this.reciboSeleccionado = r; }
            this.showRecibo = true;
        },

        async imprimirRecibo(r) {
            try {
                await window.axios.post(`/api/v1/recibos-caja/${r.id}/reimprimir`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
            } catch(e) {}
            window.open(`/admin/recibos/${r.id}/imprimir?auto=1`, '_blank');
            await this.loadRecibos();
        },

        abrirAnulacion(r) {
            this.reciboSeleccionado = r;
            this.motivoAnulacion = '';
            this.showAnulacion = true;
        },

        async anular() {
            this.saving = true;
            try {
                const { data } = await window.axios.post(`/api/v1/recibos-caja/${this.reciboSeleccionado.id}/anular`, { motivo_anulacion: this.motivoAnulacion }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showAnulacion = false; this.toast('Recibo anulado', 'success'); await this.loadRecibos(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al anular'), 'error'); } finally { this.saving = false; }
        },

        async loadEnlaces() {
            const token = localStorage.getItem('auth_token');
            try {
                const { data } = await window.axios.get('/api/v1/enlaces-pago', { headers: { Authorization: `Bearer ${token}` } });
                this.enlacesPago = data.data || [];
            } catch(e) { this.enlacesPago = []; }
        },

        abrirModalEnlace() {
            this.editandoEnlace = false;
            this.editandoEnlaceId = null;
            this.formEnlace = { codigo: '', nombre: '', monto: '', concepto_pago_id: '', cuenta_bancaria_id: '', fecha_vencimiento: '', usos_maximos: '', estado: 'activo' };
            this.errorEnlace = '';
            this.showModalEnlace = true;
        },

        async editarEnlace(e) {
            this.editandoEnlace = true;
            this.editandoEnlaceId = e.id;
            this.formEnlace = {
                codigo: e.codigo || '',
                nombre: e.nombre || '',
                monto: e.monto || '',
                concepto_pago_id: e.concepto_pago_id || '',
                cuenta_bancaria_id: e.cuenta_bancaria_id || '',
                fecha_vencimiento: e.fecha_vencimiento ? String(e.fecha_vencimiento).slice(0, 10) : '',
                usos_maximos: e.usos_maximos || '',
                estado: e.estado || 'activo',
            };
            this.errorEnlace = '';
            this.showModalEnlace = true;
        },

        async guardarEnlace() {
            this.savingEnlace = true; this.errorEnlace = '';
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            const payload = { ...this.formEnlace };
            if (!payload.monto) payload.monto = null;
            if (!payload.concepto_pago_id) payload.concepto_pago_id = null;
            if (!payload.cuenta_bancaria_id) payload.cuenta_bancaria_id = null;
            if (!payload.fecha_vencimiento) payload.fecha_vencimiento = null;
            if (!payload.usos_maximos) payload.usos_maximos = null;
            try {
                const { data } = this.editandoEnlace
                    ? await window.axios.post(`/api/v1/enlaces-pago/${this.editandoEnlaceId}`, payload, h)
                    : await window.axios.post('/api/v1/enlaces-pago', payload, h);
                if (data.resultado === 'A') {
                    this.showModalEnlace = false;
                    this.toast(data.mensaje, 'success');
                    await this.loadEnlaces();
                } else {
                    this.errorEnlace = data.mensaje;
                }
            } catch(e) { this.errorEnlace = window.extractError(e, 'Error al guardar'); } finally { this.savingEnlace = false; }
        },

        async eliminarEnlace(e) {
            if (!confirm('¿Está seguro de eliminar este enlace de pago?')) return;
            const token = localStorage.getItem('auth_token');
            try {
                const { data } = await window.axios.post(`/api/v1/enlaces-pago/${e.id}`, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.toast('Enlace eliminado', 'success');
                    await this.loadEnlaces();
                }
            } catch(e) { this.toast(window.extractError(e, 'Error al eliminar'), 'error'); }
        },

        fmtMonto(n) { return new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n) || 0); },
        fmtFecha(f) {
            if (!f) return '-';
            if (typeof f === 'object' && f.date) f = f.date;
            return window.formatDateLocal(f);
        },
        fmtHora(f) {
            if (!f) return '-';
            if (typeof f === 'object' && f.date) f = f.date;
            return window.formatDateLocal(f, { hour: '2-digit', minute: '2-digit', hour12: false });
        },
        toast(message, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type } })); }
    }
}
</script>
@endsection
