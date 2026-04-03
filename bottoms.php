<?php 
session_start();
include('db_config.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottoms - Dreamy Y2K Shop</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="bottoms.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="marquee">
        <div class="marquee-content">
            <span>✦ NEW BOTTOMS DROPPED ✦ 20% OFF FIRST ORDER ✦ SECURE CHECKOUT ✦ NEW BOTTOMS DROPPED ✦ 20% OFF FIRST ORDER ✦ SECURE CHECKOUT ✦</span>
            <span>✦ NEW BOTTOMS DROPPED ✦ 20% OFF FIRST ORDER ✦ SECURE CHECKOUT ✦ NEW BOTTOMS DROPPED ✦ 20% OFF FIRST ORDER ✦ SECURE CHECKOUT ✦</span>
        </div>
    </div>

    <nav class="navbar">
        <div class="logo">✦ DREAMY ✦</div>
            <ul class="nav-links">
            <li><a href="home.php" ><i class="bi bi-house-heart"></i> Home</a></li>
            <li><a href="tops.php" >Tops</a></li>
            <li><a href="bottoms.php" class="active">Bottoms</a></li>
            <li><a href="cart.php" class="cart-badge"><i class="bi bi-bag-heart"></i> Cart<span class="cart-badge-count" id="cartCount">0</span></a></li>
            <li><a href="orders.php" ><i class="bi bi-clock-history"></i> Orders</a></li>
            <li class="account-btn"><a href="account.php" ><i class="bi bi-person-circle"></i> Account</a></li>
        </ul>
    </nav>

    <header class="category-header">
        <div class="category-window">
            <div class="window-header">
                <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                <span>bottoms_collection.exe</span>
            </div>
            <div class="category-banner">
                <h1>NEW SEASON BOTTOMS</h1>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="product-grid">
            <?php
            // Fetching only Bottoms (category_id 3) and sorting by price
            $query = "SELECT * FROM products WHERE category_id = 3 ORDER BY price ASC"; 
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
    ?>
    <div class="window-card" id="product-<?php echo $row['product_id']; ?>">
        <div class="window-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <div class="window-title"><?php echo $row['name']; ?>.exe</div>
        </div>
        
        <div class="product-img-container" style="position: relative;">
    
    <?php 
    $stock = $row['stock_count'];

    // 1. SOLD OUT: If stock is exactly 0
    if ($stock == 0): ?>
        <div class="stock-sticker out-of-stock-sticker">
            SOLD OUT :(
        </div>

    <?php 
    // 2. LOW STOCK: If stock is between 1 and 4
    elseif ($stock > 0 && $stock <= 4): ?>
        <div class="stock-sticker">
            ONLY <?php echo $stock; ?> LEFT!
        </div>

    <?php 
    // 3. NORMAL STOCK: If stock is more than 4, it does nothing (no sticker)
    endif; ?>

    <a href="product.php?id=<?php echo $row['product_id']; ?>" style="display:block; text-decoration:none;">
        <div class="product-img">
            <img src="<?php echo $row['img_url']; ?>" alt="Product">
        </div>
    </a>
</div>

        <div class="product-info">
            <a href="product.php?id=<?php echo $row['product_id']; ?>" style="text-decoration:none; color:inherit;">
                <h3><?php echo $row['name']; ?></h3>
            </a>
            <p class="price">$<?php echo $row['price']; ?></p>
            
            <?php if($row['stock_count'] > 0): ?>
                <a href="add_to_cart.php?id=<?php echo $row['product_id']; ?>" class="add-btn"><i class="bi bi-bag-plus"></i> ADD TO CART</a>
                <a href="product.php?id=<?php echo $row['product_id']; ?>" class="add-btn buy-now-btn"><i class="bi bi-eye"></i> VIEW DETAILS</a>
            <?php else: ?>
                <button class="add-btn" style="background: #eee; color: #999 !important; border-color: #ccc; cursor: not-allowed;" disabled>
                    OUT OF STOCK
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php 
}
            } else {
                echo "<p>No bottoms found in the database.</p>";
            }
            ?>
        </div>
    </main>

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
                    <li><a href="orders.php">My Orders</a></li>
                    <li><a href="account.php?action=logout">Logout</a></li>
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

    // ═══════════════════════════════════════════════════════════
    // TOAST NOTIFICATION FUNCTION
    // ═══════════════════════════════════════════════════════════
    function showToast(message, type) {
        type = type || 'success';
        var toast = document.createElement('div');
        toast.className = 'toast-notification ' + type;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.classList.add('hide');
            setTimeout(function() {
                toast.remove();
            }, 500);
        }, 3000);
    }

    // ═══════════════════════════════════════════════════════════
    // UPDATE CART COUNTER
    // ═══════════════════════════════════════════════════════════
    function updateCartCount() {
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                var badge = document.getElementById('cartCount');
                if (badge) {
                    badge.textContent = data.count;
                }
            })
            .catch(error => console.error('Error fetching cart count:', error));
    }

    // Show toast based on ?status= param, keep hash so page stays in place
    (function() {
        var url = new URL(window.location);
        var status = url.searchParams.get('status');
        if (status) {
            if (status === 'added') {
                showToast('✨ Added to cart!', 'success');
            } else if (status === 'already_in_cart') {
                showToast('✓ Already in your cart!', 'success');
            } else if (status === 'out_of_stock') {
                showToast('Sorry, this item is out of stock!', 'error');
            }
            // Clean URL but keep the hash so scroll position is preserved
            var cleanUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    })();

    // Load cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>