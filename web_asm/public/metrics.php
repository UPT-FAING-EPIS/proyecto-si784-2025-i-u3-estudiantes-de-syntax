<?php
require_once __DIR__ . '/../config/Database.php';

// Encabezado Prometheus
header('Content-Type: text/plain');

$db = new Database();
$conn = $db->getConnection();

// === MÉTRICA 1: Total de logs por módulo ===
$queryModulo = "SELECT modulo, COUNT(*) AS total FROM logs_telemetria GROUP BY modulo";
$stmt = $conn->prepare($queryModulo);
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $modulo = preg_replace('/[^a-zA-Z0-9_]/', '_', $row['modulo']); // sanitiza
    printf("ams_log_total{modulo=\"%s\"} %d\n", $modulo, $row['total']);
}

// === MÉTRICA 2: Total de logs por acción ===
$queryAccion = "SELECT accion, COUNT(*) AS total FROM logs_telemetria GROUP BY accion";
$stmt = $conn->prepare($queryAccion);
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $accion = preg_replace('/[^a-zA-Z0-9_]/', '_', $row['accion']); // sanitiza
    printf("ams_log_total{accion=\"%s\"} %d\n", $accion, $row['total']);
}

// === MÉTRICA 3: Total de logs por rol de usuario ===
$queryRol = "SELECT rol_usuario, COUNT(*) AS total FROM logs_telemetria GROUP BY rol_usuario";
$stmt = $conn->prepare($queryRol);
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $rol = preg_replace('/[^a-zA-Z0-9_]/', '_', $row['rol_usuario']); // sanitiza
    printf("ams_log_total{rol=\"%s\"} %d\n", $rol, $row['total']);
}

// === MÉTRICA 4: Total de logs ===
$queryTotal = "SELECT COUNT(*) AS total FROM logs_telemetria";
$stmt = $conn->prepare($queryTotal);
$stmt->execute();
$total = $stmt->fetchColumn();
echo "ams_logs_total $total\n";
