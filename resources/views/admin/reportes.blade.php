@extends('layouts.admin')
@section('content')
<div x-data="reportes()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Reportes</h1>
            <p class="page-subtitle">Reportes académicos, financieros, recibos y caja</p>
        </div>
        <button x-show="reporteActual" @click="consultar()" class="btn btn-outline btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
            Actualizar
        </button>
        <div class="flex gap-2">
            <button x-show="reporteActual" @click="exportar('excel')" :disabled="exportando" class="btn btn-outline btn-sm"><span x-show="!exportando">Excel</span><span x-show="exportando">⏳</span></button>
            <button x-show="reporteActual" @click="exportar('pdf')" :disabled="exportando" class="btn btn-outline btn-sm">PDF</button>
        </div>
    </div>

    {{-- Tabs de categoría --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1 overflow-x-auto">
            <button x-show="api.hasPermission('reportes.academicos.consultar')" @click="cambiarCategoria('academicos')" :class="categoria === 'academicos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Académicos</button>
            <button x-show="api.hasPermission('reportes.financieros.consultar')" @click="cambiarCategoria('financieros')" :class="categoria === 'financieros' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Financieros</button>
            <button x-show="api.hasPermission('reportes.caja.consultar')" @click="cambiarCategoria('recibos')" :class="categoria === 'recibos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Recibos</button>
            <button x-show="api.hasPermission('reportes.caja.consultar')" @click="cambiarCategoria('caja')" :class="categoria === 'caja' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Caja</button>
        </nav>
    </div>

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="label">Reporte</label>
                    <select x-model="reporteId" @change="cambiarReporte()" class="input">
                        <template x-for="r in reportesDeCategoria" :key="r.id">
                            <option :value="r.id" x-text="r.label"></option>
                        </template>
                    </select>
                </div>
                <template x-if="reporteActual?.fechaDesde !== false">
                    <div>
                        <label class="label">Desde <span x-show="reporteActual?.fechaRequerida" class="text-red-500">*</span></label>
                        <input x-model="filtros.fecha_desde" type="date" class="input">
                    </div>
                </template>
                <template x-if="reporteActual?.fechaHasta !== false">
                    <div>
                        <label class="label">Hasta <span x-show="reporteActual?.fechaRequerida" class="text-red-500">*</span></label>
                        <input x-model="filtros.fecha_hasta" type="date" class="input">
                    </div>
                </template>
                <template x-if="reporteActual?.sucursal">
                    <div>
                        <label class="label">Sucursal</label>
                        <select x-model="filtros.sucursal_id" class="input">
                            <option value="">Todas</option>
                            <template x-for="s in sucursales" :key="s.id"><option :value="s.id" x-text="s.codigo + ' · ' + s.nombre"></option></template>
                        </select>
                    </div>
                </template>
                <template x-if="reporteActual?.periodo">
                    <div>
                        <label class="label">Período</label>
                        <select x-model="filtros.periodo_academico_id" class="input">
                            <option value="">Todos</option>
                            <template x-for="p in periodos" :key="p.id"><option :value="p.id" x-text="p.codigo + ' · ' + p.nombre"></option></template>
                        </select>
                    </div>
                </template>
                <template x-if="reporteActual?.estadoMatricula">
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="filtros.estado" class="input">
                            <option value="">Todos</option>
                            <option value="matriculado">Matriculado</option>
                            <option value="retirado">Retirado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </template>
                <template x-if="reporteActual?.estadoRecibo">
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="filtros.estado" class="input">
                            <option value="">Todos</option>
                            <option value="emitido">Emitido</option>
                            <option value="reversado">Reversado</option>
                            <option value="anulado">Anulado</option>
                        </select>
                    </div>
                </template>
                <template x-if="reporteActual?.oferta">
                    <div>
                        <label class="label">Horario (Oferta) *</label>
                        <select x-model="filtros.oferta_academica_id" class="input">
                            <option value="">Seleccionar horario...</option>
                            <template x-for="o in ofertas" :key="o.id">
                                <option :value="o.id" x-text="o.codigo + ' · ' + (o.nivel_academico?.nombre || '') + ' · ' + (o.horario?.nombre || '')"></option>
                            </template>
                        </select>
                    </div>
                </template>
                <template x-if="reporteActual?.estudiante">
                    <div class="relative">
                        <label class="label">Estudiante *</label>
                        <input x-model="busquedaEstudiante" @input.debounce.300ms="buscarEstudiantes()" type="text" placeholder="Buscar por código o nombre..." class="input">
                        <div x-show="resultadosEstudiantes.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="e in resultadosEstudiantes" :key="e.id">
                                <button type="button" @click="filtros.estudiante_id = e.id; busquedaEstudiante = e.codigo + ' — ' + (e.nombres || e.nombre || '') + ' ' + (e.apellidos || e.apellido || ''); resultadosEstudiantes = [];" class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm" x-text="e.codigo + ' — ' + (e.nombres || e.nombre || '') + ' ' + (e.apellidos || e.apellido || '')"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex flex-wrap gap-2" x-show="filtrosAplicados.length > 0">
                    <template x-for="f in filtrosAplicados" :key="f">
                        <span class="badge badge-neutral" x-text="f"></span>
                    </template>
                </div>
                <button @click="consultar()" class="btn btn-primary btn-sm ml-auto">Consultar</button>
            </div>
        </div>
    </div>

    {{-- Totales --}}
    <template x-if="totales.length > 0">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <template x-for="t in totales" :key="t.label">
                <div class="stat-card">
                    <div>
                        <p class="stat-value" x-text="t.valor"></p>
                        <p class="stat-label" x-text="t.label"></p>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Tabla dinámica --}}
    <template x-if="!loading && consultado">
        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <template x-for="col in reporteActual?.columns || []" :key="col.key">
                                <th :class="col.numeric ? 'text-right' : ''" x-text="col.label"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(fila, i) in filas" :key="i">
                            <tr>
                                <template x-for="col in reporteActual?.columns || []" :key="col.key">
                                    <td :class="(col.numeric ? 'text-right font-medium ' : '') + (col.mono ? 'font-mono text-xs ' : '')" x-html="celda(fila, col)"></td>
                                </template>
                            </tr>
                        </template>
                        <template x-if="filas.length === 0">
                            <tr><td :colspan="(reporteActual?.columns || []).length" class="text-center py-10 text-gray-400 text-sm">Sin resultados para los filtros aplicados</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
            {{-- Paginación --}}
            <div class="card-body border-t border-gray-100 flex items-center justify-between" x-show="paginacion.last_page > 1">
                <p class="text-xs text-gray-400">Página <span x-text="paginacion.current_page"></span> de <span x-text="paginacion.last_page"></span> · <span x-text="paginacion.total"></span> registros</p>
                <div class="flex gap-2">
                    <button @click="cambiarPagina(paginacion.current_page - 1)" :disabled="paginacion.current_page <= 1" class="btn btn-outline btn-sm">Anterior</button>
                    <button @click="cambiarPagina(paginacion.current_page + 1)" :disabled="paginacion.current_page >= paginacion.last_page" class="btn btn-outline btn-sm">Siguiente</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Estado inicial --}}
    <template x-if="!loading && !consultado">
        <div class="card">
            <div class="card-body py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                <p class="text-gray-500 font-medium">Seleccione un reporte y los filtros, luego presione Consultar</p>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
