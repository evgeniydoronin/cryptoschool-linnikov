<?php
/**
 * Обработчики настроек Crypto Pay
 *
 * @package CryptoSchool
 * @subpackage Admin
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX обработчик для проверки подключения к Crypto Pay
 */
add_action('wp_ajax_test_cryptopay_connection', function() {
    // Проверка nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptopay_test_nonce')) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }

    // Проверка прав
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Недостаточно прав');
        return;
    }

    $token = sanitize_text_field($_POST['token'] ?? '');
    $testnet = !empty($_POST['testnet']);

    if (empty($token)) {
        wp_send_json_error('Токен не указан');
        return;
    }

    // Определяем URL API
    $api_url = $testnet
        ? 'https://testnet-pay.crypt.bot/api'
        : 'https://pay.crypt.bot/api';

    // Делаем тестовый запрос к API
    $response = wp_remote_get($api_url . '/getMe', [
        'headers' => [
            'Crypto-Pay-API-Token' => $token,
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Ошибка подключения: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['ok'])) {
        wp_send_json_error('Неверный ответ от API');
        return;
    }

    if (!$data['ok']) {
        $error_message = $data['error']['name'] ?? 'Неизвестная ошибка';
        wp_send_json_error('Ошибка API: ' . $error_message);
        return;
    }

    // Сохраняем настройки если тест успешен
    update_option('cryptoschool_cryptopay_api_token', $token);
    update_option('cryptoschool_cryptopay_testnet_mode', $testnet);

    wp_send_json_success('Подключение успешно! Приложение: ' . ($data['result']['name'] ?? 'Unknown'));
});

/**
 * AJAX обработчик сохранения настроек платежей
 */
add_action('wp_ajax_save_payment_settings', function() {
    // Проверка nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'payment_settings_nonce')) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }

    // Проверка прав
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Недостаточно прав');
        return;
    }

    // Сохраняем настройки Crypto Pay
    if (isset($_POST['crypto_gateway'])) {
        update_option('cryptoschool_crypto_gateway', sanitize_text_field($_POST['crypto_gateway']));
    }

    if (isset($_POST['cryptopay_api_token'])) {
        update_option('cryptoschool_cryptopay_api_token', sanitize_text_field($_POST['cryptopay_api_token']));
    }

    if (isset($_POST['cryptopay_testnet_mode'])) {
        update_option('cryptoschool_cryptopay_testnet_mode', !empty($_POST['cryptopay_testnet_mode']));
    } else {
        update_option('cryptoschool_cryptopay_testnet_mode', false);
    }

    if (isset($_POST['admin_telegram_id'])) {
        update_option('cryptoschool_admin_telegram_id', sanitize_text_field($_POST['admin_telegram_id']));
    }

    if (isset($_POST['notification_bot_token'])) {
        update_option('cryptoschool_notification_bot_token', sanitize_text_field($_POST['notification_bot_token']));
    }

    // Сохраняем валюту
    if (isset($_POST['currency'])) {
        update_option('cryptoschool_currency', sanitize_text_field($_POST['currency']));
    }

    // Сохраняем методы оплаты
    if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
        $payment_methods = array_map('sanitize_text_field', $_POST['payment_methods']);
        update_option('cryptoschool_payment_methods', $payment_methods);
    } else {
        update_option('cryptoschool_payment_methods', []);
    }

    wp_send_json_success('Настройки сохранены');
});

/**
 * Загрузка сохраненных настроек
 */
add_filter('cryptoschool_payment_settings', function($settings) {
    $settings['currency'] = get_option('cryptoschool_currency', 'USD');
    $settings['payment_methods'] = get_option('cryptoschool_payment_methods', ['crypto']);
    $settings['crypto_gateway'] = get_option('cryptoschool_crypto_gateway', 'cryptopay');
    $settings['cryptopay_api_token'] = get_option('cryptoschool_cryptopay_api_token', '');
    $settings['cryptopay_testnet_mode'] = get_option('cryptoschool_cryptopay_testnet_mode', true);
    $settings['admin_telegram_id'] = get_option('cryptoschool_admin_telegram_id', '');
    $settings['notification_bot_token'] = get_option('cryptoschool_notification_bot_token', '');

    return $settings;
});