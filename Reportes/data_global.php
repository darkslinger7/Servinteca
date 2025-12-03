<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'No autorizado']));
}

require_once '../includes/database.php';
header('Content-Type: application/json');

$response = [
    'servicios' => [],
    'finanzas' => [],
    'distribucion_ventas' => [],
    'top_productos' => [], // NUEVO
    'stock_bajo' => []     // NUEVO
];

// 1. SERVICIOS (Igual que antes)
$sql_servicios = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as total FROM servicios GROUP BY mes ORDER BY mes ASC LIMIT 12";
$res_serv = $conn->query($sql_servicios);
while ($row = $res_serv->fetch_assoc()) { $response['servicios'][] = $row; }

// 2. FINANZAS (Igual que antes)
$meses = [];
$sql_meses = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes FROM ventas UNION SELECT DATE_FORMAT(fecha_compra, '%Y-%m') as mes FROM compra ORDER BY mes ASC LIMIT 12";
$res_meses = $conn->query($sql_meses);
if($res_meses) {
    while($m = $res_meses->fetch_assoc()) {
        $mes_actual = $m['mes'];
        $sql_v = "SELECT SUM(total) as total_ventas FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = '$mes_actual'";
        $row_v = $conn->query($sql_v)->fetch_assoc();
        $sql_c = "SELECT SUM(cantidad * precio_compra_unitario) as total_compras FROM compra WHERE DATE_FORMAT(fecha_compra, '%Y-%m') = '$mes_actual'";
        $row_c = $conn->query($sql_c)->fetch_assoc();
        $response['finanzas'][] = ['mes' => $mes_actual, 'ventas' => (float)($row_v['total_ventas'] ?? 0), 'compras' => (float)($row_c['total_compras'] ?? 0)];
    }
}

// 3. DISTRIBUCIÓN (Igual que antes)
$sql_dist = "SELECT p.tipo_producto, SUM(d.cantidad * d.precio_unitario) as total_dinero FROM detalle_venta d JOIN producto p ON d.codigo_producto = p.codigo_unificado GROUP BY p.tipo_producto";
$res_dist = $conn->query($sql_dist);
while($row = $res_dist->fetch_assoc()) {
    $response['distribucion_ventas'][] = ['tipo' => ucfirst($row['tipo_producto']), 'total' => (float)$row['total_dinero']];
}

// --- NUEVO: 4. TOP 5 PRODUCTOS MÁS VENDIDOS ---
// Unimos detalle_venta con maquinas y repuestos para sacar el nombre
$sql_top = "SELECT 
                d.codigo_producto,
                COALESCE(m.nombre, r.nombre) as nombre_producto,
                SUM(d.cantidad) as total_vendido
            FROM detalle_venta d
            LEFT JOIN maquinas m ON d.codigo_producto = m.codigo
            LEFT JOIN repuestos r ON d.codigo_producto = r.codigo
            GROUP BY d.codigo_producto, nombre_producto
            ORDER BY total_vendido DESC
            LIMIT 5";
$res_top = $conn->query($sql_top);
while($row = $res_top->fetch_assoc()) {
    $response['top_productos'][] = [
        'nombre' => $row['nombre_producto'] ?? 'Desconocido',
        'cantidad' => (int)$row['total_vendido']
    ];
}

// --- NUEVO: 5. ALERTA STOCK BAJO (Menor a 5 unidades) ---
$sql_stock = "SELECT codigo, nombre, stock, 'Máquina' as tipo FROM maquinas WHERE stock <= 5
              UNION ALL
              SELECT codigo, nombre, stock, 'Repuesto' as tipo FROM repuestos WHERE stock <= 5
              ORDER BY stock ASC LIMIT 10";
$res_stock = $conn->query($sql_stock);
while($row = $res_stock->fetch_assoc()) {
    $response['stock_bajo'][] = $row;
}

echo json_encode($response);
$conn->close();
?>