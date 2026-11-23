<?php

declare(strict_types=1);

// Añadí comprobaciones de instalación sin escrituras ni datos personales; la CLI y las pruebas usan la misma verificación.
function notas_preflight(PDO $pdo): array
{
    $tablas = ['liderazgos_area', 'rubricas_notas', 'configuracion_notas', 'notas_semanales',
        'notas_semanales_detalle', 'notas_semanales_historial'];
    $resultado = [];
    $s = $pdo->prepare('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.implode(',',array_fill(0,count($tablas),'?')).')');
    $s->execute($tablas); $motores = $s->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($tablas as $tabla) { $resultado['Tabla InnoDB: '.$tabla] = ($motores[$tabla] ?? '') === 'InnoDB'; }
    // No consulté estructuras ausentes: un despliegue parcial devuelve un resultado explícito en vez de crear tablas.
    if (in_array(false, $resultado, true)) { return ['ok'=>false, 'comprobaciones'=>$resultado]; }
    $s = $pdo->query('SELECT r.criterios_json FROM configuracion_notas c LEFT JOIN rubricas_notas r ON r.id_rubrica=c.id_rubrica WHERE c.id=1');
    $config = $s->fetch(PDO::FETCH_ASSOC);
    $criteriosValidos = static function (mixed $json): bool {
        if (!is_string($json)) { return false; }
        try { $filas = json_decode($json,true,512,JSON_THROW_ON_ERROR); }
        catch (JsonException) { return false; }
        if (!is_array($filas) || !array_is_list($filas) || count($filas)<1 || count($filas)>50) { return false; }
        foreach ($filas as $fila) { if (!is_string($fila) || trim($fila)==='') { return false; } }
        return true;
    };
    $resultado['Configuración y criterios vigentes válidos'] = $config !== false && $criteriosValidos($config['criterios_json']);
    // Comprobé solo publicaciones: los borradores pueden estar incompletos por diseño.
    $publicadas = $pdo->query('SELECT n.nota_final,r.criterios_json,COUNT(d.posicion) AS filas,
        MIN(d.posicion) AS primera,MAX(d.posicion) AS ultima,
        SUM(CASE WHEN d.nota IS NULL OR d.nota<1 OR d.nota>7 OR MOD(d.nota*2,1)<>0 THEN 1 ELSE 0 END) AS invalidas
        FROM notas_semanales n LEFT JOIN rubricas_notas r ON r.id_rubrica=n.id_rubrica
        LEFT JOIN notas_semanales_detalle d ON d.id_nota=n.id_nota WHERE n.estado="APROBADA"
        GROUP BY n.id_nota,n.nota_final,r.criterios_json');
    $completas = true;
    while ($fila = $publicadas->fetch(PDO::FETCH_ASSOC)) {
        if (!$criteriosValidos($fila['criterios_json']) || $fila['nota_final']===null || (float)$fila['nota_final']<1 || (float)$fila['nota_final']>7) {
            $completas = false; continue;
        }
        $cantidad = count(json_decode($fila['criterios_json'],true,512,JSON_THROW_ON_ERROR));
        if ((int)$fila['filas']!==$cantidad || (int)$fila['primera']!==1 || (int)$fila['ultima']!==$cantidad || (int)$fila['invalidas']!==0) {
            $completas = false;
        }
    }
    $resultado['Publicaciones completas según su propia rúbrica'] = $completas;
    return ['ok'=>!in_array(false,$resultado,true), 'comprobaciones'=>$resultado];
}
