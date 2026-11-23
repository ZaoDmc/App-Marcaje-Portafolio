> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# CONTEXTO_PROYECTO.md

Actualización de personalización visual (2026-10-16): el practicante puede elegir en Mis datos entre los temas DaisyUI `light`, `dark`, `synthwave`, `aqua`, `coffee` y `black`; la preferencia se guarda por navegador y se aplica en todo el portal del practicante. El progreso conserva el componente radial DaisyUI con un anillo SVG compatible con Opera GX. La salida por permiso solo se ofrece durante una interrupción parcial aprobada y dentro de su intervalo horario; el fin de jornada sigue usando la marca SALIDA.
Actualización operativa de fase 6 (2026-10-15): incorporé tiempo excepcional solicitado por practicante con marcas completas y aprobación de SUPERVISOR/ADMIN con motivo manual de la autorización informal del jefe. El crédito no suma extras: habilita entrada posterior/salida anticipada con otra aprobación, reservas en minutos y reconocimiento solo del tramo autorizado no trabajado. Vence a las 168 horas exactas desde la solicitud, sin reiniciar por aprobar y sin borrar historial. El corte preserva jornadas existentes e importes extra aprobados; el formulario/POST antiguo no permite nuevas aprobaciones. Cambiar marcas fuente reabre crédito/canjes y retira su acumulado dependiente. Véase FASE_6_TIEMPO_EXCEPCIONAL.md para migración, política de corte, publicación y límites; la fase 7 de integración final sigue pendiente. Esta actualización sustituye las notas históricas siguientes que dejaban fase 6 pendiente.

Actualización visual (2026-10-20): las superficies propias del dashboard ADMIN usan las paletas oscuras compartidas, incluidos filtros, KPI, paneles y estados neutros. El panel de permiso parcial y su resumen utilizan clases semánticas del tema en lugar de fondos ámbar translúcidos. Sin cambios funcionales o de datos. Detalles y verificación en [MEJORAS_VISUALES_ADMIN_Y_PERMISOS.md](../visuales/MEJORAS_VISUALES_ADMIN_Y_PERMISOS.md).

## Notas: reglas vigentes y fases 1–6 (2026-10-19)

Actualización de acceso (2026-10-20): el menú PRACTICANTE presenta una sola entrada Notas para todos, con pestaña Mis notas y acceso desde el dashboard. La acción Evaluar mi área se habilita únicamente a líderes vigentes designados por ADMIN y se actualiza al recargar; no concede un rol global nuevo. Retirar liderazgo conserva consulta propia y bloquea evaluación. Autorización de servicios sin cambios. Véase [NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md](../notas/NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md). Sustituye la navegación anterior con dos enlaces separados.

Fase 6 validada localmente: 170 comprobaciones MariaDB de notas (incluida publicación concurrente), 354 de negocio sin fallos y 20 recorridos de navegador. Los cuatro fallos previos de negocio correspondían a un escenario que esperaba marcar un permiso pendiente/fuera de ventana; se corrigió la prueba conservando las reglas de producción. Diagnóstico CLI de solo lectura en scripts/check-notas.php; no viene incluido en el ZIP público ni aplica migraciones. Paquete local verificado, sin publicación en Hostinger, sin cambios de datos reales y sin SQL nuevo. Guía, evidencia y reversión en [FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md](../notas/FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md). Regenerar el paquete desde el commit final aprobado; rúbrica definitiva y aceptación productiva pendientes.

Fase 5 implementada: Acompañamiento, ficha seleccionada y detalle de reportes consultan una fuente común para la última nota semanal aprobada, con viernes y cantidad de evaluaciones aprobadas. El resumen abarca la práctica, sin promedios ni dependencia del periodo de asistencia. SUPERVISOR puede consultar el resumen publicado sin autosupervisarse ni obtener edición académica. El puntaje anterior de Acompañamiento permanece histórico de solo lectura, bloqueado en servidor y sin convertirlo en nota semanal. Sin migración nueva; reutiliza 009–011. Rúbrica definitiva pendiente de contenido del cliente; conserva versiones configurables. Véase [FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md](../notas/FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md). Esta decisión sustituye referencias históricas al puntaje editable.

Fase 4 implementada: revisión previa y confirmación explícita para aprobar/publicar; se exigen todos los criterios y nota final completos. Los líderes aprueban integrantes sin paso ADMIN; ADMIN aprueba líderes y corrige notas publicadas ajenas con motivo. ADMIN también puede resolver excepcionalmente un borrador sin responsable habilitado, con motivo y conservando autor/rúbrica/historial, según confirmación expresa del responsable. El líder nuevo no obtiene edición de notas anteriores. Mis notas (`practicante/mis-notas.php`) permite a todos los practicantes, incluidos líderes, consultar únicamente sus evaluaciones aprobadas, sin motivos, borradores ni historial interno. Las correcciones mantienen visible la versión anterior hasta confirmarse y conservan revisión/actor/fecha. No hay migración nueva ni cambios de datos del usuario en esta fase. Véase [FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md](../notas/FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md). Este estado sustituye las menciones siguientes que dejaban publicación pendiente.

Fase 3 implementada y probada: ADMIN accede a Evaluaciones, Áreas y líderes y Criterios; un líder vigente en contexto PRACTICANTE tiene Notas en su menú. Hay viernes pendientes/atrasados, formulario de borrador y edición administrativa con motivo y revisión esperada. ADMIN puede añadir/quitar filas y escribir los criterios creando una nueva versión global de rúbrica (1–50); notas y borradores existentes conservan sus criterios originales. Los diez rótulos provisionales no constituyen la rúbrica definitiva del cliente. Publicación y consulta del evaluado siguen en fase 4. Se aplicaron 010/011 a asistencia local con respaldo completo, conservando checksums de 36 tablas anteriores; Hostinger no se modificó. Esta actualización reemplaza las menciones de estado anteriores a esta fase. Véase [FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md](../notas/FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md).

Fase 2 implementada como base de servicio: notas_semanales identifica práctica/viernes, con diez posiciones, nota final manual, rúbrica provisional e historial de revisiones. Se permiten cargas cualquier día y viernes atrasados; este acuerdo sustituye las referencias anteriores a jueves. ADMIN consulta y corrige notas existentes de cualquier practicante con motivo e historial, manteniendo la prohibición de autoevaluación. Un líder nuevo registra faltantes de integrantes actuales pero no edita notas ni continúa borradores de otro líder; ADMIN resuelve esos borradores. Los formularios y la publicación corresponden a fases 3/4. El parche 010 se probó exclusivamente en bases ficticias; no se aplicó a asistencia ni producción. Detalles en [FASE_2_NOTAS_BASE_Y_PERMISOS.md](../notas/FASE_2_NOTAS_BASE_Y_PERMISOS.md).

Fase 1 implementada y probada localmente: `admin/notas.php` debajo de Reportes, catálogo de áreas y gestión/historial de líderes exclusiva de ADMIN. El servicio protege la exclusividad por área, rechaza formularios obsoletos y exige retirar el liderazgo antes de traslado, baja o desactivación del área. No existe todavía registro de notas semanales. Migración local aplicada; producción sin modificar. Véase [FASE_1_NOTAS_AREAS_Y_LIDERES.md](../notas/FASE_1_NOTAS_AREAS_Y_LIDERES.md).

Incidente local del mismo día: se rescató MariaDB de XAMPP tras error de checkpoint. Datos activos ahora en D:, servidor normal en 3306 y sin force_recovery; no confundir la carpeta activa con un respaldo para borrar. Ubicaciones, respaldo y límites en [INCIDENTE_MYSQL_LOCAL_20261019.md](../incidentes/INCIDENTE_MYSQL_LOCAL_20261019.md).

Periodicidad confirmada: notas semanales identificadas por viernes, registrables cualquier día, incluidas atrasadas, y conservadas durante toda la práctica. Se separan viernes evaluado y fecha real de carga, sin duplicados. El viernes actual puede seleccionarse desde el lunes; sábado y domingo se refieren al viernes recién pasado. Acumular no define sumas ni promedios; semanas parciales sin viernes y una eventual nota global mantienen alcance pendiente.

ADMIN registra y aprueba notas nuevas de líderes y tiene edición global de notas existentes; cada líder registra y aprueba las de sus integrantes sin aprobación ADMIN. El practicante consulta su evaluación después de la aprobación del responsable. Líder es una responsabilidad por área y práctica, distinta del rol global SUPERVISOR. Hay diez evaluaciones de 1 a 7 con paso 0,5, nota final manual y rúbrica descriptiva pendiente. Estas reglas sustituyen referencias históricas a aprobación ADMIN universal y falta de visibilidad global. El cierre de práctica conserva sus reglas propias. Plan y pendientes en [PLAN_IMPLEMENTACION_NOTAS.md](../planes/PLAN_IMPLEMENTACION_NOTAS.md).

## 1. Nombre del proyecto

Actualización visual de notificaciones (2026-10-16): añadí alertas de éxito DaisyUI 4.12.24 al marcaje del practicante y a las acciones exitosas de Asistencia para ADMIN/SUPERVISOR. El plugin usa clases `dui-` namespaced, sin reset ni utilidades DaisyUI globales. La demostración del login conserva su CSS independiente y tema claro/oscuro; las demás pantallas no se migran por este cambio. Guía de aprendizaje en LOGIN_DAISYUI.md. Plan de notas en PLAN_IMPLEMENTACION_NOTAS.md: módulo todavía no implementado; responsabilidades de aprobación actualizadas el 2026-10-19 en la sección de planificación vigente.

