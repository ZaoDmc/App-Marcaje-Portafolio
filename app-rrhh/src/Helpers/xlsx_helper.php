<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Motor XLSX Nativo (Sin dependencias externas)
|--------------------------------------------------------------------------
|
| Permite:
| 1. Generar la plantilla de carga masiva en .xlsx con estilos de cabecera,
|    anchos de columna y Data Validations (listas desplegables) vinculadas
|    a una segunda hoja de 'Catalogos' alimentada desde MySQL.
| 2. Leer cualquier archivo .xlsx subido por el usuario (resolviendo
|    sharedStrings, inlineStrings y valores directos) y convertirlo al
|    mismo formato que practicante_carga_masiva_leer_csv().
|
*/

if (!function_exists('xlsx_col_letter')) {
    function xlsx_col_letter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIndex = (int) (($colIndex - $mod) / 26);
        }

        return $letter;
    }
}

if (!function_exists('xlsx_escape_xml')) {
    function xlsx_escape_xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}

if (!function_exists('xlsx_generar_plantilla_practicantes')) {
    function xlsx_generar_plantilla_practicantes(array $catalogos): string
    {
        $columnas = practicante_carga_masiva_columnas_todas();

        // 1. Extraer listas de catálogos para la Hoja 2
        $nacionalidades = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre'] ?? ''), $catalogos['nacionalidades'] ?? [])));
        $regiones = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre_region'] ?? ''), $catalogos['regiones'] ?? [])));
        $instituciones = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre'] ?? ''), $catalogos['instituciones'] ?? [])));
        $sedes = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre_sede'] ?? ''), $catalogos['sedes'] ?? [])));
        $carreras = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre'] ?? ''), $catalogos['carreras'] ?? [])));
        $tiposPractica = array_values(array_filter(array_map(fn($item) => (string) ($item['nombre'] ?? ''), $catalogos['tipos_practica'] ?? [])));

        // 2. Fila de ejemplo base
        $filaEjemplo = [
            'rut_numero' => '12345678',
            'rut_dv' => '9',
            'nombre_1' => 'Juan',
            'nombre_2' => 'Carlos',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'González',
            'email' => 'juan.perez@ejemplo.cl',
            'telefono' => '+56912345678',
            'fecha_nacimiento' => '2001-05-14',
            'sexo' => 'M',
            'nacionalidad' => $nacionalidades[0] ?? 'Chilena',
            'nacionalidad_otra' => '',
            'direccion' => 'Av. Providencia',
            'numero_direccion' => '1234',
            'departamento' => '402',
            'pais' => 'Chile',
            'region' => $regiones[0] ?? 'Región Metropolitana de Santiago',
            'comuna' => 'Providencia',
            'institucion' => $instituciones[0] ?? 'Duoc UC',
            'sede' => $sedes[0] ?? 'Sede Alameda',
            'institucion_otra' => '',
            'sede_otra' => '',
            'carrera' => $carreras[0] ?? 'Ingeniería en Informática',
            'semestre' => '6',
            'tipo_practica' => $tiposPractica[0] ?? 'Profesional',
            'horas_requeridas' => '360',
            'fecha_inicio_practica' => date('Y-m-d'),
        ];

        foreach (practicante_carga_masiva_dias_ascii() as $dia => $prefijo) {
            $conJornada = $dia <= 5;
            $filaEjemplo[$prefijo . '_entrada'] = $conJornada ? '09:00' : '';
            $filaEjemplo[$prefijo . '_salida'] = $conJornada ? '18:00' : '';
            $filaEjemplo[$prefijo . '_colacion_inicio'] = $conJornada ? '13:00' : '';
            $filaEjemplo[$prefijo . '_colacion_fin'] = $conJornada ? '14:00' : '';
        }

        // 3. Construir Sheet1 XML (Carga_Practicantes)
        $sheet1Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheet1Xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $sheet1Xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>';
        $sheet1Xml .= '<sheetFormatPr defaultRowHeight="20"/>';
        
        // Anchos de columna
        $sheet1Xml .= '<cols>';
        foreach ($columnas as $i => $col) {
            $colNum = $i + 1;
            $width = in_array($col, ['email', 'region', 'carrera', 'institucion', 'sede', 'direccion'], true) ? 28 : (str_contains($col, 'colacion') || str_contains($col, 'entrada') || str_contains($col, 'salida') ? 14 : 18);
            $sheet1Xml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $sheet1Xml .= '</cols>';

        $sheet1Xml .= '<sheetData>';
        
        // Fila 1: Encabezados (con estilo de cabecera)
        $sheet1Xml .= '<row r="1" ht="28" customHeight="1">';
        foreach ($columnas as $i => $col) {
            $ref = xlsx_col_letter($i + 1) . '1';
            $sheet1Xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . xlsx_escape_xml($col) . '</t></is></c>';
        }
        $sheet1Xml .= '</row>';

        // Fila 2: Ejemplo
        $sheet1Xml .= '<row r="2" ht="20">';
        foreach ($columnas as $i => $col) {
            $ref = xlsx_col_letter($i + 1) . '2';
            $val = (string) ($filaEjemplo[$col] ?? '');
            $sheet1Xml .= '<c r="' . $ref . '" t="inlineStr" s="2"><is><t>' . xlsx_escape_xml($val) . '</t></is></c>';
        }
        $sheet1Xml .= '</row>';
        $sheet1Xml .= '</sheetData>';

        // 4. Data Validations (Menús Desplegables en Hoja 1 para filas 2 a 300)
        $validations = [];

        // Sexo (Columna J => col 10)
        $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Valor no permitido" error="Selecciona F, M u O de la lista." sqref="J2:J300"><formula1>&quot;F,M,O&quot;</formula1></dataValidation>';

        // Nacionalidad (Columna K => col 11) -> Catalogos!$A$2:$A$N
        if ($nacionalidades !== []) {
            $maxRow = count($nacionalidades) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Nacionalidad no válida" error="Selecciona una nacionalidad de la lista." sqref="K2:K300"><formula1>Catalogos!$A$2:$A$' . $maxRow . '</formula1></dataValidation>';
        }

        // Region (Columna Q => col 17) -> Catalogos!$B$2:$B$N
        if ($regiones !== []) {
            $maxRow = count($regiones) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Región no válida" error="Selecciona una región de la lista." sqref="Q2:Q300"><formula1>Catalogos!$B$2:$B$' . $maxRow . '</formula1></dataValidation>';
        }

        // Institucion (Columna S => col 19) -> Catalogos!$C$2:$C$N
        if ($instituciones !== []) {
            $maxRow = count($instituciones) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Institución no válida" error="Selecciona una institución de la lista." sqref="S2:S300"><formula1>Catalogos!$C$2:$C$' . $maxRow . '</formula1></dataValidation>';
        }

        // Sede (Columna T => col 20) -> Catalogos!$D$2:$D$N
        if ($sedes !== []) {
            $maxRow = count($sedes) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Sede no válida" error="Selecciona una sede de la lista." sqref="T2:T300"><formula1>Catalogos!$D$2:$D$' . $maxRow . '</formula1></dataValidation>';
        }

        // Carrera (Columna W => col 23) -> Catalogos!$E$2:$E$N
        if ($carreras !== []) {
            $maxRow = count($carreras) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Carrera no válida" error="Selecciona una carrera de la lista." sqref="W2:W300"><formula1>Catalogos!$E$2:$E$' . $maxRow . '</formula1></dataValidation>';
        }

        // Tipo de Practica (Columna Y => col 25) -> Catalogos!$F$2:$F$N
        if ($tiposPractica !== []) {
            $maxRow = count($tiposPractica) + 1;
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Tipo de práctica no válido" error="Selecciona un tipo de práctica de la lista." sqref="Y2:Y300"><formula1>Catalogos!$F$2:$F$' . $maxRow . '</formula1></dataValidation>';
        }

        if ($validations !== []) {
            $sheet1Xml .= '<dataValidations count="' . count($validations) . '">' . implode('', $validations) . '</dataValidations>';
        }

        $sheet1Xml .= '</worksheet>';

        // 5. Construir Sheet2 XML (Catalogos)
        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheet2Xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheet2Xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $sheet2Xml .= '<cols>';
        $sheet2Xml .= '<col min="1" max="1" width="22" customWidth="1"/>';
        $sheet2Xml .= '<col min="2" max="2" width="35" customWidth="1"/>';
        $sheet2Xml .= '<col min="3" max="3" width="22" customWidth="1"/>';
        $sheet2Xml .= '<col min="4" max="4" width="30" customWidth="1"/>';
        $sheet2Xml .= '<col min="5" max="5" width="35" customWidth="1"/>';
        $sheet2Xml .= '<col min="6" max="6" width="25" customWidth="1"/>';
        $sheet2Xml .= '</cols>';
        $sheet2Xml .= '<sheetData>';

        // Encabezados de catálogos
        $catHeaders = ['Nacionalidades', 'Regiones', 'Instituciones', 'Sedes', 'Carreras', 'Tipos_Practica'];
        $sheet2Xml .= '<row r="1" ht="24" customHeight="1">';
        foreach ($catHeaders as $idx => $h) {
            $ref = xlsx_col_letter($idx + 1) . '1';
            $sheet2Xml .= '<c r="' . $ref . '" t="inlineStr" s="3"><is><t>' . xlsx_escape_xml($h) . '</t></is></c>';
        }
        $sheet2Xml .= '</row>';

        $maxCatFilas = max(count($nacionalidades), count($regiones), count($instituciones), count($sedes), count($carreras), count($tiposPractica));
        for ($r = 0; $r < $maxCatFilas; $r++) {
            $rowNum = $r + 2;
            $sheet2Xml .= '<row r="' . $rowNum . '">';
            $valoresFila = [
                $nacionalidades[$r] ?? null,
                $regiones[$r] ?? null,
                $instituciones[$r] ?? null,
                $sedes[$r] ?? null,
                $carreras[$r] ?? null,
                $tiposPractica[$r] ?? null,
            ];
            foreach ($valoresFila as $colIdx => $val) {
                if ($val !== null) {
                    $ref = xlsx_col_letter($colIdx + 1) . $rowNum;
                    $sheet2Xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xlsx_escape_xml($val) . '</t></is></c>';
                }
            }
            $sheet2Xml .= '</row>';
        }
        $sheet2Xml .= '</sheetData>';
        $sheet2Xml .= '</worksheet>';

        // 6. Workbook XML y relaciones
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $workbookXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $workbookXml .= '<sheets>';
        $workbookXml .= '<sheet name="Carga_Practicantes" sheetId="1" r:id="rId1"/>';
        $workbookXml .= '<sheet name="Catalogos" sheetId="2" r:id="rId2"/>';
        $workbookXml .= '</sheets>';
        $workbookXml .= '</workbook>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $workbookRelsXml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $workbookRelsXml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $workbookRelsXml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>';
        $workbookRelsXml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $workbookRelsXml .= '</Relationships>';

        $rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $rootRelsXml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rootRelsXml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rootRelsXml .= '</Relationships>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $contentTypesXml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $contentTypesXml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $contentTypesXml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $contentTypesXml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $contentTypesXml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $contentTypesXml .= '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $contentTypesXml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $contentTypesXml .= '</Types>';

        // 7. Estilos (styles.xml): Fondo turquesa (#0F766E) y texto blanco para cabecera, bordes sutiles
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $stylesXml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $stylesXml .= '<fonts count="3">';
        $stylesXml .= '<font><sz val="11"/><name val="Segoe UI"/></font>'; // 0: Normal
        $stylesXml .= '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>'; // 1: Header blanco
        $stylesXml .= '<font><b/><sz val="11"/><color rgb="FF0F766E"/><name val="Segoe UI"/></font>'; // 2: Header secundario
        $stylesXml .= '</fonts>';
        $stylesXml .= '<fills count="4">';
        $stylesXml .= '<fill><patternFill patternType="none"/></fill>';
        $stylesXml .= '<fill><patternFill patternType="gray125"/></fill>';
        $stylesXml .= '<fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/></patternFill></fill>'; // 2: Turquesa portal
        $stylesXml .= '<fill><patternFill patternType="solid"><fgColor rgb="FFF4F7FB"/></patternFill></fill>'; // 3: Fondo claro
        $stylesXml .= '</fills>';
        $stylesXml .= '<borders count="2">';
        $stylesXml .= '<border><left/><right/><top/><bottom/></border>';
        $stylesXml .= '<border><left style="thin"><color rgb="FFD9E2EC"/></left><right style="thin"><color rgb="FFD9E2EC"/></right><top style="thin"><color rgb="FFD9E2EC"/></top><bottom style="thin"><color rgb="FFD9E2EC"/></bottom></border>';
        $stylesXml .= '</borders>';
        $stylesXml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $stylesXml .= '<cellXfs count="5">';
        $stylesXml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'; // 0: Default
        $stylesXml .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'; // 1: Header principal
        $stylesXml .= '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'; // 2: Celda de datos en formato TEXTO (@)
        $stylesXml .= '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'; // 3: Header secundario
        $stylesXml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'; // 4: Celda general con borde
        $stylesXml .= '</cellXfs>';
        $stylesXml .= '</styleSheet>';

        // 8. Crear ZIP temporal en memoria/disco
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el archivo zip para la plantilla XLSX.');
        }

        $zip->addFromString('_rels/.rels', $rootRelsXml);
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);

        $zip->close();

        $contenido = file_get_contents($tmpFile);
        @unlink($tmpFile);

        if ($contenido === false) {
            throw new RuntimeException('No fue posible generar el contenido binario del archivo XLSX.');
        }

        return $contenido;
    }
}

