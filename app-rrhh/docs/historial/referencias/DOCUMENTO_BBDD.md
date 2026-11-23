> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# DOCUMENTO_BBDD_PORTAL_ASISTENCIA.md

## 1. Nombre del documento

**Documento tÃ©cnico de Base de Datos - Portal de Asistencia y Control de Practicantes**

Este documento define el modelo de base de datos oficial para el proyecto web desarrollado en **PHP + MySQL/MariaDB + PDO**.

Debe ser usado como especificaciÃ³n para crear o actualizar:

```text
/database/schema.sql
/database/seed_catalogos.sql
/database/seed_admin.sql
```

Este documento no reemplaza `CONTEXTO_PROYECTO.md`; lo complementa especÃ­ficamente en la capa de Base de Datos.

---

## 2. Stack de base de datos

La base de datos debe implementarse en:

```text
MySQL / MariaDB
```

Compatible con:

```text
XAMPP local
Hostinger Web Hosting
phpMyAdmin
PDO desde PHP
```

Reglas tÃ©cnicas:

- Usar motor `InnoDB`.
- Usar charset `utf8mb4`.
- Usar collation recomendada `utf8mb4_unicode_ci`.
- Usar claves primarias enteras autoincrementales.
- Usar claves forÃ¡neas explÃ­citas.
- Usar Ã­ndices en campos usados para filtros, bÃºsquedas y relaciones.
- No usar borrado fÃ­sico como estrategia normal.
- Usar `activo`, estados o fechas de cierre segÃºn corresponda.

---

## 3. Principio de modelado

La base de datos debe estar diseÃ±ada como una estructura robusta desde el inicio.

Principios:

```text
NormalizaciÃ³n razonable.
Historial antes que sobrescritura.
AuditorÃ­a desde MVP.
Usuarios individuales desde MVP.
Roles reales desde MVP.
SesiÃ³n con rol activo trazable.
Horarios diferenciados por practicante.
Asistencia con jornadas y marcas separadas.
Ãreas asignables por historial y por bloques dentro de una jornada.
```

No se debe simplificar el modelo si eso contradice las reglas de negocio oficiales.

---

## 4. Convenciones de nombres

Usar nombres en espaÃ±ol, minÃºsculas y snake_case.

Ejemplos:

```text
usuarios_sistema
usuarios_roles
jornadas_asistencia
marcas_asistencia
auditoria_cambios
```

Convenciones:

```text
PK: id_nombre_tabla_singular o id_entidad
FK: id_tabla_referenciada_singular
Fechas de auditorÃ­a tÃ©cnica: created_at, updated_at
Borrado lÃ³gico: activo TINYINT(1)
Estados de negocio: VARCHAR o FK a catÃ¡logo segÃºn corresponda
```

---

## 5. Grupos de tablas

La base de datos se organiza en los siguientes grupos:

```text
1. CatÃ¡logos / tablas maestras
2. Personas / practicantes
3. PrÃ¡cticas
4. Horarios
5. Asistencia
6. Ãreas
7. Seguridad / usuarios / roles
8. Evaluaciones
9. AuditorÃ­a
```

---

## 6. CatÃ¡logos / tablas maestras

### 6.1 Tabla `regiones`

Finalidad:

Registrar regiones de Chile para normalizar la ubicacion del practicante.

Campos:

```text
id_region INT PK AUTO_INCREMENT
nombre_region VARCHAR(120) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Indices recomendados:

```text
UNIQUE(nombre_region)
INDEX(activo)
```

Seed inicial:

```text
Todas las regiones oficiales de Chile definidas en seed_regiones_comunas.sql.
```

Regla:

La region se selecciona desde catalogo normalizado y se usa para filtrar comunas.

---

### 6.2 Tabla `comunas`

Finalidad:

Registrar comunas de Chile asociadas a una region para evitar texto libre en direcciones.

Campos:

```text
id_comuna INT PK AUTO_INCREMENT
id_region INT NOT NULL FK -> regiones.id_region
nombre_comuna VARCHAR(120) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Indices recomendados:

```text
UNIQUE(id_region, nombre_comuna)
INDEX(id_region)
INDEX(activo)
```

Reglas:

- El usuario debe seleccionar primero una region.
- El campo comuna debe cargarse como `select` dependiente de la region seleccionada.
- `comuna` no debe guardarse como texto libre.
- Backend debe validar que `id_comuna` pertenezca a `id_region`.

---
### 6.3 Tabla `instituciones`

Finalidad:

Registrar instituciones de educaciÃ³n superior u origen acadÃ©mico.

Campos:

```text
id_institucion INT PK AUTO_INCREMENT
nombre VARCHAR(150) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(nombre)
```

Seed inicial:

```text
Duoc UC
INACAP
ENAC
Otra
```

---

### 6.4 Tabla `sedes`

Finalidad:

Registrar sedes asociadas a una instituciÃ³n.

Campos:

```text
id_sede INT PK AUTO_INCREMENT
id_institucion INT NOT NULL FK -> instituciones.id_institucion
nombre_sede VARCHAR(150) NOT NULL
codigo_sede VARCHAR(80) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_institucion)
INDEX(activo)
UNIQUE(id_institucion, codigo_sede)
```

RelaciÃ³n:

```text
instituciones 1 â”€â”€< sedes
```

Reglas:

- No usar `sedes.nombre` como campo principal.
- No usar `sedes.direccion` en esta etapa.
- Para `Duoc UC`, `INACAP` y `ENAC`, las sedes deben seleccionarse desde este catÃ¡logo normalizado.
- Para `Otra`, no se debe guardar texto libre en `sedes`; los valores manuales deben vivir en `practicantes.institucion_otra` y `practicantes.sede_otra`.
- Mantener una sede fallback `No especificada` por instituciÃ³n solo para casos excepcionales o migraciones incompletas.

---

### 6.4 Tabla `carreras`

Finalidad:

Registrar carreras o programas acadÃ©micos.

Campos:

```text
id_carrera INT PK AUTO_INCREMENT
nombre VARCHAR(150) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(nombre)
```

Seed inicial:

