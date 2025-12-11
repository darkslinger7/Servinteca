<?php
session_start();
require_once '../includes/database.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

try {
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    if ($conn->errno == 1451) {
        echo json_encode(['success' => false, 'error' => 'No se puede eliminar: Este producto tiene historial de Ventas o Compras.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
    }
}
?>