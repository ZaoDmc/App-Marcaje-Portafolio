# Plan para migrar RRHH a publicación mediante SSH y Git

Fecha del plan original: **22 de octubre de 2026**. Estado actualizado: **transición ejecutada el 23/10/2026**.

**Seguimiento 23/10/2026:** publicado `prod` c1a7178 a las 15:23:50 de Chile. `public_html/rrhh` enlaza con `app-rrhh/public`. Backend anterior `backend-rrhh`, frontend y respaldos permanecen privados para reversión. No hubo migración SQL ni automatización de GitHub Actions. Ver la [bitácora operativa vigente](../operacion/MIGRACION_GIT_20261023.md). Los apartados siguientes conservan la secuencia propuesta originalmente; no repetir desde cero los pasos ya realizados.

Se revisaron [la estructura de producción comunicada](Estructura_Producción.md) y [la propuesta del responsable](Estructura_Propuesta.md), junto al código de localización, Composer, compilación y descargas. Los árboles representan información del responsable, no una inspección del servidor realizada por esta tarea.

## Conclusión

El modelo de un checkout privado por proyecto y un enlace público es viable, condicionado al soporte y permisos del hosting. La estructura propuesta necesita adaptación: este repositorio coloca el backend directamente en su raíz y el frontend en `public/`. Un clon de Git no crea automáticamente `Backend-rrhh/` y `Public_html/` dentro del checkout.

La alternativa recomendada conserva esas rutas y conecta la carpeta pública existente del subdominio con `app-rrhh/public`. No requiere rediseñar MVC ni añadir un localizador específico de este servidor.

## Estructura de destino recomendada

```text
/home/u479877971/domains/epracticas.cl/
├── app-rrhh/                         Checkout privado de e-practicas/app-rrhh
│   ├── .git/
│   ├── .gitignore
│   ├── .env                          Entorno productivo privado, no versionado
│   ├── composer.json
│   ├── composer.lock
│   ├── vendor/                       Dependencias instaladas desde el lock
│   ├── src/
│   ├── config/
│   ├── database/
│   ├── docs/
│   ├── resources/
│   ├── scripts/
│   ├── tests/
│   ├── uploads/                      Archivos persistentes de RRHH
│   └── public/
│       ├── index.php
│       ├── backend.php
│       ├── .htaccess
│       └── assets/
├── Backend-rrhh/                     Instalación anterior durante la transición
└── public_html/
    ├── otros sitios                  Sin cambios por esta migración
    └── rrhh -> ../app-rrhh/public     Enlace, no segunda copia de frontend
```

El nombre y capitalización de `Backend-rrhh` anterior se deben confirmar antes de copiar. El nombre público **rrhh** procede del diagrama actualizado; sustituye los ejemplos anteriores `software-rrh` únicamente como referencia del destino comunicado. No cambiar el subdominio, su URL ni el nombre de la base por adoptar esta estructura.

El árbol muestra archivos principales, no una lista exhaustiva. `.git`, configuración, documentación, scripts y pruebas quedan fuera de la raíz pública. No colocar `error_log` ni otros archivos de diagnóstico en `public/`; su ubicación y exclusión de Git deben revisarse si se configura un log nuevo.

La instalación anterior permanece durante una transición controlada; no debe atender escrituras como una segunda aplicación en paralelo contra la misma base. Conservar respaldos privados identificados y retirar lo anterior solo después de validar uso de archivos, reversión y retención.

## Por qué no utilizar el árbol propuesto sin adaptaciones

| Diferencia | Efecto con el código actual |
| --- | --- |
| Backend bajo `app-rrhh/Backend-rrhh/` | No coincide con la raíz Git actual, que ya contiene `src`, `config` y Composer |
| Público bajo `app-rrhh/Public_html/` | No es el directorio `public/` que analiza y publica el repositorio |
| Localización del backend | `public/backend.php` y `router_backend_path()` buscan el padre del público o el fallback privado `backend-rrhh`; no buscan ese backend anidado |
| Composer y autoload | La raíz Composer actual declara `App\\` hacia `src/`; mover Composer y fuentes cambia el contrato de herramientas y tests |
| Frontend y publicación | Tailwind analiza `./src/` y `./public/assets/js/`; los scripts de publicación esperan la estructura actual |
| Exclusiones | `/vendor/` y `/uploads/` en `.gitignore` se refieren a la raíz; anidar directorios exige revisar las exclusiones |

