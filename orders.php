<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch user info
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id"));

// Fetch all confirmed/shipped/delivered/cancelled orders for this user, newest first
$orders_q = mysqli_query($conn, "
    SELECT o.order_id, o.order_date, o.total_price, o.status,
           o.delivery_status, o.payment_status, o.delivery_date
    FROM orders o
    WHERE o.user_id = $user_id AND o.status != 'pending'
    ORDER BY o.order_date DESC
");

// Build orders array — items fetched from purchased_items using order_id (reliable!)
$orders = [];
while ($o = mysqli_fetch_assoc($orders_q)) {
    $oid = intval($o['order_id']);

    // PRIMARY: fetch from purchased_items linked by order_id
    $items_q = mysqli_query($conn, "
        SELECT p.name, p.img_url, pi.quantity, pi.price_at_purchase,
               pi.total_price
        FROM purchased_items pi
        JOIN products p ON pi.product_id = p.product_id
        WHERE pi.order_id = $oid
    ");
    $o['items'] = [];
    while ($item = mysqli_fetch_assoc($items_q)) {
        $o['items'][] = $item;
    }

    // FALLBACK: if order_id column wasn't set yet (old orders before migration),
    // try order_items table (items still in cart that weren't cleared)
    if (empty($o['items'])) {
        $items_q2 = mysqli_query($conn, "
            SELECT p.name, p.img_url, oi.quantity,
                   p.price AS price_at_purchase,
                   (oi.quantity * p.price) AS total_price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = $oid
        ");
        while ($item = mysqli_fetch_assoc($items_q2)) {
            $o['items'][] = $item;
        }
    }

    $orders[] = $o;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="home.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .orders-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 50px 30px;
        }

        .orders-hero {
            text-align: center;
            margin-bottom: 45px;
        }

        .orders-hero h1 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .orders-hero p {
            color: #999;
            font-size: 0.85rem;
        }

        /* ORDER CARD */
        .order-card {
            background: white;
            border: 1.5px solid rgba(160,210,235,0.5);
            box-shadow: 5px 5px 0 rgba(229,169,224,0.3);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 7px 9px 0 rgba(229,169,224,0.4);
        }

        .order-header {
            background: linear-gradient(90deg, #f8f9fa, #fdf2f7);
            border-bottom: 1.5px solid rgba(160,210,235,0.35);
            padding: 14px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-id {
            font-size: 0.72rem;
            font-weight: 700;
            color: #A0D2EB;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-date {
            font-size: 0.75rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-confirmed { background: #d4edda; color: #155724; }
        .status-shipped   { background: #d1ecf1; color: #0c5460; }
        .status-cancelled  { background: #f8d7da; color: #721c24; }
        .status-delivered  { background: #c3e6cb; color: #0a3622; }
        .status-pending   { background: #fff3cd; color: #856404; }

        .order-total-badge {
            font-weight: 700;
            font-size: 1rem;
            color: #A0D2EB;
        }

        /* ITEMS LIST */
        .order-items {
            padding: 18px 22px;
        }

        .order-item-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(229,169,224,0.3);
        }

        .order-item-row:last-child { border-bottom: none; }

        .order-item-img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border: 1.5px solid rgba(160,210,235,0.4);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .order-item-img-placeholder {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #FDEEF4, #E5F4FB);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .order-item-name {
            font-weight: 600;
            font-size: 0.88rem;
            flex: 1;
        }

        .order-item-meta {
            font-size: 0.75rem;
            color: #999;
            margin-top: 2px;
        }

        .order-item-price {
            font-weight: 700;
            font-size: 0.88rem;
            color: #A0D2EB;
            white-space: nowrap;
        }

        /* EMPTY STATE */
        .empty-orders {
            text-align: center;
            padding: 80px 30px;
            color: #ccc;
        }

        .empty-orders i {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 18px;
            color: #E5A9E0;
        }

        .empty-orders h3 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.3rem;
            color: #bbb;
            margin-bottom: 10px;
        }

        .empty-orders p {
            font-size: 0.85rem;
            margin-bottom: 25px;
        }

        /* BACK LINK */
        .page-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #A0D2EB;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
            transition: color 0.2s;
        }
        .page-back:hover { color: #E5A9E0; }

        .shop-now-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: #FFD1DC;
            border: 2px solid #E5A9E0;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: #2B2B2B;
            transition: all 0.25s;
        }
        .shop-now-btn:hover { background: #E5A9E0; letter-spacing: 2px; }

        @media (max-width: 600px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .orders-container { padding: 30px 16px; }
        }
    </style>
</head>
<body>

<div class="marquee">
    <div class="marquee-content">
        <span>✦ MY ORDERS ✦ DREAMY Y2K ✦ FREE WORLDWIDE SHIPPING ✦ MY ORDERS ✦ DREAMY Y2K ✦ FREE WORLDWIDE SHIPPING ✦</span>
        <span>✦ MY ORDERS ✦ DREAMY Y2K ✦ FREE WORLDWIDE SHIPPING ✦ MY ORDERS ✦ DREAMY Y2K ✦ FREE WORLDWIDE SHIPPING ✦</span>
    </div>
</div>

<nav class="navbar">
    <div class="logo">✦ Dreamy ✦</div>
    <ul class="nav-links">
        <li><a href="home.php"><i class="bi bi-house-heart"></i> Home</a></li>
        <li><a href="tops.php">Tops</a></li>
        <li><a href="bottoms.php">Bottoms</a></li>
        <li><a href="cart.php"><i class="bi bi-bag-heart"></i> Cart</a></li>
        <li><a href="orders.php" class="active"><i class="bi bi-clock-history"></i> Orders</a></li>
        <li class="account-btn"><a href="account.php"><i class="bi bi-person-circle"></i> Account</a></li>
    </ul>
</nav>

<div class="orders-container">

    <a href="account.php" class="page-back">
        <i class="bi bi-arrow-left"></i> Back to Account
    </a>

    <div class="orders-hero">
        <h1>My Orders</h1>
        <p>Hello <?php echo htmlspecialchars($user['username']); ?>! Here's your full order history ✦</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <i class="bi bi-bag-x"></i>
            <h3>No orders yet, dreamgirl!</h3>
            <p>Looks like you haven't placed any orders. Time to fix that!</p>
            <a href="home.php" class="shop-now-btn">
                <i class="bi bi-stars"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $o):
            $status          = $o['status'];
            $delivery_status = $o['delivery_status'] ?? 'pending';
            $payment_status  = $o['payment_status']  ?? 'pending';
            $items           = $o['items'];
            $order_total     = $o['total_price'] > 0
                ? $o['total_price']
                : array_sum(array_column($items, 'total_price'));

            // Order status icon map
            $status_icons = [
                'confirmed' => 'bi-check-circle-fill',
                'shipped'   => 'bi-truck',
                'cancelled' => 'bi-x-circle-fill',
                'delivered' => 'bi-bag-check-fill',
                'pending'   => 'bi-clock',
            ];
            $icon_class = $status_icons[$status] ?? 'bi-check-circle-fill';

            // Delivery step map for the progress bar
            $delivery_steps = [
                'pending'    => 0,
                'in_transit' => 1,
                'delivered'  => 2,
                'failed'     => -1,
            ];
            $delivery_step = $delivery_steps[$delivery_status] ?? 0;
        ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-header-left">
                    <span class="order-id">
                        <i class="bi bi-receipt"></i>
                        Order #<?php echo str_pad($o['order_id'], 4, '0', STR_PAD_LEFT); ?>
                    </span>
                    <span class="order-date">
                        <i class="bi bi-calendar3"></i>
                        <?php echo date('d M Y, h:i A', strtotime($o['order_date'])); ?>
                    </span>
                </div>
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="status-badge status-<?php echo $status; ?>">
                        <i class="bi <?php echo $icon_class; ?>"></i>
                        <?php echo ucfirst($status); ?>
                    </span>
                    <span class="order-total-badge">$<?php echo number_format($order_total, 2); ?></span>
                </div>
            </div>

            <?php if ($status === 'shipped' || $status === 'delivered' || $status === 'confirmed'): ?>
            <!-- DELIVERY & PAYMENT INFO SECTION -->
            <div style="padding:14px 22px; background:linear-gradient(90deg,#f0fbff,#fdf9ff); border-bottom:1.5px solid rgba(160,210,235,0.3);">
                <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">

                    <!-- COD Label (always shown) -->
                    <div>
                        <div style="font-size:0.6rem; font-weight:700; color:#A0D2EB; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Payment Method</div>
                        <span style="background:#fdeef4; color:#c05080; padding:4px 14px; border-radius:20px; font-size:0.72rem; font-weight:700; border:1px solid #E5A9E0;">
                            💳 CASH ON DELIVERY
                        </span>
                    </div>

                    <?php if ($status === 'shipped' || $status === 'delivered'): ?>
                    <!-- Delivery Status Badge -->
                    <div>
                        <div style="font-size:0.6rem; font-weight:700; color:#A0D2EB; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">📦 Delivery Status</div>
                        <?php
                        $del_badges = [
                            'pending'    => ['bg'=>'#fff3cd','color'=>'#856404','icon'=>'⏳','label'=>'Pending'],
                            'in_transit' => ['bg'=>'#d1ecf1','color'=>'#0c5460','icon'=>'🚚','label'=>'In Transit'],
                            'delivered'  => ['bg'=>'#c3e6cb','color'=>'#0a3622','icon'=>'✅','label'=>'Delivered'],
                            'failed'     => ['bg'=>'#f8d7da','color'=>'#721c24','icon'=>'❌','label'=>'Delivery Failed'],
                        ];
                        $db = $del_badges[$delivery_status] ?? $del_badges['pending'];
                        ?>
                        <span style="background:<?php echo $db['bg']; ?>; color:<?php echo $db['color']; ?>; padding:4px 14px; border-radius:20px; font-size:0.72rem; font-weight:700;">
                            <?php echo $db['icon'] . ' ' . $db['label']; ?>
                        </span>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <div style="font-size:0.6rem; font-weight:700; color:#A0D2EB; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">💰 Payment</div>
                        <?php if ($payment_status === 'received'): ?>
                            <span style="background:#c3e6cb; color:#0a3622; padding:4px 14px; border-radius:20px; font-size:0.72rem; font-weight:700;">💚 Received</span>
                        <?php else: ?>
                            <span style="background:#fff3cd; color:#856404; padding:4px 14px; border-radius:20px; font-size:0.72rem; font-weight:700;">⏳ Pending</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Amount due note -->
                    <?php if ($payment_status !== 'received' && $status !== 'cancelled'): ?>
                    <div style="margin-left:auto; background:#fff8f0; border:1px solid #ffd08a; border-radius:8px; padding:8px 14px; font-size:0.75rem; color:#7a4f00;">
                        💼 <strong>Amount due upon delivery:</strong> $<?php echo number_format($order_total, 2); ?>
                    </div>
                    <?php elseif ($payment_status === 'received'): ?>
                    <div style="margin-left:auto; font-size:0.75rem; color:#0a3622;">
                        ✓ Payment of <strong>$<?php echo number_format($order_total, 2); ?></strong> collected
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($delivery_status === 'in_transit' || $status === 'shipped'): ?>
                <!-- DELIVERY PROGRESS BAR -->
                <div style="margin-top:16px; padding:0 10px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; position:relative;">
                        <div style="position:absolute; top:12px; left:12%; right:12%; height:3px; background:#E5A9E0; z-index:0;"></div>
                        <div style="position:absolute; top:12px; left:12%; width:<?php echo $delivery_step >= 1 ? '76%' : '0'; ?>; height:3px; background:#A0D2EB; z-index:1;"></div>
                        <?php
                        $steps = [
                            ['icon'=>'📦','label'=>'Shipped'],
                            ['icon'=>'🚚','label'=>'In Transit'],
                            ['icon'=>'🏠','label'=>'Delivered'],
                        ];
                        foreach ($steps as $si => $step):
                            $active = $delivery_step >= $si;
                        ?>
                        <div style="display:flex; flex-direction:column; align-items:center; z-index:2; flex:1;">
                            <div style="width:26px; height:26px; border-radius:50%; background:<?php echo $active ? '#A0D2EB' : '#E5A9E0'; ?>; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">
                                <?php echo $step['icon']; ?>
                            </div>
                            <div style="font-size:0.62rem; color:<?php echo $active ? '#0c5460' : '#999'; ?>; font-weight:700; margin-top:4px;"><?php echo $step['label']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ITEMS LIST -->
            <div class="order-items">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                    <div class="order-item-row">
                        <?php if (!empty($item['img_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['img_url']); ?>"
                                 class="order-item-img"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="order-item-img-placeholder" style="display:none;">✦</div>
                        <?php else: ?>
                            <div class="order-item-img-placeholder">✦</div>
                        <?php endif; ?>

                        <div style="flex:1;">
                            <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="order-item-meta">
                                Qty: <?php echo $item['quantity']; ?> &times;
                                $<?php echo number_format($item['price_at_purchase'], 2); ?>
                            </div>
                        </div>

                        <div class="order-item-price">
                            $<?php echo number_format($item['total_price'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; color:#ccc; padding:20px; font-size:0.85rem;">
                        ✦ No items found for this order
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

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
    </script>
</body>
</html>
