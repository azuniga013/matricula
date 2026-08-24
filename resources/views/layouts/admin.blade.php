<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Administrativo' }} — Cursos San Vicente de Paúl</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="adminApp()" x-init="init()">

    {{-- Login redirect if no token --}}
    <template x-if="!isLoggedIn">
        <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-brand-900 to-slate-900">
            <div class="text-center">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-white/20 border-t-white mx-auto mb-4"></div>
                <p class="text-white/60 text-sm">Cargando...</p>
            </div>
        </div>
    </template>

    <template x-if="isLoggedIn">
        <div class="flex min-h-screen">
            {{-- Sidebar overlay (mobile) --}}
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

            {{-- Sidebar --}}
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-[260px] bg-gradient-to-b from-slate-900 via-brand-950 to-slate-900 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto flex flex-col">
                {{-- Logo --}}
                <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-sm font-bold text-white truncate">Cursos San Vicente</h1>
                        <p class="text-[11px] text-white/40 truncate">Panel Administrativo</p>
                    </div>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <p class="px-3 mb-2 text-[10px] font-semibold text-white/30 uppercase tracking-widest">Principal</p>

                    <a href="/admin" :class="isActive('/admin') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                        Dashboard
                    </a>

                    <p x-show="permAny(['catalogos.sucursales.consultar','catalogos.departamentos.consultar','catalogos.niveles.consultar','catalogos.modalidades.consultar','catalogos.horarios.consultar','catalogos.docentes.consultar','catalogos.aulas.consultar','catalogos.planes.consultar'])" class="px-3 pt-4 mb-2 text-[10px] font-semibold text-white/30 uppercase tracking-widest">Académico</p>

                    <a x-show="permAny(['catalogos.sucursales.consultar','catalogos.departamentos.consultar','catalogos.niveles.consultar','catalogos.modalidades.consultar','catalogos.horarios.consultar','catalogos.docentes.consultar','catalogos.aulas.consultar','catalogos.planes.consultar'])" href="/admin/catalogos" :class="isActive('/admin/catalogos') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                        Catálogos
                    </a>

                    <a x-show="permAny(['ofertas.academicas.consultar','ofertas.periodos.consultar','ofertas.monitor.consultar'])" href="/admin/ofertas" :class="isActive('/admin/ofertas') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
                        Ofertas y Cupos
                    </a>

                    <a x-show="permAny(['ofertas.monitor.consultar'])" href="/admin/monitor" :class="isActive('/admin/monitor') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>
                        Monitor de Cupos
                    </a>

                    <a x-show="api.hasPermission('catalogos.planes-cobro.consultar')" href="/admin/planes-cobro" :class="isActive('/admin/planes-cobro') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 0 4.5 6h.75m13.5 0h.75a.75.75 0 0 0 .75-.75V4.5M3.75 9.75v.75a.75.75 0 0 0 .75.75h.75m13.5 0h.75a.75.75 0 0 0 .75-.75v-.75M4.5 13.5v.75a.75.75 0 0 0 .75.75h.75" stroke-linecap="round" /></svg>
                        Planes de Cobro
                    </a>

                    <a x-show="permAny(['estudiantes.registro.consultar','estudiantes.ficha.consultar','estudiantes.accesos.consultar'])" href="/admin/estudiantes" :class="isActive('/admin/estudiantes') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                        Estudiantes
                    </a>

                    <a x-show="permAny(['matriculas.gestion.consultar','matriculas.historial.consultar'])" href="/admin/matriculas" :class="isActive('/admin/matriculas') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        Matrícula
                    </a>

                    <a x-show="permAny(['calificaciones.registro.consultar','calificaciones.historial.consultar'])" href="/admin/calificaciones" :class="isActive('/admin/calificaciones') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                        Calificaciones
                    </a>

                    <a x-show="permAny(['asistencias.lista.consultar','asistencias.lista.crear'])" href="/admin/mis-grupos" :class="isActive('/admin/mis-grupos') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197M6 18.719a5.971 5.971 0 0 1 .941-3.197m0 0a3 3 0 0 1 4.682-2.72 9.094 9.094 0 0 1 4.636 0 3 3 0 0 1 4.682 2.72M6.94 15.522a3 3 0 0 0 4.682-2.72 9.094 9.094 0 0 0-4.636 0 3 3 0 0 0-.046 2.72ZM12 12a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" /></svg>
                        Mis Horarios
                    </a>

                    <a x-show="permAny(['asistencias.lista.consultar','asistencias.lista.crear'])" href="/admin/asistencias" :class="isActive('/admin/asistencias') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043 3.745 3.745 0 0 1-3.068 1.593c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" /></svg>
                        Asistencias
                    </a>

                    <a x-show="permAny(['inventario.libros.consultar','inventario.stock.consultar','inventario.ventas.consultar'])" href="/admin/inventario" :class="isActive('/admin/inventario') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                        Inventario
                    </a>

                    <p x-show="permAny(['pagos.consultar','caja.sesiones.consultar'])" class="px-3 pt-4 mb-2 text-[10px] font-semibold text-white/30 uppercase tracking-widest">Financiero</p>

                    <a x-show="permAny(['pagos.consultar','pagos.comprobantes.consultar','pagos.aprobacion.consultar'])" href="/admin/pagos" :class="isActive('/admin/pagos') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        Obligaciones
                    </a>

                    <a x-show="permAny(['caja.sesiones.consultar','caja.recibos.consultar','caja.cierre.consultar'])" href="/admin/caja" :class="isActive('/admin/caja') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015A2.993 2.993 0 0 0 20.25 9.75c.896 0 1.7-.393 2.25-1.015" /></svg>
                        Caja
                    </a>

                    <p x-show="permAny(['reportes.academicos.consultar','reportes.financieros.consultar','reportes.caja.consultar','reportes.inventario.consultar'])" class="px-3 pt-4 mb-2 text-[10px] font-semibold text-white/30 uppercase tracking-widest">Reportes</p>

                    <a x-show="permAny(['reportes.academicos.consultar','reportes.financieros.consultar','reportes.caja.consultar','reportes.inventario.consultar'])" href="/admin/reportes" :class="isActive('/admin/reportes') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                        Reportes
                    </a>

                    <p x-show="permAny(['seguridad.usuarios.consultar','seguridad.roles.consultar','seguridad.permisos.consultar','seguridad.auditoria.consultar'])" class="px-3 pt-4 mb-2 text-[10px] font-semibold text-white/30 uppercase tracking-widest">Sistema</p>

                    <a x-show="permAny(['seguridad.usuarios.consultar','seguridad.roles.consultar','seguridad.permisos.consultar','seguridad.auditoria.consultar'])" href="/admin/seguridad" :class="isActive('/admin/seguridad') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        Seguridad
                    </a>

                    <a x-show="permAny(['seguridad.parametros.consultar','seguridad.parametros.modificar'])" href="/admin/parametros-globales" :class="isActive('/admin/parametros-globales') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 1.985c.324-.253.736-.344 1.13-.21l4.954 1.63a1.5 1.5 0 0 1 .477 2.511l-3.185 2.558a.75.75 0 0 1-.901-.004l-4.94-3.991a.75.75 0 0 1 .002-1.158l3.46-2.726Zm-3.336 4.962a.75.75 0 0 1 .918.107l4.94 3.991a.75.75 0 0 1-.002 1.158l-3.46 2.726a1.5 1.5 0 0 1-1.13.21l-4.954-1.63a1.5 1.5 0 0 1-.477-2.511l3.185-2.558Z" /></svg>
                        Parámetros Globales
                    </a>

                    <a x-show="permAny(['configuracion.pagos.consultar','configuracion.pagos.modificar'])" href="/admin/proveedores-pago" :class="isActive('/admin/proveedores-pago') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                        Proveedores Pago
                    </a>

                    <a x-show="permAny(['distribucion_apk.consultar','distribucion_apk.crear','distribucion_apk.modificar'])" href="/admin/apk-docentes" :class="isActive('/admin/apk-docentes') ? 'active' : ''" class="sidebar-item text-white/70">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3.75m0 12.75 4.5-4.5M12 16.5l-4.5-4.5M3.75 18.75v.75A2.25 2.25 0 0 0 6 21.75h12a2.25 2.25 0 0 0 2.25-2.25v-.75" /></svg>
                        APK Docentes
                    </a>
                </nav>

                {{-- User footer --}}
                <div class="border-t border-white/10 p-3">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-sm font-bold" x-text="userName.charAt(0).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate" x-text="userName"></p>
                            <p class="text-[11px] text-white/40 truncate" x-text="userEmail"></p>
                        </div>
                        <button @click="logout()" class="text-white/30 hover:text-white transition" title="Cerrar sesión">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                        </button>
                    </div>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="flex-1 flex flex-col min-w-0">
                {{-- Top bar --}}
                <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-gray-200">
                    <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                            </button>
                            <span class="hidden sm:inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200">
                                ID: {{ request()->route()?->getName() ?? request()->path() }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <template x-if="sessionCountdown">
                                <span class="hidden sm:inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200">
                                    Expira en <span class="ml-1 font-mono" x-text="sessionCountdown"></span>
                                </span>
                            </template>
                            <div class="hidden sm:flex items-center gap-2 text-xs text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                <span x-text="currentDate"></span>
                            </div>
                        </div>
                    </div>
                </header>

                {{-- Page content --}}
                <main class="flex-1 p-4 sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Identificador</p>
                            <p class="font-mono text-[11px] text-slate-500" x-text="window.location.pathname"></p>
                        </div>
                    </div>
                    @yield('content')
                </main>
            </div>
        </div>
    </template>

    {{-- Toast notifications --}}
    <div x-data="toast()" x-on:show-toast.window="addToast($event.detail)" class="fixed top-20 right-4 z-[100] space-y-3" style="max-width: 380px;">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-8 scale-95" x-transition:enter-end="opacity-100 translate-x-0 scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-8" :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : toast.type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-red-50 border-red-200 text-red-800'" class="border rounded-xl shadow-lg px-4 py-3 flex items-start space-x-3">
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </template>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium" x-text="toast.message"></p>
                </div>
                <button @click="removeToast(toast.id)" class="flex-shrink-0 text-current opacity-40 hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </template>
    </div>

    <script>
        window.APP_TIMEZONE = @json(config('app.timezone'));
        window.formatDateLocal = function(input, options = {}) {
            if (!input) return '-';
            const d = new Date(input);
            if (isNaN(d.getTime())) return '-';
            const hasTime = Object.prototype.hasOwnProperty.call(options, 'hour') || Object.prototype.hasOwnProperty.call(options, 'minute') || Object.prototype.hasOwnProperty.call(options, 'second');
            if (!hasTime) {
                const parts = new Intl.DateTimeFormat('es-HN', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', day: '2-digit', month: '2-digit', year: 'numeric' }).formatToParts(d);
                const day = parts.find(p => p.type === 'day')?.value || '00';
                const month = parts.find(p => p.type === 'month')?.value || '00';
                const year = parts.find(p => p.type === 'year')?.value || '0000';
                return `${day}-${month}-${year}`;
            }
            return new Intl.DateTimeFormat('es-HN', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', ...options }).format(d);
        };
        window.formatDateTimeLocal = function(input) {
            return window.formatDateLocal(input, { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        };
        window.toLocalDateInput = function(input = new Date()) {
            const d = new Date(input);
            if (isNaN(d.getTime())) return '';
            const parts = new Intl.DateTimeFormat('en-CA', { timeZone: window.APP_TIMEZONE || 'America/Tegucigalpa', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(d);
            const year = parts.find(p => p.type === 'year')?.value || '';
            const month = parts.find(p => p.type === 'month')?.value || '';
            const day = parts.find(p => p.type === 'day')?.value || '';
            return `${year}-${month}-${day}`;
        };

        window.extractError = function(err, fallback) {
            const body = err?.response?.data;
            return body?.mensaje || body?.mensaje_usuario || body?.message || body?.error || 
                (body?.errores ? Object.values(body.errores).flat().join(', ') : null) || 
                fallback || 'Error inesperado';
        };
        window.extractErrorCode = function(err) {
            return err?.response?.data?.codigo_error || null;
        };

        function adminApp() {
            return {
                sidebarOpen: false,
                isLoggedIn: false,
                userName: '',
                userEmail: '',
                currentDate: new Date().toLocaleDateString('es-HN', { year: 'numeric', month: 'long', day: 'numeric' }),
                sessionExpiresAt: localStorage.getItem('auth_token_expires_at') || null,
                sessionCountdown: '',
                countdownTimer: null,

                perm: (codigo) => window.api?.hasPermission(codigo) || false,
                permAny: (codigos) => window.api?.hasAnyPermission(codigos) || false,

                async init() {
                    try {
                        const token = localStorage.getItem('auth-token') || localStorage.getItem('auth_token');
                        if (!token || !window.api) throw new Error('No autenticado');

                        const sessionUser = await window.api.fetchUser();

                        this.isLoggedIn = true;
                        this.userName = sessionUser.nombre;
                        this.userEmail = sessionUser.email;
                        window.api_user = sessionUser;
                        window.api_permisos = sessionUser.permisos || [];
                        if (window.api) {
                            window.api.user = sessionUser;
                            window.api.permisos = sessionUser.permisos || [];
                        }
                        this.startSessionCountdown();
                    } catch (e) {
                        window.location.href = '/login';
                    }
                },

                isActive(path) {
                    return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
                },

                async logout() {
                    try {
                        await window.api.logout();
                    } catch (e) {}
                    window.location.href = '/login';
                },

                startSessionCountdown() {
                    if (this.countdownTimer) clearInterval(this.countdownTimer);
                    if (!this.sessionExpiresAt) {
                        this.sessionCountdown = '';
                        return;
                    }
                    const update = () => {
                        const expires = new Date(this.sessionExpiresAt);
                        const diff = expires.getTime() - Date.now();
                        if (Number.isNaN(expires.getTime()) || diff <= 0) {
                            window.handleAuthExpired('admin');
                            return;
                        }
                        const mins = Math.floor(diff / 60000);
                        const secs = Math.floor((diff % 60000) / 1000);
                        this.sessionCountdown = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                    };
                    update();
                    this.countdownTimer = setInterval(update, 1000);
                }
            }
        }

        function toast() {
            return {
                toasts: [],
                addToast(detail) {
                    const id = Date.now();
                    this.toasts.push({ id, message: detail.message, type: detail.type || 'success', show: true });
                    setTimeout(() => this.removeToast(id), 4000);
                },
                removeToast(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }
            }
        }
    </script>
    @yield('scripts')
</body>
</html>
