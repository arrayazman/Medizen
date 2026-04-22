<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Antrean Sampling - RIS</title>

    <link href="{{ asset('css/fonts-inter.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/feather.min.js') }}"></script>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

    <style>
        :root {
            --bg: #f5f8fa;
            --surface: #ffffff;
            --surface-2: #fcfdfe;
            --accent: #059669;
            --accent-dim: rgba(5, 150, 105, 0.04);
            --accent-border: rgba(5, 150, 105, 0.15);
            --text: #020617;
            /* High contrast black-slate */
            --text-2: #475569;
            /* Slate 600 */
            --text-3: #94a3b8;
            --border: #e2e8f0;
            --ornament-op: 0.05;
            --slide-op: 0.2;
            --info-bg: #f1f5f9;
            --info-text: #1e293b;
            --info-icon-bg: #cbd5e1;
            --info-icon-color: #475569;
        }

        body.dark-mode {
            --bg: #020617;
            --surface: #0f172a;
            --surface-2: #1e293b;
            --accent: #10b981;
            --accent-dim: rgba(16, 185, 129, 0.08);
            --accent-border: rgba(16, 185, 129, 0.2);
            --text: #ffffff;
            /* pure white for dark mode */
            --text-2: #cbd5e1;
            /* Slate 300 */
            --text-3: #64748b;
            --border: #1e293b;
            --ornament-op: 0.08;
            --slide-op: 0.15;
            --info-bg: #0f172a;
            --info-text: #f8fafc;
            --info-icon-bg: #1e293b;
            --info-icon-color: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            border-radius: 0 !important;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.4s ease, color 0.4s ease;
        }

        /* Ambient Ornaments */
        .ambient-svg {
            position: absolute;
            inset: 0;
            z-index: 0;
            opacity: var(--ornament-op);
            pointer-events: none;
        }

        /* ── HEADER ── */
        .hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            height: 56px;
            background: var(--surface);
            border-bottom: 2px solid var(--accent);
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .brand-name {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: -0.4px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 0.65rem;
            color: var(--text-2);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hdr-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hdr-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg);
            padding: 4px;
            border: 1px solid var(--border);
        }

        .hdr-btn {
            width: 32px;
            height: 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .hdr-btn:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .clock {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -1px;
            font-variant-numeric: tabular-nums;
            color: var(--text);
            margin-left: 10px;
        }

        /* ── MAIN ── */
        .main {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
            z-index: 5;
        }

        /* ── LEFT: CALLING ── */
        .calling-panel {
            flex: 1;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
            transition: background 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .calling-active {
            animation: panelPulse 3s infinite ease-in-out;
        }

        @keyframes panelPulse {

            0%,
            100% {
                background: var(--surface);
            }

            50% {
                background: var(--accent-dim);
            }
        }

        .tag-calling {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.85;
            z-index: 2;
        }

        .tag-calling::before,
        .tag-calling::after {
            content: '';
            width: 30px;
            height: 2px;
            background: var(--accent-border);
        }

        .calling-name {
            font-size: clamp(2.8rem, 6.2vw, 5.5rem);
            font-weight: 950;
            text-align: center;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: -2px;
            color: var(--text);
            margin-bottom: 24px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            z-index: 2;
        }

        .calling-room {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--accent);
            background: var(--accent-dim);
            padding: 8px 30px;
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            transition: all 0.3s;
            z-index: 2;
        }

        /* ── SLIDE IMAGES ── */
        .slide-container {
            position: absolute;
            inset: 0;
            z-index: 1;
            opacity: var(--slide-op);
            overflow: hidden;
        }

        .slide-item {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 2s ease-in-out;
        }

        .slide-item.active,
        video.media-item.active {
            opacity: 1;
        }

        /* ── RIGHT ── */
        .right-panel {
            flex: 0 0 500px;
            /* Slimmed down from 360px */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--bg);
            border-left: 1px solid var(--border);
        }

        .queue-section {
            flex: 1;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .queue-section+.queue-section {
            border-top: 1px solid var(--border);
        }

        .sec-hdr {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
            z-index: 2;
        }

        .sec-label {
            font-size: 0.6rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-2);
        }

        .sec-badge {
            margin-left: auto;
            font-size: 0.6rem;
            font-weight: 900;
            color: var(--accent);
            background: var(--accent-dim);
            border: 1px solid var(--accent-border);
            padding: 1px 6px;
            min-width: 22px;
            text-align: center;
        }

        .queue-list {
            flex: 1;
            overflow: hidden;
            padding: 6px 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .q-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            background: var(--surface-2);
            border: 1px solid var(--border);
        }

        .q-num {
            font-size: 0.7rem;
            font-weight: 900;
            color: var(--accent);
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .q-name {
            flex: 1;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
        }

        .q-mod {
            font-size: 0.55rem;
            font-weight: 800;
            color: var(--text-3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            background: var(--bg);
            padding: 1px 4px;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            opacity: 0.2;
            padding: 15px;
        }

        .empty-label {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        /* REFINED COMPACT INFO PANEL */
        .info-panel {
            background: linear-gradient(135deg, var(--info-bg), var(--bg));
            padding: 12px 18px;
            flex-shrink: 0;
            position: relative;
            z-index: 5;
            border-top: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            box-shadow: inset 0 10px 20px -10px rgba(0, 0, 0, 0.03);
        }

        .info-title {
            font-size: 0.58rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--accent);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--accent-border), transparent);
        }

        .info-item {
            display: flex;
            gap: 10px;
            margin-bottom: 6px;
            padding: 6px 10px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01);
        }

        .info-icon {
            width: 18px;
            height: 18px;
            background: var(--accent-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            flex-shrink: 0;
            border: 1px solid var(--accent-border);
        }

        .info-text {
            font-size: 0.65rem;
            font-weight: 700;
            line-height: 1.4;
            color: var(--info-text);
        }

        /* ── FOOTER ── */
        .footer {
            height: 0px;
            background: var(--surface);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 15px;
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }

        .footer marquee {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-2);
        }
    </style>
