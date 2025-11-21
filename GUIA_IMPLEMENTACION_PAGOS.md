# 🔧 Guía de Implementación - Sistema de Pagos

## ⚠️ SOLUCIÓN AL ERROR HTTP 500

El error **HTTP 500** en `admin/users.php` ocurre porque faltan columnas y tablas en la base de datos.

---

## 📋 PASO 1: Preparar Base de Datos

### 1.1 Ejecutar Script SQL

Conecta a tu base de datos MySQL y ejecuta:

```bash
mysql -u tu_usuario -p tu_base_datos < sql_usuarios_pagos.sql
```

O desde phpMyAdmin:
1. Ir a tu base de datos
2. Click en pestaña "SQL"
3. Copiar y pegar el contenido de `sql_usuarios_pagos.sql`
4. Click en "Continuar"

### 1.2 Verificar Columnas Creadas

```sql
DESCRIBE usuarios;
```

Deberías ver estas nuevas columnas:
- ✅ `monto_pagar` (DECIMAL 10,2)
- ✅ `fecha_proximo_pago` (DATE)
- ✅ `estado_pago` (TINYINT 1)
- ✅ `activo` (TINYINT 1)
- ✅ `notas_pago` (TEXT)
- ✅ `ultimo_pago` (DATE)
- ✅ `monto_ultimo_pago` (DECIMAL 10,2)

### 1.3 Verificar Tablas Creadas

```sql
SHOW TABLES LIKE 'historial_pagos';
SHOW TABLES LIKE 'recordatorios_pago';
```

Ambas deben existir.

---

## 📋 PASO 2: Reemplazar Archivo PHP

### 2.1 Hacer Backup del Original

```bash
cd /ruta/a/tu/proyecto
cp admin/users.php admin/users_backup_original.php
```

### 2.2 Reemplazar con Versión Segura

```bash
cp users_seguro.php admin/users.php
```

**Importante:** Usa `users_seguro.php` (no `users_mejorado.php`) porque:
- ✅ Valida que existan las columnas antes de ejecutar
- ✅ Muestra mensajes de error claros
- ✅ No genera HTTP 500 si falta algo
- ✅ Te indica exactamente qué SQL ejecutar

---

## 📋 PASO 3: Verificar Funcionamiento

### 3.1 Acceder al Panel de Usuarios

Abre en tu navegador:
```
https://licitacionesya.com/admin/users.php
```

### 3.2 Resultados Esperados

**✅ SI TODO ESTÁ BIEN:**
Verás:
- Cards con estadísticas (Total usuarios, Activos, Pendientes, Vencidos)
- Tabla con usuarios y sus estados de pago
- Botones de acción (Editar pago, Registrar pago, Ver historial)
- Sin errores HTTP 500

**⚠️ SI FALTA ALGO:**
Verás un mensaje rojo con:
- Lista de columnas o tablas faltantes
- Comandos SQL exactos para corregir
- Instrucciones claras

**Ejemplo de mensaje de error:**
```
⚠️ Error: Columnas Faltantes en la tabla 'usuarios'

Faltan las siguientes columnas:
- monto_pagar
- fecha_proximo_pago

Ejecuta estos comandos SQL:
ALTER TABLE usuarios ADD COLUMN monto_pagar DECIMAL(10,2) DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN fecha_proximo_pago DATE NULL;
```

---

## 📋 PASO 4: Configurar Pagos

### 4.1 Establecer Monto de Pago

Para cada usuario:
1. Click en botón "Editar pago" (azul)
2. Ingresar:
   - **Monto a pagar:** Ej: 15000.00
   - **Fecha próximo pago:** Ej: 2024-12-31
   - **Estado:** Al día / Pendiente
   - **Notas:** Opcional
3. Click en "Actualizar"

### 4.2 Registrar Pago Recibido

Cuando un usuario paga:
1. Click en botón "Registrar pago" (verde)
2. Ingresar:
   - **Monto recibido:** Lo que pagó
   - **Fecha de pago:** Cuándo pagó
   - **Método:** transferencia, sinpe, efectivo, tarjeta, otro
   - **Referencia:** Número de comprobante (opcional)
3. Click en "Registrar"

