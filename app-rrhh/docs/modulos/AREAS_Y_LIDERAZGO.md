# Áreas y liderazgo

## Catálogo, pertenencia y responsabilidad

Un área es un catálogo; pertenecer al área y liderarla son relaciones diferentes. ADMIN mantiene áreas desde Catálogos. La baja debe conservar referencias e historial y respetar las restricciones del servicio; no borrar un área usada para liberar relaciones.

ADMIN/SUPERVISOR asigna el área vigente desde la ficha. Un traslado cierra la pertenencia anterior y abre otra, sin reiniciar horas. ADMIN designa, reemplaza o retira liderazgo desde **Notas → Áreas y líderes**, con motivo, control de revisión e historial.

## Elegibilidad

La candidatura requiere pertenencia actual, práctica/persona habilitadas y estado de práctica PENDIENTE_INICIO o EN_PROGRESO. No confundir ausencia de integrantes con error del selector: primero asignar Área vigente en Practicantes, después designar liderazgo. No asignar personas automáticamente para llenar la lista.

El buscador filtra nombre completo, correo o RUT entre candidatos del área. Ignora mayúsculas, tildes y signos del RUT. No amplía permisos; el servidor vuelve a validar. Sin JavaScript conserva el selector nativo completo.

## Cambios de líder

Debe retirarse la designación antes de trasladar/dar de baja a un líder o desactivar su área según las restricciones vigentes. Conservar un solo liderazgo abierto por área y las comprobaciones de concurrencia/revisión del servicio.

Un nuevo líder obtiene evaluación de integrantes al recargar su contexto PRACTICANTE, sin cambiar rol global. No hereda notas o borradores del anterior. El detalle está en [Notas](NOTAS.md).

Los bloques de área de una jornada son otra relación: representan tramos horarios sin solapamientos y no conceden liderazgo ni pertenencia académica por sí solos.

## Código y tablas

`src/Services/LiderazgoService.php`, `src/Models/AreaPracticanteModel.php`, `src/Models/AreaJornadaModel.php`, `src/Models/CatalogoModel.php`, `src/Controllers/Admin/NotasController.php`, `src/Views/admin/notas.view.php` y `public/assets/js/notas-lideres.js`.

Tablas: `areas_practica`, `historial_areas_practicante`, `liderazgos_area` y `bloques_area_jornada`.

Procedencia: [fase inicial](../historial/notas/FASE_1_NOTAS_AREAS_Y_LIDERES.md) y [buscador y accesos](../historial/notas/NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md).
