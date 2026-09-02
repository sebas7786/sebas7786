-- ============================================================
-- Eight Technologies — Portal Setup SQL
-- Ejecutar una sola vez para crear tablas y usuario admin
-- ============================================================

CREATE DATABASE IF NOT EXISTS `Eight` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `Eight`;

-- ── Usuarios del portal ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`        VARCHAR(120) NOT NULL,
  `email`         VARCHAR(180) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol`           ENUM('admin','agente','cliente') NOT NULL DEFAULT 'cliente',
  `activo`        TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login`  DATETIME NULL
) ENGINE=InnoDB;

-- ── Clientes (empresa / organización) ────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(200) NOT NULL,
  `email`       VARCHAR(180) NULL,
  `telefono`    VARCHAR(40)  NULL,
  `direccion`   TEXT         NULL,
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id`  INT UNSIGNED NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Tickets de soporte ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tickets` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `numero`       VARCHAR(20) NOT NULL UNIQUE,
  `titulo`       VARCHAR(250) NOT NULL,
  `descripcion`  TEXT NOT NULL,
  `estado`       ENUM('abierto','en_progreso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  `prioridad`    ENUM('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
  `cliente_id`   INT UNSIGNED NULL,
  `usuario_id`   INT UNSIGNED NULL,
  `asignado_a`   INT UNSIGNED NULL,
  `creado_en`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`)  REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`)  REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`asignado_a`)  REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Mensajes / comentarios de tickets ────────────────────────
CREATE TABLE IF NOT EXISTS `ticket_mensajes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id`  INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NULL,
  `mensaje`    TEXT NOT NULL,
  `creado_en`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`)  REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Usuario administrador inicial ────────────────────────────
-- Contraseña: Admin2024!
INSERT INTO `usuarios` (`nombre`, `email`, `password_hash`, `rol`, `activo`)
VALUES (
  'Administrador',
  'admin@eighttech.com',
  '$2y$12$Qb1pSshuEpxUo/ZPO1cq/eCxK5raqO1FFk1WbkYW.jOvd.KF/XwOK',
  'admin',
  1
) ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`),
  `rol`           = 'admin',
  `activo`        = 1;
