class VeloxEngine {
    constructor(u, p, api) {
        this.u = u; this.p = p; this.api = api;
        this.player = new Plyr('#main-video', { 
            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen', 'settings'],
            settings: ['quality', 'speed']
        });
        this.hls = new Hls();
        this.currentMode = '';
    }

    loadCats(mode) {
        this.currentMode = mode;
        $('#explorer').fadeIn().css('display','flex');
        const action = mode === 'live' ? 'get_live_categories' : (mode === 'movie' ? 'get_vod_categories' : 'get_series_categories');
        
        $.getJSON(`${this.api}?u=${this.u}&p=${this.p}&action=${action}`, (data) => {
            let html = '';
            data.forEach(c => {
                html += `<div class="glass" style="padding:18px; margin-bottom:12px; display:flex; justify-content:space-between;" 
                         onclick="Engine.loadItems('${c.category_id}')">
                         ${c.category_name} <i class="fas fa-chevron-right" style="color:var(--main)"></i></div>`;
            });
            $('#content-render').html(html);
        });
    }

    loadItems(catId) {
        const action = this.currentMode === 'live' ? 'get_live_streams' : (this.currentMode === 'movie' ? 'get_vod_streams' : 'get_series');
        $.getJSON(`${this.api}?u=${this.u}&p=${this.p}&action=${action}&cat=${catId}`, (data) => {
            let html = '<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">';
            data.forEach(item => {
                const id = item.stream_id || item.series_id;
                const img = item.stream_icon || 'https://via.placeholder.com/200x300?text=Sem+Capa';
                html += `<div onclick="Engine.boot('${id}', '${item.container_extension || 'ts'}')" style="text-align:center;">
                            <img src="${img}" style="width:100%; border-radius:15px; aspect-ratio:2/3; object-fit:cover;">
                            <p style="font-size:9px; margin-top:5px; opacity:0.7;">${item.name}</p>
                         </div>`;
            });
            $('#content-render').html(html + '</div>');
        });
    }

    boot(id, ext) {
        const type = this.currentMode === 'live' ? 'live' : 'movie';
        const url = `http://dom-entretenimento.shop/${type}/${this.u}/${this.p}/${id}.${ext}`;
        
        $('#player-screen').fadeIn();
        const video = document.getElementById('main-video');

        if (Hls.isSupported()) {
            this.hls.destroy();
            this.hls = new Hls({ maxBufferLength: 30, maxMaxBufferLength: 60 });
            this.hls.loadSource(url);
            this.hls.attachMedia(video);
            this.hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
        } else {
            video.src = url;
            video.play();
        }
    }

    stop() {
        const video = document.getElementById('main-video');
        video.pause();
        this.hls.destroy();
        $('#player-screen').fadeOut();
    }
}
