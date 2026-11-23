# Practicantes y archivos

## Propósito y flujo

Una persona puede tener datos personales y una práctica con estado, horas requeridas, horas previas, vigencia, institución, carrera, horario y área. No confundir `id_practicante`, `id_practica` e `id_usuario`: identifican registros distintos.

ADMIN/SUPERVISOR registra o gestiona fichas según alcance. El practicante completa sus campos permitidos y consulta los institucionales. El horario puede capturarse durante el prerregistro o cierre inicial si falta; después se administra mediante su flujo de vigencias, no como un campo personal libre.

## Datos y archivos opcionales

| Elemento | Regla actual |
| --- | --- |
| Foto | JPG/JPEG o PNG, máximo 5 MB |
| Presentación de foto | Imagen completa centrada sin recorte; recomendación 500 × 500 px |
| Sin foto | Iniciales; no bloquear el cierre inicial de ficha |
| Documentos de práctica | PDF, Word o Excel; hasta cinco activos, máximo 5 MB por archivo |
| Metadatos | Nombre original, ruta relativa, MIME/tamaño y estado; conservar bajas lógicas |

Los archivos se guardan en `uploads` del backend y se entregan con control de acceso. No publicar enlaces directos al almacenamiento. La descarga propia valida rol PRACTICANTE, propiedad y documento activo; no suponer que SUPERVISOR tiene descarga directa por conocer un ID.

La carpeta `uploads.751` observada se debe tratar como contenido previo a inventariar; no es una política vigente de duplicación ni se debe borrar o fusionar por parecido. Los archivos y respaldos persistentes quedan fuera del despliegue y de Git.

## Carga masiva

La carga CSV/XLSX reutiliza normalización y validación del registro individual; no debe saltarse unicidad, roles o trazabilidad. El servicio actual limita a 300 filas y 5 MB. El servidor y PHP tienen además límites propios, que no quedan acreditados por esos controles de aplicación.

## Código y datos

`src/Models/PracticanteModel.php`, `src/Controllers/Shared/`, `src/Controllers/Practicante/MisDatosController.php`, `FotoPerfilController.php` y `DescargarDocumentoController.php` dentro de esa misma carpeta; `src/Services/CargaMasivaService.php` y `src/Helpers/xlsx_helper.php`.

Tablas principales: `practicantes`, `practicas`, `documentos_practicante`, catálogos y relaciones de horario/área. Los ficheros binarios no forman parte de `schema.sql`.

Validar con archivos ficticios y revisar autorización, formato, tamaño y paths de descarga; ver [Pruebas](../desarrollo/PRUEBAS.md).
