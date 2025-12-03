<?php
session_start();
// Verificamos sesión (opcional, pero recomendado si ya tienes login)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../includes/database.php';

// La consulta sigue igual, seleccionamos todo (*) para traer las nuevas columnas
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
                case 'empresa_creada': echo "Empresa registrada exitosamente"; break; // Agregué este caso por si acaso
                case 'empresa_actualizada': echo "Datos actualizados exitosamente"; break; // Y este
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

    <div style="overflow-x: auto;"> <table>
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
                    
                    <td><small><?= htmlspecialchars($empresa['direccion'] ?? 'N/A') ?></small></td>
                    <td><?= htmlspecialchars($empresa['telefono'] ?? '') ?></td>
                    <td><?= htmlspecialchars($empresa['email'] ?? '') ?></td>
                    
                    
                    <td class="actions">
                        <a href="editar.php?id=<?= $empresa['id'] ?>" class="btn-edit" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-danger btn-eliminar" data-id="<?= $empresa['id'] ?>" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button> 
                    </td>
                </tr>
                <?php endwhile; ?>
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
        const modal = document.getElementById('confirmModal');
        const btnConfirm = document.getElementById('confirmDelete');
        const btnCancel = document.getElementById('confirmCancel');
        
       
        modal.style.display = 'flex';
        
       
        btnConfirm.onclick = function() {
            eliminarEmpresa(id);
        };
        
        
        btnCancel.onclick = function() {
            modal.style.display = 'none';
        };
        
     
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>