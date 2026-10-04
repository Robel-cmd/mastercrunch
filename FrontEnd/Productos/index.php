
<header class="header-body">
    <h1>Administración de productos</h1>
    <h4>Gestiona y organiza tus productos</h4>
</header>
<!-- Categorias -->
<section class="container container-secondary">
    <div class="header-card-container">
        <div class="header-left">
            <div class="icon-image">
                <i class='bx bx-category-alt'></i>
                <span class="titulo">Categorias de productos</span>
            </div>
            <div class="search-general">
                <i class='bx bx-search-alt-2' ></i>
                <input type="search" placeholder="Buscar categoria" id="searchInputCat">
            </div>
        </div>
        <div class="button-general" id="agregar-categoria">
            <a>
                <i class='bx bx-plus' ></i>
                <span>Nueva categoría</span>
            </a>
        </div>
    </div>

<div class="contenedor-categorias" id="contenedor-categorias"></div>

    <div class="paginacion" id="paginacion-categorias"></div>
</section>


<!-- productos -->
<!--Where is the doom eternium, in this time. I need it-->
<section class="container container-secondary">
    <div class="header-card-container">
        <div class="header-left">
            <div class="icon-image">
                <i class='bx bx-package'></i>
                <span class="titulo">Productos</span>
            </div>
            <div class="search-general">
                <i class='bx bx-search-alt-2'></i>
                <input type="search" placeholder="Buscar producto" id="searchInputProd">
            </div>
        </div>


        <div class="button-general" id="agregar-producto">
            <a>
                <i class='bx bx-plus' ></i>
                <span>Nuevo producto</span>
            </a>
        </div>
    </div>

    <div class="table-container" id="table-container">
        <table class="styled-table" id="table-content">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <!-- <th>Descripción</th> -->
                    <th>Precio</th>
                    <th>Categoría</th>
                    <th>Disponible</th>
                    <th>Complemento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="body-container-table"></tbody>
        </table>
    </div>
    <div class="paginacion" id="paginacion-productos"></div>
</section>
<!-- combos -->
<section class="container container-secondary">
<div class="header-card-container">
    <div class="header-left">
        <div class="icon-image">
            <i class='bx bx-gift'></i>
            <span class="titulo">Combos</span>
        </div>
        <div class="search-general">
            <i class='bx bx-search-alt-2'></i>
            <!-- CAMBIADO AQUÍ DE searchInputProd A searchInputCombo -->
            <input type="search" placeholder="Buscar combo" id="searchInputCombo">
        </div>
    </div>

    <div class="button-general" id="agregar-combos">
        <a>
            <i class='bx bx-plus' ></i>
            <span>Nuevo combo</span>
        </a>
    </div>
</div>
<div class="contenedor-categorias" id="contenedor-combos"></div>


    <div class="paginacion" id="paginacion-combos"></div>
</section>


<!-- Modal Agregar categoria-->
 <dialog class="modal" id="modal-categoria">

        <header class="modal-header">
            <h2 class="modal-title">Agregar nueva categoria</h2>
            <button class="modal-close" id="btn-close-category">X</button>
        </header>
        
        <section class="modal-content">
            <div class="datos-modal-producto">
                <div class="datos-modal-producto">
                    <span>Imagen</span>
                    <input type="file" name="" id="url-imagen">
                </div>
                <div class="datos-modal-producto">
                    <span>Nombre</span>
                    <input type="text" name="" id="nombre-categoria" placeholder="Ingrese el nombre de categoria...">
                </div >
                <div class="datos-modal-producto">
                    <span>Descripción</span>
                    <input type="text" name="" id="descripcion-categoria" placeholder="Ingrese la descripcion de la categoria...">
                </div >
            </div>
            <div class="contenido-botones-modal">
                <button class="emergente-btn" id="btn-cancel-category">Cancelar</button>
                <button class="confirm-btn" id="btn-POST-category">Confirmar categoria</button>
            </div>
        </section>
 </dialog>
 <!-- Modal editar categoria-->
