<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Feedback Banners -->
    <?php if ($mensajeExito !== null): ?>
        <?php // Reemplacé el banner propio por la alerta success de daisyUI tras registrar una marca con éxito. 
        ?>
        <div class="dui-alert dui-alert-success shadow-sm" role="status" aria-live="polite">
            <i class="bi bi-check-circle-fill text-lg" aria-hidden="true"></i>
            <span><?= e($mensajeExito) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <?php foreach ($errores as $error): ?>
                    <div><?= e((string) $error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($practica !== null): ?>
        <?php if ($marcaPorConfirmar !== null): ?>
            <!-- Agregué confirmación alternativa sin JavaScript; cancelar vuelve por GET sin guardar. -->
            <section data-marcaje-fallback class="rrhh-card p-5 space-y-3" aria-labelledby="confirmacionServidorTitulo">
                <h2 id="confirmacionServidorTitulo" class="text-lg font-bold">Confirmar marcaje</h2>
                <p>¿Confirmas registrar <strong><?= e(marcar_asistencia_nombre_marca($tiposMarca, $marcaPorConfirmar)) ?></strong>? Se utilizará la hora del servidor al confirmar.</p>
                <form method="post" action="<?= e(url('practicante/marcar-asistencia.php')) ?>" data-marcaje-confirmado class="flex flex-wrap gap-3">
                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_marcar_asistencia']) ?>">
                    <input type="hidden" name="codigo_marca" value="<?= e($marcaPorConfirmar) ?>">
                    <input type="hidden" name="confirmar_marca" value="1">
                    <input type="hidden" name="observacion" value="<?= e((string) $observacionPorConfirmar) ?>">
                    <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url('practicante/marcar-asistencia.php')) ?>">Cancelar</a>
                    <button type="submit" class="rrhh-button bg-blue-600 text-white">Confirmar <?= e(marcar_asistencia_nombre_marca($tiposMarca, $marcaPorConfirmar)) ?></button>
                </form>
            </section>
        <?php endif; ?>
        <?php if (defined('APP_DEBUG') && APP_DEBUG && (!empty($_GET['simular_fecha']) || !empty($_GET['simular_hora']))): ?>
            <div class="p-3 mb-6 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 text-xs flex flex-wrap items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center gap-2">
                    <i class="bi bi-calendar-check-fill text-purple-600 text-base"></i>
                    <span><strong>Modo simulación (APP_DEBUG):</strong> Visualizando como <strong><?= e($fechaLarga) ?></strong> a las <strong><?= e(substr($horaHoy, 0, 5)) ?></strong> (<?= e($fechaHoy) ?> <?= e($horaHoy) ?>)</span>
                </div>
                <a href="<?= e(url('practicante/marcar-asistencia.php')) ?>" class="px-2.5 py-1 rounded-lg bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold transition">
                    Volver a fecha y hora real
                </a>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- CONTENIDO PRINCIPAL (Columna Izquierda / 8 cols) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Panel de Marcación de la Jornada -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Jornada de hoy</span>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5"><?= e($fechaLarga) ?></h2>
                        </div>
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold border <?= match ($estadoVisual['class'] ?? '') {
                                                                                                                            'state-ok' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                                                                                            'state-warning' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                                                                                            'state-info' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                                                                                            'state-muted' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                                                                                            default => 'bg-amber-50/80 text-amber-700 border-amber-200'
                                                                                                                        } ?>">
                            <i class="bi <?= e($estadoVisual['icon']) ?>"></i>
                            <?= e($estadoVisual['label']) ?>
                        </span>
                    </div>

                    <?php if ($puedeMarcar): ?>
                        <?php
                        $querySimulacion = [];
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            if (!empty($_GET['simular_fecha'])) {
                                $querySimulacion['simular_fecha'] = (string) $_GET['simular_fecha'];
                            }
                            if (!empty($_GET['simular_hora'])) {
                                $querySimulacion['simular_hora'] = (string) $_GET['simular_hora'];
                            }
                        }
                        $actionSimulacion = $querySimulacion !== [] ? '?' . http_build_query($querySimulacion) : '';
                        ?>
                        <!-- Solicité confirmación nativa antes del envío; el servidor conserva todas sus validaciones. -->
                        <form method="post" action="<?= e(url('practicante/marcar-asistencia.php' . $actionSimulacion)) ?>" data-confirm-marcaje class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_marcar_asistencia']) ?>">
                            <p hidden data-marcaje-status class="text-sm text-blue-700" role="status" aria-live="polite"></p>

                            <?php if ($avisoAnticipacion !== null): ?>
                                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-center gap-2.5 shadow-sm">
                                    <i class="bi bi-clock-history text-amber-600 text-base shrink-0"></i>
                                    <span><?= e($avisoAnticipacion) ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Línea de pasos secuenciales -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-1" aria-label="Secuencia de marcacion">
                                <?php /* Conservé los cuatro pasos principales; las pausas por permiso son acciones adicionales. */ ?>
                                <?php foreach (array_slice(asistencia_tipos_marca(), 0, 4) as $index => $codigo): ?>
                                    <?php
                                    $marcaActual = asistencia_marca_por_codigo($marcas, $codigo);
                                    $enSecuencia = in_array($codigo, $permitidas, true);
                                    $ventana = $ventanasMarcas[$codigo] ?? ['habilitado' => true, 'es_anticipado' => false, 'hora_apertura' => null];
                                    $habilitada = $enSecuencia && ($ventana['habilitado'] ?? true);
                                    ?>
                                    <div class="flex flex-col items-center text-center p-3 rounded-xl border <?= $marcaActual !== null ? 'bg-emerald-50/50 border-emerald-200 text-emerald-900' : ($habilitada ? 'bg-blue-50/50 border-blue-300 ring-2 ring-blue-500/10 text-blue-900' : ($enSecuencia && ($ventana['es_anticipado'] ?? false) ? 'bg-amber-50/50 border-amber-200 text-amber-900' : 'bg-slate-50 border-slate-200/80 text-slate-400 opacity-60')) ?> transition">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-1.5 <?= $marcaActual !== null ? 'bg-emerald-600 text-white' : ($habilitada ? 'bg-blue-600 text-white shadow-sm' : ($enSecuencia && ($ventana['es_anticipado'] ?? false) ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-200 text-slate-600')) ?>">
                                            <?php if ($marcaActual !== null): ?>
                                                <i class="bi bi-check"></i>
                                            <?php else: ?>
                                                <?= e((string) ($index + 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <strong class="text-xs font-semibold leading-tight line-clamp-1"><?= e(marcar_asistencia_nombre_marca($tiposMarca, $codigo)) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Botones de Acción para Marcar -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5">
                                <?php /* Agregué las dos acciones del permiso solo al necesitarse o estar registradas. */ ?>
                                <?php foreach (array_filter(asistencia_tipos_marca(), static fn(string $c): bool => !in_array($c, ['SALIDA_PERMISO', 'REGRESO_PERMISO'], true) || in_array($c, $permitidas, true) || asistencia_marca_por_codigo($marcas, $c) !== null) as $codigo): ?>
                                    <?php
                                    $marcaActual = asistencia_marca_por_codigo($marcas, $codigo);
                                    $enSecuencia = in_array($codigo, $permitidas, true);
                                    $ventana = $ventanasMarcas[$codigo] ?? ['habilitado' => true, 'es_anticipado' => false, 'hora_apertura' => null];
                                    $habilitada = $enSecuencia && ($ventana['habilitado'] ?? true);
                                    $esAnticipado = $enSecuencia && !($ventana['habilitado'] ?? true) && ($ventana['es_anticipado'] ?? false);
                                    $esAutomatica = $marcaActual !== null && str_contains(strtolower((string) ($marcaActual['observacion'] ?? '')), 'automático');
                                    ?>
                                    <button
                                        type="submit"
                                        name="codigo_marca"
                                        value="<?= e($codigo) ?>"
                                        data-marca-label="<?= e(marcar_asistencia_nombre_marca($tiposMarca, $codigo)) ?>"
                                        <?= $habilitada ? '' : 'disabled' ?>
                                        class="group relative flex flex-col items-center justify-center text-center p-4 rounded-xl border transition-all <?= $habilitada
                                                                                                                                                                ? 'bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-md shadow-blue-500/25 border-transparent cursor-pointer active:scale-95'
                                                                                                                                                                : ($marcaActual !== null
                                                                                                                                                                    ? 'bg-emerald-50/40 border-emerald-200/80 text-slate-700 cursor-default'
                                                                                                                                                                    : ($esAnticipado
                                                                                                                                                                        ? 'bg-amber-50/60 border-amber-200 text-amber-800 cursor-not-allowed opacity-80'
                                                                                                                                                                        : 'bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed opacity-60')) ?>">
                                        <i class="bi <?= e(marcar_asistencia_marca_icono($codigo)) ?> text-2xl mb-2 <?= $habilitada ? 'text-white' : ($marcaActual !== null ? 'text-emerald-600' : ($esAnticipado ? 'text-amber-600' : 'text-slate-400')) ?>"></i>
                                        <strong class="text-xs sm:text-sm font-bold leading-snug"><?= e(marcar_asistencia_marca_accion($codigo)) ?></strong>
                                        <span class="text-[11px] mt-1 <?= $habilitada ? 'text-blue-100 font-medium' : ($marcaActual !== null ? 'text-emerald-700 font-semibold' : ($esAnticipado ? 'text-amber-700 font-semibold' : 'text-slate-400')) ?>">
                                            <?php if ($marcaActual !== null): ?>
                                                <?= e(marcar_asistencia_hora_corta_fecha_hora((string) $marcaActual['fecha_hora'])) ?>
                                                <?php if ($esAutomatica): ?>
                                                    <span class="block text-[10px] text-amber-600 font-normal">(Automático)</span>
                                                <?php endif; ?>
                                            <?php elseif ($habilitada): ?>
                                                Disponible ahora
                                            <?php elseif ($esAnticipado && !empty($ventana['hora_apertura'])): ?>
                                                Disponible a las <?= e($ventana['hora_apertura']) ?>
                                            <?php else: ?>
                                                Pendiente
                                            <?php endif; ?>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- Observación Opcional -->
                            <div class="space-y-1.5 pt-2">
                                <label for="observacion" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Observación opcional
                                </label>
                                <input
                                    type="text"
                                    id="observacion"
                                    name="observacion"
                                    maxlength="255"
                                    placeholder="Agrega un comentario si corresponde (máx 255 caracteres)"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>
                        </form>
                    <?php else: ?>
                        <?php $esSoloLecturaMeta = !empty($estadoSoloLectura['solo_lectura']); ?>
                        <div class="flex items-start sm:items-center gap-3.5 p-4 sm:p-5 rounded-2xl border text-sm shadow-sm <?= $esSoloLecturaMeta ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50/70 border-amber-200/80 text-amber-900' ?>">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $esSoloLecturaMeta ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                <i class="bi <?= $esSoloLecturaMeta ? 'bi-check2-circle' : 'bi-lock-fill' ?> text-xl"></i>
                            </div>
                            <div>
                                <strong class="block font-bold mb-0.5 <?= $esSoloLecturaMeta ? 'text-emerald-950' : 'text-amber-950' ?>">
                                    <?= $esSoloLecturaMeta ? (!empty($estadoSoloLectura['meta_cumplida']) ? 'Marcaje cerrado por meta cumplida' : 'Marcaje cerrado por término de práctica') : 'Marcaje no disponible hoy' ?>
                                </strong>
                                <span class="text-xs sm:text-sm <?= $esSoloLecturaMeta ? 'text-emerald-800' : 'text-amber-800' ?>">
                                    <?= e($esSoloLecturaMeta
                                        ? (!empty($estadoSoloLectura['meta_cumplida'])
                                            ? 'Ya alcanzaste tus horas requeridas, por lo que no necesitas registrar nuevas marcas.'
                                            : 'Tu práctica concluyó y el registro de asistencia ya no está activo.')
                                        : ($mensajeBloqueo ?? 'No hay una práctica disponible para marcar asistencia hoy.')) ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Tira de Horario del Día y Estado -->
                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tarjeta Estado Actual -->
                    <div class="flex items-center gap-3.5 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl <?= match ($estadoVisual['class'] ?? '') {
                                                                                                        'state-ok' => 'bg-emerald-50 text-emerald-600',
                                                                                                        'state-warning' => 'bg-amber-50 text-amber-600',
                                                                                                        'state-info' => 'bg-blue-50 text-blue-600',
                                                                                                        'state-muted' => 'bg-slate-100 text-slate-600',
                                                                                                        default => 'bg-amber-50 text-amber-600'
                                                                                                    } ?>">
                            <i class="bi <?= e($estadoVisual['icon']) ?>"></i>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 font-bold uppercase tracking-wider">Estado actual</span>
                            <strong class="text-sm font-bold text-slate-800"><?= e($estadoVisual['label']) ?></strong>
                        </div>
                    </div>

                    <!-- Tarjeta Horario Planificado -->
                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm text-sm">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <div class="space-y-0.5 text-xs text-slate-600">
                            <?php if ($horarioHoy === null): ?>
                                <?php if (!empty($estadoSoloLectura['solo_lectura'])): ?>
                                    <span class="block text-xs text-slate-400 font-bold uppercase tracking-wider">Jornada</span>
                                    <strong class="text-sm font-bold text-slate-800">Sin marcajes pendientes</strong>
                                <?php else: ?>
                                    <span class="text-slate-500 font-medium"><?= e($mensajeBloqueo ?? 'No hay horario pactado para hoy.') ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <div>
                                    <span class="text-slate-400 font-bold uppercase tracking-wider">Planificado:</span>
                                    <strong class="text-slate-800 ml-1 font-bold"><?= e(marcar_asistencia_formatear_hora((string) $horarioHoy['entrada_esperada'])) ?> - <?= e(marcar_asistencia_formatear_hora((string) $horarioHoy['salida_esperada'])) ?></strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-bold uppercase tracking-wider">Colación:</span>
                                    <strong class="text-slate-800 ml-1"><?= e(marcar_asistencia_formatear_hora($horarioHoy['colacion_inicio'] ?? null)) ?> - <?= e(marcar_asistencia_formatear_hora($horarioHoy['colacion_fin'] ?? null)) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php // Mostré cada tramo autorizado solo en su fecha, separado del turno ordinario. 
                            ?>
                            <?php foreach ($recuperacionesHoy ?? [] as $tramo): ?>
                                <div><strong>Recuperación de hoy:</strong> <?= e(substr($tramo['hora_inicio'], 0, 5)) ?>–<?= e(substr($tramo['hora_fin'], 0, 5)) ?> (<?= e($tramo['tipo']) ?>). Sujeta a validación de marcas.</div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <!-- Historial Reciente de Últimas Jornadas -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Historial reciente</span>
                            <h2 class="text-base font-bold text-slate-800">Últimas jornadas registradas</h2>
                        </div>
                        <a href="<?= e(url('practicante/mi-historial.php')) ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5 transition">
                            <span>Ver historial completo</span>
                            <i class="bi bi-arrow-up-right-square"></i>
                        </a>
                    </div>

                    <?php if ($historialReciente === []): ?>
                        <div class="p-8 text-center text-sm text-slate-400">
                            Aún no hay jornadas registradas para mostrar.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <!-- Adapté esta tabla para mostrar campos etiquetados sin desplazamiento horizontal en practicantes. -->
                            <table role="table" class="rrhh-responsive-table w-full text-left text-sm">
                                <thead role="rowgroup" class="bg-slate-50/75 text-xs uppercase font-bold text-slate-500 border-b border-slate-100">
                                    <tr role="row">
                                        <th role="columnheader" class="px-5 py-3 font-semibold">Fecha</th>
                                        <th role="columnheader" class="px-5 py-3 font-semibold">Estado</th>
                                        <th role="columnheader" class="px-5 py-3 font-semibold">Entrada</th>
                                        <th role="columnheader" class="px-5 py-3 font-semibold">Salida</th>
                                        <th role="columnheader" class="px-5 py-3 font-semibold">Horas trabajadas</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup" class="divide-y divide-slate-100 text-slate-700">
                                    <?php foreach ($historialReciente as $fila): ?>
                                        <?php
                                        $filaEstado = marcar_asistencia_estado_visual((string) $fila['estado_jornada'], false);
                                        $horasBaseFila = (float) ($fila['horas_calculadas'] ?? 0);
                                        $horasExtraFila = $fila['horas_extra_aprobadas'] !== null ? (float) $fila['horas_extra_aprobadas'] : 0.0;
                                        $horasTotalesFila = round($horasBaseFila + $horasExtraFila, 2);
                                        ?>
                                        <tr role="row" class="hover:bg-slate-50/50 transition">
                                            <td role="cell" data-label="Fecha" class="px-5 py-3 font-medium text-slate-900 whitespace-nowrap">
                                                <div class="rrhh-cell-value">
                                                    <?= e(marcar_asistencia_formatear_fecha((string) $fila['fecha'])) ?>
                                                </div>
                                            </td>
                                            <td role="cell" data-label="Estado" class="px-5 py-3 whitespace-nowrap">
                                                <div class="rrhh-cell-value">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= match ($filaEstado['class'] ?? '') {
                                                                                                                                                        'state-ok' => 'bg-emerald-50 text-emerald-700',
                                                                                                                                                        'state-warning' => 'bg-amber-50 text-amber-800',
                                                                                                                                                        'state-info' => 'bg-blue-50 text-blue-700',
                                                                                                                                                        default => 'bg-slate-100 text-slate-600'
                                                                                                                                                    } ?>">
                                                        <?= e($filaEstado['label']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td role="cell" data-label="Entrada" class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                                <div class="rrhh-cell-value">
                                                    <?= e(marcar_asistencia_hora_corta_fecha_hora($fila['hora_entrada'] ?? null)) ?>
                                                </div>
                                            </td>
                                            <td role="cell" data-label="Salida" class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                                <div class="rrhh-cell-value">
                                                    <?= e(marcar_asistencia_hora_corta_fecha_hora($fila['hora_salida'] ?? null)) ?>
                                                </div>
                                            </td>
                                            <td role="cell" data-label="Horas trabajadas" class="px-5 py-3 whitespace-nowrap">
                                                <div class="rrhh-cell-value">
                                                    <strong class="font-bold text-slate-900"><?= e(formatear_horas_enteras($horasTotalesFila)) ?> hrs</strong>
                                                    <?php if ($horasExtraFila > 0): ?>
                                                        <span class="text-emerald-600 text-xs font-semibold ml-1">(+<?= e(formatear_horas_extra_minutos($horasExtraFila)) ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- BARRA LATERAL DERECHA (Indicadores y Métricas / 4 cols) -->
            <aside class="lg:col-span-4 space-y-6">

                <?php
                $metaCumplida = $horasRequeridas > 0 && $porcentajeAvance >= 100.0;
                $avanceRadial = max(0, min(100, $porcentajeAvance));
                $idPracticaMeta = (int) ($practica['id_practica'] ?? 0);
                ?>
                <!-- Panel de Avance de Horas -->
                <section
                    class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-5<?= $metaCumplida ? ' practitioner-goal-card--completed' : '' ?>"
                    <?php if ($metaCumplida): ?>
                        data-meta-cumplida="1"
                        data-meta-id="<?= e((string) $idPracticaMeta) ?>"
                    <?php endif; ?>>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Avance de horas</span>
                            <h2 class="text-base font-bold text-slate-800">
                                <?= $metaCumplida ? 'Meta completada' : 'Práctica activa' ?>
                            </h2>
                        </div>
                        <?php if ($metaCumplida): ?>
                            <span class="practitioner-goal-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold shrink-0">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                <span>100%</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Mantuve radial-progress DaisyUI como contenedor accesible y dibujé el arco SVG para compatibilidad entre navegadores. -->
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100<?= $metaCumplida ? ' practitioner-goal-box--completed' : '' ?>">
                        <div
                            class="dui-radial-progress practitioner-progress shrink-0 font-extrabold text-base<?= $metaCumplida ? ' practitioner-progress--completed' : '' ?>"
                            style="--value:<?= e(number_format($avanceRadial, 1, '.', '')) ?>; --size:4rem; --thickness:4px;"
                            role="progressbar"
                            aria-valuenow="<?= e(number_format($avanceRadial, 0, '.', '')) ?>"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Progreso de horas de práctica">
                            <svg class="practitioner-progress__svg" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                                <circle class="practitioner-progress__track" cx="24" cy="24" r="20" fill="none" stroke-width="4"></circle>
                                <circle class="practitioner-progress__value" cx="24" cy="24" r="20" fill="none" stroke-width="4" stroke-dasharray="125.664" stroke-dashoffset="<?= e(number_format(125.664 * (1 - $avanceRadial / 100), 3, '.', '')) ?>"></circle>
                            </svg>
                            <span class="practitioner-progress__label"><?= e(number_format($porcentajeAvance, 0, ',', '.')) ?>%</span>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-xs text-slate-400 font-bold uppercase tracking-wider">Horas acumuladas</span>
                            <div class="text-lg font-black text-slate-900 mt-0.5">
                                <?= e(formatear_horas_enteras($horasRealizadas)) ?> <span class="text-slate-400 font-normal text-sm">/ <?= e(formatear_horas_enteras($horasRequeridas)) ?></span>
                            </div>
                            <span class="block text-xs <?= $metaCumplida ? 'practitioner-goal-subtitle--completed font-semibold' : 'text-slate-500' ?>">
                                <?= $metaCumplida ? 'Horas asignadas completadas' : 'Meta de práctica' ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($metaCumplida): ?>
                        <div class="practitioner-goal-message flex items-start justify-between gap-3 p-3.5 rounded-xl border text-xs leading-relaxed" role="status">
                            <div class="flex items-start gap-2.5">
                                <i class="bi bi-trophy-fill text-base shrink-0 mt-0.5" aria-hidden="true"></i>
                                <div>
                                    <strong class="block font-bold">¡Felicitaciones! Meta de práctica cumplida</strong>
                                    <span>Completaste el 100% de tus horas asignadas.</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="practitioner-goal-replay shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-lg border transition cursor-pointer"
                                data-confetti-replay
                                title="Volver a celebrar"
                                aria-label="Volver a lanzar confeti">
                                <i class="bi bi-stars" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Días restantes -->
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-sm">
                        <i class="bi bi-calendar3 text-lg text-blue-600 shrink-0"></i>
                        <div>
                            <span class="block text-[11px] text-slate-400 font-bold uppercase tracking-wider">Días restantes</span>
                            <strong class="text-slate-800 font-bold"><?= $diasRestantes !== null ? e((string) $diasRestantes) . ' días' : 'No definido' ?></strong>
                        </div>
                    </div>

                    <a href="<?= e(url('practicante/mi-historial.php')) ?>" class="w-full flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/30 text-slate-700 hover:text-blue-700 text-xs font-semibold transition group">
                        <span class="flex items-center gap-2">
                            <i class="bi bi-graph-up-arrow text-blue-600"></i>
                            <span>Ver historial y avance completo</span>
                        </span>
                        <i class="bi bi-chevron-right text-slate-400 group-hover:text-blue-600 transition"></i>
                    </a>
                </section>

                <!-- Resumen de Indicadores KPI -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-4">
                    <div>
                        <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Resumen</span>
                        <h2 class="text-base font-bold text-slate-800">Mis indicadores</h2>
                    </div>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 text-slate-700">
                            <span class="flex items-center gap-2 text-xs font-medium">
                                <i class="bi bi-clock-history text-blue-600"></i>
                                Horas acumuladas
                            </span>
                            <strong class="font-bold text-slate-900"><?= e(formatear_horas_enteras($horasRealizadas)) ?> h</strong>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 text-slate-700">
                            <span class="flex items-center gap-2 text-xs font-medium">
                                <i class="bi bi-bullseye text-blue-600"></i>
                                Horas requeridas
                            </span>
                            <strong class="font-bold text-slate-900"><?= e(formatear_horas_enteras($horasRequeridas)) ?> h</strong>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 text-slate-700">
                            <span class="flex items-center gap-2 text-xs font-medium">
                                <i class="bi bi-activity text-blue-600"></i>
                                Asistencia del mes
                            </span>
                            <strong class="font-bold text-slate-900"><?= e(number_format((float) ($resumenPractica['asistencia_mes'] ?? 0), 1, ',', '.')) ?>%</strong>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 text-slate-700">
                            <span class="flex items-center gap-2 text-xs font-medium">
                                <i class="bi bi-arrow-up-right text-blue-600"></i>
                                Progreso total
                            </span>
                            <strong class="font-bold text-slate-900"><?= e(number_format($porcentajeAvance, 1, ',', '.')) ?>%</strong>
                        </div>
                    </div>
                </section>

                <!-- Observaciones del Sistema / Avisos -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 space-y-4">
                    <div>
                        <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Avisos</span>
                        <h2 class="text-base font-bold text-slate-800">Observaciones del sistema</h2>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($avisosSistema as $aviso): ?>
                            <article class="flex items-start gap-3 p-3.5 rounded-xl border text-xs leading-relaxed <?= match ($aviso['class'] ?? '') {
                                                                                                                        'notice-warning' => 'bg-amber-50/60 border-amber-200 text-amber-900',
                                                                                                                        'notice-info' => 'bg-blue-50/60 border-blue-200 text-blue-900',
                                                                                                                        default => 'bg-emerald-50/60 border-emerald-200 text-emerald-900'
                                                                                                                    } ?>">
                                <i class="bi <?= e($aviso['icon']) ?> text-base shrink-0 mt-0.5"></i>
                                <div>
                                    <strong class="block font-bold text-slate-900"><?= e($aviso['title']) ?></strong>
                                    <span class="text-slate-600"><?= e($aviso['text']) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>