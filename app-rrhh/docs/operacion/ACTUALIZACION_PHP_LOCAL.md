# Configuración local de PHP 8.3.35

**Registro del cambio local del 13/10/2026.** Sus paths y resultados pertenecen a ese equipo y fecha; no son una recomendación de mantener hoy esa versión ni una confirmación productiva. Para preparar otro equipo, consultar [Entornos](ENTORNOS.md); para actualización y parcheado, [Seguridad](SEGURIDAD.md).

Fecha: 2026-10-13.

## Cambios realizados y propósito

- El responsable descargó PHP 8.3.35 en `C:\xampp\php 8.3.35`. Se comprobó que es x64, Thread Safe y contiene el módulo para Apache 2.4.
- Agregué `C:\xampp\php 8.3.35\php.ini` desde el php.ini-development del paquete nuevo. Configuré las extensiones requeridas por el proyecto y conservé los límites de memoria/carga y las rutas temporales de XAMPP. La zona horaria quedó en America/Santiago.
- Cambié las referencias de PHP en `C:\xampp\apache\conf\extra\httpd-xampp.conf`: módulo, dependencias DLL, PHPIniDir, PHPRC, variables auxiliares y alias CGI. Los archivos contienen comentarios explicativos del cambio.
- Antes de reemplazar la configuración de Apache creé `C:\xampp\apache\conf\extra\httpd-xampp.conf.before-php83-20261013`. No se sobrescribió ningún respaldo existente.
- Se conserva íntegra la carpeta `C:\xampp\php` con PHP 8.0.30. No se modificaron MySQL/MariaDB, sus datos, el .env, uploads ni producción.
- Preparé y validé las copias en scratch/php83-config antes de solicitar permiso para escribir fuera del proyecto. Esos archivos temporales no se versionan.

## Validación realizada

- PHP 8.3.35 ejecuta sin errores de carga de extensiones y lee el nuevo php.ini.
- Apache -t dio Syntax OK con la copia preparada y con la configuración final aplicada.
- Composer check-platform-reqs --lock verifica todos los requisitos, incluidos los de desarrollo, sin ignorar PHP.
- Pasaron las 20 comprobaciones de tests/configuration.php usando PHP 8.3.35.
- Se revisaron 152 archivos PHP con php -l sin errores y se cargó el bootstrap sin consultar la base de datos.
- Apache estaba detenido al comenzar y permanece detenido. No se ha comprobado todavía una respuesta HTTP ni los flujos con sesiones reales bajo la configuración nueva.

## Iniciar y comprobar

En el panel de XAMPP, pulsar Start junto a Apache. MySQL puede iniciarse por separado si se van a probar los flujos con base de datos.

Abrir `http://localhost/dashboard/phpinfo.php` y comprobar PHP 8.3.35 y el archivo de configuración `C:\xampp\php 8.3.35\php.ini`. La página ya forma parte de XAMPP; no se agregó una página phpinfo al repositorio.

Después abrir `http://localhost/asistencia/` y probar el login y los flujos pertinentes con datos locales.

## Terminal y Composer

No se modificó PATH ni el script de la consola de XAMPP. Para evitar que la terminal utilice el PHP antiguo, invocar explícitamente el nuevo ejecutable:

```powershell
& 'C:\xampp\php 8.3.35\php.exe' -v
& 'C:\xampp\php 8.3.35\php.exe' --ini
& 'C:\xampp\php 8.3.35\php.exe' scratch/phase1/composer.phar check-platform-reqs --lock
& 'C:\xampp\php 8.3.35\php.exe' scratch/phase1/composer.phar test:config
```

Composer está descargado temporalmente en scratch/phase1; no se instaló un comando global. No se instalaron ni actualizaron paquetes durante esta configuración de PHP.

## Reversión

Si fuera necesario volver a PHP 8.0, detener Apache desde el panel, restaurar el archivo httpd-xampp.conf desde su respaldo indicado y validar con `C:\xampp\apache\bin\httpd.exe -t` antes de iniciar. La nueva carpeta PHP permanece disponible. Esta reversión del entorno no hace compatible el proyecto actual con PHP 8.0, cuyo mínimo declarado sigue siendo 8.1.

Los cambios de configuración están fuera del repositorio y son propios del equipo local; este documento registra cómo reconstruirlos. No se deben incluir las carpetas de PHP o Apache en el deploy del proyecto.

## Ajuste posterior de cURL al probar HTTP

En fase 2, el arranque HTTP detectó que cURL necesitaba DLL que la CLI encontraba por su ubicación, pero Apache no. Agregué LoadFile para brotlicommon.dll, brotlidec.dll, nghttp2.dll y libssh2.dll desde PHP 8.3.35 antes de cargar el módulo PHP. Se validó en Apache temporal sin el aviso de extensión faltante y se aplicó con permiso, conservando httpd-xampp.conf.before-curl-20261013. La configuración final pasa Apache -t. No se reiniciaron servicios del usuario.
