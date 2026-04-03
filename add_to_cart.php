<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id    = intval($_SESSION['user_id']);
$product_id = intval($_GET['id']);

$referer       = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'home.php';
$referer_clean = preg_replace('/#.*$/', '', $referer);
// Also strip any existing query strings so we don't stack them
$referer_clean = preg_replace('/\?.*$/', '', $referer_clean);
$anchor        = '#product-' . $product_id;

$go_to_checkout = isset($_GET['buy']) && $_GET['buy'] == '1';

// ── 1. Check the product exists and get current stock ──────────────────────
$stock_result = mysqli_query($conn, "SELECT stock_count FROM products WHERE product_id = $product_id");
$stock_row    = mysqli_fetch_assoc($stock_result);

if (!$stock_row || $stock_row['stock_count'] <= 0) {
    // Product is sold out
    header("Location: " . $referer_clean . "?status=out_of_stock" . $anchor);
    exit();
}

$available_stock = intval($stock_row['stock_count']);

// ── 2. Get or create the user's pending order (cart) ──────────────────────
$order_result = mysqli_query($conn, "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending'");
if (mysqli_num_rows($order_result) === 0) {
    mysqli_query($conn, "INSERT INTO orders (user_id, total_price, status) VALUES ($user_id, 0.00, 'pending')");
    $order_id = mysqli_insert_id($conn);
} else {
    $order_id = intval(mysqli_fetch_assoc($order_result)['order_id']);
}

// ── 3. Check if this product is already in the cart ───────────────────────
$item_result = mysqli_query($conn, "SELECT item_id, quantity FROM order_items WHERE order_id = $order_id AND product_id = $product_id");

if (mysqli_num_rows($item_result) > 0) {
    // Already in cart — keep quantity at 1, just confirm
    // (No stock changes — stock only moves at checkout)
    $status = 'already_in_cart';
} else {
    // New item — add with quantity 1
    mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity) VALUES ($order_id, $product_id, 1)");
    $status = 'added';
}

// ── 4. Redirect back to where the user was, preserving scroll ─────────────
if ($go_to_checkout) {
    header("Location: checkout.php");
} else {
    header("Location: " . $referer_clean . "?status=" . $status . $anchor);
}
exit();
?>
