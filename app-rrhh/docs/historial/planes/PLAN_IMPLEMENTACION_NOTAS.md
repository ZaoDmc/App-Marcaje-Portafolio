> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Plan implementación sistema de notas

Actualizado: 19 de octubre de 2026. Estado: fases 1–4 implementadas y probadas localmente. Hay borradores, publicación confirmada, consulta propia y correcciones auditadas. La integración con Acompañamiento/reportes sigue pendiente de fase 5.

Entrega local de fase 1: [FASE_1_NOTAS_AREAS_Y_LIDERES.md](../notas/FASE_1_NOTAS_AREAS_Y_LIDERES.md). La migración se aplicó en XAMPP; no se publicó en Hostinger.

Entrega de fase 2: [FASE_2_NOTAS_BASE_Y_PERMISOS.md](../notas/FASE_2_NOTAS_BASE_Y_PERMISOS.md). Su documentación distingue pruebas aisladas de aplicación de migración al entorno habitual.

Entrega de fase 3: [FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md](../notas/FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md). Migraciones 010/011 aplicadas a la base local con respaldo; sin publicación en Hostinger.

Entrega de fase 4: [FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md](../notas/FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md). No agrega migraciones ni modifica notas reales durante las pruebas; validación sobre copias ficticias.

## Objetivo y decisiones confirmadas

Incorporar Notas y la designación de líderes por área. El cierre de práctica, los certificados y el envío de evaluaciones a instituciones tienen alcance propio.

- Jerarquía: jefe, Recursos Humanos/coordinadores, líderes y trabajadores; todos salvo el jefe son practicantes.
- ADMIN puede asignar practicantes a áreas y designar líderes dentro de ellas.
- ADMIN registra y aprueba normalmente notas de líderes. Como excepción confirmada, puede resolver con motivo un borrador de integrante cuyo responsable original ya no esté habilitado, sin reasignarlo ni borrar historial.
- ADMIN puede consultar y corregir notas existentes de cualquier practicante, con motivo e historial; se mantiene la prohibición de autoevaluación.
- Cada líder registra y aprueba las notas de los demás practicantes de su área, sin aprobación posterior de ADMIN.
- El practicante consulta su evaluación una vez aprobada por el responsable correspondiente: ADMIN para líderes y su líder de área para integrantes.
- Para aprobar se deben completar todos los criterios y la nota final. Revisar la publicación no escribe datos; confirmar publica de manera atómica y habilita la consulta en Mis notas.
- Cada evaluación semanal se identifica por un viernes. Puede cargarse cualquier día, incluyendo viernes atrasados, conservando por separado la fecha real de registro. Esta regla reemplaza la mención anterior a jueves.
- El líder nuevo registra faltantes de sus integrantes actuales, incluso para viernes anteriores a su designación, sin editar notas ni continuar borradores de otro líder. ADMIN puede aprobar excepcionalmente estos borradores con motivo si el responsable anterior perdió sus permisos.
- La cantidad de filas es configurable: ADMIN añade/quita criterios escritos mediante versiones de rúbrica. El límite técnico es de 1–50 criterios; se parte de las diez filas provisionales existentes sin inventar la rúbrica definitiva del cliente. Cada criterio tiene Sin evaluar o nota 1–7 con paso 0,5. Las notas/borradores existentes conservan cantidad, orden y etiquetas de su versión. Esta decisión sustituye las diez filas fijas.
- La nota final seguirá siendo manual según el acuerdo anterior, hasta que el responsable indique una fórmula. La rúbrica descriptiva está pendiente; los nombres provisionales no son criterios definitivos.
- Notas estará debajo de Reportes en los menús que tengan esa opción. PRACTICANTE necesitará una entrada propia porque su menú actual no contiene Reportes.
- Las implementaciones se documentarán en Markdown para facilitar nuevas incorporaciones al proyecto.

## Base existente y autorización

Actualización de fase 5 (2026-10-19): se confirmó mostrar la última nota semanal aprobada y su viernes en Acompañamiento, ficha y detalle de reportes; el puntaje anterior queda histórico de solo lectura. SUPERVISOR consulta este resumen aprobado dentro de su alcance, sin calificar ni acceder a borradores o motivos internos. Sin promedios ni conversión de datos antiguos. Véase [FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md](../notas/FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md). La rúbrica definitiva sigue pendiente de contenido.

Reutilizar areas_practica e historial_areas_practicante. La fase 2 incorpora notas_semanales, su detalle e historial y rubricas_notas; conserva evaluaciones_practicante y el puntaje editable de Acompañamiento sin transformarlos en notas semanales. Esa conciliación corresponde a la fase 5: no deben quedar dos notas oficiales finales editables independientemente.

Líder será una responsabilidad con vigencia por área, asociada a la práctica del designado, distinta del rol global SUPERVISOR. El servidor comprobará liderazgo y pertenencia del evaluado en cada operación, incluso si el actor tiene varios roles. SUPERVISOR por sí solo no habilita calificar ni aprobar. Tampoco se deducen permisos solo por pertenecer a Recursos Humanos o coordinación. Nadie puede evaluarse a sí mismo.

