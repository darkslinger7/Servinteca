<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Capturamos el CODIGO de la máquina a editar desde la URL
$codigo_actual = isset($_GET['codigo']) ? limpiar($_GET['codigo']) : '';
$error = '';

// --- 1. OBTENER DATOS DE LA MÁQUINA POR CÓDIGO ---
// Usamos el codigo como la clave para SELECT
$sql = "SELECT * FROM maquinas WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_actual);
$stmt->execute();
$result = $stmt->get_result();
$maquina = $result->fetch_assoc(); 

if (!$maquina) {
    header("Location: index.php?error=maquina_no_encontrada");
    exit();
}

// Guardamos el código original para que el formulario pueda pre-llenarlo
$codigo_original = $maquina['codigo']; 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. CAPTURAR Y LIMPIAR NUEVOS VALORES
    // El 'codigo' viene del campo oculto y es el ORIGINAL, no debe cambiar
    $nombre = limpiar($_POST['nombre'] ?? '');
    $codigo_nuevo = limpiar($_POST['codigo_original'] ?? ''); // ¡Usamos el código original de la DB!
    $modelo = limpiar($_POST['modelo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    $stock = intval($_POST['stock'] ?? 0); 
    
    // Validación de precio y stock
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser un valor positivo.";
    } elseif ($stock < 0) {
        $error = "El stock no puede ser negativo.";
    } else {
        // 3. CONSULTA UPDATE
        // Actualizamos todas las columnas. La cláusula WHERE utiliza el CÓDIGO ORIGINAL.
        $sql = "UPDATE maquinas 
                SET nombre = ?, modelo = ?, descripcion = ?, precio_venta = ?, stock = ? 
                WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        
        // Parámetros: sssdi (4 strings + 1 decimal/double + 1 integer). El código es el último 's'.
        $stmt->bind_param("sssdid", $nombre, $modelo, $descripcion, $precio_venta, $stock, $codigo_original);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=maquina_actualizada");
            exit();
        } else {
            $error = "Error al actualizar la máquina: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Máquina (Código: <?= htmlspecialchars($codigo_original) ?>)</h2> 
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        
        <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($codigo_original) ?>">

        <div class="form-group">
            <label for="nombre">Nombre de la Máquina:</label>
            <input type="text" id="nombre" name="nombre" 
                   value="<?= htmlspecialchars($_POST['nombre'] ?? $maquina['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" name="codigo_display" 
                   value="<?= htmlspecialchars($codigo_original) ?>" required readonly style="background-color: #f0f0f0;">
        </div>

        <div class="form-group">
            <label for="modelo">Modelo:</label>
            <input type="text" id="modelo" name="modelo" 
                   value="<?= htmlspecialchars($_POST['modelo'] ?? $maquina['modelo']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" 
                   value="<?= htmlspecialchars($_POST['precio_venta'] ?? $maquina['precio_venta']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="stock">Cantidad Disponible (Stock):</label>
            <input type="number" id="stock" name="stock" min="0" 
                   value="<?= htmlspecialchars($_POST['stock'] ?? $maquina['stock']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción de la Máquina:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required><?= 
                htmlspecialchars($_POST['descripcion'] ?? $maquina['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar Máquina</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>