<?php
/**
 * Отладка системы миграций
 */

require_once __DIR__ . '/wp-config.php';

if (!current_user_can('manage_options')) {
    wp_die('У вас нет прав для просмотра этой страницы.');
}

echo '<h1>🔧 Отладка системы миграций</h1>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #17a2b8; }
    .warning { color: #ffc107; font-weight: bold; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>';

// 1. Проверка версий
echo '<div class="section">';
echo '<h2>📋 Информация о версиях</h2>';

$plugin_version = defined('CRYPTOSCHOOL_VERSION') ? CRYPTOSCHOOL_VERSION : 'Не определена';
$db_version = get_option('cryptoschool_db_version', 'Не установлена');

echo '<p><strong>Версия плагина:</strong> ' . $plugin_version . '</p>';
echo '<p><strong>Версия схемы БД:</strong> ' . $db_version . '</p>';

if (version_compare($db_version, '1.0.5', '<')) {
    echo '<p class="warning">⚠️ Версия БД ниже 1.0.5 - миграция должна выполниться</p>';
} else {
    echo '<p class="success">✅ Версия БД соответствует или выше 1.0.5</p>';
}
echo '</div>';

// 2. Проверка класса мигратора
echo '<div class="section">';
echo '<h2>🔧 Проверка класса мигратора</h2>';

if (class_exists('CryptoSchool_Migrator')) {
    echo '<p class="success">✅ Класс CryptoSchool_Migrator существует</p>';
    
    $migrator = new CryptoSchool_Migrator();
    $needs_migration = $migrator->needs_migration();
    
    echo '<p><strong>Нужна ли миграция:</strong> ' . ($needs_migration ? '<span class="warning">Да</span>' : '<span class="success">Нет</span>') . '</p>';
    
    // Попробуем получить список миграций через рефлексию
    $reflection = new ReflectionClass($migrator);
    if ($reflection->hasMethod('get_migrations')) {
        $method = $reflection->getMethod('get_migrations');
        $method->setAccessible(true);
        $migrations = $method->invoke($migrator);
        
        echo '<h4>Доступные миграции:</h4>';
        echo '<pre>' . print_r($migrations, true) . '</pre>';
    }
} else {
    echo '<p class="error">❌ Класс CryptoSchool_Migrator не найден</p>';
}
echo '</div>';

// 3. Ручной запуск миграции
echo '<div class="section">';
echo '<h2>🚀 Ручной запуск миграции</h2>';

if (isset($_GET['run_migration']) && $_GET['run_migration'] === 'yes') {
    echo '<p class="info">🔄 Запуск миграции...</p>';
    
    if (class_exists('CryptoSchool_Migrator')) {
        $migrator = new CryptoSchool_Migrator();
        
        try {
            // Принудительно запускаем миграцию 1.0.5
            $migrator->migration_1_0_5();
            echo '<p class="success">✅ Миграция 1.0.5 выполнена принудительно!</p>';
            
            // Обновляем версию БД
            update_option('cryptoschool_db_version', '1.0.5');
            
            // Обновляем информацию о версии
            $new_db_version = get_option('cryptoschool_db_version', 'Не установлена');
            echo '<p><strong>Новая версия схемы БД:</strong> ' . $new_db_version . '</p>';
            
            echo '<p class="info">🔄 <a href="check-migration-results.php">Проверить результаты миграции</a></p>';
            
        } catch (Exception $e) {
            echo '<p class="error">❌ Ошибка при выполнении миграции: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="error">❌ Класс мигратора не найден</p>';
    }
} else {
    echo '<p><a href="?run_migration=yes" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🚀 Запустить миграцию 1.0.5 принудительно</a></p>';
    echo '<p class="info">💡 Это принудительно выполнит миграцию 1.0.5 с подробным логированием</p>';
}
echo '</div>';

// 4. Проверка структуры таблицы
echo '<div class="section">';
echo '<h2>🗃️ Текущая структура таблицы</h2>';

global $wpdb;
$table_name = $wpdb->prefix . 'cryptoschool_referral_links';

$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");

if ($columns) {
    echo '<h4>Колонки в таблице ' . $table_name . ':</h4>';
    echo '<pre>';
    foreach ($columns as $column) {
        echo $column->Field . ' (' . $column->Type . ')' . "\n";
    }
    echo '</pre>';
} else {
    echo '<p class="error">❌ Не удалось получить структуру таблицы</p>';
}
echo '</div>';

// 5. Проверка логов
echo '<div class="section">';
echo '<h2>📝 Проверка логов</h2>';

if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<p class="success">✅ WP_DEBUG включен - логи должны записываться</p>';
    
    // Попробуем найти лог-файл
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        echo '<p class="info">📄 Лог-файл найден: ' . $log_file . '</p>';
        
        // Показать последние строки лога
        $log_content = file_get_contents($log_file);
        $log_lines = explode("\n", $log_content);
        $recent_lines = array_slice($log_lines, -20); // Последние 20 строк
        
        echo '<h4>Последние записи в логе:</h4>';
        echo '<pre style="max-height: 200px; overflow-y: auto;">';
        foreach ($recent_lines as $line) {
            if (stripos($line, 'cryptoschool') !== false || stripos($line, 'migration') !== false) {
                echo '<strong>' . htmlspecialchars($line) . '</strong>' . "\n";
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo '</pre>';
    } else {
        echo '<p class="warning">⚠️ Лог-файл не найден: ' . $log_file . '</p>';
    }
} else {
    echo '<p class="warning">⚠️ WP_DEBUG отключен - логи не записываются</p>';
    echo '<p class="info">💡 Включите WP_DEBUG в wp-config.php для отладки</p>';
}
echo '</div>';

echo '<hr>';
echo '<p class="info">📅 Отладка выполнена: ' . date('Y-m-d H:i:s') . '</p>';
?>
