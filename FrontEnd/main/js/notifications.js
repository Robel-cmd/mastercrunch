
function mostrarNotificacion(mensaje, tipoMensaje = 'success') {
    const notificacion = document.getElementById('notificaion-poput');
    if (!notificacion) return;

    const tipo = (tipoMensaje === 'error') ? 'error' : 'success';

    if (notificacion.matches(':popover-open')) {
        notificacion.hidePopover();
    }

    // Actualizar contenido y clase
    notificacion.textContent = mensaje;
    notificacion.classList.remove('success', 'error');
    notificacion.classList.add(tipo);

    // Mostrar 
    notificacion.showPopover();

    // Cancelar timeout anterior
    if (notificacion.timeoutId) {
        clearTimeout(notificacion.timeoutId);
    }

    // Auto-cerrar a los 3 segundos
    notificacion.timeoutId = setTimeout(() => {
        if (notificacion.matches(':popover-open')) {
            notificacion.hidePopover();
        }
        notificacion.timeoutId = null;
    }, 3000);
}

function initNotifications() {
    // No hay nada que inicializar
}