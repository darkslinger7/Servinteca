<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$codigo_actual = isset($_GET['codigo']) ? limpiar($_GET['codigo']) : '';
$error = '';

$sql = "SELECT * FROM maquinas WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_actual);
$stmt->execute();
$maquina = $stmt->get_result()->fetch_assoc(); 

if (!$maquina) {
    header("Location: index.php?error=maquina_no_encontrada");
    exit();
}
$codigo_original = $maquina['codigo']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // NO CAPTURAMOS EL STOCK DEL POST PARA EVITAR TRAMPAS
    $nombre = limpiar($_POST['nombre'] ?? '');
    // El codigo no se edita en el UPDATE WHERE, se usa el original
    $modelo = limpiar($_POST['modelo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser positivo.";
    } else {
        // SQL UPDATE SIN TOCAR EL STOCK
        $sql = "UPDATE maquinas 
                SET nombre = ?, modelo = ?, descripcion = ?, precio_venta = ?
                WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        
        // Parámetros: sssds (nombre, modelo, desc, precio, codigo_original)
        $stmt->bind_param("sssds", $nombre, $modelo, $descripcion, $precio_venta, $codigo_original);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=maquina_actualizada");
            exit();
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Máquina: <?= htmlspecialchars($codigo_original) ?></h2> 
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($codigo_original) ?>">

        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $maquina['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Código (No editable):</label>
            <input type="text" value="<?= htmlspecialchars($codigo_original) ?>" disabled style="background-color: #e9ecef; cursor: not-allowed;">
        </div>

        <div class="form-group">
            <label for="modelo">Modelo:</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($_POST['modelo'] ?? $maquina['modelo']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['precio_venta'] ?? $maquina['precio_venta']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Stock Actual (Controlado por Compras/Ventas):</label>
            <input type="text" value="<?= htmlspecialchars($maquina['stock']) ?>" disabled style="background-color: #e9ecef; font-weight: bold; color: #333;">
            <small style="color: #666;">Para ajustar el inventario, use el módulo de Compras o realice una Venta.</small>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" rows="3" required><?= htmlspecialchars($_POST['descripcion'] ?? $maquina['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Actualizar Datos</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>