// Carrito del Pedido (lista de productos agregados)
let _carrito = [];

function renderCarrito() {
    const lista = document.getElementById('lista-pedido');
    if (!lista) return;

    if (!_carrito || _carrito.length === 0) {
        lista.innerHTML = `
            <p class="no-results" style="text-align:center; padding:20px; width:100%; color:var(--color-text-secondary);">
                Agrega productos desde el catálogo
            </p>`;
        return;
    }

    let html = '';
    _carrito.forEach((item, idx) => {
        html += `
            <article class="pedido-item pedido-item--${item.tipo || 'producto'}">
                <div class="item-header">
                    <h4>${item.nombre} <span class="pedido-tipo pedido-tipo-${item.tipo || 'producto'}">${item.tipo === 'combo' ? 'Combo' : item.tipo === 'extra' ? 'Extra' : 'Producto'}</span></h4>
                    <button class="btn-eliminar" data-eliminar-item="${idx}" title="Quitar">🗑️</button>
                </div>
                <div class="item-precio-cantidad">
                    <span class="precio">${parseFloat(item.precio).toFixed(2)} Lps</span>
                    <div class="controles-cantidad">
                        <button data-cant-item="${idx}" data-delta="-1">-</button>
                        <span>${item.cantidad}</span>
                        <button data-cant-item="${idx}" data-delta="1">+</button>
                    </div>
                </div>
                <input type="text" placeholder="Nota adicional de producto" class="input-nota" data-nota-item="${idx}" value="${item.nota || ''}">
            </article>
        `;
    });

    lista.innerHTML = html;
}

function agregarAlCarrito(item, tipo = 'producto') {
    if (!_carrito) _carrito = [];

    // Si el mismo producto/tipo ya está, solo se suma la cantidad
    const existente = _carrito.find(c => c.nombre === item.nombre && c.tipo === tipo);
    if (existente) {
        existente.cantidad = Math.min(existente.cantidad + 1, 99);
    } else {
        _carrito.push({
            nombre: item.nombre,
            precio: item.precio,
            cantidad: 1,
            nota: '',
            tipo: tipo
        });
    }
    renderCarrito();
}

function abrirModalCombo(combo) {
    const modal = document.getElementById('modal-combo-catalogo');
    if (!modal) return;

    document.getElementById('combo-modal-titulo').textContent = combo.nombre;
    document.getElementById('combo-modal-descripcion').textContent = combo.categoria_nombre || '';

    const lista = document.getElementById('combo-modal-lista');
    lista.innerHTML = '';

    if (combo.detalles && Array.isArray(combo.detalles)) {
        combo.detalles.forEach(det => {
            const li = document.createElement('li');
            li.innerHTML = `<span class="combo-detalle-item">x${det.cantidad} de ${det.producto_nombre}</span> — <span class="combo-detalle-precio">Lps.${det.precio_individual}</span>`;
            lista.appendChild(li);
        });
    }

    document.getElementById('combo-modal-precio').textContent = `Total: ${parseFloat(combo.precio).toFixed(2)} Lps`;

    // Asignar combo al modal para el botón Agregar
    modal.dataset.comboSelect = JSON.stringify(combo);

    if (!modal.open) modal.showModal();
}

