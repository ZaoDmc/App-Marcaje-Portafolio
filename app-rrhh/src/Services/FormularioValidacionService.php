<?php

declare(strict_types=1);

const FORMULARIO_VALIDACION_MAX_BYTES = 5242880;
const FORMULARIO_VALIDACION_MAX_UNCOMPRESSED_BYTES = 31457280;
const FORMULARIO_VALIDACION_MAX_ENTRIES = 200;
const FORMULARIO_VALIDACION_CODIGO_DUOC = 'DUOC_UC';
const FORMULARIO_VALIDACION_VERSION_DUOC = 'duoc-validacion-centro-2026-10';

function formulario_validacion_texto_normalizar(string $texto): string
{
    $texto = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $texto = str_replace(["\u{00A0}", "\u{200B}"], ' ', $texto);
    $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = strtolower($ascii === false ? $texto : $ascii);

    return trim(preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? $texto);
}

function formulario_validacion_institucion_codigo(array $institucion): ?string
{
    if (formulario_validacion_texto_normalizar((string) ($institucion['nombre'] ?? '')) === 'duoc uc') {
        return FORMULARIO_VALIDACION_CODIGO_DUOC;
    }

    return null;
}

function formulario_validacion_docx_etiqueta(DOMXPath $xpath, DOMNode $node): string
{
    $textos = $xpath->query('.//w:t', $node);
    if ($textos === false) {
        return '';
    }

    $texto = '';
    foreach ($textos as $textoNode) {
        $texto .= $textoNode->textContent;
    }

    return formulario_validacion_texto_normalizar($texto);
}

function formulario_validacion_docx_elemento(DOMXPath $xpath, DOMNodeList $rows, int $fila, int $celda): DOMElement
{
    $row = $rows->item($fila);
    if (!$row instanceof DOMElement) {
        throw new RuntimeException('La plantilla Duoc UC no corresponde a una versión compatible.');
    }

    $cells = $xpath->query('./w:tc', $row);
    $cell = $cells === false ? false : $cells->item($celda);
    if (!$cell instanceof DOMElement) {
        throw new RuntimeException('La plantilla Duoc UC no corresponde a una versión compatible.');
    }

    return $cell;
}

function formulario_validacion_docx_verificar_etiqueta(
    DOMXPath $xpath,
    DOMElement $cell,
    string $etiqueta
): void {
    $texto = formulario_validacion_docx_etiqueta($xpath, $cell);
    if (!str_contains($texto, formulario_validacion_texto_normalizar($etiqueta))) {
        throw new RuntimeException('La plantilla Duoc UC cambió. No se generó el documento; verifica que RRHH tenga la versión vigente.');
    }
}

function formulario_validacion_docx_validar_estructura(DOMDocument $documento): array
{
    $xpath = new DOMXPath($documento);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $tables = $xpath->query('/w:document/w:body/w:tbl');
    if ($tables === false || $tables->length !== 2) {
        throw new RuntimeException('El archivo no corresponde a la plantilla Duoc UC compatible.');
    }

    $rows = $xpath->query('./w:tr', $tables->item(0));
    if ($rows === false || $rows->length !== 46) {
        throw new RuntimeException('La plantilla Duoc UC cambió. No se generó el documento; verifica que RRHH tenga la versión vigente.');
    }

    foreach ([
        [0, 0, 'FORMULARIO VALIDACIÓN CENTRO DE PRÁCTICA'],
        [3, 0, 'ANTECEDENTES DEL ESTUDIANTE'],
        [17, 0, 'ANTECEDENTES DE LA PRÁCTICA'],
        [19, 0, 'Nombre del Centro de Práctica'],
        [21, 0, 'Rol único tributario'],
        [23, 0, 'Dirección'],
        [23, 2, 'Comuna'],
        [25, 0, 'Giro comercial'],
        [27, 0, 'Teléfono'],
        [27, 2, 'Sitio Web'],
        [29, 0, 'Nombre Jefe Directo'],
        [31, 0, 'Cargo'],
        [33, 0, 'Correo electrónico Jefe Directo'],
        [35, 0, 'Fecha inicio práctica'],
        [35, 2, 'Fecha término de práctica'],
        [37, 0, 'Horario de trabajo'],
        [37, 1, 'horario de Práctica es variable'],
        [39, 0, 'Número total de horas de práctica'],
        [41, 0, 'Beneficios asociados'],
        [43, 0, 'Departamento, unidad o sección'],
        [45, 0, 'Funciones principales que realizará el estudiante'],
    ] as [$rowIndex, $cellIndex, $label]) {
        formulario_validacion_docx_verificar_etiqueta(
            $xpath,
            formulario_validacion_docx_elemento($xpath, $rows, $rowIndex, $cellIndex),
            $label
        );
    }

    return [$xpath, $rows];
}

