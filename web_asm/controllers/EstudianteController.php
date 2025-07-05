<?php
require_once BASE_PATH . '/core/BaseController.php';
require_once BASE_PATH . '/models/EstudianteModel.php';

class EstudianteController extends BaseController {
    private $estudianteModel;

    public function __construct() {
        $this->estudianteModel = new EstudianteModel();
    }

    public function handle($accion) {
        switch ($accion) {
            case 'vincular':
                $this->vincularGet();
                break;
            case 'buscar_estudiante':
                $this->buscar_estudiantePost();
                break;
            case 'mis_clases':
                $this->mis_clasesGet();
                break;
            case 'solicitar_clase':
                $this->solicitar_clasePost();
                break;
            case 'obtener_cursos_mentoria':
                $this->obtenerCursosConMentoria();
                break;
            default:
                http_response_code(404);
                echo "Acción no encontrada";
                break;
        }
    }

    public function vincularGet() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 1) {
            header('Location: ' . BASE_URL . '/index.php?accion=login');
            exit;
        }

        $mensaje = $_SESSION['mensaje'] ?? '';
        $tipo_mensaje = $_SESSION['tipo_mensaje'] ?? '';
        $error = $_SESSION['error'] ?? '';

        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje'], $_SESSION['error']);

        require_once BASE_PATH . '/views/usuario/vincularme.php';
    }

    public function buscar_estudiantePost() {
        try {
            if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 1) {
                header('Location: ' . BASE_URL . '/index.php?accion=login');
                exit;
            }

            $codigoEstudiante = trim($_POST['codigo_estudiante'] ?? '');
            $contraUPT = trim($_POST['contra_upt'] ?? '');

            if (empty($codigoEstudiante) || empty($contraUPT)) {
                throw new Exception('Código de estudiante y contraseña son obligatorios');
            }

            if (!preg_match('/^[0-9]{8,12}$/', $codigoEstudiante)) {
                throw new Exception('El código debe tener entre 8 y 12 dígitos');
            }

            // Verificar si el estudiante ya está vinculado
            $usuarioExistente = $this->estudianteModel->verificarUsuarioVinculado($codigoEstudiante);
            if ($usuarioExistente) {
                throw new Exception('Este código de estudiante ya está vinculado a otra cuenta');
            }

            // Validar credenciales con la API
            $datosAPI = $this->validarCredencialesAPI($codigoEstudiante, $contraUPT);
            
            if (!$datosAPI['success']) {
                throw new Exception($datosAPI['error'] ?? 'Credenciales incorrectas');
            }

            // Procesar vinculación
            $usuarioId = $_SESSION['usuario_id'];
            $resultado = $this->procesarVinculacion($usuarioId, $datosAPI);

            if ($resultado['success']) {
                // Actualizar datos de sesión
                $_SESSION['rol_id'] = 2; // Cambiar rol a estudiante
                $_SESSION['mensaje'] = '¡Vinculación exitosa! Ahora eres parte de la comunidad UPT.';
                $_SESSION['tipo_mensaje'] = 'success';
                
                header('Location: ' . BASE_URL . '/index.php?accion=inicio');
                exit;
            } else {
                throw new Exception($resultado['mensaje']);
            }

        } catch (Exception $e) {
            error_log("Error en buscar_estudiantePost: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/index.php?accion=vincular');
            exit;
        }
    }

    private function validarCredencialesAPI($codigo, $password) {
        $url = 'http://161.132.45.228:8000/scrape';
        
        $data = [
            'codigo' => $codigo,
            'password' => $password
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Error CURL: " . $error);
            return [
                'success' => false,
                'error' => 'Error de conexión con el servidor. Intenta más tarde.'
            ];
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("HTTP Error: " . $httpCode);
            return [
                'success' => false,
                'error' => 'Error del servidor. Código: ' . $httpCode
            ];
        }

        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Error al procesar la respuesta del servidor'
            ];
        }

        return $responseData;
    }

    private function procesarVinculacion($usuarioId, $datosAPI) {
        try {
            // Separar nombres y apellidos
            $nombreCompleto = $datosAPI['nombre'] ?? '';
            $partesNombre = $this->separarNombresApellidos($nombreCompleto);

            // Preparar datos del estudiante
            $datosEstudiante = [
                'codigo_estudiante' => $datosAPI['codigo'],
                'carrera' => $datosAPI['carrera'],
                'nombres' => $partesNombre['nombres'],
                'apellidos' => $partesNombre['apellidos']
            ];

            // Preparar cursos
            $cursos = $datosAPI['promedios_cursos'] ?? [];

            // Registrar estudiante y cursos
            $resultado = $this->estudianteModel->registrarEstudianteCompleto(
                $usuarioId,
                $datosEstudiante,
                $cursos
            );

            return $resultado;

        } catch (Exception $e) {
            error_log("Error en procesarVinculacion: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al procesar la vinculación: ' . $e->getMessage()
            ];
        }
    }

    private function separarNombresApellidos($nombreCompleto) {
        $partes = explode(',', $nombreCompleto);
        
        if (count($partes) >= 2) {
            return [
                'apellidos' => trim($partes[0]),
                'nombres' => trim($partes[1])
            ];
        }
        
        // Si no hay coma, intentar separar por espacios
        $palabras = explode(' ', trim($nombreCompleto));
        $totalPalabras = count($palabras);
        
        if ($totalPalabras >= 4) {
            // Asumimos que las primeras 2 son apellidos y el resto nombres
            $apellidos = implode(' ', array_slice($palabras, 0, 2));
            $nombres = implode(' ', array_slice($palabras, 2));
        } elseif ($totalPalabras === 3) {
            // Primer palabra apellido, resto nombres
            $apellidos = $palabras[0];
            $nombres = implode(' ', array_slice($palabras, 1));
        } else {
            // Solo dividir por la mitad
            $mitad = floor($totalPalabras / 2);
            $apellidos = implode(' ', array_slice($palabras, 0, $mitad));
            $nombres = implode(' ', array_slice($palabras, $mitad));
        }

        return [
            'apellidos' => $apellidos ?: 'Sin apellidos',
            'nombres' => $nombres ?: 'Sin nombres'
        ];
    }

    public function mis_clasesGet() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 2) {
            header('Location: ' . BASE_URL . '/index.php?accion=login');
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'];
        $estudiante = $this->estudianteModel->obtenerEstudiantePorUsuario($usuarioId);
        
        if (!$estudiante) {
            $_SESSION['error'] = 'No se encontraron datos del estudiante';
            header('Location: ' . BASE_URL . '/index.php?accion=inicio');
            exit;
        }

        $clases = $this->estudianteModel->obtenerClasesEstudiante($estudiante['id_estudiante']);
        $cursosConMentoria = $this->estudianteModel->obtenerCursosNecesitanMentoria($estudiante['id_estudiante']);

        require_once BASE_PATH . '/views/estudiante/mis_clases.php';
    }

    public function solicitar_clasePost() {
        try {
            if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 2) {
                header('Location: ' . BASE_URL . '/index.php?accion=login');
                exit;
            }

            $idCurso = intval($_POST['id_curso'] ?? 0);
            $usuarioId = $_SESSION['usuario_id'];

            if (!$idCurso) {
                throw new Exception('Curso no válido');
            }

            $estudiante = $this->estudianteModel->obtenerEstudiantePorUsuario($usuarioId);
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }

            // Verificar si el estudiante puede solicitar mentoría
            if (!$estudiante['puede_solicitar_mentoria']) {
                throw new Exception('No tienes permisos para solicitar mentoría');
            }

            // Crear o inscribir en clase
            $resultado = $this->estudianteModel->crearOInscribirClase($idCurso, $estudiante['id_estudiante']);

            if ($resultado['success']) {
                $_SESSION['mensaje'] = $resultado['mensaje'];
                $_SESSION['tipo_mensaje'] = 'success';
            } else {
                throw new Exception($resultado['mensaje']);
            }

        } catch (Exception $e) {
            error_log("Error en solicitar_clasePost: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?accion=mis_clases');
        exit;
    }

    public function obtenerCursosConMentoria() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 2) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'];
        $estudiante = $this->estudianteModel->obtenerEstudiantePorUsuario($usuarioId);
        
        if (!$estudiante) {
            http_response_code(404);
            echo json_encode(['error' => 'Estudiante no encontrado']);
            exit;
        }

        $cursos = $this->estudianteModel->obtenerCursosNecesitanMentoria($estudiante['id_estudiante']);
        
        header('Content-Type: application/json');
        echo json_encode($cursos);
    }
}