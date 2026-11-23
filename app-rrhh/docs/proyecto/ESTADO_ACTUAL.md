# Estado actual y pendientes

Fecha de referencia: **23 de noviembre de 2026**. Describe el repositorio y los hechos comunicados; no certifica sincronización ni despliegue de cada commit en Hostinger.

## Implementado en el repositorio

| Área | Estado actual |
| --- | --- |
| Arquitectura | Front controller, catálogo de rutas y MVC; puentes antiguos retirados |
| Presentación | Tailwind y componentes DaisyUI seleccionados; temas para los tres roles |
| Operación | Marcajes con confirmación, revisión/corrección diferenciadas y acumulados con horas previas |
| Permisos | Mismo día, ausencias intermedias y planes de recuperación distribuidos |
| Calendario | Feriados comunes administrados por ADMIN y validación especial de recuperaciones |
| Tiempo excepcional | Solicitud, aprobación, reserva, compensación y vencimiento; extras históricas preservadas |
| Notas | Áreas/líderes, rúbrica versionada, borradores, publicación y correcciones auditadas |
| Acceso a Notas | Todos los practicantes consultan las propias; líderes evalúan su área; selector ADMIN muestra líderes |
| Reseñas | Captura de testimonios, calificación en estrellas y canal; custodia de autorización legal en PDF y supervisión |
| Entrega | Hostinger sirve `prod` desde checkout privado por enlace a `public/`; actualización por SSH, sin GitHub Actions; ZIP conservado como herramienta |

## Pendientes confirmados o límites

- El cliente debe entregar la rúbrica académica definitiva. Los rótulos provisionales y la nota final manual no implican una fórmula de promedio o ponderación.
- No se ha definido una nota global automática de cierre de práctica ni cómo evaluar semanas parciales sin viernes dentro de la vigencia. No inventarlas.
- Branding tiene ajustes marcados **Pendiente**. Sus medidas aprobadas son especificación; no se ha certificado que todo el CSS existente las cumpla de forma uniforme.
- PHP 8.3.35 es la versión productiva reportada. La aplicación del parche por Hostinger para el hallazgo del 21/10/2026 está pendiente de evidencia; ver [Seguridad](../operacion/SEGURIDAD.md).
- El incidente local de MariaDB tiene documentación de recuperación. No eliminar carpetas de datos/rescate basándose en su nombre; consultar [incidente](../historial/incidentes/INCIDENTE_MYSQL_LOCAL_20261019.md).
- Las pruebas documentadas se ejecutaron en copias ficticias locales; no demuestran compatibilidad de todos los navegadores, concurrencia de todos los flujos ni estado productivo.
- **Formulario validación** se incorporó en 1.2.0 y forma parte de la entrega de desarrollo actual **1.4.1**, con correcciones de URL, confirmación y horario variable. Sin evidencia de publicación del módulo. La publicación requiere aplicar el parche 012 donde falte, configurar centro/beneficios y sugerencias por carrera y comprobar `ext-zip` en el PHP web de destino. El selector muestra las instituciones activas del catálogo; Duoc UC es la única habilitada. Las pruebas usan DOCX sintéticos; no acreditan el formato visual de todas las versiones de Word ni el entorno productivo. Ver [especificación](../modulos/FORMULARIO_VALIDACION.md).

## Registro reciente

### 2026-11-23 Consolidación operativa para producción (sin MFA) — 2.1.9

Se prepara la rama `release-operacion-2.1.9` sobre `prod` (`2.1.5`, hotfix `fix-sabado`) integrando las entregas operativas `2.1.5`–`2.1.8` (márgenes de 15 min y auto-cierre, mejoras visuales de Asistencia, meta 100% con confeti y descuento de colación en recuperaciones sabatinas `>= 6 h`) junto con tres mejoras compatibles sin dependencias ni migraciones SQL:

- **Modo solo lectura al 100% o egreso:** evaluación dinámica en servidor (`asistencia_estado_solo_lectura_practicante()`) que bloquea marcajes, nuevas solicitudes de permiso y edición de datos personales al completar la meta o finalizar la práctica, manteniendo historial, tarjeta de meta cumplida y **Reseñas** habilitadas.
- **Validación POST de ventana horaria:** condición corregida (`!$evaluacionVentanaPost['habilitado']`) en `MarcarAsistenciaController.php`.
- **Truncamiento al minuto en cruces de permisos parciales:** `asistencia_permiso_cruza_marcas()` trunca al minuto (`:00`) en ausencias `PARCIAL` para evitar falsos cruces por segundos ocultos.
- **Sin cambios de BD, `.env` ni Composer:** MFA (`2.2.0`), documentos en permisos (`2.2.1`), reseñas (`2.2.2`), FAQ (`2.3.0`) y políticas de contraseña permanecen en `dev` para la próxima ventana.

### 2026-11-13 Descuento de colación y autoajuste en recuperación sabatina — 2.1.8

Se corrige el cálculo del plan de recuperación de permisos en `src/Services/RecuperacionesService.php` (`recuperaciones_minutos_netos_tramo`), `public/assets/js/recuperaciones.js` y `src/Views/practicante/permisos.view.php`. Los tramos `SABADO` y `FERIADO` de 6 horas o más (`>= 360` min brutos) descuentan automáticamente 1 hora de colación en solicitudes nuevas (`10:00–20:00` = `540 min`, `10:00–18:00` = `420 min`), conservando los `minutos_planificados` históricos al revisar. Además, el editor sugiere automáticamente la `Hora término` equivalente a los minutos de ausencia laboral y actualiza el bloque `#badgeHorasCalculadas` con todo el texto a la izquierda y el icono de estado a la derecha. Validado con `491` pruebas de negocio (`tests/recuperaciones-sabados.php` ampliado), `289` MVC, `18` de jornada laboral y verificación en Chromium. Incremento a `2.1.8`, PARCHE.

### 2026-11-13 Indicador de meta completada al 100% con confeti — 2.1.7

