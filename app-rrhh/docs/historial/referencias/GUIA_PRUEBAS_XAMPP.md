> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Guia de pruebas locales en XAMPP

## Estado actual

El proyecto ya cuenta con la primera secuencia funcional implementada hasta:

```text
practicante/mi-historial.php
```

URL local oficial:

```text
http://localhost/asistencia/
```

Se puede probar:

- Configuracion centralizada.
- Conexion PDO.
- Configuracion SMTP base.
- Schema y seeds.
- Login individual.
- Selector de rol.
- Logout.
- Activacion de cuenta por token.
- Recuperacion y restablecimiento de contrasena.
- Dashboards base por rol.
- Gestion basica de usuarios SUPERVISOR desde ADMIN.
- Pre-registro de practicantes por ADMIN/SUPERVISOR.
- Pre-registro con formulario completo y solo tres campos obligatorios minimos.
- Gestion ADMIN/SUPERVISOR de horarios pactados.
- Cierre de ficha del practicante despues de activar cuenta.
- Datos propios/contacto/direccion del practicante.
- Marcacion basica de asistencia.
- Visualizacion del horario pactado del dia en marcacion, cuando exista.
- Historial del practicante.
- Preparacion de indices de `marcas_asistencia` para correcciones/anulaciones logicas.
- Pantalla ADMIN/SUPERVISOR para revision de jornadas, anulacion granular de marcas y correcciones basicas.

Para probar flujos reales con datos, primero se debe importar la base `asistencia` con `schema.sql` y seeds.

---

## Requisitos

- XAMPP instalado.
- Apache iniciado desde XAMPP Control Panel.
- MySQL iniciado desde XAMPP Control Panel.
- Proyecto ubicado en:

```text
C:\xampp\htdocs\asistencia
```

- URL local:

```text
http://localhost/asistencia/
```

---

## 1. Pruebas sin importar base de datos

Estas pruebas sirven para confirmar que PHP, rutas, assets y pantallas cargan correctamente.

### 1.1 Validar sintaxis PHP

Ejecutar en PowerShell desde:

```text
C:\xampp\htdocs\asistencia
```

Comandos:

```powershell
C:\xampp\php\php.exe -l config\app.php
C:\xampp\php\php.exe -l config\database.php
C:\xampp\php\php.exe -l config\mail.php
C:\xampp\php\php.exe -l includes\helpers.php
C:\xampp\php\php.exe -l includes\auditoria.php
C:\xampp\php\php.exe -l includes\auth.php
C:\xampp\php\php.exe -l includes\mailer.php
C:\xampp\php\php.exe -l includes\reglas_asistencia.php
C:\xampp\php\php.exe -l includes\practicante_registro.php
C:\xampp\php\php.exe -l includes\horarios_practicante.php
C:\xampp\php\php.exe -l login.php
C:\xampp\php\php.exe -l seleccionar-rol.php
C:\xampp\php\php.exe -l logout.php
C:\xampp\php\php.exe -l activar-cuenta.php
C:\xampp\php\php.exe -l recuperar-password.php
C:\xampp\php\php.exe -l restablecer-password.php
C:\xampp\php\php.exe -l admin\dashboard.php
C:\xampp\php\php.exe -l admin\crear-practicante.php
C:\xampp\php\php.exe -l admin\practicantes.php
C:\xampp\php\php.exe -l supervisor\dashboard.php
C:\xampp\php\php.exe -l supervisor\crear-practicante.php
C:\xampp\php\php.exe -l supervisor\practicantes.php
C:\xampp\php\php.exe -l practicante\dashboard.php
C:\xampp\php\php.exe -l practicante\mis-datos.php
C:\xampp\php\php.exe -l practicante\foto-perfil.php
C:\xampp\php\php.exe -l practicante\descargar-documento.php
C:\xampp\php\php.exe -l practicante\marcar-asistencia.php
C:\xampp\php\php.exe -l practicante\mi-historial.php
```

Resultado esperado:

```text
No syntax errors detected
```

### 1.2 Abrir login

Abrir en navegador:

```text
http://localhost/asistencia/login.php
```

Resultado esperado:

- Se ve la pantalla de inicio de sesion.
- Se ve el logo del portal.
- El formulario muestra correo y contrasena.
- El enlace de recuperacion apunta a `/asistencia/recuperar-password.php`.

Nota:

Si todavia no se importo la base de datos, al intentar iniciar sesion aparecera un error generico. Eso es esperado.

### 1.3 Abrir pantallas publicas con flujos sin token

Abrir:

```text
http://localhost/asistencia/activar-cuenta.php
http://localhost/asistencia/restablecer-password.php
```

Resultado esperado:

- Muestran mensaje de enlace invalido o incompleto.
- No exponen detalles SQL.
- No modifican cuentas.

### 1.4 Abrir recuperacion de contrasena

Abrir:

```text
http://localhost/asistencia/recuperar-password.php
```

Resultado esperado:

- Se ve el formulario para ingresar correo.
- La respuesta publica es generica.
- Si no existe base importada, el error no se expone al usuario.

---

## 2. Preparar base de datos local

Estas pruebas son necesarias para validar login real, sesiones, roles, tokens, auditoria, pre-registro y asistencia.

Importante:

Si ya habias importado la base antes de los cambios de pre-registro minimo, campo `pais` o catalogo normalizado de regiones/comunas, debes resetear la base local antes de probar.

`schema.sql` usa `CREATE TABLE IF NOT EXISTS`, por lo que no reemplaza tablas antiguas existentes. Si no reseteas, la tabla `regiones` puede conservar columnas antiguas como `nombre` y `codigo`, y `seed_regiones_comunas.sql` fallara con:

```text
ERROR 1054 (42S22): Unknown column 'nombre_region' in 'field list'
```

Para XAMPP local de pruebas, ejecuta primero:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root -e ""DROP DATABASE IF EXISTS asistencia; CREATE DATABASE asistencia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"""
```

### 2.1 Crear base de datos

Opcion A, desde phpMyAdmin:

1. Abrir:

```text
http://localhost/phpmyadmin/
```

2. Crear base de datos:

```text
asistencia
```

3. Usar cotejamiento:

```text
utf8mb4_unicode_ci
```

Opcion B, desde PowerShell si ya hiciste el reset anterior o si partes sin base previa:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root -e ""CREATE DATABASE IF NOT EXISTS asistencia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"""
```

### 2.2 Importar schema

Desde PowerShell:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\schema.sql"
```

O desde phpMyAdmin:

1. Seleccionar base `asistencia`.
2. Entrar a "Importar".
3. Seleccionar `database/schema.sql`.
4. Ejecutar importacion.

Si la base ya existia y no se resetea, aplicar tambien el parche de indices de marcas:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\2026-06-03_patch_marcas_asistencia_indices_001.sql"
```

Para bases existentes, aplicar tambien el parche de documentos opcionales del practicante:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\2026-06-11_patch_documentos_practicante_002.sql"
```

### 2.3 Importar catalogo territorial

Desde PowerShell:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_regiones_comunas.sql"
```

O desde phpMyAdmin importando:

```text
database/seed_regiones_comunas.sql
```

### 2.4 Importar catalogos generales

Desde PowerShell:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_catalogos.sql"
```

O desde phpMyAdmin importando:

```text
database/seed_catalogos.sql
```

### 2.5 Importar usuario administrador inicial

Desde PowerShell:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_admin.sql"
```

O desde phpMyAdmin importando:

```text
database/seed_admin.sql
```

Importante:

- `seed_admin.sql` no guarda contrasena en texto plano.
- Para probar login real, define una contrasena local conocida generando un hash con `password_hash()` y actualizando el usuario administrador solo en tu entorno local.
- No documentes ni subas contrasenas reales al proyecto.

Ejemplo para generar un hash local:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('CAMBIAR_SOLO_LOCAL', PASSWORD_DEFAULT);"
```

Luego actualizar el administrador local reemplazando `HASH_GENERADO_AQUI`.

Importante:

No pegues un hash bcrypt con `$2y$...` dentro de un comando PowerShell con comillas dobles, porque PowerShell puede interpretar partes del hash como variables y guardarlo corrupto. Usa este formato seguro:

