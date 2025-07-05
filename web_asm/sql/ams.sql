-- --------------------------------------------------------
-- Host:                         161.132.45.228
-- Versión del servidor:         10.11.11-MariaDB-0+deb12u1 - Debian 12
-- SO del servidor:              debian-linux-gnu
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para ams_system
CREATE DATABASE IF NOT EXISTS `ams_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `ams_system`;

-- Volcando estructura para tabla ams_system.administradores
CREATE TABLE IF NOT EXISTS `administradores` (
  `id_administrador` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nivel_acceso` tinyint(4) DEFAULT 1 COMMENT '1=Básico, 2=Avanzado, 3=Super Admin',
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permisos`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_administrador`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  KEY `idx_nivel_acceso` (`nivel_acceso`),
  CONSTRAINT `administradores_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.administradores: ~1 rows (aproximadamente)
INSERT INTO `administradores` (`id_administrador`, `id_usuario`, `nivel_acceso`, `permisos`, `created_at`, `updated_at`) VALUES
	(1, 16, 1, NULL, '2025-07-05 03:31:54', '2025-07-05 03:31:54');

-- Volcando estructura para tabla ams_system.asistencias
CREATE TABLE IF NOT EXISTS `asistencias` (
  `fecha` date NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_clase` int(11) NOT NULL,
  `estado_asistencia` tinyint(4) NOT NULL COMMENT '1=Temprano, 2=Tarde, 3=Falta',
  `hora_registro` time DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`fecha`,`id_estudiante`,`id_clase`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_fecha_clase` (`fecha`,`id_clase`),
  KEY `idx_estado_asistencia` (`estado_asistencia`),
  KEY `idx_id_estudiante_fecha` (`id_estudiante`,`fecha`),
  KEY `idx_id_clase` (`id_clase`),
  KEY `idx_asistencias_fecha_estado` (`fecha`,`estado_asistencia`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`id_clase`) ON DELETE CASCADE,
  CONSTRAINT `asistencias_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.asistencias: ~0 rows (aproximadamente)

