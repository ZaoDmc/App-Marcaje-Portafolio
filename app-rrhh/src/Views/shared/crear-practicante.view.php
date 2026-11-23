<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 // Trasladé la plantilla desde Models/PracticanteModel.php; recibe los datos preparados por el controlador.
require_once VIEWS_PATH . '/components/practicante-fields.php';
require_once VIEWS_PATH . '/components/horario-fields.php'; ?>


                <?php if ($mensajeExito !== null): ?>
                    <div class="rrhh-alert border-emerald-200 bg-emerald-50 text-emerald-800" role="alert"><?= e($mensajeExito) ?></div>
                <?php endif; ?>

                <?php if ($activationUrlManual !== null): ?>
                    <div class="rrhh-alert border-amber-200 bg-amber-50 text-amber-900" role="alert">
                        No fue posible enviar el correo de activación. Copia este enlace y entrégalo manualmente al practicante:
                        <div class="mt-2">
                            <input class="rrhh-input" value="<?= e((string) $activationUrlManual) ?>" readonly>
                        </div>
                    </div>
                <?php endif; ?>

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
                    <form class="form-shell p-4 md:p-6" method="post" action="<?= e(url($actionPath)) ?>" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_crear_practicante']) ?>">
                        <p class="text-slate-500 text-sm mb-6">
                            Los campos marcados con <span class="required-star">*</span> son obligatorios.
                        </p>

                        <div class="section-title mb-4">Datos personales</div>
                        <div class="grid grid-cols-12 gap-4 mb-6">
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="rut_numero">RUT</label>
                                <input class="rrhh-input" id="rut_numero" name="rut_numero" value="<?= e((string) $values['rut_numero']) ?>" inputmode="numeric">
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="rut_dv">DV</label>
                                <input class="rrhh-input" id="rut_dv" name="rut_dv" value="<?= e((string) $values['rut_dv']) ?>" maxlength="1">
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="rrhh-label" for="nombre_1">Primer nombre <span class="required-star">*</span></label>
                                <input class="rrhh-input" id="nombre_1" name="nombre_1" value="<?= e((string) $values['nombre_1']) ?>" required>
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="rrhh-label" for="nombre_2">Segundo nombre</label>
                                <input class="rrhh-input" id="nombre_2" name="nombre_2" value="<?= e((string) $values['nombre_2']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="apellido_paterno">Apellido paterno <span class="required-star">*</span></label>
                                <input class="rrhh-input" id="apellido_paterno" name="apellido_paterno" value="<?= e((string) $values['apellido_paterno']) ?>" required>
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="apellido_materno">Apellido materno</label>
                                <input class="rrhh-input" id="apellido_materno" name="apellido_materno" value="<?= e((string) $values['apellido_materno']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="fecha_nacimiento">Fecha de nacimiento</label>
                                <input class="rrhh-input" type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e((string) $values['fecha_nacimiento']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="email">Correo <span class="required-star">*</span></label>
                                <input class="rrhh-input" type="email" id="email" name="email" value="<?= e((string) $values['email']) ?>" required>
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="telefono">Teléfono</label>
                                <input class="rrhh-input" id="telefono" name="telefono" value="<?= e((string) $values['telefono']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="sexo">Sexo</label>
                                <select class="rrhh-select" id="sexo" name="sexo">
                                    <option value="">Seleccionar</option>
                                    <option value="F" <?= (string) $values['sexo'] === 'F' ? 'selected' : '' ?>>F</option>
                                    <option value="M" <?= (string) $values['sexo'] === 'M' ? 'selected' : '' ?>>M</option>
                                    <option value="O" <?= (string) $values['sexo'] === 'O' ? 'selected' : '' ?>>O</option>
                                </select>
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="id_nacionalidad">Nacionalidad</label>
                                <?php practicante_registro_render_select('id_nacionalidad', $catalogos['nacionalidades'], 'id_nacionalidad', 'nombre', $values, 'Seleccionar', 'data-otra-nombre="Otra"'); ?>
                            </div>
                            <div class="col-span-12 md:col-span-2 campo-otra-nacionalidad">
                                <label class="rrhh-label" for="nacionalidad_otra">Ingrese su nacionalidad</label>
                                <input class="rrhh-input" id="nacionalidad_otra" name="nacionalidad_otra" value="<?= e((string) $values['nacionalidad_otra']) ?>">
                            </div>
                        </div>

                        <div class="section-title mb-4">Domicilio</div>
                        <div class="grid grid-cols-12 gap-4 mb-6">
                            <div class="col-span-12 md:col-span-5">
                                <label class="rrhh-label" for="direccion">Dirección</label>
                                <input class="rrhh-input" id="direccion" name="direccion" value="<?= e((string) $values['direccion']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="numero_direccion">Número</label>
                                <input class="rrhh-input" id="numero_direccion" name="numero_direccion" value="<?= e((string) $values['numero_direccion']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="departamento">Depto.</label>
                                <input class="rrhh-input" id="departamento" name="departamento" value="<?= e((string) $values['departamento']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="rrhh-label" for="pais">País</label>
                                <input class="rrhh-input" id="pais" name="pais" value="<?= e((string) $values['pais']) ?>" maxlength="100">
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="id_region">Región</label>
                                <?php practicante_registro_render_select('id_region', $catalogos['regiones'], 'id_region', 'nombre_region', $values, 'Seleccionar región'); ?>
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="id_comuna">Comuna</label>
                                <select class="rrhh-select" id="id_comuna" name="id_comuna">
                                    <option value="">Seleccionar comuna</option>
                                    <?php foreach ($catalogos['comunas'] as $comuna): ?>
                                        <option
                                            value="<?= e((string) $comuna['id_comuna']) ?>"
                                            data-region="<?= e((string) $comuna['id_region']) ?>"
                                            <?= (string) $values['id_comuna'] === (string) $comuna['id_comuna'] ? 'selected' : '' ?>
                                        >
                                            <?= e((string) $comuna['nombre_comuna']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="section-title mb-4">Institución y práctica</div>
                        <div class="grid grid-cols-12 gap-4 mb-6">
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="id_institucion">Institución</label>
                                <?php practicante_registro_render_select('id_institucion', $catalogos['instituciones'], 'id_institucion', 'nombre', $values, 'Seleccionar institución', 'data-otra-nombre="Otra"'); ?>
                            </div>
                            <div class="col-span-12 md:col-span-4 sede-normalizada">
                                <label class="rrhh-label" for="id_sede">Sede</label>
                                <select class="rrhh-select" id="id_sede" name="id_sede">
                                    <option value="">Seleccionar sede</option>
                                    <?php foreach ($catalogos['sedes'] as $sede): ?>
                                        <option
                                            value="<?= e((string) $sede['id_sede']) ?>"
                                            data-institucion="<?= e((string) $sede['id_institucion']) ?>"
                                            <?= (string) $values['id_sede'] === (string) $sede['id_sede'] ? 'selected' : '' ?>
                                        >
                                            <?= e((string) $sede['nombre_sede']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-span-12 md:col-span-4 campo-otra">
                                <label class="rrhh-label" for="institucion_otra">Ingrese su institución</label>
                                <input class="rrhh-input" id="institucion_otra" name="institucion_otra" value="<?= e((string) $values['institucion_otra']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-4 campo-otra">
                                <label class="rrhh-label" for="sede_otra">Ingrese su sede</label>
                                <input class="rrhh-input" id="sede_otra" name="sede_otra" value="<?= e((string) $values['sede_otra']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="id_carrera">Carrera</label>
                                <?php practicante_registro_render_select('id_carrera', $catalogos['carreras'], 'id_carrera', 'nombre', $values, 'Seleccionar carrera'); ?>
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="semestre">Semestre</label>
                                <input class="rrhh-input" id="semestre" name="semestre" value="<?= e((string) $values['semestre']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="rrhh-label" for="id_tipo_practica">Tipo de práctica</label>
                                <select class="rrhh-select" id="id_tipo_practica" name="id_tipo_practica">
                                    <option value="">Seleccionar tipo</option>
                                    <?php foreach ($catalogos['tipos_practica'] as $tipo): ?>
                                        <option
                                            value="<?= e((string) $tipo['id_tipo_practica']) ?>"
                                            data-horas-base="<?= e((string) $tipo['horas_base']) ?>"
                                            <?= (string) $values['id_tipo_practica'] === (string) $tipo['id_tipo_practica'] ? 'selected' : '' ?>
                                        >
                                            <?= e((string) $tipo['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-span-12 md:col-span-3">
                                <label class="rrhh-label" for="horas_requeridas">Horas requeridas</label>
                                <input class="rrhh-input" type="number" min="1" id="horas_requeridas" name="horas_requeridas" value="<?= e((string) $values['horas_requeridas']) ?>">
                            </div>
                            <div class="col-span-12 md:col-span-2">
                                <label class="rrhh-label" for="horas_previas">Horas previas</label>
                                <input class="rrhh-input" type="number" min="0" step="0.01" id="horas_previas" name="horas_previas" value="<?= e((string) ($values['horas_previas'] ?? '')) ?>" placeholder="0">
                                <div class="rrhh-help">Hrs. ya realizadas</div>
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <label class="rrhh-label" for="fecha_inicio_practica">Inicio de práctica</label>
                                <input class="rrhh-input" type="date" id="fecha_inicio_practica" name="fecha_inicio_practica" value="<?= e((string) $values['fecha_inicio_practica']) ?>">
                            </div>
                        </div>

                        <div class="section-title mb-4">Horario pactado</div>
                        <div class="mb-6">
                            <?php horarios_practicante_render_form($values['horario'] ?? horarios_practicante_valores_iniciales(), false); ?>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a class="rrhh-button border-slate-300 text-slate-700 hover:bg-slate-100" href="<?= e(url($dashboardPath)) ?>">Cancelar</a>
                            <button class="rrhh-button bg-brand-blue text-white hover:bg-blue-700" type="submit">Crear pre-registro</button>
                        </div>
                    </form>
                <?php endif; ?>
            </main>

            <script>
                (() => {
                    const institucion = document.getElementById('id_institucion');
                    const sede = document.getElementById('id_sede');
                    const region = document.getElementById('id_region');
                    const comuna = document.getElementById('id_comuna');
                    const camposOtra = document.querySelectorAll('.campo-otra');
                    const sedeNormalizada = document.querySelector('.sede-normalizada');
                    const tipoPractica = document.getElementById('id_tipo_practica');
                    const horas = document.getElementById('horas_requeridas');
                    const nacionalidad = document.getElementById('id_nacionalidad');
                    const camposOtraNacionalidad = document.querySelectorAll('.campo-otra-nacionalidad');

                    const actualizarInstitucion = () => {
                        const opcion = institucion.options[institucion.selectedIndex];
                        const esOtra = opcion && opcion.text.trim().toLowerCase() === 'otra';

                        camposOtra.forEach((campo) => campo.classList.toggle('hidden', !esOtra));
                        sedeNormalizada.classList.toggle('hidden', esOtra);

                        Array.from(sede.options).forEach((option) => {
                            if (option.value === '') {
                                option.hidden = false;
                                return;
                            }

                            option.hidden = option.dataset.institucion !== institucion.value;
                        });

                        const selected = sede.options[sede.selectedIndex];
                        if (esOtra || (selected && selected.value !== '' && selected.dataset.institucion !== institucion.value)) {
                            sede.value = '';
                        }
                    };

                    const actualizarHoras = () => {
                        const opcion = tipoPractica.options[tipoPractica.selectedIndex];
                        const horasBase = opcion ? Number(opcion.dataset.horasBase || 0) : 0;

                        if (horasBase > 0 && horas.value === '') {
                            horas.value = String(horasBase);
                        }
                    };

                    const actualizarComunas = () => {
                        Array.from(comuna.options).forEach((option) => {
                            if (option.value === '') {
                                option.hidden = false;
                                return;
                            }

                            option.hidden = option.dataset.region !== region.value;
                        });

                        const selected = comuna.options[comuna.selectedIndex];
                        if (selected && selected.value !== '' && selected.dataset.region !== region.value) {
                            comuna.value = '';
                        }
                    };

                    const actualizarNacionalidad = () => {
                        const opcion = nacionalidad.options[nacionalidad.selectedIndex];
                        const esOtra = opcion && opcion.text.trim().toLowerCase() === 'otra';

                        camposOtraNacionalidad.forEach((campo) => campo.classList.toggle('hidden', !esOtra));
                    };

                    institucion.addEventListener('change', actualizarInstitucion);
                    region.addEventListener('change', actualizarComunas);
                    tipoPractica.addEventListener('change', actualizarHoras);
                    nacionalidad.addEventListener('change', actualizarNacionalidad);
                    actualizarComunas();
                    actualizarInstitucion();
                    actualizarNacionalidad();
                })();
            </script>
