document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda en tiempo real
    document.getElementById('search').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const tagNumber = row.querySelector('td').textContent.toLowerCase();
            if (tagNumber.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Paginación
    document.getElementById('pagination').addEventListener('change', function() {
        const rows = document.querySelectorAll('tbody tr');
        const value = this.value;
        if (value === 'all') {
            rows.forEach(row => row.style.display = '');
        } else {
            rows.forEach((row, index) => {
                if (index < value) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });

    // Modal de editar
    const editarModal = document.getElementById('editarModal');
    editarModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const tagNumber = button.getAttribute('data-id');
        const row = button.closest('tr');
        const unit = row.querySelector('td:nth-child(2)').textContent;
        const instrumentType = row.querySelector('td:nth-child(3)').textContent;

        const modal = this;
        modal.querySelector('#edit_tag_number').value = tagNumber;
        modal.querySelector('#edit_unit').value = unit;
        modal.querySelector('#edit_instrument_type').value = instrumentType;
    });

    // Confirmar borrado
    window.confirmarBorrado = function(tagNumber) {
        if (confirm('¿Estás seguro de que deseas borrar este equipo?')) {
            window.location.href = `borrar.php?tag_number=${tagNumber}`;
        }
    };
   // Modal de detalles
   const detallesModal = document.getElementById('detallesModal');
   detallesModal.addEventListener('show.bs.modal', function(event) {
       const button = event.relatedTarget;
       const tagNumber = button.getAttribute('data-id');
   
       // Realizar una solicitud AJAX para obtener los detalles del equipo
       fetch(`obtener_detalles.php?tag_number=${tagNumber}`)
           .then(response => response.json())
           .then(data => {
               // Llenar los campos del modal con los datos obtenidos
               document.getElementById('detalle_tag_number').textContent = data.Tag_Number;
               document.getElementById('detalle_unit').textContent = data.Unit;
               document.getElementById('detalle_instrument_type').textContent = data.Instrument_Type_Desc;
               document.getElementById('detalle_area').textContent = data.Area_s;
               document.getElementById('detalle_cantidad').textContent = data.Cantidad;
               document.getElementById('detalle_f_location').textContent = data.F_location;
               document.getElementById('detalle_service_upper').textContent = data.Service_Upper;
               document.getElementById('detalle_p_id_no').textContent = data.P_ID_No;
               document.getElementById('detalle_sys_tag').textContent = data.SYS_TAG;
               document.getElementById('detalle_line_size').textContent = data.Line_size;
               document.getElementById('detalle_rating').textContent = data.Rating;
               document.getElementById('detalle_facing').textContent = data.Facing;
               document.getElementById('detalle_lineclass').textContent = data.Lineclass;
               document.getElementById('detalle_system_in').textContent = data.SYSTEM_IN;
               document.getElementById('detalle_system_out').textContent = data.SYSTEM_OUT;
               document.getElementById('detalle_io_type_out').textContent = data.IO_TYPE_OUT;
               document.getElementById('detalle_signal_cond').textContent = data.SIGNAL_COND;
               document.getElementById('detalle_crtl_act').textContent = data.CRTL_ACT;
               document.getElementById('detalle_state_0').textContent = data.STATE_0;
               document.getElementById('detalle_state_1').textContent = data.STATE_1;
               document.getElementById('detalle_po_number').textContent = data.Po_Number;
               document.getElementById('detalle_junction_box_no').textContent = data.Junction_box_no;
               document.getElementById('detalle_id_planta').textContent = data.id_planta;
           })
           .catch(error => console.error('Error al obtener los detalles:', error));
   });
   });