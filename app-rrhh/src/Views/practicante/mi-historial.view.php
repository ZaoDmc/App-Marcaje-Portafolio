<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Mensajes de error -->
    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <div class="font-bold">Se encontraron los siguientes problemas:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($practicaSeleccionada !== null && $resumen !== null): ?>
        <!-- Panel de Filtros -->
        <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                <i class="bi bi-funnel text-teal-700 text-lg"></i>
                <h2 class="text-base font-bold text-slate-800">Filtros de búsqueda</h2>
            </div>
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end" method="get" action="<?= e(url('practicante/mi-historial.php')) ?>">
                <div class="lg:col-span-5 space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="id_practica">Práctica</label>
                    <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="id_practica" name="id_practica">
                        <?php foreach ($practicas as $practica): ?>
                            <option value="<?= e((string) $practica['id_practica']) ?>" <?= (int) $practicaSeleccionada['id_practica'] === (int) $practica['id_practica'] ? 'selected' : '' ?>>
                                <?= e((string) $practica['tipo_practica_nombre']) ?> | <?= e(historial_fecha_formato((string) $practica['fecha_inicio_practica'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-3 space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="desde">Desde</label>
                    <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
                </div>
                <div class="lg:col-span-3 space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="hasta">Hasta</label>
                    <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
                </div>
                <div class="lg:col-span-1">
                    <button class="w-full py-2.5 px-4 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold text-sm rounded-xl shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer" type="submit">
                        <i class="bi bi-search"></i>
                        <span class="lg:hidden">Filtrar</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Tarjetas de Resumen / KPIs de Horas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- 1. Horas realizadas -->
            <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Horas realizadas</span>
                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-base">
                            <i class="bi bi-clock-check"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <?= e(formatear_horas_enteras($resumen['horas_realizadas'])) ?> <span class="text-sm font-semibold text-slate-500">hrs</span>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">Acumuladas validadas</div>
            </section>

            <!-- 2. Horas restantes -->
            <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Horas restantes</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <?= e(formatear_horas_enteras($resumen['horas_restantes'])) ?> <span class="text-sm font-semibold text-slate-500">hrs</span>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">Para completar la práctica</div>
            </section>

            <!-- 3. Avance -->
            <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Avance</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <?= e((string) $resumen['porcentaje_avance']) ?><span class="text-sm font-semibold text-slate-500">%</span>
                    </div>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
                    <div class="bg-teal-600 h-2 rounded-full transition-all duration-500" style="width: <?= e((string) min(100.0, (float) $resumen['porcentaje_avance'])) ?>%"></div>
                </div>
            </section>

            <!-- 4. Total de jornadas -->
            <section class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jornadas registradas</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
                            <i class="bi bi-calendar3"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 tracking-tight">
                        <?= e((string) $resumen['total_jornadas']) ?>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">Días con actividad</div>
            </section>
        </div>

        <!-- Ficha de Práctica Seleccionada -->
        <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6">
            <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-3">
                <i class="bi bi-info-circle text-teal-700 text-lg"></i>
                <h2 class="text-base font-bold text-slate-800">Detalles de la práctica seleccionada</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Tipo de práctica</div>
                    <div class="font-semibold text-slate-800 text-sm"><?= e((string) $practicaSeleccionada['tipo_practica_nombre']) ?></div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Estado actual</div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                            <?= e((string) $practicaSeleccionada['estado_practica_nombre']) ?>
                        </span>
                    </div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha de inicio</div>
                    <div class="font-semibold text-slate-800 text-sm"><?= e(historial_fecha_formato((string) $practicaSeleccionada['fecha_inicio_practica'])) ?></div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Horas requeridas</div>
                    <div class="font-semibold text-slate-800 text-sm"><?= e(formatear_horas_enteras($practicaSeleccionada['horas_requeridas'])) ?> hrs</div>
                </div>
            </div>
        </section>

        <!-- Tabla de Jornadas -->
        <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-table text-teal-700"></i>
                        <span>Jornadas del período</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Mostrando registros según el filtro de fechas aplicado</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
                        Válidas: <?= e((string) $resumen['jornadas_validas']) ?>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-semibold">
                        Observadas: <?= e((string) $resumen['jornadas_observadas']) ?>
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                        Incompletas: <?= e((string) $resumen['jornadas_incompletas']) ?>
                    </span>
                </div>
            </div>

            <?php if ($jornadas === []): ?>
                <div class="p-10 text-center text-slate-500 space-y-2">
                    <i class="bi bi-calendar-x text-3xl text-slate-400 block"></i>
                    <p class="font-medium text-sm">No hay jornadas registradas en el período seleccionado.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <!-- Adapté esta tabla para mostrar campos etiquetados sin desplazamiento horizontal en practicantes. -->
                    <table role="table" class="rrhh-responsive-table w-full text-left text-sm text-slate-700">
                        <thead role="rowgroup" class="bg-slate-50 border-b border-slate-200/80 text-xs uppercase font-bold text-slate-600 tracking-wider">
                            <tr role="row">
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Fecha</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Estado</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Entrada</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Salida</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Horas</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Marcas vigentes</th>
                                <th role="columnheader" scope="col" class="px-4 py-3.5">Observación</th>
                            </tr>
                        </thead>
                        <tbody role="rowgroup" class="divide-y divide-slate-100">
                            <?php foreach ($jornadas as $jornada): ?>
                                <?php
                                $marcas = $marcasPorJornada[(int) $jornada['id_jornada']] ?? [];
                                $horasBase = (float) ($jornada['horas_calculadas'] ?? 0);
                                $horasExtra = $jornada['horas_extra_aprobadas'] !== null ? (float) $jornada['horas_extra_aprobadas'] : 0.0;
                                $horasTotales = round($horasBase + $horasExtra, 2);
                                $badgeStyle = match ((string) $jornada['estado_jornada']) {
                                    'VALIDA' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'OBSERVADA' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'INCOMPLETA' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    'PENDIENTE_REVISION' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    // Mostré las revisadas con un indicador de cierre sin observación pendiente.
                                    'REVISADA' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'CORREGIDA' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                                ?>
                                <tr role="row" class="hover:bg-slate-50/70 transition">
                                    <td role="cell" data-label="Fecha" class="px-4 py-3.5 font-medium text-slate-900 whitespace-nowrap"><div class="rrhh-cell-value">
                                        <?= e(historial_fecha_formato((string) $jornada['fecha'])) ?>
                                    </div></td>
                                    <td role="cell" data-label="Estado" class="px-4 py-3.5 whitespace-nowrap"><div class="rrhh-cell-value">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $badgeStyle ?>">
                                            <?= e((string) $jornada['estado_jornada']) ?>
                                        </span>
                                    </div></td>
                                    <td role="cell" data-label="Entrada" class="px-4 py-3.5 whitespace-nowrap text-slate-600"><div class="rrhh-cell-value">
                                        <?= e(historial_fecha_hora_formato($jornada['hora_entrada'] ?? null)) ?>
                                    </div></td>
                                    <td role="cell" data-label="Salida" class="px-4 py-3.5 whitespace-nowrap text-slate-600"><div class="rrhh-cell-value">
                                        <?= e(historial_fecha_hora_formato($jornada['hora_salida'] ?? null)) ?>
                                    </div></td>
                                    <td role="cell" data-label="Horas" class="px-4 py-3.5 whitespace-nowrap"><div class="rrhh-cell-value">
                                        <div class="font-bold text-slate-900">
                                            <?= e(formatear_horas_enteras($horasTotales)) ?> hrs
                                        </div>
                                        <?php if ($horasExtra > 0): ?>
                                            <div class="text-xs text-emerald-600 font-medium">
                                                (+<?= e(formatear_horas_extra_minutos($horasExtra)) ?>)
                                            </div>
                                        <?php endif; ?>
                                    </div></td>
                                    <td role="cell" data-label="Marcas vigentes" class="px-4 py-3.5 text-xs text-slate-600 max-w-xs truncate" title="<?= e(historial_marcas_texto($marcas)) ?>"><div class="rrhh-cell-value">
                                        <?= e(historial_marcas_texto($marcas)) ?>
                                    </div></td>
                                    <td role="cell" data-label="Observación" class="px-4 py-3.5 text-xs text-slate-500 max-w-xs truncate" title="<?= e((string) ($jornada['observacion'] ?? 'Sin observación')) ?>"><div class="rrhh-cell-value">
                                        <?= e((string) ($jornada['observacion'] ?? 'Sin observación')) ?>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