```text
Analista Programador
IngenierÃ­a en InformÃ¡tica
TÃ©cnico en InformÃ¡tica
TÃ©cnico en AdministraciÃ³n
TÃ©cnico en LogÃ­stica
Comercio Exterior
Marketing
Recursos Humanos
Contabilidad
DiseÃ±o GrÃ¡fico
Otra
```

---

### 6.5 Tabla `tipos_practica`

Finalidad:

Registrar tipos de prÃ¡ctica y horas base sugeridas.

Campos:

```text
id_tipo_practica INT PK AUTO_INCREMENT
nombre VARCHAR(100) NOT NULL
horas_base INT NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(nombre)
```

Seed inicial:

```text
Laboral: 180
Profesional: 360
Profesional extendida: 540
Otra: 0
```

Regla:

`tipos_practica.horas_base` es un valor sugerido. El valor definitivo de horas exigidas debe guardarse congelado en `practicas.horas_requeridas`.

---

### 6.6 Tabla `estados_practica`

Finalidad:

Registrar estados posibles de una prÃ¡ctica.

Campos:

```text
id_estado_practica INT PK AUTO_INCREMENT
nombre VARCHAR(100) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Seed inicial:

```text
PENDIENTE_INICIO
EN_PROGRESO
COMPLETADA
PAUSADA
INTERRUMPIDA
ANULADA
```

---

### 6.7 Tabla `areas_practica`

Finalidad:

Registrar Ã¡reas internas donde el practicante puede trabajar.

Campos:

```text
id_area INT PK AUTO_INCREMENT
nombre VARCHAR(150) NOT NULL
descripcion TEXT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(nombre)
```

Ejemplos de Ã¡reas:

```text
CreaciÃ³n de Productos
AutomatizaciÃ³n IA
Marketing
DiseÃ±o GrÃ¡fico
AdministraciÃ³n
LogÃ­stica
Desarrollo Web
Otra
```

---

### 6.8 Tabla `tipos_marca`

Finalidad:

Registrar tipos de marca de asistencia.

Campos:

```text
id_tipo_marca INT PK AUTO_INCREMENT
codigo VARCHAR(30) NOT NULL
nombre VARCHAR(100) NOT NULL
orden TINYINT NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(codigo)
UNIQUE(orden)
```

Seed inicial:

```text
ENTRADA              orden 1
ENTRADA_COLACION     orden 2
SALIDA_COLACION      orden 3
SALIDA               orden 4
```

---

## 7. Personas / practicantes

### 7.1 Tabla `practicantes`

Finalidad:

Registrar los datos personales, acadÃ©micos y de contacto del practicante.

Campos:

```text
id_practicante INT PK AUTO_INCREMENT
rut_numero VARCHAR(20) NULL
rut_dv VARCHAR(2) NULL
nombre_1 VARCHAR(100) NOT NULL
nombre_2 VARCHAR(100) NULL
apellido_paterno VARCHAR(100) NOT NULL
apellido_materno VARCHAR(100) NULL
email VARCHAR(150) NOT NULL
telefono VARCHAR(20) NULL
fecha_nacimiento DATE NULL
sexo CHAR(1) NULL
nacionalidad VARCHAR(50) NULL
direccion VARCHAR(200) NULL
numero_direccion VARCHAR(20) NULL
departamento VARCHAR(50) NULL
id_comuna INT NULL FK -> comunas.id_comuna
pais VARCHAR(100) NULL
id_region INT NULL FK -> regiones.id_region
id_institucion INT NULL FK -> instituciones.id_institucion
id_sede INT NULL FK -> sedes.id_sede
institucion_otra VARCHAR(150) NULL
sede_otra VARCHAR(150) NULL
id_carrera INT NULL FK -> carreras.id_carrera
semestre VARCHAR(20) NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(rut_numero, rut_dv)
UNIQUE(email)
INDEX(id_region)
INDEX(id_comuna)
INDEX(id_institucion)
INDEX(id_sede)
INDEX(id_carrera)
INDEX(apellido_paterno, nombre_1)
INDEX(activo)
```

Relaciones:

```text
regiones 1 --< practicantes
comunas 1 --< practicantes
instituciones 1 â”€â”€< practicantes
sedes 1 â”€â”€< practicantes
carreras 1 â”€â”€< practicantes
```

Reglas:

- Los datos propios de una prÃ¡ctica no deben vivir principalmente en esta tabla.
- Una persona practicante puede tener historial de mÃ¡s de una prÃ¡ctica.
- No borrar fisicamente practicantes; usar `activo = 0` o estado correspondiente en `practicas`.
- La comuna se guarda mediante `id_comuna`; no se permite texto libre para comuna.
- Backend debe validar que `id_comuna` pertenezca a `id_region`.
- Si `id_institucion` corresponde a Duoc UC, INACAP o ENAC, `id_sede` es obligatorio, `institucion_otra` debe quedar `NULL` y `sede_otra` debe quedar `NULL`.
- Si `id_institucion` corresponde a Otra, `institucion_otra` y `sede_otra` son obligatorios, e `id_sede` debe quedar `NULL` o no usarse operativamente.
- El texto libre de instituciones o sedes no debe contaminar el catÃ¡logo oficial `sedes`.

---

### 7.2 Tabla `documentos_practicante`

Finalidad:

Registrar metadata de foto de perfil y documentos opcionales asociados al practicante y, cuando exista, a su prÃ¡ctica activa.

Campos:

```text
id_documento INT PK AUTO_INCREMENT
id_practicante INT NOT NULL FK -> practicantes.id_practicante
id_practica INT NULL FK -> practicas.id_practica
tipo_documento VARCHAR(40) NOT NULL
nombre_original VARCHAR(255) NOT NULL
nombre_archivo VARCHAR(255) NOT NULL
ruta_relativa VARCHAR(255) NOT NULL
mime_type VARCHAR(120) NOT NULL
tamano_bytes INT NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practicante)
INDEX(id_practica)
INDEX(tipo_documento, activo)
INDEX(activo)
```

Tipos iniciales:

```text
FOTO_PERFIL
DOCUMENTO_PRACTICA
```

Relaciones:

```text
practicantes 1 --< documentos_practicante
practicas 0..1 --< documentos_practicante
```

Reglas:

- La foto y los documentos son opcionales.
- La falta de archivos no bloquea el cierre de ficha del practicante.
- Los archivos pueden cargarse durante `Completar registro` o posteriormente desde `Mi Perfil`.
- La foto permite JPG, JPEG o PNG, mÃ¡ximo 5 MB.
- Los documentos permiten PDF, Word o Excel, mÃ¡ximo 5 documentos activos y 5 MB por archivo.
- No usar el nombre original como nombre fÃ­sico de almacenamiento; generar nombre seguro.
- No borrar fÃ­sicamente metadata; usar `activo = 0`.
- La subida debe auditarse como `SUBIR_DOCUMENTO_PRACTICANTE`.
- La visualizaciÃ³n y descarga deben hacerse con controladores protegidos que validen `id_practicante`; no se deben exponer rutas internas del servidor.

---

## 8. PrÃ¡cticas

### 8.1 Tabla `practicas`

Finalidad:

Registrar cada prÃ¡ctica asociada a un practicante.

Campos:

```text
id_practica INT PK AUTO_INCREMENT
id_practicante INT NOT NULL FK -> practicantes.id_practicante
id_tipo_practica INT NOT NULL FK -> tipos_practica.id_tipo_practica
id_estado_practica INT NOT NULL FK -> estados_practica.id_estado_practica
horas_requeridas INT NOT NULL
fecha_inicio_practica DATE NOT NULL
fecha_termino_practica DATE NULL
motivo_interrupcion TEXT NULL
observacion_estado TEXT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practicante)
INDEX(id_tipo_practica)
INDEX(id_estado_practica)
INDEX(fecha_inicio_practica)
INDEX(activo)
```

Relaciones:

```text
practicantes 1 â”€â”€< practicas
tipos_practica 1 â”€â”€< practicas
estados_practica 1 â”€â”€< practicas
```

Reglas:

- Una prÃ¡ctica pertenece a un solo practicante.
- Un practicante puede tener muchas prÃ¡cticas.
- `horas_requeridas` congela el valor definitivo exigido para esa prÃ¡ctica.
- No recalcular histÃ³ricamente `horas_requeridas` si cambia el catÃ¡logo `tipos_practica`.

---

## 9. Horarios

### 9.1 Tabla `horarios_practicante`

Finalidad:

Registrar una planificaciÃ³n horaria vigente o histÃ³rica para una prÃ¡ctica.

Campos:

```text
id_horario INT PK AUTO_INCREMENT
id_practica INT NOT NULL FK -> practicas.id_practica
nombre_horario VARCHAR(100) NOT NULL
fecha_inicio DATE NOT NULL
fecha_fin DATE NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practica)
INDEX(fecha_inicio)
INDEX(fecha_fin)
INDEX(activo)
```

RelaciÃ³n:

```text
practicas 1 â”€â”€< horarios_practicante
```

Reglas:

- El horario no es un dato personal, pero puede capturarse en el flujo operativo de pre-registro o cierre inicial de ficha.
- ADMIN o SUPERVISOR puede asignar el horario durante el pre-registro.
- Si el horario no fue asignado previamente, el PRACTICANTE debe registrarlo al completar su ficha inicial.
- El practicante puede verlo, pero no modificarlo libremente despues de cerrar ficha.
- Los cambios de horario deben conservar historial.

---

### 9.2 Tabla `detalle_horario_practicante`

Finalidad:

Registrar dÃ­as y horas esperadas dentro de un horario.

Campos:

```text
id_detalle_horario INT PK AUTO_INCREMENT
id_horario INT NOT NULL FK -> horarios_practicante.id_horario
dia_semana TINYINT NOT NULL
entrada_esperada TIME NOT NULL
salida_esperada TIME NOT NULL
colacion_inicio TIME NULL
colacion_fin TIME NULL
horas_planificadas DECIMAL(5,2) NOT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_horario)
INDEX(dia_semana)
UNIQUE(id_horario, dia_semana)
```

RelaciÃ³n:

```text
horarios_practicante 1 â”€â”€< detalle_horario_practicante
```

Reglas:

- `dia_semana` puede usar 1 a 7, donde 1 = lunes y 7 = domingo.
- Debe soportar horarios diferenciados por dÃ­a.
- No todos los practicantes tendrÃ¡n la misma jornada ni asistirÃ¡n todos los dÃ­as.

---

### 9.3 Tabla `excepciones_horario_practicante`

Finalidad:

Registrar cambios puntuales o excepciones a la planificaciÃ³n regular.

Campos:

```text
id_excepcion INT PK AUTO_INCREMENT
id_horario INT NOT NULL FK -> horarios_practicante.id_horario
fecha DATE NOT NULL
tipo_excepcion VARCHAR(50) NOT NULL
entrada_esperada TIME NULL
salida_esperada TIME NULL
colacion_inicio TIME NULL
colacion_fin TIME NULL
horas_planificadas DECIMAL(5,2) NULL
observacion TEXT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_horario)
INDEX(fecha)
UNIQUE(id_horario, fecha, tipo_excepcion)
```

Tipos de excepciÃ³n sugeridos:

```text
CAMBIO_HORARIO
DIA_LIBRE
ASISTENCIA_EXTRAORDINARIA
AUSENCIA_JUSTIFICADA
FERIADO
OTRO
```

RelaciÃ³n:

```text
horarios_practicante 1 â”€â”€< excepciones_horario_practicante
```

---

## 10. Asistencia

### 10.1 Tabla `jornadas_asistencia`

Finalidad:

Registrar el resumen de una jornada diaria de asistencia para una prÃ¡ctica.

Campos:

```text
id_jornada INT PK AUTO_INCREMENT
id_practica INT NOT NULL FK -> practicas.id_practica
fecha DATE NOT NULL
estado_jornada VARCHAR(30) NOT NULL
hora_entrada DATETIME NULL
hora_salida DATETIME NULL
horas_calculadas DECIMAL(5,2) NOT NULL DEFAULT 0.00
observacion TEXT NULL
creada_por INT NULL FK -> usuarios_sistema.id_usuario
flag_atraso TINYINT(1) NOT NULL DEFAULT 0
flag_salida_anticipada TINYINT(1) NOT NULL DEFAULT 0
flag_ausencia_esperada TINYINT(1) NOT NULL DEFAULT 0
flag_asistencia_extraordinaria TINYINT(1) NOT NULL DEFAULT 0
flag_horas_extra TINYINT(1) NOT NULL DEFAULT 0
horas_extra_aprobadas DECIMAL(5,2) NULL
aprobada_por INT NULL FK -> usuarios_sistema.id_usuario
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practica)
INDEX(fecha)
INDEX(estado_jornada)
INDEX(creada_por)
INDEX(aprobada_por)
UNIQUE(id_practica, fecha)
```

RelaciÃ³n:

```text
practicas 1 â”€â”€< jornadas_asistencia
usuarios_sistema 1 â”€â”€< jornadas_asistencia como creada_por
usuarios_sistema 1 â”€â”€< jornadas_asistencia como aprobada_por
```

Estados permitidos:

```text
VALIDA
OBSERVADA
INCOMPLETA
ANULADA
CORREGIDA
PENDIENTE_REVISION
```

Reglas:

- Los estados globales deben vivir principalmente en `jornadas_asistencia`.
- Si falta salida final, la jornada queda `INCOMPLETA` y no suma horas.
- Si hay entrada y salida vÃ¡lidas, puede sumar horas segÃºn estado.
- Si jornada dura 6 horas o mÃ¡s y no tiene colaciÃ³n completa, descontar 1 hora y dejar `OBSERVADA`.
- `INCOMPLETA`, `ANULADA` y `PENDIENTE_REVISION` no suman horas.
- `VALIDA` suma horas.
- `CORREGIDA` suma si tiene entrada y salida vÃ¡lidas.
- `OBSERVADA` puede sumar si tiene entrada y salida vÃ¡lidas, segÃºn regla de cÃ¡lculo.
- Atraso, salida anticipada, ausencia esperada, asistencia extraordinaria y horas extra se manejan como flags, no como estados principales.

---

### 10.2 Tabla `marcas_asistencia`

Finalidad:

Registrar cada marca individual realizada dentro de una jornada.

Campos:

```text
id_marca INT PK AUTO_INCREMENT
id_jornada INT NOT NULL FK -> jornadas_asistencia.id_jornada
id_tipo_marca INT NOT NULL FK -> tipos_marca.id_tipo_marca
fecha_hora DATETIME NOT NULL
observacion VARCHAR(255) NULL
creada_por INT NULL FK -> usuarios_sistema.id_usuario
activo TINYINT(1) NOT NULL DEFAULT 1
anulada TINYINT(1) NOT NULL DEFAULT 0
motivo_anulacion TEXT NULL
anulada_por INT NULL FK -> usuarios_sistema.id_usuario
fecha_anulacion DATETIME NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_jornada)
INDEX(id_tipo_marca)
INDEX(fecha_hora)
INDEX(creada_por)
INDEX(activo)
INDEX(anulada)
INDEX(anulada_por)
INDEX(fecha_anulacion)
UNIQUE(id_jornada, id_tipo_marca)
```

Relaciones:

```text
jornadas_asistencia 1 â”€â”€< marcas_asistencia
tipos_marca 1 â”€â”€< marcas_asistencia
usuarios_sistema 1 â”€â”€< marcas_asistencia como creada_por
usuarios_sistema 1 â”€â”€< marcas_asistencia como anulada_por
```

Secuencia ideal:

```text
ENTRADA -> ENTRADA_COLACION -> SALIDA_COLACION -> SALIDA
```

Opciones permitidas:

```text
Sin marca previa: ENTRADA.
DespuÃ©s de ENTRADA: ENTRADA_COLACION o SALIDA.
DespuÃ©s de ENTRADA_COLACION: SALIDA_COLACION o SALIDA.
DespuÃ©s de SALIDA_COLACION: SALIDA.
DespuÃ©s de SALIDA: ninguna.
```

AnulaciÃ³n lÃ³gica granular:

```text
activo = 1 y anulada = 0 representan una marca vigente.
activo = 0 y anulada = 1 representan una marca anulada lÃ³gicamente.
motivo_anulacion registra la justificaciÃ³n operativa.
anulada_por registra el usuario que anulÃ³ la marca.
fecha_anulacion registra cuÃ¡ndo se anulÃ³ la marca.
```

Reglas:

- No borrar fÃ­sicamente marcas de asistencia.
- Toda marca anulada debe conservar su fila original para trazabilidad.
- Toda anulaciÃ³n granular de marca debe registrar `motivo_anulacion`, `anulada_por` y `fecha_anulacion`.
- La anulaciÃ³n lÃ³gica de una marca debe auditarse en `auditoria_cambios`.
- La anulaciÃ³n o correcciÃ³n de marcas puede afectar el estado global de `jornadas_asistencia`.

---

## 11. Ãreas

### 11.1 Tabla `historial_areas_practicante`

Finalidad:

Registrar historial de asignaciÃ³n de Ã¡reas por prÃ¡ctica.

Campos:

```text
id_historial_area INT PK AUTO_INCREMENT
id_practica INT NOT NULL FK -> practicas.id_practica
id_area INT NOT NULL FK -> areas_practica.id_area
fecha_inicio DATE NOT NULL
fecha_fin DATE NULL
observacion TEXT NULL
asignado_por INT NULL FK -> usuarios_sistema.id_usuario
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practica)
INDEX(id_area)
INDEX(fecha_inicio)
INDEX(fecha_fin)
INDEX(asignado_por)
```

Relaciones:

```text
practicas 1 â”€â”€< historial_areas_practicante
areas_practica 1 â”€â”€< historial_areas_practicante
usuarios_sistema 1 â”€â”€< historial_areas_practicante como asignado_por
```

Reglas:

- ADMIN y SUPERVISOR pueden asignar o cambiar Ã¡rea.
- Cambio de Ã¡rea no reinicia horas acumuladas.
- Historial de Ã¡reas no se sobrescribe.
- Si cambia Ã¡rea, se cierra el periodo anterior con `fecha_fin` y se abre un nuevo periodo.

---

### 11.2 Tabla `bloques_area_jornada`

Finalidad:

Registrar tramos de trabajo en distintas Ã¡reas dentro de una misma jornada.

Campos:

```text
id_bloque_area INT PK AUTO_INCREMENT
id_jornada INT NOT NULL FK -> jornadas_asistencia.id_jornada
id_area INT NOT NULL FK -> areas_practica.id_area
hora_inicio DATETIME NOT NULL
hora_fin DATETIME NULL
horas_asignadas DECIMAL(5,2) NOT NULL DEFAULT 0.00
asignado_por INT NULL FK -> usuarios_sistema.id_usuario
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_jornada)
INDEX(id_area)
INDEX(asignado_por)
INDEX(hora_inicio)
INDEX(hora_fin)
```

Relaciones:

```text
jornadas_asistencia 1 â”€â”€< bloques_area_jornada
areas_practica 1 â”€â”€< bloques_area_jornada
usuarios_sistema 1 â”€â”€< bloques_area_jornada como asignado_por
```

Regla:

Una misma jornada puede dividirse en mÃ¡s de un Ã¡rea.

Ejemplo:

```text
09:00 - 13:00 CreaciÃ³n de Productos
14:00 - 18:00 AutomatizaciÃ³n IA
```

---

## 12. Seguridad / usuarios / roles

### 12.1 Tabla `roles_sistema`

Finalidad:

Registrar roles del sistema.

Campos:

```text
id_rol INT PK AUTO_INCREMENT
nombre VARCHAR(50) NOT NULL
descripcion TEXT NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(nombre)
```

Seed obligatorio:

```text
ADMIN
SUPERVISOR
PRACTICANTE
```

---

### 12.2 Tabla `usuarios_sistema`

Finalidad:

Registrar cuentas individuales de acceso al sistema.

Campos:

```text
id_usuario INT PK AUTO_INCREMENT
id_practicante INT NULL FK -> practicantes.id_practicante
email VARCHAR(150) NOT NULL
password_hash VARCHAR(255) NOT NULL
estado_cuenta VARCHAR(30) NOT NULL
debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0
email_verificado TINYINT(1) NOT NULL DEFAULT 0
intentos_fallidos INT NOT NULL DEFAULT 0
ultimo_intento_login DATETIME NULL
bloqueado_hasta DATETIME NULL
ultimo_login DATETIME NULL
activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
UNIQUE(email)
INDEX(id_practicante)
INDEX(estado_cuenta)
INDEX(activo)
INDEX(bloqueado_hasta)
```

RelaciÃ³n:

```text
practicantes 0..1 â”€â”€ 0..1 usuarios_sistema
```

Reglas:

- `id_practicante` es `NULL` cuando la cuenta no corresponde a una ficha de practicante.
- ADMIN externo debe tener `id_practicante NULL`.
- SUPERVISOR externo puede tener `id_practicante NULL`.
- PRACTICANTE debe tener `id_practicante` con valor.
- PRACTICANTE + SUPERVISOR debe tener `id_practicante` con valor.
- PRACTICANTE + ADMIN no estÃ¡ permitido.
- Si un usuario tiene rol ADMIN, entonces `id_practicante` debe ser `NULL`.

Estados de cuenta:

```text
PENDIENTE_ACTIVACION
ACTIVO
BLOQUEADO
INACTIVO
```

Reglas de bloqueo:

- `bloqueado_hasta` se usa para bloqueo temporal por intentos fallidos.
- `estado_cuenta = BLOQUEADO` se reserva para bloqueo administrativo.
- `activo = 0` representa desactivaciÃ³n lÃ³gica.

---

### 12.3 Tabla `usuarios_roles`

Finalidad:

Relacionar usuarios con uno o varios roles.

Campos:

```text
id_usuario_rol INT PK AUTO_INCREMENT
id_usuario INT NOT NULL FK -> usuarios_sistema.id_usuario
id_rol INT NOT NULL FK -> roles_sistema.id_rol
rol_activo TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_usuario)
INDEX(id_rol)
UNIQUE(id_usuario, id_rol)
```

Relaciones:

```text
usuarios_sistema 1 â”€â”€< usuarios_roles
roles_sistema 1 â”€â”€< usuarios_roles
```

Casos permitidos:

```text
ADMIN externo: id_practicante NULL + ADMIN
SUPERVISOR externo: id_practicante NULL + SUPERVISOR
ADMIN + SUPERVISOR externo: id_practicante NULL + ADMIN + SUPERVISOR
PRACTICANTE: id_practicante con valor + PRACTICANTE
PRACTICANTE + SUPERVISOR: id_practicante con valor + PRACTICANTE + SUPERVISOR
```

Caso no permitido:

```text
PRACTICANTE + ADMIN
```

Regla crÃ­tica:

Antes de asignar el rol ADMIN a un usuario, el backend debe validar que `usuarios_sistema.id_practicante IS NULL`.

---

### 12.4 Tabla `sesiones_usuario`

Finalidad:

Registrar sesiones reales y rol activo elegido por el usuario despuÃ©s del login.

Campos:

```text
id_sesion INT PK AUTO_INCREMENT
id_usuario INT NOT NULL FK -> usuarios_sistema.id_usuario
id_rol_activo INT NOT NULL FK -> roles_sistema.id_rol
session_id_hash VARCHAR(255) NOT NULL
ip VARCHAR(45) NOT NULL
user_agent VARCHAR(255) NULL
fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
fecha_ultimo_acceso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
fecha_cierre DATETIME NULL
cerrada_por_logout TINYINT(1) NOT NULL DEFAULT 0
activa TINYINT(1) NOT NULL DEFAULT 1
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_usuario)
INDEX(id_rol_activo)
INDEX(activa)
INDEX(fecha_inicio)
INDEX(fecha_cierre)
INDEX(session_id_hash)
```

Relaciones:

```text
usuarios_sistema 1 â”€â”€< sesiones_usuario
roles_sistema 1 â”€â”€< sesiones_usuario como id_rol_activo
```

Reglas:

- `id_rol_activo` debe mantenerse `NOT NULL`.
- No insertar un registro operativo en `sesiones_usuario` hasta que exista un rol activo definido.
- Si el usuario tiene un solo rol activo, se puede crear la sesiÃ³n con ese rol activo directamente.
- Si el usuario tiene mÃºltiples roles activos, debe pasar por `seleccionar-rol.php` despuÃ©s del login usando una sesiÃ³n PHP temporal.
- Durante el paso intermedio de multirrol, la sesiÃ³n PHP puede guardar datos temporales como `usuario_pendiente_rol` y `roles_disponibles`, pero todavÃ­a no debe existir fila en `sesiones_usuario`.
- En usuarios multirrol, insertar el registro en `sesiones_usuario` reciÃ©n despuÃ©s de seleccionar el rol activo.
- El rol activo condiciona dashboard, menÃº, permisos, acciones disponibles y auditorÃ­a.
- Al hacer logout, se debe actualizar `fecha_cierre`, `cerrada_por_logout = 1` y `activa = 0`.
- Guardar hash del `session_id`, no necesariamente el valor plano.

---

### 12.5 Tabla `tokens_usuario`

Finalidad:

Registrar tokens temporales para activaciÃ³n y recuperaciÃ³n de contraseÃ±a.

Campos:

```text
id_token INT PK AUTO_INCREMENT
id_usuario INT NOT NULL FK -> usuarios_sistema.id_usuario
tipo_token VARCHAR(50) NOT NULL
token_hash VARCHAR(255) NOT NULL
fecha_expiracion DATETIME NOT NULL
usado TINYINT(1) NOT NULL DEFAULT 0
fecha_uso DATETIME NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_usuario)
INDEX(tipo_token)
INDEX(fecha_expiracion)
INDEX(usado)
INDEX(token_hash)
```

Tipos mÃ­nimos:

```text
ACTIVACION_CUENTA
RECUPERACION_PASSWORD
```

RelaciÃ³n:

```text
usuarios_sistema 1 â”€â”€< tokens_usuario
```

Reglas:

- Guardar hash del token, nunca el token plano.
- El token real solo se muestra o envÃ­a una vez.
- Los tokens deben expirar.
- Los tokens usados deben invalidarse.
- La generaciÃ³n, regeneraciÃ³n y uso de tokens debe auditarse.

---

## 13. Evaluaciones

### 13.1 Tabla `evaluaciones_practicante`

Finalidad:

Registrar evaluaciones, observaciones o pre-evaluaciones asociadas a una prÃ¡ctica.

Campos:

```text
id_evaluacion INT PK AUTO_INCREMENT
id_practicante INT NOT NULL FK -> practicantes.id_practicante
id_practica INT NOT NULL FK -> practicas.id_practica
id_evaluador INT NOT NULL FK -> usuarios_sistema.id_usuario
nota_desempeno DECIMAL(4,2) NULL
potencial VARCHAR(50) NULL
nivel_desarrollo_habilidades VARCHAR(50) NULL
observacion TEXT NULL
fecha_evaluacion DATE NOT NULL
estado_evaluacion VARCHAR(30) NOT NULL
aprobado_por INT NULL FK -> usuarios_sistema.id_usuario
fecha_aprobacion DATETIME NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_practicante)
INDEX(id_practica)
INDEX(id_evaluador)
INDEX(aprobado_por)
INDEX(fecha_evaluacion)
INDEX(estado_evaluacion)
```

Relaciones:

```text
practicantes 1 â”€â”€< evaluaciones_practicante
practicas 1 â”€â”€< evaluaciones_practicante
usuarios_sistema 1 â”€â”€< evaluaciones_practicante como id_evaluador
usuarios_sistema 1 â”€â”€< evaluaciones_practicante como aprobado_por
```

Estados sugeridos:

```text
BORRADOR
PENDIENTE_APROBACION
APROBADA
RECHAZADA
ANULADA
```

Reglas:

- Evaluaciones finales y cierres de prÃ¡ctica son responsabilidad exclusiva de ADMIN.
- Supervisor-practicante puede registrar observaciones o pre-evaluaciones de otros practicantes, pero no de sÃ­ mismo.
- Supervisor-practicante no puede evaluarse a sÃ­ mismo.
- No se permite autosupervisiÃ³n ni autoevaluaciÃ³n.

---

## 14. AuditorÃ­a

### 14.1 Tabla `auditoria_cambios`

Finalidad:

Registrar eventos de seguridad y acciones funcionales relevantes.

Campos:

```text
id_auditoria INT PK AUTO_INCREMENT
id_usuario_actor INT NULL FK -> usuarios_sistema.id_usuario
id_rol_contexto INT NULL FK -> roles_sistema.id_rol
id_sesion INT NULL FK -> sesiones_usuario.id_sesion
evento VARCHAR(100) NOT NULL
tabla_afectada VARCHAR(100) NULL
id_registro_afectado INT NULL
valores_anteriores TEXT NULL
valores_nuevos TEXT NULL
ip VARCHAR(45) NOT NULL
user_agent VARCHAR(255) NULL
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

