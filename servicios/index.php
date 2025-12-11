<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';

// Consulta SQL mejorada
$sql = "SELECT s.*, e.nombre as empresa_nombre 
        FROM servicios s
        JOIN empresas e ON s.empresa_id = e.id
        ORDER BY s.fecha DESC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<section class="empresas-list">
    <h2>Servicios Registrados</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert success" id="alert-msg">Operación realizada exitosamente.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert error" id="alert-msg"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a href="crear.php" class="btn-new">
            <i class="fas fa-plus"></i> Nuevo Servicio
        </a>

        <a href="./exportar.php" class="btn-new" style="background-color: #107c41;"> <i class="fas fa-file-excel"></i> Exportar
        </a>
        <input type="text" id="buscar-servicio" placeholder="Buscar servicio..." onkeyup="filtrarServicios()">

    </div>

    <div class="table-responsive">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Equipo</th>
                        <th>Tipo</th>
                        <th>Horas</th>
                        <th>Descripción</th>
                        <th>Próx. Visita</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($servicio = $result->fetch_assoc()): ?>
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
                                <?= htmlspecialchars(substr($servicio['descripcion'], 0, 40)) . (strlen($servicio['descripcion']) > 40 ? '...' : '') ?>
                            </td>

                            <td style="color: #d35400; font-weight: bold; white-space: nowrap;">
                                <?= $servicio['proximo_servicio'] ? date('d/m/Y', strtotime($servicio['proximo_servicio'])) : '-' ?>
                            </td>

                            <td class="actions">
                                <a href="editar.php?id=<?= $servicio['id'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn-danger btn-eliminar" data-id="<?= $servicio['id'] ?>" title="Eliminar"><i class="fas fa-trash"></i></button>
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

<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Eliminar Servicio</h3>
        <p>¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.</p>
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

        // Botones eliminar
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                idEliminar = this.dataset.id;
                modal.style.display = 'flex';
            });
        });

        // Confirmar acción
        document.getElementById('confirmDelete').addEventListener('click', async () => {
            if (!idEliminar) return;
            try {
                const response = await fetch('eliminar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: idEliminar
                    })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = 'index.php?success=1';
                } else {
                    alert(data.error || 'Error al eliminar');
                    modal.style.display = 'none';
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            }
        });

        // Cerrar modal
        document.getElementById('confirmCancel').addEventListener('click', () => modal.style.display = 'none');

        // Ocultar alertas
        const alerta = document.getElementById('alert-msg');
        if (alerta) setTimeout(() => alerta.style.display = 'none', 3000);
    });

    function filtrarServicios() {
        const filter = document.getElementById('buscar-servicio').value.toUpperCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.innerText.toUpperCase().includes(filter) ? '' : 'none';
        });
    }
</script>

<?php include '../includes/footer.php'; ?>