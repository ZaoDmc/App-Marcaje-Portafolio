> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas — Fase 4: aprobación, publicación y consulta propia

Fecha: 19 de octubre de 2026. Rama: `notas-aprobacion-y-publicacion`, desde `main` en `58dd867`, sincronizado con el remoto. Estado: implementada y probada localmente sobre datos ficticios. Sin cambios de esquema ni publicación en Hostinger.

## Resultado y reglas confirmadas

- Para publicar se completan todos los criterios de la rúbrica guardada y la nota final manual. Esta condición fue confirmada expresamente en la fase; los borradores pueden seguir incompletos.
- Un líder vigente aprueba sus evaluaciones de integrantes sin aprobación ADMIN adicional. ADMIN registra y aprueba normalmente las notas de líderes.
- ADMIN consulta/corrige notas existentes ajenas, incluidas publicadas, manteniendo prohibida su autoevaluación. El líder original vigente también puede corregir sus evaluaciones si mantiene responsabilidad y pertenencia; el nuevo líder no hereda notas ni borradores del anterior.
- Si un borrador de integrante ya no tiene un autor habilitado para aprobarlo, ADMIN puede resolverlo excepcionalmente con motivo obligatorio. Es una excepción confirmada para no dejar pendiente la nota tras un reemplazo; no se convierte en aprobación normal ADMIN de todos los integrantes. El servidor comprueba pérdida efectiva de responsabilidad, incluyendo liderazgo, pertenencia y cuenta/rol habilitados. Se conserva autor, liderazgo, área y rúbrica originales.
- Una corrección exige motivo, revisión previa y confirmación; conserva estado APROBADA y sustituye valores de manera atómica al confirmar. Mientras se prepara, el practicante sigue viendo la publicación anterior. No se reabre silenciosamente ni se borra la nota para crear otra.
- La publicación no depende de completar horas de práctica ni la cierra. No calcula promedios, emite certificados ni envía notas a instituciones: esos alcances siguen separados.

## Cómo usarlo

### Líder o ADMIN evaluador

1. Abrir Notas y seleccionar practicante/práctica y viernes.
2. Guardar primero el borrador. Completar criterios y nota final; si se modifican datos al publicar, indicar el motivo.
3. Pulsar Revisar y publicar. La pantalla muestra persona, viernes, criterios, calificaciones, observaciones y nota final sin escribir en la base.
4. Se puede volver a editar sin cambios persistidos. Para publicar, marcar la confirmación y pulsar Confirmar publicación.
5. La nota pasa a APROBADA y queda visible en Mis notas de su propietario.

Para corregir una aprobada, abrir Corregir evaluación publicada, modificar valores, indicar motivo y revisar/confirmar la corrección. El historial interno plegado permite consultar revisiones, responsables, motivos y calificaciones anteriores. No se permite modificar criterios ni cambiar la rúbrica de esa evaluación.

ADMIN no ve un botón de aprobación normal para borradores de integrantes con líder competente. Si el borrador quedó sin responsable, la revisión previa identifica la resolución excepcional y exige motivo. La acción se audita de forma diferenciada.

### Practicante evaluado

Mis notas está disponible en el menú de todos los practicantes, incluidos líderes. La ruta `practicante/mis-notas.php` lista sus evaluaciones aprobadas por viernes y práctica. Puede abrir el detalle con los criterios de su versión, notas, observaciones públicas y fecha de última publicación. Se conserva el historial de distintos viernes, sin sumar ni promediar.

No se exponen borradores, razones internas de corrección, versiones descartadas ni notas de compañeros. Una corrección confirmada actualiza la versión vigente que ve el practicante; los valores anteriores quedan en el historial interno del responsable.

## Seguridad, estados y auditoría

