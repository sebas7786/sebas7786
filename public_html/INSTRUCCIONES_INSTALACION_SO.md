# 📦 INSTRUCCIONES DE INSTALACIÓN - Módulo de Salud Ocupacional

## ⚠️ PROBLEMA DETECTADO

Los archivos están en el repositorio Git local pero **NO están en el servidor de producción**.

**Ruta Local (Git):** `/home/user/sebas7786/public_html/SO/`

**Ruta Servidor:** `/home/u656059172/domains/expenicrhs.com/public_html/SO/`

---

## ✅ SOLUCIÓN: Subir Archivos al Servidor

### Método 1: Subir por FTP/cPanel (RECOMENDADO)

#### Paso 1: Descargar los Archivos del Repositorio

Todos los archivos están en el repositorio Git en la rama:
```
claude/occupational-health-form-01CX47RwWnz3eLDbhEixgmFn
```

También se creó un archivo comprimido:
```
/home/user/sebas7786/public_html/SO_modulo_completo.tar.gz
```

#### Paso 2: Conectar al Servidor

1. Abre tu cliente FTP (FileZilla, WinSCP, o el Administrador de Archivos de cPanel)
2. Conéctate a tu servidor con tus credenciales
3. Navega a la carpeta:
   ```
   /home/u656059172/domains/expenicrhs.com/public_html/
   ```

#### Paso 3: Crear el Directorio SO

Si no existe, crea la carpeta `SO`:
```
/home/u656059172/domains/expenicrhs.com/public_html/SO/
```

#### Paso 4: Subir los Archivos

Sube TODOS estos archivos a la carpeta `SO/`:

**Archivos PHP (obligatorios):**
- ✅ config.php
- ✅ index.php
- ✅ nuevo_aviso.php
- ✅ procesar_aviso.php
- ✅ exito.php
- ✅ admin_avisos.php
- ✅ ver_aviso.php
- ✅ instalar.php
- ✅ debug.php
- ✅ test.php
- ✅ nuevo_aviso_debug.php

**Archivos Adicionales:**
- ✅ crear_tabla_avisos_accidentes.sql
- ✅ README.md

**Directorios (crear vacíos si no existen):**
- ✅ uploads/
- ✅ uploads/lesiones/
- ✅ uploads/areas/
- ✅ css/ (opcional)
- ✅ js/ (opcional)

#### Paso 5: Verificar Permisos

Asegúrate de que los directorios tengan los permisos correctos:
- Directorios: **755**
- Archivos PHP: **644**
- Directorio `uploads/`: **755** (debe ser escribible)

---

### Método 2: Deploy Automático desde Git (Si tienes acceso SSH)

Si tienes acceso SSH al servidor:

```bash
# Conectar al servidor
ssh usuario@expenicrhs.com

# Ir al directorio de producción
cd /home/u656059172/domains/expenicrhs.com/

# Si el repositorio no está clonado:
git clone [URL_DEL_REPOSITORIO] .

# Si ya está clonado:
git fetch origin
git checkout claude/occupational-health-form-01CX47RwWnz3eLDbhEixgmFn
git pull origin claude/occupational-health-form-01CX47RwWnz3eLDbhEixgmFn

# Verificar que los archivos estén ahí
ls -la public_html/SO/
```

---

## 🚀 DESPUÉS DE SUBIR LOS ARCHIVOS

### 1. Verificar que PHP Funciona
```
https://expenicrhs.com/SO/test.php
```
**Debe mostrar:** "PHP está funcionando correctamente"

### 2. Ejecutar el Diagnóstico
```
https://expenicrhs.com/SO/debug.php
```
**Debe mostrar:** Todo en verde ✓ (excepto posiblemente las tablas)

### 3. Instalar la Base de Datos
```
https://expenicrhs.com/SO/instalar.php
```
**Debe mostrar:** "Instalación Completada"

### 4. Probar el Formulario
```
https://expenicrhs.com/SO/nuevo_aviso.php
```
**Debe mostrar:** El formulario completo de avisos de accidentes

---

## 📋 Checklist de Verificación

Marca cada paso conforme lo completes:

- [ ] Archivos subidos al servidor
- [ ] Directorio `uploads/` creado con permisos 755
- [ ] `test.php` funciona correctamente
- [ ] `debug.php` muestra todo en verde ✓
- [ ] `instalar.php` ejecutado exitosamente
- [ ] Tablas creadas en la base de datos
- [ ] `nuevo_aviso.php` muestra el formulario
- [ ] Formulario se puede enviar correctamente
- [ ] `admin_avisos.php` muestra el panel de administración

---

## ❓ Si Persisten Problemas

### Error: "config.php not found"
- **Causa:** El archivo config.php no está en el servidor
- **Solución:** Sube el archivo config.php al directorio SO/

### Error: "Cannot connect to database"
- **Causa:** Credenciales incorrectas
- **Solución:** Verifica las credenciales en config.php líneas 16-19

### Error: "Table doesn't exist"
- **Causa:** Las tablas no están creadas
- **Solución:** Ejecuta https://expenicrhs.com/SO/instalar.php

### Error: "Permission denied" en uploads
- **Causa:** Permisos incorrectos
- **Solución:** Cambia permisos de uploads/ a 755 o 777

---

## 📞 Soporte

Si después de seguir todos los pasos el problema persiste:

1. Ejecuta `debug.php` y anota todos los mensajes
2. Toma capturas de pantalla de los errores
3. Verifica que TODOS los archivos estén en el servidor

---

## 🎉 ¡Listo!

Una vez completados todos los pasos, tu módulo de Salud Ocupacional estará funcionando correctamente en:

- **Formulario:** https://expenicrhs.com/SO/nuevo_aviso.php
- **Admin:** https://expenicrhs.com/SO/admin_avisos.php
- **Inicio:** https://expenicrhs.com/SO/

---

**Última actualización:** 21 de Noviembre, 2024
**Versión del módulo:** 1.0
