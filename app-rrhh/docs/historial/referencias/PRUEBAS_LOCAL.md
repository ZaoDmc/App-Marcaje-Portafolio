> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Plan de pruebas locales

## Objetivo

Permitir pruebas locales en XAMPP para ingresar al portal, usar un administrador de prueba, registrar practicantes y disponer de un supervisor local para validar flujos por rol.

Este documento debe actualizarse cada vez que cambie el procedimiento de pruebas, credenciales locales de ejemplo, seeds de desarrollo o flujos disponibles.

---

## Regla principal

No hardcodear usuarios, contrasenas ni datos de prueba dentro del codigo PHP.

Para pruebas locales se deben usar:

- Seeds existentes.
- SQL ejecutado solo en entorno local.
- Datos generados manualmente en la base local.
- Hashes creados con `password_hash()`.

Las contrasenas reales no deben subirse al repositorio ni documentarse como credenciales productivas.

---

## Estado actual para pruebas

Ya existe un usuario administrador inicial en:

```text
database/seed_admin.sql
```

Usuario de prueba:

```text
admin@localhost.test
```

Ese seed no guarda contrasena en texto plano. Para poder entrar en local se debe generar un hash local y actualizar la fila del administrador en la base `asistencia`.

El portal permite:

- Login ADMIN.
- Dashboard ADMIN.
- Pre-registro de practicantes.
- Gestion ADMIN/SUPERVISOR de horarios pactados.
- Activacion de cuenta de practicante.
- Login PRACTICANTE.
- Datos propios.
- Mi Perfil con avatar, accion `Subir foto`/`Cambiar foto`, documentos y accion `Subir documentos`.
- Marcacion de asistencia.
- Historial de asistencia.
- Gestion de usuarios SUPERVISOR desde `admin/usuarios.php`.

---

## Procedimiento recomendado

### 1. Preparar base local

Primero confirmar que estas parado en la carpeta del proyecto:

```powershell
cd C:\xampp\htdocs\asistencia
Get-Location
```

El resultado debe ser:

```text
C:\xampp\htdocs\asistencia
```

Si ejecutas los comandos desde otra carpeta, rutas relativas como `database\schema.sql` no van a encontrarse.

Resetear base local de pruebas:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS asistencia; CREATE DATABASE asistencia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Este reset es necesario si la base ya existia con una version anterior del proyecto. `schema.sql` usa `CREATE TABLE IF NOT EXISTS`, por lo que no cambia tablas antiguas ya creadas. Si omites este paso, `seed_regiones_comunas.sql` puede fallar con `Unknown column 'nombre_region'`.

Si partes desde cero y la base no existe, tambien puedes crearla sin borrar:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS asistencia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Importar schema:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\schema.sql
```

Si aparece `El sistema no puede encontrar la ruta especificada`, usar ruta absoluta:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\schema.sql"
```

Si la base local ya estaba importada y no quieres resetearla, aplicar el parche de indices de marcas para permitir correcciones/anulaciones con trazabilidad:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\2026-06-03_patch_marcas_asistencia_indices_001.sql"
```

Importar catalogo territorial:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_regiones_comunas.sql
```

Alternativa con ruta absoluta:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_regiones_comunas.sql"
```

Importar catalogos generales:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_catalogos.sql
```

Alternativa con ruta absoluta:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_catalogos.sql"
```

Importar admin:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia < database\seed_admin.sql
```

