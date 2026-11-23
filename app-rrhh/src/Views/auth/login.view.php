<?php // Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
?>
<!doctype html>
<?php // Apliqué el tema institucional daisyUI únicamente en esta página de prueba. ?>
<html lang="es" class="h-full" data-theme="rrhh">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | <?= e(APP_NAME) ?></title>
    <?php // Usé el isotipo existente de e-prácticas como icono de la pestaña del navegador. ?>
    <link rel="icon" type="image/png" href="<?= e(asset('img/logo-sin-texto.png')) ?>">

<?php // Inicialicé el tema antes de cargar estilos para evitar un destello claro al abrir el login en modo oscuro. ?>
    <script>
        (function () {
            const claveTema = 'rrhh-login-theme';
            const temaSistemaOscuro = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            let tema = temaSistemaOscuro ? 'dark' : 'light';

            try {
                const temaGuardado = window.localStorage.getItem(claveTema);
                if (temaGuardado === 'light' || temaGuardado === 'dark') tema = temaGuardado;
            } catch (error) {
                // El login conserva el tema del sistema cuando el navegador bloquea el almacenamiento local.
            }

            document.documentElement.dataset.theme = tema === 'dark' ? 'rrhh-dark' : 'rrhh';
            document.documentElement.classList.toggle('dark', tema === 'dark');
        })();
    </script>

<?php // Cargué Tailwind compilado e iconos locales, sin runtime de CDN.
    require_once VIEWS_PATH . '/components/ui.php';
    require VIEWS_PATH . '/components/styles.php'; ?>
