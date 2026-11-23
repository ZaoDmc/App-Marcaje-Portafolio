> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 6 operativa: tiempo excepcional y compensación

## Tolerancia por extremo y tope ordinario (2026-10-15)

Por instrucción posterior del responsable, se admiten hasta **treinta minutos en cada extremo por separado**; no se suma el adelanto de entrada con la salida posterior. Entrar veintinueve minutos antes y salir veintinueve después mantiene la jornada válida, sin anunciar esos cincuenta y ocho minutos, sin extras y sin crear saldo. Superar treinta minutos en cualquiera de los extremos produce observación por el tramo pendiente. El límite se compara en segundos: treinta minutos exactos se toleran; treinta minutos y un segundo ya lo superan.

El mismo margen de treinta minutos se aplica a las incidencias de atraso y salida anticipada. La tolerancia cambia el estado, no inventa horas no trabajadas. El trabajo ordinario y la compensación se limitan a las horas netas planificadas; las recuperaciones expresamente validadas conservan su cálculo separado. La falta de colación sigue observada; aunque su ausencia no inventa una pausa, el reconocimiento ordinario ya no puede superar el tope neto pactado en la política nueva. Esta decisión posterior sustituye el tope anterior que permitía reconocer todo el tiempo dentro de la ventana sin colación.

Ejemplos con turno 10:00–17:00, colación de una hora y marcas de descanso completas:

| Entrada | Salida | Estado | Horas ordinarias reconocidas |
| --- | --- | --- | --- |
| 09:55 | 17:20 | VÁLIDA | 6 |
| 09:55 | 17:50 | OBSERVADA | 6 |
| 09:31 | 17:29 | VÁLIDA | 6 |
| 09:30 | 17:30 | VÁLIDA | 6 |
| 10:30 | 17:00 | VÁLIDA | 5,5 efectivamente trabajadas dentro del turno |

No se alteran las jornadas anteriores al corte ni sus extras históricas. Tampoco se omiten colaciones incompletas, marcas futuras, recuperaciones pendientes o validación obligatoria de sábados. No requiere SQL nuevo ni recálculo masivo de datos habituales.

Validación de esta ampliación: 1076 comprobaciones PHP (328 negocio, 217 MVC, 349 retiro/API, 162 rutas, 20 configuración) y quince E2E aprobadas. Evidencia final `scratch/phase8/5ae407d71dd2`, sin errores PHP. Los doce UI aprobados de la corrección anterior conservan vigencia para las plantillas, que no cambiaron en esta ampliación.

## Corrección posterior al probar en localhost (2026-10-15)

Se reprodujo una jornada nueva con turno 10:00–18:00, salida corregida a 19:30 y estado VALIDA incorrecto. Al desactivar la bandera antigua de extras, el ajuste eliminaba la observación de tiempo fuera del turno. Ahora los minutos fuera de horario quedan observados hasta su autorización excepcional, sin aumentar las extras ni el cálculo ordinario. Se excluyen intervalos ya reservados como recuperación o cubiertos por aprobación excepcional vigente. Una aprobación parcial conserva la incidencia por el resto; aprobar/rechazar recalcula la fuente dentro de la misma transacción.

La solicitud rechazada del caso correspondía a una salida de hoy que aún estaba en el futuro según el servidor. Se confirmó que no entraba al flujo de permiso normal. Separé los mensajes de jornada histórica, anulada, horas inválidas, ventana empresarial y salida/tramo futuro. El formulario conserva jornada, horas y motivo tras el rechazo y explica que una corrección futura no acredita trabajo todavía no ocurrido.

Se recalculó únicamente la jornada local 192 del caso reportado: quedó OBSERVADA, con sus marcas intactas, respaldo previo en `scratch/fase6-tiempo/jornada-192-antes-*.json` y evento técnico CORREGIR_ASISTENCIA en auditoría. No se generó solicitud, aprobación ni crédito ficticio. No requiere otro parche SQL ni se modificó producción.

Verificación de esta corrección: 1025 comprobaciones PHP (277 negocio, 217 MVC, 349 retiro/API, 162 rutas y 20 configuración), quince E2E y doce UI aprobadas. E2E `scratch/phase8/180a7478eef3` reproduce el rechazo futuro sin solicitudes guardadas ni interferencia con permisos normales, conservación de campos, solicitud terminada válida, aprobación y reserva. Las pruebas de negocio también cubren los sesenta minutos pendientes tras aprobar treinta de un excedente de noventa.

Rama `tiempo-excepcional-y-compensacion`, desde main `b0dab99`. Esta numeración corresponde al plan operativo posterior a MVC/Tailwind.

## Reglas implementadas

