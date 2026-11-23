<?php

declare(strict_types=1);

$rolActivo = auth_rol_activo();
$emailUsuario = (string) session_get('email', '');
// Cambié la detección a la ruta lógica: SCRIPT_NAME es index.php al enrutar.
$currentUrl = defined('APP_ROUTE_PATH')
    ? rtrim(BASE_PATH, '/') . APP_ROUTE_PATH
    : rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH) ?? '/'));
$currentPage = basename($currentUrl);
// El controlador prepara el badge antes de renderizar; el layout no consulta BBDD.
$permisosPendientes ??= 0;

$links = [];

if ($rolActivo === 'ADMIN') {
    $links = [
        ['url' => url('admin/dashboard.php'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ['url' => url('admin/practicantes.php'), 'icon' => 'bi-people', 'label' => 'Practicantes'],
        ['url' => url('admin/nuevo-practicante.php'), 'icon' => 'bi-person-plus', 'label' => 'Nuevo practicante', 'matchPages' => ['nuevo-practicante.php', 'crear-practicante.php', 'practicantes-carga-masiva.php']],
        ['url' => url('formulario-validacion.php'), 'icon' => 'bi-file-earmark-word', 'label' => 'Formulario validación', 'matchPages' => ['formulario-validacion.php', 'formulario-validacion-descarga.php']],
        ['url' => url('admin/asistencia.php'), 'icon' => 'bi-calendar-check', 'label' => 'Asistencia'],
        ['url' => url('admin/permisos.php'), 'icon' => 'bi-envelope-paper', 'label' => 'Permisos', 'badge' => $permisosPendientes],
        ['url' => url('supervisor/acom.php'), 'icon' => 'bi-clipboard-data', 'label' => 'Acompañamiento'],
        ['url' => url('admin/usuarios.php'), 'icon' => 'bi-diagram-3', 'label' => 'Usuarios'],
        ['url' => url('admin/catalogos.php'), 'icon' => 'bi-collection', 'label' => 'Catálogos'],
        ['url' => url('admin/reportes.php'), 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Reportes'],
        // Integré evaluaciones, liderazgo y criterios bajo una entrada Notas después de Reportes.
        ['url' => url('admin/notas-evaluaciones.php'), 'icon' => 'bi-journal-check', 'label' => 'Notas', 'matchPages' => ['notas.php', 'notas-evaluaciones.php', 'notas-rubrica.php']],
        ['url' => url('admin/auditoria.php'), 'icon' => 'bi-shield-check', 'label' => 'Auditoría'],
        ['url' => url('supervisor/resenas.php'), 'icon' => 'bi-chat-heart', 'label' => 'Reseñas', 'matchPages' => ['resenas.php']],
    ];
} elseif ($rolActivo === 'SUPERVISOR') {
    $links = [
        ['url' => url('supervisor/dashboard.php'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ['url' => url('supervisor/practicantes.php'), 'icon' => 'bi-people', 'label' => 'Practicantes'],
        ['url' => url('supervisor/nuevo-practicante.php'), 'icon' => 'bi-person-plus', 'label' => 'Nuevo practicante', 'matchPages' => ['nuevo-practicante.php', 'crear-practicante.php', 'practicantes-carga-masiva.php']],
        ['url' => url('formulario-validacion.php'), 'icon' => 'bi-file-earmark-word', 'label' => 'Formulario validación', 'matchPages' => ['formulario-validacion.php', 'formulario-validacion-descarga.php']],
        ['url' => url('supervisor/asistencia.php'), 'icon' => 'bi-calendar-check', 'label' => 'Asistencia'],
        ['url' => url('supervisor/permisos.php'), 'icon' => 'bi-envelope-paper', 'label' => 'Permisos', 'badge' => $permisosPendientes],
        ['url' => url('supervisor/acom.php'), 'icon' => 'bi-clipboard-data', 'label' => 'Acompañamiento'],
        // Realize la url visual, que permitira que se visualize dentro de la plataforma, el modulo de reseñas, para el supervisor - Alexander
        ['url' => url('supervisor/resenas.php'), 'icon' => 'bi-chat-heart', 'label' => 'Reseñas', 'matchPages' => ['resenas.php']],
        ['url' => url('supervisor/reportes.php'), 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Reportes'],
    ];
} elseif ($rolActivo === 'PRACTICANTE') {
    $links = [
        ['url' => url('practicante/dashboard.php'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ['url' => url('practicante/marcar-asistencia.php'), 'icon' => 'bi-clock-history', 'label' => 'Mi asistencia'],
        ['url' => url('practicante/mi-historial.php'), 'icon' => 'bi-calendar-week', 'label' => 'Mi historial'],
        ['url' => url('practicante/permisos.php'), 'icon' => 'bi-envelope-paper', 'label' => 'Permisos'],
        ['url' => url('practicante/mis-datos.php'), 'icon' => 'bi-person-lines-fill', 'label' => 'Mis datos'],
        // Unifiqué el módulo visible para todos; sus pestañas separan la consulta propia de la evaluación autorizada.
        ['url' => url('practicante/mis-notas.php'), 'icon' => 'bi-journal-text', 'label' => 'Notas', 'matchPages' => ['mis-notas.php', 'notas.php']],
        // Realizé la url visual, que permitira que se visualize dentro de la plataforma, el modulo de reseñas - Alexander
        ['url' => url('practicante/resenas.php'), 'icon' => 'bi-chat-heart', 'label' => 'Reseñas', 'matchPages' => ['resenas.php']],
    ];
}
?>
<!-- Migré menú y cabecera a Tailwind conservando enlaces, logotipo y estado activo. -->
<div class="flex min-h-screen">
    <button type="button" data-ui-drawer-backdrop hidden class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" aria-label="Cerrar navegación"></button>
    <aside id="sidebarOffcanvas" data-ui-drawer aria-label="Navegación principal" class="fixed inset-y-0 left-0 z-50 flex w-[220px] shrink-0 -translate-x-full flex-col overflow-y-auto bg-gradient-to-b from-brand-sidebar to-brand-sidebar-deep px-3.5 py-5 text-white transition-transform motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="mb-5 flex items-start justify-between gap-2">
            <a href="<?= e(url(strtolower($rolActivo ?? 'practicante') . '/dashboard.php')) ?>" aria-label="Ir al inicio">
                <img src="<?= e(asset('brand/logo_rojo_blanco.png')) ?>" alt="e-practicas" class="h-auto max-h-[68px] w-[164px] object-contain object-left-top">
            </a>
            <button type="button" data-ui-drawer-close class="rrhh-close text-white lg:hidden" aria-label="Cerrar navegación"></button>
        </div>
        <nav class="grid gap-1.5" aria-label="Secciones">
            <?php foreach ($links as $link): ?>
                <?php
                // Conservé la comparación de rutas canónicas, sin depender del archivo físico.
                $isActive = $currentUrl === rawurldecode((string) parse_url($link['url'], PHP_URL_PATH));
                if ($link['url'] === url('admin/dashboard.php') || $link['url'] === url('supervisor/dashboard.php') || $link['url'] === url('practicante/dashboard.php')) {
                    $isActive = basename(dirname($currentUrl)) === strtolower($rolActivo ?? '') && $currentPage === 'dashboard.php';
                } elseif (isset($link['matchPages'])) {
                    $isActive = in_array($currentPage, $link['matchPages'], true);
                }
                ?>
                <a href="<?= e($link['url']) ?>" class="flex min-h-[38px] items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold transition <?= $isActive ? 'bg-brand-active text-white shadow-lg' : 'text-white/85 hover:bg-white/10 hover:text-white' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= e($link['icon']) ?> w-[18px] text-center" aria-hidden="true"></i>
                    <span><?= e($link['label']) ?></span>
                    <?php if (!empty($link['badge']) && (int) $link['badge'] > 0): ?>
                        <span class="rrhh-sidebar-badge ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-slate-900"><?= (int) $link['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="mt-auto">
            <?php // Bloque de versión en el menú lateral: puedes mover este div arriba (bajo el logo) o debajo del enlace Cerrar sesión 
            ?>
            <a class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-red-300 bg-red-500/15 transition hover:bg-red-500/20 hover:text-red-200" href="<?= e(url('logout.php')) ?>">
                <i class="bi bi-box-arrow-left w-[18px] text-center" aria-hidden="true"></i><span>Cerrar sesión</span>
            </a>
            <div class="rrhh-sidebar-version mt-3 flex items-center justify-between rounded-lg bg-white/5 px-3 py-2 text-xs text-white/85" style="pointer-events: none; user-select: none;">
                <span class="flex items-center gap-2.5 font-semibold">
                    <i class="bi bi-tag w-[18px] text-center text-white/70" aria-hidden="true"></i>
                    <span>Versión</span>
                </span>
                <?php // Evité una versión alternativa fija; la barra usa la misma fuente central que el login. ?>
                <span class="rrhh-sidebar-version-badge inline-flex items-center rounded-md border border-white/15 bg-white/10 px-2 py-0.5 text-xs font-bold leading-none text-white"><?= e('v' . APP_VERSION) ?></span>
            </div>

        </div>
    </aside>
    <main data-ui-main class="flex min-w-0 grow flex-col bg-slate-50 dark:bg-slate-950">
        <header class="main-header sticky top-0 z-20 shrink-0 border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex max-w-[1320px] items-center justify-between gap-3 px-4 py-2 lg:px-6">
                <div class="flex min-w-0 items-center gap-2.5">
                    <button class="rrhh-button border-slate-300 text-slate-700 lg:hidden" type="button" data-ui-drawer-toggle aria-controls="sidebarOffcanvas" aria-expanded="false" aria-label="Abrir navegación"><i class="bi bi-list" aria-hidden="true"></i></button>
                    <h1 class="mb-0 min-w-0 truncate text-lg font-bold leading-none text-slate-900 dark:text-slate-100"><?= e($pageTitle ?? APP_NAME) ?></h1>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <?php if (in_array($rolActivo, ['ADMIN', 'SUPERVISOR'], true)): ?>
                        <?php // Agregué seis muestras cuadradas para cambiar el tema desde la cabecera en roles administrativos. 
                        ?>
                        <div class="rrhh-theme-swatches" role="group" aria-label="Elegir tema de color">
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="light" style="--rrhh-swatch-color:#ffffff" aria-label="Tema Claro" title="Claro" aria-pressed="false"></button>
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="dark" style="--rrhh-swatch-color:#1d232a" aria-label="Tema Oscuro" title="Oscuro" aria-pressed="false"></button>
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="synthwave" style="--rrhh-swatch-color:#e779c1" aria-label="Tema Synthwave" title="Synthwave" aria-pressed="false"></button>
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="aqua" style="--rrhh-swatch-color:#09ecf3" aria-label="Tema Aqua" title="Aqua" aria-pressed="false"></button>
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="coffee" style="--rrhh-swatch-color:#db924b" aria-label="Tema Coffee" title="Coffee" aria-pressed="false"></button>
                            <button type="button" class="rrhh-theme-swatch" data-portal-theme="black" style="--rrhh-swatch-color:#000000" aria-label="Tema Black" title="Black" aria-pressed="false"></button>
                        </div>
                        <script>
                            // Guardé la preferencia administrativa en este navegador y la apliqué sin recargar.
                            (() => {
                                const clave = 'rrhh-personal-theme';
                                const oscuros = ['dark', 'synthwave', 'aqua', 'coffee', 'black'];
                                const botones = [...document.querySelectorAll('[data-portal-theme]')];
                                const aplicarTema = tema => {
                                    document.documentElement.dataset.theme = tema;
                                    document.documentElement.classList.toggle('dark', oscuros.includes(tema));
                                    botones.forEach(boton => boton.setAttribute('aria-pressed', String(boton.dataset.portalTheme === tema)));
                                    try {
                                        window.localStorage.setItem(clave, tema);
                                    } catch (error) {
                                        // Mantuve el cambio visual aunque el navegador no permita guardar la preferencia.
                                    }
                                };
                                botones.forEach(boton => boton.addEventListener('click', () => aplicarTema(boton.dataset.portalTheme)));
                                const actual = document.documentElement.dataset.theme || 'light';
                                botones.forEach(boton => boton.setAttribute('aria-pressed', String(boton.dataset.portalTheme === actual)));
                            })();
                        </script>
                    <?php endif; ?>
                    <div class="relative shrink-0">
                        <button class="rrhh-button border-slate-200 bg-slate-50 !px-3 !py-1.5 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" type="button" data-ui-dropdown="profileMenu" aria-controls="profileMenu" aria-expanded="false" aria-label="Opciones de cuenta">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-active text-xs font-bold text-white"><?= e(strtoupper(substr($emailUsuario, 0, 1))) ?></span>
                            <!-- Limité el ancho visual del correo en practicantes; el valor completo sigue disponible. -->
                            <span class="rrhh-account-email hidden text-sm text-slate-500 dark:text-slate-300 md:inline" title="<?= e($emailUsuario) ?>"><?= e($emailUsuario) ?></span><i class="bi bi-chevron-down text-xs" aria-hidden="true"></i>
                        </button>
                        <?php // Menú desplegable posicionado con top-full para desplegarse hacia abajo sin cortes 
                        ?>
                        <div id="profileMenu" hidden class="absolute right-0 top-full z-50 mt-2 min-w-[200px] rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                            <p class="mt-2 px-3 py-1.5 text-xs text-slate-500 dark:text-slate-300">Rol: <strong class="font-semibold text-slate-700 dark:text-slate-100"><?= e($rolActivo) ?></strong></p>
                            <a class="block rounded-lg mt-2 px-3 py-2 text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/50" href="<?= e(url('logout.php')) ?>"><i class="mt-4 bi bi-box-arrow-right mr-2" aria-hidden="true"></i>Cerrar sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="mx-auto w-full max-w-[1320px] grow p-4 lg:p-6">
            <?php if ($rolActivo === 'PRACTICANTE' && !empty($modoSoloLecturaPracticante['solo_lectura'])): ?>
                <?php $enResenas = str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), 'practicante/resenas.php'); ?>
                <!-- Mostré un aviso global limpio en una sola fila (desktop) cuando el practicante completó el 100% o egresó. -->
                <div class="mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs sm:text-sm text-emerald-950 shadow-sm" role="status">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <i class="bi bi-patch-check-fill text-base" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <strong class="block font-bold text-emerald-950 leading-snug"><?= !empty($modoSoloLecturaPracticante['meta_cumplida']) ? 'Práctica completada · Modo solo lectura' : 'Práctica finalizada · Modo solo lectura' ?></strong>
                            <p class="text-xs text-emerald-800 mb-0 leading-relaxed">
                                <?= !empty($modoSoloLecturaPracticante['meta_cumplida'])
                                    ? 'Completaste el 100% de tus horas. Puedes consultar historial, notas, documentos y gestionar tu reseña.'
                                    : 'Tu práctica está finalizada. Puedes consultar historial, notas, documentos y gestionar tu reseña.' ?>
                            </p>
                        </div>
                    </div>
                    <?php if (!$enResenas): ?>
                        <a href="<?= e(url('practicante/resenas.php')) ?>" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100/60 transition shrink-0 whitespace-nowrap">
                            <i class="bi bi-star-fill text-amber-500" aria-hidden="true"></i>
                            <span>Ir a mi reseña</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
