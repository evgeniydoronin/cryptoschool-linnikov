<?php
// Простейший тест записи в лог без подключения WordPress
error_log('SIMPLE TEST: ' . date('Y-m-d H:i:s'));
file_put_contents('wp-content/simple-test.log', date('Y-m-d H:i:s') . " - Simple test write\n", FILE_APPEND);

echo "Simple test completed - check wp-content/simple-test.log";
?>