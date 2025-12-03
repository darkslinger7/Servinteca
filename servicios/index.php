<?php
session_start();
// 1. IMPORTANTE: La conexión debe ir ANTES de cualquier consulta
require_once '../includes/database.php';

// Si no hay sesión, mandamos al login
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}

$mensaje = '';
$tipoMensaje = '';

// Lógica de mensajes (Mantenemos tu código original)
if (isset($_GET['success'])) {
    $mensaje = match($_GET['success']) {
        'servicio_eliminado' => 'Servicio eliminado correctamente',
        'servicio_actualizado' => 'Servicio actualizado correctamente',
        'servicio_creado' => 'Servicio creado exitosamente',
        default => ''
    };
    $tipoMensaje = 'success';
}

if (isset($_GET['error'])) {
    $mensaje = match($_GET['error']) {
        'no_se_pudo_eliminar' => 'No se pudo eliminar el servicio',
        'servicio_no_encontrado' => 'Servicio no encontrado',
        'id_invalido' => 'ID de servicio inválido',
        default => 'Ocurrió un error'
    };
    $tipoMensaje = 'error';
}

// 2. CONSULTA SQL MEJORADA (Traemos los datos nuevos)
$sql = "SELECT s.*, e.nombre as empresa_nombre 
        FROM servicios s
        JOIN empresas e ON s.empresa_id = e.id
        ORDER BY s.fecha DESC";
$result = $conn->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conn->error);
}
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Servicios Registrados</h2>
    
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje ?>" id="mensaje-temporal">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Nuevo Servicio
        </a>
        <input type="text" id="buscar-servicio" placeholder="Buscar servicio..." onkeyup="filtrarServicios()">
        <a href="./exportar.php" class="btn-new">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </a> 
    </div>
    
    <div style="overflow-x: auto;">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Equipo</th>       <th>Tipo</th>         <th>Horas</th>        <th>Descripción</th>
                        <th>Prox. Visita</th> <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($servicio = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?= date('d/m/Y', strtotime($servicio['fecha'])) ?></td>
                        
                        <td><strong><?= htmlspecialchars($servicio['empresa_nombre']) ?></strong></td>

                        <td><?= htmlspecialchars($servicio['equipo_atendido'] ?? '-') ?></td>

                        <td>
                            <span style="padding: 2px 6px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85em;">
                                <?= htmlspecialchars($servicio['tipo_servicio'] ?? 'General') ?>
                            </span>
                        </td>

                        <td><?= $servicio['horas_uso'] ? number_format($servicio['horas_uso']) . ' hrs' : '-' ?></td>
                        
                        <td title="<?= htmlspecialchars($servicio['descripcion']) ?>">
                            <?= htmlspecialchars(substr($servicio['descripcion'], 0, 50)) . (strlen($servicio['descripcion']) > 50 ? '...' : '') ?>
                        </td>

                        <td style="color: #d35400; font-weight: bold; white-space: nowrap;">
                            <?= $servicio['proximo_servicio'] ? date('d/m/Y', strtotime($servicio['proximo_servicio'])) : '-' ?>
                        </td>

                        <td class="actions" style="white-space: nowrap;">
                            <a href="editar.php?id=<?= $servicio['id'] ?>" class="btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn-danger btn-eliminar" data-id="<?= $servicio['id'] ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert info">No hay servicios registrados</div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Ocultar mensaje temporalmente
    const mensaje = document.getElementById('mensaje-temporal');
    if (mensaje) {
        setTimeout(() => {
            mensaje.style.opacity = '0';
            setTimeout(() => mensaje.remove(), 500);
        }, 3000);
    }

    // Lógica para eliminar con formulario POST oculto (Seguridad)
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar este servicio?')) {
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

// Función de búsqueda
function filtrarServicios() {
    const input = document.getElementById('buscar-servicio');
    const filter = input.value.toUpperCase();
    const table = document.querySelector('table');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let mostrarFila = false;
        // Buscamos en todas las celdas de la fila
        const celdas = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < celdas.length - 1; j++) { // -1 para ignorar la columna acciones
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