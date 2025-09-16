<?php
/**
 * Шаблон для страницы настроек плагина
 *
 * @package CryptoSchool
 * @subpackage Admin\Views
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Получение настроек плагина
$settings = apply_filters('cryptoschool_payment_settings', []);

// Добавляем значения по умолчанию если не установлены
$settings = wp_parse_args($settings, [
    'crypto_gateway' => 'cryptopay',
    'cryptopay_api_token' => '',
    'cryptopay_testnet_mode' => 1,
    'admin_telegram_id' => '',
    'notification_bot_token' => '',
]);
?>

<div class="wrap cryptoschool-admin">
    <h1 class="wp-heading-inline"><?php _e('Настройки', 'cryptoschool'); ?></h1>
    
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p><?php _e('Здесь вы можете настроить параметры плагина, включая интеграцию с Telegram, платежными системами и другими компонентами.', 'cryptoschool'); ?></p>
    </div>
    
    <div class="cryptoschool-admin-tabs">
        <ul class="cryptoschool-admin-tabs-nav">
            <li class="active"><a href="#general"><?php _e('Общие', 'cryptoschool'); ?></a></li>
            <li><a href="#telegram"><?php _e('Telegram', 'cryptoschool'); ?></a></li>
            <li><a href="#payments"><?php _e('Платежи', 'cryptoschool'); ?></a></li>
            <li><a href="#referral"><?php _e('Реферальная система', 'cryptoschool'); ?></a></li>
            <li><a href="#email"><?php _e('Email-уведомления', 'cryptoschool'); ?></a></li>
        </ul>
        
        <div class="cryptoschool-admin-tabs-content">
            <!-- Вкладка "Общие" -->
            <div id="general" class="cryptoschool-admin-tab active">
                <form id="general-settings-form" method="post" action="">
                    <div class="cryptoschool-admin-card">
                        <h2><?php _e('Общие настройки', 'cryptoschool'); ?></h2>
                        
                        <div class="cryptoschool-admin-card-content">
                            <div class="cryptoschool-admin-form-row">
                                <label for="site_name"><?php _e('Название сайта:', 'cryptoschool'); ?></label>
                                <input type="text" id="site_name" name="site_name" class="regular-text" value="<?php echo isset($settings['site_name']) ? esc_attr($settings['site_name']) : ''; ?>">
                                <p class="description"><?php _e('Название сайта, которое будет использоваться в email-уведомлениях и других сообщениях.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="support_email"><?php _e('Email поддержки:', 'cryptoschool'); ?></label>
                                <input type="email" id="support_email" name="support_email" class="regular-text" value="<?php echo isset($settings['support_email']) ? esc_attr($settings['support_email']) : ''; ?>">
                                <p class="description"><?php _e('Email-адрес для связи с поддержкой.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="default_language"><?php _e('Язык по умолчанию:', 'cryptoschool'); ?></label>
                                <select id="default_language" name="default_language" class="regular-text">
                                    <option value="ru" <?php selected(isset($settings['default_language']) ? $settings['default_language'] : '', 'ru'); ?>><?php _e('Русский', 'cryptoschool'); ?></option>
                                    <option value="ua" <?php selected(isset($settings['default_language']) ? $settings['default_language'] : '', 'ua'); ?>><?php _e('Украинский', 'cryptoschool'); ?></option>
                                </select>
                                <p class="description"><?php _e('Язык по умолчанию для новых пользователей.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="auto_language_detection"><?php _e('Автоопределение языка:', 'cryptoschool'); ?></label>
                                <input type="checkbox" id="auto_language_detection" name="auto_language_detection" value="1" <?php checked(isset($settings['auto_language_detection']) ? $settings['auto_language_detection'] : 0, 1); ?>>
                                <p class="description"><?php _e('Автоматически определять язык пользователя по геолокации.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="debug_mode"><?php _e('Режим отладки:', 'cryptoschool'); ?></label>
                                <input type="checkbox" id="debug_mode" name="debug_mode" value="1" <?php checked(isset($settings['debug_mode']) ? $settings['debug_mode'] : 0, 1); ?>>
                                <p class="description"><?php _e('Включить режим отладки для записи дополнительной информации в логи.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Сохранить настройки', 'cryptoschool'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Вкладка "Telegram" -->
            <div id="telegram" class="cryptoschool-admin-tab">
                <form id="telegram-settings-form" method="post" action="">
                    <div class="cryptoschool-admin-card">
                        <h2><?php _e('Настройки Telegram', 'cryptoschool'); ?></h2>
                        
                        <div class="cryptoschool-admin-card-content">
                            <h3><?php _e('Платежный бот', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="payment_bot_token"><?php _e('Токен платежного бота:', 'cryptoschool'); ?></label>
                                <input type="text" id="payment_bot_token" name="payment_bot_token" class="regular-text" value="<?php echo isset($settings['payment_bot_token']) ? esc_attr($settings['payment_bot_token']) : ''; ?>">
                                <p class="description"><?php _e('Токен бота, полученный от @BotFather.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="payment_bot_username"><?php _e('Имя пользователя платежного бота:', 'cryptoschool'); ?></label>
                                <input type="text" id="payment_bot_username" name="payment_bot_username" class="regular-text" value="<?php echo isset($settings['payment_bot_username']) ? esc_attr($settings['payment_bot_username']) : ''; ?>">
                                <p class="description"><?php _e('Имя пользователя бота без символа @.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="payment_bot_webhook"><?php _e('URL для вебхука платежного бота:', 'cryptoschool'); ?></label>
                                <input type="text" id="payment_bot_webhook" name="payment_bot_webhook" class="regular-text" value="<?php echo esc_url(home_url('/wp-json/cryptoschool/v1/telegram/payment-webhook')); ?>" readonly>
                                <p class="description"><?php _e('URL для настройки вебхука в Telegram Bot API.', 'cryptoschool'); ?></p>
                                <button type="button" class="button" id="set-payment-webhook"><?php _e('Установить вебхук', 'cryptoschool'); ?></button>
                            </div>
                            
                            <h3><?php _e('Бот поддержки', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="support_bot_token"><?php _e('Токен бота поддержки:', 'cryptoschool'); ?></label>
                                <input type="text" id="support_bot_token" name="support_bot_token" class="regular-text" value="<?php echo isset($settings['support_bot_token']) ? esc_attr($settings['support_bot_token']) : ''; ?>">
                                <p class="description"><?php _e('Токен бота, полученный от @BotFather.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="support_bot_username"><?php _e('Имя пользователя бота поддержки:', 'cryptoschool'); ?></label>
                                <input type="text" id="support_bot_username" name="support_bot_username" class="regular-text" value="<?php echo isset($settings['support_bot_username']) ? esc_attr($settings['support_bot_username']) : ''; ?>">
                                <p class="description"><?php _e('Имя пользователя бота без символа @.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="support_bot_webhook"><?php _e('URL для вебхука бота поддержки:', 'cryptoschool'); ?></label>
                                <input type="text" id="support_bot_webhook" name="support_bot_webhook" class="regular-text" value="<?php echo esc_url(home_url('/wp-json/cryptoschool/v1/telegram/support-webhook')); ?>" readonly>
                                <p class="description"><?php _e('URL для настройки вебхука в Telegram Bot API.', 'cryptoschool'); ?></p>
                                <button type="button" class="button" id="set-support-webhook"><?php _e('Установить вебхук', 'cryptoschool'); ?></button>
                            </div>
                            
                            <h3><?php _e('Приватные группы', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="telegram_api_id"><?php _e('API ID:', 'cryptoschool'); ?></label>
                                <input type="text" id="telegram_api_id" name="telegram_api_id" class="regular-text" value="<?php echo isset($settings['telegram_api_id']) ? esc_attr($settings['telegram_api_id']) : ''; ?>">
                                <p class="description"><?php _e('API ID, полученный с https://my.telegram.org.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="telegram_api_hash"><?php _e('API Hash:', 'cryptoschool'); ?></label>
                                <input type="text" id="telegram_api_hash" name="telegram_api_hash" class="regular-text" value="<?php echo isset($settings['telegram_api_hash']) ? esc_attr($settings['telegram_api_hash']) : ''; ?>">
                                <p class="description"><?php _e('API Hash, полученный с https://my.telegram.org.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="telegram_phone"><?php _e('Номер телефона:', 'cryptoschool'); ?></label>
                                <input type="text" id="telegram_phone" name="telegram_phone" class="regular-text" value="<?php echo isset($settings['telegram_phone']) ? esc_attr($settings['telegram_phone']) : ''; ?>">
                                <p class="description"><?php _e('Номер телефона администратора групп в международном формате.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Сохранить настройки', 'cryptoschool'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Вкладка "Платежи" -->
            <div id="payments" class="cryptoschool-admin-tab">
                <form id="payments-settings-form" method="post" action="">
                    <div class="cryptoschool-admin-card">
                        <h2><?php _e('Настройки платежей', 'cryptoschool'); ?></h2>
                        
                        <div class="cryptoschool-admin-card-content">
                            <h3><?php _e('Общие настройки', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="currency"><?php _e('Валюта:', 'cryptoschool'); ?></label>
                                <select id="currency" name="currency" class="regular-text">
                                    <option value="USD" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'USD'); ?>>USD</option>
                                    <option value="EUR" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'EUR'); ?>>EUR</option>
                                    <option value="UAH" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'UAH'); ?>>UAH</option>
                                    <option value="RUB" <?php selected(isset($settings['currency']) ? $settings['currency'] : '', 'RUB'); ?>>RUB</option>
                                </select>
                                <p class="description"><?php _e('Основная валюта для платежей.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="payment_methods"><?php _e('Методы оплаты:', 'cryptoschool'); ?></label>
                                <div>
                                    <label>
                                        <input type="checkbox" name="payment_methods[]" value="crypto" <?php checked(isset($settings['payment_methods']) && in_array('crypto', $settings['payment_methods']), true); ?>>
                                        <?php _e('Криптовалюта', 'cryptoschool'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="payment_methods[]" value="card" <?php checked(isset($settings['payment_methods']) && in_array('card', $settings['payment_methods']), true); ?>>
                                        <?php _e('Банковская карта', 'cryptoschool'); ?>
                                    </label>
                                </div>
                                <p class="description"><?php _e('Доступные методы оплаты.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <h3><?php _e('Криптовалютные платежи', 'cryptoschool'); ?></h3>

                            <div class="cryptoschool-admin-form-row">
                                <label for="crypto_gateway"><?php _e('Платежный шлюз:', 'cryptoschool'); ?></label>
                                <select id="crypto_gateway" name="crypto_gateway" class="regular-text">
                                    <option value="cryptopay" <?php selected(isset($settings['crypto_gateway']) ? $settings['crypto_gateway'] : '', 'cryptopay'); ?>>Crypto Pay (Telegram)</option>
                                    <option value="coinbase" <?php selected(isset($settings['crypto_gateway']) ? $settings['crypto_gateway'] : '', 'coinbase'); ?>>Coinbase Commerce</option>
                                    <option value="cryptocloud" <?php selected(isset($settings['crypto_gateway']) ? $settings['crypto_gateway'] : '', 'cryptocloud'); ?>>CryptoCloud</option>
                                    <option value="binance" <?php selected(isset($settings['crypto_gateway']) ? $settings['crypto_gateway'] : '', 'binance'); ?>>Binance Pay</option>
                                </select>
                                <p class="description"><?php _e('Платежный шлюз для криптовалютных платежей.', 'cryptoschool'); ?></p>
                            </div>

                            <!-- Crypto Pay настройки -->
                            <div class="crypto-gateway cryptopay" <?php echo !isset($settings['crypto_gateway']) || $settings['crypto_gateway'] !== 'cryptopay' ? 'style="display: none;"' : ''; ?>>
                                <h4><?php _e('Настройки Crypto Pay', 'cryptoschool'); ?></h4>

                                <div class="cryptoschool-admin-form-row">
                                    <label for="cryptopay_api_token"><?php _e('API Token:', 'cryptoschool'); ?></label>
                                    <input type="text" id="cryptopay_api_token" name="cryptopay_api_token" class="regular-text" value="<?php echo isset($settings['cryptopay_api_token']) ? esc_attr($settings['cryptopay_api_token']) : ''; ?>">
                                    <p class="description"><?php _e('Токен от @CryptoBot или @CryptoTestnetBot', 'cryptoschool'); ?></p>
                                </div>

                                <div class="cryptoschool-admin-form-row">
                                    <label for="cryptopay_testnet_mode"><?php _e('Тестовый режим:', 'cryptoschool'); ?></label>
                                    <input type="checkbox" id="cryptopay_testnet_mode" name="cryptopay_testnet_mode" value="1" <?php checked(isset($settings['cryptopay_testnet_mode']) ? $settings['cryptopay_testnet_mode'] : 1, 1); ?>>
                                    <p class="description"><?php _e('Использовать @CryptoTestnetBot для тестирования', 'cryptoschool'); ?></p>
                                </div>

                                <div class="cryptoschool-admin-form-row">
                                    <label for="cryptopay_webhook_url"><?php _e('Webhook URL:', 'cryptoschool'); ?></label>
                                    <input type="text" id="cryptopay_webhook_url" class="regular-text" value="<?php echo esc_url(home_url('/wp-json/cryptoschool/v1/cryptopay/webhook')); ?>" readonly>
                                    <button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">📋 <?php _e('Копировать', 'cryptoschool'); ?></button>
                                    <p class="description"><?php _e('Скопируйте этот URL и установите в настройках бота', 'cryptoschool'); ?></p>
                                </div>

                                <div class="cryptoschool-admin-form-row">
                                    <label for="admin_telegram_id"><?php _e('Telegram ID администратора:', 'cryptoschool'); ?></label>
                                    <input type="text" id="admin_telegram_id" name="admin_telegram_id" class="regular-text" value="<?php echo isset($settings['admin_telegram_id']) ? esc_attr($settings['admin_telegram_id']) : ''; ?>">
                                    <p class="description"><?php _e('ID для отправки уведомлений о платежах. Узнать ID можно у @userinfobot', 'cryptoschool'); ?></p>
                                </div>

                                <div class="cryptoschool-admin-form-row">
                                    <label for="notification_bot_token"><?php _e('Токен бота для уведомлений:', 'cryptoschool'); ?></label>
                                    <input type="text" id="notification_bot_token" name="notification_bot_token" class="regular-text" value="<?php echo isset($settings['notification_bot_token']) ? esc_attr($settings['notification_bot_token']) : ''; ?>">
                                    <p class="description"><?php _e('Токен бота, который будет отправлять уведомления (создайте через @BotFather)', 'cryptoschool'); ?></p>
                                </div>

                                <div class="cryptoschool-admin-form-row">
                                    <button type="button" class="button" id="test-cryptopay-connection"><?php _e('Проверить подключение', 'cryptoschool'); ?></button>
                                    <span id="cryptopay-test-result"></span>
                                </div>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway coinbase" <?php echo isset($settings['crypto_gateway']) && $settings['crypto_gateway'] !== 'coinbase' ? 'style="display: none;"' : ''; ?>>
                                <label for="coinbase_api_key"><?php _e('Coinbase API Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="coinbase_api_key" name="coinbase_api_key" class="regular-text" value="<?php echo isset($settings['coinbase_api_key']) ? esc_attr($settings['coinbase_api_key']) : ''; ?>">
                                <p class="description"><?php _e('API Key для Coinbase Commerce.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway coinbase" <?php echo isset($settings['crypto_gateway']) && $settings['crypto_gateway'] !== 'coinbase' ? 'style="display: none;"' : ''; ?>>
                                <label for="coinbase_webhook_secret"><?php _e('Coinbase Webhook Secret:', 'cryptoschool'); ?></label>
                                <input type="text" id="coinbase_webhook_secret" name="coinbase_webhook_secret" class="regular-text" value="<?php echo isset($settings['coinbase_webhook_secret']) ? esc_attr($settings['coinbase_webhook_secret']) : ''; ?>">
                                <p class="description"><?php _e('Webhook Secret для Coinbase Commerce.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway cryptocloud" <?php echo !isset($settings['crypto_gateway']) || $settings['crypto_gateway'] !== 'cryptocloud' ? 'style="display: none;"' : ''; ?>>
                                <label for="cryptocloud_api_key"><?php _e('CryptoCloud API Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="cryptocloud_api_key" name="cryptocloud_api_key" class="regular-text" value="<?php echo isset($settings['cryptocloud_api_key']) ? esc_attr($settings['cryptocloud_api_key']) : ''; ?>">
                                <p class="description"><?php _e('API Key для CryptoCloud.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway cryptocloud" <?php echo !isset($settings['crypto_gateway']) || $settings['crypto_gateway'] !== 'cryptocloud' ? 'style="display: none;"' : ''; ?>>
                                <label for="cryptocloud_shop_id"><?php _e('CryptoCloud Shop ID:', 'cryptoschool'); ?></label>
                                <input type="text" id="cryptocloud_shop_id" name="cryptocloud_shop_id" class="regular-text" value="<?php echo isset($settings['cryptocloud_shop_id']) ? esc_attr($settings['cryptocloud_shop_id']) : ''; ?>">
                                <p class="description"><?php _e('Shop ID для CryptoCloud.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway binance" <?php echo !isset($settings['crypto_gateway']) || $settings['crypto_gateway'] !== 'binance' ? 'style="display: none;"' : ''; ?>>
                                <label for="binance_api_key"><?php _e('Binance API Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="binance_api_key" name="binance_api_key" class="regular-text" value="<?php echo isset($settings['binance_api_key']) ? esc_attr($settings['binance_api_key']) : ''; ?>">
                                <p class="description"><?php _e('API Key для Binance Pay.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row crypto-gateway binance" <?php echo !isset($settings['crypto_gateway']) || $settings['crypto_gateway'] !== 'binance' ? 'style="display: none;"' : ''; ?>>
                                <label for="binance_secret_key"><?php _e('Binance Secret Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="binance_secret_key" name="binance_secret_key" class="regular-text" value="<?php echo isset($settings['binance_secret_key']) ? esc_attr($settings['binance_secret_key']) : ''; ?>">
                                <p class="description"><?php _e('Secret Key для Binance Pay.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <h3><?php _e('Платежи банковской картой', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="card_gateway"><?php _e('Платежный шлюз:', 'cryptoschool'); ?></label>
                                <select id="card_gateway" name="card_gateway" class="regular-text">
                                    <option value="yoomoney" <?php selected(isset($settings['card_gateway']) ? $settings['card_gateway'] : '', 'yoomoney'); ?>>YooMoney</option>
                                    <option value="stripe" <?php selected(isset($settings['card_gateway']) ? $settings['card_gateway'] : '', 'stripe'); ?>>Stripe</option>
                                    <option value="wayforpay" <?php selected(isset($settings['card_gateway']) ? $settings['card_gateway'] : '', 'wayforpay'); ?>>WayForPay</option>
                                </select>
                                <p class="description"><?php _e('Платежный шлюз для платежей банковской картой.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway yoomoney" <?php echo isset($settings['card_gateway']) && $settings['card_gateway'] !== 'yoomoney' ? 'style="display: none;"' : ''; ?>>
                                <label for="yoomoney_shop_id"><?php _e('YooMoney Shop ID:', 'cryptoschool'); ?></label>
                                <input type="text" id="yoomoney_shop_id" name="yoomoney_shop_id" class="regular-text" value="<?php echo isset($settings['yoomoney_shop_id']) ? esc_attr($settings['yoomoney_shop_id']) : ''; ?>">
                                <p class="description"><?php _e('Shop ID для YooMoney.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway yoomoney" <?php echo isset($settings['card_gateway']) && $settings['card_gateway'] !== 'yoomoney' ? 'style="display: none;"' : ''; ?>>
                                <label for="yoomoney_secret_key"><?php _e('YooMoney Secret Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="yoomoney_secret_key" name="yoomoney_secret_key" class="regular-text" value="<?php echo isset($settings['yoomoney_secret_key']) ? esc_attr($settings['yoomoney_secret_key']) : ''; ?>">
                                <p class="description"><?php _e('Secret Key для YooMoney.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway stripe" <?php echo !isset($settings['card_gateway']) || $settings['card_gateway'] !== 'stripe' ? 'style="display: none;"' : ''; ?>>
                                <label for="stripe_publishable_key"><?php _e('Stripe Publishable Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="stripe_publishable_key" name="stripe_publishable_key" class="regular-text" value="<?php echo isset($settings['stripe_publishable_key']) ? esc_attr($settings['stripe_publishable_key']) : ''; ?>">
                                <p class="description"><?php _e('Publishable Key для Stripe.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway stripe" <?php echo !isset($settings['card_gateway']) || $settings['card_gateway'] !== 'stripe' ? 'style="display: none;"' : ''; ?>>
                                <label for="stripe_secret_key"><?php _e('Stripe Secret Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="stripe_secret_key" name="stripe_secret_key" class="regular-text" value="<?php echo isset($settings['stripe_secret_key']) ? esc_attr($settings['stripe_secret_key']) : ''; ?>">
                                <p class="description"><?php _e('Secret Key для Stripe.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway wayforpay" <?php echo !isset($settings['card_gateway']) || $settings['card_gateway'] !== 'wayforpay' ? 'style="display: none;"' : ''; ?>>
                                <label for="wayforpay_merchant_account"><?php _e('WayForPay Merchant Account:', 'cryptoschool'); ?></label>
                                <input type="text" id="wayforpay_merchant_account" name="wayforpay_merchant_account" class="regular-text" value="<?php echo isset($settings['wayforpay_merchant_account']) ? esc_attr($settings['wayforpay_merchant_account']) : ''; ?>">
                                <p class="description"><?php _e('Merchant Account для WayForPay.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row card-gateway wayforpay" <?php echo !isset($settings['card_gateway']) || $settings['card_gateway'] !== 'wayforpay' ? 'style="display: none;"' : ''; ?>>
                                <label for="wayforpay_secret_key"><?php _e('WayForPay Secret Key:', 'cryptoschool'); ?></label>
                                <input type="text" id="wayforpay_secret_key" name="wayforpay_secret_key" class="regular-text" value="<?php echo isset($settings['wayforpay_secret_key']) ? esc_attr($settings['wayforpay_secret_key']) : ''; ?>">
                                <p class="description"><?php _e('Secret Key для WayForPay.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Сохранить настройки', 'cryptoschool'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Вкладка "Реферальная система" -->
            <div id="referral" class="cryptoschool-admin-tab">
                <form id="referral-settings-form" method="post" action="">
                    <div class="cryptoschool-admin-card">
                        <h2><?php _e('Настройки реферальной системы', 'cryptoschool'); ?></h2>
                        
                        <div class="cryptoschool-admin-card-content">
                            <div class="cryptoschool-admin-form-row">
                                <label for="referral_enabled"><?php _e('Включить реферальную систему:', 'cryptoschool'); ?></label>
                                <input type="checkbox" id="referral_enabled" name="referral_enabled" value="1" <?php checked(isset($settings['referral_enabled']) ? $settings['referral_enabled'] : 0, 1); ?>>
                                <p class="description"><?php _e('Включить или отключить реферальную систему.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="default_commission_percent"><?php _e('Базовый процент комиссии:', 'cryptoschool'); ?></label>
                                <input type="number" id="default_commission_percent" name="default_commission_percent" class="small-text" min="0" max="100" step="0.1" value="<?php echo isset($settings['default_commission_percent']) ? esc_attr($settings['default_commission_percent']) : '20'; ?>">%
                                <p class="description"><?php _e('Базовый процент комиссии для рефоводов.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="max_commission_percent"><?php _e('Максимальный процент комиссии для инфлюенсеров:', 'cryptoschool'); ?></label>
                                <input type="number" id="max_commission_percent" name="max_commission_percent" class="small-text" min="0" max="100" step="0.1" value="<?php echo isset($settings['max_commission_percent']) ? esc_attr($settings['max_commission_percent']) : '50'; ?>">%
                                <p class="description"><?php _e('Максимальный процент комиссии для инфлюенсеров.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="min_withdrawal_amount"><?php _e('Минимальная сумма для вывода:', 'cryptoschool'); ?></label>
                                <input type="number" id="min_withdrawal_amount" name="min_withdrawal_amount" class="small-text" min="0" step="0.01" value="<?php echo isset($settings['min_withdrawal_amount']) ? esc_attr($settings['min_withdrawal_amount']) : '100'; ?>">
                                <span><?php echo isset($settings['currency']) ? esc_html($settings['currency']) : 'USD'; ?></span>
                                <p class="description"><?php _e('Минимальная сумма для запроса на вывод средств.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="withdrawal_methods"><?php _e('Методы вывода средств:', 'cryptoschool'); ?></label>
                                <div>
                                    <label>
                                        <input type="checkbox" name="withdrawal_methods[]" value="crypto" <?php checked(isset($settings['withdrawal_methods']) && in_array('crypto', $settings['withdrawal_methods']), true); ?>>
                                        <?php _e('Криптовалюта', 'cryptoschool'); ?>
                                    </label>
                                </div>
                                <p class="description"><?php _e('Доступные методы вывода средств.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Сохранить настройки', 'cryptoschool'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Вкладка "Email-уведомления" -->
            <div id="email" class="cryptoschool-admin-tab">
                <form id="email-settings-form" method="post" action="">
                    <div class="cryptoschool-admin-card">
                        <h2><?php _e('Настройки Email-уведомлений', 'cryptoschool'); ?></h2>
                        
                        <div class="cryptoschool-admin-card-content">
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_from_name"><?php _e('Имя отправителя:', 'cryptoschool'); ?></label>
                                <input type="text" id="email_from_name" name="email_from_name" class="regular-text" value="<?php echo isset($settings['email_from_name']) ? esc_attr($settings['email_from_name']) : ''; ?>">
                                <p class="description"><?php _e('Имя отправителя для email-уведомлений.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_from_address"><?php _e('Email отправителя:', 'cryptoschool'); ?></label>
                                <input type="email" id="email_from_address" name="email_from_address" class="regular-text" value="<?php echo isset($settings['email_from_address']) ? esc_attr($settings['email_from_address']) : ''; ?>">
                                <p class="description"><?php _e('Email-адрес отправителя для email-уведомлений.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <h3><?php _e('Уведомления для пользователей', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_welcome_subject"><?php _e('Тема приветственного письма:', 'cryptoschool'); ?></label>
                                <input type="text" id="email_welcome_subject" name="email_welcome_subject" class="regular-text" value="<?php echo isset($settings['email_welcome_subject']) ? esc_attr($settings['email_welcome_subject']) : ''; ?>">
                                <p class="description"><?php _e('Тема приветственного письма для новых пользователей.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_welcome_template"><?php _e('Шаблон приветственного письма:', 'cryptoschool'); ?></label>
                                <textarea id="email_welcome_template" name="email_welcome_template" class="large-text" rows="5"><?php echo isset($settings['email_welcome_template']) ? esc_textarea($settings['email_welcome_template']) : ''; ?></textarea>
                                <p class="description"><?php _e('Шаблон приветственного письма для новых пользователей. Доступные переменные: {user_name}, {site_name}, {login_url}.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_payment_subject"><?php _e('Тема письма о платеже:', 'cryptoschool'); ?></label>
                                <input type="text" id="email_payment_subject" name="email_payment_subject" class="regular-text" value="<?php echo isset($settings['email_payment_subject']) ? esc_attr($settings['email_payment_subject']) : ''; ?>">
                                <p class="description"><?php _e('Тема письма о платеже.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="email_payment_template"><?php _e('Шаблон письма о платеже:', 'cryptoschool'); ?></label>
                                <textarea id="email_payment_template" name="email_payment_template" class="large-text" rows="5"><?php echo isset($settings['email_payment_template']) ? esc_textarea($settings['email_payment_template']) : ''; ?></textarea>
                                <p class="description"><?php _e('Шаблон письма о платеже. Доступные переменные: {user_name}, {package_name}, {amount}, {currency}, {payment_date}, {site_name}.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <h3><?php _e('Уведомления для администраторов', 'cryptoschool'); ?></h3>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_new_user_subject"><?php _e('Тема письма о новом пользователе:', 'cryptoschool'); ?></label>
                                <input type="text" id="admin_email_new_user_subject" name="admin_email_new_user_subject" class="regular-text" value="<?php echo isset($settings['admin_email_new_user_subject']) ? esc_attr($settings['admin_email_new_user_subject']) : ''; ?>">
                                <p class="description"><?php _e('Тема письма о новом пользователе.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_new_user_template"><?php _e('Шаблон письма о новом пользователе:', 'cryptoschool'); ?></label>
                                <textarea id="admin_email_new_user_template" name="admin_email_new_user_template" class="large-text" rows="5"><?php echo isset($settings['admin_email_new_user_template']) ? esc_textarea($settings['admin_email_new_user_template']) : ''; ?></textarea>
                                <p class="description"><?php _e('Шаблон письма о новом пользователе. Доступные переменные: {user_name}, {user_email}, {registration_date}, {site_name}.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_new_payment_subject"><?php _e('Тема письма о новом платеже:', 'cryptoschool'); ?></label>
                                <input type="text" id="admin_email_new_payment_subject" name="admin_email_new_payment_subject" class="regular-text" value="<?php echo isset($settings['admin_email_new_payment_subject']) ? esc_attr($settings['admin_email_new_payment_subject']) : ''; ?>">
                                <p class="description"><?php _e('Тема письма о новом платеже.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_new_payment_template"><?php _e('Шаблон письма о новом платеже:', 'cryptoschool'); ?></label>
                                <textarea id="admin_email_new_payment_template" name="admin_email_new_payment_template" class="large-text" rows="5"><?php echo isset($settings['admin_email_new_payment_template']) ? esc_textarea($settings['admin_email_new_payment_template']) : ''; ?></textarea>
                                <p class="description"><?php _e('Шаблон письма о новом платеже. Доступные переменные: {user_name}, {user_email}, {package_name}, {amount}, {currency}, {payment_method}, {payment_date}, {site_name}.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_withdrawal_request_subject"><?php _e('Тема письма о запросе на вывод средств:', 'cryptoschool'); ?></label>
                                <input type="text" id="admin_email_withdrawal_request_subject" name="admin_email_withdrawal_request_subject" class="regular-text" value="<?php echo isset($settings['admin_email_withdrawal_request_subject']) ? esc_attr($settings['admin_email_withdrawal_request_subject']) : ''; ?>">
                                <p class="description"><?php _e('Тема письма о запросе на вывод средств.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <label for="admin_email_withdrawal_request_template"><?php _e('Шаблон письма о запросе на вывод средств:', 'cryptoschool'); ?></label>
                                <textarea id="admin_email_withdrawal_request_template" name="admin_email_withdrawal_request_template" class="large-text" rows="5"><?php echo isset($settings['admin_email_withdrawal_request_template']) ? esc_textarea($settings['admin_email_withdrawal_request_template']) : ''; ?></textarea>
                                <p class="description"><?php _e('Шаблон письма о запросе на вывод средств. Доступные переменные: {user_name}, {user_email}, {amount}, {currency}, {crypto_address}, {request_date}, {site_name}.', 'cryptoschool'); ?></p>
                            </div>
                            
                            <div class="cryptoschool-admin-form-row">
                                <button type="submit" class="button button-primary"><?php _e('Сохранить настройки', 'cryptoschool'); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Переключение вкладок
    $('.cryptoschool-admin-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.cryptoschool-admin-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        $('.cryptoschool-admin-tab').removeClass('active');
        $(target).addClass('active');
    });
    
    // Переключение полей для криптовалютных платежных шлюзов
    $('#crypto_gateway').on('change', function() {
        var gateway = $(this).val();

        $('.crypto-gateway').hide();
        $('.crypto-gateway.' + gateway).show();
    });

    // Тест подключения к Crypto Pay
    $('#test-cryptopay-connection').on('click', function() {
        var button = $(this);
        var resultSpan = $('#cryptopay-test-result');
        var token = $('#cryptopay_api_token').val();
        var testnet = $('#cryptopay_testnet_mode').is(':checked');

        if (!token) {
            resultSpan.html('<span style="color: red;">❌ Введите API токен</span>');
            return;
        }

        button.prop('disabled', true);
        resultSpan.html('<span style="color: blue;">⏳ Проверка...</span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_cryptopay_connection',
                token: token,
                testnet: testnet ? 1 : 0,
                nonce: '<?php echo wp_create_nonce("cryptopay_test_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    resultSpan.html('<span style="color: green;">✅ Подключение успешно!</span>');
                } else {
                    resultSpan.html('<span style="color: red;">❌ ' + response.data + '</span>');
                }
            },
            error: function() {
                resultSpan.html('<span style="color: red;">❌ Ошибка подключения</span>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    // Переключение полей для платежных шлюзов банковских карт
    $('#card_gateway').on('change', function() {
        var gateway = $(this).val();
        
        $('.card-gateway').hide();
        $('.card-gateway.' + gateway).show();
    });
    
    // Установка вебхука для платежного бота
    $('#set-payment-webhook').on('click', function() {
        var token = $('#payment_bot_token').val();
        var webhook = $('#payment_bot_webhook').val();
        
        if (!token) {
            alert('<?php _e('Пожалуйста, введите токен платежного бота.', 'cryptoschool'); ?>');
            return;
        }
        
        // Здесь будет AJAX-запрос для установки вебхука
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Вебхук для платежного бота успешно установлен.', 'cryptoschool'); ?>');
    });
    
    // Установка вебхука для бота поддержки
    $('#set-support-webhook').on('click', function() {
        var token = $('#support_bot_token').val();
        var webhook = $('#support_bot_webhook').val();
        
        if (!token) {
            alert('<?php _e('Пожалуйста, введите токен бота поддержки.', 'cryptoschool'); ?>');
            return;
        }
        
        // Здесь будет AJAX-запрос для установки вебхука
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Вебхук для бота поддержки успешно установлен.', 'cryptoschool'); ?>');
    });
    
    // Отправка формы общих настроек
    $('#general-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для сохранения настроек
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Общие настройки успешно сохранены.', 'cryptoschool'); ?>');
    });
    
    // Отправка формы настроек Telegram
    $('#telegram-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для сохранения настроек
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Настройки Telegram успешно сохранены.', 'cryptoschool'); ?>');
    });
    
    // Отправка формы настроек платежей
    $('#payments-settings-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'save_payment_settings',
            nonce: '<?php echo wp_create_nonce("payment_settings_nonce"); ?>',
            currency: $('#currency').val(),
            payment_methods: $('input[name="payment_methods[]"]:checked').map(function() {
                return $(this).val();
            }).get(),
            crypto_gateway: $('#crypto_gateway').val(),
            cryptopay_api_token: $('#cryptopay_api_token').val(),
            cryptopay_testnet_mode: $('#cryptopay_testnet_mode').is(':checked') ? 1 : 0,
            admin_telegram_id: $('#admin_telegram_id').val(),
            notification_bot_token: $('#notification_bot_token').val()
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Настройки платежей успешно сохранены.', 'cryptoschool'); ?>');
                } else {
                    alert('<?php _e('Ошибка:', 'cryptoschool'); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ошибка подключения', 'cryptoschool'); ?>');
            }
        });
    });
    
    // Отправка формы настроек реферальной системы
    $('#referral-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для сохранения настроек
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Настройки реферальной системы успешно сохранены.', 'cryptoschool'); ?>');
    });
    
    // Отправка формы настроек Email-уведомлений
    $('#email-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Здесь будет AJAX-запрос для сохранения настроек
        // Это заглушка, которая будет заменена на реальный код при реализации функционала
        
        alert('<?php _e('Настройки Email-уведомлений успешно сохранены.', 'cryptoschool'); ?>');
    });
});
</script>

<style>
/* Стили для вкладок */
.cryptoschool-admin-tabs {
    margin-top: 20px;
}

.cryptoschool-admin-tabs-nav {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
    border-bottom: 1px solid #ccc;
}

.cryptoschool-admin-tabs-nav li {
    margin: 0;
    padding: 0;
}

.cryptoschool-admin-tabs-nav a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #555;
    font-weight: 500;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
}

.cryptoschool-admin-tabs-nav li.active a {
    background-color: #fff;
    border-color: #ccc;
    border-bottom-color: #fff;
    color: #000;
}

.cryptoschool-admin-tab {
    display: none;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
    background-color: #fff;
}

.cryptoschool-admin-tab.active {
    display: block;
}

/* Стили для карточек */
.cryptoschool-admin-card {
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 3px;
    margin-bottom: 20px;
}

.cryptoschool-admin-card h2 {
    margin: 0;
    padding: 15px;
    border-bottom: 1px solid #ccc;
    background-color: #f5f5f5;
}

.cryptoschool-admin-card-content {
    padding: 15px;
}

/* Стили для форм */
.cryptoschool-admin-form-row {
    margin-bottom: 15px;
}

.cryptoschool-admin-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.cryptoschool-admin-form-row .description {
    margin-top: 5px;
    color: #666;
}
</style>
