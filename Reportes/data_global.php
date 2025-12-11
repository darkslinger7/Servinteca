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
    'top_productos' => []
];

try {
    $sql_servicios = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as total 
                      FROM servicios 
                      GROUP BY mes ORDER BY mes ASC LIMIT 12";
    $res_serv = $conn->query($sql_servicios);
    while ($row = $res_serv->fetch_assoc()) { $response['servicios'][] = $row; }

  
    $meses = [];
    
    $sql_meses = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes FROM ventas
                  UNION 
                  SELECT DATE_FORMAT(fecha_compra, '%Y-%m') as mes FROM compras
                  ORDER BY mes ASC LIMIT 12";
    $res_meses = $conn->query($sql_meses);

    if($res_meses) {
        while($m = $res_meses->fetch_assoc()) {
            $mes_actual = $m['mes'];
            
         
            $sql_v = "SELECT SUM(total) as total_ventas FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = '$mes_actual'";
            $row_v = $conn->query($sql_v)->fetch_assoc();
            
      
            $sql_c = "SELECT SUM(total) as total_compras FROM compras WHERE DATE_FORMAT(fecha_compra, '%Y-%m') = '$mes_actual'";
            $row_c = $conn->query($sql_c)->fetch_assoc();

            $response['finanzas'][] = [
                'mes' => $mes_actual,
                'ventas' => (float)($row_v['total_ventas'] ?? 0),
                'compras' => (float)($row_c['total_compras'] ?? 0)
            ];
        }
    }

    $sql_dist = "SELECT 
                    p.tipo, 
                    SUM(d.cantidad * d.precio_unitario) as total_dinero
                 FROM detalle_venta d
                 JOIN productos p ON d.codigo_producto = p.codigo
                 GROUP BY p.tipo";
    $res_dist = $conn->query($sql_dist);
    while($row = $res_dist->fetch_assoc()) {
        $response['distribucion_ventas'][] = [
            'tipo' => ucfirst($row['tipo']), 
            'total' => (float)$row['total_dinero']
        ];
    }

    
    $sql_top = "SELECT 
                    p.nombre,
                    SUM(d.cantidad) as total_vendido
                FROM detalle_venta d
                JOIN productos p ON d.codigo_producto = p.codigo
                GROUP BY d.codigo_producto, p.nombre
                ORDER BY total_vendido DESC
                LIMIT 5";
    $res_top = $conn->query($sql_top);
    while($row = $res_top->fetch_assoc()) {
        $response['top_productos'][] = [
            'nombre' => $row['nombre'],
            'cantidad' => (int)$row['total_vendido']
        ];
    }

} catch (Exception $e) { 
    http_response_code(500);
    $response = ['error' => 'Error al obtener datos: ' . $e->getMessage()];
}

echo json_encode($response);
$conn->close();
?>