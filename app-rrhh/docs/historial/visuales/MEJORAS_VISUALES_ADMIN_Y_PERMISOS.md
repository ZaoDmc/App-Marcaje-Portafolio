> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Temas de ADMIN y permiso parcial

Fecha: 2026-10-20. Rama `Mejoras-visuales-vista-admin`, creada desde `main` en `e262781`. Git no admite espacios en el nombre solicitado. Cambios locales, sin commit, push o despliegue.

## Problema y solución

El dashboard ADMIN utiliza componentes propios (`dashboard-filters`, `kpi-card`, `dashboard-panel` y `capabilities-strip`), con variables `--dashboard-*` independientes y varios colores blancos fijos. El tema general ya cubría las tarjetas Tailwind compartidas de otros módulos, pero no estas superficies.

En `public/assets/css/practicante-temas.css` vinculé esas variables a las paletas existentes y ajusté las superficies de filtros, estados neutros, textos, bordes, enlaces y controles del dashboard. Las reglas específicas de ADMIN se aplican a temas oscuros, conservando el diseño de Claro. El tema Black mantiene contraste en botones e iconos. Las reglas se limitan a componentes concretos; no se cambian espaciados, distribución, métricas, Chart.js ni permisos de acceso.

El permiso parcial usaba `bg-amber-50/70` y su resumen `bg-amber-100/70`. Estos colores translúcidos no estaban cubiertos por los ajustes de tema y dejaban un fondo claro al abrir el tramo. En `src/Views/practicante/permisos.view.php` los sustituí por clases semánticas del panel y del resumen. Sus fondos, textos y bordes ahora proceden del tema; títulos y explicación mantienen el color de contenido correspondiente. El resumen conserva una superficie diferenciada. No se modifican horarios, cálculos o envío del formulario.

Recompilé `public/assets/css/tailwind.css` para mantener el artefacto consistente con las clases utilizadas. El CSS de temas ya tiene versión por contenido en el layout; no se modifica `.env` ni se agregan migraciones SQL.

## Verificación

- Copia E2E aislada: `scratch/phase8/9799ce1dbc96`, con usuarios ficticios y base aleatoria. No se enviaron solicitudes de permiso ni se modificaron datos reales.
- Revisión visual temporal con Edge/Playwright: seis temas (`light`, `dark`, `synthwave`, `aqua`, `coffee`, `black`), anchos de 320/1280 px y acceso con ADMIN, PRACTICANTE y SUPERVISOR. Se contrastaron 254 superficies con las variables de paleta y se abrió el permiso parcial con horas válidas y resumen visible. No se afirma una validación directa en Opera GX.
- Capturas `admin-<tema>-<ancho>.png` y `parcial-<tema>-<ancho>.png`, junto con `qa-temas-results.json`, dentro de la fixture ignorada por Git. Inspección visual de dashboard Coffee y permiso parcial Black.
- Los recursos externos se bloquearon durante la revisión visual. Se comprobó la superficie alternativa de gráficos; no se certifica el renderizado de Chart.js, que no se modificó.
- Build del CSS correcto, sintaxis PHP y `git diff --check` correctos. Persiste el aviso previo de Browserslist desactualizado. No se ejecutó la suite completa de negocio por tratarse de cambios visuales.

Para revisar manualmente: seleccionar los cuadrados de tema en ADMIN y abrir Dashboard; comprobar filtros, KPI, paneles, tablas y acciones. En PRACTICANTE seleccionar el tema desde Mis datos, ir a Permisos, escoger un día con jornada, elegir Solo un tramo e ingresar horas válidas. El panel y el resumen deben respetar la paleta y mantener texto legible.

## Pull request

### Ajustes adicionales: login y foto de perfil

Revisión posterior solicitada por el responsable: el login Claro utiliza ahora campos grises (#E2E8F0), texto oscuro (#0F172A) y placeholders #475569. Su logo es la variante negra/roja `logo_rojo_negro.png`, creada mediante imagegen desde la imagen existente y guardada en el repositorio, sobre fondo blanco; Oscuro conserva la versión de letras blancas. Esto sustituye los ajustes anteriores de base azul y campos oscuros descritos como historial más abajo. Se actualizaron las reglas de BRANDING con la excepción autorizada. QA temporal Edge: 16 comprobaciones correctas, ambos modos y 320/1280 px, sin base de datos ni formularios enviados; capturas actualizadas en scratch/qa-login-foto.

El login utiliza `public/assets/brand/logo_rojo_blanco.png` sobre una base azul institucional en claro y oscuro, en lugar del JPG con fondo blanco. La ruta pública del repositorio está bajo `public/assets/brand/`, no `public/brand/`.

En la vista local de Mis datos permanecía `object-cover`, que recorta imágenes para llenar el marco. Se reemplazó por `object-contain object-center`, con superficie adaptada al tema y texto «Recomendado: 500 × 500 px (formato cuadrado)». Es una recomendación; no se impone un tamaño de subida ni se cambian archivos almacenados. Se conserva JPG/PNG y máximo de 5 MB.

Se recompilaron portal/login y se hicieron 12 comprobaciones visuales temporales sin base de datos, en Edge a 320/1280 px, con ambos modos e imágenes ficticias verticales/horizontales. Evidencia: `scratch/qa-login-foto/resultado.json` y capturas. No se contrastó el código desplegado en Hostinger; el diagnóstico del recorte corresponde al repositorio local.

Título: **Corrige los temas del dashboard ADMIN y del permiso parcial**

Descripción:

Se adaptan las superficies, filtros, bordes y textos del dashboard ADMIN a los temas disponibles, eliminando fondos blancos fijos en las paletas oscuras. El formulario de permiso parcial y su resumen utilizan fondos y textos del tema seleccionado, conservando la distinción visual entre ambos paneles.

Incluye comentarios explicativos, CSS compilado y documentación. Verificación visual en seis temas, móvil/escritorio y tres roles mediante una copia aislada. No cambia cálculos, permisos, base de datos o configuración de producción.
