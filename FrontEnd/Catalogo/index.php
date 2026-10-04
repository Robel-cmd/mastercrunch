<header class="header-body">
    <h1>Pedidos</h1>
    <h4>Realice ventas, pedidos.</h4>
</header>

<!--BARRA DE BÚSQUEDA GLOBAL-->
<section class="container-secondary toolbar-catalogo">
    <div class="header-card-container" style="border-bottom: none; padding-bottom: 0; margin-bottom: 0;">
        <div class="header-left">
            <div class="icon-image">
                <i class='bx bx-search-alt-2'></i>
                <span class="titulo">Búsqueda global</span>
            </div>
            <div class="search-general">
                <i class='bx bx-search-alt-2'></i>
                <input type="search" placeholder="Buscar..." id="buscador-global">
            </div>
        </div>
        <div class="button-general" id="btn-filtros">
            <a>
                <i class='bx bx-filter-alt'></i>
                <span>Filtros</span>
            </a>
        </div>
    </div>
</section>


<!--SECCIÓN DE CATEGORÍAS-->
<section class="container-secondary">
    <div class="header-card-container">
        <div class="icon-image">
            <i class='bx bx-category-alt'></i>
            <span class="titulo">Listado de categorías</span>
        </div>
    </div>

    <div class="carrusel-categorias" id="categorias-container">
        <!-- Aquí se generarán dinámicamente las categorías -->
    </div>

    <div class="paginacion" id="paginacion-catalogo-categorias"></div>
</section>



<!--SECCIÓN CATÁLOGO + RESUMEN-->
<div class="seccion-media-grid-catalogo">

    <!-- Columna izquierda: Catálogo -->
    <section class="container-secondary catalogo-productos">
        <div class="header-card-container">
            <div class="icon-image">
                <i class='bx bx-package'></i>
                <span class="titulo">Catálogo de productos</span>
            </div>
        </div>

        <div class="grid-productos" id="catalogo-container">
            <!-- Los productos se cargarán dinámicamente aquí -->
        </div>

        <div class="paginacion" id="paginacion-catalogo-productos"></div>
    </section>

    <!-- Columna derecha: Resumen del pedido -->
    <aside class="resumen-pedidos-agregados">
        <header>
            <i class='bx bx-cart-alt'></i>
            <h2>Lista de productos agregados</h2>
        </header>

        <div class="lista-pedido" id="lista-pedido">
            <article class="pedido-item">
                <div class="item-header">
                    <h4>Nombre producto</h4>
                    <button class="btn-eliminar">🗑️</button>
                </div>
                <div class="item-precio-cantidad">
                    <span class="precio">200.00 Lps</span>
                    <div class="controles-cantidad">
                        <button>-</button>
                        <span>1</span>
                        <button>+</button>
                    </div>
                </div>
                <input type="text" placeholder="Nota adicional de producto" class="input-nota">
            </article>
            <!-- ... más items ... -->
        </div>

        <footer class="acciones-pedido">
            <button class="btn-borrar">Borrar lista</button>
            <button class="btn-facturar">Facturar pedido</button>
        </footer>
    </aside>
</div>


<!--PANEL FLOTANTE DE PEDIDOS-->
<div class="pedidos-flotante" id="pedidos-flotante">

    <!-- Panel expandible -->
    <div class="pedidos-panel" id="pedidos-panel">

        <header class="pedidos-panel-header">
            <div class="icon-image">
                <i class='bx bx-receipt'></i>
                <span class="titulo">Pedidos</span>
            </div>
            <button class="btn-cerrar-panel" id="btn-cerrar-panel" aria-label="Cerrar">
                <i class='bx bx-x'></i>
            </button>
        </header>

        <!-- Tabs -->
        <div class="pedidos-tabs">
            <button class="tab-pedido active" data-tab="en-proceso">
                <span class="tab-dot dot-proceso"></span>
                En proceso
                <span class="tab-count">1</span>
            </button>
            <button class="tab-pedido" data-tab="completado">
                <span class="tab-dot dot-completado"></span>
                Completado
                <span class="tab-count">1</span>
            </button>
        </div>

        <!-- Lista de pedidos -->
        <div class="pedidos-lista" id="pedidos-lista">

            <!-- Pestaña: EN PROCESO -->
            <div class="pedidos-tab-content active" data-content="en-proceso">

                <article class="pedido-flotante-item">
                    <div class="pedido-flotante-top">
                        <span class="pedido-id">#001</span>
                        <span class="pedido-estado en-proceso">En proceso</span>
                    </div>
                    <p class="pedido-resumen">Pollo frito en piezas x2, Combo familiar x1</p>
                    <div class="pedido-flotante-bottom">
                        <span class="pedido-hora"><i class='bx bx-time'></i> 12:45 PM</span>
                        <span class="pedido-total">L. 650.00</span>
                    </div>
                </article>

            </div>

            <!-- Pestaña: COMPLETADO -->
            <div class="pedidos-tab-content" data-content="completado">

                <article class="pedido-flotante-item completado">
                    <div class="pedido-flotante-top">
                        <span class="pedido-id">#A-45</span>
                        <span class="pedido-estado completado">Completado</span>
                    </div>
                    <p class="pedido-resumen">Pollo frito x1, Refresco x1</p>
                    <div class="pedido-flotante-bottom">
                        <span class="pedido-hora"><i class='bx bx-time'></i> 11:30 AM</span>
                        <span class="pedido-total">L. 250.00</span>
                    </div>
                </article>

            </div>
        </div>
    </div>

    <!-- Botón flotante estatico-->
    <button class="pedidos-toggle-btn" id="pedidos-toggle-btn" aria-label="Ver pedidos">
        <i class='bx bx-receipt'></i>
        <span class="pedidos-toggle-badge">2</span>
        <span class="pedidos-toggle-label">Pedidos</span>
    </button>
</div>