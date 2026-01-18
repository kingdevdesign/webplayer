<?php
require_once 'config.php';
if (isset($_SESSION['iptu'])) { header("Location: dashboard.php"); exit; }

$saved = isset($_COOKIE['fastplay_profile']) ? json_decode($_COOKIE['fastplay_profile'], true) : null;
$edit_mode = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GFPLAYER - Login</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #ff6600; --bg: #050505; --card: #121212; --border: rgba(255,255,255,0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        
        .auth-card { background: var(--card); padding: 30px; border-radius: 20px; border: 1px solid var(--border); width: 100%; max-width: 400px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .fast-logo { font-size: 32px; font-weight: 900; margin-bottom: 25px; letter-spacing: -1px; }
        .fast-logo span { color: var(--primary); }
        
        .input-group { margin-bottom: 15px; position: relative; width: 100%; }
        .input-group i { position: absolute; left: 15px; top: 18px; color: var(--primary); font-size: 14px; }
        .input-group input { width: 100%; background: #000; border: 1px solid var(--border); padding: 15px 15px 15px 45px; border-radius: 12px; color: #fff; outline: none; transition: 0.3s; font-size: 14px; }
        .input-group input:focus { border-color: var(--primary); }
        
        .btn-main { width: 100%; background: var(--primary); color: #fff; border: none; padding: 16px; border-radius: 12px; font-weight: bold; font-size: 14px; cursor: pointer; text-transform: uppercase; margin-top: 5px; }
        .btn-cancel { width: 100%; background: transparent; color: #888; border: 1px solid var(--border); padding: 12px; border-radius: 12px; font-weight: bold; font-size: 12px; cursor: pointer; margin-top: 10px; text-decoration: none; display: block; }
        
        .profile-box { background: #000; padding: 20px; border-radius: 15px; border: 1px solid var(--primary); display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; cursor: pointer; text-align: left; }
        .profile-info b { font-size: 16px; display: block; color: #fff; }
        .profile-info small { color: var(--primary); font-size: 11px; }

        /* Modal de Mensagem */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding: 20px; }
        .modal-content { background:var(--card); padding:30px; border-radius:20px; border:1px solid var(--primary); text-align:center; max-width: 300px; width: 100%; }
    </style>
</head>
<body>

    <div id="custom-modal" class="modal-overlay">
        <div class="modal-content" id="modal-body"></div>
    </div>

    <div class="auth-card">
        <h1 class="fast-logo">GF<span>PLAYER</span></h1>
        
        <?php if ($saved && !$edit_mode): ?>
    <div class="profile-box" onclick="autoLogin()">
        <div class="profile-info">
            <small>ENTRAR COM A LISTA:</small>
            <b><?php echo $saved['name']; ?></b>
            <span style="font-size: 11px; color: #888; display: block; margin-top: 2px;">
                <i class="far fa-calendar-alt"></i> Validade: 
                <span style="color: var(--primary);">
                    <?php echo $saved['validade'] ?? 'Consultando...'; ?>
                </span>
            </span>
        </div>
        <i class="fas fa-play-circle" style="color:var(--primary); font-size: 24px;"></i>
    </div>
    <a href="?edit=1" class="btn-cancel" style="border: none;"><i class="fas fa-user-cog"></i> EDITAR DADOS DA CONTA</a>


        <?php else: ?>
            <form id="login-form">
                <div class="input-group">
                    <i class="fas fa-tag"></i>
                    <input type="text" name="list_name" placeholder="Nome da Lista" value="<?php echo $edit_mode?$saved['name']:''; ?>" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-link"></i>
                    <input type="text" name="dns" placeholder="DNS do Servidor" value="<?php echo $edit_mode?$saved['dns']:''; ?>" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="user" placeholder="Usuário" value="<?php echo $edit_mode?$saved['u']:''; ?>" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="pass" placeholder="Senha" value="<?php echo $edit_mode?$saved['p']:''; ?>" required>
                </div>
                
                <button type="submit" class="btn-main">
                    <?php echo $edit_mode ? 'Salvar Alterações' : 'Conectar Agora'; ?>
                </button>

                <?php if ($edit_mode || $saved): ?>
                    <a href="index.php" class="btn-cancel">VOLTAR</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function showModal(msg, isError = false) {
            const icon = isError ? 'fa-exclamation-triangle' : 'fa-circle-notch fa-spin';
            const color = isError ? '#ff4444' : '#ff6600';
            $('#modal-body').html(`
                <i class="fas ${icon}" style="font-size:40px; color:${color}; margin-bottom:15px;"></i>
                <p style="color:#fff; font-weight:bold;">${msg}</p>
            `);
            $('#custom-modal').css('display','flex').hide().fadeIn();
        }

        $('#login-form').on('submit', function(e) {
            e.preventDefault();
            showModal('Autenticando...');
            
            $.post('auth_process.php', $(this).serialize(), function(res) {
                if(res.success) {
                    showModal('Acesso Concedido! Redirecionando...');
                    setTimeout(() => { window.location.href = 'dashboard.php'; }, 1000);
                } else {
                    showModal(res.message, true);
                    setTimeout(() => { $('#custom-modal').fadeOut(); }, 3000);
                }
            }, 'json').fail(function() {
                showModal('Erro interno no servidor.', true);
            });
        });

        // No seu script do login.php, adicione uma verificação extra no autoLogin
function autoLogin() {
    showModal('Verificando validade...');
    $.post('auth_process.php', { auto: true }, function(res) {
        if(res.success) { 
            window.location.href = 'dashboard.php'; 
        } else { 
            // Se o retorno for success: false (Expirado), mostra o erro em vermelho
            showModal(res.message, true); 
        }
    }, 'json');
}

    </script>
</body>
</html>
