# 🚀 Importador SICOP v14.3 - Actualización Pendiente

## 📋 Cambios necesarios para crear v14.3

Para crear la versión v14.3 que soporta 2 archivos (Detalle de Carteles + DetalleCarteles), necesitas hacer los siguientes cambios en `importador_sicop_v14_2.php`:

### 1. Copiar el archivo
```bash
cp importador_sicop_v14_2.php importador_sicop_v14_3.php
```

### 2. Cambiar nombre de clase (línea 25)
```php
// ANTES:
class ImportadorSICOPv14_2 {

// DESPUÉS:
class ImportadorSICOPv14_3 {
```

### 3. Añadir estadísticas para DetalleCarteles (en función inicializarStats)
```php
private function inicializarStats() {
    $this->stats = [
        'licitaciones' => ['insertadas' => 0, 'actualizadas' => 0, 'errores' => 0],
        'partidas' => ['insertadas' => 0, 'actualizadas' => 0, 'errores' => 0],
        'detalle_carteles' => ['procesadas' => 0, 'actualizadas' => 0, 'errores' => 0],  // NUEVO
        'lineas_detectadas' => 0,
        'carteles_detectados' => 0,
    ];
}
```

### 4. Añadir nueva función (justo antes del cierre de la clase, antes de la línea "}"):

```php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * IMPORTAR DETALLECARTEL ES (INFORMACIÓN ADICIONAL)
 * ═══════════════════════════════════════════════════════════════════════
 */
public function importarDetalleCartelesAdicional($archivo) {
    $this->addLog("", 'separator');
    $this->addLog("📋 IMPORTANDO: DETALLECARTELES (INFO ADICIONAL)", 'title');
    $this->addLog("", 'separator');

    try {
        $data = $this->leerCSV($archivo);
        $header = array_shift($data); // Remover header

        $this->addLog("📊 Total de registros: " . count($data), 'info');

        // Mapear índices de columnas
        $indices = [];
        foreach ($header as $idx => $col) {
            $indices[trim($col)] = $idx;
        }

        $sql = "UPDATE licitaciones SET
                tipo_procedimiento = COALESCE(:tipo_procedimiento, tipo_procedimiento),
                modalidad = COALESCE(:modalidad, modalidad),
                fecha_apertura_ofertas = COALESCE(:fecha_apertura, fecha_apertura_ofertas),
                clasificacion_objeto = COALESCE(:clas_obj, clasificacion_objeto),
                codigo_excepcion = COALESCE(:cod_excepcion, codigo_excepcion),
                descripcion_excepcion = COALESCE(:des_excepcion, descripcion_excepcion),
                presupuesto_estimado = COALESCE(:monto_est, presupuesto_estimado),
                fecha_modificacion = COALESCE(:fecha_mod, fecha_modificacion)
                WHERE numero_sicop = :numero_sicop";

        $stmt = $this->conn->prepare($sql);

        foreach ($data as $i => $row) {
            $this->stats['detalle_carteles']['procesadas']++;

            try {
                $numero_sicop = $this->val($row, $indices, 'NRO_SICOP');

                if (empty($numero_sicop)) {
                    $this->stats['detalle_carteles']['errores']++;
                    continue;
                }

                // Procesar fecha de apertura
                $fecha_apertura = null;
                $fechah_apertura_raw = $this->val($row, $indices, 'FECHAH_APERTURA');
                if (!empty($fechah_apertura_raw)) {
                    $fecha_apertura = $this->convertirFechaHora($fechah_apertura_raw);
                }

                // Procesar fecha de modificación
                $fecha_mod = null;
                $fecha_mod_raw = $this->val($row, $indices, 'FECHA_MOD');
                if (!empty($fecha_mod_raw)) {
                    $fecha_mod = $this->convertirFechaHora($fecha_mod_raw);
                }

                // Procesar monto estimado
                $monto_est = $this->limpiarNumero($this->val($row, $indices, 'MONTO_EST'));

                $stmt->bindParam(':numero_sicop', $numero_sicop);
                $stmt->bindParam(':tipo_procedimiento', $this->val($row, $indices, 'TIPO_PROCEDIMIENTO'));
                $stmt->bindParam(':modalidad', $this->val($row, $indices, 'MODALIDAD_PROCEDIMIENTO'));
                $stmt->bindParam(':fecha_apertura', $fecha_apertura);
                $stmt->bindParam(':clas_obj', $this->val($row, $indices, 'CLAS_OBJ'));
                $stmt->bindParam(':cod_excepcion', $this->val($row, $indices, 'COD_EXCEPCION'));
                $stmt->bindParam(':des_excepcion', $this->val($row, $indices, 'DES_EXCEPCION'));
                $stmt->bindParam(':monto_est', $monto_est);
                $stmt->bindParam(':fecha_mod', $fecha_mod);

                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $this->stats['detalle_carteles']['actualizadas']++;
                }

                if (($i + 1) % 100 == 0) {
                    $this->addLog("  ⏳ Procesadas " . ($i + 1) . " registros...", 'info');
                }

            } catch (PDOException $e) {
                $this->stats['detalle_carteles']['errores']++;
                $this->addLog("  ❌ Error en SICOP {$numero_sicop}: " . $e->getMessage(), 'error');
            }
        }

        $this->addLog("✅ DetalleCarteles procesado exitosamente", 'success');
        $this->addLog("  📊 Procesadas: " . $this->stats['detalle_carteles']['procesadas'], 'success');
        $this->addLog("  ✓ Actualizadas: " . $this->stats['detalle_carteles']['actualizadas'], 'success');
        $this->addLog("  ❌ Errores: " . $this->stats['detalle_carteles']['errores'], 'success');

    } catch (Exception $e) {
        $this->addLog("❌ Error fatal al procesar DetalleCarteles: " . $e->getMessage(), 'error');
        $this->stats['detalle_carteles']['errores']++;
    }
}
```

