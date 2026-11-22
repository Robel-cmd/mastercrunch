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
                    <button class="btn-eliminar btn-ui btn-ui--secondary btn-ui--icon" data-eliminar-item="${idx}" title="Quitar">🗑️</button>
                </div>
                <div class="item-precio-cantidad">
                    <span class="precio">${parseFloat(item.precio).toFixed(2)} Lps</span>
                    <div class="controles-cantidad">
                        <button data-cant-item="${idx}" data-delta="-1">-</button>
                        <input type="number" inputmode="numeric" min="1" max="99"
                               class="input-cantidad" data-cant-input="${idx}"
                               value="${item.cantidad}" title="Cantidad (editable)">
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
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(`Cantidad de "${item.nombre}" actualizada a ${existente.cantidad}`, 'success');
        }
    } else {
        _carrito.push({
            nombre: item.nombre,
            precio: item.precio,
            cantidad: 1,
            nota: '',
            tipo: tipo
        });
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(`Se agregó "${item.nombre}" a la lista`, 'success');
        }
    }
    renderCarrito();
}

function abrirDetalleCatalogo(item, tipo) {
    const modal = document.getElementById('modal-detalle-catalogo');
    if (!modal) return;

    const setText = (id, valor) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    };

    const urlRelativa = (item.url_imagen && item.url_imagen.trim() !== '')
        ? item.url_imagen
        : 'uploads/default/default-image.jpg';

    const imagen = document.getElementById('detalle-modal-imagen');
    if (imagen) {
        imagen.src = `${window.BASE_URL}/${urlRelativa}`;
        imagen.alt = item.nombre || '';
    }

    const precio = parseFloat(item.precio);

    setText('detalle-modal-nombre', item.nombre || '—');
    setText('detalle-modal-tipo', tipo === 'combo' ? 'Combo' : tipo === 'extra' ? 'Extra' : 'Producto');
    setText('detalle-modal-categoria', item.categoria_nombre || 'Sin categoría');
    setText('detalle-modal-precio', Number.isFinite(precio) ? `${precio.toFixed(2)} Lps` : '—');

    const descripcion = item.descripcion ? String(item.descripcion).trim() : '';
    setText('detalle-modal-descripcion', descripcion || 'Sin descripción');

    const bloqueCombo = document.getElementById('detalle-modal-combo');
    const listaCombo = document.getElementById('detalle-modal-lista');

    if (bloqueCombo && listaCombo) {
        listaCombo.innerHTML = '';

        if (tipo === 'combo' && Array.isArray(item.detalles)) {
            item.detalles.forEach(det => {
                const li = document.createElement('li');
                const precioDetalle = parseFloat(det.precio_individual);
                const precioTexto = Number.isFinite(precioDetalle)
                    ? `${precioDetalle.toFixed(2)} Lps`
                    : '—';
                li.textContent = `x${det.cantidad} de ${det.producto_nombre} — ${precioTexto}`;
                listaCombo.appendChild(li);
            });
            bloqueCombo.hidden = false;
        } else {
            bloqueCombo.hidden = true;
        }
    }

    modal.dataset.itemDetalle = JSON.stringify(item);
    modal.dataset.tipoDetalle = tipo;

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

    // Cantidad escrita a mano: se aplica al confirmar (Enter o salir del campo)
    lista.addEventListener('change', (e) => {
        const input = e.target.closest('[data-cant-input]');
        if (!input) return;
        const idx = parseInt(input.dataset.cantInput, 10);
        const item = _carrito[idx];
        if (!item) return;
        let v = parseInt(input.value, 10);
        if (isNaN(v)) v = item.cantidad;
        item.cantidad = Math.max(1, Math.min(99, v));
        renderCarrito();
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
    // Clicks en el catálogo: botón abre detalle; el resto de la tarjeta agrega
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

        const tipo = card.classList.contains('combo-card')
            ? 'combo'
            : card.classList.contains('extra-card')
                ? 'extra'
                : 'producto';

        if (e.target.closest('[data-ver-detalle]')) {
            abrirDetalleCatalogo(item, tipo);
        } else {
            agregarAlCarrito(item, tipo);
        }
    });
}

function initDetalleCatalogo() {
    const modal = document.getElementById('modal-detalle-catalogo');
    if (!modal || modal.dataset.bindDetalle) return;
    modal.dataset.bindDetalle = '1';

    const cerrar = () => { if (modal.open) modal.close(); };

    const btnCerrar = document.getElementById('btn-close-detalle-catalogo');
    const btnCerrarFooter = document.getElementById('btn-cerrar-detalle-catalogo');
    const btnAgregar = document.getElementById('btn-agregar-detalle-catalogo');

    if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
    if (btnCerrarFooter) btnCerrarFooter.addEventListener('click', cerrar);

    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            let item = null;
            try {
                item = JSON.parse(modal.dataset.itemDetalle || '{}');
            } catch (_) {
                item = null;
            }

            const tipo = modal.dataset.tipoDetalle || 'producto';
            if (item && item.nombre) agregarAlCarrito(item, tipo);
            cerrar();
        });
    }

    // Cerrar al hacer click fuera
    modal.addEventListener('click', (e) => {
        const r = modal.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) cerrar();
    });
}
