# Historial de versiones de RRHH

Las fechas corresponden al registro de la entrega, no certifican su publicación. Consultar [versionado](docs/desarrollo/VERSIONADO.md) y [producción](docs/operacion/PRODUCCION.md).

## 2.1.9 — 2026-11-23

**Tipo:** PARCHE desde 2.1.8 (entrega consolidada de operación y asistencia para `prod`, sin MFA ni migraciones SQL). **Estado:** preparada en rama `release-operacion-2.1.9` para PR hacia `prod`.

- **Consolidación de operación (`2.1.5`–`2.1.8` + hotfix `fix-sabado`):** integra márgenes de 15 min y auto-cierre por inactividad, mejoras visuales en Gestión de Asistencia, indicador de meta cumplida al 100% con confeti, descuento de colación en recuperaciones sabatinas `>= 6 h` y conserva el marcaje en sábado por excepción de horario (`EXCEPCION_SABADO`).
- **Modo solo lectura al 100% o egreso (`ReglasAsistenciaService.php`, `LayoutContext.php`, `sidebar.php`, controladores y vistas de practicante):** bloquea automáticamente en servidor nuevos marcajes, solicitudes de permiso y edición de datos cuando el practicante alcanza el 100% de horas o egresa, manteniendo visible el historial, la tarjeta de meta cumplida y el módulo de **Reseñas**. Auto-reversible si supervisión ajusta horas.
- **Refuerzo de validación POST de ventana horaria (`MarcarAsistenciaController.php`):** corrige la condición a `!$evaluacionVentanaPost['habilitado']` para rechazar en servidor cualquier intento de marcaje fuera de la ventana habilitada.
- **Corrección de falsos cruces por segundos ocultos en permisos parciales (`ReglasAsistenciaService.php`, `tests/business.php`):** en `asistencia_permiso_cruza_marcas()`, cuando `tipo_ausencia === 'PARCIAL'`, trunca los extremos de cada tramo trabajado al minuto exacto (`:00`) antes de compararlos con el permiso, evitando bloqueos al validar recuperaciones cuando una marca en vivo registra segundos en el borde (ej. `19:00:45` frente a `19:00–20:00`).
- **Requisitos de publicación:** sin cambios de base de datos, sin nuevas dependencias de Composer y sin cambios en `.env`.

---

## 2.1.8 — 2026-11-13

**Tipo:** PARCHE desde 2.1.7 (descuento automático de colación y cálculo asistido en planes de recuperación sabatina). **Estado:** preparada en rama `fix-permisos-recuperacion` para PR hacia `dev`.

- **Descuento automático de colación en tramos `>= 6 h`:** Se añadió `recuperaciones_minutos_netos_tramo()` en `src/Services/RecuperacionesService.php` y el cálculo equivalente en `public/assets/js/recuperaciones.js`, descontando `60` minutos de colación en tramos `SABADO` o `FERIADO` de `360` minutos brutos o más (ej. `10:00–20:00` equivale a `540 min / 9.00 hrs`; `10:00–18:00` equivale a `420 min / 7.00 hrs`), manteniendo intactos los registros históricos al revisar (`$revisando = true`).
- **Autoajuste de `Hora término` según la ausencia:** `src/Views/practicante/permisos.view.php` sincroniza en tiempo real los minutos laborales de ausencia (jornada completa o tramo parcial) para que `recuperaciones.js` sugiera automáticamente la hora de término exacta al seleccionar el sábado de recuperación.
- **Indicador comparativo de recuperación (`#badgeHorasCalculadas`):** Rediseño a tarjeta `rounded-xl` con estado en verde cuando coincide o en ámbar indicando los minutos faltantes o sobrantes, con el texto alineado a la izquierda y el icono de estado a la derecha.
- **Pruebas:** Añadidas comprobaciones en `tests/recuperaciones-sabados.php` (`10:00–20:00` = `540 min`, `10:00–18:00` = `420 min`). Validado con `php -l`, `node --check`, `tests/business.php` (`491` comprobaciones, `0` fallos), `tests/mvc.php` (`289` comprobaciones), `tests/regla-jornada-laboral.php` (`18` comprobaciones) y captura en Chromium con Playwright.

---

