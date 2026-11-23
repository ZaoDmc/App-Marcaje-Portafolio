> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Notas, fase 6: validación y preparación de publicación

Fecha: 2026-10-19. Rama `notas-validacion-y-despliegue`, desde `main`/`origin/main` en `0cec90e`, con fase 5 integrada. Validación local completada; **no se realizó commit, push ni despliegue en Hostinger**.

## Resultado y alcance

Se verificó el flujo de áreas y liderazgo, criterios versionados, borradores, publicación, consulta propia, corrección, sustitución de líderes e integración en Acompañamiento/ficha/reportes. La validación utiliza PHP 8.3.35 y MariaDB 10.4.32 local; Hostinger informó previamente PHP 8.3.35 y MariaDB 11.8.9, pero no se ejecutaron estas pruebas en ese servidor.

No se modificaron las reglas académicas, los servicios de asistencia o de notas, `.env`, uploads ni datos reales. La rúbrica definitiva sigue pendiente de recibir sus criterios; la validación técnica de las filas provisionales no los convierte en criterios académicos aprobados. Tampoco se definió promedio o nota final global de práctica.

## Cambios de esta entrega

- `scripts/check-notas.php --check` y `scripts/notas-preflight-lib.php`: diagnóstico de solo lectura para las seis tablas InnoDB, configuración/rúbrica vigente y publicaciones completas con sus propios criterios y escalas. No aplica migraciones, corrige datos ni imprime registros o credenciales. Devuelve código 0 si las comprobaciones pasan y 1 si hay pendientes/error. Una estructura incompatible puede producir el mensaje genérico de revisión incompleta; no sustituye la revisión del SQL ni los permisos de aplicación.
- `tests/notas-preflight-casos.php`: comprueba diagnóstico correcto dentro de una transacción READ ONLY, falta de configuración/tabla y publicación incompleta; revierte los casos adversos.
- `tests/notas-publicacion-concurrencia.php` y `notas-publicacion-worker.php`: dos conexiones intentan confirmar la misma revisión de un borrador. Se comprueba un solo éxito, rechazo obsoleto del otro envío, una revisión, una instantánea y una auditoría nuevas. Complementa la prueba previa de creación concurrente.
- `tests/notas-semanales.php`: integra esos casos y verifica conservación al repetir 009–011, incluyendo liderazgos y puntajes históricos de Acompañamiento.
- `tests/business.php`: actualiza un escenario anterior que esperaba marcar una pausa con permiso pendiente y fuera de su ventana. Ahora exige rechazarlo, valida los límites de la ventana de un permiso aprobado y simula las marcas futuras autorizadas en la fixture sin cambiar el reloj del servidor. Los servicios de producción no se relajaron. La prueba SQLite no certifica bloqueos InnoDB ni marcajes futuros reales; el recorrido MariaDB/navegador cubre el flujo real.

## Evidencia ejecutada

| Comprobación | Resultado |
| --- | --- |
| Notas MariaDB (`tests/notas-semanales.php`) | 170 comprobaciones correctas; migraciones idempotentes, privacidad, cambios organizacionales, escala, historial, auditoría, carreras de creación/publicación y diagnóstico. |
| Negocio SQLite (`tests/business.php`) | 354 comprobaciones, cero fallos. Los cuatro fallos registrados en fases anteriores se debían al escenario desactualizado descrito arriba. |
| Liderazgo SQLite | 33 comprobaciones correctas. |
| MVC / rutas / retiro legado / configuración | 241 / 172 (42 destinos) / 374 / 20 correctas. |
| E2E general (`tests/e2e.test.cjs`) | 17 recorridos correctos en `scratch/phase8/440a06b5038b`; incluye autenticación, perfil, documentos, permisos, asistencia, liderazgo, reportes, acompañamiento, catálogos, multirrol, recuperación y compensación. |
| E2E publicación y temas (`tests/notas-publicacion-e2e.test.cjs`) | 2 recorridos correctos en `scratch/phase8/997aae79ab5e`; publicación/corrección/resolución excepcional, consulta propia, integración ADMIN/SUPERVISOR, privacidad y resumen con seis temas a 320/1280 px. |
| E2E formularios (`tests/notas-e2e.test.cjs`) | 1 recorrido correcto en `scratch/phase8/70ab77956dc8`; criterios variables, viernes atrasados, revisiones, borradores, permisos, temas y alternativa sin JavaScript. |
| Diagnóstico sobre asistencia local | Ocho comprobaciones correctas; únicamente lecturas, sin aplicación de parches. |
| Assets | `npm run build` correcto; aviso previo de Browserslist desactualizado, sin cambios de dependencias. |
| Paquete (`verify-release.php` / `tests/release.php`) | 364 archivos verificados y 595 comprobaciones correctas, incluidas exclusión de datos/desarrollo e integridad ante archivos añadidos, faltantes o alterados. |

