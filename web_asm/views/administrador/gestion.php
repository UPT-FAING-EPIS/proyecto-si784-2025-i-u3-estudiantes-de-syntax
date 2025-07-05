<?php
use Google\Service\Dfareporting\Ad;
use Google\Service\MyBusinessAccountManagement\Admin;

$tituloPagina = "Panel de Gestión - Sistema de Mentoría Académica";
require_once BASE_PATH . '/views/components/head.php';
require_once BASE_PATH . '/views/components/header.php';

require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/models/AdminModel.php';

$adminModel = new AdminModel();

// Obtener datos con manejo de errores
try {
    $metricas = $adminModel->obtenerMetricasGenerales();
    $estadisticasGestion = $adminModel->obtenerEstadisticasGestion();
    $actividadReciente = $adminModel->obtenerActividadReciente();
    $estadoSistema = $adminModel->obtenerEstadoSistema();
} catch (Exception $e) {
    error_log("Error en panel admin: " . $e->getMessage());
    // Datos por defecto en caso de error
    $metricas = ['total_usuarios' => 0, 'estudiantes_activos' => 0, 'docentes_mentores' => 0, 'sesiones_programadas' => 0];
    $estadisticasGestion = [];
    $actividadReciente = [];
    $estadoSistema = ['servicios_online' => false];
}

// Función helper para formatear cambios
function formatearCambio($cambio) {
    if ($cambio > 0) {
        return '+' . $cambio . '%';
    } elseif ($cambio < 0) {
        return $cambio . '%';
    } else {
        return 'Sin cambios';
    }
}

// Función helper para clase CSS del cambio
function claseCambio($cambio) {
    if ($cambio > 0) return 'positive';
    if ($cambio < 0) return 'negative';
    return 'neutral';
}

// Configuración segura para JavaScript
$dashboardConfig = [
    'baseUrl' => defined('BASE_URL') ? BASE_URL : '',
    'refreshInterval' => 30000,
    'animationDelay' => 100,
    'metricas' => $metricas ?? [],
    'estadisticas' => $estadisticasGestion ?? [],
    'actividades' => $actividadReciente ?? [],
    'sistema' => $estadoSistema ?? []
];
?>

<!-- Estilos específicos del dashboard (antes del contenido) -->
<style id="dashboard-styles">
/* ===== ESTILOS DEL DASHBOARD ADMIN ===== */
.admin-dashboard {
    min-height: 100vh;
    background: white;
    padding-bottom: 2rem;
}

