<?php 
session_start();
include('db_config.php'); 

// 1. LOGOUT LOGIC: If logout is clicked, redirect to login
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']); 

// Handle the UPDATE request
if (isset($_POST['update_username'])) {
    $new_user = mysqli_real_escape_string($conn, $_POST['username']);
    $update_sql = "UPDATE users SET username = '$new_user' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "PROFILE SYNCED! ✦";
    }
}
else if (isset($_POST['update_email'])) {
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $update_sql = "UPDATE users SET email = '$new_email' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "PROFILE SYNCED! ✦";
    }
}
else if (isset($_POST['update_address'])) {
    $new_address = mysqli_real_escape_string($conn, $_POST['address']);
    $update_sql = "UPDATE users SET address = '$new_address' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "PROFILE SYNCED! ✦";
    }
}

else if (isset($_POST['update_phone_no'])) {
    $new_phone_no = mysqli_real_escape_string($conn, $_POST['phone_no']);
    $update_sql = "UPDATE users SET phone_no = '$new_phone_no' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "PROFILE SYNCED! ✦";
    }
}
// Fetch current user data
$query = "SELECT * FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Terminal - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="home.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* GLITCH EFFECT FOR THE WINDOW */
        .account-window:hover {
            animation: window-glitch 0.3s ease-in-out;
            box-shadow: 12px 12px 0px #A0D2EB, -4px -4px 0px #FFD1DC;
        }

        @keyframes window-glitch {
            0% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(2px, -2px); }
            100% { transform: translate(0); }
        }

        .account-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }

        .account-window {
            background: white;
            border: 3px solid #A0D2EB;
            width: 100%;
            max-width: 450px;
            box-shadow: 10px 10px 0px #E5A9E0;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
        }

        .profile-content { padding: 30px; text-align: center; }

        /* Y2K STYLE INFO DISPLAY */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fdf2f7;
            padding: 12px 15px;
            border-left: 5px solid #E5A9E0;
            margin-bottom: 10px;
            text-align: left;
        }

        .info-label { font-size: 0.65rem; font-weight: 700; color: #A0D2EB; text-transform: uppercase; }
        .info-value { font-size: 0.9rem; font-weight: 500; color: #2B2B2B; }

        /* TOGGLE BUTTONS */
        .edit-trigger {
            background: #A0D2EB;
            color: white;
            border: none;
            padding: 4px 10px;
            font-size: 0.6rem;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }

        .hidden-form { display: none; margin-top: 15px; padding: 15px; border: 1px dashed #A0D2EB; }

        .username-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #FDEEF4;
            margin-bottom: 10px;
            font-family: 'Space Grotesk';
        }

        .save-btn {
            width: 100%;
            padding: 10px;
            background: #FFD1DC;
            border: 2px solid #E5A9E0;
            font-weight: bold;
            cursor: pointer;
        }

        /* LOGOUT BUTTON - THE PILL LOOK */
        .logout-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            color: #999;
            text-decoration: underline;
            transition: 0.3s;
        }
        .logout-link:hover { color: #FFD1DC; }

    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">✦ DREAMY ✦</div>
            <ul class="nav-links">
            <li><a href="home.php" ><i class="bi bi-house-heart"></i> Home</a></li>
            <li><a href="tops.php" >Tops</a></li>
            <li><a href="bottoms.php" >Bottoms</a></li>
            <li><a href="cart.php" ><i class="bi bi-bag-heart"></i> Cart</a></li>
            <li><a href="orders.php" ><i class="bi bi-clock-history"></i> Orders</a></li>
            <li class="account-btn"><a href="account.php" class="active"><i class="bi bi-person-circle"></i> Account</a></li>
        </ul>
    </nav>

    <div class="account-container">
        <div class="account-window">
            <div class="window-header">
                <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                <span>user_settings.exe</span>
            </div>

            <div class="profile-content">
                <h2 style="margin-bottom: 20px; font-style: italic; text-transform: uppercase;">SETTINGZ</h2>
                
                <?php if(isset($message)) echo "<p class='status-msg'>$message</p>"; ?>

                <div class="info-row">
                    <div>
                        <p class="info-label">Current User</p>
                        <p class="info-value"><?php echo $user['username']; ?></p>
                    </div>
                    <button class="edit-trigger" onclick="toggleForm1()">EDIT</button>
                </div>

                <div class="info-row" style="border-left-color: #A0D2EB;">
                    <div>
                        <p class="info-label">Email Address</p>
                        <p class="info-value"><?php echo $user['email']; ?></p>
                    </div>
                    <button class="edit-trigger" onclick="toggleForm2()">EDIT</button>
                    
                </div>
               
                <div class="info-row" style="border-left-color: #A0D2EB;">
        
                    <div>
                        <p class="info-label">Address</p>
                        <p class="info-value"><?php echo $user['address'] ? htmlspecialchars($user['address']) : '<span style="color:#ccc; font-style:italic; font-size:0.8rem;">Not set</span>'; ?></p>
                    </div>
                    <button class="edit-trigger" onclick="toggleForm3()"><?php echo $user['address'] ? 'EDIT' : 'ADD'; ?></button>
                </div>

                <div class="info-row" style="border-left-color: #A0D2EB;">
        
                    <div>
                        <p class="info-label">Phone Number</p>
                        <p class="info-value"><?php echo $user['phone_no'] ? htmlspecialchars($user['phone_no']) : '<span style="color:#ccc; font-style:italic; font-size:0.8rem;">Not set</span>'; ?></p>
                    </div>
                    <button class="edit-trigger" onclick="toggleForm4()"><?php echo $user['phone_no'] ? 'EDIT' : 'ADD'; ?></button>
                </div>

                <div id="updateSection1" class="hidden-form">
                    <form action="account.php" method="POST">
                        <label class="info-label" style="display:block; margin-bottom:5px;">New Username</label>
                        <input type="text" name="username" class="username-input" required>
                        <button type="submit" name="update_username" class="save-btn">SAVE CHANGES</button>
                    </form>
                </div>
                <div id="updateSection2" class="hidden-form">
                    <form action="account.php" method="POST">
                        <label class="info-label" style="display:block; margin-bottom:5px;">New Email</label>
                        <input type="email" name="email" class="username-input" required>
                        <button type="submit" name="update_email" class="save-btn">SAVE CHANGES</button>
                    </form>
                </div>
                 <div id="updateSection3" class="hidden-form">
                    <form action="account.php" method="POST">
                        <label class="info-label" style="display:block; margin-bottom:5px;"><?php echo $user['address'] ? 'Update Address' : 'Add Address'; ?></label>
                        <input type="text" name="address" class="username-input" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="e.g. 123 Dream Street, City" required>
                        <button type="submit" name="update_address" class="save-btn">SAVE CHANGES</button>
                    </form>
                </div>
                <div id="updateSection4" class="hidden-form">
                    <form action="account.php" method="POST">
                        <label class="info-label" style="display:block; margin-bottom:5px;"><?php echo $user['phone_no'] ? 'Update Phone Number' : 'Add Phone Number'; ?></label>
                        <input type="text" name="phone_no" class="username-input" value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>" placeholder="e.g. +91 98765 43210" required>
                        <button type="submit" name="update_phone_no" class="save-btn">SAVE CHANGES</button>
                    </form>
                </div>
                <a href="orders.php" class="logout-link" style="display:flex; align-items:center; gap:6px; justify-content:center; color:#A0D2EB;">
                    <i class="bi bi-clock-history"></i> View My Orders
                </a>
                <a href="account.php?action=logout" class="logout-link" style="display:flex; align-items:center; gap:6px; justify-content:center; margin-top:8px;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        // JAVASCRIPT TO TOGGLE THE FORM
        function toggleForm1() {
            var section = document.getElementById("updateSection1");
            if (section.style.display === "block") {
                section.style.display = "none";
            } else {
                section.style.display = "block";
            }
        }

        function toggleForm2() {
            var section = document.getElementById("updateSection2");
            if (section.style.display === "block") {
                section.style.display = "none";
            } else {
                section.style.display = "block";
            }
        }

        function toggleForm3() {
            var section = document.getElementById("updateSection3");
            if (section.style.display === "block") {
                section.style.display = "none";
            } else {
                section.style.display = "block";
            }
        }

         function toggleForm4() {
            var section = document.getElementById("updateSection4");
            if (section.style.display === "block") {
                section.style.display = "none";
            } else {
                section.style.display = "block";
            }
        }
    </script>

    <!-- DREAMY FOOTER -->
    <footer class="dreamy-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="footer-logo">✦ Dreamy ✦</div>
                <p class="footer-tagline">Y2K fashion for the digital dreamgirl. Soft, bold, and unapologetically cute — because every outfit deserves a vibe.</p>
                <div class="footer-social">
                    <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" title="Pinterest"><i class="bi bi-pinterest"></i></a>
                    <a href="#" title="TikTok"><i class="bi bi-tiktok"></i></a>
                    <a href="#" title="Twitter"><i class="bi bi-twitter-x"></i></a>
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
                    <li><a href="checkout.php">Checkout</a></li>
                    <li><a href="orders.php"><i class="bi bi-clock-history"></i> My Orders</a></li>
                    <li><a href="account.php?action=logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Dreamy Y2K. All rights reserved.</p>
            <span class="footer-hearts">✦ ♡ ✦ ♡ ✦</span>
            <p>Made with ♡ for the dreamgirls</p>
        </div>
    </footer>

    <!-- MOBILE HAMBURGER MENU -->
    <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="nav-overlay" id="navOverlay"></div>
    <script>
    (function() {
        var btn = document.getElementById('hamburger');
        var overlay = document.getElementById('navOverlay');
        var nav = document.querySelector('.nav-links');
        if (!btn || !nav) return;
        // Move hamburger into navbar
        var navbar = document.querySelector('.navbar');
        if (navbar) navbar.appendChild(btn);
        function toggleMenu(open) {
            btn.classList.toggle('open', open);
            nav.classList.toggle('mobile-open', open);
            overlay.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }
        btn.addEventListener('click', function() { toggleMenu(!btn.classList.contains('open')); });
        overlay.addEventListener('click', function() { toggleMenu(false); });
        nav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() { toggleMenu(false); });
        });
    })();
    </script>
</body>
</html>