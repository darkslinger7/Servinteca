<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';
// Asegúrate de incluir functions.php si lo usas para limpiar()
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
} else {
    function limpiar($data) { return htmlspecialchars(trim($data)); }
}

$empresas = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $empresa_id = intval($_POST['empresa_id'] ?? 0);
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha = limpiar($_POST['fecha'] ?? '');
    
    // Nuevos Campos
    $tipo_servicio = limpiar($_POST['tipo_servicio'] ?? '');
    $equipo_atendido = limpiar($_POST['equipo_atendido'] ?? '');
    $horas_uso = !empty($_POST['horas_uso']) ? intval($_POST['horas_uso']) : NULL;
    $proximo_servicio = !empty($_POST['proximo_servicio']) ? $_POST['proximo_servicio'] : NULL;

    if ($empresa_id <= 0) {
        $error = "Debe seleccionar una empresa válida";
    } elseif (empty($descripcion)) {
        $error = "La descripción es obligatoria";
    } elseif (empty($fecha)) {
        $error = "La fecha es obligatoria";
    } else {
       
       $sql = "INSERT INTO servicios (empresa_id, descripcion, fecha, usuario_id, tipo_servicio, equipo_atendido, horas_uso, proximo_servicio) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
       $stmt = $conn->prepare($sql);
       
       $stmt->bind_param("ississis", $empresa_id, $descripcion, $fecha, $_SESSION['user_id'], $tipo_servicio, $equipo_atendido, $horas_uso, $proximo_servicio);
       
       if ($stmt->execute()) {
           $_SESSION['mensaje_exito'] = "Servicio creado exitosamente"; 
           header("Location: index.php?success=servicio_creado");
           exit();
       } else {
           $error = "Error al registrar el servicio: " . $conn->error;
       }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container" style="max-width: 800px;"> <h2>Registrar Nuevo Servicio</h2>
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="empresa_id">Empresa (Cliente):</label>
                <select id="empresa_id" name="empresa_id" required>
                    <option value="">Seleccione una empresa</option>
                    <?php while($empresa = $empresas->fetch_assoc()): ?>
                    <option value="<?= $empresa['id'] ?>" <?= ($_POST['empresa_id'] ?? '') == $empresa['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($empresa['nombre']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label for="equipo_atendido">Equipo Atendido:</label>
                <input type="text" id="equipo_atendido" name="equipo_atendido" placeholder="Ej. Epson L55" value="<?= htmlspecialchars($_POST['equipo_atendido'] ?? '') ?>">
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="tipo_servicio">Tipo de Servicio:</label>
                <select id="tipo_servicio" name="tipo_servicio" required>
                    <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                    <option value="Mantenimiento Correctivo">Mantenimiento Correctivo (Reparación)</option>
                    <option value="Instalación">Instalación / Puesta en Marcha</option>
                    <option value="Capacitación">Capacitación / Visita Técnica</option>
                </select>
            </div>

            <div class="form-group" style="flex: 1;">
                <label for="horas_uso">Horas de Uso (Contador):</label>
                <input type="number" id="horas_uso" name="horas_uso" placeholder="Ej. 1500" value="<?= htmlspecialchars($_POST['horas_uso'] ?? '') ?>">
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label for="fecha">Fecha del Servicio:</label>
                <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>" required>
            </div>

            <div class="form-group" style="flex: 1;">
                <label for="proximo_servicio">Próximo Servicio Sugerido:</label>
                <input type="date" id="proximo_servicio" name="proximo_servicio" value="<?= htmlspecialchars($_POST['proximo_servicio'] ?? '') ?>">
                <small style="color: #666;">(Opcional) Para agendar seguimiento.</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Detalle Técnico / Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Detalles técnicos del trabajo realizado..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Servicio</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaInput = document.getElementById('fecha');
    if (fechaInput && !fechaInput.value) {
        const today = new Date().toISOString().split('T')[0];
        fechaInput.value = today;
        fechaInput.max = today; 
    }
});
</script>

<?php include '../includes/footer.php'; ?>