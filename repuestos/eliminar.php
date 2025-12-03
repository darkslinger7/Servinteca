<?php
session_start();
require_once '../includes/database.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['codigo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Código no proporcionado']);
    exit;
}

$codigo = $input['codigo'];

try {
    $stmt = $conn->prepare("DELETE FROM repuestos WHERE codigo = ?");
    $stmt->bind_param("s", $codigo);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'El repuesto no existe']);
        }
    } else {
        throw new Exception($conn->error, $conn->errno);
    }
    
} catch (Exception $e) {
    if ($conn->errno == 1451) { 
        http_response_code(409); 
        echo json_encode([
            'success' => false, 
            'error' => 'No se puede eliminar: Este repuesto tiene historial de COMPRAS o VENTAS.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    }
}
?>