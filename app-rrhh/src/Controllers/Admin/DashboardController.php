<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/MetricasModel.php';

auth_requiere_rol('ADMIN');

if (!function_exists('dashboard_admin_numero')) {
    function dashboard_admin_numero(int|float $valor, int $decimales = 0): string
    {
        return number_format((float) $valor, $decimales, ',', '.');
    }
}

if (!function_exists('dashboard_admin_fecha')) {
    function dashboard_admin_fecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'Sin fecha';
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? $fecha : date('d-m-Y', $timestamp);
    }
}

if (!function_exists('dashboard_admin_estado_clase')) {
    function dashboard_admin_estado_clase(string $estado): string
    {
        return match ($estado) {
            // Agregué la revisión al indicador de jornadas aceptadas.
            'VALIDA', 'CORREGIDA', 'REVISADA', 'ACTIVA', 'Activo' => 'status-ok',
            'OBSERVADA', 'PENDIENTE_REVISION', 'En evaluacion' => 'status-warning',
            'INCOMPLETA', 'ANULADA' => 'status-danger',
            default => 'status-neutral',
        };
    }
}

if (!function_exists('dashboard_admin_iniciales')) {
    function dashboard_admin_iniciales(string $email): string
    {
        $nombre = trim(explode('@', $email)[0] ?? '');

        if ($nombre === '') {
            return 'AD';
        }

        $partes = preg_split('/[^a-z0-9]+/i', $nombre) ?: [];
        $iniciales = '';

        foreach ($partes as $parte) {
            if ($parte !== '') {
                $iniciales .= strtoupper(substr($parte, 0, 1));
            }
        }

        return substr($iniciales !== '' ? $iniciales : 'AD', 0, 2);
    }
}

if (!function_exists('dashboard_admin_fecha_valida')) {
    function dashboard_admin_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('dashboard_admin_entero_filtro')) {
    function dashboard_admin_entero_filtro(string $clave): int
    {
        $valor = query_param($clave, '');

        return ctype_digit((string) $valor) ? (int) $valor : 0;
    }
}

if (!function_exists('dashboard_admin_filtros')) {
    function dashboard_admin_filtros(): array
    {
        $periodo = (string) query_param('periodo', '30d');
        $periodosPermitidos = ['7d', '30d', 'this_month', 'last_month', 'custom'];
        $periodo = in_array($periodo, $periodosPermitidos, true) ? $periodo : '30d';
        $hoy = new DateTimeImmutable('today');

        if ($periodo === '7d') {
            $desde = $hoy->modify('-6 days');
            $hasta = $hoy;
        } elseif ($periodo === 'this_month') {
            $desde = $hoy->modify('first day of this month');
            $hasta = $hoy;
        } elseif ($periodo === 'last_month') {
            $desde = $hoy->modify('first day of last month');
            $hasta = $hoy->modify('last day of last month');
        } elseif ($periodo === 'custom') {
            $desdeParam = (string) query_param('fecha_desde', '');
            $hastaParam = (string) query_param('fecha_hasta', '');

            if (dashboard_admin_fecha_valida($desdeParam) && dashboard_admin_fecha_valida($hastaParam)) {
                $desde = new DateTimeImmutable($desdeParam);
                $hasta = new DateTimeImmutable($hastaParam);
            } else {
                $periodo = '30d';
                $desde = $hoy->modify('-29 days');
                $hasta = $hoy;
            }
        } else {
            $desde = $hoy->modify('-29 days');
            $hasta = $hoy;
        }

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $busqueda = trim((string) query_param('q', ''));
        $busqueda = substr($busqueda, 0, 100);

        return [
            'periodo' => $periodo,
            'fecha_desde' => $desde->format('Y-m-d'),
            'fecha_hasta' => $hasta->format('Y-m-d'),
            'q' => $busqueda,
            'id_institucion' => dashboard_admin_entero_filtro('id_institucion'),
            'id_sede' => dashboard_admin_entero_filtro('id_sede'),
            'id_carrera' => dashboard_admin_entero_filtro('id_carrera'),
            'id_area' => dashboard_admin_entero_filtro('id_area'),
            'id_estado_practica' => dashboard_admin_entero_filtro('id_estado_practica'),
        ];
    }
}

if (!function_exists('dashboard_admin_periodo_label')) {
    function dashboard_admin_periodo_label(array $filtros): string
    {
        return dashboard_admin_fecha((string) $filtros['fecha_desde']) . ' al ' . dashboard_admin_fecha((string) $filtros['fecha_hasta']);
    }
}

