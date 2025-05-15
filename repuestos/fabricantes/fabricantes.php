<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/check_permission.php';
require_once __DIR__ . '/../../includes/audit.php';

// Consulta principal SIN paginación
$stmt = $pdo->query("
    SELECT id_fabricante, nombres 
    FROM fabricantes WHERE estado = 'activo'
    ORDER BY id_fabricante
");
$fabricantes = $stmt->fetchAll();

// Mostrar mensajes de éxito/error
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 1100;">
            Operación realizada con éxito
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 1100;">
            Error al realizar la operación
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

logModuleAccess('Fabricantes');


?>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>


    .print-area {
        display: none;
    }



    


    
</style>

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-1">Gestión de Fabricantes</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger no-print" data-bs-toggle="modal" data-bs-target="#agregarFabricanteModal">
                Agregar Fabricante
            </button>
            <button id="btnEditar" class="btn btn-danger no-print" disabled data-bs-toggle="modal" data-bs-target="#editarFabricanteModal">
                Editar
            </button>

        </div>
        <div style="width: 300px;">
            <div class="input-group">
                
                <input type="text" id="search" class="form-control no-print" placeholder="Buscar Fabricante" onkeyup="filtrarFabricantes()">
            </div>
        </div>
    </div>
</div>

<!-- Tabla de registros -->
<table class="table table-striped no-print">
    <thead class="table-dark">
        <tr>
            <th>#</th> <!-- Cambiado de "ID" a "#" -->
            <th>Nombre</th>
        </tr>
    </thead>
    <tbody id="tabla-fabricantes">
        <?php foreach ($fabricantes as $index => $fabricante): ?>
            <tr onclick="seleccionarFabricante('<?= $fabricante['id_fabricante'] ?>')" style="cursor: pointer;">
                <td><?= $index + 1 ?></td> <!-- Numeración secuencial (1, 2, 3...) -->
                <td><?= htmlspecialchars($fabricante['nombres']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Área para imprimir -->
<div class="print-area">
    <h2 class="text-center mb-4">Listado de Fabricantes</h2>
    <p class="text-end mb-3"><small>Fecha: <?= date('d/m/Y H:i') ?></small></p>
    <table class="table table-bordered table-print">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
            </tr>
        </thead>
        <tbody id="tabla-print">
            <?php foreach ($fabricantes as $fabricante): ?>
                <tr>
                    <td><?= htmlspecialchars($fabricante['id_fabricante']) ?></td>
                    <td><?= htmlspecialchars($fabricante['nombres']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-center mt-4"><small>Sistema de Gestión - © <?= date('Y') ?></small></p>
</div>

<!-- Modales -->
<!-- Modal para Agregar Fabricante -->
<div class="modal fade" id="agregarFabricanteModal" tabindex="-1" aria-labelledby="agregarFabricanteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarFabricanteModalLabel">
                    Agregar Fabricante
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarFabricante" action="repuestos/fabricantes/agregar_fabricante.php" method="POST">
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control" name="nombres" id="nombresFabricante" required 
                               maxlength="40" oninput="actualizarContador(this, 'contadorNombre')">
                        <label for="nombresFabricante">Nombre del Fabricante</label>
                        <div id="feedbackNombre" class="invalid-feedback">
                            Este fabricante ya existe en la base de datos o supera el límite de caracteres.
                        </div>
                        <div id="contadorNombre" class="char-counter">0/40 caracteres</div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">

                        <button type="submit" class="btn btn-primary" id="btnGuardarFabricante" disabled>
                            Guardar Fabricante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Fabricante -->
<div class="modal fade" id="editarFabricanteModal" tabindex="-1" aria-labelledby="editarFabricanteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="editarFabricanteModalLabel">
                    Editar Fabricante
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formEditarFabricante" action="repuestos/fabricantes/editar_fabricante.php" method="POST">
                    <input type="hidden" id="edit_id_fabricante" name="id_fabricante">
                    
                    <div class="form-floating mb-1">
                        <input type="text" class="form-control" id="edit_nombres" name="nombres" required
                               maxlength="40" oninput="validarNombreFabricanteEdicion(this.value, document.getElementById('edit_id_fabricante').value); actualizarContador(this, 'contadorNombreEdicion')">
                        <label for="edit_nombres">Nombre del Fabricante</label>
                        <div id="feedbackNombreEdicion" class="invalid-feedback">
                            Este fabricante ya existe en la base de datos o supera el límite de caracteres.
                        </div>
                        <div id="contadorNombreEdicion" class="char-counter">0/40 caracteres</div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary" id="btnGuardarEdicion" disabled>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script's -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar automáticamente las alertas después de 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Validar nombre al escribir en el modal de agregar
    document.getElementById('nombresFabricante').addEventListener('input', function() {
        validarNombreFabricante(this.value);
    });
});

let fabricanteSeleccionado = null;
let nombreFabricanteSeleccionado = '';

// Función para actualizar el contador de caracteres
function actualizarContador(input, contadorId) {
    const contador = document.getElementById(contadorId);
    const longitud = input.value.length;
    const maxLength = input.maxLength;
    
    contador.textContent = `${longitud}/${maxLength} caracteres`;
    
    // Cambiar color según el porcentaje de uso
    if (longitud > maxLength * 0.8) {
        contador.className = 'char-counter warning';
    } else if (longitud >= maxLength) {
        contador.className = 'char-counter danger';
    } else {
        contador.className = 'char-counter';
    }
}

// Función para seleccionar un fabricante
function seleccionarFabricante(id_fabricante) {
    // Remover la clase 'selected-row' de todas las filas
    document.querySelectorAll('#tabla-fabricantes tr').forEach(tr => {
        tr.classList.remove('selected-row');
    });

    // Agregar la clase 'selected-row' a la fila seleccionada
    const filaSeleccionada = document.querySelector(`#tabla-fabricantes tr[onclick*="${id_fabricante}"]`);
    if (filaSeleccionada) {
        filaSeleccionada.classList.add('selected-row');
        nombreFabricanteSeleccionado = filaSeleccionada.querySelector('td:nth-child(2)').textContent;
    }

    // Guardar el ID del fabricante seleccionado
    fabricanteSeleccionado = id_fabricante;

    // Habilitar los botones de acciones
    document.getElementById('btnEditar').disabled = false;
}



// Función para imprimir la tabla
function imprimirTabla() {
    // Actualizar la tabla de impresión con los mismos datos de búsqueda
    const searchValue = document.getElementById('search').value.trim();
    
    if (searchValue === "") {
        // Si no hay búsqueda, imprimir todos los registros
        window.print();
        return;
    }

    // Filtrar los registros para imprimir
    fetch(`repuestos/fabricantes/buscar_fabricantes.php?search=${encodeURIComponent(searchValue)}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const htmlDoc = parser.parseFromString(data, 'text/html');
            const rows = htmlDoc.querySelectorAll('#tabla-fabricantes tr');
            
            let printContent = '';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                printContent += `
                    <tr>
                        <td>${cells[0].textContent}</td>
                        <td>${cells[1].textContent}</td>
                    </tr>
                `;
            });
            
            document.getElementById('tabla-print').innerHTML = printContent;
            window.print();
        })
        .catch(error => {
            console.error('Error al buscar fabricantes:', error);
            window.print(); // Imprimir igualmente aunque falle la búsqueda
        });
}

// Función para filtrar fabricantes (búsqueda)
function filtrarFabricantes() {
    const searchValue = document.getElementById('search').value.trim();
    
    if (searchValue === "") {
        // Si el campo de búsqueda está vacío, recargar la página para mostrar todos
        window.location.reload();
        return;
    }

    fetch(`repuestos/fabricantes/buscar_fabricantes.php?search=${encodeURIComponent(searchValue)}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-fabricantes').innerHTML = data;
            fabricanteSeleccionado = null; // Reiniciar la selección
            document.getElementById('btnEditar').disabled = true;
        })
        .catch(error => console.error('Error al buscar fabricantes:', error));
}

