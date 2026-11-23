> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Correcciones operativas y nuevas reglas de tiempo

Plan acordado con el responsable el 2026-10-14, posterior a la migración técnica de fases 0 a 8. Cada rama parte de main tras integrar la anterior. La numeración siguiente pertenece a este nuevo plan.

1. `correccion-estados-jornadas`: cierres Revisado y Corregido, motivo y auditoría, conservación de marcas y separación de aprobación de extras. REVISADA es independiente de CORREGIDA; ambos cierran observaciones. Primera entrega integrada en main antes de comenzar la segunda.
2. `ajuste-responsive-practicantes`: resolver desbordes horizontales en las pantallas del practicante conservando el zoom accesible del navegador.
3. `confirmacion-marcajes-practicantes`: confirmación de entrada, inicio/fin de colación y salida; cancelación sin escritura y prevención de envíos duplicados.
4. `permisos-mismo-dia`: ausencia completa/parcial para hoy según el horario vigente, incluyendo interrupciones intermedias. Por decisión del responsable, los cruces con marcas se admiten en observación; el supervisor debe resolverlos antes de aprobar. Se conserva una solicitud pendiente/aprobada por día y práctica.
5. `recuperacion-horas-varios-dias`: varios tramos por permiso, selección previa de colaciones existentes en el horario, extensiones y sábados dentro de lunes a sábado 10:00–20:00; aprobación y validación efectiva. Recuperar no genera crédito de tiempo excepcional. Feriados excluidos de jornada ordinaria, con posibilidad de recuperación expresamente autorizada por supervisor. Calendario laboral individual según horario. No aplica el plazo de siete días a recuperar permisos.
6. `tiempo-excepcional-y-compensacion`: autorización informal del jefe registrada en el sistema por supervisor con motivo obligatorio. Solo reconocer tiempo efectivamente trabajado y aprobado. Compensación entrando después/saliendo antes durante siete días calendario desde la solicitud; al vencer, bloquear el canje del saldo restante conservando historial. Respetar horas extra históricas y establecer corte de entrada en vigor; no acumular nuevas extras con la regla anterior.
7. `validacion-flujos-permisos-compensaciones`: integración de saldos, informes, migraciones, documentación y publicación coordinada.

Antes de las etapas 5/6 se precisarán la fuente/mantenimiento de feriados, el tratamiento de marcas al trabajar colación y la hora exacta de vencimiento. No se supondrán reglas adicionales que alteren los saldos históricos.

Decisiones de fase 5 confirmadas: calendario común de feriados mantenido por ADMIN y colación autorizada como recuperación sin marcar descanso mientras se trabaja, validada con marcas y revisión de supervisor. Por petición del responsable, Generar ausencias queda reservado a ADMIN. El vencimiento corresponde a fase 6.

## Entrega 7: validación integral

Implementé `validacion-flujos-permisos-compensaciones` desde main `740aba2`. Integré desglose de tiempos en el informe individual y avisos de expedientes excepcionales en Permisos. Resolví la revalidación expresa de canjes históricos antes autorizados, sin renovar el vencimiento ni aprobar retrospectivamente solicitudes nuevas. Pasaron 1101 comprobaciones PHP base, 16 E2E, 12 UI, 110 HTTP y 543 de publicación. Repetir los tres parches sobre datos E2E conservó diez tablas. Producción informada: PHP 8.3.35/MariaDB 11.8.9; pruebas ejecutadas en MariaDB 10.4.32 local. No requiere SQL nuevo. La validación local termina; el merge y la publicación manual quedan a cargo del responsable, según FASE_7_VALIDACION_OPERATIVA.md. No se desplegó en Hostinger.

## Entrega 6: tiempo excepcional y compensación

Implementé `tiempo-excepcional-y-compensacion` desde main `b0dab99`: aprobación con motivo del permiso informal del jefe, crédito independiente en minutos, canje futuro aprobado por supervisor y expiración a 168 horas exactas desde la solicitud. Se separa recuperación de permiso, trabajo ordinario y tiempo excepcional; se bloquean duplicados y reservas sobre saldo insuficiente. Las marcas fuente corregidas reabren el crédito y retiran canjes dependientes. Las jornadas existentes y extras aprobadas conservan su tratamiento al corte; el endpoint antiguo queda cerrado. Detalle en FASE_6_TIEMPO_EXCEPCIONAL.md. La fase 7 de integración final permanece pendiente.

## Entrega 5: recuperación por tramos

