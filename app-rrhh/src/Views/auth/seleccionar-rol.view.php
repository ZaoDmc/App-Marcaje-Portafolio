<?php // Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
?>
<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seleccionar rol | <?= e(APP_NAME) ?></title>

<?php // Cargué Tailwind compilado e iconos locales, sin runtime de CDN.
    require_once VIEWS_PATH . '/components/ui.php';
    require VIEWS_PATH . '/components/styles.php'; ?>
</head>
<body class="min-h-full bg-gradient-to-br from-teal-500/10 via-slate-50 to-blue-500/10 text-slate-800 flex items-center justify-center p-4 sm:p-6 antialiased">
    <main class="w-full max-w-2xl mx-auto">
        <section class="bg-white border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/60 p-6 sm:p-8 md:p-10" aria-labelledby="role-title">
            <div class="text-center mb-8">
                <img src="<?= e(asset('brand/logo.png')) ?>" alt="<?= e(APP_NAME) ?>" class="h-16 w-auto mx-auto object-contain mb-3">
                <h1 id="role-title" class="text-xl sm:text-2xl font-bold text-slate-900">Seleccionar rol activo</h1>
                <p class="text-sm text-slate-500 mt-1">Elige el contexto con el que trabajarás en esta sesión.</p>
            </div>

            <?php if ($errors !== []): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm space-y-1" role="alert">
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

            <form method="post" action="<?= e(url('seleccionar-rol.php')) ?>" novalidate class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <!-- Cambié la clase parcial por variantes completas que Tailwind puede compilar. -->
                <div class="grid grid-cols-1 <?= count($rolesDisponibles) > 2 ? 'sm:grid-cols-3' : 'sm:grid-cols-2' ?> gap-4">
                    <?php foreach ($rolesDisponibles as $index => $rol): ?>
                        <?php
                        $nombreRol = (string) ($rol['nombre'] ?? '');
                        $detalle = selector_rol_detalle($nombreRol);
                        $idRol = (int) ($rol['id_rol'] ?? 0);
                        ?>
                        <label class="group relative flex flex-col p-5 rounded-xl border-2 border-slate-200 hover:border-teal-500 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/30 cursor-pointer transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-900 group-hover:text-teal-700 transition">
                                    <?= e($detalle['titulo']) ?>
                                </span>
                                <input
                                    class="w-4 h-4 text-teal-600 border-slate-300 focus:ring-teal-500 cursor-pointer"
                                    type="radio"
                                    name="id_rol"
                                    value="<?= e((string) $idRol) ?>"
                                    <?= $index === 0 ? 'checked' : '' ?>
                                    required
                                >
                            </div>
                            <span class="text-xs text-slate-500 leading-relaxed">
                                <?= e($detalle['descripcion']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button
                    type="submit"
                    class="w-full py-3.5 px-4 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold rounded-xl shadow-md shadow-teal-700/20 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-all cursor-pointer"
                >
                    Continuar
                </button>
            </form>
        </section>
    </main>
</body>
</html>
