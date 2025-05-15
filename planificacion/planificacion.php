<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/check_permission.php';
require_once __DIR__ . '/../includes/audit.php'; // Asegurarse de incluir el archivo de auditoría

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar acceso al módulo
logModuleAccess('planificacion');

// Obtener id_usuario de la sesión
$id_usuario = $_SESSION['id_usuario'] ?? null;
$id_planta_usuario = null;
$nombres_planta_usuario = null;

if ($id_usuario !== null) {
    // Obtener id_planta y nombre de la planta del usuario
    $stmt = $pdo->prepare("SELECT u.id_planta, p.nombres as nombre_planta 
                          FROM usuario u
                          LEFT JOIN planta p ON u.id_planta = p.id_planta
                          WHERE u.id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $id_usuario]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $id_planta_usuario = $user_data['id_planta'] ?? null;
    $nombres_planta_usuario = $user_data['nombre_planta'] ?? 'No asignada';
}

// Verificar si la tabla es planificacion
if (isset($_GET['tabla']) && $_GET['tabla'] === 'planificacion') {
    // Obtener el mes y año actual o seleccionado
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    // Variable para almacenar mensajes de éxito/error
    $alertMessage = '';
    $alertType = '';
    
    // Manejar operaciones CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $errors = [];
            
            // Validaciones comunes
            if (empty($_POST['id_planta'])) {
                $errors[] = "La planta es obligatoria";
            }
            
            if (empty($_POST['orden'])) {
                $errors[] = "El orden es obligatorio";
            } elseif (strlen($_POST['orden']) > 15) {
                $errors[] = "El orden no puede exceder los 15 caracteres";
            }
            
            if (empty($_POST['observacion'])) {
                $errors[] = "La observación es obligatoria";
            } elseif (strlen($_POST['observacion']) > 50) {
                $errors[] = "La observación no puede exceder los 50 caracteres";
            }
            
            if (empty($_POST['fecha'])) {
                $errors[] = "La fecha es obligatoria";
            }
            
            // Validar que la fecha no sea anterior a hoy
            if (!empty($_POST['fecha']) && $_POST['fecha'] < date('Y-m-d')) {
                $errors[] = "No se pueden realizar acciones en fechas anteriores a hoy";
            }
            
            // Validación de orden único
            if (empty($errors) && $_POST['action'] !== 'delete') {
                $sql = "SELECT COUNT(*) FROM PLANIFICACION 
                        WHERE orden = :orden 
                        AND fecha = :fecha
                        AND id_planta = :id_planta";
                
                if ($_POST['action'] === 'edit') {
                    $sql .= " AND id_planificacion != :id_planificacion";
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':orden', $_POST['orden'], PDO::PARAM_STR);
                $stmt->bindParam(':fecha', $_POST['fecha'], PDO::PARAM_STR);
                $stmt->bindParam(':id_planta', $id_planta_usuario, PDO::PARAM_INT);
                
                if ($_POST['action'] === 'edit') {
                    $stmt->bindParam(':id_planificacion', $_POST['id_planificacion'], PDO::PARAM_INT);
                }
                
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $errors[] = "Ya existe una planificación con el mismo orden para esta fecha en tu planta";
                }
            }
            
            if (empty($errors)) {
                switch ($_POST['action']) {
                    case 'add':
                        // Registrar inicio de la acción
                        logAuditAction('planificacion', 'Create', "Inicio de creación de planificación", [
                            'fecha' => $_POST['fecha'],
                            'orden' => $_POST['orden'],
                            'observacion' => $_POST['observacion']
                        ]);
                        
                        if (agregarPlanificacion($pdo, $id_planta_usuario)) {
                            $alertMessage = "Planificación agregada correctamente";
                            $alertType = "success";
                            // Registrar éxito
                            logAuditAction('planificacion', 'Create', "Planificación creada exitosamente", [
                                'fecha' => $_POST['fecha'],
                                'orden' => $_POST['orden'],
                                'observacion' => $_POST['observacion']
                            ]);
                        } else {
                            $alertMessage = "Error al agregar la planificación";
                            $alertType = "danger";
                            // Registrar error
                            logSystemError('planificacion', "Error al crear planificación", [
                                'fecha' => $_POST['fecha'],
                                'orden' => $_POST['orden'],
                                'observacion' => $_POST['observacion']
                            ]);
                        }
                        break;
                    case 'edit':
                        // Obtener datos actuales antes de editar
                        $stmt = $pdo->prepare("SELECT * FROM PLANIFICACION WHERE id_planificacion = ?");
                        $stmt->execute([$_POST['id_planificacion']]);
                        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Registrar inicio de la acción con datos antiguos
                        logAuditAction('planificacion', 'Update', "Inicio de edición de planificación ID " . $_POST['id_planificacion'], [
                            'old_data' => $old_data,
                            'new_data' => [
                                'orden' => $_POST['orden'],
                                'observacion' => $_POST['observacion'],
                                'fecha' => $_POST['fecha']
                            ]
                        ]);
                        
                        if (editarPlanificacion($pdo, $id_planta_usuario)) {
                            $alertMessage = "Planificación actualizada correctamente";
                            $alertType = "success";
                            // Registrar éxito con cambios
                            $stmt = $pdo->prepare("SELECT * FROM PLANIFICACION WHERE id_planificacion = ?");
                            $stmt->execute([$_POST['id_planificacion']]);
                            $new_data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            logAuditAction('planificacion', 'Update', "Planificación actualizada exitosamente ID " . $_POST['id_planificacion'], [
                                'old_data' => $old_data,
                                'new_data' => $new_data,
                                'changes' => [
                                    'orden' => ($old_data['orden'] !== $new_data['orden']) ? 
                                        ['old' => $old_data['orden'], 'new' => $new_data['orden']] : null,
                                    'observacion' => ($old_data['observacion'] !== $new_data['observacion']) ? 
                                        ['old' => $old_data['observacion'], 'new' => $new_data['observacion']] : null,
                                    'fecha' => ($old_data['fecha'] !== $new_data['fecha']) ? 
                                        ['old' => $old_data['fecha'], 'new' => $new_data['fecha']] : null
                                ]
                            ]);
                        } else {
                            $alertMessage = "Error al actualizar la planificación";
                            $alertType = "danger";
                            // Registrar error
                            logSystemError('planificacion', "Error al actualizar planificación ID " . $_POST['id_planificacion'], [
                                'old_data' => $old_data,
                                'attempted_update' => [
                                    'orden' => $_POST['orden'],
                                    'observacion' => $_POST['observacion'],
                                    'fecha' => $_POST['fecha']
                                ]
                            ]);
                        }
                        break;
                    case 'delete':
                        // Obtener datos antes de eliminar
                        $stmt = $pdo->prepare("SELECT * FROM PLANIFICACION WHERE id_planificacion = ?");
                        $stmt->execute([$_POST['id_planificacion']]);
                        $deleted_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Registrar intento de eliminación
                        logAuditAction('planificacion', 'Delete', "Inicio de eliminación de planificación ID " . $_POST['id_planificacion'], $deleted_data);
                        
                        if (eliminarPlanificacion($pdo, $id_planta_usuario)) {
                            $alertMessage = "Planificación eliminada correctamente";
                            $alertType = "success";
                            // Registrar eliminación exitosa
                            logAuditAction('planificacion', 'Delete', "Planificación eliminada exitosamente ID " . $_POST['id_planificacion'], $deleted_data);
                        } else {
                            $alertMessage = "Error al eliminar la planificación";
                            $alertType = "danger";
                            // Registrar error en eliminación
                            logSystemError('planificacion', "Error al eliminar planificación ID " . $_POST['id_planificacion'], $deleted_data);
                        }
                        break;
                }
            } else {
                $alertMessage = implode("<br>", $errors);
                $alertType = "danger";
                // Registrar intento fallido con errores
                logAuditAction('planificacion', 'Validation', "Validación fallida al procesar planificación", [
                    'errors' => $errors,
                    'posted_data' => $_POST
                ]);
            }
        }
    }

    // Obtener datos de planificación para el mes actual
    $planificaciones = obtenerPlanificacionesMes($pdo, $month, $year, $id_planta_usuario);

    // Mostrar el calendario
    mostrarCalendario($month, $year, $planificaciones, $alertMessage, $alertType, $id_planta_usuario, $nombres_planta_usuario);
}

