<?php
require_once '../includes/database.php';
$id = intval($_GET['id']);

// Datos Venta y Cliente
$sql = "SELECT v.*, e.nombre as cliente, e.rif, e.direccion, e.telefono 
        FROM ventas v JOIN empresas e ON v.empresas_id = e.id WHERE v.id = $id";
$venta = $conn->query($sql)->fetch_assoc();

// Items
$sql_items = "SELECT d.*, COALESCE(m.nombre, r.nombre) as producto 
              FROM detalle_venta d 
              LEFT JOIN maquinas m ON d.codigo_producto = m.codigo
              LEFT JOIN repuestos r ON d.codigo_producto = r.codigo
              WHERE d.venta_id = $id";
$items = $conn->query($sql_items);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Factura #<?= $id ?></title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-size: 1.2em; font-weight: bold; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>SERVINDTECA</h1>
        <p>Servicios Industriales Tecnológicos</p>
    </div>

    <div class="info">
        <div>
            <strong>Cliente:</strong> <?= htmlspecialchars($venta['cliente']) ?><br>
            <strong>RIF:</strong> <?= htmlspecialchars($venta['rif']) ?><br>
            <strong>Dirección:</strong> <?= htmlspecialchars($venta['direccion']) ?>
        </div>
        <div style="text-align: right;">
            <strong>Factura N°:</strong> <?= str_pad($venta['id'], 6, "0", STR_PAD_LEFT) ?><br>
            <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?><br>
            <strong>Control:</strong> <?= htmlspecialchars($venta['num_comprobante']) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $items->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['producto']) ?> <small>(<?= $item['codigo_producto'] ?>)</small></td>
                <td><?= $item['cantidad'] ?></td>
                <td>$<?= number_format($item['precio_unitario'], 2) ?></td>
                <td>$<?= number_format($item['cantidad'] * $item['precio_unitario'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total">
        TOTAL A PAGAR: $<?= number_format($venta['total'], 2) ?>
    </div>

    <br><br>
    <button class="btn-print" onclick="window.print()">Imprimir Factura</button>
</body>
</html>