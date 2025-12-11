<?php
session_start();
require_once '../includes/database.php';
// Asegúrate de que la ruta sea correcta
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
} else {
    function limpiar($data) { return htmlspecialchars(trim($data)); }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensajeExito = '';
$error = '';

if ($id <= 0) { header("Location: index.php"); exit(); }

// Cargar datos actuales
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

if (!$empresa) { header("Location: index.php?error=no_existe"); exit(); }

// --- LOGICA VISUAL ---
// Separar RIF (Letra y Número)
$rif_actual_letra = strtoupper(substr($empresa['rif'], 0, 1));
$rif_actual_numero = preg_replace('/[^0-9]/', '', substr($empresa['rif'], 1));

// Separar Teléfono (Quitar +58)
$telefono_limpio = str_replace('+58', '', $empresa['telefono'] ?? '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    
    // Reconstruir RIF
    $rif = $_POST['rif_tipo'] . '-' . limpiar($_POST['rif_numero']);
    
    $direccion = limpiar($_POST['direccion']);
    
    // Reconstruir Teléfono
    $telefono_input = limpiar($_POST['telefono']);
    if (!empty($telefono_input) && !is_numeric($telefono_input)) {
         $error = "El teléfono solo debe contener números.";
    }
    $telefono = !empty($telefono_input) ? "+58" . $telefono_input : '';

    $email = limpiar($_POST['email']);

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
    }

    if (empty($error)) {
        // SQL SIN persona_contacto
        $sql = "UPDATE empresas SET nombre=?, rif=?, direccion=?, telefono=?, email=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // "sssssi" -> 5 strings, 1 int
        $stmt->bind_param("sssssi", $nombre, $rif, $direccion, $telefono, $email, $id);
        
        if ($stmt->execute()) {
            $mensajeExito = "Empresa actualizada correctamente";
            // Refrescar variables
            $empresa['nombre'] = $nombre;
            $rif_actual_letra = $_POST['rif_tipo'];
            $rif_actual_numero = $_POST['rif_numero'];
            $telefono_limpio = $telefono_input;
            $empresa['direccion'] = $direccion;
            $empresa['email'] = $email;
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Empresa: <?= htmlspecialchars($empresa['nombre']) ?></h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            <script>setTimeout(() => { window.location.href = 'index.php?success=empresa_actualizada'; }, 2000);</script>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empresa['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>RIF:</label>
            <div style="display: flex; gap: 10px;">
                <select name="rif_tipo" style="width: 80px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="J" <?= $rif_actual_letra == 'J' ? 'selected' : '' ?>>J</option>
                    <option value="G" <?= $rif_actual_letra == 'G' ? 'selected' : '' ?>>G</option>
                    <option value="V" <?= $rif_actual_letra == 'V' ? 'selected' : '' ?>>V</option>
                    <option value="E" <?= $rif_actual_letra == 'E' ? 'selected' : '' ?>>E</option>
                </select>
                <input type="text" name="rif_numero" value="<?= htmlspecialchars($rif_actual_numero) ?>" required pattern="[0-9]+" title="Solo números">
            </div>
        </div>

        <div class="form-group">
            <label for="direccion">Dirección:</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background: #eee; padding: 0.8rem 1rem; border: 1px solid #d1d5db; border-radius: 6px;">+58</span>
                <input type="number" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono_limpio) ?>" style="flex: 1;">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>