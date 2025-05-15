<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';


// Verificar si se proporcionó un tag_number
if (!isset($_GET['tag_number'])) {
    header("Location: index.php");
    exit();
}

$tagNumber = $_GET['tag_number'];

// Consulta para obtener los detalles del equipo
try {
    $stmt = $pdo->prepare("SELECT * FROM equipos WHERE Tag_Number = ?");
    $stmt->execute([$tagNumber]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$equipo) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}

// Obtener la planta del usuario logueado
try {
    $stmtPlanta = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmtPlanta->execute([$_SESSION['id_usuario']]);
    $usuario = $stmtPlanta->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        die("Usuario no encontrado.");
    }
    $idPlanta = $usuario['id_planta'];
} catch (PDOException $e) {
    die("Error al obtener planta del usuario: " . $e->getMessage());
}

// Obtener las ubicaciones para el modal de editar filtradas por planta
try {
    $stmtUbicaciones = $pdo->prepare("SELECT * FROM ubicacion WHERE id_planta = ?");
    $stmtUbicaciones->execute([$idPlanta]);
    $ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener ubicaciones: " . $e->getMessage());
}

// Obtener datos históricos de gráficos para este equipo
try {
    $stmtDatos = $pdo->prepare("SELECT * FROM equipo_graficos WHERE tag_number = ? ORDER BY fecha_creacion DESC");
    $stmtDatos->execute([$tagNumber]);
    $datosGraficos = $stmtDatos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos gráficos: " . $e->getMessage());
}



