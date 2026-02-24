<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperación</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:'Segoe UI', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8; padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(to right, #f39c12, #e67e22); padding:32px; text-align:center;">
                            <p style="margin:0; color:#fff; font-size:22px; font-weight:700; letter-spacing:0.5px;">AMG International</p>
                            <p style="margin:6px 0 0; color:rgba(255,255,255,0.85); font-size:13px;">Sistema de Nómina</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 40px;">
                            <p style="margin:0 0 8px; font-size:15px; color:#333; font-weight:600;">Hola, {{ $nombre }}</p>
                            <p style="margin:0 0 28px; font-size:14px; color:#666; line-height:1.6;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta.
                                Usa el siguiente código para continuar. <strong>Expira en 15 minutos.</strong>
                            </p>

                            {{-- Código --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:24px; background:#fef9f0; border:2px dashed #e67e22; border-radius:12px;">
                                        <p style="margin:0 0 6px; font-size:12px; color:#999; letter-spacing:1px; text-transform:uppercase;">Tu código</p>
                                        <p style="margin:0; font-size:42px; font-weight:700; color:#e67e22; letter-spacing:10px;">{{ $codigo }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 0; font-size:13px; color:#999; line-height:1.6;">
                                Si no solicitaste este código, ignora este correo. Tu contraseña no cambiará.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8f9fa; padding:20px 40px; border-top:1px solid #eee; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#aaa;">© {{ date('Y') }} AMG International · Este es un correo automático, no respondas.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