function agregarPlanificacion($pdo, $id_planta_usuario) {
    try {
        $sql = "INSERT INTO PLANIFICACION (id_planta, orden, observacion, fecha) 
                VALUES (:id_planta, :orden, :observacion, :fecha)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_planta', $id_planta_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':orden', $_POST['orden'], PDO::PARAM_STR);
        $stmt->bindParam(':observacion', $_POST['observacion'], PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $_POST['fecha'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

function editarPlanificacion($pdo, $id_planta_usuario) {
    try {
        $sql = "UPDATE PLANIFICACION SET 
                orden = :orden, 
                observacion = :observacion, 
                fecha = :fecha 
                WHERE id_planificacion = :id_planificacion
                AND id_planta = :id_planta";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_planificacion', $_POST['id_planificacion'], PDO::PARAM_INT);
        $stmt->bindParam(':id_planta', $id_planta_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':orden', $_POST['orden'], PDO::PARAM_STR);
        $stmt->bindParam(':observacion', $_POST['observacion'], PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $_POST['fecha'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

function eliminarPlanificacion($pdo, $id_planta_usuario) {
    try {
        $sql = "DELETE FROM PLANIFICACION 
                WHERE id_planificacion = :id_planificacion
                AND id_planta = :id_planta";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_planificacion', $_POST['id_planificacion'], PDO::PARAM_INT);
        $stmt->bindParam(':id_planta', $id_planta_usuario, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

function obtenerPlanificacionesMes($pdo, $month, $year, $id_planta_usuario) {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "SELECT p.*, pl.nombres as nombre_planta 
            FROM PLANIFICACION p
            JOIN PLANTA pl ON p.id_planta = pl.id_planta
            WHERE fecha BETWEEN :startDate AND :endDate
            AND p.id_planta = :id_planta
            ORDER BY fecha";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
    $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
    $stmt->bindParam(':id_planta', $id_planta_usuario, PDO::PARAM_INT);
    $stmt->execute();
    
    $planificaciones = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $planificaciones[$row['fecha']][] = $row;
    }
    
    return $planificaciones;
}

function mostrarCalendario($month, $year, $planificaciones, $alertMessage = '', $alertType = '', $id_planta_usuario, $nombres_planta_usuario) {
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $numDays = date('t', $firstDay);
    $dayOfWeek = date('w', $firstDay);
    
    // Ajustar para que la semana comience en Lunes
    if ($dayOfWeek == 0) {
        $dayOfWeek = 6;
    } else {
        $dayOfWeek--;
    }
    
    // Calcular meses anterior y siguiente
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $nextMonth = $month + 1;
    $nextYear = $year;
    if ($nextMonth > 12) {
        $nextMonth = 1;
        $nextYear++;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Calendario de Planificación</title>
        <link href="assets/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
        <style>
            .calendar-day {
                height: 120px;
                overflow-y: auto;
                border: 1px solid #dee2e6;
                position: relative;
            }
            .calendar-day:hover {
                background-color: #f8f9fa;
            }
            .day-number {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .event {
                font-size: 0.8rem;
                padding: 2px 5px;
                margin-bottom: 2px;
                background-color: #e9ecef;
                border-radius: 3px;
                cursor: pointer;
            }
            .event:hover {
                background-color: #ced4da;
            }
            .prev-month, .next-month {
                background-color: #f8f9fa;
                color: #adb5bd;
            }
            .today {
                background-color: #e7f5ff;
            }
            .btn-outline-success {
                color: #b71513;
                border-color: #b71513;
            }
            .btn-outline-success:hover {
                background-color: #b71513;  
                color: white;               
                border-color: #b71513;      
            }
            #alertContainer {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1100;
                max-width: 300px;
            }
            .btn.disabled {
                color:#b71513;
                border-color:#b71513;
            }
            .disabled-event {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>
    </head>
    <body>
        <div id="alertContainer"></div>
        
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Planificación Mensual - <?= htmlspecialchars($nombres_planta_usuario) ?></h2>
                <div>
                    <?php
                    $currentMonth = date('n');
                    $currentYear = date('Y');
                    $disablePrevMonth = ($prevYear < $currentYear) || ($prevYear == $currentYear && $prevMonth < $currentMonth);
                    ?>
                    <a href="<?= $disablePrevMonth ? '#' : "?tabla=planificacion&month=$prevMonth&year=$prevYear" ?>" class="btn btn-outline-danger <?= $disablePrevMonth ? 'disabled' : '' ?>" <?= $disablePrevMonth ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                        <i class="bi bi-chevron-left"></i> Mes Anterior
                    </a>
                    <a href="?tabla=planificacion&month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-outline-danger mx-2">
                        Hoy
                    </a>
                    <a href="?tabla=planificacion&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-danger">
                        Mes Siguiente <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <h3 class="text-center mb-3"><?= ucfirst((new DateTime())->setTimestamp($firstDay)->format('F Y')) ?></h3>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Lunes</th>
                            <th class="text-center">Martes</th>
                            <th class="text-center">Miércoles</th>
                            <th class="text-center">Jueves</th>
                            <th class="text-center">Viernes</th>
                            <th class="text-center">Sábado</th>
                            <th class="text-center">Domingo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $currentDay = 1;
                        $today = date('Y-m-d');
                        
                        while ($currentDay <= $numDays) {
                            echo '<tr>';
                            
                            for ($i = 0; $i < 7; $i++) {
                                if (($currentDay == 1 && $i < $dayOfWeek) || ($currentDay > $numDays)) {
                                    echo '<td class="calendar-day prev-month"></td>';
                                } else {
                                    $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                                    $isToday = ($currentDate == $today) ? 'today' : '';
                                    
                                    echo '<td class="calendar-day ' . $isToday . '" data-date="' . $currentDate . '">';
                                    echo '<div class="day-number">' . $currentDay . '</div>';
                                    
                                    if (isset($planificaciones[$currentDate])) {
                                        foreach ($planificaciones[$currentDate] as $event) {
                                            $isPastEvent = ($currentDate < date('Y-m-d'));
                                            echo '<div class="event ' . ($isPastEvent ? 'disabled-event' : '') . '" data-id="' . $event['id_planificacion'] . '" 
                                                  data-orden="' . htmlspecialchars($event['orden']) . '"
                                                  data-observacion="' . htmlspecialchars($event['observacion']) . '"
                                                  data-fecha="' . $event['fecha'] . '" ' . ($isPastEvent ? '' : 'data-bs-toggle="modal" data-bs-target="#eventModal"') . '>
                                                  <strong>' . htmlspecialchars($event['orden']) . '</strong>
                                                  </div>';
                                        }
                                    }
                                    
                                    if ($id_planta_usuario) {
                                        $isPastDate = ($currentDate < date('Y-m-d'));
                                        echo '<button class="btn btn-sm btn-outline-success w-100 mt-1 add-event ' . ($isPastDate ? 'disabled' : '') . '" 
                                              data-date="' . $currentDate . '" 
                                              data-bs-toggle="modal" data-bs-target="#eventModal" ' . ($isPastDate ? 'tabindex="-1" aria-disabled="true"' : '') . '>
                                              <i class="bi bi-plus"></i> Agregar
                                              </button>';
                                    }
                                    
                                    echo '</td>';
                                    $currentDay++;
                                }
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Modal -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="eventModalLabel">Nueva Planificación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="" id="planificacionForm">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="id_planificacion" id="eventId" value="">
                            <input type="hidden" name="fecha" id="eventDate" value="">
                            <input type="hidden" name="id_planta" value="<?= $id_planta_usuario ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Planta: <strong><?= htmlspecialchars($nombres_planta_usuario) ?></strong></label>
                            </div>
                            
                            <div class="mb-3">
                                <label for="orden" class="form-label">Orden *</label>
                                <input type="text" class="form-control" id="orden" name="orden" maxlength="15" required>
                                <div class="form-text">Máximo 15 caracteres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observacion" class="form-label">Observación *</label>
                                <textarea class="form-control" id="observacion" name="observacion" maxlength="50" required></textarea>
                                <div class="form-text">Máximo 50 caracteres</div>
                            </div>
                            
                            <div class="modal-footer">
                                <div id="deleteButtonContainer" class="me-auto"></div>
                                <button type="submit" class="btn btn-primary">Guardar planificacion</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if (!empty($alertMessage)): ?>
                    showAlert('<?= $alertType ?>', '<?= addslashes($alertMessage) ?>');
                <?php endif; ?>
                
                function showAlert(type, message) {
                    const alertHtml = `
                        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    document.getElementById('alertContainer').innerHTML = alertHtml;
                }
                
                document.querySelectorAll('.add-event').forEach(button => {
                    button.addEventListener('click', function() {
                        if (this.classList.contains('disabled')) {
                            return; // Prevent opening modal for past dates
                        }
                        document.getElementById('eventModalLabel').textContent = 'Nueva Planificación';
                        document.getElementById('formAction').value = 'add';
                        document.getElementById('eventId').value = '';
                        document.getElementById('eventDate').value = this.getAttribute('data-date');
                        document.getElementById('orden').value = '';
                        document.getElementById('observacion').value = '';
                        document.getElementById('deleteButtonContainer').innerHTML = '';
                    });
                });
                
                document.querySelectorAll('.event').forEach(event => {
                    event.addEventListener('click', function() {
                        if (this.classList.contains('disabled-event')) {
                            return; // Prevent opening modal for past events
                        }
                        document.getElementById('eventModalLabel').textContent = 'Editar Planificación';
                        document.getElementById('formAction').value = 'edit';
                        document.getElementById('eventId').value = this.getAttribute('data-id');
                        document.getElementById('eventDate').value = this.getAttribute('data-fecha');
                        document.getElementById('orden').value = this.getAttribute('data-orden');
                        document.getElementById('observacion').value = this.getAttribute('data-observacion');
                        
                        document.getElementById('deleteButtonContainer').innerHTML = `
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        `;
                    });
                });
            });
            
            function confirmDelete() {
                if (confirm('¿Estás seguro de que deseas eliminar esta planificación?')) {
                    document.getElementById('formAction').value = 'delete';
                    document.getElementById('planificacionForm').submit();
                }
            }
        </script>
    </body>
    </html>
    <?php
}
?>