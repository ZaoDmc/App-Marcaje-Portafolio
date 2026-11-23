// Agregué pruebas en navegador real con HTML generado por PHP y datos ficticios.
// Se interceptan todos los recursos: solo se sirven archivos locales y no se envían formularios.
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const assets = path.join(root, 'public', 'assets');
const screenshotDir = path.join(root, 'scratch', 'phase6', 'screenshots');
// Revisé la solicitud propia y el detalle de revisión sin enviar formularios.
test('cambio de colacion conserva formulario y dialogo por rol',async()=>{
    for(const role of ['Practicante','Admin','Supervisor']) {
        const args=role==='Practicante'?[path.join(__dirname,'ui-pages.php'),'permisos','--populated']:[path.join(__dirname,'mvc.php'),'--case',role,'Permisos','GET','colacion'];
        const result=JSON.parse(execFileSync(php,args,{cwd:root,encoding:'utf8'}));
        assert.deepEqual(result.warnings,[]);
        for(const width of [390,1280]) {
            const {page,errors}=await pageFor(result.html,width);
            try {
                await page.evaluate(()=>document.documentElement.setAttribute('data-theme','coffee'));
                if(role==='Practicante') {
                    await page.locator('[name=inicio_colacion]').selectOption('14:00');
                    assert.equal(await page.locator('[name=inicio_colacion]').inputValue(),'14:00');
                    assert.equal(await page.locator('[name=accion][value=solicitar_colacion]').count(),1);
                } else {
                    await page.locator('[data-ui-open="colacion-910"]').click();
                    assert.ok(await page.locator('#colacion-910').isVisible());
                    assert.equal(await page.locator('#colacion-910 [value=revisar_colacion]').count(),1);
                }
                assert.deepEqual(errors,[]);
                await page.screenshot({path:path.join(screenshotDir,`colacion-${role}-${width}.png`),animations:'disabled'});
                if(role!=='Practicante'){await page.keyboard.press('Escape');await page.waitForFunction(()=>!document.querySelector('dialog[open]'));}
            }finally{await page.close();}
        }
    }
});
// Revisé las nuevas instrucciones y opciones iniciales sin enviar solicitudes ni consultar datos reales.
test('recuperacion sabatina informa corte y retira opciones entre semana',async()=>{
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'permisos','--populated'],{cwd:root,encoding:'utf8'}));
    assert.deepEqual(result.warnings,[]);
    for(const width of [390,1280]) {
        const {page,errors}=await pageFor(result.html,width);
        try {
            await page.evaluate(()=>document.documentElement.setAttribute('data-theme','coffee'));
            assert.match(await page.locator('#formPermiso').innerText(),/viernes previo a las 12:00/);
            assert.equal(await page.locator('#formPermiso option[value="COLACION"],#formPermiso option[value="EXTENSION"]').count(),0);
            assert.ok(await page.locator('[data-rec-fecha]').isVisible());
            assert.deepEqual(errors,[]);
            await page.screenshot({path:path.join(screenshotDir,`recuperacion-sabados-${width}.png`),animations:'disabled'});
        } finally {await page.close();}
    }
});
// Revisé la nueva bandeja con controladores ficticios, recursos locales y sin enviar formularios.
test('categorias de permisos abren detalle para ambos superiores',async()=>{
    for(const role of ['Admin','Supervisor']) for(const category of ['tiempo','compensacion']) {
        const result=JSON.parse(execFileSync(php,[path.join(__dirname,'mvc.php'),'--case',role,'Permisos','GET',category],{cwd:root,encoding:'utf8'}));
        assert.deepEqual(result.warnings,[]);
        for(const width of [390,1280]) {
            const {page,errors}=await pageFor(result.html,width);
            try {
                await page.evaluate(()=>document.documentElement.setAttribute('data-theme','coffee'));
                await page.locator('[data-ui-open^="permiso-"]').first().click();
                const dialog=page.locator('dialog[open]');
                assert.equal(await dialog.count(),1);
                assert.ok(await dialog.isVisible());
                assert.match(await dialog.innerText(),/Reunión ficticia/);
                if(category==='compensacion') {
                    assert.equal(await dialog.locator('[value=revisar_compensacion]').count(),0);
                    assert.equal(await dialog.locator('[value=revisar_tiempo]').count(),0);
                }
                assert.deepEqual(errors,[]);
                await page.screenshot({path:path.join(screenshotDir,`bandeja-${role}-${category}-${width}.png`),animations:'disabled'});
                await page.keyboard.press('Escape');
                await page.waitForFunction(()=>!document.querySelector('dialog[open]'));
            } finally {await page.close();}
        }
    }
});
// Verifiqué que la demostración daisyUI se cargue solo en login y conserve los controles accesibles.
test('login usa daisyUI aislado y conserva contraseña y formulario',async()=>{
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'login'],{cwd:root,encoding:'utf8'}));
    const version=execFileSync(php,['-r','echo require $argv[1];',path.join(root,'config','version.php')],{cwd:root,encoding:'utf8'}).trim();
    assert.deepEqual(result.warnings,[]);
    assert.match(result.html,/css\/login\.css/);
    const otra=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'recuperar'],{cwd:root,encoding:'utf8'}));
    assert.doesNotMatch(otra.html,/css\/login\.css/);
    for(const width of [320,1280]){
        const {page,errors}=await pageFor(result.html,width);
        try{
            assert.equal(await page.locator('html').getAttribute('data-theme'),'rrhh');
            assert.equal(await page.locator('button[type=submit].dui-btn-primary').count(),1);
            await page.locator('#password').fill('Ejemplo-local');await page.locator('#togglePassword').click();
            assert.equal(await page.locator('#password').getAttribute('type'),'text');
            await page.locator('#togglePassword').click();assert.equal(await page.locator('#password').getAttribute('type'),'password');
            await page.locator('#mantener_sesion').check();assert.equal(await page.locator('#mantener_sesion').isChecked(),true);
            assert.equal(await page.locator('[name=csrf_token]').count(),1);
            // Revisé el indicador con ambos temas y anchos, usando la versión del código.
            assert.equal(await page.locator('#app-version').innerText(),'Versión '+version);
            assert.ok(await page.locator('#app-version').isVisible());
            assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=document.documentElement.clientWidth+1));
            assert.deepEqual(errors,[]);
            await page.screenshot({path:path.join(screenshotDir,'login-daisyui-'+width+'.png'),fullPage:true});
            await page.locator('#themeToggle').click();
            assert.equal(await page.locator('html').getAttribute('data-theme'),'rrhh-dark');
            assert.ok(await page.locator('#app-version').isVisible());
            assert.equal(await page.locator('#app-version').innerText(),'Versión '+version);
            assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=document.documentElement.clientWidth+1));
            // Capturé el estado final del tema, evitando una imagen a mitad de transición.
            await page.screenshot({path:path.join(screenshotDir,'login-version-dark-'+width+'.png'),fullPage:true,animations:'disabled'});
        }finally{await page.close();}
    }
});
// Comprobé formularios y saldo poblado de la fase 6 en móvil y escritorio.
test('tiempo excepcional conserva formularios y saldo sin desbordar en móvil',async()=>{
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'permisos','--populated'],{cwd:root,encoding:'utf8'}));
    assert.deepEqual(result.warnings,[]);
    for(const width of [320,1280]){
        const {page,errors}=await pageFor(result.html,width);
        try{
            const panel=page.locator('section[aria-labelledby=titulo-tiempo-excepcional]');
            await panel.locator('summary').first().click();await panel.locator('summary').last().click();
            assert.match(await panel.innerText(),/Disponibles: 30 min/);
            assert.equal(await panel.locator('form').count(),2);
            const sizes=await page.evaluate(()=>({width:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
            assert.ok(sizes.scroll<=sizes.width+1,JSON.stringify(sizes));assert.deepEqual(errors,[]);
            await panel.screenshot({path:path.join(screenshotDir,'tiempo-excepcional-'+width+'.png')});
        }finally{await page.close();}
    }
});
const php = process.env.PHP_BINARY || 'php';
let browser;

function render(role, module, mode = 'normal') {
    const output = execFileSync(php, [path.join(__dirname, 'mvc.php'), '--case', role, module, 'GET', mode], { cwd: root, encoding: 'utf8' });
    const result = JSON.parse(output);
    assert.deepEqual(result.warnings, [], `PHP ${role}/${module}`);
    return result.html;
}

async function pageFor(html, width = 1280, recoveryOptions = null) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    // Nunca dejamos que una fixture de pruebas contacte con el servidor o Internet.
    await page.route('**/*', async route => {
        const url = new URL(route.request().url());
        const offset = url.pathname.indexOf('/assets/');
        const file = offset < 0 ? '' : path.resolve(assets, decodeURIComponent(url.pathname.slice(offset + 8)));
        if (!file.startsWith(assets + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {
            errors.push('Recurso inesperado: ' + url.pathname);
            return route.abort();
        }
        const type = { '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.woff2': 'font/woff2', '.woff': 'font/woff' }[path.extname(file)] || 'application/octet-stream';
        await route.fulfill({ status: 200, contentType: type, body: fs.readFileSync(file) });
    });
    // Permití un endpoint sintético solo para probar el editor; sigue bloqueado todo tráfico externo.
    if (recoveryOptions!==null) await page.route('**/practicante/api/horario-dia.php?*',route=>route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true,opciones:recoveryOptions})}));
    await page.setContent(html, { waitUntil: 'networkidle' });
    if (html.includes('js/ui.js')) await page.waitForFunction(() => document.documentElement.dataset.rrhhUiReady === 'true');
    await page.evaluate(() => document.fonts.ready);
    return { page, errors };
}

