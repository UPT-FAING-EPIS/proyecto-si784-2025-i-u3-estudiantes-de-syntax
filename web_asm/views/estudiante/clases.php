<?php
require_once BASE_PATH . '/views/components/head.php';
require_once BASE_PATH . '/views/components/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 2) {
    header('Location: ' . BASE_URL . '/index.php?accion=login');
    exit;
}

require_once BASE_PATH . '/models/EstudianteModel.php';
$estudianteModel = new EstudianteModel();

$estudiante = $estudianteModel->obtenerEstudiantePorUsuario($_SESSION['usuario_id']);
$cursos = [];

if ($estudiante) {
    $cursos = $estudianteModel->obtenerCursosEstudiante($estudiante['id_estudiante']);
}

// Manejo de solicitud de mentoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'solicitar_mentoria') {
    $id_curso = $_POST['id_curso'] ?? 0;
    
    try {
        $resultado = $estudianteModel->crearOInscribirClase($id_curso, $estudiante['id_estudiante']);
        
        if ($resultado['success']) {
            $mensaje = "Solicitud de mentoría enviada correctamente";
            $tipo_mensaje = "success";
            // Recargar datos
            $cursos = $estudianteModel->obtenerCursosEstudiante($estudiante['id_estudiante']);
        } else {
            throw new Exception($resultado['mensaje']);
        }
        
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Manejo de actualización de datos académicos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_datos') {
    $codigo_estudiante = $_POST['codigo_estudiante'] ?? '';
    $password_estudiante = $_POST['password_estudiante'] ?? '';
    
    // Validar que solo contengan números
    if (!preg_match('/^[0-9]{10}$/', $codigo_estudiante)) {
        $mensaje = "El código de estudiante debe contener exactamente 10 dígitos numéricos";
        $tipo_mensaje = "danger";
    } elseif (!preg_match('/^[0-9]{6}$/', $password_estudiante)) {
        $mensaje = "La contraseña debe contener exactamente 6 dígitos numéricos";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Aquí iría la lógica para actualizar los datos académicos
            // Por ejemplo: conectar con el sistema académico de la UPT
            // $resultado = $estudianteModel->actualizarDatosAcademicos($codigo_estudiante, $password_estudiante, $_SESSION['usuario_id']);
            
            // Por ahora simulamos una respuesta exitosa
            $mensaje = "Datos académicos actualizados correctamente. Los cambios se reflejarán en breve.";
            $tipo_mensaje = "success";
            
            // Recargar datos del estudiante y cursos
            $estudiante = $estudianteModel->obtenerEstudiantePorUsuario($_SESSION['usuario_id']);
            if ($estudiante) {
                $cursos = $estudianteModel->obtenerCursosEstudiante($estudiante['id_estudiante']);
            }
            
        } catch (Exception $e) {
            $mensaje = "Error al actualizar los datos: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
    
    // Redireccionar para evitar reenvío de formulario
    $_SESSION['mensaje_temp'] = $mensaje;
    $_SESSION['tipo_mensaje_temp'] = $tipo_mensaje;
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Recuperar mensajes temporales de sesión
if (isset($_SESSION['mensaje_temp'])) {
    $mensaje = $_SESSION['mensaje_temp'];
    $tipo_mensaje = $_SESSION['tipo_mensaje_temp'];
    unset($_SESSION['mensaje_temp'], $_SESSION['tipo_mensaje_temp']);
}

// Función para determinar el valor de necesita_mentoria basado en la nota
function calcularNecesitaMentoria($ponderado) {
    if ($ponderado >= 0 && $ponderado <= 5) {
        return 0; // Necesita mentoría con extrema urgencia
    } elseif ($ponderado >= 6 && $ponderado <= 10) {
        return 1; // Necesita mentoría urgente
    } elseif ($ponderado >= 11 && $ponderado <= 15) {
        return 2; // Puede requerir mentoría
    } elseif ($ponderado >= 16 && $ponderado <= 20) {
        return 3; // Excelente rendimiento
    }
    return 1; // Por defecto
}
?>
<style>
:root {
    /* Colores basados en page-header */
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --secondary-blue: #2c5282;
    --primary-blue: #1e3a5f;
    
    /* Colores de éxito/verde */
    --success: #10b981;
    --success-dark: #059669;
    --accent-green: #28a745;
    --light-green: #20c997;
    
    /* Colores informativos y de estado */
    --info: #06b6d4;
    --info-dark: #0891b2;
    --warning: #1e3a5f; /* Usando el color del page-header */
    --warning-dark: #1e40af;
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
    --gradient-header: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
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

.cursos-container {
    padding: 2rem 0;
    min-height: calc(100vh - 120px);
}

.page-header {
    background: var(--gradient-header);
    color: white;
    padding: 2.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
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

.page-header .content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.header-text {
    flex: 1;
    min-width: 300px;
}

.page-header h1 {
    font-size: 2.25rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header p {
    opacity: 0.9;
    margin: 0;
    font-size: 1.125rem;
    font-weight: 400;
}

/* Botón de actualizar datos en el header */
.header-update-btn {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius);
    padding: 0.875rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-sm);
}

.header-update-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.student-info {
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    border: 1px solid var(--border);
}

.student-info h3 {
    color: var(--primary);
    margin-bottom: 1.5rem;
    font-weight: 700;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: var(--surface);
    border-radius: 0.5rem;
    border-left: 4px solid var(--primary);
    transition: var(--transition);
}

.info-item:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.info-item i {
    color: var(--primary);
    font-size: 1.25rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.info-item .label {
    font-weight: 600;
    color: var(--gray-600);
    margin-right: 0.5rem;
}

.info-item .value {
    color: var(--gray-900);
    font-weight: 600;
}

.section {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    overflow: hidden;
    border: 1px solid var(--border);
}

.section-header {
    padding: 1.5rem 2rem;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    color: var(--primary);
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.75rem;
    font-size: 1.5rem;
}

.section-content {
    padding: 2rem;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.course-card {
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    background: var(--white);
    box-shadow: var(--shadow-sm);
}

.course-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

/* Estilos para las 4 fases de mentoría basadas en notas */
.course-card.mentoria-extrema {
    border-color: var(--danger);
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.03) 100%);
}

.course-card.mentoria-urgente {
    border-color: #ff6b35;
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.08) 0%, rgba(255, 107, 53, 0.03) 100%);
}

.course-card.mentoria-puede-requerir {
    border-color: var(--primary-blue);
    background: linear-gradient(135deg, rgba(30, 58, 95, 0.08) 0%, rgba(30, 58, 95, 0.03) 100%);
}

.course-card.mentoria-excelente {
    border-color: var(--success);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.03) 100%);
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.course-code {
    font-weight: 800;
    color: var(--primary);
    font-size: 1.125rem;
    line-height: 1.2;
}