function formulario_validacion_docx_agregar_texto(DOMDocument $documento, DOMElement $parrafo, string $texto): void
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $lineas = preg_split('/\r\n|\r|\n/', $texto);
    if ($lineas === false) {
        throw new RuntimeException('No se pudo preparar un campo del documento.');
    }

    foreach ($lineas as $indice => $linea) {
        if ($indice > 0) {
            $salto = $documento->createElementNS($namespace, 'w:br');
            $parrafo->appendChild($documento->createElementNS($namespace, 'w:r'))->appendChild($salto);
        }

        if ($linea === '') {
            continue;
        }

        $run = $documento->createElementNS($namespace, 'w:r');
        $runProperties = $documento->createElementNS($namespace, 'w:rPr');
        $font = $documento->createElementNS($namespace, 'w:rFonts');
        $font->setAttributeNS($namespace, 'w:ascii', 'Arial Narrow');
        $font->setAttributeNS($namespace, 'w:hAnsi', 'Arial Narrow');
        $runProperties->appendChild($font);
        $size = $documento->createElementNS($namespace, 'w:sz');
        $size->setAttributeNS($namespace, 'w:val', '20');
        $runProperties->appendChild($size);
        $run->appendChild($runProperties);
        $textNode = $documento->createElementNS($namespace, 'w:t');
        $textNode->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $textNode->appendChild($documento->createTextNode($linea));
        $run->appendChild($textNode);
        $parrafo->appendChild($run);
    }
}

function formulario_validacion_docx_campo_vacio(DOMXPath $xpath, DOMElement $cell): void
{
    if (trim(formulario_validacion_docx_etiqueta($xpath, $cell)) !== '') {
        throw new RuntimeException('El formulario ya contiene antecedentes de práctica en un campo que el sistema debe completar. No se sobrescribió.');
    }
}

function formulario_validacion_docx_celda_con_solo_etiqueta(
    DOMXPath $xpath,
    DOMNodeList $rows,
    int $row,
    int $cell,
    string $label
): DOMElement {
    $target = formulario_validacion_docx_elemento($xpath, $rows, $row, $cell);
    if (formulario_validacion_docx_etiqueta($xpath, $target) !== formulario_validacion_texto_normalizar($label)) {
        throw new RuntimeException('La plantilla Duoc UC cambió o el horario ya tiene datos. No se generó el documento.');
    }

    return $target;
}

