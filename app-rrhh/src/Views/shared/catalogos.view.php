<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 // Trasladé la plantilla desde Models/CatalogoModel.php; recibe los datos preparados por el controlador. ?>


        <?php if ($errores !== []): ?>
            <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($mensajeExito !== null): ?>
            <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="alert"><?= e($mensajeExito) ?></div>
        <?php endif; ?>

        <ul class="rrhh-tabs mb-4">
            <?php foreach ($definiciones as $clave => $definicionTab): ?>
                <li class="">
                    <a class="rrhh-tab <?= $clave === $catalogoActivo ? 'active' : '' ?>" href="<?= e(url('admin/catalogos.php?catalogo=' . $clave)) ?>">
                        <?= e($definicionTab['titulo']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <section class="panel p-4 md:p-6 mb-4">
            <div class="section-title mb-4"><?= $idEditar !== null ? 'Editar' : e($definicion['articulo_nuevo'] ?? 'Nueva') ?> <?= e($definicion['singular']) ?></div>
            <form class="grid grid-cols-12 gap-4 items-end" method="post" action="<?= e(url('admin/catalogos.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="catalogo" value="<?= e($catalogoActivo) ?>">
                <input type="hidden" name="id" value="<?= e((string) ($idEditar ?? '')) ?>">

                <?php foreach ($definicion['campos'] as $campo => $meta): ?>
                    <div class="col-span-12 md:col-span-4">
                        <label class="rrhh-label" for="campo_<?= e($campo) ?>"><?= e($meta['label']) ?></label>
                        <?php if (($meta['tipo'] ?? 'texto') === 'catalogo'): ?>
                            <select class="rrhh-select" id="campo_<?= e($campo) ?>" name="campos[<?= e($campo) ?>]" <?= ($meta['requerido'] ?? false) ? 'required' : '' ?>>
                                <option value="">Seleccionar</option>
                                <?php // El controlador entrega las opciones relacionadas sin consultar desde HTML. ?>
                                <?php foreach ($opcionesPorCampo[$campo] as $idOpcion => $nombreOpcion): ?>
                                    <option value="<?= e((string) $idOpcion) ?>" <?= (string) ($valoresFormulario[$campo] ?? '') === (string) $idOpcion ? 'selected' : '' ?>>
                                        <?= e($nombreOpcion) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (($meta['tipo'] ?? 'texto') === 'fecha'): ?>
                            <!-- Agregué selección de fecha para el calendario común de feriados. -->
                            <input class="rrhh-input" type="date" id="campo_<?= e($campo) ?>" name="campos[<?= e($campo) ?>]" value="<?= e((string) ($valoresFormulario[$campo] ?? '')) ?>" required>
                        <?php elseif (($meta['tipo'] ?? 'texto') === 'entero'): ?>
                            <input class="rrhh-input" type="number" min="<?= e((string) ($meta['min'] ?? 0)) ?>" id="campo_<?= e($campo) ?>" name="campos[<?= e($campo) ?>]" value="<?= e((string) ($valoresFormulario[$campo] ?? '')) ?>" <?= ($meta['requerido'] ?? false) ? 'required' : '' ?>>
                        <?php else: ?>
                            <input class="rrhh-input" id="campo_<?= e($campo) ?>" name="campos[<?= e($campo) ?>]" maxlength="<?= e((string) ($meta['maxlength'] ?? 150)) ?>" value="<?= e((string) ($valoresFormulario[$campo] ?? '')) ?>" <?= ($meta['requerido'] ?? false) ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="col-span-12 flex gap-2">
                    <button class="rrhh-button bg-brand-blue text-white hover:bg-blue-700" type="submit">Guardar</button>
                    <?php if ($idEditar !== null): ?>
                        <a class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100" href="<?= e(url('admin/catalogos.php?catalogo=' . $catalogoActivo)) ?>">Cancelar edición</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel p-4 md:p-6">
            <div class="section-title mb-4"><?= e($definicion['titulo']) ?></div>
            <?php if ($filasPorCatalogo[$catalogoActivo] === []): ?>
                <p class="text-slate-500 mb-0">No hay registros para mostrar.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="rrhh-table rrhh-table-compact align-middle mb-0">
                        <thead>
                            <tr>
                                <?php foreach ($definicion['campos'] as $campo => $meta): ?>
                                    <th><?= e($meta['label']) ?></th>
                                <?php endforeach; ?>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filasPorCatalogo[$catalogoActivo] as $fila): ?>
                                <tr>
                                    <?php foreach ($definicion['campos'] as $campo => $meta): ?>
                                        <td>
                                            <?php if (($meta['tipo'] ?? 'texto') === 'catalogo'): ?>
                                                <?= e((string) ($fila[$campo . '_nombre'] ?? '—')) ?>
                                            <?php else: ?>
                                                <?= e((string) $fila[$campo]) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td>
                                        <span class="rrhh-badge <?= e(rrhh_tone((int) $fila['activo'] === 1 ? 'success' : 'secondary')) ?>">
                                            <?= (int) $fila['activo'] === 1 ? 'SI' : 'NO' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a class="rrhh-button border-blue-600 text-blue-700 hover:bg-blue-50 !px-3 !py-1.5 !text-xs" href="<?= e(url('admin/catalogos.php?catalogo=' . $catalogoActivo . '&editar=' . (int) $fila[$definicion['pk']])) ?>">Editar</a>
                                            <form method="post" action="<?= e(url('admin/catalogos.php')) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="catalogo" value="<?= e($catalogoActivo) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $fila[$definicion['pk']] ?>">
                                                <input type="hidden" name="activo" value="<?= (int) $fila['activo'] === 1 ? '0' : '1' ?>">
                                                <button class="rrhh-button <?= e(rrhh_outline_tone((int) $fila['activo'] === 1 ? 'danger' : 'success')) ?> !px-3 !py-1.5 !text-xs" type="submit">
                                                    <?= (int) $fila['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
