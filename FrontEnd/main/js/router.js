function buildUrl(href) {
    if (!href || href === '#') return null;

    if (href.startsWith('/')) {
        const marker = '/FrontEnd/';
        const idx = href.indexOf(marker);

        if (idx !== -1) {
            const restoDeRuta = href.substring(idx + marker.length);
            return `${window.FRONT_BASE}/${restoDeRuta}`;
        }

        return href;
    }

    return href;
}

function ajax(url) {
    if (!url) return;
    if (window.clearAllPolls) window.clearAllPolls();
    const http = new XMLHttpRequest();

    http.onreadystatechange = function () {
        if (this.readyState !== 4) return;

        if (this.status === 200) {
            document.getElementById('content-main').innerHTML = this.responseText;

            if (url.includes('resumen')) {
                initVentasPorEmpleado();
                initProductosMasVendidos();
                initVentasPorHora();
            } else if (url.includes('Productos')) {
                initCategorias();
                initProductos();
                initCombo();
                initNotifications();
                cargarModales();
            } else if (url.includes('Catalogo')) {
                initCategoriasCatalogo();
                initCatalogoAPI();
                initTooglePanel();
                initToggleBuscador();
                if (typeof initCarrito === 'function') initCarrito();
                if (typeof initCatalogoItems === 'function') initCatalogoItems();
                if (typeof initModalComboCatalogo === 'function') initModalComboCatalogo();
            }
        } else {
            console.error("Error en la petición AJAX. Estado:", this.status, "URL:", url);
        }
    };

    http.open("GET", url, true);
    http.send();
}

const menuLinks = document.querySelectorAll('.menu-link');

menuLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();

        const url = buildUrl(link.getAttribute('href'));

        // Quitar "active" de todos los items del menú
        menuLinks.forEach(L => L.parentElement.classList.remove('active'));

        if (url) {
            ajax(url);
        }
    });
});

window.addEventListener('DOMContentLoaded', () => {
    const defaultLink = document.querySelector('#resumen');

    if (defaultLink) {
        const urlDefault = buildUrl(defaultLink.getAttribute('href'));

        if (urlDefault) {
            defaultLink.parentElement.classList.add('active');
            ajax(urlDefault);
        }
    }
});