<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensajeExito = '';
$error = '';

if ($id <= 0) {
    header("Location: index.php?error=id_no_valido");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$empresa = $result->fetch_assoc();

if (!$empresa) {
    header("Location: index.php?error=empresa_no_encontrada");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $rif = limpiar($_POST['rif']);

    $sql = "UPDATE empresas SET 
            nombre = ?,
            rif = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nombre, $rif, $id);
    
    if ($stmt->execute()) {
        $mensajeExito = "Empresa actualizada exitosamente";
        $empresa['nombre'] = $nombre;
        $empresa['rif'] = $rif;
    } else {
        $error = "Error al actualizar la empresa";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Empresa: <?= htmlspecialchars($empresa['nombre']) ?></h2>
    
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
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empresa['nombre']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="rif">RIF:</label>
            <input type="text" id="rif" name="rif" value="<?= htmlspecialchars($empresa['rif']) ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Actualizar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>