Se incorpora en la vista de asistencia del practicante (`src/Views/practicante/marcar-asistencia.view.php`) el estado visual de meta cumplida al alcanzar el 100% de las horas asignadas: anillo SVG en verde éxito, insignia `100%`, mensaje de felicitación y botón de repetición de celebración. En `public/assets/js/ui.js` se añade una ráfaga ligera de confeti en `<canvas>` (`600 ms` de pausa inicial, `2500 ms` de dispersión, disparo único por sesión en `sessionStorage`, respeto a `prefers-reduced-motion` y límite de 5 pulsaciones con enfriamiento de 2 minutos). Estilos adaptados a temas claro y `Black` en `public/assets/css/practicante-temas.css`. Validado con `489` pruebas de negocio, `289` MVC y verificación en Chromium. Incremento a `2.1.7`, PARCHE.

### 2026-11-12 Rediseño de filtros y armonización visual de Asistencia — 2.1.6

Se armoniza la interfaz del módulo de asistencia (`/supervisor/asistencia.php`) bajo el estándar de diseño y las buenas prácticas UX/UI de Permisos y Practicantes.

- **Barra de filtros segmentada:** Reemplazo de la grilla de botones extendidos por una cápsula segmentada inline con bordes redondeados (`rounded-2xl`), iconos semánticos temáticos, etiquetas compactas y badges de conteo circular para cada estado operativo (Todos, Válidas, Observadas, Incompletas y Corregidas).
- **Botón «Limpiar filtros» condicional y visible:** Rediseño del botón con fondo sólido claro (`bg-slate-100/90 hover:bg-slate-200`), borde sutil y glifo de reinicio. Se corrige la lógica para que permanezca oculto en el estado inicial «Todos» y solo aparezca cuando realmente existan filtros restrictivos activos.
- **Formulario de bloques de área en modal:** Corrección de la estructura a 12 columnas (`md:grid-cols-12`) con altura uniforme `!h-10`, campos proporcionados para Área, Inicio (fecha y hora contiguas), Fin y botón Agregar, eliminando líneas desbordadas y deformaciones.
- **Ficha emergente sin salto de layout (Zero Layout Shift):** Altura fijada a 704 px (`min(44rem)`) con scroll interno suave en el cuerpo del diálogo, garantizando estabilidad visual al navegar entre Resumen, Marcas, Bloques y Acciones.
- **Responsive y modo oscuro:** Reorganización fluida (*flex-wrap*) en pantallas móviles de 390 px y soporte verificado en tema oscuro Black / Alto contraste.
- **Validación y reporte ejecutivo:** 489 pruebas de negocio, 289 MVC y 18 unitarias de jornada laboral pasadas con 100% de éxito (0 fallos). Generación del reporte ejecutivo visual en Word (`docs/reporte-mejoras-visuales-asistencia.docx`) mediante WordCraft. Incremento a 2.1.6, PARCHE.
### 2026-11-11 Margen de anticipación de 15 min y auto-cierre por inactividad — 2.1.5

Se incorpora en la pantalla de asistencia del practicante (`src/Views/practicante/marcar-asistencia.view.php`), en el controlador (`src/Controllers/Practicante/MarcarAsistenciaController.php`) y en el servicio de reglas (`src/Services/ReglasAsistenciaService.php`) el control estricto de margen de anticipación (15 minutos antes de la hora esperada) y los cierres automáticos por inactividad (auto-retorno de colación a los 15 minutos y auto-salida de jornada a los 30 minutos). Validado con 35 comprobaciones unitarias en `tests/regla-margenes-asistencia.php`.

### 2026-11-11 Recuperación de integración probada de dev en prod — 2.1.4

El responsable confirmó utilizar `dev` local `41b34a3` como referencia probada. Al actualizar las referencias, `origin/dev` estaba en `af09c6f` y ya era antecesor de `origin/prod` `62eb22d`: repetir el merge no recuperaba las resoluciones perdidas. Se preparó `corregir-integracion-dev-prod` desde ese prod y se restauraron los 13 archivos funcionales, de esquema, pruebas y documentación que diferían respecto de la referencia; no se reescribe el historial ni se descartan entregas 2.1.2/2.1.3.

Se reprodujo localmente, sobre el archivo del commit `62eb22d`, el fatal de PHP `Cannot redeclare recuperaciones_opciones_fecha()` por las declaraciones en las líneas 21 y 98. La restauración recupera el servicio coherente, los controles sabatinos y el parche 015 eliminado en la integración. Incremento 2.1.3 → 2.1.4, PARCHE. No se cambia la base habitual, el `.env`, uploads ni Hostinger; confirmar migraciones pendientes, particularmente 015/016, al publicar. El responsable informó el SHA productivo `62eb22d` y error 500 en la ruta del supervisor; no se ha certificado el restablecimiento del servicio web.

Validación de la corrección: 489 comprobaciones de negocio, 289 MVC, 180 rutas y 21 de configuración correctas; sintaxis correcta en 202 archivos PHP y revisión de whitespace sin errores. Los archivos de aplicación, recursos, esquema y pruebas recuperados coinciden con la referencia `41b34a3`; la versión y los registros documentales describen esta nueva entrega. No se ejecutó `tests/regla-jornada-laboral.php` porque utiliza la base habitual y la práctica 2; no se usaron esos datos para validar. Sin migraciones ejecutadas ni comprobación de login productivo.

### 2026-11-10 Regla de jornada laboral y marcaje sabatino con recuperación — 2.1.3

Se implementa en la pantalla de marcaje del practicante (`/practicante/marcar-asistencia.php`) y en el servicio central de asistencia (`src/Services/ReglasAsistenciaService.php`) el control estricto de habilitación en jornada laboral.

