# Módulo de Salud Ocupacional
## Sistema de Gestión de Avisos de Accidentes Laborales

---

## 📋 Descripción

Sistema completo para la gestión de avisos de accidentes laborales que permite a los empleados reportar incidentes de forma rápida y eficiente, y al departamento de Salud Ocupacional administrar y dar seguimiento a cada caso.

---

## 🚀 Instalación

### Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP requeridas:
  - PDO
  - pdo_mysql
  - json
  - fileinfo

### Pasos de Instalación

1. **Subir los archivos** al directorio `public_html/SO/` de su servidor

2. **Acceder al instalador** navegando a:
   ```
   https://expenicrhs.com/SO/instalar.php
   ```

3. **Ejecutar la instalación**
   - El instalador creará automáticamente:
     - Tabla `avisos_accidentes`
     - Tabla `avisos_accidentes_seguimiento`
     - Directorios de uploads necesarios

4. **Verificar la instalación**
   - Si todo está correcto, verá un mensaje de "Instalación Completada"
   - Los directorios de uploads tendrán permisos 755

---

## 📁 Estructura de Archivos

```
SO/
├── config.php                     # Configuración de base de datos y funciones
├── index.php                      # Página de inicio
├── instalar.php                   # Instalador del módulo
├── nuevo_aviso.php                # Formulario de reporte de accidentes
├── procesar_aviso.php             # Procesamiento del formulario
├── exito.php                      # Página de confirmación
├── admin_avisos.php               # Panel de administración
├── ver_aviso.php                  # Vista detallada de un aviso
├── crear_tabla_avisos_accidentes.sql  # Script SQL manual
├── README.md                      # Este archivo
└── uploads/
    ├── lesiones/                  # Fotos de lesiones
    └── areas/                     # Fotos de áreas donde ocurrió el accidente
```

---

## 🎯 Características Principales

### Para Empleados

1. **Formulario Intuitivo**
   - Interfaz amigable y fácil de usar
   - Validaciones en tiempo real
   - Campos obligatorios claramente marcados

2. **Información Completa**
   - Datos personales del empleado
   - Detalles del incidente
   - Ubicación exacta
   - Partes del cuerpo afectadas
   - Tipo de lesión
   - Testigos
   - Declaración del afectado

3. **Evidencias Fotográficas**
   - Subida de fotos de la lesión
   - Fotos del área donde ocurrió
   - Formatos soportados: JPG, PNG, GIF
   - Tamaño máximo: 5MB por imagen

4. **Confirmación Inmediata**
   - Número de aviso generado automáticamente
   - Confirmación por pantalla
   - Instrucciones sobre próximos pasos

### Para Administradores

1. **Panel de Administración**
   - Vista de todos los avisos
   - Estadísticas en tiempo real
   - Filtros avanzados de búsqueda

2. **Gestión de Estados**
   - Pendiente
   - En Revisión
   - Cerrado

3. **Vista Detallada**
   - Información completa del aviso
   - Visualización de evidencias
   - Historial de seguimiento
   - Opción de impresión

4. **Búsqueda y Filtrado**
   - Por nombre, cédula o departamento
   - Por estado del aviso
   - Por rango de fechas

---

## 📝 Uso del Sistema

### Reportar un Nuevo Accidente

1. Acceda a: `https://expenicrhs.com/SO/nuevo_aviso.php`
2. Complete todos los campos marcados con asterisco (*)
3. Seleccione las partes del cuerpo afectadas
4. Indique el tipo de lesión
5. Adjunte evidencias fotográficas (opcional pero recomendado)
6. Escriba su declaración con el mayor detalle posible
7. Confirme la veracidad de la información
8. Haga clic en "Enviar Aviso de Accidente"
9. Anote su número de aviso para futuras referencias

### Administrar Avisos

1. Acceda a: `https://expenicrhs.com/SO/admin_avisos.php`
2. Visualice las estadísticas generales
3. Use los filtros para buscar avisos específicos
4. Haga clic en "Ver" para ver los detalles completos
5. Puede imprimir el aviso usando el botón de impresión

---

## 🗄️ Estructura de la Base de Datos

