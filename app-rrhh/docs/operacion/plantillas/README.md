# Plantillas de mantenimiento manual

Estos archivos no son migraciones, no se importan durante un despliegue y no forman parte de la baja lógica normal del portal.

## SQL directo solicitado por el responsable

El responsable solicitó posteriormente una [alternativa de DELETE directos](eliminar_practicante_directo.sql), sin procedimiento almacenado. Se reemplaza el `0` de `@id_practicante` por el ID de persona; `@id_usuario` se obtiene desde su vínculo real. Este archivo elimina datos operativos propios, cuenta y auditoría del actor/sesiones/ficha, sin conservar la cuenta como hace la opción predeterminada del procedimiento anterior. La eliminación de auditoría es parte de la petición excepcional actual, no una regla de operación normal del portal.

Requiere respaldo, tablas InnoDB, revisión del esquema instalado, ausencia de escrituras concurrentes y un cliente que se detenga ante el primer error. No desactivar claves foráneas. Si una instrucción falla, ejecutar `ROLLBACK` en la misma conexión y no continuar a `COMMIT`: este SQL directo **no tiene un manejador de errores ni garantiza reversión automática**. Una cuenta que haya actuado sobre notas, liderazgos u otros registros ajenos puede seguir referenciada y bloquear el borrado. No agregar `OR id_evaluador = @id_usuario` o `OR asignado_por = @id_usuario` para borrar filas de otras personas y desbloquearlo.

No borra catálogos ni archivos físicos. Los registros de auditoría de otros actores sobre otras tablas no se purgan de forma global: esta consulta no constituye anonimización completa. Los cambios futuros de esquema requieren revisar la consulta otra vez.

Se completaron **23 comprobaciones correctas** del SQL directo sobre una base ficticia local creada con el schema actual: ID cero sin efectos, eliminación de persona/cuenta/dependencias recientes y auditoría correspondiente, conservando persona, práctica, jornada y marca de otro usuario. La base ficticia se retiró. No se ejecutó contra la aplicación ni producción. El auxiliar está en `scratch/plantilla-borrado/verificar_directo.php`. Estas pruebas son distintas de las 31 del procedimiento conservador descrito abajo.

## Alternativa con procedimiento y vista previa

[Plantilla SQL](eliminar_practicante.sql), preparada por solicitud explícita del responsable el 26/10/2026. Permite borrar la ficha y sus datos operativos propios, con vista previa inicial. Requiere respaldo, esquema completo actual del proyecto en MariaDB, permisos para crear/retirar un procedimiento y ausencia de escrituras concurrentes. Usar una sesión SQL sin otra transacción abierta.

1. Elegir la base correcta en phpMyAdmin; no seleccionar una tabla como sustituto de comprobar el entorno.
2. Abrir el archivo y sustituir `SET @id_practicante = NULL` por el ID de persona. No usar `id_practica` ni `id_usuario`.
3. Mantener `@confirmar_borrado = 0`. Importar o ejecutar el archivo completo. Revisar identidad, correo, cantidades y rutas de documentos mostrados. La vista previa no borra registros ni garantiza que todas las dependencias permitirán la eliminación real.
4. Si corresponde eliminar, confirmar respaldo y mantenimiento; volver a ejecutar el archivo completo con el mismo ID y `@confirmar_borrado = 1`. Esa opción confirma el borrado dentro del procedimiento: no hay un COMMIT posterior para decidir otra vez.
5. Por defecto `@eliminar_cuenta = 0`: la cuenta queda desvinculada de la ficha, inactiva, con roles desactivados, sesiones cerradas y tokens invalidados. Esto conserva la autoría histórica y la auditoría. La cuenta sigue existiendo y su correo continúa ocupado.
6. Usar `@eliminar_cuenta = 1` solo si también se desea eliminar el acceso. La auditoría o la autoría en datos de otros puede impedirlo. No borrar esas dependencias para forzar el resultado: el procedimiento revierte sus cambios si falla una FK y puede volver a utilizarse conservando la cuenta cuando no existan otras incompatibilidades.

El procedimiento se llama `rrhh_plantilla_eliminar_practicante_20261026`, utiliza `SQL SECURITY INVOKER` y se retira al terminar. Si el cliente detiene el archivo ante un error, puede quedar el procedimiento: eliminar únicamente ese auxiliar con el DROP indicado al pie del SQL antes de reimportar. No sustituirlo por `DROP DATABASE`, desactivar claves foráneas ni hacer un borrado global.

## Alcance y límites

- Borra prácticas propias, horarios, marcas y jornadas, permisos y recuperaciones, tiempo excepcional y compensaciones, notas propias y su historial/detalle, documentos registrados, pertenencias, liderazgos y acompañamiento de la persona elegida.
- No borra catálogos, rúbricas, configuración global, auditoría ni filas ajenas para desbloquear el borrado. Si otras notas referencian el liderazgo eliminado, una FK puede impedir toda la operación.
- No elimina archivos físicos de `uploads`; devuelve las rutas registradas para tratarlas después con un procedimiento de archivos autorizado y revisado. No usar comandos de borrado basados en una ruta supuesta por el ID.
- No es una anonimización completa: pueden quedar datos personales en la cuenta conservada, auditoría, documentos físicos y respaldos.
- Se basa en el schema del repositorio. Bloquea tablas adicionales con identificadores operativos conocidos, FK de tablas no inventariadas y bases con tablas no InnoDB. Estos controles no sustituyen revisar cambios de esquema, referencias sin FK, triggers ni integraciones futuras.
- Para operación habitual, usar la baja lógica del portal y su auditoría. La solicitud actual autoriza esta plantilla excepcional; no cambia las reglas normales de conservación de historiales.

## Validación

El 26/10/2026, después de que el responsable activara MySQL, se completaron **31 comprobaciones correctas** sobre una base ficticia nueva en MariaDB local. Se verificaron ID vacío/inexistente, vista previa sin cambios, bloqueo de tabla adicional, reversión por auditoría y por notas ajenas que referencian un liderazgo, borrado de todas las dependencias propias representadas, conservación de la otra persona con su práctica/jornada/marca, cuenta retenida inactiva con rol desactivado, sesión cerrada y token invalidado, y eliminación opcional de una cuenta sin referencias.

La primera ejecución detectó un nombre de columna incorrecto en la desactivación del rol. Se corrigió a `usuarios_roles.rol_activo` y la ejecución completa posterior pasó. La reversión impidió confirmar esa ejecución fallida; no se utilizó una base real.

El auxiliar de comprobación está en `scratch/plantilla-borrado/verificar.php`, ignorado por Git; crea una base nueva `rrhh_borrado_fixture_*` local y la retira al finalizar. Las bases ficticias de ambas ejecuciones fueron retiradas. No se ejecutó la plantilla en la base de la aplicación ni en producción. La prueba usa el schema del repositorio: antes de usarla en otra instalación, revisar sus tablas, permisos, triggers y respaldos.
