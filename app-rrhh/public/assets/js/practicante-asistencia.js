(function () {
    'use strict';

    function setupSidebar() {
        var toggle = document.querySelector('[data-practitioner-sidebar-toggle]');
        var backdrop = document.querySelector('[data-practitioner-sidebar-backdrop]');
        var mobileQuery = window.matchMedia('(max-width: 1180px)');
        var stored = window.localStorage ? window.localStorage.getItem('practitionerSidebarCollapsed') : null;

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
                window.localStorage.setItem('practitionerSidebarCollapsed', collapsed ? '1' : '0');
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

        document.querySelectorAll('.practitioner-sidebar a').forEach(function (link) {
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

    function setupClock() {
        var target = document.querySelector('[data-current-time]');

        if (!target) {
            return;
        }

        function update() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            target.textContent = hours + ':' + minutes;
        }

        update();
        window.setInterval(update, 30000);
    }

    setupSidebar();
    setupClock();
}());
