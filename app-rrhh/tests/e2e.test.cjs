// Agregué recorridos autenticados con MariaDB, formularios reales y SMTP local.
// Solo operan contra la copia E2E y cuentas example.test; nunca contra Hostinger.
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
assert.ok(fixtureFile, 'Definir E2E_FIXTURE con la fixture creada por e2e-setup.php');
const f = JSON.parse(fs.readFileSync(fixtureFile, 'utf8'));
assert.match(f.database, /^rrhh_e2e_[a-f0-9]{12}$/);
assert.ok(path.resolve(f.directory).startsWith(path.join(root, 'scratch', 'phase8') + path.sep));
assert.equal(path.resolve(f.backend),path.join(path.resolve(f.directory),'backend-rrhh'));
assert.equal(path.resolve(f.frontend),path.join(path.resolve(f.directory),'public_html','portal'));
let browser, server, smtp, base, logStream;
const mails = [];
const results = [];
const state = () => JSON.parse(execFileSync(php, [path.join(root, 'tests/e2e-state.php'), fixtureFile], { encoding: 'utf8' }));
const listen = server => new Promise((resolve, reject) => {
    server.once('error', reject);
    server.listen(0, '127.0.0.1', () => resolve(server.address().port));
});
async function login(role) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(base + '/login.php');
    await page.locator('[name=email]').fill(role + '@example.test');
    await page.locator('[name=password]').fill(f.password);
    await Promise.all([page.waitForURL('**/' + role + '/**'), page.locator('button[type=submit]').click()]);
    return { context, page };
}
// Desde el rediseño de Permisos, el detalle, Aprobar/Rechazar y la validación de tramos viven en la ventana "Revisar solicitud".
async function abrirSolicitud(page, idPermiso) {
    await page.locator('[data-ui-open="modalSolicitud_' + idPermiso + '"]').click();
    await page.locator('#modalSolicitud_' + idPermiso + '[open]').waitFor();
}
async function abrirSolicitudQueContiene(page, locator) {
    const id = await locator.evaluate(el => el.closest('dialog.rrhh-solicitud-dialog').id.replace('modalSolicitud_', ''));
    await abrirSolicitud(page, id);
}
async function submit(form, page) {
    const invalid = await form.locator('input,select,textarea').evaluateAll(elements => elements.filter(e => !e.checkValidity()).map(e => e.name));
    assert.deepEqual(invalid, [], 'campos inválidos del escenario');
    await Promise.all([page.waitForNavigation(), form.locator('[type=submit]').last().click()]);
}
before(async () => {
    smtp = net.createServer(socket => {
        let buffer = '', data = false, message = '';
        socket.write('220 e2e.local SMTP\r\n');
        socket.on('data', chunk => {
            buffer += chunk.toString();
            while (buffer.includes('\r\n')) {
                const end = buffer.indexOf('\r\n');
                const line = buffer.slice(0, end); buffer = buffer.slice(end + 2);
                if (data) {
                    if (line === '.') { mails.push(message); message = ''; data = false; socket.write('250 captured\r\n'); }
                    else message += line + '\r\n';
                } else if (/^EHLO|^HELO/i.test(line)) socket.write('250-e2e.local\r\n250 SIZE 5000000\r\n');
                else if (/^RCPT TO:/i.test(line)) socket.write(/@example\.test>/i.test(line) ? '250 local recipient\r\n' : '550 only example.test\r\n');
                else if (/^DATA$/i.test(line)) { data = true; socket.write('354 data\r\n'); }
                else if (/^QUIT/i.test(line)) socket.end('221 bye\r\n');
                else socket.write('250 ok\r\n');
            }
        });
    });
    const mailPort = await listen(smtp);
    const reservation = net.createServer();
    const port = await listen(reservation);
    await new Promise(resolve => reservation.close(resolve));
    base = 'http://127.0.0.1:' + port;
    logStream = fs.createWriteStream(path.join(f.directory, 'php.log'));
    // Aislé también el error_log; la CLI instalada lo enviaba fuera del log capturado del servidor.
    server = spawn(php, ['-d','error_log='+path.join(f.directory,'php-errors.log'),'-S', '127.0.0.1:' + port, '-t', f.frontend, path.join(root, 'tests/e2e-router.php')], {
        cwd: root, env: { ...process.env, E2E_FRONTEND: f.frontend, BACKEND_PATH: f.backend,
            APP_ENV: 'production', APP_DEBUG: 'false', BASE_PATH: '', APP_BASE_PATH: '', BASE_URL: base, APP_URL: base,
            DB_HOST: f.db_host, DB_PORT: f.db_port, DB_NAME: f.database, DB_USER: f.db_user, DB_PASS: f.db_pass,
            MAIL_HOST: '127.0.0.1', MAIL_PORT: String(mailPort), MAIL_ENCRYPTION: '', MAIL_USERNAME: '', MAIL_PASSWORD: '',
            MAIL_FROM_ADDRESS: 'portal@example.test', MAIL_FROM_NAME: 'Portal E2E', MAIL_DEBUG: '0' }, stdio: ['ignore', 'pipe', 'pipe']
    });
    server.stdout.pipe(logStream); server.stderr.pipe(logStream);
    for (let i = 0; i < 50; i++) {
        try { if ((await fetch(base + '/login.php')).status === 200) break; } catch {}
        if (i === 49) throw new Error('El servidor de pruebas no inició');
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    browser = await chromium.launch({ channel: process.env.UI_CHANNEL || 'msedge', headless: true });
});
after(async () => {
    if (browser) await browser.close();
    if (server && server.exitCode === null) {
        server.kill(); await new Promise(resolve => server.once('exit', resolve));
    }
    if (smtp) await new Promise(resolve => smtp.close(resolve));
    if (logStream) logStream.end();
    fs.writeFileSync(path.join(f.directory, 'results.json'), JSON.stringify({ database: f.database, results, captured_mails: mails.length }, null, 2));
});

test('los tres roles autentican, recorren sus módulos y cierran sesión', async () => {
    const modules = {
        admin: ['dashboard','usuarios','practicantes','crear-practicante','practicantes-carga-masiva','asistencia','permisos','reportes','catalogos','auditoria'],
        supervisor: ['dashboard','practicantes','crear-practicante','practicantes-carga-masiva','asistencia','permisos','reportes','acom'],
        practicante: ['dashboard','mis-datos','marcar-asistencia','mi-historial','permisos']
    };
    for (const [role, pages] of Object.entries(modules)) {
        const { page, context } = await login(role);
        const jsErrors = [];
        page.on('pageerror',error=>jsErrors.push(error.message));
        try {
            for (const module of pages) {
                const response = await page.goto(base + '/' + role + '/' + module + '.php');
                assert.equal(response.status(), 200, role + '/' + module);
                assert.doesNotMatch(await page.locator('body').innerText(), /Ocurrió un error|No fue posible cargar|Fatal error|Warning:/i, role + '/' + module);
                assert.deepEqual(jsErrors,[],role+'/'+module+': errores JavaScript');
                if (role==='admin' && module==='dashboard') {
                    const charts = await page.evaluate(()=>typeof Chart==='undefined'?0:Object.keys(Chart.instances).length);
                    assert.ok(charts>0,'dashboard dibuja gráficas con datos ficticios');
                }
                await page.screenshot({ path: path.join(f.directory, role + '-' + module + '.png'), fullPage: true });
                results.push({ role, module, status: 'passed' });
                // Agregué la ficha seleccionada con datos MariaDB: el listado vacío no ejercitaba sus paneles.
                if (module === 'practicantes' && ['admin','supervisor'].includes(role)) {
                    const selected=await page.goto(base+'/'+role+'/practicantes.php?id_practicante=1');
                    assert.equal(selected.status(),200);
                    assert.doesNotMatch(await page.locator('body').innerText(),/No fue posible cargar|Fatal error|Warning:/i);
                    assert.ok(await page.locator('[name="horario[1][entrada_esperada]"]').isVisible());
                    assert.deepEqual(jsErrors,[],role+': ficha seleccionada');
                    await page.screenshot({path:path.join(f.directory,role+'-practicante-seleccionado.png'),fullPage:true});
                    results.push({role,module:'practicante-seleccionado',status:'passed'});
                }
            }
            if (role === 'practicante') {
                const denied = await context.request.get(base + '/admin/usuarios.php', { maxRedirects: 0 });
                assert.ok([302,403].includes(denied.status()));
            }
            await page.goto(base + '/logout.php');
            await page.goto(base + '/' + role + '/dashboard.php');
            assert.match(page.url(), /login\.php$/);
        } finally { await context.close(); }
    }
});

// Comprobé hoy en el calendario y la API, sin crear solicitudes ni modificar las marcas.
test('permisos permite consultar y seleccionar el horario de hoy', async () => {
    const {page,context}=await login('practicante');
    try {
        await page.goto(base+'/practicante/permisos.php');
        assert.equal(await page.locator('#fecha_ausencia').getAttribute('min'),f.today);
        const response=await context.request.get(base+'/practicante/api/horario-dia.php?fecha='+f.today);
        const body=await response.json();
        assert.equal(body.ok,true);
        assert.equal(Number(body.horas_planificadas),8);
        const loaded=page.waitForResponse(response=>response.url().includes('/api/horario-dia.php?fecha='+f.today));
        await page.locator('#fecha_ausencia').fill(f.today);
        await page.locator('#fecha_ausencia').dispatchEvent('change');
        await loaded;
        await page.waitForFunction(()=>!document.getElementById('panelHorarioDia').classList.contains('hidden'));
        assert.match(await page.locator('#txtHorarioJornada').innerText(),/09:00.*18:00/);
        results.push({flow:'permiso-fecha-actual',status:'passed'});
    } finally { await context.close(); }
});

test('practicante guarda perfil, sube foto/documento y descarga archivos autorizados', async () => {
    const { page, context } = await login('practicante');
    try {
        await page.goto(base + '/practicante/mis-datos.php');
        await page.locator('[name=telefono]').fill('000000000');
        await page.locator('[name=numero_direccion]').fill('1');
        await Promise.all([page.waitForNavigation(), page.locator('[name=accion_perfil][value=guardar_cambios]').click()]);
        assert.equal(await page.locator('[name=telefono]').inputValue(), '000000000');
        // Aseguré conservación de región/comuna; antes dos variables indefinidas las convertían en null.
        assert.equal(Number(state().practicantes.find(row=>Number(row.id_practicante)===1).id_region),1);
        assert.equal(Number(state().practicantes.find(row=>Number(row.id_practicante)===1).id_comuna),1);
        const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aVqkAAAAASUVORK5CYII=', 'base64');
        await page.locator('[name=foto_perfil]').setInputFiles({ name:'foto-ficticia.png', mimeType:'image/png', buffer:png });
        await Promise.all([page.waitForNavigation(), page.locator('[name=accion_perfil][value=subir_foto]').click()]);
        assert.ok(state().documentos_practicante.some(row => row.tipo_documento === 'FOTO_PERFIL'));
        const photo = await context.request.get(base + '/practicante/foto-perfil.php');
        assert.equal(photo.status(), 200);
        assert.deepEqual(await photo.body(), png);
        const pdf = Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n');
        await page.locator('[name="documentos_practica[]"]').setInputFiles({ name:'documento-ficticio.pdf', mimeType:'application/pdf', buffer:pdf });
        await Promise.all([page.waitForNavigation(), page.locator('[name=accion_perfil][value=subir_documentos]').click()]);
        const saved = state().documentos_practicante.find(row => row.tipo_documento === 'DOCUMENTO_PRACTICA');
        assert.ok(saved);
        const document = await context.request.get(base + '/practicante/descargar-documento.php?id=' + saved.id_documento);
        assert.equal(document.status(), 200);
        assert.deepEqual(await document.body(), pdf);
        const anonymous = await browser.newContext();
        try {
            assert.equal((await anonymous.request.get(base + '/practicante/descargar-documento.php?id=' + saved.id_documento, {maxRedirects:0})).status(),302);
        } finally { await anonymous.close(); }
        results.push({flow:'perfil-archivos',status:'passed'});
    } finally { await context.close(); }
});

test('practicante solicita permiso y supervisor lo aprueba con auditoría y plan por tramos', async () => {
    const { page, context } = await login('practicante');
    try {
        await page.goto(base + '/practicante/permisos.php');
        const absence = new Date(f.today + 'T12:00:00Z'); absence.setUTCDate(absence.getUTCDate() + 1);
        while ([0,6].includes(absence.getUTCDay())) absence.setUTCDate(absence.getUTCDate() + 1);
        const recovery = new Date(absence);
        while (recovery.getUTCDay() !== f.recovery_weekday) recovery.setUTCDate(recovery.getUTCDate() + 1);
        await page.locator('[name=fecha_ausencia]').fill(absence.toISOString().slice(0,10));
        await page.locator('#fecha_recuperacion').fill(recovery.toISOString().slice(0,10));
        await page.locator('#fecha_recuperacion').press('Tab');
        await page.waitForLoadState('networkidle');
        await page.locator('#hora_inicio_recuperacion').fill('10:00');
        await page.locator('#hora_fin_recuperacion').fill('18:00');
        await page.locator('[name=motivo]').fill('Solicitud ficticia de integración');
        await submit(page.locator('#formPermiso'), page);
        assert.equal(state().permisos_practicante.length, 1);
        assert.equal(state().permisos_practicante[0].estado, 'PENDIENTE');
    } finally { await context.close(); }
    const supervisor = await login('supervisor');
    try {
        await supervisor.page.goto(base + '/supervisor/permisos.php?desde=' + f.today + '&hasta=2099-12-31');
        const id = state().permisos_practicante[0].id_permiso;
        await abrirSolicitud(supervisor.page, id);
        await supervisor.page.locator('#modalSolicitud_' + id + ' [data-ui-open="modalAprobar_' + id + '"]').click();
        await submit(supervisor.page.locator('#modalAprobar_' + id + ' form'), supervisor.page);
        const saved = state();
        assert.equal(saved.permisos_practicante[0].estado, 'APROBADO');
        // La recuperación se autoriza por tramo: no reemplaza todo el horario por una excepción.
        assert.equal(saved.excepciones_horario_practicante.length, 1);
        assert.equal(saved.recuperaciones_permiso[0].estado,'PROGRAMADA');
        assert.equal(Number(saved.recuperaciones_permiso[0].minutos_validados),0);
        assert.ok(saved.auditoria_cambios.some(row => row.evento === 'APROBAR_PERMISO'));
        results.push({ flow: 'permiso-aprobación', status: 'passed' });
    } finally { await supervisor.context.close(); }
});

test('marcaje real, rechazo de duplicado y revisión de jornada sin descuento ficticio', async () => {
    const { page, context } = await login('practicante');
    try {
        await page.goto(base + '/practicante/marcar-asistencia.php');
        // Reproduje el POST sin bandera que mostraba Confirmar Entrada, ahora con JS activo.
        const initialCount=state().marcas_asistencia.length;
        await Promise.all([page.waitForNavigation(),page.locator('form[data-confirm-marcaje]').evaluate(form=>{
            const code=document.createElement('input');code.type='hidden';code.name='codigo_marca';code.value='ENTRADA';form.appendChild(code);
            HTMLFormElement.prototype.submit.call(form);
        })]);
        assert.equal(await page.locator('[data-marcaje-fallback]').count(),1);
        assert.equal(await page.locator('[data-marcaje-fallback]').isVisible(),false,'JS activo no muestra Confirmar Entrada dentro de la página');
        assert.equal(state().marcas_asistencia.length,initialCount,'POST de preparación no registra');
        assert.match(await page.locator('script[src*="js/ui.js"]').getAttribute('src'),/\?v=[a-f0-9]{16}$/);
        // Probé cancelación de las cuatro marcas y confirmación sin duplicados; colación también sin JavaScript.
        let postCount=0;
        page.on('request',request=>{ if(request.method()==='POST' && request.url().includes('/practicante/marcar-asistencia.php')) postCount++; });
        for (const [code,label] of [['ENTRADA','Entrada'],['ENTRADA_COLACION','Entrada a colación'],['SALIDA_COLACION','Salida de colación'],['SALIDA','Salida']]) {
            const count=state().marcas_asistencia.length;
            const posts=postCount;
            const button=page.locator('button[name=codigo_marca][value='+code+']');
            // Verifiqué una ventana confirm real del navegador, sin un modal dentro de la página.
            const canceled=page.waitForEvent('dialog');
            const clickCancel=button.click();
            const popup=await canceled;
            assert.equal(popup.type(),'confirm');
            assert.match(popup.message(),new RegExp(label));
            await popup.dismiss();
            await clickCancel;
            assert.equal(state().marcas_asistencia.length,count,'Cancelar '+code+' no escribe');
            assert.equal(postCount,posts,'Cancelar no envía POST');
            if(code==='ENTRADA_COLACION') {
                const noScript=await browser.newContext({javaScriptEnabled:false});
                await noScript.addCookies(await context.cookies());
                const plain=await noScript.newPage();
                try {
                    await plain.goto(base+'/practicante/marcar-asistencia.php');
                    for (const cancel of [true,false]) {
                        await Promise.all([plain.waitForNavigation(),plain.locator('button[name=codigo_marca][value='+code+']').click()]);
                        const fallback=plain.locator('form[data-marcaje-confirmado]');
                        assert.equal(await fallback.isVisible(),true,'Confirmación sin JavaScript');
                        assert.equal(state().marcas_asistencia.length,count,'Preparar no escribe');
                        await Promise.all([plain.waitForNavigation(),cancel ? fallback.locator('a').click() : fallback.locator('button').click()]);
                        assert.equal(state().marcas_asistencia.length,count+(cancel?0:1));
                    }
                } finally { await noScript.close(); }
                await page.reload();
            } else {
                const accepted=page.waitForEvent('dialog');
                const clickAccept=page.evaluate(code=>{
                    const button=document.querySelector('button[name=codigo_marca][value='+code+']');
                    button.click(); button.click();
                },code);
                const popup=await accepted;
                const navigation=page.waitForNavigation();
                await popup.accept();
                await clickAccept;
                await navigation;
                assert.equal(postCount,posts+1,'Confirmación doble envía un único POST');
            }
            assert.equal(state().marcas_asistencia.length,count+1,'Una marca guardada para '+code);
        }
        const token = await page.locator('[name=csrf_token]').inputValue();
        const before = state().marcas_asistencia.length;
        await context.request.post(base + '/practicante/marcar-asistencia.php', { form: {csrf_token:token,codigo_marca:'ENTRADA',confirmar_marca:'1'} });
        assert.equal(state().marcas_asistencia.length, before);
        await context.request.post(base + '/practicante/marcar-asistencia.php', { form: {csrf_token:'invalido',codigo_marca:'SALIDA',confirmar_marca:'1'} });
        assert.equal(state().marcas_asistencia.length, before, 'CSRF inválido no registra marcas');
        assert.ok(state().jornadas_asistencia.some(row => row.fecha === f.today));
    } finally { await context.close(); }
    const supervisor = await login('supervisor');
    try {
        await supervisor.page.goto(base + '/supervisor/asistencia.php?desde=' + f.yesterday + '&hasta=' + f.today + '&id_jornada=1');
        const form = supervisor.page.locator('form').filter({ has: supervisor.page.locator('[name=accion][value=cambiar_estado]') });
        // Probé ambos cierres y su reapertura explícita sin cambiar marcas ni aprobar extras.
        const marksBefore = state().marcas_asistencia;
        for (const status of ['REVISADA', 'OBSERVADA', 'CORREGIDA', 'OBSERVADA', 'REVISADA']) {
            await form.locator('[name=estado_jornada]').selectOption(status);
            await form.locator('[name=motivo]').fill('Revisión ficticia: jornada completa sin colación registrada');
            await submit(form, supervisor.page);
            assert.equal(state().jornadas_asistencia.find(row => Number(row.id_jornada) === 1).estado_jornada, status);
        }
        assert.deepEqual(state().marcas_asistencia, marksBefore, 'Los cierres no modifican marcas');
        const row = state().jornadas_asistencia.find(row => Number(row.id_jornada) === 1);
        assert.equal(row.estado_jornada, 'REVISADA');
        assert.equal(Number(row.horas_calculadas), 9);
        assert.equal(Number(row.horas_extra_aprobadas), 0);
        results.push({ flow:'marcaje-revisión', status:'passed' });
    } finally { await supervisor.context.close(); }
});

test('alta administrativa, horario, activación por correo y aislamiento de documentos', async () => {
    const admin = await login('admin');
    try {
        await admin.page.goto(base + '/admin/crear-practicante.php');
        for (const [name, value] of Object.entries({rut_numero:'22222222',rut_dv:'2',nombre_1:'Alta',apellido_paterno:'Ficticia',email:'alta@example.test',fecha_nacimiento:'2000-01-01',direccion:'Dirección ficticia',numero_direccion:'1',pais:'Chile',semestre:'4',horas_requeridas:'360',fecha_inicio_practica:f.today})) {
            await admin.page.locator('[name="' + name + '"]').fill(value);
        }
        for (const name of ['id_region','id_comuna','id_institucion','id_sede','id_carrera','id_tipo_practica','id_nacionalidad']) await admin.page.locator('[name=' + name + ']').selectOption('1');
        await admin.page.locator('[name=sexo]').selectOption('F');
        await admin.page.locator('[name="horario[1][activo]"]').check();
        for (const [name,time] of Object.entries({entrada_esperada:'09:00',salida_esperada:'18:00',colacion_inicio:'13:00',colacion_fin:'14:00'})) await admin.page.locator('[name="horario[1][' + name + ']"]').fill(time);
        await submit(admin.page.locator('form').filter({has:admin.page.locator('[name=nombre_1]')}), admin.page);
        assert.match(await admin.page.locator('body').innerText(), /Practicante pre-registrado correctamente/);
    } finally { await admin.context.close(); }
    const decoded = mails.at(-1).replace(/=\r\n/g,'').replace(/=([0-9A-F]{2})/gi, (_,hex) => String.fromCharCode(parseInt(hex,16)));
    const link = decoded.match(/http:\/\/127\.0\.0\.1:\d+\/activar-cuenta\.php\?token=[a-f0-9]+/);
    assert.ok(link, 'correo de activación capturado');
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
        await page.goto(link[0]);
        await page.locator('[name=password]').fill(f.password);
        await page.locator('[name=password_confirmacion]').fill(f.password);
        await submit(page.locator('form'), page);
        await page.goto(base + '/login.php');
        await page.locator('[name=email]').fill('alta@example.test');
        await page.locator('[name=password]').fill(f.password);
        await Promise.all([page.waitForURL('**/practicante/**'), page.locator('[type=submit]').click()]);
        const saved = state().documentos_practicante.find(row => row.tipo_documento === 'DOCUMENTO_PRACTICA');
        assert.equal((await context.request.get(base + '/practicante/descargar-documento.php?id=' + saved.id_documento, {maxRedirects:0})).status(),404,'otra cuenta no descarga documento ajeno');
        await page.goto(link[0]);
        assert.equal(await page.locator('[name=password]').count(),0,'activación no se reutiliza');
        results.push({flow:'alta-activación-aislamiento',status:'passed'});
    } finally { await context.close(); }
});

