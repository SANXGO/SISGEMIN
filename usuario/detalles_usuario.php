<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';

// Verificar si se proporcionó un ID de usuario
if (!isset($_GET['id'])) {
    header('Location: ../usuario.php');
    exit;
}

$id_usuario = $_GET['id'];

// Obtener los detalles del usuario
$stmt = $pdo->prepare("SELECT u.*, p.nombres AS nombre_planta, c.nombre_cargo 
                      FROM usuario u 
                      LEFT JOIN planta p ON u.id_planta = p.id_planta 
                      LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
                      WHERE u.estado = 'activo' AND u.id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

// Si no se encuentra el usuario, redirigir
if (!$usuario) {
    header('Location: ../index.php?tabla=usuario');
    exit;
}

logViewRecord('Usuario', $id_usuario, $usuario);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Usuario #<?= htmlspecialchars($usuario['id_usuario']) ?></title>
    <link rel="icon" href="../favicon.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">

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
            background: linear-gradient(135deg, #b71513 100%);
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
            border-bottom: 3px solid #b71513;
            background-color: transparent;
        }
        
        .form-floating label {
            color: #6c757d;
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

        a{
            color : #b71513;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Breadcrumb para mejor navegación -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="../index.php?tabla=usuario">Usuarios</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuario #<?= htmlspecialchars($usuario['id_usuario']) ?></li>
            </ol>
        </nav>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i>
                        <?= htmlspecialchars($usuario['nombre']) ?> <?= htmlspecialchars($usuario['apellido']) ?>
                        <span class="badge bg-secondary ms-2"><?= htmlspecialchars($usuario['nombre_cargo']) ?></span>
                    </h2>
                    <small class="text-white-50">ID: <?= htmlspecialchars($usuario['id_usuario']) ?></small>
                </div>
                <div class="btn-group">
                    <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#editarUsuarioModal" data-id="<?= $usuario['id_usuario'] ?>">
                        <i class="bi bi-pencil-square me-1"></i> Editar
                    </button>
                    <a href="../index.php?tabla=usuario" class="btn btn-custom">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Columna 1: Información Personal -->
                    <div class="col-md-4">
                        <div class="detail-item">
                            <strong><i class="bi bi-person me-2"></i>Nombre:</strong>
                            <span><?= htmlspecialchars($usuario['nombre']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-person me-2"></i>Apellido:</strong>
                            <span><?= htmlspecialchars($usuario['apellido']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-credit-card me-2"></i>Cédula:</strong>
                            <span><?= htmlspecialchars($usuario['cedula']) ?></span>
                        </div>
                    </div>

                    <!-- Columna 2: Información Laboral -->
                    <div class="col-md-4">
                        <div class="detail-item">
                            <strong><i class="bi bi-briefcase me-2"></i>Cargo:</strong>
                            <span><?= htmlspecialchars($usuario['nombre_cargo']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-building me-2"></i>Planta:</strong>
                            <span><?= htmlspecialchars($usuario['nombre_planta']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-key me-2"></i>Contraseña:</strong>
                            <span>•••••••</span>
                        </div>
                    </div>

                    <!-- Columna 3: Contacto -->
                    <div class="col-md-4">
                        <div class="detail-item">
                            <strong><i class="bi bi-telephone me-2"></i>Teléfono:</strong>
                            <span><?= htmlspecialchars($usuario['telefono']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-envelope me-2"></i>Correo:</strong>
                            <span><?= htmlspecialchars($usuario['correo']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">Última actualización: <?= date('d/m/Y H:i') ?></small>
                <small class="text-muted">ID: <?= htmlspecialchars($usuario['id_usuario']) ?></small>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <!-- Encabezado del Modal -->
                <div class="modal-header bg-gradient-primary text-white">
                    <h3 class="modal-title fw-bold" id="editarUsuarioModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Usuario: #<?= htmlspecialchars($usuario['id_usuario']) ?>
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4">
                    <form id="editarUsuarioForm" action="../usuario/editar_usuario.php" method="POST" novalidate>
                        <input type="hidden" id="edit_id_usuario" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                        
                        <!-- Sección de pestañas para mejor organización -->
                        <ul class="nav nav-tabs mb-4" id="usuarioTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab">
                                    <i class="bi bi-info-circle me-2"></i>Información Personal
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="laboral-tab" data-bs-toggle="tab" data-bs-target="#laboral-tab-pane" type="button" role="tab">
                                    <i class="bi bi-briefcase me-2"></i>Datos Laborales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad-tab-pane" type="button" role="tab">
                                    <i class="bi bi-shield-lock me-2"></i>Seguridad
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="usuarioTabsContent">
                            <!-- Pestaña 1: Información Personal -->
                            <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- Nombre -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_nombre" name="nombre" 
                                                   pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]{2,50}" 
                                                   title="Solo letras (mínimo 2 caracteres)" required
                                                   value="<?= htmlspecialchars($usuario['nombre']) ?>">
                                            <label for="edit_nombre">Nombre</label>
                                            <div class="invalid-feedback">El nombre debe contener solo letras (2-50 caracteres)</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Apellido -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_apellido" name="apellido" 
                                                   pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]{2,50}" 
                                                   title="Solo letras (mínimo 2 caracteres)" required
                                                   value="<?= htmlspecialchars($usuario['apellido']) ?>">
                                            <label for="edit_apellido">Apellido</label>
                                            <div class="invalid-feedback">El apellido debe contener solo letras (2-50 caracteres)</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Cédula -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="edit_cedula" name="cedula" 
                                                   pattern="[0-9]{6,12}" title="6-12 dígitos" required
                                                   value="<?= htmlspecialchars($usuario['cedula']) ?>">
                                            <label for="edit_cedula">Cédula</label>
                                            <div class="invalid-feedback">Ingrese una cédula válida (6-12 dígitos)</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Teléfono -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="edit_telefono" name="telefono" 
                                                   pattern="[0-9]{7,15}" title="Formato: 7-15 dígitos" required
                                                   value="<?= htmlspecialchars($usuario['telefono']) ?>">
                                            <label for="edit_telefono">Teléfono</label>
                                            <div class="invalid-feedback">Ingrese un teléfono válido (7-15 dígitos)</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Correo -->
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="edit_correo" name="correo" 
                                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required
                                                   value="<?= htmlspecialchars($usuario['correo']) ?>">
                                            <label for="edit_correo">Correo</label>
                                            <div class="invalid-feedback">Ingrese un correo electrónico válido</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 2: Datos Laborales -->
                            <div class="tab-pane fade" id="laboral-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- Cargo -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="edit_id_cargo" name="id_cargo" required>
                                                <option value="">Seleccione un cargo</option>
                                                <?php 
                                                $stmt_cargos = $pdo->query("SELECT * FROM cargo");
                                                $cargos = $stmt_cargos->fetchAll();
                                                foreach ($cargos as $cargo): ?>
                                                    <option value="<?= $cargo['id_cargo'] ?>" <?= $usuario['id_cargo'] == $cargo['id_cargo'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="edit_id_cargo">Cargo</label>
                                            <div class="invalid-feedback">Seleccione un cargo</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Planta -->
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="edit_id_planta" name="id_planta" required>
                                                <option value="">Seleccione una planta</option>
                                                <?php 
                                                $stmt_plantas = $pdo->query("SELECT * FROM planta");
                                                $plantas = $stmt_plantas->fetchAll();
                                                foreach ($plantas as $planta): ?>
                                                    <option value="<?= $planta['id_planta'] ?>" <?= $usuario['id_planta'] == $planta['id_planta'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($planta['nombres']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="edit_id_planta">Planta</label>
                                            <div class="invalid-feedback">Seleccione una planta</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña 3: Seguridad -->
                            <div class="tab-pane fade" id="seguridad-tab-pane" role="tabpanel" tabindex="0">
                                <div class="row g-3">
                                    <!-- Contraseña -->
                                    <div class="col-md-12">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="edit_pass" name="pass" 
                                                   minlength="5" 
                                                   pattern="^(?=.*[a-zA-Z]).{5,}$"
                                                   title="Mínimo 5 caracteres y al menos una letra" required
                                                   value="<?= htmlspecialchars($usuario['pass']) ?>">
                                            <label for="edit_pass">Contraseña</label>
                                            <div class="invalid-feedback">La contraseña debe tener al menos 5 caracteres y una letra</div>
                                            <div class="form-text">Mínimo 5 caracteres y al menos una letra</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pie del Modal -->
                        <div class="modal-footer bg-light mt-4">
                           
                            <button type="submit" class="btn btn-danger">
                                 Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obtenemos el formulario
        const form = document.getElementById('editarUsuarioForm');
        
        // Configuramos la validación del formulario
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Validamos el formulario
            if (form.checkValidity()) {
                // Si es válido, lo enviamos
                form.submit();
            } else {
                // Si no es válido, mostramos los mensajes de error
                form.classList.add('was-validated');
            }
        }, false);
        
        // Validación en tiempo real para los campos
        form.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                if (input.checkValidity()) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            });
        });
        
        // Validación de cédula única
        const cedulaInput = document.getElementById('edit_cedula');
        cedulaInput.addEventListener('blur', function() {
            const cedula = this.value.trim();
            const idUsuario = document.getElementById('edit_id_usuario').value;
            
            if (cedula.length >= 6) {
                fetch(`../usuario/verificar_existencia.php?campo=cedula&valor=${cedula}&excluir=${idUsuario}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.existe) {
                            cedulaInput.setCustomValidity('Esta cédula ya está registrada');
                            cedulaInput.classList.add('is-invalid');
                            const feedback = cedulaInput.nextElementSibling.nextElementSibling;
                            feedback.textContent = 'Esta cédula ya está registrada';
                        } else {
                            cedulaInput.setCustomValidity('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
        
        // Validación de correo único
        const correoInput = document.getElementById('edit_correo');
        correoInput.addEventListener('blur', function() {
            const correo = this.value.trim();
            const idUsuario = document.getElementById('edit_id_usuario').value;
            
            if (correo.length > 0) {
                fetch(`../usuario/verificar_existencia.php?campo=correo&valor=${correo}&excluir=${idUsuario}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.existe) {
                            correoInput.setCustomValidity('Este correo ya está registrado');
                            correoInput.classList.add('is-invalid');
                            const feedback = correoInput.nextElementSibling.nextElementSibling;
                            feedback.textContent = 'Este correo ya está registrado';
                        } else {
                            correoInput.setCustomValidity('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });
    </script>
</body>
</html>