function reportes() {
    const M = (n) => 'L ' + new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n) || 0);
    const F = (f) => {
        if (!f) return '-';
        const d = window.formatDateLocal(f);
        if (d && d !== '-') return d;
        return String(f).substring(0, 10).split('-').reverse().join('-');
    };
    const BADGE = (txt, cls) => `<span class="badge badge-${cls}">${txt}</span>`;

    return {
        categoria: 'academicos',
        reporteId: '',
        loading: false, consultado: false, exportando: false,
        filas: [], totales: [], filtrosAplicados: [],
        paginacion: { current_page: 1, last_page: 1, total: 0 },
        sucursales: [], periodos: [], ofertas: [],
        busquedaEstudiante: '', resultadosEstudiantes: [],
        filtros: { fecha_desde: '', fecha_hasta: '', sucursal_id: '', periodo_academico_id: '', estado: '', oferta_academica_id: '', estudiante_id: '' },

        catalogo: {
            academicos: [
                { id: 'por-periodo', label: 'Matriculados por Período', url: '/api/v1/reportes/academicos/por-periodo', fechaDesde: false, fechaHasta: false, periodo: true, sucursal: true,
                  columns: [ {key:'periodo_codigo', label:'Código', mono:true}, {key:'periodo_nombre', label:'Período'}, {key:'total_matriculados', label:'Matriculados', numeric:true} ],
                  totalesFn: (filas) => [{ label: 'Total matriculados', valor: filas.reduce((a, f) => a + Number(f.total_matriculados || 0), 0) }] },
                { id: 'por-sucursal', label: 'Matriculados por Sucursal', url: '/api/v1/reportes/academicos/por-sucursal', fechaDesde: false, fechaHasta: false, periodo: true,
                  columns: [ {key:'sucursal_codigo', label:'Código', mono:true}, {key:'sucursal_nombre', label:'Sucursal'}, {key:'total_matriculados', label:'Matriculados', numeric:true} ],
                  totalesFn: (filas) => [{ label: 'Total matriculados', valor: filas.reduce((a, f) => a + Number(f.total_matriculados || 0), 0) }] },
                { id: 'por-nivel', label: 'Matriculados por Nivel', url: '/api/v1/reportes/academicos/por-nivel', fechaDesde: false, fechaHasta: false, periodo: true, sucursal: true,
                  columns: [ {key:'nivel_codigo', label:'Código', mono:true}, {key:'nivel_nombre', label:'Nivel'}, {key:'total_matriculados', label:'Matriculados', numeric:true} ],
                  totalesFn: (filas) => [{ label: 'Total matriculados', valor: filas.reduce((a, f) => a + Number(f.total_matriculados || 0), 0) }] },
                { id: 'por-docente', label: 'Matriculados por Docente', url: '/api/v1/reportes/academicos/por-docente', fechaDesde: false, fechaHasta: false, periodo: true, sucursal: true, estadoMatricula: true,
                  columns: [ {key:'docente', label:'Docente', fn: f => `${f.docente_nombre || ''} ${f.docente_apellido || ''}`}, {key:'nivel_nombre', label:'Nivel'}, {key:'horario_nombre', label:'Horario'}, {key:'total_matriculados', label:'Matriculados', numeric:true} ],
                  totalesFn: (filas) => [{ label: 'Total matriculados', valor: filas.reduce((a, f) => a + Number(f.total_matriculados || 0), 0) }] },
                { id: 'grupo', label: 'Alumnos por Horario', url: '/api/v1/reportes/academicos/grupo', fechaDesde: false, fechaHasta: false, oferta: true,
                  columns: [ {key:'sucursal_codigo', label:'Sucursal', mono:true, fn: f => `${f.sucursal_codigo || ''} · ${f.sucursal_nombre || ''}`}, {key:'periodo_nombre', label:'Período'}, {key:'plan_nombre', label:'Plan'}, {key:'nivel_codigo', label:'Nivel', mono:true, fn: f => `${f.nivel_codigo || ''} · ${f.nivel_nombre || ''}`}, {key:'horario_nombre', label:'Horario', fn: f => `${f.horario_codigo || ''} · ${f.horario_nombre || ''}`}, {key:'docente', label:'Docente', fn: f => `${f.docente_codigo || ''} · ${(f.docente_nombre || '')} ${(f.docente_apellido || '')}`}, {key:'estudiante_codigo', label:'Código Alumno', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'correo', label:'Correo'}, {key:'telefono', label:'Teléfono'}, {key:'estado_matricula', label:'Estado', fn: f => BADGE(f.estado_matricula, 'success')} ],
                  totalesFn: (filas) => [{ label: 'Alumnos en el horario', valor: filas.length }] },
                { id: 'calificaciones-grupo', label: 'Calificaciones por Horario', url: '/api/v1/reportes/academicos/calificaciones-por-grupo', fechaDesde: false, fechaHasta: false, oferta: true,
                  columns: [ {key:'estudiante_codigo', label:'Código', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'nota_final', label:'Nota Final', numeric:true}, {key:'faltas', label:'Faltas', numeric:true}, {key:'estado_calificacion', label:'Estado', fn: f => BADGE(f.estado_calificacion, f.estado_calificacion === 'registrado' ? 'success' : 'info')} ],
                  totalesFn: (filas) => {
                      const conNota = filas.filter(f => f.nota_final !== null && f.nota_final !== undefined);
                      const prom = conNota.length ? (conNota.reduce((a, f) => a + Number(f.nota_final), 0) / conNota.length).toFixed(1) : '-';
                      return [{ label: 'Con calificación', valor: conNota.length }, { label: 'Promedio del horario', valor: prom }];
                  } },
                { id: 'nivel-actual', label: 'Nivel Actual del Estudiante', url: '/api/v1/reportes/academicos/nivel-actual', fechaDesde: false, fechaHasta: false, estudiante: true, single: true,
                  columns: [ {key:'nivel_codigo', label:'Código Nivel', mono:true}, {key:'nivel_nombre', label:'Nivel'}, {key:'periodo_nombre', label:'Período'}, {key:'fecha_confirmacion', label:'Confirmado', fn: f => F(f.fecha_confirmacion)} ] },
            ],
            financieros: [
                { id: 'por-concepto', label: 'Ingresos por Concepto', url: '/api/v1/reportes/financieros/por-concepto', fechaRequerida: true, sucursal: true, periodo: true,
                  columns: [ {key:'concepto_codigo', label:'Código', mono:true}, {key:'concepto_nombre', label:'Concepto'}, {key:'cantidad', label:'Cantidad', numeric:true}, {key:'total_monto', label:'Total', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Operaciones', valor: filas.reduce((a, f) => a + Number(f.cantidad || 0), 0) }, { label: 'Total ingresos', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
                { id: 'por-metodo', label: 'Ingresos por Forma de Pago', url: '/api/v1/reportes/financieros/por-metodo', fechaRequerida: true, sucursal: true,
                  columns: [ {key:'metodo_codigo', label:'Código', mono:true}, {key:'metodo_nombre', label:'Forma de Pago'}, {key:'cantidad', label:'Cantidad', numeric:true}, {key:'total_monto', label:'Total', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Operaciones', valor: filas.reduce((a, f) => a + Number(f.cantidad || 0), 0) }, { label: 'Total ingresos', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
                { id: 'por-sucursal-fin', label: 'Ingresos por Sucursal', url: '/api/v1/reportes/financieros/por-sucursal', fechaRequerida: true, periodo: true,
                  columns: [ {key:'sucursal_codigo', label:'Código', mono:true}, {key:'sucursal_nombre', label:'Sucursal'}, {key:'cantidad', label:'Cantidad', numeric:true}, {key:'total_monto', label:'Total', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Operaciones', valor: filas.reduce((a, f) => a + Number(f.cantidad || 0), 0) }, { label: 'Total ingresos', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
                { id: 'pagos-pendientes', label: 'Pagos Pendientes', url: '/api/v1/reportes/financieros/pagos-pendientes', fechaDesde: false, fechaHasta: false, sucursal: true, periodo: true,
                  columns: [ {key:'sucursal_codigo', label:'Sucursal', mono:true, fn: f => `${f.sucursal_codigo || ''} · ${f.sucursal_nombre || ''}`}, {key:'periodo_nombre', label:'Período'}, {key:'plan_nombre', label:'Plan'}, {key:'nivel_codigo', label:'Nivel', mono:true, fn: f => `${f.nivel_codigo || ''} · ${f.nivel_nombre || ''}`}, {key:'horario_nombre', label:'Horario', fn: f => `${f.horario_codigo || ''} · ${f.horario_nombre || ''}`}, {key:'docente_nombre', label:'Docente', fn: f => `${f.docente_codigo || ''} · ${f.docente_nombre || ''}`}, {key:'estudiante_codigo', label:'Código', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'correo', label:'Correo'}, {key:'telefono', label:'Teléfono'}, {key:'concepto_codigo', label:'Concepto', mono:true, fn: f => `${f.concepto_codigo || ''} · ${f.concepto_nombre || ''}`}, {key:'monto', label:'Monto', numeric:true, money:true}, {key:'fecha_pago', label:'Fecha', fn: f => F(f.fecha_pago)}, {key:'referencia_externa', label:'Referencia'} ],
                  totalesFn: (filas) => [{ label: 'Pagos pendientes', valor: filas.length }, { label: 'Monto por revisar', valor: M(filas.reduce((a, f) => a + Number(f.monto || 0), 0)) }] },
                { id: 'pagos-rechazados', label: 'Pagos Rechazados', url: '/api/v1/reportes/financieros/pagos-rechazados', sucursal: true,
                  columns: [ {key:'sucursal_codigo', label:'Sucursal', mono:true, fn: f => `${f.sucursal_codigo || ''} · ${f.sucursal_nombre || ''}`}, {key:'periodo_nombre', label:'Período'}, {key:'plan_nombre', label:'Plan'}, {key:'nivel_codigo', label:'Nivel', mono:true, fn: f => `${f.nivel_codigo || ''} · ${f.nivel_nombre || ''}`}, {key:'horario_nombre', label:'Horario', fn: f => `${f.horario_codigo || ''} · ${f.horario_nombre || ''}`}, {key:'docente_nombre', label:'Docente', fn: f => `${f.docente_codigo || ''} · ${f.docente_nombre || ''}`}, {key:'estudiante_codigo', label:'Código', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'correo', label:'Correo'}, {key:'telefono', label:'Teléfono'}, {key:'concepto_codigo', label:'Concepto', mono:true, fn: f => `${f.concepto_codigo || ''} · ${f.concepto_nombre || ''}`}, {key:'monto', label:'Monto', numeric:true, money:true}, {key:'motivo_rechazo', label:'Motivo'}, {key:'fecha_rechazo', label:'Fecha Rechazo', fn: f => F(f.fecha_rechazo)} ],
                  totalesFn: (filas) => [{ label: 'Pagos rechazados', valor: filas.length }] },
            ],
            recibos: [
                { id: 'por-orden', label: 'Recibos por Orden Numérico', url: '/api/v1/reportes/recibos/por-orden', fechaRequerida: true, sucursal: true, estadoRecibo: true, paginado: true,
                  columns: [ {key:'numero_recibo', label:'# Recibo', mono:true, fn: f => String(f.numero_recibo).padStart(6, '0')}, {key:'estudiante', label:'Estudiante', fn: f => `${f.estudiante?.nombre || ''} ${f.estudiante?.apellido || ''}`}, {key:'metodo_pago', label:'Método', fn: f => f.metodo_pago?.nombre || '-'}, {key:'monto_total', label:'Monto', numeric:true, money:true}, {key:'estado', label:'Estado', fn: f => BADGE(f.estado, f.estado === 'emitido' ? 'success' : f.estado === 'anulado' ? 'danger' : 'info')}, {key:'fecha_recibo', label:'Fecha', fn: f => F(f.fecha_recibo || f.fecha_proceso || f.pago?.fecha_proceso || f.creado_en)} ] },
                { id: 'por-concepto-detalle', label: 'Recibos por Concepto (Detalle)', url: '/api/v1/reportes/recibos/por-concepto-detalle', fechaRequerida: true, sucursal: true, paginado: true,
                  columns: [ {key:'numero_recibo', label:'# Recibo', mono:true, fn: f => String(f.numero_recibo).padStart(6, '0')}, {key:'estudiante', label:'Estudiante', fn: f => `${f.estudiante?.nombre || ''} ${f.estudiante?.apellido || ''}`}, {key:'concepto_pago', label:'Concepto', fn: f => f.concepto_pago?.nombre || '-'}, {key:'metodo_pago', label:'Método', fn: f => f.metodo_pago?.nombre || '-'}, {key:'monto_total', label:'Monto', numeric:true, money:true}, {key:'estado', label:'Estado', fn: f => BADGE(f.estado, f.estado === 'emitido' ? 'success' : f.estado === 'anulado' ? 'danger' : 'info')}, {key:'fecha_recibo', label:'Fecha', fn: f => F(f.fecha_recibo || f.fecha_proceso || f.pago?.fecha_proceso || f.creado_en)} ] },
                { id: 'por-metodo-rec', label: 'Recibos por Forma de Pago', url: '/api/v1/reportes/recibos/por-metodo', fechaRequerida: true, sucursal: true,
                  columns: [ {key:'sucursal_codigo', label:'Sucursal', mono:true, fn: f => `${f.sucursal_codigo || ''} · ${f.sucursal_nombre || ''}`}, {key:'periodo_nombre', label:'Período'}, {key:'plan_nombre', label:'Plan'}, {key:'nivel_codigo', label:'Nivel', mono:true, fn: f => `${f.nivel_codigo || ''} · ${f.nivel_nombre || ''}`}, {key:'horario_nombre', label:'Horario', fn: f => `${f.horario_codigo || ''} · ${f.horario_nombre || ''}`}, {key:'docente_nombre', label:'Docente', fn: f => `${f.docente_codigo || ''} · ${f.docente_nombre || ''}`}, {key:'estudiante_codigo', label:'Código', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'correo', label:'Correo'}, {key:'telefono', label:'Teléfono'}, {key:'metodo_codigo', label:'Código', mono:true, fn: f => `${f.metodo_codigo || ''} · ${f.metodo_nombre || ''}`}, {key:'cantidad', label:'Recibos', numeric:true}, {key:'total_monto', label:'Total', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Recibos', valor: filas.reduce((a, f) => a + Number(f.cantidad || 0), 0) }, { label: 'Total', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
                { id: 'anulados', label: 'Recibos Anulados', url: '/api/v1/reportes/recibos/anulados', sucursal: true,
                  columns: [ {key:'numero_recibo', label:'# Recibo', mono:true, fn: f => String(f.numero_recibo).padStart(6, '0')}, {key:'sucursal_codigo', label:'Sucursal', mono:true, fn: f => `${f.sucursal_codigo || ''} · ${f.sucursal_nombre || ''}`}, {key:'periodo_nombre', label:'Período'}, {key:'plan_nombre', label:'Plan'}, {key:'nivel_codigo', label:'Nivel', mono:true, fn: f => `${f.nivel_codigo || ''} · ${f.nivel_nombre || ''}`}, {key:'horario_nombre', label:'Horario', fn: f => `${f.horario_codigo || ''} · ${f.horario_nombre || ''}`}, {key:'docente_nombre', label:'Docente', fn: f => `${f.docente_codigo || ''} · ${f.docente_nombre || ''}`}, {key:'estudiante_codigo', label:'Código', mono:true}, {key:'nombre_completo', label:'Estudiante', fn: f => `${f.nombre || ''} ${f.apellido || ''}`}, {key:'correo', label:'Correo'}, {key:'telefono', label:'Teléfono'}, {key:'monto_total', label:'Monto', numeric:true, money:true}, {key:'motivo_anulacion', label:'Motivo'}, {key:'fecha_anulacion', label:'Fecha Anulación', fn: f => F(f.fecha_anulacion)} ],
                  totalesFn: (filas) => [{ label: 'Recibos anulados', valor: filas.length }, { label: 'Monto anulado', valor: M(filas.reduce((a, f) => a + Number(f.monto || 0), 0)) }] },
            ],
            caja: [
                { id: 'por-cajero', label: 'Caja por Cajero', url: '/api/v1/reportes/caja/por-cajero', fechaRequerida: true, sucursal: true,
                  columns: [ {key:'sesion_codigo', label:'Sesión', mono:true}, {key:'cajero_nombre', label:'Cajero'}, {key:'sucursal_nombre', label:'Sucursal'}, {key:'metodo_nombre', label:'Forma Pago'}, {key:'sesiones', label:'Sesiones', numeric:true}, {key:'sesiones_cerradas', label:'Cerradas', numeric:true}, {key:'sesiones_abiertas', label:'Abiertas', numeric:true}, {key:'total_monto', label:'Total', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Sesiones totales', valor: filas.reduce((a, f) => a + Number(f.sesiones || 0), 0) }, { label: 'Total ingresos', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
                { id: 'resumen-diario', label: 'Resumen Diario de Ingresos', url: '/api/v1/reportes/caja/resumen-diario', fechaRequerida: true, sucursal: true,
                  columns: [ {key:'fecha', label:'Fecha', fn: f => F(f.fecha)}, {key:'sucursal_nombre', label:'Sucursal'}, {key:'metodo_nombre', label:'Forma Pago'}, {key:'cajero_nombre', label:'Cajero'}, {key:'cantidad_recibos', label:'Recibos', numeric:true}, {key:'total_monto', label:'Total del Día', numeric:true, money:true} ],
                  totalesFn: (filas) => [{ label: 'Recibos', valor: filas.reduce((a, f) => a + Number(f.cantidad_recibos || 0), 0) }, { label: 'Total ingresos', valor: M(filas.reduce((a, f) => a + Number(f.total_monto || 0), 0)) }] },
            ],
        },

        get reportesDeCategoria() { return this.catalogo[this.categoria] || []; },
        get reporteActual() { return this.reportesDeCategoria.find(r => r.id === this.reporteId) || null; },

        async init() {
            const h = { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } };
            const [s, p, o] = await Promise.allSettled([
                window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                window.axios.get('/api/v1/catalogos-academicos/periodos-academicos', h),
                window.axios.get('/api/v1/ofertas/academicas?per_page=200', h),
            ]);
            this.sucursales = s.status === 'fulfilled' ? (s.value.data.data?.data || s.value.data.data || []) : [];
            this.periodos = p.status === 'fulfilled' ? (p.value.data.data?.data || p.value.data.data || []) : [];
            this.ofertas = o.status === 'fulfilled' ? (o.value.data.data?.data || o.value.data.data || []) : [];
            // Seleccionar primer reporte disponible
            if (this.reportesDeCategoria.length > 0) this.reporteId = this.reportesDeCategoria[0].id;
            // Fechas por defecto: período abierto actual; si no existe, usar mes actual
            const periodoAbierto = this.periodos.find(p => p.estado === 'activo');
            if (periodoAbierto?.fecha_inicio && periodoAbierto?.fecha_fin) {
                this.filtros.fecha_desde = String(periodoAbierto.fecha_inicio).substring(0, 10);
                this.filtros.fecha_hasta = String(periodoAbierto.fecha_fin).substring(0, 10);
                if (!this.filtros.periodo_academico_id) this.filtros.periodo_academico_id = periodoAbierto.id;
            } else {
                const hoy = new Date();
                const fechaLocal = window.toLocalDateInput(hoy);
                this.filtros.fecha_desde = fechaLocal.substring(0, 8) + '01';
                this.filtros.fecha_hasta = fechaLocal;
            }
            if (this.reporteActual && !this.reporteActual.oferta && !this.reporteActual.estudiante) {
                await this.consultar();
            }
        },

        cambiarCategoria(cat) {
            this.categoria = cat;
            this.reporteId = this.reportesDeCategoria[0]?.id || '';
            this.cambiarReporte();
        },

        cambiarReporte() {
            this.filas = []; this.totales = []; this.consultado = false; this.filtrosAplicados = [];
            this.paginacion = { current_page: 1, last_page: 1, total: 0 };
            this.filtros.estado = ''; this.filtros.oferta_academica_id = ''; this.filtros.estudiante_id = '';
            this.busquedaEstudiante = '';
        },

        async buscarEstudiantes() {
            if (this.busquedaEstudiante.length < 2) { this.resultadosEstudiantes = []; return; }
            try {
                const { data } = await window.axios.get(`/api/v1/estudiantes?buscar=${this.busquedaEstudiante}`, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                this.resultadosEstudiantes = data.data?.data || data.data || [];
            } catch(e) {}
        },

        async consultar(page = 1) {
            const r = this.reporteActual;
            if (!r) return;
            // Validaciones AGENTS §11
            if (r.fechaRequerida && (!this.filtros.fecha_desde || !this.filtros.fecha_hasta)) {
                this.toast('Los campos Desde y Hasta son obligatorios para este reporte', 'warning');
                return;
            }
            if (r.oferta && !this.filtros.oferta_academica_id) {
                this.toast('Seleccione un horario (oferta académica)', 'warning');
                return;
            }
            if (r.estudiante && !this.filtros.estudiante_id) {
                this.toast('Seleccione un estudiante', 'warning');
                return;
            }

            this.loading = true;
            try {
                let url = r.url + '?';
                if (this.filtros.fecha_desde && r.fechaDesde !== false) url += `fecha_desde=${this.filtros.fecha_desde}&`;
                if (this.filtros.fecha_hasta && r.fechaHasta !== false) url += `fecha_hasta=${this.filtros.fecha_hasta}&`;
                if (this.filtros.sucursal_id && r.sucursal) url += `sucursal_id=${this.filtros.sucursal_id}&`;
                if (this.filtros.periodo_academico_id && r.periodo) url += `periodo_academico_id=${this.filtros.periodo_academico_id}&`;
                if (this.filtros.estado && (r.estadoMatricula || r.estadoRecibo)) url += `estado=${this.filtros.estado}&`;
                if (this.filtros.oferta_academica_id && r.oferta) url += `oferta_academica_id=${this.filtros.oferta_academica_id}&`;
                if (this.filtros.estudiante_id && r.estudiante) url += `estudiante_id=${this.filtros.estudiante_id}&`;
                if (r.paginado) url += `per_page=50&page=${page}&`;

                const { data } = await window.axios.get(url, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                let filas;
                if (r.paginado) {
                    filas = data.data?.data || [];
                    this.paginacion = { current_page: data.data?.current_page || 1, last_page: data.data?.last_page || 1, total: data.data?.total || 0 };
                } else if (r.single) {
                    filas = data.data ? [data.data] : [];
                    this.paginacion = { current_page: 1, last_page: 1, total: filas.length };
                } else {
                    filas = data.data?.data || data.data || [];
                    this.paginacion = { current_page: 1, last_page: 1, total: filas.length };
                }
                this.filas = filas;
                this.totales = r.totalesFn ? r.totalesFn(filas) : [];
                this.filtrosAplicados = this.resumenFiltros();
                this.consultado = true;
            } catch(e) {
                this.toast(window.extractError(e, 'Error al consultar el reporte'), 'error');
            } finally { this.loading = false; }
        },

        async exportar(formato) {
            const r = this.reporteActual;
            if (!r) return;
            let url = `/api/v1/reportes/exportar?reporte=${this.categoria}.${r.id}&formato=${formato}`;
            const params = new URLSearchParams();
            if (this.filtros.fecha_desde && r.fechaDesde !== false) params.append('fecha_desde', this.filtros.fecha_desde);
            if (this.filtros.fecha_hasta && r.fechaHasta !== false) params.append('fecha_hasta', this.filtros.fecha_hasta);
            if (this.filtros.sucursal_id && r.sucursal) params.append('sucursal_id', this.filtros.sucursal_id);
            if (this.filtros.periodo_academico_id && r.periodo) params.append('periodo_academico_id', this.filtros.periodo_academico_id);
            if (this.filtros.estado && (r.estadoMatricula || r.estadoRecibo)) params.append('estado', this.filtros.estado);
            if (this.filtros.oferta_academica_id && r.oferta) params.append('oferta_academica_id', this.filtros.oferta_academica_id);
            if (this.filtros.estudiante_id && r.estudiante) params.append('estudiante_id', this.filtros.estudiante_id);
            if (this.filtros.metodo_pago_id) params.append('metodo_pago_id', this.filtros.metodo_pago_id);
            const qs = params.toString();
            if (qs) url += '&' + qs;

            this.exportando = true;
            try {
                const res = await window.axios.get(url, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
                    responseType: 'blob',
                });
                const disposition = res.headers['content-disposition'] || '';
                const match = disposition.match(/filename="?([^"]+)"?/);
                const filename = match ? match[1] : `reporte.${formato === 'excel' ? 'xlsx' : 'pdf'}`;
                const blobUrl = URL.createObjectURL(res.data);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
                this.toast('Descarga iniciada', 'success');
            } catch(e) {
                this.toast('Error al exportar', 'error');
            } finally { this.exportando = false; }
        },

        cambiarPagina(page) {
            if (page < 1 || page > this.paginacion.last_page) return;
            this.consultar(page);
        },

        resumenFiltros() {
            const chips = [];
            const r = this.reporteActual;
            if (this.filtros.fecha_desde && r?.fechaDesde !== false) chips.push(`Desde: ${this.filtros.fecha_desde}`);
            if (this.filtros.fecha_hasta && r?.fechaHasta !== false) chips.push(`Hasta: ${this.filtros.fecha_hasta}`);
            if (this.filtros.sucursal_id) {
                const s = this.sucursales.find(x => x.id == this.filtros.sucursal_id);
                if (s) chips.push(`Sucursal: ${s.nombre}`);
            }
            if (this.filtros.periodo_academico_id) {
                const p = this.periodos.find(x => x.id == this.filtros.periodo_academico_id);
                if (p) chips.push(`Período: ${p.nombre}`);
            }
            if (this.filtros.estado) chips.push(`Estado: ${this.filtros.estado}`);
            return chips;
        },

        celda(fila, col) {
            let v;
            if (col.fn) v = col.fn(fila);
            else {
                v = col.key.split('.').reduce((o, k) => (o && o[k] !== undefined) ? o[k] : null, fila);
                if (col.money) v = M(v);
                if (v === null || v === undefined || v === '') v = '-';
            }
            return v;
        },

        toast(message, type) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type } })); }
    }
}
</script>
@endsection
