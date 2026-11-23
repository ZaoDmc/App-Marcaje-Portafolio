// Probé el selector real con 200 candidatos ficticios, sin base de datos ni envío de designaciones.
const {test}=require('node:test');
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path');
const {execFileSync}=require('node:child_process'); const {chromium}=require('playwright');
const root=path.resolve(__dirname,'..'), assets=path.join(root,'public/assets'), php=process.env.PHP_BINARY||'php';
test('líderes: búsqueda con 200 integrantes, nombres acentuados, correo y RUT',async()=>{
    const result=JSON.parse(execFileSync(php,[path.join(__dirname,'mvc.php'),'--case','Admin','Notas','GET','normal'],{encoding:'utf8',cwd:root}));
    assert.deepEqual(result.warnings,[]);
    // Conservé el HTML de la vista y sustituí solo los datos de las opciones por un catálogo sintético grande.
    let options='<option value="">Seleccionar integrante</option>';
    for(let i=0;i<200;i++)options+=`<option value="${1000+i}" data-busqueda="Dámaris Ejemplo ${i} p${i}@example.test ${10000000+i}-K">Dámaris Ejemplo ${i} · p${i}@example.test · ${10000000+i}-K</option>`;
    const html=result.html.replace(/(<select id="lider-practica"[^>]*>)[\s\S]*?<\/select>/,'$1'+options+'</select>');
    const browser=await chromium.launch({channel:process.env.UI_CHANNEL||'msedge',headless:true});
    try {
        for(const js of [true,false]){
            const context=await browser.newContext({javaScriptEnabled:js}); const page=await context.newPage(); const errors=[];
            page.on('pageerror',e=>errors.push(e.message));
            await page.route('**/*',route=>{
                const u=new URL(route.request().url()),offset=u.pathname.indexOf('/assets/');
                const file=offset<0?'':path.resolve(assets,decodeURIComponent(u.pathname.slice(offset+8)));
                if(!file.startsWith(assets+path.sep)||!fs.existsSync(file))return route.abort();
                const types={'.css':'text/css','.js':'text/javascript','.png':'image/png','.woff2':'font/woff2'};
                return route.fulfill({contentType:types[path.extname(file)]||'application/octet-stream',body:fs.readFileSync(file)});
            });
            await page.setContent(html,{waitUntil:'networkidle'});
            const select=page.locator('#lider-practica'),search=page.locator('#lider-busqueda');
            assert.equal(await select.locator('option').count(),201);
            if(js){
                assert.ok(await search.isVisible());
                for(const query of ['DAMARIS EJEMPLO 137','p137@example.test','10.000.137-k']){
                    await search.fill(query); assert.equal(await select.locator('option').count(),2);
                    await select.selectOption('1137'); assert.equal(await select.inputValue(),'1137');
                }
                await search.fill('nadie-inexistente'); assert.ok(await select.isDisabled());
                assert.ok(await page.locator('[data-designar-lider]').isDisabled()); assert.equal(await select.inputValue(),'');
                assert.match(await page.locator('#lider-busqueda-estado').innerText(),/Sin coincidencias/);
                await search.fill(''); assert.equal(await select.locator('option').count(),201);
                assert.ok(await page.locator('[data-designar-lider]').isEnabled());
            }else{
                assert.equal(await search.isVisible(),false); await select.selectOption('1137');
                assert.equal(await select.inputValue(),'1137');
            }
            assert.deepEqual(errors,[]); await context.close();
        }
    } finally {await browser.close();}
});