**Resultado:**
- ✅ Se guarda en tabla `historial_pagos`
- ✅ Se actualiza `ultimo_pago` y `monto_ultimo_pago` del usuario
- ✅ Puedes ver historial completo después

### 4.3 Ver Historial de Pagos

1. Click en botón "Ver historial" (gris)
2. Verás tabla con:
   - Fecha de cada pago
   - Monto pagado
   - Método usado
   - Referencia
   - Estado

---

## 📋 PASO 5: Gestión Automática

### 5.1 Desactivación Automática

El sistema **automáticamente desactiva** usuarios con:
- Estado de pago: Pendiente
- Fecha próximo pago vencida por más de 30 días

**Trigger SQL:**
```sql
CREATE TRIGGER desactivar_usuario_pago_vencido
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF NEW.fecha_proximo_pago IS NOT NULL
       AND NEW.estado_pago = 0
       AND DATEDIFF(CURDATE(), NEW.fecha_proximo_pago) > 30
    THEN
        SET NEW.activo = 0;
    END IF;
END;
```

### 5.2 Reactivar Usuario Manualmente

Si un usuario paga después de ser desactivado:
1. Registrar el pago (Paso 4.2)
2. Editar su estado (Paso 4.1) y marcar "Al día"
3. Click en switch "Activo/Inactivo" en la tabla

---

## 🎯 Características del Sistema

### Dashboard con Estadísticas

En la parte superior verás:

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ 👥 Total        │  │ ✅ Activos      │  │ ⏳ Pendientes   │  │ ❌ Vencidos     │
│    147 usuarios │  │    125 usuarios │  │    15 pagos     │  │    7 usuarios   │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

### Tabla de Usuarios

| Nombre | Email | Monto | Próximo Pago | Estado Pago | Activo | Acciones |
|--------|-------|-------|--------------|-------------|---------|----------|
| Juan P | juan@email.com | ₡15,000 | 31/12/2024 | 🟢 Al día | ✅ | 📝💰📜 |
| María G | maria@email.com | ₡20,000 | 15/11/2024 | 🔴 Pendiente | ❌ | 📝💰📜 |

### Estados de Pago

- 🟢 **Al día (verde):** Usuario pagó a tiempo
- 🔴 **Pendiente (rojo):** Usuario debe pagar

### Estados de Usuario

- ✅ **Activo (verde):** Puede acceder al sistema
- ❌ **Inactivo (rojo):** Bloqueado por falta de pago

---

## 🔍 Consultas Útiles

### Ver usuarios con pago vencido

```sql
SELECT nombre, email, fecha_proximo_pago,
       DATEDIFF(CURDATE(), fecha_proximo_pago) as dias_vencido
FROM usuarios
WHERE estado_pago = 0
  AND fecha_proximo_pago < CURDATE()
  AND es_admin = 0
ORDER BY dias_vencido DESC;
```

### Ingresos del mes actual

```sql
SELECT SUM(monto) as total_mes
FROM historial_pagos
WHERE MONTH(fecha_pago) = MONTH(CURDATE())
  AND YEAR(fecha_pago) = YEAR(CURDATE())
  AND estado = 'pagado';
```

### Historial de un usuario

```sql
SELECT fecha_pago, monto, metodo_pago, referencia
FROM historial_pagos
WHERE usuario_id = 123
ORDER BY fecha_pago DESC;
```

### Usuarios próximos a vencer (7 días)

```sql
SELECT nombre, email, fecha_proximo_pago,
       DATEDIFF(fecha_proximo_pago, CURDATE()) as dias_restantes
FROM usuarios
WHERE estado_pago = 0
  AND fecha_proximo_pago BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY dias_restantes;
```

---

## 🐛 Solución de Problemas

### Problema 1: Sigue apareciendo HTTP 500

**Causa:** No se ejecutó el script SQL o faltan columnas

**Solución:**
```bash
# Verificar columnas
mysql -u tu_usuario -p -e "DESCRIBE tu_base_datos.usuarios;"

# Si falta alguna, ejecutar de nuevo:
mysql -u tu_usuario -p tu_base_datos < sql_usuarios_pagos.sql
```