function initCarrito() {
    const lista = document.getElementById('lista-pedido');
    if (!lista) return;

    // Clics en el carrito: eliminar, cantidad
    lista.addEventListener('click', (e) => {
        const elim = e.target.closest('[data-eliminar-item]');
        if (elim) {
            const idx = parseInt(elim.dataset.eliminarItem, 10);
            _carrito.splice(idx, 1);
            renderCarrito();
            return;
        }

        const cant = e.target.closest('[data-cant-item]');
        if (cant) {
            const idx = parseInt(cant.dataset.cantItem, 10);
            const delta = parseInt(cant.dataset.delta, 10);
            if (_carrito[idx]) {
                _carrito[idx].cantidad = Math.max(1, Math.min(99, _carrito[idx].cantidad + delta));
                renderCarrito();
            }
        }
    });

    // Nota por ítem
    lista.addEventListener('input', (e) => {
        const nota = e.target.closest('[data-nota-item]');
        if (nota) {
            const idx = parseInt(nota.dataset.notaItem, 10);
            if (_carrito[idx]) _carrito[idx].nota = nota.value;
        }
    });

    // Botón "Borrar lista" → confirmación; borrar sólo al confirmar
    const btnBorrar = document.querySelector('.btn-borrar');
    if (btnBorrar && !btnBorrar.dataset.bind) {
        btnBorrar.dataset.bind = '1';
        const modalBorrar = document.getElementById('warning-modal-borrar-lista');
        btnBorrar.addEventListener('click', () => {
            if (modalBorrar && !modalBorrar.open) modalBorrar.showModal();
        });
    }

    const btnCancelarBorrar = document.getElementById('cancelar-borrar-lista');
    const btnConfirmarBorrar = document.getElementById('confirmar-borrar-lista');
    const modalBorrar = document.getElementById('warning-modal-borrar-lista');
    if (btnCancelarBorrar && modalBorrar) {
        btnCancelarBorrar.addEventListener('click', () => { if (modalBorrar.open) modalBorrar.close(); });
    }
    if (btnConfirmarBorrar && modalBorrar) {
        btnConfirmarBorrar.addEventListener('click', () => {
            _carrito = [];
            renderCarrito();
            if (modalBorrar.open) modalBorrar.close();
        });
    }

    // Botón "Facturar" → mensaje visual (sin funcionalidad por ahora)
    const btnFacturar = document.querySelector('.btn-facturar');
    const modalFacturar = document.getElementById('modal-confirmacion-facturar');
    if (btnFacturar && modalFacturar && !btnFacturar.dataset.bind) {
        btnFacturar.dataset.bind = '1';
        btnFacturar.addEventListener('click', () => {
            if (!modalFacturar.open) modalFacturar.showModal();
        });
    }

    const btnAceptarFacturar = document.getElementById('aceptar-modal-facturar');
    if (btnAceptarFacturar && modalFacturar) {
        btnAceptarFacturar.addEventListener('click', () => {
            if (modalFacturar.open) modalFacturar.close();
        });
    }

    renderCarrito();
}

function initCatalogoItems() {
    // Clicks en el catálogo: producto -> carrito, combo -> modal
    const grid = document.getElementById('catalogo-container');
    if (!grid || grid.dataset.bindItems) {
        if (grid) grid.dataset.bindItems = '1';
        return;
    }
    grid.dataset.bindItems = '1';

    grid.addEventListener('click', (e) => {
        const card = e.target.closest('.producto-card');
        if (!card) return;

        const id = card.dataset.id;
        const items = window._catalogoItems || [];
        const item = items.find(i => String(i.id) === String(id));
        if (!item) return;

        if (card.classList.contains('combo-card')) {
            abrirModalCombo(item);
        } else if (card.classList.contains('extra-card')) {
            agregarAlCarrito(item, 'extra');
        } else {
            agregarAlCarrito(item, 'producto');
        }
    });
}

function initModalComboCatalogo() {
    const modal = document.getElementById('modal-combo-catalogo');
    if (!modal) return;

    const cerrar = () => { if (modal.open) modal.close(); };

    const btnCerrar = document.getElementById('btn-close-combo-catalogo');
    const btnCancelar = document.getElementById('btn-cancel-combo-catalogo');
    if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrar);

    // Cerrar al hacer click fuera
    modal.addEventListener('click', (e) => {
        const r = modal.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) cerrar();
    });

    const btnAgregar = document.getElementById('btn-agregar-combo-catalogo');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            let combo = null;
            try {
                combo = JSON.parse(modal.dataset.comboSelect || '{}');
            } catch (_) { combo = null; }
            if (combo && combo.nombre) agregarAlCarrito(combo, 'combo');
            cerrar();
        });
    }
}
