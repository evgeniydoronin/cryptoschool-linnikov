<?php
// Прямой тест functions.php без WordPress
file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - Starting direct functions.php test\n", FILE_APPEND);

// Определяем константы, которые нужны functions.php
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', __DIR__ . '/wp-content');
}

// Создаем минимальные функции WordPress которые используются в functions.php
if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        return __DIR__ . '/wp-content/themes/cryptoschool';
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10) {
        file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - add_action: $hook\n", FILE_APPEND);
        return true;
    }
}

// Попробуем подключить functions.php напрямую
try {
    file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - About to include functions.php\n", FILE_APPEND);
    
    include_once('wp-content/themes/cryptoschool/functions.php');
    
    file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - functions.php included successfully\n", FILE_APPEND);
    
    echo "Direct functions.php test completed - check wp-content/direct-test.log";
    
} catch (Exception $e) {
    file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "Error: " . $e->getMessage();
} catch (Error $e) {
    file_put_contents('wp-content/direct-test.log', date('Y-m-d H:i:s') . " - FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "Fatal Error: " . $e->getMessage();
}
?>