- En Permisos, el practicante solicita reconocer un tramo terminado, fuera del turno vigente, dentro de 10:00–20:00. Las marcas deben formar una secuencia completa; se reconocen minutos completos efectivamente trabajados. El tramo no puede pertenecer a recuperación de un permiso ni a otra solicitud excepcional. Colaciones y feriados siguen el flujo de recuperación de fase 5.
- El permiso del jefe sigue siendo informal. SUPERVISOR/ADMIN registra manualmente el motivo al aprobar o rechazar; no se agrega rol de jefe. El supervisor con práctica propia no puede aprobarse.
- El crédito aprobado permanece separado de horas extra y del avance acumulado. Se utiliza solicitando una entrada posterior o salida anticipada en el horario individual, sin incluir colación, feriado, jornada completa ni un día con permiso pendiente/aprobado. Se admite una compensación activa por día. El uso requiere nueva aprobación del supervisor antes de empezar el tramo.
- Las reservas pendientes y aprobadas descuentan del saldo disponible para evitar doble gasto. Rechazar libera la reserva. Cuando existen marcas completas en el día compensado, se reconoce únicamente la parte autorizada no trabajada; si se trabaja todo o parte del tramo no se duplica su crédito. La parte no utilizada vuelve al saldo mientras no venza. La ausencia de entrada/salida no permite reconocer una jornada completa ficticia.
- Siete días significa **168 horas exactas desde la solicitud de reconocimiento excepcional**, con fecha/hora UTC persistida y presentación en America/Santiago. Aprobar o volver a revisar no reinicia el plazo. El tramo compensado debe terminar como máximo al vencer y solicitarse/aprobarse antes de comenzar. No se necesita cron para bloquear saldo vencido: se comprueba en cada operación. El historial permanece, incluso cuando el saldo es cero; volver a solicitar el mismo trabajo vencido no renueva el plazo.
- Una corrección/anulación de marcas fuente retira el saldo y reabre canjes dependientes con auditoría; se recalculan sus jornadas. Si horario/calendario o evidencia dejan de ser compatibles, el servicio impide el nuevo canje y requiere revisión. La verificación general de informes, notificaciones y cambios masivos de calendario corresponde también a fase 7.

## Corte e históricos

`politica_tiempo_excepcional` guarda una única fecha UTC de activación y el máximo ID de jornada existente al ejecutar la migración. Las jornadas ya existentes conservan su cálculo histórico; las creadas después usan la política nueva. Una jornada que ya existía al activar no se convierte en fuente canjeable, aunque se corrija después.

`extras_historicas` conserva los importes aprobados antes del corte. Se mantienen sus acumulados y no se convierten en saldo de compensación. El endpoint y formulario antiguos de aprobar extras quedan cerrados al activar la política; no se permite aprobar extras pendientes con la regla anterior. Las nuevas jornadas calculan trabajo ordinario y recuperación validada por separado del crédito excepcional.

La migración no borra marcas, permisos ni jornadas, no cambia .env y no modifica imports de horas previas. Repetirla no desplaza el corte. La anulación lógica conserva el historial, pero una jornada anulada sigue sin aportar al avance.

## Publicación coordinada

1. Respaldar la base y los archivos persistentes. Detener temporalmente nuevas operaciones durante el cambio para que la versión anterior no apruebe extras mientras se fija el corte.
2. Confirmar motor/versión de la base del hosting. El SQL está probado con MariaDB 10.4.32; PHP 8.3.35 no identifica el motor de base. Se requiere que los parches de permisos y fase 5 ya estén aplicados.
3. Ejecutar `database/2026-10-15_patch_tiempo_excepcional_008.sql` en la base completa, no pegándolo dentro de la estructura de una sola tabla. Crea política, snapshot histórico, tiempos y compensaciones con claves foráneas. No ejecutar variantes destructivas para resolver incompatibilidades.
4. Actualizar backend y frontend coordinadamente, incluyendo Tailwind recompilado. Conservar .env, .htaccess y uploads propios del hosting según la guía de despliegue.
5. Verificar el corte, importes históricos, solicitud excepcional, motivo de aprobación, reserva, autorización de canje y marcajes posteriores. La fecha de vencimiento se presenta con zona horaria.

En local se aplicó y repitió el parche con respaldo `scratch/fase6-tiempo/antes-fase6.sql`; resultado en `resultado.json`. Corte: jornada 191, UTC 2026-10-15 15:06:45. Se conservaron las dos jornadas con extras aprobadas existentes. No se ejecutó ningún cambio en Hostinger.

## Validación y límites

Las pruebas de negocio ejercitan solicitud/aprobación, identidad, minutos reales, reservas, vencimiento, rechazo, saldo parcial, canje aplicado, conservación histórica, rollback de auditoría y corrección de fuente. SQLite no simula bloqueos concurrentes de InnoDB. Los formularios se prueban adicionalmente con Edge y MariaDB local sobre copias/bases rrhh_e2e_*; no sobre los datos habituales.

La fase 7 queda dedicada a integración final de informes/saldos/notificaciones, comprobación de migraciones y despliegue coordinado. No se afirma haber probado Hostinger ni concurrencia de varios dispositivos. El corte local no se reutiliza en producción: el SQL lo calcula allí una sola vez.

Resultado final: 1015 comprobaciones PHP (267 negocio, 217 MVC, 349 retiro/API, 162 rutas y 20 configuración), quince E2E y doce UI aprobadas. Evidencia E2E final en `scratch/phase8/6a4760cfec74`; capturas del componente en `scratch/phase6/screenshots/tiempo-excepcional-320.png` y `tiempo-excepcional-1280.png`. Se comprobó también la carga de validadores desde Asistencia sin abrir previamente Permisos. No hay errores PHP en el log aislado final.

La reapertura de esta entrega fue conservadora: las compensaciones cuya fuente pierde validez quedan en REVISAR y dejan de aportar. La integración posterior de fase 7 permite revalidar expresamente canjes históricos antes autorizados, conservando autor/fecha de la autorización original y registrando la nueva revisión en auditoría. No autoriza solicitudes retrospectivas ni renueva crédito vencido. Véase FASE_7_VALIDACION_OPERATIVA.md para la regla y las pruebas vigentes.
