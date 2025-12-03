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
    
    // 1. CAPTURAR EL NUEVO CAMPO precio_venta
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    
    // 1. CAPTURAR EL NUEVO CAMPO stock
    $stock = intval($_POST['stock'] ?? 0); 

    // VALIDACIÓN DE PRECIO Y STOCK
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser un valor positivo.";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo.";
    } else {
        // 2. MODIFICAR LA CONSULTA INSERT
        // Columnas añadidas: precio_venta y stock
        $sql = "INSERT INTO maquinas (nombre, codigo, modelo, descripcion, precio_venta, stock) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // Parámetros: ssss (strings) d (decimal/double) i (integer)
        $stmt->bind_param("ssssdi", $nombre, $codigo, $modelo, $descripcion, $precio_venta, $stock);
        
        if ($stmt->execute()) {
            $mensajeExito = "Máquina registrada exitosamente";
            
            // Limpiar variables POST después de éxito
            $_POST['nombre'] = $_POST['codigo'] = $_POST['modelo'] = $_POST['descripcion'] = $_POST['precio_venta'] = $_POST['stock'] = '';
        } else {
            // Error común: Duplicidad de código
            if ($conn->errno == 1062) { 
                $error = "Error: El código de máquina ya existe.";
            } else {
                $error = "Error al registrar la máquina: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Agregar nueva máquina</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            <script>
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            </script>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre de la Máquina:</label>
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
            <label for="descripcion">Descripción de la Máquina:</label>
            <input type="text" id="descripcion" name="descripcion" value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>