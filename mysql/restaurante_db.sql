-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-09-2026 a las 00:11:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `restaurante_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre_cliente` varchar(100) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `distancia_km` decimal(4,2) NOT NULL DEFAULT 3.50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre_cliente`, `direccion`, `distancia_km`) VALUES
(1, 'Leonardo Tavera', 'Av. Argentina 123', 2.50),
(2, 'María López', 'Urb. Buenos Aires', 4.00),
(3, 'Lucía Gómez 64', 'Nuevo Chimbote Mz. A #495', 4.40),
(4, 'Mateo Silva 83', 'Urb. Bellamar #170', 1.40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id_cupon` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `monto_minimo` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupones`
--

INSERT INTO `cupones` (`id_cupon`, `codigo`, `tipo_descuento`, `valor_descuento`, `monto_minimo`, `activo`, `creado_en`) VALUES
(1, 'CHIMBOTE2026', 'porcentaje', 15.00, 30.00, 1, '2026-09-11 08:43:22'),
(2, 'PROMO50', 'monto_fijo', 10.00, 40.00, 1, '2026-09-11 08:43:22'),
(3, 'INVIERNO2026', 'porcentaje', 9.99, 60.00, 1, '2026-09-11 11:09:50'),
(4, 'HOLA123', 'porcentaje', 5.00, 20.00, 1, '2026-09-11 20:29:13'),
(5, 'VERANO2027', 'porcentaje', 25.00, 120.00, 1, '2026-09-11 20:30:13'),
(9, 'VERANO2028', 'porcentaje', 25.00, 120.00, 1, '2026-09-11 21:55:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_restaurante` int(11) NOT NULL,
  `producto` varchar(150) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `codigo_cupon` varchar(50) DEFAULT NULL,
  `descuento_aplicado` decimal(10,2) DEFAULT 0.00,
  `monto_total` decimal(10,2) NOT NULL,
  `eta_minutos_total` int(11) NOT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_cliente`, `id_restaurante`, `producto`, `subtotal`, `codigo_cupon`, `descuento_aplicado`, `monto_total`, `eta_minutos_total`, `fecha_pedido`) VALUES
(1, 3, 3, 'Salchipapa Mixta Familiar', 55.00, 'PROMO50', 10.00, 45.00, 48, '2026-09-11 10:02:10'),
(2, 1, 1, 'Papas Fritas', 55.00, 'CHIMBOTE2026', 8.25, 46.75, 35, '2026-09-11 10:37:55'),
(3, 1, 1, 'Salchipapa Mixta', 55.00, NULL, 0.00, 45.00, 45, '2026-09-11 11:06:32'),
(4, 3, 5, 'Combinoche', 18.00, NULL, 0.00, 18.00, 37, '2026-09-11 20:31:52'),
(5, 1, 1, 'Salchipapa Mixta', 55.00, NULL, 0.00, 45.00, 45, '2026-09-11 20:53:20'),
(6, 1, 1, 'Salchipapa Mixta', 55.00, NULL, 0.00, 45.00, 45, '2026-09-11 20:53:23'),
(7, 1, 1, 'Prueba', 100.00, 'HOLA123', 5.00, 95.00, 35, '2026-09-11 21:10:02'),
(8, 1, 1, 'Hamburguesa', 100.00, 'HOLA123', 5.00, 95.00, 35, '2026-09-11 21:16:43'),
(9, 1, 1, 'Hamburguesa', 100.00, 'HOLA123', 5.00, 95.00, 35, '2026-09-11 21:17:51'),
(10, 1, 1, 'Hamburguesa', 100.00, 'HOLA123', 5.00, 95.00, 35, '2026-09-11 21:20:02'),
(11, 1, 3, 'Ceviche mixto con chicharron de pota', 55.00, NULL, 0.00, 45.00, 45, '2026-09-11 21:56:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes`
--

CREATE TABLE `restaurantes` (
  `id_restaurante` int(11) NOT NULL,
  `nombre_restaurante` varchar(100) NOT NULL,
  `tiempo_preparacion_base` int(11) NOT NULL DEFAULT 15
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurantes`
--

INSERT INTO `restaurantes` (`id_restaurante`, `nombre_restaurante`, `tiempo_preparacion_base`) VALUES
(1, 'Pizza Mostra', 20),
(3, 'El Pez Dorado 43', 25),
(4, 'Broster King', 28),
(5, 'El Pez Dorado', 14);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id_cupon`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_restaurante` (`id_restaurante`);

--
-- Indices de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD PRIMARY KEY (`id_restaurante`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id_cupon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  MODIFY `id_restaurante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_restaurante`) REFERENCES `restaurantes` (`id_restaurante`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