## 2.1.7 — 2026-11-13

**Tipo:** PARCHE desde 2.1.6 (indicador visual de meta de horas completada al 100% con efecto confeti). **Estado:** preparada en rama `meta-completa` para PR hacia `dev`.

- **Indicador de meta cumplida (100%):** En `src/Views/practicante/marcar-asistencia.view.php`, cuando `$horasRequeridas > 0` y `$porcentajeAvance >= 100.0`, la tarjeta de avance cambia su título a **Meta completada**, muestra el badge `100%`, colorea el anillo radial SVG en verde éxito e incorpora el mensaje de felicitación *"¡Felicitaciones! Meta de práctica cumplida — Completaste el 100% de tus horas asignadas."*
- **Compatibilidad de temas:** Estilos `.practitioner-progress--completed` y `.practitioner-goal-*` en `public/assets/css/practicante-temas.css` utilizando las variables `--rrhh-btn-success-soft-*` para asegurar contraste en tema claro y tema oscuro `Black`.
- **Confeti ligero en Vanilla JS (`<canvas>`):** Implementado en `public/assets/js/ui.js` sin dependencias externas (`2500 ms` de duración, pausa inicial de `600 ms`, limpieza automática del `<canvas>` al finalizar). Se dispara una vez por sesión mediante `sessionStorage`, respeta `prefers-reduced-motion: reduce` y ofrece un botón de repetición (`data-confetti-replay`) limitado a 5 pulsaciones con enfriamiento de 2 minutos o reinicio al recargar.
- **Pruebas:** `php -l` sin errores, `tests/business.php` (`489` comprobaciones, `0` fallos) y `tests/mvc.php` (`289` comprobaciones correctas), más verificación en Chromium con Playwright en tema claro, tema `Black` y límite de 5 clics.

---

## 2.1.6 — 2026-11-12

**Tipo:** PARCHE desde 2.1.5 (rediseño de filtros y armonización visual del módulo de Asistencia). **Estado:** preparada en rama `mejoras-visuales-asistencia` para PR hacia `dev`.

- **Barra de filtros segmentada unificada:** Se reemplazó la grilla ancha de botones gigantes por la cápsula segmentada inline con esquinas redondeadas (`rounded-2xl`), idéntica al estándar visual de Permisos y Practicantes, con iconos semánticos, etiquetas compactas y badges de conteo circular.
- **Botón "Limpiar filtros" visible y condicional:** Se rediseñó el botón con fondo sólido claro (`bg-slate-100/90 hover:bg-slate-200`), borde sutil e icono de reinicio. Se corrigió la condición lógica para que permanezca oculto en el estado inicial "Todos" y solo aparezca cuando realmente existan filtros restrictivos aplicados.
- **Formulario de bloques de área en modal:** Corrección de la distribución a 12 columnas (`md:grid-cols-12`) con altura uniforme `!h-10`, campos proporcionales para Área, Inicio (fecha y hora contiguas), Fin y botón Agregar, eliminando líneas horizontales desbordadas y deformaciones.
- **Modal sin salto de layout (Zero Layout Shift):** Altura fijada a 704 px (`min(44rem)`) con scroll interno suave en el cuerpo del diálogo, garantizando estabilidad visual al navegar entre Resumen, Marcas, Bloques y Acciones.
- **Compatibilidad con temas y responsive:** Adaptabilidad fluida en dispositivos móviles (390 px) mediante *flex-wrap* armónico y soporte verificado en tema oscuro Black / Alto contraste.
- **Documentación ejecutiva:** Generación del reporte de verificación visual automatizada con capturas de Playwright en formato Word (`docs/reporte-mejoras-visuales-asistencia.docx`) mediante WordCraft.
- **Pruebas de regresión:** 489 pruebas de negocio, 289 MVC y 18 de regla laboral ejecutadas con 100% de éxito (0 fallos).

---

## 2.1.5 — 2026-11-11

**Tipo:** PARCHE desde 2.1.4 (margen de anticipación de 15 minutos por marca y cierres automáticos por inactividad). **Estado:** preparada en rama `margen-marcaje` para PR hacia `dev`.