/* Notas ocultas y seguras */
.course-grade {
    font-size: 1.5rem;
    font-weight: 900;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    min-width: 70px;
    text-align: center;
    box-shadow: var(--shadow-md);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    user-select: none;
}

.course-grade:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-lg);
}

/* Estado de nota oculta */
.course-grade.hidden {
    background: linear-gradient(135deg, var(--gray-400) 0%, var(--gray-500) 100%) !important;
    color: white;
}

.course-grade.hidden::before {
    content: '•••';
    font-size: 1.2rem;
    letter-spacing: 0.2rem;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.course-grade.hidden .grade-number {
    opacity: 0;
    visibility: hidden;
}

/* Animación de revelado */
.course-grade.revealing {
    animation: revealGrade 0.6s ease-in-out;
}

@keyframes revealGrade {
    0% { transform: scale(1) rotateY(0deg); }
    50% { transform: scale(1.1) rotateY(90deg); }
    100% { transform: scale(1) rotateY(0deg); }
}

/* Indicador de clic */
.course-grade::after {
    content: '👁️';
    position: absolute;
    bottom: -5px;
    right: -5px;
    font-size: 0.7rem;
    opacity: 0.7;
    transition: var(--transition);
}

.course-grade:hover::after {
    opacity: 1;
    transform: scale(1.2);
}

.course-grade.hidden::after {
    content: '🔒';
}

.grade-excellent {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
}

.grade-good {
    background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
}

.grade-warning {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
}

.grade-danger {
    background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
}

.course-name {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 1rem;
    line-height: 1.4;
    font-size: 1rem;
}

.course-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.course-credits {
    background: var(--gray-100);
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    color: var(--gray-600);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.course-status {
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-matriculado {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-dark);
}

.mentoring-alert {
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* Alertas para las 4 fases de mentoría */
.mentoring-extrema {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger-dark);
    border: 2px solid rgba(239, 68, 68, 0.3);
    animation: pulseUrgent 1.5s infinite;
}

.mentoring-urgente {
    background: rgba(255, 107, 53, 0.15);
    color: #d63031;
    border: 2px solid rgba(255, 107, 53, 0.3);
    animation: pulse 2s infinite;
}

.mentoring-puede-requerir {
    background: rgba(30, 58, 95, 0.15);
    color: var(--primary-blue);
    border: 2px solid rgba(30, 58, 95, 0.3);
}

.mentoring-excelente {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success-dark);
    border: 2px solid rgba(16, 185, 129, 0.3);
}

@keyframes pulseUrgent {
    0%, 100% { transform: scale(1); background: rgba(239, 68, 68, 0.15); }
    50% { transform: scale(1.03); background: rgba(239, 68, 68, 0.25); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

/* Botones para las 4 fases */
.btn-mentoria-extrema {
    background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 700;
    width: 100%;
    transition: var(--transition);
    cursor: pointer;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
}

.btn-mentoria-extrema:hover {
    background: linear-gradient(135deg, var(--danger-dark) 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-mentoria-urgente {
    background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 700;
    width: 100%;
    transition: var(--transition);
    cursor: pointer;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
}

.btn-mentoria-urgente:hover {
    background: linear-gradient(135deg, #e55a2b 0%, #d44a1c 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-mentoria-puede-requerir {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 700;
    width: 100%;
    transition: var(--transition);
    cursor: pointer;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
}

.btn-mentoria-puede-requerir:hover {
    background: linear-gradient(135deg, var(--secondary-blue) 0%, #1e40af 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-mentoria-excelente {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 700;
    width: 100%;
    cursor: default;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: var(--shadow-sm);
}

/* Modal deslizable mejorado */
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
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transform: scale(0.7) translateY(50px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.modal.show .modal-content {
    transform: scale(1) translateY(0);
}

/* Indicador de deslizable */
.modal-content::before {
    content: '';
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 4px;
    background: var(--gray-300);
    border-radius: 2px;
    z-index: 10;
    transition: var(--transition);
}

.modal-content:hover::before {
    background: var(--primary);
    width: 50px;
}

.modal-header {
    padding: 2rem 2rem 1.5rem 2rem;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: grab;
    user-select: none;
    position: relative;
}

.modal-header:active {
    cursor: grabbing;
}

.modal-header::after {
    content: '⋮⋮';
    position: absolute;
    right: 3rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    font-size: 1.2rem;
    letter-spacing: 2px;
    transition: var(--transition);
}

.modal-header:hover::after {
    color: var(--primary);
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-500);
    transition: var(--transition);
    padding: 0.5rem;
    border-radius: 50%;
    z-index: 10;
    position: relative;
}

.modal-close:hover {
    color: var(--danger);
    background: rgba(239, 68, 68, 0.1);
}

.modal-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

/* Contenido del modal de notas mejorado */
.grade-display {
    text-align: center;
    margin-bottom: 2rem;
}

.course-title {
    color: var(--primary);
    margin-bottom: 1.5rem;
    font-size: 1.4rem;
    font-weight: 700;
}

.grade-circle {
    display: inline-block;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    position: relative;
    box-shadow: var(--shadow-md);
}

.grade-number {
    font-size: 2.5rem;
    font-weight: 900;
    margin: 0;
}

.grade-label {
    color: var(--gray-600);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.grade-status {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 1rem;
}

.status-excelente {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-dark);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-bueno {
    background: rgba(6, 182, 212, 0.1);
    color: var(--info-dark);
    border: 1px solid rgba(6, 182, 212, 0.3);
}

.status-regular {
    background: rgba(30, 58, 95, 0.1);
    color: var(--primary-blue);
    border: 1px solid rgba(30, 58, 95, 0.3);
}

.status-deficiente {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-dark);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.course-info-detailed {
    background: var(--surface);
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-top: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
}

.info-row:last-child {
    border-bottom: none;
}

.info-row strong {
    color: var(--gray-700);
    font-weight: 600;
}

.info-row span {
    color: var(--gray-900);
    font-weight: 500;
}

/* Animaciones de entrada */
.modal-content.slide-in {
    animation: slideInModal 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideInModal {
    0% {
        transform: scale(0.8) translateY(100px);
        opacity: 0;
    }
    100% {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

/* Modal para actualizar datos - Movible */
.update-modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    opacity: 0;
    transition: all 0.3s ease;
}

.update-modal.show {
    display: flex;
    opacity: 1;
    align-items: center;
    justify-content: center;
}

.update-modal-content {
    background: white;
    border-radius: var(--border-radius);
    max-width: 600px;
    width: 90%;
    max-height: 90%;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    transform: scale(0.7);
    transition: transform 0.3s ease;
    position: relative;
}

.update-modal.show .update-modal-content {
    transform: scale(1);
}

.update-modal-header {
    padding: 1.5rem 2rem;
    background: var(--gradient-header);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: move;
    user-select: none;
}

.update-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.update-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.8);
    transition: var(--transition);
}

.update-modal-close:hover {
    color: white;
}

.update-modal-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.terms-section {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary-blue);
}

.terms-section h4 {
    color: var(--primary-blue);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.terms-section p {
    color: var(--gray-600);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border);
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: var(--transition);
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.btn-cancel {
    background: var(--gray-300);
    color: var(--gray-700);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 600;
    transition: var(--transition);
}

.btn-cancel:hover {
    background: var(--gray-400);
}

.btn-submit {
    background: var(--gradient-header);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 600;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-submit:hover {
    background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--primary-blue) 100%);
    transform: translateY(-1px);
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

.alert i {
    margin-right: 0.5rem;
}

.badge {
    padding: 0.375rem 0.75rem;
    border-radius: 1rem;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.bg-primary {
    background: var(--primary) !important;
    color: white;
}

.bg-warning {
    background: var(--primary-blue) !important;
    color: white;
}

/* Tooltip para notas ocultas */
.grade-tooltip {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--gray-800);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
    z-index: 10;
}

.course-grade:hover .grade-tooltip {
    opacity: 1;
    visibility: visible;
}

.grade-tooltip::before {
    content: '';
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 5px solid var(--gray-800);
}

/* Responsive Design */
@media (max-width: 768px) {
    .cursos-container {
        padding: 1rem;
    }
    
    .page-header {
        padding: 2rem 1.5rem;
    }
    
    .page-header .content {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .page-header h1 {
        font-size: 1.875rem;
    }
    
    .header-update-btn {
        align-self: stretch;
        justify-content: center;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .course-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .course-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .student-info {
        padding: 1.5rem;
    }
    
    .section-content {
        padding: 1.5rem;
    }
    
    .update-modal-content {
        width: 95%;
        margin: 1rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
        max-height: 85vh;
    }
    
    .modal-header {
        padding: 1.5rem;
    }
    
    .modal-body {
        padding: 1.5rem;
        max-height: 50vh;
    }
    
    .grade-circle {
        width: 100px;
        height: 100px;
    }
    
    .grade-number {
        font-size: 2rem;
    }
    
    .course-title {
        font-size: 1.2rem;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .course-card {
        padding: 1.25rem;
    }
    
    .section-header {
        padding: 1.25rem 1.5rem;
    }
    
    .section-title {
        font-size: 1.25rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .header-update-btn {
        padding: 0.75rem 1rem;
        font-size: 0.8rem;
    }
    
    .update-modal-body {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .modal-content {
        width: 98%;
        margin: 0.5rem;
    }
    
    .modal-header {
        padding: 1rem;
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .modal-header::after {
        display: none;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .course-grade::after {
        display: none;
    }
}

/* Animaciones adicionales */
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

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Estados de carga */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--gray-300);
    border-top: 4px solid var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mejoras de accesibilidad */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus visible para mejor accesibilidad */
.course-grade:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.header-update-btn:focus-visible,
.btn-mentoria-extrema:focus-visible,
.btn-mentoria-urgente:focus-visible,
.btn-mentoria-puede-requerir:focus-visible,
.btn-mentoria-excelente:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.5);
    outline-offset: 2px;
}

.modal-close:focus-visible,
.update-modal-close:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Estados de hover mejorados */
.course-card:hover .course-grade {
    transform: scale(1.1);
}

.course-card:hover .course-grade.hidden::before {
    animation: pulse 1s infinite;
}

/* Transiciones suaves para todos los elementos interactivos */
* {
    transition: var(--transition);
}

/* Desactivar transiciones durante el redimensionamiento */
.resize-animation-stopper * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
}
</style>
<div class="cursos-container">
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="content">
                <div class="header-text">
                    <h1>
                        <i class="fas fa-graduation-cap"></i>
                        Mis Cursos
                    </h1>
                    <p>Gestiona tus cursos académicos y solicita mentoría cuando la necesites</p>
                </div>
                <button class="header-update-btn" onclick="openUpdateModal()">
                    <i class="fas fa-sync-alt"></i>
                    Actualizar Datos
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($estudiante): ?>
            <!-- Información del Estudiante -->
            <div class="student-info">
                <h3>
                    <i class="fas fa-user"></i>
                    Información Académica
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-id-card"></i>
                        <div>
                            <span class="label">Código:</span>
                            <span class="value"><?= htmlspecialchars($estudiante['codigo_estudiante']) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-graduate"></i>
                        <div>
                            <span class="label">Estudiante:</span>
                            <span class="value"><?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <div>
                            <span class="label">Carrera:</span>
                            <span class="value"><?= htmlspecialchars($estudiante['carrera']) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <span class="label">Promedio:</span>
                            <span class="value"><?= $estudiante['promedio_general'] ? number_format($estudiante['promedio_general'], 2) : 'N/A' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Todos los Cursos -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-book-open"></i>
                        Mis Cursos Académicos
                    </h2>
                    <span class="badge bg-primary"><?= count($cursos) ?></span>
                </div>
                <div class="section-content">
                    <?php if (!empty($cursos)): ?>
                        <div class="courses-grid">
                            <?php foreach ($cursos as $curso): ?>
                                <?php 
                                // Calcular el valor de necesita_mentoria basado en la nota
                                $necesita_mentoria = calcularNecesitaMentoria($curso['ponderado']);
                                
                                // Determinar la clase de mentoría basada en necesita_mentoria (0, 1, 2, 3)
                                $mentoriaClass = '';
                                $mentoriaAlert = '';
                                $mentoriaButton = '';
                                $mentoriaText = '';
                                $mentoriaIcon = '';
                                
                                switch($necesita_mentoria) {
                                    case 0: // 0-5 puntos - Necesita mentoría con extrema urgencia
                                        $mentoriaClass = 'mentoria-extrema';
                                        $mentoriaAlert = 'mentoring-extrema';
                                        $mentoriaButton = 'btn-mentoria-extrema';
                                        $mentoriaText = 'Requiere Mentoría Inmediata';
                                        $mentoriaIcon = 'fas fa-exclamation-triangle';
                                        break;
                                    case 1: // 6-10 puntos - Necesita mentoría urgente
                                        $mentoriaClass = 'mentoria-urgente';
                                        $mentoriaAlert = 'mentoring-urgente';
                                        $mentoriaButton = 'btn-mentoria-urgente';
                                        $mentoriaText = 'Necesita Mentoría Urgente';
                                        $mentoriaIcon = 'fas fa-exclamation-circle';
                                        break;
                                    case 2: // 11-15 puntos - Puede requerir mentoría
                                        $mentoriaClass = 'mentoria-puede-requerir';
                                        $mentoriaAlert = 'mentoring-puede-requerir';
                                        $mentoriaButton = 'btn-mentoria-puede-requerir';
                                        $mentoriaText = 'Puede Requerir Mentoría';
                                        $mentoriaIcon = 'fas fa-info-circle';
                                        break;
                                    case 3: // 16-20 puntos - Excelente rendimiento
                                        $mentoriaClass = 'mentoria-excelente';
                                        $mentoriaAlert = 'mentoring-excelente';
                                        $mentoriaButton = 'btn-mentoria-excelente';
                                        $mentoriaText = 'Excelente Rendimiento';
                                        $mentoriaIcon = 'fas fa-trophy';
                                        break;
                                }
                                ?>
                                <div class="course-card <?= $mentoriaClass ?>">
                                    <div class="course-header">
                                        <div class="course-code"><?= htmlspecialchars($curso['codigo_curso']) ?></div>
                                        <div class="course-grade grade-<?= $curso['ponderado'] >= 16 ? 'excellent' : ($curso['ponderado'] >= 14 ? 'good' : ($curso['ponderado'] >= 11 ? 'warning' : 'danger')) ?>"
                                             onclick="mostrarNotas('<?= htmlspecialchars($curso['codigo_curso']) ?>', '<?= htmlspecialchars($curso['nombre']) ?>', <?= $curso['ponderado'] ?>)"
                                             title="Click para ver detalles de la nota">
                                            <?= number_format($curso['ponderado'], 1) ?>
                                        </div>
                                    </div>
                                    <div class="course-name"><?= htmlspecialchars($curso['nombre']) ?></div>
                                    <div class="course-details">
                                        <span class="course-credits">
                                            <i class="fas fa-clock"></i>
                                            <?= $curso['creditos'] ?> créditos
                                        </span>
                                        <span class="course-status status-matriculado">
                                            <?= htmlspecialchars($curso['estado_curso']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mentoring-alert <?= $mentoriaAlert ?>">
                                        <i class="<?= $mentoriaIcon ?>"></i>
                                        <?= $mentoriaText ?>
                                    </div>
                                    
                                    <?php if ($necesita_mentoria < 3): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="accion" value="solicitar_mentoria">
                                            <input type="hidden" name="id_curso" value="<?= $curso['id_curso'] ?>">
                                            <button type="submit" class="<?= $mentoriaButton ?>">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                <?php
                                                switch($necesita_mentoria) {
                                                    case 0:
                                                        echo 'Solicitar Mentoría Inmediata';
                                                        break;
                                                    case 1:
                                                        echo 'Solicitar Mentoría Urgente';
                                                        break;
                                                    case 2:
                                                        echo 'Solicitar Mentoría';
                                                        break;
                                                }
                                                ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="<?= $mentoriaButton ?>" disabled>
                                            <i class="fas fa-star me-2"></i>
                                            Excelente Rendimiento
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No tienes cursos registrados</h3>
                            <p>Aún no tienes cursos en tu registro académico. Completa el proceso de vinculación para ver tus cursos matriculados.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-graduate"></i>
                <h3>Información de estudiante no encontrada</h3>
                <p>No se pudo cargar tu información académica. Por favor, completa el proceso de vinculación con tu cuenta UPT.</p>
                <a href="<?= BASE_URL ?>/index.php?accion=vincular" class="btn btn-primary">
                    <i class="fas fa-link me-2"></i>
                    Vincular Cuenta UPT
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para ver notas -->
<div id="notasModal" class="modal">
    <div class="modal-content" id="notasModalContent">
        <div class="modal-header" id="notasModalHeader">
            <h3 class="modal-title">
                <i class="fas fa-chart-bar"></i>
                Detalles de Calificación
            </h3>
            <button class="modal-close" type="button" onclick="closeNotasModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="text-align: center;">
                <h4 id="curso-nombre" style="color: var(--primary); margin-bottom: 1rem;"></h4>
                <div style="display: inline-block; padding: 2rem; background: var(--surface); border-radius: 1rem; margin-bottom: 1.5rem;">
                    <div style="font-size: 3rem; font-weight: 900; margin-bottom: 0.5rem;" id="curso-nota"></div>
                    <div style="color: var(--gray-600); font-weight: 600;">Ponderado Final</div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: 0.5rem; text-align: left;">
                    <h5 style="color: var(--primary); margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i>
                        Información Adicional
                    </h5>
                    <p style="margin-bottom: 0.5rem;"><strong>Código del Curso:</strong> <span id="curso-codigo"></span></p>
                    <p style="margin-bottom: 0.5rem;"><strong>Estado:</strong> <span style="color: var(--success);">Matriculado</span></p>
                    <p style="margin: 0;"><strong>Nota mínima aprobatoria:</strong> 10.5</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para actualizar datos académicos -->
<div id="updateModal" class="update-modal">
    <div class="update-modal-content" id="updateModalContent">
        <div class="update-modal-header" id="updateModalHeader">
            <h3 class="update-modal-title">
                <i class="fas fa-sync-alt"></i>
                Actualizar Datos Académicos
            </h3>
            <button class="update-modal-close" type="button" onclick="closeUpdateModal()">&times;</button>
        </div>
        <div class="update-modal-body">
            <div class="terms-section">
                <h4><i class="fas fa-shield-alt"></i> Términos y Condiciones</h4>
                <p>
                    Al utilizar esta función de actualización de datos académicos, usted acepta que sus datos 
                    personales y académicos serán recuperados y manejados de acuerdo con la legislación vigente 
                    sobre protección de datos personales.
                </p>
                <p>
                    La información será utilizada exclusivamente para fines académicos y administrativos, 
                    garantizando la confidencialidad y seguridad de sus datos conforme a las políticas 
                    de privacidad de la Universidad Privada de Tacna.
                </p>
                <p style="margin: 0;">
                    <strong>Al continuar, usted acepta estos términos y autoriza el procesamiento de sus datos.</strong>
                </p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="accion" value="actualizar_datos">
                
                <div class="form-group">
                    <label class="form-label" for="codigo_estudiante">
                        <i class="fas fa-key"></i> Código de Estudiante (10 dígitos)
                    </label>
                    <input 
                        type="text" 
                        id="codigo_estudiante" 
                        name="codigo_estudiante" 
                        class="form-input" 
                        maxlength="10" 
                        pattern="[0-9]{10}" 
                        placeholder="Ingrese su código de estudiante"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password_estudiante">
                        <i class="fas fa-lock"></i> Contraseña (6 dígitos)
                    </label>
                    <input 
                        type="password" 
                        id="password_estudiante" 
                        name="password_estudiante" 
                        class="form-input" 
                        maxlength="6" 
                        pattern="[0-9]{6}" 
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeUpdateModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar Datos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Variables globales
let isDragging = false;
let currentModal = null;
let startX, startY, initialX, initialY;
let notasOcultas = new Set();

// Inicialización principal
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Auto-remover alertas
    setupAlerts();
    
    // Configurar formularios de mentoría
    setupMentoriaForms();
    
    // Animación de entrada
    animateCards();
    
    // Validación de campos
    setupFieldValidation();
    
    // Ocultar notas inicialmente
    setupSecureGrades();
    
    // Configurar modales
    setupModals();
    
    // Configurar eventos de teclado
    setupKeyboardEvents();
    
    // Configurar redimensionamiento de ventana
    setupWindowResize();
}

// === GESTIÓN DE ALERTAS ===
function setupAlerts() {
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
}

// === GESTIÓN DE NOTAS SEGURAS ===
function setupSecureGrades() {
    const grades = document.querySelectorAll('.course-grade');
    grades.forEach((grade, index) => {
        const gradeNumber = grade.textContent.trim();
        
        // Validar que sea un número válido antes de proceder
        const numericGrade = parseFloat(gradeNumber);
        if (isNaN(numericGrade)) {
            console.warn('Nota no válida encontrada:', gradeNumber);
            return; // Saltar esta nota si no es válida
        }
        
        grade.setAttribute('data-grade', gradeNumber);
        grade.setAttribute('data-index', index);
        
        // Crear ícono de ojo para ocultar la nota
        const eyeIcon = document.createElement('i');
        eyeIcon.className = 'fas fa-eye';
        eyeIcon.style.fontSize = '1.2rem';
        
        // Guardar el contenido original y reemplazar con ícono
        grade.innerHTML = '';
        grade.appendChild(eyeIcon);
        grade.classList.add('hidden');
        notasOcultas.add(index);
        
        // Agregar evento de click para revelar/ocultar
        grade.addEventListener('click', function(e) {
            e.stopPropagation();
            revelarNota(this, index, gradeNumber);
        });
        
        // Evento de teclado para accesibilidad
        grade.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                revelarNota(this, index, gradeNumber);
            }
        });
        
        // Accesibilidad
        grade.setAttribute('tabindex', '0');
        grade.setAttribute('role', 'button');
        grade.setAttribute('aria-label', 'Nota oculta, click para revelar');
        grade.title = 'Click para ver la nota';
    });
}

function revelarNota(gradeElement, index, gradeNumber) {
    if (notasOcultas.has(index)) {
        // Revelar la nota: cambiar ícono por número
        gradeElement.innerHTML = gradeNumber;
        gradeElement.classList.remove('hidden');
        notasOcultas.delete(index);
        gradeElement.title = 'Click para ocultar la nota';
        
        // Mostrar el modal después de un breve delay
        setTimeout(() => {
            const codigo = gradeElement.closest('.course-card').querySelector('.course-code').textContent;
            const nombre = gradeElement.closest('.course-card').querySelector('.course-name').textContent;
            // Usar el valor guardado en lugar de parseFloat del contenido actual
            const ponderado = parseFloat(gradeNumber);
            mostrarNotas(codigo, nombre, ponderado);
        }, 150);
    } else {
        // Ocultar la nota: cambiar número por ícono
        const eyeIcon = document.createElement('i');
        eyeIcon.className = 'fas fa-eye';
        eyeIcon.style.fontSize = '1.2rem';
        
        gradeElement.innerHTML = '';
        gradeElement.appendChild(eyeIcon);
        gradeElement.classList.add('hidden');
        notasOcultas.add(index);
        gradeElement.title = 'Click para ver la nota';
    }
}

function ocultarTodasLasNotasNuevamente() {
    const grades = document.querySelectorAll('.course-grade');
    grades.forEach((grade, index) => {
        if (!notasOcultas.has(index)) {
            // Crear ícono de ojo
            const eyeIcon = document.createElement('i');
            eyeIcon.className = 'fas fa-eye';
            eyeIcon.style.fontSize = '1.2rem';
            
            // Reemplazar contenido con ícono
            grade.innerHTML = '';
            grade.appendChild(eyeIcon);
            grade.classList.add('hidden');
            notasOcultas.add(index);
            grade.title = 'Click para ver la nota';
        }
    });
}

// === MODAL DE NOTAS ===
function mostrarNotas(codigo, nombre, ponderado) {
    // Validar que ponderado sea un número válido
    if (isNaN(ponderado) || ponderado === null || ponderado === undefined) {
        console.error('Nota inválida:', ponderado);
        showToast('Error: Nota no válida', 'danger');
        return;
    }
    
    const modal = document.getElementById('notasModal');
    const modalContent = document.getElementById('notasModalContent');
    const cursoNombre = document.getElementById('curso-nombre');
    const cursoNota = document.getElementById('curso-nota');
    const cursoCodigo = document.getElementById('curso-codigo');
    const gradeStatus = document.getElementById('grade-status');
    const fechaConsulta = document.getElementById('fecha-consulta');
    
    // Llenar datos del modal
    cursoNombre.textContent = nombre;
    cursoNota.textContent = ponderado.toFixed(1);
    cursoCodigo.textContent = codigo;
    
    // Establecer fecha actual
    const ahora = new Date();
    if (fechaConsulta) {
        fechaConsulta.textContent = ahora.toLocaleDateString('es-PE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Aplicar colores y estado según la nota
    let statusClass = '';
    let statusText = '';
    
    if (ponderado >= 16) {
        cursoNota.style.color = 'var(--success)';
        statusClass = 'status-excelente';
        statusText = 'Excelente';
    } else if (ponderado >= 14) {
        cursoNota.style.color = 'var(--info)';
        statusClass = 'status-bueno';
        statusText = 'Bueno';
    } else if (ponderado >= 11) {
        cursoNota.style.color = 'var(--primary-blue)';
        statusClass = 'status-regular';
        statusText = 'Regular';
    } else if (ponderado >= 6) {
        cursoNota.style.color = '#ff6b35';
        statusClass = 'status-deficiente';
        statusText = 'Deficiente';
    } else {
        cursoNota.style.color = 'var(--danger)';
        statusClass = 'status-deficiente';
        statusText = 'Muy Deficiente';
    }
    
    if (gradeStatus) {
        gradeStatus.className = `grade-status ${statusClass}`;
        gradeStatus.textContent = statusText;
    }
    
    // Resetear posición del modal
    resetModalPosition(modalContent);
    
    // Agregar clase de animación
    modalContent.classList.add('slide-in');
    
    // Mostrar modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Remover clase de animación
    setTimeout(() => {
        modalContent.classList.remove('slide-in');
    }, 400);
}

function closeNotasModal() {
    const modal = document.getElementById('notasModal');
    const modalContent = document.getElementById('notasModalContent');
    
    // Animación de cierre
    modalContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    modalContent.style.transform = 'translateY(100%) scale(0.8)';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0)';
    
    setTimeout(() => {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        
        // Resetear estilos
        resetModalStyles(modalContent);
        modal.style.backgroundColor = '';
    }, 300);
}

// === MODAL DE ACTUALIZACIÓN ===
function openUpdateModal() {
    const modal = document.getElementById('updateModal');
    const modalContent = document.getElementById('updateModalContent');
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Resetear posición del modal
    resetModalPosition(modalContent);
}

function closeUpdateModal() {
    const modal = document.getElementById('updateModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    
    // Limpiar formulario
    const codigoInput = document.getElementById('codigo_estudiante');
    const passwordInput = document.getElementById('password_estudiante');
    if (codigoInput) codigoInput.value = '';
    if (passwordInput) passwordInput.value = '';
}

// === FUNCIONES AUXILIARES DE MODAL ===
function resetModalPosition(modalContent) {
    modalContent.style.transform = 'translate(0, 0)';
    modalContent.style.left = 'auto';
    modalContent.style.top = 'auto';
    modalContent.style.position = 'relative';
    modalContent.style.transition = '';
}

function resetModalStyles(modalContent) {
    modalContent.style.transform = '';
    modalContent.style.transition = '';
    modalContent.style.left = '';
    modalContent.style.top = '';
    modalContent.style.position = '';
}

// === CONFIGURACIÓN DE MODALES ===
function setupModals() {
    setupModalDragging();
    setupSwipeableModal();
    setupModalEvents();
}

function setupModalEvents() {
    // Modal de notas
    const notasModal = document.getElementById('notasModal');
    if (notasModal) {
        notasModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotasModal();
            }
        });
    }
    
    // Modal de actualización
    const updateModal = document.getElementById('updateModal');
    if (updateModal) {
        updateModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeUpdateModal();
            }
        });
        
        // Validar formulario
        const updateForm = updateModal.querySelector('form');
        if (updateForm) {
            updateForm.addEventListener('submit', handleUpdateFormSubmit);
        }
    }
    
    // Doble click para ocultar notas
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.addEventListener('dblclick', function() {
            if (confirm('¿Deseas ocultar todas las notas nuevamente por seguridad?')) {
                ocultarTodasLasNotasNuevamente();
                showToast('Todas las notas han sido ocultadas nuevamente', 'info');
            }
        });
    }
}

function handleUpdateFormSubmit(e) {
    const codigo = document.getElementById('codigo_estudiante').value;
    const password = document.getElementById('password_estudiante').value;
    
    if (codigo.length !== 10) {
        e.preventDefault();
        showToast('El código de estudiante debe tener exactamente 10 dígitos', 'danger');
        document.getElementById('codigo_estudiante').focus();
        return;
    }
    
    if (password.length !== 6) {
        e.preventDefault();
        showToast('La contraseña debe tener exactamente 6 dígitos', 'danger');
        document.getElementById('password_estudiante').focus();
        return;
    }
    
    // Mostrar confirmación
    const confirmacion = confirm(
        '¿Está seguro de que desea actualizar sus datos académicos?\n\n' +
        'Esta acción:\n' +
        '• Consultará la base de datos académica de la UPT\n' +
        '• Actualizará sus calificaciones y cursos\n' +
        '• Puede tomar algunos minutos en procesarse\n\n' +
        '¿Desea continuar?'
    );
    
    if (!confirmacion) {
        e.preventDefault();
        return;
    }
    
    // Deshabilitar botón y mostrar loading
    const btn = e.target.querySelector('.btn-submit');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<div class="loading-spinner"></div> Actualizando datos académicos...';
    
    // Restaurar botón en caso de error
    setTimeout(() => {
        if (btn.disabled) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }, 10000);
}

// === MODAL DESLIZABLE (SWIPE) ===
function setupSwipeableModal() {
    const modal = document.getElementById('notasModal');
    const modalContent = document.getElementById('notasModalContent');
    if (!modal || !modalContent) return;
    
    let startY = 0;
    let currentY = 0;
    let isDraggingModal = false;
    let initialTransform = 0;

    // Touch events
    modalContent.addEventListener('touchstart', handleTouchStart, { passive: false });
    modalContent.addEventListener('touchmove', handleTouchMove, { passive: false });
    modalContent.addEventListener('touchend', handleTouchEnd, { passive: false });

    // Mouse events
    modalContent.addEventListener('mousedown', handleMouseStart);
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseEnd);

    function handleTouchStart(e) {
        if (e.target.closest('.modal-close')) return;
        startY = e.touches[0].clientY;
        isDraggingModal = true;
        modalContent.style.transition = 'none';
    }

    function handleMouseStart(e) {
        if (e.target.closest('.modal-close')) return;
        startY = e.clientY;
        isDraggingModal = true;
        modalContent.style.transition = 'none';
        e.preventDefault();
    }

    function handleTouchMove(e) {
        if (!isDraggingModal) return;
        e.preventDefault();
        currentY = e.touches[0].clientY;
        handleDrag();
    }

    function handleMouseMove(e) {
        if (!isDraggingModal) return;
        e.preventDefault();
        currentY = e.clientY;
        handleDrag();
    }

    function handleDrag() {
        const deltaY = currentY - startY;
        
        if (deltaY > 0) {
            const translateY = Math.min(deltaY, 300);
            const opacity = Math.max(1 - (translateY / 300), 0.3);
            
            modalContent.style.transform = `translateY(${translateY}px) scale(${1 - translateY / 1000})`;
            modal.style.backgroundColor = `rgba(0, 0, 0, ${0.7 * opacity})`;
        }
    }

    function handleTouchEnd() {
        handleDragEnd();
    }

    function handleMouseEnd() {
        handleDragEnd();
    }

    function handleDragEnd() {
        if (!isDraggingModal) return;
        isDraggingModal = false;
        
        const deltaY = currentY - startY;
        
        modalContent.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        
        if (deltaY > 100) {
            closeNotasModal();
        } else {
            modalContent.style.transform = 'translateY(0) scale(1)';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        }
        
        setTimeout(() => {
            modalContent.style.transition = '';
        }, 300);
    }
}

// === ARRASTRE DE MODALES ===
function setupModalDragging() {
    const notasModalHeader = document.getElementById('notasModalHeader');
    const notasModalContent = document.getElementById('notasModalContent');
    
    if (notasModalHeader && notasModalContent) {
        setupDraggableModal(notasModalHeader, notasModalContent);
    }
    
    const updateModalHeader = document.getElementById('updateModalHeader');
    const updateModalContent = document.getElementById('updateModalContent');
    
    if (updateModalHeader && updateModalContent) {
        setupDraggableModal(updateModalHeader, updateModalContent);
    }
}

function setupDraggableModal(header, content) {
    header.addEventListener('mousedown', function(e) {
        if (e.target.closest('.modal-close, .update-modal-close')) return;
        
        isDragging = true;
        currentModal = content;
        
        startX = e.clientX;
        startY = e.clientY;
        
        const rect = content.getBoundingClientRect();
        initialX = rect.left;
        initialY = rect.top;
        
        document.body.style.cursor = 'grabbing';
        header.style.cursor = 'grabbing';
        
        e.preventDefault();
    });
    
    header.style.cursor = 'grab';
    header.title = 'Arrastra para mover el modal';
}

// Event listeners globales para arrastre
document.addEventListener('mousemove', function(e) {
    if (!isDragging || !currentModal) return;
    
    e.preventDefault();
    
    const deltaX = e.clientX - startX;
    const deltaY = e.clientY - startY;
    
    const newX = initialX + deltaX;
    const newY = initialY + deltaY;
    
    const modalRect = currentModal.getBoundingClientRect();
    const maxX = window.innerWidth - modalRect.width;
    const maxY = window.innerHeight - modalRect.height;
    
    const constrainedX = Math.max(0, Math.min(newX, maxX));
    const constrainedY = Math.max(0, Math.min(newY, maxY));
    
    currentModal.style.position = 'fixed';
    currentModal.style.left = constrainedX + 'px';
    currentModal.style.top = constrainedY + 'px';
    currentModal.style.transform = 'none';
    currentModal.style.margin = '0';
});

document.addEventListener('mouseup', function() {
    if (isDragging) {
        isDragging = false;
        currentModal = null;
        
        document.body.style.cursor = 'auto';
        
        const headers = document.querySelectorAll('.modal-header, .update-modal-header');
        headers.forEach(header => {
            header.style.cursor = 'grab';
        });
    }
});

// === FORMULARIOS DE MENTORÍA ===
function setupMentoriaForms() {
    const mentoriaForms = document.querySelectorAll('form[action=""]');
    mentoriaForms.forEach(form => {
        if (form.querySelector('input[name="accion"][value="solicitar_mentoria"]')) {
            form.addEventListener('submit', function(e) {
                const curso = this.closest('.course-card').querySelector('.course-name').textContent;
                const tipoMentoria = this.querySelector('button[type="submit"]').textContent.trim();
                
                const mensaje = `¿Estás seguro de que quieres solicitar mentoría para:\n\n${curso}?\n\nTipo: ${tipoMentoria}`;
                
                if (!confirm(mensaje)) {
                    e.preventDefault();
                    return;
                }
                
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<div class="loading-spinner"></div> Solicitando...';
            });
        }
    });
}

// === VALIDACIÓN DE CAMPOS ===
function setupFieldValidation() {
    const codigoInput = document.getElementById('codigo_estudiante');
    const passwordInput = document.getElementById('password_estudiante');
    
    if (codigoInput) {
        codigoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
            
            updateFieldValidation(this, this.value.length === 10);
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
            
            updateFieldValidation(this, this.value.length === 6);
        });
    }
}

