function initCocina() {
    // ------------------------------------------------------------------
    // Tablero de cocina — funciona con DATOS DE PRUEBA (mock local)
    //
    // La integración con la API queda pendiente para el encargado del
    // backend; los puntos de conexión están marcados con "TODO API:".
    //
    // Estados de pedidos.estado que gobiernan las columnas:
    //   pendiente/recibido -> En cola
    //   preparacion        -> En preparación
    //   entregado          -> Completado
    //   cancelado          -> no se muestra en cocina
    // ------------------------------------------------------------------

    // Elementos del DOM
    const listNuevos = document.getElementById('list-nuevos');
    const listPreparando = document.getElementById('list-preparando');
    const listListo = document.getElementById('list-listo');

    const countNuevos = document.getElementById('count-nuevos');
    const countPreparando = document.getElementById('count-preparando');
    const countListo = document.getElementById('count-listo');

    const board = document.querySelector('.cocina-board');
    if (!board || !listNuevos || !listPreparando || !listListo) return;

    const btnPrepararTodos = document.getElementById('btn-preparar-todos');

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------
    function minutosAtras(min) {
        return new Date(Date.now() - min * 60000).toISOString();
    }

    // Mapea el estado del pedido a la columna del tablero
    function columnaDeEstado(estado) {
        const e = String(estado || '').toLowerCase().trim();
        if (e === 'pendiente' || e === 'recibido') return 'nuevo';
        if (e === 'preparacion') return 'preparando';
        if (e === 'entregado') return 'listo';
        return null; // cancelado u otros: no se muestra en cocina
    }

    function etiquetaEstado(estado) {
        if (estado === 'preparacion') return 'En preparación';
        if (estado === 'entregado') return 'Completado';
        return 'En cola';
    }

    function tiempoTranscurrido(fecha) {
        if (!fecha) return '';
        const inicio = new Date(String(fecha).replace(' ', 'T')).getTime();
        if (isNaN(inicio)) return '';
        const min = Math.floor((Date.now() - inicio) / 60000);
        if (min < 1) return 'Hace instantes';
        if (min < 60) return `Hace ${min} min`;
        const h = Math.floor(min / 60);
        return `Hace ${h} h ${min % 60} min`;
    }

    // ------------------------------------------------------------------
    // Datos de prueba del tablero
    // TODO API: sustituir por fetch a api/pedido.php?entity=pedido y
    //           ?entity=DetallePedido (los nombres de producto/combo se
    //           resuelven con meta.php?entity=producto / entity=combo).
    //           El backend responde 404 sin registros: tratarlo como [].
    // ------------------------------------------------------------------
    const PEDIDOS_PRUEBA = [
        {
            id_pedido: 101, id_empleado: 1, estado: 'pendiente', tipo_pedido: 'LOCAL',
            cliente: 'María López',
            observaciones: 'Sin cebolla en la hamburguesa',
            fecha_hora_pedido: minutosAtras(3), fecha_hora_entrega: null,
            items: [
                { cant: 2, nombre: 'Hamburguesa clásica', comentario: '' },
                { cant: 1, nombre: 'Papas grandes', comentario: '' }
            ]
        },
        {
            id_pedido: 102, id_empleado: 1, estado: 'pendiente', tipo_pedido: 'PARA_LLEVAR',
            cliente: '',
            observaciones: '',
            fecha_hora_pedido: minutosAtras(8), fecha_hora_entrega: null,
            items: [
                { cant: 1, nombre: 'Combo Crunch', comentario: 'extra queso' },
                { cant: 2, nombre: 'Refresco 500ml', comentario: '' }
            ]
        },
        {
            id_pedido: 103, id_empleado: 1, estado: 'pendiente', tipo_pedido: 'DELIVERY',
            cliente: 'Jorge Ruiz',
            observaciones: 'Llamar antes de llegar',
            fecha_hora_pedido: minutosAtras(1), fecha_hora_entrega: null,
            items: [
                { cant: 1, nombre: 'Ensalada César', comentario: 'aderezo aparte' }
            ]
        },
        {
            id_pedido: 104, id_empleado: 1, estado: 'preparacion', tipo_pedido: 'LOCAL',
            cliente: 'Ana Mejía',
            observaciones: '',
            fecha_hora_pedido: minutosAtras(14), fecha_hora_entrega: null,
            items: [
                { cant: 3, nombre: 'Tacos de res', comentario: '' },
                { cant: 1, nombre: 'Agua natural', comentario: '' }
            ]
        },
        {
            id_pedido: 105, id_empleado: 1, estado: 'preparacion', tipo_pedido: 'PARA_LLEVAR',
            cliente: '',
            observaciones: 'Entregar en caja 2',
            fecha_hora_pedido: minutosAtras(21), fecha_hora_entrega: null,
            items: [
                { cant: 1, nombre: 'Combo familiar', comentario: '' },
                { cant: 2, nombre: 'Papas grandes', comentario: 'bien doradas' }
            ]
        },
        {
            id_pedido: 106, id_empleado: 1, estado: 'entregado', tipo_pedido: 'LOCAL',
            cliente: 'Carla Flores',
            observaciones: '',
            fecha_hora_pedido: minutosAtras(40), fecha_hora_entrega: minutosAtras(12),
            items: [
                { cant: 2, nombre: 'Pizza mediana', comentario: '' }
            ]
        },
        {
            id_pedido: 107, id_empleado: 1, estado: 'entregado', tipo_pedido: 'DELIVERY',
            cliente: 'Pedro Santos',
            observaciones: '',
            fecha_hora_pedido: minutosAtras(55), fecha_hora_entrega: minutosAtras(25),
            items: [
                { cant: 1, nombre: 'Hamburguesa doble', comentario: '' },
                { cant: 1, nombre: 'Refresco 500ml', comentario: '' }
            ]
        }
    ];

    // Copia local de trabajo: los cambios de estado solo viven en esta sesión
    let pedidos = PEDIDOS_PRUEBA.map(p => ({ ...p, items: p.items.map(i => ({ ...i })) }));

    function cargarDatos() {
        // TODO API: traer los pedidos reales, normalizarlos con sus ítems
        //           (igual que el mock: { ...pedido, items: [...] }) y luego:
        renderizar();
    }

    // ------------------------------------------------------------------
    // Renderizado de tarjetas
    // ------------------------------------------------------------------
    function renderizar() {
        listNuevos.innerHTML = '';
        listPreparando.innerHTML = '';
        listListo.innerHTML = '';

        let totalNuevos = 0, totalPrep = 0, totalListos = 0;

        pedidos.forEach(pedido => {
            const columna = columnaDeEstado(pedido.estado);

            const itemsHtml = pedido.items.map(item => `
                <div class="cocina-item">
                    <span class="cocina-item-qty">x${item.cant}</span>
                    <span>${item.nombre}</span>
                </div>
                ${item.comentario ? `<p class="cocina-item-comentario">📝 ${item.comentario}</p>` : ''}
            `).join('') || '<div class="cocina-item"><span>Sin productos registrados</span></div>';

            let accionTexto = '';
            let accionIcono = '';
            if (columna === 'nuevo') {
                accionTexto = 'Mantén pulsado para preparar';
                accionIcono = "<i class='bx bx-chevron-down'></i>";
                totalNuevos++;
            } else if (columna === 'preparando') {
                accionTexto = 'Mantén pulsado para completar';
                accionIcono = "<i class='bx bx-check'></i>";
                totalPrep++;
            } else {
                accionTexto = 'Toca para devolver';
                accionIcono = "<i class='bx bx-undo'></i>";
                totalListos++;
            }

            const tipoPedido = String(pedido.tipo_pedido || '')
                .replace(/_/g, ' ').toUpperCase() || 'PEDIDO';
            const cliente = pedido.cliente || 'Cliente no especificado';
            const cuando = columna === 'listo'
                ? (pedido.fecha_hora_entrega
                    ? 'Entregado ' + tiempoTranscurrido(pedido.fecha_hora_entrega).replace('Hace', 'hace')
                    : 'Completado')
                : tiempoTranscurrido(pedido.fecha_hora_pedido);

            const card = document.createElement('article');
            card.className = 'cocina-card';
            card.dataset.id = pedido.id_pedido;
            card.innerHTML = `
                <div class="cocina-card-top">
                    <h3>Pedido #${pedido.id_pedido}</h3>
                    <span class="badge-pago">${tipoPedido}</span>
                </div>
                <div class="cocina-card-time">
                    <span>${cuando}</span>
                    <span>${cliente}</span>
                </div>
                <div class="cocina-card-items">
                    ${itemsHtml}
                </div>
                ${pedido.observaciones ? `<p class="cocina-card-nota">📌 ${pedido.observaciones}</p>` : ''}
                <div class="cocina-card-action">
                    ${accionIcono} ${accionTexto}
                </div>
            `;

            if (columna === 'nuevo') listNuevos.appendChild(card);
            else if (columna === 'preparando') listPreparando.appendChild(card);
            else listListo.appendChild(card);
        });

        countNuevos.textContent = totalNuevos;
        countPreparando.textContent = totalPrep;
        countListo.textContent = totalListos;
    }

    // ------------------------------------------------------------------
    // Cambio de estado (local por ahora; sin HTTP)
    // TODO API: enviar PUT a api/pedido.php?entity=pedido con
    //           { id_pedido, id_empleado, estado, tipo_pedido }
    //           (+ cliente y observaciones opcionales) — el backend
    //           exige esos cuatro campos en todo update.
    // ------------------------------------------------------------------
    function cambiarEstadoPedido(pedido, nuevoEstado) {
        pedido.estado = nuevoEstado;
        if (nuevoEstado === 'entregado') {
            pedido.fecha_hora_entrega = new Date().toISOString();
        }
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(`Pedido #${pedido.id_pedido} → ${etiquetaEstado(nuevoEstado)}`, 'success');
        }
    }

    function confirmarPedido(idPedido, card) {
        const pedido = pedidos.find(p => String(p.id_pedido) === String(idPedido));
        if (!pedido) return;

        const columna = columnaDeEstado(pedido.estado);
        let nuevoEstado = null;
        if (columna === 'nuevo') nuevoEstado = 'preparacion';
        else if (columna === 'preparando') nuevoEstado = 'entregado';
        if (!nuevoEstado) return;

        cambiarEstadoPedido(pedido, nuevoEstado);

        if (card) {
            card.style.transform = 'scale(0.95)';
            card.style.opacity = '0';
        }
        setTimeout(renderizar, 250);
    }

    // ------------------------------------------------------------------
    // Lógica de Mantener Pulsado (long press)
    // ------------------------------------------------------------------
    let progressInterval = null;
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

        let tiempoTranscurridoMs = 0;
        card.classList.add('presionando');

        progressBar.style.width = '0%';
        void progressBar.offsetWidth;

        progressInterval = setInterval(() => {
            tiempoTranscurridoMs += 50;
            const porcentaje = (tiempoTranscurridoMs / TIEMPO_CONFIRMACION) * 100;
            progressBar.style.width = `${porcentaje}%`;

            if (tiempoTranscurridoMs >= TIEMPO_CONFIRMACION) {
                const id = currentIdPedido;
                const target = currentCard;
                cancelarPresion();
                confirmarPedido(id, target);
            }
        }, 50);
    }

    function cancelarPresion() {
        clearInterval(progressInterval);
        progressInterval = null;

        if (currentCard) {
            currentCard.classList.remove('presionando');
            const progressBar = currentCard.querySelector('.cocina-card-progress');
            if (progressBar) progressBar.remove();
        }

        currentCard = null;
        currentIdPedido = null;
    }

    // --- Eventos Pointer
    board.addEventListener('pointerdown', (e) => {
        const card = e.target.closest('.cocina-card');
        // Ignorar si no hay tarjeta (la columna Completado avanza con click + modal)
        if (!card || card.closest('#col-listo')) return;

        iniciarPresion(e, card, parseInt(card.dataset.id));
    });

    // Handlers globales: se limpian y re-registran en cada navegación
    // al módulo para no duplicar listeners
    if (window._cocinaPointerUp) window.removeEventListener('pointerup', window._cocinaPointerUp);
    window._cocinaPointerUp = () => { if (currentCard) cancelarPresion(); };
    window.addEventListener('pointerup', window._cocinaPointerUp);

    if (window._cocinaPointerCancel) window.removeEventListener('pointercancel', window._cocinaPointerCancel);
    window._cocinaPointerCancel = () => { if (currentCard) cancelarPresion(); };
    window.addEventListener('pointercancel', window._cocinaPointerCancel);

    if (window._cocinaPointerMove) window.removeEventListener('pointermove', window._cocinaPointerMove);
    window._cocinaPointerMove = (e) => {
        if (!currentCard) return;
        const dx = Math.abs(e.clientX - startX);
        const dy = Math.abs(e.clientY - startY);
        if (dx > MAX_MOVIMIENTO || dy > MAX_MOVIMIENTO) cancelarPresion();
    };
    window.addEventListener('pointermove', window._cocinaPointerMove);

    // ------------------------------------------------------------------
    // Modal de reversión (Completado -> En preparación)
    // ------------------------------------------------------------------
    const modalRevertir = document.getElementById('modal-revertir');
    const btnConfirmarRevertir = document.getElementById('confirmar-revertir');
    const btnCancelarRevertir = document.getElementById('cancelar-revertir');
    let idPedidoARevertir = null;

    // Clic normal en columna Completado
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
            if (modalRevertir) modalRevertir.close();
            idPedidoARevertir = null;
        });
    }

    if (btnConfirmarRevertir) {
        btnConfirmarRevertir.addEventListener('click', () => {
            const pedido = pedidos.find(p => String(p.id_pedido) === String(idPedidoARevertir));
            if (pedido) {
                cambiarEstadoPedido(pedido, 'preparacion');
                renderizar();
            }
            if (modalRevertir) modalRevertir.close();
            idPedidoARevertir = null;
        });
    }

    // ------------------------------------------------------------------
    // Botón "Preparar todos"
    // ------------------------------------------------------------------
    if (btnPrepararTodos) {
        btnPrepararTodos.addEventListener('click', () => {
            const enCola = pedidos.filter(p => columnaDeEstado(p.estado) === 'nuevo');
            if (!enCola.length) {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('No hay pedidos en cola', 'error');
                }
                return;
            }
            enCola.forEach(p => cambiarEstadoPedido(p, 'preparacion'));
            renderizar();
        });
    }

    // Primera carga (mock local; el polling se omite hasta conectar la API)
    cargarDatos();
}
