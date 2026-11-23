<?php
// Agregué presentación de líderes con componentes existentes y datos preparados antes del HTML.
require VIEWS_PATH . '/layout/head.php';
require VIEWS_PATH . '/layout/sidebar.php';
?>
<?php // Apliqué las etiquetas semánticas del módulo para conservar contraste también en la gestión de líderes. ?>
<div class="rrhh-notas space-y-6">
    <?php // Integré la gestión de líderes en la navegación del módulo de evaluaciones.
    $seccionNotas='lideres'; require VIEWS_PATH.'/components/notas-nav.php'; ?>
    <div>
        <h1 class="text-xl font-bold text-slate-900">Notas · Líderes por área</h1>
        <p class="text-sm text-slate-600 mt-2">Administra los líderes responsables de las evaluaciones semanales. Accede a Evaluaciones para registrar borradores.</p>
    </div>
    <?php if ($mensajeExito !== null): ?>
        <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="status"><?= e((string)$mensajeExito) ?></div>
    <?php endif; ?>
    <?php if ($errores !== []): ?>
        <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert"><ul>
            <?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if (!$migracionPendiente): ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rrhh-card p-4 sm:p-6 min-w-0">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Áreas y líderes vigentes</h2>
            <?php if ($areas === []): ?>
                <p class="text-sm text-slate-600">No hay áreas registradas. Puedes crearlas desde Catálogos.</p>
            <?php else: ?>
                <div class="table-wrap"><table class="rrhh-table rrhh-table-compact">
                    <thead><tr><th>Área</th><th>Integrantes activos</th><th>Líder</th><th>Acción</th></tr></thead>
                    <tbody><?php foreach ($areas as $area): ?>
                        <tr>
                            <td><?= e($area['nombre']) ?><?php if (!(int)$area['activo']): ?><span class="block text-xs text-slate-500">Inactiva</span><?php endif; ?></td>
                            <td><?= (int)$area['integrantes'] ?></td>
                            <td><?= $area['id_liderazgo'] !== null ? e(trim($area['nombre_1'].' '.$area['apellido_paterno'])) : 'Sin líder' ?></td>
                            <td><a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url('admin/notas.php?id_area='.(int)$area['id_area'])) ?>" <?= $areaSeleccionada !== null && (int)$areaSeleccionada['id_area'] === (int)$area['id_area'] ? 'aria-current="true"' : '' ?>>Gestionar</a></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </section>

        <?php if ($areaSeleccionada !== null): ?>
        <section class="rrhh-card p-4 sm:p-6 min-w-0 space-y-4" aria-labelledby="titulo-gestion-lider">
            <h2 id="titulo-gestion-lider" class="text-lg font-bold text-slate-900"><?= e($areaSeleccionada['nombre']) ?></h2>
            <p class="text-sm text-slate-600">Líder vigente: <strong><?= $areaSeleccionada['id_liderazgo'] !== null ? e(trim($areaSeleccionada['nombre_1'].' '.$areaSeleccionada['apellido_paterno'])) : 'Sin líder' ?></strong></p>
            <?php // Expliqué el requisito de pertenencia cuando el selector está vacío, sin asignar personas automáticamente. ?>
            <?php if ($integrantes === []): ?>
                <div class="rrhh-card p-4 space-y-2" role="status">
                    <p class="text-sm text-slate-700">Esta área no tiene integrantes elegibles para liderarla.</p>
                    <p class="text-sm text-slate-600">Asigna primero al practicante a esta área desde su ficha. Su práctica debe estar activa y pendiente de inicio o en progreso.</p>
                    <a class="rrhh-button border-slate-300 text-slate-700" href="<?= e(url('admin/practicantes.php')) ?>">Asignar integrantes desde Practicantes</a>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('admin/notas.php')) ?>" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_notas_liderazgo']) ?>">
                <input type="hidden" name="id_area" value="<?= (int)$areaSeleccionada['id_area'] ?>">
                <?php // Conservé la designación vista para rechazar formularios que quedaron desactualizados. ?>
                <input type="hidden" name="id_liderazgo_vigente" value="<?= (int)($areaSeleccionada['id_liderazgo'] ?? 0) ?>">
                <!-- Activé la búsqueda solo con JavaScript; sin él permanece el selector nativo completo. -->
                <div data-busqueda-lider hidden>
                    <label for="lider-busqueda" class="rrhh-label">Buscar integrante por nombre, correo o RUT</label>
                    <input id="lider-busqueda" class="rrhh-input" type="search" maxlength="200" placeholder="Escribe para filtrar los integrantes del área" autocomplete="off" aria-controls="lider-practica" aria-describedby="lider-busqueda-estado">
                    <p id="lider-busqueda-estado" class="rrhh-help" role="status" aria-live="polite"></p>
                </div>
                <div>
                    <label for="lider-practica" class="rrhh-label">Practicante del área</label>
                    <select id="lider-practica" name="id_practica" class="rrhh-select" <?= $integrantes === [] ? 'disabled' : '' ?>>
                        <option value="">Seleccionar integrante</option>
                        <?php foreach ($integrantes as $integrante): ?>
                            <?php // Conservé el ID real como valor y escapé el texto visible y el índice de búsqueda. ?>
                            <?php $nombreIntegrante=trim(implode(' ',array_filter([$integrante['nombre_1'],$integrante['nombre_2']??'', $integrante['apellido_paterno'],$integrante['apellido_materno']??''])));
                                $rutIntegrante=trim((string)($integrante['rut_numero']??''));
                                if ($rutIntegrante!=='' && !empty($integrante['rut_dv'])) { $rutIntegrante.='-'.$integrante['rut_dv']; } ?>
                            <option value="<?= (int)$integrante['id_practica'] ?>" data-busqueda="<?= e($nombreIntegrante.' '.$integrante['email'].' '.$rutIntegrante) ?>" <?= (int)$integrante['id_practica'] === $candidatoSeleccionado ? 'selected' : '' ?>><?= e($nombreIntegrante.' · '.$integrante['email'].($rutIntegrante!==''?' · '.$rutIntegrante:'').' · Práctica #'.$integrante['id_practica']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-slate-500 mt-2">Se muestran pertenencias actuales con práctica pendiente de inicio o en progreso.</p>
                </div>
                <div>
                    <label for="lider-motivo" class="rrhh-label">Motivo de la designación, reemplazo o retiro</label>
                    <textarea id="lider-motivo" name="motivo" class="rrhh-input" rows="3" maxlength="500" required><?= e($motivo) ?></textarea>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button data-designar-lider class="rrhh-button bg-blue-600 text-white" type="submit" name="accion" value="designar" <?= !$areaSeleccionada['activo'] || $integrantes === [] ? 'disabled' : '' ?>><?= $areaSeleccionada['id_liderazgo'] !== null ? 'Reemplazar líder' : 'Designar líder' ?></button>
                    <?php if ($areaSeleccionada['id_liderazgo'] !== null): ?>
                        <button class="rrhh-button border-red-300 text-red-700" type="submit" name="accion" value="retirar">Retirar liderazgo</button>
                    <?php endif; ?>
                </div>
            </form>
            <p class="text-xs text-slate-500">Para cambiar de área o dar de baja a un líder, retira primero su designación. El historial se conserva.</p>
            <details>
                <summary class="tiempo-excepcional-toggle rrhh-button border-slate-300 text-slate-700 list-none cursor-pointer">Ver integrantes elegibles (<?= count($integrantes) ?>)</summary>
                <ul class="mt-3 space-y-2 text-sm text-slate-700"><?php foreach ($integrantes as $integrante): ?>
                    <li class="break-words"><?= e($integrante['nombre_1'].' '.$integrante['apellido_paterno'].' · '.$integrante['email']) ?></li>
                <?php endforeach; ?></ul>
            </details>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($areaSeleccionada !== null): ?>
    <section class="rrhh-card p-4 sm:p-6 min-w-0">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Historial de liderazgo · <?= e($areaSeleccionada['nombre']) ?></h2>
        <?php if ($historialLideres === []): ?>
            <p class="text-sm text-slate-600">Esta área todavía no tiene designaciones registradas.</p>
        <?php else: ?>
            <div class="table-wrap"><table class="rrhh-table rrhh-table-compact">
                <thead><tr><th>Líder / práctica</th><th>Vigencia</th><th>Designación</th><th>Cierre</th></tr></thead>
                <tbody><?php foreach ($historialLideres as $registro): ?>
                    <tr>
                        <td><?= e($registro['nombre_1'].' '.$registro['apellido_paterno']) ?><span class="block text-xs text-slate-500">Práctica #<?= (int)$registro['id_practica'] ?></span></td>
                        <td><?= e($registro['fecha_inicio']) ?><span class="block"><?= $registro['fecha_fin'] !== null ? e($registro['fecha_fin']) : 'Vigente' ?></span></td>
                        <td><span class="block text-xs text-slate-500"><?= e($registro['asignado_email']) ?></span><?= e($registro['motivo_designacion']) ?></td>
                        <td><?php if ($registro['fecha_fin'] !== null): ?><span class="block text-xs text-slate-500"><?= e((string)$registro['finalizado_email']) ?></span><?= e((string)$registro['motivo_fin']) ?><?php else: ?>—<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php // Versioné el buscador para evitar reutilizar una copia anterior en el navegador. ?>
<script src="<?= e(asset('js/notas-lideres.js').'?v='.substr(hash_file('sha256',ASSETS_PATH.'/js/notas-lideres.js'),0,12)) ?>" defer></script>
<?php require VIEWS_PATH . '/layout/foot.php'; ?>
