<?php
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (isset($_GET['nombre'])) {
    $nombre_buscado = '%' . limpiar($_GET['nombre']) . '%'; 

    $resultados = [];

    $sql_maquinas = "SELECT codigo, nombre, 'Máquina' AS tipo FROM maquinas WHERE nombre LIKE ? LIMIT 5";
    $stmt_maquinas = $conn->prepare($sql_maquinas);
    $stmt_maquinas->bind_param("s", $nombre_buscado);
    $stmt_maquinas->execute();
    $result_maquinas = $stmt_maquinas->get_result();
    while ($row = $result_maquinas->fetch_assoc()) {
        $resultados[] = $row;
    }
    $stmt_maquinas->close();

    
    $sql_repuestos = "SELECT codigo, nombre, 'Repuesto' AS tipo FROM repuestos WHERE nombre LIKE ? LIMIT 5";
    $stmt_repuestos = $conn->prepare($sql_repuestos);
    $stmt_repuestos->bind_param("s", $nombre_buscado);
    $stmt_repuestos->execute();
    $result_repuestos = $stmt_repuestos->get_result();
    while ($row = $result_repuestos->fetch_assoc()) {
        $resultados[] = $row;
    }
    $stmt_repuestos->close();
    
    if (count($resultados) > 0) {
        echo json_encode([
            'success' => true,
            'resultados' => $resultados
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontraron coincidencias.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Nombre no especificado.']);
}
?>