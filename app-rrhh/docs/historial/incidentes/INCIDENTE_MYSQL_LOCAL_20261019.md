> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Incidente local de MariaDB — 19 de octubre de 2026

> **Ubicación sustituida el 23/10/2026:** el directorio activo de esta entrega histórica dejó de estar en uso tras una nueva recuperación. Consultar [operación local vigente](../../operacion/MARIADB_LOCAL_20261023.md). Conservar las carpetas anteriores; este archivo describe el rescate del 19/10.

Estado: datos rescatados y MariaDB 10.4.32 de XAMPP arrancando normalmente en 3306, sin modo de recuperación. Esta intervención no afecta Hostinger ni cambia el entorno del repositorio.

## Diagnóstico

El log de XAMPP registraba `InnoDB: Missing MLOG_CHECKPOINT at 41422031 between the checkpoint 41422031 and the end 41421824`, seguido de fallo de inicialización de InnoDB y aborto. El puerto 3306 estaba libre. También se observó espacio crítico en C:. No se atribuye el daño a las migraciones SQL del proyecto ni se afirma una causa de corrupción no comprobada.

Una segunda copia temporal agotó C:; se descartó únicamente esa copia incompleta y temporales propios. Los datos originales nunca se borraron ni se reemplazaron. El respaldo se trasladó a D: y se comparó por SHA-256 con los 2633 archivos originales.

## Rescate y verificaciones

1. Se mantuvo intacta la carpeta original `C:\xampp\mysql\data` y se respaldó `C:\xampp\mysql\bin\my.ini`.
2. Se creó `D:\Rescate-MySQL-20261019\respaldo-original`, su manifiesto SHA-256 y una copia de trabajo separada.
3. Se probó modo de recuperación 1 sobre la copia en 3307: no resolvió el checkpoint. El modo 6 permitió exportar todas las bases a `rescate-completo.sql`, sin ignorar errores de exportación.
4. Se inicializó una instancia limpia en `recuperado`, se importó el SQL y se compararon 1235 tablas de usuario, phpMyAdmin y pruebas: conteos y checksums coincidieron con la copia rescatable. Las tablas internas de mysql no forman parte de ese conteo comparado; sus definiciones y datos se incluyeron en la exportación/restauración.
5. Se cerraron las instancias temporales y se ajustó my.ini para usar el directorio recuperado, conservando el puerto 3306. No se cambió el .env del proyecto.
6. Tras el arranque normal se repitió la comparación de las 1235 tablas, se verificaron mysql/phpmyadmin/asistencia con mysqlcheck y se comprobó un apagado y arranque con los argumentos habituales de XAMPP (`--defaults-file=... --standalone`). Resultado: servidor activo, puerto 3306 y `innodb_force_recovery=0`.

Las comparaciones certifican coincidencia con los datos que se pudieron leer de la copia dañada, no la conservación de transacciones pendientes anteriores al fallo. Se conservaron los originales para cualquier revisión adicional. No se eliminaron ibdata1 ni registros InnoDB para forzar un arranque.

Referencia: [Modos de recuperación InnoDB — MariaDB](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-troubleshooting/innodb-recovery-modes). El modo 6 se utilizó para rescatar y exportar una copia, no para operación normal.

## Ubicación activa: no confundir con un respaldo prescindible

**`D:\Rescate-MySQL-20261019\recuperado` es ahora el directorio ACTIVO de datos de XAMPP. No borrarlo, renombrarlo ni moverlo como si fuera un temporal.**

El my.ini de XAMPP configura datadir, innodb_data_home_dir e innodb_log_group_home_dir en esa carpeta y tmpdir en `D:\Rescate-MySQL-20261019\tmp`. El log normal se encuentra dentro de recuperado como `mysql_error.log`; un acceso del panel que abra el log antiguo de C: puede mostrar el error histórico.

En la carpeta de rescate permanecen el respaldo original, copia de trabajo, exportación SQL, configuración previa, inventarios y resultados de mysqlcheck. Contienen datos locales: no subirlos al repositorio, al directorio público ni adjuntarlos a incidencias. Los scripts auxiliares de esta sesión quedaron en scratch ignorado por Git.

No restaurar simplemente my.ini.original para volver a C:, porque esa carpeta conserva la versión dañada anterior al rescate. Cualquier traslado posterior deberá hacerse con MySQL detenido, respaldo y verificación. C: sigue con poco espacio; resolverlo es una tarea adicional, sin borrar estos datos activos ni sus respaldos por inferencia.