before(async () => {
    fs.mkdirSync(screenshotDir, { recursive: true });
    // Usamos Edge instalado. UI_BROWSER permite otra ruta o UI_CHANNEL otro canal.
    browser = await chromium.launch({ headless: true, ...(process.env.UI_BROWSER
        ? { executablePath: process.env.UI_BROWSER } : { channel: process.env.UI_CHANNEL || 'msedge' }) });
});
after(async () => { if (browser) await browser.close(); });

// Agregué restauración de tramos, tipos con dos ventanas y reflujo del editor con varias filas.
test('editor de recuperación restaura extensión correcta y mantiene filas móviles sin desborde',async()=>{
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'permisos'],{cwd:root,encoding:'utf8'}));
    const previos=JSON.stringify([{fecha:'2026-10-28',tipo:'EXTENSION',hora_inicio:'18:00',hora_fin:'19:00'}]).replaceAll('"','&quot;');
    const html=result.html.replace('data-previos="[]"',`data-previos="${previos}"`);
    const {page,errors}=await pageFor(html,320,[{tipo:'EXTENSION',inicio:'10:00',fin:'12:00'},{tipo:'EXTENSION',inicio:'18:00',fin:'20:00'}]);
    try{
        assert.equal(await page.locator('[data-rec-inicio]').inputValue(),'18:00');
        assert.equal(await page.locator('[data-rec-inicio]').getAttribute('min'),'18:00');
        assert.equal(await page.locator('[data-rec-fin]').getAttribute('max'),'20:00');
        assert.match(await page.locator('#badgeHorasCalculadas').innerText(),/60 minutos/);
        for(let i=0;i<3;i++)await page.locator('#agregarRecuperacion').click();
        assert.equal(await page.locator('.recuperacion-fila').count(),4);
        const names=await page.locator('[data-rec-fecha]').evaluateAll(items=>items.map(i=>i.name));
        assert.equal(new Set(names).size,4,'Campos de cada fila tienen nombres independientes');
        assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
        await page.locator('.recuperacion-fila').nth(2).locator('[data-rec-quitar]').click();
        assert.equal(await page.locator('.recuperacion-fila').count(),3);
        assert.deepEqual(errors,[]);
    }finally{await page.close();}
});

