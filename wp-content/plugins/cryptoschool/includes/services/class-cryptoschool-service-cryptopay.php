<?php
/**
 * Сервис для работы с Crypto Pay API
 *
 * API Documentation: https://help.send.tg/en/articles/10279948-crypto-pay-api
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса Crypto Pay
 */
class CryptoSchool_Service_CryptoPay extends CryptoSchool_Service {

    /**
     * API токен
     *
     * @var string
     */
    private $api_token;

    /**
     * Режим тестирования
     *
     * @var bool
     */
    private $testnet_mode;

    /**
     * Базовый URL API
     *
     * @var string
     */
    private $api_base_url;

    /**
     * Логгер
     *
     * @var CryptoSchool_Logger
     */
    private $logger;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);

        // Получаем настройки
        $this->api_token = get_option('cryptoschool_cryptopay_api_token', '');
        $this->testnet_mode = get_option('cryptoschool_cryptopay_testnet_mode', true);

        // Устанавливаем базовый URL в зависимости от режима
        $this->api_base_url = $this->testnet_mode
            ? 'https://testnet-pay.crypt.bot/api'
            : 'https://pay.crypt.bot/api';

        // Инициализируем логгер
        $this->logger = CryptoSchool_Logger::get_instance();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Webhook endpoint
        $this->add_action('rest_api_init', 'register_webhook_endpoint');

        // AJAX действия
        $this->add_action('wp_ajax_cryptopay_create_invoice', 'ajax_create_invoice');
        $this->add_action('wp_ajax_cryptopay_check_invoice', 'ajax_check_invoice');
    }

    /**
     * Регистрация webhook endpoint
     *
     * @return void
     */
    public function register_webhook_endpoint() {
        register_rest_route('cryptoschool/v1', '/cryptopay/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true', // Webhook должен быть публичным
        ]);
    }

    /**
     * Создание инвойса
     *
     * @param int   $package_id      ID пакета
     * @param int   $user_id         ID пользователя
     * @param int   $referral_link_id ID реферальной ссылки (опционально)
     * @return array|WP_Error
     */
    public function create_invoice($package_id, $user_id, $referral_link_id = null) {
        // Получаем информацию о пакете
        global $wpdb;
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cryptoschool_packages WHERE id = %d",
            $package_id
        ));

        if (!$package) {
            return new WP_Error('package_not_found', 'Пакет не найден');
        }

        // Получаем пользователя
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Пользователь не найден');
        }

        // Рассчитываем сумму с учетом скидки
        $amount = $package->price;
        $discount_amount = 0;
        $discount_percent = 0;

        if ($referral_link_id) {
            $referral_link = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cryptoschool_referral_links WHERE id = %d",
                $referral_link_id
            ));

            if ($referral_link) {
                $discount_percent = $referral_link->discount_percent;
                $discount_amount = ($amount * $discount_percent) / 100;
                $amount = $amount - $discount_amount;
            }
        }

        // Создаем запись в БД
        $payment_data = [
            'user_id' => $user_id,
            'package_id' => $package_id,
            'amount' => $amount,
            'original_amount' => $package->price,
            'discount_amount' => $discount_amount,
            'discount_percent' => $discount_percent,
            'currency' => 'USD',
            'payment_method' => 'crypto',
            'payment_gateway' => 'cryptopay',
            'referral_link_id' => $referral_link_id,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        ];

        $wpdb->insert(
            $wpdb->prefix . 'cryptoschool_payments',
            $payment_data
        );

        $payment_id = $wpdb->insert_id;

        // Создаем инвойс в Crypto Pay
        $invoice_response = $this->api_request('createInvoice', [
            'currency_type' => 'fiat',
            'fiat' => 'USD',
            'amount' => $amount,
            'description' => sprintf('Оплата пакета "%s"', $package->title),
            'payload' => json_encode([
                'payment_id' => $payment_id,
                'user_id' => $user_id,
                'package_id' => $package_id,
            ]),
            'paid_btn_name' => 'callback',
            'paid_btn_url' => home_url('/dashboard/'),
        ]);

        if (is_wp_error($invoice_response)) {
            $this->logger->error('Failed to create Crypto Pay invoice', [
                'error' => $invoice_response->get_error_message(),
                'payment_id' => $payment_id,
            ]);
            return $invoice_response;
        }

        // Обновляем запись с ID инвойса
        $wpdb->update(
            $wpdb->prefix . 'cryptoschool_payments',
            [
                'cryptopay_invoice_id' => $invoice_response['result']['invoice_id'],
                'cryptopay_status' => $invoice_response['result']['status'],
            ],
            ['id' => $payment_id]
        );

        $this->logger->info('Crypto Pay invoice created', [
            'payment_id' => $payment_id,
            'invoice_id' => $invoice_response['result']['invoice_id'],
            'amount' => $amount,
        ]);

        // Используем готовую ссылку от API
        $pay_url = $invoice_response['result']['pay_url'];

        return [
            'success' => true,
            'payment_id' => $payment_id,
            'invoice_id' => $invoice_response['result']['invoice_id'],
            'hash' => $invoice_response['result']['hash'],
            'pay_url' => $pay_url,
            'amount' => $amount,
            'currency' => 'USD',
        ];
    }

    /**
     * Обработка webhook
     *
     * @param WP_REST_Request $request Запрос
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $data = $request->get_json_params();

        $this->logger->info('Crypto Pay webhook received', $data);

        // Проверяем наличие необходимых данных
        if (!isset($data['update_type']) || !isset($data['payload'])) {
            return new WP_REST_Response(['error' => 'Invalid webhook data'], 400);
        }

        // Обрабатываем разные типы обновлений
        switch ($data['update_type']) {
            case 'invoice_paid':
                $this->process_paid_invoice($data['payload']);
                break;

            case 'invoice_expired':
                $this->process_expired_invoice($data['payload']);
                break;

            default:
                $this->logger->warning('Unknown webhook update type', ['type' => $data['update_type']]);
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    /**
     * Обработка оплаченного инвойса
     *
     * @param array $payload Данные инвойса
     * @return void
     */
    private function process_paid_invoice($payload) {
        global $wpdb;

        $invoice_id = $payload['invoice_id'];

        // Находим платеж по invoice_id
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cryptoschool_payments WHERE cryptopay_invoice_id = %s",
            $invoice_id
        ));

        if (!$payment) {
            $this->logger->error('Payment not found for invoice', ['invoice_id' => $invoice_id]);
            return;
        }

        // Обновляем статус платежа
        $wpdb->update(
            $wpdb->prefix . 'cryptoschool_payments',
            [
                'status' => 'completed',
                'cryptopay_status' => 'paid',
                'crypto_currency' => $payload['asset'] ?? null,
                'crypto_amount' => $payload['amount'] ?? null,
                'exchange_rate' => $payload['exchange_rate'] ?? null,
                'payment_date' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $payment->id]
        );

        // Активируем доступ к пакету
        $this->activate_package_access($payment->user_id, $payment->package_id);

        // Обрабатываем реферальные комиссии
        if ($payment->referral_link_id) {
            do_action('cryptoschool_payment_completed', $payment->id, [
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
            ]);
        }

        // Отправляем уведомление администратору
        $this->send_admin_notification($payment->id);

        $this->logger->info('Payment completed', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
        ]);
    }

    /**
     * Обработка истекшего инвойса
     *
     * @param array $payload Данные инвойса
     * @return void
     */
    private function process_expired_invoice($payload) {
        global $wpdb;

        $invoice_id = $payload['invoice_id'];

        // Обновляем статус платежа
        $wpdb->update(
            $wpdb->prefix . 'cryptoschool_payments',
            [
                'status' => 'failed',
                'cryptopay_status' => 'expired',
                'updated_at' => current_time('mysql'),
            ],
            ['cryptopay_invoice_id' => $invoice_id]
        );

        $this->logger->info('Invoice expired', ['invoice_id' => $invoice_id]);
    }

    /**
     * Активация доступа к пакету
     *
     * @param int $user_id    ID пользователя
     * @param int $package_id ID пакета
     * @return void
     */
    private function activate_package_access($user_id, $package_id) {
        global $wpdb;

        // Получаем информацию о пакете
        $package = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cryptoschool_packages WHERE id = %d",
            $package_id
        ));

        if (!$package) {
            return;
        }

        // Рассчитываем дату окончания доступа
        $access_end = null;
        if ($package->duration_months > 0) {
            $access_end = date('Y-m-d H:i:s', strtotime("+{$package->duration_months} months"));
        }

        // Создаем или обновляем доступ
        $existing_access = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cryptoschool_user_access
             WHERE user_id = %d AND package_id = %d",
            $user_id, $package_id
        ));

        if ($existing_access) {
            // Продлеваем существующий доступ
            $wpdb->update(
                $wpdb->prefix . 'cryptoschool_user_access',
                [
                    'access_end' => $access_end,
                    'status' => 'active',
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $existing_access->id]
            );
        } else {
            // Создаем новый доступ
            $wpdb->insert(
                $wpdb->prefix . 'cryptoschool_user_access',
                [
                    'user_id' => $user_id,
                    'package_id' => $package_id,
                    'access_start' => current_time('mysql'),
                    'access_end' => $access_end,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                ]
            );
        }
    }

    /**
     * Отправка уведомления администратору в Telegram
     *
     * @param int $payment_id ID платежа
     * @return void
     */
    private function send_admin_notification($payment_id) {
        global $wpdb;

        // Получаем данные платежа
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, pkg.title as package_title, u.user_email, u.display_name
             FROM {$wpdb->prefix}cryptoschool_payments p
             LEFT JOIN {$wpdb->prefix}cryptoschool_packages pkg ON p.package_id = pkg.id
             LEFT JOIN {$wpdb->prefix}users u ON p.user_id = u.ID
             WHERE p.id = %d",
            $payment_id
        ));

        if (!$payment) {
            return;
        }

        // Получаем настройки для уведомлений
        $admin_telegram_id = get_option('cryptoschool_admin_telegram_id', '');
        $notification_bot_token = get_option('cryptoschool_notification_bot_token', '');

        if (empty($admin_telegram_id) || empty($notification_bot_token)) {
            $this->logger->warning('Admin notification settings not configured');
            return;
        }

        // Формируем сообщение
        $message = "💰 <b>Новый платеж!</b>\n\n";
        $message .= "👤 Пользователь: {$payment->display_name}\n";
        $message .= "📧 Email: {$payment->user_email}\n";
        $message .= "📦 Пакет: {$payment->package_title}\n";
        $message .= "💵 Сумма: \${$payment->amount} USD\n";

        if ($payment->crypto_currency) {
            $message .= "🪙 Криптовалюта: {$payment->crypto_currency}\n";
            $message .= "💱 Крипто сумма: {$payment->crypto_amount}\n";
        }

        if ($payment->discount_amount > 0) {
            $message .= "🎁 Скидка: \${$payment->discount_amount} ({$payment->discount_percent}%)\n";
        }

        $message .= "\n🕒 Время: " . date('d.m.Y H:i', strtotime($payment->payment_date));

        // Отправляем сообщение через Telegram API
        $telegram_api_url = "https://api.telegram.org/bot{$notification_bot_token}/sendMessage";

        $response = wp_remote_post($telegram_api_url, [
            'body' => [
                'chat_id' => $admin_telegram_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ],
        ]);

        if (is_wp_error($response)) {
            $this->logger->error('Failed to send admin notification', [
                'error' => $response->get_error_message(),
            ]);
        } else {
            // Отмечаем, что уведомление отправлено
            $wpdb->update(
                $wpdb->prefix . 'cryptoschool_payments',
                ['admin_notified' => 1],
                ['id' => $payment_id]
            );

            $this->logger->info('Admin notification sent', ['payment_id' => $payment_id]);
        }
    }

    /**
     * Выполнение API запроса к Crypto Pay
     *
     * @param string $method Метод API
     * @param array  $params Параметры запроса
     * @return array|WP_Error
     */
    private function api_request($method, $params = []) {
        $url = $this->api_base_url . '/' . $method;

        // Логируем запрос
        $this->logger->info('CryptoPay API Request', [
            'method' => $method,
            'url' => $url,
            'params' => $params
        ]);

        $response = wp_remote_post($url, [
            'headers' => [
                'Crypto-Pay-API-Token' => $this->api_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($params),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            $this->logger->error('CryptoPay API Connection Error', [
                'error' => $response->get_error_message()
            ]);
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        $data = json_decode($body, true);

        // Логируем ответ
        $this->logger->info('CryptoPay API Response', [
            'status_code' => $status_code,
            'response_body' => $body
        ]);

        if (!$data || !isset($data['ok'])) {
            $this->logger->error('CryptoPay API Invalid response format', [
                'body' => $body
            ]);
            return new WP_Error('api_error', 'Invalid API response');
        }

        if (!$data['ok']) {
            $error_name = $data['error']['name'] ?? 'Unknown error';
            $error_code = $data['error']['code'] ?? 0;

            // Специальное логирование для ASSET_REQUIRED
            if ($error_name === 'ASSET_REQUIRED') {
                $this->logger->warning('ASSET_REQUIRED Error - Check accepted assets in @CryptoTestnetBot app settings', [
                    'assets_sent' => $params['asset'] ?? 'none',
                    'params' => $params
                ]);
            }

            $this->logger->error('CryptoPay API Error', [
                'error_name' => $error_name,
                'error_code' => $error_code,
                'full_error' => $data['error'] ?? null
            ]);

            return new WP_Error('api_error', $error_name);
        }

        $this->logger->info('CryptoPay API Success', [
            'result' => $data['result'] ?? null
        ]);
        return $data;
    }

    /**
     * AJAX обработчик создания инвойса
     *
     * @return void
     */
    public function ajax_create_invoice() {
        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptopay_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error('Not authorized');
            return;
        }

        $package_id = intval($_POST['package_id'] ?? 0);
        $user_id = get_current_user_id();

        // Получаем реферальную ссылку из сессии
        $referral_link_id = null;
        if (isset($_SESSION['cryptoschool_referral'])) {
            $referral_link_id = $_SESSION['cryptoschool_referral']['link_id'] ?? null;
        }

        $result = $this->create_invoice($package_id, $user_id, $referral_link_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX обработчик проверки статуса инвойса
     *
     * @return void
     */
    public function ajax_check_invoice() {
        // Проверка nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptopay_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $invoice_id = sanitize_text_field($_POST['invoice_id'] ?? '');

        if (empty($invoice_id)) {
            wp_send_json_error('Invoice ID required');
            return;
        }

        // Проверяем статус в БД
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT status, cryptopay_status FROM {$wpdb->prefix}cryptoschool_payments
             WHERE cryptopay_invoice_id = %s",
            $invoice_id
        ));

        if (!$payment) {
            wp_send_json_error('Invoice not found');
            return;
        }

        wp_send_json_success([
            'status' => $payment->status,
            'cryptopay_status' => $payment->cryptopay_status,
        ]);
    }
}