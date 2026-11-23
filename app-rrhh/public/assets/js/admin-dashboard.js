(function () {
    'use strict';

    var data = window.adminDashboardData || {};
    var colors = {
        blue: '#2563EB',
        blueDark: '#145DEB',
        blueLight: '#93C5FD',
        cyan: '#06B6D4',
        violet: '#7C3AED',
        yellow: '#F59E0B',
        green: '#16A34A',
        red: '#E30613',
        gray: '#CBD5E1',
        ink: '#0F172A',
        muted: '#64748B'
    };

    function hasValues(values) {
        return Array.isArray(values) && values.some(function (value) {
            return Number(value) > 0;
        });
    }

    function setupSidebar() {
        var toggle = document.querySelector('[data-sidebar-toggle]');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');
        var mobileQuery = window.matchMedia('(max-width: 1180px)');
        var stored = window.localStorage ? window.localStorage.getItem('adminSidebarCollapsed') : null;

        function isMobile() {
            return mobileQuery.matches;
        }

        function setExpanded(expanded) {
            if (toggle) {
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            }
        }

        function openMobileMenu() {
            document.body.classList.add('sidebar-mobile-open');
            if (backdrop) {
                backdrop.hidden = false;
            }
            setExpanded(true);
        }

        function closeMobileMenu() {
            document.body.classList.remove('sidebar-mobile-open');
            if (backdrop) {
                backdrop.hidden = true;
            }
            setExpanded(false);
        }

        function toggleDesktopSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
            var collapsed = document.body.classList.contains('sidebar-collapsed');
            if (window.localStorage) {
                window.localStorage.setItem('adminSidebarCollapsed', collapsed ? '1' : '0');
            }
            setExpanded(!collapsed);
        }

        if (stored === '1') {
            document.body.classList.add('sidebar-collapsed');
            setExpanded(false);
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (isMobile()) {
                    if (document.body.classList.contains('sidebar-mobile-open')) {
                        closeMobileMenu();
                    } else {
                        openMobileMenu();
                    }
                    return;
                }

                toggleDesktopSidebar();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeMobileMenu);
        }

        document.querySelectorAll('.admin-sidebar a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) {
                    closeMobileMenu();
                }
            });
        });

        mobileQuery.addEventListener('change', function () {
            closeMobileMenu();
            setExpanded(!document.body.classList.contains('sidebar-collapsed'));
        });
    }

    function replaceWithEmptyState(canvasId, message) {
        var canvas = document.getElementById(canvasId);

        if (!canvas || !canvas.parentNode) {
            return;
        }

        var empty = document.createElement('div');
        empty.className = 'chart-empty';
        empty.textContent = message;
        canvas.parentNode.replaceChild(empty, canvas);
    }

    function buildLineChart() {
        var weekly = data.weekly || {};
        var labels = weekly.labels || [];
        var hours = weekly.hours || [];
        var attendance = weekly.attendance || [];
        var previousHours = weekly.previousHours || [];
        var canvas = document.getElementById('weeklyAttendanceChart');

        if (!canvas) {
            return;
        }

        if (!hasValues(hours) && !hasValues(attendance)) {
            replaceWithEmptyState('weeklyAttendanceChart', 'Sin datos suficientes para el seguimiento semanal.');
            return;
        }

        var context = canvas.getContext('2d');
        var fillGradient = context.createLinearGradient(0, 0, 0, 280);
        fillGradient.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
        fillGradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Horas registradas',
                        data: hours,
                        borderColor: colors.blue,
                        backgroundColor: fillGradient,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: colors.blue,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        tension: 0.42,
                        fill: true
                    },
                    {
                        label: 'Periodo anterior',
                        data: previousHours,
                        borderColor: 'rgba(100, 116, 139, 0.58)',
                        backgroundColor: 'rgba(100, 116, 139, 0)',
                        borderWidth: 2,
                        pointRadius: 0,
                        borderDash: [4, 6],
                        tension: 0.42
                    },
                    {
                        label: 'Jornadas',
                        data: attendance,
                        borderColor: colors.cyan,
                        backgroundColor: 'rgba(6, 182, 212, 0.08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: colors.cyan,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        borderDash: [7, 5],
                        tension: 0.42,
                        yAxisID: 'yAttendance'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        padding: 12,
                        titleColor: '#FFFFFF',
                        bodyColor: '#E2E8F0'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: colors.muted
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.22)'
                        },
                        ticks: {
                            color: colors.muted,
                            precision: 0
                        }
                    },
                    yAttendance: {
                        beginAtZero: true,
                        display: false,
                        position: 'right',
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    function buildDoughnutChart() {
        var states = data.states || {};
        var labels = states.labels || [];
        var values = states.values || [];
        var canvas = document.getElementById('stateDistributionChart');

        if (!canvas) {
            return;
        }

        if (!hasValues(values)) {
            replaceWithEmptyState('stateDistributionChart', 'Sin practicas activas para distribuir.');
            return;
        }

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: [
                            colors.blue,
                            colors.cyan,
                            colors.green,
                            colors.yellow,
                            colors.violet,
                            colors.gray
                        ],
                        borderColor: '#FFFFFF',
                        borderWidth: 4,
                        hoverOffset: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            color: colors.ink,
                            padding: 14,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        padding: 12
                    }
                }
            }
        });
    }

    function buildBarChart() {
        var areas = data.areas || {};
        var labels = areas.labels || [];
        var values = areas.values || [];
        var canvas = document.getElementById('areaDistributionChart');

        if (!canvas) {
            return;
        }

        if (!hasValues(values)) {
            replaceWithEmptyState('areaDistributionChart', 'Sin areas asignadas todavia.');
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Practicantes',
                        data: values,
                        backgroundColor: colors.blue,
                        hoverBackgroundColor: colors.blueDark,
                        borderRadius: 6,
                        barThickness: 18
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        padding: 12
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.2)'
                        },
                        ticks: {
                            color: colors.muted,
                            precision: 0
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: colors.ink,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    }

    setupSidebar();

    if (typeof Chart === 'undefined') {
        replaceWithEmptyState('weeklyAttendanceChart', 'Chart.js no esta disponible para renderizar el grafico.');
        replaceWithEmptyState('stateDistributionChart', 'Chart.js no esta disponible para renderizar el grafico.');
        replaceWithEmptyState('areaDistributionChart', 'Chart.js no esta disponible para renderizar el grafico.');
        return;
    }

    Chart.defaults.font.family = 'Arial, Helvetica, sans-serif';
    Chart.defaults.color = colors.muted;

    buildLineChart();
    buildDoughnutChart();
    buildBarChart();
}());
