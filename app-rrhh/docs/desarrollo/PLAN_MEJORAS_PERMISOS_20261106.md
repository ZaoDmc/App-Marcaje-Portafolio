# Entregas de permisos, compensación y ajustes de horas

Solicitud del responsable: 6 de noviembre de 2026. Una rama y PR por entrega desde `dev`; esperar confirmación antes de comenzar la siguiente. Sin autorización de commit, push ni despliegue.

## Rectificación del superior — reglas confirmadas, pendientes de implementación

Estas decisiones posteriores sustituyen las reglas incompatibles del plan anterior. Los apartados de entregas 1 y 2 conservan evidencia de lo implementado; no son autorización para seguir ampliando recuperación entre semana o nuevos créditos excepcionales.

| Regla confirmada | Comportamiento solicitado |
| --- | --- |
| Recuperación | Solo sábados, entre 10:00 y 20:00. Retirar opciones nuevas de extensión entre semana, colación trabajada y recuperación del viernes anterior a la ausencia. |
| Corte | La solicitud para un sábado debe enviarse antes del viernes previo a las 12:00, en la zona de la aplicación. A las 12:00 ya está fuera de plazo. Las solicitudes enviadas a tiempo siguen pendientes y pueden aprobarse después del corte. |
| Emergencia después del corte | Permitir solicitar el sábado de la semana siguiente, sujeto a su propio corte. No rechazar toda la solicitud por haber pasado el corte del sábado inmediato. |
| Rechazo automático | Rechazar la elección del sábado cuyo corte ya pasó, con mensaje y trazabilidad. No rechazar solicitudes puntuales solo porque el supervisor todavía no las aprobó. Definir la representación del intento rechazado al implementar. |
| Colación | Apartado propio para cambiar, solo en la fecha elegida, entre 13:00–14:00 y 14:00–15:00; conserva una hora de descanso. Solicitud durante la jornada y aprobación de ADMIN/SUPERVISOR, incluso después de terminada, con recálculo según marcas. |
| Tiempo excepcional | Retirar nuevos reconocimientos; respetar créditos y canjes ya aprobados hasta su vencimiento, conservando historial. El tratamiento de solicitudes anteriores todavía pendientes se debe resolver antes de activar la retirada; no se dio autorización para rechazarlas o borrarlas automáticamente. |
| Horario semanal | Gestión manual exclusiva del personal autorizado de RRHH. Sin apartado de permiso de cambio de horario semanal. Los ajustes diarios autorizados no sobrescriben el horario base. |
| Marcaje sin jornada | Rechazar en servidor con «No cuenta con jornada asignada». |
| Entrada muy anticipada | Rechazar desde una hora antes de la entrada pactada para ese día, con «No cuenta con jornada asignada». La interpretación de borde se implementará y probará explícitamente: adelanto de 60 minutos o más. |
| Acumulado | No considerar trabajo fuera del horario autorizado; conservar recuperación sabatina aprobada y créditos/canjes ya aprobados como excepciones explícitas, sin crear nuevas horas extra. |

### Nuevo orden de entregas

1. Cerrar o integrar la bandeja 1.7.0 ya preparada. No mezclar sus cambios pendientes con la siguiente rama.
2. `permisos-recuperacion-sabados`: preparada localmente desde dev `0cf8811`, versión 2.0.0. Solo nuevos tramos sabatinos con corte viernes 12:00, evidencia UTC de envío, auditoría del rechazo y aprobación tardía. Los tramos anteriores conservan política 1; los nuevos, incluidos adicionales, usan política 2. Requiere 015. Pasaron 456 pruebas de negocio, 276 MVC, 21 de configuración y 4 de migración MariaDB ficticia. Sin publicación.
3. `permisos-cambio-colacion`: preparada localmente desde dev `6b5da8c`, 2.0.0 → 2.1.0 (MENOR). Solicitud para hoy, aprobación incluso posterior a la jornada, recálculo, cruces y auditoría. Tabla y parche 016 requeridos antes del código. Ver [módulo](../modulos/CAMBIO_COLACION.md). Sin publicación.
4. `retirar-nuevos-tiempos-excepcionales`: bloquear tanto interfaz como POST de nuevos reconocimientos y preservar aprobados. Definir tratamiento visible de pendientes anteriores sin borrar historial.
5. `marcajes-jornada-asignada`: rechazar días sin turno y entradas con adelanto de 60 minutos o más; excluir trabajo fuera del horario autorizado y probar recuperaciones sabatinas autorizadas.
6. Reconsiderar después los ajustes manuales de Reportes y el detalle emergente de Asistencia, que quedan pendientes.

