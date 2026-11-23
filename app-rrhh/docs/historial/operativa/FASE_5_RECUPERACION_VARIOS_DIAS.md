> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase operativa 5: recuperación en varios días

Rama `recuperacion-horas-varios-dias`, desde main `97ba027`. Corresponde al plan de correcciones operativas, no a la antigua fase 5 de migración MVC.

## Funcionamiento

El permiso contiene una ausencia y un plan de tramos. Cada tramo registra fecha, tipo, inicio/fin, minutos previstos, autorización y minutos posteriormente validados. El plan inicial debe sumar exactamente los minutos laborales de ausencia; colación dentro de la ausencia no produce deuda. Se rechazan horas/fechas inválidas, domingos, tramos fuera de 10:00–20:00, fuera de vigencia de práctica o superpuestos con ausencia, trabajo ordinario u otra reserva. La recuperación se solicita antes del inicio del tramo; no se aplica el vencimiento de siete días de tiempo excepcional.

Tipos disponibles según horario individual:

- COLACION: solo la colación existente del horario vigente, con posibilidad de seleccionar una parte de ella.
- EXTENSION: antes/después de la jornada asignada, dentro de 10:00–20:00. Si existen dos ventanas, el editor distingue sus horas aunque compartan el mismo tipo.
- SABADO: dentro de 10:00–20:00, sin reutilizar trabajo ordinario de un horario que ya tenga ese sábado asignado.
- FERIADO: dentro de 10:00–20:00, con consentimiento expreso y motivo del supervisor.

Ejemplos: cinco horas de ausencia se pueden repartir entre cinco colaciones de una hora, extensiones de dos/dos/una hora en días con salida anticipada respecto de las 20:00, o tres colaciones y dos horas de sábado. Todos los tramos requieren aprobación y comprobación posterior.

## Autorización y validación

Autorizar el permiso pasa los tramos a PROGRAMADA; no reconoce horas. La ausencia conserva su excepción propia, pero la recuperación nueva no reemplaza el turno completo por ASISTENCIA_EXTRAORDINARIA. El supervisor verifica cada tramo terminado en Permisos mediante motivo obligatorio y marcas efectivamente registradas, con rol/propiedad, CSRF y auditoría.

Para colación trabajada solicitada y autorizada, el practicante no marca inicio/fin de colación dentro de ese tramo trabajado. Entrada/salida y otras pausas delimitan el trabajo real. Si toma descanso, debe registrarlo; esos minutos no se recuperan. Los minutos se cuentan por intersección con el tramo, redondeados hacia abajo al minuto completo, y se limitan al saldo de ausencia. Se requieren marcas coherentes cuando existen; una jornada incompleta/anulada necesita resolución previa. Un tramo sin trabajo registrado puede cerrarse expresamente con cero y motivo, conservando todo su saldo.

La recuperación se reconoce después de que termina la ausencia de origen, aunque se haya planificado trabajo previo. No se valida mientras esa ausencia cruce tiempo registrado como trabajado. Corregir marcas del día recuperado reabre su validación; una corrección que cruza la ausencia original también retira créditos dependientes y recalcula los otros días afectados. Resolver una observación no significa aprobar extras ni inventar marcas.

Si horario/calendario del tramo cambió antes de reconocerlo, pasa a REPROGRAMAR sin crédito y se libera saldo para otra solicitud. Si se trabajó menos de lo previsto, se registra solo lo trabajado y el practicante puede solicitar tramos adicionales sobre saldo libre. Esos tramos requieren autorización nueva; no se alteran la ausencia aprobada ni las validaciones anteriores. Reservas pendientes y trabajo ya validado impiden duplicar saldo o reutilizar un intervalo.

La jornada guarda en horas_calculadas el trabajo ordinario más recuperación validada. El ledger permite distinguir ambos; la recuperación pendiente no suma y los minutos de recuperación autorizada no se proponen también como extras. Se mantienen los acumulados/informes existentes. Las nuevas jornadas de sábado no acumulan automáticamente: requieren validar recuperación o aprobar el tiempo detectado mediante revisión expresa del supervisor. Las jornadas históricas conservan politica_recuperacion_v2=0; no hay conversión masiva ni borrado de extras anteriores.

