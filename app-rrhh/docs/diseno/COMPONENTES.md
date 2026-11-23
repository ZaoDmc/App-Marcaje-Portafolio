# Componentes visuales y temas

## Especificación y código

[Branding](BRANDING.md) contiene los valores visuales acordados en formato humano. No es un generador de CSS: cambiar allí una medida requiere implementarla y verificarla. Las reglas aún marcadas Pendiente no se consideran aprobadas. La unificación total entre especificación y CSS sigue como alcance por revisar.

| Pieza | Ubicación y responsabilidad |
| --- | --- |
| Utilidades y componentes compartidos | `resources/css/tailwind.css`; clases `rrhh-card`, `rrhh-button`, `rrhh-input`, `rrhh-select`, `rrhh-table`, entre otras |
| Temas del portal | `public/assets/css/practicante-temas.css`; pese al nombre histórico, da soporte visual a los tres roles |
| Estilos del login | `resources/css/login.css`, `tailwind.login.config.cjs` y vista de login |
| Configuración de compilación | `tailwind.config.cjs`, `tailwind.login.config.cjs` y `package.json` |
| HTML compartido | `src/Views/layout/` y `src/Views/components/` |
| CSS generado | `public/assets/css/tailwind.css` y `public/assets/css/login.css` |
| Iconos | Bootstrap Icons como recursos locales; no implica cargar Bootstrap CSS/JS. En Permisos, Lucide en línea con `icono('nombre')` (`src/Helpers/helpers.php`); `scripts/copy-icons.cjs` copia solo los SVG listados a `public/assets/icons/lucide/` |
| Botones | `rrhh-button` + variante `rrhh-btn-primary`, `-secondary`, `-success`, `-danger`, `-success-soft`, `-danger-soft` y tamaño `rrhh-btn-sm`, en `practicante-temas.css`; valores propios para Claro y Black; los demás temas heredan su color principal. Aplicado en Permisos |
| Horas | Campos de texto con `data-hora24` y `pattern` 24 h; `ui.js` aplica la máscara y completa al salir. No usar `type="time"` (muestra AM/PM según el navegador) |

`practicante-temas.css` es un archivo fuente mantenido directamente; los dos CSS generados se regeneran desde sus fuentes/configuración. No editar únicamente el minificado ni duplicar assets en raíz.

## Temas

El portal ofrece light, dark, synthwave, aqua, coffee y black; selección en Mis datos para PRACTICANTE y muestras de color en el header de ADMIN/SUPERVISOR. La preferencia se conserva por navegador, no como una configuración global del usuario en la base. Login tiene su selector claro/oscuro independiente.

Usar superficies y textos semánticos del tema; evitar fondos blancos fijos en temas oscuros. Revisar también inputs nativos, select, opciones, autofill, disabled, hover, foco y bordes. Mantener intensidad moderada de bordes, iconos coherentes y textos legibles. El color del logo conserva la variante aprobada.

No asumir compatibilidad solo porque se vea bien en un navegador. Hubo diferencias reportadas en Opera GX: comprobar estilos de controles nativos y caché además de las clases. El progreso circular combina el componente visual con SVG compatible; no volver a depender únicamente de un truco CSS sin comprobarlo.

## DaisyUI y compilación

Se usa DaisyUI 4.12.24 con prefijo `dui-` y Tailwind 3.4.17. Copiar un ejemplo externo requiere adaptar las clases al prefijo, al ámbito compilado y a la paleta; no añadir una CDN o una segunda versión para lograr que aparezca. Guía de aprendizaje: [Login con DaisyUI](LOGIN_DAISYUI.md).

Desde la raíz del repositorio:

```text
npm run build:css:login
npm run build:css:portal
```

Ejecutar el comando que corresponda al cambio; `npm run build` prepara todos los recursos e iconos. Las dependencias se instalan con el lock existente cuando falten, no se actualizan para una edición de texto. Hostinger recibe los recursos compilados y no necesita Node.

## Cómo cambiar una medida

El flujo de colación utiliza `src/Views/components/cambio-colacion.php`, superficies `rrhh-card`, controles semánticos y diálogos `rrhh-solicitud-dialog` ya existentes. Se verificaron Coffee, 390/1280 px y tres roles con fixtures locales; no se envían formularios reales en el ensayo visual.

En Permisos de ADMIN/SUPERVISOR, las categorías de tiempo y compensación usan `src/Views/shared/permisos-tiempo.view.php`, `rrhh-table` y los diálogos `rrhh-solicitud-dialog` con `data-ui-open`/`data-ui-close`. Reutilizan `ui.js` y las superficies del tema, sin una segunda biblioteca de modales. El componente excepcional recibe un título único por diálogo y oculta la revisión del reconocimiento al consultar un canje. CSS del portal recompilado; pruebas Edge con fixtures, Coffee a 390/1280 px para ambos roles. No acredita otros navegadores ni todas las paletas.

1. Editar su fila en Branding, por ejemplo Tamaño de tarjetas → Relleno interior en móvil.
2. Identificar el componente compartido o la excepción de pantalla que usa esa medida.
3. Aplicar el valor sin alterar reglas de negocio, compilar si corresponde y revisar los seis temas y móvil/escritorio relevantes.
4. Registrar la implementación y cualquier diferencia pendiente en [Estado](../proyecto/ESTADO_ACTUAL.md).

La migración histórica de Bootstrap queda en [Historial](../historial/README.md). Sus recomendaciones de estilos antiguos no prevalecen sobre esta guía.
