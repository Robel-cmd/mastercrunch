let _comboState = {
    todosLosCombos:       [],
    valorTotalProd:       0,
    valorTotalProdedit:   0,
    comboAnteriorUrl:     "",
    idComboEnEdicion:     null,
    idComboActual:        null,
    accionComboActual:    null,
    idComboEliminar:      null,
    apiUrls:              null,
    cargarAPI:            null, 
    limpiarModalEdit:     null, 
    render:               null
};
let _comboDocReady = false;
let _paginaComboActual = 1;
let _combosMostrados   = [];
const COMBOS_POR_PAGINA = 6; // máximo
const ANCHO_TARJETA_COMBO = 300;

function initCombo() {

    // Rutas dinámicas
    const BASE_URL = window.BASE_URL;
    const API_BASE = window.API_BASE;

    const apirurlProductos = `${API_BASE}/meta.php?entity=producto`;
    const apirurlCombos    = `${API_BASE}/meta.php?entity=combo`;

    // Guardar URLs en el estado global
    _comboState.apiUrls = {
        prod:  apirurlProductos,
        combo: apirurlCombos
    };

    const contenedor  = document.querySelector('#contenedor-combos');
    const inputSearch = document.querySelector('#searchInputCombo');


    // Reconstruye el array de detalles desde el DOM
    function recolectarDetallesDesdeDOM(tbodyId) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return [];
        const filas = tbody.querySelectorAll('tr');
        const detalles = [];
        filas.forEach(tr => {
            const id       = tr.getAttribute('data-id-producto');
            const cantidad = tr.getAttribute('data-cantidad');
            const precio   = tr.getAttribute('data-precio');
            if (id && cantidad && precio) {
                detalles.push({
                    id_producto:       id.toString(),
                    cantidad:          cantidad.toString(),
                    precio_individual: precio.toString()
                });
            }
        });
        return detalles;
    }

    function renderizarCombos(registros) {
        if (!contenedor) return;
        contenedor.innerHTML = "";

        if (!registros || registros.length === 0) {
            contenedor.innerHTML = `<p class="no-results" style="padding:20px; text-align:center; width:100%;">No se encontraron combos.</p>`;
            renderPaginacion(document.getElementById('paginacion-combos'), 1, 0, COMBOS_POR_PAGINA, () => {});
            return;
        }

        _combosMostrados = registros;
        const porPagina = calcularPorPagina(contenedor, COMBOS_POR_PAGINA, ANCHO_TARJETA_COMBO);

        const totalPaginas = Math.max(1, Math.ceil(registros.length / porPagina));
        if (_paginaComboActual > totalPaginas) _paginaComboActual = totalPaginas;

        const inicio   = (_paginaComboActual - 1) * porPagina;
        const visibles = registros.slice(inicio, inicio + porPagina);

        let htmlAcumulado = "";

        visibles.forEach(item => {
            const esActivo          = item.activo == 0 ? 'innactivo' : 'activo';
            const claseActivo       = item.activo == 0 ? 'inactivo' : 'activo';
            const cambiarEstadoBtn  = item.activo == 1 ? 'desactivar' : 'activar';
            const textoEstadoVisual = item.activo == 0 ? 'Innactivo' : 'Activo';

            let detallesContent = "";
            if (item.detalles && Array.isArray(item.detalles)) {
                item.detalles.forEach(itemDetalle => {
                    detallesContent += `
                        <div class="card-list detalle">
                            <span class="cant-detalle-combo">x${itemDetalle.cantidad}</span>
                            <span class="nombre-detalle">${itemDetalle.producto_nombre}</span>
                            <span class="detalle-precio">Lps.${itemDetalle.precio_individual}</span>
                        </div>
                    `;
                });
            }

            const urlDefault = (item.url_imagen_combo && item.url_imagen_combo.trim() !== "")
                ? item.url_imagen_combo
                : "uploads/default/default-image.jpg";

            htmlAcumulado += `
                <div class="categoria">
                    <div class="image-wrapper">
                        <div class="image-category" id="categoria-view">
                            <img class="${esActivo}-image" src="${BASE_URL}/uploads/default/innactive.png" alt="innactivo">
                            <img class="img-category" src="${BASE_URL}/${urlDefault}" alt="Imagen del combo">
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-title">${item.nombre}</div>
                        <div class="card-value precio-combo">Lps.${item.precio_total}</div>
                        <div class="detalles">${detallesContent}</div>
                        <div class="card-estado ${claseActivo}">${textoEstadoVisual}</div>
                    </div>
                    <div class="card-actions">
                        <div class="btn btn-edit button-editar btn-combo-edit" data-id="${item.id_combo}" id="editar-combo">Editar</div>
                        <div class="btn btn-${cambiarEstadoBtn} btn-${cambiarEstadoBtn}-combo" data-id="${item.id_combo}">${cambiarEstadoBtn}</div>
                        <div class="btn btn-borrar-Combo" data-id="${item.id_combo}">Eliminar</div>
                    </div>
                </div>
            `;
        });

        contenedor.innerHTML = htmlAcumulado;

        renderPaginacion(
            document.getElementById('paginacion-combos'),
            _paginaComboActual,
            _combosMostrados.length,
            porPagina,
            (p) => { _paginaComboActual = p; renderizarCombos(_combosMostrados); }
        );
    }

    window.addEventListener('resize', () => {
        if (_combosMostrados.length > 0) renderizarCombos(_combosMostrados);
    });

    async function cargarAPICombo() {
        const apiMeta = `${API_BASE}/meta.php?entity=combo`;

        try {
            const response = await fetch(apiMeta);

            if (response.status === 404) {
                _comboState.todosLosCombos = [];
                renderizarCombos([]);
                return;
            }

            if (!response.ok) throw new Error('Error en la respuesta de la API');

            const data = await response.json();
            _comboState.todosLosCombos = data.registros || data || [];
            renderizarCombos(_comboState.todosLosCombos);

        } catch (error) {
            console.error('Error al cargar los datos:', error);
            _comboState.todosLosCombos = [];
            renderizarCombos([]);
        }
    }

    // Guardar refs para listeners persistentes
    _comboState.cargarAPI = cargarAPICombo;
    _comboState.render    = renderizarCombos;

    /*FILTROS DE BÚSQUEDA*/
    if (inputSearch) {
        inputSearch.addEventListener('input', (e) => {
            const textoBusqueda = e.target.value.toLowerCase().trim();
            _paginaComboActual = 1;
            const combosFiltrados = _comboState.todosLosCombos.filter(item => {
                const nombreCombo = item.nombre ? item.nombre.toLowerCase() : "";
                return nombreCombo.includes(textoBusqueda);
            });
            renderizarCombos(combosFiltrados);
        });
    }

    /*FILTRO MODAL AGREGAR*/
    function initFiltroModalCombos() {
        let productosOriginales = [];
        const inputFiltroModal = document.querySelector('#filtro-productos-modal');
        const selectProductos  = document.querySelector('#content-list-product');

        function actualizarSelect(productos) {
            if (!selectProductos) return;
            selectProductos.innerHTML = "";

            if (!productos || productos.length === 0) {
                const option = document.createElement('option');
                option.text = "No se encontraron productos";
                option.disabled = true;
                selectProductos.appendChild(option);
                return;
            }

            const optionDefault = document.createElement('option');
            optionDefault.text = "Seleccione un producto...";
            optionDefault.value = "";
            optionDefault.disabled = true;
            optionDefault.selected = true;
            selectProductos.appendChild(optionDefault);

            productos.forEach(prod => {
                const option = document.createElement('option');
                option.value = prod.id_producto;
                option.text = prod.nombre;
                selectProductos.appendChild(option);
            });
        }

        async function cargarProductosParaModal() {
            try {
                const response = await fetch(apirurlProductos);
                const data = await response.json();
                productosOriginales = data.registros || data || [];
                actualizarSelect(productosOriginales);
            } catch (error) {
                console.error('Error al cargar productos para el modal:', error);
            }
        }

        if (inputFiltroModal) {
            inputFiltroModal.addEventListener('input', (e) => {
                const texto = e.target.value.toLowerCase().trim();
                const productosFiltrados = productosOriginales.filter(prod => {
                    const nombreProd = prod.nombre ? prod.nombre.toLowerCase() : "";
                    return nombreProd.includes(texto);
                });
                actualizarSelect(productosFiltrados);
            });
        }

        cargarProductosParaModal();
    }

    /*FILTRO MODAL EDITAR*/
    function initFiltroModalCombosEdit() {
        let productosOriginalesEdit = [];
        const inputFiltroModalEdit  = document.querySelector('#filtro-productos-modal-edit');
        const selectProductosEdit   = document.querySelector('#content-list-product-edit');

        function actualizarSelectEdit(productos) {
            if (!selectProductosEdit) return;
            selectProductosEdit.innerHTML = "";

            if (!productos || productos.length === 0) {
                const option = document.createElement('option');
                option.text = "No se encontraron productos";
                option.disabled = true;
                selectProductosEdit.appendChild(option);
                return;
            }

            const optionDefault = document.createElement('option');
            optionDefault.text = "Seleccione un producto...";
            optionDefault.value = "";
            optionDefault.disabled = true;
            optionDefault.selected = true;
            selectProductosEdit.appendChild(optionDefault);

            productos.forEach(prod => {
                const option = document.createElement('option');
                option.value = prod.id_producto;
                option.text = prod.nombre;
                selectProductosEdit.appendChild(option);
            });
        }

        async function cargarProductosParaModalEdit() {
            try {
                const response = await fetch(apirurlProductos);
                const data = await response.json();
                productosOriginalesEdit = data.registros || data || [];
                actualizarSelectEdit(productosOriginalesEdit);
            } catch (error) {
                console.error('Error al cargar productos para el modal de edición:', error);
            }
        }

        if (inputFiltroModalEdit) {
            inputFiltroModalEdit.addEventListener('input', (e) => {
                const texto = e.target.value.toLowerCase().trim();
                const productosFiltrados = productosOriginalesEdit.filter(prod => {
                    const nombreProd = prod.nombre ? prod.nombre.toLowerCase() : "";
                    return nombreProd.includes(texto);
                });
                actualizarSelectEdit(productosFiltrados);
            });
        }

        cargarProductosParaModalEdit();
    }

    /* LIMPIAR MODALES*/
    function limpiarModalCombo() {
        _comboState.valorTotalProd = 0;

        const contenidoLista = document.getElementById('content-prod-list');
        if (contenidoLista) contenidoLista.innerHTML = '';

        const campos = ['nombreCombo', 'desc-combo', 'precio-total-combo', 'cant-prod-cmb', 'filtro-productos-modal'];
        campos.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = (id === 'cant-prod-cmb') ? '1' : '';
        });

        const fileInput = document.getElementById('url-imagen-combo');
        if (fileInput) fileInput.value = '';

        const valorSpan = document.getElementById('precio-total-prod');
        if (valorSpan) valorSpan.textContent = "Total: 00.00 Lps";

        const modal = document.getElementById('modal-combos');
        if (modal && modal.open) modal.close();
    }

    function limpiarModalComboEdit() {
        _comboState.idComboEnEdicion   = null;
        _comboState.comboAnteriorUrl   = "";
        _comboState.valorTotalProdedit = 0;

        const contenidoLista = document.getElementById('content-prod-list-edit');
        if (contenidoLista) contenidoLista.innerHTML = '';

        const campos = ['nombreCombo-edit', 'desc-combo-edit', 'precio-total-combo-edit', 'cant-prod-cmb-edit', 'filtro-productos-modal-edit'];
        campos.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = (id === 'cant-prod-cmb-edit') ? '1' : '';
        });

        const fileInput = document.getElementById('url-imagen-combo-edit');
        if (fileInput) fileInput.value = '';

        const valorSpan = document.getElementById('precio-total-prod-edit');
        if (valorSpan) valorSpan.textContent = "Total: 00.00 Lps";

        const modal = document.getElementById('modal-combos-edit');
        if (modal && modal.open) modal.close();
    }

    // Guardar ref
    _comboState.limpiarModalEdit = limpiarModalComboEdit;

    /*LISTENERS EN DOCUMENT*/
    if (!_comboDocReady) {
        _comboDocReady = true;

        /* --- Abrir modal editar combo --- */
        document.addEventListener('click', async (e) => {
            const btnEditarCombo = e.target.closest('.btn-combo-edit');
            if (!btnEditarCombo) return;

            const modalEditCombo = document.getElementById('modal-combos-edit');
            if (!modalEditCombo) return;

            const idCombo = btnEditarCombo.dataset.id;
            _comboState.idComboEnEdicion = idCombo;
            _comboState.valorTotalProdedit = 0;

            const contentProdListEdit = document.getElementById('content-prod-list-edit');
            if (contentProdListEdit) contentProdListEdit.innerHTML = '';

            const apiComboId = `${_comboState.apiUrls.combo}&id=${idCombo}`;
            try {
                const res  = await fetch(apiComboId);
                const data = await res.json();

                const inputNombre = document.getElementById('nombreCombo-edit');
                const inputDesc   = document.getElementById('desc-combo-edit');
                const inputPrecio = document.getElementById('precio-total-combo-edit');

                if (inputNombre) inputNombre.value = data.nombre || '';
                if (inputDesc)   inputDesc.value   = data.descripcion || '';
                if (inputPrecio) inputPrecio.value = data.precio_total || '';

                _comboState.comboAnteriorUrl = data.url_imagen_combo || '';

                const bodyContent = document.getElementById('content-prod-list-edit');
                const valorSpan   = document.getElementById('precio-total-prod-edit');

                if (data.detalles && Array.isArray(data.detalles) && bodyContent) {
                    for (const item of data.detalles) {
                        let nombreProd = item.producto_nombre || "Producto";
                        if (!item.producto_nombre) {
                            try {
                                const resProd  = await fetch(`${_comboState.apiUrls.prod}&id=${item.id_producto}`);
                                const prodData = await resProd.json();
                                if (prodData.nombre) nombreProd = prodData.nombre;
                            } catch (err) { console.error(err); }
                        }
                        const precioInd   = parseFloat(item.precio_individual) || 0;
                        const cantidad    = parseFloat(item.cantidad) || 0;
                        const totalFila   = precioInd * cantidad;

                        _comboState.valorTotalProdedit += totalFila;

                        const contentList = `
                            <tr data-id-producto="${item.id_producto}"
                                data-cantidad="${cantidad}"
                                data-precio="${precioInd}"
                                data-total="${totalFila.toFixed(2)}">
                                <td>${nombreProd}</td>
                                <td>${cantidad}</td>
                                <td>${precioInd.toFixed(2)}</td>
                                <td>${totalFila.toFixed(2)}</td>
                                <td><button type="button" class="btn btn-cancelar-fila-edit" data-total="${totalFila.toFixed(2)}">Cancelar</button></td>
                            </tr>
                        `;
                        bodyContent.insertAdjacentHTML('beforeend', contentList);
                    }
                }

                if (valorSpan) {
                    valorSpan.textContent = _comboState.valorTotalProdedit > 0
                        ? `Total: ${_comboState.valorTotalProdedit.toFixed(2)} Lps`
                        : "Total: 00.00 Lps";
                }
            } catch (error) {
                console.error("Error al cargar el combo para editar:", error);
            }

            if (!modalEditCombo.open) modalEditCombo.showModal();
        });

        /* --- Abrir modal desactivar/activar combo --- */
        document.addEventListener('click', (e) => {
            const btnDesactivar = e.target.closest('.btn-desactivar-combo');
            const btnActivar    = e.target.closest('.btn-activar-combo');
            const warningModalCombo = document.getElementById('warning-modal-desactivar-combo');
            if (!warningModalCombo) return;

            if (btnDesactivar) {
                _comboState.idComboActual     = btnDesactivar.dataset.id;
                _comboState.accionComboActual = 'desactivar';
                if (!warningModalCombo.open) warningModalCombo.showModal();
            } else if (btnActivar) {
                _comboState.idComboActual     = btnActivar.dataset.id;
                _comboState.accionComboActual = 'activar';
                if (!warningModalCombo.open) warningModalCombo.showModal();
            }
        });

        /* --- Confirmar desactivar/activar combo --- */
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#confirmar-desactivar-combo')) return;

            const warningModalCombo = document.getElementById('warning-modal-desactivar-combo');
            const idAccion          = _comboState.idComboActual;
            const accion            = _comboState.accionComboActual;

            if (warningModalCombo && warningModalCombo.open) warningModalCombo.close();
            if (!idAccion || !accion) return;

            const nuevoEstado = accion === 'activar' ? 1 : 0;

            fetch(`${_comboState.apiUrls.combo}&id=${idAccion}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_combo: idAccion, activo: nuevoEstado })
            })
            .then(res => res.json().then(d => ({ ok: res.ok, data: d })))
            .then(({ ok, data }) => {
                if (ok) {
                    if (_comboState.cargarAPI) _comboState.cargarAPI();
                    mostrarNotificacion("¡Se ha modificado el combo con éxito!", "success");
                } else {
                    mostrarNotificacion(data.message || "No se pudo modificar el combo", "error");
                }
            })
            .catch(error => {
                console.error(error);
                mostrarNotificacion("¡No se ha podido modificar el combo!", "error");
            });

            _comboState.idComboActual     = null;
            _comboState.accionComboActual = null;
        });

        /* --- Cancelar desactivar/activar combo --- */
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#cancelar-desactivar-combo')) return;
            const warningModalCombo = document.getElementById('warning-modal-desactivar-combo');
            if (warningModalCombo && warningModalCombo.open) warningModalCombo.close();
            _comboState.idComboActual     = null;
            _comboState.accionComboActual = null;
        });

        /* --- Abrir modal eliminar combo --- */
        document.addEventListener('click', (e) => {
            const btnEliminarCombo = e.target.closest('.btn-borrar-Combo');
            const warningModalBorrarCombo = document.getElementById('warning-modal-borrar-combo');
            if (!btnEliminarCombo || !warningModalBorrarCombo) return;

            const id = btnEliminarCombo.dataset.id;
            if (!id) return;

            _comboState.idComboEliminar = id;
            if (!warningModalBorrarCombo.open) warningModalBorrarCombo.showModal();
        });

        /* --- Confirmar eliminar combo --- */
        document.addEventListener('click', async (e) => {
            if (!e.target.closest('#confirmar-eliminar-combo')) return;

            const warningModalBorrarCombo = document.getElementById('warning-modal-borrar-combo');
            const idEliminar = _comboState.idComboEliminar;

            if (warningModalBorrarCombo && warningModalBorrarCombo.open) warningModalBorrarCombo.close();
            if (!idEliminar) return;

            try {
                const res = await fetch(_comboState.apiUrls.combo, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_combo: idEliminar.toString() })
                });
                const data = await res.json();

                if (res.ok) {
                    mostrarNotificacion(data.message || "Combo eliminado exitosamente", "success");
                    if (_comboState.cargarAPI) _comboState.cargarAPI();
                } else {
                    mostrarNotificacion(data.message || "No se pudo eliminar el combo", "error");
                }
            } catch (error) {
                console.error('Error de red al eliminar:', error);
                mostrarNotificacion("Error de conexión con el servidor", "error");
            }

            _comboState.idComboEliminar = null;
        });

        /* --- Cancelar eliminar combo --- */
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#cancelar-eliminar-combo')) return;
            const warningModalBorrarCombo = document.getElementById('warning-modal-borrar-combo');
            if (warningModalBorrarCombo && warningModalBorrarCombo.open) warningModalBorrarCombo.close();
            _comboState.idComboEliminar = null;
        });
    }


    /* --- Agregar producto al combo (modal crear) --- */
    const btnAgregarProd = document.getElementById('meter-datos-prod-cmb');
    if (btnAgregarProd) {
        btnAgregarProd.addEventListener('click', () => {
            const inputSelectProd = document.getElementById('content-list-product').value;
            const cantidad        = document.getElementById('cant-prod-cmb').value;

            if (!inputSelectProd) { mostrarNotificacion("Selecciona un producto válido", "error"); return; }
            if (!cantidad || cantidad <= 0) { mostrarNotificacion("Ingresa una cantidad válida", "error"); return; }

            fetch(`${apirurlProductos}&id=${inputSelectProd}`)
                .then(res => res.json())
                .then(data => {
                    if (data.nombre === undefined) {
                        mostrarNotificacion("Producto no válido", "error");
                        return;
                    }

                    const precioNum = parseFloat(data.precio) || 0;
                    const total     = precioNum * cantidad;
                    _comboState.valorTotalProd += total;

                    const valorSpan = document.getElementById('precio-total-prod');
                    if (valorSpan) {
                        valorSpan.textContent = _comboState.valorTotalProd > 0
                            ? `Total: ${_comboState.valorTotalProd.toFixed(2)} Lps`
                            : "Total: 00.00 Lps";
                    }

                    const inputTotal = document.getElementById('precio-total-combo');
                    if (inputTotal) inputTotal.value = _comboState.valorTotalProd.toFixed(2);

                    const bodyContent = document.getElementById('content-prod-list');
                    if (!bodyContent) return;

                    const contentList = `
                        <tr data-id-producto="${data.id_producto}"
                            data-cantidad="${cantidad}"
                            data-precio="${precioNum}"
                            data-total="${total.toFixed(2)}">
                            <td>${data.nombre}</td>
                            <td>${cantidad}</td>
                            <td>${precioNum.toFixed(2)}</td>
                            <td>${total.toFixed(2)}</td>
                            <td><button type="button" class="btn btn-cancelar-fila" data-total="${total.toFixed(2)}">Cancelar</button></td>
                        </tr>
                    `;
                    bodyContent.innerHTML += contentList;
                })
                .catch(error => console.error("Error al obtener producto:", error));
        });
    }

    /* --- Cancelar fila (modal crear) --- */
    const bodyContentList = document.getElementById('content-prod-list');
    if (bodyContentList) {
        bodyContentList.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-cancelar-fila, .btn-cancelar-fila-edit');
            if (!btn) return;

            const tr = btn.closest('tr');
            if (!tr) return;

            const totalFila = parseFloat(tr.getAttribute('data-total')) || 0;
            _comboState.valorTotalProd = Math.max(0, _comboState.valorTotalProd - totalFila);

            const valorSpan  = document.getElementById('precio-total-prod');
            const inputTotal = document.getElementById('precio-total-combo');

            if (_comboState.valorTotalProd <= 0) {
                _comboState.valorTotalProd = 0;
                if (valorSpan)  valorSpan.textContent = "Total: 00.00 Lps";
                if (inputTotal) inputTotal.value      = '';
            } else {
                if (valorSpan)  valorSpan.textContent = `Total: ${_comboState.valorTotalProd.toFixed(2)} Lps`;
                if (inputTotal) inputTotal.value      = _comboState.valorTotalProd.toFixed(2);
            }

            tr.remove();
        });
    }

    /* --- Cancelar/cerrar modal crear --- */
    const btnCancelCombos = document.getElementById('btn-cancel-combos');
    const btnCloseCombos  = document.getElementById('btn-close-combos');
    if (btnCancelCombos) btnCancelCombos.addEventListener('click', limpiarModalCombo);
    if (btnCloseCombos)  btnCloseCombos.addEventListener('click', limpiarModalCombo);

    const modalCombosEl = document.getElementById('modal-combos');
    if (modalCombosEl) modalCombosEl.addEventListener('close', limpiarModalCombo);

    /* --- Crear combo --- */
    const agregarCombo = document.getElementById('AgregarComboNuevo');
    if (agregarCombo) {
        agregarCombo.addEventListener('click', async () => {
            const nombreCombo      = document.getElementById('nombreCombo').value.trim();
            const descCombo        = document.getElementById('desc-combo').value.trim();
            const precioTotalCombo = document.getElementById('precio-total-combo').value.trim();

            const detallesFinales = recolectarDetallesDesdeDOM('content-prod-list');

            if (detallesFinales.length === 0) {
                mostrarNotificacion("Debes agregar al menos un producto al combo.", "error");
                return;
            }
            if (nombreCombo === "" || descCombo === "" || precioTotalCombo === "") {
                mostrarNotificacion("Debes rellenar todos los campos.", "error");
                return;
            }

            const suma = detallesFinales.reduce((acc, d) =>
                acc + (parseFloat(d.precio_individual) * parseInt(d.cantidad)), 0);
            if (Math.abs(suma - parseFloat(precioTotalCombo)) > 0.01) {
                mostrarNotificacion(`El precio total (${parseFloat(precioTotalCombo).toFixed(2)}) no coincide con la suma (${suma.toFixed(2)}).`, "error");
                return;
            }

            const formData = new FormData();
            formData.append("nombre", nombreCombo);
            formData.append("descripcion", descCombo);
            formData.append("precio_total", precioTotalCombo);
            formData.append("activo", "1");

            detallesFinales.forEach((det, i) => {
                formData.append(`detalles[${i}][id_producto]`, det.id_producto);
                formData.append(`detalles[${i}][cantidad]`, det.cantidad);
                formData.append(`detalles[${i}][precio_individual]`, det.precio_individual);
            });

            const fileInput = document.getElementById('url-imagen-combo');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                formData.append("imagen", fileInput.files[0]);
            }

            try {
                const res = await fetch(apirurlCombos, { method: 'POST', body: formData });
                const resultado = await res.json();
                if (res.ok) {
                    mostrarNotificacion(resultado.message, "success");
                    limpiarModalCombo();
                    cargarAPICombo();
                } else {
                    mostrarNotificacion(resultado.message, "error");
                }
            } catch (error) {
                console.error("Error de red al enviar el combo:", error);
            }
        });
    }

    /* --- Cerrar modal editar (X y Cancelar) --- */
    const modalEditCombo        = document.getElementById('modal-combos-edit');
    const btncerrarEditFCombo   = document.getElementById('btn-close-combos-edit');
    const btnCancelEditarCombo  = document.getElementById('btn-cancel-combos-edit');

    if (modalEditCombo) modalEditCombo.addEventListener('close', limpiarModalComboEdit);
    if (btncerrarEditFCombo) btncerrarEditFCombo.addEventListener('click', limpiarModalComboEdit);
    if (btnCancelEditarCombo) btnCancelEditarCombo.addEventListener('click', limpiarModalComboEdit);

    /* --- Cancelar fila (modal editar) --- */
    const bodyContentListEdit = document.getElementById('content-prod-list-edit');
    if (bodyContentListEdit) {
        bodyContentListEdit.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-cancelar-fila-edit, .btn-cancelar-fila');
            if (!btn) return;

            const tr = btn.closest('tr');
            if (!tr) return;

            const totalFila = parseFloat(tr.getAttribute('data-total')) || 0;
            _comboState.valorTotalProdedit = Math.max(0, _comboState.valorTotalProdedit - totalFila);

            const valorSpan  = document.getElementById('precio-total-prod-edit');
            const inputTotal = document.getElementById('precio-total-combo-edit');

            if (_comboState.valorTotalProdedit <= 0) {
                _comboState.valorTotalProdedit = 0;
                if (valorSpan)  valorSpan.textContent = "Total: 00.00 Lps";
                if (inputTotal) inputTotal.value      = '';
            } else {
                if (valorSpan)  valorSpan.textContent = `Total: ${_comboState.valorTotalProdedit.toFixed(2)} Lps`;
                if (inputTotal) inputTotal.value      = _comboState.valorTotalProdedit.toFixed(2);
            }

            tr.remove();
        });
    }

    /* --- Agregar producto (modal editar) --- */
    const btnAgregarProdEdit = document.getElementById('meter-datos-prod-cmb-edit');
    if (btnAgregarProdEdit) {
        btnAgregarProdEdit.addEventListener('click', async () => {
            const inputSelectProd = document.getElementById('content-list-product-edit').value;
            const cantidad        = parseFloat(document.getElementById('cant-prod-cmb-edit').value);

            if (!inputSelectProd) { mostrarNotificacion("Selecciona un producto válido", "error"); return; }
            if (!cantidad || cantidad <= 0) { mostrarNotificacion("Ingresa una cantidad válida", "error"); return; }

            try {
                const res  = await fetch(`${apirurlProductos}&id=${inputSelectProd}`);
                const data = await res.json();
                if (!data || !data.nombre) {
                    mostrarNotificacion("Producto no encontrado", "error");
                    return;
                }

                const precioNum = parseFloat(data.precio) || 0;
                const totalFila = precioNum * cantidad;
                _comboState.valorTotalProdedit += totalFila;

                const valorSpan = document.getElementById('precio-total-prod-edit');
                if (valorSpan) valorSpan.textContent = `Total: ${_comboState.valorTotalProdedit.toFixed(2)} Lps`;

                const inputTotal = document.getElementById('precio-total-combo-edit');
                if (inputTotal) inputTotal.value = _comboState.valorTotalProdedit.toFixed(2);

                const bodyContent = document.getElementById('content-prod-list-edit');
                if (!bodyContent) return;

                const contentList = `
                    <tr data-id-producto="${data.id_producto}"
                        data-cantidad="${cantidad}"
                        data-precio="${precioNum}"
                        data-total="${totalFila.toFixed(2)}">
                        <td>${data.nombre}</td>
                        <td>${cantidad}</td>
                        <td>${precioNum.toFixed(2)}</td>
                        <td>${totalFila.toFixed(2)}</td>
                        <td><button type="button" class="btn btn-cancelar-fila-edit" data-total="${totalFila.toFixed(2)}">Cancelar</button></td>
                    </tr>
                `;
                bodyContent.insertAdjacentHTML('beforeend', contentList);
            } catch (error) {
                console.error("Error al obtener producto:", error);
            }
        });
    }

    /* --- Actualizar combo (PUT) --- */
    const buttonPUTdata = document.getElementById('btn-PUT-combos-edit');
    if (buttonPUTdata) {
        buttonPUTdata.addEventListener('click', async () => {
            const idComboEnEdicion = _comboState.idComboEnEdicion;
            if (!idComboEnEdicion) {
                mostrarNotificacion('No se ha identificado el ID del combo', 'error');
                return;
            }

            const nombreCombo      = document.getElementById('nombreCombo-edit').value.trim();
            const descCombo        = document.getElementById('desc-combo-edit').value.trim();
            const precioTotalCombo = document.getElementById('precio-total-combo-edit').value.trim();

            const detallesFinales = recolectarDetallesDesdeDOM('content-prod-list-edit');

            if (detallesFinales.length === 0) {
                mostrarNotificacion("Debes agregar al menos un producto al combo.", "error");
                return;
            }
            if (nombreCombo === "" || descCombo === "" || precioTotalCombo === "") {
                mostrarNotificacion("Debes rellenar todos los campos obligatorios.", "error");
                return;
            }

            const suma = detallesFinales.reduce((acc, d) =>
                acc + (parseFloat(d.precio_individual) * parseInt(d.cantidad)), 0);
            if (Math.abs(suma - parseFloat(precioTotalCombo)) > 0.01) {
                mostrarNotificacion(`El precio total (${parseFloat(precioTotalCombo).toFixed(2)}) no coincide con la suma (${suma.toFixed(2)}).`, "error");
                return;
            }

            const formData = new FormData();
            formData.append("id_combo", idComboEnEdicion.toString());
            formData.append("nombre", nombreCombo);
            formData.append("descripcion", descCombo);
            formData.append("precio_total", precioTotalCombo);
            formData.append("activo", "1");

            detallesFinales.forEach((det, i) => {
                formData.append(`detalles[${i}][id_producto]`, det.id_producto);
                formData.append(`detalles[${i}][cantidad]`, det.cantidad);
                formData.append(`detalles[${i}][precio_individual]`, det.precio_individual);
            });

            formData.append("_method", "PUT");

            const inputFile = document.getElementById('url-imagen-combo-edit');
            if (inputFile && inputFile.files && inputFile.files.length > 0) {
                formData.append("imagen", inputFile.files[0]);
            } else {
                formData.append("url_imagen_combo", _comboState.comboAnteriorUrl || "");
            }

            try {
                const res = await fetch(`${apirurlCombos}&id=${idComboEnEdicion}`, {
                    method: 'POST',
                    body: formData
                });
                const resultado = await res.json();
                if (res.ok) {
                    mostrarNotificacion(resultado.message || "Combo actualizado con éxito", "success");
                    limpiarModalComboEdit();
                    cargarAPICombo();
                } else {
                    mostrarNotificacion(resultado.message || "Error al actualizar el combo", "error");
                }
            } catch (error) {
                console.error("Error de red al actualizar el combo:", error);
                mostrarNotificacion("Error de conexión con el servidor", "error");
            }
        });
        window.registerPoll(cargarAPICombo, 5000);
    }

    cargarAPICombo();
    initFiltroModalCombos();
    initFiltroModalCombosEdit();
}