Alternativa con ruta absoluta:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_admin.sql"
```

Nota sobre collations:

Si antes viste un error como:

```text
ERROR 1267 (HY000): Illegal mix of collations
```

vuelve a ejecutar `seed_admin.sql` actualizado. El seed fuerza `utf8mb4_unicode_ci` para sus literales y comparaciones, que es la collation usada por el schema del proyecto.

Verificacion rapida de rutas en PowerShell:

```powershell
Test-Path C:\xampp\mysql\bin\mysql.exe
Test-Path C:\xampp\htdocs\asistencia\database\schema.sql
Test-Path C:\xampp\htdocs\asistencia\database\2026-06-03_patch_marcas_asistencia_indices_001.sql
Test-Path C:\xampp\htdocs\asistencia\database\seed_regiones_comunas.sql
Test-Path C:\xampp\htdocs\asistencia\database\seed_catalogos.sql
Test-Path C:\xampp\htdocs\asistencia\database\seed_admin.sql
```

Todos deben responder:

```text
True
```

Si estas usando CMD y ves un prompt como `C:\xampp\mysql\bin>`, no uses `Test-Path`. En CMD usa:

```bat
if exist C:\xampp\mysql\bin\mysql.exe (echo OK mysql.exe) else (echo FALTA mysql.exe)
if exist C:\xampp\htdocs\asistencia\database\schema.sql (echo OK schema.sql) else (echo FALTA schema.sql)
if exist C:\xampp\htdocs\asistencia\database\2026-06-03_patch_marcas_asistencia_indices_001.sql (echo OK 2026-06-03_patch_marcas_asistencia_indices_001.sql) else (echo FALTA 2026-06-03_patch_marcas_asistencia_indices_001.sql)
if exist C:\xampp\htdocs\asistencia\database\seed_regiones_comunas.sql (echo OK seed_regiones_comunas.sql) else (echo FALTA seed_regiones_comunas.sql)
if exist C:\xampp\htdocs\asistencia\database\seed_catalogos.sql (echo OK seed_catalogos.sql) else (echo FALTA seed_catalogos.sql)
if exist C:\xampp\htdocs\asistencia\database\seed_admin.sql (echo OK seed_admin.sql) else (echo FALTA seed_admin.sql)
```

Tambien puedes abrir PowerShell desde CMD con:

```bat
powershell
```

Despues de eso, el prompt deberia verse similar a:

```text
PS C:\xampp\mysql\bin>
```

En ese modo si puedes ejecutar `Test-Path`.

---

### 2. Definir contrasena local para ADMIN

Generar hash local:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('AdminLocal123', PASSWORD_DEFAULT);"
```

Actualizar el usuario administrador reemplazando `HASH_GENERADO_AQUI`.

Importante: no pegues hashes bcrypt con `$2y$...` dentro de comandos PowerShell con comillas dobles. PowerShell puede interpretar los segmentos con `$` como variables y guardar un hash corrupto. Usa un here-string de comillas simples:

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

Verificar que el hash no quedo corrupto:

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

Credencial local sugerida:

```text
Email: admin@localhost.test
Contrasena: AdminLocal123
```

Esta contrasena es solo para entorno local.

---

### 3. Ingresar como ADMIN

Abrir:

```text
http://localhost/asistencia/login.php
```

Ingresar con:

```text
admin@localhost.test
AdminLocal123
```

La pantalla debe mostrar:

- Recuadro de inicio de sesion.
- Texto lateral de bienvenida al portal de e-practicas.
- Checkbox `Mantener la sesion iniciada`.

Resultado esperado:

```text
http://localhost/asistencia/admin/dashboard.php
```

Validar dashboard avanzado inicial ADMIN:

- Usa sidebar azul oscuro con logo transparente `assets/brand/logo_rojo_blanco.png`, sin deformarlo.
- Usa iconografia Bootstrap Icons en sidebar, KPIs, alertas y franja inferior; no debe mostrar cuadros con letras como iconos principales.
- El sidebar muestra `Nuevo practicante` y abre `admin/crear-practicante.php`.
- Muestra metricas de practicantes activos, practicas activas, horas registradas, horas de los ultimos 30 dias, instituciones vinculadas, avance promedio/global, pendientes/ausencias, usuarios activos y auditoria 24h.
- Muestra graficos Chart.js para seguimiento semanal, distribucion por estado y practicantes por area.
- Muestra registros recientes y alertas operativas.
- Carga estilos desde `assets/css/admin-dashboard.css` y graficos desde `assets/js/admin-dashboard.js`.
- Permite filtrar por periodo, rango manual, busqueda simple, institucion, sede, carrera, area y estado de practica.
- Al aplicar filtros, la URL conserva parametros `GET` y KPIs/graficos/tabla/alertas se recalculan desde BBDD.
- El enlace `Limpiar` vuelve a `admin/dashboard.php` sin filtros.
- Muestra comparativos de KPIs contra el periodo anterior cuando corresponde.
- El grafico principal muestra referencia visual del periodo anterior.
- El sidebar permite colapsar en escritorio y abrir/cerrar con menu hamburguesa en pantallas menores.
- El menu lateral contiene `Cerrar sesion` y ejecuta `logout.php`.
- La barra superior tambien contiene `Cerrar sesion` al lado derecho y ejecuta `logout.php`.
- La vista de escritorio usa densidad compacta para que KPIs y graficos principales queden visibles al entrar al dashboard.
- El menu `Auditoria` abre `admin/auditoria.php`.
- El menu `Reportes` abre `admin/reportes.php`.
- El menu `Asistencia` abre `admin/asistencia.php`.
- En vista movil, el sidebar debe apilarse arriba y las tarjetas no deben superponerse.

