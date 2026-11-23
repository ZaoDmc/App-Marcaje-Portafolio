# Mantenimiento de documentación

Aplican las [instrucciones del repositorio](../AGENTS.md).

- El punto de entrada es [README.md](README.md). Las reglas vigentes se mantienen en documentos cortos por tema, no como actualizaciones contradictorias dentro de un archivo gigante.
- Usar enlaces relativos que funcionen desde la ubicación real del Markdown. Los paths de código entre backticks se interpretan desde la raíz del repositorio.
- Distinguir regla acordada, comportamiento comprobado, propuesta y pendiente. No convertir sugerencias o resultados locales en hechos productivos.
- Conservar el formato humano `Ajuste | Valor` en branding y `Elemento | Texto deseado` en textos. Aclarar que son especificaciones que requieren implementación.
- Antes de mover un archivo, actualizar referencias del README, otros documentos y pruebas que lean inventarios. No dejar archivos puente con copias de reglas.
- Conservar la historia y atribuciones. `historial/` no debe usarse como fuente vigente cuando existe una decisión posterior en el documento del módulo.
- Una nueva funcionalidad actualiza su módulo, el estado y, si cambia la estructura, este índice. No es necesario leer toda la bitácora para una edición puntual.