El flujo es BORRADOR → revisión previa sin persistencia → confirmación → APROBADA. Solo un POST con CSRF válido y confirmación expresa publica. Los IDs, cuenta/rol, responsabilidad, estado, rúbrica y revisión esperada vuelven a comprobarse dentro de la transacción definitiva. Un segundo envío o una pestaña obsoleta se rechazan sin crear otra revisión.

La revisión previa no es una autorización reutilizable: cambios de líder, cuenta, rol o estado entre revisar y confirmar se comprueban al confirmar. Tampoco permite publicar un borrador nuevo sin guardarlo primero.

El motivo es obligatorio al guardar cambios de un borrador, modificar valores durante una aprobación, corregir una publicada o resolver excepcionalmente. Aprobar un borrador completo sin cambios permite motivo vacío; el historial registra la aprobación explícita.

Publicación/corrección escribe cabecera, detalle, aprobación, revisión e historial en una transacción. Si falla la auditoría, todo se revierte. Los eventos son `PUBLICAR_NOTA_SEMANAL`, `CORREGIR_NOTA_PUBLICADA` y `RESOLVER_BORRADOR_NOTA`. Se conserva el autor original y el último aprobador se registra por separado. El historial guarda cada instantánea completa, criterios, motivo y actor.

La consulta propia obtiene el practicante de la cuenta autenticada, no de un parámetro de URL. Requiere dueño y APROBADA antes de devolver un detalle. La ruta solo admite GET/HEAD; POST recibe 405. Un ID ajeno o propio sin publicar no revela su estado. Las respuestas son privadas con `Cache-Control: private, no-store`. La lectura de cabecera/detalle se realiza en una transacción de snapshot REPEATABLE READ para no mezclar versiones al corregirse simultáneamente.

El historial interno vuelve a exigir permisos de evaluador/ADMIN y no está disponible para el evaluado ordinario. El menú no sustituye autorización del servidor. CSRF, revisión esperada y formato/escala se validan aunque se envíe el formulario directamente sin interfaz.

## Archivos y despliegue

- `src/Services/PublicacionNotasService.php`: preparación, publicación/corrección, resolución excepcional, historial y consulta propia.
- `src/Services/NotasService.php`: snapshot coherente de lectura; el guardado de borradores sigue rechazando aprobadas.
- `src/Services/AuditoriaService.php`: nuevos eventos de publicación.
- `src/Controllers/Shared/NotasEvaluacionesController.php`: separar guardar/revisar/confirmar/volver; manejo de corrección explícita.
- `src/Controllers/Practicante/MisNotasController.php` y `src/Views/practicante/mis-notas.view.php`: consulta privada aprobada.
- `src/Views/components/notas-confirmacion.php`, `notas-revisiones.php` y vista compartida: confirmación e historial interno plegado.
- `config/routes.php` y sidebar: Mis notas para PRACTICANTE; sin facultades nuevas para SUPERVISOR.
- Tailwind compilado y pruebas de servicio/Edge; plan, contexto y progreso actualizados.

La fase no necesita un parche nuevo: reutiliza las tablas y columnas de 009, 010 y 011. No se modificó `schema.sql`. Antes de publicar, comprobar que esas migraciones ya estén aplicadas; no reimportar el schema completo sobre producción.

Publicar backend/frontend de la misma versión, incluyendo CSS compilado. Verificar primero un flujo de aprobación con cuentas autorizadas, la consulta propia y un acceso ajeno rechazado. La fase no despliega a Hostinger ni modifica `.env` o uploads. Las pruebas crean usuarios y notas únicamente en bases ficticias `rrhh_e2e_*`; no crean calificaciones en la base habitual del proyecto.

Para revertir código, conservar notas, revisiones, rúbricas y auditoría. Una versión anterior puede dejar aprobadas en solo lectura y sin consulta propia, pero no debe convertirlas en borradores ni eliminar publicaciones. No restaurar la base completa automáticamente porque se perderían operaciones posteriores.

## Validación reproducible

