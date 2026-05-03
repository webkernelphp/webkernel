<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECURE ACCESS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;500;600;700&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cyan:    #00d4ff;
            --cyan-dim: #0099bb;
            --red:     #ff3b3b;
            --bg:      #020c14;
            --surface: #040f1a;
            --border:  #0a2a3a;
            --text:    #a0c8d8;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Share Tech Mono', monospace;
            overflow: hidden;
        }

        /* Animated grid background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,212,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,212,255,.03) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridPulse 8s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: fixed; inset: 0;
            background: radial-gradient(ellipse at center, rgba(0,40,60,.6) 0%, var(--bg) 70%);
            pointer-events: none;
        }
        @keyframes gridPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        .hud {
            position: relative; z-index: 1;
            width: 340px;
        }

        /* Corner brackets */
        .bracket {
            position: absolute;
            width: 20px; height: 20px;
            border-color: var(--cyan);
            border-style: solid;
            opacity: .7;
        }
        .bracket-tl { top: -8px; left: -8px; border-width: 2px 0 0 2px; }
        .bracket-tr { top: -8px; right: -8px; border-width: 2px 2px 0 0; }
        .bracket-bl { bottom: -8px; left: -8px; border-width: 0 0 2px 2px; }
        .bracket-br { bottom: -8px; right: -8px; border-width: 0 2px 2px 0; }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 2rem 1.75rem 1.75rem;
            position: relative;
        }

        .panel-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: .75rem;
            margin-bottom: 1.5rem;
        }
        .panel-header .label {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            letter-spacing: .2em;
            font-size: .7rem;
            color: var(--cyan-dim);
            display: block;
            margin-bottom: .2rem;
        }
        .panel-header .title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--cyan);
            letter-spacing: .12em;
        }
        .status-dot {
            display: inline-block;
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--cyan);
            margin-right: .4rem;
            animation: blink 1.4s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }

        /* Dots */
        .pin-dots {
            display: flex; justify-content: center; gap: .7rem;
            margin-bottom: 1.5rem;
        }
        .pin-dots span {
            width: 12px; height: 12px;
            border: 1px solid var(--cyan-dim);
            background: transparent;
            transform: rotate(45deg);
            transition: background .1s, border-color .1s;
        }
        .pin-dots span.filled {
            background: var(--cyan);
            border-color: var(--cyan);
            box-shadow: 0 0 8px var(--cyan);
        }
        .pin-dots span.error {
            background: var(--red);
            border-color: var(--red);
            box-shadow: 0 0 8px var(--red);
        }

        /* Grid */
        .pin-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .4rem;
            margin-bottom: 1.25rem;
        }
        .pin-grid button {
            padding: .7rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: .05em;
            cursor: pointer;
            transition: all .12s;
            position: relative;
            overflow: hidden;
        }
        .pin-grid button::before {
            content: '';
            position: absolute; inset: 0;
            background: var(--cyan);
            opacity: 0;
            transition: opacity .12s;
        }
        .pin-grid button:hover { border-color: var(--cyan-dim); color: var(--cyan); }
        .pin-grid button:hover::before { opacity: .06; }
        .pin-grid button:active::before { opacity: .15; }
        .pin-grid button.action { font-size: .75rem; color: #4a7a8a; letter-spacing: .08em; }
        .pin-grid button.action:hover { color: var(--cyan); }

        /* Duration */
        .duration-row {
            margin-bottom: 1.25rem;
        }
        .duration-row label {
            display: block;
            font-size: .65rem;
            letter-spacing: .15em;
            color: #4a7a8a;
            margin-bottom: .4rem;
        }
        .duration-row select {
            width: 100%;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--cyan);
            font-family: 'Share Tech Mono', monospace;
            font-size: .8rem;
            padding: .45rem .6rem;
            appearance: none;
            cursor: pointer;
            outline: none;
        }
        .duration-row select:focus { border-color: var(--cyan-dim); }
        .duration-row select option { background: #040f1a; color: var(--cyan); }

        /* Error */
        .pin-error {
            font-size: .7rem;
            letter-spacing: .1em;
            color: var(--red);
            min-height: 1rem;
            text-align: center;
        }

        /* Scan line effect */
        @keyframes scan {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }
        .scanline {
            position: fixed; left: 0; right: 0; height: 2px;
            background: linear-gradient(transparent, rgba(0,212,255,.06), transparent);
            animation: scan 6s linear infinite;
            pointer-events: none; z-index: 0;
        }

        input[name="pin"] { display: none; }
    </style>
</head>
<body>
    <div class="scanline"></div>

    <div class="hud">
        <div class="bracket bracket-tl"></div>
        <div class="bracket bracket-tr"></div>
        <div class="bracket bracket-bl"></div>
        <div class="bracket bracket-br"></div>

        <div class="panel">
            <div class="panel-header">
                <span class="label"><span class="status-dot"></span>RESTRICTED ACCESS</span>
                <div class="title">AUTHENTICATION REQUIRED</div>
            </div>

            <div class="pin-dots" id="pin-dots">
                @for ($i = 1; $i <= $pinLength; $i++)
                    <span id="d{{ $i }}"></span>
                @endfor
            </div>

            <form method="POST" action="{{ $formAction }}" id="pin-form">
                @csrf
                <input type="hidden" name="pin" id="pin-value">

                <div class="pin-grid">
                    <button type="button" onclick="pinKey('1')">1</button>
                    <button type="button" onclick="pinKey('2')">2</button>
                    <button type="button" onclick="pinKey('3')">3</button>
                    <button type="button" onclick="pinKey('4')">4</button>
                    <button type="button" onclick="pinKey('5')">5</button>
                    <button type="button" onclick="pinKey('6')">6</button>
                    <button type="button" onclick="pinKey('7')">7</button>
                    <button type="button" onclick="pinKey('8')">8</button>
                    <button type="button" onclick="pinKey('9')">9</button>
                    <button type="button" class="action" onclick="pinClear()">CLR</button>
                    <button type="button" onclick="pinKey('0')">0</button>
                    <button type="button" class="action" onclick="pinBack()">&#9003;</button>
                </div>

                <div class="duration-row">
                    <label>ACCESS DURATION</label>
                    <select name="duration">
                        <option value="15">15 MIN</option>
                        <option value="30" selected>30 MIN</option>
                        <option value="60">1 HOUR</option>
                        <option value="120">2 HOURS</option>
                        <option value="240">4 HOURS</option>
                        <option value="480">8 HOURS</option>
                    </select>
                </div>
            </form>

            @error('pin')
                <div class="pin-error">// {{ strtoupper($message) }}</div>
            @else
                <div class="pin-error"></div>
            @enderror
        </div>
    </div>

    <script>
        const PIN_LENGTH = {{ $pinLength }};
        let entered = '';

        function updateDots(state) {
            for (let i = 1; i <= PIN_LENGTH; i++) {
                const d = document.getElementById('d' + i);
                d.className = '';
                if (state === 'error') { d.classList.add('error'); }
                else if (i <= entered.length) { d.classList.add('filled'); }
            }
        }

        function submit() {
            document.getElementById('pin-value').value = entered;
            document.getElementById('pin-form').submit();
        }

        function pinKey(k) {
            if (entered.length >= PIN_LENGTH) return;
            entered += k;
            updateDots();
            if (entered.length === PIN_LENGTH) submit();
        }

        function pinBack() { entered = entered.slice(0, -1); updateDots(); }
        function pinClear() { entered = ''; updateDots(); }

        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key >= '0' && e.key <= '9') { pinKey(e.key); }
            else if (e.key === 'Backspace') { pinBack(); }
            else if (e.key === 'Escape') { pinClear(); }
            else if (e.key === 'Enter' && entered.length === PIN_LENGTH) { submit(); }
        });

        // Paste support
        document.addEventListener('paste', (e) => {
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/\D/g, '').slice(0, PIN_LENGTH);
            entered = digits;
            updateDots();
            if (entered.length === PIN_LENGTH) submit();
        });

        @error('pin')
            updateDots('error');
            setTimeout(() => { entered = ''; updateDots(); }, 800);
        @enderror
    </script>
</body>
</html>
