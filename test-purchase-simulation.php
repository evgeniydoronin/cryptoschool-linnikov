<?php
/**
 * Тестовая страница для симуляции покупок с реферальными скидками
 */

// Подключаем WordPress
require_once __DIR__ . '/wp-load.php';

// Проверяем авторизацию
if (!is_user_logged_in()) {
    echo "❌ Для тестирования необходима авторизация\n";
    echo "Авторизуйтесь как пользователь, который регистрировался по реферальной ссылке\n";
    exit;
}

$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

echo "🛒 ТЕСТОВАЯ СТРАНИЦА СИМУЛЯЦИИ ПОКУПКИ\n\n";
echo "👤 Пользователь: {$current_user->display_name} (ID: {$current_user_id})\n";

global $wpdb;

// Проверяем, есть ли у пользователя реферальная связь
$referral_connection = $wpdb->get_row($wpdb->prepare(
    "SELECT ru.*, rl.referral_code, rl.discount_percent, rl.commission_percent, rl.link_name
     FROM {$wpdb->prefix}cryptoschool_referral_users ru
     LEFT JOIN {$wpdb->prefix}cryptoschool_referral_links rl ON ru.referral_link_id = rl.id
     WHERE ru.user_id = %d",
    $current_user_id
), ARRAY_A);

if (!$referral_connection) {
    echo "ℹ️  У вас нет активной реферальной связи\n";
    echo "Для тестирования сначала зарегистрируйтесь по реферальной ссылке\n";
    exit;
}

echo "🔗 Реферальная связь найдена:\n";
echo "   - Реферальный код: {$referral_connection['referral_code']}\n";
echo "   - Скидка: {$referral_connection['discount_percent']}%\n";
echo "   - Комиссия для реферера: {$referral_connection['commission_percent']}%\n";
echo "   - Статус: {$referral_connection['status']}\n\n";

// Обработка POST запроса (симуляция покупки)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_purchase'])) {
    
    $package_name = sanitize_text_field($_POST['package_name'] ?? 'Базовый курс');
    $original_price = floatval($_POST['original_price'] ?? 100);
    
    echo "🚀 СИМУЛЯЦИЯ ПОКУПКИ:\n";
    echo "   - Пакет: {$package_name}\n";
    echo "   - Оригинальная цена: \${$original_price}\n";
    
    // Применяем реферальную скидку
    $discount_percent = floatval($referral_connection['discount_percent']);
    $discount_amount = round($original_price * ($discount_percent / 100), 2);
    $final_price = round($original_price - $discount_amount, 2);
    
    echo "   - Реферальная скидка {$discount_percent}%: -\${$discount_amount}\n";
    echo "   - К оплате: \${$final_price}\n\n";
    
    // Симулируем успешную оплату и начисление комиссий
    echo "💳 Симулируем успешную оплату...\n";
    
    // Рассчитываем комиссию первого уровня
    $commission_percent = floatval($referral_connection['commission_percent']);
    $commission_amount = round($final_price * ($commission_percent / 100), 2);
    
    echo "💰 Рассчитываем комиссии:\n";
    echo "   - Комиссия 1-го уровня ({$commission_percent}% от \${$final_price}): \${$commission_amount}\n";
    
    // Создаем транзакцию первого уровня
    $transaction_result = $wpdb->insert(
        $wpdb->prefix . 'cryptoschool_referral_transactions',
        [
            'referrer_id' => $referral_connection['referrer_id'],
            'user_id' => $current_user_id,
            'referral_link_id' => $referral_connection['referral_link_id'],
            'amount' => $commission_amount,
            'type' => 'commission_level_1',
            'status' => 'completed',
            'purchase_amount' => $final_price,
            'package_name' => $package_name,
            'created_at' => current_time('mysql')
        ],
        ['%d', '%d', '%d', '%f', '%s', '%s', '%f', '%s', '%s']
    );
    
    if ($transaction_result) {
        $transaction_id = $wpdb->insert_id;
        echo "✅ Транзакция создана (ID: {$transaction_id})\n";
        
        // Обновляем total_earned в реферальной ссылке
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}cryptoschool_referral_links 
             SET total_earned = COALESCE(total_earned, 0) + %f 
             WHERE id = %d",
            $commission_amount,
            $referral_connection['referral_link_id']
        ));
        
        echo "✅ Обновлены доходы реферальной ссылки (+\${$commission_amount})\n";
        
        // Проверяем второй уровень (реферер реферера)
        $second_level_referrer = $wpdb->get_row($wpdb->prepare(
            "SELECT ru.*, rl.referral_code 
             FROM {$wpdb->prefix}cryptoschool_referral_users ru
             LEFT JOIN {$wpdb->prefix}cryptoschool_referral_links rl ON ru.referral_link_id = rl.id
             WHERE ru.user_id = %d",
            $referral_connection['referrer_id']
        ), ARRAY_A);
        
        if ($second_level_referrer) {
            // Комиссия второго уровня - фиксированные 5%
            $second_level_commission = round($final_price * 0.05, 2);
            
            echo "   - Комиссия 2-го уровня (5% от \${$final_price}): \${$second_level_commission}\n";
            
            $second_transaction = $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_referral_transactions',
                [
                    'referrer_id' => $second_level_referrer['referrer_id'],
                    'user_id' => $current_user_id,
                    'referral_link_id' => $second_level_referrer['referral_link_id'],
                    'amount' => $second_level_commission,
                    'type' => 'commission_level_2',
                    'status' => 'completed',
                    'purchase_amount' => $final_price,
                    'package_name' => $package_name,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%f', '%s', '%s', '%f', '%s', '%s']
            );
            
            if ($second_transaction) {
                echo "✅ Транзакция 2-го уровня создана (ID: {$wpdb->insert_id})\n";
                
                // Обновляем доходы ссылки второго уровня
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}cryptoschool_referral_links 
                     SET total_earned = COALESCE(total_earned, 0) + %f 
                     WHERE id = %d",
                    $second_level_commission,
                    $second_level_referrer['referral_link_id']
                ));
                
                echo "✅ Обновлены доходы ссылки 2-го уровня (+\${$second_level_commission})\n";
            }
        } else {
            echo "   - Реферер 2-го уровня не найден\n";
        }
        
        // Обновляем статус пользователя
        $wpdb->update(
            $wpdb->prefix . 'cryptoschool_referral_users',
            [
                'status' => 'purchased',
                'purchase_date' => current_time('mysql'),
                'purchase_amount' => $final_price
            ],
            ['user_id' => $current_user_id],
            ['%s', '%s', '%f'],
            ['%d']
        );
        
        echo "✅ Статус пользователя обновлен на 'purchased'\n";
        
        echo "\n🎉 ПОКУПКА СИМУЛИРОВАНА УСПЕШНО!\n";
        echo "💡 Проверьте таблицу referral_transactions для просмотра созданных транзакций\n\n";
        
    } else {
        echo "❌ Ошибка при создании транзакции: " . $wpdb->last_error . "\n";
    }
}

