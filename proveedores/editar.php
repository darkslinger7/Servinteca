<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

$stmt = $conn->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$proveedor = $stmt->get_result()->fetch_assoc();

if (!$proveedor) { header("Location: index.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $documento = limpiar($_POST['documento']);
    $direccion = limpiar($_POST['direccion']);
    $telefono = limpiar($_POST['telefono']);
    $email = limpiar($_POST['email']);
    $persona_contacto = limpiar($_POST['persona_contacto']);

    $sql = "UPDATE proveedores SET nombre=?, documento=?, direccion=?, telefono=?, email=?, persona_contacto=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $nombre, $documento, $direccion, $telefono, $email, $persona_contacto, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=actualizado");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Proveedor</h2>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Razón Social:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Documento:</label>
            <input type="text" name="documento" value="<?= htmlspecialchars($proveedor['documento']) ?>" required>
        </div>

        <div class="form-group">
            <label>Dirección:</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Persona de Contacto:</label>
            <input type="text" name="persona_contacto" value="<?= htmlspecialchars($proveedor['persona_contacto'] ?? '') ?>">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>