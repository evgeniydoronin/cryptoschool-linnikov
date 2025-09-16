<?php
/**
 * Тестовый скрипт для проверки оптимизированной системы логирования
 * 
 * Использование: https://cryptoschool.ai/test-security-logging.php
 * 
 * @package CryptoSchool
 */

// Подключаем WordPress
require_once __DIR__ . '/wp-config.php';

// Проверяем права доступа
if (!current_user_can('manage_options')) {
    wp_die('У вас нет прав для выполнения этого действия.');
}

echo "<h1>🔍 Тест оптимизированной системы логирования</h1>\n";

// Очищаем кеш транзиентов для админа
$current_user_id = get_current_user_id();
$cache_key = 'admin_access_logged_' . $current_user_id;
delete_transient($cache_key);
echo "<p>✅ Кеш админского доступа очищен</p>\n";

// Принудительно вызываем логирование
if (class_exists('CryptoSchool_Security_Logger')) {
    echo "<h2>📝 Тестирование логирования</h2>\n";
    
    // Тест 1: Логирование входа
    CryptoSchool_Security_Logger::log(
        'auth',
        'test_login',
        'Test login event from optimization script',
        CryptoSchool_Security_Logger::LEVEL_INFO,
        ['test' => true, 'script' => 'test-security-logging.php']
    );
    echo "<p>✅ Тест логирования входа выполнен</p>\n";
    
    // Тест 2: Логирование угрозы
    CryptoSchool_Security_Logger::log(
        'threats',
        'test_threat',
        'Test security threat from optimization script',
        CryptoSchool_Security_Logger::LEVEL_WARNING,
        ['test' => true, 'threat_type' => 'test']
    );
    echo "<p>✅ Тест логирования угрозы выполнен</p>\n";
    
    // Тест 3: Логирование доступа
    CryptoSchool_Security_Logger::log(
        'access',
        'test_access',
        'Test admin access from optimization script',
        CryptoSchool_Security_Logger::LEVEL_INFO,
        ['test' => true, 'access_type' => 'test']
    );
    echo "<p>✅ Тест логирования доступа выполнен</p>\n";
    
} else {
    echo "<p>❌ Класс CryptoSchool_Security_Logger не найден</p>\n";
}

// Проверяем созданные файлы
echo "<h2>📁 Проверка созданных файлов</h2>\n";

$today = date('Y-m-d');
$log_dir = WP_CONTENT_DIR . '/security-logs';

$expected_files = [
    "auth/test_login-{$today}.log",
    "threats/test_threat-{$today}.log", 
    "access/test_access-{$today}.log"
];

foreach ($expected_files as $file) {
    $filepath = $log_dir . '/' . $file;
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        echo "<p>✅ Файл создан: <code>{$file}</code> ({$size} байт)</p>\n";
        
        // Показываем последние строки
        $content = file_get_contents($filepath);
        $lines = explode("\n", trim($content));
        $last_line = end($lines);
        if ($last_line) {
            echo "<p style='background: #f0f0f0; padding: 10px; font-family: monospace; font-size: 12px;'>{$last_line}</p>\n";
        }
    } else {
        echo "<p>❌ Файл НЕ создан: <code>{$file}</code></p>\n";
    }
}

// Проверяем статистику
echo "<h2>📊 Тест статистики</h2>\n";

if (class_exists('CryptoSchool_Security_Logger')) {
    try {
        $stats = CryptoSchool_Security_Logger::get_log_statistics(1);
        echo "<p>✅ Статистика получена успешно:</p>\n";
        echo "<ul>\n";
        echo "<li>Всего событий: {$stats['total_events']}</li>\n";
        echo "<li>По уровням: " . json_encode($stats['by_level']) . "</li>\n";
        echo "<li>По категориям: " . json_encode($stats['by_category']) . "</li>\n";
        echo "<li>Топ IP: " . count($stats['top_ips']) . " адресов</li>\n";
        echo "<li>Угрозы: " . count($stats['recent_threats']) . " событий</li>\n";
        echo "</ul>\n";
    } catch (Exception $e) {
        echo "<p>❌ Ошибка получения статистики: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>❌ Класс для статистики не найден</p>\n";
}

// Показываем структуру папки логов
echo "<h2>🗂️ Текущая структура логов</h2>\n";

function show_directory_tree($dir, $prefix = '') {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filepath = $dir . '/' . $file;
        if (is_dir($filepath)) {
            echo "<p>{$prefix}📁 {$file}/</p>\n";
            show_directory_tree($filepath, $prefix . '&nbsp;&nbsp;&nbsp;&nbsp;');
        } else {
            $size = filesize($filepath);
            $modified = date('Y-m-d H:i:s', filemtime($filepath));
            echo "<p>{$prefix}📄 {$file} ({$size} байт, {$modified})</p>\n";
        }
    }
}

show_directory_tree($log_dir);

// Показываем debug информацию
echo "<h2>🐛 Debug информация</h2>\n";

$debug_file = $log_dir . '/debug-security-logger.log';
if (file_exists($debug_file)) {
    $debug_content = file_get_contents($debug_file);
    echo "<p>✅ Debug файл найден:</p>\n";
    echo "<pre style='background: #f0f0f0; padding: 15px; overflow-x: auto; font-size: 12px;'>";
    echo htmlspecialchars($debug_content);
    echo "</pre>\n";
} else {
    echo "<p>❌ Debug файл не найден: <code>{$debug_file}</code></p>\n";
}

echo "<h2>✅ Тестирование завершено</h2>\n";
echo "<p><strong>Рекомендации:</strong></p>\n";
echo "<ul>\n";
echo "<li>Если файлы создаются с правильными именами (без timestamp) - оптимизация работает</li>\n";
echo "<li>Можно удалить старые файлы с timestamp вручную</li>\n";
echo "<li>Новые логи будут создаваться в оптимизированном формате</li>\n";
echo "<li>Проверьте debug информацию выше чтобы понять откуда берется timestamp</li>\n";
echo "</ul>\n";

echo "<p><a href='/wp-admin/'>← Вернуться в админку</a></p>\n";