// Показываем форму для симуляции
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Симуляция покупки</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        .btn { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #005a87; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #007cba; }
        .discount-info { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .price-breakdown { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .total-price { font-size: 24px; font-weight: bold; color: #28a745; }
        .original-price { text-decoration: line-through; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Симуляция покупки с реферальной скидкой</h1>
        
        <div class="info-box">
            <strong>Ваша реферальная связь:</strong><br>
            Код: <?= htmlspecialchars($referral_connection['referral_code']) ?><br>
            Скидка: <?= htmlspecialchars($referral_connection['discount_percent']) ?>%<br>
            Статус: <?= htmlspecialchars($referral_connection['status']) ?>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="package_name">Выберите пакет:</label>
                <select name="package_name" id="package_name">
                    <option value="Базовый курс">Базовый курс</option>
                    <option value="Продвинутый курс">Продвинутый курс</option>
                    <option value="VIP курс">VIP курс</option>
                    <option value="Комплексная программа">Комплексная программа</option>
                </select>
            </div>

            <div class="form-group">
                <label for="original_price">Цена (USD):</label>
                <input type="number" name="original_price" id="original_price" value="100" step="0.01" min="1">
            </div>

            <div class="price-breakdown" id="price-breakdown">
                <div>Оригинальная цена: $<span id="original-amount">100.00</span></div>
                <div>Реферальная скидка (<?= $referral_connection['discount_percent'] ?>%): -$<span id="discount-amount">0.00</span></div>
                <hr>
                <div class="total-price">К оплате: $<span id="final-amount">100.00</span></div>
            </div>

            <div class="discount-info">
                <strong>💰 Начисления:</strong><br>
                Комиссия реферера (<?= $referral_connection['commission_percent'] ?>%): $<span id="commission-amount">0.00</span><br>
                Комиссия 2-го уровня (5%): $<span id="second-level-commission">0.00</span>
            </div>

            <button type="submit" name="simulate_purchase" class="btn">
                🚀 Симулировать покупку
            </button>
        </form>

        <script>
            function updateCalculations() {
                const originalPrice = parseFloat(document.getElementById('original_price').value) || 0;
                const discountPercent = <?= $referral_connection['discount_percent'] ?>;
                const commissionPercent = <?= $referral_connection['commission_percent'] ?>;
                
                const discountAmount = originalPrice * (discountPercent / 100);
                const finalPrice = originalPrice - discountAmount;
                const commissionAmount = finalPrice * (commissionPercent / 100);
                const secondLevelCommission = finalPrice * 0.05;
                
                document.getElementById('original-amount').textContent = originalPrice.toFixed(2);
                document.getElementById('discount-amount').textContent = discountAmount.toFixed(2);
                document.getElementById('final-amount').textContent = finalPrice.toFixed(2);
                document.getElementById('commission-amount').textContent = commissionAmount.toFixed(2);
                document.getElementById('second-level-commission').textContent = secondLevelCommission.toFixed(2);
            }
            
            document.getElementById('original_price').addEventListener('input', updateCalculations);
            updateCalculations(); // Вызываем при загрузке страницы
        </script>
    </div>
</body>
</html>

<?php
// Показываем последние транзакции пользователя
$recent_transactions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_transactions 
     WHERE user_id = %d 
     ORDER BY created_at DESC 
     LIMIT 5",
    $current_user_id
), ARRAY_A);

if ($recent_transactions) {
    echo "\n📊 ПОСЛЕДНИЕ ТРАНЗАКЦИИ:\n";
    foreach ($recent_transactions as $transaction) {
        echo "   - {$transaction['created_at']}: \${$transaction['amount']} ({$transaction['type']}) - {$transaction['status']}\n";
    }
}

echo "\n💡 Для просмотра в браузере откройте: http://localhost:8080/test-purchase-simulation.php\n";
?>