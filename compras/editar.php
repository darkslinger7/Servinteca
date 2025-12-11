<?php
session_start();
if (!isset($_SESSION['user_id'])) exit(header("Location: /Servindteca/auth/login.php"));
require_once '../includes/database.php';

$id_compra = (int)($_GET['id'] ?? 0);
$errores = [];
$compra = null;
$proveedores = $conn->query("SELECT id, nombre FROM proveedores ORDER BY nombre");

if ($id_compra > 0) {
    $stmt = $conn->prepare("SELECT * FROM compra WHERE id_compra = ?");
    $stmt->bind_param("i", $id_compra);
    $stmt->execute();
    $compra = $stmt->get_result()->fetch_assoc();
    if (!$compra) $errores[] = "Compra no encontrada.";
} else {
    $errores[] = "ID inválido.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errores)) {
    $nuevo_proveedor = (int)$_POST['id_proveedor'];
    $nueva_factura = $_POST['num_factura'];
    $nueva_fecha = $_POST['fecha_compra'];
    $nueva_cantidad = (int)$_POST['cantidad'];
    $nuevo_precio = (float)$_POST['precio_compra_unitario'];
    
    if ($nueva_cantidad <= 0) $errores[] = "Cantidad inválida.";
    
    if (empty($errores)) {
        try {
            $conn->begin_transaction();
            
            $diferencia = $nueva_cantidad - $compra['cantidad'];
            
            if ($diferencia != 0) {
                $stmt_tipo = $conn->prepare("SELECT tipo_producto FROM producto WHERE codigo_unificado = ?");
                $stmt_tipo->bind_param("s", $compra['codigo_producto']);
                $stmt_tipo->execute();
                $stmt_tipo->bind_result($tipo);
                $stmt_tipo->fetch();
                $stmt_tipo->close();
                
                $tabla = ($tipo == 'maquina') ? 'maquinas' : 'repuestos';
                $stmt_st = $conn->prepare("UPDATE {$tabla} SET stock = stock + ? WHERE codigo = ?");
                $stmt_st->bind_param("is", $diferencia, $compra['codigo_producto']);
                $stmt_st->execute();
            }
            
            $sql = "UPDATE compra SET id_proveedor=?, num_factura=?, fecha_compra=?, cantidad=?, precio_compra_unitario=? WHERE id_compra=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issidi", $nuevo_proveedor, $nueva_factura, $nueva_fecha, $nueva_cantidad, $nuevo_precio, $id_compra);
            $stmt->execute();
            
            $conn->commit();
            $_SESSION['mensaje_exito'] = "Compra actualizada correctamente.";
            header("Location: index.php");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errores[] = "Error: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="form-container">
    <h2>Editar Compra #<?= $id_compra ?></h2>
    <?php if($errores) foreach($errores as $e) echo "<div class='alert error'>$e</div>"; ?>
    
    <?php if($compra): ?>
    <form method="POST">
        <div class="form-group">
            <label>Proveedor:</label>
            <select name="id_proveedor" required>
                <?php while($p = $proveedores->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $compra['id_proveedor'] ? 'selected' : '' ?>><?= $p['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Factura:</label>
            <input type="text" name="num_factura" value="<?= htmlspecialchars($compra['num_factura']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Código Producto (Fijo):</label>
            <input type="text" value="<?= htmlspecialchars($compra['codigo_producto']) ?>" disabled style="background:#eee;">
        </div>
        
        <div class="form-group">
            <label>Fecha:</label>
            <input type="date" name="fecha_compra" value="<?= htmlspecialchars($compra['fecha_compra']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Cantidad:</label>
            <input type="number" name="cantidad" value="<?= htmlspecialchars($compra['cantidad']) ?>" required min="1">
            <small>Al cambiar esto, el stock se ajustará automáticamente.</small>
        </div>
        
        <div class="form-group">
            <label>Costo Unitario:</label>
            <input type="number" name="precio_compra_unitario" step="0.01" value="<?= htmlspecialchars($compra['precio_compra_unitario']) ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>