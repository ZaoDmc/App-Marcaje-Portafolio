# epracticas-platform

Sistema web PHP + MySQL/MariaDB para gestion de practicantes, control de asistencia, supervision operativa y reportes administrativos.

## Estado del proyecto

Entrega de desarrollo actual: **v2.1.4**. La fuente de versión es `config/version.php`; consultar [CHANGELOG](CHANGELOG.md) y el [registro separado de producción](docs/operacion/PRODUCCION.md).


Proyecto en desarrollo con MVC y Tailwind integrados, gestión operativa y Notas semanales. El repositorio contiene código, scripts SQL y documentación técnica revisable. Los secretos, archivos de usuarios, respaldos y dumps reales deben quedar fuera de Git.

**Comenzar aquí:** [documentación por componentes](docs/README.md), [estado actual](docs/proyecto/ESTADO_ACTUAL.md) e [instrucciones para agentes y colaboradores](AGENTS.md). Las fases anteriores se conservan como historia; no es necesario leerlas todas para una tarea puntual.

## Stack

- PHP compatible con `composer.json`; versión productiva reportada 8.3.35, con [seguimiento de seguridad pendiente](docs/operacion/SEGURIDAD.md).
- Composer con PHPMailer y phpdotenv; versiones fijadas en composer.lock.
- MySQL/MariaDB
- PDO
- Tailwind CSS 3.4.17 compilado localmente; Bootstrap Icons 1.11.3 como recurso local independiente, sin Bootstrap CSS/JS.
- daisyUI 4.12.24 en login y componentes seleccionados del portal, con temas compartidos; [guía visual](docs/diseno/COMPONENTES.md) y [ejemplo del login](docs/diseno/LOGIN_DAISYUI.md).
- JavaScript puntual
- XAMPP para desarrollo local

## Modulos principales

- Autenticacion y seleccion de rol.
- Panel ADMIN.
- Panel SUPERVISOR.
- Panel PRACTICANTE.
- Pre-registro de practicantes.
- Gestion de usuarios y roles.
- Gestion de practicantes (filtros semanticos, metricas de avance y modal superpuesto para edicion de ficha y horario pactado con atajos y feedback en tiempo real).
- Marcaje de asistencia.
- Gestion de horarios, areas, excepciones y ausencias.
- Reportes operativos.
- Auditoria base.

## Estructura

```text
src/Controllers/ Preparación HTTP y autorización por rol
src/Models/      Consultas y acceso a datos
src/Services/    Reglas y operaciones de negocio
src/Views/       Pantallas y componentes compartidos
config/          Configuración y catálogo de rutas
database/        Schema, catálogos y parches SQL versionables
public/          Entrada HTTP y recursos activos en public/assets/
resources/       Fuente de estilos Tailwind
scripts/         Compilación y preparación de publicaciones
tests/           Comprobaciones automatizadas
```

La documentación técnica de `docs/` se versiona sin incluir credenciales ni datos personales. Los puentes de compatibilidad se retiraron en fase 5; sus URLs continúan funcionando mediante el catálogo. La copia antigua de `assets/` se retiró en fase 7.

## Entrada compartida y rutas (fase 2)

Agregué `config/routes.php` como catálogo explícito: las URLs actuales se mantienen y apuntan directamente a controladores, con autorización antes de renderizar. No se carga un archivo porque exista o porque la URL comience con una carpeta de rol.

En local, `.htaccess` de la raíz envía las páginas a `public/index.php` y sirve `/assets/` desde `public/assets/`. En Hostinger se copia el contenido público, incluidos `index.php`, `backend.php`, `.htaccess` y `assets/`, al directorio público; el backend queda fuera de public_html. El descubrimiento relativo evita cambiar el .env productivo con la estructura comunicada. Los dos lados deben actualizarse de forma coordinada; subir solo el ZIP del backend dejaría activo el enrutador productivo antiguo.

Las reglas Apache bloquean configuración, código interno, documentos, metadatos y diagnóstico antes de servir estáticos. Los uploads se descargan mediante controladores autorizados. La actualización productiva debe retirar los puentes antiguos según el inventario de fase 5.

