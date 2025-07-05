<?php
function registrarLogTelemetria($accion, $descripcion = '', $modulo = '') {
    $usuario_id = $_SESSION['id_usuario'] ?? null;
    $rol = $_SESSION['rol'] ?? 'anónimo';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';

    require_once BASE_PATH . '/config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("INSERT INTO logs_telemetria 
        (id_usuario, rol_usuario, accion, descripcion, modulo, ip_usuario, navegador)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([$usuario_id, $rol, $accion, $descripcion, $modulo, $ip, $navegador]);
}