- **Control de anticipación horaria (15 min):** Se restringe el marcaje anticipado a un máximo de 15 minutos antes de la hora esperada para cada tipo de marca (`ENTRADA`, `ENTRADA_COLACION`, `SALIDA_COLACION`, `SALIDA`). Si el usuario intenta marcar antes de esa ventana, el botón permanece inhabilitado (`disabled`) con la indicación `Disponible a las HH:MM` y una alerta explicativa.
- **Validación estricta en servidor:** El controlador de marcaje (`MarcarAsistenciaController.php`) revalida en el método POST la función `asistencia_evaluar_ventana_marca` bajo candado transaccional, rechazando intentos de alteración fuera de la ventana permitida.
- **Auto-retorno de colación por inactividad:** Si transcurren 15 minutos después de la hora pactada de regreso de colación sin interacción del practicante, el sistema registra de forma automática la marca `SALIDA_COLACION` (`creada_por = NULL`, observación descriptiva `[Sistema]`), evitando colaciones abiertas indefinidamente.
- **Auto-cierre de jornada por inactividad:** Si transcurren 30 minutos tras la hora pactada de fin de jornada sin registro de salida, el sistema genera la marca de `SALIDA` a la hora programada (`creada_por = NULL`, observación `[Sistema]`) y recalcula la jornada para evitar que quede en estado incompleto o abandonado.
- **Soporte de simulación horaria en desarrollo:** Se amplía la simulación segura (`APP_DEBUG = true`) admitiendo `?simular_hora=HH:MM` junto con `?simular_fecha=YYYY-MM-DD`, permitiendo verificar las ventanas antes, durante y después del horario establecido.
- **Suite de pruebas unitarias:** Archivo `tests/regla-margenes-asistencia.php` con 35 comprobaciones automatizadas cubriendo ventanas de anticipación, puntualidad, atrasos, auto-colación y auto-salida.

---

## 2.1.4 — 2026-11-11

**Tipo:** PARCHE desde 2.1.3. Corrección de integración desde `prod`, recuperando el código de `dev` local probado en `41b34a3`. No acredita un despliegue en Hostinger.

- Se retira la declaración duplicada de `recuperaciones_opciones_fecha()` introducida en la integración de `prod` (`62eb22d`), que provoca el fatal de PHP `Cannot redeclare` al cargar el servicio de recuperaciones.
- Se recuperan los controles de recuperación sabatina, el corte del viernes, la evidencia UTC del envío y su auditoría, junto con textos y pruebas que se perdieron al resolver conflictos. Se mantienen los cambios probados de 2.1.2 y 2.1.3.
- Se restituye el parche existente `database/2026-11-06_patch_recuperaciones_sabados_015.sql` y sus columnas en `schema.sql`; no se crea una migración nueva ni se modifica una base de datos.
- Publicación: comprobar qué migraciones están aplicadas en destino, especialmente 015 y 016 para recuperación y colación, antes de actualizar el checkout. No importar `schema.sql` sobre una instalación existente ni repetir parches ya aplicados por costumbre. Usar Composer desde el lock y verificar las pantallas autenticadas por rol.
- La validación de esta corrección se registra en `docs/proyecto/ESTADO_ACTUAL.md`. El merge conserva el historial; no requiere reset ni push forzado.

## 2.1.3 — 2026-11-10

**Tipo:** PARCHE desde 2.1.2 (regla de jornada laboral, bloqueo en días no laborables y control de aprobación de sábados). **Estado:** preparada en rama `marcaje-reglas` para PR hacia `dev`.