Las bases E2E contienen datos ficticios y credenciales de prueba en `scratch/`, ignorado por Git. Las pruebas unitarias MariaDB eliminan únicamente su base aleatoria; los recorridos de navegador conservan sus fixtures para evidencia. No borrar carpetas de rescate de MySQL como parte de esta validación.

Se revisaron los registros finales: el E2E general registra el rechazo esperado del POST antiguo que intenta escribir el puntaje histórico; no es un fallo del recorrido. Los recorridos específicos de Notas finalizaron sin un archivo de errores PHP. Sintaxis de los PHP modificados y `git diff --check` correctos; se inspeccionó también la captura Coffee del resumen móvil.

Se generó un artefacto de comprobación en `scratch/releases/20261019-214416-0cec90e9-ca7886/`:

- `backend-rrhh.zip`
- `software-rrhh.zip`
- `manifest.json`, con hashes, commit de origen y marca de cambios sin commit.

Composer instaló en staging las siete dependencias fijadas por el lock con `--no-dev`, sin actualizar versiones ni tocar `vendor` local. El primer intento sin red se interrumpió al no acceder a la caché; se repitió con autorización y finalizó correctamente. El intento interrumpido no es el artefacto verificado.

**El paquete es evidencia local, no una publicación del commit final de fase 6.** Después del merge, generar de nuevo desde `main` limpio para que el manifiesto identifique el commit aprobado.

## Guía de publicación

### 1. Preparar y respaldar

1. Integrar el PR y sincronizar `main`; comprobar `git status --short` vacío.
2. Confirmar en Hostinger las carpetas reales: `backend-rrhh` fuera de `public_html`, y la carpeta existente del subdominio dentro de `public_html` (`software-rrh` o su nombre real). El nombre del ZIP no exige renombrarla.
3. Respaldar base completa, código desplegado y uploads. Conservar los `.env` y `.htaccess` vigentes; contrastar sus reglas con la guía [general de despliegue](../mvc-tailwind/FASE_7_DESPLIEGUE_HOSTINGER.md).
4. Pausar escrituras durante la migración y el cambio coordinado de backend/frontend para evitar versiones mezcladas.

### 2. Revisar la base

Para actualizar una base existente, comprobar qué migraciones faltan y ejecutar, sobre la **base**, en este orden:

1. [009: liderazgo](../../../database/2026-10-19_patch_liderazgos_area_009.sql).
2. [010: notas semanales](../../../database/2026-10-19_patch_notas_semanales_010.sql).
3. [011: rúbricas configurables](../../../database/2026-10-19_patch_rubricas_configurables_011.sql).

Las fases 4–6 no agregan parches. Si 009–011 ya están aplicados correctamente, esta entrega no necesita SQL adicional. **No importar `schema.sql` sobre una base de producción existente**: está destinado a instalaciones nuevas. Revisar dependencias en [database/README.md](../../../database/README.md). MariaDB puede confirmar DDL por sentencia: ante un fallo parcial conservar el respaldo, diagnosticar y completar antes de reabrir el servicio.

El diagnóstico opcional puede ejecutarse desde la copia local o por CLI en un backend privado preparado con sus archivos:

```powershell
& 'C:\xampp\php 8.3.35\php.exe' scripts/check-notas.php --check
```

En Hostinger usar la ruta del PHP CLI habilitado allí. El generador **no incluye `scripts/` en los ZIP**: con SSH se pueden colocar temporalmente los dos archivos de diagnóstico dentro de `backend-rrhh/scripts/`, nunca en la carpeta pública. Sin SSH, revisar las estructuras y configuración mediante phpMyAdmin y completar la comprobación funcional. No compartir contenido del `.env` ni registros personales para acreditar el resultado.

