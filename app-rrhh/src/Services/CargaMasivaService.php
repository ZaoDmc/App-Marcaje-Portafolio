<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Models/PracticanteModel.php';
require_once __DIR__ . '/../Helpers/xlsx_helper.php';

/*
|--------------------------------------------------------------------------
| Carga masiva de practicantes via CSV
|--------------------------------------------------------------------------
|
| Reutiliza practicante_registro_normalizar/validar/guardar por fila: este
| modulo solo agrega parseo de CSV, resolucion de catalogos por nombre y
| mapeo de columnas de horario hacia la forma que ya espera el registro
| individual. Las reglas de negocio (unicidad, transaccion, correo,
| auditoria) siguen viviendo en practicante_registro.php.
|
*/

const CARGA_MASIVA_MAX_FILAS = 300;
const CARGA_MASIVA_MAX_BYTES = 5 * 1024 * 1024;

if (!function_exists('practicante_carga_masiva_dias_ascii')) {
    function practicante_carga_masiva_dias_ascii(): array
    {
        return [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];
    }
}

if (!function_exists('practicante_carga_masiva_columnas_base')) {
    function practicante_carga_masiva_columnas_base(): array
    {
        return [
            'rut_numero', 'rut_dv', 'nombre_1', 'nombre_2', 'apellido_paterno', 'apellido_materno',
            'email', 'telefono', 'fecha_nacimiento', 'sexo', 'nacionalidad', 'nacionalidad_otra', 'direccion',
            'numero_direccion', 'departamento', 'pais', 'region', 'comuna', 'institucion', 'sede',
            'institucion_otra', 'sede_otra', 'carrera', 'semestre', 'tipo_practica',
            'horas_requeridas', 'horas_previas', 'fecha_inicio_practica',
        ];
    }
}

if (!function_exists('practicante_carga_masiva_columnas_horario')) {
    function practicante_carga_masiva_columnas_horario(): array
    {
        $columnas = [];

        foreach (practicante_carga_masiva_dias_ascii() as $prefijo) {
            foreach (['entrada', 'salida', 'colacion_inicio', 'colacion_fin'] as $sufijo) {
                $columnas[] = $prefijo . '_' . $sufijo;
            }
        }

        return $columnas;
    }
}

if (!function_exists('practicante_carga_masiva_columnas_todas')) {
    function practicante_carga_masiva_columnas_todas(): array
    {
        return array_merge(practicante_carga_masiva_columnas_base(), practicante_carga_masiva_columnas_horario());
    }
}

if (!function_exists('practicante_carga_masiva_columnas_requeridas')) {
    function practicante_carga_masiva_columnas_requeridas(): array
    {
        return ['nombre_1', 'apellido_paterno', 'email'];
    }
}

if (!function_exists('practicante_carga_masiva_leer_csv')) {
    function practicante_carga_masiva_leer_csv(string $rutaTemporal): array
    {
        $contenido = file_get_contents($rutaTemporal);

        if ($contenido === false) {
            throw new RuntimeException('No fue posible leer el archivo subido.');
        }

        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $convertido = @mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
            $contenido = $convertido !== false ? $convertido : $contenido;
        }

        $primeraLinea = '';
        foreach (preg_split("/\r\n|\r|\n/", $contenido) as $linea) {
            if (trim($linea) !== '') {
                $primeraLinea = $linea;
                break;
            }
        }
        $delimitador = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contenido);
        rewind($stream);

        $encabezados = fgetcsv($stream, 0, $delimitador);

        if (!is_array($encabezados)) {
            fclose($stream);

            return ['headers' => [], 'filas' => []];
        }

        $encabezadosCanonicos = [];
        foreach ($encabezados as $indice => $encabezado) {
            $encabezadosCanonicos[$indice] = trim(mb_strtolower((string) $encabezado));
        }

        $filas = [];
        while (($valores = fgetcsv($stream, 0, $delimitador)) !== false) {
            if (!is_array($valores)) {
                continue;
            }

            $vacia = true;
            foreach ($valores as $valor) {
                if (trim((string) $valor) !== '') {
                    $vacia = false;
                    break;
                }
            }

            if ($vacia) {
                continue;
            }

            $fila = [];
            foreach ($encabezadosCanonicos as $indice => $clave) {
                $fila[$clave] = trim((string) ($valores[$indice] ?? ''));
            }
            $filas[] = $fila;
        }

        fclose($stream);

        return ['headers' => array_values($encabezadosCanonicos), 'filas' => $filas];
    }
}