test('las vistas usan CSS local compilado y no contienen dependencias Bootstrap de interfaz', () => {
    // Distinguí la clase Bootstrap completa de dui-btn-primary, que pertenece al plugin daisyUI autorizado.
    const legacy = /cdn\.tailwindcss|bootstrap@|data-bs-|\b(?:form-control|col-md-\d|text-bg-)|(?<![\w-])btn-primary\b/;
    assert.match('class="btn-primary"',legacy);
    assert.doesNotMatch('class="dui-btn-primary"',legacy);
    function visit(dir) {
        for (const file of fs.readdirSync(dir, { withFileTypes: true })) {
            const target = path.join(dir, file.name);
            if (file.isDirectory()) visit(target);
            else if (file.name.endsWith('.php')) {
                const source = fs.readFileSync(target, 'utf8');
                assert.doesNotMatch(source, legacy, file.name);
            }
        }
    }
    visit(path.join(root, 'src', 'Views'));
    const compiled = fs.readFileSync(path.join(assets, 'css', 'tailwind.css'), 'utf8');
    for (const selector of ['.rrhh-input', '.rrhh-button', '.rrhh-table', '.bg-emerald-100', '.bg-amber-100']) {
        assert.ok(compiled.includes(selector), 'Clase compilada: ' + selector);
    }
    assert.ok(fs.existsSync(path.join(assets, 'icons', 'fonts', 'bootstrap-icons.woff2')));
});

