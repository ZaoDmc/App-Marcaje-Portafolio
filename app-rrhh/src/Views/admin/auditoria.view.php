<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Mensajes de feedback -->
    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <div class="font-bold">Se encontraron los siguientes problemas:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Panel de Filtros -->
    <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
            <i class="bi bi-funnel text-blue-600 text-lg"></i>
            <h2 class="text-base font-bold text-slate-800">Filtros de auditoría</h2>
        </div>
        <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end" method="get" action="<?= e(url('admin/auditoria.php')) ?>">
            <div class="lg:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="desde">Desde</label>
                <input class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition" type="date" id="desde" name="desde" value="<?= e((string) $filtros['desde']) ?>">
            </div>
            <div class="lg:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="hasta">Hasta</label>
                <input class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition" type="date" id="hasta" name="hasta" value="<?= e((string) $filtros['hasta']) ?>">
            </div>
            <div class="lg:col-span-3 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="evento">Evento</label>
                <select class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white" id="evento" name="evento">
                    <option value="">Todos los eventos</option>
                    <?php foreach ($eventosCatalogo as $eventoCatalogo): ?>
                        <option value="<?= e($eventoCatalogo) ?>" <?= $filtros['evento'] === $eventoCatalogo ? 'selected' : '' ?>>
                            <?= e($eventoCatalogo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="tabla">Tabla</label>
                <select class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white" id="tabla" name="tabla">
                    <option value="">Todas</option>
                    <?php foreach ($tablasCatalogo as $tablaCatalogo): ?>
                        <option value="<?= e($tablaCatalogo) ?>" <?= $filtros['tabla'] === $tablaCatalogo ? 'selected' : '' ?>>
                            <?= e($tablaCatalogo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-2 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="q">Buscar</label>
                <input class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition" id="q" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Actor, IP o detalle" maxlength="100">
            </div>
            <div class="lg:col-span-1">
                <button class="w-full py-2 px-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center justify-center gap-1 cursor-pointer" type="submit">
                    <i class="bi bi-search"></i>
                    <span class="lg:hidden">Filtrar</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Métricas / Tarjetas de Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Eventos listados</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                    <i class="bi bi-list-check"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight"><?= e((string) $resumen['total']) ?></div>
            <div class="text-[11px] text-slate-400 mt-2">Registros encontrados</div>
        </section>

        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Seguridad</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-base">
                    <i class="bi bi-shield-lock"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight"><?= e((string) $resumen['seguridad']) ?></div>
            <div class="text-[11px] text-slate-400 mt-2">Inicios de sesión y accesos</div>
        </section>

        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Funcionales</span>
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-base">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight"><?= e((string) $resumen['funcionales']) ?></div>
            <div class="text-[11px] text-slate-400 mt-2">Operaciones del sistema</div>
        </section>

        <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Usuarios actores</span>
                <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center text-base">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight"><?= e((string) $resumen['usuarios']) ?></div>
            <div class="text-[11px] text-slate-400 mt-2">Distintos involucrados</div>
        </section>
    </div>

    <!-- Tabla de Eventos de Auditoría -->
    <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-shield-check text-blue-600"></i>
                    <span>Trazabilidad y registro de eventos</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Se muestran hasta 200 eventos por consulta ordenada cronológicamente</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                Solo lectura
            </span>
        </div>

        <?php if ($eventos === []): ?>
            <div class="p-10 text-center text-slate-500 space-y-2">
                <i class="bi bi-inbox text-3xl text-slate-400 block"></i>
                <p class="font-medium text-sm">No hay eventos para los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-xs uppercase font-bold text-slate-600 tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Fecha</th>
                            <th scope="col" class="px-4 py-3.5">Evento</th>
                            <th scope="col" class="px-4 py-3.5">Actor</th>
                            <th scope="col" class="px-4 py-3.5">Tabla</th>
                            <th scope="col" class="px-4 py-3.5">ID Reg.</th>
                            <th scope="col" class="px-4 py-3.5">IP</th>
                            <th scope="col" class="px-4 py-3.5">Valores y Detalles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($eventos as $evento): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                    <?= e(auditoria_admin_formatear_fecha_hora((string) $evento['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-xs"><?= e((string) $evento['evento']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= e((string) ($evento['rol_contexto_nombre'] ?? 'Sin rol')) ?></div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                                    <?= e((string) ($evento['actor_email'] ?? 'Sistema/CLI')) ?>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                                    <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]"><?= e((string) ($evento['tabla_afectada'] ?? 'No registrada')) ?></code>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap font-mono">
                                    <?= e((string) ($evento['id_registro_afectado'] ?? '')) ?>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap font-mono">
                                    <?= e((string) $evento['ip']) ?>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 max-w-xs">
                                    <details class="cursor-pointer group">
                                        <summary class="font-semibold text-blue-600 group-hover:underline list-none flex items-center gap-1">
                                            <span>Ver detalles</span>
                                            <i class="bi bi-chevron-down text-[10px]"></i>
                                        </summary>
                                        <div class="mt-2 p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1.5 text-[11px] font-mono overflow-x-auto">
                                            <div><strong class="text-slate-700">Anteriores:</strong> <?= e(auditoria_admin_resumir_valor($evento['valores_anteriores'] ?? null)) ?></div>
                                            <div><strong class="text-slate-700">Nuevos:</strong> <?= e(auditoria_admin_resumir_valor($evento['valores_nuevos'] ?? null)) ?></div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
