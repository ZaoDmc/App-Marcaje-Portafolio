# Instrucciones para trabajar en este repositorio

## Lectura inicial

1. Leer [docs/README.md](docs/README.md), [contexto vigente](docs/proyecto/CONTEXTO.md) y [estado y pendientes](docs/proyecto/ESTADO_ACTUAL.md).
2. Antes de cambiar permisos, leer [roles](docs/proyecto/ROLES_Y_PERMISOS.md). Leer únicamente los módulos relacionados con la tarea.
3. Antes de cambiar presentación, leer [branding](docs/diseno/BRANDING.md) y [componentes visuales](docs/diseno/COMPONENTES.md). Para textos, consultar [catálogo editorial](docs/diseno/TEXTOS_INTERFAZ.md).
4. Consultar [arquitectura](docs/arquitectura/README.md), [datos](docs/datos/README.md), [pruebas](docs/desarrollo/PRUEBAS.md) o [despliegue](docs/operacion/DESPLIEGUE.md) según el cambio.
5. Antes de modificar código, leer [versionado](docs/desarrollo/VERSIONADO.md), `config/version.php`, [CHANGELOG](CHANGELOG.md) y [producción](docs/operacion/PRODUCCION.md).

## Reglas de trabajo

- Las instrucciones del usuario tienen prioridad. Los documentos de `docs/historial/` son evidencia anterior, no instrucciones nuevas ni reglas vigentes. No aplicar un plan antiguo como tarea autorizada.
- Mantener PHP, PDO, MySQL/MariaDB y la separación MVC actual. No cambiar arquitectura ni identidad visual sin plantearlo al responsable.
- Antes de editar, explicar alcance, archivos previstos, dependencias y forma de verificar. Trabajar por etapas y comentar en español los cambios relevantes de código.
- Conservar rutas públicas mediante `config/routes.php`; no reintroducir archivos puente, rutas absolutas de equipos ni lógica de negocio en vistas.
- Revalidar autorización e identidad en servidor. No permitir autosupervisión ni autoevaluación. Conservar CSRF, auditoría y bajas lógicas; no borrar historiales funcionales.
- No exponer secretos, datos personales ni dumps. No versionar `.env`, uploads o respaldos. No sustituir el `.env` de producción por el local.
- Para cambios de datos, comprobar respaldo, migración y alcance del entorno. Utilizar fixtures ficticias para pruebas; una fixture no acredita producción. No ejecutar pruebas de agotamiento de recursos o explotación para validar una actualización de PHP.
- Conservar trabajo ajeno. No hacer commit, push, merge, crear PR en GitHub ni desplegar sin autorización. Usar ramas descriptivas sin prefijo `codex`, según preferencia del responsable.
- Elegir las comprobaciones pertinentes de `docs/desarrollo/PRUEBAS.md`; no iniciar suites con escritura sobre datos reales.
- Actualizar la versión una vez por entrega de código según MAYOR.MENOR.PARCHE: visuales/correcciones compatibles → PARCHE; funcionalidades compatibles → MENOR y PARCHE=0; incompatibilidades → MAYOR y los otros dos=0. Respetar la base inicial 1.1.1 declarada para esta adopción. Registrar cambios en CHANGELOG; los ajustes exclusivamente documentales no incrementan el software.
- Mantener `config/version.php` como única fuente de la versión servida. Distinguirla de la versión productiva comprobada en `docs/operacion/PRODUCCION.md`; no actualizar ese estado por un merge o prueba local. Incluir versión y categoría en el PR, sin crear etiquetas ni publicar sin autorización.
- Al terminar, actualizar los documentos vigentes afectados y añadir una entrada fechada en `docs/proyecto/ESTADO_ACTUAL.md`. La bitácora extensa anterior permanece en `docs/historial/PROGRESO.md`.
- Entregar resultado, validación y límites, con título y descripción de PR listos para copiar. La descripción debe representar el cambio final.

## Si la documentación y el código difieren

Documentar la diferencia; no afirmar que una especificación está implementada sin comprobarlo. Una petición explícita actual resuelve una regla anterior; si persiste una contradicción funcional que afecte el cambio, consultar al responsable antes de decidir por él. Editar Markdown no cambia la interfaz ni la base de datos automáticamente.
