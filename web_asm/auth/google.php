<?php
use Google\Service\Oauth2;

require_once(__DIR__ . '/../config/constants.php');
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/models/Usuario.php';

function establecerSesionOAuth($datos) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['usuario_id'] = $datos['id_usuario'];
    $_SESSION['email'] = $datos['email'];
    $_SESSION['rol_id'] = $datos['rol_prioritario'] ?? $datos['id_rol'] ?? 1;
    $_SESSION['rol_nombre'] = $datos['rol_nombre'] ?? 'Usuario';
    
    $_SESSION['login_time'] = time();
    $_SESSION['login_date'] = date('Y-m-d H:i:s');
    $_SESSION['ip_login'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $_SESSION['nombres'] = $datos['nombres'] ?? '';
    $_SESSION['apellidos'] = $datos['apellidos'] ?? '';
    $_SESSION['email_verificado'] = $datos['email_verificado'] ?? true;
    
    error_log("🔑 Sesión OAuth establecida para usuario {$datos['id_usuario']} a las " . $_SESSION['login_date']);
}

function obtenerUrlRedirectOAuth($rol_id) {
    switch ((int)$rol_id) {
        case 4:
            return BASE_URL . '/index.php';
        case 3:
            return BASE_URL . '/index.php';
        case 2:
            return BASE_URL . '/index.php';
        default:
            return BASE_URL . '/index.php';
    }
}

try {
    session_start();

    $client = new Google_Client();
    $client->setClientId('59149942943-1pfft8ievm0sh1o2fni3o8hcjcl40h6i.apps.googleusercontent.com');
    $client->setClientSecret('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');
    $client->setRedirectUri(BASE_URL . '/auth/google.php');
    $client->addScope('email');
    $client->addScope('profile');

    if (!isset($_GET['code'])) {
        $auth_url = $client->createAuthUrl();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        exit;
    } else {
        $client->authenticate($_GET['code']);
        $token = $client->getAccessToken();
        $client->setAccessToken($token);

        $oauth = new Oauth2($client);
        $userInfo = $oauth->userinfo->get();

        $email = $userInfo->email;
        $google_id = $userInfo->id;

        error_log("🔍 OAuth - Email: {$email}, Google ID: {$google_id}");

        $usuario = new Usuario();
        
        $existe = $usuario->verificarCredencialesOAuth($email, $google_id);

        if ($existe) {
            $datosCompletos = $usuario->obtenerDatosCompletos($existe['id_usuario']);
            
            if ($datosCompletos) {
                establecerSesionOAuth($datosCompletos);
                
                $usuario->registrarInicioSesion($existe['id_usuario']);
                
                error_log("✅ Login OAuth exitoso para: {$email} (ID: {$existe['id_usuario']})");
                
                $redirectUrl = obtenerUrlRedirectOAuth($existe['id_rol']);
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                error_log("❌ Error obteniendo datos completos para usuario {$existe['id_usuario']}");
                header('Location: ' . BASE_URL . '/index.php?accion=login&error=servidor');
                exit;
            }
        } else {
            $resultado = $usuario->registrarOAuth($email, $google_id);
            
            if ($resultado && $resultado['id_usuario'] > 0) {
                $datosCompletos = $usuario->obtenerDatosCompletos($resultado['id_usuario']);
                
                if ($datosCompletos) {
                    establecerSesionOAuth($datosCompletos);
                    
                    $usuario->registrarInicioSesion($resultado['id_usuario']);
                    
                    error_log("✅ Registro OAuth exitoso para: {$email} (ID: {$resultado['id_usuario']})");
                    
                    $redirectUrl = obtenerUrlRedirectOAuth($resultado['id_rol']);
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    error_log("❌ Error obteniendo datos completos para usuario recién creado {$resultado['id_usuario']}");
                    header('Location: ' . BASE_URL . '/index.php?accion=login&error=registro_fallido');
                    exit;
                }
            } else {
                error_log("❌ Error en registro OAuth para: {$email}");
                header('Location: ' . BASE_URL . '/index.php?accion=login&error=registro_fallido');
                exit;
            }
        }
    }

} catch (Google_Service_Exception $e) {
    error_log("❌ Error de Google OAuth: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/index.php?accion=login&error=servidor');
    exit;
} catch (Exception $e) {
    error_log("❌ Error general en OAuth: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/index.php?accion=login&error=servidor');
    exit;
}
?>