Regla posterior de tolerancia confirmada (2026-10-15): hasta treinta minutos por extremo, sin acumular adelanto de entrada y salida posterior. Ejemplo confirmado: veintinueve minutos antes y veintinueve después sigue válido sin sumar ni anunciar ese tiempo. El trabajo ordinario más compensación tiene tope en horas netas planificadas; recuperaciones validadas siguen separadas. Se aplica también margen de treinta minutos a atraso y salida anticipada, conservando solo horas efectivas. Colación ausente/incompleta, marcas futuras y validación de sábados no se omiten. Se preservan jornadas históricas anteriores al corte. Esta decisión sustituye el aviso automático por cualquier minuto fuera de horario descrito en la corrección anterior.

Corrección de fase 6 al probar en localhost (2026-10-15): conservar la observación del tiempo fuera de turno hasta aprobarlo como excepcional; suprimir las extras antiguas no debe convertir la jornada automáticamente en válida. La aprobación parcial solo libera el tramo cubierto. Los rechazos por salida/tramo futuro ahora lo explican expresamente y mantienen el formulario; no son validaciones del permiso de ausencia. Véase la corrección documentada en FASE_6_TIEMPO_EXCEPCIONAL.md.

**Portal de Asistencia y Control de Practicantes**

Proyecto web desarrollado en PHP + MySQL/MariaDB para controlar practicantes, asistencia, horarios, áreas, usuarios, roles, auditoría, reportes y administración.

Este documento es el contexto oficial para la implementación técnica del proyecto.

Actualización de arquitectura (fase 4, 2026-10-13): las páginas compartidas de gestión se preparan en `src/Controllers/Shared` antes del layout y se presentan mediante `src/Views/shared`; los campos reutilizables viven en `src/Views/components`. Véase `FASE_4_SEPARACION_MVC.md` para alcance y validación. Se conservan las rutas públicas y las reglas de fase 3.

Actualización de arquitectura (fase 5, 2026-10-13): se retiraron los puentes de raíz, páginas por rol e `includes`, manteniendo sus URLs mediante `public/index.php` y el catálogo explícito. El endpoint de horario diario ahora pertenece a `src/Controllers/Practicante`. Véase `FASE_5_RETIRO_PUENTES.md` y su inventario CSV. La entrada productiva antigua que incluye archivos del backend por URL debe sustituirse en un despliegue coordinado antes de publicar esta versión; no se modifica automáticamente el hosting.

Contexto funcional actualizado (2026-10-14): tiempo excepcional autorizado informalmente por jefe y aprobado en software por supervisor con motivo obligatorio; compensación durante siete días calendario desde la solicitud, venciendo el saldo sin borrar historial. Recuperaciones de permisos repartidas entre varios días, colaciones previamente solicitadas, extensiones y sábados entre 10:00 y 20:00, con validación obligatoria de sábados y autorización expresa para feriados. Días laborales según horario individual; preservar extras históricas al establecer el corte de las nuevas reglas. Véase PLAN_CORRECCIONES_OPERATIVAS.md. Estas reglas nuevas permanecen pendientes de implementación; esta primera rama solo separa cierres REVISADA/CORREGIDA, acumulados e informes.

Actualización operativa de fase 4 (2026-10-14): permisos para hoy con horario vigente e interrupciones intermedias mediante SALIDA_PERMISO/REGRESO_PERMISO. Por decisión explícita del responsable se permite solicitar con cruce de marcas en observación, pero la aprobación exige resolverlo; no basta un comentario para reconocer ausencia y trabajo simultáneos. Solicitudes observadas requieren motivo de resolución. Se mantienen colación independiente y una solicitud pendiente/aprobada por día. Detalle, migración y límites en FASE_4_PERMISOS_MISMO_DIA.md. Las recuperaciones distribuidas y el tiempo excepcional siguen pendientes de las fases 5/6.

Actualización operativa de fase 5 (2026-10-14): implementé recuperación por varios tramos de colación, extensión, sábado y feriado, con presupuesto en minutos y validación manual de trabajo efectivo. El responsable eligió calendario común mantenido por ADMIN y colación trabajada sin marcas de descanso durante el tramo autorizado. Cambiar evidencia reabre créditos; saldo incompleto permite solicitar nuevos tramos y requiere otra autorización. La ausencia original también debe estar terminada y no cruzar trabajo antes de reconocer recuperación. Generar ausencias quedó reservado a ADMIN por solicitud expresa. Véase FASE_5_RECUPERACION_VARIOS_DIAS.md para publicación, reglas y límites. Tiempo excepcional y compensación de siete días siguen pendientes de fase 6.

Actualización de publicación (fase 7, 2026-10-14): `scripts/build-release.php` prepara backend y frontend separados con dependencias de producción, lista positiva y manifiesto de hashes, excluyendo entorno y uploads. `public/assets` es ahora la única fuente del repositorio; las copias antiguas se retiraron con inventario. La estructura real comunicada es backend-rrhh fuera de public_html y una carpeta pública software-rrh/software-rrhh usada por el subdominio; las rutas de la sección 3 son referencias históricas, no destinos confirmados. La guía `FASE_7_DESPLIEGUE_HOSTINGER.md` exige respaldo y actualización coordinada; no ejecuta un despliegue ni concilia automáticamente los uploads productivos.

Actualización de validación (fase 8, 2026-10-14): agregué recorridos autenticados contra una copia aislada y bases ficticias rrhh_e2e_*, con MariaDB, Edge y SMTP local. Se corrigió la carga omitida del inicializador de gráficas del dashboard ADMIN. Véase FASE_8_VALIDACION_FLUJOS.md para los once escenarios aprobados, instrucciones, evidencia y límites; la validación local no sustituye la comprobación del hosting ni implementa las nuevas reglas de horas.

Actualización de presentación (correcciones operativas, entrega 2, 2026-10-14): las tablas del practicante presentan campos etiquetados bajo 1280 px y columnas ajustadas en escritorio; el layout acota estilos al rol PRACTICANTE. Se corrigieron desbordes por registros y textos largos, conservando zoom manual, semántica de tabla, datos completos y formularios. Véase PLAN_CORRECCIONES_OPERATIVAS.md y PROGRESO.md para validación y límites. Las reglas nuevas de recuperación y compensación permanecen pendientes.

Los documentos docs/CONTEXTO_PROYECTO_old.md y sus variantes son un histórico solo para mantener registro de cambios en el contexto.

Corrección operativa adicional (2026-10-14): la gestión de practicantes de SUPERVISOR/ADMIN también adapta listado, ficha seleccionada, áreas, horarios y excepciones al ancho de cada panel. Se ampliaron pruebas con registros cargados y apertura real de ficha; la entrega anterior solo había corregido el portal PRACTICANTE. Véase PLAN_CORRECCIONES_OPERATIVAS.md para evidencia e instrucciones.

Preferencia visual confirmada: la gestión operativa conserva tablas compactas en escritorio, incluido el horario semanal de siete filas y seis columnas. Se ajustan anchos/padding para evitar desplazamiento horizontal; la adaptación apilada se reserva a móviles, con dos campos de horario por fila. Esta decisión sustituye el apilado por panel que el responsable consideró demasiado largo.

Correcciones operativas, entrega 3 (2026-10-14): agregué confirmación antes de entrada, inicio/fin de colación y salida, con bloqueo del doble clic y alternativa de confirmación del servidor sin JavaScript. La marca utiliza hora del servidor al confirmar; cancelar no registra asistencia. Se preservan permisos, CSRF, secuencia y reglas de cálculo. El controlador exige confirmación explícita antes de crear/registrar la jornada; frontend y backend se publican coordinadamente.

Preferencia de confirmación: se utiliza window.confirm, la ventana nativa del navegador, en lugar del modal HTML inicialmente propuesto. La alternativa sin JavaScript permanece en página y se mantiene el bloqueo del envío duplicado.

Integración de marcaje: ui.js utiliza URL versionada por contenido para evitar reutilizar un script anterior. Al inicializar JS con confirm disponible, se oculta la alternativa HTML de un POST pendiente; sin JS sigue disponible. No se registra automáticamente la marca pendiente.

---

## 2. Stack oficial

El proyecto debe implementarse con:

- PHP 8.1 o superior de la serie 8.x; producción confirmada en PHP 8.3.35. Requisito incorporado en fase 1 para reflejar el uso de `never` y las dependencias de desarrollo.
- Composer con versiones bloqueadas en `composer.lock`; PHPMailer y phpdotenv compartidos con el despliegue productivo.
- MySQL / MariaDB
- PDO
- PHPMailer mediante SMTP
- HTML5
- CSS3
- JavaScript mínimo y controlado
- Tailwind CSS 3.4.17 compilado localmente, con configuración y lock de frontend (fase 6).
- Bootstrap Icons 1.11.3 como recurso local independiente del framework Bootstrap.
- Chart.js permitido solo para gráficos del dashboard y visualizaciones estadísticas
- Sesiones PHP seguras
- `password_hash()`
- `password_verify()`
- XAMPP para desarrollo local
- Hostinger Web Hosting como entorno productivo

El frontend puede utilizar HTML5 y CSS3 moderno sin problema, ya que estos recursos se ejecutan principalmente en el navegador del usuario y son compatibles con Hostinger Web Hosting.

Se permite utilizar:

- HTML5 semántico.
- CSS3 avanzado.
- Flexbox.
- CSS Grid.
- Variables CSS.
- Media queries responsive.
- Transiciones suaves.
- Sombras.
- Bordes redondeados.
- Gradientes.
- Pseudo-elementos.
- Tailwind CSS como base visual; Bootstrap CSS/JS retirado en fase 6.
- Bootstrap Icons local para iconografía.
- Chart.js para gráficos del dashboard, siempre de forma acotada y justificada.

Para pantallas visualmente relevantes, como el Dashboard Administrador, se conservan componentes Tailwind y estilos dedicados por módulo cuando aportan a la identidad visual, por ejemplo:

