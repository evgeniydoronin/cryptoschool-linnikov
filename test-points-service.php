<?php
/**
 * Тестовый скрипт для проверки инициализации сервиса баллов
 * Проверяет корректность настройки системы начисления баллов
 */

// Подключение к WordPress
require_once('wp-load.php');

// Проверяем, запущен ли скрипт через браузер
$is_web_request = !empty($_SERVER['HTTP_HOST']);

if ($is_web_request) {
    echo "<pre style='background: #1e1e1e; color: #fff; padding: 20px; font-family: monospace; line-height: 1.5;'>";
}

echo "=== ТЕСТИРОВАНИЕ СЕРВИСА БАЛЛОВ ===\n\n";

// 1. Проверяем, что сервис баллов правильно инициализируется
echo "🔧 === ПРОВЕРКА ИНИЦИАЛИЗАЦИИ СЕРВИСА ===\n";

try {
    $loader = new CryptoSchool_Loader();
    $points_service = new CryptoSchool_Service_Points($loader);
    echo "✅ CryptoSchool_Service_Points успешно создан\n";
} catch (Exception $e) {
    echo "❌ Ошибка создания CryptoSchool_Service_Points: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Проверяем репозитории
echo "\n📦 === ПРОВЕРКА РЕПОЗИТОРИЕВ ===\n";

try {
    $points_repo = new CryptoSchool_Repository_Points_History();
    echo "✅ CryptoSchool_Repository_Points_History создан\n";
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

try {
    $streak_repo = new CryptoSchool_Repository_User_Streak();
    echo "✅ CryptoSchool_Repository_User_Streak создан\n";
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

try {
    $leaderboard_repo = new CryptoSchool_Repository_User_Leaderboard();
    echo "✅ CryptoSchool_Repository_User_Leaderboard создан\n";
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

// 3. Проверяем существование таблиц в базе данных
echo "\n🗄️  === ПРОВЕРКА ТАБЛИЦ БАЗЫ ДАННЫХ ===\n";

global $wpdb;

$tables_to_check = [
    'cryptoschool_points_history' => 'История баллов',
    'cryptoschool_user_streak' => 'Серии пользователей',
    'cryptoschool_user_leaderboard' => 'Рейтинг пользователей'
];

foreach ($tables_to_check as $table => $description) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "✅ $description ($full_table_name): $count записей\n";
    } else {
        echo "❌ $description ($full_table_name): таблица не существует\n";
    }
}

// 4. Проверяем регистрацию хуков
echo "\n🪝 === ПРОВЕРКА ХУКОВ ===\n";

$hook_exists = has_action('cryptoschool_lesson_completed');
if ($hook_exists) {
    echo "✅ Хук 'cryptoschool_lesson_completed' зарегистрирован (приоритет: $hook_exists)\n";
    
    // Получаем список всех функций, привязанных к этому хуку
    global $wp_filter;
    if (isset($wp_filter['cryptoschool_lesson_completed'])) {
        echo "   📋 Привязанные функции:\n";
        foreach ($wp_filter['cryptoschool_lesson_completed']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && is_object($callback['function'][0])) {
                    $class = get_class($callback['function'][0]);
                    $method = $callback['function'][1];
                    echo "      - $class::$method (приоритет: $priority)\n";
                } else {
                    echo "      - " . print_r($callback['function'], true) . " (приоритет: $priority)\n";
                }
            }
        }
    }
} else {
    echo "❌ Хук 'cryptoschool_lesson_completed' НЕ зарегистрирован\n";
}

// 5. Тестируем вызов хука
echo "\n🧪 === ТЕСТИРОВАНИЕ ХУКА ===\n";

$test_user_id = 6;
$test_lesson_id = 60; // trid одного из завершенных уроков

echo "Тестируем вызов: do_action('cryptoschool_lesson_completed', $test_user_id, $test_lesson_id)\n";

// Захватываем лог до вызова
$log_before = file_get_contents('wp-content/debug.log');

// Вызываем хук
do_action('cryptoschool_lesson_completed', $test_user_id, $test_lesson_id);

// Проверяем лог после
sleep(1);
$log_after = file_get_contents('wp-content/debug.log');

if ($log_after !== $log_before) {
    echo "✅ Хук сработал - в логе появились новые записи\n";
    echo "📝 Новые записи в логе:\n";
    $new_lines = substr($log_after, strlen($log_before));
    echo $new_lines . "\n";
} else {
    echo "⚠️  Хук вызван, но новых записей в логе нет\n";
}

// 6. Проверяем результат в базе данных
echo "\n📊 === ПРОВЕРКА РЕЗУЛЬТАТА В БД ===\n";

$points_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_points_history WHERE user_id = %d",
    $test_user_id
));

$streak_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_streak WHERE user_id = %d",
    $test_user_id
));

$leaderboard_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_user_leaderboard WHERE user_id = %d",
    $test_user_id
));

echo "Записи баллов для пользователя $test_user_id: $points_count\n";
echo "Записи серии для пользователя $test_user_id: $streak_exists\n";
echo "Записи рейтинга для пользователя $test_user_id: $leaderboard_exists\n";

if ($points_count > 0 || $streak_exists > 0 || $leaderboard_exists > 0) {
    echo "✅ Хук отработал - данные появились в БД!\n";
} else {
    echo "❌ Хук не создал записи в БД\n";
}

// 7. Итоговые выводы
echo "\n🎯 === ИТОГОВЫЕ ВЫВОДЫ ===\n";

if ($hook_exists && ($points_count > 0 || $streak_exists > 0)) {
    echo "🎉 СИСТЕМА БАЛЛОВ РАБОТАЕТ КОРРЕКТНО!\n";
    echo "✅ Сервис инициализирован\n";
    echo "✅ Хуки зарегистрированы\n";
    echo "✅ Данные записываются в БД\n";
} else {
    echo "❌ СИСТЕМА БАЛЛОВ ТРЕБУЕТ ДОРАБОТКИ\n";
    
    if (!$hook_exists) {
        echo "🔧 Проблема: Хук не зарегистрирован\n";
        echo "💡 Решение: Проверить вызов register_hooks() в сервисе\n";
    }
    
    if ($points_count == 0 && $streak_exists == 0) {
        echo "🔧 Проблема: Данные не записываются в БД\n";
        echo "💡 Решение: Проверить методы репозиториев\n";
    }
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";

if ($is_web_request) {
    echo "</pre>";
}
?>