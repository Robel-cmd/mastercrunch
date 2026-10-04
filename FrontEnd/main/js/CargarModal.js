function cargarModales() {
    function configurarModal(selectorModal, selectorAbrir, selectorCerrar, selectorCancelar) {
        const modal       = document.querySelector(selectorModal);
        const btnAbrir    = document.querySelector(selectorAbrir);
        const btnCerrar   = document.querySelector(selectorCerrar);
        const btnCancelar = document.querySelector(selectorCancelar);

        // Sin modal en el DOM no hay nada que configurar
        if (!modal) return;

        // Abrir desde el botón principal
        if (btnAbrir) {
            btnAbrir.addEventListener('click', () => {
                if (!modal.open) modal.showModal();
            });
        }

        // Cerrar desde la X
        if (btnCerrar) {
            btnCerrar.addEventListener('click', () => {
                if (modal.open) modal.close();
            });
        }

        // Cerrar desde Cancelar
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                if (modal.open) modal.close();
            });
        }
    }

    // Modal: Agregar Producto
    configurarModal(
        '#modal-producto',
        '#agregar-producto',
        '#btn-close-product',
        '#btn-cancel-product'
    );

    // Modal: Agregar Categoría
    configurarModal(
        '#modal-categoria',
        '#agregar-categoria',
        '#btn-close-category',
        '#btn-cancel-category'
    );

    // Modal: Agregar Combo
    configurarModal(
        '#modal-combos',
        '#agregar-combos',
        '#btn-close-combos',
        '#btn-cancel-combos'
    );
}