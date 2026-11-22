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

/* --- Avatar: fallback con iniciales si la imagen no carga --- */
const avatarImg = document.querySelector('.user-img img');
const avatarBox = document.querySelector('.user-img');

if (avatarImg && avatarBox) {
    const showAvatarFallback = () => avatarBox.classList.add('avatar-error');

    avatarImg.addEventListener('error', showAvatarFallback);
    if (avatarImg.complete && avatarImg.naturalWidth === 0) {
        showAvatarFallback();
    }
}

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

/* --- Overlay del sidebar en pantallas pequeñas --- */
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

function setSidebarOpen(open) {
    document.body.classList.toggle('sidebar-hidden', open);
    if (sidebarBtn) sidebarBtn.setAttribute('aria-expanded', String(open));
}

if (sidebarBtn) {
    sidebarBtn.addEventListener('click', () => {
        setSidebarOpen(!document.body.classList.contains('sidebar-hidden'));
    });

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-hidden')) {
            setSidebarOpen(false);
            sidebarBtn.focus();
        }
    });
}

document.querySelectorAll('.sidebar .menu-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900 || window.innerHeight <= 683) {
            setSidebarOpen(false);
        }
    });
});

/* --- Minimizar sidebar (control explícito) --- */
if (menuBtn && sidebar) {
    const toggleSidebar = () => sidebar.classList.toggle('minimize');

    menuBtn.addEventListener('click', toggleSidebar);
    menuBtn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleSidebar();
        }
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

/* --- Expandir sidebar y cerrar overlay según el viewport --- */
function checkWindowsSize() {
    const isSmall = window.innerWidth <= 900 || window.innerHeight <= 683;

    if (sidebar && isSmall) {
        sidebar.classList.remove('minimize');
    }
    if (!isSmall) {
        setSidebarOpen(false);
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

window.registerPoll = function (fn, ms = 5000, opts = {}) {
    const id = setInterval(() => {
        // Saltar si la pestaña está oculta (no malgastar recursos)
        if (document.hidden) return;

        // Saltar si hay un modal abierto (no interrumpir al usuario),
        // salvo que el poll lo desactive explícitamente
        if (!opts.ignoreModals && document.querySelector('dialog[open]')) return;

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