test('carga masiva CSV, plantillas CSV/XLSX y rechazo de duplicados', async () => {
    const {page,context} = await login('admin');
    try {
        for (const format of ['csv','xlsx']) {
            const response = await context.request.get(base + '/admin/practicantes-carga-masiva.php?plantilla=1&formato=' + format);
            assert.equal(response.status(),200);
            assert.match(response.headers()['content-disposition'], /attachment/);
            if (format === 'xlsx') assert.equal((await response.body()).subarray(0,2).toString(), 'PK');
            else assert.match(await response.text(), /nombre_1/);
        }
        await page.goto(base + '/admin/practicantes-carga-masiva.php');
        const file = {name:'carga-ficticia.csv',mimeType:'text/csv',buffer:Buffer.from('nombre_1,apellido_paterno,email\nMasiva,Ficticia,masiva@example.test\n')};
        const before = state().practicantes.length;
        for (let i=0;i<2;i++) {
            await page.locator('[name=archivo_csv]').setInputFiles(file);
            await submit(page.locator('form').filter({has:page.locator('[name=archivo_csv]')}),page);
            assert.equal(state().practicantes.length,before+1,'importación repetida no duplica practicante');
        }
        results.push({flow:'carga-masiva-plantillas',status:'passed'});
    } finally { await context.close(); }
});

