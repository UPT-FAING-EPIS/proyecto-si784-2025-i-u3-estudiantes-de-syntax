<?php
session_start();
require_once __DIR__ . '/config/constants.php';
require_once BASE_PATH . '/core/BaseController.php';

$tiempoExpiracion = 900; // 15 minutos

if (isset($_SESSION['ultimo_acceso'])) {
    $inactividad = time() - $_SESSION['ultimo_acceso'];
    if ($inactividad > $tiempoExpiracion) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?accion=login&reason=timeout');
        exit;
    }
}

$_SESSION['ultimo_acceso'] = time();
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'inicio';
$usuarioId = $_SESSION['usuario_id'] ?? null;
$rolId = $_SESSION['rol_id'] ?? null;

// ========================================
// ACCIONES DE AUTENTICACIÓN (SIN LOGIN)
// ========================================
$authActions = ['login', 'procesar_login', 'consulta_dni', 'registro', 'procesar_registro', 'cerrar'];
if (in_array($accion, $authActions)) {
    require_once BASE_PATH . '/controllers/AuthController.php';
    $auth = new AuthController();
    if ($accion === 'cerrar') {
        session_destroy();
        header('Location: ' . BASE_URL);
        exit;
    }
    $auth->handle($accion);
    exit;
}

// ========================================
// ACCIONES PÚBLICAS (SIN LOGIN)
// ========================================
$accionesPublicas = ['mentoria', 'mentores', 'alumnos', 'anuncios', 'faq', 'testimonios', 'inicio'];
if (in_array($accion, $accionesPublicas)) {
    require_once BASE_PATH . '/controllers/HomeController.php';
    $home = new HomeController();
    $home->handle($accion);
    exit;
}
if (!$usuarioId || !$rolId) {
    header('Location: ' . BASE_URL . '/index.php?accion=login');
    exit;
}

$accionesVinculacion = [
    'vincular',
    'buscar_estudiante'
];

if (in_array($accion, $accionesVinculacion)) {
    if (!$usuarioId) {
        header('Location: ' . BASE_URL . '/index.php?accion=login');
        exit;
    }

    require_once BASE_PATH . '/controllers/EstudianteController.php';
    $estudiante = new EstudianteController();
    $estudiante->handle($accion);
    exit;
}
if (in_array($accion, ['obtener_usuarios', 'buscar_usuarios', 'detalle_usuario', 'actualizar_usuario', 'obtener_usuarios_roles', 'actualizar_usuario_roles', 'obtener_roles']) && $rolId == 4) {
    require_once BASE_PATH . '/controllers/AdminController.php';
    $adminController = new AdminController();
    $adminController->handle($accion);
    exit;
}
$mapaAcciones = [
    // Acciones de administración
    'obtener_usuarios' => ['AdminController', [4]],
    'buscar_usuarios' => ['AdminController', [4]],
    'detalle_usuario' => ['AdminController', [4]],
    'actualizar_usuario' => ['AdminController', [4]],
    'obtener_usuarios_roles' => ['AdminController', [4]],
    'actualizar_usuario_roles' => ['AdminController', [4]],
    'obtener_roles' => ['AdminController', [4]],
    
    // Acciones de docente
    'empezar_clase' => ['DocenteController', [3, 4]],
    'clases_asignadas' => ['DocenteController', [3]],
    'programar_clase' => ['DocenteController', [3]],
    'tomar_clases' => ['DocenteController', [3, 4]],
    'procesar_tomar_clase' => ['DocenteController', [3, 4]],
    'info_clase_disponible' => ['DocenteController', [3, 4]],
    'estadisticas_docente' => ['DocenteController', [3, 4]],
    'alumnos' => ['DocenteController', [3]],
    'reportes' => ['DocenteController', [3]],
    
    // Acciones de estudiante
    'solicitar_clase' => ['EstudianteController', [2]],
    'mis_clases' => ['EstudianteController', [2]],
    'obtener_cursos_mentoria' => ['EstudianteController', [2]],
];

if (isset($mapaAcciones[$accion])) {
    [$controladorNombre, $rolesPermitidos] = $mapaAcciones[$accion];
    if (in_array($rolId, $rolesPermitidos)) {
        require_once BASE_PATH . "/controllers/{$controladorNombre}.php";
        $ctrl = new $controladorNombre();
        $ctrl->handle($accion);
        exit;
    } else {
        http_response_code(403);
        echo "<h2>Acción no permitida para tu rol.</h2>";
        exit;
    }
}

$carpetasPorRol = [1 => 'usuario', 2 => 'estudiante', 3 => 'docente', 4 => 'administrador'];
$carpeta = $carpetasPorRol[$rolId] ?? null;

if ($carpeta) {
    $ruta = BASE_PATH . "/views/$carpeta/$accion.php";
    $rutaComun = BASE_PATH . "/views/usuario/$accion.php";
    if (file_exists($ruta)) {
        include $ruta;
        exit;
    } elseif (file_exists($rutaComun)) {
        include $rutaComun;
        exit;
    }
}

http_response_code(404);
echo "<h2>Página no encontrada</h2>";
exit;
?>