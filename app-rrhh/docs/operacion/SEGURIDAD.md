# Seguridad operativa

## Responsabilidades

El equipo mantiene controles de aplicación, dependencias, rutas, validaciones, secretos y evidencia. Hostinger administra el motor PHP y procesos productivos del servicio contratado; una actualización de Composer o un ZIP nuevo no sustituye el parche del intérprete.

Conservar `.env` privado, contraseñas/tokens protegidos, autorización de descargas y CSRF. No publicar phpinfo(), dumps, diagnósticos con secretos, evidencias de usuarios reales o carpetas de rescate. Revisar `public/.htaccess`, la ubicación privada del backend y permisos efectivos sin asumir que una extensión bloqueada cubre toda la seguridad.

## Seguimiento PHP del 21 de octubre de 2026

La versión productiva reportada por el encargado es **8.3.35**. La revisión documental realizada ese día la situó dentro del rango del aviso [GHSA-w8hw-w9fx-j79w](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w), que identifica 8.3.36 como corregida en esa rama. También se revisó el [aviso relacionado](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).

**Estado conocido:** pendiente de evidencia de actualización o parche equivalente por Hostinger. No se confirmó compromiso del sitio ni se accedió al panel productivo. No inferir el SAPI o la presencia de un backport únicamente del número de versión o del uso de Hostinger.

Solicitar al proveedor confirmación de versión/compilación, SAPI y corrección efectiva; después verificar el entorno web del subdominio y las funciones utilizadas. No ejecutar cargas de agotamiento de memoria ni pruebas de explotación. Los límites de 5 MB en la aplicación y ocultar errores no acreditan que PHP esté corregido.

El Word del hallazgo se conserva fuera del repositorio en la documentación del responsable. No incorporar copias con datos privados a Git para cerrar este pendiente. Registrar aquí la fecha y evidencia no sensible cuando el proveedor confirme; no convertir una fecha propuesta en una actualización realizada.

## Operaciones con datos

Pruebas sobre bases/usuarios ficticios. Antes de cambios reales, revisar alcance y respaldo; conservar bajas lógicas e historial. Para identidad y acceso consultar [Roles](../proyecto/ROLES_Y_PERMISOS.md); para trazabilidad consultar [Auditoría](../modulos/AUDITORIA.md).
