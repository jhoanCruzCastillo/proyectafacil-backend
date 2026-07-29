<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// /api/auth/* nunca va detrás del filtro 'auth' — es el propio punto de entrada de la sesión.
$routes->group('api', static function (RouteCollection $routes) {
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/me', 'AuthController::me');
    $routes->post('auth/logout', 'AuthController::logout');
});

// Todo lo demás requiere sesión activa (Módulo 1 en adelante).
$routes->group('api', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('sectores', 'SectoresController::index');
    $routes->get('sectores/(:num)', 'SectoresController::show/$1');
    $routes->post('sectores', 'SectoresController::create');
    $routes->put('sectores/(:num)', 'SectoresController::update/$1');
    $routes->delete('sectores/(:num)', 'SectoresController::delete/$1');
    $routes->get('sectores/(:num)/plantillas', 'PlantillasController::listBySector/$1');

    $routes->get('plantillas', 'PlantillasController::index');
    $routes->get('plantillas/(:num)', 'PlantillasController::show/$1');
    $routes->post('plantillas', 'PlantillasController::create');
    $routes->put('plantillas/(:num)', 'PlantillasController::update/$1');
    $routes->delete('plantillas/(:num)', 'PlantillasController::delete/$1');

    $routes->get('plantillas/(:num)/archivos', 'ArchivosController::getCatalogo/$1');
    $routes->post('plantillas/(:num)/archivos', 'ArchivosController::addArchivo/$1');
    $routes->delete('plantillas/(:num)/archivos/(:num)', 'ArchivosController::deleteArchivo/$1/$2');
    $routes->post('plantillas/(:num)/archivos/(:num)/asignar', 'ArchivosController::asignarArchivo/$1/$2');

    $routes->get('ejemplos', 'EjemplosController::index');
    $routes->get('ejemplos/(:num)', 'EjemplosController::show/$1');
    $routes->post('ejemplos', 'EjemplosController::create');
    $routes->put('ejemplos/(:num)', 'EjemplosController::update/$1');
    $routes->delete('ejemplos/(:num)', 'EjemplosController::delete/$1');
    $routes->get('plantillas/(:num)/ejemplos', 'EjemplosController::listByPlantilla/$1');

    $routes->get('ejemplos/(:num)/excel', 'ExcelEjemplosController::get/$1');
    $routes->post('ejemplos/(:num)/excel', 'ExcelEjemplosController::set/$1');

    $routes->get('usuarios', 'UsuariosController::index');
    $routes->post('usuarios', 'UsuariosController::create');
    $routes->put('usuarios/(:num)', 'UsuariosController::update/$1');
    $routes->delete('usuarios/(:num)', 'UsuariosController::delete/$1');

    $routes->get('tipos-usuario', 'TiposUsuarioController::index');
    $routes->post('tipos-usuario', 'TiposUsuarioController::create');
    $routes->put('tipos-usuario/(:num)', 'TiposUsuarioController::update/$1');
    $routes->delete('tipos-usuario/(:num)', 'TiposUsuarioController::delete/$1');

    $routes->get('roles-permisos', 'RolesPermisosController::index');
    $routes->put('roles-permisos/(:segment)', 'RolesPermisosController::update/$1');

    $routes->get('facturacion/(:num)', 'FacturacionController::get/$1');
    $routes->put('facturacion/(:num)', 'FacturacionController::update/$1');

    $routes->get('tickets-consulta/(:num)', 'TicketsConsultaController::index/$1');

    $routes->get('especialidades-asesor/(:num)', 'EspecialidadesAsesorController::index/$1');
    $routes->put('especialidades-asesor/(:num)', 'EspecialidadesAsesorController::guardar/$1');

    $routes->get('actividad', 'ActividadController::index');
    $routes->post('actividad', 'ActividadController::push');

    $routes->get('historial-cambios', 'HistorialCambiosController::index');
    $routes->get('ejemplos/(:num)/historial-cambios', 'HistorialCambiosController::listByEjemplo/$1');
    $routes->post('historial-cambios', 'HistorialCambiosController::registrar');

    $routes->get('docentes', 'DocentesController::index');
    $routes->get('docentes/admin', 'DocentesController::indexAdmin');
    $routes->put('docentes/(:num)/horario', 'DocentesController::actualizarHorario/$1');
    $routes->get('disponibilidad-horarios', 'DocentesController::disponibilidadAgregada');

    $routes->get('asesoria/solicitudes', 'AsesoriaController::misSolicitudes');
    $routes->post('asesoria/solicitudes', 'AsesoriaController::crear');
    $routes->post('asesoria/solicitudes/(:num)/aceptar', 'AsesoriaController::aceptar/$1');
    $routes->post('asesoria/solicitudes/(:num)/finalizar', 'AsesoriaController::finalizar/$1');
    $routes->post('asesoria/solicitudes/(:num)/cancelar', 'AsesoriaController::cancelarPropia/$1');
    $routes->post('asesoria/solicitudes/(:num)/calificar', 'AsesoriaController::calificar/$1');

    $routes->get('asesoria/dashboard', 'TicketsAsesoriaController::dashboard');
    $routes->get('asesoria/tickets', 'TicketsAsesoriaController::index');
    $routes->get('asesoria/tickets-mismo-horario', 'TicketsAsesoriaController::mismoHorario');
    $routes->get('asesoria/tickets/(:num)', 'TicketsAsesoriaController::detalle/$1');
    $routes->get('asesoria/tickets/(:num)/docentes-disponibles', 'TicketsAsesoriaController::docentesDisponibles/$1');
    $routes->post('asesoria/tickets/(:num)/asignar', 'TicketsAsesoriaController::asignar/$1');
    $routes->post('asesoria/tickets/(:num)/en-espera', 'TicketsAsesoriaController::marcarEnEspera/$1');
    $routes->post('asesoria/tickets/(:num)/reabrir-horario', 'TicketsAsesoriaController::reabrirHorario/$1');
    $routes->post('asesoria/tickets/(:num)/cancelar', 'TicketsAsesoriaController::cancelar/$1');
    $routes->get('asesoria/cobertura-horarios', 'TicketsAsesoriaController::coberturaHorarios');
    $routes->get('asesoria/liquidaciones', 'TicketsAsesoriaController::liquidaciones');
    $routes->post('asesoria/liquidaciones/autorizar', 'TicketsAsesoriaController::autorizarPago');
    $routes->get('asesoria/solicitudes/(:num)/mensajes', 'AsesoriaController::mensajes/$1');
    $routes->post('asesoria/solicitudes/(:num)/mensajes', 'AsesoriaController::enviarMensaje/$1');

    $routes->get('notificaciones', 'NotificacionesController::index');
    $routes->post('notificaciones/(:num)/leida', 'NotificacionesController::marcarLeida/$1');
});
