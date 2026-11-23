<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Services/HorariosPracticanteService.php';

/*
|--------------------------------------------------------------------------
| Pre-registro de practicantes
|--------------------------------------------------------------------------
|
| Servicio compartido para ADMIN y SUPERVISOR. Centraliza validaciones,
| persistencia, token de activación y correo, evitando duplicar reglas de
| negocio cr?ticas en las vistas de cada rol.
|
*/

if (!function_exists('practicante_registro_valores_iniciales')) {
    function practicante_registro_valores_iniciales(): array
    {
        return [
            'rut_numero' => '',
            'rut_dv' => '',
            'nombre_1' => '',
            'nombre_2' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'email' => '',
            'telefono' => '',
            'fecha_nacimiento' => '',
            'sexo' => '',
            'id_nacionalidad' => '',
            'nacionalidad_otra' => '',
            'direccion' => '',
            'numero_direccion' => '',
            'departamento' => '',
            'id_comuna' => '',
            'pais' => '',
            'id_region' => '',
            'id_institucion' => '',
            'id_sede' => '',
            'institucion_otra' => '',
            'sede_otra' => '',
            'id_carrera' => '',
            'semestre' => '',
            'id_tipo_practica' => '',
            'horas_requeridas' => '',
            'horas_previas' => '',
            'fecha_inicio_practica' => '',
            'horario' => horarios_practicante_valores_iniciales(),
        ];
    }
}

if (!function_exists('practicante_registro_catalogos')) {
    function practicante_registro_catalogos(): array
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
            'nacionalidades' => $pdo->query(
                'SELECT id_nacionalidad, nombre
                 FROM nacionalidades
                 WHERE activo = 1
                 ORDER BY FIELD(nombre, "Chilena", "Otra"), nombre ASC'
            )->fetchAll(),
        ];
    }
}

