<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';

$mensaje = '';
$tipoMensaje = '';

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    $tipoMensaje = 'success';
    unset($_SESSION['mensaje_exito']);
}

// Consulta unificada: Ventas + Nombre Cliente + Cantidad Items
$sql = "SELECT 
            v.id,
            v.fecha_venta, 
            v.num_comprobante,
            v.total, 
            e.nombre as cliente_nombre,
            (SELECT COUNT(*) FROM detalle_venta WHERE venta_id = v.id) as cant_items
        FROM ventas v
        JOIN empresas e ON v.empresas_id = e.id
        ORDER BY v.fecha_venta DESC, v.id DESC";

$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Registro de Ventas</h2>
    
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>" id="mensaje-temporal"><?= $mensaje ?></div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-cash-register"></i> Nueva Venta
        </a>
        <input type="text" id="buscar-venta" placeholder="Buscar venta..." onkeyup="filtrarVentas()">
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID / Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($venta = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:bold; color:#555;">
                        #<?= $venta['id'] ?> 
                        <small style="color:#888; display:block;"><?= htmlspecialchars($venta['num_comprobante'] ?? '') ?></small>
                    </td>
                    <td><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></td>
                    <td><?= htmlspecialchars($venta['cliente_nombre']) ?></td>
                    <td style="text-align:center;"><?= $venta['cant_items'] ?></td>
                    <td style="font-weight:bold; color:green;">$<?= number_format($venta['total'], 2) ?></td>
                    
                    <td class="actions">
                        <a href="factura.php?id=<?= $venta['id'] ?>" target="_blank" class="btn secondary" title="Imprimir" style="padding: 5px 10px;">
                            <i class="fas fa-print"></i>
                        </a>
                        
                        <button class="btn-danger btn-eliminar" data-id="<?= $venta['id'] ?>" title="Anular Venta">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align: center;">No hay ventas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mensaje = document.getElementById('mensaje-temporal');
    if (mensaje) setTimeout(() => mensaje.remove(), 3000);

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (confirm('ATENCIÓN: ¿Anular esta venta? \n\nEsto devolverá los productos al stock.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = this.dataset.id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

function filtrarVentas() {
    const filter = document.getElementById('buscar-venta').value.toUpperCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    });
}
</script>
<?php include '../includes/footer.php'; ?>