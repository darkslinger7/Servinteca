<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$codigo_actual = isset($_GET['codigo']) ? limpiar($_GET['codigo']) : '';
$error = '';

$sql = "SELECT * FROM repuestos WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_actual);
$stmt->execute();
$repuesto = $stmt->get_result()->fetch_assoc(); 

if (!$repuesto) {
    header("Location: index.php?error=repuesto_no_encontrado");
    exit();
}
$codigo_original = $repuesto['codigo']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $modelo = limpiar($_POST['modelo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser positivo.";
    } else {
        $sql = "UPDATE repuestos 
                SET nombre = ?, modelo = ?, descripcion = ?, precio_venta = ?
                WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("sssds", $nombre, $modelo, $descripcion, $precio_venta, $codigo_original);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=repuesto_actualizado");
            exit();
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Repuesto: <?= htmlspecialchars($codigo_original) ?></h2> 
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($codigo_original) ?>">

        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $repuesto['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Código (No editable):</label>
            <input type="text" value="<?= htmlspecialchars($codigo_original) ?>" disabled style="background-color: #e9ecef;">
        </div>

        <div class="form-group">
            <label for="modelo">Modelo / Compatibilidad:</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($_POST['modelo'] ?? $repuesto['modelo']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['precio_venta'] ?? $repuesto['precio_venta']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Stock Actual:</label>
            <input type="text" value="<?= htmlspecialchars($repuesto['stock']) ?>" disabled style="background-color: #e9ecef; font-weight: bold; color: #333;">
            <small style="color: #666;">Modifícalo mediante Compras o Ventas.</small>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" rows="3" required><?= htmlspecialchars($_POST['descripcion'] ?? $repuesto['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Actualizar Datos</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>