```text
assets/css/admin-dashboard.css

También se permite JavaScript acotado por pantalla cuando aporte valor real a la experiencia de usuario. Por ejemplo:

Renderizado de gráficos con Chart.js.
Interacciones simples del menú lateral.
Dropdowns.
Confirmaciones antes de acciones críticas.
Tooltips.
Validaciones frontend complementarias.
Mostrar u ocultar secciones de formularios.
Animaciones visuales livianas.
Filtros visuales básicos.

La lógica crítica del sistema debe permanecer en PHP/backend. JavaScript no debe reemplazar validaciones, permisos, reglas de negocio ni auditoría.

Regla general del stack:

PHP decide.
MySQL/MariaDB guarda.
PDO consulta.
HTML5 estructura.
CSS3 diseña.
Tailwind compilado presenta.
JavaScript mejora la interacción.
Chart.js dibuja gráficos.


No usar en esta etapa:

Laravel
Symfony
Node.js
React
Vue
Angular
Backend en Python
Microservicios
SPA separada
API REST separada innecesaria
Login genérico por rol
Login genérico de practicante
Auto-registro libre de practicantes
Dependencias npm innecesarias
Renderizado completo de vistas desde JavaScript
---

## 3. Entorno local y hosting

Ruta local oficial:

```text
C:\xampp\htdocs\asistencia\
```

URL local oficial:

```text
http://localhost/asistencia/
```

Ruta esperada en Hostinger:

```text
/public_html/asistencia/
```

Posible ruta en dominio:

```text
https://ofertasimperdibles.cl/asistencia/
```

Posible subdominio futuro:

```text
https://portal.ofertasimperdibles.cl/
```

El sistema debe evitar rutas quemadas para que pueda funcionar en local, ruta `/asistencia/` o subdominio futuro sin reescribir todo el código.

---

## 4. Tipo de arquitectura

Actualización de fase 2 (2026-10-13): agregué un catálogo explícito en `config/routes.php` y el front controller compartido `public/index.php`, con descubrimiento relativo del backend en `public/backend.php`. Local utiliza `.htaccess` de la raíz para pasar las entradas tradicionales al mismo catálogo; producción debe actualizar el contenido público y el backend de forma coordinada. Se conservan URLs, parámetros y puentes físicos hasta su limpieza posterior. Archivos privados y uploads directos quedan bloqueados en las reglas propuestas. Detalles y límites de validación en `docs/historial/mvc-tailwind/FASE_2_ENRUTAMIENTO.md`.

La arquitectura será:

```text
Monolito modular escalable en PHP
```

Esto significa:

Es monolítica en despliegue: una sola aplicación PHP.
Es modular en organización interna: carpetas separadas por responsabilidad.
Es por capas: configuración, conexión, autenticación, autorización, reglas de negocio, auditoría, vistas, assets y acceso a datos.
No es PHP plano desordenado.
No es una SPA.
No es una arquitectura de microservicios.
No requiere una API REST separada para la operación interna del sistema.

El sistema debe mantener separación clara de responsabilidades:

/config        → configuración general, base de datos, correo y constantes.
/public        → punto de entrada público, assets accesibles y rutas públicas si aplica.
/assets        → CSS, JavaScript, imágenes, logos e iconografía.
/includes      → layouts, componentes reutilizables, helpers visuales.
/auth          → login, logout, activación, recuperación de contraseña.
/admin         → vistas y módulos exclusivos del rol ADMIN.
/supervisor    → vistas y módulos del rol SUPERVISOR.
/practicante   → vistas y módulos del rol PRACTICANTE.
/src o /app     → funciones de negocio, consultas, servicios internos y utilidades.

La interfaz debe renderizarse principalmente desde PHP, utilizando vistas HTML5 y componentes reutilizables. CSS3 y Tailwind compilado son responsables de la presentación visual; las pantallas clave conservan estilos propios cuando sea necesario. Bootstrap CSS/JS se retiró en fase 6.

JavaScript se usará como complemento, no como arquitectura principal. Su uso debe mantenerse encapsulado por pantalla o módulo. Por ejemplo, para el Dashboard Administrador se permite:

assets/js/admin-dashboard.js

Este archivo puede encargarse de:

Inicializar gráficos Chart.js.
Recibir datos generados por PHP mediante json_encode.
Controlar interacciones visuales simples.
Aplicar comportamientos de UX no críticos.

Las reglas críticas deben permanecer en PHP, incluyendo:

Validación de sesión.
Control de acceso por rol.
Permisos.
Validaciones de negocio.
Consultas PDO.
Auditoría.
Cambios de estado.
Correcciones de asistencia.
Asignación de áreas.
Activación y recuperación de cuentas.

No se usará microservicios porque el stack oficial es Hostinger Web Hosting + PHP + MySQL/MariaDB.

No se debe convertir el sistema en una aplicación frontend independiente. El objetivo es mantener un monolito PHP modular, escalable, mantenible y compatible con hosting compartido, con una interfaz basada en HTML5, CSS3, Tailwind compilado, iconografía local y JavaScript controlado.

---

## 5. Principio general de implementación

El proyecto debe seguir este enfoque:

```text
Base de datos robusta desde el inicio.
Implementación incremental por etapas.
Arquitectura simple, mantenible y escalable.
```

No se busca un MVP pobre que luego obligue a rehacer todo.

La BBDD debe quedar lo más cercana posible a una versión definitiva, aunque algunas funcionalidades queden solo modeladas y se implementen más adelante.

---

## 6. Configuración centralizada

Actualización de fase 1 (2026-10-13): agregué `config/environment.php` como cargador común de phpdotenv y reemplacé el parser manual de `.env`. Las variables preexistentes se respetan, incluidas cadenas vacías. `app_env()` consulta proceso, `$_ENV` y `$_SERVER`; `APP_DEBUG` y `MAIL_DEBUG` permanecen desactivados en producción. `.env.example` documenta la configuración sin secretos; Composer y el lock se versionan, mientras `.env`, vendor y uploads se excluyen de Git. Detalles en `docs/historial/mvc-tailwind/FASE_1_CONFIGURACION.md`.

Debe existir el archivo:

```text
/config/app.php
```

Este archivo debe centralizar la configuración general del sistema.

Debe incluir constantes como:

```php
APP_ENV
APP_NAME
BASE_URL
BASE_PATH
ASSETS_URL
SESSION_NAME
SESSION_TIMEOUT_MINUTES
REMEMBER_SESSION_DAYS
TOKEN_EXPIRATION_HOURS
MAX_LOGIN_ATTEMPTS
LOGIN_LOCK_MINUTES
```

Reglas:

- No usar URLs rígidas en redirects.
- No usar rutas quemadas en formularios.
- No usar rutas quemadas en assets.
- No usar rutas quemadas en enlaces internos.
- No usar rutas quemadas en generación de tokens.
- Toda ruta debe depender de `BASE_URL`, `BASE_PATH` o helpers derivados.

---

## 7. Estructura inicial de carpetas

Usar esta estructura base:

```text
asistencia/
├── index.php
├── login.php
├── logout.php
├── activar-cuenta.php
├── recuperar-password.php
├── restablecer-password.php
├── seleccionar-rol.php
│
├── config/
│   ├── app.php
│   ├── database.php
│   └── mail.php
│
├── includes/
│   ├── auth.php
│   ├── helpers.php
│   ├── auditoria.php
│   ├── mailer.php
│   └── reglas_asistencia.php
│
├── admin/
│   ├── dashboard.php
│   ├── practicantes.php
│   ├── crear-practicante.php
│   └── usuarios.php
│
├── supervisor/
│   ├── dashboard.php
│   ├── practicantes.php
│   └── crear-practicante.php
│
├── practicante/
│   ├── dashboard.php
│   ├── mis-datos.php
│   ├── marcar-asistencia.php
│   └── mi-historial.php
│
├── database/
│   ├── schema.sql
│   ├── seed_catalogos.sql
│   └── seed_admin.sql
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── img/
│   └── brand/
│
└── vendor/
```

Regla:

```text
assets/brand debe reservarse para el logo del centro de prácticas, isotipo, favicon, paleta visual y recursos institucionales de marca.
```
---

## 8. Primera etapa funcional

La primera etapa debe implementar funcionalmente:

1. Configuración centralizada.
2. Base de datos v1.1.
3. Seeds iniciales.
4. Conexión PDO.
5. Login individual seguro.
6. Roles reales.
7. Activación de cuenta por token.
8. Recuperación de contraseña con PHPMailer.
9. Selector de contexto para usuarios con múltiples roles.
10. Paneles básicos por rol.
11. Pre-registro de practicantes por ADMIN/SUPERVISOR.
12. Edición de datos propios por PRACTICANTE.
13. Marcación básica de asistencia.
14. Auditoría base.

---

## 9. Funcionalidades modeladas, pero no necesariamente completas en etapa 1

Estas funcionalidades deben quedar consideradas en la base de datos o arquitectura, pero no tienen que estar completamente funcionales en la primera etapa:

- Dashboard avanzado.
- Exportación XLSX real.
- Evaluaciones de practicantes.
- Migración desde Google Forms.
- Bloques de área por jornada.
- Excepciones avanzadas de horario.
- Reportes filtrados.
- Ranking de practicantes destacados.
- Métricas institucionales avanzadas.

---

## 10. Archivos principales de configuración

Crear:

```text
/config/app.php
/config/database.php
/config/mail.php
```

### `/config/app.php`

Responsabilidades:

- Definir entorno.
- Definir URL base.
- Definir ruta base.
- Definir ruta de assets.
- Definir configuración de sesiones.
- Definir expiración de tokens.
- Definir intentos máximos de login.
- Definir bloqueo temporal de login.

### `/config/database.php`

Responsabilidades:

- Crear conexión PDO.
- Configurar DSN.
- Configurar charset.
- Activar excepciones PDO.
- Usar `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
- Usar `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.
- Usar `PDO::ATTR_EMULATE_PREPARES => false`.

### `/config/mail.php`

Responsabilidades:

- Definir host SMTP.
- Definir puerto SMTP.
- Definir usuario SMTP.
- Definir contraseña SMTP.
- Definir remitente.
- Definir nombre del remitente.
- Definir modo debug según `APP_ENV`.

---

## 11. Login seguro

El sistema usará login individual desde el MVP.

Ingreso mediante:

```text
email
contraseña
```

No usar:

```text
username como login principal
login genérico por rol
login genérico de practicante
selector de rol antes del login
```

Flujo esperado:

1. Usuario ingresa email y contraseña.
2. PHP consulta `usuarios_sistema` por email.
3. Valida que el usuario exista.
4. Valida `activo`.
5. Valida `estado_cuenta`.
6. Verifica bloqueo temporal mediante `bloqueado_hasta`.
7. Valida contraseña con `password_verify()`.
8. Consulta roles activos desde `usuarios_roles`.
9. Crea sesión segura.
10. Ejecuta `session_regenerate_id(true)`.
11. Si tiene un solo rol, redirige a su panel.
12. Si tiene múltiples roles, redirige a `seleccionar-rol.php`.

Regla opcional de permanencia:

- La pantalla de login puede ofrecer `Mantener la sesión iniciada`.
- Si el usuario no marca esa opción, aplica el timeout por inactividad definido en `SESSION_TIMEOUT_MINUTES`.
- Si el usuario marca esa opción, la cookie de sesión puede mantenerse por `REMEMBER_SESSION_DAYS` y el usuario puede volver a abrir la URL del portal sin iniciar sesión nuevamente.
- El logout manual siempre debe cerrar la sesión real, destruir la cookie y marcar la fila operativa de `sesiones_usuario` como cerrada.
- Esta opción no cambia roles, permisos, auditoría ni el flujo de selección de rol.

---

## 12. Roles iniciales

Roles obligatorios:

```text
ADMIN
SUPERVISOR
PRACTICANTE
```

### ADMIN

Puede:

- Acceso completo.
- Crear practicantes.
- Editar practicantes.
- Crear usuarios.
- Crear supervisores.
- Gestionar roles.
- Dar de baja logicamente practicantes.
- Corregir asistencia.
- Anular asistencia.
- Cambiar áreas.
- Cerrar práctica.
- Realizar evaluaciones finales.
- Exportar XLSX.
- Ver auditoría.

### SUPERVISOR

Puede:

- Ver dashboard limitado.
- Crear/pre-registrar practicantes.
- Editar datos personales de practicantes.
- Ver detalle operativo de practicantes.
- Corregir asistencia.
- Observar registros.
- Marcar `PENDIENTE_REVISION`.
- Resolver correcciones.
- Asignar/cambiar área.
- Exportar XLSX limitado.

No puede:

- Anular asistencia.
- Crear roles.
- Crear usuarios supervisores.
- Cambiar configuración global.
- Cerrar prácticas.
- Realizar evaluaciones finales.

### PRACTICANTE

Puede:

- Iniciar sesión con cuenta individual.
- Ver panel practicante.
- Ver pantalla `Mi Perfil` con foto, datos propios y documentos.
- Editar sus propios datos permitidos.
- Subir foto de perfil y documentos opcionales de práctica desde sus datos.
- Marcar asistencia.
- Ver historial.
- Ver horas realizadas.
- Ver horas restantes.
- Ver porcentaje de avance.

No puede:

- Corregir asistencia.
- Anular asistencia.
- Crear cuentas.
- Crear usuarios.
- Cambiar su práctica.
- Modificar horas requeridas.
- Modificar institución/carrera/tipo de práctica.

Regla de documentos:

- La foto de perfil y los documentos de práctica son opcionales.
- La falta de archivos no bloquea el cierre inicial de ficha.
- El practicante puede cargarlos durante `Completar registro` o posteriormente desde `Mi Perfil`.
- Foto: JPG, JPEG o PNG, máximo 5 MB.
- Documentos: PDF, Word o Excel, máximo 5 documentos activos y 5 MB por archivo.
- La metadata debe conservarse con baja lógica, no borrado físico.
- `Mi Perfil` debe mostrar la foto como avatar profesional; si no existe foto, debe mostrar iniciales.
- `Mi Perfil` debe mostrar una accion visible para `Subir foto` o `Cambiar foto`.
- Los documentos activos deben listarse sin exponer rutas internas.
- `Mi Perfil` debe mostrar una accion visible para `Subir documentos`.
- La descarga de documentos del practicante debe validar sesión, rol, documento activo y propiedad del practicante.

Regla de entrada inicial:

- Si la ficha del PRACTICANTE esta incompleta, el primer destino sigue siendo `practicante/mis-datos.php?completar=1`.
- Si la ficha ya esta completa, el primer destino del rol PRACTICANTE debe ser `practicante/marcar-asistencia.php`, no el panel practicante.

---

## 13. Usuarios con múltiples roles

Se acepta que un usuario pueda tener más de un rol.

Casos permitidos:

```text
ADMIN externo: id_practicante NULL + rol ADMIN
SUPERVISOR externo: id_practicante NULL + rol SUPERVISOR
ADMIN + SUPERVISOR externo: id_practicante NULL + roles ADMIN y SUPERVISOR
PRACTICANTE: id_practicante con valor + rol PRACTICANTE
PRACTICANTE + SUPERVISOR: id_practicante con valor + roles PRACTICANTE y SUPERVISOR
```

Caso no permitido:

```text
PRACTICANTE + ADMIN
```

Regla oficial:

```text
Un practicante puede tener rol SUPERVISOR, pero no puede tener rol ADMIN.
```

Flujo:

1. Usuario inicia sesión.
2. Backend detecta roles activos.
3. Si tiene un solo rol, se define ese rol como contexto activo.
4. Si tiene múltiples roles, redirige a `seleccionar-rol.php`.
5. Usuario elige contexto activo.
6. Sistema guarda `rol_activo` e `id_rol_activo` en sesión PHP.
7. Si se usa tabla `sesiones_usuario`, el sistema registra la sesión solo cuando ya existe un rol activo definido.

Reglas:

- El selector ocurre después del login, nunca antes.
- El rol activo condiciona dashboard, menú, permisos, acciones disponibles y auditoría.
- Si un usuario tiene rol `ADMIN`, debe tener `usuarios_sistema.id_practicante NULL`.
- Si `usuarios_sistema.id_practicante IS NOT NULL`, el sistema no debe permitir asignar rol `ADMIN`.
- La combinación `PRACTICANTE + SUPERVISOR` sí queda permitida.
- Durante un login multirrol, antes de seleccionar rol, se puede usar una sesión PHP temporal para guardar `usuario_pendiente_rol` y `roles_disponibles`, pero no debe considerarse una sesión operativa completa.
---

## 14. Regla practicante-supervisor

Un usuario que sea practicante y supervisor mantiene sus obligaciones como practicante:

- Debe marcar asistencia.
- Debe cumplir horario.
- Debe completar horas.
- Conserva historial como practicante.

Pero no puede autosupervisarse.

No puede, desde el rol supervisor:

- Corregir su propia asistencia.
- Aprobar sus propias horas extra.
- Evaluar su propio desempeño.
- Cambiar su propia área.
- Resolver observaciones propias.
- Cerrar su propia práctica.
- Anular registros propios.

Esta regla debe aplicarse con validación explícita en backend, idealmente mediante una función reusable en `includes/auth.php` o `includes/helpers.php`.

Ejemplo conceptual:

```php
if ($rolActivo === 'SUPERVISOR' && $idPracticanteObjetivo === $_SESSION['id_practicante']) {
    // Denegar acción por conflicto de interés.
}
```

---

## 15. Tablas de autenticación

Crear:

```text
roles_sistema
usuarios_sistema
usuarios_roles
sesiones_usuario
tokens_usuario
```

### `usuarios_sistema`

Debe considerar:

```text
id_usuario
id_practicante NULL
email
password_hash
estado_cuenta
debe_cambiar_password
email_verificado
intentos_fallidos
ultimo_intento_login
bloqueado_hasta
ultimo_login
activo
created_at
updated_at
```

Estados de cuenta:

```text
PENDIENTE_ACTIVACION
ACTIVO
BLOQUEADO
INACTIVO
```

Reglas:

- `ADMIN` y `SUPERVISOR` pueden tener `id_practicante NULL`.
- `PRACTICANTE` debe quedar vinculado a `practicantes.id_practicante`.
- `PRACTICANTE + SUPERVISOR` queda permitido.
- `PRACTICANTE + ADMIN` no está permitido.
- Todo usuario con rol `ADMIN` debe tener `id_practicante NULL`.
- Si `id_practicante IS NOT NULL`, no se debe permitir asignar rol `ADMIN`.
- Un usuario puede tener varios roles mediante `usuarios_roles`.
- `bloqueado_hasta` se usa para bloqueo temporal por intentos fallidos.
- `estado_cuenta = BLOQUEADO` se reserva para bloqueo administrativo.
- `activo = 0` representa desactivación lógica de la cuenta.

### `sesiones_usuario`

Debe registrar el contexto activo de acceso elegido por el usuario después del login.

Campos recomendados:

```text
id_sesion
id_usuario
id_rol_activo
session_id_hash
ip
user_agent
fecha_inicio
fecha_ultimo_acceso
fecha_cierre
cerrada_por_logout
activa
created_at
updated_at
```

Reglas:

- `id_rol_activo` debe ser `NOT NULL`.
- No se debe insertar un registro operativo en `sesiones_usuario` hasta que exista un rol activo definido.
- Si el usuario tiene un solo rol activo, ese rol queda como `id_rol_activo` automáticamente y se puede registrar la sesión inmediatamente después del login exitoso.
- Si el usuario tiene múltiples roles activos, `id_rol_activo` se define después de seleccionar contexto en `seleccionar-rol.php`; recién entonces se debe insertar el registro en `sesiones_usuario`.
- Durante el paso intermedio de selección de rol se puede usar `$_SESSION['usuario_pendiente_rol']` y `$_SESSION['roles_disponibles']`, pero esa etapa no debe generar aún una sesión operativa en BBDD.
- El rol activo condiciona menú, dashboard, permisos, acciones disponibles y auditoría.
- Al cerrar sesión, se debe marcar `fecha_cierre`, `cerrada_por_logout` y `activa = 0`.
- Se debe guardar `session_id_hash`, no el `session_id` plano.
- `id_rol_activo` debe corresponder a un rol realmente asignado al usuario en `usuarios_roles`.
---

## 16. Control de intentos fallidos

Regla oficial:

```text
Después de 3 intentos fallidos, bloquear temporalmente por 15 minutos.
```

Usar:

```text
MAX_LOGIN_ATTEMPTS = 3
LOGIN_LOCK_MINUTES = 15
```

No usar `estado_cuenta = BLOQUEADO` para este caso.

Usar:

```text
bloqueado_hasta
intentos_fallidos
ultimo_intento_login
```

---

## 17. Tokens de usuario

La tabla `tokens_usuario` debe soportar distintos tipos de token.

Tipos mínimos:

```text
ACTIVACION_CUENTA
RECUPERACION_PASSWORD
```

Campos recomendados:

```text
id_token
id_usuario
tipo_token
token_hash
fecha_expiracion
usado
fecha_uso
created_at
```

Reglas:

- Guardar hash del token, no el token plano.
- El token real solo se muestra/envía una vez.
- Los tokens deben expirar.
- Los tokens usados deben invalidarse.
- La generación, regeneración y uso de tokens debe auditarse.

---

## 18. Activación de cuenta

El practicante no se auto-registra libremente.

Flujo:

1. ADMIN o SUPERVISOR pre-registra practicante.
2. Se crea registro en `practicantes`.
3. Se crea registro en `practicas`.
4. Se crea usuario asociado en `usuarios_sistema`.
5. Se vincula `usuarios_sistema.id_practicante`.
6. Cuenta queda `PENDIENTE_ACTIVACION`.
7. Se genera token temporal.
8. Se guarda hash del token en `tokens_usuario`.
9. Se envía enlace de activación con PHPMailer.
10. Si falla SMTP, el sistema muestra enlace temporal para copiar manualmente.
11. Se registra auditoría.
12. Practicante define su contraseña.
13. Cuenta queda `ACTIVO`.

Reglas:

- ADMIN puede crear/pre-registrar practicantes.
- SUPERVISOR puede crear/pre-registrar practicantes.
- PRACTICANTE no puede crear cuentas.
- Solo ADMIN puede crear usuarios SUPERVISOR.
- SUPERVISOR no puede crear otros supervisores.
- La creacion/asignacion basica de usuarios SUPERVISOR se realiza desde `admin/usuarios.php`.
- Si el correo no existe, la interfaz crea un SUPERVISOR externo con `id_practicante NULL`.
- Los supervisores externos creados desde interfaz deben quedar `PENDIENTE_ACTIVACION`, con rol `SUPERVISOR`, token `ACTIVACION_CUENTA` y sin contrasena visible.
- Si el correo ya pertenece a un usuario existente, la interfaz debe asignar el rol `SUPERVISOR` a esa cuenta sin crear otro usuario ni generar una contrasena nueva.
- Si el usuario existente esta vinculado a practicante, la combinacion resultante `PRACTICANTE + SUPERVISOR` queda permitida y debe usar selector de rol despues del login cuando corresponda.
- ADMIN puede quitar el rol `SUPERVISOR` a un usuario vinculado a practicante, dejando activo el rol `PRACTICANTE` y sin desactivar la cuenta.
- La remocion de `SUPERVISOR` debe ser logica mediante `usuarios_roles.rol_activo = 0`, no borrado fisico, y debe auditarse como `ROL_REMOVIDO`.
- Si SMTP no esta configurado, la pantalla ADMIN puede mostrar enlace manual de activacion para pruebas locales.

---

## 19. Recuperación de contraseña

Debe quedar funcional desde el MVP.

Flujo:

1. Usuario solicita recuperación por email.
2. Sistema genera token temporal.
3. Guarda hash del token.
4. Envía enlace por PHPMailer.
5. Usuario define nueva contraseña.
6. Se guarda con `password_hash()`.
7. Se invalida token.
8. Se audita el cambio.

Reglas:

- No enviar contraseñas reales por correo.
- No revelar si un email existe o no.
- Usar respuesta genérica tipo: “Si el correo existe, se enviaron instrucciones”.

---

## 20. Seguridad de sesión

Aplicar:

```text
session_regenerate_id(true) después del login
timeout por inactividad
opción controlada de mantener sesión iniciada con expiración centralizada
logout real destruyendo sesión
cookies HttpOnly
cookies Secure cuando haya HTTPS
validación de sesión en cada página privada
validación de rol/permisos en backend
control de intentos fallidos
bloqueo temporal
```

---

## 21. Practicantes

Tabla `practicantes` debe considerar:

```text
id_practicante
rut_numero
rut_dv
nombre_1
nombre_2
apellido_paterno
apellido_materno
email
telefono
fecha_nacimiento
sexo
nacionalidad
direccion
numero_direccion
departamento
id_comuna
pais
id_region
id_institucion
id_sede INT NULL
institucion_otra VARCHAR(150) NULL
sede_otra VARCHAR(150) NULL
id_carrera
semestre
activo
created_at
updated_at
```

Campos obligatorios:

```text
Pre-registro por ADMIN o SUPERVISOR:
nombre_1
apellido_paterno
email

