<?php

declare(strict_types=1);

// Trasladé los campos de presentación a un componente; conserva estilos y escape de valores.
if (!function_exists('mis_datos_render_select')) {
    function mis_datos_render_select(string $name, array $items, string $idKey, string $labelKey, array $values, string $placeholder, bool $required = false): void
    {
        $selected = (string) ($values[$name] ?? '');
        $reqAttr = $required ? 'required' : '';
        echo '<select class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600/20 focus:border-teal-600 transition bg-white" id="' . e($name) . '" name="' . e($name) . '" ' . $reqAttr . '>';
        echo '<option value="">' . e($placeholder) . '</option>';
        foreach ($items as $item) {
            $val = (string) $item[$idKey];
            $selAttr = $selected === $val ? 'selected' : '';
            echo '<option value="' . e($val) . '" ' . $selAttr . '>' . e((string) $item[$labelKey]) . '</option>';
        }
        echo '</select>';
    }
}

if (!function_exists('mis_datos_label')) {
    function mis_datos_label(string $for, string $texto, bool $required = true): void
    {
        $star = $required ? ' <span class="text-red-500 font-bold">*</span>' : '';
        echo '<label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5" for="' . e($for) . '">' . e($texto) . $star . '</label>';
    }
}

