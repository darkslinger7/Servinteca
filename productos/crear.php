<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = limpiar($_POST['tipo']);
    $codigo = limpiar($_POST['codigo']);
    $nombre = limpiar($_POST['nombre']);
    $modelo = limpiar($_POST['modelo']);
    $descripcion = limpiar($_POST['descripcion']);
    $precio = floatval($_POST['precio_venta']);
    
    // Stock inicial siempre 0 para productos físicos
    $stock = 0; 

    if (empty($codigo) || empty($nombre)) {
        $error = "Código y Nombre son obligatorios.";
    } else {
        $sql = "INSERT INTO productos (codigo, nombre, tipo, modelo, descripcion, precio_venta, stock) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssdi", $codigo, $nombre, $tipo, $modelo, $descripcion, $precio, $stock);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            // Error 1062 = Código duplicado
            $error = ($conn->errno == 1062) ? "Error: El código '$codigo' ya existe." : "Error: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nuevo Producto</h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Tipo de Producto:</label>
            <select name="tipo" required onchange="toggleStock(this.value)">
                <option value="repuesto">Repuesto</option>
                <option value="maquina">Máquina</option>
                <option value="servicio">Servicio (Mano de obra)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Código Único:</label>
            <input type="text" name="codigo" required placeholder="Ej. REF-001">
        </div>

        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="nombre" required placeholder="Ej. Filtro de Aceite">
        </div>

        <div class="form-group">
            <label>Modelo (Opcional):</label>
            <input type="text" name="modelo" placeholder="Ej. Serie X500">
        </div>

        <div class="form-group">
            <label>Precio Venta ($):</label>
            <input type="number" name="precio_venta" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion" rows="3"></textarea>
        </div>
        
        <div class="alert info" id="msg-stock">
            <i class="fas fa-info-circle"></i> El stock inicial será <b>0</b>. Para agregar unidades, use el módulo de <b>Compras</b>.
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<script>
function toggleStock(tipo) {
    const msg = document.getElementById('msg-stock');
    if (tipo === 'servicio') {
        msg.style.display = 'none'; // Servicios no manejan stock visible
    } else {
        msg.style.display = 'flex';
    }
}
</script>

<?php include '../includes/footer.php'; ?>