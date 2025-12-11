<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// CONSULTA UNIFICADA (Mucho más simple ahora)
// Traemos productos con stock positivo O servicios (que no usan stock)
$productos_sql = "SELECT codigo, nombre, precio_venta, stock, tipo 
                  FROM productos 
                  WHERE stock > 0 OR tipo = 'servicio' 
                  ORDER BY nombre";
$productos_result = $conn->query($productos_sql);

$clientes_result = $conn->query("SELECT id, nombre, rif FROM empresas ORDER BY nombre");

// Array auxiliar para JS
$productos_data = [];
if ($productos_result->num_rows > 0) {
    while ($p = $productos_result->fetch_assoc()) {
        $productos_data[$p['codigo']] = $p;
    }
}
$productos_result->data_seek(0); 

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = limpiar($_POST['cliente_id'] ?? ''); 
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha = limpiar($_POST['fecha'] ?? '');
    $num_comprobante = limpiar($_POST['num_comprobante'] ?? '');
    $items = $_POST['items'] ?? []; 
    
    // --- 1. VALIDACIÓN DE FECHA FUTURA ---
    $fecha_actual = date('Y-m-d');
    if ($fecha > $fecha_actual) {
        $error = "Error: No puede registrar ventas con fecha futura (La fecha máxima es hoy: $fecha_actual).";
    }
    
    // --- 2. VALIDACIONES DE LOGICA ---
    $total_venta = 0.00;
    $errores_items = [];
    
    if (empty($error)) { // Solo validamos items si la fecha está bien
        foreach ($items as $index => $item) {
            $codigo = limpiar($item['codigo'] ?? '');
            $cantidad = (int)($item['cantidad'] ?? 0);
            $precio = (float)($item['precio_unitario'] ?? 0.00);
            
            // Verificar existencia en BD
            if (empty($codigo)) continue; // Saltar vacíos
            
            // Consultamos la BD fresca para ver el stock REAL (Evitar trucos de HTML)
            $stmt_check = $conn->prepare("SELECT stock, nombre, tipo FROM productos WHERE codigo = ?");
            $stmt_check->bind_param("s", $codigo);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$res_check) {
                $errores_items[] = "El producto código '$codigo' no existe.";
                continue;
            }

            // --- 3. VALIDACIÓN DE STOCK (Solo para bienes físicos) ---
            if ($res_check['tipo'] !== 'servicio') {
                if ($res_check['stock'] < $cantidad) {
                    $errores_items[] = "Stock insuficiente para '{$res_check['nombre']}'. Solicitado: $cantidad | Disponible: {$res_check['stock']}.";
                }
            }

            // Validar valores
            if ($cantidad <= 0) $errores_items[] = "La cantidad para '{$res_check['nombre']}' debe ser mayor a 0.";
            if ($precio < 0) $errores_items[] = "El precio no puede ser negativo.";
            
            $items[$index]['tipo'] = $res_check['tipo']; // Guardamos el tipo seguro desde BD
            $total_venta += ($cantidad * $precio);
        }
    }

    if (!empty($errores_items)) {
        $error = "<b>No se pudo procesar la venta:</b><br>" . implode("<br>", $errores_items);
    } elseif (empty($error) && $total_venta <= 0) {
        $error = "El total de la venta debe ser mayor a cero.";
    } elseif (empty($error)) {
        
        // --- PROCESAR VENTA ---
        $conn->begin_transaction(); 
        try {
            // A. Insertar Cabecera
            $sql = "INSERT INTO ventas (empresas_id, total, descripcion, fecha_venta, num_comprobante, usuario_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idsssi", $cliente_id, $total_venta, $descripcion, $fecha, $num_comprobante, $_SESSION['user_id']); 
            $stmt->execute();
            $venta_id = $conn->insert_id;
            $stmt->close();
          
            // B. Insertar Detalles y Descontar Stock
            $sql_detalle = "INSERT INTO detalle_venta (venta_id, cantidad, precio_unitario, codigo_producto) 
                            VALUES (?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            
            // Preparar actualización de stock
            $sql_stock = "UPDATE productos SET stock = stock - ? WHERE codigo = ?";
            $stmt_stock = $conn->prepare($sql_stock);

            foreach ($items as $item) {
                $codigo = $item['codigo'];
                $cantidad = $item['cantidad'];
                $precio = $item['precio_unitario'];
                $tipo = $item['tipo']; 
                
                // Guardar detalle
                $stmt_detalle->bind_param("iids", $venta_id, $cantidad, $precio, $codigo);
                $stmt_detalle->execute();
                
                // Descontar Stock (Si no es servicio)
                if ($tipo !== 'servicio') {
                    $stmt_stock->bind_param("is", $cantidad, $codigo); 
                    $stmt_stock->execute();
                }
            }
            
            $stmt_detalle->close();
            $stmt_stock->close();
            
            $conn->commit();
            
            $_SESSION['mensaje_exito'] = "Venta registrada correctamente. Factura #$venta_id";
            header("Location: index.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error crítico: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="form-container" style="max-width: 1000px;">
    <h2>Registrar Nueva Venta</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="background:#f9fafb; padding:20px; border-radius:8px; border:1px solid #e5e7eb; margin-bottom:20px; display:flex; gap:20px; flex-wrap:wrap;">
            <div style="flex:2; min-width:250px;">
                <label for="cliente_id">Cliente:</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Seleccione...</option>
                    <?php 
                    $clientes_result->data_seek(0); 
                    while($c = $clientes_result->fetch_assoc()): 
                    ?>
                    <option value="<?= $c['id'] ?>" <?= (($_POST['cliente_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?> (<?= $c['rif'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="flex:1; min-width:150px;">
                <label for="fecha">Fecha Emisión:</label>
                <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div style="flex:1; min-width:150px;">
                <label for="num_comprobante">N° Control (Opcional):</label>
                <input type="text" id="num_comprobante" name="num_comprobante" value="<?= htmlspecialchars($_POST['num_comprobante'] ?? '') ?>">
            </div>
        </div>

        <h3 style="color:var(--azul-rey); border-bottom:2px solid var(--azul-claro); padding-bottom:5px; margin-bottom:15px;">Productos</h3>
        
        <div id="detalle-productos-container"></div>
        
        <div style="text-align:right; margin-top:20px; font-size:1.2em; border-top:1px solid #ddd; padding-top:10px;">
            <label style="font-weight:bold; margin-right:10px;">TOTAL A PAGAR:</label>
            <span style="color:var(--success); font-weight:800; font-size:1.4em;">$</span>
            <input type="text" id="total_venta" readonly value="0.00" style="border:none; background:transparent; font-weight:800; font-size:1.4em; color:var(--success); width:120px; text-align:left;">
        </div>

        <button type="button" id="agregar-producto" class="btn secondary" style="margin-top:-40px;">
            <i class="fas fa-plus"></i> Agregar Item
        </button>

        <div class="form-group" style="margin-top: 20px;">
            <label for="descripcion">Notas / Observaciones:</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales de la venta..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Procesar Venta</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Generamos las opciones una sola vez
    const opcionesHTML = `
        <option value="">-- Seleccionar --</option>
        <?php foreach($productos_data as $p): ?>
        <option value="<?= $p['codigo'] ?>" 
                data-precio="<?= $p['precio_venta'] ?>" 
                data-stock="<?= $p['stock'] ?>"
                data-tipo="<?= $p['tipo'] ?>">
            <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock'] ?>)
        </option>
        <?php endforeach; ?>
    `;

    let itemCount = 0;

    function agregarFila() {
        const container = document.getElementById('detalle-productos-container');
        const row = document.createElement('div');
        row.className = 'product-row';
        row.style.cssText = 'display:flex; gap:10px; align-items:flex-end; background:#fff; padding:10px; border:1px solid #eee; margin-bottom:10px; border-radius:6px;';
        
        row.innerHTML = `
            <div style="flex:3;">
                <label style="font-size:0.85em; font-weight:bold;">Producto / Servicio</label>
                <select name="items[${itemCount}][codigo]" class="item-select" required onchange="actualizarDatos(this)" style="width:100%;">
                    ${opcionesHTML}
                </select>
            </div>
            
            <div style="flex:1;">
                <label style="font-size:0.85em; font-weight:bold;">Stock</label>
                <input type="text" class="item-stock" disabled style="background:#f3f4f6; color:#666; width:100%;">
            </div>

            <div style="flex:1;">
                <label style="font-size:0.85em; font-weight:bold;">Cant.</label>
                <input type="number" name="items[${itemCount}][cantidad]" class="item-cant" min="1" value="1" required onchange="calcularTotal()" style="width:100%;">
            </div>

            <div style="flex:1;">
                <label style="font-size:0.85em; font-weight:bold;">Precio $</label>
                <input type="number" name="items[${itemCount}][precio_unitario]" class="item-precio" step="0.01" required onchange="calcularTotal()" style="width:100%;">
            </div>

            <div style="flex:1; text-align:right;">
                <label style="font-size:0.85em; font-weight:bold;">Subtotal</label>
                <div class="item-subtotal" style="padding:10px 0; font-weight:bold;">0.00</div>
            </div>

            <div>
                <button type="button" class="btn-danger" onclick="this.parentElement.parentElement.remove(); calcularTotal();" style="padding:8px 12px; margin-bottom:2px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.appendChild(row);
        itemCount++;
    }

    function actualizarDatos(select) {
        const row = select.closest('.product-row');
        const option = select.options[select.selectedIndex];
        
        if (option.value) {
            const precio = option.dataset.precio;
            const stock = option.dataset.stock;
            const tipo = option.dataset.tipo;

            row.querySelector('.item-precio').value = precio;
            
            // Manejo visual del stock
            const stockInput = row.querySelector('.item-stock');
            if (tipo === 'servicio') {
                stockInput.value = '∞'; // Infinito para servicios
                row.querySelector('.item-cant').removeAttribute('max');
            } else {
                stockInput.value = stock;
                // Opcional: Poner límite máximo en el input HTML (UX extra)
                row.querySelector('.item-cant').max = stock;
            }
        }
        calcularTotal();
    }

    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.product-row').forEach(row => {
            const cant = parseFloat(row.querySelector('.item-cant').value) || 0;
            const precio = parseFloat(row.querySelector('.item-precio').value) || 0;
            const sub = cant * precio;
            
            row.querySelector('.item-subtotal').textContent = sub.toFixed(2);
            total += sub;
        });
        document.getElementById('total_venta').value = total.toFixed(2);
    }

   
    document.getElementById('agregar-producto').addEventListener('click', agregarFila);
    
    
    <?php if(empty($_POST['items'])): ?>
        agregarFila(); 
    <?php endif; ?>

    
    document.getElementById('fecha').max = new Date().toISOString().split("T")[0];

</script>

<?php include '../includes/footer.php'; ?>