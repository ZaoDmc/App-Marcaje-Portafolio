# Formulario validación

Estado documental al **5 de noviembre de 2026**: módulo incorporado inicialmente en 1.2.0 y correcciones integradas en `dev` para la entrega **1.4.1**. La primera implementación se preparó el 29/10/2026. No se acredita un despliegue de este módulo; siguen siendo necesarios la configuración de datos reales y el parche 012 en instalaciones existentes.

## Objetivo y alcance

Permitir a ADMIN y SUPERVISOR cargar el DOCX de validación enviado por un estudiante, completar los antecedentes de práctica y descargar una nueva copia para devolverla por el canal externo acordado.

La primera plantilla soportada es Duoc UC. El mapeo actual está definido para esa plantilla; otra institución requiere código, plantilla revisada y pruebas específicas. No se pretende mapear cualquier DOCX desde una interfaz configurable.

El módulo no:

- crea solicitudes de aprobación institucional ni registra el resultado externo;
- crea o habilita fichas, prácticas o cuentas, ni envía correos de activación;
- extrae, guarda o modifica los antecedentes personales escritos en el DOCX;
- envía documentos por WhatsApp o correo;
- procesa automáticamente formularios de otras instituciones en la primera entrega.

El prerregistro existente se realiza por separado, después de la aprobación externa. Este módulo no lo inicia ni se enlaza con él.

## Flujo acordado

1. ADMIN o SUPERVISOR abre **Formulario validación** desde una opción propia del menú lateral.
2. Selecciona una institución del catálogo activo. Todas se muestran, pero solo se puede seleccionar una que tenga una plantilla implementada.
3. Carga el DOCX de esa institución que ya contiene los antecedentes del estudiante.
4. El servidor valida que el archivo sea un DOCX legible y que corresponda a la plantilla seleccionada. El original se procesa temporalmente y no se conserva.
5. El usuario completa los antecedentes variables de la práctica y selecciona la carrera para cargar las sugerencias de unidad y funciones.
6. El usuario revisa los datos fijos del centro y los valores sugeridos. Puede ajustar unidad y funciones para ese documento. La configuración de centro/beneficios y las sugerencias por carrera se mantienen en el mismo módulo.
7. El sistema genera un archivo nuevo, conserva sin cambios los antecedentes del estudiante y completa únicamente los campos definidos para la plantilla.
8. El sistema guarda de forma privada el DOCX generado y metadatos técnicos mínimos. ADMIN y SUPERVISOR pueden descargarlo.
9. RRHH revisa y devuelve el documento por el canal externo. La firma manuscrita y el timbre del centro se incorporan fuera del sistema.

Una generación fallida no debe guardar un registro que parezca exitoso ni dejar el archivo original temporal en almacenamiento persistente. El archivo cargado nunca se modifica.

## Datos del formulario Duoc UC

### Datos fijos compartidos

Se administran desde configuración y se aplican a cada documento:

- nombre y RUT del centro de práctica;
- dirección, comuna, giro comercial, teléfono y sitio web;
- nombre, cargo y correo electrónico del jefe directo;
- beneficios asociados.

ADMIN y SUPERVISOR pueden mantener esta configuración global, según decisión del responsable. Una modificación afecta futuras generaciones, no documentos ya generados.

La sección **Firma y timbre Centro de Práctica** permanece vacía para la firma manuscrita y el timbre reales.

### Datos por documento

La pantalla de generación solicita los datos variables que la plantilla necesita y que no se obtienen de una ficha previa:

- carrera, requerida para proponer unidad y funciones;
- fecha de inicio y término;
- horario de trabajo y elección obligatoria de si es variable («Sí» o «No»);
- número total de horas;
- unidad, sección o departamento;
- funciones principales.

Unidad y funciones se sugieren desde una configuración por carrera y permanecen editables en cada documento. ADMIN y SUPERVISOR pueden mantener esas sugerencias y ajustar sus valores en una generación. Si una carrera no tiene una sugerencia configurada, los campos requeridos deben completarse antes de generar.

Los campos de tipo de práctica, salida intermedia, sede/campus y otros antecedentes del estudiante permanecen como fueron escritos en el DOCX. El módulo no los lee ni los usa para construir datos persistidos.

## Correcciones integradas en 1.4.1

Procedencia: commit `871f60b` del colaborador (04/11/2026), integrado por `9d9155d` en `dev` (05/11/2026). Su alcance se comprobó en el controlador y la vista compartidos; no se modificaron el modelo, el servicio de generación ni las tablas en esa corrección.