if (!function_exists('practicante_registro_buscar_por_id')) {
    function practicante_registro_buscar_por_id(array $items, string $campoId, int $id): ?array
    {
        foreach ($items as $item) {
            if ((int) ($item[$campoId] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('practicante_registro_sede_pertenece')) {
    function practicante_registro_sede_pertenece(array $sedes, int $idSede, int $idInstitucion): bool
    {
        foreach ($sedes as $sede) {
            if ((int) $sede['id_sede'] === $idSede && (int) $sede['id_institucion'] === $idInstitucion) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('practicante_registro_comuna_pertenece')) {
    function practicante_registro_comuna_pertenece(array $comunas, int $idComuna, int $idRegion): bool
    {
        foreach ($comunas as $comuna) {
            if ((int) $comuna['id_comuna'] === $idComuna && (int) $comuna['id_region'] === $idRegion) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('practicante_registro_es_otra_institucion')) {
    function practicante_registro_es_otra_institucion(?array $institucion): bool
    {
        return $institucion !== null && strcasecmp((string) $institucion['nombre'], 'Otra') === 0;
    }
}

if (!function_exists('practicante_registro_es_otra_nacionalidad')) {
    function practicante_registro_es_otra_nacionalidad(?array $nacionalidad): bool
    {
        return $nacionalidad !== null && strcasecmp((string) $nacionalidad['nombre'], 'Otra') === 0;
    }
}

if (!function_exists('practicante_registro_texto')) {
    function practicante_registro_texto(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }
}

if (!function_exists('practicante_registro_entero')) {
    function practicante_registro_entero(array $data, string $key): ?int
    {
        $value = trim((string) ($data[$key] ?? ''));

        return ctype_digit($value) ? (int) $value : null;
    }
}

if (!function_exists('practicante_registro_normalizar_rut')) {
    function practicante_registro_normalizar_rut(array $data): array
    {
        $rutNumero = practicante_registro_texto($data, 'rut_numero');
        $rutDv = strtoupper(practicante_registro_texto($data, 'rut_dv'));

        // Acepta RUT separado o completo con guion para evitar rechazos por formato de ingreso.
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

if (!function_exists('practicante_registro_normalizar_fecha')) {
    function practicante_registro_normalizar_fecha(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        // 1. Si es un número serial de fecha de Excel (ej: 37025 para 2001-05-14)
        if (is_numeric($texto) && (float) $texto > 1000 && (float) $texto < 100000) {
            $dias = (int) floor((float) $texto);
            $unixTimestamp = ($dias - 25569) * 86400;
            if ($unixTimestamp > 0) {
                return gmdate('Y-m-d', $unixTimestamp);
            }
        }

        // 2. Si ya viene en formato YYYY-MM-DD o YYYY/MM/DD o YYYY.MM.DD
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $texto, $m)) {
            $y = (int) $m[1];
            $mes = (int) $m[2];
            $d = (int) $m[3];
            if (checkdate($mes, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mes, $d);
            }
        }

        // 3. Si viene en formato latino DD-MM-YYYY o DD/MM/YYYY o DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $texto, $m)) {
            $d = (int) $m[1];
            $mes = (int) $m[2];
            $y = (int) $m[3];
            if (checkdate($mes, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $mes, $d);
            }
        }

        // 4. Intentar con DateTimeImmutable
        try {
            $dt = new DateTimeImmutable($texto);

            return $dt->format('Y-m-d');
        } catch (Throwable) {
            return $texto;
        }
    }
}

if (!function_exists('practicante_registro_normalizar')) {
    function practicante_registro_normalizar(array $data, array $catalogos): array
    {
        // Inicialicé ubicación antes de usarla: los avisos PHP ocultaban pérdida de región y comuna al guardar.
        $idRegion = practicante_registro_entero($data, 'id_region');
        $idComuna = practicante_registro_entero($data, 'id_comuna');
        $idInstitucion = practicante_registro_entero($data, 'id_institucion');
        $institucion = $idInstitucion !== null
            ? practicante_registro_buscar_por_id($catalogos['instituciones'], 'id_institucion', $idInstitucion)
            : null;
        $esOtra = practicante_registro_es_otra_institucion($institucion);
        $rut = practicante_registro_normalizar_rut($data);

        $idNacionalidad = practicante_registro_entero($data, 'id_nacionalidad');
        $nacionalidadCatalogo = $idNacionalidad !== null
            ? practicante_registro_buscar_por_id($catalogos['nacionalidades'], 'id_nacionalidad', $idNacionalidad)
            : null;
        $nacionalidadEsOtra = practicante_registro_es_otra_nacionalidad($nacionalidadCatalogo);
        $nacionalidad = $nacionalidadEsOtra
            ? (practicante_registro_texto($data, 'nacionalidad_otra') ?: null)
            : ($nacionalidadCatalogo !== null ? (string) $nacionalidadCatalogo['nombre'] : null);

        return [
            'rut_numero' => $rut['rut_numero'],
            'rut_dv' => $rut['rut_dv'],
            'nombre_1' => practicante_registro_texto($data, 'nombre_1'),
            'nombre_2' => practicante_registro_texto($data, 'nombre_2') ?: null,
            'apellido_paterno' => practicante_registro_texto($data, 'apellido_paterno'),
            'apellido_materno' => practicante_registro_texto($data, 'apellido_materno') ?: null,
            'email' => strtolower(practicante_registro_texto($data, 'email')),
            'telefono' => practicante_registro_texto($data, 'telefono') ?: null,
            'fecha_nacimiento' => practicante_registro_normalizar_fecha($data['fecha_nacimiento'] ?? null),
            'sexo' => strtoupper(practicante_registro_texto($data, 'sexo')) ?: null,
            'id_nacionalidad' => $idNacionalidad,
            'nacionalidad_otra' => $nacionalidadEsOtra ? (practicante_registro_texto($data, 'nacionalidad_otra') ?: null) : null,
            'nacionalidad' => $nacionalidad,
            'direccion' => practicante_registro_texto($data, 'direccion') ?: null,
            'numero_direccion' => practicante_registro_texto($data, 'numero_direccion') ?: null,
            'departamento' => practicante_registro_texto($data, 'departamento') ?: null,
            'pais' => (practicante_registro_texto($data, 'pais') ?: (($idRegion !== null || $idComuna !== null) ? 'Chile' : null)),
            'id_region' => $idRegion,
            'id_comuna' => $idComuna,
            'id_institucion' => $idInstitucion,
            'id_sede' => $esOtra ? null : practicante_registro_entero($data, 'id_sede'),
            'institucion_otra' => $esOtra ? practicante_registro_texto($data, 'institucion_otra') : null,
            'sede_otra' => $esOtra ? practicante_registro_texto($data, 'sede_otra') : null,
            'id_carrera' => practicante_registro_entero($data, 'id_carrera'),
            'semestre' => practicante_registro_texto($data, 'semestre') ?: null,
            'id_tipo_practica' => practicante_registro_entero($data, 'id_tipo_practica'),
            'horas_requeridas' => practicante_registro_entero($data, 'horas_requeridas'),
            'horas_previas' => max(0.0, round((float) ($data['horas_previas'] ?? 0), 2)),
            'fecha_inicio_practica' => practicante_registro_normalizar_fecha($data['fecha_inicio_practica'] ?? null),
            'horario' => horarios_practicante_normalizar($data),
        ];
    }
}

if (!function_exists('practicante_registro_validar')) {
    function practicante_registro_validar(array $normalizado, array $catalogos): array
    {
        $errores = [];
        $institucion = $normalizado['id_institucion'] !== null
            ? practicante_registro_buscar_por_id($catalogos['instituciones'], 'id_institucion', (int) $normalizado['id_institucion'])
            : null;
        $esOtra = practicante_registro_es_otra_institucion($institucion);

        if ($normalizado['rut_numero'] !== null && !preg_match('/^\d{7,9}$/', (string) $normalizado['rut_numero'])) {
            $errores[] = 'Ingresa un RUT válido, sin puntos ni guion.';
        }

        if ($normalizado['rut_dv'] !== null && !preg_match('/^[0-9K]$/', (string) $normalizado['rut_dv'])) {
            $errores[] = 'Ingresa un dígito verificador válido.';
        }

        if (($normalizado['rut_numero'] === null && $normalizado['rut_dv'] !== null) || ($normalizado['rut_numero'] !== null && $normalizado['rut_dv'] === null)) {
            $errores[] = 'Si informas RUT, debes completar número y dígito verificador.';
        }

        foreach (['nombre_1', 'apellido_paterno', 'email'] as $campo) {
            if ((string) ($normalizado[$campo] ?? '') === '') {
                $errores[] = 'Completa todos los campos obligatorios.';
                break;
            }
        }

        if (!filter_var($normalizado['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingresa un correo electrónico válido.';
        }

        foreach (['fecha_nacimiento' => 'fecha de nacimiento', 'fecha_inicio_practica' => 'fecha de inicio de práctica'] as $campo => $etiqueta) {
            if ($normalizado[$campo] === null) {
                continue;
            }

            $fecha = DateTimeImmutable::createFromFormat('Y-m-d', (string) $normalizado[$campo]);
            $fechaValida = $fecha !== false && $fecha->format('Y-m-d') === $normalizado[$campo];

            if (!$fechaValida) {
                $errores[] = 'Ingresa una ' . $etiqueta . ' válida.';
            }
        }

        if ($normalizado['sexo'] !== null && !in_array($normalizado['sexo'], ['F', 'M', 'O'], true)) {
            $errores[] = 'Selecciona una opción válida para sexo.';
        }

        $nacionalidadCatalogo = $normalizado['id_nacionalidad'] !== null
            ? practicante_registro_buscar_por_id($catalogos['nacionalidades'], 'id_nacionalidad', (int) $normalizado['id_nacionalidad'])
            : null;

        if ($normalizado['id_nacionalidad'] !== null && $nacionalidadCatalogo === null) {
            $errores[] = 'Selecciona un valor válido para nacionalidad.';
        }

        if (practicante_registro_es_otra_nacionalidad($nacionalidadCatalogo) && ($normalizado['nacionalidad_otra'] === null || $normalizado['nacionalidad_otra'] === '')) {
            $errores[] = 'Ingresa la nacionalidad.';
        }

        foreach ([
            'id_region' => ['regiones', 'id_region', 'región'],
            'id_comuna' => ['comunas', 'id_comuna', 'comuna'],
            'id_institucion' => ['instituciones', 'id_institucion', 'institución'],
            'id_carrera' => ['carreras', 'id_carrera', 'carrera'],
            'id_tipo_practica' => ['tipos_practica', 'id_tipo_practica', 'tipo de práctica'],
        ] as $campo => [$catalogo, $campoId, $etiqueta]) {
            if ($normalizado[$campo] !== null && practicante_registro_buscar_por_id($catalogos[$catalogo], $campoId, (int) $normalizado[$campo]) === null) {
                $errores[] = 'Selecciona un valor válido para ' . $etiqueta . '.';
            }
        }

        if ($normalizado['horas_requeridas'] !== null && $normalizado['horas_requeridas'] <= 0) {
            $errores[] = 'Ingresa horas requeridas mayores a cero.';
        }

        $errores = array_merge(
            $errores,
            horarios_practicante_validar($normalizado['horario'], false, $normalizado['fecha_inicio_practica'])
        );

        if ($normalizado['id_region'] === null && $normalizado['id_comuna'] !== null) {
            $errores[] = 'Selecciona una region antes de elegir comuna.';
        }

        if ($normalizado['id_region'] !== null && $normalizado['id_comuna'] !== null && !practicante_registro_comuna_pertenece($catalogos['comunas'], (int) $normalizado['id_comuna'], (int) $normalizado['id_region'])) {
            $errores[] = 'Selecciona una comuna valida para la region.';
        }

        if ($institucion === null && $normalizado['id_sede'] !== null) {
            $errores[] = 'Selecciona una institución válida antes de elegir sede.';
        }

        if ($institucion !== null && !$esOtra) {
            if ($normalizado['id_sede'] === null || !practicante_registro_sede_pertenece($catalogos['sedes'], (int) $normalizado['id_sede'], (int) $normalizado['id_institucion'])) {
                $errores[] = 'Selecciona una sede válida para la institución.';
            }
        }

        if ($esOtra) {
            if ($normalizado['institucion_otra'] === null || $normalizado['institucion_otra'] === '') {
                $errores[] = 'Ingresa el nombre de la institución.';
            }

            if ($normalizado['sede_otra'] === null || $normalizado['sede_otra'] === '') {
                $errores[] = 'Ingresa el nombre de la sede.';
            }
        }

        return array_values(array_unique($errores));
    }
}

if (!function_exists('practicante_registro_estado_pendiente_id')) {
    function practicante_registro_estado_pendiente_id(PDO $pdo): int
    {
        $statement = $pdo->prepare(
            'SELECT id_estado_practica
             FROM estados_practica
             WHERE nombre = :nombre
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['nombre' => 'PENDIENTE_INICIO']);

        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No existe el estado de práctica PENDIENTE_INICIO.');
        }

        return (int) $id;
    }
}

if (!function_exists('practicante_registro_rol_practicante_id')) {
    function practicante_registro_rol_practicante_id(PDO $pdo): int
    {
        $statement = $pdo->prepare(
            'SELECT id_rol
             FROM roles_sistema
             WHERE nombre = :nombre
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['nombre' => 'PRACTICANTE']);

        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No existe el rol PRACTICANTE.');
        }

        return (int) $id;
    }
}

if (!function_exists('practicante_registro_validar_unicidad')) {
    function practicante_registro_validar_unicidad(PDO $pdo, array $data): array
    {
        $errores = [];

        if ($data['rut_numero'] !== null && $data['rut_dv'] !== null) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM practicantes
                 WHERE rut_numero = :rut_numero
                   AND rut_dv = :rut_dv'
            );
            $statement->execute([
                'rut_numero' => $data['rut_numero'],
                'rut_dv' => $data['rut_dv'],
            ]);

            if ((int) $statement->fetchColumn() > 0) {
                $errores[] = 'Ya existe un practicante registrado con ese RUT.';
            }
        }

        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM practicantes
             WHERE email = :email'
        );
        $statement->execute(['email' => $data['email']]);

        if ((int) $statement->fetchColumn() > 0) {
            $errores[] = 'Ya existe un practicante registrado con ese correo.';
        }

        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM usuarios_sistema
             WHERE email = :email'
        );
        $statement->execute(['email' => $data['email']]);

        if ((int) $statement->fetchColumn() > 0) {
            $errores[] = 'Ya existe una cuenta de usuario con ese correo.';
        }

        return $errores;
    }
}

if (!function_exists('practicante_registro_guardar')) {
    function practicante_registro_guardar(array $data): array
    {
        $pdo = db();
        $erroresUnicidad = practicante_registro_validar_unicidad($pdo, $data);

        if ($erroresUnicidad !== []) {
            return ['ok' => false, 'errores' => $erroresUnicidad];
        }

        $tokenPlano = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenPlano);
        $passwordTemporal = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $fechaExpiracion = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRATION_HOURS * 3600));

        try {
            $pdo->beginTransaction();

            $estadoPendienteId = practicante_registro_estado_pendiente_id($pdo);
            $rolPracticanteId = practicante_registro_rol_practicante_id($pdo);

            $statement = $pdo->prepare(
                'INSERT INTO practicantes (
                    rut_numero,
                    rut_dv,
                    nombre_1,
                    nombre_2,
                    apellido_paterno,
                    apellido_materno,
                    email,
                    telefono,
                    fecha_nacimiento,
                    sexo,
                    nacionalidad,
                    direccion,
                    numero_direccion,
                    departamento,
                    id_comuna,
                    pais,
                    id_region,
                    id_institucion,
                    id_sede,
                    institucion_otra,
                    sede_otra,
                    id_carrera,
                    semestre,
                    activo
                ) VALUES (
                    :rut_numero,
                    :rut_dv,
                    :nombre_1,
                    :nombre_2,
                    :apellido_paterno,
                    :apellido_materno,
                    :email,
                    :telefono,
                    :fecha_nacimiento,
                    :sexo,
                    :nacionalidad,
                    :direccion,
                    :numero_direccion,
                    :departamento,
                    :id_comuna,
                    :pais,
                    :id_region,
                    :id_institucion,
                    :id_sede,
                    :institucion_otra,
                    :sede_otra,
                    :id_carrera,
                    :semestre,
                    1
                )'
            );
            $statement->execute([
                'rut_numero' => $data['rut_numero'],
                'rut_dv' => $data['rut_dv'],
                'nombre_1' => $data['nombre_1'],
                'nombre_2' => $data['nombre_2'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
                'email' => $data['email'],
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
            ]);
            $idPracticante = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO practicas (
                    id_practicante,
                    id_tipo_practica,
                    id_estado_practica,
                    horas_requeridas,
                    horas_previas,
                    fecha_inicio_practica,
                    activo
                ) VALUES (
                    :id_practicante,
                    :id_tipo_practica,
                    :id_estado_practica,
                    :horas_requeridas,
                    :horas_previas,
                    :fecha_inicio_practica,
                    1
                )'
            );
            $statement->execute([
                'id_practicante' => $idPracticante,
                'id_tipo_practica' => $data['id_tipo_practica'],
                'id_estado_practica' => $estadoPendienteId,
                'horas_requeridas' => $data['horas_requeridas'],
                'horas_previas' => $data['horas_previas'] ?? 0,
                'fecha_inicio_practica' => $data['fecha_inicio_practica'],
            ]);
            $idPractica = (int) $pdo->lastInsertId();
            $idHorario = horarios_practicante_guardar(
                $idPractica,
                (string) $data['fecha_inicio_practica'],
                $data['horario']
            );

            $statement = $pdo->prepare(
                'INSERT INTO usuarios_sistema (
                    id_practicante,
                    email,
                    password_hash,
                    estado_cuenta,
                    debe_cambiar_password,
                    email_verificado,
                    activo
                ) VALUES (
                    :id_practicante,
                    :email,
                    :password_hash,
                    :estado_cuenta,
                    1,
                    0,
                    1
                )'
            );
            $statement->execute([
                'id_practicante' => $idPracticante,
                'email' => $data['email'],
                'password_hash' => $passwordTemporal,
                'estado_cuenta' => 'PENDIENTE_ACTIVACION',
            ]);
            $idUsuario = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO usuarios_roles (id_usuario, id_rol, rol_activo)
                 VALUES (:id_usuario, :id_rol, 1)'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'id_rol' => $rolPracticanteId,
            ]);

            $statement = $pdo->prepare(
                'INSERT INTO tokens_usuario (
                    id_usuario,
                    tipo_token,
                    token_hash,
                    fecha_expiracion,
                    usado
                ) VALUES (
                    :id_usuario,
                    :tipo_token,
                    :token_hash,
                    :fecha_expiracion,
                    0
                )'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'tipo_token' => 'ACTIVACION_CUENTA',
                'token_hash' => $tokenHash,
                'fecha_expiracion' => $fechaExpiracion,
            ]);
            $idToken = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Error al crear practicante: ' . $exception->getMessage());

            return [
                'ok' => false,
                'errores' => ['No fue posible guardar el pre-registro. Revisa la base de datos e inténtalo nuevamente.'],
            ];
        }

        $nombreCompleto = trim($data['nombre_1'] . ' ' . $data['apellido_paterno']);
        $urlActivacion = url('activar-cuenta.php?token=' . urlencode($tokenPlano));
        $envio = mailer_enviar_activacion($data['email'], $nombreCompleto, $urlActivacion);

        auditoria_registrar('CREAR_PRACTICANTE', [
            'tabla_afectada' => 'practicantes',
            'id_registro_afectado' => $idPracticante,
            'valores_nuevos' => [
                'id_practicante' => $idPracticante,
                'id_practica' => $idPractica,
                'id_horario' => $idHorario,
                'id_usuario' => $idUsuario,
            ],
        ]);
        auditoria_registrar('CREACION_USUARIO', [
            'tabla_afectada' => 'usuarios_sistema',
            'id_registro_afectado' => $idUsuario,
            'valores_nuevos' => [
                'estado_cuenta' => 'PENDIENTE_ACTIVACION',
                'rol' => 'PRACTICANTE',
            ],
        ]);
        auditoria_registrar('TOKEN_GENERADO', [
            'tabla_afectada' => 'tokens_usuario',
            'id_registro_afectado' => $idToken,
            'valores_nuevos' => [
                'tipo_token' => 'ACTIVACION_CUENTA',
                'fecha_expiracion' => $fechaExpiracion,
            ],
        ]);

        return [
            'ok' => true,
            'id_practicante' => $idPracticante,
            'email_enviado' => (bool) ($envio['ok'] ?? false),
            'url_activacion' => (bool) ($envio['ok'] ?? false) ? null : $urlActivacion,
        ];
    }
}

