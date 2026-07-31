<?php

namespace App\Services;

use App\Models\ParametroGlobal;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExportacionReportesService
{
    private function empresa(): array
    {
        return [
            'nombre'       => ParametroGlobal::obtener('EMPRESA_NOMBRE') ?: 'Cursos San Vicente de Paúl',
            'direccion'    => ParametroGlobal::obtener('EMPRESA_DIRECCION') ?: '',
            'telefono'     => ParametroGlobal::obtener('EMPRESA_TELEFONO') ?: '',
            'correo'       => ParametroGlobal::obtener('EMPRESA_CORREO') ?: '',
            'moneda'       => ParametroGlobal::obtener('MONEDA_SIMBOLO') ?: 'L',
            'formato_fecha'=> ParametroGlobal::obtener('FORMATO_FECHA') ?: 'd/m/Y',
            'pie'          => ParametroGlobal::obtener('REPORTE_PIE_PAGINA') ?: '',
        ];
    }

    public function generarExcel(array $filas, string $tituloReporte, ?string $filtrosResumen = null, ?string $usuarioGenerador = null): string
    {
        $empresa = $this->empresa();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($tituloReporte, 0, 31));

        $colTotal = $filas ? count((array) $filas[0]) : 1;
        $ultimaCol = $this->colLetra($colTotal);

        // Encabezado institucional
        $sheet->mergeCells("A1:{$ultimaCol}1");
        $sheet->setCellValue('A1', $empresa['nombre']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$ultimaCol}2");
        $sheet->setCellValue('A2', trim($empresa['direccion'] . ' · Tel: ' . $empresa['telefono'] . ' · ' . $empresa['correo'], ' ·'));
        $sheet->getStyle('A2')->getFont()->setSize(9);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Título del reporte
        $sheet->mergeCells("A3:{$ultimaCol}3");
        $sheet->setCellValue('A3', $tituloReporte);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Filtros y metadatos
        $filaInicio = 4;
        if ($filtrosResumen) {
            $sheet->mergeCells("A{$filaInicio}:{$ultimaCol}{$filaInicio}");
            $sheet->setCellValue("A{$filaInicio}", 'Filtros: ' . $filtrosResumen);
            $sheet->getStyle("A{$filaInicio}")->getFont()->setSize(9)->setItalic(true);
            $filaInicio++;
        }
        $fechaGen = now()->format($empresa['formato_fecha'] . ' H:i');
        $sheet->mergeCells("A{$filaInicio}:{$ultimaCol}{$filaInicio}");
        $sheet->setCellValue("A{$filaInicio}", "Generado: {$fechaGen}" . ($usuarioGenerador ? " · Usuario: {$usuarioGenerador}" : ''));
        $sheet->getStyle("A{$filaInicio}")->getFont()->setSize(9)->setItalic(true);
        $filaInicio++;

        // Espacio
        $filaInicio++;

        // Encabezados de columnas
        $headers = $filas ? array_keys((array) $filas[0]) : [];
        $filaHeaders = $filaInicio;
        foreach ($headers as $i => $h) {
            $col = $this->colLetra($i + 1);
            $sheet->setCellValue("{$col}{$filaHeaders}", $h);
        }
        $sheet->getStyle("A{$filaHeaders}:{$ultimaCol}{$filaHeaders}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaHeaders}:{$ultimaCol}{$filaHeaders}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $sheet->getStyle("A{$filaHeaders}:{$ultimaCol}{$filaHeaders}")->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$filaHeaders}:{$ultimaCol}{$filaHeaders}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Datos
        $filaDatos = $filaHeaders + 1;
        $camposMonetarios = ['monto', 'total_monto', 'total', 'monto_total', 'saldo', 'monto_aplicado'];
        $decimales = (int) (ParametroGlobal::obtener('MONEDA_DECIMALES') ?? 2);
        foreach ($filas as $fila) {
            foreach ($headers as $i => $h) {
                $col = $this->colLetra($i + 1);
                $valor = data_get($fila, $h);
                if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
                    $sheet->setCellValue("{$col}{$filaDatos}", $valor);
                } elseif (in_array($h, $camposMonetarios) && is_numeric($valor)) {
                    $sheet->setCellValue("{$col}{$filaDatos}", (float) $valor);
                    $sheet->getStyle("{$col}{$filaDatos}")->getNumberFormat()->setFormatCode(
                        $this->formatoMonedaExcel($decimales)
                    );
                } else {
                    $sheet->setCellValue("{$col}{$filaDatos}", $valor);
                }
            }
            $filaDatos++;
        }

        // Bordes
        $filaFinal = $filaDatos - 1;
        if ($filaFinal >= $filaHeaders) {
            $sheet->getStyle("A{$filaHeaders}:{$ultimaCol}{$filaFinal}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto-ancho
        for ($i = 0; $i < $colTotal; $i++) {
            $sheet->getColumnDimension($this->colLetra($i + 1))->setAutoSize(true);
        }

        // Pie de página
        $filaPie = $filaFinal + 2;
        if ($empresa['pie']) {
            $sheet->mergeCells("A{$filaPie}:{$ultimaCol}{$filaPie}");
            $sheet->setCellValue("A{$filaPie}", $empresa['pie']);
            $sheet->getStyle("A{$filaPie}")->getFont()->setSize(8)->setItalic(true);
            $sheet->getStyle("A{$filaPie}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmp);

        return $tmp;
    }

    private function colLetra(int $n): string
    {
        $letra = '';
        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $letra = chr(65 + $mod) . $letra;
            $n = intdiv($n - $mod, 26);
        }
        return $letra;
    }

    private function formatoMonedaExcel(int $decimales): string
    {
        $moneda = ParametroGlobal::obtener('MONEDA_SIMBOLO') ?: 'L';
        $cero = str_repeat('0', $decimales);
        return '_-' . $moneda . '* #' . ($decimales > 0 ? ',0.' . $cero : '') . '_-;-' . $moneda . '* #' . ($decimales > 0 ? ',0.' . $cero : '') . '_-;_-' . $moneda . '* "-"??_-;_-@_-';
    }

    public function empresaParaPdf(): array
    {
        return $this->empresa();
    }
}