<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();

if (!$prod) { header("Location: index.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $modelo = limpiar($_POST['modelo']);
    $descripcion = limpiar($_POST['descripcion']);
    $precio = floatval($_POST['precio_venta']);
    $tipo = limpiar($_POST['tipo']);

    $sql = "UPDATE productos SET nombre=?, modelo=?, descripcion=?, precio_venta=?, tipo=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsi", $nombre, $modelo, $descripcion, $precio, $tipo, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar: <?= htmlspecialchars($prod['nombre']) ?></h2>
    
    <form method="POST">
        <div class="form-group">
            <label>Código (No editable):</label>
            <input type="text" value="<?= htmlspecialchars($prod['codigo']) ?>" disabled style="background:#eee;">
        </div>

        <div class="form-group">
            <label>Tipo:</label>
            <select name="tipo" required>
                <option value="repuesto" <?= $prod['tipo']=='repuesto'?'selected':'' ?>>Repuesto</option>
                <option value="maquina" <?= $prod['tipo']=='maquina'?'selected':'' ?>>Máquina</option>
                <option value="servicio" <?= $prod['tipo']=='servicio'?'selected':'' ?>>Servicio</option>
            </select>
        </div>

        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($prod['nombre']) ?>" required>
        </div>

        <div class="form-group">
            <label>Modelo:</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($prod['modelo']) ?>">
        </div>

        <div class="form-group">
            <label>Precio Venta ($):</label>
            <input type="number" name="precio_venta" step="0.01" value="<?= $prod['precio_venta'] ?>" required>
        </div>

        <div class="form-group">
            <label>Stock Actual:</label>
            <input type="text" value="<?= $prod['stock'] ?>" disabled style="background:#eee; width:100px;">
        </div>

        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion" rows="3"><?= htmlspecialchars($prod['descripcion']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>
<?php include '../includes/footer.php'; ?>