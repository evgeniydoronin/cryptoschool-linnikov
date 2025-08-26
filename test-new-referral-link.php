<?php
/**
 * Тестирование создания новой реферальной ссылки через API
 */

// Подключаем WordPress
require_once __DIR__ . '/wp-load.php';

// Проверяем, что пользователь авторизован
if (!is_user_logged_in()) {
    echo "❌ Необходима авторизация\n";
    exit;
}

$current_user_id = get_current_user_id();
echo "🔍 Тестируем создание реферальной ссылки для пользователя ID: {$current_user_id}\n\n";

try {
    // Инициализируем сервис реферальной системы
    $referral_service = new CryptoSchool_Service_Referral();
    
    echo "✅ Сервис реферальной системы инициализирован\n";
    
    // Тестовые данные
    $test_data = array(
        'link_name' => 'Тестовая ссылка через API',
        'discount_percent' => 25.0,
        'commission_percent' => 15.0
    );
    
    echo "📝 Данные для создания:\n";
    echo "   - Название: {$test_data['link_name']}\n";
    echo "   - Скидка: {$test_data['discount_percent']}%\n";
    echo "   - Комиссия: {$test_data['commission_percent']}%\n\n";
    
    // Создаем ссылку
    echo "🚀 Создаем реферальную ссылку...\n";
    
    $new_link = $referral_service->create_referral_link(
        $current_user_id,
        $test_data['link_name'],
        $test_data['discount_percent'],
        $test_data['commission_percent']
    );
    
    if ($new_link) {
        echo "✅ Ссылка создана успешно!\n\n";
        
        echo "📊 Данные новой ссылки:\n";
        echo "   - ID: " . $new_link->getAttribute('id') . "\n";
        echo "   - Код: " . $new_link->getAttribute('referral_code') . "\n";
        echo "   - Название: " . $new_link->getAttribute('link_name') . "\n";
        echo "   - Скидка: " . $new_link->getAttribute('discount_percent') . "%\n";
        echo "   - Комиссия: " . $new_link->getAttribute('commission_percent') . "%\n";
        echo "   - URL: " . site_url('/ref/' . $new_link->getAttribute('referral_code')) . "\n";
        echo "   - Создана: " . $new_link->getAttribute('created_at') . "\n\n";
        
        // Проверяем сохранение в БД
        global $wpdb;
        $saved_link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE id = %d",
            $new_link->getAttribute('id')
        ), ARRAY_A);
        
        if ($saved_link) {
            echo "✅ Ссылка успешно сохранена в базе данных\n";
            echo "📝 Данные из БД:\n";
            echo "   - ID: " . $saved_link['id'] . "\n";
            echo "   - User ID: " . $saved_link['user_id'] . "\n";
            echo "   - Код: " . $saved_link['referral_code'] . "\n";
            echo "   - Название: " . $saved_link['link_name'] . "\n";
        } else {
            echo "❌ Ошибка: ссылка не найдена в БД\n";
        }
        
    } else {
        echo "❌ Ошибка при создании ссылки\n";
    }
    
} catch (Exception $e) {
    echo "❌ Исключение: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🏁 Тест завершен\n";