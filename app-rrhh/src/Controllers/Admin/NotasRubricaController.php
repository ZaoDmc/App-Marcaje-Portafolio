<?php

declare(strict_types=1);

require_once dirname(__DIR__,2).'/bootstrap.php';
require_once SERVICES_PATH.'/NotasFormularioService.php';
auth_requiere_rol('ADMIN');

// Separé la configuración de criterios de la calificación; solo ADMIN crea versiones nuevas.
$pageTitle='Notas · Criterios'; $errores=[]; $criterios=[]; $descripcion=''; $motivo=''; $revision=0; $rubricaActual=null;
$_SESSION['csrf_notas_rubrica'] ??= bin2hex(random_bytes(32));
$mensajeExito=$_SESSION['notas_rubrica_exito'] ?? null; unset($_SESSION['notas_rubrica_exito']);
try {
    $actor=notas_actor(); $rubricaActual=notas_rubrica_actual();
    $criterios=$rubricaActual['criterios']; $descripcion=$rubricaActual['descripcion']; $revision=$rubricaActual['revision_config'];
    if (is_post()) {
        $token=input('csrf_token');
        if (!is_string($token) || !hash_equals($_SESSION['csrf_notas_rubrica'],$token)) { throw new DomainException('La sesión del formulario expiró. Recarga e intenta nuevamente.'); }
        $entrada=input('criterios'); $revisionEntrada=filter_var(input('revision'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!is_array($entrada) || !is_string(input('descripcion')) || !is_string(input('motivo')) || $revisionEntrada===false) {
            throw new DomainException('Los campos de la rúbrica no tienen un formato válido.');
        }
        $criterios=array_map(static fn($c)=>is_string($c)?$c:'',$entrada);
        $descripcion=input('descripcion'); $motivo=input('motivo'); $revision=$revisionEntrada;
        // Admití agregar/quitar filas también sin JavaScript, sin guardar una versión incompleta.
        if (input('accion')==='agregar') {
            if (count($criterios)>=50) { throw new DomainException('El máximo es de 50 criterios.'); }
            $criterios[]='';
        } elseif (input('quitar')!==null) {
            $indice=filter_var(input('quitar'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            if ($indice===false || !array_key_exists($indice,$criterios) || count($criterios)<=1) { throw new DomainException('Conserva al menos un criterio válido.'); }
            array_splice($criterios,$indice,1);
        } elseif (input('accion')==='guardar') {
            notas_guardar_rubrica($entrada,$descripcion,$revisionEntrada,$motivo);
            $_SESSION['notas_rubrica_exito']='Nueva versión de criterios guardada. Las evaluaciones existentes conservan su versión anterior.';
            redirect_to('admin/notas-rubrica.php',303);
        } else { throw new DomainException('Acción de criterios no válida.'); }
    }
} catch (DomainException $e) { $errores[]=$e->getMessage(); }
catch (Throwable $e) { error_log('Error en rúbricas: '.$e->getMessage()); $errores[]='No fue posible cargar o guardar los criterios.'; }
require SRC_PATH.'/Controllers/Shared/LayoutContext.php';
require VIEWS_PATH.'/admin/notas-rubrica.view.php';
