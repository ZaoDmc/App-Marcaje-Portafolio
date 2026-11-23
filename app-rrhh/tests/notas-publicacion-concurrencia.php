<?php

declare(strict_types=1);

// Hice competir dos confirmaciones con la misma revisión y comprobé un único efecto publicado y auditado.
if (PHP_SAPI!=='cli' || !isset($fixture,$notaLider,$admin)) { throw new RuntimeException('Ejecutar mediante tests/notas-semanales.php.'); }
$idConcurrente=(int)$notaLider['id_nota'];
$revisionConcurrente=(int)$fixture->query('SELECT revision FROM notas_semanales WHERE id_nota='.$idConcurrente)->fetchColumn();
$historialAntes=(int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial WHERE id_nota='.$idConcurrente)->fetchColumn();
$auditoriaAntes=(int)$fixture->query('SELECT COUNT(*) FROM auditoria_cambios WHERE evento="PUBLICAR_NOTA_SEMANAL" AND id_registro_afectado='.$idConcurrente)->fetchColumn();
$procesos=[]; $fixture->beginTransaction();
try {
    $fixture->query('SELECT id_practica FROM practicas WHERE id_practica='.$practicas[1].' FOR UPDATE')->fetchAll();
    for ($i=0;$i<2;$i++) {
        $token=bin2hex(random_bytes(4)); $canales=[];
        $proceso=proc_open([PHP_BINARY,$root.'/tests/notas-publicacion-worker.php',$database,(string)$admin,(string)$idConcurrente,$token],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$canales,$root);
        if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar la confirmación concurrente.'); }
        fclose($canales[0]); $procesos[]=[$proceso,$canales,$sessionDir.'/'.$database.'-'.$token.'.ready'];
    }
    $limite=microtime(true)+5;
    foreach ($procesos as [$proceso,$canales,$ready]) {
        while (!is_file($ready) && microtime(true)<$limite) { usleep(20000); }
        notaCheck(is_file($ready),'La confirmación concurrente se preparó con la revisión anterior.');
    }
    usleep(250000);
    foreach ($procesos as [$proceso]) { notaCheck(proc_get_status($proceso)['running'],'La publicación espera antes de liberar el bloqueo.'); }
    $fixture->commit();
} finally { if ($fixture->inTransaction()) { $fixture->rollBack(); } }
$salidas=[];
foreach ($procesos as [$proceso,$canales]) {
    $salidas[]=stream_get_contents($canales[1]); $error=stream_get_contents($canales[2]);
    fclose($canales[1]); fclose($canales[2]);
    notaCheck(proc_close($proceso)===0 && $error==='', 'El proceso concurrente termina sin error técnico.');
}
sort($salidas);
notaCheck($salidas===['OBSOLETA','PUBLICADA'],'Solo una confirmación publica; la otra se rechaza como obsoleta.');
$trasCarrera=$fixture->query('SELECT estado,revision FROM notas_semanales WHERE id_nota='.$idConcurrente)->fetch();
notaCheck($trasCarrera['estado']==='APROBADA' && (int)$trasCarrera['revision']===$revisionConcurrente+1,'Una sola revisión tras publicar simultáneamente.');
notaCheck((int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial WHERE id_nota='.$idConcurrente)->fetchColumn()===$historialAntes+1,'Una sola instantánea nueva en historial.');
notaCheck((int)$fixture->query('SELECT COUNT(*) FROM auditoria_cambios WHERE evento="PUBLICAR_NOTA_SEMANAL" AND id_registro_afectado='.$idConcurrente)->fetchColumn()===$auditoriaAntes+1,'Una sola auditoría de publicación.');