Ãndices recomendados:

```text
INDEX(id_usuario_actor)
INDEX(id_rol_contexto)
INDEX(id_sesion)
INDEX(evento)
INDEX(tabla_afectada)
INDEX(id_registro_afectado)
INDEX(created_at)
```

Relaciones:

```text
usuarios_sistema 1 â”€â”€< auditoria_cambios como id_usuario_actor
roles_sistema 1 â”€â”€< auditoria_cambios como id_rol_contexto
sesiones_usuario 1 â”€â”€< auditoria_cambios como id_sesion
```

Reglas:

- Auditar desde MVP.
- Guardar quiÃ©n ejecutÃ³ la acciÃ³n.
- Guardar bajo quÃ© rol de contexto se ejecutÃ³ la acciÃ³n.
- Guardar desde quÃ© sesiÃ³n se ejecutÃ³ la acciÃ³n.
- Guardar valores anteriores y nuevos cuando aplique.
- No guardar contraseÃ±as, tokens planos ni datos sensibles innecesarios en auditorÃ­a.

Eventos de seguridad mÃ­nimos:

```text
LOGIN_EXITOSO
LOGIN_FALLIDO
LOGOUT
RECUPERACION_SOLICITADA
TOKEN_GENERADO
TOKEN_REGENERADO
PASSWORD_CAMBIADA
CUENTA_ACTIVADA
USUARIO_BLOQUEADO
ROL_ASIGNADO
ROL_REMOVIDO
CREACION_USUARIO
EDICION_USUARIO
CAMBIO_ESTADO_CUENTA
INTENTO_ASIGNACION_ADMIN_RECHAZADO
SELECCION_ROL_CONTEXTO
```

