# Publicación en Hostinger

## Publicación vigente mediante SSH y Git

Antes de actualizar código, revisar la [versión de la entrega](../desarrollo/VERSIONADO.md) y CHANGELOG. Después de verificar la publicación, registrar versión servida, SHA, fecha/hora y resultado en [Producción](PRODUCCION.md). No marcarla publicada solo por integrar un PR a prod. El login muestra la versión del checkout que realmente sirve la aplicación.

Para entregar el procedimiento a un operador, consultar el [informe Word de migración y publicación](Informe_migracion_GitHub_Hostinger_RRHH.docx). Incluye la evidencia del 23/10 y un procedimiento que fija el SHA revisado antes de actualizar el checkout activo.

**Actualización 23/10/2026:** publicado `prod` c1a7178 a las 15:23:50 de Chile. `public_html/rrhh` enlaza con `app-rrhh/public`; `.env`, uploads y base productiva se conservaron. Respaldos finales verificados y frontend anterior privado retenido. Consultar [evidencias, incidencias y reversión](MIGRACION_GIT_20261023.md) antes de actualizar el servidor.

La estructura conserva el backend privado en la raíz del checkout `app-rrhh` y expone únicamente su `public/`. El [plan de migración](../arquitectura/MIGRACION_SSH_GIT_RRHH.md) conserva la propuesta original como antecedente. `backend-rrhh` ya no es el backend activo y se mantiene para reversión.

El flujo es ramas de trabajo → PR a `dev` → pruebas/revisión → PR a `prod` → actualización autorizada por SSH. **El merge no despliega automáticamente:** no se ha configurado GitHub Actions. Antes de cada actualización revisar diff, migraciones, dependencias, respaldo y necesidad de mantenimiento. Un `git pull --ff-only origin prod` dentro de `app-rrhh` cambia código activo; no ejecutarlo como prueba de conectividad. Usar PHP 8.3 explícito para Composer y comprobaciones CLI. No extraer ZIP sobre ese checkout ni sustituir su `.env` o uploads.

El procedimiento por ZIP que sigue documenta la publicación anterior; sus ejemplos de nombres deben contrastarse con la estructura comunicada. No mezclar extracción de ZIP y pull dentro de un mismo checkout operativo.

Durante la preparación se detectaron archivos de PHPMailer seguidos por Git pese a excluir vendor. Antes de actualizar el checkout preparado con su retiro, seguir la [transición específica de dependencias](DEPENDENCIAS_COMPOSER.md): guardar solo esos cambios y reinstalar el paquete después del pull. No basta asumir que un install sin operaciones recuperará archivos eliminados por Git.

## Contrato de publicación

El frontend y backend se publican coordinadamente. La entrada pública, catálogo de rutas y controladores deben corresponder a la misma versión. No subir únicamente el backend dejando activo un front controller antiguo que incluya archivos por URL.

| Artefacto | Destino y contenido |
| --- | --- |
| `backend-rrhh.zip` | Backend privado; código, configuración sin secretos y dependencias de producción según el generador |
| `software-rrhh.zip` | Carpeta pública del subdominio; entrada, localizador, `.htaccess` y assets |
| `.env` y uploads existentes | Persistentes del servidor; no se sustituyen por los del equipo |
| Base de datos | Migraciones explícitas con respaldo; no la actualiza un ZIP ni un Pull de Git |

El `.htaccess` local de raíz protege el checkout y reescribe hacia `public/index.php`; el público protege la entrada y estáticos desplegados. El backend debe permanecer fuera de la raíz pública. Las reglas de acceso de archivos no parchean el motor PHP.

## Preparación y verificación

Desde una revisión aprobada y con las herramientas requeridas:

```text
php scripts/build-release.php ruta/a/composer.phar
php scripts/verify-release.php carpeta_generada
php tests/release.php carpeta_generada
```

`ruta/a/composer.phar` y `carpeta_generada` son argumentos de ejemplo que se reemplazan por rutas reales. El generador prepara staging en `scratch/releases`, instalación de dependencias productivas y manifiesto con hashes. No instala dependencias en Hostinger ni publica por sí solo. El diagnóstico debe indicar si el árbol tenía cambios sin commit; para una entrega trazable regenerar desde la revisión aprobada.

## Aplicación

1. Confirmar respaldo recuperable de base y archivos persistentes, versión del motor y directorio real del subdominio.
2. Determinar parches pendientes; aplicar solo los requeridos y en el orden documentado. Preservar el corte de extras históricas y las versiones de rúbricas.
3. Coordinar una ventana sin escrituras incompatibles cuando cambie el esquema o política.
4. Extraer ambos paquetes en sus destinos correctos conservando secretos y archivos del servidor; revisar las reglas propias antes de sustituirlas.
5. Verificar login, rutas, archivos autorizados, marcajes/acumulado, permisos, recuperación y Notas según el alcance publicado.
6. Registrar revisión, fecha, migraciones, versión de PHP efectiva y resultados. Una prueba local no certifica el hosting.

La reversión debe considerar código **y datos**; no restaurar solo PHP si las escrituras nuevas dejaron un estado incompatible. Conservar respaldos y no ejecutar SQL destructivo de reversión sin revisar el impacto.

Detalles históricos del empaquetado: [fase de despliegue](../historial/mvc-tailwind/FASE_7_DESPLIEGUE_HOSTINGER.md). Comprobaciones específicas de notas: [validación académica](../historial/notas/FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md). Sus versiones y fechas pertenecen a esas entregas.
