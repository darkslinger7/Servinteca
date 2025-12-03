<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';

$sql = "SELECT * FROM proveedores ORDER BY nombre";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Gestión de Proveedores</h2>
   
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success">
            <?php 
            switch($_GET['success']) {
                case 'creado': echo "Proveedor registrado exitosamente."; break;
                case 'actualizado': echo "Datos del proveedor actualizados."; break;
                case 'eliminado': echo "Proveedor eliminado correctamente."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error">
            <?php 
            switch($_GET['error']) {
                case 'eliminacion_fallida': echo "Error: No se puede eliminar porque tiene COMPRAS registradas."; break;
                default: echo "Ocurrió un error."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Nuevo Proveedor
        </a>
        <input type="text" id="buscar" placeholder="Buscar proveedor..." onkeyup="filtrarTabla()">
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Empresa / Razón Social</th>
                    <th>Documento (RIF/Tax ID)</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Contacto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($row['documento']) ?></td>
                    <td><?= htmlspecialchars($row['telefono'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['persona_contacto'] ?? '-') ?></td>
                    
                    <td class="actions">
                        <a href="editar.php?id=<?= $row['id'] ?>" class="btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $row['id'] ?>" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button> 
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="6" style="text-align: center;">No hay proveedores registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de eliminar este proveedor?</p>
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
            
            if (data.success) {
                window.location.href = 'index.php?success=eliminado';
            } else {
                alert(data.error);
                modal.style.display = 'none';
            }
        } catch (error) {
            console.error(error);
        }
    });

    document.getElementById('confirmCancel').addEventListener('click', () => {
        modal.style.display = 'none';
    });
});

function filtrarTabla() {
    const filter = document.getElementById('buscar').value.toUpperCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    });
}
</script>

<?php include '../includes/footer.php'; ?>