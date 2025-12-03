<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$mensajeExito = '';
$error = '';
// Inicializar modo seleccionado para persistencia
$modo_seleccionado = ''; 

$datosCompra = [
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
    
    // Captura de modo para validación estricta
    $modo_seleccionado = limpiar($_POST['modo_registro'] ?? '');

    // Captura de datos
    $datosCompra['codigo_producto'] = limpiar($_POST['codigo_producto'] ?? '');
    $datosCompra['fecha_compra'] = limpiar($_POST['fecha_compra'] ?? date('Y-m-d'));
    $datosCompra['cantidad'] = intval($_POST['cantidad'] ?? 0); 
    $datosCompra['precio_compra_unitario'] = floatval($_POST['precio_compra_unitario'] ?? 0); 
    $precio_venta_nuevo = floatval($_POST['precio_venta'] ?? 0);
    
    // Datos de creación (pueden venir vacíos si es modo "Existente")
    $datosCompra['tipo_producto'] = limpiar($_POST['tipo_producto'] ?? ''); 
    $datosCompra['nombre_producto'] = limpiar($_POST['nombre_producto'] ?? ''); 
    $datosCompra['modelo_producto'] = limpiar($_POST['modelo_producto'] ?? ''); 
    $datosCompra['descripcion_producto'] = limpiar($_POST['descripcion_producto'] ?? ''); 

    // --- Validación BÁSICA ---
    if (empty($modo_seleccionado)) {
        $error = "Debe seleccionar si es un producto registrado o un producto nuevo.";
    } elseif ($datosCompra['cantidad'] <= 0 || $datosCompra['precio_compra_unitario'] <= 0 || $precio_venta_nuevo <= 0) {
        $error = "La cantidad y los precios deben ser valores positivos.";
    } elseif (empty($datosCompra['codigo_producto'])) {
        $error = "Debe ingresar el código del producto.";
    } else {
        
        try {
            $conn->begin_transaction();
            
            // --- 1. Determinar si el código existe ---
            $sql_check = "SELECT tipo_producto FROM producto WHERE codigo_unificado = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $datosCompra['codigo_producto']);
            $stmt_check->execute();
            $stmt_check->bind_result($tipo_existente);
            $stmt_check->fetch();
            $stmt_check->close();
            
            $es_producto_nuevo = empty($tipo_existente);
            $tipo_final = $tipo_existente;
            
            // VALIDACIÓN ESTRICTA DEL MODO SELECCIONADO VS EXISTENCIA EN DB
            
            if ($modo_seleccionado == 'nuevo' && !$es_producto_nuevo) {
                throw new Exception("ERROR CRÍTICO: El código **{$datosCompra['codigo_producto']}** ya existe. Cambie el modo de registro a 'Producto Registrado'.");
            }
            if ($modo_seleccionado == 'existente' && $es_producto_nuevo) {
                 throw new Exception("ERROR CRÍTICO: El código **{$datosCompra['codigo_producto']}** NO existe. Cambie el modo de registro a 'Producto Nuevo'.");
            }
            
            
            if ($modo_seleccionado == 'nuevo') {
                // --- ESCENARIO 1: CREAR NUEVO PRODUCTO Y REGISTRAR COMPRA INICIAL ---
                
                // Validación estricta para creación
                if (empty($datosCompra['tipo_producto']) || ($datosCompra['tipo_producto'] !== 'maquina' && $datosCompra['tipo_producto'] !== 'repuesto')) {
                    throw new Exception("Debe especificar si es Máquina o Repuesto.");
                }
                if (empty($datosCompra['nombre_producto']) || empty($datosCompra['modelo_producto'])) {
                     throw new Exception("Nombre y Modelo son requeridos para crear un producto nuevo.");
                }

                $tipo_final = $datosCompra['tipo_producto'];
                $tabla = ($tipo_final === 'maquina') ? 'maquinas' : 'repuestos';
                
                // 1.A. Insertar en tabla nativa
                $sql_nativo = "INSERT INTO {$tabla} (nombre, codigo, modelo, descripcion, precio_venta, stock) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_nativo = $conn->prepare($sql_nativo);
                $stmt_nativo->bind_param("ssssdi", 
                    $datosCompra['nombre_producto'], $datosCompra['codigo_producto'], 
                    $datosCompra['modelo_producto'], $datosCompra['descripcion_producto'], 
                    $precio_venta_nuevo, $datosCompra['cantidad']
                );
                $stmt_nativo->execute();
                $stmt_nativo->close();

                // 1.B. Insertar en tabla 'producto' (Catálogo Unificado)
                $sql_producto = "INSERT INTO producto (codigo_unificado, tipo_producto) VALUES (?, ?)";
                $stmt_producto = $conn->prepare($sql_producto);
                $stmt_producto->bind_param("ss", $datosCompra['codigo_producto'], $tipo_final);
                $stmt_producto->execute();
                $stmt_producto->close();
                
                $mensaje_accion = "Producto **nuevo** ({$tipo_final}) creado y ";
                
            } else { // $modo_seleccionado == 'existente'
                // --- ESCENARIO 2: COMPRA DE PRODUCTO EXISTENTE (SOLO ACTUALIZAR STOCK/PRECIO) ---
                
                $tabla = ($tipo_final === 'maquina') ? 'maquinas' : 'repuestos';
                
                // 2.A. Actualizar Stock y Precio de Venta
                $sql_update = "UPDATE {$tabla} 
                               SET stock = stock + ?, precio_venta = ? 
                               WHERE codigo = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ids", $datosCompra['cantidad'], $precio_venta_nuevo, $datosCompra['codigo_producto']);
                $stmt_update->execute();
                $stmt_update->close();
                
                $mensaje_accion = "Stock de producto **existente** ({$tipo_final}) ";
            }

            // --- 3. Registrar la Compra en la tabla 'compra' (Paso común) ---
            $sql_insert_compra = "INSERT INTO compra (codigo_producto, fecha_compra, cantidad, precio_compra_unitario) 
                                 VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert_compra);
            $stmt_insert->bind_param("ssid", 
                $datosCompra['codigo_producto'], $datosCompra['fecha_compra'], 
                $datosCompra['cantidad'], $datosCompra['precio_compra_unitario']
            );
            $stmt_insert->execute();
            $stmt_insert->close();
            
            $conn->commit();
            $mensajeExito = $mensaje_accion . "registrado como compra y stock actualizado exitosamente.";
            
            // Limpiar formulario después de éxito
            $datosCompra = [
                'codigo_producto' => '', 'fecha_compra' => date('Y-m-d'), 'cantidad' => '', 
                'precio_compra_unitario' => '', 'precio_venta' => '', 'tipo_producto' => '',
                'nombre_producto' => '', 'modelo_producto' => '', 'descripcion_producto' => ''
            ];
            $modo_seleccionado = '';
            
        } catch (Exception $e) {
            $conn->rollback();
            // Error 1062 es por duplicidad de código
            if ($conn->errno == 1062) { 
                $error = "Error: El código de producto ya existe y hubo un error de consistencia. Verifique su selección.";
            } else {
                $error = "Error al procesar: " . $e->getMessage();
            }
        }
    }
} else {
    $datosCompra['fecha_compra'] = date('Y-m-d');
}

