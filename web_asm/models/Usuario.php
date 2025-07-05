<?php
require_once BASE_PATH . '/config/Database.php';

/**
 * Formatea segundos a texto legible
 */
if (!function_exists('formatearTiempo')) {
    function formatearTiempo($segundos) {
        $dias = floor($segundos / 86400);
        $horas = floor(($segundos % 86400) / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segundos = $segundos % 60;
        
        $partes = [];
        
        if ($dias > 0) {
            $partes[] = $dias . ' día' . ($dias > 1 ? 's' : '');
        }
        if ($horas > 0) {
            $partes[] = $horas . ' hora' . ($horas > 1 ? 's' : '');
        }
        if ($minutos > 0) {
            $partes[] = $minutos . ' min';
        }
        if (empty($partes) || ($dias == 0 && $horas == 0)) {
            $partes[] = $segundos . ' seg';
        }
        
        return implode(', ', array_slice($partes, 0, 2));
    }
}

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Verifica credenciales de usuario (sistema antiguo)
     * NOTA: Necesitas migrar a la nueva estructura de BD
     */
    public function verificarCredenciales($email, $password) {
        // Para tu BD actual (ams_system):
        $sql = "SELECT u.id_usuario, u.password_hash, u.email, u.activo, u.email_verificado,
                       ur.id_rol, r.nombre AS rol_nombre, r.priority
                FROM usuarios u
                INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
                INNER JOIN roles r ON ur.id_rol = r.id_rol
                WHERE u.email = ? AND u.activo = 1 AND ur.activo = 1
                ORDER BY r.priority ASC
                LIMIT 1";
        
        $user = $this->db->fetchOne($sql, [$email]);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    /**
     * Verifica credenciales OAuth (Google)
     */
    public function verificarCredencialesOAuth($email, $google_id) {
        $sql = "SELECT u.id_usuario, u.email, u.activo, u.email_verificado,
                       ur.id_rol, r.nombre AS rol_nombre, r.priority
                FROM usuarios u
                INNER JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
                INNER JOIN roles r ON ur.id_rol = r.id_rol
                WHERE u.email = ? AND u.google_id = ? AND u.activo = 1 AND ur.activo = 1
                ORDER BY r.priority ASC
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [$email, $google_id]);
    }

    /**
     * Registra usuario usando el procedimiento almacenado
     */
    private function registrarUsuario($email, $passwordHash = null, $google_id = null) {
        try {
            $tipoRegistro = $google_id ? 'OAuth' : 'Tradicional';
            error_log("📌 Registrando usuario {$tipoRegistro}: {$email}");
            
            $stmt = $this->db->getConnection()->prepare("CALL sp_registrar_usuario(?, ?, ?, @p_id_usuario, @p_id_rol)");
            $stmt->execute([$email, $passwordHash, $google_id]);
            
            $result = $this->db->query("SELECT @p_id_usuario AS id_usuario, @p_id_rol AS id_rol")->fetch(PDO::FETCH_ASSOC);
            
            $user_id = (int) ($result['id_usuario'] ?? 0);
            $rol_id = (int) ($result['id_rol'] ?? 0);
            
            if ($user_id > 0 && $rol_id > 0) {
                error_log("✅ Usuario {$tipoRegistro} registrado - ID: {$user_id}, Rol: {$rol_id}");
                return ['id_usuario' => $user_id, 'id_rol' => $rol_id];
            } else {
                error_log("❌ Error en registro {$tipoRegistro} - IDs inválidos");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en registro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca usuario por credenciales OAuth
     */
    public function buscarPorCredenciales($email, $google_id) {
        $sql = "SELECT u.id_usuario, u.email, ur.id_rol, r.nombre AS rol
                FROM usuarios u
                JOIN usuario_roles ur ON ur.id_usuario = u.id_usuario
                JOIN roles r ON ur.id_rol = r.id_rol
                WHERE u.email = ? AND u.google_id = ? AND ur.activo = TRUE
                LIMIT 1";
        
        return $this->db->fetchOne($sql, [$email, $google_id]);
    }

    /**
     * Registra usuario OAuth
     */
    public function registrarOAuth($email, $google_id) {
        return $this->registrarUsuario($email, null, $google_id); 
    }

    /**
     * Registra usuario tradicional
     */
    public function registrarTradicional($email, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $this->registrarUsuario($email, $passwordHash, null);
    }

    /**
     * Obtiene usuario por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Actualiza contraseña de usuario
     */
    public function actualizarPassword($id, $password) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id_usuario = ?";
            $affectedRows = $this->db->execute($sql, [$hash, $id]);
            
            if ($affectedRows === 0) {
                throw new Exception("No se pudo actualizar la contraseña o el usuario no existe");
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene ID de estudiante por ID de usuario
     */
    public function obtenerIdEstudiante($id_usuario) {
        $sql = "SELECT id_estudiante FROM estudiantes WHERE id_usuario = ?";
        $result = $this->db->fetchOne($sql, [$id_usuario]);
        return $result ? $result['id_estudiante'] : null;
    }

    /**
     * Obtiene datos completos usando el procedimiento almacenado
     */
    public function obtenerDatosCompletos($id_usuario) {
        try {
            $stmt = $this->db->getConnection()->prepare("CALL sp_obtener_datos_completos(?)");
            $stmt->execute([$id_usuario]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $resultado ?: null;
            
        } catch (Exception $e) {
            error_log("❌ Error al obtener datos completos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza la última actividad del usuario
     */
    public function actualizarUltimaActividad($id_usuario, $ip_address = null) {
        try {
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            
            $sql = "UPDATE usuarios SET 
                        ultima_actividad = NOW(), 
                        updated_at = NOW() 
                    WHERE id_usuario = ?";
            
            $result = $this->db->execute($sql, [$id_usuario]);
            
            if ($result) {
                error_log("✅ Última actividad actualizada para usuario {$id_usuario} desde IP {$ip}");
            }
            
            return $result > 0;
            
        } catch (Exception $e) {
            error_log("❌ Error actualizando última actividad: " . $e->getMessage());
            return false;
        }
    }

    /**
     *  Obtiene estadísticas de tiempo de conexión
     */
    public function obtenerEstadisticasTiempo($id_usuario) {
        try {
            $sql = "SELECT 
                        u.created_at as fecha_registro,
                        u.ultima_actividad,
                        TIMESTAMPDIFF(SECOND, u.created_at, NOW()) as tiempo_total_cuenta,
                        CASE 
                            WHEN u.ultima_actividad IS NOT NULL 
                            THEN TIMESTAMPDIFF(SECOND, u.ultima_actividad, NOW())
                            ELSE NULL 
                        END as tiempo_inactivo,
                        dp.nombres,
                        dp.apellidos
                    FROM usuarios u
                    JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    WHERE u.id_usuario = ?";
            
            $resultado = $this->db->fetchOne($sql, [$id_usuario]);
            
            if ($resultado) {
                // Agregar texto formateado
                $resultado['tiempo_total_texto'] = formatearTiempo($resultado['tiempo_total_cuenta']);
                $resultado['tiempo_inactivo_texto'] = $resultado['tiempo_inactivo'] ? 
                    formatearTiempo($resultado['tiempo_inactivo']) : 'En línea';
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("❌ Error obteniendo estadísticas: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registra inicio de sesión
     */
    public function registrarInicioSesion($id_usuario, $ip_address = null, $user_agent = null) {
        try {
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $agent = $user_agent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            
            // Actualizar última actividad
            $this->actualizarUltimaActividad($id_usuario, $ip);
            
            // Log opcional: puedes crear tabla sesiones_log si quieres trackear todas las sesiones
            error_log("🔑 Usuario {$id_usuario} inició sesión desde {$ip}");
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error registrando inicio de sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra fin de sesión
     */
    public function registrarFinSesion($id_usuario, $duracion_segundos = null) {
        try {
            // Actualizar última actividad
            $this->actualizarUltimaActividad($id_usuario);
            
            if ($duracion_segundos) {
                $duracion_texto = formatearTiempo($duracion_segundos);
                error_log("🔓 Usuario {$id_usuario} cerró sesión después de {$duracion_texto}");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Error registrando fin de sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene roles de un usuario
     */
    public function obtenerRolesUsuario($id_usuario) {
        $sql = "SELECT r.id_rol, r.nombre, r.descripcion, ur.activo
                FROM roles r
                INNER JOIN usuario_roles ur ON r.id_rol = ur.id_rol
                WHERE ur.id_usuario = ? AND ur.activo = 1";
        
        return $this->db->fetchAll($sql, [$id_usuario]);
    }

    /**
     * Asigna rol a usuario
     */
    public function asignarRol($id_usuario, $id_rol) {
        try {
            $sql = "INSERT INTO usuario_roles (id_usuario, id_rol, fecha_asignacion, activo) 
                    VALUES (?, ?, NOW(), 1)
                    ON DUPLICATE KEY UPDATE activo = 1, fecha_asignacion = NOW()";
            
            return $this->db->execute($sql, [$id_usuario, $id_rol]) > 0;
            
        } catch (Exception $e) {
            error_log("❌ Error al asignar rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoca rol de usuario
     */
    public function revocarRol($id_usuario, $id_rol) {
        try {
            $sql = "UPDATE usuario_roles SET activo = 0 WHERE id_usuario = ? AND id_rol = ?";
            return $this->db->execute($sql, [$id_usuario, $id_rol]) > 0;
            
        } catch (Exception $e) {
            error_log("❌ Error al revocar rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si existe email
     */
    public function existeEmail($email) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = ?";
        $result = $this->db->fetchOne($sql, [$email]);
        return $result['total'] > 0;
    }

    /**
     * Actualiza datos personales
     */
    public function actualizarDatos($id_usuario, $datos) {
        try {
            // Obtener id_datos_personales
            $usuario = $this->obtenerPorId($id_usuario);
            if (!$usuario) {
                throw new Exception("Usuario no encontrado");
            }
            
            $campos = [];
            $valores = [];
            
            // Campos permitidos en la tabla datos_personales
            $camposPermitidos = ['nombres', 'apellidos', 'telefono', 'direccion', 'fecha_nacimiento', 'genero'];
            
            foreach ($datos as $campo => $valor) {
                if (in_array($campo, $camposPermitidos)) {
                    $campos[] = "$campo = ?";
                    $valores[] = $valor;
                }
            }
            
            if (empty($campos)) {
                throw new Exception("No hay campos válidos para actualizar");
            }
            
            $valores[] = $usuario['id_datos_personales'];
            $sql = "UPDATE datos_personales SET " . implode(', ', $campos) . ", updated_at = NOW() WHERE id_datos_personales = ?";
            
            return $this->db->execute($sql, $valores) > 0;
            
        } catch (Exception $e) {
            error_log("❌ Error al actualizar datos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista usuarios con filtros
     */
    public function listarUsuarios($filtros = [], $limite = 50, $offset = 0) {
        $sql = "SELECT u.id_usuario, u.email, u.email_verificado, u.activo, u.created_at,
                       dp.nombres, dp.apellidos, dp.telefono,
                       GROUP_CONCAT(r.nombre) as roles
                FROM usuarios u
                JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario AND ur.activo = 1
                LEFT JOIN roles r ON ur.id_rol = r.id_rol";
        
        $params = [];
        $condiciones = [];
        
        if (!empty($filtros['email'])) {
            $condiciones[] = "u.email LIKE ?";
            $params[] = '%' . $filtros['email'] . '%';
        }
        
        if (!empty($filtros['nombre'])) {
            $condiciones[] = "(dp.nombres LIKE ? OR dp.apellidos LIKE ?)";
            $params[] = '%' . $filtros['nombre'] . '%';
            $params[] = '%' . $filtros['nombre'] . '%';
        }
        
        if (!empty($filtros['rol'])) {
            $condiciones[] = "r.id_rol = ?";
            $params[] = $filtros['rol'];
        }
        
        if (!empty($filtros['activo'])) {
            $condiciones[] = "u.activo = ?";
            $params[] = $filtros['activo'];
        }
        
        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }
        
        $sql .= " GROUP BY u.id_usuario ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limite;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene usuarios conectados recientemente
     */
    public function obtenerUsuariosConectados($minutos = 30) {
        try {
            $sql = "SELECT u.id_usuario, u.email, u.ultima_actividad,
                           dp.nombres, dp.apellidos,
                           TIMESTAMPDIFF(MINUTE, u.ultima_actividad, NOW()) as minutos_inactivo
                    FROM usuarios u
                    JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    WHERE u.ultima_actividad IS NOT NULL 
                    AND u.ultima_actividad >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                    AND u.activo = 1
                    ORDER BY u.ultima_actividad DESC";
            
            return $this->db->fetchAll($sql, [$minutos]);
            
        } catch (Exception $e) {
            error_log("❌ Error obteniendo usuarios conectados: " . $e->getMessage());
            return [];
        }
    }
    public function obtenerEstadisticasGenerales() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_usuarios,
                        COUNT(CASE WHEN activo = 1 THEN 1 END) as usuarios_activos,
                        COUNT(CASE WHEN email_verificado = 1 THEN 1 END) as usuarios_verificados,
                        COUNT(CASE WHEN ultima_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as usuarios_activos_24h,
                        COUNT(CASE WHEN ultima_actividad >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as usuarios_activos_7d,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as usuarios_nuevos_30d
                    FROM usuarios";
            
            return $this->db->fetchOne($sql);
            
        } catch (Exception $e) {
            error_log("❌ Error obteniendo estadísticas: " . $e->getMessage());
            return null;
        }
    }
}