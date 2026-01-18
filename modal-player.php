<?php
require_once 'config.php';
checkAuth();

$id   = $_GET['id']   ?? '';
$type = $_GET['type'] ?? ''; 
$dns  = rtrim($_GET['dns'], '/'); // Remove barra final se houver
$u    = $_GET['u']    ?? '';
$p    = $_GET['p']    ?? '';

// Construção da URL de Stream conforme padrões Xtream Codes
if ($type === 'live') {
    $streamUrl = "{$dns}/live/{$u}/{$p}/{$id}.m3u8";
    $containerType = 'application/x-mpegURL';
} elseif ($type === 'serie') {
    // Tentamos MP4 por padrão, mas o player é flexível
    $streamUrl = "{$dns}/series/{$u}/{$p}/{$id}.mp4"; 
    $containerType = 'video/mp4';
} else {
    // Filmes
    $streamUrl = "{$dns}/movie/{$u}/{$p}/{$id}.mp4";
    $containerType = 'video/mp4';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VU PLAYER PRO</title>
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <style>
        :root { --plyr-color-main: #ff6600; --plyr-video-background: #000; }
        body, html { margin: 0; padding: 0; background: #000; width: 100%; height: 100%; overflow: hidden; }
        .player-container { width: 100%; height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .btn-exit {
            position: fixed; top: 20px; left: 20px; z-index: 99;
            background: rgba(0, 0, 0, 0.6); border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff; padding: 12px 20px; border-radius: 50px;
            cursor: pointer; backdrop-filter: blur(10px); font-family: sans-serif;
            display: flex; align-items: center; gap: 8px; font-size: 14px; text-decoration: none;
        }
        
        .v-loader { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 5; background: #000; }
        .v-spinner { width: 45px; height: 45px; border: 4px solid rgba(255,102,0,0.1); border-left-color: #ff6600; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>



<div class="player-container">
    <div id="loading" class="v-loader"><div class="v-spinner"></div></div>
    <video id="player" playsinline controls autoplay></video>
</div>


<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('player');
    const source = "<?= $streamUrl ?>";
    const type = "<?= $type ?>";
    const loading = document.getElementById('loading');

    const player = new Plyr(video, {
        autoplay: true,
        muted: true, // Auto-play garantido
        settings: ['quality', 'speed']
    });

    if (type === 'live' && Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(source);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play();
            loading.style.display = 'none';
        });
    } else {
        // Para Filmes e Séries (VOD)
        video.src = source;
        video.addEventListener('loadedmetadata', () => {
            video.play();
            loading.style.display = 'none';
        });
    }

    // Monitor de Erro Aprimorado
    video.onerror = () => {
        // Se falhar como .mp4, tenta rodar sem extensão (alguns painéis exigem)
        if (source.includes('.mp4')) {
            const newSource = source.replace('.mp4', '');
            video.src = newSource;
        } else {
            console.error("Falha fatal no carregamento do vídeo.");
            loading.style.display = 'none';
        }
    };

    video.onplaying = () => {
        loading.style.display = 'none';
        setTimeout(() => { video.muted = false; }, 1000);
    };
});
</script>
</body>
</html>
