// Agregué filas accesibles sin insertar texto del usuario como HTML; el servidor vuelve a validar todo.
(() => {
    const form = document.querySelector('[data-notas-rubrica]');
    if (!form) return;
    const lista = form.querySelector('[data-criterios-lista]');
    const agregar = form.querySelector('[data-agregar-criterio]');
    const contador = form.querySelector('[data-criterios-contador]');
    function actualizar() {
        const filas = [...lista.querySelectorAll('[data-criterio-fila]')];
        filas.forEach((fila, i) => {
            const campo = fila.querySelector('textarea');
            const etiqueta = fila.querySelector('label');
            campo.id = 'criterio-' + i;
            etiqueta.htmlFor = campo.id;
            etiqueta.textContent = 'Criterio ' + (i + 1);
            const quitar = fila.querySelector('[data-quitar-criterio]');
            quitar.value = String(i);
            quitar.disabled = filas.length <= 1;
            quitar.setAttribute('aria-label', 'Quitar criterio ' + (i + 1));
        });
        agregar.disabled = filas.length >= 50;
        contador.textContent = filas.length + ' de 50 criterios. Cada fila tendrá un selector de nota de 1 a 7.';
    }
    agregar.addEventListener('click', event => {
        event.preventDefault();
        if (lista.children.length >= 50) return;
        const fila = lista.firstElementChild.cloneNode(true);
        fila.querySelector('textarea').value = '';
        lista.append(fila);
        actualizar();
        fila.querySelector('textarea').focus();
    });
    lista.addEventListener('click', event => {
        const quitar = event.target.closest('[data-quitar-criterio]');
        if (!quitar) return;
        event.preventDefault();
        if (lista.children.length <= 1) return;
        quitar.closest('[data-criterio-fila]').remove();
        actualizar();
        agregar.focus();
    });
    actualizar();
})();
