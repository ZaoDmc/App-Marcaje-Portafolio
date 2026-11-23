<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="space-y-6">
    <!-- Mensajes de feedback -->
    <?php if ($errores !== []): ?>
        <div class="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-red-600 text-lg shrink-0 mt-0.5"></i>
            <div class="space-y-1">
                <div class="font-bold">Se encontraron los siguientes problemas:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito !== null): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium shadow-sm space-y-3" role="alert">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-lg"></i>
                <span><?= e($mensajeExito) ?></span>
            </div>
            <?php if ($urlActivacion !== null): ?>
                <div class="p-3 bg-white rounded-xl border border-emerald-200/80 space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="url_activacion">Enlace manual de activación</label>
                    <div class="flex items-center gap-2">
                        <input class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 text-slate-700 bg-slate-50 font-mono select-all" id="url_activacion" value="<?= e($urlActivacion) ?>" readonly>
                        <button class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition whitespace-nowrap" type="button" onclick="navigator.clipboard.writeText(document.getElementById('url_activacion').value)">
                            Copiar
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para Crear / Asignar Supervisor -->
    <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm p-5 sm:p-6 space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="bi bi-person-gear text-blue-600"></i>
                <span>Crear o asignar supervisor</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Si el correo electrónico ya existe en el sistema, se asignará el rol SUPERVISOR a esa cuenta existente. Si no existe, se creará un supervisor externo con invitación de activación por correo.
            </p>
        </div>

        <form class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end pt-1" method="post" action="<?= e(url('admin/usuarios.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
            <input type="hidden" name="accion" value="crear_supervisor">

            <div class="sm:col-span-8 space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600" for="email">
                    Correo institucional <span class="text-red-500">*</span>
                </label>
                <input class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition" type="email" id="email" name="email" placeholder="ejemplo@institucion.cl" maxlength="150" required>
            </div>
            <div class="sm:col-span-4">
                <button class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-sm shadow-blue-600/20 transition cursor-pointer" type="submit">
                    Guardar supervisor
                </button>
            </div>
        </form>
    </section>

    <!-- Listado y Búsqueda de Usuarios -->
    <section class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="bi bi-people text-blue-600"></i>
                    <span>Cuentas de usuario</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Gestión de identidades, roles asignados y estados de acceso</p>
            </div>

            <form class="flex items-center gap-2 w-full sm:w-auto" method="get" action="<?= e(url('admin/usuarios.php')) ?>">
                <div class="relative w-full sm:w-64">
                    <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input class="w-full pl-8 pr-3.5 py-2 rounded-xl border border-slate-300 text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por email o rol..." maxlength="100">
                </div>
                <button class="py-2 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl border border-slate-300 transition cursor-pointer" type="submit">
                    Buscar
                </button>
            </form>
        </div>

        <?php if ($usuarios === []): ?>
            <div class="p-10 text-center text-slate-500 space-y-2">
                <i class="bi bi-people text-3xl text-slate-400 block"></i>
                <p class="font-medium text-sm">No hay usuarios registrados que coincidan con la búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200/80 text-xs uppercase font-bold text-slate-600 tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3.5">Email</th>
                            <th scope="col" class="px-4 py-3.5">Roles</th>
                            <th scope="col" class="px-4 py-3.5">Estado</th>
                            <th scope="col" class="px-4 py-3.5 text-center">Activo</th>
                            <th scope="col" class="px-4 py-3.5">Último login</th>
                            <th scope="col" class="px-4 py-3.5">Creado</th>
                            <th scope="col" class="px-4 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                            $esPracticanteSupervisor = $usuario['id_practicante'] !== null
                                && usuarios_admin_fila_tiene_rol($usuario, 'PRACTICANTE')
                                && usuarios_admin_fila_tiene_rol($usuario, 'SUPERVISOR');
                            ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3.5 font-medium text-slate-900 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs uppercase">
                                            <?= e(substr((string) $usuario['email'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs"><?= e((string) $usuario['email']) ?></div>
                                            <?php if ($usuario['id_practicante'] !== null): ?>
                                                <div class="text-[11px] text-teal-700 font-semibold">Vinculado a practicante</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= e((string) ($usuario['roles'] ?? 'Sin rol activo')) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                                    <?= e((string) $usuario['estado_cuenta']) ?>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold <?= (int) $usuario['activo'] === 1 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                        <?= (int) $usuario['activo'] === 1 ? 'SÍ' : 'NO' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                    <?= e((string) ($usuario['ultimo_login'] ?? 'Sin login')) ?>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                                    <?= e((string) $usuario['created_at']) ?>
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <?php if ($esPracticanteSupervisor): ?>
                                        <form method="post" action="<?= e(url('admin/usuarios.php')) ?>" onsubmit="return confirm('¿Quitar el rol SUPERVISOR y dejar este usuario solo como PRACTICANTE?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                            <input type="hidden" name="accion" value="quitar_supervisor">
                                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                                            <button class="px-2.5 py-1 text-xs font-semibold text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition cursor-pointer" type="submit">
                                                Quitar supervisor
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs italic">Sin acción</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