```powershell
@'
UPDATE usuarios_sistema
SET password_hash = 'HASH_GENERADO_AQUI',
    estado_cuenta = 'ACTIVO',
    email_verificado = 1,
    debe_cambiar_password = 0,
    activo = 1,
    intentos_fallidos = 0,
    ultimo_intento_login = NULL,
    bloqueado_hasta = NULL
WHERE email = 'admin@localhost.test';
'@ | C:\xampp\mysql\bin\mysql.exe -u root asistencia
```

Verificacion recomendada:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT email, LEFT(password_hash, 4) AS hash_inicio, CHAR_LENGTH(password_hash) AS hash_largo, estado_cuenta, email_verificado, activo, intentos_fallidos, bloqueado_hasta FROM usuarios_sistema WHERE email='admin@localhost.test';"
```

Resultado esperado:

```text
hash_inicio = $2y$
hash_largo = 60
estado_cuenta = ACTIVO
email_verificado = 1
activo = 1
```

---

## 3. Pruebas con base importada

### 3.1 Probar conexion PDO

Ejecutar:

```powershell
C:\xampp\php\php.exe -r "require 'config/database.php'; echo db() instanceof PDO ? 'OK' : 'ERROR';"
```

Resultado esperado:

```text
OK
```

### 3.2 Probar login ADMIN

Abrir:

```text
http://localhost/asistencia/login.php
```

Usar:

```text
Email: admin@localhost.test
Contrasena: la contrasena local usada para generar el hash
```

La pantalla de login debe mostrar un bloque lateral de bienvenida al portal de e-practicas y el checkbox `Mantener la sesion iniciada`.

Resultado esperado:

- Si el administrador tiene solo rol `ADMIN`, se redirige a:

```text
http://localhost/asistencia/admin/dashboard.php
```

- Se ve el dashboard base de administrador.
- El enlace "Crear practicante" apunta a:

```text
http://localhost/asistencia/admin/crear-practicante.php
```

Dashboard avanzado inicial ADMIN:

- Muestra metricas de practicantes activos, practicas activas, horas de los ultimos 30 dias, avance global, pendientes/ausencias, usuarios activos y auditoria 24h.
- La tarjeta `Auditoria` abre `admin/auditoria.php`.
- La tarjeta `Reportes` abre `admin/reportes.php`.

Prueba adicional:

- Repetir el login marcando `Mantener la sesion iniciada`.
- Cerrar y abrir el navegador, o volver a abrir directamente `http://localhost/asistencia/`.
- Resultado esperado: si no se ejecuto `logout.php` y no vencio la expiracion persistente, la sesion sigue activa y redirige al dashboard del rol activo.

### 3.3 Verificar sesion operativa

En phpMyAdmin, revisar tabla:

```text
sesiones_usuario
```

Resultado esperado:

- Existe una fila nueva.
- `id_rol_activo` no es NULL.
- `activa = 1`.
- `session_id_hash` tiene hash, no el ID plano de sesion.

### 3.4 Crear o asignar supervisor desde ADMIN

Desde sesion ADMIN:

```text
http://localhost/asistencia/admin/usuarios.php
```

Caso A, correo nuevo:

- Lista usuarios existentes, roles, estado y actividad.
- Permite crear usuario externo SUPERVISOR con correo valido.
- El nuevo usuario queda `PENDIENTE_ACTIVACION`.
- Se asigna rol `SUPERVISOR`.
- Se genera token de activacion.
- Si SMTP/PHPMailer no esta configurado, se muestra enlace manual de activacion.
- Se auditan `CREACION_USUARIO`, `ROL_ASIGNADO` y `TOKEN_GENERADO`.

Despues abrir el enlace de activacion, definir contrasena y confirmar que el login redirige a:

```text
http://localhost/asistencia/supervisor/dashboard.php
```

Dashboard avanzado inicial SUPERVISOR:

- Muestra metricas de practicantes activos, practicas activas, horas de los ultimos 30 dias, avance global, pendientes de revision y ausencias esperadas.
- La tarjeta `Reportes` abre `supervisor/reportes.php`.
- Si el supervisor tambien es practicante, sus metricas operativas no deben incluir su propia practica.

