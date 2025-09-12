<?php
/**
 * Тестовый файл для проверки прав администратора
 * Использовать: https://cryptoschool.ai/wp-content/themes/cryptoschool/test-admin-access.php
 */

require_once('../../../wp-load.php');

// Проверяем, авторизован ли пользователь
if (!is_user_logged_in()) {
    echo "ERROR: User not logged in\n";
    exit;
}

$current_user = wp_get_current_user();

echo "=== CryptoSchool Admin Access Test ===\n";
echo "User ID: " . $current_user->ID . "\n";
echo "User login: " . $current_user->user_login . "\n";
echo "User email: " . $current_user->user_email . "\n";
echo "User roles: " . implode(', ', (array)$current_user->roles) . "\n";
echo "\nCapabilities Check:\n";
echo "- Is administrator: " . (current_user_can('administrator') ? 'YES' : 'NO') . "\n";
echo "- Can manage_options: " . (current_user_can('manage_options') ? 'YES' : 'NO') . "\n";
echo "- Can activate_plugins: " . (current_user_can('activate_plugins') ? 'YES' : 'NO') . "\n";
echo "- Can edit_posts: " . (current_user_can('edit_posts') ? 'YES' : 'NO') . "\n";
echo "\nEnvironment Check:\n";
echo "- Is admin area: " . (is_admin() ? 'YES' : 'NO') . "\n";
echo "- Admin URL: " . admin_url() . "\n";
echo "- Home URL: " . home_url() . "\n";
echo "- Site URL: " . site_url() . "\n";

// Проверка всех capabilities пользователя
$all_caps = $current_user->get_role_caps();
if (!empty($all_caps)) {
    echo "\nAll User Capabilities:\n";
    foreach ($all_caps as $cap => $value) {
        if ($value) {
            echo "- {$cap}: YES\n";
        }
    }
}

echo "\n=== End Test ===\n";