> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase operativa 4: permisos para hoy e interrupciones

Rama `permisos-mismo-dia`, creada desde main `aedf30d`. Esta numeración corresponde a PLAN_CORRECCIONES_OPERATIVAS, no a la migración MVC anterior.

## Comportamiento

- Solicitar ausencia completa o parcial para hoy y días futuros con jornada vigente. Fechas pasadas, fechas inexistentes y horas inválidas se rechazan en servidor y consulta de horario.
- Una ausencia parcial puede ser entrada tardía, salida anticipada o una interrupción intermedia. Por ejemplo, un turno 09:00–18:00 con colación 13:00–14:00 y permiso 11:00–12:00 conserva trabajo 09:00–11:00 y 12:00–18:00: siete horas previstas.
- Las interrupciones usan SALIDA_PERMISO y REGRESO_PERMISO, independientes de la colación. Los botones adicionales aparecen cuando corresponden; conservan la confirmación nativa y CSRF. Ausencias al inicio/final usan entrada/salida normales. Un permiso pendiente permite registrar hechos, pero la pausa queda observada hasta su aprobación. Rechazar durante una pausa no impide registrar el regreso.
- No se inventa regreso ni se suma la pausa como trabajo. Una interrupción incompleta/coherencia inválida no acumula horas. La jornada larga sin colación sigue contando el trabajo real y requiere revisión; la pausa por permiso se excluye de su duración.
- Una solicitud que cruza tiempo registrado queda PENDIENTE con requiere_revision=1 y explicación visible EN OBSERVACIÓN. Se consideran entradas abiertas hasta el momento de revisión y marcas vigentes; las anuladas no representan trabajo. Una secuencia incoherente requiere corrección.
- Aprobar vuelve a comprobar el cruce bajo bloqueo, aunque la solicitud inicialmente no estuviera observada. El supervisor corrige/anula/reemplaza las marcas desde Asistencia con respaldo, o rechaza el permiso; escribir un comentario por sí solo no autoriza el cruce. Si la solicitud nació observada, resolverla exige comentario del supervisor.
- Solicitud y auditoría se guardan juntas; aprobación, excepciones y recálculo de jornada parcial también. Fallos revierten la operación. Se conservan marcas y extras anteriores compatibles; incompatibilidades exigen revisión expresa. No se reclasifican jornadas históricas en masa.
- Se conserva una solicitud pendiente/aprobada por fecha, incluso si dos parciales no se cruzarían. Permisos rechazados no impiden volver a solicitar para hoy/futuro. El regreso sigue disponible al cerrar una pausa registrada.
- La excepción parcial conserva la ventana exterior, descanso y horas netas de ambos tramos. La solicitud y su ausencia delimitan la interrupción; las marcas registran lo sucedido realmente. Si el horario cambió y ya no coincide con lo solicitado, se bloquea aprobación para revisión.

## Migración y publicación

1. Respaldar la base antes de publicar. Ejecutar `database/2026-10-14_patch_permisos_mismo_dia_006.sql` en la base destino después de los parches anteriores de permisos. Es una migración aditiva de MariaDB: dos campos de observación y dos tipos de marca. Los estados existentes no cambian ni se borran datos.
2. Publicar backend y frontend/assets coordinadamente desde el commit aprobado. Es obligatorio incluir el SQL en el procedimiento de despliegue: los ZIP de la aplicación no ejecutan migraciones automáticamente. Las marcas nuevas no dependen de IDs numéricos fijos.
3. Conservar el .env y las reglas del hosting. Esta entrega no requiere variables, paquetes ni rutas nuevas.
4. Comprobar solicitud para hoy, estado observado cuando existen marcas, bloqueo de aprobación, corrección con motivo y un permiso intermedio con salida/regreso/colación/salida final.

La migración se aplicó solo a la base `asistencia` de XAMPP. Respaldo previo de esquema y catálogo en `scratch/phase4/esquema-antes-20261014-175703.json`; no contiene credenciales ni copia documentos. No se ejecutó ningún cambio en Hostinger. El ejecutor local está en scratch, excluido de Git; el SQL portable sí se versiona.

Las recuperaciones distribuidas entre varios días, colaciones trabajadas solicitadas de antemano y nuevas reglas de tiempo excepcional pertenecen a fases 5/6. Aquí se conserva el plan de recuperación de un solo día existente; no se presenta como implementado el reparto de horas.

## Validación y límites

Pruebas de negocio en SQLite: fechas/horas estrictas, identidad, permiso de hoy, duplicados, observación de marcas abiertas/cerradas, bloqueo y resolución con comentario, ausencia intermedia, colación parcial, seis escrituras de marcas, regreso incompleto, cierre de observación al aprobar, conservación de marcas y rollback por auditoría fallida. Se comprueba también el orden de escritura de marcas que comparten segundo.

Los recorridos de navegador usan MariaDB ficticia y cuentas example.test; incluyen solicitud de hoy con cruce, aprobación rechazada sin crear excepciones, rechazo con motivo y aprobación de una ausencia intermedia futura con ambos tramos y siete horas previstas. Se conservan los recorridos de otros módulos y confirmación/cancelación de marcajes. Las pruebas UI incluyen observaciones largas y listas pobladas sin desplazamiento horizontal.

Los bloqueos se implementaron en servidor; estas pruebas no demuestran concurrencia InnoDB entre varios dispositivos ni validan Hostinger o Safari móvil. La recuperación de varios días y tiempo excepcional aún no se prueba porque no pertenece a esta entrega. Los permisos parciales antiguos incompatibles con el horario vigente se detienen para revisión; no se reparan automáticamente.