### Problema 2: Mensaje "Tabla historial_pagos no existe"

**Causa:** Script SQL no creó las tablas

**Solución:**
```sql
-- Ejecutar manualmente
CREATE TABLE IF NOT EXISTS historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NULL,
    referencia VARCHAR(100) NULL,
    estado VARCHAR(20) DEFAULT 'pagado',
    notas TEXT NULL,
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

### Problema 3: Modal no se abre

**Causa:** Falta Bootstrap JS

**Solución:**
Verificar que en el footer o header tengas:
```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
```

### Problema 4: No guarda el pago

**Causa:** Error en la inserción a historial_pagos

**Solución:**
1. Verificar que la tabla exista
2. Ver errores de PHP:
```php
// En el archivo PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
3. Revisar logs de Apache/PHP

---

## 📊 Vista del Sistema (Vista SQL Creada)

El script crea una vista llamada `vista_estado_pagos`:

```sql
SELECT * FROM vista_estado_pagos;
```

Esta vista muestra:
- ID y datos del usuario
- Estado de pago y activación
- Montos configurados y pagados
- Días hasta próximo pago
- Total de pagos registrados
- Total pagado hasta la fecha
- Último pago registrado
- Recordatorios enviados

---

## ✅ Checklist de Implementación

- [ ] Hacer backup de `admin/users.php`
- [ ] Ejecutar `sql_usuarios_pagos.sql` en la base de datos
- [ ] Verificar que columnas fueron creadas con `DESCRIBE usuarios`
- [ ] Verificar que tablas fueron creadas con `SHOW TABLES`
- [ ] Copiar `users_seguro.php` a `admin/users.php`
- [ ] Acceder a `admin/users.php` en el navegador
- [ ] Verificar que NO aparezca error HTTP 500
- [ ] Verificar que aparezcan estadísticas en cards
- [ ] Verificar que aparezca tabla de usuarios
- [ ] Probar abrir modal "Editar pago"
- [ ] Probar guardar monto y fecha de pago
- [ ] Probar abrir modal "Registrar pago"
- [ ] Probar guardar un pago recibido
- [ ] Probar abrir modal "Ver historial"
- [ ] Verificar que historial muestre los pagos
- [ ] Probar activar/desactivar usuario con el switch
- [ ] Crear índices recomendados (opcional):
```sql
CREATE INDEX idx_estado_pago ON usuarios(estado_pago);
CREATE INDEX idx_activo ON usuarios(activo);
CREATE INDEX idx_fecha_proximo_pago ON usuarios(fecha_proximo_pago);
```

---

## 🚀 Resumen de Archivos

### `sql_usuarios_pagos.sql`
- Script que modifica la base de datos
- Agrega 7 columnas a tabla `usuarios`
- Crea 2 tablas nuevas: `historial_pagos` y `recordatorios_pago`
- Crea vista `vista_estado_pagos`
- Crea trigger para desactivación automática
- Crea índices para mejorar rendimiento

### `users_seguro.php`
- Versión con validación de estructura DB
- Verifica columnas antes de ejecutar
- Muestra errores claros con soluciones
- Evita HTTP 500
- **USAR ESTE ARCHIVO** ✅

### `users_mejorado.php`
- Versión sin validación
- Asume que DB está correcta
- Puede generar HTTP 500 si falta algo
- Solo usar después de ejecutar SQL

---

## 📞 Ayuda Adicional

Si después de seguir todos los pasos sigues teniendo problemas:

1. **Verificar versión de PHP:** Necesitas PHP 7.4 o superior
2. **Verificar extensión PDO:** Debe estar habilitada
3. **Verificar permisos:** Usuario de DB debe tener permisos de ALTER y CREATE
4. **Revisar logs:**
   - Apache: `/var/log/apache2/error.log`
   - PHP-FPM: `/var/log/php-fpm/error.log`

---

**¡Sistema listo para usar!** 🎉

Una vez completados todos los pasos, tendrás un sistema completo de gestión de pagos con:
- ✅ Control de montos y fechas
- ✅ Historial de pagos
- ✅ Desactivación automática
- ✅ Reportes y estadísticas
- ✅ Interfaz profesional
