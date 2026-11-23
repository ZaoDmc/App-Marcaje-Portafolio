<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/mail.php';

/*
|--------------------------------------------------------------------------
| Envío de correos del sistema
|--------------------------------------------------------------------------
|
| Este módulo centraliza el uso de PHPMailer mediante SMTP. No genera tokens
| ni decide reglas de negocio; solo arma y envía mensajes transaccionales.
| Los controladores definirán cuándo enviar activaciones o recuperaciones.
|
*/

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if (!function_exists('mailer_cargar_phpmailer')) {
    function mailer_cargar_phpmailer(): bool
    {
        if (class_exists(PHPMailer::class)) {
            return true;
        }

        $autoloadPath = APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (is_file($autoloadPath)) {
            require_once $autoloadPath;

            return class_exists(PHPMailer::class);
        }

        $basePath = APP_ROOT . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'phpmailer'
            . DIRECTORY_SEPARATOR . 'phpmailer'
            . DIRECTORY_SEPARATOR . 'src';

        $manualFiles = [
            $basePath . DIRECTORY_SEPARATOR . 'Exception.php',
            $basePath . DIRECTORY_SEPARATOR . 'PHPMailer.php',
            $basePath . DIRECTORY_SEPARATOR . 'SMTP.php',
        ];

        foreach ($manualFiles as $file) {
            if (!is_file($file)) {
                return false;
            }
        }

        // Permite usar PHPMailer instalado manualmente en Hostinger sin Composer.
        foreach ($manualFiles as $file) {
            require_once $file;
        }

        return class_exists(PHPMailer::class);
    }
}

if (!function_exists('mailer_disponible')) {
    function mailer_disponible(): bool
    {
        return mailer_cargar_phpmailer() && MAIL_HOST !== '' && MAIL_FROM_ADDRESS !== '';
    }
}

