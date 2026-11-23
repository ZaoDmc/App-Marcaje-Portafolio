<?php // Mantuve las revisiones internas plegadas y separadas de lo que recibe el practicante evaluado. ?>
<?php if ($historialRevisiones!==[]): ?>
<section class="rrhh-card p-4 sm:p-6" data-notas-revisiones>
    <details><summary class="rrhh-label cursor-pointer">Historial de revisiones (<?= count($historialRevisiones) ?>)</summary>
        <ul class="space-y-4 mt-4"><?php foreach ($historialRevisiones as $registro): ?>
            <li class="border-b border-slate-200 pb-4 space-y-2 text-sm text-slate-700">
                <p class="font-semibold">Revisión <?= (int)$registro['revision'] ?> · <?= e($registro['contenido']['operacion']??'GUARDAR_BORRADOR') ?></p>
                <p class="rrhh-help"><?= e($registro['registrado_en'].' · '.$registro['actor_email']) ?></p>
                <p class="whitespace-pre-wrap break-words"><?= e($registro['motivo']) ?></p>
                <details><summary class="cursor-pointer">Ver calificaciones de esta revisión</summary><dl class="space-y-2 mt-3">
                    <?php foreach (($registro['contenido']['detalle']??[]) as $posicion=>$valor): ?><div class="flex flex-wrap justify-between gap-3"><dt class="grow basis-48 break-words"><?= e($registro['contenido']['criterios'][(int)$posicion-1]??('Evaluación '.$posicion)) ?></dt><dd><?= $valor===null?'Sin evaluar':e(str_replace('.',',',(string)$valor)) ?></dd></div><?php endforeach; ?>
                    <div class="font-semibold">Nota final: <?= e(str_replace('.',',',(string)($registro['contenido']['nota_final']??'Sin evaluar'))) ?></div>
                </dl></details>
            </li>
        <?php endforeach; ?></ul>
    </details>
</section>
<?php endif; ?>
