<?php
require_once BASE_PATH . '/views/components/head.php';
require_once BASE_PATH . '/views/components/header.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] !== 1) {
    header('Location: ' . BASE_URL . '/public?accion=login');
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? '';
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje'], $_SESSION['error']);
?>

<style>
:root {
    --primary-blue: #1e3a5f;
    --secondary-blue: #2c5282;
    --accent-blue: #3182ce;
    --light-blue: #ebf8ff;
    --dark-blue: #1a202c;
    --success-green: #38a169;
    --warning-orange: #ed8936;
    --danger-red: #e53e3e;
    --light-gray: #f7fafc;
    --border-gray: #e2e8f0;
    --text-gray: #4a5568;
    --gradient-primary: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    --gradient-accent: linear-gradient(135deg, var(--accent-blue) 0%, var(--primary-blue) 100%);
    --gradient-success: linear-gradient(135deg, var(--success-green) 0%, #48bb78 100%);
    --shadow-sm: 0 2px 8px rgba(30, 58, 95, 0.1);
    --shadow-md: 0 4px 20px rgba(30, 58, 95, 0.15);
    --shadow-lg: 0 8px 30px rgba(30, 58, 95, 0.2);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --border-radius: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e0 100%);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 20% 50%, rgba(49, 130, 206, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(56, 161, 105, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(237, 137, 54, 0.05) 0%, transparent 50%);
    z-index: -1;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    33% { transform: translateY(-10px) rotate(0.5deg); }
    66% { transform: translateY(-5px) rotate(-0.5deg); }
}

.vincular-container {
    padding: 1rem;
    position: relative;
}

.vincular-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    max-width: 600px;
    width: 100%;
    margin: 2rem auto;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transform: translateY(0);
    transition: var(--transition);
}

.vincular-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(30, 58, 95, 0.15);
}

.vincular-header {
    background: var(--gradient-primary);
    color: white;
    padding: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.vincular-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.vincular-header .icon-wrapper {
    position: relative;
    z-index: 2;
    margin-bottom: 1rem;
}

.vincular-header .main-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: inline-block;
    animation: bounce 2s infinite;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-8px); }
    60% { transform: translateY(-4px); }
}

.vincular-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 2;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    letter-spacing: -0.01em;
}

.vincular-header p {
    opacity: 0.9;
    font-size: 1rem;
    margin: 0;
    position: relative;
    z-index: 2;
    font-weight: 400;
}

.vincular-body {
    padding: 2rem;
    background: white;
    position: relative;
}

.form-section {
    opacity: 1;
    transform: translateY(0);
    transition: var(--transition);
}

.form-section.hidden {
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
}

.form-floating {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-control {
    border: 2px solid var(--border-gray);
    border-radius: var(--border-radius);
    padding: 1rem 1rem 1rem 3rem;
    font-size: 1rem;
    transition: var(--transition);
    background: var(--light-gray);
    font-weight: 500;
    color: var(--dark-blue);
    width: 100%;
}

.form-control:focus {
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.15);
    background: white;
    transform: translateY(-1px);
}

.form-control:hover {
    border-color: var(--accent-blue);
    background: white;
}

.form-floating .form-control {
    height: 55px;
    padding-left: 3rem;
    padding-top: 1.5rem;
    padding-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.2;
}

.form-floating > label {
    padding-left: 3rem;
    color: var(--text-gray);
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
}

.form-floating .form-control:focus ~ label,
.form-floating .form-control:not(:placeholder-shown) ~ label {
    color: var(--accent-blue);
    transform: scale(0.9) translateY(-0.5rem);
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    pointer-events: none;
    transform: translateY(-50%);
    color: var(--text-gray);
    font-size: 1.2rem;
    z-index: 5;
    transition: var(--transition);
}

.form-floating .form-control:focus ~ .input-icon,
.form-floating .form-control:not(:placeholder-shown) ~ .input-icon {
    color: var(--accent-blue);
    transform: translateY(-50%) scale(1.05);
}

