<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

$sql = "SELECT * FROM tipos_servicios ORDER BY nombre ASC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Catálogo de Tipos de Servicio</h2>
    
    <?php if(isset($_GET['success'])): ?><div class="alert success" id="alert-msg">Operación exitosa.</div><?php endif; ?>
    <?php if(isset($_GET['error'])): ?><div class="alert error" id="alert-msg"><?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new"><i class="fas fa-plus"></i> Nuevo Tipo</a>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre del Servicio</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($row['descripcion'] ?? '-') ?></td>
                    <td class="actions">
                        <a href="editar.php?id=<?= $row['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $row['id'] ?>"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Eliminar Tipo de Servicio</h3>
        <p>¿Estás seguro? Esto podría afectar la creación de futuros servicios.</p>
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
    const alerta = document.getElementById('alert-msg');
    if(alerta) setTimeout(() => alerta.style.display='none', 3000);
});
</script>
<?php include '../includes/footer.php'; ?>