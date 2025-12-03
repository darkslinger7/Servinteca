<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';


$venta_id = isset($_GET['id']) ? limpiar($_GET['id']) : '';
$error = '';
$venta = null;
$detalle_original = [];

if (empty($venta_id) || !is_numeric($venta_id)) {
    header("Location: index.php?error=venta_invalida");
    exit();
}


// --- CONSULTAS NECESARIAS ---
$productos_sql = "
    (SELECT codigo, nombre, precio_venta, stock, 'maquina' as tipo FROM maquinas)
    UNION ALL
    (SELECT codigo, nombre, precio_venta, stock, 'repuesto' as tipo FROM repuestos)
    ORDER BY nombre
";
$productos_result = $conn->query($productos_sql);
$clientes_result = $conn->query("SELECT id, nombre FROM empresas ORDER BY nombre");

// Almacenar datos para fácil acceso en la lógica POST
$productos_data = []; 
if ($productos_result->num_rows > 0) {
    while ($p = $productos_result->fetch_assoc()) {
        $productos_data[$p['codigo']] = $p;
    }
}
$productos_result->data_seek(0); // Reiniciar el puntero para el HTML

// 1. CARGA DE CABECERA
$sql_venta_cabecera = "SELECT v.*, e.nombre as empresa_nombre FROM ventas v JOIN empresas e ON v.empresas_id = e.id WHERE v.id = ?";
$stmt_venta_cabecera = $conn->prepare($sql_venta_cabecera);
$stmt_venta_cabecera->bind_param("i", $venta_id);
$stmt_venta_cabecera->execute();
$venta = $stmt_venta_cabecera->get_result()->fetch_assoc(); 
$stmt_venta_cabecera->close();

if (!$venta) {
    header("Location: index.php?error=venta_no_encontrada");
    exit();
}

// 2. CARGA DEL DETALLE ORIGINAL (CORRECCIÓN AQUÍ)
$sql_detalle = "SELECT 
                    COALESCE(codigo_maquina, codigo_repuesto) AS codigo, 
                    cantidad, 
                    precio_unitario 
                FROM detalle_venta 
                WHERE venta_id = ?";
$stmt_detalle = $conn->prepare($sql_detalle);
$stmt_detalle->bind_param("i", $venta_id);
$stmt_detalle->execute();
$result_detalle = $stmt_detalle->get_result();

while ($fila = $result_detalle->fetch_assoc()) {
    $detalle_original[] = $fila;
}
$stmt_detalle->close();

