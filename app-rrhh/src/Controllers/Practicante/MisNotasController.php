<?php

declare(strict_types=1);

require_once dirname(__DIR__,2).'/bootstrap.php';
require_once SERVICES_PATH.'/PublicacionNotasService.php';
auth_requiere_rol('PRACTICANTE');

// Derivé la persona desde la cuenta, sin aceptar un id_practicante ni permitir consultar borradores propios.
header('Cache-Control: private, no-store');
$pageTitle='Mis notas'; $errores=[]; $notasPublicadas=[]; $notaPropia=null;
try {
    if (!notas_disponible()) { throw new DomainException('El módulo de notas todavía no está disponible.'); }
    $notasPublicadas=notas_propias_publicadas();
    $idTexto=query_param('id_nota');
    $id=$idTexto===null?null:filter_var($idTexto,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if ($id===false) { throw new DomainException('Selecciona una evaluación válida.'); }
    if ($id===null && $notasPublicadas!==[]) { $id=(int)$notasPublicadas[0]['id_nota']; }
    if ($id!==null) {
        try { $notaPropia=notas_consultar_propia($id); }
        catch (DomainException $e) { http_response_code(403); throw $e; }
    }
} catch (DomainException $e) { $errores[]=$e->getMessage(); }
catch (Throwable $e) { error_log('Error en consulta propia de notas: '.$e->getMessage()); $errores[]='No fue posible cargar tus notas. Intenta nuevamente.'; }
require SRC_PATH.'/Controllers/Shared/LayoutContext.php';
require VIEWS_PATH.'/practicante/mis-notas.view.php';