.consent-section {
    background: linear-gradient(135deg, var(--light-blue) 0%, rgba(49, 130, 206, 0.08) 100%);
    border: 2px solid rgba(49, 130, 206, 0.2);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.consent-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 10% 10%, rgba(49,130,206,0.1) 0%, transparent 50%),
        radial-gradient(circle at 90% 90%, rgba(49,130,206,0.1) 0%, transparent 50%);
    opacity: 0.6;
    animation: pulse-bg 4s ease-in-out infinite;
}

@keyframes pulse-bg {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.8; }
}

.consent-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
}

.consent-icon {
    width: 40px;
    height: 40px;
    background: var(--gradient-accent);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 1rem;
    box-shadow: var(--shadow-md);
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: var(--shadow-md); }
    to { box-shadow: 0 2px 10px rgba(49, 130, 206, 0.3); }
}

.consent-title {
    color: var(--primary-blue);
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
    letter-spacing: -0.01em;
}

.consent-content {
    position: relative;
    z-index: 2;
}

.consent-text {
    color: var(--text-gray);
    margin-bottom: 1rem;
    line-height: 1.5;
    text-align: justify;
    font-size: 0.9rem;
    font-weight: 400;
}

.consent-text strong {
    color: var(--primary-blue);
    font-weight: 600;
}

.data-protection-info {
    background: rgba(56, 161, 105, 0.1);
    border: 1px solid var(--success-green);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    position: relative;
    overflow: hidden;
}

.data-protection-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: var(--gradient-success);
}

.data-protection-info .icon {
    color: var(--success-green);
    font-size: 1.2rem;
    margin-right: 0.75rem;
    margin-top: 0.1rem;
    flex-shrink: 0;
}

.data-protection-info .text {
    color: var(--success-green);
    font-weight: 600;
    font-size: 0.85rem;
    line-height: 1.4;
}

.data-list {
    background: white;
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(49, 130, 206, 0.1);
}

.data-list h6 {
    color: var(--primary-blue);
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.data-list h6 i {
    margin-right: 0.5rem;
    color: var(--accent-blue);
    font-size: 1rem;
}

.data-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.data-items li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
    color: var(--text-gray);
    border-bottom: 1px solid var(--border-gray);
    transition: var(--transition);
    font-size: 0.85rem;
}

.data-items li:last-child {
    border-bottom: none;
}

.data-items li i {
    color: var(--accent-blue);
    margin-right: 0.75rem;
    font-size: 0.9rem;
    width: 16px;
    flex-shrink: 0;
}

.data-items li span {
    line-height: 1.4;
}

.data-items li strong {
    color: var(--primary-blue);
}

.consent-checkbox {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: var(--border-radius);
    border: 2px solid var(--border-gray);
    transition: var(--transition);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.consent-checkbox:hover {
    border-color: var(--accent-blue);
    box-shadow: var(--shadow-sm);
    transform: translateY(-1px);
}

.consent-checkbox.checked {
    border-color: var(--success-green);
    background: rgba(56, 161, 105, 0.05);
}

.consent-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 1rem;
    margin-top: 0.2rem;
    flex-shrink: 0;
    accent-color: var(--accent-blue);
    cursor: pointer;
}

.consent-checkbox label {
    color: var(--text-gray);
    font-weight: 500;
    line-height: 1.4;
    cursor: pointer;
    margin: 0;
    font-size: 0.9rem;
}

.consent-checkbox label strong {
    color: var(--primary-blue);
}

.btn-vincular {
    background: var(--gradient-accent);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 1rem;
    width: 100%;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: var(--shadow-md);
}

.btn-vincular::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: var(--transition);
}

.btn-vincular:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: white;
}

.btn-vincular:hover:not(:disabled)::before {
    left: 100%;
}

.btn-vincular:active:not(:disabled) {
    transform: translateY(-1px);
}

