<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

$sql = "SELECT * FROM productos ORDER BY nombre ASC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Catálogo de Productos</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success" id="alert-msg">Operación realizada exitosamente.</div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error" id="alert-msg"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new"><i class="fas fa-plus"></i> Nuevo Producto</a>
        <a href="../compra/crear.php" class="btn-secondary"><i class="fas fa-shopping-cart"></i> Reponer Stock</a>
        <input type="text" id="buscar-producto" placeholder="Buscar producto..." onkeyup="filtrarProductos()">
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Nombre / Modelo</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:bold; color:#555;"><?= htmlspecialchars($row['codigo']) ?></td>
                    <td>
                        <span style="padding:2px 6px; border-radius:4px; font-size:0.85rem; background:<?= $row['tipo']=='maquina'?'#e3f2fd':($row['tipo']=='servicio'?'#f3e5f5':'#fff3e0') ?>">
                            <?= ucfirst($row['tipo']) ?>
                        </span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($row['nombre']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($row['modelo'] ?? '') ?></small>
                    </td>
                    <td>$<?= number_format($row['precio_venta'], 2) ?></td>
                    <td style="text-align: center;">
                        <?php if($row['tipo'] == 'servicio'): ?>
                            <span style="color:#999;">-</span>
                        <?php else: ?>
                            <span style="color:<?= $row['stock']<=5 ? '#d32f2f' : '#388e3c' ?>; font-weight:bold;">
                                <?= $row['stock'] ?>
                            </span>
                        <?php endif; ?>
                    </td>
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
        <h3>Eliminar Producto</h3>
        <p>¿Estás seguro? Se borrará del catálogo si no tiene movimientos.</p>
        <div class="modal-actions">
            <button id="confirmCancel" class="btn-secondary">Cancelar</button>
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

function filtrarProductos() {
    const filter = document.getElementById('buscar-producto').value.toUpperCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.innerText.toUpperCase().includes(filter) ? '' : 'none';
    });
}
</script>
<?php include '../includes/footer.php'; ?>