### Tabla: `avisos_accidentes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del aviso |
| nombre_completo | VARCHAR(255) | Nombre del empleado |
| cedula | VARCHAR(50) | Cédula del empleado |
| puesto | VARCHAR(150) | Puesto de trabajo |
| departamento | VARCHAR(150) | Departamento |
| supervisor_inmediato | VARCHAR(255) | Nombre del supervisor |
| fecha_incidente | DATE | Fecha del accidente |
| hora_incidente | TIME | Hora del accidente |
| lugar_exacto | TEXT | Ubicación exacta |
| actividad_realizaba | TEXT | Actividad al momento del accidente |
| descripcion_accidente | TEXT | Descripción detallada |
| partes_cuerpo_afectadas | JSON | Array de partes afectadas |
| otra_parte_cuerpo | VARCHAR(255) | Otra parte especificada |
| tipo_lesion | VARCHAR(100) | Tipo de lesión |
| otra_lesion | VARCHAR(255) | Otra lesión especificada |
| recibio_atencion_inmediata | ENUM | Sí/No |
| descripcion_atencion | TEXT | Descripción de atención |
| hubo_testigos | ENUM | Sí/No |
| testigo_1 | VARCHAR(255) | Nombre testigo 1 |
| testigo_2 | VARCHAR(255) | Nombre testigo 2 |
| declaracion_afectado | TEXT | Declaración completa |
| foto_lesion | VARCHAR(255) | Ruta de foto de lesión |
| foto_area | VARCHAR(255) | Ruta de foto del área |
| firma_digital | VARCHAR(255) | Firma digital |
| fecha_llenado | DATETIME | Fecha de registro |
| estado | ENUM | pendiente/en_revision/cerrado |
| fecha_registro | TIMESTAMP | Timestamp de creación |
| fecha_actualizacion | TIMESTAMP | Timestamp de actualización |

### Tabla: `avisos_accidentes_seguimiento`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único |
| aviso_id | INT | ID del aviso relacionado |
| usuario | VARCHAR(150) | Usuario que realizó la acción |
| accion | VARCHAR(100) | Tipo de acción |
| comentario | TEXT | Comentario adicional |
| fecha_accion | TIMESTAMP | Fecha de la acción |

---

## 🔒 Seguridad

- **Protección CSRF**: Todos los formularios incluyen token CSRF
- **Validación de Entrada**: Sanitización de todos los datos recibidos
- **Validación de Archivos**:
  - Solo imágenes permitidas
  - Verificación de tipo MIME
  - Límite de tamaño (5MB)
- **Prepared Statements**: Protección contra inyección SQL
- **Manejo de Errores**: Los errores no exponen información sensible

---

## ⚙️ Configuración

### Archivo `config.php`

Las principales configuraciones se encuentran en este archivo:

```php
// Base de datos
$host = 'localhost';
$dbname = 'u656059172_SistemaRH';
$username = 'u656059172_SistemaRH';
$password = '~7JB>d0v';

// URLs
define('SO_BASE_URL', 'https://expenicrhs.com/SO');

// Límites de archivos
define('SO_MAX_FILE_SIZE', 5242880); // 5MB

// Tipos de archivo permitidos
define('SO_ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif'
]);
```

---

## 🎨 Personalización

### Colores y Estilos

Los estilos están incluidos en cada archivo PHP para facilitar la personalización. Los colores principales son:

- Primario: `#667eea` (Azul/Púrpura)
- Secundario: `#764ba2` (Púrpura)
- Éxito: `#27ae60` (Verde)
- Advertencia: `#f39c12` (Naranja)
- Peligro: `#e74c3c` (Rojo)

---

## 🐛 Solución de Problemas

### Error HTTP 500

1. Verifique que las tablas estén creadas
2. Ejecute `instalar.php` nuevamente
3. Verifique los permisos de los directorios de uploads
4. Revise los logs de PHP del servidor

### Imágenes No Se Suben

1. Verifique permisos del directorio `uploads/` (755)
2. Verifique el tamaño máximo de upload en `php.ini`
3. Confirme que el tipo de archivo sea permitido

### Error de Conexión a BD

1. Verifique las credenciales en `config.php`
2. Confirme que la base de datos existe
3. Verifique que el usuario tenga permisos adecuados

---

## 📊 Estadísticas y Reportes

El panel de administración muestra:

- **Total de Avisos**: Número total de avisos registrados
- **Pendientes**: Avisos que requieren atención
- **En Revisión**: Avisos siendo investigados
- **Cerrados**: Casos completados

---

## 🔄 Actualizaciones Futuras

Mejoras planificadas:

- [ ] Exportación a PDF
- [ ] Exportación a Excel
- [ ] Notificaciones por email
- [ ] Dashboard con gráficos
- [ ] Firma digital con canvas
- [ ] Aplicación móvil
- [ ] API REST

---

## 📞 Soporte

Para soporte técnico o reportar problemas:

- Email: soporte@expenicrhs.com
- Sistema: Salud Ocupacional v1.0

---

## 📄 Licencia

Este sistema es propiedad de EXPENIC RHS y es de uso interno exclusivo.

---

## 👥 Créditos

Desarrollado para el Departamento de Salud Ocupacional
Versión: 1.0
Fecha: 2024

---

## ✅ Checklist de Instalación

- [ ] Archivos subidos al servidor
- [ ] Ejecutado `instalar.php`
- [ ] Tablas creadas en la base de datos
- [ ] Directorios de uploads con permisos correctos
- [ ] Probado formulario de nuevo aviso
- [ ] Verificado panel de administración
- [ ] Configurado acceso para administradores
- [ ] Documentado el proceso internamente

---

**¡Gracias por usar el Sistema de Salud Ocupacional!**
