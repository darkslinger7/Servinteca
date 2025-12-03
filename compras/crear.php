<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Obtener lista de proveedores para el select
$proveedores = $conn->query("SELECT id, nombre FROM proveedores ORDER BY nombre");

$mensajeExito = '';
$error = '';
$modo_seleccionado = ''; 

$datosCompra = [
    'num_factura' => '',      // Nuevo
    'id_proveedor' => '',     // Nuevo
    'codigo_producto' => '', 
    'fecha_compra' => date('Y-m-d'), 
    'cantidad' => '', 
    'precio_compra_unitario' => '',
    'precio_venta' => '',
    'tipo_producto' => '', 
    'nombre_producto' => '', 
    'modelo_producto' => '', 
    'descripcion_producto' => '' 
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $modo_seleccionado = limpiar($_POST['modo_registro'] ?? '');

    // Captura de datos generales
    $datosCompra['num_factura'] = limpiar($_POST['num_factura'] ?? '');
    $datosCompra['id_proveedor'] = intval($_POST['id_proveedor'] ?? 0);
    $datosCompra['codigo_producto'] = limpiar($_POST['codigo_producto'] ?? '');
    $datosCompra['fecha_compra'] = limpiar($_POST['fecha_compra'] ?? date('Y-m-d'));
    $datosCompra['cantidad'] = intval($_POST['cantidad'] ?? 0); 
    $datosCompra['precio_compra_unitario'] = floatval($_POST['precio_compra_unitario'] ?? 0); 
    $precio_venta_nuevo = floatval($_POST['precio_venta'] ?? 0);
    
    // Datos de creación
    $datosCompra['tipo_producto'] = limpiar($_POST['tipo_producto'] ?? ''); 
    $datosCompra['nombre_producto'] = limpiar($_POST['nombre_producto'] ?? ''); 
    $datosCompra['modelo_producto'] = limpiar($_POST['modelo_producto'] ?? ''); 
    $datosCompra['descripcion_producto'] = limpiar($_POST['descripcion_producto'] ?? ''); 

    // --- Validaciones ---
    if (empty($datosCompra['num_factura'])) {
        $error = "El número de factura es obligatorio.";
    } elseif ($datosCompra['id_proveedor'] <= 0) {
        $error = "Debe seleccionar un proveedor válido.";
    } elseif (empty($modo_seleccionado)) {
        $error = "Debe seleccionar el modo de registro.";
    } elseif ($datosCompra['cantidad'] <= 0 || $datosCompra['precio_compra_unitario'] <= 0 || $precio_venta_nuevo <= 0) {
        $error = "Cantidad y precios deben ser positivos.";
    } elseif (empty($datosCompra['codigo_producto'])) {
        $error = "El código del producto es obligatorio.";
    } else {
        
        try {
            $conn->begin_transaction();
            
            // Verificar existencia del código
            $sql_check = "SELECT tipo_producto FROM producto WHERE codigo_unificado = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $datosCompra['codigo_producto']);
            $stmt_check->execute();
            $stmt_check->bind_result($tipo_existente);
            $stmt_check->fetch();
            $stmt_check->close();
            
            $es_producto_nuevo = empty($tipo_existente);
            $tipo_final = $tipo_existente;
            
            // Validaciones de lógica de negocio
            if ($modo_seleccionado == 'nuevo' && !$es_producto_nuevo) {
                throw new Exception("El código ya existe. Cambie a 'Producto Registrado'.");
            }
            if ($modo_seleccionado == 'existente' && $es_producto_nuevo) {
                 throw new Exception("El código NO existe. Cambie a 'Producto Nuevo'.");
            }
            
            if ($modo_seleccionado == 'nuevo') {
                // ESCENARIO 1: PRODUCTO NUEVO
                if (empty($datosCompra['tipo_producto'])) throw new Exception("Especifique si es Máquina o Repuesto.");
                if (empty($datosCompra['nombre_producto'])) throw new Exception("Nombre del producto requerido.");

                $tipo_final = $datosCompra['tipo_producto'];
                $tabla = ($tipo_final === 'maquina') ? 'maquinas' : 'repuestos';
                
                // 1.A. Insertar en tabla específica (maquinas/repuestos)
                // OJO: Stock inicial es la cantidad de la compra
                $sql_nativo = "INSERT INTO {$tabla} (nombre, codigo, modelo, descripcion, precio_venta, stock) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_nativo = $conn->prepare($sql_nativo);
                $stmt_nativo->bind_param("ssssdi", $datosCompra['nombre_producto'], $datosCompra['codigo_producto'], $datosCompra['modelo_producto'], $datosCompra['descripcion_producto'], $precio_venta_nuevo, $datosCompra['cantidad']);
                $stmt_nativo->execute();
                $stmt_nativo->close();

                // 1.B. Insertar en catálogo unificado
                $sql_producto = "INSERT INTO producto (codigo_unificado, tipo_producto) VALUES (?, ?)";
                $stmt_producto = $conn->prepare($sql_producto);
                $stmt_producto->bind_param("ss", $datosCompra['codigo_producto'], $tipo_final);
                $stmt_producto->execute();
                $stmt_producto->close();
                
            } else { 
                // ESCENARIO 2: PRODUCTO EXISTENTE
                $tabla = ($tipo_final === 'maquina') ? 'maquinas' : 'repuestos';
                
                // 2.A. Actualizar Stock y Precio
                $sql_update = "UPDATE {$tabla} SET stock = stock + ?, precio_venta = ? WHERE codigo = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ids", $datosCompra['cantidad'], $precio_venta_nuevo, $datosCompra['codigo_producto']);
                $stmt_update->execute();
                $stmt_update->close();
            }

            // --- 3. REGISTRAR COMPRA (Con Factura y Proveedor) ---
            $sql_insert_compra = "INSERT INTO compra (num_factura, id_proveedor, codigo_producto, fecha_compra, cantidad, precio_compra_unitario) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert_compra);
            // Parámetros: sissid (string, int, string, string, int, double)
            $stmt_insert->bind_param("sissid", 
                $datosCompra['num_factura'], 
                $datosCompra['id_proveedor'],
                $datosCompra['codigo_producto'], 
                $datosCompra['fecha_compra'], 
                $datosCompra['cantidad'], 
                $datosCompra['precio_compra_unitario']
            );
            $stmt_insert->execute();
            $stmt_insert->close();
            
            $conn->commit();
            $_SESSION['mensaje_exito'] = "Compra registrada exitosamente (Factura: {$datosCompra['num_factura']}).";
            header("Location: index.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            if ($conn->errno == 1062) { 
                $error = "Error: Código duplicado o factura repetida.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-container" style="max-width: 900px;">
    <h2>Registrar Compra</h2>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" id="form-compra-crear">
        
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
            <h3 style="margin-top:0;">1. Datos de Facturación</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="id_proveedor">Proveedor:</label>
                    <select name="id_proveedor" id="id_proveedor" required>
                        <option value="">Seleccione Proveedor</option>
                        <?php while($prov = $proveedores->fetch_assoc()): ?>
                            <option value="<?= $prov['id'] ?>" <?= ($datosCompra['id_proveedor'] == $prov['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../proveedores/crear.php">¿Nuevo proveedor?</a></small>
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="num_factura">N° Factura / Control:</label>
                    <input type="text" id="num_factura" name="num_factura" value="<?= htmlspecialchars($datosCompra['num_factura']) ?>" required placeholder="Ej. 0001254">
                </div>

                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="fecha_compra">Fecha Emisión:</label>
                    <input type="date" id="fecha_compra" name="fecha_compra" value="<?= htmlspecialchars($datosCompra['fecha_compra']) ?>" required>
                </div>
            </div>
        </div>

        <h3>2. Selección de Producto</h3>
        <div class="form-group mode-selector">
            <input type="radio" id="modo_existente" name="modo_registro" value="existente" <?= ($modo_seleccionado == 'existente' || empty($modo_seleccionado)) ? 'checked' : '' ?>>
            <label for="modo_existente">Producto Registrado (Reponer Stock)</label>
            
            <input type="radio" id="modo_nuevo" name="modo_registro" value="nuevo" <?= ($modo_seleccionado == 'nuevo') ? 'checked' : '' ?>>
            <label for="modo_nuevo">Producto Nuevo (Crear Ficha + Stock)</label>
        </div>
        
        <hr/>

        <div id="compra_form_content">
            
            <div class="form-group">
                <label for="codigo_producto">Código de Producto:</label>
                <input type="text" id="codigo_producto" name="codigo_producto" value="<?= htmlspecialchars($datosCompra['codigo_producto']) ?>" required>
            </div>
            
            <div id="display_nombre_existente" class="alert-info" style="margin-bottom: 15px; display: none; padding: 10px; border-radius: 4px;"></div>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1;">
                    <label for="cantidad">Cantidad (Unidades):</label>
                    <input type="number" id="cantidad" name="cantidad" min="1" value="<?= htmlspecialchars($datosCompra['cantidad']) ?>" required>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="precio_compra_unitario">Costo Unitario ($):</label>
                    <input type="number" id="precio_compra_unitario" name="precio_compra_unitario" step="0.01" min="0.01" value="<?= htmlspecialchars($datosCompra['precio_compra_unitario']) ?>" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label for="precio_venta">Precio Venta Público ($):</label>
                    <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($datosCompra['precio_venta']) ?>" required>
                </div>
            </div>

            <div id="campos-nuevo-producto" style="background-color: #eef; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <h4>Detalles del Nuevo Producto</h4>
                <div class="form-group">
                    <label for="tipo_producto">Tipo:</label>
                    <select id="tipo_producto" name="tipo_producto">
                        <option value="">Seleccione</option>
                        <option value="maquina" <?= ($datosCompra['tipo_producto'] == 'maquina') ? 'selected' : '' ?>>Máquina</option>
                        <option value="repuesto" <?= ($datosCompra['tipo_producto'] == 'repuesto') ? 'selected' : '' ?>>Repuesto</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nombre_producto">Nombre:</label>
                    <input type="text" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($datosCompra['nombre_producto']) ?>">
                </div>

                <div class="form-group">
                    <label for="modelo_producto">Modelo:</label>
                    <input type="text" id="modelo_producto" name="modelo_producto" value="<?= htmlspecialchars($datosCompra['modelo_producto']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion_producto">Descripción:</label>
                    <input type="text" id="descripcion_producto" name="descripcion_producto" value="<?= htmlspecialchars($datosCompra['descripcion_producto']) ?>">
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">Registrar Compra</button>
                <a href="index.php" class="btn secondary">Cancelar</a>
            </div>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeRadios = document.querySelectorAll('input[name="modo_registro"]');
    const newProductFields = document.getElementById('campos-nuevo-producto');
    const codigoInput = document.getElementById('codigo_producto');
    const nameDisplay = document.getElementById('display_nombre_existente');
    
    // Campos requeridos en modo nuevo
    const creationFields = [
        document.getElementById('tipo_producto'),
        document.getElementById('nombre_producto'),
        document.getElementById('modelo_producto')
    ];
    
    function updateFormMode(mode) {
        if (mode === 'nuevo') {
            newProductFields.style.display = 'block';
            nameDisplay.style.display = 'none';
            creationFields.forEach(f => f.required = true);
        } else {
            newProductFields.style.display = 'none';
            nameDisplay.style.display = 'block';
            creationFields.forEach(f => f.required = false);
        }
        checkProductCode(codigoInput.value.trim());
    }
    
    let checkTimeout;
    function checkProductCode(codigo) {
        clearTimeout(checkTimeout);
        const selectedMode = document.querySelector('input[name="modo_registro"]:checked').value;
        
        if (selectedMode === 'existente' && codigo.length > 0) {
            nameDisplay.style.display = 'block';
            nameDisplay.innerHTML = 'Buscando...';
            
            checkTimeout = setTimeout(() => {
                fetch(`buscar_producto.php?codigo=${encodeURIComponent(codigo)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            nameDisplay.className = 'alert success';
                            nameDisplay.innerHTML = `✅ <b>${data.nombre}</b> (${data.tipo_producto}) - Precio Actual: $${data.precio_venta}`;
                            document.getElementById('precio_venta').value = data.precio_venta;
                        } else {
                            nameDisplay.className = 'alert error';
                            nameDisplay.innerHTML = `❌ ${data.error} Cambie a "Producto Nuevo".`;
                        }
                    })
                    .catch(() => nameDisplay.innerHTML = 'Error al buscar.');
            }, 500);
        } else {
            nameDisplay.style.display = 'none';
        }
    }

    modeRadios.forEach(radio => radio.addEventListener('change', (e) => updateFormMode(e.target.value)));
    codigoInput.addEventListener('keyup', (e) => checkProductCode(e.target.value));
    
    // Inicializar
    updateFormMode(document.querySelector('input[name="modo_registro"]:checked').value);
});
</script>

<?php include '../includes/footer.php'; ?>