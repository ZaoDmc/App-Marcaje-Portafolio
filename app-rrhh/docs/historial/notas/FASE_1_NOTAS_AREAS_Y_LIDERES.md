> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas — Fase 1: áreas y líderes

Fecha: 19 de octubre de 2026. Rama: `notas-areas-y-lideres`, desde `main` en `a52b59c`. Estado: implementada y probada localmente; no publicada ni confirmada en producción.

El commit `43f084e` de correcciones menores seguía en su rama al consultar GitHub. No se incorporó como dependencia de esta fase. Los documentos de planificación pendientes se conservaron en la rama nueva.

## Resultado y alcance

ADMIN dispone de `admin/notas.php`, debajo de Reportes, para consultar áreas e integrantes, designar o reemplazar líderes y retirar una designación. Se añadió Áreas a Catálogos para mantener nombres, descripciones y estado.

Esta fase no crea notas, evaluaciones semanales ni rúbricas. Prepara la estructura organizacional que utilizarán esas funciones. No crea un rol global LIDER ni concede funciones de SUPERVISOR.

## Reglas implementadas

- Gestión de liderazgo exclusivamente por ADMIN, comprobada en ruta, controlador y servicio. Los otros roles no acceden a esta pantalla.
- Como máximo un líder vigente por área. Se permiten áreas sin líder.
- El candidato debe pertenecer actualmente al área, con practicante/práctica activos y estado PENDIENTE_INICIO o EN_PROGRESO. Se excluyen pertenencias futuras. Las fechas se comparan usando America/Santiago desde PHP.
- Designaciones inmediatas; no se programa liderazgo futuro ni se permite reescribir vigencias pasadas desde el formulario.
- Designación, reemplazo y retiro exigen motivo de hasta 500 caracteres. Reemplazar cierra el registro anterior y crea otro; nunca borra el historial.
- El formulario conserva el identificador del líder que ADMIN vio. Si otra operación lo cambió, se rechaza el formulario obsoleto.
- Cambiar de área o dar de baja a un líder requiere retirar primero su designación desde Notas. También se bloquea desactivar su área mientras conserve liderazgo vigente. Los permisos anteriores de SUPERVISOR para asignar integrantes a áreas no se revocaron.
- El índice único sobre una columna generada protege el máximo de un líder vigente. Transacciones y bloqueos de filas coordinan designaciones, cambios de área y bajas. Se mantiene un orden de bloqueo práctica → área cuando ambas intervienen.
- Auditoría de DESIGNAR_LIDER_AREA, REEMPLAZAR_LIDER_AREA y RETIRAR_LIDER_AREA. Si falla la auditoría de una designación, se revierte toda la operación.
- No se impuso una restricción adicional sobre cuántas áreas puede liderar una persona. La pertenencia al área sigue siendo obligatoria; esa política se definirá antes de ampliar permisos de evaluación.

## Archivos principales

- `config/routes.php`, `src/Views/layout/sidebar.php`: ruta y menú de ADMIN.
- `src/Controllers/Admin/NotasController.php`: autorización, CSRF, validación, preparación y redirección después de guardar.
- `src/Views/admin/notas.view.php`: áreas, formularios, integrantes e historial con los temas existentes.
- `src/Services/LiderazgoService.php`: lecturas, autorización y transacciones.
- `src/Models/AreaPracticanteModel.php`, `src/Services/HorariosPracticanteService.php`: integridad ante cambios de área y bajas.
- `src/Models/CatalogoModel.php`: catálogo de áreas y bloqueo de desactivación con liderazgo.
- `src/Controllers/Shared/PracticantesController.php`: explicación de la baja bloqueada, sin perder la ficha.
- `src/Services/AuditoriaService.php`: eventos de liderazgo.
- `database/schema.sql`, `database/2026-10-19_patch_liderazgos_area_009.sql`: instalación nueva y migración de bases existentes.

## Base de datos y publicación

La tabla `liderazgos_area` conserva práctica, área, inicio/fin, responsables, motivos y una clave generada para el área vigente. Sus relaciones impiden borrar las referencias históricas.

Para una base existente, ejecutar `database/2026-10-19_patch_liderazgos_area_009.sql` sobre la base del proyecto, no sobre una tabla concreta. Solo añade la tabla; no designa líderes ni modifica evaluaciones. Puede repetirse. En instalaciones nuevas basta con el `schema.sql` actualizado para esta estructura; los datos de catálogos siguen en sus seeds.

El parche ya se aplicó a la base local asistencia durante esta entrega: seis tablas existentes conservaron sus checksums y el historial de liderazgo quedó vacío, listo para pruebas manuales. No se aplicó a producción.