// Agregué el recorrido de líderes sobre una fixture preparada por liderazgos-mariadb.php.
test('liderazgos: ADMIN designa, reemplaza y retira con CSRF y aislamiento de roles', async () => {
    // Preparé el caso cuando se ejecuta la suite completa, conservando también el uso aislado documentado.
    if (!f.liderazgo) {
        execFileSync(php,[path.join(root,'tests/liderazgos-mariadb.php'),fixtureFile],{encoding:'utf8'});
        f.liderazgo=JSON.parse(fs.readFileSync(fixtureFile,'utf8')).liderazgo;
    }
    const {page,context} = await login('admin');
    const scenario = f.liderazgo;
    const url = base + '/admin/notas.php?id_area=' + scenario.area;
    const registros = () => state().liderazgos_area.filter(row => Number(row.id_area) === scenario.area);
    try {
        assert.equal((await page.goto(url)).status(), 200);
        // Probé el filtro con integrantes reales de la fixture, sin cambiar su alcance de área.
        await page.locator('#lider-busqueda').waitFor({state:'visible'});
        const etiquetaCandidato = await page.locator('#lider-practica option[value="'+scenario.primera+'"]').textContent();
        const correoCandidato = etiquetaCandidato.split('·')[1].trim();
        await page.locator('#lider-busqueda').fill(correoCandidato);
        assert.equal(await page.locator('#lider-practica option').count(),2);
        await page.locator('#lider-busqueda').fill('');
        const payload = async (changes={}) => ({
            csrf_token: await page.locator('[name=csrf_token]').inputValue(),
            id_area:String(scenario.area), id_practica:String(scenario.primera),
            id_liderazgo_vigente:'0', accion:'designar', motivo:'Designación ficticia', ...changes
        });
        let response = await context.request.post(url,{form:await payload({csrf_token:'invalid'})});
        assert.match(await response.text(),/sesión del formulario expiró/);
        assert.equal(registros().length,0);
        response = await context.request.post(url,{form:await payload({id_practica:String(scenario.ajena)})});
        assert.match(await response.text(),/pertenecer actualmente al área/);
        assert.equal(registros().length,0);
        await page.locator('[name=id_practica]').selectOption(String(scenario.primera));
        await page.locator('[name=motivo]').fill('Designación ficticia por ADMIN');
        await Promise.all([page.waitForNavigation(),page.locator('[name=accion][value=designar]').click()]);
        assert.match(await page.locator('body').innerText(),/Liderazgo actualizado correctamente/);
        assert.equal(registros().length,1);
        response = await context.request.post(url,{form:await payload({id_practica:String(scenario.segunda)})});
        assert.match(await response.text(),/cambió mientras editabas/);
        assert.equal(registros().length,1,'formulario obsoleto no reemplaza al líder');
        await page.locator('[name=id_practica]').selectOption(String(scenario.segunda));
        await page.locator('[name=motivo]').fill('Reemplazo ficticio con motivo');
        await Promise.all([page.waitForNavigation(),page.locator('[name=accion][value=designar]').click()]);
        let rows=registros();
        assert.equal(rows.length,2);
        assert.equal(rows.filter(row=>row.fecha_fin===null).length,1);
        assert.equal(Number(rows.find(row=>row.fecha_fin===null).id_practica),scenario.segunda);
        await page.locator('[name=motivo]').fill('Retiro ficticio, conserva historial');
        await Promise.all([page.waitForNavigation(),page.locator('[name=accion][value=retirar]').click()]);
        rows=registros();
        assert.equal(rows.length,2);
        assert.ok(rows.every(row=>row.fecha_fin!==null && row.finalizado_por!==null));
        assert.match(await page.locator('body').innerText(),/Liderazgo retirado/);
        for (const role of ['supervisor','practicante']) {
            const other=await login(role);
            try { assert.equal((await other.page.goto(url)).status(),403); }
            finally { await other.context.close(); }
        }
        for (const width of [320,1280]) {
            await page.setViewportSize({width,height:900});
            await page.waitForFunction(movil=>{
                const rect=document.querySelector('[data-ui-drawer]').getBoundingClientRect();
                return movil?rect.right<=1:rect.left>=-1;
            },width<1024);
            assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Notas no desborda la página');
            await page.screenshot({path:path.join(f.directory,'notas-admin-'+width+'.png'),fullPage:true});
        }
        results.push({flow:'liderazgos-admin',status:'passed'});
    } finally { await context.close(); }
});

