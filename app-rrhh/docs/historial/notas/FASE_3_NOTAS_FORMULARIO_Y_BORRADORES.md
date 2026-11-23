> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas — Fase 3: formulario, borradores y criterios configurables

Fecha: 19 de octubre de 2026. Rama: `notas-formulario-y-borradores`, desde `main` en `932113b`. Estado: implementada y probada localmente. Migraciones 010/011 aplicadas a la base local con respaldo; no publicadas ni aplicadas por esta entrega en Hostinger. Sin commit ni push realizados durante la implementación.

## Resultado y uso

ADMIN encuentra Notas debajo de Reportes, con tres secciones: Evaluaciones, Áreas y líderes y Criterios. Los líderes designados mantienen contexto PRACTICANTE y ven Notas en su menú. SUPERVISOR no obtiene facultades de evaluación por su rol.

1. ADMIN abre `admin/notas-rubrica.php`, escribe el nombre de la versión y el criterio de cada fila. Puede agregar o quitar filas, dejando entre 1 y 50. Explica el cambio y guarda una versión nueva.
2. Si todavía no hay líderes, ADMIN los designa en Áreas y líderes, conservando el flujo de fase 1. La cuenta designada debe usar su contexto PRACTICANTE.
3. El líder abre `practicante/notas.php`, selecciona integrante y viernes. Puede elegir viernes pendientes anteriores, incluidos periodos anteriores a su designación que no tengan evaluación existente.
4. Cada criterio muestra un selector de 1 a 7 en pasos de 0,5 y Sin evaluar. Nota final manual de 1–7 con hasta dos decimales, observaciones y guardado de borrador. No se calculan promedios.
5. Para editar un borrador, vuelve a abrir su viernes e indica motivo. ADMIN puede corregir borradores de cualquier practicante ajeno, aunque la práctica haya cambiado de área o terminado. ADMIN registra nuevas evaluaciones únicamente de líderes.

Rutas locales: `http://localhost/asistencia/admin/notas-evaluaciones.php`, `http://localhost/asistencia/admin/notas-rubrica.php` y `http://localhost/asistencia/practicante/notas.php`. Las URLs públicas las atiende el router; no se recrearon puentes PHP en raíz ni carpetas de rol.

Esta fase guarda borradores: no aprueba/publica notas ni habilita consulta del evaluado. Las aprobadas se muestran en solo lectura si existen; su corrección llegará con el flujo de fase 4. No se modificaron Acompañamiento, acumulados de asistencia ni evaluaciones antiguas.

## Filas y criterios

El requisito de filas variables sustituye la cantidad fija de diez. Se adoptó configuración global por ADMIN, anunciada antes de implementar: los líderes califican y no alteran criterios arbitrariamente. Cada fila contiene un criterio escrito y un selector de nota. El límite 1–50 es técnico; no es una regla académica proporcionada por el cliente.

La instalación conserva `provisional-v1` con los diez rótulos iniciales Evaluación 1–10. ADMIN debe reemplazar esos rótulos con la rúbrica real cuando la reciba; no se inventaron criterios, ponderaciones ni una fórmula de nota final.

Guardar criterios inserta otra fila de `rubricas_notas` y cambia el puntero de `configuracion_notas`, con motivo, actor y revisión. El servicio no modifica versiones anteriores ni elimina criterios de evaluaciones existentes. Una evaluación nueva toma la versión vigente; un borrador existente conserva su versión incluso si se añadieron/quitaron filas después. El historial de cada guardado incluye criterios escritos y notas, además de referenciar la versión.

Si cambia la versión activa mientras se completa una evaluación nueva, se rechaza el envío. La pantalla conserva los valores asociados a los criterios que el usuario vio y pide recargar; no los traslada automáticamente a otras etiquetas.

## Permisos y seguridad

