<?php

declare(strict_types=1);

// Probé la integración dentro de la base aleatoria existente, con publicaciones reales y puntajes históricos ficticios.
if (PHP_SAPI !== 'cli' || !isset($fixture, $compacta, $practicas, $usuarios)) {
    throw new RuntimeException('Ejecutar mediante tests/notas-semanales.php.');
}
require_once $root . '/src/Services/ResumenNotasService.php';
require_once $root . '/src/Models/AcompanamientoModel.php';
notaSesion($admin, 'ADMIN');
$resumenIntegrado = notas_resumen_supervision([$practicas[2], $practicas[1]]);
$esperada = $fixture->query('SELECT viernes,nota_final FROM notas_semanales WHERE estado="APROBADA" AND id_practica='.$practicas[2].' ORDER BY viernes DESC LIMIT 1')->fetch();
notaCheck($resumenIntegrado[$practicas[2]]['ultima']['viernes'] === $esperada['viernes'], 'Resumen usa el último viernes aprobado.');
notaCheck($resumenIntegrado[$practicas[2]]['ultima']['nota_final'] === $esperada['nota_final'], 'Resumen refleja la corrección publicada vigente.');
notaCheck($resumenIntegrado[$practicas[1]]['ultima'] === null && $resumenIntegrado[$practicas[1]]['cantidad'] === 0, 'Un borrador no es cero ni nota aprobada.');
notaCheck(array_keys($resumenIntegrado[$practicas[2]]['ultima']) === ['viernes','nota_final','revision'], 'Resumen no incluye motivos, observaciones ni autores internos.');
notaCheck(notas_resumen_supervision([]) === [], 'Listado vacío no consulta notas ajenas.');
notaRechaza(fn()=>notas_resumen_supervision([0]), 'no disponible');
notaRechaza(fn()=>notas_resumen_supervision([PHP_INT_MAX]), 'no disponible');
notaSesion($usuarios[1], 'SUPERVISOR');
notaCheck(notas_resumen_supervision([$practicas[2]])[$practicas[2]] === $resumenIntegrado[$practicas[2]], 'Supervisor lee el mismo resumen oficial que ADMIN.');
notaRechaza(fn()=>notas_resumen_supervision([$practicas[1]]), 'no disponible');
notaSesion($usuarios[1], 'PRACTICANTE');
notaRechaza(fn()=>notas_resumen_supervision([$practicas[2]]), 'Sin autorización');
notaSesion($usuarios[1], 'SUPERVISOR');
$fixture->exec('UPDATE usuarios_sistema SET activo=0 WHERE id_usuario='.$usuarios[1]);
notaRechaza(fn()=>notas_resumen_supervision([$practicas[2]]), 'no están habilitados');
$fixture->exec('UPDATE usuarios_sistema SET activo=1 WHERE id_usuario='.$usuarios[1]);
notaSesion($admin, 'ADMIN');
// Una carga atrasada o un borrador más reciente no cambia el significado de última semana aprobada.
$antesOrden = notas_resumen_supervision([$practicas[2]])[$practicas[2]];
$fixture->exec('UPDATE notas_semanales SET actualizado_en="2099-01-01 00:00:00" WHERE id_practica='.$practicas[2].' AND viernes<"'.$esperada['viernes'].'"');
notaCheck(notas_resumen_supervision([$practicas[2]])[$practicas[2]] === $antesOrden, 'Fecha de edición atrasada no altera el orden académico.');
acom_gestion_asegurar_tabla();
$fixture->exec('INSERT INTO acompanamiento (id_practica,puntaje_evaluacion) VALUES ('.$practicas[2].',6.2)');
notaRechaza(fn()=>acom_gestion_guardar($practicas[2], ['puntaje_evaluacion'=>7, 'evaluacion_enviada'=>1]), 'solo lectura');
notaCheck((float)$fixture->query('SELECT puntaje_evaluacion FROM acompanamiento WHERE id_practica='.$practicas[2])->fetchColumn() === 6.2, 'POST antiguo no sobrescribe puntaje histórico.');
acom_gestion_guardar($practicas[2], ['evaluacion_enviada'=>1]);
notaCheck((int)$fixture->query('SELECT evaluacion_enviada FROM acompanamiento WHERE id_practica='.$practicas[2])->fetchColumn() === 1, 'Envío institucional sigue independiente de aprobación semanal.');
notaCheck((float)$fixture->query('SELECT puntaje_evaluacion FROM acompanamiento WHERE id_practica='.$practicas[2])->fetchColumn() === 6.2, 'Guardar seguimiento conserva puntaje histórico.');
// Simulé instalación previa sin tabla de notas sin crear tablas desde una petición de lectura.
$fixture->exec('RENAME TABLE notas_semanales_historial TO historial_notas_temporal');
try {
    $incompleta = notas_resumen_supervision([$practicas[2]])[$practicas[2]];
    notaCheck(!$incompleta['disponible'] && $incompleta['ultima'] === null, 'Migración pendiente se distingue de práctica sin notas.');
} finally {
    $fixture->exec('RENAME TABLE historial_notas_temporal TO notas_semanales_historial');
}
