<?php

declare(strict_types=1);

function formulario_validacion_configuracion(): array
{
    $statement = db()->query(
        'SELECT nombre_centro, rut_centro, direccion, comuna, giro_comercial,
                telefono, sitio_web, jefe_nombre, jefe_cargo, jefe_email, beneficios
         FROM formulario_validacion_configuracion
         WHERE id_configuracion = 1
         LIMIT 1'
    );
    $configuracion = $statement->fetch();

    return is_array($configuracion) ? $configuracion : [
        'nombre_centro' => '',
        'rut_centro' => '',
        'direccion' => '',
        'comuna' => '',
        'giro_comercial' => '',
        'telefono' => '',
        'sitio_web' => '',
        'jefe_nombre' => '',
        'jefe_cargo' => '',
        'jefe_email' => '',
        'beneficios' => '',
    ];
}

function formulario_validacion_guardar_configuracion(array $datos): void
{
    $statement = db()->prepare(
        'INSERT INTO formulario_validacion_configuracion (
            id_configuracion, nombre_centro, rut_centro, direccion, comuna,
            giro_comercial, telefono, sitio_web, jefe_nombre, jefe_cargo,
            jefe_email, beneficios, actualizado_por
         ) VALUES (
            1, :nombre_centro, :rut_centro, :direccion, :comuna,
            :giro_comercial, :telefono, :sitio_web, :jefe_nombre, :jefe_cargo,
            :jefe_email, :beneficios, :actualizado_por
         )
         ON DUPLICATE KEY UPDATE
            nombre_centro = VALUES(nombre_centro),
            rut_centro = VALUES(rut_centro),
            direccion = VALUES(direccion),
            comuna = VALUES(comuna),
            giro_comercial = VALUES(giro_comercial),
            telefono = VALUES(telefono),
            sitio_web = VALUES(sitio_web),
            jefe_nombre = VALUES(jefe_nombre),
            jefe_cargo = VALUES(jefe_cargo),
            jefe_email = VALUES(jefe_email),
            beneficios = VALUES(beneficios),
            actualizado_por = VALUES(actualizado_por)'
    );
    $statement->execute([
        'nombre_centro' => $datos['nombre_centro'],
        'rut_centro' => $datos['rut_centro'],
        'direccion' => $datos['direccion'],
        'comuna' => $datos['comuna'],
        'giro_comercial' => $datos['giro_comercial'],
        'telefono' => $datos['telefono'],
        'sitio_web' => $datos['sitio_web'],
        'jefe_nombre' => $datos['jefe_nombre'],
        'jefe_cargo' => $datos['jefe_cargo'],
        'jefe_email' => $datos['jefe_email'],
        'beneficios' => $datos['beneficios'],
        'actualizado_por' => auth_usuario_actual_id(),
    ]);
}

function formulario_validacion_carreras(): array
{
    return db()->query(
        'SELECT id_carrera, nombre
         FROM carreras
         WHERE activo = 1
         ORDER BY nombre ASC'
    )->fetchAll();
}

function formulario_validacion_instituciones(): array
{
    return db()->query(
        'SELECT id_institucion, nombre
         FROM instituciones
         WHERE activo = 1
         ORDER BY FIELD(nombre, "Duoc UC", "INACAP", "ENAC", "Otra"), nombre ASC'
    )->fetchAll();
}

function formulario_validacion_plantillas_carrera(): array
{
    return db()->query(
        'SELECT fv.id_carrera, c.nombre AS carrera, fv.unidad, fv.funciones
         FROM formulario_validacion_plantillas_carrera fv
         INNER JOIN carreras c ON c.id_carrera = fv.id_carrera
         WHERE c.activo = 1 AND fv.activo = 1
         ORDER BY c.nombre ASC'
    )->fetchAll();
}

function formulario_validacion_guardar_plantilla(int $idCarrera, string $unidad, string $funciones): void
{
    $statement = db()->prepare(
        'SELECT id_carrera
         FROM carreras
         WHERE id_carrera = :id_carrera AND activo = 1
         LIMIT 1'
    );
    $statement->execute(['id_carrera' => $idCarrera]);
    if ($statement->fetchColumn() === false) {
        throw new InvalidArgumentException('Selecciona una carrera activa.');
    }

    $statement = db()->prepare(
        'INSERT INTO formulario_validacion_plantillas_carrera (
            id_carrera, unidad, funciones, actualizado_por
         ) VALUES (
            :id_carrera, :unidad, :funciones, :actualizado_por
         )
         ON DUPLICATE KEY UPDATE
            unidad = VALUES(unidad),
            funciones = VALUES(funciones),
            actualizado_por = VALUES(actualizado_por),
            activo = 1'
    );
    $statement->execute([
        'id_carrera' => $idCarrera,
        'unidad' => $unidad,
        'funciones' => $funciones,
        'actualizado_por' => auth_usuario_actual_id(),
    ]);
}

function formulario_validacion_guardar_documento(
    int $idCarrera,
    string $rutaRelativa,
    string $nombreArchivo,
    int $tamanoBytes,
    string $institucionCodigo,
    string $versionPlantilla
): int {
    $statement = db()->prepare(
        'INSERT INTO documentos_validacion_generados (
            institucion_codigo, version_plantilla, id_carrera, nombre_archivo,
            ruta_relativa, mime_type, tamano_bytes, creado_por
         ) VALUES (
            :institucion_codigo, :version_plantilla, :id_carrera, :nombre_archivo,
            :ruta_relativa, "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            :tamano_bytes, :creado_por
         )'
    );
    $statement->execute([
        'institucion_codigo' => $institucionCodigo,
        'version_plantilla' => $versionPlantilla,
        'id_carrera' => $idCarrera,
        'nombre_archivo' => $nombreArchivo,
        'ruta_relativa' => $rutaRelativa,
        'tamano_bytes' => $tamanoBytes,
        'creado_por' => auth_usuario_actual_id(),
    ]);

    return (int) db()->lastInsertId();
}

function formulario_validacion_historial(int $limite = 50): array
{
    $limite = max(1, min(100, $limite));
    $statement = db()->query(
        'SELECT d.id_documento_validacion, d.version_plantilla,
                d.nombre_archivo, d.tamano_bytes, d.created_at,
                c.nombre AS carrera, u.email AS creado_por
         FROM documentos_validacion_generados d
         INNER JOIN carreras c ON c.id_carrera = d.id_carrera
         INNER JOIN usuarios_sistema u ON u.id_usuario = d.creado_por
         WHERE d.activo = 1
         ORDER BY d.created_at DESC, d.id_documento_validacion DESC
         LIMIT ' . $limite
    );

    return $statement->fetchAll();
}

function formulario_validacion_documento_por_id(int $idDocumento): ?array
{
    $statement = db()->prepare(
        'SELECT nombre_archivo, ruta_relativa, mime_type, tamano_bytes
         FROM documentos_validacion_generados
         WHERE id_documento_validacion = :id_documento
           AND activo = 1
         LIMIT 1'
    );
    $statement->execute(['id_documento' => $idDocumento]);
    $documento = $statement->fetch();

    return is_array($documento) ? $documento : null;
}