</head>

<body>
    <!-- Ambient Pattern -->
    <svg class="ambient-svg" width="100%" height="100%">
        <defs>
            <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.5" fill="currentColor" />
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#dots)" />
    </svg>

    <header class="hdr">
        <div class="brand">
            <div class="brand-icon">
                <i data-feather="activity" style="width:16px;height:16px"></i>
            </div>
            <div>
                <div class="brand-name">{{ config('app.name', 'MEDIZEN RIS') }}</div>
                <div class="brand-sub">Antrean Unit Pelayanan Radiologi</div>
            </div>
        </div>
        <div class="hdr-right">
            <div class="hdr-controls">
                <button class="hdr-btn" id="modeToggle" title="Toggle Tema">
                    <i data-feather="moon" style="width:14px;height:14px"></i>
                </button>
                <button class="hdr-btn" id="fsToggle" title="Layar Penuh">
                    <i data-feather="maximize" style="width:14px;height:14px"></i>
                </button>
            </div>
            <div class="clock" id="clock">00:00:00</div>
        </div>
    </header>

    <div class="main">
        <!-- LEFT: CALLING -->
        <div class="calling-panel" id="callingContainer">
            <!-- Dynamic Gallery / Slide Container -->
            <div class="slide-container">
                @if($activeGallery && $activeGallery->items->count() > 0)
                    @foreach($activeGallery->items as $index => $item)
                        @if($activeGallery->type === 'photo')
                            <div class="slide-item media-item {{ $index === 0 ? 'active' : '' }}"
                                style="background-image: url('{{ asset('storage/' . $item->file_path) }}')"></div>
                        @else
                            <video class="media-item {{ $index === 0 ? 'active' : '' }}"
                                style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 2s;"
                                muted playsinline>
                                <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                            </video>
                        @endif
                    @endforeach
                @else
                    <!-- Fallback to default hero images -->
                    <div class="slide-item media-item active" style="background-image: url('{{ asset('img/hero1.png') }}')">
                    </div>
                    <div class="slide-item media-item" style="background-image: url('{{ asset('img/hero2.png') }}')"></div>
                    <div class="slide-item media-item" style="background-image: url('{{ asset('img/hero3.png') }}')"></div>
                @endif
            </div>

            <div class="tag-calling">Sedang Dipanggil</div>
            <div class="calling-name" id="calledName">ANTREAN KOSONG</div>
            <div class="calling-room" id="calledRoom">
                <i data-feather="map-pin" style="width:14px;height:14px"></i>
                <span>-</span>
            </div>
        </div>

        <!-- RIGHT: LISTS -->
        <div class="right-panel">
            <div class="queue-section">
                <div class="sec-hdr">
                    <i data-feather="layers" style="width:12px;height:12px;color:var(--accent)"></i>
                    <span class="sec-label">Prioritas Berikutnya</span>
                    <span class="sec-badge" id="nextCount">0</span>
                </div>
                <div class="queue-list" id="nextList">
                    <!-- Dynamic Rows -->
                </div>
            </div>

            <div class="queue-section">
                <div class="sec-hdr">
                    <i data-feather="check-circle" style="width:12px;height:12px;color:var(--text-3)"></i>
                    <span class="sec-label">Antrean Selesai (Completed)</span>
                    <span class="sec-badge" id="doneCount">0</span>
                </div>
                <div class="queue-list" id="completedList">
                    <!-- Dynamic Rows -->
                </div>
            </div>

            <!-- REFINED INFO PANEL -->
            <div class="info-panel">
                <div class="info-title">Instruksi Pelayanan</div>
                @php 
                                        $instructions = $setting->display_instructions ?? [
                        'Siapkan Nomor Pendaftaran & Identitas diri (KTP/SIM) Anda.',
                        'Masuk ke ruang sampling SETELAH nama Anda muncul di monitor utama.'
                    ]; 
                @endphp
                @foreach($instructions as $inst)
                    <div class="info-item">
                        <div class="info-icon"><i data-feather="file-text" style="width:12px"></i></div>
                        <div class="info-text">{{ $inst }}</div>
                    </div>
                @endforeach
        </div>
    </div>