test('reportes de detalle reflejan la revisión y el acumulado reconocido', async () => {
    const {page,context} = await login('supervisor');
    try {
        const response = await page.goto(base + '/supervisor/reportes.php?id_practica=1&desde=' + f.yesterday + '&hasta=' + f.today);
        assert.equal(response.status(),200);
        const text = await page.locator('body').innerText();
        assert.match(text,/Ficticia/);
        // Comprobé el cierre y la eliminación del resaltado pendiente conservando el motivo histórico.
        assert.match(text,/REVISADA/);
        const reviewed = page.locator('tbody tr').filter({hasText:'REVISADA'});
        assert.equal(await reviewed.count(), 1);
        assert.doesNotMatch(await reviewed.getAttribute('class') || '', /bg-amber-50/);
        assert.match(await reviewed.innerText(), /Revisión ficticia/);
        const row = state().jornadas_asistencia.find(row => Number(row.id_jornada)===1);
        assert.match(text,new RegExp(Number(row.horas_calculadas)+' / 360 hrs'),'reporte conserva acumulado reconocido');
        assert.doesNotMatch(text,/Ocurrió un error|No fue posible/);
        results.push({flow:'reporte-detalle',status:'passed'});
    } finally { await context.close(); }
});

test('acompañamiento guarda seguimiento por AJAX y conserva puntaje histórico de solo lectura', async () => {
    const {page,context} = await login('supervisor');
    try {
        await page.goto(base + '/supervisor/acom.php?vista=acompanamiento');
        const saved = page.waitForResponse(response => response.url().includes('/supervisor/acom.php') && response.request().method() === 'POST');
        // Ajusté la regresión al seguimiento editable y la consulta académica de solo lectura.
        assert.equal(await page.locator('[name="evaluacion[1]"]').count(), 0);
        await page.locator('input[type=checkbox][name="evaluacion_enviada[1]"]').check();
        const response = await saved;
        assert.equal((await response.json()).ok,true);
        assert.equal(Number(state().acompanamiento.find(row => Number(row.id_practica)===1).evaluacion_enviada),1);
        assert.ok(state().auditoria_cambios.some(row => row.tabla_afectada === 'acompanamiento'));
        // Envié el antiguo campo manualmente: se informa el bloqueo y no se modifica el registro guardado.
        const anterior = state().acompanamiento;
        const token = await page.locator('#formAcompanamiento [name=csrf_token]').inputValue();
        const antigua = await context.request.post(base + '/supervisor/acom.php', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            form: { csrf_token: token, 'evaluacion[1]': '7' }
        });
        const rechazada = await antigua.json();
        assert.equal(rechazada.ok, false); assert.match(rechazada.error, /solo lectura/);
        assert.deepEqual(state().acompanamiento, anterior);
        results.push({flow:'acompañamiento-ajax',status:'passed'});
    } finally { await context.close(); }
});

