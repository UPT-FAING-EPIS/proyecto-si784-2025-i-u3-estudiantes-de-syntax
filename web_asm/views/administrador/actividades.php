<?php
require_once BASE_PATH . '/views/components/head.php';
require_once BASE_PATH . '/views/components/header.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/models/AdminModel.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 4) {
    header('Location: ' . BASE_URL . '/index.php?accion=login');
    exit;
}

$adminModel = new AdminModel();

// Obtener parámetros de filtro y paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Obtener todas las actividades
$resultado = $adminModel->obtenerTodasActividades($pagina, 20, $filtro_tipo, $filtro_fecha);
$actividades = $resultado['actividades'];
$total_actividades = $resultado['total'];
$pagina_actual = $resultado['pagina_actual'];
$total_paginas = $resultado['total_paginas'];

// Filtrar por búsqueda si se especifica
if ($busqueda) {
    $actividades = array_filter($actividades, function($actividad) use ($busqueda) {
        return stripos($actividad['titulo'], $busqueda) !== false ||
               stripos($actividad['descripcion'], $busqueda) !== false ||
               stripos($actividad['usuario'], $busqueda) !== false;
    });
}

// Función helper para generar URLs de filtro
function generarUrlFiltro($tipo = null, $fecha = null, $pagina = 1) {
    $params = [];
    if ($tipo) $params['tipo'] = $tipo;
    if ($fecha) $params['fecha'] = $fecha;
    if ($pagina > 1) $params['pagina'] = $pagina;
    if (isset($_GET['buscar']) && $_GET['buscar']) $params['buscar'] = $_GET['buscar'];
    
    return BASE_URL . '/index.php?accion=actividades' . ($params ? '&' . http_build_query($params) : '');
}
?>