Ejecutar `composer test:routing` para las comprobaciones sin BBDD. Las pruebas HTTP opcionales y las condiciones del despliegue están descritas en [docs/historial/mvc-tailwind/FASE_2_ENRUTAMIENTO.md](docs/historial/mvc-tailwind/FASE_2_ENRUTAMIENTO.md).

## Preparación de dependencias (fase 1)

Agregué este procedimiento para reconstruir las dependencias sin copiar una carpeta vendor de otro entorno. Desde la raíz del backend y usando PHP compatible:

```text
composer install
composer validate --no-check-publish
composer check-platform-reqs
composer test:config
```

Para preparar dependencias de producción se utiliza `composer install --no-dev --prefer-dist --optimize-autoloader`, seguido de `composer check-platform-reqs --no-dev`. Se conserva el lock; `composer update` se reserva para una actualización deliberada de dependencias.

Se requieren las extensiones declaradas en composer.json: ctype, fileinfo, filter, hash, json, mbstring, openssl, PDO, pdo_mysql, session, SimpleXML y zip. Composer también comprueba las extensiones adicionales de las dependencias de desarrollo.

Las pruebas de las entregas anteriores se ejecutaron con PHP 8.3.35, la versión productiva reportada entonces; esto no acredita el parcheado actual. Si `php` en PATH apunta a un binario antiguo, utilizar explícitamente el ejecutable compatible del equipo. No usar `--ignore-platform-reqs` para desplegar ni desactivar las verificaciones de Composer. Ver [Entornos](docs/operacion/ENTORNOS.md) y [registro de preparación](docs/historial/mvc-tailwind/FASE_1_CONFIGURACION.md).

## Configuracion

Agregué `.env.example` como plantilla sin secretos: copiarla como `.env` en la raíz privada del backend y completar los valores propios. No sobrescribir el `.env` de producción con esta plantilla.

La configuración se carga mediante phpdotenv desde `config/environment.php`. Las variables existentes del proceso, `$_ENV` y `$_SERVER` se respetan; la aplicación consulta primero el proceso y después las superglobales. Los valores vacíos explícitos no se reemplazan por valores predeterminados.

Para valores con espacios, `#` o símbolos especiales, utilizar comillas según la sintaxis de dotenv. Las URLs con `https://` no se truncan. `APP_DEBUG` y `MAIL_DEBUG` se desactivan en producción, aunque estén habilitados en el entorno.

En local tradicional, usar `APP_BASE_PATH=/asistencia` y `APP_URL=http://localhost/asistencia`. En un subdominio, usar `APP_BASE_PATH=` y la URL HTTPS pública correspondiente. `BASE_PATH` y `BASE_URL` se conservan como aliases antiguos con prioridad; evitar definir ambas variantes simultáneamente. `BACKEND_PATH`, si se usa en el enrutador local, debe existir en el proceso antes de localizar el backend y no puede resolverse desde el `.env` privado por sí solo.

Variables utiles:

```text
APP_ENV
APP_DEBUG
APP_TIMEZONE
APP_BASE_PATH
APP_URL
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
DB_CHARSET
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
MAIL_DEBUG
```

No subas archivos `.env`, configuraciones locales con credenciales, respaldos SQL ni dumps reales al repositorio.

## Instalacion local con XAMPP

1. Clonar el repositorio dentro de `C:\xampp\htdocs\asistencia`, usar PHP compatible y ejecutar `composer install` en la raíz del proyecto.
2. Copiar `.env.example` como `.env`, completar la configuración local e iniciar Apache y MySQL desde XAMPP. Comprobar también la versión PHP utilizada por Apache, que puede diferir de la CLI.
3. Crear la base de datos:

```sql
CREATE DATABASE asistencia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Importar el schema completo para una base nueva. Incluye permisos, recuperaciones, feriados, tiempo excepcional, compensaciones y Acompañamiento; no requiere aplicar los parches después de instalar desde cero. Los parches se conservan para actualizar bases existentes; no reimportar schema.sql sobre producción como mecanismo de actualización.

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\schema.sql
```