logViewRecord('equipos', $tagNumber, $equipo);


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles Instrumento <?= htmlspecialchars($equipo['Tag_Number']) ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="icon" href="../favicon.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color:#b71513;
            --secondary-color: #540212;
            --accent-color: #b71513;;
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
        
        .pdf-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .pdf-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge-custom {
            background-color: var(--secondary-color);
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
            background:  #b71513;
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
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
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

        a {
            color:#b71513;
        }
        
        h3 {
            color:rgb(255, 255, 255);
        }

        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .alert {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        /* Estilos para la imagen del equipo */
        .equipo-imagen {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .imagen-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .sin-imagen {
            background-color: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }



        .card-header.bg-primary {
    background-color: #b71513 !important;
}





















/* Estilos mejorados para la sección de gráficos */
.graficos-section {
    margin-top: 30px;
    padding: 25px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Contenedor del gráfico - tamaño aumentado y mejorado */
.grafico-container {
    height: 600px;  /* Aumentamos significativamente el tamaño */
    width: 100%;
    margin: 0 auto;
    position: relative;
    min-height: 300px; /* Altura mínima para móviles */
}

/* Canvas del gráfico - ajuste para ocupar todo el espacio */
.grafico-container canvas {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

/* Ajustes para tarjetas */
.card-body {
    padding: 1.5rem 2rem; /* Más espacio interno */
}

/* Tamaños de fuente aumentados */
.graficos-section h4 {
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
}

.card-header h5 {
    font-size: 1.5rem;
    font-weight: 600;
}

/* Media queries para responsividad */
@media (max-width: 992px) {
    .grafico-container {
        height: 500px;
    }
}

@media (max-width: 768px) {
    .grafico-container {
        height: 400px;
    }
    
    .graficos-section h4 {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .grafico-container {
        height: 350px;
    }
}




    </style>
</head>
<body>
<div id="alertContainer"></div>

<div class="container py-4">
    <!-- Breadcrumb para mejor navegación -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="../index.php?tabla=equipos">Instrumento</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($equipo['Tag_Number']) ?></li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-tags me-2"></i>
                    <?= htmlspecialchars($equipo['Tag_Number']) ?>
                    <span class="badge bg-secondary ms-2"><?= htmlspecialchars($equipo['Instrument_Type_Desc']) ?></span>
                </h2>
                <small class="text-white-50">ID: <?= htmlspecialchars($equipo['Tag_Number']) ?></small>
            </div>
            <div class="btn-group">
                <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#editarModal">
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </button>
                <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#subirPdfModal">
                    <i class="bi bi-upload me-1"></i> Subir PDF
                </button>
                <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#verPdfsModal">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDFs
                </button>
                



                <a href="javascript:history.back()" class="btn btn-custom">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>


            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- Columna izquierda para la imagen -->
                <div class="col-md-4">
                    <div class="imagen-container" style="width: 100%; max-width: 400px; height: 500px; overflow: hidden; display: flex; justify-content: center; align-items: center; border: 1px solid #ddd;">
                        <?php 
                        $rutaImagen = '../assets/img/equipos/' . basename($equipo['foto_perfil']);
                        $rutaCompleta = __DIR__ . '../../assets/img/equipos/' . basename($equipo['foto_perfil']);
                        
                        if (!empty($equipo['foto_perfil']) && file_exists($rutaCompleta)): ?>
                            <img src="<?= htmlspecialchars($rutaImagen) ?>" 
                                 alt="Foto del equipo <?= htmlspecialchars($equipo['Tag_Number']) ?>" 
                                 class="equipo-imagen"
                                 id="fotoEquipo"
                                 style="cursor: pointer; width: 100%; height: 100%; object-fit: cover;"
                                 title="Haz clic para cambiar la foto">
                        <?php else: ?>
                            <div class="sin-imagen" id="sinImagen" 
                                 style="cursor: pointer; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;"
                                 onclick="document.getElementById('edit_foto_perfil').click()">
                                <i class="bi bi-camera" style="font-size: 2rem;"></i>
                                <p class="mt-2">No hay imagen disponible para este instrumento</p>
                                <p class="text-muted small">Haz clic para agregar una foto</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Columna derecha para el contenido -->
                <div class="col-md-8">
                    <div class="row">
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong><i class="bi bi-geo-alt me-2"></i>Ubicación:</strong>
                                <span><?= htmlspecialchars($equipo['id_ubicacion']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-123 me-2"></i>Cantidad:</strong>
                                <span><?= htmlspecialchars($equipo['Cantidad']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-pin-map me-2"></i>F Location:</strong>
                                <span><?= htmlspecialchars($equipo['F_location']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-box me-2"></i>Po Number:</strong>
                                <span><?= htmlspecialchars($equipo['Po_Number']) ?></span>
                            </div>
                        </div>

                        <!-- Columna 2 -->
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong><i class="bi bi-tools me-2"></i>Service Upper:</strong>
                                <span><?= htmlspecialchars($equipo['Service_Upper']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-123 me-2"></i>P ID No:</strong>
                                <span><?= htmlspecialchars($equipo['P_ID_No']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-tag me-2"></i>SYS TAG:</strong>
                                <span><?= htmlspecialchars($equipo['SYS_TAG']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-arrows-angle-expand me-2"></i>Line Size:</strong>
                                <span><?= htmlspecialchars($equipo['Line_size']) ?></span>
                            </div>

                        
                        </div>
                    </div>

                    <!-- Segunda fila de detalles -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong><i class="bi bi-speedometer2 me-2"></i>Rating:</strong>
                                <span><?= htmlspecialchars($equipo['Rating']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-compass me-2"></i>Facing:</strong>
                                <span><?= htmlspecialchars($equipo['Facing']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong><i class="bi bi-diagram-3 me-2"></i>Lineclass:</strong>
                                <span><?= htmlspecialchars($equipo['Lineclass']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="bi bi-box-arrow-in-down me-2"></i>SYSTEM IN:</strong>
                                <span><?= htmlspecialchars($equipo['SYSTEM_IN']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles adicionales -->
                    <div class="detail-item mt-3">
                        <strong><i class="bi bi-plug me-2"></i>Junction Box No:</strong>
                        <span><?= htmlspecialchars($equipo['Junction_box_no']) ?></span>
                    </div>
                    <!-- En la sección de detalles, agregar estos nuevos campos -->
<div class="row mt-3">
    <div class="col-md-6">
        <div class="detail-item">
            <strong><i class="bi bi-tools me-2"></i>Herramientas:</strong>
            <span><?= htmlspecialchars($equipo['Herramientas']) ?></span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="detail-item">
            <strong><i class="bi bi-shield me-2"></i>Empacadura:</strong>
            <span><?= htmlspecialchars($equipo['Empacadura']) ?></span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="detail-item">
            <strong><i class="bi bi-nut me-2"></i>Esparragos:</strong>
            <span><?= htmlspecialchars($equipo['Esparragos']) ?></span>
        </div>
    </div>
</div>
                </div>
                
            </div>

            <!-- Sección expandible para detalles adicionales -->
            <div class="mt-4">
                <a class="d-flex align-items-center text-decoration-none" data-bs-toggle="collapse" href="#moreDetails" role="button">
                    <i class="bi bi-chevron-down me-2"></i>
                    <strong class="text-danger">Mostrar más detalles</strong>
                </a>
                
                <div class="collapse mt-2" id="moreDetails">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong>SYSTEM OUT:</strong>
                                <span><?= htmlspecialchars($equipo['SYSTEM_OUT']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>IO TYPE OUT:</strong>
                                <span><?= htmlspecialchars($equipo['IO_TYPE_OUT']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>SIGNAL COND:</strong>
                                <span><?= htmlspecialchars($equipo['SIGNAL_COND']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <strong>CRTL ACT:</strong>
                                <span><?= htmlspecialchars($equipo['CRTL_ACT']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>STATE 0:</strong>
                                <span><?= htmlspecialchars($equipo['STATE_0']) ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>STATE 1:</strong>
                                <span><?= htmlspecialchars($equipo['STATE_1']) ?></span>
                            </div>
                        </div>

                        
                    </div>
                </div>
            </div>
        </div>
        





<!-- Sección de Gráficos -->
<div class="graficos-section mt-4">
    <h4 class="mb-4"><i class="bi bi-graph-up me-2"></i>Gráficos del Instrumento</h4>
    
    <!-- Formulario para ingresar datos -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Ingresar Datos para Gráfico</h5>
        </div>
        <div class="card-body">
            <form id="formGrafico">
                <input type="hidden" name="tag_number" value="<?= htmlspecialchars($tagNumber) ?>">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-floating mb-3">
                            <input type="number" step="any" class="form-control" id="ejeX" name="ejeX" placeholder="Eje X" required>
                            <label for="ejeX">Eje X</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-floating mb-3">
                            <input type="number" step="any" class="form-control" id="ejeY" name="ejeY" placeholder="Eje Y" required>
                            <label for="ejeY">Eje Y</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> Agregar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Gráfico -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Visualización de Datos</h5>
        </div>
        <div class="card-body">
            <div class="grafico-container">
                <canvas id="graficoEquipo"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tabla de datos históricos -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Historial de Datos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datos-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Eje X</th>
                            <th>Eje Y</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($datosGraficos) > 0): ?>
                            <?php foreach ($datosGraficos as $dato): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($dato['fecha_creacion']))) ?></td>
                                    <td><?= htmlspecialchars($dato['eje_x']) ?></td>
                                    <td><?= htmlspecialchars($dato['eje_y']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarDato(<?= $dato['id'] ?>)">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay datos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>









        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <small class="text-muted">Última actualización: <?= date('d/m/Y H:i') ?></small>
        </div>
    </div>
</div>

    <!-- Modal para Editar Equipo -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <!-- Encabezado del Modal -->
                <div class="modal-header bg-gradient-primary text-white">
                    <h3 class="modal-title fw-bold" id="editarModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Equipo: <?= htmlspecialchars($equipo['Tag_Number']) ?>
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4">
                    <form action="../equipo/editar.php" method="POST" id="formEditarEquipo" enctype="multipart/form-data">
                        <!-- Sección de pestañas para mejor organización -->
                        <ul class="nav nav-tabs mb-4" id="equipoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tecnicos-tab" data-bs-toggle="tab" data-bs-target="#tecnicos-tab-pane" type="button" role="tab">
                                    <i class="bi bi-tools me-2"></i>Datos Técnicos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema-tab-pane" type="button" role="tab">
                                    <i class="bi bi-gear me-2"></i>Configuración
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="otros-tab" data-bs-toggle="tab" data-bs-target="#otros-tab-pane" type="button" role="tab">
                                    <i class="bi bi-file-earmark-text me-2"></i>Otros Datos
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="equipoTabsContent">
                            <!-- Pestaña 1: Información Básica -->
                            <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- Tag Number (solo lectura) -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-light" id="edit_tag_number" name="tag_number" 
                                                   value="<?= htmlspecialchars($equipo['Tag_Number']) ?>" readonly>
                                            <label for="edit_tag_number">Tag Number</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Ubicación -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="edit_unit" name="id_ubicacion" required>
                                                <option value="">Seleccionar...</option>
                                                <?php foreach ($ubicaciones as $ubicacion): ?>
                                                    <option value="<?= htmlspecialchars($ubicacion['id_ubicacion']) ?>" <?= $ubicacion['id_ubicacion'] == $equipo['id_ubicacion'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($ubicacion['id_ubicacion']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="edit_unit">Ubicación</label>
                                        </div>
                                    </div>
                      
                                    <!-- Instrument Type -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_instrument_type" name="instrument_type" 
                                                   value="<?= htmlspecialchars($equipo['Instrument_Type_Desc']) ?>" required>
                                            <label for="edit_instrument_type">Instrument Type</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Cantidad -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="edit_cantidad" name="cantidad" 
                                                   value="<?= htmlspecialchars($equipo['Cantidad']) ?>">
                                            <label for="edit_cantidad">Cantidad</label>
                                        </div>
                                    </div>
                                    
<!-- En la pestaña de Información Básica del modal -->
<div class="col-md-12">
    <div class="mb-3">
        <label for="edit_foto_perfil" class="form-label">Foto del Equipo</label>
        <input type="file" class="form-control d-none" id="edit_foto_perfil" name="foto_perfil" accept="image/*">
        <small class="text-muted">Formatos aceptados: JPG, PNG, GIF (Máx. 2MB)</small>
        <?php if (!empty($equipo['foto_perfil']) && file_exists($rutaCompleta)): ?>
            <div class="mt-2">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="eliminar_foto" name="eliminar_foto">
                    <label class="form-check-label" for="eliminar_foto">Eliminar foto actual</label>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 2: Datos Técnicos -->
                            <div class="tab-pane fade" id="tecnicos-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- F Location -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_f_location" name="f_location" 
                                                   value="<?= htmlspecialchars($equipo['F_location']) ?>">
                                            <label for="edit_f_location">F Location</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Service Upper -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_service_upper" name="service_upper" 
                                                   value="<?= htmlspecialchars($equipo['Service_Upper']) ?>">
                                            <label for="edit_service_upper">Service Upper</label>
                                        </div>
                                    </div>
                                    
                                    <!-- P ID No -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_p_id_no" name="p_id_no" 
                                                   value="<?= htmlspecialchars($equipo['P_ID_No']) ?>">
                                            <label for="edit_p_id_no">P ID No</label>
                                        </div>
                                    </div>
                                    
                                    <!-- SYS TAG -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_sys_tag" name="sys_tag" 
                                                   value="<?= htmlspecialchars($equipo['SYS_TAG']) ?>">
                                            <label for="edit_sys_tag">SYS TAG</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Line Size -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_line_size" name="line_size" 
                                                   value="<?= htmlspecialchars($equipo['Line_size']) ?>">
                                            <label for="edit_line_size">Line Size</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_rating" name="rating" 
                                                   value="<?= htmlspecialchars($equipo['Rating']) ?>">
                                            <label for="edit_rating">Rating</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 3: Configuración -->
                            <div class="tab-pane fade" id="sistema-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- Facing -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_facing" name="facing" 
                                                   value="<?= htmlspecialchars($equipo['Facing']) ?>">
                                            <label for="edit_facing">Facing</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Lineclass -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_lineclass" name="lineclass" 
                                                   value="<?= htmlspecialchars($equipo['Lineclass']) ?>">
                                            <label for="edit_lineclass">Lineclass</label>
                                        </div>
                                    </div>
                                    
                                    <!-- SYSTEM IN -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_system_in" name="system_in" 
                                                   value="<?= htmlspecialchars($equipo['SYSTEM_IN']) ?>">
                                            <label for="edit_system_in">SYSTEM IN</label>
                                        </div>
                                    </div>
                                    
                                    <!-- SYSTEM OUT -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_system_out" name="system_out" 
                                                   value="<?= htmlspecialchars($equipo['SYSTEM_OUT']) ?>">
                                            <label for="edit_system_out">SYSTEM OUT</label>
                                        </div>
                                    </div>
                                    
                                    <!-- IO TYPE OUT -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_io_type_out" name="io_type_out" 
                                                   value="<?= htmlspecialchars($equipo['IO_TYPE_OUT']) ?>">
                                            <label for="edit_io_type_out">IO TYPE OUT</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 4: Otros Datos -->
                            <div class="tab-pane fade" id="otros-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- SIGNAL COND -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_signal_cond" name="signal_cond" 
                                                   value="<?= htmlspecialchars($equipo['SIGNAL_COND']) ?>">
                                            <label for="edit_signal_cond">SIGNAL COND</label>
                                        </div>
                                    </div>
                                    
                                    <!-- CRTL ACT -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_crtl_act" name="crtl_act" 
                                                   value="<?= htmlspecialchars($equipo['CRTL_ACT']) ?>">
                                            <label for="edit_crtl_act">CRTL ACT</label>
                                        </div>
                                    </div>
                                    
                                    <!-- STATE 0 -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_state_0" name="state_0" 
                                                   value="<?= htmlspecialchars($equipo['STATE_0']) ?>">
                                            <label for="edit_state_0">STATE 0</label>
                                        </div>
                                    </div>
                                    
                                    <!-- STATE 1 -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_state_1" name="state_1" 
                                                   value="<?= htmlspecialchars($equipo['STATE_1']) ?>">
                                            <label for="edit_state_1">STATE 1</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Po Number -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_po_number" name="po_number" 
                                                   value="<?= htmlspecialchars($equipo['Po_Number']) ?>">
                                            <label for="edit_po_number">Po Number</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Junction Box No -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_junction_box_no" name="junction_box_no" 
                                                   value="<?= htmlspecialchars($equipo['Junction_box_no']) ?>">
                                            <label for="edit_junction_box_no">Junction Box No</label>
                                        </div>
                                    </div>


                                    <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_herramientas" name="herramientas" 
                       value="<?= htmlspecialchars($equipo['Herramientas']) ?>">
                <label for="edit_herramientas">Herramientas</label>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_empacadura" name="empacadura" 
                       value="<?= htmlspecialchars($equipo['Empacadura']) ?>">
                <label for="edit_empacadura">Empacadura</label>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_esparragos" name="esparragos" 
                       value="<?= htmlspecialchars($equipo['Esparragos']) ?>">
                <label for="edit_esparragos">Esparragos</label>
            </div>
        </div>
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



    
    <!-- Modal para Subir PDF -->
    <div class="modal fade" id="subirPdfModal" tabindex="-1" aria-labelledby="subirPdfModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="subirPdfModalLabel">Subir PDF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="../equipo/subir_pdf.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tag_number" value="<?= htmlspecialchars($equipo['Tag_Number']) ?>">
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">Seleccionar archivo PDF</label>
                            <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Subir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver PDFs -->
    <div class="modal fade" id="verPdfsModal" tabindex="-1" aria-labelledby="verPdfsModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="verPdfsModalLabel">Archivos PDF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="pdfs-list">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM archivos_pdf WHERE Tag_Number = ?");
                        $stmt->execute([$equipo['Tag_Number']]);
                        $pdfs = $stmt->fetchAll();

                        if (count($pdfs) > 0) {
                            foreach ($pdfs as $pdf) {
                                echo "<div class='pdf-item'>
                                        <a href='{$pdf['ruta_archivo']}' target='_blank'>{$pdf['ruta_archivo']}</a>
                                        <button class='btn btn-danger btn-sm' onclick='eliminarPdf({$pdf['id']})'>Eliminar</button>
                                      </div>";
                            }
                        } else {
                            echo "<p>No hay archivos PDF asociados a este equipo.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
     // Función para mostrar alertas flotantes
     function showFloatingAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.style.minWidth = '300px';
        alertDiv.style.marginBottom = '10px';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center">
                
                <div>${message}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
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

    // Manejar el envío del formulario de edición
    document.getElementById('formEditarEquipo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert(data.message, 'success');

                // Cerrar el modal después de 1.5 segundos
                setTimeout(() => {
                    location.reload();

                    const modal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
                    modal.hide();


                    
                }, 3000);
            } else {
                showFloatingAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showFloatingAlert('Error al procesar la solicitud', 'danger');
            console.error('Error:', error);
        });
    });

    // Manejar el envío del formulario de subir PDF
    document.querySelector('#subirPdfModal form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert(data.message, 'success');
                // Cerrar el modal después de 1.5 segundos
                setTimeout(() => {
                    location.reload();

                    const modal = bootstrap.Modal.getInstance(document.getElementById('subirPdfModal'));
                    modal.hide()
                    // Actualizar la lista de PDFs
                    fetch(`../equipo/obtener_pdfs.php?tag_number=<?= $equipo['Tag_Number'] ?>`)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('pdfs-list').innerHTML = html;
                        });
                }, 3000);
            } else {
                showFloatingAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showFloatingAlert('Error al subir el archivo', 'danger');
            console.error('Error:', error);
        });
    });

    function eliminarPdf(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
            fetch('../equipo/eliminar_pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showFloatingAlert(data.message, 'success');
                    // Actualizar la lista de PDFs
                    fetch(`../equipo/obtener_pdfs.php?tag_number=<?= $equipo['Tag_Number'] ?>`)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('pdfs-list').innerHTML = html;
                        });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error al eliminar el archivo:', error);
                showFloatingAlert('Error: ' + error.message, 'danger');
            });
        }
    }

    // Mostrar alerta si hay un mensaje en la URL (para cuando se redirige después de una acción)
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const messageType = urlParams.get('type');
        
        if (message && messageType) {
            showFloatingAlert(decodeURIComponent(message), messageType);
        }
    });




   
document.addEventListener('DOMContentLoaded', function() {
    const fotoEquipo = document.getElementById('fotoEquipo');
    const sinImagen = document.getElementById('sinImagen');
    const inputFoto = document.getElementById('edit_foto_perfil');
    
    if (fotoEquipo) {
        fotoEquipo.addEventListener('click', function() {
            inputFoto.click();
        });
    }

    if (sinImagen) {
        sinImagen.addEventListener('click', function() {

        });
    }

    // Mostrar vista previa cuando se selecciona una nueva imagen
    inputFoto.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Si hay una imagen existente, la actualizamos
                if (fotoEquipo) {
                    fotoEquipo.src = e.target.result;
                } else {
                    // Si no hay imagen, creamos una nueva
                    const imagenContainer = document.querySelector('.imagen-container');
                    imagenContainer.innerHTML = `
                        <img src="${e.target.result}" 
                             alt="Nueva foto del equipo" 
                             class="equipo-imagen"
                             id="fotoEquipo"
                             style="cursor: pointer;"
                             title="Haz clic para cambiar la foto">
                    `;
                    // Volvemos a asignar el event listener
                    document.getElementById('fotoEquipo').addEventListener('click', function() {
                        inputFoto.click();
                    });
                }
            }
            
            reader.readAsDataURL(this.files[0]);
            
            // Mostrar mensaje de que la foto se actualizará al guardar
            showFloatingAlert('La foto se actualizará cuando guardes los cambios', 'info');
        }
    });
});





    // Gráfico y manejo de datos
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar gráfico
        const ctx = document.getElementById('graficoEquipo').getContext('2d');
        let grafico = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Datos del Equipo',
                    backgroundColor: 'rgba(183, 21, 19, 0.7)',
                    borderColor: 'rgba(183, 21, 19, 1)',
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Eje X'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Eje Y'
                        }
                    }
                }
            }
        });

        // Cargar datos existentes en el gráfico
        function cargarDatosGrafico() {
            fetch(`../equipo/obtener_datos_grafico.php?tag_number=<?= $tagNumber ?>`)
                .then(response => response.json())
                .then(data => {
                    grafico.data.datasets[0].data = data.map(item => ({
                        x: parseFloat(item.eje_x),
                        y: parseFloat(item.eje_y)
                    }));
                    grafico.update();
                })
                .catch(error => {
                    console.error('Error al cargar datos:', error);
                });
        }

        // Cargar datos iniciales
        cargarDatosGrafico();

        // Manejar envío del formulario de gráfico
        document.getElementById('formGrafico').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../equipo/guardar_dato_grafico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFloatingAlert('Dato guardado correctamente', 'success');
                    // Actualizar gráfico y tabla
                    cargarDatosGrafico();
                    // Recargar la tabla
                    fetch(`../equipo/obtener_tabla_datos.php?tag_number=<?= $tagNumber ?>`)
                        .then(response => response.text())
                        .then(html => {
                            document.querySelector('.datos-table tbody').innerHTML = html;
                        });
                    // Limpiar formulario
                    this.reset();
                } else {
                    showFloatingAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showFloatingAlert('Error al guardar el dato', 'danger');
                console.error('Error:', error);
            });
        });
    });
    function eliminarDato(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este dato?')) {
            fetch('../equipo/eliminar_dato_grafico.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFloatingAlert('Dato eliminado correctamente', 'success');
                    // Recargar la página para actualizar gráfico y tabla
                    location.reload();
                } else {
                    showFloatingAlert('Error al eliminar el dato', 'danger');
                }
            })
            .catch(error => {
                showFloatingAlert('Error al eliminar el dato', 'danger');
                console.error('Error:', error);
            });
        }
    }
</script>
</body>
</html>