.dashboard-header {
    background: #2c5282;
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.dashboard-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.dashboard-subtitle {
    margin: 0.5rem 0 0 0;
    font-size: 1.1rem;
    opacity: 0.9;
}

.header-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-header {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(10px);
}

.btn-header:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.btn-header.btn-primary {
    background: #28a745;
    border-color: #28a745;
}

.btn-header.btn-primary:hover {
    background: #218838;
}

/* ===== MÉTRICAS ===== */
.metrics-section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

.last-updated {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.metric-card {
    background: #2c5282;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.metric-card.success::before {
    background: linear-gradient(90deg, #28a745, #20c997);
}

.metric-card.info::before {
    background: linear-gradient(90deg, #17a2b8, #007bff);
}

.metric-card.warning::before {
    background: linear-gradient(90deg, #ffc107, #fd7e14);
}

.metric-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.metric-card.success .metric-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.metric-card.info .metric-icon {
    background: linear-gradient(135deg, #17a2b8, #007bff);
}

.metric-card.warning .metric-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: block;
}

.metric-label {
    font-size: 1.1rem;
    color: white;
    font-weight: 500;
    margin-bottom: 1rem;
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.metric-trend.positive {
    color: #28a745;
}

.metric-trend.negative {
    color: #dc3545;
}

.metric-trend.neutral {
    color: #6c757d;
}

/* ===== ACCIONES ===== */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.action-item {
    background: #2c5282;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.action-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.action-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
}

.action-icon.success {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.action-icon.warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.action-badge {
    background: #e9ecef;
    color: #495057;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.action-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.action-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.action-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

.action-footer {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.action-link {
    color:rgb(7, 77, 152);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.action-item:hover .action-link {
    color:rgb(8, 106, 210);
    transform: translateX(5px);
}

/* ===== ACTIVIDAD RECIENTE ===== */
.activity-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.activity-main {
    color:rgb(115, 148, 240);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.btn-text {
    background: none;
    border: none;
    color: #007bff;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-text:hover {
    color: #0056b3;
    transform: translateX(3px);
}

.activity-timeline {
    position: relative;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-left: 3rem;
    margin-bottom: 2rem;
}

.timeline-dot {
    position: absolute;
    left: 8px;
    top: 8px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 1px #e9ecef;
}

.timeline-dot.success {
    background: #28a745;
}

.timeline-dot.warning {
    background: #ffc107;
}

.timeline-dot.info {
    background: #17a2b8;
}

.timeline-dot.danger {
    background: #dc3545;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    gap: 1rem;
}

.timeline-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: white;
}

.timeline-time {
    font-size: 0.85rem;
    color: white;
    white-space: nowrap;
}

.timeline-content p {
    color: #6c757d;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.timeline-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.meta-badge {
    background: #f8f9fa;
    color: #495057;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.meta-user {
    font-size: 0.85rem;
    color: #6c757d;
}

/* ===== PANEL LATERAL ===== */
.quick-access {
    background: #2c5282;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    height: fit-content;
}

.quick-header h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: #2c3e50;
}

.quick-btn {
    width: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 1.5rem;
}

.quick-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.quick-stats h4 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
}

.status-grid {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
    color: #6c757d;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-indicator.online {
    background: #28a745;
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.4);
}

.status-indicator.offline {
    background: #dc3545;
    box-shadow: 0 0 8px rgba(220, 53, 69, 0.4);
}

.status-indicator.warning {
    background: #ffc107;
    box-shadow: 0 0 8px rgba(255, 193, 7, 0.4);
}

.status-indicator.success {
    background: #28a745;
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.4);
}

/* ===== NOTIFICACIONES ===== */
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
}

.notification {
    background: white;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 12px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
}

.notification.notification-success {
    border-left-color: #28a745;
}

.notification.notification-error {
    border-left-color: #dc3545;
}

.notification.notification-warning {
    border-left-color: #ffc107;
}

.notification.notification-info {
    border-left-color: #17a2b8;
}

.notification-close {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    margin-left: auto;
    transition: all 0.3s ease;
}

.notification-close:hover {
    background: #f8f9fa;
    color: #495057;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .activity-container {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem 0;
    }
    
    .dashboard-title {
        font-size: 1.8rem;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .action-stats {
        justify-content: center;
    }
    
    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

/* ===== ANIMACIONES ===== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.metric-card,
.action-item,
.timeline-item {
    animation: fadeInUp 0.6s ease forwards;
}

.metric-card:nth-child(1) { animation-delay: 0.1s; }
.metric-card:nth-child(2) { animation-delay: 0.2s; }
.metric-card:nth-child(3) { animation-delay: 0.3s; }
.metric-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="dashboard-title">
                        <i class="fas fa-chart-line"></i>
                        Panel de Gestión Administrativo
                    </h1>
                    <p class="dashboard-subtitle">
                        Control total del sistema de mentoría académica UPT
                    </p>
                </div>
                <div class="header-actions">
                    <button class="btn-header" onclick="AdminDashboard.refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar
                    </button>
                    <button class="btn-header btn-primary" onclick="AdminDashboard.openQuickActions()">
                        <i class="fas fa-plus"></i>
                        Acción Rápida
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Métricas Generales -->
        <section class="metrics-section">
            <div class="section-header">
                <h2 class="section-title">Métricas del Sistema</h2>
                <p class="last-updated">Última actualización: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" data-target="<?php echo $metricas['total_usuarios']; ?>">
                            <?php echo number_format($metricas['total_usuarios']); ?>
                        </div>
                        <div class="metric-label">Total Usuarios</div>
                        <div class="metric-trend <?php echo claseCambio($metricas['cambio_usuarios'] ?? 0); ?>">
                            <i class="fas fa-arrow-<?php echo ($metricas['cambio_usuarios'] ?? 0) >= 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo formatearCambio($metricas['cambio_usuarios'] ?? 0); ?> vs mes anterior
                        </div>
                    </div>
                </div>

                <div class="metric-card success">
                    <div class="metric-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" data-target="<?php echo $metricas['estudiantes_activos']; ?>">
                            <?php echo number_format($metricas['estudiantes_activos']); ?>
                        </div>
                        <div class="metric-label">Estudiantes Activos</div>
                        <div class="metric-trend <?php echo claseCambio($metricas['cambio_estudiantes'] ?? 0); ?>">
                            <i class="fas fa-arrow-<?php echo ($metricas['cambio_estudiantes'] ?? 0) >= 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo formatearCambio($metricas['cambio_estudiantes'] ?? 0); ?> vs mes anterior
                        </div>
                    </div>
                </div>

                <div class="metric-card info">
                    <div class="metric-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" data-target="<?php echo $metricas['docentes_mentores']; ?>">
                            <?php echo number_format($metricas['docentes_mentores']); ?>
                        </div>
                        <div class="metric-label">Docentes Mentores</div>
                        <div class="metric-trend <?php echo claseCambio($metricas['cambio_docentes'] ?? 0); ?>">
                            <i class="fas fa-arrow-<?php echo ($metricas['cambio_docentes'] ?? 0) >= 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo formatearCambio($metricas['cambio_docentes'] ?? 0); ?> vs mes anterior
                        </div>
                    </div>
                </div>

                <div class="metric-card warning">
                    <div class="metric-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value" data-target="<?php echo $metricas['sesiones_programadas']; ?>">
                            <?php echo number_format($metricas['sesiones_programadas']); ?>
                        </div>
                        <div class="metric-label">Sesiones Programadas</div>
                        <div class="metric-trend <?php echo claseCambio($metricas['cambio_sesiones'] ?? 0); ?>">
                            <i class="fas fa-arrow-<?php echo ($metricas['cambio_sesiones'] ?? 0) >= 0 ? 'up' : 'down'; ?>"></i>
                            <?php echo formatearCambio($metricas['cambio_sesiones'] ?? 0); ?> vs mes anterior
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Acciones de Gestión -->
        <section class="actions-section">
            <div class="section-header">
                <h2 class="section-title">Gestión del Sistema</h2>
                <div class="section-meta">
                    <span>Herramientas de administración</span>
                </div>
            </div>

            <div class="actions-grid">
                <div class="action-item" onclick="AdminDashboard.navegarA('modificar_usuarios')">
                    <div class="action-header">
                        <div class="action-icon success">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="action-badge">Activo</div>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Modificar Usuarios</h3>
                        <p class="action-description">
                            Administra perfiles, roles y permisos de usuarios del sistema
                        </p>
                        <div class="action-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['total_usuarios'] ?? 0; ?></span>
                                <span class="stat-label">Total</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['usuarios_pendientes'] ?? 0; ?></span>
                                <span class="stat-label">Pendientes</span>
                            </div>
                        </div>
                    </div>
                    <div class="action-footer">
                        <span class="action-link">Gestionar usuarios <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>

                <div class="action-item" onclick="AdminDashboard.navegarA('logs')">
                    <div class="action-header">
                        <div class="action-icon warning">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="action-badge">Análisis</div>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Reportes y Análisis</h3>
                        <p class="action-description">
                            Genera informes detallados y analiza el rendimiento del sistema
                        </p>
                        <div class="action-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['total_reportes'] ?? 0; ?></span>
                                <span class="stat-label">Reportes</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['satisfaccion'] ?? 85; ?>%</span>
                                <span class="stat-label">Satisfacción</span>
                            </div>
                        </div>
                    </div>
                    <div class="action-footer">
                        <span class="action-link">Ver reportes <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Panel de Actividad Reciente -->
        <section class="activity-section">
            <div class="activity-container">
                <div class="activity-main">
                    <div class="section-header">
                        <h2 class="section-title">Actividad Reciente</h2>
                        <button class="btn-text" onclick="AdminDashboard.verTodasActividades()">
                            Ver todas <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <div class="activity-timeline">
                        <?php if (!empty($actividadReciente)): ?>
                            <?php foreach ($actividadReciente as $actividad): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?php echo $actividad['tipo']; ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h4><?php echo htmlspecialchars($actividad['titulo']); ?></h4>
                                        <span class="timeline-time"><?php echo $adminModel->formatearTiempoTranscurrido($actividad['fecha']); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                    <div class="timeline-meta">
                                        <span class="meta-badge"><?php echo htmlspecialchars($actividad['badge']); ?></span>
                                        <span class="meta-user"><?php echo htmlspecialchars($actividad['usuario']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="timeline-item">
                                <div class="timeline-dot info"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h4>Sistema iniciado</h4>
                                        <span class="timeline-time">hace unos momentos</span>
                                    </div>
                                    <p>Panel administrativo cargado correctamente</p>
                                    <div class="timeline-meta">
                                        <span class="meta-badge">Sistema</span>
                                        <span class="meta-user">Admin UPT</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel lateral de acceso rápido -->
                <div class="quick-access">
                    <div class="quick-header">
                        <h3>Acceso Rápido</h3>
                    </div>
                    
                    <div class="quick-actions">
                        <button class="quick-btn" onclick="AdminDashboard.generarReporte()">
                            <i class="fas fa-file-export"></i>
                            <span>Generar Reporte</span>
                        </button>
                    </div>

                    <div class="quick-stats">
                        <h4>Estado del Sistema</h4>
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-indicator <?php echo ($estadoSistema['servicios_online'] ?? false) ? 'online' : 'offline'; ?>"></div>
                                <span>Servicios <?php echo ($estadoSistema['servicios_online'] ?? false) ? 'Online' : 'Offline'; ?></span>
                            </div>
                            <div class="status-item">
                                <div class="status-indicator <?php echo ($estadoSistema['mantenimiento_programado'] ?? false) ? 'warning' : 'success'; ?>"></div>
                                <span><?php echo ($estadoSistema['mantenimiento_programado'] ?? false) ? 'Mantenimiento programado' : 'Sistema operativo'; ?></span>
                            </div>
                            <div class="status-item">
                                <div class="status-indicator <?php echo ($estadoSistema['bd_optimizada'] ?? true) ? 'success' : 'warning'; ?>"></div>
                                <span>Base de datos <?php echo ($estadoSistema['bd_optimizada'] ?? true) ? 'optimizada' : 'requiere optimización'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Script del Dashboard optimizado para evitar conflictos -->
<script>
// ===== PREVENIR CONFLICTOS CON FOOTER =====
if (typeof window.AdminDashboard === 'undefined') {
    
    // ===== CONFIGURACIÓN SEGURA =====
    window.AdminDashboard = (function() {
        'use strict';
        
        // Configuración desde PHP
        const config = <?php echo json_encode($dashboardConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // Variables internas del módulo
        let currentUsers = [];
        let currentPage = 1;
        let usersPerPage = 15;
        let totalUsers = 0;
        let searchTimeout = null;
        let isLoading = false;
        let rolesDisponibles = [];
        
        // ===== FUNCIONES PRINCIPALES =====
        
        /**
         * Actualizar dashboard
         */
        function refreshDashboard() {
            showNotification('Actualizando datos...', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        /**
         * Abrir menú de acciones rápidas
         */
        function openQuickActions() {
            createQuickActionsModal();
        }
        
        /**
         * Navegación principal
         */
        function navegarA(seccion) {
            console.log('Navegando a:', seccion);
            
            const acciones = {
                'anadir_alumnos': () => window.location.href = `${config.baseUrl}/index.php?accion=anadir_alumnos`,
                'modificar_usuarios': () => abrirModalUsuarios(),
                'logs': () => window.location.href = `${config.baseUrl}/index.php?accion=logs`,
                'modificar_clases': () => window.location.href = `${config.baseUrl}/index.php?accion=modificar_clases`,
                'reportes': () => window.location.href = `${config.baseUrl}/index.php?accion=reportes`
            };
            
            if (acciones[seccion]) {
                acciones[seccion]();
            } else {
                console.log('Sección no encontrada:', seccion);
                showNotification('Sección no disponible', 'warning');
            }
        }
        
        /**
         * Ver todas las actividades
         */
        function verTodasActividades() {
            window.location.href = `${config.baseUrl}/index.php?accion=actividades`;
        }
        
        /**
         * Generar reporte
         */
        function generarReporte() {
            showNotification('Redirigiendo a reportes...', 'info');
            window.location.href = `${config.baseUrl}/index.php?accion=reportes`;
        }
        
        // ===== MODAL DE ACCIONES RÁPIDAS =====
        
        function createQuickActionsModal() {
            // Eliminar modal existente si existe
            const existingModal = document.getElementById('quickActionsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            const modal = document.createElement('div');
            modal.id = 'quickActionsModal';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content quick-actions-modal">
                    <div class="modal-header">
                        <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                        <button class="modal-close" onclick="AdminDashboard.closeQuickActions()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="quick-actions-grid">
                            <button class="quick-action-btn" onclick="AdminDashboard.navegarA('anadir_alumnos'); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-user-plus"></i>
                                <span>Añadir Estudiante</span>
                            </button>
                            <button class="quick-action-btn" onclick="AdminDashboard.navegarA('modificar_usuarios'); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-users-cog"></i>
                                <span>Gestionar Usuarios</span>
                            </button>
                            <button class="quick-action-btn" onclick="AdminDashboard.navegarA('modificar_clases'); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Programar Clase</span>
                            </button>
                            <button class="quick-action-btn" onclick="AdminDashboard.navegarA('reportes'); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-chart-bar"></i>
                                <span>Ver Reportes</span>
                            </button>
                            <button class="quick-action-btn" onclick="AdminDashboard.refreshDashboard(); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-sync-alt"></i>
                                <span>Actualizar Dashboard</span>
                            </button>
                            <button class="quick-action-btn" onclick="AdminDashboard.navegarA('logs'); AdminDashboard.closeQuickActions();">
                                <i class="fas fa-file-alt"></i>
                                <span>Ver Logs</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar estilos para el modal si no existen
            if (!document.getElementById('quickActionsStyles')) {
                const styles = document.createElement('style');
                styles.id = 'quickActionsStyles';
                styles.textContent = `
                    .quick-actions-modal {
                        width: 600px;
                        max-width: 90vw;
                    }
                    
                    .quick-actions-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                        gap: 1rem;
                    }
                    
                    .quick-action-btn {
                        background: white;
                        border: 2px solid #e9ecef;
                        border-radius: 12px;
                        padding: 1.5rem 1rem;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 0.5rem;
                        text-align: center;
                        color: #495057;
                        font-weight: 500;
                    }
                    
                    .quick-action-btn:hover {
                        border-color: #007bff;
                        background: #f8f9ff;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 15px rgba(0,123,255,0.2);
                    }
                    
                    .quick-action-btn i {
                        font-size: 1.5rem;
                        color: #007bff;
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            // Animar entrada
            setTimeout(() => {
                modal.style.opacity = '1';
                const modalContent = modal.querySelector('.modal-content');
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }, 10);
            
            // Cerrar al hacer clic fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeQuickActions();
                }
            });
        }
        
        function closeQuickActions() {
            const modal = document.getElementById('quickActionsModal');
            if (modal) {
                const modalContent = modal.querySelector('.modal-content');
                modalContent.style.transform = 'scale(0.8)';
                modalContent.style.opacity = '0';
                modal.style.opacity = '0';
                
                setTimeout(() => {
                    modal.remove();
                    document.body.style.overflow = '';
                }, 300);
            }
        }
        
        // ===== MODAL DE USUARIOS OPTIMIZADO =====
        
        function abrirModalUsuarios() {
            // Crear modal solo si no existe
            if (!document.getElementById('modalUsuarios')) {
                crearModalUsuarios();
            }
            
            const modal = document.getElementById('modalUsuarios');
            modal.style.display = 'flex';
            modal.style.opacity = '0';
            document.body.style.overflow = 'hidden';
            
            // Cargar usuarios automáticamente
            buscarUsuariosAutomatico();
            
            // Animar entrada
            setTimeout(() => {
                modal.style.opacity = '1';
                const modalContent = modal.querySelector('.modal-content');
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }, 10);
            
            // Enfocar búsqueda
            setTimeout(() => {
                const searchInput = document.getElementById('searchInputMain');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 100);
        }
        
        function crearModalUsuarios() {
            const modalHTML = `
                <div id="modalUsuarios" class="modal-overlay" onclick="AdminDashboard.cerrarModalUsuarios(event)">
                    <div class="modal-content modal-usuarios" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h2><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
                            <button class="modal-close" onclick="AdminDashboard.cerrarModalUsuarios()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="modal-body">
                            <!-- Barra de búsqueda -->
                            <div class="search-section">
                                <div class="search-container">
                                    <div class="search-input-wrapper">
                                        <i class="fas fa-search search-icon"></i>
                                        <input 
                                            type="text" 
                                            id="searchInputMain" 
                                            placeholder="Buscar por nombre, email o DNI..." 
                                            class="search-input"
                                            autocomplete="off"
                                        >
                                        <button 
                                            id="clearSearchBtn" 
                                            class="clear-search-btn" 
                                            onclick="AdminDashboard.limpiarBusqueda()"
                                            style="display: none;"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="search-stats">
                                        <span id="searchStats" class="stats-text">Cargando usuarios...</span>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <button onclick="AdminDashboard.actualizarUsuarios()" class="btn-action btn-refresh">
                                        <i class="fas fa-sync-alt"></i>
                                        <span>Actualizar</span>
                                    </button>
                                    <button onclick="AdminDashboard.exportarUsuarios()" class="btn-action btn-export">
                                        <i class="fas fa-download"></i>
                                        <span>Exportar</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Indicador de carga -->
                            <div id="loadingUsers" class="loading-state" style="display: none;">
                                <div class="loading-spinner"></div>
                                <p>Buscando usuarios...</p>
                            </div>
                            
                            <!-- Tabla de usuarios -->
                            <div id="usersTableContainer" class="table-container">
                                <table id="usersTable" class="users-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>DNI</th>
                                            <th>Nombre Completo</th>
                                            <th>Email</th>
                                            <th>Roles</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <!-- Los datos se cargarán aquí -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <div id="paginationContainer" class="pagination-container">
                                <!-- La paginación se generará aquí -->
                            </div>
                            
                            <!-- Estado vacío -->
                            <div id="emptyState" class="empty-state" style="display: none;">
                                <div class="empty-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3>No se encontraron usuarios</h3>
                                <p id="emptyMessage">No hay usuarios que coincidan con la búsqueda.</p>
                                <button onclick="AdminDashboard.limpiarBusqueda()" class="btn-empty">
                                    <i class="fas fa-refresh"></i> Ver todos los usuarios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Configurar búsqueda automática
            configurarBusquedaAutomatica();
            
            // Agregar estilos si no existen
            if (!document.getElementById('modalUserStyles')) {
                agregarEstilosModal();
            }
            
            // Cargar roles disponibles
            cargarRolesDisponibles();
        }
        
        // ===== FUNCIONES DE BÚSQUEDA Y GESTIÓN =====
        
        function configurarBusquedaAutomatica() {
            const searchInput = document.getElementById('searchInputMain');
            const clearBtn = document.getElementById('clearSearchBtn');
            
            if (!searchInput) return;
            
            searchInput.addEventListener('input', function() {
                const valor = this.value.trim();
                
                if (valor.length > 0) {
                    clearBtn.style.display = 'flex';
                } else {
                    clearBtn.style.display = 'none';
                }
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    buscarUsuariosAutomatico(valor);
                }, 300);
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    buscarUsuariosAutomatico(this.value.trim());
                }
            });
        }
        
        async function buscarUsuariosAutomatico(termino = '') {
            if (isLoading) return;
            
            try {
                isLoading = true;
                mostrarCarga(true);
                actualizarEstadisticas('Buscando...');
                
                const offset = (currentPage - 1) * usersPerPage;
                let url = `${config.baseUrl}/index.php?accion=obtener_usuarios_roles&limite=${usersPerPage}&offset=${offset}`;
                
                if (termino && termino.length > 0) {
                    url += `&filtro_tipo=general&filtro_valor=${encodeURIComponent(termino)}`;
                }
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    currentUsers = data.usuarios || [];
                    totalUsers = data.total || currentUsers.length;
                    
                    renderizarTablaUsuarios(currentUsers);
                    generarPaginacion(totalUsers, currentPage);
                    
                    const mensaje = termino 
                        ? `${totalUsers} resultado(s) para "${termino}"`
                        : `${totalUsers} usuario(s) total`;
                    actualizarEstadisticas(mensaje);
                    
                } else {
                    throw new Error(data.message || 'Error al cargar usuarios');
                }
                
            } catch (error) {
                console.error('Error en búsqueda:', error);
                showNotification('Error al buscar usuarios: ' + error.message, 'error');
                mostrarEstadoVacio('Error al cargar los datos');
                actualizarEstadisticas('Error en la búsqueda');
            } finally {
                isLoading = false;
                mostrarCarga(false);
            }
        }
        
        function renderizarTablaUsuarios(usuarios) {
            const tbody = document.getElementById('usersTableBody');
            const tableContainer = document.getElementById('usersTableContainer');
            const emptyState = document.getElementById('emptyState');
            
            if (!usuarios || usuarios.length === 0) {
                tableContainer.style.display = 'none';
                emptyState.style.display = 'flex';
                return;
            }
            
            tableContainer.style.display = 'block';
            emptyState.style.display = 'none';
            
            tbody.innerHTML = usuarios.map(usuario => {
                const nombreCompleto = `${usuario.NOMBRE || ''} ${usuario.APELLIDO || ''}`.trim() || 'Sin nombre';
                const estado = usuario.ACTIVO == 1 ? 'Activo' : 'Inactivo';
                const estadoClass = usuario.ACTIVO == 1 ? 'status-active' : 'status-inactive';
                
                let rolesHtml = '';
                if (usuario.ROLES_ARRAY && usuario.ROLES_ARRAY.length > 0) {
                    rolesHtml = usuario.ROLES_ARRAY.map(rol => 
                        `<span class="role-badge">${rol.nombre}</span>`
                    ).join('');
                } else {
                    rolesHtml = '<span class="role-badge role-empty">Sin rol</span>';
                }
                
                return `
                    <tr class="user-row" data-user-id="${usuario.ID_USUARIO}">
                        <td>${usuario.ID_USUARIO}</td>
                        <td>${usuario.DNI || '-'}</td>
                        <td>
                            <div class="user-info">
                                <span class="user-name">${nombreCompleto}</span>
                                ${usuario.OAUTH_PROVIDER ? `<span class="oauth-badge">${usuario.OAUTH_PROVIDER}</span>` : ''}
                            </div>
                        </td>
                        <td class="email-cell">${usuario.EMAIL || 'Sin email'}</td>
                        <td>
                            <div class="roles-wrapper">
                                ${rolesHtml}
                            </div>
                        </td>
                        <td>
                            <span class="status-badge ${estadoClass}">
                                <i class="fas fa-${usuario.ACTIVO == 1 ? 'check-circle' : 'times-circle'}"></i>
                                ${estado}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button onclick="AdminDashboard.verDetalleUsuario(${usuario.ID_USUARIO})" class="btn-table btn-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="AdminDashboard.editarUsuario(${usuario.ID_USUARIO})" class="btn-table btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // ===== FUNCIONES AUXILIARES =====
        
        function mostrarCarga(mostrar) {
            const loading = document.getElementById('loadingUsers');
            const tableContainer = document.getElementById('usersTableContainer');
            const emptyState = document.getElementById('emptyState');
            
            if (mostrar) {
                loading.style.display = 'flex';
                tableContainer.style.display = 'none';
                emptyState.style.display = 'none';
            } else {
                loading.style.display = 'none';
            }
        }
        
        function mostrarEstadoVacio(mensaje = 'No se encontraron usuarios') {
            const emptyState = document.getElementById('emptyState');
            const emptyMessage = document.getElementById('emptyMessage');
            const tableContainer = document.getElementById('usersTableContainer');
            
            if (emptyMessage) {
                emptyMessage.textContent = mensaje;
            }
            
            tableContainer.style.display = 'none';
            emptyState.style.display = 'flex';
        }
        
        function actualizarEstadisticas(mensaje) {
            const statsElement = document.getElementById('searchStats');
            if (statsElement) {
                statsElement.textContent = mensaje;
            }
        }
        
        function limpiarBusqueda() {
            const searchInput = document.getElementById('searchInputMain');
            const clearBtn = document.getElementById('clearSearchBtn');
            
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
            
            currentPage = 1;
            buscarUsuariosAutomatico('');
        }
        
        function actualizarUsuarios() {
            showNotification('Actualizando lista de usuarios...', 'info');
            currentPage = 1;
            const termino = document.getElementById('searchInputMain')?.value || '';
            buscarUsuariosAutomatico(termino);
        }
        
        function exportarUsuarios() {
            if (!currentUsers || currentUsers.length === 0) {
                showNotification('No hay usuarios para exportar', 'warning');
                return;
            }
            
            showNotification('Exportando usuarios...', 'info');
            
            const headers = ['ID', 'DNI', 'Nombre', 'Apellido', 'Email', 'Roles', 'Estado'];
            const csvContent = [
                headers.join(','),
                ...currentUsers.map(user => {
                    const roles = user.ROLES_ARRAY ? user.ROLES_ARRAY.map(r => r.nombre).join(';') : 'Sin rol';
                    return [
                        user.ID_USUARIO,
                        `"${user.DNI || ''}"`,
                        `"${user.NOMBRE || ''}"`,
                        `"${user.APELLIDO || ''}"`,
                        `"${user.EMAIL || ''}"`,
                        `"${roles}"`,
                        user.ACTIVO == 1 ? 'Activo' : 'Inactivo'
                    ].join(',');
                })
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `usuarios_ams_upt_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            
            showNotification('Archivo CSV descargado correctamente', 'success');
        }
        
        function generarPaginacion(total, pagina) {
            const container = document.getElementById('paginationContainer');
            
            if (total <= usersPerPage) {
                container.innerHTML = '';
                return;
            }
            
            const totalPaginas = Math.ceil(total / usersPerPage);
            let paginationHTML = '<div class="pagination">';
            
            if (currentPage > 1) {
                paginationHTML += `
                    <button onclick="AdminDashboard.cambiarPagina(${currentPage - 1})" class="page-btn page-prev">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                `;
            }
            
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPaginas, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === currentPage;
                paginationHTML += `
                    <button onclick="AdminDashboard.cambiarPagina(${i})" class="page-btn ${isActive ? 'active' : ''}">
                        ${i}
                    </button>
                `;
            }
            
            if (currentPage < totalPaginas) {
                paginationHTML += `
                    <button onclick="AdminDashboard.cambiarPagina(${currentPage + 1})" class="page-btn page-next">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }
            
            paginationHTML += '</div>';
            paginationHTML += `<div class="pagination-info">Página ${currentPage} de ${totalPaginas} (${total} usuarios)</div>`;
            
            container.innerHTML = paginationHTML;
        }
        
        function cambiarPagina(nuevaPagina) {
            currentPage = nuevaPagina;
            const termino = document.getElementById('searchInputMain')?.value || '';
            buscarUsuariosAutomatico(termino);
        }
        
        function cerrarModalUsuarios(event) {
            if (event && event.target !== event.currentTarget) {
                return;
            }
            
            const modal = document.getElementById('modalUsuarios');
            if (!modal) return;
            
            const modalContent = modal.querySelector('.modal-content');
            
            modalContent.style.transform = 'scale(0.8)';
            modalContent.style.opacity = '0';
            modal.style.opacity = '0';
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
        
        async function cargarRolesDisponibles() {
            try {
                const response = await fetch(`${config.baseUrl}/index.php?accion=obtener_roles`);
                const data = await response.json();
                
                if (data.success) {
                    rolesDisponibles = data.roles;
                }
            } catch (error) {
                console.error('Error cargando roles:', error);
            }
        }
        
        function verDetalleUsuario(userId) {
            showNotification('Cargando detalles del usuario...', 'info');
            // Implementar vista de detalles
        }
        
        function editarUsuario(userId) {
            showNotification('Abriendo editor de usuario...', 'info');
            // Implementar edición de usuario
        }
        
        // ===== FUNCIONES DE NOTIFICACIÓN =====
        
        function showNotification(message, type = 'info', duration = 4000) {
            // Usar la función global si existe, sino crear propia
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type, duration);
                return;
            }
            
            // Crear contenedor si no existe
            let container = document.getElementById('notificationContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notificationContainer';
                container.className = 'notification-container';
                document.body.appendChild(container);
            }
            
            // Crear notificación
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const iconMap = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            notification.innerHTML = `
                <i class="${iconMap[type]}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(notification);
            
            // Auto-remove
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, duration);
            
            // Animar entrada
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);
        }
        
        // ===== AGREGAR ESTILOS DEL MODAL =====
        
        function agregarEstilosModal() {
            const estilos = document.createElement('style');
            estilos.id = 'modalUserStyles';
            estilos.textContent = `
                /* ESTILOS PARA MODAL DE USUARIOS */
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    backdrop-filter: blur(3px);
                    transition: opacity 0.3s ease;
                    opacity: 0;
                }
                
                .modal-content {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-height: 90vh;
                    overflow-y: auto;
                    transform: scale(0.8);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                    margin: auto;
                    box-sizing: border-box;
                }
                
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 25px;
                    border-bottom: 1px solid #e9ecef;
                    background: linear-gradient(135deg, #1e3a5f 0%, #2c5aa0 100%);
                    color: white;
                    border-radius: 16px 16px 0 0;
                }
                
                .modal-header h2,
                .modal-header h3 {
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 1.4rem;
                    font-weight: 600;
                }
                
                .modal-close {
                    background: rgba(255, 255, 255, 0.1);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    color: white;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                    font-size: 1.1rem;
                }
                
                .modal-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: rotate(90deg);
                }
                
                .modal-body {
                    padding: 25px;
                }
                
                .modal-usuarios {
                    width: 95vw;
                    max-width: 1400px;
                    height: 90vh;
                    max-height: 900px;
                }
                
                .search-section {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 20px;
                    gap: 20px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 12px;
                    border: 1px solid #e9ecef;
                }
                
                .search-container {
                    flex: 1;
                    min-width: 300px;
                }
                
                .search-input-wrapper {
                    position: relative;
                    display: flex;
                    align-items: center;
                    margin-bottom: 8px;
                }
                
                .search-icon {
                    position: absolute;
                    left: 15px;
                    color: #6c757d;
                    z-index: 2;
                }
                
                .search-input {
                    width: 100%;
                    padding: 12px 15px 12px 45px;
                    border: 2px solid #dee2e6;
                    border-radius: 8px;
                    font-size: 1rem;
                    background: white;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                
                .search-input:focus {
                    outline: none;
                    border-color: #007bff;
                    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
                }
                
                .clear-search-btn {
                    position: absolute;
                    right: 10px;
                    background: none;
                    border: none;
                    color: #6c757d;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 4px;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                }
                
                .clear-search-btn:hover {
                    color: #dc3545;
                    background: #f8f9fa;
                }
                
                .stats-text {
                    color: #6c757d;
                    font-size: 0.9rem;
                    font-weight: 500;
                }
                
                .action-buttons {
                    display: flex;
                    gap: 10px;
                    flex-shrink: 0;
                }
                
                .btn-action {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px 16px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    white-space: nowrap;
                }
                
                .btn-refresh {
                    background: #28a745;
                    color: white;
                }
                
                .btn-refresh:hover {
                    background: #218838;
                    transform: translateY(-1px);
                }
                
                .btn-export {
                    background: #17a2b8;
                    color: white;
                }
                
                .btn-export:hover {
                    background: #138496;
                    transform: translateY(-1px);
                }
                
                .loading-state {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 60px 20px;
                    color: #6c757d;
                    background: #f8f9fa;
                    border-radius: 12px;
                    margin: 20px 0;
                }
                
                .loading-spinner {
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 15px;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .table-container {
                    border-radius: 12px;
                    border: 1px solid #dee2e6;
                    overflow: hidden;
                    background: white;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    max-height: 60vh;
                    overflow-y: auto;
                }
                
                .users-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 0.9rem;
                }
                
                .users-table th {
                    background: linear-gradient(135deg, #1e3a5f 0%, #2c5aa0 100%);
                    color: white;
                    padding: 15px 12px;
                    text-align: left;
                    font-weight: 600;
                    position: sticky;
                    top: 0;
                    z-index: 10;
                    border-bottom: 2px solid #0056b3;
                }
                
                .users-table td {
                    padding: 12px;
                    border-bottom: 1px solid #dee2e6;
                    vertical-align: middle;
                }
                
                .users-table tbody tr {
                    transition: all 0.3s ease;
                }
                
                .users-table tbody tr:hover {
                    background: #f8f9fa;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .user-info {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }
                
                .user-name {
                    font-weight: 500;
                    color: #2c5aa0;
                }
                
                .oauth-badge {
                    background: linear-gradient(135deg, #17a2b8, #20c997);
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 0.7rem;
                    font-weight: 500;
                    align-self: flex-start;
                }
                
                .email-cell {
                    color: #6c757d;
                    font-size: 0.85rem;
                }
                
                .roles-wrapper {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 4px;
                }
                
                .role-badge {
                    padding: 3px 8px;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    white-space: nowrap;
                    background: #e3f2fd;
                    color: #1976d2;
                }
                
                .role-empty {
                    background: #f5f5f5;
                    color: #757575;
                }
                
                .status-badge {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 500;
                }
                
                .status-active {
                    background: #d4edda;
                    color: #155724;
                }
                
                .status-inactive {
                    background: #f8d7da;
                    color: #721c24;
                }
                
                .action-buttons {
                    display: flex;
                    gap: 6px;
                    justify-content: center;
                }
                
                .btn-table {
                    background: none;
                    border: 1px solid;
                    padding: 6px 8px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 0.85rem;
                }
                
                .btn-view {
                    color: #007bff;
                    border-color: #007bff;
                }
                
                .btn-view:hover {
                    background: #007bff;
                    color: white;
                    transform: translateY(-1px);
                }
                
                .btn-edit {
                    color: #28a745;
                    border-color: #28a745;
                }
                
                .btn-edit:hover {
                    background: #28a745;
                    color: white;
                    transform: translateY(-1px);
                }
                
                .empty-state {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 80px 20px;
                    text-align: center;
                    background: #f8f9fa;
                    border-radius: 12px;
                    margin: 20px 0;
                }
                
                .empty-icon {
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #e9ecef, #dee2e6);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 20px;
                }
                
                .empty-icon i {
                    font-size: 2.5rem;
                    color: #6c757d;
                }
                
                .empty-state h3 {
                    margin-bottom: 10px;
                    color: #495057;
                    font-size: 1.5rem;
                }
                
                .empty-state p {
                    color: #6c757d;
                    margin-bottom: 20px;
                    font-size: 1rem;
                }
                
                .btn-empty {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .btn-empty:hover {
                    background: #0056b3;
                    transform: translateY(-2px);
                }
                
                .pagination-container {
                    margin-top: 20px;
                    text-align: center;
                }
                
                .pagination {
                    display: inline-flex;
                    gap: 8px;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .page-btn {
                    background: white;
                    color: #007bff;
                    border: 1px solid #dee2e6;
                    padding: 8px 12px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-weight: 500;
                    min-width: 40px;
                }
                
                .page-btn:hover {
                    background: #007bff;
                    color: white;
                    border-color: #007bff;
                    transform: translateY(-1px);
                }
                
                .page-btn.active {
                    background: #007bff;
                    color: white;
                    border-color: #007bff;
                }
                
                .page-prev, .page-next {
                    padding: 8px 16px;
                }
                
                .pagination-info {
                    color: #6c757d;
                    font-size: 0.9rem;
                }
                
                /* Responsive */
                @media (max-width: 1200px) {
                    .modal-usuarios {
                        width: 98vw;
                        height: 95vh;
                    }
                    
                    .search-section {
                        flex-direction: column;
                        align-items: stretch;
                    }
                    
                    .action-buttons {
                        justify-content: center;
                    }
                }
                
                @media (max-width: 768px) {
                    .users-table {
                        font-size: 0.8rem;
                    }
                    
                    .users-table th,
                    .users-table td {
                        padding: 8px 6px;
                    }
                    
                    .btn-action span {
                        display: none;
                    }
                    
                    .pagination {
                        flex-wrap: wrap;
                        justify-content: center;
                    }
                }
            `;
            
            document.head.appendChild(estilos);
        }
        
        // ===== ANIMACIONES Y EFECTOS =====
        
        function animateCounter(element) {
            const target = parseInt(element.getAttribute('data-target')) || 0;
            const duration = 2000;
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        }
        
        function initializeAnimations() {
            // Animar contadores
            document.querySelectorAll('.metric-value[data-target]').forEach(counter => {
                animateCounter(counter);
            });
            
            // Animar entrada de elementos
            const items = document.querySelectorAll('.metric-card, .action-item, .timeline-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // ===== INICIALIZACIÓN =====
        
        function init() {
            console.log('Inicializando AdminDashboard...');
            
            // Esperar a que el DOM esté completamente cargado
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeAnimations);
            } else {
                setTimeout(initializeAnimations, 100);
            }
            
            // Configurar escape para cerrar modales
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const modal = document.querySelector('.modal-overlay');
                    if (modal) {
                        const closeButton = modal.querySelector('.modal-close');
                        if (closeButton) {
                            closeButton.click();
                        }
                    }
                }
            });
            
            console.log('AdminDashboard inicializado correctamente');
        }
        
        // ===== API PÚBLICA =====
        
        return {
            // Funciones principales
            refreshDashboard,
            openQuickActions,
            closeQuickActions,
            navegarA,
            verTodasActividades,
            generarReporte,
            
            // Gestión de usuarios
            abrirModalUsuarios,
            cerrarModalUsuarios,
            buscarUsuariosAutomatico,
            limpiarBusqueda,
            actualizarUsuarios,
            exportarUsuarios,
            cambiarPagina,
            verDetalleUsuario,
            editarUsuario,
            
            // Utilidades
            showNotification,
            animateCounter,
            init,
            
            // Configuración
            config
        };
        
    })();
    
    // ===== AUTO-INICIALIZACIÓN =====
    AdminDashboard.init();
    
    console.log('AdminDashboard module loaded successfully');
}

// ===== COMPATIBILIDAD CON FUNCIONES GLOBALES =====

// Exponer funciones principales globalmente para compatibilidad
window.refreshDashboard = function() { AdminDashboard.refreshDashboard(); };
window.openQuickActions = function() { AdminDashboard.openQuickActions(); };
window.navegarA = function(seccion) { AdminDashboard.navegarA(seccion); };
window.verTodasActividades = function() { AdminDashboard.verTodasActividades(); };
window.generarReporte = function() { AdminDashboard.generarReporte(); };
window.abrirModalUsuarios = function() { AdminDashboard.abrirModalUsuarios(); };

// Asegurar que showNotification esté disponible globalmente
if (typeof window.showNotification !== 'function') {
    window.showNotification = function(message, type, duration) { 
        AdminDashboard.showNotification(message, type, duration); 
    };
}

console.log('Dashboard Admin cargado - Versión optimizada sin conflictos');
</script>

<?php
// Incluir footer al final
require_once BASE_PATH . '/views/components/footer.php';
?>