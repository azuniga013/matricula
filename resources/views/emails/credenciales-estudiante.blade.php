<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $esRegistro ? 'Bienvenido' : 'Cuenta Activada' }} — Cursos San Vicente de Paúl</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Inter,system-ui,-apple-system,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #0d9488 100%);padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                Cursos San Vicente de Paúl
                            </h1>
                            <p style="color:rgba(255,255,255,0.75);font-size:14px;margin:6px 0 0 0;">
                                {{ $esRegistro ? 'Bienvenido al sistema' : 'Tu cuenta ha sido activada' }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="font-size:16px;color:#374151;margin:0 0 8px 0;">Hola, <strong style="color:#111827;">{{ $nombre }}</strong></p>

                            @if ($esRegistro)
                                <p style="font-size:14px;color:#6b7280;margin:0 0 24px 0;line-height:1.6;">
                                    Te damos la bienvenida a Cursos San Vicente de Paúl. 
                                    Tus credenciales de acceso al portal del estudiante son las siguientes:
                                </p>
                            @else
                                <p style="font-size:14px;color:#6b7280;margin:0 0 24px 0;line-height:1.6;">
                                    Tu cuenta ha sido activada exitosamente. 
                                    A continuación tus credenciales de acceso al portal del estudiante:
                                </p>
                            @endif

                            {{-- Credentials Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Código de Estudiante</span>
                                        <p style="font-size:16px;font-weight:600;color:#0f172a;margin:2px 0 0 0;font-family:monospace;">{{ $codigo }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Correo Electrónico</span>
                                        <p style="font-size:16px;font-weight:600;color:#0f172a;margin:2px 0 0 0;">{{ $email }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Contraseña</span>
                                        <p style="font-size:16px;font-weight:600;color:#0f172a;margin:2px 0 0 0;font-family:monospace;">{{ $password }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px;color:#6b7280;margin:0 0 8px 0;line-height:1.6;">
                                Puedes acceder al portal del estudiante desde:
                            </p>
                            <p style="margin:0 0 24px 0;">
                                <a href="{{ url('/estudiante/login') }}" style="color:#0d9488;font-weight:600;font-size:14px;text-decoration:none;">
                                    {{ url('/estudiante/login') }} &rarr;
                                </a>
                            </p>

                            <p style="font-size:13px;color:#9ca3af;margin:0;line-height:1.5;border-top:1px solid #e5e7eb;padding-top:16px;">
                                Si no solicitaste este acceso, ignora este mensaje.<br>
                                Cursos San Vicente de Paúl — {{ date('Y') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