ADMIN no tendrá una segunda aprobación obligatoria sobre notas que corresponden a líderes de área. Su acceso global de consulta y edición está confirmado; cada corrección dejará motivo y versión anterior. Aprobar una nota no cierra automáticamente la práctica.

## Evaluaciones semanales y acumulación

La periodicidad semanal se identifica por viernes, con carga cualquier día. El formulario usa la versión de criterios guardada para cada periodo, conservando responsables, área e historial. Una semana sin evaluación no representa una nota cero.

La fase 2 identifica cada evaluación por práctica y viernes mediante clave única, distingue el periodo de la fecha real de carga y conserva versiones de correcciones. Usa America/Santiago. Se permite el viernes de la semana actual desde el lunes; sábado y domingo siguen referidos al viernes recién pasado. No se adelantan semanas posteriores. Esta convención operativa no define una fórmula ni un intervalo diario de cálculo académico.

Acumular significa conservar las evaluaciones de toda la práctica. No se ha confirmado una suma, promedio o ponderación automática. La nota de cada evaluación semanal y una eventual nota de cierre de práctica deben distinguirse; falta definir cómo se relacionan con el campo Nota final planteado inicialmente. Se conserva el ingreso manual y, por confirmación de fase 5, el puntaje existente de Acompañamiento queda histórico de solo lectura.

El servicio identifica viernes faltantes dentro de las fechas de práctica, sin desplazar viernes feriados. El apartado visual de pendientes llega en fase 3. No se impone restricción de carga por día. Si se necesita una semana parcial sin viernes dentro de la práctica, su tratamiento académico sigue pendiente; no se inventa una evaluación fuera de sus fechas.

## Fases y ramas propuestas

Estado al 2026-10-19: fases 1–5 integradas y fase 6 validada localmente en `notas-validacion-y-despliegue`. Se ejecutaron 170 comprobaciones MariaDB, 354 de negocio sin fallos y 20 recorridos de navegador; paquete verificado y guía en [FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md](../notas/FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md). Pendientes: recibir rúbrica definitiva y confirmar despliegue/aceptación en Hostinger. La preparación local no certifica publicación productiva.

| Fase | Rama | Alcance | Criterio de término |
| --- | --- | --- | --- |
| 1. Áreas y liderazgo | notas-areas-y-lideres | Designaciones por ADMIN, historial, pertenencia y vigencias; entrada Notas para ADMIN con gestión de líderes. | Designar, reemplazar y retirar líderes sin perder historial ni dejar designaciones incompatibles. |
| 2. Base y permisos | notas-base-y-permisos | Evaluaciones semanales por práctica, prevención de duplicados por periodo, detalle, versiones de criterios y autorización por área. | El servidor distingue evaluación de líder por ADMIN y evaluación de integrante por su líder, bloqueando autoevaluación, accesos ajenos y duplicados semanales. |
| 3. Formulario y borradores | notas-formulario-y-borradores | Formularios, viernes pendientes/atrasados, filas y criterios configurables por ADMIN, nota final manual, observaciones y borradores. | Nueva semana conserva anteriores; cada evaluación mantiene su rúbrica; borradores privados y formularios adaptados a los seis temas. |
| 4. Aprobación y consulta | notas-aprobacion-y-publicacion | Confirmación por el responsable competente, consulta propia, auditoría y correcciones con historial. | El líder publica las notas de su equipo sin paso ADMIN; ADMIN publica las de líderes. |
| 5. Rúbrica e integración | notas-rubrica-e-integracion | Incorporar criterios cuando se entreguen, preservar versiones y conectar nota oficial con Acompañamiento, ficha y reportes. | Una sola fuente oficial y conservación histórica; rúbrica definitiva sujeta a recibir su contenido. |
| 6. Validación y publicación | notas-validacion-y-despliegue | Probar permisos, cambios organizacionales, migraciones, interfaz y empaquetado. | Evidencia reproducible y guía de despliegue y reversión sin perder notas anteriores. |

Cada rama parte de main actualizado después de integrar la anterior. Cada entrega incluye comentarios explicativos en código y título/descripción del PR para que el responsable lo publique.

## Detalle de la fase 1

- ADMIN verá áreas, integrantes y líder vigente, con acciones para designar, reemplazar o terminar la designación.
- Se propone como máximo un líder vigente por área, permitiendo áreas temporalmente sin líder. Se requiere pertenencia al área y práctica activa.
- Conservar práctica, área, vigencia, actor y motivo; reemplazar no elimina registros históricos.
- Cambios de área y bajas deberán resolver expresamente las designaciones afectadas. No conceder acceso a borradores de otro equipo por un cambio organizacional.
- Proteger la exclusividad con transacciones y controles para operaciones simultáneas.
- Entregar migración para bases existentes y schema.sql actualizado para instalaciones nuevas.
- Todavía no habilitar registro de notas: esta fase prepara la estructura organizacional.