function updateFieldValidation(input, isValid) {
    if (isValid) {
        input.style.borderColor = 'var(--success)';
        input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
    } else {
        input.style.borderColor = 'var(--border)';
        input.style.boxShadow = '';
    }
}

// === ANIMACIONES ===
function animateCards() {
    const cards = document.querySelectorAll('.course-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });
}

// === EVENTOS DE TECLADO ===
function setupKeyboardEvents() {
    document.addEventListener('keydown', function(e) {
        // Cerrar modal con Escape
        if (e.key === 'Escape') {
            const notasModal = document.getElementById('notasModal');
            const updateModal = document.getElementById('updateModal');
            
            if (notasModal && notasModal.classList.contains('show')) {
                closeNotasModal();
            } else if (updateModal && updateModal.classList.contains('show')) {
                closeUpdateModal();
            }
        }
        
        // Ocultar todas las notas con Ctrl+H
        if (e.ctrlKey && e.key === 'h') {
            e.preventDefault();
            ocultarTodasLasNotasNuevamente();
            showToast('Todas las notas han sido ocultadas', 'info');
        }
    });
}

// === REDIMENSIONAMIENTO DE VENTANA ===
function setupWindowResize() {
    let resizeTimer;
    window.addEventListener('resize', function() {
        // Prevenir animaciones durante el redimensionamiento
        document.body.classList.add('resize-animation-stopper');
        
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            document.body.classList.remove('resize-animation-stopper');
        }, 400);
    });
}

// === FUNCIONES AUXILIARES ===
function showToast(message, type = 'info') {
    // Crear toast dinámico
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Estilos del toast
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success)' : type === 'danger' ? 'var(--danger)' : 'var(--info)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        box-shadow: var(--shadow-lg);
        min-width: 300px;
    `;
    
    document.body.appendChild(toast);
    
    // Mostrar
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar y remover
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// === FUNCIONES GLOBALES PARA BOTONES ===
window.openUpdateModal = openUpdateModal;
window.closeUpdateModal = closeUpdateModal;
window.closeNotasModal = closeNotasModal;
window.mostrarNotas = mostrarNotas;

// Función para debugging
window.debugNotasOcultas = function() {
    console.log('Notas ocultas:', notasOcultas);
    console.log('Total de notas:', document.querySelectorAll('.course-grade').length);
};
</script>
<?php require_once BASE_PATH . '/views/components/footer.php'; ?>