<?php
session_start();
require_once '../includes/database.php';
// Asegúrate de que la ruta a functions.php sea correcta
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
} else {
    function limpiar($data) { return htmlspecialchars(trim($data)); }
}

$mensajeExito = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    
    // 1. CONSTRUCCIÓN DEL RIF
    $rif_tipo = $_POST['rif_tipo'];
    $rif_numero = limpiar($_POST['rif_numero']);
    $rif = $rif_tipo . '-' . $rif_numero;

    $direccion = limpiar($_POST['direccion']);
    
    // 2. CONSTRUCCIÓN DEL TELÉFONO
    $telefono_input = limpiar($_POST['telefono']);
    // Validamos que sean solo números
    if (!empty($telefono_input) && !is_numeric($telefono_input)) {
         $error = "El teléfono solo debe contener números.";
    }
    // Solo agregamos el prefijo si hay número
    $telefono = !empty($telefono_input) ? "+58" . $telefono_input : '';

    $email = limpiar($_POST['email']);
    
    // VALIDACIÓN DE EMAIL
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    }

    // SI NO HAY ERRORES, INSERTAMOS
    if (empty($error)) {
        // SQL CORREGIDO: Eliminado 'persona_contacto'
        $sql = "INSERT INTO empresas (nombre, rif, direccion, telefono, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // BIND CORREGIDO: "sssss" (5 strings) y solo 5 variables
        $stmt->bind_param("sssss", $nombre, $rif, $direccion, $telefono, $email);
        
        if ($stmt->execute()) {
            $mensajeExito = "Empresa creada exitosamente";
            $_POST = array(); // Limpiar formulario
        } else {
            if ($conn->errno == 1062) {
                $error = "Error: Ese RIF ya está registrado en el sistema.";
            } else {
                $error = "Error al registrar: " . $conn->error;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nueva Empresa</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            <script>setTimeout(() => { window.location.href = 'index.php?success=empresa_creada'; }, 2000);</script>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Razón Social / Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required placeholder="Ej. Inversiones Servindteca C.A.">
        </div>
        
        <div class="form-group">
            <label>RIF:</label>
            <div style="display: flex; gap: 10px;">
                <select name="rif_tipo" style="width: 80px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="J" <?= (($_POST['rif_tipo'] ?? '') == 'J') ? 'selected' : '' ?>>J</option>
                    <option value="G" <?= (($_POST['rif_tipo'] ?? '') == 'G') ? 'selected' : '' ?>>G</option>
                    <option value="V" <?= (($_POST['rif_tipo'] ?? '') == 'V') ? 'selected' : '' ?>>V</option>
                    <option value="E" <?= (($_POST['rif_tipo'] ?? '') == 'E') ? 'selected' : '' ?>>E</option>
                </select>
                <input type="text" name="rif_numero" value="<?= htmlspecialchars($_POST['rif_numero'] ?? '') ?>" required placeholder="Ej. 123456789" pattern="[0-9]+" title="Solo números">
            </div>
        </div>

        <div class="form-group">
            <label for="direccion">Dirección Fiscal:</label>
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background: #eee; padding: 0.8rem 1rem; border: 1px solid #d1d5db; border-radius: 6px;">+58</span>
                <input type="number" id="telefono" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" placeholder="4141234567" style="flex: 1;">
            </div>
            <small style="color: #666;">Ingresa el número sin el 0 inicial</small>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="empresa@dominio.com">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>