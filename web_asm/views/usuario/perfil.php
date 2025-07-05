<?php
require_once BASE_PATH . '/models/Usuario.php';

$usuarioModel = new Usuario();
$usuario = $usuarioModel->obtenerDatosCompletos($_SESSION['usuario_id']);

$rol_prioritario = $usuario['rol_prioritario'] ?? 1;
$es_estudiante = !is_null($usuario['id_estudiante']);
$es_docente = !is_null($usuario['id_mentor']);
$es_admin = !is_null($usuario['id_administrador']);

function validarPasswordSegura($password) {
    if (strlen($password) < 8) {
        return "La contraseña debe tener al menos 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "La contraseña debe contener al menos una letra mayúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "La contraseña debe contener al menos una letra minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "La contraseña debe contener al menos un número";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "La contraseña debe contener al menos un carácter especial";
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'actualizar_password') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            if (empty($password)) {
                throw new Exception("La contraseña es requerida");
            }
            
            if ($password !== $confirm_password) {
                throw new Exception("Las contraseñas no coinciden");
            }

            $validacion = validarPasswordSegura($password);
            if ($validacion !== true) {
                throw new Exception($validacion);
            }

            $usuarioModel->actualizarPassword($_SESSION['usuario_id'], $password);
            
            $mensaje = "Contraseña actualizada correctamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al actualizar contraseña: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

$login_time = $_SESSION['login_time'] ?? null;

if (!$login_time) {
    if (isset($usuario['fecha_registro'])) {
        $login_time = strtotime($usuario['fecha_registro']);
    } else {
        $login_time = time();
    }
    $_SESSION['login_time'] = $login_time;
}

$tiempo_conectado = time() - $login_time;

if (!function_exists('formatearTiempo')) {
    function formatearTiempo($segundos) {
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segundos = $segundos % 60;
        
        if ($horas > 0) {
            return sprintf("%d hora(s), %d minuto(s)", $horas, $minutos);
        } elseif ($minutos > 0) {
            return sprintf("%d minuto(s), %d segundo(s)", $minutos, $segundos);
        } else {
            return sprintf("%d segundo(s)", $segundos);
        }
    }
}

$tiempo_conectado_texto = formatearTiempo($tiempo_conectado);

function obtenerEstadoAcademico($estado) {
    return match($estado) {
        1 => ['texto' => 'Activo', 'clase' => 'success'],
        2 => ['texto' => 'Inactivo', 'clase' => 'danger'],
        3 => ['texto' => 'Graduado', 'clase' => 'info'],
        4 => ['texto' => 'Suspendido', 'clase' => 'warning'],
        default => ['texto' => 'No definido', 'clase' => 'secondary']
    };
}

function obtenerNivelAcceso($nivel) {
    return match($nivel) {
        1 => ['texto' => 'Básico', 'clase' => 'primary'],
        2 => ['texto' => 'Avanzado', 'clase' => 'warning'],
        3 => ['texto' => 'Super Admin', 'clase' => 'danger'],
        default => ['texto' => 'No definido', 'clase' => 'secondary']
    };
}

function obtenerRolPrincipal($rol_prioritario) {
    return match($rol_prioritario) {
        4 => ['nombre' => 'Administrador', 'icono' => 'user-shield', 'clase' => 'danger'],
        3 => ['nombre' => 'Docente', 'icono' => 'chalkboard-teacher', 'clase' => 'success'],
        2 => ['nombre' => 'Estudiante', 'icono' => 'graduation-cap', 'clase' => 'primary'],
        1 => ['nombre' => 'Usuario', 'icono' => 'user', 'clase' => 'secondary'],
        default => ['nombre' => 'Sin rol', 'icono' => 'user', 'clase' => 'secondary']
    };
}

$rol_info = obtenerRolPrincipal($rol_prioritario);
$puede_reclamar = $es_estudiante || $es_docente;
$tipo_usuario = $es_estudiante ? 'estudiante' : ($es_docente ? 'docente' : 'otro');

require_once BASE_PATH . '/views/components/head.php';
require_once BASE_PATH . '/views/components/header.php';
?>

<style>
.perfil-container {
    --perfil-primary: #1e3a5f;
    --perfil-secondary: #1d4ed8;
    --perfil-accent: #5a73c4;
    --perfil-light: #e8f0fe;
    --perfil-dark: #2d4482;
    --perfil-success: #28a745;
    --perfil-warning: #ffc107;
    --perfil-danger: #dc3545;
    --perfil-light-gray: #f8f9fa;
    --perfil-border: #e9ecef;
    --perfil-text: #495057;
    --perfil-shadow: 0 0.125rem 0.25rem rgba(60, 90, 166, 0.1);
    --perfil-shadow-hover: 0 0.5rem 1rem rgba(60, 90, 166, 0.15);
}

.perfil-page {
    background: linear-gradient(135deg, var(--perfil-light-gray) 0%, var(--perfil-border) 100%);
    min-height: 100vh;
    padding: 2rem 0;
    margin-top: 80px;
}

.perfil-card {
    border: none;
    box-shadow: var(--perfil-shadow);
    transition: all 0.3s ease;
    border-radius: 1rem;
    overflow: hidden;
    margin-bottom: 2rem;
    background: white;
    position: relative;
}

.perfil-card:hover {
    box-shadow: var(--perfil-shadow-hover);
    transform: translateY(-3px);
}

.perfil-card-header {
    background: linear-gradient(135deg, var(--perfil-primary) 0%, var(--perfil-secondary) 100%);
    color: white;
    border-bottom: none;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
}

.perfil-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1) 0%, transparent 50%);
}

