<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Services/AuthService.php';
// Protegí el área que todavía tiene un líder designado.
require_once __DIR__ . '/../Services/LiderazgoService.php';

/*
|--------------------------------------------------------------------------
| Administración de catálogos maestros
|--------------------------------------------------------------------------
|
| CRUD genérico y reservado a ADMIN para los catálogos simples que
| alimentan el pre-registro y la carga masiva de practicantes
| (instituciones, sedes, carreras, tipos de práctica, nacionalidades).
| Cada catálogo se describe una vez en catalogos_admin_definiciones() y
| el resto de funciones opera de forma genérica sobre esa definición, en
| vez de duplicar un CRUD por catálogo.
|
*/

if (!function_exists('catalogos_admin_definiciones')) {
    function catalogos_admin_definiciones(): array
    {
        return [
            // Habilité el catálogo de áreas que ADMIN necesita para organizar los equipos y sus líderes.
            'areas' => [
                'tabla'=>'areas_practica', 'pk'=>'id_area', 'titulo'=>'Áreas', 'singular'=>'área', 'orden'=>'nombre ASC',
                'campos'=>[
                    'nombre'=>['label'=>'Nombre','tipo'=>'texto','requerido'=>true,'maxlength'=>150],
                    'descripcion'=>['label'=>'Descripción','tipo'=>'texto','requerido'=>false,'maxlength'=>1000],
                ],
            ],
            // Agregué el calendario común de feriados, gestionado exclusivamente por ADMIN.
            'feriados' => [
                'tabla'=>'feriados','pk'=>'id_feriado','titulo'=>'Feriados','singular'=>'feriado',
                'orden'=>'fecha DESC', 'campos'=>[
                    'nombre'=>['label'=>'Nombre','tipo'=>'texto','requerido'=>true,'maxlength'=>150],
                    'fecha'=>['label'=>'Fecha','tipo'=>'fecha','requerido'=>true],
                ],
            ],
            'instituciones' => [
                'tabla' => 'instituciones',
                'pk' => 'id_institucion',
                'titulo' => 'Instituciones',
                'singular' => 'institución',
                'orden' => 'nombre ASC',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 150],
                ],
            ],
            'sedes' => [
                'tabla' => 'sedes',
                'pk' => 'id_sede',
                'titulo' => 'Sedes',
                'singular' => 'sede',
                'orden' => 'id_institucion ASC, nombre_sede ASC',
                'campos' => [
                    'id_institucion' => ['label' => 'Institución', 'tipo' => 'catalogo', 'requerido' => true, 'catalogo_fk' => 'instituciones'],
                    'nombre_sede' => ['label' => 'Nombre sede', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 150],
                    'codigo_sede' => ['label' => 'Código sede', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 80],
                ],
            ],
            'carreras' => [
                'tabla' => 'carreras',
                'pk' => 'id_carrera',
                'titulo' => 'Carreras',
                'singular' => 'carrera',
                'orden' => 'nombre ASC',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 150],
                ],
            ],
            'tipos_practica' => [
                'tabla' => 'tipos_practica',
                'pk' => 'id_tipo_practica',
                'titulo' => 'Tipos de práctica',
                'singular' => 'tipo de práctica',
                'articulo_nuevo' => 'Nuevo',
                'orden' => 'id_tipo_practica ASC',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 100],
                    'horas_base' => ['label' => 'Horas base', 'tipo' => 'entero', 'requerido' => true, 'min' => 0],
                ],
            ],
            'nacionalidades' => [
                'tabla' => 'nacionalidades',
                'pk' => 'id_nacionalidad',
                'titulo' => 'Nacionalidades',
                'singular' => 'nacionalidad',
                'orden' => 'nombre ASC',
                'campos' => [
                    'nombre' => ['label' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'maxlength' => 80],
                ],
            ],
        ];
    }
}

if (!function_exists('catalogos_admin_definicion')) {
    function catalogos_admin_definicion(string $clave): ?array
    {
        return catalogos_admin_definiciones()[$clave] ?? null;
    }
}

