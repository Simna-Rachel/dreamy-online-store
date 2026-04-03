<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pid) { header("Location: home.php"); exit(); }

$result = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = $pid");
$product = mysqli_fetch_assoc($result);
if (!$product) { header("Location: home.php"); exit(); }

// AI-written descriptions per product name keywords
function getDreamyDescription($name, $price, $stock) {
    $name_lower = strtolower($name);
    
    $descriptions = [
        'cyber cowgirl' => ["Saddle up, dreamgirl. These statement flares blend wild-west energy with Y2K cyber glam — think rodeo rave meets digital frontier. The wide flare silhouette elongates your legs while the bold cut screams main character.", "Perfect for: festivals, night outs, making everyone stare.", "Pair with: a cropped mesh top and chunky boots."],
        'mesh' => ["Sheer magic. This mesh top layers over any fit for instant Y2K cred — wear it alone for max impact or over a bralette for that layered look the internet is obsessed with.", "Perfect for: concerts, parties, chaotic good outfits.", "Pair with: high-waist flares or a mini skirt."],
        'leopard' => ["Retro girl, this one's for you. This leopard print channels peak 2000s fashion with a modern cut that hits just right. Soft, stretchy, and impossibly cute.", "Perfect for: brunch, shopping trips, wherever you want to be noticed.", "Pair with: solid bottoms to let the print do the talking."],
        'cargo' => ["Utility meets Y2K. This jacket layers over everything — from slip dresses to baggy jeans. The oversized silhouette is giving gorpcore-meets-cyber aesthetic and we are so here for it.", "Perfect for: everyday layering, outdoor adventures, effortless cool.", "Pair with: anything. Seriously, anything."],
        'bodysuit' => ["Baby pink and cloud-soft, this bodysuit is your new go-to. The fitted silhouette tucks perfectly into high-waist skirts and jeans for that clean, polished Y2K look.", "Perfect for: date nights, dressed-up casual, tucked-in perfection.", "Pair with: wide-leg trousers or a denim mini."],
        'holographic' => ["Future girl vibes only. This holographic crop catches every light source in the room and turns it into your personal spotlight. Wear it to be seen — because you will be.", "Perfect for: night events, festivals, anywhere with good lighting.", "Pair with: leather trousers or sparkly skirts."],
        'denim mini' => ["The classic, reimagined. This denim mini skirt has the lived-in feel of vintage denim with a flattering modern cut. Short enough to make a statement, comfy enough to wear all day.", "Perfect for: everything. The denim mini is a lifestyle.", "Pair with: oversized tees or cropped tanks."],
        'butterfly' => ["Floaty, flared, and absolutely dreamy. These butterfly flare jeans have the wide-leg silhouette of Y2K icons with a modern comfort fit. The flutter at the hem is giving runway energy.", "Perfect for: everyday wear, photo shoots, living your best life.", "Pair with: a fitted crop top or bodysuit."],
        'ruffled' => ["Ruffles and denim — two things that never go out of style, now together. This skirt adds texture and playfulness to any look without trying too hard.", "Perfect for: brunch, girls' trips, feminine-edge outfits.", "Pair with: a tucked-in blouse or cropped knit."],
        'blue top' => ["Sassy, vibrant, and made to stand out. This blue top has a bold color that pops against any skin tone and a cut that flatters every body type.", "Perfect for: date nights, going out, making an entrance.", "Pair with: white jeans or a flowing skirt."],
        'twilight' => ["Moody, mystical, and made for dreamers. This outfit captures that golden-hour energy — soft tones that glow under any lighting, day or night.", "Perfect for: photoshoots, evening events, channeling your inner romantic.", "Pair with: delicate jewelry and soft makeup."],
        'bundle' => ["The ultimate dreamy starter pack. This curated bundle gives you the full Y2K look in one fell swoop — coordinated, effortless, and absolutely iconic.", "Perfect for: gifting, building your dream wardrobe, full look moments.", "Pair with: your favourite accessories and a confident attitude."],
        'butterfly set' => ["Coordinated and cute — this butterfly set does the hard work for you. Wear it together for a full look or mix and match with your wardrobe staples.", "Perfect for: content creation, special occasions, effortless outfits.", "Pair with: white sneakers or strappy heels."],
        'party' => ["This outfit was made for the night. Bold, festive, and built to dance in — the Y2K Party Outfit brings the energy before you even walk in the door.", "Perfect for: birthdays, nights out, celebrations of all kinds.", "Pair with: platform shoes and your most chaotic accessories."],
        'gyaru' => ["Channel the legendary Gyaru queen aesthetic — maximalist, unapologetic, and dripping in confidence. This collection pays homage to the Japanese street style that defined an era.", "Perfect for: serving looks, special events, being unforgettable.", "Pair with: sky-high platforms, layered jewelry, and bold lashes."],
    ];

    foreach ($descriptions as $keyword => $desc) {
        if (str_contains($name_lower, $keyword)) {
            return $desc;
        }
    }

    // Generic fallback
    return [
        "A dreamy Y2K staple that belongs in every wardrobe. Clean lines, perfect fit, and that undeniable Dreamy aesthetic make this piece a must-have for the season.",
        "Perfect for: any occasion you want to look effortlessly cool.",
        "Pair with: your favourite Dreamy pieces for a full look."
    ];
}

