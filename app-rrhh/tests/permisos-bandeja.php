<?php
declare(strict_types=1);
// Probé clasificación, filtros y HTML con expedientes ficticios, sin consultas adicionales.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$creditos=[];
foreach(range(1,12) as $i) {
    $creditos[]=[
        'id_tiempo'=>$i,'id_practica'=>$i,'nombre_1'=>'Persona '.$i,'apellido_paterno'=>'<script>Ficticio</script>',
        'email'=>'persona'.$i.'@example.test','fecha_trabajo'=>'2026-11-05','hora_inicio'=>'17:00','hora_fin'=>'18:00',
        'estado'=>$i===1?'PENDIENTE':'APROBADO','minutos_solicitados'=>60,'minutos_aprobados'=>60,'saldo'=>0,
        'vencido'=>false,'vence_utc'=>'2026-11-13 20:00:00','motivo_solicitud'=>'Motivo <script>',
        'politica_canje'=>2,'revisado_por'=>$i===1?null:501,
        'compensaciones'=>[['id_compensacion'=>$i,'fecha'=>'2026-11-06','tipo'=>'SALIDA','hora_inicio'=>'16:00','hora_fin'=>'17:00','estado'=>$i===2?'REVISAR':'APROBADA','minutos'=>60,'minutos_aplicados'=>0,'motivo_solicitud'=>'Motivo <script>']],
    ];
}
$filtros=['estado'=>'','desde'=>'','hasta'=>'','q'=>''];
$lista=tiempo_bandeja($creditos,'tiempo',$filtros,5,1);
check($lista['total']===12 && count($lista['items'])===5 && $lista['paginas']===3,'Bandeja pagina reconocimientos sin perder total');
check($lista['conteos']===['tiempo'=>12,'compensacion'=>12],'Contadores distinguen reconocimientos y canjes');
check($lista['items'][0]['credito']['id_tiempo']===12,'Bandeja desempata por identificador descendente');
check(tiempo_bandeja($creditos,'compensacion',$filtros,5,999)['pagina']===3,'Página fuera de rango vuelve a la última disponible');
$filtrados=$filtros;$filtrados['estado']='PENDIENTE';
check(tiempo_bandeja($creditos,'tiempo',$filtrados,5,1)['total']===1,'Reconocimientos pendientes se filtran');
check(tiempo_bandeja($creditos,'compensacion',$filtrados,5,1)['items'][0]['registro']['id_compensacion']===2,'Canjes reabiertos se presentan como pendientes de revisión');
$filtrados['estado']='APROBADO';
check(tiempo_bandeja($creditos,'compensacion',$filtrados,5,1)['total']===11,'Canjes autónomos aprobados no aparecen pendientes');
$filtrados=$filtros;$filtrados['q']='PERSONA3@EXAMPLE.TEST';
check(tiempo_bandeja($creditos,'tiempo',$filtrados,5,1)['total']===1,'Buscador admite correo sin distinguir mayúsculas');
$filtrados=$filtros;$filtrados['desde']='2026-11-06';
check(tiempo_bandeja($creditos,'tiempo',$filtrados,5,1)['total']===0,'Fecha de reconocimiento se filtra por día trabajado');
check(tiempo_bandeja($creditos,'compensacion',$filtrados,5,1)['total']===12,'Fecha de canje se filtra por día compensado');
$sesionAnterior=$_SESSION;
foreach(['ADMIN','SUPERVISOR'] as $rol) {
    $_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>$rol,'id_practicante'=>1];
    $categoriaPermisos='compensacion';$rutaActual=strtolower($rol).'/permisos.php';
    $limitesPermitidos=[5,10,25,50];$limiteSeleccionado=5;$token='fixture-token';
    $bandejaTiempo=tiempo_bandeja([$creditos[2]],$categoriaPermisos,$filtros,5,1);
    ob_start();require __DIR__.'/../src/Views/shared/permisos-tiempo.view.php';$html=ob_get_clean();
    check(str_contains($html,'data-ui-open="permiso-compensacion-3"') && str_contains($html,'<dialog'),'Ambos superiores abren canje en diálogo: '.$rol);
    check(!str_contains($html,'<script>') && str_contains($html,'&lt;script&gt;'),'Detalle escapa nombre y motivos: '.$rol);
    check(!str_contains($html,'value="revisar_compensacion"'),'Canje autónomo aprobado no pide aprobación adicional: '.$rol);
    check(substr_count($html,'id="permiso-compensacion-3-titulo"')===1,'Detalle tiene título accesible único: '.$rol);
}
$_SESSION=$sesionAnterior;
