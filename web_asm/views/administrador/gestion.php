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
?>

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
                    <button class="btn-header" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar
                    </button>
                    <button class="btn-header btn-primary" onclick="openQuickActions()">
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
                <div class="action-item" onclick="navegarA('anadir_alumnos')">
                    <div class="action-header">
                        <div class="action-icon primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-badge">Nuevo</div>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Añadir Estudiantes</h3>
                        <p class="action-description">
                            Registra nuevos estudiantes y gestiona su información académica
                        </p>
                        <div class="action-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['estudiantes_hoy'] ?? 0; ?></span>
                                <span class="stat-label">Hoy</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['estudiantes_mes'] ?? 0; ?></span>
                                <span class="stat-label">Este mes</span>
                            </div>
                        </div>
                    </div>
                    <div class="action-footer">
                        <span class="action-link">Gestionar estudiantes <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>

                <div class="action-item" onclick="navegarA('modificar_usuarios')">
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

                <div class="action-item" onclick="navegarA('modificar_clases')">
                    <div class="action-header">
                        <div class="action-icon info">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="action-badge">Programado</div>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Gestionar Clases</h3>
                        <p class="action-description">
                            Administra horarios, materias y asignaciones de mentoría
                        </p>
                        <div class="action-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['clases_activas'] ?? 0; ?></span>
                                <span class="stat-label">Activas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $estadisticasGestion['clases_hoy'] ?? 0; ?></span>
                                <span class="stat-label">Hoy</span>
                            </div>
                        </div>
                    </div>
                    <div class="action-footer">
                        <span class="action-link">Gestionar clases <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>

                <div class="action-item" onclick="navegarA('reportes')">
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
                        <button class="btn-text" onclick="verTodasActividades()">
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
                        <button class="quick-btn" onclick="crearSesion()">
                            <i class="fas fa-plus-circle"></i>
                            <span>Nueva Sesión</span>
                        </button>    
                        <button class="quick-btn" onclick="generarReporte()">
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
<script>
// ========================================
// CONFIGURACIÓN GLOBAL DEL DASHBOARD
// ========================================

// Configuraciones globales
window.BASE_URL = '<?php echo BASE_URL; ?>';
window.dashboardConfig = {
    baseUrl: '<?php echo BASE_URL; ?>',
    refreshInterval: 30000,
    animationDelay: 100
};

window.dashboardData = {
    metricas: <?php echo json_encode($metricas ?? []); ?>,
    estadisticas: <?php echo json_encode($estadisticasGestion ?? []); ?>,
    actividades: <?php echo json_encode($actividadReciente ?? []); ?>,
    sistema: <?php echo json_encode($estadoSistema ?? []); ?>
};

console.log('Dashboard data:', window.dashboardData);

// Variables globales para la gestión de usuarios
let currentUsers = [];
let currentPage = 1;
let usersPerPage = 20;
let totalUsers = 0;

// Variables globales para roles
let rolesDisponibles = [];
/**
 * Actualizar datos del dashboard
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
    // Implementar modal de acciones rápidas
    showNotification('Menú de acciones rápidas en desarrollo', 'info');
}

/**
 * Ver todas las actividades
 */
function verTodasActividades() {
    window.location.href = `${window.BASE_URL}/index.php?accion=actividades`;
}

/**
 * Ver solicitudes pendientes
 */
function verPendientes() {
    showNotification('Cargando solicitudes pendientes...', 'info');
    setTimeout(() => {
        window.location.href = `${window.BASE_URL}/index.php?accion=pendientes`;
    }, 1000);
}

/**
 * Buscar usuario (función placeholder)
 */
function buscarUsuario() {
    if (typeof window.buscarUsuario === 'function') {
        window.buscarUsuario();
    } else {
        // Abrir modal de usuarios como alternativa
        abrirModalUsuarios();
    }
}

/**
 * Crear nueva sesión
 */
function crearSesion() {
    showNotification('Redirigiendo a crear sesión...', 'info');
    window.location.href = `${window.BASE_URL}/index.php?accion=crear_sesion`;
}

/**
 * Generar reporte
 */
function generarReporte() {
    showNotification('Redirigiendo a reportes...', 'info');
    window.location.href = `${window.BASE_URL}/index.php?accion=reportes`;
}

// ========================================
// ANIMACIONES Y EFECTOS
// ========================================

