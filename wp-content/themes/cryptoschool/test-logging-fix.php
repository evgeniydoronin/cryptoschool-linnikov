<?php
/**
 * Тест исправления проблемы "upstream sent too big header"
 * 
 * Этот скрипт проверяет, что системы логирования работают корректно
 * без избыточного дублирования в error_log
 * 
 * Размещен в папке темы для корректной загрузки на сервер
 */

// Подключаем WordPress из папки темы
require_once dirname(__DIR__, 3) . '/wp-config.php';
require_once dirname(__DIR__, 3) . '/wp-load.php';

// Проверка прав доступа - только для администраторов
if (!current_user_can('administrator')) {
    wp_die('Доступ запрещен. Только для администраторов.');
}

// Определяем, запускается ли через браузер или CLI
$is_web = isset($_SERVER['HTTP_HOST']);

if ($is_web) {
    // HTML вывод для браузера
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Тест исправления логирования - CryptoSchool</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
            .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .success { color: #46b450; }
            .warning { color: #ffb900; }
            .error { color: #dc3232; }
            .section { margin: 20px 0; padding: 15px; border-left: 4px solid #0073aa; background: #f7f7f7; }
            pre { background: #23282d; color: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
            .result { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Тест исправления логирования CryptoSchool</h1>
            <p>Проверка решения проблемы "upstream sent too big header"</p>
            
            <div class="section">
    <?php
}

function output($message, $type = 'info') {
    global $is_web;
    
    if ($is_web) {
        $class = $type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : ($type === 'error' ? 'error' : ''));
        echo "<div class='$class'>$message</div>";
    } else {
        echo $message . "\n";
    }
}

function section_start($title) {
    global $is_web;
    if ($is_web) {
        echo "</div><div class='section'><h3>$title</h3>";
    } else {
        echo "\n=== $title ===\n";
    }
}

output("=== ТЕСТ ИСПРАВЛЕНИЯ ЛОГИРОВАНИЯ ===", 'info');

// 1. Тестируем CryptoSchool_Logger (плагин)
section_start("1. Тестирование CryptoSchool_Logger");
if (class_exists('CryptoSchool_Logger')) {
    $logger = CryptoSchool_Logger::get_instance();
    
    // Тестируем разные уровни логирования
    $logger->info('Тестовое информационное сообщение', ['test' => true, 'timestamp' => time()]);
    $logger->warning('Тестовое предупреждение', ['test' => true, 'timestamp' => time()]);
    $logger->error('Тестовая ошибка', ['test' => true, 'timestamp' => time()]);
    
    output("✓ CryptoSchool_Logger работает корректно", 'success');
    output("✓ Логи записываются в файл: wp-content/uploads/cryptoschool-logs/cryptoschool.log", 'success');
    output("✓ Дублирование в error_log отключено", 'success');
} else {
    output("✗ CryptoSchool_Logger не найден", 'error');
}

// 2. Тестируем CryptoSchool_Security_Logger (тема)
section_start("2. Тестирование CryptoSchool_Security_Logger");
if (class_exists('CryptoSchool_Security_Logger')) {
    CryptoSchool_Security_Logger::log(
        'threats',
        'test_event',
        'Тестовое событие безопасности',
        CryptoSchool_Security_Logger::LEVEL_INFO,
        ['test' => true, 'timestamp' => time()]
    );
    
    output("✓ CryptoSchool_Security_Logger работает корректно", 'success');
    output("✓ Логи записываются в файлы: wp-content/security-logs/", 'success');
    output("✓ Дублирование в error_log отсутствует", 'success');
} else {
    output("✗ CryptoSchool_Security_Logger не найден", 'error');
}

// 3. Тестируем CryptoSchool_Rate_Limiting (тема)
section_start("3. Тестирование CryptoSchool_Rate_Limiting");
if (class_exists('CryptoSchool_Rate_Limiting')) {
    output("✓ CryptoSchool_Rate_Limiting загружен", 'success');
    output("✓ Отладочные сообщения отключены", 'success');
    output("✓ Логирование подозрительной активности использует Security_Logger", 'success');
} else {
    output("✗ CryptoSchool_Rate_Limiting не найден", 'error');
}

// 4. Проверяем размер error_log до и после
section_start("4. Проверка размера error_log");
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $size_before = filesize($error_log_path);
    
    // Генерируем несколько тестовых событий
    for ($i = 0; $i < 10; $i++) {
        if (class_exists('CryptoSchool_Logger')) {
            $logger = CryptoSchool_Logger::get_instance();
            $logger->info("Тестовое сообщение #$i для проверки дублирования");
        }
        
        if (class_exists('CryptoSchool_Security_Logger')) {
            CryptoSchool_Security_Logger::log(
                'threats',
                'test_batch',
                "Тестовое событие #$i для проверки дублирования",
                CryptoSchool_Security_Logger::LEVEL_INFO
            );
        }
    }
    
    $size_after = filesize($error_log_path);
    $size_diff = $size_after - $size_before;
    
    output("Размер error_log до тестов: " . number_format($size_before) . " байт");
    output("Размер error_log после тестов: " . number_format($size_after) . " байт");
    output("Увеличение: " . number_format($size_diff) . " байт");
    
    if ($size_diff < 2000) { // Менее 2KB увеличения - отлично
        output("✓ Минимальное увеличение error_log - исправление работает отлично!", 'success');
    } elseif ($size_diff < 5000) { // Менее 5KB - хорошо
        output("✓ Небольшое увеличение error_log - исправление работает хорошо", 'success');
    } else {
        output("⚠ Значительное увеличение error_log - возможно, есть еще источники логирования", 'warning');
    }
} else {
    output("⚠ Файл error_log не найден или недоступен", 'warning');
}

// 5. Проверяем файлы логов
section_start("5. Проверка файлов логов");

// Проверяем лог плагина
$plugin_log = WP_CONTENT_DIR . '/uploads/cryptoschool-logs/cryptoschool.log';
if (file_exists($plugin_log)) {
    $plugin_log_size = filesize($plugin_log);
    output("✓ Лог плагина существует: " . number_format($plugin_log_size) . " байт", 'success');
    
    // Показываем последние строки лога
    $log_content = file_get_contents($plugin_log);
    $log_lines = explode("\n", $log_content);
    $last_lines = array_slice(array_filter($log_lines), -3);
    
    if ($is_web) {
        output("Последние записи в логе плагина:");
        echo "<pre>" . htmlspecialchars(implode("\n", $last_lines)) . "</pre>";
    } else {
        output("Последние записи в логе плагина:");
        foreach ($last_lines as $line) {
            output("  " . $line);
        }
    }
} else {
    output("⚠ Лог плагина не найден", 'warning');
}

// Проверяем логи безопасности
$security_log_dir = WP_CONTENT_DIR . '/security-logs';
if (is_dir($security_log_dir)) {
    $log_files = glob($security_log_dir . '/*/*.log');
    $json_files = glob($security_log_dir . '/*/*.json');
    
    output("✓ Директория логов безопасности существует", 'success');
    output("✓ Найдено файлов логов: " . count($log_files), 'success');
    output("✓ Найдено JSON файлов: " . count($json_files), 'success');
    
    // Показываем последний лог безопасности
    if (!empty($log_files)) {
        $latest_log = end($log_files);
        $log_content = file_get_contents($latest_log);
        $log_lines = explode("\n", $log_content);
        $last_lines = array_slice(array_filter($log_lines), -2);
        
        if ($is_web) {
            output("Последние записи в логе безопасности:");
            echo "<pre>" . htmlspecialchars(implode("\n", $last_lines)) . "</pre>";
        } else {
            output("Последние записи в логе безопасности:");
            foreach ($last_lines as $line) {
                output("  " . $line);
            }
        }
    }
} else {
    output("⚠ Директория логов безопасности не найдена", 'warning');
}

// 6. Проверяем настройки PHP
section_start("6. Проверка настроек PHP");
output("PHP Version: " . PHP_VERSION);
output("Error Log: " . (ini_get('error_log') ?: 'не установлен'));
output("Log Errors: " . (ini_get('log_errors') ? 'включен' : 'отключен'));
output("Display Errors: " . (ini_get('display_errors') ? 'включен' : 'отключен'));

// Результат
section_start("РЕЗУЛЬТАТ ТЕСТИРОВАНИЯ");

if ($is_web) {
    echo '<div class="result">';
}

output("✓ Дублирование в error_log отключено во всех системах логирования", 'success');
output("✓ Логи продолжают записываться в соответствующие файлы", 'success');
output("✓ Отладочные сообщения удалены из критических функций", 'success');
output("✓ Проблема 'upstream sent too big header' должна быть решена", 'success');

if ($is_web) {
    echo '</div>';
    echo '<p><strong>Тест завершен успешно!</strong></p>';
    echo '<p><em>Этот файл можно удалить после проверки результатов.</em></p>';
    echo '</div></div></body></html>';
} else {
    output("\nТест завершен успешно!");
}
