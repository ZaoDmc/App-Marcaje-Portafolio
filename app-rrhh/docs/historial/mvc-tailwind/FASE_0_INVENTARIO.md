> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 0: inventario y base de implementación

Fecha: 2026-10-13 (America/Santiago).

## Registro de cambios de esta fase

Agregué este documento para identificar la versión de partida, las dependencias y las decisiones pendientes antes de modificar la aplicación. Agregué `FASE_0_RUTAS.csv` para enumerar los puntos de entrada actuales y su destino. Actualicé `PROGRESO.md` para conservar la trazabilidad de esta revisión.

No se modificó el comportamiento de la aplicación, código PHP, configuración de producción, base de datos ni archivos de usuarios. Se actualizó la especificación de negocio con la regla de colación confirmada por el responsable. Los futuros cambios de código deben incluir comentarios que expliquen su propósito, como solicitó el responsable del proyecto.

## Base de Git comprobada

- El árbol de trabajo estaba limpio al comenzar; los 13 archivos pendientes de la revisión anterior ya no estaban modificados localmente.
- `main` local estaba en `b214194`, antes del merge comunicado por el responsable.
- Se consultó `origin` y se actualizó `main` mediante fast-forward a `95fe890`.
- Se creó `codex/fase-0-inventario` desde ese commit. No se crearon commits ni se publicó la rama durante esta fase.
- La rama antigua local no se eliminó. Su existencia local no demuestra que siga existiendo en GitHub.

## Propósito y flujos que deben preservarse

El sistema administra prácticas, horarios pactados, marcaje de asistencia, correcciones por supervisores, permisos, reportes y auditoría. Mantiene usuarios individuales y contexto activo de rol, incluyendo PRACTICANTE + SUPERVISOR con prohibición de autosupervisión.

| Flujo | Componentes actuales | Dependencias que deben preservarse |
| --- | --- | --- |
| Login y selección de rol | Controllers/Auth, AuthService, helpers | Sesiones, bloqueo de intentos, CSRF, destinos por rol |
| Activación y recuperación | Controllers/Auth, MailService | Tokens, correo SMTP, URLs en correos y cambios de contraseña |
| Alta individual | Controllers/Admin y Supervisor, PracticanteModel | Usuario, práctica, horario, rol y token de activación |
| Carga masiva | CargaMasivaService, PracticanteModel, xlsx_helper | CSV/XLSX y descarga de plantillas antes de imprimir HTML |
| Completar ficha y perfil | MisDatosController | Catálogos, campos editables, horario inicial, foto y documentos |
| Marcaje e historial | Controllers/Practicante, ReglasAsistenciaService | Secuencia de marcas, estado de jornada, horas y horario vigente |
| Correcciones | AsistenciaService, ReglasAsistenciaService | Marcas anuladas conservadas, motivos, horas extra, áreas y auditoría |
| Horarios y excepciones | HorariosPracticanteService, ExcepcionesHorarioService | Vigencias y prioridad de excepciones por fecha |
| Permisos | PermisosService, horario-dia.php | Ausencia total/parcial, recuperación y excepciones generadas |
| Acompañamiento | AcomController, AcompanamientoModel | Guardado AJAX, seguimiento y total de horas |
| Usuarios, catálogos y auditoría | Controllers/Admin, modelos y AuditoriaService | Restricciones por rol, bajas lógicas y trazabilidad |
| Reportes y métricas | ReportesService, MetricasModel | Filtros, estados acumulables, horas previas y extras aprobadas |

## Inventario de rutas

`FASE_0_RUTAS.csv` enumera los 36 archivos de entrada de la raíz y de las carpetas de roles. Incluye 33 puentes a controladores y tres entradas con lógica propia: `index.php`, `diagnostico_correo.php` y `practicante/api/horario-dia.php`.

Las rutas del CSV describen el estado encontrado, no una lista de acceso autorizada. La autorización real debe comprobarse en controladores y servicios. El diagnóstico SMTP debe quedar excluido del futuro mapa público.

| Entrada adicional | Local actual | Producción comunicada |
| --- | --- | --- |
| `/` | index.php raíz decide el destino por sesión; public/index.php redirige al login | Carga login.php |
| `/index.php` | Comportamiento diferente según punto de entrada | No tiene alias explícito en el código compartido; revisar comportamiento del servidor |
| `/login` | Sin alias explícito | Alias a login.php |
| `public/index.php` | Entrada alternativa de la migración | Existe otro index.php mantenido en el directorio público del hosting |