<dialog class="modal" id="modal-categoria-editar">
    <header class="modal-header">
        <h2 class="modal-title">Editar categoria</h2>
        <button class="modal-close" id="btn-close-category-editar">X</button>
    </header>
    <section class="modal-content">
        <div class="datos-modal-producto">
            <div class="datos-modal-producto">
                <span>Imagen</span>
                <input type="file" id="url-imagen-editar">
            </div>
            <div class="datos-modal-producto">
                <span>Nombre</span>
                <input type="text" id="nombre-categoria-editar" placeholder="Ingrese el nombre de categoria...">
            </div>
            <div class="datos-modal-producto">
                <span>Descripción</span>
                <input type="text" id="descripcion-categoria-editar" placeholder="Ingrese la descripcion de la categoria...">
            </div>
        </div>
        <div class="contenido-botones-modal">
            <button class="emergente-btn" id="btn-cancel-category-editar">Cancelar</button>
            <button class="confirm-btn" id="btn-PUT-category">Confirmar cambios</button>
        </div>
    </section>
</dialog>

 <!-- Modal Agregar combos -->
<dialog class="modal" id="modal-combos">

    <header class="modal-header">
        <h2 class="modal-title">Agregar nuevo combo</h2>
        <button class="modal-close" id="btn-close-combos">X</button>
    </header>
    
    <section class="modal-content">
        <div class="datos-modal-producto">
            <div class="datos-modal-producto">
                <span>Nombre</span>
                <input type="text" name="" id="nombreCombo"  placeholder="Ejemplo: Combo familiar" accept="image/*">
            </div >
            <div class="datos-modal-producto">
                <span>Imagen</span>
                <input type="file" name="" id="url-imagen-combo">
            </div>
            <div class="datos-modal-producto">
                <span>Descripcion</span>
                <input type="text" name="" id="desc-combo"  placeholder="Ejemplo: Comidas y bebidas">
            </div >
            <div class="datos-modal-producto">
                <span>Precio total</span>
                <input type="number" name="" id="precio-total-combo" min=0  placeholder="Ejemplo: 50.99">
                <center><span class="total-combo" id="precio-total-prod">Total: 00.00 Lps</span></center>
            </div>
            <div class="datos-modal-producto">
                <fieldset style="padding: 16px; color: gray;">
                    <legend>Agregar productos al combo</legend>
                    
                        <div style="margin-bottom: 10px;">
                            <span>Filtrar producto</span>
                            <input type="text" id="filtro-productos-modal" placeholder="Escribe para buscar producto..." style="width: 100%; padding: 6px; box-sizing: border-box;">
                        </div>

                    <div>
                        <span>Producto</span>
                        <select name="productos" id="content-list-product">
                            <!-- Se cargaran los productos desde la API -->
                        </select>
                    </div>
                    <div>
                        <span>Cantidad total</span>
                        <input type="number" name="" id="cant-prod-cmb" min=1 max=9999 placeholder="Ejemplo: 2" value="1">
                    </div>
                    <button class="confirm-btn" id="meter-datos-prod-cmb">Agregar producto</button>
                </fieldset>
                
                
                <table class="styled-table">
                    <tr>
                        <th>Producto</th> 
                        <th>cantidad</th> 
                        <th>precio unitario</th>
                        <th>precio total</th>
                        <th>Acciones</th>
                    </tr>
                    <tbody id="content-prod-list">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="contenido-botones-modal">
            <button class="emergente-btn" id="btn-cancel-combos">Cancelar</button>
            <button class="confirm-btn" id="AgregarComboNuevo">Confirmar producto</button>
        </div>
    </section>
</dialog>
 <!-- Modal editar combos -->
