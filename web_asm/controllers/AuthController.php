<?php
require_once BASE_PATH . '/models/Usuario.php';

class AuthController extends BaseController {

    public function handle($accion) {
        switch ($accion) {
            case 'login':
                $this->loginGet();
                break;
            case 'procesar_login':
                $this->loginPost();
                break;
            case 'login_oauth':
                $this->loginOAuth();
                break;
            case 'registro':
                $this->registroGet();
                break;
            case 'procesar_registro':
                $this->registroPost();
                break;
            case 'consulta_dni':
                $this->consultaDNI();
                break;
            case 'cerrar':
            case 'logout':
                $this->logout();
                break;
            default:
                echo "<h2>Acción de autenticación no válida: " . htmlspecialchars($accion) . "</h2>";
                break;
        }
    }
    public function loginGet() {
        // Si ya está logueado, redirigir al dashboard
        if ($this->verificarSesionActiva()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }
        
        require BASE_PATH . '/views/login.php';
    }

    public function loginPost() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            $usuarioModel = new Usuario();
            $datos = $usuarioModel->verificarCredenciales($email, $password);

            if ($datos) {
                // ✅ ESTABLECER SESIÓN CON TIEMPO DE LOGIN
                $this->establecerSesion($datos);
                
                // ✅ REGISTRAR INICIO DE SESIÓN
                $usuarioModel->registrarInicioSesion($datos['id_usuario']);
                
                // Log de login exitoso
                error_log("🔑 Login exitoso para usuario: {$email} (ID: {$datos['id_usuario']})");
                
                // Redirigir según el rol
                $redirectUrl = $this->obtenerUrlRedirect($datos['id_rol']);
                header('Location: ' . $redirectUrl);
                exit();
                
            } else {
                // Log de intento fallido
                error_log("❌ Intento de login fallido para email: {$email}");
                
                $this->mostrarError('Credenciales incorrectas', BASE_URL . '/index.php?accion=login');
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en login: " . $e->getMessage());
            $this->mostrarError('Error del servidor. Intente nuevamente.', BASE_URL . '/index.php?accion=login');
        }
    }

    public function loginOAuth() {
        $email = $_POST['email'] ?? '';
        $google_id = $_POST['google_id'] ?? '';
        $nombres = $_POST['nombres'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';

        try {
            $usuarioModel = new Usuario();
            
            // Buscar usuario existente por OAuth
            $datos = $usuarioModel->verificarCredencialesOAuth($email, $google_id);
            
            if (!$datos) {
                // Registrar nuevo usuario OAuth
                $resultado = $usuarioModel->registrarOAuth($email, $google_id);
                
                if (!$resultado) {
                    $this->mostrarError('Error al registrar usuario con Google', BASE_URL . '/index.php?accion=login');
                    return;
                }
                
                // Obtener datos completos del usuario recién creado
                $datos = $usuarioModel->obtenerDatosCompletos($resultado['id_usuario']);
                
                if (!$datos) {
                    $this->mostrarError('Error al obtener datos del usuario', BASE_URL . '/index.php?accion=login');
                    return;
                }
                
                // Actualizar nombres si están vacíos
                if (empty($datos['nombres']) && !empty($nombres)) {
                    $usuarioModel->actualizarDatos($resultado['id_usuario'], [
                        'nombres' => $nombres,
                        'apellidos' => $apellidos
                    ]);
                }
            }

            // ✅ ESTABLECER SESIÓN CON TIEMPO DE LOGIN
            $this->establecerSesion($datos);
            
            // ✅ REGISTRAR INICIO DE SESIÓN
            $usuarioModel->registrarInicioSesion($datos['id_usuario']);
            
            // Log de login OAuth exitoso
            error_log("🔑 Login OAuth exitoso para usuario: {$email} (ID: {$datos['id_usuario']})");
            
            // Respuesta JSON para JavaScript
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'redirect' => $this->obtenerUrlRedirect($datos['rol_prioritario'])
            ]);
            exit();
            
        } catch (Exception $e) {
            error_log("❌ Error en login OAuth: " . $e->getMessage());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error del servidor. Intente nuevamente.'
            ]);
            exit();
        }
    }

    private function establecerSesion($datos) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // ✅ DATOS BÁSICOS DE SESIÓN
        $_SESSION['usuario_id'] = $datos['id_usuario'];
        $_SESSION['email'] = $datos['email'];
        $_SESSION['rol_id'] = $datos['rol_prioritario'] ?? $datos['id_rol'] ?? 1;
        $_SESSION['rol_nombre'] = $datos['rol_nombre'] ?? 'Usuario';
        
        // ✅ TIEMPO DE LOGIN - SOLO SE ESTABLECE UNA VEZ AQUÍ
        $_SESSION['login_time'] = time();
        $_SESSION['login_date'] = date('Y-m-d H:i:s');
        $_SESSION['ip_login'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // ✅ DATOS ADICIONALES
        $_SESSION['nombres'] = $datos['nombres'] ?? '';
        $_SESSION['apellidos'] = $datos['apellidos'] ?? '';
        $_SESSION['email_verificado'] = $datos['email_verificado'] ?? false;
        
        // Log detallado de sesión
        error_log("🔑 Sesión establecida:");
        error_log("   - Usuario ID: {$datos['id_usuario']}");
        error_log("   - Email: {$datos['email']}");
        error_log("   - Rol: {$_SESSION['rol_id']}");
        error_log("   - Login Time: {$_SESSION['login_time']} (" . $_SESSION['login_date'] . ")");
        error_log("   - IP: {$_SESSION['ip_login']}");
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Calcular tiempo de sesión
        $duracion = null;
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        
        if (isset($_SESSION['login_time'])) {
            $duracion = time() - $_SESSION['login_time'];
            $duracion_texto = formatearTiempo($duracion);
            error_log("🔓 Usuario {$usuario_id} estuvo conectado: {$duracion_texto}");
        }
        
        // Registrar fin de sesión
        if ($usuario_id) {
            try {
                $usuarioModel = new Usuario();
                $usuarioModel->registrarFinSesion($usuario_id, $duracion);
            } catch (Exception $e) {
                error_log("❌ Error registrando fin de sesión: " . $e->getMessage());
            }
        }
        
        // Limpiar sesión completamente
        session_destroy();
        
        // Redirigir a inicio
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }

    /**
     * Mostrar formulario de registro
     */
    public function registroGet() {
        // Si ya está logueado, redirigir
        if ($this->verificarSesionActiva()) {
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }
        
        require BASE_PATH . '/views/register.php';
    }

    public function registroPost() {
        header('Content-Type: application/json');

        $dni = $_POST['dni'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            // Validaciones básicas
            if (empty($email) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email y contraseña son requeridos'
                ]);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email inválido'
                ]);
                exit;
            }

            $usuarioModel = new Usuario();
            
            // Verificar si el email ya existe
            if ($usuarioModel->existeEmail($email)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ]);
                exit;
            }

            // Registrar usuario tradicional
            $resultado = $usuarioModel->registrarTradicional($email, $password);

            if ($resultado) {
                // Actualizar datos personales si se proporcionaron
                if (!empty($nombre) || !empty($apellido)) {
                    $usuarioModel->actualizarDatos($resultado['id_usuario'], [
                        'nombres' => $nombre,
                        'apellidos' => $apellido
                    ]);
                }

                // ✅ ESTABLECER SESIÓN AUTOMÁTICAMENTE
                $datosUsuario = $usuarioModel->obtenerDatosCompletos($resultado['id_usuario']);
                if ($datosUsuario) {
                    $this->establecerSesion($datosUsuario);
                    $usuarioModel->registrarInicioSesion($datosUsuario['id_usuario']);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Registro exitoso'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al registrar el usuario'
                ]);
            }

        } catch (Exception $e) {
            error_log("❌ Error en registro: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error en el registro: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Consulta DNI en API externa
     */
    private function consultaDNI() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!isset($_GET['dni']) || !preg_match('/^\d{8}$/', $_GET['dni'])) {
                $this->sendErrorResponse(400, 'DNI inválido. Debe contener exactamente 8 dígitos.');
                return;
            }

            $dni = $_GET['dni'];
            $token = 'apis-token-16209.4jn7mUQ93GRnE1lHfPq1eQ20s0Ywir8P';
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.apis.net.pe/v2/reniec/dni?numero=' . $dni,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DNI Validator/1.0)',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token,
                    'Referer: https://apis.net.pe/consulta-dni-api'
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($curlError) {
                $this->sendErrorResponse(500, 'Error de conexión: ' . $curlError);
                return;
            }

            if ($httpCode !== 200) {
                $this->sendErrorResponse($httpCode, "Error del servidor externo (HTTP $httpCode)");
                return;
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Respuesta no es JSON válido: " . substr($response, 0, 200));
                $this->sendErrorResponse(502, 'Respuesta inválida del servicio externo');
                return;
            }

            if (isset($data['error'])) {
                $this->sendErrorResponse(400, $data['error']);
                return;
            }

            if (!isset($data['nombres']) || !isset($data['apellidoPaterno'])) {
                $this->sendErrorResponse(404, 'DNI no encontrado o datos incompletos');
                return;
            }

            echo json_encode([
                'success' => true,
                'nombres' => trim($data['nombres']),
                'apellidoPaterno' => trim($data['apellidoPaterno']),
                'apellidoMaterno' => trim($data['apellidoMaterno'] ?? ''),
                'numeroDocumento' => $dni
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en consultaDNI: " . $e->getMessage());
            $this->sendErrorResponse(500, 'Error interno del servidor');
        }
    }

    private function verificarSesionActiva() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }

    private function obtenerUrlRedirect($rol_id) {
        switch ((int)$rol_id) {
            case 4: // Administrador
                return BASE_URL . '/index.php?accion=admin_panel';
            case 3: // Docente
                return BASE_URL . '/index.php?accion=clases_asignadas';
            case 2: // Estudiante
                return BASE_URL . '/index.php?accion=clases';
            default: // Usuario básico
                return BASE_URL . '/index.php';
        }
    }
    private function mostrarError($mensaje, $url) {
        echo "<script>";
        echo "alert('" . addslashes($mensaje) . "');";
        echo "window.location.href='" . $url . "';";
        echo "</script>";
        exit();
    }

    /**
     * Enviar respuesta de error JSON
     */
    private function sendErrorResponse($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
    }

    public function obtenerInfoSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }
        
        $tiempoConectado = isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : 0;
        
        return [
            'usuario_id' => $_SESSION['usuario_id'],
            'email' => $_SESSION['email'],
            'rol_id' => $_SESSION['rol_id'],
            'nombres' => $_SESSION['nombres'] ?? '',
            'apellidos' => $_SESSION['apellidos'] ?? '',
            'login_time' => $_SESSION['login_time'] ?? null,
            'login_date' => $_SESSION['login_date'] ?? null,
            'tiempo_conectado' => $tiempoConectado,
            'tiempo_conectado_texto' => formatearTiempo($tiempoConectado),
            'ip_login' => $_SESSION['ip_login'] ?? 'unknown'
        ];
    }

    public static function requiereAuth($rol_minimo = 1) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si está logueado
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/index.php?accion=login');
            exit();
        }
        
        // Verificar rol mínimo
        $rol_usuario = $_SESSION['rol_id'] ?? 1;
        if ($rol_usuario < $rol_minimo) {
            header('Location: ' . BASE_URL . '/index.php?error=sin_permisos');
            exit();
        }
        
        // Actualizar última actividad
        try {
            $usuarioModel = new Usuario();
            $usuarioModel->actualizarUltimaActividad($_SESSION['usuario_id']);
        } catch (Exception $e) {
            error_log("❌ Error actualizando actividad: " . $e->getMessage());
        }
        
        return true;
    }
}