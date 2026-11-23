> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Acceso a Notas para practicantes y líderes

Fecha: 2026-10-20. Rama `notas-acceso-practicantes-y-lideres`, creada desde `main` en `3270d66`. Cambios locales; sin commit, push ni despliegue.

## Comportamiento

Todos los practicantes tienen una entrada **Notas** en el menú y un acceso desde el dashboard. El módulo abre **Mis notas**, donde se consultan las evaluaciones propias aprobadas. Si aún no hay publicaciones, el acceso permanece visible y muestra el estado vacío.

Cuando ADMIN designa al practicante como líder vigente, el módulo muestra además **Evaluar mi área**. También aparece esa acción en el dashboard. Se habilita al recargar la página, sin volver a iniciar sesión ni conceder un rol global distinto.

El servidor ya implementaba la autorización de notas y la consulta propia. Antes había dos enlaces diferentes: Mis notas para todos y Notas solo para líderes. Esta entrega unifica la navegación y mejora el descubrimiento del módulo; no crea una segunda lógica de permisos.

## Cómo habilitar la evaluación

1. Desde la gestión de practicantes, asegurar que la práctica del futuro líder pertenece al área correspondiente.
2. Como ADMIN, entrar a **Notas → Áreas y líderes**, seleccionar el área y designar a ese practicante con el motivo requerido.
3. En la cuenta del practicante, utilizar el contexto **PRACTICANTE**. Si también posee el rol SUPERVISOR, ese rol por sí solo no habilita calificar.
4. Recargar Dashboard o Notas. Acceder a **Evaluar mi área**, seleccionar integrante y viernes y seguir el flujo existente de borrador, revisión y confirmación.

El liderazgo debe estar vigente, con cuenta/persona/práctica habilitadas, área activa y pertenencia actual al área. Una designación futura no habilita la evaluación antes de su inicio.

## Buscar y designar líderes

En ADMIN → Notas → Áreas y líderes, el buscador filtra los integrantes elegibles por nombre completo, correo o RUT. Ignora mayúsculas, tildes y signos del RUT; permite combinar palabras. Después de buscar, se selecciona el integrante y se escribe el motivo de designación. Buscar no asigna automáticamente al primer resultado.

El selector contiene exclusivamente pertenencias vigentes al área con práctica habilitada en estado PENDIENTE_INICIO o EN_PROGRESO. Si está vacío, la pantalla explica el requisito y ofrece acceso a Practicantes: abrir la ficha y asignar el Área vigente antes de volver a designar al líder. No se deben mostrar candidatos de otras áreas para ocultar una falta de asignaciones.

El diagnóstico local del 20 de octubre encontró ocho áreas sin pertenencias abiertas y once prácticas activas sin área vigente. Esto explica el selector vacío observado; no se asignaron personas automáticamente ni se modificaron sus datos.

El filtrado funciona en el navegador sobre los candidatos autorizados por el servidor, sin peticiones adicionales. Al perder una coincidencia se limpia la selección y, sin resultados, se bloquea Designar. Sin JavaScript permanece el selector nativo completo. El servidor vuelve a validar pertenencia, estado, CSRF y revisión vigente al guardar. El script `public/assets/js/notas-lideres.js` lleva versión por contenido para renovar caché.

Validación adicional: 33 comprobaciones de liderazgo y 170 de Notas MariaDB; 241 MVC; prueba Edge con 200 candidatos por nombre, correo y RUT, sin coincidencias y sin JavaScript. Recorrido real ADMIN aprobado en `scratch/phase8/e4e843e50c8d`: búsqueda, designación, reemplazo, retiro, rechazo de otro área y aislamiento de roles. Bases y cuentas ficticias. CSS recompilado y sintaxis/diff revisados.

## Límites conservados

### Selector de evaluaciones de ADMIN

El listado habitual de **Evaluaciones** muestra solamente prácticas con liderazgo vigente según el proveedor del módulo. ADMIN califica líderes; los líderes conservan el selector de integrantes de su área. Sin líderes disponibles se indica revisar Áreas y líderes.

Este filtro corresponde a la navegación, no reduce la autorización administrativa acordada: ADMIN conserva la consulta y corrección de notas existentes de cualquier práctica por enlace directo. En ese caso el integrante no se agrega al selector ni se selecciona otro líder por defecto; se identifica la consulta administrativa y se mantienen los viernes de la práctica abierta. Las notas aprobadas de otros practicantes también se consultan en Acompañamiento y Reportes.