if (!function_exists('practicante_carga_masiva_validar_encabezados')) {
    function practicante_carga_masiva_validar_encabezados(array $headers): array
    {
        $errores = [];

        foreach (practicante_carga_masiva_columnas_requeridas() as $columna) {
            if (!in_array($columna, $headers, true)) {
                $errores[] = "Falta la columna obligatoria '{$columna}' en el archivo.";
            }
        }

        return $errores;
    }
}

if (!function_exists('practicante_carga_masiva_normalizar_clave')) {
    function practicante_carga_masiva_normalizar_clave(string $texto): string
    {
        // Tolera diferencias de tipeo entre personas distintas: mayusculas/minusculas,
        // tildes, apostrofos/guiones y espacios repetidos no deben producir un error de
        // "no existe" cuando el valor es, en esencia, el mismo.
        $texto = trim(mb_strtolower($texto, 'UTF-8'));
        $texto = str_replace(
            ['á', 'à', 'ä', 'â', 'é', 'è', 'ë', 'ê', 'í', 'ì', 'ï', 'î', 'ó', 'ò', 'ö', 'ô', 'ú', 'ù', 'ü', 'û', 'ñ'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'n'],
            $texto
        );
        // Los apostrofos se eliminan sin dejar espacio (O'Higgins -> ohiggins) para que
        // "OHiggins" y "O'Higgins" normalicen igual.
        $texto = str_replace(["'", "’", '`'], '', $texto);
        $texto = (string) preg_replace('/[^a-z0-9]+/', ' ', $texto);
        $texto = (string) preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }
}

if (!function_exists('practicante_carga_masiva_buscar_por_nombre')) {
    function practicante_carga_masiva_buscar_por_nombre(array $items, string $campoNombre, string $nombre, ?array $filtroScope = null): ?array
    {
        $nombreNormalizado = practicante_carga_masiva_normalizar_clave($nombre);

        foreach ($items as $item) {
            if (practicante_carga_masiva_normalizar_clave((string) ($item[$campoNombre] ?? '')) !== $nombreNormalizado) {
                continue;
            }

            if ($filtroScope !== null && (string) ($item[$filtroScope['campo']] ?? '') !== (string) $filtroScope['valor']) {
                continue;
            }

            return $item;
        }

        return null;
    }
}

if (!function_exists('practicante_carga_masiva_alias_regiones')) {
    function practicante_carga_masiva_alias_regiones(): array
    {
        // Clave: fragmento normalizado distintivo del nombre oficial de la región.
        // Valor: alias adicionales normalizados que distintas personas suelen usar
        // (numeración romana, sigla "RM", nombre corto sin "Región de/del").
        return [
            'arica y parinacota' => ['xv', 'arica'],
            'tarapaca' => ['i', 'tarapaca'],
            'antofagasta' => ['ii', 'antofagasta'],
            'atacama' => ['iii', 'atacama'],
            'coquimbo' => ['iv', 'coquimbo'],
            'valparaiso' => ['v', 'valparaiso'],
            'metropolitana de santiago' => ['rm', 'metropolitana', 'santiago'],
            'libertador general bernardo ohiggins' => ['vi', 'ohiggins', 'libertador bernardo ohiggins', 'libertador ohiggins'],
            'maule' => ['vii', 'maule'],
            'nuble' => ['xvi', 'nuble'],
            'biobio' => ['viii', 'biobio', 'bio bio'],
            'la araucania' => ['ix', 'araucania'],
            'los rios' => ['xiv', 'los rios'],
            'los lagos' => ['x', 'los lagos'],
            'aysen del general carlos ibanez del campo' => ['xi', 'aysen'],
            'magallanes y de la antartica chilena' => ['xii', 'magallanes', 'magallanes y antartica'],
        ];
    }
}