Implementé `recuperacion-horas-varios-dias` desde main `97ba027`. Incluye plan exacto en minutos, aprobación sin crédito automático, validación de tiempo efectivo, saldo parcial y solicitudes adicionales con otra autorización, feriados comunes y protección de sábados históricos. Cambios de marcas/origen reabren créditos; planes que dejan de ser elegibles liberan saldo para reprogramar. Véase FASE_5_RECUPERACION_VARIOS_DIAS.md para reglas, publicación y límites.

Validación de esa entrega: 964 comprobaciones PHP, catorce E2E y once UI aprobadas. Migración local aplicada con respaldo; no se ejecutó en producción ni se publicó. La fase 6 se implementó posteriormente según la entrega anterior.

## Entrega 1: revisión de jornadas

- Selector independiente Revisado / Sin cambios en las marcas y Corregido / Cerrar corrección, disponible en observadas y pendientes.
- Ambos cierres exigen motivo, rol autorizado y marcas completas/coherentes. El servidor impide cierre repetido, práctica propia y reactivación de anuladas. Una incompleta necesita corregir las marcas primero.
- Se recalcula la base con la regla vigente, sin descontar una colación inventada. Se conservan marcas y extras previamente aprobadas compatibles; no se aprueban extras nuevas. Una aprobación anterior incompatible detiene la acción para revisión expresa.
- Responsable, fecha, valores anteriores/nuevos y motivo quedan en REVISAR_JORNADA. No se reutiliza aprobada_por, que corresponde a extras.
- Revisadas y corregidas dejan de contar/resaltarse como observaciones pendientes; su texto anterior queda consultable. Atrasos y otras incidencias reales no se borran.
- REVISADA participa en los acumulados PHP/SQL, métricas, historial, marcaje e informes. No hay migración de esquema porque estado_jornada ya es VARCHAR(30). No se reclasifican jornadas históricas masivamente.
- Si cambian las marcas después, el recálculo existente vuelve a evaluar la jornada y exige las aprobaciones correspondientes.

Validación local aprobada: 859 comprobaciones PHP, once recorridos E2E y siete pruebas visuales. Evidencia E2E en scratch/phase8/113829d3fb4c; detalle y límites en PROGRESO.md. El despliegue debe actualizar conjuntamente backend y contenido público de la aplicación; las nuevas reglas de recuperación y compensación no están todavía implementadas.

## Entrega 2: presentación de practicantes

- Adapté las tablas de historial, marcaje reciente, solicitudes de permisos y horario de Mis datos. Por debajo de 1280 px cada registro muestra sus campos con etiquetas; en escritorio las columnas ajustan el contenido sin desplazamiento horizontal. Los encabezados permanecen accesibles y la tabla conserva roles explícitos.
- Acoté los estilos nuevos al rol PRACTICANTE mediante rrhh-practicante en el layout. Las columnas y controles de otros roles mantienen su presentación; el componente compartido de horario conserva nombres, valores y formularios.
- Ajusté palabras largas, archivos y correo de cuenta para no ensanchar la página. El correo abreviado visualmente conserva su valor completo en el atributo title; documentos, motivos y observaciones se muestran completos.
- Conservé el viewport y el zoom manual. Los controles móviles utilizan un tamaño de texto de 1rem para reducir la ampliación automática por texto pequeño; no se limita user-scalable ni maximum-scale.
- Agregué fixtures pobladas y verificaciones de ancho del documento y de las tablas. La suite anterior comprobaba principalmente listas vacías; el nuevo escenario reprodujo el desborde de 944 px en una ventana de 320 px antes de corregirlo.
- Pasaron ocho pruebas visuales, once E2E y 859 comprobaciones PHP. Evidencia de 35 escenarios poblados entre 320 y 1440 px en scratch/responsive-practicantes/after y comparación anterior en before; E2E en scratch/phase8/e07e959c5806. Los 640 px comprueban reflujo con ancho reducido, no simulan físicamente el gesto de zoom ni Safari móvil.

Los archivos de producción afectados son app.css, Tailwind recompilado, layout/head y sidebar, componente horario-fields y las cuatro vistas de practicante con listados/documentos. No hay cambios de negocio, base de datos ni dependencias. Antes de publicar, generar los paquetes coordinados desde el commit aprobado.

### Corrección adicional: Supervisor → Practicantes

El responsable identificó que la vista operativa de supervisor seguía desbordando: la entrega anterior había cubierto el portal del practicante, y el recorrido operativo usaba lista vacía/sin ficha seleccionada. Corregí esta vista compartida también para ADMIN en correccion-responsive-gestion-practicantes, desde main cdd471f.

