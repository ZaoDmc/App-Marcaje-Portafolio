> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 1: dependencias y configuración reproducibles

Fecha: 2026-10-13. Rama: codex/fase-0-inventario.

## Cambios y propósito

- Agregué composer.json a partir del manifiesto comunicado por el responsable. Cambié el requisito PHP a ^8.1 y declaré las extensiones utilizadas por base de datos, SMTP, cargas y XLSX. Conservé las restricciones de PHPMailer, phpdotenv y PHPUnit, y el autoload PSR-4 comunicado. El código actual sigue usando funciones y require_once; esta fase no lo convierte a clases.
- Incorporé composer.lock desde el adjunto de producción y sincronicé sus metadatos mediante Composer. Conservé las versiones y referencias de código de los 33 paquetes originales, incluidos los de desarrollo. PHPMailer permanece en 6.12.0, phpdotenv en 5.6.4 y PHPUnit en 10.5.64.
- Agregué config/environment.php con app_load_env() y app_env(). La primera carga phpdotenv una vez por directorio y la segunda consulta configuración preservando valores vacíos. Los comentarios del código explican cada incorporación.
- Reemplacé el parser manual de config/app.php y adapté config/database.php y config/mail.php al acceso común. Esto evita truncar URLs con https:// y respeta variables preexistentes.
- Agregué soporte explícito de APP_DEBUG, conservando su valor predeterminado local. APP_DEBUG y MAIL_DEBUG quedan desactivados en producción para no mostrar detalles sensibles.
- Agregué .env.example sin secretos y documenté los aliases, la ruta pública y los límites de BACKEND_PATH.
- Amplié .gitignore para evitar incorporar .env privados, uploads, uploads.751 u otras variantes y cachés de PHPUnit. No eliminé contenido existente.
- Agregué tests/configuration.php y el comando Composer test:config para verificar semántica de configuración con archivos ficticios. No requiere MySQL ni correo.
- Actualicé README, contexto y progreso para documentar la instalación y los límites de validación.

JSON no admite comentarios: el motivo de los cambios de composer.json y del lock se registra aquí para conservar archivos válidos y trazabilidad.

## Semántica del entorno

El cargador exige vendor/autoload.php y phpdotenv. Permite que no exista .env cuando el servidor proporciona variables. Un archivo inválido produce un error de configuración sin revelar la línea ni sus valores.

Se utiliza createUnsafeImmutable para conservar compatibilidad con getenv() en el código existente y con el index.php productivo. «Unsafe» identifica el uso del adaptador putenv de la biblioteca; no desactiva la inmutabilidad ni autoriza cargar archivos desde una solicitud HTTP. La ruta la proporciona el código de configuración.

Una variable existente no se sobrescribe desde .env. app_env() consulta getenv(), después $_ENV y después $_SERVER. Si se suministran valores contradictorios en varios canales, deben unificarse en la configuración del servidor; las referencias entre variables de phpdotenv se evalúan con su repositorio de adaptadores.

El index.php productivo compartido todavía carga Dotenv antes de incluir la aplicación. Se comprobó que una carga previa no sea sobrescrita. Retirar esa llamada redundante y unificar el punto de entrada corresponde a fase 2; no se modificó el servidor.

## Ajuste local de sintaxis

El .env local tenía MAIL_PASSWORD con espacios y sin comillas. Se añadieron comillas compatibles con dotenv, verificando en memoria que el valor interpretado fuera idéntico antes de guardar. No se imprimió el valor, no se cambió la contraseña y no se modificó el .env de producción. Este ajuste local permanece fuera de Git.

## Preparar una copia limpia