if (!function_exists('practicante_carga_masiva_resolver_region')) {
    function practicante_carga_masiva_resolver_region(array $regiones, string $nombreRegion): ?array
    {
        $normalizadoInput = practicante_carga_masiva_normalizar_clave($nombreRegion);

        // 1. Coincidencia directa contra el nombre oficial (tolerante a tildes/mayusculas).
        foreach ($regiones as $region) {
            if (practicante_carga_masiva_normalizar_clave((string) $region['nombre_region']) === $normalizadoInput) {
                return $region;
            }
        }

        // 2. Alias conocidos: numeración romana, "RM", nombre corto sin "Región de/del".
        // Se prueba tanto el texto tal cual como sin el prefijo "Región (de/del) ", porque
        // "Región Metropolitana" y "Metropolitana" deben resolver a la misma región.
        $normalizadoSinPrefijo = trim((string) preg_replace('/^region\s+(de\s+|del\s+)?/', '', $normalizadoInput));

        foreach (practicante_carga_masiva_alias_regiones() as $fragmento => $alias) {
            $coincideFragmento = $normalizadoInput === $fragmento || $normalizadoSinPrefijo === $fragmento;
            $coincideAlias = in_array($normalizadoInput, $alias, true) || in_array($normalizadoSinPrefijo, $alias, true);

            if (!$coincideFragmento && !$coincideAlias) {
                continue;
            }

            foreach ($regiones as $region) {
                if (str_contains(practicante_carga_masiva_normalizar_clave((string) $region['nombre_region']), $fragmento)) {
                    return $region;
                }
            }
        }

        return null;
    }
}

if (!function_exists('practicante_carga_masiva_normalizar_sexo')) {
    function practicante_carga_masiva_normalizar_sexo(string $valor): string
    {
        $normalizado = practicante_carga_masiva_normalizar_clave($valor);

        return match (true) {
            in_array($normalizado, ['f', 'femenino', 'mujer'], true) => 'F',
            in_array($normalizado, ['m', 'masculino', 'hombre'], true) => 'M',
            in_array($normalizado, ['o', 'otro', 'otra', 'no binario', 'no binaria'], true) => 'O',
            // Si no coincide con ningun alias conocido, se deja tal cual para que la
            // validación existente de practicante_registro.php la rechace con su mensaje.
            default => strtoupper(trim($valor)),
        };
    }
}

