<?php
// Agregué presentación reutilizable del plan y saldo, sin consultar datos desde la vista.
$tramosPlan=$permisoPlan['recuperaciones']??[];
$validadosPlan=array_sum(array_column($tramosPlan,'minutos_validados'));
$queryStringActual = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$urlConFiltros = !empty($rutaActual) ? url($rutaActual . $queryStringActual) : '#';
$idModalPadre = 'modalSolicitud_' . (int) ($permisoPlan['id_permiso'] ?? 0);
?>
<div class="rrhh-plan-recuperacion space-y-2 whitespace-normal break-words text-xs">
<?php if ($tramosPlan===[]): ?>
    <div><?= e((string)$permisoPlan['fecha_recuperacion']) ?>: <?= e(substr($permisoPlan['hora_inicio_recuperacion'],0,5)) ?>–<?= e(substr($permisoPlan['hora_fin_recuperacion'],0,5)) ?></div>
    <div>Plan anterior: <?= e((string)$permisoPlan['horas_a_recuperar']) ?> hrs</div>
<?php else: ?>
    <?php foreach ($tramosPlan as $tramoPlan): ?>
        <div class="rrhh-tramo-recuperacion border-b border-slate-200 pb-2" data-tramo-id="<?= (int)$tramoPlan['id_recuperacion'] ?>">
            <strong><?= e($tramoPlan['fecha']) ?> · <?= e($tramoPlan['tipo']) ?></strong>
            <div><?= e(substr($tramoPlan['hora_inicio'],0,5)) ?>–<?= e(substr($tramoPlan['hora_fin'],0,5)) ?> · <?= (int)$tramoPlan['minutos_planificados'] ?> min</div>
            <div data-tramo-estado class="<?= $tramoPlan['estado']==='VALIDADA' ? 'text-emerald-700 font-semibold' : '' ?>"><?= e($tramoPlan['estado']) ?> · <?= (int)$tramoPlan['minutos_validados'] ?> min validados</div>
            <?php // Distinguí planes previos de tramos sujetos al corte nuevo. ?>
            <div class="text-slate-600"><?= (int)($tramoPlan['politica_recuperacion']??1)===2?'Recuperación sabatina · corte viernes 12:00':'Plan anterior · conserva su política original' ?></div>
            <?php if (!empty($tramoPlan['motivo_validacion'])): ?><div class="text-slate-600"><?= e($tramoPlan['motivo_validacion']) ?></div><?php endif; ?>
            <?php if (!empty($accionesRecuperacion) && $permisoPlan['estado']==='APROBADO' && $tramoPlan['estado']==='PROPUESTA'): ?>
                <!-- Colapsé la autorización de tramos propuestos en un bloque desplegable para mantener la ficha limpia. -->
                <details class="rrhh-tramo-accion-form rounded-lg border border-slate-200/90 bg-slate-50/70 p-2.5">
                    <summary class="cursor-pointer text-xs font-semibold text-blue-700 hover:text-blue-900 inline-flex items-center gap-1 select-none">
                        <span>Autorizar tramo adicional propuesto...</span>
                    </summary>
                    <form method="post" class="space-y-2 mt-2 pt-2 border-t border-slate-200" action="<?= e($urlConFiltros) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                        <input type="hidden" name="accion" value="autorizar_recuperacion_adicional">
                        <input type="hidden" name="id_permiso" value="<?= (int)$permisoPlan['id_permiso'] ?>">
                        <input type="hidden" name="id_recuperacion" value="<?= (int)$tramoPlan['id_recuperacion'] ?>">
                        <input type="hidden" name="modal_abierto" value="<?= e($idModalPadre) ?>">
                        <label class="block text-xs font-semibold text-slate-700">
                            Motivo de autorización
                            <input class="rrhh-input !py-1.5 !text-xs mt-1" name="motivo_adicional" maxlength="500" required>
                        </label>
                        <?php if($tramoPlan['tipo']==='FERIADO'): ?><label class="block text-xs"><input type="checkbox" name="autorizar_feriados" value="1" required> Autorizo expresamente este feriado</label><?php endif; ?>
                        <button class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" type="submit">Confirmar autorización</button>
                    </form>
                </details>
            <?php endif; ?>
            <?php if (!empty($accionesRecuperacion) && $permisoPlan['estado']==='APROBADO' && $tramoPlan['estado']==='PROGRAMADA' && strtotime($tramoPlan['fecha'].' '.$tramoPlan['hora_fin'])<=time()): ?>
                <!-- Botón de acción directa con mensaje predeterminado automático; sin inputs manuales y con AJAX. -->
                <form method="post" class="rrhh-tramo-accion-form" action="<?= e($urlConFiltros) ?>" data-ajax-validar-recuperacion>
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="accion" value="validar_recuperacion">
                    <input type="hidden" name="id_permiso" value="<?= (int)$permisoPlan['id_permiso'] ?>">
                    <input type="hidden" name="id_recuperacion" value="<?= (int)$tramoPlan['id_recuperacion'] ?>">
                    <input type="hidden" name="modal_abierto" value="<?= e($idModalPadre) ?>">
                    <input type="hidden" name="motivo_validacion" value="Jornada de recuperación validada conforme a marcas registradas.">
                    <button class="rrhh-button rrhh-btn-success rrhh-btn-sm rrhh-btn-validar inline-flex items-center gap-2" type="submit">
                        <?= icono('circle-check') ?><span>Validar asistencia cumplida</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="rrhh-saldo-resumen font-semibold" data-saldo-plan>Recuperado: <?= (int)$validadosPlan ?> min · Pendiente: <?= max(0,(int)round((float)$permisoPlan['horas_ausencia']*60)-$validadosPlan) ?> min</div>
    <?php $librePlan=recuperaciones_saldo_libre($permisoPlan,$tramosPlan); ?>
    <?php if(empty($accionesRecuperacion) && $permisoPlan['estado']==='APROBADO' && $librePlan>0): ?>
        <!-- Permití pedir el saldo realmente libre, sin alterar ni borrar validaciones anteriores. -->
        <details class="mt-2"><summary class="cursor-pointer text-blue-700">Solicitar recuperación del saldo (<?= $librePlan ?> min libres)</summary>
        <form method="post" class="space-y-2 mt-2" action="<?= e(url('practicante/permisos.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="accion" value="solicitar_recuperacion_adicional">
            <input type="hidden" name="id_permiso" value="<?= (int)$permisoPlan['id_permiso'] ?>">
            <label class="block">Fecha<input class="rrhh-input" type="date" name="recuperacion_adicional[fecha]" min="<?= date('Y-m-d') ?>" required></label>
            <?php // Restringí también nuevas propuestas adicionales de permisos antiguos a sábados. ?>
            <p class="text-sm">Solo sábados de 10:00 a 20:00. Solicita antes del viernes previo a las 12:00; después del corte elige el sábado siguiente.</p>
            <label class="block">Tipo<select class="rrhh-select" name="recuperacion_adicional[tipo]" required><option value="SABADO">Sábado</option><option value="FERIADO">Sábado feriado con autorización expresa</option></select></label>
            <label class="block">Inicio<input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 name="recuperacion_adicional[hora_inicio]" required></label>
            <label class="block">Fin<input class="rrhh-input" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 name="recuperacion_adicional[hora_fin]" required></label>
            <label class="block">Motivo<input class="rrhh-input" name="motivo_adicional" maxlength="500" required></label>
            <button class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" type="submit">Solicitar tramo adicional</button>
        </form></details>
    <?php endif; ?>
<?php endif; ?>
</div>
