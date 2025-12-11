<?php

$num_alertas = 0;
$res_alertas = null;

if (isset($_SESSION['user_id']) && isset($conn)) {
    $sql_alertas = "SELECT codigo, nombre, stock, tipo FROM productos 
                    WHERE stock <= 5 AND tipo != 'servicio' 
                    ORDER BY stock ASC";
    $res_alertas = $conn->query($sql_alertas);
    if ($res_alertas) {
        $num_alertas = $res_alertas->num_rows;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servindteca - Sistema de Gestión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/servindteca/css/styles.css">
    <link rel="icon" href="/servindteca/img/logo.png">
</head>
<body>

    <header>
        <div class="logo-container">
            <h1>Sistema de Gestión Servindteca</h1>
        </div>
    </header>

    <?php if(isset($_SESSION['user_id'])): ?>
    <nav>
        <a href="/servindteca/index.php"><i class="fas fa-home"></i> Inicio</a>

       <div class="dropdown">
    <span class="dropbtn"><i class="fas fa-cogs"></i> Operaciones</span>
    <div class="dropdown-content">
        <a href="/servindteca/ventas/index.php">Ventas</a>
        <a href="/servindteca/compras/index.php">Compras</a>
        <a href="/servindteca/servicios/index.php">Servicios Técnicos</a>
        <a href="/servindteca/tipos_servicios/index.php">Tipos de Servicio</a>
    </div>
</div>

        <div class="dropdown">
            <span class="dropbtn"><i class="fas fa-boxes"></i> Inventario</span>
            <div class="dropdown-content">
                <a href="/servindteca/productos/index.php">Catálogo General</a>
            </div>
        </div>

        <div class="dropdown">
            <span class="dropbtn"><i class="fas fa-address-book"></i> Directorios</span>
            <div class="dropdown-content">
                <a href="/servindteca/empresas/index.php">Clientes (Empresas)</a>
                <a href="/servindteca/proveedores/index.php">Proveedores</a>
            </div>
        </div>

        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            
            <a href="/servindteca/reportes/index.php"><i class="fas fa-chart-line"></i> Reportes</a>
            
            <div class="dropdown">
                <span class="dropbtn" style="color: #002366;"><i class="fas fa-user-shield"></i> Admin</span>
                <div class="dropdown-content">
                    <a href="/servindteca/usuarios/index.php">Gestión de Usuarios</a>
                </div>
            </div>

        <?php endif; ?>

        <div class="dropdown notification-bell">
            <span class="dropbtn" style="position: relative; padding-right: 10px;">
                <i class="fas fa-bell" style="font-size: 1.2rem; color: <?= $num_alertas > 0 ? '#e74c3c' : '#ccc' ?>;"></i>
                <?php if($num_alertas > 0): ?>
                    <span class="badge" style="position: absolute; top: -5px; right: 0; background: #e74c3c; color: white; font-size: 0.7rem; padding: 2px 5px; border-radius: 50%;"><?= $num_alertas ?></span>
                <?php endif; ?>
            </span>
            <div class="dropdown-content" style="width: 300px; right: 0; left: auto; transform: none; max-height: 400px; overflow-y: auto;">
                <div style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; background: #f9f9f9; color: #333;">
                    Alertas de Stock Bajo
                </div>
                
                <?php if($num_alertas > 0): ?>
                    <?php while($prod = $res_alertas->fetch_assoc()): ?>
                        <a href="/servindteca/productos/index.php?buscar=<?= urlencode($prod['nombre']) ?>" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;">
                            <div style="line-height: 1.2;">
                                <div style="font-weight: bold; font-size: 0.9rem;"><?= htmlspecialchars($prod['nombre']) ?></div>
                                <small style="color:#999;"><?= htmlspecialchars($prod['codigo']) ?></small>
                            </div>
                            <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold;">
                                <?= $prod['stock'] ?> un.
                            </span>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #999;">
                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; display: block; color: #22c55e;"></i>
                        Todo en orden.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="/servindteca/auth/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </nav>
    <?php endif; ?>

    <main>