test('administrador crea un catálogo y supervisor no puede modificarlo', async () => {
    const {page,context} = await login('admin');
    try {
        await page.goto(base + '/admin/catalogos.php?catalogo=carreras');
        await page.locator('[name="campos[nombre]"]').fill('Carrera ficticia E2E');
        await submit(page.locator('form').filter({has:page.locator('[name="campos[nombre]"]')}),page);
        assert.ok(state().carreras.some(row => row.nombre === 'Carrera ficticia E2E'));
    } finally { await context.close(); }
    const supervisor = await login('supervisor');
    try {
        const response = await supervisor.context.request.post(base + '/admin/catalogos.php', {maxRedirects:0,form:{accion:'guardar',catalogo:'carreras','campos[nombre]':'No autorizado'}});
        assert.ok([302,403].includes(response.status()));
        assert.ok(!state().carreras.some(row => row.nombre==='No autorizado'));
        results.push({flow:'catálogos-autorización',status:'passed'});
    } finally { await supervisor.context.close(); }
});

test('cuenta multirrol rechaza rol ajeno y permite elegir supervisor', async () => {
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
        await page.goto(base + '/login.php');
        await page.locator('[name=email]').fill('multi@example.test');
        await page.locator('[name=password]').fill(f.password);
        await Promise.all([page.waitForURL('**/seleccionar-rol.php'),page.locator('[type=submit]').click()]);
        const roles = state().roles_sistema;
        const token = await page.locator('[name=csrf_token]').inputValue();
        const denied = await context.request.post(base + '/seleccionar-rol.php',{form:{csrf_token:token,id_rol:String(roles.find(row=>row.nombre==='PRACTICANTE').id_rol)}});
        assert.match(await denied.text(),/no est.* disponible para esta cuenta/);
        await page.reload();
        const id = roles.find(row=>row.nombre==='SUPERVISOR').id_rol;
        await page.locator('[name=id_rol][value="'+id+'"]').check();
        await Promise.all([page.waitForURL('**/supervisor/**'),page.locator('[type=submit]').click()]);
        results.push({flow:'selector-multirrol',status:'passed'});
    } finally { await context.close(); }
});

// Agregué formularios reales para observar un permiso de hoy y conservar ambos tramos futuros.
test('fase 4: cruce de hoy impide aprobación e interrupción intermedia conserva el regreso', async () => {
    const practicante = await login('practicante');
    const supervisor = await login('supervisor');
    try {
        const p = practicante.page;
        const s = supervisor.page;
        await p.goto(base + '/practicante/permisos.php');
        await p.locator('#fecha_ausencia').fill(f.today);
        await p.locator('#fecha_ausencia').dispatchEvent('change');
        await p.waitForFunction(()=>!document.getElementById('panelHorarioDia').classList.contains('hidden'));
        const recovery = new Date(f.today+'T12:00:00Z'); recovery.setUTCDate(recovery.getUTCDate()+14);
        while (recovery.getUTCDay()!==6) recovery.setUTCDate(recovery.getUTCDate()+1);
        const rec = recovery.toISOString().slice(0,10);
        await p.locator('#fecha_recuperacion').fill(rec);
        await p.waitForLoadState('networkidle');
        await p.locator('#hora_inicio_recuperacion').fill('10:00');
        await p.locator('#hora_fin_recuperacion').fill('18:00');
        await p.locator('[name=motivo]').fill('Permiso ficticio con marcas de hoy');
        await submit(p.locator('#formPermiso'),p);
        let permiso=state().permisos_practicante.find(row=>row.fecha_ausencia===f.today);
        assert.ok(permiso);
        assert.equal(Number(permiso.requiere_revision),1);
        assert.match(await p.locator('body').innerText(),/EN OBSERVACIÓN/);
        const exceptions=state().excepciones_horario_practicante.length;
        await s.goto(base+'/supervisor/permisos.php');
        await abrirSolicitud(s, permiso.id_permiso);
        await s.locator('#modalSolicitud_' + permiso.id_permiso + ' [data-ui-open="modalAprobar_' + permiso.id_permiso + '"]').click();
        await submit(s.locator('#modalAprobar_'+permiso.id_permiso+' form'),s);
        assert.match(await s.locator('body').innerText(),/cruza tiempo trabajado/);
        assert.equal(state().excepciones_horario_practicante.length,exceptions);
        await abrirSolicitud(s, permiso.id_permiso);
        await s.locator('#modalSolicitud_' + permiso.id_permiso + ' [data-ui-open="modalRechazar_' + permiso.id_permiso + '"]').click();
        const rejection=s.locator('#modalRechazar_'+permiso.id_permiso+' form');
        await rejection.locator('[name=motivo_rechazo]').fill('La ausencia completa no corresponde a las marcas');
        await submit(rejection,s);
        assert.equal(state().permisos_practicante.find(row=>row.id_permiso===permiso.id_permiso).estado,'RECHAZADO');

        const absence=new Date(f.today+'T12:00:00Z'); absence.setUTCDate(absence.getUTCDate()+8);
        while ([0,6].includes(absence.getUTCDay())) absence.setUTCDate(absence.getUTCDate()+1);
        const date=absence.toISOString().slice(0,10);
        await p.goto(base+'/practicante/permisos.php');
        await p.locator('#fecha_ausencia').fill(date);
        await p.locator('#fecha_ausencia').dispatchEvent('change');
        await p.waitForFunction(()=>!document.getElementById('panelHorarioDia').classList.contains('hidden'));
        await p.locator('label').filter({has:p.locator('#radioParcial')}).click();
        await p.locator('#hora_inicio_ausencia').fill('11:00');
        await p.locator('#hora_fin_ausencia').fill('12:00');
        await p.locator('#hora_fin_ausencia').dispatchEvent('change');
        assert.equal(await p.locator('#txtNuevoHorario').innerText(),'09:00 – 11:00 y 12:00 – 18:00');
        assert.equal(await p.locator('#txtHorasTrabajo').innerText(),'7 hrs');
        await p.locator('#fecha_recuperacion').fill(rec);
        await p.waitForLoadState('networkidle');
        await p.locator('#hora_inicio_recuperacion').fill('10:00');
        await p.locator('#hora_fin_recuperacion').fill('11:00');
        await p.locator('[name=motivo]').fill('Interrupción ficticia con regreso');
        await submit(p.locator('#formPermiso'),p);
        permiso=state().permisos_practicante.find(row=>row.fecha_ausencia===date);
        assert.equal(Number(permiso.horas_trabajo_dia),7);
        assert.match(await p.locator('body').innerText(),/09:00–11:00 y 12:00–18:00/);
        await s.goto(base+'/supervisor/permisos.php');
        await abrirSolicitud(s, permiso.id_permiso);
        await s.locator('#modalSolicitud_' + permiso.id_permiso + ' [data-ui-open="modalAprobar_' + permiso.id_permiso + '"]').click();
        await submit(s.locator('#modalAprobar_'+permiso.id_permiso+' form'),s);
        assert.equal(state().permisos_practicante.find(row=>row.id_permiso===permiso.id_permiso).estado,'APROBADO');
        const exception=state().excepciones_horario_practicante.find(row=>row.fecha===date);
        assert.equal(exception.entrada_esperada,'09:00:00');
        assert.equal(exception.salida_esperada,'18:00:00');
        assert.equal(Number(exception.horas_planificadas),7);
        results.push({flow:'permisos-mismo-dia-interrupcion',status:'passed'});
    } finally { await practicante.context.close(); await supervisor.context.close(); }
});

