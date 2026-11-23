# Notas de practicantes

## Quién evalúa y consulta

Todos los practicantes tienen **Notas → Mis notas**, incluso sin publicaciones. Si ADMIN les designa como líder vigente, aparece **Evaluar mi área** al recargar. Retirar liderazgo conserva la consulta propia y bloquea evaluación de equipo.

El líder registra y publica notas de sus integrantes autorizados; ADMIN registra y publica las de líderes. El selector habitual de Evaluaciones de ADMIN muestra solo líderes vigentes. ADMIN conserva consulta y corrección de notas existentes de cualquier practicante por enlace directo, con motivo e historial. Acompañamiento y Reportes muestran el resumen aprobado a quienes pueden supervisar, sin borradores ni motivos internos.

## Periodicidad y criterios

| Ajuste académico | Valor vigente |
| --- | --- |
| Identidad del periodo | Práctica y viernes, sin duplicados |
| Día de carga | Cualquier día; permite viernes atrasados |
| Semana actual | Viernes actual disponible desde el lunes; fines de semana se refieren al viernes recién pasado |
| Criterios | Configurables por ADMIN, entre 1 y 50 |
| Escala por criterio | 1 a 7 en pasos de 0,5; Sin evaluar durante borrador |
| Nota final semanal | Manual; no se calcula un promedio o ponderación |
| Publicación | Todos los criterios y nota final completos, revisión y confirmación explícitas |

ADMIN configura etiquetas y filas en Criterios creando una versión de rúbrica. Las evaluaciones conservan su versión, cantidad, orden y texto. Los diez rótulos iniciales son provisionales; no constituyen la rúbrica definitiva del cliente. Editar este Markdown no cambia la configuración almacenada.

Acumular notas significa conservar semanas, no sumarlas ni calcular una nota de cierre. Una semana sin nota no es un cero. El tratamiento de semanas parciales sin viernes sigue pendiente; no inventar periodos fuera de la práctica.

## Autoría y publicación

- BORRADOR no es visible en Mis notas. Preparar/revisar publicación no escribe; confirmar guarda/publica de manera transaccional.
- Las correcciones de una publicada mantienen visible la versión anterior hasta confirmar, preservando autor, revisión, motivo y valores anteriores.
- El líder nuevo puede registrar viernes faltantes de sus integrantes, incluidos atrasados, pero no editar notas ni continuar borradores del anterior.
- ADMIN puede resolver excepcionalmente un borrador sin responsable habilitado, con motivo, sin reasignar autoría ni borrar historial.
- Se prohíbe autoevaluación y acceso a otra área mediante IDs. El servidor revalida identidad, responsabilidad y revisión esperada.

## Código y datos

Controlador compartido: `src/Controllers/Shared/NotasEvaluacionesController.php`; presentación en `src/Views/shared/notas-evaluaciones.view.php`, `src/Views/components/notas-nav.php` y `src/Views/practicante/mis-notas.view.php`.

Servicios en `src/Services/`: `NotasService.php`, `NotasFormularioService.php`, `RubricasNotasService.php`, `PublicacionNotasService.php` y `ResumenNotasService.php`. `LayoutContext.php` prepara el acceso evaluador antes del HTML.

Tablas: `rubricas_notas`, `configuracion_notas`, `notas_semanales`, `notas_semanales_detalle`, `notas_semanales_historial` y relaciones de área/liderazgo. `evaluaciones_practicante` y el puntaje previo de Acompañamiento no se convierten automáticamente en notas semanales.

Procedencia y evidencia: [historial de Notas](../historial/README.md#notas). Diagnóstico de solo lectura: `php scripts/check-notas.php --check`. Las pruebas usan fixtures aisladas; consultar [Pruebas](../desarrollo/PRUEBAS.md).
