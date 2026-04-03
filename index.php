<?php
session_start();
if (isset($_SESSION['intro_seen'])) {
    header("Location: login.php");
    exit();
}
$_SESSION['intro_seen'] = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✦ Dreamy Y2K Shop ✦</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #06060f;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Space Grotesk', sans-serif;
        }

        /* ── STAR PARTICLES ── */
        .stars-layer {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .star-particle {
            position: absolute;
            border-radius: 50%;
            background: white;
            animation: starTwinkle var(--dur) ease-in-out infinite var(--delay);
            opacity: 0;
        }
        @keyframes starTwinkle {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            50%       { opacity: var(--op); transform: scale(1); }
        }

        /* ── DRIFTING SPARKLE PARTICLES ── */
        .drift-particle {
            position: fixed;
            pointer-events: none;
            z-index: 1;
            opacity: 0;
            font-size: var(--sz);
            color: var(--col);
            animation: driftUp var(--dur) ease-in var(--delay) infinite;
        }
        @keyframes driftUp {
            0%   { opacity: 0;   transform: translateY(0)   rotate(0deg)   scale(0.6); }
            15%  { opacity: 0.9; }
            85%  { opacity: 0.5; }
            100% { opacity: 0;   transform: translateY(-120px) rotate(30deg) scale(1.1); }
        }

        /* ── AMBIENT GLOW ── */
        .bg-glow {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: radial-gradient(ellipse at 50% 50%, rgba(160,100,235,0) 0%, transparent 60%);
            animation: bgReveal 2.5s ease 1s forwards;
        }
        @keyframes bgReveal {
            to { background: radial-gradient(ellipse at 50% 50%, rgba(100,60,180,0.22) 0%, rgba(160,210,235,0.07) 45%, transparent 70%); }
        }

        /* ── RING GLOW BEHIND LOGO ── */
        .ring-glow {
            position: absolute;
            width: min(420px, 72vw);
            height: min(420px, 72vw);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(229,169,224,0.18) 0%, rgba(160,210,235,0.10) 50%, transparent 75%);
            opacity: 0;
            animation: ringAppear 1.8s ease 0.6s forwards;
            z-index: 0;
        }
        @keyframes ringAppear {
            to { opacity: 1; }
        }

        /* ── ROTATING RING ── */
        .ring-border {
            position: absolute;
            width: min(400px, 70vw);
            height: min(400px, 70vw);
            border-radius: 50%;
            border: 1px solid rgba(229,169,224,0.25);
            opacity: 0;
            animation: ringAppear 1.8s ease 1s forwards, ringRotate 18s linear 1s infinite;
            z-index: 1;
        }
        .ring-border::before {
            content: '';
            position: absolute;
            top: -3px; left: 50%;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #E5A9E0;
            box-shadow: 0 0 8px 3px rgba(229,169,224,0.7);
            transform: translateX(-50%);
        }
        @keyframes ringRotate {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .ring-border-2 {
            position: absolute;
            width: min(440px, 76vw);
            height: min(440px, 76vw);
            border-radius: 50%;
            border: 1px solid rgba(160,210,235,0.15);
            opacity: 0;
            animation: ringAppear 2s ease 1.2s forwards, ringRotateRev 24s linear 1.2s infinite;
            z-index: 1;
        }
        .ring-border-2::before {
            content: '';
            position: absolute;
            bottom: -3px; left: 50%;
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #A0D2EB;
            box-shadow: 0 0 8px 3px rgba(160,210,235,0.6);
            transform: translateX(-50%);
        }
        @keyframes ringRotateRev {
            from { transform: rotate(0deg); }
            to   { transform: rotate(-360deg); }
        }

        /* ── MAIN STAGE ── */
        .intro-stage {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── EMBLEM WRAPPER ── */
        .emblem-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(0.7);
            animation: emblemIn 1.4s cubic-bezier(0.16, 1, 0.3, 1) 0.3s forwards;
        }
        @keyframes emblemIn {
            0%   { opacity: 0; transform: scale(0.7) rotate(-8deg); }
            60%  { opacity: 1; transform: scale(1.06) rotate(1deg); }
            100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }

        .logo-img {
            width: min(280px, 52vw);
            height: auto;
            position: relative;
            z-index: 2;
            filter:
                drop-shadow(0 0 30px rgba(229,169,224,0.55))
                drop-shadow(0 0 70px rgba(160,210,235,0.25));
            animation: logoPulse 3.5s ease-in-out 1.8s infinite;
        }
        @keyframes logoPulse {
            0%, 100% { filter: drop-shadow(0 0 28px rgba(229,169,224,0.5))  drop-shadow(0 0 60px rgba(160,210,235,0.2)); }
            50%       { filter: drop-shadow(0 0 50px rgba(229,169,224,0.85)) drop-shadow(0 0 90px rgba(160,210,235,0.38)); }
        }

        /* ── LOADING DOTS ── */
        .loading-dots {
            margin-top: 36px;
            display: flex;
            gap: 10px;
            align-items: center;
            opacity: 0;
            animation: fadeUp 0.6s ease 2s forwards;
        }
        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255,209,220,0.4);
            animation: dotPulse 1.4s ease-in-out infinite;
        }
        .dot:nth-child(1) { animation-delay: 0s;    background: rgba(255,209,220,0.5); }
        .dot:nth-child(2) { animation-delay: 0.18s; background: rgba(229,169,224,0.5); }
        .dot:nth-child(3) { animation-delay: 0.36s; background: rgba(160,210,235,0.5); }
        .dot:nth-child(4) { animation-delay: 0.54s; background: rgba(229,169,224,0.5); }
        .dot:nth-child(5) { animation-delay: 0.72s; background: rgba(255,209,220,0.5); }
        @keyframes dotPulse {
            0%, 100% { transform: scale(1);   opacity: 0.35; }
            50%       { transform: scale(1.7); opacity: 1; }
        }

        /* ── TAGLINE ── */
        .tagline-wrap {
            margin-top: 22px;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.7s ease 2.5s forwards;
        }
        .tagline-text {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: clamp(0.85rem, 2.2vw, 1.15rem);
            background: linear-gradient(90deg, #FFD1DC, #A0D2EB, #E5A9E0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
            display: inline-block;
        }
        .tagline-sub {
            font-size: 0.62rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: rgba(160,210,235,0.45);
            margin-top: 9px;
            opacity: 0;
            animation: fadeUp 0.6s ease 3.5s forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── HOLOGRAPHIC PROGRESS BAR ── */
        .progress-wrap {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: rgba(255,255,255,0.04);
            z-index: 30;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #FFD1DC, #E5A9E0, #A0D2EB, #FFD1DC);
            background-size: 200% 100%;
            animation: progressFill 6.5s linear 0.5s forwards, holoShift 2s linear 0.5s infinite;
            box-shadow: 0 0 8px rgba(229,169,224,0.6);
        }
        @keyframes progressFill {
            to { width: 100%; }
        }
        @keyframes holoShift {
            from { background-position: 0% 0%; }
            to   { background-position: 200% 0%; }
        }

        /* ── SKIP ── */
        .skip-btn {
            position: fixed;
            top: 22px; right: 28px;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.25);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            cursor: pointer;
            z-index: 50;
            border: none;
            background: none;
            font-family: 'Space Grotesk', sans-serif;
            transition: color 0.3s;
            opacity: 0;
            animation: fadeUp 0.5s ease 1.2s forwards;
        }
        .skip-btn:hover { color: rgba(255,255,255,0.65); }

        /* ── PAGE FADE OUT ── */
        .page-fadeout { animation: pageFade 0.9s ease forwards; }
        @keyframes pageFade { to { opacity: 0; } }

        /* ── FLASH SPARKLES ── */
        .burst-spark {
            position: absolute;
            pointer-events: none;
            opacity: 0;
            z-index: 5;
            animation: burstAnim var(--dur) ease var(--delay) forwards;
            font-size: var(--sz);
            color: var(--col);
        }
        @keyframes burstAnim {
            0%   { opacity: 0; transform: translate(0,0) scale(0.4) rotate(0deg); }
            30%  { opacity: 1; transform: translate(var(--tx), var(--ty)) scale(1.2) rotate(15deg); }
            100% { opacity: 0; transform: translate(calc(var(--tx)*1.6), calc(var(--ty)*1.6)) scale(0.7) rotate(40deg); }
        }
    </style>
</head>
<body>

<button class="skip-btn" onclick="goToShop()">SKIP ›</button>
<div class="bg-glow"></div>
<div class="stars-layer" id="starsLayer"></div>

<div class="intro-stage">
    <div class="emblem-wrap" id="emblemWrap">
        <div class="ring-glow"></div>
        <div class="ring-border"></div>
        <div class="ring-border-2"></div>
        <img src="dreamy_logo.png" alt="Dreamy Y2K Shop" class="logo-img" id="logoImg">
    </div>

    <div class="loading-dots">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>

    <div class="tagline-wrap">
        <div class="tagline-text" id="taglineText"></div>
        <div class="tagline-sub">Y2K Fashion for the Digital Dreamgirl</div>
    </div>
</div>

<div class="progress-wrap">
    <div class="progress-fill"></div>
</div>

<script>
// ── STAR PARTICLES ──
const starsLayer = document.getElementById('starsLayer');
for (let i = 0; i < 90; i++) {
    const s = document.createElement('div');
    s.className = 'star-particle';
    const sz = Math.random() * 2.5 + 0.8;
    s.style.cssText = `width:${sz}px;height:${sz}px;left:${Math.random()*100}%;top:${Math.random()*100}%;--dur:${2+Math.random()*4}s;--delay:${Math.random()*5}s;--op:${0.25+Math.random()*0.75};`;
    starsLayer.appendChild(s);
}

// ── DRIFTING SPARKLE PARTICLES ──
const driftSymbols = ['✦','✧','⋆','✶','◇','·'];
const driftColors  = ['#FFD1DC','#E5A9E0','#A0D2EB','#ffffff','#d4b8ff'];
for (let i = 0; i < 22; i++) {
    const d = document.createElement('div');
    d.className = 'drift-particle';
    d.textContent = driftSymbols[i % driftSymbols.length];
    d.style.cssText = `
        left:${5 + Math.random()*90}%;
        bottom:${Math.random()*60}%;
        --sz:${0.6 + Math.random()*0.9}rem;
        --col:${driftColors[i % driftColors.length]};
        --dur:${4 + Math.random()*5}s;
        --delay:${Math.random()*6}s;
    `;
    document.body.appendChild(d);
}

// ── BURST SPARKLES on emblem appear ──
setTimeout(() => {
    const wrap = document.getElementById('emblemWrap');
    const syms  = ['✦','✧','★','✶','⋆'];
    const cols  = ['#FFD1DC','#A0D2EB','#E5A9E0','#ffffff'];
    const spots = [
        {tx:'-60px',ty:'-55px'},{tx:'55px',ty:'-65px'},
        {tx:'-75px',ty:'10px'}, {tx:'70px',ty:'15px'},
        {tx:'-50px',ty:'60px'}, {tx:'48px',ty:'62px'},
        {tx:'0px',  ty:'-80px'},{tx:'-20px',ty:'75px'},
    ];
    spots.forEach((sp, i) => {
        const el = document.createElement('span');
        el.className = 'burst-spark';
        el.textContent = syms[i % syms.length];
        el.style.cssText = `
            top:50%;left:50%;
            --tx:${sp.tx};--ty:${sp.ty};
            --dur:${0.9 + Math.random()*0.5}s;
            --delay:${0.05 * i}s;
            --sz:${0.85 + Math.random()*0.7}rem;
            --col:${cols[i % cols.length]};
        `;
        wrap.appendChild(el);
    });
}, 900);

// ── TYPEWRITER ──
const el     = document.getElementById('taglineText');
const phrase = 'Welcome to Dreamy ✦';
let   idx    = 0;
function typeChar() {
    if (idx < phrase.length) {
        el.textContent += phrase[idx++];
        setTimeout(typeChar, 72);
    }
}
setTimeout(typeChar, 2700);

// ── AUTO REDIRECT at ~7s ──
let timer = setTimeout(goToShop, 7000);

function goToShop() {
    clearTimeout(timer);
    document.body.classList.add('page-fadeout');
    setTimeout(() => { window.location.href = 'login.php'; }, 850);
}
</script>
</body>
</html>