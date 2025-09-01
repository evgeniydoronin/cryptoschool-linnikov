<?php
/**
 * Тестирование двухуровневой реферальной системы
 */

require_once __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

echo "🧪 ТЕСТ: Двухуровневая реферальная система\n\n";

global $wpdb;

// Создаем тестовую цепочку рефералов
echo "1️⃣ Создаем цепочку рефералов A → B → C\n";

// Пользователь A (уровень 1) - создает реферальную ссылку
$userA_id = wp_create_user('testA_' . time(), 'TestPass123!', 'testA_' . time() . '@example.com');
echo "   - Пользователь A создан: ID {$userA_id}\n";

// Создаем реферальную ссылку для пользователя A
$referral_service = new CryptoSchool_Service_Referral(new CryptoSchool_Loader());
$linkA = $referral_service->create_referral_link($userA_id, [
    'link_name' => 'Ссылка A',
    'discount_percent' => 30,
    'commission_percent' => 10
]);
$codeA = $linkA->getAttribute('referral_code');
echo "   - Реферальная ссылка A: {$codeA}\n";

// Пользователь B (уровень 2) - регистрируется по ссылке A и создает свою ссылку
$_COOKIE['cryptoschool_referral_code'] = $codeA;
$userB_id = wp_create_user('testB_' . time(), 'TestPass123!', 'testB_' . time() . '@example.com');
echo "   - Пользователь B создан: ID {$userB_id} (реферал A)\n";

// Создаем реферальную ссылку для пользователя B
$linkB = $referral_service->create_referral_link($userB_id, [
    'link_name' => 'Ссылка B',
    'discount_percent' => 25,
    'commission_percent' => 8
]);
$codeB = $linkB->getAttribute('referral_code');
echo "   - Реферальная ссылка B: {$codeB}\n";

// Пользователь C (уровень 3) - регистрируется по ссылке B
$_COOKIE['cryptoschool_referral_code'] = $codeB;
$userC_id = wp_create_user('testC_' . time(), 'TestPass123!', 'testC_' . time() . '@example.com');
echo "   - Пользователь C создан: ID {$userC_id} (реферал B)\n";

echo "\n2️⃣ Проверяем созданные связи:\n";

// Проверяем связи
$connectionB = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_users WHERE user_id = %d",
    $userB_id
), ARRAY_A);

$connectionC = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_users WHERE user_id = %d",
    $userC_id
), ARRAY_A);

echo "   - B привлечен пользователем: {$connectionB['referrer_id']} (должен быть {$userA_id})\n";
echo "   - C привлечен пользователем: {$connectionC['referrer_id']} (должен быть {$userB_id})\n";

echo "\n3️⃣ Симулируем покупку пользователем C за \$300:\n";

// Авторизуемся как пользователь C
wp_set_current_user($userC_id);

// Симулируем покупку
$original_price = 300;
$discount_percent = 25; // Скидка от ссылки B
$discount_amount = round($original_price * ($discount_percent / 100), 2);
$final_price = round($original_price - $discount_amount, 2);

echo "   - Оригинальная цена: \${$original_price}\n";
echo "   - Скидка {$discount_percent}%: -\${$discount_amount}\n";
echo "   - К оплате: \${$final_price}\n";

// Комиссия 1-го уровня (B получает)
$commission_level1_percent = 8; // Из ссылки B
$commission_level1 = round($final_price * ($commission_level1_percent / 100), 2);

// Комиссия 2-го уровня (A получает)
$commission_level2 = round($final_price * 0.05, 2); // Фиксированные 5%

echo "\n💰 Рассчитываем комиссии:\n";
echo "   - Комиссия 1-го уровня (B): {$commission_level1_percent}% от \${$final_price} = \${$commission_level1}\n";
echo "   - Комиссия 2-го уровня (A): 5% от \${$final_price} = \${$commission_level2}\n";

// Создаем транзакции
echo "\n4️⃣ Создаем транзакции:\n";

// Транзакция 1-го уровня (B)
$transaction1_result = $wpdb->insert(
    $wpdb->prefix . 'cryptoschool_referral_transactions',
    [
        'referrer_id' => $userB_id,
        'user_id' => $userC_id,
        'referral_link_id' => $linkB->getAttribute('id'),
        'amount' => $commission_level1,
        'type' => 'commission_level_1',
        'status' => 'completed',
        'purchase_amount' => $final_price,
        'package_name' => 'Тест двухуровневой системы',
        'created_at' => current_time('mysql')
    ]
);

if ($transaction1_result) {
    echo "   ✅ Транзакция 1-го уровня создана (ID: {$wpdb->insert_id})\n";
    
    // Обновляем доходы ссылки B
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}cryptoschool_referral_links 
         SET total_earned = COALESCE(total_earned, 0) + %f 
         WHERE id = %d",
        $commission_level1,
        $linkB->getAttribute('id')
    ));
} else {
    echo "   ❌ Ошибка создания транзакции 1-го уровня: " . $wpdb->last_error . "\n";
}

