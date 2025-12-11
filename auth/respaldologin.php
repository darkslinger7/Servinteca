<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null; 

if (isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de seguridad inválido";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Todos los campos son obligatorios";
        } else {
            $sql = "SELECT id, password, nombre_completo FROM usuarios WHERE username = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("s", $username);  
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $username;
                        $_SESSION['nombre_completo'] = $user['nombre_completo'];
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
                        
                        header("Location: /Servindteca/index.php");
                        exit();
                    } else {
                        $error = "Credenciales incorrectas";
                    }
                } else {
                    $error = "Usuario no encontrado";
                }
                $stmt->close();
            } else {
                $error = "Error en la base de datos";
            }
        }
    }
}
$conn->close();
?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Servindteca</title>
    <link rel="stylesheet" href="/Servindteca/css/styles.css">
    <link rel="icon" href="/Servindteca/img/Logo.png">
</head>
<body>
<div class="login-container">
    <img src="/Servindteca/img/logo.png" alt="Logo Servindteca" class="login-logo">
    <div class="company-header">
        <h2>SERVICIOS INDUSTRIALES</h2>
        <h3>TECNOLOGY C.A.</h3>
        <p class="rif">RIF.J-405570360</p>
    </div>
    
    <h1 class="login-title">Iniciar Sesión</h1>
    
    <?php if($error !== null): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form class="login-form" action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="login-button">Ingresar</button>
    </form>
  </div>
</body>
</html> -->