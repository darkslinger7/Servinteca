<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../includes/database.php';

$sql = "SELECT * FROM empresas ORDER BY nombre";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Empresas Registradas (Clientes)</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success" id="alert-msg">Operación realizada exitosamente.</div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error" id="alert-msg">Error: <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Nueva Empresa
        </a>
        <input type="text" id="buscar-empresa" placeholder="Buscar por nombre, RIF..." onkeyup="filtrarEmpresas()">
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre / Razón Social</th>
                    <th>RIF</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($empresa = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($empresa['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($empresa['rif']) ?></td>
                    <td><small><?= htmlspecialchars($empresa['direccion'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($empresa['telefono'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($empresa['email'] ?? '-') ?></td>
                    
                    <td class="actions">
                        <a href="editar.php?id=<?= $empresa['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $empresa['id'] ?>" title="Eliminar"><i class="fas fa-trash"></i></button> 
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align:center;">No hay empresas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de eliminar esta empresa? Esta acción no se puede deshacer.</p>
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
        btn.addEventListener('click', function(e) {
            e.preventDefault();
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

            if (data.success) {
                window.location.href = 'index.php?success=1';
            } else {
                alert(data.error || 'Error al eliminar');
                modal.style.display = 'none';
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión');
        }
    });

    document.getElementById('confirmCancel').addEventListener('click', () => modal.style.display = 'none');
    
    const alerta = document.getElementById('alert-msg');
    if(alerta) setTimeout(() => alerta.style.display='none', 3000);
});

function filtrarEmpresas() {
    const filter = document.getElementById('buscar-empresa').value.toUpperCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().includes(filter) ? '' : 'none';
    });
}
</script>

<?php include '../includes/footer.php'; ?>