test('las trece entradas compartidas se presentan en escritorio y móvil sin desbordar la página', async () => {
    for (const role of ['Admin', 'Supervisor']) {
        for (const module of ['Asistencia', 'Permisos', 'Reportes', 'CrearPracticante', 'PracticantesCargaMasiva', 'Practicantes', 'Catalogos']) {
            if (role === 'Supervisor' && module === 'Catalogos') continue;
            const { page, errors } = await pageFor(render(role, module));
            try {
                for (const width of [1280, 390]) {
                    await page.setViewportSize({ width, height: 900 });
                    // Esperé al estado cerrado del menú antes de medir/capturar móvil.
                    if (width < 1024) await page.waitForFunction(() => document.querySelector('[data-ui-drawer]').getBoundingClientRect().right <= 1);
                    const size = await page.evaluate(() => ({ viewport: innerWidth, content: document.documentElement.scrollWidth }));
                    assert.ok(size.content <= size.viewport + 1, `${role}/${module}/${width}: desborde de ${size.content}`);
                    assert.equal(await page.locator('main h1').count(), 1);
                    await page.screenshot({ path: path.join(screenshotDir, `${role}-${module}-${width}.png`), fullPage: true });
                }
                assert.deepEqual(errors, [], `${role}/${module}: errores JS/recursos`);
            } finally { await page.close(); }
        }
    }
});

