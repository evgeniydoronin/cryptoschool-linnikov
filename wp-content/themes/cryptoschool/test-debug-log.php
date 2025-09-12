<?php
/**
 * Тестовый файл для диагностики проблем с debug.log
 * Использовать: https://cryptoschool.ai/wp-content/themes/cryptoschool/test-debug-log.php
 */

require_once('../../../wp-load.php');

echo "<h2>=== Debug Log Diagnostic Test ===</h2>\n";

// Проверка констант WordPress
echo "<h3>WordPress Debug Constants:</h3>\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "<br>\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . "<br>\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'true' : 'false') . "<br>\n";
echo "SCRIPT_DEBUG: " . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'true' : 'false') . "<br>\n";

// Проверка путей
echo "<h3>Paths:</h3>\n";
echo "WP_CONTENT_DIR: " . WP_CONTENT_DIR . "<br>\n";
echo "Debug log path: " . WP_CONTENT_DIR . '/debug.log' . "<br>\n";
echo "ABSPATH: " . ABSPATH . "<br>\n";

// Проверка прав доступа
echo "<h3>Permissions:</h3>\n";
echo "WP_CONTENT_DIR writable: " . (is_writable(WP_CONTENT_DIR) ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "<br>\n";
echo "WP_CONTENT_DIR permissions: " . substr(sprintf('%o', fileperms(WP_CONTENT_DIR)), -4) . "<br>\n";

// Проверка существования debug.log
$debug_log_path = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log_path)) {
    echo "<span style='color: green;'>debug.log EXISTS</span><br>\n";
    echo "File size: " . filesize($debug_log_path) . " bytes<br>\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($debug_log_path)), -4) . "<br>\n";
    echo "File writable: " . (is_writable($debug_log_path) ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "<br>\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($debug_log_path)) . "<br>\n";
} else {
    echo "<span style='color: red;'>debug.log DOES NOT EXIST</span><br>\n";
    
    // Попытка создать файл
    if (@touch($debug_log_path)) {
        echo "<span style='color: green;'>Successfully created debug.log</span><br>\n";
    } else {
        echo "<span style='color: red;'>Failed to create debug.log</span><br>\n";
    }
}

// Проверка настроек PHP
echo "<h3>PHP Settings:</h3>\n";
echo "error_reporting: " . error_reporting() . "<br>\n";
echo "log_errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>\n";
echo "error_log: " . ini_get('error_log') . "<br>\n";
echo "display_errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>\n";

// Тестирование записи в лог
echo "<h3>Log Write Test:</h3>\n";
$test_message = "Test message from test-debug-log.php at " . date('Y-m-d H:i:s');

// Попытка записи через error_log
$result1 = error_log($test_message);
echo "error_log() result: " . ($result1 ? '<span style="color: green;">SUCCESS</span>' : '<span style="color: red;">FAILED</span>') . "<br>\n";

// Попытка записи через WP функции
if (function_exists('wp_debug_log')) {
    wp_debug_log($test_message . ' (via wp_debug_log)');
    echo "wp_debug_log() called<br>\n";
}

// Попытка прямой записи в файл
$direct_write = @file_put_contents($debug_log_path, date('[Y-m-d H:i:s] ') . $test_message . "\n", FILE_APPEND | LOCK_EX);
echo "Direct file write result: " . ($direct_write !== false ? '<span style="color: green;">SUCCESS (' . $direct_write . ' bytes)</span>' : '<span style="color: red;">FAILED</span>') . "<br>\n";

// Проверка после записи
if (file_exists($debug_log_path)) {
    echo "Current debug.log size: " . filesize($debug_log_path) . " bytes<br>\n";
}

echo "<h3>Recommendations:</h3>\n";
if (!file_exists($debug_log_path)) {
    echo "• Create debug.log file manually<br>\n";
    echo "• Set permissions 664 for debug.log<br>\n";
}
if (!is_writable(WP_CONTENT_DIR)) {
    echo "• Set permissions 755 or 775 for wp-content directory<br>\n";
}
if (!ini_get('log_errors')) {
    echo "• Enable log_errors in PHP configuration<br>\n";
}

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";