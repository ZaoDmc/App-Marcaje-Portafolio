# Transición de RRHH a Git en Hostinger — 23/10/2026

## Estado de esta entrega

**Publicación completada el 23/10/2026 a las 15:23:50, America/Santiago** (18:23:50 UTC), dentro de la ventana autorizada **15:10–16:00**. Verificación independiente a las 15:24:31. El sitio ya sirve el checkout de `prod` mediante el enlace público. No existe un despliegue automático disparado por GitHub.

La rama de esta documentación es `migracion-produccion-ssh`, creada desde `origin/dev`. No confundirla con la versión elegida para publicar: el checkout privado del servidor está en **`prod`, `c1a7178eb295fb80a027ad8ed702c7a4e337b4f5`**. Ese commit ya incluye la mejora de mensajes de canje. Las modificaciones anteriores de este chat fueron integradas por el responsable; no se hizo commit, push ni merge desde la preparación operativa.

## Rutas confirmadas

| Elemento | Ubicación |
| --- | --- |
| URL | `https://rrhh.epracticas.cl` |
| Raíz del dominio | `/home/u479877971/domains/epracticas.cl` |
| Instalación anterior | `backend-rrhh`, en minúsculas, bajo la raíz del dominio |
| Entrada pública actual | Enlace `public_html/rrhh` hacia `app-rrhh/public` |
| Checkout privado | `app-rrhh`, con `public/` dentro de su raíz Git |
| Frontend original conservado | `.rrhh-cambio-20261023/public-anterior`, fuera del directorio público |
| Material operativo privado | `.rrhh-cambio-20261023`, bajo la raíz del dominio |

El contenido de `app-rrhh/public` y su backend asociado atienden las solicitudes. `backend-rrhh` y el frontend anterior se conservan para reversión; ya no son la instalación activa. Actualizar el checkout activo sí cambia el código servido.

## Hechos verificados directamente

- Acceso SSH desde el equipo del responsable a Hostinger, sin leer ni compartir claves privadas. La deploy key de lectura de GitHub se desbloqueó por el responsable en un agente temporal de tres horas; la passphrase no se guardó en scripts ni documentación.
- Actualización de `app-rrhh` por avance rápido desde `07e31ce` a `c1a7178`; árbol Git limpio. El diff de esa entrega no modifica SQL, Composer ni las entradas públicas.
- PHP CLI y web comunicados por el servidor: **8.3.35**; consulta de base: **11.8.9-MariaDB-log**. Esto no acredita parcheado del aviso de seguridad pendiente.
- Requisitos Composer y diagnóstico de Notas correctos.
- Prueba CLI del nuevo backend: localización, URL/base, las 41 tablas exigidas por schema, configuración de Notas y acceso a uploads correctos. La base contiene 44 tablas InnoDB; no se eliminan las adicionales.
- **369 comprobaciones de negocio**, exclusivamente sobre SQLite en memoria y sesiones ficticias privadas, y **241 MVC** correctas en Hostinger. No se ejecutaron formularios con escritura sobre registros reales.
- Uploads coincidentes por comparación con checksum; no se encontraron cron que mencionen la ruta del backend antiguo. Esto no inventaría tareas gestionadas por otros mecanismos del proveedor.
- HTTPS/login publicado: 200; entrada `index.php`: 302. El ensayo PHP web del nuevo destino confirmó SAPI `litespeed`, PHP 8.3.35, MariaDB 11.8.9, backend/URL correctos, 41 tablas requeridas presentes, Notas válida y uploads legibles/escribibles.
- Seis recursos CSS/JS públicos se compararon byte a byte con los archivos de `prod`, incluyendo temas y los dos scripts de Notas. `.env`, `backend.php`, `src/bootstrap.php` y `uploads/` respondieron 403; ruta inexistente, diagnóstico temporal y URL de prueba de enlace respondieron 404. Árbol Git limpio después de publicar.

## Respaldos y mantenimiento preparados

La carpeta operativa contiene un SQL inicial de aproximadamente 4,8 MB, un archivo comprimido del backend de aproximadamente 7,4 MB y una copia del frontend actual. El dump terminó sin errores, el tar se puede listar y ambos tienen SHA-256 verificado. **No se ensayó restaurar este SQL en una base del hosting**; no presentarlo como una restauración certificada.

