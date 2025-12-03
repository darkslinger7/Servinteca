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
    
    $precio_venta = floatval($_POST['precio_venta'] ?? 0); 
    
    if ($precio_venta <= 0) {
        $error = "El precio de venta debe ser un valor positivo.";
    } else {
        // STOCK INICIAL SIEMPRE ES 0
        $stock_inicial = 0;

        $sql = "INSERT INTO repuestos (nombre, codigo, modelo, descripcion, precio_venta, stock) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ssssdi", $nombre, $codigo, $modelo, $descripcion, $precio_venta, $stock_inicial);
        
        if ($stmt->execute()) {
            $mensajeExito = "Repuesto registrado exitosamente (Stock inicial: 0)";
            $_POST = array(); 
        } else {
            if ($conn->errno == 1062) { 
                $error = "Error: El código de repuesto ya existe.";
            } else {
                $error = "Error al registrar: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nuevo Repuesto (Catálogo)</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            <script>setTimeout(() => { window.location.href = 'index.php'; }, 2000);</script>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre del Repuesto:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required placeholder="Ej. Filtro de tinta">
        </div>
        
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" required placeholder="Ej. RP-FLT-001">
        </div>

        <div class="form-group">
            <label for="modelo">Modelo / Compatibilidad:</label>
            <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>" required placeholder="Ej. Serie UX">
        </div>
        
        <div class="form-group">
            <label for="precio_venta">Precio de Venta ($):</label>
            <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['precio_venta'] ?? '') ?>" required>
        </div>
        
        <div class="alert info" style="font-size: 0.9em; margin-bottom: 15px;">
            <i class="fas fa-info-circle"></i> Nota: El stock inicial será 0. Para agregar unidades, ve al módulo de <strong>Compras</strong>.
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <input type="text" id="descripcion" name="descripcion" value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Crear Ficha</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>