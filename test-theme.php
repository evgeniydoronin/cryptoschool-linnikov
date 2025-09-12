<?php
// Подключаем WordPress
require_once('wp-load.php');

echo "<pre>";
echo "=== THEME DEBUG ===\n";

// Проверяем активную тему
$current_theme = wp_get_theme();
echo "Current theme: " . $current_theme->get('Name') . "\n";
echo "Theme directory: " . $current_theme->get_stylesheet_directory() . "\n";
echo "Template: " . get_template() . "\n";
echo "Stylesheet: " . get_stylesheet() . "\n";

// Проверяем существует ли functions.php темы
$functions_file = get_template_directory() . '/functions.php';
echo "Functions.php path: " . $functions_file . "\n";
echo "Functions.php exists: " . (file_exists($functions_file) ? 'Yes' : 'No') . "\n";
if (file_exists($functions_file)) {
    echo "Functions.php size: " . filesize($functions_file) . " bytes\n";
    echo "Functions.php modified: " . date('Y-m-d H:i:s', filemtime($functions_file)) . "\n";
}

// Проверяем хуки
echo "\nRegistered init hooks:\n";
global $wp_filter;
if (isset($wp_filter['init'])) {
    foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && is_string($callback['function'][1])) {
                echo "Priority $priority: " . $callback['function'][1] . "\n";
            } else if (is_string($callback['function'])) {
                echo "Priority $priority: " . $callback['function'] . "\n";
            }
        }
    }
}

echo "\nLooking for cryptoschool_redirect_non_admin_users:\n";
if (function_exists('cryptoschool_redirect_non_admin_users')) {
    echo "Function exists: Yes\n";
} else {
    echo "Function exists: No\n";
}

echo "</pre>";
?>