Antes de activar las restricciones nuevas, revisar los planes de recuperación existentes; no cancelar ni transformar aprobaciones anteriores silenciosamente. La solicitud no definió todavía una política de conversión de esos planes. Cada entrega debe conservar fechas, evidencia, aprobación tardía, auditoría y autorización del servidor y mantener actualizado el schema si necesita datos nuevos.

Estado actualizado: el responsable integró la bandeja 1.7.0 y se preparó la entrega de recuperación sabatina 2.0.0 en su propia rama. El resto de la rectificación permanece pendiente. La observación anterior de cambios sin commit de la bandeja ya no describe el árbol actual.

## Plan anterior y evidencia de entregas

| Entrega | Rama prevista | Alcance |
| --- | --- | --- |
| 1, integrada en dev por el responsable | `permisos-recuperacion-anticipada` | Ausencias pasadas/actuales/futuras; recuperación anterior a la ausencia, validación con evidencia y ajustes por fecha sin cambiar horario semanal |
| 2, integrada en dev por el responsable | `tiempo-excepcional-canje-autonomo` | Una aprobación de reconocimiento; canje decidido por practicante sin segunda aprobación; vencimiento y texto explicativo |
| 3, preparada localmente | `permisos-bandeja-superiores` | Integrar tiempo excepcional y compensación como categorías de Solicitudes de permisos, para ADMIN/SUPERVISOR |
| 4, pendiente | `reportes-ajustes-horas-auditados` | Sumar/restar horas mediante movimientos con actor, fecha y motivo; conservar marcas e historial y evitar autosupervisión |
| 5, pendiente | `asistencia-detalle-emergente` | Revisar primero lo ya integrado y completar diálogo de ficha/corrección reutilizando Permisos y Practicantes; móvil, teclado, temas y errores |

## Entrega 1

Base: `dev` local `cd283df`, coincidente con la referencia local `origin/dev`; no se consultó el remoto de nuevo. Versión 1.4.1 → 1.5.0 (MENOR por ampliación compatible). Sin SQL ni dependencias nuevas. La recuperación se sigue solicitando antes de comenzar su tramo; permitir una ausencia pasada no habilita inventar recuperación histórica.

Las pruebas usan SQLite ficticio, no datos reales: aprobación, ausencia pasada, recuperación anterior a ausencia, evidencia, no duplicación, horario base intacto y reapertura ante cruces. No acreditan concurrencia InnoDB, recorrido visual ni producción.

La aprobación tardía, incluso después de cinco días, valida en la misma transacción los tramos terminados con marcas completas y recalcula sus jornadas. Evidencia incompleta conserva revisión pendiente; no se eliminan incidencias ajenas.

Resultado local: 398 comprobaciones de negocio (29 nuevas), 244 MVC y 21 de configuración correctas; sintaxis PHP y `git diff --check` correctos.

## Acuerdos para la entrega 2

Confirmado por el responsable: entrar **más tarde** o salir más temprano; **5 días hábiles desde la aprobación**, según horario individual y excluyendo feriados. El supervisor registra la autorización del jefe con motivo y solo aprueba el reconocimiento. El canje debe seguir respetando saldo disponible, fechas, horario y superposiciones; no concede edición del horario semanal.

Confirmado e implementado: contar cinco jornadas posteriores al día de aprobación y terminar al fin del turno del quinto día. Los expedientes existentes conservan política y vencimiento originales; el parche 014 los identifica como política 1. Las solicitudes nuevas guardan política 2. Texto aplicado con «entrar más tarde» y vencimiento sin borrar historial. Entrega 1.5.0 → 1.6.0, MENOR, desde dev `5b0297f`. Requiere aplicar 014 antes del código; sin aplicación a bases reales ni despliegue. La bandeja unificada de superiores sigue siendo la entrega 3.

## Acuerdos para las entregas restantes

Entrega 3: rama desde dev `fcfc39d`, versión 1.6.0 → 1.7.0 (MENOR), sin SQL nuevo; requiere 014 anterior. Bandeja categorizada con detalle emergente, filtros y paginación para ambos superiores. 435 comprobaciones de negocio, 276 MVC, 21 de configuración y ocho recorridos Edge Coffee a 390/1280 px correctos. Sin publicación. La entrega siguiente es el libro de ajustes manuales auditados desde Reportes, pendiente de confirmación del responsable.

Entrega 2 validada localmente: 417 comprobaciones de negocio (19 nuevas), 244 MVC, 21 de configuración y 3 de migración MariaDB ficticia. Sin prueba visual ni concurrencia InnoDB. El parche no se aplicó a la base habitual.

ADMIN recibe los mismos cambios que SUPERVISOR donde aplique. En Reportes, definir límites del total y usar un libro de ajustes auditado, sin sobrescribir marcas; comprobar acumulados en todas las pantallas. Si requiere tabla nueva, incluir migración y schema. La entrega visual revisará lo que ya exista para no duplicar modales.
