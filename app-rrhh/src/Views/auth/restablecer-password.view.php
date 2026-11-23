<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña | <?= e(APP_NAME) ?></title>

<?php // Cargué Tailwind compilado e iconos locales, sin runtime de CDN.
    require_once VIEWS_PATH . '/components/ui.php';
    require VIEWS_PATH . '/components/styles.php'; ?>
</head>
<body class="min-h-full bg-gradient-to-br from-teal-500/10 via-slate-50 to-blue-500/10 text-slate-800 flex items-center justify-center p-4 sm:p-6 antialiased">
    <main class="w-full max-w-md mx-auto">
        <section class="bg-white border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/60 p-6 sm:p-8 md:p-10" aria-labelledby="reset-title">
            <div class="text-center mb-6">
                <img src="<?= e(asset('brand/logo.png')) ?>" alt="<?= e(APP_NAME) ?>" class="h-16 w-auto mx-auto object-contain mb-3">
                <h1 id="reset-title" class="text-xl sm:text-2xl font-bold text-slate-900">Restablecer contraseña</h1>
                <p class="text-sm text-slate-500 mt-1">Define una nueva contraseña para tu cuenta.</p>
            </div>

            <?php if ($passwordChanged): ?>
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2.5" role="alert">
                    <i class="bi bi-check-circle-fill text-emerald-600"></i>
                    <span>Tu contraseña fue actualizada correctamente.</span>
                </div>
                <a class="block w-full text-center py-3.5 px-4 bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-xl shadow-md transition" href="<?= e(url('login.php')) ?>">
                    Ir al login
                </a>
            <?php elseif ($tokenError !== null): ?>
                <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2.5" role="alert">
                    <i class="bi bi-exclamation-circle-fill text-red-500"></i>
                    <span><?= e($tokenError) ?></span>
                </div>
                <a class="block w-full text-center py-3.5 px-4 bg-teal-700 hover:bg-teal-800 text-white font-semibold rounded-xl shadow-md transition" href="<?= e(url('recuperar-password.php')) ?>">
                    Solicitar nuevo enlace
                </a>
            <?php else: ?>
                <?php if ($formErrors !== []): ?>
                    <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm space-y-1" role="alert">
                        <div class="flex items-start gap-2">
                            <i class="bi bi-exclamation-circle-fill text-red-500 mt-0.5"></i>
                            <div>
                                <?php foreach ($formErrors as $error): ?>
                                    <div><?= e($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('restablecer-password.php?token=' . rawurlencode($token))) ?>" novalidate class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Nueva contraseña</label>
                        <input
                            type="password"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition"
                            id="password"
                            name="password"
                            placeholder="Mínimo 8 caracteres"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <div>
                        <label for="password_confirmacion" class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar contraseña</label>
                        <input
                            type="password"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition"
                            id="password_confirmacion"
                            name="password_confirmacion"
                            placeholder="Repite la contraseña"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 px-4 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold rounded-xl shadow-md shadow-teal-700/20 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-all cursor-pointer"
                    >
                        Actualizar contraseña
                    </button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
