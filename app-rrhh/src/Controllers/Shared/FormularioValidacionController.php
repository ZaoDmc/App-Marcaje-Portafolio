<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once MODELS_PATH . '/FormularioValidacionModel.php';
require_once SERVICES_PATH . '/FormularioValidacionService.php';

auth_requiere_rol(['ADMIN', 'SUPERVISOR']);

$pageTitle = 'Formulario validación';
$token = csrf_token('csrf_formulario_validacion');
$activeTab = (string) query_param('tab', 'generar');
$tabsPermitidas = ['generar', 'configuracion', 'carreras', 'historial'];
if (!in_array($activeTab, $tabsPermitidas, true)) {
    $activeTab = 'generar';
}

$errores = [];
$mensajeExito = $_SESSION['formulario_validacion_exito'] ?? null;
unset($_SESSION['formulario_validacion_exito']);
$valores = [
    'id_institucion' => '',
    'id_carrera' => '',
    'fecha_inicio' => '',
    'fecha_termino' => '',
    'horas_totales' => '',
    'horario_trabajo' => '',
    'horario_variable' => '',
    'unidad' => '',
    'funciones' => '',
];
$configuracion = [];
$instituciones = [];
$institucionSeleccionada = null;
$carreras = [];
$plantillas = [];
$historial = [];
$generadoId = (string) query_param('generado', '');
$dbDisponible = true;
$subidaTemporal = null;
$salidaTemporal = null;
$redirectPath = null;