Prueba de sesion persistente:

1. Volver a `logout.php` para cerrar cualquier sesion anterior.
2. Entrar de nuevo marcando `Mantener la sesion iniciada`.
3. Cerrar y abrir el navegador, o volver a llamar directamente la URL `http://localhost/asistencia/`.
4. Resultado esperado: mientras no se use `logout.php` y no venza la expiracion persistente, el portal debe reconocer la sesion y redirigir al dashboard correspondiente.

---

### 4. Crear o asignar supervisor desde ADMIN

Ingresar como ADMIN y abrir:

```text
http://localhost/asistencia/admin/usuarios.php
```

Caso A: crear un supervisor externo con un correo que no exista, por ejemplo:

```text
supervisor@localhost.test
```

Resultado esperado:

- Se crea usuario en `usuarios_sistema` con `id_practicante = NULL`.
- La cuenta queda `PENDIENTE_ACTIVACION`.
- Se asigna rol `SUPERVISOR` en `usuarios_roles`.
- Se genera token `ACTIVACION_CUENTA`.
- Si no hay SMTP, se muestra enlace manual de activacion para copiar.
- Se auditan `CREACION_USUARIO`, `ROL_ASIGNADO` y `TOKEN_GENERADO`.

Abrir el enlace de activacion, definir contrasena y luego iniciar sesion. El destino esperado es:

```text
http://localhost/asistencia/supervisor/dashboard.php
```

Validar dashboard avanzado inicial SUPERVISOR:

- Muestra metricas de practicantes activos, practicas activas, horas de los ultimos 30 dias, avance global, pendientes de revision y ausencias esperadas.
- Muestra acceso visible a `Crear practicante` y abre `supervisor/crear-practicante.php`.
- La tarjeta `Reportes` abre `supervisor/reportes.php`.
- Si el supervisor tambien es practicante, sus metricas operativas no deben incluir su propia practica.

Caso B: asignar rol SUPERVISOR a un practicante existente.

En el mismo formulario, ingresar el correo de un usuario PRACTICANTE ya existente.

Resultado esperado:

- No se crea otro usuario en `usuarios_sistema`.
- No se genera token nuevo ni contrasena nueva.
- Se asigna o reactiva el rol `SUPERVISOR` en `usuarios_roles`.
- Se audita `ROL_ASIGNADO`.
- Al iniciar sesion con sus credenciales actuales, si conserva roles `PRACTICANTE` y `SUPERVISOR`, debe pasar por `seleccionar-rol.php`.

