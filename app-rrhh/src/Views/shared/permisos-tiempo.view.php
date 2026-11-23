<?php
// Reutilicé controles y temas de Permisos; la lista recibe solo expedientes autorizados.
$esCategoriaTiempo=$categoriaPermisos==='tiempo';
$paramsTiempo=['categoria'=>$categoriaPermisos,'estado'=>$filtros['estado'],'desde'=>$filtros['desde'],'hasta'=>$filtros['hasta'],'q'=>$filtros['q'],'limite'=>$limiteSeleccionado];
?>
<section class="rrhh-card p-4 mb-6 space-y-4">
    <h2 class="text-lg font-bold"><?= $esCategoriaTiempo?'Reconocimiento de tiempo excepcional':'Canjes de compensación' ?></h2>
    <p class="text-sm"><?= $esCategoriaTiempo?'Revisa el trabajo comprobado y registra el motivo de la autorización del jefe.':'Los canjes de créditos nuevos son confirmados por el practicante. Solo los históricos o reabiertos por cambios de evidencia requieren revisión.' ?></p>
    <form method="get" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <input type="hidden" name="categoria" value="<?= e($categoriaPermisos) ?>">
        <label class="rrhh-label">Buscar practicante<input class="rrhh-input" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nombre, correo o #práctica"></label>
        <label class="rrhh-label">Estado<select class="rrhh-select" name="estado">
            <?php foreach([''=>'Todos','PENDIENTE'=>'Pendiente o en revisión','APROBADO'=>'Aprobado o aplicado','RECHAZADO'=>'Rechazado'] as $valor=>$etiqueta): ?><option value="<?= e($valor) ?>" <?= $filtros['estado']===$valor?'selected':'' ?>><?= e($etiqueta) ?></option><?php endforeach; ?>
        </select></label>
        <label class="rrhh-label">Desde<input class="rrhh-input" type="date" name="desde" value="<?= e($filtros['desde']) ?>"></label>
        <label class="rrhh-label">Hasta<input class="rrhh-input" type="date" name="hasta" value="<?= e($filtros['hasta']) ?>"></label>
        <label class="rrhh-label">Registros por página<select class="rrhh-select" name="limite"><?php foreach($limitesPermitidos as $limite): ?><option <?= $limite===$limiteSeleccionado?'selected':'' ?>><?= (int)$limite ?></option><?php endforeach; ?></select></label>
        <button class="rrhh-button rrhh-btn-primary" type="submit">Filtrar</button>
    </form>
</section>
<section class="rrhh-card p-4 space-y-4" id="listado-permisos">
    <p><?= (int)$bandejaTiempo['total'] ?> registros encontrados</p>
    <div class="overflow-x-auto">
        <table class="rrhh-table w-full">
            <thead><tr><th>Practicante</th><th>Fecha y tramo</th><th>Estado</th><th>Minutos</th><th>Acción</th></tr></thead>
            <tbody>
            <?php foreach($bandejaTiempo['items'] as $fila): ?>
                <?php $credito=$fila['credito'];$registro=$fila['registro'];$idDialogo='permiso-'.$categoriaPermisos.'-'.(int)($registro['id_compensacion']??$registro['id_tiempo']); ?>
                <tr>
                    <td><?= e(trim(($credito['nombre_1']??'').' '.($credito['apellido_paterno']??''))) ?><br><span class="text-xs"><?= e($credito['email']??'') ?> · #<?= (int)$credito['id_practica'] ?></span></td>
                    <td><?= e($fila['fecha']) ?><br><?= e(substr($registro['hora_inicio'],0,5)) ?>–<?= e(substr($registro['hora_fin'],0,5)) ?></td>
                    <td><?= e($registro['estado']) ?><?= $esCategoriaTiempo && $credito['vencido']?' · Saldo vencido':'' ?><?php if(!$esCategoriaTiempo && (int)($credito['politica_canje']??1)===2): ?><br><span class="text-xs">Canje autónomo</span><?php endif; ?></td>
                    <td><?= (int)($esCategoriaTiempo?$credito['minutos_solicitados']:$registro['minutos']) ?></td>
                    <td><button type="button" class="rrhh-button rrhh-btn-secondary rrhh-btn-sm" data-ui-open="<?= e($idDialogo) ?>">Ver detalle</button></td>
                </tr>
            <?php endforeach; ?>
            <?php if($bandejaTiempo['items']===[]): ?><tr><td colspan="5">No hay solicitudes para estos filtros.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="flex flex-wrap gap-3 items-center" aria-label="Paginación de permisos excepcionales">
        <span>Página <?= (int)$bandejaTiempo['pagina'] ?> de <?= (int)$bandejaTiempo['paginas'] ?></span>
        <?php foreach([-1=>'Anterior',1=>'Siguiente'] as $paso=>$texto): ?>
            <?php $pagina=$bandejaTiempo['pagina']+$paso; if($pagina<1 || $pagina>$bandejaTiempo['paginas']){continue;} ?>
            <a class="rrhh-button rrhh-btn-secondary" href="<?= e(url($rutaActual.'?'.http_build_query($paramsTiempo+['pagina'=>$pagina]))) ?>#listado-permisos"><?= e($texto) ?></a>
        <?php endforeach; ?>
    </nav>
</section>
<?php foreach($bandejaTiempo['items'] as $fila): ?>
    <?php
    $credito=$fila['credito'];$registro=$fila['registro'];
    $idDialogo='permiso-'.$categoriaPermisos.'-'.(int)($registro['id_compensacion']??$registro['id_tiempo']);
    // En Compensación mostré únicamente el canje seleccionado y su origen, sin otra aprobación del reconocimiento.
    if(!$esCategoriaTiempo){$credito['compensaciones']=[$registro];}else{$credito['compensaciones']=[];}
    $tiempos=[$credito];$idTituloTiempo=$idDialogo.'-titulo';
    $soloDetalleCompensacion=!$esCategoriaTiempo;
    ?>
    <dialog data-ui-dialog class="rrhh-solicitud-dialog" id="<?= e($idDialogo) ?>" aria-labelledby="<?= e($idTituloTiempo) ?>">
        <div class="rrhh-practicante-modal-header"><strong>Detalle de <?= $esCategoriaTiempo?'tiempo excepcional':'compensación' ?></strong><button type="button" class="rrhh-practicante-modal-close" data-ui-close aria-label="Cerrar detalle">Cerrar</button></div>
        <div class="rrhh-practicante-modal-body"><?php require VIEWS_PATH.'/components/tiempo-excepcional.php'; ?></div>
    </dialog>
    <?php unset($idTituloTiempo,$soloDetalleCompensacion); ?>
<?php endforeach; ?>
