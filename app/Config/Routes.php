<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// /api/auth/* nunca va detrás del filtro 'auth' — es el propio punto de entrada de la sesión.
$routes->group('api', static function (RouteCollection $routes) {
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/me', 'AuthController::me');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->post('auth/registro', 'AuthController::register');
    $routes->get('auth/verificar/(:segment)', 'AuthController::verificar/$1');

    // Catálogo de sectores para el paso "temas de interés" del registro público — antes de tener
    // sesión no se puede llamar al GET /sectores normal (detrás del filtro auth). Mismo método,
    // sin nada sensible que proteger (es un catálogo, no datos de usuario).
    $routes->get('sectores/publico', 'SectoresController::index');

    // Stripe llama a esto directo — no manda sesión, no puede ir detrás del filtro 'auth'. La
    // firma en el header Stripe-Signature es la única verificación (ver PagosController::webhook).
    $routes->post('pagos/webhook', 'PagosController::webhook');
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
    // Proxy binario (S3/Cloudinary) — el SPA no puede fetch directo al bucket (CORS).
    $routes->get('archivos/(:num)/contenido', 'ArchivosController::contenido/$1');

    // Imágenes de campos tipo `imagen`: el binario se sube a Cloudinary y en el JSON se guarda su URL.
    $routes->post('imagenes', 'ImagenesController::subir');
    $routes->get('imagenes/capacidades', 'ImagenesController::capacidades');

    $routes->get('ejemplos', 'EjemplosController::index');
    $routes->get('ejemplos/(:num)', 'EjemplosController::show/$1');
    $routes->post('ejemplos', 'EjemplosController::create');
    $routes->put('ejemplos/(:num)', 'EjemplosController::update/$1');
    $routes->delete('ejemplos/(:num)', 'EjemplosController::delete/$1');
    $routes->put('ejemplos/(:num)/referencia-ia', 'EjemplosController::marcarReferenciaIA/$1');
    $routes->get('plantillas/(:num)/ejemplos', 'EjemplosController::listByPlantilla/$1');

    $routes->get('ejemplos/(:num)/excel', 'ExcelEjemplosController::get/$1');
    $routes->post('ejemplos/(:num)/excel', 'ExcelEjemplosController::set/$1');

    $routes->get('ejemplos/(:num)/fuente-verdad', 'FuenteVerdadController::index/$1');
    $routes->post('ejemplos/(:num)/fuente-verdad/archivos', 'FuenteVerdadController::guardarArchivo/$1');
    $routes->delete('ejemplos/(:num)/fuente-verdad/archivos/(:num)', 'FuenteVerdadController::eliminarArchivo/$1/$2');
    $routes->put('ejemplos/(:num)/fuente-verdad/texto', 'FuenteVerdadController::guardarTexto/$1');
    $routes->post('ejemplos/(:num)/llenar-ia', 'LlenadoIAController::llenarFicha/$1');
    $routes->post('ejemplos/(:num)/llenar-tabla-ia', 'LlenadoIAController::llenarTabla/$1');
    $routes->get('plantillas/(:num)/preview-prompt', 'LlenadoIAController::previsualizarPrompt/$1');
    // "Llenar toda la ficha" vía Batch API de OpenAI (~50% más barato, sin respuesta inmediata) — ver
    // enviarLoteFicha(). El botón individual sigue en llenar-ia/llenar-tabla-ia de arriba, sin tocar.
    $routes->post('ejemplos/(:num)/llenar-ia-lote', 'LlenadoIAController::enviarLoteFicha/$1');
    $routes->get('ejemplos/(:num)/llenar-ia-lote/(:num)', 'LlenadoIAController::estadoLoteFicha/$1/$2');

    $routes->get('usuarios', 'UsuariosController::index');
    $routes->post('usuarios', 'UsuariosController::create');
    $routes->put('usuarios/(:num)', 'UsuariosController::update/$1');
    $routes->delete('usuarios/(:num)', 'UsuariosController::delete/$1');
    $routes->post('usuarios/(:num)/enviar-accesos', 'UsuariosController::enviarAccesos/$1');
    $routes->post('usuarios/(:num)/enviar-accesos-directo', 'UsuariosController::enviarAccesosDirecto/$1');

    $routes->get('tipos-usuario', 'TiposUsuarioController::index');
    $routes->post('tipos-usuario', 'TiposUsuarioController::create');
    $routes->put('tipos-usuario/(:num)', 'TiposUsuarioController::update/$1');
    $routes->delete('tipos-usuario/(:num)', 'TiposUsuarioController::delete/$1');

    $routes->get('roles-permisos', 'RolesPermisosController::index');
    $routes->put('roles-permisos/(:segment)', 'RolesPermisosController::update/$1');

    $routes->get('facturacion/resumen-niveles', 'FacturacionController::resumenNiveles');
    $routes->get('facturacion/(:num)', 'FacturacionController::get/$1');
    $routes->put('facturacion/(:num)', 'FacturacionController::update/$1');

    $routes->get('tickets-consulta/(:num)', 'TicketsConsultaController::index/$1');

    $routes->get('especialidades-asesor/(:num)', 'EspecialidadesAsesorController::index/$1');
    $routes->put('especialidades-asesor/(:num)', 'EspecialidadesAsesorController::guardar/$1');

    $routes->get('subtemas-especialidad', 'SubtemasEspecialidadController::index');
    $routes->get('subtemas-asesor/(:num)', 'SubtemasEspecialidadController::delAsesor/$1');
    $routes->put('subtemas-asesor/(:num)', 'SubtemasEspecialidadController::guardarDelAsesor/$1');

    $routes->get('actividad', 'ActividadController::index');
    $routes->post('actividad', 'ActividadController::push');
    $routes->get('actividad/por-usuario/(:num)', 'ActividadController::porActor/$1');
    $routes->get('actividad/ultima-modificacion-perfil/(:num)', 'ActividadController::ultimaModificacionPerfil/$1');

    $routes->get('sesiones', 'SesionesController::misSesiones');
    $routes->post('sesiones/(:num)/cerrar', 'SesionesController::cerrar/$1');

    $routes->get('historial-cambios', 'HistorialCambiosController::index');
    $routes->get('ejemplos/(:num)/historial-cambios', 'HistorialCambiosController::listByEjemplo/$1');
    $routes->post('historial-cambios', 'HistorialCambiosController::registrar');

    $routes->get('docentes', 'DocentesController::index');
    $routes->get('docentes/admin', 'DocentesController::indexAdmin');
    $routes->put('docentes/(:num)/horario', 'DocentesController::actualizarHorario/$1');
    $routes->get('docentes/(:num)/excepciones', 'DocentesController::excepciones/$1');
    $routes->put('docentes/(:num)/excepciones', 'DocentesController::actualizarExcepciones/$1');
    $routes->get('disponibilidad-horarios', 'DocentesController::disponibilidadAgregada');

    $routes->get('asesoria/solicitudes', 'AsesoriaController::misSolicitudes');
    $routes->get('asesoria/no-atendidas', 'AsesoriaController::noAtendidas');
    $routes->get('asesoria/agendados-por-rango', 'AsesoriaController::agendadosPorRango');

    $routes->get('beneficios', 'BeneficiosController::index');
    $routes->get('beneficios/mios', 'BeneficiosController::misBeneficios');
    $routes->post('pagos/checkout', 'PagosController::checkout');
    $routes->post('pagos/checkout-plan', 'PagosController::checkoutPlan');
    $routes->post('pagos/cambiar-plan', 'PagosController::cambiarPlan');
    $routes->post('pagos/checkout-addon', 'PagosController::checkoutAddon');
    $routes->post('pagos/quitar-addon', 'PagosController::quitarAddon');
    $routes->get('pagos/portal', 'PagosController::portal');

    $routes->get('plantillas/(:num)/contextos-ia', 'ContextosIAController::index/$1');
    $routes->get('plantillas/(:num)/contextos-ia/prompt-sistema-predeterminado', 'ContextosIAController::promptSistemaPredeterminado/$1');
    // Rutas de "generales" van ANTES que el (:segment) genérico de sección: si no, "generales" se
    // interpretaría como un seccionId y nunca llegaría a estos métodos.
    $routes->get('plantillas/(:num)/contextos-ia/generales', 'ContextosIAController::indexGenerales/$1');
    $routes->post('plantillas/(:num)/contextos-ia/generales', 'ContextosIAController::guardarGeneral/$1');
    $routes->put('plantillas/(:num)/contextos-ia/generales/(:num)', 'ContextosIAController::guardarGeneral/$1/$2');
    $routes->delete('plantillas/(:num)/contextos-ia/generales/(:num)', 'ContextosIAController::eliminarGeneral/$1/$2');
    // "Estructura": qué insumo va en cada paso del armado del prompt de sistema (paso = 1/2/4/5).
    $routes->post('plantillas/(:num)/contextos-ia/pasos/(:num)', 'ContextosIAController::guardarPaso/$1/$2');
    $routes->delete('plantillas/(:num)/contextos-ia/pasos-asignaciones/(:num)', 'ContextosIAController::eliminarPaso/$1/$2');
    $routes->put('plantillas/(:num)/contextos-ia/(:segment)', 'ContextosIAController::guardar/$1/$2');
    $routes->delete('plantillas/(:num)/contextos-ia/(:segment)', 'ContextosIAController::eliminar/$1/$2');
    $routes->get('contextos-ia/globales', 'ContextosIAController::indexGlobales');
    $routes->post('contextos-ia/globales', 'ContextosIAController::guardarGlobal');
    $routes->put('contextos-ia/globales/(:num)', 'ContextosIAController::guardarGlobal/$1');
    $routes->delete('contextos-ia/globales/(:num)', 'ContextosIAController::eliminarGlobal/$1');

    $routes->post('asistente-ia/consultar', 'AsistenteIAController::consultar');

    $routes->get('mi-liquidacion/historico', 'MiLiquidacionController::historico');
    $routes->get('mi-liquidacion/pendiente', 'MiLiquidacionController::pendiente');
    $routes->get('mi-liquidacion/mes', 'MiLiquidacionController::mes');
    $routes->post('asesoria/solicitudes', 'AsesoriaController::crear');
    $routes->post('asesoria/solicitudes/(:num)/aceptar', 'AsesoriaController::aceptar/$1');
    $routes->post('asesoria/solicitudes/(:num)/finalizar', 'AsesoriaController::finalizar/$1');
    $routes->post('asesoria/solicitudes/(:num)/completar-video', 'AsesoriaController::completarVideo/$1');
    $routes->post('asesoria/solicitudes/(:num)/cancelar', 'AsesoriaController::cancelarPropia/$1');
    $routes->post('asesoria/solicitudes/(:num)/calificar', 'AsesoriaController::calificar/$1');

    $routes->get('asesoria/dashboard', 'TicketsAsesoriaController::dashboard');
    $routes->get('asesoria/tickets', 'TicketsAsesoriaController::index');
    $routes->get('asesoria/tickets-mismo-horario', 'TicketsAsesoriaController::mismoHorario');
    $routes->get('asesoria/tickets/(:num)', 'TicketsAsesoriaController::detalle/$1');
    $routes->get('asesoria/tickets/(:num)/historial-conexion', 'TicketsAsesoriaController::historialConexion/$1');
    $routes->get('asesoria/tickets/(:num)/docentes-disponibles', 'TicketsAsesoriaController::docentesDisponibles/$1');
    $routes->post('asesoria/tickets/(:num)/asignar', 'TicketsAsesoriaController::asignar/$1');
    $routes->post('asesoria/tickets/(:num)/en-espera', 'TicketsAsesoriaController::marcarEnEspera/$1');
    $routes->post('asesoria/tickets/(:num)/reabrir-horario', 'TicketsAsesoriaController::reabrirHorario/$1');
    $routes->post('asesoria/tickets/(:num)/cancelar', 'TicketsAsesoriaController::cancelar/$1');
    $routes->get('asesoria/cobertura-horarios', 'TicketsAsesoriaController::coberturaHorarios');
    $routes->get('asesoria/liquidaciones', 'TicketsAsesoriaController::liquidaciones');
    $routes->post('asesoria/liquidaciones/autorizar', 'TicketsAsesoriaController::autorizarPago');
    $routes->get('asesoria/configuracion-sla', 'TicketsAsesoriaController::configuracionSla');
    $routes->put('asesoria/configuracion-sla', 'TicketsAsesoriaController::actualizarConfiguracionSla');
    $routes->get('asesoria/solicitudes/(:num)/mensajes', 'AsesoriaController::mensajes/$1');
    $routes->post('asesoria/solicitudes/(:num)/mensajes', 'AsesoriaController::enviarMensaje/$1');
    $routes->post('asesoria/adjuntos', 'AsesoriaController::subirAdjunto');
    $routes->get('asesoria/mensajes/(:num)/adjunto', 'AsesoriaController::adjuntoMensaje/$1');

    $routes->get('notificaciones', 'NotificacionesController::index');
    $routes->post('notificaciones/(:num)/leida', 'NotificacionesController::marcarLeida/$1');
});