- `php tests/notas-semanales.php`: 135 comprobaciones MariaDB. Incluye las regresiones de fases 2/3 y casos de completitud, confirmación, revisión sin escritura, autor/rol/área, aprobación directa por líder, ADMIN a líderes, privacidad, correcciones, motivo, resolución excepcional e idempotencia de migraciones. Un fallo de auditoría de publicación conserva borrador, detalle e historial. La concurrencia previa sigue comprobando serialización y rechazo de escrituras obsoletas entre procesos.
- `php tests/mvc.php`: 240 comprobaciones; `tests/routing.php`: 172 y 42 rutas; `tests/legacy-removal.php`: 372; `tests/liderazgos.php`: 33; `tests/configuration.php`: 20. Sintaxis PHP/JS y diff revisados.
- `npm run build:css:portal`: compilación correcta, con el aviso anterior de Browserslist.
- Recorrido Edge de fase 4 aprobado en `scratch/phase8/4a8c2fd24ddf/`: aprobación completa, volver a editar sin escribir, rechazo de incompletas, CSRF, confirmación/reenvíos, consulta antes/después, IDs ajenos, POST a ruta de lectura, XSS escapado, corrección por líder/ADMIN, nota del líder, reemplazo y resolución excepcional ADMIN. La prueba inicial detectó y permitió corregir el required heredado del motivo en una aprobación sin cambios.
- Mis notas en seis temas a 320/1280 px sin desplazamiento horizontal; doce capturas, con revisión visual de Coffee móvil. Evidencia y resumen en `publicacion-results.json` y registros de esa fixture, ignorados por Git.
- Regresión de formularios de fase 3 sobre fixture nueva independiente `scratch/phase8/b414d4532dd9/`, para criterios variables, versiones, borradores, CSRF, revisiones obsoletas, alternativa sin JS y temas.

Para preparar el recorrido de fase 4:

```powershell
php tests/e2e-setup.php RUTA_VENDOR_PARA_PRUEBAS
php tests/notas-e2e-setup.php RUTA_FIXTURE_JSON --publicacion
$env:PHP_BINARY='RUTA_PHP_EXE'
$env:E2E_FIXTURE='RUTA_FIXTURE_JSON'
node --test tests/notas-publicacion-e2e.test.cjs
```

Usar una fixture nueva para cada recorrido. Para fase 3 omitir `--publicacion` y ejecutar `tests/notas-e2e.test.cjs` sobre otra fixture. Se usan solo conexiones locales y cuentas ficticias; no pasar una base del usuario ni versionar JSON con credenciales. Edge es Chromium: no se declara probado Opera GX directamente.

No se ejecutó la suite E2E completa ni se declaran resueltos los cuatro fallos anteriores de negocio documentados en fase 1. Producción y calificaciones reales no se tocaron.

## Pull request

**Título:** Notas · Fase 4: aprobación, consulta propia y correcciones auditadas

**Descripción:**

### Cambios

- Habilita revisión y confirmación para publicar evaluaciones semanales completas.
- Permite a líderes aprobar a sus integrantes y a ADMIN aprobar las notas de líderes.
- Añade Mis notas para que cada practicante consulte exclusivamente sus evaluaciones aprobadas.
- Incorpora correcciones con motivo, control de revisiones e historial interno, conservando visible la publicación anterior hasta confirmar.
- Permite a ADMIN resolver excepcionalmente borradores sin responsable habilitado, conservando autor y rúbrica originales.
- Protege consulta y escrituras mediante permisos de servidor, CSRF, confirmación explícita y transacciones.

### Validación

135 comprobaciones MariaDB, pruebas de MVC, rutas, liderazgo, configuración y legado/API. Recorridos Edge de publicación y regresión de formularios, con comprobación de los seis temas en móvil y escritorio.

### Despliegue

No agrega migraciones. Requiere 009–011 aplicadas y actualización coordinada de backend, frontend y CSS. La integración con Acompañamiento y reportes corresponde a la fase 5.