- **Regla estricta de marcaje en jornada laboral:** Bloqueo automático del botón de marcaje los días domingo (no laborable absoluto) y días de semana sin turno asignado en el horario pactado del practicante.
- **Excepción sabatina con recuperación aprobada:** Los días sábado permanecen estrictamente bloqueados por defecto, habilitándose única y exclusivamente cuando el practicante cuenta con una solicitud de permiso en estado `APROBADO` y un tramo de recuperación programado para esa fecha específica, adaptando el horario al inicio y fin autorizados.
- **Protección bidireccional en servidor:** La validación se ejecuta en el renderizado GET y se revalida bajo bloqueo estricto en el procesamiento POST de `MarcarAsistenciaController.php`, impidiendo registros forzados.
- **Tolerancia y margen de 5 días hábiles:** Incorporación de cálculo de días hábiles posteriores al sábado (`recuperaciones_calcular_plazo_aprobacion`), permitiendo a supervisores regularizar aprobaciones tardías sin expiración prematura.
- **Badges y recordatorios visuales en supervisión:** Indicadores contextuales en la tabla de permisos (`⚡ Por aprobar este sábado` para solicitudes inminentes y `⏳ Aprobación tardía (quedan X d. hábiles)` para tramos pasados dentro de tolerancia).
- **Simulación en desarrollo:** Soporte seguro de `?simular_fecha=YYYY-MM-DD` exclusivo para entorno local (`APP_DEBUG = true`), con banner informativo y retorno rápido a la fecha real.
- **Sincronización de esquema:** Se anexó la definición de la tabla `resenas_practica` a `database/schema.sql`.

---

## 2.1.2 — 2026-11-10

**Tipo:** PARCHE desde 2.1.1 (mejora compatible de gestión y trazabilidad de horas). **Estado:** preparada en rama `modificar-horas-practica` para PR hacia `dev`.

- **Modificación de horas en Reportes:** Se incorpora botón y ventana modal en la ficha de detalle de practicante en `/admin/reportes.php` y `/supervisor/reportes.php` para actualizar las horas requeridas y las horas acumuladas históricas.
- **Validación de topes:** Restricción estricta en servidor y cliente para que ningún valor supere las 1.000 horas ni acepte números negativos (horas requeridas entre 1 y 1.000; horas acumuladas entre 0 y 1.000).
- **Cálculo y consistencia:** El sistema ajusta automáticamente las `horas_previas` en la tabla `practicas` para que el avance total acumulado coincida exactamente con lo ingresado sin alterar las jornadas ni las marcas reales de asistencia.
- **Seguridad y auditoría:** Protección con token CSRF específico, patrón Post-Redirect-Get con aviso de confirmación y registro auditable en la base de datos bajo el evento funcional `MODIFICAR_HORAS_PRACTICA` (capturando actor, rol, valores anteriores y nuevos).
- **Trazabilidad visible:** La ficha de detalle del practicante y la ventana modal de edición despliegan directamente quién fue el responsable de la modificación, con su nombre, correo, rol, fecha y hora exacta del cambio.

---

## 2.1.1 — 2026-11-09

**Tipo:** PARCHE desde 2.1.0, mejoras visuales y validación interactiva compatible. **Estado:** preparada en `mejoras-visuales`, sin publicación.

- Nueva columna «Recuperación» en la tabla de Permisos de ADMIN/SUPERVISOR: fecha planificada, tramo, estado y cálculo de avance en planes de múltiples fechas.
- Botón «Validar asistencia cumplida» en un solo clic con motivo predeterminado automático («Jornada de recuperación validada conforme a marcas registradas») sin inputs manuales.
- Validación en vivo mediante AJAX (`X-Requested-With: XMLHttpRequest`): actualiza tramo, saldo y fila sin recargar la página, preservando filtros activos (incluyendo límite de 20 registros) y estado del diálogo.
- Microinteracciones orgánicas: retroalimentación táctil al presionar, retardo perceptible de 450 ms con indicador de carga acotado, insignia animada con resorte y trazado de confirmación, y aviso flotante no intrusivo.
- Enlace directo «Ver marcas →» a Asistencia filtrado por practicante y fecha cuando una solicitud pendiente cruza marcas registradas del mismo día.
- Despliegue: sin cambios en el esquema de base de datos (`database/schema.sql` vigente); requiere publicar CSS compilado y nuevo ícono `calendar.svg`.
- Validación: 489 pruebas de negocio, 289 MVC, 180 rutas y 21 de configuración correctas; capturas visuales en navegador local.

---

## 2.1.0 — 2026-11-06

**Tipo:** MENOR desde 2.0.0, nueva funcionalidad compatible. **Estado:** preparada en `permisos-cambio-colacion`, sin publicación.

