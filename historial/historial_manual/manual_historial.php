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
    if ($_GET['success'] === 'manual_habilitado') {
        $alertMessage = 'Manual habilitado exitosamente!';
    } elseif ($_GET['success'] === 'manual_deshabilitado') {
        $alertMessage = 'Manual deshabilitado exitosamente!';
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

// Consulta para obtener todos los manuales con información de planta
$stmt = $pdo->query("
    SELECT m.id_manual, m.descripcion, m.estado, p.nombres AS nombre_planta
    FROM manual m
    JOIN planta p ON m.id_planta = p.id_planta
    ORDER BY m.estado, m.descripcion
");
$manuales = $stmt->fetchAll();
logModuleAccess('Historial Manual');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Manuales</title>
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
        
        .sticky-top {
            z-index: 1020;
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
        <h4 class="text-center mb-1">Historial de Manuales</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoManual()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Descripción" onkeyup="filtrarManuales()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>ID Manual</th>
                    <th>Descripción</th>
                    <th>Planta</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-manuales">
                <?php foreach ($manuales as $manual): ?>
                <tr id="manual-<?= $manual['id_manual'] ?>" onclick="seleccionarManual('<?= $manual['id_manual'] ?>')" style="cursor: pointer;">
                    <td><?= htmlspecialchars($manual['id_manual']) ?></td>
                    <td><?= htmlspecialchars($manual['descripcion']) ?></td>
                    <td><?= htmlspecialchars($manual['nombre_planta']) ?></td>
                    <td>
                        <span class="badge <?= $manual['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($manual['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($manuales)): ?>
                <tr>
                    <td colspan="4" class="text-center">No hay manuales registrados</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let manualSeleccionado = null;
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

    // Función para seleccionar un manual con estilo personalizado
    function seleccionarManual(id_manual) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-manuales tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`manual-${id_manual}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            manualSeleccionado = id_manual;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    // Función para cambiar el estado de un manual
    function cambiarEstadoManual() {
        if (!manualSeleccionado) {
            showFloatingAlert('Por favor seleccione un manual primero', 'warning');
            return;
        }

        // Obtener el estado actual del manual seleccionado
        const fila = document.getElementById(`manual-${manualSeleccionado}`);
        const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
        const descripcionManual = fila.cells[1].textContent.trim();
        const plantaManual = fila.cells[2].textContent.trim();
        
        const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const mensaje = `¿Estás seguro de que deseas ${nombreAccion} el manual "${descripcionManual}" de la planta "${plantaManual}"?`;
        
        if (confirm(mensaje)) {
            fetch(`historial/historial_manual/${accion}_manual_historial.php?id_manual=${manualSeleccionado}`, {
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw new Error(text || 'Error en la respuesta del servidor') });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFloatingAlert(`Manual ${nombreAccion}do exitosamente!`, 'success');
                        
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
                        manualSeleccionado = null;
                        
                        // Redirigir a la URL limpia con el parámetro de éxito
                        const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=manual_${accion}do`;
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error desconocido al cambiar estado');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert(`Error al ${nombreAccion} el manual: ${error.message}`, 'danger');
                });
        }
    }

    // Función para filtrar manuales
    function filtrarManuales() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-manuales tr[id^="manual-"]');
        
        rows.forEach(row => {
            const descripcionManual = row.cells[1].textContent.toLowerCase();
            const plantaManual = row.cells[2].textContent.toLowerCase();
            if (descripcionManual.includes(searchValue) || plantaManual.includes(searchValue)) {
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
            filtrarManuales();
        }
    });
    </script>
</body>
</html>