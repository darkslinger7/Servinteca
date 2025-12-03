<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$mensajeExito = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $codigo = limpiar($_POST['codigo']);
    $modelo = limpiar($_POST['modelo']);
    $descripcion = limpiar($_POST['descripcion']);
    
    // 1. CAPTURAR CAMPOS
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    $stock = intval($_POST['stock'] ?? 0); 

    // VALIDACIÓN DE PRECIO Y STOCK
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser un valor positivo.";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo.";
    } else {
        // 2. MODIFICAR LA CONSULTA INSERT para la tabla 'repuestos'
        $sql = "INSERT INTO repuestos (nombre, codigo, modelo, descripcion, precio_venta, stock) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // Parámetros: ssss (strings) d (decimal/double) i (integer)
        $stmt->bind_param("ssssdi", $nombre, $codigo, $modelo, $descripcion, $precio_venta, $stock);
        
        if ($stmt->execute()) {
            // MENSAJE DE ÉXITO: Cambiado a Repuesto
            $mensajeExito = "Repuesto registrado exitosamente";
            
            // Redirección al index con mensaje de éxito de creación de repuesto
            echo "<script>window.location.href = 'index.php?success=repuesto_creado';</script>";
            exit(); 
        } else {
            // Error común: Duplicidad de código
            if ($conn->errno == 1062) { 
                // MENSAJE DE ERROR: Cambiado a Repuesto
                $error = "Error: El código de repuesto ya existe.";
            } else {
                // MENSAJE DE ERROR: Cambiado a Repuesto
                $error = "Error al registrar el repuesto: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Agregar nuevo repuesto</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre del Repuesto:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="modelo">Modelo:</label>
            <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['precio_venta'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="stock">Cantidad Disponible (Stock):</label>
            <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción del Repuesto:</label>
            <input type="text" id="descripcion" name="descripcion" value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>