- Solicitud propia para cambiar la colación de hoy entre 13:00–14:00 y 14:00–15:00, con motivo e historial.
- Categoría de revisión ADMIN/SUPERVISOR con filtros, paginación y detalle emergente; aprobación incluso después de terminada la jornada y aviso de pendientes en el menú.
- Ajuste diario del horario efectivo y recálculo auditado, conservando horario semanal, marcas e incidencias pendientes. No genera recuperación ni tiempo excepcional.
- Control de autosupervisión, duplicación, turno cambiado y cruces con ausencias/recuperaciones/canjes. Un rechazo permite volver a solicitar conservando registros anteriores.
- Despliegue: respaldo y **parche 016 antes del código**; nueva tabla incluida en schema. Sin dependencias o variables nuevas. No publicar consultas de horario sin crear antes la tabla.
- Validación: pruebas de negocio, MVC/configuración, migración MariaDB ficticia y Edge en móvil/escritorio con Coffee; resultados y límites en Estado actual.

## 2.0.0 — 2026-11-06

**Tipo:** MAYOR desde 1.7.0: cambia las opciones de uso aceptadas para solicitudes nuevas (retira recuperación entre semana, colación trabajada y recuperación anterior a la ausencia). Los tramos existentes conservan su política. **Estado:** preparada en `permisos-recuperacion-sabados`; sin publicación.

- Recuperación nueva solo sábados 10:00–20:00, sin jornada ordinaria superpuesta; feriados solo si son sábados con autorización expresa.
- Envío antes del viernes previo a las 12:00; rechazo automático fuera del corte con auditoría y opción de elegir sábado siguiente, sin crear reserva pendiente.
- Fecha UTC y política por tramo para aprobar después del corte o del sábado trabajado, conservando evidencia y recalculando las horas acreditadas.
- Formularios inicial y adicional, API de opciones y servicios aplican la nueva restricción; planes anteriores no se cancelan ni recalculan por esta migración.
- Validación: 456 comprobaciones de negocio, 276 MVC, 21 de configuración y 4 de migración MariaDB ficticia; sintaxis y diff correctos. Sin certificación productiva ni concurrencia InnoDB.
- Despliegue: respaldo y parche **015 antes del código**; schema actualizado para instalación nueva. Sin dependencias o variables nuevas. Retirada de nuevos créditos excepcionales, cambio de colación y bloqueo de marcajes permanecen para entregas separadas.

## 1.7.0 — 2026-11-06

**Tipo:** MENOR, ampliación compatible desde 1.6.0. **Estado:** preparada en `permisos-bandeja-superiores`, sin publicación.

- Categorías de Ausencias y recuperación, Tiempo excepcional y Compensación en la bandeja compartida de ADMIN/SUPERVISOR.
- Nuevas listas compactas con búsqueda, filtros de fecha/estado, paginación y detalle emergente; reemplazan los expedientes excepcionales desplegados antes de los permisos ordinarios.
- Los canjes autónomos confirmados no solicitan segunda aprobación; se conservan revisiones de históricos y evidencia reabierta.
- Validación: 435 comprobaciones de negocio, 276 MVC, 21 de configuración y prueba Edge con ocho recorridos de categoría/rol/ancho en Coffee; CSS compilado, sintaxis y diff correctos.
- Despliegue: sin SQL nuevo, entorno ni dependencias nuevas. Requiere el parche 014 de 1.6.0; publicar CSS del portal junto al código. Sin validación productiva ni certificación de todos los temas/navegadores.

## 1.6.0 — 2026-11-06

**Tipo:** MENOR, ampliación compatible desde 1.5.0. **Estado:** preparada en `tiempo-excepcional-canje-autonomo`, sin publicación.

- Nuevos créditos: una aprobación del reconocimiento con motivo del supervisor y canje confirmado por el practicante sin segunda aprobación, con controles de saldo, exclusividad diaria, horario y auditoría propia.
- Vencimiento al finalizar el quinto día de jornada posterior a la aprobación, según horario individual y excluyendo feriados; la revisión de evidencia no renueva el plazo.
- Conserva política y vencimientos de expedientes anteriores; actualiza instrucciones y botones de Permisos para los tres roles.
- Despliegue: respaldo y parche **014 antes del código**, schema actualizado para instalaciones nuevas. Sin nuevas dependencias ni variables de entorno. No ejecutar schema sobre producción.
- Validación: negocio SQLite, migración MariaDB ficticia repetible, MVC, configuración y sintaxis. Sin comprobación productiva ni certificación de concurrencia/navegador.

