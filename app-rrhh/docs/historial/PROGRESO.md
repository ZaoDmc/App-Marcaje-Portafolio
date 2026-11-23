> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Progreso del Proyecto

## 2026-10-20 — Buscador de candidatos a líder y diagnóstico de áreas vacías

- Ajuste posterior solicitado: el selector de Evaluaciones de ADMIN muestra solo líderes vigentes; los líderes conservan sus integrantes. La consulta/corrección administrativa por enlace de otras prácticas sigue habilitada, sin añadirlas al selector ni seleccionar silenciosamente otro líder. Se mantiene acceso a notas aprobadas por Acompañamiento/Reportes. Cambios comentados en controlador y vista compartidos, sin SQL ni datos reales.
- Validación del filtro: MVC 241, sintaxis y diff correctos; recorrido Edge de publicación/corrección/resolución ADMIN aprobado en scratch/phase8/85bbd3f92bca, con verificación de exclusión de integrantes del selector y corrección global por enlace. El primer intento eede49d6ac58 no llegó al flujo por omitir --publicacion al preparar cuentas ficticias; se repitió correctamente en una fixture nueva. Sin commit/push/despliegue.

- En la misma rama notas-acceso-practicantes-y-lideres, añadí búsqueda por nombre completo, correo o RUT al formulario ADMIN. Mantiene el selector nativo sin JavaScript y revalida todos los permisos en el servidor. Sin cambios de tablas ni asignaciones automáticas.
- El diagnóstico local de solo lectura encontró ocho áreas sin pertenencias abiertas y once prácticas activas sin área vigente. Agregué explicación y enlace a Practicantes cuando no hay candidatos; primero se asigna Área vigente desde la ficha y luego el liderazgo.
- Comenté los cambios y documenté el flujo en NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md. Comprobaciones: liderazgo 33, Notas MariaDB 170, MVC 241; prueba UI Edge con 200 candidatos y alternativa sin JS; recorrido ADMIN de búsqueda/designación/reemplazo/retiro aprobado en scratch/phase8/e4e843e50c8d. CSS recompilado, sintaxis/diff correctos. No se ejecutó la suite E2E general completa ni se modificaron datos reales, .env o uploads. Sin commit/push/despliegue.

## 2026-10-20 — Notas para todos los practicantes y evaluación por liderazgo

- Creé notas-acceso-practicantes-y-lideres desde main 3270d66, con mejoras visuales integradas y árbol limpio. Sin commit, push ni despliegue.
- El servidor ya distinguía consulta propia y evaluación por líder; unifiqué el menú Notas para todos y añadí navegación Mis notas / Evaluar mi área y acceso desde el dashboard. La evaluación aparece solo con liderazgo vigente; retirar liderazgo conserva la consulta propia.
- Conservé autorización, rutas y reglas de calificación. No se conceden roles globales nuevos ni se asignaron liderazgos reales. Sin SQL nuevo, cambios de entorno o uploads.
- Notas MariaDB: 170; MVC: 241; rutas: 172/42 destinos. Recorrido Edge aprobado en scratch/phase8/36b404aba389: menú común, evaluación bloqueada sin liderazgo y habilitada al designar por ADMIN desde formulario real, sobre la sesión del practicante ya iniciada; publicación, privacidad e integración conservadas. CSS recompilado y sintaxis/diff revisados.
- Documenté uso, archivos, límites y descripción de PR en NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md; regresión de formularios y revocación en otra fixture aislada.

- Regresión de formularios/revocación aprobada en scratch/phase8/8daa77abcf6d. Adapté las nuevas pestañas al tema y comprobé sus colores reales en seis paletas a 320/1280 px, con capturas notas-navegacion-* en la fixture de publicación. No se ejecutó el E2E general completo.

## 2026-10-20 — Mejoras visuales de ADMIN y permisos parciales

- El responsable sustituyó la indicación anterior de campos azules por gris en el login Claro: fondo #E2E8F0, texto #0F172A y placeholder #475569, incluido el autocompletado. Autorizó variante de logo negra/roja sobre blanco; creé logo_rojo_negro.png mediante imagegen desde el logo existente y conservé el original para Oscuro. Actualicé BRANDING para reflejar esta excepción expresa. Sin cambios de autenticación ni datos.

- A petición del responsable, los campos de correo y contraseña del login Claro usan #1E293B, como la base del logo, con placeholders negros y texto ingresado blanco. Añadí protección del fondo en autocompletado WebKit/Chromium, limitada al modo claro; sin cambios de autenticación.

