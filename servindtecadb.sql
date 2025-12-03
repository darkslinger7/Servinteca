-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-12-2025 a las 05:30:33
-- Versión del servidor: 8.0.44
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
  `id_compra` int NOT NULL,
  `num_factura` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_proveedor` int DEFAULT NULL,
  `codigo_producto` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_compra` date NOT NULL,
  `cantidad` int NOT NULL,
  `precio_compra_unitario` decimal(11,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_compra`, `num_factura`, `id_proveedor`, `codigo_producto`, `fecha_compra`, `cantidad`, `precio_compra_unitario`) VALUES
(9, '', NULL, '123', '2025-11-25', 50, 5000.00),
(10, '', NULL, '45', '2025-11-25', 50, 10000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int NOT NULL,
  `venta_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(11,2) NOT NULL,
  `codigo_producto` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `cantidad`, `precio_unitario`, `codigo_producto`) VALUES
(21, 20, 1, 1000.00, '123'),
(22, 21, 1, 1000.00, '123'),
(23, 22, 5, 400000.00, '45'),
(24, 23, 2, 1000.00, '123'),
(25, 24, 5, 1000.00, '123'),
(26, 25, 2, 1000.00, '123');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `rif` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `nombre`, `rif`, `direccion`, `telefono`, `email`, `fecha_registro`) VALUES
(1, 'HierrosMaterialesNC', 'J011252002', 'Duaca Carrera 4 entre Calles 15 y 16', '02538088046', 'hierrosmatn@gmail.com', '2025-06-29 22:07:45'),
(4, 'Power Colour', '1235456', 'Barquisimeto Carrera 17 con Calle 22', '04122640541', 'power123@gmail.com', '2025-06-30 01:11:32'),
(7, 'Inmobiliaria la Asunsion', 'J-5asd4asd4', 'Barquisimeto Carrera 12 con calle 15', '041234518745', 'kilos1pow@gmail.com', '2025-06-30 01:24:19'),
(9, 'Heladeria Kreisel Supra', '8514218', 'Barquisimeto Carrera 16 calle 19', '04123697412', 'kilojsjqm@gmail.com', '2025-06-30 02:33:58'),
(12, 'iujo barquisimeto', 'j-123912', 'Barquisimeto Carrera 22 calle 57', '04122640545', 'iujoedu@gmail.com', '2025-11-06 15:18:21'),
(13, 'natulac', '458774', 'Barquisimeto Carrera 12 calle 25', '02538088044', 'natulacteos@gmail.com', '2025-11-06 18:27:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinas`
--

CREATE TABLE `maquinas` (
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `stock` int NOT NULL,
  `precio_venta` decimal(11,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `maquinas`
--

INSERT INTO `maquinas` (`nombre`, `codigo`, `modelo`, `descripcion`, `stock`, `precio_venta`) VALUES
('inkjet', '123', 'Epson L5590', 'Impresora de tinta continua', 41, 1000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `codigo_unificado` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_producto` varchar(10) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Indica si es "maquina" o "repuesto"',
  `codigo_repuesto` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo_maquina` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL
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
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Razón Social',
  `documento` varchar(50) NOT NULL COMMENT 'RIF, RUT o Tax ID',
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `persona_contacto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `documento`, `direccion`, `telefono`, `email`, `persona_contacto`) VALUES
(1, 'Epson Global C.A.', 'J-45612345', 'Shinxuan China', '+584120203664', 'epson123@gmail.com', 'Antony Wu');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

CREATE TABLE `repuestos` (
  `nombre` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `precio_venta` decimal(11,2) DEFAULT '0.00'
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
  `id` int NOT NULL,
  `empresa_id` int NOT NULL,
  `tipo_servicio` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci NOT NULL,
  `horas_uso` int DEFAULT NULL,
  `equipo_atendido` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` date NOT NULL,
  `proximo_servicio` date DEFAULT NULL,
  `usuario_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `empresa_id`, `tipo_servicio`, `descripcion`, `horas_uso`, `equipo_atendido`, `fecha`, `proximo_servicio`, `usuario_id`) VALUES
(1, 1, NULL, 'Cambio de pieza rynan 01001251537892x', NULL, NULL, '2025-06-18', NULL, 1),
(3, 1, NULL, 'Cambio de pieza domino 145210J1452475asd', NULL, NULL, '2025-06-27', NULL, 1),
(7, 9, NULL, 'cambiamos la heladera', NULL, NULL, '2025-06-30', NULL, 1),
(8, 12, NULL, 'Reparacion de baños cubiculo ultimo de varones donde se consiguen lechugas', NULL, NULL, '2025-11-06', NULL, 4),
(9, 4, NULL, 'servicio de tinta', NULL, NULL, '2025-11-11', NULL, 4),
(10, 7, NULL, 'hh', NULL, NULL, '2025-11-25', NULL, 22),
(11, 1, NULL, 'cambio de pieza', NULL, NULL, '2025-07-09', NULL, 22);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
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
  `id` int NOT NULL,
  `empresas_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `num_comprobante` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(11,2) NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
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
(22, 13, 22, '2025-11-25 00:00:00', NULL, 2000000.00, 'uju'),
(23, 4, 22, '2025-11-25 00:00:00', NULL, 2000.00, 'prueba'),
(24, 7, 22, '2025-10-15 00:00:00', NULL, 5000.00, 'pru'),
(25, 1, 22, '2025-08-06 00:00:00', NULL, 2000.00, '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `fk_compra_producto` (`codigo_producto`),
  ADD KEY `fk_compra_proveedor` (`id_proveedor`);

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
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id_compra` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_producto` FOREIGN KEY (`codigo_producto`) REFERENCES `producto` (`codigo_unificado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
