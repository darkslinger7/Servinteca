<?php
// Este script NO debe incluir header.php ni footer.php. Solo devuelve datos JSON.
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    // Para un entorno real, es mejor usar un mensaje JSON de error
    die(); 
}

require_once '../includes/database.php';

// Establecer el encabezado para devolver JSON
header('Content-Type: application/json');

// Consulta para contar servicios por mes y año
// Usamos la columna 'fecha' de la tabla 'servicios'
$sql = "SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes_anio, 
            COUNT(id) as total_servicios
        FROM servicios
        GROUP BY mes_anio
        ORDER BY mes_anio ASC";

$result = $conn->query($sql);

$data = array();

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Formato: ['2025-10', 5], ['2025-11', 8]
        $data[] = [
            $row['mes_anio'], 
            (int)$row['total_servicios']
        ];
    }
}

// Devolver los datos en formato JSON
echo json_encode($data);

// Cerramos la conexión
$conn->close();
?>