if (!function_exists('practicante_carga_masiva_resolver_catalogos')) {
    function practicante_carga_masiva_resolver_catalogos(array $fila, array $catalogos): array
    {
        $data = [
            'id_region' => '',
            'id_comuna' => '',
            'id_institucion' => '',
            'id_sede' => '',
            'institucion_otra' => trim((string) ($fila['institucion_otra'] ?? '')),
            'sede_otra' => trim((string) ($fila['sede_otra'] ?? '')),
            'id_carrera' => '',
            'id_tipo_practica' => '',
            'id_nacionalidad' => '',
            'nacionalidad_otra' => '',
        ];
        $errores = [];

        $nombreRegion = trim((string) ($fila['region'] ?? ''));
        $idRegionResuelta = null;

        if ($nombreRegion !== '') {
            $region = practicante_carga_masiva_resolver_region($catalogos['regiones'], $nombreRegion);

            if ($region === null) {
                $errores[] = "Región '{$nombreRegion}' no existe. Usa el nombre oficial (ej. 'Región Metropolitana de Santiago'), su sigla (ej. 'RM') o su numeración romana (ej. 'V').";
            } else {
                $idRegionResuelta = (int) $region['id_region'];
                $data['id_region'] = (string) $idRegionResuelta;
            }
        }

        $nombreComuna = trim((string) ($fila['comuna'] ?? ''));

        if ($nombreComuna !== '') {
            if ($idRegionResuelta === null) {
                $errores[] = "Debes indicar región para validar la comuna '{$nombreComuna}'.";
            } else {
                $comuna = practicante_carga_masiva_buscar_por_nombre(
                    $catalogos['comunas'],
                    'nombre_comuna',
                    $nombreComuna,
                    ['campo' => 'id_region', 'valor' => $idRegionResuelta]
                );

                if ($comuna === null) {
                    $errores[] = "Comuna '{$nombreComuna}' no existe para la región '{$nombreRegion}'.";
                } else {
                    $data['id_comuna'] = (string) $comuna['id_comuna'];
                }
            }
        }

        $nombreInstitucion = trim((string) ($fila['institucion'] ?? ''));

        if ($nombreInstitucion !== '') {
            $institucion = practicante_carga_masiva_buscar_por_nombre($catalogos['instituciones'], 'nombre', $nombreInstitucion);

            if ($institucion === null) {
                $errores[] = "Institución '{$nombreInstitucion}' no existe.";
            } else {
                $data['id_institucion'] = (string) $institucion['id_institucion'];
                $institucionEsOtra = strcasecmp((string) $institucion['nombre'], 'Otra') === 0;
                $nombreSede = trim((string) ($fila['sede'] ?? ''));

                if (!$institucionEsOtra && $nombreSede !== '') {
                    $sede = practicante_carga_masiva_buscar_por_nombre(
                        $catalogos['sedes'],
                        'nombre_sede',
                        $nombreSede,
                        ['campo' => 'id_institucion', 'valor' => (int) $institucion['id_institucion']]
                    );

                    if ($sede === null) {
                        $errores[] = "Sede '{$nombreSede}' no existe para la institución '{$nombreInstitucion}'.";
                    } else {
                        $data['id_sede'] = (string) $sede['id_sede'];
                    }
                }
            }
        }

        $nombreNacionalidad = trim((string) ($fila['nacionalidad'] ?? ''));

        if ($nombreNacionalidad !== '') {
            $nacionalidad = practicante_carga_masiva_buscar_por_nombre($catalogos['nacionalidades'], 'nombre', $nombreNacionalidad);

            if ($nacionalidad === null) {
                $errores[] = "Nacionalidad '{$nombreNacionalidad}' no existe. Usa un valor del catálogo o 'Otra' junto con la columna nacionalidad_otra.";
            } else {
                $data['id_nacionalidad'] = (string) $nacionalidad['id_nacionalidad'];
                $nacionalidadEsOtra = strcasecmp((string) $nacionalidad['nombre'], 'Otra') === 0;
                $data['nacionalidad_otra'] = $nacionalidadEsOtra ? trim((string) ($fila['nacionalidad_otra'] ?? '')) : '';
            }
        }

        $nombreCarrera = trim((string) ($fila['carrera'] ?? ''));

        if ($nombreCarrera !== '') {
            $carrera = practicante_carga_masiva_buscar_por_nombre($catalogos['carreras'], 'nombre', $nombreCarrera);

            if ($carrera === null) {
                $errores[] = "Carrera '{$nombreCarrera}' no existe.";
            } else {
                $data['id_carrera'] = (string) $carrera['id_carrera'];
            }
        }

        $nombreTipoPractica = trim((string) ($fila['tipo_practica'] ?? ''));

        if ($nombreTipoPractica !== '') {
            $tipoPractica = practicante_carga_masiva_buscar_por_nombre($catalogos['tipos_practica'], 'nombre', $nombreTipoPractica);

            if ($tipoPractica === null) {
                $errores[] = "Tipo de práctica '{$nombreTipoPractica}' no existe.";
            } else {
                $data['id_tipo_practica'] = (string) $tipoPractica['id_tipo_practica'];
            }
        }

        return ['data' => $data, 'errores' => $errores];
    }
}

if (!function_exists('practicante_carga_masiva_horario_desde_fila')) {
    function practicante_carga_masiva_horario_desde_fila(array $fila): array
    {
        $horario = [];

        foreach (practicante_carga_masiva_dias_ascii() as $dia => $prefijo) {
            $entrada = trim((string) ($fila[$prefijo . '_entrada'] ?? ''));
            $salida = trim((string) ($fila[$prefijo . '_salida'] ?? ''));
            $colacionInicio = trim((string) ($fila[$prefijo . '_colacion_inicio'] ?? ''));
            $colacionFin = trim((string) ($fila[$prefijo . '_colacion_fin'] ?? ''));

            $filaDia = [
                'entrada_esperada' => $entrada,
                'salida_esperada' => $salida,
                'colacion_inicio' => $colacionInicio,
                'colacion_fin' => $colacionFin,
            ];

            // Solo se marca 'activo' cuando hay entrada y salida: horarios_practicante_normalizar()
            // usa isset() sobre esta clave, igual que el checkbox del formulario individual.
            if ($entrada !== '' && $salida !== '') {
                $filaDia['activo'] = '1';
            }

            $horario[$dia] = $filaDia;
        }

        return $horario;
    }
}