// Agregué reparto en varios días, feriado común, validación con marcas y bloqueo de generación al supervisor.
test('fase 5: plan de colaciones y sábado, calendario y validación efectiva', async () => {
    const p=await login('practicante'),s=await login('supervisor'),a=await login('admin');
    try {
        const inicio=new Date(f.today+'T12:00:00Z');inicio.setUTCDate(inicio.getUTCDate()+35);
        while([0,6].includes(inicio.getUTCDay()))inicio.setUTCDate(inicio.getUTCDate()+1);
        const absence=inicio.toISOString().slice(0,10);
        const fechas=[];const d=new Date(inicio);
        while(fechas.length<3){d.setUTCDate(d.getUTCDate()+1);if(![0,6].includes(d.getUTCDay()))fechas.push(d.toISOString().slice(0,10));}
        const sat=new Date(d);while(sat.getUTCDay()!==6)sat.setUTCDate(sat.getUTCDate()+1);
        fechas.push(sat.toISOString().slice(0,10));
        await p.page.goto(base+'/practicante/permisos.php');
        await p.page.locator('#fecha_ausencia').fill(absence);
        await p.page.waitForLoadState('networkidle');
        await p.page.locator('label').filter({has:p.page.locator('#radioParcial')}).click();
        await p.page.locator('#hora_inicio_ausencia').fill('10:00');
        await p.page.locator('#hora_fin_ausencia').fill('16:00');
        await p.page.locator('#hora_fin_ausencia').press('Tab');
        for(let i=0;i<4;i++){
            if(i)await p.page.locator('#agregarRecuperacion').click();
            const fila=p.page.locator('.recuperacion-fila').nth(i);
            await fila.locator('[data-rec-fecha]').fill(fechas[i]);
            await p.page.waitForLoadState('networkidle');
            await fila.locator('[data-rec-tipo]').selectOption(i<3?'COLACION':'SABADO');
            await fila.locator('[data-rec-inicio]').fill(i<3?'13:00':'14:00');
            await fila.locator('[data-rec-fin]').fill(i<3?'14:00':'16:00');
            await fila.locator('[data-rec-fin]').press('Tab');
        }
        assert.match(await p.page.locator('#badgeHorasCalculadas').innerText(),/300 minutos/);
        await p.page.locator('[name=motivo]').fill('Cinco horas ficticias repartidas en colaciones y sábado');
        await submit(p.page.locator('#formPermiso'),p.page);
        const permiso=state().permisos_practicante.find(row=>row.fecha_ausencia===absence);
        assert.ok(permiso,'Solicitud de cinco horas guardada');
        let plan=state().recuperaciones_permiso.filter(row=>row.id_permiso===permiso.id_permiso);
        assert.equal(plan.length,4);assert.equal(plan.reduce((v,r)=>v+Number(r.minutos_planificados),0),300);
        await s.page.goto(base+'/supervisor/permisos.php');
        await abrirSolicitud(s.page, permiso.id_permiso);
        await s.page.locator('#modalSolicitud_' + permiso.id_permiso + ' [data-ui-open="modalAprobar_' + permiso.id_permiso + '"]').click();
        await submit(s.page.locator('#modalAprobar_'+permiso.id_permiso+' form'),s.page);
        plan=state().recuperaciones_permiso.filter(row=>row.id_permiso===permiso.id_permiso);
        assert.ok(plan.every(row=>row.estado==='PROGRAMADA'&&Number(row.minutos_validados)===0));

        await a.page.goto(base+'/admin/catalogos.php?catalogo=feriados');
        const holiday=new Date(f.today+'T12:00:00Z');holiday.setUTCDate(holiday.getUTCDate()+50);
        const fechaHoliday=holiday.toISOString().slice(0,10);
        await a.page.locator('[name="campos[nombre]"]').fill('Feriado ficticio E2E');
        await a.page.locator('[name="campos[fecha]"]').fill(fechaHoliday);
        await submit(a.page.locator('form').filter({has:a.page.locator('[name="campos[fecha]"]')}),a.page);
        assert.ok(state().feriados.some(row=>row.fecha===fechaHoliday));
        assert.ok(state().auditoria_cambios.some(row=>row.tabla_afectada==='feriados'));
        const respuesta=await p.context.request.get(base+'/practicante/api/horario-dia.php?fecha='+fechaHoliday);
        assert.equal((await respuesta.json()).ok,false,'Feriado excluido de horario ordinario');

        // El helper solo siembra la base ficticia y modela una colación autorizada antes de las marcas pasadas.
        const terminado=JSON.parse(execFileSync(php,[path.join(root,'tests/e2e-recovery-setup.php'),fixtureFile],{encoding:'utf8'}));
        assert.equal(Number(state().jornadas_asistencia.find(row=>Number(row.id_jornada)===1).horas_calculadas),8);
        await s.page.goto(base+'/supervisor/permisos.php');
        const validar=s.page.locator('form').filter({has:s.page.locator('[name=id_recuperacion][value="'+terminado.id_recuperacion+'"]')});
        await abrirSolicitudQueContiene(s.page, validar);
        await validar.locator('[name=motivo_validacion]').fill('Se verificó trabajo durante colación autorizada');
        await submit(validar,s.page);
        assert.match(await s.page.locator('body').innerText(),/Recuperación validada según marcas registradas/,'La validación debe confirmar su resultado');
        const verificado=state().recuperaciones_permiso.find(row=>Number(row.id_recuperacion)===terminado.id_recuperacion);
        assert.equal(verificado.estado,'VALIDADA');assert.equal(Number(verificado.minutos_validados),60);
        assert.equal(Number(state().jornadas_asistencia.find(row=>Number(row.id_jornada)===1).horas_calculadas),9);
        // Cerré extensión no trabajada con cero y solicité el saldo para un día nuevo, sin crédito anticipado.
        const sinTrabajo=s.page.locator('form').filter({has:s.page.locator('[name=id_recuperacion][value="'+terminado.id_no_trabajado+'"]')});
        await abrirSolicitudQueContiene(s.page, sinTrabajo);
        await sinTrabajo.locator('[name=motivo_validacion]').fill('No hubo trabajo registrado después de las 18:00');
        await submit(sinTrabajo,s.page);
        assert.equal(state().recuperaciones_permiso.find(row=>Number(row.id_recuperacion)===terminado.id_no_trabajado).estado,'VALIDADA');
        await p.page.goto(base+'/practicante/permisos.php');
        const saldo=p.page.locator('form').filter({has:p.page.locator('[name=accion][value=solicitar_recuperacion_adicional]')})
            .filter({has:p.page.locator('[name=id_permiso][value="'+terminado.id_permiso+'"]')});
        await saldo.locator('..').locator('summary').click();
        const nuevo=new Date(f.today+'T12:00:00Z');nuevo.setUTCDate(nuevo.getUTCDate()+52);
        while([0,6].includes(nuevo.getUTCDay()))nuevo.setUTCDate(nuevo.getUTCDate()+1);
        await saldo.locator('[name="recuperacion_adicional[fecha]"]').fill(nuevo.toISOString().slice(0,10));
        await saldo.locator('[name="recuperacion_adicional[tipo]"]').selectOption('COLACION');
        await saldo.locator('[name="recuperacion_adicional[hora_inicio]"]').fill('13:00');
        await saldo.locator('[name="recuperacion_adicional[hora_fin]"]').fill('14:00');
        await saldo.locator('[name=motivo_adicional]').fill('Solicito recuperar el saldo de la extensión no cumplida');
        await submit(saldo,p.page);
        const adicional=state().recuperaciones_permiso.find(row=>row.id_permiso===String(terminado.id_permiso) && row.fecha===nuevo.toISOString().slice(0,10))
            || state().recuperaciones_permiso.find(row=>Number(row.id_permiso)===terminado.id_permiso && row.fecha===nuevo.toISOString().slice(0,10));
        assert.ok(adicional);assert.equal(adicional.estado,'PROPUESTA');
        await s.page.goto(base+'/supervisor/permisos.php?estado=PENDIENTE');
        const autorizar=s.page.locator('form').filter({has:s.page.locator('[name=accion][value=autorizar_recuperacion_adicional]')})
            .filter({has:s.page.locator('[name=id_recuperacion][value="'+adicional.id_recuperacion+'"]')});
        await autorizar.locator('[name=motivo_adicional]').fill('Se autoriza recuperar la extensión pendiente en colación');
        await submit(autorizar,s.page);
        assert.equal(state().recuperaciones_permiso.find(row=>row.id_recuperacion===adicional.id_recuperacion).estado,'PROGRAMADA');
        assert.equal(Number(state().jornadas_asistencia.find(row=>Number(row.id_jornada)===1).horas_calculadas),9,'Saldo futuro no genera horas antes de trabajarlo');
        await s.page.goto(base+'/supervisor/asistencia.php?id_jornada=1&desde='+f.yesterday+'&hasta='+f.today);
        assert.equal(await s.page.getByRole('button',{name:'Generar ausencias',exact:true}).count(),0);
        const token=await s.page.locator('[name=csrf_token]').first().inputValue();
        const antes=state().jornadas_asistencia.length;
        const denied=await s.context.request.post(base+'/supervisor/asistencia.php',{form:{csrf_token:token,accion:'generar_ausencias_esperadas',confirmar_ausencias:'1',desde:f.yesterday,hasta:f.yesterday}});
        assert.match(await denied.text(),/Solo ADMIN puede generar/);
        assert.equal(state().jornadas_asistencia.length,antes);
        results.push({flow:'recuperacion-varios-dias-feriados-validacion',status:'passed'});
    }finally{await p.context.close();await s.context.close();await a.context.close();}
});

