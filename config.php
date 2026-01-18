<?php
session_start();
$admin_name = "V PLAYER";
$year = "2026";

// Função para verificar se está logado
function checkAuth() {
    if (!isset($_SESSION['iptu'])) {
        header("Location: index.php");
        exit;
    }
}
?>
