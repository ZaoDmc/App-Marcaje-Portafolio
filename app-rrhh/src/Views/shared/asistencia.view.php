<?php
// Migré la presentación a utilidades/componentes Tailwind y sistema de diseño unificado.
// Recibe los datos preparados por el controlador en Controllers/Shared/AsistenciaController.php.

$fechaDefaultDesde = date('Y-m-d', strtotime('-30 days'));
$fechaDefaultHasta = date('Y-m-d');
$tieneFiltroFecha = (query_param('desde') !== null && query_param('desde') !== $fechaDefaultDesde)
    || (query_param('hasta') !== null && query_param('hasta') !== $fechaDefaultHasta);

$hayFiltrosActivos = (($filtros['estado'] ?? '') !== '')
    || (($filtros['q'] ?? '') !== '')
    || $tieneFiltroFecha;

$urlListadoBase = asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => null]);
$urlLimpiarFiltros = url($rutaActual);
?>

<div class="gestion-asistencia-content">
    <?php // Avisos flotantes: Éxito y Errores accesibles y cerrables ?>
    <?php if ($mensajeExito !== null): ?>
        <div class="rrhh-alert rrhh-aviso-flotante rrhh-aviso-flotante--exito border-emerald-200 bg-emerald-50 text-emerald-800 pr-12 mb-6" role="alert">
            <i class="bi bi-check-circle-fill text-emerald-600 text-lg shrink-0"></i>
            <span><?= e($mensajeExito) ?></span>
            <button type="button" class="rrhh-close" data-ui-dismiss-alert aria-label="Cerrar aviso"></button>
        </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="rrhh-alert rrhh-aviso-flotante border-red-200 bg-red-50 text-red-800 pr-12 mb-6" role="alert">
            <i class="bi bi-exclamation-octagon-fill text-red-600 text-lg shrink-0"></i>
            <ul class="mb-0 pl-2">
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="rrhh-close" data-ui-dismiss-alert aria-label="Cerrar aviso"></button>
        </div>
    <?php endif; ?>

    <!-- Tarjetas KPI métricas (Mismo formato y jerarquía visual de Permisos) -->
    <div class="grid grid-cols-12 gap-4 mb-6">
        <div class="col-span-12 sm:col-span-4">
            <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
                <div class="flex justify-between items-center gap-4">
                    <div>
                        <div class="text-slate-500 text-xs font-semibold mb-1.5">Jornadas válidas</div>
                        <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-emerald-700">
                            <?= (int) ($conteosEstado['VALIDA'] ?? 0) ?>
                        </strong>
                        <span class="text-xs text-slate-400 mt-1.5 block font-medium">Cumplidas sin observaciones</span>
                    </div>
                    <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--success w-12 h-12 shrink-0 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-span-12 sm:col-span-4">
            <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
                <div class="flex justify-between items-center gap-4">
                    <div>
                        <div class="text-slate-500 text-xs font-semibold mb-1.5">Jornadas observadas</div>
                        <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-amber-700">
                            <?= (int) ($conteosEstado['OBSERVADA'] ?? 0) ?>
                        </strong>
                        <span class="text-xs text-slate-400 mt-1.5 block font-medium">Requieren revisión o ajuste</span>
                    </div>
                    <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--warning w-12 h-12 shrink-0 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-span-12 sm:col-span-4">
            <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
                <div class="flex justify-between items-center gap-4">
                    <div>
                        <div class="text-slate-500 text-xs font-semibold mb-1.5">Jornadas incompletas</div>
                        <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-red-700">
                            <?= (int) ($conteosEstado['INCOMPLETA'] ?? 0) ?>
                        </strong>
                        <span class="text-xs text-slate-400 mt-1.5 block font-medium">Marcas faltantes o ausencias</span>
                    </div>
                    <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--danger permission-rejected-icon w-12 h-12 shrink-0 rounded-2xl bg-red-50 border border-red-200/80 text-red-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Pestañas de Estado + Filtros y Buscador (Mismo formato unificado de Permisos) -->
    <section class="rrhh-card border border-slate-200/80 shadow-sm mb-6 bg-white rounded-2xl rrhh-theme-panel-border">
        <div class="p-4 sm:p-5 space-y-4">
            <!-- Fila superior: Pestañas de estado (Barra segmentada unificada idéntica a Permisos) -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 pb-4 rrhh-theme-panel-border">
                <nav class="rrhh-permisos-tabs inline-flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5" aria-label="Filtrar por estado">
                    <?php
                    $tabsConfig = [
                        ['estado' => '',           'label' => 'Todos',       'icon' => 'bi bi-collection',          'count' => (int)($conteosEstado[''] ?? 0),           'variant' => 'all'],
                        ['estado' => 'VALIDA',     'label' => 'Válidas',     'icon' => 'bi bi-check2-circle',       'count' => (int)($conteosEstado['VALIDA'] ?? 0),     'variant' => 'success'],
                        ['estado' => 'OBSERVADA',  'label' => 'Observadas',  'icon' => 'bi bi-hourglass-split',     'count' => (int)($conteosEstado['OBSERVADA'] ?? 0),  'variant' => 'warning'],
                        ['estado' => 'INCOMPLETA', 'label' => 'Incompletas', 'icon' => 'bi bi-clock-history',      'count' => (int)($conteosEstado['INCOMPLETA'] ?? 0), 'variant' => 'danger'],
                        ['estado' => 'CORREGIDA',  'label' => 'Corregidas',  'icon' => 'bi bi-wrench-adjustable',   'count' => (int)($conteosEstado['CORREGIDA'] ?? 0),  'variant' => 'info'],
                    ];
                    ?>
                    <?php foreach ($tabsConfig as $tab): ?>
                        <?php
                        $activo = ($filtros['estado'] === $tab['estado']);
                        $paramsTab = [];
                        if ($tab['estado'] !== '') {
                            $paramsTab['estado'] = $tab['estado'];
                        }
                        if (($filtros['q'] ?? '') !== '') {
                            $paramsTab['q'] = $filtros['q'];
                        }
                        if ($tieneFiltroFecha) {
                            $paramsTab['desde'] = $filtros['desde'];
                            $paramsTab['hasta'] = $filtros['hasta'];
                        }
                        $urlTab = url($rutaActual . ($paramsTab !== [] ? '?' . http_build_query($paramsTab) : ''));
                        $claseTab = match ($tab['variant']) {
                            'success' => 'rrhh-permisos-tab--success',
                            'warning' => 'rrhh-permisos-tab--warning',
                            'danger'  => 'rrhh-permisos-tab--danger',
                            'info'    => 'rrhh-permisos-tab--info',
                            default   => 'rrhh-permisos-tab--all',
                        };
                        ?>
                        <a href="<?= e($urlTab) ?>"
                           class="rrhh-permisos-tab <?= $claseTab ?> inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold"
                           aria-current="<?= $activo ? 'page' : 'false' ?>">
                            <i class="<?= e($tab['icon']) ?> rrhh-permisos-tab-icon text-sm" aria-hidden="true"></i>
                            <span><?= e($tab['label']) ?></span>
                            <span class="rrhh-permisos-tab-count inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold leading-none">
                                <?= $tab['count'] ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <?php if ($hayFiltrosActivos): ?>
                    <a href="<?= e($urlLimpiarFiltros) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200/90 bg-slate-100/90 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold shadow-2xs transition">
                        <i class="bi bi-arrow-counterclockwise text-slate-500"></i> Limpiar filtros
                    </a>
                <?php endif; ?>
            </div>

            <!-- Fila inferior: Campos de búsqueda y fechas alineados perfectamente con altura uniforme h-10 -->
            <form class="grid grid-cols-12 gap-3 sm:gap-4 items-end" method="get" action="<?= e(url($rutaActual)) ?>">
                <?php if (($filtros['estado'] ?? '') !== ''): ?>
                    <input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>">
                <?php endif; ?>
                <div class="col-span-12 sm:col-span-6 md:col-span-3">
                    <label class="rrhh-label text-xs font-semibold text-slate-500 mb-1.5 block" for="desde">Jornada desde</label>
                    <input class="rrhh-input w-full !h-10 !text-xs rounded-xl" type="date" id="desde" name="desde" value="<?= e((string) $filtros['desde']) ?>">
                </div>
                <div class="col-span-12 sm:col-span-6 md:col-span-3">
                    <label class="rrhh-label text-xs font-semibold text-slate-500 mb-1.5 block" for="hasta">Jornada hasta</label>
                    <input class="rrhh-input w-full !h-10 !text-xs rounded-xl" type="date" id="hasta" name="hasta" value="<?= e((string) $filtros['hasta']) ?>">
                </div>
                <div class="col-span-12 md:col-span-4">
                    <label class="rrhh-label text-xs font-semibold text-slate-500 mb-1.5 block" for="q">Buscar practicante</label>
                    <input class="rrhh-input w-full !h-10 !text-xs rounded-xl" id="q" name="q" value="<?= e((string) $filtros['q']) ?>" placeholder="Nombre, apellido, correo o RUT..." maxlength="100">
                </div>
                <div class="col-span-12 md:col-span-2">
                    <button class="rrhh-button rrhh-btn-primary w-full !h-10 !text-xs font-bold rounded-xl justify-center shadow-xs" type="submit">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
            </form>
                </div>
            </form>
        </div>
    </section>

    <?php /* Herramienta de Ausencias esperadas exclusiva para ADMIN */ ?>
    <?php if (auth_rol_activo() === 'ADMIN'): ?>
        <section class="panel p-4 sm:p-5 mb-6 rounded-2xl border border-slate-200/80 shadow-sm bg-white rrhh-theme-panel-border">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 mb-0.5">Generación masiva de ausencias esperadas</h2>
                    <p class="text-xs text-slate-500 mb-0">Genera jornadas incompletas solo para días planificados pasados que no tuvieron marcas.</p>
                </div>
                <span class="rrhh-badge bg-slate-50 text-slate-700 border text-xs">Máximo 31 días</span>
            </div>
            <form class="grid grid-cols-12 gap-3 items-end" method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros)) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="accion" value="generar_ausencias_esperadas">
                <input type="hidden" name="desde" value="<?= e((string) $filtros['desde']) ?>">
                <input type="hidden" name="hasta" value="<?= e(min((string) $filtros['hasta'], date('Y-m-d', strtotime('-1 day')))) ?>">
                <div class="col-span-12 md:col-span-8">
                    <div class="flex items-center gap-2">
                        <input class="rrhh-checkbox" type="checkbox" id="confirmar_ausencias" name="confirmar_ausencias" value="1" required>
                        <label class="text-xs text-slate-700" for="confirmar_ausencias">
                            Confirmo generar ausencias hasta el día anterior a hoy dentro del rango seleccionado.
                        </label>
                    </div>
                </div>
                <div class="col-span-12 md:col-span-4">
                    <button class="rrhh-button rrhh-btn-secondary !text-xs !py-2 w-full justify-center" type="submit">
                        <i class="bi bi-gear-fill me-1"></i> Generar ausencias
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <!-- Tabla Principal de Jornadas (Estilo unificado con Practicantes y Permisos) -->
    <section class="panel p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm bg-white mb-6 rrhh-theme-panel-border">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-200/80 rrhh-theme-panel-border">
            <div>
                <h2 class="text-base font-bold text-slate-900 mb-0.5">Jornadas registradas</h2>
                <p class="text-xs text-slate-500 mb-0">Listado de asistencias según el rango y estado seleccionados.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                <?= (int) $totalJornadas ?> resultado<?= $totalJornadas === 1 ? '' : 's' ?>
                <?php if ($totalPaginas > 1): ?>
                    · Pág. <?= (int) $paginaActual ?> de <?= (int) $totalPaginas ?>
                <?php endif; ?>
            </span>
        </div>

        <?php if ($jornadas === []): ?>
            <div class="p-8 text-center text-slate-500">
                <i class="bi bi-inbox text-3xl block mb-2 text-slate-400"></i>
                <p class="font-medium mb-1">No hay jornadas para los filtros seleccionados.</p>
                <p class="text-xs text-slate-400 mb-0">Intenta ampliar el rango de fechas o restablecer la búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="rrhh-table-scroll overflow-x-auto">
                <table class="rrhh-table rrhh-table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Practicante</th>
                            <th>Estado</th>
                            <th>Horas registradas</th>
                            <th>Marcas</th>
                            <th class="text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jornadas as $jornada): ?>
                            <?php
                            $vistaHoras = $horasVistaPorJornada[(int) $jornada['id_jornada']];
                            $nombreCompleto = asistencia_gestion_nombre_practicante($jornada);
                            $partesNombre = explode(' ', trim($nombreCompleto));
                            $iniciales = strtoupper(substr($partesNombre[0] ?? 'P', 0, 1) . substr($partesNombre[1] ?? '', 0, 1));
                            $diaSemanaNum = (int) date('N', strtotime((string) $jornada['fecha']));
                            $diaSemanaNombre = horarios_practicante_dias()[$diaSemanaNum] ?? '';
                            $urlVerJornada = asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornada['id_jornada']]);
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="whitespace-nowrap">
                                    <strong class="block text-slate-900 leading-snug"><?= e(asistencia_gestion_formatear_fecha((string) $jornada['fecha'])) ?></strong>
                                    <span class="text-slate-500 text-xs capitalize"><?= e($diaSemanaNombre) ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="rrhh-practicante-avatar shrink-0" aria-hidden="true"><?= e($iniciales) ?></span>
                                        <div class="min-w-0">
                                            <strong class="block leading-snug text-slate-900 truncate"><?= e($nombreCompleto) ?></strong>
                                            <div class="text-slate-500 text-xs mt-0.5 truncate"><?= e((string) $jornada['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rrhh-badge <?= e(rrhh_tone(e(asistencia_gestion_estado_badge((string) $jornada['estado_jornada'])))) ?>">
                                            <?= e((string) $jornada['estado_jornada']) ?>
                                        </span>
                                        <?php if ((int) ($jornada['flag_ausencia_esperada'] ?? 0) === 1): ?>
                                            <span class="rrhh-badge bg-amber-100 text-amber-900 text-[11px]">Ausencia</span>
                                        <?php endif; ?>
                                        <?php if ($vistaHoras['tiene_horas_extra']): ?>
                                            <?php if ($vistaHoras['es_validada']): ?>
                                                <span class="rrhh-badge bg-emerald-100 text-emerald-800 text-[11px]">Horas validadas</span>
                                            <?php else: ?>
                                                <span class="rrhh-badge bg-blue-100 text-blue-800 text-[11px]">Extras pendientes</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <?php if ($vistaHoras['tiene_horas_extra']): ?>
                                        <?php if ($vistaHoras['es_validada']): ?>
                                            <div>
                                                <span class="rrhh-badge bg-emerald-100 text-emerald-800 text-xs font-bold">
                                                    <?= e(formatear_horas_enteras($vistaHoras['horas_validadas'])) ?> hrs
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                (<?= e(formatear_horas_enteras($vistaHoras['horas_base'])) ?>h base + <?= e(formatear_horas_extra_minutos($vistaHoras['horas_extra_aprobadas'])) ?> extra)
                                            </div>
                                        <?php else: ?>
                                            <div>
                                                <strong class="text-slate-900"><?= e(formatear_horas_enteras($vistaHoras['horas_base'])) ?> hrs</strong>
                                                <span class="rrhh-badge bg-blue-100 text-blue-800 text-[11px] ms-1">
                                                    +<?= e(formatear_horas_extra_minutos($vistaHoras['horas_extra_sugeridas'])) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <strong class="text-slate-900"><?= e(formatear_horas_enteras((float) ($jornada['horas_calculadas'] ?? 0))) ?> hrs</strong>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    <span class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                        <i class="bi bi-check2 text-emerald-600"></i> <?= e((string) $jornada['marcas_vigentes']) ?> vigentes
                                    </span>
                                    <?php if ((int) $jornada['marcas_anuladas'] > 0): ?>
                                        <span class="block text-slate-400 text-[11px]">
                                            <?= e((string) $jornada['marcas_anuladas']) ?> anuladas
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a class="rrhh-button rrhh-btn-secondary !text-xs !py-1.5 !px-3 inline-flex items-center gap-1.5" href="<?= e($urlVerJornada) ?>">
                                        <i class="bi bi-eye"></i> <span>Revisar jornada</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-200/80">
                    <div>
                        <?php if ($paginaActual > 1): ?>
                            <a class="rrhh-button rrhh-btn-secondary !px-3 !py-1.5 !text-xs" href="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['pagina' => $paginaActual - 1])) ?>">
                                &laquo; Anterior
                            </a>
                        <?php else: ?>
                            <span class="rrhh-button rrhh-btn-secondary !px-3 !py-1.5 !text-xs opacity-50 cursor-not-allowed">&laquo; Anterior</span>
                        <?php endif; ?>
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Página <strong><?= $paginaActual ?></strong> de <strong><?= $totalPaginas ?></strong>
                    </div>

                    <div>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a class="rrhh-button rrhh-btn-secondary !px-3 !py-1.5 !text-xs" href="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['pagina' => $paginaActual + 1])) ?>">
                                Siguiente &raquo;
                            </a>
                        <?php else: ?>
                            <span class="rrhh-button rrhh-btn-secondary !px-3 !py-1.5 !text-xs opacity-50 cursor-not-allowed">Siguiente &raquo;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- Modal Emergente de Detalle y Gestión de Jornada (Ficha idéntica a Practicantes) -->
    <?php if ($jornadaSeleccionada !== null): ?>
        <?php
        $nombreSel = asistencia_gestion_nombre_practicante($jornadaSeleccionada);
        $partesSel = explode(' ', trim($nombreSel));
        $inicialesSel = strtoupper(substr($partesSel[0] ?? 'P', 0, 1) . substr($partesSel[1] ?? '', 0, 1));
        $diaSelNum = (int) date('N', strtotime((string) $jornadaSeleccionada['fecha']));
        $diaSelNombre = horarios_practicante_dias()[$diaSelNum] ?? '';
        $fechaSelFormato = asistencia_gestion_formatear_fecha((string) $jornadaSeleccionada['fecha']);
        ?>
        <div id="modalDetalleJornada" class="rrhh-practicante-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalDetalleJornadaTitulo" data-close-url="<?= e($urlListadoBase) ?>">
            <div class="rrhh-practicante-modal-dialog panel !max-w-4xl">
                <!-- Cabecera del Modal -->
                <div class="rrhh-practicante-modal-top">
                    <div class="rrhh-practicante-modal-header">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="rrhh-practicante-avatar rrhh-practicante-avatar--lg shrink-0" aria-hidden="true"><?= e($inicialesSel) ?></span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 id="modalDetalleJornadaTitulo" class="text-lg font-bold mb-0 text-slate-900"><?= e($nombreSel) ?></h2>
                                    <span class="rrhh-badge <?= e(rrhh_tone(e(asistencia_gestion_estado_badge((string) $jornadaSeleccionada['estado_jornada'])))) ?>">
                                        <?= e((string) $jornadaSeleccionada['estado_jornada']) ?>
                                    </span>
                                    <?php if ((int) ($jornadaSeleccionada['flag_ausencia_esperada'] ?? 0) === 1): ?>
                                        <span class="rrhh-badge bg-amber-100 text-amber-900">Ausencia esperada</span>
                                    <?php endif; ?>
                                    <?php if ($vistaHorasSel['tiene_horas_extra']): ?>
                                        <?php if ($vistaHorasSel['es_validada']): ?>
                                            <span class="rrhh-badge bg-emerald-100 text-emerald-800">Horas Extra Validadas</span>
                                        <?php else: ?>
                                            <span class="rrhh-badge bg-blue-100 text-blue-800">Horas Extra Pendientes</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-slate-500 text-xs sm:text-sm truncate mt-0.5">
                                    <i class="bi bi-calendar-check me-1"></i> <?= e($diaSelNombre) ?>, <?= e($fechaSelFormato) ?>
                                    <?php if (!empty($jornadaSeleccionada['email'])): ?>
                                        · <?= e((string) $jornadaSeleccionada['email']) ?>
                                    <?php endif; ?>
                                    · ID Práctica #<?= e((string) $jornadaSeleccionada['id_practica']) ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?= e($urlListadoBase) ?>" data-close-jornada-modal class="rrhh-practicante-modal-close shrink-0" aria-label="Cerrar detalle de jornada">
                            <i class="bi bi-x-lg"></i> <span>Cerrar</span>
                        </a>
                    </div>

                    <!-- Navegación interna por pestañas (Estilo ficha de practicante) -->
                    <nav class="rrhh-practicante-modal-nav" aria-label="Secciones de la jornada">
                        <a href="#jornada-resumen" class="rrhh-practicante-nav-pill is-active" data-jornada-nav-pill>
                            <i class="bi bi-clock-history"></i> <span>Resumen y horario</span>
                        </a>
                        <a href="#jornada-marcas" class="rrhh-practicante-nav-pill" data-jornada-nav-pill>
                            <i class="bi bi-list-check"></i> <span>Marcas registradas (<?= count($marcas) ?>)</span>
                        </a>
                        <a href="#jornada-bloques" class="rrhh-practicante-nav-pill" data-jornada-nav-pill>
                            <i class="bi bi-diagram-3"></i> <span>Bloques de área (<?= count($bloquesArea) ?>)</span>
                        </a>
                        <?php if ($puedeOperar): ?>
                            <a href="#jornada-acciones" class="rrhh-practicante-nav-pill rrhh-practicante-nav-pill--primary" data-jornada-nav-pill>
                                <i class="bi bi-tools"></i> <span>Acciones y correcciones</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Cuerpo del Modal con scroll interno suave -->
                <div class="rrhh-practicante-modal-body space-y-6">
                    <?php if (!$puedeOperar): ?>
                        <div class="rrhh-alert border-amber-200 bg-amber-50 text-amber-900" role="alert">
                            <i class="bi bi-shield-lock me-1"></i> No puedes operar directamente sobre esta jornada con el rol activo.
                        </div>
                    <?php endif; ?>

                    <?php if ($vistaHorasSel['es_revisada']): ?>
                        <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="alert">
                            <i class="bi bi-check2-all me-1"></i> Jornada revisada. Las horas extra que sigan pendientes requieren su aprobación por separado.
                        </div>
                    <?php endif; ?>

                    <?php if ($vistaHorasSel['tiene_horas_extra']): ?>
                        <?php if ($vistaHorasSel['es_validada']): ?>
                            <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800 flex flex-wrap items-center justify-between gap-2 p-4 rounded-xl" role="alert">
                                <div>
                                    <div class="text-base font-bold text-emerald-800 mb-1">
                                        ✓ Horas Validadas: <?= e(formatear_horas_enteras($vistaHorasSel['horas_validadas'])) ?> hrs
                                    </div>
                                    <div class="text-xs text-emerald-700">
                                        Esta jornada ya fue revisada y aprobada. Se suman <strong><?= e(formatear_horas_enteras($vistaHorasSel['horas_validadas'])) ?> horas</strong> (<?= e(formatear_horas_enteras($vistaHorasSel['horas_base'])) ?> base + <?= e(formatear_horas_extra_minutos($vistaHorasSel['horas_extra_aprobadas'])) ?> extra) al total acumulado del practicante.
                                    </div>
                                </div>
                                <span class="rrhh-badge bg-emerald-200 text-emerald-900 font-bold text-xs">HORAS APROBADAS</span>
                            </div>
                        <?php else: ?>
                            <div class="rrhh-alert border-cyan-200 bg-cyan-50 text-cyan-900 flex flex-wrap items-center justify-between gap-2 p-4 rounded-xl" role="alert">
                                <div>
                                    <div class="text-base font-bold text-blue-800 mb-1">
                                        Horas Extra por Confirmar: <?= e(formatear_horas_extra_minutos($vistaHorasSel['horas_extra_sugeridas'])) ?>
                                    </div>
                                    <div class="text-xs text-blue-700">
                                        El practicante registró marcas fuera de su horario. Mientras el supervisor no apruebe las horas extra, el sistema contempla automáticamente el tope de <strong><?= e(formatear_horas_enteras($vistaHorasSel['horas_base'])) ?> horas base</strong> cumplidas.
                                    </div>
                                </div>
                                <span class="rrhh-badge bg-blue-100 text-blue-800 font-bold text-xs">PENDIENTE CONFIRMACIÓN</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- SECCIÓN 1: Resumen y Horario -->
                    <div id="jornada-resumen" data-jornada-section>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                            <i class="bi bi-clock-history text-blue-600"></i> Resumen de tiempos y marcas reales
                        </h3>

                        <div class="grid grid-cols-12 gap-3 mb-4">
                            <div class="col-span-6 sm:col-span-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                <div class="text-xs text-slate-500 font-medium">Entrada real</div>
                                <strong class="text-sm text-slate-900 block mt-0.5">
                                    <?= e(asistencia_gestion_formatear_fecha_hora($jornadaSeleccionada['hora_entrada'] ?? null)) ?>
                                </strong>
                            </div>
                            <div class="col-span-6 sm:col-span-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                <div class="text-xs text-slate-500 font-medium">Salida real</div>
                                <strong class="text-sm text-slate-900 block mt-0.5">
                                    <?= e(asistencia_gestion_formatear_fecha_hora($jornadaSeleccionada['hora_salida'] ?? null)) ?>
                                </strong>
                            </div>
                            <div class="col-span-6 sm:col-span-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                <div class="text-xs text-slate-500 font-medium">Horas base (Turno)</div>
                                <strong class="text-sm block mt-0.5 <?= ($vistaHorasSel['tiene_horas_extra'] && !$vistaHorasSel['es_validada']) ? 'text-amber-700' : 'text-slate-900' ?>">
                                    <?= e(formatear_horas_enteras($vistaHorasSel['horas_base'])) ?> hrs
                                </strong>
                            </div>
                            <div class="col-span-6 sm:col-span-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                <div class="text-xs text-slate-500 font-medium">Horas extra</div>
                                <?php if ($vistaHorasSel['tiene_horas_extra']): ?>
                                    <?php if ($vistaHorasSel['es_validada']): ?>
                                        <strong class="text-sm text-emerald-700 block mt-0.5"><?= e(formatear_horas_extra_minutos($vistaHorasSel['horas_extra_aprobadas'])) ?> (Aprobadas)</strong>
                                    <?php else: ?>
                                        <strong class="text-sm text-blue-700 block mt-0.5"><?= e(formatear_horas_extra_minutos($vistaHorasSel['horas_extra_sugeridas'])) ?> (Por confirmar)</strong>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 block mt-0.5">Sin horas extra</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Horario pactado para este día -->
                        <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 mb-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Horario pactado para este día
                                </div>
                                <?php if ($horarioPactadoDia !== null): ?>
                                    <span class="rrhh-badge bg-white text-slate-700 border text-xs">
                                        <?= e((string) ($horarioPactadoDia['nombre_horario'] ?? 'Horario vigente')) ?>
                                        <?php if (!empty($horarioPactadoDia['es_excepcion'])): ?>
                                            (Excepción)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($horarioPactadoDia === null): ?>
                                <p class="text-xs text-slate-500 mb-0">
                                    ℹ️ <strong>Sin horario planificado:</strong> El practicante no tenía un turno programado para esta fecha (día no laboral o sin horario registrado).
                                </p>
                            <?php else: ?>
                                <div class="grid grid-cols-12 gap-2 text-xs">
                                    <div class="col-span-6 sm:col-span-3 bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <span class="text-slate-400 block">Entrada esperada</span>
                                        <strong class="text-slate-900 text-sm"><?= e(asistencia_gestion_formatear_hora($horarioPactadoDia['entrada_esperada'] ?? null)) ?></strong>
                                    </div>
                                    <div class="col-span-6 sm:col-span-3 bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <span class="text-slate-400 block">Salida esperada</span>
                                        <strong class="text-slate-900 text-sm"><?= e(asistencia_gestion_formatear_hora($horarioPactadoDia['salida_esperada'] ?? null)) ?></strong>
                                    </div>
                                    <div class="col-span-6 sm:col-span-3 bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <span class="text-slate-400 block">Colación</span>
                                        <strong class="text-slate-900 text-sm">
                                            <?php if (!empty($horarioPactadoDia['colacion_inicio']) && !empty($horarioPactadoDia['colacion_fin'])): ?>
                                                <?= e(asistencia_gestion_formatear_hora($horarioPactadoDia['colacion_inicio'])) ?> - <?= e(asistencia_gestion_formatear_hora($horarioPactadoDia['colacion_fin'])) ?>
                                            <?php else: ?>
                                                Sin colación
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                    <div class="col-span-6 sm:col-span-3 bg-white p-2.5 rounded-lg border border-slate-200/70">
                                        <span class="text-slate-400 block">Horas planificadas</span>
                                        <strong class="text-blue-700 text-sm"><?= e(formatear_horas_enteras($horarioPactadoDia['horas_planificadas'] ?? 0)) ?> hrs</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($jornadaSeleccionada['observacion'])): ?>
                            <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl">
                                <div class="text-xs font-bold text-amber-900 mb-0.5">Observación registrada</div>
                                <p class="text-xs text-amber-800 mb-0"><?= e((string) $jornadaSeleccionada['observacion']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SECCIÓN 2: Marcas Registradas -->
                    <div id="jornada-marcas" data-jornada-section hidden>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                            <i class="bi bi-list-check text-blue-600"></i> Historial de marcas de la jornada
                        </h3>

                        <?php if ($marcas === []): ?>
                            <p class="text-xs text-slate-500 mb-0">La jornada no tiene marcas registradas.</p>
                        <?php else: ?>
                            <div class="rrhh-table-scroll overflow-x-auto rounded-xl border border-slate-200/80">
                                <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Marca</th>
                                            <th>Fecha y hora</th>
                                            <th>Estado</th>
                                            <th>Observación</th>
                                            <th class="text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($marcas as $marca): ?>
                                            <?php $vigente = (int) $marca['activo'] === 1 && (int) $marca['anulada'] === 0; ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-slate-900 text-xs"><?= e((string) $marca['tipo_marca']) ?></strong>
                                                    <div class="text-slate-400 text-[11px]"><?= e((string) $marca['codigo']) ?></div>
                                                </td>
                                                <td class="whitespace-nowrap text-xs font-mono">
                                                    <?= e(asistencia_gestion_formatear_fecha_hora((string) $marca['fecha_hora'])) ?>
                                                </td>
                                                <td>
                                                    <span class="rrhh-badge <?= e(rrhh_tone($vigente ? 'success' : 'dark')) ?> text-[11px]">
                                                        <?= $vigente ? 'VIGENTE' : 'ANULADA' ?>
                                                    </span>
                                                    <?php if (!$vigente && !empty($marca['motivo_anulacion'])): ?>
                                                        <div class="text-slate-400 text-[11px] mt-0.5"><?= e((string) $marca['motivo_anulacion']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-xs text-slate-600">
                                                    <?= e((string) ($marca['observacion'] ?? 'Sin observación')) ?>
                                                </td>
                                                <td class="text-right whitespace-nowrap">
                                                    <?php if ($vigente && $puedeOperar): ?>
                                                        <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>" class="inline-flex items-center gap-1.5">
                                                            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                            <input type="hidden" name="accion" value="anular_marca">
                                                            <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">
                                                            <input type="hidden" name="id_marca" value="<?= e((string) $marca['id_marca']) ?>">
                                                            <input class="rrhh-input !h-7 !text-xs !py-0 !px-2 w-32" name="motivo" maxlength="255" placeholder="Motivo..." required>
                                                            <button class="rrhh-button rrhh-btn-danger-soft !h-7 !text-xs !py-0 !px-2.5" type="submit">
                                                                Anular
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 text-xs">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SECCIÓN 3: Bloques de Área -->
                    <div id="jornada-bloques" data-jornada-section hidden>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                            <i class="bi bi-diagram-3 text-blue-600"></i> Bloques de área asignados
                        </h3>

                        <?php if ($bloquesArea === []): ?>
                            <p class="text-xs text-slate-500 mb-4">La jornada no tiene bloques de área registrados.</p>
                        <?php else: ?>
                            <div class="rrhh-table-scroll overflow-x-auto rounded-xl border border-slate-200/80 mb-4">
                                <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Área</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Horas</th>
                                            <th>Asignado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bloquesArea as $bloqueArea): ?>
                                            <tr>
                                                <td class="font-semibold text-xs"><?= e((string) $bloqueArea['area_nombre']) ?></td>
                                                <td class="text-xs font-mono"><?= e(asistencia_gestion_formatear_fecha_hora((string) $bloqueArea['hora_inicio'])) ?></td>
                                                <td class="text-xs font-mono"><?= e(asistencia_gestion_formatear_fecha_hora($bloqueArea['hora_fin'] ?? null)) ?></td>
                                                <td class="text-xs font-bold text-blue-700"><?= e((string) $bloqueArea['horas_asignadas']) ?> hrs</td>
                                                <td class="text-xs text-slate-500"><?= e((string) ($bloqueArea['asignado_por_email'] ?? 'No registrado')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($puedeOperar): ?>
                            <div class="p-4 sm:p-5 bg-slate-50/80 rounded-2xl border border-slate-200/80">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
                                    <i class="bi bi-plus-circle text-blue-600"></i> Agregar nuevo bloque de área
                                </div>
                                <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                    <input type="hidden" name="accion" value="registrar_bloque_area">
                                    <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                    <div class="grid grid-cols-12 gap-3 items-end">
                                        <div class="col-span-12 md:col-span-4">
                                            <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1 block" for="id_area_bloque">Área</label>
                                            <select class="rrhh-select w-full !h-10 !text-xs rounded-xl" id="id_area_bloque" name="id_area" <?= $areasCatalogo === [] ? 'disabled' : '' ?> required>
                                                <option value="">Seleccionar área...</option>
                                                <?php foreach ($areasCatalogo as $area): ?>
                                                    <option value="<?= e((string) $area['id_area']) ?>"><?= e((string) $area['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-span-12 sm:col-span-6 md:col-span-3">
                                            <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1 block" for="hora_inicio_bloque">Inicio</label>
                                            <div class="flex gap-1.5 items-center">
                                                <input class="rrhh-input flex-1 min-w-0 !h-10 !text-xs rounded-xl" type="date" id="hora_inicio_bloque" name="hora_inicio_bloque_fecha" value="<?= e((string) $jornadaSeleccionada['fecha']) ?>" <?= $areasCatalogo === [] ? 'disabled' : '' ?> required>
                                                <input class="rrhh-input w-20 shrink-0 !h-10 !text-xs text-center font-mono rounded-xl" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora 24 h" data-hora24 name="hora_inicio_bloque_hora" value="09:00" <?= $areasCatalogo === [] ? 'disabled' : '' ?> required>
                                            </div>
                                        </div>
                                        <div class="col-span-12 sm:col-span-6 md:col-span-3">
                                            <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1 block" for="hora_fin_bloque">Fin</label>
                                            <div class="flex gap-1.5 items-center">
                                                <input class="rrhh-input flex-1 min-w-0 !h-10 !text-xs rounded-xl" type="date" id="hora_fin_bloque" name="hora_fin_bloque_fecha" value="<?= e((string) $jornadaSeleccionada['fecha']) ?>" <?= $areasCatalogo === [] ? 'disabled' : '' ?> required>
                                                <input class="rrhh-input w-20 shrink-0 !h-10 !text-xs text-center font-mono rounded-xl" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora 24 h" data-hora24 name="hora_fin_bloque_hora" value="13:00" <?= $areasCatalogo === [] ? 'disabled' : '' ?> required>
                                            </div>
                                        </div>
                                        <div class="col-span-12 md:col-span-2">
                                            <button class="rrhh-button rrhh-btn-primary w-full justify-center !h-10 !text-xs font-semibold rounded-xl shadow-xs" type="submit" <?= $areasCatalogo === [] ? 'disabled' : '' ?>>
                                                <i class="bi bi-plus-circle me-1"></i> Agregar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SECCIÓN 4: Acciones y Correcciones -->
                    <?php if ($puedeOperar): ?>
                        <div id="jornada-acciones" data-jornada-section hidden class="space-y-6">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-1 flex items-center gap-1.5">
                                <i class="bi bi-tools text-blue-600"></i> Acciones correctivas y estados
                            </h3>

                            <div class="grid grid-cols-12 gap-4">
                                <!-- Formulario 1: Marca Correctiva -->
                                <div class="col-span-12 md:col-span-6">
                                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 h-full flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-1.5">
                                                <i class="bi bi-fingerprint text-blue-600"></i> Registrar marca correctiva
                                            </div>
                                            <p class="text-xs text-slate-500 mb-3">Agrega una marca manual por omisión o error del practicante.</p>

                                            <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                <input type="hidden" name="accion" value="registrar_marca">
                                                <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1" for="codigo_marca">Tipo de marca</label>
                                                        <select class="rrhh-select w-full" id="codigo_marca" name="codigo_marca">
                                                            <?php foreach ($tiposMarca as $tipo): ?>
                                                                <option value="<?= e((string) $tipo['codigo']) ?>"><?= e((string) $tipo['nombre']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1" for="fecha_hora">Fecha y hora (24 h)</label>
                                                        <div class="flex gap-2">
                                                            <input class="rrhh-input flex-1 !text-xs" type="date" id="fecha_hora" name="fecha_hora_fecha" value="<?= e(substr($fechaHoraDefault, 0, 10)) ?>" required>
                                                            <input class="rrhh-input w-24 shrink-0 !text-xs" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora 24 h" data-hora24 name="fecha_hora_hora" value="<?= e(substr($fechaHoraDefault, 11, 5)) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1" for="observacion">Motivo u observación</label>
                                                        <input class="rrhh-input w-full !text-xs" id="observacion" name="observacion" maxlength="255" placeholder="Ej: Olvido de marcaje justificado">
                                                    </div>
                                                </div>

                                                <button class="rrhh-button rrhh-btn-primary w-full justify-center !text-xs mt-4" type="submit">
                                                    <i class="bi bi-save me-1"></i> Registrar marca
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario 2: Estado de Revisión -->
                                <div class="col-span-12 md:col-span-6">
                                    <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 h-full flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-1.5">
                                                <i class="bi bi-check-square text-blue-600"></i> Actualizar estado de revisión
                                            </div>
                                            <p class="text-xs text-slate-500 mb-3">Cambia la calificación operativa de la jornada.</p>

                                            <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1" for="estado_jornada">Nuevo estado</label>
                                                        <select class="rrhh-select w-full" id="estado_jornada" name="estado_jornada">
                                                            <option value="OBSERVADA">OBSERVADA</option>
                                                            <option value="PENDIENTE_REVISION">PENDIENTE_REVISION</option>
                                                            <?php if (in_array($jornadaSeleccionada['estado_jornada'], ['OBSERVADA', 'PENDIENTE_REVISION'], true)): ?>
                                                                <option value="REVISADA">Revisado / Sin cambios en marcas</option>
                                                                <option value="CORREGIDA">Corregido / Cerrar corrección</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="rrhh-label text-xs font-semibold text-slate-600 mb-1" for="motivo_estado">Motivo del cambio</label>
                                                        <input class="rrhh-input w-full !text-xs" id="motivo_estado" name="motivo" maxlength="255" placeholder="Obligatorio para auditoría..." required>
                                                    </div>
                                                </div>

                                                <button class="rrhh-button rrhh-btn-secondary w-full justify-center !text-xs mt-4" type="submit">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Actualizar estado
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario 3: Resolver Ausencia Esperada (Si aplica) -->
                            <?php if ((int) ($jornadaSeleccionada['flag_ausencia_esperada'] ?? 0) === 1): ?>
                                <div class="p-4 bg-amber-50/80 rounded-xl border border-amber-200/80">
                                    <div class="text-xs font-bold uppercase tracking-wider text-amber-900 mb-2 flex items-center gap-1.5">
                                        <i class="bi bi-question-circle text-amber-700"></i> Resolver ausencia esperada
                                    </div>
                                    <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="accion" value="resolver_ausencia_esperada">
                                        <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                        <div class="grid grid-cols-12 gap-3 items-end">
                                            <div class="col-span-12 sm:col-span-4">
                                                <label class="rrhh-label text-xs font-semibold text-amber-900 mb-1" for="resultado_ausencia">Resolución</label>
                                                <select class="rrhh-select w-full" id="resultado_ausencia" name="resultado_ausencia" required>
                                                    <option value="JUSTIFICADA">JUSTIFICADA</option>
                                                    <option value="NO_JUSTIFICADA">NO_JUSTIFICADA</option>
                                                    <option value="PENDIENTE_REVISION">PENDIENTE_REVISION</option>
                                                </select>
                                            </div>
                                            <div class="col-span-12 sm:col-span-5">
                                                <label class="rrhh-label text-xs font-semibold text-amber-900 mb-1" for="motivo_ausencia">Motivo de resolución</label>
                                                <input class="rrhh-input w-full !text-xs" id="motivo_ausencia" name="motivo" maxlength="255" required>
                                            </div>
                                            <div class="col-span-12 sm:col-span-3">
                                                <button class="rrhh-button rrhh-btn-success w-full justify-center !text-xs !h-10" type="submit">
                                                    Resolver ausencia
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario 4: Aprobación de Horas Extra (Si aplica) -->
                            <?php if (!$politicaTiempoActiva && ($vistaHorasSel['tiene_horas_extra'] || $jornadaSeleccionada['horas_extra_aprobadas'] !== null)): ?>
                                <div class="p-4 bg-emerald-50/80 rounded-xl border border-emerald-200/80">
                                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-900 mb-2 flex items-center gap-1.5">
                                        <i class="bi bi-patch-check text-emerald-700"></i> <?= $vistaHorasSel['es_validada'] ? 'Modificar Horas Extra Validadas' : 'Aprobación de Horas Extra' ?>
                                    </div>
                                    <p class="text-xs text-emerald-800 mb-3">
                                        Al aprobar las horas extra, el sistema sumará las <strong>horas base (<?= e(formatear_horas_enteras($vistaHorasSel['horas_base'])) ?> hrs)</strong> más las horas extra autorizadas al avance total del practicante.
                                    </p>

                                    <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="accion" value="aprobar_horas_extra">
                                        <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                        <div class="grid grid-cols-12 gap-3 items-end">
                                            <div class="col-span-12 sm:col-span-4">
                                                <label class="rrhh-label text-xs font-semibold text-emerald-900 mb-1" for="horas_extra_aprobadas">Horas extra a aprobar</label>
                                                <input class="rrhh-input w-full" type="number" step="0.01" min="0" max="12" id="horas_extra_aprobadas" name="horas_extra_aprobadas" value="<?= e($jornadaSeleccionada['horas_extra_aprobadas'] !== null ? (string) $jornadaSeleccionada['horas_extra_aprobadas'] : number_format($vistaHorasSel['horas_extra_sugeridas'], 2, '.', '')) ?>" required>
                                                <span class="text-[11px] text-emerald-700 block mt-1">
                                                    Detectadas: <strong><?= e(formatear_horas_extra_minutos($vistaHorasSel['horas_extra_sugeridas'])) ?></strong> (<?= e(number_format($vistaHorasSel['horas_extra_sugeridas'], 2, '.', '')) ?> hrs)
                                                </span>
                                            </div>
                                            <div class="col-span-12 sm:col-span-5">
                                                <label class="rrhh-label text-xs font-semibold text-emerald-900 mb-1" for="motivo_horas_extra">Motivo de aprobación</label>
                                                <input class="rrhh-input w-full !text-xs" id="motivo_horas_extra" name="motivo" maxlength="255" placeholder="Ej: Apoyo en proyecto extraordinario" required>
                                            </div>
                                            <div class="col-span-12 sm:col-span-3">
                                                <button class="rrhh-button rrhh-btn-success w-full justify-center !text-xs !h-10" type="submit">
                                                    <?= $vistaHorasSel['es_validada'] ? 'Actualizar Horas' : 'Aprobar y Validar' ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario 5: Anulación de Jornada (Solo ADMIN) -->
                            <?php if ($rolEsperado === 'ADMIN'): ?>
                                <div class="p-4 bg-red-50/80 rounded-xl border border-red-200/80">
                                    <div class="text-xs font-bold uppercase tracking-wider text-red-900 mb-2 flex items-center gap-1.5">
                                        <i class="bi bi-trash3 text-red-700"></i> Anulación de jornada completa
                                    </div>
                                    <form method="post" action="<?= e(asistencia_gestion_url_con_filtros($rutaActual, $filtros, ['id_jornada' => (int) $jornadaSeleccionada['id_jornada']])) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                        <input type="hidden" name="accion" value="anular_jornada">
                                        <input type="hidden" name="id_jornada" value="<?= e((string) $jornadaSeleccionada['id_jornada']) ?>">

                                        <div class="space-y-3">
                                            <div>
                                                <label class="rrhh-label text-xs font-semibold text-red-900 mb-1" for="motivo_anulacion">Motivo de anulación completa</label>
                                                <input class="rrhh-input w-full !text-xs" id="motivo_anulacion" name="motivo" maxlength="255" required>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input class="rrhh-checkbox" type="checkbox" value="1" id="confirmar_anulacion" name="confirmar_anulacion" required>
                                                <label class="text-xs text-red-800" for="confirmar_anulacion">Confirmo la anulación completa de esta jornada y sus marcas.</label>
                                            </div>
                                            <button class="rrhh-button rrhh-btn-danger !text-xs !py-2 px-4" type="submit">
                                                Anular jornada definitivamente
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pie del Modal -->
                <div class="flex items-center justify-between p-3.5 sm:p-4 bg-slate-50 border-t border-slate-200/80 rounded-b-2xl">
                    <span class="text-xs text-slate-400 hidden sm:inline">Presiona <kbd class="px-1.5 py-0.5 bg-white border rounded text-[10px] font-mono shadow-sm">Esc</kbd> o haz clic fuera para cerrar</span>
                    <a href="<?= e($urlListadoBase) ?>" data-close-jornada-modal class="rrhh-button rrhh-btn-secondary !text-xs !py-1.5 !px-4 ms-auto">
                        Cerrar ficha
                    </a>
                </div>
            </div>
        </div>

        <script>
            (function () {
                'use strict';
                const modal = document.getElementById('modalDetalleJornada');
                if (!modal) return;

                const closeUrl = modal.getAttribute('data-close-url') || window.location.pathname;

                function closeModal() {
                    modal.hidden = true;
                    modal.style.display = 'none';
                    document.body.classList.remove('rrhh-practicante-modal-open');
                    window.location.href = closeUrl;
                }

                document.body.classList.add('rrhh-practicante-modal-open');

                modal.querySelectorAll('[data-close-jornada-modal]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        closeModal();
                    });
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                window.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.hidden) {
                        closeModal();
                    }
                });

                // Navegación fluida por pestañas internas del modal
                const navPills = Array.from(modal.querySelectorAll('[data-jornada-nav-pill]'));
                const sections = Array.from(modal.querySelectorAll('[data-jornada-section]'));

                navPills.forEach(function (pill) {
                    pill.addEventListener('click', function (e) {
                        e.preventDefault();
                        const targetId = (pill.getAttribute('href') || '').replace('#', '');
                        if (!targetId) return;

                        navPills.forEach(p => p.classList.remove('is-active'));
                        pill.classList.add('is-active');

                        sections.forEach(function (sec) {
                            if (sec.id === targetId) {
                                sec.hidden = false;
                            } else {
                                sec.hidden = true;
                            }
                        });
                    });
                });
            })();
        </script>
    <?php endif; ?>
</div>