Dependencias externas a los enlaces de navegación que también deben conservarse:

- Correos con `activar-cuenta.php?token=...` y `restablecer-password.php?token=...`.
- Consulta JSON `practicante/api/horario-dia.php?fecha=YYYY-MM-DD` desde la vista de permisos.
- Foto mediante `practicante/foto-perfil.php` y descarga mediante `practicante/descargar-documento.php?id=...`.
- Plantillas en carga masiva con `plantilla=1` y selección de formato.
- Formularios, filtros, paginación y respuestas AJAX de acompañamiento.
- Helpers `url()`, `asset()` y `redirect_to()`, dependientes de BASE_URL, BASE_PATH y ASSETS_URL.
- Menú activo calculado con SCRIPT_NAME: habrá que adaptarlo cuando todas las peticiones entren por index.php.

## Archivos puente y separación MVC

Los puentes de rutas siguen siendo necesarios porque los dos enrutadores buscan archivos físicos. Los controladores de ADMIN/SUPERVISOR siguen incluyendo varios puentes de `includes/`; deben reemplazarse esas dependencias antes de borrar archivos.

| Puente en includes/ | Destino en src/ |
| --- | --- |
| auth.php | Services/AuthService.php |
| auditoria.php | Services/AuditoriaService.php |
| mailer.php | Services/MailService.php |
| reglas_asistencia.php | Services/ReglasAsistenciaService.php |
| asistencia_gestion.php | Services/AsistenciaService.php |
| horarios_practicante.php | Services/HorariosPracticanteService.php |
| excepciones_horario.php | Services/ExcepcionesHorarioService.php |
| ausencias_esperadas.php | Services/AusenciasEsperadasService.php |
| permisos_gestion.php | Services/PermisosService.php |
| reportes_gestion.php | Services/ReportesService.php |
| practicante_carga_masiva.php | Services/CargaMasivaService.php |
| practicante_registro.php | Models/PracticanteModel.php |
| usuarios_gestion.php | Models/UsuarioModel.php |
| catalogos_admin.php | Models/CatalogoModel.php |
| dashboard_metricas.php | Models/MetricasModel.php |
| areas_practicante.php | Models/AreaPracticanteModel.php |
| areas_jornada.php | Models/AreaJornadaModel.php |
| acom_gestion.php | Models/AcompanamientoModel.php |
| helpers.php, xlsx_helper.php | Helpers/ |
| layout/head.php, sidebar.php, foot.php | Views/layout/ |

Persisten funciones que procesan solicitudes y renderizan páginas dentro de servicios/modelos: asistencia_gestion_render_page, horarios_practicante_render_gestion_page, permisos_gestion_render_supervisor, practicante_carga_masiva_render, reportes_gestion_render_page, practicante_registro_render y catalogos_admin_render. Su separación corresponde a fases posteriores.

## Entornos y despliegue

| Aspecto | Local comprobado | Producción comunicada por el responsable |
| --- | --- | --- |
| Directorio privado | Raíz del repositorio | backend-rrhh, fuera de public_html |
| Directorio público | Se conservan raíz y public/ | public_html/software-rrh |
| Entrada | public/index.php y entradas tradicionales | index.php con Composer, Dotenv y rutas físicas |
| Localización del backend | Directorio padre o BACKEND_PATH del proceso | dirname(__DIR__, 2) + /backend-rrhh |
| Dependencias | vendor/autoload.php existe; composer.json y composer.lock no están en la raíz versionada | Composer y lock compartidos por el responsable |
| PHP | XAMPP CLI 8.0.30 | PHP 8.3.35, confirmado por el responsable mediante X-Powered-By |
| Configuración | Cargador propio en config/app.php | Dotenv en la entrada y luego el cargador propio |
| Recursos | assets/ y public/assets/ | assets/ del directorio público |
| Publicación | Sin procedimiento reproducible versionado identificado | ZIP de asistencia extraído en backend-rrhh; frontend actualizado por separado |

