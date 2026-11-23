# Organización documental del 21 de octubre de 2026

## Objetivo y alcance

Separar reglas vigentes, especificaciones editables y evidencia histórica para que una persona o IA pueda abrir únicamente el contexto necesario. Rama `organizacion-documentacion`, desde `main` en `7d2b343`.

No se modifica el comportamiento de la aplicación, el CSS, las tablas, archivos personales, `.env`, configuración del hosting o política de GitHub. Solo documentación, navegación documental y paths de pruebas que consumen inventarios. Sin commit, push ni despliegue.

## Qué cambió de lugar

| Antes dentro de docs | Ahora |
| --- | --- |
| BRANDING.md y LOGIN_DAISYUI.md | `diseno/` |
| ACTUALIZACION_PHP_LOCAL.md | `operacion/` |
| CONTEXTO_PROYECTO.md y DOCUMENTO_BBDD.md | `historial/referencias/` |
| GUIA_PRUEBAS_XAMPP.md y PRUEBAS_LOCAL.md | `historial/referencias/` |
| MIS_CAMBIOS.md y APORTES-ACOMPANAMIENTO.md | `historial/referencias/` |
| PROGRESO.md | `historial/PROGRESO.md` |
| Planes de MVC, operación y Notas | `historial/planes/` |
| Fases de configuración, rutas, MVC, Tailwind y ZIP | `historial/mvc-tailwind/` |
| Fases de correcciones, permisos y tiempo excepcional | `historial/operativa/` |
| Fases y ajustes de Notas | `historial/notas/` |
| Mejoras visuales de ADMIN | `historial/visuales/` |
| Incidente MariaDB | `historial/incidentes/` |
| Inventarios CSV de rutas, puentes y assets retirados | Junto a sus fases en `historial/mvc-tailwind/` |

Los 38 archivos reubicados corresponden a 35 Markdown y tres CSV. Se conservaron contenidos y atribuciones con enlaces recalculados; los archivos históricos incluyen aviso de alcance. No quedan copias puente en las ubicaciones anteriores. El [índice histórico](../historial/README.md) permite encontrarlos por entrega.

## Qué se consulta ahora

La entrada es [docs/README.md](../README.md), precedida por [AGENTS.md de raíz](../../AGENTS.md) para instrucciones del repositorio. `proyecto/` contiene contexto, estado y permisos; `modulos/` organiza reglas actuales y mapas de código; `diseno/` ofrece tablas para especificar medidas y textos; `arquitectura/`, `datos/`, `operacion/` y `desarrollo/` cubren el trabajo técnico.

Se conservan README de raíz y `database/README.md` por su función de presentación e inventario cercano al SQL. No se reorganizan Markdown de vendor, node_modules, scratch, dependencias ni documentos externos.

## Documentación editable y límites

Editar textos o medidas en Markdown cambia la especificación, no el software. No se introdujo un parser de Markdown, CMS o sistema de traducciones. Áreas, feriados y rúbricas se mantienen desde sus módulos de ADMIN y se almacenan en la base. La documentación distingue estos casos y señala ajustes pendientes.

## Verificación

Comprobaciones ejecutadas el 21/10/2026:

- 64 archivos Markdown revisados y 229 enlaces locales con destino existente; sin enlaces locales rotos. Se comprobaron destinos de archivo, no disponibilidad de webs externas ni todos los anchors de encabezado.
- Los 38 archivos movidos existen en su ubicación nueva y no permanecen como puentes en la anterior. Los tres CSV conservan su SHA-256; los 32 Markdown históricos conservan su texto, descontando aviso histórico y actualización de enlaces/rutas.
- Rutas: 172 comprobaciones correctas, 42 destinos. Configuración: 20. Legado/API: 374. MVC: 241. Total: 807 comprobaciones correctas, sin conexión a la base habitual.
- Sintaxis PHP correcta de las referencias ajustadas en publicación y prueba HTTP; `git diff --check` limpio.
- El ejemplo docente de DaisyUI se actualizó para no afirmar que el plugin permanece exclusivo del login. La guía de cambio local de PHP señala su fecha y remite al seguimiento de seguridad; Branding conserva sus valores y pendientes.

No se ejecutó la suite HTTP contra Apache, E2E autenticada, pruebas de base de datos ni una nueva generación de ZIP: no hubo cambios de aplicación que lo requirieran. `tests/release.php` conserva la prueba del paquete y solo cambia el path del inventario; los bytes de ese inventario se verificaron independientemente. Los comprobadores auxiliares e inventario previo de esta organización permanecen en `scratch`, fuera de Git y despliegue.

## Mantenimiento posterior

Una funcionalidad actualiza el módulo correspondiente y el Estado actual. Las decisiones sustituidas conservan procedencia en historial. Una nueva guía debe tener propósito, estado, reglas, mapa de código y comprobaciones pertinentes; no duplicar el contexto entero en cada módulo.
