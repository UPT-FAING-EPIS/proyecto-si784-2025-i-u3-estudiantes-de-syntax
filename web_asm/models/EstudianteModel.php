<?php
require_once BASE_PATH . '/config/Database.php';

class EstudianteModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function verificarUsuarioVinculado($codigoEstudiante) {
        try {
            $sql = "SELECT e.id_estudiante
                    FROM estudiantes e 
                    WHERE e.codigo_estudiante = ?";
            
            $resultado = $this->db->fetchOne($sql, [$codigoEstudiante]);
            return $resultado !== false;
        } catch (PDOException $e) {
            error_log("Error en verificarUsuarioVinculado: " . $e->getMessage());
            return false;
        }
    }

    public function registrarEstudianteCompleto($usuarioId, $datosEstudiante, $cursos) {
        try {
            error_log("🔍 Iniciando registrarEstudianteCompleto para usuario: $usuarioId");
            
            $this->db->beginTransaction();

            // 1. Actualizar datos personales
            error_log("📝 Actualizando datos personales...");
            $this->actualizarDatosPersonales($usuarioId, $datosEstudiante);

            // 2. Registrar o actualizar estudiante
            error_log("👨‍🎓 Registrando estudiante...");
            $idEstudiante = $this->registrarEstudiante($usuarioId, $datosEstudiante);
            error_log("✅ Estudiante registrado con ID: $idEstudiante");

            // 3. Registrar cursos
            error_log("📚 Registrando cursos (" . count($cursos) . " cursos)...");
            $this->registrarCursos($cursos);

            // 4. Registrar relación estudiante-cursos
            error_log("🔗 Registrando relación estudiante-cursos...");
            $this->registrarEstudianteCursos($idEstudiante, $cursos);

            // 5. Actualizar rol del usuario
            error_log("🔄 Actualizando rol del usuario...");
            $this->actualizarRolUsuario($usuarioId, 2); // Rol estudiante

            $this->db->commit();
            error_log("✅ Registro completo exitoso para estudiante ID: $idEstudiante");

            return [
                'success' => true,
                'mensaje' => 'Estudiante registrado exitosamente',
                'id_estudiante' => $idEstudiante
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("❌ Error en registrarEstudianteCompleto: " . $e->getMessage());
            error_log("📍 Trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'mensaje' => 'Error al registrar estudiante: ' . $e->getMessage()
            ];
        }
    }

    private function actualizarDatosPersonales($usuarioId, $datosEstudiante) {
        // Obtener el id_datos_personales del usuario
        $sql = "SELECT id_datos_personales FROM usuarios WHERE id_usuario = ?";
        $resultado = $this->db->fetchOne($sql, [$usuarioId]);
        
        if (!$resultado) {
            throw new Exception('Usuario no encontrado');
        }

        $idDatosPersonales = $resultado['id_datos_personales'];

        // Actualizar datos personales
        $sql = "UPDATE datos_personales 
                SET nombres = ?, 
                    apellidos = ?, 
                    updated_at = NOW()
                WHERE id_datos_personales = ?";
        
        $this->db->execute($sql, [
            $datosEstudiante['nombres'],
            $datosEstudiante['apellidos'],
            $idDatosPersonales
        ]);
    }

    private function registrarEstudiante($usuarioId, $datosEstudiante) {
        error_log("🎓 Iniciando registrarEstudiante - Usuario ID: $usuarioId");
        
        // Verificar que el usuario existe
        $sqlUsuario = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
        $usuario = $this->db->fetchOne($sqlUsuario, [$usuarioId]);
        
        if (!$usuario) {
            throw new Exception("Usuario con ID $usuarioId no encontrado");
        }
        
        error_log("👤 Usuario verificado: ID=$usuarioId");
        
        // Verificar si ya existe un registro de estudiante
        $sql = "SELECT id_estudiante FROM estudiantes WHERE id_usuario = ?";
        $estudiante = $this->db->fetchOne($sql, [$usuarioId]);
        
        error_log("🔍 Búsqueda de estudiante existente: " . ($estudiante ? "ENCONTRADO ID=" . $estudiante['id_estudiante'] : "NO ENCONTRADO"));

        if ($estudiante) {
            // Actualizar estudiante existente
            error_log("🔄 Actualizando estudiante existente con ID: " . $estudiante['id_estudiante']);
            $sql = "UPDATE estudiantes 
                    SET codigo_estudiante = ?, 
                        carrera = ?, 
                        estado_academico = 1,
                        puede_solicitar_mentoria = 1,
                        updated_at = NOW()
                    WHERE id_usuario = ?";
            
            $filasAfectadas = $this->db->execute($sql, [
                $datosEstudiante['codigo_estudiante'],
                $datosEstudiante['carrera'],
                $usuarioId
            ]);
            
            error_log("✅ Estudiante actualizado - Filas afectadas: $filasAfectadas");
            return $estudiante['id_estudiante'];
        } else {
            // Verificar que no exista otro estudiante con el mismo código
            $sqlCodigo = "SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = ?";
            $estudianteExistente = $this->db->fetchOne($sqlCodigo, [$datosEstudiante['codigo_estudiante']]);
            
            if ($estudianteExistente) {
                throw new Exception("Ya existe un estudiante con el código: " . $datosEstudiante['codigo_estudiante']);
            }
            
            // Crear nuevo estudiante
            error_log("➕ Creando nuevo estudiante...");
            error_log("📋 Datos a insertar: " . json_encode($datosEstudiante, JSON_UNESCAPED_UNICODE));
            
            $sql = "INSERT INTO estudiantes 
                    (id_usuario, codigo_estudiante, carrera, estado_academico, puede_solicitar_mentoria) 
                    VALUES (?, ?, ?, 1, 1)";
            
            try {
                $filasAfectadas = $this->db->execute($sql, [
                    $usuarioId,
                    $datosEstudiante['codigo_estudiante'],
                    $datosEstudiante['carrera']
                ]);
                
                error_log("📊 Filas afectadas en INSERT: $filasAfectadas");

                if ($filasAfectadas === 0) {
                    throw new Exception("INSERT no afectó ninguna fila");
                }

                $idEstudiante = $this->db->getLastInsertId();
                error_log("🆔 Last Insert ID obtenido: " . ($idEstudiante ?: 'NULL/FALSE'));
                
                // Verificar que se insertó correctamente
                if (!$idEstudiante || $idEstudiante == 0) {
                    // Intentar buscar el estudiante que acabamos de crear
                    error_log("⚠️ LastInsertId falló, intentando buscar el registro creado...");
                    $sqlBuscar = "SELECT id_estudiante FROM estudiantes WHERE id_usuario = ? ORDER BY created_at DESC LIMIT 1";
                    $estudianteCreado = $this->db->fetchOne($sqlBuscar, [$usuarioId]);
                    
                    if ($estudianteCreado) {
                        error_log("✅ Estudiante encontrado por búsqueda: ID=" . $estudianteCreado['id_estudiante']);
                        return $estudianteCreado['id_estudiante'];
                    } else {
                        error_log("❌ No se pudo encontrar el estudiante creado");
                        
                        // Verificar si se insertó algo
                        $countSql = "SELECT COUNT(*) as total FROM estudiantes WHERE id_usuario = ?";
                        $count = $this->db->fetchOne($countSql, [$usuarioId]);
                        error_log("📊 Registros de estudiante para usuario $usuarioId: " . ($count['total'] ?? 0));
                        
                        throw new Exception('Error al obtener el ID del estudiante creado');
                    }
                }
                
                error_log("✅ Estudiante creado exitosamente con ID: $idEstudiante");
                return $idEstudiante;
                
            } catch (Exception $e) {
                error_log("❌ Error en INSERT de estudiante: " . $e->getMessage());
                throw $e;
            }
        }
    }

    private function registrarCursos($cursos) {
        foreach ($cursos as $curso) {
            $sql = "INSERT INTO cursos (codigo_curso, nombre, creditos, activo) 
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    creditos = VALUES(creditos),
                    updated_at = NOW()";
            
            $this->db->execute($sql, [
                $curso['codigo'],
                $curso['nombre'],
                $curso['creditos']
            ]);
        }
    }

    private function registrarEstudianteCursos($idEstudiante, $cursos) {
        // Verificar que el ID del estudiante sea válido
        if (!$idEstudiante || $idEstudiante == 0) {
            throw new Exception('ID de estudiante inválido: ' . $idEstudiante);
        }
        
        // Verificar que el estudiante existe
        $sql = "SELECT id_estudiante FROM estudiantes WHERE id_estudiante = ?";
        $estudianteExiste = $this->db->fetchOne($sql, [$idEstudiante]);
        
        if (!$estudianteExiste) {
            throw new Exception('El estudiante con ID ' . $idEstudiante . ' no existe');
        }
        
        foreach ($cursos as $curso) {
            // Obtener ID del curso
            $sql = "SELECT id_curso FROM cursos WHERE codigo_curso = ?";
            $cursoData = $this->db->fetchOne($sql, [$curso['codigo']]);
            
            if (!$cursoData) {
                error_log("Curso no encontrado: " . $curso['codigo']);
                continue; // Si no encuentra el curso, continúa con el siguiente
            }

            $idCurso = $cursoData['id_curso'];

            // Determinar si necesita mentoría (promedio < 11)
            $necesitaMentoria = ($curso['promedio'] < 11.0) ? 1 : 0;

            // Insertar o actualizar relación estudiante-curso
            $sql = "INSERT INTO estudiante_cursos 
                    (id_estudiante, id_curso, ponderado, estado_curso, necesita_mentoria) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    ponderado = VALUES(ponderado),
                    estado_curso = VALUES(estado_curso),
                    necesita_mentoria = VALUES(necesita_mentoria),
                    updated_at = NOW()";
            
            try {
                $this->db->execute($sql, [
                    $idEstudiante,
                    $idCurso,
                    $curso['promedio'],
                    $curso['estado'],
                    $necesitaMentoria
                ]);
                
                error_log("Curso registrado exitosamente - ID Estudiante: $idEstudiante, ID Curso: $idCurso, Promedio: " . $curso['promedio']);
                
            } catch (Exception $e) {
                error_log("Error registrando curso " . $curso['codigo'] . ": " . $e->getMessage());
                // Continuar con el siguiente curso en lugar de fallar completamente
                continue;
            }
        }

        // Actualizar promedio general del estudiante
        $this->actualizarPromedioGeneral($idEstudiante);
    }

    private function actualizarPromedioGeneral($idEstudiante) {
        $sql = "UPDATE estudiantes 
                SET promedio_general = (
                    SELECT AVG(ec.ponderado)
                    FROM estudiante_cursos ec
                    WHERE ec.id_estudiante = ?
                )
                WHERE id_estudiante = ?";
        
        $this->db->execute($sql, [$idEstudiante, $idEstudiante]);
    }

    private function actualizarRolUsuario($usuarioId, $nuevoRol) {
        // Verificar si ya tiene el rol
        $sql = "SELECT id_usuario_rol FROM usuario_roles 
                WHERE id_usuario = ? AND id_rol = ?";
        $rolExistente = $this->db->fetchOne($sql, [$usuarioId, $nuevoRol]);

        if (!$rolExistente) {
            // Insertar nuevo rol
            $sql = "INSERT INTO usuario_roles (id_usuario, id_rol, activo) 
                    VALUES (?, ?, 1)";
            $this->db->execute($sql, [$usuarioId, $nuevoRol]);
        } else {
            // Activar rol existente
            $sql = "UPDATE usuario_roles 
                    SET activo = 1, fecha_asignacion = NOW() 
                    WHERE id_usuario = ? AND id_rol = ?";
            $this->db->execute($sql, [$usuarioId, $nuevoRol]);
        }
    }

    public function obtenerEstudiantePorUsuario($usuarioId) {
        try {
            $sql = "SELECT 
                        e.id_estudiante,
                        e.codigo_estudiante,
                        e.carrera,
                        e.estado_academico,
                        e.promedio_general,
                        e.puede_solicitar_mentoria,
                        dp.nombres,
                        dp.apellidos,
                        u.email
                    FROM estudiantes e
                    INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    WHERE e.id_usuario = ?";
            
            return $this->db->fetchOne($sql, [$usuarioId]);
        } catch (PDOException $e) {
            error_log("Error en obtenerEstudiantePorUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerClasesEstudiante($idEstudiante) {
        try {
            $sql = "SELECT 
                        c.id_clase,
                        c.titulo,
                        c.descripcion,
                        c.estado,
                        c.fecha_programada,
                        c.fecha_inicio,
                        c.fecha_fin,
                        c.enlace_reunion,
                        c.estudiantes_inscritos,
                        c.capacidad_maxima,
                        cur.codigo_curso,
                        cur.nombre as curso_nombre,
                        dp.nombres as mentor_nombres,
                        dp.apellidos as mentor_apellidos,
                        i.fecha_inscripcion,
                        i.activa
                    FROM inscripciones i
                    INNER JOIN clases c ON i.id_clase = c.id_clase
                    INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                    LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
                    LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
                    LEFT JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    WHERE i.id_estudiante = ? AND i.activa = 1
                    ORDER BY c.fecha_programada DESC, c.created_at DESC";
            
            return $this->db->fetchAll($sql, [$idEstudiante]);
        } catch (PDOException $e) {
            error_log("Error en obtenerClasesEstudiante: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerCursosNecesitanMentoria($idEstudiante) {
        try {
            $sql = "SELECT 
                        c.id_curso,
                        c.codigo_curso,
                        c.nombre,
                        c.creditos,
                        ec.ponderado,
                        ec.estado_curso,
                        ec.necesita_mentoria
                    FROM estudiante_cursos ec
                    INNER JOIN cursos c ON ec.id_curso = c.id_curso
                    WHERE ec.id_estudiante = ? AND ec.necesita_mentoria = 1
                    ORDER BY ec.ponderado ASC";
            
            return $this->db->fetchAll($sql, [$idEstudiante]);
        } catch (PDOException $e) {
            error_log("Error en obtenerCursosNecesitanMentoria: " . $e->getMessage());
            return [];
        }
    }

    public function crearOInscribirClase($idCurso, $idEstudiante) {
        try {
            $sql = "CALL sp_crear_o_inscribir_clase(?, ?, @p_success, @p_message, @p_id_clase)";
            $this->db->execute($sql, [$idCurso, $idEstudiante]);

            // Obtener resultados del procedimiento almacenado
            $resultado = $this->db->fetchOne("SELECT @p_success as success, @p_message as mensaje, @p_id_clase as id_clase");

            return [
                'success' => (bool)$resultado['success'],
                'mensaje' => $resultado['mensaje'],
                'id_clase' => $resultado['id_clase']
            ];

        } catch (PDOException $e) {
            error_log("Error en crearOInscribirClase: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al crear o inscribir en clase: ' . $e->getMessage()
            ];
        }
    }

    public function obtenerCursosEstudiante($idEstudiante) {
        try {
            $sql = "SELECT 
                        c.id_curso,
                        c.codigo_curso,
                        c.nombre,
                        c.creditos,
                        ec.ponderado,
                        ec.estado_curso,
                        ec.necesita_mentoria,
                        ec.fecha_ultima_actualizacion
                    FROM estudiante_cursos ec
                    INNER JOIN cursos c ON ec.id_curso = c.id_curso
                    WHERE ec.id_estudiante = ?
                    ORDER BY ec.necesita_mentoria DESC, ec.ponderado ASC";
            
            return $this->db->fetchAll($sql, [$idEstudiante]);
        } catch (PDOException $e) {
            error_log("Error en obtenerCursosEstudiante: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEstadisticasEstudiante($idEstudiante) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_cursos,
                        SUM(CASE WHEN ec.necesita_mentoria = 1 THEN 1 ELSE 0 END) as cursos_necesitan_mentoria,
                        AVG(ec.ponderado) as promedio_general,
                        MAX(ec.ponderado) as nota_maxima,
                        MIN(ec.ponderado) as nota_minima
                    FROM estudiante_cursos ec
                    WHERE ec.id_estudiante = ?";
            
            return $this->db->fetchOne($sql, [$idEstudiante]);
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticasEstudiante: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerHistorialClases($idEstudiante) {
        try {
            $sql = "SELECT 
                        c.id_clase,
                        c.titulo,
                        c.descripcion,
                        c.estado,
                        c.fecha_programada,
                        c.fecha_inicio,
                        c.fecha_fin,
                        cur.codigo_curso,
                        cur.nombre as curso_nombre,
                        dp.nombres as mentor_nombres,
                        dp.apellidos as mentor_apellidos,
                        i.fecha_inscripcion,
                        CASE 
                            WHEN c.estado = 4 THEN 'Finalizada'
                            WHEN c.estado = 3 THEN 'En Proceso'
                            WHEN c.estado = 2 THEN 'Activa'
                            WHEN c.estado = 1 THEN 'Pendiente'
                            WHEN c.estado = 5 THEN 'Cerrada'
                            ELSE 'Desconocido'
                        END as estado_texto
                    FROM inscripciones i
                    INNER JOIN clases c ON i.id_clase = c.id_clase
                    INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                    LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
                    LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
                    LEFT JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    WHERE i.id_estudiante = ?
                    ORDER BY c.fecha_programada DESC";
            
            return $this->db->fetchAll($sql, [$idEstudiante]);
        } catch (PDOException $e) {
            error_log("Error en obtenerHistorialClases: " . $e->getMessage());
            return [];
        }
    }
        /**
     * Obtiene las clases donde el estudiante está inscrito usando el procedimiento almacenado
     */
    public function obtenerClasesEstudianteInscrito($idUsuario) {
        try {
            $sql = "CALL sp_obtener_clases_estudiante_inscrito(?)"; 
            return $this->db->fetchAll($sql, [$idUsuario]);
        } catch (PDOException $e) {
            error_log("Error en obtenerClasesEstudianteInscrito: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las clases disponibles para inscripción usando el procedimiento almacenado
     */
    public function obtenerClasesDisponiblesParaInscripcion($idUsuario) {
        try {
            $sql = "CALL sp_obtener_clases_disponibles_para_inscripcion(?)"; 
            return $this->db->fetchAll($sql, [$idUsuario]);
        } catch (PDOException $e) {
            error_log("Error en obtenerClasesDisponiblesParaInscripcion: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Método auxiliar para obtener el ID de estudiante por ID de usuario
     */
    public function obtenerIdEstudiantePorUsuario($idUsuario) {
        try {
            $sql = "SELECT id_estudiante FROM estudiantes WHERE id_usuario = ?";
            $resultado = $this->db->fetchOne($sql, [$idUsuario]);
            return $resultado ? $resultado['id_estudiante'] : null;
        } catch (PDOException $e) {
            error_log("Error en obtenerIdEstudiantePorUsuario: " . $e->getMessage());
            return null;
        }
    }
}