if (is_post()) {
    $tabEnviado = (string) input('tab', 'generar');
    if (in_array($tabEnviado, $tabsPermitidas, true)) {
        $activeTab = $tabEnviado;
    }

    if (!csrf_validar((string) input('csrf_token', ''), 'csrf_formulario_validacion')) {
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        $accion = (string) input('accion', '');
        try {
            if ($accion === 'guardar_configuracion') {
                $configuracionNueva = [];
                $longitudes = [
                    'nombre_centro' => 150,
                    'rut_centro' => 20,
                    'direccion' => 250,
                    'comuna' => 150,
                    'giro_comercial' => 250,
                    'telefono' => 30,
                    'sitio_web' => 255,
                    'jefe_nombre' => 150,
                    'jefe_cargo' => 150,
                    'jefe_email' => 190,
                    'beneficios' => 3000,
                ];
                foreach ($longitudes as $campo => $maximo) {
                    $valor = trim((string) input($campo, ''));

                    if ($campo === 'sitio_web') {
                        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $valor)) {
                            $partesUrl = parse_url($valor);
                            $protocolo = strtolower((string) ($partesUrl['scheme'] ?? ''));

                            if (!in_array($protocolo, ['http', 'https'], true)) {
                                $errores[] = 'Ingresa una dirección que use http:// o https://.';
                                break;
                            }
                        } elseif (str_contains($valor, '://')) {
                            $errores[] = 'Ingresa una dirección web válida, por ejemplo: sitioweb.cl.';
                            break;
                        } else {
                            $valor = 'https://' . $valor;
                        }
                    }

                    if ($valor === '' || mb_strlen($valor) > $maximo) {
                        $errores[] = 'Completa los datos del centro y respeta los máximos indicados.';
                        break;
                    }

                    $configuracionNueva[$campo] = $valor;

                    if ($valor === '' || mb_strlen($valor) > $maximo) {
                        $errores[] = 'Completa los datos del centro y respeta los máximos indicados.';
                        break;
                    }
                }
                if ($errores === [] && !filter_var($configuracionNueva['jefe_email'], FILTER_VALIDATE_EMAIL)) {
                    $errores[] = 'Ingresa un correo válido para el jefe directo.';
                }
                $sitioWeb = $errores === [] ? parse_url($configuracionNueva['sitio_web']) : false;

                if (
                    $errores === []
                    && (
                        !filter_var($configuracionNueva['sitio_web'], FILTER_VALIDATE_URL)
                        || !is_array($sitioWeb)
                        || !in_array(
                            strtolower((string) ($sitioWeb['scheme'] ?? '')),
                            ['http', 'https'],
                            true
                        )
                        || empty($sitioWeb['host'])
                    )
                ) {
                    $errores[] = 'Ingresa una dirección web válida, por ejemplo: sitioweb.cl.';
                }
                if ($errores === []) {
                    formulario_validacion_guardar_configuracion($configuracionNueva);
                    auditoria_registrar('CONFIGURAR_FORMULARIO_VALIDACION', [
                        'tabla_afectada' => 'formulario_validacion_configuracion',
                        'id_registro_afectado' => 1,
                    ]);
                    $_SESSION['formulario_validacion_exito'] = 'Los datos del centro se guardaron con éxito.';
                    $redirectPath = 'formulario-validacion.php?tab=configuracion';
                }
                $configuracion = array_merge($configuracion, $configuracionNueva);
                $activeTab = 'configuracion';
            } elseif ($accion === 'guardar_plantilla_carrera') {
                $idCarrera = filter_var(input('id_carrera'), FILTER_VALIDATE_INT);
                $unidad = trim((string) input('unidad', ''));
                $funciones = trim((string) input('funciones', ''));
                if ($idCarrera === false || $idCarrera <= 0) {
                    $errores[] = 'Selecciona una carrera activa.';
                }
                if ($unidad === '' || mb_strlen($unidad) > 2000 || $funciones === '' || mb_strlen($funciones) > 8000) {
                    $errores[] = 'Completa la unidad y las funciones sugeridas respetando los máximos indicados.';
                }
                if ($errores === []) {
                    formulario_validacion_guardar_plantilla((int) $idCarrera, $unidad, $funciones);
                    auditoria_registrar('GUARDAR_PLANTILLA_FORMULARIO_VALIDACION', [
                        'tabla_afectada' => 'formulario_validacion_plantillas_carrera',
                        'id_registro_afectado' => (int) $idCarrera,
                    ]);
                    $redirectPath = 'formulario-validacion.php?tab=carreras';
                }
                $activeTab = 'carreras';
            } elseif ($accion === 'generar') {
                foreach ($valores as $campo => $_valor) {
                    $valores[$campo] = trim((string) input($campo, ''));
                }

                $instituciones = formulario_validacion_instituciones();
                $configuracion = formulario_validacion_configuracion();
                $carreras = formulario_validacion_carreras();
                $plantillas = formulario_validacion_plantillas_carrera();

                $idInstitucion = filter_var($valores['id_institucion'], FILTER_VALIDATE_INT);
                if ($idInstitucion === false || $idInstitucion <= 0) {
                    $errores[] = 'Selecciona una institución activa.';
                } else {
                    foreach ($instituciones as $institucion) {
                        if ((int) $institucion['id_institucion'] === (int) $idInstitucion) {
                            $institucionSeleccionada = $institucion;
                            break;
                        }
                    }
                    if ($institucionSeleccionada === null) {
                        $errores[] = 'La institución seleccionada no está disponible.';
                    }
                }
                $codigoInstitucion = $institucionSeleccionada === null
                    ? null
                    : formulario_validacion_institucion_codigo($institucionSeleccionada);
                if ($institucionSeleccionada !== null && $codigoInstitucion === null) {
                    $errores[] = 'La generación automática para ' . $institucionSeleccionada['nombre'] . ' aún no está disponible.';
                }

                $idCarrera = filter_var($valores['id_carrera'], FILTER_VALIDATE_INT);
                if ($idCarrera === false || $idCarrera <= 0) {
                    $errores[] = 'Selecciona una carrera para aplicar las funciones sugeridas.';
                } elseif (!array_filter($carreras, static fn(array $carrera): bool => (int) $carrera['id_carrera'] === (int) $idCarrera)) {
                    $errores[] = 'La carrera seleccionada no está disponible.';
                }

                $camposRequeridos = [
                    'fecha_inicio' => 10,
                    'fecha_termino' => 10,
                    'horas_totales' => 5,
                    'horario_trabajo' => 300,
                    'unidad' => 2000,
                    'funciones' => 8000,
                ];
                foreach ($camposRequeridos as $campo => $maximo) {
                    if ($valores[$campo] === '' || mb_strlen($valores[$campo]) > $maximo) {
                        $errores[] = 'Completa todos los antecedentes de práctica y respeta los máximos indicados.';
                        break;
                    }
                }
                if (!in_array($valores['horario_variable'], ['Sí', 'No'], true)) {
                    $errores[] = 'Indica si el horario de práctica es variable.';
                }

                $fechaInicio = DateTimeImmutable::createFromFormat('!Y-m-d', $valores['fecha_inicio']);
                $fechaTermino = DateTimeImmutable::createFromFormat('!Y-m-d', $valores['fecha_termino']);
                if ($fechaInicio === false || $fechaInicio->format('Y-m-d') !== $valores['fecha_inicio']
                    || $fechaTermino === false || $fechaTermino->format('Y-m-d') !== $valores['fecha_termino']
                    || $fechaTermino < $fechaInicio) {
                    $errores[] = 'Ingresa fechas válidas y asegúrate de que el término no sea anterior al inicio.';
                }

                if (!ctype_digit($valores['horas_totales']) || (int) $valores['horas_totales'] <= 0
                    || (int) $valores['horas_totales'] > 99999) {
                    $errores[] = 'Ingresa un número válido de horas totales.';
                }

                $configuracionCompleta = true;
                foreach ([
                    'nombre_centro', 'rut_centro', 'direccion', 'comuna', 'giro_comercial',
                    'telefono', 'sitio_web', 'jefe_nombre', 'jefe_cargo', 'jefe_email', 'beneficios',
                ] as $campo) {
                    if (trim((string) ($configuracion[$campo] ?? '')) === '') {
                        $configuracionCompleta = false;
                        break;
                    }
                }
                if (!$configuracionCompleta) {
                    $errores[] = 'Antes de generar, completa los datos compartidos del centro en Configuración.';
                }

                $archivo = $_FILES['docx'] ?? null;
                if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $errores[] = 'Selecciona el DOCX completado por el estudiante.';
                } else {
                    $subidaTemporal = (string) ($archivo['tmp_name'] ?? '');
                    if (!is_uploaded_file($subidaTemporal)) {
                        $errores[] = 'El archivo recibido no es una carga válida.';
                    } elseif (strtolower((string) pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION)) !== 'docx') {
                        $errores[] = 'El archivo debe ser un documento Word .docx.';
                    } elseif ((int) ($archivo['size'] ?? 0) < 1 || (int) $archivo['size'] > FORMULARIO_VALIDACION_MAX_BYTES) {
                        $errores[] = 'El DOCX debe pesar entre 1 byte y 5 MB.';
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeReal = (string) $finfo->file($subidaTemporal);
                        if (!in_array($mimeReal, [
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/octet-stream',
                        ], true)) {
                            $errores[] = 'El archivo no parece ser un DOCX válido.';
                        }
                    }
                }

                if ($errores === []) {
                    $datos = $configuracion;
                    $datos['fecha_inicio'] = $fechaInicio->format('d/m/Y');
                    $datos['fecha_termino'] = $fechaTermino->format('d/m/Y');
                    $datos['horas_totales'] = $valores['horas_totales'];
                    $datos['horario_trabajo'] = $valores['horario_trabajo'];
                    $datos['horario_variable'] = $valores['horario_variable'];
                    $datos['unidad'] = $valores['unidad'];
                    $datos['funciones'] = $valores['funciones'];

                    $salidaTemporal = formulario_validacion_docx_generar($subidaTemporal, $datos);
                    $directorioUploads = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads';
                    if (!is_dir($directorioUploads) && !mkdir($directorioUploads, 0750, true) && !is_dir($directorioUploads)) {
                        throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
                    }
                    $uploadsReal = realpath($directorioUploads);
                    if ($uploadsReal === false) {
                        throw new RuntimeException('No se pudo validar el almacenamiento privado.');
                    }

                    $directorioPrivado = $uploadsReal . DIRECTORY_SEPARATOR . 'formularios-validacion';
                    if (!is_dir($directorioPrivado) && !mkdir($directorioPrivado, 0750, true) && !is_dir($directorioPrivado)) {
                        throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
                    }
                    $directorioPrivadoReal = realpath($directorioPrivado);
                    if ($directorioPrivadoReal === false
                        || !str_starts_with(strtolower($directorioPrivadoReal), strtolower($uploadsReal . DIRECTORY_SEPARATOR))) {
                        throw new RuntimeException('La ruta de almacenamiento del documento no es válida.');
                    }

                    $tokenArchivo = bin2hex(random_bytes(16));
                    $nombreArchivo = 'formulario-validacion-' . gmdate('Ymd-His') . '-' . $tokenArchivo . '.docx';
                    $rutaFisica = $directorioPrivadoReal . DIRECTORY_SEPARATOR . $nombreArchivo;
                    $rutaRelativa = 'uploads/formularios-validacion/' . $nombreArchivo;
                    if (!rename($salidaTemporal, $rutaFisica)) {
                        throw new RuntimeException('No se pudo guardar el documento generado.');
                    }
                    $salidaTemporal = null;

                    try {
                        db()->beginTransaction();
                        $documentoId = formulario_validacion_guardar_documento(
                            (int) $idCarrera,
                            $rutaRelativa,
                            $nombreArchivo,
                            (int) filesize($rutaFisica),
                            $codigoInstitucion,
                            FORMULARIO_VALIDACION_VERSION_DUOC
                        );
                        db()->commit();
                    } catch (Throwable $exception) {
                        if (db()->inTransaction()) {
                            db()->rollBack();
                        }
                        if (is_file($rutaFisica) && !unlink($rutaFisica)) {
                            error_log('No se pudo eliminar un DOCX sin registro tras un error de persistencia.');
                        }
                        throw $exception;
                    }

                    auditoria_registrar('GENERAR_FORMULARIO_VALIDACION', [
                        'tabla_afectada' => 'documentos_validacion_generados',
                        'id_registro_afectado' => $documentoId,
                        'valores_nuevos' => [
                            'institucion' => $codigoInstitucion,
                            'version_plantilla' => FORMULARIO_VALIDACION_VERSION_DUOC,
                            'id_carrera' => (int) $idCarrera,
                        ],
                    ]);
                    $redirectPath = 'formulario-validacion.php?tab=historial&generado=' . $documentoId;
                }
                $activeTab = 'generar';
            } else {
                $errores[] = 'La acción solicitada no es válida.';
            }
        } catch (PDOException $exception) {
            error_log('Error de persistencia en el módulo de formulario validación.');
            $errores[] = 'No fue posible completar la operación. Verifica los datos e inténtalo nuevamente.';
        } catch (InvalidArgumentException $exception) {
            $errores[] = $exception->getMessage();
        } catch (RuntimeException $exception) {
            $errores[] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('Error al procesar el módulo de formulario validación.');
            $errores[] = 'No fue posible completar la operación. Verifica los datos e inténtalo nuevamente.';
        } finally {
            if ($subidaTemporal !== null && is_file($subidaTemporal) && !unlink($subidaTemporal)) {
                error_log('No se pudo eliminar el DOCX temporal recibido.');
            }
            if ($salidaTemporal !== null && is_file($salidaTemporal) && !unlink($salidaTemporal)) {
                error_log('No se pudo eliminar el DOCX temporal generado.');
            }
        }
    }
}

if ($redirectPath !== null) {
    redirect_to($redirectPath);
}

try {
    $configuracion = $configuracion !== [] ? $configuracion : formulario_validacion_configuracion();
    $instituciones = $instituciones !== [] ? $instituciones : formulario_validacion_instituciones();
    $carreras = $carreras !== [] ? $carreras : formulario_validacion_carreras();
    $plantillas = formulario_validacion_plantillas_carrera();
    $historial = formulario_validacion_historial();
} catch (Throwable $exception) {
    error_log('No fue posible cargar el módulo de formulario validación.');
    $dbDisponible = false;
}

$plantillasPorCarrera = [];
foreach ($plantillas as $plantilla) {
    $plantillasPorCarrera[(string) $plantilla['id_carrera']] = [
        'unidad' => (string) $plantilla['unidad'],
        'funciones' => (string) $plantilla['funciones'],
    ];
}

require SRC_PATH . '/Controllers/Shared/LayoutContext.php';
require VIEWS_PATH . '/shared/formulario-validacion.view.php';
