# Colaboración y revisiones

## Flujo del equipo

Desde el acuerdo de transición SSH/Git del 22/10/2026, cada persona trabaja sobre una rama descriptiva creada desde `dev` actualizado. Guardar cambios propios antes de cambiar de rama. Una tarea por PR facilita revisar, probar y resolver conflictos.

El colaborador abre PR hacia `dev`; el responsable revisa y aprueba. Tras probar la integración, una entrega pasa mediante PR aprobado de `dev` hacia `prod`, la rama del checkout de Hostinger. `main` se conserva como referencia durante la transición y no se añade como paso obligatorio antes de dev. La función posterior de main debe acordarse, evitando dos ramas de integración paralelas.

Descargar Git no instala dependencias ni aplica SQL; revisar migraciones y recursos compilados del PR. Las protecciones de dev/prod deben verificarse en GitHub: que existan las ramas o este texto no acredita que la aprobación esté técnicamente exigida.

El responsable solicitó aprobación propia obligatoria antes del merge. Esto es una política del equipo: **este documento no acredita que las protecciones de GitHub estén configuradas**. La configuración efectiva se comprueba en el repositorio y plan de GitHub. Un archivo CODEOWNERS por sí solo no fuerza revisiones si no existe una regla que las exija. No se añadió ni modificó esa configuración en la reorganización documental.

## Acuerdos del proyecto

- Usar ramas sin prefijo `codex`, según preferencia del responsable.
- No hacer commit, push, merge, crear PR en GitHub ni desplegar desde una tarea asistida sin autorización.
- Entregar título y descripción de PR listos para copiar al completar una entrega.
- Comentar en español los cambios de código relevantes y actualizar el módulo documental afectado.
- Evitar trabajar simultáneamente sobre schema, controladores compartidos o estilos globales sin coordinar el alcance.
- Conservar trabajo ajeno y resolver conflictos entendiendo ambos cambios, no eligiendo una versión completa por comodidad.
- Cada persona tiene su base y `.env` locales; no compartir secretos por Git.

## Contenido de un PR

Aplicar la [regla de versionado](VERSIONADO.md): indicar versión anterior, nueva y categoría del cambio; actualizar la fuente de versión y CHANGELOG por entrega de código. La versión preparada en dev y el merge a prod no prueban publicación: el estado observado se registra por separado en [Producción](../operacion/PRODUCCION.md).

Explicar el problema y el comportamiento final, con validación y límites. Si necesita SQL, indicar archivo, orden y entorno; si cambia frontend, incluir compilados pertinentes. No presentar resultados de una fase antigua como comprobaciones nuevas ni incluir dumps, uploads o evidencias personales.

La publicación en Hostinger se coordina desde una revisión aprobada; ver [Despliegue](../operacion/DESPLIEGUE.md). La organización del repositorio y el estado real de sus ramas se comprueban al comenzar cada tarea, no se suponen por el último mensaje.
