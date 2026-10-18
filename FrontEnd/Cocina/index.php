<header class="header-body">
    <h1>Cocina</h1>
    <h4>Gestión de pedidos.</h4>
</header>

<div class="cocina-board">
    
    <!--NUEVAS ÓRDENES -->
    <section class="cocina-column" id="col-nuevos">
        <div class="cocina-column-header">
            <div class="icon-image">
                <i class='bx bx-receipt'></i>
                <span class="titulo">En cola</span>
                <span class="badge-count" id="count-nuevos">0</span>
            </div>
            <button class="btn-preparar-todos" id="btn-preparar-todos">Preparar todos</button>
        </div>
        <div class="cocina-list" id="list-nuevos">
            <!-- Las tarjetas se generan con JS -->
        </div>
    </section>

    <!--EN PREPARACIÓN -->
    <section class="cocina-column" id="col-preparando">
        <div class="cocina-column-header">
            <div class="icon-image">
                <i class='bx bx-time-five'></i>
                <span class="titulo">En preparación</span>
                <span class="badge-count" id="count-preparando">0</span>
            </div>
        </div>
        <div class="cocina-list" id="list-preparando">
            <!-- Las tarjetas  con JS -->
        </div>
    </section>

    <!--LISTO -->
    <section class="cocina-column" id="col-listo">
        <div class="cocina-column-header">
            <div class="icon-image">
                <i class='bx bx-check-circle'></i>
                <span class="titulo">Completado</span>
                <span class="badge-count" id="count-listo">0</span>
            </div>
        </div>
        <div class="cocina-list" id="list-listo">
            <!-- Las tarjetas con JS -->
        </div>
    </section>

</div>
<!-- Modal de Advertencia-->
<dialog class="warning-modal" id="modal-revertir">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            <strong>¿Devolver pedido a preparación?</strong><br>
            El pedido volverá a la columna de "En preparación".
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-revertir">No, mantener</button>
            <button class="confirmar-desactivar" id="confirmar-revertir">Sí, devolver</button>
        </div>
    </section>
</dialog>