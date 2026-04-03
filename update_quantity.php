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

if (isset($_GET['id']) && isset($_GET['action'])) {
    $item_id = intval($_GET['id']);
    $action  = $_GET['action'];

    // Only touch the pending (active cart) order
    $order_result = mysqli_query($conn, "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending'");
    $order        = mysqli_fetch_assoc($order_result);
    $order_id     = $order ? intval($order['order_id']) : null;

    if ($order_id) {
        $item_result = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE item_id = $item_id AND order_id = $order_id");
        $item        = mysqli_fetch_assoc($item_result);

        if ($item) {
            $product_id       = intval($item['product_id']);
            $current_quantity = intval($item['quantity']);

            if ($action === 'increase') {
                // Check available stock before allowing increase
                $stock_result = mysqli_query($conn, "SELECT stock_count FROM products WHERE product_id = $product_id");
                $stock_row    = mysqli_fetch_assoc($stock_result);
                $available    = intval($stock_row['stock_count']);

                if ($current_quantity < $available) {
                    mysqli_query($conn, "UPDATE order_items SET quantity = quantity + 1 WHERE item_id = $item_id");
                }
                // If current_quantity >= available: silently cap, no increase
            } elseif ($action === 'decrease') {
                if ($current_quantity > 1) {
                    mysqli_query($conn, "UPDATE order_items SET quantity = quantity - 1 WHERE item_id = $item_id");
                } else {
                    // Quantity is 1 — remove item from cart entirely
                    mysqli_query($conn, "DELETE FROM order_items WHERE item_id = $item_id AND order_id = $order_id");
                }
            }
        }
    }
}

// Redirect back to cart preserving scroll position
header("Location: cart.php" . $anchor);
exit();
?>
