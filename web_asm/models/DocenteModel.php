<?php
require_once BASE_PATH . '/config/Database.php';

class DocenteModel {
    
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerIdDocente($usuarioId) {
        try {
            return $this->db->fetchOne("
                SELECT m.id_mentor as ID_DOCENTE, m.id_usuario as ID_USUARIO
                FROM mentores m 
                WHERE m.id_usuario = ?
            ", [$usuarioId]);
        } catch (Exception $e) {
            error_log("Error en obtenerIdDocente: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerClasesAsignadas($idDocente) {
        try {
            $clases = $this->db->fetchAll("
                SELECT 
                    c.id_clase as ID_CLASE,
                    c.titulo as TITULO,
                    c.descripcion as DESCRIPCION,
                    c.estado as ESTADO,
                    c.fecha_programada as FECHA_PROGRAMADA,
                    c.fecha_inicio as FECHA_INICIO,
                    c.fecha_fin as FECHA_FIN,
                    c.capacidad_maxima as CAPACIDAD,
                    c.enlace_reunion as ENLACE,
                    cur.nombre as NOMBRE_CURSO,
                    cur.codigo_curso as CODIGO_CURSO,
                    COALESCE(COUNT(DISTINCT i.id_estudiante), 0) as PARTICIPANTES
                FROM clases c
                INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                LEFT JOIN inscripciones i ON c.id_clase = i.id_clase AND i.activa = 1
                WHERE c.id_mentor = ?
                GROUP BY 
                    c.id_clase, c.titulo, c.descripcion, c.estado, 
                    c.fecha_programada, c.fecha_inicio, c.fecha_fin, 
                    c.capacidad_maxima, c.enlace_reunion, cur.nombre, cur.codigo_curso
                ORDER BY 
                    c.estado DESC,
                    c.fecha_programada DESC
            ", [$idDocente]);

            // ... existing code ...
            foreach ($clases as &$clase) {
                $clase['PORCENTAJE_OCUPACION'] = $clase['CAPACIDAD'] > 0 
                    ? round(($clase['PARTICIPANTES'] / $clase['CAPACIDAD']) * 100, 1) 
                    : 0;
                if ($clase['FECHA_INICIO']) {
                    $clase['FECHA_INICIO_FORMATEADA'] = date('d/m/Y H:i', strtotime($clase['FECHA_INICIO']));
                }
                if ($clase['FECHA_FIN']) {
                    $clase['FECHA_FIN_FORMATEADA'] = date('d/m/Y H:i', strtotime($clase['FECHA_FIN']));
                }
            }

            return $clases;
            
        } catch (Exception $e) {
            error_log("Error en obtenerClasesAsignadas: " . $e->getMessage());
            return [];
        }
    }

    public function verificarPermisosClase($idClase, $usuarioId) {
        $resultado = $this->db->fetchOne("
            SELECT COUNT(*) as count, m.id_mentor as ID_DOCENTE
            FROM clases c
            INNER JOIN mentores m ON c.id_mentor = m.id_mentor
            WHERE c.id_clase = ? AND m.id_usuario = ?
        ", [$idClase, $usuarioId]);

        return $resultado['count'] > 0 ? $resultado : false;
    }

    public function cerrarClase($idClase) {
        try {
            $this->db->beginTransaction();
            
            $filasAfectadas = $this->db->execute("
                UPDATE clases 
                SET estado = 5, fecha_fin = NOW() 
                WHERE id_clase = ?
            ", [$idClase]);

            if ($filasAfectadas > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            return false;
        }
    }

    public function iniciarClase($idClase, $enlace) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que la clase esté en estado ACTIVO (2)
            $clase = $this->db->fetchOne("
                SELECT estado 
                FROM clases 
                WHERE id_clase = ?
            ", [$idClase]);
            
            if (!$clase || $clase['estado'] != 2) {
                $this->db->rollback();
                return false;
            }
            
            // Cambiar estado a EN_PROCESO (3) y actualizar enlace
            $filasAfectadas = $this->db->execute("
                UPDATE clases 
                SET enlace_reunion = ?, 
                    fecha_inicio = NOW(), 
                    estado = 3,
                    updated_at = NOW()
                WHERE id_clase = ?
            ", [$enlace, $idClase]);
            
            if ($filasAfectadas > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Error en iniciarClase: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstudiantesClase($idClase) {
        return $this->db->fetchAll("
            SELECT 
                e.id_estudiante as ID_ESTUDIANTE,
                dp.nombres as NOMBRE,
                dp.apellidos as APELLIDO,
                e.codigo_estudiante as CODIGO,
                u.email as EMAIL_CORPORATIVO,
                0 as CALIFICACION_ACTUAL
            FROM inscripciones i
            INNER JOIN estudiantes e ON i.id_estudiante = e.id_estudiante
            INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
            WHERE i.id_clase = ? AND i.activa = 1
            ORDER BY dp.apellidos, dp.nombres
        ", [$idClase]);
    }

    public function obtenerInfoClaseParaDiscord($idClase) {
        return $this->db->fetchOne("
            SELECT 
                c.id_clase as ID_CLASE,
                cur.codigo_curso as CODIGO_CURSO,
                cur.nombre as NOMBRE_CURSO,
                'Disponible' as CICLO,
                c.capacidad_maxima as CAPACIDAD
            FROM clases c
            INNER JOIN cursos cur ON c.id_curso = cur.id_curso
            WHERE c.id_clase = ?
        ", [$idClase]);
    }

    public function calificarEstudiante($idDocente, $idEstudiante, $idClase, $calificacion, $observacion, $usuarioRegistrador) {
        try {
            $this->db->beginTransaction();

            // Nota: Las tablas registro_academico, unidad y notas no existen en ams.sql
            // Esta funcionalidad necesitaría ser rediseñada según el esquema actual
            // Por ahora, retornamos true para mantener compatibilidad
            
            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            return false;
        }
    }

    public function obtenerCalificacionesClase($idClase, $usuarioId) {
        // Esta función también necesita ser rediseñada según el esquema actual
        // Por ahora retornamos estructura básica
        $clase = $this->db->fetchOne("
            SELECT 
                c.id_clase as ID_CLASE,
                cur.nombre as NOMBRE_CURSO,
                cur.codigo_curso as CODIGO_CURSO,
                'Actual' as NOMBRE_CICLO
            FROM clases c
            INNER JOIN cursos cur ON c.id_curso = cur.id_curso
            INNER JOIN mentores m ON c.id_mentor = m.id_mentor
            WHERE c.id_clase = ? AND m.id_usuario = ?
        ", [$idClase, $usuarioId]);

        if (!$clase) {
            return false;
        }

        $calificaciones = [];

        return [
            'clase' => $clase,
            'calificaciones' => $calificaciones
        ];
    }

    public function obtenerInfoDocente($usuarioId) {
        return $this->db->fetchOne("
            SELECT 
                m.id_mentor as ID_DOCENTE,
                dp.nombres as NOMBRE,
                dp.apellidos as APELLIDO,
                u.email as EMAIL,
                dp.dni as DNI
            FROM mentores m
            INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
            WHERE u.id_usuario = ?
        ", [$usuarioId]);
    }

    public function obtenerClasesDisponibles() {
        try {
            $clases = $this->db->fetchAll("
                SELECT 
                    c.id_clase as ID_CLASE,
                    c.titulo as TITULO,
                    c.descripcion as RAZON,
                    c.capacidad_maxima as CAPACIDAD,
                    c.fecha_programada as FECHA_INICIO,
                    c.fecha_fin as FECHA_FIN,
                    cur.nombre as NOMBRE_CURSO,
                    cur.codigo_curso as CODIGO_CURSO,
                    'Disponible' as NOMBRE_CICLO,
                    'Actual' as SEMESTRE,
                    'Por definir' as HORARIO,
                    COALESCE(COUNT(DISTINCT i.id_estudiante), 0) as PARTICIPANTES
                FROM clases c
                INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                LEFT JOIN inscripciones i ON c.id_clase = i.id_clase AND i.activa = 1
                WHERE c.id_mentor IS NULL 
                  AND c.estado = 1
                GROUP BY 
                    c.id_clase, c.titulo, c.descripcion, c.capacidad_maxima,
                    c.fecha_programada, c.fecha_fin, cur.nombre, cur.codigo_curso
                ORDER BY c.fecha_programada ASC
            ");

            // ... existing code ...
            foreach ($clases as &$clase) {
                $clase['PORCENTAJE_OCUPACION'] = $clase['CAPACIDAD'] > 0 
                    ? round(($clase['PARTICIPANTES'] / $clase['CAPACIDAD']) * 100, 1) 
                    : 0;
                if ($clase['FECHA_INICIO']) {
                    $clase['FECHA_INICIO_FORMATEADA'] = date('d/m/Y', strtotime($clase['FECHA_INICIO']));
                }
                if ($clase['FECHA_FIN']) {
                    $clase['FECHA_FIN_FORMATEADA'] = date('d/m/Y', strtotime($clase['FECHA_FIN']));
                }
            }

            return $clases;
            
        } catch (Exception $e) {
            error_log("Error en obtenerClasesDisponibles: " . $e->getMessage());
            return [];
        }
    }

    public function tomarClase($idClase, $idDocente) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que la clase esté disponible
            $verificacion = $this->puedeTomarClase($idClase, $idDocente);
            
            if (!$verificacion['puede_tomar']) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => $verificacion['razon']
                ];
            }
            
            // Asignar el mentor a la clase
            $filasAfectadas = $this->db->execute("
                UPDATE clases 
                SET id_mentor = ?, updated_at = NOW()
                WHERE id_clase = ? AND id_mentor IS NULL
            ", [$idDocente, $idClase]);
            
            if ($filasAfectadas > 0) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => 'Clase tomada exitosamente. Ahora eres el mentor de esta clase.'
                ];
            } else {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'No se pudo tomar la clase. Es posible que otro mentor la haya tomado.'
                ];
            }
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Error en tomarClase: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    public function puedeTomarClase($idClase, $idDocente) {
        try {
            $resultado = $this->db->fetchOne("
                SELECT 
                    c.id_clase,
                    c.estado,
                    c.fecha_programada,
                    cur.nombre as NOMBRE_CURSO,
                    CASE 
                        WHEN c.id_mentor IS NOT NULL THEN 'YA_ASIGNADA'
                        WHEN c.estado != 1 THEN 'INACTIVA'
                        WHEN c.fecha_programada <= NOW() THEN 'YA_INICIADA'
                        ELSE 'DISPONIBLE'
                    END as ESTADO_DISPONIBILIDAD
                FROM clases c
                INNER JOIN cursos cur ON c.id_curso = cur.id_curso
                WHERE c.id_clase = ?
            ", [$idClase]);

            if (!$resultado) {
                return [
                    'puede_tomar' => false,
                    'razon' => 'Clase no encontrada'
                ];
            }

            $razones = [
                'YA_ASIGNADA' => 'Esta clase ya tiene un mentor asignado',
                'INACTIVA' => 'Esta clase no está activa',
                'YA_INICIADA' => 'Esta clase ya ha iniciado',
                'DISPONIBLE' => 'Clase disponible para tomar'
            ];

            if ($resultado['ESTADO_DISPONIBILIDAD'] === 'DISPONIBLE') {
                return [
                    'puede_tomar' => true,
                    'razon' => $razones['DISPONIBLE'],
                    'clase' => $resultado
                ];
            } else {
                return [
                    'puede_tomar' => false,
                    'razon' => $razones[$resultado['ESTADO_DISPONIBILIDAD']]
                ];
            }

        } catch (Exception $e) {
            error_log("Error en puedeTomarClase: " . $e->getMessage());
            return [
                'puede_tomar' => false,
                'razon' => 'Error interno del servidor'
            ];
        }
    }

    public function programarClase($idClase, $fechaInicio, $fechaFin) {
        try {
            $this->db->beginTransaction();
            
            // Verificar que la clase esté en estado PENDIENTE (1)
            $clase = $this->db->fetchOne("
                SELECT estado 
                FROM clases 
                WHERE id_clase = ?
            ", [$idClase]);
            
            if (!$clase || $clase['estado'] != 1) {
                $this->db->rollback();
                return false;
            }
            
            // Convertir fechas a formato datetime (agregar hora por defecto)
            $fechaInicioDatetime = $fechaInicio . ' 08:00:00'; // 8:00 AM por defecto
            $fechaFinDatetime = $fechaFin . ' 18:00:00'; // 6:00 PM por defecto
            
            // Actualizar las fechas y cambiar estado a ACTIVO (2)
            $filasAfectadas = $this->db->execute("
                UPDATE clases 
                SET fecha_programada = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?,
                    estado = 2,
                    updated_at = NOW()
                WHERE id_clase = ?
            ", [$fechaInicioDatetime, $fechaInicioDatetime, $fechaFinDatetime, $idClase]);
            
            if ($filasAfectadas > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Error en programarClase: " . $e->getMessage());
            return false;
        }
    }
}
?>