La propuesta puede convertirse en una arquitectura distinta mediante una implementación explícita que adapte todo lo anterior. Configurar BACKEND_PATH antes de arrancar puede resolver parte de la localización, pero no reestructura el checkout ni corrige por sí solo herramientas y exclusiones. No hay necesidad funcional de asumir ese trabajo para adoptar Git en Hostinger.

## Comprobación pendiente de mayúsculas

El diagrama actual indica `Backend-rrhh`, pero el fallback del repositorio dice `/backend-rrhh`. En Linux pueden ser directorios distintos. Esto **no demuestra que el sitio actual esté roto**: el nombre real, el archivo público desplegado o una variable del proceso pueden diferir del diagrama.

Antes de cambiar nada, comprobar el nombre real y cómo está localizado actualmente el backend. No intentar arreglarlo renombrando una carpeta activa ni editando el `.env`: este último se lee después de localizar el backend y no puede suministrar retrospectivamente BACKEND_PATH.

## Fase 0 Inventario de solo lectura

**Este es el siguiente paso.** Desde SSH en Hostinger, recoger rutas y herramientas sin modificar carpetas:

```bash
RRHH_DOMINIO='/home/u479877971/domains/epracticas.cl'
pwd
ls -ld "$RRHH_DOMINIO/Backend-rrhh" "$RRHH_DOMINIO/backend-rrhh"
ls -ld "$RRHH_DOMINIO/public_html/rrhh" "$RRHH_DOMINIO/app-rrhh"
readlink -f "$RRHH_DOMINIO/public_html/rrhh"
git --version
php -v
composer --version
```

Que uno de los nombres o `app-rrhh` no exista puede ser normal: no crear, mover ni borrar para hacer desaparecer ese mensaje. No buscar ni modificar los directorios de otros proyectos.

Confirmar además en hPanel el document root del subdominio, PHP web/SAPI y soporte de enlace a un destino privado. `php -v` solo informa el CLI. Revisar restricciones de acceso al destino y las reglas `.htaccess` efectivas.

Entregable: rutas y mayúsculas confirmadas; revisión de código que se publicará; estado de ramas dev/prod en GitHub; ubicación de persistentes; requisitos del hosting. Compartir solo información no sensible: nunca contenido de `.env`, claves privadas, cookies o documentos personales.

## Fase 1 Respaldos y línea base

1. Respaldar la base y ambas partes de la instalación actual, incluidos `.env`, `.htaccess` y archivos de usuarios; conservar todo fuera del público y con acceso restringido.
2. Determinar la versión estable elegida y las diferencias productivas frente al repositorio. No afirmar que el último commit local es idéntico a producción sin contrastarlo.
3. Revisar áreas persistentes: `uploads`, `uploads.751` y cualquier archivo en la carpeta pública anterior. Los archivos bajo `uploads.751` no se fusionan ni borran por similitud; contrastar con metadata/rutas de documentos.
4. Acordar ventana de cambio y reversión. Preferir separar la migración de ubicación de nuevas funcionalidades o parches de esquema para facilitar recuperación.

## Fase 2 Ramas y acceso al repositorio

Para seguir el protocolo del equipo: trabajo → PR a dev → pruebas → PR aprobado de dev a prod. Crear las ramas solo si faltan, desde la línea base aprobada; no borrar ni sobrescribir ramas existentes. Conservar main y definir su función durante la transición.

Crear o reutilizar una clave dedicada para este repositorio sin sobrescribir una existente. Agregar solo su parte pública como deploy key de lectura en `e-practicas/app-rrhh`; la privada queda en Hostinger. La conexión del computador al hosting es independiente del acceso del hosting a GitHub.

Comprobar lectura de `refs/heads/prod` con esa identidad. Si falla o la rama no existe, resolver antes de clonar. No modificar permisos de otros proyectos ni compartir una clave privada en la documentación.

## Fase 3 Preparación privada sin cambiar el sitio activo

