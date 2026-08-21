@extends('layouts.portal')
@section('title', 'Mis Pagos')
@section('content')
<div x-data="pagosView()" x-init="init()" x-cloak>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Mis Pagos</h2>
            <p class="text-sm text-gray-500">Historial y creación de pagos</p>
        </div>
        <button @click="abrirNuevoPago($event)" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nuevo Pago
        </button>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Pagos registrados</p>
                    <p class="text-2xl font-bold text-brand-700" x-text="pagos.length"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Pendientes</p>
                    <p class="text-2xl font-bold text-amber-700" x-text="pagos.filter(p => p.estado === 'pendiente').length"></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <p class="text-xs text-gray-500 mb-1">Aprobados</p>
                    <p class="text-2xl font-bold text-green-700" x-text="pagos.filter(p => p.estado === 'aprobado').length"></p>
                </div>
            </div>

            <template x-if="pagos.some(p => p.estado === 'solicita_link')">
                <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-sky-900">Tiene un link de pago pendiente</p>
                            <p class="text-xs text-sky-700">Busque los registros con estado <span class="font-medium">solicita link</span>. En este estado el pago fue solicitado y contabilidad debe cargar el enlace antes de que pueda confirmarlo.</p>
                        </div>
                        <a href="#pagos-solicita-link" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">Ir al registro</a>
                    </div>
                </div>
            </template>
            <template x-if="pagos.some(p => p.estado === 'esperando_respuesta')">
                <div class="mb-6 rounded-xl border border-purple-200 bg-purple-50 p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-purple-900">Enlace de pago disponible</p>
                            <p class="text-xs text-purple-700">Contabilidad ya publicó el enlace. Busque los registros con estado <span class="font-medium">esperando respuesta</span>, abra el link, complete el pago y confirme aquí.</p>
                        </div>
                        <a href="#pagos-esperando-respuesta" class="inline-flex items-center justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-medium text-white hover:bg-purple-700">Ir al registro</a>
                    </div>
                </div>
            </template>

            <template x-if="pagos.length === 0 && !tienePendientes">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <p class="text-gray-400">No tiene pagos registrados.</p>
                    <a href="/estudiante/matricula" class="mt-4 inline-block text-sm text-brand-600 hover:text-brand-700">Matricularme ahora →</a>
                </div>
            </template>

            <template x-if="pagos.length > 0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="pagos-solicita-link">
                    <a id="pagos-esperando-respuesta" class="block"></a>
                    <div class="md:hidden p-4 space-y-4">
                        <template x-for="p in pagos" :key="p.id">
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" :class="p.estado === 'solicita_link' ? 'ring-1 ring-sky-200 bg-sky-50/30' : p.estado === 'esperando_respuesta' ? 'ring-1 ring-purple-200 bg-purple-50/30' : ''">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <p class="font-mono text-xs text-gray-500" x-text="p.codigo || '-' "></p>
                                        <p class="font-semibold text-gray-900" x-text="p.concepto || '-' "></p>
                                        <p class="text-xs text-gray-500" x-text="p.fecha"></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium" :class="{'bg-amber-100 text-amber-700': p.estado === 'pendiente', 'bg-sky-100 text-sky-700': p.estado === 'solicita_link', 'bg-purple-100 text-purple-700': p.estado === 'esperando_respuesta', 'bg-blue-100 text-blue-700': p.estado === 'en_revision', 'bg-green-100 text-green-700': p.estado === 'aprobado', 'bg-red-100 text-red-700': p.estado === 'rechazado'}" x-text="p.estado === 'solicita_link' ? 'solicita link' : p.estado.replace('_', ' ')"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Monto</p>
                                        <p class="inline-flex items-baseline gap-1 font-semibold tabular-nums text-gray-900 whitespace-nowrap">
                                            <span class="text-xs font-medium text-gray-500">L.</span>
                                            <span x-text="fmtMonto(p.monto)"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Método</p>
                                        <p class="text-gray-700" x-text="p.metodo || '-' "></p>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 mb-3 text-xs text-gray-700 space-y-2">
                                    <template x-if="p.link_pago_url && p.metodo_pago?.permite_link_pago && p.estado !== 'aprobado'">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-2.5 py-1 text-sky-700 font-medium"><span class="w-2 h-2 rounded-full bg-sky-500"></span>Link disponible</span>
                                            <a :href="p.link_pago_url" target="_blank" class="text-blue-600 font-medium">Abrir</a>
                                        </div>
                                    </template>
                                    <template x-if="!p.link_pago_url && p.estado === 'solicita_link'">
                                        <div class="flex items-center gap-2 text-amber-700"><span class="w-2 h-2 rounded-full bg-amber-500"></span>En espera de enlace</div>
                                    </template>
                                    <template x-if="p.tiene_comprobante">
                                        <div class="text-green-700 flex items-center gap-2">
                                            <span>✓ Tiene comprobante</span>
                                            <template x-for="c in (p.comprobantes || [])" :key="c.id">
                                                <a :href="c.ruta_descarga" target="_blank" class="text-blue-600 underline text-xs">Ver comprobante</a>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-if="flujoPortal.habilita_solicitud_link && p.link_pago_url && p.estado === 'esperando_respuesta'">
                                        <button @click="confirmarLinkPago(p)" class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700">Ya completé el pago</button>
                                    </template>
                                    <template x-if="p.estado === 'rechazado' && p.motivo_rechazo">
                                        <button @click="verMotivoRechazo(p)" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Ver motivo</button>
                                    </template>
                                    <button @click="eliminarPago(p)" class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Eliminar</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="overflow-x-auto hidden md:block">
                        <table class="w-full text-sm">
                            <thead><tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Código</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Fecha</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Concepto</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Monto</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Método</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Link</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Comprobante</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Acciones</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="p in pagos" :key="p.id">
                                    <tr class="hover:bg-gray-50" :class="p.estado === 'solicita_link' ? 'bg-sky-50/70' : p.estado === 'esperando_respuesta' ? 'bg-purple-50/70' : ''">
                                        <td class="px-4 py-3 font-mono text-xs text-gray-600" x-text="p.codigo || '-' "></td>
                                        <td class="px-4 py-3 text-gray-500" x-text="p.fecha"></td>
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="p.concepto || '-'"></td>
                                        <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">
                                            <span class="inline-flex items-baseline gap-1 tabular-nums">
                                                <span class="text-xs font-medium text-gray-500">L.</span>
