<?php
require_once 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['parental_password'] ?? '0000';
    $user_id = $_SESSION['iptu']['id']; // Assumindo que você tem o ID do usuário na sessão

    // Substitua 'usuarios' pelo nome da sua tabela e garanta que a coluna 'senha_parental' exista
    $stmt = $db->prepare("UPDATE usuarios SET senha_parental = ? WHERE id = ?");
    if ($stmt->execute([$new_pass, $user_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
