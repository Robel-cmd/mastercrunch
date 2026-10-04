
let _prodState = {
    listaProductos:   [],
    listaMostrada:    [],
    objetoCategorias: {},
    idEliminar:       null,
    idEditar:         null,
    apiUrls:          null, 
    cargarAPI:        null,
    render:           null  
};
let _prodDocReady = false;
let _paginaProdActual = 1;
const PRODUCTOS_POR_PAGINA = 10;

function initProductos() {

    // Rutas dinámicas
    const BASE_URL = window.BASE_URL;
    const API_BASE = window.API_BASE;

    const apirurlCat       = `${API_BASE}/meta.php?entity=categoria`;
    const apirurlProductos = `${API_BASE}/meta.php?entity=producto`;
    const apirurlCombos    = `${API_BASE}/meta.php?entity=combo`;

    // Guardamos las URLs en el estado global
    _prodState.apiUrls = {
        cat:   apirurlCat,
        prod:  apirurlProductos,
        combo: apirurlCombos
    };

    function renderizarTabla(registros) {
        const contenedorTabla = document.querySelector('#table-content tbody');
        if (!contenedorTabla) return;
        contenedorTabla.innerHTML = "";

        if (!registros || registros.length === 0) {
            contenedorTabla.innerHTML = `<tr><td colspan="8" style="text-align:center;">No se encontraron productos</td></tr>`;
            renderPaginacion(document.getElementById('paginacion-productos'), 1, 0, PRODUCTOS_POR_PAGINA, () => {});
            return;
        }

        _prodState.listaMostrada = registros;

        const totalPaginas = Math.max(1, Math.ceil(registros.length / PRODUCTOS_POR_PAGINA));
        if (_paginaProdActual > totalPaginas) _paginaProdActual = totalPaginas;

        const inicio   = (_paginaProdActual - 1) * PRODUCTOS_POR_PAGINA;
        const visibles = registros.slice(inicio, inicio + PRODUCTOS_POR_PAGINA);

        registros = visibles;
        registros.forEach(item => {
            const disponibilidad  = item.disponibilidad == 0 ? 'Agotado' : 'Disponible';
            const esExtraBool     = String(item.es_extra) === "1";
            const extraIcono      = esExtraBool ? '✅' : '❌';
            const nombreCategoria = _prodState.objetoCategorias[String(item.id_categoria)] || "Ninguna";
            const urlDefault      = (item.url_imagen && item.url_imagen.trim() !== "")
                ? item.url_imagen
                : "uploads/default/default-image.jpg";

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td class="codigo-interno">${item.codigo_interno}</td>
                <td class="product-name-cell" style="cursor:pointer;">
                    <div>
                        <img src="${BASE_URL}/${urlDefault}" alt="Producto" class="img-prod">
                    </div>
                </td>
                <td class="nombre-producto">${item.nombre}</td>
                <td class="product-price-cell">${item.precio}</td>
                <td>${nombreCategoria}</td>
                <td class="row-disponible ${disponibilidad}">${disponibilidad}</td>
                <td>${extraIcono}</td>
                <td>
                    <div class="table-actions">
                        <div class="btn btn-edit-prod btn-editar"
                             data-id="${item.id_producto}"
                             data-codint="${item.codigo_interno}"
                             data-img="${item.url_imagen || ''}"
                             data-nombre="${item.nombre}"
                             data-price="${item.precio}"
                             data-categoriaid="${item.id_categoria}"
                             data-extra="${esExtraBool ? '1' : '0'}">
                             Editar
                        </div>
                        <div class="btn btn-delete-prod btn-eliminar btn-borrar" data-id="${item.id_producto}">Eliminar</div>
                    </div>
                </td>
            `;
            contenedorTabla.appendChild(fila);
        });

        renderPaginacion(
            document.getElementById('paginacion-productos'),
            _paginaProdActual,
            _prodState.listaMostrada.length,
            PRODUCTOS_POR_PAGINA,
            (p) => { _paginaProdActual = p; renderizarTabla(_prodState.listaMostrada); }
        );
    }

    async function cargarAPIProducto() {
        try {
            const [resCat, resProd] = await Promise.all([
                fetch(apirurlCat),
                fetch(apirurlProductos)
            ]);

            let categoriaData = {};
            let ProductoData  = {};
            try { categoriaData = await resCat.json(); } catch (_) { categoriaData = {}; }
            try { ProductoData  = await resProd.json(); } catch (_) { ProductoData  = {}; }

            const catsArray = Array.isArray(categoriaData)
                ? categoriaData
                : (categoriaData.registros || []);

            const prodsArray = Array.isArray(ProductoData)
                ? ProductoData
                : (ProductoData.registros || []);

            const ObjCat = {};
            catsArray.forEach(item => {
                const catId = item.id_categoria || item.id;
                if (catId) ObjCat[String(catId)] = item.nombre;
            });
            _prodState.objetoCategorias = ObjCat;

            const selectCategorias     = document.querySelector('#content-categorias-list');
            const selectCategoriasEdit = document.querySelector('#content-categorias-list-edit');

            if (selectCategorias)     selectCategorias.innerHTML     = "";
            if (selectCategoriasEdit) selectCategoriasEdit.innerHTML = "";

            catsArray.forEach(cat => {
                if (cat.activo == 0) return;
                const catId = cat.id_categoria || cat.id;

                if (selectCategorias) {
                    const optionAdd = document.createElement("option");
                    optionAdd.value = catId;
                    optionAdd.textContent = cat.nombre;
                    selectCategorias.appendChild(optionAdd);
                }
                if (selectCategoriasEdit) {
                    const optionEdit = document.createElement("option");
                    optionEdit.value = catId;
                    optionEdit.textContent = cat.nombre;
                    selectCategoriasEdit.appendChild(optionEdit);
                }
            });

            _prodState.listaProductos = prodsArray;
            renderizarTabla(_prodState.listaProductos);

        } catch (error) {
            console.error('Error al cargar los datos:', error);
            _prodState.listaProductos = [];
            renderizarTabla([]);
        }
    }

    // Guardar referencias para que los listeners persistentes las usen
    _prodState.cargarAPI = cargarAPIProducto;
    _prodState.render    = renderizarTabla;

    cargarAPIProducto();

    if (!_prodDocReady) {
        _prodDocReady = true;

        /* --- Vista previa de imagen --- */
        document.addEventListener('click', (e) => {
            const img = e.target.closest('.img-prod');
            if (!img) return;
            const modalViewImage    = document.getElementById('modalViewImg');
            const imgPreviewContent = document.querySelector('#modalViewImg #img-preview-content');
            if (modalViewImage && imgPreviewContent) {
                imgPreviewContent.src = img.src;
                imgPreviewContent.alt = img.alt;
                if (!modalViewImage.open) modalViewImage.showModal();
            }
        });

        /* --- Abrir modal eliminar--- */
        document.addEventListener('click', async (e) => {
            const btnVariableEliminar = e.target.closest('.btn-eliminar');
            if (!btnVariableEliminar) return;

            const modalEliminarP = document.getElementById('warning-modal-borrar-P');
            if (!modalEliminarP) return;

            const idTemporal = btnVariableEliminar.dataset.id;
            if (!idTemporal) return;

            try {
                const res = await fetch(_prodState.apiUrls.combo);
                let data = {};
                try { data = await res.json(); } catch (_) { data = {}; }
                const combos = Array.isArray(data) ? data : (data.registros || []);

                const tieneProductosAsociados = combos.some(com =>
                    com.detalles && com.detalles.some(detalle =>
                        String(detalle.id_producto) === String(idTemporal)
                    )
                );

                if (tieneProductosAsociados) {
                    mostrarNotificacion("No se puede Eliminar el producto porque tiene combos asociados", "error");
                    return;
                }

                _prodState.idEliminar = idTemporal;
                if (!modalEliminarP.open) modalEliminarP.showModal();
            } catch (error) {
                console.error(error);
                mostrarNotificacion("Error al intentar verificar el producto", "error");
            }
        });

        /* --- Confirmar eliminar producto --- */
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#confirmar-eliminar-P')) return;

            const modalEliminarP = document.getElementById('warning-modal-borrar-P');
            const idAEliminar    = _prodState.idEliminar;
            if (!idAEliminar) return;

            if (modalEliminarP && modalEliminarP.open) modalEliminarP.close();

            fetch(_prodState.apiUrls.prod, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_producto: idAEliminar })
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok) {
                    mostrarNotificacion('¡Se ha eliminado el producto con éxito!', 'success');
                    if (_prodState.cargarAPI) _prodState.cargarAPI();
                } else {
                    mostrarNotificacion(data.message || "No se pudo eliminar el producto", "error");
                }
            })
            .catch(error => {
                console.error(error);
                mostrarNotificacion("Error al intentar eliminar el producto", "error");
            });

            _prodState.idEliminar = null;
        });

        /* --- Cancelar eliminar producto --- */
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#cancelar-eliminar-P')) return;
            const modalEliminarP = document.getElementById('warning-modal-borrar-P');
            if (modalEliminarP && modalEliminarP.open) modalEliminarP.close();
            _prodState.idEliminar = null;
        });

        /* --- Abrir modal editar producto --- */
        document.addEventListener('click', (e) => {
            const btnEditarProd = e.target.closest('.btn-edit-prod');
            if (!btnEditarProd) return;

            const modalEditProd = document.getElementById('modal-producto-edit');
            if (!modalEditProd) return;

            _prodState.idEditar = btnEditarProd.dataset.id;

            const codInt      = btnEditarProd.dataset.codint;
            const nombre      = btnEditarProd.dataset.nombre;
            const price       = btnEditarProd.dataset.price;
            const categoriaID = btnEditarProd.dataset.categoriaid;
            const esExtra     = btnEditarProd.dataset.extra;

            const inputImagen = document.getElementById('url-image-edit-prod');
            if (inputImagen) inputImagen.value = '';

            const selectCatEdit = document.getElementById('content-categorias-list-edit');
            if (selectCatEdit) selectCatEdit.value = categoriaID;

            const radioS = document.getElementById('checked-si-rad');
            const radion = document.getElementById('checked-no-rad');
            if (esExtra === '1') {
                if (radioS) radioS.checked = true;
                if (radion) radion.checked = false;
            } else {
                if (radion) radion.checked = true;
                if (radioS) radioS.checked = false;
            }

            const inputCodInt = document.getElementById('text-codigoInterno-edit');
            const inputNombre = document.getElementById('text-name-edit');
            const inputPrice  = document.getElementById('number-price-edit');
            if (inputCodInt) inputCodInt.value = codInt || '';
            if (inputNombre) inputNombre.value = nombre || '';
            if (inputPrice)  inputPrice.value  = price  || '';

            if (!modalEditProd.open) modalEditProd.showModal();
        });
    }


    /* --- Cerrar modal vista imagen --- */
    const modalViewImage = document.getElementById('modalViewImg');
    const btnCloseModal  = document.getElementById('btn-cancel-viewImg');
    const btnXmodal      = document.getElementById('btn-close-viewImage');

    if (btnCloseModal && modalViewImage) {
        btnCloseModal.addEventListener('click', () => modalViewImage.close());
    }
    if (btnXmodal && modalViewImage) {
        btnXmodal.addEventListener('click', () => modalViewImage.close());
    }
    if (modalViewImage) {
        modalViewImage.addEventListener('click', (e) => {
            const d = modalViewImage.getBoundingClientRect();
            if (e.clientX < d.left || e.clientX > d.right ||
                e.clientY < d.top  || e.clientY > d.bottom) {
                modalViewImage.close();
            }
        });
    }

    /* --- Agregar producto (POST) --- */
    const btnAgregarProducto = document.getElementById('btn-POST-producto');
    const btncancelProd      = document.getElementById('btn-cancel-product');
    const modalP             = document.getElementById('modal-producto');

    if (btnAgregarProducto) {
        btnAgregarProducto.addEventListener('click', () => {
            const inputCodigo    = document.getElementById('text-codigoInterno');
            const inputNombre    = document.getElementById('text-name');
            const inputPrecio    = document.getElementById('number-price');
            const inputCategoria = document.getElementById('content-categorias-list');
            const radioExtra     = document.querySelector('input[name="extraA"]:checked');
            const inputImagen    = document.getElementById('url-image');

            const codigoInterno  = inputCodigo.value.trim();
            const nombre         = inputNombre.value.trim();
            const precio         = inputPrecio.value.trim();
            const categoria      = inputCategoria.value;
            const valorEsExtra   = radioExtra ? radioExtra.value : null;
            const archivo        = inputImagen.files ? inputImagen.files[0] : null;
            const disponibilidad = 1;

            if (!valorEsExtra || !codigoInterno || !nombre || !precio || !categoria || !archivo) {
                mostrarNotificacion('Rellene todos los datos y seleccione una imagen.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('codigo_interno', codigoInterno);
            formData.append('nombre', nombre);
            formData.append('precio', precio);
            formData.append('id_categoria', categoria);
            formData.append('imagen', archivo);
            formData.append('es_extra', valorEsExtra);
            formData.append('disponibilidad', disponibilidad);

            fetch(apirurlProductos, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(() => {
                    if (modalP) modalP.close();
                    cargarAPIProducto();
                    mostrarNotificacion("¡Se han agregado los datos de forma exitosa!", "success");

                    inputCodigo.value    = "";
                    inputNombre.value    = "";
                    inputPrecio.value    = "";
                    inputCategoria.value = "";
                    inputImagen.value    = "";

                    const radioNo = document.querySelector('input[name="extraA"][value="0"]');
                    if (radioNo) radioNo.checked = true;
                })
                .catch(error => {
                    console.error(error);
                    mostrarNotificacion("Ha ocurrido un error al intentar enviar los datos", "error");
                });
        });
    }

    if (btncancelProd && modalP) {
        btncancelProd.addEventListener('click', () => {
            document.getElementById('text-codigoInterno').value       = "";
            document.getElementById('text-name').value                 = "";
            document.getElementById('number-price').value              = "";
            document.getElementById('content-categorias-list').value   = "";
            document.getElementById('url-image').value                 = "";

            const radioNo = document.querySelector('input[name="extraA"][value="0"]');
            if (radioNo) radioNo.checked = true;

            modalP.close();
        });
    }

    /* --- Cancelar eliminar (por si el modal se abre desde otra vía) --- */
    const btnCancelEliminarProd = document.getElementById('cancelar-eliminar-P');
    const modalEliminarP        = document.getElementById('warning-modal-borrar-P');
    if (btnCancelEliminarProd && modalEliminarP) {
        btnCancelEliminarProd.addEventListener('click', () => {
            modalEliminarP.close();
        });
    }
    if (modalEliminarP) {
        modalEliminarP.addEventListener('close', () => {
            _prodState.idEliminar = null;
        });
    }

    /* --- Cerrar modal editar (X y Cancelar) --- */
    const modalEditProd       = document.getElementById('modal-producto-edit');
    const btncerrarEditProd   = document.getElementById('btn-close-product-edit');
    const btnCancelEditarProd = document.getElementById('btn-cancel-product-edit');

    if (btncerrarEditProd && modalEditProd) {
        btncerrarEditProd.addEventListener('click', () => modalEditProd.close());
    }
    if (btnCancelEditarProd && modalEditProd) {
        btnCancelEditarProd.addEventListener('click', () => modalEditProd.close());
    }

    /* --- Actualizar producto (PUT vía POST + _method) --- */
    const btnUpdateProd = document.getElementById('btn-PUT-producto-edit');
    if (btnUpdateProd) {
        btnUpdateProd.addEventListener('click', async () => {
            const idProducto = _prodState.idEditar;
            if (!idProducto) {
                mostrarNotificacion("No hay un producto seleccionado para editar", "error");
                return;
            }

            const codIntVal    = document.getElementById('text-codigoInterno-edit').value;
            const nombreVal    = document.getElementById('text-name-edit').value;
            const priceVal     = document.getElementById('number-price-edit').value;
            const categoriaVal = document.getElementById('content-categorias-list-edit').value;

            if (!categoriaVal) {
                mostrarNotificacion("Debe seleccionar una categoría", "error");
                return;
            }

            const inputImagen    = document.getElementById('url-image-edit-prod');
            const archivoImagen  = inputImagen && inputImagen.files ? inputImagen.files[0] : null;

            const radioExtraSeleccionado = document.querySelector('input[name="extra"]:checked');
            const valorExtra             = radioExtraSeleccionado ? radioExtraSeleccionado.value : "0";

            const formData = new FormData();
            formData.append('id_producto', idProducto);
            formData.append('codigo_interno', codIntVal);
            formData.append('nombre', nombreVal);
            formData.append('precio', priceVal);
            formData.append('id_categoria', categoriaVal);
            formData.append('es_extra', valorExtra);
            formData.append('_method', 'PUT');

            if (archivoImagen) formData.append('imagen', archivoImagen);

            const apiProdID = `${apirurlProductos}&id=${idProducto}`;

            try {
                const res = await fetch(apiProdID, { method: 'POST', body: formData });

                const textoRespuesta = await res.text();
                let data;
                try { data = JSON.parse(textoRespuesta); }
                catch (err) {
                    console.error("El servidor no devolvió un JSON válido:", textoRespuesta);
                    throw new Error("Respuesta no válida del servidor");
                }

                if (!res.ok) throw new Error(data.message || "Error en la respuesta del servidor");

                mostrarNotificacion('¡Se ha actualizado el producto con éxito!', 'success');
                if (modalEditProd) modalEditProd.close();
                cargarAPIProducto();
            } catch (error) {
                mostrarNotificacion("Error al intentar actualizar el producto", "error");
                console.error("Detalle técnico:", error);
            }
        });
    }

    /* --- Filtro de búsqueda --- */
    const searchInputProd = document.getElementById('searchInputProd');
    if (searchInputProd) {
        searchInputProd.addEventListener('input', (e) => {
            const texto = e.target.value.toLowerCase().trim();
            _paginaProdActual = 1;

            const filtrados = _prodState.listaProductos.filter(prod => {
                const codigo          = (prod.codigo_interno || "").toLowerCase();
                const nombre          = (prod.nombre || "").toLowerCase();
                const nombreCategoria = (_prodState.objetoCategorias[String(prod.id_categoria)] || "").toLowerCase();

                return codigo.includes(texto) ||
                       nombre.includes(texto) ||
                       nombreCategoria.includes(texto);
            });

            renderizarTabla(filtrados);
        });
    }
    window.registerPoll(cargarAPIProducto, 5000);
}