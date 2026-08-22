@extends('layouts.admin')
@section('content')
<div x-data="seguridad()" x-init="init()">
    <div class="page-header">
        <div>
            <h1 class="page-title">Seguridad</h1>
            <p class="page-subtitle">Gestión de usuarios, roles y permisos</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-1 overflow-x-auto">
            <button x-show="api.hasPermission('seguridad.usuarios.consultar')" @click="cambiarTab('usuarios')" :class="tab === 'usuarios' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Usuarios</button>
            <button x-show="api.hasPermission('seguridad.roles.consultar')" @click="cambiarTab('roles')" :class="tab === 'roles' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Roles</button>
            <button x-show="api.hasPermission('seguridad.modulos.consultar')" @click="cambiarTab('modulos')" :class="tab === 'modulos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Módulos</button>
            <button x-show="api.hasPermission('seguridad.opciones.consultar')" @click="cambiarTab('opciones')" :class="tab === 'opciones' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Opciones</button>
            <button x-show="api.hasPermission('seguridad.permisos.consultar')" @click="cambiarTab('permisos')" :class="tab === 'permisos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Permisos</button>
            <button x-show="api.hasPermission('seguridad.configurar')" @click="cambiarTab('flujos_matricula')" :class="tab === 'flujos_matricula' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Flujo Matrícula</button>
            <button x-show="api.hasPermission('seguridad.auditoria.consultar')" @click="cambiarTab('auditores')" :class="tab === 'auditores' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Auditoría</button>
            <button x-show="api.hasPermission('seguridad.auditoria.consultar')" @click="cambiarTab('correos')" :class="tab === 'correos' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium">Correos</button>
        </nav>
    </div>

    <template x-if="loading"><div class="flex items-center justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-2 border-brand-500/20 border-t-brand-500"></div></div></template>

    <template x-if="!loading">
        <div>
            {{-- Usuarios --}}
            <div x-show="tab === 'usuarios'" class="space-y-4">
                <div class="flex justify-end"><button x-show="api.hasPermission('seguridad.usuarios.crear')" @click="openUserModal()" class="btn btn-primary"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nuevo Usuario</button></div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Nombre</th><th>Email</th><th>Alcance</th><th>Estado</th><th>Último Acceso</th><th class="text-right">Acciones</th></tr></thead>
                            <tbody>
                                <template x-for="u in usuarios" :key="u.id">
                                    <tr>
                                        <td class="font-medium" x-text="u.name"></td>
                                        <td class="text-gray-500" x-text="u.email"></td>
                                        <td class="text-xs">
                                            <span class="badge badge-info" x-show="resumenAlcanceUsuario(u).tipo === 'global'">Global</span>
                                            <span class="badge badge-neutral" x-show="resumenAlcanceUsuario(u).tipo !== 'global'" x-text="resumenAlcanceUsuario(u).etiqueta"></span>
                                            <p class="mt-1 text-gray-500" x-show="resumenAlcanceUsuario(u).detalle" x-text="resumenAlcanceUsuario(u).detalle"></p>
                                        </td>
                                        <td><span :class="u.estado === 'activo' ? 'badge-success' : 'badge-danger'" class="badge" x-text="u.estado"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="formatearFecha(u.ultimo_acceso)"></td>
                                        <td class="text-right"><button x-show="api.hasPermission('seguridad.usuarios.modificar')" @click="editUser(u)" class="btn btn-ghost btn-sm">Editar</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Roles --}}
            <div x-show="tab === 'roles'" class="space-y-4">
                <div class="flex justify-end"><button x-show="api.hasPermission('seguridad.roles.crear')" @click="openRoleModal()" class="btn btn-primary"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nuevo Rol</button></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="r in roles" :key="r.id">
                        <div class="card hover:shadow-md transition-shadow">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-gray-900" x-text="r.nombre"></h4>
                                    <span class="badge badge-neutral" x-text="r.usuarios_count + ' usuarios'"></span>
                                </div>
                                <p class="text-xs text-gray-500 font-mono mb-3" x-text="r.codigo"></p>
                                <button x-show="api.hasPermission('seguridad.roles.modificar')" @click="editRolePermisos(r)" class="btn btn-outline btn-sm w-full">Administrar Permisos</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Módulos --}}
            <div x-show="tab === 'modulos'" class="card">
                <div class="card-header flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-900">Módulos del Sistema</h3>
                    <button x-show="api.hasPermission('seguridad.modulos.crear')" @click="openModuloModal()" class="btn btn-primary btn-sm">Nuevo Módulo</button>
                </div>
                <div class="px-6 py-3 border-b border-gray-100 text-xs text-gray-500 flex flex-wrap gap-3">
                    <span x-text="modulos.length + ' módulos'"></span>
                    <span x-text="modulosActivos.length + ' activos'"></span>
                    <span x-text="modulosInactivos.length + ' inactivos'"></span>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <input x-model="busquedaModulos" type="text" class="input text-sm flex-1 min-w-[220px]" placeholder="Buscar módulo por código o nombre">
                        <select x-model="estadoModulos" class="input text-sm w-full sm:w-44">
                            <option value="">Todos</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                        </select>
                        <button x-show="api.hasPermission('seguridad.modulos.modificar')" @click="activarFiltradosModulos()" class="btn btn-outline btn-sm">Activar filtrados</button>
                        <button x-show="api.hasPermission('seguridad.modulos.eliminar')" @click="desactivarFiltradosModulos()" class="btn btn-outline btn-sm text-red-600 border-red-200">Desactivar filtrados</button>
                    </div>
                    <template x-for="m in modulosFiltrados" :key="m.id">
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900" x-text="m.codigo + ' · ' + m.nombre"></p>
                                <p class="text-xs text-gray-500" x-text="m.opciones_count + ' opciones'"></p>
                                <span class="badge text-[10px] mt-2" :class="m.estado === 'activo' ? 'badge-success' : 'badge-danger'" x-text="m.estado"></span>
                            </div>
                            <div class="flex gap-2">
                                <button x-show="api.hasPermission('seguridad.modulos.modificar')" @click="editModulo(m)" class="btn btn-outline btn-sm">Editar</button>
                                <button x-show="api.hasPermission('seguridad.modulos.eliminar') && m.estado === 'activo'" @click="deleteModulo(m)" class="btn btn-outline btn-sm text-red-600 border-red-200">Bajar</button>
                                <button x-show="api.hasPermission('seguridad.modulos.modificar') && m.estado !== 'activo'" @click="reactivateModulo(m)" class="btn btn-outline btn-sm text-emerald-600 border-emerald-200">Reactivar</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Opciones --}}
            <div x-show="tab === 'opciones'" class="card">
                <div class="card-header flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-900">Opciones de Módulo</h3>
                    <button x-show="api.hasPermission('seguridad.opciones.crear')" @click="openOpcionModal()" class="btn btn-primary btn-sm">Nueva Opción</button>
                </div>
                <div class="px-6 py-3 border-b border-gray-100 text-xs text-gray-500 flex flex-wrap gap-3">
                    <span x-text="opcionesModulo.length + ' opciones'"></span>
                    <span x-text="opcionesActivas.length + ' activas'"></span>
                    <span x-text="opcionesInactivas.length + ' inactivas'"></span>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <input x-model="busquedaOpciones" type="text" class="input text-sm flex-1 min-w-[220px]" placeholder="Buscar opción por código, nombre o módulo">
                        <select x-model="estadoOpciones" class="input text-sm w-full sm:w-44">
                            <option value="">Todos</option>
                            <option value="activo">Activas</option>
                            <option value="inactivo">Inactivas</option>
                        </select>
                        <button x-show="api.hasPermission('seguridad.opciones.modificar')" @click="activarFiltradasOpciones()" class="btn btn-outline btn-sm">Activar filtradas</button>
                        <button x-show="api.hasPermission('seguridad.opciones.eliminar')" @click="desactivarFiltradasOpciones()" class="btn btn-outline btn-sm text-red-600 border-red-200">Desactivar filtradas</button>
                    </div>
                    <template x-for="o in opcionesFiltradas" :key="o.id">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900" x-text="o.codigo + ' · ' + o.nombre"></p>
                                <p class="text-xs text-gray-500" x-text="(o.modulo?.codigo || 'General') + (o.ruta ? ' · ' + o.ruta : '')"></p>
                                <span class="badge text-[10px] mt-2" :class="o.estado === 'activo' ? 'badge-success' : 'badge-danger'" x-text="o.estado"></span>
                            </div>
                            <div class="flex gap-2">
                                <button x-show="api.hasPermission('seguridad.opciones.modificar')" @click="editOpcion(o)" class="btn btn-outline btn-sm">Editar</button>
                                <button x-show="api.hasPermission('seguridad.opciones.eliminar') && o.estado === 'activo'" @click="deleteOpcion(o)" class="btn btn-outline btn-sm text-red-600 border-red-200">Bajar</button>
                                <button x-show="api.hasPermission('seguridad.opciones.modificar') && o.estado !== 'activo'" @click="reactivateOpcion(o)" class="btn btn-outline btn-sm text-emerald-600 border-emerald-200">Reactivar</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Permisos --}}
            <div x-show="tab === 'permisos'" class="card">
                <div class="card-header flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-900">Todos los Permisos del Sistema</h3>
                    <button x-show="api.hasPermission('seguridad.permisos.crear')" @click="openPermisoModal()" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Nuevo Permiso
                    </button>
                </div>
                <div class="px-6 py-3 border-b border-gray-100 text-xs text-gray-500 flex flex-wrap gap-3">
                    <span x-text="totalPermisos + ' permisos'"></span>
                    <span x-text="totalPermisosActivos + ' activos'"></span>
                    <span x-text="totalPermisosInactivos + ' inactivos'"></span>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <input x-model="busquedaPermisos" type="text" placeholder="Buscar permisos por código o nombre" class="input text-sm flex-1 min-w-[220px]">
                        <select x-model="estadoPermisos" class="input text-sm w-full sm:w-44">
                            <option value="">Todos</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                        </select>
                        <button x-show="api.hasPermission('seguridad.permisos.modificar')" @click="activarFiltradosPermisos()" class="btn btn-outline btn-sm">Activar filtrados</button>
                        <button x-show="api.hasPermission('seguridad.permisos.eliminar')" @click="desactivarFiltradosPermisos()" class="btn btn-outline btn-sm text-red-600 border-red-200">Desactivar filtrados</button>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(mod, modKey) in permisosFiltradosPorModulo" :key="modKey">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-2" x-text="modKey + ' (' + mod.length + ')' "></h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="p in mod" :key="p.id">
                                        <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1">
                                            <span class="badge text-[10px]" :class="p.estado === 'activo' ? 'badge-success' : 'badge-danger'" x-text="p.estado"></span>
                                            <button x-show="api.hasPermission('seguridad.permisos.modificar')" @click="editPermiso(p)" class="text-xs font-mono text-brand-600 hover:underline" x-text="p.codigo"></button>
                                            <span x-show="!api.hasPermission('seguridad.permisos.modificar')" class="text-xs font-mono text-gray-700" x-text="p.codigo"></span>
                                            <button x-show="api.hasPermission('seguridad.permisos.modificar')" @click="editPermiso(p)" class="text-[10px] uppercase tracking-wide text-gray-400 hover:text-brand-600">Editar</button>
                                            <button x-show="api.hasPermission('seguridad.permisos.eliminar') && p.estado === 'activo'" @click="deletePermiso(p)" class="text-[10px] uppercase tracking-wide text-red-500 hover:text-red-700">Bajar</button>
                                            <button x-show="api.hasPermission('seguridad.permisos.modificar') && p.estado !== 'activo'" @click="reactivatePermiso(p)" class="text-[10px] uppercase tracking-wide text-emerald-600 hover:text-emerald-700">Reactivar</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Flujo Matrícula --}}
            <div x-show="tab === 'flujos_matricula'" class="space-y-4">
                <div class="flex justify-end"><button x-show="api.hasPermission('seguridad.configurar')" @click="openFlujoCrudModal()" class="btn btn-primary">Nueva Configuración</button></div>
                <p class="text-xs text-gray-500">Use esta guía para identificar qué pantalla y qué flujo controla cada bandera antes de activar o desactivar opciones.</p>
                <div class="card" x-data="{ abierta: false }">
                    <button type="button" class="card-header w-full flex items-center justify-between text-left" @click="abierta = !abierta">
                        <h3 class="text-sm font-semibold text-gray-900">Guía rápida de banderas</h3>
                        <span class="text-xs text-gray-500" x-text="abierta ? 'Ocultar' : 'Mostrar'"></span>
                    </button>
                    <div class="p-4 overflow-x-auto" x-show="abierta" x-transition>
                        <table class="table text-sm">
                            <thead>
                                <tr>
                                    <th>Banda</th>
                                    <th>Pantalla</th>
                                    <th>Uso real</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <tr>
                                    <td class="font-mono text-xs">habilita_reserva_cupo</td>
                                    <td>Matrículas</td>
                                    <td>Permite reservar cupo al iniciar la matrícula.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_revision_contable</td>
                                    <td>Matrículas / Pagos</td>
                                    <td>Habilita el paso de revisión contable antes de aprobar.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_aprobacion_pago</td>
                                    <td>Pagos / Matrículas</td>
                                    <td>Permite aprobar pagos y confirmar matrícula asociada.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_generacion_recibo</td>
                                    <td>Pagos / Recibos</td>
                                    <td>Genera o habilita el recibo de caja al aprobar el pago.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_carga_comprobante</td>
                                    <td>Portal estudiante / Pagos</td>
                                    <td>Permite subir comprobantes y verlos en revisión.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">requiere_comprobante</td>
                                    <td>Portal estudiante</td>
                                    <td>Obliga a adjuntar comprobante antes de enviar el pago.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_confirmacion_matricula</td>
                                    <td>Portal estudiante / Matrículas</td>
                                    <td>Habilita la confirmación de matrícula desde el flujo del estudiante.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_seleccion_obligaciones</td>
                                    <td>Portal estudiante / Matrículas</td>
                                    <td>Permite elegir manualmente las obligaciones a pagar.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_whatsapp</td>
                                    <td>Portal estudiante</td>
                                    <td>Muestra el acceso a WhatsApp autorizado después del pago.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_reenganche</td>
                                    <td>Portal estudiante / Matrículas</td>
                                    <td>Permite reencauzar un pago o matrícula en revisión o rechazo.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">habilita_solicitud_link</td>
                                    <td>Pagos / Portal estudiante</td>
                                    <td>Permite solicitar y manejar el flujo de enlace de pago.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">link_pago_url</td>
                                    <td>Pagos / Portal estudiante</td>
                                    <td>Guarda el enlace externo de pago publicado por contabilidad.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">link_pago_estado</td>
                                    <td>Pagos / Portal estudiante</td>
                                    <td>Indica si el enlace fue enviado, ejecutado o rechazado.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">confirmado_por_estudiante_en</td>
                                    <td>Portal estudiante</td>
                                    <td>Registra cuándo el estudiante confirmó el pago por link.</td>
                                </tr>
                                <tr>
                                    <td class="font-mono text-xs">confirmado_por_estudiante_id</td>
                                    <td>Portal estudiante</td>
                                    <td>Guarda qué estudiante confirmó el enlace de pago.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Código</th><th>Origen</th><th>Conceptos</th><th>Métodos</th><th>Estado</th></tr></thead>
                            <tbody>
                                <template x-for="c in flujosMatricula" :key="c.id">
                                    <tr>
                                        <td x-text="c.codigo"></td>
                                        <td x-text="c.origen"></td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="cp in (c.conceptos_pago || c.conceptosPago || [])" :key="cp.id">
                                                    <span class="badge badge-info" x-text="cp.codigo"></span>
                                                </template>
                                                <span x-show="!(c.conceptos_pago || c.conceptosPago || []).length" class="text-gray-400">-</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="mp in (c.metodos_pago || c.metodosPago || [])" :key="mp.id">
                                                    <span class="badge badge-sky" x-text="mp.codigo"></span>
                                                </template>
                                                <span x-show="!(c.metodos_pago || c.metodosPago || []).length" class="text-gray-400">-</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" :class="c.estado === 'activo' ? 'badge-success' : 'badge-danger'" x-text="c.estado"></span>
                                            <div class="mt-2 flex gap-2">
                                                <button @click="openFlujoCrudModal(c)" class="btn btn-ghost btn-sm">Editar</button>
                                                <button @click="desactivarFlujo(c)" class="btn btn-ghost btn-sm text-red-600">Desactivar</button>
                                                <button @click="eliminarFlujo(c)" class="btn btn-ghost btn-sm text-red-700 font-semibold">Eliminar</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Auditoría --}}
            <div x-show="tab === 'auditores'" class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Bitácora de Actividad</h3></div>
                <div class="p-4 border-b border-gray-100 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="label">Usuario</label>
                            <select x-model="filtros.usuario_id" class="input text-sm">
                                <option value="">Todos</option>
                                <template x-for="u in usuarios" :key="u.id">
                                    <option :value="u.id" x-text="u.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="label">Método</label>
                            <select x-model="filtros.metodo" class="input text-sm">
                                <option value="">Todos</option>
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Estado HTTP</label>
                            <select x-model="filtros.estado_http" class="input text-sm">
                                <option value="">Todos</option>
                                <option value="200">200 OK</option>
                                <option value="201">201 Creado</option>
                                <option value="302">302 Redirección</option>
                                <option value="400">400 Bad Request</option>
                                <option value="401">401 No Autorizado</option>
                                <option value="403">403 Prohibido</option>
                                <option value="404">404 No Encontrado</option>
                                <option value="422">422 Validación</option>
                                <option value="500">500 Error Interno</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Búsqueda (ruta)</label>
                            <input x-model="filtros.busqueda" type="text" placeholder="Buscar ruta..." class="input text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Fecha desde</label>
                            <input x-model="filtros.fecha_desde" type="datetime-local" class="input text-sm">
                        </div>
                        <div>
                            <label class="label">Fecha hasta</label>
                            <input x-model="filtros.fecha_hasta" type="datetime-local" class="input text-sm">
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button @click="cargarBitacora()" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            Filtrar
                        </button>
                        <button @click="limpiarFiltros()" class="btn btn-outline btn-sm">Limpiar</button>
                        <span x-show="cargandoBitacora" class="inline-flex items-center text-xs text-gray-400"><div class="animate-spin rounded-full h-3 w-3 border-2 border-gray-300 border-t-gray-600 mr-2"></div>Cargando...</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Usuario</th><th>Acción</th><th>Ruta</th><th>Estado</th><th>IP</th><th>Duración</th><th>Fecha</th></tr></thead>
                            <tbody>
                                <template x-for="b in bitacora" :key="b.id">
                                    <tr>
                                        <td class="font-medium" x-text="b.usuario?.name || 'Sistema'"></td>
                                        <td><span class="badge badge-info" x-text="b.metodo"></span></td>
                                        <td class="text-gray-500 font-mono text-xs max-w-[300px] truncate" x-text="b.ruta" :title="b.ruta"></td>
                                        <td><span :class="b.codigo_http < 400 ? 'badge-success' : 'badge-danger'" class="badge" x-text="b.codigo_http"></span></td>
                                        <td class="text-gray-500 text-xs" x-text="b.ip"></td>
                                        <td class="text-gray-500 text-xs" x-text="b.duracion_ms ? b.duracion_ms + 'ms' : '-'"></td>
                                        <td class="text-gray-500 text-xs" x-text="formatearFecha(b.created_at)"></td>
                                    </tr>
                                </template>
                                <tr x-show="bitacora.length === 0">
                                    <td colspan="7" class="text-center text-gray-400 py-8">No se encontraron registros</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'correos'" class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Bitácora de Correos Enviados</h3></div>
                <div class="p-4 border-b border-gray-100 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="label">Tipo</label>
                            <select x-model="filtrosCorreos.tipo" class="input text-sm">
                                <option value="">Todos</option>
                                <option value="registro">Registro</option>
                                <option value="activacion">Activación</option>
                                <option value="reenvio">Reenvío</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Estado</label>
                            <select x-model="filtrosCorreos.estado" class="input text-sm">
                                <option value="">Todos</option>
                                <option value="enviado">Enviado</option>
                                <option value="fallido">Fallido</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Destinatario</label>
                            <input x-model="filtrosCorreos.destinatario" type="text" placeholder="Buscar correo..." class="input text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Fecha desde</label>
                            <input x-model="filtrosCorreos.fecha_desde" type="datetime-local" class="input text-sm">
                        </div>
                        <div>
                            <label class="label">Fecha hasta</label>
                            <input x-model="filtrosCorreos.fecha_hasta" type="datetime-local" class="input text-sm">
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button @click="cargarCorreos()" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            Filtrar
                        </button>
                        <button @click="limpiarFiltrosCorreos()" class="btn btn-outline btn-sm">Limpiar</button>
                        <span x-show="cargandoCorreos" class="inline-flex items-center text-xs text-gray-400"><div class="animate-spin rounded-full h-3 w-3 border-2 border-gray-300 border-t-gray-600 mr-2"></div>Cargando...</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Destinatario</th><th>Asunto</th><th>Tipo</th><th>Estudiante</th><th>Estado</th><th>Error</th><th>Fecha</th><th class="text-right">Acción</th></tr></thead>
                            <tbody>
                                <template x-for="c in correos" :key="c.id">
                                    <tr>
                                        <td class="text-gray-500 font-mono text-xs" x-text="c.destinatario"></td>
                                        <td class="font-medium text-sm max-w-[250px] truncate" x-text="c.asunto" :title="c.asunto"></td>
                                        <td><span class="badge" :class="c.tipo === 'registro' ? 'badge-info' : c.tipo === 'activacion' ? 'badge-warning' : 'badge-neutral'" x-text="c.tipo"></span></td>
                                        <td class="text-gray-500 font-mono text-xs" x-text="c.codigo_estudiante || '-'"></td>
                                        <td><span :class="c.estado === 'enviado' ? 'badge-success' : 'badge-danger'" class="badge" x-text="c.estado"></span></td>
                                        <td class="text-red-500 text-xs max-w-[200px] truncate" x-text="c.error || '-'" :title="c.error"></td>
                                        <td class="text-gray-500 text-xs" x-text="formatearFecha(c.creado_en)"></td>
                                        <td class="text-right">
                                            <button @click="verCorreo(c)" class="btn btn-ghost btn-sm">Ver</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="correos.length === 0">
                                    <td colspan="8" class="text-center text-gray-400 py-8">No se encontraron registros</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Permiso Modal --}}
    <div x-show="showPermisoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showPermisoModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editingPermiso ? 'Editar Permiso' : 'Nuevo Permiso'"></h3>
                <button @click="showPermisoModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form @submit.prevent="savePermiso()" class="p-6 space-y-4">
                <div>
                    <label class="label">Opción de módulo</label>
                    <select x-model="permisoForm.opcion_modulo_id" required class="input">
                        <option value="">Seleccione una opción</option>
                        <template x-for="opt in opcionesModulo" :key="opt.id">
                            <option :value="opt.id" x-text="(opt.modulo?.codigo || 'General') + ' · ' + opt.codigo + ' · ' + opt.nombre"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Código</label>
                        <input x-model="permisoForm.codigo" type="text" required maxlength="80" class="input" placeholder="ej. seguridad.permisos.crear">
                    </div>
                    <div>
                        <label class="label">Acción</label>
                        <input x-model="permisoForm.accion" type="text" required maxlength="30" class="input" placeholder="crear">
                    </div>
                </div>
                <div>
                    <label class="label">Nombre</label>
                    <input x-model="permisoForm.nombre" type="text" required maxlength="100" class="input" placeholder="Crear permiso">
                </div>
                <div x-show="errorPermiso" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="errorPermiso"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showPermisoModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="savingPermiso" class="btn btn-primary"><span x-text="savingPermiso ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Correo Viewer Modal --}}
    <div x-show="showCorreoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCorreoModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate" x-text="correoSeleccionado?.asunto"></h3>
                    <p class="text-sm text-gray-500" x-text="'Para: ' + (correoSeleccionado?.destinatario || '')"></p>
                </div>
                <button @click="showCorreoModal = false" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <template x-if="correoSeleccionado?.cuerpo_html">
                    <iframe srcdoc="" :srcdoc="correoSeleccionado.cuerpo_html" class="w-full h-full min-h-[60vh] bg-white rounded-lg border border-gray-200"></iframe>
                </template>
                <template x-if="!correoSeleccionado?.cuerpo_html">
                    <div class="flex items-center justify-center h-48 text-gray-400">
                        <p>No hay contenido HTML disponible para este correo</p>
                    </div>
                </template>
            </div>
            <div class="flex items-center justify-between px-6 py-3 border-t border-gray-100 shrink-0 bg-gray-50">
                <span class="text-xs text-gray-400">
                    <template x-if="correoSeleccionado">
                        <span x-text="formatearFecha(correoSeleccionado.creado_en) + ' · ' + (correoSeleccionado.estado === 'enviado' ? 'Enviado' : 'Fallido')"></span>
                    </template>
                </span>
                <button @click="showCorreoModal = false" class="btn btn-outline btn-sm">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- User Modal --}}
    <div x-show="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showUserModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="editingUser ? 'Editar Usuario' : 'Nuevo Usuario'"></h3>
                <button @click="showUserModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
            </div>
            <form @submit.prevent="saveUser()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2"><label class="label">Nombre</label><input x-model="userForm.name" type="text" required class="input"></div>
                    <div><label class="label">Email</label><input x-model="userForm.email" type="email" required class="input"></div>
                    <div><label class="label">Contraseña</label><input x-model="userForm.password" type="password" :required="!editingUser" minlength="8" class="input" :placeholder="editingUser ? 'Usar restablecer contraseña para cambiarla' : 'Mínimo 8 caracteres'"></div>
                    <div><label class="label">Confirmar contraseña</label><input x-model="userForm.password_confirmation" type="password" :required="!editingUser" minlength="8" class="input"></div>
                    <div class="col-span-2"><label class="label">Docente vinculado</label><select x-model="userForm.docente_id" class="input"><option value="">Usuario administrativo sin vínculo docente</option><template x-for="d in docentesDisponiblesParaUsuario()" :key="d.id"><option :value="d.id" x-text="d.codigo + ' · ' + d.nombre + ' ' + d.apellido"></option></template></select><p class="mt-1 text-xs text-gray-500">Primero cree la ficha del docente en Catálogos. Un docente solo puede tener una cuenta.</p></div>
                    <div class="col-span-2"><label class="label">Roles *</label><div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border border-gray-200 p-3 max-h-40 overflow-y-auto"><template x-for="rol in rolesDisponiblesParaAsignar()" :key="rol.id"><label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" :value="rol.codigo" x-model="userForm.roles" class="rounded border-gray-300 text-brand-600"><span x-text="rol.nombre + ' (' + rol.codigo + ')'"></span></label></template><p x-show="rolesDisponiblesParaAsignar().length === 0" class="text-sm text-amber-700">No hay roles disponibles o no tiene permiso para consultarlos.</p></div></div>
                    <div class="col-span-2"><label class="label">Sucursales con acceso</label><div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border border-gray-200 p-3 max-h-40 overflow-y-auto"><template x-for="sucursal in sucursalesCatalogo" :key="sucursal.id"><label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" :value="sucursal.codigo" x-model="userForm.sucursales" class="rounded border-gray-300 text-brand-600"><span x-text="sucursal.codigo + ' · ' + sucursal.nombre"></span></label></template><p x-show="sucursalesCatalogo.length === 0" class="text-sm text-amber-700">No hay sucursales disponibles o no tiene permiso para consultarlas.</p></div><p class="mt-1 text-xs text-gray-500">Puede asignar una o varias sucursales. Eso define qué información por sucursal podrá consultar el usuario.</p></div>
                    <div><label class="label">Estado</label><select x-model="userForm.estado" class="input"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </div>
                <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3"><p class="text-sm text-red-600" x-text="error"></p></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showUserModal = false" class="btn btn-outline">Cancelar</button>
                    <button type="submit" :disabled="saving" class="btn btn-primary"><span x-text="saving ? 'Guardando...' : 'Guardar'"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Permisos Modal (slide-over) --}}
    <div x-show="showPermisosModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/50" @click="closePermisosModal()"></div>
        <div class="absolute inset-y-0 right-0 w-full max-w-2xl">
            <div x-show="showPermisosModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="h-full flex flex-col bg-white shadow-2xl">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Administrar Permisos</h3>
                        <p class="text-sm text-gray-500" x-text="rolNombre"></p>
                    </div>
                    <button @click="closePermisosModal()" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Search + Copy --}}
                <div class="px-6 py-3 border-b border-gray-200 space-y-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg></div>
                        <input x-model="busquedaPermisos" type="text" placeholder="Buscar permisos por código..." class="input pl-10 text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <select x-model="copiarDesdeRolId" class="input text-sm flex-1">
                            <option value="">Copiar permisos desde otro rol...</option>
                            <template x-for="r in roles" :key="r.id">
                                <option x-show="r.id !== rolEditando?.id" :value="r.id" x-text="r.nombre"></option>
                            </template>
                        </select>
                        <button @click="copiarDesdeRol()" :disabled="!copiarDesdeRolId" class="btn btn-outline btn-sm whitespace-nowrap">Copiar</button>
                    </div>
                </div>

                {{-- Permisos Matrix --}}
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <template x-for="(permisos, modKey) in permisosFiltrados" :key="modKey">
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            {{-- Module Header --}}
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" :checked="isModuloMarcado(modKey)" :indeterminate.prop="isModuloParcial(modKey)" @change="toggleModuloTodos(modKey)" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm font-semibold text-gray-800 uppercase tracking-wide" x-text="modKey"></span>
                                </div>
                                <span class="text-xs text-gray-500 font-mono" x-text="conteoModulo(modKey)"></span>
                            </div>
                            {{-- Permissions List --}}
                            <div class="divide-y divide-gray-100">
                                <template x-for="p in permisos" :key="p.id">
                                    <label class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" :checked="permisosRol.includes(p.codigo)" @change="togglePermiso(p.codigo)" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <div class="flex-1 min-w-0">
                                            <span class="text-sm text-gray-700 font-mono" x-text="p.codigo"></span>
                                            <span class="text-xs text-gray-400 ml-2" x-text="p.nombre"></span>
                                        </div>
                                        <span class="badge badge-info text-[10px]" x-text="p.accion"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="Object.keys(permisosFiltrados).length === 0" class="text-center py-10">
                        <p class="text-gray-400 text-sm">No se encontraron permisos</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <span class="text-sm text-gray-500" x-text="permisosRol.length + ' permisos seleccionados'"></span>
                    <div class="flex gap-3">
                        <button @click="closePermisosModal()" class="btn btn-outline">Cancelar</button>
                        <button @click="guardarPermisos()" :disabled="savingPermisos" class="btn btn-primary">
                            <template x-if="savingPermisos"><div class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white mr-2"></div></template>
                            <span x-text="savingPermisos ? 'Guardando...' : 'Guardar Permisos'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showModuloModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showModuloModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold" x-text="editingModulo ? 'Editar Módulo' : 'Nuevo Módulo'"></h3>
            <input x-model="moduloForm.codigo" class="input" placeholder="Código">
            <input x-model="moduloForm.nombre" class="input" placeholder="Nombre">
            <input x-model="moduloForm.orden" type="number" class="input" placeholder="Orden">
            <div class="flex justify-end gap-2"><button @click="showModuloModal = false" class="btn btn-outline">Cancelar</button><button @click="saveModulo()" class="btn btn-primary">Guardar</button></div>
        </div>
    </div>

    <div x-show="showOpcionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showOpcionModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold" x-text="editingOpcion ? 'Editar Opción' : 'Nueva Opción'"></h3>
            <select x-model="opcionForm.modulo_id" class="input"><option value="">Seleccione módulo</option><template x-for="m in modulos" :key="m.id"><option :value="m.id" x-text="m.codigo + ' · ' + m.nombre"></option></template></select>
            <input x-model="opcionForm.codigo" class="input" placeholder="Código">
            <input x-model="opcionForm.nombre" class="input" placeholder="Nombre">
            <input x-model="opcionForm.ruta" class="input" placeholder="Ruta">
            <input x-model="opcionForm.orden" type="number" class="input" placeholder="Orden">
            <div class="flex justify-end gap-2"><button @click="showOpcionModal = false" class="btn btn-outline">Cancelar</button><button @click="saveOpcion()" class="btn btn-primary">Guardar</button></div>
        </div>
    </div>

    <div x-show="showFlujoCrudModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showFlujoCrudModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl p-6 space-y-4">
            <h3 class="text-lg font-semibold" x-text="editingFlujoCrud ? 'Editar Configuración de Flujo' : 'Nueva Configuración de Flujo'"></h3>
            <div class="flex gap-1.5 text-xs border-b border-gray-200 pb-3">
                <button type="button" @click="flujoPaso = 1" :class="flujoPaso === 1 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium">Métodos</button>
                <button type="button" @click="flujoPaso = 2" :class="flujoPaso === 2 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium">Conceptos</button>
                <button type="button" @click="flujoPaso = 3" :class="flujoPaso === 3 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="px-2.5 py-1 rounded-md font-medium">Banderas</button>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-3 items-start">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Código</label>
                        <input x-model="flujoCrudForm.codigo" class="input" placeholder="Código">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Estado</label>
                        <select x-model="flujoCrudForm.estado" class="input">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <input x-model="flujoCrudForm.origen" type="hidden">
            </div>
                <div x-show="flujoPaso === 1" class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-3 mt-2">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-xs font-semibold uppercase text-gray-600">Métodos asociados</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr] gap-4 items-start">
                        <div class="rounded-xl border border-gray-200 bg-white p-3 flex flex-col h-full">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lista</p>
                            <select x-model="metodoSeleccionadoFlujo" class="input w-full h-36 flex-1" size="6">
                                <template x-for="m in metodosDisponiblesFlujoSinSeleccionados()" :key="m.id">
                                    <option :value="String(m.id)" x-text="m.codigo + ' · ' + m.nombre"></option>
                                </template>
                            </select>
                            <div class="mt-3 flex justify-end">
                                <button type="button" @click="agregarMetodoSeleccionadoFlujo()" class="btn btn-primary btn-sm">+</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-3 flex flex-col h-full">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Seleccionados</p>
                            <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1 flex-1">
                                <template x-if="flujoCrudForm.metodo_pago_ids.length === 0">
                                    <div class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-sm text-gray-400">Sin métodos seleccionados</div>
                                </template>
                                <template x-for="id in flujoCrudForm.metodo_pago_ids" :key="id">
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-brand-900" x-text="formatoMetodoSeleccionado(id)"></p>
                                        </div>
                                        <button type="button" @click="quitarMetodoFlujo(id)" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-red-600 border border-red-200 hover:bg-red-50">×</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="flujoPaso === 2" class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-3 mt-2">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-xs font-semibold uppercase text-gray-600">Conceptos asociados</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr] gap-4 items-start">
                        <div class="rounded-xl border border-gray-200 bg-white p-3 flex flex-col h-full">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lista</p>
                            <select x-model="conceptoSeleccionadoFlujo" class="input w-full h-36 flex-1" size="10">
                                <template x-for="c in conceptosDisponiblesFlujoSinSeleccionados()" :key="c.id">
                                    <option :value="String(c.id)" x-text="c.codigo + ' · ' + c.nombre"></option>
                                </template>
                            </select>
                            <div class="mt-3 flex justify-end">
                                <button type="button" @click="agregarConceptoSeleccionadoFlujo()" class="btn btn-primary btn-sm">+</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-3 flex flex-col h-full">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Seleccionados</p>
                            <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1 flex-1">
                                <template x-if="flujoCrudForm.concepto_pago_ids.length === 0">
                                    <div class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-sm text-gray-400">Sin conceptos seleccionados</div>
                                </template>
                                <template x-for="id in flujoCrudForm.concepto_pago_ids" :key="id">
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-brand-900" x-text="formatoConceptoSeleccionado(id)"></p>
                                        </div>
                                        <button type="button" @click="quitarConceptoFlujo(id)" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-red-600 border border-red-200 hover:bg-red-50">×</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            <div x-show="flujoPaso === 3" class="space-y-3 text-xs">
                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3">
                    <h4 class="mb-2 text-xs font-semibold text-blue-900">Portal administrativo</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_reserva_cupo" class="rounded"> Reservar cupo</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_revision_contable" class="rounded"> Revisar contabilidad</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_aprobacion_pago" class="rounded"> Aprobar pago</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_generacion_recibo" class="rounded"> Generar recibo</label>
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                    <h4 class="mb-2 text-xs font-semibold text-emerald-900">Portal del estudiante</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_carga_comprobante" class="rounded"> Cargar comprobante</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.requiere_comprobante" class="rounded"> Requiere comprobante</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_solicitud_link" class="rounded"> Solicitar link de pago</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_confirmacion_matricula" class="rounded"> Confirmar matrícula</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_seleccion_obligaciones" class="rounded"> Selección de obligaciones</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_whatsapp" class="rounded"> WhatsApp</label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="flujoCrudForm.habilita_reenganche" class="rounded"> Reenganche</label>
                    </div>
                </div>
            </div>
            <div x-show="flujoCrudError" class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700" x-text="flujoCrudError"></div>
            <div class="flex justify-end gap-2"><button type="button" @click="showFlujoCrudModal = false" class="btn btn-outline">Cancelar</button><button type="button" @click="saveFlujoCrud()" :disabled="savingFlujoCrud" class="btn btn-primary"><span x-text="savingFlujoCrud ? 'Guardando...' : (editingFlujoCrud ? 'Actualizar' : 'Guardar')"></span></button></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function seguridad() {
    return {
        loading: true, tab: 'usuarios', error: '',
        usuarios: [], roles: [], docentes: [], sucursalesCatalogo: [], permisosPorModulo: {}, bitacora: [],
        flujosMatricula: [],
        conceptosPago: [], metodosPago: [],
        cargandoBitacora: false,
        filtros: { usuario_id: '', metodo: '', estado_http: '', fecha_desde: '', fecha_hasta: '', busqueda: '' },
        correos: [], cargandoCorreos: false,
        filtrosCorreos: { tipo: '', destinatario: '', estado: '', fecha_desde: '', fecha_hasta: '' },
        showCorreoModal: false, correoSeleccionado: null,

        /* User modal */
        showUserModal: false, editingUser: false, saving: false, editUserId: null,
        userForm: { name: '', email: '', password: '', password_confirmation: '', docente_id: '', roles: [], sucursales: [], estado: 'activo' },

        /* Módulos / Opciones */
        modulos: [],
        busquedaModulos: '',
        estadoModulos: '',
        showModuloModal: false, editingModulo: false, savingModulo: false, editModuloId: null,
        moduloForm: { codigo: '', nombre: '', orden: '' },
        showOpcionModal: false, editingOpcion: false, savingOpcion: false, editOpcionId: null,
        busquedaOpciones: '',
        estadoOpciones: '',
        opcionForm: { modulo_id: '', codigo: '', nombre: '', ruta: '', orden: '' },

        /* Permisos modal */
        showPermisosModal: false, savingPermisos: false,
        rolEditando: null, rolNombre: '',
        permisosRol: [],
        todosPermisos: {},
        busquedaPermisos: '',
        copiarDesdeRolId: '',

        /* Nuevo permiso */
        showPermisoModal: false, savingPermiso: false, errorPermiso: '', editingPermiso: false, editPermisoId: null,
        opcionesModulo: [],
        estadoPermisos: '',
        permisoForm: { opcion_modulo_id: '', codigo: '', nombre: '', accion: '' },

        showFlujoCrudModal: false, savingFlujoCrud: false, editingFlujoCrud: false, editFlujoId: null, flujoCrudError: '', flujoPaso: 1,
        flujoCrudForm: { codigo: '', origen: 'tecnico', concepto_pago_ids: [], metodo_pago_ids: [], metodo_pago_id: '', estado: 'activo', habilita_reserva_cupo: true, habilita_carga_comprobante: true, requiere_comprobante: true, habilita_revision_contable: true, habilita_aprobacion_pago: true, habilita_generacion_recibo: true, habilita_confirmacion_matricula: true, habilita_seleccion_obligaciones: true, habilita_whatsapp: true, habilita_reenganche: true, habilita_solicitud_link: true },
        conceptoSeleccionadoFlujo: '',
        metodoSeleccionadoFlujo: '',

        /* ---------- Init ---------- */
        async init() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            try {
                await this.cargarTabActual();
            } catch(e) {} finally { this.loading = false; }
        },

        async cambiarTab(tab) {
            this.tab = tab;
            await this.cargarTabActual();
        },

        async cargarTabActual() {
            const token = localStorage.getItem('auth_token');
            const h = { headers: { Authorization: `Bearer ${token}` } };
            try {
                if (this.tab === 'usuarios') {
                    const [usuariosRes, rolesRes, docentesRes, sucursalesRes] = await Promise.all([
                        window.axios.get('/api/v1/seguridad/usuarios', h),
                        window.axios.get('/api/v1/seguridad/roles', h),
                        window.axios.get('/api/v1/catalogos-academicos/docentes', h),
                        window.axios.get('/api/v1/catalogos-academicos/sucursales', h),
                    ]);
                    this.usuarios = usuariosRes.data.data?.data || usuariosRes.data.data || [];
                    this.roles = rolesRes.data.data?.data || rolesRes.data.data || [];
                    this.docentes = docentesRes.data.data?.data || docentesRes.data.data || [];
                    this.sucursalesCatalogo = sucursalesRes.data.data?.data || sucursalesRes.data.data || [];
                } else if (this.tab === 'roles') {
                    const { data } = await window.axios.get('/api/v1/seguridad/roles', h);
                    this.roles = data.data?.data || data.data || [];
                } else if (this.tab === 'modulos') {
                    const { data } = await window.axios.get('/api/v1/seguridad/modulos', { headers: h.headers, params: { estado: this.estadoModulos || undefined } });
                    this.modulos = data.data?.data || data.data || [];
                } else if (this.tab === 'opciones') {
                    const { data } = await window.axios.get('/api/v1/seguridad/opciones', { headers: h.headers, params: { estado: this.estadoOpciones || undefined } });
                    this.opcionesModulo = data.data?.data || data.data || [];
                } else if (this.tab === 'permisos') {
                    const { data } = await window.axios.get('/api/v1/seguridad/permisos', { headers: h.headers, params: { estado: this.estadoPermisos || undefined } });
                    const permisos = data.data?.data || data.data || [];
                    this.permisosPorModulo = {};
                    permisos.forEach(p => {
                        const mod = p.opcion_modulo?.modulo?.codigo || 'General';
                        if (!this.permisosPorModulo[mod]) this.permisosPorModulo[mod] = [];
                        this.permisosPorModulo[mod].push(p);
                    });
                    this.todosPermisos = this.permisosPorModulo;
                } else if (this.tab === 'flujos_matricula') {
                    const [fRes, cRes, mpRes] = await Promise.allSettled([
                        window.axios.get('/api/v1/seguridad/configuraciones-flujo-matricula', h),
                        window.axios.get('/api/v1/catalogos-academicos/conceptos-pago', h),
                        window.axios.get('/api/v1/catalogos-academicos/metodos-pago', h),
                    ]);
                    this.flujosMatricula = fRes.status === 'fulfilled' ? (fRes.value.data.data?.data || fRes.value.data.data || []) : [];
                    this.conceptosPago = cRes.status === 'fulfilled' ? (cRes.value.data.data?.data || cRes.value.data.data || []) : [];
                    this.metodosPago = mpRes.status === 'fulfilled' ? (mpRes.value.data.data?.data || mpRes.value.data.data || []) : [];
                } else if (this.tab === 'auditores') {
                    await this.cargarBitacora();
                } else if (this.tab === 'correos') {
                    await this.cargarCorreos();
                }
            } catch(e) {}
        },

        openFlujoCrudModal(c = null) {
            if (c && !c.id) {
                this.flujoCrudError = 'No se pudo identificar el registro a editar';
                return;
            }
            this.flujoCrudError = '';
            this.editingFlujoCrud = !!c;
            this.editFlujoId = c ? Number(c.id || 0) : null;
            this.flujoPaso = 1;
            this.flujoCrudForm = c ? {
                codigo: c.codigo || '',
                origen: c.origen || 'tecnico',
                concepto_pago_ids: (() => {
                    const desdePivot = (c.conceptos_pago || c.conceptosPago || []).map(x => String(x.id));
                    if (desdePivot.length > 0) return desdePivot;
                    if (c.concepto_pago_id) return [String(c.concepto_pago_id)];
                    return [];
                })(),
                metodo_pago_ids: (() => {
                    const desdePivot = (c.metodos_pago || c.metodosPago || []).map(x => String(x.id));
                    if (desdePivot.length > 0) return desdePivot;
                    if (c.metodo_pago_id) return [String(c.metodo_pago_id)];
                    return [];
                })(),
                metodo_pago_id: c.metodo_pago_id || null,
                estado: c.estado || 'activo',
                habilita_reserva_cupo: !!c.habilita_reserva_cupo,
                habilita_carga_comprobante: !!c.habilita_carga_comprobante,
                requiere_comprobante: !!c.requiere_comprobante,
                habilita_revision_contable: !!c.habilita_revision_contable,
                habilita_aprobacion_pago: !!c.habilita_aprobacion_pago,
                habilita_generacion_recibo: !!c.habilita_generacion_recibo,
                habilita_confirmacion_matricula: !!c.habilita_confirmacion_matricula,
                habilita_seleccion_obligaciones: !!c.habilita_seleccion_obligaciones,
                habilita_whatsapp: !!c.habilita_whatsapp,
                habilita_reenganche: !!c.habilita_reenganche,
                habilita_solicitud_link: !!c.habilita_solicitud_link,
            } : { codigo: '', origen: 'tecnico', concepto_pago_ids: [], metodo_pago_ids: [], metodo_pago_id: null, estado: 'activo', habilita_reserva_cupo: true, habilita_carga_comprobante: true, requiere_comprobante: true, habilita_revision_contable: true, habilita_aprobacion_pago: true, habilita_generacion_recibo: true, habilita_confirmacion_matricula: true, habilita_seleccion_obligaciones: true, habilita_whatsapp: true, habilita_reenganche: true, habilita_solicitud_link: true };
            this.showFlujoCrudModal = true;
        },

        alCambiarMetodoFlujo() {
            this.flujoCrudForm.concepto_pago_ids = [];
            this.conceptoSeleccionadoFlujo = '';
            this.flujoCrudForm.metodo_pago_ids = [];
            this.metodoSeleccionadoFlujo = '';
        },

        metodosDisponiblesFlujoSinSeleccionados() {
            const seleccionados = new Set((this.flujoCrudForm.metodo_pago_ids || []).map(v => String(v)));
            return this.metodosPago.filter(m => !seleccionados.has(String(m.id)));
        },

        agregarMetodoFlujo(id) {
            const v = String(id);
            if (!this.flujoCrudForm.metodo_pago_ids.includes(v)) {
                this.flujoCrudForm.metodo_pago_ids.push(v);
            }
        },

        agregarMetodoSeleccionadoFlujo() {
            if (!this.metodoSeleccionadoFlujo) return;
            this.agregarMetodoFlujo(this.metodoSeleccionadoFlujo);
            this.metodoSeleccionadoFlujo = '';
        },

        quitarMetodoFlujo(id) {
            const v = String(id);
            this.flujoCrudForm.metodo_pago_ids = this.flujoCrudForm.metodo_pago_ids.filter(x => x !== v);
        },

        formatoMetodoSeleccionado(id) {
            const m = this.metodosPago.find(x => String(x.id) === String(id));
            return m ? `${m.codigo} · ${m.nombre}` : `Método #${id}`;
        },

        conceptosDisponiblesFlujo() {
            return this.conceptosPago;
        },

        conceptosDisponiblesFlujoSinSeleccionados() {
            const seleccionados = new Set((this.flujoCrudForm.concepto_pago_ids || []).map(v => String(v)));
            return this.conceptosDisponiblesFlujo().filter(c => !seleccionados.has(String(c.id)));
        },

        agregarConceptoFlujo(id) {
            const v = String(id);
            if (!this.flujoCrudForm.concepto_pago_ids.includes(v)) {
                this.flujoCrudForm.concepto_pago_ids.push(v);
            }
        },

        agregarConceptoSeleccionadoFlujo() {
            if (!this.conceptoSeleccionadoFlujo) return;
            this.agregarConceptoFlujo(this.conceptoSeleccionadoFlujo);
            this.conceptoSeleccionadoFlujo = '';
        },

        quitarConceptoFlujo(id) {
            const v = String(id);
            this.flujoCrudForm.concepto_pago_ids = this.flujoCrudForm.concepto_pago_ids.filter(x => x !== v);
        },

        formatoConceptoSeleccionado(id) {
            const c = this.conceptosPago.find(x => String(x.id) === String(id));
            return c ? `${c.codigo} · ${c.nombre}` : `Concepto #${id}`;
        },

        async desactivarFlujo(c) { const token = localStorage.getItem('auth_token'); await window.axios.post(`/api/v1/seguridad/configuraciones-flujo-matricula/${c.id}`, { headers: { Authorization: `Bearer ${token}` } }); await this.init(); },
        async eliminarFlujo(c) {
            if (!confirm(`¿Eliminar permanentemente "${c.codigo}"? Esta acción no se puede deshacer.`)) return;
            const token = localStorage.getItem('auth_token');
            try {
                const { data } = await window.axios.post(`/api/v1/seguridad/configuraciones-flujo-matricula/${c.id}/forzar`, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado !== 'A') { alert(data.mensaje || 'Error al eliminar'); return; }
                await this.init();
            } catch(e) { alert(window.extractError(e, 'Error al eliminar la configuración')); }
        },

        async saveFlujoCrud() {
            this.savingFlujoCrud = true;
            this.flujoCrudError = '';
            const token = localStorage.getItem('auth_token');
            try {
                if (!this.flujoCrudForm.codigo || this.flujoCrudForm.concepto_pago_ids.length === 0 || this.flujoCrudForm.metodo_pago_ids.length === 0) {
                    this.flujoCrudError = 'Código, al menos un método y un concepto son obligatorios';
                    return;
                }
                const url = this.editingFlujoCrud ? `/api/v1/seguridad/configuraciones-flujo-matricula/${Number(this.editFlujoId)}` : '/api/v1/seguridad/configuraciones-flujo-matricula';
                const payload = { ...this.flujoCrudForm, estado: this.flujoCrudForm.estado || 'activo', metodo_pago_id: this.flujoCrudForm.metodo_pago_ids[0] || null, metodo_pago_ids: this.flujoCrudForm.metodo_pago_ids.map(v => Number(v)), origen: 'tecnico', concepto_pago_ids: this.flujoCrudForm.concepto_pago_ids.map(v => Number(v)) };
                const { data } = await window.api.actualizar(url, payload, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') { this.showFlujoCrudModal = false; await this.init(); }
                else { this.flujoCrudError = data.mensaje || 'No se pudo guardar la configuración'; }
            } catch(e) { this.flujoCrudError = window.extractError(e, 'Error al guardar configuración'); } finally { this.savingFlujoCrud = false; }
        },

        /* ---------- Bitácora ---------- */
        formatearFecha(fecha) {
            if (!fecha) return '-';
            try {
                return window.formatDateLocal(fecha, {
                    year: 'numeric', month: '2-digit', day: '2-digit',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    hour12: false
                });
            } catch(e) { return '-'; }
        },

        async cargarBitacora() {
            this.cargandoBitacora = true;
            const token = localStorage.getItem('auth_token');
            const params = new URLSearchParams();
            params.set('per_page', '50');
            if (this.filtros.usuario_id) params.set('usuario_id', this.filtros.usuario_id);
            if (this.filtros.metodo) params.set('metodo', this.filtros.metodo);
            if (this.filtros.estado_http) params.set('estado_http', this.filtros.estado_http);
            if (this.filtros.fecha_desde) params.set('fecha_desde', this.filtros.fecha_desde);
            if (this.filtros.fecha_hasta) params.set('fecha_hasta', this.filtros.fecha_hasta);
            if (this.filtros.busqueda) params.set('busqueda', this.filtros.busqueda);
            try {
                const { data } = await window.axios.get(`/api/v1/seguridad/auditoria/peticiones?${params}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                this.bitacora = data.data?.data || data.data || [];
            } catch(e) {
                this.bitacora = [];
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al cargar bitácora', type: 'error' } }));
            } finally { this.cargandoBitacora = false; }
        },

        limpiarFiltros() {
            this.filtros = { usuario_id: '', metodo: '', estado_http: '', fecha_desde: '', fecha_hasta: '', busqueda: '' };
            this.cargarBitacora();
        },

        async cargarCorreos() {
            this.cargandoCorreos = true;
            const token = localStorage.getItem('auth_token');
            const params = new URLSearchParams();
            params.set('per_page', '50');
            if (this.filtrosCorreos.tipo) params.set('tipo', this.filtrosCorreos.tipo);
            if (this.filtrosCorreos.estado) params.set('estado', this.filtrosCorreos.estado);
            if (this.filtrosCorreos.destinatario) params.set('destinatario', this.filtrosCorreos.destinatario);
            if (this.filtrosCorreos.fecha_desde) params.set('fecha_desde', this.filtrosCorreos.fecha_desde);
            if (this.filtrosCorreos.fecha_hasta) params.set('fecha_hasta', this.filtrosCorreos.fecha_hasta);
            try {
                const { data } = await window.axios.get(`/api/v1/seguridad/auditoria/correos?${params}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                this.correos = data.data?.data || data.data || [];
            } catch(e) {
                this.correos = [];
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al cargar bitácora de correos', type: 'error' } }));
            } finally { this.cargandoCorreos = false; }
        },

        limpiarFiltrosCorreos() {
            this.filtrosCorreos = { tipo: '', destinatario: '', estado: '', fecha_desde: '', fecha_hasta: '' };
            this.cargarCorreos();
        },

        verCorreo(c) {
            this.correoSeleccionado = c;
            this.showCorreoModal = true;
        },

        /* ---------- Users ---------- */
        openUserModal() { this.editingUser = false; this.editUserId = null; this.error = ''; this.userForm = { name: '', email: '', password: '', password_confirmation: '', docente_id: '', roles: [], sucursales: [], estado: 'activo' }; this.showUserModal = true; },

        editUser(u) { this.editingUser = true; this.editUserId = u.id; this.error = ''; this.userForm = { name: u.name, email: u.email, password: '', password_confirmation: '', docente_id: u.docente_id || '', roles: (u.roles || []).map(r => r.codigo), sucursales: (u.sucursales || []).map(s => s.codigo), estado: u.estado }; this.showUserModal = true; },

        docentesDisponiblesParaUsuario() {
            return this.docentes.filter(d => !this.usuarios.some(u => Number(u.docente_id) === Number(d.id) && u.id !== this.editUserId));
        },

        esSuperadminActual() {
            return (window.api.user?.roles || []).some(r => r.codigo === 'SUPERADMIN');
        },

        rolesDisponiblesParaAsignar() {
            if (this.esSuperadminActual()) return this.roles;
            return this.roles.filter(r => !['SUPERADMIN', 'ADMIN_GENERAL'].includes(r.codigo));
        },

        resumenAlcanceUsuario(u) {
            const globalDirecto = (u.alcances || []).some(a => a.estado === 'activo' && a.tipo === 'global');
            const globalPorRol = (u.roles || []).some(r => (r.alcances || []).some(a => a.estado === 'activo' && a.tipo === 'global'));
            if (globalDirecto || globalPorRol) {
                return { tipo: 'global', etiqueta: 'Global', detalle: '' };
            }

            const sucursales = u.sucursales || [];
            if (sucursales.length === 0) {
                return { tipo: 'sin_sucursal', etiqueta: 'Sin sucursales', detalle: '' };
            }

            const detalle = sucursales.map(s => s.codigo + ' · ' + s.nombre).join(', ');
            return {
                tipo: 'sucursales',
                etiqueta: sucursales.length === 1 ? '1 sucursal' : `${sucursales.length} sucursales`,
                detalle,
            };
        },

        async saveUser() {
            this.saving = true; this.error = '';
            try {
                const url = this.editingUser ? `/api/v1/seguridad/usuarios/${this.editUserId}` : '/api/v1/seguridad/usuarios';
                const payload = { ...this.userForm };
                if (this.editingUser) { delete payload.password; delete payload.password_confirmation; }
                const { data } = await window.api.actualizar(url, payload, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                if (data.resultado === 'A') {
                    const usuarioId = this.editingUser ? this.editUserId : data.data.id;
                    if (this.editingUser) await window.axios.post(`/api/v1/seguridad/usuarios/${usuarioId}/roles`, { roles: payload.roles }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                    await window.axios.post(`/api/v1/seguridad/usuarios/${usuarioId}/sucursales`, { sucursales: payload.sucursales || [] }, { headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` } });
                    this.showUserModal = false; window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Usuario y roles guardados', type: 'success' } })); await this.init();
                }
                else { this.error = data.mensaje || 'Error'; }
            } catch(e) { this.error = window.extractError(e, 'Error'); } finally { this.saving = false; }
        },

        /* ---------- Roles CRUD ---------- */
        openRoleModal() { alert('Próximamente: creación de roles'); },

        openModuloModal() { this.editingModulo = false; this.editModuloId = null; this.moduloForm = { codigo: '', nombre: '', orden: '' }; this.showModuloModal = true; },
        editModulo(m) { this.editingModulo = true; this.editModuloId = m.id; this.moduloForm = { codigo: m.codigo, nombre: m.nombre, orden: m.orden ?? '' }; this.showModuloModal = true; },
        async saveModulo() {
            this.savingModulo = true;
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editingModulo ? `/api/v1/seguridad/modulos/${this.editModuloId}` : '/api/v1/seguridad/modulos';
                const { data } = await window.api.actualizar(url, this.moduloForm, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') { this.showModuloModal = false; await this.init(); }
            } catch (e) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'Error al guardar módulo'), type: 'error' } })); } finally { this.savingModulo = false; }
        },
        async deleteModulo(m) { if (!confirm(`¿Dar de baja el módulo ${m.codigo}?`)) return; const token = localStorage.getItem('auth_token'); await window.axios.post(`/api/v1/seguridad/modulos/${m.id}`, { headers: { Authorization: `Bearer ${token}` } }); await this.init(); },
        async reactivateModulo(m) {
            const token = localStorage.getItem('auth_token');
            await window.axios.post(`/api/v1/seguridad/modulos/${m.id}`, { nombre: m.nombre, orden: m.orden, estado: 'activo' }, { headers: { Authorization: `Bearer ${token}` } });
            await this.init();
        },

        openOpcionModal() { this.editingOpcion = false; this.editOpcionId = null; this.opcionForm = { modulo_id: '', codigo: '', nombre: '', ruta: '', orden: '' }; this.showOpcionModal = true; },
        editOpcion(o) { this.editingOpcion = true; this.editOpcionId = o.id; this.opcionForm = { modulo_id: o.modulo_id, codigo: o.codigo, nombre: o.nombre, ruta: o.ruta || '', orden: o.orden ?? '' }; this.showOpcionModal = true; },
        async saveOpcion() {
            this.savingOpcion = true;
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editingOpcion ? `/api/v1/seguridad/opciones/${this.editOpcionId}` : '/api/v1/seguridad/opciones';
                const payload = { modulo_id: this.opcionForm.modulo_id, codigo: this.opcionForm.codigo, nombre: this.opcionForm.nombre, ruta: this.opcionForm.ruta, orden: this.opcionForm.orden };
                const { data } = await window.api.actualizar(url, payload, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') { this.showOpcionModal = false; await this.init(); }
            } catch (e) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'Error al guardar opción'), type: 'error' } })); } finally { this.savingOpcion = false; }
        },
        async deleteOpcion(o) { if (!confirm(`¿Dar de baja la opción ${o.codigo}?`)) return; const token = localStorage.getItem('auth_token'); await window.axios.post(`/api/v1/seguridad/opciones/${o.id}`, { headers: { Authorization: `Bearer ${token}` } }); await this.init(); },
        async reactivateOpcion(o) {
            const token = localStorage.getItem('auth_token');
            await window.axios.post(`/api/v1/seguridad/opciones/${o.id}`, { nombre: o.nombre, ruta: o.ruta, orden: o.orden, estado: 'activo' }, { headers: { Authorization: `Bearer ${token}` } });
            await this.init();
        },

        openPermisoModal() {
            this.errorPermiso = '';
            this.editingPermiso = false;
            this.editPermisoId = null;
            this.permisoForm = { opcion_modulo_id: '', codigo: '', nombre: '', accion: '' };
            this.showPermisoModal = true;
        },

        editPermiso(p) {
            this.errorPermiso = '';
            this.editingPermiso = true;
            this.editPermisoId = p.id;
            this.permisoForm = {
                opcion_modulo_id: p.opcion_modulo_id,
                codigo: p.codigo,
                nombre: p.nombre,
                accion: p.accion,
            };
            this.showPermisoModal = true;
        },

        async savePermiso() {
            this.savingPermiso = true;
            this.errorPermiso = '';
            try {
                const token = localStorage.getItem('auth_token');
                const url = this.editingPermiso ? `/api/v1/seguridad/permisos/${this.editPermisoId}` : '/api/v1/seguridad/permisos';
                const payload = { ...this.permisoForm };
                const { data } = await window.api.actualizar(url, payload, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    this.showPermisoModal = false;
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: this.editingPermiso ? 'Permiso actualizado' : 'Permiso creado', type: 'success' } }));
                    await this.init();
                } else {
                    this.errorPermiso = data.mensaje || 'Error al guardar';
                }
            } catch (e) {
                this.errorPermiso = window.extractError(e, 'Error al guardar permiso');
            } finally {
                this.savingPermiso = false;
            }
        },

        async deletePermiso(p) {
            if (!confirm(`¿Dar de baja el permiso ${p.codigo}?`)) return;
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.post(`/api/v1/seguridad/permisos/${p.id}`, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Permiso desactivado', type: 'success' } }));
                    await this.init();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje || 'Error al desactivar', type: 'error' } }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'Error al desactivar permiso'), type: 'error' } }));
            }
        },
        async reactivatePermiso(p) {
            const token = localStorage.getItem('auth_token');
            await window.axios.post(`/api/v1/seguridad/permisos/${p.id}`, { nombre: p.nombre, accion: p.accion, estado: 'activo' }, { headers: { Authorization: `Bearer ${token}` } });
            await this.init();
        },

        /* ---------- Permisos modal ---------- */
        async editRolePermisos(r) {
            this.rolEditando = r;
            this.rolNombre = r.nombre;
            this.permisosRol = [];
            this.busquedaPermisos = '';
            this.copiarDesdeRolId = '';
            this.showPermisosModal = true;

            try {
                const token = localStorage.getItem('auth_token');
                const h = { headers: { Authorization: `Bearer ${token}` } };
                const [catalogoRes, rolRes] = await Promise.all([
                    window.axios.get('/api/v1/seguridad/permisos', h),
                    window.axios.get(`/api/v1/seguridad/roles/${r.id}/permisos`, h),
                ]);

                const catalogo = catalogoRes.data.data?.data || catalogoRes.data.data || [];
                const group = {};
                catalogo.forEach(p => {
                    const mod = p.opcion_modulo?.modulo?.codigo || 'General';
                    if (!group[mod]) group[mod] = [];
                    group[mod].push(p);
                });
                this.permisosPorModulo = group;
                this.todosPermisos = group;

                const data = rolRes.data;
                if (data.resultado === 'A') {
                    const items = data.data?.permisos || data.data || [];
                    this.permisosRol = items.map(p => p.codigo || p);
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al cargar permisos', type: 'error' } }));
                this.showPermisosModal = false;
            }
        },

        closePermisosModal() {
            this.showPermisosModal = false;
            this.rolEditando = null;
            this.permisosRol = [];
            this.busquedaPermisos = '';
            this.copiarDesdeRolId = '';
        },

        togglePermiso(codigo) {
            const idx = this.permisosRol.indexOf(codigo);
            if (idx === -1) this.permisosRol.push(codigo);
            else this.permisosRol.splice(idx, 1);
        },

        toggleModuloTodos(modKey) {
            const permisos = this.permisosFiltrados[modKey] || [];
            const todos = permisos.map(p => p.codigo);
            const allChecked = todos.every(c => this.permisosRol.includes(c));
            if (allChecked) {
                this.permisosRol = this.permisosRol.filter(c => !todos.includes(c));
            } else {
                todos.forEach(c => { if (!this.permisosRol.includes(c)) this.permisosRol.push(c); });
            }
        },

        isModuloMarcado(modKey) {
            const permisos = this.permisosFiltrados[modKey] || [];
            if (permisos.length === 0) return false;
            return permisos.every(p => this.permisosRol.includes(p.codigo));
        },

        isModuloParcial(modKey) {
            const permisos = this.permisosFiltrados[modKey] || [];
            const checked = permisos.filter(p => this.permisosRol.includes(p.codigo));
            return checked.length > 0 && checked.length < permisos.length;
        },

        conteoModulo(modKey) {
            const permisos = this.permisosFiltrados[modKey] || [];
            const checked = permisos.filter(p => this.permisosRol.includes(p.codigo)).length;
            return `${checked}/${permisos.length}`;
        },

        async copiarDesdeRol() {
            if (!this.copiarDesdeRolId) return;
            if (!confirm('¿Reemplazar los permisos actuales con los del rol seleccionado?')) return;
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.get(`/api/v1/seguridad/roles/${this.copiarDesdeRolId}/permisos`, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    const items = data.data?.permisos || data.data || [];
                    this.permisosRol = items.map(p => p.codigo || p);
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Permisos copiados', type: 'success' } }));
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Error al copiar permisos', type: 'error' } }));
            }
            this.copiarDesdeRolId = '';
        },

        async guardarPermisos() {
            if (!this.rolEditando) return;
            this.savingPermisos = true;
            try {
                const token = localStorage.getItem('auth_token');
                const { data } = await window.axios.post(`/api/v1/seguridad/roles/${this.rolEditando.id}/permisos`, { permisos: this.permisosRol }, { headers: { Authorization: `Bearer ${token}` } });
                if (data.resultado === 'A') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Permisos guardados correctamente', type: 'success' } }));
                    this.closePermisosModal();
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.mensaje || 'Error al guardar', type: 'error' } }));
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: window.extractError(e, 'Error al guardar permisos'), type: 'error' } }));
            } finally { this.savingPermisos = false; }
        },

        get permisosFiltrados() {
            const q = this.busquedaPermisos.toLowerCase().trim();
            if (!q) return this.todosPermisos;
            const result = {};
            Object.entries(this.todosPermisos).forEach(([modKey, permisos]) => {
                const filtered = permisos.filter(p => p.codigo?.toLowerCase().includes(q) || p.nombre?.toLowerCase().includes(q));
                if (filtered.length > 0) result[modKey] = filtered;
            });
            return result;
        },

        get modulosFiltrados() {
            const q = this.busquedaModulos.toLowerCase().trim();
            return this.modulos.filter(m => (!this.estadoModulos || m.estado === this.estadoModulos) && (!q || m.codigo?.toLowerCase().includes(q) || m.nombre?.toLowerCase().includes(q)));
        },

        get modulosActivos() { return this.modulos.filter(m => m.estado === 'activo'); },
        get modulosInactivos() { return this.modulos.filter(m => m.estado !== 'activo'); },

        get opcionesFiltradas() {
            const q = this.busquedaOpciones.toLowerCase().trim();
            return this.opcionesModulo.filter(o => (!this.estadoOpciones || o.estado === this.estadoOpciones) && (!q || o.codigo?.toLowerCase().includes(q) || o.nombre?.toLowerCase().includes(q) || o.modulo?.codigo?.toLowerCase().includes(q) || o.ruta?.toLowerCase().includes(q)));
        },

        get opcionesActivas() { return this.opcionesModulo.filter(o => o.estado === 'activo'); },
        get opcionesInactivas() { return this.opcionesModulo.filter(o => o.estado !== 'activo'); },

        get totalPermisos() {
            return Object.values(this.todosPermisos).reduce((acc, permisos) => acc + permisos.length, 0);
        },

        get totalPermisosActivos() {
            return Object.values(this.todosPermisos).flat().filter(p => p.estado === 'activo').length;
        },

        get totalPermisosInactivos() {
            return Object.values(this.todosPermisos).flat().filter(p => p.estado !== 'activo').length;
        },

        get permisosFiltradosPorModulo() {
            const q = this.busquedaPermisos.toLowerCase().trim();
            const result = {};
            Object.entries(this.todosPermisos).forEach(([modKey, permisos]) => {
                const filtered = permisos.filter(p => (!this.estadoPermisos || p.estado === this.estadoPermisos) && (!q || p.codigo?.toLowerCase().includes(q) || p.nombre?.toLowerCase().includes(q)));
                if (filtered.length) result[modKey] = filtered;
            });
            return result;
        },

        async activarFiltradosModulos() { await this.cambiarEstadoLista(this.modulosFiltrados, 'modulos', 'activo'); },
        async desactivarFiltradosModulos() { await this.cambiarEstadoLista(this.modulosFiltrados, 'modulos', 'inactivo'); },
        async activarFiltradasOpciones() { await this.cambiarEstadoLista(this.opcionesFiltradas, 'opciones', 'activo'); },
        async desactivarFiltradasOpciones() { await this.cambiarEstadoLista(this.opcionesFiltradas, 'opciones', 'inactivo'); },
        async activarFiltradosPermisos() { await this.cambiarEstadoLista(Object.values(this.permisosFiltradosPorModulo).flat(), 'permisos', 'activo'); },
        async desactivarFiltradosPermisos() { await this.cambiarEstadoLista(Object.values(this.permisosFiltradosPorModulo).flat(), 'permisos', 'inactivo'); },

        async cambiarEstadoLista(items, tipo, estado) {
            if (!items.length) return;
            if (!confirm(`¿${estado === 'activo' ? 'Activar' : 'Desactivar'} ${items.length} registros seleccionados?`)) return;
            const token = localStorage.getItem('auth_token');
            for (const item of items) {
                const url = tipo === 'modulos' ? `/api/v1/seguridad/modulos/${item.id}` : tipo === 'opciones' ? `/api/v1/seguridad/opciones/${item.id}` : `/api/v1/seguridad/permisos/${item.id}`;
                const payload = tipo === 'permisos' ? { nombre: item.nombre, accion: item.accion, estado } : tipo === 'modulos' ? { nombre: item.nombre, orden: item.orden, estado } : { nombre: item.nombre, ruta: item.ruta, orden: item.orden, estado };
                await window.axios.post(url, payload, { headers: { Authorization: `Bearer ${token}` } });
            }
            await this.init();
        },
    }
}
</script>
@endsection
