<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$pos = stripos($script_path, '/FrontEnd');
$base_url = ($pos !== false) ? substr($script_path, 0, $pos) . '/FrontEnd' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracion</title>
    <!--estilos-->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/AsideStyle.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/resumen.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/productos.css">
    <!--Librerias-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=lightbulb_2" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/catalogo.css">
</head>
<body>
    <div class="menu-btn sidebar-btn" id="sidebar-btn">
        <i class="bx bx-menu"></i>
        <i class="bx bx-x"></i>
    </div>
    <div class="dark-mode-btn" id="dark-mode-btn">
        <i class="bx bx-moon"></i>
        <i class="bx bx-sun"></i>
    </div>
    <div class="sidebar" id="sidebar">
        <div class="header">
            <div class="menu-btn" id="menu-btn">
                <i class='bx bx-chevron-left' ></i>
            </div>
            <div class="brand">
            <img class="brand-light" src="<?php echo $base_url; ?>/assets/Logo.png" alt="logo">
            <img class="brand-dark" src="<?php echo $base_url; ?>/assets/Logo.png" alt="logo">
            <span>MasterCrunch</span>
            </div>
        </div>

        <div class="menu-container">
            <!--
            <div class="search">
                <i class='bx bx-search-alt-2' ></i>
                <input type="search" placeholder="search">
            </div>
            -->
            <ul class="menu">
                <li class="menu-item menu-item-static active">
                    <a href="<?php echo $base_url; ?>/resumen/index.php" class="menu-link" id="resumen">
                        <i class='bx bx-home'></i>
                        <span>Resumen</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Productos/index.php" class="menu-link">
                        <i class='bx bx-food-menu'></i>
                        <span>Productos</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Metas/index.php" class="menu-link">
                        <i class='bx bx-target-lock'></i>
                        <span>Metas</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Catalogo/index.php" class="menu-link">
                        <i class='bx bx-book-content'></i>
                        <span>Pedido</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Personal/index.php" class="menu-link">
                        <i class='bx bxs-briefcase-alt-2' ></i>
                        <span>Personal</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Usuarios/index.php" class="menu-link">
                        <i class='bx bx-user'></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="<?php echo $base_url; ?>/Historial/index.php" class="menu-link">
                        <i class='bx bx-history' ></i>
                        <span>Historial</span>
                    </a>
                </li>
                <!--
               <li class="menu-item menu-item-dropdown">
                    <a href="#" class="menu-link">
                        <i class='bx bx-home-alt' ></i>
                        <span>xd</span>
                        <i class='bx bx-chevron-down' ></i>
                    </a>
                    <ul class="sub-menu">
                        <li href="#" class="sub-menu-link">Configuracion</li>
                        <li href="#" class="sub-menu-link">asereje</li>
                        <li href="#" class="sub-menu-link">De jebe</li>
                        <li href="#" class="sub-menu-link">majabi bugi</li>
                    </ul>
                </li>
                -->
            </ul>
        </div>
        <!--
        <div class="footer">
            <ul class="menu">
                <li class="menu-item menu-item-static">
                    <a href="#" class="menu-link">
                        <i class='bx bx-bell' ></i>
                        <span>Notificaciones</span>
                    </a>
                </li>
                <li class="menu-item menu-item-static">
                    <a href="#" class="menu-link">
                        <i class='bx bxs-cog' ></i>
                        <span>Configuración</span>
                    </a>
                </li>
            </ul>
            <div class="user">
                <div class="user-img">
                    <img src="./assets/profile.png" alt="user">
                </div>
                <div class="user-data">
                    <span class="name">Cajera</span>
                    <span class="rol">Caja</span>
                </div>
                <div class="user-icon">
                    <i class='bx bx-exit' ></i>
                </div>
            </div>
        </div>
        -->
    </div>
    <main id="content-main">
    </main>
    <!-- Principales -->
    <script src="<?php echo $base_url; ?>/main/js/main.js"></script>
    <!-- Rutas de funcionalidad -->
    <script src="<?php echo $base_url; ?>/main/js/router.js"></script>
    <script src="<?php echo $base_url; ?>/main/js/paginacion.js"></script>
    <!-- Componentes -->
    <script src="<?php echo $base_url; ?>/main/js/CargarModal.js"></script>
    <script src="<?php echo $base_url; ?>/main/js/notifications.js"></script>




    <!-- Resumen -->
     <script src="<?php echo $base_url; ?>/resumen/js/charts.js"></script>
    <!-- Productos -->
    <script src="<?php echo $base_url; ?>/Productos/js/categoriasAPI.js"></script>
    <script src="<?php echo $base_url; ?>/Productos/js/ProductosAPI.js"></script>
    <script src="<?php echo $base_url; ?>/Productos/js/ComboAPI.js"></script>
    <!-- Catalogos -->
    <script src="<?php echo $base_url; ?>/Catalogo/js/categoriasCat.js"></script>
    <script src="<?php echo $base_url; ?>/Catalogo/js/catalogoProdAPI.js"></script>
    <script src="<?php echo $base_url; ?>/Catalogo/js/tooglePanel.js"></script>
    <script src="<?php echo $base_url; ?>/Catalogo/js/catalogoCarrito.js"></script>

</body>
</html>


