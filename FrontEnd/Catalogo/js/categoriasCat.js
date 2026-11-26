function initCategoriasCatalogo() {

    const apiUrl = `${window.API_BASE}/meta.php?entity=categoria`;
    let _todasLasCategorias = [];
    let _categoriasRenderizadas = [];

    // Estado compartido: ahora admite varias categorías seleccionadas
    if (!window._catalogoFiltros) window._catalogoFiltros = { texto: '', categorias: [] };
    if (!Array.isArray(window._catalogoFiltros.categorias)) window._catalogoFiltros.categorias = [];

    const btnFiltro  = document.getElementById('btn-filtro-categorias');
    const menuFiltro = document.getElementById('menu-filtro-categorias');

    function urlImagen(categoria) {
        const relativa = (categoria.imagen && categoria.imagen.trim() !== '')
            ? categoria.imagen
            : 'uploads/default/default-image.jpg';
        return `${window.BASE_URL}/${relativa}`;
    }

    function seleccionadas() {
        return window._catalogoFiltros.categorias || [];
    }

    function normalizar(nombre) {
        return String(nombre || '').trim().toLowerCase();
    }

    function escapar(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function sincronizarIndicadores() {
        const total = seleccionadas().length;

        const contador = document.getElementById('filtro-categorias-count');
        if (contador) {
            contador.textContent = String(total);
            contador.hidden = total === 0;
        }

        const todas = document.querySelector('.opcion-categoria--todas');
        if (todas) todas.setAttribute('aria-pressed', total === 0 ? 'true' : 'false');

        const limpiar = document.getElementById('btn-limpiar-filtro-categorias');
        if (limpiar) limpiar.disabled = total === 0;
    }

    // Actualiza solo los atributos: no reconstruye el DOM para no perder
    // el objetivo del scroll con la rueda del ratón al marcar categorías.
    function sincronizarOpciones() {
        const lista = document.getElementById('opciones-categorias');
        if (!lista) return;

        const activas = seleccionadas().map(normalizar);
        lista.querySelectorAll('.opcion-categoria').forEach(opcion => {
            const activa = activas.includes(normalizar(opcion.dataset.nombre || ''));
            opcion.setAttribute('aria-pressed', activa ? 'true' : 'false');
        });

        sincronizarIndicadores();
    }

    // Dibuja las opciones compactas del desplegable (imagen + nombre + check)
    function renderOpciones() {
        const lista = document.getElementById('opciones-categorias');
        if (!lista) return;

        const scrollTop = lista.scrollTop;

        if (_todasLasCategorias.length === 0) {
            lista.innerHTML = `<p class="filtro-categorias__vacio">No hay categorías disponibles</p>`;
            _categoriasRenderizadas = [];
            sincronizarIndicadores();
            return;
        }

        const activas = seleccionadas().map(normalizar);

        lista.innerHTML = _todasLasCategorias.map(categoria => {
            const nombre = categoria.nombre || '';
            const activa = activas.includes(normalizar(nombre));

            return `
                <button type="button" class="opcion-categoria" data-nombre="${escapar(nombre)}"
                        aria-pressed="${activa ? 'true' : 'false'}">
                    <img class="opcion-categoria__img" src="${escapar(urlImagen(categoria))}" alt="">
                    <span class="opcion-categoria__texto">${escapar(nombre)}</span>
                    <i class='bx bx-check opcion-categoria__check'></i>
                </button>
            `;
        }).join('');

        _categoriasRenderizadas = _todasLasCategorias.map(c => normalizar(c.nombre || ''));
        lista.scrollTop = scrollTop;
        sincronizarIndicadores();
    }

    // En el polling solo redibuja si cambió el conjunto de categorías.
    function actualizarOpciones() {
        const lista = document.getElementById('opciones-categorias');
        const actuales = _todasLasCategorias.map(c => normalizar(c.nombre || ''));
        const iguales = _categoriasRenderizadas.length === actuales.length &&
            actuales.every((nombre, i) => nombre === _categoriasRenderizadas[i]);

        if (!lista || !lista.querySelector('.opcion-categoria') || !iguales) {
            renderOpciones();
            return;
        }

        sincronizarOpciones();
    }

    function abrirMenu(abierto) {
        if (!menuFiltro || !btnFiltro) return;
        menuFiltro.hidden = !abierto;
        btnFiltro.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    function limpiarCategorias() {
        const filtros = window._catalogoFiltros;
        if (!filtros || !seleccionadas().length) return;

        filtros.categorias = [];
        sincronizarOpciones();
        if (typeof window.renderCatalogoConFiltros === 'function') {
            window.renderCatalogoConFiltros(true);
        }
    }

    function alternarCategoria(nombre) {
        const filtros = window._catalogoFiltros;
        filtros.categorias = filtros.categorias || [];

        if (!nombre) {
            limpiarCategorias();
            return;
        }

        const buscada = normalizar(nombre);
        const indice = filtros.categorias.findIndex(c => normalizar(c) === buscada);
        if (indice >= 0) filtros.categorias.splice(indice, 1);
        else filtros.categorias.push(nombre);

        sincronizarOpciones();
        if (typeof window.renderCatalogoConFiltros === 'function') {
            window.renderCatalogoConFiltros(true);
        }
    }

    if (btnFiltro && menuFiltro && !btnFiltro.dataset.bindFiltroCategorias) {
        btnFiltro.dataset.bindFiltroCategorias = '1';

        btnFiltro.addEventListener('click', (e) => {
            e.stopPropagation();
            abrirMenu(menuFiltro.hidden);
        });

        menuFiltro.addEventListener('click', (e) => {
            if (e.target.closest('#btn-limpiar-filtro-categorias')) {
                limpiarCategorias();
                return;
            }

            const opcion = e.target.closest('.opcion-categoria');
            if (!opcion || !menuFiltro.contains(opcion)) return;
            alternarCategoria(opcion.dataset.nombre || '');
        });

        document.addEventListener('click', (e) => {
            if (menuFiltro.hidden) return;
            if (menuFiltro.contains(e.target) || btnFiltro.contains(e.target)) return;
            abrirMenu(false);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !menuFiltro.hidden) {
                abrirMenu(false);
                btnFiltro.focus();
            }
        });
    }

    async function fetchCategorias() {
        try {
            const response = await fetch(apiUrl, { cache: 'no-store' });
            const data = await response.json();
            _todasLasCategorias = Array.isArray(data) ? data : (data.registros || []);
        } catch (error) {
            console.error('Error al obtener las categorías:', error);
            _todasLasCategorias = [];
        }
        actualizarOpciones();
    }

    window.renderFiltroCategorias = actualizarOpciones;

    fetchCategorias();
    window.registerPoll(fetchCategorias, 5000);
}