Archivos: `src/Controllers/Shared/NotasEvaluacionesController.php` y `src/Views/shared/notas-evaluaciones.view.php`. Sin cambios de permisos de servicios, SQL o datos reales.

- Solo se evalúan integrantes dentro del alcance autorizado del área. No se permite autoevaluación ni se concede acceso a otras áreas manipulando IDs.
- Las notas de los líderes corresponden a ADMIN según las reglas existentes.
- Todos los practicantes, incluidos los líderes, conservan su consulta propia.
- Al retirar o reemplazar al líder desaparece la acción de evaluar, pero permanece el módulo Notas y su historial propio aprobado.
- El nuevo líder no hereda edición de evaluaciones ni borradores del anterior. ADMIN conserva la resolución excepcional prevista.
- Ocultar una acción no es el único control: los controladores y servicios revalidan cada acceso, guardado y publicación. Un no líder que accede directamente a la ruta de evaluación recibe 403.

## Archivos

- `src/Views/layout/sidebar.php`: una entrada Notas para todos, activa en consulta y evaluación.
- `src/Views/components/notas-nav.php`: pestañas Mis notas y Evaluar mi área, según liderazgo preparado por el controlador.
- `public/assets/css/practicante-temas.css`: pestañas adaptadas a la paleta común, sin fondo blanco fijo en temas oscuros.
- `src/Views/practicante/mis-notas.view.php`: incorpora las pestañas compartidas.
- `src/Views/practicante/dashboard.view.php`: acceso común y acción adicional para líderes.
- `src/Controllers/Shared/LayoutContext.php`: conserva la preparación de liderazgo antes del HTML; comentario actualizado al acceso unificado.
- Pruebas de formularios y publicación: navegación común, designación ADMIN desde formulario real y retirada del liderazgo.

No se modifican rutas, roles globales, tablas ni reglas de calificación. No requiere SQL nuevo; siguen siendo necesarias las migraciones 009–011 del módulo. Ante estructuras incompletas debe revisarse su instalación; esta entrega no asigna líderes a cuentas reales ni aplica migraciones automáticamente.

## Validación

- Notas MariaDB: 170 comprobaciones correctas en base temporal, incluidos permisos por área, publicación, identidad, cambios de liderazgo y auditoría.
- MVC: 241 comprobaciones; rutas: 172, 42 destinos. Sintaxis PHP/JS y diff revisados; CSS del portal recompilado.
- Recorrido `tests/notas-publicacion-e2e.test.cjs`, fixture `scratch/phase8/36b404aba389`: dos pruebas correctas. Practicantes sin liderazgo acceden al módulo y no pueden evaluar; ADMIN designa desde formulario real y la sesión ya iniciada del nuevo líder obtiene la acción al recargar. Se conserva publicación, privacidad, correcciones, integración y seis temas.
- Regresión `tests/notas-e2e.test.cjs`, fixture `scratch/phase8/8daa77abcf6d`: un recorrido correcto de formularios y pérdida de acceso evaluador tras reemplazo, conservando menú y consulta propia.
- Tras revisar el aspecto de las pestañas, adapté sus superficies al tema y ejecuté la prueba filtrada de temas: comprueba fondo/texto de la pestaña propia contra las variables de las seis paletas, a 320/1280 px. Capturas `notas-navegacion-*` en la fixture de publicación.

Las fixtures y cuentas son ficticias; no se modificaron datos reales, producción, `.env` ni uploads. No se ejecutó la suite E2E general completa en esta entrega.

## Pull request

**Título:** Unifica el acceso a Notas para practicantes y líderes de área

**Descripción:**

Se incorpora una entrada Notas para todos los practicantes, con acceso desde el dashboard y pestaña Mis notas para consultar evaluaciones propias publicadas. Los líderes vigentes designados por ADMIN disponen además de Evaluar mi área, sin un rol global nuevo ni acceso fuera de su área.

La navegación se actualiza al recargar después de una designación o retirada de liderazgo. Se conservan los controles del servidor, la prohibición de autoevaluación y la privacidad de borradores e historiales de otros responsables.

Incluye comentarios, documentación, CSS compilado y pruebas de acceso, designación ADMIN y revocación. No requiere nuevas migraciones SQL ni modifica datos de usuarios reales.
