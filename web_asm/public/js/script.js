window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

window.addEventListener('load', function() {
    document.querySelectorAll('.loading-overlay').forEach(overlay => {
        overlay.remove();
    });
});
function confirmarCerrarSesion(e, logoutUrl) {
    e.preventDefault();

    if (confirm("¿Estás seguro de que deseas cerrar sesión?")) {
        const overlay = document.createElement("div");
        overlay.style.position = "fixed";
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.width = "100vw";
        overlay.style.height = "100vh";
        overlay.style.backgroundColor = "rgba(0,0,0,0.6)";
        overlay.style.display = "flex";
        overlay.style.justifyContent = "center";
        overlay.style.alignItems = "center";
        overlay.style.zIndex = 9999;

        const spinner = document.createElement("div");
        spinner.innerHTML = `
            <div style="color: white; text-align: center;">
                <div class="loader"></div>
                <p style="margin-top: 10px;">Cerrando sesión...</p>
            </div>
        `;

        overlay.appendChild(spinner);
        document.body.appendChild(overlay);

        setTimeout(() => {
            window.location.href = logoutUrl;
        }, 2000);
    }
}
let TOKEN_API_DNI = "";
let codigoEnviado = "";
const BASE_URL = window.location.origin;

fetch("config.json")
    .then(response => {
        if (!response.ok) throw new Error("No se pudo cargar config.json");
        return response.json();
    })
    .then(config => {
        TOKEN_API_DNI = config.apitoken;
    })
    .catch(err => {
        console.error("Error cargando config.json:", err);
    });

const form = document.getElementById("formRegistro");
const btnRegistrar = document.getElementById("btnRegistrar");

if (form) {
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const correo = document.getElementById("email").value;
        if (!correo) {
            alert("El correo es obligatorio.");
            return;
        }

        mostrarCarga(true);

        const formData = new FormData();
        formData.append("email", correo);
        console.log("📤 Enviando solicitud de código...");

        fetch(`${BASE_URL}/auth/notify.php`, {
            method: "POST",
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`);
            return res.json();
        })
        .then(data => {
            if (data.success && data.codigo) {
                codigoEnviado = data.codigo;
                document.getElementById("modalCodigo").style.display = "block";
            } else if (!data.success) {
                alert("❌ " + (data.message || "No se pudo enviar el código."));
            } else {
                alert("❌ El servidor no devolvió el código.");
            }
        })
        .catch(err => {
            console.error("❌ Error al contactar al servidor:", err);
            alert("❌ No se pudo contactar con el servidor. Revisa la consola.");
        })
        .finally(() => mostrarCarga(false));
    });
}

function verificarDNI(input) {
    input.value = input.value.replace(/\D/g, '');

    if (input.value.length === 8) {
        validarDNI(input.value);
    } else {
        document.getElementById("nombre").value = "";
        document.getElementById("apellido").value = "";
    }
}

function validarDNI(dni) {
    if (!/^\d{8}$/.test(dni)) {
        mostrarError("DNI debe contener exactamente 8 dígitos");
        return;
    }

    mostrarCarga(true);
    
    if (window.dniController) {
        window.dniController.abort();
    }
    window.dniController = new AbortController();

    fetch(`index.php?accion=consulta_dni&dni=${dni}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        signal: window.dniController.signal,
        timeout: 10000
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta no es JSON válido');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            rellenarCamposDNI(data);
        } else {
            limpiarDNI();
        }
    })
    .catch(error => {
        if (error.name === 'AbortError') {
            console.log('Petición cancelada');
            return;
        }
        
        console.error('Error consultando DNI:', error);
    })
    .finally(() => {
        mostrarCarga(false);
        window.dniController = null;
    });
}
function mostrarError(mensaje) {
    let errorElement = document.getElementById('dni-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.id = 'dni-error';
        errorElement.className = 'error-message';
        
        const dniField = document.getElementById('dni');
        if (dniField && dniField.parentNode) {
            dniField.parentNode.insertBefore(errorElement, dniField.nextSibling);
        }
    }
    
    errorElement.textContent = mensaje;
    errorElement.style.display = 'block';
    setTimeout(() => ocultarError(), 5000);
}

function ocultarError() {
    const errorElement = document.getElementById('dni-error');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}
