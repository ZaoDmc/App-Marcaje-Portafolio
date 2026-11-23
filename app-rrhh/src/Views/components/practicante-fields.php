<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.


declare(strict_types=1);

// Trasladé los campos de presentación a un componente; conserva estilos y escape de valores.
if (!function_exists('practicante_registro_render_select')) {
    function practicante_registro_render_select(string $name, array $items, string $idKey, string $labelKey, array $values, string $placeholder, string $extraAttributes = ''): void
    {
        $selected = (string) ($values[$name] ?? '');
        ?>
        <select class="rrhh-select" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $extraAttributes ?>>
            <option value=""><?= e($placeholder) ?></option>
            <?php foreach ($items as $item): ?>
                <option value="<?= e((string) $item[$idKey]) ?>" <?= $selected === (string) $item[$idKey] ? 'selected' : '' ?>>
                    <?= e((string) $item[$labelKey]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}
