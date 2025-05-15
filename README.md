# Sistema de Gestión PEQUIVEN

## Descripción del Proyecto
PEQUIVEN es un sistema integral de gestión web diseñado para facilitar la administración de equipos, actividades de mantenimiento, manuales, repuestos y permisos de usuario. El sistema proporciona un mecanismo de control de acceso basado en roles para garantizar una gestión segura y organizada de los activos industriales y los procesos relacionados.

## Características
- **Gestión de Equipos:** Registrar, editar y dar seguimiento a los detalles de los equipos.
- **Gestión de Mantenimiento:** Gestionar tareas de mantenimiento preventivo y correctivo.
- **Manuales y Documentación:** Cargar, buscar y consultar manuales de equipos y documentos relacionados.
- **Gestión de Repuestos:** Gestionar el inventario de repuestos e información relacionada.
- **Gestión de Usuarios y Permisos:** Control de acceso basado en roles para restringir el acceso a los módulos según los roles de usuario.
- **Panel de Control:** Resumen de estadísticas clave, incluyendo el recuento de equipos, las actividades de mantenimiento, los manuales y los repuestos.
- **Carga Dinámica de Módulos:** Los módulos se cargan dinámicamente según los permisos de usuario.

## Tecnologías utilizadas
- PHP (Backend)
- MySQL (Base de datos)
- Bootstrap 5 (Interfaz de usuario)
- Font Awesome (Íconos)
- JavaScript (Scripting del lado del cliente)
- jQuery

## Instalación

1. **Clonar el repositorio:**
```bash
git clone <repository-url>
```

2. **Configurar la base de datos:**
- Crear una base de datos MySQL llamada `instrumento`.
- Importar el esquema SQL y los datos desde `base-datos/pequiven.sql`.

3. **Configurar la conexión a la base de datos:**
- Editar `includes/conexion.php` para configurar el nombre de usuario y la contraseña de MySQL.

4. **Configurar un servidor local:**
- Usar XAMPP, WAMP o cualquier servidor compatible con PHP.
- Colocar la carpeta del proyecto en el directorio raíz del servidor (por ejemplo, `htdocs` para XAMPP).

5. **Acceder a la aplicación:**
- Abra su navegador y navegue a `http://localhost/PEQUIVEN/`.
- Inicie sesión con sus credenciales de usuario.

## Uso

- Inicie sesión a través de la página de inicio de sesión.
- Navegue por los módulos usando el menú lateral.
- Acceda a los módulos de equipos, mantenimiento, manuales, repuestos y gestión de usuarios según sus permisos.
- Consulte las estadísticas del panel de control en la página de inicio.
- Utilice las funciones de búsqueda y filtrado dentro de los módulos para encontrar registros específicos.

## Estructura de carpetas

- `assets/` - Contiene CSS, JavaScript, fuentes e imágenes utilizadas en la interfaz de usuario.
- `equipo/` - Archivos del módulo de gestión de equipos.
- `Mantenimiento/` - Módulos de gestión de mantenimiento (correctivo, preventivo, maestro).
- `manual/` - Gestión de manuales y documentación.
- `planificacion/` - Gestión de planificación y actividades.
- `planta/` - Gestión de planta.
- `repuestos/` - Gestión de repuestos. - `ubicacion/` - Gestión de ubicaciones.
- `usuario/` - Gestión de usuarios.
- `includes/` - Inclusiones principales, como la conexión a la base de datos y la comprobación de permisos.
- `base-datos/` - Archivo de volcado de base de datos para la configuración inicial.
- `FPDF_master/` - Biblioteca de terceros para la generación de PDF.

## Licencia
Este proyecto no especifica actualmente una licencia. Puede agregar un archivo de licencia si lo desea.

## Contacto
Para preguntas o asistencia, póngase en contacto con el responsable del proyecto.