<?php
session_start();
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $documento = limpiar($_POST['documento']);
    $direccion = limpiar($_POST['direccion']);
    $telefono = limpiar($_POST['telefono']);
    $email = limpiar($_POST['email']);
    $persona_contacto = limpiar($_POST['persona_contacto']);

    if (empty($nombre) || empty($documento)) {
        $error = "Nombre y Documento son obligatorios.";
    } else {
        $sql = "INSERT INTO proveedores (nombre, documento, direccion, telefono, email, persona_contacto) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $nombre, $documento, $direccion, $telefono, $email, $persona_contacto);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=creado");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nuevo Proveedor</h2>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Razón Social / Nombre:</label>
            <input type="text" name="nombre" required placeholder="Ej. Epson Global Inc.">
        </div>
        
        <div class="form-group">
            <label>Documento (RIF / Tax ID):</label>
            <input type="text" name="documento" required placeholder="Ej. J-12345678-9 o ID-55500">
        </div>

        <div class="form-group">
            <label>Dirección:</label>
            <input type="text" name="direccion" placeholder="Dirección fiscal o de envío">
        </div>

        <div class="form-group">
            <label>Teléfono:</label>
            <input type="text" name="telefono" placeholder="+1 555-0199">
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" placeholder="contacto@proveedor.com">
        </div>

        <div class="form-group">
            <label>Persona de Contacto:</label>
            <input type="text" name="persona_contacto" placeholder="Nombre del vendedor asignado">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Guardar</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>