### 3. Generar y subir el commit aprobado

```powershell
npm run build
& 'C:\xampp\php 8.3.35\php.exe' scripts/build-release.php scratch/phase1/composer.phar
```

Usar la carpeta nueva indicada por el comando:

```powershell
& 'C:\xampp\php 8.3.35\php.exe' scripts/verify-release.php scratch/releases/<id>
& 'C:\xampp\php 8.3.35\php.exe' tests/release.php scratch/releases/<id>
```

Verificar commit y `working_tree_dirty: false` en el manifiesto. Extraer los ZIP dentro de sus destinos sin añadir una carpeta envolvente, conservando `.env`, uploads y `uploads.751`. No subir `scratch`, tests, seeds, dumps, documentación o el manifiesto al directorio público. El backend empaquetado incluye bloqueo HTTP propio y el frontend necesita las reglas de front controller del subdominio; no intercambiar ambos `.htaccess`. Actualizar backend y frontend juntos, incluyendo los CSS compilados.

### 4. Comprobación funcional posterior

- ADMIN: designar líder en un área de prueba autorizada y revisar criterios. Cargar los definitivos solo cuando estén entregados; guardar una nueva versión no cambia evaluaciones anteriores.
- Líder: crear borrador de un integrante, elegir viernes actual o atrasado, completar criterios/final, revisar y confirmar. El integrante no debe verlo antes de publicarse.
- Practicante: comprobar Mis notas, sin acceso a borradores o notas ajenas. La publicación no modifica horas ni cierra la práctica.
- ADMIN: evaluar un líder y corregir una nota ajena con motivo/confirmación. La versión publicada anterior permanece hasta confirmar.
- SUPERVISOR: comprobar resumen en Acompañamiento, ficha y reporte; sin edición académica ni borradores. El puntaje anterior conserva su valor histórico y los indicadores operativos se pueden guardar.
- Contrastar viernes y nota en las tres pantallas, incluida una semana atrasada. No interpretar el resumen como promedio ni como nota final de práctica.
- Verificar marcajes, permisos y descargas antes de reabrir escrituras y registrar responsable/fecha del despliegue. Evitar publicar notas ficticias de prueba en cuentas reales.

## Reversión y pendientes

Ante fallo, mantener suspendidas las escrituras y restaurar **ambas mitades del código** de la versión anterior conocida. Conservar las tablas de notas: no borrar historial ni ejecutar DROP para revertir la interfaz. Si ya se cargaron evaluaciones después del respaldo, exportarlas y conciliar antes de restaurar una base; una restauración indiscriminada perdería esas entradas. Volver a código anterior a fase 5 también volvería a habilitar el puntaje independiente de Acompañamiento; requiere decisión consciente, no reconciliación automática.

Pendientes: recibir rúbrica definitiva y confirmar despliegue/aceptación en Hostinger. Semanas parciales sin viernes y eventual cálculo de nota global siguen fuera de las reglas confirmadas; esta validación no los decide.

## Pull request

**Título:** Valida el módulo de notas y prepara su despliegue en Hostinger

**Descripción:**

Se completa la validación de áreas y líderes, rúbricas versionadas, notas semanales, publicación, correcciones, consulta propia e integración con Acompañamiento, fichas y reportes. Se añade un diagnóstico CLI de solo lectura y una prueba de confirmaciones simultáneas que verifica una sola publicación, revisión y auditoría.

Se actualiza un escenario de prueba de permisos a la regla vigente de aprobación y ventana horaria, resolviendo los cuatro fallos previos sin relajar el código de producción. Se documentan migraciones 009–011, respaldos, publicación coordinada y reversión conservando historial.

Validación: 170 comprobaciones MariaDB de notas; 354 de negocio sin fallos; regresiones MVC, rutas, liderazgo y configuración; 20 recorridos de navegador correctos; paquete local con 364 archivos verificados y 595 comprobaciones. No requiere SQL nuevo ni modifica `.env`, uploads o datos reales. La rúbrica definitiva y la aceptación en Hostinger siguen pendientes.
