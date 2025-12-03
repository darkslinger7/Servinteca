<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Capturamos el CODIGO del repuesto a editar desde la URL
$codigo_actual = isset($_GET['codigo']) ? limpiar($_GET['codigo']) : '';
$error = '';

// --- 1. OBTENER DATOS DEL REPUESTO POR CÓDIGO ---
// La consulta ahora busca en la tabla 'repuestos'
$sql = "SELECT * FROM repuestos WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_actual);
$stmt->execute();
$result = $stmt->get_result();
// Cambiamos la variable de $maquina a $repuesto
$repuesto = $result->fetch_assoc(); 

if (!$repuesto) {
    // MENSAJE DE ERROR: Cambiado a repuesto
    header("Location: index.php?error=repuesto_no_encontrado");
    exit();
}

// Guardamos el código original para que el formulario pueda pre-llenarlo
$codigo_original = $repuesto['codigo']; 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. CAPTURAR Y LIMPIAR NUEVOS VALORES
    // El 'codigo_original' viene del campo oculto y es la clave de búsqueda
    $nombre = limpiar($_POST['nombre'] ?? '');
    // $codigo_nuevo = limpiar($_POST['codigo_original'] ?? ''); // Este campo no se usa realmente en el UPDATE
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
        // La consulta ahora actualiza la tabla 'repuestos'
        $sql = "UPDATE repuestos 
                SET nombre = ?, modelo = ?, descripcion = ?, precio_venta = ?, stock = ? 
                WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        
        // Parámetros: sssdi (4 strings + 1 decimal/double + 1 integer). El código es el último 's'.
        $stmt->bind_param("sssdid", $nombre, $modelo, $descripcion, $precio_venta, $stock, $codigo_original);
        
        if ($stmt->execute()) {
            // REDIRECCIÓN: Cambiado el mensaje de éxito a repuesto
            header("Location: index.php?success=repuesto_actualizado");
            exit();
        } else {
            // MENSAJE DE ERROR: Cambiado a repuesto
            $error = "Error al actualizar el repuesto: " . $conn->error;
        }
    }
}
// Si la actualización falla, recargamos la variable $repuesto con los datos POST para mantener el formulario.
// Si no hay error, $repuesto sigue conteniendo los datos originales.
if ($error && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $repuesto = [
        'nombre' => $nombre,
        'codigo' => $codigo_original,
        'modelo' => $modelo,
        'descripcion' => $descripcion,
        'precio_venta' => $precio_venta,
        'stock' => $stock
    ];
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Repuesto (Código: <?= htmlspecialchars($codigo_original) ?>)</h2> 
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        
        <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($codigo_original) ?>">

        <div class="form-group">
            <label for="nombre">Nombre del Repuesto:</label>
            <input type="text" id="nombre" name="nombre" 
                    value="<?= htmlspecialchars($_POST['nombre'] ?? $repuesto['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" name="codigo_display" 
                    value="<?= htmlspecialchars($codigo_original) ?>" required readonly style="background-color: #f0f0f0;">
        </div>

        <div class="form-group">
            <label for="modelo">Modelo:</label>
            <input type="text" id="modelo" name="modelo" 
                    value="<?= htmlspecialchars($_POST['modelo'] ?? $repuesto['modelo']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" 
                    value="<?= htmlspecialchars($_POST['precio_venta'] ?? $repuesto['precio_venta']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="stock">Cantidad Disponible (Stock):</label>
            <input type="number" id="stock" name="stock" min="0" 
                    value="<?= htmlspecialchars($_POST['stock'] ?? $repuesto['stock']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción del Repuesto:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required><?= 
                htmlspecialchars($_POST['descripcion'] ?? $repuesto['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar Repuesto</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>