<dialog class="modal" id="modal-combos-edit">

    <header class="modal-header">
        <h2 class="modal-title">editar combo</h2>
        <button class="modal-close" id="btn-close-combos-edit">X</button>
    </header>
    
    <section class="modal-content">
        <div class="datos-modal-producto">
            <div class="datos-modal-producto">
                <span>Nombre</span>
                <input type="text" name="" id="nombreCombo-edit"  placeholder="Ejemplo: Combo familiar">
            </div >
            <div class="datos-modal-producto">
                <span>Imagen</span>
                <input type="file" id="url-imagen-combo-edit" accept="image/*">
            </div>
            <div class="datos-modal-producto">
                <span>Descripcion</span>
                <input type="text" name="" id="desc-combo-edit"  placeholder="Ejemplo: Comidas y bebidas">
            </div >
            <div class="datos-modal-producto">
                <span>Precio total</span>
                <input type="number" name="" id="precio-total-combo-edit" min=0  placeholder="Ejemplo: 50.99">
                <center><span class="total-combo" id="precio-total-prod-edit">Total: 00.00 Lps</span></center>
            </div>
            <div class="datos-modal-producto">
                <fieldset style="padding: 16px; color: gray;">
                    <legend>Agregar productos al combo</legend>
                    
                        <div style="margin-bottom: 10px;">
                            <span>Filtrar producto</span>
                            <input type="text" id="filtro-productos-modal-edit" placeholder="Escribe para buscar producto..." style="width: 100%; padding: 6px; box-sizing: border-box;">
                        </div>

                    <div>
                        <span>Producto</span>
                        <select name="productos" id="content-list-product-edit">
                            <!-- Se cargaran los productos desde la API -->
                        </select>
                    </div>
                    <div>
                        <span>Cantidad total</span>
                        <input type="number" name="" id="cant-prod-cmb-edit" min=1 max=9999 placeholder="Ejemplo: 2" value="1">
                    </div>
                    <button class="confirm-btn" id="meter-datos-prod-cmb-edit">Agregar producto</button>
                </fieldset>
                
                
                <table class="styled-table">
                    <tr>
                        <th>Producto</th> 
                        <th>cantidad</th> 
                        <th>precio unitario</th>
                        <th>precio total</th>
                        <th>Acciones</th>
                    </tr>
                    <tbody id="content-prod-list-edit">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="contenido-botones-modal">
            <button class="emergente-btn" id="btn-cancel-combos-edit">Cancelar</button>
            <button class="confirm-btn" id="btn-PUT-combos-edit">Confirmar producto</button>
        </div>
    </section>
</dialog>




<!-- APARTADO DE vista de imagen -->
<dialog class="modal" id="modalViewImg">
    <header class="modal-header">
        <h2 class="modal-title">Vista previa de imagen</h2>
    </header>
    <section class="contentPreviewImage">
        <img id="img-preview-content" src="" alt="Vista previa" class="imagePreview">
    </section>
    <div class="contenido-botones-modal">
        <button class="confirm-btn" id="btn-cancel-viewImg">Regresar</button>
    </div>
</dialog>

<!-- APARTADO DE MODALES DE ADVERTENCIA -->
<!-- APARTADO DE MODALES DE ADVERTENCIA -->
<!-- APARTADO DE MODALES DE ADVERTENCIA -->

<!--CONFIRMACION-->
<dialog class="modal-priority" id="success-modal">
    <header class="check-tittle OK">
        <div class="conteiner-icon OK">
            <i class='bx bx-check'></i>
        </div>
        
    </header>
    <section class="content-priority">
        <p class="description-priority">
            ¡Se ha realizado la acción con exito!
        </p>
    </section>
</dialog>
<!-- ADVERTENCIA CAT-->
<dialog class="warning-modal" id="warning-modal-desactivar">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            ¿Esta seguro que quiere realizar esta acción?
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-desactivar">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-desactivar">Si, continuar</button>
            
        </div>
    </section>
</dialog>
<!-- ADVERTENCIA COMBO-->
<dialog class="warning-modal" id="warning-modal-desactivar-combo">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            ¿Esta seguro que quiere realizar esta acción?
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-desactivar-combo">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-desactivar-combo">Si, continuar</button>
        </div>
    </section>
</dialog>

<!-- Borrar COMBO-->
<dialog class="warning-modal" id="warning-modal-borrar-combo">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            <strong>¿Está seguro que quiere eliminar este combo?</strong><br>
            Esta acción no podrá revertirse
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-eliminar-combo">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-eliminar-combo">Sí, continuar</button>
        </div>
    </section>
</dialog>
<!-- Borrar CAT-->
<dialog class="warning-modal" id="warning-modal-borrar">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            <strong>¿Está seguro que quiere realizar esta acción?</strong><br>
            Esta accion no podra revertirse
        </p>
        <div>
            <button class="cancelar-desactivar" id="cancelar-eliminar">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-eliminar">Sí, continuar</button>
        </div>
    </section>
