> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 8: validación de flujos con datos ficticios

Rama `fase-8-validacion-flujos`, creada desde `main` en `48463df`, con fase 7 integrada. La validación usa PHP 8.3.35, MariaDB 10.4.32 de XAMPP y Edge instalado. No se ha desplegado en Hostinger ni hecho commit/push.

## Entorno y archivos

- `tests/e2e-setup.php`: crea una base nueva `rrhh_e2e_<id>` por ejecución, importa schema/catálogos/parches y agrega cuentas y horarios ficticios. No importa `seed_admin.sql`, no lee `.env` y no usa la base habitual. El lector retira el BOM UTF-8 de SQL antes de enviarlo a PDO.
- Copia `src`, `config`, `public` y un `vendor` de producción de fase 7 a `scratch/phase8/<id>`, con backend y frontend separados. Las cargas se guardan exclusivamente en el backend de esa copia.
- `tests/e2e-router.php`: arranca la entrada real `public/index.php` mediante el servidor PHP CLI. No sustituye las comprobaciones de rewrite Apache de fases anteriores.
- `tests/e2e-state.php`: consulta únicamente tablas fijas de una fixture bajo scratch y una base de nombre E2E, para contrastar respuestas con estados persistidos. Acompañamiento crea su tabla al abrir su módulo; el lector admite que todavía no exista antes de ese momento.
- `tests/e2e.test.cjs`: ejecuta formularios en Edge, inspecciona persistencia MariaDB, capturas y SMTP. El buzón local acepta únicamente destinatarios `example.test`; ningún mensaje sale a Hostinger o a personas reales.
- `npm run test:e2e` ejecuta la suite. El cambio JSON se documenta aquí porque no admite comentarios; los archivos de código llevan comentarios explicativos.

Los servicios de prueba escuchan solo en `127.0.0.1`, en puertos libres, y se cierran al finalizar. No se reinician Apache/MySQL del responsable. Las bases ficticias y copias se conservan para inspección; no hay borrado automático de bases. No se deben publicar `fixture.json`, capturas, logs o resultados: `scratch` ya está ignorado por Git.

## Resultados del recorrido

**11 pruebas E2E aprobadas**, con 23 páginas autenticadas, tres correos capturados y verificaciones adicionales:

| Recorrido | Comprobación |
| --- | --- |
| Acceso y salida | ADMIN, SUPERVISOR y PRACTICANTE autentican, recorren módulos y cierran sesión; la URL privada exige login después de logout. |
| Pantallas | Diez de ADMIN, ocho de SUPERVISOR y cinco de PRACTICANTE renderizan con MariaDB, sin errores JavaScript detectados. Dashboard crea instancias reales de Chart.js. |
| Perfil y archivos | Guarda datos del perfil; carga PNG/PDF ficticios; entrega los mismos bytes mediante controladores autorizados; el anónimo debe iniciar sesión. |
| Permiso | Solicitud futura con recuperación válida de sábado queda PENDIENTE; supervisor aprueba desde el diálogo; se guardan APROBADO, dos excepciones y auditoría APROBAR_PERMISO. |
| Marcaje y revisión | Registra ENTRADA real; rechaza otra ENTRADA con CSRF vigente y una SALIDA con CSRF inválido. Revisa jornada de ayer sin colación, conservando nueve horas y cero extras aprobadas. |
| Alta y activación | ADMIN pre-registra un practicante completo con horario; correo local contiene activación; establece contraseña, inicia sesión y el token no se reutiliza. Otra cuenta no descarga el PDF ajeno. |
| Importación | Descarga plantillas CSV y XLSX; importa una fila CSV; repetirla no duplica el practicante. |
| Reporte | El detalle muestra al practicante y el estado CORREGIDA tras la revisión. |
| Acompañamiento | Cambio de puntaje por AJAX responde correctamente, persiste 6.5 y genera auditoría. |
| Catálogos | ADMIN crea una carrera; SUPERVISOR no puede crear otra enviando POST a la ruta administrativa. |
| Multirrol | Cuenta ADMIN/SUPERVISOR rechaza elegir PRACTICANTE no asignado y permite entrar como SUPERVISOR. |
| Recuperación | Genera correo local, define contraseña nueva, permite login con ella y rechaza reutilización del token consumido. |

La evidencia del recorrido final aprobado está bajo `scratch/phase8/145e720819a8/`: `results.json`, 23 capturas y `php.log`. La base es `rrhh_e2e_145e720819a8`. No se encontraron errores PHP en ese log. El dashboard se inspeccionó visualmente en la ejecución aprobada anterior y la suite comprueba sus instancias de gráficas, además de su HTTP 200.

