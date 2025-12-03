<?php
session_start();
session_unset();
session_destroy();
header("Location: /servindteca/auth/login.php");
exit();
?>