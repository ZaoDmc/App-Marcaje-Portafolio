# Permisos y recuperaciones

## Ausencia solicitada

El ajuste diario de descanso tiene un flujo independiente: [Cambio de colación](CAMBIO_COLACION.md), disponible desde 2.1.0. No equivale a ausencia, recuperación ni modificación del horario semanal.

Desde 1.5.0 el practicante puede solicitar ausencias pasadas, actuales o futuras, completas o parciales, dentro de su práctica activa y en fechas con jornada pactada. Una ausencia intermedia conserva el trabajo anterior y posterior. No se habilitan solicitudes de prácticas cerradas.

Si se cruza con marcas de trabajo, la solicitud se admite en observación; el supervisor debe resolver el cruce antes de aprobar. Se validan otra vez los cruces al aprobar, incluso si surgieron después de solicitar. No modificar ni eliminar marcas automáticamente para hacer que el permiso encaje.

Una interrupción intermedia aprobada habilita salida y regreso por permiso en su contexto horario. Es distinta de la colación y del fin de jornada.

## Plan distribuido

### Política nueva desde 2.0.0

Los tramos nuevos, incluidos los adicionales de permisos anteriores, solo permiten **sábados 10:00–20:00**, desde el día de ausencia en adelante y dentro de la práctica. No pueden ocupar jornada ordinaria ni reutilizar minutos reservados. Un sábado feriado conserva la autorización especial expresa del supervisor; un feriado entre semana ya no es opción.

El corte para cada sábado es el **viernes anterior a las 12:00**, en la zona del portal. 11:59:59 es válido; 12:00:00 ya es tardío. Si se solicita una ausencia de emergencia después del corte, se puede elegir el sábado siguiente. Cada sábado de un plan distribuido tiene su propio corte.

El servidor rechaza automáticamente el envío fuera de plazo y muestra la alternativa del sábado siguiente. Se audita `RECHAZO_PLAZO_RECUPERACION`; no se crea un permiso RECHAZADO ni PENDIENTE y no se reservan minutos. Si falla la auditoría se registra el fallo en log y el envío sigue rechazado. No existe una tarea que rechace solicitudes puntuales aún pendientes de aprobación al llegar el viernes.

Los tramos guardan `politica_recuperacion=2` y `solicitado_utc`, capturados al validar el envío. La aprobación usa ese instante, no la hora en que el supervisor revisa. Puede aprobarse después del corte o después de realizado el sábado y validar sus marcas completas en la misma operación. La aprobación no inventa trabajo ni elimina otras incidencias.

Los tramos anteriores conservan política 1; no se cambian fechas, estados, modalidades o créditos ya reconocidos. La restricción se activa para propuestas nuevas, no mediante una conversión silenciosa de expedientes anteriores. Antes de publicar código, respaldar y aplicar **parche 015**; `schema.sql` contiene los campos para instalaciones nuevas. No importar schema encima de producción.

### Política anterior: solo revisión de tramos ya existentes

La tabla y el ejemplo siguientes describen política 1. No son opciones para nuevas solicitudes.

| Forma de recuperar | Condición |
| --- | --- |
| Colación trabajada | Solicitud previa del tramo real de colación de su horario y aprobación; no marcar inicio/fin de descanso si se trabaja ese tramo |
| Extensión del turno | Tiempo fuera de la jornada ordinaria y dentro de la ventana de recuperación |
| Sábado | Solicitud y validación obligatoria con marcas y motivo |
| Feriado | Autorización expresa del supervisor para recuperación; no convierte el día en jornada ordinaria |

La ventana de recuperación es lunes a sábado entre **10:00 y 20:00**, respetando fechas de práctica y tramos disponibles. El plan inicial debe sumar exactamente los minutos laborales que se faltarán; puede distribuirse en varios días. No reutilizar un tramo ordinario, ausente o reservado en otro expediente.

## Aprobación y trabajo acreditado

En planes de política 1 ya existentes, la recuperación podía ocurrir antes o después de la ausencia: por ejemplo, viernes 17:00–20:00 para faltar lunes 10:00–13:00. Ese flujo se conserva para revisar lo guardado. Los tramos nuevos de política 2 siguen la restricción sabatina y el corte semanal anteriores.

Los ajustes son por fecha: excepción de ausencia y tramos de recuperación autorizados. No se modifica el horario semanal ni se necesita una tarea que lo restaure. Mi asistencia muestra la recuperación diaria separada del turno ordinario para no confundir horas ordinarias con recuperaciones sujetas a validación.

El supervisor puede validar recuperación terminada aunque la ausencia aún sea futura. Si después aparecen marcas de trabajo durante la ausencia, el recálculo reabre la recuperación y retira sus minutos validados hasta resolver el cruce, conservando el historial.

Programar o aprobar el permiso no equivale a validar trabajo. El supervisor reconoce minutos efectivamente acreditados con marcas completas, tramo terminado y motivo, limitados al saldo. La colación trabajada se acredita con entrada/salida y revisión; omitir la pausa sin aprobación no genera recuperación automática.

La aprobación puede realizarse después de ejecutado el permiso, incluso transcurridos más de cinco días. Al aprobar, los tramos ya terminados con marcas completas se validan en la misma transacción, con motivo y auditoría del supervisor; se recalculan horas y se retiran las observaciones resueltas. Los tramos futuros, sin evidencia completa o con otras incidencias no se dan por trabajados ni se limpian forzosamente. No hay un plazo de cinco días para revisar permisos ordinarios; ese plazo pertenece a la futura política de canje excepcional.

Cambios en horario, feriados o marcas pueden exigir REPROGRAMAR o reabrir la validación. Conservar firma/evidencia, revisión y auditoría. Las recuperaciones validadas forman parte del cálculo correspondiente, sin contarlas otra vez como horas extra ni como crédito excepcional.

El calendario común se administra desde Catálogos por ADMIN; no se cargan feriados supuestos. La acción Generar ausencias no está disponible para SUPERVISOR.

## Código y datos

`src/Services/PermisosService.php`, `src/Services/RecuperacionesService.php`, controladores/vistas de Permisos por rol y compartidos; tablas `permisos_practicante`, `recuperaciones_permiso`, `feriados` y evidencias de jornada.

Procedencia: [mismo día e interrupciones](../historial/operativa/FASE_4_PERMISOS_MISMO_DIA.md) y [planes de recuperación](../historial/operativa/FASE_5_RECUPERACION_VARIOS_DIAS.md). Para crédito por permanencia excepcional, usar [Tiempo excepcional](TIEMPO_EXCEPCIONAL.md), no este saldo.
