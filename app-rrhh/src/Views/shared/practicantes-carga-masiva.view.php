<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 // Trasladé la plantilla desde Services/CargaMasivaService.php; recibe los datos preparados por el controlador. ?>


                <?php if ($errores !== []): ?>
                    <div class="rrhh-alert border-red-200 bg-red-50 text-red-800" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?= e((string) $error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($catalogos !== null): ?>
                    <div class="form-shell p-4 md:p-6 mb-6">
                        <div class="section-title mb-4">Cargar archivo Excel o CSV</div>
                        <p class="text-slate-500 text-sm mb-4">
                            Descarga la plantilla recomendada en Excel (que incluye <strong>listas desplegables automáticas</strong> para carreras, instituciones, regiones, tipos de práctica, etc.) o en CSV tradicional, complétala y súbela aquí. Máximo <?= e((string) CARGA_MASIVA_MAX_FILAS) ?> practicantes por archivo.
                        </p>
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <a class="rrhh-button bg-emerald-700 text-white hover:bg-emerald-800 !px-3 !py-1.5 !text-xs" href="<?= e(url($actionPath . '?plantilla=1&formato=xlsx')) ?>">
                                <i class="bi bi-file-earmark-excel"></i> Descargar plantilla Excel (.xlsx) <span class="rrhh-badge bg-slate-50 text-slate-900 ml-1">Recomendado</span>
                            </a>
                            <a class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100 !px-3 !py-1.5 !text-xs" href="<?= e(url($actionPath . '?plantilla=1&formato=csv')) ?>">
                                <i class="bi bi-file-earmark-text"></i> Descargar plantilla CSV plano
                            </a>
                        </div>
                        <form method="post" action="<?= e(url($actionPath)) ?>" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_carga_masiva']) ?>">
                            <div class="grid grid-cols-12 gap-4 items-end">
                                <div class="col-span-12 md:col-span-8">
                                    <label class="rrhh-label" for="archivo_csv">Archivo (.xlsx o .csv)</label>
                                    <input class="rrhh-input" type="file" id="archivo_csv" name="archivo_csv" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
                                </div>
                                <div class="col-span-12 md:col-span-4 flex md:justify-end gap-2">
                                    <a class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100" href="<?= e(url($dashboardPath)) ?>">Cancelar</a>
                                    <button class="rrhh-button bg-brand-blue text-white hover:bg-blue-700" type="submit">Cargar practicantes</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="form-shell p-4 md:p-6 mb-6">
                        <button class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100 !px-3 !py-1.5 !text-xs" type="button" data-ui-collapse="valoresPermitidos" aria-expanded="false" aria-controls="valoresPermitidos">
                            <i class="bi bi-list-check"></i> Ver valores permitidos (para evitar errores de tipeo)
                        </button>
                        <div class="mt-4" id="valoresPermitidos" hidden>
                            <p class="text-slate-500 text-sm mb-4">
                                El sistema tolera diferencias de mayúsculas, tildes y espacios, y acepta las siglas/abreviaciones
                                indicadas abajo. Aun así, usar el nombre oficial evita ambigüedades.
                            </p>
                            <div class="grid grid-cols-12 gap-6">
                                <div class="col-span-12 md:col-span-4">
                                    <div class="font-semibold text-sm mb-2">Sexo</div>
                                    <ul class="text-sm mb-0">
                                        <li><code>F</code> (también: Femenino, Mujer)</li>
                                        <li><code>M</code> (también: Masculino, Hombre)</li>
                                        <li><code>O</code> (también: Otro, No binario)</li>
                                    </ul>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="font-semibold text-sm mb-2">Institución</div>
                                    <ul class="text-sm mb-0">
                                        <?php foreach ($catalogos['instituciones'] as $institucion): ?>
                                            <li><?= e((string) $institucion['nombre']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="font-semibold text-sm mb-2">Tipo de práctica</div>
                                    <ul class="text-sm mb-0">
                                        <?php foreach ($catalogos['tipos_practica'] as $tipoPractica): ?>
                                            <li><?= e((string) $tipoPractica['nombre']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <div class="font-semibold text-sm mb-2">Nacionalidad</div>
                                    <ul class="text-sm mb-0">
                                        <?php foreach ($catalogos['nacionalidades'] as $nacionalidad): ?>
                                            <li><?= e((string) $nacionalidad['nombre']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <p class="text-slate-500 text-sm mb-0 mt-1">
                                        Si usas 'Otra', completa también la columna <code>nacionalidad_otra</code>.
                                    </p>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="font-semibold text-sm mb-2">Región (nombre oficial + alias aceptados)</div>
                                    <ul class="text-sm mb-0">
                                        <?php foreach ($catalogos['regiones'] as $region): ?>
                                            <?php
                                            $nombreOficial = (string) $region['nombre_region'];
                                            $normalizadoOficial = practicante_carga_masiva_normalizar_clave($nombreOficial);
                                            $aliasRegion = [];
                                            foreach (practicante_carga_masiva_alias_regiones() as $fragmento => $alias) {
                                                if (str_contains($normalizadoOficial, $fragmento)) {
                                                    $aliasRegion = $alias;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <li>
                                                <?= e($nombreOficial) ?>
                                                <?php if ($aliasRegion !== []): ?>
                                                    <span class="text-slate-500">(también: <?= e(strtoupper(implode(', ', $aliasRegion))) ?>)</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <div class="font-semibold text-sm mb-2">Carrera</div>
                                    <ul class="text-sm mb-0">
                                        <?php foreach ($catalogos['carreras'] as $carrera): ?>
                                            <li><?= e((string) $carrera['nombre']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-span-12">
                                    <div class="font-semibold text-sm mb-2">Sede (por institución)</div>
                                    <?php foreach ($catalogos['instituciones'] as $institucion): ?>
                                        <?php
                                        $sedesInstitucion = array_values(array_filter(
                                            $catalogos['sedes'],
                                            static fn (array $sede): bool => (int) $sede['id_institucion'] === (int) $institucion['id_institucion']
                                        ));
                                        if ($sedesInstitucion === []) {
                                            continue;
                                        }
                                        ?>
                                        <div class="text-sm mb-2">
                                            <span class="font-semibold"><?= e((string) $institucion['nombre']) ?>:</span>
                                            <?= e(implode(', ', array_map(static fn (array $sede): string => (string) $sede['nombre_sede'], $sedesInstitucion))) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-span-12">
                                    <p class="text-slate-500 text-sm mb-0">
                                        <strong>Comuna:</strong> debe corresponder a la región indicada en la misma fila; usa el nombre oficial de la comuna (ej. "Puente Alto", "Providencia").
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($resumen !== null): ?>
                        <div class="rrhh-alert <?= $resumen['fallidos'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>" role="alert">
                            Procesadas <?= e((string) $resumen['total']) ?> filas:
                            <strong><?= e((string) $resumen['creados']) ?></strong> practicantes creados,
                            <strong><?= e((string) $resumen['fallidos']) ?></strong> con errores.
                        </div>

                        <div class="rrhh-table-scroll overflow-x-auto">
                            <table class="rrhh-table rrhh-table-compact align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen['resultados'] as $fila): ?>
                                        <tr>
                                            <td><?= e((string) $fila['numero_fila']) ?></td>
                                            <td><?= e((string) $fila['nombre']) ?></td>
                                            <td><?= e((string) $fila['email']) ?></td>
                                            <td>
                                                <?php if ($fila['ok']): ?>
                                                    <span class="rrhh-badge bg-emerald-100 text-emerald-800">Creado</span>
                                                <?php else: ?>
                                                    <span class="rrhh-badge bg-red-100 text-red-800">Error</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fila['errores'] !== []): ?>
                                                    <ul class="mb-0 text-sm">
                                                        <?php foreach ($fila['errores'] as $errorFila): ?>
                                                            <li><?= e((string) $errorFila) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php elseif (!empty($fila['url_activacion'])): ?>
                                                    <div class="text-sm text-slate-500">
                                                        No se pudo enviar el correo de activación. Enlace manual:
                                                        <input class="rrhh-input !py-1.5 !text-xs mt-1" value="<?= e((string) $fila['url_activacion']) ?>" readonly>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-500 text-sm">Correo de activación enviado.</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
