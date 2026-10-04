
window._charts = window._charts || {
    ventasPorEmpleado:   null,
    productosMasVendidos: null,
    ventasPorHora:       null
};


function getCSSVar(name) {
    return getComputedStyle(document.body).getPropertyValue(name).trim();
}


function getThemeColors() {
    const isDark = document.body.classList.contains('dark-mode');
    return {
        gridColor:    isDark ? '#2b2d3b' : '#f0f0f05d',
        tickColor:    isDark ? '#9ca2b8' : '#777474',
        primary:      '#ffae00',
        primaryAlpha: 'rgba(255, 174, 0, 0.4)',
        primaryTrans: 'rgba(255, 174, 0, 0)'
    };
}


function initVentasPorEmpleado(datos = null) {
    const canvasElement = document.getElementById('ventas_por_empleado');
    if (!canvasElement) return;

    // Destruir instancia previa
    if (window._charts.ventasPorEmpleado instanceof Chart) {
        window._charts.ventasPorEmpleado.destroy();
    }

    const ctx         = canvasElement.getContext('2d');
    const colors      = getThemeColors();

    // Datos: usa los pasados por parámetro o el placeholder
    const labels      = datos?.labels || ['Javier O.', 'Ricardo M.', 'Robel S.', 'Debier L.', 'Jonathan P.'];
    const valores     = datos?.valores || [12952, 18400, 9500, 21200, 15300];

    window._charts.ventasPorEmpleado = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ventas (L.)',
                data: valores,
                backgroundColor: 'rgba(255, 174, 0, 0.41)',
                borderColor: colors.primary,
                borderWidth: 1,
                borderRadius: 5,
                maxBarThickness: 52
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: colors.tickColor }
                },
                y: {
                    grid: {
                        color: colors.gridColor,
                        border: { dash: [4, 4] }
                    },
                    ticks: { color: colors.tickColor }
                }
            }
        }
    });
}

function initProductosMasVendidos(datos = null) {
    const canvasElement = document.getElementById('productos_mas_vendidos');
    if (!canvasElement) return;

    if (window._charts.productosMasVendidos instanceof Chart) {
        window._charts.productosMasVendidos.destroy();
    }

    const ctx = canvasElement.getContext('2d');
    const colors = getThemeColors();

    const labels  = datos?.labels || ['Pollo con Tajadas', 'Pollo Frito Estilo Campero', 'Alitas Barbacoa', 'Pollo Crujiente (Crispy)', 'Pechuga a la Plancha'];
    const valores = datos?.valores || [120, 95, 65, 80, 40];

    window._charts.productosMasVendidos = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                label: 'Productos Vendidos',
                data: valores,
                backgroundColor: [
                    'rgba(255, 0, 0, 0.36)',
                    'rgba(255, 157, 59, 0.36)',
                    'rgba(17, 255, 0, 0.36)',
                    'rgba(255, 79, 208, 0.36)',
                    'rgba(0, 230, 211, 0.36)'
                ],
                borderColor: [
                    '#ff0000',
                    '#ff9d3b',
                    '#11ff00',
                    '#ff4fd0',
                    '#00e6d3'
                ],
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '50%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return ` ${label}: ${value} vendidos`;
                        }
                    }
                }
            }
        }
    });
}
function initVentasPorHora(datos = null) {
    const canvasElement = document.getElementById('ventas_por_hora');
    if (!canvasElement) return;

    if (window._charts.ventasPorHora instanceof Chart) {
        window._charts.ventasPorHora.destroy();
    }

    const ctx    = canvasElement.getContext('2d');
    const colors = getThemeColors();

    // Gradiente debajo de la línea
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, colors.primaryAlpha);
    gradient.addColorStop(1, colors.primaryTrans);

    const labels  = datos?.labels || ['00:00 - 01:00', '01:00 - 02:00', '02:00 - 03:00', '03:00 - 04:00', '04:00 - 05:00'];
    const valores = datos?.valores || [12, 4, 8, 16, 1];

    window._charts.ventasPorHora = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Ventas (L.)',
                data: valores,
                backgroundColor: gradient,
                borderColor: '#ff911c',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: colors.tickColor }
                },
                y: {
                    grid: {
                        color: colors.gridColor,
                        border: { dash: [4, 4] }   // ✅ sintaxis correcta
                    },
                    ticks: { color: colors.tickColor }
                }
            }
        }
    });

    // window.registerPoll(() => initVentasPorEmpleado(), 5000);
}