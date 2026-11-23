<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Header del panel -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm rrhh-theme-panel-border">
        <div class="rrhh-non-interactive select-none pointer-events-none">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-1">Contexto activo</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 rrhh-theme-role-pill">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> SUPERVISOR
            </span>
        </div>
        <a class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-sm shadow-blue-600/20 transition-all cursor-pointer" href="<?= e(url('supervisor/nuevo-practicante.php')) ?>">
            <i class="bi bi-person-plus"></i> Crear practicante
        </a>
    </div>

    <!-- Mensajes de advertencia o error del dashboard -->
    <?php if ($dashboardErrores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <?php foreach ($dashboardErrores as $error): ?>
                    <div><?= e($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Métricas principales / KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- 1. Practicantes activos -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Practicantes activos</span>
                    <!-- Apliqué el acento del tema también al icono de la métrica. -->
                    <div class="rrhh-dashboard-icon w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= e((string) $metricas['practicantes_activos']) ?>
                </div>
            </div>
            <div class="text-[11px] text-slate-400 mt-2 font-medium">
                <?= e((string) $metricas['practicas_activas']) ?> prácticas en curso
            </div>
        </section>

        <!-- 2. Horas últimos 30 días -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Horas últimos 30 días</span>
                    <div class="rrhh-dashboard-icon w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-base">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= e(formatear_horas_enteras($metricas['horas_30_dias'])) ?> <span class="text-sm font-semibold text-slate-500">hrs</span>
                </div>
            </div>
            <div class="text-[11px] text-teal-700 mt-2 font-medium">
                <?= e((string) $metricas['avance_global']) ?>% avance global
            </div>
        </section>

        <!-- 3. Pendientes revisión -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pendientes revisión</span>
                    <div class="rrhh-dashboard-icon w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center text-base">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= e((string) $metricas['pendientes_revision']) ?>
                </div>
            </div>
            <div class="text-[11px] text-slate-400 mt-2 font-medium">Jornadas por resolver</div>
        </section>

        <!-- 4. Ausencias esperadas -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ausencias esperadas</span>
                    <div class="rrhh-dashboard-icon w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-base">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">
                    <?= e((string) $metricas['ausencias_esperadas']) ?>
                </div>
            </div>
            <div class="text-[11px] text-slate-400 mt-2 font-medium">Generadas para revisión</div>
        </section>
    </div>

    <!-- Módulos de Acceso Rápido del Supervisor -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Crear practicante -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <!-- Unifiqué los iconos de acceso rápido con el color principal del tema elegido. -->
                <div class="rrhh-dashboard-icon w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Crear practicante</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Pre-registro de practicantes y generación de activación inicial.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition cursor-pointer" href="<?= e(url('supervisor/nuevo-practicante.php')) ?>">
                Abrir formulario
            </a>
        </section>

        <!-- Practicantes -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="rrhh-dashboard-icon w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-people"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Practicantes</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Seguimiento, fichas y actualización de datos permitidos.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-blue-600 hover:text-blue-700 text-slate-700 transition" href="<?= e(url('supervisor/practicantes.php')) ?>">
                Ver practicantes
            </a>
        </section>

        <!-- Asistencia -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="rrhh-dashboard-icon w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Asistencia</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Correcciones, observaciones y revisión de jornadas diarias.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-blue-600 hover:text-blue-700 text-slate-700 transition" href="<?= e(url('supervisor/asistencia.php')) ?>">
                Revisar jornadas
            </a>
        </section>

        <!-- Permisos -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="rrhh-dashboard-icon w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-envelope-paper"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Permisos</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Revisión y resolución de solicitudes de ausencia y recuperación.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-blue-600 hover:text-blue-700 text-slate-700 transition" href="<?= e(url('supervisor/permisos.php')) ?>">
                Gestionar permisos
            </a>
        </section>

        <!-- Reportes -->
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="rrhh-dashboard-icon w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 text-lg">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                </div>
                <h2 class="text-base font-bold text-slate-800">Reportes</h2>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Resumen de asistencia, horas acumuladas y avance de prácticas.</p>
            </div>
            <a class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 hover:border-blue-600 hover:text-blue-700 text-slate-700 transition" href="<?= e(url('supervisor/reportes.php')) ?>">
                Generar reportes
            </a>
        </section>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