function formulario_validacion_docx_generar(string $rutaEntrada, array $datos): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('El servidor no tiene habilitada la extensión ZIP requerida para procesar documentos.');
    }
    if (!is_file($rutaEntrada) || !is_readable($rutaEntrada)) {
        throw new RuntimeException('No se pudo leer el documento recibido.');
    }
    $tamanoEntrada = filesize($rutaEntrada);
    if ($tamanoEntrada === false || $tamanoEntrada < 1 || $tamanoEntrada > FORMULARIO_VALIDACION_MAX_BYTES) {
        throw new RuntimeException('El DOCX debe pesar entre 1 byte y 5 MB.');
    }

    $zipEntrada = new ZipArchive();
    if ($zipEntrada->open($rutaEntrada, ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('El archivo no es un DOCX válido o está dañado.');
    }

    $rutaTemporal = tempnam(sys_get_temp_dir(), 'rrhh-fv-');
    if ($rutaTemporal === false) {
        $zipEntrada->close();
        throw new RuntimeException('No se pudo preparar el documento de salida.');
    }

    $zipSalida = new ZipArchive();
    $salidaAbierta = false;
    try {
        if ($zipEntrada->numFiles < 1 || $zipEntrada->numFiles > FORMULARIO_VALIDACION_MAX_ENTRIES) {
            throw new RuntimeException('El DOCX no tiene una estructura válida.');
        }

        $totalExpandido = 0;
        $partes = [];
        $nombresEntradas = [];
        for ($indice = 0; $indice < $zipEntrada->numFiles; $indice++) {
            $nombre = $zipEntrada->getNameIndex($indice);
            $estadistica = $zipEntrada->statIndex($indice);
            if (!is_string($nombre) || !is_array($estadistica)
                || str_contains($nombre, "\0") || str_contains($nombre, '\\')
                || str_starts_with($nombre, '/') || preg_match('~(^|/)\.\.?(/|$)|^[A-Za-z]:~', $nombre)
                || str_contains(strtolower($nombre), 'vbaproject')
                || str_contains(strtolower($nombre), 'oleobject')
                || isset($nombresEntradas[$nombre])) {
                throw new RuntimeException('El DOCX contiene una estructura no permitida.');
            }
            $nombresEntradas[$nombre] = true;

            $totalExpandido += (int) ($estadistica['size'] ?? 0);
            if ($totalExpandido > FORMULARIO_VALIDACION_MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('El contenido interno del DOCX supera el límite permitido.');
            }

            $contenido = $zipEntrada->getFromIndex($indice);
            if (!is_string($contenido)) {
                throw new RuntimeException('No se pudo leer una parte del DOCX.');
            }
            $partes[$nombre] = $contenido;
        }

        if (!isset($partes['[Content_Types].xml'], $partes['word/document.xml'])) {
            throw new RuntimeException('El archivo no es un DOCX compatible.');
        }

        $documento = new DOMDocument('1.0', 'UTF-8');
        $documento->preserveWhiteSpace = true;
        $documento->formatOutput = false;
        $libxmlAnterior = libxml_use_internal_errors(true);
        try {
            if (stripos($partes['word/document.xml'], '<!DOCTYPE') !== false
                || !$documento->loadXML($partes['word/document.xml'], LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('El contenido XML del DOCX no es válido.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($libxmlAnterior);
        }

        [$xpath, $rows] = formulario_validacion_docx_validar_estructura($documento);
        $campos = [
            [19, 1, $datos['nombre_centro'], ''],
            [21, 1, $datos['rut_centro'], ''],
            [23, 1, $datos['direccion'], ''],
            [23, 3, $datos['comuna'], ''],
            [25, 1, $datos['giro_comercial'], ''],
            [27, 1, $datos['telefono'], ''],
            [27, 3, $datos['sitio_web'], ''],
            [29, 1, $datos['jefe_nombre'], ''],
            [31, 1, $datos['jefe_cargo'], ''],
            [33, 1, $datos['jefe_email'], ''],
            [35, 1, $datos['fecha_inicio'], ''],
            [35, 3, $datos['fecha_termino'], ''],
            [39, 1, $datos['horas_totales'], ''],
        ];
        foreach ($campos as [$fila, $indiceCelda, $valor]) {
            $celda = formulario_validacion_docx_elemento($xpath, $rows, $fila, $indiceCelda);
            formulario_validacion_docx_campo_vacio($xpath, $celda);
            $parrafo = $xpath->query('./w:p', $celda)?->item(0);
            if (!$parrafo instanceof DOMElement) {
                throw new RuntimeException('La plantilla Duoc UC cambió. No se generó el documento.');
            }
            formulario_validacion_docx_agregar_texto($documento, $parrafo, $valor);
        }

        $horarioCelda = formulario_validacion_docx_celda_con_solo_etiqueta(
            $xpath,
            $rows,
            37,
            0,
            'Horario de trabajo:'
        );
        $horarioVariableCelda = formulario_validacion_docx_celda_con_solo_etiqueta(
            $xpath,
            $rows,
            37,
            1,
            'Escriba aquí si su horario de Práctica es variable:'
        );
        formulario_validacion_docx_agregar_texto(
            $documento,
            $xpath->query('./w:p', $horarioCelda)?->item(0),
            $datos['horario_trabajo']
        );
        formulario_validacion_docx_agregar_texto(
            $documento,
            $xpath->query('./w:p', $horarioVariableCelda)?->item(0),
            $datos['horario_variable'] !== '' ? $datos['horario_variable'] : 'No aplica'
        );

        $beneficiosCell = formulario_validacion_docx_elemento($xpath, $rows, 41, 0);
        $beneficiosParagraph = $xpath->query('./w:p', $beneficiosCell)?->item(0);
        if (!$beneficiosParagraph instanceof DOMElement
            || formulario_validacion_docx_etiqueta($xpath, $beneficiosCell)
                !== formulario_validacion_texto_normalizar('Beneficios asociados (Renta mensual, movilización, colación, bus de acercamiento, otros)')) {
            throw new RuntimeException('La plantilla Duoc UC cambió. No se generó el documento.');
        }
        $beneficioParrafosPrevios = $xpath->query('./w:p[position()>1]//w:t', $beneficiosCell);
        if ($beneficioParrafosPrevios !== false && $beneficioParrafosPrevios->length > 0) {
            throw new RuntimeException('El campo de beneficios ya contiene datos. No se sobrescribió.');
        }
        $filaBeneficiosVacia = formulario_validacion_docx_elemento($xpath, $rows, 42, 0);
        formulario_validacion_docx_campo_vacio($xpath, $filaBeneficiosVacia);
        $beneficiosParagraph = $beneficiosParagraph->parentNode->appendChild($documento->createElementNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w:p'
        ));
        formulario_validacion_docx_agregar_texto($documento, $beneficiosParagraph, $datos['beneficios']);

        $unidadCell = formulario_validacion_docx_elemento($xpath, $rows, 43, 0);
        formulario_validacion_docx_verificar_etiqueta($xpath, $unidadCell, 'Departamento, unidad o sección');
        $unidadParagraph = $xpath->query('./w:p', $unidadCell)?->item(1);
        if (!$unidadParagraph instanceof DOMElement
            || trim(formulario_validacion_docx_etiqueta($xpath, $unidadParagraph)) !== '') {
            throw new RuntimeException('La plantilla Duoc UC cambió. No se generó el documento.');
        }
        formulario_validacion_docx_agregar_texto($documento, $unidadParagraph, $datos['unidad']);
        formulario_validacion_docx_campo_vacio($xpath, formulario_validacion_docx_elemento($xpath, $rows, 44, 0));

        $funcionesCell = formulario_validacion_docx_elemento($xpath, $rows, 45, 0);
        formulario_validacion_docx_verificar_etiqueta($xpath, $funcionesCell, 'Funciones principales que realizará el estudiante');
        $funcionesParrafosPrevios = $xpath->query('./w:p[position()>1]//w:t', $funcionesCell);
        if ($funcionesParrafosPrevios !== false && $funcionesParrafosPrevios->length > 0) {
            throw new RuntimeException('El campo de funciones ya contiene datos. No se sobrescribió.');
        }
        $funcionesParagraph = $funcionesCell->appendChild($documento->createElementNS(
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
            'w:p'
        ));
        formulario_validacion_docx_agregar_texto($documento, $funcionesParagraph, $datos['funciones']);

        $xmlSalida = $documento->saveXML();
        if (!is_string($xmlSalida)) {
            throw new RuntimeException('No se pudo completar el documento.');
        }
        $partes['word/document.xml'] = $xmlSalida;

        if ($zipSalida->open($rutaTemporal, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el documento de salida.');
        }
        $salidaAbierta = true;
        foreach ($partes as $nombre => $contenido) {
            if (!$zipSalida->addFromString($nombre, $contenido)) {
                throw new RuntimeException('No se pudo preparar el documento de salida.');
            }
        }
        if (!$zipSalida->close()) {
            $salidaAbierta = false;
            throw new RuntimeException('No se pudo finalizar el documento de salida.');
        }
        $salidaAbierta = false;
    } catch (Throwable $exception) {
        if ($salidaAbierta) {
            $zipSalida->close();
        }
        @unlink($rutaTemporal);
        throw $exception;
    } finally {
        $zipEntrada->close();
    }

    return $rutaTemporal;
}
