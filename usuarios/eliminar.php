<?php
session_start();
require_once '../includes/database.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'No puedes eliminar tu propia cuenta.']);
    exit;
}

try {
    $conn->query("DELETE FROM usuarios WHERE id = $id");
    
    echo json_encode(['success' => true]);

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1451) {
        echo json_encode([
            'success' => false, 
            'error' => 'No se puede eliminar este usuario porque tiene historial de Ventas, Compras o Servicios registrados a su nombre. (Seguridad de Auditoría)'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}
?>