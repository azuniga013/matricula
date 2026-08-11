@extends('layouts.admin')
@section('content')
<div x-data="estudiantes()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Estudiantes</h1>
            <p class="page-subtitle">Gestión de estudiantes y ficha integral</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="loadEstudiantes()" class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            </button>
            <button x-show="api.hasPermission('estudiantes.registro.crear')" @click="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nuevo Estudiante
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="label">Buscar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg></div>
                        <input x-model="search" @input.debounce.300ms="loadEstudiantes()" type="text" placeholder="Buscar por código, nombre o identidad..." class="input pl-10">
                    </div>
                </div>
                <div>
                    <label class="label">Sucursal</label>
                    <select x-model="filtroSucursal" @change="loadEstudiantes()" class="input">
                        <option value="">Todas</option>
                        <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
    </template>

    <template x-if="!loading">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Identidad</th><th>Correo</th><th>Teléfono</th><th>Sucursal</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                    <tbody>
                        <template x-for="e in estudiantes" :key="e.id">
                            <tr>
                                <td class="font-mono text-xs font-semibold text-brand-600" x-text="e.codigo"></td>
                                <td class="font-medium" x-text="e.nombre + ' ' + e.apellido"></td>
                                <td class="text-gray-500 font-mono text-xs" x-text="e.identidad ? maskId(e.identidad) : '-'"></td>
                                <td class="text-gray-500" x-text="e.correo ? maskEmail(e.correo) : '-'"></td>
                                <td class="text-gray-500" x-text="e.telefono ? maskPhone(e.telefono) : '-'"></td>
                                <td x-text="e.sucursal?.nombre || '-'"></td>
                                <td><span :class="e.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="e.estado"></span></td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="viewFicha(e)" class="btn btn-ghost btn-sm">Ficha</button>
                                        <button x-show="api.hasPermission('estudiantes.registro.modificar')" @click="editEstudiante(e)" class="btn btn-ghost btn-sm">Editar</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    {{-- Ficha Integral Modal (AGENTS §4.6.1) --}}
    <div x-show="showFicha" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showFicha = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Ficha Integral del Estudiante</h3>
                    <p class="text-xs text-gray-400" x-text="ficha ? (ficha.codigo + ' · ' + ficha.nombre + ' ' + ficha.apellido) : ''"></p>
                </div>
                <button @click="showFicha = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>

            {{-- Tabs de la ficha --}}
            <div class="border-b border-gray-200 px-6">
                <nav class="flex space-x-1 overflow-x-auto">
                    <template x-for="t in [{id:'datos',label:'Datos'},{id:'responsables',label:'Responsables'},{id:'matriculas',label:'Matrículas'},{id:'pagos',label:'Pagos'},{id:'recibos',label:'Recibos'},{id:'calificaciones',label:'Historial académico'}]" :key="t.id">
                        <button @click="fichaTab = t.id" :class="fichaTab === t.id ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-3 border-b-2 text-sm font-medium" x-text="t.label"></button>
                    </template>
                </nav>
            </div>

            <div class="p-6" x-show="ficha">
                <div x-show="fichaDataLoading" class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div>
                <template x-if="!fichaDataLoading">
                <div>
                <div x-show="fichaTab === 'datos'">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-900 border-b pb-2">Datos Personales</h4>
                            <div class="text-sm"><span class="text-gray-500">Código:</span> <span class="font-medium" x-text="ficha?.codigo"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Nombre:</span> <span class="font-medium" x-text="ficha?.nombre + ' ' + ficha?.apellido"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Identidad:</span> <span class="font-medium font-mono" x-text="ficha?.identidad || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Fecha Nac:</span> <span class="font-medium" x-text="ficha?.fecha_nacimiento || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Sexo:</span> <span class="font-medium" x-text="ficha?.sexo || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Dirección:</span> <span class="font-medium" x-text="ficha?.direccion || '-'"></span></div>
                        </div>
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-900 border-b pb-2">Contacto</h4>
                            <div class="text-sm"><span class="text-gray-500">Correo:</span> <span class="font-medium" x-text="ficha?.correo || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Teléfono:</span> <span class="font-medium" x-text="ficha?.telefono || '-'"></span></div>
                            <h4 class="font-semibold text-gray-900 border-b pb-2 pt-3">Padre/Madre</h4>
                            <div class="text-sm"><span class="text-gray-500">Nombre:</span> <span class="font-medium" x-text="ficha?.nombre_padre || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Teléfono:</span> <span class="font-medium" x-text="ficha?.telefono_padre || '-'"></span></div>
                            <div class="text-sm"><span class="text-gray-500">Correo:</span> <span class="font-medium" x-text="ficha?.correo_padre || '-'"></span></div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t">
                        <h4 class="font-semibold text-gray-900 mb-3">Estado</h4>
                        <div class="flex gap-3">
                            <span :class="ficha?.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="ficha?.estado"></span>
                            <span x-show="ficha?.es_primer_ingreso" class="badge badge-info">Primer Ingreso</span>
                            <span class="badge badge-neutral" x-text="ficha?.sucursal?.nombre || ''"></span>
                        </div>
                    </div>
                </div>

                <div x-show="fichaTab === 'responsables'">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-gray-900">Contactos responsables</h4>
                            <p class="text-sm text-gray-500">Consentimiento y canales de notificación por asistencia.</p>
                        </div>
                        <button x-show="ficha?.id && api.hasPermission('estudiantes.modificar')" @click="openContactoModal()" class="btn btn-primary btn-sm">Nuevo contacto</button>
                    </div>

                    <template x-if="fichaContactos.length === 0">
                        <p class="text-sm text-gray-400 text-center py-8">Sin contactos responsables registrados</p>
                    </template>

                    <div class="table-container" x-show="fichaContactos.length > 0">
                        <table class="table">
                            <thead><tr><th>Nombre</th><th>Parentesco</th><th>Correo</th><th>WhatsApp</th><th>Canales</th><th>Consentimiento</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="c in fichaContactos" :key="c.id">
                                    <tr>
                                        <td class="font-medium" x-text="c.nombre"></td>
                                        <td class="text-sm text-gray-500" x-text="c.parentesco || '-' "></td>
                                        <td class="text-sm text-gray-500" x-text="c.correo || '-' "></td>
                                        <td class="text-sm text-gray-500 font-mono" x-text="c.telefono_whatsapp || '-' "></td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                <span x-show="c.recibe_asistencia_email" class="badge badge-info">Email</span>
                                                <span x-show="c.recibe_asistencia_whatsapp" class="badge badge-success">WhatsApp</span>
                                                <span x-show="!c.recibe_asistencia_email && !c.recibe_asistencia_whatsapp" class="badge badge-neutral">Sin canal</span>
                                            </div>
                                        </td>
                                        <td class="text-sm text-gray-500" x-text="fmtFecha(c.consentimiento_asistencia_en) || '-' "></td>
                                        <td><span :class="c.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="c.estado"></span></td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button x-show="api.hasPermission('estudiantes.modificar')" @click="editContacto(c)" class="btn btn-ghost btn-sm">Editar</button>
                                                <button x-show="api.hasPermission('estudiantes.modificar') && c.estado === 'activo'" @click="desactivarContacto(c)" class="btn btn-ghost btn-sm text-red-600">Desactivar</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB MATRÍCULAS --}}
                <div x-show="fichaTab === 'matriculas'">
                    <template x-if="fichaMatriculas.length === 0"><p class="text-sm text-gray-400 text-center py-8">Sin matrículas registradas</p></template>
                    <div class="table-container" x-show="fichaMatriculas.length > 0">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nivel</th><th>Horario</th><th>Régimen</th><th>Modalidad</th><th>Período</th><th>Estado</th><th>Fecha</th></tr></thead>
                            <tbody>
                                <template x-for="m in fichaMatriculas" :key="m.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="m.codigo"></td>
                                        <td class="text-sm font-medium" x-text="m.oferta_academica?.nivel_academico?.nombre || '-'"></td>
                                        <td class="text-xs text-gray-500" x-text="m.oferta_academica?.horario?.codigo || '-'"></td>
                                        <td class="text-xs" x-text="m.regimen || m.oferta_academica?.regimen_academico?.nombre || m.ofertaAcademica?.nivelAcademico?.regimenAcademico?.nombre || '-'"></td>
                                        <td class="text-xs" x-text="m.oferta_academica?.modalidad?.nombre || '-'"></td>
                                        <td class="text-xs text-gray-500" x-text="m.oferta_academica?.periodo_academico?.nombre || '-'"></td>
                                        <td><span :class="{'badge-success': m.estado === 'matriculado', 'badge-warning': m.estado === 'reservada', 'badge-danger': m.estado === 'cancelado', 'badge-info': m.estado === 'iniciada'}" class="badge" x-text="m.estado"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="fmtFecha(m.fecha_confirmacion || m.fecha_reserva)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB PAGOS --}}
                <div x-show="fichaTab === 'pagos'">
                    <template x-if="fichaPagos.length === 0"><p class="text-sm text-gray-400 text-center py-8">Sin pagos registrados</p></template>
                    <div class="table-container" x-show="fichaPagos.length > 0">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Estado</th><th>Fecha</th><th>Hora</th></tr></thead>
                            <tbody>
                                <template x-for="p in fichaPagos" :key="p.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="p.codigo"></td>
                                        <td><span class="badge badge-info" x-text="p.concepto_pago?.codigo"></span></td>
                                        <td class="text-xs text-gray-500" x-text="p.metodo_pago?.nombre || '-'"></td>
                                        <td class="font-medium">L <span x-text="fmtMonto(p.monto)"></span></td>
                                        <td><span :class="{'badge-success': p.estado === 'aprobado', 'badge-warning': p.estado === 'pendiente' || p.estado === 'en_revision', 'badge-danger': p.estado === 'rechazado'}" class="badge" x-text="p.estado"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="fmtFecha(p.fecha_proceso || p.fecha_aprobacion || p.creado_en)"></td>
                                        <td class="text-gray-400 text-xs" x-text="fmtHora(p.fecha_proceso || p.fecha_aprobacion || p.creado_en)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB RECIBOS --}}
                <div x-show="fichaTab === 'recibos'">
                    <template x-if="fichaRecibos.length === 0"><p class="text-sm text-gray-400 text-center py-8">Sin recibos emitidos</p></template>
                    <div class="table-container" x-show="fichaRecibos.length > 0">
                        <table class="table">
                            <thead><tr><th># Recibo</th><th>Código Pago</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Estado</th><th>Fecha</th><th>Hora</th></tr></thead>
                            <tbody>
                                <template x-for="r in fichaRecibos" :key="r.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="String(r.numero_recibo).padStart(6, '0')"></td>
                                        <td class="font-mono text-xs text-gray-600" x-text="r.codigo_pago || '-'"></td>
                                        <td><span class="badge badge-info" x-text="r.concepto_pago?.codigo"></span></td>
                                        <td class="text-xs text-gray-500" x-text="r.metodo_pago?.nombre || '-'"></td>
                                        <td class="font-medium">L <span x-text="fmtMonto(r.monto_total ?? r.monto ?? 0)"></span></td>
                                        <td><span :class="{'badge-success': r.estado === 'emitido', 'badge-danger': r.estado === 'anulado', 'badge-warning': r.estado === 'reversado'}" class="badge" x-text="r.estado"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="fmtFecha(r.fecha_recibo || r.fecha_proceso || r.creado_en)"></td>
                                        <td class="text-gray-400 text-xs" x-text="fmtHora(r.fecha_recibo || r.fecha_proceso || r.creado_en)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB CALIFICACIONES --}}
                <div x-show="fichaTab === 'calificaciones'">
                    <template x-if="fichaCalificaciones.length === 0"><p class="text-sm text-gray-400 text-center py-8">Sin calificaciones registradas</p></template>
                    <div class="table-container" x-show="fichaCalificaciones.length > 0">
                        <table class="table">
                            <thead><tr><th>Nivel</th><th>Periodo</th><th class="text-center">Nota Final</th><th class="text-center">Faltas</th><th>Estado</th><th class="text-right">Certificado</th></tr></thead>
                            <tbody>
                                <template x-for="c in fichaCalificaciones" :key="c.id">
                                    <tr>
                                        <td class="text-sm font-medium" x-text="c.nivel || '-'"></td>
                                        <td class="text-xs text-gray-500" x-text="c.periodo || '-'"></td>
                                        <td class="text-center font-semibold" :class="Number(c.nota_final) >= 80 ? 'text-green-600' : 'text-red-600'" x-text="c.nota_final ?? '-'"></td>
                                        <td class="text-center" x-text="c.faltas ?? 0"></td>
                                        <td><span :class="{'badge-success': c.estado === 'aprobado', 'badge-danger': c.estado === 'reprobado', 'badge-info': c.estado === 'matriculado'}" class="badge" x-text="c.estado"></span></td>
                                        <td class="text-right"><button x-show="c.aprobada && api.hasPermission('calificaciones.modificar')" @click="emitirCertificadoAdmin(c)" class="btn btn-ghost btn-sm text-brand-600" title="Genera el certificado del nivel aprobado">Generar certificado</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Certificados emitidos</h4>
                        <template x-if="fichaCertificados.length === 0"><p class="text-sm text-gray-400 text-center py-8">Aún no tiene certificados emitidos</p></template>
                        <div class="table-container" x-show="fichaCertificados.length > 0">
                            <table class="table">
                                <thead><tr><th>Código</th><th>Nivel</th><th>Nota</th><th>Emisión</th><th class="text-right">Acciones</th></tr></thead>
                                <tbody>
                                    <template x-for="c in fichaCertificados" :key="c.codigo">
                                        <tr>
                                            <td class="font-mono text-xs font-semibold text-brand-600" x-text="c.codigo"></td>
                                            <td x-text="c.nivel || '-'"></td>
                                            <td x-text="c.nota_final"></td>
                                            <td x-text="fmtFecha(c.emitido_en)"></td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button @click="window.open(c.vista_url, '_blank')" class="btn btn-ghost btn-sm">Ver</button>
                                                    <button @click="window.open(c.pdf_url, '_blank')" class="btn btn-ghost btn-sm text-brand-600">PDF</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Editar Estudiante' : 'Nuevo Estudiante'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="saveEstudiante()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Código</label>
                        <div class="text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5" x-show="!editing">
                            Se generará automáticamente
                        </div>
                        <input x-model="form.codigo" x-show="editing" type="text" :class="fieldErrors.codigo ? 'input input-error' : 'input'" readonly>
                        <template x-if="fieldErrors.codigo"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.codigo[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Sucursal</label>
                        <select x-model="form.sucursal_id" required :class="fieldErrors.sucursal_id ? 'input input-error' : 'input'"><option value="">Seleccionar...</option><template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template></select>
                        <template x-if="fieldErrors.sucursal_id"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.sucursal_id[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Nombre</label>
                        <input x-model="form.nombre" type="text" required :class="fieldErrors.nombre ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.nombre"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.nombre[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Apellido</label>
                        <input x-model="form.apellido" type="text" required :class="fieldErrors.apellido ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.apellido"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.apellido[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Identidad</label>
                        <input x-model="form.identidad" type="text" :class="fieldErrors.identidad ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.identidad"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.identidad[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Fecha Nacimiento</label>
                        <input x-model="form.fecha_nacimiento" type="date" :class="fieldErrors.fecha_nacimiento ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.fecha_nacimiento"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.fecha_nacimiento[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Sexo</label>
                        <select x-model="form.sexo" :class="fieldErrors.sexo ? 'input input-error' : 'input'"><option value="">Seleccionar...</option><option value="M">Masculino</option><option value="F">Femenino</option></select>
                        <template x-if="fieldErrors.sexo"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.sexo[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Correo</label>
                        <input x-model="form.correo" type="email" :class="fieldErrors.correo ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.correo"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.correo[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Teléfono</label>
                        <input x-model="form.telefono" type="text" :class="fieldErrors.telefono ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.telefono"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.telefono[0]"></p></template>
                    </div>
                    <div class="col-span-2">
                        <label class="label">Dirección</label>
                        <input x-model="form.direccion" type="text" :class="fieldErrors.direccion ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.direccion"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.direccion[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Nombre Padre/Madre</label>
                        <input x-model="form.nombre_padre" type="text" :class="fieldErrors.nombre_padre ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.nombre_padre"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.nombre_padre[0]"></p></template>
                    </div>
                    <div>
                        <label class="label">Teléfono Padre</label>
                        <input x-model="form.telefono_padre" type="text" :class="fieldErrors.telefono_padre ? 'input input-error' : 'input'">
                        <template x-if="fieldErrors.telefono_padre"><p class="text-red-500 text-xs mt-1" x-text="fieldErrors.telefono_padre[0]"></p></template>
                    </div>
                </div>
                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600 font-medium" x-text="error"></p>
                    <template x-if="Object.keys(fieldErrors).length > 0">
                        <ul class="mt-2 text-sm text-red-600 list-disc list-inside space-y-1">
                            <template x-for="(msgs, field) in fieldErrors" :key="field">
                                <li x-text="msgs[0]"></li>
                            </template>
                        </ul>
                    </template>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary"><span x-text="saving ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showContactoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showContactoModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editingContacto ? 'Editar contacto responsable' : 'Nuevo contacto responsable'"></h3>
                <button @click="showContactoModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="saveContacto()" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Nombre</label>
                        <input x-model="contactoForm.nombre" type="text" required class="input">
                    </div>
                    <div>
                        <label class="label">Parentesco</label>
                        <input x-model="contactoForm.parentesco" type="text" class="input" placeholder="madre, padre, tutor...">
                    </div>
                    <div>
                        <label class="label">Correo</label>
                        <input x-model="contactoForm.correo" type="email" class="input">
                    </div>
                    <div>
                        <label class="label">WhatsApp</label>
                        <input x-model="contactoForm.telefono_whatsapp" type="text" class="input" placeholder="+50499990000">
                    </div>
                    <div>
                        <label class="label">Prioridad</label>
                        <input x-model="contactoForm.prioridad" type="number" min="1" max="99" class="input">
                    </div>
                    <div>
                        <label class="label">Consentimiento</label>
                        <input x-model="contactoForm.consentimiento_asistencia_en" type="datetime-local" class="input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Evidencia de consentimiento</label>
                        <textarea x-model="contactoForm.consentimiento_evidencia" class="input min-h-[90px]" placeholder="Referencia, observación o evidencia resumida"></textarea>
                    </div>
                    <div>
                        <label class="label">Vigente desde</label>
                        <input x-model="contactoForm.vigente_desde" type="date" class="input">
                    </div>
                    <div>
                        <label class="label">Vigente hasta</label>
                        <input x-model="contactoForm.vigente_hasta" type="date" class="input">
                    </div>
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <label class="flex items-center gap-2"><input x-model="contactoForm.recibe_asistencia_email" type="checkbox" class="rounded"> <span>Email asistencia</span></label>
                        <label class="flex items-center gap-2"><input x-model="contactoForm.recibe_asistencia_whatsapp" type="checkbox" class="rounded"> <span>WhatsApp asistencia</span></label>
                        <div>
                            <label class="label">Estado</label>
                            <select x-model="contactoForm.estado" class="input">
                                <option value="activo">activo</option>
                                <option value="inactivo">inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="contactoError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600 font-medium" x-text="contactoError"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showContactoModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingContacto" class="btn btn-primary"><span x-text="savingContacto ? 'Guardando...' : 'Guardar' "></span></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .input-error {
        border-color: #ef4444 !important;
        --tw-ring-color: #fca5a5 !important;
    }
</style>
@endsection

@section('scripts')
<script>
function estudiantes() {
    return {
        loading: true, showModal: false, showFicha: false, showContactoModal: false, editing: false, editingContacto: false, saving: false, savingContacto: false, error: '', contactoError: '', fieldErrors: {},
        estudiantes: [], sucursales: [], ficha: null, form: {}, editId: null, search: '', filtroSucursal: '',
        fichaTab: 'datos', fichaDataLoading: false,
        fichaMatriculas: [], fichaPagos: [], fichaRecibos: [], fichaCalificaciones: [], fichaCertificados: [], fichaContactos: [],
        contactoForm: {}, contactoId: null,

        async init() { await Promise.all([this.loadEstudiantes(), this.loadSucursales()]); },

        async loadSucursales() {
            try {
                const { data } = await window.axios.get('/api/v1/catalogos-academicos/sucursales', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.sucursales = data.data?.data || data.data || [];
            } catch(e) {}
        },

        async loadEstudiantes() {
            this.loading = true;
            try {
                let url = '/api/v1/estudiantes?';
                if (this.search) url += `buscar=${this.search}&`;
                if (this.filtroSucursal) url += `sucursal_id=${this.filtroSucursal}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.estudiantes = data.data?.data || data.data || [];
            } catch(e) { console.error(e); } finally { this.loading = false; }
        },

        maskEmail(e) { return e.substring(0,2) + '****' + e.substring(e.indexOf('@')); },
        maskPhone(t) { return '****-' + t.slice(-4); },
        maskId(i) { return i.substring(0,3) + '****' + i.slice(-3); },

        openModal() { this.editing = false; this.editId = null; this.error = ''; this.fieldErrors = {}; this.form = { codigo:'', nombre:'', apellido:'', sucursal_id:'', identidad:'', correo:'', telefono:'', direccion:'', sexo:'', fecha_nacimiento:'', nombre_padre:'', telefono_padre:'' }; this.showModal = true; },

        editEstudiante(e) { this.editing = true; this.editId = e.id; this.error = ''; this.fieldErrors = {}; this.form = { codigo: e.codigo, nombre: e.nombre, apellido: e.apellido, sucursal_id: e.sucursal_id, identidad: e.identidad||'', correo: e.correo||'', telefono: e.telefono||'', direccion: e.direccion||'', sexo: e.sexo||'', fecha_nacimiento: e.fecha_nacimiento ? String(e.fecha_nacimiento).slice(0, 10) : '', nombre_padre: e.nombre_padre||'', telefono_padre: e.telefono_padre||'' }; this.showModal = true; },

        async viewFicha(e) {
            this.showFicha = true; this.ficha = e;
            this.fichaMatriculas = []; this.fichaPagos = []; this.fichaRecibos = []; this.fichaCalificaciones = []; this.fichaCertificados = []; this.fichaContactos = [];
            this.fichaDataLoading = true; this.fichaTab = 'datos';
            const headers = { Authorization: `Bearer ${localStorage.getItem('auth_token')}` };
            const extractData = (r) => r?.data?.data?.data || r?.data?.data || [];
            try {
                const estR = await window.axios.get(`/api/v1/estudiantes/${e.id}`, { headers });
                if (estR?.data?.data) this.ficha = estR.data.data;
            } catch(err) { console.error('est show', err); }
            try {
                const r = await window.axios.get(`/api/v1/matriculas?estudiante_id=${e.id}&per_page=999`, { headers });
                this.fichaMatriculas = extractData(r);
            } catch(err) { console.error('mats', err); }
            try {
                const r = await window.axios.get(`/api/v1/pagos?estudiante_id=${e.id}&per_page=999`, { headers });
                this.fichaPagos = extractData(r);
            } catch(err) { console.error('pags', err); }
            try {
                const r = await window.axios.get(`/api/v1/recibos-caja?estudiante_id=${e.id}&per_page=999`, { headers });
                this.fichaRecibos = extractData(r);
            } catch(err) { console.error('recs', err); }
            try {
                const r = await window.axios.get(`/api/v1/calificaciones?estudiante_id=${e.id}&per_page=999`, { headers });
                this.fichaCalificaciones = (r.data.data?.data || r.data.data || []).map(c => ({
                    id: c.id,
                    codigo: c.codigo,
                    nivel: c.oferta_academica?.nivel_academico?.nombre || c.ofertaAcademica?.nivelAcademico?.nombre || '-',
                    periodo: c.oferta_academica?.periodo_academico?.nombre || c.ofertaAcademica?.periodoAcademico?.nombre || '-',
                     nota_final: c.nota_final,
                     estado: c.estado,
                     aprobada: c.aprobada === true,
                     creado_en: c.creado_en,
                    faltas: c.faltas,
                }));
            } catch(err) {
                console.error('cals', err);
                this.error = window.extractError ? window.extractError(err, 'No se pudieron cargar las calificaciones') : 'No se pudieron cargar las calificaciones';
            }
            try {
                const r = await window.axios.get(`/api/v1/estudiantes/certificados/estudiante/${e.id}`, { headers });
                this.fichaCertificados = r.data.data || [];
            } catch(err) { console.error('certs', err); }
            try {
                const r = await window.axios.get(`/api/v1/estudiantes/${e.id}/contactos-responsable`, { headers });
                this.fichaContactos = r.data.data || [];
            } catch(err) { console.error('contactos', err); }
            this.fichaDataLoading = false;
        },

        openContactoModal() {
            this.editingContacto = false; this.contactoId = null; this.contactoError = '';
            this.contactoForm = { nombre:'', parentesco:'', correo:'', telefono_whatsapp:'', recibe_asistencia_email:false, recibe_asistencia_whatsapp:false, consentimiento_asistencia_en:'', consentimiento_evidencia:'', prioridad:1, vigente_desde:'', vigente_hasta:'', estado:'activo' };
            this.showContactoModal = true;
        },

        editContacto(c) {
            this.editingContacto = true; this.contactoId = c.id; this.contactoError = '';
            this.contactoForm = {
                nombre: c.nombre || '',
                parentesco: c.parentesco || '',
                correo: c.correo || '',
                telefono_whatsapp: c.telefono_whatsapp || '',
                recibe_asistencia_email: !!c.recibe_asistencia_email,
                recibe_asistencia_whatsapp: !!c.recibe_asistencia_whatsapp,
                consentimiento_asistencia_en: c.consentimiento_asistencia_en ? String(c.consentimiento_asistencia_en).slice(0, 16) : '',
                consentimiento_evidencia: c.consentimiento_evidencia || '',
                prioridad: c.prioridad || 1,
                vigente_desde: c.vigente_desde ? String(c.vigente_desde).slice(0, 10) : '',
                vigente_hasta: c.vigente_hasta ? String(c.vigente_hasta).slice(0, 10) : '',
                estado: c.estado || 'activo',
            };
            this.showContactoModal = true;
        },

        async saveContacto() {
            if (!this.ficha?.id) return;
            this.savingContacto = true; this.contactoError = '';
            try {
                const payload = { ...this.contactoForm };
                const url = this.editingContacto
                    ? `/api/v1/estudiantes/${this.ficha.id}/contactos-responsable/${this.contactoId}`
                    : `/api/v1/estudiantes/${this.ficha.id}/contactos-responsable`;
                const { data } = await window.api.actualizar(url, payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showContactoModal = false;
                    await this.recargarContactosFicha();
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Contacto responsable guardado', type: 'success' } }));
                }
            } catch (e) {
                this.contactoError = window.extractError ? window.extractError(e, 'No se pudo guardar el contacto responsable') : 'No se pudo guardar el contacto responsable';
            } finally {
                this.savingContacto = false;
            }
        },

        async desactivarContacto(c) {
            if (!this.ficha?.id || !confirm(`¿Desactivar a ${c.nombre}?`)) return;
            try {
                const { data } = await window.api.actualizar(`/api/v1/estudiantes/${this.ficha.id}/contactos-responsable/${c.id}/desactivar`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    await this.recargarContactosFicha();
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Contacto responsable desactivado', type: 'success' } }));
                }
            } catch (e) {
                alert(window.extractError ? window.extractError(e, 'No se pudo desactivar el contacto') : 'No se pudo desactivar el contacto');
            }
        },

        async recargarContactosFicha() {
            if (!this.ficha?.id) return;
            const { data } = await window.axios.get(`/api/v1/estudiantes/${this.ficha.id}/contactos-responsable`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
            this.fichaContactos = data.data || [];
        },

        async emitirCertificadoAdmin(c) {
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.post('/api/v1/estudiantes/certificados/electronicos/admin', {
                    calificacion_id: c.id,
                }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    window.open(data.data?.pdf_url || `/certificados/${data.data.token_validacion}/pdf`, '_blank');
                    const certificado = { ...data.data, nivel: data.data?.nivel?.nombre || '-' };
                    this.fichaCertificados = [certificado, ...this.fichaCertificados.filter(item => item.codigo !== certificado.codigo)];
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Certificado generado', type: 'success' } }));
                }
            } catch(e) {
                alert(window.extractError ? window.extractError(e, 'No se pudo emitir el certificado') : 'No se pudo emitir el certificado');
            }
        },

        async saveEstudiante() {
            this.saving = true; this.error = '';
            try {
                const url = this.editing ? `/api/v1/estudiantes/${this.editId}` : '/api/v1/estudiantes';
                const { data } = await window.api.actualizar(url, this.form, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showModal = false; window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Estudiante guardado', type: 'success' } })); await this.loadEstudiantes(); }
                else { this.error = data.mensaje || 'Error'; }
            } catch(e) { this.error = window.extractError(e, 'Error de validación'); this.fieldErrors = e.response?.data?.errores || {}; } finally { this.saving = false; }
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
    }
}
</script>
@endsection
