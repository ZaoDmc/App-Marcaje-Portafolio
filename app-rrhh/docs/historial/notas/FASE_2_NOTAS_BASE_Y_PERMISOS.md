> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas — Fase 2: base semanal y permisos

Fecha: 19 de octubre de 2026. Rama: `notas-base-y-permisos`, desde `main` en `853d2bd`, sincronizado con `origin/main`. Estado: servicio y migración implementados y probados en bases ficticias locales. Parche pendiente en la base habitual `asistencia` y en Hostinger.

## Entrega

Preparé persistencia, validación, autorización y revisiones de notas semanales. Los formularios, el apartado visual de atrasadas y los menús de evaluación corresponden a fase 3. La aprobación y corrección de notas aprobadas corresponden a fase 4. La pantalla ADMIN de liderazgo conserva su funcionamiento.

Conservé `evaluaciones_practicante` y el puntaje de Acompañamiento. Las tablas semanales son separadas porque los datos antiguos no tienen viernes, diez criterios ni historial que permita convertirlos automáticamente. La conciliación con una fuente oficial final sigue en fase 5.

## Reglas

- Identificación única por práctica y viernes; creación y actualización conservan su fecha real independiente. Puede cargarse cualquier día e incluir viernes atrasados. Un feriado no desplaza el viernes.
- Se permite el viernes actual desde el lunes; sábado/domingo se refieren al viernes recién pasado. No se adelantan semanas posteriores. Esta convención permite carga cualquier día sin inventar un intervalo académico de cálculo.
- Las evaluaciones nuevas requieren práctica/persona habilitada, área única vigente y viernes dentro de las fechas de práctica. La búsqueda de pendientes exige inicio registrado, sin inventarlo. Semanas parciales sin viernes dentro de la práctica siguen pendientes de definición académica.
- ADMIN registra nuevas evaluaciones de líderes y consulta/corrige notas existentes de cualquier practicante. La corrección conserva el área, autor y liderazgo originales, incluso tras baja, cierre o traslado. Toda edición exige motivo e historial.
- El líder registra y aprueba integrantes sin otra aprobación ADMIN. Aquí se implementan permisos y borradores; publicar se habilita en fase 4.
- El líder opera en contexto PRACTICANTE con liderazgo, práctica y pertenencia vigentes. SUPERVISOR por sí solo no habilita calificar. Cuenta y rol se comprueban en base aun con sesión abierta.
- Nadie se evalúa ni edita su propia nota, incluyendo ADMIN vinculado a un practicante. ADMIN tiene consulta global; para el practicante ordinario la consulta propia solo devuelve aprobadas.
- Un líder nuevo registra faltantes de sus integrantes actuales, incluso viernes anteriores a su designación; no edita ni lee borradores de otro líder. El borrador existente ocupa ese viernes y lo resuelve ADMIN, según el criterio indicado antes de comenzar y aceptado al continuar.
- Si el evaluado es líder actualmente o lo fue durante el viernes seleccionado, su evaluación nueva corresponde a ADMIN. Perder liderazgo, pertenencia o elegibilidad invalida la edición del antiguo responsable.
- Diez posiciones con vacío o notas 1–7 con paso 0,5. Vacío no es cero. Nota final manual 1–7 con hasta dos decimales, sin promedio; falta confirmar su presentación definitiva en el formulario.
- Revisión esperada contra formularios obsoletos; instantánea completa y auditoría por guardado. Un fallo de auditoría revierte nota, detalle y revisión juntos. Las lecturas bloqueantes evitan autorizaciones basadas en un snapshot antiguo de InnoDB.

## Archivos y datos

- `src/Services/NotasService.php`: disponibilidad, viernes, actor, destino, autorización, escala, guardado, consulta y búsqueda de pendientes. Los controladores futuros deben invocar operaciones completas; nunca construir permisos con actor/área/rol del POST.
- `src/Services/AuditoriaService.php`: evento `GUARDAR_NOTA_SEMANAL`.
- `database/2026-10-19_patch_notas_semanales_010.sql` y `database/schema.sql`: misma estructura para bases existentes y nuevas.
- `tests/notas-semanales.php` y `tests/notas-concurrencia-worker.php`: pruebas reales en MariaDB y segundo proceso independiente.
- Plan, contexto, progreso e inventario SQL actualizados.