El uso de `never` en helpers requiere PHP 8.1 para su semántica prevista. El composer.json compartido declara >=8.0; PHPUnit 10 del lock requiere >=8.1. La versión mínima debe resolverse en fase 1, sin suponer que la validación de sintaxis confirma compatibilidad en ejecución.

## Almacenamiento: hechos comprobados y pendientes

`mis_datos_guardar_archivo()` guarda bajo APP_ROOT, utilizando el ID de la tabla practicantes, no el ID de usuario, práctica o documento:

```text
APP_ROOT/uploads/practicantes/{id_practicante}/perfil/{nombre_aleatorio}.{extension}
APP_ROOT/uploads/practicantes/{id_practicante}/documentos/{nombre_aleatorio}.{extension}
```

- Se genera el nombre con random_bytes; el nombre original queda como metadata.
- `documentos_practicante` conserva id_practicante, id_practica, tipo_documento, ruta_relativa, MIME, tamaño y estado activo.
- FotoPerfilController y DescargarDocumentoController buscan dentro de APP_ROOT/uploads, comprueban propiedad y limitan la ruta física mediante realpath.
- Al cambiar foto se inactivan registros anteriores; archivos parecidos pueden ser versiones conservadas y no duplicados descartables.
- El código revisado no genera ni utiliza explícitamente `uploads.751`.
- Localmente se observó una carpeta de practicante; no se abrieron documentos ni se incluyeron IDs o datos personales en este informe.
- No hay archivos uploads versionados en Git actualmente, pero .gitignore tampoco excluye la carpeta: existe riesgo de incorporarlos accidentalmente en el futuro.

El responsable confirmó dos carpetas en la raíz del proyecto/backend productivo: `uploads/` y `uploads.751/`, con contenido parcialmente distinto. La ubicación es relativa a backend-rrhh; no se necesita publicar la ruta absoluta de la cuenta de hosting. El código revisado utiliza `uploads/`. No se ha comprobado el origen de `uploads.751/`, si el código desplegado tiene ajustes adicionales o si existen referencias productivas a esa carpeta. No se asume que sea una copia descartable.

Una carpeta contiene estas directivas comunicadas por el responsable:

```apache
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5
```

Intentan impedir ejecución PHP; su efectividad depende del manejador PHP y del servidor. No equivalen a exigir autenticación para descargar fotos o documentos. No se deben copiar ciegamente, borrar carpetas ni fusionar contenidos durante esta fase.

Procedimiento pendiente para identificar el almacenamiento activo: contrastar las rutas de documentos_practicante con APP_ROOT del backend realmente desplegado; comparar existencia, tamaño y hash de archivos sin exponer su contenido; clasificar activos, históricos y huérfanos; preparar respaldo antes de cualquier traslado. Una ruta relativa sola no distingue dos copias con la misma estructura.

## Reglas de horas observadas, todavía sin cambios

| Caso | Comportamiento actual observado en el código | Estado de decisión |
| --- | --- | --- |
| Falta entrada o salida final | INCOMPLETA, cero horas | Preservar como base |
| Estados acumulables | VALIDA, OBSERVADA y CORREGIDA | Verificar consistencia entre módulos |
| Estados no acumulables | INCOMPLETA, ANULADA y PENDIENTE_REVISION | Preservar como base |
| Colación completa | Descuenta el tiempo entre marcas | Preservar como base |
| Sin colación, jornada >=6 horas | OBSERVADA; con horario se aplica tope planificado; sin horario no se descuenta una hora ficticia | Confirmado: observar y contar el tiempo trabajado, sin descuento automático de una hora por falta de marcas de colación; revisar que el tope actual no contradiga la regla |
| Excedente <=0,25 horas | No se sugiere extra ni se reconoce automáticamente el excedente | Conservar comportamiento actual; no se autorizó modificar esta tolerancia |
| Excedente >0,25 horas | Sugiere el excedente completo para aprobación | Conservar comportamiento actual; no se autorizó modificar esta tolerancia |
| Atraso | Se marca desde 600 segundos de diferencia | Conservar comportamiento actual; no se autorizó modificar este umbral |
| Horas previas y extras aprobadas | Incluidas en varios módulos; acompañamiento omite extras aprobadas | Unificar posteriormente |

La confirmación del responsable sustituye la instrucción anterior de descontar automáticamente una hora de colación. Se actualizó CONTEXTO_PROYECTO.md para evitar que una implementación futura aplique esa regla antigua. El cálculo PHP todavía no se modificó y debe comprobarse con casos con/sin horario, colación parcial y excedentes.

