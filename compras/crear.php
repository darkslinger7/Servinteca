<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';

// Cargar Proveedores
$proveedores = $conn->query("SELECT * FROM proveedores ORDER BY nombre");

// Cargar Productos
$productos = $conn->query("SELECT codigo, nombre, stock FROM productos ORDER BY nombre");
$lista_productos = [];
while($p = $productos->fetch_assoc()) {
    $lista_productos[] = $p;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_proveedor = (int)$_POST['id_proveedor'];
    $num_factura = $_POST['num_factura'];
    $fecha = $_POST['fecha'];
    $items = $_POST['items'] ?? [];
    
    // --- VALIDACIÓN DE FECHA ---
    $hoy = date('Y-m-d');
    
    if ($fecha > $hoy) {
        $error = "Error: La fecha de compra no puede ser futura (Máximo: $hoy).";
    } elseif (empty($items)) {
        $error = "Debe agregar al menos un producto a la compra.";
    } elseif (empty($num_factura)) {
        $error = "El número de factura es obligatorio.";
    } else {
        $conn->begin_transaction();
        try {
            $total_compra = 0;
            
            // 1. Insertar Cabecera
            $stmt = $conn->prepare("INSERT INTO compras (id_proveedor, num_factura, fecha_compra, usuario_id, total) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("issi", $id_proveedor, $num_factura, $fecha, $_SESSION['user_id']);
            $stmt->execute();
            $compra_id = $conn->insert_id;
            $stmt->close();
            
            // 2. Insertar Detalles y Actualizar Stock
            $stmt_det = $conn->prepare("INSERT INTO detalle_compra (compra_id, codigo_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE codigo = ?");
            
            foreach ($items as $item) {
                $codigo = $item['codigo'];
                $cantidad = (int)$item['cantidad'];
                $precio = (float)$item['precio'];
                
                if ($cantidad <= 0 || $precio < 0) throw new Exception("Cantidad o precio inválido en producto $codigo");
                
                // Guardar detalle
                $stmt_det->bind_param("isid", $compra_id, $codigo, $cantidad, $precio);
                $stmt_det->execute();
                
                // Sumar al stock
                $stmt_stock->bind_param("is", $cantidad, $codigo);
                $stmt_stock->execute();
                
                $total_compra += ($cantidad * $precio);
            }
            
            // 3. Actualizar Total
            $conn->query("UPDATE compras SET total = $total_compra WHERE id = $compra_id");
            
            $conn->commit();
            header("Location: index.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al procesar: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container" style="max-width: 1000px;">
    <h2>Registrar Compra (Multi-producto)</h2>
    
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    
    <form method="POST" id="form-compra">
        <div style="background:#f8fafb; padding:15px; border-radius:8px; border:1px solid #eee; margin-bottom:20px; display:flex; gap:15px;">
            <div style="flex:1;">
                <label>Proveedor:</label>
                <select name="id_proveedor" required style="width:100%;">
                    <option value="">-- Seleccionar --</option>
                    <?php while($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
                <small><a href="../proveedores/crear.php">+ Nuevo Proveedor</a></small>
            </div>
            <div style="flex:1;">
                <label>N° Factura:</label>
                <input type="text" name="num_factura" required placeholder="Ej. A-000123">
            </div>
            <div style="flex:1;">
                <label>Fecha:</label>
                <input type="date" name="fecha" id="fecha_compra" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse;" id="tabla-items">
            <thead>
                <tr style="background:#002366; color:white;">
                    <th style="padding:10px; border-radius:5px 0 0 5px;">Producto</th>
                    <th style="padding:10px; width:100px;">Cantidad</th>
                    <th style="padding:10px; width:150px;">Costo Unit. ($)</th>
                    <th style="padding:10px; width:150px;">Subtotal</th>
                    <th style="padding:10px; width:50px; border-radius:0 5px 5px 0;"></th>
                </tr>
            </thead>
            <tbody id="lista-items">
                </tbody>
        </table>

        <button type="button" class="btn secondary" onclick="agregarFila()" style="margin-top:10px;">
            <i class="fas fa-plus"></i> Agregar Producto
        </button>
        <span style="margin-left: 15px; font-size: 0.9em; color: #666;">
            ¿Producto nuevo? <a href="../productos/crear.php" target="_blank">Créalo aquí primero</a>.
        </span>

        <div style="text-align:right; margin-top:20px; font-size:1.3em;">
            <strong>Total Factura: $<span id="total-display">0.00</span></strong>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Compra</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
const productos = <?= json_encode($lista_productos) ?>;
let itemIndex = 0;

// Bloquear fecha futura en JS (Doble seguridad)
document.getElementById('fecha_compra').max = new Date().toISOString().split("T")[0];

function agregarFila() {
    const tbody = document.getElementById('lista-items');
    const tr = document.createElement('tr');
    
    let options = '<option value="">-- Buscar Producto --</option>';
    productos.forEach(p => {
        options += `<option value="${p.codigo}">${p.nombre} (Cod: ${p.codigo})</option>`;
    });

    tr.innerHTML = `
        <td style="padding:5px;">
            <select name="items[${itemIndex}][codigo]" required style="width:100%;" class="input-prod">
                ${options}
            </select>
        </td>
        <td style="padding:5px;">
            <input type="number" name="items[${itemIndex}][cantidad]" class="input-cant" min="1" value="1" required onchange="calcularTotal()" style="width:100%;">
        </td>
        <td style="padding:5px;">
            <input type="number" name="items[${itemIndex}][precio]" class="input-precio" step="0.01" min="0" required onchange="calcularTotal()" style="width:100%;">
        </td>
        <td style="padding:5px; text-align:right; font-weight:bold;" class="td-subtotal">
            $0.00
        </td>
        <td style="padding:5px; text-align:center;">
            <button type="button" class="btn-danger" onclick="this.closest('tr').remove(); calcularTotal();" style="padding:5px 10px;">X</button>
        </td>
    `;
    
    tbody.appendChild(tr);
    itemIndex++;
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('#lista-items tr').forEach(row => {
        const cant = parseFloat(row.querySelector('.input-cant').value) || 0;
        const precio = parseFloat(row.querySelector('.input-precio').value) || 0;
        const sub = cant * precio;
        
        row.querySelector('.td-subtotal').textContent = '$' + sub.toFixed(2);
        total += sub;
    });
    document.getElementById('total-display').textContent = total.toFixed(2);
}

// Iniciar con una fila
document.addEventListener('DOMContentLoaded', agregarFila);
</script>

<?php include '../includes/footer.php'; ?>