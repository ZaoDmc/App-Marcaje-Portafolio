<?php require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Header del panel -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm rrhh-theme-panel-border rrhh-non-interactive select-none pointer-events-none">
        <div>
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-1">Contexto activo</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 rrhh-theme-role-pill">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> ADMIN
            </span>
        </div>
        <div>
            <p class="text-xs text-slate-500">Pre-registro de practicantes al sistema</p>
        </div>
    </div>

    <!-- Opciones de Pre-registro -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Cargar manualmente -->
        <a href="<?= e(url('admin/crear-practicante.php')) ?>" class="group block bg-white border border-slate-200/80 rounded-2xl p-6 sm:p-8 shadow-sm hover:shadow-md hover:border-blue-400 transition-all text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl mb-4 group-hover:scale-105 transition-transform">
                <i class="bi bi-person-plus"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition-colors mb-2">Cargar manualmente</h2>
            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Completa el formulario interactivo para pre-registrar un practicante de forma individual con asignación de institución y sede.
            </p>
            <span class="inline-flex items-center gap-1.5 mt-5 text-xs font-semibold text-blue-600 group-hover:underline">
                Iniciar registro individual <i class="bi bi-arrow-right"></i>
            </span>
        </a>

        <!-- Carga masiva -->
        <a href="<?= e(url('admin/practicantes-carga-masiva.php')) ?>" class="group block bg-white border border-slate-200/80 rounded-2xl p-6 sm:p-8 shadow-sm hover:shadow-md hover:border-teal-400 transition-all text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-3xl mb-4 group-hover:scale-105 transition-transform">
                <i class="bi bi-file-earmark-spreadsheet"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-900 group-hover:text-teal-700 transition-colors mb-2">Carga masiva</h2>
            <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Sube un archivo delimitado por comas (CSV) con la plantilla estándar para pre-registrar múltiples practicantes a la vez.
            </p>
            <span class="inline-flex items-center gap-1.5 mt-5 text-xs font-semibold text-teal-700 group-hover:underline">
                Subir archivo CSV <i class="bi bi-arrow-right"></i>
            </span>
        </a>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
