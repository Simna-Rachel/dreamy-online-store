<?php
session_start();
include('db_config.php');

// Guard: only admins can access
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = htmlspecialchars($_SESSION['admin_name']);

// ── HANDLE ACTIONS ──────────────────────────────────────────

// 1. ADD NEW PRODUCT
if (isset($_POST['add_product'])) {
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $price    = floatval($_POST['price']);
    $stock    = intval($_POST['stock_count']);
    $cat      = intval($_POST['category_id']);
    $img      = mysqli_real_escape_string($conn, $_POST['img_url']);
    $sql = "INSERT INTO products (name, price, stock_count, category_id, img_url)
            VALUES ('$name', $price, $stock, $cat, '$img')";
    mysqli_query($conn, $sql);
    header("Location: admin_dashboard.php?tab=products&msg=Product+added!");
    exit();
}

// 2. DELETE PRODUCT
if (isset($_GET['delete_product'])) {
    $pid = intval($_GET['delete_product']);
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $pid");
    header("Location: admin_dashboard.php?msg=Product+deleted.");
    exit();
}

// 3. UPDATE STOCK
if (isset($_POST['update_stock'])) {
    $pid   = intval($_POST['product_id']);
    $stock = intval($_POST['stock_count']);
    mysqli_query($conn, "UPDATE products SET stock_count = $stock WHERE product_id = $pid");
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
    header("Location: admin_dashboard.php?msg=Stock+updated.&tab=" . $tab . "#product-row-" . $pid);
    exit();
}

// 3b. EDIT PRODUCT (name, price, img)
if (isset($_POST['edit_product'])) {
    $pid   = intval($_POST['product_id']);
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $price = floatval($_POST['price']);
    $img   = mysqli_real_escape_string($conn, $_POST['img_url']);
    $cat   = intval($_POST['category_id']);
    mysqli_query($conn, "UPDATE products SET name='$name', price=$price, img_url='$img', category_id=$cat WHERE product_id=$pid");
    header("Location: admin_dashboard.php?msg=Product+updated!&tab=products");
    exit();
}

// 4. UPDATE ORDER STATUS (Using CURSOR pattern)
if (isset($_POST['update_order_status'])) {
    $oid        = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    // CURSOR pattern: Fetch current order status + delivery info
    $cur_result = mysqli_query($conn, "SELECT status, delivery_status, payment_status, user_id, order_date FROM orders WHERE order_id = $oid");
    $cur_row    = mysqli_fetch_assoc($cur_result);
    $cur_status = $cur_row ? $cur_row['status'] : '';

    // Allowed transitions (simple state machine):
    // confirmed → shipped, confirmed → cancelled
    // shipped   → delivered, cancelled
    // delivered and cancelled are locked
    $allowed = false;
    if ($cur_status === 'confirmed' && ($new_status === 'shipped' || $new_status === 'cancelled')) {
        $allowed = true;
    } elseif ($cur_status === 'shipped' && ($new_status === 'delivered' || $new_status === 'cancelled')) {
        $allowed = true;
    }

    if ($allowed) {
        if ($new_status === 'shipped') {
            // When shipped: update order status AND set delivery_status = in_transit
            mysqli_query($conn, "UPDATE orders SET status = 'shipped', delivery_status = 'in_transit' WHERE order_id = $oid");
        } elseif ($new_status === 'delivered') {
            // When delivered: update order + delivery status + set delivery_date
            mysqli_query($conn, "UPDATE orders SET status = 'delivered', delivery_status = 'delivered', delivery_date = NOW() WHERE order_id = $oid");
        } elseif ($new_status === 'cancelled') {
            // When cancelled: also mark delivery as failed
            mysqli_query($conn, "UPDATE orders SET status = 'cancelled', delivery_status = 'failed' WHERE order_id = $oid");
        } else {
            mysqli_query($conn, "UPDATE orders SET status = '$new_status' WHERE order_id = $oid");
        }
        header("Location: admin_dashboard.php?msg=Order+status+updated.&tab=orders");
    } else {
        header("Location: admin_dashboard.php?msg=Invalid+status+change.&tab=orders");
    }
    exit();
}

// 4b. UPDATE DELIVERY STATUS (separate delivery management)
if (isset($_POST['update_delivery_status'])) {
    $oid            = intval($_POST['order_id']);
    $new_delivery   = mysqli_real_escape_string($conn, $_POST['delivery_status']);

    // CURSOR pattern: Fetch current order
    $cur_result = mysqli_query($conn, "SELECT status, delivery_status, payment_status FROM orders WHERE order_id = $oid");
    $cur_row    = mysqli_fetch_assoc($cur_result);
    $cur_delivery = $cur_row ? $cur_row['delivery_status'] : '';
    $cur_status   = $cur_row ? $cur_row['status'] : '';

    // Only allow delivery status update for shipped orders
    $valid_deliveries = ['pending', 'in_transit', 'delivered', 'failed'];
    $allowed = in_array($new_delivery, $valid_deliveries) && $cur_status === 'shipped';

    if ($allowed) {
        if ($new_delivery === 'delivered') {
            // Auto-update order status too when delivery is marked delivered
            mysqli_query($conn, "UPDATE orders SET delivery_status = 'delivered', status = 'delivered', delivery_date = NOW() WHERE order_id = $oid");
        } elseif ($new_delivery === 'failed') {
            mysqli_query($conn, "UPDATE orders SET delivery_status = 'failed', status = 'cancelled' WHERE order_id = $oid");
        } else {
            mysqli_query($conn, "UPDATE orders SET delivery_status = '$new_delivery' WHERE order_id = $oid");
        }
        header("Location: admin_dashboard.php?msg=Delivery+status+updated.&tab=orders");
    } else {
        header("Location: admin_dashboard.php?msg=Invalid+delivery+status+change.&tab=orders");
    }
    exit();
}

