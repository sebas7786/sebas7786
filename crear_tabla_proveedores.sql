-- ═══════════════════════════════════════════════════════════════════════
-- TABLA DE PROVEEDORES - LicitacionesYA
-- ═══════════════════════════════════════════════════════════════════════
-- Esta tabla almacena información de proveedores que participan en licitaciones
--
-- EJECUTAR EN phpMyAdmin > SQL
-- ═══════════════════════════════════════════════════════════════════════

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 1: Verificar si existe tabla anterior
-- ═══════════════════════════════════════════════════════════════════════

-- Mostrar tabla existente (si existe)
SHOW TABLES LIKE 'proveedores';
SHOW TABLES LIKE 'proveedoras';

-- Si existe tabla "proveedoras", puedes renombrarla (descomenta si es necesario)
-- RENAME TABLE proveedoras TO proveedores;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 2: Crear tabla de proveedores
-- ═══════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS proveedores (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Identificación del proveedor
    cedula_proveedor VARCHAR(50) NOT NULL UNIQUE COMMENT 'Cédula jurídica o física',
    nombre_proveedor VARCHAR(255) NOT NULL COMMENT 'Nombre o razón social',

    -- Clasificación
    tipo_proveedor ENUM('Física', 'Jurídica', 'Extranjera', 'Consorcio', 'Otro') DEFAULT 'Jurídica' COMMENT 'Tipo de proveedor',
    tamano_proveedor ENUM('Micro', 'Pequeña', 'Mediana', 'Grande', 'No Especificado') DEFAULT 'No Especificado' COMMENT 'Tamaño según facturación',

    -- Fechas importantes
    fecha_constitucion DATE NULL COMMENT 'Fecha de constitución de la empresa',
    fecha_expiracion DATE NULL COMMENT 'Fecha de expiración de registro/licencia',

    -- Ubicación
    zona_geo_prov VARCHAR(100) NULL COMMENT 'Provincia o zona geográfica',
    direccion TEXT NULL COMMENT 'Dirección completa',

    -- Información de contacto
    telefono VARCHAR(50) NULL COMMENT 'Teléfono de contacto',
    email VARCHAR(255) NULL COMMENT 'Correo electrónico',
    sitio_web VARCHAR(255) NULL COMMENT 'Sitio web',

    -- Información adicional
    actividad_economica VARCHAR(255) NULL COMMENT 'Descripción de actividad económica',
    codigo_actividad VARCHAR(50) NULL COMMENT 'Código CIIU o similar',

    -- Estado y registro
    estado_proveedor ENUM('Activo', 'Inactivo', 'Suspendido', 'Inhabilitado') DEFAULT 'Activo' COMMENT 'Estado del proveedor',
    motivo_inhabilitacion TEXT NULL COMMENT 'Razón de inhabilitación si aplica',

    -- Estadísticas
    total_adjudicaciones INT(11) DEFAULT 0 COMMENT 'Total de licitaciones adjudicadas',
    monto_total_adjudicado DECIMAL(20,2) DEFAULT 0.00 COMMENT 'Monto total adjudicado histórico',
    ultima_adjudicacion DATE NULL COMMENT 'Fecha de última adjudicación',

    -- Metadatos
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro en el sistema',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última actualización',
    registrado_por INT(11) UNSIGNED NULL COMMENT 'Usuario admin que registró',

    -- Notas internas
    notas TEXT NULL COMMENT 'Notas internas sobre el proveedor',

    -- Índices para búsquedas rápidas
    INDEX idx_cedula (cedula_proveedor),
    INDEX idx_nombre (nombre_proveedor),
    INDEX idx_tipo (tipo_proveedor),
    INDEX idx_zona (zona_geo_prov),
    INDEX idx_estado (estado_proveedor),
    INDEX idx_fecha_registro (fecha_registro)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de proveedores del sistema de licitaciones';

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 3: Migrar datos si existe tabla anterior "proveedoras"
-- ═══════════════════════════════════════════════════════════════════════

-- Verificar si existe tabla proveedoras
SET @tabla_existe = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'proveedoras'
);

-- Si existe, migrar datos (descomenta si es necesario)
/*
INSERT INTO proveedores (
    cedula_proveedor,
    nombre_proveedor,
    zona_geo_prov,
    fecha_registro
)
SELECT
    cedula,
    nombre_proveedor,
    provincia,
    NOW()
FROM proveedoras
ON DUPLICATE KEY UPDATE
    nombre_proveedor = VALUES(nombre_proveedor),
    zona_geo_prov = VALUES(zona_geo_prov);
*/

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 4: Crear trigger para actualizar estadísticas automáticamente
-- ═══════════════════════════════════════════════════════════════════════

DELIMITER $$

-- Trigger cuando se adjudica una línea a un proveedor
CREATE TRIGGER actualizar_estadisticas_proveedor
AFTER INSERT ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    -- Actualizar estadísticas del proveedor
    UPDATE proveedores
    SET
        total_adjudicaciones = total_adjudicaciones + 1,
        monto_total_adjudicado = monto_total_adjudicado + COALESCE(NEW.monto_adjudicado, 0),
        ultima_adjudicacion = COALESCE(NEW.fecha_adjudicacion, CURDATE())
    WHERE cedula_proveedor = NEW.cedula_proveedor;

    -- Si el proveedor no existe, crearlo automáticamente
    INSERT IGNORE INTO proveedores (
        cedula_proveedor,
        nombre_proveedor,
        total_adjudicaciones,
        monto_total_adjudicado,
        ultima_adjudicacion
    )
    VALUES (
        NEW.cedula_proveedor,
        COALESCE(NEW.nombre_proveedor, 'Sin nombre'),
        1,
        COALESCE(NEW.monto_adjudicado, 0),
        COALESCE(NEW.fecha_adjudicacion, CURDATE())
    );
