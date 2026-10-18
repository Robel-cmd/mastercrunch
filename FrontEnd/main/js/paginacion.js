// Helper de paginación reutilizable
// container: elemento .paginacion donde se renderiza
// paginaActual: página visible (1-indexada)
// totalItems: cantidad total de elementos (ya filtrados)
// porPagina: elementos por página
// onChange(nuevaPagina): callback al cambiar de página
// Calcula cuántos elementos caben en el contenedor según su ancho real.
// contenedor: elemento DOM donde se listan los ítems
// max: máximo de elementos por página
// anchoItem: ancho aproximado de cada tarjeta (incluye gap)
function calcularPorPagina(contenedor, max, anchoItem) {
    const ancho = (contenedor && contenedor.clientWidth > 0)
        ? contenedor.clientWidth
        : window.innerWidth;

    // Si ya hay tarjetas renderizadas, usar el ancho real de la primera
    let anchoReal = anchoItem;
    const primera = contenedor ? contenedor.firstElementChild : null;
    if (primera && primera.offsetWidth > 0) {
        anchoReal = primera.offsetWidth + 20; // + gap
    }

    return Math.max(1, Math.min(max, Math.floor(ancho / anchoReal)));
}

function renderPaginacion(container, paginaActual, totalItems, porPagina, onChange) {
    if (!container) return 1;

    const totalPaginas = Math.max(1, Math.ceil(totalItems / porPagina));

    // Una sola página: no mostrar controles
    if (totalPaginas <= 1) {
        container.innerHTML = '';
        return 1;
    }

    let html = '';

    html += `<button data-page="${paginaActual - 1}" ${paginaActual <= 1 ? 'disabled' : ''}>&lt;</button>`;

    // Ventana compacta: primera, última y las vecinas a la actual.
    // Los saltos se muestran como "…" (evita desbordes en pantallas pequeñas).
    const paginas = new Set([1, totalPaginas, paginaActual, paginaActual - 1, paginaActual + 1]);
    if (paginaActual <= 3) {
        paginas.add(2); paginas.add(3); paginas.add(4);
    }
    if (paginaActual >= totalPaginas - 2) {
        paginas.add(totalPaginas - 1); paginas.add(totalPaginas - 2); paginas.add(totalPaginas - 3);
    }
    const lista = [...paginas]
        .filter(p => p >= 1 && p <= totalPaginas)
        .sort((a, b) => a - b);

    let anterior = 0;
    for (const p of lista) {
        if (p - anterior === 2) {
            // Solo falta una página entre ambos: mejor mostrarla que un "…"
            const falta = anterior + 1;
            html += `<span data-page="${falta}" style="cursor:pointer;">${falta}</span>`;
        } else if (p - anterior > 2) {
            html += `<span class="paginacion-ellipsis" aria-hidden="true">…</span>`;
        }
        html += `<span data-page="${p}" class="${p === paginaActual ? 'active' : ''}" style="cursor:pointer;">${p}</span>`;
        anterior = p;
    }

    html += `<button data-page="${paginaActual + 1}" ${paginaActual >= totalPaginas ? 'disabled' : ''}>&gt;</button>`;

    container.innerHTML = html;

    container.querySelectorAll('[data-page]').forEach(el => {
        el.addEventListener('click', () => {
            const p = parseInt(el.dataset.page, 10);
            if (p >= 1 && p <= totalPaginas && p !== paginaActual) {
                onChange(p);
            }
        });
    });

    return totalPaginas;
}
