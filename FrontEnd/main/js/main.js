window.getBaseURL = function () {
    const segments = window.location.pathname.split('/').filter(Boolean);

    // Si no hay segmentos → estamos en la raíz del dominio
    if (segments.length === 0) return '';

    const first = segments[0];

    if (first.includes('.')) return '';

    //el primer segmento es la carpeta del proyecto
    return `/${first}`;
};

window.BASE_URL   = window.getBaseURL();
window.API_BASE   = `${window.BASE_URL}/backEnd/api`;
window.FRONT_BASE = `${window.BASE_URL}/FrontEnd`;

// sidebar y dark mode
const menusItemsDropDown = document.querySelectorAll(".menu-item-dropdown");
const menusItemsStatic   = document.querySelectorAll(".menu-item-static");
const sidebar            = document.getElementById('sidebar');
const menuBtn            = document.getElementById('menu-btn');
const sidebarBtn         = document.getElementById('sidebar-btn');
const darkModeBtn        = document.getElementById('dark-mode-btn');
const allMenuItems       = document.querySelectorAll('.menu-item');

/* --- Marcar item activo--- */
allMenuItems.forEach(menuItem => {
    menuItem.addEventListener('click', () => {
        allMenuItems.forEach(item => item.classList.remove('active'));
        menuItem.classList.add('active');
    });
});

/* --- Dark mode--- */
if (localStorage.getItem('dark-mode') === 'true') {
    document.body.classList.add('dark-mode');
}

if (darkModeBtn) {
    darkModeBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('dark-mode', document.body.classList.contains('dark-mode'));
    });
}

/* --- Sidebar toggle--- */
if (sidebarBtn) {
    sidebarBtn.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-hidden');
    });
}

/* --- Minimizar sidebar-- */
if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('minimize');
    });
}

/* --- Auto expandir al pasar el mouse--- */
if (sidebar) {
    sidebar.addEventListener('mouseenter', () => {
        if (window.innerWidth <= 700) return;
        sidebar.classList.remove('minimize');
    });
    sidebar.addEventListener('mouseleave', () => {
        if (window.innerWidth <= 700) return;
        sidebar.classList.add('minimize');
    });
}

/* --- Inicializar submenús --- */
menusItemsDropDown.forEach(item => {
    const subMenu = item.querySelector('.sub-menu');
    if (subMenu) {
        item.classList.remove('sub-menu-toggle');
        subMenu.style.height  = "0";
        subMenu.style.padding = "0";
    }
});

/* --- Toggle de submenús --- */
menusItemsDropDown.forEach(menuItem => {
    menuItem.addEventListener('click', () => {
        const subMenu  = menuItem.querySelector('.sub-menu');
        const isActive = menuItem.classList.toggle('sub-menu-toggle');

        if (subMenu) {
            if (isActive) {
                subMenu.style.height  = `${subMenu.scrollHeight + 6}px`;
                subMenu.style.padding = "0.2rem 0";
            } else {
                subMenu.style.height  = "0";
                subMenu.style.padding = "0";
            }
        }

        // Cerrar los demás submenús abiertos
        menusItemsDropDown.forEach((item) => {
            if (item !== menuItem) {
                const otherSubmenu = item.querySelector('.sub-menu');
                if (otherSubmenu) {
                    item.classList.remove('sub-menu-toggle');
                    otherSubmenu.style.height  = "0";
                    otherSubmenu.style.padding = "0";
                }
            }
        });
    });
});

/* --- Cerrar submenús-- */
menusItemsStatic.forEach(menuItem => {
    menuItem.addEventListener('mouseenter', () => {
        if (!sidebar.classList.contains('minimize')) return;

        menusItemsDropDown.forEach((item) => {
            if (item === menuItem) return;

            const otherSubmenu = item.querySelector('.sub-menu');
            if (otherSubmenu) {
                item.classList.remove('sub-menu-toggle');
                otherSubmenu.style.height  = "0";
                otherSubmenu.style.padding = "0";
            }
        });
    });
});

/* ---expandir sidebar en pantallas chicas --- */
function checkWindowsSize() {
    if (sidebar && window.innerWidth <= 700) {
        sidebar.classList.remove('minimize');
    }
}
checkWindowsSize();
window.addEventListener('resize', checkWindowsSize);


darkModeBtn?.addEventListener('click', () => {
    // Solo re-renderizar si estamos en resumen
    if (document.getElementById('ventas_por_empleado'))   initVentasPorEmpleado();
    if (document.getElementById('productos_mas_vendidos')) initProductosMasVendidos();
    if (document.getElementById('ventas_por_hora'))       initVentasPorHora();
});


window._pollingTimers = [];

/**
 * Registra una función para que se ejecute periódicamente.
 * @param {Function} fn  - Función de refresco
 * @param {number}   ms  - Intervalo en milisegundos (default 30s)
 * @returns {number}     - ID del intervalo
 */

window.registerPoll = function (fn, ms = 5000) {
    const id = setInterval(() => {
        // Saltar si la pestaña está oculta (no malgastar recursos)
        if (document.hidden) return;

        // Saltar si hay un modal abierto (no interrumpir al usuario)
        if (document.querySelector('dialog[open]')) return;

        // Ejecutar con try/catch por si el endpoint falla
        try {
            fn();
        } catch (err) {
            console.error('Error en auto-refresh:', err);
        }
    }, ms);

    window._pollingTimers.push(id);
    return id;
};

window.clearAllPolls = function () {
    window._pollingTimers.forEach(id => clearInterval(id));
    window._pollingTimers = [];
};