Ficha completa del practicante:
rut_numero
rut_dv
nombre_1
apellido_paterno
email
fecha_nacimiento
sexo
nacionalidad
direccion
numero_direccion
id_comuna
pais
id_region
id_institucion
id_carrera
semestre
```

Reglas de pre-registro y cierre de ficha:

```text
El formulario de pre-registro puede mostrar todos los campos disponibles, pero solo nombre_1, apellido_paterno y email son obligatorios.
Los campos no informados en el pre-registro deben poder quedar NULL hasta que el practicante complete su ficha.
Despues de activar la cuenta e iniciar sesion por primera vez, el PRACTICANTE con ficha incompleta debe ser redirigido automaticamente al formulario de completar registro.
El PRACTICANTE puede completar una vez los datos faltantes necesarios para cerrar su ficha.
Despues de completar la ficha, el PRACTICANTE mantiene editables sus datos de contacto y direccion: telefono, nacionalidad, direccion, numero_direccion, departamento, id_region, id_comuna y pais.
El PRACTICANTE no puede modificar libremente despues de completar ficha los datos de institucion, sede, carrera, tipo de practica, horas requeridas ni fechas de practica.
El horario pactado puede informarse durante el pre-registro por ADMIN/SUPERVISOR o durante el cierre inicial de ficha por el PRACTICANTE si aun no existe.
Para cerrar ficha completa debe existir al menos un dia con jornada pactada.
El ADMIN mantiene siempre la potestad de modificar los datos del practicante segun los permisos del sistema.
Todos los campos obligatorios en formularios deben mostrarse con asterisco rojo y una nota visible que indique que los campos marcados con * son obligatorios.
```

Reglas de institución y sede:

```text
Si id_institucion corresponde a Duoc UC, INACAP o ENAC:
- id_sede es obligatorio.
- institucion_otra debe quedar NULL.
- sede_otra debe quedar NULL.
- La sede debe seleccionarse desde el catálogo normalizado sedes.

