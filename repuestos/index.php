<?php
session_start();
// Validación de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';

// Consultamos todos los repuestos
$sql = "SELECT * FROM repuestos ORDER BY nombre";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Inventario de Repuestos</h2>
   
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success">
            <?php 
            switch($_GET['success']) {
                case 'repuesto_eliminado': echo "Repuesto eliminado del catálogo."; break; 
                case 'repuesto_actualizado': echo "Ficha técnica actualizada."; break;
                default: echo "Operación realizada con éxito."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error">
            <?php 
            switch($_GET['error']) {
                case 'repuesto_no_encontrado': echo "El repuesto no fue encontrado."; break;
                case 'eliminacion_fallida': echo "Error: No se puede eliminar porque tiene historial (Compras/Ventas)."; break;
                default: echo "Ocurrió un error."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new" title="Registrar un repuesto nuevo">
            <i class="fas fa-plus"></i> Nuevo Repuesto
        </a>

        <a href="../compra/crear.php" class="btn secondary" title="Aumentar stock de repuestos existentes">
            <i class="fas fa-shopping-cart"></i> Registrar Compra (Stock)
        </a>

        <input type="text" id="buscar-repuesto" placeholder="Buscar repuesto..." onkeyup="filtrarRepuestos()">
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre / Modelo</th>
                    <th>Descripción</th>
                    <th>Precio Venta</th> 
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: bold; color: #555;"><?= htmlspecialchars($row['codigo']) ?></td>
                    
                    <td>
                        <strong><?= htmlspecialchars($row['nombre']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($row['modelo']) ?></small>
                    </td>
                    
                    <td title="<?= htmlspecialchars($row['descripcion']) ?>">
                        <?= htmlspecialchars(substr($row['descripcion'], 0, 40)) ?>...
                    </td>
                    
                    <td><?= '$' . number_format($row['precio_venta'], 2) ?></td> 
                    
                    <td style="font-weight: bold; text-align: center;">
                        <?php if($row['stock'] <= 0): ?>
                            <span style="color: red; background: #ffe6e6; padding: 2px 6px; border-radius: 4px;">AGOTADO (0)</span>
                        <?php elseif($row['stock'] <= 10): ?> <span style="color: #d35400;">BAJO (<?= $row['stock'] ?>)</span>
                        <?php else: ?>
                            <span style="color: green;"><?= $row['stock'] ?></span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="actions">
                        <a href="editar.php?codigo=<?= urlencode($row['codigo']) ?>" class="btn-edit" title="Editar Ficha">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <button class="btn-danger btn-eliminar" data-codigo="<?= htmlspecialchars($row['codigo']) ?>" title="Eliminar del Catálogo">
                            <i class="fas fa-trash"></i>
                        </button> 
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px;">No hay repuestos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de eliminar el repuesto <span id="codigo-display" style="font-weight: bold;"></span>?</p>
        <p style="font-size: 0.9em; color: #666;">Nota: Solo se eliminará si NO tiene historial de compras o ventas.</p>
        <div class="modal-actions">
            <button id="confirmCancel" class="btn secondary">Cancelar</button>
            <button id="confirmDelete" class="btn danger">Eliminar</button>
        </div>
    </div>
</div>

<script>
// SCRIPT DE ELIMINACIÓN Y BÚSQUEDA
document.addEventListener('DOMContentLoaded', function() {
    let codigoAEliminar = null;
    const modal = document.getElementById('confirmModal');
    
    // Abrir Modal
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            codigoAEliminar = this.dataset.codigo;
            document.getElementById('codigo-display').textContent = codigoAEliminar;
            modal.style.display = 'flex';
        });
    });

    // Confirmar Eliminar
    document.getElementById('confirmDelete').addEventListener('click', async () => {
        if (!codigoAEliminar) return;
        
        try {
            const response = await fetch('eliminar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo: codigoAEliminar })
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = 'index.php?success=repuesto_eliminado';
            } else {
                alert(data.error || 'Error al eliminar');
                modal.style.display = 'none';
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión');
        }
    });

    // Cancelar Modal
    document.getElementById('confirmCancel').addEventListener('click', () => {
        modal.style.display = 'none';
        codigoAEliminar = null;
    });
});

// Función de filtrado
function filtrarRepuestos() {
    const input = document.getElementById('buscar-repuesto');
    const filter = input.value.toUpperCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    });
}
</script>

<?php include '../includes/footer.php'; ?>