/**
 * Animar contadores de métricas
 */
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000; // 2 segundos
    const start = 0;
    const increment = target / (duration / 16); // 60 FPS
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

/**
 * Inicializar animaciones al cargar
 */
document.addEventListener('DOMContentLoaded', function() {
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
});
/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info', duration = 5000) {
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
function navegarA(seccion) {
    console.log('Navegando a:', seccion);
    
    switch(seccion) {
        case 'anadir_alumnos':
            window.location.href = `${window.BASE_URL}/index.php?accion=anadir_alumnos`;
            break;
        case 'modificar_usuarios':
            abrirModalUsuarios();
            break;
        case 'modificar_clases':
            window.location.href = `${window.BASE_URL}/index.php?accion=modificar_clases`;
            break;
        case 'reportes':
            window.location.href = `${window.BASE_URL}/index.php?accion=reportes`;
            break;
        default:
            console.log('Sección no encontrada:', seccion);
    }
}

/**
 * Abrir modal de gestión de usuarios
 */
function abrirModalUsuarios() {
    // Crear el modal si no existe
    if (!document.getElementById('modalUsuarios')) {
        crearModalUsuarios();
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalUsuarios');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Cargar usuarios al abrir el modal
    cargarUsuarios();
    
    // Animar entrada
    setTimeout(() => {
        modal.querySelector('.modal-content').style.transform = 'scale(1)';
        modal.querySelector('.modal-content').style.opacity = '1';
    }, 10);
}

/**
 * Crear el modal de usuarios dinámicamente
 */
function crearModalUsuarios() {
    const modalHTML = `
        <div id="modalUsuarios" class="modal-overlay" onclick="cerrarModalUsuarios(event)">
            <div class="modal-content modal-large" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
                    <button class="modal-close" onclick="cerrarModalUsuarios()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- Barra de búsqueda simplificada -->
                    <div class="search-section">
                        <div class="search-container">
                            <div class="search-input-group">
                                <input type="text" id="searchInput" placeholder="Buscar por nombre o correo..." class="search-input">
                                <button onclick="buscarUsuarios()" class="search-btn">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button onclick="limpiarBusqueda()" class="btn-clear">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button onclick="cargarUsuarios()" class="btn-refresh">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Loading state -->
                    <div id="loadingUsers" class="loading-state" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>Cargando usuarios...</p>
                    </div>
                    
                    <!-- Tabla de usuarios más grande -->
                    <div id="usersTableContainer" class="table-container-large">
                        <table id="usersTable" class="users-table-large">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 250px;">Nombre Completo</th>
                                    <th style="width: 200px;">Email</th>
                                    <th style="width: 150px;">Roles</th>
                                    <th style="width: 120px;">Código</th>
                                    <th style="width: 100px;">Estado</th>
                                    <th style="width: 130px;">Fecha Registro</th>
                                    <th style="width: 120px;">Acciones</th>
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
                        <i class="fas fa-users"></i>
                        <h3>No se encontraron usuarios</h3>
                        <p>No hay usuarios que coincidan con los criterios de búsqueda.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de edición de usuario -->
        <div id="editUserModal" class="modal-overlay" style="display: none;">
            <div class="modal-content modal-medium">
                <div class="modal-header">
                    <h3><i class="fas fa-user-edit"></i> Editar Usuario</h3>
                    <button class="modal-close" onclick="cerrarModalEdicion()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId">
                        
                        <div class="form-group">
                            <label for="editUserEmail">Email:</label>
                            <input type="email" id="editUserEmail" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Roles del usuario:</label>
                            <div id="rolesContainer" class="roles-container">
                                <!-- Los checkboxes de roles se cargarán aquí -->
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" onclick="cerrarModalEdicion()" class="btn-cancel">
                                Cancelar
                            </button>
                            <button type="button" onclick="guardarCambiosUsuario()" class="btn-save">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Agregar event listeners simplificados
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarUsuarios();
        }
    });
    
    // Auto-búsqueda después de escribir
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 2 || this.value.length === 0) {
                buscarUsuarios();
            }
        }, 500);
    });
    
    // Agregar estilos si no existen
    if (!document.getElementById('modalUserStyles')) {
        agregarEstilosModal();
    }
    
    // Cargar roles disponibles
    cargarRolesDisponibles();
}

/**
 * Cargar roles disponibles
 */
async function cargarRolesDisponibles() {
    try {
        const response = await fetch(`${window.BASE_URL}/index.php?accion=obtener_roles`);
        const data = await response.json();
        
        if (data.success) {
            rolesDisponibles = data.roles;
        }
    } catch (error) {
        console.error('Error cargando roles:', error);
    }
}

/**
 * Cargar todos los usuarios con roles (búsqueda simplificada)
 */
async function cargarUsuarios(pagina = 1) {
    try {
        mostrarCarga(true);
        
        const offset = (pagina - 1) * usersPerPage;
        let url = `${window.BASE_URL}/index.php?accion=obtener_usuarios_roles&limite=${usersPerPage}&offset=${offset}`;
        
        // Agregar filtro de búsqueda simple
        const filtroValor = document.getElementById('searchInput')?.value;
        
        if (filtroValor && filtroValor.trim()) {
            // Búsqueda general en nombre y email
            url += `&filtro_tipo=general&filtro_valor=${encodeURIComponent(filtroValor.trim())}`;
        }
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            currentUsers = data.usuarios;
            currentPage = pagina;
            totalUsers = data.total || data.usuarios.length;
            renderizarTablaUsuarios(currentUsers);
            generarPaginacion(data.total || data.usuarios.length, pagina);
        } else {
            throw new Error(data.message || 'Error al cargar usuarios');
        }
        
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        showNotification('Error al cargar los usuarios: ' + error.message, 'error');
        mostrarEstadoVacio();
    } finally {
        mostrarCarga(false);
    }
}

/**
 * Función de búsqueda simplificada
 */
function buscarUsuarios() {
    cargarUsuarios(1); // Reiniciar a la primera página
}
/**
 * Limpiar búsqueda
 */
function limpiarBusqueda() {
    document.getElementById('searchInput').value = '';
    cargarUsuarios(1);
}

/**
 * Renderizar tabla de usuarios con múltiples roles
 */
function renderizarTablaUsuarios(usuarios) {
    const tbody = document.getElementById('usersTableBody');
    
    if (!usuarios || usuarios.length === 0) {
        mostrarEstadoVacio();
        return;
    }
    
    // Ocultar estado vacío
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('usersTableContainer').style.display = 'block';
    
    tbody.innerHTML = usuarios.map(usuario => {
        const fechaRegistro = new Date(usuario.FECHA_REG).toLocaleDateString('es-ES');
        const nombreCompleto = `${usuario.NOMBRE || ''} ${usuario.APELLIDO || ''}`.trim() || 'Sin nombre';
        const estado = usuario.ACTIVO == 1 ? 'Activo' : 'Inactivo';
        const estadoClass = usuario.ACTIVO == 1 ? 'status-active' : 'status-inactive';
        const oauthBadge = usuario.OAUTH_PROVIDER ? `<span class="oauth-badge">${usuario.OAUTH_PROVIDER}</span>` : '';
        
        // Renderizar roles múltiples
        let rolesHtml = '';
        if (usuario.ROLES_ARRAY && usuario.ROLES_ARRAY.length > 0) {
            rolesHtml = usuario.ROLES_ARRAY.map(rol => 
                `<span class="role-badge role-${rol.nombre.toLowerCase()}">${rol.nombre}</span>`
            ).join(' ');
        } else {
            rolesHtml = '<span class="role-badge role-sin-rol">Sin rol</span>';
        }
        
        return `
            <tr class="user-row" data-user-id="${usuario.ID_USUARIO}">
                <td>${usuario.ID_USUARIO}</td>
                <td>${usuario.DNI || 'Sin DNI'}</td>
                <td>
                    <div class="user-name-cell">
                        ${nombreCompleto}
                        ${oauthBadge}
                    </div>
                </td>
                <td>${usuario.EMAIL || 'Sin email'}</td>
                <td>
                    <div class="roles-cell">
                        ${rolesHtml}
                    </div>
                </td>
                <td>${usuario.CODIGO_ESTUDIANTE || '-'}</td>
                <td>
                    <span class="status-badge ${estadoClass}">
                        ${estado}
                    </span>
                </td>
                <td>${fechaRegistro}</td>
                <td>
                    <div class="action-buttons">
                        <button onclick="verDetalleUsuario(${usuario.ID_USUARIO})" class="btn-action btn-view" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editarUsuario(${usuario.ID_USUARIO})" class="btn-action btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Ver detalle de usuario
 */
async function verDetalleUsuario(userId) {
    try {
        const response = await fetch(`${window.BASE_URL}/index.php?accion=detalle_usuario&id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const usuario = data.usuario;
            mostrarModalDetalle(usuario);
        } else {
            showNotification('Error al cargar detalles del usuario', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al cargar detalles', 'error');
    }
}

/**
 * Mostrar modal con detalles del usuario
 */
function mostrarModalDetalle(usuario) {
    const modalHTML = `
        <div id="userDetailModal" class="modal-overlay" onclick="cerrarModalDetalle()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3><i class="fas fa-user"></i> Detalles del Usuario</h3>
                    <button class="modal-close" onclick="cerrarModalDetalle()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="user-detail-grid">
                        <div class="detail-section">
                            <h4>Información Personal</h4>
                            <div class="detail-item">
                                <strong>Nombre:</strong> 
                                <span>${usuario.nombres || 'No especificado'} ${usuario.apellidos || ''}</span>
                            </div>
                            <div class="detail-item">
                                <strong>DNI:</strong> 
                                <span>${usuario.dni || 'No especificado'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Email:</strong> 
                                <span>${usuario.email || 'No especificado'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Teléfono:</strong> 
                                <span>${usuario.telefono || 'No especificado'}</span>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>Información del Sistema</h4>
                            <div class="detail-item">
                                <strong>ID Usuario:</strong> 
                                <span>${usuario.id_usuario}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Roles:</strong> 
                                <span>${usuario.roles || 'Sin roles asignados'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Estado:</strong> 
                                <span>${usuario.activo == 1 ? 'Activo' : 'Inactivo'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Fecha Registro:</strong> 
                                <span>${new Date(usuario.created_at).toLocaleString('es-ES')}</span>
                            </div>
                        </div>
                        
                        ${usuario.codigo_estudiante ? `
                        <div class="detail-section">
                            <h4>Información Académica</h4>
                            <div class="detail-item">
                                <strong>Código:</strong> 
                                <span>${usuario.codigo_estudiante}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Carrera:</strong> 
                                <span>${usuario.carrera || 'No especificada'}</span>
                            </div>
                            <div class="detail-item">
                                <strong>Semestre:</strong> 
                                <span>${usuario.semestre || 'No especificado'}</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Cerrar modal de detalle
 */
function cerrarModalDetalle() {
    const modal = document.getElementById('userDetailModal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Editar usuario con múltiples roles
 */
function editarUsuario(userId) {
    const usuario = currentUsers.find(u => u.ID_USUARIO == userId);
    if (!usuario) {
        showNotification('Usuario no encontrado', 'error');
        return;
    }
    
    // Llenar formulario
    document.getElementById('editUserId').value = usuario.ID_USUARIO;
    document.getElementById('editUserEmail').value = usuario.EMAIL;
    
    // Crear checkboxes para roles
    const rolesContainer = document.getElementById('rolesContainer');
    rolesContainer.innerHTML = '';
    
    rolesDisponibles.forEach(rol => {
        const isChecked = usuario.ROLES_ARRAY && usuario.ROLES_ARRAY.some(r => r.id == rol.id_rol);
        
        const roleDiv = document.createElement('div');
        roleDiv.className = 'role-checkbox';
        roleDiv.innerHTML = `
            <label class="checkbox-label">
                <input type="checkbox" name="roles" value="${rol.id_rol}" ${isChecked ? 'checked' : ''}>
                <span class="checkmark"></span>
                <span class="role-name">${rol.nombre}</span>
                <small class="role-description">${rol.descripcion}</small>
            </label>
        `;
        rolesContainer.appendChild(roleDiv);
    });
    
    // Mostrar modal
    document.getElementById('editUserModal').style.display = 'flex';
}

/**
 * Guardar cambios del usuario con múltiples roles
 */
async function guardarCambiosUsuario() {
    try {
        const userId = document.getElementById('editUserId').value;
        const email = document.getElementById('editUserEmail').value;
        
        // Obtener roles seleccionados
        const rolesCheckboxes = document.querySelectorAll('input[name="roles"]:checked');
        const roles = Array.from(rolesCheckboxes).map(cb => parseInt(cb.value));
        
        if (roles.length === 0) {
            showNotification('Debe seleccionar al menos un rol', 'warning');
            return;
        }
        
        const saveBtn = document.querySelector('#editUserModal .btn-save');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled = true;
        
        const response = await fetch(`${window.BASE_URL}/index.php?accion=actualizar_usuario_roles`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_usuario: parseInt(userId),
                email: email,
                roles: roles
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Usuario actualizado correctamente', 'success');
            cerrarModalEdicion();
            cargarUsuarios(currentPage); // Recargar la página actual
        } else {
            throw new Error(data.message || 'Error al actualizar usuario');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al guardar cambios: ' + error.message, 'error');
    } finally {
        const saveBtn = document.querySelector('#editUserModal .btn-save');
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
            saveBtn.disabled = false;
        }
    }
}

/**
 * Cerrar modal de edición
 */
function cerrarModalEdicion() {
    document.getElementById('editUserModal').style.display = 'none';
}

/**
 * Exportar usuarios
 */
function exportarUsuarios() {
    if (!currentUsers || currentUsers.length === 0) {
        showNotification('No hay usuarios para exportar', 'warning');
        return;
    }
    
    // Crear CSV con los datos actuales
    const headers = ['ID', 'DNI', 'Nombre', 'Apellido', 'Email', 'Rol', 'Código', 'Estado', 'Fecha Registro'];
    const csvContent = [
        headers.join(','),
        ...currentUsers.map(user => [
            user.ID_USUARIO,
            `"${user.DNI || ''}"`,
            `"${user.NOMBRE || ''}"`,
            `"${user.APELLIDO || ''}"`,
            `"${user.EMAIL || ''}"`,
            `"${user.ROL || ''}"`,
            `"${user.CODIGO_ESTUDIANTE || ''}"`,
            user.ACTIVO == 1 ? 'Activo' : 'Inactivo',
            `"${new Date(user.FECHA_REG).toLocaleDateString('es-ES')}"`
        ].join(','))
    ].join('\n');
    
    // Descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `usuarios_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showNotification('Usuarios exportados correctamente', 'success');
}
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
        // ✅ SOLUCIÓN: Mostrar la tabla cuando termina la carga
        tableContainer.style.display = 'block';
    }
}

/**
 * Mostrar estado vacío
 */
function mostrarEstadoVacio() {
    document.getElementById('usersTableContainer').style.display = 'none';
    document.getElementById('emptyState').style.display = 'flex';
}

/**
 * Generar paginación básica
 */
function generarPaginacion(total = null, pagina = 1) {
    const container = document.getElementById('paginationContainer');
    
    if (currentUsers.length < usersPerPage && pagina === 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = '<div class="pagination">';
    
    // Botón anterior
    if (currentPage > 1) {
        paginationHTML += `<button onclick="cargarUsuarios(${currentPage - 1})" class="page-btn">Anterior</button>`;
    }
    
    // Página actual
    paginationHTML += `<span class="page-info">Página ${currentPage}</span>`;
    
    // Botón siguiente
    if (currentUsers.length === usersPerPage) {
        paginationHTML += `<button onclick="cargarUsuarios(${currentPage + 1})" class="page-btn">Siguiente</button>`;
    }
    
    paginationHTML += '</div>';
    container.innerHTML = paginationHTML;
}

/**
 * Cerrar modal de usuarios
 */
function cerrarModalUsuarios(event) {
    if (event && event.target !== event.currentTarget) {
        return; // Solo cerrar si se hace clic fuera del contenido
    }
    
    const modal = document.getElementById('modalUsuarios');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');
    
    // Animar salida
    modalContent.style.transform = 'scale(0.8)';
    modalContent.style.opacity = '0';
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

/**
 * Función para guardar cambios del usuario (funciona con la función existente)
 */
function saveUserChanges() {
    // Esta función redirige a la nueva función guardarCambiosUsuario
    guardarCambiosUsuario();
}

/**
 * Agregar estilos CSS al documento
 */
function agregarEstilosModal() {
    const estilos = `
        <style id="modalUserStyles">
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 98vw;
            max-height: 95vh;
            width: 1400px; /* Más ancho */
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .modal-small {
            width: 500px;
        }

        .modal-large {
            width: 1400px; /* Modal extra grande */
        }

        .modal-medium {
            width: 600px;
        }

        .modal-header {
            background: #1e3a5f;
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2, .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
            max-height: calc(90vh - 120px);
            overflow-y: auto;
        }

        .search-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .search-container {
            flex: 1;
            min-width: 300px;
        }

        /* Búsqueda simplificada */
        .search-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }

        .search-select {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            min-width: 150px;
        }

        .search-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            white-space: nowrap;
        }

        .search-btn:hover {
            background: #0056b3;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-refresh, .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            white-space: nowrap;
        }

        .btn-refresh:hover {
            background: #1e7e34;
        }

        .btn-export {
            background: #6c757d;
        }

        .btn-export:hover {
            background: #545b62;
        }

        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 5px;
        }
        
        .btn-clear:hover {
            background: #5a6268;
        }

        .search-results-message {
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-clear-search {
            background: #0c5460;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
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
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        /* Tabla más grande y mejor organizada */
        .table-container-large {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            max-height: 60vh; /* Altura máxima para scroll vertical */
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .users-table-large {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 0.9rem; /* Texto un poco más pequeño para que quepa más */
        }

        .users-table th,
        .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .users-table-large th,
        .users-table-large td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .users-table-large th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .users-table tbody tr:hover {
            background: #f8f9fa;
        }

        .users-table-large tbody tr:hover {
            background: #f8f9fa;
        }

        .user-name-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .oauth-badge {
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        /* Estilos para múltiples roles */
        .roles-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .role-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .role-usuario { background: #e3f2fd; color: #1976d2; }
        .role-estudiante { background: #e8f5e8; color: #388e3c; }
        .role-docente { background: #fff3e0; color: #f57c00; }
        .role-administrador { background: #fce4ec; color: #c2185b; }
        .role-sin-rol { background: #f5f5f5; color: #757575; }
        
        /* Estilos para checkboxes de roles */
        .roles-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
        }
        
        .role-checkbox {
            margin-bottom: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .checkbox-label:hover {
            background: #f8f9fa;
        }
        
        .checkbox-label input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .role-name {
            font-weight: 500;
            color: #333;
        }
        
        .role-description {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
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
            gap: 5px;
        }

        .btn-action {
            background: none;
            border: 1px solid;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-view {
            color: #007bff;
            border-color: #007bff;
        }

        .btn-view:hover {
            background: #007bff;
            color: white;
        }

        .btn-edit {
            color: #28a745;
            border-color: #28a745;
        }

        .btn-edit:hover {
            background: #28a745;
            color: white;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: #6c757d;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #6c757d;
        }

        .empty-state p {
            color: #adb5bd;
            margin-bottom: 0;
        }

        .pagination-container {
            margin-top: 25px;
            text-align: center;
        }

        .pagination {
            display: inline-flex;
            gap: 15px;
            align-items: center;
        }

        .page-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .page-btn:hover {
            background: #0056b3;
        }

        .page-info {
            color: #6c757d;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-cancel, .btn-save {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        .btn-save {
            background: #007bff;
            color: white;
        }

        .btn-save:hover {
            background: #0056b3;
        }

        .btn-save:disabled {
            background: #adb5bd;
            cursor: not-allowed;
        }

        .user-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .detail-section h4 {
            margin: 0 0 15px 0;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }

        .detail-item {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-item strong {
            color: #495057;
            min-width: 120px;
        }

        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .notification-success {
            border-left: 4px solid #28a745;
        }

        .notification-error {
            border-left: 4px solid #dc3545;
        }

        .notification-warning {
            border-left: 4px solid #ffc107;
        }

        .notification-info {
            border-left: 4px solid #17a2b8;
        }

        .notification-success i { color: #28a745; }
        .notification-error i { color: #dc3545; }
        .notification-warning i { color: #ffc107; }
        .notification-info i { color: #17a2b8; }

        .notification-close {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            margin-left: auto;
        }

        .notification-close:hover {
            background: #f8f9fa;
        }

        /* Responsive para pantallas más pequeñas */
        @media (max-width: 1500px) {
            .modal-content {
                width: 95vw;
            }
            
            .modal-large {
                width: 95vw;
            }
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95vw;
                height: 95vh;
                margin: 10px;
            }
            
            .search-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group {
                flex-wrap: wrap;
            }
            
            .search-input {
                min-width: 100%;
                margin-bottom: 10px;
            }
            
            .users-table {
                font-size: 0.9rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 8px 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .user-detail-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', estilos);
}
</script>
<?php require_once BASE_PATH . '/views/components/footer.php'; ?>