## 1.5.0 — 2026-11-06

**Tipo:** MENOR, ampliación compatible desde 1.4.1. **Estado:** preparada localmente; no publicada.

- Ausencias completas/parciales pasadas, actuales o futuras dentro de la práctica activa, conservando revisión de cruces con marcas.
- Validación de recuperaciones terminadas anteriores a la ausencia, con aprobación, evidencia, auditoría y reapertura si después aparecen marcas contradictorias.
- Aprobación tardía sin límite de cinco días: reconoce en la misma transacción las recuperaciones terminadas con marcas completas y recalcula sus jornadas; conserva incidencias no resueltas y revierte todo si falla la auditoría.
- Ayudas de solicitud y tramos autorizados visibles en Mi asistencia, sin alterar el horario semanal ni acreditar horas automáticamente.
- Validación: negocio SQLite, MVC, configuración y sintaxis; ver Estado actual. Sin pruebas productivas ni certificación de concurrencia InnoDB.
- Despliegue: sin SQL, nuevas dependencias ni variables de entorno; requiere las tablas de permisos y recuperaciones existentes. No cambia todavía el tiempo excepcional.

## 1.4.1 — 2026-11-05

**Tipo:** PARCHE (correcciones compatibles del formulario). **Estado:** correcciones integradas en `dev` mediante `9d9155d`; documentación y versión central normalizadas a petición del responsable. No acredita publicación en Hostinger.

- **Sitio web del centro:** permite direcciones sin protocolo, añadiendo `https://` antes de guardarlas; conserva HTTP o HTTPS explícitos y rechaza protocolos distintos, URL inválida o sin host.
- **Confirmación de configuración:** tras guardar los datos del centro, un mensaje de sesión confirma el éxito después de redirigir a la pestaña Configuración; se consume una sola vez.
- **Horario variable:** reemplaza la descripción libre opcional por la elección obligatoria «Sí» o «No», validada también en el controlador y escrita en la celda correspondiente del DOCX.
- **Ayudas de horario:** explica cómo describir días y horas en formato de 24 horas y cuándo seleccionar un horario variable. El horario de trabajo permanece como texto libre requerido, con máximo de 300 caracteres; no se añadió un parser de turnos.
- **Trazabilidad:** documenta las correcciones de `871f60b` sin modificar su implementación ni reescribir las versiones de incorporación de los módulos (1.2.0, 1.3.0 y 1.4.0). `config/version.php` pasa de 1.4.0 a 1.4.1; el login y la barra lateral utilizan APP_VERSION.

**Publicación:** esta corrección no añade SQL, variables de entorno ni dependencias. Requiere los prerrequisitos del módulo original (parche 012, configuración y extensiones ZIP/DOM) y las migraciones que falten de la entrega integrada, incluido el parche 013 si Reseñas aún no está instalado. No importar schema.sql sobre una base existente. Publicar vista, controlador y fuente de versión de la entrega.

**Validación:** revisión del diff y del código actual; 20 comprobaciones DOCX sintéticas, 21 de configuración y 244 MVC correctas, sintaxis PHP correcta y `git diff --check` sin errores. Alcance y límites registrados en [Formulario validación](docs/modulos/FORMULARIO_VALIDACION.md); sin prueba HTTP integrada del controlador ni revisión visual nueva en Word. Sin envío de correos, cambios en bases reales ni despliegue.

---

## 1.4.0 — 2026-11-04

**Tipo:** MENOR (funcionalidad nueva compatible). **Estado:** preparada en rama `marcaje-correo` sobre `dev` (1.3.0); sin publicar.