- **Reglas de jornada:** Bloqueo absoluto de marcajes los domingos y días de semana sin turno pactado. Los sábados quedan bloqueados por defecto, habilitándose única y exclusivamente cuando el practicante cuenta con una solicitud de permiso aprobada con tramo de recuperación programado para esa fecha, adaptando el horario del día a los límites autorizados.
- **Tolerancia y margen de 5 días hábiles:** Incorporación en `src/Services/RecuperacionesService.php` del helper `recuperaciones_calcular_plazo_aprobacion()`, permitiendo al supervisor regularizar aprobaciones tardías sin expiración prematura.
- **Avisos de UX en supervisión:** En `/supervisor/permisos.php` se incorporan insignias contextuales (`⚡ Por aprobar este sábado` para solicitudes inminentes y `⏳ Aprobación tardía` con contador de días hábiles restantes).
- **Simulación local segura:** Soporte exclusivo en desarrollo (`APP_DEBUG = true`) del parámetro `?simular_fecha=YYYY-MM-DD` para verificación integral sin alterar el reloj del servidor.
- **Esquema:** Sincronización de la tabla `resenas_practica` en `database/schema.sql`.
- **Validación:** 489 pruebas de negocio, 180 de enrutamiento, 289 MVC y 18 pruebas unitarias de jornada laboral (`tests/regla-jornada-laboral.php`) correctas al 100%. Pruebas interactivas en navegador local. Incremento a 2.1.3, PARCHE.

### 2026-11-10 Modificación de horas de práctica en Reportes — 2.1.2

Se incorpora en la ficha de detalle de practicantes dentro de Reportes (`/admin/reportes.php` y `/supervisor/reportes.php`) la capacidad para que ADMIN y SUPERVISOR actualicen las horas requeridas y las horas acumuladas históricas con tope máximo estricto de 1.000 horas.

- **Flujo y validación:** Botón «Modificar horas» en la cabecera de la ficha que abre un diálogo emergente nativo. Valida en cliente y servidor que las horas requeridas estén entre 1 y 1.000, y las acumuladas entre 0 y 1.000.
- **Persistencia:** Se ajustan proporcionalmente las `horas_previas` en la tabla `practicas` para reflejar con exactitud las horas acumuladas deseadas sin manipular ni alterar el historial de jornadas de asistencia reales.
- **Seguridad y trazabilidad:** Validación CSRF (`csrf_reportes_horas`), redirección limpia mediante PRG con mensaje de confirmación y registro en auditoría funcional bajo el evento `MODIFICAR_HORAS_PRACTICA` (capturando actor, rol, valores anteriores y nuevos). Además, tanto la ficha del practicante como el modal de modificación despliegan la información de la última edición (responsable, correo, rol, fecha y hora).
- **Validación:** 489 pruebas de negocio, 180 de enrutamiento y 21 de configuración correctas sin fallos. Prueba funcional directa en navegador como supervisor.

### 2026-11-09 Columna de recuperación y validación interactiva — 2.1.1

Resultado final: 489 comprobaciones de negocio, 289 MVC, 180 rutas y 21 de configuración correctas. Sintaxis, compilación CSS (`npm run build`) y diff limpios.

Rama `mejoras-visuales`. Se incorpora columna «Recuperación» en la tabla de Permisos de ADMIN y SUPERVISOR con información de tramos, fecha y horas acreditadas. Botón directo «Validar asistencia cumplida» en un clic con motivo automático («Jornada de recuperación validada conforme a marcas registradas») y validación interactiva vía AJAX que actualiza estado, saldo y celda sin recargar la página ni perder filtros activos (soporte oficial de `limite=20`). Microinteracciones con retardo perceptible de 450 ms, animación de resorte, trazado SVG acotado a 14 px y aviso flotante no intrusivo. Enlace directo «Ver marcas →» para ausencias que cruzan marcas de asistencia. Sin cambios en la estructura de base de datos (`database/schema.sql` vigente). Incremento 2.1.0 → 2.1.1, PARCHE.

### 2026-11-06 Cambio diario de colación — 2.1.0

Resultado final: 489 comprobaciones de negocio (33 nuevas), 289 MVC, 21 de configuración, 3 de migración MariaDB ficticia y un test Edge con seis recorridos de rol/ancho correctos. Sintaxis y diff correctos; compilación del CSS ejecutada sin diferencia en el archivo generado.

Rama `permisos-cambio-colacion` desde dev limpio `6b5da8c`. Practicante solicita para hoy 13–14 / 14–15 con motivo; revisión ADMIN/SUPERVISOR propia por categoría y diálogo, incluso posterior al término de jornada. Superposición por fecha y firma de horario, sin modificar horario semanal ni marcas; recálculo y auditoría en una transacción. Cruces con ausencias/recuperaciones/canjes y autosupervisión bloqueados. El rechazo permite otra solicitud conservando historial. Se informa cuando un horario manual posterior deja sin aplicación el ajuste previo. Parche 016 y schema, sin aplicación a base habitual ni producción. 2.0.0 → 2.1.0, MENOR. Pruebas de negocio SQLite, MVC, configuración, migración MariaDB ficticia y seis recorridos Edge Coffee para tres roles a 390/1280 px; capturas revisadas. Sin envío de formularios reales, certificación de concurrencia InnoDB ni de todas las paletas/navegadores. Sin commit ni push. Las siguientes entregas siguen siendo retirada de nuevos reconocimientos excepcionales y rechazo de marcajes sin jornada.

### 2026-11-06 Recuperación nueva solo sábados — 2.0.0

Prueba visual adicional Edge con fixture local sin enviar formularios: móvil 390 px y escritorio 1280 px en Coffee, instrucciones de corte y ausencia de opciones COLACION/EXTENSION en el formulario nuevo. Capturas revisadas. No certifica otros navegadores o todos los temas.

