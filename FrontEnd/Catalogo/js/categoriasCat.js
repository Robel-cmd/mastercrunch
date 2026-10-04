function initCategoriasCatalogo() {

    // Ruta dinámica
    const apiUrl = `${window.API_BASE}/meta.php?entity=categoria`;

    let _paginaCatActual = 1;
    let _categoriasMostradas = [];
    const CATEGORIAS_POR_PAGINA = 6; // máximo
    const ANCHO_TARJETA_CAT = 280;   // 260px tarjeta + gap

    //tarjetas de categorías
    function renderizarCategorias(registros) {
        const categoriasContainer = document.getElementById('categorias-container');
        if (!categoriasContainer) return;

        categoriasContainer.innerHTML = '';

        // Estado vacío
        if (!registros || registros.length === 0) {
            categoriasContainer.innerHTML = `
                <div class="no-results" style="text-align:center; width:100%; padding:20px;">
                    No se encontraron categorías
                </div>
            `;
            renderPaginacion(document.getElementById('paginacion-catalogo-categorias'), 1, 0, CATEGORIAS_POR_PAGINA, () => {});
            return;
        }

        _categoriasMostradas = registros;
        const porPagina = calcularPorPagina(categoriasContainer, CATEGORIAS_POR_PAGINA, ANCHO_TARJETA_CAT);

        const totalPaginas = Math.max(1, Math.ceil(registros.length / porPagina));
        if (_paginaCatActual > totalPaginas) _paginaCatActual = totalPaginas;

        const inicio   = (_paginaCatActual - 1) * porPagina;
        const visibles = registros.slice(inicio, inicio + porPagina);

        let html = '';

        visibles.forEach(item => {
            const urlRelativa = (item.imagen && item.imagen.trim() !== '')
                ? item.imagen
                : 'uploads/default/default-image.jpg';

            const urlImagen = `${window.BASE_URL}/${urlRelativa}`;

            const descripcion = item.descripcion || '';

            html += `
                <article class="categoria-card-catalogo" data-nombre="${item.nombre}" data-id="${item.id}">
                    <img src="${urlImagen}" alt="Imagen de ${item.nombre}">
                    <h3>${item.nombre}</h3>
                    <p>${descripcion}</p>
                </article>
            `;
        });

        categoriasContainer.innerHTML = html;

        renderPaginacion(
            document.getElementById('paginacion-catalogo-categorias'),
            _paginaCatActual,
            _categoriasMostradas.length,
            porPagina,
            (p) => { _paginaCatActual = p; renderizarCategorias(_categoriasMostradas); }
        );
    }

    // Al cambiar el tamaño de pantalla, recalcular cuántos ítems caben
    window.addEventListener('resize', () => {
        if (_categoriasMostradas.length > 0) renderizarCategorias(_categoriasMostradas);
    });

    // Cargar categorías
    function fetchCategorias() {
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                const registros = Array.isArray(data) ? data : (data.registros || []);
                renderizarCategorias(registros);
            })
            .catch(error => {
                console.error('Error al obtener las categorías:', error);
                renderizarCategorias([]);
            });
    }

    fetchCategorias();
    window.registerPoll(fetchCategorias, 5000);
}