<div class="admin-container">
    <div class="admin-content">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="btn-back" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="header-text">
                        <h1>Todas las Actividades</h1>
                        <p>Historial completo de actividades del sistema</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button class="btn btn-secondary" onclick="exportarActividades()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="filters-section">
            <div class="filters-container">
                <div class="search-box">
                    <form method="GET" action="<?php echo BASE_URL; ?>/index.php">
                        <input type="hidden" name="accion" value="actividades">
                        <?php if ($filtro_tipo): ?><input type="hidden" name="tipo" value="<?php echo htmlspecialchars($filtro_tipo); ?>"><?php endif; ?>
                        <?php if ($filtro_fecha): ?><input type="hidden" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>"><?php endif; ?>
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" name="buscar" placeholder="Buscar actividades..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            <button type="submit" class="btn-search">Buscar</button>
                        </div>
                    </form>
                </div>
                
                <div class="filter-tabs">
                    <a href="<?php echo generarUrlFiltro(); ?>" class="filter-tab <?php echo !$filtro_tipo ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> Todas
                    </a>
                    <a href="<?php echo generarUrlFiltro('usuarios'); ?>" class="filter-tab <?php echo $filtro_tipo === 'usuarios' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i> Usuarios
                    </a>
                    <a href="<?php echo generarUrlFiltro('inscripciones'); ?>" class="filter-tab <?php echo $filtro_tipo === 'inscripciones' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i> Inscripciones
                    </a>
                    <a href="<?php echo generarUrlFiltro('clases'); ?>" class="filter-tab <?php echo $filtro_tipo === 'clases' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i> Clases
                    </a>
                    <a href="<?php echo generarUrlFiltro('calificaciones'); ?>" class="filter-tab <?php echo $filtro_tipo === 'calificaciones' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Calificaciones
                    </a>
                    <a href="<?php echo generarUrlFiltro('roles'); ?>" class="filter-tab <?php echo $filtro_tipo === 'roles' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tag"></i> Roles
                    </a>
                    <a href="<?php echo generarUrlFiltro('asistencias'); ?>" class="filter-tab <?php echo $filtro_tipo === 'asistencias' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Asistencias
                    </a>
                </div>
                
                <div class="date-filters">
                    <a href="<?php echo generarUrlFiltro($filtro_tipo, 'hoy'); ?>" class="date-filter <?php echo $filtro_fecha === 'hoy' ? 'active' : ''; ?>">
                        Hoy
                    </a>
                    <a href="<?php echo generarUrlFiltro($filtro_tipo, 'semana'); ?>" class="date-filter <?php echo $filtro_fecha === 'semana' ? 'active' : ''; ?>">
                        Esta semana
                    </a>
                    <a href="<?php echo generarUrlFiltro($filtro_tipo, 'mes'); ?>" class="date-filter <?php echo $filtro_fecha === 'mes' ? 'active' : ''; ?>">
                        Este mes
                    </a>
                    <a href="<?php echo generarUrlFiltro($filtro_tipo); ?>" class="date-filter <?php echo !$filtro_fecha ? 'active' : ''; ?>">
                        Todo el tiempo
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="stats-summary">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_actividades); ?></h3>
                    <p>Total de actividades</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count(array_filter($actividades, function($a) { return date('Y-m-d', strtotime($a['fecha'])) === date('Y-m-d'); })); ?></h3>
                    <p>Actividades hoy</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($actividades); ?></h3>
                    <p>Mostrando</p>
                </div>
            </div>
        </div>

        <!-- Lista de actividades -->
        <div class="activities-section">
            <?php if (empty($actividades)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No se encontraron actividades</h3>
                    <p>No hay actividades que coincidan con los filtros seleccionados.</p>
                    <button class="btn btn-primary" onclick="window.location.href='<?php echo generarUrlFiltro(); ?>'">
                        <i class="fas fa-refresh"></i> Ver todas las actividades
                    </button>
                </div>
            <?php else: ?>
                <div class="activities-timeline">
                    <?php foreach ($actividades as $actividad): ?>
                        <div class="activity-item" data-categoria="<?php echo htmlspecialchars($actividad['categoria'] ?? ''); ?>">
                            <div class="activity-dot <?php echo $actividad['tipo']; ?>"></div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <div class="activity-title-section">
                                        <h4 class="activity-title"><?php echo htmlspecialchars($actividad['titulo']); ?></h4>
                                        <span class="activity-badge badge-<?php echo $actividad['tipo']; ?>">
                                            <?php echo htmlspecialchars($actividad['badge']); ?>
                                        </span>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $adminModel->formatearTiempoTranscurrido($actividad['fecha']); ?>
                                        </span>
                                        <span class="activity-date">
                                            <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="activity-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                
                                <div class="activity-footer">
                                    <div class="activity-user">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($actividad['usuario']); ?></span>
                                    </div>
                                    
                                    <?php if (isset($actividad['detalles']) && !empty($actividad['detalles'])): ?>
                                        <button class="btn-details" onclick="toggleDetails(this)">
                                            <i class="fas fa-chevron-down"></i> Ver detalles
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($actividad['detalles']) && !empty($actividad['detalles'])): ?>
                                    <div class="activity-details" style="display: none;">
                                        <div class="details-grid">
                                            <?php foreach ($actividad['detalles'] as $clave => $valor): ?>
                                                <div class="detail-item">
                                                    <strong><?php echo htmlspecialchars($clave); ?>:</strong>
                                                    <span><?php echo htmlspecialchars($valor); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination-section">
                <div class="pagination">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="<?php echo generarUrlFiltro($filtro_tipo, $filtro_fecha, $pagina_actual - 1); ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    
                    if ($inicio > 1): ?>
                        <a href="<?php echo generarUrlFiltro($filtro_tipo, $filtro_fecha, 1); ?>" class="page-number">1</a>
                        <?php if ($inicio > 2): ?><span class="page-dots">...</span><?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <a href="<?php echo generarUrlFiltro($filtro_tipo, $filtro_fecha, $i); ?>" 
                           class="page-number <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?><span class="page-dots">...</span><?php endif; ?>
                        <a href="<?php echo generarUrlFiltro($filtro_tipo, $filtro_fecha, $total_paginas); ?>" class="page-number"><?php echo $total_paginas; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="<?php echo generarUrlFiltro($filtro_tipo, $filtro_fecha, $pagina_actual + 1); ?>" class="page-btn">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    <p>Mostrando <?php echo count($actividades); ?> de <?php echo number_format($total_actividades); ?> actividades</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-container {
    min-height: 100vh;
    background: linear-gradient(135deg,rgb(245, 245, 245) 0%,rgb(244, 244, 245) 100%);
    padding: 20px;
}

