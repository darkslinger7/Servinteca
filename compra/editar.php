<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';

$id_compra = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
$errores = [];
$compra = null;
$cantidad_antigua = 0; 

// A. Cargar datos de la compra existente
if ($id_compra > 0) {
    $sql_load = "SELECT id_compra, codigo_producto, fecha_compra, cantidad, precio_compra_unitario 
                 FROM compra 
                 WHERE id_compra = ?";
    $stmt_load = $conn->prepare($sql_load);
    $stmt_load->bind_param("i", $id_compra);
    $stmt_load->execute();
    $result_load = $stmt_load->get_result();
    
    if ($result_load->num_rows == 1) {
        $compra = $result_load->fetch_assoc();
        $cantidad_antigua = $compra['cantidad']; 
    } else {
        $errores[] = "Registro de compra no encontrado.";
        $id_compra = 0; 
    }
    $stmt_load->close();
} else {
    $errores[] = "ID de compra no especificado.";
}

// B. Procesar el formulario POST de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && $id_compra > 0) {
    
    $id_compra_post = (int)$_POST['id_compra'];
    $codigo_producto = trim($_POST['codigo_producto']);
    $fecha_compra = trim($_POST['fecha_compra']);
    $nueva_cantidad = (int)$_POST['cantidad'];
    $precio_compra_unitario = (float)$_POST['precio_compra_unitario'];
    $cantidad_original_post = (int)$_POST['cantidad_original'];

    if ($nueva_cantidad <= 0 || $precio_compra_unitario <= 0) {
        $errores[] = "La cantidad y el precio deben ser valores positivos.";
    }

    if (empty($errores)) {
        try {
            $conn->begin_transaction();
            
            // 1. Calcular la diferencia neta para el stock
            $diferencia_stock = $nueva_cantidad - $cantidad_original_post;
            
            // 2. Obtener el tipo de producto para actualizar el stock
            $stmt_get_type = $conn->prepare("SELECT tipo_producto FROM producto WHERE codigo_unificado = ?");
            $stmt_get_type->bind_param("s", $codigo_producto);
            $stmt_get_type->execute();
            $stmt_get_type->bind_result($tipo_producto);
            $stmt_get_type->fetch();
            $stmt_get_type->close();

            $tabla = ($tipo_producto === 'maquina') ? 'maquinas' : 'repuestos';
            
            // 3. Actualizar el Stock (Aplicar la diferencia neta: si es +, suma; si es -, resta)
            $sql_update_stock = "UPDATE {$tabla} SET stock = stock + ? WHERE codigo = ?";
            $stmt_stock = $conn->prepare($sql_update_stock);
            $stmt_stock->bind_param("is", $diferencia_stock, $codigo_producto);
            $stmt_stock->execute();
            $stmt_stock->close();

            // 4. Actualizar la tabla 'compra'
            $sql_update_compra = "UPDATE compra SET 
                                    codigo_producto = ?, 
                                    fecha_compra = ?, 
                                    cantidad = ?, 
                                    precio_compra_unitario = ? 
                                 WHERE id_compra = ?";
            $stmt_compra = $conn->prepare($sql_update_compra);
            $stmt_compra->bind_param("ssid", $codigo_producto, $fecha_compra, $nueva_cantidad, $precio_compra_unitario, $id_compra_post);
            $stmt_compra->execute();
            $stmt_compra->close();
            
            $conn->commit();
            $_SESSION['mensaje_exito'] = "Compra ID {$id_compra_post} actualizada y stock ajustado correctamente.";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errores[] = "Error al actualizar la compra y stock: " . $e->getMessage();
            $compra = $_POST; 
        }
    } else {
        $compra = $_POST;
    }
}

include '../includes/header.php'; 
?>

<div class="container mt-5">
    <h2>Editar Registro de Compra (ID: <?= $id_compra ?>)</h2>

    <?php 
    if (!empty($errores)) {
        echo '<div class="alert alert-danger" role="alert">';
        foreach ($errores as $error) {
            echo "<p>{$error}</p>";
        }
        echo '</div>';
    }
    ?>

    <?php if ($compra): ?>
        <form action="editar.php?id=<?= $id_compra ?>" method="POST">
            <input type="hidden" name="id_compra" value="<?= htmlspecialchars($compra['id_compra'] ?? $id_compra) ?>">
            <input type="hidden" name="cantidad_original" value="<?= htmlspecialchars($cantidad_antigua) ?>">
            
            <div class="mb-3">
                <label for="codigo_producto" class="form-label">Código Producto</label>
                <input type="text" class="form-control" id="codigo_producto" name="codigo_producto" 
                       value="<?= htmlspecialchars($compra['codigo_producto']) ?>" required readonly>
                <div class="form-text">El código de producto no se puede cambiar en la edición.</div>
            </div>

            <div class="mb-3">
                <label for="fecha_compra" class="form-label">Fecha de Compra</label>
                <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" 
                       value="<?= htmlspecialchars($compra['fecha_compra']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label">Nueva Cantidad Comprada</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" 
                       value="<?= htmlspecialchars($compra['cantidad']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="precio_compra_unitario" class="form-label">Nuevo Precio Unitario de Compra (Costo)</label>
                <input type="number" step="0.01" class="form-control" id="precio_compra_unitario" name="precio_compra_unitario" min="0.01" 
                       value="<?= htmlspecialchars($compra['precio_compra_unitario']) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">No se puede cargar el formulario de edición. <?= implode(' ', $errores) ?></div>
    <?php endif; ?>
</div>

<?php 
include '../includes/footer.php'; 
?>