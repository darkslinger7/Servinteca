<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$mensaje = '';
$tipoMensaje = '';

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    $tipoMensaje = 'success';
    unset($_SESSION['mensaje_exito']);
}

// Consulta actualizada con JOIN a proveedores y campos nuevos
$sql = "SELECT 
            c.id_compra,
            c.num_factura,
            c.fecha_compra, 
            c.cantidad, 
            c.precio_compra_unitario,
            
            p.codigo_unificado,
            p.tipo_producto,

            COALESCE(m.nombre, r.nombre) AS nombre_producto,
            COALESCE(m.precio_venta, r.precio_venta) AS precio_venta_actual,
            
            prov.nombre AS nombre_proveedor
            
        FROM compra c
        JOIN producto p ON c.codigo_producto = p.codigo_unificado
        LEFT JOIN maquinas m ON p.codigo_unificado = m.codigo AND p.tipo_producto = 'maquina'
        LEFT JOIN repuestos r ON p.codigo_unificado = r.codigo AND p.tipo_producto = 'repuesto'
        LEFT JOIN proveedores prov ON c.id_proveedor = prov.id
        
        ORDER BY c.fecha_compra DESC, c.id_compra DESC";

$result = $conn->query($sql);

if ($result === false) {
    $mensaje = "Error al cargar las compras: " . $conn->error;
    $tipoMensaje = 'error';
}
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Registro de Compras</h2>
    
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>" id="mensaje-temporal">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Registrar Nueva Compra
        </a>
        <input type="text" id="buscar-compra" placeholder="Buscar (Factura, Proveedor, Producto)..." onkeyup="filtrarCompras()">
    </div>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>N° Factura</th> <th>Proveedor</th>  <th>Código</th> 
                        <th>Producto</th> 
                        <th>Cant.</th> 
                        <th>Costo Unit.</th> 
                        <th>P. Venta Actual</th> 
                        <th>Acciones</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php while($compra = $result->fetch_assoc()): ?>
                    <tr data-id="<?= htmlspecialchars($compra['id_compra']) ?>">
                        <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td> 
                        <td style="font-weight:bold; color:#555;"><?= htmlspecialchars($compra['num_factura']) ?></td>
                        <td><?= htmlspecialchars($compra['nombre_proveedor'] ?? 'S/N') ?></td>
                        
                        <td><small><?= htmlspecialchars($compra['codigo_unificado']) ?></small></td> 
                        <td>
                            <?= htmlspecialchars($compra['nombre_producto']) ?>
                            <br><small style="color:#888;"><?= ucfirst($compra['tipo_producto']) ?></small>
                        </td> 
                        
                        <td style="font-weight:bold; text-align:center;"><?= htmlspecialchars($compra['cantidad']) ?></td> 
                        <td>$<?= number_format($compra['precio_compra_unitario'], 2) ?></td> 
                        <td>$<?= number_format($compra['precio_venta_actual'], 2) ?></td>
                        
                        <td class="actions" style="white-space:nowrap;">
                            <a href="editar.php?id=<?= $compra['id_compra'] ?>" class="btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-danger btn-eliminar" data-id="<?= $compra['id_compra'] ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert info">No hay compras registradas.</div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mensaje = document.getElementById('mensaje-temporal');
    if (mensaje) {
        setTimeout(() => { mensaje.style.opacity = '0'; setTimeout(() => mensaje.remove(), 500); }, 3000); 
    }

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            if (confirm('ATENCIÓN: Eliminar esta compra RESTARÁ las unidades del inventario actual. ¿Desea continuar?')) { 
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'eliminar.php'; 
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

function filtrarCompras() {
    const input = document.getElementById('buscar-compra');
    const filter = input.value.toUpperCase();
    const table = document.querySelector('table');
    if (!table) return; 
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let mostrarFila = false;
        const celdas = tr[i].getElementsByTagName('td');
        for (let j = 0; j < celdas.length - 1; j++) { 
            if (celdas[j].textContent.toUpperCase().indexOf(filter) > -1) {
                mostrarFila = true; break;
            }
        }
        tr[i].style.display = mostrarFila ? "" : "none";
    }
}
</script>
<?php include '../includes/footer.php'; ?>