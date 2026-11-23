# Autenticación y usuarios

## Flujo

El usuario accede con correo y contraseña individual. El servidor comprueba cuenta habilitada, verificación, bloqueo y hash de contraseña, obtiene roles activos y establece el contexto. Un usuario multirrol selecciona después del login; una sesión de selección pendiente no equivale a una sesión operativa completa.

Si la ficha del practicante está incompleta, se dirige a completarla. Una ficha completa entra al marcaje. Activación y recuperación de contraseña tienen flujos separados mediante tokens; no se usa un login genérico por rol ni un autoregistro público libre.

## Reglas

- Contraseñas y tokens se conservan con los mecanismos de hash del sistema; no añadir almacenamiento en texto plano.
- Regenerar la sesión al autenticar; conservar caducidad, cookie y registro operativo. Logout cierra la sesión y cookie, incluso si se eligió mantener sesión.
- La permanencia se configura con variables de entorno, no editando este documento.
- No otorgar ADMIN a una cuenta asociada a practicante. La combinación con SUPERVISOR mantiene la prohibición de autosupervisión.
- No mostrar detalles internos, contraseñas o tokens en errores o auditorías.
- Usar la infraestructura de correo existente. Probar con SMTP ficticio; no enviar correos a personas reales durante una prueba sin autorización.

## Código y datos

`src/Services/AuthService.php`, `src/Services/MailService.php`, `src/Models/UsuarioModel.php`, `src/Controllers/Auth/`, `src/Controllers/Admin/` y `src/Views/auth/`.

Tablas: `usuarios_sistema`, `usuarios_roles`, `roles_sistema`, `sesiones_usuario` y `tokens_usuario`. Los destinos exactos están en `config/routes.php`; la configuración de entorno en `config/`.

Validación: configuración, MVC, rutas y pruebas funcionales de login/sesiones según [Pruebas](../desarrollo/PRUEBAS.md). Para permisos comunes, consultar [Roles](../proyecto/ROLES_Y_PERMISOS.md).

Para distinguir la respuesta genérica de recuperación del resultado real de SMTP y revisar incidencias sin exponer credenciales, consultar [operación de correo](../operacion/CORREO_SMTP.md).
