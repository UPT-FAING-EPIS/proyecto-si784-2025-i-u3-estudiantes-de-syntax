<?php
require_once BASE_PATH . '/core/BaseController.php';
require_once BASE_PATH . '/models/AdminModel.php';

class AdminController extends BaseController {
    private $adminModel;

    public function __construct() {
        $this->adminModel = new AdminModel();
    }

    public function handle($accion) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 4) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores pueden realizar esta acción.'
            ]);
            exit;
        }

        header('Content-Type: application/json');
        
        try {
            switch ($accion) {
                case 'obtener_usuarios':
                    $this->obtenerUsuariosGet();
                    break;
                case 'buscar_usuarios':
                    $this->buscarUsuariosGet();
                    break;
                case 'detalle_usuario':
                    $this->detalleUsuarioGet();
                    break;
                case 'actualizar_usuario':
                    $this->actualizarUsuarioPost();
                    break;
                case 'obtener_usuarios_roles':
                    $this->obtenerUsuariosRolesGet();
                    break;
                case 'actualizar_usuario_roles':
                    $this->actualizarUsuarioRolesPost();
                    break;
                case 'obtener_roles':
                    $this->obtenerRolesGet();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Acción no reconocida'
                    ]);
                    break;
            }
        } catch (Exception $e) {
            error_log("Error en AdminController acción '$accion': " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function obtenerUsuariosGet() {
        $limite = isset($_GET['limite']) ? max(1, min(100, intval($_GET['limite']))) : 50;
        $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
        
        $usuarios = $this->adminModel->obtenerTodosUsuarios($limite, $offset);
        
        if ($usuarios !== false) {
            echo json_encode([
                'success' => true, 
                'usuarios' => $usuarios,
                'total' => count($usuarios),
                'limite' => $limite,
                'offset' => $offset
            ]);
        } else {
            throw new Exception('Error al obtener usuarios');
        }
    }

    public function buscarUsuariosGet() {
        $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
        $valor = isset($_GET['valor']) ? trim($_GET['valor']) : '';
        $limite = isset($_GET['limite']) ? max(1, min(100, intval($_GET['limite']))) : 50;
        
        if (empty($valor)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Valor de búsqueda requerido'
            ]);
            return;
        }
        
        $tiposValidos = ['nombre', 'dni', 'email', 'codigo', ''];
        if (!in_array($tipo, $tiposValidos)) {
            $tipo = '';
        }
        
        $usuarios = $this->adminModel->buscarUsuarios($tipo, $valor, $limite);
        
        if ($usuarios !== false) {
            echo json_encode([
                'success' => true, 
                'usuarios' => $usuarios,
                'total' => count($usuarios),
                'criterio' => $tipo,
                'valor' => $valor
            ]);
        } else {
            throw new Exception('Error en la búsqueda de usuarios');
        }
    }

    public function detalleUsuarioGet() {
        $id_usuario = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id_usuario <= 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'ID de usuario inválido'
            ]);
            return;
        }
        
        $detalle = $this->adminModel->obtenerDetalleUsuario($id_usuario);
        
        if ($detalle) {
            echo json_encode([
                'success' => true, 
                'usuario' => $detalle
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Usuario no encontrado'
            ]);
        }
    }

    public function actualizarUsuarioPost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Datos JSON inválidos');
        }
        
        $camposRequeridos = ['id_usuario', 'email', 'id_rol'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($input[$campo]) || empty($input[$campo])) {
                throw new Exception("Campo requerido faltante: $campo");
            }
        }
        
        $id_usuario = intval($input['id_usuario']);
        $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
        $id_rol = intval($input['id_rol']);
        
        if ($id_usuario <= 0) {
            throw new Exception('ID de usuario inválido');
        }
        
        if (!$email) {
            throw new Exception('Email inválido');
        }
        
        if (!in_array($id_rol, [1, 2, 3, 4])) {
            throw new Exception('Rol inválido');
        }
        
        $resultado = $this->adminModel->actualizarUsuario($id_usuario, $email, $id_rol);
        
        if ($resultado) {
            error_log("Usuario $id_usuario actualizado por admin {$_SESSION['usuario_id']}: email=$email, rol=$id_rol");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Usuario actualizado correctamente'
            ]);
        } else {
            throw new Exception('Error al actualizar usuario en la base de datos');
        }
    }

    public function obtenerUsuariosRolesGet() {
        $limite = isset($_GET['limite']) ? max(1, min(100, intval($_GET['limite']))) : 50;
        $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
        $filtro = null;
        
        if (isset($_GET['filtro_tipo']) && isset($_GET['filtro_valor']) && !empty($_GET['filtro_valor'])) {
            $filtro = [
                'tipo' => $_GET['filtro_tipo'],
                'valor' => trim($_GET['filtro_valor'])
            ];
        }
        
        $usuarios = $this->adminModel->obtenerUsuariosConRoles($limite, $offset, $filtro);
        
        if ($usuarios !== false) {
            echo json_encode([
                'success' => true, 
                'usuarios' => $usuarios,
                'total' => count($usuarios),
                'limite' => $limite,
                'offset' => $offset
            ]);
        } else {
            throw new Exception('Error al obtener usuarios');
        }
    }

    public function actualizarUsuarioRolesPost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Datos JSON inválidos');
        }
        
        $camposRequeridos = ['id_usuario', 'email', 'roles'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($input[$campo])) {
                throw new Exception("Campo requerido faltante: $campo");
            }
        }
        
        $id_usuario = intval($input['id_usuario']);
        $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
        $roles = $input['roles'];
        
        if ($id_usuario <= 0) {
            throw new Exception('ID de usuario inválido');
        }
        
        if (!$email) {
            throw new Exception('Email inválido');
        }
        
        if (!is_array($roles) || empty($roles)) {
            throw new Exception('Debe seleccionar al menos un rol');
        }
        
        // Validar que todos los roles sean válidos
        $roles_validos = [1, 2, 3, 4];
        foreach ($roles as $rol) {
            if (!in_array(intval($rol), $roles_validos)) {
                throw new Exception('Rol inválido: ' . $rol);
            }
        }
        
        $resultado = $this->adminModel->actualizarUsuarioConRoles($id_usuario, $email, array_map('intval', $roles));
        
        if ($resultado) {
            error_log("Usuario $id_usuario actualizado por admin {$_SESSION['usuario_id']}: email=$email, roles=" . implode(',', $roles));
            
            echo json_encode([
                'success' => true, 
                'message' => 'Usuario actualizado correctamente'
            ]);
        } else {
            throw new Exception('Error al actualizar usuario en la base de datos');
        }
    }

    public function obtenerRolesGet() {
        $roles = $this->adminModel->obtenerRolesDisponibles();
        echo json_encode([
            'success' => true,
            'roles' => $roles
        ]);
    }
}