## Calendario y roles

ADMIN mantiene el calendario común en `admin/catalogos.php?catalogo=feriados`: nombre, fecha, activación/desactivación y auditoría. La tabla se crea vacía; no se cargan fechas supuestas ni se consulta una API externa. Los feriados comunes excluyen la jornada ordinaria y se conservan también las excepciones individuales FERIADO existentes. Recuperar en un feriado requiere autorización expresa del supervisor, además de comprobar trabajo real.

Generar ausencias se oculta para SUPERVISOR y se bloquea en controlador y servicio, incluso por POST directo. ADMIN conserva la herramienta; al generar, el horario vigente excluye feriados. Generar jornadas faltantes y registrar un calendario son acciones diferentes.

## Publicación

1. Respaldar la base de producción y ejecutar `database/2026-10-14_patch_recuperaciones_varios_dias_007.sql`, después del parche de fase 4, seleccionando la base completa en phpMyAdmin. Crea `feriados`, `recuperaciones_permiso` y agrega `politica_recuperacion_v2` a `jornadas_asistencia`. La migración es aditiva para MariaDB y se puede repetir.
2. Actualizar backend y frontend/assets coordinadamente desde el commit aprobado. Incluir el JavaScript nuevo y Tailwind compilado; el editor usa versión por contenido. Los ZIP no ejecutan SQL automáticamente.
3. Conservar .env, uploads y reglas del servidor; no se requieren variables ni dependencias nuevas.
4. Registrar los feriados reales desde ADMIN y comprobar permiso de varios tramos, autorización especial, marcajes, validación, saldo parcial y ausencia del botón de generación en SUPERVISOR.

En XAMPP el parche ya está aplicado y se comprobó su repetición. Respaldo del esquema previo: `scratch/phase5/esquema-antes-20261014-190224.json`. Solo se ampliaron esquema/tablas locales; no se alteraron marcajes históricos ni se publicó en Hostinger. Los permisos históricos sin tramos conservan su representación y flujo anterior; no se inventaron validaciones retrospectivas.

## Verificación y límites

Las pruebas de negocio usan SQLite aislada y servicios reales; cubren exactitud de minutos, calendario, autorización de feriados, identidad, reservas, rollback por auditoría, colación/extensión, crédito parcial, reprogramación, cambios en marcas/origen y política histórica de sábados. No simulan bloqueos InnoDB concurrentes.

Los recorridos E2E usan MariaDB ficticia, Edge y SMTP local. Prueban plan de cinco horas en tres colaciones y sábado, calendario ADMIN, validación de trabajo, cierre con cero, solicitud/autorización adicional y POST de generación denegado al supervisor. El escenario terminado se siembra exclusivamente en la fixture: modela solicitud/autorización anteriores a las marcas, sin cambiar el reloj ni admitir solicitudes pasadas en el formulario. UI comprueba reflujo, filas independientes, restauración de datos y dos ventanas de extensión.

Se aisló también el error_log de PHP. Así se detectó y corrigió pérdida de región/comuna por variables indefinidas en normalización de practicantes; la prueba de perfil comprueba ahora ambos valores persistidos. No se restauran datos históricos sin su respaldo.

El nuevo modelo de tiempo excepcional y compensación con vencimiento corresponde a fase 6; no se afirma implementado. La revisión local no sustituye la comprobación de Hostinger ni acredita concurrencia entre dispositivos. Las fechas del calendario deben mantenerse desde ADMIN.

Resultado: 964 comprobaciones PHP (219 negocio, 216 MVC, 347 retiro/API, 162 enrutamiento y 20 configuración), catorce E2E y once UI aprobadas. Evidencia E2E final en `scratch/phase8/b78674db1bed`, sin entradas en el error_log aislado; las once UI se aprobaron con `scratch/phase8/32cf65e7fe5f` y la misma versión de vistas/editor. Sintaxis PHP/JS y git diff --check correctos. No se hizo commit, push ni despliegue.
