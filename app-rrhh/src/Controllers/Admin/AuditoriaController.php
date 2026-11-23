<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';

auth_requiere_rol('ADMIN');

if (!function_exists('auditoria_admin_fecha_valida')) {
    function auditoria_admin_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('auditoria_admin_formatear_fecha_hora')) {
    function auditoria_admin_formatear_fecha_hora(?string $fechaHora): string
    {
        if ($fechaHora === null || $fechaHora === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('d-m-Y H:i', $timestamp);
    }
}

if (!function_exists('auditoria_admin_filtros')) {
    function auditoria_admin_filtros(): array
    {
        $desde = query_param('desde');
        $hasta = query_param('hasta');
        $evento = strtoupper(trim((string) query_param('evento', '')));
        $tabla = trim((string) query_param('tabla', ''));
        $busqueda = trim((string) query_param('q', ''));

        $desde = is_string($desde) && auditoria_admin_fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-7 days'));
        $hasta = is_string($hasta) && auditoria_admin_fecha_valida($hasta) ? $hasta : date('Y-m-d');

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'evento' => $evento,
            'tabla' => $tabla,
            'q' => $busqueda,
        ];
    }
}

if (!function_exists('auditoria_admin_eventos')) {
    function auditoria_admin_eventos(): array
    {
        return array_merge(auditoria_eventos_seguridad(), auditoria_eventos_funcionales());
    }
}

if (!function_exists('auditoria_admin_tablas')) {
    function auditoria_admin_tablas(): array
    {
        $statement = db()->query(
            'SELECT DISTINCT tabla_afectada
             FROM auditoria_cambios
             WHERE tabla_afectada IS NOT NULL
               AND tabla_afectada <> ""
             ORDER BY tabla_afectada ASC'
        );

        return array_map(static fn (array $fila): string => (string) $fila['tabla_afectada'], $statement->fetchAll());
    }
}

if (!function_exists('auditoria_admin_redactar')) {
    function auditoria_admin_redactar(mixed $valor): mixed
    {
        if (!is_array($valor)) {
            return $valor;
        }

        $redactado = [];

        foreach ($valor as $clave => $item) {
            $claveTexto = strtolower((string) $clave);

            if (str_contains($claveTexto, 'password')
                || str_contains($claveTexto, 'token')
                || str_contains($claveTexto, 'hash')
                || str_contains($claveTexto, 'pass')) {
                $redactado[$clave] = '[redactado]';
                continue;
            }

            $redactado[$clave] = auditoria_admin_redactar($item);
        }

        return $redactado;
    }
}

if (!function_exists('auditoria_admin_resumir_valor')) {
    function auditoria_admin_resumir_valor(?string $valor): string
    {
        if ($valor === null || trim($valor) === '') {
            return 'Sin datos';
        }

        $decodificado = json_decode($valor, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $valor = (string) json_encode(auditoria_admin_redactar($decodificado), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $valor = preg_replace('/\s+/', ' ', (string) $valor);
        $valor = trim((string) $valor);

        return strlen($valor) > 500 ? substr($valor, 0, 500) . '...' : $valor;
    }
}

if (!function_exists('auditoria_admin_buscar')) {
    function auditoria_admin_buscar(array $filtros): array
    {
        $where = ['ac.created_at BETWEEN :desde AND :hasta'];
        $params = [
            'desde' => $filtros['desde'] . ' 00:00:00',
            'hasta' => $filtros['hasta'] . ' 23:59:59',
        ];

        if (($filtros['evento'] ?? '') !== '' && in_array($filtros['evento'], auditoria_admin_eventos(), true)) {
            $where[] = 'ac.evento = :evento';
            $params['evento'] = $filtros['evento'];
        }

        if (($filtros['tabla'] ?? '') !== '') {
            $where[] = 'ac.tabla_afectada = :tabla';
            $params['tabla'] = $filtros['tabla'];
        }

        if (($filtros['q'] ?? '') !== '') {
            $where[] = '(
                us.email LIKE :busqueda_email
                OR ac.evento LIKE :busqueda_evento
                OR ac.tabla_afectada LIKE :busqueda_tabla
                OR ac.ip LIKE :busqueda_ip
            )';
            $busqueda = '%' . $filtros['q'] . '%';
            $params['busqueda_email'] = $busqueda;
            $params['busqueda_evento'] = $busqueda;
            $params['busqueda_tabla'] = $busqueda;
            $params['busqueda_ip'] = $busqueda;
        }

        $statement = db()->prepare(
            'SELECT
                ac.*,
                us.email AS actor_email,
                rs.nombre AS rol_contexto_nombre
             FROM auditoria_cambios ac
             LEFT JOIN usuarios_sistema us ON us.id_usuario = ac.id_usuario_actor
             LEFT JOIN roles_sistema rs ON rs.id_rol = ac.id_rol_contexto
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ac.created_at DESC, ac.id_auditoria DESC
             LIMIT 200'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('auditoria_admin_resumen')) {
    function auditoria_admin_resumen(array $eventos): array
    {
        $seguridad = 0;
        $funcionales = 0;
        $usuarios = [];

        foreach ($eventos as $evento) {
            if (in_array((string) $evento['evento'], auditoria_eventos_seguridad(), true)) {
                $seguridad++;
            }

            if (in_array((string) $evento['evento'], auditoria_eventos_funcionales(), true)) {
                $funcionales++;
            }

            if (!empty($evento['actor_email'])) {
                $usuarios[(string) $evento['actor_email']] = true;
            }
        }

        return [
            'total' => count($eventos),
            'seguridad' => $seguridad,
            'funcionales' => $funcionales,
            'usuarios' => count($usuarios),
        ];
    }
}

$errores = [];
$filtros = auditoria_admin_filtros();
$eventosCatalogo = auditoria_admin_eventos();
$tablasCatalogo = [];
$eventos = [];

try {
    $tablasCatalogo = auditoria_admin_tablas();
    $eventos = auditoria_admin_buscar($filtros);
} catch (Throwable $exception) {
    error_log('Error al cargar auditoria visible: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar la auditoria.';
}

$resumen = auditoria_admin_resumen($eventos);
$pageTitle = 'Auditoria';
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/auditoria.view.php';
