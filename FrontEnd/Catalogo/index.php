<header class="header-body">
    <h1>Pedidos</h1>
    <h4>Realice ventas, pedidos.</h4>
</header>

<!--BARRA DE BÚSQUEDA GLOBAL-->
<section class="container-secondary seccion-categorias-sticky">
    <div class="header-card-container categorias-header">
        <div class="header-left">
            <div class="icon-image">
                <i class='bx bx-category-alt'></i>
                <span class="titulo">Listado de categorías</span>
            </div>
        </div>

        <div class="header-actions-group">
            <!-- Toggle del buscador -->
            <button class="btn-toggle-icon" id="btn-toggle-search" aria-expanded="true" title="Buscar">
                <i class='bx bx-search'></i>
                <i class='bx bx-chevron-down toggle-arrow'></i>
            </button>

            <!-- Toggle de categorías -->
            <button class="btn-toggle-icon" id="btn-toggle-cats" aria-expanded="true" title="Categorías">
                <i class='bx bx-category'></i>
                <i class='bx bx-chevron-down toggle-arrow'></i>
            </button>
        </div>
    </div>

    <!-- Bloque colapsable: buscador global -->
    <div class="search-bar-collapsible open" id="search-bar-collapsible">
        <div class="search-general">
            <i class='bx bx-search-alt-2'></i>
            <input type="search" placeholder="Buscar en categorías, catálogo y tabla..." id="buscador-global">
        </div>
    </div>

    <!-- Bloque colapsable: carrusel de categorías -->
    <div class="cats-collapsible open" id="cats-collapsible">
        <div class="cats-collapsible-inner">
            <div class="carrusel-categorias" id="categorias-container"></div>
            <div class="paginacion" id="paginacion-catalogo-categorias"></div>
        </div>
    </div>
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

        <div class="filtros-catalogo">
            <button class="chip-filtro active" data-filtro="todos" type="button">Todos</button>
            <button class="chip-filtro" data-filtro="productos" type="button">Productos</button>
            <button class="chip-filtro" data-filtro="combos" type="button">Combos</button>
            <button class="chip-filtro" data-filtro="extras" type="button">Extras</button>
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
            <!-- Los productos agregados se renderizan dinámicamente aquí -->
        </div>

        <footer class="acciones-pedido">
            <button class="btn-borrar">Borrar lista</button>
            <button class="btn-facturar">Facturar pedido</button>
        </footer>
    </aside>
</div>


<!-- CONFIRMACIONES PEDIDO -->

<!-- Borrar lista -->
<dialog class="warning-modal" id="warning-modal-borrar-lista">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            <strong>¿Está seguro que quiere borrar toda la lista?</strong><br>
            Los productos agregados se eliminarán.
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-borrar-lista">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-borrar-lista">Sí, borrar</button>
        </div>
    </section>
</dialog>

<!-- Facturar (solo visual por ahora) -->
<dialog class="warning-modal" id="modal-confirmacion-facturar">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-receipt'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            Facturar estará disponible próximamente.
        </p>
        <div>
            <button class="confirmar-desactivar" id="aceptar-modal-facturar">Aceptar</button>
        </div>
    </section>
</dialog>

<!-- Modal de detalle de combo -->
<dialog class="modal" id="modal-combo-catalogo">
    <header class="modal-header">
        <h2 class="modal-title" id="combo-modal-titulo">Combo</h2>
        <button class="modal-close" id="btn-close-combo-catalogo">X</button>
    </header>
    <section class="modal-content">
        <div class="combo-detalle">
            <p id="combo-modal-descripcion"></p>
            <ul id="combo-modal-lista" class="combo-detalle-lista"></ul>
            <p id="combo-modal-precio" class="combo-detalle-precio"></p>
        </div>
        <div class="contenido-botones-modal">
            <button class="emergente-btn" id="btn-cancel-combo-catalogo">Cancelar</button>
            <button class="confirm-btn" id="btn-agregar-combo-catalogo">Agregar</button>
        </div>
    </section>
</dialog>

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