1. Usar PHP 8.1 o superior de la serie 8.x; se recomienda alinear desarrollo con PHP 8.3 de producción. Verificar php -v y la versión de Apache/hosting por separado.
2. Instalar dependencias desde el lock con composer install. No utilizar composer update para un despliegue habitual.
3. Copiar .env.example como .env en el backend privado y completar variables propias. En producción, conservar el .env existente y revisar solo las variables necesarias.
4. Ejecutar composer validate --no-check-publish y composer check-platform-reqs.
5. Ejecutar composer test:config. La prueba utiliza procesos aislados y temporales, sin leer el .env real ni acceder a la BBDD.
6. Para un paquete de producción, ejecutar composer install --no-dev --prefer-dist --optimize-autoloader y composer check-platform-reqs --no-dev con el PHP de destino. El paquete completo y sus exclusiones se definirán en fase 7.

Composer debe estar disponible como comando o ejecutarse como `php ruta/al/composer.phar`. La herramienta descargada en esta sesión es temporal y está excluida de Git; no se instaló un comando global. La instalación limpia con PHP compatible sigue pendiente de ejecutar; la simulación descrita abajo utiliza el vendor ya existente.

## Validación realizada y límites

- Composer se descargó desde getcomposer.org en scratch/phase1/composer.phar, sin instalación global. Se verificó el SHA-256 publicado por el mismo origen. scratch está excluido de Git.
- Se sincronizó el lock con update --lock --no-install --no-scripts --no-plugins --no-audit --ignore-platform-req=php. La excepción de PHP se utilizó exclusivamente para escribir metadatos con el CLI 8.0.30 local: no es un procedimiento de instalación ni despliegue y no valida compatibilidad de ejecución.
- Se compararon nombres, versiones y referencias source de los 33 paquetes antes y después; no cambiaron.
- Composer valida manifiesto y lock; advierte únicamente que no se declaró una licencia, que continúa pendiente de definición por el responsable.
- Pasaron las 20 comprobaciones de configuración sobre URLs, Unicode, símbolos, comentarios, valores vacíos, interpolación, precedencia, cargas previas, idempotencia, archivo ausente, errores sanitizados, debug de producción, aliases y detección de URL.
- Los cinco archivos PHP agregados o modificados pasan php -l. El bootstrap carga configuración y servicios sin consultar la BBDD; esto no prueba los flujos completos ni la compatibilidad de ejecución de toda la aplicación en PHP 8.0.
- La simulación install --dry-run --no-dev --no-scripts --no-plugins --ignore-platform-req=php terminó sin operaciones sobre paquetes. La excepción PHP se limita a esta inspección con el runtime local; no certifica que la aplicación pueda ejecutarse allí.
- El chequeo de plataforma local debe fallar por PHP 8.0.30 frente al mínimo 8.1. Las extensiones de producción declaradas están disponibles en la CLI inspeccionada o satisfechas por los polyfills bloqueados.
- No se actualizó XAMPP ni se instaló otro PHP. No se ejecutó la aplicación completa en PHP 8.3 ni se accedió a Hostinger, BBDD o SMTP. La compatibilidad efectiva del entorno productivo debe verificarse allí con Composer antes de publicar.
- No se realizó auditoría de vulnerabilidades en esta fase; conservar el lock no equivale a certificar la seguridad de sus versiones.

## Alcance pendiente

La fase deja el código y sus dependencias descritos de forma reproducible. Antes de ejecutar la aplicación completa en local, se requiere configurar PHP compatible. Enrutamiento seguro, eliminación de puentes, reglas de horas, acción de revisión de jornadas, Tailwind y conciliación de uploads continúan en sus fases respectivas.

## Actualización posterior: PHP 8.3.35 local

El 2026-10-13, después de que el responsable descargara el paquete, se creó el php.ini nuevo y se configuró Apache para utilizar PHP 8.3.35, conservando PHP 8.0 y un respaldo de Apache. Pasaron los requisitos completos de Composer sin ignorar PHP, las 20 comprobaciones de configuración, la sintaxis de 152 archivos y la carga del bootstrap. Apache permanece detenido; la verificación HTTP y de flujos reales sigue pendiente. No se modificó PATH: la CLI nueva se invoca por su ruta explícita. Detalles en ACTUALIZACION_PHP_LOCAL.md.
