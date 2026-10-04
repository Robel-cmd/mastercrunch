function initTooglePanel() {
    const btn        = document.getElementById('pedidos-toggle-btn');
    const panel      = document.getElementById('pedidos-panel');
    const btnCerrar  = document.getElementById('btn-cerrar-panel');
    const contenedor = document.getElementById('pedidos-flotante');

    //si no existen los elementos, salimos
    if (!btn || !panel || !btnCerrar || !contenedor) return;

    //evitar inicializar dos veces el mismo panel
    if (contenedor.dataset.panelInitialized === 'true') return;
    contenedor.dataset.panelInitialized = 'true';

    // Abrir / cerrar con el botón flotante
    btn.addEventListener('click', () => {
        contenedor.classList.toggle('abierto');
    });

    // Cerrar con la X del header
    btnCerrar.addEventListener('click', () => {
        contenedor.classList.remove('abierto');
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && contenedor.classList.contains('abierto')) {
            contenedor.classList.remove('abierto');
        }
    });

    // Cambio de pestañas (En proceso / Completado)
    const tabs     = document.querySelectorAll('.tab-pedido');
    const contents = document.querySelectorAll('.pedidos-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;

            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            tab.classList.add('active');

            const contentActivo = document.querySelector(
                `.pedidos-tab-content[data-content="${target}"]`
            );
            if (contentActivo) contentActivo.classList.add('active');
        });
    });
}