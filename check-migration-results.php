<?php
/**
 * Скрипт для проверки результатов миграции реферальной системы
 * 
 * Запустите этот файл в браузере: http://ваш-сайт.com/check-migration-results.php
 * 
 * @package CryptoSchool
 */

// Подключение WordPress
require_once __DIR__ . '/wp-config.php';

// Проверка доступа (только для администраторов)
if (!current_user_can('manage_options')) {
    wp_die('У вас нет прав для просмотра этой страницы.');
}

global $wpdb;

echo '<h1>🔍 Проверка результатов миграции реферальной системы</h1>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #17a2b8; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
</style>';

// Функция для проверки существования колонки
function check_column_exists($table, $column) {
    global $wpdb;
    $result = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    return !empty($result);
}

// Функция для проверки существования таблицы
function check_table_exists($table) {
    global $wpdb;
    $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    return $result === $table;
}

// Функция для проверки существования индекса
function check_index_exists($table, $index_name) {
    global $wpdb;
    $result = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = '{$index_name}'");
    return !empty($result);
}

// Проверка версии плагина
echo '<div class="section">';
echo '<h2>📋 Информация о версии</h2>';
$plugin_version = defined('CRYPTOSCHOOL_VERSION') ? CRYPTOSCHOOL_VERSION : 'Не определена';
$db_version = get_option('cryptoschool_db_version', 'Не установлена');

echo '<table>';
echo '<tr><th>Параметр</th><th>Значение</th></tr>';
echo '<tr><td>Версия плагина</td><td>' . $plugin_version . '</td></tr>';
echo '<tr><td>Версия схемы БД</td><td>' . $db_version . '</td></tr>';
echo '</table>';

if (version_compare($db_version, '1.0.5', '>=')) {
    echo '<p class="success">✅ Версия схемы БД соответствует или выше 1.0.5</p>';
} else {
    echo '<p class="error">❌ Версия схемы БД ниже 1.0.5. Необходимо выполнить миграцию.</p>';
}
echo '</div>';

// 1. Проверка обновления таблицы реферальных ссылок
echo '<div class="section">';
echo '<h2>🔗 Проверка таблицы реферальных ссылок</h2>';

$referral_links_table = $wpdb->prefix . 'cryptoschool_referral_links';
$table_exists = check_table_exists($referral_links_table);

if (!$table_exists) {
    echo '<p class="error">❌ Таблица ' . $referral_links_table . ' не существует</p>';
} else {
    echo '<p class="success">✅ Таблица ' . $referral_links_table . ' существует</p>';
    
    // Проверка новых колонок
    $new_columns = [
        'link_name' => 'Название ссылки',
        'link_description' => 'Описание ссылки', 
        'clicks_count' => 'Количество переходов',
        'conversions_count' => 'Количество конверсий',
        'total_earned' => 'Общая сумма заработка',
        'is_active' => 'Активна ли ссылка'
    ];
    
    echo '<table>';
    echo '<tr><th>Колонка</th><th>Описание</th><th>Статус</th></tr>';
    
    foreach ($new_columns as $column => $description) {
        $exists = check_column_exists($referral_links_table, $column);
        $status = $exists ? '<span class="success">✅ Существует</span>' : '<span class="error">❌ Отсутствует</span>';
        echo '<tr><td>' . $column . '</td><td>' . $description . '</td><td>' . $status . '</td></tr>';
    }
    echo '</table>';
}
echo '</div>';

// 2. Проверка новой таблицы иерархии
echo '<div class="section">';
echo '<h2>🏗️ Проверка таблицы двухуровневой иерархии</h2>';

$hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
$hierarchy_exists = check_table_exists($hierarchy_table);

if ($hierarchy_exists) {
    echo '<p class="success">✅ Таблица ' . $hierarchy_table . ' создана</p>';
    
    // Показать структуру таблицы
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$hierarchy_table}");
        echo '<table>';
        echo '<tr><th>Колонка</th><th>Тип</th><th>Комментарий</th></tr>';
        foreach ($columns as $column) {
            $comment = isset($column->Comment) ? $column->Comment : '-';
            echo '<tr><td>' . $column->Field . '</td><td>' . $column->Type . '</td><td>' . ($comment ?: '-') . '</td></tr>';
        }
        echo '</table>';
} else {
    echo '<p class="error">❌ Таблица ' . $hierarchy_table . ' не создана</p>';
}
echo '</div>';

// 3. Проверка обновления таблицы транзакций
echo '<div class="section">';
echo '<h2>💰 Проверка таблицы реферальных транзакций</h2>';

$transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
$transactions_exists = check_table_exists($transactions_table);

