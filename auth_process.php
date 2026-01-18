<?php
require_once 'config.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

try {
    $dns  = $_POST['dns'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $name = $_POST['list_name'] ?? 'Minha Lista';

    if (isset($_POST['auto']) && isset($_COOKIE['fastplay_profile'])) {
        $saved = json_decode($_COOKIE['fastplay_profile'], true);
        $dns = $saved['dns']; $user = $saved['u']; $pass = $saved['p']; $name = $saved['name'];
    }

    if (empty($dns) || empty($user) || empty($pass)) {
        throw new Exception("Preencha todos os campos.");
    }

    $dns = rtrim($dns, '/');

    // Monta URL da API
    $host = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $path = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
    $apiUrl = $host . $path . "api.php?dns=" . urlencode($dns) . "&u=" . urlencode($user) . "&p=" . urlencode($pass) . "&action=index";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['user_info']) && $data['user_info']['auth'] == 1) {
        $uInfo = $data['user_info'];
        
        // --- LÓGICA DE VALIDADE ---
        $exp_date = $uInfo['exp_date']; // Timestamp vindo do painel
        $hoje = time();
        $formatted_date = $uInfo['formatted_date'] ?? 'Ilimitado';

        // Se a data de expiração não for vazia, não for nula e for menor que agora
        if (!empty($exp_date) && $exp_date !== "null" && $exp_date < $hoje) {
            throw new Exception("Esta lista expirou em " . $formatted_date);
        }
        // --------------------------

        $_SESSION['iptu'] = [
            'dns' => $dns,
            'u'   => $user,
            'p'   => $pass,
            'name' => $name,
            'formatted_date' => $formatted_date,
            'status' => $uInfo['status'],
            'user_info' => $uInfo
        ];

        // Salvamos a 'validade' dentro do cookie para aparecer no login sem precisar consultar a API de novo
        $profile = [
            'dns' => $dns, 
            'u' => $user, 
            'p' => $pass, 
            'name' => $name,
            'validade' => $formatted_date
        ];
        
        setcookie('fastplay_profile', json_encode($profile), time() + (86400 * 30), "/");

        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Credenciais inválidas ou servidor offline.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
