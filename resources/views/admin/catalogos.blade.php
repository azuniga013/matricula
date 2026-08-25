@extends('layouts.admin')
@section('content')
<div x-data="catalogos()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Catálogos Académicos</h1>
            <p class="page-subtitle">Gestión de catálogos del sistema</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1 overflow-x-auto" aria-label="Tabs">
            <template x-for="tab in tabs" :key="tab.id">
                <button @click="changeTab(tab.id)"
                    :class="activeTab === tab.id ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium transition-colors">
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </nav>
    </div>

    {{-- Loading --}}
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div>
        </div>
    </template>

    {{-- Content --}}
    <template x-if="!loading">
        <div>
            {{-- Sucursales --}}
            <div x-show="activeTab === 'sucursales'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.sucursales.crear')" @click="openModal('sucursal')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nueva Sucursal
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td class="text-gray-500" x-text="item.direccion || '-'"></td>
                                        <td class="text-gray-500" x-text="item.telefono || '-'"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Departamentos Académicos --}}
            <div x-show="activeTab === 'departamentos'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.departamentos.crear')" @click="openModal('departamento')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Departamento
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Niveles Académicos --}}
            <div x-show="activeTab === 'niveles'" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-600">Versión del Plan:</label>
                        <select x-model="planFiltroId" class="input w-80 text-sm">
                            <option value="">Todas las versiones</option>
                            <template x-for="v in dynamicOptions['versiones-plan-estudio'] || []" :key="v.value">
                                <option :value="v.value" x-text="v.label"></option>
                            </template>
                        </select>
                    </div>
                    <button x-show="api.hasPermission('catalogos.niveles.crear')" @click="openModal('nivel')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Nivel
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Versión</th><th>Régimen</th><th>Orden</th><th>Nota Mín.</th><th>Faltas Máx.</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in filteredNiveles" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td class="text-xs text-gray-500" x-text="item.version_plan_estudio?.plan_estudio?.nombre ? (item.version_plan_estudio.plan_estudio.nombre + ' · V' + item.version_plan_estudio.numero_version) : '-'"></td>
                                        <td><span class="badge badge-brand" x-text="item.regimen_academico?.nombre || '-'"></span></td>
                                        <td x-text="item.orden"></td>
                                        <td x-text="item.nota_minima_aprobar + '%'"></td>
                                        <td x-text="item.faltas_maximas_permitidas"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button>
                                                <button x-show="canDelete()" @click="deleteItem(item)" class="btn btn-ghost btn-sm text-red-500">Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Modalidades --}}
            <div x-show="activeTab === 'modalidades'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.modalidades.crear')" @click="openModal('modalidad')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nueva Modalidad
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td><span class="badge badge-info" x-text="item.tipo"></span></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Horarios --}}
            <div x-show="activeTab === 'horarios'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.horarios.crear')" @click="openModal('horario')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Horario
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Días</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td x-text="item.hora_inicio"></td>
                                        <td x-text="item.hora_fin"></td>
                                        <td class="text-xs text-gray-500">
                                            <template x-for="day in ['lunes','martes','miercoles','jueves','viernes','sabado','domingo']" :key="day">
                                                <template x-if="item[day]">
                                                    <span class="inline-block px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-medium mr-1 mb-0.5" x-text="day.substring(0,3)"></span>
                                                </template>
                                            </template>
                                            <span x-show="!['lunes','martes','miercoles','jueves','viernes','sabado','domingo'].some(d => item[d])" class="text-gray-400">—</span>
                                        </td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Docentes --}}
            <div x-show="activeTab === 'docentes'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.docentes.crear')" @click="openModal('docente')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Docente
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Apellido</th><th>Correo</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td x-text="item.apellido"></td>
                                        <td class="text-gray-500" x-text="item.correo || '-'"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Aulas --}}
            <div x-show="activeTab === 'aulas'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.aulas.crear')" @click="openModal('aula')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nueva Aula
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Sucursal</th><th>Capacidad</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td x-text="item.sucursal?.nombre || '-'"></td>
                                        <td x-text="item.capacidad"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Planes de Estudio --}}
            <div x-show="activeTab === 'planes'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.planes.crear')" @click="openModal('plan')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Plan
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                  <thead><tr><th>Código</th><th>Nombre</th><th>Departamento</th><th>Descripción</th><th>Vers.</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                  <tbody>
                      <template x-for="item in items" :key="item.id">
                          <tr>
                              <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                              <td class="font-medium" x-text="item.nombre"></td>
                              <td class="text-gray-500" x-text="item.departamento_academico?.nombre || '-'"></td>
                              <td class="text-gray-500 text-sm max-w-[200px] truncate" x-text="item.descripcion || '-'"></td>
                              <td><span class="font-mono text-xs font-semibold" x-text="item.versiones_count || 0"></span></td>
                              <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                              <td class="text-right">
                                  <div class="flex items-center justify-end gap-1">
                                      <button @click="openVersiones(item)" class="btn btn-ghost btn-sm">Versiones</button>
                                      <button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button>
                                  </div>
                              </td>
                          </tr>
                      </template>
                  </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Periodos --}}
            <div x-show="activeTab === 'periodos'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('ofertas.periodos.crear')" @click="openModal('periodo')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Período
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td x-text="formatearFecha(item.fecha_inicio)"></td>
                                        <td x-text="formatearFecha(item.fecha_fin)"></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : item.estado === 'cerrado' ? 'badge-neutral' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Conceptos de Pago --}}
            <div x-show="activeTab === 'conceptos'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.conceptos.crear')" @click="openModal('concepto')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Concepto
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Tipo de Monto</th><th>Monto Fijo</th><th>Portal</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td><span class="badge badge-info" x-text="{fijo:'Fijo',manual:'Manual',por_oferta:'Por Oferta',por_inventario:'Por Inventario'}[item.tipo_monto] || item.tipo_monto"></span></td>
                                        <td x-text="item.monto_fijo ? 'L ' + Number(item.monto_fijo).toFixed(2) : '-'"></td>
                                        <td><span :class="item.portal_disponible ? 'badge-success' : 'badge-warning'" class="badge" x-text="item.portal_disponible ? 'Habilitado' : 'Oculto'"></span></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Métodos de Pago --}}
            <div x-show="activeTab === 'metodos'" class="space-y-4">
                <div class="flex justify-end">
                    <button x-show="api.hasPermission('catalogos.metodos.crear')" @click="openModal('metodo')" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Método
                    </button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Link Pago</th><th>Portal</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="item in items" :key="item.id">
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-brand-600" x-text="item.codigo"></td>
                                        <td class="font-medium" x-text="item.nombre"></td>
                                        <td class="text-gray-500" x-text="item.descripcion || '-'"></td>
                                        <td><span :class="item.permite_link_pago ? 'badge-success' : 'badge-warning'" class="badge" x-text="item.permite_link_pago ? 'Sí' : 'No'"></span></td>
                                        <td><span :class="item.portal_disponible ? 'badge-success' : 'badge-warning'" class="badge" x-text="item.portal_disponible ? 'Habilitado' : 'Oculto'"></span></td>
                                        <td><span :class="item.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="item.estado"></span></td>
                                        <td class="text-right"><button x-show="canEdit()" @click="editItem(item)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </template>

    {{-- Modal --}}
    <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editing ? 'Editar' : 'Nuevo'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form @submit.prevent="saveItem()" class="p-6 space-y-4">
                {{-- Dynamic fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="field in currentFields" :key="field.key">
                    <div :class="field.key === 'descripcion' ? 'md:col-span-2' : ''">
                        <label class="label" x-text="field.label"></label>
                        <template x-if="field.type === 'text'">
                            <input x-model="formData[field.key]" type="text" :required="field.required" class="input">
                        </template>
                        <template x-if="field.type === 'number'">
                            <input x-model.number="formData[field.key]" type="number" :required="field.required" class="input">
                        </template>
                        <template x-if="field.type === 'date'">
                            <input x-model="formData[field.key]" type="date" :required="field.required" class="input">
                        </template>
                        <template x-if="field.type === 'time'">
                            <input x-model="formData[field.key]" type="time" :required="field.required" class="input">
                        </template>
                        <template x-if="field.type === 'textarea'">
                            <textarea x-model="formData[field.key]" :required="field.required" class="input" rows="3"></textarea>
                        </template>
                        <template x-if="field.type === 'select'">
                            <select x-model="formData[field.key]" :required="field.required" class="input" @change="onFieldChange(field.key)">
                                <option value="">Seleccionar...</option>
                                <template x-for="opt in fieldOptions(field)" :key="opt.value">
                                    <option :value="opt.value" x-text="opt.label"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="field.key === 'permite_link_pago'">
                            <p class="mt-1 text-xs text-gray-500">Habilita este método para permitir solicitudes de link de pago.</p>
                        </template>
                        <template x-if="field.type === 'checkbox'">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input x-model="formData[field.key]" type="checkbox" class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                <span class="text-sm text-gray-700" x-text="field.label"></span>
                            </label>
                        </template>
                        <template x-if="field.type === 'checkboxes'">
                            <div class="flex flex-wrap gap-3 mt-1">
                                <template x-for="opt in fieldOptions(field)" :key="opt.value">
                                    <label class="flex items-center gap-1.5 cursor-pointer text-sm">
                                        <input type="checkbox" :value="opt.value"
                                            :checked="(formData[field.key] || []).includes(opt.value)"
                                            @change="toggleCheckbox(field.key, opt.value)"
                                            class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                        <span x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                </div>
                <div x-show="editing && auditoriaEntidad.length > 0 && ['sucursales'].includes(activeTab)" class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-gray-800">Auditoría</h4>
                    <template x-for="item in auditoriaEntidad" :key="item.id">
                        <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs space-y-1">
                            <p class="font-medium text-gray-700" x-text="(item.usuario?.name || 'Sistema') + ' · ' + item.accion"></p>
                            <p class="text-gray-500" x-text="formatearFecha(item.creado_en)"></p>
                            <p class="text-gray-600" x-text="item.descripcion || '-' "></p>
                        </div>
                    </template>
                </div>

                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary">
                        <template x-if="saving"><div class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white"></div></template>
                        <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Versiones (lista + formulario inline) --}}
    <div x-show="showModalVersiones" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModalVersiones = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">
                    <template x-if="!mostrandoFormVersion">
                        Versiones · <span class="text-brand-600" x-text="planSeleccionado?.codigo + ' — ' + planSeleccionado?.nombre"></span>
                    </template>
                    <template x-if="mostrandoFormVersion">
                        <span x-text="editandoVersion ? 'Editar Versión' : 'Nueva Versión'"></span>
                    </template>
                </h3>
                <button @click="cerrarVersiones()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Vista lista -->
            <div x-show="!mostrandoFormVersion" class="p-6">
                <div class="flex justify-end mb-4">
                    <button @click="openVersionForm()" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nueva Versión
                    </button>
                </div>
                <template x-if="versionesLoading">
                    <div class="flex items-center justify-center py-10"><div class="animate-spin rounded-full h-6 w-6 border-2 border-brand-500/20 border-t-brand-500"></div></div>
                </template>
                <template x-if="!versionesLoading">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>N.º</th><th>Vigente Desde</th><th>Vigente Hasta</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="v in versiones" :key="v.id">
                                    <tr>
                                        <td class="font-mono font-semibold" x-text="'v' + v.numero_version"></td>
                                        <td x-text="formatearFecha(v.vigente_desde)"></td>
                                        <td x-text="v.vigente_hasta ? formatearFecha(v.vigente_hasta) : '-'"></td>
                                        <td><span :class="v.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="v.estado"></span></td>
                                        <td class="text-right"><button @click="editVersion(v)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                                <template x-if="versiones.length === 0">
                                    <tr><td colspan="5" class="text-center py-8 text-gray-400 text-sm">Sin versiones registradas</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <!-- Vista formulario -->
            <form x-show="mostrandoFormVersion" @submit.prevent="saveVersion()" class="p-6 space-y-4">
                <template x-if="!editandoVersion">
                    <div>
                        <label class="label">Número de Versión</label>
                        <input x-model.number="versionForm.numero_version" type="number" min="1" required class="input">
                    </div>
                </template>
                <div>
                    <label class="label">Vigente Desde</label>
                    <input x-model="versionForm.vigente_desde" type="date" required class="input">
                </div>
                <div>
                    <label class="label">Vigente Hasta</label>
                    <input x-model="versionForm.vigente_hasta" type="date" class="input">
                </div>
                <template x-if="editandoVersion">
                    <div>
                        <label class="label">Estado</label>
                        <select x-model="versionForm.estado" class="input">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </template>
                <div x-show="errorVersion" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-red-600" x-text="errorVersion"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="mostrandoFormVersion = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingVersion" class="btn btn-primary">
                        <span x-text="savingVersion ? 'Guardando...' : 'Guardar'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function catalogos() {
    return {
        activeTab: 'sucursales',
        loading: true,
        showModal: false,
        editing: false,
        saving: false,
        error: '',
        items: [],
        planFiltroId: '',
        formData: {}, auditoriaEntidad: [],
        editId: null,

        permMap: {
            sucursales: 'catalogos.sucursales',
            departamentos: 'catalogos.departamentos',
            planes: 'catalogos.planes',
            niveles: 'catalogos.niveles',
            modalidades: 'catalogos.modalidades',
            horarios: 'catalogos.horarios',
            docentes: 'catalogos.docentes',
            aulas: 'catalogos.aulas',
            periodos: 'ofertas.periodos',
            conceptos: 'catalogos.conceptos',
            metodos: 'catalogos.metodos',
        },

        canCreate() {
            const base = this.permMap[this.activeTab];
            return base ? api.hasPermission(base + '.crear') : false;
        },

        canEdit() {
            const base = this.permMap[this.activeTab];
            return base ? api.hasPermission(base + '.modificar') : false;
        },

        canDelete() {
            const base = this.permMap[this.activeTab];
            return base ? api.hasPermission(base + '.eliminar') : false;
        },

        get filteredNiveles() {
            if (!this.planFiltroId) return this.items;
            return this.items.filter(item =>
                String(item.version_plan_estudio_id) === String(this.planFiltroId)
            );
        },

        tabs: [
            { id: 'sucursales', label: 'Sucursales', endpoint: 'sucursales' },
            { id: 'departamentos', label: 'Departamentos', endpoint: 'departamentos-academicos' },
            { id: 'planes', label: 'Planes de Estudio', endpoint: 'planes-estudio' },
            { id: 'niveles', label: 'Niveles', endpoint: 'niveles-academicos' },
            { id: 'modalidades', label: 'Modalidades', endpoint: 'modalidades' },
            { id: 'horarios', label: 'Horarios', endpoint: 'horarios' },
            { id: 'docentes', label: 'Docentes', endpoint: 'docentes' },
            { id: 'aulas', label: 'Aulas', endpoint: 'aulas' },
            { id: 'periodos', label: 'Períodos', endpoint: 'periodos-academicos' },
            { id: 'conceptos', label: 'Conceptos de Pago', endpoint: 'conceptos-pago' },
            { id: 'metodos', label: 'Métodos de Pago', endpoint: 'metodos-pago' },
        ],

        dynamicOptions: {},
        loadedTabs: {},
        loadedOptions: {},

        get currentTab() {
            return this.tabs.find(t => t.id === this.activeTab);
        },

        get currentFields() {
            const fields = {
                sucursales: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'direccion', label: 'Dirección', type: 'text', required: false },
                    { key: 'telefono', label: 'Teléfono', type: 'text', required: false },
                    { key: 'correo', label: 'Correo', type: 'text', required: false },
                    { key: 'modalidades_atencion', label: 'Modalidades de Atención', type: 'checkboxes', required: false, optionsEndpoint: 'modalidades?tipo=atencion' },
                ],
                departamentos: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'estado', label: 'Estado', type: 'select', required: false, options: [
                        { value: 'activo', label: 'Activo' },
                        { value: 'inactivo', label: 'Inactivo' },
                    ]},
                ],
                planes: [
                    { key: 'departamento_academico_id', label: 'Departamento Académico', type: 'select', required: true, optionsEndpoint: 'departamentos-academicos', optionLabel: 'nombre', optionValue: 'id' },
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'descripcion', label: 'Descripción', type: 'textarea', required: false },
                    { key: 'estado', label: 'Estado', type: 'select', required: false, options: [
                        { value: 'activo', label: 'Activo' },
                        { value: 'inactivo', label: 'Inactivo' },
                    ]},
                ],
                niveles: [
                    { key: 'version_plan_estudio_id', label: 'Versión del Plan de Estudio', type: 'select', required: true, optionsEndpoint: 'versiones-plan-estudio', optionLabel: 'nombre', optionValue: 'id' },
                    { key: 'regimen_academico_id', label: 'Régimen Académico', type: 'select', required: true, optionsEndpoint: 'modalidades?tipo=regimen_academico', optionLabel: 'nombre', optionValue: 'id' },
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'orden', label: 'Orden', type: 'number', required: true },
                    { key: 'nota_minima_aprobar', label: 'Nota Mínima (%)', type: 'number', required: true },
                    { key: 'faltas_maximas_permitidas', label: 'Faltas Máximas', type: 'number', required: true },
                    { key: 'prerrequisitos', label: 'Prerrequisitos', type: 'checkboxes', required: false, optionsEndpoint: 'niveles-academicos' },
                ],
                modalidades: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'tipo', label: 'Tipo', type: 'select', required: true, options: [
                        { value: 'regimen_academico', label: 'Régimen Académico' },
                        { value: 'atencion', label: 'Modalidad de Atención' },
                    ]},
                ],
                horarios: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'hora_inicio', label: 'Hora Inicio', type: 'time', required: true },
                    { key: 'hora_fin', label: 'Hora Fin', type: 'time', required: true },
                    { key: 'es_24_horas', label: '24 Horas', type: 'checkbox', required: false },
                    { key: 'lunes', label: 'Lunes', type: 'checkbox', required: false },
                    { key: 'martes', label: 'Martes', type: 'checkbox', required: false },
                    { key: 'miercoles', label: 'Miércoles', type: 'checkbox', required: false },
                    { key: 'jueves', label: 'Jueves', type: 'checkbox', required: false },
                    { key: 'viernes', label: 'Viernes', type: 'checkbox', required: false },
                    { key: 'sabado', label: 'Sábado', type: 'checkbox', required: false },
                    { key: 'domingo', label: 'Domingo', type: 'checkbox', required: false },
                    { key: 'descripcion', label: 'Descripción', type: 'textarea', required: false },
                ],
                docentes: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'apellido', label: 'Apellido', type: 'text', required: true },
                    { key: 'correo', label: 'Correo', type: 'text', required: false },
                    { key: 'telefono', label: 'Teléfono', type: 'text', required: false },
                ],
                aulas: [
                    { key: 'sucursal_id', label: 'Sucursal', type: 'select', required: true, optionsEndpoint: 'sucursales', optionLabel: 'nombre', optionValue: 'id' },
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'capacidad', label: 'Capacidad', type: 'number', required: true },
                ],
                periodos: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'fecha_inicio', label: 'Fecha Inicio', type: 'date', required: true },
                    { key: 'fecha_fin', label: 'Fecha Fin', type: 'date', required: true },
                    { key: 'estado', label: 'Estado', type: 'select', required: true, options: [
                        { value: 'activo', label: 'Activo' },
                        { value: 'cerrado', label: 'Cerrado' },
                        { value: 'inactivo', label: 'Inactivo' },
                    ]},
                ],
                conceptos: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'tipo_monto', label: 'Tipo de Monto', type: 'select', required: true, options: [
                        { value: 'fijo', label: 'Fijo' },
                        { value: 'manual', label: 'Manual' },
                        { value: 'por_oferta', label: 'Por Oferta Académica' },
                        { value: 'por_inventario', label: 'Por Inventario' },
                    ]},
                    { key: 'monto_fijo', label: 'Monto Fijo (L)', type: 'number', required: false },
                    { key: 'descripcion', label: 'Descripción', type: 'text', required: false },
                    { key: 'portal_disponible', label: 'Mostrar en Portal', type: 'select', required: false, options: [
                        { value: true, label: 'Habilitado' },
                        { value: false, label: 'Oculto' },
                    ]},
                ],
                metodos: [
                    { key: 'codigo', label: 'Código', type: 'text', required: true },
                    { key: 'nombre', label: 'Nombre', type: 'text', required: true },
                    { key: 'descripcion', label: 'Descripción', type: 'text', required: false },
                    { key: 'permite_link_pago', label: 'Permite Link de Pago', type: 'select', required: false, options: [
                        { value: true, label: 'Sí' },
                        { value: false, label: 'No' },
                    ]},
                    { key: 'portal_disponible', label: 'Mostrar en Portal', type: 'select', required: false, options: [
                        { value: true, label: 'Habilitado' },
                        { value: false, label: 'Oculto' },
                    ]},
                    { key: 'estado', label: 'Estado', type: 'select', required: false, options: [
                        { value: 'activo', label: 'Activo' },
                        { value: 'inactivo', label: 'Inactivo' },
                    ]},
                ],
            };
            return fields[this.activeTab] || [];
        },

        fieldOptions(field) {
            let options = field.optionsEndpoint ? (this.dynamicOptions[field.optionsEndpoint] || []) : (field.options || []);

            if (field.key === 'prerrequisitos' && this.formData.version_plan_estudio_id) {
                options = options.filter(opt => {
                    const item = this.dynamicOptions['niveles-academicos-data']?.find(n => String(n.id) === String(opt.value));
                    return item && String(item.version_plan_estudio_id) === String(this.formData.version_plan_estudio_id);
                });
            }

            return options;
        },

        optionEndpointsForTab(tabId) {
            const map = {
                planes: ['departamentos-academicos'],
                niveles: ['versiones-plan-estudio', 'modalidades?tipo=regimen_academico', 'niveles-academicos'],
                aulas: ['sucursales'],
                sucursales: ['modalidades?tipo=atencion'],
                conceptos: [],
                metodos: [],
            };
            return map[tabId] || [];
        },

        async loadDynamicOptionsForTab(tabId) {
            const referenciados = this.optionEndpointsForTab(tabId);
            const pendientes = referenciados.filter(ep => !this.loadedOptions[ep]);
            if (!pendientes.length) return;

            const token = localStorage.getItem('auth_token');
            await Promise.all(pendientes.map(async ep => {
                try {
                    const { data } = await window.axios.get(`/api/v1/catalogos-academicos/${ep}`, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    if (data.resultado === 'A') {
                        const items = (data.data.data || data.data || []);
                        this.dynamicOptions[ep] = items.map(item => {
                            let label;
                            if (item.codigo && item.nombre) {
                                label = `${item.codigo} · ${item.nombre}`;
                            } else if (item.numero_version) {
                                const plan = item.plan_estudio ? (item.plan_estudio.nombre || '') : '';
                                label = `${plan} · V${item.numero_version}`.trim();
                            } else {
                                label = item.nombre || `#${item.id}`;
                            }
                            return { value: item.id, label };
                        });
                        if (ep === 'niveles-academicos') {
                            this.dynamicOptions['niveles-academicos-data'] = items;
                        }
                        this.loadedOptions[ep] = true;
                    }
                } catch (e) { /* catálogo no disponible */ }
            }));
        },

        async changeTab(tabId) {
            this.activeTab = tabId;
            await this.loadDynamicOptionsForTab(tabId);
            await this.loadTab(true);
        },

        async init() {
            await this.loadDynamicOptionsForTab(this.activeTab);
            await this.loadTab(true);
        },

        async loadTab(force = false) {
            if (!force && this.loadedTabs[this.activeTab]) {
                return;
            }
            this.loading = true;
            this.items = [];
            try {
                const token = localStorage.getItem('auth_token');
                let url = `/api/v1/catalogos-academicos/${this.currentTab.endpoint}`;
                const params = new URLSearchParams();
                if (params.toString()) url += `?${params.toString()}`;
                const { data } = await window.axios.get(url, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.items = data.data.data || data.data || [];
                    this.loadedTabs[this.activeTab] = true;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        formatearFecha(fecha) {
            if (!fecha) return '—';

            const [anio, mes, dia] = String(fecha).slice(0, 10).split('-');
            return anio && mes && dia ? `${dia}/${mes}/${anio}` : '—';
        },

        toggleCheckbox(key, value) {
            if (!Array.isArray(this.formData[key])) {
                this.formData[key] = [];
            }
            const idx = this.formData[key].indexOf(value);
            if (idx === -1) {
                this.formData[key].push(value);
            } else {
                this.formData[key].splice(idx, 1);
            }
        },

        onFieldChange(key) {
            if (key === 'version_plan_estudio_id') {
                this.formData.prerrequisitos = [];
            }
        },

        openModal(type) {
            this.editing = false;
            this.editId = null;
            this.error = '';
            this.auditoriaEntidad = [];
            this.formData = {};
            this.currentFields.forEach(f => {
                if (f.type === 'number') this.formData[f.key] = 0;
                else if (f.type === 'checkbox') this.formData[f.key] = false;
                else if (f.type === 'checkboxes') this.formData[f.key] = [];
                else this.formData[f.key] = '';
            });
            if (type === 'sucursal') {
                this.loadDynamicOptionsForTab('sucursales');
            }
            this.showModal = true;
        },

        async editItem(item) {
            this.editing = true;
            this.editId = item.id;
            this.error = '';
            if (this.activeTab === 'sucursales') {
                this.loadDynamicOptionsForTab('sucursales');
            }
            let data = item;
            if (this.activeTab === 'sucursales') {
                try {
                    const token = localStorage.getItem('auth_token');
                    const res = await window.axios.get(`/api/v1/catalogos-academicos/sucursales/${item.id}`, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    if (res.data?.resultado === 'A' && res.data?.data) {
                        data = res.data.data;
                    }
                } catch (e) {
                    console.error(e);
                }
            }
            this.formData = {};
            this.currentFields.forEach(f => {
                if (f.type === 'checkbox') {
                    this.formData[f.key] = !!data[f.key];
                } else if (f.type === 'checkboxes') {
                    this.formData[f.key] = (data[f.key] || []).map(x => x.id ?? x.value ?? x);
                } else if (f.type === 'date' && data[f.key]) {
                    this.formData[f.key] = String(data[f.key]).slice(0, 10);
                } else {
                    this.formData[f.key] = data[f.key] ?? '';
                }
            });
            this.showModal = true;
            if (['sucursales'].includes(this.activeTab)) {
                try {
                    const token = localStorage.getItem('auth_token');
                    const entidadTipo = this.activeTab === 'sucursales' ? 'sucursales' : null;
                    if (entidadTipo) {
                        const { data } = await window.axios.get(`/api/v1/seguridad/auditoria/entidad?entidad_tipo=${entidadTipo}&entidad_id=${item.id}`, { headers: { Authorization: `Bearer ${token}` } });
                        this.auditoriaEntidad = data.data || [];
                    }
                } catch (e) { this.auditoriaEntidad = []; }
            }
        },

        async saveItem() {
            this.saving = true;
            this.error = '';
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editing
                    ? `/api/v1/catalogos-academicos/${this.currentTab.endpoint}/${this.editId}`
                    : `/api/v1/catalogos-academicos/${this.currentTab.endpoint}`;
                const { data } = await window.api.actualizar(url, this.formData, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.showModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Guardado correctamente', type: 'success' } }));
                    await this.loadTab(true);
                } else {
                    this.error = data.mensaje || 'Error al guardar';
                }
            } catch (e) {
                this.error = window.extractError(e, 'Error al guardar');
            } finally {
                this.saving = false;
            }
        },

        async deleteItem(item) {
            const msg = '¿Está seguro de eliminar este registro?';
            if (!confirm(msg)) return;
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.post(
                    `/api/v1/catalogos-academicos/${this.currentTab.endpoint}/${item.id}`,
                    { headers: { Authorization: `Bearer ${token}` } }
                );
                if (data.resultado === 'A') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Eliminado correctamente', type: 'success' } }));
                    await this.loadTab(true);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje || 'Error al eliminar', type: 'error' } }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'Error al eliminar'), type: 'error' } }));
            }
        },

        // === Versiones ===
        showModalVersiones: false,
        versiones: [],
        versionesLoading: false,
        planSeleccionado: null,
        mostrandoFormVersion: false,
        editandoVersion: false,
        editVersionId: null,
        versionForm: { plan_estudio_id: '', numero_version: '', vigente_desde: '', vigente_hasta: '', estado: 'activo' },
        savingVersion: false,
        errorVersion: '',

        async openVersiones(plan) {
            this.planSeleccionado = plan;
            this.errorVersion = '';
            this.showModalVersiones = true;
            await this.loadVersiones();
        },

        async loadVersiones() {
            if (!this.planSeleccionado) return;
            this.versionesLoading = true;
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.get(`/api/v1/catalogos-academicos/versiones-plan-estudio?plan_estudio_id=${this.planSeleccionado.id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.versiones = data.data.data || data.data || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.versionesLoading = false;
            }
        },

        cerrarVersiones() {
            this.mostrandoFormVersion = false;
            this.showModalVersiones = false;
        },

        openVersionForm() {
            this.editandoVersion = false;
            this.editVersionId = null;
            this.errorVersion = '';
            this.versionForm.plan_estudio_id = this.planSeleccionado.id;
            this.versionForm.numero_version = '';
            this.versionForm.vigente_desde = '';
            this.versionForm.vigente_hasta = '';
            this.versionForm.estado = 'activo';
            this.mostrandoFormVersion = true;
        },

        editVersion(v) {
            this.editandoVersion = true;
            this.editVersionId = v.id;
            this.errorVersion = '';
            this.versionForm.plan_estudio_id = this.planSeleccionado.id;
            this.versionForm.numero_version = v.numero_version;
            this.versionForm.vigente_desde = v.vigente_desde ? String(v.vigente_desde).slice(0, 10) : '';
            this.versionForm.vigente_hasta = v.vigente_hasta ? String(v.vigente_hasta).slice(0, 10) : '';
            this.versionForm.estado = v.estado;
            this.mostrandoFormVersion = true;
        },

        async saveVersion() {
            this.savingVersion = true;
            this.errorVersion = '';
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editandoVersion
                    ? `/api/v1/catalogos-academicos/versiones-plan-estudio/${this.editVersionId}`
                    : '/api/v1/catalogos-academicos/versiones-plan-estudio';
                const { data } = await window.api.actualizar(url, this.versionForm, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                if (data.resultado === 'A') {
                    this.mostrandoFormVersion = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Versión guardada correctamente', type: 'success' } }));
                    await this.loadVersiones();
                    await this.loadTab(true);
                } else {
                    this.errorVersion = data.mensaje || 'Error al guardar';
                }
            } catch (e) {
                this.errorVersion = window.extractError(e, 'Error al guardar');
            } finally {
                this.savingVersion = false;
            }
        }
    }
}
</script>
@endsection
