<?php
session_start();
require_once '../includes/database.php';

$sql = "SELECT * FROM empresas ORDER BY nombre";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Empresas Registradas</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success">
            <?php 
            switch($_GET['success']) {
                case 'empresa_eliminada': echo "Empresa eliminada correctamente"; break;   
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error">
            <?php 
            switch($_GET['error']) {
                case 'empresa_no_encontrada': echo "La empresa no fue encontrada"; break;
                case 'error_eliminacion': echo "Error al eliminar la empresa"; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
    <a href="crear.php" class="btn-new">
        <i class="fas fa-plus"></i> Nueva Empresa
    </a>
    <input type="text" id="buscar-empresa" placeholder="Buscar empresa...">
    </div>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>RIF</th>
                <!-- <th>Direccion </th> -->
                <th>Acciones</th>
                
            </tr>
        </thead>
        <tbody>
            <?php while($empresa = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($empresa['nombre']) ?></td>
                <td><?= htmlspecialchars($empresa['rif']) ?></td>
                <td class="actions">
                <a href="editar.php?id=<?= $empresa['id'] ?>" class="btn-edit">
                <i class="fas fa-edit"></i> Editar
                </a>
                <button class="btn-danger btn-eliminar" data-id="<?= $empresa['id'] ?>">
                <i class="fas fa-trash"></i> Eliminar</button> 
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
async function eliminarEmpresa(id) {
    try {
        const response = await fetch('eliminar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: id })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error en el servidor');
        }

        if (!data.success) {
            throw new Error(data.error || 'Error al eliminar');
        }

        window.location.href = 'index.php?success=empresa_eliminada';
        
    } catch (error) {
        console.error('Error:', error);
        alert(error.message);
        window.location.href = 'index.php?error=eliminacion_fallida';
    }
}


document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const id = this.dataset.id;
        
        if (confirm('¿Está seguro de eliminar esta empresa?')) {
            eliminarEmpresa(id);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>