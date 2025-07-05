<?php
require_once BASE_PATH . '/models/EstudianteModel.php';
require_once BASE_PATH . '/models/ClaseModel.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 2) {
    header('Location: ' . BASE_URL . '/index.php?accion=login');
    exit;
}

$estudianteModel = new EstudianteModel();
$claseModel = new ClaseModel();
$idUsuario = $_SESSION['usuario_id']; 

// Obtener datos del estudiante
$estudiante = $estudianteModel->obtenerEstudiantePorUsuario($idUsuario);
if (!$estudiante) {
    header('Location: ' . BASE_URL . '/index.php?accion=vincularme');
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['error']);

// Obtener clases inscritas del estudiante
$clasesInscritas = $estudianteModel->obtenerClasesEstudianteInscrito($idUsuario);

// Obtener clases disponibles para inscripción (procedimiento almacenado)
$clasesDisponibles = $estudianteModel->obtenerClasesDisponiblesParaInscripcion($idUsuario);

// Procesar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] === 'inscribir') {
        $idClase = $_POST['id_clase'] ?? null;
        if ($idClase) {
            try {
                $resultado = $claseModel->inscribirEstudiante($idClase, $estudiante['id_estudiante']);
                if ($resultado['success']) {
                    $_SESSION['mensaje'] = $resultado['mensaje'];
                } else {
                    $_SESSION['error'] = $resultado['mensaje'];
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al inscribirse: ' . $e->getMessage();
            }
        }
        header('Location: /estudiante/solicitar');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include BASE_PATH . '/views/components/head.php'; ?>
    <title>Gestión de Clases - AMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
:root {
    /* Colores primarios */
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --secondary-blue: #2c5282; /* Alias para compatibilidad */
    --primary-blue: #1e3a5f;
    
    /* Colores de éxito/verde */
    --success: #10b981;
    --success-dark: #059669;
    --accent-green: #28a745;
    --light-green: #20c997;
    
    /* Colores informativos y de estado */
    --info: #06b6d4;
    --info-dark: #0891b2;
    --warning: #f59e0b;
    --warning-dark: #d97706;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    
    /* Grises */
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    
    /* Superficie y bordes */
    --white: #ffffff;
    --surface: #fafbfc;
    --border: #e2e8f0;
    
    /* Variables de diseño */
    --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --border-radius: 0.75rem;
}

body {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    color: var(--gray-800);
}

.clases-container {
    padding: 2rem 0;
    min-height: calc(100vh - 120px);
    max-width: 1400px;
    margin: 0 auto;
    padding-left: 20px;
    padding-right: 20px;
}

.page-header {
    background: var(--primary-blue);
    color: white;
    padding: 2.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    text-align: center;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.page-header h1 {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

.page-header p {
    opacity: 0.9;
    margin: 0;
    font-size: 1.125rem;
    font-weight: 400;
    position: relative;
    z-index: 2;
}

.seccion-clases {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    overflow: hidden;
    border: 1px solid var(--border);
}

.seccion-titulo {
    padding: 1.5rem 2rem;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--primary);
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.seccion-titulo i {
    margin-right: 0.75rem;
    font-size: 1.5rem;
}

.stats-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    background: var(--primary) !important;
    color: white;
}

.clase-card {
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin: 2rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    background: var(--white);
    box-shadow: var(--shadow-sm);
}

.clase-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.necesita-mentoria {
    border-color: var(--warning);
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%);
}

.clase-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 15px;
}

.clase-titulo {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--gray-700);
    line-height: 1.3;
    flex: 1;
}

.badges-container {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.estado-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.estado-pendiente { 
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-dark);
}
.estado-activo { 
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-dark);
}
.estado-en_proceso { 
    background: rgba(6, 182, 212, 0.1);
    color: var(--info-dark);
}
.estado-finalizado { 
    background: rgba(156, 163, 175, 0.1);
    color: var(--gray-600);
}
.estado-cerrada { 
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-dark);
}

