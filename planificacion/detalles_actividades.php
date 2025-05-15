<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';


// Verificar si se proporcionó un ID de actividad
if (!isset($_GET['id'])) {
    header('Location: ../actividades.php');
    exit;
}

$id_actividad = $_GET['id'];

// Obtener los detalles de la actividad con información del usuario
$stmt = $pdo->prepare("
    SELECT a.*, u.nombre as nombre_usuario 
    FROM actividades a
    JOIN usuario u ON a.id_usuario = u.id_usuario
    WHERE a.id_actividad = ?
");
$stmt->execute([$id_actividad]);
$actividad = $stmt->fetch();

// Si no se encuentra la actividad, redirigir
if (!$actividad) {
    header('Location: ../actividades.php');
    exit;
}

// Obtener usuarios para el select del modal
$stmtUsuarios = $pdo->query("SELECT id_usuario, nombre FROM usuario ORDER BY nombre");
$usuarios = $stmtUsuarios->fetchAll();

logViewRecord('actividades', $id_actividad, $actividad);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Actividad #<?= htmlspecialchars($actividad['id_actividad']) ?></title>
    <link rel="icon" href="../favicon.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #b71513;
            --secondary-color: #540212;
            --accent-color: #b71513;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
        }
        
        .btn-custom {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            background-color: #b71513;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border: none;
        }
        
        .detail-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .detail-item:hover {
            background-color: #f8f9fa;
        }
        
        .detail-item strong {
            color: var(--primary-color);
            min-width: 150px;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .detail-item strong {
                min-width: 120px;
                display: block;
                margin-bottom: 5px;
            }
        }

        /* Breadcrumb */
        .breadcrumb {
            background-color: #e9ecef;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
        }

        /* Modal styles */
        .bg-gradient-primary {
            background: linear-gradient( #b71513 100%);
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #2c3e50;
            border-bottom: 3px solid #dee2e6;
        }
        
        .nav-tabs .nav-link.active {
            color: #2c3e50;
            border-bottom: 3px solid #b71513;
            background-color: transparent;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #b71513;
            box-shadow: 0 0 0 0.25rem rgba(183, 21, 19, 0.25);
        }
        
        .modal-content {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .btn-primary {
            background-color: #b71513;
            border-color: #b71513;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #b71513;
            border-color: #b71513;
            transform: translateY(-2px);
        }
        
        .progress {
            height: 25px;
            border-radius: 5px;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            font-weight: bold;
        }

        a{
            color: #b71513;
        }

    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Breadcrumb para mejor navegación -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="../index.php?tabla=actividades">Actividades</a></li>
                <li class="breadcrumb-item active" aria-current="page">Actividad #<?= htmlspecialchars($actividad['id_actividad']) ?></li>
            </ol>
        </nav>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>
                        Actividad #<?= htmlspecialchars($actividad['id_actividad']) ?>
                        <span class="badge bg-secondary ms-2">Orden: <?= htmlspecialchars($actividad['orden']) ?></span>
                    </h2>
                    <small class="text-white-50">Registrada por: <?= htmlspecialchars($actividad['nombre_usuario']) ?></small>
                </div>
                <div class="btn-group">

                <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#editarActividadModal"   >
        <i class="bi bi-pencil-square me-1"></i> Editar
    </button>
    <a href="../index.php?tabla=actividades" class="btn btn-custom">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>





</div>
            </div>
            
            <div class="card-body">
                <!-- Barra de progreso -->
                <div class="mb-4">
                    <h5 class="mb-2">Avance de la actividad</h5>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?= htmlspecialchars($actividad['avance']) ?>%;" 
                             aria-valuenow="<?= htmlspecialchars($actividad['avance']) ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= htmlspecialchars($actividad['avance']) ?>%
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Columna 1: Información Básica -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-card-checklist me-2"></i>Orden:</strong>
                            <span><?= htmlspecialchars($actividad['orden']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-building me-2"></i>Planta:</strong>
                            <span><?= htmlspecialchars($actividad['planta']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-geo-alt me-2"></i>Ubicación:</strong>
                            <span><?= htmlspecialchars($actividad['ubicacion']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-tag me-2"></i>Tag Number:</strong>
                            <span><?= htmlspecialchars($actividad['tag_number']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-calendar me-2"></i>Fecha:</strong>
                            <span><?= htmlspecialchars($actividad['fecha']) ?></span>
                        </div>
                    </div>

                    <!-- Columna 2: Detalles Técnicos -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-file-text me-2"></i>N° Permiso:</strong>
                            <span><?= htmlspecialchars($actividad['num_permiso']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-people me-2"></i>Especialistas:</strong>
                            <span><?= htmlspecialchars($actividad['especialistas']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-clock me-2"></i>Tiempo estimado:</strong>
                            <span><?= htmlspecialchars($actividad['tiempo']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-tools me-2"></i>Recurso de apoyo:</strong>
                            <span><?= htmlspecialchars($actividad['recurso_apoyo']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Descripción de la actividad -->
                <div class="mt-4">
                    <h5><i class="bi bi-journal-text me-2"></i>Descripción de la actividad</h5>
                    <div class="card bg-light p-3">
                        <?= nl2br(htmlspecialchars($actividad['actividad'])) ?>
                    </div>
                </div>
                
                <!-- Observaciones -->
                <?php if (!empty($actividad['observacion'])): ?>
                <div class="mt-4">
                    <h5><i class="bi bi-chat-square-text me-2"></i>Observaciones</h5>
                    <div class="card bg-light p-3">
                        <?= nl2br(htmlspecialchars($actividad['observacion'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
<!-- Modificar la línea 278 (footer) para verificar si existe la fecha de actualización -->
<div class="card-footer bg-light d-flex justify-content-between align-items-center">
    <?php if (isset($actividad['fecha_actualizacion']) && !empty($actividad['fecha_actualizacion'])): ?>
        <small class="text-muted">Última actualización: <?= date('d/m/Y H:i', strtotime($actividad['fecha_actualizacion'])) ?></small>
    <?php else: ?>
        <small class="text-muted">Última actualización: No disponible</small>
    <?php endif; ?>
    <small class="text-muted">ID: <?= htmlspecialchars($actividad['id_actividad']) ?></small>
</div>
        </div>
    </div>


    <div class="modal fade" id="editarActividadModal" tabindex="-1" aria-labelledby="editarActividadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="editarActividadModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Actividad #<?= htmlspecialchars($actividad['id_actividad']) ?>
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formEditarActividad" action="../planificacion/editar_actividad.php" method="POST">
                    <input type="hidden" name="id_actividad" value="<?= htmlspecialchars($actividad['id_actividad']) ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="edit_id_usuario" name="id_usuario" required>
                                    <?php foreach ($usuarios as $user): ?>
                                        <option value="<?= $user['id_usuario'] ?>" <?= $user['id_usuario'] == $actividad['id_usuario'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Usuario</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_orden" name="orden" value="<?= htmlspecialchars($actividad['orden']) ?>" required>
                                <label>Orden</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_num_permiso" name="num_permiso" value="<?= htmlspecialchars($actividad['num_permiso']) ?>" required>
                                <label>Número de Permiso</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_planta" name="planta" value="<?= htmlspecialchars($actividad['planta']) ?>" required>
                                <label>Planta</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_ubicacion" name="ubicacion" value="<?= htmlspecialchars($actividad['ubicacion']) ?>" required>
                                <label>Ubicación</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_tag_number" name="tag_number" value="<?= htmlspecialchars($actividad['tag_number']) ?>" required>
                                <label>Tag Number</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="edit_actividad" name="actividad" style="height: 100px" required><?= htmlspecialchars($actividad['actividad']) ?></textarea>
                                <label>Actividad</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_especialistas" name="especialistas" value="<?= htmlspecialchars($actividad['especialistas']) ?>" required>
                                <label>Especialistas</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_tiempo" name="tiempo" value="<?= htmlspecialchars($actividad['tiempo']) ?>" required>
                                <label>Tiempo</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_recurso_apoyo" name="recurso_apoyo" value="<?= htmlspecialchars($actividad['recurso_apoyo']) ?>" required>
                                <label>Recurso de Apoyo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="edit_fecha" name="fecha" value="<?= htmlspecialchars($actividad['fecha']) ?>" required>
                                <label>Fecha</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_avance" name="avance" value="<?= htmlspecialchars($actividad['avance']) ?>" required>
                                <label>Avance (%)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="edit_observacion" name="observacion" style="height: 100px"><?= htmlspecialchars($actividad['observacion']) ?></textarea>
                                <label>Observación</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>