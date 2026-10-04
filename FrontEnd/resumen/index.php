<!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen</title>
</head>
<body>
<header class="header-body">
    <h1 class="title-text">Resumen</h1>
    <h4>Resumen de actividades</h4>
</header>

    <!--Contenido-->
    <section class="container">
        <div class="header-card-container">
            <div class="icon-image">
                <i class='bx bx-data'></i>
                <span class="titulo">Datos generales</span>
            </div>
        </div>

        <!-- Contenedor exclusivo para las tarjetas de KPI -->
        <div class="kpi-grid">
            <!--Productividad de los colaboradores-->
            <div class="card-header">
                <div class="icon-wrapper">
                    <div class="icon-box"><i class='bx bx-group'></i></div>
                </div>
                <div class="badge negative">
                    <span class="badge-text">-100%</span>
                </div>
                <div class="card-content">
                    <div class="card-title">Productividad de los colaboradores</div>
                    <div class="card-value">L. 12,952.00</div>
                </div>
            </div>
            <!--Promedio de entrega-->
            <div class="card-header">
                <div class="icon-wrapper">
                    <div class="icon-box"><i class='bx bx-time-five'></i></div>
                </div>
                <div class="badge negative">
                    <span class="badge-text">-100%</span>
                </div>
                <div class="card-content">
                    <div class="card-title">Promedio de entrega</div>
                    <div class="card-value">16.56 Min</div>
                </div>
            </div>
            <!--Promedio de venta-->
            <div class="card-header">
                <div class="icon-wrapper">
                    <div class="icon-box"><i class='bx bx-dollar-circle'></i></div>
                </div>
                <div class="badge negative">
                    <span class="badge-text">-100%</span>
                </div>
                <div class="card-content">
                    <div class="card-title">Promedio de venta</div>
                    <div class="card-value">L. 12,952.00</div>
                </div>
            </div>
            <!--Ventas maximas-->
            <div class="card-header">
                <div class="icon-wrapper">
                    <div class="icon-box"><i class='bx bx-trophy'></i></div>
                </div>
                <div class="badge negative">
                    <span class="badge-text">-100%</span>
                </div>
                <div class="card-content">
                    <div class="card-title">Ventas maximas</div>
                    <div class="card-value">L. 12,952.00</div>
                </div>
            </div>
        </div>


    </section>
    <!--Contenido de las graficas-->
    <div class="charts-grid-container">
        
        <section class="container" style="margin-top: 0;">
            <div class="header-card-container">
                <div class="icon-image">
                    <i class='bx bx-line-chart'></i>
                    <span class="titulo">Ventas por empleado</span>
                </div>
            </div>
            <div style="position: relative; width: 100%; height: 280px; margin-top: 15px;">
                <canvas id="ventas_por_empleado"></canvas>
            </div>
        </section>

        <section class="container" style="margin-top: 0;">
            <div class="header-card-container">
                <div class="icon-image">
                    <i class='bx bx-medal'></i>
                    <span class="titulo">Productos mas vendidos</span>
                </div>
            </div>
            <div style="position: relative; width: 100%; height: 280px; margin-top: 15px;">
                <canvas id="productos_mas_vendidos"></canvas>
            </div>
        </section>
    </div>

        <section class="container" style="margin-top: 32;">
            <div class="header-card-container">
                <div class="icon-image">
                    <i class='bx bx-time'></i>
                    <span class="titulo">Ventas por hora</span>
                </div>
            </div>
            <div style="position: relative; width: 100%; height: 280px; margin-top: 32px;">
                <canvas id="ventas_por_hora"></canvas>
            </div>
        </section>
    <div class="charts-grid-container">
            <section class="container" style="margin-top: 0;">
                <div class="header-card-container">
                    <div class="icon-image">
                        <i class='bx bx-pie-chart-alt'></i>
                        <span class="titulo">none</span>
                    </div>
                </div>
                <div style="position: relative; width: 100%; height: 280px; margin-top: 15px;">
                    <canvas id=""></canvas>
                </div>
        </section>
            <section class="container" style="margin-top: 0;">
                <div class="header-card-container">
                    <div class="icon-image">
                        <i class='bx bx-bar-chart-square'></i>
                        <span class="titulo">none</span>
                    </div>
                </div>
                    <div style="position: relative; width: 100%; height: 280px; margin-top: 15px;">
                        <canvas id=""></canvas>
                    </div>
        </section>
    </div>
</body>
</html>