Rama `permisos-recuperacion-sabados` desde dev limpio `0cf8811`. Solicitudes nuevas y adicionales restringidas a sábados 10:00–20:00, con corte viernes anterior 12:00 Chile; 12:00 exactas se rechaza y puede elegirse el sábado siguiente. Se audita el intento tardío sin crear solicitud/reserva. La fecha UTC original se persiste por tramo y permite aprobación posterior al corte o al trabajo realizado. Los tramos anteriores conservan política 1, modalidades y créditos; sin conversión destructiva. Parche 015 y schema actualizados. Se mantienen controles de jornada ordinaria, cruces, saldo, marcas y autorización especial de sábados feriados. Pasaron 456 pruebas de negocio (21 nuevas), 276 MVC, 21 de configuración y 4 de migración MariaDB ficticia (repetición, preservación de datos y campos de schema). Sin aplicación a datos reales o producción, commit ni push. 1.7.0 → 2.0.0, MAYOR por retirar opciones de uso antes aceptadas en solicitudes nuevas. Todavía no implementa cambio de colación, retirada de nuevos créditos excepcionales ni bloqueo de marcajes: siguen separados según el plan rectificado.

### 2026-11-06 Rectificación de política por el superior — planificación

Confirmado: recuperación únicamente sábados 10:00–20:00 con envío anterior al viernes 12:00; fuera de ese corte se puede solicitar el sábado siguiente. Las solicitudes enviadas a tiempo pueden aprobarse después. Cambio diario de colación entre 13:00–14:00 y 14:00–15:00 con aprobación incluso tras terminar la jornada. Retirar nuevos reconocimientos excepcionales respetando créditos/canjes ya aprobados. Sin permiso de cambio de horario semanal; marcajes sin jornada o con entrada anticipada de 60 minutos o más se rechazarán con «No cuenta con jornada asignada». [Plan rectificado y pendientes](../desarrollo/PLAN_MEJORAS_PERMISOS_20261106.md). Son reglas solicitadas aún no implementadas: el código no se cambió en esta rectificación ni se incrementó la versión. Se conservó la rama de bandeja 1.7.0 con sus cambios sin commit; queda cerrar esa entrega antes de crear otra rama desde dev. No se consultó producción.

### 2026-11-06 Bandeja por categorías para superiores — 1.7.0

Rama `permisos-bandeja-superiores` desde dev limpio `fcfc39d`, tras integración de 1.6.0 por el responsable. Se trasladan los expedientes excepcionales de la parte superior a categorías de Permisos, con listas compactas, búsqueda, fechas, estados, paginación y detalle emergente para ADMIN y SUPERVISOR. Se conserva autorización del servicio; canjes autónomos confirmados sin aprobación adicional, revisión histórica/reapertura disponible. Preparación de datos en controlador/servicio, escape de motivos y títulos únicos en diálogos. Pasaron 435 comprobaciones de negocio (18 nuevas), 276 MVC (32 nuevas), 21 de configuración y un test Edge con ocho recorridos Coffee a 390/1280 px, apertura/cierre Escape y capturas revisadas. El arranque Edge requirió acceso fuera del sandbox por perfil temporal; toda la red fue interceptada y no se enviaron formularios reales. CSS compilado; sin SQL nuevo (depende de 014), cambios productivos, commit o push. Versión 1.6.0 → 1.7.0, MENOR. La lista excepcional sigue paginando en memoria los expedientes autorizados; no se certifica rendimiento de grandes volúmenes ni concurrencia.

### 2026-11-06 Canje autónomo y cinco jornadas hábiles — 1.6.0

Rama `tiempo-excepcional-canje-autonomo` desde dev limpio `5b0297f`, después de integrar el responsable la entrega 1.5.0. Nuevos créditos con una aprobación del reconocimiento por ADMIN/SUPERVISOR y motivo; el practicante confirma canje con auditoría propia y sin segunda aprobación. Cinco jornadas posteriores al día de aprobación, según horario individual y sin feriados, hasta fin del quinto turno. La revisión de evidencia no renueva el plazo. Los expedientes existentes conservan política y vencimiento; requiere parche 014 antes de publicar código y actualiza schema para instalación nueva. Se actualizaron textos y documentación; la bandeja unificada continúa pendiente para entrega 3. Pasaron 417 comprobaciones SQLite de negocio (19 nuevas), 244 MVC, 21 de configuración y 3 de migración MariaDB ficticia (repetición del parche y comparación con schema). Sintaxis y diff correctos. La prueba de migración necesitó acceso fuera del sandbox al socket local; creó y retiró únicamente su base aleatoria. Sin pruebas de concurrencia InnoDB, recorrido visual ni consultas/cambios en producción; sin commit, push o despliegue. Versión local 1.5.0 → 1.6.0, MENOR.

### 2026-11-06 Permisos y recuperación anticipada — 1.5.0

Ampliación solicitada durante la misma entrega: aprobación tardía aun después de cinco días, con validación transaccional de recuperaciones ya terminadas y con marcas completas. Se recalculan sus horas y estado sin ocultar otras incidencias; evidencia incompleta queda pendiente. El fallo de auditoría revierte aprobación y crédito juntos. Validación final: 398 comprobaciones de negocio (29 nuevas), 244 MVC y 21 de configuración, sintaxis y diff correctos. Sin navegador ni concurrencia InnoDB comprobados. Se mantiene 1.5.0 por ser la misma entrega aún pendiente.

Rama `permisos-recuperacion-anticipada` desde `dev` local limpio `cd283df`. Ampliación compatible 1.4.1 → 1.5.0. Se habilitan ausencias pasadas dentro de la práctica en servidor, API y formulario; se permite validar trabajo de recuperación terminado antes de la ausencia futura. Se conservan solicitud previa del tramo de recuperación, autorización de superiores, marcas, auditoría y reapertura por cruces posteriores. La aprobación no modifica el horario semanal: ausencia y recuperación se limitan a sus fechas; Mi asistencia muestra los tramos del día. Sin SQL ni cambio de entorno. Pruebas aisladas de negocio, MVC y configuración; no se consultó producción ni se hicieron operaciones sobre datos reales. Sin commit, push ni despliegue. Las próximas entregas requieren confirmación: [plan y acuerdos](../desarrollo/PLAN_MEJORAS_PERMISOS_20261106.md).

### 2026-11-05 Documentación de correcciones y normalización a 1.4.1