$detalle_original_json = json_encode($detalle_original);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id_nuevo = limpiar($_POST['cliente_id'] ?? $venta['empresas_id']);
    $descripcion_nueva = limpiar($_POST['descripcion'] ?? $venta['descripcion']);
    $fecha_nueva = limpiar($_POST['fecha'] ?? $venta['fecha_venta']);
    $items_nuevos = $_POST['items'] ?? []; 
    $detalle_original_post = json_decode($_POST['detalle_original_json'] ?? '[]', true);
    
    $total_nuevo = 0.00;
    $errores_items = [];
    
    // VALIDACIÓN Y CÁLCULO DEL NUEVO TOTAL
    foreach ($items_nuevos as $index => $item) {
        $codigo = limpiar($item['codigo'] ?? '');
        $cantidad = limpiar($item['cantidad'] ?? 0);
        $precio = limpiar($item['precio_unitario'] ?? 0.00);
        
        if (empty($codigo) || !isset($productos_data[$codigo])) {
            $errores_items[] = "Línea " . ($index + 1) . ": Producto no seleccionado o inválido.";
            continue;
        }
        // ********** AÑADIDO: Asignar tipo para uso posterior **********
        $items_nuevos[$index]['tipo'] = $productos_data[$codigo]['tipo'];
        // *************************************************************

        if (!is_numeric($cantidad) || $cantidad <= 0) {
            $errores_items[] = "Línea " . ($index + 1) . ": La cantidad debe ser un número positivo.";
            continue;
        }
        if (!is_numeric($precio) || $precio <= 0) {
            $errores_items[] = "Línea " . ($index + 1) . ": El precio unitario debe ser un valor positivo.";
            continue;
        }
        
        $total_nuevo += ($cantidad * $precio);
    }
    
    if (!empty($errores_items)) {
        $error = "Errores en el detalle de la venta: <br>" . implode("<br>", $errores_items);
    } elseif ($total_nuevo <= 0) {
        $error = "El total de la venta debe ser mayor a cero.";
    } else {
        
        $conn->begin_transaction(); 
        try {
            
            // 1. REVERTIR STOCK ORIGINAL (CORRECCIÓN AQUÍ: No se usa prepared statement con el nombre de la tabla)
            foreach ($detalle_original_post as $original) {
                $codigo_orig = $original['codigo'];
                $cantidad_orig = $original['cantidad'];
                
                // Obtener el tipo de producto
                $sql_get_tipo = "SELECT 'maquina' as tipo FROM maquinas WHERE codigo = ? 
                                 UNION ALL 
                                 SELECT 'repuesto' as tipo FROM repuestos WHERE codigo = ?";
                $stmt_get_tipo = $conn->prepare($sql_get_tipo);
                $stmt_get_tipo->bind_param("ss", $codigo_orig, $codigo_orig);
                $stmt_get_tipo->execute();
                $tipo_result = $stmt_get_tipo->get_result()->fetch_assoc();
                $stmt_get_tipo->close();
                
                $tabla_stock = $tipo_result ? ($tipo_result['tipo'] == 'maquina' ? 'maquinas' : 'repuestos') : null;
                
                if ($tabla_stock) {
                    // Consulta directa ya que el nombre de la tabla no puede ser un placeholder de prepared statement
                    $conn->query("UPDATE {$tabla_stock} SET stock = stock + $cantidad_orig WHERE codigo = '{$codigo_orig}'");
                }
            }
            
            // 2. ELIMINAR DETALLE VIEJO
            $conn->query("DELETE FROM detalle_venta WHERE venta_id = $venta_id");

            // 3. INSERTAR DETALLE NUEVO (CORRECCIÓN AQUÍ: Uso de codigo_maquina y codigo_repuesto)
            $sql_insert_detalle = "INSERT INTO detalle_venta (venta_id, cantidad, precio_unitario, codigo_maquina, codigo_repuesto) 
                                   VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_detalle = $conn->prepare($sql_insert_detalle);
            
            // 4. AJUSTAR STOCK NUEVO
            foreach ($items_nuevos as $item) {
                $codigo = limpiar($item['codigo']);
                $cantidad = limpiar($item['cantidad']);
                $precio = limpiar($item['precio_unitario']);
                $tipo = $item['tipo']; // Tipo ya asignado en la validación
                
                // Determinar qué columna usar
                $codigo_maquina = ($tipo === 'maquina') ? $codigo : NULL;
                $codigo_repuesto = ($tipo === 'repuesto') ? $codigo : NULL;

                // Insertar detalle con las columnas correctas
                $stmt_insert_detalle->bind_param("iidsi", $venta_id, $cantidad, $precio, $codigo_maquina, $codigo_repuesto);
                $stmt_insert_detalle->execute();
                
                // Ajustar Stock
                $tabla_stock_nuevo = ($tipo == 'maquina') ? 'maquinas' : 'repuestos';
                
                // Consulta directa de stock
                $conn->query("UPDATE {$tabla_stock_nuevo} SET stock = stock - $cantidad WHERE codigo = '{$codigo}'");
            }
            $stmt_insert_detalle->close();

            // 5. ACTUALIZAR CABECERA
            $sql_update_venta = "UPDATE ventas 
                                 SET empresas_id = ?, total = ?, descripcion = ?, fecha_venta = ? 
                                 WHERE id = ?";
            $stmt_update_venta = $conn->prepare($sql_update_venta);
            $stmt_update_venta->bind_param("idssi", $cliente_id_nuevo, $total_nuevo, $descripcion_nueva, $fecha_nueva, $venta_id);
            $stmt_update_venta->execute();
            $stmt_update_venta->close();

            
            $conn->commit();
            $_SESSION['mensaje_exito'] = "Venta ID: $venta_id actualizada exitosamente. Nuevo Total: $" . number_format($total_nuevo, 2);
            header("Location: index.php?success=venta_actualizada");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al actualizar la venta. Transacción revertida. Detalle: " . $e->getMessage();
        }
    }
    
    // Si hay error en POST, recarga el detalle nuevo para que el usuario no pierda el trabajo
    $venta['empresas_id'] = $cliente_id_nuevo;
    $venta['fecha_venta'] = $fecha_nueva;
    $venta['descripcion'] = $descripcion_nueva;
    
    // Usar el detalle post en caso de error para rellenar el formulario
    $detalle_original = $items_nuevos; 
    $detalle_original_json = json_encode($items_nuevos);
}


