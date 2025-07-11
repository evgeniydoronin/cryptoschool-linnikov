<?php
/**
 * Тестовый файл для проверки работы реферальной системы
 * 
 * ВАЖНО: Этот файл только для тестирования! Удалить после завершения Этапа 1.
 * 
 * Для запуска: поместить в корень WordPress и открыть в браузере
 */

// Подключаем WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Подключаем файлы плагина
require_once('wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model.php');
require_once('wp-content/plugins/cryptoschool/includes/models/class-cryptoschool-model-referral-link.php');
require_once('wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository.php');
require_once('wp-content/plugins/cryptoschool/includes/repositories/class-cryptoschool-repository-referral-link.php');

echo "<h1>Тест реферальной системы - Этап 1</h1>";

// Тест 1: Создание модели
echo "<h2>Тест 1: Создание модели реферальной ссылки</h2>";

try {
    $model_data = [
        'id' => 1,
        'user_id' => 1,
        'referral_code' => 'TEST123',
        'link_name' => 'Тестовая ссылка',
        'link_description' => 'Описание тестовой ссылки',
        'discount_percent' => 15.0,
        'commission_percent' => 25.0,
        'clicks_count' => 10,
        'conversions_count' => 2,
        'total_earned' => 50.0,
        'is_active' => 1,
        'created_at' => '2025-06-16 10:00:00',
        'updated_at' => '2025-06-16 10:00:00'
    ];

    $model = new CryptoSchool_Model_Referral_Link($model_data);
    
    echo "<p>✅ Модель создана успешно</p>";
    echo "<p>ID пользователя: " . $model->get_user_id() . "</p>";
    echo "<p>Реферальный код: " . $model->get_referral_code() . "</p>";
    echo "<p>Название ссылки: " . $model->get_link_name() . "</p>";
    echo "<p>Полная ссылка: " . $model->get_full_url() . "</p>";
    echo "<p>Общий процент: " . $model->get_total_percent() . "%</p>";
    echo "<p>Конверсия: " . $model->get_conversion_rate() . "%</p>";
    echo "<p>В пределах лимита (40%): " . ($model->is_within_limit() ? 'Да' : 'Нет') . "</p>";
    
    // Тест валидации
    $validation_errors = $model->validate();
    if (empty($validation_errors)) {
        echo "<p>✅ Валидация пройдена</p>";
    } else {
        echo "<p>❌ Ошибки валидации: " . implode(', ', $validation_errors) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка создания модели: " . $e->getMessage() . "</p>";
}

// Тест 2: Создание репозитория
echo "<h2>Тест 2: Создание репозитория</h2>";

try {
    $repository = new CryptoSchool_Repository_Referral_Link();
    echo "<p>✅ Репозиторий создан успешно</p>";
    echo "<p>Имя таблицы: " . $repository->get_table_name() . "</p>";
    
    // Тест генерации уникального кода
    $unique_code = $repository->generate_unique_code();
    echo "<p>Сгенерированный код: " . $unique_code . "</p>";
    
    // Тест проверки уникальности (должен вернуть true для несуществующего кода)
    $is_unique = $repository->is_code_unique('NONEXISTENT123');
    echo "<p>Код 'NONEXISTENT123' уникален: " . ($is_unique ? 'Да' : 'Нет') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка создания репозитория: " . $e->getMessage() . "</p>";
}

// Тест 3: Проверка структуры базы данных
echo "<h2>Тест 3: Проверка структуры базы данных</h2>";

global $wpdb;

// Проверяем существование таблицы реферальных ссылок
$table_name = $wpdb->prefix . 'cryptoschool_referral_links';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "<p>✅ Таблица $table_name существует</p>";
    
    // Проверяем структуру таблицы
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "<p>Колонки таблицы:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} ({$column->Type})</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p>❌ Таблица $table_name не существует</p>";
    echo "<p>💡 Необходимо выполнить миграцию из файла: wp-content/plugins/cryptoschool/includes/migrations/migration-referral-system-update.sql</p>";
}

// Тест 4: Проверка новой таблицы иерархии
$hierarchy_table = $wpdb->prefix . 'cryptoschool_referral_hierarchy';
$hierarchy_exists = $wpdb->get_var("SHOW TABLES LIKE '$hierarchy_table'") == $hierarchy_table;

if ($hierarchy_exists) {
    echo "<p>✅ Таблица $hierarchy_table существует</p>";
} else {
    echo "<p>❌ Таблица $hierarchy_table не существует</p>";
}

// Тест 5: Тест модели с невалидными данными
echo "<h2>Тест 4: Валидация модели с невалидными данными</h2>";

try {
    $invalid_data = [
        'user_id' => '', // Пустой user_id
        'referral_code' => '', // Пустой код
        'discount_percent' => 50.0, // Превышение лимита
        'commission_percent' => 30.0, // Сумма 80% > 40%
        'clicks_count' => -5, // Отрицательное значение
        'conversions_count' => 15 // Больше чем переходы
    ];

    $invalid_model = new CryptoSchool_Model_Referral_Link($invalid_data);
    $validation_errors = $invalid_model->validate();
    
    if (!empty($validation_errors)) {
        echo "<p>✅ Валидация корректно выявила ошибки:</p><ul>";
        foreach ($validation_errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Валидация не выявила ошибки в невалидных данных</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Ошибка при тестировании валидации: " . $e->getMessage() . "</p>";
}

echo "<h2>Результаты тестирования Этапа 1</h2>";
echo "<p><strong>Статус:</strong> Базовые компоненты созданы и готовы к тестированию</p>";
echo "<p><strong>Следующий шаг:</strong> Выполнить SQL-миграцию и протестировать работу с базой данных</p>";

echo "<hr>";
echo "<p><em>Дата тестирования: " . date('Y-m-d H:i:s') . "</em></p>";
?>
