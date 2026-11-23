<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.


declare(strict_types=1);

// Trasladé los campos de presentación a un componente; conserva estilos y escape de valores.
if (!function_exists('horarios_practicante_render_form')) {
    function horarios_practicante_render_form(array $horario, bool $required): void
    {
        ?>
        <div class="rrhh-table-scroll overflow-x-auto">
            <!-- Adapté esta tabla para mostrar campos etiquetados sin desplazamiento horizontal en practicantes. -->
            <!-- Identifiqué el horario para mantener sus seis columnas compactas en la gestión de escritorio. -->
            <table role="table" class="rrhh-horario-table rrhh-responsive-table rrhh-table align-middle">
                <thead role="rowgroup">
                    <tr role="row">
                        <th role="columnheader">Día</th>
                        <th role="columnheader">Con jornada</th>
                        <th role="columnheader">Entrada</th>
                        <th role="columnheader">Salida</th>
                        <th role="columnheader">Inicio colación</th>
                        <th role="columnheader">Fin colación</th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                    <?php foreach (horarios_practicante_dias() as $dia => $nombreDia): ?>
                        <?php $fila = $horario[$dia] ?? horarios_practicante_valores_iniciales()[$dia]; ?>
                        <tr role="row">
                            <td role="cell" data-label="Día"><div class="rrhh-cell-value"><?= e($nombreDia) ?></div></td>
                            <td role="cell" data-label="Con jornada"><div class="rrhh-cell-value">
                                <input
                                    class="rrhh-checkbox horario-activo"
                                    type="checkbox"
                                    name="horario[<?= e((string) $dia) ?>][activo]"
                                    value="1"
                                    <?= ($fila['activo'] ?? '0') === '1' ? 'checked' : '' ?>
                                    aria-label="Con jornada <?= e($nombreDia) ?>"
                                >
                            </div></td>
                            <td role="cell" data-label="Entrada"><div class="rrhh-cell-value">
                                <input class="rrhh-input !py-1.5 !text-xs horario-tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="horario[<?= e((string) $dia) ?>][entrada_esperada]" value="<?= e((string) ($fila['entrada_esperada'] ?? '')) ?>">
                            </div></td>
                            <td role="cell" data-label="Salida"><div class="rrhh-cell-value">
                                <input class="rrhh-input !py-1.5 !text-xs horario-tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="horario[<?= e((string) $dia) ?>][salida_esperada]" value="<?= e((string) ($fila['salida_esperada'] ?? '')) ?>">
                            </div></td>
                            <td role="cell" data-label="Inicio colación"><div class="rrhh-cell-value">
                                <input class="rrhh-input !py-1.5 !text-xs horario-tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="horario[<?= e((string) $dia) ?>][colacion_inicio]" value="<?= e((string) ($fila['colacion_inicio'] ?? '')) ?>">
                            </div></td>
                            <td role="cell" data-label="Fin colación"><div class="rrhh-cell-value">
                                <input class="rrhh-input !py-1.5 !text-xs horario-tiempo" type="text" inputmode="numeric" maxlength="5" placeholder="HH:MM" autocomplete="off" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Hora en formato 24 h, por ejemplo 14:00" data-hora24 name="horario[<?= e((string) $dia) ?>][colacion_fin]" value="<?= e((string) ($fila['colacion_fin'] ?? '')) ?>">
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($required): ?>
            <div class="rrhh-help">Debe existir al menos un día con jornada pactada para cerrar la ficha.</div>
        <?php endif; ?>
        <?php
    }
}
