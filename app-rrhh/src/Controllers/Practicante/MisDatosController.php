<?php

declare(strict_types=1);

// Corregí los textos de validación dañados por codificación a UTF-8, sin
// modificar qué campos se permiten ni las reglas de edición de la ficha.

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/HorariosPracticanteService.php';
require_once SERVICES_PATH . '/ReglasAsistenciaService.php';

/*
|--------------------------------------------------------------------------
| Mis datos / completar registro - PRACTICANTE
|--------------------------------------------------------------------------
|
| Esta pantalla opera en dos modos. Si la ficha esta incompleta, permite
| cerrarla con todos los datos obligatorios. Si ya esta completa, mantiene
| editables solo los datos de contacto y direccion autorizados.
|
*/

auth_requiere_rol('PRACTICANTE');

if (!defined('MIS_DATOS_UPLOAD_MAX_BYTES')) {
    define('MIS_DATOS_UPLOAD_MAX_BYTES', 5242880);
}

if (!defined('MIS_DATOS_DOCUMENTOS_MAXIMOS')) {
    define('MIS_DATOS_DOCUMENTOS_MAXIMOS', 5);
}

if (!function_exists('mis_datos_texto')) {
    function mis_datos_texto(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }
}

if (!function_exists('mis_datos_entero')) {
    function mis_datos_entero(array $data, string $key): ?int
    {
        $value = trim((string) ($data[$key] ?? ''));

        return ctype_digit($value) ? (int) $value : null;
    }
}

if (!function_exists('mis_datos_normalizar_rut')) {
    function mis_datos_normalizar_rut(array $data): array
    {
        $rutNumero = mis_datos_texto($data, 'rut_numero');
        $rutDv = strtoupper(mis_datos_texto($data, 'rut_dv'));

        // Permite ingresar el RUT completo con guion o separado en numero y DV.
        if ($rutDv === '' && preg_match('/^([\d.\s]+)-\s*([0-9kK])$/', $rutNumero, $coincidencias) === 1) {
            $rutNumero = (string) $coincidencias[1];
            $rutDv = strtoupper((string) $coincidencias[2]);
        }

        $rutCompacto = strtoupper((string) preg_replace('/[^0-9K]/i', '', $rutNumero));

        if ($rutDv === '' && preg_match('/^(\d{7,8})([0-9K])$/', $rutCompacto, $coincidencias) === 1 && strlen($rutCompacto) === 9) {
            $rutCompacto = (string) $coincidencias[1];
            $rutDv = (string) $coincidencias[2];
        }

        return [
            'rut_numero' => (preg_replace('/\D+/', '', $rutCompacto) ?: null),
            'rut_dv' => $rutDv !== '' ? $rutDv : null,
        ];
    }
}

if (!function_exists('mis_datos_largo')) {
    function mis_datos_largo(string $valor): int
    {
        // Mantiene compatibilidad con hosting compartido aunque mbstring no este disponible.
        return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
    }
}

if (!function_exists('mis_datos_catalogos')) {
    function mis_datos_catalogos(): array
    {
        $pdo = db();

        return [
            'regiones' => $pdo->query(
                'SELECT id_region, nombre_region
                 FROM regiones
                 WHERE activo = 1
                 ORDER BY id_region ASC'
            )->fetchAll(),
            'comunas' => $pdo->query(
                'SELECT id_comuna, id_region, nombre_comuna
                 FROM comunas
                 WHERE activo = 1
                 ORDER BY id_region ASC, nombre_comuna ASC'
            )->fetchAll(),
            'instituciones' => $pdo->query(
                'SELECT id_institucion, nombre
                 FROM instituciones
                 WHERE activo = 1
                 ORDER BY FIELD(nombre, "Duoc UC", "INACAP", "ENAC", "Otra"), nombre ASC'
            )->fetchAll(),
            'sedes' => $pdo->query(
                'SELECT id_sede, id_institucion, nombre_sede, codigo_sede
                 FROM sedes
                 WHERE activo = 1
                 ORDER BY id_institucion ASC, nombre_sede ASC'
            )->fetchAll(),
            'carreras' => $pdo->query(
                'SELECT id_carrera, nombre
                 FROM carreras
                 WHERE activo = 1
                 ORDER BY nombre ASC'
            )->fetchAll(),
            'tipos_practica' => $pdo->query(
                'SELECT id_tipo_practica, nombre, horas_base
                 FROM tipos_practica
                 WHERE activo = 1
                 ORDER BY id_tipo_practica ASC'
            )->fetchAll(),
        ];
    }
}

