# Entorno local y configuración

## Separación de entornos

Cada colaborador mantiene su `.env` privado y base local. Producción tiene sus propios secretos en el backend de Hostinger; no sustituirlos por la plantilla ni enviar esos valores en un PR.

| Aspecto | Local | Producción comunicada |
| --- | --- | --- |
| Servidor | XAMPP, Apache y MariaDB/MySQL | Hostinger gestiona el servidor y PHP |
| URL | Habitualmente `http://localhost/asistencia` | Subdominio HTTPS configurado en hosting |
| Backend | Checkout del repositorio | El diagrama del 22/10/2026 indica `Backend-rrhh`, fuera de la carpeta pública; confirmar mayúsculas reales |
| Recursos públicos | `public/`, con rewrite desde la raíz local | El diagrama del 22/10/2026 indica `public_html/rrhh` |
| Datos persistentes | Base y uploads de desarrollo | Base, `.env` y uploads propios; conservar al publicar |

Los nombres de carpeta pública han variado en la conversación; la referencia más reciente es [el árbol aportado por el responsable](../arquitectura/Estructura_Producción.md). Comprobar el path real del subdominio en Hostinger antes de actuar. El ZIP público se llama `software-rrhh.zip`; eso no obliga a renombrar la carpeta del hosting. La adaptación a checkout y enlace está en [el plan SSH/Git](../arquitectura/MIGRACION_SSH_GIT_RRHH.md).

## Preparar desarrollo

1. Instalar PHP compatible y sus extensiones declaradas en `composer.json`; comprobar el PHP que usa CLI y el que usa Apache.
2. Instalar dependencias desde `composer.lock` mediante `composer install`. No usar `--ignore-platform-reqs` para ocultar incompatibilidades.
3. Crear una base local y seguir [Datos](../datos/README.md); no utilizar credenciales productivas.
4. Copiar `.env.example` a `.env` y completar únicamente valores locales: base, correo de prueba y URLs.
5. Para frontend, instalar dependencias con el lock del proyecto cuando falten y compilar lo necesario; ver [Componentes](../diseno/COMPONENTES.md).
6. Activar Apache y base, abrir la URL y ejecutar las comprobaciones pertinentes.

Ejemplo de URL local: `APP_BASE_PATH=/asistencia`, `APP_URL=http://localhost/asistencia`. Para subdominio en raíz la base suele estar vacía; comprobar el despliegue. Los aliases antiguos BASE_PATH/BASE_URL pueden tener prioridad: evitar variantes contradictorias.

`BACKEND_PATH`, si se usa para localizar el backend, debe estar disponible antes de cargar su `.env`; no puede depender del archivo que aún no se ha localizado. No escribir un path de este equipo en un controlador.

## PHP y MariaDB

La carpeta de un ejecutable no garantiza qué binario usa Apache. Antes de actualizar XAMPP, seguir [guía local de PHP](ACTUALIZACION_PHP_LOCAL.md) y conservar configuración/extensiones; no mezclar DLL de versiones. La versión productiva reportada requiere el seguimiento descrito en [Seguridad](SEGURIDAD.md).

Si MariaDB no arranca, diagnosticar logs y puerto antes de copiar carpetas o borrar archivos. El [incidente local documentado](../historial/incidentes/INCIDENTE_MYSQL_LOCAL_20261019.md) contiene ubicaciones de rescate que no deben tratarse como material descartable.

La intervención local del **23/10/2026** sustituye el directorio activo anterior y utiliza el servicio Windows manual `mysql`. Consultar [recuperación y arranque vigentes](MARIADB_LOCAL_20261023.md) antes de iniciar o limpiar carpetas. Abrir el panel XAMPP como administrador para controlar el servicio; no usar arranques standalone alternativos ni finalizar el proceso desde el Administrador de tareas.
