<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
// Trasladé la plantilla desde Services/PermisosService.php; recibe los datos preparados por el controlador. 
?>

<?php // Avisos flotantes: Aprobar/Rechazar recargan en #listado-permisos y un aviso arriba de la página quedaba fuera de la vista. 
?>
<?php if ($mensajeExito !== ''): ?>
    <div class="rrhh-alert rrhh-aviso-flotante rrhh-aviso-flotante--exito border-emerald-200 bg-emerald-50 text-emerald-800 pr-12" role="alert">
        <?= icono('circle-check') ?><span><?= e($mensajeExito) ?></span>
        <button type="button" class="rrhh-close" data-ui-dismiss-alert aria-label="Cerrar aviso"></button>
    </div>
<?php endif; ?>

<?php if ($errores !== []): ?>
    <div class="rrhh-alert rrhh-aviso-flotante border-red-200 bg-red-50 text-red-800 pr-12" role="alert">
        <?= icono('circle-alert') ?>
        <ul class="mb-0 pl-4">
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="rrhh-close" data-ui-dismiss-alert aria-label="Cerrar aviso"></button>
    </div>
<?php endif; ?>

<?php // Integré las categorías en la misma bandeja; cada detalle se abre en un diálogo. 
?>
<section class="rrhh-card p-4 mb-6 space-y-3">
    <h2 class="text-lg font-bold">Solicitudes de permisos de practicantes</h2>
    <nav class="flex flex-wrap gap-2" aria-label="Categorías de permisos">
        <?php foreach (['ausencias' => 'Ausencias y recuperación', 'tiempo' => 'Tiempo excepcional', 'compensacion' => 'Compensación', 'colacion' => 'Cambio de colación'] as $valor => $etiqueta): ?>
            <a class="rrhh-button <?= $categoriaPermisos === $valor ? 'rrhh-btn-primary' : 'rrhh-btn-secondary' ?>" href="<?= e(url($rutaActual . '?' . http_build_query(['categoria' => $valor, 'limite' => $limiteSeleccionado]))) ?>" <?= $categoriaPermisos === $valor ? 'aria-current="page"' : '' ?>><?= e($etiqueta) ?><?= $valor === 'colacion' ? ' (' . (int)$totalColaciones . ')' : ($valor !== 'ausencias' ? ' (' . (int)$bandejaTiempo['conteos'][$valor] . ')' : '') ?></a>
        <?php endforeach; ?>
    </nav>
</section>
<?php if ($categoriaPermisos === 'colacion'): ?>
    <?php require VIEWS_PATH . '/components/cambio-colacion.php';
    return; ?>
<?php elseif ($categoriaPermisos !== 'ausencias'): ?>
    <?php require VIEWS_PATH . '/shared/permisos-tiempo.view.php'; ?>
    <?php return; ?>
<?php endif; ?>
<!-- Tarjetas de resumen métrico (con jerarquía tipográfica limpia y espaciado uniforme) -->
<div class="grid grid-cols-12 gap-4 mb-6">
    <div class="col-span-12 md:col-span-4">
        <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
            <div class="flex justify-between items-center gap-4">
                <div>
                    <div class="text-slate-500 text-xs font-semibold mb-1.5">Pendientes de revisión</div>
                    <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-amber-700"><?= (int) $datos['pendientes'] ?></strong>
                    <span class="text-xs text-slate-400 mt-1.5 block font-medium">Solicitudes por resolver</span>
                </div>
                <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--warning w-12 h-12 shrink-0 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                    <?= icono('hourglass') ?>
                </div>
            </div>
        </section>
    </div>
    <div class="col-span-12 md:col-span-4">
        <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
            <div class="flex justify-between items-center gap-4">
                <div>
                    <div class="text-slate-500 text-xs font-semibold mb-1.5">Permisos aprobados</div>
                    <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-emerald-700"><?= (int) $datos['aprobados'] ?></strong>
                    <span class="text-xs text-slate-400 mt-1.5 block font-medium">Ausencias autorizadas</span>
                </div>
                <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--success w-12 h-12 shrink-0 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                    <?= icono('circle-check') ?>
                </div>
            </div>
        </section>
    </div>
    <div class="col-span-12 md:col-span-4">
        <section class="rrhh-card border border-slate-200/80 shadow-sm p-5 bg-white h-full rounded-2xl">
            <div class="flex justify-between items-center gap-4">
                <div>
                    <div class="text-slate-500 text-xs font-semibold mb-1.5">Permisos rechazados</div>
                    <strong class="text-3xl font-black tracking-tight leading-none block mb-0 text-red-700"><?= (int) $datos['rechazados'] ?></strong>
                    <span class="text-xs text-slate-400 mt-1.5 block font-medium">Solicitudes denegadas</span>
                </div>
                <!-- Destaqué el indicador de rechazos con un fondo e icono rojos adaptados al portal temático. -->
                <div class="rrhh-permiso-kpi-icon rrhh-permiso-kpi-icon--danger permission-rejected-icon w-12 h-12 shrink-0 rounded-2xl bg-red-50 border border-red-200/80 text-red-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                    <?= icono('octagon-x') ?>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
// Construí los enlaces de las pestañas de estado conservando el límite y los filtros de fecha/búsqueda.
$totalGlobalPermisos = (int) ($datos['pendientes'] ?? 0) + (int) ($datos['aprobados'] ?? 0) + (int) ($datos['rechazados'] ?? 0);
$pestanasEstado = [
    [
        'valor'    => '',
        'etiqueta' => 'Todos',
        'icono'    => 'layers',
        'variante' => 'all',
        'conteo'   => $totalGlobalPermisos,
    ],
    [
        'valor'    => 'PENDIENTE',
        'etiqueta' => 'Pendientes de revisión',
        'icono'    => 'hourglass',
        'variante' => 'warning',
        'conteo'   => (int) ($datos['pendientes'] ?? 0),
    ],
    [
        'valor'    => 'APROBADO',
        'etiqueta' => 'Aprobados',
        'icono'    => 'circle-check',
        'variante' => 'success',
        'conteo'   => (int) ($datos['aprobados'] ?? 0),
    ],
    [
        'valor'    => 'RECHAZADO',
        'etiqueta' => 'Rechazados',
        'icono'    => 'octagon-x',
        'variante' => 'danger',
        'conteo'   => (int) ($datos['rechazados'] ?? 0),
    ],
];
?>

