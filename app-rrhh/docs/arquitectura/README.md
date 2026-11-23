# Arquitectura y flujo de una petición

## Estructura productiva y propuesta de publicación

El responsable incorporó el 22/10/2026 [el árbol actual de Hostinger](Estructura_Producción.md) y [su propuesta](Estructura_Propuesta.md). Se conservan como referencia aportada, sin tratarlos como una lectura directa del servidor.

La [revisión y plan de migración a SSH/Git](MIGRACION_SSH_GIT_RRHH.md) propone conservar la raíz del repositorio en `app-rrhh` y enlazar `public_html/rrhh` con `app-rrhh/public`. El backend anidado de la propuesta inicial exige adaptaciones que no son necesarias para adoptar Git. La fase siguiente es un inventario de solo lectura; no hay migración productiva ejecutada.

## Recorrido HTTP

```text
Navegador → reglas .htaccess → public/index.php
          → config/routes.php → controlador autorizado
          → servicios / modelos → vista → layout / componentes
```

`public/backend.php` localiza el backend privado. `src/bootstrap.php` prepara configuración, conexión y helpers. El catálogo resuelve destinos explícitos: no se debe incluir un archivo arbitrario construido desde la URL. Las rutas antiguas se conservan como contrato, aunque sus archivos puente ya no existan.

## Responsabilidad de cada carpeta

| Carpeta | Responsabilidad | Evitar |
| --- | --- | --- |
| `config/` | Entorno, aplicación, base, correo y rutas | Credenciales versionadas, URLs de un equipo |
| `src/Controllers/` | HTTP, rol, entrada, preparación, respuesta y redirección | Reglas críticas nuevas escondidas en HTML |
| `src/Controllers/Shared/` | Flujos reutilizados por ADMIN y SUPERVISOR o evaluación compartida | Copias divergentes del mismo flujo por rol |
| `src/Services/` | Negocio, autorizaciones específicas, transacciones y auditoría | Confiar en lo enviado por un botón |
| `src/Models/` | Consultas y persistencia reutilizable | Renderizar pantallas |
| `src/Helpers/` | Utilidades y soporte XLSX | Introducir permisos implícitos |
| `src/Views/` | Mostrar datos preparados, con escape | Consultas nuevas o cambios de estado al renderizar |
| `src/Views/components/` | Campos, avisos y partes compartidas | Duplicar etiquetas o estilos para cada pantalla |
| `src/Views/layout/` | Head, sidebar, header y foot | Preparar operaciones de negocio desde el menú |
| `resources/css/` | Fuentes compilables de estilos | Editar únicamente el CSS minificado |
| `public/assets/` | Recursos activos publicados | Mantener otra copia en raíz |
| `database/` | Schema, seeds y migraciones | Recrear tablas de producción desde una guía antigua |
| `tests/` | Comprobaciones y fixtures | Mezclar cuentas ficticias con producción |
| `scripts/` | Preparar/verificar publicaciones y diagnósticos | Incorporar secretos en paquetes |

La separación describe el patrón a mantener; servicios actuales también contienen consultas PDO y algunas utilidades siguen siendo funciones. No se propone una reescritura arquitectónica en esta organización documental.

## Convenciones

- Rutas de código en la documentación: relativas a la raíz. Rutas web: las definidas en `config/routes.php`.
- URLs generadas mediante los helpers `url()` y `asset()` y la configuración del entorno.
- Autorizar antes de enviar HTML; preparar variables comunes mediante `src/Controllers/Shared/LayoutContext.php` cuando corresponda.
- GET consulta; las acciones de escritura del proyecto usan POST con CSRF y validación de entrada. Conservar redirección después de guardar donde existe.
- Conflictos de revisión, estados, firmas de marcas y auditoría se resuelven en el servicio y su transacción, no con una clase CSS.
- Escapar texto dinámico en HTML con `e()` y validar antes de construir SQL o rutas de archivos.

Para una modificación concreta, abrir el [módulo correspondiente](../modulos/README.md), seguir el destino en el catálogo y revisar sus pruebas. El historial MVC explica la migración, no es una lista de tareas pendientes: [entregas anteriores](../historial/README.md).