// 4c. UPDATE PAYMENT STATUS (only after delivered)
if (isset($_POST['update_payment_status'])) {
    $oid         = intval($_POST['order_id']);
    $new_payment = mysqli_real_escape_string($conn, $_POST['payment_status']);

    // CURSOR pattern: check current state
    $cur_result = mysqli_query($conn, "SELECT status, payment_status FROM orders WHERE order_id = $oid");
    $cur_row    = mysqli_fetch_assoc($cur_result);
    $cur_status = $cur_row ? $cur_row['status'] : '';

    // Payment can only be updated after delivered
    if ($cur_status === 'delivered' && $new_payment === 'received') {
        // Calculate real total from purchased_items for this order
        $total_result = mysqli_query($conn, "SELECT COALESCE(SUM(total_price), 0) AS t FROM purchased_items WHERE order_id = $oid");
        $real_total   = floatval(mysqli_fetch_assoc($total_result)['t']);
        // Update payment status AND fix the total_price in one query
        mysqli_query($conn, "UPDATE orders SET payment_status = 'received', total_price = $real_total WHERE order_id = $oid");
        header("Location: admin_dashboard.php?msg=Payment+marked+as+received.&tab=orders");
    } else {
        header("Location: admin_dashboard.php?msg=Payment+can+only+be+collected+after+delivery.&tab=orders");
    }
    exit();
}

// 5. LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// ── FETCH DATA ───────────────────────────────────────────────
$products   = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id ORDER BY p.category_id, p.name");
$categories = mysqli_query($conn, "SELECT * FROM categories");

// Orders with user info (exclude pending/empty carts)
$orders = mysqli_query($conn, "
    SELECT o.order_id, o.status, o.total_price, o.order_date AS created_at,
           o.delivery_status, o.payment_status, o.delivery_date,
           u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.status != 'pending'
    ORDER BY o.order_date DESC
");

// Stats
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status != 'pending'"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_price),0) as t FROM orders WHERE status='delivered' AND payment_status='received'"))['t'];

