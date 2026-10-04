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
                <article class="producto-card" data-nombre="${item.nombre}" data-id="${item.id}">
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

    // Cargar catálogo
    function fetchCategorias() {
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                // Carga tolerante: acepta array directo o {registros: [...]}
                const registros = Array.isArray(data) ? data : (data.registros || []);
                renderizarCatalogo(registros);
            })
            .catch(error => {
                console.error('Error al obtener el catálogo:', error);
                renderizarCatalogo([]);
            });
    }
    window.registerPoll(fetchCategorias, 5000);
    fetchCategorias();
}