<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servindteca - <?= $titulo ?? 'Inicio' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/servindteca/css/styles.css">
    <link rel="icon" href="/servindteca/img/logo.png">
</head>
<body>
    <header>
        <img src="/servindteca/img/logo.png" alt="Logo Servindteca" class="logo">
        <h1>Sistema de Gestión de Mantenimiento</h1>
    </header>
    <nav>
        <a href="/servindteca/index.php">Inicio</a>
        <a href="/servindteca/empresas/index.php">Empresas</a>
        <a href="/servindteca/servicios/index.php">Servicios</a>
        <a href="/servindteca/maquinas/index.php">Maquinas</a>
        <a href="/servindteca/repuestos/index.php">Repuestos</a>
        <a href="/servindteca/ventas/index.php">Ventas</a>
        <a href="/servindteca/compra/index.php">Compras</a>
        <a href="/servindteca/reportes/index.php">Reportes</a>

        <?php if(isset($_SESSION['usuario_id'])): ?>
            <a href="/servindteca/auth/logout.php" class="logout">Cerrar Sesión</a>
        <?php endif; ?>
    </nav>
    <main>