if (!$transactions_exists) {
    echo '<p class="info">ℹ️ Таблица ' . $transactions_table . ' еще не создана (будет создана при первой транзакции)</p>';
} else {
    echo '<p class="success">✅ Таблица ' . $transactions_table . ' существует</p>';
    
    // Проверка новых колонок в таблице транзакций
    $transaction_columns = [
        'referral_level' => 'Уровень реферала (1 или 2)',
        'level1_commission' => 'Комиссия 1-го уровня',
        'level2_commission' => 'Комиссия 2-го уровня',
        'referral_link_id' => 'ID реферальной ссылки'
    ];
    
    echo '<table>';
    echo '<tr><th>Колонка</th><th>Описание</th><th>Статус</th></tr>';
    
    foreach ($transaction_columns as $column => $description) {
        $exists = check_column_exists($transactions_table, $column);
        $status = $exists ? '<span class="success">✅ Существует</span>' : '<span class="error">❌ Отсутствует</span>';
        echo '<tr><td>' . $column . '</td><td>' . $description . '</td><td>' . $status . '</td></tr>';
    }
    echo '</table>';
}
echo '</div>';

// 4. Проверка индексов
echo '<div class="section">';
echo '<h2>🚀 Проверка индексов для оптимизации</h2>';

if ($table_exists) {
    $indexes_to_check = [
        'idx_referral_links_user_active' => 'Индекс по пользователю и активности',
        'idx_referral_links_code_active' => 'Индекс по коду и активности'
    ];
    
    echo '<table>';
    echo '<tr><th>Индекс</th><th>Описание</th><th>Статус</th></tr>';
    
    foreach ($indexes_to_check as $index => $description) {
        $exists = check_index_exists($referral_links_table, $index);
        $status = $exists ? '<span class="success">✅ Создан</span>' : '<span class="error">❌ Отсутствует</span>';
        echo '<tr><td>' . $index . '</td><td>' . $description . '</td><td>' . $status . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<p class="error">❌ Невозможно проверить индексы - основная таблица не существует</p>';
}
echo '</div>';

// 5. Проверка данных
echo '<div class="section">';
echo '<h2>📊 Проверка данных</h2>';

if ($table_exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$referral_links_table}");
    echo '<p>Количество записей в таблице реферальных ссылок: <strong>' . $count . '</strong></p>';
    
    if ($count > 0) {
        // Показать пример записи
        $sample = $wpdb->get_row("SELECT * FROM {$referral_links_table} LIMIT 1", ARRAY_A);
        echo '<h4>Пример записи:</h4>';
        echo '<table>';
        echo '<tr><th>Поле</th><th>Значение</th></tr>';
        foreach ($sample as $field => $value) {
            echo '<tr><td>' . $field . '</td><td>' . ($value ?: 'NULL') . '</td></tr>';
        }
        echo '</table>';
    }
}

if ($hierarchy_exists) {
    $hierarchy_count = $wpdb->get_var("SELECT COUNT(*) FROM {$hierarchy_table}");
    echo '<p>Количество записей в таблице иерархии: <strong>' . $hierarchy_count . '</strong></p>';
}
echo '</div>';

// 6. Общий результат
echo '<div class="section">';
echo '<h2>🎯 Общий результат миграции</h2>';

$all_checks = [];

// Проверки для общего результата
if ($table_exists) {
    $all_checks[] = check_column_exists($referral_links_table, 'link_name');
    $all_checks[] = check_column_exists($referral_links_table, 'clicks_count');
    $all_checks[] = check_column_exists($referral_links_table, 'is_active');
}
$all_checks[] = $hierarchy_exists;

$success_count = count(array_filter($all_checks));
$total_checks = count($all_checks);

if ($success_count === $total_checks) {
    echo '<p class="success">🎉 Миграция выполнена успешно! Все компоненты реферальной системы готовы к работе.</p>';
} else {
    echo '<p class="error">⚠️ Миграция выполнена частично. Успешно: ' . $success_count . ' из ' . $total_checks . '</p>';
    echo '<p class="info">💡 Попробуйте деактивировать и снова активировать плагин для завершения миграции.</p>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>🔧 Дополнительные действия</h2>';
echo '<p><a href="test-referral-system.php" target="_blank">🧪 Запустить тест реферальной системы</a></p>';
echo '<p><a href="wp-admin/plugins.php">⚙️ Перейти к управлению плагинами</a></p>';
echo '</div>';

echo '<hr>';
echo '<p class="info">📅 Проверка выполнена: ' . date('Y-m-d H:i:s') . '</p>';
?>