// Función para validar el nombre del fabricante (agregar)
function validarNombreFabricante(nombre) {
    const input = document.getElementById('nombresFabricante');
    const feedback = document.getElementById('feedbackNombre');
    const btnGuardar = document.getElementById('btnGuardarFabricante');
    
    // Validar longitud máxima
    if (nombre.length > 40) {
        input.classList.add('is-invalid');
        feedback.textContent = 'El nombre no puede exceder los 40 caracteres.';
        btnGuardar.disabled = true;
        return;
    }
    
    // Validar que no esté vacío
    if (nombre.trim() === '') {
        input.classList.add('is-invalid');
        feedback.textContent = 'El nombre del fabricante es requerido.';
        btnGuardar.disabled = true;
        return;
    }
    
    // Verificar si el fabricante ya existe
    fetch(`repuestos/fabricantes/verificar_fabricante.php?nombre=${encodeURIComponent(nombre)}`)
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                input.classList.add('is-invalid');
                feedback.textContent = 'Este fabricante ya existe en la base de datos.';
                btnGuardar.disabled = true;
            } else {
                input.classList.remove('is-invalid');
                btnGuardar.disabled = nombre.trim() === '';
            }
        })
        .catch(error => {
            console.error('Error al verificar fabricante:', error);
            input.classList.remove('is-invalid');
            btnGuardar.disabled = nombre.trim() === '';
        });
}

