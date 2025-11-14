-- ═══════════════════════════════════════════════════════════════════════
-- CREAR USUARIO ADMINISTRADOR
-- ═══════════════════════════════════════════════════════════════════════
-- Usuario: sebas@licitacionesya.com
-- Contraseña: Sebas778
-- Rol: Administrador
-- ═══════════════════════════════════════════════════════════════════════

USE u613395717_licitahoy1;

-- ──────────────────────────────────────────────────────────────────────
-- PASO 1: Verificar si el usuario ya existe
-- ──────────────────────────────────────────────────────────────────────
SELECT
    id,
    nombre,
    email,
    es_admin,
    estado_pago,
    fecha_creacion
FROM usuarios
WHERE email = 'sebas@licitacionesya.com';

-- ──────────────────────────────────────────────────────────────────────
-- PASO 2: Crear o actualizar el usuario administrador
-- ──────────────────────────────────────────────────────────────────────
INSERT INTO usuarios (
    nombre,
    email,
    correo,
    contrasena,
    es_admin,
    estado_pago,
    recibir_alertas_email,
    frecuencia_alertas,
    tiene_perfil_directorio,
    fecha_creacion
) VALUES (
    'Administrador',
    'sebas@licitacionesya.com',
    'sebas@licitacionesya.com',
    '$2y$12$vInLCppCeEUjy9wfmCW0MuDKCcylonkptWu6Y2hjSx68BWFI4rHI.',
    1,
    1,
    1,
    'inmediata',
    0,
    NOW()
) ON DUPLICATE KEY UPDATE
    nombre = 'Administrador',
    contrasena = '$2y$12$vInLCppCeEUjy9wfmCW0MuDKCcylonkptWu6Y2hjSx68BWFI4rHI.',
    es_admin = 1,
    estado_pago = 1;

-- ──────────────────────────────────────────────────────────────────────
-- PASO 3: Verificar que se creó correctamente
-- ──────────────────────────────────────────────────────────────────────
SELECT
    id,
    nombre,
    email,
    correo,
    es_admin,
    estado_pago,
    recibir_alertas_email,
    frecuencia_alertas,
    fecha_creacion
FROM usuarios
WHERE email = 'sebas@licitacionesya.com';

-- ═══════════════════════════════════════════════════════════════════════
-- CREDENCIALES DE ACCESO
-- ═══════════════════════════════════════════════════════════════════════
--
-- Email/Usuario: sebas@licitacionesya.com
-- Contraseña:     Sebas778
-- Rol:            Administrador (es_admin = 1)
-- Estado:         Activo (estado_pago = 1)
--
-- ═══════════════════════════════════════════════════════════════════════
-- CARACTERÍSTICAS DEL USUARIO CREADO
-- ═══════════════════════════════════════════════════════════════════════
--
-- ✓ Acceso completo de administrador
-- ✓ Cuenta activa y verificada
-- ✓ Puede recibir alertas por email
-- ✓ Frecuencia de alertas: inmediata
-- ✓ Contraseña hasheada con bcrypt (segura)
--
-- ═══════════════════════════════════════════════════════════════════════
-- RECOMENDACIONES DE SEGURIDAD
-- ═══════════════════════════════════════════════════════════════════════
--
-- 1. Cambia la contraseña después del primer inicio de sesión
-- 2. Usa una contraseña más fuerte con al menos:
--    - 12 caracteres
--    - Mayúsculas y minúsculas
--    - Números
--    - Símbolos especiales
--
-- 3. Activa autenticación de dos factores (si está disponible)
-- 4. No compartas estas credenciales
-- 5. Guarda este archivo en un lugar seguro o elimínalo después de usar
--
-- ═══════════════════════════════════════════════════════════════════════
