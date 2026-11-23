<?php // Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Página no encontrada</title>
    <!-- Conservé el diseño de la respuesta anterior durante la fase de rutas. -->

    <?php // Cargué Tailwind compilado e iconos locales, sin runtime de CDN.
    require_once VIEWS_PATH . '/components/ui.php';
    require VIEWS_PATH . '/components/styles.php'; ?>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <!-- Agregué una respuesta sin rutas físicas ni información del backend. -->
    <main class="text-center p-6">
        <h1 class="text-6xl font-bold text-slate-500">404</h1>
        <p class="text-lg">La página solicitada no existe o ha sido movida.</p>
        <a href="<?= e(url('login.php')) ?>" class="rrhh-button bg-brand-blue text-white hover:bg-blue-700">Ir al inicio</a>
    </main>
</body>
</html>