if (!function_exists('catalogos_admin_token')) {
    function catalogos_admin_token(): string
    {
        start_secure_session();

        if (empty($_SESSION['csrf_catalogos_admin'])) {
            $_SESSION['csrf_catalogos_admin'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_catalogos_admin'];
    }
}

if (!function_exists('catalogos_admin_opciones_fk')) {
    function catalogos_admin_opciones_fk(string $clave): array
    {
        $definicion = catalogos_admin_definicion($clave);

        if ($definicion === null) {
            return [];
        }

        $campoNombre = array_key_first($definicion['campos']);
        $statement = db()->query(
            'SELECT ' . $definicion['pk'] . ' AS id, ' . $campoNombre . ' AS nombre
             FROM ' . $definicion['tabla'] . '
             WHERE activo = 1
             ORDER BY ' . $definicion['orden']
        );

        $opciones = [];
        foreach ($statement->fetchAll() as $fila) {
            $opciones[(int) $fila['id']] = (string) $fila['nombre'];
        }

        return $opciones;
    }
}

if (!function_exists('catalogos_admin_listar')) {
    function catalogos_admin_listar(string $clave): array
    {
        $definicion = catalogos_admin_definicion($clave);

        if ($definicion === null) {
            return [];
        }

        $columnas = array_merge([$definicion['pk']], array_keys($definicion['campos']), ['activo']);
        $sql = 'SELECT ' . implode(', ', $columnas) . ' FROM ' . $definicion['tabla'] . ' ORDER BY ' . $definicion['orden'];
        $filas = db()->query($sql)->fetchAll();

        foreach ($definicion['campos'] as $campo => $meta) {
            if (($meta['tipo'] ?? 'texto') !== 'catalogo') {
                continue;
            }

            $opciones = catalogos_admin_opciones_fk($meta['catalogo_fk']);
            foreach ($filas as &$fila) {
                $fila[$campo . '_nombre'] = $opciones[(int) $fila[$campo]] ?? '—';
            }
            unset($fila);
        }

        return $filas;
    }
}

if (!function_exists('catalogos_admin_validar')) {
    function catalogos_admin_validar(array $definicion, array $datos): array
    {
        $errores = [];

        foreach ($definicion['campos'] as $campo => $meta) {
            $valor = $datos[$campo] ?? null;
            $tipo = $meta['tipo'] ?? 'texto';
            // Validé fechas civiles reales antes de guardarlas en el calendario compartido.
            if ($tipo === 'fecha') {
                $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $valor);
                if ($fecha === false || $fecha->format('Y-m-d') !== (string) $valor) { $errores[]='Ingresa una fecha válida.'; }
                continue;
            }

            if ($tipo === 'entero') {
                if ($valor === null || $valor === '') {
                    if ($meta['requerido'] ?? false) {
                        $errores[] = 'Ingresa ' . $meta['label'] . '.';
                    }
                    continue;
                }

                if (!ctype_digit((string) $valor) || (int) $valor < ($meta['min'] ?? 0)) {
                    $errores[] = $meta['label'] . ' debe ser un número válido.';
                }

                continue;
            }

            if ($tipo === 'catalogo') {
                if ($valor === null || $valor === '' || !ctype_digit((string) $valor)) {
                    if ($meta['requerido'] ?? false) {
                        $errores[] = 'Selecciona ' . $meta['label'] . '.';
                    }
                    continue;
                }

                $opciones = catalogos_admin_opciones_fk($meta['catalogo_fk']);

                if (!isset($opciones[(int) $valor])) {
                    $errores[] = 'Selecciona un valor válido para ' . $meta['label'] . '.';
                }

                continue;
            }

            $texto = trim((string) ($valor ?? ''));

            if ($texto === '' && ($meta['requerido'] ?? false)) {
                $errores[] = 'Ingresa ' . $meta['label'] . '.';
            }

            if (isset($meta['maxlength']) && mb_strlen($texto) > $meta['maxlength']) {
                $errores[] = $meta['label'] . ' no puede superar ' . $meta['maxlength'] . ' caracteres.';
            }
        }

        return $errores;
    }
}

if (!function_exists('catalogos_admin_guardar')) {
    function catalogos_admin_guardar(string $clave, ?int $id, array $datos): array
    {
        // El catálogo de áreas y sus nombres solo se administran desde ADMIN.
        if ($clave === 'areas' && (!auth_check() || auth_rol_activo() !== 'ADMIN')) {
            return ['ok'=>false,'errores'=>['Solo ADMIN puede administrar el catálogo de áreas.']];
        }
        // Exigí ADMIN también al guardar calendario, además del control de la pantalla.
        if ($clave==='feriados' && (!auth_check() || auth_rol_activo()!=='ADMIN')) { return ['ok'=>false,'errores'=>['Solo ADMIN mantiene feriados.']]; }
        $definicion = catalogos_admin_definicion($clave);

        if ($definicion === null) {
            return ['ok' => false, 'errores' => ['Catálogo no reconocido.']];
        }

        $errores = catalogos_admin_validar($definicion, $datos);

        if ($errores !== []) {
            return ['ok' => false, 'errores' => $errores];
        }

        $valores = [];
        foreach ($definicion['campos'] as $campo => $meta) {
            $valor = $datos[$campo] ?? null;
            $valores[$campo] = match ($meta['tipo'] ?? 'texto') {
                'entero', 'catalogo' => (int) $valor,
                default => trim((string) $valor),
            };
        }

        $pdo = db();

        try {
            if ($id === null) {
                $columnas = array_keys($valores);
                $marcadores = array_map(static fn (string $c): string => ':' . $c, $columnas);
                $statement = $pdo->prepare(
                    'INSERT INTO ' . $definicion['tabla'] . ' (' . implode(', ', $columnas) . ', activo)
                     VALUES (' . implode(', ', $marcadores) . ', 1)'
                );
                $statement->execute($valores);
                $idNuevo = (int) $pdo->lastInsertId();

                auditoria_registrar('CATALOGO_CREADO', [
                    'tabla_afectada' => $definicion['tabla'],
                    'id_registro_afectado' => $idNuevo,
                    'valores_nuevos' => $valores,
                ]);

                return ['ok' => true, 'mensaje' => ucfirst($definicion['singular']) . ' creada correctamente.'];
            }

            $asignaciones = implode(', ', array_map(static fn (string $c): string => $c . ' = :' . $c, array_keys($valores)));
            $statement = $pdo->prepare(
                'UPDATE ' . $definicion['tabla'] . ' SET ' . $asignaciones . ' WHERE ' . $definicion['pk'] . ' = :id_registro'
            );
            $valores['id_registro'] = $id;
            $statement->execute($valores);

            auditoria_registrar('CATALOGO_EDITADO', [
                'tabla_afectada' => $definicion['tabla'],
                'id_registro_afectado' => $id,
                'valores_nuevos' => $valores,
            ]);

            return ['ok' => true, 'mensaje' => ucfirst($definicion['singular']) . ' actualizada correctamente.'];
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return ['ok' => false, 'errores' => ['Ya existe un registro con esos datos (nombre o código duplicado).']];
            }

            error_log('Error al guardar catálogo ' . $clave . ': ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible guardar el registro.']];
        }
    }
}