Si id_institucion corresponde a Otra:
- institucion_otra es obligatorio.
- sede_otra es obligatorio.
- id_sede debe quedar NULL o no usarse operativamente.
- No se debe contaminar el catálogo oficial sedes con texto libre ingresado por usuarios.
```

Regla general:

Los datos específicos de práctica deben vivir principalmente en tabla `practicas`, no directamente en `practicantes`.

---

## 22. Tabla `practicas`

Decisión oficial:

Crear tabla `practicas` separada.

Relación:

```text
Un practicante puede tener muchas prácticas.
Una práctica pertenece a un solo practicante.
```

Esto permite historial de prácticas por persona.

Campos recomendados:

```text
id_practica
id_practicante
id_tipo_practica
id_estado_practica
horas_requeridas
fecha_inicio_practica
fecha_termino_practica
motivo_interrupcion
observacion_estado
activo
created_at
updated_at
```

Regla:

`tipos_practica.horas_base` funciona como valor sugerido desde catálogo.

`practicas.horas_requeridas` guarda el valor definitivo aplicado a esa práctica, aunque duplique el dato base. Esta duplicación es intencional para congelar el valor histórico.

---

## 23. Catálogos

Crear catálogos:

```text
regiones
comunas
instituciones
sedes
carreras
tipos_practica
estados_practica
areas_practica
tipos_marca
roles_sistema
```

### Regiones y comunas

Las regiones y comunas de Chile deben existir como catalogos reutilizables en BBDD.

Tabla `regiones`:

```text
id_region
nombre_region
activo
created_at
updated_at
```

Tabla `comunas`:

```text
id_comuna
id_region
nombre_comuna
activo
created_at
updated_at
```

Reglas de formulario y backend:

```text
El usuario selecciona primero una region.
El campo comuna se carga dinamicamente con las comunas de esa region.
Comuna no debe ser texto libre.
No se permite guardar una comuna que no pertenezca a la region seleccionada.
La validacion debe existir en frontend y backend.
```

### Instituciones iniciales

```text
Duoc UC
INACAP
ENAC
Otra
```

### Sedes

La tabla `sedes` debe funcionar como catálogo formal normalizado por institución.

Estructura obligatoria:

```text
id_sede
id_institucion
nombre_sede
codigo_sede
activo
created_at
updated_at
```

Reglas:

- No usar `sedes.nombre` como campo principal.
- No usar `sedes.direccion` en esta etapa.
- Para `Duoc UC`, `INACAP` y `ENAC`, la sede debe seleccionarse desde catálogo normalizado.
- Para `Otra`, el usuario debe escribir manualmente `institucion_otra` y `sede_otra`.
- No se debe contaminar el catálogo oficial `sedes` con texto libre ingresado por usuarios.
- Mantener una sede fallback `No especificada` por institución solo para casos excepcionales o migraciones incompletas.

Índices recomendados:

```text
UNIQUE(id_institucion, codigo_sede)
INDEX(id_institucion)
INDEX(activo)
```

Regla para formularios:

```text
Si el usuario selecciona Duoc UC, INACAP o ENAC:
- Mostrar selector desplegable de sedes.
- Guardar id_institucion e id_sede.
- No mostrar inputs manuales de institución o sede.

