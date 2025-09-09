<?php
/**
 * Простой API контроллер для реферальной системы
 *
 * Обрабатывает базовые AJAX-запросы для работы с реферальными ссылками
 *
 * @package CryptoSchool
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс простого API контроллера реферальной системы
 */
class CryptoSchool_API_Referral_Simple {

    /**
     * Сервис реферальной системы
     *
     * @var CryptoSchool_Service_Referral
     */
    private $referral_service;

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Получение сервиса реферальной системы (ленивая инициализация)
     *
     * @return CryptoSchool_Service_Referral
     */
    private function get_referral_service() {
        if (!$this->referral_service) {
            // Создаем сервис без loader для простого API контроллера
            require_once CRYPTOSCHOOL_PLUGIN_DIR . 'includes/repositories/class-cryptoschool-repository-referral-link.php';
            $this->referral_service = new class() {
                private $referral_link_repository;
                
                public function __construct() {
                    $this->referral_link_repository = new CryptoSchool_Repository_Referral_Link();
                }
                
                public function create_referral_link($user_id, $link_name, $discount_percent, $commission_percent) {
                    // Генерируем уникальный код
                    do {
                        $referral_code = 'REF' . $user_id . strtoupper(substr(md5(time() . rand()), 0, 6));
                        $existing = $this->referral_link_repository->where(['referral_code' => $referral_code]);
                    } while (!empty($existing));
                    
                    // Подготавливаем данные для создания
                    $data = [
                        'user_id' => $user_id,
                        'referral_code' => $referral_code,
                        'link_name' => $link_name,
                        'discount_percent' => $discount_percent,
                        'commission_percent' => $commission_percent,
                        'clicks_count' => 0,
                        'conversions_count' => 0,
                        'total_earned' => 0.00,
                        'is_active' => 1,
                        'created_at' => current_time('mysql')
                    ];
                    
                    // Создаем ссылку в БД
                    return $this->referral_link_repository->create($data);
                }
            };
        }
        return $this->referral_service;
    }

    /**
     * Инициализация хуков
     *
     * @return void
     */
    private function init_hooks() {
        // AJAX хуки для авторизованных пользователей
        add_action('wp_ajax_get_referral_data', array($this, 'get_referral_data'));
        add_action('wp_ajax_create_referral_link', array($this, 'create_referral_link'));
        
        // Подключение скриптов
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Подключение скриптов
     *
     * @return void
     */
    public function enqueue_scripts() {
        if (is_page_template('page-referral.php') || is_page('referral')) {
            wp_enqueue_script('jquery');
            
            // Подключаем наш JavaScript файл
            wp_enqueue_script(
                'cryptoschool-referral-system',
                get_template_directory_uri() . '/assets/js/referral-system.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Локализация для AJAX
            wp_localize_script('cryptoschool-referral-system', 'cryptoschool_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cryptoschool_referral_nonce')
            ));
        }
    }

