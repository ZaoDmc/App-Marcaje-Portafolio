<?php
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
$extraStyles ??= [];
$rolActivoActual = auth_rol_activo();
$esPracticante = $rolActivoActual === 'PRACTICANTE';
$esPersonalAdministrativo = in_array($rolActivoActual, ['ADMIN', 'SUPERVISOR'], true);
$usaTemasPortal = $esPracticante || $esPersonalAdministrativo;
$claveTemaPortal = $esPracticante ? 'rrhh-practicante-theme' : 'rrhh-personal-theme';
?>
<?php // Activé el tema claro de daisyUI para resolver los colores semánticos de sus alertas del portal. ?>
<!doctype html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <?php // Usé el isotipo existente para identificar todas las páginas internas en la pestaña del navegador. ?>
    <link rel="icon" type="image/png" href="<?= e(asset('img/logo-sin-texto.png')) ?>">

    <?php if ($usaTemasPortal): ?>
        <?php // Apliqué el tema guardado antes del CSS; cada grupo de usuario conserva su propia preferencia. ?>
        <script>
            (function () {
                const clave = <?= json_encode($claveTemaPortal, JSON_THROW_ON_ERROR) ?>;
                const permitidos = ['light', 'dark', 'synthwave', 'aqua', 'coffee', 'black'];
                const oscuros = ['dark', 'synthwave', 'aqua', 'coffee', 'black'];
                let tema = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                try {
                    const guardado = window.localStorage.getItem(clave);
                    if (permitidos.includes(guardado)) tema = guardado;
                    <?php if ($esPracticante): ?>
                        const anterior = window.localStorage.getItem('rrhh-practicante-marcaje-theme');
                        if (!permitidos.includes(guardado) && (anterior === 'light' || anterior === 'dark')) tema = anterior;
                    <?php endif; ?>
                } catch (error) {
                    // Conservé el tema del sistema si el navegador bloquea el almacenamiento local.
                }
                document.documentElement.dataset.theme = tema;
                document.documentElement.classList.toggle('dark', oscuros.includes(tema));
            })();
        </script>
    <?php endif; ?>

    <?php // Cargué Tailwind compilado e iconos locales, sin runtime de CDN.
    require_once VIEWS_PATH . '/components/ui.php';
    require VIEWS_PATH . '/components/styles.php'; ?>
    <!-- Conservé los estilos específicos del portal después de la base Tailwind. -->
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <?php if ($usaTemasPortal): ?>
        <?php
        // Cargué el tema compartido para practicantes, supervisores y administradores con versión por contenido.
        $temaPortalCss = ASSETS_PATH . '/css/practicante-temas.css';
        $temaPortalHash = is_file($temaPortalCss) && is_readable($temaPortalCss) ? hash_file('sha256', $temaPortalCss) : false;
        $temaPortalUrl = asset('css/practicante-temas.css') . ($temaPortalHash !== false ? '?v=' . substr($temaPortalHash, 0, 16) : '');
        ?>
        <link href="<?= e($temaPortalUrl) ?>" rel="stylesheet">
    <?php endif; ?>
    <?php foreach ($extraStyles as $style): ?>
        <link href="<?= e($style) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<!-- Habilité las paletas compartidas únicamente para los roles que cuentan con selector de tema. -->
<body class="<?= $usaTemasPortal ? 'rrhh-themed ' . ($esPracticante ? 'rrhh-practicante' : 'rrhh-equipo') : '' ?>">
