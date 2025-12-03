<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "DELETE FROM servicios WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION['user_id']);


if ($id <= 0) {
    header("Location: index.php?error=id_invalido");
    exit();
}


$sql = "DELETE FROM servicios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    
    header("Location: index.php?success=servicio_eliminado");
} else {
   
    header("Location: index.php?error=no_se_pudo_eliminar");
}

$stmt->close();
$conn->close();
exit();
?>