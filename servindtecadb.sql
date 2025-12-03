-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-12-2025 a las 16:35:40
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
-- Base de datos: `servindtecadb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `codigo_producto` varchar(30) NOT NULL,
  `fecha_compra` date NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra_unitario` decimal(11,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_compra`, `codigo_producto`, `fecha_compra`, `cantidad`, `precio_compra_unitario`) VALUES
(9, '123', '2025-11-25', 50, 5000.00),
(10, '45', '2025-11-25', 50, 10000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(11,2) NOT NULL,
  `codigo_producto` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `cantidad`, `precio_unitario`, `codigo_producto`) VALUES
(21, 20, 1, 1000.00, '123'),
(22, 21, 1, 1000.00, '123'),
(23, 22, 5, 400000.00, '45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rif` varchar(20) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `rif`, `fecha_registro`) VALUES
(1, 'HierrosMaterialesNC', 'J011252002', '2025-06-29 22:07:45'),
(4, 'Power Colour', '1235456', '2025-06-30 01:11:32'),
(7, 'Inmobiliaria la Asunsion', 'J-5asd4asd4', '2025-06-30 01:24:19'),
(9, 'Heladeria Kreisel Supra', '8514218', '2025-06-30 02:33:58'),
(12, 'iujo barquisimeto', 'j-123912', '2025-11-06 15:18:21'),
(13, 'natulac', '458774', '2025-11-06 18:27:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinas`
--

CREATE TABLE `maquinas` (
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL,
  `precio_venta` decimal(11,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `maquinas`
--

INSERT INTO `maquinas` (`nombre`, `codigo`, `modelo`, `descripcion`, `stock`, `precio_venta`) VALUES
('inkjet', '123', 'Epson L5590', 'Impresora de tinta continua', 50, 1000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `codigo_unificado` varchar(30) NOT NULL,
  `tipo_producto` varchar(10) NOT NULL COMMENT 'Indica si es "maquina" o "repuesto"',
  `codigo_repuesto` varchar(30) DEFAULT NULL,
  `codigo_maquina` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`codigo_unificado`, `tipo_producto`, `codigo_repuesto`, `codigo_maquina`) VALUES
('123', 'maquina', NULL, NULL),
('123525', 'maquina', NULL, NULL),
('4152', 'repuesto', NULL, NULL),
('45', 'repuesto', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

CREATE TABLE `repuestos` (
  `nombre` varchar(100) DEFAULT NULL,
  `codigo` varchar(30) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `precio_venta` decimal(11,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repuestos`
--

INSERT INTO `repuestos` (`nombre`, `codigo`, `modelo`, `descripcion`, `stock`, `precio_venta`) VALUES
('inyectadora', '45', 'lova', 'qq', 3, 400000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` date NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `empresa_id`, `descripcion`, `fecha`, `usuario_id`) VALUES
(1, 1, 'Cambio de pieza rynan 01001251537892x', '2025-06-18', 1),
(3, 1, 'Cambio de pieza domino 145210J1452475asd', '2025-06-27', 1),
(7, 9, 'cambiamos la heladera', '2025-06-30', 1),
(8, 12, 'Reparacion de baños cubiculo ultimo de varones donde se consiguen lechugas', '2025-11-06', 4),
(9, 4, 'servicio de tinta', '2025-11-11', 4),
(10, 7, 'hh', '2025-11-25', 22);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`) VALUES
(1, 'admin', '$2y$10$36Qm7ZO7KNhRDF0fJhiEOeYCoeTNBMxx3R7XSj.ABMacH8jZ.q40.', 'Administrador'),
(3, 'hola', '$2y$10$3ieFv.x8akFxW96T.G121uai2pu6ilW52Fl0cM1Qg5PyOiiGBydaG', 'Jesus Rodriguez'),
(4, 'juan', '$2y$10$ylBVH0BUSuSZ5Vugp45M1.3mvkU5zc.a4z.Fs7rZP.dFZIrmREePq', 'Juan Sun'),
(21, 'jesus', '$2y$10$ZeC2pTQxqJ18KG6f.rszS.9yx9BE6EQclhZGe9FPRLDmVPIXVxq.O', 'Jesus Rodriguez'),
(22, 'johan', '$2y$10$rCPOgghBrdvcnn5j1XKYKOxnzIcO6m6M6ExbtiVUhy5oyUylk/4Mu', 'Johan torres');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `empresas_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `num_comprobante` varchar(20) DEFAULT NULL,
  `total` decimal(11,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `empresas_id`, `usuario_id`, `fecha_venta`, `num_comprobante`, `total`, `descripcion`) VALUES
(14, 9, 22, '2025-11-12 00:00:00', NULL, 922.26, 'hj'),
(17, 12, 22, '2025-11-12 00:00:00', NULL, 37.21, 'kk'),
(18, 9, 22, '2025-11-12 00:00:00', NULL, 37.21, 'dd'),
(20, 9, 22, '2025-11-25 00:00:00', NULL, 1000.00, 'hjg'),
(21, 9, 22, '2025-11-25 00:00:00', NULL, 1000.00, 'hj'),
(22, 13, 22, '2025-11-25 00:00:00', NULL, 2000000.00, 'uju');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `fk_compra_producto` (`codigo_producto`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `fk_unificado_codigo` (`codigo_producto`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `maquinas`
--
ALTER TABLE `maquinas`
  ADD PRIMARY KEY (`codigo`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`codigo_unificado`),
  ADD KEY `fk_repuesto_codigo` (`codigo_repuesto`),
  ADD KEY `fk_maquina_codigo` (`codigo_maquina`);

--
-- Indices de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  ADD PRIMARY KEY (`codigo`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `num_comprobante` (`num_comprobante`),
  ADD KEY `empresas_id` (`empresas_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_producto` FOREIGN KEY (`codigo_producto`) REFERENCES `producto` (`codigo_unificado`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unificado_codigo` FOREIGN KEY (`codigo_producto`) REFERENCES `producto` (`codigo_unificado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_maquina_codigo` FOREIGN KEY (`codigo_maquina`) REFERENCES `maquinas` (`codigo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_repuesto_codigo` FOREIGN KEY (`codigo_repuesto`) REFERENCES `repuestos` (`codigo`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `servicios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`empresas_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
