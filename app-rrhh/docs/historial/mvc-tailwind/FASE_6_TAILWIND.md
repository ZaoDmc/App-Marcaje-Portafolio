> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 6: completar la migración visual a Tailwind

Rama `fase-6-migracion-tailwind`, creada desde `main` en `0ab2eef`, después de consultar origin y actualizar main por avance rápido al merge de fase 5. El árbol estaba limpio; no se modificaron commits anteriores.

## Objetivo y cambios

Completar la migración de presentación sin cambiar reglas de horas, permisos, rutas, SQL o datos. Se conservan logotipos, colores, contenidos y acciones de formularios.

| Área | Implementación |
| --- | --- |
| Recursos | Tailwind 3.4.17 compilado a `public/assets/css/tailwind.css`; se retiraron Play CDN y Bootstrap CSS/JS. Bootstrap Icons 1.11.3 conserva la iconografía y ahora se sirve con fuentes y licencia locales. Chart.js continúa como antes en los módulos que lo utilizan. |
| Compilación | `package.json`, `package-lock.json`, `tailwind.config.cjs`, fuente `resources/css/tailwind.css` y copia reproducible de iconos en `scripts/copy-icons.cjs`. Node se usa solo para desarrollo/compilación. |
| Vistas pendientes | Formularios, tablas, rejillas, tarjetas, alertas, pestañas, indicadores y barras de progreso de gestión usan utilidades Tailwind y componentes propios `rrhh-*`, sin una capa de aliases Bootstrap. |
| Vistas ya migradas | Conservan sus rejillas Tailwind y ahora cargan CSS estático. El selector de roles utiliza variantes completas de clases, evitando concatenaciones que no se compilan. |
| Estados dinámicos | `src/Views/components/ui.php` proporciona clases completas para tonos de estados y botones dinámicos. La compilación escanea PHP/JS bajo `src` y JavaScript público; no depende de descubrir clases durante la navegación. |
| Menú/cabecera | Sidebar Tailwind con apertura móvil, fondo, Escape, contención y retorno del foco, atributos ARIA y contenido principal inert mientras está abierto. Menú de cuenta con cierre exterior/Escape. |
| Permisos | Diálogos nativos con título accesible, capa superior, cierre por botón/fondo/Escape y retorno del foco. Se conservan POST, IDs de permiso, CSRF, motivo requerido y acciones aprobar/rechazar. |
| Interacciones | `ui.js` sustituye Bootstrap para desplegables, secciones de valores permitidos y cierre de alertas. Los campos «Otra» usan `hidden`; el selector propio de vistas de acompañamiento conserva su JavaScript. |
| Protección | `.htaccess` bloquea también `node_modules`, `resources` y `scripts`. Solo los recursos de `public/assets` se sirven públicamente. |

Los cambios incluyen comentarios explicativos en español. JSON no admite comentarios; sus cambios se documentan aquí. El CSS compilado y los iconos son artefactos versionados, reproducibles desde las versiones bloqueadas; no se editan manualmente. Se conserva la licencia original de los iconos.

La estructura sigue siendo un monolito PHP. No se añadió una SPA ni se trasladaron reglas de negocio a JavaScript. Los componentes propios reutilizan `@apply` de Tailwind para evitar repetir estilos de controles en todas las páginas.

## Desarrollo y compilación

```powershell
npm ci --ignore-scripts
npm run build
```

`npm run build` compila Tailwind y copia únicamente CSS, dos fuentes y licencia de Bootstrap Icons. Las dependencias de desarrollo bloqueadas son Tailwind 3.4.17, Bootstrap Icons 1.11.3 y Playwright 1.62.1 para pruebas. No se ejecutan scripts de instalación ni se descargan navegadores: las pruebas usan Edge instalado por defecto.

Tailwind 3.4.17 conserva la sintaxis de las vistas existentes. La compilación y el tratamiento de clases completas siguen la [instalación oficial con CLI](https://v3.tailwindcss.com/docs/installation) y la [configuración de contenido](https://v3.tailwindcss.com/docs/content-configuration#dynamic-class-names). No se incorpora una actualización mayor de Tailwind en esta fase.

El CSS generado se versiona y se publica junto con `public/assets/js/ui.js` y `public/assets/icons`. Hostinger no necesita npm, Node, `resources`, `scripts` o `node_modules` para ejecutar el portal. `node_modules` queda ignorado por Git.

## Validación

```powershell
$env:PHP_BINARY = 'C:\xampp\php 8.3.35\php.exe'
npm run test:ui
& $env:PHP_BINARY tests/mvc.php
& $env:PHP_BINARY tests/business.php
& $env:PHP_BINARY tests/configuration.php
& $env:PHP_BINARY tests/routing.php
& $env:PHP_BINARY tests/legacy-removal.php
```

Las siete pruebas de navegador usan Edge sin ventana, procesos PHP con datos sintéticos y todas las solicitudes interceptadas para servir únicamente recursos locales. No se envían formularios ni se autentican usuarios reales. `UI_CHANNEL` permite seleccionar otro canal instalado o `UI_BROWSER` una ruta de navegador compatible.

Se revisan 25 pantallas/variantes a 1280 y 390 píxeles: trece entradas compartidas por rol, cinco de autenticación, perfil, marcaje, historial, permisos y panel del practicante, acompañamiento y 404. Se generan 50 capturas bajo `scratch/phase6/screenshots`, ignoradas por Git. Se comprueban desbordes, recursos/JavaScript, colores compilados, modales y formularios, foco, menú móvil, retorno a escritorio, desplegables, campos «Otra» y rejilla de selección de rol.

La revisión visual incluyó capturas de permisos en escritorio, alta de practicantes en móvil, login y marcaje móvil. Se ajustaron el contraste del indicador de sábado y la captura del menú cerrado tras cambiar de tamaño; el menú respeta movimiento reducido.

También pasan 199 comprobaciones MVC, 100 de negocio, 20 de configuración, 162 de rutas y 343 de retiro/API. Las dos comprobaciones adicionales frente a fase 5 provienen del recorrido de los nuevos componentes PHP en el análisis de dependencias.

Apache temporal pasó 220 comprobaciones en `/asistencia` y `public` como raíz: rutas, autorización anónima, POST/CSRF, HEAD, métodos, protección de fuentes de compilación y disponibilidad/integridad de CSS, JavaScript e iconos locales. El servidor temporal se cerró al finalizar.

## Límites y publicación

Las pruebas visuales utilizan datos controlados. No sustituyen la revisión con listados extensos, documentos reales, gráficas con datos, envío SMTP, escrituras de negocio o navegadores distintos de Edge. Los diálogos y la gestión del foco requieren un navegador moderno compatible con `dialog` e `inert`.

Para probar en XAMPP, abrir `http://localhost/asistencia/` con Apache y MySQL iniciados y recorrer los perfiles habituales. Si el navegador conserva estilos antiguos, recargar sin caché. No se modificaron `.env`, uploads, base de datos o configuración de XAMPP.

La publicación sigue requiriendo backend y entrada pública coordinados, como establece fase 5. Incluir todos los recursos nuevos compilados; no subir únicamente el ZIP del backend ni sustituir uploads/.env. La preparación del paquete, conciliación de copias antiguas de `assets` y verificación de archivos productivos corresponden a fase 7. No se modificó Hostinger ni se hizo commit/push/despliegue.

Las nuevas reglas de compensación dentro de dos días y recuperación distribuida de permisos continúan pendientes de definición e implementación; esta migración no las activa.
