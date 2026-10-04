-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-10-2026 a las 18:32:05
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `master_crunch_db`
CREATE DATABASE IF NOT EXISTS master_crunch_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE master_crunch_db;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria`
--
      
-- INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`, `activo`, `imagen`) VALUES
-- (1, 'Bebidas', 'Bebidas frías y calientes', 1, 'uploads/categorias/cat_6ab052645e72a.webp'),
-- (2, 'Comidas', 'comidas pollo', 1, 'uploads/categorias/cat_6ab05ca841570.jpeg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combo`
--

CREATE TABLE `combo` (
  `id_combo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `url_imagen_combo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combo`
--

-- INSERT INTO `combo` (`id_combo`, `nombre`, `descripcion`, `precio_total`, `url_imagen_combo`, `activo`) VALUES
-- (1, 'Combo Ceibeño ', 'nuevo', 230.00, 'uploads/combos/combo_6ab052dc7fbad.png', 1),
-- (2, 'Combo Burras ', 'burras', 200.00, 'uploads/combos/combo_6ab062279b58e.jpeg', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combo_detalle`
--

CREATE TABLE `combo_detalle` (
  `id_combo_detalle` int(11) NOT NULL,
  `id_combo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_individual` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combo_detalle`
--

-- INSERT INTO `combo_detalle` (`id_combo_detalle`, `id_combo`, `id_producto`, `cantidad`, `precio_individual`) VALUES
-- (1, 1, 1, 1, 230.00),
-- (5, 2, 3, 1, 50.00),
-- (6, 2, 2, 1, 50.00),
-- (7, 2, 1, 2, 50.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `numero_telefono` varchar(20) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `fecha_contratacion` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleados`
--

-- INSERT INTO `empleados` (`id_empleado`, `nombre`, `apellido`, `numero_telefono`, `usuario`, `contrasena_hash`, `id_rol`, `fecha_contratacion`, `activo`, `ultimo_acceso`) VALUES
-- (1, 'Carlos', 'Mendoza', '9999-8888', 'cmendoza', '$2y$10$eImiTXuWVxfM37uY4JANjOL.81c81v.8', 1, '2026-01-15', 1, '2026-09-26 19:20:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL,
  `numero_factura` varchar(30) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_empleado` int(11) NOT NULL,
  `id_metodo_pago` int(11) NOT NULL,
  `fecha_hora_factura` datetime NOT NULL DEFAULT current_timestamp(),
  `estado_factura` varchar(30) NOT NULL DEFAULT 'emitida',
  `rtn_cliente` varchar(20) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_detalle`
--

CREATE TABLE `factura_detalle` (
  `id_factura_detalle` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metas_diarias`
--

CREATE TABLE `metas_diarias` (
  `id_meta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cantidad_meta` int(11) NOT NULL,
  `ventas_reales` int(11) NOT NULL DEFAULT 0,
  `porc_comparacion` decimal(10,2) NOT NULL,
  `estadistica` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metas_diarias`
--

-- INSERT INTO `metas_diarias` (`id_meta`, `id_producto`, `fecha`, `cantidad_meta`, `ventas_reales`, `porc_comparacion`, `estadistica`) VALUES
-- (2, 1, '2026-09-20', 50, 20, 40.00, 'Insuficiente'),
-- (3, 2, '2026-09-20', 30, 1, 3.33, 'Critico'),
-- (4, 3, '2026-09-20', 0, 0, 0.00, 'Sin ventas/Nulo'),
-- (5, 1, '2026-09-21', 50, 20, 40.00, 'Insuficiente'),
-- (6, 2, '2026-09-21', 20, 23, 115.00, 'Extraordinario'),
-- (7, 3, '2026-09-21', 32, 61, 190.63, 'Extraordinario'),
-- (8, 1, '2026-09-22', 20, 20, 100.00, 'Excelente'),
-- (9, 2, '2026-09-22', 30, 23, 76.67, 'Muy bueno'),
-- (10, 3, '2026-09-22', 24, 31, 129.17, 'Extraordinario'),
-- (11, 1, '2026-09-26', 5, 0, 0.00, 'Sin ventas/Nulo'),
-- (12, 2, '2026-09-26', 6, 0, 0.00, 'Sin ventas/Nulo'),
-- (13, 3, '2026-09-26', 14, 0, 0.00, 'Sin ventas/Nulo'),
-- (14, 1, '2026-09-27', 5, 0, 0.00, 'Sin ventas/Nulo'),
-- (15, 2, '2026-09-27', 6, 0, 0.00, 'Sin ventas/Nulo'),
-- (16, 3, '2026-09-27', 7, 0, 0.00, 'Sin ventas/Nulo'),
-- (17, 1, '2026-09-28', 5, 0, 0.00, 'Sin ventas/Nulo'),
-- (18, 2, '2026-09-28', 6, 0, 0.00, 'Sin ventas/Nulo'),
-- (19, 3, '2026-09-28', 7, 0, 0.00, 'Sin ventas/Nulo'),
-- (20, 1, '2026-09-29', 5, 0, 0.00, 'Sin ventas/Nulo'),
-- (21, 2, '2026-09-29', 6, 0, 0.00, 'Sin ventas/Nulo'),
-- (22, 3, '2026-09-29', 7, 0, 0.00, 'Sin ventas/Nulo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodo_pago`
--

CREATE TABLE `metodo_pago` (
  `id_metodo_pago` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `fecha_hora_pedido` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_hora_entrega` datetime DEFAULT NULL,
  `cliente` varchar(100) NOT NULL DEFAULT 'Cliente no especificado',
  `id_empleado` int(11) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'pendiente',
  `tipo_pedido` varchar(30) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

-- INSERT INTO `pedidos` (`id_pedido`, `fecha_hora_pedido`, `fecha_hora_entrega`, `cliente`, `id_empleado`, `estado`, `tipo_pedido`, `observaciones`) VALUES
-- (1, '2026-09-21 10:00:00', '2026-09-21 10:30:00', 'Juan Pérez', 1, 'entregado', 'Para llevar', 'Sin cebolla en el arroz'),
-- (2, '2026-09-21 11:15:00', '2026-09-21 11:45:00', 'Maria López', 1, 'entregado', 'Comer aquí', 'Mesa 4'),
-- (3, '2026-09-21 12:00:00', '2026-09-21 12:40:00', 'Carlos Gómez', 1, 'entregado', 'Domicilio', 'Entregar en el bloque B'),
-- (4, '2026-09-21 13:30:00', '2026-09-21 14:00:00', 'Ana Rodríguez', 1, 'entregado', 'Para llevar', 'Salsa extra'),
-- (5, '2026-09-21 14:10:00', '2026-09-21 14:35:00', 'Roberto Suazo', 1, 'entregado', 'Comer aquí', 'Mesa 1'),
-- (6, '2026-09-26 15:00:00', '2026-09-26 15:25:00', 'Laura Fernández', 1, 'entregado', 'Para llevar', 'Sin picante'),
-- (7, '2026-09-27 15:00:00', '2026-09-27 15:25:00', 'Laura Fernández', 1, 'ENTREGADO', 'Para llevar', 'Sin picante'),
-- (8, '2026-09-28 12:30:00', '2026-09-28 12:55:00', 'Laura Fernández', 1, 'entregado', 'Para llevar', 'Sin picante'),
-- (9, '2026-09-28 12:30:00', '2026-09-28 12:55:00', 'Laura Fernández', 1, 'entregado', 'Para llevar', 'Sin picante'),
-- (10, '2026-09-29 12:30:00', '2026-09-29 12:55:00', 'Laura Fernández', 1, 'entregado', 'Para llevar', 'Sin picante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_detalle`
--

CREATE TABLE `pedidos_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_combo` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos_detalle`
-- --

-- INSERT INTO `pedidos_detalle` (`id_detalle`, `id_pedido`, `id_producto`, `id_combo`, `cantidad`, `precio_unitario`) VALUES
-- (1, 1, 1, NULL, 1, 250.00),
-- (2, 1, 3, NULL, 1, 25.00),
-- (3, 2, 2, NULL, 2, 120.00),
-- (4, 3, NULL, 1, 1, 230.00),
-- (5, 4, NULL, 2, 1, 150.00),
-- (6, 4, 3, NULL, 2, 25.00),
-- (7, 5, 1, NULL, 1, 250.00),
-- (8, 5, 2, NULL, 1, 120.00),
-- (9, 6, 2, NULL, 1, 120.00),
-- (10, 6, 3, NULL, 1, 25.00),
-- (11, 7, 2, NULL, 6, 120.00),
-- (12, 7, 1, NULL, 4, 230.00),
-- (13, 7, 3, 1, 12, 25.00),
-- (14, 8, NULL, 1, 2, 120.00),
-- (15, 8, NULL, 1, 1, 25.00),
-- (16, 10, 1, 2, 1, 120.00),
-- (17, 10, 1, 2, 1, 25.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `codigo_interno` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `url_imagen` varchar(255) DEFAULT NULL,
  `disponibilidad` tinyint(1) NOT NULL DEFAULT 1,
  `es_extra` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

-- INSERT INTO `productos` (`id_producto`, `codigo_interno`, `nombre`, `precio`, `id_categoria`, `url_imagen`, `disponibilidad`, `es_extra`, `fecha_creacion`) VALUES
-- (1, '0001', 'Arros Chino', 250.00, 1, 'uploads/productos/prod_6ab052afd31f1.webp', 1, 0, '2026-09-20 15:39:59'),
-- (2, '0002', 'Pollo chuco', 120.00, 1, 'uploads/productos/prod_6ab059cc3d7dd.webp', 1, 0, '2026-09-20 16:10:20'),
-- (3, '0003', 'Papas Fritas', 25.00, 2, 'uploads/productos/prod_6ab059f13fe4a.webp', 1, 0, '2026-09-20 16:10:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(50) DEFAULT NULL,
  `nivel_acceso` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

-- INSERT INTO `roles` (`id_rol`, `nombre`, `descripcion`, `nivel_acceso`) VALUES
-- (1, 'Administrador', 'Acceso total al sistema', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `combo`
--
ALTER TABLE `combo`
  ADD PRIMARY KEY (`id_combo`);

--
-- Indices de la tabla `combo_detalle`
--
ALTER TABLE `combo_detalle`
  ADD PRIMARY KEY (`id_combo_detalle`),
  ADD KEY `fk_combodet_combo` (`id_combo`),
  ADD KEY `fk_combodet_producto` (`id_producto`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_empleados_roles` (`id_rol`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `fk_facturas_pedido` (`id_pedido`),
  ADD KEY `fk_facturas_empleado` (`id_empleado`),
  ADD KEY `fk_facturas_metodopago` (`id_metodo_pago`);

--
-- Indices de la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  ADD PRIMARY KEY (`id_factura_detalle`),
  ADD KEY `fk_facturadet_factura` (`id_factura`),
  ADD KEY `fk_facturadet_producto` (`id_producto`);

--
-- Indices de la tabla `metas_diarias`
--
ALTER TABLE `metas_diarias`
  ADD PRIMARY KEY (`id_meta`),
  ADD KEY `fk_metasdiarias_producto` (`id_producto`);

--
-- Indices de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  ADD PRIMARY KEY (`id_metodo_pago`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedidos_empleados` (`id_empleado`);

--
-- Indices de la tabla `pedidos_detalle`
--
ALTER TABLE `pedidos_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_pedidosdet_pedido` (`id_pedido`),
  ADD KEY `fk_pedidosdet_combo` (`id_combo`),
  ADD KEY `fk_pedidosdet_producto` (`id_producto`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_productos_categoria` (`id_categoria`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `combo`
--
ALTER TABLE `combo`
  MODIFY `id_combo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `combo_detalle`
--
ALTER TABLE `combo_detalle`
  MODIFY `id_combo_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  MODIFY `id_factura_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metas_diarias`
--
ALTER TABLE `metas_diarias`
  MODIFY `id_meta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `metodo_pago`
--
ALTER TABLE `metodo_pago`
  MODIFY `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pedidos_detalle`
--
ALTER TABLE `pedidos_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `combo_detalle`
--
ALTER TABLE `combo_detalle`
  ADD CONSTRAINT `fk_combodet_combo` FOREIGN KEY (`id_combo`) REFERENCES `combo` (`id_combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_combodet_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `fk_empleados_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_facturas_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facturas_metodopago` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodo_pago` (`id_metodo_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facturas_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `factura_detalle`
--
ALTER TABLE `factura_detalle`
  ADD CONSTRAINT `fk_facturadet_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_facturadet_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `metas_diarias`
--
ALTER TABLE `metas_diarias`
  ADD CONSTRAINT `fk_metasdiarias_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_empleados` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos_detalle`
--
ALTER TABLE `pedidos_detalle`
  ADD CONSTRAINT `fk_pedidosdet_combo` FOREIGN KEY (`id_combo`) REFERENCES `combo` (`id_combo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidosdet_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidosdet_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