Eventos funcionales mÃ­nimos:

```text
EDICION_DATOS_PROPIOS_PRACTICANTE
CREAR_PRACTICANTE
EDITAR_PRACTICANTE
CORREGIR_ASISTENCIA
ANULAR_ASISTENCIA
ANULAR_MARCA_ASISTENCIA
CAMBIAR_AREA
APROBAR_HORAS_EXTRA
SUBIR_DOCUMENTO_PRACTICANTE
CERRAR_PRACTICA
EXPORTAR_XLSX
```

---

## 15. Reglas crÃ­ticas de negocio que deben reflejarse en BBDD y backend

### 15.1 Login y rol activo

```text
El selector de rol ocurre despuÃ©s del login, nunca antes.
```

Flujo:

```text
1. Usuario ingresa email y contraseÃ±a.
2. Backend valida credenciales.
3. Backend obtiene roles activos.
4. Si tiene un solo rol, se define rol activo automÃ¡ticamente.
5. Si tiene un solo rol, se crea la sesiÃ³n operativa en sesiones_usuario con ese rol activo.
6. Si tiene mÃºltiples roles, se redirige a seleccionar-rol.php usando una sesiÃ³n PHP temporal.
7. La sesiÃ³n PHP temporal puede guardar usuario_pendiente_rol y roles_disponibles.
8. DespuÃ©s de seleccionar rol, se crea el registro en sesiones_usuario con id_rol_activo.
9. El rol activo queda disponible para permisos, menÃº, dashboard y auditorÃ­a.
```