function rellenarCamposDNI(data) {
    const nombreField = document.getElementById("nombre");
    const apellidoField = document.getElementById("apellido");
    const emailField = document.getElementById("email");
    
    if (nombreField) {
        nombreField.value = data.nombres || '';
    }
    
    if (apellidoField) {
        const apellidos = [
            data.apellidoPaterno || '',
            data.apellidoMaterno || ''
        ].filter(Boolean).join(' ');
        apellidoField.value = apellidos;
    }
    
    if (emailField) {
        emailField.focus();
    }
}
function limpiarDNI(msg) {
    alert(msg);
    document.getElementById("dni").value = "";
    document.getElementById("nombre").value = "";
    document.getElementById("apellido").value = "";
    document.getElementById("dni").focus();
}

function validarCorreo(input) {
    const correo = input.value;
    const regex = /^[\w\-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
    document.getElementById("correoError").textContent = regex.test(correo) ? "" : "Correo no válido";
}

function validarPassword(pass) {
    const fuerte = /(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#\$%\^&\*])/;
    document.getElementById("passError").textContent = fuerte.test(pass)
        ? ""
        : "Contraseña insegura. Usa mayúsculas, números y símbolos.";
    compararPassword();
}

function compararPassword() {
    const pass = document.getElementById("password").value;
    const confirm = document.getElementById("confirmar").value;
    const valido = pass && confirm && pass === confirm;
    document.getElementById("passError").textContent = !valido ? "Las contraseñas no coinciden." : "";
    document.getElementById("btnRegistrar").disabled = !valido;
}

function mostrarCarga(estado) {
    if (estado) {
        const carga = document.createElement("div");
        carga.id = "cargandoOverlay";
        carga.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:9999;";
        carga.innerHTML = '<div style="color:white;">Validando...<div class="loader"></div></div>';
        document.body.appendChild(carga);
    } else {
        const carga = document.getElementById("cargandoOverlay");
        if (carga) carga.remove();
    }
}

function verificarCodigo() {
    const input = document.getElementById("codigoVerificacion").value;

    if (input !== codigoEnviado) {
        alert("⚠️ Código incorrecto.");
        return;
    }

    const form = document.getElementById("formRegistro");
    const formData = new FormData(form);

    console.log("📤 Enviando datos del formulario:");
    for (let pair of formData.entries()) {
        console.log(`🔹 ${pair[0]}: ${pair[1]}`);
    }

    mostrarCarga(true);
    fetch(`${BASE_URL}/index.php?accion=procesar_registro`, {
        method: "POST",
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (err) {
            throw new Error("Respuesta inválida del servidor: " + text);
        }
    })
    .then(data => {
        if (data.success) {
            alert("✅ Registro completado.");
            window.location.href = `${BASE_URL}/index.php`;
        } else {
            alert("❌ " + data.message);
        }
    })
    .catch((err) => {
        console.error("❌ Error al registrar:", err);
        alert("❌ Error al registrar: " + err.message);
    })
    .finally(() => mostrarCarga(false));
}
function cerrarModal() {
    const modal = document.getElementById('modalCodigo');
    modal.classList.remove('show');
}
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.header');
    const navLinks = document.querySelectorAll('.nav-link');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    const currentUrl = window.location.href;
    navLinks.forEach(link => {
        if (currentUrl.includes(link.href) && link.href !== window.location.origin + '/') {
            link.setAttribute('data-active', 'true');
        }
    });
    
    // Cerrar menú móvil al hacer clic en un enlace
    const mobileLinks = document.querySelectorAll('.mobile-nav-link');
    const mobileMenu = document.getElementById('mobileMenu');
    const toggleButton = document.querySelector('.navbar-toggler');
    
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('show');
            toggleButton.setAttribute('aria-expanded', 'false');
        });
    });
    
    // Efecto parallax sutil en el header
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        header.style.transform = `translateY(${rate}px)`;
    });
});


//ADMIN - Script Completo Unificado
const dashboardConfig = {
    baseUrl: window.BASE_URL || window.location.origin,
    refreshInterval: 30000,
    animationDelay: 100
};

// ===== NAVEGACIÓN PRINCIPAL =====
function navegarA(accion) {
    switch(accion) {
        case 'anadir_alumnos':
            openAddStudentModal();
            break;
        case 'modificar_usuarios':
            openManageUsersModal();
            break;
        case 'modificar_clases':
        case 'reportes':
            const urls = {
                'modificar_clases': `${dashboardConfig.baseUrl}/index.php?accion=modificar_clases`,
                'reportes': `${dashboardConfig.baseUrl}/index.php?accion=reportes`
            };
            showLoadingState();
            window.location.href = urls[accion];
            break;
        default:
            console.log('Acción no definida:', accion);
    }
}