if (!function_exists('dashboard_admin_filtros_activos')) {
    function dashboard_admin_filtros_activos(array $filtros): int
    {
        $activos = 0;

        foreach (['q', 'id_institucion', 'id_sede', 'id_carrera', 'id_area', 'id_estado_practica'] as $clave) {
            if (($filtros[$clave] ?? '') !== '' && (string) ($filtros[$clave] ?? '0') !== '0') {
                $activos++;
            }
        }

        return $activos;
    }
}

if (!function_exists('dashboard_admin_comparativo')) {
    function dashboard_admin_comparativo(int|float $actual, int|float $anterior, bool $menorEsMejor = false): array
    {
        $actual = (float) $actual;
        $anterior = (float) $anterior;
        $diferencia = $actual - $anterior;
        $porcentaje = $anterior > 0 ? round(($diferencia / $anterior) * 100, 1) : ($actual > 0 ? 100.0 : 0.0);
        $direccion = $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'flat');
        $positivo = $direccion === 'flat' || (($direccion === 'up') !== $menorEsMejor);

        return [
            'label' => ($porcentaje > 0 ? '+' : '') . dashboard_admin_numero($porcentaje, 1) . '% vs periodo anterior',
            'icono' => match ($direccion) {
                'up' => 'bi-arrow-up-short',
                'down' => 'bi-arrow-down-short',
                default => 'bi-dash',
            },
            'clase' => $direccion === 'flat' ? 'trend-neutral' : ($positivo ? 'trend-up' : 'trend-down'),
        ];
    }
}

if (!function_exists('dashboard_admin_comparativo_neutro')) {
    function dashboard_admin_comparativo_neutro(string $label = 'Dato actual'): array
    {
        return [
            'label' => $label,
            'icono' => 'bi-check2-circle',
            'clase' => 'trend-neutral',
        ];
    }
}

$dashboardErrores = [];
$filtros = dashboard_admin_filtros();
$filtrosPeriodoAnterior = dashboard_metricas_periodo_anterior($filtros);
$catalogos = [
    'instituciones' => [],
    'sedes' => [],
    'carreras' => [],
    'areas' => [],
    'estados' => [],
];
$metricas = [
    'practicantes_activos' => 0,
    'practicas_activas' => 0,
    'horas_30_dias' => 0,
    'pendientes_revision' => 0,
    'ausencias_esperadas' => 0,
    'horas_totales' => 0,
    'horas_requeridas' => 0,
    'avance_global' => 0,
];
$metricasAdmin = [
    'usuarios_activos' => 0,
    'eventos_24h' => 0,
];
$panelAdmin = [
    'instituciones_vinculadas' => 0,
    'avance_promedio' => 0,
    'seguimiento_semanal' => [],
    'distribucion_estados' => [],
    'practicantes_por_area' => [],
    'registros_recientes' => [],
    'alertas' => [],
];
$metricasAnteriores = $metricas;
$panelAdminAnterior = $panelAdmin;

try {
    $catalogos = dashboard_metricas_admin_catalogos();
    $metricas = array_merge($metricas, dashboard_metricas_resumen('ADMIN', $filtros));
    $metricasAdmin = array_merge($metricasAdmin, dashboard_metricas_admin_extra());
    $panelAdmin = array_merge($panelAdmin, dashboard_metricas_admin_panel($filtros));
    $metricasAnteriores = array_merge($metricasAnteriores, dashboard_metricas_resumen('ADMIN', $filtrosPeriodoAnterior));
    $panelAdminAnterior = array_merge($panelAdminAnterior, dashboard_metricas_admin_panel($filtrosPeriodoAnterior));
} catch (Throwable $exception) {
    error_log('Error al cargar metricas del dashboard ADMIN: ' . $exception->getMessage());
    $dashboardErrores[] = 'No fue posible cargar las metricas del dashboard.';
}

$seguimientoSemanal = is_array($panelAdmin['seguimiento_semanal']) ? $panelAdmin['seguimiento_semanal'] : [];
$seguimientoAnterior = is_array($panelAdminAnterior['seguimiento_semanal']) ? $panelAdminAnterior['seguimiento_semanal'] : [];
$distribucionEstados = is_array($panelAdmin['distribucion_estados']) ? $panelAdmin['distribucion_estados'] : [];
$practicantesPorArea = is_array($panelAdmin['practicantes_por_area']) ? $panelAdmin['practicantes_por_area'] : [];
$registrosRecientes = is_array($panelAdmin['registros_recientes']) ? $panelAdmin['registros_recientes'] : [];
$alertas = is_array($panelAdmin['alertas']) ? $panelAdmin['alertas'] : [];
$alertasActivas = array_sum(array_map(static fn (array $alerta): int => (int) $alerta['total'], $alertas));
$emailAdmin = (string) session_get('email', '');
$periodoLabel = dashboard_admin_periodo_label($filtros);
$periodoAnteriorLabel = dashboard_admin_periodo_label($filtrosPeriodoAnterior);
$filtrosActivos = dashboard_admin_filtros_activos($filtros);

