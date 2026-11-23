<?php
// Mostré únicamente evaluaciones aprobadas y sus criterios de origen, sin motivos ni revisiones internas.
require VIEWS_PATH.'/layout/head.php'; require VIEWS_PATH.'/layout/sidebar.php';
// Incorporé la navegación compartida: la consulta propia permanece disponible aunque no haya liderazgo ni notas publicadas.
$seccionNotas='propias'; require VIEWS_PATH.'/components/notas-nav.php';
?>
<div class="rrhh-notas space-y-6">
    <section class="rrhh-card p-4 sm:p-6"><h2 class="text-xl font-bold text-slate-900">Mis evaluaciones semanales</h2><p class="rrhh-help">Aquí encontrarás tus notas una vez aprobadas. Cada evaluación corresponde al viernes indicado.</p></section>
    <?php if ($errores!==[]): ?><div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($notasPublicadas===[] && $errores===[]): ?><p class="rrhh-card p-6 text-slate-700">Todavía no tienes evaluaciones publicadas.</p><?php endif; ?>
    <?php if ($notasPublicadas!==[]): ?>
    <section class="rrhh-card p-4 sm:p-6 space-y-4">
        <h2 class="text-lg font-bold text-slate-900">Historial semanal publicado</h2>
        <ul class="space-y-3"><?php foreach ($notasPublicadas as $publicada): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 text-sm text-slate-700">
                <div><span class="font-semibold"><?= e(date('d-m-Y',strtotime($publicada['viernes']))) ?></span> · Práctica #<?= (int)$publicada['id_practica'] ?> · Nota <?= e(str_replace('.',',',(string)$publicada['nota_final'])) ?></div>
                <a href="<?= e(url('practicante/mis-notas.php?id_nota='.(int)$publicada['id_nota'])) ?>" class="rrhh-button border-slate-300 text-slate-700" <?= $notaPropia!==null && (int)$notaPropia['id_nota']===(int)$publicada['id_nota']?'aria-current="true"':'' ?>>Ver evaluación</a>
            </li>
        <?php endforeach; ?></ul>
    </section>
    <?php endif; ?>
    <?php if ($notaPropia!==null): ?>
    <section class="rrhh-card p-4 sm:p-6 space-y-4" aria-labelledby="titulo-nota-propia">
        <h2 id="titulo-nota-propia" class="text-lg font-bold text-slate-900">Evaluación del <?= e(date('d-m-Y',strtotime($notaPropia['viernes']))) ?></h2>
        <p class="rrhh-help"><?= e($notaPropia['rubrica']['descripcion']) ?> · Última publicación: <?= e(date('d-m-Y H:i',strtotime($notaPropia['aprobado_en']))) ?></p>
        <dl class="space-y-3"><?php foreach ($notaPropia['rubrica']['criterios'] as $indice=>$criterio): ?>
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-3 text-sm text-slate-700"><dt class="min-w-0 break-words grow basis-48"><?= e($criterio) ?></dt><dd class="font-bold shrink-0"><?= e(str_replace('.',',',(string)($notaPropia['detalle'][$indice+1]??'Sin evaluar'))) ?></dd></div>
        <?php endforeach; ?></dl>
        <p class="text-lg font-bold text-slate-900" data-nota-final-publicada>Nota final: <?= e(str_replace('.',',',(string)$notaPropia['nota_final'])) ?></p>
        <?php if (trim($notaPropia['observacion']??'')!==''): ?><div><h3 class="rrhh-label">Observaciones</h3><p class="text-sm text-slate-700 whitespace-pre-wrap break-words"><?= e($notaPropia['observacion']) ?></p></div><?php endif; ?>
    </section>
    <?php endif; ?>
</div>
<?php require VIEWS_PATH.'/layout/foot.php'; ?>
