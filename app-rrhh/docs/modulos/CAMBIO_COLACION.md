# Cambio diario de colación

Entrega de desarrollo 2.1.0, 06/11/2026. No acredita publicación en Hostinger.

## Regla y flujo

El practicante solicita desde Permisos cambiar **la colación de hoy** entre 13:00–14:00 y 14:00–15:00, siempre una hora. La fecha y la práctica se obtienen de la sesión y del servidor: no se admiten fechas arbitrarias ni solicitudes de otra persona. Debe existir práctica activa en vigencia, jornada asignada y colación vigente de una hora en una de esas franjas. El descanso solicitado debe caber completo en el turno y ser distinto del actual.

La solicitud puede hacerse el mismo día, incluso después del tramo de colación; no se crea para un día anterior desde este formulario. ADMIN/SUPERVISOR puede aprobar o rechazar posteriormente, incluso después de terminada la jornada o en días posteriores, con motivo obligatorio. No se permite autosupervisión ni autoaprobación. Una solicitud pendiente no cambia el horario efectivo.

En Permisos de superiores aparece **Cambio de colación**, con filtros por nombre/correo, fecha y estado, paginación y detalle emergente. El menú suma las pendientes autorizadas al indicador de Permisos. El practicante consulta su historial propio. Los motivos se escapan al renderizar.

## Aplicación temporal y marcas

La aprobación guarda una superposición para esa fecha, consultada por `asistencia_horario_vigente_dia`; no modifica `detalle_horario_practicante` ni crea un permiso de cambio de horario semanal. Al día siguiente se utiliza el horario correspondiente sin restauraciones programadas.

Se recalcula la jornada existente en la misma transacción de revisión, conservando las marcas reales. El descanso no pasa a ser tiempo trabajado, no genera recuperación ni crédito excepcional y no se borran incidencias de otra causa. Si faltan las marcas de pausa, la aprobación de la franja no las fabrica ni deja válida la jornada forzosamente.

Una firma del horario detecta cambios entre envío y revisión: no se aprueba sobre otro turno sin resolver el conflicto. Una modificación manual posterior de horario/calendario prevalece y deja de aplicar la superposición si ya no coincide con la firma; el detalle informa esa situación y conserva la aprobación histórica.

## Conflictos y trazabilidad

- No se admiten cruces de ausencia con la colación original o la nueva: cambiar la pausa podría alterar la deuda del permiso.
- Se comprueban recuperaciones reservadas en ambas franjas y canjes de compensación que ocupen la nueva pausa; se exige resolverlos antes.
- Una sola solicitud PENDIENTE/APROBADA por práctica y fecha, serializada mediante bloqueo de práctica. Un rechazo permite nueva solicitud sin sobrescribir el historial anterior.
- Se registran `SOLICITAR_CAMBIO_COLACION` y `REVISAR_CAMBIO_COLACION`, con actor, fecha, valores y motivo. Un fallo de auditoría revierte solicitud o revisión junto con su recálculo.

## Código, datos y publicación

Servicio `src/Services/ColacionService.php`, resolución de horario en `ReglasAsistenciaService.php`, controladores de Permisos, `Shared/LayoutContext.php` y componente `src/Views/components/cambio-colacion.php`.

Tabla `solicitudes_colacion`, con relaciones RESTRICT a práctica y usuarios solicitante/revisor. El mantenimiento manual de cuentas debe contemplar esas dependencias; no borrar historiales para evitar una FK.

**Respaldar y aplicar el parche 016 antes de publicar el código.** `schema.sql` incorpora la tabla para instalaciones nuevas. Las consultas de horario usan esta tabla: no publicar el código antes de crearla. No importar schema sobre una instalación existente. Sin nuevas variables de entorno o dependencias.

Pruebas: negocio SQLite con datos ficticios; MVC/CSRF y escape de HTML; migración MariaDB local aleatoria (parche repetible, inclusión en schema y protección FK); Edge con Coffee, tres roles y 390/1280 px sin enviar formularios reales. No certifica concurrencia InnoDB, todos los temas/navegadores ni producción.
