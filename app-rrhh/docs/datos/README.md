# Datos y migraciones

## Fuentes vigentes

El esquema ejecutable está en [database/schema.sql](../../database/schema.sql), los catálogos iniciales en [seed_catalogos.sql](../../database/seed_catalogos.sql) y el orden de parches en [database/README.md](../../database/README.md). La descripción SQL anterior se conserva como [referencia histórica](../historial/referencias/DOCUMENTO_BBDD.md); no usarla como instalador actual.

Instalación nueva: importar schema y los seeds que correspondan según las instrucciones de raíz; no volver a aplicar todos los parches por costumbre. `schema.sql` reúne las tablas necesarias, pero no crea secretos, usuarios operativos reales, uploads ni datos académicos definitivos.

Instalación existente: identificar esquema y migraciones realmente aplicadas, respaldar y usar los parches faltantes en orden de dependencia. No importar schema completo encima de producción como actualización. El inventario de fechas no acredita cuándo se ejecutó cada parche en Hostinger.

## Relaciones por dominio

| Dominio | Tablas principales |
| --- | --- |
| Identidad | `usuarios_sistema`, `usuarios_roles`, `roles_sistema`, `sesiones_usuario`, `tokens_usuario` |
| Persona y práctica | `practicantes`, `practicas`, `documentos_practicante`, catálogos institucionales y geográficos |
| Horario | `horarios_practicante`, `detalle_horario_practicante`, `excepciones_horario_practicante`, `feriados` |
| Asistencia | `jornadas_asistencia`, `marcas_asistencia`, `tipos_marca` |
| Áreas | `areas_practica`, `historial_areas_practicante`, `liderazgos_area`, `bloques_area_jornada` |
| Ausencia y recuperación | `permisos_practicante`, `recuperaciones_permiso` |
| Excepcional e históricos | `politica_tiempo_excepcional`, `extras_historicas`, `tiempos_excepcionales`, `compensaciones_tiempo` |
| Evaluación semanal | `rubricas_notas`, `configuracion_notas`, `notas_semanales`, `notas_semanales_detalle`, `notas_semanales_historial` |
| Seguimiento previo | `acompanamiento`, `evaluaciones_practicante` |
| Trazabilidad | `auditoria_cambios` más historiales del módulo |

Un ID de usuario no equivale a un ID de persona o práctica. Conservar claves foráneas, cortes históricos, índices y revisiones de concurrencia; no simplificar el modelo para resolver un fallo de formulario.

## Nuevo cambio de esquema

Crear un parche `AAAA-MM-DD_patch_nombre_XXX.sql` siguiendo el inventario, documentar dependencias y compatibilidad, y actualizar el schema para instalaciones nuevas. Las fechas se justifican con evidencia; no usar la fecha de copia del explorador. No renumerar parches ya publicados. El siguiente número libre se confirma contra `database/README.md` antes de crear uno.

Probar creación limpia y actualización de datos existentes en bases ficticias. MariaDB 10.4 y 11.8 no acreditan sintaxis idéntica de MySQL; comprobar el motor del entorno. No registrar credenciales o dumps reales en Git.