- Ajusté posteriormente el fondo del logo del login a slate-800 (#1E293B), el mismo tono de la tarjeta oscura, para que esa base se use también en modo claro y se integre con la tarjeta en oscuro.

- Corregí el login: sustituí el JPG de fondo blanco por public/assets/brand/logo_rojo_blanco.png sobre azul institucional, con proporciones conservadas en claro/oscuro. En Mis datos local sustituí object-cover por object-contain/object-center y añadí recomendación de 500 × 500 px vinculada al campo; sin recorte, redimensionado del archivo ni cambios de subida.
- Recompilé CSS de portal y login. QA temporal sin base de datos: 12 comprobaciones en Edge, 320/1280 px, claro/oscuro e imágenes ficticias verticales/horizontales completas. Evidencia en scratch/qa-login-foto; sintaxis PHP y diff correctos. No se consultó producción ni se modificaron fotos reales.

- A petición del responsable, convertí BRANDING.md en una plantilla de planificación con diez apartados desplegables, instrucciones, sugerencias y campos para completar. Distingue propuestas de decisiones y no aplica cambios visuales. No se reorganizó el resto de los Markdown.

- Posteriormente reescribí las respuestas guardadas como documentación de identidad y diseño: logotipo, tipografía, escala aprobada, paleta, temas y tarjetas. Conservé como propuestas las medidas aún rotuladas «sugerencia» y documenté las ambigüedades tipográficas, el fondo de avisos y los apartados 8–10 aún sin completar. Sin implementar cambios de interfaz ni modificar las otras mejoras en curso.

- El responsable confirmó familia tipográfica común, avisos con fondo coloreado y medidas propuestas de tarjetas. Reorganicé BRANDING.md con índice y tablas «Ajuste / Valor», incluyendo «Tamaño de tarjetas» y ejemplos editables en px. Sustituí clases técnicas del logotipo por medidas legibles; conservé como pendientes los controles, tablas, espaciado e iconos aún no definidos. Solo documentación, sin aplicar estas reglas al código.

- Creé Mejoras-visuales-vista-admin desde main e262781, sustituyendo los espacios del nombre solicitado por guiones. Sin commit, push ni despliegue.
- Vinculé las variables propias del dashboard ADMIN con las paletas oscuras y corregí fondos fijos, textos, bordes, filtros, estados neutros y contraste de acciones/íconos Black. Conservé la distribución y el diseño de Claro.
- Reemplacé los fondos ámbar translúcidos del formulario parcial y su resumen por superficies semánticas del tema; texto explicativo y bordes coherentes. Sin cambios de cálculos o permisos.
- Recompilé el CSS y revisé sintaxis/diff. QA visual Edge sobre copia ficticia scratch/phase8/9799ce1dbc96: 254 superficies, seis temas, 320/1280 px y tres roles, incluido desplegar el tramo parcial y su resumen sin enviar permisos. Gráficos externos bloqueados en la revisión; no se modificó Chart.js ni se certifica Opera GX directamente.
- Documenté archivos, causa, revisión manual y PR en MEJORAS_VISUALES_ADMIN_Y_PERMISOS.md. Sin modificaciones de datos reales, .env, uploads o SQL.

## 2026-10-19 — Notas, fase 6: validación y preparación de publicación

- Creé notas-validacion-y-despliegue desde main/origin/main 0cec90e, con fase 5 integrada y árbol limpio. Sin commit, push ni publicación en Hostinger.
- Añadí check-notas.php y notas-preflight-lib.php para diagnosticar instalación mediante lecturas, sin aplicar parches ni exponer registros. Verifiqué localmente las seis tablas InnoDB, configuración y publicaciones completas. Casos adversos y READ ONLY cubiertos en la base temporal.
- Añadí confirmación concurrente desde dos conexiones reales: un éxito y un rechazo obsoleto, con una sola revisión/instantánea/auditoría. Repetí 009–011 sobre datos poblados y comprobé conservación de notas, liderazgos y puntaje histórico. Notas MariaDB: 170 comprobaciones correctas.
- Investigué los cuatro fallos previos de negocio: el escenario intentaba marcar una pausa con permiso pendiente y fuera de ventana. Actualicé las expectativas y comprobé rechazo, aprobación y límites horarios; no se modificaron los servicios de producción. Negocio: 354 comprobaciones, cero fallos. MVC 241, rutas 172/42 destinos, legado/API 374, liderazgo 33 y configuración 20 correctas.
- E2E general: 17 recorridos aprobados en scratch/phase8/440a06b5038b. Publicación/integración/temas: 2 en 997aae79ab5e. Formularios/rúbricas/sin JS: 1 en 70ab77956dc8. Veinte recorridos totales, sobre copias distintas y usuarios ficticios; sin modificar asistencia ni producción.
- Assets recompilados. Composer instaló las siete dependencias del lock solo en staging; el intento sin red se interrumpió y la repetición autorizada finalizó. Paquete de validación scratch/releases/20261019-214416-0cec90e9-ca7886: 364 archivos íntegros y 595 comprobaciones de release. Tiene cambios sin commit; regenerar desde main limpio tras el merge para publicar.
- Documenté matriz de evidencia, diagnóstico, migraciones, publicación manual, aceptación y reversión en FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md. Sin parche SQL nuevo ni cambios de .env, uploads o XAMPP. Rúbrica definitiva y validación/aceptación en Hostinger pendientes.

## 2026-10-19 — Notas, fase 5: resumen oficial e integración

- Creé notas-rubrica-e-integracion desde main/origin/main 1ee5f56, con fase 4 integrada. Sin commit, push ni despliegue.
- Confirmé mostrar última nota semanal aprobada con viernes, conservar puntaje anterior de Acompañamiento histórico de solo lectura y permitir consulta SUPERVISOR sin edición, borradores o motivos internos.
- Agregué ResumenNotasService con cuenta/rol comprobados en base, autorización por lote y bloqueo de autosupervisión. Elige por viernes y refleja correcciones sin copiar notas a columnas independientes. Reutilicé un componente en Acompañamiento, ficha seleccionada y detalle de reportes ADMIN/SUPERVISOR, con respuestas privadas sin caché y tratamiento independiente de errores académicos.
- Bloqueé escrituras del puntaje histórico incluso desde formularios antiguos; seguimiento institucional conserva auditoría. Sin fórmula de promedio ni nota final global de práctica.
- Notas MariaDB: 152 comprobaciones correctas; MVC 241, rutas 172/42 destinos, legado/API 374 y configuración 20. Negocio conserva cuatro fallos previos de interrupciones/permisos (352 comprobaciones). Edge aprobado en scratch/phase8/21ab4de9331f: formularios, publicación, privacidad, correcciones, integración de tres pantallas y seis temas a 320/1280 px. No se ejecutó la suite E2E completa.
- Verifiqué las paletas del personal con su clave de preferencia propia y comprobación de data-theme; inspección visual Coffee móvil correcta. Recorrido AJAX filtrado aprobado en scratch/phase8/e3bd433e29af: guarda seguimiento, rechaza POST antiguo con mensaje explícito y conserva todo el registro. Sintaxis PHP/JS y diff revisados.
- Recompilé Tailwind y documenté alcance, pruebas, publicación, reversión y PR en FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md. Sin parche nuevo ni cambios de datos del usuario, entorno o uploads. Rúbrica definitiva pendiente de recibir criterios. Siguiente fase: validación y preparación de publicación.

## 2026-10-19 — Notas, fase 4: aprobación, consulta propia y correcciones

- Creé notas-aprobacion-y-publicacion desde main 58dd867, sincronizado con origin/main y con fase 3 integrada. Sin commit, push ni despliegue.
- Confirmé con el responsable dos reglas antes de cerrar la implementación: exigir todos los criterios y nota final completos; permitir resolución excepcional ADMIN de borradores sin responsable habilitado, con motivo, sin transferirlos al líder nuevo.
- Agregué PublicacionNotasService, revisión previa sin escritura, confirmación expresa, transacción y revisión esperada. Publiqué con los roles acordados, sin aprobación ADMIN intermedia para integrantes con líder competente. Correcciones por responsable original vigente o ADMIN conservan la versión visible hasta confirmar, junto con valores anteriores, actor, fecha y motivo.
- Añadí MisNotasController, vista propia y ruta GET/HEAD exclusiva PRACTICANTE; menú Mis notas para todos, incluidos líderes. Se deriva dueño desde la cuenta y solo se muestran aprobadas de sus prácticas; IDs ajenos, borradores e historial interno quedan excluidos. Respuestas privadas con no-store; lectura coherente de cabecera/detalle mediante transacción REPEATABLE READ.
- Añadí historial interno plegado en la gestión, separado de la consulta propia. Auditoría de publicación, corrección y resolución excepcional; si falla, se revierten estado, detalle e historial juntos. Reenvíos o formularios obsoletos no duplican publicaciones.
- La prueba Edge detectó el required heredado del motivo al aprobar sin cambios; ajusté formulario y regresión para mantener motivo obligatorio en edición/corrección/resolución, sin exigirlo en aprobación normal sin cambios.
- Pruebas: 135 comprobaciones MariaDB con datos ficticios; 240 MVC, 172 rutas (42 destinos), 372 legado/API, 33 liderazgo y 20 configuración. Recorrido Edge de publicación aprobado en scratch/phase8/4a8c2fd24ddf: completitud, volver a editar, CSRF, confirmación/reenvíos, privacidad, correcciones, ADMIN a líderes, resolución tras reemplazo y seis temas a 320/1280 px. Regresión de formularios de fase 3 ejecutada sobre otra fixture. Evidencia concreta en FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md; no se ejecutó la suite E2E completa.
- Recompilé Tailwind del portal y revisé sintaxis/diff. La fase reutiliza 009–011; no agregué parche ni modifiqué schema, .env, uploads, configuración XAMPP o notas de asistencia local/Hostinger. Documenté alcance, uso, despliegue, pruebas y PR; integración de rúbrica definitiva/Acompañamiento/reportes sigue en fase 5.

## 2026-10-19 — Notas, fase 3: formularios y criterios configurables

- Creé notas-formulario-y-borradores desde main 932113b, comprobado sincronizado con origin/main. Sin commit, push ni publicación remota.
- Añadí rutas/vistas de evaluaciones ADMIN/PRACTICANTE y criterios ADMIN, navegación dentro de Notas y menú de evaluación solo para líderes vigentes. Los proveedores preparan el alcance antes del HTML y revalidan cuenta, rol, área y liderazgo; no se confía en IDs del navegador.
- Incorporé viernes pendientes/atrasados, consulta de borradores, motivo y revisión esperada al editar, CSRF, PRG, escape y conservación de entradas ante errores. Se conserva el token por sesión para permitir pestañas simultáneas sin perder protección por revisión. Publicar y corregir aprobadas sigue pendiente de fase 4.
- A pedido del responsable, sustituí diez filas fijas por versiones de rúbrica: ADMIN agrega/quita filas y escribe criterios, con rango técnico 1–50. Cada evaluación conserva etiquetas, orden y cantidad de su versión, aun si cambia la activa; se rechaza un formulario nuevo con versión obsoleta. No se inventó rúbrica académica ni ponderación.
- Agregué parche 011 y schema con el mismo SQL. Un ajuste de CHECK amplía posiciones a 50 y configuracion_notas identifica la rúbrica vigente con revisión, actor y motivo. Añadí auditoría atómica de cambios y etiquetas en las instantáneas de notas.
- Apliqué 010/011 exclusivamente a asistencia local con scripts/migrate-notas-local.php, tras respaldo completo con mysqldump y SHA-256; checksums de 36 tablas previas conservados. Evidencia privada ignorada: scratch/notas-migration-20261019-165146-7cdc3d. No se cambió .env, uploads, configuración XAMPP ni Hostinger.
- Pruebas: 106 comprobaciones MariaDB de notas/rúbricas, incluyendo versiones, escala, privacidad, revocaciones, cambios de área/líder, auditoría, idempotencia y concurrencia. También 239 MVC, 170 rutas y 366 legado/API. Un recorrido Edge específico aprobado, repetido con fixture nueva tras mejorar contraste de etiquetas; formularios reales, CSRF, IDs ajenos, XSS escapado, viernes atrasados, edición ADMIN, versión conservada, pestaña obsoleta, sin JS y retiro de liderazgo. Capturas y resultados en scratch/phase8/84d740dfe917; seis temas a 320/1280 px sin desborde y rúbrica ADMIN a ambos anchos. No se ejecutó la suite E2E completa.
- Recompilé Tailwind del portal; persiste el aviso previo de Browserslist. Añadí contraste semántico de etiquetas/ayudas solo dentro de Notas, respetando los seis temas. Documenté uso, pruebas, migración y PR en FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md; actualicé plan, contexto e inventario SQL.

## 2026-10-19 — Notas, fase 2: base semanal y permisos

- Creé notas-base-y-permisos desde main 853d2bd, comprobado sincronizado con origin/main. No hice commit, push ni PR remoto.
- Incorporé viernes, carga cualquier día y registro de atrasadas. ADMIN consulta y edita notas existentes de cualquier practicante, sin autoevaluación. Líder nuevo registra faltantes, sin heredar ediciones ni borradores; ADMIN resuelve estos últimos según el criterio indicado antes de comenzar y aceptado al continuar.
- Agregué parche 010 y schema idéntico para rubricas_notas, notas_semanales, detalle e historial. Clave única práctica/viernes, restricciones de escala/estado/aprobación y relaciones sin borrado en cascada. No convertí evaluaciones_practicante ni puntajes de Acompañamiento.
- Añadí NotasService con cuenta/rol comprobados en base, liderazgo y pertenencia, lectura privada, viernes pendientes, guardado de borradores, revisión esperada, motivo, diez posiciones y nota final manual. Usa bloqueos, lecturas actuales y auditoría atómica. ADMIN conserva área original al corregir después de traslados o bajas.
- La base prepara estados y lectura propia de aprobadas, pero formularios y publicación/corrección de aprobadas quedan en fases 3/4. No se reabren aprobadas mediante el servicio de borradores.
- Pruebas: 83 comprobaciones MariaDB, con concurrencia entre procesos, migración repetida sobre datos poblados, privacidad, reemplazos, traslados, rol revocado y rollback de auditoría. Además 237 MVC, 33 liderazgo, 164 rutas, 354 legado/API y 20 configuración; sintaxis PHP y diff revisados. Las pruebas crean y eliminan únicamente sus bases aleatorias rrhh_e2e_*.
- MySQL estaba activo en 3306; el primer rechazo fue por permisos de sockets del entorno de ejecución y se resolvió ejecutando pruebas con autorización. No se modificó XAMPP.
- Actualicé inventario, plan, contexto y FASE_2_NOTAS_BASE_Y_PERMISOS.md. No se aplicó el parche a asistencia ni producción; no se cambiaron entorno, uploads ni datos del usuario.

## 2026-10-19 — Fechas y secuencia de parches SQL

- Creé ordenar-parches-sql desde origin/main b1b21ea, con la fase 1 de Notas ya integrada. No hice commit, push ni publicación.
- Renombré nueve parches al formato AAAA-MM-DD_patch_nombre_XXX.sql con secuencia global 001–009. Priorizo las fechas explícitas de implementación; para nacionalidades y los dos parches antiguos de permisos uso la última modificación conocida en Git, distinguiendo esa evidencia de una fecha de creación.
- Conservé exactamente el contenido SQL, comparando SHA-256 de cada archivo antes y después. La tabla de permisos precede al ALTER de permisos parciales; permisos de hoy, recuperaciones y tiempo excepcional conservan ese orden de dependencia.
- Actualicé referencias en controladores, servicios, pruebas y documentación, incluyendo las guías históricas. Añadí database/README.md como inventario de orden, fechas, fuentes y requisitos.
- Este cambio no ejecuta migraciones ni altera datos. Los parches ya aplicados no deben repetirse solo porque cambió su nombre. La fase 2 de Notas no se inició.
- Validé los nueve SHA-256, la existencia de todos los nombres referenciados y la ausencia de referencias a nombres anteriores. Pasaron 236 comprobaciones MVC, la revisión de sintaxis de los seis PHP afectados y git diff --check; no se ejecutaron parches contra bases de datos.

## 2026-10-19 — Notas, fase 1: áreas y líderes

- Creé notas-areas-y-lideres desde main a52b59c tras consultar origin/main. El commit 43f084e de correcciones menores seguía en su rama. Conservé la documentación pendiente; no hice commit, push, PR remoto ni publicación.
- Agregué gestión exclusiva ADMIN en admin/notas.php, entrada debajo de Reportes y catálogo de áreas. Hay integrantes elegibles, designación, reemplazo, retiro e historial con responsables, motivos y fechas; no se registran aún calificaciones.
- Añadí liderazgos_area, parche repetible y schema actualizado. Índice generado único por área vigente, bloqueos/transacciones, comprobación de formulario obsoleto y auditoría atómica de cambios. Cambiar área, dar de baja o desactivar un área con liderazgo requiere retiro explícito previo.
- Pruebas: 33 liderazgo SQLite, 42 MariaDB con concurrencia entre procesos, 236 MVC, 164 rutas, 353 retiro/API y 20 configuración. Un recorrido Edge/Playwright de liderazgo aprobado, con CSRF, candidato ajeno, reemplazo/retiro, roles denegados y capturas sin desborde a 320/1280 px; evidencia scratch/phase8/7c0c9c4327d0. CSS recompilado.
- Repetí el recorrido final con fixture nueva 290c39cd3a2f, incluyendo preparación automática del escenario y la prueba concurrente; aprobado. Se comprobó que MySQL seguía activo en 3306 al terminar. No se declara ejecutada la suite E2E completa.
- La suite general de negocio conserva cuatro fallos previos de permisos/interrupciones, reproducidos también en una copia intacta de main. No se atribuyen a esta fase ni se declaran resueltos.
- MySQL local no iniciaba: rescaté y restauré sus bases sobre copias con respaldo SHA-256 de 2633 archivos; comparé conteos/checksums de 1235 tablas antes y después y tras el arranque normal. XAMPP usa datos recuperados en D:, puerto 3306 y force_recovery=0; se verificó apagado/arranque y mysqlcheck. La carpeta original de C: sigue intacta. Detalles y límites en INCIDENTE_MYSQL_LOCAL_20261019.md.
- Apliqué el parche de liderazgo exclusivamente a la base local asistencia; seis tablas existentes conservaron sus checksums y no se asignaron líderes automáticamente. No se cambiaron .env, uploads ni producción.
- Documenté la entrega en FASE_1_NOTAS_AREAS_Y_LIDERES.md y actualicé plan/contexto. La fase 2 mantiene pendientes calendario exacto, excepciones de carga de jueves, frecuencia ya confirmada semanal y relación entre nota semanal y final.

## 2026-10-19 — Planificación de Notas y documentación de continuidad

- Incorporé la periodicidad confirmada posteriormente: evaluaciones semanales, carga los jueves e historial conservado durante toda la práctica. Ajusté las fases 2/3 para periodos, prevención de duplicados y consulta semanal. Límites de semana, excepciones al jueves y relación entre nota semanal y final siguen pendientes; no se asumió una fórmula ni se implementaron restricciones de calendario.

- Actualicé PLAN_IMPLEMENTACION_NOTAS.md: ADMIN registra/aprueba únicamente notas de líderes; los líderes registran/aprueban las de integrantes de su área sin intervención ADMIN. La consulta del evaluado depende de la aprobación del responsable correspondiente, sustituyendo la regla histórica de aprobación ADMIN universal.
- Organicé seis fases empezando por áreas y liderazgo, con ramas y criterios de término. Documenté diez evaluaciones de 1 a 7 con paso 0,5, nota final inicialmente manual y rúbrica pendiente, separando acuerdos de propuestas y cuestiones abiertas.
- Actualicé el resumen y la sección de evaluaciones de CONTEXTO_PROYECTO.md. El cierre de práctica conserva sus reglas independientes y SUPERVISOR no obtiene permisos de calificación solo por ese rol.
- Documenté el contenido de cada entrega Markdown: estado, decisiones, archivos, tablas, migraciones, reversión, pruebas y pendientes. No se realizó todavía la organización global de documentos.
- Esta entrega modifica documentación únicamente; no implementa la fase 1 ni modifica tablas, datos o permisos del sistema. Se revisaron coherencia, enlaces y diferencias; no correspondía ejecutar pruebas de aplicación.

## 2026-10-16 — Temas DaisyUI y anillo compatible

- Añadí selector de apariencia a Mis datos con los temas `light`, `dark`, `synthwave`, `aqua`, `coffee` y `black`; el cambio se aplica sin enviar el formulario de perfil y persiste en el navegador. El layout aplica esa preferencia antes de cargar CSS y el alcance queda limitado a cuentas PRACTICANTE.
- Apliqué variables DaisyUI a superficies, textos, bordes y color primario de las páginas del practicante. Mantengo intacta la identidad del logotipo y de la navegación institucional.
- Conservé radial-progress DaisyUI para estructura y accesibilidad, y dibujé el anillo con SVG para evitar depender de la máscara de gradiente que emplea DaisyUI 4.12.24.
- Recompilé CSS compartido y pasaron las revisiones de sintaxis PHP, configuración Tailwind y `git diff --check`. La suite `npm run test:ui` no pudo iniciar Edge/Playwright en este entorno Windows; no llegó a ejecutar sus assertions. La preferencia es por navegador y no requiere migración de base de datos.

## 2026-10-16 — Modo oscuro y progreso visual del marcaje

- Limité la acción SALIDA_PERMISO a permisos parciales aprobados, desde la hora de inicio hasta antes del fin autorizado. El control permanece oculto fuera de esa ventana y la salida de jornada conserva el flujo normal SALIDA; el servidor revalida la regla al guardar.
- Añadí modo oscuro con botón accesible en el encabezado del marcaje. Sigue el tema del sistema al primer acceso, recuerda la preferencia local y aplica colores a tarjetas, formularios, tablas, avisos y cabecera en esta pantalla.
- Sustituí el círculo de porcentaje por radial-progress DaisyUI con valor limitado entre 0 y 100 y atributos accesibles. Tailwind recompilado; no toqué permisos guardados ni marcas.
## 2026-10-16 — Alertas DaisyUI para marcajes y correcciones

- Reemplacé los avisos de éxito del marcaje del practicante y de acciones en Asistencia ADMIN/SUPERVISOR por `dui-alert dui-alert-success`, con anuncio accesible como estado. Los mensajes, validaciones y guardado de marcas no cambiaron.
- Añadí DaisyUI 4.12.24 al CSS compartido con prefijo `dui-`, tema claro y estilos base/utilidades desactivados. Así las clases nuevas no colisionan con el diseño existente; la hoja de login continúa independiente.
- Recompilé el CSS Tailwind del portal. La alerta permanece en el lugar del mensaje actual y se muestra después de una respuesta exitosa del servidor.
## 2026-10-15 — Ajuste visual y modo oscuro del login

- Dejé el fondo del login blanco y la tarjeta gris en tema claro; añadí superficies y textos legibles para modo oscuro. El cambio de tema alterna DaisyUI y las utilidades `dark:` de Tailwind, respeta la preferencia del sistema al primer acceso y guarda la elección localmente.
- Limité el selector y el tema `rrhh-dark` al login; no cambié otras vistas ni la lógica de autenticación. Recompilé el CSS independiente del login.

## 2026-10-15 — Correcciones operativas, fase 6: tiempo excepcional y compensación

- Confirmé main limpio en `b0dab99` y creé `tiempo-excepcional-y-compensacion`, sin prefijo codex. Cambios comentados en español. No hice commit, push, PR en GitHub ni despliegue.
- Agregué solicitud de tiempo efectivamente trabajado fuera del turno y aprobación de SUPERVISOR/ADMIN con motivo manual de la autorización informal del jefe. El crédito no aumenta las horas extra ni el acumulado al aprobar; habilita compensar entrada o salida con otra autorización previa.
- Separé crédito, recuperación, reserva y aplicación. Se descuentan reservas pendientes/aprobadas, se libera el rechazo y solo se aplica la porción autorizada no trabajada sobre marcas completas. Los minutos no usados vuelven al saldo si siguen vigentes; no se duplican intervalos de recuperación ni permisos/compensaciones del mismo día. No se permite autosupervisión.
- Implementé vencimiento a las 168 horas exactas desde la solicitud, con UTC persistido y presentación en America/Santiago. Aprobar no reinicia el plazo, el tramo debe terminar dentro de la ventana y el servidor impide canjear saldo vencido sin depender de cron. Conservo historial; volver a solicitar el mismo trabajo vencido no renueva el crédito.
- Agregué corte único por jornadas existentes y snapshot de importes extra aprobados. Cerré formulario y POST antiguos para impedir nuevas aprobaciones; las jornadas e importes históricos permanecen. Mantuve la validación obligatoria de sábados: no suman automáticamente bajo la política excepcional.
- Correcciones o anulación de fuente reabren crédito/canjes con auditoría y recalculan dependencias. La reapertura es conservadora: no restaura canjes históricos automáticamente ni permite autorizaciones retroactivas; la resolución de ajustes posteriores al vencimiento queda para revisar expresamente en fase 7. Horario y evidencia se vuelven a comprobar al autorizar y aplicar.
- Apliqué y repetí `database/2026-10-15_patch_tiempo_excepcional_008.sql` únicamente en asistencia local, después de exportar `scratch/fase6-tiempo/antes-fase6.sql`. Corte fijo: jornada 191, UTC 2026-10-15 15:06:45; dos jornadas con importes aprobados conservados. No modifiqué .env, uploads ni marcas habituales; el esquema y snapshot locales sí se ampliaron. La recuperación de XAMPP anterior se documenta separadamente en scratch/mysql-recovery.
- Pasaron 1015 comprobaciones PHP: 267 negocio, 217 MVC, 349 retiro/API, 162 rutas y 20 configuración. Incluyen rollback de auditoría, saldo parcial, expiración, identidad, canje aplicado, conservación histórica, reapertura y mantenimiento del control de sábados. SQLite no prueba concurrencia InnoDB.
- Pasaron quince E2E finales en `scratch/phase8/6a4760cfec74` con MariaDB/Edge/SMTP ficticios y sin errores PHP en sus logs. El nuevo recorrido solicita sesenta minutos, aprueba con motivo, reserva treinta, autoriza la entrada posterior y verifica saldo de treinta sin extras nuevas. Se corrigió la interferencia inicial de la fixture con fechas/totales de otros escenarios, sin modificar reglas productivas para hacerla pasar.
- Pasaron doce UI; inspeccioné la captura del componente en escritorio y generé vistas enfocadas a 320/1280 px. Tailwind recompilado con su aviso previo de Browserslist, sin dependencias nuevas. Comprobé la disponibilidad de validadores desde Asistencia sin abrir Permisos. Sintaxis y git diff --check correctos.
- Documenté reglas/corte/publicación en FASE_6_TIEMPO_EXCEPCIONAL.md y actualicé contexto y plan. Para Hostinger hay que respaldar, verificar motor/versión, ejecutar el parche y publicar backend/frontend coordinadamente. La fase 7 de integración final de saldos, informes, notificaciones, migraciones y publicación sigue pendiente.

## 2026-10-15 — Corrección de fase 6 tras prueba manual local

- Continué sobre `tiempo-excepcional-y-compensacion`, sincronizada con su remoto en `de39fbb`, sin crear otra rama, commit, push ni PR en GitHub.
- Consulté solo jornadas/horario/marcas y solicitudes del caso informado. La jornada nueva 192 tenía horario 10:00–18:00, salida 19:30 de hoy, estado VALIDA y ninguna solicitud excepcional. La hora de salida aún no había ocurrido según el servidor. El usuario confirmó el mensaje genérico del servicio excepcional: no existía cruce con el permiso normal.
- Corregí el ajuste que borraba la incidencia al apagar las extras antiguas. Resté jornada ordinaria, recuperaciones reservadas y tramos excepcionales realmente aprobados; el resto queda observado, con sus minutos pendientes, sin generar extras. Aprobación/rechazo excepcional recalcula el estado de la fuente en la misma transacción. Una aprobación parcial no cierra el resto del excedente.
- Separé mensajes de jornada histórica/anulada, horas inválidas, ventana empresarial y salida futura. También rechacé una salida futura aunque se intente pedir una porción anterior; no se acepta una jornada todavía no terminada. Conservé jornada, horas y motivo en el formulario rechazado y añadí explicación junto a los campos.
- Tras probar los cambios, recalculé exclusivamente el estado/observaciones de la jornada local 192, con respaldo previo en scratch/fase6-tiempo/jornada-192-antes-*.json y auditoría técnica CORREGIR_ASISTENCIA. Quedó OBSERVADA; verifiqué igualdad de marcas antes/después. No creé créditos/solicitudes ni cambié horario, .env o uploads. No hay migración nueva ni modificación de Hostinger.
- Pasaron 1025 comprobaciones PHP: 277 negocio, 217 MVC, 349 retiro/API, 162 rutas y 20 configuración. Pasaron quince E2E (scratch/phase8/180a7478eef3) y doce UI. La regresión reproduce salida futura, rechazo explícito sin guardar solicitud ni permiso normal, restauración de campos, jornada observada y autorización posterior de un tramo terminado; negocio comprueba también aprobación parcial. No se detectaron errores PHP en los logs E2E finales.
- Conservé la presentación y no añadí dependencias ni utilidades CSS. Cambios comentados en español y documentación actualizada; sintaxis y git diff --check comprobados. Las simulaciones de trabajo deben usar jornadas/horas ya ocurridas: una salida futura escrita manualmente no permite acreditar el tiempo anticipadamente.

## 2026-10-15 — Tolerancia de treinta minutos por extremo

- El responsable precisó que los márgenes se evalúan por separado: entrar veintinueve minutos antes y salir veintinueve después sigue válido sin sumar ni reflejar esos cincuenta y ocho minutos. Apliqué hasta treinta minutos inclusivos en cada extremo; superar el límite, incluso en un segundo, conserva la incidencia correspondiente. No se acumulan entrada anticipada y salida posterior.
- Ajusté únicamente la política nueva en TiempoExcepcionalService. Reemplacé las incidencias anteriores de atraso/salida anticipada por margen de treinta minutos. Las horas efectivas siguen calculándose dentro del turno y no se inventa tiempo no trabajado por tolerar una incidencia. Trabajo ordinario más compensación tiene tope en horas netas pactadas; recuperaciones expresamente validadas mantienen su crédito separado.
- Conservé aprobación excepcional, firmas, reservas y vencimiento. Las tolerancias no crean solicitudes ni saldo. Colación incompleta/ausente, marcas futuras, permisos y revisión obligatoria de sábados conservan sus controles. Las jornadas anteriores al corte y sus extras históricas no se recalculan con la nueva regla.
- Agregué tests/tolerancia-marcajes.php, integrado en business.php, con ejemplos 09:55–17:20, 09:55–17:50 y 09:31–17:29 para horario 10:00–17:00/una hora de colación; límites exactos y segundos, atrasos, salidas anticipadas, tope con colación reducida/ausente, incompletas, recuperación validada y sábado. Pasaron 328 comprobaciones de negocio, 217 MVC, 349 retiro/API, 162 rutas y 20 configuración: 1076 PHP en total.
- Amplié la fixture E2E con tres jornadas de días independientes y excepciones de horario ficticias, usando el cálculo real con MariaDB. Sus estados, seis horas reconocidas y ausencia de extras/incidencias toleradas se verifican también al abrir Asistencia como supervisor. No cambié datos habituales, .env, uploads, dependencias ni hosting. No requiere SQL nuevo, cambio visual ni recálculo masivo. No hice commit, push ni PR en GitHub.
- Actualicé contexto y FASE_6_TIEMPO_EXCEPCIONAL.md para documentar el nuevo tope, que sustituye la posibilidad anterior de reconocer por encima de lo planificado dentro del turno cuando no se marcaba colación.
- Validación final de esta ampliación: quince E2E aprobadas en scratch/phase8/5ae407d71dd2, incluidos los tres ejemplos del responsable contra MariaDB y su apertura real en Asistencia. Sin errores PHP en el log aislado. La primera ejecución solo falló por una expectativa de prueba con encabezado distinto al texto real; se corrigió la expectativa, sin cambiar la interfaz, y se repitió la suite completa. Se mantienen los doce UI de la corrección previa; esta ampliación no cambió la presentación. Sintaxis y git diff --check correctos.

## 2026-10-15 — Correcciones operativas, fase 7: validación final

- Partí de main limpio y sincronizado en 740aba2 y creé validacion-flujos-permisos-compensaciones sin prefijo codex. Cambios comentados en español; no hice commit, push, PR remoto ni despliegue.
- Integré informe individual con recuperación validada, compensación aplicada, excepcional aprobado por fecha correspondiente y saldo/pendientes a la consulta. Los importes desglosados no vuelven a sumarse al avance. Añadí autorización explícita en el servicio y fixture MVC antes de emitir HTML.
- Extendí el aviso lateral de Permisos con expedientes excepcionales/canjes pendientes y reabiertos, excluyendo la práctica propia del supervisor. Conservé el contador de permisos ordinarios y la identidad visual.
- Resolví la limitación histórica documentada en fase 6: fuente antes aprobada y canje antes autorizado pueden revalidarse expresamente tras corregir marcas, incluso vencido el saldo. Exigí evidencia vigente, motivo, autorización original anterior al tramo, presupuesto y exclusividad diaria; actualicé el destino en la transacción. Conservo vencimiento/fecha original del canje y audito la nueva intervención. Las solicitudes pasadas nunca autorizadas siguen bloqueadas; reducir trabajo fuente limita el crédito sin restaurar saldo vencido.
- Pasaron 1101 comprobaciones PHP: 351 negocio, 219 MVC, 349 retiro/API, 162 rutas y 20 configuración. Pasaron 16 E2E y 12 UI con MariaDB/Edge/SMTP ficticios, incluyendo informes y revalidación por formularios reales; evidencia scratch/phase8/3ad478d3388c. Sin errores PHP en logs aislados.
- tests/e2e-migrations.php repitió los parches operativos 4/5/6 sobre la base E2E poblada: diez tablas conservaron exactamente su contenido, política, históricos, marcas y canjes. No requiere SQL nuevo ni modificaciones de la base habitual.
- Pasaron 110 comprobaciones HTTP en Apache local. Generé paquete de revisión local scratch/releases/20261015-185040-740aba29-f4f310 y verifiqué 338 archivos. Pasaron 543 comprobaciones de publicación, incluyendo casos negativos. Corregí la prueba que exigía al CSS actual conservar un hash del inventario histórico de traslado; ahora exige existencia y correspondencia íntegra con el código actual/manifiesto, sin alterar el CSV histórico.
- Recompilé Tailwind; solo permanece el aviso previo de Browserslist. No cambié dependencias, .env, uploads ni datos habituales. El paquete declara árbol con cambios sin commit; regenerarlo desde main limpio después del merge antes de publicar.
- El responsable confirmó producción 11.8.9-MariaDB-log; documenté compatibilidad de sintaxis con fuentes oficiales, sin afirmar una ejecución en ese servidor. La prueba local usa 10.4.32. Concurrencia de varios dispositivos y verificación final del hosting no están certificadas por las pruebas secuenciales locales. Guía de publicación, preservación de archivos, orden de parches y recuperación en FASE_7_VALIDACION_OPERATIVA.md. Fase local completada; publicación manual pendiente del responsable.
- Repetición final tras conservar también el autor original del canje: 16 E2E aprobadas y nueva comprobación de idempotencia sobre diez tablas en 3ad478d3388c. Repetí únicamente el recorrido histórico con captura estabilizada del menú en 5b0d731224fe: aprobado. Inspeccioné el panel del informe a 320/1280 px; lectura completa sin desborde. Fuente original, autor y fecha se conservan; el actor de la nueva revisión queda en auditoría.

## 2026-10-15 — Schema completo, plan de notas y demostración daisyUI en login

- Creé schema-completo-y-login-daisyui desde main sincronizado 4064ca6. No hice commit, push, PR ni despliegue. Cambios de código/SQL/configuración comentados en español.
- Consolidé en schema.sql las ocho tablas que faltaban: permisos_practicante, feriados, recuperaciones_permiso, politica_tiempo_excepcional, extras_historicas, tiempos_excepcionales, compensaciones_tiempo y acompanamiento. El esquema contiene 35 tablas, incluye observación de permisos y política de recuperación y activa su corte propio en una base vacía. Conservé parches para bases existentes; no importé schema en la base habitual ni en producción. Los catálogos completos y el usuario administrador continúan en sus seeds; schema no sustituye esos datos iniciales.
- Actualicé seed_catalogos con SALIDA_PERMISO/REGRESO_PERMISO. La fixture E2E instala schema y catálogos sin parches antiguos de creación, verifica las ocho tablas antes de entrar a pantallas y ajusta solo su corte ficticio para representar una migración histórica. Repetir los tres parches sobre la base de prueba poblada conservó las diez tablas comprobadas.
- Guardé docs/historial/planes/PLAN_IMPLEMENTACION_NOTAS.md y una copia idéntica solicitada en el Escritorio, Plan implementación sistema de notas.md. Mantiene las decisiones de nota manual y visibilidad después de aprobación ADMIN; no implementé evaluaciones ni resolví escalas/estados aún pendientes.
- Instalé daisyUI 4.12.24 fijado en package/lock, compatible con Tailwind 3.4.17. Agregué tailwind.login.config.cjs, resources/css/login.css y public/assets/css/login.css. Solo login.view.php carga ese CSS y tema institucional; card/input/checkbox/btn usan prefijo dui-. Se mantienen PHP, campos, CSRF y acciones de autenticación. Compilación independiente del portal; npm run build:css compila ambas salidas. No añadí CDN ni JavaScript de framework.
- Pasaron 1101 comprobaciones PHP base, 16 E2E en scratch/phase8/9b93408f6c80 y 13 UI. La primera UI detectó un falso positivo del patrón Bootstrap al encontrar dui-btn-primary; ajusté la detección para exigir la clase completa y comprobé que sigue rechazando btn-primary. Repetición final de las 13 UI aprobada. Inspeccioné login-daisyui-320.png y -1280.png en scratch/phase6/screenshots; sin desbordes. Solo permanece el aviso previo Browserslist al compilar.
- Documenté uso y aprendizaje en LOGIN_DAISYUI.md: adaptar prefijos, conservar contratos PHP, recompilar, comprobar rutas content y distinguir CSS de comportamiento JS/negocio. README y branding actualizados. No cambié .env, uploads ni datos habituales. Plan de notas sigue en definición; no se avanzó a su implementación.

## Estado actual

El flujo publico base ya cuenta con:

- `login.php`
- `seleccionar-rol.php`
- `logout.php`
- `activar-cuenta.php`
- `recuperar-password.php`
- `restablecer-password.php`
- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `practicante/dashboard.php`
- `admin/crear-practicante.php`
- `supervisor/crear-practicante.php`
- `includes/practicante_registro.php`
- `practicante/mis-datos.php`
- `practicante/marcar-asistencia.php`
- `practicante/mi-historial.php`

La primera secuencia funcional definida en `docs/historial/referencias/CONTEXTO_PROYECTO.md` ya cuenta con sus archivos principales implementados hasta:

- `practicante/mi-historial.php`

La prueba integral final de la primera secuencia funcional en XAMPP ya fue validada por el usuario y queda considerada como punto 1 de Fase 2 completado.

La Fase 2.1 de gestion administrativa de horarios pactados para ADMIN/SUPERVISOR ya quedo implementada.

La Fase 2.2 de baja logica y detalle operativo de practicantes ya quedo implementada.

La Fase 2.3 de preparacion de indices para correcciones/anulaciones de asistencia ya quedo implementada y el parche de indices ya fue aplicado en la base local.

La Fase 2.4 de UI base para correcciones/anulaciones de asistencia ADMIN/SUPERVISOR ya quedo implementada.

La Fase 2.5 de gestion basica de usuarios SUPERVISOR desde ADMIN ya quedo implementada y ajustada para permitir asignar o quitar rol SUPERVISOR a usuarios existentes vinculados a practicante.

La Fase 2.6 de gestion base de areas por practica ya quedo implementada en `admin/practicantes.php` y `supervisor/practicantes.php`.

La Fase 2.7 de bloques de area por jornada ya quedo implementada en `admin/asistencia.php` y `supervisor/asistencia.php`.

La Fase 2.8 de excepciones de horario ya quedo implementada en la gestion ADMIN/SUPERVISOR de practicantes y en la lectura de horario diario para asistencia.

La Fase 2.9 de generacion/revision inicial de ausencias esperadas ya quedo implementada en `admin/asistencia.php` y `supervisor/asistencia.php`.

La Fase 2.10 de revision/aprobacion inicial de horas extra ya quedo implementada en `admin/asistencia.php` y `supervisor/asistencia.php`.

La Fase 2.11 de justificacion/resolucion inicial de ausencias esperadas ya quedo implementada en `admin/asistencia.php` y `supervisor/asistencia.php`.

La Fase 2.12 de edicion/anulacion logica de excepciones de horario ya quedo implementada en `admin/practicantes.php` y `supervisor/practicantes.php`.

La Fase 2.13 de reportes basicos de asistencia/avance ya quedo implementada en `admin/reportes.php` y `supervisor/reportes.php`.

La Fase 2.14 de auditoria visible ADMIN ya quedo implementada en `admin/auditoria.php`.

La Fase 2.15 de dashboard avanzado inicial ya quedo implementada en `admin/dashboard.php` y `supervisor/dashboard.php`.

La mejora visual y funcional del Dashboard Administrador ya quedo implementada en `admin/dashboard.php`, usando `assets/brand/logo_rojo_blanco.png` y metricas reales ampliadas desde `includes/dashboard_metricas.php`.

La iteracion visual premium del Dashboard Administrador ya quedo implementada con `assets/css/admin-dashboard.css`, `assets/js/admin-dashboard.js`, Bootstrap Icons y Chart.js acotado a la pantalla.

La Fase 2.16 de filtros interactivos reales del Dashboard ADMIN ya quedo implementada con parametros `GET`, catalogos reales y consultas PDO filtradas.

La Fase 2.17 de comparativos, logout lateral, sidebar responsive e iconografia/graficas mejoradas del Dashboard ADMIN ya quedo implementada.

La iteracion de densidad compacta y logout superior del Dashboard ADMIN ya quedo implementada.

La iteracion visual de `practicante/marcar-asistencia.php` ya quedo implementada con sidebar institucional estilo Dashboard Administrador y referencia visual `assets/img/registro_asistencia_2.png`.

La habilitacion visible del pre-registro desde Dashboard ADMIN y Panel SUPERVISOR ya quedo implementada.

La carga opcional de foto de perfil y documentos de practica desde `practicante/mis-datos.php` ya quedo implementada para el cierre de ficha y para carga posterior.

El destino inicial del rol PRACTICANTE ya quedo ajustado para entrar directamente a `practicante/marcar-asistencia.php` cuando la ficha esta completa, manteniendo la redireccion a `mis-datos.php?completar=1` si la ficha esta incompleta.

La pantalla `practicante/mis-datos.php` ya quedo transformada visualmente en `Mi Perfil` cuando la ficha esta completa, mostrando avatar de perfil, placeholder con iniciales, carga/cambio de foto y documentos activos con descarga protegida.

La pantalla `Mi Perfil` ya cuenta con acciones visibles junto a los selectores de archivo: `Subir foto`/`Cambiar foto` y `Subir documentos`, ademas de mantener `Guardar cambios` para el formulario completo.

El siguiente paso sugerido es aplicar el parche `database/2026-06-11_patch_documentos_practicante_002.sql`, probar en navegador el flujo de `Completar registro`/`Mis datos` con archivos validos e invalidos, y despues avanzar a revision administrativa de documentos, selector dependiente institucion-sede, exportacion XLSX o reportes avanzados.

## 2026-06-11 - Destino inicial PRACTICANTE a Registro de Asistencia

### Objetivo trabajado

Ajustar el destino inicial del rol PRACTICANTE para que, despues del login o seleccion de rol, ingrese primero al Registro de Asistencia.

### Archivos modificados

- `includes/auth.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se cambio `auth_dashboard_por_rol('PRACTICANTE')` desde `practicante/dashboard.php` a `practicante/marcar-asistencia.php`.
- Se mantuvo intacta la validacion de ficha incompleta: si falta completar ficha, el destino sigue siendo `practicante/mis-datos.php?completar=1`.
- No se modificaron vistas, estilos, textos del formulario ni navegacion interna.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\includes\auth.php`

## 2026-06-11 - Mi Perfil con avatar y documentos seguros

### Objetivo trabajado

Transformar la pantalla de datos del practicante en una experiencia `Mi Perfil`, manteniendo la ruta existente `practicante/mis-datos.php` para no romper redirecciones.

### Archivos modificados

- `practicante/mis-datos.php`
- `practicante/foto-perfil.php`
- `practicante/descargar-documento.php`
- `includes/auth.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/DOCUMENTO_BBDD.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Si existe foto activa, `Mi Perfil` la muestra como avatar circular usando un controlador protegido.
- Si no existe foto, se muestran iniciales del practicante como placeholder visual.
- La carga o cambio de foto queda dentro de la misma pantalla y conserva validacion JPG/JPEG/PNG hasta 5 MB.
- Los documentos activos se listan en una card con nombre, tamano, fecha y boton de descarga.
- La descarga de documentos usa `practicante/descargar-documento.php`, validando sesion, rol PRACTICANTE, documento activo y propiedad del practicante.
- No se muestran rutas internas del servidor ni enlaces directos a `uploads`.
- Se permitio que `foto-perfil.php` y `descargar-documento.php` funcionen durante el estado de ficha incompleta, sin abrir acceso a otros modulos.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\practicante\mis-datos.php`
- `C:\xampp\php\php.exe -l .\practicante\foto-perfil.php`
- `C:\xampp\php\php.exe -l .\practicante\descargar-documento.php`
- `C:\xampp\php\php.exe -l .\includes\auth.php`

## 2026-06-12 - Acciones explicitas para carga de archivos en Mi Perfil

### Objetivo trabajado

Hacer visible y claro que la foto de perfil y los documentos se suben desde la pantalla `Mi Perfil`, sin depender solo del boton general `Guardar cambios`.

### Archivos modificados

- `practicante/mis-datos.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego boton `Subir foto` o `Cambiar foto` junto al selector de imagen.
- Se agrego boton `Subir documentos` junto al selector de documentos.
- Se mantuvo el boton inferior `Guardar cambios` para guardar el formulario completo.
- Se agregaron validaciones de accion: si se presiona `Subir foto` sin seleccionar imagen, se muestra error claro; lo mismo para `Subir documentos`.
- Se agrego version al `src` del avatar para evitar que el navegador conserve una foto anterior en cache.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\practicante\mis-datos.php`

## 2026-06-11 - Documentos opcionales del practicante

### Objetivo trabajado

Permitir que el practicante suba una foto de perfil opcional y documentos opcionales de practica desde `practicante/mis-datos.php`, sin convertirlos en requisito para completar la ficha.

### Archivos modificados

- `practicante/mis-datos.php`
- `includes/auditoria.php`
- `database/schema.sql`
- `database/2026-06-11_patch_documentos_practicante_002.sql`
- `uploads/.htaccess`
- `docs/historial/referencias/DOCUMENTO_BBDD.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- La foto de perfil es opcional y acepta JPG, JPEG o PNG hasta 5 MB.
- Los documentos de practica son opcionales y aceptan PDF, Word o Excel hasta 5 MB cada uno.
- Se permite mantener hasta 5 documentos de practica activos por practicante.
- Los archivos pueden subirse durante `Completar registro` o posteriormente desde `Mis datos`.
- La funcion que define si la ficha esta completa no considera archivos.
- Se creo la tabla `documentos_practicante` para guardar metadata y permitir baja logica con `activo`.
- Los nombres fisicos se generan con `random_bytes()` y no reutilizan el nombre original.
- Se agrego `uploads/.htaccess` para bloquear listado y ejecucion de scripts en la carpeta de subida.
- Se agrego el evento funcional `SUBIR_DOCUMENTO_PRACTICANTE`.

### Errores pendientes

- Falta aplicar el patch SQL en la base local si esta ya existia antes de este cambio.
- Falta prueba manual real en navegador con archivos subidos por HTTP.
- Falta una pantalla administrativa para revisar, aprobar o anular documentos cargados.

### Pruebas recomendadas

- Aplicar `database/2026-06-11_patch_documentos_practicante_002.sql`.
- Validar sintaxis con `C:\xampp\php\php.exe -l .\practicante\mis-datos.php` y `C:\xampp\php\php.exe -l .\includes\auditoria.php`.
- Completar ficha sin archivos y confirmar que no se bloquea el cierre.
- Subir foto valida menor a 5 MB.
- Intentar foto mayor a 5 MB o formato no permitido.
- Subir hasta 5 documentos validos PDF/Word/Excel.
- Intentar subir un sexto documento y confirmar rechazo.
- Confirmar filas en `documentos_practicante` y auditoria `SUBIR_DOCUMENTO_PRACTICANTE`.

## 2026-05-28 - Configuracion general de la aplicacion

### Objetivo trabajado

Crear la configuracion centralizada inicial del proyecto en `config/app.php`, sin implementar login, base de datos ni vistas.

### Archivos modificados

- `config/app.php`
- `PROGRESO.md`

### Decisiones aplicadas

- Se definieron constantes generales de aplicacion: `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`, `APP_ROOT`, `CONFIG_PATH`, `INCLUDES_PATH`, `ASSETS_PATH`, `APP_BASE_PATH`, `APP_URL` y `ASSETS_URL`.
- Se calcularon rutas fisicas desde `dirname(__DIR__)` para evitar dependencias con `C:\xampp\htdocs\asistencia\`.
- Se detecto la ruta base web desde `$_SERVER['SCRIPT_NAME']`, permitiendo operar en `/asistencia/` durante desarrollo local y en raiz para un futuro subdominio.
- Se permitio sobrescribir `APP_ENV`, `APP_BASE_PATH` y `APP_URL` mediante variables de entorno para facilitar despliegues futuros.
- Se incluyeron comentarios profesionales en espaÃ±ol dentro del codigo de configuracion.

### Errores pendientes

- No hay errores funcionales pendientes en `config/app.php`.
- PHP no esta disponible directamente como comando `php` en PowerShell; para pruebas locales se debe usar `C:\xampp\php\php.exe` o agregar PHP al `PATH`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l config\app.php`.
- Se simulo URL local con `/asistencia/index.php`, obteniendo `APP_BASE_PATH=/asistencia`, `APP_URL=http://localhost/asistencia` y `ASSETS_URL=http://localhost/asistencia/assets`.
- Se simulo futuro subdominio con `/index.php` y HTTPS, obteniendo `APP_URL=https://portal.ofertasimperdibles.cl` y `ASSETS_URL=https://portal.ofertasimperdibles.cl/assets`.

### Siguiente paso sugerido

Crear la configuracion de base de datos en `config/database.php` usando PDO, manteniendo credenciales centralizadas y sin implementar aun pantallas ni flujo de login.

## 2026-05-28 - Alineacion de constantes oficiales en `config/app.php`

### Objetivo trabajado

Alinear la configuracion general con las constantes exigidas por `CONTEXTO_PROYECTO.md`.

### Archivos modificados

- `config/app.php`
- `PROGRESO.md`

### Decisiones aplicadas

- Se incorporaron las constantes oficiales `BASE_URL`, `BASE_PATH`, `SESSION_NAME`, `SESSION_TIMEOUT_MINUTES`, `TOKEN_EXPIRATION_HOURS`, `MAX_LOGIN_ATTEMPTS` y `LOGIN_LOCK_MINUTES`.
- Se mantuvieron `APP_URL` y `APP_BASE_PATH` como aliases de compatibilidad interna, derivados de `BASE_URL` y `BASE_PATH`.
- Se permitio configurar `BASE_URL` y `BASE_PATH` mediante variables de entorno, conservando compatibilidad temporal con `APP_URL` y `APP_BASE_PATH`.
- Se confirmo que `assets/brand` es una carpeta adicional valida para almacenar el logo del portal.
- Se confirmo que `config/database.php`, `config/mail.php` y el resto de archivos existentes son placeholders hasta que llegue su etapa de implementacion.
- Se definio que el archivo de progreso oficial es `PROGRESO.md` con mayusculas.
- Se dejo establecido que la futura exportacion XLSX usara una libreria PHP compatible con Hostinger.

### Errores pendientes

- No hay errores funcionales pendientes en `config/app.php`.
- PHP no esta disponible directamente como comando `php` en PowerShell; para pruebas locales se debe usar `C:\xampp\php\php.exe` o agregar PHP al `PATH`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l config\app.php`.
- Se simulo URL local con `/asistencia/index.php`, obteniendo `BASE_PATH=/asistencia`, `BASE_URL=http://localhost/asistencia` y `ASSETS_URL=http://localhost/asistencia/assets`.
- Se simulo futuro subdominio con `/index.php` y HTTPS, obteniendo `BASE_PATH` vacio, `BASE_URL=https://portal.ofertasimperdibles.cl` y `ASSETS_URL=https://portal.ofertasimperdibles.cl/assets`.
- Se verificaron constantes operativas de login: `SESSION_NAME=ASISTENCIA_PORTAL_SESION`, `MAX_LOGIN_ATTEMPTS=3` y `LOGIN_LOCK_MINUTES=15`.

### Siguiente paso sugerido

Implementar `config/database.php` con conexion PDO, charset `utf8mb4`, excepciones activas y credenciales centralizadas para XAMPP y Hostinger.

## 2026-05-29 - Implementacion de conexion PDO centralizada

### Objetivo trabajado

Implementar `config/database.php` como archivo central de conexion a base de datos, respetando el orden oficial del proyecto y sin avanzar aun a `config/mail.php`, `schema.sql` ni seeds.

### Archivos modificados

- `config/database.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo `config/app.php` sin cambios porque ya cumple las constantes oficiales y las pruebas de rutas local/subdominio.
- Se implementaron constantes de base de datos configurables por variables de entorno: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` y `DB_CHARSET`.
- Se definio `DB_CHARSET` con valor por defecto `utf8mb4`.
- Se creo la funcion `database_dsn()` para centralizar el DSN de PDO.
- Se creo la funcion `database_options()` con las opciones obligatorias: `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` y `PDO::ATTR_EMULATE_PREPARES => false`.
- Se creo la funcion `db()` con conexion lazy y reutilizable mediante variable estatica.
- No se abre conexion al incluir `config/database.php`, evitando fallos antes de crear/importar la base de datos.
- En modo local/debug se relanza la excepcion original de PDO; fuera de debug se entrega un error generico para no exponer detalles sensibles.

### Errores pendientes

- No se probo conexion real contra MySQL porque `database/schema.sql` aun no ha sido implementado/importado en esta etapa.
- PHP no esta disponible directamente como comando `php` en PowerShell; para pruebas locales se debe usar `C:\xampp\php\php.exe` o agregar PHP al `PATH`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l config\app.php`.
- Se simulo URL local con `config/app.php`, obteniendo `BASE_PATH=/asistencia`, `BASE_URL=http://localhost/asistencia` y `ASSETS_URL=http://localhost/asistencia/assets`.
- Se simulo futuro subdominio con `config/app.php`, obteniendo `BASE_PATH` vacio, `BASE_URL=https://portal.ofertasimperdibles.cl` y `ASSETS_URL=https://portal.ofertasimperdibles.cl/assets`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l config\database.php`.
- Se incluyo `config/database.php` desde CLI y se verifico que expone `DB_HOST=localhost`, `DB_NAME=asistencia`, `DB_CHARSET=utf8mb4` y DSN `mysql:host=localhost;port=3306;dbname=asistencia;charset=utf8mb4` sin intentar conectar automaticamente.

### Siguiente paso sugerido

Implementar `config/mail.php` con configuracion SMTP centralizada, modo debug dependiente de `APP_ENV` y compatibilidad con PHPMailer, sin enviar correos todavia.

## 2026-05-29 - Implementacion de configuracion SMTP centralizada

### Objetivo trabajado

Implementar `config/mail.php` como archivo central de configuracion SMTP para uso posterior con PHPMailer, sin enviar correos ni implementar flujos de activacion o recuperacion todavia.

### Archivos modificados

- `config/mail.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se centralizaron las constantes `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` y `MAIL_DEBUG`.
- Se usaron variables de entorno para host, puerto, usuario, contraseÃ±a, cifrado y remitente, evitando credenciales rigidas en el codigo.
- Se dejo `MAIL_FROM_NAME` con `APP_NAME` como valor por defecto para mantener consistencia institucional.
- Se definio `MAIL_DEBUG` segun `APP_ENV`, usando debug activo en local y desactivado fuera de local.
- Se agrego `mail_smtp_auth_enabled()` para activar autenticacion SMTP solo cuando existan usuario y contraseÃ±a configurados.
- No se envio correo ni se instancio PHPMailer en esta etapa; eso queda para `includes/mailer.php`.

### Errores pendientes

- No hay credenciales SMTP reales configuradas todavia.
- No se probo envio real de correos porque la etapa actual solo cubre configuracion.
- PHP no esta disponible directamente como comando `php` en PowerShell; para pruebas locales se debe usar `C:\xampp\php\php.exe` o agregar PHP al `PATH`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l config\mail.php`.
- Se incluyo `config/mail.php` desde CLI y se verificaron valores por defecto: `MAIL_PORT=587`, `MAIL_FROM_ADDRESS=no-reply@localhost`, `MAIL_FROM_NAME=Portal de Asistencia y Control de Practicantes`, `MAIL_DEBUG=2` y autenticacion SMTP desactivada por falta de credenciales.

### Siguiente paso sugerido

Preparar la etapa de `database/schema.sql`, indicando antes de modificar el archivo las tablas a crear, riesgos y pasos de prueba en XAMPP/phpMyAdmin, tal como exige `docs/historial/referencias/DOCUMENTO_BBDD.md`.

## 2026-05-29 - Implementacion de `database/schema.sql`

### Objetivo trabajado

Crear el schema inicial de base de datos con las 24 tablas definidas en `docs/historial/referencias/DOCUMENTO_BBDD.md`, respetando el orden oficial, relaciones, indices, claves foraneas, no borrado fisico y reglas criticas documentadas.

### Archivos modificados

- `database/schema.sql`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se uso `CREATE TABLE IF NOT EXISTS` y no se incluyeron instrucciones `DROP`, para evitar borrado destructivo.
- Se definio motor `InnoDB`, charset `utf8mb4` y collation `utf8mb4_unicode_ci` en todas las tablas.
- Se crearon las 24 tablas en el orden recomendado: catalogos, practicantes, practicas, horarios, usuarios, sesiones, tokens, asistencia, areas, evaluaciones y auditoria.
- Se usaron claves foraneas con `ON UPDATE CASCADE` y `ON DELETE RESTRICT`.
- Se mantuvo `practicantes.id_sede` como `NULL` permitido y se agregaron `institucion_otra` y `sede_otra`.
- Se uso `sedes.nombre_sede` y `sedes.codigo_sede`, sin `sedes.nombre` ni `sedes.direccion`.
- Se mantuvo `sesiones_usuario.id_rol_activo` como `NOT NULL`.
- Se agrego anulaciÃ³n logica granular en `marcas_asistencia` con `activo`, `anulada`, `motivo_anulacion`, `anulada_por` y `fecha_anulacion`.
- Se agrego `auditoria_cambios` con `id_usuario_actor`, `id_rol_contexto` e `id_sesion`.
- Se mantuvieron estados operativos como `VARCHAR` donde corresponde: `estado_cuenta`, `estado_jornada` y `estado_evaluacion`.

### Errores pendientes

- No se importo el schema en una base MySQL real desde esta etapa para evitar crear o alterar bases locales sin una accion explicita del usuario.
- Las reglas de negocio que no se pueden garantizar solo con FKs quedan pendientes para backend: prohibicion PRACTICANTE + ADMIN, conflicto de interes, secuencia de marcas, calculo de horas y permisos por rol.

### Pruebas realizadas

- Se verifico que `database/schema.sql` contiene 24 instrucciones `CREATE TABLE IF NOT EXISTS`.
- Se verifico presencia de campos criticos: `id_rol_activo INT NOT NULL`, `id_sede INT NULL`, `institucion_otra VARCHAR(150) NULL`, `sede_otra VARCHAR(150) NULL`, `nombre_sede`, `codigo_sede`, `motivo_anulacion`, `anulada_por`, `id_usuario_actor`, `id_rol_contexto` e `id_sesion`.
- Se confirmo disponibilidad del cliente MariaDB de XAMPP con `C:\xampp\mysql\bin\mysql.exe --version`.

### Siguiente paso sugerido

Implementar `database/seed_catalogos.sql` con regiones, instituciones, sedes normalizadas, carreras, tipos de practica, estados de practica, areas, tipos de marca y roles del sistema.

## 2026-05-29 - Implementacion de `database/seed_catalogos.sql`

### Objetivo trabajado

Crear los seeds iniciales de catalogos necesarios para operar la primera etapa del sistema, sin borrar datos existentes y respetando las reglas documentadas de instituciones, sedes, roles y asistencia.

### Archivos modificados

- `database/seed_catalogos.sql`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se usaron instrucciones `INSERT ... ON DUPLICATE KEY UPDATE` para permitir reejecutar el seed sin duplicar catalogos.
- Se cargaron regiones en el orden oficial, comenzando por RM.
- Se cargaron instituciones iniciales: Duoc UC, INACAP, ENAC y Otra.
- Se cargaron sedes normalizadas de Duoc UC, INACAP y ENAC segun `docs/historial/referencias/CONTEXTO_PROYECTO.md`.
- Se agrego sede fallback `No especificada` por institucion, incluyendo `Otra - No especificada`, sin usarla para texto libre operativo.
- Se cargaron carreras iniciales, tipos de practica, estados de practica, areas, tipos de marca y roles del sistema.
- Se mantuvo la regla de no guardar texto libre de usuarios en el catalogo `sedes`; para institucion Otra se usaran `practicantes.institucion_otra` y `practicantes.sede_otra`.

### Errores pendientes

- No se importo el seed en MySQL real desde esta etapa para evitar crear o alterar bases locales sin una accion explicita del usuario.
- La validacion completa del seed queda pendiente para cuando se importe despues de `database/schema.sql`.

### Pruebas realizadas

- Se verifico que el archivo contiene cargas para regiones, instituciones, sedes, carreras, tipos de practica, estados de practica, areas, tipos de marca y roles.
- Se verifico que existen 57 instrucciones de carga para `sedes`.
- Se verifico presencia de sedes fallback: `Duoc UC - No especificada`, `INACAP - No especificada`, `ENAC - No especificada` y `Otra - No especificada`.
- Se verifico presencia de catalogos criticos: `PENDIENTE_INICIO`, `ENTRADA_COLACION` y rol `PRACTICANTE`.

### Siguiente paso sugerido

Implementar `database/seed_admin.sql` con el usuario ADMIN inicial, rol ADMIN asociado y contraseÃ±a temporal hasheada con `password_hash()`, sin guardar contraseÃ±as en texto plano.

## 2026-05-29 - Implementacion de `database/seed_admin.sql`

### Objetivo trabajado

Crear el seed inicial del usuario administrador externo y asignarle el rol `ADMIN`, sin guardar contraseÃ±a en texto plano y sin duplicar registros al reejecutar el seed.

### Archivos modificados

- `database/seed_admin.sql`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo un usuario administrador inicial con email `admin@localhost.test`.
- Se definio `id_practicante NULL`, cumpliendo la regla de que todo ADMIN debe ser externo y no estar vinculado a practicante.
- Se dejo `estado_cuenta = ACTIVO`, `activo = 1`, `email_verificado = 1` y `debe_cambiar_password = 1`.
- Se uso un hash generado con `password_hash()` y no se guardo la contraseÃ±a temporal en texto plano.
- Se asigno el rol `ADMIN` mediante `usuarios_roles`, buscando el rol desde `roles_sistema`.
- Se uso `ON DUPLICATE KEY UPDATE` para permitir reejecutar el seed sin duplicar usuario ni rol.

### Errores pendientes

- Antes de producciÃ³n se debe reemplazar el email y hash temporal por credenciales institucionales reales.
- No se importo el seed en MySQL real desde esta etapa para evitar crear o alterar bases locales sin una accion explicita del usuario.

### Pruebas realizadas

- Se verifico que `database/seed_admin.sql` contiene `id_practicante NULL`, `estado_cuenta = ACTIVO`, `debe_cambiar_password`, `email_verificado` y asignacion del rol `ADMIN`.
- Se verifico que el archivo no contiene la contraseÃ±a temporal en texto plano; solo contiene el hash.
- Se verifico que la asignacion de rol depende de `roles_sistema.nombre = 'ADMIN'`.

### Siguiente paso sugerido

Implementar `includes/helpers.php` con helpers de rutas, redireccion, escape HTML, requests, sesiones basicas y utilidades comunes, sin implementar aun login completo.

## 2026-05-29 - Implementacion de `includes/helpers.php`

### Objetivo trabajado

Implementar helpers generales reutilizables para rutas, assets, redirecciones, escape HTML, lectura de requests y manejo basico de sesion segura, sin implementar login ni reglas de negocio criticas.

### Archivos modificados

- `includes/helpers.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego `e()` para escape HTML con `htmlspecialchars`.
- Se agrego `url()` para construir enlaces internos desde `BASE_URL`.
- Se agrego `asset()` para construir rutas de assets desde `ASSETS_URL`.
- Se agrego `redirect_to()` para redireccionar sin rutas rigidas.
- Se agregaron helpers de request: `is_post()`, `input()` y `query_param()`.
- Se agrego `is_https_request()` para detectar HTTPS.
- Se agrego `start_secure_session()` usando `SESSION_NAME`, cookie `HttpOnly`, `Secure` cuando corresponda y `SameSite=Lax`.
- Se agregaron helpers simples de sesion: `session_get()`, `session_put()` y `session_forget()`.
- No se implemento autenticacion, validacion de roles ni auditoria en esta etapa.

### Errores pendientes

- La regeneracion de ID de sesion despues de login queda pendiente para `includes/auth.php`.
- La validacion de permisos y conflicto de interes queda pendiente para `includes/auth.php` o helpers especializados.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\helpers.php`.
- Se probo `url('login.php')`, obteniendo `http://localhost/asistencia/login.php`.
- Se probo `asset('brand/logo.png')`, obteniendo `http://localhost/asistencia/assets/brand/logo.png`.
- Se probo `e('<b>x</b>')`, obteniendo `&lt;b&gt;x&lt;/b&gt;`.
- Se verifico que `is_post()` retorna falso por defecto en CLI.

### Siguiente paso sugerido

Implementar `includes/auditoria.php` para registrar eventos en `auditoria_cambios`, usando PDO y contemplando usuario actor, rol contexto, sesion, IP y user agent.

## 2026-05-29 - Implementacion de `includes/auditoria.php`

### Objetivo trabajado

Implementar el modulo base de auditoria para registrar eventos de seguridad y acciones funcionales en `auditoria_cambios`, contemplando usuario actor, rol de contexto, sesion, IP y user agent.

### Archivos modificados

- `includes/auditoria.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agregaron listas de eventos permitidos para seguridad y funcionales segun los documentos oficiales.
- Se incluyeron eventos criticos `ANULAR_MARCA_ASISTENCIA` e `INTENTO_ASIGNACION_ADMIN_RECHAZADO`.
- Se creo `auditoria_evento_permitido()` para validar eventos antes de insertar.
- Se creo `auditoria_registrar()` para insertar en `auditoria_cambios` con PDO.
- Se leen `id_usuario`, `id_rol_activo` e `id_sesion` desde una sesion PHP activa cuando existen, sin iniciar una sesion nueva solo para auditar.
- Se serializan `valores_anteriores` y `valores_nuevos` como texto JSON, sin depender de funciones JSON de MySQL/MariaDB.
- La auditoria captura errores y devuelve `false` para no romper el flujo principal si la base de datos no esta disponible.

### Errores pendientes

- No se probo insercion real en `auditoria_cambios` porque la base de datos aun no ha sido importada en MySQL desde esta sesion.
- La integracion efectiva de auditoria en login, tokens, roles y asistencia queda pendiente para las etapas correspondientes.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\auditoria.php`.
- Se verifico que `ANULAR_MARCA_ASISTENCIA` e `INTENTO_ASIGNACION_ADMIN_RECHAZADO` son eventos permitidos.
- Se verifico serializacion de valores con caracteres UTF-8.
- Se verifico que `auditoria_ip()` devuelve `CLI` cuando se ejecuta desde consola.
- Se verifico presencia de `id_usuario_actor`, `id_rol_contexto` e `id_sesion` en la insercion preparada.

### Siguiente paso sugerido

Implementar `includes/auth.php` con funciones base de sesion, autenticacion, roles activos, contexto activo y validaciones de permisos, sin construir todavia las vistas de login.

## 2026-05-29 - Implementacion de `includes/auth.php`

### Objetivo trabajado

Implementar la capa base de autenticacion, sesion operativa, rol activo, permisos minimos y validaciones de conflicto de interes, sin construir todavia `login.php`, `seleccionar-rol.php`, `logout.php` ni vistas.

### Archivos modificados

- `includes/auth.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se implementaron funciones para buscar usuario por email, obtener roles activos, validar contrasena con `password_verify()` y controlar bloqueo temporal por intentos fallidos.
- Se agrego flujo reutilizable de login mediante `auth_intentar_login()`, dejando la presentacion para la etapa de vistas.
- Se ejecuta `session_regenerate_id(true)` al preparar una sesion autenticada.
- Si el usuario tiene un solo rol, se define `id_rol_activo` inmediatamente y se crea registro en `sesiones_usuario`.
- Si el usuario tiene multiples roles, se guarda `usuario_pendiente_rol` y `roles_disponibles` en sesion PHP temporal, sin insertar todavia en `sesiones_usuario`.
- Se agrego `auth_establecer_rol_activo()` para crear la sesion operativa recien despues de seleccionar rol activo.
- Se guarda `session_id_hash` usando `hash('sha256', session_id())`, no el `session_id` plano.
- Se agregaron helpers de contexto: usuario actual, practicante actual, rol activo, requerir login y requerir rol.
- Se agrego cierre de sesion con actualizacion de `fecha_cierre`, `cerrada_por_logout` y `activa = 0`.
- Se agregaron reglas reutilizables para impedir rol `ADMIN` a usuarios con `id_practicante` y auditar `INTENTO_ASIGNACION_ADMIN_RECHAZADO`.
- Se agrego validacion de conflicto de interes para impedir autosupervision cuando el rol activo es `SUPERVISOR`.

### Errores pendientes

- No se probo login real contra base de datos porque el schema y los seeds aun no han sido importados en MySQL desde esta sesion.
- Las paginas `login.php`, `seleccionar-rol.php` y `logout.php` aun no estan implementadas; usaran estas funciones en etapas posteriores.
- En pruebas CLI, PHP no pudo escribir sesiones en `C:\xampp\tmp`; para pruebas de consola con sesion se uso temporalmente `-d session.save_path=.`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\auth.php`.
- Se verifico que `auth_puede_asignar_rol_admin(null)` retorna `true`.
- Se verifico que `auth_puede_asignar_rol_admin(10)` retorna `false`.
- Se verificaron rutas por rol: `admin/dashboard.php`, `supervisor/dashboard.php` y `practicante/dashboard.php`.
- Se probo la regla de conflicto de interes con rol activo `SUPERVISOR`: devuelve `true` cuando intenta actuar sobre su propio `id_practicante` y `false` sobre otro practicante.

### Siguiente paso sugerido

Implementar `includes/mailer.php` usando PHPMailer y la configuracion centralizada de `config/mail.php`, sin crear todavia pantallas de activacion o recuperacion.

## 2026-05-29 - Implementacion de `includes/mailer.php`

### Objetivo trabajado

Implementar el modulo central de envio de correos transaccionales con PHPMailer y SMTP, sin crear todavia pantallas ni controladores de activacion o recuperacion de contrasena.

### Archivos modificados

- `includes/mailer.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se uso `config/mail.php` como unica fuente de configuracion SMTP: host, puerto, usuario, contrasena, cifrado, remitente y debug.
- Se agrego carga flexible de PHPMailer compatible con XAMPP y Hostinger: primero `vendor/autoload.php` si existe, luego carga manual desde `vendor/phpmailer/phpmailer/src`.
- Se agrego `mailer_disponible()` para validar libreria y SMTP antes de intentar enviar.
- Se agrego `mailer_enviar()` con respuesta estructurada `ok/error`, evitando romper el flujo principal si SMTP aun no esta configurado.
- Se agrego plantilla HTML transaccional minima con texto alternativo automatico.
- Se agregaron helpers especificos para correo de activacion y recuperacion de contrasena, recibiendo enlaces ya generados por los futuros controladores.
- No se generan tokens en este archivo; esa responsabilidad queda para los flujos de activacion y recuperacion.
- No se envian contrasenas reales por correo.

### Errores pendientes

- PHPMailer aun no esta instalado en `vendor`, por lo que el envio real queda pendiente hasta instalar la libreria por Composer o subirla manualmente.
- No hay credenciales SMTP reales configuradas todavia.
- No se probo envio real de correos porque faltan libreria y servidor SMTP configurado.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\mailer.php`.
- Se verifico que, sin PHPMailer instalado, `mailer_cargar_phpmailer()` retorna `false`.
- Se verifico que, sin SMTP configurado, `mailer_disponible()` retorna `false`.
- Se verifico que `mailer_enviar()` devuelve `ok=false` y error controlado cuando PHPMailer o SMTP no estan configurados.
- Se probo conversion simple de HTML a texto plano para `AltBody`.

### Siguiente paso sugerido

Implementar `includes/reglas_asistencia.php` con validaciones base de secuencia de marcas, estados de jornada y anulaciÃ³n lÃ³gica de marcas, sin construir todavia la pantalla de marcado.

## 2026-05-29 - Implementacion de `includes/reglas_asistencia.php`

### Objetivo trabajado

Implementar las reglas base de asistencia para secuencia de marcas, estados de jornada, calculo de horas, flags operativos y anulaciÃ³n lÃ³gica granular de marcas, sin construir todavia la pantalla `practicante/marcar-asistencia.php`.

### Archivos modificados

- `includes/reglas_asistencia.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se definieron los tipos de marca oficiales: `ENTRADA`, `ENTRADA_COLACION`, `SALIDA_COLACION` y `SALIDA`.
- Se definieron estados de jornada validados por backend: `VALIDA`, `OBSERVADA`, `INCOMPLETA`, `ANULADA`, `CORREGIDA` y `PENDIENTE_REVISION`.
- Se implemento la secuencia permitida de marcas segun documentos oficiales.
- Se implemento calculo base de horas con entrada y salida final.
- Si falta entrada o salida final, la jornada queda `INCOMPLETA` y no suma horas.
- Si una jornada dura 6 horas o mas y no tiene colaciÃ³n completa, se descuenta 1 hora y queda `OBSERVADA`.
- Se agregaron flags base para atraso, salida anticipada, asistencia extraordinaria y horas extra.
- Se implementaron funciones PDO para obtener o crear jornada, registrar marca, recalcular jornada y anular marca.
- La anulaciÃ³n de marca usa `activo = 0`, `anulada = 1`, `motivo_anulacion`, `anulada_por` y `fecha_anulacion`, sin borrar fisicamente.
- La anulaciÃ³n granular audita el evento `ANULAR_MARCA_ASISTENCIA`.

### Errores pendientes

- No se probaron inserciones reales porque el schema y los seeds aun no han sido importados en MySQL desde esta sesion.
- El indice `UNIQUE(id_jornada, id_tipo_marca)` de `marcas_asistencia` puede impedir registrar una nueva marca del mismo tipo si existe una marca anterior anulada; esto debe revisarse antes de habilitar correcciones operativas completas.
- La deteccion de horario planificado queda preparada para recibir un arreglo de horario, pero aun falta integrar consultas reales a `horarios_practicante`, `detalle_horario_practicante` y `excepciones_horario_practicante`.
- La pantalla de marcado y los permisos por rol aun no estan implementados.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\reglas_asistencia.php`.
- Se verifico que sin marca previa solo se permite `ENTRADA`.
- Se verifico que despues de `ENTRADA` se permite `ENTRADA_COLACION` o `SALIDA`.
- Se verifico que despues de `SALIDA` no se permite ninguna marca adicional.
- Se calculo una jornada `ENTRADA` + `SALIDA` de 6,5 horas sin colaciÃ³n completa, quedando `OBSERVADA` con 5,5 horas calculadas.
- Se calculo una jornada con colaciÃ³n completa, quedando `VALIDA` con 8 horas calculadas.
- Se probo deteccion de atraso desde 10 minutos y salida anticipada usando horario esperado.
- Se verifico que `VALIDA` suma horas e `INCOMPLETA` no suma horas.

### Siguiente paso sugerido

Implementar `login.php` usando `includes/auth.php`, con formulario Bootstrap 5, validacion de credenciales, bloqueo por intentos fallidos y redireccion a `seleccionar-rol.php` cuando existan multiples roles.

## 2026-05-29 - Implementacion de `login.php`

### Objetivo trabajado

Implementar la pantalla y controlador liviano de inicio de sesion, consumiendo `includes/auth.php` y respetando el flujo oficial de login individual seguro.

### Archivos modificados

- `login.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `login.php` por una pantalla funcional con Bootstrap 5.
- Se reutilizo el logo desde `assets/brand/logo.png` usando `asset()` para evitar rutas quemadas.
- Se agrego formulario con email, contrasena y token CSRF de sesion.
- Se valida formato de email, contrasena obligatoria y token CSRF antes de consultar credenciales.
- Se llama a `auth_intentar_login()` para delegar validacion de usuario, password, bloqueo temporal, roles, sesion segura y redireccion.
- Si el usuario ya tiene sesion activa, se redirige al dashboard correspondiente al rol activo.
- Si el usuario tiene multiples roles, el redirect queda preparado hacia `seleccionar-rol.php` mediante la respuesta de `auth_intentar_login()`.
- Se captura cualquier error tecnico del login y se registra con `error_log`, mostrando un mensaje generico al usuario.
- No se implementaron todavia `seleccionar-rol.php`, `logout.php` ni dashboards reales.

### Errores pendientes

- No se probo login real contra base de datos porque el schema y los seeds aun no han sido importados en MySQL desde esta sesion.
- `seleccionar-rol.php` aun es placeholder, por lo que el flujo multirrol queda pendiente para el siguiente paso.
- Los dashboards de destino siguen siendo placeholders.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l login.php`.
- Se cargo `login.php` por CLI simulando `http://localhost/asistencia/login.php`, confirmando que genera HTML sin errores.
- Se verifico que las rutas del logo, action del formulario y recuperacion de contrasena se generan con `BASE_URL`.
- Se probo POST local sin base de datos con CSRF invalido, correo invalido y contrasena vacia, confirmando que muestra errores de validacion.
- Se elimino el archivo temporal de sesion creado durante la prueba CLI.

### Siguiente paso sugerido

Implementar `seleccionar-rol.php` para completar el flujo multirrol, usando la sesion PHP temporal `usuario_pendiente_rol` y creando la sesion operativa en `sesiones_usuario` recien despues de elegir rol activo.

## 2026-05-29 - Implementacion de `seleccionar-rol.php`

### Objetivo trabajado

Implementar el selector de rol activo para usuarios multirrol, completando el paso intermedio entre login exitoso y sesion operativa con `id_rol_activo`.

### Archivos modificados

- `seleccionar-rol.php`
- `includes/auth.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `seleccionar-rol.php` por una pantalla funcional con Bootstrap 5.
- Se usa la sesion PHP temporal con `usuario_pendiente_rol` y `roles_disponibles`.
- Si ya existe una sesion operativa con rol activo, se redirige al dashboard correspondiente.
- Si no existe usuario pendiente o roles disponibles, se redirige a `login.php`.
- Se agrego token CSRF especifico para la seleccion de rol.
- Se valida que el `id_rol` recibido exista dentro de `roles_disponibles`, evitando seleccionar roles no asignados.
- Se llama a `auth_establecer_rol_activo()` para definir `id_rol_activo`, `rol_activo` y crear la fila en `sesiones_usuario`.
- Se ajusto `auth_establecer_rol_activo()` para crear primero la sesion operativa en BBDD y solo despues marcar la sesion PHP como operativa, evitando estados parciales si falla la insercion.
- No se implemento todavia `logout.php` ni dashboards reales.

### Errores pendientes

- No se probo seleccion real de rol contra base de datos porque el schema y los seeds aun no han sido importados en MySQL desde esta sesion.
- Los dashboards de destino siguen siendo placeholders.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l seleccionar-rol.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\auth.php`.
- Se cargo `seleccionar-rol.php` por CLI con sesion temporal simulada y roles `ADMIN` y `SUPERVISOR`.
- Se verifico que la pagina renderiza roles disponibles y logo desde `BASE_URL`.
- Se probo POST local con CSRF invalido, confirmando que muestra error sin consultar base de datos.
- Se elimino el archivo temporal de sesion creado durante la prueba CLI.

### Siguiente paso sugerido

Implementar `logout.php` para cerrar la sesion operativa, actualizar `sesiones_usuario` con `fecha_cierre`, `cerrada_por_logout` y `activa = 0`, auditar `LOGOUT` y destruir la sesion PHP.

## 2026-05-29 - Implementacion de `logout.php`

### Objetivo trabajado

Implementar el cierre de sesion del sistema, incluyendo sesiones operativas con rol activo y sesiones PHP temporales usadas durante la seleccion de rol.

### Archivos modificados

- `logout.php`
- `includes/auth.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `logout.php` por un controlador breve que llama a `auth_logout()` y redirige a `login.php?logout=1`.
- Se mantiene la logica sensible de cierre dentro de `includes/auth.php`.
- `auth_logout()` registra evento `LOGOUT` mediante auditoria cuando sea posible.
- Si existe `id_sesion`, `auth_logout()` intenta actualizar `sesiones_usuario` con `fecha_cierre`, `cerrada_por_logout = 1` y `activa = 0`.
- Se reforzo `auth_logout()` para destruir la sesion PHP aunque falle la auditoria o la actualizacion de `sesiones_usuario` por no existir aun la base de datos local.
- El cierre tambien limpia sesiones temporales multirrol porque destruye completamente la sesion PHP.

### Errores pendientes

- No se probo actualizacion real de `sesiones_usuario` porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- En pruebas CLI sin base importada se registran errores esperados en `error_log`: base de datos `asistencia` no encontrada.
- `login.php` todavia no muestra un mensaje especifico para `logout=1`; solo redirige correctamente.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l logout.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\auth.php`.
- Se simulo logout desde una sesion temporal multirrol sin `id_sesion`; la sesion PHP fue destruida aunque auditoria no pudo escribirse por falta de base local.
- Se simulo logout desde una sesion operativa con `id_sesion`; el intento de actualizar `sesiones_usuario` fallo de forma controlada por falta de base local y la sesion PHP igualmente fue destruida.
- Se verifico que no quedaron archivos temporales `sess_*` en la raiz del proyecto despues de las pruebas CLI.

### Siguiente paso sugerido

Implementar `activar-cuenta.php` para validar tokens de activacion, permitir definir contrasena inicial y activar cuentas pendientes sin enviar ni guardar contrasenas en texto plano.

## 2026-05-29 - Implementacion de `activar-cuenta.php`

### Objetivo trabajado

Implementar la activacion inicial de cuentas mediante token, permitiendo definir contrasena inicial y activar usuarios en estado `PENDIENTE_ACTIVACION`.

### Archivos modificados

- `activar-cuenta.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `activar-cuenta.php` por una pantalla funcional con Bootstrap 5.
- Se valida el token recibido por URL comparando `hash('sha256', token)` contra `tokens_usuario.token_hash`.
- No se guarda ni se audita el token plano.
- Se exige token tipo `ACTIVACION_CUENTA`, no usado y no expirado.
- Se exige que la cuenta asociada este activa y en estado `PENDIENTE_ACTIVACION`.
- Se agrego token CSRF para proteger el formulario de definicion de contrasena.
- Se valida contrasena minima de 8 caracteres y confirmacion coincidente.
- Se guarda la contrasena con `password_hash()`.
- La activacion se aplica en transaccion: actualiza `usuarios_sistema`, marca el token como usado y registra `fecha_uso`.
- La cuenta queda `ACTIVO`, `email_verificado = 1`, `debe_cambiar_password = 0` y se limpian intentos/bloqueos temporales.
- Se auditan `PASSWORD_CAMBIADA` y `CUENTA_ACTIVADA` sin registrar contrasena ni token plano.

### Errores pendientes

- No se probo activacion real contra base de datos porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- Falta implementar el flujo que genera y envia el token de activacion desde el pre-registro de practicantes.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l activar-cuenta.php`.
- Se cargo `activar-cuenta.php` por CLI sin token, confirmando mensaje de enlace invalido sin consultar base de datos.
- Se cargo `activar-cuenta.php` por CLI con token simulado y sin base importada, confirmando mensaje generico de validacion sin exponer el error SQL al usuario.
- Se verifico que la ruta del logo se genera con `BASE_URL`.
- Se verifico en el codigo que se usa `token_hash`, `password_hash()`, `PASSWORD_CAMBIADA` y `CUENTA_ACTIVADA`.
- Se eliminaron los archivos temporales de sesion creados durante las pruebas CLI.

### Siguiente paso sugerido

Implementar `recuperar-password.php` para solicitar recuperacion con respuesta generica, generar token `RECUPERACION_PASSWORD`, guardar solo hash y preparar envio por `includes/mailer.php`.



## 2026-05-29 - Implementacion de `recuperar-password.php`

### Objetivo trabajado

Implementar la solicitud de recuperacion de contrasena con respuesta generica, generacion de token temporal, guardado de hash y envio mediante `includes/mailer.php`.

### Archivos modificados

- `recuperar-password.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `recuperar-password.php` por una pantalla funcional con Bootstrap 5.
- Se agrego respuesta publica generica: si el correo existe, se enviaron instrucciones.
- Se valida email y token CSRF antes de procesar la solicitud.
- No se revela al usuario si el email existe o no.
- Si el usuario existe, esta activo y tiene `estado_cuenta = ACTIVO`, se genera token `RECUPERACION_PASSWORD`.
- El token plano se usa solo para construir el enlace enviado por correo; en BBDD se guarda `hash('sha256', token)`.
- Se invalidan tokens previos de recuperacion no usados antes de crear uno nuevo.
- La expiracion usa `TOKEN_EXPIRATION_HOURS`.
- Se auditan `RECUPERACION_SOLICITADA` y `TOKEN_GENERADO` sin registrar el token plano.
- Se intenta enviar el enlace con `mailer_enviar_recuperacion_password()`.
- Si PHPMailer o SMTP no estan configurados, el error se registra internamente y la respuesta al usuario sigue siendo generica.

### Errores pendientes

- No se probo generacion real de token porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- No se probo envio real de correo porque PHPMailer y SMTP aun no estan configurados.
- `restablecer-password.php` ya fue implementado; falta probar el flujo real completo contra base importada y token generado por `recuperar-password.php`.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l recuperar-password.php`.
- Se cargo `recuperar-password.php` por CLI en modo GET, confirmando render de pantalla, logo y enlace de retorno al login.
- Se probo POST local con CSRF invalido y correo invalido, confirmando errores de validacion.
- Se probo POST local con email valido y base no importada, confirmando respuesta generica sin exponer el error SQL al usuario.
- Se verifico en el codigo que se usa `RECUPERACION_PASSWORD`, `token_hash`, `RECUPERACION_SOLICITADA`, `TOKEN_GENERADO` y `mailer_enviar_recuperacion_password()`.
- Se eliminaron los archivos temporales de sesion creados durante las pruebas CLI.

### Siguiente paso sugerido

Implementar `restablecer-password.php` para validar token `RECUPERACION_PASSWORD`, permitir definir nueva contrasena, guardar con `password_hash()` e invalidar el token usado.

## 2026-05-29 - Guia de pruebas locales en XAMPP

### Objetivo trabajado

Documentar como probar el estado actual del proyecto en XAMPP, diferenciando pruebas disponibles sin base de datos y pruebas completas que requieren importar `schema.sql` y seeds.

### Archivos modificados

- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo una guia especifica para pruebas locales con URL oficial `http://localhost/asistencia/`.
- Se separaron pruebas sin base de datos: sintaxis PHP, carga de pantallas, rutas, logo, sesiones y validaciones basicas.
- Se documentaron pasos para crear la base `asistencia` en phpMyAdmin o por PowerShell.
- Se documentaron importaciones de `database/schema.sql`, `database/seed_catalogos.sql` y `database/seed_admin.sql`.
- Se explico que `seed_admin.sql` no guarda contrasena en texto plano y que para prueba local se debe generar un hash propio con `password_hash()`.
- Se documentaron verificaciones esperadas en `sesiones_usuario` y `auditoria_cambios` despues de login/logout real.
- Se dejaron explicitados los flujos aun incompletos y pendientes tecnicos conocidos.

### Errores pendientes

- La guia no ejecuta importaciones automaticamente; el usuario debe aplicarlas desde phpMyAdmin o PowerShell cuando quiera probar base real.
- Login real requiere definir una contrasena local conocida para `admin@localhost.test`.
- La activacion real requiere que exista un token `ACTIVACION_CUENTA` generado por un flujo posterior.

### Pruebas realizadas

- Se verifico existencia de `database/schema.sql`, `database/seed_catalogos.sql` y `database/seed_admin.sql`.
- Se verifico disponibilidad local de `C:\xampp\php\php.exe`.
- Se verifico disponibilidad local de `C:\xampp\mysql\bin\mysql.exe`.
- Se reviso que la guia contenga instrucciones para `login.php`, `logout.php`, `sesiones_usuario`, `auditoria_cambios`, PHPMailer y el pendiente del indice `UNIQUE(id_jornada, id_tipo_marca)`.

### Siguiente paso sugerido

Implementar `admin/crear-practicante.php` y `supervisor/crear-practicante.php` como formularios base de pre-registro, respetando instituciones/sedes normalizadas y campos manuales para institucion Otra.





## 2026-05-29 - Implementacion de `restablecer-password.php`

### Objetivo trabajado

Implementar el restablecimiento de contrasena mediante token `RECUPERACION_PASSWORD`, permitiendo definir una nueva contrasena, guardar hash e invalidar el token usado.

### Archivos modificados

- `restablecer-password.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el placeholder de `restablecer-password.php` por una pantalla funcional con Bootstrap 5.
- Se valida el token recibido por URL comparando `hash('sha256', token)` contra `tokens_usuario.token_hash`.
- No se guarda ni se audita el token plano.
- Se exige token tipo `RECUPERACION_PASSWORD`, no usado y no expirado.
- Se exige que la cuenta asociada este activa y en estado `ACTIVO`.
- Se agrego token CSRF para proteger el formulario.
- Se valida contrasena minima de 8 caracteres y confirmacion coincidente.
- Se guarda la nueva contrasena con `password_hash()`.
- La actualizacion se aplica en transaccion: actualiza `usuarios_sistema`, marca el token como usado y registra `fecha_uso`.
- Se invalidan otros tokens `RECUPERACION_PASSWORD` pendientes del mismo usuario.
- Se limpian intentos fallidos y bloqueo temporal al restablecer la contrasena.
- Se audita `PASSWORD_CAMBIADA` sin registrar contrasena ni token plano.

### Errores pendientes

- No se probo restablecimiento real contra base de datos porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- La generacion real del token depende de `recuperar-password.php` y del envio SMTP cuando PHPMailer quede configurado.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l restablecer-password.php`.
- Se cargo `restablecer-password.php` por CLI sin token, confirmando mensaje de enlace invalido sin consultar base de datos.
- Se cargo `restablecer-password.php` por CLI con token simulado y sin base importada, confirmando mensaje generico de validacion sin exponer el error SQL al usuario.
- Se verifico que la ruta del logo se genera con `BASE_URL`.
- Se verifico que `restablecer_password_hash_token('abc')` coincide con `hash('sha256', 'abc')`.
- Se probaron validaciones de contrasena corta, confirmacion no coincidente y contrasena valida.
- Se verifico en el codigo que se usa `RECUPERACION_PASSWORD`, `token_hash`, `password_hash()`, `fecha_uso` y `PASSWORD_CAMBIADA`.
- Se eliminaron los archivos temporales de sesion creados durante las pruebas CLI.

### Siguiente paso sugerido

Implementar `admin/crear-practicante.php` y `supervisor/crear-practicante.php` como formularios base de pre-registro, respetando instituciones/sedes normalizadas y campos manuales para institucion Otra.

## 2026-05-29 - Actualizacion de estado en `PROGRESO.md`

### Objetivo trabajado

Actualizar `docs/historial/PROGRESO.md` para reflejar que `restablecer-password.php` ya fue implementado y que el siguiente paso real fue crear dashboards base por rol.

### Archivos modificados

- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego un bloque `Estado actual` al inicio del documento.
- Se listaron los archivos publicos base ya implementados: login, selector de rol, logout, activacion, recuperacion y restablecimiento de contrasena.
- Se dejo registrado que los dashboards base eran el siguiente paso inmediato en ese momento.
- Se corrigio el siguiente paso de la entrada de `activar-cuenta.php` para apuntar a `recuperar-password.php`.
- Se actualizo el pendiente de `recuperar-password.php` indicando que `restablecer-password.php` ya esta implementado y falta probar el flujo real con base importada.
- Se actualizo la entrada de guia XAMPP para apuntar a dashboards base como siguiente paso.

### Errores pendientes

- Las entradas historicas mantienen algunos "siguiente paso sugerido" que eran correctos en su momento.
- Sigue pendiente probar flujos reales contra MySQL importado.

### Pruebas realizadas

- Se reviso el encabezado `Estado actual`.
- Se verifico que la entrada final de `restablecer-password.php` existe.
- Se verifico que el siguiente paso actual queda registrado como dashboards base por rol.

### Siguiente paso sugerido

Implementar `admin/crear-practicante.php` y `supervisor/crear-practicante.php` como formularios base de pre-registro, respetando instituciones/sedes normalizadas y campos manuales para institucion Otra.

## 2026-05-29 - Implementacion de dashboards base por rol

### Objetivo trabajado

Implementar dashboards base para `ADMIN`, `SUPERVISOR` y `PRACTICANTE`, protegidos por rol activo y preparados como puntos de entrada a los modulos posteriores.

### Archivos modificados

- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `practicante/dashboard.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazaron los placeholders de los tres dashboards por pantallas funcionales con Bootstrap 5.
- Cada dashboard usa `auth_requiere_rol()` para validar el rol activo en backend.
- Se reutiliza el logo desde `assets/brand/logo.png` mediante `asset()`.
- Se agrego enlace de cierre de sesion usando `url('logout.php')`.
- El dashboard ADMIN enlaza a gestion de practicantes, usuarios y creacion de practicante.
- El dashboard SUPERVISOR enlaza a practicantes y creacion de practicante, sin acciones reservadas al ADMIN.
- El dashboard PRACTICANTE enlaza a mis datos, marcar asistencia e historial.
- Las tarjetas de funciones aun no implementadas quedan marcadas como pendientes, sin simular metricas reales.

### Errores pendientes

- No se probaron dashboards con login real porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- Los modulos enlazados siguen siendo placeholders o estan pendientes de implementacion.
- Bootstrap se carga desde CDN; si se requiere funcionamiento sin internet local, habra que agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l admin\dashboard.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l supervisor\dashboard.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\dashboard.php`.
- Se cargo `admin/dashboard.php` por CLI con sesion simulada de rol `ADMIN`.
- Se cargo `supervisor/dashboard.php` por CLI con sesion simulada de rol `SUPERVISOR`.
- Se cargo `practicante/dashboard.php` por CLI con sesion simulada de rol `PRACTICANTE`.
- Se verifico que los enlaces de logout y modulos internos se generan con `BASE_URL`.
- Se eliminaron los archivos temporales de sesion creados durante las pruebas CLI.

### Siguiente paso sugerido

Implementar `admin/crear-practicante.php` y `supervisor/crear-practicante.php` como formularios base de pre-registro, respetando instituciones/sedes normalizadas y campos manuales para institucion Otra.

## 2026-05-29 - Implementacion de pre-registro de practicantes

### Objetivo trabajado

Implementar los formularios base de pre-registro de practicantes para `ADMIN` y `SUPERVISOR`, respetando las reglas oficiales de instituciones, sedes, usuario asociado, token de activacion y auditoria.

### Archivos modificados

- `includes/practicante_registro.php`
- `admin/crear-practicante.php`
- `supervisor/crear-practicante.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/practicante_registro.php` como modulo compartido para evitar duplicar validaciones sensibles entre ADMIN y SUPERVISOR.
- `admin/crear-practicante.php` queda protegido para rol `ADMIN`.
- `supervisor/crear-practicante.php` queda protegido para rol `SUPERVISOR`.
- El formulario carga catalogos normalizados desde BBDD: regiones, instituciones, sedes, carreras y tipos de practica.
- Para Duoc UC, INACAP y ENAC se exige `id_sede` desde catalogo y se limpian `institucion_otra` y `sede_otra`.
- Para institucion Otra se exige `institucion_otra` y `sede_otra`, y `id_sede` queda `NULL`.
- La persistencia crea `practicantes`, `practicas`, `usuarios_sistema`, `usuarios_roles` y `tokens_usuario` dentro del flujo de pre-registro.
- La cuenta del practicante queda `PENDIENTE_ACTIVACION`, con rol `PRACTICANTE` y token `ACTIVACION_CUENTA`.
- Se guarda solo `token_hash`; el token plano se usa una vez para correo o enlace manual si SMTP no esta disponible.
- Se auditan `CREAR_PRACTICANTE`, `CREACION_USUARIO` y `TOKEN_GENERADO`.
- Se agregaron validaciones de RUT basico, correo, fechas, horas, catalogos, sede perteneciente a institucion y unicidad de RUT/correo.

### Errores pendientes

- No se probo guardado real porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- No se probo envio real de activacion porque PHPMailer/SMTP no estan configurados.
- Si SMTP falla, el formulario muestra enlace manual de activacion, como indica el flujo oficial.
- Bootstrap sigue cargando desde CDN; si se requiere uso sin internet, queda pendiente agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\practicante_registro.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l admin\crear-practicante.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l supervisor\crear-practicante.php`.
- Se cargo `admin/crear-practicante.php` por CLI con sesion simulada de rol `ADMIN`, confirmando error controlado cuando no existe la base local.
- Se cargo `supervisor/crear-practicante.php` por CLI con sesion simulada de rol `SUPERVISOR`, confirmando error controlado cuando no existe la base local.
- Se probo validacion aislada de institucion Otra, confirmando que `id_sede` queda `NULL` y los campos manuales son aceptados.
- Se verifico que no quedaron archivos temporales `sess_*` despues de las pruebas CLI.

### Siguiente paso sugerido

Implementar `practicante/mis-datos.php` para que el practicante pueda editar solo sus datos personales permitidos, sin cambiar practica, institucion, carrera, tipo de practica ni horas requeridas.

## 2026-05-29 - Implementacion de `practicante/mis-datos.php`

### Objetivo trabajado

Implementar la pantalla de datos propios para el rol `PRACTICANTE`, permitiendo editar solo campos personales autorizados y dejando en solo lectura los datos de practica e institucion.

### Archivos modificados

- `practicante/mis-datos.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- La pantalla queda protegida con `auth_requiere_rol('PRACTICANTE')`.
- Se valida que la sesion tenga `id_practicante` vinculado antes de consultar datos.
- Se muestran en solo lectura RUT, nombre, correo, institucion, sede, carrera, tipo de practica, estado, horas requeridas e inicio de practica.
- Se permite editar solo telefono, nacionalidad, direccion, numero, departamento, comuna y region.
- No se permite modificar practica, institucion, sede, carrera, tipo de practica, horas requeridas ni correo de acceso desde esta pantalla.
- Se usa CSRF propio para proteger el formulario.
- Se valida region desde catalogo activo y campos obligatorios de domicilio/contacto.
- Se audita la actualizacion con `EDICION_DATOS_PROPIOS_PRACTICANTE`.
- Si la base local no existe o no esta importada, se muestra un error controlado al usuario.
- Se agrego validacion de largo compatible con hosting compartido, usando `mb_strlen()` solo si esta disponible.

### Errores pendientes

- No se probo actualizacion real porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- La edicion de correo de acceso queda fuera de esta pantalla para evitar inconsistencias entre `practicantes.email` y `usuarios_sistema.email`; deberia tratarse en un flujo especifico con verificacion.
- Bootstrap sigue cargando desde CDN; si se requiere uso sin internet, queda pendiente agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\mis-datos.php`.
- Se cargo `practicante/mis-datos.php` por CLI con sesion simulada de rol `PRACTICANTE`, confirmando error controlado cuando no existe la base local.
- Se volvio a validar sintaxis despues del ajuste de compatibilidad de largo de texto.

### Siguiente paso sugerido

Implementar `practicante/marcar-asistencia.php` usando `includes/reglas_asistencia.php`, respetando secuencia de marcas, jornada activa y calculo basico de estado.

## 2026-05-29 - Implementacion de `practicante/marcar-asistencia.php`

### Objetivo trabajado

Implementar la pantalla de marcacion de asistencia para el rol `PRACTICANTE`, usando las reglas centralizadas de secuencia, jornada y recalculo ya definidas en `includes/reglas_asistencia.php`.

### Archivos modificados

- `practicante/marcar-asistencia.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- La pantalla queda protegida con `auth_requiere_rol('PRACTICANTE')`.
- Se valida que la sesion tenga `id_practicante` vinculado.
- Se busca la practica activa del practicante con estado `PENDIENTE_INICIO` o `EN_PROGRESO`.
- Se impide marcar si la fecha actual esta fuera del rango de la practica.
- Se carga o crea la jornada diaria solo al registrar una marca, evitando crear jornadas vacias en GET.
- Se calculan marcas permitidas segun la secuencia oficial: `ENTRADA`, `ENTRADA_COLACION`, `SALIDA_COLACION`, `SALIDA`.
- La persistencia de marcas usa `asistencia_obtener_o_crear_jornada()` y `asistencia_registrar_marca()`.
- La jornada se recalcula con las reglas ya existentes despues de cada marca.
- Se protege el formulario con CSRF propio.
- Se muestra resumen de jornada, estado, horas calculadas, observacion y marcas vigentes.
- Si la base local no existe o no esta importada, se muestra un error controlado al usuario.

### Errores pendientes

- No se probo registro real de marcas porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- El indice `UNIQUE(id_jornada, id_tipo_marca)` en `marcas_asistencia` impide repetir un mismo tipo de marca incluso si una marca fue anulada; este pendiente ya estaba documentado para una futura revision de schema.
- No se implementa asignacion de horario ni validacion avanzada contra horario planificado en esta pantalla; por ahora opera con las reglas base existentes.
- Bootstrap sigue cargando desde CDN; si se requiere uso sin internet, queda pendiente agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\marcar-asistencia.php`.
- Se cargo `practicante/marcar-asistencia.php` por CLI con sesion simulada de rol `PRACTICANTE`, confirmando error controlado cuando no existe la base local.
- Se probaron reglas puras de secuencia con `asistencia_siguientes_marcas_permitidas()` y calculo base de jornada con `asistencia_calcular_resumen_jornada()`.
- Se verifico que no quedaron archivos temporales `sess_*` despues de las pruebas CLI.

### Siguiente paso sugerido

Implementar `practicante/mi-historial.php` para que el practicante consulte sus jornadas, marcas vigentes, horas realizadas y estado de asistencia.

## 2026-05-29 - Implementacion de `practicante/mi-historial.php`

### Objetivo trabajado

Implementar la pantalla de historial de asistencia para el rol `PRACTICANTE`, mostrando practicas, jornadas, marcas vigentes, horas realizadas, horas restantes y porcentaje de avance.

### Archivos modificados

- `practicante/mi-historial.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- La pantalla queda protegida con `auth_requiere_rol('PRACTICANTE')`.
- Se valida que la sesion tenga `id_practicante` vinculado.
- Se listan las practicas activas o historicas del practicante autenticado.
- Se permite filtrar por practica y rango de fechas mediante parametros GET.
- Se calcula resumen global de la practica seleccionada: jornadas, horas realizadas, horas restantes y porcentaje de avance.
- Solo suman horas las jornadas con estados `VALIDA`, `OBSERVADA` o `CORREGIDA`, respetando las reglas de `includes/reglas_asistencia.php`.
- Se muestran jornadas del periodo con estado, entrada, salida, horas, marcas vigentes y observacion.
- Se consultan solo marcas vigentes (`activo = 1` y `anulada = 0`), manteniendo la trazabilidad de anulaciones en la base sin exponerlas como vigentes.
- La pantalla es de solo lectura; no modifica asistencia, practicas ni marcas.
- Si la base local no existe o no esta importada, se muestra un error controlado al usuario.

### Errores pendientes

- No se probo lectura real de historial porque la base `asistencia` aun no existe/importa en MySQL desde esta sesion.
- Las marcas anuladas no se muestran en esta vista de practicante; quedan conservadas para auditoria y vistas administrativas futuras.
- El indice `UNIQUE(id_jornada, id_tipo_marca)` en `marcas_asistencia` sigue pendiente de revision para permitir anulacion y remarcacion granular.
- Bootstrap sigue cargando desde CDN; si se requiere uso sin internet, queda pendiente agregar Bootstrap local en `assets`.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\mi-historial.php`.
- Se cargo `practicante/mi-historial.php` por CLI con sesion simulada de rol `PRACTICANTE`, confirmando error controlado cuando no existe la base local.
- Se verifico que la pantalla usa `BASE_URL` mediante `url()` y assets mediante `asset()`.

### Siguiente paso sugerido

Realizar prueba integral en XAMPP con la base `asistencia` importada, revisar `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md` si requiere actualizarse con los nuevos modulos y resolver el pendiente del indice de marcas antes de flujos avanzados de correccion/anulacion.

## 2026-05-29 - Actualizacion de `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`

### Objetivo trabajado

Actualizar la guia de pruebas locales para reflejar el estado real del proyecto despues de implementar la primera secuencia funcional hasta `practicante/mi-historial.php`.

### Archivos modificados

- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazaron referencias obsoletas que indicaban dashboards, recuperacion o restablecimiento como pendientes.
- Se agregaron comandos de validacion de sintaxis para todos los archivos implementados en la secuencia inicial.
- Se documentaron pruebas con base importada para login ADMIN, sesion operativa, pre-registro, activacion, login PRACTICANTE, mis datos, marcacion de asistencia e historial.
- Se agrego una seccion especifica para recuperacion y restablecimiento de contrasena.
- Se separaron flujos no cubiertos en esta etapa: `admin/practicantes.php`, `admin/usuarios.php`, `supervisor/practicantes.php`, correcciones/anulaciones administrativas, horarios, areas, reportes, evaluaciones y XLSX.
- Se mantuvieron pendientes tecnicos conocidos: Bootstrap por CDN, PHPMailer no instalado, importacion manual de base, hash local de admin y revision del indice de marcas.

### Errores pendientes

- No se ejecuto prueba integral real con MySQL importado desde esta actualizacion documental.
- La guia aun depende de que el usuario importe manualmente `schema.sql`, `seed_catalogos.sql` y `seed_admin.sql`.

### Pruebas realizadas

- Se reviso `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md` para confirmar que ya no contiene referencias obsoletas a dashboards, recuperacion o restablecimiento como pendientes.
- Se verifico que la guia menciona los modulos nuevos: `admin/crear-practicante.php`, `supervisor/crear-practicante.php`, `practicante/mis-datos.php`, `practicante/marcar-asistencia.php` y `practicante/mi-historial.php`.

### Siguiente paso sugerido

Ejecutar la prueba integral en XAMPP con la base `asistencia` importada y resolver el pendiente del indice `UNIQUE(id_jornada, id_tipo_marca)` antes de implementar correcciones o anulaciones operativas.

## 2026-05-29 - Creacion de `docs/historial/referencias/PRUEBAS_LOCAL.md`

### Objetivo trabajado

Crear un documento operativo para planificar y ejecutar pruebas locales en XAMPP, permitiendo ingresar como ADMIN, registrar practicantes y disponer de un usuario SUPERVISOR de prueba sin hardcodear credenciales en PHP.

### Archivos modificados

- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se definio que no se deben hardcodear usuarios, contrasenas ni datos de prueba dentro del codigo PHP.
- Se documenta el uso de seeds existentes y SQL local controlado para preparar datos de prueba.
- Se establecio el flujo para crear hash local del ADMIN `admin@localhost.test`.
- Se agrego procedimiento temporal para crear `supervisor@localhost.test` por SQL local, hasta implementar `admin/usuarios.php`.
- Se documenta el flujo recomendado para registrar practicantes desde el portal y activar cuentas con enlace manual si SMTP no esta configurado.
- Se agregaron tablas y eventos de auditoria que deben revisarse durante la prueba integral.
- Se dejo indicado que `docs/historial/referencias/PRUEBAS_LOCAL.md` debe actualizarse cada vez que cambie el procedimiento de pruebas locales.

### Errores pendientes

- No se ejecuto la prueba integral real contra MySQL desde esta etapa documental.
- La creacion de supervisores sigue siendo manual por SQL local hasta implementar `admin/usuarios.php`.
- Las contrasenas sugeridas son solo locales y deben reemplazarse si se usa otro entorno de prueba.

### Pruebas realizadas

- Se verifico que `docs/historial/referencias/PRUEBAS_LOCAL.md` no existia antes de crearlo.
- Se reviso que el documento incluya pasos para ADMIN, SUPERVISOR y PRACTICANTE.
- Se reviso que no proponga hardcodear credenciales en archivos PHP.

### Siguiente paso sugerido

Ejecutar los pasos de `docs/historial/referencias/PRUEBAS_LOCAL.md` en XAMPP y, si aparece algun error real de importacion, login, activacion o marcacion, actualizar este documento con el ajuste correspondiente.

## 2026-05-29 - Ajuste de importacion SQL en `docs/historial/referencias/PRUEBAS_LOCAL.md`

### Objetivo trabajado

Corregir el procedimiento local de importacion SQL despues de detectar el error `El sistema no puede encontrar la ruta especificada` al ejecutar `mysql.exe` con una ruta relativa.

### Archivos modificados

- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego el paso explicito `cd C:\xampp\htdocs\asistencia` antes de importar archivos SQL.
- Se explico que `database\schema.sql` solo funciona si la consola esta ubicada en la raiz del proyecto.
- Se agregaron alternativas con rutas absolutas usando `cmd /c` para importar `schema.sql`, `seed_catalogos.sql` y `seed_admin.sql`.
- Se agregaron comandos `Test-Path` para validar que existan `mysql.exe` y los archivos SQL antes de importar.

### Errores pendientes

- No se ejecuto la importacion real de la base desde esta respuesta.
- Si el error persiste, se debe revisar si XAMPP esta instalado en otra ruta o si MySQL no esta iniciado.

### Pruebas realizadas

- Se verifico que existe `C:\xampp\mysql\bin\mysql.exe`.
- Se verifico que existe `C:\xampp\htdocs\asistencia\database\schema.sql`.

### Siguiente paso sugerido

Reintentar la importacion usando primero `cd C:\xampp\htdocs\asistencia` o, si falla, usar la variante con ruta absoluta mediante `cmd /c`.

## 2026-05-29 - Correccion de collation en `database/seed_admin.sql`

### Objetivo trabajado

Corregir el error local `ERROR 1267 (HY000): Illegal mix of collations` al importar `database/seed_admin.sql`.

### Archivos modificados

- `database/seed_admin.sql`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se cambio `SET NAMES utf8mb4` por `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`.
- Se definieron `@admin_email` y `@admin_password_hash` como literales `_utf8mb4` con `COLLATE utf8mb4_unicode_ci`.
- Se ajusto la comparacion del rol `ADMIN` para usar literal `_utf8mb4'ADMIN' COLLATE utf8mb4_unicode_ci`.
- Se mantuvo el seed idempotente con `ON DUPLICATE KEY UPDATE`.
- Se agrego una nota en `docs/historial/referencias/PRUEBAS_LOCAL.md` explicando que, ante `ERROR 1267`, se debe reejecutar el seed actualizado.

### Errores pendientes

- No se ejecuto la importacion real desde esta respuesta; el usuario debe reintentar el comando local.
- Si la base local fue creada previamente con otra collation por defecto, las tablas creadas por `schema.sql` siguen usando `utf8mb4_unicode_ci`, pero conviene validar que no haya tablas antiguas de pruebas anteriores.

### Pruebas realizadas

- Se reviso `database/seed_admin.sql` y se confirmo que los literales de comparacion usan `utf8mb4_unicode_ci`.
- Se reviso `docs/historial/referencias/PRUEBAS_LOCAL.md` y se confirmo que incluye la nota sobre `ERROR 1267`.

### Siguiente paso sugerido

Reejecutar `seed_admin.sql` con:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_admin.sql"
```

## 2026-05-29 - Aclaracion PowerShell/CMD en `docs/historial/referencias/PRUEBAS_LOCAL.md`

### Objetivo trabajado

Aclarar que `Test-Path` es un comando de PowerShell y no funciona en CMD, despues de que el usuario lo ejecuto desde `C:\xampp\mysql\bin>`.

### Archivos modificados

- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvieron los comandos `Test-Path` bajo el encabezado explicito "PowerShell".
- Se agregaron comandos equivalentes para CMD usando `if exist`.
- Se explico que un prompt como `C:\xampp\mysql\bin>` corresponde a CMD.
- Se agrego la opcion de escribir `powershell` desde CMD para cambiar a PowerShell.

### Errores pendientes

- No se ejecuto nuevamente la importacion local desde esta respuesta.

### Pruebas realizadas

- Se reviso que `docs/historial/referencias/PRUEBAS_LOCAL.md` incluya alternativas para PowerShell y CMD.

### Siguiente paso sugerido

Verificar rutas con `if exist` si se esta en CMD, o cambiar a PowerShell y usar `Test-Path`.

## 2026-06-01 - Correccion de pre-registro minimo y cierre de ficha del practicante

### Objetivo trabajado

Alinear el flujo de pre-registro, activacion y primer acceso del practicante con las reglas oficiales: mantener el formulario completo para ADMIN/SUPERVISOR, exigir solo primer nombre, apellido paterno y correo en pre-registro, permitir que el practicante complete la ficha posteriormente, agregar pais a direccion y mostrar horario pactado en marcacion.

### Archivos modificados

- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `database/schema.sql`
- `includes/practicante_registro.php`
- `includes/auth.php`
- `login.php`
- `seleccionar-rol.php`
- `practicante/mis-datos.php`
- `includes/reglas_asistencia.php`
- `practicante/marcar-asistencia.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se documento en `docs/historial/referencias/CONTEXTO_PROYECTO.md` la diferencia entre pre-registro minimo y ficha completa del practicante.
- Se agrego `pais` al modelo de `practicantes`.
- Se relajaron restricciones `NOT NULL` en `practicantes` para permitir registros incompletos durante el pre-registro.
- Se permitio que `practicas.id_tipo_practica`, `horas_requeridas` y `fecha_inicio_practica` queden `NULL` hasta cerrar ficha.
- El pre-registro conserva todos los campos visibles, pero solo marca como obligatorios `nombre_1`, `apellido_paterno` y `email`.
- `practicante/mis-datos.php` ahora opera como completar registro cuando la ficha esta incompleta y como edicion de contacto/direccion cuando ya esta completa.
- El PRACTICANTE puede completar una vez datos de institucion, sede, carrera, tipo de practica, horas e inicio; despues conserva editables telefono, nacionalidad, direccion, numero, departamento, comuna, pais y region.
- `includes/auth.php` redirige automaticamente al practicante con ficha incompleta hacia `practicante/mis-datos.php?completar=1`.
- Se bloquea el acceso directo del practicante incompleto a otras pantallas privadas hasta cerrar ficha.
- `practicante/marcar-asistencia.php` muestra el horario pactado del dia cuando existe y avisa si el dia no tiene jornada pactada.
- `includes/reglas_asistencia.php` consulta el horario vigente del dia y lo usa al recalcular la jornada despues de registrar una marca.

### Errores pendientes

- Falta crear una pantalla ADMIN/SUPERVISOR para asignar horarios pactados; por ahora solo se consulta y muestra si ya existen registros en `horarios_practicante` y `detalle_horario_practicante`.
- Falta implementar baja logica de practicantes desde `admin/practicantes.php`.
- Falta implementar el modulo de documentos PDF del practicante.
- La base local debe resetearse nuevamente e importarse con el `schema.sql` actualizado antes de probar este flujo.

### Pruebas realizadas

- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\practicante_registro.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\mis-datos.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\auth.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l practicante\marcar-asistencia.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l login.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l seleccionar-rol.php`.
- Se valido sintaxis con `C:\xampp\php\php.exe -l includes\reglas_asistencia.php`.

### Siguiente paso sugerido

Resetear la base local, importar `schema.sql`, `seed_catalogos.sql` y `seed_admin.sql`, y probar desde cero: login ADMIN, pre-registro con solo tres campos obligatorios, activacion del practicante, primer login con redireccion a completar registro y acceso posterior a marcar asistencia.

## 2026-06-01 - Actualizacion de `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md` por flujo de ficha incompleta

### Objetivo trabajado

Actualizar la guia de pruebas locales para reflejar el nuevo flujo de pre-registro minimo, cierre de ficha del practicante, campo `pais`, reset de base local y visualizacion de horario pactado en marcacion.

### Archivos modificados

- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego la recomendacion de resetear la base local si ya habia sido importada antes del cambio de schema.
- Se reemplazaron los comandos de importacion con variantes `cmd /c` y rutas absolutas para que funcionen correctamente desde PowerShell con redireccion `<`.
- Se actualizo la prueba de pre-registro para indicar que el formulario conserva todos los campos, pero solo exige primer nombre, apellido paterno y correo.
- Se actualizo la prueba de login PRACTICANTE para esperar redireccion a `practicante/mis-datos.php?completar=1` cuando la ficha esta incompleta.
- Se actualizo la prueba de `mis-datos.php` para cubrir modo `Completar registro` y modo `Mis datos`.
- Se agrego la expectativa de visualizar horario pactado del dia en `practicante/marcar-asistencia.php`.
- Se documento que la asignacion administrativa de horarios y el modulo de documentos PDF siguen pendientes.

### Errores pendientes

- No se ejecuto una prueba integral en navegador despues de actualizar la guia.
- La base local debe resetearse e importarse antes de validar el flujo nuevo.

### Pruebas realizadas

- Se reviso que `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md` mencione pre-registro minimo, completar registro, `pais`, reset local y horario pactado.

### Siguiente paso sugerido

Resetear e importar la base local actualizada, luego probar el flujo completo desde `admin@localhost.test` hasta el primer acceso del practicante y cierre de ficha.

## 2026-06-02 - Correccion de guia para hash local del admin en PowerShell

### Objetivo trabajado

Corregir el procedimiento de `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md` para actualizar la contrasena local del usuario `admin@localhost.test` sin corromper hashes bcrypt que contienen `$`.

### Archivos modificados

- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se diagnostico que el hash guardado en la base tenia largo `38` y no comenzaba con `$2y$`, por lo que `password_verify()` no podia validar `AdminLocal123`.
- Se corrigio localmente el usuario admin con un hash bcrypt valido de largo `60`.
- Se actualizo la guia para evitar pegar hashes bcrypt dentro de comandos PowerShell con comillas dobles.
- Se reemplazo el comando vulnerable por un bloque here-string enviado por entrada estandar a `mysql.exe`.
- Se agrego una consulta de verificacion para confirmar `hash_inicio = $2y$`, `hash_largo = 60`, cuenta activa y bloqueo limpio.

### Errores pendientes

- Falta que el usuario pruebe nuevamente el login en navegador y confirme que se crea la fila en `sesiones_usuario`.

### Pruebas realizadas

- Se consulto `usuarios_sistema` y se detecto hash corrupto para `admin@localhost.test`.
- Se actualizo el hash local de `admin@localhost.test` con `AdminLocal123`.
- Se verifico que el hash guardado comienza con `$2y$`, tiene largo `60`, `estado_cuenta = ACTIVO`, `email_verificado = 1`, `activo = 1`, `intentos_fallidos = 0`, `bloqueado_hasta = NULL` y rol `ADMIN`.

### Siguiente paso sugerido

Ingresar nuevamente a `http://localhost/asistencia/login.php` con `admin@localhost.test` y `AdminLocal123`, luego revisar que `sesiones_usuario` tenga una fila activa nueva.

## 2026-06-02 - Catalogo de regiones y comunas de Chile

### Objetivo trabajado

Implementar catalogo normalizado de regiones y comunas de Chile para formularios de direccion, reemplazando comuna como texto libre por un selector dependiente de region.

### Archivos modificados

- `database/schema.sql`
- `database/seed_catalogos.sql`
- `database/seed_regiones_comunas.sql`
- `includes/practicante_registro.php`
- `practicante/mis-datos.php`
- `includes/auth.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/DOCUMENTO_BBDD.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se crearon tablas normalizadas `regiones` y `comunas` en `schema.sql`.
- Se cambio `practicantes.comuna` por `practicantes.id_comuna` con FK a `comunas.id_comuna`.
- Se agrego `database/seed_regiones_comunas.sql` con 16 regiones y 346 comunas.
- `seed_catalogos.sql` dejo de cargar regiones antiguas con `codigo`/`nombre`.
- El pre-registro carga regiones y comunas desde BBDD; comuna se filtra por region y no es texto libre.
- `practicante/mis-datos.php` exige region y comuna para cerrar ficha y valida que la comuna pertenezca a la region.
- `includes/auth.php` considera `id_comuna` como parte de la ficha completa.
- Se actualizaron documentos para reflejar `nombre_region`, `nombre_comuna`, `id_comuna` y el nuevo orden de importacion local.

### Errores pendientes

- La base local `asistencia` debe resetearse o migrarse antes de probar, porque el esquema cambio de `comuna VARCHAR` a `id_comuna INT`.
- Falta prueba manual en navegador del selector dependiente en pre-registro y en `practicante/mis-datos.php`.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\practicante_registro.php`
- `C:\xampp\php\php.exe -l practicante\mis-datos.php`
- `C:\xampp\php\php.exe -l includes\auth.php`
- Se importo `schema.sql` en base temporal `asistencia_tmp_codex`.
- Se importo `seed_regiones_comunas.sql` en base temporal.
- Se importo `seed_catalogos.sql` en base temporal.
- Se verificaron conteos: 16 regiones y 346 comunas.
- Se elimino la base temporal `asistencia_tmp_codex`.

### Siguiente paso sugerido

Resetear la base local, importar en orden `schema.sql`, `seed_regiones_comunas.sql`, `seed_catalogos.sql` y `seed_admin.sql`, luego probar login ADMIN, crear practicante y completar ficha verificando que comuna dependa de region.

## 2026-06-02 - Correccion de guia por base local con schema antiguo

### Objetivo trabajado

Aclarar el procedimiento local cuando `seed_regiones_comunas.sql` falla con `Unknown column 'nombre_region'` por existir una tabla `regiones` creada con el schema antiguo.

### Archivos modificados

- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se documento que `schema.sql` usa `CREATE TABLE IF NOT EXISTS` y no reemplaza tablas antiguas.
- Se reforzo que, en XAMPP de pruebas, debe ejecutarse `DROP DATABASE IF EXISTS asistencia` y luego recrear la base antes de importar el schema actualizado.
- Se agrego el error esperado `ERROR 1054 (42S22): Unknown column 'nombre_region' in 'field list'` como indicador de base local no reseteada.

### Errores pendientes

- Falta que el usuario vuelva a resetear la base local y reimporte los archivos SQL en el orden actualizado.

### Pruebas realizadas

- Se reviso la guia local y se confirmo que el error se explica por una tabla `regiones` previa con columnas antiguas.

### Siguiente paso sugerido

Ejecutar primero el reset de base local y luego importar `schema.sql`, `seed_regiones_comunas.sql`, `seed_catalogos.sql` y `seed_admin.sql`.

## 2026-06-02 - Correccion de comandos de hash bcrypt en `PRUEBAS_LOCAL.md`

### Objetivo trabajado

Evitar que PowerShell corrompa hashes bcrypt al actualizar contrasenas locales de ADMIN o SUPERVISOR desde comandos SQL.

### Archivos modificados

- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el comando vulnerable con `mysql.exe -e "UPDATE ... password_hash='$2y$...'"` por un here-string de comillas simples.
- Se agrego verificacion de `hash_inicio = $2y$` y `hash_largo = 60`.
- Se ajusto tambien el ejemplo de creacion del supervisor local para usar here-string y limpiar campos de bloqueo.

### Errores pendientes

- Falta que el usuario vuelva a aplicar el hash correcto en la base local y pruebe login en navegador.

### Pruebas realizadas

- Se reviso `docs/historial/referencias/PRUEBAS_LOCAL.md` y se eliminaron los comandos que exponian el hash bcrypt a expansion de variables de PowerShell.

### Siguiente paso sugerido

Actualizar el admin local con el here-string seguro, verificar largo del hash y probar login con `admin@localhost.test` / `AdminLocal123`.

## 2026-06-02 - Correccion de tildes, RUT y horario pactado

### Objetivo trabajado

Corregir textos mojibakeados en formularios, hacer mas tolerante la lectura del RUT/DV y permitir registrar horario pactado en pre-registro y cierre inicial de ficha.

### Archivos modificados

- `includes/horarios_practicante.php`
- `includes/practicante_registro.php`
- `practicante/mis-datos.php`
- `practicante/marcar-asistencia.php`
- `includes/auth.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/DOCUMENTO_BBDD.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se corrigieron literales visibles con tildes dañadas en PHP.
- Se agrego normalizacion de RUT para aceptar numero/DV separados o RUT completo con guion en el campo RUT.
- Se creo helper reutilizable para horario pactado semanal.
- El pre-registro ADMIN/SUPERVISOR puede guardar horario pactado si se informa, sin cambiar los tres campos obligatorios minimos.
- El cierre inicial de ficha del PRACTICANTE exige al menos un dia con jornada pactada.
- Despues de cerrar ficha, el PRACTICANTE no modifica libremente el horario; queda pendiente una pantalla administrativa dedicada para gestion avanzada.
- `auth.php` considera incompleta una ficha sin horario pactado activo.

### Errores pendientes

- Falta prueba manual en navegador del bloque de horario pactado y del ingreso de RUT completo.
- Sigue pendiente una pantalla dedicada ADMIN/SUPERVISOR para modificar horarios despues del cierre de ficha.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l includes\practicante_registro.php`
- `C:\xampp\php\php.exe -l practicante\mis-datos.php`
- `C:\xampp\php\php.exe -l practicante\marcar-asistencia.php`
- `C:\xampp\php\php.exe -l includes\auth.php`
- `C:\xampp\php\php.exe -l admin\crear-practicante.php`
- `C:\xampp\php\php.exe -l supervisor\crear-practicante.php`
- Se probo por CLI que un horario requerido sin dias genera error, y que lunes a viernes 09:00-18:00 con colacion 13:00-14:00 valida y calcula 8 horas.
- Se busco mojibake restante en archivos PHP y no se detectaron coincidencias.

### Siguiente paso sugerido

Probar en navegador: pre-registro con horario opcional, activacion de practicante, completar ficha con RUT completo y horario pactado, y luego marcar asistencia verificando que se muestre el horario del dia.

## 2026-06-02 - Correccion de carga de horario en marcar asistencia

### Objetivo trabajado

Corregir el error `SQLSTATE[HY093]: Invalid parameter number` al entrar a `practicante/marcar-asistencia.php`.

### Archivos modificados

- `includes/reglas_asistencia.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo la reutilizacion del placeholder PDO `:fecha` por placeholders independientes `:fecha_inicio` y `:fecha_fin`.
- Se mantuvo la misma regla funcional: cargar el horario vigente cuando `fecha_inicio <= fecha` y `fecha_fin` es NULL o posterior/igual a la fecha consultada.
- No se modifico la base de datos porque el problema era de consulta SQL/PDO, no de schema.

### Errores pendientes

- Falta prueba manual en navegador registrando la primera marca del practicante.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\reglas_asistencia.php`
- Prueba CLI contra la base local: `asistencia_horario_vigente_dia()` ya no arroja `HY093`.
- La prueba confirmo `HORARIO_OK=SI` y `JORNADA_EXISTE=NO`, esperado antes de la primera marca.

### Siguiente paso sugerido

Entrar nuevamente como PRACTICANTE a `practicante/marcar-asistencia.php` y registrar la primera `ENTRADA`.

## 2026-06-02 - Login con sesion persistente y texto de bienvenida

### Objetivo trabajado

Agregar en `login.php` la opcion `Mantener la sesion iniciada` y un bloque lateral de bienvenida al portal de e-practicas, manteniendo el flujo de login seguro, roles reales y logout operativo.

### Archivos modificados

- `config/app.php`
- `includes/helpers.php`
- `includes/auth.php`
- `login.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego `REMEMBER_SESSION_DAYS` como constante centralizada.
- Si el usuario no marca `Mantener la sesion iniciada`, sigue aplicando `SESSION_TIMEOUT_MINUTES`.
- Si el usuario marca la opcion, la cookie de sesion se extiende por el plazo configurado y `auth_verificar_timeout()` respeta esa sesion persistente hasta su expiracion.
- `logout.php` sigue cerrando la sesion real y ahora tambien elimina la cookie persistente mediante helper centralizado.
- La pantalla de login mantiene el formulario existente y agrega texto de bienvenida sin cambiar roles, permisos ni base de datos.

### Errores pendientes

- Falta prueba manual en navegador cerrando y reabriendo el navegador con `Mantener la sesion iniciada` marcado.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l config\app.php`
- `C:\xampp\php\php.exe -l includes\helpers.php`
- `C:\xampp\php\php.exe -l includes\auth.php`
- `C:\xampp\php\php.exe -l login.php`

### Siguiente paso sugerido

Probar en navegador el login normal, el login con `Mantener la sesion iniciada`, el acceso directo a `http://localhost/asistencia/` y el logout posterior.

## 2026-06-02 - Validacion del punto 1 de Fase 2

### Objetivo trabajado

Registrar que el punto 1 propuesto para Fase 2, correspondiente a pruebas integrales finales de Fase 1 en navegador/XAMPP, ya fue validado por el usuario.

### Archivos modificados

- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se considera validado el punto 1 de Fase 2: pruebas integrales finales de Fase 1.
- Se actualizo el estado actual del proyecto para no mantener la prueba integral como siguiente paso pendiente.
- El siguiente foco sugerido de Fase 2 queda en la gestion administrativa de horarios pactados para ADMIN/SUPERVISOR.

### Errores pendientes

- No se registran errores nuevos por esta actualizacion documental.

### Pruebas realizadas

- Validacion informada por el usuario.

### Siguiente paso sugerido

Comenzar Fase 2 con la pantalla ADMIN/SUPERVISOR para administrar horarios pactados despues del cierre de ficha.

## 2026-06-02 - Fase 2.1 gestion administrativa de horarios pactados

### Objetivo trabajado

Implementar una primera pantalla operativa para que ADMIN y SUPERVISOR administren el horario pactado vigente de practicantes con practica activa, despues del cierre de ficha, conservando historial y sin borrar registros fisicamente.

### Archivos modificados

- `includes/horarios_practicante.php`
- `admin/practicantes.php`
- `supervisor/practicantes.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo la arquitectura actual PHP/PDO sin agregar rutas externas ni migraciones.
- `admin/practicantes.php` y `supervisor/practicantes.php` dejaron de ser placeholders y ahora delegan en una pantalla reutilizable de gestion de horarios.
- Se agrego listado de practicantes activos con practica activa y busqueda por nombre, apellido, RUT o correo.
- Se permite abrir un practicante y reemplazar su horario semanal pactado con fecha de inicio de vigencia.
- El reemplazo inactiva el horario vigente anterior y completa `fecha_fin` cuando estaba vacia, manteniendo historial.
- Se reutiliza la validacion existente de horario pactado y se exige al menos un dia con jornada.
- En rol SUPERVISOR se bloquea la autosupervision usando `auth_es_conflicto_supervisor_practicante()`.
- La accion se audita como `EDITAR_PRACTICANTE`, afectando `horarios_practicante`.
- Se actualizo el contexto oficial para dejar explicita la administracion posterior de horarios por ADMIN/SUPERVISOR.

### Errores pendientes

- No se implementaron excepciones avanzadas de horario ni cambios temporales por fecha.
- No se implementaron filtros por supervisor asignado porque el modelo actual aun no define una relacion de asignacion supervisor-practicante.
- Sigue pendiente resolver correcciones/anulaciones operativas de asistencia y el indice `UNIQUE(id_jornada, id_tipo_marca)` antes de esos flujos.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l admin\practicantes.php`
- `C:\xampp\php\php.exe -l supervisor\practicantes.php`
- Prueba PHP CLI de solo lectura contra MySQL local: `horarios_practicante_listar_practicantes_gestion()` respondio `PRACTICANTES=1`.
- Prueba PHP CLI de busqueda contra MySQL local: `horarios_practicante_listar_practicantes_gestion('test')` respondio `PRACTICANTES=0` sin error PDO.

### Siguiente paso sugerido

Probar en navegador como ADMIN y SUPERVISOR la edicion de horario pactado desde `admin/practicantes.php` y `supervisor/practicantes.php`, luego avanzar a baja logica/edicion avanzada de practicantes o preparar correcciones de asistencia.

## 2026-06-02 - Fase 2.2 baja logica y detalle operativo de practicantes

### Objetivo trabajado

Completar la siguiente subfase de gestion de practicantes agregando detalle operativo del practicante seleccionado y baja logica reservada a ADMIN, sin borrar fisicamente registros ni entrar aun en correcciones de asistencia.

### Archivos modificados

- `includes/horarios_practicante.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvieron `admin/practicantes.php` y `supervisor/practicantes.php` como entradas delgadas que reutilizan la pantalla comun ya creada en Fase 2.1.
- La pantalla ahora muestra resumen de carrera, sede, fecha de inicio de practica y horas requeridas del practicante seleccionado.
- ADMIN puede ejecutar baja logica con confirmacion explicita.
- La baja logica desactiva `practicantes.activo`, practicas activas asociadas y la cuenta vinculada en `usuarios_sistema`, dejando `estado_cuenta = INACTIVO`.
- SUPERVISOR puede revisar detalle y gestionar horarios permitidos, pero no tiene accion de baja logica.
- Se audita la baja como `EDITAR_PRACTICANTE`, afectando `practicantes`.
- Se actualizo el contexto oficial para dejar explicita la regla de baja logica reservada a ADMIN.

### Errores pendientes

- No se implemento edicion administrativa completa de datos personales/institucionales del practicante.
- No se implementaron documentos PDF del practicante.
- Sigue pendiente preparar correcciones/anulaciones de asistencia y resolver antes el indice `UNIQUE(id_jornada, id_tipo_marca)`.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l admin\practicantes.php`
- `C:\xampp\php\php.exe -l supervisor\practicantes.php`
- Prueba PHP CLI de solo lectura contra MySQL local: `horarios_practicante_listar_practicantes_gestion()` respondio `PRACTICANTES=1`.

### Siguiente paso sugerido

Probar en navegador la baja logica desde `admin/practicantes.php`; despues preparar la etapa de correcciones/anulaciones de asistencia revisando primero el indice de marcas.

## 2026-06-03 - Fase 2.3 preparacion de indices para correcciones de asistencia

### Objetivo trabajado

Preparar `marcas_asistencia` para permitir correcciones y anulaciones logicas con trazabilidad, eliminando el bloqueo tecnico del indice unico por jornada y tipo de marca.

### Archivos modificados

- `database/schema.sql`
- `database/2026-06-03_patch_marcas_asistencia_indices_001.sql`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reemplazo el indice unico `uq_marcas_jornada_tipo` por el indice no unico `idx_marcas_jornada_tipo_estado`.
- Se creo un parche SQL idempotente para bases locales ya importadas sin obligar a resetear toda la base.
- Se documento que la unicidad operativa de marcas vigentes queda en backend usando `activo = 1` y `anulada = 0`.
- No se implemento todavia UI de correcciones/anulaciones ni se modificaron controladores PHP.

### Errores pendientes

- Falta implementar la pantalla/flujo ADMIN-SUPERVISOR para correcciones, anulaciones individuales y anulacion completa de jornadas.

### Pruebas realizadas

- Se reviso que `database/schema.sql` ya no contiene `uq_marcas_jornada_tipo`.
- Se reviso que el parche SQL exista y documente el cambio para bases ya importadas.
- Antes de aplicar el parche, se consulto la base local en modo solo lectura y se confirmo que conservaba `uq_marcas_jornada_tipo`.
- Con autorizacion explicita del usuario, se ejecuto `database/2026-06-03_patch_marcas_asistencia_indices_001.sql` en la base local.
- Se verifico que `marcas_asistencia` ya tiene `idx_marcas_jornada_tipo_estado` como indice no unico sobre `id_jornada`, `id_tipo_marca`, `activo` y `anulada`.

### Siguiente paso sugerido

Implementar la UI de correcciones/anulaciones de asistencia.

## 2026-06-03 - Fase 2.4 UI base de correcciones/anulaciones de asistencia

### Objetivo trabajado

Implementar una primera pantalla operativa ADMIN/SUPERVISOR para revisar jornadas, anular marcas individuales, registrar marcas correctivas y marcar jornadas en revision, respetando no borrado fisico y auditoria.

### Archivos modificados

- `includes/asistencia_gestion.php`
- `admin/asistencia.php`
- `supervisor/asistencia.php`
- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo un helper comun `includes/asistencia_gestion.php` para mantener entradas delgadas por rol.
- ADMIN y SUPERVISOR pueden listar jornadas con filtros por fecha, estado y busqueda de practicante.
- El detalle muestra marcas vigentes y anuladas.
- La anulacion individual de marcas reutiliza `asistencia_anular_marca()` y audita `ANULAR_MARCA_ASISTENCIA`.
- Las marcas correctivas se registran con `asistencia_registrar_marca()` respetando la secuencia vigente y auditan `CORREGIR_ASISTENCIA`.
- ADMIN y SUPERVISOR pueden marcar jornadas como `OBSERVADA` o `PENDIENTE_REVISION`.
- La anulacion completa de jornada queda solo para ADMIN y audita `ANULAR_ASISTENCIA`.
- Se mantiene bloqueo de autosupervision para SUPERVISOR usando `auth_es_conflicto_supervisor_practicante()`.

### Errores pendientes

- Falta prueba manual en navegador con sesiones reales ADMIN/SUPERVISOR.
- No se implementaron correcciones avanzadas por bloques de area, excepciones de horario ni aprobaciones posteriores.
- No se implemento todavia una vista dedicada de auditoria.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\asistencia_gestion.php`
- `C:\xampp\php\php.exe -l admin\asistencia.php`
- `C:\xampp\php\php.exe -l supervisor\asistencia.php`
- `C:\xampp\php\php.exe -l admin\dashboard.php`
- `C:\xampp\php\php.exe -l supervisor\dashboard.php`
- Prueba PHP CLI de solo lectura contra MySQL local: `asistencia_gestion_jornadas()` respondio `JORNADAS=1`.
- Prueba PHP CLI de busqueda contra MySQL local: `asistencia_gestion_jornadas()` con `q = test` respondio `BUSQUEDA=0` sin error PDO.
- Se verifico que `marcas_asistencia` conserva `idx_marcas_jornada_tipo_estado` como indice no unico y ya no expone `uq_marcas_jornada_tipo`.

### Siguiente paso sugerido

Probar en navegador `admin/asistencia.php` y `supervisor/asistencia.php`; despues avanzar a correcciones avanzadas por bloques de area o excepciones de horario.

## 2026-06-03 - Validacion de login SUPERVISOR local

### Objetivo trabajado

Registrar la validacion informada por el usuario: el usuario SUPERVISOR creado localmente por SQL pudo iniciar sesion y acceder correctamente.

### Archivos modificados

- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se considera validado el acceso del rol SUPERVISOR con la base local actual.
- La validacion confirma que las pantallas protegidas por SUPERVISOR pueden probarse con sesion real.

### Errores pendientes

- La creacion de supervisores seguia dependiendo de SQL manual hasta implementar `admin/usuarios.php`.

### Pruebas realizadas

- Validacion manual informada por el usuario: login y acceso de SUPERVISOR funcionan correctamente.

### Siguiente paso sugerido

Implementar `admin/usuarios.php` para crear supervisores desde interfaz.

## 2026-06-03 - Fase 2.5 gestion basica de usuarios SUPERVISOR

### Objetivo trabajado

Implementar la pantalla ADMIN de gestion basica de usuarios para listar cuentas y crear usuarios externos SUPERVISOR con activacion por token, evitando SQL manual y contrasenas visibles.

### Archivos modificados

- `includes/usuarios_gestion.php`
- `admin/usuarios.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- `admin/usuarios.php` dejo de ser placeholder y ahora exige rol `ADMIN`.
- Se creo `includes/usuarios_gestion.php` para concentrar listado, validacion y creacion de supervisores.
- Los supervisores se crean como usuarios externos con `id_practicante = NULL`.
- La cuenta queda `PENDIENTE_ACTIVACION`, `email_verificado = 0`, `debe_cambiar_password = 1` y rol `SUPERVISOR` activo.
- Se genera token `ACTIVACION_CUENTA` con hash SHA-256 en `tokens_usuario`.
- Si PHPMailer/SMTP no esta disponible, la pantalla muestra enlace manual de activacion para pruebas locales.
- Se auditan `CREACION_USUARIO`, `ROL_ASIGNADO` y `TOKEN_GENERADO`.
- No se implementaron edicion avanzada, baja/desactivacion ni gestion de roles multiples en esta fase.

### Errores pendientes

- Falta prueba manual en navegador creando un supervisor nuevo desde `admin/usuarios.php`.
- Falta implementar edicion/desactivacion de usuarios y administracion avanzada de roles.
- PHPMailer sigue pendiente si se requiere envio real de correos.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\usuarios_gestion.php`
- `C:\xampp\php\php.exe -l admin\usuarios.php`
- Prueba PHP CLI de solo lectura contra MySQL local: `usuarios_gestion_listar()` respondio `USUARIOS=3`.
- Prueba PHP CLI de busqueda contra MySQL local: `usuarios_gestion_listar('supervisor')` respondio `BUSQUEDA=1`.
- Prueba PHP CLI sin escritura: `usuarios_gestion_crear_supervisor('correo-invalido')` rechazo el correo antes de insertar.

### Siguiente paso sugerido

Probar en navegador la creacion de un nuevo supervisor desde `admin/usuarios.php`; despues avanzar a correcciones avanzadas por bloques de area o excepciones de horario.

## 2026-06-03 - Ajuste Fase 2.5 asignacion SUPERVISOR a usuario existente

### Objetivo trabajado

Corregir la gestion de usuarios para permitir que un usuario existente, incluyendo un PRACTICANTE, reciba tambien el rol `SUPERVISOR` desde `admin/usuarios.php`, sin crear cuentas duplicadas.

### Archivos modificados

- `includes/usuarios_gestion.php`
- `admin/usuarios.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Si el correo no existe, se mantiene el flujo de creacion de supervisor externo con token de activacion.
- Si el correo ya existe, se asigna o reactiva `SUPERVISOR` en `usuarios_roles`.
- Si el usuario ya tiene `SUPERVISOR` activo, se muestra mensaje informativo en vez de error.
- No se genera token ni contrasena nueva para usuarios existentes.
- El caso `PRACTICANTE + SUPERVISOR` queda alineado con la regla oficial de usuarios multirrol.
- La pantalla ahora comunica "Crear o asignar supervisor" para evitar confundir alta nueva con asignacion de rol.

### Errores pendientes

- Falta prueba manual en navegador asignando rol `SUPERVISOR` al practicante existente reportado por el usuario.
- Sigue pendiente implementar edicion/desactivacion de usuarios y gestion avanzada de roles.
- `git diff` no pudo revisarse desde la herramienta por un error de sandbox/ejecucion de Git; no afecta los archivos editados ni las pruebas PHP.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\usuarios_gestion.php`
- `C:\xampp\php\php.exe -l admin\usuarios.php`
- Prueba PHP CLI sin escritura: `usuarios_gestion_crear_supervisor('correo-invalido')` rechazo el correo antes de insertar.
- Prueba PHP CLI de solo lectura: `supervisor@localhost.test` existe y conserva rol `SUPERVISOR` activo.
- Prueba PHP CLI sin escritura efectiva: `usuarios_gestion_crear_supervisor('supervisor@localhost.test')` respondio `El usuario ya tiene rol SUPERVISOR activo.`

### Siguiente paso sugerido

Probar en navegador `admin/usuarios.php` ingresando el correo del practicante existente; luego cerrar sesion e iniciar sesion con ese usuario para validar el selector de rol `PRACTICANTE` / `SUPERVISOR`.

## 2026-06-03 - Ajuste Fase 2.5 remocion SUPERVISOR en practicantes

### Objetivo trabajado

Permitir que ADMIN quite el rol `SUPERVISOR` a un usuario vinculado a practicante, dejandolo solo como `PRACTICANTE` sin desactivar la cuenta ni borrar registros fisicamente.

### Archivos modificados

- `includes/usuarios_gestion.php`
- `admin/usuarios.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego accion `quitar_supervisor` protegida por CSRF dentro de `admin/usuarios.php`.
- La accion solo aplica a usuarios con `id_practicante` y roles activos `PRACTICANTE` y `SUPERVISOR`.
- La remocion es logica: `usuarios_roles.rol_activo = 0` para el rol `SUPERVISOR`.
- El rol `PRACTICANTE` permanece activo.
- La accion se audita como `ROL_REMOVIDO`.
- No se habilito quitar `SUPERVISOR` a supervisores externos desde esta accion para evitar dejar usuarios sin rol activo.

### Errores pendientes

- Falta prueba manual en navegador usando un practicante-supervisor real.
- Sigue pendiente edicion/desactivacion general de usuarios y gestion avanzada de roles.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\usuarios_gestion.php`
- `C:\xampp\php\php.exe -l admin\usuarios.php`
- Prueba PHP CLI sin escritura: `usuarios_gestion_quitar_supervisor_practicante(0)` rechazo el usuario invalido.
- Prueba PHP CLI sin escritura efectiva: intentar quitar `SUPERVISOR` a `supervisor@localhost.test` fue rechazado porque es supervisor externo, no practicante.

### Siguiente paso sugerido

Probar en navegador: asignar `SUPERVISOR` a un practicante, confirmar selector de rol, luego quitar `SUPERVISOR` desde `admin/usuarios.php` y verificar que el siguiente login quede solo como `PRACTICANTE`.

## 2026-06-03 - Fase 2.6 gestion base de areas por practica

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR asignen o cambien el area vigente de una practica desde la pantalla existente de practicantes, conservando historial y sin reiniciar horas.

### Archivos modificados

- `includes/areas_practicante.php`
- `includes/horarios_practicante.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/areas_practicante.php` para listar catalogo, leer area vigente, leer historial y cambiar area.
- `admin/practicantes.php` y `supervisor/practicantes.php` muestran area vigente al reutilizar `includes/horarios_practicante.php`.
- El cambio de area cierra el historial anterior con `fecha_fin` y crea una nueva fila vigente en `historial_areas_practicante`.
- La accion se audita como `CAMBIAR_AREA`.
- Se mantiene bloqueo de autosupervision para usuarios `PRACTICANTE + SUPERVISOR`.
- No se implementaron aun bloques de area por jornada; quedan para una fase posterior.

### Errores pendientes

- Falta prueba manual en navegador cambiando area como ADMIN y SUPERVISOR.
- Falta implementar `bloques_area_jornada` para dividir una jornada en mas de un area.
- Falta implementar excepciones avanzadas de horario.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\areas_practicante.php`
- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l admin\practicantes.php`
- `C:\xampp\php\php.exe -l supervisor\practicantes.php`
- Prueba PHP CLI de solo lectura: `areas_practicante_catalogo()` respondio `AREAS=8`.
- Prueba PHP CLI sin escritura: `areas_practicante_cambiar(0, 0, '2026-06-03', '')` rechazo `Practica no valida`.
- Prueba PHP CLI de solo lectura: `horarios_practicante_listar_practicantes_gestion()` respondio `PRACTICANTES=1` e incluye `area_vigente`.

### Siguiente paso sugerido

Probar la asignacion de area vigente desde `admin/practicantes.php` y `supervisor/practicantes.php`; despues avanzar a bloques de area por jornada.

## 2026-06-03 - Fase 2.7 bloques de area por jornada

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR registren bloques de area dentro de una jornada de asistencia, para representar tramos como manana/tarde en areas distintas.

### Archivos modificados

- `includes/areas_jornada.php`
- `includes/asistencia_gestion.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/areas_jornada.php` para listar bloques, validar horarios, evitar solapamientos y registrar tramos.
- `admin/asistencia.php` y `supervisor/asistencia.php` muestran bloques de area en el detalle de jornada al reutilizar `includes/asistencia_gestion.php`.
- Cada bloque exige area activa, inicio y fin dentro de la fecha de la jornada.
- `horas_asignadas` se calcula desde inicio y fin.
- Se bloquean bloques solapados en una misma jornada.
- La accion se audita como `CAMBIAR_AREA`, afectando `bloques_area_jornada`.
- Se mantiene bloqueo de autosupervision para usuarios `PRACTICANTE + SUPERVISOR`.
- No se implemento edicion/anulacion de bloques porque la tabla actual no modela anulacion logica granular para esos registros.

### Errores pendientes

- Falta prueba manual en navegador registrando bloques reales como ADMIN y SUPERVISOR.
- Falta definir una estrategia de correccion/anulacion logica de bloques si se requiere modificar un bloque mal ingresado.
- Falta implementar excepciones avanzadas de horario.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\areas_jornada.php`
- `C:\xampp\php\php.exe -l includes\asistencia_gestion.php`
- `C:\xampp\php\php.exe -l admin\asistencia.php`
- `C:\xampp\php\php.exe -l supervisor\asistencia.php`
- Prueba PHP CLI de solo lectura: `areas_jornada_bloques(0)` respondio `BLOQUES=0`.
- Prueba PHP CLI sin escritura: `areas_jornada_registrar_bloque()` rechazo una jornada invalida.
- Prueba PHP CLI de solo lectura: `asistencia_gestion_jornadas()` respondio `JORNADAS=2` y la primera jornada tiene `BLOQUES_JORNADA=0`.

### Siguiente paso sugerido

Probar bloques de area en `admin/asistencia.php` y `supervisor/asistencia.php`; despues avanzar a excepciones de horario.

## 2026-06-03 - Fase 2.8 excepciones de horario

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR registren excepciones puntuales al horario vigente de un practicante, y que la asistencia diaria use esa excepcion antes del horario semanal.

### Archivos modificados

- `includes/excepciones_horario.php`
- `includes/horarios_practicante.php`
- `includes/reglas_asistencia.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/excepciones_horario.php` para tipos, validacion, listado, guardado y adaptacion a asistencia.
- `admin/practicantes.php` y `supervisor/practicantes.php` muestran un bloque de excepciones al reutilizar `includes/horarios_practicante.php`.
- `CAMBIO_HORARIO` y `ASISTENCIA_EXTRAORDINARIA` pueden definir entrada, salida, colacion y horas planificadas.
- `DIA_LIBRE`, `AUSENCIA_JUSTIFICADA`, `FERIADO` y `OTRO` no exigen horario.
- `asistencia_horario_vigente_dia()` ahora revisa excepcion activa antes del detalle semanal.
- Las excepciones se guardan con `INSERT ... ON DUPLICATE KEY UPDATE`, reactivando/actualizando la fila logica si ya existia para horario, fecha y tipo.
- La accion se audita como `EDITAR_PRACTICANTE`.
- Se mantiene bloqueo de autosupervision por la pantalla de gestion compartida.

### Errores pendientes

- Falta prueba manual en navegador registrando una excepcion y luego marcando asistencia en la fecha afectada.
- No se implemento generacion automatica de ausencias esperadas.
- No se implemento pantalla de anulacion/desactivacion individual de excepciones.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\excepciones_horario.php`
- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l includes\reglas_asistencia.php`
- `C:\xampp\php\php.exe -l admin\practicantes.php`
- `C:\xampp\php\php.exe -l supervisor\practicantes.php`
- Prueba PHP CLI de solo lectura: `excepciones_horario_tipos()` respondio `TIPOS=6`.
- Prueba PHP CLI sin escritura: `excepciones_horario_guardar(0, ...)` rechazo `Horario no valido para registrar excepcion.`
- Prueba PHP CLI de lectura de horario diario: `asistencia_horario_vigente_dia()` respondio `HORARIO=OK` para una practica activa.

### Siguiente paso sugerido

Probar excepciones de horario en `admin/practicantes.php` y `supervisor/practicantes.php`; despues avanzar a generacion/revision de ausencias esperadas.

## 2026-06-03 - Fase 2.9 generacion inicial de ausencias esperadas

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR generen jornadas de ausencia esperada para dias planificados ya pasados, cuando no exista una jornada previa ni marcas registradas.

### Archivos modificados

- `includes/ausencias_esperadas.php`
- `includes/asistencia_gestion.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/ausencias_esperadas.php` para validar rango, listar practicas activas, detectar jornadas existentes y generar ausencias.
- La accion se integra en `admin/asistencia.php` y `supervisor/asistencia.php` mediante `includes/asistencia_gestion.php`.
- Solo se generan ausencias para fechas anteriores al dia actual.
- El rango maximo permitido es de 31 dias.
- Se crea jornada `INCOMPLETA`, `horas_calculadas = 0`, `flag_ausencia_esperada = 1` y observacion operativa.
- Si ya existe una jornada para la practica y fecha, se omite.
- Si no hay horario planificado vigente para la fecha, se omite.
- La accion se audita como `CORREGIR_ASISTENCIA`, afectando `jornadas_asistencia`.

### Errores pendientes

- Falta prueba manual en navegador generando ausencias con datos reales.
- No se implemento aun un flujo avanzado para justificar/resolver ausencias esperadas.
- No se implemento ejecucion automatica en background; la generacion queda manual y confirmada.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\ausencias_esperadas.php`
- `C:\xampp\php\php.exe -l includes\asistencia_gestion.php`
- `C:\xampp\php\php.exe -l admin\asistencia.php`
- `C:\xampp\php\php.exe -l supervisor\asistencia.php`
- Prueba PHP CLI sin escritura: `ausencias_esperadas_generar(hoy, hoy, null)` rechazo `Solo se generan ausencias para fechas anteriores a hoy.`
- Prueba PHP CLI de solo lectura: `ausencias_esperadas_practicas_activas()` respondio `PRACTICAS=1`.
- Prueba PHP CLI sin escritura: rango con fecha invalida rechazo `Debe indicar un rango de fechas valido.`

### Siguiente paso sugerido

Probar generacion de ausencias esperadas en `admin/asistencia.php` y `supervisor/asistencia.php`; despues avanzar a justificacion/resolucion de ausencias o revision/aprobacion de horas extra.

## 2026-06-03 - Fase 2.10 revision/aprobacion inicial de horas extra

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR revisen una jornada marcada con `flag_horas_extra = 1` y registren horas extra aprobadas con motivo, sin cambiar schema ni crear pantallas nuevas.

### Archivos modificados

- `includes/asistencia_gestion.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reutilizo la pantalla existente `admin/asistencia.php` y `supervisor/asistencia.php`.
- Se agrego la accion `aprobar_horas_extra` en `includes/asistencia_gestion.php`.
- La aprobacion exige jornada con `flag_horas_extra = 1`, horas aprobadas mayores a cero y motivo obligatorio.
- No se permite aprobar horas extra en jornadas `ANULADA` o `INCOMPLETA`.
- Las horas aprobadas no pueden superar `horas_calculadas`.
- La accion actualiza `horas_extra_aprobadas`, `aprobada_por`, deja la jornada como `CORREGIDA` y conserva trazabilidad en `observacion`.
- La accion se audita como `APROBAR_HORAS_EXTRA`.
- Se mantiene bloqueo de autosupervision para usuarios `PRACTICANTE + SUPERVISOR`.

### Errores pendientes

- La base local no tiene actualmente jornadas con `flag_horas_extra = 1`; falta prueba manual en navegador generando o corrigiendo una jornada real que active ese flag.
- No se implemento aun un flujo avanzado para rechazar horas extra ni un historial separado de aprobaciones.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\asistencia_gestion.php`
- `C:\xampp\php\php.exe -l admin\asistencia.php`
- `C:\xampp\php\php.exe -l supervisor\asistencia.php`
- Prueba PHP CLI sin escritura: `asistencia_gestion_aprobar_horas_extra(0, '0', '')` rechazo `Debe indicar un motivo de aprobacion.`
- Consulta MySQL local de solo lectura: `SELECT COUNT(*) AS jornadas_horas_extra FROM jornadas_asistencia WHERE flag_horas_extra = 1;` respondio `0`.

### Siguiente paso sugerido

Probar en navegador la aprobacion de horas extra cuando exista una jornada con `flag_horas_extra = 1`; despues avanzar a justificacion/resolucion de ausencias esperadas o a edicion/anulacion logica de excepciones de horario.

## 2026-06-03 - Fase 2.11 justificacion/resolucion inicial de ausencias esperadas

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR resuelvan una jornada generada como ausencia esperada, sin crear schema nuevo y conservando `flag_ausencia_esperada = 1` como trazabilidad.

### Archivos modificados

- `includes/asistencia_gestion.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se reutilizo la pantalla existente `admin/asistencia.php` y `supervisor/asistencia.php`.
- Se agrego la accion `resolver_ausencia_esperada` en `includes/asistencia_gestion.php`.
- La resolucion solo aplica a jornadas con `flag_ausencia_esperada = 1`.
- `JUSTIFICADA` deja la jornada como `OBSERVADA` y mantiene `horas_calculadas = 0`.
- `NO_JUSTIFICADA` deja la jornada como `INCOMPLETA` y mantiene `horas_calculadas = 0`.
- `PENDIENTE_REVISION` deja la jornada como `PENDIENTE_REVISION` y mantiene `horas_calculadas = 0`.
- La resolucion exige motivo obligatorio y conserva trazabilidad en `observacion`.
- La accion se audita como `CORREGIR_ASISTENCIA`.
- Se mantiene bloqueo de autosupervision para usuarios `PRACTICANTE + SUPERVISOR`.

### Errores pendientes

- La base local no tiene actualmente jornadas con `flag_ausencia_esperada = 1`; falta generar una ausencia esperada real y probar la resolucion manual en navegador.
- No se implemento aun historial separado de resoluciones ni carga de adjuntos/documentos justificativos.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\asistencia_gestion.php`
- `C:\xampp\php\php.exe -l admin\asistencia.php`
- `C:\xampp\php\php.exe -l supervisor\asistencia.php`
- Prueba PHP CLI sin escritura: `asistencia_gestion_resolver_ausencia_esperada(0, 'INVALIDA', '')` rechazo `Resultado de ausencia no permitido.`
- Consulta MySQL local de solo lectura: `SELECT COUNT(*) AS ausencias_esperadas FROM jornadas_asistencia WHERE flag_ausencia_esperada = 1;` respondio `0`.

### Siguiente paso sugerido

Probar en navegador la generacion y resolucion de ausencias esperadas; despues avanzar a edicion/anulacion logica de excepciones de horario.

## 2026-06-03 - Fase 2.12 edicion/anulacion logica de excepciones de horario

### Objetivo trabajado

Permitir que ADMIN y SUPERVISOR anulen logicamente excepciones puntuales de horario desde la gestion existente de practicantes, sin borrar registros ni crear schema nuevo.

### Archivos modificados

- `includes/excepciones_horario.php`
- `includes/horarios_practicante.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo la edicion mediante el flujo existente: reingresar la misma fecha y tipo actualiza la excepcion por `ON DUPLICATE KEY UPDATE`.
- Se agrego `excepciones_horario_obtener()` para validar la excepcion por ID.
- Se agrego `excepciones_horario_anular()` para marcar `activo = 0` con motivo en `observacion`.
- La anulacion valida que la excepcion pertenezca al horario vigente del practicante seleccionado.
- La UI de excepciones muestra observacion, estado y accion de anulacion por fila.
- No se borra fisicamente la excepcion.
- La accion se audita como `EDITAR_PRACTICANTE`.
- Se mantiene bloqueo de autosupervision para usuarios `PRACTICANTE + SUPERVISOR`.

### Errores pendientes

- Falta prueba manual en navegador anulando una excepcion activa real.
- No se implemento historial separado de versiones de una misma excepcion; la edicion mantiene el comportamiento actual de actualizar la fila unica por horario, fecha y tipo.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\excepciones_horario.php`
- `C:\xampp\php\php.exe -l includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l admin\practicantes.php`
- `C:\xampp\php\php.exe -l supervisor\practicantes.php`
- Prueba PHP CLI sin escritura: `excepciones_horario_anular(0, 0, '')` rechazo `Excepcion no valida.`
- Consulta MySQL local de solo lectura: `SELECT COUNT(*) AS excepciones_total, SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS excepciones_activas FROM excepciones_horario_practicante;` respondio `1` total y `1` activa.

### Siguiente paso sugerido

Probar en navegador `admin/practicantes.php` o `supervisor/practicantes.php` anulando una excepcion activa; despues avanzar a reportes basicos de asistencia/avance.

## 2026-06-03 - Fase 2.13 reportes basicos de asistencia/avance

### Objetivo trabajado

Implementar una pantalla inicial de reportes web de solo lectura para ADMIN y SUPERVISOR, con resumen de asistencia, horas y avance por practicante/practica.

### Archivos modificados

- `includes/reportes_gestion.php`
- `admin/reportes.php`
- `supervisor/reportes.php`
- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/reportes_gestion.php` como modulo compartido para mantener entradas delgadas por rol.
- `admin/reportes.php` exige rol `ADMIN`.
- `supervisor/reportes.php` exige rol `SUPERVISOR`.
- Se agregaron enlaces desde los dashboards de ADMIN y SUPERVISOR.
- El reporte permite filtrar por rango de fechas, estado de practica y practicante.
- El resumen muestra practicas filtradas, horas del periodo, avance global, jornadas pendientes y ausencias esperadas.
- La tabla muestra horas totales, avance, horas del periodo, conteo de jornadas y alertas por practica.
- En rol SUPERVISOR, si el usuario tambien es practicante, se excluye su propia practica del reporte operativo.
- No se implemento exportacion XLSX real en esta fase.

### Errores pendientes

- Falta prueba manual en navegador con sesiones reales ADMIN/SUPERVISOR.
- No se implementaron reportes avanzados por institucion, sede, carrera, sexo, rango etario ni exportacion XLSX.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\reportes_gestion.php`
- `C:\xampp\php\php.exe -l admin\reportes.php`
- `C:\xampp\php\php.exe -l supervisor\reportes.php`
- `C:\xampp\php\php.exe -l admin\dashboard.php`
- `C:\xampp\php\php.exe -l supervisor\dashboard.php`
- Prueba PHP CLI de carga: `function_exists('reportes_gestion_practicas')` respondio `FUNCION_OK`.
- Consulta MySQL local de solo lectura: practicas activas respondio `1`.
- Consulta MySQL local de solo lectura: horas sumables del periodo 2026-05-04 a 2026-06-03 respondio `4.47`.

### Siguiente paso sugerido

Probar en navegador `admin/reportes.php` y `supervisor/reportes.php`; despues avanzar a dashboard avanzado o auditoria visible.

## 2026-06-03 - Fase 2.14 auditoria visible ADMIN

### Objetivo trabajado

Implementar una vista inicial de auditoria para ADMIN, de solo lectura, con filtros basicos y resumen de eventos registrados en `auditoria_cambios`.

### Archivos modificados

- `admin/auditoria.php`
- `admin/dashboard.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- La vista exige rol `ADMIN`.
- Se agrego acceso desde la tarjeta `Auditoria` del dashboard ADMIN.
- Se implementaron filtros por rango de fechas, evento, tabla afectada y busqueda simple.
- Se muestran metricas de eventos listados, eventos de seguridad, eventos funcionales y usuarios actores.
- La tabla muestra fecha, evento, actor, rol contexto, tabla, registro, IP y resumen de valores.
- Los valores sensibles con claves asociadas a password, token o hash se muestran redactados.
- La pantalla es solo lectura; no permite editar, borrar ni exportar auditoria.
- No se modifico schema ni se agregaron tablas.

### Errores pendientes

- Falta prueba manual en navegador con sesion real ADMIN.
- No se implemento exportacion de auditoria ni detalle completo paginado mas alla del limite inicial de 200 eventos.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l admin\auditoria.php`
- `C:\xampp\php\php.exe -l admin\dashboard.php`
- Carga PHP CLI de `admin/auditoria.php` sin error fatal.
- Consulta MySQL local de solo lectura: auditoria reciente respondio `86` eventos en el rango 2026-05-27 a 2026-06-03.
- Consulta MySQL local de solo lectura: eventos distintos respondio `14`.

### Siguiente paso sugerido

Probar en navegador `admin/auditoria.php`; despues avanzar a dashboard avanzado o exportacion XLSX segun prioridad.

## 2026-06-03 - Fase 2.15 dashboard avanzado inicial

### Objetivo trabajado

Agregar metricas operativas reales a los dashboards ADMIN y SUPERVISOR, reutilizando datos existentes sin crear nuevas tablas ni implementar graficos todavia.

### Archivos modificados

- `includes/dashboard_metricas.php`
- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/referencias/GUIA_PRUEBAS_XAMPP.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se creo `includes/dashboard_metricas.php` como modulo compartido de consultas agregadas.
- ADMIN y SUPERVISOR muestran practicantes activos, practicas activas, horas de los ultimos 30 dias, avance global, pendientes de revision y ausencias esperadas.
- ADMIN muestra ademas usuarios activos y eventos de auditoria de las ultimas 24 horas.
- En rol SUPERVISOR, si el usuario tambien es practicante, las metricas excluyen su propia practica.
- Los dashboards conservan sus tarjetas de navegacion existentes.
- No se implementaron graficos, segmentacion avanzada ni exportacion en esta fase.

### Errores pendientes

- Falta prueba manual en navegador con sesiones reales ADMIN/SUPERVISOR.
- Las metricas son agregadas globales iniciales; quedan pendientes filtros visuales por institucion, sede, carrera, area, sexo y rangos etarios.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l includes\dashboard_metricas.php`
- `C:\xampp\php\php.exe -l admin\dashboard.php`
- `C:\xampp\php\php.exe -l supervisor\dashboard.php`
- Prueba PHP CLI de carga: `function_exists('dashboard_metricas_resumen')` respondio `METRICAS_OK`.
- Consulta MySQL local de solo lectura: practicantes activos respondio `1`.
- Consulta MySQL local de solo lectura: horas sumables del periodo 2026-05-04 a 2026-06-03 respondio `4.47`.

### Siguiente paso sugerido

Probar en navegador `admin/dashboard.php` y `supervisor/dashboard.php`; despues avanzar a exportacion XLSX o reportes avanzados segun prioridad.

## 2026-06-04 - Mejora visual y funcional Dashboard Administrador

### Objetivo trabajado

Implementar una version institucional, moderna y responsive del Dashboard Administrador de e-practicas, usando como referencia visual `assets/img/Dashboard_Administrador.png`, el logo transparente `assets/brand/logo_rojo_blanco.png` y datos reales desde la BBDD mediante PDO.

### Archivos modificados

- `admin/dashboard.php`
- `includes/dashboard_metricas.php`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- El acceso sigue restringido por backend a rol `ADMIN` con `auth_requiere_rol('ADMIN')`.
- Se reemplazo el dashboard ADMIN basico por un layout con sidebar azul oscuro, logo transparente, topbar, KPIs, paneles visuales, tabla de registros recientes y alertas.
- Se uso `asset('brand/logo_rojo_blanco.png')`; no se uso el logo antiguo ni una version con fondo blanco.
- No se agregaron librerias nuevas de graficos; los graficos iniciales se representan con HTML/CSS y datos reales.
- Se mantuvieron rutas internas con `url()` y assets con `asset()`.
- Se amplio `includes/dashboard_metricas.php` sin romper las funciones compartidas usadas por SUPERVISOR.

### Metricas conectadas a BBDD

- Practicantes activos y practicas activas.
- Horas registradas totales y horas de los ultimos 30 dias.
- Instituciones vinculadas a practicas activas.
- Avance promedio por practica y avance global ponderado.
- Jornadas pendientes de revision y ausencias esperadas.
- Usuarios activos y eventos de auditoria de las ultimas 24 horas.
- Seguimiento semanal de horas y jornadas.
- Distribucion por estado de practica.
- Practicantes por area vigente.
- Registros recientes de asistencia.
- Alertas por fichas incompletas, jornadas en revision, ausencias esperadas y horas extra pendientes.

### Errores pendientes

- Falta prueba manual en navegador con sesion real ADMIN, incluyendo vista responsive.
- Los comparativos tipo `vs mes anterior` quedan pendientes para una etapa posterior; no se hardcodearon.
- Los filtros interactivos del dashboard por periodo, institucion, sede, carrera, area, sexo o rangos etarios quedan pendientes.
- La exportacion XLSX/PDF sigue pendiente.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\includes\dashboard_metricas.php`
- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- Prueba PHP CLI del helper: `dashboard_metricas_admin_panel()` respondio `PANEL_OK=7` y `ALERTAS=4`.
- Carga PHP CLI de `admin/dashboard.php` con sesion ADMIN simulada genero HTML sin error fatal.
- Consulta MySQL local de solo lectura: instituciones vinculadas respondio `1`, jornadas recientes `3` y auditoria 24h `30`.

### Siguiente paso sugerido

Probar en navegador `http://localhost/asistencia/admin/dashboard.php` con usuario ADMIN y revisar responsive; despues avanzar a filtros interactivos del dashboard o exportacion XLSX segun prioridad.

## 2026-06-04 - Iteracion visual premium Dashboard Administrador

### Objetivo trabajado

Acercar visualmente `admin/dashboard.php` a la referencia `assets/img/Dashboard_Administrador.png`, manteniendo permisos, sesiones, rutas, consultas PDO y metricas reales existentes.

### Archivos modificados

- `admin/dashboard.php`
- `assets/css/admin-dashboard.css`
- `assets/js/admin-dashboard.js`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo `auth_requiere_rol('ADMIN')` como control de acceso.
- Se mantuvieron las metricas reales desde `includes/dashboard_metricas.php`; no se duplico logica de negocio en JavaScript.
- Se reemplazaron iconos con letras por Bootstrap Icons.
- Se separo el CSS visual en `assets/css/admin-dashboard.css`.
- Se creo `assets/js/admin-dashboard.js` para inicializar graficos Chart.js con datos entregados por PHP mediante `json_encode`.
- Se mantuvo el logo transparente `assets/brand/logo_rojo_blanco.png` directamente sobre el sidebar azul oscuro.
- No se agregaron rutas nuevas ni enlaces a modulos inexistentes.

### Metricas conectadas a BBDD

- Practicantes activos y practicas activas.
- Horas registradas totales y horas de los ultimos 30 dias.
- Instituciones vinculadas.
- Avance promedio y avance global.
- Pendientes de revision y ausencias esperadas.
- Usuarios activos y auditoria 24h.
- Seguimiento semanal de horas y jornadas.
- Distribucion por estado de practica.
- Practicantes por area.
- Registros recientes.
- Alertas operativas.

### Librerias usadas

- Bootstrap 5 ya usado por el proyecto.
- Bootstrap Icons mediante CDN solo en `admin/dashboard.php`.
- Chart.js mediante CDN solo en `admin/dashboard.php`.

### Errores pendientes

- Falta prueba manual en navegador con sesion real ADMIN y revision responsive.
- Si el equipo no tiene internet local, Bootstrap Icons y Chart.js no cargaran desde CDN; quedaria pendiente empaquetarlos localmente en `assets`.
- Los filtros visuales de busqueda/periodo se mantienen deshabilitados hasta implementar filtrado real.
- Comparativos contra mes anterior y exportacion XLSX/PDF siguen pendientes.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- `cmd /c node --check assets\js\admin-dashboard.js`
- Carga PHP CLI de `admin/dashboard.php` con sesion ADMIN simulada genero HTML sin error fatal.
- La carga simulada confirmo enlaces a `admin-dashboard.css`, `admin-dashboard.js`, Bootstrap Icons y Chart.js.

### Siguiente paso sugerido

Probar visualmente `http://localhost/asistencia/admin/dashboard.php` como ADMIN en escritorio y movil; despues avanzar a filtros interactivos reales del dashboard o exportacion XLSX.

## 2026-06-04 - Fase 2.16 filtros interactivos Dashboard ADMIN

### Objetivo trabajado

Implementar filtros reales del Dashboard ADMIN usando parametros `GET`, catalogos existentes y consultas PDO, sin cambiar permisos, sesiones, login, schema ni reglas de negocio.

### Archivos modificados

- `admin/dashboard.php`
- `includes/dashboard_metricas.php`
- `assets/css/admin-dashboard.css`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Los filtros viajan por `GET` para permitir recargar y compartir la URL filtrada.
- Se agregaron filtros por periodo, rango manual, busqueda simple, institucion, sede, carrera, area y estado de practica.
- La busqueda simple revisa practicante, correo, institucion, sede, carrera y area vigente.
- Las metricas, graficos, registros recientes y alertas se recalculan desde BBDD mediante PDO.
- `assets/js/admin-dashboard.js` sigue limitado a renderizar graficos con datos entregados por PHP mediante `json_encode`.
- No se modifico schema ni se crearon APIs separadas.

### Metricas conectadas a BBDD

- KPIs de practicantes/practicas activas filtrados por entidad.
- Horas del periodo filtrado.
- Pendientes, ausencias, registros recientes y alertas filtrados por periodo y entidad.
- Distribucion por estado, practicantes por area y seguimiento del periodo filtrado.
- Catalogos reales de instituciones, sedes, carreras, areas y estados de practica.

### Errores pendientes

- Falta prueba manual en navegador con sesion real ADMIN.
- Los filtros de sede muestran todas las sedes activas; queda pendiente filtrado dependiente por institucion en frontend.
- No se implementaron comparativos contra mes anterior.
- No se implemento exportacion XLSX/PDF.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\includes\dashboard_metricas.php`
- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- `cmd /c node --check assets\js\admin-dashboard.js`
- Prueba PHP CLI de `dashboard_metricas_resumen('ADMIN', filtros)` y `dashboard_metricas_admin_panel(filtros)` respondio sin error.
- Carga PHP CLI de `admin/dashboard.php` con sesion ADMIN simulada y parametros `GET` filtrados genero HTML sin error fatal.

### Siguiente paso sugerido

Probar en navegador `http://localhost/asistencia/admin/dashboard.php` filtrando por periodo, busqueda, institucion, sede, carrera, area y estado; despues avanzar a comparativos reales del dashboard o exportacion XLSX.

## 2026-06-04 - Fase 2.17 comparativos y UX responsive Dashboard ADMIN

### Objetivo trabajado

Agregar comparativos reales contra periodo anterior al Dashboard ADMIN y mejorar la UX/UI con logout en sidebar, menu lateral colapsable/hamburguesa, mejor iconografia y graficas mas modernas.

### Archivos modificados

- `admin/dashboard.php`
- `includes/dashboard_metricas.php`
- `assets/css/admin-dashboard.css`
- `assets/js/admin-dashboard.js`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego calculo de periodo anterior en backend con `dashboard_metricas_periodo_anterior()`.
- Se muestran tendencias en KPIs cuando la comparacion aplica; para metricas sin historico real se muestra etiqueta neutral.
- El grafico principal incorpora una serie tenue de periodo anterior.
- El sidebar ADMIN ahora incluye `Cerrar sesion` apuntando a `logout.php`.
- En escritorio el sidebar puede colapsarse y conservar estado visual en `localStorage`.
- En movil/tablet el sidebar se abre como menu lateral con boton hamburguesa y backdrop.
- Se mejoro la paleta visual con azul institucional, cian, verde, amarillo, rojo y violeta de apoyo.
- JavaScript sigue limitado a render visual y comportamiento no critico; permisos, sesiones y datos siguen en PHP/backend.

### Errores pendientes

- Falta prueba manual en navegador con sesion real ADMIN, incluyendo colapso, hamburguesa y logout.
- El selector de sedes sigue mostrando todas las sedes activas; queda pendiente hacerlo dependiente de institucion.
- La exportacion XLSX/PDF sigue pendiente.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- `C:\xampp\php\php.exe -l .\includes\dashboard_metricas.php`
- `cmd /c node --check assets\js\admin-dashboard.js`
- Prueba PHP CLI de periodo anterior y metricas comparativas respondio sin error.
- Carga PHP CLI de `admin/dashboard.php` sin filtros confirmo sidebar toggle, logout lateral y datos de periodo anterior para Chart.js.
- Carga PHP CLI de `admin/dashboard.php` con filtros `GET` confirmo render sin error fatal y comparativos visibles.

### Siguiente paso sugerido

Probar visualmente `http://localhost/asistencia/admin/dashboard.php` en escritorio y movil; despues avanzar a selector dependiente institucion-sede o exportacion XLSX.

## 2026-06-04 - Iteracion Dashboard ADMIN logout superior y densidad compacta

### Objetivo trabajado

Mejorar la primera visualizacion del Dashboard Administrador agregando salida directa en barra superior y compactando la escala visual para que KPIs y graficos principales ocupen menos alto al entrar.

### Archivos modificados

- `admin/dashboard.php`
- `assets/css/admin-dashboard.css`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo el logout del sidebar y se agrego un segundo acceso `Cerrar sesion` en la barra superior derecha.
- Ambos accesos usan la ruta centralizada `logout.php`.
- Se redujo la densidad visual del dashboard desde CSS, incluyendo sidebar, topbar, filtros, KPIs, paneles, tablas, alertas y alturas de graficos.
- Se ajusto el breakpoint de escritorio para evitar que los KPIs pasen prematuramente a dos filas en pantallas amplias.
- No se tocaron consultas PDO, permisos, sesiones, roles ni reglas de negocio.

### Errores pendientes

- Falta validacion visual manual en navegador para confirmar encaje exacto segun resolucion real del equipo.
- Si el navegador mantiene cache CSS, puede requerir recargar con cache limpia.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- `C:\xampp\php\php.exe -l .\includes\dashboard_metricas.php`
- `cmd /c node --check assets\js\admin-dashboard.js`
- Carga PHP CLI de `admin/dashboard.php` con sesion ADMIN simulada confirmo `topbar-logout`, `sidebar-logout` y `admin-dashboard.css`.

### Siguiente paso sugerido

Probar `http://localhost/asistencia/admin/dashboard.php` como ADMIN y confirmar que el logout superior aparece a la derecha y que los graficos principales quedan visibles al entrar.

## 2026-06-05 - Iteracion visual marcar asistencia PRACTICANTE

### Objetivo trabajado

Redisenar `practicante/marcar-asistencia.php` usando como referencia visual `assets/img/registro_asistencia_2.png`, manteniendo el sidebar institucional del Dashboard Administrador y sin solicitar RUT, DV ni nombre del practicante.

### Archivos modificados

- `practicante/marcar-asistencia.php`
- `assets/css/practicante-asistencia.css`
- `assets/js/practicante-asistencia.js`
- `docs/historial/referencias/CONTEXTO_PROYECTO.md`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se mantuvo `auth_requiere_rol('PRACTICANTE')`, CSRF y registro mediante `asistencia_registrar_marca()`.
- Se elimino la estructura HTML antigua mal formada que contenia `brand-mark<head>`.
- La vista ya no solicita RUT, DV ni nombre; usa la sesion autenticada del practicante.
- Se agrego sidebar azul oscuro con logo transparente `brand/logo_rojo_blanco.png`, navegacion real del practicante y logout.
- Se agregaron pasos visuales de jornada, botones de marca disponibles, horario pactado, estado actual, avance de horas, indicadores, avisos e historial reciente.
- Se creo CSS dedicado para la pantalla y JS minimo para sidebar responsive/reloj visual.
- No se cambio schema, permisos, secuencia de marcas ni reglas criticas de asistencia.

### Errores pendientes

- Falta validacion visual manual en navegador con sesion real PRACTICANTE.
- Bootstrap Icons sigue cargando por CDN; si el equipo no tiene internet local, los iconos pueden no mostrarse.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\practicante\marcar-asistencia.php`
- `cmd /c node --check assets\js\practicante-asistencia.js`
- Carga PHP CLI de `practicante/marcar-asistencia.php` con sesion PRACTICANTE local confirmo render HTML, `practicante-asistencia.css`, `practicante-asistencia.js`, sidebar y acciones de marca.

### Siguiente paso sugerido

Probar `http://localhost/asistencia/practicante/marcar-asistencia.php` como PRACTICANTE, revisar responsive y registrar una marca real para confirmar que el rediseño conserva la operacion.

## 2026-06-05 - Acceso visible a pre-registro ADMIN/SUPERVISOR

### Objetivo trabajado

Habilitar accesos visibles al pre-registro de practicantes desde el Dashboard Administrador y el Panel Supervisor, sin modificar la logica de alta ni permisos existentes.

### Archivos modificados

- `admin/dashboard.php`
- `supervisor/dashboard.php`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- En ADMIN se agrego `Nuevo practicante` al sidebar del dashboard, apuntando a `admin/crear-practicante.php`.
- En SUPERVISOR se agrego una tarjeta `Crear practicante` en el grid principal, apuntando a `supervisor/crear-practicante.php`.
- Se mantuvo el boton superior existente del Panel Supervisor.
- No se modifico `includes/practicante_registro.php`, formularios de pre-registro ni reglas de negocio.

### Errores pendientes

- Falta prueba manual en navegador con sesiones reales ADMIN y SUPERVISOR.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\admin\dashboard.php`
- `C:\xampp\php\php.exe -l .\supervisor\dashboard.php`
- Carga PHP CLI de `admin/dashboard.php` con sesion ADMIN simulada confirmo `admin/crear-practicante.php` y etiqueta `Nuevo practicante`.
- Carga PHP CLI de `supervisor/dashboard.php` con sesion SUPERVISOR simulada confirmo `supervisor/crear-practicante.php` y etiqueta `Crear practicante`.

### Siguiente paso sugerido

Probar `admin/dashboard.php` y `supervisor/dashboard.php`, abriendo los accesos visibles de pre-registro.

## 2026-06-05 - Seed demo local con 50 practicantes

### Objetivo trabajado

Crear una base demo mas diversa para mostrar el software funcionando, conservando los datos actuales de la BBDD local.

### Archivos modificados

- `database/seed_demo_practicantes_50.sql`
- `database/backup_asistencia_before_demo_20260605.sql`
- `docs/historial/referencias/PRUEBAS_LOCAL.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se genero respaldo previo de la base local antes de aplicar el seed.
- Se agregaron 50 practicantes ficticios con correos `nombreapellido@practicante.cl`.
- Cada practicante demo quedo con usuario activo y rol `PRACTICANTE`.
- Se distribuyeron los practicantes entre INACAP, Duoc UC, ENAC y Otra, con distintas sedes, carreras, tipos de practica y areas.
- Se crearon practicas, horarios, jornadas y marcas demo usando el marcador `DEMO_LOCAL_2026_50`.
- El seed no usa `DROP`, `TRUNCATE` ni `DELETE`.

### Resultado validado en BBDD local

- Total actual de practicantes: 52.
- Practicantes demo cargados: 50.
- Practicas demo: 50.
- Jornadas demo: 250.
- Marcas demo: 500.
- Distribucion demo: INACAP 30, Duoc UC 10, Otra 6, ENAC 4.

### Errores pendientes

- No hay errores pendientes del seed local.
- La base online no fue modificada.

### Pruebas realizadas

- Ejecucion de `database/seed_demo_practicantes_50.sql` contra la base local `asistencia`.
- Validacion SQL de practicantes, practicas, jornadas, marcas y distribucion por institucion.

### Siguiente paso sugerido

Probar la demostracion en navegador con `admin/dashboard.php`, `supervisor/dashboard.php` y login de algunos practicantes demo.

## 2026-06-05 - Plan de incorporacion de equipo

### Objetivo trabajado

Crear un documento guia para sumar mas desarrolladores y perfiles funcionales al proyecto, separando responsabilidades por area.

### Archivos modificados

- `docs/PLAN_INCORPORACION_EQUIPO.md`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se definieron areas de participacion: producto, RRHH, backend, base de datos, frontend UX/UI, diseno visual, QA y seguridad.
- Se incluyeron documentos oficiales de partida, stack tecnico, flujo de trabajo, checklist de entrega y backlog inicial por frente.
- Se mantuvo como referencia principal `docs/historial/referencias/CONTEXTO_PROYECTO.md`.

### Errores pendientes

- Falta asignar responsables reales por area.
- Falta convertir el backlog inicial en tareas priorizadas.

### Pruebas realizadas

- Revision documental del archivo creado.

### Siguiente paso sugerido

Crear una matriz de responsables y tareas iniciales para incorporar colaboradores por frente.

## 2026-06-05 - Ajuste densidad ADMIN practicantes

### Objetivo trabajado

Ajustar visualmente `admin/practicantes.php` al 80% para mejorar la lectura de la columna `Practica` en la tabla `Practicantes con practica activa`.

### Archivos modificados

- `includes/horarios_practicante.php`
- `docs/historial/PROGRESO.md`

### Decisiones aplicadas

- Se agrego una clase de pagina para distinguir la gestion por rol.
- Se aplico densidad compacta solo para la vista ADMIN.
- Se redujo tipografia y padding de tabla, controles y badges.
- Se agrego ancho minimo a la segunda columna para mejorar lectura de tipo de practica y carrera.
- No se modificaron consultas, permisos, sesiones ni reglas de horarios.

### Errores pendientes

- Falta validacion visual manual en navegador con la resolucion real del equipo.

### Pruebas realizadas

- `C:\xampp\php\php.exe -l .\includes\horarios_practicante.php`
- `C:\xampp\php\php.exe -l .\admin\practicantes.php`
- `C:\xampp\php\php.exe -l .\supervisor\practicantes.php`
- Carga PHP CLI de `admin/practicantes.php` con sesion ADMIN simulada confirmo clases `gestion-practicantes-admin` y `gestion-practicantes-content`.

### Siguiente paso sugerido

Probar `http://localhost/asistencia/admin/practicantes.php` en navegador y confirmar si la columna `Practica` queda legible sin scroll incomodo.

## 2026-10-13 - Fase 0: inventario de migración y despliegue

### Cambios realizados y propósito

- Agregué `docs/historial/mvc-tailwind/FASE_0_INVENTARIO.md` para documentar la base de Git, los flujos, las dependencias, la separación de entornos, el almacenamiento y las reglas actuales de horas.
- Agregué `docs/historial/mvc-tailwind/FASE_0_RUTAS.csv` para enumerar las rutas actuales, sus archivos y destinos. Es un inventario, no un mapa de autorización.
- Actualicé este registro para dejar trazabilidad de la fase 0. No modifiqué código de aplicación, configuración, datos ni uploads.
- Registré la instrucción del responsable: cada cambio futuro de código debe llevar comentarios explicativos de su propósito.

### Base de trabajo

- Árbol inicial limpio; no persistían las 13 modificaciones locales de la revisión anterior.
- `main` local estaba en `b214194`; tras consultar GitHub se sincronizó por fast-forward a `95fe890`.
- Se creó `codex/fase-0-inventario` desde el main actualizado. No se hizo commit, push ni despliegue en esta fase.

### Hallazgos y validación

- Se verificó el destino de los archivos puente y la existencia de los destinos enumerados en el CSV.
- Se confirmó que los uploads se escriben bajo APP_ROOT/uploads/practicantes/{id_practicante}/perfil o documentos.
- Se confirmó que foto y descarga buscan el archivo desde APP_ROOT y validan propiedad. No se abrieron documentos personales ni se consultaron datos productivos.
- No se encontró en el código una regla que genere uploads.751. No se eliminó ni fusionó ninguna carpeta.
- Se registraron las diferencias entre el enrutador del repositorio y el productivo compartido por el responsable.
- Se detectó PHP CLI 8.0.30 en XAMPP; la versión PHP seleccionada en Hostinger sigue pendiente de confirmar.
- Se documentó la diferencia entre la regla antigua de colación y el cálculo actual sin cambiar el comportamiento.

### Pendientes para cerrar fase 0

- Confirmar PHP de Hostinger.
- Confirmar la regla de colación y las tolerancias vigentes.
- Confirmar las rutas completas y el origen/uso de las dos carpetas de uploads productivas.
- El inventario local está preparado; la fase 0 queda pendiente de estas confirmaciones antes de cambios que dependan de ellas.

### Confirmaciones posteriores del responsable (2026-10-13)

- Actualicé `FASE_0_INVENTARIO.md` con PHP 8.3.35 en Hostinger, según la cabecera compartida por el responsable. Local conserva PHP CLI 8.0.30; alinear la compatibilidad sigue siendo parte de fase 1.
- Actualicé `CONTEXTO_PROYECTO.md` para reemplazar la regla antigua de descuento: una jornada de seis horas o más sin colación registrada debe quedar observada, contando el tiempo trabajado y sin descuento automático de una hora. No se modificó el cálculo PHP.
- Agregué a la especificación la acción de revisión de jornadas OBSERVADA/PENDIENTE_REVISION por ADMIN/SUPERVISOR, con validación de marcas, bloqueo de autosupervisión y auditoría. La implementación está prevista para fase 3; se propone CORREGIDA como estado técnico sin introducir un estado nuevo todavía.
- Registré que las carpetas productivas se llaman `uploads/` y `uploads.751/` y están en la raíz del proyecto/backend. El código revisado usa `uploads/`; no se confirmó el origen ni la redundancia de `uploads.751/`.
- Las tolerancias de atraso y horas extra conservan su comportamiento actual, sin atribuirles una nueva aprobación del responsable.
- El inventario y las decisiones funcionales quedan consolidados. La conciliación de metadata y archivos productivos sigue pendiente antes de cualquier intervención de almacenamiento en fase 7. No se eliminaron, fusionaron ni trasladaron archivos.
- Los cambios de esta actualización son exclusivamente documentales. No se hizo commit, push ni despliegue.

## 2026-10-13 - Fase 1: dependencias y carga central de configuración

### Cambios realizados y propósito

- Agregué composer.json a partir del manifiesto productivo, con PHP ^8.1 y las extensiones utilizadas por la aplicación. Incorporé composer.lock desde el adjunto y sincronicé metadatos con Composer sin cambiar las versiones ni referencias de los 33 paquetes.
- Agregué config/environment.php para cargar phpdotenv de forma inmutable y una vez por directorio, y app_env() para leer configuración conservando valores vacíos y variables del servidor.
- Reemplacé el parser manual en config/app.php y adapté config/database.php y config/mail.php al acceso común. APP_DEBUG y MAIL_DEBUG se desactivan en producción. Cada cambio de código incluye comentarios explicativos.
- Agregué .env.example sin secretos y exclusiones Git para variantes privadas de .env, uploads y cachés de pruebas.
- Agregué tests/configuration.php, restringido a CLI, y composer test:config para comprobar la configuración con temporales y procesos aislados, sin leer el .env real, conectar a MySQL ni enviar correo.
- Normalicé únicamente la sintaxis de MAIL_PASSWORD en el .env local: tenía espacios sin comillas. Se verificó que el valor no cambiara y no se mostró su contenido. No se tocó el .env de producción.
- Actualicé README, CONTEXTO_PROYECTO y agregué FASE_1_CONFIGURACION.md con decisiones, procedimiento y límites de validación. Los cambios de JSON se explican allí porque JSON no admite comentarios.

### Herramienta de validación

- Se descargó Composer en scratch/phase1, con autorización del responsable y checksum SHA-256 verificado; no se instaló globalmente.
- Composer sincronizó solo metadatos del lock con --no-install, --no-scripts y --no-plugins. Se ignoró el requisito PHP únicamente en esa operación porque la CLI local es 8.0.30. No se autoriza usar esa excepción para instalar o desplegar la aplicación.

### Validación y límites

- Composer valida manifiesto y lock. La licencia sigue pendiente de definición; no se inventó una licencia para silenciar su advertencia.
- Pasaron las 20 comprobaciones de configuración y php -l sobre los cinco archivos PHP agregados/modificados. El bootstrap cargó sin consultar la BBDD. La simulación de instalación de producción desde el lock no requiere instalar, actualizar ni eliminar paquetes del vendor actual; se omitió PHP solo para esta inspección local, no para un despliegue.
- Se verificó que los 33 paquetes conservan nombres, versiones y referencias source del lock productivo compartido.
- El chequeo de plataforma identifica PHP 8.0.30 local como incompatible con el mínimo 8.1. Las extensiones requeridas están disponibles o satisfechas por los polyfills del lock.
- No se instaló otro PHP ni se modificó XAMPP. La aplicación completa no se probó en PHP 8.3; se requiere validar la plataforma del hosting y configurar una versión PHP compatible en desarrollo antes de publicar.
- No se modificaron rutas, reglas de asistencia, datos, correos ni uploads. No se hizo commit, push ni despliegue.

## 2026-10-13 - Configuración de PHP 8.3.35 en XAMPP

- Verifiqué el paquete descargado por el responsable: PHP 8.3.35 x64 Thread Safe, con el módulo para Apache 2.4.
- Agregué un php.ini desde la plantilla nueva con extensiones del proyecto, límites de carga conservados, temporales de XAMPP y zona horaria America/Santiago. Actualicé las referencias PHP de httpd-xampp.conf y comenté los cambios.
- Preparé copias dentro de scratch, validé Apache y solicité permiso antes de aplicar los archivos fuera del proyecto. Creé el respaldo httpd-xampp.conf.before-php83-20261013 y conservé la carpeta PHP 8.0. No modifiqué PATH, MySQL, datos, .env, uploads ni producción.
- Composer verifica los requisitos completos y de producción con PHP 8.3.35 sin excepciones de plataforma. Pasaron las 20 pruebas de configuración, la revisión sintáctica de 152 archivos y la carga del bootstrap sin consultar BBDD. Apache -t dio Syntax OK antes y después de aplicar los archivos.
- Agregué ACTUALIZACION_PHP_LOCAL.md y actualicé FASE_1_CONFIGURACION.md para registrar preparación, validación, comandos y reversión.
- Apache estaba detenido y permanece detenido. Pendiente que el responsable lo inicie desde el panel y compruebe PHP 8.3.35 en la respuesta HTTP y los flujos con datos locales. No se instalaron paquetes, no se hizo commit, push ni despliegue.

## 2026-10-13 - .env local y fase 2: entrada compartida segura

### Cambios realizados y propósito

- Completé el .env local agregando 12 variables faltantes de la plantilla, sin sustituir valores existentes ni mostrarlos. Añadí comentarios para revisar configuración de MySQL y SMTP local. Verifiqué que continúa excluido de Git; el .env productivo no se modificó.
- Agregué config/routes.php con 37 rutas explícitas, roles y métodos. El diagnóstico SMTP no forma parte del catálogo. Los destinos de negocio se conservan, incluido temporalmente horario-dia.php como endpoint fijo.
- Agregué src/Http/Router.php y public/backend.php para normalizar rutas y localizar el backend por estructura relativa o variable del proceso. Reemplacé la inclusión de archivos desde REQUEST_URI en public/index.php.
- Unifiqué index.php de la raíz y trasladé su lógica de sesión/rol a Auth/HomeController.php para evitar recursión. Agregué una vista 404 conservando el diseño anterior.
- Agregué .htaccess local y endurecí public/.htaccess: protección previa de sensibles y carpetas privadas, bloqueo de uploads/variantes y diagnóstico, lista de formatos públicos y rewrite interno que conserva POST/query. /assets utiliza public/assets sin eliminar copias antiguas.
- Adapté la ruta activa del sidebar a la ruta lógica, manteniendo identidad visual e interacciones existentes.
- Agregué tests/routing.php, tests/routing-http.php y composer test:routing. Los archivos incluyen comentarios explicativos; JSON se documenta en FASE_2_ENRUTAMIENTO.md.
- Actualicé .env.example, README, CONTEXTO_PROYECTO y documentación de fases. Conservé los archivos ya staged y no cambié la selección del responsable en Git.

### Validación y ajuste de entorno

- Pasaron 162 comprobaciones de rutas y las 20 de configuración con PHP 8.3.35. Composer valida manifiesto y lock sin cambios de dependencias.
- Se probaron reglas reales con Apache temporal en localhost, en subcarpeta /asistencia y public como raíz independiente. Se comprobaron login/CSRF, aliases, raíz, 404, protección de archivos reales, sesión previa de rutas por rol, JSON/foto/descarga, integridad de CSS, POST, 405 y HEAD.
- La prueba HTTP detectó dependencias DLL de cURL que Apache no cargaba. Agregué su carga explícita en la configuración local de XAMPP con permiso y respaldo; Apache -t dio Syntax OK. No se reiniciaron servicios del usuario.
- Los servidores de prueba se cierran al terminar. No se autenticaron usuarios ni se enviaron correos, cargaron archivos o modificaron registros de negocio.
- La validación HTTP final pasó 68 comprobaciones en los dos escenarios. Los diez archivos PHP afectados pasan php -l; .env sigue ignorado y no versionado. Las comprobaciones iniciales de red se repitieron fuera del sandbox con permiso porque el aislamiento impedía acceder al servidor temporal.

### Límites y siguiente etapa

- No se eliminaron puentes ni se modificaron horas, permisos de negocio o uploads. Esos cambios pertenecen a fases posteriores.
- No se modificó Hostinger. Para activar el nuevo enrutador allí hay que desplegar backend y contenido público coordinadamente, incluidos backend.php y .htaccess, conservando el .env productivo. El ZIP solo de backend no reemplaza la entrada pública antigua.
- Sigue pendiente validar flujos autenticados con datos controlados y las reglas en el hosting real antes de publicar. No se hizo commit, push ni despliegue.

## 2026-10-13 - Rama y commit de fase 2

- Creé `fase-2-enrutamiento-seguro` desde `codex/fase-0-inventario`, usando el nombre sin prefijo solicitado por el responsable. La fase 1 ya estaba registrada en el commit e93be8b.
- Preparé un commit con la entrada compartida, el catálogo de 37 rutas, las reglas Apache, las pruebas y la documentación de la configuración local de PHP. Los comentarios del código explican los cambios.
- Verifiqué que .env, scratch y archivos de usuarios quedan fuera del commit. El .env local se conserva únicamente en el equipo.
- La validación registrada comprende 162 comprobaciones de rutas, 68 HTTP, 20 de configuración y la sintaxis de los diez PHP afectados. No se hicieron cambios de código adicionales durante la preparación del commit.
- No se solicita ni se realiza push, Pull Request, merge o despliegue en este paso. Después de publicar y revisar la rama, corresponde hacer merge hacia main, sincronizar main local y crear la siguiente rama desde esa base.

## 2026-10-13 - Fase 3: autorización, auditoría y consistencia funcional

- Verifiqué que la rama creada por el responsable parte del merge de fase 2 en main. Trabajé en esa rama, sin crear otra ni modificar commits anteriores.
- Agregué autorización compartida por práctica para impedir autosupervisión y operaciones de roles no autorizados. Permisos validan también el ID del revisor contra la sesión. Acompañamiento oculta el registro propio y vuelve a comprobarlo al guardar.
- Corregí catálogo e identificadores de auditoría de permisos. Aprobar/rechazar permisos, guardar acompañamiento, revisar/cambiar estado/aprobar extras y corregir/anular marcas o jornadas exigen su auditoría antes de confirmar la transacción. La aprobación de permisos revierte también las excepciones si alguna falla.
- Apliqué la regla confirmada: seis horas o más sin colación quedan observadas y reconocen el tiempo dentro del turno sin deducción ficticia. Agregué CORREGIDA / Marcar como revisada, con validación de marcas y motivo, conservando observaciones y sin aprobar extras implícitamente.
- Eliminé la sugerencia arbitraria de media hora extra y limité aprobaciones al exceso calculado. Permití ajustar aprobaciones anteriores incompatibles; cambiar marcas retira la aprobación previa para revisarla nuevamente.
- Unifiqué los totales de acompañamiento con base y extras aprobadas; conservé las horas previas al refrescar el resumen después de marcar. Corregí textos dañados de MisDatos y respuestas AJAX de guardado parcial de acompañamiento.
- Agregué bloqueos de jornada para serializar marcaje, corrección, revisión y anulación. La creación usa la clave única práctica/fecha de forma idempotente. Acompañamiento bloquea la práctica al guardar para capturar el valor anterior coherente.
- Agregué comentarios explicativos en los cambios de código, tests/business.php, composer test:business y FASE_3_CORRECCIONES.md; actualicé README y contexto. El cambio del JSON se documenta porque ese formato no admite comentarios.

### Verificación y límites

- Pasaron 100 comprobaciones de negocio con PDO SQLite en memoria y datos ficticios, 20 de configuración y 162 de rutas con PHP 8.3.35. Los diez PHP afectados/agregados pasan revisión de sintaxis; Composer valida manifiesto y lock, conservando la advertencia previa sobre licencia sin definir. Git diff --check no detecta errores de espacios.
- Las pruebas ejecutan servicios reales con adaptaciones de dialecto SQL y fallos deliberados para comprobar rollback. Cubren cálculos, autorización, revisión, extras, permisos, acompañamiento y duplicados secuenciales. No consultan ni modifican la base de datos del proyecto.
- Pendiente comprobar flujos autenticados en navegador y dos marcajes simultáneos en MySQL/MariaDB. SQLite no valida FOR UPDATE, el comportamiento concurrente de InnoDB ni las restricciones del esquema productivo. El esquema del repositorio incluye InnoDB y la clave única de jornada requerida.
- No se recalcularon jornadas históricas masivamente, no se modificaron .env, uploads, puentes, dependencias o Hostinger. No se hizo commit, push ni despliegue. Antes de publicar, revisar los escenarios manuales descritos en FASE_3_CORRECCIONES.md.

## 2026-10-13 - Fase 4: separación MVC de páginas compartidas

- Confirmé main limpio en 4f04ed3, con fase 3 integrada, y creé `fase-4-separacion-mvc` desde esa base, sin prefijo según la preferencia del responsable. La escritura de la referencia Git requirió aprobación del entorno.
- Separé siete páginas que todavía se procesaban y renderizaban dentro de servicios/modelos: asistencia, permisos, reportes, pre-registro, carga masiva, practicantes/horarios y catálogos. Los controladores de ADMIN/SUPERVISOR preparan parámetros y ejecutan controladores compartidos antes del layout; las vistas por rol componen las plantillas compartidas.
- Trasladé los manejadores HTTP de asistencia y catálogos al controlador correspondiente. Conservé autenticación, CSRF, rutas y reglas de negocio; la descarga de plantillas permanece antes del HTML. Las redirecciones de gestión de horarios ya no se ejecutan después de mostrar el layout.
- Trasladé los campos de horario y selectores/etiquetas de pre-registro y MisDatos a componentes de vista, con sus estilos y escape originales.
- Eliminé dependencias de archivos puente en controladores Admin/Supervisor sin borrarlos físicamente. Preparé el contador lateral en LayoutContext para las pantallas privadas y retiré su consulta del sidebar.
- Preparé desgloses de asistencia, opciones relacionadas de catálogos y acumulados de reportes en el controlador antes del HTML; agregué mensajes controlados para errores de carga de los dos primeros.
- Agregué comentarios explicativos, `tests/mvc.php`, `composer test:mvc` y `FASE_4_SEPARACION_MVC.md`; actualicé README y contexto y marqué el plan antiguo para aclarar su numeración distinta.
- Registré el nuevo contexto del cliente sobre compensaciones en dos días y recuperaciones distribuidas. No se implementaron esas reglas: siguen pendientes sus definiciones y el diseño correspondiente.

### Validación y límites

- Pasaron 199 comprobaciones MVC en procesos aislados: 13 entradas por rol, GET/POST con CSRF inválido, autorización antes del HTML, layout único, lecturas previas al render, modales pendientes, detalle de reportes y componentes con escape/selección. Se usaron datos ficticios y proveedores de lectura sustituidos, sin conectar a MySQL o escribir registros reales.
- Pasaron también las 100 comprobaciones de negocio, 20 de configuración y 162 de rutas. Los 66 PHP afectados/agregados pasan revisión sintáctica. Composer valida manifiesto y lock, con la advertencia previa de licencia pendiente; git diff --check queda limpio.
- Pendiente la prueba interactiva en navegador con datos locales controlados, especialmente guardado/redirecciones, importaciones, modales y MisDatos. Las pruebas CLI no validan aspecto visual, interacción JavaScript, SMTP, escrituras MySQL ni concurrencia.
- No modifiqué .env, uploads, base de datos, recursos visuales, dependencias instaladas ni Hostinger. No eliminé puentes ni hice commit, push o despliegue.
- Siguiente etapa: fase 5 para completar la independencia del legado y retirar puentes, incluido el endpoint de horario diario; después, Tailwind y despliegue según el inventario vigente.

## 2026-10-13 - Fase 5: retiro del legado y endpoint MVC

- Verifiqué main limpio en 5a689fc después del merge de fase 4 y creé `fase-5-retiro-puentes` desde esa base, conservando la preferencia de nombres sin prefijo.
- Verifiqué por contenido los puentes antes de retirarlos. Eliminé 57 puentes: 33 páginas de autenticación/roles, 23 módulos/layouts de includes y el index raíz que reenviaba a public. Las 37 URLs siguen declaradas en el catálogo y no necesitan archivos físicos.
- Trasladé el endpoint de horario diario a `src/Controllers/Practicante/HorarioDiaController.php`, conservando su URL, acceso PRACTICANTE, GET/HEAD, mensajes y campos JSON. Agregué validación de parámetros arrays, bytes nulos y fechas inexistentes, con error HTTP 400.
- Retiré diagnostico_correo.php, bloqueado desde fase 2 y fuera del catálogo; no se ejecutó ni se envió correo. Son 59 archivos retirados en total, documentados individualmente en FASE_5_ARCHIVOS_RETIRADOS.csv con destino y motivo en lugar de comentarios imposibles de conservar en archivos eliminados.
- Retiré INCLUDES_PATH, sin consumidores, y ajusté ASSETS_PATH a public/assets. No se movieron ni eliminaron las copias de recursos, uploads o archivos de usuarios.
- Actualicé comentarios del rewrite local y el destino esperado del endpoint en las pruebas de rutas, conservando intacto el inventario histórico. Agregué tests/legacy-removal.php, composer test:legacy y recorrido HTTP de todas las rutas.
- Actualicé README, contexto, numeración del plan histórico y FASE_5_RETIRO_PUENTES.md. Los cambios de código tienen comentarios explicativos; JSON y eliminaciones se explican en la documentación.

### Validación y publicación

- Pasaron 341 comprobaciones de retiro/API, 199 MVC, 100 de negocio, 20 de configuración y 162 de rutas con PHP 8.3.35. La API se probó con proveedores sintéticos en procesos aislados, incluyendo autorización, datos inválidos y error interno sin revelar detalles.
- Apache temporal pasó 200 comprobaciones HTTP en subcarpeta /asistencia y public como raíz, recorriendo las 37 URLs sin los archivos puente. Se comprobó protección de privados, sesión previa, POST/CSRF, métodos, HEAD y recursos. Se ejecutó con aprobación del entorno y se cerró al terminar, sin reiniciar los servicios del responsable.
- Los seis PHP agregados/modificados pasan sintaxis. Composer valida manifiesto/lock con la advertencia previa de licencia pendiente. Git diff --check queda limpio y .env/scratch continúan ignorados.
- No se autenticaron usuarios reales ni modificaron datos de negocio, correo, documentos, .env, XAMPP o Hostinger. No se hizo commit, push ni despliegue. Pendiente probar flujos autenticados en navegador con datos locales controlados.
- La entrada pública productiva antigua depende de archivos ahora retirados. Antes de publicar, sustituirla por el contenido público nuevo y desplegar backend coordinadamente, conservando .env/uploads y preparando respaldo/reversión. Extraer un ZIP encima no elimina los puentes antiguos: retirar solo el inventario en ese despliegue coordinado. No subir únicamente este backend con el index público anterior.
- Siguiente etapa: fase 6 de Tailwind y validación visual por pantalla. Las reglas nuevas de compensaciones y recuperación distribuida siguen pendientes de diseño e implementación explícita.

## 2026-10-13 - Fase 6: migración visual a Tailwind

- Actualicé main mediante avance rápido al merge de fase 5, 0ab2eef, y creé `fase-6-migracion-tailwind`, conservando la preferencia de nombres sin prefijo. La actualización remota y escritura de referencias se realizaron con aprobación del entorno.
- Sustituí Bootstrap CSS/JavaScript y Tailwind CDN por Tailwind 3.4.17 compilado localmente. Conservé Bootstrap Icons 1.11.3 como iconos independientes, ahora con CSS y fuentes locales. Agregué manifiesto y lock de npm, configuración, fuentes CSS y scripts de compilación; node_modules queda ignorado.
- Migré las vistas compartidas, componentes y navegación conservando formularios, rutas, CSRF y reglas de negocio. Agregué componentes de estilos y tonos con clases completas para que la compilación incluya las variantes dinámicas. Conservé identidad visual y estilos particulares después de Tailwind.
- Agregué JavaScript local para diálogos nativos, menú móvil con control de foco, desplegables, secciones plegables y cierre de avisos. Adapté los campos condicionales y preservé los selectores propios de acompañamiento. Los cambios de código incluyen comentarios explicativos; JSON y recursos generados se explican en FASE_6_TAILWIND.md.
- Protegí node_modules, resources y scripts en ambos escenarios Apache. Actualicé README, branding, contexto y documentación de la fase. No cambié dependencias PHP, servicios, controladores de negocio ni esquema de datos.

### Validación y límites

- Pasaron siete pruebas de navegador en Edge con datos ficticios: 25 pantallas o variantes en escritorio y móvil, con 50 capturas bajo scratch ignorado. Se verificaron recursos locales, ausencia de desbordes, modales, foco, menú móvil, desplegables, campos condicionales y rejillas. Revisé visualmente capturas representativas y corregí contraste y comportamiento al cambiar de tamaño.
- Pasaron 199 comprobaciones MVC, 100 de negocio, 20 de configuración, 162 de rutas y 343 de retiro/API: 824 en total. Los 31 PHP agregados/modificados pasan sintaxis con PHP 8.3.35. Composer valida manifiesto y lock con su advertencia previa sobre licencia.
- Apache temporal pasó 220 comprobaciones HTTP en subcarpeta y raíz pública, incluyendo protección de fuentes y disponibilidad de los recursos compilados. Se cerró al terminar. El responsable inició después su Apache y MySQL; no los detuve ni reinicié.
- La compilación final reprodujo el mismo hash del CSS y git diff --check quedó limpio. npm muestra las tres versiones fijadas; la compilación conserva un aviso no bloqueante de antigüedad de Browserslist. Las descargas y pruebas de navegador que el aislamiento impedía ejecutar se repitieron con aprobación del entorno.
- Las pruebas no autentican usuarios reales, envían formularios ni modifican registros. Queda pendiente el recorrido con datos locales controlados, listados extensos, gráficas, escrituras MySQL, SMTP y otros navegadores. No modifiqué .env, uploads, configuración XAMPP o Hostinger. No hice commit, push ni despliegue.
- Siguiente etapa: fase 7 para preparar publicación coordinada de backend y entrada pública, revisar copias antiguas de assets y los archivos productivos, con respaldo y reversión. Las nuevas reglas de compensaciones y recuperación distribuida siguen pendientes de definición e implementación.

## 2026-10-14 - Fase 7: preparación del despliegue coordinado

- Confirmé en GitHub que fase 6 estaba subida y mergeada, y main local/remoto coincidían en 2356658. Creé `fase-7-despliegue-hostinger` desde main con el nombre sin prefijo solicitado; el árbol estaba limpio.
- Agregué generador CLI y verificador de publicaciones con lista positiva, manifiesto SHA-256, controladores requeridos, validación de entradas ZIP y dependencias de producción correspondientes al lock. Composer instala en una carpeta nueva bajo scratch, sin plugins/scripts ni dependencias de desarrollo; no modifica vendor local. Los cambios tienen comentarios explicativos.
- Preparé backend-rrhh.zip y software-rrhh.zip sin carpeta envolvente, con 332 archivos verificados. La raíz del backend empaquetado bloquea HTTP; la raíz local conserva su rewrite. El backend mantiene public para respetar ASSETS_PATH y el ZIP público lleva el mismo contenido para servirlo.
- Retiré 14 archivos de assets raíz después de comparar sus destinos públicos: 13 iguales y un app.css anterior a Tailwind. Registré rutas, hashes anteriores y actuales y motivos en FASE_7_ASSETS_RETIRADOS.csv. Public/assets no cambió. No eliminé carpetas ni documentos de usuarios.
- Agregué tests/release.php y FASE_7_DESPLIEGUE_HOSTINGER.md; actualicé README y contexto. La guía describe rutas reales, preservación de entorno/uploads, respaldo, actualización coordinada, revisión de reglas, retirada selectiva de restos y reversión. Los .htaccess productivos no se sustituyen automáticamente.

### Validación y límites

- Pasaron 531 comprobaciones de publicación: fuentes y hashes, inventario retirado, extracción en estructura equivalente al hosting, descubrimiento del backend y rechazo de cinco casos adversos (extra, faltante, contenido alterado, hash ZIP alterado y vendor de desarrollo).
- Las siete dependencias de producción instalaron desde el lock con PHP 8.3.35 y check-platform-reqs --no-dev correcto. Se comprobó autoload de PHPMailer/Dotenv sin PHPUnit. La primera instalación se interrumpió por restricciones de red/escritura del sandbox y se repitió con aprobación del entorno; los intentos incompletos quedan ignorados bajo scratch.
- Apache temporal pasó 220 comprobaciones HTTP con los ZIP extraídos, en raíz y subcarpeta explícita, más la denegación HTTP 403 del backend privado. Se cerró únicamente el proceso temporal. Pasaron otras 110 comprobaciones en el localhost del responsable tras retirar assets antiguos; el aislamiento exigió repetir esa consulta con aprobación.
- Pasaron 343 comprobaciones de retiro/API, 162 de rutas, 199 MVC, 100 de negocio y 20 de configuración: 824 en total. Los cuatro PHP nuevos pasan sintaxis y git diff --check queda limpio. El paquete final vuelve a pasar verificación y reproduce los hashes de archivos del paquete probado por HTTP.
- La salida final de esta sesión está en scratch/releases/20261014-134016-2356658d-331d9f, ignorada por Git; registra working_tree_dirty porque todavía no hay commit de fase 7. Antes de publicar la versión aprobada, regenerar desde su commit.
- No modifiqué .env, uploads, base de datos, configuración XAMPP o Hostinger; no reinicié Apache/MySQL del responsable ni envié correo o formularios válidos. No hice commit, push ni despliegue.
- Pendientes productivos: confirmar nombre exacto de carpeta pública/raíz documental, extensiones y reglas particulares; conciliar referencias de uploads/ uploads.751 sin eliminar contenido; realizar respaldo y pruebas autenticadas controladas antes de publicar. No hay acceso al hosting en esta sesión. Las nuevas reglas de compensaciones y recuperaciones distribuidas permanecen pendientes de definición e implementación.

## 2026-10-14 - Fase 8: pruebas autenticadas e integración

- Confirmé main limpio con fase 7 integrada en 48463df y creé `fase-8-validacion-flujos`. No hice commit, push, PR o despliegue; el responsable pidió gestionar GitHub manualmente.
- Agregué e2e-setup.php, e2e-router.php, e2e-state.php y e2e.test.cjs: base MariaDB nueva por ejecución, código separado bajo scratch, sesiones/formularios reales en Edge, uploads ficticios y SMTP local que únicamente captura example.test. No se carga .env habitual ni se consulta su base. Las herramientas incluyen comentarios explicativos.
- Agregué npm test:e2e, sin dependencias nuevas; actualicé README y contexto, y documenté escenarios, instrucciones y límites en FASE_8_VALIDACION_FLUJOS.md. JSON se explica en la guía porque no admite comentarios.
- Encontré y corregí una omisión funcional: la vista del dashboard ADMIN no cargaba admin-dashboard.js después de Chart.js. Agregué esa carga con comentario; la prueba ahora exige gráficas inicializadas y ausencia de errores JavaScript en las pantallas recorridas.
- Ajusté datos y acciones de pruebas sin cambiar reglas de negocio: recuperación en día libre y horario válido de sábado, evento APROBAR_PERMISO, elección del radio seguida de Continuar. El seed elimina BOM al leer SQL, alinea su zona horaria con PHP y admite ejecutar en distintos días de la semana. La lectura de resultados admite la tabla de acompañamiento todavía no creada para evitar fallos en cadena.

### Resultado y evidencia

- Pasaron once pruebas E2E: acceso/salida y 23 pantallas por los tres roles, perfil y carga/descarga PNG/PDF, solicitud/aprobación de permiso con auditoría y dos excepciones, marcaje/duplicado/CSRF, revisión CORREGIDA sin descontar colación inventada ni aprobar extras, alta con horario/activación, aislamiento de documentos, importación CSV sin duplicados y plantillas CSV/XLSX, reporte con estado/acumulado, acompañamiento AJAX, catálogo con autorización, multirrol y recuperación de contraseña con token de un solo uso.
- La ejecución final con npm test:e2e usa rrhh_e2e_145e720819a8 y conserva results.json, php.log y 23 capturas en scratch/phase8/145e720819a8. Capturó tres correos locales; no detecté errores PHP en el log. Revisé visualmente el dashboard con gráficas en una ejecución previa aprobada.
- Pasaron las siete pruebas visuales existentes (25 pantallas/variantes, 50 capturas) y las 824 comprobaciones PHP: 199 MVC, 100 negocio, 20 configuración, 162 rutas y 343 retiro/API. Los tres PHP nuevos y la vista modificada pasan sintaxis; Node valida JavaScript y git diff --check queda limpio. Composer valida con su aviso anterior de licencia.
- Regeneré el paquete en scratch/releases/20261014-150433-48463df4-60450c: 332 archivos verificados, siete dependencias de producción con requisitos correctos y 531 comprobaciones de publicación aprobadas. No contiene las pruebas ni sus datos. Antes de publicar, regenerar desde el commit aprobado.
- Se ejecutó con aprobación del entorno por acceso a sockets MariaDB, servidor local y Edge. Los procesos PHP/SMTP/navegador de las pruebas se cerraron al finalizar. No reinicié Apache/MySQL ni cambié XAMPP, .env, uploads habituales, datos reales o Hostinger.
- Las bases y copias ficticias de los intentos quedan identificadas por rrhh_e2e_* y scratch/phase8 para revisión; no eliminé bases automáticamente. Un primer seed interrumpido por BOM también creó una base con ese prefijo. Estas bases no son el entorno habitual ni deben publicarse.
- La validación local cubre los escenarios documentados. Quedan fuera SMTP TLS/autenticado del hosting, concurrencia de marcajes, HTTPS real, otros navegadores y determinadas variantes administrativas/importación XLSX; no afirmo cobertura exhaustiva. Los requisitos productivos de fase 7 y las nuevas reglas de compensaciones/recuperación distribuida continúan pendientes.

## 2026-10-14 - Correcciones operativas, entrega 1: cierres de jornadas

- Confirmé main limpio y sincronizado en ffb4bf6, merge de fase 8, y creé correccion-estados-jornadas desde esa base, sin prefijo. La escritura de la referencia requirió aprobación del entorno.
- Separé REVISADA de CORREGIDA en el selector de asistencia de ADMIN/SUPERVISOR. Ambos cierres exigen motivo y marcas completas/coherentes; conservan marcas, validaciones de rol y propiedad, y auditoría transaccional REVISAR_JORNADA con responsable, fecha y antes/después. No aprueban tiempo adicional automáticamente.
- Incluí REVISADA en estados acumulables, SQL de métricas/reportes/historial/marcaje y presentación por rol. Las extras históricas aprobadas compatibles se conservan al revisar. Las aprobaciones incompatibles requieren revisión expresa y no se eliminan automáticamente.
- Separé el historial de una observación pendiente: los informes dejan de contar/resaltar como pendiente una jornada REVISADA o CORREGIDA, conservando el texto original. Atrasos, otras incidencias reales y extras todavía pendientes siguen visibles por separado.
- Agregué comentarios explicativos y cobertura de negocio/MVC/E2E; documenté el nuevo plan de siete ramas y las decisiones del responsable en PLAN_CORRECCIONES_OPERATIVAS.md y CONTEXTO_PROYECTO.md. Las recuperaciones distribuidas y tiempo excepcional con vencimiento a siete días siguen pendientes para sus ramas.

### Validación y alcance

- Pasaron 859 comprobaciones PHP: 119 negocio (SQLite ficticio), 215 MVC, 20 configuración, 162 rutas y 343 retiro/API. Sintaxis correcta en los 13 PHP afectados; Node valida los scripts de pruebas y git diff --check queda limpio.
- Pasaron once recorridos E2E con MariaDB local y Edge en copia aislada: 23 pantallas, ambos cierres mediante formulario, reapertura explícita, conservación de marcas, separación de extras y reporte con REVISADA sin resaltado pendiente, además de los recorridos previos. Evidencia: scratch/phase8/113829d3fb4c, base rrhh_e2e_113829d3fb4c, results.json/php.log/capturas; tres mensajes SMTP capturados localmente y sin errores PHP en el log.
- Pasaron siete pruebas visuales de escritorio/móvil. Ajusté únicamente la sincronización del test de diálogo: el evento close que limpia la clase del body ocurre después de retirar open; ahora espera ambas condiciones. No cambié el JavaScript productivo para resolver esa carrera de la prueba.
- Compilé Tailwind; el CSS generado conserva el contenido anterior, sin cambio versionado. Se mantiene el aviso previo de Browserslist desactualizado. La CLI explícita es PHP 8.3.35.
- Al principio MariaDB estaba apagado: el responsable lo activó antes del recorrido aprobado. El navegador/sockets requirieron aprobación del entorno; no reinicié servicios ni modifiqué .env, uploads o registros habituales. Los datos E2E quedan en scratch/base ficticia para revisión.
- No se requiere migración de esquema: estado_jornada es VARCHAR(30). No recalculé/reclasifiqué jornadas históricas masivamente ni implementé las reglas de las otras seis entregas. No hice commit, push, PR ni despliegue.
- Esta verificación local no demuestra concurrencia InnoDB, entrega SMTP real ni comportamiento del hosting. Para probar manualmente: abrir asistencia como supervisor, seleccionar jornada observada/pendiente completa, elegir Revisado o Corregido y escribir motivo; comprobar estado/horas en asistencia, historial e informes y actor/fecha en auditoría.

## 2026-10-14 - Correcciones operativas, entrega 2: presentación de practicantes

- Confirmé main limpio/sincronizado en 6be673d y creé ajuste-responsive-practicantes desde esa base con aprobación del entorno.
- Reproduje los desbordes con fixtures pobladas, nombres/correos/documentos largos y solicitudes en tres estados. Antes, una ventana de 320 px tenía hasta 944 px de contenido en perfil; las tablas exigían desplazamiento aun cuando el documento completo no desbordaba. La validación anterior de listas vacías no cubría estos casos.
- Adapté cuatro tablas: historial de jornadas, marcaje reciente, solicitudes y horarios de Mis datos (componente compartido). Cada celda conserva su contenido en rrhh-cell-value y tiene data-label; encabezados/filas/celdas mantienen roles explícitos. Bajo 1280 px se apilan campos etiquetados; escritorio ajusta columnas y texto. Corregí durante inspección visual el procesamiento de atributos title con PHP para conservar HTML válido; la prueba comprueba que no quedan fragmentos visibles de atributos.
- Agregué ámbito rrhh-practicante en el layout, límites de controles y elementos flex/grid, ajuste de palabras largas y correo con title completo. Los documentos aprovechan el ancho y separan el botón de descarga en móviles. Los estilos se aplican solo a PRACTICANTE; no cambié reglas, nombres de formularios, autorización ni la estructura de datos.
- Conservé el zoom manual/viewport y dejé texto de controles móviles en 1rem para evitar controles pequeños. Recompilé Tailwind por las utilidades nuevas; el CSS generado se versiona y sus cambios se explican aquí, sin editarlo manualmente. Los cambios fuente incluyen comentarios explicativos.
- Agregué --populated en ui-pages.php y una octava prueba visual. Mide documento y tablas en 320/390/640/768/1024/1280/1440 px para cinco pantallas, comprueba registros presentes y texto completo, recursos locales y errores JS. Evidencia: scratch/responsive-practicantes/before y after, 35 capturas/mediciones por estado. Revisé capturas representativas de historial, permisos y perfil.

### Verificación y límites

- Pasaron ocho pruebas UI, incluyendo las siete previas para los otros roles y controles compartidos. Tras mejorar el ancho de documentos móviles repetí el escenario poblado completo, aprobado. Las pruebas utilizan Edge y datos ficticios; no envían formularios.
- Pasaron once E2E con MariaDB/Edge, 23 pantallas y tres correos SMTP capturados localmente. Evidencia: scratch/phase8/e07e959c5806 y base rrhh_e2e_e07e959c5806; sin errores PHP en php.log. Se conservaron recorridos de perfil/documentos, alta/horarios, marcaje, permisos y revisiones. Los procesos temporales se cerraron al terminar.
- Pasaron 859 comprobaciones PHP: 215 MVC, 119 negocio, 20 configuración, 162 rutas y 343 retiro/API. Sintaxis correcta en los ocho PHP afectados y JavaScript; git diff --check limpio. Tailwind compiló con el aviso previo de Browserslist desactualizado.
- No modifiqué .env, uploads habituales, datos reales, XAMPP, dependencias o hosting. No requiere migración de esquema. Las fixtures/capturas permanecen ignoradas bajo scratch. No hice commit, push, PR ni despliegue; se entrega título/descripción del PR al responsable.
- La verificación cubre Edge y anchos simulados; 640 px comprueban reflujo equivalente a reducir el espacio de una ventana de 1280 px, sin certificar zoom físico del navegador o Safari/iOS. No oculta el desborde global para pasar las pruebas ni deshabilita la ampliación del usuario.
- Siguiente rama: confirmacion-marcajes-practicantes, después de integrar esta entrega en main.

## 2026-10-14 - Corrección de presentación en gestión del supervisor

- El responsable comprobó que Supervisor → Practicantes seguía requiriendo desplazamiento horizontal. La entrega anterior cubría PRACTICANTE; las pruebas de gestión operativa mostraban lista vacía y no abrían la ficha. Confirmé main limpio cdd471f con aquella entrega integrada y creé correccion-responsive-gestion-practicantes con aprobación del entorno.
- Reproduje el defecto con listado/ficha/áreas/horario/excepción poblados: en 320 px la página llegaba a 654 px y una tabla superaba 2114 px. La nueva prueba falla antes del ajuste y pasa después, para supervisor y administrador en 320/390/640/768/1024/1280/1440/1920 px.
- Agregué etiquetas y roles de tabla en la vista compartida practicantes.view.php. El estado vacío conserva su mensaje/color. No cambié campos, enlaces, CSRF, acciones ni lógica del controlador.
- Agregué estilos acotados a gestion-practicantes-content: mínimos flex/grid, palabras largas, controles limitados al panel y formularios de anulación flexibles. Los listados usan columnas ajustadas y campos apilados según el ancho del contenedor, no solo según la ventana, para cubrir los paneles lado a lado. El botón Buscar conserva su ancho legible. No oculté globalmente el desborde ni modifiqué el zoom del usuario.
- Amplié fixtures MVC y la suite UI con cuatro tablas cargadas y ficha seleccionada; amplié E2E con la apertura de fichas de ADMIN/SUPERVISOR usando MariaDB real ficticia. Los cambios tienen comentarios explicativos. Actualicé contexto y plan de correcciones.

### Validación

- Nueve pruebas UI aprobadas: las ocho previas y gestión poblada para dos roles/ocho anchos (16 escenarios). Capturas y mediciones en scratch/responsive-gestion/before y after; inspeccioné visualmente supervisor a 390 y 1280 px y ajusté la búsqueda antes de la ejecución final.
- Once E2E aprobadas, 25 pantallas incluyendo ambas fichas seleccionadas, tres correos capturados localmente. Evidencia: scratch/phase8/e05c9152fa07, base rrhh_e2e_e05c9152fa07; procesos temporales cerrados al terminar. No se detectaron errores PHP en php.log.
- 859 comprobaciones PHP aprobadas: 215 MVC, 119 negocio, 20 configuración, 162 rutas, 343 retiro/API. Ambos PHP modificados y los scripts JS pasan sintaxis; git diff --check limpio. Tailwind recompila sin cambios de contenido generado, con su aviso previo de Browserslist.
- No modifiqué registros habituales, .env, uploads, servicios, dependencias o hosting. No requiere migración. No hice commit, push, PR ni despliegue; se entrega su título/descripción. Esta corrección precede a la confirmación de marcajes del plan.
- Verificado en Edge, no en todos los navegadores. Los estilos de esta pantalla utilizan consultas de contenedor. Para comprobar localmente: Ctrl+F5 en Supervisor → Practicantes, abrir una ficha y revisar horario, áreas y excepciones.

### Ajuste posterior solicitado: conservar el formato compacto

- El responsable prefirió el formato anterior: el apilado por ancho de panel hacía excesivamente larga la ficha y el horario. Conservé la distribución original de columnas y restablecí tablas en escritorio, eliminando las consultas de contenedor de esta pantalla. El ajuste pertenece a la misma rama aún sin commit.
- Repartí los anchos de listado y horario, reduje padding de celdas/controles sin reducir el tamaño de letra de escritorio y mantuve corte de palabras largas. En móvil el horario presenta dos campos por fila, en lugar de seis bloques sucesivos por día. No se ocultan datos ni se usa overflow global para disimular el problema.
- Identifiqué el componente compartido con rrhh-horario-table y cargué valores 10:00/18:00/13:00/14:00 en la fixture operativa. La nueva prueba exige horario menor de 500 px, filas table-row y encabezados visibles en los anchos de escritorio. Las horas permanecen completas y editables.
- Pasaron las nueve pruebas UI y 215 comprobaciones MVC; tras la inspección visual repetí los 16 escenarios operativos con los anchos finales, aprobados. PHP/JS pasan sintaxis y git diff --check limpio. Tailwind recompila con el aviso anterior y sin cambio de contenido generado.
- Inspeccioné la captura específica Supervisor-horario-compacto.png en scratch/responsive-gestion/after para comprobar que valores, iconos y columnas se ven completos. La suite E2E anterior sigue documentada como validación funcional precedente, no como una nueva ejecución tras este ajuste CSS.
- Se mantiene el alcance de presentación: no cambia base de datos, reglas, permisos ni valores de formularios; no hice commit, push o despliegue. Para ver los estilos nuevos, Ctrl+F5 en Supervisor → Practicantes.

## 2026-10-14 - Correcciones operativas, entrega 3: confirmación de marcajes

- Confirmé main limpio/sincronizado cd7692c y creé confirmacion-marcajes-practicantes con aprobación del entorno, después del merge de gestión compacta.
- Agregué diálogo nativo con título/descripción y acción elegida para las cuatro marcas. Cancelar/Escape no envían datos; se conserva retorno del foco. Confirmar muestra estado de envío y bloquea botones para evitar doble clic, sin cerrar como cancelado un envío en curso.
- Amplié ui.js únicamente para formularios con los nuevos atributos. Copié codigo_marca a un campo oculto antes de deshabilitar el submitter para no perderlo en el POST, e incluí confirmar_marca=1. Restauré controles/diálogo al volver por caché de navegación, eliminando los campos transitorios del formulario principal.
- Agregué confirmación del servidor: primer POST sin bandera muestra formulario de confirmación y no crea marca/jornada. Cancelar vuelve por GET; Confirmar envía bandera/código/CSRF. Se revalida código y secuencia vigente antes de crear/registrar. Los cálculos, la hora del servidor y las reglas del servicio permanecen iguales.
- Los cambios incluyen comentarios explicativos; actualicé el plan y contexto. No hay migración, dependencias nuevas, cambio de rutas o escritura en datos habituales.

### Verificación

- Diez pruebas UI aprobadas: las nueve previas y confirmación de cada tipo, Escape, Cancelar, retorno del foco, payload/código/CSRF, bloqueo del doble envío y estado de botones. Cuatro capturas confirmacion-* en scratch/phase6/screenshots; revisé visualmente confirmacion-ENTRADA.png.
- Once E2E aprobadas, con 25 pantallas de los tres roles, las cuatro marcas reales ficticias y validaciones previas. Cada cancelación con JS deja el contador de marcas y POST sin cambios; los dobles clics envían un solo POST. Inicio de colación se prepara/cancela/confirma en contexto con JavaScript desactivado, comprobando escritura únicamente al confirmar. Se mantienen pruebas de CSRF inválido, duplicado y revisión de jornadas.
- Evidencia final: scratch/phase8/bac556ac395d, base rrhh_e2e_bac556ac395d, results.json/php.log/capturas; tres mensajes SMTP capturados localmente. No se detectaron errores PHP en el log. Los procesos de prueba terminaron y las bases/copies ficticias quedan para revisión.
- El primer intento E2E detectó que las etiquetas del catálogo son Entrada a colación / Salida de colación, distintas de las etiquetas sintéticas de la prueba visual. Ajusté la expectativa E2E al catálogo vigente y repetí con una fixture nueva; no renombré los tipos productivos para hacer pasar la prueba.
- Pasaron 859 comprobaciones PHP: 215 MVC, 119 negocio, 20 configuración, 162 rutas y 343 retiro/API. Ambos PHP afectados y los tres JS afectados pasan sintaxis; git diff --check limpio. Tailwind recompila sin cambio generado, conservando el aviso previo de Browserslist.
- No modifiqué .env, uploads habituales, datos reales, XAMPP, dependencias ni hosting. No hice commit, push, PR ni despliegue; se entrega título y descripción. Publicar frontend y backend coordinadamente desde la versión aprobada. Los scripts de prueba/capturas quedan fuera del despliegue.
- Validado en Edge y MariaDB local con datos ficticios; el doble clic no demuestra concurrencia entre dispositivos ni compatibilidad de todos los navegadores. La restauración por caché se implementó, sin una nueva prueba de navegación BFCache real. Siguiente rama, después del merge: permisos-mismo-dia.

### Ajuste solicitado: ventana nativa del navegador

- Sustituí el modal HTML por window.confirm para las cuatro marcas, según la preferencia del responsable. Eliminé el marcado del diálogo y sus manejadores específicos; conservé confirmación del servidor sin JavaScript, bandera, CSRF, foco tras cancelar y bloqueo de botones al aceptar.
- El estado Registrando marca… aparece en el formulario mientras se envía. La restauración por caché ahora limpia ese mensaje y los campos transitorios, sin necesitar estado de un diálogo HTML. La apariencia/idioma de la ventana depende del navegador.
- Adapté pruebas para detectar diálogos de tipo confirm y aceptar/cancelar mediante el navegador real, comprobando que ya no existe confirmacionMarcaje en el DOM. Se conservan las comprobaciones de código/CSRF, cancelación sin POST, doble clic con una escritura y alternativa sin JavaScript.
- Pasaron diez UI, once E2E y 215 MVC nuevamente. Evidencia final E2E: scratch/phase8/f8f87dd37313, base rrhh_e2e_f8f87dd37313; tres correos ficticios capturados localmente, sin errores PHP en el log. Los 859 checks PHP precedentes permanecen documentados; este ajuste no cambió el controlador ni los servicios.
- Sintaxis PHP/JS correcta, git diff --check limpio y Tailwind recompilado sin cambio generado. Las capturas de confirmación anteriores corresponden al diseño descartado; no representan la ventana nativa actual.
- Cambios comentados en la misma rama confirmacion-marcajes-practicantes, sin commit/push/despliegue ni modificaciones de datos habituales. Recargar con Ctrl+F5 antes de probar los marcajes locales.

### Integración local: confirmación alternativa y caché del script

- El responsable seguía viendo Cancelar / Confirmar Entrada en localhost/asistencia/practicante/marcar-asistencia.php. Comprobé por HTTP el ui.js servido por su Apache: 200, contiene win.confirm y no pendingMark; no constaté qué copia tenía su navegador. El texto citado sigue existiendo en la alternativa del servidor para un POST sin bandera, escenario que reproduje con datos ficticios.
- Agregué versión por hash SHA-256 de contenido (16 caracteres) a la URL del ui.js en layout/foot.php, calculada desde ASSETS_PATH. No depende de rutas absolutas del equipo ni obliga a modificar .env; funciona también con los assets duplicados iguales del paquete de publicación.
- Identifiqué la sección alternativa con data-marcaje-fallback y la oculté al iniciar JS si confirm está disponible. Así un POST pendiente no mantiene el botón Confirmar Entrada dentro de la página con JS activo. La alternativa conserva su operación cuando JS está desactivado; no se acepta ni guarda una marca automáticamente.
- Amplié E2E para enviar un POST sin bandera y verificar que la alternativa existe pero queda invisible con JS activo, sin incrementar marcas; después recorre las cuatro ventanas y su confirmación/cancelación. La prueba sin JS mantiene visible la alternativa y registra solo al confirmar.
- Pasaron diez UI, once E2E y 215 MVC nuevamente. Evidencia E2E final: scratch/phase8/d82ab5b197ce, base rrhh_e2e_d82ab5b197ce; tres correos locales ficticios. Código PHP/JS y git diff --check correctos, sin errores PHP en el log de pruebas.
- No modifiqué el backend de marcaje ni datos habituales, entorno, uploads, dependencias o servicios. No hice commit/push/despliegue. Para comprobar el resultado actual, abrir directamente la URL del marcaje (GET), evitando reenviar un POST anterior.

## Correcciones operativas, fase 4: permisos para hoy e interrupciones

- Creé `permisos-mismo-dia` desde main `aedf30d`, sin prefijo codex. Cambios comentados en español; no hice commit, push, PR en GitHub ni despliegue.
- Permití hoy en calendario, endpoint y servicio, con validación estricta de fechas/horas y del practicante autenticado. Conservé rechazo de fechas pasadas y días sin jornada vigente.
- Corregí ausencia intermedia para conservar los dos tramos de trabajo, colación parcial y minutos en las vistas/preview. La aprobación conserva la ventana exterior y guarda las horas netas previstas, sin perder el tramo de regreso.
- Agregué marcas SALIDA_PERMISO/REGRESO_PERMISO independientes de colación, controles contextuales y confirmación nativa existente. Entrada tardía/salida anticipada usan las marcas normales. No se inventan regresos; las pausas no suman trabajo. Pausas pendientes/rechazadas quedan observadas, sin impedir regresar de una pausa ya registrada.
- Apliqué las respuestas del responsable: solicitar con cruce se permite en observación; aprobar requiere resolver marcas y registrar comentario cuando la solicitud nació observada. Revalidé también cruces que aparecen después de solicitar, entradas abiertas y secuencias inválidas. No se corrigen/anulan marcas automáticamente.
- Guardé solicitud y auditoría en transacción; bloqueo de práctica/jornada y repetición del control de duplicados. La aprobación conserva su transacción con excepciones y recalcula jornadas parciales existentes; conserva extras anteriores compatibles o detiene la aprobación para revisión expresa.
- Agregué `database/2026-10-14_patch_permisos_mismo_dia_006.sql`: dos campos de observación y dos tipos de marca, con cambios aditivos. Aplicado únicamente a `asistencia` local; respaldo previo del esquema/catalogo en `scratch/phase4/esquema-antes-20261014-175703.json`. No cambié .env, uploads ni marcas/datos históricos del entorno habitual; el catálogo y esquema locales sí se ampliaron.
- Corregí un fallo detectado por E2E/UI: el guion tipográfico junto a variables PHP debía quedar fuera de sus nombres; ahora la descripción muestra ambos tramos completos y está cubierta por pruebas. La fixture visual sustituye la consulta nueva y prueba observaciones largas sin desbordes. Conservé el orden de escritura para marcas del mismo segundo, evitando que los ordinales del catálogo reordenen permiso/colación.
- Pasaron 914 comprobaciones PHP: 172 negocio, 215 MVC, 345 retiro/API, 162 enrutamiento y 20 configuración. Sintaxis PHP correcta y git diff --check limpio. Tailwind recompilado; se restauraron los finales de línea de los iconos copiados para evitar cambios ajenos a la fase.
- Pasaron trece E2E con MariaDB aislada; evidencia final `scratch/phase8/c319fbcc4fbf`, sin avisos/errores PHP en su log. Incluyen cruce de hoy, bloqueo sin excepciones, rechazo con motivo y parcial intermedio aprobado con siete horas y ventana 09:00–18:00 conservada. Pasaron diez UI; evidencia de la misma versión visual con observaciones largas validada junto a `scratch/phase8/d64ff35be25d`.
- Las seis escrituras de marcas por permiso y su aprobación posterior se prueban con servicios reales en SQLite; no representan una prueba de varios dispositivos concurrentes ni de Hostinger. Los recorridos E2E verifican formularios de permisos con MariaDB y los cuatro marcajes ordinarios con confirmación/cancelación.
- Detallé publicación y límites en FASE_4_PERMISOS_MISMO_DIA.md. Antes de publicar en Hostinger hay que ejecutar el parche en su base y actualizar backend/frontend juntos. Recuperaciones distribuidas y tiempo excepcional siguen en fases 5/6; no se implementaron en esta entrega.

## Correcciones operativas, fase 5: recuperación en varios días

- Creé `recuperacion-horas-varios-dias` desde main `97ba027`, después del merge comunicado. Cambios comentados en español; no hice commit, push, PR en GitHub ni despliegue.
- Agregué ledger `recuperaciones_permiso` y editor de varios tramos: colaciones existentes, extensiones, sábado y feriado, dentro de lunes a sábado 10:00–20:00 y vigencia de práctica. El plan inicial suma minutos de ausencia exactos y no reutiliza intervalos ordinarios, ausentes o reservados.
- Apliqué las respuestas del responsable: calendario común mantenido por ADMIN y colación trabajada sin marcas de descanso en ese tramo, con comprobación de entrada/salida y pausas efectivas. El calendario queda vacío hasta ingresar fechas reales; no consulté API ni inventé feriados. Se conservan también feriados individuales previos.
- Separé permiso autorizado de recuperación validada. Los tramos PROGRAMADA no reconocen horas; la validación de supervisor exige motivo, marcas coherentes y tramo terminado, con autorización expresa adicional para feriados. Solo suma tiempo trabajado, por minuto completo, limitado al saldo. Tramos adicionales sobre saldo parcial/cerrado en cero requieren otra autorización y se incluyen en pendientes de revisión.
- Revalidé horario/calendario tanto al aprobar como al reconocer. Un tramo que dejó de ser elegible pasa a REPROGRAMAR, sin crédito, conservando historial y liberando saldo. No se reconoce recuperación antes de terminar la ausencia, ni mientras su origen cruce trabajo. Cambiar marcas reabre validaciones; un cruce posterior en la ausencia también retira créditos dependientes y recalcula los otros días.
- Incorporé recuperación validada al acumulado de horas_calculadas y la mantuve separada de extras en el ledger y cálculo. No duplico el mismo intervalo como base/recuperación/extra. Jornadas nuevas de sábado exigen revisión; las históricas conservan su política y no se convierten ni borran extras anteriores masivamente. Tiempo excepcional y compensación de siete días siguen pendientes de fase 6.
- Por solicitud expresa, oculté Generar ausencias a SUPERVISOR y bloqueé también el POST y servicio; ADMIN conserva la herramienta. El generador omite feriados mediante el horario vigente. Calendario y generación de jornadas faltantes no se confunden.
- Aislé el error_log del servidor E2E. Esto reveló una dependencia faltante al validar desde Permisos (SQLite la importaba previamente) y dos variables indefinidas de región/comuna en normalización. Cargué el servicio necesario al ejecutar la acción y preservé ubicación al guardar; E2E comprueba persistencia de ambos campos. No se reconstruyen datos antiguos sin respaldo.
- Agregué `database/2026-10-14_patch_recuperaciones_varios_dias_007.sql`: tablas feriados/recuperaciones y columna politica_recuperacion_v2 en jornadas, por defecto cero. Aplicado y repetido con éxito únicamente en `asistencia` local; respaldo previo en `scratch/phase5/esquema-antes-20261014-190224.json`. No cambié .env, uploads, dependencias ni marcajes históricos de la base habitual.
- Pasaron 964 comprobaciones PHP: 219 negocio, 216 MVC, 347 retiro/API, 162 rutas y 20 configuración. Cobertura de reservas, minutos, feriados, identidad, rollback, saldo parcial/adicional, reprogramación, marcas/origen y protección histórica. El adaptador SQLite no simula InnoDB concurrente.
- Pasaron catorce E2E con MariaDB/Edge/SMTP ficticios. Evidencia final `scratch/phase8/b78674db1bed`, sin entradas en error_log aislado. Cinco horas en tres colaciones y sábado, autorización, calendario, validación, cierre en cero, nueva solicitud/autorización de saldo y POST de generación denegado a supervisor; se conservaron otros recorridos. El helper de caso terminado solo modifica bases rrhh_e2e_* y modela aprobación anterior a las marcas, sin admitir pasado en solicitudes reales.
- Pasaron once UI con `scratch/phase8/32cf65e7fe5f`: editor poblado, restauración de extensión antes/después con mismo tipo, filas independientes y 320 px, además de tablas compactas, formularios, confirmación y responsive existentes. Las vistas/editor no cambiaron tras esa validación visual; las últimas correcciones son de servicios/normalización.
- Tailwind recompilado, PHP/JS sin errores de sintaxis y git diff --check limpio. Documenté procedimiento productivo en FASE_5_RECUPERACION_VARIOS_DIAS.md: ejecutar SQL en la base antes de actualizar backend/frontend conjuntamente, conservando entorno y archivos persistentes. No se comprobó Hostinger ni concurrencia entre dispositivos.
