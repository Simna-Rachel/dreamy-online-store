<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Sanitize anchor for scroll position
$anchor = isset($_GET['anchor']) ? '#' . preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['anchor']) : '';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Only touch the pending order
    $order_result = mysqli_query($conn, "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending'");
    $order        = mysqli_fetch_assoc($order_result);
    $order_id     = $order ? intval($order['order_id']) : null;

    if ($order_id) {
        // No stock restore — stock is never deducted until checkout
        mysqli_query($conn, "DELETE FROM order_items WHERE item_id = $id AND order_id = $order_id");
    }
}

// Redirect back to cart (anchor is best-effort; item may be gone)
header("Location: cart.php" . $anchor);
exit();
?>