.perfil-card-header h3, .perfil-card-header h5 {
    margin: 0;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
}

.perfil-card-header .badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-size: 0.8rem;
    margin-left: auto;
    backdrop-filter: blur(10px);
}

.perfil-card-body {
    padding: 2rem;
}

.perfil-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.perfil-info-item {
    background: linear-gradient(135deg, #ffffff 0%, var(--perfil-light-gray) 100%);
    border: 1px solid var(--perfil-border);
    border-radius: 0.75rem;
    padding: 1.25rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.perfil-info-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.75rem rgba(60, 90, 166, 0.15);
    border-color: var(--perfil-primary);
}

.perfil-info-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--perfil-text);
    font-size: 0.9rem;
}

.perfil-info-header i {
    color: var(--perfil-primary);
    margin-right: 0.75rem;
    width: 20px;
    font-size: 1.1rem;
}

.perfil-info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--perfil-dark);
    word-break: break-word;
}

.perfil-badge {
    font-size: 0.85rem;
    padding: 0.6rem 1rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
    display: inline-block;
    margin: 0.25rem;
    transition: all 0.3s ease;
}

.perfil-badge:hover {
    transform: scale(1.05);
}

.perfil-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.perfil-badge:hover::before {
    left: 100%;
}

.perfil-btn {
    border-radius: 0.75rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.perfil-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.perfil-btn:hover::before {
    left: 100%;
}

.perfil-btn-primary {
    background: linear-gradient(135deg, var(--perfil-primary) 0%, var(--perfil-secondary) 100%);
    box-shadow: 0 0.25rem 0.5rem rgba(60, 90, 166, 0.3);
    color: white;
}

.perfil-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(60, 90, 166, 0.4);
    color: white;
}

.perfil-btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
}