// Stock alerts
$alerts = mysqli_query($conn, "SELECT * FROM stock_alerts ORDER BY alerted_at DESC LIMIT 10");

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dreamy Y2K</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Space Grotesk',sans-serif; }
        body { background:#FDEEF4; color:#2B2B2B; }

        /* TOPBAR */
        .admin-bar {
            background: white;
            border-bottom: 3px solid #A0D2EB;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top:0; z-index:100;
        }
        .admin-bar .logo { font-weight:700; font-size:1.3rem; }
        .admin-badge { background:#A0D2EB; color:white; font-size:0.6rem; padding:3px 8px; font-weight:700; letter-spacing:1px; margin-left:8px; }
        .logout-btn { background:#FFD1DC; border:1px solid #E5A9E0; padding:8px 20px; font-weight:700; font-size:0.8rem; text-decoration:none; color:#000; }

        /* TABS NAV */
        .tab-nav {
            background:white;
            border-bottom:2px solid #E5A9E0;
            display:flex;
            gap:0;
            padding: 0 40px;
        }
        .tab-nav a {
            padding: 12px 22px;
            font-size:0.8rem;
            font-weight:700;
            text-decoration:none;
            color:#999;
            border-bottom:3px solid transparent;
            text-transform:uppercase;
        }
        .tab-nav a.active { color:#A0D2EB; border-bottom-color:#A0D2EB; }
        .tab-nav a:hover { color:#A0D2EB; }

        .container { padding:35px 40px; max-width:1200px; margin:0 auto; }

        /* FLASH MESSAGE */
        .flash { background:#d4edda; border:1px solid #A0D2EB; color:#155724; padding:10px 20px; margin-bottom:25px; font-weight:600; font-size:0.85rem; }

        /* STAT CARDS */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:35px; }
        .stat-card {
            background:white;
            border:2px solid #A0D2EB;
            box-shadow:5px 5px 0 #E5A9E0;
            padding:20px 25px;
        }
        .stat-label { font-size:0.65rem; color:#999; text-transform:uppercase; font-weight:700; margin-bottom:6px; }
        .stat-value { font-size:1.8rem; font-weight:700; color:#A0D2EB; }

        /* WINDOW CARD / TABLE */
        .panel {
            background:white;
            border:2px solid #A0D2EB;
            box-shadow:6px 6px 0 #E5A9E0;
            border-radius:8px;
            overflow:hidden;
            margin-bottom:30px;
        }
        .panel-header {
            background:#f8f9fa;
            border-bottom:2px solid #A0D2EB;
            padding:10px 18px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:0.75rem;
            font-weight:700;
        }
        .panel-body { padding:25px; }

        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th { background:#fdf2f7; padding:10px 12px; text-align:left; font-size:0.65rem; text-transform:uppercase; color:#A0D2EB; font-weight:700; border-bottom:2px solid #E5A9E0; }
        td { padding:10px 12px; border-bottom:1px solid #fdeef4; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fdf9fb; }

        /* FORMS */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:15px; margin-bottom:15px; }
        .form-group label { display:block; font-size:0.65rem; font-weight:700; color:#A0D2EB; text-transform:uppercase; margin-bottom:5px; }
        .form-group input, .form-group select {
            width:100%; padding:9px 12px;
            border:2px solid #FDEEF4;
            font-family:'Space Grotesk',sans-serif;
            font-size:0.85rem;
            background:#fdf2f7;
        }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:#A0D2EB; background:white; }

        .btn { padding:9px 20px; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:0.8rem; cursor:pointer; border:2px solid; text-transform:uppercase; text-decoration:none; display:inline-block; transition:0.2s; }
        .btn-pink  { background:#FFD1DC; border-color:#E5A9E0; color:#000; }
        .btn-blue  { background:#A0D2EB; border-color:#7bbbd8; color:#fff; }
        .btn-red   { background:#ffe0e0; border-color:#ffb3b3; color:#c00; }
        .btn-green { background:#d4edda; border-color:#c3e6cb; color:#155724; }
        .btn:hover { opacity:0.85; transform:translateY(-1px); }

        .badge { padding:3px 10px; font-size:0.65rem; font-weight:700; border-radius:20px; text-transform:uppercase; }
        .badge-confirmed { background:#d4edda; color:#155724; }
        .badge-pending   { background:#fff3cd; color:#856404; }
        .badge-shipped   { background:#d1ecf1; color:#0c5460; }
        .badge-cancelled  { background:#f8d7da; color:#721c24; }
        .badge-delivered  { background:#c3e6cb; color:#0a3622; }

        .alert-row { background:#fff8e1 !important; }
        .dots { display:flex; gap:5px; }
        .dot { width:8px; height:8px; border-radius:50%; }
        .pink { background:#FFD1DC; }
        .blue { background:#A0D2EB; }

        .product-thumb { width:45px; height:45px; object-fit:cover; border:2px solid #E5A9E0; }
        .inline-form { display:inline; }
        .stock-input { width:70px; padding:4px 8px; border:2px solid #FDEEF4; font-family:'Space Grotesk',sans-serif; font-size:0.8rem; background:#fdf2f7; }

        @media(max-width:768px) {
            .stats-grid { grid-template-columns:repeat(2,1fr); }
            .container { padding:20px; }
            .form-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div class="admin-bar">
    <div>
        <span class="logo">✦ DREAMY ✦</span>
        <span class="admin-badge">ADMIN</span>
    </div>
    <div style="display:flex; align-items:center; gap:20px;">
        <span style="font-size:0.8rem; color:#999;">Logged in as <strong><?php echo $admin_name; ?></strong></span>
        <a href="admin_dashboard.php?logout=1" class="logout-btn">LOGOUT</a>
    </div>
</div>

<!-- TABS -->
<div class="tab-nav">
    <a href="?tab=overview"  class="<?php echo $active_tab=='overview'  ? 'active':''; ?>">Overview</a>
    <a href="?tab=products"  class="<?php echo $active_tab=='products'  ? 'active':''; ?>">Products</a>
    <a href="?tab=orders"    class="<?php echo $active_tab=='orders'    ? 'active':''; ?>">Orders</a>
    <a href="?tab=users"     class="<?php echo $active_tab=='users'     ? 'active':''; ?>">Users</a>
    <a href="?tab=purchases" class="<?php echo $active_tab=='purchases' ? 'active':''; ?>">Purchase History</a>
    <a href="?tab=alerts"    class="<?php echo $active_tab=='alerts'    ? 'active':''; ?>">Stock Alerts</a>
</div>

<div class="container">

<?php if ($msg): ?>
    <div class="flash">✦ <?php echo $msg; ?></div>
<?php endif; ?>

<!-- ══════════════ OVERVIEW TAB ══════════════ -->
<?php if ($active_tab == 'overview'): ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?php echo $total_products; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?php echo $total_users; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Confirmed Orders</div>
            <div class="stat-value"><?php echo $total_orders; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="panel">
        <div class="panel-header">
            <span>recent_orders.exe</span>
            <a href="?tab=orders" class="btn btn-blue" style="font-size:0.7rem; padding:5px 14px;">VIEW ALL</a>
        </div>
        <div class="panel-body">
            <table>
                <tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
                <?php
                $recent = mysqli_query($conn, "SELECT o.order_id, o.status, o.total_price, o.order_date AS created_at, u.username FROM orders o JOIN users u ON o.user_id=u.user_id WHERE o.status != 'pending' ORDER BY o.order_date DESC LIMIT 5");
                while ($row = mysqli_fetch_assoc($recent)): ?>
                <tr>
                    <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><strong>$<?php echo number_format($row['total_price'],2); ?></strong></td>
                    <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                    <td style="color:#999; font-size:0.8rem;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

<!-- ══════════════ PRODUCTS TAB ══════════════ -->
<?php elseif ($active_tab == 'products'): ?>

    <!-- ADD PRODUCT FORM -->
    <div class="panel" id="add-product-form" style="margin-bottom:30px;">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>add_new_product.exe</span>
        </div>
        <div class="panel-body">
            <form method="POST" action="admin_dashboard.php?tab=products#add-product-form" id="add-product-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="e.g. Cyber Flare Jeans" required>
                    </div>
                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" step="0.01" name="price" placeholder="38.00" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Count</label>
                        <input type="number" name="stock_count" placeholder="10" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <?php
                            $cats = mysqli_query($conn, "SELECT * FROM categories");
                            while ($c = mysqli_fetch_assoc($cats)):
                            ?>
                            <option value="<?php echo $c['category_id']; ?>"><?php echo $c['category_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Image Filename (e.g. my_dress.jpg)</label>
                        <input type="text" name="img_url" placeholder="my_dress.jpg" required>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-pink">✦ ADD PRODUCT</button>
            </form>
        </div>
    </div>

    <!-- PRODUCT LIST -->
    <div class="panel">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>all_products.exe — <?php echo $total_products; ?> items</span>
        </div>
        <div class="panel-body">
            <table>
                <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Update Stock</th><th>Edit</th><th>Delete</th></tr>
                <?php
                mysqli_data_seek($products, 0);
                while ($p = mysqli_fetch_assoc($products)):
                    $low = $p['stock_count'] <= 3;
                    $pid = $p['product_id'];
                ?>
                <tr id="product-row-<?php echo $pid; ?>" class="<?php echo $low ? 'alert-row':''; ?>">
                    <td><img src="<?php echo htmlspecialchars($p['img_url']); ?>" class="product-thumb" onerror="this.src='mod.jpg'"></td>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><?php if($low) echo ' <span style="color:#c00; font-size:0.7rem;">⚠ LOW</span>'; ?></td>
                    <td><?php echo $p['category_name']; ?></td>
                    <td>$<?php echo number_format($p['price'],2); ?></td>
                    <td><?php echo $p['stock_count']; ?></td>
                    <td>
                        <form method="POST" action="admin_dashboard.php?tab=products" class="inline-form" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                            <input type="number" name="stock_count" class="stock-input" value="<?php echo $p['stock_count']; ?>" min="0">
                            <button type="submit" name="update_stock" class="btn btn-blue" style="padding:4px 10px; font-size:0.7rem;">SAVE</button>
                        </form>
                    </td>
                    <td>
                        <button class="btn btn-pink" style="padding:4px 10px; font-size:0.7rem;"
                            onclick="toggleEdit(<?php echo $pid; ?>)">EDIT</button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-red" style="padding:4px 10px; font-size:0.7rem;"
                           onclick="showConfirmDeleteProduct('Delete this product?', <?php echo $pid; ?>)">DEL</button>
                    </td>
                </tr>
                <!-- INLINE EDIT ROW -->
                <tr id="edit-row-<?php echo $pid; ?>" style="display:none; background:#fff8fd;">
                    <td colspan="8" style="padding:15px 20px;">
                        <form method="POST" action="admin_dashboard.php?tab=products" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                            <div class="form-group" style="flex:2; min-width:150px;">
                                <label>Product Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                            </div>
                            <div class="form-group" style="flex:1; min-width:100px;">
                                <label>Price ($)</label>
                                <input type="number" step="0.01" name="price" value="<?php echo $p['price']; ?>" required>
                            </div>
                            <div class="form-group" style="flex:1; min-width:100px;">
                                <label>Category</label>
                                <select name="category_id">
                                    <?php
                                    $cats2 = mysqli_query($conn, "SELECT * FROM categories");
                                    while ($c2 = mysqli_fetch_assoc($cats2)):
                                    ?>
                                    <option value="<?php echo $c2['category_id']; ?>"
                                        <?php echo $c2['category_id'] == $p['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo $c2['category_name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex:2; min-width:150px;">
                                <label>Image Filename</label>
                                <input type="text" name="img_url" value="<?php echo htmlspecialchars($p['img_url']); ?>">
                            </div>
                            <div style="display:flex; gap:8px; align-items:flex-end; padding-bottom:2px;">
                                <button type="submit" name="edit_product" class="btn btn-green">✦ SAVE CHANGES</button>
                                <button type="button" class="btn btn-red" onclick="toggleEdit(<?php echo $pid; ?>)">CANCEL</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
    <script>
    function toggleEdit(pid) {
        var row = document.getElementById('edit-row-' + pid);
        row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
    }
    </script>

<!-- ══════════════ ORDERS TAB ══════════════ -->
<?php elseif ($active_tab == 'orders'): ?>

    <div class="panel">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>all_orders.exe</span>
        </div>
        <div class="panel-body">
            <table>
                <tr>
                    <th>Order ID</th><th>Customer</th><th>Email</th><th>Items</th>
                    <th>Total</th><th>Order Status</th><th>📦 Delivery</th>
                    <th>💳 Payment (COD)</th><th>Date</th>
                </tr>
                <?php while ($o = mysqli_fetch_assoc($orders)):
                    $oid_safe     = intval($o['order_id']);
                    $cur_status   = $o['status'];
                    $cur_delivery = $o['delivery_status'] ?? 'pending';
                    $cur_payment  = $o['payment_status']  ?? 'pending';

                    // Fetch items for this order
                    $items_q = mysqli_query($conn, "
                        SELECT p.name, oi.quantity, p.price
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.product_id
                        WHERE oi.order_id = $oid_safe
                    ");
                    $items_html = '';
                    while ($it = mysqli_fetch_assoc($items_q)) {
                        $items_html .= '<div style="font-size:0.78rem; padding:2px 0; border-bottom:1px dashed #fdeef4;">'
                            . htmlspecialchars($it['name'])
                            . ' &times; <strong>' . $it['quantity'] . '</strong>'
                            . ' <span style="color:#999;">($' . number_format($it['price'] * $it['quantity'], 2) . ')</span>'
                            . '</div>';
                    }
                    if (!$items_html) $items_html = '<span style="color:#ccc; font-size:0.75rem;">—</span>';

                    // Delivery badge map
                    $delivery_badges = [
                        'pending'    => ['bg'=>'#fff3cd','color'=>'#856404','label'=>'⏳ Pending'],
                        'in_transit' => ['bg'=>'#d1ecf1','color'=>'#0c5460','label'=>'📦 In Transit'],
                        'delivered'  => ['bg'=>'#c3e6cb','color'=>'#0a3622','label'=>'✅ Delivered'],
                        'failed'     => ['bg'=>'#f8d7da','color'=>'#721c24','label'=>'❌ Failed'],
                    ];
                    $db = $delivery_badges[$cur_delivery] ?? $delivery_badges['pending'];
                    $pay_icon  = $cur_payment === 'received' ? '💚 Received' : '⏳ Pending';
                    $pay_bg    = $cur_payment === 'received' ? '#c3e6cb' : '#fff3cd';
                    $pay_color = $cur_payment === 'received' ? '#0a3622' : '#856404';
                ?>
                <tr>
                    <td><strong>#<?php echo $o['order_id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($o['username']); ?></td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo htmlspecialchars($o['email']); ?></td>
                    <td style="max-width:180px;"><?php echo $items_html; ?></td>
                    <td><strong>$<?php echo number_format($o['total_price'],2); ?></strong></td>

                    <!-- ORDER STATUS + CONTROL -->
                    <td>
                        <span class="badge badge-<?php echo $cur_status; ?>"><?php echo ucfirst($cur_status); ?></span>
                        <?php if ($cur_status === 'confirmed'): ?>
                            <form method="POST" action="admin_dashboard.php?tab=orders" class="inline-form" style="margin-top:6px; display:flex; gap:5px; flex-wrap:wrap;">
                                <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                <input type="hidden" name="update_order_status" value="1">
                                <select name="status" style="padding:3px 6px; border:2px solid #FDEEF4; font-family:'Space Grotesk',sans-serif; font-size:0.72rem; background:#fdf2f7;">
                                    <option value="shipped">📦 Mark as Shipped</option>
                                    <option value="cancelled">✕ Cancel Order</option>
                                </select>
                                <button type="button" class="btn btn-blue" style="padding:3px 8px; font-size:0.68rem;"
                                    onclick="showConfirmDialog('Update this order status? This cannot be undone.', this.form)">UPDATE</button>
                            </form>
                        <?php elseif ($cur_status === 'shipped'): ?>
                            <div style="font-size:0.7rem; color:#0c5460; margin-top:4px; font-style:italic;">Manage via Delivery →</div>
                        <?php elseif ($cur_status === 'delivered'): ?>
                            <div style="font-size:0.7rem; color:#0a3622; margin-top:4px;">✓ Complete</div>
                        <?php else: ?>
                            <div style="font-size:0.7rem; color:#999; margin-top:4px; font-style:italic;">✕ Locked</div>
                        <?php endif; ?>
                    </td>

                    <!-- DELIVERY STATUS + CONTROL -->
                    <td>
                        <span style="background:<?php echo $db['bg']; ?>; color:<?php echo $db['color']; ?>; padding:3px 10px; border-radius:20px; font-size:0.65rem; font-weight:700; display:inline-block; margin-bottom:4px;">
                            <?php echo $db['label']; ?>
                        </span>
                        <?php if ($cur_status === 'shipped'): ?>
                            <form method="POST" action="admin_dashboard.php?tab=orders" class="inline-form" style="display:flex; gap:5px; flex-wrap:wrap;">
                                <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                <input type="hidden" name="update_delivery_status" value="1">
                                <select name="delivery_status" style="padding:3px 6px; border:2px solid #FDEEF4; font-family:'Space Grotesk',sans-serif; font-size:0.72rem; background:#fdf2f7;">
                                    <option value="in_transit" <?php echo $cur_delivery==='in_transit'?'selected':''; ?>>📦 In Transit</option>
                                    <option value="delivered"  <?php echo $cur_delivery==='delivered' ?'selected':''; ?>>✅ Delivered</option>
                                    <option value="failed"     <?php echo $cur_delivery==='failed'    ?'selected':''; ?>>❌ Failed</option>
                                </select>
                                <button type="button" class="btn btn-blue" style="padding:3px 8px; font-size:0.68rem;"
                                    onclick="showConfirmDialog('Update delivery status for this order?', this.form)">UPDATE</button>
                            </form>
                        <?php elseif ($cur_status === 'delivered' && $o['delivery_date']): ?>
                            <div style="font-size:0.7rem; color:#999; margin-top:2px;">
                                📅 <?php echo date('d M Y', strtotime($o['delivery_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- PAYMENT STATUS -->
                    <td>
                        <?php if ($cur_status === 'cancelled'): ?>
                            <div style="font-size:0.7rem; color:#999; font-style:italic;">✕ N/A</div>
                        <?php else: ?>
                            <div style="font-size:0.6rem; color:#999; margin-bottom:4px; font-weight:700;">CASH ON DELIVERY</div>
                            <span style="background:<?php echo $pay_bg; ?>; color:<?php echo $pay_color; ?>; padding:3px 10px; border-radius:20px; font-size:0.65rem; font-weight:700; display:inline-block; margin-bottom:4px;">
                                <?php echo $pay_icon; ?>
                            </span>
                            <?php if ($cur_status === 'delivered' && $cur_payment !== 'received'): ?>
                                <form method="POST" action="admin_dashboard.php?tab=orders" class="inline-form" style="margin-top:4px;">
                                    <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                    <input type="hidden" name="payment_status" value="received">
                                    <input type="hidden" name="update_payment_status" value="1">
                                    <button type="button" class="btn btn-green" style="padding:3px 8px; font-size:0.68rem;"
                                        onclick="showConfirmDialog('Mark cash payment as received for order #<?php echo $o['order_id']; ?>?', this.form)">MARK RECEIVED</button>
                                </form>
                            <?php elseif ($cur_payment === 'received'): ?>
                                <div style="font-size:0.7rem; color:#0a3622; font-style:italic;">Amount collected ✓</div>
                            <?php else: ?>
                                <div style="font-size:0.7rem; color:#999; font-style:italic;">Due on delivery</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td style="font-size:0.8rem; color:#999;"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>


<?php elseif ($active_tab == 'users'): ?>

    <div class="panel">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>all_users.exe — <?php echo $total_users; ?> registered</span>
        </div>
        <div class="panel-body">
            <table>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Address</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Purchases</th></tr>
                <?php
                $users = mysqli_query($conn, "
                    SELECT u.*,
                        COUNT(DISTINCT o.order_id) AS order_count,
                        COALESCE((
                            SELECT SUM(pi.total_price)
                            FROM purchased_items pi
                            JOIN orders o2 ON pi.order_id = o2.order_id
                            WHERE o2.user_id = u.user_id AND o2.payment_status = 'received'
                        ), 0) AS total_spent
                    FROM users u
                    LEFT JOIN orders o ON u.user_id = o.user_id AND o.status != 'pending'
                    GROUP BY u.user_id
                    ORDER BY u.user_id DESC
                ");
                while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><?php echo $u['user_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo $u['address'] ?: '—'; ?></td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo $u['phone_no'] ?: '—'; ?></td>
                    <td style="text-align:center;"><span class="badge badge-confirmed"><?php echo $u['order_count']; ?></span></td>
                    <td><strong>$<?php echo number_format($u['total_spent'], 2); ?></strong></td>
                    <td style="font-size:0.8rem; color:#999;">—</td>
                    <td>
                        <a href="?tab=purchases&user_id=<?php echo $u['user_id']; ?>" class="btn btn-pink" style="padding:4px 10px; font-size:0.7rem;">VIEW HISTORY</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

<!-- ══════════════ PURCHASE HISTORY TAB ══════════════ -->
<?php elseif ($active_tab == 'purchases'): ?>

    <?php
    $filter_uid  = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $filter_name = '';
    if ($filter_uid) {
        $fn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE user_id=$filter_uid"));
        if ($fn) $filter_name = $fn['username'];
    }

    $where = $filter_uid ? "WHERE pi.user_id = $filter_uid" : '';

    $purchases = mysqli_query($conn, "
        SELECT pi.purchase_id, pi.purchased_at, pi.quantity, pi.price_at_purchase, pi.total_price,
               u.username, u.email, u.user_id,
               p.name AS product_name, p.img_url
        FROM purchased_items pi
        JOIN users u    ON pi.user_id    = u.user_id
        JOIN products p ON pi.product_id = p.product_id
        $where
        ORDER BY pi.purchased_at DESC
    ");

    // Totals for the filtered view
    $total_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as cnt, COALESCE(SUM(total_price),0) as rev
         FROM purchased_items pi $where"
    ));
    ?>

    <!-- Filter bar -->
    <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px; flex-wrap:wrap;">
        <span style="font-size:0.8rem; font-weight:700; color:#A0D2EB;">
            <?php echo $filter_uid ? 'Showing purchases for: <strong>' . htmlspecialchars($filter_name) . '</strong>' : 'All Users — Full Purchase History'; ?>
        </span>
        <?php if ($filter_uid): ?>
            <a href="?tab=purchases" class="btn btn-pink" style="padding:5px 14px; font-size:0.75rem;">CLEAR FILTER</a>
        <?php endif; ?>
        <span style="font-size:0.8rem; color:#999; margin-left:auto;">
            <?php echo $total_row['cnt']; ?> purchase(s) &nbsp;|&nbsp; Total: <strong>$<?php echo number_format($total_row['rev'], 2); ?></strong>
        </span>
    </div>

    <!-- Filter by user dropdown -->
    <form method="GET" action="admin_dashboard.php" style="margin-bottom:20px; display:flex; gap:10px; align-items:center;">
        <input type="hidden" name="tab" value="purchases">
        <div class="form-group" style="margin:0; flex:1; max-width:300px;">
            <label>Filter by User</label>
            <select name="user_id" onchange="this.form.submit()" style="padding:8px 12px; border:2px solid #FDEEF4; font-family:'Space Grotesk',sans-serif; font-size:0.85rem; background:#fdf2f7; width:100%;">
                <option value="0">— All Users —</option>
                <?php
                $all_users = mysqli_query($conn, "SELECT user_id, username FROM users ORDER BY username");
                while ($au = mysqli_fetch_assoc($all_users)):
                ?>
                <option value="<?php echo $au['user_id']; ?>" <?php echo $filter_uid == $au['user_id'] ? 'selected':''; ?>>
                    <?php echo htmlspecialchars($au['username']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>

    <div class="panel">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>purchase_history.exe</span>
        </div>
        <div class="panel-body">
            <table>
                <tr><th>#</th><th>Product</th><th>Customer</th><th>Email</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Purchased On</th></tr>
                <?php
                $has_rows = false;
                while ($pi = mysqli_fetch_assoc($purchases)):
                    $has_rows = true;
                ?>
                <tr>
                    <td style="color:#999; font-size:0.8rem;"><?php echo $pi['purchase_id']; ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="<?php echo htmlspecialchars($pi['img_url']); ?>" style="width:36px; height:36px; object-fit:cover; border:2px solid #E5A9E0;" onerror="this.src='mod.jpg'">
                            <strong><?php echo htmlspecialchars($pi['product_name']); ?></strong>
                        </div>
                    </td>
                    <td>
                        <a href="?tab=purchases&user_id=<?php echo $pi['user_id']; ?>" style="color:#A0D2EB; font-weight:700; text-decoration:none;">
                            <?php echo htmlspecialchars($pi['username']); ?>
                        </a>
                    </td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo htmlspecialchars($pi['email']); ?></td>
                    <td style="text-align:center;"><strong><?php echo $pi['quantity']; ?></strong></td>
                    <td>$<?php echo number_format($pi['price_at_purchase'], 2); ?></td>
                    <td><strong>$<?php echo number_format($pi['total_price'], 2); ?></strong></td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo date('d M Y, H:i', strtotime($pi['purchased_at'])); ?></td>
                </tr>
                <?php endwhile;
                if (!$has_rows): ?>
                <tr><td colspan="8" style="text-align:center; color:#999; padding:30px;">No purchases found<?php echo $filter_uid ? ' for this user' : ''; ?>.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

<!-- ══════════════ STOCK ALERTS TAB ══════════════ -->
<?php elseif ($active_tab == 'alerts'): ?>

    <!-- OUT OF STOCK PRODUCTS (from products table directly) -->
    <div class="panel" style="margin-bottom:25px;">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>out_of_stock.exe — products with 0 stock</span>
        </div>
        <div class="panel-body">
            <p style="font-size:0.8rem; color:#999; margin-bottom:15px;">
                These products currently have <strong>0 stock</strong> and are showing as <em>Sold Out</em> to shoppers.
            </p>
            <table>
                <tr><th>Product ID</th><th>Product Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Fix Stock</th></tr>
                <?php
                $out_of_stock = mysqli_query($conn, "
                    SELECT p.*, c.category_name FROM products p
                    JOIN categories c ON p.category_id = c.category_id
                    WHERE p.stock_count = 0
                    ORDER BY p.name
                ");
                $has_oos = false;
                while ($oos = mysqli_fetch_assoc($out_of_stock)):
                    $has_oos = true;
                ?>
                <tr id="product-row-<?php echo $oos['product_id']; ?>" style="background:#fff0f3;">
                    <td><?php echo $oos['product_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($oos['name']); ?></strong> <span style="color:#c00; font-size:0.7rem;">✦ SOLD OUT</span></td>
                    <td><?php echo $oos['category_name']; ?></td>
                    <td>$<?php echo number_format($oos['price'],2); ?></td>
                    <td style="color:#c00; font-weight:700;">0</td>
                    <td>
                        <form method="POST" action="admin_dashboard.php?tab=alerts" class="inline-form" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="product_id" value="<?php echo $oos['product_id']; ?>">
                            <input type="number" name="stock_count" class="stock-input" value="5" min="1">
                            <button type="submit" name="update_stock" class="btn btn-blue" style="padding:4px 10px; font-size:0.7rem;">RESTOCK</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile;
                if (!$has_oos) echo '<tr><td colspan="6" style="text-align:center; color:#999; padding:25px;">✦ All products are in stock!</td></tr>';
                ?>
            </table>
        </div>
    </div>

    <!-- LOW STOCK ALERTS (from stock_alerts trigger log) -->
    <div class="panel">
        <div class="panel-header">
            <div class="dots"><span class="dot pink"></span><span class="dot blue"></span></div>
            <span>low_stock_alerts.exe — auto-logged by trigger (stock ≤ 3)</span>
        </div>
        <div class="panel-body">
            <p style="font-size:0.8rem; color:#999; margin-bottom:15px;">
                These alerts are automatically created by the <strong>trg_low_stock_alert</strong> MySQL trigger
                when any product's stock drops to 3 or below.
            </p>
            <table>
                <tr><th>Alert ID</th><th>Product ID</th><th>Product Name</th><th>Stock at Alert</th><th>Current Stock</th><th>Alerted At</th></tr>
                <?php
                $alerts_full = mysqli_query($conn, "
                    SELECT sa.*, p.stock_count AS current_stock
                    FROM stock_alerts sa
                    LEFT JOIN products p ON sa.product_id = p.product_id
                    ORDER BY sa.alerted_at DESC LIMIT 20
                ");
                $has = false;
                while ($a = mysqli_fetch_assoc($alerts_full)):
                    $has = true;
                    $cur = $a['current_stock'];
                    $cur_color = ($cur == 0) ? '#c00' : (($cur <= 3) ? '#856404' : '#155724');
                ?>
                <tr class="alert-row">
                    <td><?php echo $a['alert_id']; ?></td>
                    <td><?php echo $a['product_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($a['product_name']); ?></strong></td>
                    <td style="color:#856404; font-weight:700;"><?php echo $a['stock_left']; ?> left</td>
                    <td style="color:<?php echo $cur_color; ?>; font-weight:700;"><?php echo $cur !== null ? $cur : '—'; ?></td>
                    <td style="font-size:0.8rem; color:#999;"><?php echo $a['alerted_at']; ?></td>
                </tr>
                <?php endwhile;
                if (!$has) echo '<tr><td colspan="6" style="text-align:center; color:#999; padding:30px;">No low-stock alerts yet. Alerts appear when stock drops to 3 or below.</td></tr>';
                ?>
            </table>
        </div>
    </div>

<?php endif; ?>
</div><!-- /container -->

<!-- ════════════════════════════════════════════════════════════ -->
<!-- CUSTOM CURSOR-BASED CONFIRMATION DIALOG (Replaces JS confirm) -->
<!-- ════════════════════════════════════════════════════════════ -->
<div id="confirmDialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; flex-direction:column;">
    <div style="background:white; border:3px solid #A0D2EB; border-radius:10px; padding:30px; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.3); text-align:center; font-family:'Space Grotesk',sans-serif;">
        <!-- WINDOW HEADER STYLE -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #FDEEF4;">
            <div style="display:flex; gap:6px;">
                <span style="width:12px; height:12px; border-radius:50%; background:#FFD1DC;"></span>
                <span style="width:12px; height:12px; border-radius:50%; background:#A0D2EB;"></span>
            </div>
            <span style="font-weight:700; font-size:0.9rem; color:#2B2B2B;">confirm_action.exe</span>
            <div></div>
        </div>
        
        <!-- MESSAGE -->
        <div style="margin-bottom:25px; font-size:0.95rem; color:#2B2B2B; line-height:1.5;" id="dialogMessage"></div>
        
        <!-- BUTTONS -->
        <div style="display:flex; gap:12px; justify-content:center;">
            <button type="button" onclick="confirmAction()" style="padding:10px 25px; background:#A0D2EB; color:white; border:none; border-radius:6px; font-weight:700; font-family:'Space Grotesk',sans-serif; font-size:0.85rem; cursor:pointer; transition:0.2s;" onmouseover="this.background='#7ebcd4'" onmouseout="this.background='#A0D2EB'">
                OK
            </button>
            <button type="button" onclick="cancelAction()" style="padding:10px 25px; background:#2B5C7B; color:white; border:none; border-radius:6px; font-weight:700; font-family:'Space Grotesk',sans-serif; font-size:0.85rem; cursor:pointer; transition:0.2s;" onmouseover="this.background='#1e4056'" onmouseout="this.background='#2B5C7B'">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════════════════
// CURSOR-BASED CONFIRMATION DIALOG
// (Replaces JavaScript confirm() - Educational Style)
// ════════════════════════════════════════════════════════════

let confirmData = {
    message: '',
    orderId: null,
    form: null,
    isDelete: false,
    productId: null
};

// Show confirmation dialog (Cursor Pattern - Fetch Data)
function showConfirmDialog(message, form) {
    confirmData.message = message;
    confirmData.form = form;
    confirmData.isDelete = false;
    
    document.getElementById('dialogMessage').textContent = message;
    document.getElementById('confirmDialog').style.display = 'flex';
}

// Show delete product confirmation
function showConfirmDeleteProduct(message, productId) {
    confirmData.message = message;
    confirmData.productId = productId;
    confirmData.isDelete = true;
    
    document.getElementById('dialogMessage').textContent = message;
    document.getElementById('confirmDialog').style.display = 'flex';
}

// Confirm action (Process Data)
function confirmAction() {
    if (confirmData.isDelete) {
        // Delete product
        window.location.href = 'admin_dashboard.php?delete_product=' + confirmData.productId + '&tab=products';
    } else {
        // Submit form (IMPORTANT: set update_order_status to '1' so PHP detects it)
        if (confirmData.form) {
            // Find the hidden update_order_status input and set it
            var hiddenInput = confirmData.form.querySelector('input[name="update_order_status"]');
            if (hiddenInput) {
                hiddenInput.value = '1';
            }
            confirmData.form.submit();
        }
    }
    closeDialog();
}

// Cancel action (Close Cursor)
function cancelAction() {
    closeDialog();
}

// Close dialog
function closeDialog() {
    document.getElementById('confirmDialog').style.display = 'none';
    confirmData = {
        message: '',
        orderId: null,
        form: null,
        isDelete: false,
        productId: null
    };
}

// Close dialog on background click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmDialog').addEventListener('click', function(e) {
        if (e.target === this) {
            cancelAction();
        }
    });
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDialog();
    }
});
</script>

</body>
</html>