</div>
@if($setting && $setting->display_marquee_text)
    <div class="marquee-footer"
        style="position: fixed; bottom: 0; left: 0; width: 100%; height: 40px; background: var(--slate-900); color: white; display: flex; align-items: center; overflow: hidden; z-index: 9999; border-top: 2px solid var(--accent);">
        <div class="marquee-content"
            style="white-space: nowrap; animation: marquee 30s linear infinite; padding-left: 100%; font-weight: 600; font-size: 0.9rem;">
            {{ $setting->display_marquee_text }}
        </div>
    </div>
        <style>
        @keyframes marquee {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(-100%, 0);
            }
            }
        .footer {
                margin-bottom: 40px;
            }

            /* Space for marquee */
        </style>
@endif

    <div class="footer">
       
    </div>

    <audio id="bellSound"
        src="https://assets.mixkit.co/sfx/preview/mixkit-modern-classic-door-bell-sound-113.mp3"></audio>

    <script>
        feather.replace();

        // 1. Clock
        function updateTime() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${h}:${m}:${s}`;
        }
        setInterval(updateTime, 1000);
        updateTime();

        // 2. Theme
        function setTheme(isDark) {
            document.body.classList.toggle('dark-mode', isDark);
            const icon = isDark ? 'sun' : 'moon';
            document.getElementById('modeToggle').innerHTML = `<i data-feather="${icon}" style="width:14px;height:14px"></i>`;
            feather.replace();
            localStorage.setItem('theme-mode-q-v4', isDark ? 'dark' : 'light');
        }
        document.getElementById('modeToggle').onclick = (e) => {
            e.stopPropagation();
            setTheme(!document.body.classList.contains('dark-mode'));
        };
        // Default ke dark mode, kecuali user sudah pilih light secara eksplisit
        const savedTheme = localStorage.getItem('theme-mode-q-v4');
        setTheme(savedTheme !== 'light');

        // 3. Fullscreen
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                document.getElementById('fsToggle').innerHTML = `<i data-feather="minimize" style="width:14px;height:14px"></i>`;
            } else {
                document.exitFullscreen();
                document.getElementById('fsToggle').innerHTML = `<i data-feather="maximize" style="width:14px;height:14px"></i>`;
            }
            feather.replace();
        }
        document.getElementById('fsToggle').onclick = (e) => {
            e.stopPropagation();
            toggleFullscreen();
        };

        // 4. Media Management (Slideshow / Video Playlist)
        let currentMediaIndex = 0;
        const galleryType = "{{ $activeGallery->type ?? 'photo' }}";
        const mediaItems = document.querySelectorAll('.media-item');

        function rotateMedia() {
            if (mediaItems.length <= 1) return;

            mediaItems[currentMediaIndex].classList.remove('active');
            currentMediaIndex = (currentMediaIndex + 1) % mediaItems.length;
            const nextMedia = mediaItems[currentMediaIndex];
            nextMedia.classList.add('active');

            // If it's a video, play it
            if (galleryType === 'video' && nextMedia.tagName === 'VIDEO') {
                nextMedia.play();
            }
        }

        // Initialize media
        if (galleryType === 'video') {
            // Setup video event listeners
            mediaItems.forEach((vid, index) => {
                if (vid.tagName === 'VIDEO') {
                    vid.onended = function () {
                        rotateMedia();
                    };
                }
            });
            // Play first video if active
            if (mediaItems[0] && mediaItems[0].tagName === 'VIDEO') {
                mediaItems[0].play();
            }
        } else {
            // Standard photo rotation
            if (mediaItems.length > 1) {
                setInterval(rotateMedia, 8000);
            }
        }

        // 5. Data Logic
        let currentCallingId = null;
        let lastGalleryId = "{{ $activeGallery->id ?? 0 }}";

        function refreshData() {
            $.getJSON("{{ route('queue.api.sampling') }}", function (data) {
                updateCalling(data.calling);
                updateLists(data.waiting, data.completed);

                // Check if gallery setting changed (requires full reload if changed significantly)
                if (data.gallery && data.gallery.id != lastGalleryId) {
                    location.reload();
                }
            });
        }

        function speakPatient(patient) {
            if (!('speechSynthesis' in window)) return;
            
            // Cancel current speech if any
            window.speechSynthesis.cancel();

            const name = patient.patient.nama;
            const room = patient.room ? patient.room.name : (patient.examination_type ? patient.examination_type.name : 'pemeriksaan');
            
            // Format: "Nomor antrean, [Nama], silakan menuju ke [Ruangan]"
            const text = `Panggilan untuk pasien, ${name}, silakan menuju ke ${room}`;
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            
            window.speechSynthesis.speak(utterance);
        }

        function updateCalling(patient) {
            const container = document.getElementById('callingContainer');
            const nameEl = document.getElementById('calledName');
            const roomSpan = document.getElementById('calledRoom').querySelector('span');

            if (!patient) {
                nameEl.textContent = "ANTREAN KOSONG";
                roomSpan.textContent = "-";
                container.classList.remove('calling-active');
                currentCallingId = null;
                return;
            }

            if (currentCallingId !== patient.id) {
                currentCallingId = patient.id;
                container.classList.add('calling-active');
                
                // Play Bell and Speak
                try { 
                    document.getElementById('bellSound').play();
                    // Delay speech a bit after bell
                    setTimeout(() => speakPatient(patient), 1500);
                } catch (e) { console.log('Audio error:', e); }
                
                nameEl.textContent = patient.patient.nama;
                roomSpan.textContent = patient.room ? patient.room.name : (patient.examination_type ? patient.examination_type.name : 'SILAKAN MASUK');
                setTimeout(() => container.classList.remove('calling-active'), 25000);
            }
        }

        function updateLists(waiting, completed) {
            // Show top 8 waiting
            const topWaiting = waiting.slice(0, 8);
            // Show top 8 completed
            const topDone = completed.slice(0, 7);

            document.getElementById('nextCount').textContent = waiting.length;
            document.getElementById('doneCount').textContent = completed.length;

            document.getElementById('nextList').innerHTML = renderWaiting(topWaiting);
            document.getElementById('completedList').innerHTML = renderDone(topDone);

            setTimeout(feather.replace, 0);
        }

        function renderWaiting(list) {
            if (!list.length) {
                return `
                    <div class="empty-state">
                        <i data-feather="slash" style="width:20px;height:20px"></i>
                        <div class="empty-label">Kosong</div>
                    </div>
                `;
            }
            return list.map(item => `
                <div class="q-row" style="border-left: 3px solid var(--accent);">
                    <span class="q-name" style="font-size:0.9rem; color: var(--accent);">${item.patient.nama}</span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="q-mod">${item.examination_type ? item.examination_type.name : '-'}</span>
                    </div>
                </div>
            `).join('');
        }

        function renderDone(list) {
            if (!list.length) {
                return `
                    <div class="empty-state">
                        <i data-feather="slash" style="width:20px;height:20px"></i>
                        <div class="empty-label">Belum ada pasien selesai</div>
                    </div>
                `;
            }
            return list.map(item => `
                <div class="q-row" style="opacity: 0.8; border-left: 3px solid var(--text-3);">
                    <span class="q-name" style="font-size:0.85rem; color: var(--text-2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.patient.nama}</span>
                    <span class="q-mod" style="background: var(--border); font-size: 0.7rem;">${item.examination_type ? item.examination_type.name : (item.room ? item.room.name : '-')}</span>
                </div>
            `).join('');
        }

        refreshData();
        setInterval(refreshData, 5000);

        // Body click for easy fullscreen
        document.body.onclick = function () {
            if (!document.fullscreenElement) toggleFullscreen();
        };
    </script>
</body>

</html>