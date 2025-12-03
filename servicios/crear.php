<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';


$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $empresa_id = intval($_POST['empresa_id'] ?? 0);
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha = limpiar($_POST['fecha'] ?? '');

    if ($empresa_id <= 0) {
        $error = "Debe seleccionar una empresa válida";
    } elseif (empty($descripcion)) {
        $error = "La descripción es obligatoria";
    } elseif (empty($fecha)) {
        $error = "La fecha es obligatoria";
    } else {
       $sql = "INSERT INTO servicios (empresa_id, descripcion, fecha, usuario_id) 
        VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $empresa_id, $descripcion, $fecha, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Servicio creado exitosamente";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al registrar el servicio: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nuevo Servicio</h2>
    
    <?php if(isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert success">
            <?= $_SESSION['mensaje_exito'] ?>
            <?php unset($_SESSION['mensaje_exito']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="empresa_id">Empresa:</label>
            <select id="empresa_id" name="empresa_id" required>
                <option value="">Seleccione una empresa</option>
                <?php while($empresa = $empresas->fetch_assoc()): ?>
                <option value="<?= $empresa['id'] ?>" <?= ($_POST['empresa_id'] ?? '') == $empresa['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($empresa['nombre']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaInput = document.getElementById('fecha');
    if (fechaInput && !fechaInput.value) {
        const today = new Date().toISOString().split('T')[0];
        fechaInput.value = today;                               //fecha actual por defecto
        fechaInput.max = today;                                  //esta es para bloquear fechas errroneas futuras
    }
});
</script>

<?php include '../includes/footer.php'; ?>