Caso B, correo de usuario existente:

- Permite ingresar el correo de un usuario PRACTICANTE ya existente.
- No crea una segunda cuenta ni muestra error de correo duplicado.
- Asigna o reactiva el rol `SUPERVISOR` en `usuarios_roles`.
- No genera token nuevo porque la cuenta existente mantiene sus credenciales actuales.
- Si el usuario queda con roles `PRACTICANTE` y `SUPERVISOR`, el login debe redirigir a `seleccionar-rol.php`.

Caso C, quitar SUPERVISOR a practicante:

- En la tabla de usuarios, un practicante-supervisor muestra accion para quitar `SUPERVISOR`.
- La accion deja activo el rol `PRACTICANTE`.
- El rol `SUPERVISOR` queda con `rol_activo = 0`, sin borrar fisicamente la fila.
- Se audita `ROL_REMOVIDO`.
- En el siguiente login, si el usuario solo conserva `PRACTICANTE`, no debe aparecer selector de rol.

### 3.5 Probar pre-registro de practicante

Desde sesion ADMIN:

```text
http://localhost/asistencia/admin/crear-practicante.php
```

O desde sesion SUPERVISOR:

```text
http://localhost/asistencia/supervisor/crear-practicante.php
```

Probar primero el caso minimo:

```text
Primer nombre
Apellido paterno
Correo electronico
```

Resultado esperado:

- El formulario conserva todos los campos disponibles.
- Solo `Primer nombre`, `Apellido paterno` y `Correo` aparecen como obligatorios con asterisco rojo.
- Se muestra la nota: los campos marcados con `*` son obligatorios.
- Permite crear el pre-registro dejando los demas campos vacios.
- El bloque `Horario pactado` puede quedar vacio en pre-registro.
- Si se marca algun dia con jornada en `Horario pactado`, debe guardar entrada, salida, colacion opcional y asociarlo a la practica cuando exista fecha de inicio.

Probar despues casos con datos institucionales opcionales:

- Institucion `Duoc UC`, `INACAP` o `ENAC`: debe exigir sede desde catalogo.
- Institucion `Otra`: debe exigir "Ingrese su institucion" e "Ingrese su sede", y no usar `id_sede` operativamente.

Resultado esperado:

- Se crea fila en `practicantes`.
- Se crea fila en `practicas`.
- Se crea usuario asociado en `usuarios_sistema`.
- Se asigna rol `PRACTICANTE` en `usuarios_roles`.
- Se genera token `ACTIVACION_CUENTA` en `tokens_usuario` guardado como hash.
- Si SMTP/PHPMailer no esta configurado, se muestra enlace manual de activacion.
- Se registran eventos de auditoria `CREAR_PRACTICANTE`, `CREACION_USUARIO` y `TOKEN_GENERADO`.

### 3.6 Probar gestion ADMIN/SUPERVISOR de horarios pactados

Desde sesion ADMIN:

```text
http://localhost/asistencia/admin/practicantes.php
```

Desde sesion SUPERVISOR:

```text
http://localhost/asistencia/supervisor/practicantes.php
```

Resultado esperado:

- Lista practicantes activos con practica activa.
- Permite buscar por nombre, apellido, RUT o correo.
- Muestra resumen de carrera, sede, fecha de inicio y horas requeridas del practicante seleccionado.
- Muestra si el practicante tiene horario vigente o esta sin horario.
- Permite abrir un practicante y reemplazar su horario semanal indicando fecha de inicio de vigencia.
- Exige al menos un dia con jornada pactada.
- Al guardar, el horario anterior queda inactivo con fecha de termino y el nuevo queda vigente.
- No borra fisicamente horarios anteriores ni detalles.
- En rol ADMIN permite baja logica con confirmacion.
- La baja logica desactiva `practicantes`, practicas activas y cuenta vinculada, conservando historial.
- En rol SUPERVISOR no se muestra accion de baja logica.
- En rol SUPERVISOR, un usuario practicante-supervisor no puede modificar su propio horario.
- Muestra el area vigente de la practica cuando existe.
- ADMIN y SUPERVISOR pueden asignar o cambiar el area vigente desde catalogo.
- El cambio de area cierra el periodo anterior en `historial_areas_practicante` y abre uno nuevo.
- En rol SUPERVISOR, un usuario practicante-supervisor no puede modificar su propia area.
- Permite registrar excepciones puntuales sobre el horario vigente.
- Permite editar una excepcion reingresando la misma fecha y tipo.
- Permite anular logicamente una excepcion activa indicando motivo.
- `CAMBIO_HORARIO` y `ASISTENCIA_EXTRAORDINARIA` usan entrada/salida/colacion excepcional.
- `DIA_LIBRE`, `AUSENCIA_JUSTIFICADA`, `FERIADO` y `OTRO` pueden quedar sin horario.
- La marcacion del practicante usa la excepcion activa de la fecha antes que el horario semanal.
- Se audita como `EDITAR_PRACTICANTE`.
- El cambio de area se audita como `CAMBIAR_AREA`.