if (!function_exists('xlsx_leer_filas')) {
    function xlsx_leer_filas(string $rutaArchivo): array
    {
        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('El archivo subido no es un archivo XLSX válido.');
        }

        // 1. Cargar Shared Strings si existen
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xmlSST = @simplexml_load_string($sharedStringsXml);
            if ($xmlSST !== false) {
                foreach ($xmlSST->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $t = '';
                        foreach ($si->r as $r) {
                            $t .= (string) $r->t;
                        }
                        $sharedStrings[] = $t;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Cargar Sheet1 (o la primera hoja)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Si no se llama sheet1.xml, buscar el primer xml en xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (str_starts_with($stat['name'] ?? '', 'xl/worksheets/') && str_ends_with($stat['name'] ?? '', '.xml')) {
                    $sheetXml = $zip->getFromName($stat['name']);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('No fue posible encontrar la hoja de cálculo dentro del archivo XLSX.');
        }

        $xmlSheet = @simplexml_load_string($sheetXml);
        if ($xmlSheet === false || !isset($xmlSheet->sheetData)) {
            return ['headers' => [], 'filas' => []];
        }

        // Función auxiliar para obtener letra de columna desde ref (ej: 'AA2' -> 'AA')
        $extraerColLetra = function(string $cellRef): string {
            return preg_replace('/[0-9]/', '', $cellRef);
        };

        // Función auxiliar para convertir letra a índice 1-based (ej: 'A' -> 1, 'AA' -> 27)
        $letraAIndice = function(string $colLetra): int {
            $len = strlen($colLetra);
            $idx = 0;
            for ($i = 0; $i < $len; $i++) {
                $idx = $idx * 26 + (ord($colLetra[$i]) - 64);
            }
            return $idx;
        };

        $filasRaw = [];
        foreach ($xmlSheet->sheetData->row as $row) {
            $rowNum = (int) ($row['r'] ?? 0);
            $filaValores = [];

            foreach ($row->c as $c) {
                $cellRef = (string) ($c['r'] ?? '');
                $colLetra = $extraerColLetra($cellRef);
                $colIndex = $letraAIndice($colLetra);
                $type = (string) ($c['t'] ?? '');
                $val = '';

                if ($type === 's') {
                    $sstIndex = (int) ($c->v ?? 0);
                    $val = $sharedStrings[$sstIndex] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } else {
                    $val = (string) ($c->v ?? '');
                }

                $filaValores[$colIndex] = trim($val);
            }

            if ($filaValores !== []) {
                $filasRaw[$rowNum] = $filaValores;
            }
        }

        if ($filasRaw === []) {
            return ['headers' => [], 'filas' => []];
        }

        // Fila 1: Encabezados
        ksort($filasRaw);
        $filaHeader = array_shift($filasRaw);
        ksort($filaHeader);

        $encabezadosCanonicos = [];
        foreach ($filaHeader as $colIdx => $headerText) {
            $encabezadosCanonicos[$colIdx] = trim(mb_strtolower((string) $headerText));
        }

        // Filas de datos
        $filas = [];
        foreach ($filasRaw as $filaData) {
            $vacia = true;
            $fila = [];

            foreach ($encabezadosCanonicos as $colIdx => $clave) {
                $val = trim((string) ($filaData[$colIdx] ?? ''));
                if ($val !== '') {
                    $vacia = false;
                }
                $fila[$clave] = $val;
            }

            if (!$vacia) {
                $filas[] = $fila;
            }
        }

        return [
            'headers' => array_values($encabezadosCanonicos),
            'filas' => $filas,
        ];
    }
}
