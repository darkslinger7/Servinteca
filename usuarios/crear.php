<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /Servindteca/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $username = limpiar($_POST['username']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    if (empty($nombre) || empty($username) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $check = $conn->query("SELECT id FROM usuarios WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hash, $nombre, $rol);
            
            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit();
            } else {
                $error = "Error al registrar: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nuevo Usuario</h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" required placeholder="Ej. Juan Pérez">
        </div>
        
        <div class="form-group">
            <label>Usuario (Login):</label>
            <input type="text" name="username" required placeholder="Ej. jperez">
        </div>

        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Rol / Permisos:</label>
            <select name="rol" required>
                <option value="vendedor">Vendedor (Solo Ventas/Servicios)</option>
                <option value="admin">Administrador (Acceso Total)</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Crear Usuario</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>
<?php include '../includes/footer.php'; ?>