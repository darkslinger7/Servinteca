<?php
session_start();
require_once '../includes/database.php';

// SEGURIDAD: Solo admin entra aquí
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: /Servindteca/index.php");
    exit();
}

$sql = "SELECT * FROM usuarios ORDER BY nombre_completo";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Gestión de Usuarios (Personal)</h2>
    
    <?php if(isset($_GET['success'])): ?><div class="alert success">Usuario registrado/actualizado.</div><?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new"><i class="fas fa-user-plus"></i> Nuevo Usuario</a>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario (Login)</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: 4px; background: <?= $row['rol']=='admin'?'#e0e7ff':'#dcfce7' ?>; color: <?= $row['rol']=='admin'?'#3730a3':'#166534' ?>; font-weight: bold; font-size: 0.85rem;">
                            <?= ucfirst($row['rol']) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="editar.php?id=<?= $row['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                            <button class="btn-danger btn-eliminar" data-id="<?= $row['id'] ?>"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Eliminar Usuario</h3>
        <p>¿Estás seguro? Este usuario perderá acceso al sistema.</p>
        <div class="modal-actions">
            <button id="confirmCancel" class="btn secondary">Cancelar</button>
            <button id="confirmDelete" class="btn danger">Eliminar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let idEliminar = null;
    const modal = document.getElementById('confirmModal');

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            idEliminar = this.dataset.id;
            modal.style.display = 'flex';
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', async () => {
        if (!idEliminar) return;
        try {
            const response = await fetch('eliminar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: idEliminar })
            });
            const data = await response.json();
            if (data.success) window.location.href = 'index.php?success=1';
            else { alert(data.error); modal.style.display = 'none'; }
        } catch (e) { alert('Error de conexión'); }
    });

    document.getElementById('confirmCancel').addEventListener('click', () => modal.style.display = 'none');
});
</script>
<?php include '../includes/footer.php'; ?>