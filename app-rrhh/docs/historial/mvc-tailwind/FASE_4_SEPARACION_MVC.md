> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 4: separación de controladores y vistas

Rama `fase-4-separacion-mvc`, creada desde `main` limpio en `4f04ed3`, después del merge y sincronización comunicados por el responsable.

## Objetivo y numeración

Esta etapa corresponde a las fases 4/5 de arquitectura del inventario de fase 0. El archivo `plan_implementacion_mvc_tailwind.md` contiene un plan antiguo de cuatro fases con otra numeración; no se utiliza para interpretar los números de esta secuencia. La migración visual a Tailwind corresponde a la fase 6 del inventario vigente.

El objetivo es que el controlador autorice, procese el formulario y prepare los datos antes de que la vista emita HTML. Los servicios y modelos conservan las reglas y consultas, pero dejan de contener las páginas completas. Se conserva PHP procedural, sin introducir un framework ni cambiar la identidad visual.

## Cambios y archivos

| Capa | Cambios |
| --- | --- |
| `src/Controllers/Shared/` | Preparación compartida de asistencia, permisos, reportes, pre-registro, carga masiva, practicantes/horarios y catálogos. Se trasladaron también los manejadores POST de asistencia y catálogos. |
| `src/Controllers/Admin/` y `Supervisor/` | Parámetros explícitos por rol y ejecución del controlador compartido antes de cargar la vista. Se reemplazaron referencias a puentes `includes/` por dependencias internas directas. |
| `src/Views/shared/` | Siete plantillas trasladadas desde servicios/modelos, conservando formularios, acciones, IDs, CSRF, tablas, modales y estilos. |
| Vistas por rol | Componen head, sidebar, plantilla compartida y foot. No llaman funciones que procesen formularios o generen páginas desde servicios. |
| `src/Views/components/` | Campos de horario, selectores de pre-registro y campos/etiquetas de MisDatos, trasladados desde el modelo, servicio o controlador correspondiente. |
| `LayoutContext.php` y sidebar | El controlador prepara el contador de permisos antes del HTML; el sidebar recibe el valor sin consultar la BBDD. Las pantallas privadas restantes también cargan este contexto. |
| Servicios y modelos afectados | Se retiraron únicamente las funciones de presentación y los dos manejadores HTTP trasladados. Las funciones de negocio permanecen en sus ubicaciones. |

Los desgloses de asistencia, las opciones relacionadas de catálogos y los acumulados del detalle de reportes se preparan antes de renderizar. Los errores de carga de desgloses/opciones se presentan como mensajes, evitando consultas tardías desde la plantilla.

Se conserva la descarga de plantillas Excel/CSV antes del layout. Los flujos de horarios que redirigen después de guardar ahora se ejecutan antes de enviar HTML, conservando sus destinos y parámetros. Las dependencias de los archivos puente todavía existen para compatibilidad; no se eliminan físicamente en esta fase.

Cada traslado o integración contiene comentarios explicativos en español. `composer.json` incorpora `test:mvc`; el JSON se explica aquí porque no admite comentarios.

## Validación

```powershell
& 'C:\xampp\php 8.3.35\php.exe' tests/mvc.php
& 'C:\xampp\php 8.3.35\php.exe' tests/business.php
& 'C:\xampp\php 8.3.35\php.exe' tests/configuration.php
& 'C:\xampp\php 8.3.35\php.exe' tests/routing.php
```

Las 199 comprobaciones MVC verifican ausencia de HTML intercalado en servicios/modelos, renderizado de las 13 entradas por rol afectadas, GET y POST con CSRF inválido, layout completo, autorización anónima antes del HTML, lecturas antes del render, mensajes de error, modales de permisos pendientes, acumulados del detalle de reportes y componentes con selección/escape de texto. Se utilizan procesos PHP aislados y proveedores de datos ficticios; no se conecta a MySQL, envía correo ni guarda registros reales.

Se conservan las 100 comprobaciones de negocio, 20 de configuración y 162 de rutas. La sintaxis de los PHP afectados y Composer se validan sin instalar dependencias.

Antes de publicar, probar en `http://localhost/asistencia/` con datos locales controlados:

1. Revisar asistencia, permisos y reportes desde ADMIN y SUPERVISOR; comprobar formularios, filtros y detalle.
2. Revisar pre-registro, descarga de plantillas y carga masiva con archivos sintéticos.
3. Guardar horario, cambiar área y registrar una excepción; comprobar redirección y mensaje posterior.
4. Abrir MisDatos y comprobar selectores, etiquetas y horario.
5. Comprobar que el contador de permisos y el menú activo siguen funcionando.

Las pruebas CLI no validan interacción JavaScript, aspecto visual en navegador, escrituras sobre MySQL, envío SMTP ni concurrencia. No se ha desplegado a Hostinger.

## Nuevo contexto funcional, pendiente de implementación

El responsable comunicó después de fase 3 que el cliente quiere conservar la fecha de término pactada y sustituir extras acumulables por compensaciones de jornada dentro de los dos días siguientes. También necesita repartir la recuperación de una ausencia entre varios días, incluyendo tiempo trabajado durante la colación cuando corresponda.

Este contexto queda registrado, sin cambiar todavía el cálculo, la base de datos ni las pantallas de negocio. Antes de diseñar esa modificación hay que precisar días hábiles/corridos, ajustes de entrada/salida, autorización, tiempo no compensado, fines de semana y compensaciones cerca del término de práctica. La regla antigua de extras continúa ejecutándose hasta implementar y validar su reemplazo explícito.

## Siguiente etapa

Fase 5: terminar de independizar las entradas del legado y retirar los archivos puente mediante el catálogo explícito de rutas, incluyendo el endpoint de horario diario. Mantener intactos uploads y configuración productiva. Después, migrar las vistas pendientes a Tailwind con validación por pantalla y preparar el despliegue coordinado del backend y directorio público.