.mentoria-badge {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-dark);
    border: 1px solid rgba(245, 158, 11, 0.2);
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.clase-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: var(--surface);
    border-radius: 0.5rem;
    border: 1px solid var(--border);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-label {
    font-weight: 600;
    color: var(--gray-600);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-label i {
    color: var(--primary);
    width: 16px;
}

.info-value {
    color: var(--gray-900);
    font-size: 1rem;
    font-weight: 500;
}

/* Barras de progreso animadas */
.progress-container {
    margin-top: 0.5rem;
    background: var(--gray-200);
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 1.5s ease-in-out;
    position: relative;
    overflow: hidden;
}

.progress-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: progressShine 2s infinite;
}

@keyframes progressShine {
    0% { left: -100%; }
    100% { left: 100%; }
}

.progress-success {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
}

.progress-warning {
    background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
}

.progress-danger {
    background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
}

.progress-info {
    background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
}

.cupos-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.clase-descripcion {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--surface);
    border-radius: 0.5rem;
    color: var(--gray-600);
    font-style: italic;
    border-left: 4px solid var(--primary);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.clase-descripcion i {
    color: var(--primary);
    margin-top: 0.125rem;
    flex-shrink: 0;
}

.clase-acciones {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn {
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 700;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #1e40af 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--success-dark) 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    color: white;
}

.btn-secondary {
    background: var(--gray-300);
    color: var(--gray-600);
    cursor: not-allowed;
}

.btn:disabled {
    background: var(--gray-300);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    color: var(--gray-600);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray-500);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.6;
    color: var(--gray-400);
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: var(--gray-700);
    font-weight: 600;
}

.empty-state p {
    color: var(--gray-500);
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.6;
}

.alert {
    border: none;
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-dark);
    border-left-color: var(--success);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-dark);
    border-left-color: var(--danger);
}

.cupos-info {
    font-size: 0.95rem;
    font-weight: 500;
}

.cupos-disponibles {
    color: var(--success);
    font-weight: 700;
}

.cupos-limitados {
    color: var(--warning);
    font-weight: 700;
}

.sin-cupos {
    color: var(--danger);
    font-weight: 700;
}

.ponderado-alto {
    color: var(--success);
    font-weight: 700;
}

.ponderado-bajo {
    color: var(--danger);
    font-weight: 700;
}

/* Modal mejorado */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show {
    display: flex;
    opacity: 1;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: var(--border-radius);
    max-width: 90%;
    max-height: 90%;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transform: scale(0.7);
    transition: transform 0.3s ease;
}

.modal.show .modal-content {
    transform: scale(1);
}

.modal-header {
    padding: 1.5rem 2rem;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    transition: var(--transition);
}

.modal-close:hover {
    color: var(--gray-700);
}

.modal-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.detail-card {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 1.5rem;
    border: 1px solid var(--border);
}

