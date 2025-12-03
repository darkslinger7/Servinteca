<?php
// Script para devolver el nombre y el precio de venta de un producto por su código
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (isset($_GET['codigo'])) {
    $codigo = limpiar($_GET['codigo']);

    // Buscar el tipo de producto primero
    $sql_type = "SELECT tipo_producto FROM producto WHERE codigo_unificado = ?";
    $stmt_type = $conn->prepare($sql_type);
    $stmt_type->bind_param("s", $codigo);
    $stmt_type->execute();
    $result_type = $stmt_type->get_result();
    $product_type_row = $result_type->fetch_assoc();
    $stmt_type->close();
    
    if ($product_type_row) {
        $tipo = $product_type_row['tipo_producto'];
        $tabla = ($tipo === 'maquina') ? 'maquinas' : 'repuestos';
        
        // Usar el tipo para buscar el nombre y el precio de venta
        $sql_details = "SELECT nombre, precio_venta FROM {$tabla} WHERE codigo = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("s", $codigo);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();
        $details = $result_details->fetch_assoc();
        $stmt_details->close();

        if ($details) {
            echo json_encode([
                'success' => true,
                'nombre' => $details['nombre'],
                'precio_venta' => $details['precio_venta'],
                'tipo_producto' => $tipo
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontraron detalles para este código.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Código no encontrado en el catálogo.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Código no especificado.']);
}
?>