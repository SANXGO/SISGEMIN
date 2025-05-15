<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/check_permission.php';
require_once __DIR__ . '/../includes/audit.php';


// Mostrar mensajes de éxito/error
if (isset($_GET['borrado']) && $_GET['borrado'] == 1) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Usuario eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Error: ' . htmlspecialchars($_GET['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

// Obtener lista de usuarios con JOIN para cargos
$stmt = $pdo->query("SELECT u.*, c.nombre_cargo 
                    FROM usuario u 
                    LEFT JOIN cargo c ON u.id_cargo = c.id_cargo WHERE u.estado = 'activo'");
$usuarios = $stmt->fetchAll();

// Obtener lista de plantas
$stmt_plantas = $pdo->query("SELECT id_planta, nombres FROM planta");
$plantas = $stmt_plantas->fetchAll();
$plantasMap = array_column($plantas, 'nombres', 'id_planta');

// Obtener lista de cargos para el select
$stmt_cargos = $pdo->query("SELECT id_cargo, nombre_cargo FROM cargo");
$cargos = $stmt_cargos->fetchAll();
$cargosMap = array_column($cargos, 'nombre_cargo', 'id_cargo');
logModuleAccess('Usuario');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table {
            margin-top: 80px;
        }
        .selected-row {
            background-color: #f8d7da !important;
        }
        /* Modal styles */
        .bg-gradient-primary {
            background: linear-gradient(#b71513 100%);
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
            background-color: #9c120f;
            border-color: #9c120f;
            transform: translateY(-2px);
        }
        .btn.disable, .btn:disabled {
            background-color: #b71513;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875em;
        }
        .spinner-border.text-danger {
            color: #b71513 !important;
            width: 2rem;
            height: 2rem;
            border-width: 0.25em;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h4 class="text-center mb-4">Gestión de Usuarios</h4>

        <!-- Barra de búsqueda y botones -->
        <nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <div class="d-grid gap-2 d-md-block">
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarUsuarioModal">
                                     Agregar Usuario
                                </button>

                            </div>
                        </li>
                    </ul>
                    <form class="d-flex" role="search">
                        <div class="input-group">
                            <input type="text" id="search" class="form-control" placeholder="Buscar por Cédula, Nombre, Apellido, Teléfono..." 
                                   onkeyup="filtrarUsuarios()">
                         
                        </div>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Tabla de registros -->
        <div class="table-responsive">
        <table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Cargo</th>   
            <th>Cédula</th>
            <th>Planta</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody id="tabla-usuarios">
        <?php foreach ($usuarios as $index => $usuario): ?>
        <tr data-id="<?= $usuario['id_usuario'] ?>" onclick="seleccionarID(<?= $usuario['id_usuario'] ?>)">
            <td>
                <a href="usuario/detalles_usuario.php?id=<?= $usuario['id_usuario'] ?>" class="text-danger">
                    <?= $index + 1 ?> <!-- index + 1 para que empiece en 1 -->
                </a>
            </td>
            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
            <td><?= htmlspecialchars($usuario['apellido']) ?></td>
            <td><?= htmlspecialchars($usuario['nombre_cargo'] ?? 'Sin cargo') ?></td>
            <td><?= htmlspecialchars($usuario['cedula']) ?></td>
            <td><?= isset($plantasMap[$usuario['id_planta']]) ? htmlspecialchars($plantasMap[$usuario['id_planta']]) : 'Planta no encontrada' ?></td>
            <td><?= htmlspecialchars($usuario['telefono']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>

        <!-- Modal para Agregar Usuario -->
        <div class="modal fade" id="agregarUsuarioModal" tabindex="-1" aria-labelledby="agregarUsuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <!-- Encabezado del Modal -->
                    <div class="modal-header bg-gradient-primary text-white">
                        <h3 class="modal-title fw-bold" id="agregarUsuarioModalLabel">
                           
                            Agregar Usuario
                        </h3>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- Cuerpo del Modal -->
                    <div class="modal-body p-4">
                        <form id="formAgregarUsuario" action="usuario/agregar_usuario.php" method="POST">
                            <div class="row g-3">
                                <!-- Columna 1 -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                               pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ ]{2,50}" 
                                               title="Solo letras (entre 2 y 50 caracteres)">
                                        <label for="nombre">Nombre</label>
                                        <div class="invalid-feedback">Solo letras (2-50 caracteres)</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="apellido" name="apellido" required
                                               pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ ]{2,50}" 
                                               title="Solo letras (entre 2 y 50 caracteres)">
                                        <label for="apellido">Apellido</label>
                                        <div class="invalid-feedback">Solo letras (2-50 caracteres)</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="correo" name="correo" required
                                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                               title="Ingrese un correo válido">
                                        <label for="correo">Correo</label>
                                        <div class="invalid-feedback">Ingrese un correo válido</div>
                                        <div id="correo-existe" class="text-danger small mt-1 d-none">Este correo ya está registrado</div>
                                    </div>
                                </div>
                                
                                <!-- Columna 2 -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="cedula" name="cedula" required
                                               pattern="[0-9]{6,12}" 
                                               title="Solo números (6-12 dígitos)">
                                        <label for="cedula">Cédula</label>
                                        <div class="invalid-feedback">Solo números (6-12 dígitos)</div>
                                        <div id="cedula-existe" class="text-danger small mt-1 d-none">Esta cédula ya está registrada</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="telefono" name="telefono" required
                                               pattern="[0-9+]{7,15}" 
                                               title="Solo números y + (7-15 caracteres)">
                                        <label for="telefono">Teléfono</label>
                                        <div class="invalid-feedback">Solo números y + (7-15 caracteres)</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="pass" name="pass" required
                                               minlength="5"
                                               pattern="^(?=.*[a-zA-Z]).{5,}$"
                                               title="Mínimo 5 caracteres y al menos una letra">
                                        <label for="pass">Contraseña</label>
                                        <div class="invalid-feedback">Mínimo 5 caracteres y al menos una letra</div>
                                    </div>
                                </div>
                                
                                <!-- Columna 3 -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="id_cargo" name="id_cargo" required>
                                            <option value="">Seleccione un cargo</option>
                                            <?php foreach ($cargos as $cargo): ?>
                                                <option value="<?= $cargo['id_cargo'] ?>"><?= htmlspecialchars($cargo['nombre_cargo']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="id_cargo">Cargo</label>
                                        <div class="invalid-feedback">Seleccione un cargo</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="id_planta" name="id_planta" required>
                                            <option value="">Seleccione una planta</option>
                                            <?php foreach ($plantas as $planta): ?>
                                                <option value="<?= $planta['id_planta'] ?>"><?= htmlspecialchars($planta['nombres']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="id_planta">Planta</label>
                                        <div class="invalid-feedback">Seleccione una planta</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="confirmar_pass" required
                                               minlength="5"
                                               pattern="^(?=.*[a-zA-Z]).{5,}$"
                                               title="Las contraseñas deben coincidir y contener al menos una letra">
                                        <label for="confirmar_pass">Confirmar Contraseña</label>
                                        <div class="invalid-feedback">Las contraseñas no coinciden o no contienen al menos una letra</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pie del Modal -->
                            <div class="modal-footer bg-light mt-4">
                               
                                <button type="submit" class="btn btn-danger">
                                     Guardar Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    let selectedUserId = null;

    // Función para seleccionar un usuario por su ID
    function seleccionarID(id_usuario) {
        // Remover la clase 'selected-row' de todas las filas
        const rows = document.querySelectorAll('#tabla-usuarios tr');
        rows.forEach(row => row.classList.remove('selected-row'));

        // Agregar la clase 'selected-row' a la fila seleccionada
        const selectedRow = document.querySelector(`#tabla-usuarios tr[data-id="${id_usuario}"]`);
        if (selectedRow) {
            selectedRow.classList.add('selected-row');
        }

        // Habilitar el botón de eliminar
        selectedUserId = id_usuario;
    }

    // Función para el buscador dinámico
    function filtrarUsuarios() {
        const input = document.getElementById('search');
        const filter = input.value.trim();
        const tabla = document.getElementById('tabla-usuarios');
        
        // Mostrar loader mientras busca
        tabla.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
        
        fetch(`usuario/buscar_usuario.php?search=${encodeURIComponent(filter)}`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta');
                return response.text();
            })
            .then(data => {
                tabla.innerHTML = data;
                // Restablecer selección después de filtrar
                selectedUserId = null;
                
                // Reasignar eventos de clic a las nuevas filas
                document.querySelectorAll('#tabla-usuarios tr[data-id]').forEach(row => {
                    row.addEventListener('click', function() {
                        seleccionarID(this.getAttribute('data-id'));
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                tabla.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos</td></tr>';
            });
    }

    

    document.addEventListener('DOMContentLoaded', function() {
        const formAgregar = document.getElementById('formAgregarUsuario');
        const passInput = document.getElementById('pass');
        const confirmPassInput = document.getElementById('confirmar_pass');
        const cedulaInput = document.getElementById('cedula');
        const correoInput = document.getElementById('correo');
        const cedulaExisteMsg = document.getElementById('cedula-existe');
        const correoExisteMsg = document.getElementById('correo-existe');
        
        // Validar que las contraseñas coincidan
        function validarContraseñas() {
            if (passInput.value !== confirmPassInput.value) {
                confirmPassInput.setCustomValidity("Las contraseñas no coinciden");
                confirmPassInput.classList.add('is-invalid');
                return false;
            } else {
                confirmPassInput.setCustomValidity("");
                confirmPassInput.classList.remove('is-invalid');
                return true;
            }
        }
        
        // Función para verificar existencia en BD
        async function verificarExistencia(campo, valor) {
            if (!valor || valor.length < (campo === 'cedula' ? 6 : 5)) return false;
            
            try {
                const response = await fetch(`usuario/verificar_existencia.php?campo=${campo}&valor=${encodeURIComponent(valor)}`);
                if (!response.ok) throw new Error('Error en la respuesta');
                
                const data = await response.json();
                return data.existe;
            } catch (error) {
                console.error('Error:', error);
                return false;
            }
        }
        
        // Event listeners para validación en tiempo real (agregar usuario)
        passInput.addEventListener('input', validarContraseñas);
        confirmPassInput.addEventListener('input', validarContraseñas);
        
        // Validar cédula al perder foco
        cedulaInput.addEventListener('blur', async function() {
            const cedula = this.value.trim();
            if (cedula.length >= 6) {
                const existe = await verificarExistencia('cedula', cedula);
                if (existe) {
                    cedulaInput.setCustomValidity('Cédula ya registrada');
                    cedulaExisteMsg.classList.remove('d-none');
                } else {
                    cedulaInput.setCustomValidity('');
                    cedulaExisteMsg.classList.add('d-none');
                }
                cedulaInput.classList.toggle('is-invalid', existe);
            }
        });
        
        // Validar correo al perder foco
        correoInput.addEventListener('blur', async function() {
            const correo = this.value.trim();
            if (correo.includes('@')) {
                const existe = await verificarExistencia('correo', correo);
                if (existe) {
                    correoInput.setCustomValidity('Correo ya registrado');
                    correoExisteMsg.classList.remove('d-none');
                } else {
                    correoInput.setCustomValidity('');
                    correoExisteMsg.classList.add('d-none');
                }
                correoInput.classList.toggle('is-invalid', existe);
            }
        });
        
        // Validación al enviar el formulario de agregar
        formAgregar.addEventListener('submit', async function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Verificar todas las validaciones
            const [cedulaExiste, correoExiste] = await Promise.all([
                verificarExistencia('cedula', cedulaInput.value.trim()),
                verificarExistencia('correo', correoInput.value.trim())
            ]);
            
            const passValida = validarContraseñas();
            
            // Marcar campos inválidos
            let formIsValid = true;
            formAgregar.querySelectorAll('input, select').forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    formIsValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            // Mostrar mensajes de error
            cedulaExisteMsg.classList.toggle('d-none', !cedulaExiste);
            correoExisteMsg.classList.toggle('d-none', !correoExiste);
            
            if (!formIsValid || cedulaExiste || correoExiste || !passValida) {
                formAgregar.classList.add('was-validated');
                return;
            }
            
            // Enviar formulario si todo está bien
            formAgregar.submit();
        });
        
        // Validación cuando se pierde el foco de un campo
        formAgregar.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('blur', function() {
                this.classList.toggle('is-invalid', !this.checkValidity());
            });
        });
    });
    </script>
</body>
</html>