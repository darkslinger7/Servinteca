<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$mensajeExito = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $rif = limpiar($_POST['rif']);

    $sql = "INSERT INTO empresas (nombre, rif) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nombre, $rif);
    
    if ($stmt->execute()) {
        $mensajeExito = "Empresa creada exitosamente";
     
        $_POST['nombre'] = '';
        $_POST['rif'] = '';
    } else {
        $error = "Error al registrar la empresa";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nueva Empresa</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success">
            <?= $mensajeExito ?>
            <script>
            
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            </script>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nombre">Nombre de la Empresa:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="rif">RIF:</label>
            <input type="text" id="rif" name="rif" value="<?= htmlspecialchars($_POST['rif'] ?? '') ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>