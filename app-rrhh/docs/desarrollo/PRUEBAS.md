# Elegir y ejecutar pruebas

## Alcance de las comprobaciones

Las pruebas apropiadas dependen del cambio. Una reorganización de Markdown no requiere registrar marcajes ni enviar correos. Un cambio funcional necesita comprobar permisos, errores y persistencia además de la pantalla. No reutilizar datos reales como fixture.

Comandos desde la raíz, con PHP compatible y extensiones del proyecto. Cuando `php` apunta a un binario antiguo, utilizar el ejecutable correcto del equipo, sin quemar esa ruta en código versionado.

| Cambio | Comprobaciones pertinentes |
| --- | --- |
| Configuración y entorno | `php tests/configuration.php` o `composer test:config` |
| Catálogo/rutas/inventarios | `php tests/routing.php` y, si cambia el contrato antiguo, `php tests/legacy-removal.php` |
| Controladores y vistas | `php tests/mvc.php` |
| Cálculo y autorización operativa | `php tests/business.php`; fixtures aisladas SQLite |
| Migración de canje autónomo | `php tests/canje-migracion.php`; crea y retira una base MariaDB local aleatoria, sin cargar `.env` ni tocar datos reales |
| Migración de recuperación sabatina | `php tests/recuperaciones-sabados-migracion.php`; crea y retira una base MariaDB local ficticia, prueba repetición de 015 y conserva modalidades/créditos anteriores |
| Migración de cambio de colación | `php tests/colacion-migracion.php`; base MariaDB local ficticia, repetición de 016 con datos y protección de historial por FK |
| Liderazgo | `php tests/liderazgos.php`; para bloqueos reales, fixture MariaDB documentada |
| Generación de formulario DOCX | `php -d extension=zip tests/formulario-validacion.php`; usa paquetes OOXML sintéticos y no documentos personales |
| Reglas y publicación de notas | `php tests/notas-semanales.php`; crea una base temporal local |
| JS del buscador de líder | `node --test tests/notas-lideres-ui.test.cjs` |
| UI general | `npm run test:ui` según los requisitos de la fixture |
| Formularios autenticados | E2E con MariaDB, servidor y cuentas ficticias |
| Paquete publicado | `php scripts/verify-release.php carpeta` y `php tests/release.php carpeta` |

## E2E aislado

`tests/e2e-setup.php` crea una base `rrhh_e2e_*` y una copia bajo `scratch/phase8`, utilizando credenciales E2E del proceso y datos ficticios; no carga el `.env` habitual. Necesita MariaDB local disponible y las dependencias que copia. Revisar sus argumentos antes de ejecutarlo.

Definir `E2E_FIXTURE` con el JSON generado y, si hace falta, `PHP_BINARY` con el ejecutable del equipo. Los JSON contienen credenciales ficticias y no deben pegarse completos en logs públicos. Utilizar fixture nueva por recorrido que modifique datos.

Para Notas, preparar la fixture mediante `tests/notas-e2e-setup.php` y usar `--publicacion` cuando el recorrido necesite las cuentas de integrante/ajeno/reemplazo. Ejecutar la suite correspondiente: `tests/notas-e2e.test.cjs` o `tests/notas-publicacion-e2e.test.cjs`. La segunda verifica publicación, privacidad, correcciones e integración; una preparación incompleta causa fallos de login que no son fallos de negocio.

Las pruebas de navegador usan Playwright y Edge por defecto en estos recorridos. Comprobar ejecutable y dependencias antes de instalar o descargar herramientas. Los correos de prueba se capturan localmente; no enviar a personas reales.

## Evidencia y límites

Registrar comando, resultado y alcance; distinguir casos no ejecutados o bloqueados. No repetir números históricos como si fueran de la versión actual. `scratch` guarda artefactos y queda excluido del despliegue/Git. SQLite no demuestra bloqueos de InnoDB; Edge no certifica Opera GX; un test local no confirma producción.

Si solo se mueven documentos, verificar enlaces, conservación de inventarios y tests que los consumen. Las guías anteriores con casos manuales se conservan como [referencias históricas](../historial/README.md); no ejecutar acciones antiguas de extras o SQL destructivo por seguirlas literalmente.