if (!function_exists('mis_datos_buscar_por_id')) {
    function mis_datos_buscar_por_id(array $items, string $campoId, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        foreach ($items as $item) {
            if ((int) ($item[$campoId] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('mis_datos_es_otra_institucion')) {
    function mis_datos_es_otra_institucion(?array $institucion): bool
    {
        return $institucion !== null && strcasecmp((string) $institucion['nombre'], 'Otra') === 0;
    }
}

if (!function_exists('mis_datos_sede_pertenece')) {
    function mis_datos_sede_pertenece(array $sedes, int $idSede, int $idInstitucion): bool
    {
        foreach ($sedes as $sede) {
            if ((int) $sede['id_sede'] === $idSede && (int) $sede['id_institucion'] === $idInstitucion) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('mis_datos_comuna_pertenece')) {
    function mis_datos_comuna_pertenece(array $comunas, int $idComuna, int $idRegion): bool
    {
        foreach ($comunas as $comuna) {
            if ((int) $comuna['id_comuna'] === $idComuna && (int) $comuna['id_region'] === $idRegion) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('mis_datos_estado_pendiente_id')) {
    function mis_datos_estado_pendiente_id(): int
    {
        $statement = db()->prepare(
            'SELECT id_estado_practica
             FROM estados_practica
             WHERE nombre = :nombre
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['nombre' => 'PENDIENTE_INICIO']);

        $idEstado = $statement->fetchColumn();

        if ($idEstado === false) {
            throw new RuntimeException('No existe el estado PENDIENTE_INICIO.');
        }

        return (int) $idEstado;
    }
}

if (!function_exists('mis_datos_obtener_practicante')) {
    function mis_datos_obtener_practicante(int $idPracticante): ?array
    {
        $statement = db()->prepare(
            'SELECT
                p.*,
                r.nombre_region AS region_nombre,
                co.nombre_comuna,
                i.nombre AS institucion_nombre,
                s.nombre_sede,
                c.nombre AS carrera_nombre,
                pr.id_practica,
                pr.id_tipo_practica,
                pr.horas_requeridas,
                pr.fecha_inicio_practica,
                pr.fecha_termino_practica,
                tp.nombre AS tipo_practica_nombre,
                ep.nombre AS estado_practica_nombre
             FROM practicantes p
             LEFT JOIN regiones r ON r.id_region = p.id_region
             LEFT JOIN comunas co ON co.id_comuna = p.id_comuna
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN sedes s ON s.id_sede = p.id_sede
             LEFT JOIN carreras c ON c.id_carrera = p.id_carrera
             LEFT JOIN practicas pr ON pr.id_practica = (
                SELECT pr2.id_practica
                FROM practicas pr2
                WHERE pr2.id_practicante = p.id_practicante
                  AND pr2.activo = 1
                ORDER BY pr2.fecha_inicio_practica DESC, pr2.id_practica DESC
                LIMIT 1
             )
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             LEFT JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE p.id_practicante = :id_practicante
               AND p.activo = 1
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);

        $practicante = $statement->fetch();

        return is_array($practicante) ? $practicante : null;
    }
}

if (!function_exists('mis_datos_ficha_completa')) {
    function mis_datos_ficha_completa(array $practicante): bool
    {
        $pais = trim((string) ($practicante['pais'] ?? ''));
        if ($pais === '' && (!empty($practicante['id_region']) || !empty($practicante['id_comuna']))) {
            $practicante['pais'] = 'Chile';
        }

        foreach ([
            'rut_numero',
            'rut_dv',
            'nombre_1',
            'apellido_paterno',
            'email',
            'fecha_nacimiento',
            'sexo',
            'nacionalidad',
            'direccion',
            'pais',
            'id_region',
            'id_comuna',
            'semestre',
            'id_tipo_practica',
            'horas_requeridas',
            'fecha_inicio_practica',
        ] as $campo) {
            if ($practicante[$campo] === null || (string) $practicante[$campo] === '') {
                return false;
            }
        }

        $tieneInstitucion = !empty($practicante['id_institucion']) || !empty($practicante['institucion_nombre']) || !empty($practicante['institucion_otra']);
        $tieneCarrera = !empty($practicante['id_carrera']);

        if (!$tieneInstitucion || !$tieneCarrera) {
            return false;
        }

        $esOtra = strcasecmp((string) ($practicante['institucion_nombre'] ?? ''), 'Otra') === 0;

        if ($esOtra) {
            if (empty($practicante['institucion_otra']) || empty($practicante['sede_otra'])) {
                return false;
            }
        } elseif (empty($practicante['id_sede']) && empty($practicante['sede_otra'])) {
            return false;
        }

        if (empty($practicante['id_practica'])) {
            return false;
        }

        return horarios_practicante_tiene_dias(horarios_practicante_obtener_vigente((int) $practicante['id_practica']));
    }
}

if (!function_exists('mis_datos_normalizar')) {
    function mis_datos_normalizar(array $data, array $practicante, bool $modoCompletar, array $catalogos): array
    {
        $idInstitucion = $modoCompletar ? mis_datos_entero($data, 'id_institucion') : ((int) ($practicante['id_institucion'] ?? 0) ?: null);
        $institucion = mis_datos_buscar_por_id($catalogos['instituciones'], 'id_institucion', $idInstitucion);
        $esOtra = mis_datos_es_otra_institucion($institucion);
        $rut = $modoCompletar ? mis_datos_normalizar_rut($data) : [
            'rut_numero' => $practicante['rut_numero'] ?? null,
            'rut_dv' => $practicante['rut_dv'] ?? null,
        ];

        $idRegion = mis_datos_entero($data, 'id_region');
        $idComuna = mis_datos_entero($data, 'id_comuna');
        $pais = mis_datos_texto($data, 'pais');
        if ($pais === '' && ($idRegion !== null || $idComuna !== null)) {
            $pais = 'Chile';
        }
        if ($pais === '') {
            $pais = trim((string) ($practicante['pais'] ?? '')) ?: 'Chile';
        }

        return [
            'rut_numero' => $rut['rut_numero'],
            'rut_dv' => $rut['rut_dv'],
            'nombre_1' => (string) ($practicante['nombre_1'] ?? ''),
            'nombre_2' => $modoCompletar ? (mis_datos_texto($data, 'nombre_2') ?: null) : ($practicante['nombre_2'] ?? null),
            'apellido_paterno' => (string) ($practicante['apellido_paterno'] ?? ''),
            'apellido_materno' => $modoCompletar ? (mis_datos_texto($data, 'apellido_materno') ?: null) : ($practicante['apellido_materno'] ?? null),
            'telefono' => mis_datos_texto($data, 'telefono') ?: null,
            'fecha_nacimiento' => $modoCompletar ? (mis_datos_texto($data, 'fecha_nacimiento') ?: null) : ($practicante['fecha_nacimiento'] ?? null),
            'sexo' => $modoCompletar ? (strtoupper(mis_datos_texto($data, 'sexo')) ?: null) : ($practicante['sexo'] ?? null),
            'nacionalidad' => mis_datos_texto($data, 'nacionalidad') ?: null,
            'direccion' => mis_datos_texto($data, 'direccion') ?: null,
            'numero_direccion' => mis_datos_texto($data, 'numero_direccion') ?: null,
            'departamento' => mis_datos_texto($data, 'departamento') ?: null,
            'pais' => $pais,
            'id_region' => $idRegion,
            'id_comuna' => $idComuna,
            'id_institucion' => $idInstitucion,
            'id_sede' => $modoCompletar && !$esOtra ? mis_datos_entero($data, 'id_sede') : ($esOtra ? null : ($practicante['id_sede'] ?? null)),
            'institucion_otra' => $modoCompletar && $esOtra ? (mis_datos_texto($data, 'institucion_otra') ?: null) : ($esOtra ? ($practicante['institucion_otra'] ?? null) : null),
            'sede_otra' => $modoCompletar && $esOtra ? (mis_datos_texto($data, 'sede_otra') ?: null) : ($esOtra ? ($practicante['sede_otra'] ?? null) : null),
            'id_carrera' => $modoCompletar ? mis_datos_entero($data, 'id_carrera') : ($practicante['id_carrera'] ?? null),
            'semestre' => $modoCompletar ? (mis_datos_texto($data, 'semestre') ?: null) : ($practicante['semestre'] ?? null),
            'id_practica' => $practicante['id_practica'] !== null ? (int) $practicante['id_practica'] : null,
            'id_tipo_practica' => $modoCompletar ? mis_datos_entero($data, 'id_tipo_practica') : ($practicante['id_tipo_practica'] ?? null),
            'horas_requeridas' => $modoCompletar ? mis_datos_entero($data, 'horas_requeridas') : ($practicante['horas_requeridas'] ?? null),
            'fecha_inicio_practica' => $modoCompletar ? (mis_datos_texto($data, 'fecha_inicio_practica') ?: null) : ($practicante['fecha_inicio_practica'] ?? null),
            'horario' => $modoCompletar ? horarios_practicante_normalizar($data) : horarios_practicante_valores_iniciales(),
        ];
    }
}

if (!function_exists('mis_datos_validar')) {
    function mis_datos_validar(array $data, array $catalogos, bool $modoCompletar): array
    {
        $errores = [];

        foreach (['nacionalidad', 'direccion', 'numero_direccion', 'pais', 'id_region', 'id_comuna'] as $campo) {
            if ((string) ($data[$campo] ?? '') === '') {
                $errores[] = 'Completa todos los campos obligatorios de contacto y dirección.';
                break;
            }
        }

        if (mis_datos_buscar_por_id($catalogos['regiones'], 'id_region', $data['id_region']) === null) {
            $errores[] = 'Selecciona una región válida.';
        }

        if (mis_datos_buscar_por_id($catalogos['comunas'], 'id_comuna', $data['id_comuna']) === null) {
            $errores[] = 'Selecciona una comuna válida.';
        }

        if ($data['id_region'] !== null && $data['id_comuna'] !== null && !mis_datos_comuna_pertenece($catalogos['comunas'], (int) $data['id_comuna'], (int) $data['id_region'])) {
            $errores[] = 'La comuna seleccionada no pertenece a la región.';
        }

        if ($data['telefono'] !== null && mis_datos_largo((string) $data['telefono']) > 20) {
            $errores[] = 'El teléfono no puede superar 20 caracteres.';
        }

        if (mis_datos_largo((string) ($data['nacionalidad'] ?? '')) > 50) {
            $errores[] = 'La nacionalidad no puede superar 50 caracteres.';
        }

        if (!$modoCompletar) {
            return array_values(array_unique($errores));
        }

        $errores = array_merge(
            $errores,
            horarios_practicante_validar($data['horario'], true, $data['fecha_inicio_practica'])
        );

        foreach (['rut_numero', 'rut_dv', 'fecha_nacimiento', 'sexo', 'id_institucion', 'id_carrera', 'semestre', 'id_tipo_practica', 'horas_requeridas', 'fecha_inicio_practica'] as $campo) {
            if ($data[$campo] === null || (string) $data[$campo] === '') {
                $errores[] = 'Completa todos los campos obligatorios para cerrar tu ficha.';
                break;
            }
        }

        if ($data['rut_numero'] !== null && !preg_match('/^\d{7,9}$/', (string) $data['rut_numero'])) {
            $errores[] = 'Ingresa un RUT válido, sin puntos ni guion.';
        }

        if ($data['rut_dv'] !== null && !preg_match('/^[0-9K]$/', (string) $data['rut_dv'])) {
            $errores[] = 'Ingresa un dígito verificador válido.';
        }

        foreach (['fecha_nacimiento' => 'fecha de nacimiento', 'fecha_inicio_practica' => 'fecha de inicio de práctica'] as $campo => $etiqueta) {
            $fecha = DateTimeImmutable::createFromFormat('Y-m-d', (string) $data[$campo]);
            $fechaValida = $fecha !== false && $fecha->format('Y-m-d') === $data[$campo];

            if (!$fechaValida) {
                $errores[] = 'Ingresa una ' . $etiqueta . ' válida.';
            }
        }

        if (!in_array($data['sexo'], ['F', 'M', 'O'], true)) {
            $errores[] = 'Selecciona una opción válida para sexo.';
        }

        foreach ([
            'id_institucion' => ['instituciones', 'id_institucion', 'institución'],
            'id_carrera' => ['carreras', 'id_carrera', 'carrera'],
            'id_tipo_practica' => ['tipos_practica', 'id_tipo_practica', 'tipo de práctica'],
        ] as $campo => [$catalogo, $campoId, $etiqueta]) {
            if (mis_datos_buscar_por_id($catalogos[$catalogo], $campoId, $data[$campo]) === null) {
                $errores[] = 'Selecciona un valor válido para ' . $etiqueta . '.';
            }
        }

        if ($data['horas_requeridas'] === null || $data['horas_requeridas'] <= 0) {
            $errores[] = 'Ingresa horas requeridas mayores a cero.';
        }

        $institucion = mis_datos_buscar_por_id($catalogos['instituciones'], 'id_institucion', $data['id_institucion']);
        $esOtra = mis_datos_es_otra_institucion($institucion);

        if ($institucion !== null && !$esOtra && ($data['id_sede'] === null || !mis_datos_sede_pertenece($catalogos['sedes'], (int) $data['id_sede'], (int) $data['id_institucion']))) {
            $errores[] = 'Selecciona una sede válida para la institución.';
        }

        if ($esOtra && ((string) ($data['institucion_otra'] ?? '') === '' || (string) ($data['sede_otra'] ?? '') === '')) {
            $errores[] = 'Ingresa institución y sede cuando selecciones Otra.';
        }

        return array_values(array_unique($errores));
    }
}

if (!function_exists('mis_datos_validar_rut_unico')) {
    function mis_datos_validar_rut_unico(int $idPracticante, array $data): array
    {
        if ($data['rut_numero'] === null || $data['rut_dv'] === null) {
            return [];
        }

        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM practicantes
             WHERE rut_numero = :rut_numero
               AND rut_dv = :rut_dv
               AND id_practicante <> :id_practicante'
        );
        $statement->execute([
            'rut_numero' => $data['rut_numero'],
            'rut_dv' => $data['rut_dv'],
            'id_practicante' => $idPracticante,
        ]);

        return (int) $statement->fetchColumn() > 0 ? ['Ya existe otro practicante registrado con ese RUT.'] : [];
    }
}

if (!function_exists('mis_datos_documentos_obtener')) {
    function mis_datos_documentos_obtener(int $idPracticante): array
    {
        try {
            $statement = db()->prepare(
                'SELECT id_documento, tipo_documento, nombre_original, ruta_relativa, mime_type, tamano_bytes, created_at
                 FROM documentos_practicante
                 WHERE id_practicante = :id_practicante
                   AND activo = 1
                 ORDER BY tipo_documento ASC, created_at DESC, id_documento DESC'
            );
            $statement->execute(['id_practicante' => $idPracticante]);
        } catch (Throwable $exception) {
            error_log('Error al cargar documentos del practicante: ' . $exception->getMessage());

            return [
                'foto' => null,
                'documentos' => [],
            ];
        }

        $foto = null;
        $documentos = [];

        foreach ($statement->fetchAll() as $documento) {
            if ((string) $documento['tipo_documento'] === 'FOTO_PERFIL') {
                $foto ??= $documento;
                continue;
            }

            if ((string) $documento['tipo_documento'] === 'DOCUMENTO_PRACTICA') {
                $documentos[] = $documento;
            }
        }

        return [
            'foto' => $foto,
            'documentos' => $documentos,
        ];
    }
}

if (!function_exists('mis_datos_archivo_mime')) {
    function mis_datos_archivo_mime(string $tmpName): ?string
    {
        if (!is_file($tmpName)) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }
}

if (!function_exists('mis_datos_archivo_extension')) {
    function mis_datos_archivo_extension(string $nombre): string
    {
        return strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));
    }
}

if (!function_exists('mis_datos_archivos_preparar')) {
    function mis_datos_archivos_preparar(array $files, array $documentosActuales): array
    {
        $resultado = [
            'foto' => null,
            'documentos' => [],
            'errores' => [],
        ];

        $foto = $files['foto_perfil'] ?? null;
        if (is_array($foto) && (int) ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $resultado['foto'] = mis_datos_validar_archivo_simple($foto, 'foto de perfil', [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
            ], $resultado['errores']);
        }

        $documentos = $files['documentos_practica'] ?? null;
        if (is_array($documentos) && is_array($documentos['error'] ?? null)) {
            $existentes = count($documentosActuales['documentos'] ?? []);

            foreach ($documentos['error'] as $index => $error) {
                if ((int) $error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $archivo = [
                    'name' => $documentos['name'][$index] ?? '',
                    'type' => $documentos['type'][$index] ?? '',
                    'tmp_name' => $documentos['tmp_name'][$index] ?? '',
                    'error' => $error,
                    'size' => $documentos['size'][$index] ?? 0,
                ];

                $validado = mis_datos_validar_archivo_simple($archivo, 'documento de práctica', [
                    'pdf' => ['application/pdf'],
                    'doc' => ['application/msword'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                    ],
                    'xls' => [
                        'application/vnd.ms-excel',
                        'application/octet-stream',
                    ],
                    'xlsx' => [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/zip',
                    ],
                ], $resultado['errores']);

                if ($validado !== null) {
                    $resultado['documentos'][] = $validado;
                }
            }

            if ($existentes + count($resultado['documentos']) > MIS_DATOS_DOCUMENTOS_MAXIMOS) {
                $resultado['errores'][] = 'Puedes mantener hasta 5 documentos de práctica activos.';
            }
        }

        $resultado['errores'] = array_values(array_unique($resultado['errores']));

        return $resultado;
    }
}

if (!function_exists('mis_datos_validar_archivo_simple')) {
    function mis_datos_validar_archivo_simple(array $archivo, string $etiqueta, array $permitidos, array &$errores): ?array
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $errores[] = 'No fue posible subir el archivo de ' . $etiqueta . '.';

            return null;
        }

        $nombreOriginal = basename((string) ($archivo['name'] ?? ''));
        $tmpName = (string) ($archivo['tmp_name'] ?? '');
        $tamano = (int) ($archivo['size'] ?? 0);
        $extension = mis_datos_archivo_extension($nombreOriginal);
        $mime = mis_datos_archivo_mime($tmpName);

        if ($tamano <= 0 || $tamano > MIS_DATOS_UPLOAD_MAX_BYTES) {
            $errores[] = 'Cada archivo debe pesar como máximo 5 MB.';

            return null;
        }

        if ($nombreOriginal === '' || !isset($permitidos[$extension]) || $mime === null || !in_array($mime, $permitidos[$extension], true)) {
            $errores[] = 'El formato del archivo de ' . $etiqueta . ' no está permitido.';

            return null;
        }

        return [
            'nombre_original' => substr($nombreOriginal, 0, 255),
            'tmp_name' => $tmpName,
            'extension' => $extension,
            'mime_type' => $mime,
            'tamano_bytes' => $tamano,
        ];
    }
}

if (!function_exists('mis_datos_guardar_archivos')) {
    function mis_datos_guardar_archivos(int $idPracticante, ?int $idPractica, array $archivos, array &$movidos): array
    {
        $guardados = [];

        if ($archivos['foto'] !== null) {
            $guardados[] = mis_datos_guardar_archivo($idPracticante, $idPractica, 'FOTO_PERFIL', $archivos['foto'], $movidos);

            $statement = db()->prepare(
                'UPDATE documentos_practicante
                 SET activo = 0
                 WHERE id_practicante = :id_practicante
                   AND tipo_documento = "FOTO_PERFIL"
                   AND id_documento <> :id_documento'
            );
            $statement->execute([
                'id_practicante' => $idPracticante,
                'id_documento' => $guardados[array_key_last($guardados)]['id_documento'],
            ]);
        }

        foreach ($archivos['documentos'] as $documento) {
            $guardados[] = mis_datos_guardar_archivo($idPracticante, $idPractica, 'DOCUMENTO_PRACTICA', $documento, $movidos);
        }

        return $guardados;
    }
}

if (!function_exists('mis_datos_guardar_archivo')) {
    function mis_datos_guardar_archivo(int $idPracticante, ?int $idPractica, string $tipoDocumento, array $archivo, array &$movidos): array
    {
        $subcarpeta = $tipoDocumento === 'FOTO_PERFIL' ? 'perfil' : 'documentos';
        $directorioRelativo = 'uploads/practicantes/' . $idPracticante . '/' . $subcarpeta;
        $directorioFisico = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directorioRelativo);

        if (!is_dir($directorioFisico) && !mkdir($directorioFisico, 0775, true) && !is_dir($directorioFisico)) {
            throw new RuntimeException('No fue posible crear el directorio de subida.');
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $archivo['extension'];
        $rutaFisica = $directorioFisico . DIRECTORY_SEPARATOR . $nombreArchivo;
        $rutaRelativa = $directorioRelativo . '/' . $nombreArchivo;

        if (!move_uploaded_file((string) $archivo['tmp_name'], $rutaFisica)) {
            throw new RuntimeException('No fue posible mover el archivo subido.');
        }

        $movidos[] = $rutaFisica;

        $statement = db()->prepare(
            'INSERT INTO documentos_practicante (
                id_practicante,
                id_practica,
                tipo_documento,
                nombre_original,
                nombre_archivo,
                ruta_relativa,
                mime_type,
                tamano_bytes,
                activo
            ) VALUES (
                :id_practicante,
                :id_practica,
                :tipo_documento,
                :nombre_original,
                :nombre_archivo,
                :ruta_relativa,
                :mime_type,
                :tamano_bytes,
                1
            )'
        );
        $statement->execute([
            'id_practicante' => $idPracticante,
            'id_practica' => $idPractica,
            'tipo_documento' => $tipoDocumento,
            'nombre_original' => $archivo['nombre_original'],
            'nombre_archivo' => $nombreArchivo,
            'ruta_relativa' => $rutaRelativa,
            'mime_type' => $archivo['mime_type'],
            'tamano_bytes' => $archivo['tamano_bytes'],
        ]);

        return [
            'id_documento' => (int) db()->lastInsertId(),
            'tipo_documento' => $tipoDocumento,
            'nombre_original' => $archivo['nombre_original'],
            'tamano_bytes' => $archivo['tamano_bytes'],
        ];
    }
}

if (!function_exists('mis_datos_guardar')) {
    function mis_datos_guardar(int $idPracticante, array $data, array $anteriores, bool $modoCompletar, array $archivos): bool
    {
        $archivosMovidos = [];
        $archivosGuardados = [];

        try {
            db()->beginTransaction();

            $statement = db()->prepare(
                'UPDATE practicantes
                 SET rut_numero = :rut_numero,
                     rut_dv = :rut_dv,
                     nombre_2 = :nombre_2,
                     apellido_materno = :apellido_materno,
                     telefono = :telefono,
                     fecha_nacimiento = :fecha_nacimiento,
                     sexo = :sexo,
                     nacionalidad = :nacionalidad,
                     direccion = :direccion,
                     numero_direccion = :numero_direccion,
                     departamento = :departamento,
                     pais = :pais,
                     id_region = :id_region,
                     id_comuna = :id_comuna,
                     id_institucion = :id_institucion,
                     id_sede = :id_sede,
                     institucion_otra = :institucion_otra,
                     sede_otra = :sede_otra,
                     id_carrera = :id_carrera,
                     semestre = :semestre
                 WHERE id_practicante = :id_practicante
                   AND activo = 1'
            );
            $statement->execute([
                'rut_numero' => $data['rut_numero'],
                'rut_dv' => $data['rut_dv'],
                'nombre_2' => $data['nombre_2'],
                'apellido_materno' => $data['apellido_materno'],
                'telefono' => $data['telefono'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo' => $data['sexo'],
                'nacionalidad' => $data['nacionalidad'],
                'direccion' => $data['direccion'],
                'numero_direccion' => $data['numero_direccion'],
                'departamento' => $data['departamento'],
                'pais' => $data['pais'],
                'id_region' => $data['id_region'],
                'id_comuna' => $data['id_comuna'],
                'id_institucion' => $data['id_institucion'],
                'id_sede' => $data['id_sede'],
                'institucion_otra' => $data['institucion_otra'],
                'sede_otra' => $data['sede_otra'],
                'id_carrera' => $data['id_carrera'],
                'semestre' => $data['semestre'],
                'id_practicante' => $idPracticante,
            ]);

            $idPractica = $data['id_practica'];

            if ($data['id_practica'] !== null) {
                $statement = db()->prepare(
                    'UPDATE practicas
                     SET id_tipo_practica = :id_tipo_practica,
                         horas_requeridas = :horas_requeridas,
                         fecha_inicio_practica = :fecha_inicio_practica
                     WHERE id_practica = :id_practica
                       AND id_practicante = :id_practicante
                       AND activo = 1'
                );
                $statement->execute([
                    'id_tipo_practica' => $data['id_tipo_practica'],
                    'horas_requeridas' => $data['horas_requeridas'],
                    'fecha_inicio_practica' => $data['fecha_inicio_practica'],
                    'id_practica' => $data['id_practica'],
                    'id_practicante' => $idPracticante,
                ]);
            } else {
                $statement = db()->prepare(
                    'INSERT INTO practicas (
                        id_practicante,
                        id_tipo_practica,
                        id_estado_practica,
                        horas_requeridas,
                        fecha_inicio_practica,
                        activo
                    ) VALUES (
                        :id_practicante,
                        :id_tipo_practica,
                        :id_estado_practica,
                        :horas_requeridas,
                        :fecha_inicio_practica,
                        1
                    )'
                );
                $statement->execute([
                    'id_practicante' => $idPracticante,
                    'id_tipo_practica' => $data['id_tipo_practica'],
                    'id_estado_practica' => mis_datos_estado_pendiente_id(),
                    'horas_requeridas' => $data['horas_requeridas'],
                    'fecha_inicio_practica' => $data['fecha_inicio_practica'],
                ]);
                $idPractica = (int) db()->lastInsertId();
            }

            $idHorario = null;

            if ($modoCompletar && $idPractica !== null) {
                $idHorario = horarios_practicante_guardar(
                    (int) $idPractica,
                    (string) $data['fecha_inicio_practica'],
                    $data['horario']
                );
            }

            $archivosGuardados = mis_datos_guardar_archivos($idPracticante, $idPractica, $archivos, $archivosMovidos);

            db()->commit();

            auditoria_registrar('EDICION_DATOS_PROPIOS_PRACTICANTE', [
                'tabla_afectada' => 'practicantes',
                'id_registro_afectado' => $idPracticante,
                'valores_anteriores' => $anteriores,
                'valores_nuevos' => [
                    'modo' => $modoCompletar ? 'COMPLETAR_FICHA' : 'EDITAR_CONTACTO_DIRECCION',
                    'id_horario' => $idHorario,
                    'datos' => $data,
                ],
            ]);

            if ($archivosGuardados !== []) {
                auditoria_registrar('SUBIR_DOCUMENTO_PRACTICANTE', [
                    'tabla_afectada' => 'documentos_practicante',
                    'id_registro_afectado' => $idPracticante,
                    'valores_nuevos' => [
                        'id_practica' => $idPractica,
                        'archivos' => $archivosGuardados,
                    ],
                ]);
            }

            return true;
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            foreach ($archivosMovidos as $rutaFisica) {
                if (is_file($rutaFisica)) {
                    unlink($rutaFisica);
                }
            }

            error_log('Error al guardar datos del practicante: ' . $exception->getMessage());

            return false;
        }
    }
}

if (!function_exists('mis_datos_valor_visible')) {
    function mis_datos_valor_visible(mixed $valor, string $fallback = 'Pendiente'): string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' ? $texto : $fallback;
    }
}

if (!function_exists('mis_datos_formatear_tamano')) {
    function mis_datos_formatear_tamano(int $bytes): string
    {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }
}

if (!function_exists('mis_datos_nombre_completo')) {
    function mis_datos_nombre_completo(array $practicante): string
    {
        return trim((string) $practicante['nombre_1'] . ' ' . (string) ($practicante['nombre_2'] ?? '') . ' ' . (string) $practicante['apellido_paterno'] . ' ' . (string) ($practicante['apellido_materno'] ?? ''));
    }
}

if (!function_exists('mis_datos_iniciales')) {
    function mis_datos_iniciales(array $practicante): string
    {
        $nombre = trim((string) ($practicante['nombre_1'] ?? ''));
        $apellido = trim((string) ($practicante['apellido_paterno'] ?? ''));
        $iniciales = '';

        foreach ([$nombre, $apellido] as $parte) {
            if ($parte !== '') {
                $iniciales .= function_exists('mb_substr') ? mb_substr($parte, 0, 1, 'UTF-8') : substr($parte, 0, 1);
            }
        }

        return strtoupper($iniciales !== '' ? $iniciales : 'P');
    }
}

if (!function_exists('mis_datos_fecha_visible')) {
    function mis_datos_fecha_visible(mixed $fecha): string
    {
        $texto = trim((string) ($fecha ?? ''));

        if ($texto === '') {
            return 'Fecha no disponible';
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $texto)
            ?: DateTimeImmutable::createFromFormat('Y-m-d', $texto);

        return $date instanceof DateTimeImmutable ? $date->format('d-m-Y') : $texto;
    }
}

// Trasladé mis_datos_render_select a la capa de presentación en fase 4.


// Trasladé mis_datos_label a la capa de presentación en fase 4.


$errores = [];
$mensajeExito = null;
$catalogos = [];
$practicante = null;
$documentosActuales = [
    'foto' => null,
    'documentos' => [],
];
$idPracticante = auth_practicante_actual_id();

start_secure_session();

if (empty($_SESSION['csrf_mis_datos'])) {
    $_SESSION['csrf_mis_datos'] = bin2hex(random_bytes(32));
}

if ($idPracticante === null) {
    $errores[] = 'Tu cuenta no está vinculada a un registro de practicante.';
} else {
    try {
        $catalogos = mis_datos_catalogos();
        $practicante = mis_datos_obtener_practicante($idPracticante);

        if ($practicante === null) {
            $errores[] = 'No fue posible encontrar tus datos de practicante.';
        } else {
            $documentosActuales = mis_datos_documentos_obtener((int) $practicante['id_practicante']);
        }
    } catch (Throwable $exception) {
        error_log('Error al cargar datos del practicante: ' . $exception->getMessage());
        $errores[] = 'No fue posible cargar tus datos. Verifica que la base de datos esté importada.';
    }
}

$modoCompletar = $practicante !== null && !mis_datos_ficha_completa($practicante);
$estadoSoloLectura = $idPracticante !== null && function_exists('asistencia_estado_solo_lectura_practicante')
    ? asistencia_estado_solo_lectura_practicante($idPracticante)
    : ['solo_lectura' => false, 'motivo' => null];
$soloLectura = !empty($estadoSoloLectura['solo_lectura']);
$values = $practicante ?? [];

if ($practicante !== null && $modoCompletar) {
    $values['horario'] = $practicante['id_practica'] !== null
        ? horarios_practicante_obtener_vigente((int) $practicante['id_practica'])
        : horarios_practicante_valores_iniciales();
}

if (is_post() && $practicante !== null && $catalogos !== []) {
    if ($soloLectura) {
        $errores[] = (string) ($estadoSoloLectura['motivo'] ?? 'Tu perfil está en modo solo lectura y no permite modificaciones.');
    } elseif (!hash_equals((string) $_SESSION['csrf_mis_datos'], (string) input('csrf_token', ''))) {
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        $accionPerfil = (string) input('accion_perfil', 'guardar_cambios');
        $normalizado = mis_datos_normalizar($_POST, $practicante, $modoCompletar, $catalogos);
        $archivos = mis_datos_archivos_preparar($_FILES, $documentosActuales);
        $erroresArchivos = $archivos['errores'];

        if ($accionPerfil === 'subir_foto' && $archivos['foto'] === null && $erroresArchivos === []) {
            $erroresArchivos[] = 'Selecciona una imagen de perfil antes de presionar Subir foto.';
        }

        if ($accionPerfil === 'subir_documentos' && $archivos['documentos'] === [] && $erroresArchivos === []) {
            $erroresArchivos[] = 'Selecciona al menos un documento antes de presionar Subir documentos.';
        }

        $errores = array_merge(
            $errores,
            mis_datos_validar($normalizado, $catalogos, $modoCompletar),
            $modoCompletar ? mis_datos_validar_rut_unico((int) $practicante['id_practicante'], $normalizado) : [],
            $erroresArchivos
        );

        if ($errores === []) {
            $anteriores = $practicante;

            if (mis_datos_guardar((int) $practicante['id_practicante'], $normalizado, $anteriores, $modoCompletar, $archivos)) {
                $subioFoto = $archivos['foto'] !== null;
                $subioDocumentos = $archivos['documentos'] !== [];
                $mensajeExito = $modoCompletar ? 'Tu ficha fue completada correctamente.' : 'Tu perfil fue actualizado correctamente.';

                if ($subioFoto && $subioDocumentos) {
                    $mensajeExito = $modoCompletar ? 'Tu ficha fue completada y tus archivos fueron subidos correctamente.' : 'Tu foto y documentos fueron actualizados correctamente.';
                } elseif ($subioFoto) {
                    $mensajeExito = $modoCompletar ? 'Tu ficha fue completada y tu foto fue subida correctamente.' : 'Tu foto de perfil fue actualizada correctamente.';
                } elseif ($subioDocumentos) {
                    $mensajeExito = $modoCompletar ? 'Tu ficha fue completada y tus documentos fueron subidos correctamente.' : 'Tus documentos fueron subidos correctamente.';
                }

                $_SESSION['csrf_mis_datos'] = bin2hex(random_bytes(32));
                $practicante = mis_datos_obtener_practicante((int) $practicante['id_practicante']);
                $documentosActuales = mis_datos_documentos_obtener((int) $anteriores['id_practicante']);
                $modoCompletar = $practicante !== null && !mis_datos_ficha_completa($practicante);
                $values = $practicante ?? [];
                if ($practicante !== null && $modoCompletar) {
                    $values['horario'] = $practicante['id_practica'] !== null
                        ? horarios_practicante_obtener_vigente((int) $practicante['id_practica'])
                        : horarios_practicante_valores_iniciales();
                }
            } else {
                $errores[] = 'No fue posible guardar tus datos. Intenta nuevamente.';
                $values = array_merge($practicante, $normalizado);
            }
        } else {
            $values = array_merge($practicante, $normalizado);
        }
    }
}
$pageTitle = ($modoCompletar ? 'Completar registro' : 'Mi Perfil');
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/practicante/mis-datos.view.php';