if (!function_exists('mailer_configurar_smtp')) {
    function mailer_configurar_smtp(PHPMailer $mail): void
    {
        // Toda configuración sensible viene desde config/mail.php y variables de entorno.
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = mail_smtp_auth_enabled();
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
        $mail->SMTPDebug = MAIL_DEBUG;
        $mail->Debugoutput = 'error_log';

        $encryption = strtolower(MAIL_ENCRYPTION);

        if ($encryption === 'ssl' || MAIL_PORT === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || MAIL_PORT === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }
}

if (!function_exists('mailer_crear')) {
    function mailer_crear(): PHPMailer
    {
        if (!mailer_cargar_phpmailer()) {
            throw new RuntimeException('PHPMailer no está instalado o no pudo cargarse.');
        }

        $mail = new PHPMailer(true);
        mailer_configurar_smtp($mail);
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        return $mail;
    }
}

if (!function_exists('mailer_respuesta')) {
    function mailer_respuesta(bool $ok, ?string $error = null): array
    {
        return [
            'ok' => $ok,
            'error' => $error,
        ];
    }
}

if (!function_exists('mailer_html_base')) {
    function mailer_html_base(string $titulo, string $contenidoHtml): string
    {
        // Plantilla mínima transaccional; las vistas completas se implementan en su etapa.
        return '<!doctype html>'
            . '<html lang="es">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . e($titulo) . '</title>'
            . '</head>'
            . '<body style="font-family:Arial,sans-serif;line-height:1.5;color:#1f2937;">'
            . '<main style="max-width:640px;margin:0 auto;padding:24px;">'
            . '<h1 style="font-size:20px;margin:0 0 16px;">' . e($titulo) . '</h1>'
            . $contenidoHtml
            . '<p style="margin-top:24px;color:#6b7280;font-size:13px;">'
            . e(APP_NAME)
            . '</p>'
            . '</main>'
            . '</body>'
            . '</html>';
    }
}

if (!function_exists('mailer_texto_desde_html')) {
    function mailer_texto_desde_html(string $html): string
    {
        $texto = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8');

        return trim(preg_replace('/[ \t]+/', ' ', $texto) ?? $texto);
    }
}

if (!function_exists('mailer_enviar')) {
    function mailer_enviar(string $destinatarioEmail, string $destinatarioNombre, string $asunto, string $html, ?string $textoPlano = null): array
    {
        if (!mailer_disponible()) {
            return mailer_respuesta(false, 'PHPMailer o SMTP no están configurados.');
        }

        try {
            $mail = mailer_crear();
            $mail->addAddress($destinatarioEmail, $destinatarioNombre);
            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $textoPlano ?? mailer_texto_desde_html($html);
            $mail->send();

            return mailer_respuesta(true);
        } catch (PHPMailerException|RuntimeException $exception) {
            error_log('Error al enviar correo: ' . $exception->getMessage());

            return mailer_respuesta(false, 'No fue posible enviar el correo.');
        }
    }
}

if (!function_exists('mailer_enviar_comprobante_marca')) {
    /**
     * Envía al practicante el comprobante de una marca ya guardada.
     * No lanza excepciones: el llamador decide qué hacer con ['ok' => false].
     *
     * @param array{fecha_larga?:string, observacion?:?string} $detalles
     */
    function mailer_enviar_comprobante_marca(
        string $email,
        string $nombrePracticante,
        string $tipoMarcaTexto,
        string $fechaHora,
        int $idMarca,
        array $detalles = []
    ): array {
        if (!MAIL_COMPROBANTE_ACTIVO) {
            return mailer_respuesta(false, 'Comprobante de marcaje desactivado.');
        }

        if (!mailer_disponible()) {
            return mailer_respuesta(false, 'PHPMailer o SMTP no están configurados.');
        }

        $timestamp = strtotime($fechaHora) ?: time();
        $folio = 'M-' . str_pad((string) $idMarca, 6, '0', STR_PAD_LEFT);
        $filas = [
            'Tipo de marca' => $tipoMarcaTexto,
            'Fecha' => (string) ($detalles['fecha_larga'] ?? date('d-m-Y', $timestamp)),
            'Hora' => date('H:i:s', $timestamp),
            'Folio' => $folio,
        ];

        if (!empty($detalles['observacion'])) {
            $filas['Observación'] = (string) $detalles['observacion'];
        }

        $filasHtml = '';
        foreach ($filas as $etiqueta => $valor) {
            $filasHtml .= '<tr>'
                . '<td style="padding:10px 0;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:40%;">' . e($etiqueta) . '</td>'
                . '<td style="padding:10px 0;border-top:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;">' . e($valor) . '</td>'
                . '</tr>';
        }

        // El logo va incrustado por CID: Gmail lo muestra sin pedir "cargar imágenes" y no aparece como adjunto.
        $rutaLogo = MAIL_COMPROBANTE_LOGO !== ''
            ? APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(MAIL_COMPROBANTE_LOGO, '/\\')
            : '';
        $usarLogo = $rutaLogo !== '' && is_file($rutaLogo) && is_readable($rutaLogo);

        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Comprobante de marca</title></head>'
            . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:\'Segoe UI\',\'Helvetica Neue\',Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:12px;border:1px solid #e5e7eb;">'
            . ($usarLogo ? '<tr><td style="padding:24px 28px 0;"><img src="cid:logo_comprobante" alt="e-prácticas" width="164" style="display:block;width:164px;max-width:100%;height:auto;border:0;"></td></tr>' : '')
            . '<tr><td style="padding:24px 28px 8px;">'
            . '<span style="display:inline-block;background:#dcfce7;color:#166534;font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;">Marcaje registrado &#10003;</span>'
            . '<h1 style="margin:14px 0 4px;font-size:20px;line-height:1.3;color:#1e3a8a;">' . e($tipoMarcaTexto) . ' &middot; ' . e(date('H:i', $timestamp)) . '</h1>'
            . '<p style="margin:0;color:#374151;font-size:14px;line-height:1.5;">Hola ' . e($nombrePracticante) . ', tu marca quedó guardada en el sistema.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:8px 28px 4px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $filasHtml . '</table></td></tr>'
            . '<tr><td style="padding:16px 28px 24px;color:#6b7280;font-size:13px;line-height:1.5;">' . e(MAIL_COMPROBANTE_MENSAJE) . '</td></tr>'
            . '</table>'
            . '<p style="margin:16px 0 0;color:#9ca3af;font-size:12px;">' . e(APP_NAME) . '</p>'
            . '</td></tr></table></body></html>';

        $texto = "Marcaje registrado: {$tipoMarcaTexto}\n";
        foreach ($filas as $etiqueta => $valor) {
            $texto .= $etiqueta . ': ' . $valor . "\n";
        }
        $texto .= "\n" . MAIL_COMPROBANTE_MENSAJE;

        try {
            $mail = mailer_crear();
            // Límite corto: un SMTP caído no debe dejar al practicante esperando (PHPMailer usa 300 s por defecto).
            $mail->Timeout = 10;
            // Hilo diario: mismo asunto y raíz de referencias por practicante y día, así Gmail/Outlook
            // agrupan las marcas de la jornada en una sola conversación.
            $dominio = substr(strrchr(MAIL_FROM_ADDRESS, '@') ?: '@localhost', 1);
            $raizHilo = '<asistencia-' . substr(hash('sha256', strtolower($email)), 0, 12) . '-' . date('Ymd', $timestamp) . '@' . $dominio . '>';
            $mail->MessageID = '<marca-' . $idMarca . '-' . bin2hex(random_bytes(4)) . '@' . $dominio . '>';
            $mail->addCustomHeader('In-Reply-To', $raizHilo);
            $mail->addCustomHeader('References', $raizHilo);
            // Aviso de correo automático: evita respuestas de vacaciones y se trata como notificación.
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
            $mail->addAddress($email, $nombrePracticante);
            $mail->Subject = 'Comprobantes de asistencia · ' . date('d-m-Y', $timestamp);
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $texto;

            if ($usarLogo) {
                $mail->addEmbeddedImage($rutaLogo, 'logo_comprobante', 'logo.png');
            }

            $mail->send();

            return mailer_respuesta(true);
        } catch (Throwable $exception) {
            error_log('Error al enviar comprobante de marca ' . $folio . ': ' . $exception->getMessage());

            return mailer_respuesta(false, 'No fue posible enviar el comprobante.');
        }
    }
}

if (!function_exists('mailer_boton_html')) {
    function mailer_boton_html(string $url, string $texto): string
    {
        return '<p style="margin:24px 0;">'
            . '<a href="' . e($url) . '" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:6px;">'
            . e($texto)
            . '</a>'
            . '</p>';
    }
}

if (!function_exists('mailer_enviar_activacion')) {
    function mailer_enviar_activacion(string $email, string $nombre, string $urlActivacion): array
    {
        $contenido = '<p>Hola ' . e($nombre) . ',</p>'
            . '<p>Se ha creado una cuenta para que puedas acceder al portal y completar tu activación.</p>'
            . mailer_boton_html($urlActivacion, 'Activar cuenta')
            . '<p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>'
            . '<p><a href="' . e($urlActivacion) . '">' . e($urlActivacion) . '</a></p>';

        $html = mailer_html_base('Activación de cuenta', $contenido);

        return mailer_enviar($email, $nombre, 'Activa tu cuenta - ' . APP_NAME, $html);
    }
}

if (!function_exists('mailer_enviar_recuperacion_password')) {
    function mailer_enviar_recuperacion_password(string $email, string $nombre, string $urlRecuperacion): array
    {
        $contenido = '<p>Hola ' . e($nombre) . ',</p>'
            . '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>'
            . mailer_boton_html($urlRecuperacion, 'Restablecer contraseña')
            . '<p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>'
            . '<p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>'
            . '<p><a href="' . e($urlRecuperacion) . '">' . e($urlRecuperacion) . '</a></p>';

        $html = mailer_html_base('Recuperación de contraseña', $contenido);

        return mailer_enviar($email, $nombre, 'Recupera tu contraseña - ' . APP_NAME, $html);
    }
}