<span x-text="fmtMonto(p.monto)"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500" x-text="p.metodo || '-'"></td>
                                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="{'bg-amber-100 text-amber-700': p.estado === 'pendiente', 'bg-sky-100 text-sky-700': p.estado === 'solicita_link', 'bg-purple-100 text-purple-700': p.estado === 'esperando_respuesta', 'bg-blue-100 text-blue-700': p.estado === 'en_revision', 'bg-green-100 text-green-700': p.estado === 'aprobado', 'bg-red-100 text-red-700': p.estado === 'rechazado'}" x-text="p.estado === 'solicita_link' ? 'solicita link' : p.estado.replace('_', ' ')"></span></td>
                                        <td class="px-4 py-3">
                                            <div class="min-w-[160px] space-y-1">
                                                <template x-if="p.link_pago_url && p.metodo_pago?.permite_link_pago && p.estado !== 'aprobado'">
                                                    <a :href="p.link_pago_url" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 border border-sky-200 hover:bg-sky-100 hover:text-sky-800">
                                                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                                        Abrir link
                                                    </a>
                                                </template>
                                                <template x-if="!p.link_pago_url && p.estado === 'solicita_link'">
                                                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 border border-amber-200">
                                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                        Pendiente
                                                    </div>
                                                </template>
                                                <template x-if="!p.link_pago_url && p.estado !== 'solicita_link' && p.estado !== 'esperando_respuesta'">
                                                    <span class="text-xs text-gray-400">—</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                                <div class="mb-2 sm:hidden rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                                    <p class="text-[11px] text-gray-500 uppercase tracking-wide">Código</p>
                                                    <p class="font-mono text-sm font-semibold text-gray-800" x-text="p.codigo || '-' "></p>
                                                </div>
                                                <template x-if="p.matricula_codigo">
                                                    <div class="mb-2 text-[11px] text-gray-500">
                                                        <p class="font-medium text-gray-700" x-text="p.matricula_codigo"></p>
                                                        <p x-text="(p.matricula_nivel || 'Matrícula') + ' · ' + (p.matricula_estado || '-')"></p>
                                                        <p x-text="'Monto aplicado: ' + fmtMonto(p.obligaciones_total || 0) + ' L.'"></p>
                                                    </div>
                                                </template>
                                                <template x-if="p.estado === 'solicita_link'">
                                                    <div class="mb-2 rounded-lg border border-sky-200 bg-white/70 p-3 text-xs text-sky-800">
                                                        <p class="font-semibold">Pago en espera de confirmación</p>
                                                        <p>La solicitud está en revisión. Contabilidad debe publicar el enlace.</p>
                                                    </div>
                                                </template>
                                                <template x-if="p.link_pago_url && p.estado === 'esperando_respuesta'">
                                                    <div class="mb-2 rounded-lg border border-purple-200 bg-white/70 p-3 text-xs text-purple-800">
                                                        <p class="font-semibold">Enlace disponible</p>
                                                        <p>Contabilidad ya publicó el enlace. Abra el link, complete el pago externo y confirme aquí.</p>
                                                    </div>
                                                </template>
                                                <template x-if="flujoPortal.habilita_carga_comprobante && (p.estado === 'pendiente' || p.estado === 'rechazado')">
                                                    <button @click="subirComprobantePago(p)" class="text-xs text-brand-600 font-medium hover:text-brand-700">Subir comprobante</button>
                                                </template>
                                                <template x-if="flujoPortal.habilita_solicitud_link && p.link_pago_url && p.estado === 'esperando_respuesta'">
                                                    <div class="space-y-1">
                                                        <a :href="p.link_pago_url" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Abrir link</a>
                                                        <button @click="copiarLinkPago(p)" class="text-xs text-gray-600 font-medium hover:text-gray-800">Copiar link</button>
                                                        <button @click="confirmarLinkPago(p)" class="inline-flex items-center justify-center rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 hover:bg-brand-100">Ya completé el pago</button>
                                                    </div>
                                                </template>
                                            <template x-if="p.tiene_comprobante">
                                                <div class="space-y-1">
                                                    <span class="text-xs text-green-600 font-medium">✓ Subido</span>
                                                    <template x-for="c in (p.comprobantes || [])" :key="c.id">
                                                        <div class="flex items-center gap-2 text-[11px]">
                                                            <span class="text-gray-600 truncate max-w-[120px]" x-text="c.nombre_archivo"></span>
                                                            <a :href="c.ruta_descarga" target="_blank" class="text-blue-600 underline font-medium whitespace-nowrap">Ver</a>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="p.estado !== 'pendiente' && !p.tiene_comprobante">
                                                <span class="text-xs text-gray-400">—</span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <template x-if="flujoPortal.habilita_solicitud_link && p.link_pago_url && p.estado === 'esperando_respuesta'">
                                                    <button @click="confirmarLinkPago(p)" class="text-xs text-brand-600 font-medium hover:text-brand-700">Ya completé el pago</button>
                                                </template>
                                                <template x-if="p.estado === 'rechazado' && p.motivo_rechazo">
                                                    <button @click="verMotivoRechazo(p)" class="text-xs text-red-600 font-medium hover:text-red-700">Ver motivo</button>
                                                </template>
                                                <template x-if="true">
                                                    <button @click="eliminarPago(p)" class="text-xs text-red-600 font-medium hover:text-red-700">Eliminar</button>
                                                </template>
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

    {{-- Modal: Nuevo Pago --}}
    <template x-if="showModal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Nuevo Pago</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>

                <template x-if="modalLoading">
                    <div class="flex justify-center py-8"><div class="animate-spin rounded-full h-6 w-6 border-2 border-brand-500/20 border-t-brand-500"></div></div>
                </template>

                <template x-if="!modalLoading && matriculasPendientes.length === 0">
                    <div class="text-center py-8">
                        <p class="text-gray-400 mb-3">No tiene obligaciones pendientes en este momento.</p>
                        <a href="/estudiante/matricula" class="text-sm text-brand-600 hover:text-brand-700">Matricularme ahora →</a>
                    </div>
                </template>

                <template x-if="!modalLoading && matriculasPendientes.length > 1">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-amber-900 mb-2">Seleccione la matrícula a pagar</label>
                        <select x-model="matriculaSeleccionadaId" @change="cambiarMatriculaSeleccionada()" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <template x-for="m in matriculasPendientes" :key="m.id">
                                <option :value="m.id" x-text="m.codigo + ' · ' + (m.nivel || 'Matrícula') + ' · L. ' + fmtMonto(totalSeleccionado(m))"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <template x-if="!modalLoading && matriculasPendientes.length > 0">
                    <div class="space-y-4">
                        <template x-for="m in matriculasPendientes" :key="m.id">
                            <div class="border border-gray-200 rounded-lg p-4" :class="m.estado === 'solicita_link' ? 'ring-1 ring-sky-200 bg-sky-50/30' : ''">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm" x-text="m.nivel || 'Matrícula'"></p>
                                        <p class="text-xs text-gray-400" x-text="m.codigo"></p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="m.estado === 'solicita_link' ? 'bg-sky-100 text-sky-700' : (m.estado === 'reservada' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700')" x-text="m.estado === 'solicita_link' ? 'solicita link' : m.estado"></span>
                                </div>
                                <p class="text-xs text-gray-500 mb-2" x-text="m.horario ? m.horario + ' · ' + m.regimen : m.regimen"></p>
                                <p class="text-xs text-gray-500 mb-3" x-text="'Obligaciones pendientes: ' + m.obligaciones.length + ' · Total: L. ' + fmtMonto(m.obligaciones.reduce((s, o) => s + Number(o.saldo || 0), 0))"></p>

                                <div class="flex items-center gap-2 mb-2 text-xs">
                                    <button @click="seleccionarTodas(m)" class="text-brand-600 font-medium hover:text-brand-700">Seleccionar todo</button>
                                    <button @click="deseleccionarTodas(m)" class="text-gray-500 font-medium hover:text-gray-700">Ninguno</button>
                                    <template x-if="totalSeleccionado(m) > 0">
                                        <span class="ml-auto font-semibold text-brand-700" x-text="'Subtotal: ' + fmtMonto(totalSeleccionado(m)) + ' L.'"></span>
                                    </template>
                                </div>

                                <div class="space-y-1">
                                    <template x-for="o in m.obligaciones" :key="o.id">
                                        <label class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-colors"
                                            :class="estaSeleccionada(m.id, o.id) ? 'bg-brand-50 border border-brand-200' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100'">
                                            <input type="checkbox"
                                                :checked="estaSeleccionada(m.id, o.id)"
                                                @change="toggleObligacion(m.id, o.id)"
                                                class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                            <span class="flex-1 text-sm text-gray-700" x-text="o.nombre_cargo"></span>
                                            <span class="text-sm font-semibold" :class="estaSeleccionada(m.id, o.id) ? 'text-brand-700' : 'text-gray-900'" x-text="fmtMonto(o.saldo) + ' L.'"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="border-t border-gray-200 pt-4 space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                                <select x-model="form.metodo_pago_id" @change="alCambiarMetodo()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Seleccionar...</option>
                                    <template x-for="mp in metodosPago" :key="mp.id">
                                        <option :value="mp.id" x-text="mp.nombre"></option>
                                    </template>
                                </select>
                            </div>

                            <template x-if="!esMetodoTarjeta(form.metodo_pago_id)">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Referencia
                                        <span x-show="esMetodoValidable(form.metodo_pago_id)" class="text-red-500">*</span>
                                        <span x-show="!esMetodoValidable(form.metodo_pago_id)" class="text-gray-400">(opcional)</span>
                                    </label>
                                    <input x-model="form.referencia" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Número de referencia o comprobante">
                                </div>
                            </template>

                             <template x-if="esMetodoValidable(form.metodo_pago_id)">
                                 <div>
                                     <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago <span class="text-red-500">*</span></label>
                                     <input x-model="form.fecha_pago" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :max="hoyStr()">
                                     <p class="text-xs text-gray-400 mt-1">Fecha en la que realizó el depósito o transferencia.</p>
                                 </div>
                             </template>
                             <template x-if="esMetodoValidable(form.metodo_pago_id)">
                                 <div>
                                     <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta bancaria donde realizó el pago <span class="text-red-500">*</span></label>
                                     <select x-model="form.cuenta_bancaria_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"><option value="">Seleccionar...</option><template x-for="cuenta in cuentasBancarias" :key="cuenta.id"><option :value="cuenta.id" x-text="cuenta.banco + ' — ' + cuenta.numero_cuenta + ' (' + cuenta.tipo_cuenta + ')'"></option></template></select>
                                     <p x-show="cuentasBancarias.length === 0" class="mt-1 text-xs text-amber-700">No hay cuentas bancarias activas configuradas para recibir depósitos o transferencias.</p>
                                 </div>
                             </template>

                            <template x-if="!esMetodoTarjeta(form.metodo_pago_id) && !esMetodoLink(form.metodo_pago_id)">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Comprobante
                                        <span x-text="flujoPortal.requiere_comprobante ? ' *' : ' (opcional)'"></span>
                                    </label>
                                    <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="handleFileChange($event)" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                                    <p x-show="flujoPortal.requiere_comprobante" class="text-xs text-red-500 mt-1">Este campo es obligatorio</p>
                                    <p x-show="formArchivoError" class="text-xs text-red-500 mt-1" x-text="formArchivoError"></p>
                                    <p x-show="form.archivo && !formArchivoError" class="text-xs text-green-600 mt-1">
                                        <span x-text="form.archivo.name + ' (' + (form.archivo.size / 1024 / 1024).toFixed(1) + ' MB)'"></span>
                                    </p>
                                </div>
                            </template>

                            <template x-if="esMetodoLink(form.metodo_pago_id)">
                                <div>
                                    <template x-if="enlacesDisponibles.length === 0">
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                                            <p class="text-sm font-semibold text-amber-800 mb-1">Pago por Link</p>
                                            <p class="text-xs text-amber-600">Seleccione sus obligaciones para generar el link de pago.</p>
                                        </div>
                                    </template>
                                    <template x-if="enlacesDisponibles.length > 0">
                                        <div class="space-y-3">
                                            <template x-for="enl in enlacesDisponibles" :key="enl.id">
                                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                    <p class="text-sm font-semibold text-blue-800 mb-2" x-text="enl.nombre"></p>
                                                    <div class="text-xs text-blue-700 space-y-1">
                                                        <p><span class="font-medium">Banco:</span> <span x-text="enl.cuenta_bancaria?.banco || '-'"></span></p>
                                                        <p><span class="font-medium">Cuenta:</span> <span class="font-mono" x-text="enl.cuenta_bancaria?.numero_cuenta || '-'"></span></p>
                                                        <p><span class="font-medium">Tipo:</span> <span x-text="enl.cuenta_bancaria?.tipo_cuenta || '-'"></span></p>
                                                        <p x-show="enl.monto"><span class="font-medium">Monto:</span> <span x-text="fmtMonto(enl.monto) + ' L.'"></span></p>
                                                    </div>
                                                    <p class="text-xs text-blue-500 mt-2">Realice su depósito o transferencia a esta cuenta y suba su comprobante.</p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="esMetodoTarjeta(form.metodo_pago_id)">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                                    <p class="text-sm font-semibold text-blue-800 mb-1">Pago con Tarjeta</p>
                                    <p class="text-xs text-blue-600">Será redirigido a PayPal para completar el pago de forma segura.</p>
                                </div>
                            </template>
                        </div>

                        <template x-if="modalError"><p class="text-sm text-red-600" x-text="modalError"></p></template>

                        <div class="flex gap-3 pt-2">
                            <button @click="showModal = false" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Cancelar</button>
                            <button @click="procesarPago()" :disabled="enviando || !form.metodo_pago_id" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 disabled:opacity-50">
                                <span x-show="!enviando" x-text="esMetodoTarjeta(form.metodo_pago_id) ? 'Pagar con PayPal' : 'Pagar'"></span>
                                <span x-show="enviando">Procesando...</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Modal: Subir Comprobante a pago existente --}}
    <template x-if="selectedPago">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="selectedPago = null"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Subir Comprobante</h3>
                <p class="text-sm text-gray-500 mb-4">Pago: <span class="font-medium" x-text="selectedPago.codigo"></span> — <span class="inline-flex items-baseline gap-1 font-bold tabular-nums text-gray-900 whitespace-nowrap"><span class="text-xs font-medium text-gray-500">L.</span><span x-text="fmtMonto(selectedPago.monto)"></span></span></p>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Método de pago *</label>
                        <select x-model="formComp.metodo_pago_id" :disabled="!!selectedPago?.metodo_pago_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed">
                            <option value="">Seleccionar...</option>
                            <template x-for="mp in metodosPago" :key="mp.id">
                                <option :value="mp.id" x-text="mp.nombre"></option>
                            </template>
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Referencia <span x-show="esMetodoValidable(formComp.metodo_pago_id)" class="text-red-500">*</span></label><input x-model="formComp.referencia" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Número de referencia"></div>
                    <template x-if="esMetodoValidable(formComp.metodo_pago_id)">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago <span class="text-red-500">*</span></label><input x-model="formComp.fecha_pago" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" :max="hoyStr()"></div>
                    </template>
                    <template x-if="esMetodoValidable(formComp.metodo_pago_id)">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Cuenta bancaria donde realizó el pago <span class="text-red-500">*</span></label><select x-model="formComp.cuenta_bancaria_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"><option value="">Seleccionar...</option><template x-for="cuenta in cuentasBancarias" :key="cuenta.id"><option :value="cuenta.id" x-text="cuenta.banco + ' — ' + cuenta.numero_cuenta + ' (' + cuenta.tipo_cuenta + ')'"></option></template></select><p x-show="cuentasBancarias.length === 0" class="mt-1 text-xs text-amber-700">No hay cuentas bancarias activas configuradas para recibir depósitos o transferencias.</p></div>
                    </template>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (JPG, PNG, PDF, máx 5MB) *</label><input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="handleCompFileChange($event)" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"><p x-show="compFileError" class="text-xs text-red-500 mt-1" x-text="compFileError"></p></div>
                </div>
                <template x-if="uploadError"><p class="text-sm text-red-600 mt-3" x-text="uploadError"></p></template>
                <div class="flex gap-3 mt-6">
                    <button @click="selectedPago = null; uploadError = ''" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cancelar</button>
                    <button @click="enviarComprobante()" :disabled="uploading" class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        <span x-show="!uploading">Subir</span><span x-show="uploading">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="showMotivoRechazo && pagoMotivo">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showMotivoRechazo = false; pagoMotivo = null"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Motivo de rechazo</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Pago <span class="font-mono font-medium" x-text="pagoMotivo?.codigo"></span>
                </p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 whitespace-pre-wrap" x-text="pagoMotivo?.motivo_rechazo || 'Sin detalle' "></div>
                <div class="flex justify-end mt-4">
                    <button @click="showMotivoRechazo = false; pagoMotivo = null" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium">Cerrar</button>
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

