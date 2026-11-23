# Aprender DaisyUI desde el login

Actualizado para la estructura vigente el 21/10/2026. La primera integración empezó aislada en el login; el portal ahora también utiliza DaisyUI y seis temas en su compilación compartida. Para diseño general, consultar [Componentes](COMPONENTES.md) y [Branding](BRANDING.md).

Agregué esta demostración exclusivamente a la presentación de login.php. La URL continúa siendo /asistencia/login.php, pero MVC renderiza src/Views/auth/login.view.php; por eso se edita la vista y no se crea un archivo puente en la raíz.

## Cómo funciona

Tailwind aporta utilidades como w-full, h-12 y rounded-xl. daisyUI agrega componentes compuestos como botones, inputs y tarjetas. Se combinan en la misma clase HTML; no reemplaza PHP, la autenticación ni las validaciones.

Este proyecto usa Tailwind 3.4.17 y daisyUI 4.12.24 fijado en package.json/package-lock.json. daisyUI 5 requiere Tailwind 4; no actualizar ambas versiones como parte de esta prueba. Referencia oficial: https://daisyui.com/docs/v5/ .

## Archivos para aprender

- tailwind.login.config.cjs: carga el plugin, limita el análisis a la vista del login y define el tema rrhh con los colores institucionales.
- resources/css/login.css: fuente de la compilación específica. No se edita el archivo minificado generado.
- public/assets/css/login.css: resultado que sirve Apache; también se incluye en el paquete público de despliegue.
- src/Views/auth/login.view.php: carga ese CSS únicamente aquí, después del CSS compartido, y aplica data-theme="rrhh".

Los componentes usan el prefijo dui- para diferenciarlos de los estilos rrhh existentes. Ambas compilaciones cargan el plugin con su ámbito y temas: las otras pantallas no reciben login.css. Login usa rrhh/rrhh-dark y su selector propio; la preferencia inicial puede seguir el navegador. Se evita un segundo reset/preflight.

Ejemplo real del botón:

```html
<button type="submit" class="dui-btn dui-btn-primary w-full h-12 rounded-xl text-base">
    Entrar
</button>
```

dui-btn define el componente; dui-btn-primary usa el azul del tema; w-full ocupa el ancho disponible y las demás utilidades ajustan tamaño y forma. Los inputs usan dui-input dui-input-bordered; el checkbox, dui-checkbox dui-checkbox-primary dui-checkbox-sm; la tarjeta, dui-card.

## Instalar y compilar

```powershell
npm ci
npm run build:css
```

build:css compila el portal y el login. Para iterar únicamente en la prueba visual:

```powershell
npm run build:css:login
```

Después abrir http://localhost/asistencia/login.php. Cambiar colores en el tema de tailwind.login.config.cjs o componentes en la vista, recompilar y recargar. El CSS específico utiliza una versión por hash para evitar servir una compilación anterior desde caché. Node y daisyUI son herramientas de desarrollo; el hosting recibe CSS compilado y no necesita ejecutar npm para mostrarlo.

## Adaptar un componente de la documentación

1. Consultar ejemplos compatibles con daisyUI 4. Los ejemplos de la versión 5 pueden usar clases o estructuras diferentes.
2. Ubicar el HTML de la pantalla. En login es src/Views/auth/login.view.php; no modificar el controlador para un cambio exclusivamente visual.
3. Copiar solo el componente y adaptar sus clases daisyUI al prefijo del proyecto: btn pasa a dui-btn; btn-primary, a dui-btn-primary; input, a dui-input. Las utilidades Tailwind como w-full, mt-4 o rounded-xl no llevan ese prefijo.
4. Si reemplaza un control existente, conservar type, name, id, value, etiquetas, autocomplete, campos ocultos CSRF y PHP que prepara datos. Una mejora visual no debe romper el contrato del formulario.
5. Recompilar con npm run build:css:login y recargar el navegador. Añadir una clase al PHP no genera por sí solo CSS nuevo.
6. Probar móvil, teclado, foco y el comportamiento real. Un botón de acción visual dentro de un formulario debe usar type="button" para no enviar el formulario accidentalmente.

Ejemplo de aviso puramente visual que se puede agregar antes del formulario:

```html
<div class="dui-alert dui-alert-info mb-4" role="status">
    <span>Ingresa con el correo asociado a tu cuenta.</span>
</div>
```

Para este ejemplo no hace falta cambiar la configuración ni instalar otra dependencia; la vista ya está incluida en content. El tema rrhh controla los colores semánticos primary/info/etc. Las utilidades Tailwind ajustan espacio, tamaño y distribución del componente.

daisyUI proporciona CSS. Algunos ejemplos funcionan con HTML nativo, como details o dialog; otros necesitan JavaScript para abrir/cerrar o actualizar datos. Copiar su aspecto no crea automáticamente autenticación, consultas a la base, envío de correos ni funciones de negocio. Los componentes React/Vue de otros sitios requieren adaptación a HTML/PHP; este proyecto no utiliza esos frameworks.

Evitar construir nombres de clase por fragmentos PHP, por ejemplo dui-btn- seguido de una variable: Tailwind analiza archivos como texto y puede no generar esa combinación. Usar un mapa de clases completas permitidas, conservando escape de salida y validación del valor.

Fuera del login hay que revisar el content de la configuración compartida, el CSS cargado y las reglas semánticas de tema de esa pantalla. El plugin ya está integrado en el portal; no añadir otra CDN, reset o versión. Copiar una clase no garantiza el mismo resultado si las reglas de tema de rrhh tienen prioridad.

## Resultado histórico de la primera integración

Pasaron 13 pruebas UI y 16 recorridos completos en copia aislada con MariaDB/Edge. Inspeccioné capturas del login a 320 y 1280 px. La instalación E2E importa schema y catálogos sin los antiguos parches de creación; comprueba previamente las ocho tablas que antes faltaban. El ajuste de corte posterior pertenece únicamente a la fixture que simula una migración con historial. Se conservan los tests de repetición de parches para bases existentes.

## Lista de prueba manual

Comprobar escritorio/móvil, navegación con teclado, campos y etiquetas, botón de mostrar contraseña, errores, sesión expirada, Mantener sesión iniciada e ingreso por rol. Conservar POST, CSRF, nombres de campos y reglas de autenticación. Aplicar cambios a otras pantallas solo dentro del alcance solicitado y comprobar sus temas.
