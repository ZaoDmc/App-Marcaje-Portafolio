# MariaDB local: recuperación y arranque del 23/10/2026

Esta intervención corresponde únicamente al equipo del responsable. No es una migración del repositorio ni modifica Hostinger. No copiar estas rutas a servidores o colaboradores.

## Configuración que debe conservarse

| Elemento | Estado al finalizar la intervención |
| --- | --- |
| Motor | MariaDB 10.4.32 de XAMPP; sin cambio de versión |
| Puerto local | 3306 |
| Servicio Windows | `mysql`, inicio **manual**, no automático |
| Configuración | `C:\xampp\mysql\bin\my.ini` |
| Directorio activo | `D:\Rescate-MySQL-20261023\restaurado-servicio` |
| Temporales | `D:\Rescate-MySQL-20261023\tmp` |
| Modo de recuperación | No configurado para la operación normal |

**El directorio activo no es un respaldo prescindible aunque esté dentro de una carpeta llamada Rescate.** El directorio del 19/10 permanece conservado, pero ya no es el activo. No restaurar configuraciones antiguas por su nombre.

## Arranque y apagado

Abrir el panel XAMPP con **Ejecutar como administrador**. El servicio se registró con el nombre predeterminado `mysql`, por lo que no fue necesario cambiar `xampp-control.ini`. La instalación provisional `RRHHMariaDBLocal` se retiró antes de utilizarla.

Desde una consola PowerShell administradora pueden utilizarse `Start-Service mysql`, `Get-Service mysql` y `Stop-Service mysql`. No lanzar otra instancia sobre el mismo directorio, ni usar Finalizar tarea como apagado habitual. El servicio no arranca automáticamente con Windows.

Los ensayos ocultos de `mysqld --standalone` sin redirección y el reinicio desde el panel anterior dejaron mensajes de log dentro de archivos Aria y advertencias de tamaño. El arranque con consola y salida explícita pasó un reinicio sin esa contaminación; se eligió el servicio Windows para evitar depender de ese lanzador. No se ha determinado la causa original de toda la corrupción ni se atribuye a SQL de la aplicación, hardware o un parche de PHP.

## Respaldo y recuperación realizados

1. Se confirmó el error inicial de apertura de `mysql.db` en los eventos de Windows. El log antiguo de C: no representaba el directorio activo.
2. Con MariaDB detenido se copió íntegramente el directorio anterior: **4.180 archivos verificados por SHA-256**, más `my.ini`, en `D:\Rescate-MySQL-20261023\respaldo-previo`.
3. Se ensayó reparar `mysql.db` sobre una copia. La recuperación conservó sus cero registros; `mysql.global_priv` conservó las cuatro cuentas de la copia. Los fallos de reinicio posteriores impidieron dar por suficiente esa primera reparación.
4. Desde una copia legible se exportaron todas las bases a `rescate-completo.sql`, sin ignorar errores, y se inventariaron **2.010 tablas** de usuario/phpMyAdmin/pruebas por conteo y checksum. Los secretos y registros permanecen fuera de Git y del directorio público.
5. Se ensayó una importación lógica completa en `reconstruido`, con comparación coincidente. Ese directorio volvió a presentar fallos tras el arranque problemático y **no es el activo final**.
6. El directorio final `restaurado-servicio` procede de otra copia de la instancia legible inventariada. Se repararon y normalizaron metadatos Aria y se corrigieron las tablas internas que señalaron las comprobaciones; no se sustituyeron tablas funcionales individualmente ni se importó el schema del proyecto sobre los datos.
7. Se configuró XAMPP para el directorio final y se registró el servicio Windows manual. Los originales, el SQL, configuraciones e inventarios permanecen conservados en D:.

Los conteos/checksums comparan la copia legible con el origen inventariado; no acreditan recuperar transacciones que ya faltaban antes del respaldo. `mysqlcheck` puede informar corrupción aunque termine con código cero: hay que revisar también los mensajes, no solo el código de salida.

## Verificación y límites

Tras el Stop/Start confirmado por el responsable desde el panel abierto como administrador, se comprobaron servicio `mysql` Running/Manual, puerto 3306, innodb_force_recovery=0, **69 tablas OK sin advertencias** (41 de `asistencia` y 28 internas) y **2.010 conteos/checksums coincidentes** con el origen inventariado. No quedaron instancias temporales en 3307/3308. El estado final se resume en [Estado actual](../proyecto/ESTADO_ACTUAL.md). Un arranque correcto no garantiza que no vuelva a existir un fallo: mantener respaldos y apagado limpio.

No se actualizaron PHP/MariaDB, `.env` del proyecto ni producción. No publicar el SQL, los inventarios privados o las carpetas de rescate. Los scripts auxiliares permanecen en `scratch/mysql-recovery-tools`, ignorado por Git.

Referencias oficiales: [aria_chk](https://mariadb.com/docs/server/clients-and-utilities/aria-clients-and-utilities/aria_chk), [REPAIR TABLE](https://mariadb.com/docs/server/reference/sql-statements/table-statements/repair-table), [log de errores en Windows](https://mariadb.com/docs/server/server-management/server-monitoring-logs/error-log) y [opciones de servicio Windows](https://mariadb.com/docs/server/server-management/starting-and-stopping-mariadb/mariadbd-options).