- Listado, historial de áreas y excepciones incorporan etiquetas/roles; el horario compartido usa sus etiquetas existentes.
- Se conserva la disposición original de listado y ficha en escritorio con tablas compactas, anchos repartidos y controles ajustados. El horario mantiene siete filas y seis columnas, sin apilar cada campo por el ancho del panel. Solo en móviles se apilan los otros listados; el horario agrupa dos campos por fila para contener su altura. El contenido largo se parte sin ocultarlo y Buscar conserva su ancho legible.
- Las pruebas cargan ficha, horario, áreas y excepción con acción de anulación; cubren ambos roles y ocho anchos entre 320 y 1920 px. Antes se reprodujeron desbordes de página y tablas; después los 16 casos no requieren desplazamiento horizontal.
- Pasaron nueve pruebas visuales, once E2E (ahora con ficha seleccionada en ambos roles) y 859 comprobaciones PHP. Evidencia visual: scratch/responsive-gestion/before y after; E2E: scratch/phase8/e05c9152fa07.

Tras la revisión visual del responsable se sustituyó el apilado por panel por el formato compacto anterior. La nueva validación exige filas de tabla, encabezados visibles y horario menor de 500 px de alto en escritorio; pasaron nueve pruebas UI y 215 MVC, además de repetir los 16 casos de gestión tras ajustar columnas. Captura específica: scratch/responsive-gestion/after/Supervisor-horario-compacto.png. La validación E2E precedente cubre los formularios conservados; este ajuste final es de presentación.

Comprobar localmente entrando como supervisor en Practicantes y abriendo una ficha. Recargar con Ctrl+F5 para obtener el CSS actualizado. No hay cambios de reglas, base de datos, dependencias ni permisos.

## Entrega 3: confirmación de marcajes

Implementada en confirmacion-marcajes-practicantes desde main cd7692c, después de integrar la presentación compacta de gestión.

- Entrada, inicio de colación, fin de colación y salida abren una ventana confirm nativa del navegador que identifica la marca seleccionada. Su apariencia y botones dependen del navegador/idioma. Usa las etiquetas vigentes del catálogo (Entrada a colación / Salida de colación). La hora se toma del servidor al aceptar, no al abrir la ventana.
- Cancelar no envía el formulario y devuelve el foco al botón original. Aceptar conserva CSRF/código y bloquea botones mostrando Registrando marca… en la página mientras se envía.
- Sin JavaScript, el primer POST prepara una confirmación en página sin crear marca/jornada; Confirmar realiza el segundo POST y Cancelar vuelve por GET. El servidor exige confirmar_marca=1 y vuelve a comprobar secuencia, sesión, práctica y CSRF antes de registrar.
- Se conserva el control de duplicados del servicio. El bloqueo visual evita envíos repetidos por clic; no representa una prueba de concurrencia InnoDB entre distintos dispositivos.
- Agregué recuperación de controles al volver desde la caché de navegación; el backend sigue rechazando tokens o secuencias obsoletos.
- Pasaron diez pruebas visuales, once E2E y 859 comprobaciones PHP en la entrega; tras sustituir el modal HTML por confirm se repitieron diez UI, once E2E y 215 MVC, aprobadas. E2E comprueba ventanas de tipo confirm reales, cancelación sin POST, doble clic con una sola escritura y colación en contexto sin JavaScript. Evidencia final: scratch/phase8/f8f87dd37313; las capturas confirmacion-* pertenecen al diseño anterior, sustituido por solicitud del responsable.

No requiere migración ni dependencias nuevas. Actualizar conjuntamente backend y public/assets/js/ui.js al publicar; regenerar paquetes desde el commit aprobado. Las recuperaciones distribuidas y tiempo excepcional siguen pendientes.

Corrección de integración local: la alternativa del servidor de un POST pendiente conservaba el botón Confirmar Entrada. La oculté cuando se inicializa JavaScript y está disponible confirm, dejando los botones principales como acceso a la ventana nativa. Sin JavaScript la alternativa sigue visible. El layout agrega ?v=<hash de contenido> a ui.js para invalidar scripts anteriores. Se verificó el POST pendiente con JS activo, ventana nativa y ausencia de escritura hasta aceptar: diez UI, once E2E y 215 MVC aprobadas. Evidencia final: scratch/phase8/d82ab5b197ce. Abrir la página desde su URL (GET) evita reenviar un formulario anterior.
