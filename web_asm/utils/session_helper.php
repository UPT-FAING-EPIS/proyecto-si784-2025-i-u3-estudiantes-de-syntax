<?php
/**
 * Session Helper Functions
 * Provides common session management utilities
 */

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Check if user has specific role
 * @param int $roleId Role ID to check
 * @return bool
 */
function hasRole($roleId) {
    return isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == $roleId;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return hasRole(4);
}

/**
 * Check if user is teacher
 * @return bool
 */
function isTeacher() {
    return hasRole(3);
}

/**
 * Check if user is student
 * @return bool
 */
function isStudent() {
    return hasRole(2);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Get current user role ID
 * @return int|null
 */
function getCurrentRoleId() {
    return $_SESSION['rol_id'] ?? null;
}

/**
 * Get current user role name
 * @return string|null
 */
function getCurrentRoleName() {
    return $_SESSION['rol_nombre'] ?? null;
}

/**
 * Redirect if not logged in
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = '/index.php?accion=login') {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Redirect if user doesn't have required role
 * @param int $requiredRole Required role ID
 * @param string $redirectUrl URL to redirect to if access denied
 */
function requireRole($requiredRole, $redirectUrl = '/index.php') {
    if (!hasRole($requiredRole)) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Set flash message
 * @param string $message Message text
 * @param string $type Message type (success, error, info, warning)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['mensaje'] = $message;
    $_SESSION['tipo_mensaje'] = $type;
}

/**
 * Get and clear flash message
 * @return array|null Array with 'message' and 'type' keys, or null if no message
 */
function getFlashMessage() {
    if (isset($_SESSION['mensaje'])) {
        $message = [
            'message' => $_SESSION['mensaje'],
            'type' => $_SESSION['tipo_mensaje'] ?? 'info'
        ];
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        return $message;
    }
    return null;
}

/**
 * Clear all session data and destroy session
 */
function destroySession() {
    session_unset();
    session_destroy();
}

/**
 * Check session timeout
 * @param int $timeoutMinutes Timeout in minutes (default: 30)
 * @return bool True if session is still valid
 */
function checkSessionTimeout($timeoutMinutes = 30) {
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactividad = time() - $_SESSION['ultimo_acceso'];
        if ($inactividad > ($timeoutMinutes * 60)) {
            destroySession();
            return false;
        }
    }
    $_SESSION['ultimo_acceso'] = time();
    return true;
}
?>