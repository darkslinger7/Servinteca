<?php
session_start();

// Asegurarse de que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}

require_once '../includes/database.php';

// 1. CONSULTA SQL: Aseguramos que se traigan todas las columnas, incluyendo precio_venta y stock.
$sql = "SELECT * FROM maquinas ORDER BY nombre";
$result = $conn->query($sql);

// Verificar si hay resultados para evitar errores
if ($result === false) {
    die("Error en la consulta SQL: " . $conn->error);
}
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Inventario de Máquinas</h2>
   
    <?php if(isset($_GET['success'])): ?>
        <div class="alert success">
            <?php 
            switch($_GET['success']) {
                case 'maquina_eliminada': echo "Máquina eliminada correctamente."; break; 
                case 'maquina_actualizada': echo "Máquina actualizada correctamente."; break;
                // 'empresa_eliminada' y 'maquina_creada' eliminados o corregidos
                default: echo "Operación realizada con éxito."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert error">
            <?php 
            switch($_GET['error']) {
                case 'maquina_no_encontrada': echo "La máquina no fue encontrada."; break;
                case 'error_eliminacion': echo "Error al eliminar la máquina. Asegúrese de que no esté referenciada."; break;
                case 'eliminacion_fallida': echo "Error inesperado al intentar eliminar la máquina."; break; // Nuevo mensaje
                default: echo "Ocurrió un error."; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="../compra/crear.php" class="btn secondary">
            <i class="fas fa-cart-plus"></i> Nueva Compra/Máquina
        </a>
        <input type="text" id="buscar-empresa" placeholder="Buscar máquina...">
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
            <?php while($maquina = $result->fetch_assoc()): ?>
            <tr data-codigo="<?= htmlspecialchars($maquina['codigo']) ?>"> 
                <td><?= htmlspecialchars($maquina['nombre']) ?></td>
                <td><?= htmlspecialchars($maquina['codigo']) ?></td>
                <td><?= htmlspecialchars($maquina['modelo']) ?></td>
                <td><?= htmlspecialchars(substr($maquina['descripcion'], 0, 50)) ?><?= (strlen($maquina['descripcion']) > 50) ? '...' : '' ?></td>
                
                <td><?= '$' . number_format($maquina['precio_venta'], 2) ?></td> 
                
                <td style="font-weight: bold; color: <?= ($maquina['stock'] <= 5) ? 'red' : 'green'; ?>;"><?= htmlspecialchars($maquina['stock']) ?></td>
                
                <td class="actions">
                <a href="editar.php?codigo=<?= urlencode($maquina['codigo']) ?>" class="btn-edit">
                <i class="fas fa-edit"></i> Editar
                </a>
                
                <button class="btn-danger btn-eliminar" data-codigo="<?= htmlspecialchars($maquina['codigo']) ?>">
                <i class="fas fa-trash"></i> Eliminar</button> 
                
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No hay máquinas registradas en el inventario.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>


<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirmar Eliminación</h3>
        <p>¿Estás seguro de eliminar la máquina con código: <span id="maquina-codigo-display" style="font-weight: bold;"></span>? Esta acción no se puede deshacer.</p>
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
const codigoDisplay = document.getElementById('maquina-codigo-display');


async function eliminarMaquina(codigo) {
    try {
        const response = await fetch('eliminar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            // Corregido: Enviamos el código en el body
            body: JSON.stringify({ codigo: codigo }) 
        });

        const data = await response.json();

        // Si la respuesta HTTP no es 200 (ej. 404, 500)
        if (!response.ok) {
            throw new Error(data.error || 'Error en el servidor o archivo no encontrado.');
        }

        // Si la respuesta JSON indica que no fue exitosa
        if (!data.success) {
            throw new Error(data.error || 'Error al eliminar la máquina. Puede que esté referenciada.');
        }

        // Éxito
        window.location.href = 'index.php?success=maquina_eliminada'; 
        
    } catch (error) {
        console.error('Error de Eliminación:', error);
        alert('Error al eliminar la máquina: ' + error.message);
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
        eliminarMaquina(codigoAEliminar);
    }
    modal.style.display = 'none'; // Ocultar inmediatamente
});

confirmCancelBtn.addEventListener('click', () => {
    modal.style.display = 'none'; // Ocultar
    codigoAEliminar = null;
});

// Implementación de la función de búsqueda (opcional, si tienes el CSS/JS global para ello)
document.getElementById('buscar-empresa').addEventListener('keyup', function() {
    const filter = this.value.toUpperCase();
    const table = document.querySelector('table tbody');
    const tr = table.getElementsByTagName('tr');

    for (let i = 0; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        
        // Buscamos en todas las celdas (Nombre, Código, Modelo, etc.)
        for (let j = 0; j < td.length - 1; j++) { // Excluimos la última columna (Acciones)
            if (td[j]) {
                if (td[j].textContent.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
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