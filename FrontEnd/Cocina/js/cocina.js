function initCocina() {
    // Datos de prueba
    // aqui cargaras los datos API
    let pedidos = [
        { id: 1, estado: 'nuevo', pago: 'Efectivo', tiempo: '15min', items: [{ cant: 1, nombre: 'Hamburguesa' }, { cant: 1, nombre: 'Hamburguesa' }] },
        { id: 2, estado: 'nuevo', pago: 'Tarjeta', tiempo: '10min', items: [{ cant: 2, nombre: 'Alitas BBQ' }] },
        { id: 3, estado: 'preparando', pago: 'Efectivo', tiempo: '5min', items: [{ cant: 1, nombre: 'Pizza Personal' }] },
        { id: 4, estado: 'listo', pago: 'Efectivo', tiempo: 'Entregado', items: [{ cant: 1, nombre: 'Pollo Frito' }] }
    ];

    //Elementos del DOM
    const listNuevos = document.getElementById('list-nuevos');
    const listPreparando = document.getElementById('list-preparando');
    const listListo = document.getElementById('list-listo');
    
    const countNuevos = document.getElementById('count-nuevos');
    const countPreparando = document.getElementById('count-preparando');
    const countListo = document.getElementById('count-listo');

    const board = document.querySelector('.cocina-board');

    //Renderizar Tarjetas
    function renderizar() {
        listNuevos.innerHTML = '';
        listPreparando.innerHTML = '';
        listListo.innerHTML = '';

        let totalNuevos = 0, totalPrep = 0, totalListos = 0;

        pedidos.forEach(pedido => {
            const itemsHtml = pedido.items.map(item => `
                <div class="cocina-item">
                    <span class="cocina-item-qty">x${item.cant}</span>
                    <span>${item.nombre}</span>
                </div>
            `).join('');

            let accionTexto = '';
            let accionIcono = '';
            if (pedido.estado === 'nuevo') {
                accionTexto = 'Toca para preparar';
                accionIcono = "<i class='bx bx-chevron-down'></i>";
                totalNuevos++;
            } else if (pedido.estado === 'preparando') {
                accionTexto = 'Toca para completar';
                accionIcono = "<i class='bx bx-check'></i>";
                totalPrep++;
            } else {
                accionTexto = 'Toca para devolver';
                accionIcono = "<i class='bx bx-undo'></i>";
                totalListos++;
            }

            const card = document.createElement('article');
            card.className = 'cocina-card';
            card.dataset.id = pedido.id;
            card.innerHTML = `
                <div class="cocina-card-top">
                    <h3>Pedido #${pedido.id}</h3>
                    <span class="badge-pago">${pedido.pago}</span>
                </div>
                <div class="cocina-card-time">
                    <span>Tiempo estimado</span>
                    <span>${pedido.tiempo}</span>
                </div>
                <div class="cocina-card-items">
                    ${itemsHtml}
                </div>
                <div class="cocina-card-action">
                    ${accionIcono} ${accionTexto}
                </div>
            `;

            if (pedido.estado === 'nuevo') listNuevos.appendChild(card);
            else if (pedido.estado === 'preparando') listPreparando.appendChild(card);
            else listListo.appendChild(card);
        });

        countNuevos.textContent = totalNuevos;
        countPreparando.textContent = totalPrep;
        countListo.textContent = totalListos;
    }

    //Lógica de Long Press
    let progressInterval;
    let currentCard = null;
    let currentIdPedido = null;
    let startX = 0;
    let startY = 0;
    const TIEMPO_CONFIRMACION = 800;
    const MAX_MOVIMIENTO = 15;

    function iniciarPresion(e, card, idPedido) {
        if (currentCard) cancelarPresion();

        currentCard = card;
        currentIdPedido = idPedido;
        startX = e.clientX;
        startY = e.clientY;

        // Limpiar barra vieja si existe
        const barraVieja = card.querySelector('.cocina-card-progress');
        if (barraVieja) barraVieja.remove();

        // Crear nueva barra
        const progressBar = document.createElement('div');
        progressBar.className = 'cocina-card-progress';
        card.appendChild(progressBar);

        let tiempoTranscurrido = 0;
        card.classList.add('presionando');
        
        progressBar.style.width = '0%';
        void progressBar.offsetWidth; 

        progressInterval = setInterval(() => {
            tiempoTranscurrido += 50;
            const porcentaje = (tiempoTranscurrido / TIEMPO_CONFIRMACION) * 100;
            progressBar.style.width = `${porcentaje}%`;

            if (tiempoTranscurrido >= TIEMPO_CONFIRMACION) {
                cancelarPresion();
                confirmarPedido(idPedido, card);
            }
        }, 50);
    }

    function cancelarPresion() {
        clearInterval(progressInterval);
        
        if (currentCard) {
            currentCard.classList.remove('presionando');
            const progressBar = currentCard.querySelector('.cocina-card-progress');
            if (progressBar) progressBar.remove(); // Eliminar del DOM
        }
        
        currentCard = null;
        currentIdPedido = null;
    }

    function confirmarPedido(idPedido, card) {
        const pedido = pedidos.find(p => p.id === idPedido);
        if (!pedido) return;

        if (pedido.estado === 'nuevo') {
            pedido.estado = 'preparando';
        } else if (pedido.estado === 'preparando') {
            pedido.estado = 'listo';
        }

        card.style.transform = 'scale(0.95)';
        card.style.opacity = '0';
        
        setTimeout(() => {
            renderizar();
        }, 200);
    }

    // --- Eventos Pointer
    board.addEventListener('pointerdown', (e) => {
        const card = e.target.closest('.cocina-card');
        // Ignorar si no hay tarjeta
        if (!card || card.closest('#col-listo')) return; 

        // Iniciar presión pasando el evento
        iniciarPresion(e, card, parseInt(card.dataset.id));
    });

    // Escuchar para soltar o cancelar
    window.addEventListener('pointerup', () => {
        if (currentCard) cancelarPresion();
    });

    window.addEventListener('pointercancel', () => {
        if (currentCard) cancelarPresion();
    });

    // Si el dedo se mueve mucho, cancelar
    window.addEventListener('pointermove', (e) => {
        if (!currentCard) return;
        
        const dx = Math.abs(e.clientX - startX);
        const dy = Math.abs(e.clientY - startY);
        
        if (dx > MAX_MOVIMIENTO || dy > MAX_MOVIMIENTO) {
            cancelarPresion();
        }
    });

    // Modal de Reversión
    const modalRevertir = document.getElementById('modal-revertir');
    const btnConfirmarRevertir = document.getElementById('confirmar-revertir');
    const btnCancelarRevertir = document.getElementById('cancelar-revertir');
    let idPedidoARevertir = null;

    // Clic normal modal
    board.addEventListener('click', (e) => {
        const card = e.target.closest('.cocina-card');
        if (!card) return;

        if (card.closest('#col-listo')) {
            idPedidoARevertir = parseInt(card.dataset.id);
            if (modalRevertir) modalRevertir.showModal();
        }
    });

    if (btnCancelarRevertir) {
        btnCancelarRevertir.addEventListener('click', () => {
            modalRevertir.close();
            idPedidoARevertir = null;
        });
    }

    if (btnConfirmarRevertir) {
        btnConfirmarRevertir.addEventListener('click', () => {
            if (idPedidoARevertir) {
                const pedido = pedidos.find(p => p.id === idPedidoARevertir);
                if (pedido) {
                    pedido.estado = 'preparando';
                    renderizar();
                }
            }
            modalRevertir.close();
            idPedidoARevertir = null;
        });
    }
    renderizar();
}