<?php
require_once 'config.php';
session_start(); // Garante que a sessão está ativa

// Lógica de Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php"); // Altere para o nome da sua tela de login
    exit;
}

checkAuth();
$sess = $_SESSION['iptu'];
// Pega a data formatada que a sua api.php injetou no user_info
$validade = $sess['user_info']['formatted_date'] ?? 'Ilimitado';
$parental_pass = $sess['senha_parental'] ?? '0000'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GF PLAYER - DASHBOARD</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #ff6600; --bg: #050505; --card: #121212; --border: rgba(255,255,255,0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: #fff; height: 100vh; overflow: hidden; }
        
        /* Dashboard Home */
        .fast-logo { text-align: center; padding: 30px 20px; font-size: 26px; font-weight: 900; letter-spacing: -1px; }
        .nav-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 0 20px; }
        .nav-card { background: var(--card); padding: 25px; text-align: center; border-radius: 15px; border: 1px solid var(--border); transition: 0.3s; }
        .nav-card:active { transform: scale(0.95); background: #1a1a1a; }
        .nav-card i { color: var(--primary); font-size: 28px; margin-bottom: 12px; display: block; }

        /* Explorer e Renderização */
        #explorer { position: fixed; inset: 0; background: var(--bg); z-index: 1000; display: none; flex-direction: column; }
        .exp-top-bar { padding: 15px; background: #000; border-bottom: 1px solid var(--border); }
        .exp-nav { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; font-weight: bold; }
        .search-box input { width: 100%; background: #111; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; }
        
        .render-area { flex: 1; overflow-y: auto; padding: 15px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; align-content: start; padding-bottom: 80px; }
        
        /* Grid de Itens (Posters) */
        .i-card { background: var(--card); border-radius: 8px; overflow: hidden; border: 1px solid var(--border); position: relative; min-height: 150px; display: flex; flex-direction: column; }
        .i-card img { width: 100%; aspect-ratio: 2/3; object-fit: cover; background: #1a1a1a; }
        .i-card p { font-size: 10px; padding: 8px 4px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: rgba(0,0,0,0.8); width: 100%; }

        /* Categorias (Texto esquerda, ícone direita) */
        .cat-item { background: var(--card); padding: 16px; border-radius: 12px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border); }
        .cat-item span { flex: 1; font-size: 14px; font-weight: 500; }
        .cat-item i { color: var(--primary); font-size: 14px; margin-left: 10px; }

        /* Modais */
        #details-view, #settings-view, #parental-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 2000; display: none; flex-direction: column; padding: 25px; overflow-y: auto; }
        #parental-modal { z-index: 4000; align-items: center; justify-content: center; background: rgba(0,0,0,0.95); }
        
        .season-tabs { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; margin-bottom: 15px; }
        .tab { padding: 8px 18px; background: var(--card); border-radius: 20px; border: 1px solid var(--border); white-space: nowrap; font-size: 12px; color: #fff; }
        .tab.active { background: var(--primary); border-color: var(--primary); }

        /* Configurações */
        .info-box { background: var(--card); padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 15px; }
        .info-box label { color: var(--primary); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px; }
        .input-main { width: 100%; background: #000; border: 1px solid var(--border); color: #fff; padding: 14px; border-radius: 10px; margin-top: 10px; outline: none; }
        
        /* Player */
        #player-container { position: fixed; inset: 0; background: #000; z-index: 5000; display: none; }
        #player-frame { width: 100%; height: 100%; border: none; }

        .spinner { width: 30px; height: 30px; border: 3px solid rgba(255,255,255,0.1); border-left-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 40px auto; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<header class="fast-logo">GF<span style="color:var(--primary);">PLAYER</span></header>
<div class="nav-grid">
    <div class="nav-card" onclick="App.open('live')"><i class="fas fa-tv"></i><b>CANAIS</b></div>
    <div class="nav-card" onclick="App.open('movie')"><i class="fas fa-film"></i><b>FILMES</b></div>
    <div class="nav-card" onclick="App.open('serie')"><i class="fas fa-video"></i><b>SÉRIES</b></div>
    <div class="nav-card" onclick="App.openSettings()"><i class="fas fa-cog"></i><b>AJUSTES</b></div>
    <div class="nav-card" style="grid-column: span 2;" onclick="location.href='dashboard.php?logout=1'"><i class="fas fa-power-off"></i><b>SAIR DA CONTA</b></div>
</div>

<div id="explorer">
    <div class="exp-top-bar">
        <div class="exp-nav">
            <i class="fas fa-arrow-left" style="color:var(--primary);" onclick="App.back()"></i>
            <span id="exp-title">VOLTAR</span>
        </div>
        <div class="search-box"><input type="text" id="search" placeholder="Buscar conteúdo..." onkeyup="App.filter(this.value)"></div>
    </div>
    <div class="render-area" id="render"></div>
</div>

<div id="details-view">
    <div class="exp-nav" style="margin-bottom:20px;" onclick="$('#details-view').hide()"><i class="fas fa-times"></i> FECHAR</div>
    <div id="details-content"></div>
</div>

<div id="settings-view">
    <div class="exp-nav" style="margin-bottom:20px;" onclick="$('#settings-view').hide()"><i class="fas fa-arrow-left"></i> CONFIGURAÇÕES</div>
    
    <div class="info-box">
        <label>Playlist Ativa</label>
        <div style="font-size:18px; font-weight:bold;"><?= $sess['name'] ?></div>
    </div>

    <div class="info-box">
    <label>Vencimento da Lista</label>
    <div style="color:#4caf50; font-weight:bold;">
        <?= $validade ?>
    </div>
</div>


    <div class="info-box">
        <label>Senha Parental (Conteúdo Adulto)</label>
        <input type="number" id="new-parental-pass" class="input-main" value="<?= $parental_pass ?>" pattern="\d*" maxlength="4">
        <button onclick="App.saveSettings()" style="width:100%; background:var(--primary); border:none; color:#fff; padding:15px; border-radius:10px; margin-top:15px; font-weight:bold;">SALVAR NOVA SENHA</button>
    </div>
</div>

<div id="parental-modal">
    <i class="fas fa-lock" style="font-size:50px; color:var(--primary); margin-bottom:20px;"></i>
    <h2 style="margin-bottom:10px;">Área Restrita</h2>
    <p style="opacity:0.6; margin-bottom:20px;">Digite sua senha de 4 dígitos</p>
    <input type="password" id="parental-input" class="input-main" style="text-align:center; font-size:24px; letter-spacing:10px; max-width:200px;" maxlength="4">
    <div style="display:flex; gap:10px; margin-top:30px;">
        <button class="tab" onclick="$('#parental-modal').hide()">CANCELAR</button>
        <button class="tab active" onclick="App.unlock()">DESBLOQUEAR</button>
    </div>
</div>

<div id="player-container">
    <div style="position:fixed; top:20px; left:20px; z-index:5100; background:rgba(0,0,0,0.6); width:45px; height:45px; border-radius:50%; display:flex; align-items:center; justify-content:center;" onclick="App.closePlayer()">
        <i class="fas fa-arrow-left"></i>
    </div>
    <iframe id="player-frame" src="" allowfullscreen></iframe>
</div>

<script>
const creds = { dns: "<?= $sess['dns'] ?>", u: "<?= $sess['u'] ?>", p: "<?= $sess['p'] ?>" };
let parentalTarget = null;

const App = {
    type: '',
    view: 'cats',
    currentData: [],
    tempEpisodes: {},

    open: (t) => { App.type = t; App.view = 'cats'; $('#explorer').css('display','flex'); App.loadCats(); },
    
    back: () => { 
        if(App.view === 'items') App.loadCats(); 
        else $('#explorer').hide(); 
    },

    loadCats: () => {
        App.view = 'cats';
        const act = App.type==='live'?'get_live_categories':(App.type==='movie'?'get_vod_categories':'get_series_categories');
        $('#render').html('<div class="spinner"></div>');
        
        $.getJSON(`api.php?u=${creds.u}&p=${creds.p}&dns=${encodeURIComponent(creds.dns)}&action=${act}`, (data) => {
            let h = ''; 
            data.forEach(c => { 
                const isAdult = /adulto|xxx|hot|sex|porn|hentai/i.test(c.category_name);
                h += `<div onclick="App.handleCatClick('${c.category_id}','${c.category_name}', ${isAdult})" class="cat-item">
                        <span>${c.category_name}</span>
                        <i class="${isAdult ? 'fas fa-lock' : 'fas fa-chevron-right'}"></i>
                      </div>`; 
            });
            $('#render').css('grid-template-columns','1fr').html(h);
        });
    },

    handleCatClick: (id, name, isLocked) => {
        if(isLocked) {
            parentalTarget = { id, name };
            $('#parental-modal').css('display','flex');
            $('#parental-input').val('').focus();
        } else {
            App.loadItems(id, name);
        }
    },

    unlock: () => {
        const val = $('#parental-input').val();
        if(val === "<?= $parental_pass ?>") {
            $('#parental-modal').hide();
            App.loadItems(parentalTarget.id, parentalTarget.name);
        } else {
            alert("Senha incorreta!");
        }
    },

    loadItems: (cid, name) => {
        App.view = 'items';
        $('#exp-title').text(name);
        $('#render').html('<div class="spinner"></div>');
        const act = App.type==='live'?'get_live_streams':(App.type==='movie'?'get_vod_streams':'get_series');
        
        $.getJSON(`api.php?u=${creds.u}&p=${creds.p}&dns=${encodeURIComponent(creds.dns)}&action=${act}&cat=${cid}`, (data) => {
            App.currentData = data;
            App.renderGrid(data);
        });
    },

    renderGrid: (data) => {
        let h = '';
        data.forEach(i => {
            const id = i.stream_id || i.series_id || i.vod_id;
            const img = i.stream_icon || i.cover || 'https://placehold.co/200x300/111/fff?text=VU';
            const prog = JSON.parse(localStorage.getItem('prog_'+id) || '{"p":0}');
            
            h += `<div class="i-card" onclick="App.showDetails('${id}')">
                    <img src="${img}">
                    ${prog.p > 0 ? `<div style="position:absolute; bottom:25px; left:0; width:100%; height:3px; background:#333;"><div style="width:${prog.p}%; height:100%; background:var(--primary);"></div></div>` : ''}
                    <p>${i.name}</p>
                  </div>`;
        });
        $('#render').css('grid-template-columns','repeat(3, 1fr)').html(h);
    },

    showDetails: (id) => {
        $('#details-view').css('display','flex');
        $('#details-content').html('<div class="spinner"></div>');

        if(App.type === 'live') {
            const live = App.currentData.find(x => x.stream_id == id);
            App.drawUI(live, id);
            return;
        }

        const act = App.type === 'serie' ? 'get_series_info' : 'get_vod_info';
        const param = App.type === 'serie' ? 'series_id' : 'vod_id';
        
        $.getJSON(`api.php?u=${creds.u}&p=${creds.p}&dns=${encodeURIComponent(creds.dns)}&action=${act}&${param}=${id}`, (data) => {
            App.drawUI(data, id);
        });
    },

    drawUI: (data, id) => {
        const info = data.info || data.movie_data || data || {};
        const title = info.name || "Sem título";
        const plot = info.plot || "Sinopse não disponível.";
        const img = info.cover || info.movie_image || info.stream_icon;

        let h = `<div style="text-align:center;">
                    <img src="${img}" style="width:140px; border-radius:15px; border:2px solid var(--primary); margin-bottom:20px;">
                    <h2 style="margin-bottom:15px; color:var(--primary);">${title}</h2>
                    <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:12px; font-size:13px; text-align:justify; margin-bottom:25px; line-height:1.5; color:#ccc;">${plot}</div>`;

        if(App.type === 'serie' && data.episodes) {
            App.tempEpisodes = data.episodes;
            h += `<div class="season-tabs">`;
            Object.keys(data.episodes).forEach((s, idx) => {
                h += `<div class="tab ${idx===0?'active':''}" onclick="App.changeSeason(this, '${s}')">Temporada ${s}</div>`;
            });
            h += `</div><div id="ep-list"></div>`;
            $('#details-content').html(h);
            App.changeSeason(null, Object.keys(data.episodes)[0]);
        } else {
            h += `<button onclick="App.play('${id}')" style="width:100%; padding:18px; background:var(--primary); border:none; border-radius:12px; color:white; font-weight:bold; font-size:16px;">ASSISTIR AGORA</button></div>`;
            $('#details-content').html(h);
        }
    },

    changeSeason: (btn, s) => {
        if(btn) { $('.tab').removeClass('active'); $(btn).addClass('active'); }
        let h = '';
        App.tempEpisodes[s].forEach(ep => {
            h += `<div class="cat-item" onclick="App.play('${ep.id}')">
                    <span><b>E${ep.episode}</b> - ${ep.title}</span>
                    <i class="fas fa-play-circle" style="font-size:20px;"></i>
                  </div>`;
        });
        $('#ep-list').html(h);
    },

    play: (id) => {
        const url = `modal-player.php?u=${creds.u}&p=${creds.p}&dns=${encodeURIComponent(creds.dns)}&id=${id}&type=${App.type}`;
        $('#player-frame').attr('src', url);
        $('#player-container').fadeIn();
    },

    closePlayer: () => { $('#player-frame').attr('src', ''); $('#player-container').fadeOut(); },
    
    openSettings: () => { $('#settings-view').css('display', 'flex'); },

    saveSettings: () => {
        const pass = $('#new-parental-pass').val();
        if(pass.length !== 4) return alert("A senha deve ter 4 dígitos.");
        $.post('update_settings.php', { senha: pass }, (res) => {
            alert("Senha atualizada! O app será reiniciado.");
            location.reload();
        });
    },

    filter: (q) => {
        const f = App.currentData.filter(i => i.name.toLowerCase().includes(q.toLowerCase()));
        App.renderGrid(f);
    }
};
</script>
</body>
</html>
