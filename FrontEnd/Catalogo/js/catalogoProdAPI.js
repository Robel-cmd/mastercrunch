function initCatalogoAPI() {

    // Rutas dinámicas
    const apiUrl  = `${window.API_BASE}/meta.php?entity=producto`;
    const apiUrlC = `${window.API_BASE}/meta.php?entity=combo`;

    let _paginaProdActual = 1;
    let _productosMostrados = [];
    const PRODUCTOS_POR_PAGINA = 10;

    //tarjetas de productos en el catálogo
    function renderizarCatalogo(registros, preservarScroll = false) {
        const catalogoContainer = document.getElementById('catalogo-container');
        if (!catalogoContainer) return;

        const scrollY = window.scrollY;

        // Estado vacío
        if (!registros || registros.length === 0) {
            catalogoContainer.innerHTML = `
                <div class="no-results" style="text-align:center; width:100%; padding:20px;">
                    No se encontraron productos
                </div>
            `;
            renderPaginacion(document.getElementById('paginacion-catalogo-productos'), 1, 0, PRODUCTOS_POR_PAGINA, () => {});
            if (preservarScroll) window.scrollTo(0, scrollY);
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
            const esCombo = Boolean(item.esCombo);
            const esExtra = !esCombo && String(item.es_extra) === '1';

            const claseTipo = esCombo
                ? ' combo-card tarjeta-catalogo--combo'
                : esExtra
                    ? ' extra-card tarjeta-catalogo--extra'
                    : ' tarjeta-catalogo--producto';

            const textoAccion = 'Ver detalle';
            const descripcion = item.descripcion ? String(item.descripcion).trim() : '';
            const descripcionHtml = descripcion
                ? `<p class="tarjeta-catalogo__descripcion">${descripcion}</p>`
                : '';

            html += `
                <article class="producto-card tarjeta-catalogo${claseTipo}" data-nombre="${item.nombre}" data-id="${item.id}">
                    <div class="tarjeta-catalogo__media">
                        <img src="${urlImagen}" alt="${item.nombre}">
                        <!-- Espacio reservado: el badge rojo se mostrará cuando exista el dato de descuento -->
                    </div>
                    <div class="tarjeta-catalogo__cuerpo">
                        <span class="tarjeta-catalogo__categoria">${categoriaNombre}</span>
                        <h3 class="tarjeta-catalogo__nombre">${item.nombre}</h3>
                        <div class="tarjeta-catalogo__precios">
                            <span class="precio-producto-catalogo">${item.precio} Lps</span>
                        </div>
                        ${descripcionHtml}
                        <button type="button" class="tarjeta-catalogo__accion" data-ver-detalle>${textoAccion}</button>
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

        if (preservarScroll) window.scrollTo(0, scrollY);
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
    if (!window._catalogoFiltros) window._catalogoFiltros = { texto: '', categorias: [] };
    if (!Array.isArray(window._catalogoFiltros.categorias)) window._catalogoFiltros.categorias = [];

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

        const cats = window._catalogoFiltros.categorias || [];
        if (cats.length > 0) {
            const catNorms = cats.map(c => String(c).trim().toLowerCase());
            items = items.filter(i => {
                const nombreCategoria = String(i.categoria_nombre || '').trim().toLowerCase();

                // Los combos no tienen categoría propia: siguen disponibles
                // mientras el chip esté en "Todos" o "Combos".
                if (i.esCombo && (filtro === 'todos' || filtro === 'combos')) return true;

                return catNorms.includes(nombreCategoria);
            });
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
        renderizarCatalogo(itemsFiltrados(), !resetPagina);
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

    }

    // Buscador global
    const buscador = document.getElementById('buscador-global');
    if (buscador && !buscador.dataset.bindBuscador) {
        buscador.dataset.bindBuscador = '1';
        buscador.addEventListener('input', () => {
            window._catalogoFiltros = window._catalogoFiltros || { texto: '', categorias: [] };
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