| Tabla | Función |
| --- | --- |
| `rubricas_notas` | Versión provisional con nombres Evaluación 1–10, sin ponderaciones. El servicio no modifica versiones usadas. |
| `notas_semanales` | Práctica, viernes, área, rúbrica, autor, liderazgo, tipo de evaluado, estado, nota final, fechas y revisión. |
| `notas_semanales_detalle` | Diez posiciones, distinguiendo vacío de valor numérico. |
| `notas_semanales_historial` | Instantánea por revisión con actor, fecha y motivo. |

El servicio de borradores rechaza APROBADAS. Su corrección administrativa tendrá un flujo auditado en fase 4; no se publican ni reabren silenciosamente. La consulta propia devuelve únicamente la versión vigente aprobada, sin exponer borradores anteriores ni historial completo.

## Migración y reversión

Respaldar la base existente, comprobar el parche 009 y ejecutar `database/2026-10-19_patch_notas_semanales_010.sql` sobre la base del proyecto. Crea cuatro tablas y una rúbrica provisional, sin notas ni cambios a datos anteriores. Puede repetirse. DDL no es transaccional como conjunto: ante interrupción comprobar y repetir; `IF NOT EXISTS` no repara tablas incompatibles creadas manualmente.

Para instalaciones nuevas, usar `schema.sql` y los seeds. No reimportar el schema completo sobre producción. El parche no reclasifica evaluaciones antiguas ni sincroniza Acompañamiento.

Se probó únicamente en bases aleatorias: sigue pendiente aplicarlo a `asistencia` y producción antes de habilitar formularios. No se cambiaron `.env`, uploads ni configuración XAMPP.

Para revertir código, conservar las cuatro tablas, sus notas, revisiones y rúbrica. No borrar datos al revertir. Las pantallas anteriores siguen funcionando sin el parche porque aún no dependen de este servicio.

## Validación

Ejecutar `php tests/notas-semanales.php` con PDO MySQL y MariaDB local. Lee `E2E_DB_HOST`, `E2E_DB_PORT`, `E2E_DB_USER` y `E2E_DB_PASS` del proceso; predeterminados locales: `127.0.0.1:3306`, `root` y contraseña vacía. Solo permite conexiones locales, crea un nombre aleatorio `rrhh_e2e_*` y elimina exclusivamente esa base al finalizar. Requiere permisos E2E para crear/eliminar bases y triggers.

- 83 comprobaciones MariaDB: migración desde esquema anterior y repetición poblada, conservación de evaluaciones antiguas, escala, vacíos, cambio de año, atrasadas, restricciones SQL, privacidad, cuenta/rol revocados, autoevaluación, áreas ajenas, reemplazo, traslado/baja, edición ADMIN y rollback.
- Dos procesos con conexiones independientes: la segunda solicitud espera una transacción sin confirmar, luego recibe rechazo por duplicado y queda una sola evaluación.
- 237 MVC, 33 liderazgo, 164 rutas, 354 legado/API y 20 configuración; sintaxis PHP y diff revisados.
- Sin prueba visual nueva porque no se alteraron pantallas. No se ejecutó la suite E2E completa ni se volvió a declarar aprobada la suite general de negocio con fallos previos documentados en fase 1.

## Próximo paso

Fase 3: pantalla para líderes/ADMIN, integrantes, selección de viernes actual/atrasado, pendientes, diez selectores, nota final manual, observaciones y borradores. Requiere CSRF, escape, validación de IDs y revisión esperada sin confiar en campos de autor/área/rol. Fase 4: aprobación, consulta del practicante y corrección administrativa de aprobadas con historial.

## Pull request

Título: `Notas · Fase 2: evaluaciones por viernes y permisos de edición`

Descripción:

Prepara evaluaciones semanales por práctica y viernes, con carga cualquier día, registro de atrasadas y protección contra duplicados. Incorpora diez evaluaciones, nota final manual, rúbrica provisional y revisiones auditadas, conservando las evaluaciones anteriores y Acompañamiento.

Los líderes registran faltantes de su área sin heredar notas ni borradores del líder anterior. ADMIN consulta y corrige borradores de cualquier practicante con motivo e historial. Incluye control de acceso, privacidad, revisión esperada y transacciones. Los formularios y la publicación corresponden a fases 3/4.

Validación: 83 comprobaciones MariaDB con concurrencia entre procesos, además de MVC, liderazgo, rutas, legado/API y configuración. Incluye parche 010, schema y documentación. Migración pendiente en la base habitual y producción.