function showUsersLoading(show) {
    const loading = document.getElementById('usersLoading');
    if (loading) {
        loading.style.display = show ? 'flex' : 'none';
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}


function animateCounter(element) {
    const target = parseInt(element.dataset.target);
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    element.classList.add('loading');
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
            element.classList.remove('loading');
        }
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}


function crearSesion() {
    showNotification('Redirigiendo a crear nueva sesión...', 'info');
    setTimeout(() => {
        window.location.href = `${dashboardConfig.baseUrl}/index.php?accion=nueva_sesion`;
    }, 1000);
}

function verPendientes() {
    showNotification('Cargando solicitudes pendientes...', 'info');
    setTimeout(() => {
        window.location.href = `${dashboardConfig.baseUrl}/index.php?accion=pendientes`;
    }, 1000);
}

function openQuickActions() {
    const menu = createQuickActionsMenu();
    document.body.appendChild(menu);
    setTimeout(() => menu.classList.add('show'), 10);
}

function createQuickActionsMenu() {
    const menu = document.createElement('div');
    menu.className = 'quick-actions-menu';
    menu.innerHTML = `
        <div class="menu-content">
            <div class="menu-header">
                <h3>Acciones Rápidas</h3>
                <button class="menu-close" onclick="this.closest('.quick-actions-menu').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="menu-grid">
                <button class="menu-item" onclick="navegarA('anadir_alumnos'); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-user-plus"></i>
                    <span>Añadir Estudiante</span>
                </button>
                <button class="menu-item" onclick="navegarA('modificar_usuarios'); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-users-cog"></i>
                    <span>Gestionar Usuarios</span>
                </button>
                <button class="menu-item" onclick="navegarA('modificar_clases'); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Programar Clase</span>
                </button>
                <button class="menu-item" onclick="navegarA('reportes'); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-chart-bar"></i>
                    <span>Ver Reportes</span>
                </button>
                <button class="menu-item" onclick="buscarUsuario(); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-search"></i>
                    <span>Buscar Usuario</span>
                </button>
                <button class="menu-item" onclick="refreshDashboard(); this.closest('.quick-actions-menu').remove();">
                    <i class="fas fa-sync-alt"></i>
                    <span>Actualizar Dashboard</span>
                </button>
            </div>
        </div>
    `;
    
    menu.addEventListener('click', (e) => {
        if (e.target === menu) {
            menu.remove();
        }
    });
    
    return menu;
}

// ===== FUNCIONES DE ESTADO Y NOTIFICACIONES =====
function showLoadingState() {
    const existingOverlay = document.querySelector('.loading-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Cargando...</p>
        </div>
    `;
    document.body.appendChild(loader);
    
    setTimeout(() => {
        if (loader && loader.parentNode) {
            loader.remove();
        }
    }, 5000);
}

function showNotification(message, type = 'info', duration = 4000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.closest('.notification').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

function handleConnectionError() {
    showNotification('Error de conexión. Verificando estado del servidor...', 'error', 6000);
    
    setTimeout(() => {
        fetch(dashboardConfig.baseUrl + '/ping.php')
            .then(response => {
                if (response.ok) {
                    showNotification('Conexión restaurada', 'success');
                } else {
                    showNotification('Servidor no disponible. Intenta más tarde.', 'error');
                }
            })
            .catch(() => {
                showNotification('Sin conexión a internet', 'error');
            });
    }, 5000);
}

function getMetricKey(chartType) {
    const mapping = {
        'users': 'total_usuarios',
        'students': 'estudiantes_activos', 
        'teachers': 'docentes_mentores',
        'sessions': 'sesiones_programadas'
    };
    return mapping[chartType];
}

function getChartColor(type) {
    const colors = {
        'users': '#8b5cf6',
        'students': '#10b981',
        'teachers': '#3b82f6',
        'sessions': '#f59e0b'
    };
    return colors[type] || '#6b7280';
}

// ===== FUNCIONES DE TIEMPO =====
function updateTimeAgo() {
    const timeElements = document.querySelectorAll('.timeline-time');
    timeElements.forEach(element => {
        const originalTime = element.dataset.time;
        if (originalTime) {
            element.textContent = formatTimeAgo(originalTime);
        }
    });
}

function formatTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return `hace ${diff} seg`;
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)} hora${Math.floor(diff / 3600) > 1 ? 's' : ''}`;
    return `hace ${Math.floor(diff / 86400)} día${Math.floor(diff / 86400) > 1 ? 's' : ''}`;
}

// ===== FUNCIONES ADICIONALES =====
function initializeTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = event.target.dataset.tooltip;
    document.body.appendChild(tooltip);
    
    const rect = event.target.getBoundingClientRect();
    tooltip.style.position = 'absolute';
    tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
    tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;
    tooltip.style.zIndex = '1003';
    tooltip.style.background = '#1f2937';
    tooltip.style.color = 'white';
    tooltip.style.padding = '4px 8px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '0.875rem';
    tooltip.style.whiteSpace = 'nowrap';
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

function checkConnectivity() {
    return fetch(dashboardConfig.baseUrl + '/ping.php', {
        method: 'HEAD',
        cache: 'no-cache'
    }).then(response => response.ok).catch(() => false);
}

function saveDashboardState() {
    const state = {
        lastRefresh: Date.now(),
        metricsData: window.dashboardData?.metricas || null,
        userPreferences: {
            autoRefresh: true,
            notifications: true
        }
    };
    
    try {
        localStorage.setItem('dashboardState', JSON.stringify(state));
    } catch (e) {
        console.warn('No se pudo guardar el estado del dashboard:', e);
    }
}

function loadDashboardState() {
    try {
        const state = localStorage.getItem('dashboardState');
        return state ? JSON.parse(state) : null;
    } catch (e) {
        console.warn('No se pudo cargar el estado del dashboard:', e);
        return null;
    }
}

function setupPerformanceObservers() {
    const lazyElements = document.querySelectorAll('.metric-card, .action-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
            }
        });
    }, { threshold: 0.1 });
    
    lazyElements.forEach(el => observer.observe(el));
}

