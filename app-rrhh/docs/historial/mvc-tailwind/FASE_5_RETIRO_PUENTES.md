> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 5: retiro de archivos puente

Rama `fase-5-retiro-puentes`, creada desde `main` limpio en `5a689fc`, después del merge de fase 4 comunicado por el responsable.

## Cambios realizados

- Retiré 57 puentes: 33 entradas de autenticación/páginas por rol, 23 módulos/layouts de `includes` y el `index.php` de la raíz que delegaba al front controller. Antes de retirarlos se comprobó que los 56 puentes de páginas/módulos solo declaraban tipos y requerían su destino; el puente raíz ya estaba identificado.
- Trasladé `practicante/api/horario-dia.php` a `src/Controllers/Practicante/HorarioDiaController.php`. Su URL, autorización PRACTICANTE, métodos GET/HEAD y campos JSON se conservan. El formulario de permisos no cambia de URL.
- Retiré `diagnostico_correo.php`, diagnóstico SMTP antiguo bloqueado desde fase 2 y ajeno al catálogo público. No se ejecutó ni envió correo. No se incorpora otro diagnóstico público en su reemplazo.
- Las 37 rutas del catálogo apuntan ahora a `src/Controllers`. Las antiguas URLs siguen resolviéndose con `.htaccess` y `public/index.php`, sin depender de archivos físicos ni redirecciones nuevas.
- Eliminé la constante sin uso `INCLUDES_PATH` y corregí `ASSETS_PATH` para señalar `public/assets`, la fuente que Apache ya servía desde fase 2. No moví ni borré recursos gráficos.
- Reforcé la validación de fecha del endpoint: arrays, bytes nulos y fechas inexistentes devuelven el error de fecha inválida con HTTP 400, evitando avisos PHP. Se conservan los demás mensajes, estados y tipos del contrato anterior.
- Actualicé la prueba de rutas con la migración explícita del endpoint, conservando intacto el CSV histórico de fase 0.

Los 59 archivos retirados, sus destinos y motivos están enumerados individualmente en `FASE_5_ARCHIVOS_RETIRADOS.csv`. Como un archivo eliminado ya no puede contener un comentario, este inventario documenta cada eliminación. Los cambios de código incluyen comentarios explicativos. `composer.json` incorpora `test:legacy`, explicado aquí porque JSON no admite comentarios.

## Estructura y URLs

La entrada ejecutable es `public/index.php`; `.htaccess` en la raíz permite seguir abriendo `http://localhost/asistencia/`. `/login.php`, `/admin/asistencia.php`, `/practicante/api/horario-dia.php` y las demás rutas mantienen sus nombres aunque ya no existan esos archivos en la raíz o carpetas antiguas.

En la raíz deben conservarse `composer.json` y `composer.lock`: describen las dependencias y fijan sus versiones. También corresponden allí `.gitignore`, README, `.htaccess` local y el `.env` privado. Estos archivos no son puentes. Configuración, código, base de datos, documentación y dependencias permanecen en sus carpetas.

No se tocaron `uploads/`, `uploads.751/` ni sus documentos. La copia antigua de recursos `assets/` queda pendiente de conciliación durante la migración visual/despliegue; `public/assets/` continúa siendo la fuente pública activa. Las carpetas del legado que queden vacías en el equipo no se versionan en Git.

## Validación

```powershell
& 'C:\xampp\php 8.3.35\php.exe' tests/legacy-removal.php
& 'C:\xampp\php 8.3.35\php.exe' tests/routing.php
& 'C:\xampp\php 8.3.35\php.exe' tests/mvc.php
& 'C:\xampp\php 8.3.35\php.exe' tests/business.php
& 'C:\xampp\php 8.3.35\php.exe' tests/configuration.php
```

- 341 comprobaciones de retiro/API: archivos ausentes y destinos presentes, rutas independientes del legado, búsqueda de referencias residuales y endpoint con datos sintéticos en procesos aislados. Incluyen autorización, práctica del usuario autenticado, fecha inválida/futura, falta de práctica/horario y error interno sin exponer detalles.
- 162 comprobaciones de rutas, 199 MVC, 100 de negocio y 20 de configuración.
- 200 comprobaciones HTTP con Apache temporal en dos escenarios: subcarpeta `/asistencia` y `public` como raíz. Se recorrieron las 37 URLs sin los puentes; se comprobaron autorización anónima, POST/CSRF, métodos, HEAD, CSS, protección de privados y diagnóstico retirado.
- Sintaxis de los PHP agregados/modificados, validación de Composer y revisión de espacios del diff.

Apache temporal se cerró al finalizar. No se autenticaron usuarios reales ni se modificaron registros, archivos de usuarios o configuración de XAMPP. Las pruebas no sustituyen los flujos autenticados con datos locales controlados ni validan la plataforma de Hostinger.

## Despliegue coordinado obligatorio

El `index.php` productivo antiguo compartido por el responsable intenta incluir `login.php` y archivos de las carpetas por rol en el backend. Esa entrada es incompatible con la retirada de puentes.

Antes de publicar esta versión se debe preparar conjuntamente:

1. Backend actualizado en `backend-rrhh`, con `src`, `config`, dependencias y demás archivos requeridos.
2. Contenido de `public` en la carpeta pública del subdominio, incluyendo `index.php`, `backend.php`, `.htaccess` y recursos.
3. Conservación del `.env` y uploads productivos, y contraste de las reglas `.htaccess` actuales antes de reemplazarlas.
4. Respaldo y procedimiento de reversión de ambas partes antes de realizar el cambio.

No basta con subir el ZIP del backend conservando la entrada pública antigua. Tampoco debe borrarse masivamente el backend productivo para retirar archivos: solo se retirarán los enumerados, en una publicación coordinada y verificada. Extraer un ZIP encima no elimina necesariamente los puentes antiguos del servidor. El procedimiento reproducible y su ejecución quedan para fase 7; esta fase no modifica Hostinger.

## Pendientes

Probar login, perfiles por rol, formularios de permisos, consulta del horario diario y descarga/foto con una base local de prueba en `http://localhost/asistencia/`.

Fase 6: migrar las vistas restantes a Tailwind y comprobar interacciones por pantalla. Las nuevas compensaciones dentro de dos días y la recuperación de permisos distribuida continúan registradas como trabajo funcional pendiente; esta fase no cambia esas reglas.
