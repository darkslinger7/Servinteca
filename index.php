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
                    <a href="/Servindteca/empresas/index.php" class="btn btn-primary">Ver empresas</a>
                    <a href="servicios/index.php" class="btn btn-primary">Ver servicios</a>
                    
                    <a href="auth/logout.php" class="btn btn-logout">Cerrar sesión</a>
                </div>
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