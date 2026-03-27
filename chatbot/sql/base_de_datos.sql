CREATE DATABASE IF NOT EXISTS chatbot_seguros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chatbot_seguros;

CREATE TABLE IF NOT EXISTS tipos_seguro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    orden_visual INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opciones_seguro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_seguro_id INT NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    orden_visual INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_opciones_tipo FOREIGN KEY (tipo_seguro_id) REFERENCES tipos_seguro(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telefono VARCHAR(20) NOT NULL UNIQUE,
    estado_actual VARCHAR(80) NOT NULL,
    datos_temporales JSON NULL,
    tipo_seguro_id INT NULL,
    opcion_seguro_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversacion_tipo FOREIGN KEY (tipo_seguro_id) REFERENCES tipos_seguro(id),
    CONSTRAINT fk_conversacion_opcion FOREIGN KEY (opcion_seguro_id) REFERENCES opciones_seguro(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitudes_seguro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telefono VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(120) NOT NULL,
    edad INT NOT NULL,
    correo VARCHAR(120) NOT NULL,
    ciudad VARCHAR(80) NOT NULL,
    codigo_postal VARCHAR(5) NOT NULL,
    tipo_seguro_id INT NOT NULL,
    opcion_seguro_id INT NOT NULL,
    datos_adicionales JSON NULL,
    ruta_ine_frente VARCHAR(255) NULL,
    mime_ine_frente VARCHAR(80) NULL,
    media_id_frente VARCHAR(80) NULL,
    ruta_ine_reverso VARCHAR(255) NULL,
    mime_ine_reverso VARCHAR(80) NULL,
    media_id_reverso VARCHAR(80) NULL,
    validacion_ine_completa TINYINT(1) NOT NULL DEFAULT 0,
    estatus ENUM('pendiente_revision','en_contacto','cerrada') NOT NULL DEFAULT 'pendiente_revision',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitud_tipo FOREIGN KEY (tipo_seguro_id) REFERENCES tipos_seguro(id),
    CONSTRAINT fk_solicitud_opcion FOREIGN KEY (opcion_seguro_id) REFERENCES opciones_seguro(id)
) ENGINE=InnoDB;

INSERT INTO tipos_seguro (id, nombre, descripcion, orden_visual, activo)
VALUES
(1, 'Seguro de Auto', 'Cotiza coberturas para tu vehículo.', 1, 1),
(2, 'Seguro de Vida', 'Protección personal y familiar.', 2, 1),
(3, 'Seguro de Gastos Médicos', 'Cobertura médica para ti o tu familia.', 3, 1),
(4, 'Seguro de Hogar', 'Protección para vivienda y pertenencias.', 4, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), orden_visual = VALUES(orden_visual), activo = VALUES(activo);

INSERT INTO opciones_seguro (id, tipo_seguro_id, nombre, descripcion, orden_visual, activo)
VALUES
(1, 1, 'Cobertura Básica', 'Daños a terceros y asistencia básica.', 1, 1),
(2, 1, 'Cobertura Limitada', 'Incluye robo total y responsabilidad civil.', 2, 1),
(3, 1, 'Cobertura Amplia', 'Protección integral del vehículo.', 3, 1),
(4, 2, 'Plan Individual', 'Protección para una sola persona.', 1, 1),
(5, 2, 'Plan Familiar', 'Cobertura para dependientes directos.', 2, 1),
(6, 2, 'Plan con Ahorro', 'Cobertura con componente de ahorro.', 3, 1),
(7, 3, 'Plan Básico', 'Cobertura médica esencial.', 1, 1),
(8, 3, 'Plan Plus', 'Cobertura con beneficios ampliados.', 2, 1),
(9, 3, 'Plan Premium', 'Mayor nivel de protección y red médica.', 3, 1),
(10, 4, 'Protección Esencial', 'Daños básicos a la vivienda.', 1, 1),
(11, 4, 'Protección Completa', 'Cobertura amplia de vivienda y contenidos.', 2, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), orden_visual = VALUES(orden_visual), activo = VALUES(activo);
