<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
// Trasladé la plantilla desde Services/HorariosPracticanteService.php; recibe los datos preparados por el controlador.
require_once VIEWS_PATH . '/components/horario-fields.php';

// Calculé indicadores rápidos del listado activo para presentar el estado general antes de abrir una ficha.
$totalPracticantesLista = count($practicantes);
$conHorarioLista = 0;
foreach ($practicantes as $itemConteo) {
    if (!empty($itemConteo['id_horario'])) {
        $conHorarioLista++;
    }
}
$sinHorarioLista = $totalPracticantesLista - $conHorarioLista;
$urlListadoBase = url($actionPath . ($busqueda !== '' ? '?q=' . urlencode($busqueda) : ''));
?>

        <div class="gestion-practicantes-content">
                <!-- Unifiqué la cabecera de contexto activo en una tarjeta moderna con insignia de rol y buscador integrado. -->
                <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-6 rrhh-theme-panel-border">
                    <div class="rrhh-non-interactive select-none pointer-events-none">
                        <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-1">Contexto activo</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 rrhh-theme-role-pill">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> <?= e($rolActivo) ?>
                        </span>
                    </div>
                    <!-- Acoté la búsqueda para que el campo ceda espacio sin comprimir el botón. -->
                    <form class="gestion-practicantes-search flex items-center gap-2" method="get" action="<?= e(url($actionPath)) ?>">
                        <input class="rrhh-input" name="q" value="<?= e($busqueda) ?>" placeholder="nombre, correo o carrera...">
                        <button class="rrhh-button rrhh-practicantes-search-btn" type="submit">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <?php if ($busqueda !== ''): ?>
                            <a class="rrhh-button rrhh-clean-filter-btn !px-3" href="<?= e(url($actionPath)) ?>" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if ($mensajeExito !== null && $practicanteSeleccionado === null): ?>
                    <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="alert"><?= e($mensajeExito) ?></div>
                <?php endif; ?>

                <?php if ($errores !== [] && $practicanteSeleccionado === null): ?>
                    <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert">
                        <ul class="mb-0">
                            <?php foreach (array_unique($errores) as $error): ?>
                                <li><?= e((string) $error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-12 gap-4">
                    <!-- Expandí el listado a ancho completo para evitar quiebres de texto y abrir la ficha en un modal dedicado. -->
                    <div class="col-span-12">
                        <section class="panel p-4 sm:p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-200 rrhh-theme-panel-border">
                                <div>
                                    <h2 class="text-base font-bold mb-1">Practicantes con práctica activa</h2>
                                    <p class="text-slate-500 text-xs mb-0">Filtra por estado de horario o pulsa el botón <strong> Abrir ficha </strong> para revisar y gestionar al practicante en ventana emergente.</p>
                                </div>
                                <!-- Unifiqué las pestañas de estado con el diseño semántico e interactivo de Permisos (azul, esmeralda, ámbar). -->
                                <div class="rrhh-permisos-tabs rrhh-practicantes-filter-tabs inline-flex flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5" role="group" aria-label="Filtrar practicantes por estado de horario">
                                    <button type="button" class="rrhh-permisos-tab rrhh-permisos-tab--all rrhh-practicantes-filter-tab is-active inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold" data-filter-horario="todos" aria-pressed="true" aria-current="page">
                                        <i class="bi bi-people rrhh-permisos-tab-icon text-sm" aria-hidden="true"></i>
                                        <span>Todos</span>
                                        <span class="rrhh-permisos-tab-count rrhh-practicantes-filter-count inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold leading-none"><?= e((string) $totalPracticantesLista) ?></span>
                                    </button>
                                    <button type="button" class="rrhh-permisos-tab rrhh-permisos-tab--success rrhh-practicantes-filter-tab rrhh-practicantes-filter-tab--ok inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold" data-filter-horario="con-horario" aria-pressed="false">
                                        <i class="bi bi-check2-circle rrhh-permisos-tab-icon text-sm" aria-hidden="true"></i>
                                        <span>Con horario</span>
                                        <span class="rrhh-permisos-tab-count rrhh-practicantes-filter-count inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold leading-none"><?= e((string) $conHorarioLista) ?></span>
                                    </button>
                                    <button type="button" class="rrhh-permisos-tab rrhh-permisos-tab--warning rrhh-practicantes-filter-tab rrhh-practicantes-filter-tab--warn inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold" data-filter-horario="sin-horario" aria-pressed="false">
                                        <i class="bi bi-clock-history rrhh-permisos-tab-icon text-sm" aria-hidden="true"></i>
                                        <span>Sin horario</span>
                                        <span class="rrhh-permisos-tab-count rrhh-practicantes-filter-count inline-flex items-center justify-center rounded-full px-2 py-0.5 text-[11px] font-bold leading-none"><?= e((string) $sinHorarioLista) ?></span>
                                    </button>
                                </div>
                            </div>
                            <div class="rrhh-table-scroll overflow-x-auto">
                                <!-- Adapté el listado operativo con etiquetas y roles para conservar datos y acciones en paneles estrechos. -->
<table role="table" class="rrhh-managed-list rrhh-responsive-table rrhh-table rrhh-table-hover align-middle mb-0">
                                    <thead role="rowgroup">
                                        <tr role="row">
                                            <th role="columnheader">Practicante</th>
                                            <th role="columnheader">Práctica y carrera</th>
                                            <th role="columnheader">Área asignada</th>
                                            <th role="columnheader">Horario pactado</th>
                                            <th role="columnheader" class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody role="rowgroup">
                                        <?php if ($practicantes === []): ?>
                                            <tr role="row">
                                                <td role="cell" class="rrhh-table-empty text-slate-500" colspan="5">No hay practicantes para mostrar.</td>
                                            </tr>
                                        <?php else: ?>
                                            <tr id="filaSinResultadosFiltroHorario" role="row" hidden>
                                                <td role="cell" class="rrhh-table-empty text-slate-500" colspan="5">No hay practicantes que coincidan con el filtro seleccionado.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($practicantes as $item): ?>
                                            <?php
                                                $nombre = trim((string) $item['nombre_1'] . ' ' . (string) $item['apellido_paterno']);
                                                $iniciales = strtoupper(
                                                    substr(trim((string) ($item['nombre_1'] ?? 'P')), 0, 1)
                                                    . substr(trim((string) ($item['apellido_paterno'] ?? '')), 0, 1)
                                                );
                                                $conflicto = auth_es_conflicto_supervisor_practicante((int) $item['id_practicante']);
                                                $tieneHorarioItem = !empty($item['id_horario']);
                                                $estadoHorarioFiltro = $tieneHorarioItem ? 'con-horario' : 'sin-horario';
                                                $urlAbrirFicha = url($actionPath . '?id_practicante=' . (int) $item['id_practicante'] . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : ''));
                                                $esFilaSeleccionada = $practicanteSeleccionado !== null
                                                    && (int) ($practicanteSeleccionado['id_practicante'] ?? 0) === (int) $item['id_practicante'];
                                            ?>
                                            <tr
                                                role="row"
                                                data-practicante-row
                                                data-horario-estado="<?= e($estadoHorarioFiltro) ?>"
                                                class="<?= $esFilaSeleccionada ? 'bg-blue-50/60' : '' ?>"
                                            >
                                                <td role="cell" data-label="Practicante"><div class="rrhh-cell-value flex items-start gap-3">
                                                    <span class="rrhh-practicante-avatar shrink-0" aria-hidden="true"><?= e($iniciales) ?></span>
                                                    <div class="min-w-0">
                                                        <strong class="block leading-snug"><?= e($nombre) ?></strong>
                                                        <div class="text-slate-500 text-xs mt-0.5"><?= e((string) $item['email']) ?></div>
                                                        <?php
                                                            // Mostrar badge de estado de cuenta para que el admin sepa
                                                            // si el practicante ya activó su cuenta o todavía está pendiente
                                                            $estadoCuenta = (string) ($item['estado_cuenta'] ?? '');
                                                        ?>
                                                        <?php if ($estadoCuenta === 'PENDIENTE_ACTIVACION'): ?>
                                                            <span class="rrhh-badge bg-amber-100 text-amber-900 mt-1.5" title="El practicante no ha activado su cuenta">
                                                                <i class="bi bi-hourglass-split"></i> Pendiente de activación
                                                            </span>
                                                        <?php elseif ($estadoCuenta === 'ACTIVA'): ?>
                                                            <span class="rrhh-badge bg-emerald-100 text-emerald-800 mt-1.5">
                                                                <i class="bi bi-patch-check"></i> Cuenta activa
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div></td>
                                                <td role="cell" data-label="Práctica"><div class="rrhh-cell-value">
                                                    <span class="font-semibold"><?= e((string) ($item['tipo_practica'] ?? 'Sin tipo')) ?></span>
                                                    <div class="text-slate-500 text-xs mt-0.5"><?= e((string) ($item['carrera'] ?? 'Sin carrera')) ?></div>
                                                </div></td>
                                                <td role="cell" data-label="Área"><div class="rrhh-cell-value">
                                                    <?php if (!empty($item['area_vigente'])): ?>
                                                        <span class="inline-flex items-center gap-1.5 font-medium">
                                                            <i class="bi bi-building text-slate-400"></i>
                                                            <?= e((string) $item['area_vigente']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 text-xs">Sin área asignada</span>
                                                    <?php endif; ?>
                                                </div></td>
                                                <td role="cell" data-label="Horario"><div class="rrhh-cell-value">
                                                    <?php if ($tieneHorarioItem): ?>
                                                        <span class="rrhh-badge bg-emerald-100 text-emerald-800"><i class="bi bi-check2-circle"></i> Vigente</span>
                                                    <?php else: ?>
                                                        <span class="rrhh-badge bg-amber-100 text-amber-900"><i class="bi bi-exclamation-circle"></i> Sin horario</span>
                                                    <?php endif; ?>
                                                </div></td>
                                                <td role="cell" data-label="Acciones" class="text-right"><div class="rrhh-cell-value">
                                                    <?php if ($conflicto): ?>
                                                        <span class="rrhh-badge bg-slate-100 text-slate-500 border border-slate-200 select-none pointer-events-none"><i class="bi bi-lock-fill"></i> Bloqueado</span>
                                                    <?php else: ?>
                                                        <a class="rrhh-button rrhh-practicantes-open-btn !px-3.5 !py-1.5 !text-xs font-semibold" href="<?= e($urlAbrirFicha) ?>">
                                                            <i class="bi bi-person-vcard"></i> <span>Abrir ficha</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                <?php if ($practicanteSeleccionado !== null): ?>
                    <!-- Presenté la ficha y configuración de horario en un modal superpuesto centrado para evitar comprimir la tabla principal. -->
                    <div id="modalFichaPracticante" class="rrhh-practicante-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalFichaPracticanteTitulo" data-close-url="<?= e($urlListadoBase) ?>">
                        <div class="rrhh-practicante-modal-dialog panel">
                            <?php if (auth_es_conflicto_supervisor_practicante((int) $practicanteSeleccionado['id_practicante'])): ?>
                                <div class="rrhh-practicante-modal-top">
                                    <div class="rrhh-practicante-modal-header">
                                        <div>
                                            <h2 id="modalFichaPracticanteTitulo" class="text-base font-bold mb-1">Acción no permitida</h2>
                                            <p class="text-slate-500 text-sm mb-0">No puedes modificar tu propio horario desde el rol supervisor.</p>
                                        </div>
                                        <a href="<?= e($urlListadoBase) ?>" data-close-practicante-modal class="rrhh-practicante-modal-close" aria-label="Cerrar ventana">
                                            <i class="bi bi-x-lg"></i> <span>Cerrar</span>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                    $nombreSeleccionado = trim(
                                        (string) $practicanteSeleccionado['nombre_1'] . ' '
                                        . (string) $practicanteSeleccionado['apellido_paterno']
                                    );
                                    $inicialesSeleccionado = strtoupper(
                                        substr(trim((string) ($practicanteSeleccionado['nombre_1'] ?? 'P')), 0, 1)
                                        . substr(trim((string) ($practicanteSeleccionado['apellido_paterno'] ?? '')), 0, 1)
                                    );
                                ?>
                                <div class="rrhh-practicante-modal-top">
                                    <div class="rrhh-practicante-modal-header">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="rrhh-practicante-avatar rrhh-practicante-avatar--lg shrink-0" aria-hidden="true"><?= e($inicialesSeleccionado) ?></span>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h2 id="modalFichaPracticanteTitulo" class="text-lg font-bold mb-0"><?= e($nombreSeleccionado) ?></h2>
                                                    <span class="rrhh-badge bg-slate-100 text-slate-700 border">ID práctica <?= e((string) $practicanteSeleccionado['id_practica']) ?></span>
                                                </div>
                                                <div class="text-slate-500 text-xs sm:text-sm truncate mt-0.5">
                                                    <?= e((string) $practicanteSeleccionado['email']) ?>
                                                    <?php if (!empty($practicanteSeleccionado['institucion'])): ?>
                                                        · <?= e((string) $practicanteSeleccionado['institucion']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="<?= e($urlListadoBase) ?>" data-close-practicante-modal class="rrhh-practicante-modal-close shrink-0" aria-label="Cerrar ficha del practicante">
                                            <i class="bi bi-x-lg"></i> <span>Cerrar</span>
                                        </a>
                                    </div>

                                    <nav class="rrhh-practicante-modal-nav" aria-label="Secciones de la ficha">
                                        <a href="#ficha-datos-personales" class="rrhh-practicante-nav-pill is-active" data-modal-nav-pill><i class="bi bi-person-lines-fill"></i> <span>Datos y práctica</span></a>
                                        <a href="#ficha-area-vigente" class="rrhh-practicante-nav-pill" data-modal-nav-pill><i class="bi bi-building"></i> <span>Área vigente</span></a>
                                        <a href="#ficha-excepciones" class="rrhh-practicante-nav-pill" data-modal-nav-pill><i class="bi bi-calendar2-week"></i> <span>Excepciones</span></a>
                                        <a href="#ficha-horario-pactado" class="rrhh-practicante-nav-pill" data-modal-nav-pill><i class="bi bi-clock"></i> <span>Horario semanal</span></a>
                                        <?php if ($rolPermitido === 'ADMIN'): ?>
                                            <a href="#ficha-baja-logica" class="rrhh-practicante-nav-pill rrhh-practicante-nav-pill--danger" data-modal-nav-pill><i class="bi bi-exclamation-octagon"></i> <span>Baja lógica</span></a>
                                        <?php endif; ?>
                                    </nav>
                                </div>

                                <div class="rrhh-practicante-modal-body">
                                    <?php if ($mensajeExito !== null): ?>
                                        <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800 mb-4" role="alert"><?= e($mensajeExito) ?></div>
                                    <?php endif; ?>

                                    <?php if ($errores !== []): ?>
                                        <div class="rrhh-alert border-red-200 bg-red-50 text-red-800 mb-4" role="alert">
                                            <ul class="mb-0">
                                                <?php foreach (array_unique($errores) as $error): ?>
                                                    <li><?= e((string) $error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                <?php
                                    // Si acabamos de hacer un reenvío exitoso y el email no se pudo enviar,
                                    // mostramos la URL para que el admin la copie y se la envíe manualmente
                                    if (isset($reenvioUrlActivacion) && $reenvioUrlActivacion !== null): ?>
                                    <div class="rrhh-alert border-amber-200 bg-amber-50 text-amber-900 mb-4">
                                        <strong><i class="bi bi-exclamation-triangle-fill mr-1"></i> El email no pudo enviarse automáticamente.</strong><br>
                                        Copia este link y envíaselo al practicante manualmente:<br>
                                        <a href="<?= e($reenvioUrlActivacion) ?>" class="break-words text-sm"><?= e($reenvioUrlActivacion) ?></a>
                                    </div>
                                <?php endif; ?>

                                <?php
                                    // Obtener el estado de cuenta actual del practicante seleccionado
                                    // para decidir si mostrar el botón de reenvío o no
                                    $estadoCuentaSelec = (string) ($practicanteSeleccionado['estado_cuenta'] ?? '');
                                ?>
                                <?php if ($estadoCuentaSelec === 'PENDIENTE_ACTIVACION' && $rolPermitido === 'ADMIN'): ?>
                                    <div class="rrhh-alert border-amber-200 bg-amber-50 text-amber-900 flex items-start gap-3 mb-4">
                                        <div class="grow">
                                            <strong><i class="bi bi-hourglass-split mr-1"></i> Cuenta pendiente de activación</strong><br>
                                            <span class="text-sm">El practicante no ha activado su cuenta. Puedes reenviarle el link de activación con una nueva vigencia de <?= TOKEN_EXPIRATION_HOURS ?> horas.</span>
                                        </div>
                                        <form method="post" action="<?= e(url($actionPath)) ?>" class="shrink-0">
                                            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                            <input type="hidden" name="accion" value="reenviar_activacion">
                                            <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">
                                            <button type="submit" class="rrhh-button bg-amber-300 text-amber-950 hover:bg-amber-400 !px-3 !py-1.5 !text-xs"><i class="bi bi-send"></i> Reenviar link</button>
                                        </form>
                                    </div>
                                <?php elseif ($estadoCuentaSelec === 'ACTIVA'): ?>
                                    <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800 mb-4 py-2 text-sm">
                                        <i class="bi bi-check-circle-fill mr-1"></i> <strong>Cuenta activada</strong> — El practicante ya puede iniciar sesión.
                                    </div>
                                <?php endif; ?>

                                <?php
                                    // Formatear RUT con formato estándar chileno (ej: 12.345.678-9)
                                    $rutCompleto = 'Sin RUT';
                                    if (!empty($practicanteSeleccionado['rut_numero'])) {
                                        $rutNum = (string) $practicanteSeleccionado['rut_numero'];
                                        $rutDv = (string) ($practicanteSeleccionado['rut_dv'] ?? '');
                                        $rutFormateado = number_format((float) preg_replace('/\D/', '', $rutNum), 0, '', '.');
                                        $rutCompleto = $rutFormateado . ($rutDv !== '' ? '-' . $rutDv : '');
                                    }

                                    // Formatear nombre completo
                                    $nombreCompletoFicha = trim(implode(' ', array_filter([
                                        $practicanteSeleccionado['nombre_1'] ?? '',
                                        $practicanteSeleccionado['nombre_2'] ?? '',
                                        $practicanteSeleccionado['apellido_paterno'] ?? '',
                                        $practicanteSeleccionado['apellido_materno'] ?? '',
                                    ])));

                                    // Formatear fecha de nacimiento y cálculo de edad
                                    $fechaNacimientoTxt = 'No registrada';
                                    $edadTxt = '';
                                    if (!empty($practicanteSeleccionado['fecha_nacimiento'])) {
                                        $timeNac = strtotime((string) $practicanteSeleccionado['fecha_nacimiento']);
                                        if ($timeNac !== false) {
                                            $fechaNacimientoTxt = date('d-m-Y', $timeNac);
                                            $edad = (int) date('Y') - (int) date('Y', $timeNac);
                                            if (date('md') < date('md', $timeNac)) {
                                                $edad--;
                                            }
                                            $edadTxt = " ({$edad} años)";
                                        }
                                    }

                                    // Formatear dirección completa
                                    $partesDireccion = array_filter([
                                        trim(($practicanteSeleccionado['direccion'] ?? '') . ' ' . ($practicanteSeleccionado['numero_direccion'] ?? '')),
                                        !empty($practicanteSeleccionado['departamento']) ? 'Depto/Of: ' . $practicanteSeleccionado['departamento'] : '',
                                        $practicanteSeleccionado['comuna'] ?? '',
                                        $practicanteSeleccionado['region'] ?? '',
                                        $practicanteSeleccionado['pais'] ?? '',
                                    ]);
                                    $direccionCompletaTxt = $partesDireccion !== [] ? implode(', ', $partesDireccion) : 'No registrada';
                                ?>

                                <!-- Ficha estructurada de datos personales y académicos del practicante en dos columnas en escritorio -->
                                <div id="ficha-datos-personales" class="grid grid-cols-12 gap-4 mb-4">
                                    <div class="col-span-12 lg:col-span-6">
                                        <div class="rrhh-card border h-full shadow-none bg-slate-50">
                                            <div class="border-b border-slate-200 px-4 py-2.5 bg-white flex items-center justify-between gap-2">
                                                <div class="font-bold text-xs tracking-wide text-slate-600 flex items-center gap-1.5">
                                                    <i class="bi bi-person-vcard text-blue-600"></i>
                                                    <span>Ficha de datos personales y contacto</span>
                                                </div>
                                                <span class="rrhh-badge bg-slate-100 text-slate-700">RUT: <?= e($rutCompleto) ?></span>
                                            </div>
                                            <div class="p-4">
                                                <div class="grid grid-cols-12 gap-3">
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-person text-slate-400"></i> Nombre completo</div>
                                                        <div class="font-semibold text-sm mt-0.5"><?= e($nombreCompletoFicha !== '' ? $nombreCompletoFicha : $nombreSeleccionado) ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-calendar-event text-slate-400"></i> Fecha de nacimiento</div>
                                                        <div class="text-sm mt-0.5"><?= e($fechaNacimientoTxt . $edadTxt) ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-envelope text-slate-400"></i> Correo electrónico</div>
                                                        <div class="text-sm mt-0.5"><a href="mailto:<?= e((string) $practicanteSeleccionado['email']) ?>" class="no-underline"><?= e((string) $practicanteSeleccionado['email']) ?></a></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-telephone text-slate-400"></i> Teléfono</div>
                                                        <div class="text-sm mt-0.5"><?= !empty($practicanteSeleccionado['telefono']) ? e((string) $practicanteSeleccionado['telefono']) : '<span class="text-slate-500">No registrado</span>' ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-globe-americas text-slate-400"></i> Nacionalidad / Sexo</div>
                                                        <div class="text-sm mt-0.5"><?= e((string) ($practicanteSeleccionado['nacionalidad'] ?? 'Chilena')) ?> <?= !empty($practicanteSeleccionado['sexo']) ? '(' . e((string) $practicanteSeleccionado['sexo']) . ')' : '' ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-geo-alt text-slate-400"></i> Dirección</div>
                                                        <div class="text-sm break-words mt-0.5"><?= e($direccionCompletaTxt) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-span-12 lg:col-span-6">
                                        <div class="rrhh-card border h-full shadow-none bg-slate-50">
                                            <div class="border-b border-slate-200 px-4 py-2.5 bg-white">
                                                <div class="font-bold text-xs tracking-wide text-slate-600 flex items-center gap-1.5">
                                                    <i class="bi bi-mortarboard text-blue-600"></i>
                                                    <span>Datos académicos y de la práctica</span>
                                                </div>
                                            </div>
                                            <div class="p-4">
                                                <div class="grid grid-cols-12 gap-3">
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-journal-bookmark text-slate-400"></i> Carrera</div>
                                                        <div class="font-semibold text-sm mt-0.5"><?= e((string) ($practicanteSeleccionado['carrera'] ?? 'Sin carrera')) ?> <?= !empty($practicanteSeleccionado['semestre']) ? '(Semestre ' . e((string) $practicanteSeleccionado['semestre']) . ')' : '' ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-bank text-slate-400"></i> Institución / Sede</div>
                                                        <div class="text-sm mt-0.5"><?= e((string) ($practicanteSeleccionado['institucion'] ?? 'Sin institución')) ?> - <?= e((string) ($practicanteSeleccionado['nombre_sede'] ?? 'Sin sede')) ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-briefcase text-slate-400"></i> Tipo de práctica</div>
                                                        <div class="text-sm mt-0.5"><?= e((string) ($practicanteSeleccionado['tipo_practica'] ?? 'Estándar')) ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-hourglass-bottom text-slate-400"></i> Horas requeridas</div>
                                                        <div class="text-sm mt-0.5">
                                                            <!-- Formateo visual de horas requeridas y previas redondeadas a enteros cerrados -->
                                                            <strong><?= e(formatear_horas_enteras($practicanteSeleccionado['horas_requeridas'] ?? 0)) ?> hrs</strong>
                                                            <?php if ((float) ($practicanteSeleccionado['horas_previas'] ?? 0) > 0): ?>
                                                                <span class="text-slate-500 text-xs">(<?= e(formatear_horas_enteras($practicanteSeleccionado['horas_previas'])) ?> hrs previas)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-calendar-check text-slate-400"></i> Fecha inicio práctica</div>
                                                        <div class="text-sm mt-0.5"><?= e((string) ($practicanteSeleccionado['fecha_inicio_practica'] ?? 'Sin fecha')) ?></div>
                                                    </div>
                                                    <div class="col-span-12 sm:col-span-6">
                                                        <div class="text-xs text-slate-500 flex items-center gap-1"><i class="bi bi-calendar-range text-slate-400"></i> Fecha término estimada</div>
                                                        <div class="text-sm mt-0.5"><?= !empty($practicanteSeleccionado['fecha_termino_practica']) ? e((string) $practicanteSeleccionado['fecha_termino_practica']) : '<span class="text-slate-500">No registrada</span>' ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Agregué el resumen académico publicado sin controles para calificar desde la ficha. -->
                                <section class="rrhh-card p-4 mb-4" aria-label="Notas semanales">
                                    <?php $resumenNota = $resumenNotaFicha; require VIEWS_PATH . '/components/notas-resumen.php'; ?>
                                </section>

                                <!-- Adapté el borde de Área vigente a la paleta activa para evitar líneas claras y duras. -->
                                <form id="ficha-area-vigente" method="post" action="<?= e(url($actionPath)) ?>" class="rrhh-theme-panel-border border rounded-xl p-4 mb-4" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                    <input type="hidden" name="accion" value="guardar_area">
                                    <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">

                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                        <div>
                                            <h3 class="text-base font-bold mb-0.5">Área vigente</h3>
                                            <div class="text-slate-500 text-xs"><?= e((string) ($practicanteSeleccionado['area_vigente'] ?? 'Sin area asignada')) ?></div>
                                        </div>
                                        <span class="rrhh-badge bg-slate-50 text-slate-700 border">Historial conservado</span>
                                    </div>

                                    <div class="grid grid-cols-12 gap-4">
                                        <div class="col-span-12 md:col-span-5">
                                            <label class="rrhh-label" for="id_area">Nueva área</label>
                                            <select class="rrhh-select" id="id_area" name="id_area" required>
                                                <option value="">Seleccionar</option>
                                                <?php foreach ($areasCatalogo as $area): ?>
                                                    <option value="<?= e((string) $area['id_area']) ?>" <?= (int) ($practicanteSeleccionado['id_area_vigente'] ?? 0) === (int) $area['id_area'] ? 'selected' : '' ?>>
                                                        <?= e((string) $area['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-4">
                                            <label class="rrhh-label" for="fecha_inicio_area">Inicio de vigencia</label>
                                            <input class="rrhh-input" type="date" id="fecha_inicio_area" name="fecha_inicio_area" value="<?= e((string) ($practicanteSeleccionado['fecha_inicio_practica'] ?? date('Y-m-d'))) ?>" required>
                                        </div>
                                        <div class="col-span-12 md:col-span-3 flex items-end">
                                            <button class="rrhh-button rrhh-practicantes-search-btn w-full" type="submit" <?= $areasCatalogo === [] ? 'disabled' : '' ?>>
                                                <i class="bi bi-check2-circle"></i> <span>Guardar área</span>
                                            </button>
                                        </div>
                                        <div class="col-span-12">
                                            <label class="rrhh-label" for="observacion_area">Observación</label>
                                            <input class="rrhh-input" id="observacion_area" name="observacion_area" maxlength="255">
                                        </div>
                                    </div>

                                    <?php if ($historialAreas !== []): ?>
                                        <div class="rrhh-table-scroll overflow-x-auto mt-4">
                                            <!-- Adapté el listado operativo con etiquetas y roles para conservar datos y acciones en paneles estrechos. -->
<table role="table" class="rrhh-responsive-table rrhh-table rrhh-table-compact align-middle mb-0">
                                                <thead role="rowgroup">
                                                    <tr role="row">
                                                        <th role="columnheader">Area</th>
                                                        <th role="columnheader">Inicio</th>
                                                        <th role="columnheader">Fin</th>
                                                        <th role="columnheader">Asignado por</th>
                                                    </tr>
                                                </thead>
                                                <tbody role="rowgroup">
                                                    <?php foreach ($historialAreas as $historialArea): ?>
                                                        <tr role="row">
                                                            <td role="cell" data-label="Área"><div class="rrhh-cell-value"><?= e((string) $historialArea['area_nombre']) ?></div></td>
                                                            <td role="cell" data-label="Inicio"><div class="rrhh-cell-value"><?= e((string) $historialArea['fecha_inicio']) ?></div></td>
                                                            <td role="cell" data-label="Fin"><div class="rrhh-cell-value"><?= e((string) ($historialArea['fecha_fin'] ?? 'Vigente')) ?></div></td>
                                                            <td role="cell" data-label="Asignado por"><div class="rrhh-cell-value"><?= e((string) ($historialArea['asignado_por_email'] ?? 'No registrado')) ?></div></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </form>

                                <!-- Agrupé el formulario y el listado de excepciones de horario dentro de un mismo bloque visual. -->
                                <div id="ficha-excepciones" class="rrhh-theme-panel-border border rounded-xl p-4 mb-4">
                                    <form method="post" action="<?= e(url($actionPath)) ?>" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                        <input type="hidden" name="accion" value="guardar_excepcion_horario">
                                        <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">

                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                            <div>
                                                <h3 class="text-base font-bold mb-0.5">Excepciones de horario</h3>
                                                <div class="text-slate-500 text-xs">Cambios puntuales sobre el horario vigente</div>
                                            </div>
                                            <span class="rrhh-badge bg-slate-50 text-slate-700 border">Prioridad sobre horario semanal</span>
                                        </div>

                                        <div class="grid grid-cols-12 gap-4">
                                            <div class="col-span-12 md:col-span-4">
                                                <label class="rrhh-label" for="fecha_excepcion">Fecha</label>
                                                <input class="rrhh-input" type="date" id="fecha_excepcion" name="fecha_excepcion" value="<?= e(date('Y-m-d')) ?>" required>
                                            </div>
                                            <div class="col-span-12 md:col-span-4">
                                                <label class="rrhh-label" for="tipo_excepcion">Tipo</label>
                                                <select class="rrhh-select" id="tipo_excepcion" name="tipo_excepcion" required>
                                                    <?php foreach (excepciones_horario_tipos() as $codigo => $nombreTipo): ?>
                                                        <option value="<?= e($codigo) ?>"><?= e($nombreTipo) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-span-12 md:col-span-4 flex items-end">
                                                <button class="rrhh-button rrhh-practicantes-search-btn w-full" type="submit">
                                                    <i class="bi bi-calendar-plus"></i> <span>Guardar excepción</span>
                                                </button>
                                            </div>
                                            <div class="col-span-12 md:col-span-3">
                                                <label class="rrhh-label" for="entrada_excepcion">Entrada</label>
                                                <input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="entrada_excepcion" name="entrada_excepcion">
                                            </div>
                                            <div class="col-span-12 md:col-span-3">
                                                <label class="rrhh-label" for="salida_excepcion">Salida</label>
                                                <input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="salida_excepcion" name="salida_excepcion">
                                            </div>
                                            <div class="col-span-12 md:col-span-3">
                                                <label class="rrhh-label" for="colacion_inicio_excepcion">Inicio colación</label>
                                                <input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="colacion_inicio_excepcion" name="colacion_inicio_excepcion">
                                            </div>
                                            <div class="col-span-12 md:col-span-3">
                                                <label class="rrhh-label" for="colacion_fin_excepcion">Fin colación</label>
                                                <input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="colacion_fin_excepcion" name="colacion_fin_excepcion">
                                            </div>
                                            <div class="col-span-12">
                                                <label class="rrhh-label" for="observacion_excepcion">Observación</label>
                                                <input class="rrhh-input" id="observacion_excepcion" name="observacion_excepcion" maxlength="255">
                                            </div>
                                        </div>
                                    </form>

                                    <?php if ($excepcionesHorario !== []): ?>
                                        <div class="rrhh-table-scroll overflow-x-auto mt-4">
                                            <!-- Adapté el listado operativo con etiquetas y roles para conservar datos y acciones en paneles estrechos. -->
<table role="table" class="rrhh-responsive-table rrhh-table rrhh-table-compact align-middle mb-0">
                                                <thead role="rowgroup">
                                                    <tr role="row">
                                                        <th role="columnheader">Fecha</th>
                                                        <th role="columnheader">Tipo</th>
                                                        <th role="columnheader">Horario</th>
                                                        <th role="columnheader">Observacion</th>
                                                        <th role="columnheader">Estado</th>
                                                        <th role="columnheader">Accion</th>
                                                    </tr>
                                                </thead>
                                                <tbody role="rowgroup">
                                                    <?php foreach ($excepcionesHorario as $excepcionHorario): ?>
                                                        <?php
                                                            $excepcionActiva = (int) $excepcionHorario['activo'] === 1;
                                                            $tipoExcepcion = (string) $excepcionHorario['tipo_excepcion'];
                                                            $nombreTipoExcepcion = excepciones_horario_tipos()[$tipoExcepcion] ?? $tipoExcepcion;
                                                            $observacionExcepcion = trim((string) ($excepcionHorario['observacion'] ?? ''));
                                                        ?>
                                                        <tr role="row">
                                                            <td role="cell" data-label="Fecha"><div class="rrhh-cell-value"><?= e((string) $excepcionHorario['fecha']) ?></div></td>
                                                            <td role="cell" data-label="Tipo"><div class="rrhh-cell-value"><?= e((string) $nombreTipoExcepcion) ?></div></td>
                                                            <td role="cell" data-label="Horario"><div class="rrhh-cell-value">
                                                                <?php if (!empty($excepcionHorario['entrada_esperada']) && !empty($excepcionHorario['salida_esperada'])): ?>
                                                                    <?= e(substr((string) $excepcionHorario['entrada_esperada'], 0, 5)) ?>-<?= e(substr((string) $excepcionHorario['salida_esperada'], 0, 5)) ?>
                                                                <?php else: ?>
                                                                    <span class="text-slate-500 text-sm">Sin horario</span>
                                                                <?php endif; ?>
                                                            </div></td>
                                                            <td role="cell" data-label="Observación"><div class="rrhh-cell-value">
                                                                <?php if ($observacionExcepcion !== ''): ?>
                                                                    <!-- Conservé el texto completo y lo desplegué por fila para mantener compacta la tabla. -->
                                                                    <details class="rrhh-observation-disclosure">
                                                                        <summary class="rrhh-observation-toggle"><span class="rrhh-observation-more">Ver más</span><span class="rrhh-observation-less">Ver menos</span></summary>
                                                                        <div class="rrhh-observation-text"><?= e($observacionExcepcion) ?></div>
                                                                    </details>
                                                                <?php else: ?>
                                                                    <span class="rrhh-observation-text">Sin observación</span>
                                                                <?php endif; ?>
                                                            </div></td>
                                                            <td role="cell" data-label="Estado"><div class="rrhh-cell-value">
                                                                <span class="rrhh-badge <?= e(rrhh_tone($excepcionActiva ? 'success' : 'secondary')) ?>">
                                                                    <?= $excepcionActiva ? 'ACTIVA' : 'INACTIVA' ?>
                                                                </span>
                                                            </div></td>
                                                            <td role="cell" data-label="Acción"><div class="rrhh-cell-value">
                                                                <?php if ($excepcionActiva): ?>
                                                                    <form method="post" action="<?= e(url($actionPath)) ?>" novalidate>
                                                                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                                                        <input type="hidden" name="accion" value="anular_excepcion_horario">
                                                                        <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">
                                                                        <input type="hidden" name="id_excepcion" value="<?= e((string) $excepcionHorario['id_excepcion']) ?>">
                                                                        <div class="rrhh-input-group text-xs">
                                                                            <input class="rrhh-input" name="motivo_anulacion_excepcion" maxlength="255" placeholder="Motivo" required>
                                                                            <button class="rrhh-button rrhh-practicantes-danger-btn" type="submit">Anular</button>
                                                                        </div>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <span class="text-slate-500 text-sm">Sin accion</span>
                                                                <?php endif; ?>
                                                            </div></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <form id="ficha-horario-pactado" method="post" action="<?= e(url($actionPath)) ?>" class="rrhh-theme-panel-border border rounded-xl p-4 mb-4" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                    <input type="hidden" name="accion" value="guardar_horario">
                                    <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">

                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                        <div>
                                            <h3 class="text-base font-bold mb-0.5">Horario pactado</h3>
                                            <div class="text-slate-500 text-xs">Jornada semanal regular de lunes a domingo</div>
                                        </div>
                                        <span class="rrhh-badge bg-slate-50 text-slate-700 border">Vigencia semanal</span>
                                    </div>

                                    <div class="grid grid-cols-12 gap-4 mb-4">
                                        <div class="col-span-12 md:col-span-5">
                                            <label class="rrhh-label" for="fecha_inicio_horario">Inicio de vigencia</label>
                                            <input class="rrhh-input" type="date" id="fecha_inicio_horario" name="fecha_inicio_horario" value="<?= e($fechaInicioHorario !== '' ? $fechaInicioHorario : date('Y-m-d')) ?>" required>
                                        </div>
                                        <div class="col-span-12 md:col-span-7">
                                            <label class="rrhh-label">Práctica</label>
                                            <div class="rrhh-input rrhh-non-interactive select-none pointer-events-none">
                                                <?= e((string) ($practicanteSeleccionado['tipo_practica'] ?? 'Sin tipo')) ?>
                                                <!-- Formateo de horas de la práctica redondeadas a entero -->
                                                - <?= e(formatear_horas_enteras($practicanteSeleccionado['horas_requeridas'] ?? 0)) ?> horas
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Barra de acciones rápidas para agilizar la carga repetitiva de horarios de lunes a viernes. -->
                                    <div class="rrhh-horario-quickbar mb-3">
                                        <span class="text-xs font-semibold text-slate-500 flex items-center gap-1.5 select-none">
                                            <i class="bi bi-lightning-charge text-blue-600"></i> Atajos de llenado:
                                        </span>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" class="rrhh-horario-quickbtn" data-horario-action="copy-mon-fri">
                                                <i class="bi bi-files"></i> Replicar lunes en lunes–viernes
                                            </button>
                                            <button type="button" class="rrhh-horario-quickbtn" data-horario-action="preset-standard">
                                                <i class="bi bi-clock-history"></i> Jornada base (10:00–18:00)
                                            </button>
                                            <button type="button" class="rrhh-horario-quickbtn rrhh-horario-quickbtn--muted" data-horario-action="clear-all">
                                                <i class="bi bi-eraser"></i> Limpiar días
                                            </button>
                                        </div>
                                    </div>

                                    <?php horarios_practicante_render_form($horarioFormulario, true); ?>

                                    <div class="flex justify-end gap-2 mt-4">
                                        <a class="rrhh-button rrhh-clean-filter-btn" href="<?= e($urlListadoBase) ?>" data-close-practicante-modal>Cancelar</a>
                                        <button class="rrhh-button bg-brand-blue text-white hover:bg-blue-700" type="submit">
                                            <i class="bi bi-check2-circle"></i> <span>Guardar horario</span>
                                        </button>
                                    </div>
                                </form>

                                <?php if ($rolPermitido === 'ADMIN'): ?>
                                    <form id="ficha-baja-logica" method="post" action="<?= e(url($actionPath)) ?>" class="border border-red-300 rounded-xl p-4" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_gestion_horarios']) ?>">
                                        <input type="hidden" name="accion" value="baja_practicante">
                                        <input type="hidden" name="id_practicante" value="<?= e((string) $practicanteSeleccionado['id_practicante']) ?>">

                                        <h3 class="text-base font-bold text-red-700 mb-1 flex items-center gap-1.5">
                                            <i class="bi bi-exclamation-octagon"></i> Baja lógica
                                        </h3>
                                        <p class="text-slate-500 text-sm mb-4">
                                            Esta acción desactiva al practicante, sus prácticas activas y la cuenta de usuario vinculada. No borra registros históricos.
                                        </p>
                                        <div class="flex items-center gap-2 mb-4">
                                            <input class="rrhh-checkbox" type="checkbox" id="confirmar_baja" name="confirmar_baja" value="1" required>
                                            <label class="text-sm" for="confirmar_baja">
                                                Confirmo que deseo dar de baja a este practicante.
                                            </label>
                                        </div>
                                        <button class="rrhh-button rrhh-practicantes-danger-btn" type="submit">
                                            <i class="bi bi-person-x"></i> <span>Dar de baja</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Toast flotante tipo HUD para feedback interactivo sin alterar el layout -->
                <div id="rrhhToastFeedback" class="rrhh-toast-feedback" role="status" aria-live="polite">
                    <i id="rrhhToastIcon" class="bi bi-check-circle-fill text-emerald-400 text-sm"></i>
                    <span id="rrhhToastText"></span>
                </div>

                <script>
                    // Gestiona pestañas de filtro de horario, cierre del modal y atajos del horario semanal.
                    (function () {
                        const filterTabs = Array.from(document.querySelectorAll('[data-filter-horario]'));
                        const rows = Array.from(document.querySelectorAll('[data-practicante-row]'));
                        const emptyFilterRow = document.getElementById('filaSinResultadosFiltroHorario');

                        if (filterTabs.length > 0) {
                            filterTabs.forEach(function (tab) {
                                tab.addEventListener('click', function () {
                                    const filter = tab.getAttribute('data-filter-horario') || 'todos';
                                    let visibleCount = 0;
                                    filterTabs.forEach(function (item) {
                                        const active = item === tab;
                                        item.classList.toggle('is-active', active);
                                        item.setAttribute('aria-pressed', active ? 'true' : 'false');
                                        if (active) {
                                            item.setAttribute('aria-current', 'page');
                                        } else {
                                            item.removeAttribute('aria-current');
                                        }
                                    });
                                    rows.forEach(function (row) {
                                        const match = filter === 'todos' || row.getAttribute('data-horario-estado') === filter;
                                        row.hidden = !match;
                                        if (match) visibleCount++;
                                    });
                                    if (emptyFilterRow && rows.length > 0) {
                                        emptyFilterRow.hidden = visibleCount > 0;
                                    }
                                });
                            });
                        }

                        const modal = document.getElementById('modalFichaPracticante');
                        if (!modal) return;
                        const closeUrl = modal.getAttribute('data-close-url') || window.location.pathname;
                        function closeModal(event) {
                            if (event) event.preventDefault();
                            modal.hidden = true;
                            modal.style.display = 'none';
                            document.body.classList.remove('rrhh-practicante-modal-open');
                            if (window.history && typeof window.history.replaceState === 'function') {
                                window.history.replaceState({}, document.title, closeUrl);
                            }
                        }
                        document.body.classList.add('rrhh-practicante-modal-open');
                        modal.querySelectorAll('[data-close-practicante-modal]').forEach(function (btn) {
                            btn.addEventListener('click', closeModal);
                        });
                        modal.addEventListener('click', function (event) {
                            if (event.target === modal) {
                                closeModal(event);
                            }
                        });
                        document.addEventListener('keydown', function (event) {
                            if (event.key === 'Escape' && !modal.hidden) {
                                closeModal(event);
                            }
                        });

                        const navPills = Array.from(modal.querySelectorAll('[data-modal-nav-pill]'));
                        navPills.forEach(function (pill) {
                            pill.addEventListener('click', function () {
                                navPills.forEach(function (item) {
                                    item.classList.toggle('is-active', item === pill);
                                });
                            });
                        });

                        const horarioForm = document.getElementById('ficha-horario-pactado');
                        const toastEl = document.getElementById('rrhhToastFeedback');
                        const toastIconEl = document.getElementById('rrhhToastIcon');
                        const toastTextEl = document.getElementById('rrhhToastText');
                        let toastTimer = null;

                        function showQuickFeedback(text, type = 'success') {
                            if (!toastEl || !toastTextEl || !toastIconEl) return;
                            toastTextEl.textContent = text;
                            toastEl.classList.remove('is-warning', 'is-error');

                            if (type === 'warning') {
                                toastIconEl.className = 'bi bi-exclamation-triangle-fill text-amber-400 text-sm';
                                toastEl.classList.add('is-warning');
                            } else if (type === 'error') {
                                toastIconEl.className = 'bi bi-x-circle-fill text-rose-400 text-sm';
                                toastEl.classList.add('is-error');
                            } else {
                                toastIconEl.className = 'bi bi-check-circle-fill text-emerald-400 text-sm';
                            }

                            toastEl.classList.add('is-visible');
                            if (toastTimer) clearTimeout(toastTimer);
                            toastTimer = setTimeout(function () {
                                toastEl.classList.remove('is-visible');
                            }, 2800);
                        }

                        function getDayField(day, field) {
                            return horarioForm ? horarioForm.querySelector('[name="horario[' + day + '][' + field + ']"]') : null;
                        }

                        function triggerHighlight(field) {
                            if (!field) return;
                            field.classList.remove('rrhh-field-highlight');
                            void field.offsetWidth;
                            field.classList.add('rrhh-field-highlight');
                            setTimeout(function () {
                                field.classList.remove('rrhh-field-highlight');
                            }, 550);
                        }

                        // Aplica valores con retardo escalonado para dar fluidez física al autocompletado
                        function setDayValues(d, inVal, outVal, colInVal, colOutVal, isActive, delayMs, highlight) {
                            setTimeout(function () {
                                const chk = getDayField(d, 'activo');
                                const fIn = getDayField(d, 'entrada_esperada');
                                const fOut = getDayField(d, 'salida_esperada');
                                const fColIn = getDayField(d, 'colacion_inicio');
                                const fColOut = getDayField(d, 'colacion_fin');
                                if (chk) chk.checked = isActive;
                                if (fIn) { fIn.value = inVal; if (highlight) triggerHighlight(fIn); }
                                if (fOut) { fOut.value = outVal; if (highlight) triggerHighlight(fOut); }
                                if (fColIn) { fColIn.value = colInVal; if (highlight) triggerHighlight(fColIn); }
                                if (fColOut) { fColOut.value = colOutVal; if (highlight) triggerHighlight(fColOut); }
                            }, delayMs);
                        }

                        if (horarioForm) {
                            horarioForm.querySelectorAll('[data-horario-action]').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    const action = btn.getAttribute('data-horario-action');
                                    if (action === 'copy-mon-fri') {
                                        const monInField = getDayField(1, 'entrada_esperada');
                                        const monOutField = getDayField(1, 'salida_esperada');
                                        const monIn = (monInField?.value || '').trim();
                                        const monOut = (monOutField?.value || '').trim();

                                        // Si lunes no tiene horas ingresadas, muestra alerta de orientación y micro-shake en el botón
                                        if (!monIn && !monOut) {
                                            showQuickFeedback('Ingresa primero el horario del día lunes para replicarlo', 'warning');
                                            btn.classList.remove('rrhh-btn-shake');
                                            void btn.offsetWidth;
                                            btn.classList.add('rrhh-btn-shake');
                                            setTimeout(function () {
                                                btn.classList.remove('rrhh-btn-shake');
                                            }, 600);
                                            const monChk = getDayField(1, 'activo');
                                            if (monChk) monChk.checked = true;
                                            if (monInField) monInField.focus();
                                            return;
                                        }

                                        const monColIn = (getDayField(1, 'colacion_inicio') || {}).value || '';
                                        const monColOut = (getDayField(1, 'colacion_fin') || {}).value || '';

                                        // Cascada fluida escalonada (40ms entre días) para que se aprecie la propagación orgánica
                                        for (let d = 1; d <= 5; d++) {
                                            const delay = (d - 1) * 40;
                                            setDayValues(d, monIn, monOut, monColIn, monColOut, true, delay, d > 1);
                                        }
                                        showQuickFeedback('Horario del lunes replicado de lunes a viernes', 'success');
                                    } else if (action === 'preset-standard') {
                                        // Cascada fluida para cargar la jornada base estándar (10:00-18:00)
                                        for (let d = 1; d <= 7; d++) {
                                            const active = d <= 5;
                                            const fInVal = active ? '10:00' : '';
                                            const fOutVal = active ? '18:00' : '';
                                            const fColInVal = active ? '14:00' : '';
                                            const fColOutVal = active ? '15:00' : '';
                                            const delay = active ? (d - 1) * 35 : 0;
                                            setDayValues(d, fInVal, fOutVal, fColInVal, fColOutVal, active, delay, active);
                                        }
                                        showQuickFeedback('Jornada base (10:00–18:00) cargada de lunes a viernes', 'success');
                                    } else if (action === 'clear-all') {
                                        for (let d = 1; d <= 7; d++) {
                                            setDayValues(d, '', '', '', '', false, 0, false);
                                        }
                                        showQuickFeedback('Días limpiados', 'success');
                                    }
                                });
                            });
                        }
                    })();
                </script>
        </div>
