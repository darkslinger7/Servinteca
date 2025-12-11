<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    // Opcional: Redirigir si no está logueado, aunque a veces las facturas son públicas
    // header("Location: /Servindteca/auth/login.php"); exit(); 
}
require_once '../includes/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID de factura inválido.");
}

// 1. Datos Venta y Cliente (Cabecera)
$sql = "SELECT v.*, e.nombre as cliente, e.rif, e.direccion, e.telefono 
        FROM ventas v 
        JOIN empresas e ON v.empresas_id = e.id 
        WHERE v.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    die("Venta no encontrada.");
}

// 2. Items (Detalle) - ACTUALIZADO A TABLA 'PRODUCTOS'
$sql_items = "SELECT 
                d.cantidad,
                d.precio_unitario,
                d.codigo_producto,
                p.nombre as producto,
                p.modelo
              FROM detalle_venta d 
              JOIN productos p ON d.codigo_producto = p.codigo
              WHERE d.venta_id = ?";
              
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$stmt_items->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #<?= str_pad($venta['id'], 6, "0", STR_PAD_LEFT) ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #002366; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #002366; }
        .header p { margin: 5px 0; color: #666; }
        
        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-box { width: 48%; }
        .info-title { font-weight: bold; color: #002366; border-bottom: 1px solid #ccc; margin-bottom: 10px; padding-bottom: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.95rem; }
        th { background-color: #f8f9fa; color: #002366; text-transform: uppercase; font-size: 0.85rem; }
        .text-right { text-align: right; }
        
        .total-section { text-align: right; margin-top: 20px; }
        .total-row { display: flex; justify-content: flex-end; margin-bottom: 5px; }
        .total-label { width: 150px; font-weight: bold; }
        .total-value { width: 150px; font-weight: bold; font-size: 1.2rem; color: #002366; }
        
        .btn-print { 
            display: block; margin: 40px auto; padding: 10px 20px; 
            background: #002366; color: white; border: none; border-radius: 5px; 
            cursor: pointer; font-size: 1rem; text-decoration: none; text-align: center; width: 150px;
        }
        .btn-print:hover { background: #001a4d; }
        
        @media print { 
            .btn-print { display: none; } 
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SERVINDTECA</h1>
        <p>Servicios Industriales Tecnológicos C.A.</p>
        <p>RIF: J-405570360</p>
    </div>

    <div class="info-section">
        <div class="info-box">
            <div class="info-title">CLIENTE</div>
            <strong><?= htmlspecialchars($venta['cliente']) ?></strong><br>
            RIF: <?= htmlspecialchars($venta['rif']) ?><br>
            <?= htmlspecialchars($venta['direccion']) ?><br>
            <?= htmlspecialchars($venta['telefono']) ?>
        </div>
        <div class="info-box text-right">
            <div class="info-title">DATOS FACTURA</div>
            <strong>N° Interno:</strong> <?= str_pad($venta['id'], 6, "0", STR_PAD_LEFT) ?><br>
            <strong>N° Control:</strong> <?= htmlspecialchars($venta['num_comprobante'] ?? 'N/A') ?><br>
            <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">CÓDIGO</th>
                <th width="45%">DESCRIPCIÓN / PRODUCTO</th>
                <th width="10%" class="text-right">CANT.</th>
                <th width="15%" class="text-right">PRECIO UNIT.</th>
                <th width="15%" class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal_acumulado = 0;
            while($item = $result_items->fetch_assoc()): 
                $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
                $subtotal_acumulado += $subtotal_item;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['codigo_producto']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['producto']) ?></strong><br>
                    <small style="color:#666;"><?= htmlspecialchars($item['modelo'] ?? '') ?></small>
                </td>
                <td class="text-right"><?= $item['cantidad'] ?></td>
                <td class="text-right">$<?= number_format($item['precio_unitario'], 2) ?></td>
                <td class="text-right">$<?= number_format($subtotal_item, 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <div class="total-label">SUBTOTAL:</div>
            <div class="total-value" style="font-size: 1rem; color: #333;">$<?= number_format($subtotal_acumulado, 2) ?></div>
        </div>
        <div class="total-row" style="margin-top: 10px; border-top: 2px solid #ddd; padding-top: 10px;">
            <div class="total-label">TOTAL A PAGAR:</div>
            <div class="total-value">$<?= number_format($venta['total'], 2) ?></div>
        </div>
    </div>
    
    <?php if(!empty($venta['descripcion'])): ?>
    <div style="margin-top: 30px; border: 1px solid #eee; padding: 15px; border-radius: 5px; background: #f9f9f9;">
        <strong>Observaciones:</strong><br>
        <?= nl2br(htmlspecialchars($venta['descripcion'])) ?>
    </div>
    <?php endif; ?>

    <button onclick="window.print()" class="btn-print">Imprimir</button>
</body>
</html>