Se revisó el cambio `871f60b`, integrado mediante `9d9155d` en dev: normalización de sitios web sin protocolo, aceptación de HTTP/HTTPS, confirmación de guardado por sesión, sustitución de la descripción libre de horario variable por Sí/No obligatorio y ayudas de horario. No figuraba en la guía ni en CHANGELOG; el título del merge decía 1.4.1 pero `config/version.php` devolvía 1.4.0. Se documenta el comportamiento observado y se actualiza la fuente única a 1.4.1 (PARCHE, destino indicado por el responsable), conservando antecedentes y trabajo del colaborador. Se retira el fallback fijo 1.1.1 de la barra lateral para usar APP_VERSION como el login. No se alteran las validaciones del controlador ni la base; no se equipara la ayuda de horario con un parser de turnos. Pasaron 20 comprobaciones DOCX, 21 de configuración y 244 MVC, sintaxis y revisión de whitespace. Las pruebas DOCX usan el servicio y no cubren el POST del controlador ni la visualización en Word. Pendiente evidencia de despliegue; no se consultó ni modificó Hostinger en esta tarea. Sin commit ni push.

### 2026-11-04 Comprobante de marcaje por correo (v1.4.0)

Rama `marcaje-correo`, actualizada con `dev` (1.3.0). Cada marca del practicante envía un comprobante por correo (tipo, fecha, hora y folio) como respaldo de doble autenticidad. Archivos: `src/Services/MailService.php`, `src/Controllers/Practicante/MarcarAsistenciaController.php`, `config/mail.php`, `.env.example` y `public/assets/brand/logo_correo.png`. Remitente, mensaje, logo y activación se configuran en `.env`.

- El envío no revierte ni bloquea la marca: va después del commit, con límite de 10 s y fallos solo en log.
- Validado en local con fixture ficticia: Mailpit para el diseño y entrega real en Gmail mediante SMTP de Gmail. No se probó en producción.
- 04/11/2026: el buzón de envío cambió a `recursos.humanos@epracticas.cl` (Hostinger), en reemplazo de `MarcajeEpracticas@epracticas.cl`; envío comprobado desde local. Pendiente para producción: que un responsable con acceso cambie `MAIL_USERNAME`, `MAIL_FROM_ADDRESS` y `MAIL_PASSWORD` en el `.env` de `app-rrhh` (ver [Correo](../operacion/CORREO_SMTP.md)).
- En local, `vendor/phpmailer` estaba incompleto (faltaban `PHPMailer.php`, `SMTP.php` y `Exception.php`); se reinstaló 6.12.0 desde `composer.lock`. `vendor/` no se versiona.

### 2026-11-04 Módulo de Recepción y Gestión de Reseñas (v1.3.0)

Implementación completa (frontend y backend) del nuevo módulo de Recepción y Gestión de Reseñas para estudiantes en práctica y equipo de Recursos Humanos / Supervisión, catalogada como Version:1.3.0):

- **Portal Practicante (`/practicante/resenas.php`):** Formulario interactivo con calificación general (1 a 5 estrellas), selección de aspectos destacados (chips dinámicos), selector de canales de envío (Discord, Correo, WhatsApp, Documento presencial), testimonio con contador de caracteres y previsualización en vivo, descarga de plantilla PDF oficial y carga segura de autorización legal firmada con validación de consentimiento expreso.
- **Portal Supervisor / RRHH (`/supervisor/resenas.php`):** Tarjetas de métricas en tiempo real (Pendientes de Entrega, Reseñas Recibidas, Reseñas Publicadas) y tabla de seguimiento de practicantes con visualización de canales, descarga de autorizaciones en PDF y flujo de aprobación/publicación directa.
- **Base de Datos y Modelo (`src/Models/ResenasModel.php`):** Parche `database/2026-11-04_patch_resenas_practica_013.sql` con la tabla `resenas_practica`. Sincronización automática con la tabla existente `acompanamiento` actualizando las banderas `resena_recibida` y `resena_publicada`.
- **Almacenamiento y Seguridad:** Custodia de archivos en `uploads/resenas/` excluido de Git, validación CSRF en todas las peticiones POST y entrega autenticada de documentos mediante cabeceras HTTP binarias.
- **Versionado y Documentación:** Actualización centralizada en `config/version.php` a `1.3.0`, documentación técnica normalizada en `docs/modulos/RECEPCION_RESENAS.md`, actualización del índice en `docs/modulos/README.md` y registro en `CHANGELOG.md`.

### 2026-11-02 Formulario de validación (v1.2.0)

Se incorpora a la versión de desarrollo el flujo para que ADMIN y SUPERVISOR completen el formulario Duoc UC con antecedentes de práctica y descarguen una copia generada. El original se procesa temporalmente y se elimina; el documento generado queda en almacenamiento privado con metadatos mínimos. La primera versión solo admite la plantilla Duoc UC. La prueba automatizada usa DOCX sintéticos; aún se requiere aplicar el parche 012 y configurar los datos reales antes de publicar. No se verificó producción.

### 2026-11-02 Formato 24 h y rediseño de Permisos (v1.1.3)

Rama `mejoras-visuales`, que incorpora `formato-24hrs`. Detalle en [CHANGELOG](../../CHANGELOG.md).

