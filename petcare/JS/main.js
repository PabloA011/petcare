document.addEventListener('DOMContentLoaded', () => {
    
    const alertasContainer = document.getElementById('alertas-proximas');

    const alertas = [
        { tipo: 'turno', mensaje: 'Turno pendiente con **Max** (Perro Labrador) mañana a las 10:00.', nivel: 'warning' },
        { tipo: 'vacuna', mensaje: 'La vacuna Antirrábica de **Lola** vence en 3 días.', nivel: 'danger' },
        { tipo: 'tratamiento', mensaje: 'Revisar la continuidad del tratamiento de **Pipo** hoy.', nivel: 'info' }
    ];

    alertasContainer.innerHTML = ''; 

    if (alertas.length > 0) {
        alertas.forEach(alerta => {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${alerta.nivel} d-flex align-items-center mb-2`;
            alertDiv.setAttribute('role', 'alert');

            let icon = '';
            if (alerta.tipo === 'turno') icon = '<i class="bi bi-calendar-x-fill me-2"></i>';
            if (alerta.tipo === 'vacuna') icon = '<i class="bi bi-virus me-2"></i>';
            if (alerta.tipo === 'tratamiento') icon = '<i class="bi bi-journal-medical me-2"></i>';
            
            alertDiv.innerHTML = icon + alerta.mensaje;
            alertasContainer.appendChild(alertDiv);
        });
    } else {
        alertasContainer.innerHTML = `
            <div class="alert alert-success mb-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> ¡Sistema de alertas limpio! No hay recordatorios urgentes.
            </div>
        `;
    }

});