</dialog>
<!-- Modal Agregar Producto-->
 <dialog class="modal" id="modal-producto">

        <header class="modal-header">
            <h2 class="modal-title">Agregar nuevo producto</h2>
            <button class="modal-close" id="btn-close-product">X</button>
        </header>
        
        <section class="modal-content">
            <div class="datos-modal-producto">
                <div>
                    <span>Codigo interno</span>
                    <input type="text" name="" id="text-codigoInterno" placeholder="Ejemplo: B012004">
                </div>
                <div class="datos-modal-producto">
                    <span>Imagen</span>
                    <input type="file" name="" id="url-image">
                </div>
                <div class="datos-modal-producto">
                    <span>Nombre</span>
                    <input type="text" name="" id="text-name" placeholder="Ejemplo: Pollo frito">
                </div >
                <div class="datos-modal-producto">
                    <span>Precio</span>
                    <input type="number" name="" id="number-price"  placeholder="Ejemplo: 50.99">
                </div>
                <div class="datos-modal-producto">
                    <span>Meta diaria</span>
                    <input type="number" name="META" id="number-meta"  placeholder="Ejemplo: 500" disabled>
                    <center><p style="color: red;">No disponible actualmente</p></center>
                </div>
                <div class="datos-modal-producto">
                    <span>Categoria</span>
                    <select name="categoria" id="content-categorias-list">
                        <!-- AQUI IRAN LOS DATOS DE CATEGORIAS -->
                    </select>
                </div>
                <div class="datos-modal-producto">
                    <fieldset>
                        <legend>¿Este producto es un extra?</legend>
                        <div>
                            <input type="radio" name="extraA" value="1">
                            <label for="extra-si">Si</label>
                        </div>
                        <div>
                            <input type="radio" name="extraA" value="0" checked>
                            <label for="extra-no">No</label>
                        </div>
                    </fieldset>

                </div>
            </div>

            <div class="contenido-botones-modal">
                <button class="emergente-btn" id="btn-cancel-product">Cancelar</button>
                <button class="confirm-btn" id="btn-POST-producto">Confirmar producto</button>
            </div>
        </section>
 </dialog>
<!-- Borrar PROD-->
<dialog class="warning-modal" id="warning-modal-borrar-P">
    <header class="check-tittle warning">
        <div class="conteiner-icon warning">
            <i class='bx bx-error'></i>
        </div>
    </header>
    <section class="content-priority">
        <p class="description-priority">
            <strong>¿Está seguro que quiere realizar esta acción?</strong>
        </p>
        <br>
        <p>
            Esta accion no podra revertirse
        </p>
            
        
        <div>
            <button class="cancelar-desactivar" id="cancelar-eliminar-P">No, regresar</button>
            <button class="confirmar-desactivar" id="confirmar-eliminar-P">Sí, continuar</button>
        </div>
    </section>
</dialog>
<!-- Editar PROD-->
 <dialog class="modal" id="modal-producto-edit">

        <header class="modal-header">
            <h2 class="modal-title">Editar producto</h2>
            <button class="modal-close" id="btn-close-product-edit">X</button>
        </header>
        
        <section class="modal-content">
            <div class="datos-modal-producto">
                <div>
                    <span>Codigo interno</span>
                    <input type="text" name="" id="text-codigoInterno-edit" placeholder="Ejemplo: B012004">
                </div>
                <div class="datos-modal-producto">
                    <span>Imagen</span>
                    <input type="file" name="" id="url-image-edit-prod">
                </div>
                <div class="datos-modal-producto">
                    <span>Nombre</span>
                    <input type="text" name="" id="text-name-edit" placeholder="Ejemplo: Pollo frito">
                </div >
                <div class="datos-modal-producto">
                    <span>Precio</span>
                    <input type="number" name="" id="number-price-edit"  placeholder="Ejemplo: 50.99">
                </div>
                <div class="datos-modal-producto">
                    <span>Meta diaria</span>
                    <input type="number" name="META" id="number-meta"  placeholder="Ejemplo: 500" disabled>
                    <center><p style="color: red;">No disponible actualmente</p></center>
                </div>
                <div class="datos-modal-producto">
                    <span>Categoria</span>
                    <select name="categoria" id="content-categorias-list-edit">
                        <!-- AQUI IRAN LOS DATOS DE CATEGORIAS -->
                    </select>
                </div>
                <div class="datos-modal-producto">
                    <fieldset>
                        <legend>¿Este producto es un extra?</legend>
                        <div>
                            <input type="radio" name="extra" value="1" id="checked-si-rad">
                            <label for="extra-si">Si</label>
                        </div>
                        <div>
                            <input type="radio" name="extra" value="0" id="checked-no-rad">
                            <label for="extra-no">No</label>
                        </div>
                    </fieldset>

                </div>
            </div>

            <div class="contenido-botones-modal">
                <button class="emergente-btn" id="btn-cancel-product-edit">Cancelar</button>
                <button class="confirm-btn" id="btn-PUT-producto-edit">Confirmar producto</button>
            </div>
        </section>
 </dialog>
<!-- Poput -->
<div class="notification" id="notificaion-poput" popover="manual" role="status" aria-live="polite"></div>