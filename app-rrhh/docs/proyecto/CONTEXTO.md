# Contexto vigente del proyecto

Actualizado: **11 de noviembre de 2026**. Leer junto a [Estado actual](ESTADO_ACTUAL.md).

## Propósito

ePrácticas RRHH gestiona practicantes, sus prácticas, horarios individuales, áreas, asistencia, permisos, recuperación de horas y evaluaciones semanales. Permite a supervisores revisar y corregir marcajes con trazabilidad, y a cada practicante consultar su avance y notas publicadas.

El objetivo operativo es reconocer el trabajo y las recuperaciones autorizadas sin convertir cualquier permanencia adicional en horas extra nuevas. Las horas previas y extras históricas reconocidas deben conservarse. Las reglas detalladas pertenecen a [Asistencia](../modulos/ASISTENCIA_Y_HORARIOS.md), [Permisos](../modulos/PERMISOS_Y_RECUPERACIONES.md) y [Tiempo excepcional](../modulos/TIEMPO_EXCEPCIONAL.md).

## Personas y responsabilidades

Existen roles de software ADMIN, SUPERVISOR y PRACTICANTE. Ser líder de un área es una responsabilidad vigente asociada a una práctica, no otro rol global. La jerarquía del cliente —jefe, RRHH/coordinadores, líderes y trabajadores— no concede permisos automáticamente por su nombre. Consultar [Roles](ROLES_Y_PERMISOS.md).

## Plataforma actual

La versión de la entrega de código preparada es **2.1.4**. Los tramos de recuperación nuevos se restringen a sábados con corte del viernes; los planes anteriores conservan su política. Mantiene permisos por categorías y los módulos de las entregas anteriores. Incluye cambio diario de colación con aprobación posterior y ajuste temporal. La retirada de nuevos créditos excepcionales sigue pendiente; la regla de jornada laboral se documenta en la entrega 2.1.3 y su integración se recupera en 2.1.4. La numeración explícita comenzó en 1.1.1 por acuerdo del 27/10/2026; ese valor se conserva como antecedente. La fuente es `config/version.php`; login y barra lateral usan APP_VERSION. La entrega integrada reúne Formulario validación (incorporado en 1.2.0 y corregido en 1.4.1), Reseñas (1.3.0) y comprobantes de marcaje por correo (1.4.0). Consultar [regla de incrementos](../desarrollo/VERSIONADO.md) y [registro de producción](../operacion/PRODUCCION.md); la versión local no acredita un despliegue.

| Parte | Tecnología o condición |
| --- | --- |
| Backend | PHP, PDO, Composer, PHPMailer y phpdotenv |
| Datos | MySQL/MariaDB; parches recientes probados en MariaDB |
| Presentación | MVC, Tailwind CSS 3.4.17, DaisyUI 4.12.24 con prefijo `dui-`, Bootstrap Icons local |
| Navegador | JavaScript puntual para confirmaciones, temas, formularios y filtros |
| Desarrollo | XAMPP; entorno personal configurado mediante `.env` privado |
| Hosting | Hostinger; backend privado y entrada pública separados |
| Publicación | Checkout privado `app-rrhh` de `prod`, actualizado por SSH, con enlace público a `public/`; sin GitHub Actions; generadores ZIP conservados |

El ensayo web en Hostinger del 23/10/2026 confirmó PHP 8.3.35 con SAPI litespeed y MariaDB 11.8.9. La revisión de seguridad de PHP del 21/10/2026 requiere seguimiento del proveedor; ver [Seguridad](../operacion/SEGURIDAD.md). `composer.json` expresa compatibilidad, no acredita que una versión esté parcheada.

## Principios que se conservan

- Cuenta individual, contraseña con hash, activación y selección de rol después del login. Sin autoregistro público libre.
- Autorización del servidor, control de autosupervisión, protección CSRF y auditoría de cambios.
- Historial y bajas lógicas: conservar marcas, asignaciones, notas y sus versiones.
- Rutas explícitas y configuración según entorno. No reintroducir puentes PHP ni URLs rígidas de un equipo.
- Separar trabajo ordinario, recuperación validada, crédito excepcional y extras históricas; evitar duplicar acumulados.
- Respetar la privacidad de archivos, borradores, motivos internos y datos propios.

## Dónde está la historia

El contexto original de más de dos mil líneas se conserva en [Contexto histórico](../historial/referencias/CONTEXTO_PROYECTO.md). Contiene el diseño inicial y actualizaciones de diferentes fechas; ya no es la lectura obligatoria para cada tarea. El índice actual y los módulos resuelven las decisiones posteriores sin borrar esa procedencia.
