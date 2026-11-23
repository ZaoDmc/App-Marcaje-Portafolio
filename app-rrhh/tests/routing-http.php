<?php

declare(strict_types=1);

// Agregué pruebas HTTP opcionales para comprobar las reglas reales de Apache.
// Solo se utilizan solicitudes anónimas y un POST con CSRF inválido: no se
// autentican usuarios, envían correos, registran marcas ni consultan documentos.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$bases = array_slice($argv, 1) ?: ['http://127.0.0.1:18083/asistencia', 'http://127.0.0.1:18084'];
$checks = 0;

function routing_http_request(string $url, string $method = 'GET', string $body = ''): array
{
    $options = ['method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10];
    if ($body !== '') {
        $options['header'] = "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['content'] = $body;
    }
    $response = @file_get_contents($url, false, stream_context_create(['http' => $options]));
    $headers = $http_response_header ?? [];
    if ($response === false && $headers === []) {
        throw new RuntimeException('El servidor HTTP de pruebas no está disponible.');
    }
    preg_match('~^HTTP/\S+ (\d+)~', $headers[0] ?? '', $status);
    return ['status' => (int) ($status[1] ?? 0), 'body' => $response ?: '', 'headers' => $headers];
}

function routing_http_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        // No imprimo cuerpos ni cabeceras, ya que una regresión podría haber
        // devuelto un archivo sensible o una cookie de sesión.
        throw new RuntimeException('Falló la comprobación HTTP: ' . $label);
    }
    $checks++;
}

foreach ($bases as $base) {
    $base = rtrim($base, '/');
    // Agregué recorrido de todas las URLs tras retirar sus archivos físicos:
    // las públicas renderizan y las privadas exigen sesión antes del negocio.
    $catalogue = require dirname(__DIR__) . '/config/routes.php';
    foreach ($catalogue as $path => $route) {
        $response = routing_http_request($base . $path);
        $expected = $route['roles'] !== [] || in_array($path, ['/', '/index.php', '/logout.php', '/seleccionar-rol.php'], true)
            ? 302 : 200;
        routing_http_assert($response['status'] === $expected, 'URL conservada sin puente: ' . $path);
        if ($route['roles'] !== []) {
            $location = array_values(array_filter($response['headers'], static fn(string $header): bool => str_starts_with(strtolower($header), 'location:')));
            routing_http_assert(count($location) === 1 && str_ends_with(trim($location[0]), '/login.php'), 'rol exige login: ' . $path);
        }
    }
    routing_http_assert(routing_http_request($base . '/practicante/api/horario-dia.php', 'POST')['status'] === 405, 'API trasladada conserva GET/HEAD');
    $login = routing_http_request($base . '/login.php');
    routing_http_assert($login['status'] === 200 && str_contains($login['body'], 'csrf_token'), 'login renderiza con CSRF');
    routing_http_assert(routing_http_request($base . '/login')['status'] === 200, 'alias productivo /login');
    routing_http_assert(routing_http_request($base . '/')['status'] === 302, 'raíz redirige sin recursión');
    routing_http_assert(routing_http_request($base . '/index.php')['status'] === 302, 'index conserva acceso inicial');
    routing_http_assert(routing_http_request($base . '/no-existe.php')['status'] === 404, 'ruta desconocida devuelve 404');
    routing_http_assert(routing_http_request($base . '/admin/no-registrado.php')['status'] === 404, 'prefijo de rol no autoriza un archivo');

    foreach (['/.env', '/.env.example', '/.git/config', '/composer.json', '/composer.lock',
        '/config/app.php', '/src/Views/admin/usuarios.view.php', '/vendor/autoload.php',
        // Actualicé la muestra documental a un archivo vigente; debe seguir bloqueado por HTTP.
        '/database/schema.sql', '/includes/auth.php', '/docs/proyecto/CONTEXTO.md',
        '/tests/configuration.php', '/scratch/phase1/composer.phar',
        // Agregué protección de los archivos de compilación de fase 6.
        '/package.json', '/package-lock.json', '/tailwind.config.cjs',
        '/resources/css/tailwind.css', '/scripts/copy-icons.cjs', '/node_modules/tailwindcss/package.json',
        '/uploads/practicantes/1/documentos/prueba.pdf', '/uploads.751/prueba.pdf',
        '/diagnostico_correo.php', '/backend.php', '/public/backend.php'] as $privatePath) {
        routing_http_assert(in_array(routing_http_request($base . $privatePath)['status'], [403, 404], true), 'protección de ' . $privatePath);
    }

    foreach (['/admin/asistencia.php', '/supervisor/acom.php', '/practicante/marcar-asistencia.php',
        '/practicante/api/horario-dia.php?fecha=2026-12-01', '/practicante/foto-perfil.php',
        '/practicante/descargar-documento.php?id=1'] as $protectedPath) {
        routing_http_assert(routing_http_request($base . $protectedPath)['status'] === 302, 'sesión requerida antes de ' . $protectedPath);
    }

    $css = routing_http_request($base . '/assets/css/app.css');
    routing_http_assert($css['status'] === 200 && hash('sha256', $css['body']) === hash_file('sha256', dirname(__DIR__) . '/public/assets/css/app.css'), 'CSS servido desde public/assets');
    // Comprobé recursos compilados y retirada del runtime CDN en el login real.
    $tailwind = routing_http_request($base . '/assets/css/tailwind.css');
    routing_http_assert($tailwind['status'] === 200 && hash('sha256', $tailwind['body']) === hash_file('sha256', dirname(__DIR__) . '/public/assets/css/tailwind.css'), 'CSS Tailwind compilado íntegro');
    routing_http_assert(routing_http_request($base . '/assets/js/ui.js')['status'] === 200, 'interacciones locales disponibles');
    routing_http_assert(routing_http_request($base . '/assets/icons/fonts/bootstrap-icons.woff2')['status'] === 200, 'fuente de iconos local disponible');
    routing_http_assert(!str_contains($login['body'], 'cdn.tailwindcss.com') && str_contains($login['body'], '/assets/css/tailwind.css'), 'login sin CDN Tailwind');
    $post = routing_http_request($base . '/login.php?prueba=routing', 'POST', 'csrf_token=invalido&email=&password=');
    routing_http_assert($post['status'] === 200 && str_contains($post['body'], 'La solicitud no pudo validarse.'), 'POST conserva cuerpo y validación CSRF');
    $invalidMethod = routing_http_request($base . '/login.php', 'DELETE');
    routing_http_assert($invalidMethod['status'] === 405 && count(preg_grep('/^Allow: /i', $invalidMethod['headers'])) === 1, 'método no autorizado devuelve 405 y Allow');
    $head = routing_http_request($base . '/login.php', 'HEAD');
    routing_http_assert($head['status'] === 200 && $head['body'] === '', 'HEAD no devuelve cuerpo');
}
echo "HTTP: {$checks} comprobaciones correctas en " . count($bases) . " ubicaciones públicas.\n";