---

### 15.2 Multirrol

Se permite:

```text
ADMIN externo
SUPERVISOR externo
ADMIN + SUPERVISOR externo
PRACTICANTE
PRACTICANTE + SUPERVISOR
```

No se permite:

```text
PRACTICANTE + ADMIN
```

ValidaciÃ³n obligatoria:

```text
Si usuarios_sistema.id_practicante IS NOT NULL, no se debe permitir asignar rol ADMIN.
```

---

### 15.3 Conflicto de interÃ©s

Un usuario practicante-supervisor mantiene obligaciones de practicante, pero no puede autosupervisarse.

No puede ejecutar sobre su propia prÃ¡ctica:

```text
corregir asistencia
aprobar horas extra
evaluar desempeÃ±o
cambiar Ã¡rea
resolver observaciones
cerrar prÃ¡ctica
anular registros propios
```

Esta regla debe validarse en backend. La BBDD ayuda mediante:

```text
usuarios_sistema.id_practicante
evaluaciones_practicante.id_practicante
evaluaciones_practicante.id_evaluador
jornadas_asistencia.id_practica
practicas.id_practicante
auditoria_cambios.id_usuario_actor
auditoria_cambios.id_rol_contexto
auditoria_cambios.id_sesion
```

---

### 15.4 No borrado fÃ­sico

