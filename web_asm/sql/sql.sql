CREATE DATABASE IF NOT EXISTS `ams_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ams_system`;

-- Eliminar triggers existentes si existen
DROP TRIGGER IF EXISTS `tr_marcar_necesita_mentoria_insert`;
DROP TRIGGER IF EXISTS `tr_marcar_necesita_mentoria_update`;
DROP TRIGGER IF EXISTS `tr_actualizar_estudiantes_inscritos_insert`;
DROP TRIGGER IF EXISTS `tr_actualizar_estudiantes_inscritos_update`;
DROP TRIGGER IF EXISTS `tr_actualizar_estudiantes_inscritos_delete`;

-- Eliminar procedimientos existentes si existen
DROP PROCEDURE IF EXISTS `sp_registrar_estudiante`;
DROP PROCEDURE IF EXISTS `sp_crear_o_inscribir_clase`;

-- Eliminar vistas existentes si existen
DROP VIEW IF EXISTS `v_usuarios`;

CREATE TABLE IF NOT EXISTS `datos_personales` (
    `id_datos_personales` INT AUTO_INCREMENT PRIMARY KEY,
    `nombres` VARCHAR(100) NOT NULL,
    `apellidos` VARCHAR(100) NOT NULL,
    `telefono` VARCHAR(15) NULL,
    `direccion` VARCHAR(255) NULL,
    `fecha_nacimiento` DATE NULL,
    `genero` ENUM('M', 'F', 'Otro') NULL,
    `foto_perfil` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_nombres_apellidos` (`nombres`, `apellidos`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `id_datos_personales` INT NOT NULL,
    `ultima_actividad` TIMESTAMP NULL,
    `email_verificado` BOOLEAN DEFAULT FALSE,
    `activo` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_datos_personales`) REFERENCES `datos_personales`(`id_datos_personales`) ON DELETE CASCADE,
    INDEX `idx_email` (`email`),
    INDEX `idx_activo` (`activo`),
    INDEX `idx_email_busqueda` (`email`, `activo`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `roles` (
    `id_rol` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(50) UNIQUE NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `activo` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO `roles` (`nombre`, `descripcion`) VALUES
('usuario', 'Usuario general sin permisos especiales'),
('estudiante', 'Usuario que puede solicitar mentoría'),
('docente', 'Usuario que puede dar mentoría y clases'),
('administrador', 'Usuario con permisos completos del sistema');

CREATE TABLE IF NOT EXISTS `usuario_roles` (
    `id_usuario_rol` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `id_rol` INT NOT NULL,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `activo` BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    FOREIGN KEY (`id_rol`) REFERENCES `roles`(`id_rol`) ON DELETE CASCADE,
    UNIQUE KEY `unique_usuario_rol` (`id_usuario`, `id_rol`),
    INDEX `idx_id_usuario` (`id_usuario`),
    INDEX `idx_id_rol` (`id_rol`),
    INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `estudiantes` (
    `id_estudiante` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT UNIQUE NOT NULL,
    `codigo_estudiante` VARCHAR(12) UNIQUE NOT NULL,
    `carrera` VARCHAR(100) NOT NULL,
    `estado_academico` TINYINT NOT NULL COMMENT '1=Activo, 2=Inactivo, 3=Graduado, 4=Suspendido',
    `promedio_general` DECIMAL(4,2) NULL,
    `puede_solicitar_mentoria` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    INDEX `idx_codigo_estudiante` (`codigo_estudiante`),
    INDEX `idx_carrera` (`carrera`),
    INDEX `idx_estado_academico` (`estado_academico`),
    INDEX `idx_puede_solicitar` (`puede_solicitar_mentoria`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mentores` (
    `id_mentor` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT UNIQUE NOT NULL,
    `especialidades` TEXT NULL,
    `puede_tomar_clase` BOOLEAN DEFAULT TRUE,
    `calificacion_promedio` DECIMAL(3,2) DEFAULT 0.00,
    `total_clases_dadas` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    INDEX `idx_puede_tomar_clase` (`puede_tomar_clase`),
    INDEX `idx_calificacion` (`calificacion_promedio`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `administradores` (
    `id_administrador` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT UNIQUE NOT NULL,
    `nivel_acceso` TINYINT DEFAULT 1 COMMENT '1=Básico, 2=Avanzado, 3=Super Admin',
    `permisos` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    INDEX `idx_nivel_acceso` (`nivel_acceso`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cursos` (
    `id_curso` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_curso` VARCHAR(10) UNIQUE NOT NULL,
    `nombre` VARCHAR(200) NOT NULL,
    `creditos` TINYINT NULL,
    `activo` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_codigo_curso` (`codigo_curso`),
    INDEX `idx_nombre` (`nombre`),
    INDEX `idx_activo` (`activo`),
    INDEX `idx_codigo_busqueda` (`codigo_curso`, `activo`),
    INDEX `idx_nombre_busqueda` (`nombre`, `activo`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `estudiante_cursos` (
    `id_estudiante_curso` INT AUTO_INCREMENT PRIMARY KEY,
    `id_estudiante` INT NOT NULL,
    `id_curso` INT NOT NULL,
    `ponderado` DECIMAL(4,2) NOT NULL,
    `estado_curso` VARCHAR(50) NOT NULL,
    `necesita_mentoria` BOOLEAN DEFAULT FALSE,
    `fecha_ultima_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes`(`id_estudiante`) ON DELETE CASCADE,
    FOREIGN KEY (`id_curso`) REFERENCES `cursos`(`id_curso`) ON DELETE CASCADE,
    UNIQUE KEY `unique_estudiante_curso` (`id_estudiante`, `id_curso`),
    INDEX `idx_necesita_mentoria` (`necesita_mentoria`),
    INDEX `idx_ponderado` (`ponderado`),
    INDEX `idx_estado_curso` (`estado_curso`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `clases` (
    `id_clase` INT AUTO_INCREMENT PRIMARY KEY,
    `id_curso` INT NOT NULL,
    `id_mentor` INT NULL,
    `titulo` VARCHAR(200) NOT NULL,
    `descripcion` TEXT NULL,
    `capacidad_maxima` TINYINT DEFAULT 20,
    `estudiantes_inscritos` TINYINT DEFAULT 0,
    `estado` TINYINT DEFAULT 1 COMMENT '1=PENDIENTE, 2=ACTIVO, 3=EN_PROCESO, 4=FINALIZADO, 5=CERRADA',
    `fecha_programada` DATETIME NULL,
    `fecha_inicio` DATETIME NULL,
    `fecha_fin` DATETIME NULL,
    `enlace_reunion` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_curso`) REFERENCES `cursos`(`id_curso`) ON DELETE CASCADE,
    FOREIGN KEY (`id_mentor`) REFERENCES `mentores`(`id_mentor`) ON DELETE SET NULL,
    INDEX `idx_estado` (`estado`),
    INDEX `idx_fecha_programada` (`fecha_programada`),
    INDEX `idx_id_curso_mentor` (`id_curso`, `id_mentor`),
    INDEX `idx_estado_mentor` (`estado`, `id_mentor`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `inscripciones` (
    `id_inscripcion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_clase` INT NOT NULL,
    `id_estudiante` INT NOT NULL,
    `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `activa` BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (`id_clase`) REFERENCES `clases`(`id_clase`) ON DELETE CASCADE,
    FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes`(`id_estudiante`) ON DELETE CASCADE,
    UNIQUE KEY `unique_clase_estudiante` (`id_clase`, `id_estudiante`),
    INDEX `idx_activa` (`activa`),
    INDEX `idx_id_clase` (`id_clase`),
    INDEX `idx_id_estudiante` (`id_estudiante`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `asistencias` (
    `fecha` DATE NOT NULL,
    `id_estudiante` INT NOT NULL,
    `id_clase` INT NOT NULL,
    `estado_asistencia` TINYINT NOT NULL COMMENT '1=Temprano, 2=Tarde, 3=Falta',
    `hora_registro` TIME NULL,
    `observaciones` VARCHAR(255) NULL,
    `registrado_por` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`fecha`, `id_estudiante`, `id_clase`),
    FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes`(`id_estudiante`) ON DELETE CASCADE,
    FOREIGN KEY (`id_clase`) REFERENCES `clases`(`id_clase`) ON DELETE CASCADE,
    FOREIGN KEY (`registrado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL,
    INDEX `idx_fecha_clase` (`fecha`, `id_clase`),
    INDEX `idx_estado_asistencia` (`estado_asistencia`),
    INDEX `idx_id_estudiante_fecha` (`id_estudiante`, `fecha`),
    INDEX `idx_id_clase` (`id_clase`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `calificaciones` (
    `id_calificacion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_clase` INT NOT NULL,
    `id_estudiante` INT NOT NULL,
    `criterio_evaluacion` VARCHAR(100) NOT NULL,
    `nota` DECIMAL(4,2) NOT NULL,
    `porcentaje` DECIMAL(5,2) NOT NULL,
    `observaciones` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_clase`) REFERENCES `clases`(`id_clase`) ON DELETE CASCADE,
    FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes`(`id_estudiante`) ON DELETE CASCADE,
    UNIQUE KEY `unique_clase_estudiante_criterio` (`id_clase`, `id_estudiante`, `criterio_evaluacion`),
    INDEX `idx_id_clase` (`id_clase`),
    INDEX `idx_id_estudiante` (`id_estudiante`),
    INDEX `idx_nota` (`nota`),
    INDEX `idx_criterio` (`criterio_evaluacion`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `comentarios` (
    `id_comentario` INT AUTO_INCREMENT PRIMARY KEY,
    `id_estudiante` INT NOT NULL,
    `id_mentor` INT NOT NULL,
    `id_clase` INT NOT NULL,
    `puntuacion` TINYINT NOT NULL,
    `comentario_texto` TEXT NULL,
    `fecha_comentario` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes`(`id_estudiante`) ON DELETE CASCADE,
    FOREIGN KEY (`id_mentor`) REFERENCES `mentores`(`id_mentor`) ON DELETE CASCADE,
    FOREIGN KEY (`id_clase`) REFERENCES `clases`(`id_clase`) ON DELETE CASCADE,
    INDEX `idx_puntuacion` (`puntuacion`),
    INDEX `idx_fecha` (`fecha_comentario`),
    INDEX `idx_id_clase` (`id_clase`),
    INDEX `idx_id_estudiante` (`id_estudiante`),
    INDEX `idx_id_mentor` (`id_mentor`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `auditoria` (
    `id_auditoria` INT AUTO_INCREMENT PRIMARY KEY,
    `tabla_afectada` VARCHAR(50) NOT NULL,
    `registro_id` INT NOT NULL,
    `accion` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    `datos_anteriores` JSON NULL,
    `datos_nuevos` JSON NULL,
    `id_usuario` INT NULL,
    `ip_address` VARCHAR(45) NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL,
    INDEX `idx_tabla_registro` (`tabla_afectada`, `registro_id`),
    INDEX `idx_accion` (`accion`),
    INDEX `idx_timestamp` (`timestamp`),
    INDEX `idx_id_usuario` (`id_usuario`)
) ENGINE=InnoDB;

-- VISTAS
CREATE VIEW `v_usuarios` AS
SELECT 
   u.id_usuario,
   CONCAT(dp.nombres, ' ', dp.apellidos) as nombre_completo,
   dp.nombres,
   dp.apellidos,
   u.email,
   GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles,
   u.activo,
   u.email_verificado,
   u.created_at
FROM usuarios u
JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario AND ur.activo = TRUE
LEFT JOIN roles r ON ur.id_rol = r.id_rol AND r.activo = TRUE
WHERE u.activo = TRUE
GROUP BY u.id_usuario, dp.nombres, dp.apellidos, u.email, u.activo, u.email_verificado, u.created_at
ORDER BY dp.apellidos, dp.nombres;

-- ÍNDICES ADICIONALES
CREATE INDEX IF NOT EXISTS `idx_estudiantes_busqueda_completa` ON `estudiantes` (`codigo_estudiante`, `carrera`, `estado_academico`);
CREATE INDEX IF NOT EXISTS `idx_cursos_busqueda_completa` ON `cursos` (`codigo_curso`, `nombre`, `activo`);
CREATE INDEX IF NOT EXISTS `idx_clases_estado_mentor` ON `clases` (`estado`, `id_mentor`);
CREATE INDEX IF NOT EXISTS `idx_datos_personales_busqueda` ON `datos_personales` (`nombres`, `apellidos`);
CREATE INDEX IF NOT EXISTS `idx_estudiante_cursos_mentoria` ON `estudiante_cursos` (`necesita_mentoria`, `ponderado`);
CREATE INDEX IF NOT EXISTS `idx_asistencias_fecha_estado` ON `asistencias` (`fecha`, `estado_asistencia`);
CREATE INDEX IF NOT EXISTS `idx_comentarios_puntuacion` ON `comentarios` (`puntuacion`, `fecha_comentario`);

-- TRIGGERS PARA MARCAR SI UN ESTUDIANTE NECESITA MENTORÍA
DELIMITER //

CREATE TRIGGER `tr_marcar_necesita_mentoria_insert`
BEFORE INSERT ON `estudiante_cursos`
FOR EACH ROW
BEGIN
   IF NEW.ponderado < 11.0 THEN
       SET NEW.necesita_mentoria = TRUE;
   ELSE
       SET NEW.necesita_mentoria = FALSE;
   END IF;
END //

CREATE TRIGGER `tr_marcar_necesita_mentoria_update`
BEFORE UPDATE ON `estudiante_cursos`
FOR EACH ROW
BEGIN
   IF NEW.ponderado < 11.0 THEN
       SET NEW.necesita_mentoria = TRUE;
   ELSE
       SET NEW.necesita_mentoria = FALSE;
   END IF;
END //

-- TRIGGERS PARA ACTUALIZAR EL NÚMERO DE ESTUDIANTES INSCRITOS
CREATE TRIGGER `tr_actualizar_estudiantes_inscritos_insert`
AFTER INSERT ON `inscripciones`
FOR EACH ROW
BEGIN
   UPDATE `clases` 
   SET `estudiantes_inscritos` = (
       SELECT COUNT(*) 
       FROM `inscripciones` 
       WHERE `id_clase` = NEW.id_clase AND `activa` = TRUE
   )
   WHERE `id_clase` = NEW.id_clase;
END //

CREATE TRIGGER `tr_actualizar_estudiantes_inscritos_update`
AFTER UPDATE ON `inscripciones`
FOR EACH ROW
BEGIN
   IF OLD.activa != NEW.activa THEN
       UPDATE `clases` 
       SET `estudiantes_inscritos` = (
           SELECT COUNT(*) 
           FROM `inscripciones` 
           WHERE `id_clase` = NEW.id_clase AND `activa` = TRUE
       )
       WHERE `id_clase` = NEW.id_clase;
   END IF;
END //

CREATE TRIGGER `tr_actualizar_estudiantes_inscritos_delete`
AFTER DELETE ON `inscripciones`
FOR EACH ROW
BEGIN
   UPDATE `clases` 
   SET `estudiantes_inscritos` = (
       SELECT COUNT(*) 
       FROM `inscripciones` 
       WHERE `id_clase` = OLD.id_clase AND `activa` = TRUE
   )
   WHERE `id_clase` = OLD.id_clase;
END //

DELIMITER ;

-- PROCEDIMIENTO PARA REGISTRAR ESTUDIANTE Y CURSOS
DELIMITER //

CREATE PROCEDURE `sp_registrar_estudiante`(
   IN p_nombres VARCHAR(100),
   IN p_apellidos VARCHAR(100),
   IN p_codigo_estudiante VARCHAR(12),
   IN p_carrera VARCHAR(100),
   IN p_id_usuario INT,
   IN p_codigo_curso VARCHAR(10),
   IN p_nombre_curso VARCHAR(200),
   IN p_creditos TINYINT,
   IN p_estado VARCHAR(50),
   IN p_ponderado DECIMAL(4,2),
   IN p_periodo VARCHAR(10),
   OUT p_success BOOLEAN,
   OUT p_message VARCHAR(255),
   OUT p_id_estudiante INT
)
BEGIN
   DECLARE v_id_estudiante INT;
   DECLARE v_id_curso INT;
   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       ROLLBACK;
       SET p_success = FALSE;
       SET p_message = 'Error en la transacción';
       SET p_id_estudiante = NULL;
   END;
   
   START TRANSACTION;
   
   -- Verificar si el código de estudiante ya existe
   SELECT `id_estudiante` INTO v_id_estudiante 
   FROM `estudiantes` 
   WHERE `codigo_estudiante` = p_codigo_estudiante;
   
   -- Si no existe, crear el estudiante
   IF v_id_estudiante IS NULL THEN
       INSERT INTO `estudiantes` (
           `id_usuario`, `codigo_estudiante`, `carrera`, `estado_academico`
       ) VALUES (
           p_id_usuario, p_codigo_estudiante, p_carrera, 1
       );
       SET v_id_estudiante = LAST_INSERT_ID();
   END IF;
   
   -- Buscar si el curso ya existe
   SELECT `id_curso` INTO v_id_curso 
   FROM `cursos` 
   WHERE `codigo_curso` = p_codigo_curso;
   
   -- Si no existe, crear el curso
   IF v_id_curso IS NULL THEN
       INSERT INTO `cursos` (`codigo_curso`, `nombre`, `creditos`)
       VALUES (p_codigo_curso, p_nombre_curso, p_creditos);
       SET v_id_curso = LAST_INSERT_ID();
   END IF;
   
   -- Insertar o actualizar en estudiante_cursos
   INSERT INTO `estudiante_cursos` (
       `id_estudiante`, `id_curso`, `ponderado`, `estado_curso`
   ) VALUES (
       v_id_estudiante, v_id_curso, p_ponderado, p_estado
   ) ON DUPLICATE KEY UPDATE
       `ponderado` = VALUES(`ponderado`),
       `estado_curso` = VALUES(`estado_curso`);
   
   COMMIT;
   
   SET p_success = TRUE;
   SET p_message = 'Estudiante y curso registrado exitosamente';
   SET p_id_estudiante = v_id_estudiante;
   
END //

DELIMITER ;

-- PROCEDIMIENTO PARA CREAR O INSCRIBIR EN CLASE
DELIMITER //

CREATE PROCEDURE `sp_crear_o_inscribir_clase`(
   IN p_id_curso INT,
   IN p_id_estudiante INT,
   OUT p_success BOOLEAN,
   OUT p_message VARCHAR(255),
   OUT p_id_clase INT
)
BEGIN
   DECLARE v_id_clase INT DEFAULT NULL;
   DECLARE v_estudiantes_inscritos INT DEFAULT 0;
   DECLARE v_ya_inscrito INT DEFAULT 0;
   DECLARE v_codigo_curso VARCHAR(10);
   DECLARE v_nombre_curso VARCHAR(200);
   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       ROLLBACK;
       SET p_success = FALSE;
       SET p_message = 'Error en la transacción';
       SET p_id_clase = NULL;
   END;
   
   START TRANSACTION;
   
   -- Obtener datos del curso
   SELECT `codigo_curso`, `nombre` INTO v_codigo_curso, v_nombre_curso
   FROM `cursos` 
   WHERE `id_curso` = p_id_curso;
   
   -- Verificar si existe una clase PENDIENTE con cupos disponibles para este curso
   SELECT `id_clase`, `estudiantes_inscritos` 
   INTO v_id_clase, v_estudiantes_inscritos
   FROM `clases` 
   WHERE `id_curso` = p_id_curso 
     AND `estado` = 1 -- PENDIENTE
     AND `estudiantes_inscritos` < 25
   LIMIT 1;
   
   -- Si no existe clase con cupos o no existe clase, crear una nueva
   IF v_id_clase IS NULL THEN
       INSERT INTO `clases` (
           `id_curso`, `titulo`, `descripcion`, `capacidad_maxima`, `estado`
       ) VALUES (
           p_id_curso, 
           CONCAT('Mentoría - ', v_codigo_curso),
           CONCAT('Clase de mentoría para el curso: ', v_nombre_curso),
           25, 
           1 -- PENDIENTE
       );
       SET v_id_clase = LAST_INSERT_ID();
   END IF;
   
   -- Verificar si el estudiante ya está inscrito en esta clase
   SELECT COUNT(*) INTO v_ya_inscrito
   FROM `inscripciones`
   WHERE `id_clase` = v_id_clase 
     AND `id_estudiante` = p_id_estudiante 
     AND `activa` = TRUE;
   
   -- Si no está inscrito, inscribirlo
   IF v_ya_inscrito = 0 THEN
       INSERT INTO `inscripciones` (
           `id_clase`, `id_estudiante`, `activa`
       ) VALUES (
           v_id_clase, p_id_estudiante, TRUE
       );
       
       SET p_success = TRUE;
       SET p_message = 'Estudiante inscrito en la clase exitosamente';
       SET p_id_clase = v_id_clase;
   ELSE
       SET p_success = FALSE;
       SET p_message = 'El estudiante ya está inscrito en esta clase';
       SET p_id_clase = v_id_clase;
       ROLLBACK;
   END IF;
   
   COMMIT;
   
END //

DELIMITER ;