if (!function_exists('catalogos_admin_cambiar_estado')) {
    function catalogos_admin_cambiar_estado(string $clave, int $id, bool $activo): array
    {
        // Reservé también al ADMIN los cambios del nuevo catálogo de áreas.
        if ($clave === 'areas' && (!auth_check() || auth_rol_activo() !== 'ADMIN')) {
            return ['ok'=>false,'errores'=>['Solo ADMIN puede administrar el catálogo de áreas.']];
        }
        // Impedí activar/desactivar feriados desde una llamada de otro rol.
        if ($clave==='feriados' && (!auth_check() || auth_rol_activo()!=='ADMIN')) { return ['ok'=>false,'errores'=>['Solo ADMIN mantiene feriados.']]; }
        $definicion = catalogos_admin_definicion($clave);

        if ($definicion === null) {
            return ['ok' => false, 'errores' => ['Catálogo no reconocido.']];
        }

        // Serialicé la desactivación del área con las designaciones; los demás catálogos conservan su flujo.
        $esArea = $definicion['tabla'] === 'areas_practica';
        try {
            if ($esArea) {
                db()->beginTransaction();
                $bloqueo = db()->prepare('SELECT id_area FROM areas_practica WHERE id_area=? FOR UPDATE');
                $bloqueo->execute([$id]);
                if (!$activo) { liderazgos_exigir_area_sin_lider($id); }
            }
            $statement = db()->prepare(
                'UPDATE ' . $definicion['tabla'] . ' SET activo = :activo WHERE ' . $definicion['pk'] . ' = :id'
            );
            $statement->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
            if ($esArea) { db()->commit(); }
        } catch (Throwable $exception) {
            if ($esArea && db()->inTransaction()) { db()->rollBack(); }
            if ($exception instanceof DomainException) { return ['ok'=>false,'errores'=>[$exception->getMessage()]]; }
            throw $exception;
        }

        auditoria_registrar($activo ? 'CATALOGO_ACTIVADO' : 'CATALOGO_DESACTIVADO', [
            'tabla_afectada' => $definicion['tabla'],
            'id_registro_afectado' => $id,
        ]);

        return ['ok' => true, 'mensaje' => 'Estado actualizado correctamente.'];
    }
}

// Trasladé el procesamiento HTTP de catalogos_admin_procesar_post al controlador compartido.
// Trasladé catalogos_admin_render a la capa de presentación en fase 4.