No borrar fÃ­sicamente registros normales del negocio.

Usar:

```text
activo = 0
estado = ANULADA
estado = INACTIVO
fecha_cierre
fecha_fin
```

Aplica a:

```text
practicantes
usuarios_sistema
practicas
jornadas_asistencia
marcas_asistencia
horarios_practicante
areas_practica
evaluaciones_practicante
```

---

## 16. Reglas SQL recomendadas

### 16.1 Restricciones de integridad

Usar claves forÃ¡neas con comportamiento conservador:

```text
ON UPDATE CASCADE
ON DELETE RESTRICT
```

No usar `ON DELETE CASCADE` en tablas de negocio porque puede destruir historial.

Excepciones solo si estÃ¡n justificadas, por ejemplo tablas temporales o staging.

---

### 16.2 Campos `created_at` y `updated_at`

PatrÃ³n recomendado:

```sql
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
```

---

### 16.3 Estados como VARCHAR

Para el MVP se aceptan estados como `VARCHAR`, siempre que el backend valide valores permitidos.

DecisiÃ³n mixta oficial:

```text
estados_practica se maneja como catÃ¡logo.
usuarios_sistema.estado_cuenta se maneja como VARCHAR validado por backend.
jornadas_asistencia.estado_jornada se maneja como VARCHAR validado por backend.
evaluaciones_practicante.estado_evaluacion se maneja como VARCHAR validado por backend.
```