.btn-vincular:disabled {
    background: linear-gradient(135deg, var(--border-gray) 0%, #a0aec0 100%);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-vincular:disabled::before {
    display: none;
}

.btn-pulse {
    animation: pulse-button 2s infinite;
}

@keyframes pulse-button {
    0% {
        box-shadow: 0 0 0 0 rgba(56, 161, 105, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(56, 161, 105, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(56, 161, 105, 0);
    }
}

.loading-section {
    display: none;
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: var(--border-radius);
    position: relative;
    overflow: hidden;
}

.loading-section.active {
    display: block;
    animation: fadeInUp 0.5s ease-out;
}

.loading-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 30%, rgba(49, 130, 206, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(56, 161, 105, 0.05) 0%, transparent 50%);
    animation: rotate 10s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-content {
    position: relative;
    z-index: 2;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--border-gray);
    border-top: 4px solid var(--accent-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
    box-shadow: var(--shadow-sm);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-title {
    color: var(--primary-blue);
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: -0.01em;
}

.loading-subtitle {
    color: var(--text-gray);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
    line-height: 1.4;
}

.loading-steps {
    text-align: left;
    max-width: 350px;
    margin: 0 auto;
}

.loading-step {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    background: rgba(49, 130, 206, 0.05);
    transition: var(--transition);
}

.loading-step.active {
    background: rgba(49, 130, 206, 0.1);
    border-left: 3px solid var(--accent-blue);
}

.loading-step.completed {
    background: rgba(56, 161, 105, 0.1);
    border-left: 3px solid var(--success-green);
}

.loading-step .step-icon {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    background: var(--border-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    color: white;
    font-weight: 700;
    font-size: 0.75rem;
    transition: var(--transition);
}

.loading-step.active .step-icon {
    background: var(--accent-blue);
    animation: pulse-icon 2s infinite;
}

.loading-step.completed .step-icon {
    background: var(--success-green);
}

@keyframes pulse-icon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.loading-step .step-text {
    color: var(--text-gray);
    font-weight: 500;
    font-size: 0.85rem;
}

.loading-step.active .step-text {
    color: var(--primary-blue);
    font-weight: 600;
}

.loading-step.completed .step-text {
    color: var(--success-green);
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: var(--border-gray);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 1rem;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: var(--gradient-accent);
    border-radius: 2px;
    width: 0%;
    transition: width 0.5s ease;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer-progress 2s infinite;
}

@keyframes shimmer-progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.alert {
    border: none;
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid;
    position: relative;
    overflow: hidden;
}

.alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.alert-success {
    background: rgba(56, 161, 105, 0.1);
    color: var(--success-green);
    border-left-color: var(--success-green);
}

.alert-danger {
    background: rgba(229, 62, 62, 0.1);
    color: var(--danger-red);
    border-left-color: var(--danger-red);
}

.alert-info {
    background: rgba(49, 130, 206, 0.1);
    color: var(--primary-blue);
    border-left-color: var(--accent-blue);
}

.alert-warning {
    background: rgba(237, 137, 54, 0.1);
    color: var(--warning-orange);
    border-left-color: var(--warning-orange);
}

.alert i {
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.alert .alert-content {
    position: relative;
    z-index: 2;
}

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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.slide-in-up {
    animation: fadeInUp 0.8s ease-out;
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

.form-control.is-invalid {
    border-color: var(--danger-red);
    background: rgba(229, 62, 62, 0.05);
    animation: shake 0.5s ease-in-out;
}

.form-control.is-valid {
    border-color: var(--success-green);
    background: rgba(56, 161, 105, 0.05);
}

.form-group.has-success .form-control {
    border-color: var(--success-green);
    box-shadow: 0 0 0 0.15rem rgba(56, 161, 105, 0.1);
}

.form-group.has-error .form-control {
    border-color: var(--danger-red);
    box-shadow: 0 0 0 0.15rem rgba(229, 62, 62, 0.1);
}

.success-checkmark {
    color: var(--success-green);
    font-size: 1rem;
    margin-left: 0.5rem;
    animation: checkmark-appear 0.3s ease-out;
}

@keyframes checkmark-appear {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    .vincular-container {
        padding: 0.5rem;
    }

    .vincular-card {
        margin: 1rem auto;
    }

    .vincular-header {
        padding: 1.5rem;
    }

    .vincular-header h1 {
        font-size: 1.5rem;
    }

    .vincular-header .main-icon {
        font-size: 2rem;
    }

    .vincular-body {
        padding: 1.5rem;
    }

    .consent-section {
        padding: 1rem;
    }

    .consent-header {
        flex-direction: column;
        text-align: center;
    }

    .consent-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }

    .consent-title {
        font-size: 1rem;
    }

    .data-list {
        padding: 1rem;
    }

    .loading-section {
        padding: 1.5rem;
    }

    .loading-title {
        font-size: 1.2rem;
    }

    .loading-subtitle {
        font-size: 0.85rem;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
    }

    .consent-text {
        font-size: 0.85rem;
    }

    .data-protection-info .text {
        font-size: 0.8rem;
    }

    .data-items li {
        font-size: 0.8rem;
    }

    .consent-checkbox label {
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .vincular-card {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .vincular-header {
        padding: 1rem;
    }

    .vincular-header h1 {
        font-size: 1.3rem;
    }

    .vincular-body {
        padding: 1rem;
    }

    .form-control {
        padding: 0.75rem 0.75rem 0.75rem 2.5rem;
        font-size: 0.9rem;
    }

    .form-floating > label {
        padding-left: 2.5rem;
        font-size: 0.85rem;
    }

    .input-icon {
        left: 0.75rem;
        font-size: 1rem;
    }

    .consent-section {
        padding: 0.75rem;
    }

    .data-list {
        padding: 0.75rem;
    }

    .btn-vincular {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }

    .loading-steps {
        max-width: 100%;
    }

    .loading-step {
        padding: 0.5rem;
    }

    .loading-step .step-icon {
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
    }

    .loading-step .step-text {
        font-size: 0.8rem;
    }
}
</style>

<div class="vincular-container">
    <div class="container-fluid">
        <div class="vincular-card slide-in-up">
            <!-- HEADER -->
            <div class="vincular-header">
                <div class="icon-wrapper">
                    <i class="fas fa-university main-icon"></i>
                </div>
                <h1>Vincularme a la UPT</h1>
                <p>Conecta tu cuenta con la Universidad Privada de Tacna</p>
            </div>

            <!-- BODY PRINCIPAL -->
            <div class="vincular-body">
                <!-- ALERTAS -->
                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <div class="alert-content">
                            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'danger' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                            <?= htmlspecialchars($mensaje) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO PRINCIPAL -->
                <div class="form-section" id="formSection">
                    <!-- SECCIÓN DE CONSENTIMIENTO -->
                    <div class="consent-section">
                        <div class="consent-header">
                            <div class="consent-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="consent-title">Consentimiento de Uso de Datos UPT</h5>
                        </div>

                        <div class="consent-content">
                            <p class="consent-text">
                                Al continuar con el proceso de vinculación, <strong>autorizo expresamente</strong> 
                                el acceso y uso de mis datos académicos almacenados en el sistema de intranet 
                                de la <strong>Universidad Privada de Tacna (UPT)</strong> para los fines 
                                específicos del sistema de mentoría académica.
                            </p>

                            <div class="data-protection-info">
                                <i class="fas fa-lock icon"></i>
                                <div class="text">
                                    <strong>Protección Garantizada:</strong> Todos tus datos serán tratados 
                                    con absoluta confidencialidad y utilizados únicamente para mejorar 
                                    tu experiencia académica.
                                </div>
                            </div>

                            <div class="data-list">
                                <h6>
                                    <i class="fas fa-database"></i>
                                    Datos que serán extraídos del sistema UPT:
                                </h6>
                                <ul class="data-items">
                                    <li>
                                        <i class="fas fa-user"></i>
                                        <span><strong>Información Personal:</strong> Nombres y apellidos completos</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-id-badge"></i>
                                        <span><strong>Código de Estudiante:</strong> Identificador único institucional</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-book"></i>
                                        <span><strong>Cursos Matriculados:</strong> Materias registradas en el semestre actual</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-chart-line"></i>
                                        <span><strong>Notas Académicas:</strong> Calificaciones y rendimiento académico</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="consent-checkbox" id="consentContainer">
                                <input type="checkbox" id="consentimiento" name="consentimiento" required>
                                <label for="consentimiento">
                                    Acepto que mis datos académicos sean extraídos del intranet UPT 
                                    y procesados de manera segura. Entiendo que estos datos serán 
                                    utilizados exclusivamente para <strong>fines educativos y de mentoría</strong>, 
                                    y que están protegidos bajo las políticas de privacidad institucional.
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- FORMULARIO DE CREDENCIALES -->
                    <form method="POST" action="<?= BASE_URL ?>/index.php?accion=buscar_estudiante" id="codigoForm">
                        <div class="form-group" id="codigoGroup">
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="codigoEstudiante" 
                                       name="codigo_estudiante"
                                       placeholder="Código de estudiante"
                                       pattern="[0-9]{8,12}"
                                       maxlength="12"
                                       value="<?= htmlspecialchars($_POST['codigo_estudiante'] ?? '') ?>"
                                       required>
                                <label for="codigoEstudiante">Código de Estudiante Intranet</label>
                                <i class="fas fa-id-card input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group" id="contraGroup">
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="contraUPT" 
                                       name="contra_upt"
                                       placeholder="Contraseña UPT"
                                       required>
                                <label for="contraUPT">Contraseña del Intranet UPT</label>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-vincular" id="btnBuscar" disabled>
                            <i class="fas fa-link me-2"></i>
                            Vincularme a la UPT
                        </button>
                    </form>
                </div>

                <!-- SECCIÓN DE CARGA -->
                <div class="loading-section" id="loadingSection">
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <h3 class="loading-title">Procesando Vinculación</h3>
                        <p class="loading-subtitle">
                            Estamos validando tus credenciales y extrayendo tu información académica del sistema UPT.
                        </p>
                        
                        <div class="loading-steps">
                            <div class="loading-step" id="step1">
                                <div class="step-icon">1</div>
                                <div class="step-text">Validando credenciales UPT</div>
                            </div>
                            <div class="loading-step" id="step2">
                                <div class="step-icon">2</div>
                                <div class="step-text">Extrayendo datos académicos</div>
                            </div>
                            <div class="loading-step" id="step3">
                                <div class="step-icon">3</div>
                                <div class="step-text">Registrando cursos y notas</div>
                            </div>
                            <div class="loading-step" id="step4">
                                <div class="step-icon">4</div>
                                <div class="step-text">Configurando tu perfil</div>
                            </div>
                            <div class="loading-step" id="step5">
                                <div class="step-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="step-text">Finalizando vinculación</div>
                            </div>
                        </div>

                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consentCheckbox = document.getElementById('consentimiento');
    const consentContainer = document.getElementById('consentContainer');
    const btnBuscar = document.getElementById('btnBuscar');
    const codigoForm = document.getElementById('codigoForm');
    const formSection = document.getElementById('formSection');
    const loadingSection = document.getElementById('loadingSection');
    const codigoInput = document.getElementById('codigoEstudiante');
    const contraInput = document.getElementById('contraUPT');
    const codigoGroup = document.getElementById('codigoGroup');
    const contraGroup = document.getElementById('contraGroup');

    // Controlar habilitación del botón
    function updateButtonState() {
        const codigoValido = codigoInput.value.trim().length >= 8;
        const contraValida = contraInput.value.trim().length >= 1;
        const consentimientoAceptado = consentCheckbox.checked;
        
        btnBuscar.disabled = !(codigoValido && contraValida && consentimientoAceptado);
        
        if (consentimientoAceptado && codigoValido && contraValida) {
            btnBuscar.classList.add('btn-pulse');
        } else {
            btnBuscar.classList.remove('btn-pulse');
        }
    }

    // Event listeners
    consentCheckbox.addEventListener('change', function() {
        updateButtonState();
        
        if (this.checked) {
            consentContainer.classList.add('checked');
            setTimeout(() => codigoInput.focus(), 200);
        } else {
            consentContainer.classList.remove('checked');
        }
    });

    codigoInput.addEventListener('input', function() {
        const codigo = this.value.replace(/\D/g, '');
        this.value = codigo;
        
        if (codigo.length === 0) {
            codigoGroup.classList.remove('has-success', 'has-error');
            this.classList.remove('is-valid', 'is-invalid');
        } else if (codigo.length >= 8 && codigo.length <= 12) {
            codigoGroup.classList.add('has-success');
            codigoGroup.classList.remove('has-error');
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        } else {
            codigoGroup.classList.add('has-error');
            codigoGroup.classList.remove('has-success');
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        }
        
        updateButtonState();
    });

    contraInput.addEventListener('input', function() {
        const contra = this.value.trim();
        
        if (contra.length === 0) {
            contraGroup.classList.remove('has-success', 'has-error');
            this.classList.remove('is-valid', 'is-invalid');
        } else if (contra.length >= 1) {
            contraGroup.classList.add('has-success');
            contraGroup.classList.remove('has-error');
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
        
        updateButtonState();
    });

    // Validación del formulario
    codigoForm.addEventListener('submit', function(e) {
        const codigo = codigoInput.value.trim();
        const contra = contraInput.value.trim();
        
        if (!consentCheckbox.checked) {
            e.preventDefault();
            showAlert('Debes aceptar el consentimiento de uso de datos para continuar', 'warning');
            consentCheckbox.focus();
            return;
        }
        
        if (!codigo || !contra) {
            e.preventDefault();
            showAlert('Por favor, completa todos los campos', 'danger');
            return;
        }

        if (!/^[0-9]{8,12}$/.test(codigo)) {
            e.preventDefault();
            showAlert('El código debe tener entre 8 y 12 dígitos', 'danger');
            codigoInput.focus();
            return;
        }

        startLoadingProcess();
    });

    function startLoadingProcess() {
        formSection.classList.add('hidden');
        setTimeout(() => {
            formSection.style.display = 'none';
            loadingSection.classList.add('active');
            simulateLoadingSteps();
        }, 300);
    }

    function simulateLoadingSteps() {
        const steps = [
            { id: 'step1', delay: 300, progress: 20 },
            { id: 'step2', delay: 800, progress: 40 },
            { id: 'step3', delay: 1300, progress: 60 },
            { id: 'step4', delay: 1800, progress: 80 },
            { id: 'step5', delay: 2300, progress: 100 }
        ];

        steps.forEach((step, index) => {
            setTimeout(() => {
                const currentStep = document.getElementById(step.id);
                currentStep.classList.add('active');
                
                if (index > 0) {
                    const prevStep = document.getElementById(steps[index - 1].id);
                    prevStep.classList.remove('active');
                    prevStep.classList.add('completed');
                }
                
                const progressFill = document.getElementById('progressFill');
                progressFill.style.width = step.progress + '%';
                
                if (index === steps.length - 1) {
                    setTimeout(() => {
                        currentStep.classList.remove('active');
                        currentStep.classList.add('completed');
                    }, 500);
                }
            }, step.delay);
        });
    }

    function showAlert(message, type = 'danger') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'exclamation-triangle')}"></i>
                ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const targetContainer = document.querySelector('.vincular-body');
        
        if (targetContainer) {
            targetContainer.insertBefore(alertDiv, targetContainer.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }
    }

    // Inicializar
    updateButtonState();
    
    // Auto-remover alertas existentes
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 4000);
        }
    });

    // Prevenir múltiples envíos
    let formSubmitted = false;
    codigoForm.addEventListener('submit', function(e) {
        if (formSubmitted) {
            e.preventDefault();
            return;
        }
        formSubmitted = true;
        btnBuscar.disabled = true;
        btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
    });
});
</script>

<?php require_once BASE_PATH . '/views/components/footer.php'; ?>