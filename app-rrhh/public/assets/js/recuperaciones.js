// Agregué un editor de tramos: consulta horario individual, mantiene minutos y conserva datos tras errores.
document.addEventListener('DOMContentLoaded', () => {
    const lista=document.getElementById('recuperacionesFilas');
    if (!lista) return;
    const plantilla=lista.firstElementChild.cloneNode(true);
    const api=lista.dataset.api;
    let siguiente=1;
    const minutos=h=>{ const [a,b]=h.split(':').map(Number);return a*60+b; };
    const hhmm=m=>`${String(Math.floor(m/60)).padStart(2,'0')}:${String(m%60).padStart(2,'0')}`;
    const netosFila=f=>{
        const a=f.querySelector('[data-rec-inicio]').value,b=f.querySelector('[data-rec-fin]').value;
        const tipo=f.querySelector('[data-rec-tipo]')?.value||'';
        if(!a||!b) return {netos:0,colacion:0};
        const brutos=Math.max(0,minutos(b)-minutos(a));
        // Desconté 1 h de colación en tramos sabatinos/feriados de 6 h o más, igual que el servidor.
        const colacion=(tipo==='SABADO'||tipo==='FERIADO')&&brutos>=360?60:0;
        return {netos:Math.max(0,brutos-colacion),colacion};
    };
    const total=()=>{
        let descuentaColacion=false;
        const suma=Array.from(lista.children).reduce((s,f)=>{
            const r=netosFila(f);
            if(r.colacion>0) descuentaColacion=true;
            return s+r.netos;
        },0);
        const req=Number(lista.dataset.minutosAusencia||0);
        const badge=document.getElementById('badgeHorasCalculadas');
        const textoBase=`Plan de recuperación: ${suma} minutos (${(suma/60).toFixed(2)} hrs)`;

        const izq=document.createElement('div');
        izq.className='min-w-0 flex-1 space-y-0.5';
        const lineaPrincipal=document.createElement('div');
        lineaPrincipal.className='font-bold';
        lineaPrincipal.textContent=textoBase;
        izq.appendChild(lineaPrincipal);
        if(descuentaColacion) {
            const nota=document.createElement('div');
            nota.className='text-[11px] opacity-80 font-medium';
            nota.textContent='Incluye descuento de 1 h de colación';
            izq.appendChild(nota);
        }

        badge.className='w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-xs border transition-colors';
        if(req>0 && suma===req) {
            badge.classList.add('bg-emerald-50','text-emerald-800','border-emerald-200');
            const estado=document.createElement('div');
            estado.className='font-semibold';
            estado.textContent=`Coincide con ${req} min (${(req/60).toFixed(2)} hrs)`;
            izq.appendChild(estado);
            const ico=document.createElement('i');
            ico.className='bi bi-check-circle-fill text-lg shrink-0';
            ico.setAttribute('aria-hidden','true');
            badge.replaceChildren(izq,ico);
        } else if(req>0 && suma>0) {
            const diff=Math.abs(suma-req);
            const estadoTxt=suma>req?`Sobran ${diff} min · requiere ${req} min`:`Faltan ${diff} min · requiere ${req} min`;
            badge.classList.add('bg-amber-50','text-amber-800','border-amber-200');
            const estado=document.createElement('div');
            estado.className='font-semibold';
            estado.textContent=estadoTxt;
            izq.appendChild(estado);
            const ico=document.createElement('i');
            ico.className='bi bi-exclamation-circle-fill text-lg shrink-0';
            ico.setAttribute('aria-hidden','true');
            badge.replaceChildren(izq,ico);
        } else {
            badge.classList.add('bg-blue-50','text-blue-700','border-blue-200');
            badge.replaceChildren(izq);
        }
    };
    async function opciones(fila) {
        const fecha=fila.querySelector('[data-rec-fecha]').value;
        const select=fila.querySelector('[data-rec-tipo]');
        if (!fecha) return;
        try {
            const resp=await fetch(`${api}?recuperacion=1&fecha=${encodeURIComponent(fecha)}`);
            const data=await resp.json();
            if (fila.querySelector('[data-rec-fecha]').value!==fecha) return;
            const anterior=select.value;
            select.replaceChildren();
            const rangos=data.ok?data.opciones:[];
            for (const r of rangos) {
                const opt=document.createElement('option');
                opt.value=r.tipo; opt.textContent=`${r.tipo}: ${r.inicio}–${r.fin}`;
                opt.dataset.inicio=r.inicio;opt.dataset.fin=r.fin;
                select.appendChild(opt);
            }
            if (!rangos.length) {
                const opt=document.createElement('option');opt.value='';opt.textContent=data.error||'No hay tramos disponibles ese día';select.appendChild(opt);
            } else {
                // Diferencié extensiones antes/después del turno que comparten el mismo tipo.
                const a=fila.querySelector('[data-rec-inicio]').value,b=fila.querySelector('[data-rec-fin]').value;
                const encaja=rangos.findIndex(r=>r.tipo===anterior && a && b && a>=r.inicio && b<=r.fin);
                const mismoTipo=rangos.findIndex(r=>r.tipo===anterior);
                select.selectedIndex=encaja>=0?encaja:mismoTipo>=0?mismoTipo:0;
            }
            sugerir(fila,false);
        } catch {
            fila.querySelector('[data-rec-ayuda]').textContent='No se pudo consultar el horario. Recarga y vuelve a seleccionar la fecha.';
            select.value='';
        }
    }
    function sugerir(fila,reemplazar) {
        const opcion=fila.querySelector('[data-rec-tipo]').selectedOptions[0];
        if (!opcion?.dataset.inicio) return;
        const campoIni=fila.querySelector('[data-rec-inicio]');
        const campoFin=fila.querySelector('[data-rec-fin]');
        campoIni.min=opcion.dataset.inicio;campoIni.max=opcion.dataset.fin;
        campoFin.min=opcion.dataset.inicio;campoFin.max=opcion.dataset.fin;
        if (reemplazar||!campoIni.value) campoIni.value=opcion.dataset.inicio;

        const req=Number(lista.dataset.minutosAusencia||0);
        const otros=Array.from(lista.children).reduce((s,f)=>f===fila?s:s+netosFila(f).netos,0);
        const faltante=Math.max(0,req-otros);
        let finSugerido=opcion.dataset.fin;
        if (req>0 && faltante>0 && campoIni.value) {
            const brutosNecesarios=(opcion.value==='SABADO'||opcion.value==='FERIADO')&&faltante>300?faltante+60:faltante;
            const finMin=Math.min(minutos(opcion.dataset.fin),minutos(campoIni.value)+brutosNecesarios);
            if (finMin>minutos(campoIni.value)) finSugerido=hhmm(finMin);
        }
        if (reemplazar||!campoFin.value) campoFin.value=finSugerido;
        fila.querySelector('[data-rec-ayuda]').textContent=opcion.value==='COLACION'
            ? 'Solo el tramo de colación de tu horario. Si está autorizado y lo trabajas, no marques la pausa; el supervisor verificará el tiempo trabajado.'
            : opcion.value==='FERIADO'?'Requiere autorización expresa y motivo del supervisor, además de validar las marcas.'
            : 'Solo sábados 10:00–20:00. Solicita antes del viernes previo a las 12:00; después del corte elige el sábado siguiente. El supervisor validará tus marcas.';
        total();
    }
    function agregar(datos={}) {
        if (lista.children.length>=60) return;
        const f=plantilla.cloneNode(true);const i=siguiente++;
        f.dataset.index=String(i);
        for (const campo of f.querySelectorAll('input,select')) {
            campo.name=campo.name.replace(/\[\d+\]/,`[${i}]`);
            if (campo.id) { const viejo=campo.id;campo.id=`${viejo}_${i}`;f.querySelector(`label[for="${viejo}"]`)?.setAttribute('for',campo.id); }
            campo.value='';
        }
        for (const elemento of f.querySelectorAll('[id]')) {
            if (!elemento.matches('input,select')) elemento.removeAttribute('id');
        }
        lista.appendChild(f);
        for (const [clave,valor] of Object.entries(datos)) {
            const c=f.querySelector(`[name$="[${clave}]"]`);if (c)c.value=valor;
        }
        if (datos.fecha) opciones(f);
        total();
    }
    document.getElementById('agregarRecuperacion').addEventListener('click',()=>agregar());
    lista.addEventListener('change',e=>{
        const f=e.target.closest('.recuperacion-fila');if(!f)return;
        if(e.target.matches('[data-rec-fecha]')) opciones(f);
        else if(e.target.matches('[data-rec-tipo]')) sugerir(f,true);
        total();
    });
    lista.addEventListener('click',e=>{
        if(!e.target.closest('[data-rec-quitar]'))return;
        if(lista.children.length>1)e.target.closest('.recuperacion-fila').remove();total();
    });
    lista.addEventListener('rrhh:ausencia-cambiada',total);
    // Restauré el plan completo después de un rechazo de validación del formulario.
    try {
        const previos=JSON.parse(lista.dataset.previos||'[]');
        if(Array.isArray(previos)&&previos.length) {
            const f=lista.firstElementChild;
            for(const [clave,valor]of Object.entries(previos[0])) {const c=f.querySelector(`[name$="[${clave}]"]`);if(c)c.value=valor;}
            if(previos[0].fecha)opciones(f);
            previos.slice(1).forEach(agregar);
        }
    }catch{}
    total();
});
