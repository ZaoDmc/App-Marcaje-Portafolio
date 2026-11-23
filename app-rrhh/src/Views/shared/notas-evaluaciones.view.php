<?php
// Presenté datos ya autorizados y preparados por el controlador, sin consultas desde la vista.
require VIEWS_PATH.'/layout/head.php';
require VIEWS_PATH.'/layout/sidebar.php';
$seccionNotas='evaluaciones'; require VIEWS_PATH.'/components/notas-nav.php';
?>
<div class="rrhh-notas space-y-6">
    <section class="rrhh-card p-4 sm:p-6">
        <h2 class="text-xl font-bold text-slate-900">Evaluaciones semanales</h2>
        <p class="rrhh-help">Selecciona el viernes que evaluarás. Puedes registrar semanas atrasadas cualquier día; guardar un borrador no publica la nota.</p>
    </section>
    <?php if ($mensajeExito!==null): ?><div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="status"><?= e($mensajeExito) ?></div><?php endif; ?>
    <?php if ($errores!==[]): ?><div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (!$migracionPendiente && ($destinatarios!==[] || $seleccionado!==null)): ?>
    <section class="rrhh-card p-4 sm:p-6 space-y-4">
        <?php if ($esAdmin): ?><p class="rrhh-help">El selector muestra solo líderes vigentes. Las notas aprobadas de otros practicantes pueden consultarse en Acompañamiento o Reportes.</p><?php endif; ?>
        <?php if ($destinatarios!==[]): ?>
        <form method="get" action="<?= e(url($rutaNotas)) ?>" class="flex flex-col sm:flex-row items-end gap-3">
            <div class="w-full min-w-0 grow">
                <label class="rrhh-label" for="notas-practica"><?= $esAdmin?'Líder / práctica':'Practicante / práctica' ?></label>
                <select id="notas-practica" name="id_practica" class="rrhh-select" required>
                    <?php // Evité mostrar otro líder como seleccionado al abrir por enlace una nota de un integrante o antiguo líder. ?>
                    <?php if ($esAdmin && !in_array($idPractica,array_map('intval',array_column($destinatarios,'id_practica')),true)): ?><option value="" selected disabled>Seleccionar líder</option><?php endif; ?>
                    <?php foreach ($destinatarios as $persona): ?><option value="<?= (int)$persona['id_practica'] ?>" <?= (int)$persona['id_practica']===$idPractica?'selected':'' ?>><?= e($persona['nombre_1'].' '.$persona['apellido_paterno'].' · #'.$persona['id_practica'].' · '.($persona['area_nombre']??'Sin área').' · '.($persona['es_lider']?'Líder':'Integrante')) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="rrhh-button border-slate-300 text-slate-700">Abrir práctica</button>
        </form>
        <?php elseif ($esAdmin): ?><p class="rrhh-help">No hay líderes vigentes para seleccionar.</p><?php endif; ?>
        <?php if ($seleccionado!==null): ?>
            <?php if ($esAdmin && !(bool)$seleccionado['es_lider']): ?><p class="rrhh-help">Consulta administrativa abierta por enlace directo. Esta práctica no aparece en el selector de líderes.</p><?php endif; ?>
            <p class="text-sm text-slate-600 break-words"><?= e($seleccionado['email']) ?> · Práctica #<?= (int)$seleccionado['id_practica'] ?></p>
            <?php
            // Unifiqué viernes libres y registrados para poder volver a abrir el mismo periodo.
            $opciones=array_fill_keys($pendientes,'Pendiente');
            foreach ($periodos as $periodo) { $opciones[$periodo['viernes']]=$periodo['estado']==='OTRO_RESPONSABLE'?'Gestionada por otro responsable':$periodo['estado']; }
            krsort($opciones);
            ?>
            <?php if ($opciones!==[]): ?>
            <form method="get" action="<?= e(url($rutaNotas)) ?>" class="flex flex-col sm:flex-row items-end gap-3">
                <input type="hidden" name="id_practica" value="<?= (int)$idPractica ?>">
                <div class="w-full min-w-0 grow"><label class="rrhh-label" for="notas-viernes">Viernes evaluado</label>
                    <select id="notas-viernes" name="viernes" class="rrhh-select"><?php foreach ($opciones as $fecha=>$estadoPeriodo): ?><option value="<?= e($fecha) ?>" <?= $fecha===$viernes?'selected':'' ?>><?= e(date('d-m-Y',strtotime($fecha)).' · '.$estadoPeriodo) ?></option><?php endforeach; ?></select>
                </div>
                <button class="rrhh-button border-slate-300 text-slate-700" type="submit">Abrir viernes</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php elseif (!$migracionPendiente && $errores===[]): ?><p class="rrhh-card p-6 text-slate-600"><?= $esAdmin?'No hay líderes vigentes disponibles para evaluar. Revisa las designaciones en Áreas y líderes.':'No hay prácticas disponibles para evaluar.' ?></p><?php endif; ?>

    <?php if ($avisoPeriodo!==''): ?><p class="rrhh-card p-4 text-sm text-slate-600"><?= e($avisoPeriodo) ?></p><?php endif; ?>

    <?php if ($enConfirmacion): ?>
        <?php require VIEWS_PATH.'/components/notas-confirmacion.php'; ?>
    <?php elseif ($rubricaFormulario!==null): ?>
    <section class="rrhh-card p-4 sm:p-6 space-y-4" aria-labelledby="titulo-evaluacion">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h2 id="titulo-evaluacion" class="text-lg font-bold text-slate-900">Evaluación del <?= e(date('d-m-Y',strtotime($viernes))) ?></h2>
                <p class="rrhh-help"><?= e($rubricaFormulario['descripcion']) ?> · <?= count($rubricaFormulario['criterios']) ?> criterios · versión #<?= (int)$rubricaFormulario['id_rubrica'] ?></p>
            </div>
            <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url($rutaNotas.'?id_practica='.(int)$idPractica.'&viernes='.rawurlencode($viernes))) ?>">Recargar evaluación</a>
        </div>
        <?php if ($notaFormulario!==null && $notaFormulario['estado']==='APROBADA' && !$esCorreccion): ?>
            <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url($rutaNotas.'?id_practica='.(int)$idPractica.'&viernes='.rawurlencode($viernes).'&corregir=1')) ?>">Corregir evaluación publicada</a>
        <?php endif; ?>
        <form method="post" action="<?= e(url($rutaNotas)) ?>" class="space-y-5" data-notas-borrador>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_notas_borrador']) ?>">
            <?php // La acción la determina el botón; revisar y guardar borrador son operaciones distintas. ?>
            <input type="hidden" name="modo" value="<?= $esCorreccion?'corregir':'borrador' ?>">
            <input type="hidden" name="estado_esperado" value="<?= e($notaFormulario['estado']??'BORRADOR') ?>">
            <input type="hidden" name="id_practica" value="<?= (int)$idPractica ?>">
            <input type="hidden" name="viernes" value="<?= e($viernes) ?>">
            <input type="hidden" name="revision" value="<?= (int)$revision ?>">
            <input type="hidden" name="id_rubrica" value="<?= (int)$rubricaFormulario['id_rubrica'] ?>">
            <fieldset class="space-y-4 min-w-0" <?= !$editable?'disabled':'' ?>>
                <legend class="rrhh-label">Criterios y calificaciones</legend>
                <?php foreach ($rubricaFormulario['criterios'] as $indice=>$criterio): $posicion=$indice+1; ?>
                    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_10rem] gap-3 items-center border-b border-slate-200 pb-3">
                        <label for="nota-<?= $posicion ?>" class="text-sm text-slate-700 break-words leading-relaxed"><span class="font-bold"><?= $posicion ?>.</span> <?= e($criterio) ?></label>
                        <select id="nota-<?= $posicion ?>" name="notas[<?= $posicion ?>]" class="rrhh-select">
                            <option value="">Sin evaluar</option>
                            <?php for ($medio=2;$medio<=14;$medio++): $valor=number_format($medio/2,1,'.',''); ?>
                            <option value="<?= $valor ?>" <?= isset($valores[$posicion]) && $valores[$posicion]!=='' && (float)$valores[$posicion]===(float)$valor?'selected':'' ?>><?= e(str_replace('.',',',rtrim(rtrim($valor,'0'),'.'))) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="max-w-xs"><label for="nota-final" class="rrhh-label">Nota final manual</label><input id="nota-final" name="nota_final" type="number" min="1" max="7" step="0.01" value="<?= e((string)$notaFinal) ?>" class="rrhh-input"><p class="rrhh-help">No se calcula un promedio automático.</p></div>
                <div><label for="nota-observacion" class="rrhh-label">Observaciones</label><textarea id="nota-observacion" name="observacion" rows="3" maxlength="5000" class="rrhh-input"><?= e($observacion) ?></textarea></div>
                <?php // La aprobación sin cambios no necesita motivo; guardar cambios y corregir siguen validados en servidor. ?>
                <div><label for="nota-motivo" class="rrhh-label">Motivo <?= $esCorreccion?'de la corrección (obligatorio)':'de los cambios' ?></label><textarea id="nota-motivo" name="motivo" rows="2" maxlength="500" class="rrhh-input" <?= $esCorreccion?'required':'' ?>><?= e($motivo) ?></textarea><p class="rrhh-help">Obligatorio al editar, corregir o resolver un borrador sin responsable. Publicar sin cambiar el borrador no requiere motivo.</p></div>
                <?php if ($editable): ?><div class="flex flex-wrap gap-3">
                    <?php if (!$esCorreccion): ?><button class="rrhh-button bg-blue-600 text-white" type="submit" name="accion" value="guardar">Guardar borrador</button><?php endif; ?>
                    <?php if ($notaFormulario!==null && $puedePublicar): ?><button class="rrhh-button border-slate-300 text-slate-700" type="submit" name="accion" value="revisar_publicacion"><?= $esCorreccion?'Revisar corrección':'Revisar y publicar' ?></button><?php elseif ($notaFormulario!==null): ?><p class="rrhh-help">El líder responsable debe aprobar este borrador.</p><?php else: ?><p class="rrhh-help">Guarda primero el borrador para revisar su publicación.</p><?php endif; ?>
                </div><?php endif; ?>
            </fieldset>
        </form>
        <p class="rrhh-help">Esta evaluación conserva sus criterios aunque ADMIN cambie la rúbrica para semanas nuevas.</p>
    </section>
    <?php endif; ?>

    <?php require VIEWS_PATH.'/components/notas-revisiones.php'; ?>

    <?php if ($seleccionado!==null): ?>
    <section class="rrhh-card p-4 sm:p-6 space-y-3">
        <details <?= $rubricaFormulario===null?'open':'' ?>><summary class="rrhh-label cursor-pointer">Viernes pendientes o atrasados (<?= count($pendientes) ?>)</summary>
            <?php if ($pendientes===[]): ?><p class="rrhh-help">No hay viernes faltantes disponibles para tu responsabilidad.</p><?php else: ?>
            <ul class="flex flex-wrap gap-2 mt-3"><?php foreach ($pendientes as $fecha): ?><li><a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url($rutaNotas.'?id_practica='.(int)$idPractica.'&viernes='.$fecha)) ?>"><?= e(date('d-m-Y',strtotime($fecha))) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
        </details>
        <h2 class="text-lg font-bold text-slate-900">Historial semanal</h2>
        <?php if ($periodos===[]): ?><p class="rrhh-help">Todavía no hay evaluaciones registradas.</p><?php else: ?>
        <ul class="space-y-2"><?php foreach ($periodos as $periodo): ?><li class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 py-3 text-sm text-slate-700">
            <span><?= e(date('d-m-Y',strtotime($periodo['viernes']))) ?> · <?= $periodo['estado']==='OTRO_RESPONSABLE'?'Gestionada por otro responsable':e($periodo['estado']) ?></span>
            <?php if ($periodo['id_nota']!==null): ?><a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url($rutaNotas.'?id_practica='.(int)$idPractica.'&viernes='.$periodo['viernes'])) ?>">Abrir evaluación</a><?php endif; ?>
        </li><?php endforeach; ?></ul><?php endif; ?>
    </section>
    <?php endif; ?>
</div>
<?php require VIEWS_PATH.'/layout/foot.php'; ?>
