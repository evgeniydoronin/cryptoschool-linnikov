<?php
/**
 * Тестирование обновления счетчиков конверсии при регистрации
 */

// Подключаем WordPress
require_once __DIR__ . '/wp-load.php';

// Подключаем функции пользователей
require_once ABSPATH . 'wp-admin/includes/user.php';

echo "🧪 ТЕСТ: Обновление счетчиков конверсии при регистрации\n\n";

global $wpdb;

// Получаем реферальную ссылку для тестирования
$referral_code = 'REF6D4416E'; // Код пользователя 6
$referral_link = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE referral_code = %s",
    $referral_code
), ARRAY_A);

if (!$referral_link) {
    echo "❌ ОШИБКА: Реферальная ссылка с кодом {$referral_code} не найдена!\n";
    exit;
}

echo "📊 СЧЕТЧИКИ ДО РЕГИСТРАЦИИ:\n";
echo "   - Клики: {$referral_link['clicks_count']}\n";
echo "   - Конверсии: {$referral_link['conversions_count']}\n";
echo "   - Процент конверсии: {$referral_link['conversion_rate']}%\n\n";

// Симулируем наличие реферального кода в cookie
$_COOKIE['cryptoschool_referral_code'] = $referral_code;

echo "1️⃣ Создаем тестового пользователя с реферальным кодом...\n";

$test_username = 'testuser_' . time();
$test_email = 'test_' . time() . '@example.com';
$test_password = 'TestPassword123!';

$user_id = wp_create_user($test_username, $test_password, $test_email);

if (is_wp_error($user_id)) {
    echo "❌ ОШИБКА при создании пользователя: " . $user_id->get_error_message() . "\n";
    exit;
}

echo "✅ Пользователь создан: ID = {$user_id}\n\n";

// Проверяем обновленные счетчики
echo "2️⃣ Проверяем обновленные счетчики...\n";

$updated_link = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE referral_code = %s",
    $referral_code
), ARRAY_A);

echo "📊 СЧЕТЧИКИ ПОСЛЕ РЕГИСТРАЦИИ:\n";
echo "   - Клики: {$updated_link['clicks_count']}\n";
echo "   - Конверсии: {$updated_link['conversions_count']}\n";
echo "   - Процент конверсии: {$updated_link['conversion_rate']}%\n\n";

// Проверяем изменения
$clicks_diff = $updated_link['clicks_count'] - $referral_link['clicks_count'];
$conversions_diff = $updated_link['conversions_count'] - $referral_link['conversions_count'];
$rate_diff = $updated_link['conversion_rate'] - $referral_link['conversion_rate'];

echo "📈 ИЗМЕНЕНИЯ:\n";
echo "   - Клики: " . ($clicks_diff >= 0 ? "+{$clicks_diff}" : $clicks_diff) . "\n";
echo "   - Конверсии: " . ($conversions_diff >= 0 ? "+{$conversions_diff}" : $conversions_diff) . "\n";
echo "   - Процент: " . ($rate_diff >= 0 ? "+{$rate_diff}" : $rate_diff) . "%\n\n";

// Проверяем создание связи
$referral_user = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_users WHERE user_id = %d",
    $user_id
), ARRAY_A);

if ($referral_user) {
    echo "✅ Реферальная связь создана:\n";
    echo "   - ID связи: {$referral_user['id']}\n";
    echo "   - Реферер: {$referral_user['referrer_id']}\n";
    echo "   - Статус: {$referral_user['status']}\n\n";
} else {
    echo "❌ Реферальная связь НЕ создана!\n\n";
}

// Проверяем ожидаемые результаты
$success = true;
$errors = [];

if ($conversions_diff !== 1) {
    $success = false;
    $errors[] = "Конверсии должны увеличиться на 1, но увеличились на {$conversions_diff}";
}

$expected_rate = round((($referral_link['conversions_count'] + 1) / max(1, $referral_link['clicks_count'])) * 100, 2);
if (abs($updated_link['conversion_rate'] - $expected_rate) > 0.01) {
    $success = false;
    $errors[] = "Процент конверсии должен быть {$expected_rate}%, но стал {$updated_link['conversion_rate']}%";
}

if (!$referral_user) {
    $success = false;
    $errors[] = "Реферальная связь не была создана";
}

// Выводим результат
if ($success) {
    echo "🎉 ТЕСТ ПРОЙДЕН! Счетчики конверсии обновляются корректно!\n";
} else {
    echo "❌ ТЕСТ НЕ ПРОЙДЕН! Обнаружены ошибки:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

// Очищаем тестовые данные
echo "\n3️⃣ Очищаем тестовые данные...\n";

if ($referral_user) {
    $wpdb->delete(
        $wpdb->prefix . 'cryptoschool_referral_users',
        ['id' => $referral_user['id']]
    );
    echo "✅ Связь в referral_users удалена\n";
}

wp_delete_user($user_id);
echo "✅ Тестовый пользователь удален\n";

// Восстанавливаем исходные счетчики
$wpdb->update(
    $wpdb->prefix . 'cryptoschool_referral_links',
    [
        'conversions_count' => $referral_link['conversions_count'],
        'conversion_rate' => $referral_link['conversion_rate']
    ],
    ['id' => $referral_link['id']]
);
echo "✅ Счетчики восстановлены\n";

echo "\n🏁 Тест завершен!\n";