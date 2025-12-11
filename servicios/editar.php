<?php
session_start();
require_once '../includes/database.php';

if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
} else {
    function limpiar($data) { return htmlspecialchars(trim($data)); }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar servicio actual
$sql = "SELECT * FROM servicios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$servicio = $stmt->get_result()->fetch_assoc();

if (!$servicio) {
    header("Location: index.php?error=servicio_no_encontrado");
    exit();
}

$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $empresa_id = intval($_POST['empresa_id']);
    $descripcion = limpiar($_POST['descripcion']);
    $fecha = limpiar($_POST['fecha']);
    
    // Nuevos campos
    $tipo_servicio = limpiar($_POST['tipo_servicio']);
    $equipo_atendido = limpiar($_POST['equipo_atendido']);
    $horas_uso = !empty($_POST['horas_uso']) ? intval($_POST['horas_uso']) : NULL;
    $proximo_servicio = !empty($_POST['proximo_servicio']) ? $_POST['proximo_servicio'] : NULL;

    // SQL Update actualizado
    $sql = "UPDATE servicios SET empresa_id=?, descripcion=?, fecha=?, tipo_servicio=?, equipo_atendido=?, horas_uso=?, proximo_servicio=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssisi", $empresa_id, $descripcion, $fecha, $tipo_servicio, $equipo_atendido, $horas_uso, $proximo_servicio, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=servicio_actualizado");
        exit();
    } else {
        $error = "Error al actualizar el servicio";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container" style="max-width: 800px;">
    <h2>Editar Servicio #<?= $id ?></h2>
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="empresa_id">Empresa:</label>
                <select id="empresa_id" name="empresa_id" required>
                    <?php while($empresa = $empresas->fetch_assoc()): ?>
                    <option value="<?= $empresa['id'] ?>" <?= $empresa['id'] == $servicio['empresa_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($empresa['nombre']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="equipo_atendido">Equipo Atendido:</label>
                <input type="text" name="equipo_atendido" value="<?= htmlspecialchars($servicio['equipo_atendido'] ?? '') ?>">
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="tipo_servicio">Tipo de Servicio:</label>
                <select id="tipo_servicio" name="tipo_servicio" required>
                    <?php 
                    $opciones = ["Mantenimiento Preventivo", "Mantenimiento Correctivo", "Instalación", "Capacitación"];
                    foreach($opciones as $op): 
                    ?>
                        <option value="<?= $op ?>" <?= ($servicio['tipo_servicio'] == $op) ? 'selected' : '' ?>><?= $op ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="horas_uso">Horas de Uso:</label>
                <input type="number" name="horas_uso" value="<?= htmlspecialchars($servicio['horas_uso'] ?? '') ?>">
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d', strtotime($servicio['fecha'])) ?>" required>
            </div>
             <div class="form-group" style="flex: 1;">
                <label for="proximo_servicio">Próximo Servicio:</label>
                <input type="date" name="proximo_servicio" value="<?= $servicio['proximo_servicio'] ? date('Y-m-d', strtotime($servicio['proximo_servicio'])) : '' ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required><?= htmlspecialchars($servicio['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>
<?php include '../includes/footer.php'; ?>