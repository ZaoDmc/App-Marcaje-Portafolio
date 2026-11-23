<?php

declare(strict_types=1);

// Agregué tonos completos para las clases dinámicas de PHP: Tailwind puede
// compilarlas sin concatenar nombres parciales que desaparecerían del CSS final.
if (!function_exists('rrhh_tone')) {
    function rrhh_tone(string $tone): string
    {
        return match ($tone) {
            'primary' => 'bg-blue-100 text-blue-800 border-blue-200',
            'success' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'warning' => 'bg-amber-100 text-amber-900 border-amber-200',
            'danger' => 'bg-red-100 text-red-800 border-red-200',
            'info' => 'bg-cyan-100 text-cyan-900 border-cyan-200',
            'dark' => 'bg-slate-800 text-white border-slate-700',
            'light' => 'bg-slate-50 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}

// Agregué variantes explícitas para los botones de activar/desactivar catálogo.
if (!function_exists('rrhh_outline_tone')) {
    function rrhh_outline_tone(string $tone): string
    {
        return $tone === 'danger'
            ? 'border-red-600 text-red-700 hover:bg-red-50'
            : 'border-emerald-600 text-emerald-700 hover:bg-emerald-50';
    }
}
