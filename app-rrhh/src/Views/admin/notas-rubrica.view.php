<?php
// Separé la edición de criterios de las notas; cada guardado genera una versión inmutable nueva.
require VIEWS_PATH.'/layout/head.php'; require VIEWS_PATH.'/layout/sidebar.php';
$seccionNotas='criterios'; require VIEWS_PATH.'/components/notas-nav.php';
?>
<div class="rrhh-notas space-y-6">
    <section class="rrhh-card p-4 sm:p-6"><h2 class="text-xl font-bold text-slate-900">Criterios de evaluación</h2><p class="rrhh-help">Añade o quita filas y escribe qué se evaluará en cada una. La nueva versión se aplicará a evaluaciones nuevas; las anteriores conservarán sus criterios y notas.</p></section>
    <?php if ($mensajeExito!==null): ?><div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="status"><?= e($mensajeExito) ?></div><?php endif; ?>
    <?php if ($errores!==[]): ?><div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($rubricaActual!==null): ?>
    <form method="post" action="<?= e(url('admin/notas-rubrica.php')) ?>" class="rrhh-card p-4 sm:p-6 space-y-5" data-notas-rubrica>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_notas_rubrica']) ?>"><input type="hidden" name="revision" value="<?= (int)$revision ?>">
        <div><label for="rubrica-nombre" class="rrhh-label">Nombre de la versión</label><input class="rrhh-input" id="rubrica-nombre" name="descripcion" maxlength="250" value="<?= e($descripcion) ?>" required></div>
        <div class="space-y-3" data-criterios-lista>
            <?php foreach ($criterios as $indice=>$criterio): ?>
            <div class="border-b border-slate-200 pb-4 space-y-2" data-criterio-fila>
                <label for="criterio-<?= (int)$indice ?>" class="rrhh-label" data-criterio-etiqueta>Criterio <?= (int)$indice+1 ?></label>
                <textarea class="rrhh-input" id="criterio-<?= (int)$indice ?>" name="criterios[]" maxlength="500" rows="2" required><?= e($criterio) ?></textarea>
                <button class="rrhh-button border-red-300 text-red-700" type="submit" name="quitar" value="<?= (int)$indice ?>" formnovalidate data-quitar-criterio>Quitar criterio</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="rrhh-button border-slate-300 text-slate-700" type="submit" name="accion" value="agregar" formnovalidate data-agregar-criterio>Agregar criterio</button>
        <p class="rrhh-help" data-criterios-contador role="status"><?= count($criterios) ?> de 50 criterios. Cada fila tendrá un selector de nota de 1 a 7.</p>
        <div><label for="rubrica-motivo" class="rrhh-label">Motivo del cambio</label><textarea id="rubrica-motivo" name="motivo" maxlength="500" rows="2" class="rrhh-input" required><?= e($motivo) ?></textarea></div>
        <button class="rrhh-button bg-blue-600 text-white" name="accion" value="guardar" type="submit">Guardar nueva versión</button>
    </form>
    <?php // Versioné el script por contenido para que el navegador no reutilice controles anteriores. ?>
    <script src="<?= e(asset('js/notas-rubrica.js').'?v='.substr(hash_file('sha256',ASSETS_PATH.'/js/notas-rubrica.js'),0,16)) ?>" defer></script>
    <?php endif; ?>
</div>
<?php require VIEWS_PATH.'/layout/foot.php'; ?>
