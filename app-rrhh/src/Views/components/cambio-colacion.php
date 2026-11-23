<?php
// Reutilicé componentes temáticos y diálogos, con datos ya preparados por el controlador.
$colacionPropia=auth_rol_activo()==='PRACTICANTE';
?>
<section class="rrhh-card p-4 sm:p-6 space-y-4" aria-labelledby="cambio-colacion-titulo">
    <h2 id="cambio-colacion-titulo" class="text-lg font-bold">Cambio de colación</h2>
    <p class="text-sm">Cambia solo la colación de hoy entre 13:00–14:00 y 14:00–15:00, manteniendo una hora de descanso. Requiere aprobación de ADMIN o SUPERVISOR, que puede revisarla después de terminada la jornada. No cambia tu horario semanal ni genera horas de recuperación.</p>
    <?php if($colacionPropia): ?>
        <form method="post" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>"><input type="hidden" name="accion" value="solicitar_colacion">
            <label class="rrhh-label">Colación solicitada para hoy<select class="rrhh-select" name="inicio_colacion" required><option value="13:00">13:00–14:00</option><option value="14:00">14:00–15:00</option></select></label>
            <label class="rrhh-label">Motivo<textarea class="rrhh-input" name="motivo_colacion" maxlength="500" required></textarea></label>
            <button type="submit" class="rrhh-button rrhh-btn-primary">Solicitar cambio de colación</button>
        </form>
    <?php else: ?>
        <form method="get" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input type="hidden" name="categoria" value="colacion">
            <label class="rrhh-label">Buscar practicante<input class="rrhh-input" name="q" value="<?= e($filtros['q']) ?>"></label>
            <label class="rrhh-label">Estado<select class="rrhh-select" name="estado"><?php foreach([''=>'Todos','PENDIENTE'=>'Pendiente','APROBADO'=>'Aprobado','RECHAZADO'=>'Rechazado'] as $v=>$texto): ?><option value="<?= e($v) ?>" <?= $v===$filtros['estado']?'selected':'' ?>><?= e($texto) ?></option><?php endforeach; ?></select></label>
            <label class="rrhh-label">Desde<input type="date" class="rrhh-input" name="desde" value="<?= e($filtros['desde']) ?>"></label>
            <label class="rrhh-label">Hasta<input type="date" class="rrhh-input" name="hasta" value="<?= e($filtros['hasta']) ?>"></label>
            <button type="submit" class="rrhh-button rrhh-btn-primary">Filtrar</button>
        </form>
    <?php endif; ?>
    <div class="overflow-x-auto"><table class="rrhh-table w-full"><thead><tr><th>Fecha</th><?php if(!$colacionPropia): ?><th>Practicante</th><?php endif; ?><th>Colación solicitada</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>
    <?php foreach($solicitudesColacion as $c): ?>
        <tr><td><?= e($c['fecha']) ?></td><?php if(!$colacionPropia): ?><td><?= e($c['nombre_1'].' '.$c['apellido_paterno']) ?></td><?php endif; ?><td><?= e(substr($c['inicio_solicitado'],0,5)) ?>–<?= e(colacion_fin(substr($c['inicio_solicitado'],0,5))) ?></td><td><?= e($c['estado']) ?></td><td><button type="button" class="rrhh-button rrhh-btn-secondary" data-ui-open="colacion-<?= (int)$c['id_solicitud'] ?>">Ver detalle</button></td></tr>
    <?php endforeach; ?>
    <?php if($solicitudesColacion===[]): ?><tr><td colspan="5">No hay solicitudes de colación.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php if(!$colacionPropia): ?>
        <nav class="flex gap-3" aria-label="Paginación de colación"><span>Página <?= (int)$colacionPagina ?> de <?= (int)$colacionPaginas ?></span>
        <?php foreach([-1=>'Anterior',1=>'Siguiente'] as $paso=>$texto): ?><?php $pagina=$colacionPagina+$paso;if($pagina<1 || $pagina>$colacionPaginas){continue;} ?><a class="rrhh-button rrhh-btn-secondary" href="<?= e(url($rutaActual.'?'.http_build_query($filtros+['categoria'=>'colacion','pagina'=>$pagina,'limite'=>$limiteSeleccionado]))) ?>"><?= e($texto) ?></a><?php endforeach; ?></nav>
    <?php endif; ?>
</section>
<?php foreach($solicitudesColacion as $c): ?>
<dialog data-ui-dialog class="rrhh-solicitud-dialog" id="colacion-<?= (int)$c['id_solicitud'] ?>" aria-labelledby="colacion-titulo-<?= (int)$c['id_solicitud'] ?>">
    <div class="rrhh-practicante-modal-header"><h2 id="colacion-titulo-<?= (int)$c['id_solicitud'] ?>">Cambio de colación · <?= e($c['fecha']) ?></h2><button type="button" class="rrhh-practicante-modal-close" data-ui-close>Cerrar</button></div>
    <div class="rrhh-practicante-modal-body space-y-3">
        <p>Anterior: <?= e(substr($c['inicio_anterior'],0,5)) ?>–<?= e(colacion_fin(substr($c['inicio_anterior'],0,5))) ?> · Solicitada: <?= e(substr($c['inicio_solicitado'],0,5)) ?>–<?= e(colacion_fin(substr($c['inicio_solicitado'],0,5))) ?></p>
        <p>Motivo: <?= e($c['motivo']) ?></p><p>Estado: <?= e($c['estado']) ?></p>
        <?php if(!empty($c['motivo_revision'])): ?><p>Revisión: <?= e($c['motivo_revision']) ?></p><?php endif; ?>
        <?php if(array_key_exists('aplica_actualmente',$c) && !$c['aplica_actualmente']): ?><p>El horario o calendario cambió después de aprobarse: este ajuste ya no coincide con la jornada actual. Consulta a RRHH.</p><?php endif; ?>
        <?php if(!$colacionPropia && $c['estado']==='PENDIENTE'): ?>
            <form method="post" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>"><input type="hidden" name="accion" value="revisar_colacion"><input type="hidden" name="id_solicitud" value="<?= (int)$c['id_solicitud'] ?>">
                <label class="rrhh-label">Decisión<select class="rrhh-select" name="decision_colacion"><option value="APROBAR">Aprobar</option><option value="RECHAZAR">Rechazar</option></select></label>
                <label class="rrhh-label">Motivo de revisión<textarea name="motivo_colacion" class="rrhh-input" maxlength="500" required></textarea></label>
                <button type="submit" class="rrhh-button rrhh-btn-primary">Resolver solicitud</button>
            </form>
        <?php endif; ?>
    </div>
</dialog>
<?php endforeach; ?>
