<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de módulos RBAC
    |--------------------------------------------------------------------------
    |
    | Cada módulo define sus opciones y permisos estándar.
    | El RegistroPermisosService crea permisos como: <modulo>.<accion>
    | Acciones estándar: consultar, crear, modificar, eliminar, aprobar,
    |                     anular, imprimir, exportar, importar, asignar, configurar
    |
    */

    'modulos' => [

        'seguridad' => [
            'nombre' => 'Seguridad',
            'orden' => 1,
            'opciones' => [
                'seguridad.usuarios' => ['nombre' => 'Usuarios', 'ruta' => '/seguridad/usuarios'],
                'seguridad.roles' => ['nombre' => 'Roles', 'ruta' => '/seguridad/roles'],
                'seguridad.permisos' => ['nombre' => 'Permisos', 'ruta' => '/seguridad/permisos'],
                'seguridad.modulos' => ['nombre' => 'Módulos', 'ruta' => '/seguridad/modulos'],
                'seguridad.auditoria' => ['nombre' => 'Auditoría', 'ruta' => '/seguridad/auditoria'],
                'seguridad.sesiones' => ['nombre' => 'Sesiones', 'ruta' => '/seguridad/sesiones'],
                'seguridad.flujos-matricula' => ['nombre' => 'Flujo Matrícula', 'ruta' => '/seguridad/flujos-matricula'],
                'seguridad.parametros' => ['nombre' => 'Parámetros Globales', 'ruta' => '/seguridad/parametros'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'eliminar', 'configurar'],
        ],

        'catalogos_academicos' => [
            'nombre' => 'Catálogos Académicos',
            'orden' => 2,
            'opciones' => [
                'catalogos.sucursales' => ['nombre' => 'Sucursales', 'ruta' => '/catalogos/sucursales'],
                'catalogos.departamentos' => ['nombre' => 'Departamentos', 'ruta' => '/catalogos/departamentos'],
                'catalogos.planes' => ['nombre' => 'Planes de Estudio', 'ruta' => '/catalogos/planes'],
                'catalogos.niveles' => ['nombre' => 'Niveles', 'ruta' => '/catalogos/niveles'],
                'catalogos.modalidades' => ['nombre' => 'Modalidades', 'ruta' => '/catalogos/modalidades'],
                'catalogos.horarios' => ['nombre' => 'Horarios', 'ruta' => '/catalogos/horarios'],
                'catalogos.docentes' => ['nombre' => 'Docentes', 'ruta' => '/catalogos/docentes'],
                'catalogos.aulas' => ['nombre' => 'Aulas', 'ruta' => '/catalogos/aulas'],
                'catalogos.conceptos' => ['nombre' => 'Conceptos de Pago', 'ruta' => '/catalogos/conceptos'],
                'catalogos.metodos' => ['nombre' => 'Métodos de Pago', 'ruta' => '/catalogos/metodos'],
                'catalogos.planes-cobro' => ['nombre' => 'Planes de Cobro', 'ruta' => '/catalogos/planes-cobro'],
                'catalogos.grupos-whatsapp' => ['nombre' => 'Grupos WhatsApp', 'ruta' => '/catalogos/grupos-whatsapp'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'eliminar'],
        ],

        'ofertas' => [
            'nombre' => 'Ofertas y Cupos',
            'orden' => 3,
            'opciones' => [
                'ofertas.academicas' => ['nombre' => 'Ofertas Académicas', 'ruta' => '/ofertas/academicas'],
                'ofertas.periodos' => ['nombre' => 'Períodos', 'ruta' => '/ofertas/periodos'],
                'ofertas.monitor' => ['nombre' => 'Monitor de Cupos', 'ruta' => '/ofertas/monitor'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'eliminar', 'aprobar'],
        ],

        'estudiantes' => [
            'nombre' => 'Estudiantes',
            'orden' => 4,
            'opciones' => [
                'estudiantes.registro' => ['nombre' => 'Registro', 'ruta' => '/estudiantes/registro'],
                'estudiantes.ficha' => ['nombre' => 'Ficha Integral', 'ruta' => '/estudiantes/ficha'],
                'estudiantes.accesos' => ['nombre' => 'Accesos', 'ruta' => '/estudiantes/accesos'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'aprobar'],
        ],

        'matriculas' => [
            'nombre' => 'Matrículas',
            'orden' => 5,
            'opciones' => [
                'matriculas.gestion' => ['nombre' => 'Gestión', 'ruta' => '/matriculas/gestion'],
                'matriculas.historial' => ['nombre' => 'Historial', 'ruta' => '/matriculas/historial'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'eliminar', 'aprobar', 'anular'],
        ],

        'pagos' => [
            'nombre' => 'Pagos',
            'orden' => 6,
            'opciones' => [
                'pagos.comprobantes' => ['nombre' => 'Comprobantes', 'ruta' => '/pagos/comprobantes'],
                'pagos.aprobacion' => ['nombre' => 'Aprobación', 'ruta' => '/pagos/aprobacion'],
                'pagos.obligaciones' => ['nombre' => 'Obligaciones', 'ruta' => '/pagos/obligaciones'],
                'pagos.enlaces-pago' => ['nombre' => 'Enlaces de Pago', 'ruta' => '/pagos/enlaces-pago'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'aprobar', 'anular'],
        ],

        'caja' => [
            'nombre' => 'Caja',
            'orden' => 7,
            'opciones' => [
                'caja.sesiones' => ['nombre' => 'Sesiones', 'ruta' => '/caja/sesiones'],
                'caja.recibos' => ['nombre' => 'Recibos', 'ruta' => '/caja/recibos'],
                'caja.cierre' => ['nombre' => 'Cierre', 'ruta' => '/caja/cierre'],
                'caja.reversion' => ['nombre' => 'Reversión', 'ruta' => '/caja/reversion'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'aprobar', 'anular', 'imprimir'],
        ],

        'calificaciones' => [
            'nombre' => 'Calificaciones',
            'orden' => 8,
            'opciones' => [
                'calificaciones.registro' => ['nombre' => 'Registro', 'ruta' => '/calificaciones/registro'],
                'calificaciones.asistencia' => ['nombre' => 'Asistencia', 'ruta' => '/calificaciones/asistencia'],
                'calificaciones.historial' => ['nombre' => 'Historial', 'ruta' => '/calificaciones/historial'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'aprobar'],
        ],

        'asistencias' => [
            'nombre' => 'Asistencias',
            'orden' => 6,
            'opciones' => [
                'asistencias.lista' => ['nombre' => 'Pasar Lista', 'ruta' => '/asistencias/lista'],
                'asistencias.reporte' => ['nombre' => 'Reporte', 'ruta' => '/asistencias/reporte'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar'],
        ],

        'inventario' => [
            'nombre' => 'Inventario',
            'orden' => 9,
            'opciones' => [
                'inventario.libros' => ['nombre' => 'Libros', 'ruta' => '/inventario/libros'],
                'inventario.stock' => ['nombre' => 'Stock', 'ruta' => '/inventario/stock'],
                'inventario.ventas' => ['nombre' => 'Ventas', 'ruta' => '/inventario/ventas'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'aprobar'],
        ],

        'reportes' => [
            'nombre' => 'Reportes',
            'orden' => 10,
            'opciones' => [
                'reportes.academicos' => ['nombre' => 'Académicos', 'ruta' => '/reportes/academicos'],
                'reportes.financieros' => ['nombre' => 'Financieros', 'ruta' => '/reportes/financieros'],
                'reportes.caja' => ['nombre' => 'Caja', 'ruta' => '/reportes/caja'],
                'reportes.inventario' => ['nombre' => 'Inventario', 'ruta' => '/reportes/inventario'],
            ],
            'acciones' => ['consultar', 'exportar'],
        ],

        'configuracion' => [
            'nombre' => 'Configuración',
            'orden' => 11,
            'opciones' => [
                'configuracion.pagos' => ['nombre' => 'Proveedores de Pago', 'ruta' => '/configuracion/pagos'],
            ],
            'acciones' => ['consultar', 'modificar'],
        ],

        'flujos_matricula' => [
            'nombre' => 'Flujos de Matrícula',
            'orden' => 12,
            'opciones' => [
                'flujos_matricula.configuracion' => ['nombre' => 'Configuraciones de Flujo', 'ruta' => '/seguridad/flujos-matricula'],
            ],
            'acciones' => ['consultar', 'crear', 'modificar', 'eliminar', 'configurar'],
        ],

    ],

];