test('recuperación SMTP local, contraseña nueva y token de un solo uso', async () => {
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
        await page.goto(base + '/recuperar-password.php');
        await page.locator('[name=email]').fill('admin@example.test');
        await submit(page.locator('form'), page);
        assert.ok(mails.length >= 1, 'correo capturado exclusivamente por SMTP local');
        const decoded = mails.at(-1).replace(/=\r\n/g,'').replace(/=([0-9A-F]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex,16)));
        const link = decoded.match(/http:\/\/127\.0\.0\.1:\d+\/restablecer-password\.php\?token=[a-f0-9]+/);
        assert.ok(link, 'enlace de recuperación');
        await page.goto(link[0]);
        const changed = f.password + 'Nueva';
        await page.locator('[name=password]').fill(changed);
        await page.locator('[name=password_confirmacion]').fill(changed);
        await submit(page.locator('form'), page);
        assert.ok(state().tokens_usuario.some(row => Number(row.usado) === 1));
        await page.goto(link[0]);
        assert.equal(await page.locator('[name=password]').count(), 0, 'token consumido no permite reutilización');
        await page.goto(base + '/login.php');
        await page.locator('[name=email]').fill('admin@example.test');
        await page.locator('[name=password]').fill(changed);
        await Promise.all([page.waitForURL('**/admin/**'), page.locator('[type=submit]').click()]);
        results.push({ flow:'recuperación', status:'passed' });
    } finally { await context.close(); }
});
// Agregué un recorrido completo por los formularios de tiempo excepcional y canje parcial.
test('tiempo excepcional requiere motivo, aprobación y reserva sin sumar extras',async()=>{
    const escenario=JSON.parse(execFileSync(php,[path.join(root,'tests/e2e-tiempo-setup.php'),fixtureFile],{encoding:'utf8'}));
    const p=await login('practicante');const s=await login('supervisor');
    try{
        await p.page.goto(base+'/practicante/permisos.php');
        const solicitud=p.page.locator('form').filter({has:p.page.locator('[name=accion][value=solicitar_tiempo]')});
        await solicitud.locator('..').locator('summary').click();
        // Reproduje el rechazo de futuro con mensaje concreto y sin entrar al permiso normal.
        const permisosAntes=state().permisos_practicante.length;
        await solicitud.locator('[name=id_jornada]').selectOption(String(escenario.id_jornada_futura));
        await solicitud.locator('[name=hora_inicio_tiempo]').fill('18:00');await solicitud.locator('[name=hora_fin_tiempo]').fill('19:30');
        await solicitud.locator('[name=motivo_tiempo]').fill('Prueba de salida futura');await submit(solicitud,p.page);
        assert.match(await p.page.locator('body').innerText(),/La salida de la jornada o el fin solicitado está en el futuro/);
        assert.equal(state().tiempos_excepcionales.length,0);assert.equal(state().permisos_practicante.length,permisosAntes);
        assert.equal(await solicitud.locator('[name=hora_fin_tiempo]').inputValue(),'19:30');
        assert.equal(await solicitud.locator('[name=motivo_tiempo]').inputValue(),'Prueba de salida futura');
        assert.equal(await solicitud.locator('[name=id_jornada]').inputValue(),String(escenario.id_jornada_futura));
        assert.equal(state().jornadas_asistencia.find(j=>Number(j.id_jornada)===escenario.id_jornada).estado_jornada,'OBSERVADA');
        await solicitud.locator('..').locator('summary').click();
        await solicitud.locator('[name=id_jornada]').selectOption(String(escenario.id_jornada));
        await solicitud.locator('[name=hora_inicio_tiempo]').fill('18:00');await solicitud.locator('[name=hora_fin_tiempo]').fill('19:00');
        await solicitud.locator('[name=motivo_tiempo]').fill('Reunión excepcional solicitada por el jefe');
        await submit(solicitud,p.page);
        let credito=state().tiempos_excepcionales.at(-1);assert.ok(credito);assert.equal(credito.estado,'PENDIENTE');assert.equal(Number(credito.minutos_solicitados),60);
        const vence=credito.vence_utc;
        await s.page.goto(base+'/supervisor/permisos.php');
        const revision=s.page.locator('article[data-tiempo-id="'+credito.id_tiempo+'"] form').filter({has:s.page.locator('[name=accion][value=revisar_tiempo]')});
        await revision.locator('[name=motivo_tiempo]').fill('El jefe autorizó la permanencia por cierre de reunión');
        await submit(revision,s.page);
        credito=state().tiempos_excepcionales.at(-1);assert.equal(credito.estado,'APROBADO');assert.equal(Number(credito.minutos_aprobados),60);assert.equal(credito.vence_utc,vence);
        const origen=state().jornadas_asistencia.find(j=>Number(j.id_jornada)===escenario.id_jornada);assert.equal(Number(origen.horas_calculadas),8);assert.equal(Number(origen.horas_extra_aprobadas),0);assert.equal(origen.estado_jornada,'VALIDA');
        await p.page.goto(base+'/practicante/permisos.php');
        const canje=p.page.locator('article[data-tiempo-id="'+credito.id_tiempo+'"] form').filter({has:p.page.locator('[name=accion][value=solicitar_compensacion]')});
        await canje.locator('..').locator('summary').click();await canje.locator('[name=fecha_compensacion]').fill(escenario.fecha_compensacion);
        await canje.locator('[name=tipo_compensacion]').selectOption('ENTRADA');await canje.locator('[name=hora_inicio_tiempo]').fill('09:00');await canje.locator('[name=hora_fin_tiempo]').fill('09:30');
        await canje.locator('[name=motivo_tiempo]').fill('Usar treinta minutos entrando después');await submit(canje,p.page);
        let compensacion=state().compensaciones_tiempo.at(-1);assert.ok(compensacion,await p.page.locator('body').innerText());assert.equal(compensacion.estado,'PENDIENTE');assert.equal(Number(compensacion.minutos),30);
        await s.page.goto(base+'/supervisor/permisos.php');
        const aprobar=s.page.locator('[data-compensacion-id="'+compensacion.id_compensacion+'"] form');await aprobar.locator('[name=motivo_tiempo]').fill('Ingreso posterior aprobado con el saldo disponible');await submit(aprobar,s.page);
        compensacion=state().compensaciones_tiempo.at(-1);assert.equal(compensacion.estado,'APROBADA');assert.equal(Number(compensacion.minutos_aplicados),0);
        await p.page.goto(base+'/practicante/permisos.php');assert.match(await p.page.locator('[data-tiempo-id="'+credito.id_tiempo+'"]').innerText(),/Disponibles: 30 min/);
        // Contrasté el informe con el libro de crédito sin incrementar las horas ordinarias de origen.
        await s.page.goto(base+'/supervisor/reportes.php?id_practica=1&desde='+escenario.fecha+'&hasta='+escenario.fecha_compensacion);
        const desglose=s.page.locator('[aria-label="Desglose de recuperación y compensación"]');
        assert.match(await desglose.innerText(),/Tiempo excepcional aprobado 60 min/);
        assert.match(await desglose.innerText(),/saldo excepcional disponible 30 min/);
        assert.ok(state().auditoria_cambios.some(a=>a.evento==='REVISAR_TIEMPO_EXCEPCIONAL'));
        // Verifiqué cálculo y presentación con MariaDB: 29 antes + 29 después no se acumulan ni elevan seis horas.
        for(const ejemplo of escenario.tolerancias){
            const jornada=state().jornadas_asistencia.find(j=>Number(j.id_jornada)===ejemplo.id_jornada);
            assert.equal(jornada.estado_jornada,ejemplo.estado);assert.equal(Number(jornada.horas_calculadas),6);assert.equal(Number(jornada.horas_extra_aprobadas),0);
            assert.equal(Number(jornada.flag_horas_extra),0);
            if(ejemplo.estado==='VALIDA')assert.equal(jornada.observacion,null);
            await s.page.goto(base+'/supervisor/asistencia.php?id_jornada='+ejemplo.id_jornada);
            assert.equal(await s.page.locator('h1').innerText(),'Revision de asistencia');
        }
        results.push({flow:'tiempo-excepcional-compensacion',status:'passed'});
    }finally{await p.context.close();await s.context.close();}
});

