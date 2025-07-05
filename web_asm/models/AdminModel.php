<?php
require_once BASE_PATH . '/config/Database.php';

class AdminModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function obtenerMetricasGenerales() {
        try {
            // Total de usuarios activos
            $total_usuarios = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1"
            )['total'] ?? 0;
            
            // Usuarios del mes anterior para comparación
            $usuarios_mes_anterior = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM usuarios 
                 WHERE activo = 1 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['total'] ?? 0;

            // Estudiantes activos
            $estudiantes_activos = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM estudiantes e 
                 INNER JOIN usuarios u ON e.id_usuario = u.id_usuario 
                 WHERE u.activo = 1 AND e.estado_academico = 1"
            )['total'] ?? 0;
            
            // Estudiantes del mes anterior
            $estudiantes_mes_anterior = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM estudiantes e 
                 INNER JOIN usuarios u ON e.id_usuario = u.id_usuario 
                 WHERE u.activo = 1 AND e.estado_academico = 1
                 AND e.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND e.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['total'] ?? 0;

            // Docentes mentores activos
            $docentes_mentores = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM mentores m 
                 INNER JOIN usuarios u ON m.id_usuario = u.id_usuario 
                 WHERE u.activo = 1 AND m.puede_tomar_clase = 1"
            )['total'] ?? 0;
            
            // Docentes del mes anterior
            $docentes_mes_anterior = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM mentores m 
                 INNER JOIN usuarios u ON m.id_usuario = u.id_usuario 
                 WHERE u.activo = 1 AND m.puede_tomar_clase = 1
                 AND m.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND m.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['total'] ?? 0;

            // Sesiones programadas (PENDIENTE=1, ACTIVO=2, EN_PROCESO=3)
            $sesiones_programadas = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM clases WHERE estado IN (1, 2, 3)"
            )['total'] ?? 0;
            
            // Sesiones del mes anterior
            $sesiones_mes_anterior = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM clases 
                 WHERE estado IN (1, 2, 3)
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['total'] ?? 0;

            $metricas = [
                'total_usuarios' => $total_usuarios,
                'estudiantes_activos' => $estudiantes_activos,
                'docentes_mentores' => $docentes_mentores,
                'sesiones_programadas' => $sesiones_programadas,
                'usuarios_mes_anterior' => $usuarios_mes_anterior,
                'estudiantes_mes_anterior' => $estudiantes_mes_anterior,
                'docentes_mes_anterior' => $docentes_mes_anterior,
                'sesiones_mes_anterior' => $sesiones_mes_anterior
            ];

            // Calcular porcentajes de cambio
            $metricas['cambio_usuarios'] = $this->calcularPorcentajeCambio($total_usuarios, $usuarios_mes_anterior);
            $metricas['cambio_estudiantes'] = $this->calcularPorcentajeCambio($estudiantes_activos, $estudiantes_mes_anterior);
            $metricas['cambio_docentes'] = $this->calcularPorcentajeCambio($docentes_mentores, $docentes_mes_anterior);
            $metricas['cambio_sesiones'] = $this->calcularPorcentajeCambio($sesiones_programadas, $sesiones_mes_anterior);

            return $metricas;
        } catch (Exception $e) {
            error_log("Error al obtener métricas generales: " . $e->getMessage());
            return $this->obtenerMetricasPorDefecto();
        }
    }

    public function obtenerEstadisticasGestion() {
        try {
            // Estudiantes registrados hoy
            $estudiantes_hoy = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM estudiantes 
                 WHERE DATE(created_at) = CURDATE()"
            )['total'] ?? 0;
            
            // Estudiantes registrados este mes
            $estudiantes_mes = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM estudiantes 
                 WHERE MONTH(created_at) = MONTH(NOW()) 
                 AND YEAR(created_at) = YEAR(NOW())"
            )['total'] ?? 0;
            
            // Total de usuarios en el sistema
            $total_usuarios = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1"
            )['total'] ?? 0;
            
            // Usuarios pendientes de verificación (estimado)
            $usuarios_pendientes = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM usuarios 
                 WHERE email_verificado = 0 AND activo = 1"
            )['total'] ?? 0;
            
            // Clases activas
            $clases_activas = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM clases 
                 WHERE estado IN (1, 2, 3)"
            )['total'] ?? 0;
            
            // Clases programadas para hoy
            $clases_hoy = $this->db->fetchOne(
                "SELECT COUNT(*) as total 
                 FROM clases 
                 WHERE DATE(fecha_programada) = CURDATE() 
                 AND estado IN (1, 2, 3)"
            )['total'] ?? 0;
            
            // Total de reportes (comentarios/calificaciones)
            $total_reportes = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM comentarios"
            )['total'] ?? 0;
            
            // Satisfacción promedio (puntuación * 20 para convertir a porcentaje)
            $satisfaccion = $this->db->fetchOne(
                "SELECT AVG(puntuacion) * 20 as promedio 
                 FROM comentarios 
                 WHERE fecha_comentario >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['promedio'] ?? 85;

            return [
                'estudiantes_hoy' => $estudiantes_hoy,
                'estudiantes_mes' => $estudiantes_mes,
                'total_usuarios' => $total_usuarios,
                'usuarios_pendientes' => $usuarios_pendientes,
                'clases_activas' => $clases_activas,
                'clases_hoy' => $clases_hoy,
                'total_reportes' => $total_reportes,
                'satisfaccion' => round($satisfaccion, 0)
            ];
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas de gestión: " . $e->getMessage());
            return [
                'estudiantes_hoy' => 0,
                'estudiantes_mes' => 0,
                'total_usuarios' => 0,
                'usuarios_pendientes' => 0,
                'clases_activas' => 0,
                'clases_hoy' => 0,
                'total_reportes' => 0,
                'satisfaccion' => 85
            ];
        }
    }
 
    public function obtenerActividadReciente($limite = 15) {
        try {
            $actividades = [];

            // 1. NUEVOS REGISTROS DE USUARIOS
            $sql_usuarios = "SELECT 
                                dp.nombres, dp.apellidos, u.email,
                                u.created_at as fecha, u.oauth_provider
                            FROM usuarios u 
                            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                            WHERE u.activo = 1 AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            ORDER BY u.created_at DESC 
                            LIMIT 5";
            
            $usuarios = $this->db->fetchAll($sql_usuarios);
            foreach ($usuarios as $user) {
                $nombre = trim($user['nombres'] . ' ' . $user['apellidos']) ?: 'Usuario';
                $tipo_registro = $user['oauth_provider'] ? 'OAuth (Google)' : 'Tradicional';
                
                $actividades[] = [
                    'tipo' => 'success',
                    'titulo' => 'Nuevo usuario registrado',
                    'descripcion' => $nombre . ' se registró usando ' . $tipo_registro,
                    'fecha' => $user['fecha'],
                    'badge' => 'Registro',
                    'usuario' => 'Sistema'
                ];
            }

            // 2. INSCRIPCIONES A CLASES
            $sql_inscripciones = "SELECT 
                                    i.fecha_inscripcion as fecha,
                                    dp.nombres, dp.apellidos,
                                    c.titulo as clase_titulo,
                                    cur.nombre as curso_nombre
                                FROM inscripciones i
                                INNER JOIN estudiantes e ON i.id_estudiante = e.id_estudiante
                                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                                INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                                INNER JOIN clases c ON i.id_clase = c.id_clase
                                LEFT JOIN cursos cur ON c.id_curso = cur.id_curso
                                WHERE i.activa = 1 AND i.fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                ORDER BY i.fecha_inscripcion DESC 
                                LIMIT 5";
            
            $inscripciones = $this->db->fetchAll($sql_inscripciones);
            foreach ($inscripciones as $insc) {
                $nombre = trim($insc['nombres'] . ' ' . $insc['apellidos']) ?: 'Estudiante';
                $clase = $insc['curso_nombre'] ?: $insc['clase_titulo'] ?: 'Clase de mentoría';
                
                $actividades[] = [
                    'tipo' => 'info',
                    'titulo' => 'Nueva inscripción',
                    'descripcion' => $nombre . ' se inscribió en ' . $clase,
                    'fecha' => $insc['fecha'],
                    'badge' => 'Inscripción',
                    'usuario' => $nombre
                ];
            }

            // 3. ASIGNACIÓN DE ROLES
            $sql_roles = "SELECT 
                            ur.fecha_asignacion as fecha,
                            dp.nombres, dp.apellidos,
                            r.nombre as rol_nombre
                        FROM usuario_roles ur
                        INNER JOIN usuarios u ON ur.id_usuario = u.id_usuario
                        INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                        INNER JOIN roles r ON ur.id_rol = r.id_rol
                        WHERE ur.activo = 1 AND ur.fecha_asignacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ORDER BY ur.fecha_asignacion DESC 
                        LIMIT 4";
            
            $roles = $this->db->fetchAll($sql_roles);
            foreach ($roles as $rol) {
                $nombre = trim($rol['nombres'] . ' ' . $rol['apellidos']) ?: 'Usuario';
                
                $actividades[] = [
                    'tipo' => 'warning',
                    'titulo' => 'Rol asignado',
                    'descripcion' => $nombre . ' obtuvo el rol de ' . ucfirst($rol['rol_nombre']),
                    'fecha' => $rol['fecha'],
                    'badge' => 'Rol',
                    'usuario' => 'Administrador'
                ];
            }

            // 4. COMENTARIOS Y CALIFICACIONES
            $sql_comentarios = "SELECT 
                                c.fecha_comentario as fecha,
                                c.puntuacion,
                                dp_est.nombres as est_nombres, dp_est.apellidos as est_apellidos,
                                dp_ment.nombres as ment_nombres, dp_ment.apellidos as ment_apellidos,
                                cl.titulo as clase_titulo
                            FROM comentarios c
                            INNER JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
                            INNER JOIN usuarios u_est ON e.id_usuario = u_est.id_usuario
                            INNER JOIN datos_personales dp_est ON u_est.id_datos_personales = dp_est.id_datos_personales
                            INNER JOIN mentores m ON c.id_mentor = m.id_mentor
                            INNER JOIN usuarios u_ment ON m.id_usuario = u_ment.id_usuario
                            INNER JOIN datos_personales dp_ment ON u_ment.id_datos_personales = dp_ment.id_datos_personales
                            INNER JOIN clases cl ON c.id_clase = cl.id_clase
                            WHERE c.fecha_comentario >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            ORDER BY c.fecha_comentario DESC 
                            LIMIT 3";
            
            $comentarios = $this->db->fetchAll($sql_comentarios);
            foreach ($comentarios as $com) {
                $estudiante = trim($com['est_nombres'] . ' ' . $com['est_apellidos']) ?: 'Estudiante';
                $mentor = trim($com['ment_nombres'] . ' ' . $com['ment_apellidos']) ?: 'Mentor';
                $estrellas = str_repeat('⭐', $com['puntuacion']);
                
                $actividades[] = [
                    'tipo' => 'primary',
                    'titulo' => 'Nueva calificación',
                    'descripcion' => $estudiante . ' calificó a ' . $mentor . ' con ' . $estrellas,
                    'fecha' => $com['fecha'],
                    'badge' => 'Calificación',
                    'usuario' => $estudiante
                ];
            }

            // 5. CLASES PROGRAMADAS
            $sql_clases = "SELECT 
                            c.created_at as fecha,
                            c.titulo, c.fecha_programada,
                            cur.nombre as curso_nombre,
                            dp.nombres as mentor_nombres, dp.apellidos as mentor_apellidos
                        FROM clases c
                        LEFT JOIN cursos cur ON c.id_curso = cur.id_curso
                        LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
                        LEFT JOIN usuarios u ON m.id_usuario = u.id_usuario
                        LEFT JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                        WHERE c.estado IN (1,2) AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ORDER BY c.created_at DESC 
                        LIMIT 3";
            
            $clases = $this->db->fetchAll($sql_clases);
            foreach ($clases as $clase) {
                $mentor = 'Sin asignar';
                if ($clase['mentor_nombres'] && $clase['mentor_apellidos']) {
                    $mentor = 'Prof. ' . trim($clase['mentor_nombres'] . ' ' . $clase['mentor_apellidos']);
                }
                
                $curso = $clase['curso_nombre'] ?: $clase['titulo'] ?: 'Clase de mentoría';
                $fecha_prog = $clase['fecha_programada'] ? date('d/m/Y H:i', strtotime($clase['fecha_programada'])) : 'Por programar';
                
                $actividades[] = [
                    'tipo' => 'info',
                    'titulo' => 'Clase programada',
                    'descripcion' => 'Nueva sesión de ' . $curso . ' para ' . $fecha_prog,
                    'fecha' => $clase['fecha'],
                    'badge' => 'Programación',
                    'usuario' => $mentor
                ];
            }

            // 6. ASISTENCIAS REGISTRADAS
            $sql_asistencias = "SELECT 
                                a.created_at as fecha,
                                a.estado_asistencia,
                                dp.nombres, dp.apellidos,
                                c.titulo as clase_titulo
                            FROM asistencias a
                            INNER JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                            INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                            INNER JOIN clases c ON a.id_clase = c.id_clase
                            WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                            ORDER BY a.created_at DESC 
                            LIMIT 3";
            
            $asistencias = $this->db->fetchAll($sql_asistencias);
            foreach ($asistencias as $asist) {
                $nombre = trim($asist['nombres'] . ' ' . $asist['apellidos']) ?: 'Estudiante';
                $estados = [1 => 'Temprano', 2 => 'Tarde', 3 => 'Falta'];
                $estado = $estados[$asist['estado_asistencia']] ?? 'Desconocido';
                $tipo_badge = $asist['estado_asistencia'] == 1 ? 'success' : ($asist['estado_asistencia'] == 2 ? 'warning' : 'danger');
                
                $actividades[] = [
                    'tipo' => $tipo_badge,
                    'titulo' => 'Asistencia registrada',
                    'descripcion' => $nombre . ' - ' . $estado . ' en ' . ($asist['clase_titulo'] ?: 'clase'),
                    'fecha' => $asist['fecha'],
                    'badge' => 'Asistencia',
                    'usuario' => 'Sistema'
                ];
            }

            // 7. ACTIVIDAD DE AUDITORÍA (cambios importantes)
            $sql_auditoria = "SELECT 
                                a.timestamp as fecha,
                                a.accion,
                                a.tabla_afectada,
                                dp.nombres, dp.apellidos
                            FROM auditoria a
                            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
                            LEFT JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                            WHERE a.timestamp >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                            AND a.tabla_afectada IN ('usuarios', 'estudiantes', 'mentores', 'clases')
                            ORDER BY a.timestamp DESC 
                            LIMIT 2";
            
            $auditorias = $this->db->fetchAll($sql_auditoria);
            foreach ($auditorias as $audit) {
                $usuario = 'Sistema';
                if ($audit['nombres'] && $audit['apellidos']) {
                    $usuario = trim($audit['nombres'] . ' ' . $audit['apellidos']);
                }
                
                $acciones = ['INSERT' => 'creó', 'UPDATE' => 'modificó', 'DELETE' => 'eliminó'];
                $accion = $acciones[$audit['accion']] ?? 'cambió';
                
                $actividades[] = [
                    'tipo' => 'secondary',
                    'titulo' => 'Cambio en sistema',
                    'descripcion' => $usuario . ' ' . $accion . ' registro en ' . $audit['tabla_afectada'],
                    'fecha' => $audit['fecha'],
                    'badge' => 'Auditoría',
                    'usuario' => $usuario
                ];
            }

            // Ordenar todas las actividades por fecha más reciente
            usort($actividades, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            return array_slice($actividades, 0, $limite);

        } catch (Exception $e) {
            error_log("Error al obtener actividad reciente completa: " . $e->getMessage());
            return $this->obtenerActividadPorDefecto();
        }
    }

    public function obtenerDatosGraficos() {
        try {
            // Usuarios por mes (últimos 6 meses)
            $sql_usuarios = "SELECT 
                                MONTH(created_at) as mes,
                                YEAR(created_at) as año,
                                COUNT(*) as cantidad
                            FROM usuarios 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY YEAR(created_at), MONTH(created_at)
                            ORDER BY año, mes";
            
            $usuarios_grafico = $this->db->fetchAll($sql_usuarios);

            // Estudiantes por mes
            $sql_estudiantes = "SELECT 
                                   MONTH(created_at) as mes,
                                   YEAR(created_at) as año,
                                   COUNT(*) as cantidad
                               FROM estudiantes 
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                               GROUP BY YEAR(created_at), MONTH(created_at)
                               ORDER BY año, mes";
            
            $estudiantes_grafico = $this->db->fetchAll($sql_estudiantes);

            // Clases por mes
            $sql_clases = "SELECT 
                              MONTH(created_at) as mes,
                              YEAR(created_at) as año,
                              COUNT(*) as cantidad
                          FROM clases 
                          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                          GROUP BY YEAR(created_at), MONTH(created_at)
                          ORDER BY año, mes";
            
            $clases_grafico = $this->db->fetchAll($sql_clases);

            return [
                'usuarios' => $usuarios_grafico,
                'estudiantes' => $estudiantes_grafico,
                'clases' => $clases_grafico
            ];

        } catch (Exception $e) {
            error_log("Error al obtener datos de gráficos: " . $e->getMessage());
            return [
                'usuarios' => [],
                'estudiantes' => [],
                'clases' => []
            ];
        }
    }

    public function obtenerEstadoSistema() {
        try {
            // Verificar si hay conexión a la BD
            $servicios_online = true;
            try {
                $this->db->fetchOne("SELECT 1");
            } catch (Exception $e) {
                $servicios_online = false;
            }
            
            // Verificar si hay clases en mantenimiento (estado = 5)
            $mantenimiento = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM clases WHERE estado = 5"
            )['total'] > 0;
            
            // Verificar optimización de BD (simulado)
            $bd_optimizada = true;

            return [
                'servicios_online' => $servicios_online,
                'mantenimiento_programado' => $mantenimiento,
                'bd_optimizada' => $bd_optimizada
            ];

        } catch (Exception $e) {
            error_log("Error al obtener estado del sistema: " . $e->getMessage());
            return [
                'servicios_online' => false,
                'mantenimiento_programado' => false,
                'bd_optimizada' => true
            ];
        }
    }

    private function calcularPorcentajeCambio($actual, $anterior) {
        if ($anterior == 0) {
            return $actual > 0 ? 100 : 0;
        }
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    private function obtenerMetricasPorDefecto() {
        return [
            'total_usuarios' => 0,
            'estudiantes_activos' => 0,
            'docentes_mentores' => 0,
            'sesiones_programadas' => 0,
            'cambio_usuarios' => 0,
            'cambio_estudiantes' => 0,
            'cambio_docentes' => 0,
            'cambio_sesiones' => 0
        ];
    }

    private function obtenerActividadPorDefecto() {
        return [
            [
                'tipo' => 'info',
                'titulo' => 'Sistema iniciado',
                'descripcion' => 'Panel administrativo cargado correctamente',
                'fecha' => date('Y-m-d H:i:s'),
                'badge' => 'Sistema',
                'usuario' => 'Admin UPT'
            ]
        ];
    }

    public function formatearTiempoTranscurrido($fecha) {
        if (!$fecha) return 'Fecha no disponible';
        
        $tiempo = time() - strtotime($fecha);
        
        if ($tiempo < 60) {
            return 'hace ' . $tiempo . ' seg';
        } elseif ($tiempo < 3600) {
            return 'hace ' . floor($tiempo / 60) . ' min';
        } elseif ($tiempo < 86400) {
            $horas = floor($tiempo / 3600);
            return 'hace ' . $horas . ' hora' . ($horas > 1 ? 's' : '');
        } else {
            $dias = floor($tiempo / 86400);
            return 'hace ' . $dias . ' día' . ($dias > 1 ? 's' : '');
        }
    }
    public function actualizarUsuario($id_usuario, $email, $id_rol) {
        try {
            $this->db->beginTransaction();
            
            // 1. Verificar que el email no esté en uso por otro usuario
            $sql_check = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
            $existing = $this->db->fetchOne($sql_check, [$email, $id_usuario]);
            
            if ($existing) {
                throw new Exception('El email ya está en uso por otro usuario');
            }
            
            // 2. Actualizar email del usuario
            $sql_user = "UPDATE usuarios SET email = ?, updated_at = NOW() WHERE id_usuario = ?";
            $this->db->execute($sql_user, [$email, $id_usuario]);
            
            // 3. Desactivar roles actuales
            $sql_deactivate = "UPDATE usuario_roles SET activo = 0 WHERE id_usuario = ?";
            $this->db->execute($sql_deactivate, [$id_usuario]);
            
            // 4. Asignar nuevo rol
            $sql_role = "INSERT INTO usuario_roles (id_usuario, id_rol, fecha_asignacion, activo) 
                        VALUES (?, ?, NOW(), 1)
                        ON DUPLICATE KEY UPDATE activo = 1, fecha_asignacion = NOW()";
            $this->db->execute($sql_role, [$id_usuario, $id_rol]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error actualizando usuario: " . $e->getMessage());
            throw $e;
        }
    }
    public function obtenerTodasActividades($pagina = 1, $limite = 20, $filtro_tipo = null, $filtro_fecha = null) {
        try {
            $offset = ($pagina - 1) * $limite;
            $actividades = [];
            
            // SEGURO - Validación y parámetros preparados
            $where_conditions = [];
            $params_fecha = [];
            
            if ($filtro_fecha) {
                // Validar valores permitidos
                $filtros_validos = ['hoy', 'semana', 'mes'];
                if (in_array($filtro_fecha, $filtros_validos)) {
                    switch ($filtro_fecha) {
                        case 'hoy':
                            $where_conditions[] = "DATE(fecha_actividad) = CURDATE()";
                            break;
                        case 'semana':
                            $where_conditions[] = "fecha_actividad >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                            break;
                        case 'mes':
                            $where_conditions[] = "fecha_actividad >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                            break;
                    }
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'AND ' . implode(' AND ', $where_conditions) : '';
            
            // Validar filtro_tipo
            $tipos_validos = ['usuarios', 'inscripciones', 'roles', 'calificaciones', 'clases', 'asistencias'];
            if ($filtro_tipo && !in_array($filtro_tipo, $tipos_validos)) {
                $filtro_tipo = null; // Ignorar valor inválido
            }
            
            // 1. REGISTROS DE USUARIOS
            if (!$filtro_tipo || $filtro_tipo === 'usuarios') {
                $sql_usuarios = "SELECT 
                                    dp.nombres, dp.apellidos, u.email, 
                                    u.created_at as fecha_actividad, u.oauth_provider, 
                                    'usuario_registro' as tipo_actividad 
                                FROM usuarios u 
                                INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales 
                                WHERE u.activo = 1 $where_clause 
                                ORDER BY u.created_at DESC";
                
                $usuarios = $this->db->fetchAll($sql_usuarios, $params_fecha);
                foreach ($usuarios as $usuario) {
                    $actividades[] = [
                        'tipo' => 'usuario_registro',
                        'titulo' => 'Nuevo usuario registrado',
                        'descripcion' => "Usuario {$usuario['nombres']} {$usuario['apellidos']} se registró",
                        'fecha' => $usuario['fecha_actividad'],
                        'usuario' => $usuario['email'],
                        'badge' => ucfirst($usuario['oauth_provider'] ?? 'Local'),
                        'icono' => 'fa-user-plus'
                    ];
                }
            }
            
            // 2. INSCRIPCIONES A CLASES
            if (!$filtro_tipo || $filtro_tipo === 'inscripciones') {
                $sql_inscripciones = "SELECT 
                                        dp.nombres, dp.apellidos, c.nombre_curso, 
                                        ic.fecha_inscripcion as fecha_actividad,
                                        'inscripcion_clase' as tipo_actividad
                                    FROM inscripciones_clases ic
                                    INNER JOIN estudiantes e ON ic.id_estudiante = e.id_estudiante
                                    INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                                    INNER JOIN clases cl ON ic.id_clase = cl.id_clase
                                    INNER JOIN cursos c ON cl.id_curso = c.id_curso
                                    WHERE ic.activo = 1 $where_clause
                                    ORDER BY ic.fecha_inscripcion DESC";
                
                $inscripciones = $this->db->fetchAll($sql_inscripciones, $params_fecha);
                foreach ($inscripciones as $inscripcion) {
                    $actividades[] = [
                        'tipo' => 'inscripcion_clase',
                        'titulo' => 'Nueva inscripción a clase',
                        'descripcion' => "Estudiante {$inscripcion['nombres']} {$inscripcion['apellidos']} se inscribió a {$inscripcion['nombre_curso']}",
                        'fecha' => $inscripcion['fecha_actividad'],
                        'usuario' => "{$inscripcion['nombres']} {$inscripcion['apellidos']}",
                        'badge' => 'Inscripción',
                        'icono' => 'fa-book-open'
                    ];
                }
            }
            
            // 3. CAMBIOS DE ROLES
            if (!$filtro_tipo || $filtro_tipo === 'roles') {
                $sql_roles = "SELECT 
                                dp.nombres, dp.apellidos, r.nombre_rol,
                                ur.fecha_asignacion as fecha_actividad,
                                'cambio_rol' as tipo_actividad
                            FROM usuario_roles ur
                            INNER JOIN usuarios u ON ur.id_usuario = u.id_usuario
                            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                            INNER JOIN roles r ON ur.id_rol = r.id_rol
                            WHERE ur.activo = 1 $where_clause
                            ORDER BY ur.fecha_asignacion DESC";
                
                $roles = $this->db->fetchAll($sql_roles, $params_fecha);
                foreach ($roles as $rol) {
                    $actividades[] = [
                        'tipo' => 'cambio_rol',
                        'titulo' => 'Cambio de rol de usuario',
                        'descripcion' => "Usuario {$rol['nombres']} {$rol['apellidos']} asignado como {$rol['nombre_rol']}",
                        'fecha' => $rol['fecha_actividad'],
                        'usuario' => "{$rol['nombres']} {$rol['apellidos']}",
                        'badge' => $rol['nombre_rol'],
                        'icono' => 'fa-user-tag'
                    ];
                }
            }
            
            // 4. CALIFICACIONES
            if (!$filtro_tipo || $filtro_tipo === 'calificaciones') {
                $sql_calificaciones = "SELECT 
                                        dp.nombres, dp.apellidos, c.nombre_curso, 
                                        cal.puntuacion, cal.fecha_calificacion as fecha_actividad,
                                        'nueva_calificacion' as tipo_actividad
                                    FROM calificaciones cal
                                    INNER JOIN estudiantes e ON cal.id_estudiante = e.id_estudiante
                                    INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                                    INNER JOIN clases cl ON cal.id_clase = cl.id_clase
                                    INNER JOIN cursos c ON cl.id_curso = c.id_curso
                                    WHERE cal.activo = 1 $where_clause
                                    ORDER BY cal.fecha_calificacion DESC";
                
                $calificaciones = $this->db->fetchAll($sql_calificaciones, $params_fecha);
                foreach ($calificaciones as $calificacion) {
                    $actividades[] = [
                        'tipo' => 'nueva_calificacion',
                        'titulo' => 'Nueva calificación registrada',
                        'descripcion' => "Estudiante {$calificacion['nombres']} {$calificacion['apellidos']} calificó {$calificacion['nombre_curso']} con {$calificacion['puntuacion']} estrellas",
                        'fecha' => $calificacion['fecha_actividad'],
                        'usuario' => "{$calificacion['nombres']} {$calificacion['apellidos']}",
                        'badge' => "{$calificacion['puntuacion']} ⭐",
                        'icono' => 'fa-star'
                    ];
                }
            }
            
            // 5. CLASES PROGRAMADAS
            if (!$filtro_tipo || $filtro_tipo === 'clases') {
                $sql_clases = "SELECT 
                                dp.nombres, dp.apellidos, c.nombre_curso, 
                                cl.fecha_clase as fecha_actividad, cl.estado,
                                'clase_programada' as tipo_actividad
                            FROM clases cl
                            INNER JOIN mentores m ON cl.id_mentor = m.id_mentor
                            INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                            INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                            INNER JOIN cursos c ON cl.id_curso = c.id_curso
                            WHERE cl.activo = 1 $where_clause
                            ORDER BY cl.fecha_clase DESC";
                
                $clases = $this->db->fetchAll($sql_clases, $params_fecha);
                foreach ($clases as $clase) {
                    $actividades[] = [
                        'tipo' => 'clase_programada',
                        'titulo' => 'Clase programada',
                        'descripcion' => "Mentor {$clase['nombres']} {$clase['apellidos']} programó clase de {$clase['nombre_curso']}",
                        'fecha' => $clase['fecha_actividad'],
                        'usuario' => "{$clase['nombres']} {$clase['apellidos']}",
                        'badge' => ucfirst($clase['estado']),
                        'icono' => 'fa-calendar-plus'
                    ];
                }
            }
            
            // 6. ASISTENCIAS
            if (!$filtro_tipo || $filtro_tipo === 'asistencias') {
                $sql_asistencias = "SELECT 
                                    dp.nombres, dp.apellidos, c.nombre_curso, 
                                    a.fecha_asistencia as fecha_actividad, a.estado_asistencia,
                                    'registro_asistencia' as tipo_actividad
                                FROM asistencias a
                                INNER JOIN estudiantes e ON a.id_estudiante = e.id_estudiante
                                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                                INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                                INNER JOIN clases cl ON a.id_clase = cl.id_clase
                                INNER JOIN cursos c ON cl.id_curso = c.id_curso
                                WHERE a.activo = 1 $where_clause
                                ORDER BY a.fecha_asistencia DESC";
                
                $asistencias = $this->db->fetchAll($sql_asistencias, $params_fecha);
                foreach ($asistencias as $asistencia) {
                    $icono = $asistencia['estado_asistencia'] === 'presente' ? 'fa-check-circle' : 'fa-times-circle';
                    $actividades[] = [
                        'tipo' => 'registro_asistencia',
                        'titulo' => 'Asistencia registrada',
                        'descripcion' => "Estudiante {$asistencia['nombres']} {$asistencia['apellidos']} marcado como {$asistencia['estado_asistencia']} en {$asistencia['nombre_curso']}",
                        'fecha' => $asistencia['fecha_actividad'],
                        'usuario' => "{$asistencia['nombres']} {$asistencia['apellidos']}",
                        'badge' => ucfirst($asistencia['estado_asistencia']),
                        'icono' => $icono
                    ];
                }
            }
            
            // Ordenar todas las actividades por fecha
            usort($actividades, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            
            // Aplicar paginación
            $total_actividades = count($actividades);
            $actividades_paginadas = array_slice($actividades, $offset, $limite);
            
            return [
                'actividades' => $actividades_paginadas,
                'total' => $total_actividades,
                'pagina_actual' => $pagina,
                'total_paginas' => ceil($total_actividades / $limite),
                'limite' => $limite
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener todas las actividades: " . $e->getMessage());
            return [
                'actividades' => [],
                'total' => 0,
                'pagina_actual' => 1,
                'total_paginas' => 0,
                'limite' => $limite
            ];
        }
    }
    public function obtenerTodosUsuarios($limite = 50, $offset = 0) {
        try {
            $sql = "SELECT 
                        u.id_usuario as ID_USUARIO,
                        dp.nombres as NOMBRE,
                        dp.apellidos as APELLIDO,
                        u.email as EMAIL,
                        r.nombre as ROL,
                        e.codigo_estudiante as CODIGO_ESTUDIANTE,
                        e.estado_academico as ESTADO_ESTUDIANTE,
                        u.created_at as FECHA_REG,
                        u.activo as ACTIVO,
                        u.oauth_provider as OAUTH_PROVIDER
                    FROM usuarios u
                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario AND ur.activo = 1
                    LEFT JOIN roles r ON ur.id_rol = r.id_rol
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    WHERE u.activo = 1
                    ORDER BY u.created_at DESC
                    LIMIT ? OFFSET ?";
            
            return $this->db->fetchAll($sql, [$limite, $offset]);
        } catch (Exception $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }
    public function buscarUsuarios($tipo, $valor, $limite = 50) {
        try {
            $sql = "SELECT 
                        u.id_usuario as ID_USUARIO,
                        dp.nombres as NOMBRE,
                        dp.apellidos as APELLIDO,
                        u.email as EMAIL,
                        r.nombre as ROL,
                        e.codigo_estudiante as CODIGO_ESTUDIANTE,
                        e.estado_academico as ESTADO_ESTUDIANTE,
                        u.created_at as FECHA_REG,
                        u.activo as ACTIVO,
                        u.oauth_provider as OAUTH_PROVIDER
                    FROM usuarios u
                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario AND ur.activo = 1
                    LEFT JOIN roles r ON ur.id_rol = r.id_rol
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    WHERE u.activo = 1";
            
            $params = [];
            
            switch ($tipo) {
                case 'codigo':
                    $sql .= " AND e.codigo_estudiante LIKE ?";
                    $params[] = "%{$valor}%";
                    break;
                case 'email':
                    $sql .= " AND u.email LIKE ?";
                    $params[] = "%{$valor}%";
                    break;
                case 'nombre':
                    $sql .= " AND (dp.nombres LIKE ? OR dp.apellidos LIKE ? OR CONCAT(dp.nombres, ' ', dp.apellidos) LIKE ?)";
                    $params[] = "%{$valor}%";
                    $params[] = "%{$valor}%";
                    $params[] = "%{$valor}%";
                    break;
                default:
                    // Si no hay tipo específico, buscar en todos los campos disponibles
                    $sql .= " AND (e.codigo_estudiante LIKE ? OR u.email LIKE ? OR dp.nombres LIKE ? OR dp.apellidos LIKE ?)";
                    $params = array_fill(0, 4, "%{$valor}%");
            }
            
            $sql .= " ORDER BY u.created_at DESC LIMIT ?";
            $params[] = $limite;
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("Error al buscar usuarios: " . $e->getMessage());
            return [];
        }
    }
    public function obtenerRolesDisponibles() {
        try {
            $sql = "SELECT 
                        id_rol,
                        nombre,
                        descripcion,
                        priority
                    FROM roles 
                    WHERE activo = 1 
                    ORDER BY priority ASC";
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Error al obtener roles disponibles: " . $e->getMessage());
            return [];
        }
    }
    public function actualizarUsuarioConRoles($id_usuario, $email, $roles_ids) {
        try {
            // Convertir el array de roles a JSON para el procedimiento
            $roles_json = json_encode($roles_ids);
            
            // Llamar al procedimiento almacenado
            $sql = "CALL sp_actualizar_usuario_con_roles(?, ?, ?, @p_success, @p_message)";
            $this->db->execute($sql, [$id_usuario, $email, $roles_json]);
            
            // Obtener los resultados del procedimiento
            $result = $this->db->fetchOne("SELECT @p_success as success, @p_message as message");
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            // Log de auditoría
            error_log("Usuario $id_usuario actualizado via SP: email=$email, roles=" . implode(',', $roles_ids));
            error_log("Resultado SP: " . $result['message']);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error actualizando usuario con roles via SP: " . $e->getMessage());
            throw $e;
        }
    }
    public function obtenerUsuariosConRoles($limite = 50, $offset = 0, $filtro = null) {
        try {
            // Consulta base corregida según tu estructura de BD (SIN DNI)
            $sql = "SELECT 
                        u.id_usuario as ID_USUARIO,
                        dp.nombres as NOMBRE,
                        dp.apellidos as APELLIDO,
                        u.email as EMAIL,
                        e.codigo_estudiante as CODIGO_ESTUDIANTE,
                        u.created_at as FECHA_REG,
                        u.activo as ACTIVO,
                        u.oauth_provider as OAUTH_PROVIDER
                    FROM usuarios u
                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    WHERE u.activo = 1";
            
            $params = [];
            
            // Aplicar filtros si existen
            if ($filtro && isset($filtro['tipo']) && isset($filtro['valor']) && !empty($filtro['valor'])) {
                switch ($filtro['tipo']) {
                    case 'email':
                        $sql .= " AND u.email LIKE ?";
                        $params[] = "%" . $filtro['valor'] . "%";
                        break;
                    case 'codigo':
                        $sql .= " AND e.codigo_estudiante LIKE ?";
                        $params[] = "%" . $filtro['valor'] . "%";
                        break;
                    default:
                        // Búsqueda general en email, nombres, apellidos
                        $sql .= " AND (u.email LIKE ? OR dp.nombres LIKE ? OR dp.apellidos LIKE ? OR e.codigo_estudiante LIKE ?)";
                        $valorBusqueda = "%" . $filtro['valor'] . "%";
                        $params = array_merge($params, [$valorBusqueda, $valorBusqueda, $valorBusqueda, $valorBusqueda]);
                        break;
                }
            }
            
            $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;
            
            // Ejecutar consulta principal
            $usuarios = $this->db->fetchAll($sql, $params);
            
            // Si no hay usuarios, retornar array vacío
            if (empty($usuarios)) {
                return [];
            }
            
            // Ahora obtener los roles para cada usuario
            foreach ($usuarios as &$usuario) {
                $sql_roles = "SELECT 
                                r.id_rol,
                                r.nombre
                            FROM usuario_roles ur
                            INNER JOIN roles r ON ur.id_rol = r.id_rol
                            WHERE ur.id_usuario = ? AND ur.activo = 1
                            ORDER BY r.priority ASC";
                
                $roles = $this->db->fetchAll($sql_roles, [$usuario['ID_USUARIO']]);
                
                // Agregar roles al usuario
                $usuario['ROLES_ARRAY'] = $roles;
                
                // Para compatibilidad, crear string de roles
                if (!empty($roles)) {
                    $usuario['ROLES'] = implode('|', array_map(function($rol) {
                        return $rol['id_rol'] . ':' . $rol['nombre'];
                    }, $roles));
                } else {
                    $usuario['ROLES'] = '';
                    $usuario['ROLES_ARRAY'] = [];
                }
                
                // Agregar campo DNI como NULL o vacío para compatibilidad
                $usuario['DNI'] = null;
            }
            
            return $usuarios;
            
        } catch (Exception $e) {
            error_log("Error al obtener usuarios con roles: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'No SQL'));
            error_log("Params: " . print_r($params ?? [], true));
            return [];
        }
    }
    public function obtenerDetalleUsuario($id_usuario) {
        try {
            $sql = "SELECT 
                        u.id_usuario,
                        dp.nombres, dp.apellidos, dp.telefono, dp.direccion,
                        dp.fecha_nacimiento, dp.genero,
                        u.email, u.created_at, u.ultima_actividad, u.activo, u.oauth_provider,
                        e.codigo_estudiante, e.carrera, e.estado_academico,
                        e.promedio_general, e.puede_solicitar_mentoria,
                        m.especialidades, m.puede_tomar_clase, m.calificacion_promedio, m.total_clases_dadas,
                        a.nivel_acceso, a.permisos,
                        GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles
                    FROM usuarios u
                    INNER JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
                    LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
                    LEFT JOIN mentores m ON u.id_usuario = m.id_usuario
                    LEFT JOIN administradores a ON u.id_usuario = a.id_usuario
                    LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario AND ur.activo = 1
                    LEFT JOIN roles r ON ur.id_rol = r.id_rol
                    WHERE u.id_usuario = ?
                    GROUP BY u.id_usuario";
            
            return $this->db->fetchOne($sql, [$id_usuario]);
        } catch (Exception $e) {
            error_log("Error al obtener detalle de usuario: " . $e->getMessage());
            return null;
        }
    }
}
?>