Ejemplos:

```text
usuarios_sistema.estado_cuenta
jornadas_asistencia.estado_jornada
evaluaciones_practicante.estado_evaluacion
```

Si el sistema crece, se puede migrar a catÃ¡logos adicionales.

---

### 16.4 Campos JSON

No usar JSON como soluciÃ³n principal para datos estructurales del dominio.

`auditoria_cambios.valores_anteriores` y `auditoria_cambios.valores_nuevos` pueden guardar texto serializado o JSON como texto, pero sin depender de funciones avanzadas de MySQL que puedan variar en Hostinger/MariaDB.

---

## 17. Orden recomendado de creaciÃ³n en `schema.sql`

Crear tablas en este orden para evitar errores de claves forÃ¡neas:

```text
1. regiones
2. instituciones
3. sedes
4. carreras
5. tipos_practica
6. estados_practica
7. areas_practica
8. tipos_marca
9. roles_sistema
10. practicantes
11. practicas
12. documentos_practicante
13. horarios_practicante
14. detalle_horario_practicante
15. excepciones_horario_practicante
16. usuarios_sistema
17. usuarios_roles
18. sesiones_usuario
19. tokens_usuario
20. jornadas_asistencia
21. marcas_asistencia
22. historial_areas_practicante
23. bloques_area_jornada
24. evaluaciones_practicante
25. auditoria_cambios
```

