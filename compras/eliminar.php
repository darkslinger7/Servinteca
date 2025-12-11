<?php
session_start();
require_once '../includes/database.php';

header("Content-Type: application/json");

// 1. Validar seguridad (Solo POST y Usuario Logueado)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// 2. Obtener el ID
$input = json_decode(file_get_contents('php://input'), true);
$id_compra = (int)($input['id'] ?? 0);

if ($id_compra <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

try {
    $conn->begin_transaction();

    // 3. RECUPERAR ITEMS PARA RESTAR EL STOCK (Revertir la compra)
    // Buscamos en la nueva tabla 'detalle_compra'
    $sql_items = "SELECT codigo_producto, cantidad FROM detalle_compra WHERE compra_id = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $id_compra);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();

    // Preparamos la consulta de actualización de stock (Restar)
    $sql_update = "UPDATE productos SET stock = stock - ? WHERE codigo = ?";
    $stmt_update = $conn->prepare($sql_update);

    while($item = $res_items->fetch_assoc()) {
        $stmt_update->bind_param("is", $item['cantidad'], $item['codigo_producto']);
        $stmt_update->execute();
    }
    
    $stmt_items->close();
    $stmt_update->close();

    // 4. ELIMINAR LA COMPRA (Cabecera)
    // Gracias a ON DELETE CASCADE en la base de datos, los detalles se borran solos.
    $stmt_del = $conn->prepare("DELETE FROM compras WHERE id = ?");
    $stmt_del->bind_param("i", $id_compra);
    
    if ($stmt_del->execute()) {
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("No se pudo eliminar el registro principal.");
    }
    $stmt_del->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>