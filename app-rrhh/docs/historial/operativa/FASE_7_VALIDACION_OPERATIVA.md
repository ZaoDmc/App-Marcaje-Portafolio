> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 7 operativa: validación de permisos y compensaciones

Entrega del 2026-10-15 en `validacion-flujos-permisos-compensaciones`, desde main `740aba2`. Esta fase pertenece al plan de correcciones operativas; no sustituye la guía técnica FASE_7_DESPLIEGUE_HOSTINGER.md.

## Resultado y ajustes

- El informe individual de ADMIN/SUPERVISOR separa recuperación validada, tiempo excepcional aprobado y compensación aplicada por sus respectivas fechas. El saldo disponible y los expedientes pendientes se muestran a la fecha de consulta, sin filtro de periodo. El desglose no se suma nuevamente al avance.
- El aviso lateral de Permisos incluye solicitudes excepcionales y canjes pendientes/reabiertos. Excluye la práctica propia del revisor; el contador de permisos ordinarios conserva su significado.
- Si se corrigen marcas fuente, crédito y canjes dependientes siguen quedando en revisión y sin aportar. El supervisor puede revalidar primero la evidencia originalmente aprobada y después un canje pasado autorizado antes de comenzar. Se exige motivo, marcas completas y coherentes, horario compatible, presupuesto suficiente y auditoría. Una petición pasada sin autorización original no puede aprobarse retrospectivamente.
- La revalidación no reinicia las 168 horas ni permite gastar saldo vencido. Conserva la fecha original de autorización del canje y registra la nueva intervención en auditoría. Reconoce únicamente los minutos no trabajados y actualiza el avance del destino en la misma transacción. Si se reduce el trabajo fuente, se limita el presupuesto; las reservas de otros canjes y la exclusividad diaria siguen comprobándose.
- La prueba del paquete compara los assets activos con el código actual y el manifiesto. El CSV de assets retirados conserva sus hashes históricos como evidencia del traslado original, sin exigir que el CSS permanezca inmutable para siempre.

## Evidencia local

Entorno probado: PHP 8.3.35, MariaDB 10.4.32, Apache local y Edge sin ventana. Todas las operaciones autenticadas nuevas se hicieron con cuentas example.test en una base rrhh_e2e_* y copias de backend/frontend; no sobre usuarios habituales.

| Comprobación | Resultado |
| --- | --- |
| Negocio real con SQLite | 351 comprobaciones |
| Separación MVC y renderizado | 219 comprobaciones |
| Retiro de puentes/API | 349 comprobaciones |
| Catálogo/enrutamiento | 162 comprobaciones |
| Configuración de entorno | 20 comprobaciones |
| Navegador + MariaDB + SMTP local ficticio | 16 recorridos completos |
| Interfaz y accesibilidad responsive | 12 pruebas |
| HTTP de Apache en localhost/asistencia | 110 comprobaciones |
| ZIP reales, integridad, exclusión de datos y casos adversos | 543 comprobaciones |
| Repetición de los tres parches sobre la base E2E poblada | 10 tablas sin cambios de contenido |

Total de las cinco suites PHP base: **1101 comprobaciones**. Las comprobaciones de paquetes, HTTP y migraciones son adicionales. No aparecieron errores PHP en los logs E2E finales.

Evidencia E2E: `scratch/phase8/3ad478d3388c/results.json`, capturas y php.log. Repetí además el escenario histórico en una fixture nueva (`scratch/phase8/5b0d731224fe`) e inspeccioné informe-tiempos-320.png y -1280.png, esperando que terminara la transición del menú antes de capturar. El panel se lee completo y no desborda. Fixture con credenciales ficticias ignorada por Git: no publicarla. Las pruebas de migraciones compararon todo el contenido de tipos de marca, permisos, jornadas, marcas, feriados, recuperaciones, política, extras históricas, créditos y canjes antes/después.

Los recorridos cubren autenticación de tres roles, multirrol, altas, perfil/documentos y autorización de descargas, marcajes/confirmación/duplicados, cierres Revisado/Corregido, permisos para hoy e interrupciones, cruce con marcas, recuperaciones distribuidas/colación/sábado/feriado, catálogos, informes, auditoría, recuperación de contraseña, aprobación/reserva excepcional y revalidación histórica. Las tolerancias de 30 minutos por extremo y los ejemplos 09:55–17:20, 09:55–17:50 y 09:31–17:29 se conservan sin generar extras nuevas.

## Reproducir

Usar PHP 8.3.35 y las dependencias existentes. No ejecutar los helpers E2E contra una base distinta de la fixture creada por el propio sistema de pruebas.

