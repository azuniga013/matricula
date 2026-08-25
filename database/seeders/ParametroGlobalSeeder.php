<?php

namespace Database\Seeders;

use App\Models\ParametroGlobal;
use Illuminate\Database\Seeder;

class ParametroGlobalSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            // Datos de la empresa
            ['grupo' => '01', 'codigo' => 'EMPRESA_NOMBRE', 'nombre' => 'Nombre de la institución', 'valor' => 'Cursos San Vicente de Paúl', 'tipo' => 'texto', 'descripcion' => 'Nombre oficial que aparece en encabezados de PDF y Excel'],
            ['grupo' => '01', 'codigo' => 'EMPRESA_DIRECCION', 'nombre' => 'Dirección', 'valor' => 'Col. Las Acacias, San Pedro Sula, Honduras', 'tipo' => 'texto', 'descripcion' => 'Dirección física de la institución'],
            ['grupo' => '01', 'codigo' => 'EMPRESA_TELEFONO', 'nombre' => 'Teléfono', 'valor' => '+504 2550-1234', 'tipo' => 'texto', 'descripcion' => 'Teléfono de contacto'],
            ['grupo' => '01', 'codigo' => 'EMPRESA_CORREO', 'nombre' => 'Correo electrónico', 'valor' => 'info@cursossanvicente.edu', 'tipo' => 'texto', 'descripcion' => 'Correo de contacto institucional'],

            // Formatos
            ['grupo' => '01', 'codigo' => 'FORMATO_FECHA', 'nombre' => 'Formato de fecha', 'valor' => 'd/m/Y', 'tipo' => 'seleccion', 'opciones' => ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y'], 'descripcion' => 'Formato de fecha para reportes y pantallas'],
            ['grupo' => '01', 'codigo' => 'FORMATO_FECHA_HORA', 'nombre' => 'Formato fecha y hora', 'valor' => 'd/m/Y H:i', 'tipo' => 'seleccion', 'opciones' => ['d/m/Y H:i', 'Y-m-d H:i', 'd-m-Y H:i:s'], 'descripcion' => 'Formato de fecha y hora'],

            // Moneda
            ['grupo' => '01', 'codigo' => 'MONEDA_SIMBOLO', 'nombre' => 'Símbolo de moneda', 'valor' => 'L', 'tipo' => 'texto', 'descripcion' => 'Símbolo que precede a los montos'],
            ['grupo' => '01', 'codigo' => 'MONEDA_CODIGO', 'nombre' => 'Código de moneda', 'valor' => 'HNL', 'tipo' => 'texto', 'descripcion' => 'Código ISO de la moneda'],
            ['grupo' => '01', 'codigo' => 'MONEDA_DECIMALES', 'nombre' => 'Decimales de moneda', 'valor' => '2', 'tipo' => 'numero', 'descripcion' => 'Cantidad de decimales en montos'],

            // CSV
            ['grupo' => '01', 'codigo' => 'CSV_SEPARADOR', 'nombre' => 'Separador CSV', 'valor' => ',', 'tipo' => 'seleccion', 'opciones' => [',', ';', '\t', '|'], 'descripcion' => 'Separador de campos para exportación CSV'],
            ['grupo' => '01', 'codigo' => 'CSV_DELIMITADOR', 'nombre' => 'Delimitador de texto CSV', 'valor' => '"', 'tipo' => 'texto', 'descripcion' => 'Carácter que delimita texto en CSV'],

            // Reportes
            ['grupo' => '01', 'codigo' => 'REPORTE_PIE_PAGINA', 'nombre' => 'Pie de página de reportes', 'valor' => 'Documento generado por el sistema de Cursos San Vicente de Paúl', 'tipo' => 'texto', 'descripcion' => 'Texto que aparece al pie de los reportes PDF'],

            // Notificaciones de asistencia
            ['grupo' => 'notificaciones_asistencia', 'codigo' => 'EMAIL_HABILITADO', 'nombre' => 'Email habilitado', 'valor' => 'true', 'tipo' => 'booleano', 'descripcion' => 'Permite el envío de notificaciones de asistencia por correo institucional'],
            ['grupo' => 'notificaciones_asistencia', 'codigo' => 'WHATSAPP_HABILITADO', 'nombre' => 'WhatsApp habilitado', 'valor' => 'false', 'tipo' => 'booleano', 'descripcion' => 'Permite el procesamiento del canal WhatsApp para asistencia'],
            ['grupo' => 'notificaciones_asistencia', 'codigo' => 'WHATSAPP_DRIVER', 'nombre' => 'Driver WhatsApp', 'valor' => 'deshabilitado', 'tipo' => 'seleccion', 'opciones' => ['deshabilitado', 'stub', 'meta_cloud_api'], 'descripcion' => 'Driver configurado para el canal WhatsApp de asistencia'],
            ['grupo' => 'notificaciones_asistencia', 'codigo' => 'WHATSAPP_REMITENTE', 'nombre' => 'Remitente WhatsApp', 'valor' => '', 'tipo' => 'texto', 'descripcion' => 'Identificador o número remitente del proveedor oficial de WhatsApp'],
            ['grupo' => 'notificaciones_asistencia', 'codigo' => 'WHATSAPP_PLANTILLA', 'nombre' => 'Plantilla WhatsApp', 'valor' => 'asistencia_basica', 'tipo' => 'texto', 'descripcion' => 'Plantilla oficial aprobada para enviar faltas o tardanzas por WhatsApp'],

            // Bitácoras
            ['grupo' => 'bitacoras', 'codigo' => 'AUDITORIA_CENTRAL_HABILITADA', 'nombre' => 'Auditoría central habilitada', 'valor' => 'true', 'tipo' => 'booleano', 'descripcion' => 'Activa o desactiva la tabla bitacora_auditoria.'],
            ['grupo' => 'bitacoras', 'codigo' => 'BITACORA_PETICIONES_HABILITADA', 'nombre' => 'Bitácora de peticiones habilitada', 'valor' => 'true', 'tipo' => 'booleano', 'descripcion' => 'Activa o desactiva la tabla bitacora_peticiones.'],
            ['grupo' => 'bitacoras', 'codigo' => 'BITACORA_SEGURIDAD_HABILITADA', 'nombre' => 'Bitácora de seguridad habilitada', 'valor' => 'true', 'tipo' => 'booleano', 'descripcion' => 'Activa o desactiva la tabla bitacora_seguridad.'],
            ['grupo' => 'bitacoras', 'codigo' => 'BITACORA_CORREOS_HABILITADA', 'nombre' => 'Bitácora de correos habilitada', 'valor' => 'true', 'tipo' => 'booleano', 'descripcion' => 'Activa o desactiva la tabla bitacora_correos.'],
            ['grupo' => 'bitacoras', 'codigo' => 'RETENCION_AUDITORIA_DIAS', 'nombre' => 'Retención auditoría central (días)', 'valor' => '180', 'tipo' => 'numero', 'descripcion' => 'Cantidad de días a conservar en bitacora_auditoria antes de purgar.'],
            ['grupo' => 'bitacoras', 'codigo' => 'RETENCION_PETICIONES_DIAS', 'nombre' => 'Retención bitácora de peticiones (días)', 'valor' => '30', 'tipo' => 'numero', 'descripcion' => 'Cantidad de días a conservar en bitacora_peticiones antes de purgar.'],
            ['grupo' => 'bitacoras', 'codigo' => 'RETENCION_SEGURIDAD_DIAS', 'nombre' => 'Retención bitácora de seguridad (días)', 'valor' => '365', 'tipo' => 'numero', 'descripcion' => 'Cantidad de días a conservar en bitacora_seguridad antes de purgar.'],
            ['grupo' => 'bitacoras', 'codigo' => 'RETENCION_CORREOS_DIAS', 'nombre' => 'Retención bitácora de correos (días)', 'valor' => '90', 'tipo' => 'numero', 'descripcion' => 'Cantidad de días a conservar en bitacora_correos antes de purgar.'],
        ];

        foreach ($parametros as $p) {
            ParametroGlobal::firstOrCreate(
                ['grupo' => $p['grupo'], 'codigo' => $p['codigo']],
                $p
            );
        }
    }
}
