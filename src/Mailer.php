<?php

declare(strict_types=1);

namespace App;

use App\Support\Env;
use RuntimeException;

/**
 * Envío de email via la API REST de Mailgun (cURL directo, sin SDK).
 *
 * Variables de entorno requeridas:
 *   MAILGUN_API_KEY       (private API key del workspace)
 *   MAILGUN_DOMAIN        (ej: mg.tudominio.com, o un sandbox de Mailgun)
 *   MAIL_FROM_ADDRESS     (ej: no-reply@tudominio.com — debe pertenecer al dominio anterior o estar autorizado)
 *
 * Variables opcionales:
 *   MAILGUN_REGION        ('us' por defecto, o 'eu')
 *   MAIL_FROM_NAME        ('Wellness Circle Academy' por defecto)
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $textBody, string $htmlBody): void
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Destinatario inválido.');
        }

        $apiKey   = Env::require('MAILGUN_API_KEY');
        $domain   = Env::require('MAILGUN_DOMAIN');
        $fromAddr = Env::require('MAIL_FROM_ADDRESS');
        $fromName = Env::get('MAIL_FROM_NAME', 'Wellness Circle Academy') ?? 'Wellness Circle Academy';
        $region   = mb_strtolower((string) (Env::get('MAILGUN_REGION', 'us') ?? 'us'));

        $endpoint = $region === 'eu'
            ? 'https://api.eu.mailgun.net/v3/' . rawurlencode($domain) . '/messages'
            : 'https://api.mailgun.net/v3/'    . rawurlencode($domain) . '/messages';

        $fields = [
            'from'    => sprintf('%s <%s>', $fromName, $fromAddr),
            'to'      => $to,
            'subject' => $subject,
            'text'    => $textBody,
            'html'    => $htmlBody,
        ];

        $ch = curl_init($endpoint);
        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => 'api:' . $apiKey,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'wca-mailer/1.0',
        ]);

        $response   = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[wca] Mailer: cURL falló: ' . $curlError);
            throw new RuntimeException('No se pudo conectar con el servidor de email.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            error_log(sprintf(
                '[wca] Mailer: Mailgun HTTP %d response=%s',
                $statusCode,
                mb_substr((string) $response, 0, 500)
            ));
            throw new RuntimeException('El servidor de email rechazó la solicitud (HTTP ' . $statusCode . ').');
        }
    }

    /**
     * ¿Está configurado el proveedor de email? Útil para no romper en local
     * cuando no hay credenciales.
     */
    public static function isConfigured(): bool
    {
        return Env::get('MAILGUN_API_KEY')    !== null
            && Env::get('MAILGUN_DOMAIN')     !== null
            && Env::get('MAIL_FROM_ADDRESS')  !== null;
    }
}
