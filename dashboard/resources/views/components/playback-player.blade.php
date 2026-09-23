@props(['videoUrl', 'tracksUrl', 'events' => [], 'poster' => null, 'videoAsset' => null])

<div id="pbWrap" style="position:relative;width:100%;background:#000;">
    <video id="pbVideo" controls preload="metadata" playsinline poster="{{ $poster ?? '' }}" style="width:100%;display:block;max-height:70vh;object-fit:contain;">
        <source src="{{ $videoUrl }}" type="video/mp4">
    </video>
    <canvas id="pbCanvas" style="position:absolute;inset:0;pointer-events:none;width:100%;height:100%;"></canvas>

    <div style="font-size:12px;padding:8px 12px;background:rgba(15,23,42,0.9);border-top:1px solid rgba(255,255,255,0.08);display:flex;flex-wrap:wrap;gap:12px;align-items:center;color:#e2e8f0;">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;"><input type="checkbox" id="pbPeople" checked class="form-check-input" style="margin-top:0;"> People</label>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;"><input type="checkbox" id="pbPhones" checked class="form-check-input" style="margin-top:0;"> Phones</label>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;"><input type="checkbox" id="pbIds" checked class="form-check-input" style="margin-top:0;"> Track IDs</label>
        <div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-left:auto;">Confidence <input type="range" id="pbConf" min="0" max="1" step="0.05" value="0.5" style="width:120px;vertical-align:middle;"> <span id="pbConfVal" style="min-width:30px;display:inline-block;">0.50</span></div>
        <span id="pbCount" style="font-weight:600;font-size:13px;">Detections: 0</span>
    </div>

    <div id="pbMarkers" style="position:relative;height:20px;background:#0f172a;border-bottom:1px solid rgba(255,255,255,0.05);margin-top:-1px;overflow:hidden;">
        @foreach($events as $ev)
        @php $pct = ($videoAsset && $videoAsset->duration && $videoAsset->duration > 0) ? (($ev->started_at_seconds ?? 0) / $videoAsset->duration * 100) : 0; @endphp
        <div title="{{ $ev->event_type }} at {{ number_format($ev->started_at_seconds ?? 0,1) }}s" onclick="pbSeek({{ ($ev->started_at_seconds ?? 0) }})" style="position:absolute;left:{{ $pct }}%;width:6px;height:100%;background:{{ str_contains($ev->event_type,'Mobile Phone') ? '#dc2626' : '#f59e0b' }};cursor:pointer;border-radius:2px;transform:translateX(-50%);" aria-label="Event marker"></div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function(){
    const video = document.getElementById('pbVideo');
    const canvas = document.getElementById('pbCanvas');
    const ctx = canvas.getContext('2d');
    const wrap = document.getElementById('pbWrap');
    let tracksData = null;
    let frames = null;
    let dpr = window.devicePixelRatio || 1;

    function resizeCanvas(){
        const vw = video.videoWidth || 1920, vh = video.videoHeight || 1080;
        const cw = video.clientWidth || wrap.clientWidth || 960;
        const ch = video.clientHeight || wrap.clientHeight || 540;
        const scale = Math.min(cw / vw, ch / vh);
        const dispW = vw * scale, dispH = vh * scale;
        const offX = Math.max(0, (cw - dispW) / 2);
        const offY = Math.max(0, (ch - dispH) / 2);
        canvas.width = Math.round(cw * dpr);
        canvas.height = Math.round(ch * dpr);
        canvas.style.width = cw + 'px';
        canvas.style.height = ch + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { vw, vh, cw, ch, scale, offX, offY, dispW, dispH };
    }

    const pbPeople = document.getElementById('pbPeople');
    const pbPhones = document.getElementById('pbPhones');
    const pbIds = document.getElementById('pbIds');
    const pbConf = document.getElementById('pbConf');
    const pbConfVal = document.getElementById('pbConfVal');
    const pbCount = document.getElementById('pbCount');

    pbConf.addEventListener('input', () => { pbConfVal.textContent = parseFloat(pbConf.value).toFixed(2); });

    function fetchTracks(){
        fetch('{{ $tracksUrl }}').then(r => r.ok ? r.json() : Promise.reject('no tracks')).then(data => {
            tracksData = data;
            frames = tracksData && tracksData.frames ? tracksData.frames.sort((a,b) => a.t - b.t) : null;
        }).catch(() => {
            tracksData = null; frames = null;
            document.getElementById('pbCount').textContent = 'Detections: 0';
        });
    }

    function binarySearchFrames(t){
        if (!frames || frames.length === 0) return null;
        let lo = 0, hi = frames.length - 1;
        while (lo < hi) {
            const mid = Math.floor((lo + hi) / 2);
            if (frames[mid].t < t) lo = mid + 1; else hi = mid;
        }
        return frames[lo];
    }

    function drawOverlay(){
        if (!tracksData || !frames) return;
        const info = resizeCanvas();
        const current = video.currentTime || 0;
        const frameData = binarySearchFrames(current);
        if (!frameData || !frameData.boxes) { ctx.clearRect(0, 0, info.cw, info.ch); return; }
        ctx.clearRect(0, 0, info.cw, info.ch);
        const threshold = parseFloat(pbConf.value);
        const boxes = frameData.boxes.filter(b => b.conf >= threshold);
        const showPeople = pbPeople.checked;
        const showPhones = pbPhones.checked;
        const showIds = pbIds.checked;
        let count = 0;
        for (const b of boxes) {
            const cls = (b.cls || '').toString();
            if (cls.indexOf('person') !== -1 || cls.indexOf('Person') !== -1) { if (!showPeople) continue; }
            if (cls.indexOf('cell phone') !== -1 || cls.indexOf('phone') !== -1) { if (!showPhones) continue; }
            count++;
            const s = info.scale;
            const offX = info.offX, offY = info.offY;
            const x = offX + b.xyxy[0] * s;
            const y = offY + b.xyxy[1] * s;
            const wBox = Math.max(1, (b.xyxy[2] - b.xyxy[0]) * s);
            const hBox = Math.max(1, (b.xyxy[3] - b.xyxy[1]) * s);
            const isPerson = (cls.indexOf('person') !== -1 || cls.indexOf('Person') !== -1);
            ctx.strokeStyle = isPerson ? '#2563EB' : '#DC2626';
            ctx.lineWidth = 2 * (dpr || 1) / s; // scale line relative to source resolution roughly
            ctx.strokeRect(x, y, wBox, hBox);
            if (showIds && b.id) {
                const label = '#' + b.id;
                const fontSize = Math.max(10, 12);
                ctx.font = (fontSize / dpr) + 'px sans-serif';
                const metrics = ctx.measureText(label);
                const labelX = x + 2;
                const labelY = Math.max(y - 4, metrics.actualBoundingBoxAscent + 4);
                const chipH = 16;
                ctx.fillStyle = isPerson ? 'rgba(37,99,235,0.85)' : 'rgba(220,38,38,0.85)';
                ctx.fillRect(labelX, labelY - chipH, metrics.width + 6, chipH);
                ctx.fillStyle = '#fff';
                ctx.fillText(label, labelX + 3, labelY - 2);
            }
        }
        pbCount.textContent = 'Detections: ' + count;
    }

    function loop(){
        if (video.readyState >= 2) drawOverlay();
        requestAnimationFrame(loop);
    }

    function pbSeek(sec){ video.currentTime = sec; }
    window.pbSeek = pbSeek;

    video.addEventListener('loadedmetadata', () => {
        resizeCanvas();
        fetchTracks();
        loop();
    });

    window.addEventListener('resize', () => { resizeCanvas(); });
    const ro = new ResizeObserver(() => resizeCanvas());
    ro.observe(wrap);
})();
</script>
@endpush