- **Comprobante de marcaje por correo:** cada marca que registra el practicante (entrada, colación, salida y permisos) envía a su correo un comprobante con tipo de marca, fecha, hora exacta guardada, y folio `M-000000`.
- **Tolerancia a fallos:** el envío ocurre después del commit de la marca, con límite de 10 s. Si SMTP falla o no está configurado, la marca se conserva y solo se registra el motivo en el log del servidor.
- **Configuración en `.env`:** `MAIL_COMPROBANTE_ACTIVO` (1/0), `MAIL_COMPROBANTE_MENSAJE` (texto del pie) y `MAIL_COMPROBANTE_LOGO` (ruta relativa a `public/`, vacío sin imagen). Remitente y dominio siguen en `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`.
- **Prioridad alta:** cabeceras `X-Priority: 1` e `Importance: High` (Outlook y Apple Mail las muestran; Gmail aplica su propio criterio).
- **Logo liviano:** se agrega `public/assets/brand/logo_correo.png` (328 px, 25 KB), incrustado por CID, no como adjunto.

**Publicación:** no requiere SQL. Variables nuevas opcionales con valores por defecto. Requiere SMTP de producción operativo (ver incidente en [Correo](docs/operacion/CORREO_SMTP.md)); mientras falle, el comprobante no llega pero el marcaje funciona. Para apagarlo: `MAIL_COMPROBANTE_ACTIVO=0`.

**Validación:** `php -l` correcto; 21 config, 241 MVC y 172 enrutamiento correctas. Prueba en navegador con fixture local: marca con PHPMailer no disponible se guardó y dejó solo log; marca con Mailpit generó el correo al practicante, 0 adjuntos, revisado a 1280 y 360 px. Entrega real comprobada en Gmail con SMTP de Gmail (contraseña de aplicación local): llegaron el envío directo y el de una marca de salida. Envío correcto también desde local con el buzón de Hostinger vigente `recursos.humanos@epracticas.cl` (reemplaza a `MarcajeEpracticas@epracticas.cl`, probado el 03/11/2026).

---

## 1.3.0 — 2026-11-04

**Tipo:** MENOR (nueva funcionalidad compatible de módulo de reseñas). **Estado:** preparada en rama `RECEPCION-RESENAS` para PR hacia `dev`.

- **Módulo de Recepción y Gestión de Reseñas:**
  - **Portal Practicante (`/practicante/resenas.php`):** Formulario interactivo con calificación en estrellas (1 a 5), selección de aspectos destacados, selector de canales de envío (Discord, Correo, WhatsApp, Documento presencial), testimonio con contador de caracteres en vivo y descarga/subida de autorización legal oficial en PDF firmada.
  - **Portal Supervisor / RRHH (`/supervisor/resenas.php`):** Panel con tarjetas métricas en tiempo real (Pendientes, Recibidas, Publicadas), tabla de seguimiento con visualización de canales, descarga de autorizaciones firmadas y flujo de aprobación/publicación de testimonios.
  - **Base de Datos y Modelo (`ResenasModel.php`):** Creación de tabla `resenas_practica` (parche `013`) y sincronización automática con la tabla existente `acompanamiento` (`resena_recibida` y `resena_publicada`).
  - **Custodia de Archivos:** Almacenamiento seguro en `uploads/resenas/` excluido de Git y descarga autenticada con cabeceras binarias protegidas.

---

## 1.2.0 — 2026-11-02

**Tipo:** MENOR (funcionalidad compatible). **Estado:** implementada en la rama de desarrollo, sin publicar.

- **Formulario de validación:** ADMIN y SUPERVISOR pueden cargar el DOCX Duoc UC, completar los antecedentes de práctica y descargar una copia generada. El original se procesa temporalmente y se elimina; el resultado y sus metadatos mínimos se conservan en almacenamiento privado. Otras instituciones requieren una plantilla y un adaptador propios.
- **Configuración:** se administran los datos del centro y las sugerencias de unidad y funciones por carrera.

**Validación:** `php -d extension=zip tests/formulario-validacion.php` pasó con 20 comprobaciones sobre documentos OOXML sintéticos; no acredita el formato visual en todas las versiones de Word ni el entorno productivo.

**Publicación:** requiere aplicar el parche SQL 012, configurar los datos reales del centro y las sugerencias por carrera, y comprobar `ext-zip` en el PHP web de destino. No se ha desplegado.

---

## 1.1.3 — 2026-11-02

**Tipo:** PARCHE (mejoras visuales, de usabilidad y correcciones compatibles). **Estado:** preparada en rama `mejoras-visuales` para PR hacia `dev`.

