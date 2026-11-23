> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas, fase 5: integración del resumen académico

Fecha: 2026-10-19. Rama: `notas-rubrica-e-integracion`, creada desde `main`/`origin/main` en `1ee5f56`, con fase 4 integrada. Implementación local; sin commit, push ni publicación en Hostinger.

## Decisiones confirmadas

- Acompañamiento, ficha y detalle de reportes muestran la última nota semanal aprobada y su viernes.
- El puntaje anterior de Acompañamiento permanece histórico de solo lectura; no se transforma en evaluación semanal ni se elimina.
- SUPERVISOR consulta el resumen aprobado de prácticas que puede supervisar. No obtiene permisos para calificar, consultar borradores o leer motivos internos. Se conserva el bloqueo de autosupervisión.
- La nota final semanal sigue siendo manual. No se calcula promedio, suma, ponderación ni nota final global de práctica.

## Implementación

`src/Services/ResumenNotasService.php` valida cuenta y rol actuales en base de datos, existencia de prácticas y conflicto de autosupervisión. Consulta por lote la nota de mayor viernes entre las aprobadas y la cantidad de evaluaciones aprobadas. Selecciona únicamente viernes, nota final y revisión, sin observaciones o revisiones internas. Una carga atrasada no sustituye el viernes más reciente; las correcciones confirmadas se reflejan sin sincronizar columnas duplicadas.

El componente `src/Views/components/notas-resumen.php` recibe datos preparados por los controladores y utiliza clases del tema existente. Se incorpora a:

- Acompañamiento: `supervisor/acom.php`, accesible también por ADMIN. Sustituye el input independiente y conserva el puntaje anterior etiquetado como histórico.
- Ficha seleccionada en `admin/practicantes.php` y `supervisor/practicantes.php`.
- Detalle en `admin/reportes.php` y `supervisor/reportes.php`. Indica expresamente que abarca toda la práctica, independientemente del periodo de asistencia. No se añadió columna al listado general ni exportación académica.

Los tres controladores envían `Cache-Control: private, no-store`. Un fallo académico no oculta el seguimiento operativo: informa notas no disponibles sin mostrar un cero ni afirmar que no existen publicaciones. La ausencia de migraciones se distingue de una práctica sin aprobadas.

`AcompanamientoModel.php` rechaza cualquier escritura de `puntaje_evaluacion`, incluso combinada con otros campos en un formulario antiguo o POST manual. El controlador conserva la clave antigua para rechazarla explícitamente. Indicadores de evaluación enviada, reseñas, certificados y demás seguimiento continúan editables y auditados; enviar a una institución sigue siendo distinto de aprobar una nota semanal.

## Rúbrica y conservación

Se reutilizan criterios configurables y versiones inmutables de fase 3. ADMIN podrá cargar la rúbrica real en Criterios cuando se entregue. **No se recibió ni se inventó el contenido definitivo**: esa parte sigue pendiente de contenido, sin bloquear la integración.

No hay parche SQL nuevo ni cambio de `schema.sql`: se requieren 009, 010 y 011 en el orden de `database/README.md`. No se modificaron `.env`, uploads, XAMPP ni notas o puntajes de asistencia local o producción. `evaluaciones_practicante` y `acompanamiento.puntaje_evaluacion` permanecen conservados.

## Verificación

- `tests/notas-semanales.php`: 152 comprobaciones MariaDB correctas. Nuevos casos en `tests/notas-integracion-casos.php`: origen oficial, orden por viernes, correcciones, exclusión de borradores/campos internos, cuenta desactivada, SUPERVISOR, autosupervisión, IDs inválidos, conservación del puntaje y seguimiento independiente. Crea y elimina exclusivamente su base aleatoria `rrhh_e2e_*`.
- MVC: 241; rutas: 172, 42 destinos; legado/API: 374; configuración: 20.
- Negocio: 352 comprobaciones y cuatro fallos previos sobre SALIDA_PERMISO, REGRESO_PERMISO y aprobación de interrupciones. Se actualizó la expectativa de Acompañamiento para exigir puntaje histórico inmutable y seguimiento editable. No se considera aprobada la suite completa de negocio.
- Recorrido Edge aprobado con `tests/notas-publicacion-e2e.test.cjs`: publicación, consulta propia, correcciones y resolución excepcional; compara la misma nota en ficha, reportes y Acompañamiento para ADMIN y SUPERVISOR. Comprueba privacidad y ausencia del antiguo input. Captura consulta propia y resumen de reporte en seis temas a 320/1280 px. Evidencia final en `scratch/phase8/21ab4de9331f/publicacion-results.json` y capturas `resumen-notas-*`. Primer recorrido de integración aprobado en `2823dd450a4d`.
- Se recompiló Tailwind; persiste aviso previo de Browserslist desactualizado. Sin actualizar dependencias.

La verificación específica de temas usa `rrhh-personal-theme` y comprueba el atributo `data-theme`; resultados en `scratch/phase8/21ab4de9331f/integracion-temas-results.json`. Se inspeccionó visualmente Coffee a 320 px. El recorrido AJAX filtrado de `tests/e2e.test.cjs` también pasó sobre la fixture nueva `scratch/phase8/e3bd433e29af`: guarda el indicador institucional, rechaza por HTTP el antiguo campo de puntaje con mensaje compatible con el cliente y conserva el registro completo sin cambios.

Para reproducir Edge, crear una fixture nueva con `tests/e2e-setup.php`, prepararla con `tests/notas-e2e-setup.php <fixture> --publicacion` y ejecutar `node --test tests/notas-publicacion-e2e.test.cjs` definiendo `PHP_BINARY` y `E2E_FIXTURE`. No reutilizar una fixture modificada por el recorrido completo. Evidencia y credenciales ficticias quedan en `scratch/`, ignorado por Git. La suite E2E completa no se ejecutó.

## Publicación y reversión

Publicar código y assets mediante el procedimiento habitual, verificando previamente 009–011 en una copia de la base de destino. Sin SQL adicional por esta fase. Revisar una aprobada, un practicante sin publicaciones, un puntaje histórico y el guardado de indicadores.

Revertir el código restablecería el input editable anterior y volvería a permitir puntajes independientes. No requiere borrar tablas ni restaurar datos por esta fase; publicaciones e historial permanecen.

## Pull request

Título: **Integra las notas semanales aprobadas en Acompañamiento, fichas y reportes**

Descripción:

Se centraliza la consulta de la última nota semanal aprobada, identificada por viernes, en Acompañamiento, ficha del practicante y detalle de reportes. SUPERVISOR consulta el resumen sin obtener permisos para calificar ni acceso a borradores o motivos internos. Las correcciones publicadas se reflejan desde la misma fuente.

El puntaje anterior de Acompañamiento se conserva histórico de solo lectura, con bloqueo de escritura en servidor. El seguimiento institucional continúa editable y separado de la aprobación académica. No se calculan promedios ni nota final global de práctica.

Validación: 152 comprobaciones MariaDB; regresiones MVC, rutas, legado y configuración; recorrido Edge de formularios, integración y seis temas. La suite de negocio conserva cuatro fallos previos de interrupciones/permisos, documentados como pendientes. Sin migración nueva; requiere 009–011. La rúbrica definitiva sigue pendiente de recibir sus criterios.

Siguiente paso: fase 6 de validación y preparación de publicación; incorporar criterios definitivos cuando se reciban.