.admin-content {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    background: #1e3a5f;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.btn-back {
    background: #1e3a5f;
    border: none;
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #1e3a5f;
    transform: translateX(-2px);
}

.header-text h1 {
    margin: 0;
    color: #2c3e50;
    font-size: 2rem;
    font-weight: 700;
}

.header-text p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 1.1rem;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.filters-section {
    background: #1e3a5f;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.search-box {
    margin-bottom: 20px;
}

.search-input {
    display: flex;
    align-items: center;
    background: #1e3a5f;
    border-radius: 10px;
    padding: 0 15px;
    gap: 10px;
}

.search-input input {
    flex: 1;
    border: none;
    background: none;
    padding: 15px 0;
    font-size: 1rem;
    outline: none;
}

.btn-search {
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-search:hover {
    background: #0056b3;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.filter-tab:hover {
    background: #e9ecef;
    color: #495057;
}

.filter-tab.active {
    background: #007bff;
    color: white;
}

.date-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.date-filter {
    padding: 8px 16px;
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.date-filter:hover {
    background: #e9ecef;
    color: #495057;
}

.date-filter.active {
    background: #28a745;
    color: white;
}

.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.success { background: #28a745; }
.stat-icon.info { background: #17a2b8; }
.stat-icon.warning { background: #ffc107; }

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 1rem;
}

.activities-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #6c757d;
    margin-bottom: 10px;
}

.empty-state p {
    color: #adb5bd;
    margin-bottom: 30px;
}

.activities-timeline {
    position: relative;
}

.activities-timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.activity-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 80px;
}

.activity-dot {
    position: absolute;
    left: 22px;
    top: 8px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.activity-dot.success { background: #28a745; }
.activity-dot.info { background: #17a2b8; }
.activity-dot.warning { background: #ffc107; }
.activity-dot.primary { background: #007bff; }
.activity-dot.danger { background: #dc3545; }
.activity-dot.secondary { background: #6c757d; }

.activity-content {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
}

.activity-content:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.activity-title-section {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.activity-title {
    margin: 0;
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 600;
}

.activity-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.badge-success { background: #28a745; }
.badge-info { background: #17a2b8; }
.badge-warning { background: #ffc107; color: #212529; }
.badge-primary { background: #007bff; }
.badge-danger { background: #dc3545; }
.badge-secondary { background: #6c757d; }

.activity-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    font-size: 0.9rem;
    color: #6c757d;
}

.activity-description {
    margin: 10px 0;
    color: #495057;
    line-height: 1.5;
}

.activity-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.activity-user {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 0.9rem;
}

.btn-details {
    background: none;
    border: 1px solid #dee2e6;
    color: #6c757d;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.btn-details:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.activity-details {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.detail-item strong {
    color: #495057;
    font-size: 0.85rem;
}

.detail-item span {
    color: #6c757d;
    font-size: 0.9rem;
}

.pagination-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    text-align: center;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.page-btn, .page-number {
    padding: 10px 15px;
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.page-btn:hover, .page-number:hover {
    background: #e9ecef;
    color: #495057;
}

.page-number.active {
    background: #007bff;
    color: white;
}

.page-dots {
    color: #6c757d;
    padding: 0 5px;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.9rem;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .admin-container {
        padding: 10px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .filter-tabs, .date-filters {
        justify-content: center;
    }
    
    .activity-item {
        padding-left: 60px;
    }
    
    .activities-timeline::before {
        left: 20px;
    }
    
    .activity-dot {
        left: 12px;
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .activity-meta {
        align-items: flex-start;
    }
    
    .pagination {
        justify-content: center;
    }
}
</style>

<script>
function toggleDetails(button) {
    const details = button.closest('.activity-content').querySelector('.activity-details');
    const icon = button.querySelector('i');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Ocultar detalles';
    } else {
        details.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Ver detalles';
    }
}

function exportarActividades() {
    // Implementar exportación de actividades
    alert('Función de exportación en desarrollo');
}

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.activity-item');
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
</script>

<?php require_once BASE_PATH . '/views/components/footer.php'; ?>