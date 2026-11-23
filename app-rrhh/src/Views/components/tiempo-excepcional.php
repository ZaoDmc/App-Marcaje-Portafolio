<?php
// Agregué formularios independientes con CSRF y motivos obligatorios, conservando componentes visuales existentes.
$tiempos ??=[];$jornadasTiempo ??=[];$esPracticante=auth_rol_activo()==='PRACTICANTE';$esSupervisor=in_array(auth_rol_activo(),['SUPERVISOR','ADMIN'],true);
// Conservé únicamente los campos del formulario excepcional enviado cuando su validación falla.
$solicitudTiempoEnviada=input('accion')==='solicitar_tiempo';
// Usé un identificador propio cuando el componente aparece en varios detalles emergentes.
$idTituloTiempo=$idTituloTiempo??'titulo-tiempo-excepcional';
?>
<section class="rrhh-card p-4 sm:p-6 my-6 space-y-4" aria-labelledby="<?= e($idTituloTiempo) ?>">
    <h2 id="<?= e($idTituloTiempo) ?>" class="text-lg font-bold text-blue-900">Tiempo excepcional y compensación</h2>
    <?php if($esSupervisor): ?>
        <?php // Compacté la explicación para supervisores y la dejé disponible mediante un desplegable accesible. ?>
        <details>
            <summary class="tiempo-excepcional-toggle rrhh-button list-none cursor-pointer border-slate-300 text-slate-700">
                <?= icono('info') ?> Ver detalles del tiempo excepcional y la compensación
            </summary>
            <div class="mt-3 space-y-3">
                <p class="text-sm text-slate-600">El supervisor registra la autorización del jefe con su motivo. Solo se reconoce trabajo comprobado fuera del turno. Este crédito permite entrar después o salir antes; no aumenta las horas extra acumuladas ni sustituye la recuperación de permisos.</p>
                <p class="text-sm text-slate-600">Los créditos nuevos vencen al terminar el turno del quinto día hábil posterior a la aprobación, según tu horario y excluyendo feriados. El saldo vencido deja de estar disponible; se conserva su historial. Los expedientes anteriores mantienen su política original.</p>
                <p class="text-sm text-slate-600">Si cambian las marcas, revisa primero el trabajo de origen y después el canje. Un canje pasado solo puede revalidarse si ya estaba autorizado antes de comenzar y conserva evidencia compatible. Esto no renueva su vencimiento ni permite usar saldo vencido.</p>
            </div>
        </details>
    <?php else: ?>
    <p class="text-sm text-slate-600">Para solicitar este permiso, primero es necesario haber hecho tiempo extra. Una vez hecho, abajo en "Solicitar reconocimiento de tiempo excepcional trabajado", selecciona la fecha y las horas extra que trabajaste junto al contexto de por qué se realizaron. Esto queda a la espera de una aprobación por parte del supervisor correspondiente. Una vez aprobado, puedes canjear el saldo en el apartado de compensación para entrar más tarde o salir más temprano en un plazo de 5 días hábiles. El canje de un crédito nuevo no requiere otra aprobación. El saldo no canjeado vence y deja de estar disponible, conservando su historial.</p>
    <p class="text-sm text-slate-600">Los créditos nuevos vencen al terminar el turno del quinto día hábil posterior a la aprobación, según tu horario y excluyendo feriados. El saldo vencido deja de estar disponible; se conserva su historial. Los expedientes anteriores mantienen su política original.</p>
    <?php // Expliqué la revisión histórica sin presentar una nueva autorización retroactiva. ?>
    <?php if(!$esPracticante): ?><p class="text-sm text-slate-600">Si cambian las marcas, revisa primero el trabajo de origen y después el canje. Un canje pasado solo puede revalidarse si ya estaba autorizado antes de comenzar y conserva evidencia compatible. Esto no renueva su vencimiento ni permite usar saldo vencido.</p><?php endif; ?>
    <?php endif; ?>
    <?php if($esPracticante): ?>
        <details class="border rounded-lg p-4">
            <summary class="font-semibold cursor-pointer">Solicitar reconocimiento de tiempo excepcional trabajado</summary>
            <?php if($jornadasTiempo===[]): ?>
                <p class="text-sm text-slate-600 mt-3">Todavía no tienes jornadas terminadas creadas bajo la nueva política. Las jornadas y extras anteriores conservan su tratamiento histórico.</p>
            <?php else: ?>
            <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="accion" value="solicitar_tiempo">
                <label class="rrhh-label">Jornada trabajada<select name="id_jornada" class="rrhh-select" required>
                    <?php foreach($jornadasTiempo as $j): ?><option value="<?= (int)$j['id_jornada'] ?>" <?= $solicitudTiempoEnviada && (int)input('id_jornada',0)===(int)$j['id_jornada']?'selected':'' ?>><?= e($j['fecha']) ?></option><?php endforeach; ?>
                </select></label>
                <div class="text-sm text-slate-600">Indica el tramo adicional exacto, entre 10:00 y 20:00, fuera de tu turno ordinario. La salida y el tramo ya deben haber ocurrido; corregir una salida a una hora futura no permite solicitarla anticipadamente.</div>
                <label class="rrhh-label">Desde<input name="hora_inicio_tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 value="<?= e($solicitudTiempoEnviada?(string)input('hora_inicio_tiempo',''):'') ?>" class="rrhh-input" required></label>
                <label class="rrhh-label">Hasta<input name="hora_fin_tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="(1[0-9]:[0-5][0-9]|20:00)" title="Hora entre 10:00 y 20:00, formato 24 h" data-hora24 value="<?= e($solicitudTiempoEnviada?(string)input('hora_fin_tiempo',''):'') ?>" class="rrhh-input" required></label>
                <label class="rrhh-label sm:col-span-2">Motivo y contexto de la autorización del jefe<textarea name="motivo_tiempo" maxlength="500" class="rrhh-input" required><?= e($solicitudTiempoEnviada?(string)input('motivo_tiempo',''):'') ?></textarea></label>
                <button type="submit" class="rrhh-button rrhh-btn-primary">Solicitar tiempo excepcional</button>
            </form>
            <?php endif; ?>
        </details>
    <?php endif; ?>
    <?php if($tiempos===[]): ?><p class="text-sm text-slate-500">No hay solicitudes de tiempo excepcional.</p><?php endif; ?>
    <?php foreach($tiempos as $t): ?>
        <article class="border rounded-lg p-4 space-y-3 break-words" data-tiempo-id="<?= (int)$t['id_tiempo'] ?>">
            <h3 class="font-semibold">Solicitud #<?= (int)$t['id_tiempo'] ?> · <?= e($t['fecha_trabajo']) ?> · <?= e(substr($t['hora_inicio'],0,5)) ?>–<?= e(substr($t['hora_fin'],0,5)) ?></h3>
            <?php if(!$esPracticante): ?><p class="text-sm"><?= e(trim(($t['nombre_1']??'').' '.($t['apellido_paterno']??''))) ?> · Práctica #<?= (int)$t['id_practica'] ?></p><?php endif; ?>
            <p class="text-sm">Estado: <strong><?= e($t['estado']) ?></strong><?= $t['vencido']?' · Saldo vencido':'' ?>. Solicitados: <?= (int)$t['minutos_solicitados'] ?> min · Aprobados: <?= (int)$t['minutos_aprobados'] ?> min · Disponibles: <strong><?= (int)$t['saldo'] ?> min</strong>.</p>
            <?php $venceLocal=(new DateTimeImmutable($t['vence_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(APP_TIMEZONE)); ?>
            <?php // Diferencié el plazo aún no iniciado del vencimiento fijado al aprobar. ?>
            <p class="text-sm text-slate-600"><?php if((int)($t['politica_canje']??1)===2 && empty($t['revisado_por'])): ?>Plazo de canje pendiente de aprobación.<?php else: ?>Vence: <?= e($venceLocal->format('d/m/Y H:i:s')) ?> (<?= e(APP_TIMEZONE) ?>).<?php endif; ?> Motivo: <?= e($t['motivo_solicitud']) ?></p>
            <?php if(!empty($t['motivo_revision'])): ?><p class="text-sm">Revisión: <?= e($t['motivo_revision']) ?></p><?php endif; ?>
            <?php if(!$esPracticante && empty($soloDetalleCompensacion) && in_array($t['estado'],['PENDIENTE','REVISAR'],true)): ?>
                <form method="post" class="space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>"><input type="hidden" name="accion" value="revisar_tiempo"><input type="hidden" name="id_tiempo" value="<?= (int)$t['id_tiempo'] ?>">
                    <label class="rrhh-label">Motivo de la autorización del jefe o del rechazo<textarea name="motivo_tiempo" maxlength="500" class="rrhh-input" required></textarea></label>
                    <label class="rrhh-label">Decisión<select name="decision_tiempo" class="rrhh-select"><option value="APROBAR">Aprobar trabajo comprobado</option><option value="RECHAZAR">Rechazar</option></select></label>
                    <button type="submit" class="rrhh-button rrhh-btn-primary">Revisar tiempo excepcional</button>
                </form>
            <?php endif; ?>
            <?php if($esPracticante && $t['saldo']>0): ?>
                <details class="border rounded-lg p-3"><summary class="font-semibold cursor-pointer">Canjear saldo aprobado</summary>
                    <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                        <input type="hidden" name="csrf_token" value="<?= e($token) ?>"><input type="hidden" name="accion" value="solicitar_compensacion"><input type="hidden" name="id_tiempo" value="<?= (int)$t['id_tiempo'] ?>">
                        <label class="rrhh-label">Día de compensación<input type="date" name="fecha_compensacion" min="<?= e(date('Y-m-d')) ?>" max="<?= e($venceLocal->format('Y-m-d')) ?>" class="rrhh-input" required></label>
                        <label class="rrhh-label">Cómo usarlo<select name="tipo_compensacion" class="rrhh-select"><option value="ENTRADA">Entrar más tarde</option><option value="SALIDA">Salir antes</option></select></label>
                        <label class="rrhh-label">Desde (inicio del tramo que no trabajarás)<input type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="hora_inicio_tiempo" class="rrhh-input" required></label>
                        <label class="rrhh-label">Hasta (fin del tramo que no trabajarás)<input type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="hora_fin_tiempo" class="rrhh-input" required></label>
                        <label class="rrhh-label sm:col-span-2">Motivo<textarea name="motivo_tiempo" maxlength="500" class="rrhh-input" required></textarea></label>
                        <button type="submit" class="rrhh-button rrhh-btn-primary"><?= (int)($t['politica_canje']??1)===2?'Confirmar canje':'Solicitar compensación' ?></button>
                    </form>
                </details>
            <?php endif; ?>
            <?php foreach($t['compensaciones'] as $c): ?>
                <div class="bg-slate-50 border rounded-lg p-3 space-y-2" data-compensacion-id="<?= (int)$c['id_compensacion'] ?>">
                    <p class="text-sm font-semibold">Compensación #<?= (int)$c['id_compensacion'] ?> · <?= e($c['fecha']) ?> · <?= e($c['tipo']==='ENTRADA'?'Entrar después':'Salir antes') ?> · <?= e(substr($c['hora_inicio'],0,5)) ?>–<?= e(substr($c['hora_fin'],0,5)) ?></p>
                    <p class="text-sm"><?= e($c['estado']) ?> · Reservados <?= (int)$c['minutos'] ?> min · Aplicados <?= (int)$c['minutos_aplicados'] ?> min. <?= e($c['motivo_solicitud']) ?></p>
                    <?php if(!empty($c['motivo_revision'])): ?><p class="text-sm">Revisión: <?= e($c['motivo_revision']) ?></p><?php endif; ?>
                    <?php if(!$esPracticante && in_array($c['estado'],['PENDIENTE','REVISAR'],true)): ?>
                        <form method="post" class="space-y-2">
                            <input type="hidden" name="csrf_token" value="<?= e($token) ?>"><input type="hidden" name="accion" value="revisar_compensacion"><input type="hidden" name="id_compensacion" value="<?= (int)$c['id_compensacion'] ?>">
                            <label class="rrhh-label">Motivo de revisión<textarea name="motivo_tiempo" maxlength="500" class="rrhh-input" required></textarea></label>
                            <label class="rrhh-label">Decisión<select name="decision_tiempo" class="rrhh-select"><option value="APROBAR">Aprobar compensación</option><option value="RECHAZAR">Rechazar y liberar reserva</option></select></label>
                            <button type="submit" class="rrhh-button rrhh-btn-primary">Revisar compensación</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </article>
    <?php endforeach; ?>
</section>
