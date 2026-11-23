<?php
declare(strict_types=1);
// Repetí los tres parches sobre la base E2E poblada y comparé datos/corte sin imprimir registros personales.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$archivo=realpath($argv[1]??'');$base=realpath($root.'/scratch/phase8');
if($archivo===false || $base===false || !str_starts_with($archivo,$base.DIRECTORY_SEPARATOR)){throw new RuntimeException('Fixture aislada requerida.');}
$f=json_decode(file_get_contents($archivo),true,512,JSON_THROW_ON_ERROR);
if(!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)){throw new RuntimeException('Base ficticia local requerida.');}
$pdo=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$tablas=['tipos_marca','permisos_practicante','jornadas_asistencia','marcas_asistencia','feriados','recuperaciones_permiso','politica_tiempo_excepcional','extras_historicas','tiempos_excepcionales','compensaciones_tiempo'];
function migracion_huella(PDO $pdo,string $tabla): string {
    // Ordené las filas serializadas para comparar todo el contenido sin depender del plan de lectura SQL.
    $filas=array_map(static fn(array $fila):string=>json_encode($fila,JSON_THROW_ON_ERROR),$pdo->query('SELECT * FROM '.$tabla)->fetchAll());
    sort($filas,SORT_STRING);return hash('sha256',implode("\n",$filas));
}
$antes=[];foreach($tablas as $tabla){$antes[$tabla]=migracion_huella($pdo,$tabla);}
// Conservé el orden 006 → 007 → 008 al adoptar los nombres fechados.
foreach(['2026-10-14_patch_permisos_mismo_dia_006.sql','2026-10-14_patch_recuperaciones_varios_dias_007.sql','2026-10-15_patch_tiempo_excepcional_008.sql'] as $parche){
    $pdo->exec(preg_replace('/^\xEF\xBB\xBF/','',file_get_contents($root.'/database/'.$parche)));
}
foreach($tablas as $tabla){if($antes[$tabla]!==migracion_huella($pdo,$tabla)){throw new RuntimeException('La repetición alteró datos existentes en '.$tabla);}}
echo "Migraciones: 10 tablas sin cambios de datos; corte, históricos, solicitudes y canjes conservados.\n";
