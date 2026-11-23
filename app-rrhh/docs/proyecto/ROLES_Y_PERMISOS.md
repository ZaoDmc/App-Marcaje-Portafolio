# Roles y permisos

Estado: reglas vigentes del proyecto. La interfaz orienta; el servidor decide y revalida cada operación.

## Matriz de responsabilidades

| Acción | ADMIN | SUPERVISOR | PRACTICANTE |
| --- | --- | --- | --- |
| Gestión de usuarios, roles y catálogos globales | Sí | Sin administración global | No |
| Crear o gestionar fichas y horarios en su alcance | Sí | Sí, sin autosupervisión | Solo datos propios permitidos y cierre inicial de ficha |
| Registrar marcajes | Desde flujos de corrección autorizados | Desde flujos de corrección autorizados | Sus propios marcajes |
| Corregir y revisar asistencia | Sí | Sí, excluida la propia | No |
| Anular jornada completa | Sí | No | No |
| Anular marca individual en una corrección auditada | Sí | Cuando el flujo lo permite | No |
| Generar ausencias esperadas | Sí | No | No |
| Resolver permisos, recuperaciones y tiempo excepcional | Sí | Sí, excluidos los propios | Solicitar y consultar los propios |
| Cambiar colación solo para una fecha | Aprobar/rechazar con motivo y recálculo | Aprobar/rechazar, excluida la propia | Solicitar para hoy 13–14 / 14–15 y consultar su historial; no modifica horario base |
| Canjear crédito excepcional aprobado de política 2 | Consulta; revisión si cambió evidencia | Consulta; revisión si cambió evidencia | Confirma su propio canje sin segunda aprobación, dentro de saldo, plazo y horario |
| Mantener áreas y feriados comunes en Catálogos | Sí | No | No |
| Asignar pertenencia a un área | Sí | Sí, excluida la propia | No |
| Designar o retirar líderes | Sí | No | No |
| Configurar rúbricas | Sí | No | No |
| Registrar y publicar notas nuevas | Líderes; resolución excepcional prevista | El rol por sí solo no habilita | Como líder vigente, integrantes autorizados de su área |
| Corregir notas existentes | Global, con motivo e historial | No por el solo rol | Líder según autoría y reglas de edición; no hereda notas anteriores |
| Consultar notas aprobadas | Gestión y reportes | Dentro del alcance de supervisión | Solo las propias; evaluación de equipo según liderazgo |
| Cerrar práctica | ADMIN según validación del flujo | No | No |
| Generar formularios de validación y mantener datos del centro/funciones por carrera | Sí | Sí | No |

## Identidad y múltiples roles

ADMIN externo tiene `usuarios_sistema.id_practicante` nulo. No se permite PRACTICANTE + ADMIN. Sí se permite PRACTICANTE + SUPERVISOR, conservando su obligación de marcar como practicante.

El rol activo se selecciona después de autenticar y condiciona acciones, menú y auditoría. Tener varios roles no combina todas sus facultades en cualquier ruta. El rol SUPERVISOR no convierte a alguien en evaluador académico.

## Conflictos de interés

No se permite corregir asistencia propia, resolver observaciones propias, aprobar permisos o tiempo propios, cambiar su propia área desde supervisión ni autoevaluarse. Cambiar un ID en la URL o esconder un botón no sustituye esta validación.

## Liderazgo y notas

El liderazgo exige designación y pertenencia vigentes al área, además de las condiciones de habilitación. La consulta de notas propias permanece aunque se retire el liderazgo. Para autoría, reemplazo, publicación y excepciones de ADMIN, leer [Notas](../modulos/NOTAS.md) y [Áreas](../modulos/AREAS_Y_LIDERAZGO.md).

## Código de referencia

`src/Services/AuthService.php`, `src/Models/UsuarioModel.php`, controladores por rol, `src/Services/LiderazgoService.php`, `src/Services/NotasService.php` y `src/Services/PublicacionNotasService.php`. La matriz resume el alcance; no permite omitir los controles particulares del servicio.
