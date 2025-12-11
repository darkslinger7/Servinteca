<?php
session_start();
require_once '../includes/database.php';

// Preparamos la respuesta JSON
header("Content-Type: application/json");

// Función para enviar respuesta y salir limpiamente
function responder($exito, $mensaje) {
    ob_end_clean(); // Borrar cualquier "basura" anterior
    echo json_encode(['success' => $exito, 'error' => $mensaje]);
    exit;
}

// 2. SEGURIDAD
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    responder(false, 'No autorizado');
}

$input = json_decode(file_get_contents('php://input'), true);
$venta_id = intval($input['id'] ?? 0);

if ($venta_id <= 0) {
    responder(false, 'ID de venta inválido');
}

try {
    $conn->begin_transaction();

    // 3. RECUPERAR ITEMS (Consulta Segura)
    $stmt_items = $conn->prepare("SELECT codigo_producto, cantidad FROM detalle_venta WHERE venta_id = ?");
    $stmt_items->bind_param("i", $venta_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();

    // Preparamos consultas auxiliares fuera del bucle
    $stmt_check_tipo = $conn->prepare("SELECT tipo FROM productos WHERE codigo = ?");
    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE codigo = ?");

    while($item = $res_items->fetch_assoc()) {
        $cod = $item['codigo_producto'];
        $cant = $item['cantidad'];
        
        // Verificar tipo
        $stmt_check_tipo->bind_param("s", $cod);
        $stmt_check_tipo->execute();
        $res_tipo = $stmt_check_tipo->get_result();
        $tipo = ($res_tipo->num_rows > 0) ? $res_tipo->fetch_assoc()['tipo'] : '';

        // Solo devolver stock si no es servicio
        if ($tipo !== 'servicio') {
            $stmt_stock->bind_param("is", $cant, $cod);
            $stmt_stock->execute();
        }
    }
    
    // Cerrar statements auxiliares
    $stmt_items->close();
    $stmt_check_tipo->close();
    $stmt_stock->close();

    // 4. ELIMINAR LA VENTA
    $stmt_del = $conn->prepare("DELETE FROM ventas WHERE id = ?");
    $stmt_del->bind_param("i", $venta_id);
    
    if ($stmt_del->execute()) {
        $conn->commit();
        responder(true, 'Venta anulada correctamente');
    } else {
        throw new Exception("No se pudo eliminar el registro de venta.");
    }
    $stmt_del->close();

} catch (Exception $e) {
    $conn->rollback();
    responder(false, 'Error: ' . $e->getMessage());
}
?>