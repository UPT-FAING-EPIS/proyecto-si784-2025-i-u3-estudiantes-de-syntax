<?php
require_once '../../config/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_GET['id_clase'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de clase requerido']);
    exit;
}

$idClase = intval($_GET['id_clase']);

function obtenerDetallesClase($idClase) {
    try {
        $db = Database::getInstance();
        
        // Consulta principal para obtener datos de la clase
        $sql = "
            SELECT 
                c.id_clase,
                c.titulo,
                c.descripcion,
                c.capacidad_maxima,
                c.estudiantes_inscritos,
                c.estado,
                c.fecha_programada,
                c.fecha_inicio,
                c.fecha_fin,
                c.enlace_reunion,
                cur.codigo_curso,
                cur.nombre as nombre_curso,
                cur.creditos,
                CONCAT(dp.nombres, ' ', dp.apellidos) as nombre_mentor,
                m.especialidades,
                m.calificacion_promedio,
                m.total_clases_dadas,
                u.email as email_mentor
            FROM clases c
            INNER JOIN cursos cur ON c.id_curso = cur.id_curso
            LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
            LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
            LEFT JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
            WHERE c.id_clase = ?
        ";
        
        $clase = $db->fetchOne($sql, [$idClase]);
        
        if (!$clase) {
            return [
                'error' => 'Clase no encontrada'
            ];
        }
        
        // Obtener comentarios de la clase
        $sqlComentarios = "
            SELECT 
                com.puntuacion,
                com.comentario_texto,
                com.fecha_comentario,
                CONCAT(dp_est.nombres, ' ', dp_est.apellidos) as nombre_estudiante
            FROM comentarios com
            INNER JOIN estudiantes e ON com.id_estudiante = e.id_estudiante
            INNER JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
            INNER JOIN datos_personales dp_est ON u_est.id_datos_personales = dp_est.id_datos_personales
            WHERE com.id_clase = ?
            ORDER BY com.fecha_comentario DESC
            LIMIT 5
        ";
        
        $comentarios = $db->fetchAll($sqlComentarios, [$idClase]);
        
        // Calcular duración basada en fechas reales
        $duracion = null;
        if ($clase['fecha_inicio'] && $clase['fecha_fin']) {
            $inicio = new DateTime($clase['fecha_inicio']);
            $fin = new DateTime($clase['fecha_fin']);
            $diff = $inicio->diff($fin);
            if ($diff->h > 0 || $diff->i > 0) {
                $duracion = '';
                if ($diff->h > 0) $duracion .= $diff->h . ' horas ';
                if ($diff->i > 0) $duracion .= $diff->i . ' minutos';
                $duracion = trim($duracion);
            }
        }
        
        // Determinar modalidad basada en enlace de reunión
        $modalidad = !empty($clase['enlace_reunion']) ? 'Virtual' : 'Presencial';
        
        // Estados de la clase
        $estados = [
            1 => 'Pendiente',
            2 => 'Activo', 
            3 => 'En Proceso',
            4 => 'Finalizado',
            5 => 'Cerrada'
        ];
        
        // Preparar datos del mentor solo si existe
        $mentor = null;
        if ($clase['nombre_mentor']) {
            $mentor = [
                'nombre' => htmlspecialchars($clase['nombre_mentor']),
                'email' => $clase['email_mentor'] ? htmlspecialchars($clase['email_mentor']) : null,
                'especialidades' => $clase['especialidades'] ? htmlspecialchars($clase['especialidades']) : null,
                'calificacion_promedio' => $clase['calificacion_promedio'] ? number_format($clase['calificacion_promedio'], 1) : null,
                'total_clases_dadas' => $clase['total_clases_dadas'] ?: 0
            ];
        }
        
        return [
            'id_clase' => $clase['id_clase'],
            'titulo' => htmlspecialchars($clase['titulo']),
            'descripcion' => $clase['descripcion'] ? htmlspecialchars($clase['descripcion']) : null,
            'curso' => [
                'codigo' => htmlspecialchars($clase['codigo_curso']),
                'nombre' => htmlspecialchars($clase['nombre_curso']),
                'creditos' => $clase['creditos']
            ],
            'duracion' => $duracion,
            'modalidad' => $modalidad,
            'estado' => $estados[$clase['estado']] ?? 'Desconocido',
            'fecha_programada' => $clase['fecha_programada'] ? date('d/m/Y H:i', strtotime($clase['fecha_programada'])) : null,
            'fecha_inicio' => $clase['fecha_inicio'] ? date('d/m/Y H:i', strtotime($clase['fecha_inicio'])) : null,
            'fecha_fin' => $clase['fecha_fin'] ? date('d/m/Y H:i', strtotime($clase['fecha_fin'])) : null,
            'capacidad_maxima' => $clase['capacidad_maxima'],
            'estudiantes_inscritos' => $clase['estudiantes_inscritos'],
            'cupos_disponibles' => $clase['capacidad_maxima'] - $clase['estudiantes_inscritos'],
            'enlace_reunion' => $clase['enlace_reunion'] ? htmlspecialchars($clase['enlace_reunion']) : null,
            'mentor' => $mentor,
            'comentarios' => array_map(function($com) {
                return [
                    'estudiante' => htmlspecialchars($com['nombre_estudiante']),
                    'puntuacion' => $com['puntuacion'],
                    'comentario' => htmlspecialchars($com['comentario_texto']),
                    'fecha' => date('d/m/Y', strtotime($com['fecha_comentario']))
                ];
            }, $comentarios)
        ];
        
    } catch (Exception $e) {
        error_log("Error en obtenerDetallesClase: " . $e->getMessage());
        throw $e;
    }
}

try {
    $detalles = obtenerDetallesClase($idClase);
    echo json_encode($detalles, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage()
    ]);
}
?>