### 5. Modificar la instanciación de la clase (buscar "new ImportadorSICOPv14_2"):
```php
// ANTES:
$imp = new ImportadorSICOPv14_2($conn);

// DESPUÉS:
$imp = new ImportadorSICOPv14_3($conn);
```

### 6. Modificar el procesamiento POST para añadir el segundo archivo:

Buscar esta sección:
```php
$archivo = $_FILES['archivo'];
if ($archivo['error'] == 0) {
    $imp->importarDetalleCartelesNuevo($archivo['tmp_name']);
}
```

Y AÑADIR después:
```php
// Procesar archivo adicional (DetalleCarteles) si existe
if (isset($_FILES['archivo_detalle']) && $_FILES['archivo_detalle']['error'] == 0) {
    $imp->importarDetalleCartelesAdicional($_FILES['archivo_detalle']['tmp_name']);
}
```

### 7. Modificar el formulario HTML (buscar el input de archivo):

Buscar:
```html
<input type="file" name="archivo" id="fileInput" accept=".csv" required>
```

Y reemplazar toda la sección del formulario por:
```html
<div class="upload-area" onclick="document.getElementById('fileInput').click()">
    <h3>📁 Archivo 1: Detalle de Carteles (requerido)</h3>
    <p style="color: #666; margin: 10px 0;">Archivo principal con licitaciones y partidas</p>
    <label for="fileInput" class="file-label">Examinar Archivo</label>
    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
</div>

<div class="upload-area" onclick="document.getElementById('fileInputDetalle').click()" style="border-style: dashed;">
    <h3>📋 Archivo 2: DetalleCarteles (opcional)</h3>
    <p style="color: #666; margin: 10px 0;">Información adicional de licitaciones</p>
    <label for="fileInputDetalle" class="file-label">Examinar Archivo (Opcional)</label>
    <input type="file" name="archivo_detalle" id="fileInputDetalle" accept=".csv">
</div>
```

### 8. Actualizar títulos en HTML:

Buscar y reemplazar:
- `v14.2` → `v14.3`
- `Detección Inteligente por Contenido` → `Con soporte para DetalleCarteles`

## 🎯 Campos procesados desde DetalleCarteles

El archivo DetalleCarteles actualiza estos campos en licitaciones existentes:
- `tipo_procedimiento` ← TIPO_PROCEDIMIENTO
- `modalidad` ← MODALIDAD_PROCEDIMIENTO
- `fecha_apertura_ofertas` ← FECHAH_APERTURA
- `clasificacion_objeto` ← CLAS_OBJ
- `codigo_excepcion` ← COD_EXCEPCION
- `descripcion_excepcion` ← DES_EXCEPCION
- `presupuesto_estimado` ← MONTO_EST
- `fecha_modificacion` ← FECHA_MOD

## 📖 Uso

1. Sube primero el archivo "Detalle de Carteles" (requerido)
2. Opcionalmente, sube también "DetalleCarteles" para información adicional
3. El sistema procesará ambos archivos automáticamente

