<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /Servindteca/auth/login.php"); exit(); }
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Obtener clientes
$clientes = $conn->query("SELECT id, nombre, rif FROM empresas ORDER BY nombre");

// Obtener productos para el JS (Catálogo con Stock)
// Usamos UNION para traer maquinas y repuestos juntos
$sql_prod = "SELECT codigo, nombre, precio_venta, stock, 'maquina' as tipo FROM maquinas WHERE stock > 0
             UNION ALL
             SELECT codigo, nombre, precio_venta, stock, 'repuesto' as tipo FROM repuestos WHERE stock > 0
             ORDER BY nombre";
$productos = $conn->query($sql_prod);
$lista_productos = [];
while($p = $productos->fetch_assoc()) {
    $lista_productos[] = $p;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $fecha = $_POST['fecha_venta'];
    $num_comprobante = limpiar($_POST['num_comprobante'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $items = $_POST['items'] ?? [];
    
    $total_venta = 0;

    if (empty($items)) {
        $error = "Debe agregar al menos un producto.";
    } elseif ($cliente_id <= 0) {
        $error = "Seleccione un cliente.";
    } else {
        try {
            $conn->begin_transaction();

            // 1. Calcular Total y Validar Stock en el Servidor (Seguridad)
            foreach ($items as $item) {
                $codigo = $item['codigo'];
                $cantidad = intval($item['cantidad']);
                
                // Buscar stock actual en BD para evitar trampas del JS
                // Buscamos primero en maquinas, si no en repuestos
                $stmt_check = $conn->prepare("SELECT stock, precio_venta, 'maquina' as tipo FROM maquinas WHERE codigo = ? UNION SELECT stock, precio_venta, 'repuesto' as tipo FROM repuestos WHERE codigo = ?");
                $stmt_check->bind_param("ss", $codigo, $codigo);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if (!$res_check) throw new Exception("Producto $codigo no existe.");
                if ($res_check['stock'] < $cantidad) throw new Exception("Stock insuficiente para $codigo. Disponible: " . $res_check['stock']);

                // Usamos el precio del formulario (puede haber descuento manual) o el de la BD
                $precio = floatval($item['precio']);
                $total_venta += ($precio * $cantidad);
            }

            // 2. Insertar Venta
            $stmt_v = $conn->prepare("INSERT INTO ventas (empresas_id, usuario_id, fecha_venta, num_comprobante, total, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_v->bind_param("iissss", $cliente_id, $_SESSION['user_id'], $fecha, $num_comprobante, $total_venta, $descripcion);
            $stmt_v->execute();
            $venta_id = $conn->insert_id;
            $stmt_v->close();

            // 3. Insertar Detalles y Descontar Stock
            $stmt_d = $conn->prepare("INSERT INTO detalle_venta (venta_id, codigo_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $codigo = $item['codigo'];
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                
                // Insertar detalle
                $stmt_d->bind_param("isid", $venta_id, $codigo, $cantidad, $precio);
                $stmt_d->execute();

                // Descontar Stock (Detectando tabla correcta)
                // Nota: Ya sabemos que existe por la validación anterior
                $tipo = $item['tipo']; // Enviado desde el form hidden
                $tabla = ($tipo == 'maquina') ? 'maquinas' : 'repuestos';
                
                $sql_stock = "UPDATE $tabla SET stock = stock - ? WHERE codigo = ?";
                $stmt_st = $conn->prepare($sql_stock);
                $stmt_st->bind_param("is", $cantidad, $codigo);
                $stmt_st->execute();
                $stmt_st->close();
            }
            $stmt_d->close();

            $conn->commit();
            $_SESSION['mensaje_exito'] = "Venta registrada exitosamente. Factura #$venta_id";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container" style="max-width: 1000px;">
    <h2>Nueva Venta</h2>
    <?php if($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <form method="POST" id="form-venta">
        <div style="background:#f9f9f9; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; gap:15px; flex-wrap:wrap;">
            <div style="flex:2;">
                <label>Cliente:</label>
                <select name="cliente_id" required style="width:100%;">
                    <option value="">Seleccione Cliente...</option>
                    <?php while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?> (<?= $c['rif'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label>Fecha:</label>
                <input type="date" name="fecha_venta" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="flex:1;">
                <label>N° Control / Factura:</label>
                <input type="text" name="num_comprobante" placeholder="Opcional">
            </div>
        </div>

        <table class="table" id="tabla-productos">
            <thead>
                <tr style="background:#eee;">
                    <th width="40%">Producto</th>
                    <th width="15%">Stock</th>
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
            <strong>Total a Pagar: $<span id="total-display">0.00</span></strong>
        </div>

        <div class="form-group" style="margin-top:15px;">
            <label>Observaciones:</label>
            <textarea name="descripcion" rows="2"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Finalizar Venta</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
const productos = <?= json_encode($lista_productos) ?>;

function agregarItem() {
    const index = document.querySelectorAll('#lista-items tr').length;
    const tr = document.createElement('tr');
    
    let options = '<option value="">-- Seleccionar --</option>';
    productos.forEach(p => {
        options += `<option value="${p.codigo}" data-precio="${p.precio_venta}" data-stock="${p.stock}" data-tipo="${p.tipo}">${p.nombre} (Stock: ${p.stock})</option>`;
    });

    tr.innerHTML = `
        <td>
            <select name="items[${index}][codigo]" class="form-control select-prod" required onchange="actualizarFila(this)">
                ${options}
            </select>
            <input type="hidden" name="items[${index}][tipo]" class="input-tipo">
        </td>
        <td><input type="text" class="input-stock" disabled style="width:60px; background:#eee;"></td>
        <td><input type="number" name="items[${index}][cantidad]" class="form-control input-cant" min="1" value="1" required onchange="calcularTotal()"></td>
        <td><input type="number" name="items[${index}][precio]" class="form-control input-precio" step="0.01" required onchange="calcularTotal()"></td>
        <td class="td-subtotal">$0.00</td>
        <td><button type="button" class="btn-danger btn-sm" onclick="eliminarFila(this)">X</button></td>
    `;
    document.getElementById('lista-items').appendChild(tr);
}

function actualizarFila(select) {
    const tr = select.closest('tr');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        tr.querySelector('.input-precio').value = option.dataset.precio;
        tr.querySelector('.input-stock').value = option.dataset.stock;
        tr.querySelector('.input-tipo').value = option.dataset.tipo;
        tr.querySelector('.input-cant').max = option.dataset.stock; // Validar maximo en HTML
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

// Agregar una fila al inicio
document.addEventListener('DOMContentLoaded', agregarItem);
</script>

<?php include '../includes/footer.php'; ?>