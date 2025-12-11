<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') exit(header("Location: /Servindteca/index.php"));

$id = intval($_GET['id'] ?? 0);
$res = $conn->query("SELECT * FROM usuarios WHERE id = $id");
$user = $res->fetch_assoc();
if (!$user) exit(header("Location: index.php"));

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $username = limpiar($_POST['username']);
    $rol = $_POST['rol'];
    $password = $_POST['password'];

    // Si escribió password, lo actualizamos, si no, dejamos el viejo
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nombre_completo=?, username=?, rol=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nombre, $username, $rol, $hash, $id);
    } else {
        $sql = "UPDATE usuarios SET nombre_completo=?, username=?, rol=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $username, $rol, $id);
    }

    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    } else {
        $error = "Error al actualizar.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Usuario: <?= htmlspecialchars($user['username']) ?></h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre_completo']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Usuario:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="form-group">
            <label>Nueva Contraseña (Opcional):</label>
            <input type="password" name="password" placeholder="Dejar en blanco para no cambiar">
        </div>

        <div class="form-group">
            <label>Rol:</label>
            <select name="rol" required>
                <option value="vendedor" <?= $user['rol']=='vendedor'?'selected':'' ?>>Vendedor</option>
                <option value="admin" <?= $user['rol']=='admin'?'selected':'' ?>>Administrador</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>
<?php include '../includes/footer.php'; ?>