    /**
     * Получение данных реферальной программы пользователя
     *
     * @return void
     */
    public function get_referral_data() {
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'cryptoschool_referral_nonce')) {
            wp_send_json_error('Ошибка безопасности');
            return;
        }

        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();

        try {
            // Пока используем заглушки, но структурируем данные правильно
            $referral_links = $this->get_user_referral_links($user_id);
            $statistics = $this->get_user_statistics($user_id);
            $recent_payments = $this->get_recent_payments($user_id);
            $referrals = $this->get_user_referrals($user_id);

            wp_send_json_success(array(
                'links' => $referral_links,
                'statistics' => $statistics,
                'recent_payments' => $recent_payments,
                'referrals' => $referrals
            ));

        } catch (Exception $e) {
            wp_send_json_error('Ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Создание новой реферальной ссылки
     *
     * @return void
     */
    public function create_referral_link() {
        // Проверка nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'cryptoschool_referral_nonce')) {
            wp_send_json_error('Ошибка безопасности');
            return;
        }

        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();

        // Получение данных из запроса
        $link_name = sanitize_text_field($_POST['link_name'] ?? 'Новая ссылка');
        $discount_percent = floatval($_POST['discount_percent'] ?? 20);
        $commission_percent = floatval($_POST['commission_percent'] ?? 20);

        // Валидация данных
        if ($discount_percent < 0 || $discount_percent > 40) {
            wp_send_json_error('Скидка должна быть от 0 до 40%');
            return;
        }

        if ($commission_percent < 0 || $commission_percent > 40) {
            wp_send_json_error('Комиссия должна быть от 0 до 40%');
            return;
        }

        if (($discount_percent + $commission_percent) > 40) {
            wp_send_json_error('Сумма скидки и комиссии не может превышать 40%');
            return;
        }

        try {
            // Используем реальный сервис для создания ссылки
            $new_link = $this->get_referral_service()->create_referral_link(
                $user_id,
                $link_name,
                $discount_percent,
                $commission_percent
            );

            if ($new_link) {
                // Преобразуем модель в массив для ответа
                $link_data = array(
                    'id' => $new_link->getAttribute('id'),
                    'name' => $new_link->getAttribute('link_name'),
                    'code' => $new_link->getAttribute('referral_code'),
                    'url' => site_url('/ref/' . $new_link->getAttribute('referral_code')),
                    'discount_percent' => (float) $new_link->getAttribute('discount_percent'),
                    'commission_percent' => (float) $new_link->getAttribute('commission_percent'),
                    'clicks_count' => (int) $new_link->getAttribute('clicks_count'),
                    'conversions_count' => (int) $new_link->getAttribute('conversions_count'),
                    'total_earned' => (float) $new_link->getAttribute('total_earned'),
                    'is_active' => true,
                    'created_at' => $new_link->getAttribute('created_at')
                );

                wp_send_json_success(array(
                    'message' => 'Реферальная ссылка создана успешно',
                    'link' => $link_data
                ));
            } else {
                wp_send_json_error('Не удалось создать реферальную ссылку');
            }

        } catch (Exception $e) {
            wp_send_json_error('Ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Получение реферальных ссылок пользователя из базы данных
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_user_referral_links($user_id) {
        global $wpdb;
        
        // Получаем реферальные ссылки пользователя
        $table_name = $wpdb->prefix . 'cryptoschool_referral_links';
        $links = $wpdb->get_results($wpdb->prepare("
            SELECT 
                id,
                referral_code as code,
                link_name as name,
                discount_percent,
                commission_percent,
                clicks_count,
                conversions_count,
                total_earned,
                created_at
            FROM `{$table_name}` 
            WHERE user_id = %d 
            ORDER BY created_at DESC
        ", $user_id), ARRAY_A);
        
        $result = array();
        
        foreach ($links as $link) {
            // Получаем статистику по каждой ссылке
            $statistics = $this->get_link_statistics($link['id'], $user_id);
            $recent_payments = $this->get_link_recent_payments($link['id'], $user_id);
            $referrals = $this->get_link_referrals($link['id'], $user_id);
            
            $result[] = array(
                'id' => (int) $link['id'],
                'name' => $link['name'] ?: 'Реф-код ' . $link['id'],
                'code' => $link['code'],
                'url' => site_url('/ref/' . $link['code']),
                'discount_percent' => (float) $link['discount_percent'],
                'commission_percent' => (float) $link['commission_percent'],
                'clicks_count' => (int) $link['clicks_count'],
                'conversions_count' => (int) $link['conversions_count'],
                'total_earned' => (float) $link['total_earned'],
                'is_active' => true,
                'created_at' => $link['created_at'],
                'statistics' => $statistics,
                'recent_payments' => $recent_payments,
                'referrals' => $referrals
            );
        }
        
        return $result;
    }

    /**
     * Получение статистики по конкретной ссылке
     *
     * @param int $link_id ID реферальной ссылки
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_link_statistics($link_id, $user_id) {
        global $wpdb;
        
        // Получаем статистику по ссылке из базы данных
        $table_name = $wpdb->prefix . 'cryptoschool_referral_links';
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                clicks_count,
                conversions_count,
                total_earned
            FROM `{$table_name}` 
            WHERE id = %d AND user_id = %d
        ", $link_id, $user_id), ARRAY_A);
        
        if (!$stats) {
            return array(
                'total_invited' => 0,
                'total_purchased' => 0,
                'total_payments' => '$0',
                'available_for_withdrawal' => '$0'
            );
        }
        
        return array(
            'total_invited' => (int) $stats['clicks_count'],
            'total_purchased' => (int) $stats['conversions_count'],
            'total_payments' => '$' . number_format($stats['total_earned'], 0),
            'available_for_withdrawal' => '$' . number_format($stats['total_earned'], 0)
        );
    }

    /**
     * Получение последних выплат по конкретной ссылке
     *
     * @param int $link_id ID реферальной ссылки
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_link_recent_payments($link_id, $user_id) {
        global $wpdb;
        
        // Получаем последние транзакции по этой ссылке
        $transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
        $payments_table = $wpdb->prefix . 'cryptoschool_payments';
        $payments = $wpdb->get_results($wpdb->prepare("
            SELECT 
                rt.amount,
                rt.status,
                rt.created_at,
                rt.comment
            FROM `{$transactions_table}` rt
            INNER JOIN `{$payments_table}` p ON rt.payment_id = p.id
            WHERE p.referral_link_id = %d AND rt.referrer_id = %d
            ORDER BY rt.created_at DESC
            LIMIT 5
        ", $link_id, $user_id), ARRAY_A);
        
        $result = array();
        
        foreach ($payments as $payment) {
            $date = date('d.m.Y', strtotime($payment['created_at']));
            $time = date('H:i', strtotime($payment['created_at']));
            
            // Определяем статус и цвета
            $status_info = $this->get_payment_status_info($payment['status']);
            
            $result[] = array(
                'date' => $date,
                'time' => $time,
                'amount' => '$' . number_format($payment['amount'], 0),
                'status' => $payment['status'],
                'status_text' => $status_info['text'],
                'status_class' => $status_info['class'],
                'status_color' => $status_info['color'],
                'comment' => $payment['comment'] ?: ''
            );
        }
        
        return $result;
    }

    /**
     * Получение рефералов по конкретной ссылке
     *
     * @param int $link_id ID реферальной ссылки
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_link_referrals($link_id, $user_id) {
        global $wpdb;
        
        // Получаем рефералов по этой ссылке
        $referral_users_table = $wpdb->prefix . 'cryptoschool_referral_users';
        $referrals = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ru.registration_date,
                ru.status,
                ru.user_id as referral_user_id
            FROM `{$referral_users_table}` ru
            WHERE ru.referral_link_id = %d AND ru.referrer_id = %d
            ORDER BY ru.registration_date DESC
            LIMIT 10
        ", $link_id, $user_id), ARRAY_A);
        
        $result = array();
        
        foreach ($referrals as $referral) {
            $date = date('d.m.Y', strtotime($referral['registration_date']));
            $time = date('H:i', strtotime($referral['registration_date']));
            
            // Генерируем маскированный Telegram ник (фиктивный)
            $telegram = $this->generate_masked_telegram($referral['referral_user_id']);
            
            // Определяем статус и цвета
            $status_info = $this->get_referral_status_info($referral['status']);
            
            $result[] = array(
                'date' => $date,
                'time' => $time,
                'telegram' => $telegram,
                'status' => $referral['status'],
                'status_text' => $status_info['text'],
                'status_class' => $status_info['class'],
                'status_color' => $status_info['color']
            );
        }
        
        return $result;
    }

    /**
     * Получение статистики пользователя из базы данных
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_user_statistics($user_id) {
        global $wpdb;
        
        // Получаем общую статистику пользователя
        $referral_users_table = $wpdb->prefix . 'cryptoschool_referral_users';
        $transactions_table = $wpdb->prefix . 'cryptoschool_referral_transactions';
        $withdrawal_table = $wpdb->prefix . 'cryptoschool_withdrawal_requests';
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT ru.user_id) as total_invited,
                COUNT(DISTINCT CASE WHEN ru.status = 'purchased' THEN ru.user_id END) as total_purchased,
                COALESCE(SUM(rt.amount), 0) as total_earned,
                COALESCE((
                    SELECT SUM(amount) 
                    FROM `{$withdrawal_table}` 
                    WHERE user_id = %d AND status = 'paid'
                ), 0) as total_paid_out
            FROM `{$referral_users_table}` ru
            LEFT JOIN `{$transactions_table}` rt ON ru.user_id = rt.user_id AND ru.referrer_id = rt.referrer_id
            WHERE ru.referrer_id = %d
        ", $user_id, $user_id), ARRAY_A);
        
        if (!$stats) {
            return array(
                'total_invited' => 0,
                'total_purchased' => 0,
                'total_payments' => '$0',
                'available_for_withdrawal' => '$0'
            );
        }
        
        $available = $stats['total_earned'] - $stats['total_paid_out'];
        
        return array(
            'total_invited' => (int) $stats['total_invited'],
            'total_purchased' => (int) $stats['total_purchased'],
            'total_payments' => '$' . number_format($stats['total_earned'], 0),
            'available_for_withdrawal' => '$' . number_format($available, 0)
        );
    }

    /**
     * Получение последних выплат (заглушка)
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_recent_payments($user_id) {
        return array(
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'amount' => '$230',
                'status' => 'processing',
                'status_text' => 'Виконується',
                'status_class' => 'status-line-indicator_orange',
                'status_color' => 'color-orange',
                'comment' => ''
            ),
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'amount' => '$130',
                'status' => 'success',
                'status_text' => 'Успішно',
                'status_class' => 'status-line-indicator_green',
                'status_color' => 'color-success',
                'comment' => ''
            ),
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'amount' => '$230',
                'status' => 'error',
                'status_text' => 'Помилка',
                'status_class' => 'status-line-indicator_red',
                'status_color' => 'color-danger',
                'comment' => 'Неправильні реквізити'
            )
        );
    }

    /**
     * Получение рефералов пользователя (заглушка)
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    private function get_user_referrals($user_id) {
        return array(
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'telegram' => '@ho*****pce',
                'status' => 'success',
                'status_text' => 'Успішно',
                'status_class' => 'status-line-indicator_green',
                'status_color' => 'color-success'
            ),
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'telegram' => '@ho*****pce',
                'status' => 'registered',
                'status_text' => 'Зареєстрований',
                'status_class' => 'status-line-indicator_orange',
                'status_color' => ''
            ),
            array(
                'date' => '24.05.2024',
                'time' => '16:30',
                'telegram' => '@ho*****pce',
                'status' => 'registered',
                'status_text' => 'Зареєстрований',
                'status_class' => 'status-line-indicator_orange',
                'status_color' => ''
            )
        );
    }

    /**
     * Создание заглушки реферальной ссылки
     *
     * @param int $user_id ID пользователя
     * @param string $name Название ссылки
     * @param float $discount_percent Процент скидки
     * @param float $commission_percent Процент комиссии
     * @return array
     */
    private function create_mock_referral_link($user_id, $name, $discount_percent, $commission_percent) {
        $code = 'REF' . $user_id . strtoupper(substr(md5(time()), 0, 6));
        
        return array(
            'id' => rand(100, 999),
            'name' => $name,
            'code' => $code,
            'url' => site_url('/ref/' . $code),
            'discount_percent' => $discount_percent,
            'commission_percent' => $commission_percent,
            'clicks_count' => 0,
            'conversions_count' => 0,
            'total_earned' => 0.00,
            'is_active' => true,
            'created_at' => current_time('Y-m-d H:i:s')
        );
    }

    /**
     * Получение информации о статусе платежа
     *
     * @param string $status Статус платежа
     * @return array
     */
    private function get_payment_status_info($status) {
        switch ($status) {
            case 'completed':
                return array(
                    'text' => 'Успішно',
                    'class' => 'status-line-indicator_green',
                    'color' => 'color-success'
                );
            case 'processing':
                return array(
                    'text' => 'Виконується',
                    'class' => 'status-line-indicator_orange',
                    'color' => 'color-orange'
                );
            case 'pending':
                return array(
                    'text' => 'Очікування',
                    'class' => 'status-line-indicator_orange',
                    'color' => 'color-orange'
                );
            case 'rejected':
                return array(
                    'text' => 'Помилка',
                    'class' => 'status-line-indicator_red',
                    'color' => 'color-danger'
                );
            default:
                return array(
                    'text' => 'Невідомо',
                    'class' => 'status-line-indicator_gray',
                    'color' => ''
                );
        }
    }

    /**
     * Получение информации о статусе реферала
     *
     * @param string $status Статус реферала
     * @return array
     */
    private function get_referral_status_info($status) {
        switch ($status) {
            case 'purchased':
                return array(
                    'text' => 'Успішно',
                    'class' => 'status-line-indicator_green',
                    'color' => 'color-success'
                );
            case 'registered':
                return array(
                    'text' => 'Зареєстрований',
                    'class' => 'status-line-indicator_orange',
                    'color' => ''
                );
            default:
                return array(
                    'text' => 'Невідомо',
                    'class' => 'status-line-indicator_gray',
                    'color' => ''
                );
        }
    }

    /**
     * Генерация маскированного Telegram ника
     *
     * @param int $user_id ID пользователя
     * @return string
     */
    private function generate_masked_telegram($user_id) {
        // Список фиктивных имен для генерации
        $names = array(
            'anatoly_crypto', 'evgeny_trader', 'maria_invest', 'dmitry_btc', 'olga_newbie',
            'alex_hodler', 'crypto_fan', 'bitcoin_lover', 'eth_trader', 'defi_expert',
            'nft_collector', 'blockchain_dev', 'crypto_news', 'trading_pro', 'hodl_master'
        );
        
        // Выбираем имя на основе user_id
        $name = $names[($user_id - 100) % count($names)];
        
        // Маскируем середину
        $length = strlen($name);
        if ($length <= 6) {
            return '@' . substr($name, 0, 2) . '*****' . substr($name, -2);
        } else {
            return '@' . substr($name, 0, 2) . '*****' . substr($name, -3);
        }
    }
}

// Инициализация API контроллера убрана - теперь он создается через систему плагина