$desc = getDreamyDescription($product['name'], $product['price'], $product['stock_count']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> — Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="home.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .product-detail-layout {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
        }

        /* IMAGE SIDE */
        .product-image-panel {
            position: sticky;
            top: 20px;
        }
        .product-image-window {
            background: white;
            border: 3px solid #A0D2EB;
            box-shadow: 8px 8px 0 #E5A9E0;
            border-radius: 10px;
            overflow: hidden;
        }
        .product-image-window .win-header {
            background: #f8f9fa;
            border-bottom: 2px solid #A0D2EB;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .product-main-img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            display: block;
        }
        .product-main-img-placeholder {
            width: 100%;
            aspect-ratio: 3/4;
            background: linear-gradient(135deg, #FDEEF4, #E5F4FB);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        /* INFO SIDE */
        .product-info-panel { padding: 10px 0; }

        .product-category-tag {
            display: inline-block;
            background: #FDEEF4;
            border: 1px solid #E5A9E0;
            color: #E5A9E0;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 12px;
            margin-bottom: 14px;
        }

        .product-detail-name {
            font-size: 2rem;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 10px;
            color: #2B2B2B;
        }

        .product-detail-price {
            font-size: 1.6rem;
            font-weight: 700;
            color: #A0D2EB;
            margin-bottom: 20px;
        }

        .stock-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .stock-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
        }
        .in-stock .stock-dot { background: #A0D2EB; }
        .in-stock { color: #A0D2EB; }
        .low-stock .stock-dot { background: #ffc107; }
        .low-stock { color: #856404; }
        .out-stock .stock-dot { background: #E5A9E0; }
        .out-stock { color: #999; }

        /* DESCRIPTION BOX */
        .description-window {
            background: #FDEEF4;
            border: 2px solid #E5A9E0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .description-window .desc-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #A0D2EB;
            margin-bottom: 10px;
        }
        .description-main {
            font-size: 0.9rem;
            line-height: 1.65;
            color: #444;
            margin-bottom: 12px;
        }
        .description-tags {
            font-size: 0.78rem;
            color: #999;
            line-height: 1.7;
        }
        .description-tags span {
            display: block;
        }
        .description-tags strong {
            color: #E5A9E0;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }
        .btn-cart {
            display: block;
            width: 100%;
            padding: 15px;
            background: #FFD1DC;
            border: 2px solid #E5A9E0;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: #2B2B2B;
            transition: all 0.2s;
        }
        .btn-cart:hover { background: #E5A9E0; letter-spacing: 3px; }
        .btn-buy {
            display: block;
            width: 100%;
            padding: 15px;
            background: #A0D2EB;
            border: 2px solid #7bbbd8;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.2s;
        }
        .btn-buy:hover { background: #7bbbd8; letter-spacing: 3px; }
        .btn-disabled {
            display: block;
            width: 100%;
            padding: 15px;
            background: #f0f0f0;
            border: 2px solid #ddd;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            color: #bbb;
            cursor: not-allowed;
        }

        /* DETAILS STRIP */
        .detail-strip {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .detail-chip {
            background: white;
            border: 2px solid #FDEEF4;
            padding: 10px 14px;
            font-size: 0.72rem;
        }
        .detail-chip .chip-label { color: #A0D2EB; font-weight: 700; text-transform: uppercase; font-size: 0.58rem; letter-spacing: 1px; margin-bottom: 3px; }
        .detail-chip .chip-val { font-weight: 700; color: #2B2B2B; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #A0D2EB;
            text-decoration: none;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .back-link:hover { color: #E5A9E0; }

        @media (max-width: 768px) {
            .product-detail-layout { grid-template-columns: 1fr; padding: 20px; gap: 25px; }
            .product-image-panel { position: static; }
        }
    </style>
</head>
<body>

<div class="marquee">
    <div class="marquee-content">
        <span>✦ NEW DROP LIVE ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦ NEW DROP LIVE ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦</span>
        <span>✦ NEW DROP LIVE ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦ NEW DROP LIVE ✦ FREE WORLDWIDE SHIPPING ✦ SECURE CHECKOUT ✦</span>
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

<div class="product-detail-layout">

    <!-- LEFT: IMAGE -->
    <div class="product-image-panel">
        <a href="javascript:history.back()" class="back-link">← Back</a>
        <div class="product-image-window">
            <div class="win-header">
                <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
                <span><?php echo strtolower(str_replace(' ', '_', $product['name'])); ?>.exe</span>
            </div>
            <?php if ($product['img_url']): ?>
                <img src="<?php echo htmlspecialchars($product['img_url']); ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="product-main-img"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="product-main-img-placeholder" style="display:none;">✦</div>
            <?php else: ?>
                <div class="product-main-img-placeholder">✦</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: INFO -->
    <div class="product-info-panel">

        <div class="product-category-tag"><?php echo htmlspecialchars($product['category_name']); ?></div>

        <h1 class="product-detail-name"><?php echo htmlspecialchars($product['name']); ?></h1>
        <div class="product-detail-price">$<?php echo number_format($product['price'], 2); ?></div>

        <?php
        $stock = $product['stock_count'];
        if ($stock == 0): ?>
            <div class="stock-indicator out-stock"><span class="stock-dot"></span> Sold Out</div>
        <?php elseif ($stock <= 3): ?>
            <div class="stock-indicator low-stock"><span class="stock-dot"></span> Only <?php echo $stock; ?> left — grab it fast!</div>
        <?php else: ?>
            <div class="stock-indicator in-stock"><span class="stock-dot"></span> In Stock (<?php echo $stock; ?> available)</div>
        <?php endif; ?>

        <!-- DESCRIPTION -->
        <div class="description-window">
            <div class="desc-label">✦ product_description.txt</div>
            <p class="description-main"><?php echo htmlspecialchars($desc[0]); ?></p>
            <div class="description-tags">
                <span><strong>✦</strong> <?php echo htmlspecialchars($desc[1]); ?></span>
                <span><strong>✦</strong> <?php echo htmlspecialchars($desc[2]); ?></span>
            </div>
        </div>

        <!-- DETAIL CHIPS -->
        <div class="detail-strip">
            <div class="detail-chip">
                <div class="chip-label">Category</div>
                <div class="chip-val"><?php echo ucfirst($product['category_name']); ?></div>
            </div>
            <div class="detail-chip">
                <div class="chip-label">Price</div>
                <div class="chip-val">$<?php echo number_format($product['price'], 2); ?></div>
            </div>
            <div class="detail-chip">
                <div class="chip-label">Shipping</div>
                <div class="chip-val">FREE ✦</div>
            </div>
            <div class="detail-chip">
                <div class="chip-label">Payment</div>
                <div class="chip-val">Cash on Delivery</div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-buttons">
            <?php if ($stock > 0): ?>
                <a href="add_to_cart.php?id=<?php echo $product['product_id']; ?>" class="btn-cart"><i class="bi bi-bag-plus"></i> Add to Cart</a>
                <a href="add_to_cart.php?id=<?php echo $product['product_id']; ?>&buy=1" class="btn-buy"><i class="bi bi-lightning-charge-fill"></i> Buy Now</a>
            <?php else: ?>
                <span class="btn-disabled">Out of Stock</span>
            <?php endif; ?>
        </div>

    </div>
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

    // Show toast if added=success parameter exists
    (function() {
        var url = new URL(window.location);
        var added = url.searchParams.get('added');
        var error = url.searchParams.get('error');
        
        if (added === 'success') {
            showToast('✨ Product added to cart!', 'success');
            // Update cart count after adding
            updateCartCount();
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (error === 'out_of_stock') {
            showToast('Sorry, this item is out of stock!', 'error');
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    })();

    // Load cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>
