<?php

declare(strict_types=1);

require_once SERVICES_PATH.'/PublicacionNotasService.php';
// Evité reutilizar formularios privados desde cachés compartidas; la revisión sigue validándose en cada escritura.
header('Cache-Control: private, no-store');

// Preparé todo antes del HTML: alcance, CSRF, versiones, valores y periodos históricos.
$pageTitle='Notas'; $errores=[]; $destinatarios=[]; $seleccionado=null; $periodos=[]; $pendientes=[];
$rubricaFormulario=null; $notaFormulario=null; $editable=false; $avisoPeriodo=''; $valores=[];
$observacion=''; $notaFinal=''; $motivo=''; $revision=0;
$enConfirmacion=false; $esCorreccion=false; $prepublicacion=null; $historialRevisiones=[]; $puedePublicar=false;
$esAdmin=auth_rol_activo()==='ADMIN';
$rutaNotas=$esAdmin?'admin/notas-evaluaciones.php':'practicante/notas.php';
$_SESSION['csrf_notas_borrador'] ??= bin2hex(random_bytes(32));
$mensajeExito=$_SESSION['notas_borrador_exito'] ?? null; unset($_SESSION['notas_borrador_exito']);
$viernesEntrada=is_post()?input('viernes'):query_param('viernes');
$viernes=is_string($viernesEntrada)?$viernesEntrada:'';
$idEntrada=is_post()?input('id_practica'):query_param('id_practica');
$idPractica=$idEntrada===null?null:filter_var($idEntrada,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$migracionPendiente=false;
try {
    $actor=notas_actor();
    if (!$esAdmin && !notas_es_lider_actual()) { http_response_code(403); throw new DomainException('Solo un líder vigente puede evaluar a su equipo.'); }
    $migracionPendiente=!notas_formulario_disponible();
    if ($migracionPendiente) { throw new DomainException('Falta instalar las migraciones de notas 010 y 011. Aplica primero 010 y luego 011.'); }
    $destinatarios=notas_destinatarios();
    // Limité el selector habitual de ADMIN a líderes; conservé el alcance global del servicio para corregir notas existentes por enlace directo.
    if ($esAdmin) {
        $destinatarios=array_values(array_filter($destinatarios,static fn(array $persona): bool => (bool)$persona['es_lider']));
    }
    if ($idPractica===false) { throw new DomainException('Selecciona una práctica válida.'); }
    if ($idPractica===null && $destinatarios!==[]) { $idPractica=(int)$destinatarios[0]['id_practica']; }
    if ($idPractica!==null) {
        try { $seleccionado=notas_destinatario_visible($idPractica); }
        catch (DomainException $e) { http_response_code(403); throw $e; }
        $periodos=notas_periodos($idPractica);
        try { $pendientes=notas_pendientes($idPractica); }
        catch (DomainException $e) { $avisoPeriodo=$e->getMessage(); }
        if ($viernesEntrada===null) { $viernes=$pendientes[0] ?? ($periodos[0]['viernes'] ?? ''); }
        if ($viernes!=='') {
            notas_viernes($viernes);
            foreach ($periodos as $periodo) { if ($periodo['viernes']===$viernes) { $notaFormulario=$periodo; break; } }
            if ($notaFormulario!==null && $notaFormulario['id_nota']===null) {
                $avisoPeriodo='Este viernes ya tiene una evaluación de otro responsable. ADMIN debe resolverla.';
            } else {
                if ($notaFormulario!==null) {
                    $rubricaFormulario=$notaFormulario['rubrica']; $valores=$notaFormulario['detalle'];
                    $notaFinal=$notaFormulario['nota_final'] ?? ''; $observacion=$notaFormulario['observacion'] ?? '';
                    $revision=(int)$notaFormulario['revision'];
                    // La corrección de una publicada se abre explícitamente y conserva la versión visible hasta confirmar.
                    $esCorreccion=$notaFormulario['estado']==='APROBADA' && (query_param('corregir')==='1' || (is_post() && input('modo')==='corregir'));
                    $editable=$notaFormulario['estado']==='BORRADOR' || $esCorreccion;
                    if (!$editable) { $avisoPeriodo='Esta evaluación está aprobada y se muestra en solo lectura.'; }
                    $historialRevisiones=notas_historial_revisiones((int)$notaFormulario['id_nota']);
                    // No ofrecí la aprobación normal de integrantes a ADMIN; solo liderazgo o resolución excepcional.
                    $puedePublicar=true;
                    if ($esAdmin && $notaFormulario['estado']==='BORRADOR' && $notaFormulario['tipo_evaluado']!=='LIDER') {
                        $destinoPublicacion=notas_destino($idPractica,$viernes,(int)$notaFormulario['id_area']);
                        $puedePublicar=$destinoPublicacion['es_lider'] || notas_borrador_sin_responsable($notaFormulario);
                    }
                } else {
                    // No basta enviar un viernes libre: comprobé fechas, elegibilidad y responsabilidad.
                    if (!in_array($viernes,$pendientes,true)) { throw new DomainException('El viernes no está disponible para crear una evaluación de esta práctica.'); }
                    $rubricaFormulario=notas_rubrica_actual(); $editable=true;
                    $valores=array_fill(1,count($rubricaFormulario['criterios']),null);
                }
            }
        }
        if (is_post()) {
            $token=input('csrf_token');
            if (!is_string($token) || !hash_equals($_SESSION['csrf_notas_borrador'],$token)) {
                throw new DomainException('La sesión del formulario expiró. Recarga e intenta nuevamente.');
            }
            $accion=input('accion');
            if (!in_array($accion,['guardar','revisar_publicacion','confirmar_publicacion','volver_edicion'],true) || !$editable || $rubricaFormulario===null) { throw new DomainException('Esta evaluación no admite la operación solicitada.'); }
            $revisionEntrada=filter_var(input('revision'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            $rubricaEntrada=filter_var(input('id_rubrica'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            $notasEntrada=input('notas'); $finalEntrada=input('nota_final');
            if ($revisionEntrada===false || $rubricaEntrada===false || !is_array($notasEntrada) ||
                !is_string(input('observacion')) || !is_string(input('motivo')) || !is_string($finalEntrada)) {
                throw new DomainException('Los campos de la evaluación no tienen un formato válido.');
            }
            // Conservé entradas válidas ante errores, sin asociarlas silenciosamente a criterios diferentes.
            $observacion=input('observacion'); $motivo=input('motivo'); $notaFinal=$finalEntrada;
            $valores=array_map(static fn($valor)=>is_scalar($valor)?(string)$valor:'',$notasEntrada);
            $revision=$revisionEntrada;
            if ($rubricaEntrada!==(int)$rubricaFormulario['id_rubrica']) {
                $rubricaFormulario=notas_rubrica($rubricaEntrada); $editable=false;
                throw new DomainException('Los criterios cambiaron. Tus valores se muestran con la versión enviada; recarga para usar la vigente.');
            }
            if ($accion==='revisar_publicacion' || $accion==='confirmar_publicacion') {
                $estadoEsperado=input('estado_esperado');
                if (!is_string($estadoEsperado)) { throw new DomainException('Recarga la evaluación antes de publicar.'); }
                if ($accion==='revisar_publicacion') {
                    // La revisión previa no escribe notas; al confirmar vuelvo a validar permisos, criterios y revisión.
                    $prepublicacion=notas_preparar_publicacion($idPractica,$viernes,$notasEntrada,$finalEntrada,$observacion,$revisionEntrada,$motivo,$rubricaEntrada,$estadoEsperado);
                    $valores=$prepublicacion['valores']; $notaFinal=$prepublicacion['final']; $enConfirmacion=true;
                } else {
                    $resultado=notas_publicar($idPractica,$viernes,$notasEntrada,$finalEntrada,$observacion,$revisionEntrada,$motivo,$rubricaEntrada,$estadoEsperado,input('confirmacion')==='1');
                    $_SESSION['notas_borrador_exito']=$resultado['operacion']==='CORREGIR_NOTA_PUBLICADA'
                        ? 'Corrección publicada. Se conservó la versión anterior en el historial.'
                        : 'Evaluación aprobada y publicada. El practicante ya puede consultarla en Mis notas.';
                    redirect_to($rutaNotas.'?id_practica='.$idPractica.'&viernes='.rawurlencode($viernes),303);
                }
            } elseif ($accion==='guardar') {
                notas_guardar_borrador($idPractica,$viernes,$notasEntrada,$finalEntrada,$observacion,$revisionEntrada,$motivo,$rubricaEntrada);
                $_SESSION['notas_borrador_exito']='Borrador guardado. Todavía no es visible para el practicante evaluado.';
                redirect_to($rutaNotas.'?id_practica='.$idPractica.'&viernes='.rawurlencode($viernes),303);
            } else {
                // Volver de la confirmación conserva el contenido en pantalla sin modificar ninguna versión.
                notas_validar_valores($notasEntrada,$finalEntrada,$observacion,count($rubricaFormulario['criterios']));
            }
            // Usé PRG y mantuve el token durante la sesión para que otras pestañas conserven su control de revisión.
        }
    } elseif (is_post()) { throw new DomainException('Selecciona un practicante antes de guardar.'); }
} catch (DomainException $e) { $errores[]=$e->getMessage(); }
catch (Throwable $e) { error_log('Error en notas semanales: '.$e->getMessage()); $errores[]='No fue posible cargar o guardar las notas. Intenta nuevamente.'; }

require SRC_PATH.'/Controllers/Shared/LayoutContext.php';
require VIEWS_PATH.'/shared/notas-evaluaciones.view.php';
