<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 // Trasladé la plantilla desde Services/ReportesService.php; recibe los datos preparados por el controlador. ?>

        <?php if (!empty($mensajeExito)): ?>
            <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800 mb-4" role="alert">
                <?= e($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <?php if ($errores !== []): ?>
            <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($detallePractica !== null): ?>
            <?php // El controlador preparó los acumulados e incidencias antes del HTML. ?>

            <!-- Cabecera de navegación de vuelta -->
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <a class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100 !px-3 !py-1.5 !text-xs" href="<?= e(url($rutaActual . '?desde=' . urlencode((string) $filtros['desde']) . '&hasta=' . urlencode((string) $filtros['hasta']) . ($filtros['q'] !== '' ? '&q=' . urlencode((string) $filtros['q']) : ''))) ?>">
                    &larr; Volver al listado de reportes
                </a>
                <span class="rrhh-badge bg-slate-50 text-slate-700 border">Detalle de practicante e incidencias</span>
            </div>

            <!-- Ficha Resumen del Practicante -->
            <div class="rrhh-card border mb-4 shadow-none bg-slate-50">
                <div class="border-b border-slate-200 px-4 py-3 bg-white py-2 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg mb-0 text-slate-900 font-bold"><?= e($nombreCompletoDetalle) ?></h2>
                        <div class="text-slate-500 text-sm"><?= e((string) $detallePractica['email']) ?> &bull; RUT: <?= e($rutCompletoDetalle) ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rrhh-button !py-1.5 !px-3 !text-xs border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center gap-1.5 font-medium shadow-sm" data-ui-open="modalModificarHoras">
                            <i class="bi bi-pencil-square text-blue-600"></i>
                            <span>Modificar horas</span>
                        </button>
                        <span class="rrhh-badge <?= e(rrhh_tone(e($detallePractica['estado_practica_nombre'] === 'ACTIVA' ? 'success' : 'secondary'))) ?> text-base">
                            <?= e((string) $detallePractica['estado_practica_nombre']) ?>
                        </span>
                    </div>
                </div>
                <div class="p-4 p-4">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Carrera</div>
                            <div class="font-semibold"><?= e((string) $detallePractica['carrera']) ?></div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Institución / Sede</div>
                            <div><?= e((string) $detallePractica['institucion']) ?> &bull; <?= e((string) $detallePractica['nombre_sede']) ?></div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Área actual asignada</div>
                            <div><?= e((string) ($detallePractica['area_nombre'] ?? 'Sin área asignada')) ?></div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Horas requeridas</div>
                            <!-- Formateo visual de horas requeridas redondeadas a entero cerrado -->
                            <div><strong><?= e(formatear_horas_enteras($horasReq)) ?> hrs</strong> (<?= e(formatear_horas_enteras($detallePractica['horas_previas'] ?? '0')) ?> previas)</div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Horas acumuladas históricas</div>
                            <!-- Formateo visual cerrado de avance de horas (ej: 7 / 360 hrs) -->
                            <div class="text-emerald-700 font-bold"><?= e(formatear_horas_avance($horasTotalesAcum, $horasReq)) ?> hrs (<?= e((string) $avanceDetalle) ?>%)</div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <div class="text-sm text-slate-500">Horas en el periodo seleccionado</div>
                            <!-- Formateo visual de horas en periodo redondeadas -->
                            <div class="text-blue-700 font-bold"><?= e(formatear_horas_enteras($horasPeriodoDet)) ?> hrs</div>
                        </div>
                    </div>

                    <div class="rrhh-progress mt-4" style="height: 10px;">
                        <div class="rrhh-progress-bar bg-emerald-600" role="progressbar" style="width: <?= e((string) $avanceDetalle) ?>%" aria-valuenow="<?= e((string) $avanceDetalle) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <?php if (!empty($ultimaModificacionHoras)): ?>
                        <div class="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-500 flex flex-wrap items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5">
                                <i class="bi bi-clock-history text-slate-400"></i>
                                <span>Última modificación de horas: <strong><?= e($ultimaModificacionHoras['fecha_hora']) ?></strong> por <strong><?= e($ultimaModificacionHoras['responsable']) ?></strong><?php if (!empty($ultimaModificacionHoras['nombre']) && !empty($ultimaModificacionHoras['email'])): ?> <span class="text-slate-400 font-normal">(<?= e($ultimaModificacionHoras['email']) ?>)</span><?php endif; ?> (<?= e($ultimaModificacionHoras['rol']) ?>).</span>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Distinguí el resumen académico global de las métricas de asistencia filtradas por periodo. -->
            <section class="rrhh-card p-4 mb-4 space-y-2" aria-label="Notas semanales">
                <?php $resumenNota = $resumenNotaReporte; require VIEWS_PATH . '/components/notas-resumen.php'; ?>
                <p class="text-xs text-slate-500">Resumen de toda la práctica, independiente del periodo de asistencia seleccionado.</p>
            </section>

            <!-- Tarjetas de métricas de incidencias del periodo -->
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-6 md:col-span-3">
                    <section class="metric-card p-4 h-full border rounded bg-white">
                        <div class="text-slate-500 text-sm">Jornadas registradas</div>
                        <strong class="text-xl block mb-0"><?= $totalJornadasDet ?></strong>
                    </section>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <section class="metric-card p-4 h-full border rounded bg-white">
                        <div class="text-slate-500 text-sm">Con observaciones</div>
                        <strong class="text-xl block mb-0 text-amber-700"><?= $jornadasConObs ?></strong>
                    </section>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <section class="metric-card p-4 h-full border rounded bg-white">
                        <div class="text-slate-500 text-sm">Ausencias esperadas</div>
                        <strong class="text-xl block mb-0 text-cyan-700"><?= $jornadasAusencias ?></strong>
                    </section>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <section class="metric-card p-4 h-full border rounded bg-white">
                        <div class="text-slate-500 text-sm">Atrasos / Horas extra</div>
                        <strong class="text-xl block mb-0 text-blue-700"><?= $jornadasAtrasos ?> / <?= $jornadasHorasExtra ?></strong>
                    </section>
                </div>
            </div>

            <?php // Mostré minutos de cada libro y distinguí el periodo consultado del saldo vigente global. ?>
            <?php if ($tiemposDetalle !== null): ?>
            <section class="rrhh-card p-4 mb-4 space-y-2" aria-label="Desglose de recuperación y compensación">
                <h2 class="text-lg font-bold text-blue-900">Recuperación y compensación</h2>
                <p class="text-sm">En el periodo: recuperación validada <strong><?= (int)$tiemposDetalle['recuperados'] ?> min</strong> · Compensación aplicada <strong><?= (int)$tiemposDetalle['compensados'] ?> min</strong> · Tiempo excepcional aprobado <strong><?= (int)$tiemposDetalle['excepcionales'] ?> min</strong>.</p>
                <p class="text-sm">A la fecha de consulta, sin limitar al periodo: saldo excepcional disponible <strong><?= (int)$tiemposDetalle['saldo'] ?> min</strong> · Solicitudes y canjes pendientes de revisión <strong><?= (int)$tiemposDetalle['pendientes'] ?></strong>. <a class="text-blue-700 underline" href="<?= e(url(strtolower($rolEsperado).'/permisos.php')) ?>">Revisar en Permisos</a></p>
                <p class="text-sm text-slate-600">Las recuperaciones validadas y compensaciones aplicadas ya están incluidas en las horas de sus jornadas. No vuelvas a sumarlas. Aprobar tiempo excepcional habilita un canje; no aumenta las horas acumuladas.</p>
            </section>
            <?php endif; ?>
            <!-- Tabla de desglose de jornadas y fechas con incidencias -->
            <section class="panel p-4 md:p-6 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div class="section-title">Historial de jornadas e incidencias en el periodo</div>
                    <div class="text-slate-500 text-sm">Desde <?= e(reportes_gestion_formatear_fecha((string) $filtros['desde'])) ?> hasta <?= e(reportes_gestion_formatear_fecha((string) $filtros['hasta'])) ?></div>
                </div>

                <?php if ($jornadasDetalle === []): ?>
                    <p class="text-slate-500 mb-0">No se registran jornadas de asistencia en el periodo consultado.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Entrada / Salida</th>
                                    <th>Flujo de marcas</th>
                                    <th>Horas base</th>
                                    <th>Horas extra</th>
                                    <th>Incidencias / Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jornadasDetalle as $jornada): ?>
                                    <?php
                                        // Dejé de destacar como pendiente el historial de jornadas revisadas/corregidas.
                                        $tieneObservacion = asistencia_jornada_observacion_pendiente($jornada);
                                        $esAusencia = (int) ($jornada['flag_ausencia_esperada'] ?? 0) === 1;
                                        $tieneAtraso = (int) ($jornada['flag_atraso'] ?? 0) === 1;
                                        $tieneSalidaAnt = (int) ($jornada['flag_salida_anticipada'] ?? 0) === 1;
                                        $tieneExtra = (int) ($jornada['flag_horas_extra'] ?? 0) === 1 || (float) ($jornada['horas_extra_aprobadas'] ?? 0) > 0;
                                    ?>
                                    <tr class="<?= $tieneObservacion ? 'bg-amber-50' : '' ?>">
                                        <td>
                                            <strong><?= e(reportes_gestion_formatear_fecha((string) $jornada['fecha'])) ?></strong>
                                        </td>
                                        <td>
                                            <span class="rrhh-badge <?= e(rrhh_tone(match ($jornada['estado_jornada']) {
                                                'VALIDA' => 'success',
                                                'OBSERVADA' => 'warning',
                                                'REVISADA' => 'success',
                                                'CORREGIDA' => 'primary',
                                                'INCOMPLETA' => 'secondary',
                                                'PENDIENTE_REVISION' => 'info',
                                                default => 'light'
                                            })) ?>">
                                                <?= e((string) $jornada['estado_jornada']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                In: <?= !empty($jornada['hora_entrada']) ? date('H:i', strtotime((string) $jornada['hora_entrada'])) : '--:--' ?>
                                                <br>
                                                Out: <?= !empty($jornada['hora_salida']) ? date('H:i', strtotime((string) $jornada['hora_salida'])) : '--:--' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-sm text-slate-500"><?= e((string) ($jornada['flujo_marcas'] ?? 'Sin marcas')) ?></div>
                                        </td>
                                        <td>
                                            <!-- Horas base redondeadas a entero cerrado -->
                                            <strong><?= e(formatear_horas_enteras($jornada['horas_calculadas'])) ?> hrs</strong>
                                        </td>
                                        <td>
                                            <?php if ((float) ($jornada['horas_extra_aprobadas'] ?? 0) > 0): ?>
                                                <!-- Formateo de horas extra aprobadas al minuto más próximo -->
                                                <span class="text-emerald-700 font-bold">+<?= e(formatear_horas_extra_minutos($jornada['horas_extra_aprobadas'])) ?></span>
                                            <?php elseif ((int) ($jornada['flag_horas_extra'] ?? 0) === 1): ?>
                                                <span class="rrhh-badge bg-blue-100 text-blue-800">Extra pendiente</span>
                                            <?php else: ?>
                                                <span class="text-slate-500">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap gap-1 mb-1">
                                                <?php if ($esAusencia): ?>
                                                    <span class="rrhh-badge bg-cyan-100 text-cyan-900">Ausencia esperada</span>
                                                <?php endif; ?>
                                                <?php if ($tieneAtraso): ?>
                                                    <span class="rrhh-badge bg-amber-100 text-amber-900">Atraso</span>
                                                <?php endif; ?>
                                                <?php if ($tieneSalidaAnt): ?>
                                                    <span class="rrhh-badge bg-slate-100 text-slate-700">Salida anticipada</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($jornada['observacion'])): ?>
                                                <div class="text-sm <?= $tieneObservacion ? 'text-red-700 font-semibold' : 'text-slate-500' ?>"><?= e((string) $jornada['observacion']) ?></div>
                                            <?php elseif (!$tieneObservacion && !$esAusencia && !$tieneAtraso && !$tieneSalidaAnt): ?>
                                                <span class="text-sm text-emerald-700">✓ Sin incidencias</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Modal para modificar horas de la práctica -->
            <dialog data-ui-dialog class="rrhh-dialog text-left" id="modalModificarHoras" aria-labelledby="modalModificarHorasTitulo">
                <div class="rrhh-practicante-modal-header flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 id="modalModificarHorasTitulo" class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-clock-history text-blue-600"></i>
                        <span>Modificar horas de la práctica</span>
                    </h3>
                    <button type="button" class="rrhh-practicante-modal-close" data-ui-close aria-label="Cerrar ventana">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form method="post" action="<?= e(url($rutaActual . '?id_practica=' . (int) $idPracticaSeleccionada . '&desde=' . urlencode((string) $filtros['desde']) . '&hasta=' . urlencode((string) $filtros['hasta']) . ($filtros['q'] !== '' ? '&q=' . urlencode((string) $filtros['q']) : ''))) ?>" class="p-4 space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token('csrf_reportes_horas')) ?>">
                    <input type="hidden" name="accion" value="modificar_horas_practica">
                    <input type="hidden" name="id_practica" value="<?= (int) $idPracticaSeleccionada ?>">

                    <?php if (!empty($ultimaModificacionHoras)): ?>
                        <div class="bg-slate-50 border border-slate-200 rounded p-3 text-xs text-slate-600 flex items-start gap-2">
                            <i class="bi bi-info-circle text-blue-600 mt-0.5"></i>
                            <div>
                                <span class="font-semibold text-slate-700">Último cambio registrado:</span>
                                <div>El <strong><?= e($ultimaModificacionHoras['fecha_hora']) ?></strong> por <strong><?= e($ultimaModificacionHoras['responsable']) ?></strong><?php if (!empty($ultimaModificacionHoras['nombre']) && !empty($ultimaModificacionHoras['email'])): ?> <span class="text-slate-400 font-normal">(<?= e($ultimaModificacionHoras['email']) ?>)</span><?php endif; ?> (<?= e($ultimaModificacionHoras['rol']) ?>).</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="rrhh-label" for="horas_requeridas">Horas requeridas (máx. 1000 hrs)</label>
                        <input class="rrhh-input" type="number" id="horas_requeridas" name="horas_requeridas" min="1" max="1000" step="1" value="<?= e(formatear_horas_enteras($horasReq)) ?>" required>
                        <p class="rrhh-help text-xs text-slate-500 mt-1">Total de horas exigidas para aprobar la práctica según su institución.</p>
                    </div>

                    <div>
                        <label class="rrhh-label" for="horas_acumuladas">Horas acumuladas históricas (máx. 1000 hrs)</label>
                        <input class="rrhh-input" type="number" id="horas_acumuladas" name="horas_acumuladas" min="0" max="1000" step="0.5" value="<?= e((string) $horasTotalesAcum) ?>" required>
                        <p class="rrhh-help text-xs text-slate-500 mt-1">Total de horas que el estudiante lleva acumuladas a la fecha.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100 !px-3 !py-1.5" data-ui-close>Cancelar</button>
                        <button type="submit" class="rrhh-button bg-brand-blue text-white hover:bg-blue-700 !px-4 !py-1.5 flex items-center gap-1.5 font-semibold">
                            <i class="bi bi-check2"></i>
                            <span>Guardar horas</span>
                        </button>
                    </div>
                </form>
            </dialog>

        <?php else: ?>

            <!-- Vista general de reportes con filtros y métricas globales -->
            <section class="panel p-4 md:p-6 mb-4">
                <div class="section-title mb-4">Filtros</div>
                <form class="grid grid-cols-12 gap-4 items-end" method="get" action="<?= e(url($rutaActual)) ?>">
                    <div class="col-span-12 md:col-span-2">
                        <label class="rrhh-label" for="desde">Desde</label>
                        <input class="rrhh-input" type="date" id="desde" name="desde" value="<?= e((string) $filtros['desde']) ?>">
                    </div>
                    <div class="col-span-12 md:col-span-2">
                        <label class="rrhh-label" for="hasta">Hasta</label>
                        <input class="rrhh-input" type="date" id="hasta" name="hasta" value="<?= e((string) $filtros['hasta']) ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="rrhh-label" for="estado_practica">Estado practica</label>
                        <select class="rrhh-select" id="estado_practica" name="estado_practica">
                            <option value="">Todos</option>
                            <?php foreach ($estadosPractica as $estadoPractica): ?>
                                <option value="<?= e($estadoPractica) ?>" <?= $filtros['estado_practica'] === $estadoPractica ? 'selected' : '' ?>>
                                    <?= e($estadoPractica) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="rrhh-label" for="q">Practicante</label>
                        <input class="rrhh-input" id="q" name="q" value="<?= e((string) $filtros['q']) ?>" maxlength="100">
                    </div>
                    <div class="col-span-12 md:col-span-2">
                        <button class="rrhh-button bg-brand-blue text-white hover:bg-blue-700 w-full" type="submit">Filtrar</button>
                    </div>
                </form>
            </section>

            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 md:col-span-6 xl:col-span-3">
                    <section class="metric-card p-4 h-full">
                        <div class="text-slate-500 text-sm">Practicas filtradas</div>
                        <strong class="text-xl block mb-0"><?= e((string) $resumen['practicas']) ?></strong>
                    </section>
                </div>
                <div class="col-span-12 md:col-span-6 xl:col-span-3">
                    <section class="metric-card p-4 h-full">
                        <div class="text-slate-500 text-sm">Horas del periodo</div>
                        <!-- Formateo visual de horas del periodo redondeadas a entero cerrado -->
                        <strong class="text-xl block mb-0"><?= e(formatear_horas_enteras($resumen['horas_periodo'])) ?> hrs</strong>
                    </section>
                </div>
                <div class="col-span-12 md:col-span-6 xl:col-span-3">
                    <section class="metric-card p-4 h-full">
                        <div class="text-slate-500 text-sm">Avance global</div>
                        <strong class="text-xl block mb-2"><?= e((string) $resumen['avance_promedio']) ?>%</strong>
                        <div class="rrhh-progress" role="progressbar" aria-valuenow="<?= e((string) $resumen['avance_promedio']) ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="rrhh-progress-bar" style="width: <?= e((string) $resumen['avance_promedio']) ?>%"></div>
                        </div>
                    </section>
                </div>
                <div class="col-span-12 md:col-span-6 xl:col-span-3">
                    <section class="metric-card p-4 h-full">
                        <div class="text-slate-500 text-sm">Pendientes / ausencias</div>
                        <strong class="text-xl block mb-0"><?= e((string) $resumen['pendientes_revision']) ?> / <?= e((string) $resumen['ausencias_esperadas']) ?></strong>
                    </section>
                </div>
            </div>

            <section class="panel p-4 md:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <div class="section-title">Avance por practicante</div>
                        <div class="text-slate-500 text-sm">
                            Periodo <?= e(reportes_gestion_formatear_fecha((string) $filtros['desde'])) ?> a <?= e(reportes_gestion_formatear_fecha((string) $filtros['hasta'])) ?>
                        </div>
                    </div>
                    <span class="rrhh-badge bg-slate-50 text-slate-700 border">Solo lectura</span>
                </div>

                <?php if ($filas === []): ?>
                    <p class="text-slate-500 mb-0">No hay practicas activas para los filtros seleccionados.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Practicante</th>
                                    <th>Practica</th>
                                    <th>Area</th>
                                    <th>Horas total</th>
                                    <th>Avance</th>
                                    <th>Periodo</th>
                                    <th>Jornadas</th>
                                    <th>Alertas</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filas as $fila): ?>
                                    <?php
                                    $horasRequeridas = (float) ($fila['horas_requeridas'] ?? 0);
                                    $horasTotales = round((float) ($fila['horas_totales'] ?? 0), 2);
                                    $avance = $horasRequeridas > 0 ? min(100.0, round(($horasTotales / $horasRequeridas) * 100, 1)) : 0.0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= e(reportes_gestion_nombre_practicante($fila)) ?></strong>
                                            <div class="text-slate-500 text-sm"><?= e((string) $fila['email']) ?></div>
                                        </td>
                                        <td>
                                            <?= e((string) ($fila['tipo_practica_nombre'] ?? 'Sin tipo')) ?>
                                            <div class="text-slate-500 text-sm"><?= e((string) $fila['estado_practica_nombre']) ?></div>
                                        </td>
                                        <td><?= e((string) $fila['area_nombre']) ?></td>
                                        <td>
                                            <!-- Formato de avance redondeado a horas cerradas (ej: 7 / 360) -->
                                            <?= e(formatear_horas_avance($horasTotales, $horasRequeridas)) ?>
                                        </td>
                                        <td>
                                            <strong><?= e((string) $avance) ?>%</strong>
                                            <div class="rrhh-progress mt-1" role="progressbar" aria-valuenow="<?= e((string) $avance) ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div class="rrhh-progress-bar" style="width: <?= e((string) $avance) ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <!-- Horas en el periodo redondeadas -->
                                            <?= e(formatear_horas_enteras($fila['horas_periodo'])) ?> h
                                            <div class="text-slate-500 text-sm"><?= e((string) $fila['jornadas_periodo']) ?> jornadas</div>
                                        </td>
                                        <td>
                                            <span class="text-emerald-700"><?= e((string) $fila['jornadas_validas']) ?> V</span>
                                            <span class="text-amber-700"><?= e((string) $fila['jornadas_observadas']) ?> O</span>
                                            <span class="text-slate-500"><?= e((string) $fila['jornadas_incompletas']) ?> I</span>
                                        </td>
                                        <td>
                                            <span class="rrhh-badge bg-cyan-100 text-cyan-900"><?= e((string) $fila['jornadas_pendientes']) ?> revision</span>
                                            <span class="rrhh-badge bg-amber-100 text-amber-900"><?= e((string) $fila['ausencias_esperadas']) ?> ausencias</span>
                                            <span class="rrhh-badge bg-slate-100 text-slate-700"><?= e((string) $fila['horas_extra_pendientes']) ?> extra</span>
                                        </td>
                                        <td class="text-right">
                                            <a class="rrhh-button border-blue-600 text-blue-700 hover:bg-blue-50 !px-3 !py-1.5 !text-xs" href="<?= e(url($rutaActual . '?id_practica=' . (int) $fila['id_practica'] . '&desde=' . urlencode((string) $filtros['desde']) . '&hasta=' . urlencode((string) $filtros['hasta']) . ($filtros['q'] !== '' ? '&q=' . urlencode((string) $filtros['q']) : ''))) ?>">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
