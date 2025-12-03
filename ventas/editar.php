<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($venta_id <= 0) {
    header("Location: index.php?error=id_invalido");
    exit();
}

// --- 1. CARGAR DATOS PARA LA VISTA ---

// Productos para el selector (Catálogo completo)
$sql_prod = "SELECT codigo, nombre, precio_venta, stock, 'maquina' as tipo FROM maquinas 
             UNION ALL
             SELECT codigo, nombre, precio_venta, stock, 'repuesto' as tipo FROM repuestos 
             ORDER BY nombre";
$productos = $conn->query($sql_prod);
$lista_productos = [];
while($p = $productos->fetch_assoc()) {
    $lista_productos[] = $p;
}

// Clientes
$clientes = $conn->query("SELECT id, nombre, rif FROM empresas ORDER BY nombre");

// Datos de la Venta Actual (Cabecera)
$stmt = $conn->prepare("SELECT * FROM ventas WHERE id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    header("Location: index.php?error=venta_no_encontrada");
    exit();
}

// Datos de la Venta Actual (Detalle)
$stmt_det = $conn->prepare("SELECT codigo_producto as codigo, cantidad, precio_unitario FROM detalle_venta WHERE venta_id = ?");
$stmt_det->bind_param("i", $venta_id);
$stmt_det->execute();
$result_det = $stmt_det->get_result();
$detalles_actuales = [];
while($row = $result_det->fetch_assoc()) {
    $detalles_actuales[] = $row;
}
$stmt_det->close();

// Codificar detalles para pasarlos a JS
$json_detalles = json_encode($detalles_actuales);
$json_productos = json_encode($lista_productos);

$error = '';