// ===== CONFIGURACIÓN PARA DATOS PHP =====
function setDashboardData(data) {
    window.dashboardData = data;
    
    if (document.readyState === 'complete') {
        setTimeout(() => {
            createRealMiniCharts();
        }, 100);
    }
}

// ===== EVENTOS PRINCIPALES =====
document.addEventListener('DOMContentLoaded', function() {
    // Observer para animaciones de contadores
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counters = entry.target.querySelectorAll('.metric-value[data-target]');
                counters.forEach(counter => {
                    if (!counter.classList.contains('animated')) {
                        counter.classList.add('animated');
                        animateCounter(counter);
                    }
                });
            }
        });
    }, { threshold: 0.3 });
    
    document.querySelectorAll('.metrics-grid').forEach(section => {
        observer.observe(section);
    });

    // Carga inicial forzada de contadores
    setTimeout(() => {
        document.querySelectorAll('.metric-value[data-target]').forEach(counter => {
            animateCounter(counter);
        });
    }, 500);

    // Crear mini gráficos
    setTimeout(() => {
        if (window.dashboardData && window.dashboardData.metricas) {
            createRealMiniCharts();
        } else {
            console.log('Datos del dashboard no disponibles para gráficos');
        }
    }, 800);
    
    // Actualizar tiempo transcurrido cada minuto
    setInterval(updateTimeAgo, 60000);
    
    // Configurar timestamps para timeline
    document.querySelectorAll('.timeline-time').forEach(element => {
        if (!element.dataset.time) {
            element.dataset.time = new Date().toISOString();
        }
    });

    // Atajos de teclado
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    navegarA('anadir_alumnos');
                    break;
                case '2':
                    e.preventDefault();
                    navegarA('modificar_usuarios');
                    break;
                case '3':
                    e.preventDefault();
                    navegarA('modificar_clases');
                    break;
                case '4':
                    e.preventDefault();
                    navegarA('reportes');
                    break;
                case 'k':
                    e.preventDefault();
                    buscarUsuario();
                    break;
                case 'r':
                    e.preventDefault();
                    refreshDashboard();
                    break;
            }
        }
        
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal-overlay, .quick-actions-menu');
            if (modal) {
                modal.remove();
            }
        }
    });
    
    // Estado de conexión
    window.addEventListener('offline', () => {
        showNotification('Sin conexión a internet', 'error');
    });
    
    window.addEventListener('online', () => {
        showNotification('Conexión restaurada', 'success');
    });
    
    // Notificación de bienvenida
    setTimeout(() => {
        if (window.dashboardData) {
            showNotification('Dashboard cargado con datos reales. ¡Bienvenido!', 'success');
        } else {
            showNotification('Dashboard cargado correctamente. ¡Bienvenido!', 'success');
        }
    }, 1000);

    // Inicializar funciones adicionales
    initializeTooltips();
    
    if ('IntersectionObserver' in window) {
        setupPerformanceObservers();
    }
    
    window.addEventListener('beforeunload', saveDashboardState);
});