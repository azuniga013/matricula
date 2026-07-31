<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; margin: 0; padding: 0; }
        .page {
            position: relative;
            width: 100%;
            min-height: 100%;
            padding: 38px 48px;
            background: #ffffff;
        }
        /* Marco decorativo doble */
        .frame-outer {
            position: absolute;
            top: 16px; right: 16px; bottom: 16px; left: 16px;
            border: 3px solid #1d4ed8;
            border-radius: 10px;
        }
        .frame-inner {
            position: absolute;
            top: 24px; right: 24px; bottom: 24px; left: 24px;
            border: 1.5px solid #d4af37;
            border-radius: 7px;
        }
        .corner {
            position: absolute;
            width: 30px; height: 30px;
            border-radius: 6px;
            background: #d4af37;
        }
        .corner.tl { top: 10px; left: 10px; }
        .corner.tr { top: 10px; right: 10px; }
        .corner.bl { bottom: 10px; left: 10px; }
        .corner.br { bottom: 10px; right: 10px; }

        .content { position: relative; z-index: 2; text-align: center; }

        .brand-row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .seal {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: #1d4ed8;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            border: 2px solid #d4af37;
        }
        .brand-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #1d4ed8;
        }
        .sub-brand { font-size: 10px; color: #6b7280; letter-spacing: 1px; margin-top: 2px; }

        .title-h1 {
            font-size: 34px;
            font-weight: bold;
            color: #111827;
            margin: 10px 0 2px;
            letter-spacing: 1px;
        }
        .title-h2 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 18px;
            font-style: italic;
        }
        .decor {
            width: 220px;
            height: 2px;
            margin: 0 auto 18px;
            background: linear-gradient(90deg, transparent, #d4af37, transparent);
        }

        .lead { font-size: 13px; color: #374151; margin-bottom: 4px; }
        .student-name {
            font-size: 30px;
            font-weight: bold;
            color: #1d4ed8;
            margin: 2px 0 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-text { font-size: 13px; color: #374151; margin: 2px 0; }
        .detail-text strong { color: #111827; }

        .columns {
            display: table;
            width: 100%;
            margin-top: 22px;
            table-layout: fixed;
        }
        .col { display: table-cell; vertical-align: middle; }
        .col.left { text-align: left; width: 50%; padding-right: 20px; }
        .col.right {
            text-align: center;
            width: 17%;
        }
        .col.center { text-align: center; width: 16%; }
        .col.row { text-align: right; width: 17%; padding-left: 20px; }

        .qr-img { width: 110px; height: 110px; border: 2px solid #d4af37; border-radius: 8px; padding: 4px; background: #fff; }

        .sign-block { text-align: center; }
        .sign-line {
            border-top: 1.5px solid #1d4ed8;
            width: 200px;
            margin: 0 auto 4px;
        }
        .sign-label { font-size: 10px; color: #6b7280; }
        .sign-name { font-size: 12px; font-weight: bold; color: #111827; }

        .verify-code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 11px;
            font-weight: bold;
            color: #1d4ed8;
            letter-spacing: 2px;
        }
        .verify-url {
            font-size: 7px;
            color: #9ca3af;
            word-break: break-all;
            max-width: 130px;
            margin: 4px auto 0;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="frame-outer"></div>
        <div class="frame-inner"></div>
        <div class="corner tl"></div><div class="corner tr"></div>
        <div class="corner bl"></div><div class="corner br"></div>

        <div class="content">
            <div class="brand-row">
                <span class="seal">SV</span>
                <div style="text-align:left">
                    <div class="brand-name">Cursos San Vicente de Paúl</div>
                    <div class="sub-brand">Formación Académica · {{ $cert->historialAcademico?->ofertaAcademica?->sucursal?->nombre ?? 'Honduras' }}</div>
                </div>
            </div>

            <div class="title-h1">CERTIFICADO DE APROBACIÓN</div>
            <div class="title-h2">Se otorga el presente certificado a</div>
            <div class="decor"></div>

            <div class="student-name">{{ $cert->estudiante?->nombre_completo ?? trim(($cert->estudiante?->nombre ?? '') . ' ' . ($cert->estudiante?->apellido ?? '')) }}</div>

            <div class="lead">Por haber aprobado satisfactoriamente el nivel</div>
            <div class="detail-text" style="font-size:20px;font-weight:bold;color:#111827;margin-top:4px;">
                {{ $cert->nivelAcademico?->nombre ?? '-' }}
            </div>

            <div class="columns">
                <div class="col left">
                    <div class="detail-text"><strong>Programa:</strong> {{ $cert->nivelAcademico?->versionPlanEstudio?->planEstudio?->nombre ?? '-' }}</div>
                    @php $v = $cert->nivelAcademico?->versionPlanEstudio; @endphp
                    @if ($v && $v->planEstudio)
                    <div class="detail-text"><strong>Versión del plan:</strong> {{ $v->planEstudio->nombre }} · V{{ $v->numero_version }}</div>
                    @endif
                    <div class="detail-text"><strong>Departamento:</strong> {{ $cert->nivelAcademico?->versionPlanEstudio?->planEstudio?->departamentoAcademico?->nombre ?? '-' }}</div>
                    <div class="detail-text"><strong>Período:</strong> {{ $cert->historialAcademico?->ofertaAcademica?->periodoAcademico?->nombre ?? '-' }}</div>
                    <div class="detail-text"><strong>Modalidad:</strong> {{ $cert->historialAcademico?->ofertaAcademica?->modalidad?->nombre ?? '-' }}</div>
                    <div class="detail-text"><strong>Docente:</strong> {{ $cert->historialAcademico?->ofertaAcademica?->docente ? trim($cert->historialAcademico->ofertaAcademica->docente->nombre . ' ' . $cert->historialAcademico->ofertaAcademica->docente->apellido) : '-' }}</div>
                </div>

                <div class="col center sign-block">
                    <div class="sign-line"></div>
                    <div class="sign-name">Coordinación Académica</div>
                    <div class="sign-label">Cursos San Vicente de Paúl</div>
                </div>

                <div class="col right">
                    <img src="{{ $qrDataUri ?? '' }}" class="qr-img" alt="QR de validación">
                    <div class="verify-code">{{ $cert->codigo_verificacion }}</div>
                    <div class="verify-url">{{ $verificacionUrl }}</div>
                </div>
            </div>

            <div style="margin-top:20px">
                <div class="detail-text"><strong>Nota final:</strong> {{ number_format((float) $cert->nota_final, 2) }} &nbsp;·&nbsp;
                <strong>Emitido el:</strong> {{ optional($cert->emitido_en)->format('d/m/Y') }} &nbsp;·&nbsp;
                <strong>Certificado N°:</strong> {{ $cert->codigo }}</div>
            </div>

            <div class="footer">
                DOCUMENTO VERIFICABLE · ESCANEE EL CÓDIGO QR O VISITE EL ENLACE INDICADO · CURSOS SAN VICENTE DE PAÚL
            </div>
        </div>
    </div>
</body>
</html>