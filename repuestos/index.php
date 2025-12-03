<?php
session_start();

// Asegurarse de que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}

require_once '../includes/database.php';

// 1. CONSULTA SQL: Aseguramos que se traigan todas las columnas de la tabla 'repuestos'.
$sql = "SELECT * FROM repuestos ORDER BY nombre";
$result = $conn->query($sql);

// Verificar si hay resultados para evitar errores
if ($result === false) {
    die("Error en la consulta SQL: " . $conn->error);
}
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Inventario de Repuestos</h2>
   
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success">
            <?php 
            switch($_GET['success']) {
                case 'repuesto_eliminado': echo "Repuesto eliminado correctamente."; break; 
                case 'repuesto_actualizado': echo "Repuesto actualizado correctamente."; break; 
                case 'repuesto_creado': echo "Repuesto creado correctamente."; break; // Mantener por si hay flujos viejos
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
                case 'error_eliminacion': echo "Error al eliminar el repuesto. Asegúrese de que no esté referenciado."; break;
                case 'eliminacion_fallida': echo "Error inesperado al intentar eliminar el repuesto."; break;
                default: echo "Ocurrió un error."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="../compra/crear.php" class="btn secondary">
            <i class="fas fa-cart-plus"></i>  Nueva Compra/Repuesto
        </a>
        <input type="text" id="buscar-repuesto" placeholder="Buscar repuesto...">
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Modelo</th>
                <th>Descripción</th>
                <th>Precio Venta</th> 
                <th>Stock</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($repuesto = $result->fetch_assoc()): ?>
            <tr data-codigo="<?= htmlspecialchars($repuesto['codigo']) ?>"> 
                <td><?= htmlspecialchars($repuesto['nombre']) ?></td>
                <td><?= htmlspecialchars($repuesto['codigo']) ?></td>
                <td><?= htmlspecialchars($repuesto['modelo']) ?></td>
                <td><?= htmlspecialchars(substr($repuesto['descripcion'], 0, 50)) ?><?= (strlen($repuesto['descripcion']) > 50) ? '...' : '' ?></td>
                
                <td><?= '$' . number_format($repuesto['precio_venta'], 2) ?></td> 
                
                <td style="font-weight: bold; color: <?= ($repuesto['stock'] <= 10) ? 'red' : 'green'; ?>;"><?= htmlspecialchars($repuesto['stock']) ?></td>
                
                <td class="actions">
                <a href="editar.php?codigo=<?= urlencode($repuesto['codigo']) ?>" class="btn-edit">
                <i class="fas fa-edit"></i> Editar
                </a>
                
                <button class="btn-danger btn-eliminar" data-codigo="<?= htmlspecialchars($repuesto['codigo']) ?>">
                <i class="fas fa-trash"></i> Eliminar</button> 
                
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No hay repuestos registrados en el inventario.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>


<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de eliminar el repuesto con código: <span id="repuesto-codigo-display" style="font-weight: bold;"></span>? Esta acción no se puede deshacer.</p>
        <div class="modal-actions">
            <button id="confirmCancel" class="btn secondary">Cancelar</button>
            <button id="confirmDelete" class="btn danger">Eliminar</button>
        </div>
    </div>
</div>

<script>
let codigoAEliminar = null;
const modal = document.getElementById('confirmModal');
const confirmDeleteBtn = document.getElementById('confirmDelete');
const confirmCancelBtn = document.getElementById('confirmCancel');
const codigoDisplay = document.getElementById('repuesto-codigo-display');


async function eliminarRepuesto(codigo) {
    try {
        const response = await fetch('eliminar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ codigo: codigo }) 
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Error en el servidor o archivo no encontrado.');
        }

        if (!data.success) {
            throw new Error(data.error || 'Error al eliminar la máquina. Puede que esté referenciada.');
        }
        
        // Éxito
        window.location.href = 'index.php?success=repuesto_eliminado'; 
        
    } catch (error) {
        console.error('Error de Eliminación:', error);
        alert('Error al eliminar el repuesto: ' + error.message);
        window.location.href = 'index.php?error=eliminacion_fallida';
    }
}

// Lógica de Modal
document.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        codigoAEliminar = this.dataset.codigo; // Capturamos el código
        codigoDisplay.textContent = codigoAEliminar; // Mostramos el código en el modal
        modal.style.display = 'flex'; // Mostrar modal
    });
});

confirmDeleteBtn.addEventListener('click', () => {
    if (codigoAEliminar) {
        eliminarRepuesto(codigoAEliminar);
    }
    modal.style.display = 'none';
});

confirmCancelBtn.addEventListener('click', () => {
    modal.style.display = 'none'; 
    codigoAEliminar = null;
});

// Implementación de la función de búsqueda
document.getElementById('buscar-repuesto').addEventListener('keyup', function() {
    const filter = this.value.toUpperCase();
    const table = document.querySelector('table tbody');
    const tr = table.getElementsByTagName('tr');

    for (let i = 0; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        
        // Buscamos en Nombre, Código y Modelo
        for (let j = 0; j < 3; j++) { 
            if (td[j] && td[j].textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        if (found) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>