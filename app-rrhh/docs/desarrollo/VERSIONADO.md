# Versionado del software

Regla acordada el **27 de octubre de 2026**. RRHH comienza su numeración explícita en **1.1.1**, por indicación del responsable. Esta entrega establece ese punto de partida; no se incrementa a 1.1.2 por incorporar el propio indicador.

## Qué número cambiar

El formato es `MAYOR.MENOR.PARCHE`, con tres enteros no negativos. La etiqueta de entrega puede llevar `v`, por ejemplo `v1.1.1`, pero el valor del código se guarda sin prefijo.

| Tipo de cambio | Incremento | Ejemplo desde 1.1.1 |
| --- | --- | --- |
| Mejora visual, corrección de un fallo o ajuste interno compatible sin nueva funcionalidad | PARCHE | 1.1.2 |
| Funcionalidad nueva o ampliación compatible del sistema | MENOR; PARCHE vuelve a cero | 1.2.0 |
| Cambio incompatible que rompe contratos o exige adaptación de integraciones, datos o uso anterior | MAYOR; MENOR y PARCHE vuelven a cero | 2.0.0 |

Una modificación grande que conserva compatibilidad no es MAYOR solo por su tamaño. Una entrega que reúne distintos tipos usa el de mayor impacto. Documentar qué compatibilidad cambia al proponer un MAYOR.

## Fuente de verdad de RRHH

`config/version.php` devuelve la versión del código; `config/app.php` la expone como `APP_VERSION` y el login muestra `Versión {APP_VERSION}`. Para cambiar el número, editar únicamente el valor devuelto por `config/version.php`, y actualizar el historial correspondiente. No duplicarlo como literal en vistas, `.env`, package.json o composer.json.

El número describe la entrega del proyecto, no la versión de PHP, Composer, MariaDB o sus dependencias. El frontend compilado y el backend deben pertenecer a la misma entrega. El generador ZIP ya copia los PHP de `config/`, incluyendo este archivo.

## Instrucciones para personas e IA

1. Leer esta regla, la versión del código, [CHANGELOG](../../CHANGELOG.md) y el [registro de producción](../operacion/PRODUCCION.md) antes de modificar código.
2. Elegir PARCHE, MENOR o MAYOR según el comportamiento final de la tarea. Actualizar el número una vez por entrega o PR coherente, no por archivo ni por cada intento de reparación dentro de esa misma tarea.
3. Respetar una versión inicial o de destino indicada explícitamente por el responsable. No inventar numeración para proyectos sin una base acordada. Los cambios exclusivamente documentales no incrementan el software; sí deben documentarse cuando corresponda.
4. Actualizar `config/version.php` y añadir en CHANGELOG el motivo, categoría, cambios, validación y requisitos de publicación. Conservar entradas anteriores y no reutilizar una versión publicada para código diferente.
5. Trabajando en paralelo desde dev, comprobar la numeración al integrar: resolver colisiones y consolidar el incremento de la entrega; no asumir que dos PR con el mismo número pueden publicarse como dos versiones diferentes.
6. Incluir versión anterior, nueva y categoría en la descripción del PR. Revisar migraciones, recursos compilados y compatibilidad aunque el incremento sea PARCHE.
7. No hacer commit, etiqueta Git, push, merge, release ni despliegue sin la autorización correspondiente. Una etiqueta `vX.Y.Z` es una referencia opcional a la revisión final aprobada, no se crea por escribir el número.
8. Tras un despliegue autorizado y comprobado, registrar versión servida, SHA completo, fecha/hora/zona, operador, validación, respaldo y resultado en PRODUCCION. Un merge a prod o una prueba local no acredita publicación. Conservar el historial de despliegues y reversiones.
9. En el cierre distinguir versión del código de trabajo y última versión productiva comprobada. Si no se pudo consultar producción, indicarlo con su última fecha de evidencia; no presentarlo como estado actual verificado.

No es obligatorio incrementar otra vez por un ajuste necesario para completar la entrega que aún está en revisión. Si la entrega ya fue publicada, una corrección posterior recibe su nuevo número.

## Aplicación a otros proyectos

Esta política se puede adoptar en cada repositorio con su propia versión, fuente de verdad e historial. No asignar 1.1.1 a todos ni sustituir convenciones existentes sin revisarlas. Mantener la regla en el AGENTS del repositorio para otros colaboradores e IA que lo lean.

La preferencia global quedó instalada el 27/10/2026 en `~/.codex/AGENTS.md` del perfil de este equipo, además de las reglas de este repositorio. No modifica por sí sola archivos, versiones ni producción de otros proyectos. Se carga al iniciar trabajos de Codex con ese perfil y puede estar sujeta a instrucciones específicas o overrides de cada proyecto. Otros equipos o herramientas necesitan adoptar sus instrucciones equivalentes. Referencia: [instrucciones globales y por proyecto de Codex](https://learn.chatgpt.com/docs/agent-configuration/agents-md).
