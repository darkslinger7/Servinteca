<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Variables para mensajes de éxito/error
$mensaje = '';
$tipoMensaje = '';

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    $tipoMensaje = 'success';
    unset($_SESSION['mensaje_exito']);
}

// --- CONSULTA SQL ARREGLADA ---
// Nota: Esta consulta sigue asumiendo que tienes una tabla 'producto' con 'codigo_unificado' 
// y que usas LEFT JOIN con 'repuestos' para obtener el nombre.
$sql = "SELECT 
            v.id,
            v.fecha_venta, 
            v.total, 
            v.descripcion,
            e.nombre as empresa_nombre,
            
            GROUP_CONCAT(
                CONCAT(
                    COALESCE(m.nombre, r.nombre), -- Usamos COALESCE para obtener nombre de Máquina o Repuesto
                    ' (x', dv.cantidad, ')'      
                )
                ORDER BY COALESCE(m.nombre, r.nombre) ASC 
                SEPARATOR ', '
            ) AS productos_vendidos,
            
            COUNT(dv.venta_id) AS num_productos_distintos
            
        FROM ventas v
        JOIN empresas e ON v.empresas_id = e.id
        JOIN detalle_venta dv ON v.id = dv.venta_id
        
        -- CAMBIOS DE JOIN UNIFICADOS:
        LEFT JOIN maquinas m ON dv.codigo_producto = m.codigo -- Intenta unir con máquinas
        LEFT JOIN repuestos r ON dv.codigo_producto = r.codigo -- Intenta unir con repuestos
        
        -- Agrupar por la cabecera de la venta
        GROUP BY v.id, v.fecha_venta, v.total, v.descripcion, e.nombre
        ORDER BY v.fecha_venta DESC, v.id DESC";

$result = $conn->query($sql);

// Manejo de errores de consulta
if ($result === false) {
    $mensaje = "Error al cargar las ventas: " . $conn->error;
    $tipoMensaje = 'error';
}
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Ventas Registradas</h2>
    
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>" id="mensaje-temporal">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Registrar Nueva Venta
        </a>
        <input type="text" id="buscar-venta" placeholder="Buscar venta (Cliente, Productos)..." onkeyup="filtrarVentas()">
    </div>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha Venta</th>
                    <th>Cliente</th> 
                    <th>Productos Vendidos</th> 
                    <th>Total Venta</th> 
                    <th>Descripción</th> 
                    <th>Acciones</th> 
                </tr>
            </thead>
            <tbody>
                <?php while($venta = $result->fetch_assoc()): ?>
                <tr data-id="<?= htmlspecialchars($venta['id']) ?>">
                    <td><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></td> 
                    <td><?= htmlspecialchars($venta['empresa_nombre']) ?></td> 
                    <td><?= htmlspecialchars($venta['productos_vendidos']) ?></td> 
                    <td>$<?= number_format($venta['total'], 2, ',', '.') ?></td> 
                    <td><?= htmlspecialchars(substr($venta['descripcion'], 0, 10)) ?>...</td>
                    <td class="actions">
                        <a href="editar.php?id=<?= $venta['id'] ?>" class="btn-edit">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="factura.php?id=<?= $venta['id'] ?>" class="btn-primary" target="_blank" title="Imprimir Factura">
                            <i class="fas fa-print"></i> Factura
                        </a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $venta['id'] ?>">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert info">No hay ventas registradas o hubo un error en la consulta.</div>
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
            
            if (confirm('¿Estás seguro de eliminar esta venta? Esta acción revertirá el stock de todos los productos asociados.')) { 
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


function filtrarVentas() {
    const input = document.getElementById('buscar-venta');
    const filter = input.value.toUpperCase();
    const table = document.querySelector('table');
    if (!table) return; 
    const tr = table.getElementsByTagName('tr');

    // i=1 para saltar el encabezado
    for (let i = 1; i < tr.length; i++) {
        let mostrarFila = false;
        // Revisamos las primeras 5 celdas: Fecha, Cliente, Productos, Total Venta, Descripción
        const celdas = tr[i].getElementsByTagName('td');
        
        // El bucle ahora solo va hasta 5 (índices 0 a 4)
        for (let j = 0; j < 5; j++) { 
            if (celdas[j]) {
                const txtValue = celdas[j].textContent || celdas[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    mostrarFila = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = mostrarFila ? "" : "none";
    }
}
</script>

<?php include '../includes/footer.php'; ?>