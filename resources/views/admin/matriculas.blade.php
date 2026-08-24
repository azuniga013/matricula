@extends('layouts.admin')
@section('content')
<div x-data="matriculas()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Matrícula</h1>
            <p class="page-subtitle">Gestión de matrículas, asignación de cupos y gestiones</p>
        </div>
        <div class="flex items-center gap-2">
            <button x-show="activeTab === 'gestiones' && api.hasPermission('matriculas.gestion.crear') && flujo.habilita_reenganche" @click="openModalGestion()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva Gestión
            </button>
            <button x-show="activeTab === 'matriculas' && api.hasPermission('matriculas.gestion.crear') && flujo.habilita_reserva_cupo" @click="openModal()" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva Matrícula
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1" aria-label="Tabs">
            <button @click="activeTab = 'matriculas'"
                :class="activeTab === 'matriculas' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium transition-colors">Matrículas</button>
            <button @click="activeTab = 'gestiones'; loadGestiones()"
                :class="activeTab === 'gestiones' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium transition-colors">Gestiones</button>
        </nav>
    </div>

    {{-- ============ TAB MATRÍCULAS ============ --}}
    <div x-show="activeTab === 'matriculas'">
        {{-- Filters --}}
        <div class="card mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div><label class="label">Período</label><select x-model="filtro.periodo" @change="load()" class="input"><option value="">Todos</option><template x-for="p in periodos" :key="p.id"><option :value="p.id" x-text="p.nombre"></option></template></select></div>
                    <div><label class="label">Sucursal</label><select x-model="filtro.sucursal" @change="load()" class="input"><option value="">Todas</option><template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.nombre"></option></template></select></div>
                    <div><label class="label">Estado</label><select x-model="filtro.estado" @change="load()" class="input"><option value="">Todos</option><option value="iniciada">Iniciada</option><option value="reservada">Reservada</option><option value="en_revision">En Revisión</option><option value="matriculado">Matriculado</option><option value="rechazado">Rechazado</option><option value="cancelado">Cancelado</option></select></div>
                    <div class="flex items-end"><button @click="load()" class="btn btn-outline w-full"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg> Actualizar</button></div>
                </div>
            </div>
        </div>

        <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

        <template x-if="!loading">
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Código</th><th>Estudiante</th><th>Oferta</th><th>Nivel</th><th>Horario</th><th>Fecha</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="m in matriculas" :key="m.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="m.codigo"></td>
                                    <td class="font-medium" x-text="(m.estudiante?.nombre || '') + ' ' + (m.estudiante?.apellido || '')"></td>
                                    <td class="text-gray-500 font-mono text-xs" x-text="m.oferta_academica?.codigo || '-'"></td>
                                    <td x-text="m.oferta_academica?.nivel_academico?.nombre || '-'"></td>
                                    <td x-text="m.oferta_academica?.horario?.nombre || '-'"></td>
                                    <td class="text-gray-500 text-xs" x-text="formatFecha(m.fecha_confirmacion || m.fecha_reserva || m.created_at)"></td>
                                    <td><span :class="{
                                        'badge-success': m.estado === 'matriculado',
                                        'badge-warning': m.estado === 'reservada',
                                        'badge-info': m.estado === 'iniciada' || m.estado === 'en_revision',
                                        'badge-danger': m.estado === 'cancelado' || m.estado === 'rechazado'
                                    }" class="badge" x-text="m.estado == 'en_revision' ? 'En Revisión' : m.estado"></span></td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button x-show="api.hasPermission('matriculas.gestion.modificar') && m.estado === 'reservada' && flujo.habilita_confirmacion_matricula" @click="confirmar(m)" class="btn btn-ghost btn-sm text-emerald-600">Confirmar</button>
                                            <button x-show="api.hasPermission('pagos.crear') && m.estado === 'en_revision' && flujo.habilita_revision_contable" @click="abrirPago(m)" class="btn btn-ghost btn-sm text-blue-600">Pago</button>
                                            <button x-show="api.hasPermission('matriculas.gestion.crear') && m.estado === 'matriculado' && flujo.habilita_reenganche" @click="nuevaGestionPara(m)" class="btn btn-ghost btn-sm text-brand-600">Gestionar</button>
                                            <button x-show="api.hasPermission('matriculas.gestion.anular') && m.estado !== 'cancelado' && m.estado !== 'matriculado'" @click="cancelar(m)" class="btn btn-ghost btn-sm text-red-600">Cancelar</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="matriculas.length === 0">
                                <tr><td colspan="8" class="text-center py-10 text-gray-400 text-sm">No hay matrículas para los filtros seleccionados</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- ============ TAB GESTIONES ============ --}}
    <div x-show="activeTab === 'gestiones'" class="space-y-6">

        {{-- Student search + Period --}}
        <div class="card">
            <div class="card-body">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Estudiante</h3>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="relative">
                        <label class="label">Código del Alumno</label>
                        <input x-model="busquedaEstudianteCodigo" @input.debounce.300ms="buscarEstudiantePorCodigo()" type="text" placeholder="Ej: EST-2026-00000006" class="input">
                        <div x-show="resultadosBusquedaEstudiante.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="e in resultadosBusquedaEstudiante" :key="e.id">
                                <button type="button" @click="seleccionarEstudianteGestion(e)" class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                                    <span class="font-medium" x-text="e.codigo + ' — ' + (e.nombre || '') + ' ' + (e.apellido || '')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="label">Período</label>
                        <select x-model="filtroGestion.periodo" @change="onFiltroGestionAcademicoChange('periodo')" class="input">
                            <option value="">Seleccionar...</option>
                            <template x-for="p in periodos" :key="p.id"><option :value="p.id" x-text="p.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Plan de estudio</label>
                        <select x-model="filtroGestion.plan" @change="onFiltroGestionAcademicoChange('plan')" class="input">
                            <option value="">Todos los planes</option>
                            <template x-for="p in planesGestionDisponibles" :key="p.id"><option :value="p.id" x-text="p.codigo + ' · ' + p.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Nivel</label>
                        <select x-model="filtroGestion.nivel" @change="onFiltroGestionAcademicoChange('nivel')" class="input" :disabled="!filtroGestion.plan">
                            <option value="">Todos los niveles</option>
                            <template x-for="n in nivelesGestionDisponibles" :key="n.id"><option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Horario / Oferta</label>
                        <select x-model="filtroGestion.oferta" @change="cargarMatriculasPagadas(); loadGestiones()" class="input" :disabled="!filtroGestion.nivel">
                            <option value="">Todos los horarios</option>
                            <template x-for="o in ofertasGestionDisponibles" :key="o.id"><option :value="o.id" x-text="o.codigo + ' · ' + (o.horario?.nombre || '')"></option></template>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="cargarMatriculasPagadas()" class="btn btn-outline w-full">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            Buscar
                        </button>
                    </div>
                    <div class="flex items-end">
                        <button @click="limpiarBusquedaGestion()" class="btn btn-ghost w-full text-gray-500">Limpiar</button>
                    </div>
                </div>
                <div x-show="estudianteGestion" class="mt-3 p-3 bg-brand-50 rounded-lg text-sm text-brand-700 flex items-center justify-between">
                    <span><span class="font-semibold" x-text="estudianteGestion?.codigo"></span> — <span x-text="(estudianteGestion?.nombre || '') + ' ' + (estudianteGestion?.apellido || '')"></span></span>
                </div>
            </div>
        </div>

        {{-- Paid matrículas --}}
        <div x-show="estudianteGestion && filtroGestion.periodo" class="card">
            <div class="card-body">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Matrículas Pagadas</h3>
                <template x-if="matriculasPagadas.length === 0">
                    <p class="text-sm text-gray-400 text-center py-6">El estudiante no tiene matrículas pagadas en este período</p>
                </template>
                <div class="table-container" x-show="matriculasPagadas.length > 0">
                    <table class="table">
                        <thead><tr><th>Matrícula</th><th>Oferta</th><th>Nivel</th><th>Horario</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                        <tbody>
                            <template x-for="m in matriculasPagadas" :key="m.id">
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-brand-600" x-text="m.codigo"></td>
                                    <td class="text-gray-500 text-xs font-mono" x-text="m.oferta_academica?.codigo || '-'"></td>
                                    <td class="text-sm font-medium" x-text="m.oferta_academica?.nivel_academico?.nombre || '-'"></td>
                                    <td class="text-xs text-gray-500" x-text="m.oferta_academica?.horario?.nombre || m.oferta_academica?.horario?.codigo || '-'"></td>
                                    <td><span class="badge badge-success" x-text="m.estado"></span></td>
                                    <td class="text-right">
                                        <button x-show="api.hasPermission('matriculas.gestion.crear')" @click="nuevaGestionPara(m)" class="btn btn-ghost btn-sm text-brand-600">Gestionar</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Gestiones history --}}
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">Historial de Gestiones</h3>
                    <button x-show="api.hasPermission('matriculas.gestion.crear')" @click="openModalGestion()" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nueva Gestión
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="label">Tipo de Gestión</label>
                        <select x-model="filtroGestion.tipo" @change="loadGestiones()" class="input">
                            <option value="">Todos</option>
                            <template x-for="t in tiposGestion" :key="t.id"><option :value="t.id" x-text="t.nombre"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="filtroGestion.estado" @change="loadGestiones()" class="input">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="rechazado">Rechazado</option>
                            <option value="ejecutado">Ejecutado</option>
                        </select>
                    </div>
                    <div class="flex items-end"><button @click="loadGestiones()" class="btn btn-outline w-full"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg> Actualizar</button></div>
                </div>
                <template x-if="loadingGestiones"><div class="flex items-center justify-center py-10"><div class="animate-spin rounded-full h-6 w-6 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>
                <template x-if="!loadingGestiones">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Matrícula</th><th>Estudiante</th><th>Tipo</th><th>Motivo</th><th>Fecha Solicitud</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="g in gestiones" :key="g.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="g.matricula?.codigo || '-'"></td>
                                        <td class="font-medium" x-text="(g.matricula?.estudiante?.nombre || '') + ' ' + (g.matricula?.estudiante?.apellido || '')"></td>
                                        <td><span class="badge badge-info" x-text="g.tipo_gestion?.nombre || '-'"></span></td>
                                        <td class="text-gray-500 text-xs max-w-[200px] truncate" x-text="g.motivo"></td>
                                        <td class="text-gray-500 text-xs" x-text="formatFecha(g.fecha_solicitud)"></td>
                                        <td><span :class="{
                                            'badge-warning': g.estado === 'pendiente',
                                            'badge-success': g.estado === 'aprobado' || g.estado === 'ejecutado',
                                            'badge-danger': g.estado === 'rechazado'
                                        }" class="badge" x-text="g.estado"></span></td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button @click="verDetalle(g)" class="btn btn-ghost btn-sm">Ver</button>
                                                <button x-show="api.hasPermission('matriculas.gestion.aprobar') && g.estado === 'pendiente'" @click="aprobarGestion(g)" class="btn btn-ghost btn-sm text-emerald-600">Aprobar</button>
                                                <button x-show="api.hasPermission('matriculas.gestion.aprobar') && g.estado === 'pendiente'" @click="abrirRechazo(g)" class="btn btn-ghost btn-sm text-red-600">Rechazar</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="gestiones.length === 0">
                                    <tr><td colspan="7" class="text-center py-10 text-gray-400 text-sm">No hay gestiones registradas</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal Nueva Matrícula --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Nueva Matrícula</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 space-y-4">
                {{-- Student search --}}
                <div>
                    <label class="label">Estudiante</label>
                    <div class="relative">
                        <input x-model="busquedaEstudiante" @input.debounce.300ms="buscarEstudiantes()" type="text" placeholder="Buscar por código o nombre..." class="input">
                        <div x-show="resultadosEstudiantes.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="e in resultadosEstudiantes" :key="e.id">
                                <button type="button" @click="selectEstudiante(e)" class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                                    <span class="font-medium" x-text="e.codigo + ' — ' + (e.nombres || e.nombre || '') + ' ' + (e.apellidos || e.apellido || '')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-show="!estudianteSeleccionado && resultadosEstudiantes.length === 0 && busquedaEstudiante.length >= 2" class="mt-1 text-xs text-gray-400">Escriba al menos 2 caracteres para buscar</div>
                    <div x-show="form.estudiante_id && estudianteSeleccionado" class="mt-2 p-2 bg-brand-50 rounded-lg text-sm text-brand-700 flex items-center justify-between">
                        <span>Seleccionado: <span class="font-semibold" x-text="estudianteSeleccionado"></span></span>
                        <button type="button" @click="limpiarEstudiante()" class="text-brand-500 hover:text-brand-700 text-xs font-medium">Cambiar</button>
                    </div>
                </div>

                {{-- Plan active hint --}}
                <template x-if="form.estudiante_id && planActivoInfo">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
                        <span class="font-semibold">Plan activo:</span> <span x-text="planActivoInfo.plan_codigo + ' · ' + planActivoInfo.plan_nombre"></span>
                        <p class="text-xs text-amber-600 mt-1">Solo se muestran ofertas del mismo plan. Debe finalizar este plan antes de cambiarse a otro.</p>
                    </div>
                </template>

                {{-- Cascading filters & grid --}}
                <template x-if="form.estudiante_id">
                    <div class="space-y-4 border-t border-gray-100 pt-4">
                        {{-- Filters row --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Período Académico</label>
                                <select x-model="filtroNuevo.periodo_id" @change="onPeriodoChange()" class="input">
                                    <option value="">Seleccionar período...</option>
                                    <template x-for="p in periodos" :key="p.id">
                                        <option :value="p.id" x-text="p.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="label">Plan de Estudio</label>
                                <select x-model="filtroNuevo.plan_id" @change="onPlanChange()" class="input" :disabled="!!planActivoInfo">
                                    <option value="">Seleccionar plan...</option>
                                    <template x-for="plan in planesDisponibles" :key="plan.id">
                                        <option :value="plan.id" x-text="plan.codigo + ' · ' + plan.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="label">Nivel Académico</label>
                                <select x-model="filtroNuevo.nivel_id" @change="onNivelChange()" class="input">
                                    <option value="">Todos los niveles</option>
                                    <template x-for="n in nivelesDisponibles" :key="n.id">
                                        <option :value="n.id" x-text="n.codigo + ' · ' + n.nombre"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Offer grid --}}
                        <div x-show="ofertasGrid.length === 0 && !loadingOfertas" class="text-center py-6 text-gray-400 text-sm">No hay ofertas disponibles para los filtros seleccionados.</div>
                        <div x-show="loadingOfertas" class="text-center py-6"><div class="animate-spin inline-block w-6 h-6 border-2 border-brand-500/20 border-t-brand-500 rounded-full"></div></div>
                        <template x-if="!loadingOfertas && ofertasGrid.length > 0">
                            <div class="max-h-64 overflow-y-auto space-y-2">
                                <template x-for="o in ofertasGrid" :key="o.id">
                                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                        :class="form.oferta_academica_id === o.id ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                        <input type="radio" name="oferta" :value="o.id" x-model="form.oferta_academica_id" class="mt-1">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium text-gray-900" x-text="o.nivel_academico?.nombre || '-'"></span>
                                                <span class="text-xs font-mono text-brand-600" x-text="o.codigo"></span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 mt-1">
                                                <span x-text="'Horario: ' + (o.horario?.nombre || '-')"></span>
                                                <span x-text="'Docente: ' + (o.docente?.nombre ? (o.docente.nombre + ' ' + (o.docente.apellido||'')) : '-')"></span>
                                                <span x-text="'Modalidad: ' + (o.modalidad?.nombre || '-')"></span>
                                                <span x-text="'Régimen: ' + (o.regimen_academico?.nombre || '-')"></span>
                                                <span class="font-semibold"
                                                    :class="(o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0))) <= 3 ? 'text-amber-600' : 'text-emerald-600'"
                                                    x-text="'Cupo: ' + (o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0)))"></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="error"></p></div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="button" @click="reservar()" :disabled="saving || !form.estudiante_id || !form.oferta_academica_id" class="btn btn-primary">
                        <span x-text="saving ? 'Reservando...' : 'Reservar Cupo'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Nueva Gestión --}}
    <div x-show="showModalGestion" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalGestion = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Nueva Gestión de Matrícula</h3>
                <button @click="showModalGestion = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="solicitarGestion()" class="p-6 space-y-4">
                <div>
                    <label class="label">Matrícula (estudiante matriculado)</label>
                    <select x-model="formGestion.matricula_id" @change="tipoGestionSeleccionado()" required class="input" :disabled="matriculaPreseleccionada">
                        <option value="">Seleccionar matrícula...</option>
                        <template x-for="m in matriculasActivas" :key="m.id">
                            <option :value="m.id" x-text="m.codigo + ' — ' + (m.estudiante?.nombre||'') + ' ' + (m.estudiante?.apellido||'') + ' — ' + (m.oferta_academica?.codigo||'')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Tipo de Gestión</label>
                    <select x-model="formGestion.tipo_gestion_matricula_id" @change="tipoGestionSeleccionado()" required class="input">
                        <option value="">Seleccionar tipo...</option>
                        <template x-for="t in tiposGestion" :key="t.id"><option :value="t.id" x-text="t.codigo + ' · ' + t.nombre"></option></template>
                    </select>
                </div>
                <div x-show="requiereOfertaDestino">
                    <label class="label" x-text="tipoGestionCambioModalidad ? 'Oferta Destino (seleccione la nueva modalidad)' : 'Oferta Destino (seleccione el nuevo horario)'"></label>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="o in ofertasDestino" :key="o.id">
                            <label class="block rounded-lg border p-3 cursor-pointer transition" :class="String(formGestion.oferta_academica_destino_id) === String(o.id) ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50'">
                                <input type="radio" name="oferta_destino" :value="o.id" x-model="formGestion.oferta_academica_destino_id" class="sr-only">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-900"><span class="font-mono text-brand-600" x-text="o.codigo"></span> · <span x-text="o.nivel_academico?.nombre || '-'" ></span></div>
                                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 mt-1">
                                            <span x-text="'Horario: ' + (o.horario?.nombre || o.horario?.codigo || '-')"></span>
                                            <span x-text="'Docente: ' + (o.docente?.nombre ? o.docente.nombre + ' ' + (o.docente.apellido || '') : '-')"></span>
                                            <span x-text="'Modalidad: ' + (o.modalidad?.nombre || '-')"></span>
                                        </div>
                                    </div>
                                    <span class="badge badge-success whitespace-nowrap" x-text="'Cupo: ' + (o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0)))"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                    <select x-show="false" x-model="formGestion.oferta_academica_destino_id" :required="requiereOfertaDestino" class="input">
                        <option value="">Seleccionar oferta destino...</option>
                        <template x-for="o in ofertasDestino" :key="o.id">
                            <option :value="o.id" x-text="o.codigo + ' — ' + (o.nivel_academico?.nombre||'') + ' — ' + (o.horario?.nombre||'') + ' (Cupo: ' + (o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0))) + ')'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="label">Motivo *</label>
                    <textarea x-model="formGestion.motivo" required rows="3" maxlength="500" class="input" placeholder="Describa el motivo de la gestión..."></textarea>
                </div>
                <div x-show="errorGestion" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorGestion"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModalGestion = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingGestion" class="btn btn-primary"><span x-text="savingGestion ? 'Solicitando...' : 'Solicitar Gestión'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Registrar Pago (desde matrícula) --}}
    <div x-show="showModalPago" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalPago = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Registrar Pago</h3>
                <button @click="showModalPago = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 space-y-4">
                <template x-if="!pagoMatricula">
                    <div class="text-center py-6"><div class="animate-spin inline-block w-6 h-6 border-2 border-brand-500/20 border-t-brand-500 rounded-full"></div><p class="text-sm text-gray-400 mt-2">Cargando obligaciones...</p></div>
                </template>
                <template x-if="pagoMatricula">
                    <form @submit.prevent="registrarPagoMatricula()">
                        <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1 mb-4">
                            <div class="flex justify-between"><span class="text-gray-500">Matrícula</span><span class="font-mono font-semibold text-brand-600" x-text="pagoMatricula.codigo"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Estudiante</span><span class="font-medium" x-text="(pagoMatricula.estudiante?.nombre||'') + ' ' + (pagoMatricula.estudiante?.apellido||'')"></span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Oferta</span><span class="font-mono text-xs" x-text="pagoMatricula.oferta_academica?.codigo"></span></div>
                        </div>

                        <template x-if="(flujoPagoMatricula?.habilita_seleccion_obligaciones ?? true)">
                            <div>
                                <label class="label mb-2">Obligaciones pendientes</label>
                                <div class="space-y-2 mb-4">
                                    <template x-for="o in (pagoMatricula.obligaciones || [])" :key="o.id">
                                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" :value="o.id" x-model="pagoObligacionesIds" class="mt-0.5">
                                            <div class="flex-1 flex items-center justify-between text-sm">
                                                <div><span class="font-medium" x-text="o.nombre_cargo"></span><br><span class="text-xs text-gray-400" x-text="'Vence: ' + (o.fecha_vencimiento || '-')"></span></div>
                                                <span class="font-semibold text-gray-900">L <span x-text="fmtMontoPago(o.monto - (o.monto_pagado||0))"></span></span>
                                            </div>
                                        </label>
                                    </template>
                                    <template x-if="!pagoMatricula.obligaciones || pagoMatricula.obligaciones.length === 0">
                                        <p class="text-sm text-gray-400 text-center py-3">No hay obligaciones pendientes</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="!(flujoPagoMatricula?.habilita_seleccion_obligaciones ?? true)">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800 mb-4">
                                La selección de obligaciones está deshabilitada para este flujo. Se aplicarán todas las obligaciones pendientes.
                            </div>
                        </template>
                        <div class="bg-gray-50 rounded-lg p-3 text-sm space-y-1 mb-4">
                            <div class="flex justify-between"><span class="text-gray-500">Total a pagar</span><span class="font-semibold">L <span x-text="fmtMontoPago(pagoTotalSeleccionado)"></span></span></div>
                            <template x-if="pagoEsEfectivo">
                                <div class="flex justify-between"><span class="text-gray-500">Vuelto</span><span class="font-semibold text-emerald-700">L <span x-text="fmtMontoPago(pagoVueltoCalculado)"></span></span></div>
                            </template>
                        </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="label">Método de Pago</label>
                                <select x-model="pagoForm.metodo_pago_id" @change="onPagoMetodoChange()" required class="input">
                                    <option value="">Seleccionar...</option>
                                    <template x-for="m in metodosPago" :key="m.id">
                                        <option :value="m.id" x-text="m.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-2" x-show="requiereCuentaBancaria(pagoForm.metodo_pago_id)">
                                <label class="label">Cuenta bancaria donde se realizó el pago *</label>
                                <select x-model="pagoForm.cuenta_bancaria_id" :required="requiereCuentaBancaria(pagoForm.metodo_pago_id)" class="input">
                                    <option value="">Seleccionar...</option>
                                    <template x-for="cuenta in cuentasBancarias" :key="cuenta.id">
                                        <option :value="cuenta.id" x-text="cuenta.banco + ' — ' + cuenta.numero_cuenta + ' (' + cuenta.tipo_cuenta + ')' "></option>
                                    </template>
                                </select>
                                <p x-show="cuentasBancarias.length === 0" class="mt-1 text-xs text-amber-700">No hay cuentas bancarias activas configuradas.</p>
                            </div>
                            <div class="col-span-2">
                                <label class="label">Referencia</label>
                                <input x-model="pagoForm.referencia_externa" type="text" class="input" placeholder="N° depósito/transferencia (opcional)">
                            </div>
                            <div class="col-span-2" x-show="pagoEsEfectivo">
                                <label class="label">Monto recibido (L) *</label>
                                <input x-model.number="pagoForm.monto_recibido" type="number" step="0.01" min="0" :required="pagoEsEfectivo" class="input">
                            </div>
                        </div>

                        <div x-show="errorPago" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mt-4"><p class="text-sm text-red-600" x-text="errorPago"></p></div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-4">
                    <button type="button" @click="showModalPago = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingPago || (!flujoPagoMatricula?.habilita_aprobacion_pago && !flujoPagoMatricula?.habilita_solicitud_link) || pagoObligacionesIds.length === 0" class="btn btn-primary">
                        <span x-text="savingPago ? 'Registrando...' : 'Registrar Pago y Aprobar'"></span>
                    </button>
                </div>
            </form>
        </template>
            </div>
        </div>
    </div>

    {{-- Modal Rechazar Gestión --}}
    <div x-show="showModalRechazo" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalRechazo = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Rechazar Gestión</h3>
                <button @click="showModalRechazo = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="rechazarGestion()" class="p-6 space-y-4">
                <p class="text-sm text-gray-600">Gestión <span class="font-mono font-semibold" x-text="gestionSeleccionada?.matricula?.codigo"></span> — <span x-text="gestionSeleccionada?.tipo_gestion?.nombre"></span></p>
                <div>
                    <label class="label">Motivo del rechazo *</label>
                    <textarea x-model="motivoRechazo" required rows="3" maxlength="500" class="input" placeholder="Explique por qué se rechaza..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModalRechazo = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingGestion" class="btn btn-danger"><span x-text="savingGestion ? 'Rechazando...' : 'Rechazar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Detalle Gestión --}}
    <div x-show="showModalDetalle" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalDetalle = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Detalle de Gestión</h3>
                <button @click="showModalDetalle = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <div class="p-6 space-y-4" x-show="gestionSeleccionada">
                 <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><p class="text-gray-400 text-xs">Matrícula</p><p class="font-mono font-semibold text-brand-600" x-text="gestionSeleccionada?.matricula?.codigo"></p></div>
                    <div><p class="text-gray-400 text-xs">Estado</p><span :class="{
                        'badge-warning': gestionSeleccionada?.estado === 'pendiente',
                        'badge-success': gestionSeleccionada?.estado === 'aprobado' || gestionSeleccionada?.estado === 'ejecutado',
                        'badge-danger': gestionSeleccionada?.estado === 'rechazado'
                    }" class="badge" x-text="gestionSeleccionada?.estado"></span></div>
                    <div class="col-span-2"><p class="text-gray-400 text-xs">Estudiante</p><p class="font-medium" x-text="(gestionSeleccionada?.matricula?.estudiante?.nombre||'') + ' ' + (gestionSeleccionada?.matricula?.estudiante?.apellido||'')"></p></div>
                    <div><p class="text-gray-400 text-xs">Tipo</p><p x-text="gestionSeleccionada?.tipo_gestion?.nombre"></p></div>
                    <div><p class="text-gray-400 text-xs">Fecha de solicitud</p><p x-text="formatFecha(gestionSeleccionada?.fecha_solicitud)"></p></div>
                    <div class="col-span-2" x-show="gestionSeleccionada?.oferta_destino"><p class="text-gray-400 text-xs">Oferta destino</p><p class="font-mono text-xs" x-text="gestionSeleccionada?.oferta_destino?.codigo"></p></div>
                    <div class="col-span-2"><p class="text-gray-400 text-xs">Motivo</p><p class="text-gray-700" x-text="gestionSeleccionada?.motivo"></p></div>
                    <div class="col-span-2" x-show="gestionSeleccionada?.motivo_decision"><p class="text-gray-400 text-xs">Motivo de decisión</p><p class="text-gray-700" x-text="gestionSeleccionada?.motivo_decision"></p></div>
                     <div x-show="gestionSeleccionada?.fecha_decision"><p class="text-gray-400 text-xs">Fecha de decisión</p><p x-text="formatFecha(gestionSeleccionada?.fecha_decision)"></p></div>
                 </div>
                 <div x-show="gestionSeleccionada?.datos_antes && gestionSeleccionada?.despues" class="border-t border-gray-100 pt-4">
                     <h4 class="text-sm font-semibold text-gray-800 mb-3">Cambio histórico</h4>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 space-y-2 text-sm">
                             <p class="font-semibold text-gray-700">Antes</p>
                             <p><span class="text-gray-400">Oferta:</span> <span class="font-mono" x-text="gestionSeleccionada?.datos_antes?.oferta_codigo || '-'" ></span></p>
                             <p><span class="text-gray-400">Nivel:</span> <span x-text="gestionSeleccionada?.datos_antes?.nivel_codigo || gestionSeleccionada?.datos_antes?.nivel_nombre || '-'" ></span></p>
                             <p><span class="text-gray-400">Plan:</span> <span x-text="gestionSeleccionada?.datos_antes?.plan_codigo || '-'" ></span></p>
                             <p><span class="text-gray-400">Horario:</span> <span x-text="gestionSeleccionada?.datos_antes?.horario_codigo || gestionSeleccionada?.datos_antes?.horario_nombre || '-'" ></span></p>
                             <p><span class="text-gray-400">Estado:</span> <span x-text="gestionSeleccionada?.datos_antes?.estado || '-'" ></span></p>
                         </div>
                         <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 space-y-2 text-sm">
                             <p class="font-semibold text-emerald-700">Después</p>
                             <p><span class="text-gray-400">Oferta:</span> <span class="font-mono" x-text="gestionSeleccionada?.despues?.oferta_codigo || '-'" ></span></p>
                             <p><span class="text-gray-400">Nivel:</span> <span x-text="gestionSeleccionada?.despues?.nivel_codigo || gestionSeleccionada?.despues?.nivel_nombre || '-'" ></span></p>
                             <p><span class="text-gray-400">Plan:</span> <span x-text="gestionSeleccionada?.despues?.plan_codigo || '-'" ></span></p>
                             <p><span class="text-gray-400">Horario:</span> <span x-text="gestionSeleccionada?.despues?.horario_codigo || gestionSeleccionada?.despues?.horario_nombre || '-'" ></span></p>
                             <p><span class="text-gray-400">Estado:</span> <span x-text="gestionSeleccionada?.despues?.estado || '-'" ></span></p>
                         </div>
                     </div>
                 </div>
                <div class="flex justify-end pt-2">
                    <button @click="showModalDetalle = false" class="btn btn-outline">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function matriculas() {
    return {
        activeTab: 'matriculas',
        loading: true, showModal: false, saving: false, error: '',
        matriculas: [], periodos: [], sucursales: [], ofertasDisponibles: [],
        flujo: { habilita_reserva_cupo: true, habilita_revision_contable: true, habilita_confirmacion_matricula: true, habilita_reenganche: true },
        resultadosEstudiantes: [], form: { estudiante_id: '', oferta_academica_id: '' },
        filtro: { periodo: '', sucursal: '', estado: '' },
        busquedaEstudiante: '', estudianteSeleccionado: '', savingConfirmar: false,

        // Pago desde matrícula
        showModalPago: false, savingPago: false, errorPago: '',
        pagoMatricula: null, pagoObligacionesIds: [],
        pagoForm: { metodo_pago_id: '', cuenta_bancaria_id: '', referencia_externa: '', monto_recibido: '' },
        flujoPagoMatricula: null,
        metodosPago: [],
        cuentasBancarias: [],

        // Nueva matrícula modal
        filtroNuevo: { periodo_id: '', plan_id: '', nivel_id: '' },
        planesEstudio: [],
        nivelesDisponibles: [],
        ofertasGrid: [],
        loadingOfertas: false,
        planActivoInfo: null,

        // Gestiones
        gestiones: [], tiposGestion: [], loadingGestiones: false,
        filtroGestion: { tipo: '', estado: '', periodo: '', plan: '', nivel: '', oferta: '' },
        ofertasGestion: [],
        showModalGestion: false, showModalRechazo: false, showModalDetalle: false,
        savingGestion: false, errorGestion: '', matriculaPreseleccionada: false,
        formGestion: { matricula_id: '', tipo_gestion_matricula_id: '', oferta_academica_destino_id: '', motivo: '' },
        matriculasActivas: [], ofertasDestino: [], ofertasDestinoLoading: false,
        gestionSeleccionada: null, motivoRechazo: '',
        busquedaEstudianteCodigo: '', resultadosBusquedaEstudiante: [],
        estudianteGestion: null, matriculasPagadas: [],

        get planesDisponibles() {
            return this.planesEstudio;
        },

        get requiereOfertaDestino() {
            const tipo = this.tiposGestion.find(t => t.id == this.formGestion.tipo_gestion_matricula_id);
            return tipo && ['CAM', 'CTR', 'TSU'].includes(tipo.codigo);
        },

        get tipoGestionCambioModalidad() {
            const tipo = this.tiposGestion.find(t => t.id == this.formGestion.tipo_gestion_matricula_id);
            return tipo?.codigo === 'CTR';
        },

        get planesGestionDisponibles() {
            const ids = new Set(this.ofertasGestion.map(o => o.nivel_academico?.version_plan_estudio?.plan_estudio_id).filter(Boolean));
            return this.planesEstudio.filter(plan => ids.has(plan.id));
        },

        get nivelesGestionDisponibles() {
            return this.ofertasGestion
                .filter(o => String(o.nivel_academico?.version_plan_estudio?.plan_estudio_id) === String(this.filtroGestion.plan))
                .map(o => o.nivel_academico)
                .filter((n, index, levels) => n && levels.findIndex(item => item.id === n.id) === index);
        },

        get ofertasGestionDisponibles() {
            return this.ofertasGestion.filter(o => String(o.nivel_academico_id) === String(this.filtroGestion.nivel));
        },

        async onFiltroGestionAcademicoChange(origen) {
            if (origen === 'periodo') { this.filtroGestion.plan = ''; this.filtroGestion.nivel = ''; this.filtroGestion.oferta = ''; await this.cargarOfertasGestion(); }
            if (origen === 'plan') { this.filtroGestion.nivel = ''; this.filtroGestion.oferta = ''; }
            if (origen === 'nivel') this.filtroGestion.oferta = '';
            await this.cargarMatriculasPagadas();
            await this.loadGestiones();
        },

        async cargarOfertasGestion() {
            if (!this.filtroGestion.periodo) { this.ofertasGestion = []; return; }
            try {
                const { data } = await window.axios.get(`/api/v1/ofertas/academicas?periodo_academico_id=${this.filtroGestion.periodo}&per_page=200`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.ofertasGestion = data.data?.data || data.data || [];
            } catch (e) { this.ofertasGestion = []; }
        },

        async tipoGestionSeleccionado() {
            this.formGestion.oferta_academica_destino_id = '';
            this.ofertasDestino = [];
            if (this.requiereOfertaDestino && this.formGestion.matricula_id) {
                await this.cargarOfertasDestino(this.matriculaGestionSeleccionada()?.oferta_academica_id);
            }
        },

        matriculaGestionSeleccionada() {
            return this.matriculasActivas.find(m => String(m.id) === String(this.formGestion.matricula_id));
        },

        async init() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            const [f, p, s, t, mp, pe, cb] = await Promise.allSettled([
                window.axios.get('/api/v1/seguridad/configuraciones-flujo-matricula', h),
                window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                window.axios.get('/api/v1/gestiones-matricula/tipos', h),
                window.axios.get('/api/v1/catalogos-academicos/metodos-pago', h),
                window.axios.get('/api/v1/catalogos-academicos/planes-estudio', h),
                window.axios.get('/api/v1/cuentas-bancarias', h).catch(() => ({ data: { data: [] } })),
            ]);
            const flujos = f.status === 'fulfilled' ? (f.value.data.data?.data || f.value.data.data || []) : [];
            this.flujo = flujos.find(c => c.origen === 'portal_administrativo' && c.estado === 'activo') || this.flujo;
            this.periodos = p.status === 'fulfilled' ? (p.value.data.data?.data || p.value.data.data || []) : [];
            this.sucursales = s.status === 'fulfilled' ? (s.value.data.data?.data || s.value.data.data || []) : [];
            this.tiposGestion = t.status === 'fulfilled' ? (t.value.data.data || []) : [];
            this.metodosPago = mp.status === 'fulfilled' ? (mp.value.data.data?.data || mp.value.data.data || []) : [];
            this.planesEstudio = pe.status === 'fulfilled' ? (pe.value.data.data?.data || pe.value.data.data || []).filter(plan => plan.estado !== 'inactivo') : [];
            this.cuentasBancarias = cb.status === 'fulfilled' ? (cb.value.data.data || []) : [];
            const activo = this.periodos.find(per => per.estado === 'activo');
            if (activo) {
                this.filtro.periodo = activo.id;
                this.filtroGestion.periodo = activo.id;
                await this.cargarOfertasGestion();
                await this.loadGestiones();
            }
            await this.load();
            this.pollingInterval = setInterval(() => this.load(), 30000);
        },

        async load() {
            this.loading = true;
            try {
                let url = '/api/v1/matriculas?';
                if (this.filtro.periodo) url += `periodo_academico_id=${this.filtro.periodo}&`;
                if (this.filtro.sucursal) url += `sucursal_id=${this.filtro.sucursal}&`;
                if (this.filtro.estado) url += `estado=${this.filtro.estado}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.matriculas = data.data?.data || data.data || [];
            } catch(e) {} finally { this.loading = false; }
        },

        async openModal() {
            this.form = { estudiante_id: '', oferta_academica_id: '' };
            this.busquedaEstudiante = ''; this.estudianteSeleccionado = ''; this.resultadosEstudiantes = []; this.error = '';
            this.filtroNuevo = { periodo_id: '', plan_id: '', nivel_id: '' };
            this.nivelesDisponibles = []; this.ofertasGrid = []; this.planActivoInfo = null;
            const activo = this.periodos.find(per => per.estado === 'activo');
            if (activo) this.filtroNuevo.periodo_id = activo.id;
            this.showModal = true;
        },

        async buscarEstudiantes() {
            if (this.busquedaEstudiante.length < 2) { this.resultadosEstudiantes = []; return; }
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes?buscar=${this.busquedaEstudiante}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.resultadosEstudiantes = data.data?.data || data.data || [];
            } catch(e) {}
        },

        async selectEstudiante(e) {
            this.form.estudiante_id = e.id;
            this.estudianteSeleccionado = e.codigo + ' — ' + (e.nombres || e.nombre || '') + ' ' + (e.apellidos || e.apellido || '');
            this.resultadosEstudiantes = []; this.busquedaEstudiante = '';
            this.form.oferta_academica_id = ''; this.ofertasGrid = []; this.nivelesDisponibles = [];
            this.planActivoInfo = null;
            await this.cargarPlanActivo();
            if (this.planActivoInfo?.plan_estudio_id) this.filtroNuevo.plan_id = this.planActivoInfo.plan_estudio_id;
            await this.cargarOfertas();
        },

        limpiarEstudiante() {
            this.form = { estudiante_id: '', oferta_academica_id: '' };
            this.estudianteSeleccionado = ''; this.resultadosEstudiantes = []; this.busquedaEstudiante = '';
            this.nivelesDisponibles = []; this.ofertasGrid = []; this.planActivoInfo = null;
            this.filtroNuevo = { periodo_id: '', plan_id: '', nivel_id: '' };
            const activo = this.periodos.find(per => per.estado === 'activo');
            if (activo) this.filtroNuevo.periodo_id = activo.id;
        },

        async cargarPlanActivo() {
            if (!this.form.estudiante_id) return;
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes/${this.form.estudiante_id}/plan-activo`, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                this.planActivoInfo = data.data || null;
            } catch(e) { this.planActivoInfo = null; }
        },

        async onPeriodoChange() {
            this.filtroNuevo.plan_id = this.planActivoInfo?.plan_estudio_id || '';
            this.filtroNuevo.nivel_id = '';
            this.form.oferta_academica_id = '';
            await this.cargarOfertas();
        },

        async onPlanChange() {
            this.filtroNuevo.nivel_id = '';
            this.form.oferta_academica_id = '';
            await this.cargarOfertas();
        },

        async onNivelChange() {
            this.form.oferta_academica_id = '';
            await this.cargarOfertas();
        },

        async cargarOfertas() {
            if (!this.form.estudiante_id || !this.filtroNuevo.periodo_id || !this.filtroNuevo.plan_id) {
                this.ofertasGrid = []; this.nivelesDisponibles = [];
                return;
            }
            this.loadingOfertas = true;
            try {
                let url = `/api/v1/ofertas/academicas?periodo_academico_id=${this.filtroNuevo.periodo_id}&estado=abierto&per_page=200`;
                const { data } = await window.axios.get(url, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                let ofertas = data.data?.data || data.data || [];

                // Filter: only offers with available spots
                ofertas = ofertas.filter(o => (o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0))) > 0);

                ofertas = ofertas.filter(o => String(o.plan_estudio_id) === String(this.filtroNuevo.plan_id));

                // Extract unique niveles from filtered offers
                const nivelMap = {};
                ofertas.forEach(o => {
                    if (o.nivel_academico?.id) {
                        nivelMap[o.nivel_academico.id] = {
                            id: o.nivel_academico.id,
                            codigo: o.nivel_academico.codigo || '',
                            nombre: o.nivel_academico.nombre || '',
                        };
                    }
                });
                this.nivelesDisponibles = Object.values(nivelMap).sort((a, b) => (a.codigo || '').localeCompare(b.codigo || ''));

                // Filter by nivel if selected
                if (this.filtroNuevo.nivel_id) {
                    ofertas = ofertas.filter(o => o.nivel_academico?.id == this.filtroNuevo.nivel_id);
                }

                this.ofertasGrid = ofertas.map(o => ({
                    ...o,
                    regimen_academico: o.nivel_academico?.regimen_academico?.nombre || o.nivel_academico?.regimenAcademico?.nombre || o.regimen || null,
                }));
            } catch(e) {
                this.ofertasGrid = []; this.nivelesDisponibles = [];
            } finally { this.loadingOfertas = false; }
        },

        async reservar() {
            this.saving = true; this.error = '';
            try {
                if (!this.flujo.habilita_reserva_cupo) throw new Error('La reserva de cupo está deshabilitada');
                const payload = { ...this.form, plan_estudio_id: this.filtroNuevo.plan_id };
                const { data } = await window.axios.post('/api/v1/matriculas/reservar', payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showModal = false; this.toast('Matrícula reservada', 'success'); await this.load(); }
                else { this.error = data.mensaje || 'Error'; }
            } catch(e) { this.error = window.extractError(e, 'Error'); } finally { this.saving = false; }
        },

        async confirmar(m) {
            if (this.savingConfirmar) return;
            if (!this.flujo.habilita_confirmacion_matricula) return;
            this.savingConfirmar = true;
            try {
                const { data } = await window.axios.post(`/api/v1/matriculas/${m.id}/confirmar`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.toast('Matrícula confirmada', 'success'); await this.load(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al confirmar'), 'error'); }
            finally { this.savingConfirmar = false; }
        },

        async cancelar(m) {
            if (!confirm('¿Cancelar esta matrícula?')) return;
            try {
                const { data } = await window.axios.post(`/api/v1/matriculas/${m.id}/cancelar`, { motivo: 'Cancelación desde panel' }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.toast('Matrícula cancelada', 'success'); await this.load(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al cancelar'), 'error'); }
        },

        async abrirPago(m) {
            if (!this.flujo.habilita_revision_contable) return;
            this.pagoMatricula = null;
            this.pagoObligacionesIds = [];
            this.pagoForm = { metodo_pago_id: '', cuenta_bancaria_id: '', referencia_externa: '', monto_recibido: '' };
            this.errorPago = '';
            this.flujoPagoMatricula = null;
            this.showModalPago = true;
            try {
                const { data } = await window.axios.get(`/api/v1/matriculas/${m.id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.pagoMatricula = data.data || m;
                await this.cargarFlujoPagoMatricula();
                this.aplicarSeleccionObligacionesPorFlujo();
            } catch(e) {
                this.pagoMatricula = m;
                this.errorPago = 'No se pudieron cargar las obligaciones';
                await this.cargarFlujoPagoMatricula();
                this.aplicarSeleccionObligacionesPorFlujo();
            }
        },

        async cargarFlujoPagoMatricula() {
            try {
                const conceptoId = this.pagoMatricula?.obligaciones?.[0]?.concepto_pago_id || null;
                const metodoId = this.pagoForm.metodo_pago_id || null;
                const { data } = await window.axios.get('/api/v1/seguridad/configuraciones-flujo-matricula', {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` }
                });
                const lista = data.data?.data || data.data || [];
                this.flujoPagoMatricula = lista.find(c =>
                    c.estado === 'activo' &&
                    c.origen === 'portal_administrativo' &&
                    (!conceptoId || String(c.concepto_pago_id) === String(conceptoId)) &&
                    (!metodoId || !c.metodo_pago_id || String(c.metodo_pago_id) === String(metodoId))
                ) || this.flujo;
            } catch (e) {
                this.flujoPagoMatricula = this.flujo;
            }
        },

        aplicarSeleccionObligacionesPorFlujo() {
            if ((this.flujoPagoMatricula?.habilita_seleccion_obligaciones ?? true)) return;
            this.pagoObligacionesIds = (this.pagoMatricula?.obligaciones || []).map(o => o.id);
        },

        get pagoEsEfectivo() {
            const metodo = this.metodosPago.find(x => x.id == this.pagoForm.metodo_pago_id);
            return metodo?.codigo === 'EFE';
        },

        get pagoTotalSeleccionado() {
            return (this.pagoMatricula?.obligaciones || [])
                .filter(o => this.pagoObligacionesIds.includes(o.id))
                .reduce((s, o) => s + Number(o.monto || 0) - Number(o.monto_pagado || 0), 0);
        },

        get pagoVueltoCalculado() {
            if (!this.pagoEsEfectivo) return 0;
            const recibido = Number(this.pagoForm.monto_recibido || 0);
            return recibido > this.pagoTotalSeleccionado ? (recibido - this.pagoTotalSeleccionado) : 0;
        },

        onPagoMetodoChange() {
            if (!this.requiereCuentaBancaria(this.pagoForm.metodo_pago_id)) {
                this.pagoForm.cuenta_bancaria_id = '';
            }
            if (!this.pagoEsEfectivo) {
                this.pagoForm.monto_recibido = '';
            }
            this.cargarFlujoPagoMatricula();
        },

        requiereCuentaBancaria(id) {
            const metodo = this.metodosPago.find(x => x.id == id);
            return ['DEP', 'TRA'].includes(metodo?.codigo);
        },

        async registrarPagoMatricula() {
            if (this.savingPago || this.pagoObligacionesIds.length === 0) return;
            if (this.flujoPagoMatricula && !this.flujoPagoMatricula.habilita_aprobacion_pago && !this.flujoPagoMatricula.habilita_solicitud_link) {
                this.errorPago = 'Este flujo no permite registrar pagos en este estado';
                return;
            }
            this.savingPago = true; this.errorPago = '';
            try {
                const obligacionesDetalle = (this.pagoMatricula.obligaciones || [])
                    .filter(o => this.pagoObligacionesIds.includes(o.id))
                    .map(o => ({
                        obligacion_id: o.id,
                        monto_aplicado: o.monto - (o.monto_pagado || 0),
                    }));
                const montoTotal = obligacionesDetalle.reduce((s, o) => s + Number(o.monto_aplicado), 0);
                const payload = {
                    estudiante_id: this.pagoMatricula.estudiante_id,
                    matricula_id: this.pagoMatricula.id,
                    concepto_pago_id: obligacionesDetalle.length > 0
                        ? (this.pagoMatricula.obligaciones?.find(o => o.id === this.pagoObligacionesIds[0])?.concepto_pago_id || '')
                        : '',
                    metodo_pago_id: this.pagoForm.metodo_pago_id,
                    cuenta_bancaria_id: this.pagoForm.cuenta_bancaria_id || null,
                    monto: montoTotal,
                    monto_recibido: this.pagoEsEfectivo ? (this.pagoForm.monto_recibido || null) : null,
                    fecha_proceso: window.toLocalDateInput(),
                    referencia_externa: this.pagoForm.referencia_externa || '',
                    obligaciones: obligacionesDetalle,
                };
                const { data } = await window.axios.post('/api/v1/pagos/registrar', payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    this.showModalPago = false;
                    this.toast('Pago registrado y aprobado. Matrícula confirmada.', 'success');
                    await this.load();
                } else {
                    this.errorPago = data.mensaje || 'Error';
                }
            } catch(e) { this.errorPago = window.extractError(e, 'Error al registrar pago'); }
            finally { this.savingPago = false; }
        },

        fmtMontoPago(n) { return new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n) || 0); },

        // ============ GESTIONES ============

        async buscarEstudiantePorCodigo() {
            const q = this.busquedaEstudianteCodigo.trim();
            if (q.length < 2) { this.resultadosBusquedaEstudiante = []; return; }
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes?buscar=${q}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.resultadosBusquedaEstudiante = data.data?.data || data.data || [];
            } catch(e) { this.resultadosBusquedaEstudiante = []; }
        },

        seleccionarEstudianteGestion(e) {
            this.estudianteGestion = e;
            this.resultadosBusquedaEstudiante = [];
            this.busquedaEstudianteCodigo = '';
            this.matriculasPagadas = [];
            if (this.filtroGestion.periodo) this.cargarMatriculasPagadas();
        },

        async cargarMatriculasPagadas() {
            if (!this.estudianteGestion || !this.filtroGestion.periodo) return;
            try {
                let filtrosAcademicos = '';
                if (this.filtroGestion.oferta) filtrosAcademicos += `&oferta_academica_id=${this.filtroGestion.oferta}`;
                const { data } = await window.axios.get(
                    `/api/v1/matriculas?estudiante_id=${this.estudianteGestion.id}&periodo_academico_id=${this.filtroGestion.periodo}&estado=matriculado&per_page=50${filtrosAcademicos}`,
                    { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } }
                );
                this.matriculasPagadas = data.data?.data || data.data || [];
            } catch(e) { this.matriculasPagadas = []; }
        },

        limpiarBusquedaGestion() {
            this.estudianteGestion = null;
            this.matriculasPagadas = [];
            this.busquedaEstudianteCodigo = '';
            this.resultadosBusquedaEstudiante = [];
            this.filtroGestion = { tipo: '', estado: '', periodo: '', plan: '', nivel: '', oferta: '' };
            this.ofertasGestion = [];
        },

        async loadGestiones() {
            this.loadingGestiones = true;
            try {
                let url = '/api/v1/gestiones-matricula?per_page=50&';
                if (this.estudianteGestion) url += `estudiante_id=${this.estudianteGestion.id}&`;
                if (this.filtroGestion.periodo) url += `periodo_academico_id=${this.filtroGestion.periodo}&`;
                if (this.filtroGestion.plan) url += `plan_estudio_id=${this.filtroGestion.plan}&`;
                if (this.filtroGestion.nivel) url += `nivel_academico_id=${this.filtroGestion.nivel}&`;
                if (this.filtroGestion.oferta) url += `oferta_academica_id=${this.filtroGestion.oferta}&`;
                if (this.filtroGestion.tipo) url += `tipo_gestion_matricula_id=${this.filtroGestion.tipo}&`;
                if (this.filtroGestion.estado) url += `estado=${this.filtroGestion.estado}&`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.gestiones = data.data?.data || data.data || [];
            } catch(e) {} finally { this.loadingGestiones = false; }
        },

        async openModalGestion() {
            this.formGestion = { matricula_id: '', tipo_gestion_matricula_id: '', oferta_academica_destino_id: '', motivo: '' };
            this.errorGestion = ''; this.matriculaPreseleccionada = false;
            await this.cargarMatriculasActivas();
            this.showModalGestion = true;
        },

        async nuevaGestionPara(m) {
            this.formGestion = { matricula_id: m.id, tipo_gestion_matricula_id: '', oferta_academica_destino_id: '', motivo: '' };
            this.errorGestion = ''; this.matriculaPreseleccionada = true;
            this.matriculasActivas = [m];
            await this.cargarOfertasDestino(m.oferta_academica_id);
            this.showModalGestion = true;
        },

        async cargarMatriculasActivas() {
            try {
                let url = '/api/v1/matriculas?estado=matriculado&per_page=100';
                if (this.estudianteGestion) url += `&estudiante_id=${this.estudianteGestion.id}`;
                if (this.filtroGestion.periodo) url += `&periodo_academico_id=${this.filtroGestion.periodo}`;
                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.matriculasActivas = data.data?.data || data.data || [];
            } catch(e) { this.matriculasActivas = []; }
        },

        async cargarOfertasDestino(excluirOfertaId = null) {
            this.ofertasDestinoLoading = true;
            try {
                const { data } = await window.axios.get('/api/v1/ofertas/academicas?estado=abierto', { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                let ofertas = (data.data?.data || data.data || []).filter(o =>
                    (o.cupos_disponibles ?? (o.cupo_maximo - (o.cupos_matriculados||0) - (o.cupos_reservados||0))) > 0
                    && o.id !== excluirOfertaId
                );
                const matricula = this.matriculaGestionSeleccionada();
                const origen = matricula?.oferta_academica;
                const tipo = this.tiposGestion.find(t => t.id == this.formGestion.tipo_gestion_matricula_id);
                if ((tipo?.codigo === 'CAM' || tipo?.codigo === 'CTR') && origen) {
                    ofertas = ofertas.filter(o =>
                        o.acepta_cambios_horario === true
                        && o.periodo_academico_id === origen.periodo_academico_id
                        && o.nivel_academico_id === origen.nivel_academico_id
                        && (tipo.codigo === 'CAM' ? o.horario_id !== origen.horario_id : o.horario_id === origen.horario_id)
                        && (tipo.codigo === 'CTR' ? o.modalidad_id !== origen.modalidad_id : true)
                        && (o.plan_estudio_id ?? o.nivel_academico?.version_plan_estudio?.plan_estudio_id) === (origen.plan_estudio_id ?? origen.nivel_academico?.version_plan_estudio?.plan_estudio_id)
                    );
                }
                this.ofertasDestino = ofertas;
            } catch(e) { this.ofertasDestino = []; }
            finally { this.ofertasDestinoLoading = false; }
        },

        async solicitarGestion() {
            this.savingGestion = true; this.errorGestion = '';
            try {
                const payload = {
                    matricula_id: this.formGestion.matricula_id,
                    tipo_gestion_matricula_id: this.formGestion.tipo_gestion_matricula_id,
                    motivo: this.formGestion.motivo,
                };
                if (this.requiereOfertaDestino && this.formGestion.oferta_academica_destino_id) {
                    payload.oferta_academica_destino_id = this.formGestion.oferta_academica_destino_id;
                }
                const { data } = await window.axios.post('/api/v1/gestiones-matricula/solicitar', payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showModalGestion = false; this.toast('Gestión solicitada', 'success'); await this.loadGestiones(); }
                else { this.errorGestion = data.mensaje || 'Error'; }
            } catch(e) { this.errorGestion = window.extractError(e, 'Error al solicitar'); } finally { this.savingGestion = false; }
        },

        async aprobarGestion(g) {
            if (!confirm(`¿Aprobar la gestión "${g.tipo_gestion?.nombre}" de la matrícula ${g.matricula?.codigo}? Se ejecutará inmediatamente.`)) return;
            try {
                const { data } = await window.axios.post(`/api/v1/gestiones-matricula/${g.id}/aprobar`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.toast('Gestión aprobada y ejecutada', 'success'); await this.loadGestiones(); await this.load(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al aprobar'), 'error'); }
        },

        abrirRechazo(g) {
            this.gestionSeleccionada = g;
            this.motivoRechazo = '';
            this.showModalRechazo = true;
        },

        async rechazarGestion() {
            this.savingGestion = true;
            try {
                const { data } = await window.axios.post(`/api/v1/gestiones-matricula/${this.gestionSeleccionada.id}/rechazar`, { motivo_decision: this.motivoRechazo }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') { this.showModalRechazo = false; this.toast('Gestión rechazada', 'success'); await this.loadGestiones(); }
            } catch(e) { this.toast(window.extractError(e, 'Error al rechazar'), 'error'); } finally { this.savingGestion = false; }
        },

        async verDetalle(g) {
            try {
                const { data } = await window.axios.get(`/api/v1/gestiones-matricula/${g.id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.gestionSeleccionada = data.data || g;
            } catch(e) { this.gestionSeleccionada = g; }
            this.showModalDetalle = true;
        },

        formatFecha(f) {
            if (!f) return '-';
            return window.formatDateLocal(f);
        },

        toast(message, type) {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type } }));
        }
    }
}
</script>
@endsection