// Función para validar el nombre del fabricante (edición)
function validarNombreFabricanteEdicion(nombre, idFabricante) {
    const input = document.getElementById('edit_nombres');
    const feedback = document.getElementById('feedbackNombreEdicion');
    const btnGuardar = document.getElementById('btnGuardarEdicion');
    
    // Validar longitud máxima
    if (nombre.length > 40) {
        input.classList.add('is-invalid');
        feedback.textContent = 'El nombre no puede exceder los 40 caracteres.';
        btnGuardar.disabled = true;
        return;
    }
    
    // Validar que no esté vacío
    if (nombre.trim() === '') {
        input.classList.add('is-invalid');
        feedback.textContent = 'El nombre del fabricante es requerido.';
        btnGuardar.disabled = true;
        return;
    }
    
    // Verificar si el fabricante ya existe (excluyendo el actual)
    fetch(`repuestos/fabricantes/verificar_fabricante.php?nombre=${encodeURIComponent(nombre)}&id_excluir=${idFabricante}`)
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                input.classList.add('is-invalid');
                feedback.textContent = 'Este fabricante ya existe en la base de datos.';
                btnGuardar.disabled = true;
            } else {
                input.classList.remove('is-invalid');
                btnGuardar.disabled = nombre.trim() === '';
            }
        })
        .catch(error => {
            console.error('Error al verificar fabricante:', error);
            input.classList.remove('is-invalid');
            btnGuardar.disabled = nombre.trim() === '';
        });
}

// Resetear el modal cuando se cierre
document.getElementById('agregarFabricanteModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('nombresFabricante').value = '';
    document.getElementById('nombresFabricante').classList.remove('is-invalid');
    document.getElementById('btnGuardarFabricante').disabled = true;
    document.getElementById('contadorNombre').textContent = '0/40 caracteres';
    document.getElementById('contadorNombre').className = 'char-counter';
});

// Modifica el evento del modal de edición para inicializar la validación
document.addEventListener('DOMContentLoaded', function() {
    const editarFabricanteModal = document.getElementById('editarFabricanteModal');
    editarFabricanteModal.addEventListener('show.bs.modal', function(event) {
        if (!fabricanteSeleccionado) return;
        
        fetch(`repuestos/fabricantes/obtener_detalles_fabricante.php?id_fabricante=${fabricanteSeleccionado}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_id_fabricante').value = data.id_fabricante;
                const inputNombre = document.getElementById('edit_nombres');
                inputNombre.value = data.nombres;
                
                // Actualizar contador
                const contador = document.getElementById('contadorNombreEdicion');
                contador.textContent = `${data.nombres.length}/40 caracteres`;
                
                // Cambiar color según el porcentaje de uso
                if (data.nombres.length > 40 * 0.8) {
                    contador.className = 'char-counter warning';
                } else if (data.nombres.length >= 40) {
                    contador.className = 'char-counter danger';
                } else {
                    contador.className = 'char-counter';
                }
                
                // Habilitar el botón inicialmente (asumiendo que el nombre actual es válido)
                document.getElementById('btnGuardarEdicion').disabled = false;
            })
            .catch(error => console.error('Error al obtener los detalles:', error));
    });
    
    // Resetear validación al cerrar el modal
    editarFabricanteModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('edit_nombres').classList.remove('is-invalid');
    });
});
</script>