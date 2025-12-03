<?php
session_start();
require_once '../includes/database.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'error' => 'Método no permitido. Se requiere POST.',
        'method_received' => $_SERVER['REQUEST_METHOD']
    ]));
}


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}


$input = json_decode(file_get_contents('php://input'), true);


if (!$input || !isset($input['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Datos inválidos']));
}

try {
    
    $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $input['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error en la ejecución");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>