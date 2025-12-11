<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

$id = intval($_GET['id'] ?? 0);
$res = $conn->query("SELECT * FROM tipos_servicios WHERE id = $id");
$tipo = $res->fetch_assoc();
if (!$tipo) header("Location: index.php");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } else {
        $stmt = $conn->prepare("UPDATE tipos_servicios SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = "Error al actualizar.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container">
    <h2>Editar Tipo de Servicio</h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Nombre del Servicio:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($tipo['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Descripción:</label>
            <input type="text" name="descripcion" value="<?= htmlspecialchars($tipo['descripcion']) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php include '../includes/footer.php'; ?>