.perfil-btn-success {
    background: linear-gradient(135deg, var(--perfil-success) 0%, #20c997 100%);
    color: white;
}

.perfil-btn-warning {
    background: linear-gradient(135deg, var(--perfil-warning) 0%, #e0a800 100%);
    color: white;
}

.perfil-btn-danger {
    background: linear-gradient(135deg, var(--perfil-danger) 0%, #c82333 100%);
    color: white;
}

.perfil-password-section {
    background: linear-gradient(135deg, #ffffff 0%, var(--perfil-light-gray) 100%);
    border-radius: 1rem;
    padding: 2rem;
    border: 1px solid var(--perfil-border);
    margin-bottom: 2rem;
}

.perfil-strength-indicator {
    height: 8px;
    border-radius: 4px;
    background-color: var(--perfil-border);
    overflow: hidden;
    margin-bottom: 0.75rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
}

.perfil-strength-bar {
    height: 100%;
    transition: all 0.5s ease;
    border-radius: 4px;
    position: relative;
}

.perfil-strength-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: perfil-shimmer 2s infinite;
}

@keyframes perfil-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.perfil-strength-weak { 
    background: linear-gradient(135deg, var(--perfil-danger) 0%, #c82333 100%); 
    width: 25%; 
}

.perfil-strength-fair { 
    background: linear-gradient(135deg, #fd7e14 0%, #e55100 100%); 
    width: 50%; 
}

.perfil-strength-good { 
    background: linear-gradient(135deg, var(--perfil-warning) 0%, #e0a800 100%); 
    width: 75%; 
}

.perfil-strength-strong { 
    background: linear-gradient(135deg, var(--perfil-success) 0%, #1e7e34 100%); 
    width: 100%; 
}

.perfil-requirements {
    background: linear-gradient(135deg, var(--perfil-light-gray) 0%, #ffffff 100%);
    border: 1px solid var(--perfil-border);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-top: 1rem;
}

.perfil-requirements ul {
    margin: 0;
    padding-left: 1rem;
}

.perfil-requirements li {
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
}

.perfil-alert {
    border-radius: 0.75rem;
    border: none;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    position: relative;
    overflow: hidden;
}

.perfil-alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: currentColor;
}

.perfil-alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #ffffff 100%);
    color: #155724;
    border-left: 4px solid var(--perfil-success);
}

.perfil-alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #ffffff 100%);
    color: #721c24;
    border-left: 4px solid var(--perfil-danger);
}

.perfil-alert-info {
    background: linear-gradient(135deg, var(--perfil-light) 0%, #ffffff 100%);
    color: var(--perfil-dark);
    border-left: 4px solid var(--perfil-primary);
}

.perfil-alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffffff 100%);
    color: #856404;
    border-left: 4px solid var(--perfil-warning);
}

.perfil-role-header {
    background: linear-gradient(135deg, var(--perfil-primary) 0%, var(--perfil-secondary) 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 1rem 1rem 0 0;
    margin: -2rem -2rem 2rem -2rem;
    position: relative;
    overflow: hidden;
}

.perfil-role-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
}

.perfil-role-header h6 {
    margin: 0;
    font-weight: 600;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
}

.perfil-role-header i {
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.perfil-time-counter {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    background: linear-gradient(135deg, var(--perfil-success) 0%, #20c997 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    display: inline-block;
    min-width: 120px;
    text-align: center;
    box-shadow: 0 0.125rem 0.25rem rgba(40, 167, 69, 0.3);
}

.perfil-user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--perfil-primary) 0%, var(--perfil-secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    margin-bottom: 1rem;
    box-shadow: 0 0.25rem 0.5rem rgba(60, 90, 166, 0.3);
}

@keyframes perfil-fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes perfil-fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes perfil-fadeInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.perfil-card:nth-child(1) { animation: perfil-fadeInUp 0.6s ease-out; }
.perfil-card:nth-child(2) { animation: perfil-fadeInLeft 0.6s ease-out 0.2s both; }
.perfil-card:nth-child(3) { animation: perfil-fadeInRight 0.6s ease-out 0.4s both; }

@media (max-width: 768px) {
    .perfil-page {
        padding: 1rem 0;
    }
    
    .perfil-card-body {
        padding: 1.5rem;
    }
    
    .perfil-info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .perfil-btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 576px) {
    .perfil-card-body {
        padding: 1rem;
    }
    
    .perfil-info-item {
        padding: 1rem;
    }
    
    .perfil-info-value {
        font-size: 1rem;
    }
}
</style>

<div class="perfil-container">
    <div class="perfil-page">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <?php if (isset($mensaje)): ?>
                        <div class="alert perfil-alert perfil-alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            <?= htmlspecialchars($mensaje) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="perfil-card">
                        <div class="perfil-card-header">
                            <h3>
                                <i class="fas fa-<?= $rol_info['icono'] ?> me-3"></i> 
                                Perfil de Usuario
                                <span class="badge bg-<?= $rol_info['clase'] ?> ms-auto">
                                    <?= $rol_info['nombre'] ?>
                                </span>
                            </h3>
                        </div>
                        <div class="perfil-card-body">
                            <div class="row">
                                <div class="col-md-2 text-center">
                                    <div class="perfil-user-avatar mx-auto">
                                        <i class="fas fa-<?= $rol_info['icono'] ?>"></i>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <div class="perfil-info-grid">
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-user"></i>
                                                Nombre Completo
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars(trim($usuario['nombres'] . ' ' . $usuario['apellidos']) ?: 'No especificado') ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-envelope"></i>
                                                Correo Electrónico
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars($usuario['email']) ?>
                                                <?php if ($usuario['email_verificado']): ?>
                                                    <span class="badge bg-success perfil-badge ms-2">Verificado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning perfil-badge ms-2">Sin Verificar</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-phone"></i>
                                                Teléfono
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars($usuario['telefono'] ?: 'No registrado') ?>
                                            </div>
                                        </div>

                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-map-marker-alt"></i>
                                                Dirección
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars($usuario['direccion'] ?: 'No registrada') ?>
                                            </div>
                                        </div>

                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-clock"></i>
                                                Tiempo Conectado
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="perfil-time-counter" id="tiempoConectado"><?= $tiempo_conectado_texto ?></span>
                                            </div>
                                        </div>

                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-calendar-alt"></i>
                                                Fecha de Registro
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= $usuario['fecha_registro'] ? date('d/m/Y H:i:s', strtotime($usuario['fecha_registro'])) : 'No disponible' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <?php if ($es_estudiante): ?>
                                <div class="col-md-6">
                                    <div class="perfil-role-header bg-primary">
                                        <h6><i class="fas fa-graduation-cap"></i>Información Académica</h6>
                                    </div>
                                    <div class="perfil-info-grid mt-3">
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-id-card"></i>
                                                Código de Estudiante
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars($usuario['codigo_estudiante'] ?: 'No asignado') ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-university"></i>
                                                Carrera
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= htmlspecialchars($usuario['carrera'] ?: 'No asignada') ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-chart-line"></i>
                                                Estado Académico
                                            </div>
                                            <div class="perfil-info-value">
                                                <?php $estado = obtenerEstadoAcademico($usuario['estado_academico']); ?>
                                                <span class="badge bg-<?= $estado['clase'] ?> perfil-badge">
                                                    <?= $estado['texto'] ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-star"></i>
                                                Promedio General
                                            </div>
                                            <div class="perfil-info-value">
                                                <?php if ($usuario['promedio_general']): ?>
                                                    <span class="badge bg-<?= $usuario['promedio_general'] >= 14 ? 'success' : ($usuario['promedio_general'] >= 11 ? 'warning' : 'danger') ?> perfil-badge">
                                                        <?= number_format($usuario['promedio_general'], 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No calculado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-question-circle"></i>
                                                Puede Solicitar Mentoría
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-<?= $usuario['puede_solicitar_mentoria'] ? 'success' : 'danger' ?> perfil-badge">
                                                    <?= $usuario['puede_solicitar_mentoria'] ? 'Sí' : 'No' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($es_docente): ?>
                                <div class="col-md-6">
                                    <div class="perfil-role-header bg-success">
                                        <h6><i class="fas fa-chalkboard-teacher"></i>Información Docente</h6>
                                    </div>
                                    <div class="perfil-info-grid mt-3">
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-id-badge"></i>
                                                ID Mentor
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-success perfil-badge"><?= $usuario['id_mentor'] ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-brain"></i>
                                                Especialidades
                                            </div>
                                            <div class="perfil-info-value">
                                                <?= $usuario['especialidades'] ? htmlspecialchars($usuario['especialidades']) : 'No especificadas' ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-check-circle"></i>
                                                Puede Tomar Clases
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-<?= $usuario['puede_tomar_clase'] ? 'success' : 'danger' ?> perfil-badge">
                                                    <?= $usuario['puede_tomar_clase'] ? 'Sí' : 'No' ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-star-half-alt"></i>
                                                Calificación Promedio
                                            </div>
                                            <div class="perfil-info-value">
                                                <?php if ($usuario['calificacion_promedio'] > 0): ?>
                                                    <span class="badge bg-<?= $usuario['calificacion_promedio'] >= 4 ? 'success' : ($usuario['calificacion_promedio'] >= 3 ? 'warning' : 'danger') ?> perfil-badge">
                                                        <?= number_format($usuario['calificacion_promedio'], 2) ?>/5.00
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin calificaciones</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-chalkboard"></i>
                                                Total de Clases Dadas
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-info perfil-badge">
                                                    <?= $usuario['total_clases_dadas'] ?? 0 ?> clases
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($es_admin): ?>
                                <div class="col-md-6">
                                    <div class="perfil-role-header bg-danger">
                                        <h6><i class="fas fa-user-shield"></i>Información Administrativa</h6>
                                    </div>
                                    <div class="perfil-info-grid mt-3">
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-id-card-alt"></i>
                                                ID Administrador
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-danger perfil-badge"><?= $usuario['id_administrador'] ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-layer-group"></i>
                                                Nivel de Acceso
                                            </div>
                                            <div class="perfil-info-value">
                                                <?php $nivel = obtenerNivelAcceso($usuario['nivel_acceso']); ?>
                                                <span class="badge bg-<?= $nivel['clase'] ?> perfil-badge">
                                                    Nivel <?= $usuario['nivel_acceso'] ?> - <?= $nivel['texto'] ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($usuario['permisos']): ?>
                                        <div class="perfil-info-item">
                                            <div class="perfil-info-header">
                                                <i class="fas fa-key"></i>
                                                Permisos Especiales
                                            </div>
                                            <div class="perfil-info-value">
                                                <span class="badge bg-warning perfil-badge">Configurado</span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!$es_estudiante && !$es_docente && !$es_admin): ?>
                            <div class="alert perfil-alert perfil-alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i> 
                                Tu cuenta es de tipo Usuario básico. Para acceder a funciones adicionales, contacta al administrador.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="perfil-card">
                        <div class="perfil-card-header">
                            <h5>
                                <i class="fas fa-lock me-3"></i> 
                                Seguridad de la Cuenta
                            </h5>
                        </div>
                        <div class="perfil-card-body">
                            <form method="POST" action="<?= BASE_URL ?>/index.php?accion=perfil" id="passwordForm">
                                <input type="hidden" name="action" value="actualizar_password">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label fw-bold">
                                            <i class="fas fa-key me-2"></i>
                                            Nueva Contraseña:
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Ingresa tu nueva contraseña" required>
                                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-3">
                                            <div class="perfil-strength-indicator">
                                                <div class="perfil-strength-bar" id="strengthBar"></div>
                                            </div>
                                            <small class="text-muted" id="strengthText">Ingresa una contraseña para ver su fortaleza</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label fw-bold">
                                            <i class="fas fa-check-double me-2"></i>
                                            Confirmar Nueva Contraseña:
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                   placeholder="Confirma tu nueva contraseña" required>
                                            <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback" id="confirmFeedback"></div>
                                    </div>
                                </div>

                                <div class="perfil-requirements mt-4">
                                    <h6 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Requisitos de Seguridad:</h6>
                                    <ul id="requirements">
                                        <li id="req-length" class="text-muted">
                                            <i class="fas fa-times me-2"></i> Mínimo 8 caracteres
                                        </li>
                                        <li id="req-upper" class="text-muted">
                                            <i class="fas fa-times me-2"></i> Al menos una letra mayúscula
                                        </li>
                                        <li id="req-lower" class="text-muted">
                                            <i class="fas fa-times me-2"></i> Al menos una letra minúscula
                                        </li>
                                        <li id="req-number" class="text-muted">
                                            <i class="fas fa-times me-2"></i> Al menos un número
                                        </li>
                                        <li id="req-special" class="text-muted">
                                            <i class="fas fa-times me-2"></i> Al menos un carácter especial (!@#$%^&*)
                                        </li>
                                    </ul>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn perfil-btn perfil-btn-primary" id="submitBtn" disabled>
                                        <i class="fas fa-save me-2"></i> Actualizar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="<?= BASE_URL ?>/index.php" class="btn perfil-btn perfil-btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver al Inicio
                        </a>
                        
                        <?php if ($es_estudiante): ?>
                        <a href="<?= BASE_URL ?>/index.php?accion=mis_clases" class="btn perfil-btn perfil-btn-primary">
                            <i class="fas fa-graduation-cap me-2"></i> Mis Clases
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($es_docente): ?>
                        <a href="<?= BASE_URL ?>/index.php?accion=clases_disponibles" class="btn perfil-btn perfil-btn-success">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Tomar Clases
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($es_admin): ?>
                        <a href="<?= BASE_URL ?>/index.php?accion=admin_panel" class="btn perfil-btn perfil-btn-danger">
                            <i class="fas fa-cogs me-2"></i> Panel de Administración
                        </a>
                        <?php endif; ?>

                        <?php if ($puede_reclamar): ?>
                        <button type="button" class="btn perfil-btn perfil-btn-warning" data-bs-toggle="modal" data-bs-target="#modalReclamarRango">
                            <i class="fas fa-<?= $tipo_usuario === 'estudiante' ? 'graduation-cap' : 'chalkboard-teacher' ?> me-2"></i> 
                            Reclamar Rango <?= ucfirst($tipo_usuario) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($puede_reclamar): ?>
<div class="modal fade" id="modalReclamarRango" tabindex="-1" aria-labelledby="modalReclamarRangoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReclamarRangoLabel">
                    <i class="fas fa-<?= $tipo_usuario === 'estudiante' ? 'graduation-cap' : 'chalkboard-teacher' ?> me-2"></i>
                    Reclamar Rango de <?= ucfirst($tipo_usuario) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h4>Generar Código de Reclamo</h4>
                    <p class="text-muted">
                        Se generará un código único que podrás usar para reclamar tu rango de 
                        <strong><?= $tipo_usuario ?></strong> en Discord.
                    </p>
                </div>

                <div class="alert perfil-alert perfil-alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Información importante:</h6>
                    <ul class="mb-0">
                        <li>El código será válido por <strong>5 minutos</strong></li>
                        <li>Úsalo en el servidor de Discord para reclamar tu rango</li>
                        <li>Solo puedes generar un código a la vez</li>
                        <li>Guarda el código en un lugar seguro</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <label for="discord_username" class="form-label fw-bold">
                        <i class="fab fa-discord me-2"></i>Username de Discord:
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" class="form-control" id="discord_username" name="discord_username" 
                               placeholder="tu_username" required 
                               pattern="^[a-zA-Z0-9_.]{2,32}$"
                               title="Username válido de Discord (2-32 caracteres, solo letras, números, puntos y guiones bajos)">
                    </div>
                    <div class="form-text">
                        <small>Ingresa tu username exacto de Discord (sin el #discriminador). Ejemplo: usuario123</small>
                    </div>
                    <div class="invalid-feedback" id="discordUsernameFeedback"></div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Datos que se incluirán:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Nombre:</strong> <?= htmlspecialchars(trim($usuario['nombres'] . ' ' . $usuario['apellidos'])) ?></li>
                            <li><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></li>
                            <li><strong>Tipo:</strong> <?= ucfirst($tipo_usuario) ?></li>
                            <li><strong>ID:</strong> <?= $tipo_usuario === 'estudiante' ? $usuario['id_estudiante'] : $usuario['id_mentor'] ?></li>
                            <li><strong>Discord:</strong> <span id="discord_preview">@tu_username</span></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Proceso de reclamo:</h6>
                        <ol class="small">
                            <li>Ingresa tu username de Discord</li>
                            <li>Confirma la generación del código</li>
                            <li>Copia el código generado</li>
                            <li>Ve al servidor de Discord</li>
                            <li>Usa el comando de reclamo con tu código</li>
                            <li>¡Disfruta de tu nuevo rango!</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <form method="POST" action="<?= BASE_URL ?>/index.php?accion=perfil" id="reclamoForm">
                    <input type="hidden" name="action" value="reclamar_rango">
                    <input type="hidden" name="discord_username" id="discord_username_hidden">
                    <input type="hidden" name="email_usuario" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                    <button type="submit" class="btn btn-success" id="btnConfirmarReclamo" disabled>
                        <i class="fas fa-key me-2"></i>Generar Código de Reclamo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const submitBtn = document.getElementById('submitBtn');
    const confirmFeedback = document.getElementById('confirmFeedback');

    const loginTime = <?= $login_time ?>;
    const tiempoConectadoElement = document.getElementById('tiempoConectado');
    
    function actualizarTiempo() {
        const tiempoActual = Math.floor(Date.now() / 1000);
        const segundosConectado = tiempoActual - loginTime;
        
        const horas = Math.floor(segundosConectado / 3600);
        const minutos = Math.floor((segundosConectado % 3600) / 60);
        const segundos = segundosConectado % 60;
        
        let texto = '';
        if (horas > 0) {
            texto = `${horas}h ${minutos}m`;
        } else if (minutos > 0) {
            texto = `${minutos}m ${segundos}s`;
        } else {
            texto = `${segundos}s`;
        }
        
        tiempoConectadoElement.textContent = texto;
    }
    
    setInterval(actualizarTiempo, 1000);

    document.getElementById('togglePassword')?.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    passwordInput?.addEventListener('input', function() {
        const password = this.value;
        checkPasswordStrength(password);
        validateForm();
    });

    confirmPasswordInput?.addEventListener('input', function() {
        validatePasswordMatch();
        validateForm();
    });

    function checkPasswordStrength(password) {
        const requirements = [
            { id: 'req-length', regex: /.{8,}/, text: 'Mínimo 8 caracteres' },
            { id: 'req-upper', regex: /[A-Z]/, text: 'Al menos una letra mayúscula' },
            { id: 'req-lower', regex: /[a-z]/, text: 'Al menos una letra minúscula' },
            { id: 'req-number', regex: /[0-9]/, text: 'Al menos un número' },
            { id: 'req-special', regex: /[^A-Za-z0-9]/, text: 'Al menos un carácter especial' }
        ];

        let score = 0;
        requirements.forEach(req => {
            const element = document.getElementById(req.id);
            const icon = element?.querySelector('i');
            
            if (element && icon) {
                if (req.regex.test(password)) {
                    score++;
                    element.classList.remove('text-muted');
                    element.classList.add('text-success');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                } else {
                    element.classList.remove('text-success');
                    element.classList.add('text-muted');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            }
        });

        if (strengthBar) {
            strengthBar.className = 'perfil-strength-bar';
            if (password.length === 0) {
                strengthText.textContent = 'Ingresa una contraseña para ver su fortaleza';
                strengthBar.style.width = '0%';
            } else if (score < 2) {
                strengthBar.classList.add('perfil-strength-weak');
                strengthText.textContent = 'Contraseña muy débil';
            } else if (score < 3) {
                strengthBar.classList.add('perfil-strength-fair');
                strengthText.textContent = 'Contraseña débil';
            } else if (score < 5) {
                strengthBar.classList.add('perfil-strength-good');
                strengthText.textContent = 'Contraseña buena';
            } else {
                strengthBar.classList.add('perfil-strength-strong');
                strengthText.textContent = 'Contraseña fuerte';
            }
        }

        return score === 5;
    }

    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (confirmPassword === '') {
            confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
            confirmFeedback.textContent = '';
            return false;
        }

        if (password === confirmPassword) {
            confirmPasswordInput.classList.remove('is-invalid');
            confirmPasswordInput.classList.add('is-valid');
            confirmFeedback.textContent = '';
            return true;
        } else {
            confirmPasswordInput.classList.remove('is-valid');
            confirmPasswordInput.classList.add('is-invalid');
            confirmFeedback.textContent = 'Las contraseñas no coinciden';
            return false;
        }
    }

    function validateForm() {
        const isPasswordStrong = checkPasswordStrength(passwordInput?.value || '');
        const isPasswordMatch = validatePasswordMatch();
        const hasPassword = (passwordInput?.value || '').length > 0;
        const hasConfirmPassword = (confirmPasswordInput?.value || '').length > 0;

        if (submitBtn) {
            submitBtn.disabled = !(isPasswordStrong && isPasswordMatch && hasPassword && hasConfirmPassword);
            
            if (!submitBtn.disabled) {
                submitBtn.classList.add('perfil-btn-success');
                submitBtn.classList.remove('perfil-btn-primary');
            } else {
                submitBtn.classList.remove('perfil-btn-success');
                submitBtn.classList.add('perfil-btn-primary');
            }
        }
    }

    const discordUsernameInput = document.getElementById('discord_username');
    const discordPreview = document.getElementById('discord_preview');
    const btnConfirmarReclamo = document.getElementById('btnConfirmarReclamo');
    const discordUsernameFeedback = document.getElementById('discordUsernameFeedback');
    const discordUsernameHidden = document.getElementById('discord_username_hidden');

    if (discordUsernameInput) {
        discordUsernameInput.addEventListener('input', function() {
            const username = this.value.trim();
            validateDiscordUsername(username);
        });

        discordUsernameInput.addEventListener('blur', function() {
            const username = this.value.trim();
            validateDiscordUsername(username);
        });
    }

    function validateDiscordUsername(username) {
        const discordRegex = /^[a-zA-Z0-9_.]{2,32}$/;
        
        if (discordPreview) {
            discordPreview.textContent = username ? `@${username}` : '@tu_username';
        }
        
        if (username === '') {
            discordUsernameInput?.classList.remove('is-valid', 'is-invalid');
            if (discordUsernameFeedback) discordUsernameFeedback.textContent = '';
            if (btnConfirmarReclamo) btnConfirmarReclamo.disabled = true;
            return false;
        }

        if (discordRegex.test(username)) {
            discordUsernameInput?.classList.remove('is-invalid');
            discordUsernameInput?.classList.add('is-valid');
            if (discordUsernameFeedback) discordUsernameFeedback.textContent = '';
            if (discordUsernameHidden) discordUsernameHidden.value = username;
            if (btnConfirmarReclamo) btnConfirmarReclamo.disabled = false;
            return true;
        } else {
            discordUsernameInput?.classList.remove('is-valid');
            discordUsernameInput?.classList.add('is-invalid');
            
            let errorMsg = '';
            if (username.length < 2) {
                errorMsg = 'Debe tener al menos 2 caracteres';
            } else if (username.length > 32) {
                errorMsg = 'No puede tener más de 32 caracteres';
            } else {
                errorMsg = 'Solo se permiten letras, números, puntos y guiones bajos';
            }
            
            if (discordUsernameFeedback) discordUsernameFeedback.textContent = errorMsg;
            if (btnConfirmarReclamo) btnConfirmarReclamo.disabled = true;
            return false;
        }
    }

    btnConfirmarReclamo?.addEventListener('click', function(e) {
        const username = discordUsernameInput?.value.trim() || '';
        
        if (!validateDiscordUsername(username)) {
            e.preventDefault();
            alert('Por favor, ingresa un username de Discord válido');
            return;
        }
        
        if (!confirm(`¿Estás seguro de que deseas generar un código de reclamo para @${username}? El código anterior (si existe) será invalidado.`)) {
            e.preventDefault();
        }
    });

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.perfil-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});
</script>

<?php require_once BASE_PATH . '/views/components/footer.php'; ?>