Validacion SQL opcional reemplazando `correo@ejemplo.test`:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT u.email, u.id_practicante, GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', ') AS roles FROM usuarios_sistema u LEFT JOIN usuarios_roles ur ON ur.id_usuario = u.id_usuario AND ur.rol_activo = 1 LEFT JOIN roles_sistema r ON r.id_rol = ur.id_rol WHERE u.email='correo@ejemplo.test' GROUP BY u.id_usuario, u.email, u.id_practicante;"
```

Caso C: quitar rol SUPERVISOR a un practicante-supervisor.

En la tabla de usuarios, ubicar el practicante que tenga roles `PRACTICANTE, SUPERVISOR` y usar el boton `Quitar supervisor`.

Resultado esperado:

- No se borra el usuario.
- No se desactiva la cuenta.
- El rol `PRACTICANTE` permanece activo.
- El rol `SUPERVISOR` queda inactivo en `usuarios_roles`.
- Se audita `ROL_REMOVIDO`.
- En el siguiente login, si solo conserva `PRACTICANTE`, debe entrar al flujo de practicante sin selector de rol.

Validacion SQL opcional reemplazando `correo@ejemplo.test`:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT u.email, r.nombre AS rol, ur.rol_activo FROM usuarios_sistema u INNER JOIN usuarios_roles ur ON ur.id_usuario = u.id_usuario INNER JOIN roles_sistema r ON r.id_rol = ur.id_rol WHERE u.email='correo@ejemplo.test' ORDER BY r.nombre;"
```

---

### 5. Registrar practicante desde el portal

Ingresar como ADMIN o SUPERVISOR y abrir:

```text
http://localhost/asistencia/admin/crear-practicante.php
```

o:

```text
http://localhost/asistencia/supervisor/crear-practicante.php
```

Probar al menos dos casos:

- Institucion normalizada: `Duoc UC`, `INACAP` o `ENAC`, seleccionando sede desde catalogo.
- Institucion `Otra`, usando campos manuales `Ingrese su institucion` e `Ingrese su sede`.

Resultado esperado:

- Se crea registro en `practicantes`.
- Se crea registro en `practicas`.
- Si se informo horario pactado, se crea registro en `horarios_practicante` y `detalle_horario_practicante`.
- Se crea usuario en `usuarios_sistema`.
- Se asigna rol `PRACTICANTE`.
- Se genera token `ACTIVACION_CUENTA`.
- Si no hay SMTP, se muestra enlace manual de activacion.

---

### 6. Administrar horario pactado desde ADMIN/SUPERVISOR

Ingresar como ADMIN y abrir:

```text
http://localhost/asistencia/admin/practicantes.php
```

O ingresar como SUPERVISOR y abrir:

```text
http://localhost/asistencia/supervisor/practicantes.php
```

Resultado esperado:

- Se listan practicantes activos con practica activa.
- Se puede buscar por nombre, apellido, RUT o correo.
- Al abrir un practicante, se muestra el horario pactado vigente si existe.
- Al abrir un practicante, se muestra resumen de carrera, sede, inicio de practica y horas requeridas.
- Se puede reemplazar el horario semanal indicando fecha de inicio de vigencia y al menos un dia con jornada.
- El horario anterior queda inactivo y conserva historial; no se borra fisicamente.
- ADMIN puede dar de baja logicamente al practicante con confirmacion.
- La baja logica desactiva el practicante, practicas activas y cuenta de usuario vinculada, sin borrar historial.
- SUPERVISOR puede revisar detalle y horario, pero no tiene boton de baja logica.
- Si el usuario tiene rol SUPERVISOR y tambien esta vinculado al practicante seleccionado, no puede modificar su propio horario.
- Se muestra el area vigente de la practica si existe.
- ADMIN y SUPERVISOR pueden asignar o cambiar area vigente desde el catalogo `areas_practica`.
- Al cambiar area, se cierra el historial anterior y se crea una nueva fila en `historial_areas_practicante`.
- Si el usuario tiene rol SUPERVISOR y tambien esta vinculado al practicante seleccionado, no puede modificar su propia area.
- Se pueden registrar excepciones puntuales sobre el horario vigente.
- Se puede editar una excepcion reingresando la misma fecha y tipo.
- Se puede anular logicamente una excepcion activa indicando motivo.
- `CAMBIO_HORARIO` y `ASISTENCIA_EXTRAORDINARIA` permiten entrada, salida y colacion excepcional.
- `DIA_LIBRE`, `AUSENCIA_JUSTIFICADA`, `FERIADO` y `OTRO` no exigen horario.
- La marcacion del practicante debe usar la excepcion activa de la fecha antes que el horario semanal.

