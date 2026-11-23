<?php

declare(strict_types=1);

// Probé el diagnóstico con transacción de solo lectura y fallos sintéticos en la base temporal, nunca en asistencia.
if (PHP_SAPI!=='cli' || !isset($fixture,$compacta)) { throw new RuntimeException('Ejecutar mediante tests/notas-semanales.php.'); }
require_once $root.'/scripts/notas-preflight-lib.php';
$fixture->exec('SET TRANSACTION READ ONLY'); $fixture->beginTransaction();
try { notaCheck(notas_preflight($fixture)['ok'], 'Preflight funciona sin permiso transaccional de escritura.'); }
finally { $fixture->rollBack(); }
$fixture->beginTransaction();
try {
    $fixture->exec('DELETE FROM notas_semanales_detalle WHERE id_nota='.$compacta['id_nota'].' AND posicion=1');
    notaCheck(!notas_preflight($fixture)['ok'], 'Preflight detecta publicación sin todos sus criterios.');
} finally { $fixture->rollBack(); }
$fixture->beginTransaction();
try {
    $fixture->exec('DELETE FROM configuracion_notas');
    notaCheck(!notas_preflight($fixture)['ok'], 'Preflight detecta falta de rúbrica activa sin crearla.');
} finally { $fixture->rollBack(); }
$fixture->exec('RENAME TABLE configuracion_notas TO config_notas_temporal');
try { notaCheck(!notas_preflight($fixture)['ok'], 'Preflight detecta migración incompleta.'); }
finally { $fixture->exec('RENAME TABLE config_notas_temporal TO configuracion_notas'); }
notaCheck(notas_preflight($fixture)['ok'], 'Los casos adversos no dejan cambios persistentes.');
