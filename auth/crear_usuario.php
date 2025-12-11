<?php
require_once '../includes/database.php';

$username = "johan";
$plain_password = "1234";
$nombre = "Johan torres";

// Generar nuevo hash
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);


// Insertar nuevo usuario
$sql = "INSERT INTO usuarios (username, password, nombre_completo) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $hashed_password, $nombre);

if ($stmt->execute()) {
    echo "✅ Usuario creado correctamente.";
} else {
    echo "❌ Error: " . $conn->error;
}
?>  