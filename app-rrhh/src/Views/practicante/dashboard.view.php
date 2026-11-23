<?php require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Header del panel -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm rrhh-theme-panel-border">
        <div class="rrhh-non-interactive select-none pointer-events-none">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-1">Contexto activo</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200 rrhh-theme-role-pill">
                <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span> PRACTICANTE
            </span>
        </div>
        <a class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold text-sm rounded-xl shadow-sm shadow-teal-700/20 transition-all cursor-pointer" href="<?= e(url('practicante/marcar-asistencia.php')) ?>">
            <i class="bi bi-clock-history"></i> Marcar asistencia
        </a>
    </div>

    <!-- Tarjetas de acceso directo -->
    <?php // Marqué los iconos de acceso para unificar sus fondos en el tema negro. ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- 1. Mis datos -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="practicante-dashboard-icon w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Mis datos</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Consulta y actualización de datos personales y ficha de práctica.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-teal-600 hover:text-teal-700 text-slate-700 transition" href="<?= e(url('practicante/mis-datos.php')) ?>">
                Abrir ficha
            </a>
        </section>

        <!-- 2. Asistencia -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="practicante-dashboard-icon w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Asistencia</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Registro diario de marcas de entrada, colación y salida de jornada.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-teal-600 hover:text-teal-700 text-slate-700 transition" href="<?= e(url('practicante/marcar-asistencia.php')) ?>">
                Registrar marca
            </a>
        </section>

        <!-- 3. Historial -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="practicante-dashboard-icon w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Historial</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Revisión de jornadas, horas realizadas acumuladas y horas restantes.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-teal-600 hover:text-teal-700 text-slate-700 transition" href="<?= e(url('practicante/mi-historial.php')) ?>">
                Ver avance
            </a>
        </section>

        <!-- 4. Permisos -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="practicante-dashboard-icon w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-envelope-paper"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Permisos</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Solicitud de permisos de ausencia y programación de recuperación de horas.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-teal-600 hover:text-teal-700 text-slate-700 transition" href="<?= e(url('practicante/permisos.php')) ?>">
                Gestionar permisos
            </a>
        </section>
    </div>
    <!-- Añadí un acceso a Notas para todos y una acción adicional solo cuando ADMIN ha designado al practicante líder vigente. -->
    <section class="rrhh-card p-4 sm:p-6 flex flex-wrap items-center justify-between gap-4" aria-labelledby="dashboard-notas-title">
        <div>
            <h2 id="dashboard-notas-title" class="text-base font-bold text-slate-800">Notas</h2>
            <p class="text-sm text-slate-500 mt-1">Consulta tus evaluaciones semanales una vez publicadas.</p>
            <?php if (!empty($mostrarNotasLider)): ?>
                <p class="text-sm text-slate-500 mt-1">Como líder, también puedes evaluar a los integrantes de tu área.</p>
            <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url('practicante/mis-notas.php')) ?>">Ver mis notas</a>
            <?php if (!empty($mostrarNotasLider)): ?>
                <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url('practicante/notas.php')) ?>">Evaluar mi área</a>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