- Cuenta y rol activos comprobados en base. Un líder solo ve integrantes actuales de sus áreas; no aparece su propia práctica ni personas de áreas ajenas. La autorización se repite al acceder con ID directo y al guardar.
- El menú se prepara antes del HTML. Retirar/reemplazar liderazgo elimina el acceso, aunque la sesión del antiguo líder continúe abierta. Ocultar el enlace no sustituye al control de servidor.
- Un viernes ocupado por otro responsable se identifica como gestionado por otro responsable, sin exponer notas, observaciones ni IDs de borradores ajenos al líder actual. ADMIN puede abrirlo y corregirlo con motivo.
- CSRF separado para criterios y borradores. El token se mantiene por sesión para admitir varias pestañas; la revisión esperada impide sobrescribir otro guardado. PRG con 303 evita repetir un envío al recargar después del éxito.
- Valores, filas, fechas, criterios y escala se validan en servidor. No se aceptan campos de autor/área/rol desde formularios. El servicio exige las posiciones exactas de la versión guardada.
- Criterios y observaciones se escapan al presentar. El JS añade filas clonando controles estáticos y asignando texto, sin insertar contenido del usuario como HTML.
- Sin JavaScript, agregar/quitar filas envía un POST de preparación, validado con CSRF, sin crear versiones incompletas. Guardar nueva versión sigue siendo una acción explícita con motivo y criterios no vacíos.
- Auditoría de rúbricas y notas atómica con su transacción. Errores o formularios obsoletos conservan las versiones anteriores.

## Archivos

- `src/Services/NotasService.php`: guardado según cantidad/versiones de criterios, rechazo de versiones obsoletas y etiquetas en las instantáneas.
- `src/Services/RubricasNotasService.php`: validación y creación de versiones ADMIN, configuración activa y auditoría.
- `src/Services/NotasFormularioService.php`: menú, destinatarios, acceso por ID y periodos visibles sin revelar borradores ajenos.
- Controladores `Admin/NotasEvaluacionesController.php`, `Admin/NotasRubricaController.php`, `Practicante/NotasController.php` y `Shared/NotasEvaluacionesController.php`.
- Vistas `shared/notas-evaluaciones.view.php`, `admin/notas-rubrica.view.php`, navegación `components/notas-nav.php` y enlace desde la gestión de líderes.
- `config/routes.php`, `Shared/LayoutContext.php` y `layout/sidebar.php`: rutas, menú y contexto.
- `public/assets/js/notas-rubrica.js`, CSS de temas y Tailwind compilado: controles de filas y adaptación de presentación. Etiquetas/ayudas dentro de Notas usan colores del tema, sin texto azul oscuro fijo.
- Migración 011, schema, script local de respaldo/aplicación y pruebas específicas de servicio/formularios.

## Migración, publicación y reversión

Orden para una base existente: verificar 009, aplicar 010 si falta y luego `database/2026-10-19_patch_rubricas_configurables_011.sql`. Ejecutar sobre la base completa, no sobre una tabla concreta. No reimportar `schema.sql` sobre producción. Para instalaciones nuevas, schema actualizado más seeds.

011 agrega `configuracion_notas` y amplía el CHECK de posiciones de 10 a 50. Conserva la rúbrica activa si se repite y no modifica notas existentes. Usa sintaxis MariaDB probada en 10.4 local y destinada a 11.8 de Hostinger; no se certificó su ejecución en MySQL. El DROP/ADD del CHECK no es una transacción DDL: aplicar durante una ventana controlada, verificar y repetir si hubo interrupción.

Localmente se ejecutó `php scripts/migrate-notas-local.php --apply`: solo admite entorno de desarrollo y host local, genera respaldo completo con mysqldump (incluye triggers/rutinas/eventos) y SHA-256 antes de aplicar 010/011. Verificó checksums de las 36 tablas anteriores. Respaldo y resultado privados ignorados por Git en `scratch/notas-migration-20261019-165146-7cdc3d/`. No se cambió configuración MySQL ni `.env` y no se crearon notas o líderes ficticios en la base habitual.

El script es para desarrollo local, no para Hostinger. `MYSQLDUMP_BINARY` permite indicar la herramienta de respaldo si no está junto al runtime XAMPP; las credenciales pasan por entorno al proceso, no por argumentos impresos. No eliminar el respaldo como parte de una publicación.

En producción: respaldar, aplicar migraciones pendientes, publicar backend y frontend de la misma versión y verificar permisos/formularios/recursos. Esta entrega no ejecuta ese despliegue ni cambia entorno/uploads productivos.

