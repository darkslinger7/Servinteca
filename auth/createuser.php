<?php

require_once '../includes/database.php'; 

$username = "admin";
$plain_password = "1234"; //como es un login cerrado no abierto para cualquier usuario sino para el administrador solo puede anadir usuarios por este medio
$nombre = "Jesus Rodriguez";


$hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

$sql = "INSERT INTO usuarios (username, password, nombre_completo) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $hashed_password, $nombre);

if ($stmt->execute()) {
    echo "Usuario creado:<br>";
    echo "Username: $username<br>";
    echo "Password: $plain_password<br>";
    echo "Hash: $hashed_password";
} else {
    echo "Error: " . $conn->error;
}
?>