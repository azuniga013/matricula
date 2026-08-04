<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta: Referencia de pago duplicada — Cursos San Vicente de Paúl</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Inter,system-ui,-apple-system,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #b45309 0%, #b91c1c 100%);padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                Cursos San Vicente de Paúl
                            </h1>
                            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:6px 0 0 0;">
                                Alerta de control — Posible pago duplicado
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="font-size:16px;color:#374151;margin:0 0 8px 0;">
                                Se ha detectado un nuevo pago cuyo <strong>número de referencia y fecha</strong>
                                coinciden con otro(s) pago(s) registrado(s) previamente por otro(s) estudiante(s).
                            </p>
                            <p style="font-size:14px;color:#6b7280;margin:0 0 24px 0;line-height:1.6;">
                                Posible uso compartido del mismo comprobante de depósito/transferencia. 
                                Se solicita revisión manual antes de aprobar el pago.
                            </p>

                            {{-- Nuevo pago --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:20px 24px;margin-bottom:20px;">
                                <tr><td colspan="2" style="font-size:12px;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;padding-bottom:8px;">Pago recién registrado</td></tr>
                                <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;">Código de pago</td><td style="padding:4px 0;color:#0f172a;font-size:14px;font-weight:600;font-family:monospace;text-align:right;">{{ $codigoPagoNuevo }}</td></tr>
                                <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;">Estudiante</td><td style="padding:4px 0;color:#0f172a;font-size:14px;font-weight:600;text-align:right;">{{ $nombreEstudianteNuevo }} ({{ $codigoEstudianteNuevo }})</td></tr>
                                <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;">Método</td><td style="padding:4px 0;color:#0f172a;font-size:14px;text-align:right;">{{ $metodo }}</td></tr>
                                <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;">Referencia</td><td style="padding:4px 0;color:#0f172a;font-size:14px;font-family:monospace;text-align:right;">{{ $referencia ?: '—' }}</td></tr>
                                <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;">Fecha de pago</td><td style="padding:4px 0;color:#0f172a;font-size:14px;text-align:right;">{{ $fechaPago ?: '—' }}</td></tr>
                            </table>

                            {{-- Coincidencias --}}
                            @if (!empty($coincidencias))
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;">
                                    <tr><td colspan="2" style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;font-weight:700;padding-bottom:14px;">Pagos coincidentes (histórico)</td></tr>
                                    @foreach ($coincidencias as $i => $c)
                                        @if ($i > 0)
                                            <tr><td colspan="2" style="height:16px;line-height:16px;font-size:0;">&nbsp;</td></tr>
                                        @endif
                                        <tr><td colspan="2" style="padding-top:0;padding-bottom:8px;">
                                            <span style="font-size:12px;color:#475569;font-weight:700;">Pago #{{ $i + 1 }} · Código</span>
                                            <span style="font-family:monospace;font-size:14px;color:#0f172a;font-weight:600;display:block;margin-top:2px;">{{ $c['codigo'] }}</span>
                                        </td></tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Estudiante</td>
                                            <td style="padding:4px 0;font-size:13px;color:#0f172a;text-align:right;">{{ $c['estudiante'] }} ({{ $c['codigo_estudiante'] }})</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Método</td>
                                            <td style="padding:4px 0;font-size:13px;color:#0f172a;text-align:right;">{{ $c['metodo'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Referencia</td>
                                            <td style="padding:4px 0;font-size:13px;color:#0f172a;font-family:monospace;text-align:right;">{{ $c['referencia'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Fecha de pago</td>
                                            <td style="padding:4px 0;font-size:13px;color:#475569;text-align:right;">{{ $c['fecha'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Monto</td>
                                            <td style="padding:4px 0;font-size:13px;color:#0f172a;font-weight:600;text-align:right;">L. {{ $c['monto'] }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:13px;color:#6b7280;">Estado del pago</td>
                                            <td style="padding:4px 0;font-size:13px;color:#475569;text-align:right;">{{ $c['estado'] }}</td>
                                        </tr>
                                        @if (!empty($c['numero_recibo']))
                                            <tr><td colspan="2" style="padding-top:10px;padding-bottom:4px;">
                                                <span style="display:inline-block;background-color:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;">RECIBO ASOCIADO</span>
                                            </td></tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Número de recibo</td>
                                                <td style="padding:4px 0;font-size:13px;color:#0f172a;font-family:monospace;font-weight:600;text-align:right;">{{ $c['numero_recibo'] }}</td>
                                            </tr>
                                            @if (!empty($c['fecha_recibo']))
                                                <tr>
                                                    <td style="padding:4px 0;font-size:13px;color:#6b7280;">Fecha de recibo</td>
                                                    <td style="padding:4px 0;font-size:13px;color:#475569;text-align:right;">{{ $c['fecha_recibo'] }}</td>
                                                </tr>
                                            @endif
                                            @if (!empty($c['estado_recibo']))
                                                <tr>
                                                    <td style="padding:4px 0;font-size:13px;color:#6b7280;">Estado de recibo</td>
                                                    <td style="padding:4px 0;font-size:13px;color:#475569;text-align:right;">{{ $c['estado_recibo'] }}</td>
                                                </tr>
                                            @endif
                                        @else
                                            <tr><td colspan="2" style="padding-top:6px;padding-bottom:4px;">
                                                <span style="display:inline-block;background-color:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;">SIN RECIBO ASOCIADO</span>
                                            </td></tr>
                                        @endif
                                    @endforeach
                                </table>
                            @endif

                            <p style="font-size:13px;color:#9ca3af;margin-top:24px;line-height:1.5;">
                                Este mensaje fue generado automáticamente por el sistema de control de pagos. 
                                No responda a este correo. Ingrese al panel administrativo para revisar el detalle del pago.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>