Para revertir código, conservar configuración, versiones, notas e historial. Deshabilitar temporalmente las nuevas pantallas si es necesario; no volver a escribir notas de cantidad variable con una versión antigua que asuma diez filas. No borrar tablas ni recuperar toda la base automáticamente, porque se perderían operaciones posteriores.

## Validación reproducible

- `php tests/notas-semanales.php`: 106 comprobaciones MariaDB. Incluye 83 regresiones de fase 2 y pruebas de criterios variables, conservación de rúbrica/etiquetas, alcance de selectores, formularios nuevos obsoletos, edición ADMIN, rollback y repetición de 010/011 con datos poblados. Crea/elimina exclusivamente su base aleatoria local.
- `php tests/mvc.php`: 239 comprobaciones; `tests/routing.php`: 170 y 41 rutas; `tests/legacy-removal.php`: 366. Sintaxis PHP/JS y diff comprobados.
- `npm run build:css:portal`: Tailwind compilado. Conserva el aviso previo de actualización de Browserslist; no se cambiaron dependencias.
- Un recorrido Edge/Playwright específico aprobado, repetido en fixture nueva tras ajustar contraste: criterios añadir/quitar, CSRF, supervisor denegado, IDs ajenos, XSS escapado, viernes atrasado, nota manual, versiones conservadas, revisión obsoleta, corrección ADMIN, semana nueva y pérdida de acceso al reemplazar líder. También alternativa sin JavaScript.
- Seis temas (`light`, `dark`, `synthwave`, `aqua`, `coffee`, `black`) a 320/1280 px, sin desborde ni selects blancos en temas oscuros; etiquetas comprobadas contra el color del tema. Rúbrica ADMIN también a ambos anchos. Capturas revisadas y evidencia ignorada en `scratch/phase8/84d740dfe917/`, con `notas-results.json` y registros del servidor.

Para repetir el recorrido, crear una fixture nueva:

```powershell
php tests/e2e-setup.php RUTA_VENDOR_PARA_PRUEBAS
php tests/notas-e2e-setup.php RUTA_FIXTURE_JSON
$env:PHP_BINARY='RUTA_PHP_EXE'
$env:E2E_FIXTURE='RUTA_FIXTURE_JSON'
node --test tests/notas-e2e.test.cjs
```

Se usan únicamente bases `rrhh_e2e_*`, cuentas `example.test` y una copia del frontend/backend. No usar la base habitual como fixture ni reutilizar una ya modificada para el mismo recorrido. Los archivos de fixture contienen credenciales ficticias y no se versionan. No se ejecutó la suite E2E completa ni se declaran corregidos los cuatro fallos de negocio anteriores documentados en fase 1. Se probó Chromium/Edge, no Opera GX directamente; se usan colores compatibles con su motor.

## Próxima fase y PR

La fase 4 incorporará aprobación/publicación, consulta propia del practicante y corrección administrativa de aprobadas con motivo e historial. Quedan pendientes las reglas de completitud antes de aprobar y cualquier fórmula/ponderación; el borrador permite valores vacíos.

Título: `Notas · Fase 3: formularios semanales y criterios configurables`

Descripción:

Incorpora formularios de notas para líderes y ADMIN, selección de viernes pendientes o atrasados, nota final manual y guardado de borradores con revisión esperada, motivo y protección CSRF. Los líderes solo evalúan integrantes de sus áreas; ADMIN puede corregir borradores ajenos.

Permite a ADMIN añadir/quitar filas y escribir criterios mediante versiones de rúbrica. Cada evaluación conserva sus criterios y cantidad de filas originales, evitando que cambios posteriores alteren notas existentes. Incluye migración 011, schema actualizado, navegación, soporte para los seis temas y documentación.

Validación: 106 comprobaciones MariaDB, MVC, rutas y legado/API, más recorrido Edge de formularios, permisos, historial, alternativa sin JS y presentación móvil/escritorio. Migraciones 010/011 aplicadas solo en local con respaldo y 36 tablas anteriores conservadas. Publicación/aprobación de notas pendiente de fase 4; producción sin modificar.
