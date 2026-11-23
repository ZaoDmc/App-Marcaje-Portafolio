# Tiempo excepcional y compensación

## Distinción fundamental

Una recuperación paga tiempo ausente de un permiso. El tiempo excepcional corresponde a trabajo adicional por una necesidad extraordinaria autorizada informalmente por el jefe. El supervisor registra en software la decisión y su motivo. No se crea un rol Jefe ni se transforma este crédito en nuevas horas extra acumuladas.

## Flujo vigente

1. El practicante solicita reconocer un tramo terminado y acreditado por marcas, fuera de su turno y dentro de la ventana admitida de 10:00–20:00. No reutiliza recuperación, otra solicitud ni trabajo futuro.
2. SUPERVISOR/ADMIN revisa y aprueba o rechaza con motivo. Un supervisor no resuelve su propio expediente.
3. Desde 1.6.0, un crédito nuevo aprobado permite al practicante confirmar entrada posterior o salida anticipada sin segunda aprobación, antes de comenzar el tramo. El supervisor aprueba únicamente el reconocimiento y documenta el motivo de la autorización del jefe.
4. Reservas pendientes y aprobadas reducen disponibilidad para evitar doble gasto. La jornada compensada reconoce solo la parte autorizada no trabajada, cuando sus marcas permiten acreditarlo; no duplica trabajo real.

## Parámetros y límites

| Regla | Valor vigente |
| --- | --- |
| Duración del crédito nuevo (política 2) | Hasta finalizar el turno del quinto día de jornada posterior al día de aprobación, según horario individual y excluyendo feriados |
| Créditos existentes (política 1) | Conservan 168 horas desde solicitud y aprobación del canje; no se convierten automáticamente |
| Reinicio al revisar evidencia | No; la primera aprobación fija el vencimiento nuevo |
| Tramo compensado | Debe terminar como máximo al vencer y autorizarse antes de empezar |
| Historial tras vencer | Se conserva; el saldo vencido deja de ser utilizable |
| Compensaciones activas | Una por día, según validaciones del servicio |

La compensación no utiliza colación, feriado, jornada completa ni días con permiso pendiente/aprobado. El saldo no utilizado puede volver a estar disponible únicamente mientras siga vigente. No renovar un trabajo vencido volviéndolo a solicitar. No se necesita cron para impedir su uso: el vencimiento se valida en cada operación.

La solicitud nueva pendiente no consume plazo: admite aprobación tardía. El primer reconocimiento fija `vence_utc`; una reapertura por corrección conserva ese vencimiento. El cálculo busca hasta 366 fechas y rechaza aprobar si no encuentra cinco jornadas dentro de la práctica; debe revisarse horario/vigencia en ese caso. El horario debe seguir siendo válido en el día del canje: el vencimiento almacenado no autoriza feriados ni un turno posteriormente retirado.

La columna `politica_canje` distingue los expedientes. Aplicar el parche **014 antes de publicar código 1.6.0**. Conserva todos los registros existentes en política 1; `tiempo_solicitar` asigna 2 a solicitudes nuevas. El campo `vence_utc` de una solicitud nueva no aprobada es un valor interno de compatibilidad, no un plazo activo, y no se muestra como vencimiento al practicante. No importar schema sobre una instalación existente.

## Marcas, correcciones e históricos

Modificar la fuente reabre crédito y canjes relacionados y recalcula jornadas con auditoría. La revalidación de expedientes históricos con autorización anterior conserva el vencimiento; no habilita autorizaciones retrospectivas nuevas.

`politica_tiempo_excepcional` conserva el corte de activación y la última jornada histórica. `extras_historicas` mantiene importes anteriores sin convertirlos en crédito. Repetir la migración no desplaza el corte; no copiar el corte local a producción.

Una jornada REVISADA o CORREGIDA no aprueba automáticamente tiempo excepcional. La tolerancia de [Asistencia](ASISTENCIA_Y_HORARIOS.md) afecta incidencias, no genera un crédito automático.

Las marcas correctivas del supervisor también sirven como evidencia: el practicante puede seleccionar la jornada corregida y solicitar el tramo adicional ya terminado. Siguen aplicando el corte histórico, la secuencia coherente, la titularidad y la aprobación excepcional independiente. Una corrección con salida futura no acredita trabajo que todavía no ocurrió.

Para un canje confirmado de salida anticipada, se utiliza **Fin de jornada** al salir; no existe una marca especial de compensación. Ejemplo: turno efectivo del viernes 10:00–17:00, canje confirmado 16:00–17:00 y salida a las 16:00. El cálculo reconoce el trabajo y la parte autorizada no trabajada, sin duplicarlos ni superar la jornada pactada. En política 2 la confirmación del practicante habilita el tramo inmediatamente; en política 1 se conserva aprobación del supervisor. Las correcciones que invalidan evidencia pueden reabrir el expediente para revisión de seguridad, sin renovar plazo.

El destino se valida contra el horario **efectivo de esa fecha**, incluidas excepciones y feriados; no contra el horario del día en que se generó el crédito. Los mensajes distinguen día sin jornada, extremos fuera del turno y colación. Un turno habitual 10:00–17:00 con excepción hasta las 18:00 exige que el canje de salida termine a las 18:00. No modificar el horario para ocultar una discrepancia: revisar la configuración y la excepción correspondientes.

## Código y procedencia

Desde 1.7.0, ADMIN y SUPERVISOR consultan el módulo desde categorías de la misma bandeja de Permisos: **Ausencias y recuperación**, **Tiempo excepcional** y **Compensación**. Las dos últimas tienen búsqueda por nombre/correo/#práctica, rango de fechas, estado y paginación de 5/10/25/50. Reconocimiento filtra por día trabajado; Compensación por día del canje. PENDIENTE incluye REVISAR y APROBADO incluye APROBADA/APLICADA. Los contadores de categoría muestran el total autorizado, no el total filtrado.

Cada fila abre un diálogo con su origen y detalle; en Compensación solo se muestra el canje seleccionado. Los canjes autónomos confirmados no piden nueva aprobación. Los históricos o reabiertos mantienen las acciones de revisión del servicio. La categoría no altera los controles del servidor ni permite consultar expedientes propios desde supervisión. Las listas se preparan desde `tiempo_listar` autorizado; la paginación de tiempo se realiza en memoria sobre esos resultados, por lo que no es una optimización de consulta SQL para grandes volúmenes.

`src/Services/TiempoExcepcionalService.php`, `src/Services/ReglasAsistenciaService.php`, `src/Services/PermisosService.php`; tablas `tiempos_excepcionales`, `compensaciones_tiempo`, `politica_tiempo_excepcional` y `extras_historicas`.

[Implementación y correcciones](../historial/operativa/FASE_6_TIEMPO_EXCEPCIONAL.md), [validación de integración](../historial/operativa/FASE_7_VALIDACION_OPERATIVA.md). Las propuestas anteriores de compensar en dos días hábiles quedan sustituidas por el plazo vigente de esta guía.
