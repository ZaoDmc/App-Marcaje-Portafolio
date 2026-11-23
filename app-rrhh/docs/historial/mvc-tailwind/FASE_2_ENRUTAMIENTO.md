> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 2: punto de entrada compartido y rutas autorizadas

Fecha: 2026-10-13. Rama de trabajo: codex/fase-0-inventario.

## Objetivo y cambios comentados

Agregué un catálogo explícito de rutas y adapté la entrada para que local y Hostinger ejecuten el mismo código. Las URLs y sus parámetros se conservan. No se eliminan puentes ni se modifican reglas de horas, permisos de negocio, correcciones o datos.

| Archivo | Cambio y propósito |
| --- | --- |
| .env local | Agregué las 12 variables faltantes de la plantilla y comentarios para completar la configuración; preservé todos los valores existentes y verifiqué la sintaxis sin imprimir credenciales |
| config/routes.php | Agregué 37 rutas con destino fijo, roles y métodos; excluí el diagnóstico SMTP |
| src/Http/Router.php | Agregué descubrimiento relativo del backend, normalización por segmentos y coincidencia exacta del catálogo |
| public/backend.php | Agregué la localización inicial del código privado antes de leer .env; usa ubicaciones relativas conocidas o BACKEND_PATH del proceso |
| public/index.php | Reemplacé la inclusión de archivos según la URL por despacho del catálogo, método y autorización previos al HTML |
| index.php | Unifiqué la entrada local delegando al front controller |
| src/Controllers/Auth/HomeController.php | Trasladé la lógica inicial de sesión/rol para evitar incluir recursivamente index.php |
| .htaccess raíz | Agregué protección del repositorio publicado localmente y rewrite de páginas, incluidos puentes existentes; /assets usa public/assets |
| public/.htaccess | Antepuse protección de sensibles a excepciones estáticas y limité estas a formatos públicos de assets |
| src/Views/errors/404.view.php | Separé la respuesta 404, conservando el diseño anterior y sin exponer rutas físicas |
| src/Views/layout/sidebar.php | Adapté el enlace activo a la ruta lógica, evitando depender de SCRIPT_NAME=index.php |
| tests/routing.php | Agregué comprobaciones de rutas, inventario, controles de acceso declarados y ubicación relativa sin ejecutar controladores |
| tests/routing-http.php | Agregué comprobaciones CLI opcionales con Apache, sin autenticación ni operaciones de negocio |
| composer.json | Agregué test:routing; el lock sigue válido porque no cambiaron requisitos ni paquetes |
| .env.example, README y contexto | Actualicé instrucciones para configuración y entrada compartida |

Los cambios PHP y Apache incluyen comentarios explicativos. JSON no admite comentarios; el motivo del comando Composer se registra aquí.

## Configuración local preparada

El .env local existe en la raíz privada y está excluido por .gitignore. Se añadieron APP_ENV, APP_DEBUG, APP_TIMEZONE, APP_BASE_PATH, APP_URL y los campos de DB/SMTP que no estaban definidos, tomando la plantilla como referencia sin sobrescribir valores presentes.

- Revisar DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS si la BBDD local difiere de los valores predeterminados de XAMPP. Una contraseña vacía solo es correcta si MySQL local está configurado así.
- Revisar MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS y MAIL_FROM_NAME si se necesitan correos locales. Los valores que ya existían pueden requerir revisión por el responsable; no se certifica que sean credenciales de prueba válidas.
- APP_BASE_PATH=/asistencia y APP_URL=http://localhost/asistencia corresponden al entorno local tradicional, salvo valores previos explícitos que se conservaron.
- No se copió contenido de este .env a producción. No se incluyeron secretos en documentación, salida de herramientas ni archivos de prueba.

## Rutas y protección

Se mantienen login, activación, recuperación, selección de rol y páginas de ADMIN, SUPERVISOR y PRACTICANTE. También se mantienen el JSON de horario, las plantillas, las fotos y las descargas. `/login` sigue siendo un alias de `/login.php`; `/` e `/index.php` usan HomeController para decidir el destino por sesión.

Las consultas y POST se conservan mediante rewrite interno. Los métodos distintos de los declarados devuelven 405 y Allow. Una ruta desconocida o inválida devuelve 404; una ruta protegida comprueba sesión y rol antes de emitir HTML. Los controladores mantienen sus propias validaciones de CSRF, propiedad y negocio.

