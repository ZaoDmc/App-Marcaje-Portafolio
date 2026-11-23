/* Agregué interacciones accesibles sin Bootstrap: diálogos, menú móvil,
 * opciones de cuenta, secciones desplegables y cierre de alertas. */
(function () {
    'use strict';

    function initUI(doc, win) {
        // Evité registrar dos veces los eventos si un módulo vuelve a iniciar el layout.
        if (doc.documentElement.dataset.rrhhUiReady) return;
        doc.documentElement.dataset.rrhhUiReady = 'true';
        const openers = new WeakMap();
        const dialogs = Array.from(doc.querySelectorAll('dialog[data-ui-dialog]'));
        const focusable = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]';

        // Oculté la alternativa HTML de un POST pendiente al cargar la ventana nativa;
        // sus botones permanecen disponibles cuando JavaScript está desactivado.
        if (typeof win.confirm === 'function') {
            doc.querySelectorAll('[data-marcaje-fallback]').forEach(function (section) { section.hidden = true; });
        }

        // Agregué la ventana confirm del navegador; cancelar nunca envía el formulario.
        function markField(form, name, value) {
            let input = form.querySelector('input[type="hidden"][name="' + name + '"]');
            if (!input) { input = doc.createElement('input'); input.type = 'hidden'; input.name = name; form.appendChild(input); }
            input.value = value;
        }
        function lockMark(form) {
            form.dataset.marcajeEnviando = '1';
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.dataset.marcajeDisabled = String(button.disabled);
                button.disabled = true;
            });
        }
        doc.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form.matches('form[data-confirm-marcaje], form[data-marcaje-confirmado]')) return;
            if (form.dataset.marcajeEnviando === '1') { event.preventDefault(); return; }
            // La alternativa del servidor ya incluye la marca y su confirmación en campos ocultos.
            if (form.hasAttribute('data-marcaje-confirmado')) { lockMark(form); return; }
            const button = event.submitter;
            if (typeof win.confirm !== 'function') return; // PHP solicita confirmación si no está disponible la ventana nativa.
            event.preventDefault();
            if (!button || button.disabled || button.name !== 'codigo_marca') return;
            if (!win.confirm('¿Confirmas registrar ' + button.dataset.marcaLabel + '?\nSe utilizará la hora del servidor al confirmar.')) {
                button.focus();
                return;
            }
            // Copié la marca antes de deshabilitar botones: un submitter deshabilitado no se envía.
            markField(form, 'codigo_marca', button.value);
            markField(form, 'confirmar_marca', '1');
            lockMark(form);
            const status = form.querySelector('[data-marcaje-status]');
            if (status) { status.hidden = false; status.textContent = 'Registrando marca…'; }
            win.HTMLFormElement.prototype.submit.call(form);
        });
        // Restauré controles al volver con Atrás desde la caché del navegador; el backend revalida CSRF y secuencia.
        win.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            doc.querySelectorAll('form[data-marcaje-enviando]').forEach(function (form) {
                delete form.dataset.marcajeEnviando;
                form.removeAttribute('aria-busy');
                form.querySelectorAll('[data-marcaje-disabled]').forEach(function (button) { button.disabled = button.dataset.marcajeDisabled === 'true'; delete button.dataset.marcajeDisabled; });
                const status = form.querySelector('[data-marcaje-status]');
                if (status) { status.hidden = true; status.textContent = ''; }
            });
            doc.querySelectorAll('form[data-confirm-marcaje] input[type="hidden"][name="codigo_marca"], form[data-confirm-marcaje] input[type="hidden"][name="confirmar_marca"]').forEach(function (input) { input.remove(); });
        });

        // Máscara de hora chilena 24 h: solo dígitos, ":" automático y se descarta cada dígito que formaría
        // una hora imposible (horas 00–23, minutos 00–59). "9" → "09", "2" + "5" → se ignora el 5.
        function mascaraHora24(valor) {
            let d = '';
            for (const c of valor.replace(/\D/g, '')) {
                if (d.length === 0 && c > '2') d = '0';
                if (d === '2' && c > '3') continue;
                if (d.length === 2 && c > '5') continue;
                if (d.length < 4) d += c;
            }
            return d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
        }
        // Al salir del campo se completa lo faltante: "14" → "14:00", "14:3" → "14:30", "1" → "01:00".
        function completarHora24(valor) {
            const d = mascaraHora24(valor).replace(':', '');
            if (d === '') return '';
            return (d.length === 1 ? '0' + d : d.slice(0, 2)) + ':' + (d.slice(2) + '00').slice(0, 2);
        }
        doc.addEventListener('input', function (event) {
            const campo = event.target;
            if (!campo.matches || !campo.matches('input[data-hora24]')) return;
            const limpio = mascaraHora24(campo.value);
            if (limpio !== campo.value) campo.value = limpio;
        });
        // En captura, para que otros scripts que escuchan "change" ya lean la hora completa.
        doc.addEventListener('change', function (event) {
            if (event.target.matches && event.target.matches('input[data-hora24]')) event.target.value = completarHora24(event.target.value);
        }, true);

        // El dialog nativo proporciona capa superior, foco contenido y Escape.
        dialogs.forEach(function (dialog) {
            dialog.addEventListener('close', function () {
                if (!dialogs.some(function (item) { return item.open; })) {
                    doc.body.classList.remove('rrhh-dialog-open');
                }
                const opener = openers.get(dialog);
                if (opener && opener.isConnected) opener.focus();
            });
            dialog.addEventListener('click', function (event) {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
                    dialog.close();
                }
            });
        });

        // Auto-abrí el diálogo marcado por el servidor para conservar el formulario en pantalla tras envíos.
        const autoOpen = doc.querySelector('dialog[data-ui-dialog][data-ui-auto-open]');
        if (autoOpen && !autoOpen.open && typeof autoOpen.showModal === 'function') {
            autoOpen.showModal();
            doc.body.classList.add('rrhh-dialog-open');
        }

        const drawer = doc.querySelector('[data-ui-drawer]');
        const drawerButton = doc.querySelector('[data-ui-drawer-toggle]');
        const backdrop = doc.querySelector('[data-ui-drawer-backdrop]');
        const main = doc.querySelector('[data-ui-main]');
        const desktop = win.matchMedia('(min-width: 1024px)');
        let drawerOpen = false;

        function setDrawer(open, restoreFocus) {
            if (!drawer || !drawerButton || !backdrop) return;
            drawerOpen = open && !desktop.matches;
            drawer.classList.toggle('-translate-x-full', !drawerOpen);
            drawer.classList.toggle('translate-x-0', drawerOpen);
            drawer.setAttribute('aria-hidden', String(!desktop.matches && !drawerOpen));
            drawer.inert = !desktop.matches && !drawerOpen;
            drawerButton.setAttribute('aria-expanded', String(drawerOpen));
            backdrop.hidden = !drawerOpen;
            if (main) main.inert = drawerOpen;
            doc.body.style.overflow = drawerOpen ? 'hidden' : '';
            if (drawerOpen) {
                drawer.setAttribute('role', 'dialog');
                drawer.setAttribute('aria-modal', 'true');
                const first = drawer.querySelector('[data-ui-drawer-close]') || drawer.querySelector(focusable);
                if (first) first.focus();
            } else {
                drawer.removeAttribute('role');
                drawer.removeAttribute('aria-modal');
                if (restoreFocus) drawerButton.focus();
            }
        }
        setDrawer(false, false);
        desktop.addEventListener('change', function () { setDrawer(false, false); });

        function closeDropdowns(except) {
            doc.querySelectorAll('[data-ui-dropdown]').forEach(function (trigger) {
                if (trigger === except) return;
                const menu = doc.getElementById(trigger.dataset.uiDropdown);
                if (menu) menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            });
        }

        doc.addEventListener('click', function (event) {
            const target = event.target instanceof win.Element ? event.target : null;
            if (!target) return;
            const opener = target.closest('[data-ui-open]');
            if (opener) {
                const dialog = doc.getElementById(opener.dataset.uiOpen);
                if (dialog && dialog.matches('dialog[data-ui-dialog]') && !dialog.open) {
                    openers.set(dialog, opener);
                    dialog.showModal();
                    doc.body.classList.add('rrhh-dialog-open');
                }
            }
            const close = target.closest('[data-ui-close]');
            if (close) {
                const dialog = close.closest('dialog[data-ui-dialog]');
                if (dialog) dialog.close();
            }
            if (target.closest('[data-ui-drawer-toggle]')) setDrawer(!drawerOpen, true);
            if (target.closest('[data-ui-drawer-close], [data-ui-drawer-backdrop]')) setDrawer(false, true);
            if (drawerOpen && target.closest('[data-ui-drawer] a[href]')) setDrawer(false, false);

            const dropdown = target.closest('[data-ui-dropdown]');
            if (dropdown) {
                const menu = doc.getElementById(dropdown.dataset.uiDropdown);
                closeDropdowns(dropdown);
                if (menu) {
                    menu.hidden = !menu.hidden;
                    dropdown.setAttribute('aria-expanded', String(!menu.hidden));
                }
            } else if (!target.closest('#profileMenu')) {
                closeDropdowns();
            }
            const collapse = target.closest('[data-ui-collapse]');
            if (collapse) {
                const panel = doc.getElementById(collapse.dataset.uiCollapse);
                if (panel) {
                    panel.hidden = !panel.hidden;
                    collapse.setAttribute('aria-expanded', String(!panel.hidden));
                }
            }
            const dismiss = target.closest('[data-ui-dismiss-alert]');
            if (dismiss) {
                const alert = dismiss.closest('[role="alert"]');
                if (alert) alert.remove();
            }
        });

        doc.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (drawerOpen) { setDrawer(false, true); event.preventDefault(); }
                doc.querySelectorAll('[data-ui-dropdown][aria-expanded="true"]').forEach(function (trigger) { trigger.focus(); });
                closeDropdowns();
            }
            // Contuve Tab dentro del menú móvil, mientras el contenido principal está inert.
            if (drawerOpen && event.key === 'Tab') {
                const items = Array.from(drawer.querySelectorAll(focusable)).filter(function (item) { return item.getClientRects().length > 0; });
                const first = items[0]; const last = items[items.length - 1];
                if (event.shiftKey && doc.activeElement === first) { last.focus(); event.preventDefault(); }
                else if (!event.shiftKey && doc.activeElement === last) { first.focus(); event.preventDefault(); }
            }
        });

        // Celebración con confeti ligero al alcanzar el 100% de las horas asignadas.
        function launchGoalConfetti(anchorEl) {
            if (!doc.body || typeof win.requestAnimationFrame !== 'function') return;
            if (typeof win.matchMedia === 'function' && win.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const canvas = doc.createElement('canvas');
            const ctx = canvas.getContext ? canvas.getContext('2d') : null;
            if (!ctx) return;

            const dpr = Math.min(win.devicePixelRatio || 1, 2);
            const width = win.innerWidth || doc.documentElement.clientWidth || 1024;
            const height = win.innerHeight || doc.documentElement.clientHeight || 768;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            canvas.setAttribute('aria-hidden', 'true');
            canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:9999;';
            doc.body.appendChild(canvas);
            ctx.scale(dpr, dpr);

            const rect = anchorEl && typeof anchorEl.getBoundingClientRect === 'function'
                ? anchorEl.getBoundingClientRect()
                : { left: width / 2, top: height / 3, width: 0, height: 0 };
            const originX = rect.left + rect.width / 2;
            const originY = rect.top + rect.height / 2;
            const colors = ['#10b981', '#34d399', '#2563eb', '#f59e0b', '#0ea5e9'];
            const particles = [];

            for (let i = 0; i < 42; i++) {
                const angle = (-Math.PI / 2) + (Math.random() - 0.5) * 1.9;
                const speed = 4 + Math.random() * 6.5;
                particles.push({
                    x: originX,
                    y: originY,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 1.5,
                    size: 5 + Math.random() * 4,
                    color: colors[i % colors.length],
                    rotation: Math.random() * Math.PI * 2,
                    vRot: (Math.random() - 0.5) * 0.24,
                    alpha: 1
                });
            }

            const start = win.performance && typeof win.performance.now === 'function' ? win.performance.now() : Date.now();
            const duration = 2500;

            function frame(now) {
                const elapsed = now - start;
                const progress = Math.min(1, elapsed / duration);
                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.15;
                    p.vx *= 0.99;
                    p.rotation += p.vRot;
                    p.alpha = progress > 0.75 ? Math.max(0, 1 - (progress - 0.75) / 0.25) : 1;

                    ctx.save();
                    ctx.globalAlpha = p.alpha;
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rotation);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.size / 2, -p.size / 3, p.size, p.size * 0.6);
                    ctx.restore();
                }

                if (progress < 1) {
                    win.requestAnimationFrame(frame);
                } else if (canvas.parentNode) {
                    canvas.parentNode.removeChild(canvas);
                }
            }

            win.requestAnimationFrame(frame);
        }

        const goalCard = doc.querySelector('[data-meta-cumplida="1"]');
        if (goalCard) {
            const ring = goalCard.querySelector('.practitioner-progress') || goalCard;
            const metaId = goalCard.getAttribute('data-meta-id') || '0';
            const storageKey = 'rrhh_meta_100_celebrada_' + metaId;
            let alreadyCelebrated = false;

            try {
                alreadyCelebrated = Boolean(win.sessionStorage && win.sessionStorage.getItem(storageKey) === '1');
                if (!alreadyCelebrated && win.sessionStorage) {
                    win.sessionStorage.setItem(storageKey, '1');
                }
            } catch (e) {
                alreadyCelebrated = false;
            }

            if (!alreadyCelebrated) {
                win.setTimeout(function () { launchGoalConfetti(ring); }, 600);
            }

            const replayBtn = goalCard.querySelector('[data-confetti-replay]');
            if (replayBtn) {
                const maxReplays = 5;
                const cooldownMs = 120000;
                let replayCount = 0;
                let unlockTimer = null;

                replayBtn.addEventListener('click', function () {
                    if (replayBtn.disabled || replayCount >= maxReplays) return;
                    replayCount += 1;
                    launchGoalConfetti(ring);

                    if (replayCount >= maxReplays) {
                        replayBtn.disabled = true;
                        replayBtn.setAttribute('aria-disabled', 'true');
                        replayBtn.setAttribute('title', 'Límite de celebraciones alcanzado. Espera 2 minutos o recarga la página.');
                        if (unlockTimer) win.clearTimeout(unlockTimer);
                        unlockTimer = win.setTimeout(function () {
                            replayCount = 0;
                            replayBtn.disabled = false;
                            replayBtn.removeAttribute('aria-disabled');
                            replayBtn.setAttribute('title', 'Volver a celebrar');
                        }, cooldownMs);
                    }
                });
            }
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { initUI: initUI };
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { initUI(document, window); });
        else initUI(document, window);
    }
}());
