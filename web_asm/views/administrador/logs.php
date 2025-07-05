<?php
require_once BASE_PATH . '/config/Database.php';
require_once BASE_PATH . '/utils/session_helper.php';

session_start();
if ($_SESSION['rol'] !== 'admin') {
    echo "<div style='color:red; font-weight:bold;'>❌ Acceso restringido: solo administradores.</div>";
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$where = [];
$params = [];

// Filtros
if (!empty($_GET['modulo'])) {
    $where[] = "l.modulo LIKE ?";
    $params[] = '%' . $_GET['modulo'] . '%';
}
if (!empty($_GET['accion_log'])) {
    $where[] = "l.accion LIKE ?";
    $params[] = '%' . $_GET['accion_log'] . '%';
}
if (!empty($_GET['desde'])) {
    $where[] = "DATE(l.fecha) >= ?";
    $params[] = $_GET['desde'];
}
if (!empty($_GET['hasta'])) {
    $where[] = "DATE(l.fecha) <= ?";
    $params[] = $_GET['hasta'];
}

$whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

$query = "SELECT l.*, u.nombre AS nombre_usuario 
          FROM logs_telemetria l 
          LEFT JOIN usuarios u ON u.id_usuario = l.id_usuario 
          $whereClause
          ORDER BY l.fecha DESC 
          LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Procesar datos para gráficas ===
$accionesPorModulo = [];
$accionesPorDia = [];

foreach ($logs as $log) {
    $modulo = $log['modulo'];
    $accionesPorModulo[$modulo] = ($accionesPorModulo[$modulo] ?? 0) + 1;

    $fecha = date('Y-m-d', strtotime($log['fecha']));
    $accionesPorDia[$fecha] = ($accionesPorDia[$fecha] ?? 0) + 1;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Telemetría - Registros del Sistema</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-4">
    <h2>📊 Registros de Telemetría</h2>

    <!-- Filtros -->
    <form method="GET" class="row g-2 mb-4">
        <input type="hidden" name="accion" value="logs">
        <div class="col-md-3">
            <input type="text" name="modulo" value="<?= htmlspecialchars($_GET['modulo'] ?? '') ?>" class="form-control" placeholder="🔎 Filtrar por módulo">
        </div>
        <div class="col-md-3">
            <input type="text" name="accion_log" value="<?= htmlspecialchars($_GET['accion_log'] ?? '') ?>" class="form-control" placeholder="🔎 Filtrar por acción">
        </div>
        <div class="col-md-2">
            <input type="date" name="desde" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <input type="date" name="hasta" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h5>📊 Acciones por Módulo</h5>
            <canvas id="graficoModulo"></canvas>
        </div>
        <div class="col-md-6">
            <h5>📈 Acciones por Día</h5>
            <canvas id="graficoDia"></canvas>
        </div>
    </div>

    <!-- Tabla -->
    <table class="table table-striped table-bordered table-hover table-sm">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Acción</th>
                <th>Módulo</th>
                <th>Descripción</th>
                <th>IP</th>
                <th>Navegador</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['fecha']) ?></td>
                    <td><?= htmlspecialchars($log['nombre_usuario'] ?? 'Anónimo') ?></td>
                    <td><?= htmlspecialchars($log['rol_usuario']) ?></td>
                    <td><?= htmlspecialchars($log['accion']) ?></td>
                    <td><?= htmlspecialchars($log['modulo']) ?></td>
                    <td><?= htmlspecialchars($log['descripcion']) ?></td>
                    <td><?= htmlspecialchars($log['ip_usuario']) ?></td>
                    <td><?= substr(htmlspecialchars($log['navegador']), 0, 30) ?>...</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Scripts para gráficos -->
    <script>
    const datosModulo = {
        labels: <?= json_encode(array_keys($accionesPorModulo)) ?>,
        datasets: [{
            label: 'Acciones por módulo',
            data: <?= json_encode(array_values($accionesPorModulo)) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };

    const datosDia = {
        labels: <?= json_encode(array_keys($accionesPorDia)) ?>,
        datasets: [{
            label: 'Acciones por día',
            data: <?= json_encode(array_values($accionesPorDia)) ?>,
            fill: false,
            borderColor: 'rgba(255, 99, 132, 1)',
            tension: 0.1
        }]
    };

    new Chart(document.getElementById('graficoModulo'), {
        type: 'bar',
        data: datosModulo,
        options: { responsive: true }
    });

    new Chart(document.getElementById('graficoDia'), {
        type: 'line',
        data: datosDia,
        options: { responsive: true }
    });
    </script>
</body>
</html>