Validacion SQL opcional:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_horario, id_practica, fecha_inicio, fecha_fin, activo FROM horarios_practicante ORDER BY id_horario DESC LIMIT 5; SELECT id_horario, dia_semana, entrada_esperada, salida_esperada, horas_planificadas, activo FROM detalle_horario_practicante ORDER BY id_detalle_horario DESC LIMIT 10;"
```

Validacion SQL opcional despues de baja logica:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_practicante, email, activo FROM practicantes ORDER BY id_practicante DESC LIMIT 5; SELECT id_practica, id_practicante, activo FROM practicas ORDER BY id_practica DESC LIMIT 5; SELECT id_usuario, id_practicante, email, estado_cuenta, activo FROM usuarios_sistema WHERE id_practicante IS NOT NULL ORDER BY id_usuario DESC LIMIT 5;"
```

Validacion SQL opcional despues de cambiar area:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT hap.id_historial_area, hap.id_practica, ap.nombre AS area, hap.fecha_inicio, hap.fecha_fin, hap.asignado_por FROM historial_areas_practicante hap INNER JOIN areas_practica ap ON ap.id_area = hap.id_area ORDER BY hap.id_historial_area DESC LIMIT 10;"
```

Validacion SQL opcional despues de registrar excepcion:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_excepcion, id_horario, fecha, tipo_excepcion, entrada_esperada, salida_esperada, horas_planificadas, activo, observacion FROM excepciones_horario_practicante ORDER BY id_excepcion DESC LIMIT 10;"
```

### 3.7 Probar activacion de practicante

Usar el enlace manual mostrado en el pre-registro si SMTP no esta configurado.

Resultado esperado:

- La pantalla permite definir contrasena.
- La cuenta pasa a `ACTIVO`.
- `email_verificado = 1`.
- `debe_cambiar_password = 0`.
- El token queda usado.
- Se auditan `PASSWORD_CAMBIADA` y `CUENTA_ACTIVADA`.

### 3.8 Probar login PRACTICANTE

Abrir:

```text
http://localhost/asistencia/login.php
```

Usar el correo del practicante y la contrasena definida en activacion.

Resultado esperado:

- Si la ficha esta incompleta, se redirige automaticamente a:

```text
http://localhost/asistencia/practicante/mis-datos.php?completar=1
```

- Se muestra aviso indicando que debe completar la ficha antes de usar el resto del portal.
- Si intenta abrir manualmente `dashboard`, `marcar-asistencia` o `mi-historial` antes de completar ficha, vuelve a `mis-datos.php?completar=1`.

Despues de completar la ficha, el login o acceso normal debe permitir llegar directamente a `practicante/marcar-asistencia.php`.

### 3.9 Probar completar registro y Mi Perfil

Abrir:

```text
http://localhost/asistencia/practicante/mis-datos.php
```

Resultado esperado:

- Si la ficha esta incompleta, muestra modo `Completar registro`.
- Exige los campos requeridos para cerrar ficha, incluyendo `pais` en direccion.
- Permite ingresar RUT separado en numero y DV, o RUT completo con guion en el campo RUT.
- Los campos obligatorios muestran asterisco rojo y nota visible.
- Permite completar institucion, sede/campos manuales, carrera, tipo de practica, horas requeridas e inicio de practica solo en este cierre inicial.
- Exige registrar al menos un dia con jornada pactada antes de cerrar ficha.
- El horario pactado permite mismo horario para varios dias, horario diferenciado por dia y dias sin jornada.
- Permite subir foto de perfil opcional en JPG, JPEG o PNG, con maximo 5 MB.
- Permite subir documentos opcionales de practica en PDF, Word o Excel, con maximo 5 documentos activos y 5 MB por archivo.
- No exige foto ni documentos para completar la ficha.
- Despues de completar ficha, muestra modo `Mi Perfil`.
- En modo `Mi Perfil`, muestra avatar con foto de perfil o iniciales si no existe foto.
- En modo `Mi Perfil`, permite subir o cambiar la foto de perfil con una accion visible `Subir foto` o `Cambiar foto`, sin mostrar rutas internas.
- En modo `Mi Perfil`, lista documentos activos subidos por el practicante.
- En modo `Mi Perfil`, permite subir documentos con una accion visible `Subir documentos`.
- La descarga de documentos usa `practicante/descargar-documento.php` y solo permite documentos propios activos.
- En modo `Mi Perfil`, mantiene editables telefono, nacionalidad, direccion, numero, departamento, pais, region y comuna.
- En modo `Mi Perfil`, permite cargar posteriormente la foto o documentos faltantes.
- La comuna se muestra como selector dependiente de la region y no como texto libre.
- En modo `Mis datos`, deja en solo lectura institucion, sede, carrera, tipo de practica, horas requeridas e inicio de practica.
- Se audita `EDICION_DATOS_PROPIOS_PRACTICANTE` y, cuando hay archivos, `SUBIR_DOCUMENTO_PRACTICANTE`.

### 3.10 Probar marcacion de asistencia

Abrir:

```text
http://localhost/asistencia/practicante/marcar-asistencia.php
```

Resultado esperado:

- Si existe practica activa en rango de fecha, se muestra la jornada del dia.
- Se visualiza el horario pactado del dia cuando existe.
- Si no existe horario pactado para el dia, se muestra aviso de asistencia extraordinaria para revision.
- Sin marcas previas, solo permite `ENTRADA`.
- Despues de `ENTRADA`, permite `ENTRADA_COLACION` o `SALIDA`.
- Despues de `ENTRADA_COLACION`, permite `SALIDA_COLACION` o `SALIDA`.
- Despues de `SALIDA_COLACION`, permite `SALIDA`.
- Despues de `SALIDA`, no permite nuevas marcas.
- La jornada recalcula estado, entrada, salida y horas.

### 3.11 Probar historial de asistencia

Abrir:

```text
http://localhost/asistencia/practicante/mi-historial.php
```

Resultado esperado:

- Permite filtrar por practica y rango de fechas.
- Muestra horas realizadas.
- Muestra horas restantes.
- Muestra porcentaje de avance.
- Muestra jornadas del periodo con marcas vigentes.
- No muestra marcas anuladas como vigentes.

### 3.12 Probar logout

Abrir:

```text
http://localhost/asistencia/logout.php
```

Resultado esperado:

- Redirige a:

```text
http://localhost/asistencia/login.php?logout=1
```

En tabla `sesiones_usuario`:

- `fecha_cierre` queda con fecha/hora.
- `cerrada_por_logout = 1`.
- `activa = 0`.
- Si se habia marcado `Mantener la sesion iniciada`, la cookie persistente tambien queda destruida.

### 3.13 Verificar auditoria

Revisar tabla:

```text
auditoria_cambios
```

Eventos esperados segun flujo probado:

```text
LOGIN_EXITOSO
LOGOUT
CREAR_PRACTICANTE
CREACION_USUARIO
ROL_ASIGNADO
TOKEN_GENERADO
PASSWORD_CAMBIADA
CUENTA_ACTIVADA
EDICION_DATOS_PROPIOS_PRACTICANTE
EDITAR_PRACTICANTE
CORREGIR_ASISTENCIA
ANULAR_MARCA_ASISTENCIA
ANULAR_ASISTENCIA
```

---

## 4. Pruebas de recuperacion de contrasena

### 4.1 Solicitar recuperacion