- Las horas se ingresan siempre en formato chileno de 24 h (`HH:MM`), con una máscara que impide horas imposibles. Las fechas siguen usando el selector nativo, que muestra el formato según el idioma del navegador (pendiente de evaluar).
- Permisos adopta el patrón de Practicantes: tabla compacta y ventana "Revisar solicitud" con detalle, plan de recuperación y Aprobar/Rechazar. Los avisos del servidor pasan a ser flotantes, y al resolver una solicitud se conserva la pestaña activa.
- Sistema de botones con variantes para Claro y Black, e íconos Lucide en Permisos mediante `icono()`. Ver [Componentes](../diseno/COMPONENTES.md).
- Diagnóstico: las aprobaciones rechazadas en local se debían a datos de prueba antiguos (practicantes sin horario, horarios cambiados después de la solicitud, planes con tramos fuera de 10:00–20:00). El servidor los bloqueó correctamente, pero el mensaje quedaba fuera de la vista.
- Datos locales (no forman parte del PR): se reemplazaron las solicitudes de prueba por 10 con fechas vigentes, se asignó horario a Diego y se crearon cuentas locales de practicante para Camila, Nicolás y Diego.
- Validación: `php -l`; rutas 172, MVC 241 y configuración 21 correctas. Aprobación y rechazo de punta a punta en el navegador como supervisor, en Claro y Black y a 390/1440 px. Se actualizaron `tests/e2e.test.cjs` y `tests/ui.test.cjs` para el nuevo flujo; no se ejecutaron porque el host no tiene `php` y `scratch/` pertenece a root. Sin SQL, commit ni despliegue.

### 2026-10-29 Mejoras visuales dentro del panel de Practicantes (v1.1.2)

Dentro del módulo de practicantes se mejoró el funcionamiento y el usabilidad dentro de la pestaña a la hora de tener que hacer una acción en algún practicante:

- Listado  a un ancho completo: se reemplaza la división lateral por una tabla de ancho completo con avatares de iniciales, columnas proporcionadas y textos sin cortes a mitad de palabra.
- Ficha de gestión en ventana emergente (pop-up): al pulsar Abrir ficha, la información personal/académica, área vigente, excepciones, horario semanal y baja lógica se abren en un modal centrado con cabecera fija, píldoras de navegación rápida con estado activo y cierre por botón `X Cerrar`, con la tecla `Escape` o click en el fondo.
- Pestañas de filtrado rápido en cliente: filtro instantáneo por estado de horario (`Todos`, `Con horario`, `Sin horario`) con contadores e interacción semántica homologada con el módulo de Permisos.
- Atajos de llenado en horario semanal: botones rápidos para replicar el lunes en lunes-viernes, cargar jornada base (`09:00-18:00`) o limpiar días.
- Cabecera de contexto activo y buscador:  tarjeta superior unificada con insignia de rol (`Admin / Supervisor`), botón Buscar con hover sólido y botón de papelera para limpiar filtros activos en Practicantes y Acompañamientos.
- Higiene de selección (`pointer-events: none; user-select: none;`): aplicada a insignias de estado, avatares, píldoras de rol y bloque de versión del siderbar.

### 2026-10-28 Mejoras visuales en Permisos y Temas (v1.1.2)

Rama `mejoras-visuales`. Se conecta la versión centralizada `APP_VERSION` en la barra lateral `sidebar` con formato `v1.1.2`.

En el módulo de Solicitudes de Permisos (`src/Controllers/Shared/PermisosController.php`, `src/Services/PermisosService.php` y `src/Views/shared/permisos.view.php`):

- Se implementó paginación predeterminada de 5 registros por página con selector dinámico `5, 10, 25, 50` en la cabecera de la tabla, conservando los filtros activos.
- Se agregó anclaje `#listado-permisos` y preservación de posición vertical mediante `sessionStorage` para evitar saltos molestos al cambiar de página o límite.
- Se reemplazó el menú desplegable de estados por pestañas segmentadas en vivo (`Pendientes de revisión`, `Aprobados`, `Rechazados`) con contadores en vivo, micro-interacción de elevación y hover/activo semántico según la paleta.
- Se rediseñaron las tarjetas de resumen KPI con tipografía en *Sentence case*, iconos cuadrados bien delimitados y subtítulos descriptivos.
- En la columna de acciones se formalizó el botón `Revisar marcas de asistencia` con icono de calendario centrado en una sola línea, aviso de observación compacto y botones de Aprobar y Rechazar con colores sólidos semánticos y ancho simétrico.
- En la parte superior derecha donde se ubica el usuario se ajustó el menú desplegable, dejándolo formal y ordenado.

En temas y estilos (`public/assets/css/practicante-temas.css` y `public/assets/css/tailwind.css`):

- Se corrigió la cascada CSS que dejaba los iconos y contadores oscuros sobre fondo negro en el tema `Black` y temas oscuros.
- Se mantuvieron los contrastes de texto blanco en los botones de acción sin verse alterados por el cambio de tema.

### 2026-10-27 Versionado explícito e indicador del login

Rama `versionado-login`, conservando los cambios documentales previos. Se adopta 1.1.1 como base indicada por el responsable, con fuente única `config/version.php` y constante APP_VERSION visible en el login en ambos temas. Se agregan [regla de versiones](../desarrollo/VERSIONADO.md), CHANGELOG e instrucciones en AGENTS; el [registro productivo](../operacion/PRODUCCION.md) separa la versión preparada de la última revisión observada en Hostinger, sin afirmar un nuevo despliegue. Se instaló la preferencia de versionado en el AGENTS global de este perfil de Codex, sin renumerar otros proyectos. Sin SQL ni nuevas variables de entorno. Sintaxis correcta, 21 comprobaciones de configuración, 241 MVC y prueba de login Edge con fixtures en claro/oscuro a 320/1280 px correctas; capturas revisadas y CSS compilado. Sin envío de formularios, commit, push ni despliegue.

### 2026-10-26 Plantilla excepcional de eliminación de practicantes

El responsable pidió después un bloque de SQL directo semejante al que ya utilizaba. Se agregó [eliminar_practicante_directo.sql](../operacion/plantillas/eliminar_practicante_directo.sql), sin procedimiento ni conservación predeterminada de cuenta: incluye borrado de cuenta y auditoría del actor/sesiones/ficha solicitado expresamente, y las tablas de recuperaciones, excepcional y Notas. No borra filas ajenas por autoría para forzar las FK. Pasaron 23 comprobaciones aisladas sobre schema actual y se retiró la base ficticia. El bloque directo requiere detenerse al primer error y hacer ROLLBACK en la misma conexión; no tiene reversión automática. No se ejecutó en la base real ni en producción.