<!-- Pestañas de Estado + Filtros y Buscador -->
<section class="rrhh-card border shadow-sm mb-6">
    <div class="p-4 sm:p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
            <nav class="rrhh-permisos-tabs inline-flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5" aria-label="Filtrar solicitudes por estado">
                <?php foreach ($pestanasEstado as $pestana): ?>
                    <?php
                    $esActiva = (string) $filtros['estado'] === $pestana['valor'];
                    $paramsPestana = array_filter([
                        'estado' => $pestana['valor'],
                        'desde'  => $filtros['desde'],
                        'hasta'  => $filtros['hasta'],
                        'q'      => $filtros['q'],
                        'limite' => (string) ((int) ($limiteSeleccionado ?? 5)),
                    ], static fn(string $v): bool => $v !== '');
                    $urlPestana = url($rutaActual . ($paramsPestana !== [] ? '?' . http_build_query($paramsPestana) : '')) . '#listado-permisos';
                    ?>
                    <a
                        href="<?= e($urlPestana) ?>"
                        data-permisos-page-link
                        class="rrhh-permisos-tab rrhh-permisos-tab--<?= e($pestana['variante']) ?> inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold"
                        <?= $esActiva ? 'aria-current="page"' : '' ?>>
                        <?= icono($pestana['icono'], 'rrhh-permisos-tab-icon') ?>
                        <span><?= e($pestana['etiqueta']) ?></span>
                        <span class="rrhh-permisos-tab-count inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold leading-none">
                            <?= (int) $pestana['conteo'] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if ($filtros['estado'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '' || $filtros['q'] !== ''): ?>
                <a href="<?= e(url($rutaActual . '?limite=' . (int) ($limiteSeleccionado ?? 5))) ?>#listado-permisos" data-permisos-page-link class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    <?= icono('rotate-ccw') ?>Limpiar filtros
                </a>
            <?php endif; ?>
        </div>

        <form class="grid grid-cols-12 gap-4 items-end" method="get" action="<?= e(url($rutaActual)) ?>#listado-permisos">
            <input type="hidden" name="limite" value="<?= (int) ($limiteSeleccionado ?? 5) ?>">
            <input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>">
            <div class="col-span-12 sm:col-span-6 md:col-span-3">
                <label class="rrhh-label text-xs font-semibold text-slate-500" for="desde">Ausencia desde</label>
                <input class="rrhh-input !py-1.5 !text-xs" type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>">
            </div>
            <div class="col-span-12 sm:col-span-6 md:col-span-3">
                <label class="rrhh-label text-xs font-semibold text-slate-500" for="hasta">Ausencia hasta</label>
                <input class="rrhh-input !py-1.5 !text-xs" type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>">
            </div>
            <div class="col-span-12 md:col-span-4">
                <label class="rrhh-label text-xs font-semibold text-slate-500" for="q">Buscar practicante</label>
                <input class="rrhh-input !py-1.5 !text-xs" id="q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, apellido, email o RUT...">
            </div>
            <div class="col-span-12 md:col-span-2">
                <button class="rrhh-button rrhh-btn-primary rrhh-btn-sm w-full" type="submit">
                    <?= icono('funnel') ?>Filtrar
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Listado de Permisos -->
<?php
// Al aprobar o rechazar se vuelve a la misma pestaña y filtros: desde "Pendientes" la solicitud resuelta sale de la lista.
$paramsRetorno = array_filter([
    'estado' => $filtros['estado'],
    'desde'  => $filtros['desde'],
    'hasta'  => $filtros['hasta'],
    'q'      => $filtros['q'],
    'limite' => (string) ((int) ($limiteSeleccionado ?? 5)),
], static fn(string $v): bool => $v !== '');
$urlRetornoListado = url($rutaActual . ($paramsRetorno !== [] ? '?' . http_build_query($paramsRetorno) : '')) . '#listado-permisos';
?>
<section id="listado-permisos" class="rrhh-card border shadow-sm scroll-mt-16">
    <div class="border-b border-slate-200 px-4 py-4 bg-white flex flex-wrap justify-between items-center gap-3">
        <h2 class="text-lg mb-0 font-bold text-slate-900">
            <?= icono('mail', 'text-blue-700 mr-2') ?>Solicitudes de permisos de practicantes
        </h2>
        <div class="flex flex-wrap items-center gap-2">
            <?php // Selector de cantidad de registros por página (5 por defecto, 10, 25, 50) con ancla a la tabla 
            ?>
            <form method="get" action="<?= e(url($rutaActual)) ?>#listado-permisos" class="inline-flex items-center gap-1.5">
                <?php if ($filtros['estado'] !== ''): ?><input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>"><?php endif; ?>
                <?php if ($filtros['desde'] !== ''): ?><input type="hidden" name="desde" value="<?= e($filtros['desde']) ?>"><?php endif; ?>
                <?php if ($filtros['hasta'] !== ''): ?><input type="hidden" name="hasta" value="<?= e($filtros['hasta']) ?>"><?php endif; ?>
                <?php if ($filtros['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filtros['q']) ?>"><?php endif; ?>
                <label for="limitePermisos" class="text-xs font-semibold text-slate-500">Mostrar:</label>
                <select id="limitePermisos" name="limite" class="rrhh-select !w-auto !py-1 !pl-2.5 !pr-7 !text-xs" onchange="this.form.submit()">
                    <?php foreach (($limitesPermitidos ?? [5, 10, 20, 25, 50]) as $opcionLimite): ?>
                        <option value="<?= (int) $opcionLimite ?>" <?= ((int) ($limiteSeleccionado ?? 5)) === (int) $opcionLimite ? 'selected' : '' ?>><?= (int) $opcionLimite ?> por pág.</option>
                    <?php endforeach; ?>
                </select>
            </form>
            <span class="rrhh-badge bg-slate-50 text-slate-700 border"><?= $datos['total'] ?> registros encontrados</span>
        </div>
    </div>
    <div class="p-4 p-0">
        <?php if ($permisos === []): ?>
            <div class="text-center py-12 text-slate-500">
                <?= icono('inbox', 'rrhh-icono-vacio text-slate-500 mb-2') ?>
                <p class="mb-0">No se encontraron solicitudes de permisos para los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="rrhh-table-scroll overflow-x-auto">
                <!-- Tabla compacta como en Practicantes: el detalle completo vive en la ventana "Revisar solicitud". -->
                <table class="rrhh-table rrhh-table-hover align-middle mb-0">
                    <thead class="bg-slate-50">
                        <tr>
                            <th>Practicante</th>
                            <th>Ausencia</th>
                            <th>Motivo</th>
                            <th>Recuperación</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permisos as $permiso): ?>
                            <?php
                            $nombrePracticante = trim(implode(' ', array_filter([
                                $permiso['nombre_1']        ?? '',
                                $permiso['nombre_2']        ?? '',
                                $permiso['apellido_paterno'] ?? '',
                                $permiso['apellido_materno'] ?? '',
                            ])));
                            // mb_* para no cortar nombres con tilde (Á, É…) al formar las iniciales del avatar.
                            $inicialesPracticante = mb_strtoupper(
                                mb_substr(trim((string) ($permiso['nombre_1'] ?? 'P')), 0, 1)
                                    . mb_substr(trim((string) ($permiso['apellido_paterno'] ?? '')), 0, 1)
                            );
                            $esParcial     = ($permiso['tipo_ausencia'] ?? 'COMPLETA') === 'PARCIAL';
                            $esPendiente   = $permiso['estado'] === 'PENDIENTE';
                            $enObservacion = $esPendiente && !empty($permiso['requiere_revision']);
                            $badgeEstado   = match ($permiso['estado']) {
                                'APROBADO'  => 'success',
                                'RECHAZADO' => 'danger',
                                default     => 'warning',
                            };
                            // Estado en mayúscula inicial y con ícono, igual que los estados de Practicantes.
                            [$estadoTexto, $estadoIcono] = match (true) {
                                $enObservacion                    => ['En observación', 'circle-alert'],
                                $permiso['estado'] === 'APROBADO'  => ['Aprobado', 'circle-check'],
                                $permiso['estado'] === 'RECHAZADO' => ['Rechazado', 'octagon-x'],
                                default                           => ['Pendiente', 'hourglass'],
                            };
                            $fechaAusenciaTexto = date('d-m-Y', strtotime((string) $permiso['fecha_ausencia']));
                            $diaAusenciaTexto   = horarios_practicante_dias()[(int) date('N', strtotime((string) $permiso['fecha_ausencia']))];
                            $tramoAusenciaTexto = $esParcial
                                ? substr((string) $permiso['hora_inicio_ausencia'], 0, 5) . '–' . substr((string) $permiso['hora_fin_ausencia'], 0, 5)
                                : '';

                            // Evalué la programación real del plan de recuperación (fechas, horas y estado de ejecución).
                            $tramosRecup         = $permiso['recuperaciones'] ?? [];
                            $tieneTramos         = !empty($tramosRecup);
                            $horasRecuperarNum   = (float) ($permiso['horas_a_recuperar'] ?? 0);
                            $tieneRecupHistorica = !empty($permiso['fecha_recuperacion']) && $horasRecuperarNum > 0;
                            $tieneRecuperacion   = $tieneTramos || $tieneRecupHistorica;
                            $esRechazado         = $permiso['estado'] === 'RECHAZADO';
                            $recupInfo           = [
                                'tiene'         => $tieneRecuperacion,
                                'titulo'        => '',
                                'detalle'       => '',
                                'es_rechazado'  => $esRechazado,
                                'esta_validado' => false,
                            ];

                            if ($tieneRecuperacion) {
                                if (count($tramosRecup) > 1) {
                                    $minTotales   = array_sum(array_column($tramosRecup, 'minutos_planificados'));
                                    $minValidados = array_sum(array_column($tramosRecup, 'minutos_validados'));
                                    $recupInfo['titulo'] = count($tramosRecup) . ' fechas (desde ' . date('d-m', strtotime((string) $tramosRecup[0]['fecha'])) . ')';
                                    if ($esRechazado) {
                                        $recupInfo['detalle'] = 'Plan descartado';
                                    } elseif ($minValidados >= $minTotales && $minTotales > 0) {
                                        $recupInfo['esta_validado'] = true;
                                        $recupInfo['detalle']       = formatear_horas_enteras(round($minValidados / 60, 2)) . ' hrs recuperadas';
                                    } elseif ($minValidados > 0) {
                                        $recupInfo['detalle']       = formatear_horas_enteras(round($minValidados / 60, 2)) . ' de ' . formatear_horas_enteras(round($minTotales / 60, 2)) . ' hrs recuperadas';
                                    } else {
                                        $recupInfo['detalle']       = formatear_horas_enteras(round($minTotales / 60, 2)) . ' hrs distribuidas';
                                    }
                                } else {
                                    $fechaR          = $tieneTramos ? (string) $tramosRecup[0]['fecha'] : (string) $permiso['fecha_recuperacion'];
                                    $fechaRTexto     = date('d-m-Y', strtotime($fechaR));
                                    $diaRTexto       = horarios_practicante_dias()[(int) date('N', strtotime($fechaR))];
                                    $horaIniR        = $tieneTramos ? substr((string) $tramosRecup[0]['hora_inicio'], 0, 5) : substr((string) ($permiso['hora_inicio_recuperacion'] ?? '00:00'), 0, 5);
                                    $horaFinR        = $tieneTramos ? substr((string) $tramosRecup[0]['hora_fin'], 0, 5) : substr((string) ($permiso['hora_fin_recuperacion'] ?? '00:00'), 0, 5);
                                    $minPlanR        = $tieneTramos ? (int) $tramosRecup[0]['minutos_planificados'] : (int) round((float) ($permiso['horas_a_recuperar'] ?? 0) * 60);
                                    $minValR         = $tieneTramos ? (int) $tramosRecup[0]['minutos_validados'] : 0;
                                    $horasPlanTexto  = formatear_horas_enteras(round($minPlanR / 60, 2)) . ' hrs';

                                    $recupInfo['titulo'] = $fechaRTexto;
                                    if ($esRechazado) {
                                        $recupInfo['detalle'] = 'Plan descartado';
                                    } elseif ($minValR > 0) {
                                        $recupInfo['esta_validado'] = true;
                                        $recupInfo['detalle']       = $diaRTexto . ' · ' . formatear_horas_enteras(round($minValR / 60, 2)) . ' hrs recuperadas';
                                    } else {
                                        $recupInfo['detalle']       = $diaRTexto . ' · ' . $horaIniR . '–' . $horaFinR . ' (' . $horasPlanTexto . ')';
                                    }

                                    // Evaluación del margen de 5 días hábiles y aviso visual de sábado
                                    if ($esPendiente && (int) date('N', strtotime($fechaR)) === 6 && function_exists('recuperaciones_calcular_plazo_aprobacion')) {
                                        $recupInfo['plazo_aprobacion'] = recuperaciones_calcular_plazo_aprobacion($fechaR);
                                    }
                                }
                            }

                            // Enlace directo al módulo de Asistencia cuando la ausencia choca con marcas del día.
                            $urlMarcasPermiso = url(strtolower(auth_rol_activo()) . '/asistencia.php?' . http_build_query([
                                'desde' => (string) $permiso['fecha_ausencia'],
                                'hasta' => (string) $permiso['fecha_ausencia'],
                                'q'     => (string) ($permiso['practicante_email'] ?? ''),
                            ]));

                            $idModalSolicitud = 'modalSolicitud_' . $permiso['id_permiso'];
                            $idModalAprobar   = 'modalAprobar_'   . $permiso['id_permiso'];
                            $idModalRechazar  = 'modalRechazar_'  . $permiso['id_permiso'];
                            ?>
                            <tr class="<?= $esPendiente ? 'rrhh-fila-pendiente' : '' ?>">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="rrhh-practicante-avatar shrink-0" aria-hidden="true"><?= e($inicialesPracticante) ?></span>
                                        <div class="min-w-0">
                                            <strong class="block leading-snug text-slate-900"><?= e($nombrePracticante) ?></strong>
                                            <div class="text-slate-500 text-xs mt-0.5"><?= e((string) $permiso['practicante_email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <strong class="block text-slate-900"><?= e($fechaAusenciaTexto) ?></strong>
                                    <span class="text-slate-500 text-xs">
                                        <?= e($diaAusenciaTexto) ?> · <?= $esParcial ? 'Parcial ' . e($tramoAusenciaTexto) : 'Jornada completa' ?>
                                    </span>
                                </td>
                                <td>
                                    <p class="rrhh-permiso-motivo" title="<?= e((string) $permiso['motivo']) ?>"><?= e((string) $permiso['motivo']) ?></p>
                                </td>
                                <td class="whitespace-nowrap" data-recup-celda="<?= (int)$permiso['id_permiso'] ?>">
                                    <?php if (!$recupInfo['tiene']): ?>
                                        <span class="text-xs text-slate-400 font-medium italic">— Sin recuperación —</span>
                                    <?php elseif ($recupInfo['es_rechazado']): ?>
                                        <span class="block line-through text-slate-400 font-medium leading-snug"><?= e($recupInfo['titulo']) ?></span>
                                        <span class="text-red-600/80 text-xs block"><?= e($recupInfo['detalle']) ?></span>
                                    <?php elseif ($recupInfo['esta_validado']): ?>
                                        <strong class="block text-slate-900 leading-snug"><?= e($recupInfo['titulo']) ?></strong>
                                        <span class="text-emerald-700 text-xs font-semibold inline-flex items-center gap-1">
                                            <svg class="rrhh-icono rrhh-icono-check-recup text-emerald-600" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:14px;height:14px;min-width:14px;max-width:14px;display:inline-block;vertical-align:-2px;">
                                                <path d="M20 6 9 17l-5-5" />
                                            </svg><?= e($recupInfo['detalle']) ?>
                                        </span>
                                    <?php else: ?>
                                        <strong class="block text-slate-900 leading-snug"><?= e($recupInfo['titulo']) ?></strong>
                                        <span class="text-slate-500 text-xs block">
                                            <?= e($recupInfo['detalle']) ?>
                                        </span>
                                        <?php if (!empty($recupInfo['plazo_aprobacion'])): ?>
                                            <?php $pa = $recupInfo['plazo_aprobacion']; ?>
                                            <?php if ($pa['es_pasado']): ?>
                                                <?php if ($pa['dentro_margen']): ?>
                                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-300" title="Aprobación tardía permitida hasta el <?= e(date('d-m-Y', strtotime($pa['fecha_limite']))) ?>">
                                                        <i class="bi bi-clock-history text-amber-600"></i>Aprobación tardía (<?= (int)$pa['habiles_restantes'] ?> d. hábiles)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200" title="Plazo de aprobación tardía expirado">
                                                        <i class="bi bi-x-circle text-red-500"></i>Plazo vencido
                                                    </span>
                                                <?php endif; ?>
                                            <?php elseif ($pa['es_proximo']): ?>
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-xs" title="Recuerda aprobar antes del sábado para habilitar el marcaje del practicante">
                                                    <i class="bi bi-lightning-charge-fill text-amber-600"></i>Por aprobar este sábado
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="inline-flex flex-col items-start gap-1">
                                        <span class="rrhh-badge <?= e(rrhh_tone($badgeEstado)) ?> whitespace-nowrap">
                                            <?= icono($estadoIcono) ?><?= e($estadoTexto) ?>
                                        </span>
                                        <?php if ($enObservacion): ?>
                                            <a href="<?= e($urlMarcasPermiso) ?>" class="rrhh-btn-ver-marcas whitespace-nowrap" title="La ausencia cruza tiempo trabajado. Clic para ver las marcas de ese día en Asistencia.">
                                                <?= icono('clock') ?><span>Ver marcas</span><?= icono('arrow-right') ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="rrhh-button rrhh-practicantes-open-btn !px-3.5 !py-1.5 !text-xs font-semibold whitespace-nowrap" data-ui-open="<?= e($idModalSolicitud) ?>">
                                        <?= icono('file-search') ?><span><?= $esPendiente ? 'Revisar solicitud' : 'Ver detalle' ?></span>
                                    </button>

                                    <!-- VENTANA "REVISAR SOLICITUD": misma cabecera, superficie y animación que la ficha de Practicantes. -->
                                    <?php $esModalAutoOpen = ((string) input('modal_abierto', '') === $idModalSolicitud); ?>
                                    <dialog data-ui-dialog<?= $esModalAutoOpen ? ' data-ui-auto-open' : '' ?> class="rrhh-solicitud-dialog text-left" id="<?= e($idModalSolicitud) ?>" aria-labelledby="<?= e($idModalSolicitud) ?>Title">
                                        <div class="rrhh-practicante-modal-header">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="rrhh-practicante-avatar rrhh-practicante-avatar--lg shrink-0" aria-hidden="true"><?= e($inicialesPracticante) ?></span>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h2 id="<?= e($idModalSolicitud) ?>Title" class="text-lg font-bold mb-0"><?= e($nombrePracticante) ?></h2>
                                                        <span class="rrhh-badge <?= e(rrhh_tone($badgeEstado)) ?>"><?= icono($estadoIcono) ?><?= e($estadoTexto) ?></span>
                                                    </div>
                                                    <div class="text-slate-500 text-xs sm:text-sm truncate mt-0.5">
                                                        <?= e((string) $permiso['practicante_email']) ?> · <?= e((string) ($permiso['tipo_practica_nombre'] ?? 'Práctica')) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="rrhh-practicante-modal-close shrink-0" data-ui-close aria-label="Cerrar solicitud">
                                                <?= icono('x') ?><span>Cerrar</span>
                                            </button>
                                        </div>

                                        <div class="rrhh-solicitud-body">
                                            <dl class="rrhh-solicitud-resumen">
                                                <div>
                                                    <dt>Día de ausencia</dt>
                                                    <dd><?= e($diaAusenciaTexto) ?> <?= e($fechaAusenciaTexto) ?></dd>
                                                </div>
                                                <div>
                                                    <dt>Tipo</dt>
                                                    <dd>
                                                        <?php if ($esParcial): ?>
                                                            Parcial · ausente <?= e($tramoAusenciaTexto) ?> (<?= e(formatear_horas_enteras($permiso['horas_ausencia'])) ?> hrs)
                                                        <?php else: ?>
                                                            Jornada completa
                                                        <?php endif; ?>
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt>Solicitado</dt>
                                                    <dd><?= e(date('d-m-Y H:i', strtotime((string) $permiso['created_at']))) ?></dd>
                                                </div>
                                                <?php if ($esParcial): ?>
                                                    <div class="rrhh-solicitud-resumen-ancho">
                                                        <dt>Trabajará ese día</dt>
                                                        <!-- Mostré ambos tramos del parcial, incluyendo el regreso y los minutos. -->
                                                        <dd><?= e(permisos_gestion_texto_tramos($permiso)) ?> (<?= e((string) $permiso['horas_trabajo_dia']) ?> hrs)</dd>
                                                    </div>
                                                <?php endif; ?>
                                            </dl>

                                            <section class="rrhh-solicitud-seccion">
                                                <h3>Motivo</h3>
                                                <p><?= e((string) $permiso['motivo']) ?></p>
                                            </section>

                                            <?php if (!empty($permiso['observacion_solicitud'])): ?>
                                                <?php
                                                // El enlace abre Asistencia filtrada por el practicante y el día de la ausencia, no la lista general.
                                                $urlMarcasPermiso = url(strtolower(auth_rol_activo()) . '/asistencia.php?' . http_build_query([
                                                    'desde' => (string) $permiso['fecha_ausencia'],
                                                    'hasta' => (string) $permiso['fecha_ausencia'],
                                                    'q' => (string) ($permiso['practicante_email'] ?? ''),
                                                ]));
                                                ?>
                                                <div class="rrhh-nota-observacion">
                                                    <?= icono('circle-alert') ?>
                                                    <div class="min-w-0">
                                                        <span><?= e((string) $permiso['observacion_solicitud']) ?></span>
                                                        <a class="rrhh-nota-enlace" href="<?= e($urlMarcasPermiso) ?>">
                                                            <span>Ver marcas del <?= e($fechaAusenciaTexto) ?></span>
                                                            <?= icono('arrow-right') ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <section class="rrhh-solicitud-seccion">
                                                <h3>Plan de recuperación</h3>
                                                <div class="rrhh-solicitud-caja">
                                                    <?php // Mostré el plan completo y sus validaciones independientes.
                                                    $permisoPlan = $permiso;
                                                    $accionesRecuperacion = true;
                                                    require VIEWS_PATH . '/components/recuperaciones-plan.php'; ?>
                                                </div>
                                            </section>

                                            <?php if (!empty($permiso['respuesta_supervisor'])): ?>
                                                <section class="rrhh-solicitud-seccion">
                                                    <h3>Respuesta del supervisor</h3>
                                                    <p class="<?= $permiso['estado'] === 'APROBADO' ? 'text-emerald-700' : 'text-red-700' ?>"><?= e((string) $permiso['respuesta_supervisor']) ?></p>
                                                    <p class="text-slate-500 text-xs mt-1">Por: <?= e((string) ($permiso['revisor_email'] ?? 'Supervisor')) ?></p>
                                                </section>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($esPendiente): ?>
                                            <div class="rrhh-solicitud-footer">
                                                <button class="rrhh-button rrhh-btn-danger-soft rrhh-btn-sm" type="button" data-ui-open="<?= e($idModalRechazar) ?>">
                                                    <?= icono('x') ?><span>Rechazar</span>
                                                </button>
                                                <button class="rrhh-button rrhh-btn-success rrhh-btn-sm" type="button" data-ui-open="<?= e($idModalAprobar) ?>">
                                                    <?= icono('check') ?><span>Aprobar</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </dialog>

                                    <?php if ($esPendiente): ?>
                                        <!-- MODAL APROBACIÓN -->
                                        <dialog data-ui-dialog class="rrhh-dialog text-left" id="<?= e($idModalAprobar) ?>" tabindex="-1" aria-labelledby="<?= e($idModalAprobar) ?>Title">
                                            <div class="w-full">
                                                <div class="bg-white text-slate-800">
                                                    <form method="post" action="<?= e($urlRetornoListado) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                        <input type="hidden" name="accion" value="aprobar_permiso">
                                                        <input type="hidden" name="id_permiso" value="<?= (int) $permiso['id_permiso'] ?>">

                                                        <div class="flex items-center justify-between gap-3 px-5 py-3 bg-emerald-700 text-white">
                                                            <h5 id="<?= e($idModalAprobar) ?>Title" class="text-base font-semibold text-base">Aprobar Permiso – <?= e($nombrePracticante) ?></h5>
                                                            <button type="button" class="rrhh-close text-white" data-ui-close aria-label="Cerrar diálogo"></button>
                                                        </div>
                                                        <div class="p-5">
                                                            <p class="text-sm text-slate-500 mb-2">
                                                                <?php if ($esParcial): ?>
                                                                    Al aprobar este permiso <strong>parcial</strong>, el día <strong><?= e(date('d-m-Y', strtotime((string) $permiso['fecha_ausencia']))) ?></strong>
                                                                    <!-- Incluí ambos tramos en la confirmación de aprobación. -->
                                                                    el practicante deberá trabajar <strong><?= e(permisos_gestion_texto_tramos($permiso)) ?></strong>
                                                                    (<?= e(formatear_horas_enteras($permiso['horas_trabajo_dia'])) ?> hrs esperadas).
                                                                    El sistema actualizará automáticamente el horario de ese día.
                                                                <?php else: ?>
                                                                    Al aprobar, se justificará la ausencia completa del <strong><?= e(date('d-m-Y', strtotime((string) $permiso['fecha_ausencia']))) ?></strong>.
                                                                <?php endif; ?>
                                                                <!-- Distinguí autorización del plan de reconocimiento del tiempo trabajado. -->
                                                                Se autorizará el plan mostrado en la solicitud. Las horas se reconocerán después de validar cada tramo con sus marcas.
                                                            </p>
                                                            <?php if (!empty($recupInfo['plazo_aprobacion'])): ?>
                                                                <?php $pa = $recupInfo['plazo_aprobacion']; ?>
                                                                <?php if ($pa['es_pasado'] && $pa['dentro_margen']): ?>
                                                                    <div class="p-3 mb-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                                                                        <div class="flex items-center gap-1.5 font-bold mb-1 text-amber-950">
                                                                            <i class="bi bi-clock-history text-amber-700"></i>
                                                                            <span>Aprobación tardía habilitada (quedan <?= (int) $pa['habiles_restantes'] ?> días hábiles)</span>
                                                                        </div>
                                                                        <span>El sábado propuesto (<?= e(date('d-m-Y', strtotime($pa['fecha_sabado']))) ?>) ya transcurrió. Al confirmar, autorizas el tramo y podrás regularizar las marcas o validar sus horas en Asistencia. Plazo máximo: <?= e(date('d-m-Y', strtotime($pa['fecha_limite']))) ?>.</span>
                                                                    </div>
                                                                <?php elseif ($pa['es_pasado'] && !$pa['dentro_margen']): ?>
                                                                    <div class="p-3 mb-3 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs">
                                                                        <div class="flex items-center gap-1.5 font-bold mb-1 text-red-950">
                                                                            <i class="bi bi-exclamation-triangle-fill text-red-600"></i>
                                                                            <span>Plazo de 5 días hábiles expirado</span>
                                                                        </div>
                                                                        <span>Han transcurrido más de 5 días hábiles desde el sábado <?= e(date('d-m-Y', strtotime($pa['fecha_sabado']))) ?> (vencido el <?= e(date('d-m-Y', strtotime($pa['fecha_limite']))) ?>).</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <?php if (in_array('FERIADO', array_column($permiso['recuperaciones'] ?? [], 'tipo'), true)): ?>
                                                                <label class="block text-sm mb-3"><input class="rrhh-checkbox" type="checkbox" name="autorizar_feriados" value="1" required> Autorizo expresamente recuperación en los feriados del plan y dejo su motivo.</label>
                                                            <?php endif; ?>
                                                            <div class="mb-4">
                                                                <label class="rrhh-label text-sm font-semibold" for="coment_<?= (int) $permiso['id_permiso'] ?>">Observación o mensaje al practicante (opcional)</label>
                                                                <input class="rrhh-input" id="coment_<?= (int) $permiso['id_permiso'] ?>" name="comentario_aprobacion" placeholder="Ej: Aprobado. Recuerda marcar tu asistencia el sábado." maxlength="255">
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-3">
                                                            <button type="button" class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" data-ui-close aria-label="Cerrar diálogo">Cancelar</button>
                                                            <button type="submit" class="rrhh-button rrhh-btn-success rrhh-btn-sm">Confirmar Aprobación</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </dialog>

                                        <!-- MODAL RECHAZO -->
                                        <dialog data-ui-dialog class="rrhh-dialog text-left" id="<?= e($idModalRechazar) ?>" tabindex="-1" aria-labelledby="<?= e($idModalRechazar) ?>Title">
                                            <div class="w-full">
                                                <div class="bg-white text-slate-800">
                                                    <form method="post" action="<?= e($urlRetornoListado) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                        <input type="hidden" name="accion" value="rechazar_permiso">
                                                        <input type="hidden" name="id_permiso" value="<?= (int) $permiso['id_permiso'] ?>">

                                                        <div class="flex items-center justify-between gap-3 px-5 py-3 bg-red-700 text-white">
                                                            <h5 id="<?= e($idModalRechazar) ?>Title" class="text-base font-semibold text-base">Rechazar Permiso – <?= e($nombrePracticante) ?></h5>
                                                            <button type="button" class="rrhh-close text-white" data-ui-close aria-label="Cerrar diálogo"></button>
                                                        </div>
                                                        <div class="p-5">
                                                            <p class="text-sm text-slate-500 mb-2">
                                                                Indica el motivo por el cual no es posible autorizar este permiso para notificar al practicante.
                                                            </p>
                                                            <div class="mb-4">
                                                                <label class="rrhh-label text-sm font-semibold" for="motivo_<?= (int) $permiso['id_permiso'] ?>">Motivo de rechazo <span class="text-red-700">*</span></label>
                                                                <textarea class="rrhh-input" id="motivo_<?= (int) $permiso['id_permiso'] ?>" name="motivo_rechazo" rows="3" placeholder="Ej: La fecha solicitada coincide con reunión de entrega obligatoria..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-3">
                                                            <button type="button" class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" data-ui-close aria-label="Cerrar diálogo">Cancelar</button>
                                                            <button type="submit" class="rrhh-button rrhh-btn-danger rrhh-btn-sm">Confirmar Rechazo</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </dialog>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <?php
                // Conservé en la paginación tanto el límite elegido como todos los filtros activos y el ancla de la tabla.
                $queryExtraPaginacion = '&limite=' . (int) ($limiteSeleccionado ?? 5)
                    . ($filtros['estado'] !== '' ? '&estado=' . urlencode($filtros['estado']) : '')
                    . ($filtros['desde'] !== '' ? '&desde=' . urlencode($filtros['desde']) : '')
                    . ($filtros['hasta'] !== '' ? '&hasta=' . urlencode($filtros['hasta']) : '')
                    . ($filtros['q'] !== '' ? '&q=' . urlencode($filtros['q']) : '');
                ?>
                <div class="flex justify-between items-center p-4 border-t">
                    <div class="text-sm text-slate-500">
                        Página <strong><?= $paginaActual ?></strong> de <strong><?= $totalPaginas ?></strong>
                    </div>
                    <div class="flex gap-1">
                        <?php if ($paginaActual > 1): ?>
                            <a data-permisos-page-link class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" href="<?= e(url($rutaActual . '?pagina=' . ($paginaActual - 1) . $queryExtraPaginacion)) ?>#listado-permisos">&laquo; Anterior</a>
                        <?php endif; ?>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a data-permisos-page-link class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" href="<?= e(url($rutaActual . '?pagina=' . ($paginaActual + 1) . $queryExtraPaginacion)) ?>#listado-permisos">Siguiente &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<script>
    // Conservé la posición vertical exacta al cambiar de página o de cantidad de registros en la tabla.
    (() => {
        const claveScroll = 'rrhh-permisos-scroll-y';
        try {
            const guardado = window.sessionStorage.getItem(claveScroll);
            if (guardado !== null) {
                window.sessionStorage.removeItem(claveScroll);
                const y = parseInt(guardado, 10);
                if (!Number.isNaN(y)) {
                    window.requestAnimationFrame(() => window.scrollTo({
                        top: y,
                        behavior: 'instant'
                    }));
                }
            }
            const guardarPosicion = () => {
                try {
                    window.sessionStorage.setItem(claveScroll, String(window.scrollY));
                } catch (e) {}
            };
            document.querySelectorAll('[data-permisos-page-link]').forEach(enlace => {
                enlace.addEventListener('click', guardarPosicion);
            });
            const selectorLimite = document.getElementById('limitePermisos');
            if (selectorLimite) {
                selectorLimite.addEventListener('change', guardarPosicion);
            }
        } catch (error) {
            // Si el navegador bloquea sessionStorage, el ancla #listado-permisos mantiene el foco en la tabla.
        }

        // Auto-abrir diálogo si viene marcado tras submit tradicional de respaldo
        const dialogAutoOpen = document.querySelector('dialog[data-ui-dialog][data-ui-auto-open]');
        if (dialogAutoOpen && !dialogAutoOpen.open && typeof dialogAutoOpen.showModal === 'function') {
            dialogAutoOpen.showModal();
            document.body.classList.add('rrhh-dialog-open');
        }

        // Toast de confirmación accesible con animación de resorte (Apple HUD style)
        const mostrarToastExito = (mensaje) => {
            let toast = document.getElementById('rrhh-toast-alerta');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'rrhh-toast-alerta';
                document.body.appendChild(toast);
            }
            toast.className = 'rrhh-toast-spring fixed top-5 right-5 z-[9999] flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-emerald-600/95 text-white font-medium text-xs sm:text-sm shadow-xl shadow-emerald-950/25 backdrop-blur-md border border-white/20 pointer-events-auto';
            toast.innerHTML = `<svg class="rrhh-icono rrhh-icono-check-recup text-white shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:16px;height:16px;min-width:16px;display:inline-block;vertical-align:-2px;"><path d="M20 6 9 17l-5-5" /></svg><span>${mensaje}</span>`;
            toast.style.display = 'flex';
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0) scale(1)';
            setTimeout(() => {
                toast.style.transition = 'opacity 350ms cubic-bezier(0.4, 0, 1, 1), transform 350ms cubic-bezier(0.4, 0, 1, 1)';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-12px) scale(0.96)';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 360);
            }, 3800);
        };

        // Procesar validación de asistencia vía Fetch (AJAX) con micro-interacción orgánica
        document.addEventListener('submit', async (e) => {
            const form = e.target.closest('form[data-ajax-validar-recuperacion]');
            if (!form) return;

            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;

            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('rrhh-btn-enviando');
            btn.innerHTML = `<svg class="rrhh-spinner-validar text-white shrink-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px!important;height:14px!important;min-width:14px!important;max-width:14px!important;display:inline-block!important;vertical-align:middle!important;"><circle cx="12" cy="12" r="9" stroke-opacity="0.25"></circle><path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"></path></svg><span>Verificando marcas de asistencia…</span>`;

            try {
                const formData = new FormData(form);
                // Delay orgánico de 450ms para percibir la inspección de marcas biométricas sin cortes de PPT
                const [resp] = await Promise.all([
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }),
                    new Promise(resolve => setTimeout(resolve, 450))
                ]);

                const data = await resp.json();

                if (resp.ok && data.ok) {
                    // 1. Reemplazar botón por badge de éxito animado con resorte y trazo de check
                    const badgeExito = document.createElement('div');
                    if (data.estado_tramo === 'VALIDADA') {
                        badgeExito.className = 'rrhh-badge-validado-animado mt-2.5 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/90 text-xs font-semibold shadow-sm';
                        badgeExito.innerHTML = `<svg class="rrhh-icono rrhh-icono-check-recup rrhh-svg-check-anim text-emerald-600 shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:16px;height:16px;min-width:16px;display:inline-block;vertical-align:-2px;"><path d="M20 6 9 17l-5-5" /></svg><span>Asistencia validada correctamente · ${data.minutos_validados} min</span>`;
                    } else {
                        badgeExito.className = 'rrhh-badge-validado-animado mt-2.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold';
                        badgeExito.innerHTML = `<span>Tramo requiere reprogramación</span>`;
                    }
                    form.replaceWith(badgeExito);

                    // 2. Actualizar estado del tramo con fade suave
                    const tramoEl = document.querySelector(`[data-tramo-id="${data.id_recuperacion}"]`);
                    if (tramoEl) {
                        const estadoEl = tramoEl.querySelector('[data-tramo-estado]');
                        if (estadoEl) {
                            estadoEl.style.transition = 'color 300ms ease, opacity 200ms ease';
                            estadoEl.className = 'text-emerald-700 font-semibold';
                            estadoEl.textContent = `${data.estado_tramo} · ${data.minutos_validados} min validados`;
                        }
                    }

                    // 3. Actualizar resumen de saldo del plan con resplandor verde (pulse)
                    const modalEl = badgeExito.closest('dialog');
                    if (modalEl) {
                        const saldoEl = modalEl.querySelector('[data-saldo-plan]');
                        if (saldoEl) {
                            saldoEl.classList.remove('rrhh-saldo-destacado');
                            void saldoEl.offsetWidth;
                            saldoEl.classList.add('rrhh-saldo-destacado');
                            saldoEl.textContent = `Recuperado: ${data.total_validados} min · Pendiente: ${data.minutos_pendientes} min`;
                        }
                    }

                    // 4. Actualizar celda en la tabla principal con animación de resplandor
                    const celdaTabla = document.querySelector(`[data-recup-celda="${data.id_permiso}"]`);
                    if (celdaTabla) {
                        celdaTabla.classList.remove('rrhh-celda-actualizada');
                        void celdaTabla.offsetWidth;
                        celdaTabla.classList.add('rrhh-celda-actualizada');
                        if (data.estado_tramo === 'VALIDADA') {
                            const esMulti = (data.total_tramos && data.total_tramos > 1);
                            const tituloCelda = esMulti ?
                                `${data.total_tramos} fechas (desde ${data.primera_fecha_corto})` :
                                data.fecha_tramo;
                            const detalleCelda = esMulti ?
                                (data.minutos_pendientes === 0 ?
                                    `${data.horas_validadas_fmt} hrs recuperadas` :
                                    `${data.horas_validadas_fmt} de ${data.horas_totales_fmt} hrs recuperadas`) :
                                `Sábado · ${data.horas_validadas_fmt} hrs recuperadas`;
                            celdaTabla.innerHTML = `
                                <strong class="block text-slate-900 leading-snug">${tituloCelda}</strong>
                                <span class="text-emerald-700 text-xs font-semibold inline-flex items-center gap-1">
                                    <svg class="rrhh-icono rrhh-icono-check-recup text-emerald-600" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:14px;height:14px;min-width:14px;max-width:14px;display:inline-block;vertical-align:-2px;"><path d="M20 6 9 17l-5-5" /></svg>
                                    <span>${detalleCelda}</span>
                                </span>
                            `;
                        } else if (data.estado_tramo === 'REPROGRAMAR') {
                            celdaTabla.innerHTML = `
                                <span class="block line-through text-slate-400 font-medium leading-snug">${data.fecha_tramo}</span>
                                <span class="text-amber-600 text-xs font-semibold block">Requiere reprogramación</span>
                            `;
                        }
                    }

                    // 5. Toast flotante dinámico con resorte
                    mostrarToastExito(data.mensaje || 'Recuperación validada según marcas registradas.');
                } else {
                    const mensajeError = (data.errores && data.errores.join(', ')) || 'No se pudo validar la asistencia.';
                    alert(mensajeError);
                    btn.classList.remove('rrhh-btn-enviando');
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            } catch (err) {
                console.error('Error al validar recuperación:', err);
                alert('Ocurrió un error al procesar la validación. Intenta nuevamente.');
                btn.classList.remove('rrhh-btn-enviando');
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        });
    })();
</script>