if (!function_exists('practicante_carga_masiva_fila_a_datos')) {
    function practicante_carga_masiva_fila_a_datos(array $fila, array $catalogos): array
    {
        $resuelto = practicante_carga_masiva_resolver_catalogos($fila, $catalogos);

        $data = array_merge($resuelto['data'], [
            'rut_numero' => (string) ($fila['rut_numero'] ?? ''),
            'rut_dv' => (string) ($fila['rut_dv'] ?? ''),
            'nombre_1' => (string) ($fila['nombre_1'] ?? ''),
            'nombre_2' => (string) ($fila['nombre_2'] ?? ''),
            'apellido_paterno' => (string) ($fila['apellido_paterno'] ?? ''),
            'apellido_materno' => (string) ($fila['apellido_materno'] ?? ''),
            'email' => (string) ($fila['email'] ?? ''),
            'telefono' => (string) ($fila['telefono'] ?? ''),
            'fecha_nacimiento' => (string) ($fila['fecha_nacimiento'] ?? ''),
            'sexo' => practicante_carga_masiva_normalizar_sexo((string) ($fila['sexo'] ?? '')),
            'direccion' => (string) ($fila['direccion'] ?? ''),
            'numero_direccion' => (string) ($fila['numero_direccion'] ?? ''),
            'departamento' => (string) ($fila['departamento'] ?? ''),
            'pais' => trim((string) ($fila['pais'] ?? '')) ?: 'Chile', //valor predeterminado como "chile"
            'semestre' => (string) ($fila['semestre'] ?? ''),
            'horas_requeridas' => (string) ($fila['horas_requeridas'] ?? ''),
            'horas_previas' => (string) ($fila['horas_previas'] ?? '0'),
            'fecha_inicio_practica' => (string) ($fila['fecha_inicio_practica'] ?? ''),
            'horario' => practicante_carga_masiva_horario_desde_fila($fila),
        ]);

        return ['data' => $data, 'errores' => $resuelto['errores']];
    }
}

if (!function_exists('practicante_carga_masiva_procesar')) {
    function practicante_carga_masiva_procesar(array $filas, array $catalogos): array
    {
        $resultados = [];
        $creados = 0;
        $fallidos = 0;

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2; // fila 1 es el encabezado
            $nombre = trim((string) ($fila['nombre_1'] ?? '') . ' ' . (string) ($fila['apellido_paterno'] ?? ''));
            $email = (string) ($fila['email'] ?? '');

            $preparado = practicante_carga_masiva_fila_a_datos($fila, $catalogos);
            $normalizado = practicante_registro_normalizar($preparado['data'], $catalogos);
            $errores = array_merge(
                $preparado['errores'],
                practicante_registro_validar($normalizado, $catalogos)
            );

            if ($errores !== []) {
                $fallidos++;
                $resultados[] = [
                    'numero_fila' => $numeroFila,
                    'nombre' => $nombre,
                    'email' => $email,
                    'ok' => false,
                    'errores' => array_values(array_unique($errores)),
                    'url_activacion' => null,
                ];

                continue;
            }

            $resultado = practicante_registro_guardar($normalizado);

            if ($resultado['ok'] ?? false) {
                $creados++;
                $resultados[] = [
                    'numero_fila' => $numeroFila,
                    'nombre' => $nombre,
                    'email' => $email,
                    'ok' => true,
                    'errores' => [],
                    'url_activacion' => $resultado['url_activacion'] ?? null,
                ];
            } else {
                $fallidos++;
                $resultados[] = [
                    'numero_fila' => $numeroFila,
                    'nombre' => $nombre,
                    'email' => $email,
                    'ok' => false,
                    'errores' => $resultado['errores'] ?? ['No fue posible guardar el pre-registro.'],
                    'url_activacion' => null,
                ];
            }
        }

        return [
            'resultados' => $resultados,
            'total' => count($filas),
            'creados' => $creados,
            'fallidos' => $fallidos,
        ];
    }
}

