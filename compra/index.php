<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
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

$sql = "SELECT 
            c.id_compra,
            c.fecha_compra, 
            c.cantidad, 
            c.precio_compra_unitario,
            
            p.codigo_unificado,
            p.tipo_producto,

            COALESCE(m.nombre, r.nombre) AS nombre_producto,
            COALESCE(m.precio_venta, r.precio_venta) AS precio_venta_actual
            
        FROM compra c
        JOIN producto p ON c.codigo_producto = p.codigo_unificado
        LEFT JOIN maquinas m ON p.codigo_unificado = m.codigo AND p.tipo_producto = 'maquina'
        LEFT JOIN repuestos r ON p.codigo_unificado = r.codigo AND p.tipo_producto = 'repuesto'
        
        ORDER BY c.fecha_compra DESC, c.id_compra DESC";

$result = $conn->query($sql);

if ($result === false) {
    $mensaje = "Error al cargar las compras: " . $conn->error;
    $tipoMensaje = 'error';
}

?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Compras de Productos Registradas</h2>
    
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>" id="mensaje-temporal">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Registrar Nueva Compra
        </a>
        <input type="text" id="buscar-compra" placeholder="Buscar compra (Código, Producto, Categoría)..." onkeyup="filtrarCompras()">
    </div>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha Compra</th>
                    <th>Código</th> 
                    <th>Categoría</th> 
                    <th>Producto (Nombre)</th> 
                    <th>Cantidad</th> 
                    <th>Precio Costo (Unit.)</th> 
                    <th>Precio Venta (Actual)</th> 
                    <th>Acciones</th> 
                </tr>
            </thead>
            <tbody>
                <?php while($compra = $result->fetch_assoc()): ?>
                <tr data-id="<?= htmlspecialchars($compra['id_compra']) ?>">
                    <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td> 
                    <td><?= htmlspecialchars($compra['codigo_unificado']) ?></td> 
                    <td><?= ucfirst(htmlspecialchars($compra['tipo_producto'])) ?></td>
                    <td><?= htmlspecialchars($compra['nombre_producto']) ?></td> 
                    <td><?= htmlspecialchars($compra['cantidad']) ?></td> 
                    <td>$<?= number_format($compra['precio_compra_unitario'], 2, ',', '.') ?></td> 
                    <td>$<?= number_format($compra['precio_venta_actual'], 2, ',', '.') ?></td>
                    <td class="actions">
                        <a href="editar.php?id=<?= $compra['id_compra'] ?>" class="btn-edit" title="Editar Compra">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $compra['id_compra'] ?>">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert info">No hay compras registradas</div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const mensaje = document.getElementById('mensaje-temporal');
    if (mensaje) {
        mensaje.style.transition = 'opacity 0.5s ease-out'; 
        setTimeout(() => {
            mensaje.style.opacity = '0';
            setTimeout(() => mensaje.remove(), 500);
        }, 3000); 
    }

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar esta compra? Esta acción restará la cantidad comprada al stock del producto asociado.')) { 
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
        
        for (let j = 0; j < 7; j++) { 
            const txtValue = celdas[j].textContent || celdas[j].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                mostrarFila = true;
                break;
            }
        }
        
        tr[i].style.display = mostrarFila ? "" : "none";
    }
}
</script>

<?php include '../includes/footer.php'; ?>