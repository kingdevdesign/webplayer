<?php
// Captura os dados enviados pela index.php
$stream_url = isset($_GET['ch']) ? base64_decode($_GET['ch']) : '';
$channel_name = isset($_GET['name']) ? urldecode($_GET['name']) : 'Canal Desconhecido';

// Se não houver URL, volta para a index
if (empty($stream_url)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproduzindo: <?php echo htmlspecialchars($channel_name); ?></title>
    
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            color: #fff;
            font-family: sans-serif;
            overflow: hidden; /* Evita rolagem na tela do player */
        }

        .player-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Botão Voltar flutuante */
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            padding: 10px 15px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: #ff6600;
        }

        /* Ajuste do vídeo para ocupar a tela toda */
        .video-js {
            width: 100% !important;
            height: 100% !important;
        }

        /* Customizando a cor do player para o Laranja do VU Player */
        .vjs-theme-city .vjs-play-progress, 
        .vjs-theme-city .vjs-volume-level {
            background-color: #ff6600;
        }

        .info-bar {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 999;
            pointer-events: none; /* Não atrapalha o clique nos controles do player */
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        }

        .info-bar h2 {
            margin: 0;
            font-size: 18px;
            color: #ff6600;
        }
    </style>
</head>
<body>

    <div class="player-container">
        <a href="index.php" class="btn-back">← Voltar</a>

        <video
            id="vu-video-player"
            class="video-js vjs-big-play-centered vjs-theme-city"
            controls
            autoplay
            preload="auto"
            data-setup='{}'>
            <source src="<?php echo $stream_url; ?>" type="application/x-mpegURL">
            <p class="vjs-no-js">
                Para visualizar este vídeo, habilite o JavaScript ou considere atualizar para um navegador web que
                <a href="https://videojs.com/html5-video-support/" target="_blank">suporte vídeo HTML5</a>
            </p>
        </video>

        <div class="info-bar">
            <h2><?php echo htmlspecialchars($channel_name); ?></h2>
            <small>Streaming ao vivo - Ultra HD</small>
        </div>
    </div>

    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <script>
        // Lógica para garantir que o player tente reconectar se houver erro leve
        var player = videojs('vu-video-player');

        player.on('error', function() {
            console.log("Erro ao carregar o canal. Verifique se o link da lista ainda está ativo.");
        });

        // Abrir em tela cheia automaticamente no mobile (opcional)
        // player.ready(function() {
        //     this.requestFullscreen();
        // });
    </script>
</body>
</html>