- **Formato de hora 24 h:** los 18 campos de hora (horario semanal, excepciones, permisos, recuperaciones y tiempo excepcional) dejan el selector nativo, que mostraba AM/PM según el idioma del navegador, y usan texto `HH:MM` con máscara que descarta horas imposibles y completa al salir (`1400` → `14:00`). Los campos de fecha y hora de Asistencia se separan en fecha + hora 24 h; el controlador los vuelve a unir en el formato que ya validaba el servicio.
- **Permisos — listado:** tabla compacta como en Practicantes (avatar, ausencia con día y tipo, motivo en dos líneas, estado en mayúscula inicial con ícono) y un único botón **Revisar solicitud** / **Ver detalle**.
- **Permisos — ventana "Revisar solicitud":** reúne resumen, motivo, observación con enlace **Ver marcas del día** (Asistencia filtrada por practicante y fecha), plan de recuperación y respuesta del supervisor; Aprobar y Rechazar abren encima los mismos diálogos de confirmación, sin cambios en formularios, CSRF ni IDs.
- **Permisos — avisos:** los mensajes de éxito y error se muestran como aviso flotante visible aunque la página recargue anclada en la tabla; al aprobar o rechazar se vuelve a la misma pestaña y filtros.
- **Corrección:** el día de la semana se mostraba en inglés (`Tuesday`); ahora usa `horarios_practicante_dias()`.
- **Sistema de botones:** variantes `rrhh-btn-primary`, `-secondary`, `-success`, `-danger`, `-success-soft`, `-danger-soft` y `rrhh-btn-sm` con valores propios para Claro y Black, foco visible, hover gradual y contraste AA medido.
- **Íconos:** se incorpora Lucide (`lucide-static` 1.59.0, ISC) en Permisos mediante el helper `icono()`; solo se publican los SVG usados (`public/assets/icons/lucide/`). El resto del portal mantiene Bootstrap Icons.

**Publicación:** no requiere SQL ni nuevas variables de entorno. Incluye `config/version.php`, vistas, helper, CSS y los SVG de `public/assets/icons/lucide/` (Hostinger no necesita Node).

---

## 1.1.2 — 2026-10-28

**Tipo:** PARCHE (mejoras visuales y de usabilidad compatibles). **Estado:** preparada en rama `mejoras-visuales` para PR hacia `dev`.

- **Módulo de Permisos:** Se incorpora paginación por defecto de 5 registros con selector `5, 10, 25, 50`, preservación de scroll vertical con `#listado-permisos` y pestañas interactivas de estados (`Pendientes`, `Aprobados`, `Rechazados`) con contadores en vivo.
- **Jerarquía y Acciones:** Rediseño de tarjetas KPI en *Sentence case*, botón simétrico y compacto de revisión de asistencias, y ajuste del menú desplegable de usuario en la barra superior.
- **Temas y Contraste:** Corrección de estilos en `practicante-temas.css` para asegurar contraste de texto blanco e iconos en botones y contadores sobre tema `Black` y temas oscuros.
- **Versionado:** Conexión de `APP_VERSION` en la barra lateral del portal.

**Publicación:** no requiere SQL ni nuevas variables de entorno. Incluye `config/version.php`, controlador, servicio, vistas y CSS compilado.

---

## 1.1.1 — 2026-10-27

**Tipo:** base inicial de numeración explícita acordada por el responsable. **Estado:** preparada en desarrollo, pendiente de publicación de este indicador.

- Se centraliza la versión en `config/version.php` y se muestra en el login en claro y oscuro.
- Se establece la regla MAYOR.MENOR.PARCHE, con instrucciones para personas e IA y registro separado de producción.
- No se reconstruye ni renumera retroactivamente el historial anterior. Las funcionalidades existentes se describen en Estado actual.

**Publicación:** no requiere SQL ni nuevas variables de entorno. Incluir `config/version.php`, la configuración, la vista y el CSS compilado correspondiente. No se publicó en Hostinger desde esta tarea.

**Validación:** sintaxis PHP correcta; 21 comprobaciones de configuración y 241 MVC correctas; prueba de login en Edge con fixtures, claro/oscuro y anchos 320/1280 sin desbordes. CSS del login compilado; la única regla generada añadida es el margen del indicador. No se enviaron formularios ni se escribieron datos de negocio.
