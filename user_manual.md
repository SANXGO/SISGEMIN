# Manual de Código - Sistema de Gestión de Instrumentos y Mantenimiento

## Introducción
Este manual describe la estructura y funcionamiento general del sistema desarrollado para la gestión de instrumentos, mantenimiento, manuales, usuarios y planificación en una planta industrial. El objetivo es facilitar la comprensión, mantenimiento y extensión del código.

## Arquitectura General
El sistema está desarrollado en PHP con una arquitectura modular. Utiliza una base de datos MySQL para almacenamiento y PDO para la conexión segura. La interfaz web usa Bootstrap para estilos y componentes, y JavaScript para interactividad.

### Estructura de Carpetas Principales
- `assets/`: Contiene recursos estáticos como CSS, JS, imágenes y fuentes.
- `equipo/`: Módulo para gestión de equipos e instrumentos.
- `manual/`: Gestión de manuales y categorías.
- `planificacion/`: Módulo de planificación mensual de actividades.
- `usuario/`: Gestión de usuarios y roles.
- `includes/`: Archivos comunes como conexión a base de datos, permisos y auditoría.
- `Mantenimiento/`, `repuestos/`, `ubicacion/`, `historial/`, `planta/`: Otros módulos específicos del sistema.
- `private/`: Archivos privados, como logs de auditoría.

## Autenticación y Autorización
- Se usa PHP sessions para manejar la autenticación.
- `includes/check_permission.php` verifica permisos basados en el rol del usuario (`id_cargo`) y el módulo accedido.
- En `index.php` se controla el acceso a módulos y se redirige a páginas de login o sin permiso según corresponda.

## Conexión a Base de Datos
- `includes/conexion.php` establece la conexión PDO con MySQL.
- Configura manejo de errores, modo de fetch y deshabilita emulación de prepared statements para seguridad.

## Punto de Entrada Principal (`index.php`)
- Verifica sesión y permisos.
- Obtiene módulos permitidos para el usuario y los agrupa en categorías para el menú lateral.
- Muestra un dashboard con estadísticas de equipos, mantenimientos, manuales y repuestos.
- Carga dinámicamente módulos según el parámetro `tabla` en la URL.

## Módulos Principales

### Equipo (`equipo/equipos.php`)
- Muestra lista de equipos activos filtrados por planta del usuario.
- Permite búsqueda por Tag Number y ubicación.
- Modal para agregar nuevo equipo con pestañas para datos básicos, técnicos, configuración y otros.
- Validaciones en frontend y backend, con AJAX para validar unicidad de Tag Number.
- Usa funciones de auditoría para registrar accesos.

### Manual (`manual/manual.php`)
- Gestión de categorías de manuales filtradas por planta.
- Tabla con búsqueda dinámica.
- Modal para agregar nuevas categorías con validación.
- Enlace para ver PDFs asociados a cada categoría.
- Auditoría de accesos y acciones.

### Planificación (`planificacion/planificacion.php`)
- Gestión de planificación mensual con CRUD.
- Validaciones de campos y fechas.
- Auditoría detallada de acciones (creación, edición, eliminación).
- Visualización en calendario con eventos y botones para agregar o modificar.
- Modal para edición y confirmación de eliminación.

### Usuario (`usuario/usuario.php`)
- Listado de usuarios activos con roles y plantas.
- Búsqueda dinámica.
- Modal para agregar usuarios con validaciones de campos y verificación AJAX para correo y cédula únicos.
- Auditoría de accesos y acciones.

## Auditoría (`includes/audit.php`)
- Funciones para registrar acciones de usuario, accesos a módulos, vistas, creaciones, actualizaciones, eliminaciones, intentos no autorizados y errores.
- Logs encriptados almacenados en archivo privado con rotación por tamaño.
- Utilizado en todos los módulos para trazabilidad y seguridad.

## Recursos Estáticos
- CSS y JS en `assets/` con Bootstrap, FontAwesome, Chart.js y jQuery.
- Imágenes y fuentes para la interfaz.
- Scripts para interactividad, validaciones y gráficos.

## Extensión y Mantenimiento
- Seguir la estructura modular para agregar nuevas funcionalidades.
- Usar las funciones de auditoría para registrar nuevas acciones.
- Mantener las validaciones tanto en frontend como backend.
- Actualizar el menú en `index.php` para nuevos módulos.
- Documentar cambios en este manual para facilitar futuras revisiones.

---

Este manual proporciona una visión integral del sistema para facilitar su comprensión y evolución. Para detalles específicos, revisar los archivos fuente correspondientes en cada módulo.