// Verifiqué la resolución de canjes ya usados, no una autorización retrospectiva de solicitudes nuevas.
test('revalidar canje histórico corrige el avance sin renovar saldo vencido',async()=>{
    const caso=JSON.parse(execFileSync(php,[path.join(root,'tests/e2e-validacion-setup.php'),fixtureFile],{encoding:'utf8'}));
    const s=await login('supervisor');const p=await login('practicante');
    try{
        assert.equal(Number(state().jornadas_asistencia.find(j=>Number(j.id_jornada)===caso.id_destino).horas_calculadas),7);
        await s.page.goto(base+'/supervisor/permisos.php');
        const credito=s.page.locator('[data-tiempo-id="'+caso.id_tiempo+'"]');
        const revisar=credito.locator('form').filter({has:s.page.locator('[name=accion][value=revisar_tiempo]')});
        await revisar.locator('[name=motivo_tiempo]').fill('Revalidé la evidencia original de la reunión');await submit(revisar,s.page);
        assert.equal(state().tiempos_excepcionales.find(t=>Number(t.id_tiempo)===caso.id_tiempo).estado,'APROBADO');
        const canje=s.page.locator('[data-compensacion-id="'+caso.id_compensacion+'"] form');
        await canje.locator('[name=motivo_tiempo]').fill('Confirmé autorización anterior al uso y marcas coherentes');await submit(canje,s.page);
        const registros=state();
        assert.equal(registros.compensaciones_tiempo.find(c=>Number(c.id_compensacion)===caso.id_compensacion).estado,'APLICADA');
        assert.equal(Number(registros.jornadas_asistencia.find(j=>Number(j.id_jornada)===caso.id_destino).horas_calculadas),8);
        assert.equal(registros.tiempos_excepcionales.find(t=>Number(t.id_tiempo)===caso.id_tiempo).vence_utc,caso.vence_utc);
        await p.page.goto(base+'/practicante/permisos.php');
        const vencido=p.page.locator('[data-tiempo-id="'+caso.id_tiempo+'"]');assert.match(await vencido.innerText(),/Disponibles: 0 min/);
        assert.equal(await vencido.locator('form').count(),0);
        await s.page.goto(base+'/supervisor/reportes.php?id_practica=1&desde='+caso.destino+'&hasta='+caso.destino);
        const informe=s.page.locator('[aria-label="Desglose de recuperación y compensación"]');
        assert.match(await informe.innerText(),/Compensación aplicada 60 min/);assert.match(await informe.innerText(),/Tiempo excepcional aprobado 0 min/);
        // Comprobé el panel nuevo con datos reales en móvil/escritorio y conservé evidencia visual local.
        for(const width of [320,1280]){
            await s.page.setViewportSize({width,height:900});
            // Esperé el cierre/apertura responsive del menú para no capturar un fotograma de su transición sobre el panel.
            await s.page.waitForFunction(movil=>{
                const rect=document.querySelector('[data-ui-drawer]').getBoundingClientRect();
                return movil?rect.right<=1:rect.left>=-1;
            },width<1024);
            assert.ok(await informe.evaluate(panel=>panel.scrollWidth<=panel.clientWidth+1),'Desglose sin desborde horizontal');
            await informe.screenshot({path:path.join(f.directory,'informe-tiempos-'+width+'.png')});
        }
        results.push({flow:'revalidacion-historica-informe',status:'passed'});
    }finally{await s.context.close();await p.context.close();}
});
