<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo #{{ $recibo->numero_recibo }} — Cursos San Vicente de Paul</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header { text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px dashed #000; }
        .header h1 { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #333; margin-top: 2px; }
        .encabezado { margin: 8px 0 10px; }
        .encabezado-titulo { text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; color: #333; }
        .encabezado-bloque { border: 1px solid #000; padding: 6px 8px; }
        .numero-recibo { text-align: center; font-size: 22px; font-weight: bold; margin: 8px 0; }
        .info { width: 100%; margin: 8px 0; }
        .info td { padding: 2px 0; vertical-align: top; }
        .info .label { color: #555; width: 35%; }
        .info .value { font-weight: bold; }
        .linea { border-top: 1px dashed #000; margin: 8px 0; }
        .titulo-seccion { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #333; margin: 8px 0 4px; }
        .detalle-item { display: flex; justify-content: space-between; font-size: 11px; padding: 2px 0; }
        .detalle-item .d-label { color: #555; }
        .detalle-item .d-value { font-weight: bold; }
        .detalle-lista { width: 100%; font-size: 11px; margin: 4px 0; }
        .detalle-lista th { text-align: left; color: #555; font-weight: normal; font-size: 10px; padding: 2px 4px 2px 0; border-bottom: 1px dotted #ccc; }
        .detalle-lista td { padding: 2px 4px 2px 0; }
        .detalle-lista .monto-col { text-align: right; }
        .monto-final { text-align: right; font-size: 16px; font-weight: bold; margin: 8px 0; }
        .footer { text-align: center; margin-top: 12px; padding-top: 8px; border-top: 2px dashed #000; font-size: 10px; color: #555; }
        .estado { text-align: center; margin: 6px 0; }
        .estado .badge { display: inline-block; padding: 2px 10px; border: 1px solid #000; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .estado .anulado { border-color: #c00; color: #c00; }
        .motivo { margin: 8px 0; padding: 6px; background: #fef2f2; border: 1px solid #fecaca; font-size: 10px; color: #991b1b; }
        .reimpresion { text-align: center; font-size: 9px; color: #888; margin-top: 4px; }
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { margin: 0; padding: 8px; }
            .no-print { display: none !important; }
        }
        .no-print { text-align: center; margin-bottom: 10px; }
        .no-print button {
            padding: 8px 24px; font-size: 14px; cursor: pointer;
            background: #1e40af; color: #fff; border: none; border-radius: 6px;
            font-family: Arial, sans-serif;
        }
        .no-print button:hover { background: #1e3a8a; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimir Recibo</button>
        <p style="margin-top:6px;font-size:11px;color:#666;font-family:Arial,sans-serif;">O presione <strong>Ctrl + P</strong></p>
        <hr style="margin:12px 0;border:none;border-top:1px solid #ddd;">
    </div>

    <div class="header">
        <h1>Cursos San Vicente de Paul</h1>
        <p>Recibo de Pago</p>
    </div>

    <div class="numero-recibo">#{{ str_pad($recibo->numero_recibo, 6, '0', STR_PAD_LEFT) }}</div>

    <div class="encabezado">
        <div class="encabezado-titulo">Datos del encabezado</div>
        <div class="encabezado-bloque">
            @php
                $fechaRecibo = $recibo->getRawOriginal('fecha_recibo')
                    ?: $recibo->getRawOriginal('fecha_proceso')
                    ?: $recibo->pago?->getRawOriginal('fecha_proceso')
                    ?: $recibo->pago?->getRawOriginal('fecha_aprobacion')
                    ?: $recibo->getRawOriginal('creado_en')
                    ?: $recibo->pago?->getRawOriginal('creado_en');
                $fechaRecibo = $fechaRecibo ? \Carbon\Carbon::parse($fechaRecibo)->timezone(config('app.timezone')) : null;
                $codigoPago = $recibo->pago?->codigo;
            @endphp
            <table class="info">
                <tr><td class="label">Estudiante</td><td class="value">{{ $recibo->estudiante->codigo }} · {{ $recibo->estudiante->nombre }} {{ $recibo->estudiante->apellido }}</td></tr>
                <tr><td class="label">Código pago</td><td class="value">{{ $codigoPago ?? '-' }}</td></tr>
                <tr><td class="label">Concepto origen</td><td class="value">{{ $recibo->conceptoPago?->nombre ?? '-' }}</td></tr>
                <tr><td class="label">Método de pago</td><td class="value">{{ $recibo->metodoPago?->nombre ?? '-' }}</td></tr>
                <tr><td class="label">Sucursal</td><td class="value">{{ $recibo->sucursal?->nombre ?? '-' }}</td></tr>
                <tr><td class="label">Fecha</td><td class="value">{{ $fechaRecibo ? $fechaRecibo->format('d/m/Y H:i') : '-' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="estado">
        <span class="badge {{ $recibo->estado === 'anulado' ? 'anulado' : '' }}">{{ strtoupper($recibo->estado) }}</span>
    </div>

    @if($recibo->estado === 'anulado' && $recibo->motivo_anulacion)
        <div class="motivo">{{ $recibo->motivo_anulacion }}</div>
    @endif

    @php $pago = $recibo->pago; @endphp

    {{-- Detalle de matrícula/cuota --}}
    @if($pago && $pago->matricula && $pago->matricula->ofertaAcademica)
        @php $oferta = $pago->matricula->ofertaAcademica; @endphp
        <div class="linea"></div>
        <div class="titulo-seccion">Detalle Académico</div>
        <div class="detalle-item"><span class="d-label">Nivel</span><span class="d-value">{{ $oferta->nivelAcademico?->codigo }} · {{ $oferta->nivelAcademico?->nombre }}</span></div>
        <div class="detalle-item"><span class="d-label">Horario</span><span class="d-value">{{ $oferta->horario?->nombre ?? '-' }}</span></div>
        <div class="detalle-item"><span class="d-label">Modalidad</span><span class="d-value">{{ $oferta->modalidad?->nombre ?? '-' }}</span></div>
        <div class="detalle-item"><span class="d-label">Docente</span><span class="d-value">{{ $oferta->docente ? trim($oferta->docente->nombre . ' ' . $oferta->docente->apellido) : '-' }}</span></div>
    @endif

    {{-- Detalle de venta de libro --}}
    @if($pago && $pago->movimientosInventario->isNotEmpty())
        <div class="linea"></div>
        <div class="titulo-seccion">Detalle de Venta</div>
        @foreach($pago->movimientosInventario as $mov)
            @php $libro = $mov->inventarioLibro?->libro; @endphp
            @if($libro)
                <div class="detalle-item"><span class="d-label">Libro</span><span class="d-value">{{ $libro->codigo }} · {{ $libro->titulo }}</span></div>
                <div class="detalle-item"><span class="d-label">Cantidad</span><span class="d-value">{{ $mov->cantidad }}</span></div>
                <div class="detalle-item"><span class="d-label">Precio unit.</span><span class="d-value">L {{ number_format($libro->precio_venta, 2) }}</span></div>
                <div class="detalle-item"><span class="d-label">Total</span><span class="d-value">L {{ number_format($libro->precio_venta * $mov->cantidad, 2) }}</span></div>
            @endif
        @endforeach
    @endif

    <div class="linea"></div>

    <div class="monto-final">L {{ number_format($recibo->monto_total, 2) }}</div>

    <div class="linea"></div>

    <div style="font-size:10px;color:#555;margin-top:6px;">
        <div style="display:flex;justify-content:space-between;">
            <span>Código: {{ $recibo->codigo }}</span>
            <span>Pago: {{ $codigoPago ?? '-' }}</span>
            <span>Año: {{ $recibo->anio }}</span>
        </div>
    </div>

    @if($recibo->veces_reimpreso > 0)
        <div class="reimpresion">Reimpresiones: {{ $recibo->veces_reimpreso }}</div>
    @endif

    <div class="footer">
        <p>¡Gracias por su pago!</p>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto') === '1') {
            window.onload = function() { setTimeout(function() { window.print(); }, 300); };
        }
    </script>
</body>
</html>
