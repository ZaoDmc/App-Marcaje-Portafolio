<?php
// Migré la presentación a utilidades/componentes Tailwind; conservé datos, acciones y validaciones.
 require_once VIEWS_PATH . '/layout/head.php'; ?>
<?php require_once VIEWS_PATH . '/layout/sidebar.php'; ?>

            <form class="dashboard-filters" method="get" action="<?= e(url('admin/dashboard.php')) ?>">
                <div class="filter-row filter-row-main">
                    <label class="search-control" for="dashboard-search">
                        <i class="bi bi-search"></i>
                        <input id="dashboard-search" name="q" type="search" value="<?= e((string) $filtros['q']) ?>" placeholder="Buscar practicantes, instituciones, sedes, carreras o areas">
                    </label>

                    <label class="period-control" for="dashboard-period">
                        <i class="bi bi-calendar3"></i>
                        <select id="dashboard-period" name="periodo">
                            <option value="7d" <?= $filtros['periodo'] === '7d' ? 'selected' : '' ?>>Ultimos 7 dias</option>
                            <option value="30d" <?= $filtros['periodo'] === '30d' ? 'selected' : '' ?>>Ultimos 30 dias</option>
                            <option value="this_month" <?= $filtros['periodo'] === 'this_month' ? 'selected' : '' ?>>Este mes</option>
                            <option value="last_month" <?= $filtros['periodo'] === 'last_month' ? 'selected' : '' ?>>Mes anterior</option>
                            <option value="custom" <?= $filtros['periodo'] === 'custom' ? 'selected' : '' ?>>Rango manual</option>
                        </select>
                    </label>

                    <label class="date-control" for="fecha-desde">
                        <span>Desde</span>
                        <input id="fecha-desde" name="fecha_desde" type="date" value="<?= e((string) $filtros['fecha_desde']) ?>">
                    </label>

                    <label class="date-control" for="fecha-hasta">
                        <span>Hasta</span>
                        <input id="fecha-hasta" name="fecha_hasta" type="date" value="<?= e((string) $filtros['fecha_hasta']) ?>">
                    </label>
                </div>

                <div class="filter-row filter-row-secondary">
                    <label class="select-control" for="id-institucion">
                        <span>Institucion</span>
                        <select id="id-institucion" name="id_institucion">
                            <option value="">Todas</option>
                            <?php foreach ($catalogos['instituciones'] as $institucion): ?>
                                <option value="<?= e((string) $institucion['id']) ?>" <?= (int) $filtros['id_institucion'] === (int) $institucion['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $institucion['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="select-control" for="id-sede">
                        <span>Sede</span>
                        <select id="id-sede" name="id_sede">
                            <option value="">Todas</option>
                            <?php foreach ($catalogos['sedes'] as $sede): ?>
                                <option value="<?= e((string) $sede['id']) ?>" <?= (int) $filtros['id_sede'] === (int) $sede['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $sede['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="select-control" for="id-carrera">
                        <span>Carrera</span>
                        <select id="id-carrera" name="id_carrera">
                            <option value="">Todas</option>
                            <?php foreach ($catalogos['carreras'] as $carrera): ?>
                                <option value="<?= e((string) $carrera['id']) ?>" <?= (int) $filtros['id_carrera'] === (int) $carrera['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $carrera['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="select-control" for="id-area">
                        <span>Area</span>
                        <select id="id-area" name="id_area">
                            <option value="">Todas</option>
                            <?php foreach ($catalogos['areas'] as $area): ?>
                                <option value="<?= e((string) $area['id']) ?>" <?= (int) $filtros['id_area'] === (int) $area['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $area['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="select-control" for="id-estado-practica">
                        <span>Estado</span>
                        <select id="id-estado-practica" name="id_estado_practica">
                            <option value="">Todos</option>
                            <?php foreach ($catalogos['estados'] as $estado): ?>
                                <option value="<?= e((string) $estado['id']) ?>" <?= (int) $filtros['id_estado_practica'] === (int) $estado['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $estado['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="filter-actions">
                        <button type="submit"><i class="bi bi-funnel"></i> Aplicar</button>
                        <a href="<?= e(url('admin/dashboard.php')) ?>">Limpiar</a>
                    </div>
                </div>

                <div class="filter-summary">
                    <span><i class="bi bi-calendar-range"></i> <?= e($periodoLabel) ?></span>
                    <span><i class="bi bi-sliders"></i> <?= e((string) $filtrosActivos) ?> filtros activos</span>
                </div>
            </form>

            <?php if ($dashboardErrores !== []): ?>
                <div class="rrhh-alert border-amber-200 bg-amber-50 text-amber-900" role="alert">
                    <?php foreach ($dashboardErrores as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="kpi-grid" aria-label="Metricas principales">
                <?php foreach ($kpis as $kpi): ?>
                    <article class="kpi-card">
                        <!-- Apliqué el color principal seleccionado a los iconos de KPI del administrador. -->
                        <span class="rrhh-dashboard-icon kpi-icon"><i class="bi <?= e((string) $kpi['icono']) ?>"></i></span>
                        <div class="kpi-content">
                            <strong><?= e((string) $kpi['valor']) ?></strong>
                            <span><?= e((string) $kpi['titulo']) ?></span>
                            <small><?= e((string) $kpi['detalle']) ?></small>
                        </div>
                        <span class="kpi-trend <?= e((string) $kpi['tendencia']['clase']) ?>">
                            <i class="bi <?= e((string) $kpi['tendencia']['icono']) ?>"></i>
                            <?= e((string) $kpi['tendencia']['label']) ?>
                        </span>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="analytics-grid" aria-label="Graficos del dashboard">
                <article class="dashboard-panel panel-wide">
                    <div class="panel-heading">
                        <div>
                            <span>Asistencia</span>
                            <h2>Seguimiento del periodo de asistencia y horas</h2>
                            <small><?= e($periodoLabel) ?> vs <?= e($periodoAnteriorLabel) ?></small>
                        </div>
                        <div class="chart-legend">
                            <span><i class="legend-line"></i>Horas registradas</span>
                            <span><i class="legend-line previous"></i>Periodo anterior</span>
                            <span><i class="legend-line dashed"></i>Jornadas</span>
                        </div>
                    </div>
                    <div class="chart-box line-chart">
                        <canvas id="weeklyAttendanceChart"></canvas>
                    </div>
                </article>

                <article class="dashboard-panel">
                    <div class="panel-heading">
                        <div>
                            <span>Estado</span>
                            <h2>Distribucion por practica</h2>
                        </div>
                    </div>
                    <div class="chart-box doughnut-chart">
                        <canvas id="stateDistributionChart"></canvas>
                    </div>
                </article>

                <article class="dashboard-panel">
                    <div class="panel-heading">
                        <div>
                            <span>Areas</span>
                            <h2>Practicantes por area</h2>
                        </div>
                    </div>
                    <div class="chart-box bar-chart">
                        <canvas id="areaDistributionChart"></canvas>
                    </div>
                </article>
            </section>

            <section class="operations-grid">
                <article class="dashboard-panel records-panel">
                    <div class="panel-heading">
                        <div>
                            <span>Actividad</span>
                            <h2>Registros recientes</h2>
                        </div>
                        <a href="<?= e(url('admin/asistencia.php')) ?>">Ver todos <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if ($registrosRecientes === []): ?>
                        <p class="empty-state">Aun no hay jornadas registradas para mostrar.</p>
                    <?php else: ?>
                        <div class="rrhh-table-scroll overflow-x-auto">
                            <table class="rrhh-table dashboard-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Practicante</th>
                                        <th>Institucion</th>
                                        <th>Area</th>
                                        <th>Estado</th>
                                        <th>Horas</th>
                                        <th>Ultima asistencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrosRecientes as $registro): ?>
                                        <tr>
                                            <td>
                                                <div class="student-cell">
                                                    <span><?= e(dashboard_admin_iniciales((string) $registro['practicante'])) ?></span>
                                                    <strong><?= e((string) $registro['practicante']) ?></strong>
                                                </div>
                                            </td>
                                            <td><?= e((string) $registro['institucion']) ?></td>
                                            <td><?= e((string) $registro['area']) ?></td>
                                            <td>
                                                <span class="status-pill <?= e(dashboard_admin_estado_clase((string) $registro['estado'])) ?>">
                                                    <?= e((string) $registro['estado']) ?>
                                                </span>
                                            </td>
                                            <td><?= e(dashboard_admin_numero((float) $registro['horas'], 1)) ?></td>
                                            <td><?= e(dashboard_admin_fecha((string) $registro['fecha'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="dashboard-panel alerts-panel">
                    <div class="panel-heading">
                        <div>
                            <span>Seguimiento</span>
                            <h2>Alertas operativas</h2>
                        </div>
                        <strong><?= e(dashboard_admin_numero((int) $alertasActivas)) ?> activas</strong>
                    </div>

                    <div class="alert-list">
                        <?php foreach ($alertas as $alerta): ?>
                            <?php
                                $alertIcon = match ((string) $alerta['nivel']) {
                                    'warning' => 'bi-exclamation-triangle-fill',
                                    'attention' => 'bi-clock-fill',
                                    default => 'bi-check-circle-fill',
                                };
                            ?>
                            <a class="alert-item <?= e((string) $alerta['nivel']) ?>" href="<?= e(url((string) $alerta['url'])) ?>">
                                <span><i class="bi <?= e($alertIcon) ?>"></i></span>
                                <span>
                                    <strong><?= e((string) $alerta['titulo']) ?></strong>
                                    <small><?= e((string) $alerta['detalle']) ?></small>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>

            <section class="capabilities-strip" aria-label="Capacidades administrativas">
                <article>
                    <i class="bi bi-shield-fill-check"></i>
                    <div>
                        <strong>Trazabilidad</strong>
                        <span>Seguimiento verificable de acciones criticas.</span>
                    </div>
                </article>
                <article>
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong>Gestion por roles</strong>
                        <span>Permisos diferenciados para mayor seguridad.</span>
                    </div>
                </article>
                <article>
                    <i class="bi bi-file-earmark-arrow-down"></i>
                    <div>
                        <strong>Reportes exportables</strong>
                        <span>Base preparada para formatos operativos.</span>
                    </div>
                </article>
                <article>
                    <i class="bi bi-bar-chart-fill"></i>
                    <div>
                        <strong>Indicadores verificables</strong>
                        <span>Datos confiables para decision institucional.</span>
                    </div>
                </article>
            </section>
    <script>
        window.adminDashboardData = <?= $chartDataJson !== false ? $chartDataJson : '{}' ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <!-- Agregué el inicializador después de Chart.js y los datos: crea las gráficas del dashboard. -->
    <script src="<?= e(asset('js/admin-dashboard.js')) ?>"></script>
<?php require_once VIEWS_PATH . '/layout/foot.php'; ?>
