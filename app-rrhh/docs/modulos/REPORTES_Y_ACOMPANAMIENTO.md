# Reportes y acompañamiento

## Qué muestran

Dashboards y reportes resumen prácticas, jornadas, incidencias y avance. El total histórico incluye las horas previas reconocidas; las horas del periodo seleccionado son otra métrica y no deben rotularse como total de práctica.

Los desgloses distinguen recuperación validada, compensación aplicada, tiempo excepcional aprobado y saldo disponible. No volver a sumar al avance los importes ya reconocidos dentro de las jornadas. Aprobar tiempo excepcional no aumenta directamente las horas acumuladas.

El resumen académico de ficha, Acompañamiento y reportes procede de la **última nota semanal aprobada**, con su viernes y número de publicaciones. No depende del rango de asistencia ni calcula promedio final. El puntaje antiguo de Acompañamiento es histórico de solo lectura; no debe quedar una segunda nota oficial editable independiente.

## Acceso y presentación

ADMIN/SUPERVISOR consulta dentro de los controles operativos existentes, excluida autosupervisión. No mostrar borradores, motivos internos ni historial privado del evaluador en el resumen académico. Los detalles extensos pueden plegarse con botones accesibles sin eliminar información.

El módulo de Acompañamiento tiene endpoints propios; conservar sus validaciones y respuestas al editar seguimiento o monitoreo. No equiparar los datos de seguimiento con las calificaciones semanales.

## Código y pruebas

`src/Services/ReportesService.php`, `src/Services/ResumenNotasService.php`, `src/Models/MetricasModel.php`, `src/Models/AcompanamientoModel.php`, controladores compartidos y vistas de reportes, dashboard y acompañamiento. Buscar sus destinos exactos en `config/routes.php` antes de editar un endpoint.

Pruebas relevantes: negocio, MVC, legado/API y recorridos de publicación/integración de Notas. Procedencia: [integración académica](../historial/notas/FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md) y [validación operativa](../historial/operativa/FASE_7_VALIDACION_OPERATIVA.md).