// --- 2. PROCESAR EL FORMULARIO (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha = $_POST['fecha'];
    $num_comprobante = limpiar($_POST['num_comprobante'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $items = $_POST['items'] ?? [];
    
    $total_venta = 0;

    if (empty($items)) {
        $error = "La venta debe tener al menos un producto.";
    } elseif ($cliente_id <= 0) {
        $error = "Seleccione un cliente válido.";
    } else {
        try {
            $conn->begin_transaction();

            // A. REVERTIR STOCK DE LA VENTA ANTERIOR
            // Consultamos qué había guardado en la BD (no confiamos en el POST anterior)
            $stmt_old = $conn->prepare("SELECT codigo_producto, cantidad FROM detalle_venta WHERE venta_id = ?");
            $stmt_old->bind_param("i", $venta_id);
            $stmt_old->execute();
            $res_old = $stmt_old->get_result();
            
            while($old_item = $res_old->fetch_assoc()) {
                $cod = $old_item['codigo_producto'];
                $cant = $old_item['cantidad'];
                
                // Buscar tipo para saber a qué tabla devolver
                $chk = $conn->query("SELECT 'maquina' as tipo FROM maquinas WHERE codigo = '$cod' UNION SELECT 'repuesto' as tipo FROM repuestos WHERE codigo = '$cod'");
                if ($row_tipo = $chk->fetch_assoc()) {
                    $tabla = ($row_tipo['tipo'] == 'maquina') ? 'maquinas' : 'repuestos';
                    $conn->query("UPDATE $tabla SET stock = stock + $cant WHERE codigo = '$cod'");
                }
            }
            $stmt_old->close();

            // B. BORRAR DETALLES VIEJOS
            $conn->query("DELETE FROM detalle_venta WHERE venta_id = $venta_id");

            // C. VALIDAR Y PROCESAR NUEVOS ITEMS
            foreach ($items as $item) {
                $codigo = $item['codigo'];
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                $total_venta += ($precio * $cantidad);

                // Verificar Stock DISPONIBLE (Ahora el stock incluye lo que acabamos de devolver en el paso A)
                $stmt_check = $conn->prepare("SELECT stock, 'maquina' as tipo FROM maquinas WHERE codigo = ? UNION SELECT stock, 'repuesto' as tipo FROM repuestos WHERE codigo = ?");
                $stmt_check->bind_param("ss", $codigo, $codigo);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if (!$res_check) throw new Exception("El producto $codigo ya no existe en el catálogo.");
                
                if ($res_check['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto $codigo. Disponible: " . $res_check['stock']);
                }

                // Insertar nuevo detalle
                $stmt_ins = $conn->prepare("INSERT INTO detalle_venta (venta_id, codigo_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt_ins->bind_param("isid", $venta_id, $codigo, $cantidad, $precio);
                $stmt_ins->execute();
                $stmt_ins->close();

                // Descontar nuevo stock
                $tabla = ($res_check['tipo'] == 'maquina') ? 'maquinas' : 'repuestos';
                $stmt_upd = $conn->prepare("UPDATE $tabla SET stock = stock - ? WHERE codigo = ?");
                $stmt_upd->bind_param("is", $cantidad, $codigo);
                $stmt_upd->execute();
                $stmt_upd->close();
            }

            // D. ACTUALIZAR CABECERA DE VENTA
            $stmt_head = $conn->prepare("UPDATE ventas SET empresas_id=?, fecha_venta=?, num_comprobante=?, total=?, descripcion=?, usuario_id=? WHERE id=?");
            $stmt_head->bind_param("issssii", $cliente_id, $fecha, $num_comprobante, $total_venta, $descripcion, $_SESSION['user_id'], $venta_id);
            $stmt_head->execute();
            $stmt_head->close();

            $conn->commit();
            $_SESSION['mensaje_exito'] = "Venta actualizada correctamente.";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al actualizar: " . $e->getMessage();
            // Recargamos datos para no perder lo que el usuario escribió
            $venta['empresas_id'] = $cliente_id;
            $venta['fecha_venta'] = $fecha;
            $venta['num_comprobante'] = $num_comprobante;
            $venta['descripcion'] = $descripcion;
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container" style="max-width: 1000px;">
    <h2>Editar Venta #<?= $venta_id ?></h2>
    
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <form method="POST" id="form-venta">
        <div style="background:#f9f9f9; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; gap:15px; flex-wrap:wrap;">
            <div style="flex:2;">
                <label>Cliente:</label>
                <select name="cliente_id" required style="width:100%;">
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $venta['empresas_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?> (<?= $c['rif'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label>Fecha:</label>
                <input type="date" name="fecha" value="<?= htmlspecialchars($venta['fecha_venta']) ?>" required>
            </div>
            <div style="flex:1;">
                <label>N° Factura/Control:</label>
                <input type="text" name="num_comprobante" value="<?= htmlspecialchars($venta['num_comprobante']) ?>">
            </div>
        </div>

        <table class="table" id="tabla-productos">
            <thead>
                <tr style="background:#eee;">
                    <th width="40%">Producto</th>
                    <th width="15%">Stock Actual</th>
                    <th width="15%">Cantidad</th>
                    <th width="15%">Precio Unit.</th>
                    <th width="15%">Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="lista-items">
                </tbody>
        </table>

        <button type="button" class="btn secondary" onclick="agregarItem()" style="margin-top:10px;">
            <i class="fas fa-plus"></i> Agregar Producto
        </button>

        <div style="text-align:right; margin-top:20px; font-size:1.2em;">
            <strong>Total a Pagar: $<span id="total-display"><?= number_format($venta['total'], 2) ?></span></strong>
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label>Observaciones:</label>
            <textarea name="descripcion" rows="2"><?= htmlspecialchars($venta['descripcion']) ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
const productos = <?= $json_productos ?>;
const detallesIniciales = <?= $json_detalles ?>;

function agregarItem(data = null) {
    const index = document.querySelectorAll('#lista-items tr').length;
    const tr = document.createElement('tr');
    
    // Si viene data (es una fila existente), usamos sus valores
    const codigoVal = data ? data.codigo : '';
    const cantVal = data ? data.cantidad : 1;
    const precioVal = data ? data.precio_unitario : '';
    
    let options = '<option value="">-- Seleccionar --</option>';
    let stockDisplay = '';

    productos.forEach(p => {
        const selected = p.codigo === codigoVal ? 'selected' : '';
        // Si es el producto seleccionado, guardamos su stock para mostrarlo
        if(selected) stockDisplay = p.stock;
        
        options += `<option value="${p.codigo}" data-precio="${p.precio_venta}" data-stock="${p.stock}" ${selected}>${p.nombre}</option>`;
    });

    tr.innerHTML = `
        <td>
            <select name="items[${index}][codigo]" class="form-control select-prod" required onchange="actualizarFila(this)">
                ${options}
            </select>
        </td>
        <td><input type="text" class="input-stock" value="${stockDisplay}" disabled style="width:60px; background:#eee;"></td>
        <td><input type="number" name="items[${index}][cantidad]" class="form-control input-cant" min="1" value="${cantVal}" required onchange="calcularTotal()"></td>
        <td><input type="number" name="items[${index}][precio]" class="form-control input-precio" step="0.01" value="${precioVal}" required onchange="calcularTotal()"></td>
        <td class="td-subtotal">$0.00</td>
        <td><button type="button" class="btn-danger btn-sm" onclick="eliminarFila(this)">X</button></td>
    `;
    document.getElementById('lista-items').appendChild(tr);
    
    // Calcular subtotal inicial de esta fila
    if(data) {
        const sub = cantVal * precioVal;
        tr.querySelector('.td-subtotal').textContent = '$' + sub.toFixed(2);
    }
}

function actualizarFila(select) {
    const tr = select.closest('tr');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        tr.querySelector('.input-precio').value = option.dataset.precio;
        tr.querySelector('.input-stock').value = option.dataset.stock;
        // Validar máximo (Opcional, porque al editar recuperamos stock y el límite real es stock_bd + cantidad_devuelta)
        // tr.querySelector('.input-cant').max = option.dataset.stock; 
    }
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('#lista-items tr').forEach(tr => {
        const cant = parseFloat(tr.querySelector('.input-cant').value) || 0;
        const precio = parseFloat(tr.querySelector('.input-precio').value) || 0;
        const sub = cant * precio;
        tr.querySelector('.td-subtotal').textContent = '$' + sub.toFixed(2);
        total += sub;
    });
    document.getElementById('total-display').textContent = total.toFixed(2);
}

function eliminarFila(btn) {
    btn.closest('tr').remove();
    calcularTotal();
}

// Cargar detalles al iniciar
document.addEventListener('DOMContentLoaded', () => {
    if (detallesIniciales.length > 0) {
        detallesIniciales.forEach(item => agregarItem(item));
        calcularTotal();
    } else {
        agregarItem(); // Fila vacía si es nuevo (aunque esto es editar)
    }
});
</script>

<?php include '../includes/footer.php'; ?>