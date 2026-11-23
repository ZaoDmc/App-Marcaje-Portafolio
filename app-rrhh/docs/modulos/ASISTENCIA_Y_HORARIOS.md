# Asistencia y horarios

## Marcajes y jornada

La asistencia se basa en `jornadas_asistencia`, `marcas_asistencia` y `tipos_marca`, no en una tabla genérica `asistencias`. El practicante marca desde su identidad autenticada. Las cuatro marcas ordinarias son ENTRADA, ENTRADA_COLACION, SALIDA_COLACION y SALIDA; sus etiquetas de pantalla deben aclarar el sentido de cada acción.

Se pide confirmación antes de registrar y se evita el doble envío. La ventana nativa pertenece al navegador; la alternativa del servidor conserva la confirmación cuando no hay JavaScript. Las interrupciones de permisos usan SALIDA_PERMISO y REGRESO_PERMISO, según el [módulo de permisos](PERMISOS_Y_RECUPERACIONES.md).

Desde 1.4.0, cada marca registrada por el practicante envía un comprobante a su correo (`practicantes.email`) con tipo, fecha, hora guardada, y folio `M-` + `id_marca`. El envío ocurre después del commit y nunca revierte la marca; un fallo queda en el log del servidor. Las correcciones de supervisor no envían comprobante. Configuración en [Correo](../operacion/CORREO_SMTP.md).

El horario vigente es individual por día y fecha. `horarios_practicante`, su detalle y excepciones conservan vigencias; no sobrescribir el pasado. Las excepciones activas se resuelven para el día correspondiente y el calendario común excluye feriados de la jornada ordinaria.

## Tolerancia y reconocimiento

| Ajuste de negocio vigente | Valor |
| --- | --- |
| Tolerancia por extremo | Hasta 30 minutos, evaluados por separado |
| Adelanto de entrada más salida posterior | No se suman para decidir la tolerancia |
| Tope ordinario de jornadas con política nueva | Horas netas pactadas; no generar nuevas extras |
| Atraso o salida anticipada tolerados | Cambian la incidencia, no inventan minutos trabajados |

Ejemplo con turno 10:00–17:00 y colación de una hora correctamente marcada: 09:55–17:20 reconoce seis horas ordinarias y tolera los extremos; 09:55–17:50 queda observado. Veintinueve minutos antes y veintinueve después no generan saldo. Más de treinta minutos en un extremo produce la observación correspondiente. El servicio compara el límite en segundos.

Sin colación registrada no se descuenta una pausa ficticia: se deja observación y se calcula el trabajo acreditado. Para la política nueva sigue existiendo el tope neto pactado; reconocer una colación para recuperar un permiso exige su flujo específico, no basta omitir las marcas. Esta regla posterior evita interpretar el texto histórico como autorización para sumar trabajo ilimitado sin colación.

## Estados y revisión

Estados actuales: VALIDA, OBSERVADA, INCOMPLETA, ANULADA, CORREGIDA, REVISADA y PENDIENTE_REVISION. La elegibilidad para sumar se resuelve en `ReglasAsistenciaService.php`; OBSERVADA puede aportar trabajo válido, mientras una jornada incompleta o anulada no aporta como jornada terminada. REVISADA cierra una revisión sin exigir alterar marcas; CORREGIDA identifica la corrección. Ambas conservan auditoría y no autorizan tiempo excepcional por sí mismas.

No atribuir horas a una salida futura ni fabricar una salida para cerrar una jornada. Cambiar o anular marcas puede invalidar aprobaciones y recalcular recuperaciones/canjes relacionados. Las marcas se anulan lógicamente; anular una jornada completa corresponde a ADMIN.

## Avance acumulado

El avance debe conservar las horas previas de la práctica y los importes históricos reconocidos junto al trabajo computable, sin duplicar recuperación o compensación ya incluida en una jornada. La respuesta tras un POST de marcaje y la recarga deben mostrar el mismo criterio de acumulado. Las horas del periodo del reporte son otra métrica, no reemplazan el total histórico.

Sábados nuevos requieren validación; la política histórica anterior al corte se conserva. Generar ausencias esperadas queda reservado a ADMIN y respeta horario, fechas anteriores y feriados; no es la herramienta para mantener el calendario común.

## Código

`src/Controllers/Practicante/MarcarAsistenciaController.php`, `src/Controllers/Shared/AsistenciaController.php`, `src/Services/AsistenciaService.php`, `MailService.php` (comprobante), `ReglasAsistenciaService.php`, `HorariosPracticanteService.php`, `ExcepcionesHorarioService.php` y `AusenciasEsperadasService.php` dentro de `src/Services/`.

Procedencia: [correcciones operativas](../historial/operativa/FASE_3_CORRECCIONES.md), [tolerancia y corte](../historial/operativa/FASE_6_TIEMPO_EXCEPCIONAL.md). No reutilizar las reglas antiguas de extras o atraso de diez minutos del contexto histórico.
