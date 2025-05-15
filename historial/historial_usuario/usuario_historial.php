<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/check_permission.php';
require_once __DIR__ . '/../../includes/audit.php';

// Capturar la URL actual sin parámetros de éxito/error
$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');

// Mostrar alertas si existen
$alertType = '';
$alertMessage = '';

if (isset($_GET['success'])) {
    $alertType = 'success';
    if ($_GET['success'] === 'usuario_habilitado') {
        $alertMessage = 'Usuario habilitado exitosamente!';
    } elseif ($_GET['success'] === 'usuario_deshabilitado') {
        $alertMessage = 'Usuario deshabilitado exitosamente!';
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta para obtener todos los usuarios con información de planta y cargo
$stmt = $pdo->query("
    SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.telefono, u.correo, 
           p.nombres AS nombre_planta, c.nombre_cargo, u.estado
    FROM usuario u
    JOIN planta p ON u.id_planta = p.id_planta
    JOIN cargo c ON u.id_cargo = c.id_cargo
    ORDER BY u.estado, u.apellido, u.nombre
");
$usuarios = $stmt->fetchAll();
logModuleAccess('Historial usuario');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Usuarios</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .selected-row {
            background-color: #e0f7fa;
            font-weight: bold;
            outline: 2px solid #b71513;
            outline-offset: -2px;
        }
        
        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .alert-floating {
            min-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .table-responsive {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Container for floating alerts -->
    <div id="alertContainer">
        <?php if ($alertMessage): ?>
        <div class="alert alert-<?= $alertType ?> alert-floating alert-dismissible fade show" role="alert">
            <?= $alertMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>

    <div class="sticky-top bg-white p-3 shadow-sm">
        <h4 class="text-center mb-1">Historial de Usuarios</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoUsuario()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Nombre o Cédula" onkeyup="filtrarUsuarios()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Cédula</th>
                    <th>Nombre Completo</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Planta</th>
                    <th>Cargo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-usuarios">
                <?php foreach ($usuarios as $usuario): ?>
                <tr id="usuario-<?= $usuario['id_usuario'] ?>" onclick="seleccionarUsuario('<?= $usuario['id_usuario'] ?>')" style="cursor: pointer;">
                    <td><?= htmlspecialchars($usuario['cedula']) ?></td>
                    <td><?= htmlspecialchars($usuario['apellido']) ?>, <?= htmlspecialchars($usuario['nombre']) ?></td>
                    <td><?= htmlspecialchars($usuario['telefono']) ?></td>
                    <td><?= htmlspecialchars($usuario['correo']) ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_planta']) ?></td>
                    <td><?= htmlspecialchars($usuario['nombre_cargo']) ?></td>
                    <td>
                        <span class="badge <?= $usuario['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($usuario['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="7" class="text-center">No hay usuarios registrados</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let usuarioSeleccionado = null;
    // Capturar los parámetros actuales de la URL
    const currentUrlParams = new URLSearchParams(window.location.search);
    // Eliminar los parámetros de éxito/error para la redirección
    currentUrlParams.delete('success');
    currentUrlParams.delete('error');
    const baseUrl = window.location.pathname;
    const cleanUrl = baseUrl + (currentUrlParams.toString() ? '?' + currentUrlParams.toString() : '');

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-cierre de alertas después de 5 segundos
        const alerts = document.querySelectorAll('.alert-auto-close');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });

    // Función para seleccionar un usuario con estilo personalizado
    function seleccionarUsuario(id_usuario) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-usuarios tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`usuario-${id_usuario}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            usuarioSeleccionado = id_usuario;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    // Función para cambiar el estado de un usuario
    function cambiarEstadoUsuario() {
        if (!usuarioSeleccionado) {
            showFloatingAlert('Por favor seleccione un usuario primero', 'warning');
            return;
        }

        // Obtener el estado actual del usuario seleccionado
        const fila = document.getElementById(`usuario-${usuarioSeleccionado}`);
        const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
        const nombreUsuario = fila.cells[1].textContent.trim();
        
        const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const mensaje = `¿Estás seguro de que deseas ${nombreAccion} al usuario ${nombreUsuario}?`;
        
        if (confirm(mensaje)) {
            fetch(`historial/historial_usuario/${accion}_usuario_historial.php?id_usuario=${usuarioSeleccionado}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFloatingAlert(`Usuario ${nombreAccion}do exitosamente!`, 'success');
                        
                        // Actualizar la fila en la tabla
                        const badge = fila.querySelector('span.badge');
                        if (estadoActual === 'activo') {
                            badge.className = 'badge bg-secondary';
                            badge.textContent = 'Inactivo';
                        } else {
                            badge.className = 'badge bg-success';
                            badge.textContent = 'Activo';
                        }
                        
                        // Deshabilitar el botón hasta nueva selección
                        document.getElementById('btnCambiarEstado').disabled = true;
                        usuarioSeleccionado = null;
                        
                        // Redirigir a la URL limpia con el parámetro de éxito
                        const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=usuario_${accion}do`;
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error desconocido al cambiar estado');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert(`Error al ${nombreAccion} el usuario: ${error.message}`, 'danger');
                });
        }
    }

    // Función para filtrar usuarios
    function filtrarUsuarios() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-usuarios tr[id^="usuario-"]');
        
        rows.forEach(row => {
            const nombreUsuario = row.cells[1].textContent.toLowerCase();
            const cedulaUsuario = row.cells[0].textContent.toLowerCase();
            if (nombreUsuario.includes(searchValue) || cedulaUsuario.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Actualizar la URL con el parámetro de búsqueda
        const params = new URLSearchParams(window.location.search);
        if (searchValue) {
            params.set('search', searchValue);
        } else {
            params.delete('search');
        }
        params.delete('success');
        params.delete('error');
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState(null, '', newUrl);
    }

    // Función para mostrar alertas flotantes
    function showFloatingAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert alert-${type} alert-floating alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alertDiv);
        
        // Cerrar automáticamente después de 5 segundos
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
        
        // Eliminar el elemento del DOM después de cerrar
        alertDiv.addEventListener('closed.bs.alert', function() {
            alertDiv.remove();
        });
    }

    // Cargar búsqueda inicial si existe
    document.addEventListener('DOMContentLoaded', function() {
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchParam) {
            document.getElementById('search').value = searchParam;
            filtrarUsuarios();
        }
    });
    </script>
</body>
</html>