```powershell
& 'C:\xampp\php 8.3.35\php.exe' tests/business.php
& 'C:\xampp\php 8.3.35\php.exe' tests/mvc.php
& 'C:\xampp\php 8.3.35\php.exe' tests/legacy-removal.php
& 'C:\xampp\php 8.3.35\php.exe' tests/routing.php
& 'C:\xampp\php 8.3.35\php.exe' tests/configuration.php
$env:PHP_BINARY='C:\xampp\php 8.3.35\php.exe'
node --test tests/ui.test.cjs
```

Para E2E: crear una fixture nueva con `tests/e2e-setup.php <vendor de producción>`, definir E2E_FIXTURE con la ruta devuelta y ejecutar `node --test tests/e2e.test.cjs`. Después, `php tests/e2e-migrations.php <fixture>` repite los parches sobre esos datos ficticios. Se requiere MySQL/MariaDB local activo. La concurrencia de varias sesiones/dispositivos no queda certificada por estos recorridos secuenciales, aunque las reservas usan transacciones y bloqueo de práctica.

## Publicar en Hostinger

El responsable confirmó **PHP 8.3.35 y 11.8.9-MariaDB-log** en producción. La sintaxis utilizada de CREATE TABLE IF NOT EXISTS, ADD COLUMN IF NOT EXISTS y claves foráneas está documentada por MariaDB: [ALTER TABLE](https://mariadb.com/docs/server/reference/sql-statements/data-definition/alter/alter-table), [CREATE TABLE](https://mariadb.com/docs/server/server-usage/tables/create-table). La validación ejecutada fue en MariaDB 10.4.32 local; no equivale a haber ejecutado los parches ni comprobado tipos/permisos/esquema particulares en el servidor 11.8.9.

**Esta fase no necesita SQL nuevo.** Si las fases 4, 5 y 6 ya están aplicadas, conservar sus tablas y corte. Si falta alguna, respaldar primero y ejecutar únicamente las pendientes, en este orden, seleccionando la base completa en phpMyAdmin:

1. `database/2026-10-14_patch_permisos_mismo_dia_006.sql`.
2. `database/2026-10-14_patch_recuperaciones_varios_dias_007.sql`.
3. `database/2026-10-15_patch_tiempo_excepcional_008.sql`.

Se requiere el esquema previo y los parches anteriores de permisos. No importar schema.sql, seeds, bases E2E ni el SQL de los respaldos locales sobre producción. IF NOT EXISTS no corrige tablas preexistentes con definiciones incompatibles: revisar su estructura antes de publicar. La migración excepcional calcula su propio corte productivo; no copiar la fila de política local.

Procedimiento coordinado:

1. Integrar el PR y generar una publicación desde main limpio. Guardar respaldo de base, backend, frontend y archivos persistentes; verificar que pueda restaurarse. Pausar operaciones durante el cambio para fijar un corte coherente si todavía falta la fase 6.
2. Comprobar los parches pendientes, ejecutar los que correspondan y revisar claves foráneas/índices. Conservar el corte y los importes históricos ya existentes. No borrar ni reiniciar tablas de saldos.
3. Ejecutar `npm run build:css` y `php scripts/build-release.php <composer.phar>`. Validar con `php tests/release.php <carpeta de publicación>`. Los dos ZIP son locales: no se descargan automáticamente desde GitHub ni se suben solos.
4. Publicar backend-rrhh.zip en el backend privado y software-rrhh.zip en la carpeta pública real del subdominio. Actualizar ambas mitades de la misma publicación. Los ZIP excluyen .env y uploads; preservar las dos carpetas uploads/uploads.751 y sus contenidos hasta tener inventario y respaldo comprobados. No usar esta fase para fusionarlas/eliminarlas.
5. Conservar el .env productivo y las adaptaciones .htaccess necesarias del hosting; revisar las reglas seguras de la guía técnica al coordinar frontend/backend. No reemplazar a ciegas una configuración propia del subdominio.
6. Comprobar login por rol, CSS, acceso a documentos, historial y horas aprobadas anteriores; Permisos y aviso lateral; informe por periodo; aprobación con motivo y reserva de compensación. Cualquier prueba de marcas/solicitudes en producción debe acordarse sobre una cuenta destinada a pruebas y quedar identificada, no sobre asistentes reales.
7. Reabrir operaciones cuando las verificaciones sean satisfactorias. Ante incompatibilidad, mantenerlas pausadas y restaurar una versión coherente de código/base según el respaldo, sin eliminar indiscriminadamente las tablas nuevas. Evitar sobrescribir actividad posterior al respaldo.

Paquete de revisión generado localmente: `scratch/releases/20261015-185040-740aba29-f4f310`, con backend-rrhh.zip, software-rrhh.zip y manifest.json; 338 archivos verificados. El manifiesto declara working_tree_dirty=true porque incluye los cambios aún sin commit de esta rama. Para publicar una versión trazable, regenerarlo después de integrar el PR. No se cambió .env, uploads, datos habituales ni Hostinger en esta fase. No se hizo commit, push ni PR en GitHub.
