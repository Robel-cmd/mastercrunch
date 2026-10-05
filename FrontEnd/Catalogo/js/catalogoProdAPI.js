function initCatalogoAPI() {

    // Rutas dinámicas
    const apiUrl  = `${window.API_BASE}/meta.php?entity=producto`;
    const apiUrlC = `${window.API_BASE}/meta.php?entity=combo`;

    let _paginaProdActual = 1;
    let _productosMostrados = [];
    const PRODUCTOS_POR_PAGINA = 10;

    //tarjetas de productos en el catálogo
    function renderizarCatalogo(registros) {
        const catalogoContainer = document.getElementById('catalogo-container');
        if (!catalogoContainer) return;

        catalogoContainer.innerHTML = '';

        // Estado vacío
        if (!registros || registros.length === 0) {
            catalogoContainer.innerHTML = `
                <div class="no-results" style="text-align:center; width:100%; padding:20px;">
                    No se encontraron productos
                </div>
            `;
            renderPaginacion(document.getElementById('paginacion-catalogo-productos'), 1, 0, PRODUCTOS_POR_PAGINA, () => {});
            return;
        }

        _productosMostrados = registros;

        const totalPaginas = Math.max(1, Math.ceil(registros.length / PRODUCTOS_POR_PAGINA));
        if (_paginaProdActual > totalPaginas) _paginaProdActual = totalPaginas;

        const inicio   = (_paginaProdActual - 1) * PRODUCTOS_POR_PAGINA;
        const visibles = registros.slice(inicio, inicio + PRODUCTOS_POR_PAGINA);

        let html = '';

        visibles.forEach(item => {
            const urlRelativa = (item.url_imagen && item.url_imagen.trim() !== '')
                ? item.url_imagen
                : 'uploads/default/default-image.jpg';

            const urlImagen = `${window.BASE_URL}/${urlRelativa}`;

            const categoriaNombre = item.categoria_nombre || 'Sin categoría';

            html += `
                <article class="producto-card${item.esCombo ? ' combo-card' : ''}${(!item.esCombo && String(item.es_extra) === '1') ? ' extra-card' : ''}" data-nombre="${item.nombre}" data-id="${item.id}">
                    <img src="${urlImagen}" alt="${item.nombre}">
                    <div class="producto-info">
                        <h3>${item.nombre}</h3>
                        <p>${categoriaNombre}</p>
                        <div class="productos-footer">
                            <span class="precio-producto-catalogo">${item.precio} Lps</span>
                        </div>
                    </div>
                </article>
            `;
        });

        catalogoContainer.innerHTML = html;

        renderPaginacion(
            document.getElementById('paginacion-catalogo-productos'),
            _paginaProdActual,
            _productosMostrados.length,
            PRODUCTOS_POR_PAGINA,
            (p) => { _paginaProdActual = p; renderizarCatalogo(_productosMostrados); }
        );
    }

    // Normaliza un combo al mismo formato que un producto del catálogo
    function normalizarCombo(item) {
        return {
            id: 'c' + (item.id_combo ?? item.id),
            nombre: item.nombre,
            categoria_nombre: 'Combo',
            precio: item.precio_total,
            url_imagen: item.url_imagen_combo,
            detalles: item.detalles || [],
            esCombo: true,
        };
    }

    // Normaliza un producto
    function normalizarProducto(item) {
        return Object.assign({ esCombo: false, es_extra: String(item.es_extra ?? '0') }, item, {
            id: item.id_producto ?? item.id,
        });
    }

    let _filtroActual = 'todos';
    let _todosLosItems = [];

    // Estado compartido de filtros (lo usa también el módulo de categorías)
    if (!window._catalogoFiltros) window._catalogoFiltros = { texto: '', categoria: null };

    // Devuelve los items según el chip activo, la categoría seleccionada y el texto
    function itemsFiltrados() {
        let items = _todosLosItems;
        const filtro = window._catalogoFiltroActual || 'todos';

        if (filtro === 'combos') {
            items = items.filter(i => i.esCombo);
        } else if (filtro === 'extras') {
            items = items.filter(i => !i.esCombo && String(i.es_extra) === '1');
        } else if (filtro === 'productos') {
            items = items.filter(i => !i.esCombo && String(i.es_extra) !== '1');
        }

        const cat = window._catalogoFiltros.categoria;
        if (cat) {
            const catNorm = String(cat).trim().toLowerCase();
            items = items.filter(i => String(i.categoria_nombre || '').trim().toLowerCase() === catNorm);
        }

        const texto = (window._catalogoFiltros.texto || '').trim().toLowerCase();
        if (texto) {
            items = items.filter(i =>
                String(i.nombre || '').toLowerCase().includes(texto) ||
                String(i.categoria_nombre || '').toLowerCase().includes(texto)
            );
        }

        return items;
    }

    // re-renderiza respetando los filtros actuales;
    // resetPagina=true cuando el usuario cambia un filtro (vuelve a la p.1),
    // resetPagina=false cuando solo se refresca el polling (mantiene la p. actual)
    function aplicarFiltro(resetPagina = true) {
        if (resetPagina) _paginaProdActual = 1;
        renderizarCatalogo(itemsFiltrados());
    }

    // Cargar catálogo: productos + combos (NO reinicia la pág. al refrescar el poll)
    function fetchCatalogo() {
        const pProductos = fetch(apiUrl)
            .then(r => r.json())
            .then(d => (Array.isArray(d) ? d : (d.registros || [])).map(normalizarProducto))
            .catch(() => []);

        const pCombos = fetch(apiUrlC)
            .then(r => r.json())
            .then(d => (Array.isArray(d) ? d : (d.registros || [])).map(normalizarCombo))
            .catch(() => []);

        Promise.all([pProductos, pCombos]).then(([productos, combos]) => {
            _todosLosItems = productos.concat(combos);
            window._catalogoItems = _todosLosItems;  // para el carrito/modal
            aplicarFiltro(false);
        });
    }

    // Listeners globales: se adjuntan UNA sola vez (evita doble registro en
    // reinicios como AJAX del router, que llamaría 2 veces a initCatalogoAPI)
    if (!document._catalogoHandlers) {
        document._catalogoHandlers = true;

        // Filtros Todos / Productos / Combos
        document.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip-filtro');
            if (!chip) return;
            document.querySelectorAll('.chip-filtro').forEach(c =>
                c.classList.toggle('active', c === chip));
            window._catalogoFiltroActual = chip.dataset.filtro;
            if (window.renderCatalogoConFiltros) window.renderCatalogoConFiltros(true);
        });

        // Selección de categoría (click en tarjeta del carrusel)
        document.addEventListener('click', (e) => {
            const carta = e.target.closest('.categoria-card-catalogo');
            if (!carta) return;
            const nombre = carta.dataset.nombre;
            const misma = window._catalogoFiltros && window._catalogoFiltros.categoria === nombre;
            window._catalogoFiltros = window._catalogoFiltros || { texto: '', categoria: null };
            window._catalogoFiltros.categoria = misma ? null : nombre;
            document.querySelectorAll('.categoria-card-catalogo').forEach(c =>
                c.classList.toggle('selected', !misma && c === carta));
            if (window.renderCatalogoConFiltros) window.renderCatalogoConFiltros(true);
        });
    }

    // Buscador global
    const buscador = document.getElementById('buscador-global');
    if (buscador && !buscador.dataset.bindBuscador) {
        buscador.dataset.bindBuscador = '1';
        buscador.addEventListener('input', () => {
            window._catalogoFiltros = window._catalogoFiltros || { texto: '', categoria: null };
            window._catalogoFiltros.texto = buscador.value;
            if (window.renderCatalogoConFiltros) window.renderCatalogoConFiltros(true);
            window.dispatchEvent(new CustomEvent('catalogo:texto', { detail: buscador.value }));
        });
    }

    // Exponemos el método de renderizado con los cierres de esta inicialización
    // Sincronizar el aspecto de los chips con el filtro guardado
    // (si se recarga el fragment del catálogo por el router, el HTML
    //  vuelve a marcar "Todos" pero el filtro interno se mantuvo)
    document.querySelectorAll('.chip-filtro').forEach(c =>
        c.classList.toggle('active', c.dataset.filtro === (window._catalogoFiltroActual || 'todos')));

    window._catalogoFiltroActual = window._catalogoFiltroActual || 'todos';
    window.renderCatalogoConFiltros = function (resetPagina) {
        if (resetPagina) _paginaProdActual = 1;
        renderizarCatalogo(itemsFiltrados());
    };

    window.registerPoll(fetchCatalogo, 5000);
    fetchCatalogo();
}