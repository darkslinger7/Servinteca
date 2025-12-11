<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } else {
        $stmt = $conn->prepare("INSERT INTO tipos_servicios (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = ($conn->errno == 1062) ? "Ese tipo de servicio ya existe." : "Error: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container">
    <h2>Registrar Tipo de Servicio</h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nombre del Servicio:</label>
            <input type="text" name="nombre" required placeholder="Ej. Mantenimiento Preventivo">
        </div>
        
        <div class="form-group">
            <label>Descripción (Opcional):</label>
            <input type="text" name="descripcion" placeholder="Breve descripción...">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php include '../includes/footer.php'; ?>