<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Mensajes de feedback -->
    <?php if ($mensajeExito !== ''): ?>
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill text-emerald-600 text-lg shrink-0"></i>
            <span><?= e($mensajeExito) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <div class="font-bold">Por favor corrige los siguientes puntos:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    <?php foreach ($errores as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($practicaActiva !== null): ?>
        <?php if (empty($soloLectura)): ?>
            <?php require VIEWS_PATH.'/components/cambio-colacion.php'; ?>
        <?php endif; ?>
        <!-- Usé contenedor de ancho completo en modo solo lectura porque xl:col-span-12 no está compilado en tailwind.css. -->
        <div class="<?= !empty($soloLectura) ? 'space-y-6' : 'grid grid-cols-1 xl:grid-cols-12 gap-6 items-start' ?>">

            <!-- FORMULARIO DE SOLICITUD DE PERMISO (5 columnas en XL) -->
            <?php if (empty($soloLectura)): ?>
            <div class="xl:col-span-5">
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-5">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i class="bi bi-calendar-plus text-blue-600"></i>
                            <span>Solicitar nuevo permiso</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Solicita una ausencia pasada, de hoy o futura, de jornada completa o parcial. La recuperación nueva solo se realiza los sábados entre 10:00 y 20:00. Debes enviarla antes del viernes previo a las 12:00; después del corte puedes elegir el sábado siguiente. Espera la aprobación del supervisor.
                        </p>
                    </div>

                    <form method="post" action="<?= e(url('practicante/permisos.php')) ?>" id="formPermiso" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                        <input type="hidden" name="accion" value="solicitar_permiso">
                        <input type="hidden" name="tipo_ausencia" id="tipoAusenciaHidden" value="COMPLETA">

                        <!-- 1. Fecha de ausencia -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700" for="fecha_ausencia">
                                1. Día de ausencia <span class="text-red-500">*</span>
                            </label>
                            <input
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                type="date"
                                id="fecha_ausencia"
                                name="fecha_ausencia"
                                <?php // Acoté la fecha a la práctica, permitiendo regularizar ausencias anteriores. ?>
                                min="<?= e((string) ($practicaActiva['fecha_inicio_practica'] ?? '')) ?>"
                                <?php if (!empty($practicaActiva['fecha_termino_practica'])): ?>max="<?= e($practicaActiva['fecha_termino_practica']) ?>"<?php endif; ?>
                                value="<?= e((string) input('fecha_ausencia', '')) ?>"
                                required
                            >
                            <div class="text-[11px] text-slate-400">Debe existir jornada pactada en esa fecha. La aprobación ajusta únicamente los días solicitados; tu horario semanal no cambia.</div>

                            <!-- Panel informativo del horario del día seleccionado -->
                            <div id="panelHorarioDia" class="hidden mt-2 p-3 rounded-xl bg-blue-50/60 border border-blue-100 text-xs text-slate-700 space-y-1">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-clock text-blue-600"></i>
                                    <span>Jornada planificada: <strong id="txtHorarioJornada" class="text-slate-900 font-bold">–</strong></span>
                                </div>
                                <div class="text-slate-500 text-[11px]" id="txtColacion"></div>
                            </div>

                            <!-- Error si no hay jornada -->
                            <div id="alertSinJornada" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2 mt-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill text-amber-600"></i>
                                <span>No tienes jornada pactada para ese día. Selecciona otra fecha.</span>
                            </div>
                        </div>

                        <!-- 2. Tipo de ausencia: Completa o Parcial -->
                        <div class="space-y-1.5" id="seccionTipoAusencia">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                2. ¿Faltarás toda la jornada o solo un tramo? <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" name="_tipo_ausencia_radio" id="radioCompleta" value="COMPLETA" checked>
                                    <div class="p-3 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
                                        <i class="bi bi-calendar-x"></i>
                                        <span>Jornada completa</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" name="_tipo_ausencia_radio" id="radioParcial" value="PARCIAL">
                                    <div class="p-3 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 hover:bg-slate-50 transition flex items-center justify-center gap-1.5">
                                        <i class="bi bi-clock-history"></i>
                                        <span>Solo un tramo</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 2b. Tramo de ausencia parcial -->
                        <!-- Vinculé el panel parcial al tema en lugar de usar un fondo ámbar translúcido fijo. -->
                        <div id="seccionAusenciaParcial" class="hidden p-4 rounded-xl rrhh-permiso-parcial-panel border space-y-3">
                            <div class="font-bold text-amber-900 text-xs flex items-center gap-1.5">
                                <i class="bi bi-clock-history"></i> Indica el tramo de horas que faltarás:
                            </div>
                            <div class="text-[11px] text-amber-800 leading-snug">
                                <!-- Expliqué la nueva interrupción y sus marcas sin confundirlas con colación. -->
                                Puedes faltar al inicio, al final o salir y regresar durante tu turno. En una interrupción usa Salida por permiso y Regreso de permiso; la colación se marca por separado. Si ya trabajaste en ese tramo, la solicitud quedará en observación.
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1" for="hora_inicio_ausencia">Desde (inicio)</label>
                                    <input class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-800 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="hora_inicio_ausencia" name="hora_inicio_ausencia" value="<?= e((string) input('hora_inicio_ausencia', '')) ?>">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1" for="hora_fin_ausencia">Hasta (término)</label>
                                    <input class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-800 text-xs focus:ring-1 focus:ring-amber-500 focus:border-amber-500" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 id="hora_fin_ausencia" name="hora_fin_ausencia" value="<?= e((string) input('hora_fin_ausencia', '')) ?>">
                                </div>
                            </div>

                            <!-- Preview del resultado parcial -->
                            <!-- Conservé una superficie diferenciada del mismo tema para el resumen del tramo solicitado. -->
                            <div id="previewParcial" class="hidden p-3 rounded-lg rrhh-permiso-parcial-resumen border text-xs space-y-1">
                                <div class="grid grid-cols-2 text-center gap-2">
                                    <div>
                                        <div class="text-[11px] text-slate-500">Faltarás</div>
                                        <strong id="txtHorasAusencia" class="text-red-700 font-bold">– hrs</strong>
                                    </div>
                                    <div>
                                        <div class="text-[11px] text-slate-500">Trabajarás ese día</div>
                                        <strong id="txtHorasTrabajo" class="text-emerald-700 font-bold">– hrs</strong>
                                    </div>
                                </div>
                                <div class="text-center text-[11px] text-slate-600 pt-1">
                                    Nuevo horario a cumplir: <strong id="txtNuevoHorario" class="text-slate-900">–</strong>
                                </div>
                            </div>
                            <div id="errorParcial" class="hidden text-red-600 text-[11px] font-medium"></div>
                        </div>

                        <!-- 3. Plan de recuperación -->
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
                            <div class="font-bold text-slate-900 text-xs uppercase tracking-wider">
                                3. Plan de recuperación de horas <span class="text-red-500">*</span>
                            </div>

                            <!-- Agregué filas independientes; el servidor valida tipos, minutos y calendario. -->
                            <div id="recuperacionesFilas" data-api="<?= e(url('practicante/api/horario-dia.php')) ?>" data-previos="<?= e(json_encode(is_array(input('recuperaciones',[]))?array_values(input('recuperaciones',[])):[],JSON_THROW_ON_ERROR)) ?>">
                            <div class="recuperacion-fila space-y-3 border border-slate-200 rounded-xl p-3" data-index="0">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1" for="fecha_recuperacion">Fecha en que recuperarás</label>
                                <input class="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600" type="date" id="fecha_recuperacion" name="recuperaciones[0][fecha]" data-rec-fecha min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <label class="block text-xs text-slate-600">Tipo de recuperación
                                <select class="rrhh-select" name="recuperaciones[0][tipo]" data-rec-tipo required>
                                    <option value="">Selecciona una fecha para consultar opciones</option>
                                    <option value="SABADO">Sábado</option>
                                    <option value="FERIADO">Feriado con autorización expresa</option>
                                </select>
                            </label>
                            <p class="text-xs text-slate-600" data-rec-ayuda>Solo sábados de 10:00 a 20:00, sin ocupar tu jornada ordinaria. Envía antes del viernes previo a las 12:00; las solicitudes enviadas a tiempo pueden aprobarse después.</p>

                            <div id="avisoSabado" class="hidden p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-start gap-2" role="alert">
                                <i class="bi bi-info-circle-fill text-blue-600 mt-0.5"></i>
                                <span><strong>Día Sábado seleccionado:</strong> El horario de recuperación permitido es entre <strong>10:00 y 20:00 hrs</strong>.</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1" for="hora_inicio_recuperacion">Hora inicio</label>
                                    <input class="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 id="hora_inicio_recuperacion" name="recuperaciones[0][hora_inicio]" data-rec-inicio required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1" for="hora_fin_recuperacion">Hora término</label>
                                    <input class="w-full px-3 py-2 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 id="hora_fin_recuperacion" name="recuperaciones[0][hora_fin]" data-rec-fin required>
                                </div>
                            </div>

                            <button class="rrhh-button text-red-700" type="button" data-rec-quitar>Quitar tramo</button>
                            </div>
                            </div>
                            <button class="rrhh-button border-blue-600 text-blue-700" type="button" id="agregarRecuperacion">Agregar otro día o tramo</button>
                            <p class="text-xs text-slate-600">El plan debe sumar exactamente las horas laborales de ausencia. Solo sábados desde el día de ausencia en adelante, 10:00–20:00. No se admite recuperar mediante colación ni extensiones entre semana. Siempre se valida el trabajo con tus marcas.</p>
                            <div class="pt-1">
                                <div class="w-full flex flex-wrap items-center justify-between gap-3 px-4 py-3 rounded-xl text-xs bg-blue-50 text-blue-700 border border-blue-200" id="badgeHorasCalculadas">
                                    <span class="font-bold">Plan de recuperación: 0 minutos (0.00 hrs)</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Motivo -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700" for="motivo">
                                    4. Motivo de la ausencia <span class="text-red-500">*</span>
                                </label>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200" id="contadorPalabras">0 / 40 palabras</span>
                            </div>
                            <textarea
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"
                                id="motivo"
                                name="motivo"
                                rows="3"
                                placeholder="Explica brevemente por qué no asistirás (máximo 40 palabras)..."
                                required
                            ><?= e((string) input('motivo', '')) ?></textarea>
                            <div class="text-[11px] text-slate-400" id="ayudaPalabras">Máximo 40 palabras permitidas.</div>
                        </div>

                        <button
                            type="submit"
                            id="btnEnviar"
                            class="w-full py-3.5 px-4 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold rounded-xl shadow-md shadow-teal-700/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="bi bi-send-fill"></i>
                            <span>Enviar solicitud de permiso</span>
                        </button>
                    </form>
                </section>
            </div>
            <?php endif; ?>

            <!-- HISTORIAL DE SOLICITUDES DE PERMISO (ancho completo en modo solo lectura) -->
            <div class="<?= !empty($soloLectura) ? 'w-full' : 'xl:col-span-7' ?>">
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="bi bi-clock-history text-slate-400"></i>
                                <span>Mis permisos solicitados</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Historial y estado de tus solicitudes</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            <?= count($solicitudes) ?> registros
                        </span>
                    </div>

                    <div class="p-0">
                        <?php if ($solicitudes === []): ?>
                            <div class="text-center py-12 px-4 text-slate-400 space-y-2">
                                <i class="bi bi-calendar-check text-4xl block text-slate-300"></i>
                                <p class="text-sm">No has registrado solicitudes de permisos hasta la fecha.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <!-- Adapté esta tabla para mostrar campos etiquetados sin desplazamiento horizontal en practicantes. -->
                                <table role="table" class="rrhh-responsive-table w-full text-left text-xs sm:text-sm">
                                    <thead role="rowgroup" class="bg-slate-50 text-[11px] uppercase font-bold text-slate-500 border-b border-slate-100">
                                        <tr role="row">
                                            <th role="columnheader" class="px-4 py-3">Fecha ausencia</th>
                                            <th role="columnheader" class="px-4 py-3">Recuperación</th>
                                            <th role="columnheader" class="px-4 py-3">Motivo</th>
                                            <th role="columnheader" class="px-4 py-3">Estado</th>
                                            <th role="columnheader" class="px-4 py-3">Respuesta</th>
                                        </tr>
                                    </thead>
                                    <tbody role="rowgroup" class="divide-y divide-slate-100 text-slate-700">
                                        <?php foreach ($solicitudes as $solicitud): ?>
                                            <?php
                                            $esSabado  = date('N', strtotime((string) $solicitud['fecha_recuperacion'])) === '6';
                                            $esParcial = ($solicitud['tipo_ausencia'] ?? 'COMPLETA') === 'PARCIAL';
                                            $badgeClasses = match ($solicitud['estado']) {
                                                'APROBADO'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'RECHAZADO' => 'bg-red-50 text-red-700 border-red-200',
                                                default     => 'bg-amber-50 text-amber-800 border-amber-200',
                                            };
                                            ?>
                                            <tr role="row" class="hover:bg-slate-50/50 transition">
                                                <td role="cell" data-label="Fecha ausencia" class="px-4 py-3 align-top whitespace-nowrap"><div class="rrhh-cell-value">
                                                    <strong class="text-slate-900 block font-bold">
                                                        <?= e(date('d-m-Y', strtotime((string) $solicitud['fecha_ausencia']))) ?>
                                                    </strong>
                                                    <span class="text-slate-400 text-xs">
                                                        <?= e(date('l', strtotime((string) $solicitud['fecha_ausencia']))) ?>
                                                    </span>
                                                    <?php if ($esParcial): ?>
                                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">PARCIAL</span>
                                                        <div class="text-[11px] text-slate-500 mt-1 space-y-0.5">
                                                            <div>Ausente: <?= e(substr((string) $solicitud['hora_inicio_ausencia'], 0, 5)) ?>–<?= e(substr((string) $solicitud['hora_fin_ausencia'], 0, 5)) ?></div>
                                                            <!-- Mostré ambos tramos y fracciones de hora en ausencias intermedias. -->
                                                            <div>Trabaja: <?= e(permisos_gestion_texto_tramos($solicitud)) ?> (<?= e((string) $solicitud['horas_trabajo_dia']) ?> hrs)</div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Jornada completa</span>
                                                    <?php endif; ?>
                                                </div></td>
                                                <td role="cell" data-label="Recuperación" class="px-4 py-3 align-top whitespace-nowrap"><div class="rrhh-cell-value">
                                                    <?php // Mostré todos los tramos y el saldo efectivo del permiso.
                                                    $permisoPlan=$solicitud; $accionesRecuperacion=false;
                                                    require VIEWS_PATH.'/components/recuperaciones-plan.php'; ?>
                                                </div></td>
                                                <td role="cell" data-label="Motivo" class="px-4 py-3 align-top"><div class="rrhh-cell-value">
                                                    <div class="text-xs text-slate-800 max-w-xs break-words leading-snug">
                                                        <?= e((string) $solicitud['motivo']) ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 mt-1">
                                                        Enviado: <?= e(date('d-m-Y H:i', strtotime((string) $solicitud['created_at']))) ?>
                                                    </div>
                                                </div></td>
                                                <td role="cell" data-label="Estado" class="px-4 py-3 align-top whitespace-nowrap"><div class="rrhh-cell-value">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $badgeClasses ?>">
                                                        <!-- Distinguí observación de pendiente sin alterar el estado histórico. -->
                                                        <?= e(!empty($solicitud['requiere_revision']) && $solicitud['estado'] === 'PENDIENTE' ? 'EN OBSERVACIÓN' : (string) $solicitud['estado']) ?>
                                                    </span>
                                                    <?php if (!empty($solicitud['observacion_solicitud'])): ?>
                                                        <p class="text-xs text-slate-600 whitespace-normal break-words mt-1"><?= e((string) $solicitud['observacion_solicitud']) ?></p>
                                                    <?php endif; ?>
                                                </div></td>
                                                <td role="cell" data-label="Respuesta" class="px-4 py-3 align-top text-xs"><div class="rrhh-cell-value">
                                                    <?php if (!empty($solicitud['respuesta_supervisor'])): ?>
                                                        <div class="font-semibold <?= $solicitud['estado'] === 'APROBADO' ? 'text-emerald-700' : 'text-red-700' ?>">
                                                            <?= e((string) $solicitud['respuesta_supervisor']) ?>
                                                        </div>
                                                        <?php if (!empty($solicitud['fecha_revision'])): ?>
                                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                                Revisado: <?= e(date('d-m-Y H:i', strtotime((string) $solicitud['fecha_revision']))) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 italic">Pendiente de revisión</span>
                                                    <?php endif; ?>
                                                </div></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Agregué editor de tramos local con versión por contenido para evitar JavaScript antiguo. -->
<script src="<?= e(url('assets/js/recuperaciones.js')) ?>?v=<?= e(substr(hash_file('sha256',ASSETS_PATH.'/js/recuperaciones.js'),0,16)) ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------------------------------------------ */
    /* Estado del horario del día seleccionado (llenado por AJAX)         */
    /* ------------------------------------------------------------------ */
    let horarioDia = null;

    const inputFechaAusencia     = document.getElementById('fecha_ausencia');
    const panelHorarioDia        = document.getElementById('panelHorarioDia');
    const txtHorarioJornada      = document.getElementById('txtHorarioJornada');
    const txtColacion            = document.getElementById('txtColacion');
    const alertSinJornada        = document.getElementById('alertSinJornada');
    const seccionAusenciaParcial = document.getElementById('seccionAusenciaParcial');
    const previewParcial         = document.getElementById('previewParcial');
    const errorParcial           = document.getElementById('errorParcial');
    const txtHorasAusencia       = document.getElementById('txtHorasAusencia');
    const txtHorasTrabajo        = document.getElementById('txtHorasTrabajo');
    const txtNuevoHorario        = document.getElementById('txtNuevoHorario');
    const inputInicioAusencia    = document.getElementById('hora_inicio_ausencia');
    const inputFinAusencia       = document.getElementById('hora_fin_ausencia');
    const tipoAusenciaHidden     = document.getElementById('tipoAusenciaHidden');
    const radioCompleta          = document.getElementById('radioCompleta');
    const radioParcial           = document.getElementById('radioParcial');

    const textareaMotivo          = document.getElementById('motivo');
    const contadorBadge           = document.getElementById('contadorPalabras');
    const ayudaPalabras           = document.getElementById('ayudaPalabras');
    const btnEnviar               = document.getElementById('btnEnviar');

    /* ------------------------------------------------------------------ */
    /* 1. Consulta AJAX del horario del día seleccionado                  */
    /* ------------------------------------------------------------------ */
    const consultarHorarioDia = async (fecha) => {
        horarioDia = null;
        panelHorarioDia.classList.add('hidden');
        alertSinJornada.classList.add('hidden');
        seccionAusenciaParcial.classList.add('hidden');

        if (!fecha) return;

        try {
            const resp = await fetch(`<?= e(url('practicante/api/horario-dia.php')) ?>?fecha=${encodeURIComponent(fecha)}`);
            const data = await resp.json();

            if (data.ok) {
                horarioDia = data;
                txtHorarioJornada.textContent = `${data.entrada} – ${data.salida} (${Math.round(data.horas_planificadas)} hrs)`;

                if (data.colacion_inicio && data.colacion_fin) {
                    txtColacion.textContent = `Colación: ${data.colacion_inicio} – ${data.colacion_fin}`;
                } else {
                    txtColacion.textContent = 'Sin colación registrada.';
                }

                panelHorarioDia.classList.remove('hidden');

                inputInicioAusencia.min = data.entrada;
                inputInicioAusencia.max = data.salida;
                inputFinAusencia.min    = data.entrada;
                inputFinAusencia.max    = data.salida;
                if (!inputInicioAusencia.value) inputInicioAusencia.value = data.entrada;
                if (!inputFinAusencia.value)    inputFinAusencia.value    = data.salida;

                if (radioParcial.checked) {
                    seccionAusenciaParcial.classList.remove('hidden');
                    calcularParcial();
                } else {
                    sincronizarMinutosAusencia(Math.round((Number(data.horas_planificadas) || 0) * 60));
                }
            } else {
                sincronizarMinutosAusencia(0);
                alertSinJornada.classList.remove('hidden');
            }
        } catch {
            sincronizarMinutosAusencia(0);
            alertSinJornada.classList.remove('hidden');
        }
    };

    const sincronizarMinutosAusencia = (minutos) => {
        const lista = document.getElementById('recuperacionesFilas');
        if (!lista) return;
        lista.dataset.minutosAusencia = String(Math.max(0, Math.round(minutos || 0)));
        lista.dispatchEvent(new CustomEvent('rrhh:ausencia-cambiada'));
    };

    if (inputFechaAusencia) {
        inputFechaAusencia.addEventListener('change', () => {
            consultarHorarioDia(inputFechaAusencia.value);
        });
        if (inputFechaAusencia.value) {
            consultarHorarioDia(inputFechaAusencia.value);
        }
    }

    /* ------------------------------------------------------------------ */
    /* 2. Toggle Completa / Parcial                                        */
    /* ------------------------------------------------------------------ */
    const actualizarTipoAusencia = () => {
        const esParcial = radioParcial.checked;
        tipoAusenciaHidden.value = esParcial ? 'PARCIAL' : 'COMPLETA';

        if (esParcial && horarioDia) {
            seccionAusenciaParcial.classList.remove('hidden');
            calcularParcial();
        } else {
            seccionAusenciaParcial.classList.add('hidden');
            if (horarioDia) {
                sincronizarMinutosAusencia(Math.round((Number(horarioDia.horas_planificadas) || 0) * 60));
            }
        }
    };

    radioCompleta?.addEventListener('change', actualizarTipoAusencia);
    radioParcial?.addEventListener('change', actualizarTipoAusencia);

    /* ------------------------------------------------------------------ */
    /* 3. Cálculo en tiempo real del tramo parcial                        */
    /* ------------------------------------------------------------------ */
    const toMin = (hhmm) => {
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
    };
    const toHHMM = (min) => {
        const h = Math.floor(min / 60).toString().padStart(2, '0');
        const m = (min % 60).toString().padStart(2, '0');
        return `${h}:${m}`;
    };

    const calcularParcial = () => {
        if (!horarioDia || !inputInicioAusencia.value || !inputFinAusencia.value) {
            previewParcial.classList.add('hidden');
            return;
        }

        const jEntrada = toMin(horarioDia.entrada);
        const jSalida  = toMin(horarioDia.salida);
        const aInicio  = toMin(inputInicioAusencia.value);
        const aFin     = toMin(inputFinAusencia.value);

        errorParcial.classList.add('hidden');

        if (aInicio < jEntrada) {
            errorParcial.textContent = `El inicio de ausencia (${inputInicioAusencia.value}) no puede ser antes de tu entrada (${horarioDia.entrada}).`;
            errorParcial.classList.remove('hidden');
            previewParcial.classList.add('hidden');
            return;
        }
        if (aFin > jSalida) {
            errorParcial.textContent = `El fin de ausencia (${inputFinAusencia.value}) no puede ser después de tu salida (${horarioDia.salida}).`;
            errorParcial.classList.remove('hidden');
            previewParcial.classList.add('hidden');
            return;
        }
        if (aFin <= aInicio) {
            errorParcial.textContent = 'La hora de fin debe ser posterior a la de inicio.';
            errorParcial.classList.remove('hidden');
            previewParcial.classList.add('hidden');
            return;
        }

        let nEntrada, nSalida;
        if (aInicio === jEntrada) {
            nEntrada = aFin;
            nSalida  = jSalida;
        } else {
            nEntrada = jEntrada;
            // Conservé el tramo posterior cuando la ausencia no alcanza la salida final.
            nSalida  = aFin === jSalida ? aInicio : jSalida;
        }

        let minColacion = 0;
        if (horarioDia.colacion_inicio && horarioDia.colacion_fin) {
            const cIni = toMin(horarioDia.colacion_inicio);
            const cFin = toMin(horarioDia.colacion_fin);
            // Desconté solo la intersección real de colación con el horario restante.
            minColacion = Math.max(0, Math.min(cFin, nSalida) - Math.max(cIni, nEntrada));
        }

        let minColAusencia = 0;
        if (horarioDia.colacion_inicio && horarioDia.colacion_fin) {
            const cIni = toMin(horarioDia.colacion_inicio);
            const cFin = toMin(horarioDia.colacion_fin);
            minColAusencia = Math.max(0, Math.min(cFin, aFin) - Math.max(cIni, aInicio));
        }

        // Resté la ausencia interior sin duplicar la porción de colación incluida en ella.
        const minInterrupcion = aInicio > jEntrada && aFin < jSalida ? (aFin - aInicio) - minColAusencia : 0;
        const minTrabajo  = Math.max(0, (nSalida - nEntrada) - minColacion - minInterrupcion);
        const minAusente  = Math.max(0, (aFin - aInicio) - minColAusencia);
        // Conservé fracciones de hora para coincidir con los valores que guarda el servidor.
        const hrsTrabajo  = Math.round(minTrabajo / 60 * 100) / 100;
        const hrsAusente  = Math.round(minAusente / 60 * 100) / 100;

        txtHorasAusencia.textContent  = `${hrsAusente} hr${hrsAusente !== 1 ? 's' : ''}`;
        txtHorasTrabajo.textContent   = `${hrsTrabajo} hr${hrsTrabajo !== 1 ? 's' : ''}`;
        txtNuevoHorario.textContent = aInicio > jEntrada && aFin < jSalida
            ? `${toHHMM(jEntrada)} – ${toHHMM(aInicio)} y ${toHHMM(aFin)} – ${toHHMM(jSalida)}`
            : `${toHHMM(nEntrada)} – ${toHHMM(nSalida)}`;
        previewParcial.classList.remove('hidden');
        if (radioParcial.checked) {
            sincronizarMinutosAusencia(minAusente);
        }
    };

    inputInicioAusencia?.addEventListener('change', calcularParcial);
    inputFinAusencia?.addEventListener('change', calcularParcial);

    /* ------------------------------------------------------------------ */
    /* 4. Recuperación: validación de Sábado y cálculo de horas           */
    /* ------------------------------------------------------------------ */
    // El editor recuperaciones.js calcula el plan completo y consulta sus opciones.


    /* ------------------------------------------------------------------ */
    /* 5. Contador de palabras del motivo                                  */
    /* ------------------------------------------------------------------ */
    const contarPalabras = (texto) => {
        const limpio = texto.trim().replace(/[^\p{L}\p{N}\s]+/gu, ' ');
        if (!limpio) return 0;
        return limpio.split(/\s+/).filter(Boolean).length;
    };

    const actualizarContador = () => {
        if (!textareaMotivo) return;
        const palabras = contarPalabras(textareaMotivo.value);
        contadorBadge.textContent = `${palabras} / 40 palabras`;

        if (palabras > 40) {
            contadorBadge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200';
            ayudaPalabras.textContent = `Has superado el límite por ${palabras - 40} palabra(s). Reduce el texto.`;
            ayudaPalabras.className   = 'text-[11px] text-red-600 font-bold';
            btnEnviar.disabled        = true;
        } else if (palabras >= 35) {
            contadorBadge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200';
            ayudaPalabras.textContent = `Quedan ${40 - palabras} palabra(s) disponible(s).`;
            ayudaPalabras.className   = 'text-[11px] text-amber-600';
            btnEnviar.disabled        = false;
        } else {
            contadorBadge.className = 'px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200';
            ayudaPalabras.textContent = 'Máximo 40 palabras permitidas.';
            ayudaPalabras.className   = 'text-[11px] text-slate-400';
            btnEnviar.disabled        = false;
        }
    };

    textareaMotivo?.addEventListener('input', actualizarContador);
    actualizarContador();
});
</script>

<?php // Agregué sección separada; recuperar permisos no genera crédito excepcional. ?>
<?php if (empty($soloLectura)): ?>
<?php require VIEWS_PATH . '/components/tiempo-excepcional.php'; ?>
<?php endif; ?>
<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
