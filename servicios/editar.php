<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$sql = "SELECT * FROM servicios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$servicio = $result->fetch_assoc();

if (!$servicio) {
    header("Location: index.php?error=servicio_no_encontrado");
    exit();
}

$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $empresa_id = intval($_POST['empresa_id']);
    $descripcion = limpiar($_POST['descripcion']);
    $fecha = limpiar($_POST['fecha']);

    $sql = "UPDATE servicios SET empresa_id = ?, descripcion = ?, fecha = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $empresa_id, $descripcion, $fecha, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=servicio_actualizado");
        exit();
    } else {
        $error = "Error al actualizar el servicio";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2><?= isset($servicio) ? 'Editar Servicio' : 'Registrar Nuevo Servicio' ?></h2>
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="empresa_id">Empresa:</label>
            <select id="empresa_id" name="empresa_id" required>
                <?php while($empresa = $empresas->fetch_assoc()): ?>
                <option value="<?= $empresa['id'] ?>" 
                    <?= $empresa['id'] == $servicio['empresa_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($empresa['nombre']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" 
                   value="<?= date('Y-m-d', strtotime($servicio['fecha'])) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required><?= 
                htmlspecialchars($servicio['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<script>

document.addEventListener('DOMContentLoaded', function() { //esta funcion la busque para bloquear fechas futuras
    const fechaInput = document.getElementById('fecha');
    if (fechaInput) {
        const today = new Date().toISOString().split('T')[0];
        fechaInput.max = today;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