Validacion SQL opcional:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_horario, id_practica, fecha_inicio, fecha_fin, activo FROM horarios_practicante ORDER BY id_horario DESC LIMIT 5; SELECT id_horario, dia_semana, entrada_esperada, salida_esperada, horas_planificadas, activo FROM detalle_horario_practicante ORDER BY id_detalle_horario DESC LIMIT 10;"
```

Validacion SQL opcional de areas:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT hap.id_historial_area, hap.id_practica, ap.nombre AS area, hap.fecha_inicio, hap.fecha_fin, hap.asignado_por FROM historial_areas_practicante hap INNER JOIN areas_practica ap ON ap.id_area = hap.id_area ORDER BY hap.id_historial_area DESC LIMIT 10;"
```

Validacion SQL opcional de excepciones:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_excepcion, id_horario, fecha, tipo_excepcion, entrada_esperada, salida_esperada, horas_planificadas, activo, observacion FROM excepciones_horario_practicante ORDER BY id_excepcion DESC LIMIT 10;"
```

Despues de probar una baja logica como ADMIN, validar:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root asistencia -e "SELECT id_practicante, email, activo FROM practicantes ORDER BY id_practicante DESC LIMIT 5; SELECT id_practica, id_practicante, activo FROM practicas ORDER BY id_practica DESC LIMIT 5; SELECT id_usuario, id_practicante, email, estado_cuenta, activo FROM usuarios_sistema WHERE id_practicante IS NOT NULL ORDER BY id_usuario DESC LIMIT 5;"
```

---

### 7. Activar cuenta del practicante

Usar el enlace manual mostrado por el pre-registro.

Resultado esperado:

- El practicante define contrasena.
- La cuenta queda `ACTIVO`.
- El token queda usado.

Credencial local sugerida para el practicante creado:

```text
Email: el correo usado en el formulario
Contrasena: PracticanteLocal123
```

---

### 8. Probar rol PRACTICANTE

Ingresar con la cuenta activada y validar:

```text
http://localhost/asistencia/practicante/dashboard.php
http://localhost/asistencia/practicante/mis-datos.php
http://localhost/asistencia/practicante/marcar-asistencia.php
http://localhost/asistencia/practicante/mi-historial.php
```

Resultado esperado:

- Puede ver dashboard.
- Puede editar solo datos personales permitidos.
- Si la ficha esta incompleta, debe completar RUT, datos requeridos, practica y al menos un dia de horario pactado.
- El RUT puede ingresarse separado en numero y DV, o completo con guion en el campo RUT.
- El horario pactado permite repetir el mismo horario en varios dias, definir horarios distintos por dia y dejar dias sin jornada.
- Puede marcar asistencia segun secuencia desde una pantalla visual con sidebar institucional, sin pedir RUT, DV ni nombre.
- La pantalla de asistencia muestra pasos de jornada, botones disponibles, horario pactado, avance de horas, indicadores, avisos e historial reciente.
- El sidebar de `practicante/marcar-asistencia.php` debe usar el estilo del Dashboard Administrador y cargar `assets/css/practicante-asistencia.css` y `assets/js/practicante-asistencia.js`.
- Puede ver historial, horas realizadas, horas restantes y porcentaje de avance.

---

## Validaciones en base de datos

Revisar estas tablas despues de las pruebas:

```text
usuarios_sistema
usuarios_roles
sesiones_usuario
tokens_usuario
practicantes
practicas
horarios_practicante
detalle_horario_practicante
jornadas_asistencia
marcas_asistencia
auditoria_cambios
```

Eventos esperados en auditoria:

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

## Probar auditoria visible ADMIN

URL:

```text
http://localhost/asistencia/admin/auditoria.php
```

Validar como ADMIN:

- El dashboard muestra acceso a `Auditoria`.
- La pantalla permite filtrar por rango de fechas, evento, tabla y busqueda.
- Se muestran metricas de eventos listados, seguridad, funcionales y usuarios actores.
- La tabla muestra fecha, evento, actor, rol contexto, tabla, registro, IP y resumen de valores.
- Los valores sensibles como tokens, hashes o passwords se muestran redactados.
- La pantalla es solo lectura; no debe ofrecer editar, borrar ni exportar auditoria.

