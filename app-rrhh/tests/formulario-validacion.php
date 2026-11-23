<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!class_exists(ZipArchive::class) || !class_exists(DOMDocument::class)) {
    fwrite(STDERR, "Se requieren ext-zip y ext-dom para probar la generación DOCX.\n");
    exit(2);
}

require_once dirname(__DIR__) . '/src/Services/FormularioValidacionService.php';

$checks = 0;
function formulario_validacion_assert(bool $condicion, string $mensaje): void
{
    global $checks;
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    $checks++;
}

formulario_validacion_assert(
    formulario_validacion_institucion_codigo(['nombre' => 'Duoc UC']) === FORMULARIO_VALIDACION_CODIGO_DUOC,
    'No se reconoció la institución que tiene plantilla implementada.'
);
formulario_validacion_assert(
    formulario_validacion_institucion_codigo(['nombre' => 'DUOC   UC']) === FORMULARIO_VALIDACION_CODIGO_DUOC,
    'El reconocimiento de institución no tolera diferencias de mayúsculas y espacios.'
);
formulario_validacion_assert(
    formulario_validacion_institucion_codigo(['nombre' => 'INACAP']) === null,
    'Se habilitó una institución cuya plantilla todavía no está implementada.'
);

function formulario_validacion_fixture_docx(bool $cambiarEtiqueta = false, bool $campoOcupado = false, bool $entradaMaliciosa = false): string
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $documento = new DOMDocument('1.0', 'UTF-8');
    $documento->preserveWhiteSpace = true;
    $root = $documento->createElementNS($namespace, 'w:document');
    $documento->appendChild($root);
    $body = $root->appendChild($documento->createElementNS($namespace, 'w:body'));
    $primeraTabla = $body->appendChild($documento->createElementNS($namespace, 'w:tbl'));
    $etiquetas = [
        0 => ['FORMULARIO VALIDACIÓN CENTRO DE PRÁCTICA'],
        3 => ['ANTECEDENTES DEL ESTUDIANTE'],
        5 => ['Nombre completo:', 'ESTUDIANTE FICTICIO DE PRUEBA'],
        17 => ['ANTECEDENTES DE LA PRÁCTICA'],
        19 => ['Nombre del Centro de Práctica:', ''],
        21 => ['Rol único tributario (RUT):', ''],
        23 => ['Dirección:', '', 'Comuna:', ''],
        25 => ['Giro comercial:', ''],
        27 => ['Teléfono:', '', 'Sitio Web', ''],
        29 => ['Nombre Jefe Directo:', ''],
        31 => ['Cargo', ''],
        33 => ['Correo electrónico Jefe Directo:', ''],
        35 => ['Fecha inicio práctica:', '', 'Fecha término de práctica:', ''],
        37 => ['Horario de trabajo:', 'Escriba aquí si su horario de Práctica es variable:'],
        39 => ['Número total de horas de práctica:', ''],
        41 => ['Beneficios asociados (Renta mensual, movilización, colación, bus de acercamiento, otros)'],
        43 => ['Departamento, unidad o sección en que desempeña sus funciones el estudiante:', '', ''],
        45 => ['Funciones principales que realizará el estudiante (adjuntar hoja si es necesario):'],
    ];

    for ($indiceFila = 0; $indiceFila < 46; $indiceFila++) {
        $fila = $primeraTabla->appendChild($documento->createElementNS($namespace, 'w:tr'));
        $celdas = $etiquetas[$indiceFila] ?? [''];
        if ($indiceFila === 21 && $cambiarEtiqueta) {
            $celdas[0] = 'RUT distinto no reconocido';
        }
        foreach ($celdas as $indiceCelda => $texto) {
            if ($indiceFila === 19 && $indiceCelda === 1 && $campoOcupado) {
                $texto = 'DATO PREEXISTENTE';
            }
            $celda = $fila->appendChild($documento->createElementNS($namespace, 'w:tc'));
            $parrafo = $celda->appendChild($documento->createElementNS($namespace, 'w:p'));
            if ($texto !== '') {
                $run = $parrafo->appendChild($documento->createElementNS($namespace, 'w:r'));
                $textNode = $run->appendChild($documento->createElementNS($namespace, 'w:t'));
                $textNode->appendChild($documento->createTextNode($texto));
            }
            if ($indiceFila === 43 && $indiceCelda === 0) {
                foreach ([1, 2] as $_) {
                    $celda->appendChild($documento->createElementNS($namespace, 'w:p'));
                }
            }
        }
    }

    $segundaTabla = $body->appendChild($documento->createElementNS($namespace, 'w:tbl'));
    $segundaFila = $segundaTabla->appendChild($documento->createElementNS($namespace, 'w:tr'));
    $segundaCelda = $segundaFila->appendChild($documento->createElementNS($namespace, 'w:tc'));
    $segundaCelda->appendChild($documento->createElementNS($namespace, 'w:p'));
    $body->appendChild($documento->createElementNS($namespace, 'w:sectPr'));
    $xml = $documento->saveXML();
    if (!is_string($xml)) {
        throw new RuntimeException('No se pudo crear el DOCX ficticio de prueba.');
    }
    $validacionXml = new DOMDocument();
    if (!$validacionXml->loadXML($xml, LIBXML_NONET)) {
        throw new RuntimeException('La fixture generó XML inválido.');
    }

    $ruta = tempnam(sys_get_temp_dir(), 'rrhh-fv-test-');
    if ($ruta === false) {
        throw new RuntimeException('No se pudo crear la fixture DOCX.');
    }
    $zip = new ZipArchive();
    if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
        unlink($ruta);
        throw new RuntimeException('No se pudo abrir la fixture DOCX.');
    }
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('word/document.xml', $xml);
    $zip->addFromString('word/header1.xml', '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p/></w:hdr>');
    if ($entradaMaliciosa) {
        $zip->addFromString('../outside.txt', 'rejected');
    }
    if (!$zip->close()) {
        unlink($ruta);
        throw new RuntimeException('No se pudo finalizar la fixture DOCX.');
    }

    return $ruta;
}

