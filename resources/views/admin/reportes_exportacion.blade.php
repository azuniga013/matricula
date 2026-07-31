<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 0; padding: 20px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; }
        .header h1 { font-size: 15px; color: #1e3a5f; margin: 0 0 4px 0; }
        .header .info { font-size: 8px; color: #6b7280; margin: 2px 0; }
        .reporte-titulo { font-size: 13px; font-weight: bold; color: #111827; text-align: center; margin: 12px 0 4px 0; }
        .filtros { font-size: 8px; color: #6b7280; text-align: center; margin-bottom: 6px; }
        .metadata { font-size: 8px; color: #9ca3af; text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
        th { background: #1e3a5f; color: #fff; font-weight: bold; font-size: 8px; text-align: center; text-transform: uppercase; }
        td { font-size: 8px; }
        tr:nth-child(even) { background: #f9fafb; }
        .totales { margin-top: 8px; font-size: 9px; font-weight: bold; text-align: right; }
        .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $empresa['nombre'] ?? 'Cursos San Vicente de Paúl' }}</h1>
        @if(!empty($empresa['direccion']))<div class="info">{{ $empresa['direccion'] }}</div>@endif
        @if(!empty($empresa['telefono']) || !empty($empresa['correo']))
        <div class="info">@if(!empty($empresa['telefono']))Tel: {{ $empresa['telefono'] }}@endif @if(!empty($empresa['correo']))· {{ $empresa['correo'] }}@endif</div>
        @endif
    </div>

    <div class="reporte-titulo">{{ $reporte }}</div>
    @if(!empty($filtros))<div class="filtros"><strong>Filtros:</strong> {{ $filtros }}</div>@endif
    <div class="metadata">
        Generado: {{ $generado_en->format($empresa['formato_fecha'] ?? 'd/m/Y') . ' H:i' }}
        @if(!empty($usuario)) · Usuario: {{ $usuario }}@endif
    </div>

    @if(empty($filas[0]))
        <p style="text-align:center; padding:20px; color:#9ca3af;">No hay datos para este reporte con los filtros seleccionados.</p>
    @else
    <table>
        <thead>
            <tr>
                @foreach(array_keys((array) $filas[0]) as $columna)
                    <th>{{ ucfirst(str_replace(['_', '.'], ' ', $columna)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $fila)
                <tr>
                    @foreach((array) $fila as $valor)
                        <td>{{ is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $moneda = $empresa['moneda'] ?? 'L';
        $colsMonetarias = ['total_monto', 'monto', 'monto_total', 'total', 'saldo'];
        $filaTotales = [];
        foreach (array_keys((array) $filas[0]) as $col) {
            if (in_array($col, $colsMonetarias)) {
                $suma = 0;
                foreach ($filas as $f) { $v = data_get($f, $col); if (is_numeric($v)) $suma += $v; }
                $filaTotales[$col] = $moneda . ' ' . number_format($suma, 2);
            }
        }
    @endphp
    @if(!empty($filaTotales))
    <div class="totales">
        @foreach($filaTotales as $col => $val)<div>{{ ucfirst(str_replace('_', ' ', $col)) }}: {{ $val }}</div>@endforeach
    </div>
    @endif
    @endif

    <div class="footer">
        @if(!empty($empresa['pie'])){{ $empresa['pie'] }} · @endif
        Cursos San Vicente de Paúl · Sistema de Gestión Académica
    </div>
</body>
</html>