Consulta de verificacion:

```sql
SELECT COUNT(*) AS total_auditoria
FROM auditoria_cambios;

SELECT evento, tabla_afectada, id_registro_afectado, created_at
FROM auditoria_cambios
ORDER BY id_auditoria DESC
LIMIT 20;
```

---

## Probar correcciones/anulaciones de asistencia

URLs:

```text
http://localhost/asistencia/admin/asistencia.php
http://localhost/asistencia/supervisor/asistencia.php
```

Validar como ADMIN:

- Listar jornadas con filtros por fecha, estado y practicante.
- Generar ausencias esperadas para un rango ya pasado, confirmando la accion.
- Abrir detalle de jornada.
- Si la jornada tiene `flag_ausencia_esperada = 1`, resolverla como `JUSTIFICADA`, `NO_JUSTIFICADA` o `PENDIENTE_REVISION`.
- Registrar bloques de area por tramo dentro de la fecha de la jornada.
- Anular una marca vigente indicando motivo.
- Registrar una marca correctiva posterior, respetando secuencia.
- Marcar una jornada como `OBSERVADA` o `PENDIENTE_REVISION`.
- Si la jornada tiene `flag_horas_extra = 1`, aprobar horas extra indicando horas aprobadas y motivo.
- Anular jornada completa solo si corresponde, confirmando la accion.

Validar como SUPERVISOR:

- Listar y revisar jornadas.
- Generar ausencias esperadas para un rango ya pasado, confirmando la accion.
- Si la jornada tiene `flag_ausencia_esperada = 1`, resolverla como `JUSTIFICADA`, `NO_JUSTIFICADA` o `PENDIENTE_REVISION`.
- Registrar bloques de area por tramo dentro de la fecha de la jornada.
- Anular marcas individuales y registrar marcas correctivas cuando el flujo lo permita.
- Marcar jornadas como `OBSERVADA` o `PENDIENTE_REVISION`.
- Si la jornada tiene `flag_horas_extra = 1`, aprobar horas extra indicando horas aprobadas y motivo.
- Confirmar que no existe accion para anular jornada completa.
- Confirmar que no puede operar bloques sobre su propia jornada si es practicante-supervisor.

Consultas de verificacion:

```sql
SELECT id_jornada, estado_jornada, horas_calculadas, flag_horas_extra, horas_extra_aprobadas, aprobada_por, observacion
FROM jornadas_asistencia
ORDER BY id_jornada DESC
LIMIT 10;

SELECT id_jornada, id_practica, fecha, estado_jornada, flag_ausencia_esperada, observacion
FROM jornadas_asistencia
WHERE flag_ausencia_esperada = 1
ORDER BY id_jornada DESC
LIMIT 20;

-- Resultado esperado tras resolver ausencias:
-- JUSTIFICADA queda como OBSERVADA con horas_calculadas = 0.
-- NO_JUSTIFICADA queda como INCOMPLETA con horas_calculadas = 0.
-- PENDIENTE_REVISION queda como PENDIENTE_REVISION con horas_calculadas = 0.

SELECT id_marca, id_jornada, activo, anulada, motivo_anulacion, anulada_por, fecha_anulacion
FROM marcas_asistencia
ORDER BY id_marca DESC
LIMIT 10;

SELECT evento, tabla_afectada, id_registro_afectado, created_at
FROM auditoria_cambios
WHERE evento IN ('CORREGIR_ASISTENCIA', 'ANULAR_MARCA_ASISTENCIA', 'ANULAR_ASISTENCIA', 'CAMBIAR_AREA', 'APROBAR_HORAS_EXTRA')
ORDER BY id_auditoria DESC
LIMIT 20;
```

Validar jornadas con horas extra:

```sql
SELECT id_jornada, fecha, estado_jornada, horas_calculadas, flag_horas_extra, horas_extra_aprobadas, aprobada_por
FROM jornadas_asistencia
WHERE flag_horas_extra = 1
ORDER BY id_jornada DESC
LIMIT 20;
```

Validar bloques de area:

