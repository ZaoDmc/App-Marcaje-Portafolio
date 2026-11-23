> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# 🗺️ Plan Maestro de Refactorización y Migración

> Documento histórico: su numeración de cuatro fases antecede al inventario actual. Para la secuencia vigente, consultar `FASE_0_INVENTARIO.md` y los documentos `FASE_1_*` a `FASE_5_*`. La fase 4 actual separa controladores y vistas, la 5 retira puentes y Tailwind corresponde a la fase 6 del inventario.

Este plan está dividido en **4 fases secuenciales**. La regla de oro durante la ejecución será: **hacer una fase, probar que todo siga funcionando, y recién pasar a la siguiente**.

## Fase 1: La Nueva Fundación (Estructura de Carpetas)

**Objetivo:** Crear las carpetas de la nueva arquitectura y establecer un "Front Controller" (un único punto de entrada), imitando la seguridad que ya se tiene en producción.

**El mapa de carpetas:**
```text
asistencia/
├── public/                 <-- NUEVO: Todo lo público va aquí
│   ├── index.php           <-- Front Controller (recibe TODAS las peticiones)
│   ├── .htaccess
│   └── assets/             <-- Movemos CSS, JS, IMG aquí
│
├── src/                    <-- NUEVO: El corazón de tu app (Oculto al navegador)
│   ├── Controllers/        (Lógica de las páginas)
│   ├── Models/             (Consultas a la base de datos)
│   ├── Services/           (Reglas de negocio, ej. gestionar asistencia)
│   ├── Helpers/            (Funciones genéricas, ej. formato de horas)
│   └── Views/              (Archivos HTML puros)
│
├── config/                 (Se queda igual)
├── database/               (Se queda igual)
├── vendor/                 (Se queda igual)
└── uploads/                (Se queda igual)
```

**Instrucción para la IA en esta fase:**
Crear la estructura de carpetas vacías en `src/`. Mover la carpeta `assets/` dentro de `public/`. Preparar el entorno para que las rutas no se rompan.

---

## Fase 2: Clasificación del Cajón Desastre (`includes/`)

**Objetivo:** Agarrar todo lo que hoy está en la carpeta `includes/` y moverlo a su lugar correcto en `src/`, separando por tipo. **No cambiaremos cómo funciona el código, solo dónde vive.**

**Mapeo de archivos (Ejemplo gráfico):**
```text
[ANTES: includes/]                   [DESPUÉS: src/]
auth.php                     --->    Services/AuthService.php
permisos_gestion.php         --->    Services/PermisosService.php
mailer.php                   --->    Services/MailService.php

helpers.php                  --->    Helpers/FormatHelper.php (Solo lo de formato)
                                     Helpers/SecurityHelper.php (Solo CSRF)

practicante_registro.php     --->    Models/PracticanteModel.php
catalogos_admin.php          --->    Models/CatalogoModel.php

layout/head.php              --->    Views/layout/head.view.php
```

**Instrucción para la IA en esta fase:**
Mover cada archivo a su nueva ubicación según el mapeo. Actualizar todos los `require_once` en el proyecto para que apunten a las nuevas rutas. Probar que el sistema siga funcionando igual que antes.

---

## Fase 3: El Gran Corte (Separación MVC - Controllers y Views)

**Objetivo:** Tomar los archivos que hoy son una mezcla de PHP y HTML (como `practicante/permisos.php`) y dividirlos en dos partes: el que "piensa" (Controller) y el que "muestra" (View).

**¿Cómo fluirá la información?**
1.  **Navegador** pide la página.
2.  **Controller** (PHP) recibe la petición, valida si el usuario tiene permiso, y le pide los datos al **Service/Model**.
3.  **Controller** toma esos datos y se los inyecta a la **View** (HTML).
4.  **View** le devuelve el diseño final al **Navegador**.

**Ejemplo con `practicante/permisos.php`:**
Lo dividiremos así:

*   **Archivo 1:** `src/Controllers/Practicante/PermisosController.php` (Solo PHP. Procesa el formulario y busca los datos).
*   **Archivo 2:** `src/Views/practicante/permisos.view.php` (Solo HTML. Solo hace `echo` de las variables que le pasó el controller).

**Instrucción para la IA en esta fase:**
Crear los Controllers para cada rol. Mover el HTML puro a la carpeta `src/Views/`. Asegurar que el Controller pase correctamente las variables a la Vista y enrutar a través de `public/`.

---

## Fase 4: Migración a Tailwind CSS (El lavado de cara)

**Objetivo:** Reemplazar Bootstrap por Tailwind CSS para tener un diseño más moderno y personalizado. Se hará **vista por vista** para no romper la interfaz de golpe.

**Estrategia:**
1. Añadiremos el CDN de Tailwind al `<head>`.
2. Migraremos componentes uno a uno (Ej: primero la barra lateral, luego el login).
3. Al terminar todas las vistas, eliminaremos los archivos de Bootstrap.

**Ejemplo del cambio:**

*Botón Bootstrap (Antes):*
`<button class="btn btn-primary w-100 py-2 fw-semibold">Enviar</button>`

*Botón Tailwind (Después):*
`<button class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm">Enviar</button>`

**Instrucción para la IA en esta fase:**
Migrar ordenadamente: Layout base -> Autenticación (Login) -> Vistas de Practicante -> Vistas de Supervisor/Admin. Eliminar Bootstrap al finalizar.