La asignación de áreas ya existe para ADMIN y SUPERVISOR. Falta decidir si se restringirá a ADMIN; este plan no revoca por sí solo el permiso operativo existente. Designar líderes sí queda reservado a ADMIN.

## Flujo propuesto

| Persona evaluada | Quién registra | Quién aprueba y publica |
| --- | --- | --- |
| Integrante del área que no sea líder | Líder vigente de esa área | Ese líder, sin intervención ADMIN |
| Líder de área | ADMIN | ADMIN |

Se implementó Borrador → Revisión previa → Confirmación → Aprobada. La revisión previa no constituye un estado guardado ni habilita consulta al evaluado. Publicar requiere todos los criterios y nota final completos. No hay aprobación ADMIN obligatoria para integrantes con líder competente.

El evaluador competente puede registrar y aprobar a otra persona; sigue prohibida la autoevaluación. El líder original vigente puede corregir sus propias evaluaciones de integrantes; ADMIN puede corregir cualquier nota ajena existente. Corregir una aprobada exige motivo, revisión previa y confirmación; mantiene publicada la versión anterior hasta confirmar y conserva ambas en el historial interno. El evaluado ve solamente la versión aprobada vigente de cada viernes, sin motivos ni borradores internos.

Ante reemplazo de líder, cambio de área o promoción del evaluado, el servidor revalida la responsabilidad. El nuevo líder registra faltantes de sus integrantes actuales; no obtiene acceso a borradores ajenos ni edición de notas del anterior. Estos borradores quedan para ADMIN, según el criterio indicado antes de comenzar esta fase. Las notas existentes conservan área, autor y liderazgo de origen.

Evaluación enviada a una institución y evaluación aprobada son conceptos diferentes. La aprobación no implica envío, cierre de práctica ni emisión de certificado.

## Decisiones pendientes antes de las fases correspondientes

- Tratamiento académico de semanas parciales sin viernes dentro de las fechas de práctica. Viernes, carga cualquier día y registro de atrasadas ya están confirmados.
- Relación entre la nota semanal y la nota final de práctica: historial solamente o resumen numérico; fórmula y redondeo si se solicitan. La acumulación no confirma una suma ni un promedio.
- Responsable de evaluar a Recursos Humanos y coordinadores cuando no sean líderes.
- Número de áreas que puede liderar simultáneamente una persona.
- Mantener o restringir a ADMIN la asignación de integrantes a áreas disponible hoy para SUPERVISOR.
- Confirmar presentación definitiva de la nota final manual: el formulario admite 1–7 con hasta dos decimales, sin calcularla. Para cada criterio está confirmada la escala 1–7 con paso 0,5.
- Regla ya confirmada e implementada: todos los criterios y nota final completos antes de aprobar. El borrador admite vacíos.
- Contenido de rúbrica y ponderaciones/redondeo si se solicita cálculo futuro.
- Si aprobar exige completar horas y cómo se tratan observaciones o recuperaciones pendientes.
- Destinatario de evaluación enviada y requisitos de certificados, cuando se aborde ese alcance.

Estas decisiones no requieren inventar criterios ni iniciar registros de notas durante la fase 1.

## Validación prevista

Probar exclusividad y vigencias de líderes, cambios de área, manipulación de IDs y roles combinados; bloqueo de autoevaluación y acceso a otros equipos; publicación directa por el líder sin paso ADMIN; evaluación de líderes exclusivamente por ADMIN; escala, vacíos y privacidad de borradores; correcciones auditadas; conservación de puntajes de Acompañamiento sin aprobarlos automáticamente; coherencia de la nota oficial y migración sobre copia aislada antes de producción.

Comprobar además múltiples viernes sin sobrescritura, duplicados, faltantes sin nota cero, fecha de carga distinta del viernes, cambios de año, corrección global ADMIN y protección de notas al cambiar área o líder. No hay bloqueo por día de carga ni fórmulas automáticas.

## Documentación por entrega

Actualizar este plan al cambiar decisiones y crear un documento de fase cuando exista una entrega concreta. Cada documento explicará:

1. Estado real: propuesto, implementado, probado localmente o publicado; indicar quién confirmó la publicación.
2. Problema resuelto y reglas, distinguiendo decisiones confirmadas de propuestas.
3. Archivos, rutas, permisos y tablas involucrados, sin credenciales ni datos personales.
4. Migraciones: orden, requisitos, conservación de datos y reversión.
5. Pruebas ejecutadas, resultados, limitaciones y forma de reproducirlas.
6. Pendientes y siguiente paso.

CONTEXTO_PROYECTO.md conservará un resumen vigente con enlace al plan y PROGRESO.md registrará cada entrega. La organización global de Markdown queda para una tarea posterior; evitar copias innecesarias con reglas contradictorias.