```sql
SELECT baj.id_bloque_area, baj.id_jornada, ap.nombre AS area, baj.hora_inicio, baj.hora_fin, baj.horas_asignadas, baj.asignado_por
FROM bloques_area_jornada baj
INNER JOIN areas_practica ap ON ap.id_area = baj.id_area
ORDER BY baj.id_bloque_area DESC
LIMIT 20;
```

---

## Probar reportes basicos de asistencia y avance

URLs:

```text
http://localhost/asistencia/admin/reportes.php
http://localhost/asistencia/supervisor/reportes.php
```

Validar como ADMIN:

- El dashboard muestra acceso a `Reportes`.
- Se muestran filtros por rango de fechas, estado de practica y practicante.
- Se muestran metricas de practicas filtradas, horas del periodo, avance global y alertas.
- La tabla muestra avance por practicante/practica, horas totales, horas del periodo, jornadas y alertas.

Validar como SUPERVISOR:

- El dashboard muestra acceso a `Reportes`.
- Se muestran las mismas metricas operativas.
- Si el supervisor tambien es practicante, no debe ver su propia practica en el reporte operativo.

Consulta de verificacion:

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

## Cargar base demo local con 50 practicantes

Archivo:

```text
database/seed_demo_practicantes_50.sql
```

Objetivo:

- Agregar 50 practicantes ficticios para demostracion local.
- Mantener los datos actuales de la BBDD local.
- Crear usuarios activos con rol `PRACTICANTE`.
- Distribuir practicantes entre distintas instituciones, sedes, carreras y areas.
- Crear practicas, horarios, jornadas y marcas de asistencia demo.

El seed es aditivo: no usa `DROP`, `TRUNCATE` ni `DELETE`.

Antes de aplicar el seed, generar respaldo:

```powershell
cmd /c "C:\xampp\mysql\bin\mysqldump.exe -u root asistencia > C:\xampp\htdocs\asistencia\database\backup_asistencia_before_demo_20260605.sql"
```

Aplicar seed:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\seed_demo_practicantes_50.sql"
```

Credencial local para cuentas demo:

```text
Email: nombreapellido@practicante.cl
Contrasena: DemoPracticante123
```

Validar carga:

```sql
SELECT COUNT(DISTINCT p.id_practicante) AS demo_practicantes
FROM practicantes p
INNER JOIN practicas pr ON pr.id_practicante = p.id_practicante
WHERE pr.observacion_estado = 'DEMO_LOCAL_2026_50';

SELECT COUNT(*) AS demo_practicas
FROM practicas
WHERE observacion_estado = 'DEMO_LOCAL_2026_50';

SELECT COUNT(*) AS demo_jornadas
FROM jornadas_asistencia
WHERE observacion LIKE 'DEMO_LOCAL_2026_50%';

SELECT COUNT(*) AS demo_marcas
FROM marcas_asistencia m
INNER JOIN jornadas_asistencia j ON j.id_jornada = m.id_jornada
WHERE j.observacion LIKE 'DEMO_LOCAL_2026_50%';
```

Resultado esperado:

```text
demo_practicantes: 50
demo_practicas: 50
demo_jornadas: 250
demo_marcas: 500
```

Restaurar la base local al respaldo previo:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root asistencia < C:\xampp\htdocs\asistencia\database\backup_asistencia_before_demo_20260605.sql"
```

Reset local limpio:

1. Respaldar primero si hay datos que conservar.
2. Recrear la base local.
3. Importar `database/schema.sql`.
4. Importar seeds oficiales necesarios, como catalogos y admin local.
5. Reaplicar parches de base vigentes si corresponde.

Reset online:

- Es tecnicamente posible solo si el hosting permite administrar la base.
- Debe hacerse con respaldo previo descargado y confirmacion explicita.
- No se debe ejecutar sobre produccion sin ventana de mantenimiento y validacion del archivo a importar.

---

## Pendientes antes de pruebas avanzadas

- Instalar PHPMailer o configurar SMTP real si se quiere probar envio de correos.
- Agregar Bootstrap local si se quiere probar sin internet.

---

## Ultima actualizacion

```text
2026-06-05
```