5. Importar catalogos base:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_regiones_comunas.sql
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_catalogos.sql
```

6. Preparar el administrador local.

Generar un hash bcrypt local:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('CAMBIAR_ESTA_PASSWORD', PASSWORD_DEFAULT);"
```

Reemplazar el placeholder en `database/seed_admin.sql` antes de importarlo:

```text
REEMPLAZAR_POR_HASH_BCRYPT
```

Luego importar:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_admin.sql
```

7. Abrir:

```text
http://localhost/asistencia/
```

## Seguridad del repositorio

Este repositorio debe evitar:

- Credenciales reales.
- Archivos `.env`.
- Configuraciones locales privadas.
- Dumps SQL reales.
- Respaldos de base de datos.
- Datos personales.
- Tokens, hashes o passwords reales de ambientes productivos.
- Documentación con secretos o datos personales.
- Carpeta `vendor/`.

Antes de cada commit se recomienda revisar:

```powershell
git status --short
git diff --cached --name-only
```

## Desarrollo

La validación autenticada se describe en [FASE_8_VALIDACION_FLUJOS.md](docs/historial/mvc-tailwind/FASE_8_VALIDACION_FLUJOS.md). `tests/e2e-setup.php` prepara una base MariaDB y una copia ficticias; `npm run test:e2e` prueba formularios reales, archivos, auditoría y correo en un SMTP local. Requiere una fixture nueva por ejecución y no utiliza el entorno o datos habituales.

La publicación coordinada en Hostinger se prepara con `php scripts/build-release.php ruta/a/composer.phar` y se documenta en [FASE_7_DESPLIEGUE_HOSTINGER.md](docs/historial/mvc-tailwind/FASE_7_DESPLIEGUE_HOSTINGER.md). Genera ZIP separados, dependencias PHP de producción y manifiesto de integridad bajo `scratch/releases`, sin incluir `.env` ni uploads. `php scripts/verify-release.php carpeta` verifica el resultado; `php tests/release.php carpeta` prueba también rechazos y extracción. La única fuente de recursos es `public/assets/`; las copias antiguas de raíz se retiraron con inventario.

La migración visual se documenta en [FASE_6_TAILWIND.md](docs/historial/mvc-tailwind/FASE_6_TAILWIND.md). Para recompilar recursos: `npm ci --ignore-scripts` y `npm run build`. El CSS, JavaScript e iconos publicados están versionados; Hostinger no necesita Node ni `node_modules`.

La retirada de puentes y el requisito de desplegar backend y entrada pública coordinadamente se describen en [FASE_5_RETIRO_PUENTES.md](docs/historial/mvc-tailwind/FASE_5_RETIRO_PUENTES.md). `composer test:legacy` comprueba archivos retirados, destinos y el contrato del endpoint de horario diario con datos sintéticos.

La separación de páginas compartidas y componentes de presentación se describe en [FASE_4_SEPARACION_MVC.md](docs/historial/mvc-tailwind/FASE_4_SEPARACION_MVC.md). `composer test:mvc` ejecuta pruebas de controladores y vistas con datos ficticios, sin consultar la base local.

La fase funcional de autorización, auditoría y revisión de jornadas se documenta en [FASE_3_CORRECCIONES.md](docs/historial/operativa/FASE_3_CORRECCIONES.md). Las pruebas aisladas se ejecutan con `composer test:business` usando PHP compatible y `pdo_sqlite` en CLI; no conectan a la base de datos del proyecto.

Reglas recomendadas:

- Mantener cambios pequenos y revisables.
- Respetar la estructura PHP + MySQL/MariaDB + PDO.
- No modificar reglas de negocio sin validacion funcional.
- No exponer errores sensibles en produccion.
- No subir datos reales de practicantes, usuarios, auditoria o sesiones.

## Licencia

Licencia pendiente de definir.