$datos = [
    'nombre_centro' => 'CENTRO FICTICIO',
    'rut_centro' => '00.000.000-0 (FICTICIO)',
    'direccion' => 'CALLE DE PRUEBA 123',
    'comuna' => 'COMUNA FICTICIA',
    'giro_comercial' => 'ACTIVIDAD FICTICIA',
    'telefono' => '+56 9 0000 0000',
    'sitio_web' => 'https://example.invalid',
    'jefe_nombre' => 'RESPONSABLE FICTICIO',
    'jefe_cargo' => 'CARGO FICTICIO',
    'jefe_email' => 'responsable@example.invalid',
    'fecha_inicio' => '02/11/2026',
    'fecha_termino' => '31/01/2027',
    'horas_totales' => '360',
    'horario_trabajo' => 'L-V 09:00-18:00',
    'horario_variable' => '',
    'beneficios' => 'BENEFICIOS FICTICIOS',
    'unidad' => 'UNIDAD FICTICIA',
    'funciones' => "FUNCIÓN FICTICIA 1\nFUNCIÓN FICTICIA 2",
];

$entrada = formulario_validacion_fixture_docx();
$hashEntrada = hash_file('sha256', $entrada);
$salida = null;
try {
    $salida = formulario_validacion_docx_generar($entrada, $datos);
    formulario_validacion_assert(hash_file('sha256', $entrada) === $hashEntrada, 'El generador modificó el archivo de entrada.');

    $zipSalida = new ZipArchive();
    formulario_validacion_assert($zipSalida->open($salida, ZipArchive::RDONLY) === true, 'La salida no es un DOCX válido.');
    formulario_validacion_assert($zipSalida->numFiles === 3, 'La salida no conservó las partes originales.');
    formulario_validacion_assert($zipSalida->getFromName('word/header1.xml') === '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p/></w:hdr>', 'Se modificó una parte OOXML no relacionada.');
    $xmlSalida = $zipSalida->getFromName('word/document.xml');
    $zipSalida->close();
    formulario_validacion_assert(is_string($xmlSalida), 'Falta word/document.xml en la salida.');

    $documentoSalida = new DOMDocument();
    formulario_validacion_assert($documentoSalida->loadXML($xmlSalida, LIBXML_NONET), 'El XML generado no se puede analizar.');
    $xpath = new DOMXPath($documentoSalida);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $texto = $xpath->query('//w:t');
    $contenido = '';
    foreach ($texto as $nodo) {
        $contenido .= $nodo->textContent . "\n";
    }
    formulario_validacion_assert(str_contains($contenido, 'ESTUDIANTE FICTICIO DE PRUEBA'), 'Se perdió la sección personal de prueba.');
    formulario_validacion_assert(str_contains($contenido, 'CENTRO FICTICIO'), 'No se completó el centro.');
    formulario_validacion_assert(str_contains($contenido, '02/11/2026'), 'No se completó la fecha de inicio.');
    formulario_validacion_assert(str_contains($contenido, 'No aplica'), 'No se dejó constancia del horario fijo.');
    formulario_validacion_assert(str_contains($contenido, 'BENEFICIOS FICTICIOS'), 'No se completaron los beneficios.');
    formulario_validacion_assert(str_contains($contenido, 'UNIDAD FICTICIA'), 'No se completó la unidad.');
    formulario_validacion_assert(str_contains($contenido, 'FUNCIÓN FICTICIA 2'), 'No se respetaron las líneas en las funciones.');
} finally {
    if ($salida !== null && is_file($salida)) {
        unlink($salida);
    }
    if (is_file($entrada)) {
        unlink($entrada);
    }
}

foreach ([
    ['label' => 'versión desconocida', 'cambiarEtiqueta' => true, 'campoOcupado' => false, 'malicioso' => false],
    ['label' => 'campo ocupado', 'cambiarEtiqueta' => false, 'campoOcupado' => true, 'malicioso' => false],
    ['label' => 'entrada ZIP insegura', 'cambiarEtiqueta' => false, 'campoOcupado' => false, 'malicioso' => true],
] as $caso) {
    $ruta = formulario_validacion_fixture_docx($caso['cambiarEtiqueta'], $caso['campoOcupado'], $caso['malicioso']);
    try {
        $rejected = false;
        try {
            $tmpOutput = formulario_validacion_docx_generar($ruta, $datos);
            unlink($tmpOutput);
        } catch (RuntimeException) {
            $rejected = true;
        }
        formulario_validacion_assert($rejected, 'No se rechazó ' . $caso['label'] . '.');
    } finally {
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}

$archivoInvalido = tempnam(sys_get_temp_dir(), 'rrhh-fv-invalid-');
if ($archivoInvalido === false) {
    throw new RuntimeException('No se pudo preparar el archivo inválido de prueba.');
}
file_put_contents($archivoInvalido, 'not a docx');
try {
    $rejected = false;
    try {
        formulario_validacion_docx_generar($archivoInvalido, $datos);
    } catch (RuntimeException) {
        $rejected = true;
    }
    formulario_validacion_assert($rejected, 'No se rechazó un archivo que no es DOCX.');
} finally {
    unlink($archivoInvalido);
}

echo 'Formulario validación: ' . $checks . " comprobaciones correctas.\n";
