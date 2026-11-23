<?php

declare(strict_types=1);

// Agregué destinos fijos para las URLs existentes, sus roles y métodos.
// Este catálogo no descubre archivos ni carga rutas desde datos de la solicitud.
// La autorización se aplica antes del HTML; se conserva el CSRF del controlador.
$htmlMethods = ['GET', 'HEAD', 'POST'];
$readMethods = ['GET', 'HEAD'];

return [
    '/' => ['controller' => 'src/Controllers/Auth/HomeController.php', 'roles' => [], 'methods' => $readMethods],
    '/index.php' => ['controller' => 'src/Controllers/Auth/HomeController.php', 'roles' => [], 'methods' => $readMethods],
    '/login' => ['controller' => 'src/Controllers/Auth/LoginController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/activar-cuenta.php' => ['controller' => 'src/Controllers/Auth/ActivarCuentaController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/admin/asistencia.php' => ['controller' => 'src/Controllers/Admin/AsistenciaController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/auditoria.php' => ['controller' => 'src/Controllers/Admin/AuditoriaController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/catalogos.php' => ['controller' => 'src/Controllers/Admin/CatalogosController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/crear-practicante.php' => ['controller' => 'src/Controllers/Admin/CrearPracticanteController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/dashboard.php' => ['controller' => 'src/Controllers/Admin/DashboardController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/nuevo-practicante.php' => ['controller' => 'src/Controllers/Admin/NuevoPracticanteController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/permisos.php' => ['controller' => 'src/Controllers/Admin/PermisosController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/practicantes-carga-masiva.php' => ['controller' => 'src/Controllers/Admin/PracticantesCargaMasivaController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/practicantes.php' => ['controller' => 'src/Controllers/Admin/PracticantesController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/reportes.php' => ['controller' => 'src/Controllers/Admin/ReportesController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    // Agregué gestión de líderes reservada a ADMIN; no habilita todavía la carga de calificaciones.
    '/admin/notas.php' => ['controller' => 'src/Controllers/Admin/NotasController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    // Agregué formularios semanales y rúbricas separados de la gestión de liderazgo.
    '/admin/notas-evaluaciones.php' => ['controller' => 'src/Controllers/Admin/NotasEvaluacionesController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/notas-rubrica.php' => ['controller' => 'src/Controllers/Admin/NotasRubricaController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/admin/usuarios.php' => ['controller' => 'src/Controllers/Admin/UsuariosController.php', 'roles' => ['ADMIN'], 'methods' => $htmlMethods],
    '/formulario-validacion.php' => ['controller' => 'src/Controllers/Shared/FormularioValidacionController.php', 'roles' => ['ADMIN', 'SUPERVISOR'], 'methods' => $htmlMethods],
    '/formulario-validacion-descarga.php' => ['controller' => 'src/Controllers/Shared/DescargarFormularioValidacionController.php', 'roles' => ['ADMIN', 'SUPERVISOR'], 'methods' => $readMethods],
    '/login.php' => ['controller' => 'src/Controllers/Auth/LoginController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/logout.php' => ['controller' => 'src/Controllers/Auth/LogoutController.php', 'roles' => [], 'methods' => $htmlMethods],
    // Trasladé el destino a MVC en fase 5 conservando URL, roles y métodos.
    '/practicante/api/horario-dia.php' => ['controller' => 'src/Controllers/Practicante/HorarioDiaController.php', 'roles' => ['PRACTICANTE'], 'methods' => $readMethods],
    '/practicante/dashboard.php' => ['controller' => 'src/Controllers/Practicante/DashboardController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    '/practicante/descargar-documento.php' => ['controller' => 'src/Controllers/Practicante/DescargarDocumentoController.php', 'roles' => ['PRACTICANTE'], 'methods' => $readMethods],
    '/practicante/foto-perfil.php' => ['controller' => 'src/Controllers/Practicante/FotoPerfilController.php', 'roles' => ['PRACTICANTE'], 'methods' => $readMethods],
    '/practicante/marcar-asistencia.php' => ['controller' => 'src/Controllers/Practicante/MarcarAsistenciaController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    '/practicante/mi-historial.php' => ['controller' => 'src/Controllers/Practicante/MiHistorialController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    '/practicante/mis-datos.php' => ['controller' => 'src/Controllers/Practicante/MisDatosController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    '/practicante/permisos.php' => ['controller' => 'src/Controllers/Practicante/PermisosController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    // El controlador exige además liderazgo vigente; el rol PRACTICANTE por sí solo no permite evaluar.
    '/practicante/notas.php' => ['controller' => 'src/Controllers/Practicante/NotasController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    // Separé la consulta propia de las facultades de evaluación; esta ruta no admite escrituras.
    '/practicante/mis-notas.php' => ['controller' => 'src/Controllers/Practicante/MisNotasController.php', 'roles' => ['PRACTICANTE'], 'methods' => $readMethods],
    // Implemente la ruta resenas.php, para manejar la logica que despues se mantendra dentro del controlador, y en base al rol que se solicita, el cual es practicante - Alexander
    '/practicante/resenas.php' => ['controller' => 'src/Controllers/Practicante/ResenasController.php', 'roles' => ['PRACTICANTE'], 'methods' => $htmlMethods],
    '/recuperar-password.php' => ['controller' => 'src/Controllers/Auth/RecuperarPasswordController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/restablecer-password.php' => ['controller' => 'src/Controllers/Auth/RestablecerPasswordController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/seleccionar-rol.php' => ['controller' => 'src/Controllers/Auth/SeleccionarRolController.php', 'roles' => [], 'methods' => $htmlMethods],
    '/supervisor/acom.php' => ['controller' => 'src/Controllers/Supervisor/AcomController.php', 'roles' => ['ADMIN', 'SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/asistencia.php' => ['controller' => 'src/Controllers/Supervisor/AsistenciaController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/crear-practicante.php' => ['controller' => 'src/Controllers/Supervisor/CrearPracticanteController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/dashboard.php' => ['controller' => 'src/Controllers/Supervisor/DashboardController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/nuevo-practicante.php' => ['controller' => 'src/Controllers/Supervisor/NuevoPracticanteController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/permisos.php' => ['controller' => 'src/Controllers/Supervisor/PermisosController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/practicantes-carga-masiva.php' => ['controller' => 'src/Controllers/Supervisor/PracticantesCargaMasivaController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/practicantes.php' => ['controller' => 'src/Controllers/Supervisor/PracticantesController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    '/supervisor/reportes.php' => ['controller' => 'src/Controllers/Supervisor/ReportesController.php', 'roles' => ['SUPERVISOR'], 'methods' => $htmlMethods],
    // Implemente la ruta resenas.php, para manejar la logica que despues se mantendra dentro del controlador, y en base al rol que se solicita, el cual es supervisor y administrador, el cual tendra mejor visualizacion para ver lo que se tiene dentro del modulo - Alexander
    '/supervisor/resenas.php' => ['controller' => 'src/Controllers/Supervisor/ResenasController.php', 'roles' => ['ADMIN', 'SUPERVISOR'], 'methods' => $htmlMethods],
];