if (!function_exists('practicante_carga_masiva_generar_plantilla_csv')) {
    function practicante_carga_masiva_generar_plantilla_csv(): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, practicante_carga_masiva_columnas_todas(), ';');

        $filaEjemplo = [
            'rut_numero' => '12345678',
            'rut_dv' => '9',
            'nombre_1' => 'Juan',
            'nombre_2' => '',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Soto',
            'email' => 'juan.perez@ejemplo.cl',
            'telefono' => '+56912345678',
            'fecha_nacimiento' => '2000-05-14',
            'sexo' => 'M',
            'nacionalidad' => 'Chilena',
            'nacionalidad_otra' => '',
            'direccion' => 'Av. Siempre Viva',
            'numero_direccion' => '123',
            'departamento' => '',
            'pais' => 'Chile',
            'region' => 'Región Metropolitana de Santiago',
            'comuna' => 'Santiago',
            'institucion' => 'Duoc UC',
            'sede' => 'Sede Alameda',
            'institucion_otra' => '',
            'sede_otra' => '',
            'carrera' => 'Ingeniería en Informática',
            'semestre' => '5',
            'tipo_practica' => 'Laboral',
            'horas_requeridas' => '450',
            'horas_previas' => '0',
            'fecha_inicio_practica' => date('Y-m-d'),
        ];

        foreach (practicante_carga_masiva_dias_ascii() as $dia => $prefijo) {
            $conJornada = $dia <= 5;
            $filaEjemplo[$prefijo . '_entrada'] = $conJornada ? '09:00' : '';
            $filaEjemplo[$prefijo . '_salida'] = $conJornada ? '18:00' : '';
            $filaEjemplo[$prefijo . '_colacion_inicio'] = $conJornada ? '13:00' : '';
            $filaEjemplo[$prefijo . '_colacion_fin'] = $conJornada ? '14:00' : '';
        }

        $valoresOrdenados = [];
        foreach (practicante_carga_masiva_columnas_todas() as $columna) {
            $valoresOrdenados[] = $filaEjemplo[$columna] ?? '';
        }
        fputcsv($stream, $valoresOrdenados, ';');

        rewind($stream);
        $contenido = stream_get_contents($stream);
        fclose($stream);

        return "\xEF\xBB\xBF" . $contenido;
    }
}

if (!function_exists('practicante_carga_masiva_validar_archivo')) {
    function practicante_carga_masiva_validar_archivo(?array $archivo): array
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'errores' => ['Selecciona un archivo Excel (.xlsx) o CSV para continuar.']];
        }

        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'errores' => ['No fue posible recibir el archivo. Intenta nuevamente.']];
        }

        if (!is_uploaded_file((string) ($archivo['tmp_name'] ?? ''))) {
            return ['ok' => false, 'errores' => ['El archivo recibido no es válido.']];
        }

        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > CARGA_MASIVA_MAX_BYTES) {
            return ['ok' => false, 'errores' => ['El archivo debe pesar entre 1 byte y ' . (CARGA_MASIVA_MAX_BYTES / 1024 / 1024) . ' MB.']];
        }

        $nombreArchivo = (string) ($archivo['name'] ?? '');
        $extension = strtolower((string) pathinfo($nombreArchivo, PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            return ['ok' => false, 'errores' => ['El archivo debe tener extensión .xlsx o .csv.']];
        }

        $mimesPermitidos = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo !== false ? (string) finfo_file($finfo, (string) $archivo['tmp_name']) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mimeReal !== '' && !in_array($mimeReal, $mimesPermitidos, true)) {
            return ['ok' => false, 'errores' => ['El archivo no parece ser un Excel o CSV válido.']];
        }

        return ['ok' => true, 'errores' => []];
    }
}

if (!function_exists('practicante_carga_masiva_servir_plantilla_si_corresponde')) {
    function practicante_carga_masiva_servir_plantilla_si_corresponde(string $rolPermitido): void
    {
        // Debe llamarse antes de Views/layout/head.php (ruta actualizada en fase 4): una vez que el layout
        // imprime HTML no es posible enviar headers de descarga de archivos.
        if (query_param('plantilla') !== '1') {
            return;
        }

        auth_requiere_rol($rolPermitido);

        $formato = strtolower((string) query_param('formato', 'xlsx'));

        if ($formato === 'csv') {
            $csv = practicante_carga_masiva_generar_plantilla_csv();

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="plantilla_practicantes.csv"');
            header('Content-Length: ' . strlen($csv));
            header('X-Content-Type-Options: nosniff');
            echo $csv;
            exit;
        }

        // Por defecto servimos la plantilla .xlsx enriquecida con desplegables
        $catalogos = practicante_registro_catalogos();
        $xlsx = xlsx_generar_plantilla_practicantes($catalogos);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla_practicantes.xlsx"');
        header('Content-Length: ' . strlen($xlsx));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: max-age=0');
        echo $xlsx;
        exit;
    }
}

// Trasladé practicante_carga_masiva_render a la capa de presentación en fase 4.
