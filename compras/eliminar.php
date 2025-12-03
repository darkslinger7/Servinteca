<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
require_once '../includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    
    $id_compra = (int)$_POST['id'];

    try {
        $conn->begin_transaction();

      
        $sql_select = "SELECT codigo_producto, cantidad FROM compra WHERE id_compra = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id_compra);
        $stmt_select->execute();
        $stmt_select->bind_result($codigo_producto, $cantidad_comprada);
        $stmt_select->fetch();
        $stmt_select->close();

        if (empty($codigo_producto)) {
            throw new Exception("Error: El registro de compra no existe.");
        }

        
        $sql_get_type = "SELECT tipo_producto FROM producto WHERE codigo_unificado = ?";
        $stmt_get_type = $conn->prepare($sql_get_type);
        $stmt_get_type->bind_param("s", $codigo_producto);
        $stmt_get_type->execute();
        $stmt_get_type->bind_result($tipo_producto);
        $stmt_get_type->fetch();
        $stmt_get_type->close();

        $tabla = ($tipo_producto === 'maquina') ? 'maquinas' : 'repuestos';
        
        $sql_revert_stock = "UPDATE {$tabla} SET stock = stock - ? WHERE codigo = ?";
        $stmt_update = $conn->prepare($sql_revert_stock);
        $stmt_update->bind_param("is", $cantidad_comprada, $codigo_producto);
        $stmt_update->execute();
        $stmt_update->close();

      
        $sql_delete = "DELETE FROM compra WHERE id_compra = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_compra);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $conn->commit();
        $_SESSION['mensaje_exito'] = "Compra eliminada y stock revertido correctamente.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje_exito'] = "Error al eliminar la compra: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    $_SESSION['mensaje_exito'] = "Acceso no autorizado o ID de compra no especificado.";
    header("Location: index.php");
    exit();
}
?>