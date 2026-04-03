<?php 
session_start();
include('db_config.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// FIX: Only get the PENDING (active) order
$order_query = "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending'";
$order_result = mysqli_query($conn, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$order_id = $order_row ? intval($order_row['order_id']) : null;

if (!$order_id) {
    mysqli_query($conn, "INSERT INTO orders (user_id, total_price, status) VALUES ($user_id, 0.00, 'pending')");
    $order_id = mysqli_insert_id($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="cart.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="marquee">
        <div class="marquee-content">
            <span>✦ YOUR SHOPPING CART ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ YOUR SHOPPING CART ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦</span>
            <span>✦ YOUR SHOPPING CART ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ YOUR SHOPPING CART ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦</span>
        </div>
    </div>

    <nav class="navbar">
        <div class="logo">✦ DREAMY ✦</div>
            <ul class="nav-links">
            <li><a href="home.php" ><i class="bi bi-house-heart"></i> Home</a></li>
            <li><a href="tops.php" >Tops</a></li>
            <li><a href="bottoms.php" >Bottoms</a></li>
            <li><a href="cart.php" class="active cart-badge"><i class="bi bi-bag-heart"></i> Cart<span class="cart-badge-count" id="cartCount">0</span></a></li>
            <li><a href="orders.php" ><i class="bi bi-clock-history"></i> Orders</a></li>
            <li class="account-btn"><a href="account.php" ><i class="bi bi-person-circle"></i> Account</a></li>
        </ul>
    </nav>

    <main class="container">
        <header class="category-header">
            <div class="category-window">
                <div class="window-header">
                    <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                    <span>shopping_cart.exe</span>
                </div>
                <div class="category-banner">
                    <h1>YOUR CURRENT ORDERS</h1>
                </div>
            </div>
        </header>

        <div class="product-grid">
            <?php
            $query = "SELECT order_items.item_id, order_items.quantity, products.name, products.price, products.img_url, products.product_id, products.stock_count 
                      FROM order_items 
                      JOIN products ON order_items.product_id = products.product_id 
                      WHERE order_items.order_id = $order_id";
            
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                // ── Auto-correct quantities that exceed current stock ──────────
                $adjusted_items = [];
                $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
                foreach ($rows as $row) {
                    $item_id     = intval($row['item_id']);
                    $cart_qty    = intval($row['quantity']);
                    $stock_count = intval($row['stock_count']);

                    if ($stock_count <= 0) {
                        // Sold out — cap cart qty to 0 display-only (keep in cart so user can remove)
                        $row['quantity'] = 0;
                    } elseif ($cart_qty > $stock_count) {
                        // Cart qty exceeds available stock — silently fix in DB and display
                        mysqli_query($conn, "UPDATE order_items SET quantity = $stock_count WHERE item_id = $item_id");
                        $row['quantity'] = $stock_count;
                        $adjusted_items[] = $row['name'];
                    }
                    $rows_fixed[] = $row;
                }

                if (!empty($adjusted_items)) { ?>
                    <div style="background:#fff3cd;border:2px solid #ffc107;border-radius:8px;padding:14px 18px;margin-bottom:18px;font-weight:700;color:#7a5700;">
                        ⚠️ Some quantities were adjusted because stock changed: <?php echo implode(', ', $adjusted_items); ?>
                    </div>
                <?php }

                foreach ($rows_fixed as $row) {
                    $item_id     = intval($row['item_id']);
                    $quantity    = intval($row['quantity']);
                    $stock_count = intval($row['stock_count']);
                    // can_add: only if there is remaining stock beyond what's already in cart
                    $can_add        = $stock_count > $quantity;
                    $is_out_of_stock = $stock_count <= 0;
                    ?>
                    <div class="window-card <?php echo $is_out_of_stock ? 'cart-item-out-of-stock' : ''; ?>" id="cart-item-<?php echo $item_id; ?>">
                        <div class="window-header">
                            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                            <div class="window-title">item_<?php echo $item_id; ?>.exe</div>
                        </div>
                        <div class="product-img" style="position: relative;">
                            <img src="<?php echo $row['img_url']; ?>" alt="Product">
                            <?php if ($is_out_of_stock): ?>
                                <div class="cart-item-stock-warning">OUT OF STOCK</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo $row['name']; ?></h3>
                            <p class="price">$<?php echo $row['price']; ?></p>

                            <?php if ($is_out_of_stock): ?>
                                <p style="color:#e05555;font-weight:700;font-size:0.85rem;margin:8px 0;">
                                    ✕ This item is sold out and will not be purchased at checkout.
                                </p>
                            <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0; justify-content: center;">
                                <a href="update_quantity.php?id=<?php echo $item_id; ?>&action=decrease&anchor=cart-item-<?php echo $item_id; ?>" class="qty-btn" style="padding: 5px 10px; background: #FFD1DC; color: #000; text-decoration: none; border-radius: 3px; font-weight: bold;">−</a>
                                <span style="max-width: 60px; text-align: center; font-weight: bold;">QTY: <?php echo $quantity; ?></span>
                                <a href="update_quantity.php?id=<?php echo $item_id; ?>&action=increase&anchor=cart-item-<?php echo $item_id; ?>"
                                   class="qty-btn"
                                   style="padding: 5px 10px; background: #A0D2EB; color: #000; text-decoration: none; border-radius: 3px; font-weight: bold; <?php echo !$can_add ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                                   <?php echo !$can_add ? 'onclick="return false;"' : ''; ?>>+</a>
                            </div>
                            <?php endif; ?>

                            <a href="delete_item.php?id=<?php echo $item_id; ?>&anchor=cart-item-<?php echo $item_id; ?>" class="add-btn">REMOVE ITEM</a>
                        </div>
                    </div>
                    <?php 
                }
            } else {
                echo "<div class='empty-msg'>Your cart is empty. No items found in your collection.</div>";
            }
            ?>
        </div>

        <?php
        $total_query = "SELECT SUM(order_items.quantity * products.price) as total
                        FROM order_items
                        JOIN products ON order_items.product_id = products.product_id
                        WHERE order_items.order_id = $order_id
                        AND products.stock_count > 0";
        $total_result = mysqli_query($conn, $total_query);
        $total_row = mysqli_fetch_assoc($total_result);
        $cart_total = $total_row['total'] ? floatval($total_row['total']) : 0;

        if ($cart_total > 0): ?>
        <div class="cart-footer">
            <div class="cart-total-window">
                <div class="window-header">
                    <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                    <span>order_total.exe</span>
                </div>
                <div class="cart-total-body">
                    <div class="total-line">
                        <span>Subtotal</span>
                        <span class="total-amount">$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="total-line">
                        <span>Shipping</span>
                        <span style="color: #A0D2EB; font-weight: 600;">FREE ✦</span>
                    </div>
                    <div class="total-line grand-total">
                        <span>TOTAL</span>
                        <span class="total-amount">$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">✦ PROCEED TO CHECKOUT ✦</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

    // Load cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>