// Persistir datos en caso de error
if ($error) {
    if (isset($_POST['modo_registro'])) $modo_seleccionado = limpiar($_POST['modo_registro']);
    // Los datos de POST ya están en $datosCompra, solo hay que usar el valor de la captura.
}

?>

<?php include '../includes/header.php'; ?>

<section class="form-container">
    <h2>Registrar Compra</h2>
    
    <?php if($mensajeExito): ?>
        <div class="alert success"><?= $mensajeExito ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" id="form-compra-crear">
        
        <h3>1. Seleccione el Tipo de Registro</h3>
        <div class="form-group mode-selector">
            <input type="radio" id="modo_existente" name="modo_registro" value="existente" required 
                <?= ($modo_seleccionado == 'existente' || empty($modo_seleccionado)) ? 'checked' : '' ?>>
            <label for="modo_existente">Producto **Registrado** (Solo añadir stock/precio)</label><br>
            
            <input type="radio" id="modo_nuevo" name="modo_registro" value="nuevo" 
                <?= ($modo_seleccionado == 'nuevo') ? 'checked' : '' ?>>
            <label for="modo_nuevo">Producto **Nuevo** (Creación + Compra Inicial)</label>
        </div>
        
        <hr/>

        <div id="compra_form_content" style="display: none;">

            <h3>2. Datos de Compra</h3>
            
            <div class="form-group">
                <label for="codigo_producto">Código de Producto Único:</label>
                <input type="text" id="codigo_producto" name="codigo_producto" value="<?= htmlspecialchars($datosCompra['codigo_producto']) ?>" required>
            </div>
            
            <div id="display_nombre_existente" class="alert-info" style="margin-bottom: 15px; display: none;"></div>
            
            <div class="form-group">
                <label for="fecha_compra">Fecha de Compra:</label>
                <input type="date" id="fecha_compra" name="fecha_compra" value="<?= htmlspecialchars($datosCompra['fecha_compra']) ?>" required>
            </div>

            <div class="form-group">
                <label for="cantidad">Cantidad Comprada (Stock):</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="<?= htmlspecialchars($datosCompra['cantidad']) ?>" required>
            </div>

            <div class="form-group">
                <label for="precio_compra_unitario">Precio de Compra (Costo Unitario $):</label>
                <input type="number" id="precio_compra_unitario" name="precio_compra_unitario" step="0.01" min="0.01" value="<?= htmlspecialchars($datosCompra['precio_compra_unitario']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="precio_venta">Precio de Venta (Nuevo Precio $):</label>
                <input type="number" id="precio_venta" name="precio_venta" step="0.01" min="0.01" value="<?= htmlspecialchars($datosCompra['precio_venta']) ?>" required>
                <small>Este valor actualizará el Precio de Venta en el catálogo.</small>
            </div>

            <div id="campos-nuevo-producto">
                <hr/>
                <h3>3. Datos de Creación del Producto Nuevo</h3>
                <div class="form-group">
                    <label for="tipo_producto">Tipo de Producto:</label>
                    <select id="tipo_producto" name="tipo_producto">
                        <option value="">Seleccione</option>
                        <option value="maquina" <?= ($datosCompra['tipo_producto'] == 'maquina') ? 'selected' : '' ?>>Máquina</option>
                        <option value="repuesto" <?= ($datosCompra['tipo_producto'] == 'repuesto') ? 'selected' : '' ?>>Repuesto</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nombre_producto">Nombre del Producto:</label>
                    <input type="text" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($datosCompra['nombre_producto']) ?>">
                </div>

                <div class="form-group">
                    <label for="modelo_producto">Modelo:</label>
                    <input type="text" id="modelo_producto" name="modelo_producto" value="<?= htmlspecialchars($datosCompra['modelo_producto']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion_producto">Descripción (Opcional):</label>
                    <input type="text" id="descripcion_producto" name="descripcion_producto" value="<?= htmlspecialchars($datosCompra['descripcion_producto']) ?>">
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn">Registrar Compra</button>
                <a href="index.php" class="btn secondary">Cancelar</a>
            </div>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modeRadios = document.querySelectorAll('input[name="modo_registro"]');
    const contentDiv = document.getElementById('compra_form_content');
    const newProductFields = document.getElementById('campos-nuevo-producto');
    const codigoInput = document.getElementById('codigo_producto');
    const nameInput = document.getElementById('nombre_producto');
    const modelInput = document.getElementById('modelo_producto');
    const typeSelect = document.getElementById('tipo_producto');
    const nameDisplay = document.getElementById('display_nombre_existente');
    
    // Campos requeridos solo para modo 'nuevo'
    const creationFields = [nameInput, modelInput, typeSelect];
    
    // Función para manejar la visibilidad y requerimientos
    function updateFormMode(mode) {
        contentDiv.style.display = 'block'; // Mostrar la sección de contenido

        if (mode === 'nuevo') {
            // Modo Nuevo: Mostrar campos de creación y hacerlos requeridos
            newProductFields.style.display = 'block';
            nameDisplay.style.display = 'none';
            nameDisplay.innerHTML = '';
            
            creationFields.forEach(field => {
                field.required = true;
                field.removeAttribute('disabled');
            });
            codigoInput.focus();
        
        } else if (mode === 'existente') {
            // Modo Existente: Ocultar campos de creación y quitar requerimientos
            newProductFields.style.display = 'none';
            nameDisplay.style.display = 'block'; 
            
            creationFields.forEach(field => {
                field.required = false;
                // Deshabilitar para que el navegador no envíe estos campos en el POST
                field.setAttribute('disabled', 'disabled'); 
                field.value = ''; // Limpiar valores
            });
        }
        
        // Ejecutar la verificación del código (con retraso si es necesario)
        setTimeout(() => checkProductCode(codigoInput.value.trim()), 50);
    }
    
    // Función para buscar el nombre del producto al ingresar el código (Solo en modo 'existente')
    let checkTimeout;

    function checkProductCode(codigo) {
        clearTimeout(checkTimeout);
        const selectedMode = document.querySelector('input[name="modo_registro"]:checked').value;
        
        // Solo mostrar la caja de mensajes si estamos en modo existente y el código no está vacío
        nameDisplay.style.display = (selectedMode === 'existente' && codigo.length > 0) ? 'block' : 'none';

        if (selectedMode === 'existente' && codigo.length > 0) {
            
            checkTimeout = setTimeout(() => {
                nameDisplay.innerHTML = '<span style="color: #004085;">Buscando producto en el catálogo...</span>';
                nameDisplay.style.backgroundColor = '#cce5ff';
                nameDisplay.style.color = '#004085';

                fetch(`buscar_producto.php?codigo=${encodeURIComponent(codigo)}`) 
                    .then(response => {
                        // Intentar parsear el JSON. Si falla, es un error de PHP en el script auxiliar
                        if (!response.ok) {
                            throw new Error('Error de conexión o fallo interno del script auxiliar.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            nameDisplay.style.backgroundColor = '#d4edda';
                            nameDisplay.style.color = '#155724';
                            nameDisplay.innerHTML = `✅ Producto Encontrado: <b>${data.nombre}</b> (${data.tipo_producto}) - Precio Venta Actual: $${data.precio_venta}`;
                            document.getElementById('precio_venta').value = data.precio_venta;
                            
                        } else {
                            nameDisplay.style.backgroundColor = '#f8d7da';
                            nameDisplay.style.color = '#721c24';
                            nameDisplay.innerHTML = `❌ Error: ${data.error}. Si es un producto nuevo, **debe cambiar el modo a 'Producto Nuevo'**.`;
                            document.getElementById('precio_venta').value = '';
                        }
                    })
                    .catch(error => {
                        nameDisplay.style.backgroundColor = '#fff3cd';
                        nameDisplay.style.color = '#856404';
                        nameDisplay.innerHTML = `⚠️ Error al verificar: ${error.message}.`;
                        document.getElementById('precio_venta').value = '';
                    });
            }, 300); // Pequeño debounce
        } else if (selectedMode === 'existente' && codigo.length === 0) {
             nameDisplay.innerHTML = 'Ingrese el código para verificar la existencia del producto.';
             nameDisplay.style.backgroundColor = '#e9ecef';
             nameDisplay.style.color = '#000';
             document.getElementById('precio_venta').value = '';
        }
    }
    
    // Asignar listeners
    modeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => updateFormMode(e.target.value));
    });

    // Usamos keyup en lugar de change para una mejor experiencia de usuario al escribir
    codigoInput.addEventListener('keyup', (e) => checkProductCode(e.target.value.trim()));
    
    // Inicializar el modo
    const initialMode = '<?= $modo_seleccionado ?: 'existente' ?>';
    updateFormMode(initialMode);
    
    // Si hubo una respuesta del servidor (éxito o error), mostramos el contenido inmediatamente
    <?php if($error || $mensajeExito): ?>
        contentDiv.style.display = 'block';
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>