if (isset($venta['empresas_id'])) {
    $clientes_result->data_seek(0);
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Editar Venta (ID: <?= htmlspecialchars($venta_id) ?>)</h2> 
    
    <?php if(!empty($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="detalle_original_json" value='<?= htmlspecialchars($detalle_original_json, ENT_QUOTES) ?>'>

        <div class="form-group">
            <label for="cliente_id">Cliente (Empresa):</label>
            <select id="cliente_id" name="cliente_id" required>
                <option value="">Seleccione un cliente</option>
                <?php $clientes_result->data_seek(0); ?>
                <?php while($cliente = $clientes_result->fetch_assoc()): ?>
                <option 
                    value="<?= htmlspecialchars($cliente['id']) ?>"
                    <?= ($venta['empresas_id'] == $cliente['id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($cliente['nombre']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars(date('Y-m-d', strtotime($venta['fecha_venta']))) ?>" required>
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
            <textarea id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($venta['descripcion']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar Venta</button>
            <a href="index.php" class="btn secondary">Cancelar</a>
        </div>
    </form>
</section>

<?php ob_start(); // Inicia el buffer de salida para capturar HTML ?>
<option value="">Seleccione Producto</option>
<?php 
$productos_result->data_seek(0);
while($producto = $productos_result->fetch_assoc()): 
?>
<option 
    value="<?= htmlspecialchars($producto['codigo']) ?>" 
    data-precio="<?= htmlspecialchars($producto['precio_venta'] ?? '0.00') ?>"
    data-stock="<?= htmlspecialchars($producto['stock'] ?? '0') ?>"
    data-tipo="<?= htmlspecialchars($producto['tipo']) ?>"
>
    <?= htmlspecialchars($producto['nombre']) ?> (Stock: <?= htmlspecialchars($producto['stock']) ?>)
</option>
<?php endwhile; ?>
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
    display: flex; 
    align-items: center;
    margin-bottom: 10px;
    gap: 10px; 
}
.product-group .form-group-inline label {
    flex-basis: 150px; 
    font-weight: bold;
}
.product-group .form-group-inline input[type="number"],
.product-group .form-group-inline input[type="text"],
.product-group .form-group-inline select {
    flex-grow: 1; 
    max-width: 250px; 
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
    margin-left: auto; 
}
</style>


<script>
    const productoOptionsHtml = `<?= $producto_options_html ?>`;
    let itemCount = 0; // Usado para el índice del array POST
    const initialDetail = JSON.parse('<?= $detalle_original_json ?>'); // Detalle cargado desde PHP

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
        
        // Define el valor y estado seleccionado (selected) del select
        // Se usa una pequeña manipulación de string para marcar el item seleccionado si existe
        let optionsWithSelected = productoOptionsHtml;
        if (productoData.codigo) {
            optionsWithSelected = productoOptionsHtml.replace(
                `value="${productoData.codigo}"`, 
                `value="${productoData.codigo}" selected`
            );
        }

        newGroup.innerHTML = `
            <div class="form-group-inline">
                <label>Producto:</label>
                <select name="items[${itemCount}][codigo]" class="item-codigo" required>
                    ${optionsWithSelected}
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
                <input type="number" name="items[${itemCount}][precio_unitario]" class="item-precio" step="0.01" min="0.01" value="${productoData.precio_unitario || ''}" required>
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
        // Solo calcular totales si no se está cargando el detalle inicial
        if (productoData.codigo) {
            calcularTotales();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const agregarButton = document.getElementById('agregar-producto');

        // Evento para agregar nueva línea de producto
        agregarButton.addEventListener('click', function() {
            agregarGrupoDetalle();
        });
        
        // --- Cargar Detalle de la Venta Original ---
        if (initialDetail && initialDetail.length > 0) {
            initialDetail.forEach(item => {
                agregarGrupoDetalle({
                    codigo: item.codigo,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario 
                });
            });
        } else {
             // Si no hay detalles (lo cual no debería pasar), agrega uno vacío
             agregarGrupoDetalle();
        }

        // Asegurar que el total se calcule al final de la carga
        calcularTotales();
    });

</script>

<?php include '../includes/footer.php'; ?>