.detail-card h4 {
    color: var(--primary);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.detail-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.detail-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-list li:last-child {
    border-bottom: none;
}

.detail-list li i {
    color: var(--primary);
    width: 20px;
    text-align: center;
}

.detail-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.info-value {
    color: var(--gray-900);
    font-weight: 500;
    text-align: right;
}

.comment-item {
    background: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border: 1px solid var(--border);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.comment-rating {
    color: var(--warning);
    font-size: 0.875rem;
}

.comment-text {
    color: var(--gray-700);
    margin: 0;
    line-height: 1.5;
    font-style: italic;
}

.resource-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 0.5rem;
    border: 1px solid var(--border);
    margin-bottom: 0.75rem;
    transition: var(--transition);
}

.resource-item:hover {
    background: var(--gray-50);
    transform: translateX(5px);
}

.resource-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.resource-icon.pdf {
    background: var(--danger);
}

.resource-icon.video {
    background: var(--success);
}

.resource-icon.task {
    background: var(--primary);
}

/* Loading Animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .clases-container {
        padding: 1rem;
    }
    
    .page-header {
        padding: 2rem 1.5rem;
    }
    
    .page-header h1 {
        font-size: 1.875rem;
    }
    
    .clase-info {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .clase-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .clase-acciones {
        justify-content: center;
    }
    
    .btn {
        padding: 0.75rem 1.25rem;
        font-size: 0.875rem;
    }
    
    .seccion-titulo {
        padding: 1.25rem 1.5rem;
        font-size: 1.25rem;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .clase-card {
        padding: 1.25rem;
        margin: 1rem;
    }
}
    </style>
</head>
<body>
    <?php include BASE_PATH . '/views/components/header.php'; ?>
    
    <div class="clases-container">
        <div class="page-header">
            <h1><i class="fas fa-graduation-cap"></i> Gestión de Clases</h1>
            <p>Administra tus clases inscritas y descubre nuevas oportunidades de aprendizaje</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Sección: Mis Clases Inscritas -->
        <div class="seccion-clases">
            <h2 class="seccion-titulo">
                <span><i class="fas fa-user-graduate"></i> Mis Clases Inscritas</span>
                <span class="stats-badge">
                    <?= count($clasesInscritas) ?> clases
                </span>
            </h2>
            
            <?php if (empty($clasesInscritas)): ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>No tienes clases inscritas</h3>
                    <p>Explora las clases disponibles para inscribirte en una y comenzar tu aprendizaje.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clasesInscritas as $clase): ?>
                    <div class="clase-card <?= ($clase['necesita_mentoria'] ?? false) ? 'necesita-mentoria' : '' ?>">
                        <div class="clase-header">
                            <div class="clase-titulo"><?= htmlspecialchars($clase['titulo']) ?></div>
                            <div class="badges-container">
                                <?php if ($clase['necesita_mentoria'] ?? false): ?>
                                    <span class="mentoria-badge">
                                        <i class="fas fa-exclamation-triangle"></i> Necesitas mentoría
                                    </span>
                                <?php endif; ?>
                                <span class="estado-badge estado-<?= strtolower($clase['estado_descripcion']) ?>">
                                    <?= htmlspecialchars($clase['estado_descripcion']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="clase-info">
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-book"></i> Curso</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($clase['codigo_curso']) ?> - 
                                    <?= htmlspecialchars($clase['nombre_curso']) ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-user-tie"></i> Mentor</span>
                                <span class="info-value">
                                    <?= $clase['nombre_mentor'] ? htmlspecialchars($clase['nombre_mentor']) : 'Por asignar' ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-calendar-alt"></i> Fecha Programada</span>
                                <span class="info-value">
                                    <?= $clase['fecha_programada'] ? date('d/m/Y H:i', strtotime($clase['fecha_programada'])) : 'Por programar' ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-clock"></i> Inscripción</span>
                                <span class="info-value">
                                    <?= date('d/m/Y', strtotime($clase['fecha_inscripcion'])) ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-users"></i> Capacidad</span>
                                <span class="info-value">
                                    <?= $clase['estudiantes_inscritos'] ?>/<?= $clase['capacidad_maxima'] ?> estudiantes
                                </span>
                                <?php 
                                $porcentaje = ($clase['estudiantes_inscritos'] / $clase['capacidad_maxima']) * 100;
                                $colorProgress = $porcentaje >= 90 ? 'progress-danger' : 
                                               ($porcentaje >= 70 ? 'progress-warning' : 'progress-success');
                                ?>
                                <div class="progress-container">
                                    <div class="progress-bar <?= $colorProgress ?>" 
                                         data-progress="<?= $porcentaje ?>"></div>
                                </div>
                                <div class="cupos-text">
                                    <span><?= number_format($porcentaje, 1) ?>% ocupado</span>
                                    <span><?= $clase['capacidad_maxima'] - $clase['estudiantes_inscritos'] ?> cupos libres</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($clase['descripcion']): ?>
                            <div class="clase-descripcion">
                                <i class="fas fa-info-circle"></i>
                                <?= htmlspecialchars($clase['descripcion']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="clase-acciones">
                            <button class="btn btn-primary" onclick="verDetalleClase(<?= $clase['id_clase'] ?>)">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                            
                            <?php if ($clase['enlace_reunion'] && in_array($clase['estado'], [2, 3])): ?>
                                <a href="<?= htmlspecialchars($clase['enlace_reunion']) ?>" 
                                   target="_blank" class="btn btn-success">
                                    <i class="fas fa-video"></i> Unirse a Clase
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Sección: Clases Disponibles -->
        <div class="seccion-clases">
            <h2 class="seccion-titulo">
                <span><i class="fas fa-search"></i> Clases Disponibles</span>
                <span class="stats-badge">
                    <?= count($clasesDisponibles) ?> disponibles
                </span>
            </h2>
            
            <?php if (empty($clasesDisponibles)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No hay clases disponibles</h3>
                    <p>No hay clases disponibles para los cursos en los que estás inscrito en este momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clasesDisponibles as $clase): ?>
                    <div class="clase-card <?= ($clase['necesita_mentoria'] ?? false) ? 'necesita-mentoria' : '' ?>">
                        <div class="clase-header">
                            <div class="clase-titulo"><?= htmlspecialchars($clase['titulo']) ?></div>
                            <div class="badges-container">
                                <?php if ($clase['necesita_mentoria'] ?? false): ?>
                                    <span class="mentoria-badge">
                                        <i class="fas fa-exclamation-triangle"></i> Necesitas mentoría
                                    </span>
                                <?php endif; ?>
                                <span class="estado-badge estado-<?= strtolower($clase['estado_descripcion']) ?>">
                                    <?= htmlspecialchars($clase['estado_descripcion']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="clase-info">
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-book"></i> Curso</span>
                                <span class="info-value">
                                    <?= htmlspecialchars($clase['codigo_curso']) ?> - 
                                    <?= htmlspecialchars($clase['nombre_curso']) ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-user-tie"></i> Mentor</span>
                                <span class="info-value">
                                    <?= $clase['nombre_mentor'] ? htmlspecialchars($clase['nombre_mentor']) : 'Por asignar' ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-calendar-alt"></i> Fecha Programada</span>
                                <span class="info-value">
                                    <?= $clase['fecha_programada'] ? date('d/m/Y H:i', strtotime($clase['fecha_programada'])) : 'Por programar' ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-chart-line"></i> Tu Ponderado</span>
                                <span class="info-value <?= $clase['ponderado'] < 11 ? 'ponderado-bajo' : 'ponderado-alto' ?>">
                                    <i class="fas fa-<?= $clase['ponderado'] < 11 ? 'arrow-down' : 'arrow-up' ?>"></i>
                                    <?= number_format($clase['ponderado'], 2) ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-users"></i> Disponibilidad</span>
                                <?php 
                                $cuposDisponibles = $clase['cupos_disponibles'];
                                $estudiantesInscritos = $clase['estudiantes_inscritos'];
                                $capacidadMaxima = $clase['capacidad_maxima'];
                                $porcentajeOcupado = ($estudiantesInscritos / $capacidadMaxima) * 100;
                                
                                $claseColor = $cuposDisponibles > 10 ? 'cupos-disponibles' : 
                                             ($cuposDisponibles > 0 ? 'cupos-limitados' : 'sin-cupos');
                                $icono = $cuposDisponibles > 10 ? 'check-circle' : 
                                        ($cuposDisponibles > 0 ? 'exclamation-triangle' : 'times-circle');
                                
                                $colorProgress = $porcentajeOcupado >= 90 ? 'progress-danger' : 
                                               ($porcentajeOcupado >= 70 ? 'progress-warning' : 'progress-success');
                                ?>
                                <span class="info-value">
                                    <span class="<?= $claseColor ?>">
                                        <i class="fas fa-<?= $icono ?>"></i>
                                        <?= $cuposDisponibles ?> cupos disponibles
                                    </span>
                                </span>
                                <div class="progress-container">
                                    <div class="progress-bar <?= $colorProgress ?>" 
                                         data-progress="<?= $porcentajeOcupado ?>"></div>
                                </div>
                                <div class="cupos-text">
                                    <span><?= number_format($porcentajeOcupado, 1) ?>% ocupado</span>
                                    <span><?= $estudiantesInscritos ?>/<?= $capacidadMaxima ?> estudiantes</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($clase['descripcion']): ?>
                            <div class="clase-descripcion">
                                <i class="fas fa-info-circle"></i>
                                <?= htmlspecialchars($clase['descripcion']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="clase-acciones">
                            <button class="btn btn-primary" onclick="verDetalleClase(<?= $clase['id_clase'] ?>)">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                            
                            <?php if ($cuposDisponibles > 0): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="inscribir">
                                    <input type="hidden" name="id_clase" value="<?= $clase['id_clase'] ?>">
                                    <button type="submit" class="btn btn-success" 
                                            onclick="return confirmarInscripcion(this)">
                                        <i class="fas fa-user-plus"></i> Inscribirse
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-times"></i> Sin Cupos
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal para detalles de la clase -->
    <div id="detallesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-info-circle"></i>
                    Detalles de la Clase
                </h3>
                <button class="modal-close" type="button">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Contenido se carga dinámicamente -->
            </div>
        </div>
    </div>
    
    <script>
// Variable global para controlar el fetch
let currentFetchController = null;

// Función para animar las barras de progreso
function animarBarrasProgreso() {
    try {
        const barras = document.querySelectorAll('.progress-bar');
        barras.forEach(barra => {
            const progreso = barra.getAttribute('data-progress');
            barra.style.width = '0%';
            setTimeout(() => {
                barra.style.width = progreso + '%';
            }, 500);
        });
    } catch (error) {
        console.error('Error en animarBarrasProgreso:', error);
    }
}

// Función para mostrar modal con detalles
function verDetalleClase(idClase) {
    console.log('Abriendo modal para clase:', idClase);
    
    // Cancelar cualquier fetch anterior
    if (currentFetchController) {
        currentFetchController.abort();
        currentFetchController = null;
    }
    
    const modal = document.getElementById('detallesModal');
    const modalBody = document.getElementById('modalBody');
    
    if (!modal || !modalBody) {
        console.error('Modal o modalBody no encontrado');
        return;
    }
    
    try {
        // Mostrar modal
        modal.classList.add('show');
        
        // Mostrar loading
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <div class="loading" style="width: 40px; height: 40px; border-width: 4px; border-color: var(--primary); border-top-color: transparent;"></div>
                <p style="margin-top: 1rem; color: var(--gray-600);">Cargando información detallada...</p>
            </div>
        `;
        
        // Crear nuevo controller para este fetch
        currentFetchController = new AbortController();
        
        // Fetch real data from database
        fetch(`/views/estudiante/obtener_detalles.php?id_clase=${idClase}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            signal: currentFetchController.signal
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Verificar si el modal sigue abierto
            if (!modal.classList.contains('show')) {
                console.log('Modal cerrado, cancelando carga');
                return;
            }
            
            console.log('Datos recibidos:', data);
            mostrarDetallesEnModal(data);
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                console.log('Fetch cancelado');
                return;
            }
            
            console.error('Error al cargar detalles:', error);
            
            if (modal.classList.contains('show')) {
                modalBody.innerHTML = `
                    <p style="text-align: center; color: var(--danger);">
                        Error al cargar los detalles de la clase. Por favor, inténtalo de nuevo.
                    </p>
                `;
            }
        });
        
    } catch (error) {
        console.error('Error general en verDetalleClase:', error);
        modalBody.innerHTML = `
            <p style="text-align: center; color: var(--danger);">
                Error inesperado. Por favor, inténtalo de nuevo.
            </p>
        `;
    }
}

// Función auxiliar para mostrar detalles en el modal
function mostrarDetallesEnModal(detalle) {
    try {
        const modalBody = document.getElementById('modalBody');
        if (!modalBody) return;
        
        if (detalle.error) {
            modalBody.innerHTML = `<p style="text-align: center; color: var(--danger);">${detalle.error}</p>`;
            return;
        }
        
        modalBody.innerHTML = `
            <div class="detail-grid">
                <div class="detail-card">
                    <h4><i class="fas fa-book"></i> ${detalle.curso.nombre}</h4>
                    <div class="detail-info">
                        <div class="info-row">
                            <span class="info-label">Código del Curso:</span>
                            <span class="info-value">${detalle.curso.codigo}</span>
                        </div>
                        ${detalle.curso.creditos ? `
                        <div class="info-row">
                            <span class="info-label">Créditos:</span>
                            <span class="info-value">${detalle.curso.creditos}</span>
                        </div>
                        ` : ''}
                        <div class="info-row">
                            <span class="info-label">Título de la Clase:</span>
                            <span class="info-value">${detalle.titulo}</span>
                        </div>
                        ${detalle.descripcion ? `
                        <div class="info-row">
                            <span class="info-label">Descripción:</span>
                            <span class="info-value">${detalle.descripcion}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="detail-card">
                    <h4><i class="fas fa-info-circle"></i> Información de la Clase</h4>
                    <div class="detail-info">
                        <div class="info-row">
                            <span class="info-label">Estado:</span>
                            <span class="info-value">${detalle.estado}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Modalidad:</span>
                            <span class="info-value">${detalle.modalidad}</span>
                        </div>
                        ${detalle.duracion ? `
                        <div class="info-row">
                            <span class="info-label">Duración:</span>
                            <span class="info-value">${detalle.duracion}</span>
                        </div>
                        ` : ''}
                        <div class="info-row">
                            <span class="info-label">Capacidad:</span>
                            <span class="info-value">${detalle.estudiantes_inscritos}/${detalle.capacidad_maxima} estudiantes</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cupos Disponibles:</span>
                            <span class="info-value">${detalle.cupos_disponibles}</span>
                        </div>
                        ${detalle.fecha_programada ? `
                        <div class="info-row">
                            <span class="info-label">Fecha Programada:</span>
                            <span class="info-value">${detalle.fecha_programada}</span>
                        </div>
                        ` : ''}
                        ${detalle.fecha_inicio ? `
                        <div class="info-row">
                            <span class="info-label">Fecha de Inicio:</span>
                            <span class="info-value">${detalle.fecha_inicio}</span>
                        </div>
                        ` : ''}
                        ${detalle.fecha_fin ? `
                        <div class="info-row">
                            <span class="info-label">Fecha de Fin:</span>
                            <span class="info-value">${detalle.fecha_fin}</span>
                        </div>
                        ` : ''}
                        ${detalle.enlace_reunion ? `
                        <div class="info-row">
                            <span class="info-label">Enlace de Reunión:</span>
                            <span class="info-value"><a href="${detalle.enlace_reunion}" target="_blank">Acceder a la reunión</a></span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                ${detalle.mentor ? `
                <div class="detail-card">
                    <h4><i class="fas fa-user-tie"></i> Información del Mentor</h4>
                    <div class="detail-info">
                        <div class="info-row">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value">${detalle.mentor.nombre}</span>
                        </div>
                        ${detalle.mentor.email ? `
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value">${detalle.mentor.email}</span>
                        </div>
                        ` : ''}
                        ${detalle.mentor.especialidades ? `
                        <div class="info-row">
                            <span class="info-label">Especialidades:</span>
                            <span class="info-value">${detalle.mentor.especialidades}</span>
                        </div>
                        ` : ''}
                        ${detalle.mentor.calificacion_promedio ? `
                        <div class="info-row">
                            <span class="info-label">Calificación Promedio:</span>
                            <span class="info-value">${detalle.mentor.calificacion_promedio}/5.0</span>
                        </div>
                        ` : ''}
                        <div class="info-row">
                            <span class="info-label">Clases Impartidas:</span>
                            <span class="info-value">${detalle.mentor.total_clases_dadas}</span>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
            
            ${detalle.comentarios && detalle.comentarios.length > 0 ? `
            <div class="detail-card">
                <h4><i class="fas fa-comments"></i> Comentarios de Estudiantes</h4>
                ${detalle.comentarios.map(com => `
                    <div class="comment-item">
                        <div class="comment-header">
                            <strong>${com.estudiante}</strong>
                            <span class="comment-rating">${'★'.repeat(com.puntuacion)}${'☆'.repeat(5-com.puntuacion)}</span>
                            <small>${com.fecha}</small>
                        </div>
                        <p class="comment-text">${com.comentario}</p>
                    </div>
                `).join('')}
            </div>
            ` : ''}
        `;
    } catch (error) {
        console.error('Error en mostrarDetallesEnModal:', error);
        modalBody.innerHTML = '<p style="text-align: center; color: var(--danger);">Error al mostrar los detalles</p>';
    }
}

// Función para cerrar modal
function cerrarModalDetalles() {
    console.log('Cerrando modal');
    
    try {
        // Cancelar cualquier fetch en progreso
        if (currentFetchController) {
            currentFetchController.abort();
            currentFetchController = null;
        }
        
        const modal = document.getElementById('detallesModal');
        if (modal) {
            modal.classList.remove('show');
            
            // Limpiar el contenido del modal después de un breve delay
            setTimeout(() => {
                const modalBody = document.getElementById('modalBody');
                if (modalBody) {
                    modalBody.innerHTML = '';
                }
            }, 300);
        }
    } catch (error) {
        console.error('Error en cerrarModalDetalles:', error);
    }
}

// Función para configurar eventos del modal
function configurarEventosModal() {
    try {
        const modal = document.getElementById('detallesModal');
        
        if (modal) {
            // Remover event listeners existentes
            modal.replaceWith(modal.cloneNode(true));
            const newModal = document.getElementById('detallesModal');
            
            // Cerrar modal al hacer clic fuera
            newModal.addEventListener('click', function(e) {
                if (e.target === newModal) {
                    e.preventDefault();
                    e.stopPropagation();
                    cerrarModalDetalles();
                }
            });
            
            // Cerrar modal con botón X
            const closeBtn = newModal.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    cerrarModalDetalles();
                });
            }
        }
    } catch (error) {
        console.error('Error en configurarEventosModal:', error);
    }
}

// Función para confirmar inscripción
function confirmarInscripcion(button) {
    try {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<div class="loading"></div> Inscribiendo...';
        button.disabled = true;
        
        // Confirmar inscripción
        if (confirm('¿Estás seguro de que quieres inscribirte en esta clase?')) {
            return true;
        } else {
            // Restaurar botón si cancela
            button.innerHTML = originalHTML;
            button.disabled = false;
            return false;
        }
    } catch (error) {
        console.error('Error en confirmarInscripcion:', error);
        return false;
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Inicializando página');
        
        // Configurar eventos del modal
        configurarEventosModal();
        
        // Auto-remover alertas después de 5 segundos
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('alert-success')) {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            }
        });
        
        // Animación de entrada de las tarjetas
        const cards = document.querySelectorAll('.clase-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Animar barras de progreso después de que las tarjetas aparezcan
        setTimeout(() => {
            animarBarrasProgreso();
        }, 1000);
        
    } catch (error) {
        console.error('Error en inicialización:', error);
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    try {
        if (e.key === 'Escape') {
            const modal = document.getElementById('detallesModal');
            if (modal && modal.classList.contains('show')) {
                cerrarModalDetalles();
            }
        }
    } catch (error) {
        console.error('Error en keydown:', error);
    }
});

// Manejar errores globales
window.addEventListener('error', function(e) {
    console.error('Error global capturado:', e.error);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rechazada:', e.reason);
});
    </script>
    
    <!-- SweetAlert2 para modales más bonitos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php include BASE_PATH . '/views/components/footer.php'; ?>
</body>
</html>