### Requerimiento incorporado: revisión de jornadas por supervisor

El responsable solicita una acción para revisar una jornada OBSERVADA o PENDIENTE_REVISION y darla por corregida/revisada cuando todo esté en orden, sin exigir que se modifiquen sus marcas.

- La acción general actual `asistencia_gestion_cambiar_estado()` solo permite OBSERVADA y PENDIENTE_REVISION; todavía falta implementar esa transición.
- Se propone reutilizar CORREGIDA como estado técnico y una etiqueta de acción «Marcar como revisada», evitando introducir REVISADA sin revisar todas las consultas. Esta es una propuesta técnica, no un cambio aplicado.
- Antes de reconocer horas se deben comprobar entrada y salida válidas, estado de jornada, rol y prohibición de autosupervisión.
- La revisión debe auditar actor, fecha, motivo y estado anterior/nuevo. No debe aprobar horas extra implícitamente ni eliminar marcas o incidencias históricas.
- La falta de colación no debe generar un descuento automático de una hora. Revisar una jornada debe permitir reconocer el trabajo conforme a la regla confirmada y conservar la trazabilidad.
- La implementación y pruebas corresponden a fase 3; no se adelantaron cambios funcionales durante el inventario.

## Hallazgos clasificados para las siguientes fases

| Tipo | Hallazgo | Tratamiento previsto |
| --- | --- | --- |
| Seguridad | public/index.php carga archivos existentes a partir de la URL | Fase 2: mapa explícito de rutas |
| Seguridad | Entrada productiva permite subrutas basadas solo en el primer segmento | Fase 2: destinos autorizados sin concatenar rutas arbitrarias |
| Seguridad | .htaccess productivo permite archivos existentes antes de proteger sensibles | Fase 2: revisar orden y protección efectiva |
| Seguridad | diagnostico_correo.php envía correo por GET y muestra diagnóstico | Excluir del mapa y del paquete público |
| Autorización | Permisos y acompañamiento requieren validar autosupervisión al guardar | Fase 3: comprobación de actor y objetivo |
| Auditoría | Eventos de permisos no admitidos e identificador con clave distinta | Fase 3: catálogo y payload coherentes |
| Funcional | Colación confirmada sin descuento automático; falta acción de revisión; acompañamiento omite extras | Fase 3: aplicar especificación confirmada y validar totales |
| Arquitectura | Servicios/modelos renderizan y controladores dependen de includes | Fases 4 y 5 |
| Interfaz | Bootstrap convive con Tailwind; quedan modales y menú Bootstrap | Fase 6: migración y validación por pantalla |
| Codificación | Revisar textos dañados en MisDatosController | Fase 3 |
| Despliegue | Entrada y dependencias distintas entre local y hosting | Fases 1, 2 y 7 |
| Persistencia | Ubicación activa y origen de uploads.751 sin confirmar | Pendiente externo; no eliminar ni fusionar |

## Criterios de cierre

- [x] Main sincronizado y rama de trabajo creada desde el merge.
- [x] Árbol inicial limpio y rutas/dependencias inventariadas.
- [x] Estructura de despliegue comunicada y diferencias registradas.
- [x] Comportamiento de escritura/lectura de uploads identificado en el código.
- [x] Reglas actuales de cálculo registradas sin modificarlas.
- [x] Confirmar PHP seleccionado en Hostinger: 8.3.35, comunicado por el responsable.
- [x] Confirmar regla de colación: jornada observada sin descuento automático de una hora; tolerancias actuales conservadas.
- [x] Confirmar nombres y ubicación relativa de las carpetas: uploads/ y uploads.751/ en la raíz del backend productivo.
- [ ] Contrastar almacenamiento activo y archivos exclusivos con el código y metadata realmente desplegados; pendiente de verificación productiva, sin borrar ni fusionar carpetas.

Estado: inventario local y decisiones funcionales consolidados. La conciliación de archivos productivos queda pendiente antes de intervenir almacenamiento y se traslada como requisito de fase 7. No se afirma que uploads.751 sea redundante ni que sus archivos puedan eliminarse. La fase 1 de dependencias puede prepararse sin depender de esa conciliación.
