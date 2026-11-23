# Parches SQL: fechas y orden de aplicación

Actualizado: 29 de octubre de 2026. Se renombraron los nueve parches anteriores sin modificar su contenido SQL; Notas incorpora 010 y 011. Las referencias apuntan a sus nombres vigentes.

## Convención

`AAAA-MM-DD_patch_nombre_XXX.sql`

- La fecha corresponde al registro de implementación documentado cuando existe.
- Si no existe fecha funcional explícita, se utiliza la última modificación conocida del archivo en la línea de historia de Git actual. Es una referencia verificable, no una fecha de creación inventada.
- No se usan las fechas de modificación del explorador: pueden cambiar al copiar o descargar el proyecto.
- `XXX` es una secuencia global de tres dígitos. El orden lo determina ese número y las dependencias, no el orden alfabético del nombre. Continuar desde `012` en futuras entregas y registrar sus requisitos.

## Inventario y procedencia

| Orden | Archivo | Fecha y evidencia | Requisitos |
| --- | --- | --- | --- |
| 001 | [2026-06-03_patch_marcas_asistencia_indices_001.sql](2026-06-03_patch_marcas_asistencia_indices_001.sql) | Documentada en PROGRESO, sección 2026-06-03, fase 2.3 de índices. Git lo incorporó posteriormente el 2026-06-22. | Base del proyecto con marcas_asistencia. |
| 002 | [2026-06-11_patch_documentos_practicante_002.sql](2026-06-11_patch_documentos_practicante_002.sql) | Documentada en PROGRESO, sección 2026-06-11, documentos opcionales. Git lo incorporó posteriormente el 2026-06-22. | practicantes y practicas existentes. |
| 003 | [2026-08-28_patch_nacionalidades_003.sql](2026-08-28_patch_nacionalidades_003.sql) | Sin fecha funcional explícita encontrada; última modificación conocida en la historia actual: commit 815c10a, 2026-08-28. | Base del proyecto; crea y carga el catálogo de nacionalidades. |
| 004 | [2026-10-01_patch_permisos_practicante_004.sql](2026-10-01_patch_permisos_practicante_004.sql) | Sin fecha funcional explícita encontrada; última modificación conocida: commit d8fe5da, 2026-10-01. | Tablas de prácticas, usuarios y excepciones referenciadas por las claves foráneas. Debe preceder a 005. |
| 005 | [2026-10-01_patch_permisos_parciales_005.sql](2026-10-01_patch_permisos_parciales_005.sql) | Sin fecha funcional explícita encontrada; última modificación conocida: commit d8fe5da, 2026-10-01. | permisos_practicante; aplicar después de 004. |
| 006 | [2026-10-14_patch_permisos_mismo_dia_006.sql](2026-10-14_patch_permisos_mismo_dia_006.sql) | PROGRESO documenta la aplicación de fase 4 con respaldo 20261014-175703. | 004/005 y catálogo tipos_marca. |
| 007 | [2026-10-14_patch_recuperaciones_varios_dias_007.sql](2026-10-14_patch_recuperaciones_varios_dias_007.sql) | PROGRESO documenta la aplicación de fase 5 con respaldo 20261014-190224; el commit caf7d36 es del día 15. Se prioriza la fecha documentada de implementación. | 006 y tablas base de permisos, usuarios y jornadas. |
| 008 | [2026-10-15_patch_tiempo_excepcional_008.sql](2026-10-15_patch_tiempo_excepcional_008.sql) | FASE_6_TIEMPO_EXCEPCIONAL documenta aplicación y corte 2026-10-15 15:06:45 UTC. | 007 y esquema operativo anterior. Conserva el corte histórico de una instalación existente. |
| 009 | [2026-10-19_patch_liderazgos_area_009.sql](2026-10-19_patch_liderazgos_area_009.sql) | FASE_1_NOTAS_AREAS_Y_LIDERES, entrega y aplicación local del 2026-10-19. | areas_practica, practicas y usuarios_sistema; independiente del cálculo de horas. |
| 010 | [2026-10-19_patch_notas_semanales_010.sql](2026-10-19_patch_notas_semanales_010.sql) | Entrega de fase 2 de Notas, 2026-10-19; véase [documentación](../docs/historial/notas/FASE_2_NOTAS_BASE_Y_PERMISOS.md). | 009, practicas, areas_practica y usuarios_sistema. Crea cuatro tablas y la rúbrica provisional; conserva evaluaciones anteriores. |
| 011 | [2026-10-19_patch_rubricas_configurables_011.sql](2026-10-19_patch_rubricas_configurables_011.sql) | Entrega de fase 3, 2026-10-19; [documentación](../docs/historial/notas/FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md). Aplicado con 010 en la base local el mismo día, con respaldo y checksums. | 010. Configuración de rúbrica vigente y máximo técnico de 50 filas. Sintaxis MariaDB 10.4/11.8, no certificada para MySQL. |
| 012 | [2026-10-29_patch_formulario_validacion_012.sql](2026-10-29_patch_formulario_validacion_012.sql) | Funcionalidad nueva del módulo Formulario validación, registrada el 2026-10-29. | Esquema base con `usuarios_sistema`, `carreras` y catálogos existentes. Añade configuración global del centro, textos sugeridos por carrera y metadatos de DOCX generados. No almacena los DOCX dentro de la base. |
| 013 | [2026-11-04_patch_resenas_practica_013.sql](2026-11-04_patch_resenas_practica_013.sql) | Módulo de Recepción y Gestión de Reseñas, registrado el 2026-11-04. | Tablas `practicas`, `practicantes` y `acompanamiento`. Crea tabla `resenas_practica` para testimonios, evaluación y custodia de autorización legal. |

