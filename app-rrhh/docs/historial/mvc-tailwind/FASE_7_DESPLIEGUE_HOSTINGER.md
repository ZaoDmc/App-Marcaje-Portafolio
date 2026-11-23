> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Fase 7: preparación del despliegue coordinado en Hostinger

Rama `fase-7-despliegue-hostinger`, creada desde `main` en `2356658`, merge del PR #6. Esta fase prepara y prueba archivos locales; no inicia una publicación, un commit o un push.

## Qué cambió

- Agregué `scripts/build-release.php`, `release-lib.php` y `verify-release.php` con comentarios explicativos. El generador trabaja solo por CLI, no carga `.env` ni el bootstrap y no consulta MySQL. Nunca modifica el `vendor` de desarrollo.
- Genera dos ZIP, sin carpeta envolvente, un `manifest.json` con SHA-256 de cada archivo y ZIP, y una copia de preparación bajo `scratch/releases/<id>/staging`. Cada ejecución usa un destino nuevo; los intentos interrumpidos quedan allí sin considerarse una publicación válida.
- Copia solo `config`, `src`, manifiestos Composer, recursos públicos y dependencias de producción instaladas desde el lock. Excluye el entorno, todos los uploads, datos SQL, Git, documentación, pruebas, herramientas npm y scripts de desarrollo. No ejecuta scripts o plugins Composer.
- Comprueba controladores del catálogo, requisitos PHP/extensiones, archivos requeridos, coincidencia de las dos mitades públicas y dependencias idénticas a `packages` del lock, sin `packages-dev`. Verifica hashes, faltantes, archivos añadidos, entradas duplicadas y recorridos de directorios.
- Retiré 14 copias antiguas de `assets/`, inventariadas en `FASE_7_ASSETS_RETIRADOS.csv`: 13 idénticas a las públicas y el antiguo `app.css`. La fuente activa sigue siendo `public/assets/`; los logos y recursos activos no cambiaron. Los hashes documentan eliminaciones, donde no es posible conservar comentarios en el archivo retirado.

El backend lleva `public/` para conservar la estructura que define `ASSETS_PATH`; el ZIP público contiene los mismos archivos para servirlos desde Hostinger. Esta duplicación entre destinos de publicación es deliberada. En el repositorio existe una única fuente de recursos.

## Generar y verificar

Desde la raíz del proyecto, con PHP 8.3.35 y Composer disponible:

```powershell
npm ci --ignore-scripts
npm run build
& 'C:\xampp\php 8.3.35\php.exe' scripts/build-release.php scratch/phase1/composer.phar
```

La ruta de Composer es un argumento: se puede utilizar otro `composer.phar` instalado, sin cambiar el código. El generador puede necesitar Internet para las siete dependencias de producción del lock. No utiliza `composer update` ni ignora requisitos de plataforma.

El comando imprime la carpeta generada. Sustituir `<id>` por ese nombre:

```powershell
& 'C:\xampp\php 8.3.35\php.exe' scripts/verify-release.php scratch/releases/<id>
& 'C:\xampp\php 8.3.35\php.exe' tests/release.php scratch/releases/<id>
```

`manifest.json` registra commit de origen, versión PHP y si había cambios sin commit. El paquete de esta sesión corresponde al árbol de trabajo de fase 7; antes de una publicación definitiva, generar uno nuevo desde el commit aprobado. Los hashes verifican integridad y correspondencia con el manifiesto, no acreditan por sí solos la identidad del autor. Conservar el manifiesto local de confianza.

No subir `staging`, `manifest.json` ni las pruebas al directorio público. `scratch` y los ZIP están ignorados por Git. Hostinger recibe código PHP/dependencias y recursos compilados, sin necesitar Node.

## Destinos y archivos persistentes

La estructura esperada, relativa a la cuenta del hosting, es:

```text
cuenta/
├── backend-rrhh/                 ← extraer contenido de backend-rrhh.zip
│   ├── .env                      ← conservar el productivo
│   ├── uploads/                  ← conservar documentos y fotos
│   ├── uploads.751/              ← conservar; origen aún sin confirmar
│   ├── config/, src/, vendor/
│   ├── composer.json, composer.lock
│   ├── .htaccess                 ← bloqueo HTTP del backend
│   └── public/                   ← estructura privada del paquete
└── public_html/
    └── software-rrh/             ← carpeta existente del subdominio
        ├── index.php, backend.php
        ├── .htaccess, assets/
        └── uploads existentes    ← conservar si los hay
```

El ZIP se llama `software-rrhh.zip`; su nombre no obliga a renombrar la carpeta real, que en el contexto se mencionó tanto como `software-rrh` como `software-rrhh`. Confirmar el nombre en el File Manager y extraer dentro de esa carpeta. No crear una carpeta anidada adicional.

`backend.php` descubre `backend-rrhh` mediante la estructura relativa, antes de leer `.env`. La publicación no requiere agregar variables al `.env` confirmado. El subdominio debe tener como raíz documental la carpeta pública existente. Si las rutas reales difieren, detener la publicación y ajustar la configuración revisada antes de seguir.

Para un acceso mediante subcarpeta URL, en lugar de raíz de subdominio, se probó `SetEnv BASE_PATH /portal` en Apache; el prefijo debe corresponder al acceso real. Es una configuración del servidor público, no un motivo para modificar secretos. Este caso no se agrega automáticamente a la configuración productiva.

Los controladores utilizan `backend-rrhh/uploads/practicantes/{id_practicante}/perfil` y `/documentos`, con autorización para servirlos. El código no usa `uploads.751`. No se fusiona, renombra ni elimina ninguna de estas carpetas: falta contrastar las rutas guardadas en la base productiva y cualquier modificación del código desplegado. Tampoco se traslada automáticamente el upload público al privado.