Se generaron además `database-final.sql` y `backend-final.tar.gz` durante mantenimiento, y sus SHA-256 se verificaron después de publicar. SQL, entorno y clave de operador tienen permisos 600; la configuración temporal del cliente MySQL se eliminó. El directorio operativo permite atravesarlo para servir únicamente el mantenimiento; no permite listar su contenido. La página de mantenimiento está fuera del repositorio y actualmente inactiva. Su acceso operativo requiere una cabecera con clave aleatoria, validada en PHP; la clave no aparece en el `.htaccess` ni en URLs.

Los auxiliares de esta sesión están en `scratch/ssh-rrhh-20261023`, ignorado por Git. Son procedimientos privados de esta intervención, no un sistema de CI/CD versionado ni un reemplazo de la revisión del operador.

## Ejecución realizada dentro de la ventana

El primer intento del 23/10 a las 15:16 de Chile se detuvo en la comprobación de GitHub, antes de modificar la entrada pública: el agente temporal anterior había dejado de responder. El responsable desbloqueó la clave en otro agente, sin compartir la passphrase.

Dos intentos posteriores completaron respaldo y ensayo, pero el login seguía devolviendo nuestra página 503 inmediatamente después de cambiar el enlace. Ambos restauraron automáticamente el directorio público anterior, sin importar SQL. Se añadió una espera limitada que solo tolera ese 503 identificado; otro error revierte. El siguiente intento terminó correctamente a las 15:23:50. Se observó retención temporal del mantenimiento; no se determinó qué capa de caché la produjo ni se reiniciaron servicios del proveedor.

Secuencia aplicada:

1. Volver a comprobar hora, SHA de `prod`, árbol limpio, respaldo y entorno. Si `prod` cambió desde `c1a7178`, detenerse antes de mantenimiento y revisar la entrega nueva.
2. Conservar el directorio público original fuera de `public_html` y conectar temporalmente el mantenimiento. Confirmar HTTP 503 y esperar solicitudes previas.
3. Generar un SQL final y un respaldo final del backend. Sincronizar uploads con checksum, sin borrados automáticos.
4. Probar por HTTP, mediante la cabecera privada, el PHP web del nuevo backend, esquema/configuración, login, recursos y rechazo de acceso administrativo anónimo. No aprobar solicitudes ni crear marcajes como datos de prueba en producción.
5. Cambiar el enlace al público de Git y comprobar HTTPS/login, contenido de assets, rutas inexistentes y bloqueo de `.env`, backend, fuentes y uploads directos.
6. Registrar revisión y resultados. Conservar instalación y respaldos anteriores; no retirarlos al primer login correcto.

El procedimiento impidió iniciar antes de 15:10 o después de 15:50 y reservó margen antes de las 16:00 para verificar o revertir. No dejó una tarea programada. Los scripts llevan fecha, SHA y límites propios de esta intervención: no reutilizarlos como despliegue genérico ni retirar sus controles para otra entrega.

## Reversión

Si falla una comprobación después de cambiar la entrada, restaurar el directorio público original únicamente si la ruta sigue correspondiendo a esta migración. Conservar también archivos que pudiera haber creado la instalación nueva copiándolos al almacenamiento anterior, sin borrados. No sobrescribir una ruta inesperada ni eliminar el checkout nuevo.

No importar el SQL automáticamente: en esta transición no se aplicó una migración de esquema, y restaurar la base podría perder escrituras válidas. La reversión automática se ejecutó correctamente en los dos intentos descritos. El procedimiento manual separado solo se validó sintácticamente. Los respaldos y la instalación anterior siguen conservados.

## Límite de la validación

Se comprobaron configuración, acceso a base/archivos, rutas, recursos y redirección administrativa anónima. No se iniciaron sesiones con cuentas reales ni se generaron marcajes, canjes, notas o correos de prueba en producción. La comprobación no acredita todos los flujos autenticados ni el parche pendiente de PHP.

## Publicaciones posteriores

El flujo del equipo sigue siendo ramas de trabajo → PR a `dev` → revisión/pruebas → PR a `prod`. Tras conectar el sitio, el operador actualizará el checkout mediante SSH desde `prod`, instalará las dependencias fijadas cuando corresponda y aplicará solo migraciones explícitas con respaldo. `.env` y uploads no llegan desde Git.

**Un merge en GitHub no ejecuta todavía el despliegue.** GitHub Actions u otra automatización requerirá una implementación posterior. Un pull sobre el checkout ya activo sí cambiará el código servido; debe coordinarse según el alcance de la entrega y sus dependencias.