Fuentes: [PROGRESO](../docs/historial/PROGRESO.md), [tiempo excepcional](../docs/historial/operativa/FASE_6_TIEMPO_EXCEPCIONAL.md), [liderazgo](../docs/historial/notas/FASE_1_NOTAS_AREAS_Y_LIDERES.md) e historial Git de cada archivo anterior al renombrado. Las fechas de este inventario no certifican cuándo se ejecutó cada parche en Hostinger.

## Uso

### Entrega 2.1.0: cambio diario de colación

**016:** [2026-11-06_patch_cambio_colacion_016.sql](2026-11-06_patch_cambio_colacion_016.sql), registrado el 06/11/2026. Requiere prácticas, usuarios y esquema operativo anterior. Crea solicitudes independientes con fecha, firma de horario, estado y revisiones auditadas; no cambia horarios ni datos anteriores. Respaldar y aplicar antes del código: la consulta de horario efectivo incorpora la nueva tabla. Incluido en schema para instalación nueva. Probado en MariaDB ficticia (repetición con datos y FK RESTRICT); no aplicado a base habitual ni producción.

### Entrega 2.0.0: recuperación exclusiva de sábados

**015:** [2026-11-06_patch_recuperaciones_sabados_015.sql](2026-11-06_patch_recuperaciones_sabados_015.sql), registrado el 06/11/2026. Requiere 007 y esquema de recuperaciones. Añade `politica_recuperacion` y `solicitado_utc` por tramo. Los tramos existentes quedan en política 1 con fecha UTC nula; los nuevos guardan política 2 y fecha original de envío para el corte del viernes. No altera créditos, fechas ni aprobaciones anteriores. Respaldar y aplicar antes del código. Prueba MariaDB ficticia: parche repetido, conservación de datos y coincidencia de campos con schema; sin aplicación a base habitual o producción.

### Entrega 1.6.0: política de canje autónomo

**014:** [2026-11-06_patch_canje_autonomo_014.sql](2026-11-06_patch_canje_autonomo_014.sql), registrado el 06/11/2026. Requiere 008 y tablas operativas existentes. Añade `politica_canje` con valor 1 para conservar expedientes anteriores; las solicitudes creadas por 1.6.0 guardan 2. No cambia vencimientos ni autoriza canjes existentes. Respaldar y aplicar antes de publicar el código. Probado dos veces sobre base MariaDB ficticia, con conservación de datos y comparación con schema nuevo. No aplicado a producción.

Notas, validación de fase 6: no agrega un parche 012. La guía [FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md](../docs/historial/notas/FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md) explica respaldo, aplicación de 009–011 según el estado real y reversión. `php scripts/check-notas.php --check` diagnostica estructuras/configuración/publicaciones mediante lecturas; no instala ni repara la base. Su revisión técnica no confirma el contenido académico de la rúbrica.

Para una instalación nueva, utilizar `schema.sql` y los seeds correspondientes. Los parches están destinados a actualizar bases existentes según su estado y requisitos; no son un reemplazo del esquema base.

Renombrar no exige volver a ejecutar los parches ya aplicados. Este cambio no ejecuta SQL ni modifica bases de datos. Antes de una publicación, revisar qué migraciones faltan en esa base, respaldar y seguir las guías de su entrega.

Al actualizar nombres se conservaron los nueve archivos byte por byte, verificados mediante SHA-256 antes y después. Si una guía menciona una implementación histórica, su ruta apunta ahora al archivo vigente; esa actualización de referencia no cambia la fecha ni el hecho histórico descrito.
