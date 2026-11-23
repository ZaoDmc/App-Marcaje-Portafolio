// Agregué un recorrido de formularios reales sobre una copia aislada y usuarios example.test.
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const net = require('node:net');
const { spawn, execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'php';
const fixtureFile = process.env.E2E_FIXTURE;
assert.ok(fixtureFile, 'Indicar E2E_FIXTURE de una copia nueva preparada con e2e-setup.php y notas-e2e-setup.php');
const f = JSON.parse(fs.readFileSync(fixtureFile, 'utf8'));
assert.match(f.database, /^rrhh_e2e_[a-f0-9]{12}$/);
assert.ok(path.resolve(f.directory).startsWith(path.join(root, 'scratch', 'phase8') + path.sep));
assert.equal(path.resolve(f.backend), path.join(path.resolve(f.directory), 'backend-rrhh'));
assert.equal(path.resolve(f.frontend), path.join(path.resolve(f.directory), 'public_html', 'portal'));
assert.ok(f.notas);
let browser, server, base, log;
const errores = [];
const state = operation => JSON.parse(execFileSync(php, [path.join(__dirname, 'notas-e2e-state.php'), fixtureFile, operation || 'state'], { cwd: root, encoding: 'utf8' }));
before(async () => {
    const portServer = net.createServer();
    await new Promise(resolve => portServer.listen(0, '127.0.0.1', resolve));
    const port = portServer.address().port;
    await new Promise(resolve => portServer.close(resolve));
    base = 'http://127.0.0.1:' + port;
    log = fs.createWriteStream(path.join(f.directory, 'notas-server.log'));
    server = spawn(php, ['-d', 'error_log=' + path.join(f.directory, 'notas-php-errors.log'), '-S', '127.0.0.1:' + port, '-t', f.frontend, path.join(__dirname, 'e2e-router.php')], {
        cwd: root,
        env: { ...process.env, E2E_FRONTEND: f.frontend, BACKEND_PATH: f.backend,
            APP_ENV: 'production', APP_DEBUG: 'false', BASE_PATH: '', APP_BASE_PATH: '', BASE_URL: base, APP_URL: base,
            DB_HOST: f.db_host, DB_PORT: f.db_port, DB_NAME: f.database, DB_USER: f.db_user, DB_PASS: f.db_pass },
        stdio: ['ignore', 'pipe', 'pipe']
    });
    server.stdout.pipe(log); server.stderr.pipe(log);
    for (let i = 0; i < 50; i++) {
        try { if ((await fetch(base + '/login.php')).status === 200) break; } catch {}
        if (i === 49) throw new Error('Servidor ficticio no disponible');
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    browser = await chromium.launch({ channel: process.env.UI_CHANNEL || 'msedge', headless: true });
});
after(async () => {
    if (browser) await browser.close();
    if (server && server.exitCode === null) { server.kill(); await new Promise(resolve => server.once('exit', resolve)); }
    if (log) log.end();
});
async function login(role) {
    const context = await browser.newContext();
    const page = await context.newPage();
    page.on('pageerror', error => errores.push(error.message));
    await page.goto(base + '/login.php');
    await page.locator('[name=email]').fill(role + '@example.test');
    await page.locator('[name=password]').fill(f.password);
    await Promise.all([page.waitForURL('**/' + role + '/**'), page.locator('button[type=submit]').click()]);
    return { context, page };
}
async function navegarSubmit(page, button) {
    await Promise.all([page.waitForNavigation(), button.click()]);
}
test('notas: rúbricas variables, viernes atrasados, borradores privados y temas', { timeout: 120000 }, async () => {
    const admin = await login('admin');
    const lider = await login('practicante');
    const supervisor = await login('supervisor');
    try {
        assert.equal((await supervisor.page.goto(base + '/admin/notas-rubrica.php')).status(), 403);
        assert.equal((await supervisor.page.goto(base + '/practicante/notas.php')).status(), 403);
        await admin.page.goto(base + '/admin/notas-rubrica.php');
        assert.equal(await admin.page.locator('[data-criterio-fila]').count(), 10);
        const antes = state();
        await admin.context.request.post(base + '/admin/notas-rubrica.php', { form: { csrf_token: 'invalid', accion: 'guardar', revision: '1', descripcion: 'Fraude', motivo: 'No guardar', 'criterios[]': 'No guardar' } });
        assert.deepEqual(state(), antes, 'CSRF inválido no crea versiones');
        while (await admin.page.locator('[data-criterio-fila]').count() > 2) await admin.page.locator('[data-quitar-criterio]').last().click();
        const criterioSeguro = 'Comunicación <script>window.__notaXss=1</script>';
        await admin.page.locator('[name="criterios[]"]').nth(0).fill(criterioSeguro);
        await admin.page.locator('[name="criterios[]"]').nth(1).fill('Responsabilidad');
        await admin.page.locator('[data-agregar-criterio]').click();
        await admin.page.locator('[name="criterios[]"]').nth(2).fill('Trabajo en equipo');
        await admin.page.locator('[data-quitar-criterio]').nth(1).click();
        await admin.page.locator('[name=descripcion]').fill('Rúbrica E2E de dos criterios');
        await admin.page.locator('[name=motivo]').fill('Configuración ficticia');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Guardar nueva versión', exact: true }));
        assert.match(await admin.page.locator('[role=status]').first().innerText(), /Nueva versión/);
        assert.equal(state().versiones, 2);
        await lider.page.goto(base + '/practicante/notas.php');
        assert.equal(await lider.page.locator('nav[aria-label=Secciones]').getByRole('link', { name: 'Notas', exact: true }).count(), 1);
        const options = await lider.page.locator('#notas-practica option').evaluateAll(items => items.map(item => item.value));
        assert.ok(!options.includes(String(f.notas.ajeno)) && !options.includes(String(f.notas.lider)));
        assert.equal((await lider.page.goto(base + '/practicante/notas.php?id_practica=' + f.notas.ajeno)).status(), 403);
        const ruta = '/practicante/notas.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.atrasado;
        await lider.page.goto(base + ruta);
        assert.equal(await lider.page.locator('[name^="notas["]').count(), 2);
        assert.ok((await lider.page.locator('label[for=nota-1]').innerText()).includes(criterioSeguro));
        assert.equal(await lider.page.evaluate(() => window.__notaXss), undefined);
        await lider.page.locator('#nota-1').selectOption('6.5'); await lider.page.locator('#nota-2').selectOption('7.0');
        await lider.page.locator('#nota-final').fill('6.75');
        await lider.page.locator('#nota-observacion').fill('<script>window.__notaXss=2</script>');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        assert.match(await lider.page.locator('[role=status]').innerText(), /Borrador guardado/);
        assert.equal(state().notas[0].viernes, f.notas.atrasado);
        assert.equal(state().notas[0].estado, 'BORRADOR');
        assert.equal(state().notas[0].nota_final, '6.75');
        assert.equal(await lider.page.evaluate(() => window.__notaXss), undefined);
        // Cambié la rúbrica activa y comprobé que el borrador anterior mantenga su versión de dos filas.
        await admin.page.locator('[data-agregar-criterio]').click();
        await admin.page.locator('[name="criterios[]"]').nth(2).fill('Puntualidad');
        await admin.page.locator('[name=motivo]').fill('Otra versión ficticia');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Guardar nueva versión', exact: true }));
        await lider.page.reload(); assert.equal(await lider.page.locator('[name^="notas["]').count(), 2);
        const stale = await lider.context.newPage(); await stale.goto(base + ruta);
        await lider.page.locator('#nota-motivo').fill('Corrección del borrador');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        await stale.locator('#nota-motivo').fill('Formulario obsoleto');
        await navegarSubmit(stale, stale.getByRole('button', { name: 'Guardar borrador', exact: true }));
        assert.match(await stale.locator('[role=alert]').innerText(), /cambió/);
        assert.equal(Number(state().notas[0].revision), 2); await stale.close();
        // ADMIN corrige un borrador de integrante y el motivo queda exigido en pantalla.
        await admin.page.goto(base + '/admin/notas-evaluaciones.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.atrasado);
        // La publicación sin cambios admite motivo vacío; el servidor exige motivo al editar un borrador.
        await admin.page.locator('#nota-final').fill('7'); await admin.page.locator('#nota-motivo').fill('Corrección administrativa ficticia');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        assert.equal(state().notas[0].nota_final, '7.00');
        // Una semana nueva usa tres criterios y no reemplaza la evaluación atrasada.
        const nueva = '/practicante/notas.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.viernes;
        await lider.page.goto(base + nueva); assert.equal(await lider.page.locator('[name^="notas["]').count(), 3);
        const antesCsrf = state();
        await lider.context.request.post(base + '/practicante/notas.php', { form: { id_practica: String(f.notas.integrante), viernes: f.notas.viernes, csrf_token: 'invalid', accion: 'guardar' } });
        assert.deepEqual(state(), antesCsrf, 'CSRF inválido no registra notas');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        assert.equal(state().notas.length, 2);
        for (const tema of ['light', 'dark', 'synthwave', 'aqua', 'coffee', 'black']) {
            await lider.page.evaluate(tema => localStorage.setItem('rrhh-practicante-theme', tema), tema);
            for (const ancho of [320, 1280]) {
                await lider.page.setViewportSize({ width: ancho, height: 900 }); await lider.page.goto(base + nueva);
                assert.equal(await lider.page.locator('html').getAttribute('data-theme'), tema);
                assert.ok(await lider.page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1), 'Sin desborde: ' + tema + '/' + ancho);
                assert.ok(await lider.page.locator('.rrhh-notas .rrhh-label').first().evaluate(el => getComputedStyle(el).color === getComputedStyle(document.body).color), 'Etiquetas legibles según tema: ' + tema);
                if (tema !== 'light') assert.notEqual(await lider.page.locator('#nota-1').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(255, 255, 255)');
                await lider.page.screenshot({ path: path.join(f.directory, 'notas-' + tema + '-' + ancho + '.png'), fullPage: true });
            }
        }
        // La alternativa sin JS añade/quita filas por POST sin guardar una rúbrica incompleta.
        const sinJs = await browser.newContext({ javaScriptEnabled: false, storageState: await admin.context.storageState() });
        const noJs = await sinJs.newPage(); await noJs.goto(base + '/admin/notas-rubrica.php');
        const configAntes = state().config;
        await navegarSubmit(noJs, noJs.getByRole('button', { name: 'Agregar criterio', exact: true }));
        assert.equal(await noJs.locator('[data-criterio-fila]').count(), 4);
        await navegarSubmit(noJs, noJs.getByRole('button', { name: 'Quitar criterio', exact: true }).last());
        assert.equal(await noJs.locator('[data-criterio-fila]').count(), 3);
        assert.deepEqual(state().config, configAntes); await sinJs.close();
        await admin.page.evaluate(() => localStorage.setItem('rrhh-personal-theme', 'coffee'));
        for (const ancho of [320, 1280]) {
            await admin.page.setViewportSize({ width: ancho, height: 900 }); await admin.page.goto(base + '/admin/notas-rubrica.php');
            assert.ok(await admin.page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1));
            assert.ok(await admin.page.locator('.rrhh-notas .rrhh-label').first().evaluate(el => getComputedStyle(el).color === getComputedStyle(document.body).color));
            await admin.page.screenshot({ path: path.join(f.directory, 'rubrica-coffee-' + ancho + '.png'), fullPage: true });
        }
        state('replace');
        assert.equal((await lider.page.goto(base + nueva)).status(), 403);
        await lider.page.goto(base + '/practicante/dashboard.php');
        // Retirar liderazgo oculta la evaluación del área, pero conserva el módulo de consulta propia.
        assert.equal(await lider.page.locator('nav[aria-label=Secciones]').getByRole('link', { name: 'Notas', exact: true }).count(), 1);
        assert.equal(await lider.page.getByRole('link', { name: 'Evaluar mi área', exact: true }).count(), 0);
        await lider.page.locator('nav[aria-label=Secciones]').getByRole('link', { name: 'Notas', exact: true }).click();
        assert.equal(await lider.page.locator('nav[aria-label="Secciones de Notas"]').getByRole('link', { name: 'Mis notas', exact: true }).count(), 1);
        assert.deepEqual(errores, []);
        fs.writeFileSync(path.join(f.directory, 'notas-results.json'), JSON.stringify({ aprobado: true, escenarios: ['criterios variables', 'CSRF', 'áreas ajenas', 'viernes atrasado', 'versiones conservadas', 'revisión obsoleta', 'edición ADMIN', 'seis temas', 'sin JS', 'retiro de liderazgo'], state: state() }, null, 2));
    } finally { await admin.context.close(); await lider.context.close(); await supervisor.context.close(); }
});
