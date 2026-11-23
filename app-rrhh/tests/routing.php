<?php

declare(strict_types=1);

// Agregué pruebas CLI del contrato de rutas sin ejecutar controladores,
// abrir sesiones, consultar BBDD ni acceder a archivos personales.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/src/Http/Router.php';
$root = dirname(__DIR__);
$routes = require $root . '/config/routes.php';
$checks = 0;

function routing_assert(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

// Compruebo URLs en subcarpeta, subdominio, acceso /public y query sin alterar
// tokens ni parámetros, ya que la normalización procesa únicamente el path.
$accepted = [
    ['/asistencia/login.php?token=abc%2Fdef', '/asistencia', '/login.php'],
    ['/asistencia', '/asistencia', '/'],
    ['/asistencia/', '/asistencia', '/'],
    ['/asistencia/public/login.php', '/asistencia', '/login.php'],
    ['/asistencia/public/', '/asistencia', '/'],
    ['/asistencia/admin/asistencia.php/', '/asistencia', '/admin/asistencia.php'],
    ['/login.php', '', '/login.php'],
    ['/', '', '/'],
    ['/mi%20portal/login.php', '/mi portal', '/login.php'],
];
foreach ($accepted as [$uri, $basePath, $expected]) {
    routing_assert(router_request_path($uri, $basePath) === $expected, 'Normalización legítima incorrecta.');
}

// Compruebo recorridos simples/codificados, dobles codificaciones, separadores
// Windows, controles, URLs absolutas y bases que solo comparten un prefijo.
$rejected = [
    '/asistencia/admin/../.env', '/asistencia/admin/%2e%2e/.env',
    '/asistencia/admin/%252e%252e/.env', '/asistencia/admin%5c..%5c.env',
    '/asistencia/login.php%00', '/asistencia//login.php',
    '/asistencia/%2flogin.php', '/asistencia/login.php%ZZ',
    '/asistencialogin.php', '/asistencia-mal/login.php',
    '//example.test/asistencia/login.php', 'https://example.test/asistencia/login.php',
    "/asistencia/login.php\n", '/asistencia/./login.php',
];
foreach ($rejected as $uri) {
    routing_assert(router_request_path($uri, '/asistencia') === null, 'Se aceptó una ruta ambigua o fuera de la base.');
}

// Compruebo que archivos reales del backend y el diagnóstico nunca se
// conviertan en rutas por existir físicamente o por comenzar con un rol.
foreach (['/.env', '/composer.lock', '/README.md', '/database/schema.sql',
    '/src/Views/admin/usuarios.view.php', '/diagnostico_correo.php',
    '/admin/archivo-no-registrado.php', '/uploads/practicantes/1/perfil/foto.png'] as $path) {
    routing_assert(router_match($routes, $path) === null, 'Se publicó un destino fuera del catálogo.');
}

// Comparo con el inventario original para no perder URLs de formularios,
// descarga, foto, JSON o autenticación durante la migración.
// Actualicé la ruta del inventario al reorganizar docs; conservé sus comprobaciones.
$inventory = fopen($root . '/docs/historial/mvc-tailwind/FASE_0_RUTAS.csv', 'r');
fgetcsv($inventory, 0, ',', '"', '');
while (($row = fgetcsv($inventory, 0, ',', '"', '')) !== false) {
    if ($row[0] === '/diagnostico_correo.php') {
        continue;
    }
    routing_assert(router_match($routes, $row[0]) !== null, 'Falta una URL del inventario anterior.');
    if ($row[0] !== '/index.php') {
        // Registré la única migración de destino de fase 5 sin alterar el CSV histórico.
        $expectedController = $row[0] === '/practicante/api/horario-dia.php'
            ? 'src/Controllers/Practicante/HorarioDiaController.php' : $row[3];
        routing_assert($routes[$row[0]]['controller'] === $expectedController, 'Cambió un destino de negocio sin migrarlo.');
    }
}
fclose($inventory);
foreach ($routes as $path => $route) {
    routing_assert(is_file($root . '/' . $route['controller']), 'Falta un destino fijo del catálogo.');
    if (str_starts_with($path, '/admin/')) {
        routing_assert($route['roles'] === ['ADMIN'], 'Una ruta ADMIN perdió su autorización previa.');
    } elseif (str_starts_with($path, '/practicante/')) {
        routing_assert($route['roles'] === ['PRACTICANTE'], 'Una ruta PRACTICANTE perdió su autorización previa.');
    }
}
routing_assert($routes['/supervisor/acom.php']['roles'] === ['ADMIN', 'SUPERVISOR'], 'Se perdió el acceso compartido de acompañamiento.');
routing_assert($routes['/formulario-validacion.php']['roles'] === ['ADMIN', 'SUPERVISOR'], 'Formulario validación no está protegido para los roles autorizados.');
routing_assert($routes['/formulario-validacion-descarga.php']['roles'] === ['ADMIN', 'SUPERVISOR'], 'La descarga de formularios no está protegida para los roles autorizados.');
routing_assert($routes['/formulario-validacion-descarga.php']['methods'] === ['GET', 'HEAD'], 'La descarga de formularios admite métodos de escritura.');
routing_assert(router_match($routes, '/login')['controller'] === $routes['/login.php']['controller'], 'Se perdió el alias /login de producción.');

// Agregué una estructura ficticia de Hostinger para comprobar el descubrimiento
// relativo del backend, sin depender de rutas de una cuenta real de hosting.
$fixture = sys_get_temp_dir() . '/rrhh-routing-' . bin2hex(random_bytes(8));
$directories = ['public_html', 'public_html/software-rrh', 'backend-rrhh',
    'backend-rrhh/src', 'backend-rrhh/config', 'backend-rrhh/vendor'];
$files = ['backend-rrhh/src/bootstrap.php', 'backend-rrhh/config/routes.php', 'backend-rrhh/vendor/autoload.php'];
mkdir($fixture, 0700);
try {
    foreach ($directories as $directory) {
        mkdir($fixture . '/' . $directory, 0700);
    }
    foreach ($files as $file) {
        file_put_contents($fixture . '/' . $file, '<?php');
    }
    routing_assert(router_backend_path($root . '/public') === realpath($root), 'No se localiza el backend local.');
    routing_assert(router_backend_path($fixture . '/public_html/software-rrh') === realpath($fixture . '/backend-rrhh'), 'No se localiza el backend relativo de Hostinger.');
    routing_assert(router_backend_path($root . '/public', $fixture . '/inexistente') === null, 'Se ignoró una configuración explícita inválida.');
    routing_assert(router_backend_path($root . '/public', $fixture . '/backend-rrhh') === realpath($fixture . '/backend-rrhh'), 'No se respetó BACKEND_PATH.');
} finally {
    // Elimino solo los archivos y directorios ficticios creados por esta prueba.
    foreach ($files as $file) {
        if (is_file($fixture . '/' . $file)) {
            unlink($fixture . '/' . $file);
        }
    }
    foreach (array_reverse($directories) as $directory) {
        rmdir($fixture . '/' . $directory);
    }
    rmdir($fixture);
}
echo "Enrutamiento: {$checks} comprobaciones correctas; " . count($routes) . " rutas explícitas.\n";
