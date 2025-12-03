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
            </div>
        </div>

        <div class="dropdown">
            <span class="dropbtn"><i class="fas fa-boxes"></i> Inventario</span>
            <div class="dropdown-content">
                <a href="/servindteca/maquinas/index.php">Catálogo de Máquinas</a>
                <a href="/servindteca/repuestos/index.php">Catálogo de Repuestos</a>
            </div>
        </div>

        <div class="dropdown">
            <span class="dropbtn"><i class="fas fa-address-book"></i> Directorios</span>
            <div class="dropdown-content">
                <a href="/servindteca/empresas/index.php">Clientes (Empresas)</a>
                <a href="/servindteca/proveedores/index.php">Proveedores</a>
            </div>
        </div>

        <a href="/servindteca/reportes/index.php"><i class="fas fa-chart-line"></i> Reportes</a>

        <a href="/servindteca/auth/logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </nav>
    <?php endif; ?>

    <main>