Se rechazan segmentos . y .., separadores Windows, controles, doble codificación y prefijos que solo coinciden parcialmente con la base. Los destinos provienen únicamente del catálogo privado. La existencia de README.md, .env, schema.sql o cualquier otro archivo ya no lo convierte en ruta pública.

Las reglas Apache bloquean archivos ocultos, configuración, vendor, src, database, includes, documentación, tests, scratch, uploads y sus variantes, y diagnostico_correo.php. Solo los formatos públicos declarados bajo assets pueden servirse directamente. public/backend.php no se puede solicitar directamente desde la carpeta pública.

Las reglas requieren mod_rewrite y AllowOverride suficiente. No se oculta su ausencia mediante IfModule, porque servir archivos sin las protecciones sería inseguro. No se sustituyó el .htaccess de Hostinger: los cambios productivos deben revisarse y desplegarse en una etapa coordinada.

## Backend en local y Hostinger

Local: public/backend.php encuentra el código en el directorio padre de public.

Estructura productiva comunicada:

```text
raíz-del-sitio/
├── backend-rrhh/
│   ├── config/routes.php
│   ├── src/bootstrap.php
│   ├── src/Http/Router.php
│   ├── vendor/autoload.php
│   ├── .env
│   └── uploads/
└── public_html/software-rrh/
    ├── index.php
    ├── backend.php
    ├── .htaccess
    └── assets/
```

La entrada usa ../../backend-rrhh para esa distribución; no necesita añadir BACKEND_PATH al .env productivo. Si cambia la distribución, BACKEND_PATH se configura en el proceso/servidor antes de localizar el backend, no desde el archivo privado que todavía no puede encontrarse.

No mezclar el index productivo anterior con el .htaccess nuevo ni copiar solo index.php omitiendo backend.php. Preparar backend y contenido público juntos, conservando el .env productivo. La decisión final de reemplazo de reglas productivas y el paquete completo corresponden al despliegue posterior.

## Uploads: no se borraron ni trasladaron

El código continúa leyendo APP_ROOT/uploads con validación de propiedad. Las solicitudes directas a uploads y uploads.751 se bloquean en la superficie web configurada. No se comprobó ni se modificó el contenido de las carpetas de Hostinger. La conciliación de archivos exclusivos, metadata y posibles referencias antiguas sigue pendiente antes de cualquier traslado o borrado.

## Comprobaciones y límites

- PHP 8.3.35: 162 comprobaciones de enrutamiento y 20 de configuración correctas.
- Pasaron 68 comprobaciones HTTP reales con Apache 2.4.58 + PHP 8.3.35: escenario local bajo /asistencia y escenario con public como raíz independiente, con URL/base suministradas por el servidor.
- Se comprobaron login con CSRF, alias /login, entrada inicial, 404, archivos privados, redirecciones anónimas de rutas de los tres roles y endpoints de archivos/JSON, integridad del CSS, POST con CSRF inválido, 405 y HEAD.
- Los Apache de prueba usan puertos 18083/18084 y logs en scratch. Se inician de forma temporal y se cierran al finalizar; no se reiniciaron servicios del usuario.
- Los diez archivos PHP afectados en esta fase pasan php -l; Composer valida manifiesto y lock. .env está ignorado y no aparece entre los archivos versionados.
- Las pruebas no ejecutan login válido, correcciones, carga de archivos ni envío de correos. Los flujos autenticados con datos controlados y la verificación en Hostinger siguen pendientes.
- No se hizo commit, push ni publicación. Los archivos ya preparados/staged por el responsable se conservaron; no se modificó su selección en Git.

Comandos:

```powershell
& 'C:\xampp\php 8.3.35\php.exe' scratch/phase1/composer.phar test:config
& 'C:\xampp\php 8.3.35\php.exe' scratch/phase1/composer.phar test:routing
# Con Apache de prueba disponible en los puertos anteriores:
& 'C:\xampp\php 8.3.35\php.exe' tests/routing-http.php
```

## Ajuste local detectado durante la prueba HTTP

La integración Apache necesitó cargar explícitamente las DLL brotlicommon, brotlidec, nghttp2 y libssh2 del PHP nuevo para habilitar cURL sin depender del directorio del ejecutable CLI. Se validó en el Apache temporal y se aplicó, con permiso, en httpd-xampp.conf. Se agregó un comentario y el respaldo httpd-xampp.conf.before-curl-20261013. Apache -t dio Syntax OK. Este ajuste solo afecta XAMPP local, no el repositorio desplegable ni Hostinger.
