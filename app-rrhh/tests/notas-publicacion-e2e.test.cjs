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
    const rutaRol = ['admin', 'supervisor'].includes(role) ? role : 'practicante';
    await Promise.all([page.waitForURL('**/' + rutaRol + '/**'), page.locator('button[type=submit]').click()]);
    return { context, page };
}
async function navegarSubmit(page, button) {
    await Promise.all([page.waitForNavigation(), button.click()]);
}

// Comprobé las transiciones con formularios reales; ninguna aprobación se simula con SQL directo.
test('notas: publicación confirmada, consulta propia, correcciones y resolución ADMIN', { timeout: 120000 }, async () => {
    const admin = await login('admin'); const lider = await login('practicante');
    const integrante = await login('integrante'); const ajeno = await login('ajeno');
    const supervisor = await login('supervisor');
    let reemplazo;
    const ruta = '/practicante/notas.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.viernes;
    const notaActual = () => state().notas.find(n => Number(n.id_practica) === Number(f.notas.integrante) && n.viernes === f.notas.viernes);
    async function confirmar(page, correccion = false) {
        await page.locator('[name=confirmacion]').check();
        await navegarSubmit(page, page.getByRole('button', { name: correccion ? 'Confirmar corrección' : 'Confirmar publicación', exact: true }));
    }
    // Contrasté la misma nota en tres pantallas reales sin abrir edición ni revelar contenido privado.
    async function comprobarIntegracion(esperada) {
        for (const [acceso, prefijo] of [[supervisor, 'supervisor'], [admin, 'admin']]) {
            for (const rutaConsulta of [
                '/' + prefijo + '/reportes.php?id_practica=' + f.notas.integrante + '&desde=2020-01-01&hasta=2020-01-02',
                '/' + prefijo + '/practicantes.php?id_practicante=' + f.notas.integrante,
                '/supervisor/acom.php?vista=acompanamiento'
            ]) {
                assert.equal((await acceso.page.goto(base + rutaConsulta)).status(), 200);
                const resumenes = acceso.page.locator('[data-notas-resumen]');
                assert.ok(await resumenes.count() > 0, rutaConsulta);
                assert.match(await resumenes.allTextContents().then(textos => textos.join(' ')), new RegExp(esperada));
                assert.doesNotMatch(await acceso.page.content(), /OBSERVACIÓN DE BORRADOR PRIVADA|MOTIVO INTERNO DE CORRECCIÓN/);
                assert.equal(await acceso.page.locator('input[name^="evaluacion["]').count(), 0);
            }
        }
    }
    try {
        // Comprobé el módulo común aun sin publicaciones; la evaluación se ofrece solo a quien ya lidera el área.
        for (const [acceso, esLider] of [[lider,true],[integrante,false],[ajeno,false]]) {
            await acceso.page.goto(base + '/practicante/dashboard.php');
            assert.equal(await acceso.page.locator('nav[aria-label=Secciones]').getByRole('link', { name:'Notas',exact:true }).count(),1);
            assert.equal(await acceso.page.getByRole('link', { name:'Evaluar mi área',exact:true }).count(),esLider?1:0);
            await acceso.page.locator('nav[aria-label=Secciones]').getByRole('link', { name:'Notas',exact:true }).click();
            const pestanas=acceso.page.locator('nav[aria-label="Secciones de Notas"]');
            assert.equal(await pestanas.getByRole('link', { name:'Mis notas',exact:true }).count(),1);
            assert.equal(await pestanas.getByRole('link', { name:'Evaluar mi área',exact:true }).count(),esLider?1:0);
        }
        assert.equal((await integrante.page.goto(base+'/practicante/notas.php')).status(),403);
        assert.equal((await supervisor.page.goto(base + '/practicante/mis-notas.php')).status(), 403);
        // Verifiqué que ADMIN elija líderes para la carga habitual, sin listar integrantes ajenos a esa responsabilidad.
        await admin.page.goto(base + '/admin/notas-evaluaciones.php');
        const candidatosAdmin=await admin.page.locator('#notas-practica option').evaluateAll(items=>items.map(item=>item.value));
        assert.ok(candidatosAdmin.includes(String(f.notas.lider)));
        assert.ok(!candidatosAdmin.includes(String(f.notas.integrante)) && !candidatosAdmin.includes(String(f.notas.ajeno)));
        await admin.page.goto(base + '/admin/notas-rubrica.php');
        while (await admin.page.locator('[data-criterio-fila]').count() > 2) await admin.page.locator('[data-quitar-criterio]').last().click();
        await admin.page.locator('[name="criterios[]"]').nth(0).fill('Comunicación');
        await admin.page.locator('[name="criterios[]"]').nth(1).fill('Responsabilidad');
        await admin.page.locator('[name=descripcion]').fill('Rúbrica de publicación E2E');
        await admin.page.locator('[name=motivo]').fill('Dos criterios ficticios');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Guardar nueva versión', exact: true }));
        await lider.page.goto(base + ruta);
        await lider.page.locator('#nota-1').selectOption('6.5');
        await lider.page.locator('#nota-final').fill('6.75');
        await lider.page.locator('#nota-observacion').fill('OBSERVACIÓN DE BORRADOR PRIVADA');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        const idNota = Number(notaActual().id_nota);
        await integrante.page.goto(base + '/practicante/mis-notas.php');
        assert.match(await integrante.page.locator('main').innerText(), /Todavía no tienes evaluaciones publicadas/);
        assert.equal((await integrante.page.goto(base + '/practicante/mis-notas.php?id_nota=' + idNota)).status(), 403);
        assert.doesNotMatch(await integrante.page.content(), /OBSERVACIÓN DE BORRADOR PRIVADA/);
        await lider.page.locator('#nota-motivo').fill('Preparación de publicación');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Revisar y publicar', exact: true }));
        assert.match(await lider.page.locator('[role=alert]').innerText(), /Completa todos/);
        assert.equal(notaActual().estado, 'BORRADOR');
        await lider.page.locator('#nota-2').selectOption('7.0');
        await lider.page.locator('#nota-observacion').fill('OBSERVACIÓN PUBLICADA <script>window.__notaXss=1</script>');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Revisar y publicar', exact: true }));
        assert.equal(await lider.page.locator('[data-notas-confirmacion]').count(), 1);
        assert.equal(notaActual().estado, 'BORRADOR');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Volver a editar', exact: true }));
        assert.equal(await lider.page.locator('#nota-2').inputValue(), '7.0');
        assert.equal(notaActual().estado, 'BORRADOR');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Revisar y publicar', exact: true }));
        const formDatos = await lider.page.locator('[data-notas-confirmacion] form').evaluate(form => Object.fromEntries(new FormData(form)));
        formDatos.accion = 'confirmar_publicacion';
        const estadoAntes = state();
        await lider.context.request.post(base + '/practicante/notas.php', { form: { ...formDatos, csrf_token: 'invalid', confirmacion: '1' } });
        assert.deepEqual(state(), estadoAntes, 'CSRF bloquea publicación');
        await lider.context.request.post(base + '/practicante/notas.php', { form: formDatos });
        assert.deepEqual(state(), estadoAntes, 'No se publica sin confirmación explícita');
        await admin.page.goto(base + '/admin/notas-evaluaciones.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.viernes);
        assert.equal(await admin.page.getByRole('button', { name: 'Revisar y publicar', exact: true }).count(), 0, 'La aprobación normal corresponde al líder');
        await confirmar(lider.page);
        assert.equal(notaActual().estado, 'APROBADA'); assert.equal(notaActual().nota_final, '6.75');
        assert.ok(notaActual().aprobado_en);
        await comprobarIntegracion('6,75');
        const despues = state();
        await lider.context.request.post(base + '/practicante/notas.php', { form: { ...formDatos, confirmacion: '1' } });
        assert.deepEqual(state(), despues, 'Repetir confirmación no duplica revisión');
        const respuestaPropia = await integrante.page.goto(base + '/practicante/mis-notas.php?id_nota=' + idNota);
        assert.match(respuestaPropia.headers()['cache-control'], /no-store/);
        assert.match(await integrante.page.locator('[data-nota-final-publicada]').innerText(), /6,75/);
        assert.equal(await integrante.page.locator('[data-notas-revisiones]').count(), 0);
        assert.doesNotMatch(await integrante.page.content(), /Preparación de publicación/);
        assert.equal(await integrante.page.evaluate(() => window.__notaXss), undefined);
        assert.equal((await ajeno.page.goto(base + '/practicante/mis-notas.php?id_nota=' + idNota)).status(), 403);
        assert.equal((await integrante.context.request.post(base + '/practicante/mis-notas.php', { form: {} })).status(), 405);
        // La revisión previa de una corrección mantiene visible la versión publicada anterior.
        await lider.page.goto(base + ruta + '&corregir=1');
        await lider.page.locator('#nota-1').selectOption('5.5'); await lider.page.locator('#nota-final').fill('5.75');
        await lider.page.locator('#nota-motivo').fill('MOTIVO INTERNO DE CORRECCIÓN');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Revisar corrección', exact: true }));
        await integrante.page.reload(); assert.match(await integrante.page.locator('[data-nota-final-publicada]').innerText(), /6,75/);
        await confirmar(lider.page, true);
        await integrante.page.reload(); assert.match(await integrante.page.locator('[data-nota-final-publicada]').innerText(), /5,75/);
        assert.doesNotMatch(await integrante.page.content(), /MOTIVO INTERNO DE CORRECCIÓN/);
        // ADMIN corrige una nota publicada de integrante, sin convertirlo en aprobación obligatoria normal.
        await admin.page.goto(base + '/admin/notas-evaluaciones.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.viernes + '&corregir=1');
        // Conservé la corrección global por enlace, sin insertar al integrante en el selector de líderes ni seleccionar otro líder por error.
        assert.equal(await admin.page.locator('#notas-practica').inputValue(),'');
        assert.equal(await admin.page.locator('#notas-practica option[value="'+f.notas.integrante+'"]').count(),0);
        await admin.page.locator('#nota-final').fill('6.25'); await admin.page.locator('#nota-motivo').fill('Corrección global ADMIN');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Revisar corrección', exact: true })); await confirmar(admin.page, true);
        await integrante.page.reload(); assert.match(await integrante.page.locator('[data-nota-final-publicada]').innerText(), /6,25/);
        assert.ok(await admin.page.locator('[data-notas-revisiones]').innerText());
        await comprobarIntegracion('6,25');
        // ADMIN aprueba al líder; el propio líder no se califica a sí mismo.
        await admin.page.goto(base + '/admin/notas-evaluaciones.php?id_practica=' + f.notas.lider + '&viernes=' + f.notas.viernes);
        await admin.page.locator('#nota-1').selectOption('6.5'); await admin.page.locator('#nota-2').selectOption('7.0');
        await admin.page.locator('#nota-final').fill('7');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Revisar y publicar', exact: true })); await confirmar(admin.page);
        await lider.page.goto(base + '/practicante/mis-notas.php'); assert.match(await lider.page.locator('[data-nota-final-publicada]').innerText(), /7,00/);
        // Un borrador del líder anterior no se transfiere al nuevo; ADMIN lo resuelve con motivo.
        const atrasada = '/practicante/notas.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.atrasado;
        await lider.page.goto(base + atrasada); await lider.page.locator('#nota-1').selectOption('6.5'); await lider.page.locator('#nota-2').selectOption('7.0'); await lider.page.locator('#nota-final').fill('6.75');
        await navegarSubmit(lider.page, lider.page.getByRole('button', { name: 'Guardar borrador', exact: true }));
        // ADMIN designa desde el formulario real; la sesión del nuevo líder obtiene la acción al recargar, sin cambiar de rol.
        reemplazo = await login('reemplazo');
        await reemplazo.page.goto(base+'/practicante/dashboard.php');
        assert.equal(await reemplazo.page.getByRole('link', {name:'Evaluar mi área',exact:true}).count(),0);
        await admin.page.goto(base+'/admin/notas.php?id_area='+f.notas.area);
        await admin.page.locator('[name=id_practica]').selectOption(String(f.notas.reemplazo));
        await admin.page.locator('[name=motivo]').fill('Designación real de prueba por ADMIN');
        await navegarSubmit(admin.page,admin.page.locator('[name=accion][value=designar]'));
        await reemplazo.page.reload();
        assert.equal(await reemplazo.page.getByRole('link', {name:'Evaluar mi área',exact:true}).count(),1);
        await reemplazo.page.getByRole('link', {name:'Evaluar mi área',exact:true}).click();
        assert.equal(await reemplazo.page.locator('nav[aria-label="Secciones de Notas"]').getByRole('link', {name:'Mis notas',exact:true}).count(),1);
        await reemplazo.page.goto(base + atrasada);
        assert.equal(await reemplazo.page.locator('[data-notas-borrador]').count(), 0);
        assert.match(await reemplazo.page.locator('main').innerText(), /otro responsable/);
        await admin.page.goto(base + '/admin/notas-evaluaciones.php?id_practica=' + f.notas.integrante + '&viernes=' + f.notas.atrasado);
        await admin.page.locator('#nota-motivo').fill('Resolución por reemplazo de líder');
        await navegarSubmit(admin.page, admin.page.getByRole('button', { name: 'Revisar y publicar', exact: true }));
        assert.match(await admin.page.locator('[data-notas-confirmacion]').innerText(), /Resolución excepcional/);
        await confirmar(admin.page);
        assert.equal(state().notas.find(n => n.viernes === f.notas.atrasado).estado, 'APROBADA');
        assert.equal((await lider.page.goto(base + ruta)).status(), 403);
        await lider.page.goto(base + '/practicante/mis-notas.php'); assert.equal(await lider.page.locator('[data-nota-final-publicada]').count(), 1);
        // Consultas privadas y confirmación legibles en todos los temas, móvil y escritorio.
        for (const tema of ['light', 'dark', 'synthwave', 'aqua', 'coffee', 'black']) {
            await integrante.page.evaluate(tema => localStorage.setItem('rrhh-practicante-theme', tema), tema);
            for (const ancho of [320, 1280]) {
                await integrante.page.setViewportSize({ width: ancho, height: 900 }); await integrante.page.goto(base + '/practicante/mis-notas.php?id_nota=' + idNota);
                assert.ok(await integrante.page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1));
                await integrante.page.screenshot({ path: path.join(f.directory, 'mis-notas-' + tema + '-' + ancho + '.png'), fullPage: true });
            }
        }
        assert.deepEqual(errores, []);
        fs.writeFileSync(path.join(f.directory, 'publicacion-results.json'), JSON.stringify({ aprobado: true, escenarios: ['completitud', 'confirmación', 'privacidad', 'CSRF', 'repetición', 'corrección', 'aprobación de líder', 'resolución excepcional', 'integración Acompañamiento/ficha/reportes ADMIN y SUPERVISOR', 'seis temas'], state: state() }, null, 2));
    } catch (error) {
        // Conservé diagnóstico ficticio de fallos para localizar validaciones sin tocar datos del usuario.
        for (const [nombre, acceso] of Object.entries({ admin, lider, integrante, ajeno })) {
            if (!acceso.page.isClosed()) {
                fs.writeFileSync(path.join(f.directory, 'fallo-publicacion-' + nombre + '.html'), await acceso.page.content());
                await acceso.page.screenshot({ path: path.join(f.directory, 'fallo-publicacion-' + nombre + '.png'), fullPage: true });
            }
        }
        throw error;
    } finally {
        await admin.context.close(); await lider.context.close(); await integrante.context.close(); await ajeno.context.close(); await supervisor.context.close();
        if (reemplazo) await reemplazo.context.close();
    }
});

// Verifiqué la preferencia específica del personal, que utiliza una clave distinta de la del practicante.
test('notas: temas del resumen académico de supervisión', async () => {
    const acceso = await login('supervisor');
    // Revisé también la pestaña propia que ahora aparece para todos los practicantes.
    const propio = await login('integrante');
    try {
        for (const tema of ['light', 'dark', 'synthwave', 'aqua', 'coffee', 'black']) {
            await acceso.page.evaluate(tema => localStorage.setItem('rrhh-personal-theme', tema), tema);
            await propio.page.evaluate(tema => localStorage.setItem('rrhh-practicante-theme', tema), tema);
            for (const ancho of [320, 1280]) {
                await acceso.page.setViewportSize({ width: ancho, height: 900 });
                await acceso.page.goto(base + '/supervisor/reportes.php?id_practica=' + f.notas.integrante);
                assert.equal(await acceso.page.locator('html').getAttribute('data-theme'), tema);
                assert.match(await acceso.page.locator('[data-notas-resumen]').innerText(), /6,25/);
                await acceso.page.locator('[aria-label="Notas semanales"]').screenshot({ path: path.join(f.directory, 'resumen-notas-' + tema + '-' + ancho + '.png') });
                await propio.page.setViewportSize({width:ancho,height:900});
                await propio.page.goto(base+'/practicante/mis-notas.php');
                const tab=propio.page.locator('.rrhh-notas-nav [aria-current="page"]');
                assert.ok(await tab.evaluate(el=>{
                    const muestra=document.createElement('span');
                    muestra.style.backgroundColor='var(--rrhh-primary)'; muestra.style.color='var(--rrhh-primary-content)';
                    document.body.appendChild(muestra);
                    const actual=getComputedStyle(el), esperado=getComputedStyle(muestra);
                    const coincide=actual.backgroundColor===esperado.backgroundColor && actual.color===esperado.color;
                    muestra.remove(); return coincide;
                }));
                await propio.page.screenshot({path:path.join(f.directory,'notas-navegacion-'+tema+'-'+ancho+'.png'),fullPage:true});
            }
        }
        fs.writeFileSync(path.join(f.directory, 'integracion-temas-results.json'), JSON.stringify({ aprobado: true, temas: ['light','dark','synthwave','aqua','coffee','black'], anchos: [320,1280] }, null, 2));
    } finally { await acceso.context.close(); await propio.context.close(); }
});
