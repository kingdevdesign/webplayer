<?php
session_start();
session_destroy();

// Limpa o cookie de login automático
if (isset($_COOKIE['iptv_login'])) {
    setcookie('iptv_login', '', time() - 3600, '/');
}

header("Location: index.php");
exit;
