<?php
session_start();
include('db_config.php');

// If already logged in as admin, go to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password'];

    $q = "SELECT * FROM admins WHERE email = '$email'";
    $r = mysqli_query($conn, $q);

    if ($r && mysqli_num_rows($r) > 0) {
        $admin = mysqli_fetch_assoc($r);
        // Using password_verify for secure hashed passwords
       if ($admin && $pass === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['name'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No admin account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .admin-badge {
            display: inline-block;
            background: #A0D2EB;
            color: white;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 3px 10px;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .error-box {
            background: #ffe0e0;
            border: 1px solid #ffb3b3;
            color: #c00;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-window">
        <div class="window-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <div class="window-title">admin_access.exe</div>
        </div>
        <div class="login-content">
            <div class="login-logo">✦ DREAMY ✦</div>
            <div style="text-align:center;"><span class="admin-badge">ADMIN PORTAL</span></div>
            <p class="login-subtitle">Restricted access — admins only</p>

            <?php if ($error): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="admin_login.php" method="POST" class="login-form">
                <div class="input-group">
                    <label>ADMIN EMAIL</label>
                    <input type="email" name="email" placeholder="admin@dreamy.com" required>
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="login-submit-btn">ACCESS ADMIN PANEL</button>
            </form>

            <div class="login-footer">
                <p><a href="login.php">← Back to Customer Login</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
