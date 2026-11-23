<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

/*
|--------------------------------------------------------------------------
| Configuración de correo SMTP
|--------------------------------------------------------------------------
|
| Este archivo define los valores que luego usará PHPMailer. Las credenciales
| deben venir desde variables de entorno en producción para no exponer datos
| sensibles dentro del repositorio.
|
*/

// Cambié estas lecturas al acceso común sin modificar el envío SMTP. Agregué
// el bloqueo del debug SMTP en producción para no mostrar conversaciones
// de autenticación aunque MAIL_DEBUG venga habilitado en el entorno.
define('MAIL_HOST', app_env('MAIL_HOST', ''));
define('MAIL_PORT', (int) app_env('MAIL_PORT', '587'));
define('MAIL_USERNAME', app_env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', app_env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', app_env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', app_env('MAIL_FROM_ADDRESS', 'no-reply@localhost'));
define('MAIL_FROM_NAME', app_env('MAIL_FROM_NAME', APP_NAME));
define('MAIL_DEBUG', APP_ENV === 'production' ? 0 : (int) app_env('MAIL_DEBUG', '0'));

// Comprobante de marcaje: se puede apagar, cambiar el mensaje o el logo sin tocar código.
// El logo es una ruta relativa a public/; vacío envía el correo sin imagen.
define('MAIL_COMPROBANTE_ACTIVO', app_env('MAIL_COMPROBANTE_ACTIVO', '1') === '1');
define('MAIL_COMPROBANTE_MENSAJE', trim((string) app_env('MAIL_COMPROBANTE_MENSAJE', '')) ?: 'Este correo es el respaldo de tu marca de asistencia. Consérvalo como comprobante; no es necesario responderlo.');
define('MAIL_COMPROBANTE_LOGO', app_env('MAIL_COMPROBANTE_LOGO', 'assets/brand/logo_correo.png'));

if (!function_exists('mail_smtp_auth_enabled')) {
    function mail_smtp_auth_enabled(): bool
    {
        // SMTPAuth solo se activa cuando existen credenciales configuradas.
        return MAIL_USERNAME !== '' && MAIL_PASSWORD !== '';
    }
}
