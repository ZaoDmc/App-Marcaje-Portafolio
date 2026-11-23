// Agregué copia reproducible de iconos y licencia desde la versión bloqueada.
// Solo publica estos cuatro archivos; no copia node_modules al directorio público.
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const source = path.join(root, 'node_modules', 'bootstrap-icons');
const target = path.join(root, 'public', 'assets', 'icons');
fs.mkdirSync(path.join(target, 'fonts'), { recursive: true });
for (const [from, to] of [
    ['font/bootstrap-icons.min.css', 'bootstrap-icons.min.css'],
    ['font/fonts/bootstrap-icons.woff', 'fonts/bootstrap-icons.woff'],
    ['font/fonts/bootstrap-icons.woff2', 'fonts/bootstrap-icons.woff2'],
    ['LICENSE', 'LICENSE']
]) fs.copyFileSync(path.join(source, from), path.join(target, to));

// Prueba Lucide: copio solo los SVG que usa la interfaz (no la librería completa) más su licencia ISC.
const lucideSource = path.join(root, 'node_modules', 'lucide-static');
const lucideTarget = path.join(target, 'lucide');
const lucideIconos = ['layers', 'hourglass', 'circle-check', 'octagon-x', 'check', 'x',
    'circle-alert', 'funnel', 'rotate-ccw', 'clock', 'mail', 'inbox', 'info', 'arrow-right', 'file-search', 'calendar'];
fs.mkdirSync(lucideTarget, { recursive: true });
for (const nombre of lucideIconos) {
    fs.copyFileSync(path.join(lucideSource, 'icons', nombre + '.svg'), path.join(lucideTarget, nombre + '.svg'));
}
fs.copyFileSync(path.join(lucideSource, 'LICENSE'), path.join(lucideTarget, 'LICENSE'));
