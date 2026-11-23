# Recepción de reseñas

## Propósito y flujo

El módulo administra la recolección, validación y custodia de los testimonios de experiencia de los practicantes, junto con la autorización legal expresa para su uso según la Ley 19.628 de Protección de la Vida Privada.

El practicante descarga la plantilla oficial en PDF (`resources/docs/autorizacion_resena_practica.pdf`), completa la valoración de 1 a 5 estrellas, selecciona aspectos destacados, define el canal de comunicación (Discord, Correo, WhatsApp o Documento), redacta su reseña y adjunta el documento firmado aceptando la exención y el tratamiento normado de sus datos.

ADMIN/SUPERVISOR supervisa el estado de entrega en tiempo real mediante tarjetas métricas (*Pendientes de Entrega*, *Reseñas Recibidas*, *Reseñas Publicadas*) y una tabla con el listado de practicantes activos. Puede consultar el testimonio, descargar el archivo firmado y autorizar la publicación oficial de la reseña.

## Reglas

- Cada práctica activa admite como máximo una reseña (`uk_resena_practica`); si el estudiante envía una corrección, se actualiza el registro existente preservando la unicidad.
- La recepción de la reseña (`RECIBIDA`) actualiza automáticamente la bandera `resena_recibida = 1` en la tabla `acompanamiento`.
- La aprobación por parte del supervisor (`PUBLICADA`) actualiza la bandera `resena_publicada = 1` en `acompanamiento`, habilitando el cierre del seguimiento para la certificación final.
- Los documentos de autorización firmados (PDF o imagen, hasta 8 MB) se guardan en `uploads/resenas/` fuera del control de versiones de Git y sin exposición web directa.
- La descarga de la plantilla y de los documentos firmados se realiza exclusivamente mediante controladores autenticados con cabeceras binarias protegidas.
- Todos los envíos por formulario validan el token CSRF de la sesión activa y exigen rol `PRACTICANTE` para el envío y `ADMIN` o `SUPERVISOR` para la revisión.

## Código y datos

`src/Models/ResenasModel.php`, `src/Controllers/Practicante/ResenasController.php`, `src/Views/practicante/resenas.view.php`, `src/Controllers/Supervisor/ResenasController.php`, `src/Views/supervisor/resenas.view.php`, `config/routes.php` y `src/Views/layout/sidebar.php`.

Tablas: `resenas_practica`, `acompanamiento`, `practicantes` y `practicas`. Parche SQL: `database/2026-11-04_patch_resenas_practica_013.sql`.

Validar autorización por roles, carga de archivos y comprobación de rutas según [Pruebas](../desarrollo/PRUEBAS.md).