-- Volcando estructura para tabla ams_system.auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `tabla_afectada` varchar(50) NOT NULL,
  `registro_id` int(11) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `id_usuario` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_tabla_registro` (`tabla_afectada`,`registro_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_id_usuario` (`id_usuario`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.auditoria: ~0 rows (aproximadamente)

-- Volcando estructura para tabla ams_system.calificaciones
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id_calificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_clase` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `criterio_evaluacion` varchar(100) NOT NULL,
  `nota` decimal(4,2) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_calificacion`),
  UNIQUE KEY `unique_clase_estudiante_criterio` (`id_clase`,`id_estudiante`,`criterio_evaluacion`),
  KEY `idx_id_clase` (`id_clase`),
  KEY `idx_id_estudiante` (`id_estudiante`),
  KEY `idx_nota` (`nota`),
  KEY `idx_criterio` (`criterio_evaluacion`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`id_clase`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.calificaciones: ~0 rows (aproximadamente)

-- Volcando estructura para tabla ams_system.clases
CREATE TABLE IF NOT EXISTS `clases` (
  `id_clase` int(11) NOT NULL AUTO_INCREMENT,
  `id_curso` int(11) NOT NULL,
  `id_mentor` int(11) DEFAULT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `capacidad_maxima` tinyint(4) DEFAULT 20,
  `estudiantes_inscritos` tinyint(4) DEFAULT 0,
  `estado` tinyint(4) DEFAULT 1 COMMENT '1=PENDIENTE, 2=ACTIVO, 3=EN_PROCESO, 4=FINALIZADO, 5=CERRADA',
  `fecha_programada` datetime DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `enlace_reunion` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_clase`),
  KEY `id_mentor` (`id_mentor`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_programada` (`fecha_programada`),
  KEY `idx_id_curso_mentor` (`id_curso`,`id_mentor`),
  KEY `idx_estado_mentor` (`estado`,`id_mentor`),
  KEY `idx_clases_estado_mentor` (`estado`,`id_mentor`),
  CONSTRAINT `clases_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE,
  CONSTRAINT `clases_ibfk_2` FOREIGN KEY (`id_mentor`) REFERENCES `mentores` (`id_mentor`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.clases: ~2 rows (aproximadamente)
INSERT INTO `clases` (`id_clase`, `id_curso`, `id_mentor`, `titulo`, `descripcion`, `capacidad_maxima`, `estudiantes_inscritos`, `estado`, `fecha_programada`, `fecha_inicio`, `fecha_fin`, `enlace_reunion`, `created_at`, `updated_at`) VALUES
	(1, 22, 1, 'Mentoría - SI-784', 'Clase de mentoría para el curso: CALIDAD Y PRUEBAS DE SOFTWARE', 25, 1, 1, NULL, NULL, NULL, NULL, '2025-07-04 00:54:40', '2025-07-05 14:42:56'),
	(2, 48, NULL, 'Mentoría - SI-785', 'Clase de mentoría para el curso: GESTIÓN DE PROYECTOS DE TI', 25, 1, 1, NULL, NULL, NULL, NULL, '2025-07-04 02:31:56', '2025-07-04 02:31:56');

-- Volcando estructura para tabla ams_system.comentarios
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id_comentario` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `id_mentor` int(11) NOT NULL,
  `id_clase` int(11) NOT NULL,
  `puntuacion` tinyint(4) NOT NULL,
  `comentario_texto` text DEFAULT NULL,
  `fecha_comentario` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_comentario`),
  KEY `idx_puntuacion` (`puntuacion`),
  KEY `idx_fecha` (`fecha_comentario`),
  KEY `idx_id_clase` (`id_clase`),
  KEY `idx_id_estudiante` (`id_estudiante`),
  KEY `idx_id_mentor` (`id_mentor`),
  KEY `idx_comentarios_puntuacion` (`puntuacion`,`fecha_comentario`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_mentor`) REFERENCES `mentores` (`id_mentor`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_3` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`id_clase`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.comentarios: ~0 rows (aproximadamente)

-- Volcando estructura para tabla ams_system.cursos
CREATE TABLE IF NOT EXISTS `cursos` (
  `id_curso` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_curso` varchar(10) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `creditos` tinyint(4) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_curso`),
  UNIQUE KEY `codigo_curso` (`codigo_curso`),
  KEY `idx_codigo_curso` (`codigo_curso`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_activo` (`activo`),
  KEY `idx_codigo_busqueda` (`codigo_curso`,`activo`),
  KEY `idx_nombre_busqueda` (`nombre`,`activo`),
  KEY `idx_cursos_busqueda_completa` (`codigo_curso`,`nombre`,`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.cursos: ~18 rows (aproximadamente)
INSERT INTO `cursos` (`id_curso`, `codigo_curso`, `nombre`, `creditos`, `activo`, `created_at`, `updated_at`) VALUES
	(19, 'SI-585', 'INGENIERÍA DE SOFTWARE', 3, 1, '2025-07-03 22:46:32', '2025-07-03 23:21:59'),
	(20, 'SI-782', 'SISTEMAS OPERATIVOS II', 4, 1, '2025-07-03 22:46:32', '2025-07-04 02:35:56'),
	(21, 'SI-783', 'BASE DE DATOS II', 4, 1, '2025-07-03 22:46:32', '2025-07-04 02:35:56'),
	(22, 'SI-784', 'CALIDAD Y PRUEBAS DE SOFTWARE', 3, 1, '2025-07-03 22:46:32', '2025-07-04 02:35:56'),
	(23, 'SI-786', 'PROGRAMACIÓN WEB I', 4, 1, '2025-07-03 22:46:32', '2025-07-04 02:35:56'),
	(24, 'TXP-1027', 'EXCEL - BASICO - INTERMEDIO', 1, 1, '2025-07-03 22:46:32', '2025-07-03 22:46:32'),
	(26, 'SI-686', 'PROGRAMACIÓN III', 4, 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(30, 'TXD-3015', 'FÚTBOL MIXTO', 1, 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(31, 'EG-382', 'ÉTICA', 3, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(32, 'SI-080', 'TALLER DE TESIS II / TRABAJO DE INVESTIGACIÓN', 3, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(33, 'SI-085', 'TALLER DE EMPRENDIMIENTO Y LIDERAZGO', 3, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(35, 'SI-886', 'PLANEAMIENTO ESTRATÉGICO DE TI', 3, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(36, 'SI-983', 'CONSTRUCCIÓN DE SOFTWARE I', 4, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(37, 'SI-985', 'GESTIÓN DE LA CONFIGURACIÓN DE SOFTWARE', 3, 1, '2025-07-04 01:47:00', '2025-07-04 02:26:33'),
	(48, 'SI-785', 'GESTIÓN DE PROYECTOS DE TI', 4, 1, '2025-07-04 02:28:13', '2025-07-04 02:35:56'),
	(50, 'SI-581', 'ARQUITECTURA DE COMPUTADORAS', 4, 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(51, 'SI-583', 'DISEÑO Y MODELAMIENTO VIRTUAL', 3, 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(56, 'EG-781', 'PROBLEMAS Y DESAFIOS DEL PERÚ EN UN MUNDO GLOBAL', 3, 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56');

-- Volcando estructura para tabla ams_system.datos_personales
CREATE TABLE IF NOT EXISTS `datos_personales` (
  `id_datos_personales` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('M','F','Otro') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_datos_personales`),
  KEY `idx_nombres_apellidos` (`nombres`,`apellidos`),
  KEY `idx_datos_personales_busqueda` (`nombres`,`apellidos`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.datos_personales: ~8 rows (aproximadamente)
INSERT INTO `datos_personales` (`id_datos_personales`, `nombres`, `apellidos`, `telefono`, `direccion`, `fecha_nacimiento`, `genero`, `created_at`, `updated_at`) VALUES
	(19, '', '', NULL, NULL, NULL, NULL, '2025-07-03 20:16:05', '2025-07-03 20:16:05'),
	(20, 'GREGORY BRANDON', 'HUANCA MERMA', NULL, NULL, NULL, NULL, '2025-07-03 20:22:06', '2025-07-03 22:46:32'),
	(21, 'AUGUSTO JOAQUIN', 'RIVERA MUÑOZ', NULL, NULL, NULL, NULL, '2025-07-03 23:21:18', '2025-07-03 23:21:59'),
	(22, 'RENZO ANTONIO', 'ANTAYHUA MAMANI', NULL, NULL, NULL, NULL, '2025-07-03 23:26:11', '2025-07-04 02:30:24'),
	(23, 'Rodrigo Samael Adonai', 'LIRA ALVAREZ', NULL, NULL, NULL, NULL, '2025-07-04 01:46:31', '2025-07-04 01:47:00'),
	(24, 'Rodrigo Samael Adonai', 'LIRA ALVAREZ', NULL, NULL, NULL, NULL, '2025-07-04 02:22:17', '2025-07-04 02:26:32'),
	(25, 'SEBASTIAN NICOLAS', 'FUENTES AVALOS', NULL, NULL, NULL, NULL, '2025-07-04 02:26:49', '2025-07-04 02:28:13'),
	(26, 'ANDY MICHAEL', 'CALIZAYA LADERA', NULL, NULL, NULL, NULL, '2025-07-04 02:35:14', '2025-07-04 02:35:55'),
	(27, '', '', NULL, NULL, NULL, NULL, '2025-07-05 03:30:08', '2025-07-05 03:30:08'),
	(28, '', '', NULL, NULL, NULL, NULL, '2025-07-05 03:32:56', '2025-07-05 03:32:56');

-- Volcando estructura para tabla ams_system.estudiantes
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id_estudiante` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `codigo_estudiante` varchar(12) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `estado_academico` tinyint(4) NOT NULL COMMENT '1=Activo, 2=Inactivo, 3=Graduado, 4=Suspendido',
  `promedio_general` decimal(4,2) DEFAULT NULL,
  `puede_solicitar_mentoria` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_estudiante`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  UNIQUE KEY `codigo_estudiante` (`codigo_estudiante`),
  KEY `idx_codigo_estudiante` (`codigo_estudiante`),
  KEY `idx_carrera` (`carrera`),
  KEY `idx_estado_academico` (`estado_academico`),
  KEY `idx_puede_solicitar` (`puede_solicitar_mentoria`),
  KEY `idx_estudiantes_busqueda_completa` (`codigo_estudiante`,`carrera`,`estado_academico`),
  CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.estudiantes: ~6 rows (aproximadamente)
INSERT INTO `estudiantes` (`id_estudiante`, `id_usuario`, `codigo_estudiante`, `carrera`, `estado_academico`, `promedio_general`, `puede_solicitar_mentoria`, `created_at`, `updated_at`) VALUES
	(6, 9, '2022073898', 'Ingeniería de Sistemas', 1, 8.00, 1, '2025-07-03 22:46:32', '2025-07-03 22:46:32'),
	(7, 10, '2022073505', 'Ingeniería de Sistemas', 1, 8.50, 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(9, 13, '2019063331', 'Ingeniería de Sistemas', 1, 7.29, 1, '2025-07-04 02:26:33', '2025-07-04 02:26:33'),
	(10, 14, '2022073902', 'Ingeniería de Sistemas', 1, 8.80, 1, '2025-07-04 02:28:13', '2025-07-04 02:28:13'),
	(11, 11, '2022073504', 'Ingeniería de Sistemas', 1, 8.83, 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(12, 15, '2022074258', 'Ingeniería de Sistemas', 1, 9.67, 1, '2025-07-04 02:35:55', '2025-07-04 02:35:56');

-- Volcando estructura para tabla ams_system.estudiante_cursos
CREATE TABLE IF NOT EXISTS `estudiante_cursos` (
  `id_estudiante_curso` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `ponderado` decimal(4,2) NOT NULL,
  `estado_curso` varchar(50) NOT NULL,
  `necesita_mentoria` tinyint(2) DEFAULT 1,
  `fecha_ultima_actualizacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_estudiante_curso`),
  UNIQUE KEY `unique_estudiante_curso` (`id_estudiante`,`id_curso`),
  KEY `id_curso` (`id_curso`),
  KEY `idx_ponderado` (`ponderado`),
  KEY `idx_estado_curso` (`estado_curso`),
  KEY `idx_estudiante_cursos_mentoria` (`necesita_mentoria`,`ponderado`),
  KEY `idx_necesita_mentoria` (`necesita_mentoria`),
  CONSTRAINT `estudiante_cursos_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE,
  CONSTRAINT `estudiante_cursos_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.estudiante_cursos: ~36 rows (aproximadamente)
INSERT INTO `estudiante_cursos` (`id_estudiante_curso`, `id_estudiante`, `id_curso`, `ponderado`, `estado_curso`, `necesita_mentoria`, `fecha_ultima_actualizacion`, `created_at`, `updated_at`) VALUES
	(4, 6, 19, 17.00, 'Matriculado', 4, '2025-07-04 21:22:50', '2025-07-03 22:46:32', '2025-07-04 21:22:50'),
	(5, 6, 20, 9.00, 'Matriculado', 1, '2025-07-04 21:21:28', '2025-07-03 22:46:32', '2025-07-04 21:21:28'),
	(6, 6, 21, 8.00, 'Matriculado', 1, '2025-07-03 22:46:32', '2025-07-03 22:46:32', '2025-07-03 22:46:32'),
	(7, 6, 22, 5.00, 'Matriculado', 0, '2025-07-04 21:22:35', '2025-07-03 22:46:32', '2025-07-04 21:22:35'),
	(8, 6, 23, 9.00, 'Matriculado', 1, '2025-07-03 22:46:32', '2025-07-03 22:46:32', '2025-07-03 22:46:32'),
	(9, 6, 24, 0.00, 'Matriculado', 0, '2025-07-04 21:22:35', '2025-07-03 22:46:32', '2025-07-04 21:22:35'),
	(10, 7, 19, 17.00, 'Matriculado', 4, '2025-07-04 21:22:35', '2025-07-03 23:21:59', '2025-07-04 21:22:35'),
	(11, 7, 26, 9.00, 'Matriculado', 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(12, 7, 20, 9.00, 'Matriculado', 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(13, 7, 21, 7.00, 'Matriculado', 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(14, 7, 23, 9.00, 'Matriculado', 1, '2025-07-03 23:21:59', '2025-07-03 23:21:59', '2025-07-03 23:21:59'),
	(15, 7, 30, 16.00, 'Matriculado', 4, '2025-07-04 21:22:35', '2025-07-03 23:21:59', '2025-07-04 21:22:35'),
	(23, 9, 31, 10.00, 'Matriculado', 1, '2025-07-04 02:26:33', '2025-07-04 02:26:33', '2025-07-04 02:26:33'),
	(24, 9, 32, 1.00, 'Retirado', 0, '2025-07-04 21:22:35', '2025-07-04 02:26:33', '2025-07-04 21:22:35'),
	(25, 9, 33, 13.00, 'Matriculado', 3, '2025-07-04 21:22:35', '2025-07-04 02:26:33', '2025-07-04 21:22:35'),
	(26, 9, 22, 6.00, 'Matriculado', 1, '2025-07-04 02:26:33', '2025-07-04 02:26:33', '2025-07-04 02:26:33'),
	(27, 9, 35, 9.00, 'Matriculado', 1, '2025-07-04 02:26:33', '2025-07-04 02:26:33', '2025-07-04 02:26:33'),
	(28, 9, 36, 7.00, 'Matriculado', 1, '2025-07-04 02:26:33', '2025-07-04 02:26:33', '2025-07-04 02:26:33'),
	(29, 9, 37, 5.00, 'Matriculado', 0, '2025-07-04 21:22:35', '2025-07-04 02:26:33', '2025-07-04 21:22:35'),
	(30, 10, 20, 9.00, 'Matriculado', 1, '2025-07-04 02:28:13', '2025-07-04 02:28:13', '2025-07-04 02:28:13'),
	(31, 10, 21, 11.00, 'Matriculado', 3, '2025-07-04 21:22:35', '2025-07-04 02:28:13', '2025-07-04 21:22:35'),
	(32, 10, 22, 8.00, 'Matriculado', 1, '2025-07-04 02:28:13', '2025-07-04 02:28:13', '2025-07-04 02:28:13'),
	(33, 10, 48, 7.00, 'Matriculado', 1, '2025-07-04 02:28:13', '2025-07-04 02:28:13', '2025-07-04 02:28:13'),
	(34, 10, 23, 9.00, 'Matriculado', 1, '2025-07-04 02:28:13', '2025-07-04 02:28:13', '2025-07-04 02:28:13'),
	(35, 11, 50, 13.00, 'Matriculado', 3, '2025-07-04 21:22:35', '2025-07-04 02:30:24', '2025-07-04 21:22:35'),
	(36, 11, 51, 12.00, 'Matriculado', 3, '2025-07-04 21:22:35', '2025-07-04 02:30:24', '2025-07-04 21:22:35'),
	(37, 11, 21, 9.00, 'Matriculado', 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(38, 11, 22, 4.00, 'Matriculado', 0, '2025-07-04 21:22:35', '2025-07-04 02:30:24', '2025-07-04 21:22:35'),
	(39, 11, 48, 7.00, 'Matriculado', 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(40, 11, 23, 8.00, 'Matriculado', 1, '2025-07-04 02:30:24', '2025-07-04 02:30:24', '2025-07-04 02:30:24'),
	(41, 12, 56, 16.00, 'Matriculado', 4, '2025-07-04 21:22:35', '2025-07-04 02:35:56', '2025-07-04 21:22:35'),
	(42, 12, 20, 10.00, 'Matriculado', 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56', '2025-07-04 02:35:56'),
	(43, 12, 21, 9.00, 'Matriculado', 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56', '2025-07-04 02:35:56'),
	(44, 12, 22, 7.00, 'Matriculado', 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56', '2025-07-04 02:35:56'),
	(45, 12, 48, 7.00, 'Matriculado', 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56', '2025-07-04 02:35:56'),
	(46, 12, 23, 9.00, 'Matriculado', 1, '2025-07-04 02:35:56', '2025-07-04 02:35:56', '2025-07-04 02:35:56');

-- Volcando estructura para tabla ams_system.inscripciones
CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_clase` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `fecha_inscripcion` timestamp NULL DEFAULT current_timestamp(),
  `activa` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_inscripcion`),
  UNIQUE KEY `unique_clase_estudiante` (`id_clase`,`id_estudiante`),
  KEY `idx_activa` (`activa`),
  KEY `idx_id_clase` (`id_clase`),
  KEY `idx_id_estudiante` (`id_estudiante`),
  CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`id_clase`) ON DELETE CASCADE,
  CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.inscripciones: ~2 rows (aproximadamente)
INSERT INTO `inscripciones` (`id_inscripcion`, `id_clase`, `id_estudiante`, `fecha_inscripcion`, `activa`) VALUES
	(1, 1, 6, '2025-07-04 00:54:40', 1),
	(2, 2, 11, '2025-07-04 02:31:56', 1);

-- Volcando estructura para tabla ams_system.mentores
CREATE TABLE IF NOT EXISTS `mentores` (
  `id_mentor` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `especialidades` text DEFAULT NULL,
  `puede_tomar_clase` tinyint(1) DEFAULT 1,
  `calificacion_promedio` decimal(3,2) DEFAULT 0.00,
  `total_clases_dadas` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_mentor`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  KEY `idx_puede_tomar_clase` (`puede_tomar_clase`),
  KEY `idx_calificacion` (`calificacion_promedio`),
  CONSTRAINT `mentores_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.mentores: ~1 rows (aproximadamente)
INSERT INTO `mentores` (`id_mentor`, `id_usuario`, `especialidades`, `puede_tomar_clase`, `calificacion_promedio`, `total_clases_dadas`, `created_at`, `updated_at`) VALUES
	(1, 16, NULL, 1, 0.00, 0, '2025-07-05 14:06:02', '2025-07-05 14:06:02');

-- Volcando estructura para tabla ams_system.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `priority` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.roles: ~4 rows (aproximadamente)
INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`, `activo`, `priority`, `created_at`) VALUES
	(1, 'usuario', 'Usuario general sin permisos especiales', 1, 4, '2025-07-01 02:40:30'),
	(2, 'estudiante', 'Usuario que puede solicitar mentoría', 1, 3, '2025-07-01 02:40:30'),
	(3, 'docente', 'Usuario que puede dar mentoría y clases', 1, 2, '2025-07-01 02:40:30'),
	(4, 'administrador', 'Usuario con permisos completos del sistema', 1, 1, '2025-07-01 02:40:30');

-- Volcando estructura para procedimiento ams_system.sp_crear_o_inscribir_clase
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
   
END//
DELIMITER ;

-- Volcando estructura para procedimiento ams_system.sp_obtener_clases_disponibles_para_inscripcion
DELIMITER //
CREATE PROCEDURE `sp_obtener_clases_disponibles_para_inscripcion`(
    IN p_id_usuario INT
)
BEGIN
    SELECT 
        c.id_clase,
        c.titulo,
        c.descripcion,
        c.capacidad_maxima,
        c.estudiantes_inscritos,
        c.estado,
        c.fecha_programada,
        c.fecha_inicio,
        c.enlace_reunion,
        cur.codigo_curso,
        cur.nombre as nombre_curso,
        cur.creditos,
        CONCAT(dp_mentor.nombres, ' ', dp_mentor.apellidos) as nombre_mentor,
        ec.ponderado,
        ec.necesita_mentoria,
        CASE c.estado
            WHEN 1 THEN 'PENDIENTE'
            WHEN 2 THEN 'ACTIVO'
            WHEN 3 THEN 'EN_PROCESO'
            WHEN 4 THEN 'FINALIZADO'
            WHEN 5 THEN 'CERRADA'
            ELSE 'DESCONOCIDO'
        END as estado_descripcion,
        (c.capacidad_maxima - c.estudiantes_inscritos) as cupos_disponibles
    FROM clases c
    INNER JOIN cursos cur ON c.id_curso = cur.id_curso
    INNER JOIN estudiante_cursos ec ON cur.id_curso = ec.id_curso
    INNER JOIN estudiantes e ON ec.id_estudiante = e.id_estudiante
    LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
    LEFT JOIN usuarios u_mentor ON m.id_usuario = u_mentor.id_usuario
    LEFT JOIN datos_personales dp_mentor ON u_mentor.id_datos_personales = dp_mentor.id_datos_personales
    WHERE e.id_usuario = p_id_usuario
        AND c.estado IN (1, 2) -- PENDIENTE o ACTIVO
        AND c.estudiantes_inscritos < c.capacidad_maxima -- Tiene cupos disponibles
        AND cur.activo = TRUE
        AND NOT EXISTS (
            SELECT 1 
            FROM inscripciones i 
            WHERE i.id_clase = c.id_clase 
                AND i.id_estudiante = e.id_estudiante 
                AND i.activa = TRUE
        ) -- No está ya inscrito en esta clase
    ORDER BY 
        ec.necesita_mentoria DESC, -- Priorizar cursos donde necesita mentoría
        c.fecha_programada ASC,
        c.created_at DESC;
END//
DELIMITER ;

-- Volcando estructura para procedimiento ams_system.sp_obtener_clases_estudiante_inscrito
DELIMITER //
CREATE PROCEDURE `sp_obtener_clases_estudiante_inscrito`(
    IN p_id_usuario INT
)
BEGIN
    SELECT 
        c.id_clase,
        c.titulo,
        c.descripcion,
        c.capacidad_maxima,
        c.estudiantes_inscritos,
        c.estado,
        c.fecha_programada,
        c.fecha_inicio,
        c.fecha_fin,
        c.enlace_reunion,
        cur.codigo_curso,
        cur.nombre as nombre_curso,
        cur.creditos,
        CONCAT(dp_mentor.nombres, ' ', dp_mentor.apellidos) as nombre_mentor,
        i.fecha_inscripcion,
        i.activa as inscripcion_activa,
        CASE c.estado
            WHEN 1 THEN 'PENDIENTE'
            WHEN 2 THEN 'ACTIVO'
            WHEN 3 THEN 'EN_PROCESO'
            WHEN 4 THEN 'FINALIZADO'
            WHEN 5 THEN 'CERRADA'
            ELSE 'DESCONOCIDO'
        END as estado_descripcion
    FROM inscripciones i
    INNER JOIN clases c ON i.id_clase = c.id_clase
    INNER JOIN cursos cur ON c.id_curso = cur.id_curso
    INNER JOIN estudiantes e ON i.id_estudiante = e.id_estudiante
    LEFT JOIN mentores m ON c.id_mentor = m.id_mentor
    LEFT JOIN usuarios u_mentor ON m.id_usuario = u_mentor.id_usuario
    LEFT JOIN datos_personales dp_mentor ON u_mentor.id_datos_personales = dp_mentor.id_datos_personales
    WHERE e.id_usuario = p_id_usuario
        AND i.activa = TRUE
        AND cur.activo = TRUE
    ORDER BY c.fecha_programada DESC, c.created_at DESC;
END//
DELIMITER ;

-- Volcando estructura para procedimiento ams_system.sp_obtener_datos_completos
DELIMITER //
CREATE PROCEDURE `sp_obtener_datos_completos`(
	IN `p_id_usuario` INT
)
BEGIN
   DECLARE v_rol_prioritario INT DEFAULT NULL;
   DECLARE v_es_admin BOOLEAN DEFAULT FALSE;
   DECLARE v_es_docente BOOLEAN DEFAULT FALSE;
   DECLARE v_es_estudiante BOOLEAN DEFAULT FALSE;
   
   SELECT r.id_rol INTO v_rol_prioritario
   FROM usuario_roles ur
   JOIN roles r ON ur.id_rol = r.id_rol
   WHERE ur.id_usuario = p_id_usuario 
     AND ur.activo = TRUE 
     AND r.activo = TRUE
   ORDER BY r.priority ASC
   LIMIT 1;
   
   SELECT COUNT(*) > 0 INTO v_es_admin
   FROM usuario_roles ur
   WHERE ur.id_usuario = p_id_usuario 
     AND ur.id_rol = 4 -- administrador
     AND ur.activo = TRUE;
     
   SELECT COUNT(*) > 0 INTO v_es_docente
   FROM usuario_roles ur
   WHERE ur.id_usuario = p_id_usuario 
     AND ur.id_rol = 3 -- docente
     AND ur.activo = TRUE;
     
   SELECT COUNT(*) > 0 INTO v_es_estudiante
   FROM usuario_roles ur
   WHERE ur.id_usuario = p_id_usuario 
     AND ur.id_rol = 2 -- estudiante
     AND ur.activo = TRUE;
   
   SELECT 
       dp.nombres,
       dp.apellidos,
       dp.telefono,
       dp.direccion,
       dp.fecha_nacimiento,
       dp.genero,
       
       u.id_usuario,
       u.email,
       u.email_verificado,
       u.activo,
       u.created_at as fecha_registro,
       
       -- ROL PRIORITARIO
       v_rol_prioritario as rol_prioritario,
       
       -- DATOS ESTUDIANTE (SOLO SI TIENE ROL ESTUDIANTE)
       IF(v_es_estudiante, e.id_estudiante, NULL) as id_estudiante,
       IF(v_es_estudiante, e.codigo_estudiante, NULL) as codigo_estudiante,
       IF(v_es_estudiante, e.carrera, NULL) as carrera,
       IF(v_es_estudiante, e.estado_academico, NULL) as estado_academico,
       IF(v_es_estudiante, e.promedio_general, NULL) as promedio_general,
       IF(v_es_estudiante, e.puede_solicitar_mentoria, NULL) as puede_solicitar_mentoria,
       
       -- DATOS MENTOR (SOLO SI TIENE ROL DOCENTE)
       IF(v_es_docente, m.id_mentor, NULL) as id_mentor,
       IF(v_es_docente, m.especialidades, NULL) as especialidades,
       IF(v_es_docente, m.puede_tomar_clase, NULL) as puede_tomar_clase,
       IF(v_es_docente, m.calificacion_promedio, NULL) as calificacion_promedio,
       IF(v_es_docente, m.total_clases_dadas, NULL) as total_clases_dadas,
       
       -- DATOS ADMIN (SOLO SI TIENE ROL ADMINISTRADOR)
       IF(v_es_admin, a.id_administrador, NULL) as id_administrador,
       IF(v_es_admin, a.nivel_acceso, NULL) as nivel_acceso,
       IF(v_es_admin, a.permisos, NULL) as permisos
       
   FROM usuarios u
   JOIN datos_personales dp ON u.id_datos_personales = dp.id_datos_personales
   LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
   LEFT JOIN mentores m ON u.id_usuario = m.id_usuario
   LEFT JOIN administradores a ON u.id_usuario = a.id_usuario
   
   WHERE u.id_usuario = p_id_usuario;
   
END//
DELIMITER ;

-- Volcando estructura para procedimiento ams_system.sp_registrar_estudiante
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
   
END//
DELIMITER ;

-- Volcando estructura para procedimiento ams_system.sp_registrar_usuario
DELIMITER //
CREATE PROCEDURE `sp_registrar_usuario`(
    IN p_email VARCHAR(150),
    IN p_password VARCHAR(255),
    IN p_google_id VARCHAR(100),
    OUT p_id_usuario INT,
    OUT p_id_rol INT
)
sp_registrar_usuario: BEGIN
    DECLARE v_id_datos_personales INT DEFAULT 0;
    DECLARE v_id_rol_usuario INT DEFAULT 1;  -- Usar directamente ID 1 = usuario
    DECLARE v_es_oauth BOOLEAN DEFAULT FALSE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_usuario = 0;
        SET p_id_rol = 0;
        SET @p_id_usuario = 0;
        SET @p_id_rol = 0;
    END;
    
    SET p_id_usuario = 0;
    SET p_id_rol = 0;
    
    -- Determinar si es OAuth
    SET v_es_oauth = (p_google_id IS NOT NULL AND p_google_id != '');
    
    START TRANSACTION;
    
    -- Verificar duplicados
    IF v_es_oauth THEN
        IF EXISTS(SELECT 1 FROM usuarios WHERE email = p_email OR google_id = p_google_id) THEN
            ROLLBACK;
            LEAVE sp_registrar_usuario;
        END IF;
    ELSE
        IF EXISTS(SELECT 1 FROM usuarios WHERE email = p_email) THEN
            ROLLBACK;
            LEAVE sp_registrar_usuario;
        END IF;
    END IF;
    
    -- Insertar datos personales (solo campos que existen)
    INSERT INTO datos_personales (nombres, apellidos) 
    VALUES ('', '');
    
    SET v_id_datos_personales = LAST_INSERT_ID();
    
    -- Verificar que se insertó
    IF v_id_datos_personales = 0 THEN
        ROLLBACK;
        LEAVE sp_registrar_usuario;
    END IF;
    
    -- Insertar usuario (campos según tu estructura real)
    INSERT INTO usuarios (
        email, 
        password_hash,
        id_datos_personales,
        google_id,
        email_verificado,
        activo,
        oauth_provider
    ) VALUES (
        p_email, 
        p_password,                                   -- Ahora puede ser NULL
        v_id_datos_personales,
        p_google_id,                                  -- Puede ser NULL
        IF(v_es_oauth, 1, 0),                        -- 1 para OAuth, 0 para tradicional
        1,                                           -- Siempre activo
        IF(v_es_oauth, 1, NULL)                      -- 1 para google (bit), NULL para tradicional
    );
    
    SET p_id_usuario = LAST_INSERT_ID();
    
    -- Verificar que se insertó el usuario
    IF p_id_usuario = 0 THEN
        ROLLBACK;
        LEAVE sp_registrar_usuario;
    END IF;
    
    -- Insertar rol
    INSERT INTO usuario_roles (id_usuario, id_rol, activo)
    VALUES (p_id_usuario, v_id_rol_usuario, 1);
    
    -- Verificar que se insertó el rol
    IF ROW_COUNT() = 0 THEN
        ROLLBACK;
        LEAVE sp_registrar_usuario;
    END IF;
    
    SET p_id_rol = v_id_rol_usuario;
    
    COMMIT;
    
    -- Variables para PHP
    SET @p_id_usuario = p_id_usuario;
    SET @p_id_rol = p_id_rol;
    
END//
DELIMITER ;

-- Volcando estructura para tabla ams_system.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `id_datos_personales` int(11) NOT NULL,
  `ultima_actividad` timestamp NULL DEFAULT NULL,
  `email_verificado` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `oauth_provider` bit(1) DEFAULT NULL,
  `google_id` varchar(200) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `id_datos_personales` (`id_datos_personales`),
  KEY `idx_email` (`email`),
  KEY `idx_activo` (`activo`),
  KEY `idx_email_busqueda` (`email`,`activo`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_datos_personales`) REFERENCES `datos_personales` (`id_datos_personales`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.usuarios: ~9 rows (aproximadamente)
INSERT INTO `usuarios` (`id_usuario`, `email`, `password_hash`, `id_datos_personales`, `ultima_actividad`, `email_verificado`, `activo`, `oauth_provider`, `google_id`, `created_at`, `updated_at`) VALUES
	(8, 'admin', NULL, 19, NULL, 1, 1, b'1', 'google123456789', '2025-07-03 20:16:05', '2025-07-03 20:16:58'),
	(9, 'gh2022073898@virtual.upt.pe', NULL, 20, '2025-07-05 13:01:34', 1, 1, b'1', '101813332633214491012', '2025-07-03 20:22:06', '2025-07-05 13:01:34'),
	(10, 'ar2022073505@virtual.upt.pe', NULL, 21, '2025-07-03 23:21:18', 1, 1, b'1', '118329219399984957613', '2025-07-03 23:21:18', '2025-07-03 23:21:18'),
	(11, 'jm2022074255@virtual.upt.pe', NULL, 22, '2025-07-04 02:34:10', 1, 1, b'1', '102796260988686898694', '2025-07-03 23:26:11', '2025-07-04 02:34:10'),
	(13, 'rl2019063331@virtual.upt.pe', NULL, 24, '2025-07-04 02:22:17', 1, 1, b'1', '100574059485395911096', '2025-07-04 02:22:17', '2025-07-04 02:22:17'),
	(14, 'sf2022073902@virtual.upt.pe', NULL, 25, '2025-07-04 02:26:49', 1, 1, b'1', '118030351119923353936', '2025-07-04 02:26:49', '2025-07-04 02:26:49'),
	(15, 'ac2022074258@virtual.upt.pe', NULL, 26, '2025-07-04 02:35:14', 1, 1, b'1', '104009791647826496397', '2025-07-04 02:35:14', '2025-07-04 02:35:14'),
	(16, 'gbhm2003@gmail.com', NULL, 27, '2025-07-05 15:56:27', 1, 1, b'1', '110751935956863197767', '2025-07-05 03:30:08', '2025-07-05 15:56:27'),
	(17, 'gregorypro159@gmail.com', NULL, 28, '2025-07-05 03:32:56', 1, 1, b'1', '108910060774384921111', '2025-07-05 03:32:56', '2025-07-05 03:32:56');

-- Volcando estructura para tabla ams_system.usuario_roles
CREATE TABLE IF NOT EXISTS `usuario_roles` (
  `id_usuario_rol` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `fecha_asignacion` timestamp NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_usuario_rol`),
  UNIQUE KEY `unique_usuario_rol` (`id_usuario`,`id_rol`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_id_rol` (`id_rol`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `usuario_roles_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `usuario_roles_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla ams_system.usuario_roles: ~17 rows (aproximadamente)
INSERT INTO `usuario_roles` (`id_usuario_rol`, `id_usuario`, `id_rol`, `fecha_asignacion`, `activo`) VALUES
	(3, 8, 1, '2025-07-03 20:16:05', 1),
	(4, 9, 1, '2025-07-03 20:22:06', 1),
	(5, 9, 2, '2025-07-03 22:46:32', 1),
	(6, 10, 1, '2025-07-03 23:21:18', 1),
	(7, 10, 2, '2025-07-03 23:21:59', 1),
	(8, 11, 1, '2025-07-03 23:26:11', 1),
	(11, 13, 1, '2025-07-04 02:22:17', 1),
	(12, 13, 2, '2025-07-04 02:26:33', 1),
	(13, 14, 1, '2025-07-04 02:26:49', 1),
	(14, 14, 2, '2025-07-04 02:28:13', 1),
	(15, 11, 2, '2025-07-04 02:30:24', 1),
	(16, 15, 1, '2025-07-04 02:35:14', 1),
	(17, 15, 2, '2025-07-04 02:35:56', 1),
	(18, 16, 1, '2025-07-05 03:30:08', 1),
	(19, 16, 4, '2025-07-05 03:32:22', 0),
	(20, 17, 1, '2025-07-05 03:32:56', 1),
	(21, 16, 3, '2025-07-05 14:05:34', 1);

-- Volcando estructura para vista ams_system.v_usuarios
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `v_usuarios` (
	`id_usuario` INT(11) NOT NULL,
	`nombre_completo` VARCHAR(1) NULL COLLATE 'utf8mb4_unicode_ci',
	`nombres` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`apellidos` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`email` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`roles` MEDIUMTEXT NULL COLLATE 'utf8mb4_unicode_ci',
	`activo` TINYINT(1) NULL,
	`email_verificado` TINYINT(1) NULL,
	`created_at` TIMESTAMP NULL
) ENGINE=MyISAM;

-- Volcando estructura para disparador ams_system.tr_actualizar_estudiantes_inscritos_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
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
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador ams_system.tr_actualizar_estudiantes_inscritos_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
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
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador ams_system.tr_actualizar_estudiantes_inscritos_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
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
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador ams_system.tr_marcar_necesita_mentoria_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tr_marcar_necesita_mentoria_insert`
BEFORE INSERT ON `estudiante_cursos`
FOR EACH ROW
BEGIN
    -- Asignar valor basado en rangos de ponderado
    CASE
        WHEN NEW.ponderado >= 0 AND NEW.ponderado <= 5 THEN
            SET NEW.necesita_mentoria = 0;
        WHEN NEW.ponderado >= 6 AND NEW.ponderado <= 10 THEN
            SET NEW.necesita_mentoria = 1;
        WHEN NEW.ponderado >= 11 AND NEW.ponderado <= 15 THEN
            SET NEW.necesita_mentoria = 3;
        WHEN NEW.ponderado >= 16 AND NEW.ponderado <= 20 THEN
            SET NEW.necesita_mentoria = 4;
        ELSE
            -- Para valores fuera de rango (opcional)
            SET NEW.necesita_mentoria = 0;
    END CASE;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador ams_system.tr_marcar_necesita_mentoria_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tr_marcar_necesita_mentoria_update`
BEFORE UPDATE ON `estudiante_cursos`
FOR EACH ROW
BEGIN
    -- Asignar valor basado en rangos de ponderado
    CASE
        WHEN NEW.ponderado >= 0 AND NEW.ponderado <= 5 THEN
            SET NEW.necesita_mentoria = 0;
        WHEN NEW.ponderado >= 6 AND NEW.ponderado <= 10 THEN
            SET NEW.necesita_mentoria = 1;
        WHEN NEW.ponderado >= 11 AND NEW.ponderado <= 15 THEN
            SET NEW.necesita_mentoria = 3;
        WHEN NEW.ponderado >= 16 AND NEW.ponderado <= 20 THEN
            SET NEW.necesita_mentoria = 4;
        ELSE
            -- Para valores fuera de rango (opcional)
            SET NEW.necesita_mentoria = 0;
    END CASE;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `v_usuarios`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_usuarios` AS select `u`.`id_usuario` AS `id_usuario`,concat(`dp`.`nombres`,' ',`dp`.`apellidos`) AS `nombre_completo`,`dp`.`nombres` AS `nombres`,`dp`.`apellidos` AS `apellidos`,`u`.`email` AS `email`,group_concat(`r`.`nombre` separator ', ') AS `roles`,`u`.`activo` AS `activo`,`u`.`email_verificado` AS `email_verificado`,`u`.`created_at` AS `created_at` from (((`usuarios` `u` join `datos_personales` `dp` on(`u`.`id_datos_personales` = `dp`.`id_datos_personales`)) left join `usuario_roles` `ur` on(`u`.`id_usuario` = `ur`.`id_usuario` and `ur`.`activo` = 1)) left join `roles` `r` on(`ur`.`id_rol` = `r`.`id_rol` and `r`.`activo` = 1)) where `u`.`activo` = 1 group by `u`.`id_usuario`,`dp`.`nombres`,`dp`.`apellidos`,`u`.`email`,`u`.`activo`,`u`.`email_verificado`,`u`.`created_at` order by `dp`.`apellidos`,`dp`.`nombres`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
