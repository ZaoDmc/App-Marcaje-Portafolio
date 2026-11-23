<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 // Agregué componentes de campos trasladados desde el controlador.
require_once VIEWS_PATH . '/components/mis-datos-fields.php';
require_once VIEWS_PATH . '/components/horario-fields.php';
require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Mensajes de feedback -->
    <?php if ($mensajeExito !== null): ?>
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill text-emerald-600 text-lg shrink-0"></i>
            <span><?= e($mensajeExito) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($modoCompletar): ?>
        <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-circle-fill text-amber-600 text-lg shrink-0"></i>
            <span>Debes completar tu ficha para acceder al portal y dar inicio a tu práctica.</span>
        </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <div class="font-bold">Por favor corrige los siguientes puntos:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($practicante !== null): ?>
        <?php // Añadí el selector visual fuera del formulario de perfil para no mezclar ni reenviar datos personales. ?>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="tema-practicante-titulo">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="tema-practicante-titulo" class="text-base font-bold text-slate-900">Personaliza el tema del portal</h2>
                    <p class="mt-1 text-sm text-slate-500">Se aplica al instante en tus páginas y se guarda en este navegador.</p>
                </div>
                <div class="w-full sm:max-w-xs">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-600" for="practicante-theme-select">Tema de color</label>
                    <select class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20" id="practicante-theme-select">
                        <option value="light">Claro</option>
                        <option value="dark">Oscuro</option>
                        <option value="synthwave">Synthwave — violeta neón</option>
                        <option value="aqua">Aqua — celeste</option>
                        <option value="coffee">Coffee — café</option>
                        <option value="black">Black — negro</option>
                    </select>
                </div>
            </div>
            <p id="practicante-theme-status" class="mt-3 text-xs text-slate-500" role="status" aria-live="polite"></p>
        </section>

        <form class="space-y-6" method="post" action="<?= e(url('practicante/mis-datos.php')) ?>" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e((string) $_SESSION['csrf_mis_datos']) ?>">

            <!-- Hero / Card de Perfil y Foto -->
            <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <div class="shrink-0">
                        <?php if ($documentosActuales['foto'] !== null): ?>
                            <?php // Mostré la foto completa y centrada en su marco, sin recortarla para llenar el cuadrado. ?>
                            <img
                                class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-contain object-center bg-slate-50 border-2 border-slate-100 shadow-sm"
                                src="<?= e(url('practicante/foto-perfil.php?v=' . (int) $documentosActuales['foto']['id_documento'])) ?>"
                                alt="Foto de perfil de <?= e(mis_datos_nombre_completo($practicante)) ?>"
                            >
                        <?php else: ?>
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gradient-to-tr from-teal-700 to-teal-500 text-white flex items-center justify-center font-bold text-2xl sm:text-3xl shadow-sm" aria-label="Iniciales del practicante">
                                <?= e(mis_datos_iniciales($practicante)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs uppercase font-bold tracking-wider text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200">
                                <?= $modoCompletar ? 'Ficha pendiente' : 'Perfil practicante' ?>
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 leading-tight">
                            <?= e(mis_datos_nombre_completo($practicante)) ?>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-2xl">
                            <?= $modoCompletar ? 'Completa tus datos obligatorios y, si lo deseas, sube tu foto y documentos para dar inicio a tus funciones.' : 'Administra tu información personal de contacto, foto de perfil y certificados de práctica.' ?>
                        </p>

                        <!-- Subida de foto de perfil -->
                        <?php if (empty($soloLectura)): ?>
                        <div class="pt-2 flex flex-col md:flex-row items-start md:items-end gap-3">
                            <div class="space-y-1 w-full md:max-w-xs">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="foto_perfil">
                                    <?= $documentosActuales['foto'] !== null ? 'Cambiar foto de perfil' : 'Subir foto de perfil' ?>
                                </label>
                                <input class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-300 text-slate-700 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer" type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/png,.jpg,.jpeg,.png" aria-describedby="foto-perfil-ayuda">
                                <?php // Añadí una recomendación de tamaño sin exigir recorte ni cambiar el límite de subida existente. ?>
                                <span id="foto-perfil-ayuda" class="text-[11px] text-slate-400 block">JPG o PNG. Máximo 5 MB. Recomendado: 500 × 500 px (formato cuadrado). La imagen se muestra completa y centrada.</span>
                            </div>
                            <button class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs rounded-xl shadow-sm transition cursor-pointer" type="submit" name="accion_perfil" value="subir_foto">
                                <?= $documentosActuales['foto'] !== null ? 'Actualizar foto' : 'Subir foto' ?>
                            </button>
                            <?php if ($documentosActuales['foto'] !== null): ?>
                                <span class="text-xs text-slate-400">
                                    Cargada el <?= e(mis_datos_fecha_visible($documentosActuales['foto']['created_at'] ?? null)) ?>.
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if (empty($soloLectura)): ?>
            <div class="text-xs text-slate-500">
                Los campos marcados con <span class="text-red-500 font-bold">*</span> son requeridos.
            </div>
            <?php endif; ?>

            <?php if ($modoCompletar): ?>
                <!-- IDENTIFICACIÓN EN MODO COMPLETAR -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="bi bi-person-vcard text-teal-700 text-lg"></i>
                        <h3 class="text-base font-bold text-slate-800">Identificación del practicante</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-3">
                            <?php mis_datos_label('rut_numero', 'RUT'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="rut_numero" name="rut_numero" value="<?= e((string) ($values['rut_numero'] ?? '')) ?>" inputmode="numeric" required>
                        </div>
                        <div class="lg:col-span-2">
                            <?php mis_datos_label('rut_dv', 'DV'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="rut_dv" name="rut_dv" value="<?= e((string) ($values['rut_dv'] ?? '')) ?>" maxlength="1" required>
                        </div>
                        <div class="lg:col-span-3 space-y-1.5">
                            <span class="block text-xs font-bold uppercase tracking-wider text-slate-600">Primer nombre</span>
                            <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium"><?= e((string) $practicante['nombre_1']) ?></div>
                        </div>
                        <div class="lg:col-span-4 space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="nombre_2">Segundo nombre</label>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="nombre_2" name="nombre_2" value="<?= e((string) ($values['nombre_2'] ?? '')) ?>">
                        </div>
                        <div class="lg:col-span-3 space-y-1.5">
                            <span class="block text-xs font-bold uppercase tracking-wider text-slate-600">Apellido paterno</span>
                            <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium"><?= e((string) $practicante['apellido_paterno']) ?></div>
                        </div>
                        <div class="lg:col-span-3 space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="apellido_materno">Apellido materno</label>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="apellido_materno" name="apellido_materno" value="<?= e((string) ($values['apellido_materno'] ?? '')) ?>">
                        </div>
                        <div class="lg:col-span-3">
                            <?php mis_datos_label('fecha_nacimiento', 'Fecha de nacimiento'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e((string) ($values['fecha_nacimiento'] ?? '')) ?>" required>
                        </div>
                        <div class="lg:col-span-3">
                            <?php mis_datos_label('sexo', 'Sexo'); ?>
                            <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="sexo" name="sexo" required>
                                <option value="">Seleccionar</option>
                                <option value="F" <?= (string) ($values['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>F</option>
                                <option value="M" <?= (string) ($values['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>M</option>
                                <option value="O" <?= (string) ($values['sexo'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
                            </select>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <!-- FICHA DE INFORMACIÓN DE SOLO LECTURA (MODO NORMAL) -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="bi bi-shield-lock text-teal-700 text-lg"></i>
                        <h3 class="text-base font-bold text-slate-800">Información institucional y académica (solo lectura)</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">RUT</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['rut_numero'])) ?>-<?= e(mis_datos_valor_visible($practicante['rut_dv'], '')) ?></div>
                        </div>
                        <div class="lg:col-span-5 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nombre completo</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(trim((string) $practicante['nombre_1'] . ' ' . (string) $practicante['nombre_2'] . ' ' . (string) $practicante['apellido_paterno'] . ' ' . (string) $practicante['apellido_materno'])) ?></div>
                        </div>
                        <div class="lg:col-span-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Correo de acceso</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e((string) $practicante['email']) ?></div>
                        </div>
                        <div class="lg:col-span-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Institución</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['institucion_otra'] ?: $practicante['institucion_nombre'])) ?></div>
                        </div>
                        <div class="lg:col-span-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Sede</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['sede_otra'] ?: $practicante['nombre_sede'])) ?></div>
                        </div>
                        <div class="lg:col-span-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Carrera</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['carrera_nombre'])) ?></div>
                        </div>
                        <div class="lg:col-span-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Tipo de práctica</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['tipo_practica_nombre'])) ?></div>
                        </div>
                        <div class="lg:col-span-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Horas requeridas</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['horas_requeridas'])) ?> hrs</div>
                        </div>
                        <div class="lg:col-span-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Inicio de práctica</div>
                            <div class="font-semibold text-slate-800 text-sm"><?= e(mis_datos_valor_visible($practicante['fecha_inicio_practica'])) ?></div>
                        </div>
                        <div class="lg:col-span-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Estado</div>
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                    <?= e(mis_datos_valor_visible($practicante['estado_practica_nombre'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- CONTACTO Y DIRECCIÓN (EDITABLE EN AMBOS MODOS) -->
            <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-geo-alt text-teal-700 text-lg"></i>
                        <h3 class="text-base font-bold text-slate-800">Contacto y dirección particular<?= !empty($soloLectura) ? ' (solo lectura)' : '' ?></h3>
                    </div>
                </div>
                <fieldset <?= !empty($soloLectura) ? 'disabled class="opacity-75"' : '' ?>>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-4 space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="telefono">Teléfono</label>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="telefono" name="telefono" value="<?= e((string) ($values['telefono'] ?? '')) ?>" maxlength="20">
                    </div>
                    <div class="lg:col-span-4">
                        <?php mis_datos_label('nacionalidad', 'Nacionalidad'); ?>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="nacionalidad" name="nacionalidad" value="<?= e((string) ($values['nacionalidad'] ?? '')) ?>" maxlength="50" required>
                    </div>
                    <div class="lg:col-span-4">
                        <?php mis_datos_label('id_region', 'Región'); ?>
                        <?php mis_datos_render_select('id_region', $catalogos['regiones'], 'id_region', 'nombre_region', $values, 'Seleccionar región', true); ?>
                    </div>
                    <div class="lg:col-span-5">
                        <?php mis_datos_label('direccion', 'Dirección'); ?>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="direccion" name="direccion" value="<?= e((string) ($values['direccion'] ?? '')) ?>" maxlength="200" required>
                    </div>
                    <div class="lg:col-span-2">
                        <?php mis_datos_label('numero_direccion', 'Número'); ?>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="numero_direccion" name="numero_direccion" value="<?= e((string) ($values['numero_direccion'] ?? '')) ?>" maxlength="20" required>
                    </div>
                    <div class="lg:col-span-2 space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="departamento">Depto.</label>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="departamento" name="departamento" value="<?= e((string) ($values['departamento'] ?? '')) ?>" maxlength="50">
                    </div>
                    <div class="lg:col-span-3">
                        <?php mis_datos_label('id_comuna', 'Comuna'); ?>
                        <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="id_comuna" name="id_comuna" required>
                            <option value="">Seleccionar comuna</option>
                            <?php foreach ($catalogos['comunas'] as $comuna): ?>
                                <option
                                    value="<?= e((string) $comuna['id_comuna']) ?>"
                                    data-region="<?= e((string) $comuna['id_region']) ?>"
                                    <?= (string) ($values['id_comuna'] ?? '') === (string) $comuna['id_comuna'] ? 'selected' : '' ?>
                                >
                                    <?= e((string) $comuna['nombre_comuna']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <?php mis_datos_label('pais', 'País'); ?>
                        <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="pais" name="pais" value="<?= e((string) ($values['pais'] ?? '')) ?>" maxlength="100" required>
                    </div>
                </div>
                </fieldset>
            </section>

            <?php if ($modoCompletar): ?>
                <!-- INSTITUCIÓN Y PRÁCTICA EN MODO COMPLETAR -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="bi bi-mortarboard text-teal-700 text-lg"></i>
                        <h3 class="text-base font-bold text-slate-800">Institución académica y tipo de práctica</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-4">
                            <?php mis_datos_label('id_institucion', 'Institución'); ?>
                            <?php mis_datos_render_select('id_institucion', $catalogos['instituciones'], 'id_institucion', 'nombre', $values, 'Seleccionar institución', true); ?>
                        </div>
                        <div class="lg:col-span-4 sede-normalizada">
                            <?php mis_datos_label('id_sede', 'Sede'); ?>
                            <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="id_sede" name="id_sede">
                                <option value="">Seleccionar sede</option>
                                <?php foreach ($catalogos['sedes'] as $sede): ?>
                                    <option
                                        value="<?= e((string) $sede['id_sede']) ?>"
                                        data-institucion="<?= e((string) $sede['id_institucion']) ?>"
                                        <?= (string) ($values['id_sede'] ?? '') === (string) $sede['id_sede'] ? 'selected' : '' ?>
                                    >
                                        <?= e((string) $sede['nombre_sede']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lg:col-span-4 campo-otra hidden">
                            <?php mis_datos_label('institucion_otra', 'Ingrese su institución'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="institucion_otra" name="institucion_otra" value="<?= e((string) ($values['institucion_otra'] ?? '')) ?>" maxlength="150">
                        </div>
                        <div class="lg:col-span-4 campo-otra hidden">
                            <?php mis_datos_label('sede_otra', 'Ingrese su sede'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="sede_otra" name="sede_otra" value="<?= e((string) ($values['sede_otra'] ?? '')) ?>" maxlength="150">
                        </div>
                        <div class="lg:col-span-4">
                            <?php mis_datos_label('id_carrera', 'Carrera'); ?>
                            <?php mis_datos_render_select('id_carrera', $catalogos['carreras'], 'id_carrera', 'nombre', $values, 'Seleccionar carrera', true); ?>
                        </div>
                        <div class="lg:col-span-2">
                            <?php mis_datos_label('semestre', 'Semestre'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" id="semestre" name="semestre" value="<?= e((string) ($values['semestre'] ?? '')) ?>" required>
                        </div>
                        <div class="lg:col-span-3">
                            <?php mis_datos_label('id_tipo_practica', 'Tipo de práctica'); ?>
                            <select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="id_tipo_practica" name="id_tipo_practica" required>
                                <option value="">Seleccionar tipo</option>
                                <?php foreach ($catalogos['tipos_practica'] as $tipo): ?>
                                    <option
                                        value="<?= e((string) $tipo['id_tipo_practica']) ?>"
                                        data-horas-base="<?= e((string) $tipo['horas_base']) ?>"
                                        <?= (string) ($values['id_tipo_practica'] ?? '') === (string) $tipo['id_tipo_practica'] ? 'selected' : '' ?>
                                    >
                                        <?= e((string) $tipo['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lg:col-span-3">
                            <?php mis_datos_label('horas_requeridas', 'Horas requeridas'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" type="number" min="1" id="horas_requeridas" name="horas_requeridas" value="<?= e((string) ($values['horas_requeridas'] ?? '')) ?>" required>
                        </div>
                        <div class="lg:col-span-4">
                            <?php mis_datos_label('fecha_inicio_practica', 'Inicio de práctica'); ?>
                            <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition" type="date" id="fecha_inicio_practica" name="fecha_inicio_practica" value="<?= e((string) ($values['fecha_inicio_practica'] ?? '')) ?>" required>
                        </div>
                    </div>
                </section>

                <!-- HORARIO PACTADO -->
                <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                        <i class="bi bi-calendar3-week text-teal-700 text-lg"></i>
                        <h3 class="text-base font-bold text-slate-800">Horario semanal pactado</h3>
                    </div>
                    <div>
                        <?php horarios_practicante_render_form($values['horario'] ?? horarios_practicante_valores_iniciales(), true); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- DOCUMENTOS DEL PRACTICANTE -->
            <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-file-earmark-arrow-up text-teal-700"></i>
                            <span>Documentos de la práctica</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Puedes mantener hasta 5 documentos activos en formato PDF, Word o Excel.</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                        <?= count($documentosActuales['documentos']) ?> de <?= MIS_DATOS_DOCUMENTOS_MAXIMOS ?> documentos
                    </span>
                </div>

                <!-- Input para subir documentos -->
                <?php if (empty($soloLectura)): ?>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="documentos_practica">
                        Subir nuevos documentos
                    </label>
                    <input class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer bg-white" type="file" id="documentos_practica" name="documentos_practica[]" accept=".pdf,.doc,.docx,.xls,.xlsx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" multiple>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400">Formatos permitidos: PDF, Word, Excel. Máx. 5 MB por archivo.</span>
                        <button class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs rounded-xl shadow-sm transition cursor-pointer" type="submit" name="accion_perfil" value="subir_documentos">
                            <i class="bi bi-upload mr-1"></i> Subir documentos
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista de documentos cargados -->
                <?php if ($documentosActuales['documentos'] !== []): ?>
                    <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                        <?php foreach ($documentosActuales['documentos'] as $documento): ?>
                            <!-- Permití que la descarga pase a otra línea cuando el nombre del documento ocupa el ancho. -->
                            <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 hover:bg-slate-50/80 transition">
                                <div class="flex min-w-0 grow basis-full sm:basis-0 items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-lg shrink-0">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800 text-sm"><?= e((string) $documento['nombre_original']) ?></div>
                                        <div class="text-[11px] text-slate-400">
                                            <?= e(mis_datos_formatear_tamano((int) $documento['tamano_bytes'])) ?> · Cargado el <?= e(mis_datos_fecha_visible($documento['created_at'] ?? null)) ?>
                                        </div>
                                    </div>
                                </div>
                                <a
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-teal-200 text-teal-700 hover:bg-teal-50 transition"
                                    href="<?= e(url('practicante/descargar-documento.php?id=' . (int) $documento['id_documento'])) ?>"
                                >
                                    <i class="bi bi-download"></i>
                                    <span>Descargar</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-xs">
                        <?= !empty($soloLectura)
                            ? 'No registraste documentos durante tu práctica.'
                            : 'Aún no has subido documentos. Puedes agregarlos ahora o hacerlo más adelante desde esta misma pantalla.' ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Acciones Finales -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition" href="<?= e(url('practicante/dashboard.php')) ?>">
                    <?= !empty($soloLectura) ? 'Volver al inicio' : 'Cancelar' ?>
                </a>
                <?php if (empty($soloLectura)): ?>
                <button class="px-6 py-2.5 bg-teal-700 hover:bg-teal-800 active:bg-teal-900 text-white font-semibold text-sm rounded-xl shadow-sm shadow-teal-700/20 transition cursor-pointer" type="submit" name="accion_perfil" value="guardar_cambios">
                    <?= $modoCompletar ? 'Completar registro' : 'Guardar cambios' ?>
                </button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($modoCompletar): ?>
    <script>
        (() => {
            const institucion = document.getElementById('id_institucion');
            const sede = document.getElementById('id_sede');
            const camposOtra = document.querySelectorAll('.campo-otra');
            const sedeNormalizada = document.querySelector('.sede-normalizada');
            const tipoPractica = document.getElementById('id_tipo_practica');
            const horas = document.getElementById('horas_requeridas');

            if (!institucion || !sede) return;

            const actualizarInstitucion = () => {
                const opcion = institucion.options[institucion.selectedIndex];
                const esOtra = opcion && opcion.text.trim().toLowerCase() === 'otra';

                camposOtra.forEach((campo) => campo.classList.toggle('hidden', !esOtra));
                if (sedeNormalizada) {
                    sedeNormalizada.classList.toggle('hidden', esOtra);
                }

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
                if (!tipoPractica || !horas) return;
                const opcion = tipoPractica.options[tipoPractica.selectedIndex];
                const horasBase = opcion ? Number(opcion.dataset.horasBase || 0) : 0;

                if (horasBase > 0 && horas.value === '') {
                    horas.value = String(horasBase);
                }
            };

            institucion.addEventListener('change', actualizarInstitucion);
            if (tipoPractica) {
                tipoPractica.addEventListener('change', actualizarHoras);
            }
            actualizarInstitucion();
        })();
    </script>
<?php endif; ?>

<script>
    (() => {
        const region = document.getElementById('id_region');
        const comuna = document.getElementById('id_comuna');

        if (!region || !comuna) {
            return;
        }

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

        region.addEventListener('change', actualizarComunas);
        actualizarComunas();
    })();
</script>

<?php // Guardé la selección solo como preferencia visual y la apliqué inmediatamente al portal del practicante. ?>
<script>
    (() => {
        const selector = document.getElementById('practicante-theme-select');
        const estado = document.getElementById('practicante-theme-status');
        const raiz = document.documentElement;
        const clave = 'rrhh-practicante-theme';
        const oscuros = ['dark', 'synthwave', 'coffee', 'black'];

        if (!selector) return;

        const aplicarTema = (tema, guardar) => {
            raiz.dataset.theme = tema;
            raiz.classList.toggle('dark', oscuros.includes(tema));

            if (guardar) {
                try {
                    window.localStorage.setItem(clave, tema);
                    if (estado) estado.textContent = 'Tema actualizado.';
                } catch (error) {
                    if (estado) estado.textContent = 'Tema aplicado a esta página; el navegador no permitió guardar la preferencia.';
                }
            }
        };

        selector.value = raiz.dataset.theme || 'light';
        selector.addEventListener('change', () => aplicarTema(selector.value, true));
    })();
</script>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
