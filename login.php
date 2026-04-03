<?php 
session_start();
include('db_config.php');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

// If already logged in, send to home
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$toast_msg = '';
$toast_type = '';
$redirect_to = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // --- LOGIN LOGIC ---
    if ($_POST['auth_action'] == 'login') {
        $checkUser = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $checkUser);

        if (mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            if ($user_data['password'] === $pass) {
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['username'] = $user_data['username'];
                $toast_msg = "Welcome back, " . htmlspecialchars($_SESSION['username']) . "! ✦";
                $toast_type = 'success';
                $redirect_to = 'home.php';
            } else {
                $toast_msg = "Incorrect password. Try again!";
                $toast_type = 'error';
            }
        } else {
            $toast_msg = "No account found with this email.";
            $toast_type = 'error';
        }
    } 
    
    // --- SIGN UP LOGIC ---
    else if ($_POST['auth_action'] == 'signup') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        
        $checkEmail = "SELECT email FROM users WHERE email='$email'";
        $exists = mysqli_query($conn, $checkEmail);

        if (mysqli_num_rows($exists) > 0) {
            $toast_msg = "This email is already in use!";
            $toast_type = 'error';
        } else {
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$pass')";
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                mysqli_query($conn, "INSERT INTO orders (user_id, total_price) VALUES ($new_id, 0.00)");
                $toast_msg = "Welcome to Dreamy, " . htmlspecialchars($username) . "! ✦";
                $toast_type = 'success';
                $redirect_to = 'home.php';
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($mode == 'signup') ? 'Join' : 'Login'; ?> - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .dreamy-toast {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: white;
            border: 2px solid #A0D2EB;
            box-shadow: 5px 5px 0 #E5A9E0;
            padding: 14px 28px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            z-index: 9999;
            opacity: 0;
            transition: all 0.35s ease;
            max-width: 340px;
            text-align: center;
            pointer-events: none;
        }
        .dreamy-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .dreamy-toast .toast-brand {
            font-size: 0.65rem;
            color: #A0D2EB;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .dreamy-toast.success { border-color: #A0D2EB; }
        .dreamy-toast.success .toast-brand { color: #A0D2EB; }
        .dreamy-toast.error { border-color: #FFD1DC; box-shadow: 5px 5px 0 #FFD1DC; }
        .dreamy-toast.error .toast-brand { color: #E5A9E0; }
    </style>
</head>
<body>

<!-- ╔══════════════════════════════════════╗ -->
<!-- ║        DREAMY INTRO OVERLAY          ║ -->
<!-- ╚══════════════════════════════════════╝ -->
<style>
    body.di-active {
        overflow: hidden !important;
    }
    #dreamy-intro {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        width: 100vw; height: 100vh;
        background: #06060f;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        overflow: hidden;
        margin: 0; padding: 0;
    }
    #dreamy-intro.hide-intro {
        animation: introFadeOut 0.9s ease forwards;
    }
    @keyframes introFadeOut {
        to { opacity: 0; pointer-events: none; }
    }

    .di-stars { position: absolute; inset: 0; pointer-events: none; }
    .di-star {
        position: absolute;
        border-radius: 50%;
        background: #fff;
        opacity: 0;
        animation: diTwinkle var(--dur) ease-in-out infinite var(--delay);
    }
    @keyframes diTwinkle {
        0%,100% { opacity:0; transform:scale(0.5); }
        50%      { opacity:var(--op); transform:scale(1); }
    }

    .di-drift {
        position: absolute;
        pointer-events: none;
        opacity: 0;
        font-size: var(--sz);
        color: var(--col);
        animation: diDrift var(--dur) ease-in var(--delay) infinite;
    }
    @keyframes diDrift {
        0%   { opacity:0;   transform:translateY(0) rotate(0deg) scale(0.6); }
        15%  { opacity:0.9; }
        85%  { opacity:0.5; }
        100% { opacity:0;   transform:translateY(-110px) rotate(28deg) scale(1.1); }
    }

    .di-bg-glow {
        position: absolute; inset: 0;
        background: radial-gradient(ellipse at 50% 50%, rgba(160,100,235,0) 0%, transparent 60%);
        animation: diBgReveal 2.5s ease 0.8s forwards;
    }
    @keyframes diBgReveal {
        to { background: radial-gradient(ellipse at 50% 50%, rgba(100,60,180,0.22) 0%, rgba(160,210,235,0.07) 45%, transparent 70%); }
    }

    .di-stage { position: relative; z-index: 10; display:flex; flex-direction:column; align-items:center; }

    .di-emblem {
        position: relative;
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transform: scale(0.7) rotate(-8deg);
        animation: diEmblemIn 1.4s cubic-bezier(0.16,1,0.3,1) 0.3s forwards;
    }
    @keyframes diEmblemIn {
        0%  { opacity:0; transform:scale(0.7) rotate(-8deg); }
        60% { opacity:1; transform:scale(1.06) rotate(1deg); }
        100%{ opacity:1; transform:scale(1) rotate(0deg); }
    }

    .di-ring-glow {
        position:absolute;
        width:min(400px,70vw); height:min(400px,70vw);
        border-radius:50%;
        background:radial-gradient(circle, rgba(229,169,224,0.18) 0%, rgba(160,210,235,0.10) 50%, transparent 75%);
        opacity:0;
        animation:diRingAppear 1.8s ease 0.6s forwards;
    }
    .di-ring1 {
        position:absolute;
        width:min(380px,67vw); height:min(380px,67vw);
        border-radius:50%;
        border:1px solid rgba(229,169,224,0.28);
        opacity:0;
        animation:diRingAppear 1.8s ease 1s forwards, diRingRot 18s linear 1s infinite;
    }
    .di-ring1::before {
        content:''; position:absolute;
        top:-3px; left:50%;
        width:6px; height:6px; border-radius:50%;
        background:#E5A9E0;
        box-shadow:0 0 8px 3px rgba(229,169,224,0.7);
        transform:translateX(-50%);
    }
    .di-ring2 {
        position:absolute;
        width:min(420px,74vw); height:min(420px,74vw);
        border-radius:50%;
        border:1px solid rgba(160,210,235,0.18);
        opacity:0;
        animation:diRingAppear 2s ease 1.2s forwards, diRingRotRev 24s linear 1.2s infinite;
    }
    .di-ring2::before {
        content:''; position:absolute;
        bottom:-3px; left:50%;
        width:5px; height:5px; border-radius:50%;
        background:#A0D2EB;
        box-shadow:0 0 8px 3px rgba(160,210,235,0.6);
        transform:translateX(-50%);
    }
    @keyframes diRingAppear { to { opacity:1; } }
    @keyframes diRingRot    { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
    @keyframes diRingRotRev { from{transform:rotate(0deg)} to{transform:rotate(-360deg)} }

    .di-logo {
        width:min(260px,50vw); height:auto;
        position:relative; z-index:2;
        filter:drop-shadow(0 0 30px rgba(229,169,224,0.55)) drop-shadow(0 0 70px rgba(160,210,235,0.25));
        animation:diLogoPulse 3.5s ease-in-out 1.8s infinite;
    }
    @keyframes diLogoPulse {
        0%,100%{ filter:drop-shadow(0 0 28px rgba(229,169,224,0.5)) drop-shadow(0 0 60px rgba(160,210,235,0.2)); }
        50%    { filter:drop-shadow(0 0 50px rgba(229,169,224,0.85)) drop-shadow(0 0 90px rgba(160,210,235,0.38)); }
    }

    .di-burst {
        position:absolute; pointer-events:none; opacity:0; z-index:5;
        font-size:var(--sz); color:var(--col);
        animation:diBurst var(--dur) ease var(--delay) forwards;
    }
    @keyframes diBurst {
        0%  { opacity:0; transform:translate(0,0) scale(0.4) rotate(0deg); }
        30% { opacity:1; transform:translate(var(--tx),var(--ty)) scale(1.2) rotate(15deg); }
        100%{ opacity:0; transform:translate(calc(var(--tx)*1.6),calc(var(--ty)*1.6)) scale(0.7) rotate(40deg); }
    }

    .di-dots {
        margin-top:32px;
        display:flex; gap:10px; align-items:center;
        opacity:0;
        animation:diFadeUp 0.6s ease 2s forwards;
    }
    .di-dot {
        width:7px; height:7px; border-radius:50%;
        animation:diDotPulse 1.4s ease-in-out infinite;
    }
    .di-dot:nth-child(1){animation-delay:0s;    background:rgba(255,209,220,0.5);}
    .di-dot:nth-child(2){animation-delay:0.18s; background:rgba(229,169,224,0.5);}
    .di-dot:nth-child(3){animation-delay:0.36s; background:rgba(160,210,235,0.5);}
    .di-dot:nth-child(4){animation-delay:0.54s; background:rgba(229,169,224,0.5);}
    .di-dot:nth-child(5){animation-delay:0.72s; background:rgba(255,209,220,0.5);}
    @keyframes diDotPulse {
        0%,100%{ transform:scale(1);   opacity:0.35; }
        50%    { transform:scale(1.7); opacity:1; }
    }

    .di-tagline {
        margin-top:20px;
        font-family:'Playfair Display',serif;
        font-style:italic;
        font-size:clamp(0.85rem,2.2vw,1.1rem);
        background:linear-gradient(90deg,#FFD1DC,#A0D2EB,#E5A9E0);
        -webkit-background-clip:text; background-clip:text;
        -webkit-text-fill-color:transparent;
        letter-spacing:2px;
        min-height:1.4em;
        opacity:0;
        animation:diFadeUp 0.7s ease 2.4s forwards;
    }

    @keyframes diFadeUp {
        from{opacity:0;transform:translateY(10px);}
        to  {opacity:1;transform:translateY(0);}
    }

    .di-progress {
        position:fixed; bottom:0; left:0; right:0;
        height:3px; background:rgba(255,255,255,0.04); z-index:100000;
    }
    .di-progress-fill {
        height:100%; width:0%;
        background:linear-gradient(90deg,#FFD1DC,#E5A9E0,#A0D2EB,#FFD1DC);
        background-size:200% 100%;
        box-shadow:0 0 8px rgba(229,169,224,0.6);
        animation:diProgress 3.8s linear 0.4s forwards, diHolo 2s linear 0.4s infinite;
    }
    @keyframes diProgress { to{width:100%;} }
    @keyframes diHolo {
        from{background-position:0% 0%;}
        to  {background-position:200% 0%;}
    }

    .di-skip {
        position:fixed; top:22px; right:28px;
        font-size:0.65rem; color:rgba(255,255,255,0.25);
        letter-spacing:2.5px; text-transform:uppercase;
        cursor:pointer; border:none; background:none;
        font-family:'Space Grotesk',sans-serif;
        transition:color 0.3s; z-index:100001;
        opacity:0; animation:diFadeUp 0.5s ease 1s forwards;
    }
    .di-skip:hover{color:rgba(255,255,255,0.65);}
</style>

<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
<div id="dreamy-intro">
    <div class="di-bg-glow"></div>
    <div class="di-stars" id="diStars"></div>

    <div class="di-stage">
        <div class="di-emblem" id="diEmblem">
            <div class="di-ring-glow"></div>
            <div class="di-ring1"></div>
            <div class="di-ring2"></div>
            <img src="dreamy_logo.png" alt="Dreamy Y2K" class="di-logo">
        </div>
        <div class="di-dots">
            <div class="di-dot"></div>
            <div class="di-dot"></div>
            <div class="di-dot"></div>
            <div class="di-dot"></div>
            <div class="di-dot"></div>
        </div>
        <div class="di-tagline" id="diTagline"></div>
    </div>

    <div class="di-progress"><div class="di-progress-fill"></div></div>
    <button class="di-skip" onclick="diDismiss()">SKIP ›</button>
</div>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
<script>
function diDismiss(){
    var intro=document.getElementById('dreamy-intro');
    if(!intro) return;
    intro.style.animation='introFadeOut 0.9s ease forwards';
    setTimeout(function(){
        intro.style.display='none';
        document.body.classList.remove('di-active');
    },900);
}
(function(){
    document.body.classList.add('di-active');
    var starsEl = document.getElementById('diStars');
    for(var i=0;i<85;i++){
        var s=document.createElement('div'); s.className='di-star';
        var sz=Math.random()*2.5+0.8;
        s.style.cssText='width:'+sz+'px;height:'+sz+'px;left:'+(Math.random()*100)+'%;top:'+(Math.random()*100)+'%;--dur:'+(2+Math.random()*4)+'s;--delay:'+(Math.random()*5)+'s;--op:'+(0.25+Math.random()*0.75)+';';
        starsEl.appendChild(s);
    }
    var driftSyms=['✦','✧','⋆','✶','◇','·'];
    var driftCols=['#FFD1DC','#E5A9E0','#A0D2EB','#ffffff','#d4b8ff'];
    for(var j=0;j<20;j++){
        var d=document.createElement('div'); d.className='di-drift';
        d.textContent=driftSyms[j%driftSyms.length];
        d.style.cssText='left:'+(5+Math.random()*90)+'%;bottom:'+(Math.random()*70)+'%;--sz:'+(0.6+Math.random()*0.9)+'rem;--col:'+driftCols[j%driftCols.length]+';--dur:'+(4+Math.random()*5)+'s;--delay:'+(Math.random()*6)+'s;';
        document.getElementById('dreamy-intro').appendChild(d);
    }
    setTimeout(function(){
        var emb=document.getElementById('diEmblem');
        var syms=['✦','✧','★','✶','⋆'];
        var cols=['#FFD1DC','#A0D2EB','#E5A9E0','#ffffff'];
        var spots=[{tx:'-60px',ty:'-55px'},{tx:'55px',ty:'-65px'},{tx:'-75px',ty:'10px'},{tx:'70px',ty:'15px'},{tx:'-50px',ty:'60px'},{tx:'48px',ty:'62px'},{tx:'0px',ty:'-80px'},{tx:'-20px',ty:'75px'}];
        spots.forEach(function(sp,i){
            var el=document.createElement('span'); el.className='di-burst';
            el.textContent=syms[i%syms.length];
            el.style.cssText='top:50%;left:50%;--tx:'+sp.tx+';--ty:'+sp.ty+';--dur:'+(0.9+Math.random()*0.5)+'s;--delay:'+(0.05*i)+'s;--sz:'+(0.85+Math.random()*0.7)+'rem;--col:'+cols[i%cols.length]+';';
            emb.appendChild(el);
        });
    },900);
    var tagEl=document.getElementById('diTagline');
    var phrase='Welcome to Dreamy ✦';
    var idx=0;
    function typeChar(){
        if(idx<phrase.length){ tagEl.textContent+=phrase[idx++]; setTimeout(typeChar,72); }
    }
    setTimeout(typeChar,2600);
    setTimeout(diDismiss,4500);
})();
</script>
<?php endif; ?>
<!-- ══════════════════════════════════════════ -->



<div class="login-container">
    <div class="login-window">
        <div class="window-header">
            <div class="dots">
                <span class="dot pink"></span>
                <span class="dot blue"></span>
            </div>
            <div class="window-title"><?php echo ($mode == 'signup') ? 'new_user.exe' : 'auth_system.exe'; ?></div>
        </div>
        
        <div class="login-content">
            <div class="login-logo">✦ DREAMY ✦</div>
            <p class="login-subtitle">
                <?php echo ($mode == 'signup') ? 'Initialize new profile' : 'Enter credentials to access'; ?>
            </p>
            
            <form action="login.php" method="POST" class="login-form">
                <input type="hidden" name="auth_action" value="<?php echo $mode; ?>">

                <?php if($mode == 'signup'): ?>
                    <div class="input-group">
                        <label>USERNAME</label>
                        <input type="text" name="username" placeholder="cyber_ghost" required>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label>EMAIL</label>
                    <input type="email" name="email" placeholder="user@dream.com" required>
                </div>

                <div class="input-group">
                    <label>PASSWORD</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="login-submit-btn">
                    <?php echo ($mode == 'signup') ? 'CREATE ACCOUNT' : 'ACCESS SYSTEM'; ?>
                </button>
            </form>
            
            <div class="login-footer">
                <?php if($mode == 'login'): ?>
                    <p>New here? <a href="login.php?mode=signup">Create an Account</a></p>
                <?php else: ?>
                    <p>Already a member? <a href="login.php?mode=login">Back to Login</a></p>
                <?php endif; ?>
                <?php if(true): // always show admin link ?>
                    <p style="margin-top:15px; font-size:0.7rem; color:#bbb;"><a href="admin_login.php" style="color:#A0D2EB;">Admin access →</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($toast_msg): ?>
<div class="dreamy-toast <?php echo $toast_type; ?>" id="dreamyToast">
    <div class="toast-brand">✦ DREAMY ✦</div>
    <?php echo $toast_msg; ?>
</div>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        var toast = document.getElementById('dreamyToast');
        setTimeout(function() { toast.classList.add('show'); }, 100);
        <?php if ($redirect_to): ?>
        setTimeout(function() { window.location = '<?php echo $redirect_to; ?>'; }, 1800);
        <?php else: ?>
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
        <?php endif; ?>
    });
</script>
<?php endif; ?>

    <!-- DREAMY FOOTER -->
    <footer class="dreamy-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="footer-logo">✦ Dreamy ✦</div>
                <p class="footer-tagline">Y2K fashion for the digital dreamgirl. Soft, bold, and unapologetically cute — because every outfit deserves a vibe.</p>
                <div class="footer-social">
                    <a href="#" title="Instagram">📷</a>
                    <a href="#" title="Pinterest">📌</a>
                    <a href="#" title="TikTok">🎵</a>
                    <a href="#" title="Twitter">🐦</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <ul>
                    <li><a href="home.php">Featured Drops</a></li>
                    <li><a href="tops.php">Tops</a></li>
                    <li><a href="bottoms.php">Bottoms</a></li>
                    <li><a href="cart.php">My Cart</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="account.php">My Profile</a></li>
                    <li><a href="login.php">Login / Sign Up</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Dreamy Y2K. All rights reserved.</p>
            <span class="footer-hearts">✦ ♡ ✦ ♡ ✦</span>
            <p>Made with ♡ for the dreamgirls</p>
        </div>
    </footer>


    <!-- FLOATING LOGIN SPARKLES -->
    <div class="login-sparkles" id="loginSparkles"></div>
    <script>
    (function() {
        var container = document.getElementById('loginSparkles');
        if (!container) return;
        var symbols = ['✦','✧','★','⋆','✶','♡','✿'];
        var colors  = ['#FFD1DC','#A0D2EB','#E5A9E0','rgba(255,209,220,0.7)','rgba(160,210,235,0.6)'];
        for (var i = 0; i < 18; i++) {
            var el = document.createElement('span');
            el.textContent = symbols[i % symbols.length];
            el.style.cssText = [
                'left:' + (5 + Math.random() * 90) + '%',
                'top:' + (10 + Math.random() * 80) + '%',
                '--dur:' + (4 + Math.random() * 5) + 's',
                '--delay:' + (Math.random() * 6) + 's',
                '--color:' + colors[i % colors.length],
                'font-size:' + (0.7 + Math.random() * 0.9) + 'rem'
            ].join(';');
            container.appendChild(el);
        }
    })();
    </script>
</body>
</html>