1. Si `app-rrhh` está libre, clonar allí la rama prod del repositorio; no clonar dentro del backend activo ni dentro de `public_html/rrhh`.
2. Fijar en ese checkout la identidad SSH de RRHH. Verificar rama, SHA y ausencia de cambios inesperados.
3. Copiar el `.env` productivo a `app-rrhh/.env` y restringir permisos según el usuario que ejecuta PHP. Conservar credenciales y URLs si no cambian; revisar cualquier ruta absoluta antigua y variables del proceso que puedan tener prioridad.
4. Copiar los archivos persistentes a `app-rrhh/uploads` conservando rutas relativas y metadata. La copia debe contener archivos reales: los controladores delimitan el almacenamiento mediante realpath; no asumir que enlazar uploads a un destino externo será compatible.
5. Instalar Composer desde el lock en la raíz del nuevo checkout, con PHP compatible y sin requisitos ignorados. Los assets compilados provienen de Git; no copiar encima los assets antiguos para ocultar diferencias de versiones.
6. Revisar el `.htaccess` público versionado y cualquier regla particular del servidor. Conservar las personalizaciones necesarias mediante un cambio revisado o configuración documentada, evitando modificaciones desconocidas que rompan un pull posterior.

No cambiar base, importar schema, marcar jornadas, aprobar notas ni enviar correo real para preparar el checkout. Si la versión requiere una migración faltante, planificarla aparte según [Datos](../datos/README.md).

## Fase 4 Ensayo aislado

Antes del enlace productivo, verificar que `public/backend.php` localiza el padre como backend y que Composer/recursos están completos. Probar por HTTP el enlace en un entorno protegido de ensayo con base/cuentas/archivos ficticios, correo capturado y tareas productivas desactivadas. No publicar una copia de producción abierta como sitio de prueba.

Comprobar URL base, HTTPS, sesiones y cookies propias de RRHH. Repositorios separados no garantizan aislamiento de procesos o sesiones bajo la misma cuenta del hosting. No cambiar configuraciones compartidas de otros subdominios para hacer pasar el ensayo.

## Fase 5 Cambio controlado de la carpeta pública

1. Confirmar respaldo recuperable y condiciones de reversión, especialmente si hubo cambios de datos.
2. Detener temporalmente escrituras de RRHH y sincronizar los últimos uploads para no perder archivos creados después de la copia inicial.
3. Conservar la carpeta pública anterior en respaldo privado; no borrarla ni crear un enlace dentro de una carpeta todavía existente.
4. Confirmar que la ruta exacta `public_html/rrhh` está libre y crear el enlace hacia `app-rrhh/public`. No usar opciones de reemplazo forzado ni aplicar el cambio sobre otro directorio.
5. Verificar resolución del enlace y respuestas HTTP/HTTPS del subdominio, no solo el resultado del comando.
6. Validar login, recursos, descargas autorizadas, acumulados y rutas protegidas. Operaciones con escritura requieren caso controlado y autorización; no usar registros reales como prueba descartable.
7. Reabrir la operación y registrar SHA, fecha, PHP efectivo, migraciones si las hubo y resultado. Comprobar que los otros sitios siguen respondiendo.

No se proporciona un comando de movimiento/reemplazo para ejecutar antes del inventario: los paths y el estado de la carpeta activa deben estar comprobados. El cambio no garantiza cero interrupciones.

## Fase 6 Actualizaciones posteriores

Ejecutar Git solamente en `app-rrhh`, sobre prod y con árbol sin cambios inesperados. Registrar SHA anterior, actualizar con avance rápido, instalar dependencias del lock y aplicar únicamente migraciones explícitamente requeridas por la versión. Verificar y conservar reversión compatible con datos.

`.env` y uploads no llegan desde Git; no usar `git clean -fdx` para limpiar el checkout, porque podría borrar datos ignorados. No aplicar `reset --hard` para ocultar modificaciones productivas ni asumir que volver al código revierte una base de datos.

La publicación por ZIP documentada en [Despliegue](../operacion/DESPLIEGUE.md) describe el procedimiento anterior hasta completar esta transición. Los ZIP pueden conservarse como artefactos de recuperación; no mezclar dos procedimientos sobre el mismo checkout activo.

## Validación de esta revisión

Revisión estática de los dos diagramas, árbol Git, `public/backend.php`, `src/Http/Router.php`, Composer, Tailwind, `.gitignore` y controladores de archivos. La viabilidad en Hostinger queda pendiente de inventario y ensayo. No se alteró código, configuración, base, claves, uploads ni infraestructura.

Comprobaciones locales de esta entrega: 172 comprobaciones de enrutamiento correctas sobre 42 rutas; 67 Markdown revisados y 240 enlaces locales con archivo de destino existente, sin enlaces rotos. No se comprobó disponibilidad de URLs externas ni se ejecutó una migración o prueba HTTP en Hostinger.
