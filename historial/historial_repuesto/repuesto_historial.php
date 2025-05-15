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
    if ($_GET['success'] === 'repuesto_habilitado') {
        $alertMessage = 'Repuesto habilitado exitosamente!';
    } elseif ($_GET['success'] === 'repuesto_deshabilitado') {
        $alertMessage = 'Repuesto deshabilitado exitosamente!';
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

// Consulta para obtener todos los repuestos con información del fabricante
$stmt = $pdo->query("
    SELECT r.id_repuestos, f.nombres as fabricante, r.descripcion, r.real_part, r.sectional_drawing, r.estado 
    FROM repuestos r
    JOIN fabricantes f ON r.id_fabricante = f.id_fabricante
    ORDER BY r.estado, r.descripcion
");
$repuestos = $stmt->fetchAll();
logModuleAccess('Historial repuesto');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Repuestos</title>
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
        
        .fixed-header {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: white;
            z-index: 10;
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
        <h4 class="text-center mb-1">Historial de Repuestos</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoRepuesto()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Descripción" onkeyup="filtrarRepuestos()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark fixed-header">
                <tr>
                    <th>ID Repuesto</th>
                    <th>Fabricante</th>
                    <th>Descripción</th>
                    <th>Real Part</th>
                    <th>Sectional Drawing</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-repuestos">
                <?php foreach ($repuestos as $repuesto): ?>
                <tr id="repuesto-<?= $repuesto['id_repuestos'] ?>" onclick="seleccionarRepuesto('<?= $repuesto['id_repuestos'] ?>')" style="cursor: pointer;">
                    <td><?= htmlspecialchars($repuesto['id_repuestos']) ?></td>
                    <td><?= htmlspecialchars($repuesto['fabricante']) ?></td>
                    <td><?= htmlspecialchars($repuesto['descripcion']) ?></td>
                    <td><?= htmlspecialchars($repuesto['real_part']) ?></td>
                    <td><?= htmlspecialchars($repuesto['sectional_drawing']) ?></td>
                    <td>
                        <span class="badge <?= $repuesto['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($repuesto['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($repuestos)): ?>
                <tr>
                    <td colspan="6" class="text-center">No hay repuestos registrados</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let repuestoSeleccionado = null;
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

    // Función para seleccionar un repuesto con estilo personalizado
    function seleccionarRepuesto(id_repuesto) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-repuestos tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`repuesto-${id_repuesto}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            repuestoSeleccionado = id_repuesto;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    // Función para cambiar el estado de un repuesto
    function cambiarEstadoRepuesto() {
        if (!repuestoSeleccionado) {
            showFloatingAlert('Por favor seleccione un repuesto primero', 'warning');
            return;
        }

        // Obtener el estado actual del repuesto seleccionado
        const fila = document.getElementById(`repuesto-${repuestoSeleccionado}`);
        const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
        const descripcionRepuesto = fila.cells[2].textContent.trim();
        
        const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const mensaje = `¿Estás seguro de que deseas ${nombreAccion} el repuesto "${descripcionRepuesto}"?`;
        
        if (confirm(mensaje)) {
            fetch(`historial/historial_repuesto/${accion}_repuesto_historial.php?id_repuesto=${repuestoSeleccionado}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFloatingAlert(`Repuesto ${nombreAccion}do exitosamente!`, 'success');
                        
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
                        repuestoSeleccionado = null;
                        
                        // Redirigir a la URL limpia con el parámetro de éxito
                        const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=repuesto_${accion}do`;
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error desconocido al cambiar estado');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert(`Error al ${nombreAccion} el repuesto: ${error.message}`, 'danger');
                });
        }
    }

    // Función para filtrar repuestos
    function filtrarRepuestos() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-repuestos tr[id^="repuesto-"]');
        
        rows.forEach(row => {
            const descripcion = row.cells[2].textContent.toLowerCase();
            const fabricante = row.cells[1].textContent.toLowerCase();
            const realPart = row.cells[3].textContent.toLowerCase();
            const sectionalDrawing = row.cells[4].textContent.toLowerCase();
            
            if (descripcion.includes(searchValue) || 
                fabricante.includes(searchValue) || 
                realPart.includes(searchValue) || 
                sectionalDrawing.includes(searchValue)) {
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
            filtrarRepuestos();
        }
    });
    </script>
</body>
</html>