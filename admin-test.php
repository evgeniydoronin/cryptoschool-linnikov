<?php
// Симулируем админскую страницу
$_SERVER['REQUEST_URI'] = '/wp-admin/users.php';

// Подключаем WordPress
require_once('wp-load.php');

echo "<pre>";
echo "=== ADMIN TEST DEBUG ===\n";
echo "Simulating REQUEST_URI: /wp-admin/users.php\n";
echo "User logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "\n";
echo "User ID: " . get_current_user_id() . "\n";
echo "User can manage_options: " . (current_user_can('manage_options') ? 'Yes' : 'No') . "\n";
echo "is_admin(): " . (is_admin() ? 'Yes' : 'No') . "\n";

// Записываем тест в лог
file_put_contents('wp-content/debug.log', date('Y-m-d H:i:s') . " [ADMIN-TEST] Simulating admin page access\n", FILE_APPEND);

echo "\nSimulating admin page - check debug.log for entry\n";
echo "</pre>";
?>