Si el usuario selecciona Otra:
- Mostrar input manual "Ingrese su institución".
- Mostrar input manual "Ingrese su sede".
- Guardar id_institucion apuntando a Otra.
- Guardar institucion_otra y sede_otra.
- id_sede debe quedar NULL o no usarse operativamente.
```

Regla para seeds:

```text
Cuando llegue la etapa de seed_catalogos.sql, se deberán cargar las sedes normalizadas de Duoc UC, INACAP y ENAC, además de una sede fallback "No especificada" por institución para casos excepcionales o migraciones incompletas.
```

Sedes normalizadas Duoc UC:

```text
Campus Virtual - VIRTUAL
Sede Alameda - ALAMEDA
Sede Padre Alonso de Ovalle - PAO
Sede Antonio Varas - AVARAS
Sede Maipú - MAIPU
Sede Melipilla - MELIPILLA
Sede Plaza Norte - PLAZA_NORTE
Sede Plaza Oeste - PLAZA_OESTE
Sede Plaza Vespucio - PLAZA_VESPUCIO
Sede Puente Alto - PUENTE_ALTO
Sede San Bernardo - SAN_BERNARDO
Sede San Carlos de Apoquindo - SAN_CARLOS_APOQUINDO
Sede San Joaquín - SAN_JOAQUIN
Sede Valparaíso - VALPARAISO
Sede Viña del Mar - VINA_DEL_MAR
Campus Arauco - ARAUCO
Campus Nacimiento - NACIMIENTO
Sede San Andrés de Concepción - SAN_ANDRES_CONCEPCION
Campus Villarrica - VILLARRICA
Sede Puerto Montt - PUERTO_MONTT
Duoc UC - No especificada - NO_ESPECIFICADA
```

Sedes normalizadas INACAP:

```text
INACAP Arica - ARICA
INACAP Iquique - IQUIQUE
INACAP Calama - CALAMA
INACAP Antofagasta - ANTOFAGASTA
INACAP Copiapó - COPIAPO
INACAP La Serena - LA_SERENA
INACAP Valparaíso - VALPARAISO
INACAP Apoquindo - APOQUINDO
INACAP Maipú - MAIPU
INACAP Renca - RENCA
INACAP Ñuñoa - NUNOA
INACAP Santiago Centro - SANTIAGO_CENTRO
INACAP Santiago Sur - SANTIAGO_SUR
INACAP La Granja - LA_GRANJA
INACAP Puente Alto - PUENTE_ALTO
INACAP Sede Central - SEDE_CENTRAL
INACAP Rancagua - RANCAGUA
INACAP Curicó - CURICO
INACAP Talca - TALCA
INACAP Chillán - CHILLAN
INACAP Concepción-Talcahuano - CONCEPCION_TALCAHUANO
INACAP San Pedro de la Paz - SAN_PEDRO_DE_LA_PAZ
INACAP Los Ángeles - LOS_ANGELES
INACAP Temuco - TEMUCO
INACAP Valdivia - VALDIVIA
INACAP Osorno - OSORNO
INACAP Puerto Montt - PUERTO_MONTT
INACAP Coyhaique - COYHAIQUE
INACAP Punta Arenas - PUNTA_ARENAS
INACAP Online - ONLINE
INACAP - No especificada - NO_ESPECIFICADA
```

Sedes normalizadas ENAC:

```text
ENAC 1 - Edificio Padre Baldo Santi - ENAC_1
ENAC 2 - Edificio Elisa Sanfuentes - ENAC_2
ENAC 4 - Edificio Mercedes Ezquerra - ENAC_4
ENAC - No especificada - NO_ESPECIFICADA
```

### Carreras iniciales

```text
Analista Programador
Ingeniería en Informática
Técnico en Informática
Técnico en Administración
Técnico en Logística
Comercio Exterior
Marketing
Recursos Humanos
Contabilidad
Diseño Gráfico
Otra
```

### Tipos de práctica

```text
Laboral: 180 horas
Profesional: 360 horas
Profesional extendida: 540 horas
Otra: manual
```

### Estados de práctica

```text
PENDIENTE_INICIO
EN_PROGRESO
COMPLETADA
PAUSADA
INTERRUMPIDA
ANULADA
```

---

## 24. Asistencia

Usar tablas:

```text
jornadas_asistencia
marcas_asistencia
tipos_marca
```

No usar `asistencias` como tabla principal.

### Tipos de marca

```text
ENTRADA
ENTRADA_COLACION
SALIDA_COLACION
SALIDA
```

### Secuencia ideal

```text
ENTRADA → ENTRADA_COLACION → SALIDA_COLACION → SALIDA
```

### Opciones permitidas

```text
Sin marca previa: ENTRADA.
Después de ENTRADA: ENTRADA_COLACION o SALIDA.
Después de ENTRADA_COLACION: SALIDA_COLACION o SALIDA.
Después de SALIDA_COLACION: SALIDA.
Después de SALIDA: ninguna.
```

### Anulación lógica granular de marcas

La tabla `marcas_asistencia` debe permitir anulación lógica individual de una marca sin borrarla físicamente.

Para permitir correcciones con trazabilidad, no debe existir una restricción única simple sobre `id_jornada` + `id_tipo_marca`, porque una marca anulada debe poder conservarse y luego registrarse nuevamente el mismo tipo de marca como vigente. La unicidad operativa de marcas vigentes debe validarse en backend usando `activo = 1` y `anulada = 0`; el schema debe mantener solo un índice no único para consulta por jornada, tipo y estado.

Agregar campos recomendados:

```text
activo
anulada
motivo_anulacion
anulada_por
fecha_anulacion
```

Reglas:

- Una marca errónea no debe eliminarse físicamente.
- Si una marca se anula, debe quedar `anulada = 1`, `activo = 0`, `motivo_anulacion`, `anulada_por` y `fecha_anulacion`.
- `anulada_por` debe relacionarse con `usuarios_sistema.id_usuario`.
- La anulación de una marca debe registrarse también en `auditoria_cambios`.
- La corrección o anulación de una marca puede afectar el estado global de `jornadas_asistencia`.
---

## 25. Estado de jornada de asistencia

Los estados globales deben vivir principalmente en `jornadas_asistencia`.

Estados:

```text
VALIDA
OBSERVADA
INCOMPLETA
ANULADA
CORREGIDA
PENDIENTE_REVISION
```

Reglas:

- Si marca ENTRADA y luego SALIDA, se permite y queda `OBSERVADA`.
- Si marca ENTRADA, ENTRADA_COLACION y luego SALIDA, se permite y queda `OBSERVADA`.
- Si falta SALIDA final, queda `INCOMPLETA`.
- Después de SALIDA no se permite otra marca.
- Regla confirmada por el responsable el 2026-10-13: si una jornada dura 6 horas o más y no tiene colación registrada, dejar `OBSERVADA` y contar el tiempo trabajado sin descontar automáticamente una hora de colación. Se reemplaza la instrucción anterior de descuento automático; el comportamiento PHP se validará en la fase funcional correspondiente.
- Si falta SALIDA, `horas_calculadas = 0`.
- `INCOMPLETA`, `ANULADA` y `PENDIENTE_REVISION` no suman horas.
- `VALIDA` suma horas.
- `CORREGIDA` suma si tiene ENTRADA y SALIDA válidas.
- `OBSERVADA` puede sumar si tiene ENTRADA y SALIDA válidas, según regla de cálculo.
- Requerimiento implementado en fase 3 el 2026-10-13: ADMIN/SUPERVISOR puede revisar una jornada `OBSERVADA` o `PENDIENTE_REVISION` y dejarla `CORREGIDA` mediante la opción «Marcar como revisada», sin modificar sus marcas. Requiere entrada/salida y secuencia válidas, control de autosupervisión, motivo y auditoría de actor, fecha y valores anteriores/nuevos. No aprueba extras automáticamente. Las aprobaciones anteriores incompatibles con el recálculo deben ajustarse expresamente; cambiar marcas retira su aprobación previa. Véase `FASE_3_CORRECCIONES.md` para pruebas y límites.

---

## 26. Horarios diferenciados

El horario pactado no es un dato personal, pero puede capturarse en el flujo operativo de pre-registro o en el cierre inicial de ficha si aun no existe.

Flujo:

1. Se registra/pre-registra practicante.
2. ADMIN o SUPERVISOR puede asignar horario durante el pre-registro.
3. Si el horario no fue asignado antes, el PRACTICANTE debe registrarlo al completar su ficha inicial.
4. Practicante puede visualizar horario.
5. Despues de cerrar ficha, el PRACTICANTE no puede modificar libremente su horario.
6. ADMIN y SUPERVISOR pueden administrar el horario pactado despues del cierre de ficha mediante pantalla dedicada, conservando historial de vigencias y sin borrado fisico.

Tablas:

```text
horarios_practicante
detalle_horario_practicante
excepciones_horario_practicante
```

Debe soportar:

```text
días diferenciados
entrada esperada
salida esperada
colación esperada
horas planificadas
vigencia del horario
cambios temporales
excepciones
```

Reglas:

- Atraso desde 10 minutos.
- Salida anticipada se marca.
- Ausencia automática si no asiste en día planificado.
- La generacion/revision operativa de ausencias esperadas se realiza desde `admin/asistencia.php` y `supervisor/asistencia.php`.
- Una ausencia esperada debe crear una jornada `INCOMPLETA` con `flag_ausencia_esperada = 1`, `horas_calculadas = 0` y observacion operativa.
- La resolucion operativa de ausencias esperadas se realiza desde `admin/asistencia.php` y `supervisor/asistencia.php`, reutilizando `estado_jornada`, `observacion`, `flag_ausencia_esperada` y auditoria `CORREGIR_ASISTENCIA`.
- Una ausencia esperada justificada no debe sumar horas; puede quedar como `OBSERVADA` con `horas_calculadas = 0`.
- Una ausencia esperada no justificada debe conservar `INCOMPLETA` y `horas_calculadas = 0`.
- Una ausencia esperada puede quedar como `PENDIENTE_REVISION` si falta antecedente para resolverla.
- Solo se deben generar ausencias para fechas anteriores al dia actual, con horario planificado vigente y sin jornada previa registrada.
- No se debe crear ausencia esperada si ya existe una jornada, marca o excepcion que deje el dia sin horario planificado.
- Asistencia en día no planificado se permite como `ASISTENCIA_EXTRAORDINARIA` con revisión.
- Horas sobre lo esperado requieren aprobación.
- Horas extra aprobadas se suman completas.
- La revision/aprobacion operativa de horas extra se realiza desde `admin/asistencia.php` y `supervisor/asistencia.php`, usando `horas_extra_aprobadas`, `aprobada_por` y auditoria `APROBAR_HORAS_EXTRA`.
- El sistema debe permitir registrar el mismo horario para varios días, horario diferenciado por día y días sin jornada.
- La pantalla de marcación de asistencia debe visualizar el horario pactado del día cuando exista.
- La pantalla `practicante/marcar-asistencia.php` debe registrar asistencia desde el usuario autenticado, sin solicitar RUT, DV ni nombre en la vista; puede usar CSS/JS dedicados para una experiencia visual moderna con sidebar institucional.
- Una ficha completa debe tener al menos un día con jornada pactada.
- Los reemplazos administrativos de horario deben cerrar o inactivar la vigencia anterior y crear un nuevo horario vigente.
- ADMIN y SUPERVISOR pueden registrar excepciones puntuales sobre el horario vigente desde `admin/practicantes.php` y `supervisor/practicantes.php`.
- Las excepciones activas de `excepciones_horario_practicante` tienen prioridad sobre el detalle semanal del horario para la fecha indicada.
- ADMIN y SUPERVISOR pueden editar una excepcion reingresando la misma fecha y tipo, y pueden anular logicamente una excepcion activa con motivo, dejando `activo = 0` sin borrar el registro.
- `CAMBIO_HORARIO` y `ASISTENCIA_EXTRAORDINARIA` pueden definir entrada, salida, colacion y horas planificadas excepcionales.
- `DIA_LIBRE`, `AUSENCIA_JUSTIFICADA`, `FERIADO` y `OTRO` pueden registrarse con observacion sin exigir horario.
- Un supervisor-practicante no puede registrar excepciones sobre su propio horario desde el rol SUPERVISOR.

Estos casos deben manejarse como flags/campos en `jornadas_asistencia`, no como estados principales.

---

## 27. Áreas

Tablas:

```text
areas_practica
historial_areas_practicante
bloques_area_jornada
```

Reglas:

- SUPERVISOR y ADMIN pueden asignar o cambiar área.
- Cambio de área no reinicia horas acumuladas.
- Historial de áreas no se sobrescribe.
- Si cambia área, se cierra periodo anterior con `fecha_fin` y se abre nuevo periodo.
- La gestion base de area vigente por practica se realiza desde `admin/practicantes.php` y `supervisor/practicantes.php`, reutilizando `historial_areas_practicante`.
- El cambio de area vigente debe auditarse como `CAMBIAR_AREA`.
- Un supervisor-practicante no puede cambiar su propia area desde el rol SUPERVISOR.
- Una jornada puede tener más de un área durante el mismo día.
- Para eso usar `bloques_area_jornada`.
- Los bloques de area por jornada se gestionan desde `admin/asistencia.php` y `supervisor/asistencia.php`.
- Cada bloque debe pertenecer a la fecha de la jornada, tener hora de inicio y fin validas, calcular `horas_asignadas` y evitar solapamiento con otros bloques de la misma jornada.
- La creacion de bloques de area debe auditarse como `CAMBIAR_AREA`.

Ejemplo:

```text
Mañana: Creación de Productos.
Tarde: Automatización IA.
```

Los bloques deben guardar:

```text
id_area
hora_inicio
hora_fin
horas_asignadas
asignado_por
created_at
updated_at
```

---

## 28. Correcciones y anulaciones

### ADMIN

Puede:

- Corregir asistencia.
- Anular asistencia completa.
- Anular lógicamente marcas individuales de asistencia.

### SUPERVISOR

Puede:

- Corregir asistencia.
- Observar asistencia.
- Marcar `PENDIENTE_REVISION`.
- Resolver correcciones.
- Anular lógicamente marcas individuales cuando corresponda al flujo de corrección permitido.

No puede:

- Anular asistencia completa.

### PRACTICANTE

No puede:

- Corregir asistencia.
- Anular asistencia.
- Anular marcas de asistencia.

Reglas:

- Toda corrección o anulación debe auditarse.
- No se permite borrado físico de jornadas ni marcas.
- La anulación completa de una jornada queda reservada para ADMIN.
- La anulación individual de marcas debe usar los campos de anulación lógica de `marcas_asistencia`.
- La pantalla operativa base de correcciones/anulaciones vive en `admin/asistencia.php` y `supervisor/asistencia.php`, reutilizando `includes/asistencia_gestion.php`.
- ADMIN y SUPERVISOR pueden revisar jornadas, resolver ausencias esperadas, anular marcas individuales con motivo, registrar marcas correctivas, aprobar horas extra y marcar jornadas como `OBSERVADA` o `PENDIENTE_REVISION`.
- SUPERVISOR no puede ejecutar anulación completa de jornada.
---

## 29. Auditoría

Crear tabla:

```text
auditoria_cambios
```

Auditar desde MVP.

### Eventos de seguridad

```text
LOGIN_EXITOSO
LOGIN_FALLIDO
LOGOUT
RECUPERACION_SOLICITADA
TOKEN_GENERADO
TOKEN_REGENERADO
PASSWORD_CAMBIADA
CUENTA_ACTIVADA
USUARIO_BLOQUEADO
ROL_ASIGNADO
ROL_REMOVIDO
CREACION_USUARIO
EDICION_USUARIO
CAMBIO_ESTADO_CUENTA
INTENTO_ASIGNACION_ADMIN_RECHAZADO
```

### Eventos funcionales

```text
EDICION_DATOS_PROPIOS_PRACTICANTE
CREAR_PRACTICANTE
EDITAR_PRACTICANTE
CORREGIR_ASISTENCIA
ANULAR_ASISTENCIA
ANULAR_MARCA_ASISTENCIA
CAMBIAR_AREA
APROBAR_HORAS_EXTRA
SUBIR_DOCUMENTO_PRACTICANTE
CERRAR_PRACTICA
EXPORTAR_XLSX
```

La auditoría debe registrar idealmente:

```text
id_auditoria
id_usuario_actor
id_rol_contexto
id_sesion
evento
tabla_afectada
id_registro_afectado
valores_anteriores
valores_nuevos
ip
user_agent
created_at
```

Reglas:

- Toda acción auditada debe guardar, cuando corresponda, el usuario actor.
- Toda acción auditada debe guardar el rol activo usado por el usuario mediante `id_rol_contexto`.
- Toda acción auditada debe guardar la sesión asociada mediante `id_sesion`, cuando exista sesión activa.
- Esto permite distinguir si una persona actuó como `PRACTICANTE`, `SUPERVISOR` o `ADMIN`.
- La asignación de rol `ADMIN` a un usuario con `id_practicante` no debe permitirse y, si se intenta, debe registrarse como `INTENTO_ASIGNACION_ADMIN_RECHAZADO` o incidente funcional equivalente.
- La anulación lógica de una marca de asistencia debe auditarse como `ANULAR_MARCA_ASISTENCIA`.

La vista inicial de auditoria visible se realiza desde:

```text
admin/auditoria.php
```

Alcance inicial:

- Acceso exclusivo ADMIN.
- Vista web de solo lectura.
- Filtros por rango de fechas, evento, tabla afectada y busqueda simple.
- Resumen de eventos listados, seguridad, funcionales y usuarios actores.
- Listado con fecha, evento, actor, rol contexto, tabla, registro, IP y resumen redactado de valores.
- No permite editar, borrar ni exportar auditoria.
---

## 30. Dashboard y reportes

El dashboard debe poder segmentar por:

```text
año
mes
trimestre
semestre
institución
sede
carrera
área
sexo
rango etario
estado de práctica
estado de asistencia
```

Esto se implementa principalmente con:

```text
campos
índices
consultas SQL
filtros backend
filtros frontend
```

No crear tablas separadas innecesarias para cada filtro.

El dashboard avanzado inicial se implementa directamente en:

```text
admin/dashboard.php
supervisor/dashboard.php
```

Alcance inicial:

- Métricas reales de practicantes activos y practicas activas.
- Horas sumables de los ultimos 30 dias.
- Avance global calculado contra horas requeridas.
- Jornadas pendientes de revision.
- Ausencias esperadas.
- Para ADMIN, usuarios activos y eventos de auditoria de las ultimas 24 horas.
- Para SUPERVISOR, si el usuario tambien es practicante, excluir su propia practica de metricas operativas.
- ADMIN cuenta con dashboard visual mejorado en `admin/dashboard.php`, inspirado en `assets/img/Dashboard_Administrador.png` y usando el logo transparente `assets/brand/logo_rojo_blanco.png`.
- El dashboard ADMIN usa `assets/css/admin-dashboard.css`, `assets/js/admin-dashboard.js`, Bootstrap Icons y Chart.js de forma acotada a la pantalla.
- El dashboard ADMIN muestra sidebar institucional, KPIs, graficos Chart.js de seguimiento semanal/distribucion, tabla de registros recientes y alertas operativas con datos reales por PDO.
- El dashboard ADMIN incluye filtros reales por `GET` para periodo, rango manual, busqueda simple, institucion, sede, carrera, area y estado de practica.
- El dashboard ADMIN incluye comparativos contra periodo anterior para KPIs y grafico principal cuando la metrica aplica.
- El sidebar ADMIN incluye opcion de logout, puede colapsarse en escritorio y funciona como menu hamburguesa/off-canvas en pantallas menores.
- El dashboard ADMIN incluye tambien logout en barra superior y usa una densidad visual compacta para priorizar KPIs y graficos dentro de la primera visualizacion.
- No incluye aun exportacion.

La gestion inicial de reportes basicos se realiza desde:

```text
admin/reportes.php
supervisor/reportes.php
```

Alcance inicial:

- Reporte web de solo lectura.
- Filtros por rango de fechas, estado de practica y practicante.
- Resumen de practicas filtradas, horas del periodo, avance global, jornadas pendientes y ausencias esperadas.
- Tabla por practicante/practica con horas totales, avance, horas del periodo, jornadas y alertas.
- En rol SUPERVISOR, un usuario practicante-supervisor no debe ver su propia practica en reportes operativos.
- No incluye aun exportacion XLSX real.

### Métricas futuras

```text
total activos
por institución
por carrera
por sexo
promedio edad
rangos etarios
por área
estados de práctica
horas realizadas
horas restantes
porcentaje avance
asistencias válidas
observadas
incompletas
tasa asistencia promedio general
cumplimiento horario
ausencias esperadas
ranking practicantes destacados
```

---

## 31. Evaluaciones

Crear tabla desde el schema inicial:

```text
evaluaciones_practicante
```

Como existe tabla `practicas`, la evaluación debe vincularse a una práctica específica.

Campos sugeridos:

```text
id_evaluacion
id_practicante
id_practica
id_evaluador
nota_desempeno
potencial
nivel_desarrollo_habilidades
observacion
fecha_evaluacion
estado_evaluacion
aprobado_por
fecha_aprobacion
created_at
updated_at
```

Reglas:

- Reglas de Notas confirmadas el 2026-10-19, pendientes de implementación: ADMIN registra y aprueba únicamente evaluaciones de líderes de área; el líder vigente registra y aprueba las de los demás integrantes de su área sin aprobación ADMIN posterior.
- SUPERVISOR por sí solo no habilita evaluar ni aprobar. Se comprobarán liderazgo y pertenencia al área.
- El evaluado consulta su nota después de la aprobación del responsable correspondiente.
- Nadie puede evaluarse a sí mismo. El responsable competente sí puede registrar y aprobar una evaluación de otra persona.
- El cierre de práctica sigue reservado a ADMIN y es independiente de aprobar una nota.
- Véase PLAN_IMPLEMENTACION_NOTAS.md para fases, puntajes existentes y decisiones pendientes.

---

## 32. Exportación

La exportación XLSX real sí está considerada, no solo CSV.

Reportes esperados:

```text
practicantes
asistencia
avance de horas
por institución
por área
observados/incompletos
historial de áreas
```

En primera etapa puede quedar modelado o preparado, pero no necesariamente implementado completo.

---

## 33. Migración Google Forms

La migración desde Google Forms está postergada.

Pero debe quedar contemplada.

Archivo futuro sugerido:

```text
staging_google_forms.sql
```

o:

```text
migracion_google_forms.sql
```

Regla:

No importar directo a tablas principales.

Flujo futuro:

```text
Google Sheets/Excel
→ staging
→ validación
→ normalización
→ carga final
```

---

## 34. No borrado físico

No borrar físicamente registros.

Usar:

```text
activo = 0
estado = ANULADA
estado = INACTIVO
estados correspondientes
```

Esto aplica a:

```text
practicantes
usuarios
prácticas
registros de asistencia
marcas
horarios
áreas
evaluaciones
```

Reglas específicas:

- La baja de un practicante debe ser logica y reservada a ADMIN.
- La baja logica de un practicante debe desactivar el registro en `practicantes`, sus practicas activas y la cuenta de usuario vinculada si existe, conservando historial.
- En `marcas_asistencia`, una marca errónea debe conservarse mediante anulación lógica granular.
- No se debe ejecutar `DELETE` para eliminar marcas de asistencia corregidas o anuladas.
- La jornada puede quedar `CORREGIDA`, `ANULADA`, `OBSERVADA` o `PENDIENTE_REVISION` según corresponda, pero las marcas originales deben conservar trazabilidad.
- Toda anulación o corrección relevante debe quedar registrada en `auditoria_cambios`.
---

## 35. Orden exacto de implementación

Implementar en este orden:

```text
1. config/app.php
2. config/database.php
3. config/mail.php
4. database/schema.sql
5. database/seed_catalogos.sql
6. database/seed_admin.sql
7. includes/helpers.php
8. includes/auditoria.php
9. includes/auth.php
10. includes/mailer.php
11. includes/reglas_asistencia.php
12. login.php
13. seleccionar-rol.php
14. logout.php
15. activar-cuenta.php
16. recuperar-password.php
17. restablecer-password.php
18. admin/dashboard.php
19. supervisor/dashboard.php
20. practicante/dashboard.php
21. admin/crear-practicante.php
22. supervisor/crear-practicante.php
23. practicante/mis-datos.php
24. practicante/marcar-asistencia.php
25. practicante/mi-historial.php
```

---

## 36. Primer archivo a implementar

Comenzar por:

```text
/config/app.php
```

No comenzar por `schema.sql`.

Motivo:

La configuración central afecta:

```text
rutas
BASE_URL
BASE_PATH
assets
redirects
formularios
sesiones
tokens
entorno local/productivo
```

Después continuar con:

```text
/config/database.php
/config/mail.php
/database/schema.sql
```

---

## 37. Estilo de implementación esperado

El código debe ser:

- Completo.
- Ordenado.
- Comentado.
- Compatible con XAMPP local.
- Compatible con Hostinger.
- Basado en PDO.
- Sin rutas quemadas.
- Con validaciones claras.
- Con manejo básico de errores.
- Con separación entre configuración, helpers, autenticación, auditoría y vistas.
- Sin mezclar reglas de negocio críticas directamente en HTML.

Usar lenguaje técnico explícito:

```text
variables
funciones
módulos
validaciones
sesiones
headers
requests
status codes
payloads
excepciones
filtros
logs
estructuras de datos
diagnóstico técnico
```

---

## 38. Reglas

Se debe trabajar por etapas.

No debe modificar muchos archivos sin control.

Antes de cada etapa debe indicar:

```text
Archivos que creará o modificará.
Objetivo técnico.
Dependencias.
Riesgos.
Cómo probar.
```

Después de cada etapa debe entregar:

```text
Resumen de cambios.
Archivos creados/modificados.
Instrucciones de prueba local.
Errores conocidos si existen.
Siguiente paso recomendado.
```

No se deben asumir reglas contradictorias.

Si detecta una contradicción técnica con este documento, debe detenerse y pedir validación antes de aplicar cambios.

---

## 39. Reglas críticas

Actualización operativa de fase 7 (2026-10-15): los informes separan recuperación, excepcional aprobado, compensación y saldo, sin duplicar avance. El supervisor puede revalidar expresamente evidencia y canjes históricos que ya tenían autorización previa al tramo, tras una corrección; esto conserva vencimiento, exige presupuesto/evidencia/motivo/auditoría y nunca habilita saldo vencido ni autoriza retrospectivamente solicitudes nuevas. Los expedientes pendientes aparecen también en el aviso lateral de Permisos. Detalle de validación y publicación coordinada en FASE_7_VALIDACION_OPERATIVA.md; no hay parche nuevo ni despliegue automático. Producción informada PHP 8.3.35/MariaDB 11.8.9; pruebas locales en MariaDB 10.4.32.

Este documento es el contexto oficial para implementación.

Las decisiones aquí descritas tienen prioridad sobre supuestos genéricos.

No simplificar el modelo de datos si eso contradice este contexto.

No reemplazar PHP + MySQL/MariaDB por otro stack.

No crear login genérico.

No permitir auto-registro libre de practicantes.

No omitir auditoría base.

No guardar contraseñas en texto plano.

No guardar tokens planos.

No usar URLs rígidas.

No borrar físicamente registros.

No implementar múltiples archivos grandes sin explicar antes el alcance.

No avanzar a la siguiente etapa sin entregar resumen, archivos modificados y forma de prueba.
