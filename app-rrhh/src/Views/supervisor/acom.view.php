<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.

require_once VIEWS_PATH . '/layout/head.php';
require_once VIEWS_PATH . '/layout/sidebar.php';
?>
        <!-- Unifiqué la cabecera de contexto activo en tarjeta moderna con insignia de rol, selector de vista y buscador con limpieza. -->
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-6 rrhh-theme-panel-border">
            <div class="flex flex-wrap items-center gap-4">
                <div class="rrhh-non-interactive select-none pointer-events-none">
                    <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-1">Contexto activo</p>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 rrhh-theme-role-pill">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> <?= e(auth_rol_activo()) ?>
                    </span>
                </div>
                <div class="fab-inline" id="fabInline">
                    <button type="button" class="rrhh-button rrhh-practicantes-search-btn fab-main fab-main-inline" id="fabMain" aria-expanded="false" aria-haspopup="true">
                        <i class="bi bi-view-stacked"></i>
                        <span class="fab-main-text"><?= e($etiquetaVistaActiva) ?></span>
                    </button>
                    <!-- Conservé el selector propio de vistas y retiré sus clases Bootstrap residuales. -->
                    <ul class="fab-dropdown" aria-labelledby="fabMain">
                        <li>
                            <a class="rrhh-menu-item block px-3 py-2 text-sm hover:bg-slate-50 fab-opcion <?php echo $vistaActiva === 'acompanamiento' ? 'active' : ''; ?>" href="<?= e(url('supervisor/acom.php?vista=acompanamiento' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">
                                <i class="bi bi-clipboard-data mr-2"></i>General
                            </a>
                        </li>
                        <li>
                                <a class="rrhh-menu-item block px-3 py-2 text-sm hover:bg-slate-50 fab-opcion <?php echo $vistaActiva === 'legales' ? 'active' : ''; ?>" href="<?= e(url('supervisor/acom.php?vista=legales' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">
                                    <i class="bi bi-shield-check mr-2"></i>Legales
                            </a>
                        </li>
                        <li>
                            <a class="rrhh-menu-item block px-3 py-2 text-sm hover:bg-slate-50 fab-opcion <?php echo $vistaActiva === 'capacitaciones' ? 'active' : ''; ?>" href="<?= e(url('supervisor/acom.php?vista=capacitaciones' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">
                                <i class="bi bi-mortarboard mr-2"></i>Capacitaciones
                            </a>
                        </li>
                        <li>
                            <a class="rrhh-menu-item block px-3 py-2 text-sm hover:bg-slate-50 fab-opcion <?php echo $vistaActiva === 'monitoreo' ? 'active' : ''; ?>" href="<?= e(url('supervisor/acom.php?vista=monitoreo' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">
                                <i class="bi bi-graph-up-arrow mr-2"></i>Monitoreo
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <form class="flex items-center gap-2" method="get" action="<?= e(url('supervisor/acom.php')) ?>">
                <input type="hidden" name="vista" value="<?= e($vistaActiva) ?>">
                <input class="rrhh-input" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar practicante...">
                <button class="rrhh-button rrhh-practicantes-search-btn" type="submit">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <?php if ($busqueda !== ''): ?>
                    <a class="rrhh-button rrhh-clean-filter-btn !px-3" href="<?= e(url('supervisor/acom.php?vista=' . urlencode($vistaActiva))) ?>" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                        <i class="bi bi-trash3-fill"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($errores !== []): ?>
            <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert">
                <ul class="mb-0">
                    <?php foreach (array_unique($errores) as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="vistas-container" data-vista-act="<?php echo e($vistaActiva); ?>">
            <section id="vista-acompanamiento" class="vista-seccion <?php echo $vistaActiva === 'acompanamiento' ? 'vista-activa' : 'vista-oculta'; ?>">
                <div class="panel p-4 mb-4">
                    <h2 class="text-base mb-4">Practicantes activos</h2>
                    <form id="formAcompanamiento" method="post" action="<?= e(url('supervisor/acom.php?vista=acompanamiento')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                        <input type="hidden" name="vista" value="acompanamiento">
                        <input type="hidden" name="seccion" value="acompanamiento">
                        <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                        <div class="rrhh-table-scroll overflow-x-auto">
                            <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Practicante</th>
                                        <th>Práctica</th>
                                        <th>Área</th>
                                        <th>Inicio</th>
                                        <th>Término</th>
                                        <th>Avance</th>
                                        <th>Notas</th>
                                        <th class="text-center">Evaluación enviada</th>
                                        <th class="text-center">LinkedIn operativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($practicantes === []): ?>
                                        <tr>
                                            <td colspan="11" class="text-slate-500">No hay practicantes para mostrar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($practicantes as $item): ?>
                                            <?php
                                                $nombre = trim((string) $item['nombre_1'] . ' ' . (string) $item['apellido_paterno']);
                                                $idPractica = (int) $item['id_practica'];
                                                $horasRequeridas = (float) ($item['horas_requeridas'] ?? 0);
                                                $horasTotales = (float) ($item['horas_totales'] ?? 0);
                                                $avance = $horasRequeridas > 0
                                                    ? min(100.0, round(($horasTotales / $horasRequeridas) * 100, 1))
                                                    : 0.0;
                                                $fechaInicio = (string) ($item['fecha_inicio_practica'] ?? '');
                                                $fechaTermino = (string) ($item['fecha_termino_practica'] ?? '');
                                                $puntaje = $item['puntaje_evaluacion'] ?? null;
                                                $evaluacionEnviada = !empty($item['evaluacion_enviada']);
                                                $resenaRecibida = !empty($item['resena_recibida']);
                                                $resenaPublicada = !empty($item['resena_publicada']);
                                                $linkedinOperativo = !empty($item['linkedin_operativo']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($nombre) ?></strong>
                                                    <div class="text-slate-500 text-sm"><?= e((string) $item['email']) ?></div>
                                                </td>
                                                <td>
                                                    <?= e((string) ($item['tipo_practica'] ?? 'Sin tipo')) ?>
                                                    <div class="text-slate-500 text-sm"><?= e((string) ($item['carrera'] ?? 'Sin carrera')) ?></div>
                                                </td>
                                                <td><?= e((string) ($item['area_vigente'] ?? 'Sin área')) ?></td>
                                                <td><?= e($fechaInicio !== '' ? date('d/m/Y', strtotime($fechaInicio)) : 'Sin fecha') ?></td>
                                                <td><?= e($fechaTermino !== '' ? date('d/m/Y', strtotime($fechaTermino)) : 'Sin fecha') ?></td>
                                                <td>
                                                    <strong><?= e((string) $avance) ?>%</strong>
                                                    <div class="rrhh-progress mt-1" role="progressbar" aria-valuenow="<?= e((string) $avance) ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="rrhh-progress-bar" style="width: <?= e((string) $avance) ?>%"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php // Reemplacé la edición independiente por la fuente oficial y conservé visible el puntaje histórico. ?>
                                                    <?php $resumenNota = $resumenNotas[$idPractica] ?? null; require VIEWS_PATH . '/components/notas-resumen.php'; ?>
                                                    <?php if ($puntaje !== null): ?>
                                                        <p class="text-xs text-slate-500 mt-2">Puntaje anterior (histórico): <?= e((string)$puntaje) ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="evaluacion_enviada[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="evaluacion_enviada[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $evaluacionEnviada ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Evaluación enviada a <?= e($nombre) ?>"
                                                    >
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="linkedin_operativo[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="linkedin_operativo[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $linkedinOperativo ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="LinkedIn operativo de <?= e($nombre) ?>"
                                                    >
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-right hidden" aria-live="polite"></div>
                    </form>
                </div>
            </section>

            <section id="vista-legales" class="vista-seccion <?php echo $vistaActiva === 'legales' ? 'vista-activa' : 'vista-oculta'; ?>">
                <div class="panel p-4 mb-4">
                    <h2 class="text-base mb-4">Legales</h2>
                    <form id="formLegales" method="post" action="<?= e(url('supervisor/acom.php?vista=legales')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                        <input type="hidden" name="vista" value="legales">
                        <input type="hidden" name="seccion" value="legales">
                        <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                        <div class="rrhh-table-scroll overflow-x-auto">
                            <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Practicante</th>
                                        <th>Práctica</th>
                                        <th>Área</th>
                                        <th>Avance</th>
                                        <th class="text-center">Reseña recibida</th>
                                        <th class="text-center">Reseña publicada</th>
                                        <th class="text-center">Documento entregado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($practicantes === []): ?>
                                        <tr>
                                            <td colspan="7" class="text-slate-500">No hay practicantes para mostrar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($practicantes as $item): ?>
                                                <?php
                                                    $nombre = trim((string) $item['nombre_1'] . ' ' . (string) $item['apellido_paterno']);
                                                    $idPractica = (int) $item['id_practica'];
                                                    $resenaRecibida = !empty($item['resena_recibida']);
                                                    $resenaPublicada = !empty($item['resena_publicada']);
                                                    $horasRequeridas = (float) ($item['horas_requeridas'] ?? 0);
                                                    $horasTotales = (float) ($item['horas_totales'] ?? 0);
                                                    $avance = $horasRequeridas > 0
                                                        ? min(100.0, round(($horasTotales / $horasRequeridas) * 100, 1))
                                                        : 0.0;
                                                ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($nombre) ?></strong>
                                                    <div class="text-slate-500 text-sm"><?= e((string) $item['email']) ?></div>
                                                </td>
                                                <td><?= e((string) ($item['tipo_practica'] ?? 'Sin tipo')) ?></td>
                                                <td><?= e((string) ($item['area_vigente'] ?? 'Sin área')) ?></td>
                                                <td>
                                                    <strong><?= e((string) $avance) ?>%</strong>
                                                    <div class="rrhh-progress mt-1" role="progressbar" aria-valuenow="<?= e((string) $avance) ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="rrhh-progress-bar" style="width: <?= e((string) $avance) ?>%"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="resena_recibida[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="resena_recibida[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $resenaRecibida ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Reseña recibida de <?= e($nombre) ?>"
                                                    >
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="resena_publicada[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="resena_publicada[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $resenaPublicada ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Reseña publicada de <?= e($nombre) ?>"
                                                    >
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="documento_entregado[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="documento_entregado[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= !empty($item['documento_entregado']) ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Documento entregado de <?= e($nombre) ?>"
                                                    >
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-right hidden" aria-live="polite"></div>
                    </form>
                </div>
            </section>

            <section id="vista-capacitaciones" class="vista-seccion <?php echo $vistaActiva === 'capacitaciones' ? 'vista-activa' : 'vista-oculta'; ?>">
                <div class="panel p-4 mb-4">
                    <h2 class="text-base mb-4">Capacitaciones</h2>
                    <form id="formCapacitaciones" method="post" action="<?= e(url('supervisor/acom.php?vista=capacitaciones')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                        <input type="hidden" name="vista" value="capacitaciones">
                        <input type="hidden" name="seccion" value="capacitaciones">
                        <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                        <div class="rrhh-table-scroll overflow-x-auto">
                            <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Practicante</th>
                                        <th>Práctica</th>
                                        <th>Área</th>
                                        <th class="text-center">Capacitación realizada</th>
                                        <th class="text-center">Certificado entregado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($practicantes === []): ?>
                                        <tr>
                                            <td colspan="5" class="text-slate-500">No hay practicantes para mostrar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($practicantes as $item): ?>
                                            <?php
                                                $nombre = trim((string) $item['nombre_1'] . ' ' . (string) $item['apellido_paterno']);
                                                $idPractica = (int) $item['id_practica'];
                                                $capacitacionRealizada = !empty($item['capacitacion_realizada']);
                                                $certificadoEntregado = !empty($item['certificado_entregado']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($nombre) ?></strong>
                                                    <div class="text-slate-500 text-sm"><?= e((string) $item['email']) ?></div>
                                                </td>
                                                <td><?= e((string) ($item['tipo_practica'] ?? 'Sin tipo')) ?></td>
                                                <td><?= e((string) ($item['area_vigente'] ?? 'Sin área')) ?></td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="capacitacion_realizada[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="capacitacion_realizada[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $capacitacionRealizada ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Capacitación realizada por <?= e($nombre) ?>"
                                                    >
                                                </td>
                                                <td class="text-center align-middle">
                                                    <input
                                                        type="hidden"
                                                        name="certificado_entregado[<?= e((string) $idPractica) ?>]"
                                                        value="0"
                                                    >
                                                    <input
                                                        class="rrhh-checkbox"
                                                        type="checkbox"
                                                        name="certificado_entregado[<?= e((string) $idPractica) ?>]"
                                                        value="1"
                                                        <?= $certificadoEntregado ? 'checked' : '' ?>
                                                        data-autosave="1"
                                                        aria-label="Certificado entregado a <?= e($nombre) ?>"
                                                    >
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-right hidden" aria-live="polite"></div>
                    </form>
                </div>
            </section>


            <section id="vista-monitoreo" class="vista-seccion <?php echo $vistaActiva === 'monitoreo' ? 'vista-activa' : 'vista-oculta'; ?>">
                <div class="panel p-4 mb-4">
                    <h2 class="text-base mb-4">Monitoreo</h2>
                    <?php $ver = $_GET['ver'] ?? 'tareas'; ?>
                    <ul class="rrhh-tabs mb-4" role="tablist">
                        <li class=""><a class="rrhh-tab <?= $ver === 'tareas' ? 'active' : '' ?>" href="<?= e(url('supervisor/acom.php?vista=monitoreo&ver=tareas' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">Tareas</a></li>
                        <li class=""><a class="rrhh-tab <?= $ver === 'responsables' ? 'active' : '' ?>" href="<?= e(url('supervisor/acom.php?vista=monitoreo&ver=responsables' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''))) ?>">Responsables</a></li>
                    </ul>
                    <?php if ($ver === 'tareas'): ?>
                        <h5>Tareas del proyecto</h5>
                        <p class="text-slate-500">*(Vista en prueba, datos de muestra)*</p>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4 flex flex-col">
                                        <h6 class="mb-2 text-base font-semibold">Inducción seguridad</h6>
                                        <p class="text-sm text-slate-600 text-sm text-slate-500">Capacitación obligatoria</p>
                                        <div class="mt-auto">
                                            <div class="flex justify-between items-center mb-2">
                                                <small class="text-slate-500">Responsable: María López</small>
                                                <span class="rrhh-badge bg-emerald-600">Completada</span>
                                            </div>
                                            <div class="rrhh-progress mb-2" style="height: 6px;">
                                                <div class="rrhh-progress-bar bg-emerald-600" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <div class="text-right">
                                                    <div class="text-sm text-slate-500">Fin estimado</div>
                                                    <div class="font-bold text-sm">2026-08-15</div>
                                                </div>
                                                <span class="font-bold text-sm text-emerald-700">100%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4 flex flex-col">
                                        <h6 class="mb-2 text-base font-semibold">Actualizar rutina de jornadas</h6>
                                        <p class="text-sm text-slate-600 text-sm text-slate-500">Registrar horas diarias</p>
                                        <div class="mt-auto">
                                            <div class="flex justify-between items-center mb-2">
                                                <small class="text-slate-500">Responsable: Carlos Ruiz</small>
                                                <span class="rrhh-badge bg-blue-600">En curso</span>
                                            </div>
                                            <div class="rrhh-progress mb-2" style="height: 6px;">
                                                <div class="rrhh-progress-bar bg-blue-600" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <div class="text-right">
                                                    <div class="text-sm text-slate-500">Fin estimado</div>
                                                    <div class="font-bold text-sm">2026-09-01</div>
                                                </div>
                                                <span class="font-bold text-sm text-blue-700">65%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4 flex flex-col">
                                        <h6 class="mb-2 text-base font-semibold">Informe mensual</h6>
                                        <p class="text-sm text-slate-600 text-sm text-slate-500">Redactar reporte de avances</p>
                                        <div class="mt-auto">
                                            <div class="flex justify-between items-center mb-2">
                                                <small class="text-slate-500">Responsable: Dra. Suárez</small>
                                                <span class="rrhh-badge bg-amber-400 text-slate-900">Pendiente</span>
                                            </div>
                                            <div class="rrhh-progress mb-2" style="height: 6px;">
                                                <div class="rrhh-progress-bar bg-amber-400" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <div class="text-right">
                                                    <div class="text-sm text-slate-500">Fin estimado</div>
                                                    <div class="font-bold text-sm">2026-09-15</div>
                                                </div>
                                                <span class="font-bold text-sm text-amber-700">10%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($ver === 'responsables'): ?>
                        <h5>Equipo y sus tareas</h5>
                        <p class="text-slate-500">*(Vista en prueba, enfoque en quién trabaja en qué)*</p>
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4">
                                        <div class="flex items-center mb-4">
                                            <div class="avatar-circle bg-blue-600 text-white mr-4 flex items-center justify-center" style="width:48px;height:48px;">
                                                <span class="font-bold">ML</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">María López</h6>
                                                <small class="text-slate-500">2 tareas asignadas</small>
                                            </div>
                                        </div>
                                        <div class="task-list text-sm">
                                            <div class="flex justify-between items-center mb-2 p-2 bg-slate-50 rounded">
                                                <div>
                                                    <div class="font-bold text-sm">Inducción seguridad</div>
                                                    <div class="text-slate-500 text-sm">Capacitación obligatoria</div>
                                                    <div class="rrhh-progress mt-1" style="height: 4px;">
                                                        <div class="rrhh-progress-bar bg-emerald-600" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="rrhh-badge bg-emerald-600">Completada</span>
                                                    <div class="font-bold text-sm text-emerald-700 mt-1">100%</div>
                                                </div>
                                            </div>
                                            <div class="flex justify-between items-center mb-2 p-2 bg-slate-50 rounded">
                                                <div>
                                                    <div class="font-bold text-sm">Informe mensual</div>
                                                    <div class="text-slate-500 text-sm">Redactar reporte</div>
                                                    <div class="rrhh-progress mt-1" style="height: 4px;">
                                                        <div class="rrhh-progress-bar bg-blue-600" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="rrhh-badge bg-blue-600">En curso</span>
                                                    <div class="font-bold text-sm text-blue-700 mt-1">60%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4">
                                        <div class="flex items-center mb-4">
                                            <div class="avatar-circle bg-blue-600 text-white mr-4 flex items-center justify-center" style="width:48px;height:48px;">
                                                <span class="font-bold">CR</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Carlos Ruiz</h6>
                                                <small class="text-slate-500">1 tarea en curso</small>
                                            </div>
                                        </div>
                                        <div class="task-list text-sm">
                                            <div class="flex justify-between items-center mb-2 p-2 bg-slate-50 rounded">
                                                <div>
                                                    <div class="font-bold text-sm">Actualizar rutina</div>
                                                    <div class="text-slate-500 text-sm">Registrar horas diarias</div>
                                                    <div class="rrhh-progress mt-1" style="height: 4px;">
                                                        <div class="rrhh-progress-bar bg-blue-600" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="rrhh-badge bg-blue-600">En curso</span>
                                                    <div class="font-bold text-sm text-blue-700 mt-1">65%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12 md:col-span-6 lg:col-span-4">
                                <div class="rrhh-card h-full shadow-sm">
                                    <div class="p-4">
                                        <div class="flex items-center mb-4">
                                            <div class="avatar-circle bg-blue-600 text-white mr-4 flex items-center justify-center" style="width:48px;height:48px;">
                                                <span class="font-bold">DS</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Dra. Suárez</h6>
                                                <small class="text-slate-500">1 tarea pendiente</small>
                                            </div>
                                        </div>
                                        <div class="task-list text-sm">
                                            <div class="flex justify-between items-center mb-2 p-2 bg-slate-50 rounded">
                                                <div>
                                                    <div class="font-bold text-sm">Informe mensual</div>
                                                    <div class="text-slate-500 text-sm">Redactar reporte de avances</div>
                                                    <div class="rrhh-progress mt-1" style="height: 4px;">
                                                        <div class="rrhh-progress-bar bg-amber-400" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="rrhh-badge bg-amber-400 text-slate-900">Pendiente</span>
                                                    <div class="font-bold text-sm text-amber-700 mt-1">10%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>


        </div>

        <script>
            (function() {
                var fabMain = document.getElementById('fabMain');
                var dropdown = fabMain.nextElementSibling;
                var isDropdownOpen = false;

                function closeDropdown() {
                    dropdown.classList.remove('show');
                    fabMain.setAttribute('aria-expanded', 'false');
                    isDropdownOpen = false;
                }

                fabMain.addEventListener('click', function(e) {
                    e.stopPropagation();
                    isDropdownOpen = !isDropdownOpen;
                    dropdown.classList.toggle('show', isDropdownOpen);
                    fabMain.setAttribute('aria-expanded', String(isDropdownOpen));
                });

                document.addEventListener('click', function(e) {
                    if (isDropdownOpen && !fabMain.contains(e.target) && !dropdown.contains(e.target)) {
                        closeDropdown();
                    }
                });
            })();
        </script>

        <script>
            (function() {
                var formA = document.getElementById('formAcompanamiento');
                var formL = document.getElementById('formLegales');
                var formC = document.getElementById('formCapacitaciones');
                var forms = [formA, formL, formC].filter(Boolean);
                if (forms.length === 0) {
                    return;
                }

                function toast(tipo, texto) {
                    var region = document.createElement('div');
                    region.setAttribute('aria-live', 'polite');
                    region.style.position = 'fixed';
                    region.style.bottom = '24px';
                    region.style.right = '24px';
                    region.style.zIndex = '1080';
                    var el = document.createElement('div');
                    el.className = tipo === 'error' ? 'toast toast-error' : 'toast toast-ok';
                    el.style.background = tipo === 'error' ? '#f56565' : '#38a169';
                    el.style.color = '#fff';
                    el.style.padding = '10px 14px';
                    el.style.borderRadius = '6px';
                    el.style.boxShadow = '0 4px 12px rgba(0,0,0,.25)';
                    el.style.marginTop = '8px';
                    el.style.maxWidth = '320px';
                    el.textContent = texto;
                    region.appendChild(el);
                    document.body.appendChild(region);
                    setTimeout(function() {
                        if (region.parentNode) {
                            region.parentNode.removeChild(region);
                        }
                    }, 1600);
                }

                var pendingTimer = null;

                function postData(form) {
                    // Construir body como query string (fiabilidad para checkboxes/inputs)
                    var params = new URLSearchParams(new FormData(form));
                    fetch(form.action || window.location.href.split('#')[0], {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                        },
                        body: params.toString()
                    }).then(function(resp) {
                        if (!resp.ok) {
                            return resp.json().catch(function() {
                                return { ok: false, error: 'Error de red al guardar.' };
                            }).then(function(d) {
                                throw d;
                            });
                        }
                        return resp.json();
                    }).then(function(data) {
                        if (data && data.ok) {
                            // guardado silencioso
                            toast('ok', 'Guardado');
                        } else if (data && data.error) {
                            toast('error', data.error);
                        }
                    }).catch(function(err) {
                        var msg = (err && err.error) ? err.error : 'No se pudo guardar este cambio.';
                        toast('error', msg);
                    });
                }

                var debounceTimer = null;

                function schedule(form) {
                    if (debounceTimer !== null) {
                        clearTimeout(debounceTimer);
                    }
                    debounceTimer = setTimeout(function() {
                        postData(form);
                    }, 500);
                }

                forms.forEach(function(form) {
                    var inputs = form.querySelectorAll('[data-autosave="1"]');
                    if (inputs.length === 0) {
                        return;
                    }

                    inputs.forEach(function(input) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.addEventListener('change', function() {
                                postData(form);
                            });
                        } else {
                            var lastVal = input.value;
                            input.addEventListener('input', function() {
                                if (input.value !== lastVal) {
                                    lastVal = input.value;
                                    schedule(form);
                                }
                            });
                        }
                    });
                });
            })();
        </script>
<?php
require_once VIEWS_PATH . '/layout/foot.php';
?>
