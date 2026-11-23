> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Refactor de Layout — Matías Baxman

## Resumen

Unifiqué el layout de todas las páginas del sistema (admin, supervisor, practicante) bajo un mismo conjunto de includes compartidos, eliminando la duplicación masiva de HTML, sidebars inline y estilos fragmentados. Todo quedó basado exclusivamente en Bootstrap 5 + Bootstrap Icons, sin agregar archivos CSS/JS nuevos.

---

## Cambios realizados

### 1. Creación de layout compartido
- **`includes/layout/head.php`** — apertura de HTML, head con Bootstrap 5 CDN + Bootstrap Icons, soporte para `$pageTitle` y `$extraStyles[]`.
- **`includes/layout/sidebar.php`** — sidebar con gradiente azul oscuro, navegación según rol (ADMIN 7 links, SUPERVISOR 5, PRACTICANTE 4), detección automática de página activa, y header superior compartido (título, avatar con inicial, dropdown con "Cerrar sesión" y "Rol: …").
- **`includes/layout/foot.php`** — cierre de <main>, script de Bootstrap JS, cierre de HTML.

### 2. Sidebar definitivo (sin colapso)
- Anclado a 220px fijos en desktop via `min-width: 992px` + `offcanvas` de Bootstrap.
- Sin botón de colapso/reducción, sin animaciones de transición, sin estado `sidebar-collapsed`.
- Sin logo alternativo (se eliminó `brand-logo-icon` y la imagen `logo-sin-texto.png`).
- "Cerrar sesión" fuera del `<nav>`, al fondo del sidebar, en color rojo, pegado con `margin-top: auto`.

### 3. Header superior compartido
- Sticky top dentro de `<main>`.
- Título de página (`$pageTitle`) a la izquierda.
- Avatar circular con inicial del email, dropdown con "Rol: ADMIN/SUPERVISOR/PRACTICANTE" y "Cerrar sesión".
- Sin badge de rol suelto en el header (se movió al dropdown).

### 4. Refactor de páginas
- Limpié 4 includes con funciones render (`horarios_practicante.php`, `asistencia_gestion.php`, `reportes_gestion.php`, `practicante_registro.php`) que envolvían HTML innecesario.
- Actualicé las 16 páginas del sistema (`admin/*.php`, `supervisor/*.php`, `practicante/*.php`) para usar el layout compartido.
- Las páginas de solo archivo (`practicante/foto-perfil.php`, `practicante/descargar-documento.php`) quedaron fuera del layout.

### 5. Limpieza de CSS
- Eliminé `html { font-size: 75% }` de los CSS legacy que distorsionaba la tipografía.
- Los CSS antiguos (`admin-dashboard.css`, `practicante-asistencia.css`) aún se cargan vía `$extraStyles[]` solo en las páginas que los necesitan para estilos de contenido (tablas, etc.), pero su CSS de layout/sidebar quedó como código muerto inofensivo.

---

## Decisiones técnicas

| Decisión | Motivo |
|---|---|
| Solo Bootstrap 5 + Icons | Cero dependencias nuevas, máximo aprovechamiento del framework ya presente |
| Sidebar fijo 220px | Experiencia predecible, sin sorpresas de colapso |
| Header dentro de `<main>` | Evita duplicar avatar/logout en cada página |
| `$extraStyles[]` para CSS legacy | No vale la pena migrar estilos de contenido que funcionan; el layout nuevo no los necesita |
| No se usó Composer | El proyecto es PHP plano, no hay autoloader |

---

## Cómo agregar una página nueva

```php
<?php
$pageTitle = 'Título de mi página';
require __DIR__ . '/../includes/layout/head.php';
require __DIR__ . '/../includes/layout/sidebar.php';
?>

<!-- Contenido aquí -->

<?php require __DIR__ . '/../includes/layout/foot.php'; ?>
```

Solo se necesita definir `$pageTitle` y pasar `$extraStyles[]` si la página requiere CSS legacy.

---

## Pendientes / Deuda técnica

- [ ] Instalar PHPMailer (manual o composer) para que funcione el restablecimiento de contraseña y activación de cuentas (actualmente roto).
- [ ] Las variables de sesión solo tienen `email` — no hay nombre/apellido para mostrar en el avatar o dropdown. Si en el futuro se agrega, el cambio es trivial en `sidebar.php`.
- [ ] Los CSS legacy todavía referencian selectores de layout antiguos (sidebars, headers) que ya no se usan. Se pueden eliminar en una limpieza futura sin riesgo.