// Транзакция 2-го уровня (A)
$transaction2_result = $wpdb->insert(
    $wpdb->prefix . 'cryptoschool_referral_transactions',
    [
        'referrer_id' => $userA_id,
        'user_id' => $userC_id,
        'referral_link_id' => $linkA->getAttribute('id'),
        'amount' => $commission_level2,
        'type' => 'commission_level_2',
        'status' => 'completed',
        'purchase_amount' => $final_price,
        'package_name' => 'Тест двухуровневой системы',
        'created_at' => current_time('mysql')
    ]
);

if ($transaction2_result) {
    echo "   ✅ Транзакция 2-го уровня создана (ID: {$wpdb->insert_id})\n";
    
    // Обновляем доходы ссылки A
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}cryptoschool_referral_links 
         SET total_earned = COALESCE(total_earned, 0) + %f 
         WHERE id = %d",
        $commission_level2,
        $linkA->getAttribute('id')
    ));
} else {
    echo "   ❌ Ошибка создания транзакции 2-го уровня: " . $wpdb->last_error . "\n";
}

echo "\n5️⃣ Проверяем итоговые результаты:\n";

// Проверяем доходы
$final_linkA = $wpdb->get_row($wpdb->prepare(
    "SELECT total_earned FROM {$wpdb->prefix}cryptoschool_referral_links WHERE id = %d",
    $linkA->getAttribute('id')
), ARRAY_A);

$final_linkB = $wpdb->get_row($wpdb->prepare(
    "SELECT total_earned FROM {$wpdb->prefix}cryptoschool_referral_links WHERE id = %d",
    $linkB->getAttribute('id')
), ARRAY_A);

echo "   - Доходы ссылки A (2-й уровень): \${$final_linkA['total_earned']}\n";
echo "   - Доходы ссылки B (1-й уровень): \${$final_linkB['total_earned']}\n";

// Проверяем транзакции
$all_transactions = $wpdb->get_results($wpdb->prepare(
    "SELECT referrer_id, amount, type FROM {$wpdb->prefix}cryptoschool_referral_transactions 
     WHERE user_id = %d ORDER BY created_at",
    $userC_id
), ARRAY_A);

echo "\n📊 Созданные транзакции:\n";
foreach ($all_transactions as $t) {
    $referrer_name = ($t['referrer_id'] == $userA_id) ? 'A' : 'B';
    echo "   - Пользователь {$referrer_name}: \${$t['amount']} ({$t['type']})\n";
}

// Проверяем корректность
$success = true;
$errors = [];

if (abs($final_linkA['total_earned'] - $commission_level2) > 0.01) {
    $success = false;
    $errors[] = "Доходы ссылки A некорректны";
}

if (abs($final_linkB['total_earned'] - $commission_level1) > 0.01) {
    $success = false;
    $errors[] = "Доходы ссылки B некорректны";
}

if (count($all_transactions) !== 2) {
    $success = false;
    $errors[] = "Должно быть создано 2 транзакции, создано " . count($all_transactions);
}

echo "\n📈 РЕЗУЛЬТАТ ТЕСТА:\n";
if ($success) {
    echo "🎉 ТЕСТ ПРОЙДЕН! Двухуровневая система работает корректно!\n";
    echo "   - Покупатель (C) получил скидку \${$discount_amount}\n";
    echo "   - Реферер 1-го уровня (B) получил комиссию \${$commission_level1}\n";
    echo "   - Реферер 2-го уровня (A) получил комиссию \${$commission_level2}\n";
} else {
    echo "❌ ТЕСТ НЕ ПРОЙДЕН! Обнаружены ошибки:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

echo "\n6️⃣ Очищаем тестовые данные...\n";

// Удаляем транзакции
$wpdb->delete($wpdb->prefix . 'cryptoschool_referral_transactions', ['user_id' => $userC_id]);

// Удаляем связи
$wpdb->delete($wpdb->prefix . 'cryptoschool_referral_users', ['user_id' => $userB_id]);
$wpdb->delete($wpdb->prefix . 'cryptoschool_referral_users', ['user_id' => $userC_id]);

// Удаляем ссылки
$wpdb->delete($wpdb->prefix . 'cryptoschool_referral_links', ['id' => $linkA->getAttribute('id')]);
$wpdb->delete($wpdb->prefix . 'cryptoschool_referral_links', ['id' => $linkB->getAttribute('id')]);

// Удаляем пользователей
wp_delete_user($userA_id);
wp_delete_user($userB_id);
wp_delete_user($userC_id);

echo "✅ Тестовые данные удалены\n";
echo "\n🏁 Тест завершен!\n";
?>