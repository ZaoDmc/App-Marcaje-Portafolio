> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 3: autorización, auditoría y consistencia de asistencia

Implementación del 2026-10-13 sobre la rama de fase 3 creada por el responsable desde `main`, que incluye el merge de fase 2. Esta fase sigue las prioridades del inventario funcional de fase 0; el plan MVC antiguo tiene otra numeración.

## Problemas y soluciones

| Problema | Cambio |
| --- | --- |
| Una petición manipulada podía resolver permisos o guardar acompañamiento propios | Validación compartida de sesión, rol operativo y practicante de la práctica en el servicio. En permisos se comprueba además que el revisor coincida con el usuario autenticado. |
| Los eventos de permisos no pertenecían al catálogo de auditoría y usaban una clave de ID incorrecta | Catálogo corregido e identificador `id_registro_afectado`. Nuevos eventos `REVISAR_JORNADA` y `EDITAR_ACOMPANAMIENTO`. |
| Aprobaciones de permisos podían continuar aunque fallara una excepción | La aprobación exige ambas excepciones y su auditoría dentro de la transacción. Un fallo revierte la operación completa. Rechazos y acompañamiento también exigen auditoría antes del commit. |
| Sin colación se limitaba la base a las horas planificadas, convirtiendo el descanso no tomado en extra | Se reconoce el tiempo trabajado dentro del turno sin descontar una pausa ficticia. Una jornada de seis horas o más sin colación permanece observada. |
| No había forma de dar una jornada por revisada sin modificar marcas | Opción `CORREGIDA / Marcar como revisada` en el selector de estado, con motivo obligatorio y auditoría de actor, fecha, valores anteriores y nuevos. |
| Acompañamiento omitía extras aprobadas | Total común: base y extras aprobadas en estados acumulables, más horas previas. El resumen tras marcar conserva las horas previas. |
| Existía una sugerencia arbitraria de 0,50 horas extra | Solo se proponen excesos calculados desde marcas. No se permite aprobar más de lo detectado. El formulario admite centésimas y permite ajustar aprobaciones anteriores. |
| Una aprobación anterior podía seguir sumando después de cambiar marcas | Corregir o anular marcas retira esa aprobación y exige revisarla nuevamente. La auditoría de la operación conserva los valores anteriores. |
| Dos peticiones podían validar la misma secuencia antes de insertar | Bloqueo de la jornada antes de leer marcas y validar; creación idempotente sobre la clave única práctica/fecha. Correcciones y anulaciones usan el mismo bloqueo padre. |
| Textos dañados por codificación | Mensajes de `MisDatosController.php` reparados a UTF-8 sin cambiar sus validaciones. |

## Regla de revisión y ejemplos

Solo ADMIN/SUPERVISOR puede revisar una jornada `OBSERVADA` o `PENDIENTE_REVISION` ajena. Se validan las marcas vigentes, su secuencia, fecha y entrada/salida. Se conserva la observación anterior, se agrega el motivo y se recalcula la base. No se modifican las marcas ni se aprueban extras implícitamente. No se incorpora otro estado ni se modifica el esquema de producción.

Con turno de 09:00 a 18:00 y ocho horas planificadas:

- Entrada 09:00 y salida 18:00 sin colación: nueve horas base, observada, cero extras propuestas. Al revisar: corregida y nueve horas reconocidas.
- Con colación marcada de 13:00 a 14:00: ocho horas base.
- Entrada 09:00 y salida 19:00 sin colación: nueve horas base y una extra por confirmar. Revisarla reconoce nueve; aprobar media hora extra reconoce 9,50.

Se conserva el umbral de extras superior a 0,25 horas y el atraso desde diez minutos. `VALIDA`, `OBSERVADA` y `CORREGIDA` acumulan; `INCOMPLETA`, `ANULADA` y `PENDIENTE_REVISION` no acumulan.

No hay recálculo masivo de registros históricos. Si una aprobación antigua supera las extras calculadas con la regla actual, la revisión se rechaza para evitar duplicar tiempo: primero debe ajustarse expresamente esa aprobación (se admite cero) con motivo y auditoría. Al cambiar marcas, la aprobación previa deja de ser aplicable.

## Archivos y validación local

Los cambios están comentados en AuthService, AuditoriaService, PermisosService, ReglasAsistenciaService, AsistenciaService, AcompanamientoModel y los controladores MarcarAsistencia, MisDatos y Acom. Se agregó `tests/business.php`; `composer.json` incorpora `test:business` (JSON no admite comentarios).

```powershell
& 'C:\xampp\php 8.3.35\php.exe' tests/business.php
& 'C:\xampp\php 8.3.35\php.exe' tests/configuration.php
& 'C:\xampp\php 8.3.35\php.exe' tests/routing.php
```

Las pruebas de negocio ejecutan los servicios con PDO SQLite en memoria y datos sintéticos. Cubren reglas de horas, autorización, revisiones, permisos, acompañamiento, duplicados secuenciales y rollback ante fallos de auditoría y recuperación. `pdo_sqlite` se necesita solo para estas pruebas CLI; producción continúa usando MySQL/MariaDB. No se instalaron dependencias adicionales.

El adaptador traduce únicamente el dialecto SQL usado por esas pruebas y elimina `FOR UPDATE`. Por tanto, estas comprobaciones no demuestran el bloqueo concurrente de InnoDB ni validan el esquema real del servidor. Para desplegar, las tablas implicadas deben usar un motor transaccional y existir la clave única de jornada por práctica/fecha, incluida en el esquema del repositorio.

## Comprobación funcional pendiente antes de publicar

En una base de pruebas MySQL/MariaDB, usar cuentas ADMIN, SUPERVISOR y SUPERVISOR/PRACTICANTE; probar el selector de revisión, correcciones y aprobación parcial; verificar auditoría y acumulados en pantalla. Enviar dos marcajes simultáneos de la misma entrada y comprobar una sola marca. Contrastar un caso histórico de extra aprobada por colación y ajustarlo expresamente antes de revisar.

Esta fase no elimina puentes, cambia uploads, modifica `.env`, despliega a Hostinger ni rediseña pantallas. La siguiente etapa debe separar las vistas y controladores pendientes antes de eliminar los puentes, conservando el contrato de rutas ya preparado.
