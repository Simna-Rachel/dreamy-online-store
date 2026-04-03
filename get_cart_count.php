<?php
session_start();
include('db_config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get the user's PENDING order (cart)
$order_query = "SELECT order_id FROM orders WHERE user_id = $user_id AND status = 'pending'";
$order_result = mysqli_query($conn, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$order_id = $order_row ? intval($order_row['order_id']) : null;

$count = 0;

if ($order_id) {
    $count_query = "SELECT COUNT(*) as total FROM order_items WHERE order_id = $order_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $count = intval($count_row['total']);
}

echo json_encode(['count' => $count]);
?>
