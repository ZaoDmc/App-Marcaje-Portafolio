# Registro de versiones en producción

## Estado conocido al iniciar el versionado

| Dato | Evidencia |
| --- | --- |
| Proyecto | ePrácticas RRHH |
| Sitio | https://rrhh.epracticas.cl |
| Rama de publicación | prod |
| Último SHA consultado | c1a7178eb295fb80a027ad8ed702c7a4e337b4f5 |
| Fecha de esa consulta | 26/10/2026, por SSH; árbol limpio |
| Publicación de esa revisión registrada | 23/10/2026, 15:23:50 America/Santiago |
| Versión explícita mostrada en esa revisión | Sin indicador; anterior a esta implementación |
| Base de numeración declarada por el responsable | 1.1.1 el 27/10/2026 |
| Versión preparada con indicador | 1.1.1; pendiente de desplegar y verificar |

La base declarada no acredita que Hostinger ya sirva el archivo de versión ni este login. Esta tarea no consultó de nuevo ni modificó producción. El estado SMTP comunicado el 26/10 se documenta aparte en [Correo](CORREO_SMTP.md).

## Registrar una publicación

Después de un despliegue autorizado, leer la versión del checkout efectivo y comprobar el login servido. Se puede consultar la versión por CLI desde la raíz del checkout con `php -r 'echo require "config/version.php";'`, usando el ejecutable PHP apropiado. Obtener el SHA con `git rev-parse HEAD` y comprobar `git status --short`. Una revisión con modificaciones locales no identifica por sí sola exactamente el código servido; registrarlo como discrepancia y resolverla.

Actualizar el estado conocido y añadir una entrada al historial con estos campos:

| Campo | Valor que debe completar el operador |
| --- | --- |
| Fecha y hora | Fecha real, hora y zona de la publicación |
| Versión publicada | Número comprobado en el código servido |
| Revisión | SHA completo; rama/ref de origen |
| Operador | Persona responsable o identificador acordado |
| Entrega | PR o referencia de la revisión aprobada |
| Validación | Login, recursos y funciones afectadas comprobadas |
| Datos y dependencias | Parches aplicados, Composer y observaciones |
| Respaldo y reversión | Referencia privada, sin secretos ni dumps |
| Resultado | Publicado, revertido o fallido; explicación breve |

Este registro es documental. No cambia el número del software, no ejecuta despliegues y puede actualizarse en un PR documental posterior a la operación, sin incrementar la versión del código.

## Historial

- **23/10/2026 15:23:50 America/Santiago:** publicado prod c1a7178, previo al indicador de versión. [Evidencias de la migración](MIGRACION_GIT_20261023.md). No se le atribuye una etiqueta Git retroactiva.
- **27/10/2026:** se declara 1.1.1 como base de numeración y se prepara el indicador local. Pendiente de publicación; no constituye un nuevo despliegue.