test('aprobar/rechazar usa dialog nativo, mantiene formulario y devuelve el foco al cerrar', async () => {
    const { page, errors } = await pageFor(render('Admin', 'Permisos'));
    try {
        // Aprobar/Rechazar se abren desde la ventana "Revisar solicitud" del permiso.
        await page.locator('[data-ui-open="modalSolicitud_31"]').click();
        const approve = page.locator('#modalSolicitud_31 [data-ui-open="modalAprobar_31"]');
        await approve.click();
        assert.equal(await page.locator('#modalAprobar_31').evaluate(element => element.open), true);
        assert.equal(await page.locator('#modalAprobar_31 input[name="accion"]').inputValue(), 'aprobar_permiso');
        assert.ok((await page.locator('#modalAprobar_31 input[name="csrf_token"]').inputValue()).length > 20);
        await page.keyboard.press('Escape');
        await page.waitForFunction(() => !document.getElementById('modalAprobar_31').open);
        assert.equal(await approve.evaluate(element => element === document.activeElement), true);
        await page.locator('#modalSolicitud_31 [data-ui-open="modalRechazar_31"]').click();
        assert.equal(await page.locator('#modalRechazar_31 textarea').evaluate(element => element.required), true);
        await page.locator('#modalRechazar_31 [data-ui-close]').first().click();
        await page.waitForFunction(() => !document.getElementById('modalRechazar_31').open);
        // Rechazar se cerró sobre la solicitud; se cierra también "Revisar solicitud" con su botón Cerrar.
        assert.equal(await page.locator('#modalSolicitud_31').evaluate(element => element.open), true);
        await page.locator('#modalSolicitud_31 .rrhh-practicante-modal-close').click();
        await page.waitForFunction(() => !document.getElementById('modalSolicitud_31').open);
        // Esperé el evento close nativo: retirar open ocurre antes de limpiar el bloqueo del body.
        await page.waitForFunction(() => !document.body.classList.contains('rrhh-dialog-open'));
        assert.equal(await page.locator('body').evaluate(element => element.classList.contains('rrhh-dialog-open')), false);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('el menú móvil contiene el foco, cierra con Escape y permite volver a escritorio', async () => {
    const { page, errors } = await pageFor(render('Supervisor', 'Asistencia'), 390);
    try {
        const toggle = page.locator('[data-ui-drawer-toggle]');
        await toggle.click();
        assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
        assert.equal(await page.locator('[data-ui-main]').evaluate(element => element.inert), true);
        const last = page.locator('[data-ui-drawer] a').last();
        await last.focus(); await page.keyboard.press('Tab');
        assert.equal(await page.locator('[data-ui-drawer] a').first().evaluate(element => element === document.activeElement), true);
        await page.keyboard.press('Escape');
        assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
        assert.equal(await toggle.evaluate(element => element === document.activeElement), true);
        await toggle.click();
        await page.setViewportSize({ width: 1280, height: 900 });
        await page.waitForFunction(() => !document.querySelector('[data-ui-main]').inert);
        assert.equal(await page.locator('[data-ui-drawer]').getAttribute('aria-hidden'), 'false');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('opciones de cuenta y valores permitidos alternan visibilidad y aria-expanded', async () => {
    const { page, errors } = await pageFor(render('Admin', 'PracticantesCargaMasiva'));
    try {
        const trigger = page.locator('[data-ui-dropdown]');
        await trigger.click();
        assert.equal(await trigger.getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await trigger.getAttribute('aria-expanded'), 'false');
        const collapse = page.locator('[data-ui-collapse]');
        await collapse.click();
        assert.equal(await collapse.getAttribute('aria-expanded'), 'true');
        assert.equal(await page.locator('#valoresPermitidos').evaluate(element => element.hidden), false);
        await collapse.click();
        assert.equal(await page.locator('#valoresPermitidos').evaluate(element => element.hidden), true);
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('los campos Otra se muestran y ocultan sin utilidades antiguas de Bootstrap', async () => {
    const { page, errors } = await pageFor(render('Admin', 'CrearPracticante'));
    try {
        // Añadí opciones ficticias a la fixture vacía; no se envía el pre-registro.
        await page.locator('#id_institucion').evaluate(select => {
            select.add(new Option('Institución ficticia', '1')); select.add(new Option('Otra', '2'));
        });
        await page.locator('#id_institucion').selectOption('2');
        assert.equal(await page.locator('.campo-otra').first().evaluate(element => getComputedStyle(element).display !== 'none'), true);
        await page.locator('#id_institucion').selectOption('1');
        assert.equal(await page.locator('.campo-otra').first().evaluate(element => getComputedStyle(element).display), 'none');
        assert.deepEqual(errors, []);
    } finally { await page.close(); }
});

test('autenticación y pantallas del practicante conservan sus rejillas y recursos locales', async () => {
    for (const name of ['login','recuperar','restablecer','activar','roles','perfil','marcar','historial','permisos','panel','acom','404']) {
        const result = JSON.parse(execFileSync(php, [path.join(__dirname, 'ui-pages.php'), name], { cwd: root, encoding: 'utf8' }));
        assert.deepEqual(result.warnings, [], name + ': render PHP');
        const { page, errors } = await pageFor(result.html);
        try {
            for (const width of [1280, 390]) {
                await page.setViewportSize({ width, height: 900 });
                if (width < 1024 && await page.locator('[data-ui-drawer]').count()) {
                    await page.waitForFunction(() => document.querySelector('[data-ui-drawer]').getBoundingClientRect().right <= 1);
                }
                const sizes = await page.evaluate(() => ({ content: document.documentElement.scrollWidth, viewport: innerWidth }));
                assert.ok(sizes.content <= sizes.viewport + 1, name + ': desborde en ' + width);
                if (name === 'roles') {
                    const columns = await page.locator('form > .grid').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length);
                    assert.equal(columns, width < 640 ? 1 : 2, 'Selector de roles responsive');
                }
                await page.screenshot({ path: path.join(screenshotDir, `${name}-${width}.png`), fullPage: true });
            }
            assert.deepEqual(errors, [], name + ': JS/recursos');
        } finally { await page.close(); }
    }
});

// Agregué listas pobladas y textos largos: comprobé desbordes de página y tabla, no solo listas vacías.
test('listas pobladas del practicante conservan todos sus campos sin desplazamiento horizontal', async () => {
    const stage = process.env.UI_RESPONSIVE_STAGE || 'after';
    const evidence = path.join(root, 'scratch', 'responsive-practicantes', stage);
    fs.mkdirSync(evidence, {recursive:true});
    const measurements = [], problems = [];
    for (const name of ['perfil','marcar','historial','permisos','panel']) {
        const result = JSON.parse(execFileSync(php, [path.join(__dirname, 'ui-pages.php'), name, '--populated'], {cwd:root,encoding:'utf8'}));
        assert.deepEqual(result.warnings, [], name + ': render poblado');
        const {page,errors} = await pageFor(result.html);
        try {
            // 640 px también comprueba el ancho disponible de una ventana de 1280 px al ampliar al 200 %.
            for (const width of [320,390,640,768,1024,1280,1440]) {
                await page.setViewportSize({width,height:900});
                if (width < 1024) await page.waitForFunction(() => document.querySelector('[data-ui-drawer]').getBoundingClientRect().right <= 1);
                const sizes = await page.evaluate(() => ({
                    content:document.documentElement.scrollWidth, viewport:innerWidth,
                    tables:Array.from(document.querySelectorAll('table')).map(table=>({
                        width:table.getBoundingClientRect().width, available:table.parentElement.clientWidth,
                        scroll:table.scrollWidth, client:table.clientWidth,
                    })),
                }));
                measurements.push({name,width,...sizes});
                if (sizes.content > sizes.viewport+1 || sizes.tables.some(table=>table.width > table.available+1 || table.scroll > table.client+1)) {
                    problems.push({name,width,...sizes});
                }
                if (['marcar','historial','permisos'].includes(name)) {
                    assert.ok(await page.locator('tbody tr').count() > 0, name + ': contiene registros');
                }
                await page.screenshot({path:path.join(evidence,`${name}-${width}.png`),fullPage:true});
            }
            // Los datos largos permanecen completos en el documento; no se reemplazan por abreviaturas.
            const body = await page.locator('body').innerText();
            assert.doesNotMatch(body, /">/, name + ': atributos HTML bien formados');
            if (name === 'historial') assert.ok(body.includes('ObservacionSinEspacios'.repeat(10)));
            if (name === 'permisos') assert.ok(body.includes('MotivoDePruebaSinEspacios'.repeat(8)));
            if (name === 'perfil') assert.ok(body.includes('Certificado'.repeat(15)+'.pdf'));
            assert.deepEqual(errors, [], name + ': recursos/JS');
        } finally { await page.close(); }
    }
    fs.writeFileSync(path.join(evidence,'measurements.json'), JSON.stringify(measurements,null,2));
    assert.deepEqual(problems, [], 'Página o listado requieren desplazamiento horizontal');
});

// Agregué la pantalla operativa con ficha seleccionada: la prueba anterior solo mostraba el listado vacío.
test('gestión de practicantes de supervisor y administrador no requiere desplazamiento horizontal', async () => {
    const evidence=path.join(root,'scratch','responsive-gestion',process.env.UI_RESPONSIVE_STAGE || 'after');
    fs.mkdirSync(evidence,{recursive:true});
    const measurements=[], problems=[];
    for (const role of ['Supervisor','Admin']) {
        const {page,errors}=await pageFor(render(role,'Practicantes','management'));
        try {
            assert.equal(await page.locator('table').count(),4,'Listado, horario, áreas y excepciones cargados');
            for (const width of [320,390,640,768,1024,1280,1440,1920]) {
                await page.setViewportSize({width,height:900});
                if(width<1024) await page.waitForFunction(()=>document.querySelector('[data-ui-drawer]').getBoundingClientRect().right<=1);
                const sizes=await page.evaluate(()=>({content:document.documentElement.scrollWidth,viewport:innerWidth,
                    tables:Array.from(document.querySelectorAll('table')).map(table=>({width:table.getBoundingClientRect().width,available:table.parentElement.clientWidth,scroll:table.scrollWidth,client:table.clientWidth}))}));
                measurements.push({role,width,...sizes});
                if(sizes.content>sizes.viewport+1 || sizes.tables.some(table=>table.width>table.available+1 || table.scroll>table.client+1)) problems.push({role,width,...sizes});
                assert.ok(await page.locator('[name="horario[1][entrada_esperada]"]').isVisible(),'Horario sigue editable');
                // Exigí formato tabular y altura contenida en escritorio para evitar siete jornadas apiladas.
                if (width>=768) {
                    const compact=await page.locator('.rrhh-horario-table').evaluate(table=>({height:table.getBoundingClientRect().height,rowDisplay:getComputedStyle(table.querySelector('tbody tr')).display}));
                    assert.equal(compact.rowDisplay,'table-row','Horario conserva filas de escritorio');
                    assert.ok(compact.height<500,'Horario semanal compacto: '+compact.height+' px');
                    assert.ok(await page.locator('.rrhh-horario-table thead').isVisible(),'Encabezados visibles');
                }
                assert.equal(await page.locator('[name="horario[1][entrada_esperada]"]').inputValue(),'10:00');
                assert.ok(await page.locator('[name=motivo_anulacion_excepcion]').isVisible(),'Acción de excepción disponible');
                await page.screenshot({path:path.join(evidence,`${role}-${width}.png`),fullPage:true});
                if (width===1280) await page.locator('.rrhh-horario-table').screenshot({path:path.join(evidence,`${role}-horario-compacto.png`)});
            }
            const text=await page.locator('body').innerText();
            assert.ok(text.includes('ObservacionSinEspacios'.repeat(10)),'Observación completa');
            assert.doesNotMatch(text,/">/,'Sin atributos HTML rotos');
            assert.deepEqual(errors,[],'Recursos y JS correctos');
        } finally { await page.close(); }
    }
    fs.writeFileSync(path.join(evidence,'measurements.json'),JSON.stringify(measurements,null,2));
    assert.deepEqual(problems,[],'Gestión requiere desplazamiento horizontal');
});

// Comprobé ventana nativa, cancelación, foco, payload y bloqueo sin enviar peticiones reales.
test('confirmación de marcaje cancela sin enviar y bloquea el doble envío', async () => {
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'ui-pages.php'),'marcar'],{cwd:root,encoding:'utf8'}));
    assert.deepEqual(result.warnings,[]);
    for (const [code,label] of [['ENTRADA','Entrada'],['ENTRADA_COLACION','Inicio colación'],['SALIDA_COLACION','Fin colación'],['SALIDA','Salida']]) {
        const {page,errors}=await pageFor(result.html,390);
        try {
            // Exigí URL versionada para invalidar scripts anteriores; el POST pendiente se comprueba en E2E.
            const script=await page.locator('script[src*="js/ui.js"]').getAttribute('src');
            assert.match(script,/\?v=[a-f0-9]{16}$/);
            await page.evaluate(code=>{
                window.markPayloads=[];
                HTMLFormElement.prototype.submit=function(){window.markPayloads.push(Object.fromEntries(new FormData(this)));};
                // El servicio ya prueba secuencia; habilité aquí cada botón para aislar la interacción visual.
                document.querySelector('button[name=codigo_marca][value='+code+']').disabled=false;
            },code);
            const button=page.locator('button[name=codigo_marca][value='+code+']');
            assert.equal(await page.locator('#confirmacionMarcaje').count(),0,'Sin modal HTML de marcaje');
            const canceled=page.waitForEvent('dialog');
            const clickCancel=button.click();
            const popup=await canceled;
            assert.equal(popup.type(),'confirm');
            assert.match(popup.message(),new RegExp(label));
            await popup.dismiss();
            await clickCancel;
            assert.equal(await button.evaluate(element=>element===document.activeElement),true);
            assert.equal(await page.evaluate(()=>window.markPayloads.length),0);
            const accepted=page.waitForEvent('dialog');
            const clickAccept=page.evaluate(code=>{const button=document.querySelector('button[name=codigo_marca][value='+code+']');button.click();button.click();},code);
            await (await accepted).accept();
            await clickAccept;
            const payloads=await page.evaluate(()=>window.markPayloads);
            assert.equal(payloads.length,1);
            assert.equal(payloads[0].codigo_marca,code);
            assert.equal(payloads[0].confirmar_marca,'1');
            assert.ok(payloads[0].csrf_token.length>20);
            assert.equal(await button.isDisabled(),true);
            assert.deepEqual(errors,[]);
        } finally { await page.close(); }
    }
});
