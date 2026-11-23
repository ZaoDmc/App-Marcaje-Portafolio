# Correo transaccional y diagnóstico SMTP

## Configuración vigente

La recuperación de contraseña y la activación usan `src/Services/MailService.php`, con configuración en `config/mail.php`. El entorno activo en Hostinger es el archivo privado `.env` del checkout `app-rrhh`. No editar el entorno de `backend-rrhh` como si siguiera siendo la instalación activa.

Variables utilizadas: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` y `MAIL_DEBUG`. No incorporar sus valores privados a Git, capturas, diagnósticos públicos ni mensajes de soporte. Producción mantiene `MAIL_DEBUG=0`.

El formulario de recuperación responde de forma genérica para no revelar si una cuenta existe. Ese mensaje no demuestra que SMTP aceptó el envío. No cambiar esta respuesta por detalles de autenticación, direcciones internas o tokens.

## Comprobante de marcaje (1.4.0)

`mailer_enviar_comprobante_marca()` usa el mismo SMTP y remitente. Variables opcionales:

| Variable | Valor por defecto | Uso |
| --- | --- | --- |
| `MAIL_COMPROBANTE_ACTIVO` | `1` | `0` apaga el envío sin tocar código |
| `MAIL_COMPROBANTE_MENSAJE` | Texto de respaldo | Pie del correo; vacío usa el valor por defecto |
| `MAIL_COMPROBANTE_LOGO` | `assets/brand/logo_correo.png` | Ruta relativa a `public/`, incrustada por CID; vacío envía sin imagen |

El dominio del remitente es el de `MAIL_FROM_ADDRESS`; debe pertenecer al buzón SMTP autenticado para no caer en spam. Límite de conexión: 10 s. En local con Docker, Mailpit (`MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `MAIL_ENCRYPTION=none`) captura los correos en `http://localhost:8025` sin enviarlos a Internet; `docker-compose.yml` está excluido localmente de Git.

### Buzón de envío vigente (04/11/2026)

El buzón vigente es `recursos.humanos@epracticas.cl` en Hostinger (`smtp.hostinger.com:465`, SSL). Reemplaza a `MarcajeEpracticas@epracticas.cl`, usado en la prueba local del 03/11/2026. El responsable comprobó desde local que el envío funciona con el nuevo buzón; la clave vive solo en `.env` privados.

Para producción, quien tenga acceso al checkout `app-rrhh` debe respaldar su `.env` y cambiar únicamente `MAIL_USERNAME`, `MAIL_FROM_ADDRESS` (mismo buzón, para no caer en spam) y `MAIL_PASSWORD`. Confirmar que `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` y `APP_URL=https://rrhh.epracticas.cl` ya estén así. Eso también restablecería activación y recuperación de contraseña, afectadas por el incidente siguiente. Pendiente: ejecutar ese cambio y comprobar entrega en producción.

## Incidente del 26 de octubre de 2026

El responsable informó que el formulario mostraba la respuesta genérica, pero no llegaba el correo después de la migración.

Comprobaciones directas, sin enviar mensajes ni generar tokens:

- Checkout activo: rama `prod`, revisión `c1a7178`, árbol limpio.
- `.env` activo y anterior idénticos por hash. Las siete variables de conexión/remitente estaban presentes y no vacías; los valores efectivos coincidían con el archivo privado.
- Configuración de correo, cargador de entorno, servicio de correo y controlador de recuperación equivalentes entre ambas instalaciones al ignorar comentarios, formato y finales de línea.
- PHPMailer 6.12.0 se carga y se construye correctamente en ambas instalaciones. Puerto configurado: 465; autenticación habilitada y debug desactivado.
- La conexión SMTP del checkout activo rechazó la autenticación. La misma prueba con la instalación anterior y sus mismos parámetros también fue rechazada. No se envió ningún mensaje.
- PHP CLI consultado: 8.3.36. Esta lectura no acredita la versión web ni cierra por sí sola el seguimiento de seguridad del proveedor.

La evidencia identifica un rechazo de autenticación SMTP, pero no determina si se debe a una contraseña desactualizada, estado del buzón, credencial específica de aplicación o política del proveedor. No atribuir el fallo al enlace público ni afirmar que se corrigió cambiando código. Pendiente verificar el acceso al buzón y contrastar la credencial SMTP con el responsable.

Los auxiliares usados están bajo `scratch/ssh-rrhh-20261023/correo-*-20261026.sh`, ignorados por Git. Ocultan secretos y clasifican resultados; no son rutas web ni pruebas de envío. No repetir intentos de autenticación fallidos en bucle.

## Procedimiento de resolución

1. Confirmar el síntoma: error al solicitar, correo no recibido o enlace recibido que falla. No pedir enlaces con tokens ni contraseñas en el chat.
2. Verificar en privado que el buzón indicado por `MAIL_USERNAME` está operativo y que sus datos SMTP corresponden al servicio contratado. Un login correcto en webmail ayuda a verificar el buzón, pero no certifica la autenticación SMTP ni una contraseña de aplicación.
3. Si hay que corregir la credencial, conservar un respaldo privado del `.env` activo y actualizar únicamente las variables de correo necesarias en `app-rrhh/.env`, con permisos 600. Preservar base de datos, URL y el resto del entorno. Introducir los valores mediante un editor privado del servidor; no colocarlos en comandos visibles, historial de shell o argumentos de proceso.
4. Comprobar que la configuración efectiva sigue cargando y hacer una conexión/autenticación SMTP sin enviar mensajes. Si el rechazo persiste con los datos confirmados, revisar el estado y la política SMTP con el proveedor; no modificar bibliotecas al azar ni activar debug público.
5. Para comprobar entrega y enlace, obtener autorización para usar un buzón de prueba controlado. La autenticación SMTP correcta no prueba entrega en bandeja de entrada. La recuperación real genera un token y puede invalidar tokens previos: no usar cuentas de terceros como fixtures.
6. Registrar el resultado, fecha y alcance sin guardar credenciales ni tokens. Mantener la respuesta pública genérica y no declarar cerrado el incidente hasta comprobar el alcance correspondiente.

No hace falta un `git pull`, un ZIP o una migración SQL para modificar una credencial privada. Una corrección del `.env` no se publica ni se replica desde GitHub.
