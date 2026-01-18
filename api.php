<?php
/**
 * API PROXY - VU PLAYER
 * Mantém compatibilidade com Xtream UI e resolve bloqueios de imagem (CORS/SSL)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dns = $_GET['dns'] ?? '';
$u = $_GET['u'] ?? '';
$p = $_GET['p'] ?? '';
$action = $_GET['action'] ?? '';

// IDs específicos para filtros
$cat = $_GET['cat'] ?? '';
$series_id = $_GET['series_id'] ?? '';
$vod_id = $_GET['vod_id'] ?? '';
$stream_id = $_GET['stream_id'] ?? '';

// --- BLOCO 1: PROXY DE IMAGEM ROBUSTO ---
// Resolve o problema de imagens HTTP em sites HTTPS e bloqueios de User-Agent
if (isset($_GET['proxy_img'])) {
    $img_url = urldecode($_GET['proxy_img']);
    
    if (filter_var($img_url, FILTER_VALIDATE_URL)) {
        $ch = curl_init($img_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        // Simula navegador real para evitar bloqueio do servidor IPTV
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36');
        
        $img_data = curl_exec($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($img_data) {
            header("Content-Type: $content_type");
            echo $img_data;
            exit;
        }
    }
    // Caso a imagem falhe, retorna um placeholder vazio
    header("Content-Type: image/png");
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    exit;
}

// --- BLOCO 2: CONSTRUÇÃO DA URL DA API ---
$url = "$dns/player_api.php?username=$u&password=$p&action=$action";

if ($cat) $url .= "&category_id=$cat";
if ($series_id) $url .= "&series_id=$series_id";
if ($vod_id) $url .= "&vod_id=$vod_id";
if ($stream_id) $url .= "&stream_id=$stream_id";

// Correção para Info de VOD (Alguns painéis exigem ambos os parâmetros)
if ($action === 'get_vod_info' && $vod_id) {
    $url .= "&vod_id=$vod_id&stream_id=$vod_id";
}

// --- BLOCO 3: EXECUÇÃO DA REQUISIÇÃO ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    
    // Injeção de Validade Formatada
    if ($action === 'index' && isset($data['user_info']['exp_date'])) {
        $ts = $data['user_info']['exp_date'];
        if ($ts && $ts != "null" && is_numeric($ts)) {
            $data['user_info']['formatted_date'] = date('d/m/Y', (int)$ts);
        } else {
            $data['user_info']['formatted_date'] = 'Ilimitado';
        }
    }

    // Função interna para aplicar o Proxy nas imagens do JSON
    function applyProxy(&$item) {
        $keys = ['stream_icon', 'cover', 'movie_image', 'movie_data'];
        foreach($keys as $key) {
            if (isset($item[$key]) && !empty($item[$key]) && is_string($item[$key])) {
                // Só aplica se for uma URL e não tiver o proxy ainda
                if (strpos($item[$key], 'http') === 0) {
                    $item[$key] = "api.php?proxy_img=" . urlencode($item[$key]);
                }
            }
        }
    }

    // Percorre os dados para tratar as imagens
    if (is_array($data)) {
        // Trata objeto único (ex: info de filme)
        if (isset($data['info'])) applyProxy($data['info']);
        if (isset($data['movie_data'])) applyProxy($data['movie_data']);
        
        // Trata listas (ex: lista de filmes ou canais)
        foreach ($data as &$row) {
            if (is_array($row)) {
                applyProxy($row);
                // Se houver episódios dentro (Séries)
                if (isset($row['episodes']) && is_array($row['episodes'])) {
                    foreach ($row['episodes'] as &$season) {
                        foreach ($season as &$ep) applyProxy($ep);
                    }
                }
            }
        }
    }

    echo json_encode($data);
} else {
    echo json_encode(["error" => "Sem resposta do servidor IPTV"]);
}