<?php // Agregué el CSS independiente con versión por contenido para evitar caché durante esta prueba visual. ?>
<link href="<?= e(asset('css/login.css')) ?>?v=<?= e(substr(hash_file('sha256', ASSETS_PATH . '/css/login.css'),0,12)) ?>" rel="stylesheet">
</head>
<body class="relative min-h-full bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-100 flex items-center justify-center p-4 pt-16 sm:p-6 lg:p-8 antialiased transition-colors duration-200">
    <?php // Añadí un control visible y accesible para alternar el tema; la preferencia se guarda solo en este navegador. ?>
    <button
        type="button"
        id="themeToggle"
        class="absolute right-4 top-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus:ring-offset-slate-950"
        aria-label="Activar modo oscuro"
        aria-pressed="false"
        title="Activar modo oscuro"
    >
        <i class="bi bi-moon-stars" id="themeToggleIcon" aria-hidden="true"></i>
        <span id="themeToggleLabel">Modo oscuro</span>
    </button>

    <main class="w-full max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

        <!-- Panel de bienvenida / presentación -->
        <section class="lg:col-span-7 space-y-6" aria-labelledby="welcome-title">
            <h2 id="welcome-title" class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight leading-tight">
                Bienvenido al portal de e-prácticas
            </h2>
            <p class="text-slate-600 dark:text-slate-300 text-base sm:text-lg leading-relaxed max-w-xl">
                Desde este espacio podrás iniciar y completar tu registro, revisar la información de tu práctica,
                consultar tu avance y acceder a los procesos disponibles según tu rol.
            </p>
            <ul class="space-y-3 pt-2">
                <li class="flex items-center gap-3 text-slate-700 dark:text-slate-200">
                    <span class="w-2 h-2 rounded-full bg-blue-700 dark:bg-blue-400 shrink-0"></span>
                    <span>Iniciar tu proceso de registro.</span>
                </li>
                <li class="flex items-center gap-3 text-slate-700 dark:text-slate-200">
                    <span class="w-2 h-2 rounded-full bg-blue-700 dark:bg-blue-400 shrink-0"></span>
                    <span>Ver la información de tu práctica.</span>
                </li>
                <li class="flex items-center gap-3 text-slate-700 dark:text-slate-200">
                    <span class="w-2 h-2 rounded-full bg-blue-700 dark:bg-blue-400 shrink-0"></span>
                    <span>Consultar tu asistencia, desempeño y avance.</span>
                </li>
                <li class="flex items-center gap-3 text-slate-700 dark:text-slate-200">
                    <span class="w-2 h-2 rounded-full bg-blue-700 dark:bg-blue-400 shrink-0"></span>
                    <span>Realizar otros procesos asociados a tu práctica.</span>
                </li>
            </ul>
        </section>

        <!-- Tarjeta de formulario de login -->
        <?php // Reemplacé componentes visuales del formulario por card/input/checkbox/btn de daisyUI; conservé el POST y sus campos. ?>
        <?php // Apliqué una tarjeta gris en modo claro y una superficie oscura en modo oscuro. ?>
        <section class="dui-card lg:col-span-5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl shadow-slate-200/60 dark:shadow-black/30 p-6 sm:p-8 md:p-10 transition-colors duration-200" aria-labelledby="login-title">
            <div class="text-center mb-6">
                <?php // Separé las variantes de marca: letras negras sobre blanco en Claro y letras blancas sobre la tarjeta oscura. ?>
                <div class="inline-flex max-w-full items-center justify-center rounded-xl bg-white dark:bg-slate-800 px-5 py-3 mb-3">
                    <img src="<?= e(asset('brand/logo_rojo_negro.png')) ?>" alt="<?= e(APP_NAME) ?>" class="block dark:hidden h-auto max-h-[68px] w-[164px] max-w-full object-contain">
                    <img src="<?= e(asset('brand/logo_rojo_blanco.png')) ?>" alt="<?= e(APP_NAME) ?>" class="hidden dark:block h-auto max-h-[68px] w-[164px] max-w-full object-contain">
                </div>
                <h1 id="login-title" class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100">Iniciar sesión</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Portal de asistencia de practicantes</p>
            </div>

            <?php if ($errors !== []): ?>
                <div class="mb-5 p-4 rounded-xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-200 text-sm space-y-1" role="alert">
                    <div class="flex items-start gap-2">
                        <i class="bi bi-exclamation-circle-fill text-red-500 mt-0.5"></i>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <div><?= e($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (query_param('timeout') === '1'): ?>
                <div class="mb-5 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-200 text-sm flex items-center gap-2" role="alert">
                    <i class="bi bi-clock-history text-amber-600"></i>
                    <span>Tu sesión expiró por inactividad.</span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login.php')) ?>" novalidate class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1.5">Correo electrónico</label>
                    <input
                        type="email"
                        class="dui-input dui-input-bordered w-full h-12 rounded-xl text-base"
                        id="email"
                        name="email"
                        value="<?= e($email) ?>"
                        placeholder="tu.correo@ejemplo.com"
                        autocomplete="email"
                        inputmode="email"
                        required
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1.5">Contraseña</label>
                    <div class="relative">
                        <input
                            type="password"
                            class="dui-input dui-input-bordered w-full h-12 pr-12 rounded-xl text-base"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            class="absolute inset-y-0 right-0 px-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none transition"
                            type="button"
                            id="togglePassword"
                            aria-label="Mostrar u ocultar contraseña"
                            title="Mostrar / ocultar contraseña"
                        >
                            <i class="bi bi-eye text-lg" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2.5 cursor-pointer text-sm text-slate-600 dark:text-slate-300 select-none">
                        <input
                            class="dui-checkbox dui-checkbox-primary dui-checkbox-sm shrink-0"
                            type="checkbox"
                            value="1"
                            id="mantener_sesion"
                            name="mantener_sesion"
                            <?= $mantenerSesion ? 'checked' : '' ?>
                        >
                        <span>Mantener sesión iniciada</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="dui-btn dui-btn-primary w-full h-12 rounded-xl text-base"
                >
                    Entrar
                </button>
            </form>

            <div class="flex items-center justify-between gap-3 mt-6 pt-5 border-t border-slate-200 dark:border-slate-700 text-xs sm:text-sm">
                <a class="text-blue-800 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-200 hover:underline font-medium" href="<?= e(url('recuperar-password.php')) ?>">
                    Recuperar contraseña
                </a>
                <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
                    <i class="bi bi-shield-lock"></i> Acceso autorizado
                </span>
            </div>
            <?php // Mostré la versión del código servido, conservando contraste en ambos temas. ?>
            <p id="app-version" class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
                Versión <?= e(APP_VERSION) ?>
            </p>
        </section>

    </main>

    <script>
        // Conecté el control de tema; actualiza DaisyUI y Tailwind juntos y guarda la selección en este navegador.
        (function () {
            const claveTema = 'rrhh-login-theme';
            const raiz = document.documentElement;
            const boton = document.getElementById('themeToggle');
            const icono = document.getElementById('themeToggleIcon');
            const etiqueta = document.getElementById('themeToggleLabel');

            if (!boton || !icono || !etiqueta) return;

            function aplicarTema(tema, guardar) {
                const oscuro = tema === 'dark';
                raiz.dataset.theme = oscuro ? 'rrhh-dark' : 'rrhh';
                raiz.classList.toggle('dark', oscuro);
                boton.setAttribute('aria-pressed', String(oscuro));
                boton.setAttribute('aria-label', oscuro ? 'Activar modo claro' : 'Activar modo oscuro');
                boton.title = oscuro ? 'Activar modo claro' : 'Activar modo oscuro';
                icono.className = oscuro ? 'bi bi-sun' : 'bi bi-moon-stars';
                etiqueta.textContent = oscuro ? 'Modo claro' : 'Modo oscuro';

                if (guardar) {
                    try {
                        window.localStorage.setItem(claveTema, tema);
                    } catch (error) {
                        // El cambio visual sigue funcionando aunque el navegador no permita guardar la preferencia.
                    }
                }
            }

            aplicarTema(raiz.classList.contains('dark') ? 'dark' : 'light', false);
            boton.addEventListener('click', function () {
                aplicarTema(raiz.classList.contains('dark') ? 'light' : 'dark', true);
            });
        })();

        (function () {
            const btn   = document.getElementById('togglePassword');
            const input = document.getElementById('password');
            const icon  = document.getElementById('togglePasswordIcon');

            if (!btn || !input || !icon) return;

            btn.addEventListener('click', function () {
                const mostrar  = input.type === 'password';
                input.type     = mostrar ? 'text' : 'password';
                icon.className = mostrar ? 'bi bi-eye-slash text-lg' : 'bi bi-eye text-lg';
                btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
                input.focus();
            });
        })();
    </script>
</body>
</html>