Por solicitud explícita del responsable, se prepara una [plantilla de mantenimiento manual](../operacion/plantillas/README.md), separada de los parches, para eliminar una ficha y sus dependencias propias. Vista previa por defecto, ID inicialmente NULL, confirmación explícita, cuenta histórica conservada/inactiva por defecto y eliminación de cuenta opcional sujeta a FK. No desactiva integridad ni borra auditoría o archivos físicos; revierte la transacción si una dependencia impide finalizar. No sustituye la baja lógica del sistema. Tras activar MySQL el responsable, pasaron 31 comprobaciones en una base ficticia local: borrado propio, conservación de datos ajenos, ambas opciones de cuenta y reversión ante dependencias. Se corrigió el nombre de columna de roles a `rol_activo` detectado en la primera ejecución. Las bases ficticias se retiraron; no se ejecutó sobre datos reales ni producción.

### 2026-10-26 Diagnóstico de recuperación de contraseña por correo

El responsable informa respuesta genérica sin recepción del mensaje. En Hostinger, prod c1a7178 permanece limpio; `.env` anterior y activo idénticos, variables SMTP cargadas, PHPMailer 6.12.0 construible. Configuración, entorno, servicio y controlador anteriores y actuales son equivalentes sin comentarios/formato. Ambas instalaciones rechazan la autenticación SMTP con sus mismos parámetros; no se enviaron mensajes ni se generaron tokens. No se alteraron código, credenciales ni datos productivos. Pendiente verificar buzón y credencial/política del proveedor con el responsable. [Diagnóstico y procedimiento](../operacion/CORREO_SMTP.md). PHP CLI observado: 8.3.36; sin comprobación web que cierre el seguimiento de seguridad.

### 2026-10-23 Informe de migración y manual para operadores

Se creó el [informe Word](../operacion/Informe_migracion_GitHub_Hostinger_RRHH.docx) con la estructura final, preparación, incidencia de vendor, respaldos, mantenimiento, reversiones y evidencia de publicación. Termina con diez pasos para publicar desde prod con los accesos establecidos, respaldo privado, revisión fijada por SHA, dependencias y comprobaciones. No contiene secretos ni ejecuta cambios en Hostinger. Se revisaron estructura y comandos; la conversión del DOCX no está disponible por falta de LibreOffice en el entorno, por lo que no se certificó su paginación final. La vista auxiliar de contenido no sustituye ese renderizado.

### 2026-10-23 Publicación desde el checkout Git de producción

Publicado `prod` c1a7178 a las 15:23:50 de Chile dentro de la ventana autorizada. `public_html/rrhh` enlaza con `app-rrhh/public`; Git limpio. Se conservaron entorno, uploads y base, sin migraciones SQL. Respaldos finales SQL/backend con SHA-256 verificado y frontend anterior privado. El agente SSH se renovó con intervención del responsable. Dos intentos revirtieron automáticamente al persistir el mantenimiento HTTP 503 tras cambiar el enlace; el intento final toleró únicamente ese 503 identificado durante una espera limitada y terminó correctamente, sin reiniciar servicios ni determinar la capa de caché responsable.

Verificación independiente 15:24:31: login 200, entrada 302, seis assets idénticos a Git, rutas privadas 403, inexistentes y diagnósticos temporales 404. Ensayo PHP web correcto con SAPI litespeed, PHP 8.3.35, MariaDB 11.8.9, 41 tablas requeridas, Notas, URL y uploads. Sin formularios con escritura sobre datos reales ni certificación de todos los flujos autenticados. [Evidencia y operación posterior](../operacion/MIGRACION_GIT_20261023.md). Merge a prod no despliega solo: requiere actualización autorizada por SSH. Documentación local pendiente de commit/PR por el responsable.

### 2026-10-23 Preparación directa de la publicación por SSH/Git

Acceso SSH directo comprobado; se actualizó únicamente el checkout privado `app-rrhh` a `prod` c1a7178 mediante la deploy key que el responsable desbloqueó temporalmente. La carpeta pública `public_html/rrhh` sigue normal y el portal activo responde 200. Se generaron respaldo inicial SQL/backend y copia de frontend en una carpeta privada, se verificaron hashes y uploads, y se prepararon mantenimiento protegido y reversión. CLI y web actual: PHP 8.3.35; motor consultado: MariaDB 11.8.9; sin tablas faltantes entre las 41 requeridas y 44 InnoDB existentes. En Hostinger pasaron 369 pruebas SQLite aisladas y 241 MVC; sin pruebas funcionales con escritura sobre registros reales. Ventana autorizada: 23/10, 15:10–16:00 Chile. Procedimiento con bloqueo horario y sintaxis verificados, **sin activación pública ni tarea automática programada**. [Evidencias, límites y ejecución pendiente](../operacion/MIGRACION_GIT_20261023.md). Rama documental `migracion-produccion-ssh`, sin commit/push/merge desde esta tarea.

### 2026-10-23 Canjes y evidencia corregida

Rama `corregir-canje-y-tiempo-corregido`, desde origin/dev, conservando el trabajo local previo. Se sustituyó el rechazo genérico del canje por mensajes de horario efectivo, día sin jornada y cruce con colación, sin relajar las restricciones. Las fixtures prueban viernes 10:00–17:00 con salida compensada 16:00–17:00, excepción que cambia la salida y solicitud sobre una salida correctiva real del supervisor. La jornada corregida ya era solicitable; no se añadió aprobación automática. El rechazo concreto comunicado en producción sigue pendiente de contrastar con su horario/excepción efectivos; no se afirma una causa comprobada ni se desplegó esta mejora. Validación: 369 comprobaciones de negocio y 241 MVC correctas. Se corrigieron fechas de fixtures para no mezclar interrupciones ordinarias con sábado ni duplicar jornadas al ejecutar un viernes.

### 2026-10-23 Recuperación local y servicio MariaDB

