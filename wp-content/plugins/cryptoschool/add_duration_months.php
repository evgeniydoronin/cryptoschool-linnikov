<?php
/**
 * Скрипт для добавления колонки duration_months в таблицу wp_cryptoschool_user_access
 */

// Загрузка WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Проверка прав доступа
if (!current_user_can('manage_options')) {
    die('Доступ запрещен');
}

global $wpdb;
$table_name = $wpdb->prefix . 'cryptoschool_user_access';

// Проверка существования таблицы
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
if (!$table_exists) {
    die("Таблица {$table_name} не существует");
}

// Проверка существования колонки
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'duration_months'");
if (!empty($column_exists)) {
    die("Колонка duration_months уже существует в таблице {$table_name}");
}

// Добавление колонки duration_months
$result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN duration_months int(11) DEFAULT NULL AFTER access_end");

if ($result === false) {
    die("Ошибка при добавлении колонки: " . $wpdb->last_error);
} else {
    echo "Колонка duration_months успешно добавлена в таблицу {$table_name}";
}
