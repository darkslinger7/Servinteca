<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/header.php';
?>

<main class="container">
    <div class="welcome-container">
        <h1 class="welcome-title">Bienvenido a Servindteca</h1>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-panel">
                <p class="user-greeting">Hola, <strong><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario') ?></strong></p>
                <div class="action-buttons">
                    <a href="/Servindteca/empresas/index.php" class="btn btn-primary">Ver Empresas</a>
                    <a href="servicios/index.php" class="btn btn-primary">Ver Servicios</a>
                    <a href="maquinas/index.php" class="btn btn-primary">Ver Máquinas</a>
                    <a href="repuestos/index.php" class="btn btn-primary">Ver Repuestos</a>
                </div>
            </div>
            <div class="action-buttons">
                <a href="proveedores/index.php" class="btn btn-primary">Proveedores</a>
                <a href="compras/index.php" class="btn btn-primary">Compras</a>
                <a href="ventas/index.php" class="btn btn-primary">Ventas</a>
                <a href="reportes/index.php" class="btn btn-primary">Estadisticas</a>
            </div>
             <div class="action-buttons">
                <a href="auth/logout.php" class="btn btn-logout">Cerrar sesión</a>
            </div>
            
        <?php else: ?>
            <div class="login-prompt">
                <p>Por favor inicia sesión para acceder al sistema</p>
                <a href="auth/login.php" class="btn btn-login">Iniciar sesión</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>