Abrir:

```text
http://localhost/asistencia/recuperar-password.php
```

Resultado esperado:

- La respuesta publica siempre es generica.
- No revela si el correo existe.
- Si el correo existe y la cuenta esta activa, genera token `RECUPERACION_PASSWORD`.
- Se guarda solo `token_hash`.
- Se auditan `RECUPERACION_SOLICITADA` y `TOKEN_GENERADO`.

### 4.2 Restablecer contrasena

Usar el enlace enviado por correo cuando SMTP este configurado.

Si SMTP no esta configurado, se puede validar manualmente consultando `tokens_usuario` y generando un flujo de prueba controlado desde base, pero no se debe exponer tokens reales ni subirlos al repositorio.

Resultado esperado:

- Permite definir nueva contrasena.
- Guarda con `password_hash()`.
- Marca token como usado.
- Limpia intentos fallidos y bloqueo temporal.
- Audita `PASSWORD_CAMBIADA`.

---

## 5. Gestion de asistencia ADMIN/SUPERVISOR

Abrir como ADMIN:

```text
http://localhost/asistencia/admin/asistencia.php
```

Abrir como SUPERVISOR:

```text
http://localhost/asistencia/supervisor/asistencia.php
```

Validaciones esperadas:

- La tarjeta `Asistencia` del dashboard abre la pantalla correspondiente.
- Se listan jornadas con filtros por fecha, estado y practicante.
- Permite generar ausencias esperadas para fechas pasadas dentro del rango filtrado, con confirmacion explicita.
- La generacion solo crea jornadas para dias planificados sin jornada previa.
- Permite resolver ausencias esperadas como `JUSTIFICADA`, `NO_JUSTIFICADA` o `PENDIENTE_REVISION`.
- Una ausencia justificada queda `OBSERVADA` con `horas_calculadas = 0`; una no justificada queda `INCOMPLETA`; una pendiente queda `PENDIENTE_REVISION`.
- El detalle muestra marcas vigentes y anuladas.
- El detalle muestra bloques de area de la jornada si existen.
- ADMIN y SUPERVISOR pueden registrar bloques de area por tramo dentro de la fecha de la jornada.
- El sistema rechaza bloques con fin anterior al inicio o solapados con otro bloque de la misma jornada.
- ADMIN y SUPERVISOR pueden anular marcas individuales con motivo.
- ADMIN y SUPERVISOR pueden registrar una marca correctiva si la secuencia vigente la permite.
- ADMIN y SUPERVISOR pueden dejar la jornada `OBSERVADA` o `PENDIENTE_REVISION`.
- ADMIN y SUPERVISOR pueden aprobar horas extra cuando la jornada tenga `flag_horas_extra = 1`.
- Solo ADMIN ve y puede ejecutar anulacion completa de jornada.
- En rol SUPERVISOR, un practicante-supervisor no puede registrar bloques sobre su propia jornada.

Verificar auditoria:

```sql
SELECT evento, tabla_afectada, id_registro_afectado, created_at
FROM auditoria_cambios
WHERE evento IN ('CORREGIR_ASISTENCIA', 'ANULAR_MARCA_ASISTENCIA', 'ANULAR_ASISTENCIA', 'CAMBIAR_AREA', 'APROBAR_HORAS_EXTRA')
ORDER BY id_auditoria DESC
LIMIT 20;
```

Verificar aprobacion de horas extra:

```sql
SELECT id_jornada, fecha, estado_jornada, horas_calculadas, flag_horas_extra, horas_extra_aprobadas, aprobada_por
FROM jornadas_asistencia
WHERE flag_horas_extra = 1
ORDER BY id_jornada DESC
LIMIT 20;
```

Verificar ausencias esperadas:

```sql
SELECT id_jornada, id_practica, fecha, estado_jornada, flag_ausencia_esperada, observacion
FROM jornadas_asistencia
WHERE flag_ausencia_esperada = 1
ORDER BY id_jornada DESC
LIMIT 20;
```

Verificar bloques de area:

```sql
SELECT baj.id_bloque_area, baj.id_jornada, ap.nombre AS area, baj.hora_inicio, baj.hora_fin, baj.horas_asignadas, baj.asignado_por
FROM bloques_area_jornada baj
INNER JOIN areas_practica ap ON ap.id_area = baj.id_area
ORDER BY baj.id_bloque_area DESC
LIMIT 20;
```

## 6. Reportes basicos ADMIN/SUPERVISOR

Abrir como ADMIN:

```text
http://localhost/asistencia/admin/reportes.php
```

Abrir como SUPERVISOR:

```text
http://localhost/asistencia/supervisor/reportes.php
```

Validaciones esperadas:

- La tarjeta `Reportes` del dashboard abre la pantalla correspondiente.
- Permite filtrar por rango de fechas, estado de practica y practicante.
- Muestra practicas filtradas, horas del periodo, avance global, jornadas pendientes y ausencias esperadas.
- La tabla muestra horas totales, avance, horas del periodo, jornadas y alertas por practica.
- En rol SUPERVISOR, un practicante-supervisor no ve su propia practica en el reporte operativo.

Verificar datos base:

```sql
SELECT COUNT(*) AS practicas_activas
FROM practicas pr
INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
WHERE pr.activo = 1
  AND p.activo = 1;

SELECT COALESCE(SUM(CASE WHEN estado_jornada IN ('VALIDA','OBSERVADA','CORREGIDA') THEN horas_calculadas ELSE 0 END),0) AS horas_periodo
FROM jornadas_asistencia
WHERE fecha BETWEEN '2026-05-04' AND '2026-06-03';

SELECT COUNT(*) AS pendientes_revision
FROM jornadas_asistencia
WHERE estado_jornada = 'PENDIENTE_REVISION';

SELECT COUNT(*) AS ausencias_esperadas
FROM jornadas_asistencia
WHERE flag_ausencia_esperada = 1;
```

---

## 7. Auditoria visible ADMIN

Abrir como ADMIN:

```text
http://localhost/asistencia/admin/auditoria.php
```

Validaciones esperadas:

- La tarjeta `Auditoria` del dashboard abre la pantalla.
- Permite filtrar por rango de fechas, evento, tabla y busqueda simple.
- Muestra eventos listados, eventos de seguridad, eventos funcionales y usuarios actores.
- La tabla muestra fecha, evento, actor, rol contexto, tabla afectada, registro, IP y resumen de valores.
- Los valores sensibles como tokens, hashes o passwords aparecen redactados.
- No permite editar, borrar ni exportar registros de auditoria.

Consulta de apoyo:

```sql
SELECT evento, tabla_afectada, id_registro_afectado, created_at
FROM auditoria_cambios
ORDER BY id_auditoria DESC
LIMIT 20;
```

---

## 8. Flujos no cubiertos en esta etapa

Los siguientes modulos existen como placeholders o quedan fuera del orden inicial de 25 pasos:

- Correcciones avanzadas por bloque de area y flujos de aprobacion posteriores mas detallados.
- Gestion de areas.
- Reportes avanzados y exportacion XLSX real.
- Evaluaciones.
- Revision administrativa de documentos del practicante.

---

## 9. Pendientes tecnicos conocidos

- `Bootstrap` se esta cargando desde CDN. Para probar sin internet, se debe instalar Bootstrap local en `assets`.
- `PHPMailer` aun no esta instalado en `vendor`; sin PHPMailer o SMTP configurado no habra envio real de correos.
- La base de datos no se importa automaticamente; debe importarse manualmente desde phpMyAdmin o PowerShell.
- Despues del cambio de schema para pre-registro minimo y `pais`, la base local debe resetearse antes de probar el flujo completo.
- La pantalla de marcacion muestra horario pactado si existe.
- El horario pactado puede capturarse en pre-registro, cierre inicial de ficha o administrarse despues desde `admin/practicantes.php` y `supervisor/practicantes.php`.
- El schema actualizado ya no usa `UNIQUE(id_jornada, id_tipo_marca)` en `marcas_asistencia`; si la base fue importada antes de Fase 2.3 y no se resetea, se debe aplicar `database/2026-06-03_patch_marcas_asistencia_indices_001.sql`.
- El usuario admin seed requiere definir hash local conocido para probar login.
