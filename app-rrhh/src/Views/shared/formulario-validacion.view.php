<?php
require VIEWS_PATH . '/layout/head.php';
require VIEWS_PATH . '/layout/sidebar.php';
?>
<section id="formulario-validacion" class="mx-auto grid w-full max-w-7xl gap-5 p-4 lg:p-8">
    <header class="rrhh-card flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between md:p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Formulario de validación
            </h1>
            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Completa los antecedentes de práctica, administra los datos del centro y consulta los documentos generados.
            </p>
        </div>
    </header>

    <?php if (!$dbDisponible): ?>
        <div class="rrhh-alert flex items-start gap-3 border-amber-300 bg-amber-50 text-amber-900" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i>
            <span>El módulo requiere la migración de base de datos 012. Solicita que se aplique antes de continuar.</span>
        </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="rrhh-alert flex items-start gap-3 border-red-300 bg-red-50 text-red-900" role="alert">
            <i class="bi bi-exclamation-circle-fill mt-0.5" aria-hidden="true"></i>
            <ul class="mb-0 list-disc pl-4">
                <?php foreach ($errores as $error): ?>
                    <li><?= e((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito !== null): ?>
        <div class="rrhh-alert flex items-center gap-3 border-emerald-300 bg-emerald-50 text-emerald-900" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= e($mensajeExito) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($generadoId !== '' && ctype_digit($generadoId)): ?>
        <div class="rrhh-alert flex flex-wrap items-center gap-3 border-emerald-300 bg-emerald-50 text-emerald-900" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>Documento generado correctamente.</span>
            <a class="rrhh-button rrhh-btn-success-soft" href="<?= e(url('formulario-validacion-descarga.php?id=' . $generadoId)) ?>">
                <i class="bi bi-download" aria-hidden="true"></i>
                Descargar DOCX
            </a>
        </div>
    <?php endif; ?>

    <nav
    class="rrhh-permisos-tabs inline-flex w-full flex-wrap items-center gap-1.5 rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5"
    aria-label="Secciones de formulario validación"
>
    <?php foreach ([
        'generar' => [
            'etiqueta' => 'Generar documento',
            'icono' => 'bi-file-earmark-plus',
        ],
        'configuracion' => [
            'etiqueta' => 'Datos del centro',
            'icono' => 'bi-building',
        ],
        'carreras' => [
            'etiqueta' => 'Funciones por carrera',
            'icono' => 'bi-list-check',
        ],
        'historial' => [
            'etiqueta' => 'Documentos generados',
            'icono' => 'bi-folder2-open',
        ],
    ] as $tab => $seccion): ?>
        <?php $esActiva = $activeTab === $tab; ?>
        <a
            class="rrhh-permisos-tab rrhh-permisos-tab--all inline-flex items-center gap-2 rounded-xl border border-transparent px-3.5 py-2 text-xs font-semibold"
            href="<?= e(url('formulario-validacion.php?tab=' . $tab)) ?>"
            <?= $esActiva ? 'aria-current="page"' : '' ?>
        >
            <i class="bi <?= e($seccion['icono']) ?> rrhh-permisos-tab-icon" aria-hidden="true"></i>
            <span><?= e($seccion['etiqueta']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

    <?php if ($activeTab === 'generar'): ?>
        <section class="rrhh-card space-y-5 p-4 md:p-6">
            <h3 class="text-lg font-bold tracking-tight">
                Generar documento<?= $institucionSeleccionada !== null ? ' — ' . e((string) $institucionSeleccionada['nombre']) : '' ?>
            </h3>
            <p class="mb-5 text-sm text-slate-600 dark:text-slate-300">
                Sube el archivo que completó el estudiante. El original se procesa temporalmente y no se conserva.
                Los antecedentes personales permanecen en el documento; la aplicación guarda de forma privada la copia generada.
                Solo se pueden generar documentos para instituciones con una plantilla habilitada.
            </p>

            <?php if (!$dbDisponible): ?>
                <p class="text-sm text-slate-600 dark:text-slate-300">La generación estará disponible después de aplicar la migración 012.</p>
            <?php elseif ($instituciones === []): ?>
                <p class="rrhh-alert border-amber-300 bg-amber-50 text-amber-900">No hay instituciones activas disponibles en el catálogo.</p>
            <?php elseif ($carreras === []): ?>
                <p class="rrhh-alert border-amber-300 bg-amber-50 text-amber-900">No hay carreras activas disponibles en el catálogo.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('formulario-validacion.php?tab=generar')) ?>" enctype="multipart/form-data" class="grid grid-cols-12 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="accion" value="generar">
                    <input type="hidden" name="tab" value="generar">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= FORMULARIO_VALIDACION_MAX_BYTES ?>">

                    <div class="col-span-12">
                        <label class="rrhh-label" for="id_institucion">Institución</label>
                        <select class="rrhh-select" id="id_institucion" name="id_institucion" required>
                            <option value="">Selecciona una institución</option>
                            <?php foreach ($instituciones as $institucion): ?>
                                <?php $institucionHabilitada = formulario_validacion_institucion_codigo($institucion) !== null; ?>
                                <option
                                    value="<?= e((string) $institucion['id_institucion']) ?>"
                                    <?= $institucionHabilitada ? '' : 'disabled' ?>
                                    <?= $institucionSeleccionada !== null && (int) $institucionSeleccionada['id_institucion'] === (int) $institucion['id_institucion'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $institucion['nombre']) ?><?= $institucionHabilitada ? '' : ' (próximamente)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-12">
                        <label class="rrhh-label" for="docx">DOCX completado por el estudiante (máximo 5 MB)</label>
                        <input class="rrhh-input" type="file" id="docx" name="docx" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="rrhh-label" for="id_carrera">Carrera</label>
                        <select class="rrhh-select" id="id_carrera" name="id_carrera" required>
                            <option value="">Selecciona una carrera</option>
                            <?php foreach ($carreras as $carrera): ?>
                                <option value="<?= e((string) $carrera['id_carrera']) ?>" <?= $valores['id_carrera'] === (string) $carrera['id_carrera'] ? 'selected' : '' ?>>
                                    <?= e((string) $carrera['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="rrhh-label" for="fecha_inicio">Inicio de práctica</label>
                        <input class="rrhh-input" type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e($valores['fecha_inicio']) ?>" required>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="rrhh-label" for="fecha_termino">Término de práctica</label>
                        <input class="rrhh-input" type="date" id="fecha_termino" name="fecha_termino" value="<?= e($valores['fecha_termino']) ?>" required>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="rrhh-label" for="horas_totales">Número total de horas</label>
                        <input class="rrhh-input" type="number" id="horas_totales" name="horas_totales" min="1" max="99999" value="<?= e($valores['horas_totales']) ?>" required>
                    </div>
                    <div class="col-span-12 md:col-span-9">
                        <label class="rrhh-label" for="horario_trabajo">Horario de trabajo</label>
                        <input class="rrhh-input" type="text" id="horario_trabajo" name="horario_trabajo" maxlength="300" value="<?= e($valores['horario_trabajo']) ?>" required>
                    <p id="horario-trabajo-ayuda" class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        Indica los días y las horas de inicio y término en formato de 24 horas.
                        Por ejemplo: lunes a viernes, de 10:00 a 18:00.
                    </p>
                    </div>
                    <div class="col-span-12">
                        <fieldset aria-describedby="horario-variable-ayuda">
                            <legend class="rrhh-label">
                                ¿El horario de práctica es variable?
                            </legend>

                            <div class="mt-2 flex gap-6">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="horario_variable"
                                        value="Sí"
                                        required
                                        <?= $valores['horario_variable'] === 'Sí' ? 'checked' : '' ?>
                                    >
                                    <span>Sí</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="horario_variable"
                                        value="No"
                                        required
                                        <?= $valores['horario_variable'] === 'No' ? 'checked' : '' ?>
                                    >
                                    <span>No</span>
                                </label>
                            </div>
                            <p id="horario-variable-ayuda" class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Selecciona «Sí» si los días u horas de práctica cambian; de lo contrario, selecciona «No».
                            </p>
                        </fieldset>
                    </div>
                    <div class="col-span-12">
                        <label class="rrhh-label" for="unidad">Departamento, unidad o sección</label>
                        <textarea class="rrhh-input min-h-20" id="unidad" name="unidad" maxlength="2000" required><?= e($valores['unidad']) ?></textarea>
                    </div>
                    <div class="col-span-12">
                        <label class="rrhh-label" for="funciones">Funciones principales</label>
                        <textarea class="rrhh-input min-h-36" id="funciones" name="funciones" maxlength="8000" data-autosize required><?= e($valores['funciones']) ?></textarea>
                    </div>
                    <div class="col-span-12 flex flex-wrap items-center gap-3">
                        <button class="rrhh-button rrhh-btn-primary" type="submit">Generar DOCX</button>
                        <a class="rrhh-button rrhh-btn-secondary" href="<?= e(url('formulario-validacion.php?tab=configuracion')) ?>">Revisar datos del centro</a>
                    </div>
                </form>
                <script>
                    (() => {
                        const templates = <?= json_encode($plantillasPorCarrera, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
                        const select = document.getElementById('id_carrera');
                        const unit = document.getElementById('unidad');
                        const functions = document.getElementById('funciones');

                        const ajustarAltura = (textarea) => {
                            if (!textarea) return;
                            textarea.style.height = 'auto';
                            textarea.style.height = `${textarea.scrollHeight}px`;
                            textarea.style.overflowY = 'hidden';
                        };        
                        
                        functions?.addEventListener('input', () => ajustarAltura(functions));
                        ajustarAltura(functions);

                        select?.addEventListener('change', () => {
                            const template = templates[select.value];
                            if (!template) return;
                            unit.value = template.unidad;
                            functions.value = template.funciones;
                            ajustarAltura(functions);
                        });
                    })();
                </script>
            <?php endif; ?>
        </section>
    <?php elseif ($activeTab === 'configuracion'): ?>
        <section class="panel p-4 md:p-6">
            <h3 class="section-title mb-2">Datos compartidos del centro de práctica</h3>
            <p class="mb-5 text-sm text-slate-600 dark:text-slate-300">Estos valores se aplican a futuras generaciones. No modifican documentos guardados.</p>
            <form method="post" action="<?= e(url('formulario-validacion.php?tab=configuracion')) ?>" class="grid grid-cols-12 gap-4">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="accion" value="guardar_configuracion">
                <input type="hidden" name="tab" value="configuracion">
                <?php foreach ([
                    'nombre_centro' => ['Nombre del centro', 150],
                    'rut_centro' => ['RUT del centro', 20],
                    'direccion' => ['Dirección', 250],
                    'comuna' => ['Comuna', 150],
                    'giro_comercial' => ['Giro comercial', 250],
                    'telefono' => ['Teléfono', 30],
                    'sitio_web' => ['Sitio web', 255],
                    'jefe_nombre' => ['Nombre del jefe directo', 150],
                    'jefe_cargo' => ['Cargo del jefe directo', 150],
                    'jefe_email' => ['Correo del jefe directo', 190],
                ] as $campo => [$label, $maxlength]): ?>
                    <div class="col-span-12 md:col-span-6">
                        <label class="rrhh-label" for="<?= e($campo) ?>"><?= e($label) ?></label>
                        
                        <?php if ($campo === 'sitio_web'): ?>
                            <input
                                class="rrhh-input"
                                id="<?= e($campo) ?>"
                                name="<?= e($campo) ?>"
                                maxlength="<?= $maxlength ?>"
                                value="<?= e((string) ($configuracion[$campo] ?? input($campo, ''))) ?>"
                                placeholder="ejemplo.cl"
                                aria-describedby="sitio-web-ayuda"
                                required
                            >
                        <?php else: ?>
                            <input class="rrhh-input" id="<?= e($campo) ?>" name="<?= e($campo) ?>" maxlength="<?= $maxlength ?>" value="<?= e((string) ($configuracion[$campo] ?? input($campo, ''))) ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="col-span-12">
                    <label class="rrhh-label" for="beneficios">Beneficios asociados</label>
                    <textarea class="rrhh-input min-h-28" id="beneficios" name="beneficios" maxlength="3000" required><?= e((string) ($configuracion['beneficios'] ?? input('beneficios', ''))) ?></textarea>
                </div>
                <div class="col-span-12"><button class="rrhh-button rrhh-btn-primary" type="submit">Guardar datos del centro</button></div>
            </form>
        </section>
    <?php elseif ($activeTab === 'carreras'): ?>
        <section class="panel p-4 md:p-6">
            <h3 class="section-title mb-2">Funciones y unidad sugeridas por carrera</h3>
            <p class="mb-5 text-sm text-slate-600 dark:text-slate-300">Guarda textos base. En cada generación se pueden revisar y editar antes de completar el DOCX.</p>
            <?php if ($carreras === []): ?>
                <p class="text-sm text-slate-600 dark:text-slate-300">No hay carreras activas disponibles en el catálogo.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('formulario-validacion.php?tab=carreras')) ?>" class="grid grid-cols-12 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="accion" value="guardar_plantilla_carrera">
                    <input type="hidden" name="tab" value="carreras">
                    <div class="col-span-12 md:col-span-5">
                        <label class="rrhh-label" for="plantilla_id_carrera">Carrera</label>
                        <select class="rrhh-select" id="plantilla_id_carrera" name="id_carrera" required>
                            <option value="">Selecciona una carrera</option>
                            <?php foreach ($carreras as $carrera): ?>
                                <option value="<?= e((string) $carrera['id_carrera']) ?>" <?= (string) input('id_carrera', '') === (string) $carrera['id_carrera'] ? 'selected' : '' ?>><?= e((string) $carrera['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-span-12">
                        <label class="rrhh-label" for="plantilla_unidad">Unidad sugerida</label>
                        <textarea class="rrhh-input min-h-20" id="plantilla_unidad" name="unidad" maxlength="2000" required><?= e((string) input('unidad', '')) ?></textarea>
                    </div>
                    <div class="col-span-12">
                        <label class="rrhh-label" for="plantilla_funciones">Funciones sugeridas</label>
                        <textarea class="rrhh-input min-h-36" id="plantilla_funciones" name="funciones" maxlength="8000" data-autosize required><?= e((string) input('funciones', '')) ?></textarea>
                    </div>
                    <div class="col-span-12"><button class="rrhh-button rrhh-btn-primary" type="submit">Guardar sugerencias</button></div>
                </form>
            <?php endif; ?>
            <div class="mt-6 grid gap-3">
                <?php foreach ($plantillas as $plantilla): ?>
                    <article class="rrhh-card p-4">
                        <h4 class="font-bold"><?= e((string) $plantilla['carrera']) ?></h4>
                        <p class="mt-2 text-sm"><strong>Unidad:</strong> <?= nl2br(e((string) $plantilla['unidad'])) ?></p>
                        <p class="mt-2 text-sm"><strong>Funciones:</strong><br><?= nl2br(e((string) $plantilla['funciones'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <script>
            (() => {
                const templates = <?= json_encode($plantillasPorCarrera, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
                const select = document.getElementById('plantilla_id_carrera');
                const unit = document.getElementById('plantilla_unidad');
                const functions = document.getElementById('plantilla_funciones');

                const ajustarAltura = (textarea) => {
                    if (!textarea) return;
                    textarea.style.height = 'auto';
                    textarea.style.height = `${textarea.scrollHeight}px`;
                    textarea.style.overflowY = 'hidden';
                };

                functions?.addEventListener('input', () => ajustarAltura(functions));
                ajustarAltura(functions);

                select?.addEventListener('change', () => {
                    const template = templates[select.value];
                    unit.value = template?.unidad ?? '';
                    functions.value = template?.funciones ?? '';
                    ajustarAltura(functions);
                });
            })();
        </script>
    <?php else: ?>
        <section class="panel p-4 md:p-6">
            <h3 class="text-lg font-bold tracking-tight">Documentos generados</h3>
            <?php if ($historial === []): ?>
                <p class="text-sm text-slate-600 dark:text-slate-300">Todavía no hay documentos generados.</p>
            <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="rrhh-table rrhh-table-compact rrhh-table-hover align-middle">
                        <thead>
                            <tr><th>Generado</th><th>Carrera</th><th>Creado por</th><th>Tamaño</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $documento): ?>
                                <tr>
                                    <td><?= e((string) $documento['created_at']) ?></td>
                                    <td><?= e((string) $documento['carrera']) ?></td>
                                    <td><?= e((string) $documento['creado_por']) ?></td>
                                    <td><?= e(number_format((int) $documento['tamano_bytes'] / 1024, 1, ',', '.')) ?> KB</td>
                                    <td><a class="font-bold underline" href="<?= e(url('formulario-validacion-descarga.php?id=' . (int) $documento['id_documento_validacion'])) ?>">Descargar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<?php require VIEWS_PATH . '/layout/foot.php'; ?>
