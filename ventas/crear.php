<?php
// ... código PHP de inicio de sesión, includes, y consultas ...
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// --- CONSULTAS NECESARIAS ---
$productos_sql = "
    (SELECT codigo, nombre, precio_venta, stock, 'maquina' as tipo FROM maquinas)
    UNION ALL
    (SELECT codigo, nombre, precio_venta, stock, 'repuesto' as tipo FROM repuestos)
    ORDER BY nombre
";
$productos_result = $conn->query($productos_sql);

$clientes_result = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

$error = '';
$productos_data = []; // Almacenar datos para fácil acceso
// Nota: La clave de este array ($p['codigo']) debe ser única para maquinas y repuestos
if ($productos_result->num_rows > 0) {
    while ($p = $productos_result->fetch_assoc()) {
        $productos_data[$p['codigo']] = $p;
    }
}
$productos_result->data_seek(0); // Reiniciar el puntero para el HTML

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. CAPTURAR DATOS
    $cliente_id = limpiar($_POST['cliente_id'] ?? ''); 
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $fecha = limpiar($_POST['fecha'] ?? '');
    $items = $_POST['items'] ?? []; 
    
    $total_venta = 0.00;
    $errores_items = [];
    
    // 2. VALIDAR Y CALCULAR EL TOTAL DE LOS ITEMS
    foreach ($items as $index => $item) {
        $codigo = limpiar($item['codigo'] ?? '');
        $cantidad = limpiar($item['cantidad'] ?? 0);
        $precio = limpiar($item['precio_unitario'] ?? 0.00);
        
        if (empty($codigo) || !isset($productos_data[$codigo])) {
            $errores_items[] = "Línea " . ($index + 1) . ": Producto no seleccionado o inválido.";
            continue;
        }
        // ******************************************************************
        // AÑADIDO: Asignar el tipo de producto a cada item para el INSERT
        // ******************************************************************
        $items[$index]['tipo'] = $productos_data[$codigo]['tipo']; 
        
        if (!is_numeric($cantidad) || $cantidad <= 0) {
            $errores_items[] = "Línea " . ($index + 1) . ": La cantidad debe ser un número positivo.";
            continue;
        }
        if (!is_numeric($precio) || $precio <= 0) {
            $errores_items[] = "Línea " . ($index + 1) . ": El precio unitario debe ser un valor positivo.";
            continue;
        }
        
        $total_venta += ($cantidad * $precio);
    }
    
    if (!empty($errores_items)) {
        $error = "Errores en el detalle de la venta: <br>" . implode("<br>", $errores_items);
    } elseif ($total_venta <= 0) {
        $error = "El total de la venta debe ser mayor a cero.";
    } else {
        // Ejecución de la transacción
        $conn->begin_transaction(); 
        try {
            // 1. INSERCIÓN EN CABECERA (ventas)
            $sql = "INSERT INTO ventas (empresas_id, total, descripcion, fecha_venta, usuario_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idssi", $cliente_id, $total_venta, $descripcion, $fecha, $_SESSION['user_id']); 
            $stmt->execute();
            $venta_id = $conn->insert_id;
            $stmt->close();
          
            // 2. INSERCIÓN EN DETALLE (detalle_venta) - ¡CORRECCIÓN AQUÍ!
            // Usamos solo 'codigo_producto' en lugar de codigo_maquina y codigo_repuesto
            $sql_detalle = "INSERT INTO detalle_venta (venta_id, cantidad, precio_unitario, codigo_producto) 
                            VALUES (?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            
            foreach ($items as $item) {
                $codigo = limpiar($item['codigo']);
                $cantidad = limpiar($item['cantidad']);
                $precio = limpiar($item['precio_unitario']);
                $tipo = $item['tipo']; // Tipo ya validado y asignado
                
                // NOTA: La lógica de NULL ya no es necesaria, solo se pasa el $codigo
                
                // Vinculación de parámetros ajustada a 4 variables: i=int, d=double, d=double, s=string/código
                $stmt_detalle->bind_param("iids", $venta_id, $cantidad, $precio, $codigo);
                $stmt_detalle->execute();
                
                // 3. ACTUALIZACIÓN DE STOCK
                // La lógica de stock es correcta y utiliza el 'tipo' para saber qué tabla actualizar
                $tabla_stock = ($tipo == 'maquina') ? 'maquinas' : 'repuestos';
                $sql_stock = "UPDATE {$tabla_stock} SET stock = stock - ? WHERE codigo = ?";
                $stmt_stock = $conn->prepare($sql_stock);
                $stmt_stock->bind_param("is", $cantidad, $codigo); 
                $stmt_stock->execute();
                $stmt_stock->close();
            }
            
            $stmt_detalle->close();
            
            $conn->commit();
            
            $_SESSION['mensaje_exito'] = "Venta creada exitosamente y stock actualizado. Total: $" . number_format($total_venta, 2);
            header("Location: index.php?success=venta_creada");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al procesar la venta. Transacción revertida. Detalle: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Nueva Venta</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        
        <div class="form-group">
            <label for="cliente_id">Cliente (Empresa):</label>
            <select id="cliente_id" name="cliente_id" required>
                <option value="">Seleccione un cliente</option>
                <?php 
                $clientes_result->data_seek(0); 
                while($cliente = $clientes_result->fetch_assoc()): 
                ?>
                <option 
                    value="<?= htmlspecialchars($cliente['id']) ?>"
                    <?= (($_POST['cliente_id'] ?? '') == $cliente['id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($cliente['nombre']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')) ?>" required>
        </div>

        <h3>Detalle de Productos</h3>
        <div id="detalle-productos-container">
            </div>
        
        <div class="total-summary" style="text-align: right; margin-top: 15px;">
            <label for="total_venta" style="font-weight: bold; margin-right: 10px;">TOTAL VENTA ($):</label>
            <input type="text" id="total_venta" name="total_venta_display" readonly style="font-weight: bold; width: 120px; text-align: right;">
        </div>

        <div class="form-actions" style="justify-content: flex-start; margin-top: 10px;">
            <button type="button" id="agregar-producto" class="btn secondary">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label for="descripcion">Notas/Descripción de la Venta:</label>
            <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Venta</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php ob_start(); // Inicia el buffer de salida para capturar HTML ?>
<option value="">Seleccione Producto</option>
<?php 
// Usar $productos_data para generar las opciones, ya que es la fuente más confiable
foreach($productos_data as $producto): 
?>
<option 
    value="<?= htmlspecialchars($producto['codigo']) ?>" 
    data-precio="<?= htmlspecialchars($producto['precio_venta'] ?? '0.00') ?>"
    data-stock="<?= htmlspecialchars($producto['stock'] ?? '0') ?>"
    data-tipo="<?= htmlspecialchars($producto['tipo']) ?>"
>
    <?= htmlspecialchars($producto['nombre']) ?> (Stock: <?= htmlspecialchars($producto['stock']) ?>)
</option>
<?php endforeach; ?>
<?php $producto_options_html = ob_get_clean(); // Captura y limpia el buffer ?>

<style>
/* Estilo para cada grupo de producto */
.product-group {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 6px;
    background-color: #f9f9f9;
}
.product-group .form-group-inline {
    display: flex; /* Para poner label y input en línea si es posible */
    align-items: center;
    margin-bottom: 10px;
    gap: 10px; /* Espacio entre los elementos en línea */
}
.product-group .form-group-inline label {
    flex-basis: 150px; /* Ancho fijo para las etiquetas */
    font-weight: bold;
}
.product-group .form-group-inline input[type="number"],
.product-group .form-group-inline input[type="text"],
.product-group .form-group-inline select {
    flex-grow: 1; /* Ocupa el espacio restante */
    max-width: 250px; /* Limita el ancho máximo para números */
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.product-group .btn-eliminar-item {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-left: auto; /* Mueve el botón a la derecha */
}
</style>


<script>
    const productoOptionsHtml = `<?= $producto_options_html ?>`;
    let itemCount = 0;

    // Función para calcular el total general
    function calcularTotales() {
        let totalGeneral = 0;
        document.querySelectorAll('.product-group').forEach(group => {
            const cantidadInput = group.querySelector('.item-cantidad');
            const precioInput = group.querySelector('.item-precio');
            const subtotalLabel = group.querySelector('.item-subtotal-label');

            const cantidad = parseFloat(cantidadInput.value) || 0;
            const precio = parseFloat(precioInput.value) || 0;
            
            const subtotal = cantidad * precio;
            
            // Actualiza el texto del subtotal
            subtotalLabel.textContent = subtotal.toFixed(2);
            totalGeneral += subtotal;
        });

        document.getElementById('total_venta').value = totalGeneral.toFixed(2);
    }

    // Función que crea un nuevo grupo de detalle
    function agregarGrupoDetalle(productoData = {}) {
        const container = document.getElementById('detalle-productos-container');
        
        const newGroup = document.createElement('div');
        newGroup.className = 'product-group';
        newGroup.dataset.index = itemCount;
        
        newGroup.innerHTML = `
            <div class="form-group-inline">
                <label>Producto:</label>
                <select name="items[${itemCount}][codigo]" class="item-codigo" required>
                    ${productoOptionsHtml}
                </select>
                <button type="button" class="btn-danger btn-eliminar-item" title="Eliminar Producto">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
            
            <div class="form-group-inline">
                <label>Cantidad:</label>
                <input type="number" name="items[${itemCount}][cantidad]" class="item-cantidad" min="1" value="${productoData.cantidad || '1'}" required>
            </div>
            
            <div class="form-group-inline">
                <label>Precio Unitario ($):</label>
                <input type="number" name="items[${itemCount}][precio_unitario]" class="item-precio" step="0.01" min="0.01" value="${productoData.precio || ''}" required>
            </div>

            <div class="form-group-inline" style="border-top: 1px dashed #ccc; padding-top: 5px;">
                <label>Subtotal ($):</label>
                <span class="item-subtotal-label" style="font-weight: bold; flex-grow: 1; text-align: right;">0.00</span>
            </div>
        `;

        container.appendChild(newGroup);

        // Añadir listeners a los nuevos campos
        const cantidadInput = newGroup.querySelector('.item-cantidad');
        const precioInput = newGroup.querySelector('.item-precio');
        const codigoSelect = newGroup.querySelector('.item-codigo');
        const eliminarButton = newGroup.querySelector('.btn-eliminar-item');

        codigoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const precio = selectedOption.getAttribute('data-precio');
            if (precio) {
                precioInput.value = parseFloat(precio).toFixed(2);
            }
            calcularTotales();
        });

        // Listener para calcular al cambiar cantidad o precio
        cantidadInput.addEventListener('input', calcularTotales);
        precioInput.addEventListener('input', calcularTotales);

        // Listener para eliminar el grupo
        eliminarButton.addEventListener('click', function() {
            newGroup.remove();
            calcularTotales();
        });
        
        itemCount++;
        calcularTotales();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ... (Tu código de fecha se mantiene aquí) ...
        const fechaInput = document.getElementById('fecha');
        if (fechaInput) {
            const today = new Date().toISOString().split('T')[0];
            if (!fechaInput.value) {
                fechaInput.value = today; 
            }
            fechaInput.max = today; 
        }
        
        const agregarButton = document.getElementById('agregar-producto');

        // Evento para agregar nueva línea de producto
        agregarButton.addEventListener('click', function() {
            agregarGrupoDetalle();
        });

        // Asegurar que haya al menos una fila al cargar
        if (document.getElementById('detalle-productos-container').children.length === 0) {
            agregarGrupoDetalle();
        }
    });

</script>

<?php include '../includes/footer.php'; ?>