if (!function_exists('practicante_registro_reenviar_activacion')) {
    /**
     * Genera un nuevo token de activación para un practicante cuya cuenta aún no ha sido activada.
     * Marca como usados todos los tokens anteriores de ACTIVACION_CUENTA para ese usuario,
     * inserta un token nuevo con expiración de TOKEN_EXPIRATION_HOURS horas y reenvía el email.
     *
     * @param  int    $idPracticante  ID del practicante al que se le reenviará el link
     * @return array  ['ok' => bool, 'errores' => string[], 'email_enviado' => bool, 'url_activacion' => string|null]
     */
    function practicante_registro_reenviar_activacion(int $idPracticante): array
    {
        $pdo = db();

        // Buscar el usuario asociado al practicante y verificar que aún NO esté activo
        $stmtUsuario = $pdo->prepare(
            'SELECT u.id_usuario, u.email, u.estado_cuenta,
                    p.nombre_1, p.apellido_paterno
             FROM usuarios_sistema u
             INNER JOIN practicantes p ON p.id_practicante = u.id_practicante
             WHERE u.id_practicante = :id_practicante
               AND u.activo = 1
             LIMIT 1'
        );
        $stmtUsuario->execute(['id_practicante' => $idPracticante]);
        $usuario = $stmtUsuario->fetch();

        // Si no encontramos usuario asociado, informar el error
        if (!is_array($usuario)) {
            return ['ok' => false, 'errores' => ['No se encontró una cuenta de usuario para este practicante.'], 'email_enviado' => false, 'url_activacion' => null];
        }

        // Solo se puede reenviar si la cuenta NO está activa todavía
        if ($usuario['estado_cuenta'] === 'ACTIVA') {
            return ['ok' => false, 'errores' => ['La cuenta ya está activa. No es necesario reenviar el link.'], 'email_enviado' => false, 'url_activacion' => null];
        }

        // Generar nuevo token en texto plano y su hash SHA-256 para guardar en BD
        $tokenPlano      = bin2hex(random_bytes(32));
        $tokenHash       = hash('sha256', $tokenPlano);
        $fechaExpiracion = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRATION_HOURS * 3600));

        try {
            $pdo->beginTransaction();

            // Invalidar todos los tokens de activación anteriores de este usuario
            // para que solo el nuevo link funcione
            $stmtInvalidar = $pdo->prepare(
                'UPDATE tokens_usuario
                 SET usado = 1
                 WHERE id_usuario = :id_usuario
                   AND tipo_token = :tipo_token
                   AND usado = 0'
            );
            $stmtInvalidar->execute([
                'id_usuario' => $usuario['id_usuario'],
                'tipo_token' => 'ACTIVACION_CUENTA',
            ]);

            // Insertar el nuevo token con la nueva fecha de expiración
            $stmtInsert = $pdo->prepare(
                'INSERT INTO tokens_usuario (id_usuario, tipo_token, token_hash, fecha_expiracion, usado)
                 VALUES (:id_usuario, :tipo_token, :token_hash, :fecha_expiracion, 0)'
            );
            $stmtInsert->execute([
                'id_usuario'      => $usuario['id_usuario'],
                'tipo_token'      => 'ACTIVACION_CUENTA',
                'token_hash'      => $tokenHash,
                'fecha_expiracion' => $fechaExpiracion,
            ]);

            $pdo->commit();
        } catch (Throwable $ex) {
            // Si algo falla, revertir todo para no dejar datos inconsistentes
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al reenviar activacion: ' . $ex->getMessage());
            return ['ok' => false, 'errores' => ['No fue posible regenerar el link de activación.'], 'email_enviado' => false, 'url_activacion' => null];
        }

        // Construir la URL de activación con el token en texto plano (no el hash)
        $urlActivacion  = url('activar-cuenta.php?token=' . urlencode($tokenPlano));
        $nombreCompleto = trim((string) $usuario['nombre_1'] . ' ' . (string) $usuario['apellido_paterno']);

        // Intentar enviar el email con el nuevo link
        $envio = mailer_enviar_activacion((string) $usuario['email'], $nombreCompleto, $urlActivacion);

        return [
            'ok'            => true,
            'errores'       => [],
            'email_enviado' => (bool) ($envio['ok'] ?? false),
            // Si el email falló, retornar la URL para que el admin pueda copiarla manualmente
            'url_activacion' => (bool) ($envio['ok'] ?? false) ? null : $urlActivacion,
        ];
    }
}

// Trasladé practicante_registro_render_select a la capa de presentación en fase 4.


// Trasladé practicante_registro_render a la capa de presentación en fase 4.
