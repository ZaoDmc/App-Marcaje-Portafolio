# Dependencias administradas por Composer

## Fuente de versiones

`composer.json` define requisitos y `composer.lock` fija las versiones a instalar. `vendor/` es un resultado de instalación, excluido de Git. No copiar allí bibliotecas manuales ni editar sus fuentes para corregir código de la aplicación.

Agregar un directorio a `.gitignore` no retira archivos que ya estaban versionados. Una instalación puede entonces cambiar dependencias y dejar el checkout modificado, aunque no se haya ejecutado `composer update`.

## Incidencia encontrada el 22 de octubre de 2026

El nuevo checkout privado de Hostinger quedó en prod `faa815ee52ee4576cffeaf943e1866920776fcc8`. El responsable instaló las siete dependencias de producción desde el lock con PHP 8.3.35. La comparación de uploads no encontró diferencias con el origen; el frontend activo todavía no se conectó al checkout nuevo.

Git mostró modificaciones en `vendor/phpmailer/phpmailer/src/PHPMailer.php` y `SMTP.php`: 189 inserciones y 486 eliminaciones entre ambos, según la salida comunicada. La revisión del repositorio confirmó tres archivos seguidos dentro de vendor: esos dos y `Exception.php`.

El archivo PHPMailer versionado declara **7.1.1**, mientras que `composer.lock` y la metadata de instalación fijan **6.12.0**. Este cambio retira los tres archivos del seguimiento con `git rm --cached`, conservando los archivos locales. No modifica Composer JSON/lock ni actualiza versiones. La versión efectiva debe provenir de la instalación de Composer; no se considera una actualización o auditoría general de dependencias.

## Publicar esta corrección

Rama `corregir-vendor-composer`, creada desde `origin/dev` en `faa815e`. PR hacia dev; después de revisar y probar, PR de dev hacia prod según el flujo acordado. No enviar la corrección mediante un commit directo en Hostinger.

Las tres eliminaciones deben formar parte del commit: son retiros del repositorio, no instrucciones para borrar los archivos instalados del equipo. En una clonación nueva se reconstruyen desde Composer. No revertir esas eliminaciones ni añadir de nuevo vendor para dejar el PR aparentemente limpio.

## Transición única del checkout de Hostinger ya preparado

Ejecutar únicamente **después de integrar la corrección en prod**, dentro del checkout privado `app-rrhh`. Este procedimiento corresponde a la copia nueva todavía no conectada al sitio; no ejecutarlo sobre el backend productivo anterior.

1. Comprobar `pwd`, rama prod y `git status --short`. Confirmar que los únicos cambios son los dos archivos PHPMailer descritos. Si aparecen otros, revisar antes de guardar/restaurar nada.
2. Guardar esos cambios concretos en un stash para permitir el avance rápido, sin incluir `.env`, uploads ni otros archivos:

```bash
git stash push -m "Respaldo vendor antes de retirar seguimiento" -- vendor/phpmailer/phpmailer/src/PHPMailer.php vendor/phpmailer/phpmailer/src/SMTP.php
```

3. Comprobar que el árbol seguido quedó limpio y actualizar:

```bash
git pull --ff-only origin prod
```

4. Git habrá retirado los tres archivos seguidos. La metadata de Composer puede seguir considerando instalado el paquete: un `install` sin operaciones no garantiza recuperarlos. Reinstalar explícitamente el mismo paquete:

```bash
/opt/alt/php83/usr/bin/php /usr/local/bin/composer reinstall phpmailer/phpmailer --prefer-dist --no-interaction --no-scripts --no-plugins
```

El ejecutable anterior corresponde al PHP CLI encontrado en esta cuenta. Confirma la versión y el parcheado aplicables al operar; no copiar la ruta de Hostinger a otro equipo ni sustituirla en código. Si falla la descarga, no conectar la nueva entrada pública hasta completar y verificar la instalación. No usar `composer update` o requisitos ignorados para resolverlo.

Una reinstalación interrumpida puede retirar tanto archivos como el registro instalado. Si posteriormente reinstalar indica que el paquete no está instalado, completar la instalación desde el mismo lock con `composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-plugins --optimize-autoloader`, usando el PHP explícito del entorno. Después comprobar carga y versión; no asumir que un fallo conserva un vendor utilizable.

5. Verificar versiones y carga sin abrir sesiones, conectar a la base o enviar correo:

```bash
/opt/alt/php83/usr/bin/php /usr/local/bin/composer show phpmailer/phpmailer
/opt/alt/php83/usr/bin/php -r 'require "vendor/autoload.php"; echo \PHPMailer\PHPMailer\PHPMailer::VERSION, PHP_EOL;'
/opt/alt/php83/usr/bin/php /usr/local/bin/composer check-platform-reqs --no-dev
git ls-files vendor
git status --short
```

Para el lock de esta entrega, la versión efectiva debe ser 6.12.0; lista de archivos seguidos en vendor y estado Git deben quedar vacíos. Confirmar que `.env` y uploads siguen presentes mediante comprobaciones de existencia, sin mostrar su contenido.

Conservar el stash como evidencia temporal; **no aplicarlo sobre vendor**, pues reintroduciría las versiones anteriores. No borrarlo automáticamente ni usar `reset --hard`, `clean -fdx` o un stash global para ocultar cambios desconocidos. La comparación final de uploads sigue siendo necesaria antes del enlace productivo si hubo escrituras mientras se preparaba la copia.

## Instalaciones posteriores

Después de esta transición, las instalaciones normales usan `composer install --no-dev --prefer-dist --optimize-autoloader` y `check-platform-reqs --no-dev`, con el PHP del entorno. Reinstalar un paquete es una reparación específica de su contenido; no sustituye la revisión deliberada de una actualización del lock.

## Validación local de la entrega

- `git ls-files vendor` quedó vacío. Los tres archivos siguen existiendo y están ignorados; los retiros del índice están preparados para el commit. No se cambiaron `composer.json` ni `composer.lock`.
- La reinstalación local inicial falló al escribir el temporal bajo restricciones del entorno. Tras autorización, Composer ya no encontraba el paquete como instalado; se completó desde el lock con una instalación, cero actualizaciones y cero eliminaciones. No se modificó el entorno productivo para resolverlo.
- PHPMailer y SMTP cargan 6.12.0 mediante `vendor/autoload.php`; Exception se resuelve. Se creó una instancia sin enviar correo, abrir sesiones ni acceder a la base. Los requisitos PHP de producción pasan.
- Composer valida el manifiesto; conserva la advertencia previa de licencia no especificada. No se inventó una licencia como parte de esta corrección.
- Configuración: 20; rutas: 172; MVC: 241 comprobaciones correctas. No se ejecutó E2E de correo ni una auditoría de seguridad de dependencias, ni se prueba Hostinger mediante estos resultados locales.

No hay commit, push ni cambio de la entrada pública. Los retiros locales preparados no equivalen a que prod ya contenga la corrección.
