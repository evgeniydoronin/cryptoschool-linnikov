<?php
// Подключаем WordPress
require_once('wp-load.php');

echo "<pre>";
echo "=== TEST DEBUG ===\n";
echo "User logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "\n";
echo "User ID: " . get_current_user_id() . "\n";
echo "User can manage_options: " . (current_user_can('manage_options') ? 'Yes' : 'No') . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "is_admin(): " . (is_admin() ? 'Yes' : 'No') . "\n";

// Попробуем записать в разные места
error_log('[TEST] error_log function');
file_put_contents('wp-content/test-direct.log', date('Y-m-d H:i:s') . " - Direct write test\n", FILE_APPEND);
file_put_contents('wp-content/debug.log', date('Y-m-d H:i:s') . " - Direct write to debug.log\n", FILE_APPEND);

echo "\nCheck these files:\n";
echo "- wp-content/debug.log\n";
echo "- wp-content/test-direct.log\n";
echo "- error_log (in root)\n";
echo "</pre>";
?>