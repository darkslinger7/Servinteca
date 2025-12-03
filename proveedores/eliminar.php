<?php
session_start();
require_once '../includes/database.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;

try {
    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    // Si falla por Foreign Key (tiene compras asociadas)
    if ($conn->errno == 1451) {
        echo json_encode(['success' => false, 'error' => 'No se puede eliminar: Este proveedor tiene historial de compras.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
    }
}
?>