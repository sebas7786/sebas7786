-- Tabla para avisos de accidentes laborales
-- Base de datos: u656059172_SistemaRH

CREATE TABLE IF NOT EXISTS avisos_accidentes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- 1. Información personal
    nombre_completo VARCHAR(255) NOT NULL,
    cedula VARCHAR(50) NOT NULL,
    puesto VARCHAR(150) NOT NULL,
    departamento VARCHAR(150) NOT NULL,
    supervisor_inmediato VARCHAR(255) NOT NULL,
    fecha_incidente DATE NOT NULL,
    hora_incidente TIME NOT NULL,

    -- 2. Información del lugar
    lugar_exacto TEXT NOT NULL,

    -- 3. Actividad que realizaba
    actividad_realizaba TEXT NOT NULL,

    -- 4. Descripción del accidente
    descripcion_accidente TEXT NOT NULL,

    -- 5. Parte del cuerpo afectada (almacenado como JSON array)
    partes_cuerpo_afectadas JSON,
    otra_parte_cuerpo VARCHAR(255),

    -- 6. Tipo de lesión
    tipo_lesion VARCHAR(100) NOT NULL,
    otra_lesion VARCHAR(255),

    -- 7. Atención inmediata
    recibio_atencion_inmediata ENUM('si', 'no') NOT NULL,
    descripcion_atencion TEXT,

    -- 8. Testigos
    hubo_testigos ENUM('si', 'no') NOT NULL,
    testigo_1 VARCHAR(255),
    testigo_2 VARCHAR(255),

    -- 9. Declaración del afectado
    declaracion_afectado TEXT NOT NULL,

    -- 10. Evidencias (rutas de archivos)
    foto_lesion VARCHAR(255),
    foto_area VARCHAR(255),

    -- 11. Confirmación
    firma_digital VARCHAR(255),
    fecha_llenado DATETIME NOT NULL,

    -- Campos de control
    estado ENUM('pendiente', 'en_revision', 'cerrado') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_cedula (cedula),
    INDEX idx_fecha_incidente (fecha_incidente),
    INDEX idx_departamento (departamento),
    INDEX idx_estado (estado),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para seguimiento de acciones sobre los avisos
CREATE TABLE IF NOT EXISTS avisos_accidentes_seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aviso_id INT NOT NULL,
    usuario VARCHAR(150) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    comentario TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (aviso_id) REFERENCES avisos_accidentes(id) ON DELETE CASCADE,
    INDEX idx_aviso_id (aviso_id),
    INDEX idx_fecha_accion (fecha_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
