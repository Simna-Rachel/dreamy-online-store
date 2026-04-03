<?php
/**
 * ONE-TIME SCRIPT: Run this once in your browser to set admin password to 12345678
 * Then DELETE this file from your server immediately after!
 * URL: yourdomain.com/reset_admin_password.php
 */
include('db_config.php');

$new_password = '12345678';
$hash = password_hash($new_password, PASSWORD_BCRYPT);

$sql = "UPDATE admins SET password = '$hash' WHERE admin_id = 1";
if (mysqli_query($conn, $sql)) {
    echo "✅ Admin password has been updated to: 12345678<br>";
    echo "⚠️ PLEASE DELETE THIS FILE NOW from your server!";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
?>