## Procedimiento manual de publicación

1. **Antes de intervenir:** revisar el paquete aprobado, su manifiesto y las pruebas. Confirmar PHP 8.3, extensiones requeridas, raíz documental del subdominio, ubicación del backend y capacidad de escritura del upload privado. No usar permisos 777 como solución automática.
2. **Respaldar:** guardar juntos el backend y frontend actuales, los `.htaccess`, el `.env`, todos los uploads privados/públicos y una exportación coherente de la base de datos. Los respaldos deben quedar fuera del acceso público. Conservar registro de rutas, fecha y versión; no subir respaldos al repositorio.
3. **Evitar una versión mezclada:** programar una ventana sin operaciones y bloquear temporalmente el acceso en el hosting mediante el mecanismo disponible. Comprobar que ya no se reciben solicitudes antes de reemplazar código. Mantener el bloqueo hasta terminar ambas partes y la revisión.
4. **Backend:** subir `backend-rrhh.zip` a una ubicación privada y extraer su contenido dentro de `backend-rrhh`. Los ZIP no incluyen `.env` ni uploads y no deben reemplazarlos. Sustituir el `vendor` de código por el del paquete completo, evitando mezclar bibliotecas anteriores con el nuevo autoloader. Guardar el anterior para reversión.
5. **Frontend:** extraer `software-rrhh.zip` dentro de la carpeta pública existente. Reemplazar `index.php`, `backend.php`, recursos y la regla pública revisada. Conservar uploads y reglas particulares que se hayan identificado como necesarias antes de intervenir.
6. **Reglas Apache:** respaldar y comparar el `.htaccess` productivo antes de sustituirlo. El viejo que permitía cualquier archivo existente no basta: la nueva entrada usa catálogo, protege privados y admite formatos públicos concretos. El paquete público trae `public/.htaccess`; el privado trae `Require all denied`. No copiar el `.htaccess` de la raíz local de XAMPP al frontend de Hostinger. No conservar a ciegas una regla antigua que anule la protección nueva.
7. **Retirar restos identificados:** extraer encima no borra puentes. Con respaldo, retirar únicamente las rutas documentadas en `FASE_5_ARCHIVOS_RETIRADOS.csv` del backend anterior y las copias `assets/` del inventario de fase 7. No eliminar `public/assets/`, uploads ni archivos no conciliados. No borrar masivamente la carpeta backend.
8. **Comprobar:** confirmar que los archivos corresponden al paquete; verificar login, logo, CSS, iconos, 404 y protección de `.env`, `vendor`, `src`, uploads directos y `backend.php`. Revisar errores del servidor sin exponerlos al usuario. Habilitar acceso controlado para validar por rol, formularios, modales, filtros, foto y descarga con cuentas/documentos autorizados; después habilitar el acceso general. Recargar sin caché.
9. **Cerrar:** quitar ZIP temporales del servidor, mantener respaldos privados y anotar versión, pruebas y resultado. Si hay caché PHP/servidor, renovarla mediante el mecanismo disponible en el hosting antes de concluir.

No hay migraciones SQL nuevas en fase 7. No ejecutar seeds ni sobrescribir la base con datos locales. Las operaciones reales de marcaje, permisos y correo requieren una prueba autorizada y controlada; no forman parte de las solicitudes automáticas anónimas.

## Reversión

Ante errores, mantener o volver a activar el bloqueo de acceso. Restaurar **juntos** backend, vendor, entrada pública, recursos y reglas Apache de la versión anterior. Conservar el `.env` productivo y uploads; no regresar documentos a una copia antigua si hubo cargas nuevas. La fase no modifica el esquema SQL: restaurar la base de datos no es el paso automático de reversión del código y podría perder operaciones nuevas. Validar login y protección antes de reabrir. Registrar el fallo y dejar la versión nueva para diagnóstico fuera del área pública.

## Validación realizada y pendientes

- Generación con PHP 8.3.35 y siete paquetes de producción; `check-platform-reqs --no-dev` correcto. Autoloader con PHPMailer/Dotenv, sin PHPUnit.
- 332 archivos en ambos ZIP verificados; 531 comprobaciones de publicación, incluyendo contenido contra fuentes, retiro de assets, extracción y descubrimiento del backend, y cinco casos adversos.
- Apache temporal con los **ZIP extraídos**, backend privado y frontend separados: 220 comprobaciones HTTP en raíz de subdominio simulada y subcarpeta `/portal`, más acceso directo al backend denegado con HTTP 403. El proceso temporal se cerró al finalizar.
- 110 comprobaciones HTTP en `localhost/asistencia` después de retirar las copias de assets, 343 de retiro/API y 162 de rutas. No se reiniciaron los servicios del responsable.
- Pasaron también 199 comprobaciones MVC, 100 de negocio y 20 de configuración. Las comprobaciones PHP anteriores suman 824, además de las 531 específicas del paquete. El paquete final reproduce los hashes por archivo del que se probó por HTTP.
- No se enviaron correos ni se autenticaron usuarios reales o modificaron registros. No se descargaron ni examinaron documentos personales.

No hay acceso al File Manager o a la base productiva en esta sesión. La conciliación de uploads, extensiones reales del hosting, reglas particulares, respaldo y pruebas autenticadas en Hostinger continúan como condiciones previas a publicar. El paquete está preparado, **no desplegado**. Las reglas futuras de compensaciones y recuperaciones distribuidas siguen pendientes y no cambian en esta fase.