## Corrección encontrada

El dashboard de ADMIN cargaba Chart.js y entregaba sus datos, pero no cargaba `admin-dashboard.js`, encargado de dibujar las gráficas. HTTP 200 y la presencia de canvas no detectaban este fallo. Agregué la carga del inicializador después de la biblioteca y de los datos en `src/Views/admin/dashboard.view.php`, con comentario explicativo. La prueba pasa únicamente si existen gráficas inicializadas. No cambié cálculos, SQL, estilos o reglas de negocio.

Las primeras ejecuciones también corrigieron escenarios de pruebas: recuperación no debe coincidir con turno ordinario, el sábado empieza a las 10:00, la auditoría se llama APROBAR_PERMISO y el selector requiere radio más botón Continuar. No se alteraron esas reglas para hacer pasar las pruebas.

## Reproducir

Primero generar una publicación actual con fase 7. Utilizar el `vendor` de su staging (sustituir `<id-publicacion>`). MariaDB debe estar iniciado y el usuario local debe poder crear una base de pruebas:

```powershell
$env:PHP_BINARY = 'C:\xampp\php 8.3.35\php.exe'
$env:E2E_FIXTURE = & $env:PHP_BINARY tests/e2e-setup.php scratch/releases/<id-publicacion>/staging/backend-rrhh/vendor
if ($LASTEXITCODE -ne 0) { throw 'Falló la preparación E2E' }
npm run test:e2e
npm run test:ui
```

Por defecto usa MariaDB `127.0.0.1:3306`, usuario local root y contraseña vacía de XAMPP. Si difiere, suministrar `E2E_DB_HOST`, `E2E_DB_PORT`, `E2E_DB_USER` y `E2E_DB_PASS` en el proceso, sin escribirlas en Git o pegar credenciales aquí. Solo admite host local. Las credenciales usadas se conservan en `fixture.json` ignorado; manejarlo como configuración privada de pruebas. No pasar credenciales de producción.

Crear una fixture nueva antes de cada ejecución completa: los escenarios cambian contraseñas, consumen tokens y registran datos. El cambio npm no añade paquetes; se mantienen las dependencias fijadas de fase 6.

El seed usa zona horaria de Chile, una jornada laborable anterior y horario de lunes a viernes más el día actual si es fin de semana. Elige otro día libre para recuperar. Estos horarios son únicamente fixtures ficticias; no cambian los horarios del portal.

## Validación complementaria y límites

- Pasaron las siete pruebas visuales de fase 6, con 25 pantallas/variantes y 50 capturas en escritorio/móvil.
- Pasaron 824 comprobaciones PHP: 199 MVC, 100 negocio, 20 configuración, 162 rutas y 343 retiro/API. Tres PHP de pruebas y la vista modificada pasan sintaxis; JavaScript pasa `node --check`. Composer valida con el aviso previo de licencia pendiente.
- Los scripts de prueba y sus datos no se incluyen en los ZIP de producción. Se debe regenerar el paquete tras esta corrección y, antes de publicar, nuevamente desde el commit aprobado.
- Generé `scratch/releases/20261014-150433-48463df4-60450c` con la corrección: 332 archivos íntegros y 531 comprobaciones de publicación aprobadas. Los once recorridos finales utilizaron sus dependencias de producción. Los ZIP siguen siendo locales e ignorados; no están publicados.

Esta validación cubre los recorridos de la tabla, no todas las combinaciones de formularios o condiciones productivas. No prueba envío SMTP TLS/autenticado de Hostinger, su File Manager, concurrencia de dos marcajes simultáneos, sesiones en HTTPS real, otros navegadores o carga masiva XLSX de entrada (sí su plantilla). La gestión de usuarios/supervisores, edición/desactivación de catálogos, otras decisiones de permisos, anulación de jornadas y correcciones individuales tienen cobertura parcial de fases anteriores, pero no un recorrido E2E nuevo para cada variante.

El servidor PHP CLI de Windows atiende secuencialmente: el rechazo de duplicados aquí no demuestra exclusión concurrente bajo Apache/InnoDB. Las pruebas previas de rollback con SQLite siguen siendo complementarias, no una prueba de todos los fallos transaccionales de MySQL.

No se consultaron ni modificaron registros reales, `.env` o uploads habituales. No se modificó el esquema del proyecto; los parches solo se importaron en bases nuevas ficticias. Las reglas futuras de compensación en dos días y recuperaciones distribuidas siguen pendientes, y esta suite verifica el comportamiento vigente.
