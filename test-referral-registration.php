<?php
/**
 * Тестирование процесса регистрации с реферальным кодом
 */

// Подключаем WordPress
require_once __DIR__ . '/wp-load.php';

// Подключаем функции пользователей для wp_delete_user()
require_once ABSPATH . 'wp-admin/includes/user.php';

echo "🧪 ТЕСТ: Процесс регистрации с реферальным кодом\n\n";

// Симулируем наличие реферального кода в cookie
$referral_code = 'REF6D4416E'; // Код пользователя 6
$_COOKIE['cryptoschool_referral_code'] = $referral_code;

echo "1️⃣ Симулируем наличие cookie: cryptoschool_referral_code = {$referral_code}\n";

// Проверяем, что ссылка существует
global $wpdb;
$referral_link = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE referral_code = %s",
    $referral_code
), ARRAY_A);

if (!$referral_link) {
    echo "❌ ОШИБКА: Реферальная ссылка с кодом {$referral_code} не найдена!\n";
    exit;
}

echo "✅ Найдена реферальная ссылка:\n";
echo "   - ID: {$referral_link['id']}\n";
echo "   - Пользователь: {$referral_link['user_id']}\n";
echo "   - Название: {$referral_link['link_name']}\n";
echo "   - Скидка: {$referral_link['discount_percent']}%\n";
echo "   - Комиссия: {$referral_link['commission_percent']}%\n\n";

// Создаем тестового пользователя
echo "2️⃣ Создаем тестового пользователя...\n";

$test_username = 'testuser_' . time();
$test_email = 'test_' . time() . '@example.com';
$test_password = 'TestPassword123!';

$user_id = wp_create_user($test_username, $test_password, $test_email);

if (is_wp_error($user_id)) {
    echo "❌ ОШИБКА при создании пользователя: " . $user_id->get_error_message() . "\n";
    exit;
}

echo "✅ Пользователь создан: ID = {$user_id}, email = {$test_email}\n\n";

// Проверяем, создалась ли связь в таблице referral_users
echo "3️⃣ Проверяем создание связи в БД...\n";

$referral_user = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_users WHERE user_id = %d",
    $user_id
), ARRAY_A);

if (!$referral_user) {
    echo "❌ ОШИБКА: Связь в таблице referral_users не создана!\n";
    
    // Проверим логи WordPress
    echo "\n📝 Проверяем логи...\n";
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $referral_logs = array_filter(explode("\n", $logs), function($line) use ($user_id) {
            return strpos($line, 'CryptoSchool Referral') !== false && strpos($line, (string)$user_id) !== false;
        });
        
        if (!empty($referral_logs)) {
            echo "Найденные логи реферальной системы:\n";
            foreach (array_slice($referral_logs, -5) as $log) {
                echo "  " . $log . "\n";
            }
        } else {
            echo "Логи реферальной системы не найдены\n";
        }
    }
    
} else {
    echo "✅ УСПЕХ! Создана связь в referral_users:\n";
    echo "   - ID связи: {$referral_user['id']}\n";
    echo "   - Реферер: {$referral_user['referrer_id']}\n";
    echo "   - Новый пользователь: {$referral_user['user_id']}\n";
    echo "   - Реферальная ссылка: {$referral_user['referral_link_id']}\n";
    echo "   - Дата регистрации: {$referral_user['registration_date']}\n";
    echo "   - Статус: {$referral_user['status']}\n\n";
    
    echo "🎉 ТЕСТ ПРОЙДЕН! Реферальная регистрация работает корректно!\n";
}

// Очищаем тестовые данные
echo "\n4️⃣ Очищаем тестовые данные...\n";

// Удаляем связь из referral_users
if ($referral_user) {
    $wpdb->delete(
        $wpdb->prefix . 'cryptoschool_referral_users',
        ['id' => $referral_user['id']]
    );
    echo "✅ Связь в referral_users удалена\n";
}

// Удаляем тестового пользователя
wp_delete_user($user_id);
echo "✅ Тестовый пользователь удален\n";

echo "\n🏁 Тест завершен!\n";