END$$

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 5: Importar proveedores desde lineas_adjudicadas (si existen)
-- ═══════════════════════════════════════════════════════════════════════

-- Crear proveedores basados en adjudicaciones existentes
INSERT INTO proveedores (
    cedula_proveedor,
    nombre_proveedor,
    total_adjudicaciones,
    monto_total_adjudicado,
    ultima_adjudicacion
)
SELECT
    la.cedula_proveedor,
    MAX(la.nombre_proveedor) as nombre_proveedor,
    COUNT(*) as total_adjudicaciones,
    SUM(COALESCE(la.monto_adjudicado, 0)) as monto_total_adjudicado,
    MAX(la.fecha_adjudicacion) as ultima_adjudicacion
FROM lineas_adjudicadas la
WHERE la.cedula_proveedor IS NOT NULL
  AND la.cedula_proveedor != ''
GROUP BY la.cedula_proveedor
ON DUPLICATE KEY UPDATE
    nombre_proveedor = VALUES(nombre_proveedor),
    total_adjudicaciones = VALUES(total_adjudicaciones),
    monto_total_adjudicado = VALUES(monto_total_adjudicado),
    ultima_adjudicacion = VALUES(ultima_adjudicacion);

-- Ver cuántos proveedores se crearon
SELECT COUNT(*) as total_proveedores FROM proveedores;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 6: Crear vista para ranking de proveedores
-- ═══════════════════════════════════════════════════════════════════════

CREATE OR REPLACE VIEW ranking_proveedores AS
SELECT
    p.cedula_proveedor,
    p.nombre_proveedor,
    p.tipo_proveedor,
    p.tamano_proveedor,
    p.zona_geo_prov,
    p.total_adjudicaciones,
    p.monto_total_adjudicado,
    p.ultima_adjudicacion,
    p.estado_proveedor,
    RANK() OVER (ORDER BY p.monto_total_adjudicado DESC) as ranking_por_monto,
    RANK() OVER (ORDER BY p.total_adjudicaciones DESC) as ranking_por_cantidad
FROM proveedores p
WHERE p.estado_proveedor = 'Activo'
ORDER BY p.monto_total_adjudicado DESC;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 7: Datos de ejemplo (opcional, solo para pruebas)
-- ═══════════════════════════════════════════════════════════════════════

/*
INSERT INTO proveedores (
    cedula_proveedor,
    nombre_proveedor,
    tipo_proveedor,
    tamano_proveedor,
    zona_geo_prov,
    fecha_constitucion,
    telefono,
    email,
    estado_proveedor
) VALUES
(
    '3-101-123456',
    'Empresa Ejemplo S.A.',
    'Jurídica',
    'Mediana',
    'San José',
    '2015-01-15',
    '2222-3333',
    'contacto@ejemplo.com',
    'Activo'
),
(
    '1-0234-5678',
    'Juan Pérez Solano',
    'Física',
    'Micro',
    'Heredia',
    NULL,
    '8888-9999',
    'juan@correo.com',
    'Activo'
);
*/

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 8: Verificación final
-- ═══════════════════════════════════════════════════════════════════════

-- Ver estructura de la tabla
DESCRIBE proveedores;

-- Ver estadísticas
SELECT
    estado_proveedor,
    COUNT(*) as cantidad,
    SUM(total_adjudicaciones) as total_adj,
    SUM(monto_total_adjudicado) as total_monto
FROM proveedores
GROUP BY estado_proveedor;

-- Top 10 proveedores por monto
SELECT
    cedula_proveedor,
    nombre_proveedor,
    total_adjudicaciones,
    monto_total_adjudicado,
    zona_geo_prov
FROM proveedores
ORDER BY monto_total_adjudicado DESC
LIMIT 10;

-- Ver triggers creados
SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';

-- ═══════════════════════════════════════════════════════════════════════
-- NOTAS IMPORTANTES
-- ═══════════════════════════════════════════════════════════════════════

/*
✅ TABLA CREADA CON:
- Todos los campos solicitados
- Campos adicionales útiles (contacto, estadísticas, etc.)
- Índices para búsquedas rápidas
- Trigger automático para actualizar estadísticas
- Vista de ranking

🔄 TRIGGER AUTOMÁTICO:
- Cuando se inserta una adjudicación, actualiza estadísticas del proveedor
- Si el proveedor no existe, lo crea automáticamente
- Mantiene sincronizados: total_adjudicaciones, monto_total, última_fecha

📊 VISTA DE RANKING:
- Muestra proveedores ordenados por monto y cantidad
- Útil para estadísticas y reportes

⚡ RENDIMIENTO:
- Índices optimizados para búsquedas rápidas
- Trigger eficiente (microsegundos)
- Preparado para miles de registros

🔧 PRÓXIMOS PASOS:
1. Ejecutar este SQL en phpMyAdmin
2. Usar el script PHP de importación para agregar proveedores masivamente
3. Usar la interfaz admin para gestionar proveedores
*/

SELECT
    '✅ Tabla de proveedores creada correctamente' AS resultado,
    (SELECT COUNT(*) FROM proveedores) AS total_proveedores;