Nota:

`usuarios_sistema` depende de `practicantes`, pero `practicantes` no depende de `usuarios_sistema`.

`jornadas_asistencia` puede referenciar `usuarios_sistema` mediante `creada_por` y `aprobada_por`, por eso se crea despuÃ©s de `usuarios_sistema`.

---

## 18. Orden recomendado de seeds

Crear seeds en este orden:

```text
1. regiones
2. instituciones
3. sedes normalizadas de Duoc UC, INACAP y ENAC definidas en docs/historial/referencias/CONTEXTO_PROYECTO.md, mÃ¡s sede fallback "No especificada" por instituciÃ³n
4. carreras
5. tipos_practica
6. estados_practica
7. areas_practica
8. tipos_marca
9. roles_sistema
10. usuario ADMIN inicial
11. rol ADMIN para usuario inicial
```

---

## 19. Usuario ADMIN inicial

Debe existir un usuario administrador inicial para entrar al sistema.

Reglas:

```text
id_practicante NULL
rol ADMIN
estado_cuenta ACTIVO
activo 1
password_hash generado con password_hash()
debe_cambiar_password 1 recomendado
email_verificado 1
```

No guardar contraseÃ±a en texto plano en `schema.sql` ni en documentaciÃ³n pÃºblica.

El seed puede incluir un hash temporal generado previamente, pero debe indicarse que debe cambiarse en primer ingreso.

---

## 20. Reglas para implementar `schema.sql`

Se debe:

```text
1. Crear el archivo /database/schema.sql.
2. Usar InnoDB y utf8mb4.
3. Crear tablas en el orden definido.
4. Crear PK, FK, UNIQUE e INDEX explÃ­citos.
5. No usar ON DELETE CASCADE en tablas de negocio.
6. No eliminar tablas o campos definidos sin pedir validaciÃ³n.
7. No crear tablas nuevas innecesarias si ya existe una tabla que cubre el caso.
8. No permitir PRACTICANTE + ADMIN.
9. Incluir sesiones_usuario.
10. Incluir auditoria_cambios con id_rol_contexto e id_sesion.
11. Mantener usuarios_sistema.id_practicante como NULL permitido.
12. Mantener tokens_usuario.token_hash, nunca token plano.
13. Mantener password_hash, nunca password plano.
14. Respetar anulaciÃ³n lÃ³gica granular de marcas_asistencia.
15. Respetar inserciÃ³n diferida de sesiones_usuario hasta que exista id_rol_activo.
16. Si usuarios_sistema.id_practicante IS NOT NULL, no permitir asignar rol ADMIN.
17. Mantener auditorÃ­a con id_usuario_actor, id_rol_contexto e id_sesion.
18. Mantener practicantes.id_sede como NULL permitido para instituciÃ³n Otra.
19. Mantener practicantes.institucion_otra y practicantes.sede_otra para texto libre de instituciÃ³n Otra.
20. No guardar texto libre ingresado por usuarios en el catÃ¡logo oficial sedes.
21. Usar sedes.nombre_sede y sedes.codigo_sede; no usar sedes.nombre ni sedes.direccion en esta etapa.
```

Antes de modificar `schema.sql`, se debe indicar:

```text
Archivos que modificarÃ¡.
Tablas que crearÃ¡.
Riesgos.
CÃ³mo probar en phpMyAdmin o consola MySQL.
```

DespuÃ©s de modificarlo, se debe entregar:

```text
Resumen de cambios.
Listado de tablas creadas.
Comando o pasos para importar en XAMPP/phpMyAdmin.
Errores conocidos si existen.
Siguiente paso recomendado.
```

---

## 21. Criterios de aceptaciÃ³n del modelo de BBDD

El modelo se considera correcto si cumple:

```text
1. Permite usuarios individuales.
2. Permite mÃºltiples roles por usuario.
3. Permite PRACTICANTE + SUPERVISOR.
4. Bloquea conceptualmente PRACTICANTE + ADMIN.
5. Permite ADMIN externo con id_practicante NULL.
6. Permite SUPERVISOR externo con id_practicante NULL.
7. Obliga a PRACTICANTE a estar vinculado a practicantes.id_practicante.
8. Registra sesiones con rol activo.
9. Permite auditorÃ­a con usuario, rol contexto y sesiÃ³n.
10. Permite activaciÃ³n y recuperaciÃ³n mediante tokens hash.
11. Soporta prÃ¡cticas histÃ³ricas por practicante.
12. Soporta horarios diferenciados.
13. Soporta excepciones de horario.
14. Soporta jornadas y marcas de asistencia separadas.
15. Soporta Ã¡reas por historial.
16. Soporta mÃºltiples Ã¡reas dentro de una jornada.
17. Soporta evaluaciones vinculadas a una prÃ¡ctica.
18. Evita borrado fÃ­sico como estrategia normal.
19. Es compatible con PHP + PDO + MySQL/MariaDB.
20. Es implementable en XAMPP y Hostinger.
```

---

## 22. Advertencias tÃ©cnicas

No asumir que todas las reglas de negocio se resuelven solo con claves forÃ¡neas.

La base de datos estructura y protege integridad referencial, pero algunas reglas deben validarse en backend PHP:

```text
secuencia de marcas
bloqueo por conflicto de interÃ©s
prohibiciÃ³n PRACTICANTE + ADMIN
aprobaciÃ³n de horas extra
detecciÃ³n de atraso
detecciÃ³n de salida anticipada
asistencia extraordinaria
cÃ¡lculo de horas
selector de rol activo
permisos por rol
```

La BBDD debe dejar los campos necesarios para aplicar esas reglas, pero la validaciÃ³n final debe hacerse en servicios/helpers PHP.

---

## 23. Estado oficial

Este documento queda como especificaciÃ³n tÃ©cnica de Base de Datos para la primera etapa del proyecto.

Cualquier cambio estructural posterior debe ser validado antes de modificar `schema.sql`.