| Elemento | Comportamiento actual |
| --- | --- |
| Sitio web del centro | Acepta `example.invalid`, `http://example.invalid` y `https://example.invalid`. Sin protocolo añade `https://`; con protocolo explícito admite únicamente HTTP/HTTPS. Comprueba URL válida y host antes de guardar. |
| Confirmación al guardar | Guarda un aviso en sesión y redirige a Configuración. La petición siguiente muestra «Los datos del centro se guardaron con éxito.» y consume el aviso. |
| Horario de trabajo | Texto requerido, máximo 300 caracteres. La ayuda pide días y horas en formato 24 h; por ejemplo «lunes a viernes, de 10:00 a 18:00». No analiza automáticamente la coherencia del turno ni calcula horas. |
| Horario variable | Dos opciones obligatorias, «Sí» y «No». El servidor rechaza valores distintos o vacíos. Se escribe esa respuesta en el campo de horario variable del DOCX; ya no se solicita una descripción libre adicional. |

El cambio conserva roles, CSRF, generación y descarga existentes. La flexibilidad de la URL no implica una comprobación de que el sitio web exista o responda; esta operación guarda texto, no consulta el sitio indicado.

Código: `src/Controllers/Shared/FormularioValidacionController.php` y `src/Views/shared/formulario-validacion.view.php`. El servicio `src/Services/FormularioValidacionService.php` conserva el mapeo de las celdas.

## Plantillas institucionales

- Cada institución soportada requiere una definición de versión y un adaptador de generación probado contra un DOCX real en blanco y una copia de prueba completada.
- El piloto incluye únicamente el formulario Duoc UC revisado en octubre de 2026.
- El selector obtiene las instituciones activas de `instituciones`. Las que todavía no tienen adaptador aparecen deshabilitadas; la validación del servidor también impide generar para ellas.
- La detección debe comprobar la estructura esperada y los rótulos/celdas de destino. Si el formulario cambió, el archivo está dañado o la estructura no coincide, se informa el error y no se ofrece una descarga exitosa.
- No buscar posiciones por índices sin verificar primero la versión completa del documento.
- Una nueva institución o versión requiere su DOCX vigente, inventario de campos, responsable de los datos y pruebas de apertura/formato en Word.

La prueba exploratoria previa con el DOCX Duoc demostró que una copia puede completarse y abrirse en Word sin modificar la sección del estudiante. La última muestra de sesión usó el documento proporcionado con datos personales y añadió antecedentes ficticios; no se transcribió el contenido personal al repositorio. No usar esa muestra como fixture. La implementación actual procesa DOCX mediante PHP `ZipArchive`/`DOMDocument`; las pruebas automatizadas usan únicamente documentos sintéticos. La confirmación visual de la muestra previa no sustituye pruebas de Word para múltiples versiones ni casos de campos largos.

## Persistencia y privacidad

- Guardar únicamente el documento generado; el DOCX de entrada se elimina después del procesamiento, tanto si resulta correcto como si falla.
- Mantener los resultados fuera del directorio público, con nombres internos aleatorios y descargas autenticadas para ADMIN/SUPERVISOR.
- No establecer borrado automático del DOCX generado. Conservarlo de forma privada hasta que exista una política explícita de eliminación administrativa.
- Guardar metadatos mínimos: institución y versión de plantilla, carrera seleccionada, creador, fecha de generación y ruta/metadata del resultado. No guardar nombre, RUN, correos ni otros datos del estudiante en la fila de la solicitud.
- No incluir contenido del DOCX, datos personales ni rutas físicas sensibles en auditoría, errores o respuestas.
- Revalidar rol en servidor, método POST y CSRF al cargar/generar; validar tipo y estructura reales, tamaño comprimido y descomprimido, cantidad de partes y nombres de entradas ZIP. Rechazar archivos con macros o estructura no permitida.
- Usar almacenamiento privado existente y una ruta de descarga autenticada; no reutilizar la descarga de documentos de practicante, que depende de una ficha que todavía no existe.

La implementación usa `formulario_validacion_configuracion` para los datos compartidos del centro, `formulario_validacion_plantillas_carrera` para las sugerencias, y `documentos_validacion_generados` para los metadatos mínimos de salida. El parche `012` crea esas tablas en instalaciones existentes; `database/schema.sql` las incluye para instalaciones nuevas. Los archivos DOCX no se almacenan en la base.

## Encaje técnico

