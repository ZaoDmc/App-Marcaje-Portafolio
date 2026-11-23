// Filtré solo candidatos ya autorizados por el servidor; buscar nunca concede pertenencia ni liderazgo.
(function () {
    'use strict';
    var panel = document.querySelector('[data-busqueda-lider]');
    var input = document.getElementById('lider-busqueda');
    var select = document.getElementById('lider-practica');
    var status = document.getElementById('lider-busqueda-estado');
    var button = document.querySelector('[data-designar-lider]');
    if (!panel || !input || !select || !status || !button || select.options.length < 2) return;
    // Ignoré tildes, mayúsculas y signos del RUT; cada palabra puede coincidir en cualquier parte del nombre/correo.
    function normalize(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }
    var placeholder = select.options[0].cloneNode(true);
    var candidates = Array.from(select.options).slice(1).map(function (option) {
        return { option: option.cloneNode(true), search: normalize(option.dataset.busqueda || option.textContent) };
    });
    var initiallyDisabled = button.disabled;
    function filter() {
        var terms = input.value.split(/\s+/).map(normalize).filter(Boolean);
        var selected = select.value;
        var matches = candidates.filter(function (candidate) {
            return terms.every(function (term) { return candidate.search.includes(term); });
        });
        // Reconstruí las opciones en lugar de ocultarlas: los desplegables nativos no ocultan opciones igual en todos los motores.
        var fragment = document.createDocumentFragment();
        fragment.appendChild(placeholder.cloneNode(true));
        matches.forEach(function (candidate) { fragment.appendChild(candidate.option.cloneNode(true)); });
        select.replaceChildren(fragment);
        select.value = matches.some(function (candidate) { return candidate.option.value === selected; }) ? selected : '';
        select.disabled = matches.length === 0;
        button.disabled = initiallyDisabled || matches.length === 0;
        status.textContent = matches.length === 0 ? 'Sin coincidencias. Prueba otro nombre, correo o RUT.'
            : matches.length + ' de ' + candidates.length + ' integrantes disponibles. Selecciona uno en la lista.';
    }
    panel.hidden = false;
    input.addEventListener('input', filter);
    filter();
})();
