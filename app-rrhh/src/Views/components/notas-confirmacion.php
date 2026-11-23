<?php // Agregué una revisión previa del contenido exacto; solo el POST confirmado publica la evaluación. ?>
<section class="rrhh-card p-4 sm:p-6 space-y-4" aria-labelledby="titulo-confirmacion-nota" data-notas-confirmacion>
    <h2 id="titulo-confirmacion-nota" class="text-lg font-bold text-slate-900"><?= $esCorreccion?'Revisar corrección':'Revisar publicación' ?></h2>
    <p class="rrhh-help"><?= e($seleccionado['nombre_1'].' '.$seleccionado['apellido_paterno']) ?> · Viernes <?= e(date('d-m-Y',strtotime($viernes))) ?></p>
    <?php if ($prepublicacion['resolucion_excepcional']): ?><p class="rrhh-alert border-amber-200 text-slate-700">Resolución excepcional de ADMIN: el borrador ya no tiene un responsable habilitado para aprobarlo. Se conservarán su autor, criterios e historial.</p><?php endif; ?>
    <dl class="space-y-3"><?php foreach ($rubricaFormulario['criterios'] as $indice=>$criterio): $posicion=$indice+1; ?>
        <div class="flex flex-wrap gap-3 justify-between border-b border-slate-200 pb-3 text-sm text-slate-700"><dt class="min-w-0 break-words grow basis-48"><?= e($criterio) ?></dt><dd class="shrink-0 font-semibold"><?= e(str_replace('.',',',(string)$valores[$posicion])) ?><?php if ($esCorreccion): ?><span class="block rrhh-help">Antes: <?= e(str_replace('.',',',(string)($prepublicacion['anteriores'][$posicion]??'Sin evaluar'))) ?></span><?php endif; ?></dd></div>
    <?php endforeach; ?></dl>
    <p class="text-lg font-bold text-slate-900">Nota final: <?= e(str_replace('.',',',(string)$notaFinal)) ?></p>
    <?php if ($esCorreccion): ?><p class="rrhh-help">Nota final anterior: <?= e(str_replace('.',',',(string)$notaFormulario['nota_final'])) ?></p><?php endif; ?>
    <?php if (trim($observacion)!==''): ?><div><h3 class="rrhh-label">Observaciones que verá el practicante</h3><p class="text-sm text-slate-700 whitespace-pre-wrap break-words"><?= e($observacion) ?></p></div><?php endif; ?>
    <?php if (trim($motivo)!==''): ?><p class="text-sm text-slate-700 whitespace-pre-wrap break-words">Motivo interno: <?= e($motivo) ?></p><?php endif; ?>
    <form method="post" action="<?= e(url($rutaNotas)) ?>" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_notas_borrador']) ?>">
        <input type="hidden" name="id_practica" value="<?= (int)$idPractica ?>"><input type="hidden" name="viernes" value="<?= e($viernes) ?>">
        <input type="hidden" name="revision" value="<?= (int)$revision ?>"><input type="hidden" name="id_rubrica" value="<?= (int)$rubricaFormulario['id_rubrica'] ?>">
        <input type="hidden" name="estado_esperado" value="<?= e($notaFormulario['estado']) ?>">
        <input type="hidden" name="modo" value="<?= $esCorreccion?'corregir':'publicar' ?>">
        <?php foreach ($valores as $posicion=>$valor): ?><input type="hidden" name="notas[<?= (int)$posicion ?>]" value="<?= e((string)$valor) ?>"><?php endforeach; ?>
        <input type="hidden" name="nota_final" value="<?= e((string)$notaFinal) ?>"><input type="hidden" name="observacion" value="<?= e($observacion) ?>"><input type="hidden" name="motivo" value="<?= e($motivo) ?>">
        <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="confirmacion" value="1" class="rrhh-checkbox mt-1" required><span>Confirmo que revisé la evaluación y autorizo su publicación para el practicante.</span></label>
        <div class="flex flex-wrap gap-3"><button class="rrhh-button bg-blue-600 text-white" type="submit" name="accion" value="confirmar_publicacion"><?= $esCorreccion?'Confirmar corrección':'Confirmar publicación' ?></button><button class="rrhh-button border-slate-300 text-slate-700" type="submit" name="accion" value="volver_edicion" formnovalidate>Volver a editar</button></div>
    </form>
</section>
