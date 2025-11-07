document.addEventListener('DOMContentLoaded', function() {
    const editarMascotaModal = document.getElementById('editarMascotaModal');
    
    if (editarMascotaModal) {
        editarMascotaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const idMascota = button.getAttribute('data-id');

            fetch(`mascotas.php?accion=obtener_mascota&id=${idMascota}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {

                    document.getElementById('editar-id-mascota').value = idMascota; 

                    document.getElementById('editar-nombre').value = data.nombre || '';
                    document.getElementById('editar-especie').value = data.especie || '';
                    document.getElementById('editar-raza').value = data.raza || '';
                    document.getElementById('editar-peso').value = data.peso || '';
                    document.getElementById('editar-observaciones').value = data.observaciones || '';

                    if (data.fecha_nacimiento && data.fecha_nacimiento !== '0000-00-00') {
                        document.getElementById('editar-fecha_nacimiento').value = data.fecha_nacimiento;
                    } else {
                        document.getElementById('editar-fecha_nacimiento').value = '';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar datos de la mascota:', error);
                    alert('No se pudieron cargar los datos de la mascota. Revisa la consola para más detalles.');
                });
        });
    }
});