- Mantener PHP, PDO, MVC y rutas explícitas del proyecto.
- Añadir una ruta protegida para ADMIN/SUPERVISOR, su controlador y servicio; el acceso del menú no sustituye la autorización del servidor.
- Procesar DOCX como paquete OOXML con `ZipArchive` y XML namespace-aware. Generar una copia, modificar únicamente las partes previstas y conservar las restantes. No automatizar Word de escritorio en el servidor.
- El servicio implementa el mapeo Duoc UC verificado por estructura y rótulos; las otras instituciones aún no están implementadas. La institución elegida se registra mediante el código de su adaptador.
- `composer.json` ya declara `ext-zip`; no se agregó una biblioteca Composer. XAMPP PHP 8.1.25 local tiene ZIP habilitado; las pruebas con PHP CLI 8.3.35 usan `-d extension=zip`. Verificar `ext-zip` en el PHP web/productivo antes de publicar.
- El límite de subida está fijado en 5 MB en la aplicación y debe coordinarse con `upload_max_filesize`, `post_max_size` y el proxy/hosting. Revisar el tamaño de DOCX representativos antes de publicar.

## Validación inicial y pendientes de publicación

- `tests/formulario-validacion.php`: 20 comprobaciones correctas con DOCX sintéticos, incluyendo mapeo, reconocimiento de instituciones soportadas y no soportadas, contenido personal sintético, entradas inválidas y rechazos de campos ocupados.
- `tests/routing.php`: 177 comprobaciones correctas; incluye los roles y métodos HTTP de las rutas del módulo.
- `tests/mvc.php`: 243 comprobaciones correctas.
- `php -l` pasó en los archivos PHP nuevos/modificados del módulo y sus pruebas.
- No se ejecutó el parche 012 ni se verificó la persistencia contra MariaDB. No se realizaron pruebas HTTP integradas con sesión/CSRF ni una revisión visual nueva en Word; estas quedan pendientes.
- Antes de publicar, probar en una base desechable la migración y la persistencia, y revisar visualmente en Word documentos sintéticos con textos cortos/largos, sin usar datos personales reales como fixtures.

## Pendientes antes de publicar

- Aplicar `database/2026-10-29_patch_formulario_validacion_012.sql` en instalaciones existentes, después de respaldar y confirmar el estado real de la base; `database/schema.sql` ya incorpora las tablas para instalaciones nuevas. No se ejecutó SQL como parte de esta implementación.
- Ingresar desde la pantalla de configuración los valores reales vigentes del centro, jefe directo y beneficios; no versionarlos como datos ficticios.
- Cargar las sugerencias aprobadas de unidad y funciones por carrera antes de usarlas como predeterminadas.
- Confirmar el límite de subida con DOCX representativos y verificar `upload_max_filesize`, `post_max_size` y hosting web.
- El XAMPP PHP 8.1.25 local tiene `ext-zip`; el PHP CLI 8.3.35 ejecuta las pruebas con `-d extension=zip`. Verificar `ext-zip` también en el PHP web/productivo al desplegar.
- Ejecutar la revisión visual en Word de documentos sintéticos con textos cortos y largos antes de publicar. No usar documentos con datos personales reales como fixtures.
- Para habilitar otra institución, obtener su formulario vigente y completar una implementación y validación de adaptador específica.

## Versión de software

El módulo se incorporó en **1.2.0** como funcionalidad MENOR. La entrega integrada actual se normaliza en **1.4.1**, PARCHE respecto de 1.4.0, por indicación del responsable. La fuente única continúa siendo `config/version.php`; ni la versión de origen del módulo ni el título del merge sustituyen ese valor. No se renumeran las entradas históricas ni se acredita producción por integrar un PR.

La revisión del 05/11/2026 contrasta código y documentación. La suite DOCX usa el servicio directamente y no prueba por sí sola el POST del controlador, las opciones Sí/No en navegador, el aviso de sesión o la persistencia de URL. Una prueba del formato visual en Word y una prueba HTTP con sesión/base ficticia siguen siendo comprobaciones diferentes.

Comprobaciones ejecutadas el 05/11/2026: 20 pruebas DOCX sintéticas, 21 de configuración y 244 MVC correctas; sintaxis de `config/version.php` y de la barra lateral correcta, y `git diff --check` sin errores. ZIP ya está habilitado en el PHP CLI 8.3.35 usado en esta revisión, por lo que no se añadió `-d extension=zip`. El primer intento de la prueba DOCX falló al renombrar un ZIP temporal dentro del aislamiento; la repetición autorizada fuera de ese aislamiento pasó. No se modificaron documentos reales, tablas, configuración de Hostinger ni se enviaron mensajes.