function pagosView() {
    return {
        loading: true, pagos: [], tienePendientes: false,
        flujoPortal: { habilita_carga_comprobante: true, requiere_comprobante: true, habilita_solicitud_link: true },

        showModal: false, modalLoading: false, triggerElement: null,
        matriculasPendientes: [], metodosPago: [], cuentasBancarias: [], enlacesDisponibles: [],
        selectedObligaciones: {},
        form: { metodo_pago_id: '', cuenta_bancaria_id: '', referencia: '', fecha_pago: '', archivo: null },
        formArchivoError: '',
        enviando: false, modalError: '',
        matriculaSeleccionadaId: null,
        flujoPagoMatricula: null,

        selectedPago: null,
        pagoMotivo: null,
        showMotivoRechazo: false,
        formComp: { metodo_pago_id: '', cuenta_bancaria_id: '', referencia: '', fecha_pago: '', archivo: null },
        uploading: false, uploadError: '', compFileError: '',
        estudianteActualId: null,

        token() { return localStorage.getItem('estudiante_token'); },

        estudianteId() {
            return this.estudianteActualId || null;
        },

        filtrarPagosEstudiante(lista) {
            const id = this.estudianteId();
            if (!id) return Array.isArray(lista) ? lista : [];
            return (Array.isArray(lista) ? lista : []).filter(item => String(item.estudiante_id || '') === String(id));
        },

        esMetodoTarjeta(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return mp?.proveedor_pago?.codigo === 'PAYPAL' || mp?.requiere_proveedor;
        },

        esMetodoLink(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return !!mp?.permite_link_pago;
        },

        esMetodoValidable(id) {
            if (!id) return false;
            const mp = this.metodosPago.find(m => m.id == id);
            return mp?.codigo === 'DEP' || mp?.codigo === 'TRA';
        },

        hoyStr() {
            const d = new Date();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + mm + '-' + dd;
        },

        async handleFileChange(event) {
            const file = event.target.files[0];
            this.formArchivoError = '';
            this.form.archivo = null;
            if (!file) return;
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) { this.formArchivoError = 'El archivo no debe superar los 5 MB'; return; }
            const isImage = /^image\/(jpeg|png|gif|webp)/.test(file.type);
            if (!isImage) { this.form.archivo = file; return; }
            try {
                const img = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const i = new Image();
                        i.onload = () => resolve(i);
                        i.onerror = reject;
                        i.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                });
                const MAX_W = 1920, MAX_H = 1920;
                let { width, height } = img;
                if (width > MAX_W || height > MAX_H) {
                    const ratio = Math.min(MAX_W / width, MAX_H / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
                if (blob.size > maxSize) { this.formArchivoError = 'La imagen comprimida aún supera los 5 MB'; return; }
                this.form.archivo = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
            } catch (e) { this.form.archivo = file; }
        },

        async handleCompFileChange(event) {
            const file = event.target.files[0];
            this.compFileError = '';
            this.formComp.archivo = null;
            if (!file) return;
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) { this.compFileError = 'El archivo no debe superar los 5 MB'; return; }
            const isImage = /^image\/(jpeg|png|gif|webp)/.test(file.type);
            if (!isImage) { this.formComp.archivo = file; return; }
            try {
                const img = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = e => { const i = new Image(); i.onload = () => resolve(i); i.onerror = reject; i.src = e.target.result; };
                    reader.readAsDataURL(file);
                });
                const MAX_W = 1920, MAX_H = 1920;
                let { width, height } = img;
                if (width > MAX_W || height > MAX_H) {
                    const ratio = Math.min(MAX_W / width, MAX_H / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width = width; canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
                if (blob.size > maxSize) { this.compFileError = 'La imagen comprimida aún supera los 5 MB'; return; }
                this.formComp.archivo = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
            } catch (e) { this.formComp.archivo = file; }
        },

        alCambiarMetodo() {
            this.modalError = ''; this.formArchivoError = '';
            this.enlacesDisponibles = [];
            if (this.esMetodoTarjeta(this.form.metodo_pago_id)) {
                this.form.referencia = '';
                this.form.fecha_pago = '';
                this.form.cuenta_bancaria_id = '';
                this.form.archivo = null;
            }
            if (this.esMetodoLink(this.form.metodo_pago_id)) {
                this.form.referencia = '';
                this.form.fecha_pago = '';
                this.form.cuenta_bancaria_id = '';
                this.cargarEnlacesDisponibles();
            }
        },

        async cargarEnlacesDisponibles() {
            const token = this.token();
            try {
                const { data } = await window.axios.get('/api/v1/estudiantes/enlaces-pago', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.enlacesDisponibles = data.data || [];
                }
            } catch(e) {
                this.enlacesDisponibles = [];
            }
        },

        estaSeleccionada(matriculaId, obligacionId) {
            return this.selectedObligaciones[matriculaId]?.includes(obligacionId) ?? false;
        },
        toggleObligacion(matriculaId, obligacionId) {
            if (!this.selectedObligaciones[matriculaId]) this.selectedObligaciones[matriculaId] = [];
            const idx = this.selectedObligaciones[matriculaId].indexOf(obligacionId);
            if (idx === -1) this.selectedObligaciones[matriculaId].push(obligacionId);
            else this.selectedObligaciones[matriculaId].splice(idx, 1);
        },
        seleccionarTodas(m) {
            this.selectedObligaciones[m.id] = m.obligaciones.map(o => o.id);
            this.matriculaSeleccionadaId = m.id;
        },
        deseleccionarTodas(m) {
            this.selectedObligaciones[m.id] = [];
        },
        cambiarMatriculaSeleccionada() {
            const m = this.matriculasPendientes.find(item => item.id == this.matriculaSeleccionadaId);
            if (m) {
                this.selectedObligaciones = {};
                this.seleccionarTodas(m);
                this.aplicarSeleccionObligacionesPorFlujo(m);
            }
        },
        aplicarSeleccionObligacionesPorFlujo(m) {
            if ((this.flujoPagoMatricula?.habilita_seleccion_obligaciones ?? true)) return;
            if (!m) return;
            this.selectedObligaciones[m.id] = m.obligaciones.map(o => o.id);
        },
        totalSeleccionado(m) {
            const ids = this.selectedObligaciones[m.id] || [];
            return m.obligaciones.filter(o => ids.includes(o.id)).reduce((s, o) => s + parseFloat(o.saldo || 0), 0);
        },

        async init() {
            const token = this.token();
            if (!token) { window.location.href = '/estudiante/login'; return; }
            this.pagos = [];
            try {
                const flujoRes = await window.axios.get('/api/v1/seguridad/configuraciones-flujo-matricula', { headers: { Authorization: `Bearer ${token}` } }).catch(() => null);
                const flujos = flujoRes?.data?.data?.data || flujoRes?.data?.data || [];
                this.flujoPortal = flujos.find(c => c.estado === 'activo' && c.origen === 'portal_estudiante') || this.flujoPortal;
                const portalRes = await window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } });
                if (portalRes.data?.resultado === 'A') {
                    this.estudianteActualId = portalRes.data.data?.estudiante?.id || null;
                    this.flujoPagoMatricula = portalRes.data.data?.flujo_pago_matricula || null;
                    this.cuentasBancarias = portalRes.data.data?.cuentas_bancarias || [];
                    localStorage.setItem('estudiante_data', JSON.stringify(portalRes.data.data?.estudiante || null));
                }
                const { data } = await window.axios.get('/api/v1/estudiantes/mis-pagos', { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') this.pagos = this.filtrarPagosEstudiante(data.data);
            } catch(e) {
                this.pagos = [];
                if (e.response?.status === 401) window.location.href = '/estudiante/login';
            }
            finally { this.loading = false; }
            this.pollingInterval = setInterval(() => this.loadPagos(), 30000);
        },

        async abrirNuevoPago(event) {
            this.showModal = true;
            this.modalLoading = true;
            this.modalError = ''; this.formArchivoError = '';
            this.form = { metodo_pago_id: '', cuenta_bancaria_id: '', referencia: '', fecha_pago: '', archivo: null };
            this.selectedObligaciones = {};
            this.matriculasPendientes = [];
            this.tienePendientes = false;
            this.matriculaSeleccionadaId = null;
            this.triggerElement = event ? event.target : null;
            const token = this.token();
            try {
                const [metodosRes, cuentasRes, portalRes] = await Promise.allSettled([
                    window.axios.get('/api/v1/estudiantes/metodos-pago'),
                    window.axios.get('/api/v1/estudiantes/cuentas-bancarias', { headers: { Authorization: `Bearer ${token}` } }),
                    window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } }),
                ]);
                if (metodosRes.status === 'fulfilled' && metodosRes.value.data.resultado === 'A') {
                    this.metodosPago = metodosRes.value.data.data || [];
                }
                if (cuentasRes.status === 'fulfilled' && cuentasRes.value.data.resultado === 'A') {
                    this.cuentasBancarias = cuentasRes.value.data.data || [];
                }
                if (portalRes.status === 'fulfilled' && portalRes.value.data.resultado === 'A') {
                    const data = portalRes.value.data.data;
                    this.estudianteActualId = data?.estudiante?.id || this.estudianteActualId;
                    this.flujoPagoMatricula = data?.flujo_pago_matricula || this.flujoPagoMatricula;
                    this.cuentasBancarias = data?.cuentas_bancarias || this.cuentasBancarias;
                    this.matriculasPendientes = data.matriculas_pendientes || [];
                    if (this.matriculasPendientes.length > 0) {
                        this.tienePendientes = true;
                        this.matriculaSeleccionadaId = this.matriculasPendientes[0].id;
                        this.seleccionarTodas(this.matriculasPendientes[0]);
                        this.aplicarSeleccionObligacionesPorFlujo(this.matriculasPendientes[0]);
                    }
                }
            } catch(e) {
                if (e.response?.status === 401) window.location.href = '/estudiante/login';
            } finally { this.modalLoading = false; }
        },

        async procesarPago() {
            if (!this.form.metodo_pago_id) { this.modalError = 'Seleccione un método de pago'; return; }
            if (this.matriculasPendientes.length === 0) { this.modalError = 'No hay obligaciones pendientes'; return; }
            if (!this.esMetodoLink(this.form.metodo_pago_id) && this.flujoPortal.requiere_comprobante && !this.form.archivo) {
                this.modalError = 'Debe adjuntar un comprobante de pago'; return;
            }
            if (this.esMetodoLink(this.form.metodo_pago_id)) {
                const pagosConSolicitud = this.pagos.filter(p => ['solicita_link','esperando_respuesta','en_revision'].includes(p.estado) && p.obligaciones_seleccionadas && p.obligaciones_seleccionadas.length > 0);
                if (pagosConSolicitud.length > 0) {
                    this.modalError = 'Ya tiene una solicitud de link en proceso. Debe esperar la respuesta de contabilidad antes de solicitar otro link para las mismas obligaciones.';
                    return;
                }
            }
            this.enviando = true; this.modalError = '';
            const token = this.token();
            const m = this.matriculasPendientes.find(item => item.id == this.matriculaSeleccionadaId) || this.matriculasPendientes[0];
            const ids = this.selectedObligaciones[m.id] || [];
            if (ids.length === 0) { this.modalError = 'Seleccione al menos una obligación'; this.enviando = false; return; }

            try {
                if (this.esMetodoTarjeta(this.form.metodo_pago_id)) {
                    const { data } = await window.axios.post('/api/v1/estudiantes/pago-tarjeta/iniciar', {
                        matricula_id: m.id,
                        metodo_pago_id: this.form.metodo_pago_id,
                        obligacion_ids: ids,
                    }, { headers: { Authorization: `Bearer ${token}` } });
                    if (data.resultado === 'A') {
                        window.location.href = data.data.redirect_url;
                    } else {
                        this.modalError = data.mensaje;
                    }
                } else {
                    if (this.esMetodoValidable(this.form.metodo_pago_id)) {
                        if (!this.form.referencia || !this.form.referencia.trim()) { this.modalError = 'Ingrese el número de referencia'; this.enviando = false; return; }
                        if (!this.form.fecha_pago) { this.modalError = 'Ingrese la fecha de pago'; this.enviando = false; return; }
                        if (!this.form.cuenta_bancaria_id) { this.modalError = 'Seleccione la cuenta bancaria donde realizó el pago'; this.enviando = false; return; }
                    }
                    const payload = {
                        matricula_id: m.id,
                        metodo_pago_id: this.form.metodo_pago_id,
                        referencia: this.form.referencia || '',
                        cuenta_bancaria_id: this.form.cuenta_bancaria_id || null,
                        obligacion_ids: ids,
                    };
                    if (this.form.fecha_pago) payload.fecha_pago = this.form.fecha_pago;
                    if (this.esMetodoLink(this.form.metodo_pago_id)) payload.solicitar_link = true;
                    const { data } = await window.axios.post('/api/v1/estudiantes/registrar-pago', payload, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    if (data.resultado === 'A') {
                        const pagoId = data.data.pago_id;
                        if (this.form.archivo && !this.esMetodoLink(this.form.metodo_pago_id)) {
                            const fd = new FormData();
                            fd.append('pago_id', pagoId);
                            fd.append('metodo_pago_id', this.form.metodo_pago_id);
                            fd.append('referencia', this.form.referencia || '');
                            if (this.form.cuenta_bancaria_id) fd.append('cuenta_bancaria_id', this.form.cuenta_bancaria_id);
                            if (this.form.fecha_pago) fd.append('fecha_pago', this.form.fecha_pago);
                            fd.append('comprobante', this.form.archivo);
                            await window.axios.post('/api/v1/estudiantes/subir-comprobante', fd, {
                                headers: { Authorization: `Bearer ${token}` }
                            });
                        }
                        this.showModal = false;
                        if (this.triggerElement) {
                            this.triggerElement.focus();
                            this.triggerElement = null;
                        }
                        this.loadPagos();
                        if (data.data?.alerta_duplicado) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Pago registrado. La referencia será verificada por contabilidad.', type: 'warning' } }));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-toast', {
                                detail: { message: 'Pago en proceso. Verifique en el historial de pagos su estado final.', type: 'success' }
                            }));
                        }
                    } else {
                        this.modalError = data.mensaje;
                    }
                }
            } catch(e) {
                this.modalError = window.extractError(e, 'Error al procesar el pago');
            } finally { this.enviando = false; }
        },

        async loadPagos() {
            const token = this.token();
            try {
                this.pagos = [];
                const { data } = await window.axios.get('/api/v1/estudiantes/mis-pagos', { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') this.pagos = this.filtrarPagosEstudiante(data.data);
            } catch(e) {
                this.pagos = [];
                if (e.response?.status === 401) window.location.href = '/estudiante/login';
            }
        },

        async confirmarLinkPago(p) {
            if (!this.flujoPortal.habilita_solicitud_link) return;
            const token = this.token();
            try {
                const { data } = await window.axios.post('/api/v1/estudiantes/confirmar-link-pago', { pago_id: p.id }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    p.estado = 'en_revision';
                    this.loadPagos();
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje, type: 'success' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje, type: 'error' } }));
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'No se pudo confirmar el pago'), type: 'error' } }));
            }
        },

        async copiarLinkPago(p) {
            if (!p?.link_pago_url) return;
            try {
                await navigator.clipboard.writeText(p.link_pago_url);
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Link copiado', type: 'success' } }));
            } catch (e) {
                window.open(p.link_pago_url, '_blank', 'noopener');
            }
        },

        async subirComprobantePago(p) {
            if (this.cuentasBancarias.length === 0) {
                const token = this.token();
                try {
                    const { data } = await window.axios.post('/api/v1/estudiantes/portal', {}, { headers: { Authorization: `Bearer ${token}` } });
                    if (data?.resultado === 'A') this.cuentasBancarias = data.data?.cuentas_bancarias || [];
                } catch (e) {
                    if (e.response?.status === 401) window.location.href = '/estudiante/login';
                }
            }
            this.selectedPago = p;
            this.formComp = { metodo_pago_id: p.metodo_pago_id || '', cuenta_bancaria_id: p.cuenta_bancaria_id || '', referencia: '', fecha_pago: '', archivo: null };
            this.uploadError = '';
        },

        verMotivoRechazo(p) {
            this.pagoMotivo = p;
            this.showMotivoRechazo = true;
        },



        async eliminarPago(p) {
            if (!confirm('¿Desea eliminar este pago? Esta acción no se puede deshacer.')) return;
            const token = this.token();
            try {
                const { data } = await window.axios.post(`/api/v1/estudiantes/mis-pagos/${p.id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    await this.loadPagos();
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.mensaje || 'Pago eliminado', type: 'success' }
                    }));
                } else {
                    this.uploadError = data.mensaje || 'No se pudo eliminar el pago';
                }
            } catch(e) {
                this.uploadError = window.extractError(e, 'No se pudo eliminar el pago');
            }
        },

        async enviarComprobante() {
            if (!this.flujoPortal.habilita_carga_comprobante) { this.uploadError = 'La carga de comprobantes está deshabilitada'; return; }
            if (!this.formComp.archivo || !this.formComp.metodo_pago_id) { this.uploadError = 'Seleccione método de pago y archivo'; return; }
            if (this.esMetodoValidable(this.formComp.metodo_pago_id)) {
                if (!this.formComp.referencia || !this.formComp.referencia.trim()) { this.uploadError = 'Ingrese el número de referencia'; return; }
                if (!this.formComp.fecha_pago) { this.uploadError = 'Ingrese la fecha de pago'; return; }
                if (!this.formComp.cuenta_bancaria_id) { this.uploadError = 'Seleccione la cuenta bancaria donde realizó el pago'; return; }
            }
            this.uploading = true; this.uploadError = '';
            const token = this.token();
            try {
                const fd = new FormData();
                fd.append('pago_id', this.selectedPago.id);
                fd.append('metodo_pago_id', this.formComp.metodo_pago_id);
                fd.append('referencia', this.formComp.referencia);
                if (this.formComp.cuenta_bancaria_id) fd.append('cuenta_bancaria_id', this.formComp.cuenta_bancaria_id);
                if (this.formComp.fecha_pago) fd.append('fecha_pago', this.formComp.fecha_pago);
                fd.append('comprobante', this.formComp.archivo);
                const { data } = await window.axios.post('/api/v1/estudiantes/subir-comprobante', fd, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.selectedPago = null;
                    this.formComp = { metodo_pago_id: '', cuenta_bancaria_id: '', referencia: '', fecha_pago: '', archivo: null };
                    this.loadPagos();
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Comprobante subido exitosamente', type: 'success' }
                    }));
                } else {
                    this.uploadError = data.mensaje;
                }
            } catch(e) {
                this.uploadError = window.extractError(e, 'Error al subir el comprobante');
            } finally { this.uploading = false; }
        },
    };
}
</script>
@endsection
