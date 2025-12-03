<?php
session_start();
// Asegúrate de que solo los usuarios logueados puedan acceder si es necesario
// if (!isset($_SESSION['user_id'])) {
//     header("Location: /Servindteca/login.php");
//     exit();
// }
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';


$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($venta_id <= 0) {
    die("ID de venta inválido o no especificado.");
}

// --- 1. CONSULTA DE CABECERA (Funciona Correctamente) ---
$sql_cabecera = "SELECT 
                    v.*, 
                    v.descripcion, 
                    e.nombre as empresa_nombre
                    
                FROM ventas v
                JOIN empresas e ON v.empresas_id = e.id
                WHERE v.id = ?";

$stmt_cabecera = $conn->prepare($sql_cabecera);
$stmt_cabecera->bind_param("i", $venta_id);
$stmt_cabecera->execute();
$result_cabecera = $stmt_cabecera->get_result();

if ($result_cabecera === false || $result_cabecera->num_rows == 0) {
    die("Venta no encontrada o error en la consulta de cabecera.");
}

$datos_venta = $result_cabecera->fetch_assoc();
$stmt_cabecera->close();


// --- 2. CONSULTA DEL DETALLE (CORRECCIÓN AQUÍ) ---
// Usamos COALESCE para obtener el código único y unimos con las columnas correctas.
$sql_detalle = "SELECT 
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.codigo_producto AS codigo, -- Usamos la columna unificada
                    COALESCE(m.nombre, r.nombre) AS producto_nombre,
                    COALESCE(m.modelo, 'N/A') AS producto_modelo
                FROM detalle_venta dv
                -- El JOIN se realiza ahora usando la columna unificada
                LEFT JOIN maquinas m ON dv.codigo_producto = m.codigo 
                LEFT JOIN repuestos r ON dv.codigo_producto = r.codigo
                WHERE dv.venta_id = ?";

$stmt_detalle = $conn->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $venta_id);
$stmt_detalle->execute();
$result_detalle = $stmt_detalle->get_result();
$stmt_detalle->close(); // Cerrar el statement después de obtener el resultado

if ($result_detalle === false) {
    die("Error al cargar el detalle de la venta: " . $conn->error);
}


header("Content-Type: text/html; charset=UTF-8");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura N° <?= htmlspecialchars($datos_venta['id']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
        }
        .factura-container {
            width: 790px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
        }
        h2, h3, h4 {
            text-align: center;
            margin: 5px 0;
        }
        .header, .detalle, .totales {
            margin-top: 15px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .detalle table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .detalle th, .detalle td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .detalle th {
            background-color: #f2f2f2;
        }
        .totales {
            float: right;
            width: 40%;
            margin-top: 20px;
        }
        .totales .total-final {
            font-size: 14pt;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 10px;
        }
        @media print {
            body { background: none; }
            .factura-container { border: none; padding: 0; width: 100%; }
            .no-print { display: none; }
            .totales { float: right; width: 40%; margin-top: 20px; }
        }
    </style>
</head>


<body onload="window.print()"> 
    <div class="factura-container">
        <div class="header">
            <h2>SERVICIOS Y MÁQUINAS S.A.</h2>
            <p style="text-align: center; font-size: 10pt;">
                </p>
        </div>

        <div class="info-venta">
            <h3>FACTURA N°: <?= htmlspecialchars($datos_venta['id']) ?></h3>
            <div class="info-row">
                <strong>Fecha de Emisión:</strong>
                <span><?= date('d/m/Y', strtotime($datos_venta['fecha_venta'])) ?></span>
            </div>
            
            <div style="border: 1px solid #ccc; padding: 10px; margin-top: 10px;">
                <h4>INFORMACIÓN DEL CLIENTE</h4>
                <div class="info-row">
                    <strong>Nombre:</strong>
                    <span><?= htmlspecialchars($datos_venta['empresa_nombre']) ?></span>
                </div>
                </div>
        </div>
        
        <div class="detalle">
            <h4>DETALLE DE VENTA</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">CÓDIGO</th>
                        <th style="width: 35%;">Máquina / Producto</th>
                        <th>Modelo</th>
                        <th style="text-align: right;">Cantidad</th>
                        <th style="text-align: right;">Precio Unitario</th>
                        <th style="text-align: right;">TOTAL ÍTEM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal_total = 0;
                    
                    while($item = $result_detalle->fetch_assoc()): 
                        $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
                        $subtotal_total += $subtotal_item;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['codigo']) ?></td>
                        <td><?= htmlspecialchars($item['producto_nombre']) ?></td>
                        <td><?= htmlspecialchars($item['producto_modelo'] ?? 'N/A') ?></td>
                        <td style="text-align: right;"><?= htmlspecialchars($item['cantidad']) ?></td>
                        <td style="text-align: right;">$<?= number_format($item['precio_unitario'], 2, ',', '.') ?></td>
                        <td style="text-align: right;">$<?= number_format($subtotal_item, 2, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="clear: both;"></div>

            <div class="totales">
                <div class="info-row">
                    <strong>Subtotal:</strong>
                    <span>$<?= number_format($subtotal_total, 2, ',', '.') ?></span>
                </div>
                
                <div class="info-row total-final">
                    <strong>TOTAL FACTURA:</strong>
                    <span style="font-size: 16pt;">$<?= number_format($datos_venta['total'], 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
        
        <div style="clear: both; text-align: left; margin-top: 100px; padding-top: 20px; border-top: 1px solid #ccc;">
             <p><strong>Observaciones / Descripción:</strong> <?= htmlspecialchars($datos_venta['descripcion'] ?? 'Sin observaciones.') ?></p>
            
            <p style="text-align: center; font-size: 10pt; margin-top: 20px;">*Gracias por preferir nuestros servicios*</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; text-decoration: none; background-color: #f0f0f0; color: #333; border: 1px solid #ccc; border-radius: 5px;">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
        </div>

    </div>

</body>
</html>