<?php require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

<div class="mx-auto max-w-5xl p-4 lg:p-8">
    <!-- Aqui implementé encabezado principal de la pagina - Alexander -->
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Recepción de Reseñas</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Comparte tu experiencia de práctica y ayuda a futuros practicantes.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php 
            $estadoActual = strtoupper((string) ($resenaActual['estado'] ?? 'PENDIENTE'));
            if ($estadoActual === 'PUBLICADA'): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Reseña Publicada
                </span>
            <?php elseif ($estadoActual === 'RECIBIDA'): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Reseña Recibida
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Pendiente de entrega
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Implementé las alertas visuales para notificar éxito o errores en el formulario - Alexander -->
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

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Implementé la columna izquierda para el formulario de redacción - Alexander -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Descarga de Documento Oficial y Autorización (PDF) -->
            <div class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50/40 p-5 shadow-sm dark:border-blue-900/40 dark:from-slate-900 dark:to-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                            <i class="bi bi-file-earmark-pdf-fill text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white">Documento Oficial de Autorización</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-300">
                                Descarga la plantilla oficial, revisa las condiciones de uso de testimonio y fírmala para adjuntarla.
                            </p>
                        </div>
                    </div>
                    <a href="<?= e(url('practicante/resenas.php?descargar=plantilla')) ?>" class="rrhh-button inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 shrink-0">
                        <i class="bi bi-download"></i>
                        Descargar Plantilla (PDF)
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-1 text-lg font-bold text-slate-800 dark:text-white">
                    Tu Experiencia de Práctica
                </h3>
                <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
                    Cuéntanos como fue tu paso por la empresa, aprendizajes y ambiente laboral
                </p>

                <!-- Conecté el formulario para enviar datos reales por POST con soporte de archivos multipart - Alexander -->
                <form action="<?= e(url('practicante/resenas.php')) ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    
                    <?php $calificacionActual = (int) ($resenaActual['calificacion'] ?? 5); ?>
                    <input type="hidden" name="calificacion" id="calificacion_input" value="<?= $calificacionActual ?>">
                    <input type="hidden" name="aspectos_destacados" id="aspectos_input" value="<?= e((string)($resenaActual['aspectos_destacados'] ?? 'Buen ambiente laboral')) ?>">

                    <!-- Calificación con Estrellas interactivas - Alexander -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Calificación general
                        </label>
                        <div class="flex items-center gap-2 text-2xl text-amber-400" id="contenedor_estrellas">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" data-star="<?= $i ?>" class="btn-estrella transition hover:scale-110 focus:outline-none <?= $i <= $calificacionActual ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700' ?>">
                                    <i class="bi bi-star-fill"></i>
                                </button>
                            <?php endfor; ?>
                            <span id="texto_calificacion" class="ml-2 text-sm font-bold text-slate-600 dark:text-slate-400">
                                <?= number_format((float)$calificacionActual, 1) ?> de 5.0
                            </span>
                        </div>
                    </div>

                    <!-- Etiquetas de Lo más destacado seleccionables - Alexander -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            ¿Qué destacarías de tu práctica?
                        </label>
                        <div class="flex flex-wrap gap-2" id="contenedor_chips">
                            <?php
                            $opcionesAspectos = [
                                'Buen ambiente laboral',
                                'Aprendizaje técnico',
                                'Acompañamiento del supervisor',
                                'Flexibilidad horaria'
                            ];
                            $aspectosGuardados = array_map('trim', explode(',', (string)($resenaActual['aspectos_destacados'] ?? 'Buen ambiente laboral')));
                            foreach ($opcionesAspectos as $aspecto):
                                $seleccionado = in_array($aspecto, $aspectosGuardados, true);
                            ?>
                                <button type="button" data-aspecto="<?= e($aspecto) ?>" class="chip-aspecto rounded-lg border px-3 py-1.5 text-xs font-semibold transition <?= $seleccionado ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300' ?>">
                                    <i class="bi <?= $seleccionado ? 'bi-check2' : 'bi-plus' ?> mr-1"></i><?= e($aspecto) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Canal de envío según Anexo oficial - Alexander -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Vía o canal donde envías tu reseña
                        </label>
                        <?php $canalActual = strtoupper((string)($resenaActual['canal_envio'] ?? 'DISCORD')); ?>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                                <input type="radio" name="canal_envio" value="DISCORD" class="rrhh-radio" <?= $canalActual === 'DISCORD' ? 'checked' : '' ?>>
                                <i class="bi bi-discord text-indigo-500 text-base"></i> Discord
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                                <input type="radio" name="canal_envio" value="CORREO" class="rrhh-radio" <?= $canalActual === 'CORREO' ? 'checked' : '' ?>>
                                <i class="bi bi-envelope-at text-sky-500 text-base"></i> Correo
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                                <input type="radio" name="canal_envio" value="WHATSAPP" class="rrhh-radio" <?= $canalActual === 'WHATSAPP' ? 'checked' : '' ?>>
                                <i class="bi bi-whatsapp text-emerald-500 text-base"></i> WhatsApp
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                                <input type="radio" name="canal_envio" value="DOCUMENTO" class="rrhh-radio" <?= $canalActual === 'DOCUMENTO' ? 'checked' : '' ?>>
                                <i class="bi bi-file-earmark-text text-blue-500 text-base"></i> Documento
                            </label>
                        </div>
                    </div>

                    <!-- Implementé un Testimonio que permita dejar tus reseñas sobre la práctica - Alexander -->
                    <div>
                        <label for="comentario" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Tu Testimonio o reseña
                        </label>
                        <textarea id="comentario" name="testimonio" rows="4" maxlength="500" required class="rrhh-input w-full rounded-xl border border-slate-300 p-3.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white" placeholder="Escribe aquí tu opinión sobre tu experiencia de práctica, desafios y en las areas que lograste participar..."><?= e((string)($resenaActual['testimonio'] ?? '')) ?></textarea>
                        <p class="mt-1 text-right text-xs text-slate-400" id="contador_caracteres">0 / 500 caracteres</p>
                    </div>

                    <!-- Adjuntar documento firmado (PDF o imagen) - Alexander -->
                    <div>
                        <label for="documento_firmado" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Adjuntar autorización firmada (PDF o Imagen)
                        </label>
                        <input type="file" id="documento_firmado" name="documento_firmado" accept=".pdf,image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                        <p class="mt-1 text-xs text-slate-400">Puedes adjuntar el PDF firmado o foto del documento con tu firma.</p>
                        
                        <?php if (!empty($resenaActual['archivo_autorizacion'])): ?>
                            <div class="mt-2 flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <i class="bi bi-file-earmark-check-fill text-base"></i>
                                <span>Ya tienes un documento adjuntado.</span>
                                <a href="<?= e(url('practicante/resenas.php?descargar=mi_archivo')) ?>" target="_blank" class="ml-1 underline hover:text-emerald-700">Ver documento actual</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Consentimiento Legal (Punto 4 del documento PDF) - Alexander -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                        <label class="flex cursor-pointer items-start gap-2.5">
                            <input type="checkbox" name="consentimiento_legal" value="1" required class="rrhh-checkbox mt-0.5" checked>
                            <span>
                                <strong>Consentimiento Final:</strong> Declaro que he leído y comprendido a cabalidad el documento de autorización, aceptando expresamente la exención de responsabilidad y el tratamiento normado de mis datos para fines formativos y testimoniales.
                            </span>
                        </label>
                    </div>

                    <!-- Implemente los botones de acción para enviar el formulario - Alexander -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit" class="rrhh-button rounded-xl bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-active shadow-sm shadow-blue-500/20">
                            <i class="bi bi-send-check mr-2"></i><?= $resenaActual ? 'Actualizar Reseña' : 'Enviar Reseña' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Implementé una columna hacia la derecha, que permita ver el estado en el cual se encuentra el requisito - Alexander -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h4 class="mb-4 text-base font-bold text-slate-800 dark:text-white">Estado del Requisito</h4>
                
                <div class="space-y-4">
                    <!-- Paso 1: Redacción -->
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full <?= $resenaActual ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400' ?>">
                            <i class="bi <?= $resenaActual ? 'bi-check-lg font-bold' : 'bi-1-circle-fill' ?> text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-white">Redacción</p>
                            <p class="text-xs text-slate-500"><?= $resenaActual ? 'Formulario completado.' : 'Completa el formulario con tu opinión.' ?></p>
                        </div>
                    </div>

                    <!-- Paso 2: Recepción por supervisor -->
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full <?= $resenaActual ? 'bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' ?>">
                            <i class="bi <?= $resenaActual ? 'bi-check-lg font-bold' : 'bi-2-circle' ?> text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-white">Recepción por supervisor</p>
                            <p class="text-xs text-slate-500"><?= $resenaActual ? 'Reseña recibida por RRHH.' : 'El equipo revisará tu testimonio.' ?></p>
                        </div>
                    </div>

                    <!-- Paso 3: Publicación -->
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full <?= $estadoActual === 'PUBLICADA' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' ?>">
                            <i class="bi <?= $estadoActual === 'PUBLICADA' ? 'bi-check-circle-fill' : 'bi-3-circle' ?> text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-white">Publicación Oficial</p>
                            <p class="text-xs text-slate-500"><?= $estadoActual === 'PUBLICADA' ? 'Aprobada para testimonio.' : 'Pendiente de aprobación.' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Implementé la interactividad de las estrellas, chips y contador de caracteres - Alexander
    document.addEventListener('DOMContentLoaded', () => {
        // Estrellas
        const califInput = document.getElementById('calificacion_input');
        const botonesEstrella = document.querySelectorAll('.btn-estrella');
        const textoCalif = document.getElementById('texto_calificacion');

        const pintarEstrellas = (valor) => {
            botonesEstrella.forEach(btn => {
                const s = parseInt(btn.dataset.star, 10);
                if (s <= valor) {
                    btn.classList.remove('text-slate-300', 'dark:text-slate-700');
                    btn.classList.add('text-amber-400');
                } else {
                    btn.classList.remove('text-amber-400');
                    btn.classList.add('text-slate-300', 'dark:text-slate-700');
                }
            });
            if (textoCalif) {
                textoCalif.textContent = valor.toFixed(1) + ' de 5.0';
            }
        };

        botonesEstrella.forEach(btn => {
            btn.addEventListener('click', () => {
                const val = parseInt(btn.dataset.star, 10);
                califInput.value = val;
                pintarEstrellas(val);
            });
        });

        // Chips de aspectos destacados
        const aspectosInput = document.getElementById('aspectos_input');
        const chips = document.querySelectorAll('.chip-aspecto');

        const actualizarChipsInput = () => {
            const seleccionados = [];
            chips.forEach(c => {
                if (c.classList.contains('border-blue-300')) {
                    seleccionados.push(c.dataset.aspecto);
                }
            });
            aspectosInput.value = seleccionados.join(', ');
        };

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const activo = chip.classList.contains('border-blue-300');
                const icono = chip.querySelector('i');
                if (activo) {
                    chip.classList.remove('border-blue-300', 'bg-blue-50', 'text-blue-700', 'dark:border-blue-900', 'dark:bg-blue-950', 'dark:text-blue-300');
                    chip.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600', 'dark:border-slate-800', 'dark:bg-slate-800', 'dark:text-slate-300');
                    icono.classList.remove('bi-check2');
                    icono.classList.add('bi-plus');
                } else {
                    chip.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'dark:border-slate-800', 'dark:bg-slate-800', 'dark:text-slate-300');
                    chip.classList.add('border-blue-300', 'bg-blue-50', 'text-blue-700', 'dark:border-blue-900', 'dark:bg-blue-950', 'dark:text-blue-300');
                    icono.classList.remove('bi-plus');
                    icono.classList.add('bi-check2');
                }
                actualizarChipsInput();
            });
        });

        // Contador de caracteres
        const textarea = document.getElementById('comentario');
        const contador = document.getElementById('contador_caracteres');
        if (textarea && contador) {
            const actualizarContador = () => {
                contador.textContent = textarea.value.length + ' / 500 caracteres';
            };
            textarea.addEventListener('input', actualizarContador);
            actualizarContador();
        }
    });
</script>

<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