Antes de publicar, respaldar base y código y aplicar el parche durante una ventana controlada. Después publicar backend/frontend de la misma versión y probar con ADMIN. No importar `schema.sql` completo sobre producción para aplicar esta fase.

Sin el parche, Notas muestra una explicación y no habilita los formularios. Los flujos anteriores de área y baja siguen funcionando: todavía no existen designaciones que proteger. No se crean tablas al abrir una página.

Para revertir el código, conservar la tabla y sus datos. Si ya se designaron líderes, no operar cambios de área o bajas con código anterior que desconozca esos controles. Volver a una versión compatible o retirar expresamente las designaciones con la versión actual, conservando su historial antes de retirar el código.

## Validación

- `php tests/liderazgos.php`: 33 comprobaciones con SQLite en memoria; permisos, candidatos, motivos, formularios obsoletos, reemplazos, retiros, bloqueos de traslado/baja/área, auditoría y reversión de transacciones.
- `php tests/mvc.php`: 236 comprobaciones, incluyendo renderizado de Notas, escape de texto, autorización, ausencia de migración y CSRF inválido con IDs y acción válidos.
- `php tests/routing.php`: 164 comprobaciones y 38 rutas.
- `php tests/legacy-removal.php`: 353 comprobaciones.
- `php tests/configuration.php`: 20 comprobaciones.
- `php tests/liderazgos-mariadb.php <fixture>`: 42 comprobaciones en MariaDB 10.4.32; repetición de migración, conteos conservados, columna generada e índice único, dos procesos con conexiones independientes y rechazo del formulario obsoleto después de esperar un bloqueo real.
- Recorrido Edge/Playwright: una prueba E2E específica de liderazgo aprobada, con designación/reemplazo/retiro, CSRF, candidato de otra área, historial y acceso denegado a SUPERVISOR/PRACTICANTE. Verificación sin desbordes y capturas a 320/1280 px. Evidencia ignorada por Git: `scratch/phase8/7c0c9c4327d0/`.
- Repetición final con fixture nueva `scratch/phase8/290c39cd3a2f/`: recorrido aprobado y preparación automática de datos/concurrencia desde la propia prueba. MySQL siguió respondiendo en 3306 al terminar. No se repitió la suite E2E completa fuera de este recorrido.
- CSS del portal recompilado; permanece el aviso previo de actualización de Browserslist.

Para repetir el recorrido MariaDB/Edge, crear una fixture nueva con `tests/e2e-setup.php` y un vendor de producción ya preparado, ejecutar `tests/liderazgos-mariadb.php` sobre su JSON y luego, con PHP_BINARY/E2E_FIXTURE definidos, `node --test --test-name-pattern='liderazgos:' tests/e2e.test.cjs`. Nunca usar la base habitual como fixture.

La suite general `tests/business.php` mantiene cuatro fallos de permisos/interrupciones que también se reprodujeron sobre una copia intacta de `main` a52b59c. No se presentan como regresiones resueltas ni como pruebas aprobadas: SALIDA_PERMISO, REGRESO_PERMISO, aprobación de ausencia sin cruce y reconocimiento de ambos tramos. Esta fase no modifica esas reglas.

## Incidente local y siguiente fase

Durante las pruebas MySQL de XAMPP no arrancaba. El rescate y cambio de ubicación de datos son una intervención local independiente, documentada en `INCIDENTE_MYSQL_LOCAL_20261019.md`. No equivalen a cambios de despliegue para Hostinger.

La fase 2 definirá evaluaciones semanales por práctica, sin duplicados, detalle y permisos. Se mantiene la decisión: ADMIN registra/aprueba únicamente notas de líderes; cada líder registra/aprueba las de sus integrantes. Jueves es el día confirmado de carga, pero los límites de semana, cargas atrasadas y cálculo de nota final siguen pendientes en `PLAN_IMPLEMENTACION_NOTAS.md`.

## Pull request propuesto

Título: `Notas · Fase 1: gestión de líderes por área`

Descripción:

Incorpora Notas debajo de Reportes para que ADMIN designe, reemplace y retire líderes de área, conservando responsables, motivos y vigencias. Añade el catálogo de áreas y evita traslados, bajas o desactivaciones incompatibles con un liderazgo vigente.

Incluye migración repetible, schema actualizado, control de concurrencia, protección CSRF y auditoría. Esta fase prepara la estructura organizacional; todavía no habilita calificaciones semanales.

Validación: pruebas específicas de liderazgo, MVC, rutas, configuración y legado; concurrencia real en MariaDB y recorrido Edge de formularios y permisos. La suite general de negocio mantiene cuatro fallos previos reproducidos en main y documentados en esta entrega.

Despliegue: aplicar database/2026-10-19_patch_liderazgos_area_009.sql a la base existente antes de habilitar la gestión. El parche ya está aplicado en local; no se ha aplicado en producción.
