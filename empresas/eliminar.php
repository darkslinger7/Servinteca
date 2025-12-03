<?php
session_start();
require_once '../includes/database.php';

// Configuración de cabeceras
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// 1. Validaciones básicas
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método no permitido.']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'ID no proporcionado']));
}

try {
    // Intentamos eliminar
    $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $input['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error al ejecutar la consulta.");
    }
    
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    // CAPTURAR ERROR DE CLAVE FORÁNEA (1451)
    if ($e->getCode() == 1451) {
        http_response_code(409); // Conflicto
        echo json_encode([
            'success' => false, 
            'error' => 'No se puede eliminar esta empresa porque tiene VENTAS o SERVICIOS asociados. Debes eliminar esos registros primero.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>