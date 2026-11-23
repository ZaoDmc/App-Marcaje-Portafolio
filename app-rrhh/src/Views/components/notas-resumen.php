<?php // Mostré un resumen de solo lectura preparado por el controlador y común a ficha, reportes y Acompañamiento. ?>
<div class="space-y-1 text-sm" data-notas-resumen>
    <div class="font-semibold">Última nota semanal aprobada</div>
    <?php if ($resumenNota === null || !$resumenNota['disponible']): ?>
        <p class="text-slate-500">Notas no disponibles en este momento.</p>
    <?php elseif ($resumenNota['ultima'] === null): ?>
        <p class="text-slate-500">Sin notas semanales aprobadas.</p>
    <?php else: ?>
        <p><strong><?= e(str_replace('.', ',', (string)$resumenNota['ultima']['nota_final'])) ?> / 7</strong></p>
        <p>Viernes <?= e(date('d/m/Y', strtotime($resumenNota['ultima']['viernes']))) ?></p>
        <p class="text-slate-500"><?= (int)$resumenNota['cantidad'] ?> evaluaciones aprobadas · Sin promedio de práctica.</p>
    <?php endif; ?>
</div>
