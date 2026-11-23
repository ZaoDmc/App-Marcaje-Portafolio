# Textos de interfaz editables

## Para una persona que quiere cambiar un texto

Busca el elemento en la tabla y cambia únicamente **Texto deseado**. Después pide aplicar esos cambios al proyecto. El Markdown es la especificación editorial: **guardar este archivo no modifica una pantalla automáticamente**.

Ejemplo: cambiar la fila Login → Botón de acceso de `Entrar` a `Acceder al portal` y solicitar su implementación. La persona o IA debe localizar el texto visible, conservar IDs y formularios, revisar pruebas dependientes y marcar la entrega en Estado actual.

## Catálogo inicial

Las filas siguientes parten de etiquetas presentes en el código al organizar la documentación el 21/10/2026. No son un inventario exhaustivo ni una sincronización automática. Una edición posterior expresa el texto deseado hasta que se implemente.

| Elemento | Texto deseado | Ubicación del texto en el código |
| --- | --- | --- |
| Login → Bienvenida | Bienvenido al portal de e-prácticas | `src/Views/auth/login.view.php` |
| Login → Título de formulario | Iniciar sesión | `src/Views/auth/login.view.php` |
| Login → Campo correo | Correo electrónico | `src/Views/auth/login.view.php` |
| Login → Permanencia | Mantener sesión iniciada | `src/Views/auth/login.view.php` |
| Login → Botón de acceso | Entrar | `src/Views/auth/login.view.php` |
| Login → Versión del software | Versión {APP_VERSION} | `src/Views/auth/login.view.php`; el número proviene de `config/version.php` |
| Menú de notas | Notas | `src/Views/layout/sidebar.php` |
| Menú administrativo | Formulario validación | `src/Views/layout/sidebar.php` |
| Formulario validación → Selección de institución | Selecciona la institución para completar su formulario de validación. | `src/Views/shared/formulario-validacion.view.php` |
| Formulario validación → Institución no disponible | {institución} (próximamente) | `src/Views/shared/formulario-validacion.view.php`; opción deshabilitada hasta implementar su plantilla |
| Formulario validación → Generación | Generar documento — {institución} | `src/Views/shared/formulario-validacion.view.php`; el nombre se obtiene del catálogo `instituciones` |
| Notas → Consulta propia | Mis notas | `src/Views/components/notas-nav.php` |
| Notas → Evaluación de equipo | Evaluar mi área | `src/Views/components/notas-nav.php` y dashboard del practicante |
| Permisos → Ausencia de todo el día | Jornada completa | `src/Views/practicante/permisos.view.php` |
| Recuperaciones → Corte semanal | Solo sábados de 10:00 a 20:00. Solicita antes del viernes previo a las 12:00; después del corte elige el sábado siguiente. | Formularios inicial y adicional de recuperación; validación del servidor en `RecuperacionesService.php` |
| Recuperaciones → Rechazo por plazo | Solicitud rechazada automáticamente: debes solicitar la recuperación antes del viernes a las 12:00. Puedes elegir el sábado de la semana siguiente. | `src/Services/RecuperacionesService.php`; el intento se audita sin crear reserva |
| Permisos superiores → Categorías | Ausencias y recuperación / Tiempo excepcional / Compensación | `src/Views/shared/permisos.view.php` |
| Permisos → Cambio diario de descanso | Cambio de colación | `src/Views/components/cambio-colacion.php` y categoría de superiores |
| Colación → Enviar | Solicitar cambio de colación | `src/Views/components/cambio-colacion.php`; solo fecha actual determinada por servidor |
| Colación → Revisión | Resolver solicitud | `src/Views/components/cambio-colacion.php`; decisión y motivo obligatorios, incluso para aprobación tardía |
| Permisos superiores → Abrir expediente excepcional | Ver detalle | `src/Views/shared/permisos-tiempo.view.php`; abre diálogo con la solicitud o canje seleccionado |
| Permisos → Fecha de ausencia | Día de ausencia | `src/Views/practicante/permisos.view.php`; admite fechas pasadas, actuales o futuras dentro de la práctica |
| Permisos → Ayuda de solicitud | Solicita una ausencia pasada, de hoy o futura, de jornada completa o parcial. Puedes recuperar antes o después del día de ausencia; solicita los tramos de recuperación antes de trabajarlos y espera la aprobación del supervisor. | `src/Views/practicante/permisos.view.php` |
| Permisos → Ausencia parcial | Solo un tramo | `src/Views/practicante/permisos.view.php` |
| Compensación → Día sin turno | No hay jornada laboral vigente para la fecha de compensación; revisa el horario y los feriados con tu supervisor. | `src/Services/TiempoExcepcionalService.php` |
| Compensación → Crédito nuevo aprobado | Canjear saldo aprobado / Confirmar canje | `src/Views/components/tiempo-excepcional.php`; no requiere segunda aprobación en política 2 |
| Compensación → Plazo de créditos nuevos | Los créditos nuevos vencen al terminar el turno del quinto día hábil posterior a la aprobación, según tu horario y excluyendo feriados. El saldo vencido deja de estar disponible; se conserva su historial. Los expedientes anteriores mantienen su política original. | `src/Views/components/tiempo-excepcional.php` |
| Compensación → Tramo fuera del turno | El horario vigente del {fecha} es {entrada}–{salida}. Para salir antes, el tramo debe terminar a las {salida} y quedar dentro de esa jornada. | `src/Services/TiempoExcepcionalService.php`; para entrada posterior indica comenzar a las {entrada} |
| Compensación → Cruce con colación | El tramo de compensación no puede incluir la colación vigente de {inicio_colacion} a {fin_colacion}. | `src/Services/TiempoExcepcionalService.php` |
| Designar líder → Buscador | Buscar integrante por nombre, correo o RUT | `src/Views/admin/notas.view.php` |
| Evaluaciones ADMIN → Selector | Líder / práctica | `src/Views/shared/notas-evaluaciones.view.php` |

Para añadir una fila, indicar **pantalla → elemento**, texto completo y su ubicación si se conoce. Si no se conoce, escribir «Localizar durante implementación»; no inventar un archivo. Mantener tildes, tono claro y etiquetas que describan la acción.

## Qué se edita en cada sitio

| Lo que quieres cambiar | Dónde se decide | Cómo se aplica |
| --- | --- | --- |
| Etiquetas, ayudas, botones y mensajes fijos | Este catálogo | Vista o servicio que emite el mensaje |
| Fuente, tamaños, tarjetas y colores | [Branding](BRANDING.md) | CSS/componentes y compilación cuando corresponda |
| Nombre de un área o feriado | Catálogos de ADMIN | Base de datos, mediante el portal |
| Criterios y cantidad de filas de evaluación | Notas → Criterios | Nueva versión de rúbrica; no alterar versiones anteriores |
| Motivo de permiso, observación o nota personal | Formulario autorizado del módulo | Registro funcional con permisos y auditoría |
| Regla de cálculo o permiso de usuario | Documento del módulo y decisión del responsable | Servicio, pruebas y migración si corresponde |

## Límites para implementar

Cambiar una etiqueta no cambia su significado funcional: «Revisada» no puede aprobar horas, «Publicar» no puede omitir confirmación. No renombrar códigos persistidos como REVISADA, IDs, nombres de campos, rutas o acciones POST al cambiar solo la presentación.

Mantener escape de datos dinámicos y marcadores de variables. No guardar correos, nombres reales, credenciales ni textos privados como ejemplos de esta tabla. Si un texto repetido cambia, buscar sus usos compartidos para que dashboard, menú y formulario no queden divergentes.
