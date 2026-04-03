<?php 
session_start();
include('db_config.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get user info
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get the user's PENDING order (cart)
$order_query = "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending' ORDER BY order_id DESC LIMIT 1";
$order_result = mysqli_query($conn, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$order_id = $order_row ? intval($order_row['order_id']) : null;

$items = [];
$grand_total = 0;

if ($order_id) {
    // Include stock_count so we can show stock status on checkout page
    $q = "SELECT order_items.item_id, order_items.quantity, products.name, products.price, products.img_url, products.product_id, products.stock_count
          FROM order_items
          JOIN products ON order_items.product_id = products.product_id
          WHERE order_items.order_id = $order_id";
    $res = mysqli_query($conn, $q);
    while ($r = mysqli_fetch_assoc($res)) {
        $stock  = intval($r['stock_count']);
        $cart_q = intval($r['quantity']);
        // Cap display quantity at available stock
        $r['display_qty']      = ($stock <= 0) ? 0 : min($cart_q, $stock);
        $r['is_out_of_stock']  = ($stock <= 0);
        $r['subtotal']         = $r['price'] * $r['display_qty'];
        if (!$r['is_out_of_stock']) {
            $grand_total += $r['subtotal'];
        }
        $items[] = $r;
    }
}

$order_placed = false;
$order_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    if (empty($items)) {
        $order_error = 'Your cart is empty!';
    } elseif (!$order_id) {
        $order_error = 'No active cart found. Please go back and add items.';
    } else {
        // Filter out items that are out of stock
        $items_to_purchase = [];
        $out_of_stock_items = [];
        $new_grand_total = 0;
        
        foreach ($items as $item) {
            $pid = intval($item['product_id']);
            $stock_query = "SELECT stock_count FROM products WHERE product_id = $pid";
            $stock_result = mysqli_query($conn, $stock_query);
            $stock_row = mysqli_fetch_assoc($stock_result);
            $current_stock = intval($stock_row['stock_count']);

            if ($current_stock <= 0) {
                // Completely out of stock — another user checked out first
                $out_of_stock_items[] = $item['name'] . ' (sold out just now — someone else got the last one!)';
            } else {
                // Cap qty at what is actually available right now
                $purchasable_qty = min(intval($item['quantity']), $current_stock);
                $adjusted_item = $item;
                $adjusted_item['quantity'] = $purchasable_qty;
                $adjusted_item['subtotal'] = floatval($item['price']) * $purchasable_qty;
                $items_to_purchase[] = $adjusted_item;
                $new_grand_total += $adjusted_item['subtotal'];
                if ($purchasable_qty < intval($item['quantity'])) {
                    $out_of_stock_items[] = $item['name'] . ' (only ' . $purchasable_qty . ' of ' . $item['quantity'] . ' were in stock — quantity adjusted)';
                }
            }
        }
        
        // If no items available for purchase
        if (empty($items_to_purchase)) {
            $order_error = 'All items in your cart are out of stock. Please remove them and try again.';
        } else {
            // Save each available item into purchased_items table (with order_id directly)
            $all_saved = true;
            foreach ($items_to_purchase as $item) {
                $pid        = intval($item['product_id']);
                $qty        = intval($item['quantity']);
                $price      = floatval($item['price']);
                $total_item = $price * $qty;
                $ins = "INSERT INTO purchased_items (order_id, user_id, product_id, quantity, price_at_purchase, total_price)
                        VALUES ($order_id, $user_id, $pid, $qty, $price, $total_item)";
                if (!mysqli_query($conn, $ins)) {
                    $all_saved = false;
                    $order_error = 'DB error: ' . mysqli_error($conn);
                    break;
                }
            }

            if ($all_saved) {
                // DEDUCT STOCK ONLY NOW (when purchase is confirmed) for available items
                // Using CURSOR pattern to track stock updates
                $stock_updates = [];
                foreach ($items_to_purchase as $item) {
                    $pid = intval($item['product_id']);
                    $qty = intval($item['quantity']);
                    
                    // Get current stock before update (cursor fetch pattern)
                    $stock_before = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock_count FROM products WHERE product_id = $pid"))['stock_count'];
                    
                    // Update stock
                    mysqli_query($conn, "UPDATE products SET stock_count = stock_count - $qty WHERE product_id = $pid");
                    
                    // Log the update (cursor pattern)
                    $stock_updates[] = [
                        'product_id'   => $pid,
                        'product_name' => $item['name'],
                        'qty_purchased'=> $qty,
                        'stock_before' => $stock_before,
                        'stock_after'  => $stock_before - $qty
                    ];
                }

                // Mark the order as confirmed and record total
                mysqli_query($conn, "UPDATE orders SET total_price = $new_grand_total, status = 'confirmed' WHERE order_id = $order_id");

                // Auto-save address and phone to user profile
                $save_address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
                $save_phone   = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
                if ($save_address || $save_phone) {
                    mysqli_query($conn, "UPDATE users SET
                        address  = IF('$save_address' != '', '$save_address', address),
                        phone_no = IF('$save_phone'   != '', '$save_phone',   phone_no)
                        WHERE user_id = $user_id");
                }

                // Remove ONLY purchased items from the cart (order_items)
                // We keep order_items as the cart reference — purchased_items is now the history
                foreach ($items_to_purchase as $item) {
                    $pid = intval($item['product_id']);
                    mysqli_query($conn, "DELETE FROM order_items WHERE order_id = $order_id AND product_id = $pid");
                }

                // Create a fresh pending order for next shopping session
                mysqli_query($conn, "INSERT INTO orders (user_id, total_price, status) VALUES ($user_id, 0.00, 'pending')");

                $order_placed = true;
                
                // Show warning if some items were out of stock
                if (!empty($out_of_stock_items)) {
                    $order_error = 'Order placed! Note: ' . implode(', ', $out_of_stock_items) . ' were out of stock and could not be purchased.';
                }
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
    <title>Checkout - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="cart.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 50px;
        }
        .checkout-section {
            background: white;
            border: 2px solid #A0D2EB;
            box-shadow: 6px 6px 0px #E5A9E0;
            border-radius: 8px;
            overflow: hidden;
        }
        .checkout-section .window-header {
            background: #f8f9fa;
            border-bottom: 2px solid #A0D2EB;
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .checkout-body { padding: 25px; }
        .checkout-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px dashed #E5A9E0;
        }
        .checkout-item img { width: 70px; height: 70px; object-fit: cover; border: 2px solid #A0D2EB; }
        .checkout-item-name { font-weight: 700; font-size: 0.9rem; }
        .checkout-item-meta { font-size: 0.75rem; color: #999; }
        .checkout-item-price { margin-left: auto; font-weight: 700; color: #A0D2EB; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: #A0D2EB; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px 12px; border: 2px solid #FDEEF4; font-family: 'Space Grotesk', sans-serif; font-size: 0.9rem; background: #fdf2f7; transition: 0.2s; }
        .form-group input:focus { outline: none; border-color: #A0D2EB; background: white; }
        .prefilled { background: #f5f5f5 !important; color: #777; }
        .order-summary-line { display: flex; justify-content: space-between; padding: 8px 0; font-size: 0.9rem; }
        .order-total-line { display: flex; justify-content: space-between; padding: 15px 0 5px; font-weight: 700; font-size: 1.1rem; border-top: 2px solid #E5A9E0; margin-top: 10px; color: #A0D2EB; }
        .place-order-btn { display: block; width: 100%; padding: 15px; background: #FFD1DC; border: 2px solid #E5A9E0; font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1rem; text-transform: uppercase; cursor: pointer; margin-top: 20px; transition: all 0.3s ease; letter-spacing: 1px; }
        .place-order-btn:hover { background: #E5A9E0; letter-spacing: 3px; box-shadow: 0 0 20px rgba(229,169,224,0.5); }
        .success-window { text-align: center; padding: 50px 30px; }
        .success-icon { font-size: 3rem; margin-bottom: 15px; }
        .success-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; }
        .success-sub { color: #999; font-size: 0.85rem; margin-bottom: 25px; }
        .back-shop-btn { display: inline-block; padding: 12px 30px; background: #A0D2EB; color: white !important; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .error-msg { background: #ffe0e0; border: 1px solid #ffb3b3; color: #c00; padding: 10px 15px; margin-bottom: 15px; font-size: 0.85rem; font-weight: 600; }
        @media (max-width: 768px) { .checkout-layout { grid-template-columns: 1fr; padding: 20px; } }
    </style>
</head>
<body>

<div class="marquee">
    <div class="marquee-content">
        <span>✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ FREE WORLDWIDE SHIPPING ✦</span>
        <span>✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦ Y2K DREAMY VIBES ✦ FREE WORLDWIDE SHIPPING ✦</span>
    </div>
</div>

<nav class="navbar">
    <div class="logo">✦ DREAMY ✦</div>
        <ul class="nav-links">
            <li><a href="home.php" ><i class="bi bi-house-heart"></i> Home</a></li>
            <li><a href="tops.php" >Tops</a></li>
            <li><a href="bottoms.php" >Bottoms</a></li>
            <li><a href="cart.php" class="cart-badge"><i class="bi bi-bag-heart"></i> Cart<span class="cart-badge-count" id="cartCount">0</span></a></li>
            <li><a href="orders.php" ><i class="bi bi-clock-history"></i> Orders</a></li>
            <li class="account-btn"><a href="account.php" ><i class="bi bi-person-circle"></i> Account</a></li>
        </ul>
</nav>

<?php if ($order_placed): ?>
<div style="max-width:600px; margin:60px auto; padding:0 20px;">
    <div class="checkout-section">
        <div class="window-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>order_confirmed.exe</span>
        </div>
        <div class="success-window">
            <div class="success-icon">✦</div>
            <div class="success-title">Order Placed!</div>
            <div class="success-sub">Thank you, <?php echo htmlspecialchars($user['username']); ?>!<br>Your dreamy haul is on its way ✨</div>
            <a href="home.php" class="back-shop-btn">CONTINUE SHOPPING</a>
        </div>


<?php else: ?>
<div class="checkout-layout">
    <div>
        <div class="checkout-section">
            <div class="window-header">
                <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                <span>delivery_details.exe</span>
            </div>
            <div class="checkout-body">
                <h2 style="margin-bottom:20px; text-transform:uppercase; font-size:1rem;">Shipping Info</h2>
                <?php if ($order_error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($order_error); ?></div>
                <?php endif; ?>
                <form method="POST" action="checkout.php">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" class="prefilled" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="prefilled" readonly>
                    </div>
                    <div class="form-group">
                        <label>Delivery Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Enter your full delivery address" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>" placeholder="Enter your phone number" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <input type="text" value="Cash on Delivery" class="prefilled" readonly>
                    </div>
                    <?php
                    $purchasable_count = count(array_filter($items, fn($i) => !$i['is_out_of_stock']));
                    if ($purchasable_count > 0): ?>
                    <button type="submit" name="place_order" class="place-order-btn">
                        ✦ CONFIRM ORDER (<?php echo $purchasable_count; ?> item<?php echo $purchasable_count > 1 ? 's' : ''; ?>) ✦
                    </button>
                    <?php else: ?>
                    <button type="button" class="place-order-btn" disabled style="opacity:0.45;cursor:not-allowed;background:#ccc;">
                        ✕ All items are sold out — go back to shop
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="checkout-section">
            <div class="window-header">
                <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                <span>order_summary.exe</span>
            </div>
            <div class="checkout-body">
                <h2 style="margin-bottom:15px; text-transform:uppercase; font-size:1rem;">Your Items</h2>
                <?php if (empty($items)): ?>
                    <p style="color:#E5A9E0; text-align:center; padding:30px 0;">Your cart is empty!</p>
                <?php else: ?>
                    <?php
                    $has_soldout_display = false;
                    foreach ($items as $item): 
                        if ($item['is_out_of_stock']) $has_soldout_display = true;
                    endforeach;
                    if ($has_soldout_display): ?>
                        <div style="background:#ffe5e5;border:2px solid #e05555;border-radius:7px;padding:11px 14px;margin-bottom:14px;font-size:0.82rem;font-weight:700;color:#b00;">
                            ✕ Some items below are sold out and will NOT be included in your order.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                    <div class="checkout-item" style="<?php echo $item['is_out_of_stock'] ? 'opacity:0.45; position:relative;' : ''; ?>">
                        <img src="<?php echo $item['img_url']; ?>" alt="<?php echo $item['name']; ?>"
                             style="<?php echo $item['is_out_of_stock'] ? 'filter:grayscale(1);' : ''; ?>">
                        <div style="flex:1;">
                            <div class="checkout-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <?php if ($item['is_out_of_stock']): ?>
                                <div style="color:#e05555;font-weight:700;font-size:0.78rem;">✕ SOLD OUT — will not be ordered</div>
                            <?php else: ?>
                                <div class="checkout-item-meta">Qty: <?php echo $item['display_qty']; ?> × $<?php echo number_format($item['price'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="checkout-item-price" style="<?php echo $item['is_out_of_stock'] ? 'text-decoration:line-through;color:#bbb;' : ''; ?>">
                            $<?php echo number_format($item['subtotal'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="order-summary-line"><span>Subtotal</span><span>$<?php echo number_format($grand_total, 2); ?></span></div>
                    <div class="order-summary-line"><span>Shipping</span><span style="color:#A0D2EB; font-weight:600;">FREE ✦</span></div>
                    <div class="order-total-line"><span>TOTAL</span><span>$<?php echo number_format($grand_total, 2); ?></span></div>
                <?php endif; ?>
                <a href="cart.php" style="display:block; text-align:center; margin-top:20px; font-size:0.75rem; color:#999; text-decoration:underline;">← Edit Cart</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