$kpis = [
    [
        'icono' => 'bi-people-fill',
        'valor' => dashboard_admin_numero((int) $metricas['practicantes_activos']),
        'titulo' => 'Practicantes activos',
        'detalle' => dashboard_admin_numero((int) $metricas['practicas_activas']) . ' practicas activas',
        'tendencia' => dashboard_admin_comparativo_neutro('Dato actual'),
    ],
    [
        'icono' => 'bi-clock-history',
        // Formateo visual de horas registradas redondeadas a entero cerrado
        'valor' => formatear_horas_enteras($metricas['horas_periodo'] ?? $metricas['horas_30_dias']) . ' hrs',
        'titulo' => 'Horas registradas',
        'detalle' => 'Periodo: ' . $periodoLabel,
        'tendencia' => dashboard_admin_comparativo((float) ($metricas['horas_periodo'] ?? 0), (float) ($metricasAnteriores['horas_periodo'] ?? 0)),
    ],
    [
        'icono' => 'bi-building',
        'valor' => dashboard_admin_numero((int) $panelAdmin['instituciones_vinculadas']),
        'titulo' => 'Instituciones vinculadas',
        'detalle' => 'Con practicas activas',
        'tendencia' => dashboard_admin_comparativo_neutro('Catalogo filtrado'),
    ],
    [
        'icono' => 'bi-activity',
        'valor' => dashboard_admin_numero((float) $panelAdmin['avance_promedio'], 1) . '%',
        'titulo' => 'Avance promedio',
        'detalle' => dashboard_admin_numero((float) $metricas['avance_global'], 1) . '% avance global',
        'tendencia' => dashboard_admin_comparativo((float) $panelAdmin['avance_promedio'], (float) $panelAdminAnterior['avance_promedio']),
    ],
    [
        'icono' => 'bi-exclamation-diamond-fill',
        'valor' => dashboard_admin_numero((int) $metricas['pendientes_revision']) . ' / ' . dashboard_admin_numero((int) $metricas['ausencias_esperadas']),
        'titulo' => 'Pendientes / ausencias',
        'detalle' => 'Revision operativa',
        'tendencia' => dashboard_admin_comparativo((int) $metricas['pendientes_revision'] + (int) $metricas['ausencias_esperadas'], (int) $metricasAnteriores['pendientes_revision'] + (int) $metricasAnteriores['ausencias_esperadas'], true),
    ],
    [
        'icono' => 'bi-shield-check',
        'valor' => dashboard_admin_numero((int) $metricasAdmin['usuarios_activos']) . ' / ' . dashboard_admin_numero((int) $metricasAdmin['eventos_24h']),
        'titulo' => 'Usuarios / auditoria 24h',
        'detalle' => 'Cuentas activas y eventos',
        'tendencia' => dashboard_admin_comparativo_neutro('Trazable'),
    ],
];

$chartData = [
    'weekly' => [
        'labels' => array_map(static fn (array $fila): string => (string) $fila['dia'], $seguimientoSemanal),
        'hours' => array_map(static fn (array $fila): float => (float) $fila['horas'], $seguimientoSemanal),
        'attendance' => array_map(static fn (array $fila): int => (int) $fila['asistencias'], $seguimientoSemanal),
        'previousHours' => array_map(static fn (array $fila): float => (float) $fila['horas'], $seguimientoAnterior),
    ],
    'states' => [
        'labels' => array_map(static fn (array $fila): string => (string) $fila['estado'], $distribucionEstados),
        'values' => array_map(static fn (array $fila): int => (int) $fila['total'], $distribucionEstados),
    ],
    'areas' => [
        'labels' => array_map(static fn (array $fila): string => (string) $fila['area'], $practicantesPorArea),
        'values' => array_map(static fn (array $fila): int => (int) $fila['total'], $practicantesPorArea),
    ],
];
$chartDataJson = json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$pageTitle = 'Dashboard Administrador';
$extraStyles = [asset('css/admin-dashboard.css')];
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/dashboard.view.php';
