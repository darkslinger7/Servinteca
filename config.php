<?php

require_once __DIR__ . '/includes/database.php'; 
require_once __DIR__ . '/includes/functions.php';


if (session_status() === PHP_SESSION_NONE) { //al final no use este php ya que no me funciono correctamente en una version beta pero no lo quise eliminar por si acaso
    session_start();
}
?>