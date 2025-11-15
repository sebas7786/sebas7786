-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: aclaraciones_licitacion
-- Almacena solicitudes de aclaraciones y sus respuestas para licitaciones
-- Importado desde: "Aclaraciones.csv"
-- ═══════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `aclaraciones_licitacion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,

    -- Identificación del cartel/licitación
    `numero_cartel` VARCHAR(20) NOT NULL COMMENT 'Número SICOP del cartel',
    `no_cartel` VARCHAR(100) NULL COMMENT 'Número de cartel descriptivo',
    `titulo` TEXT NULL COMMENT 'Título de la licitación',

    -- Datos de la solicitud
    `fecha_solicitud` DATETIME NULL COMMENT 'Fecha en que se hizo la solicitud de aclaración',
    `numero_aclaracion` VARCHAR(50) NULL COMMENT 'Número identificador de la aclaración',
    `solicitante` VARCHAR(255) NULL COMMENT 'Nombre o identificación del solicitante',
    `cedula_empresa_proveedora` VARCHAR(20) NULL COMMENT 'Cédula de la empresa que solicita',

    -- Datos de la respuesta
    `estado_respuesta` VARCHAR(50) NULL COMMENT 'Estado de la respuesta (Respondida, Pendiente, etc)',
    `numero_respuesta` VARCHAR(50) NULL COMMENT 'Número de la respuesta',
    `fecha_respuesta` DATETIME NULL COMMENT 'Fecha en que se respondió la aclaración',

    -- Control de importación
    `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha en que se importó este registro',
    `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices para búsquedas eficientes
    INDEX `idx_numero_cartel` (`numero_cartel`),
    INDEX `idx_cedula_empresa` (`cedula_empresa_proveedora`),
    INDEX `idx_estado_respuesta` (`estado_respuesta`),
    INDEX `idx_fecha_solicitud` (`fecha_solicitud`),
    INDEX `idx_numero_aclaracion` (`numero_aclaracion`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Solicitudes de aclaraciones y respuestas de licitaciones SICOP';
