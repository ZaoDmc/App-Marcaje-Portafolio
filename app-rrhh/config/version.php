<?php

declare(strict_types=1);

// Centralicé la versión del código para que el login y cada despliegue usen
// el mismo valor versionado, independiente del .env de cada instalación.
// Incrementé PARCHE a 2.1.9 para consolidar en producción las mejoras operativas de asistencia (2.1.5–2.1.8),
// el modo solo lectura al 100%/egreso, la validación estricta de ventana POST y el truncamiento al minuto en cruces de permisos parciales (sin MFA ni migraciones SQL).
return '2.1.9';
