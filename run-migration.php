<?php
/**
 * Скрипт для принудительного запуска миграции базы данных
 * 
 * Запускает миграцию 1.4.1 для добавления полей в таблицу платежей
 */

// Подключение к WordPress
require_once('wp-load.php');

// Подключение класса миграций
require_once('wp-content/plugins/cryptoschool/includes/class-cryptoschool-migrator.php');

echo "<h1>🚀 Запуск миграции базы данных</h1>";
echo "<p><strong>Дата:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // Создаем экземпляр мигратора
    $migrator = new CryptoSchool_Migrator();
    
    echo "<h2>📋 Проверка необходимости миграции</h2>";
    
    // Проверяем текущую версию БД
    $current_db_version = get_option('cryptoschool_db_version', '0.0.0');
    $plugin_version = '1.4.1';
    
    echo "<p>Текущая версия БД: <strong>{$current_db_version}</strong></p>";
    echo "<p>Версия плагина: <strong>{$plugin_version}</strong></p>";
    
    // Проверяем нужна ли миграция
    if ($migrator->needs_migration()) {
        echo "<p>✅ Миграция необходима</p>";
        
        echo "<h2>🔧 Запуск миграций</h2>";
        
        // Запускаем миграции
        $migrator->run_migrations();
        
        echo "<p>✅ Миграции выполнены успешно!</p>";
        
        // Проверяем новую версию
        $new_db_version = get_option('cryptoschool_db_version', '0.0.0');
        echo "<p>Новая версия БД: <strong>{$new_db_version}</strong></p>";
        
    } else {
        echo "<p>ℹ️ Миграция не требуется - база данных актуальна</p>";
    }
    
    echo "<h2>🔍 Проверка структуры таблицы платежей</h2>";
    
    // Проверяем структуру таблицы платежей
    global $wpdb;
    $payments_table = $wpdb->prefix . 'cryptoschool_payments';
    
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$payments_table}");
    
    echo "<p><strong>Структура таблицы {$payments_table}:</strong></p>";
    echo "<ul>";
    
    $required_fields = ['original_amount', 'discount_percent', 'discount_amount', 'final_amount'];
    $found_fields = [];
    
    foreach ($columns as $column) {
        $field_name = $column->Field;
        $field_type = $column->Type;
        
        if (in_array($field_name, $required_fields)) {
            echo "<li>✅ <strong>{$field_name}</strong> ({$field_type})</li>";
            $found_fields[] = $field_name;
        } else {
            echo "<li>{$field_name} ({$field_type})</li>";
        }
    }
    echo "</ul>";
    
    // Проверяем все ли поля добавлены
    $missing_fields = array_diff($required_fields, $found_fields);
    
    if (empty($missing_fields)) {
        echo "<p>🎉 <strong>Все необходимые поля присутствуют!</strong></p>";
        echo "<p>Теперь тест реферальной системы должен работать корректно.</p>";
    } else {
        echo "<p>❌ <strong>Отсутствуют поля:</strong> " . implode(', ', $missing_fields) . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>📝 Следующие шаги</h2>";
    echo "<ol>";
    echo "<li>Запустите тест реферальной системы: <code>test-referral-system-full.php</code></li>";
    echo "<li>Проверьте логи в <code>wp-content/debug.log</code></li>";
    echo "<li>Убедитесь, что все тесты проходят успешно</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Ошибка:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Проверьте логи WordPress для получения дополнительной информации.</p>";
}

echo "<p><em>Время завершения: " . date('Y-m-d H:i:s') . "</em></p>";
?>
