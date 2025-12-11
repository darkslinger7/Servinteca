<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

$sql = "SELECT c.*, p.nombre as proveedor FROM compras c LEFT JOIN proveedores p ON c.id_proveedor = p.id ORDER BY c.fecha_compra DESC, c.id DESC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Registro de Compras</h2>
    <?php if(isset($_GET['success'])): ?><div class="alert success" id="alert-msg">Operación exitosa.</div><?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new"><i class="fas fa-plus"></i> Registrar Compra</a>
        <input type="text" id="buscar" placeholder="Buscar factura o proveedor..." onkeyup="filtrar()">
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Fecha</th><th>Factura</th><th>Proveedor</th><th>Total</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['fecha_compra'])) ?></td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($row['num_factura']) ?></td>
                    <td><?= htmlspecialchars($row['proveedor'] ?? '-') ?></td>
                    <td style="color:green; font-weight:bold;">$<?= number_format($row['total'], 2) ?></td>
                    <td class="actions">
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
        <h3>Anular Compra</h3>
        <p>¿Estás seguro? Se restará el stock ingresado.</p>
        <div class="modal-actions">
            <button id="confirmCancel" class="btn secondary">Cancelar</button>
            <button id="confirmDelete" class="btn danger">Anular</button>
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

function filtrar() {
    const filter = document.getElementById('buscar').value.toUpperCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.innerText.toUpperCase().includes(filter) ? '' : 'none';
    });
}
</script>
<?php include '../includes/footer.php'; ?>