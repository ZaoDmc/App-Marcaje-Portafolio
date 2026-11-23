<?php require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="mx-auto max-w-7xl p-4 lg:p-8">
    <!-- Implementé el Encabezado de la página, que contendrá el título inicial de la página -->
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Gestión y Recepción de Reseñas
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Supervisa y valida las reseñas y autorizaciones legales enviadas por los practicantes.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                <i class="bi bi-shield-check"></i>
                Panel Recursos Humanos
            </span>
        </div>
    </div>

    <!-- Implementé las alertas visuales de confirmación o error para el supervisor -->
    <?php if (!empty($mensajeExito)): ?>
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300">
            <i class="bi bi-check-circle-fill text-xl"></i>
            <span><?= e($mensajeExito) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800 dark:border-red-900 dark:bg-red-950/60 dark:text-red-300">
            <i class="bi bi-exclamation-triangle-fill text-xl"></i>
            <span><?= e($mensajeError) ?></span>
        </div>
    <?php endif; ?>

    <!-- Implementé un apartado de tarjetas métricas que se encarguen de resumir el estado de las reseñas -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Aquí se encontrarán las solicitudes pendientes -->
        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pendientes de Entrega</p>
                    <h3 class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                        <?= (int) ($metricas['pendientes'] ?? 0) ?>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/50">
                    <i class="bi bi-hourglass-split text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Aquí se encontrarán las solicitudes Recibidas -->
        <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reseñas Recibidas</p>
                    <h3 class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                        <?= (int) ($metricas['recibidas'] ?? 0) ?>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50">
                    <i class="bi bi-chat-square-text text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Aquí se encontrarán las solicitudes Publicadas -->
        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reseñas Publicadas</p>
                    <h3 class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        <?= (int) ($metricas['publicadas'] ?? 0) ?>
                    </h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50">
                    <i class="bi bi-check-circle-fill text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Implementé la tabla que contendrá el listado de practicantes con sus respectivas reseñas -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 p-6 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Listado de Practicantes</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Revisa el estado de la reseña, canal de entrega y documento de autorización de cada estudiante.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="rrhh-table w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-800/40">
                        <th class="p-4 font-semibold text-slate-700 dark:text-slate-300">Practicante</th>
                        <th class="p-4 font-semibold text-slate-700 dark:text-slate-300">Carrera</th>
                        <th class="p-4 font-semibold text-slate-700 dark:text-slate-300">Canal de Envío</th>
                        <th class="p-4 text-center font-semibold text-slate-700 dark:text-slate-300">Estado Reseña</th>
                        <th class="p-4 text-center font-semibold text-slate-700 dark:text-slate-300">Autorización PDF</th>
                        <th class="p-4 text-center font-semibold text-slate-700 dark:text-slate-300">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (empty($practicantes)): ?>
                        <!-- Aquí se listarán los practicantes cuando se conecte con el backend; mientras tanto mostramos el estado vacío -->
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="bi bi-inbox text-3xl text-slate-300 dark:text-slate-600"></i>
                                    <p class="text-sm font-medium">No hay practicantes con reseñas registradas actualmente.</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">Las reseñas enviadas por los estudiantes aparecerán listadas aquí.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($practicantes as $item): ?>
                            <?php
                            $nombreCompleto = trim((string) $item['nombre_1'] . ' ' . (string) $item['apellido_paterno'] . ' ' . (string) ($item['apellido_materno'] ?? ''));
                            $estado = strtoupper((string) ($item['estado_resena'] ?? 'PENDIENTE'));
                            $canal = strtoupper((string) ($item['canal_envio'] ?? ''));
                            $tienePdf = !empty($item['archivo_autorizacion']);
                            ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                <td class="p-4">
                                    <div class="font-bold text-slate-800 dark:text-white"><?= e($nombreCompleto) ?></div>
                                    <div class="text-xs text-slate-400"><?= e((string) $item['email']) ?></div>
                                </td>
                                <td class="p-4 text-slate-600 dark:text-slate-300">
                                    <?= e((string) $item['carrera']) ?>
                                </td>
                                <td class="p-4">
                                    <?php if ($canal === 'DISCORD'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                                            <i class="bi bi-discord"></i> Discord
                                        </span>
                                    <?php elseif ($canal === 'CORREO'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">
                                            <i class="bi bi-envelope-at"></i> Correo
                                        </span>
                                    <?php elseif ($canal === 'WHATSAPP'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            <i class="bi bi-whatsapp"></i> WhatsApp
                                        </span>
                                    <?php elseif ($canal === 'DOCUMENTO'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                            <i class="bi bi-file-earmark-text"></i> Documento
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($estado === 'PUBLICADA'): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            <i class="bi bi-check-circle-fill"></i> Publicada
                                        </span>
                                    <?php elseif ($estado === 'RECIBIDA'): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                            <i class="bi bi-check2"></i> Recibida
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            <i class="bi bi-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($tienePdf && !empty($item['id_resena'])): ?>
                                        <a href="<?= e(url('supervisor/resenas.php?descargar_pdf=' . (int) $item['id_resena'])) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                            <i class="bi bi-file-earmark-pdf-fill text-base"></i> Ver PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if (!empty($item['id_resena']) && $estado === 'RECIBIDA'): ?>
                                        <form action="<?= e(url('supervisor/resenas.php')) ?>" method="POST" class="inline-block" onsubmit="return confirm('¿Aprobar y publicar esta reseña?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id_resena" value="<?= (int) $item['id_resena'] ?>">
                                            <input type="hidden" name="nuevo_estado" value="PUBLICADA">
                                            <button type="submit" class="rrhh-button rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 shadow-sm">
                                                <i class="bi bi-check-lg mr-1"></i>Aprobar
                                            </button>
                                        </form>
                                    <?php elseif ($estado === 'PUBLICADA'): ?>
                                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                            <i class="bi bi-patch-check-fill mr-1"></i>Aprobada
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">En espera</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