Se respaldaron 4.180 archivos por SHA-256 y se exportaron todas las bases desde una copia legible. Se ensayó restauración lógica y se compararon 2.010 tablas por conteo/checksum. Se detectó nueva contaminación de logs en tablas durante arranques standalone sin redirección y el panel anterior; la primera reparación no bastó. Se registró el servicio Windows manual `mysql`, conservando 3306. El directorio activo final es `D:\Rescate-MySQL-20261023\restaurado-servicio`, copia rescatable verificada con metadatos internos reparados; los directorios anteriores y el SQL permanecen conservados. El responsable confirmó Stop/Start desde el panel como administrador; después se verificaron servicio Running/Manual, 69 tablas OK sin advertencias, 2.010 conteos/checksums coincidentes e innodb_force_recovery=0. [Guía vigente de operación y límites](../operacion/MARIADB_LOCAL_20261023.md). El cambio es local, no se modificó Hostinger ni el .env.

### 2026-10-22 Verificación de puertos de Apache local

Apache tiene configurados y en escucha los puertos 80 y 443. El puerto 56661 observado pertenece a una conexión saliente de httpd hacia MySQL en 127.0.0.1:3306, no a una escucha HTTP. `httpd -t` devolvió `Syntax OK` y una solicitud al recurso estático `/icons/apache_pb.png` respondió HTTP 200. No se cambiaron puertos ni configuración. Esta comprobación acredita respuesta de Apache, no resolución del incidente MariaDB ni funcionamiento completo de la aplicación.

### 2026-10-22 Diagnóstico de arranque local de MariaDB

Revisión de solo lectura: la configuración sigue usando el directorio recuperado en D: documentado en el incidente del 19/10. El log antiguo de C: termina el 19/10 con `Missing MLOG_CHECKPOINT` y no describe el fallo actual. Los eventos Application de Windows del 22/10 a las 18:20:50 registran `Fatal error: Can't open and lock privilege tables: Incorrect file format 'db'`, seguido de aborto; InnoDB había iniciado. Después aparecen errores de cierre de archivo y APPCRASH de mysqld.exe. El bloqueo observado corresponde a la apertura de la tabla de permisos `mysql.db`; no se ha determinado la causa original del formato inválido.

No se observaron procesos mysqld/mariadbd ni escucha en 3306 durante la consulta; C: y D: tenían espacio libre. Sin arranques, reparación, cambios de configuración ni modificación de datos. Pendiente respaldar el estado actual y diagnosticar/reparar la tabla sobre una copia antes de intervenir la instancia activa. La recuperación del 19/10 no acredita la integridad actual.

### 2026-10-22 Dependencias versionadas y preparación del checkout

El responsable confirmó backend-rrhh en minúsculas, carpeta pública rrhh todavía normal, Git 2.52.0, Composer 2.9.8 y PHP CLI predeterminado 8.2.34, con ejecutable alternativo 8.3.35. Creó dev/prod desde main, configuró deploy key de lectura y clonó prod faa815e en app-rrhh. Copió el entorno privado con permisos 600 e instaló dependencias; requisitos CLI correctos. La copia inicial de uploads coincide según diff comunicado; estos datos no verifican el PHP web ni su parcheado.

La instalación detectó tres archivos PHPMailer seguidos por Git, incompatibles con el lock 6.12.0. Se prepara la rama corregir-vendor-composer desde origin/dev para retirar únicamente su seguimiento, sin borrar copias instaladas ni modificar el lock. La [guía Composer](../operacion/DEPENDENCIAS_COMPOSER.md) documenta el PR dev → prod y la reinstalación única que necesitará el checkout preparado tras recibir esas eliminaciones. La entrada pública sigue sin cambiar; sin commit, push ni modificación directa de Hostinger desde esta tarea.

### 2026-10-22 Revisión de estructura para SSH y Git

El responsable aportó los árboles actual y propuesto en Arquitectura. La estructura propuesta anida un backend que no coincide con la raíz Git ni con el descubrimiento automático actual. Se recomienda clonar en `app-rrhh` conservando `public/` y enlazar `public_html/rrhh` con esa carpeta. Se documentó la diferencia de mayúsculas Backend-rrhh/backend-rrhh como comprobación pendiente, sin asumir fallo de producción.

El [plan de transición](../arquitectura/MIGRACION_SSH_GIT_RRHH.md) comienza con inventario de solo lectura, seguido de respaldo, ramas/clave de lectura, preparación privada, ensayo y cambio controlado. Los diagramas del responsable permanecen intactos. Sin cambios de código, datos, entorno, Git remoto o Hostinger; sin commit/push/despliegue.

### 2026-10-21 Organización de documentación

- Rama `organizacion-documentacion`, desde `main` limpio en `7d2b343`. Sin commit, push ni despliegue.
- Se separan documentos vigentes por tema y se archivan las fases sin borrar su contenido funcional. Branding conserva sus valores editables; se añade catálogo de textos como especificación.
- Se incorpora `AGENTS.md` en raíz, índice y mapas de código por módulo. Se actualizan enlaces e inventarios de pruebas a sus nuevas ubicaciones.
- Validación y alcance final en [Organización documental](../desarrollo/ORGANIZACION_DOCUMENTAL.md).

### 2026-10-20 Acceso y selección en Notas

Todos los practicantes tienen Notas. El liderazgo habilita Evaluar mi área al recargar; retirarlo conserva Mis notas. ADMIN busca candidatos por nombre, correo o RUT y su selector habitual de evaluaciones muestra líderes, conservando corrección global por enlace directo.

La lista vacía de candidatos observada se debía a prácticas locales sin pertenencia vigente. No se asignaron áreas automáticamente. Evidencias: [entrega de accesos](../historial/notas/NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md).

## Mantenimiento

Registrar aquí cambios recientes con fecha, resultado y límites, sin copiar toda la bitácora ni pegar descripciones contradictorias en Contexto. Antes de crecer demasiado, mover entradas cerradas al historial conservando el resumen vigente. La bitácora anterior íntegra está en [Progreso histórico](../historial/PROGRESO.md).
