<?php
declare(strict_types=1);
/**
 * @var string $name
 * @var string $link
 * @var int    $ttl_minutes
 *
 * Plantilla HTML del email. CSS inline a propósito (compatibilidad con clientes).
 * NO usar layout.
 */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Recuperación de acceso</title>
</head>
<body style="margin:0;padding:0;background:#faf9f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1c2536;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf9f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e3e8ef;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0b2545;padding:20px 28px;color:#ffffff;">
                            <div style="font-size:16px;font-weight:600;letter-spacing:0.02em;">Wellness Circle Academy</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 16px;font-size:22px;color:#0b2545;">Recuperación de acceso</h1>
                            <p style="margin:0 0 16px;line-height:1.55;">Hola <?= e($name) ?>,</p>
                            <p style="margin:0 0 16px;line-height:1.55;">
                                Recibimos una solicitud para recuperar el acceso de administrador a
                                <strong>Wellness Circle Academy</strong>. Si fuiste tú, abre el botón
                                de abajo para definir una nueva contraseña.
                            </p>
                            <p style="margin:24px 0;text-align:center;">
                                <a href="<?= e($link) ?>"
                                   style="display:inline-block;background:#0b2545;color:#ffffff;text-decoration:none;font-weight:600;padding:14px 24px;border-radius:10px;">
                                    Definir nueva contraseña
                                </a>
                            </p>
                            <p style="margin:0 0 16px;line-height:1.55;color:#5a6878;font-size:14px;">
                                ¿No te funciona el botón? Copia y pega esta URL en tu navegador:
                            </p>
                            <p style="margin:0 0 16px;line-height:1.4;word-break:break-all;font-size:13px;">
                                <a href="<?= e($link) ?>" style="color:#0b2545;"><?= e($link) ?></a>
                            </p>
                            <p style="margin:0 0 16px;line-height:1.55;color:#5a6878;font-size:14px;">
                                El link expira en <strong><?= e($ttl_minutes) ?> minutos</strong> y solo
                                puede usarse una vez. Si no fuiste tú quien solicitó esto, ignora
                                este email; nadie obtendrá acceso solo por recibirlo.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#faf9f6;padding:16px 28px;border-top:1px solid #e3e8ef;color:#5a6878;font-size:12px;">
                            Email automático. No respondas a esta dirección.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
