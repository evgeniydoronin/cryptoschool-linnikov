<?php
/**
 * Контроллер для управления реферальной системой в административной части
 *
 * @package CryptoSchool
 * @subpackage Admin\Controllers
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контроллер для управления реферальной системой
 */
class CryptoSchool_Admin_Referrals_Controller extends CryptoSchool_Admin_Controller {

    /**
     * Сервис для работы с реферальными ссылками
     *
     * @var CryptoSchool_Service_Referral
     */
    private $referral_service;

    /**
     * Сервис для работы с запросами на вывод
     *
     * @var CryptoSchool_Service_Withdrawal
     */
    private $withdrawal_service;

    /**
     * Сервис для работы с инфлюенсерами
     *
     * @var CryptoSchool_Service_Influencer
     */
    private $influencer_service;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика плагина
     */
    public function __construct($loader) {
        // Инициализация сервисов
        $this->influencer_service = new CryptoSchool_Service_Influencer($loader);
        $this->withdrawal_service = new CryptoSchool_Service_Withdrawal($loader);
        $this->referral_service = new CryptoSchool_Service_Referral_Stats($loader);
        
        parent::__construct($loader);
    }

    /**
     * Регистрация хуков
     */
    protected function register_hooks() {
        // Регистрация AJAX-обработчиков для инфлюенсеров
        add_action('wp_ajax_cryptoschool_search_users', array($this, 'ajax_search_users'));
        add_action('wp_ajax_cryptoschool_get_influencers', array($this, 'ajax_get_influencers'));
        add_action('wp_ajax_cryptoschool_add_influencer', array($this, 'ajax_add_influencer'));
        add_action('wp_ajax_cryptoschool_update_influencer', array($this, 'ajax_update_influencer'));
        add_action('wp_ajax_cryptoschool_delete_influencer', array($this, 'ajax_delete_influencer'));

        // Регистрация AJAX-обработчиков для запросов на вывод
        add_action('wp_ajax_cryptoschool_get_withdrawal_requests', array($this, 'ajax_get_withdrawal_requests'));
        add_action('wp_ajax_cryptoschool_approve_withdrawal', array($this, 'ajax_approve_withdrawal'));
        add_action('wp_ajax_cryptoschool_reject_withdrawal', array($this, 'ajax_reject_withdrawal'));
        add_action('wp_ajax_cryptoschool_mark_withdrawal_paid', array($this, 'ajax_mark_withdrawal_paid'));

        // Регистрация AJAX-обработчиков для статистики
        add_action('wp_ajax_cryptoschool_get_referral_stats', array($this, 'ajax_get_referral_stats'));
    }

    /**
     * Отображение страницы реферальной системы
     */
    public function display_referrals_page() {
        global $wpdb;
        
        // Получение реальных данных для отображения
        $influencers = $this->get_demo_influencers();
        $withdrawal_requests = $this->get_demo_withdrawal_requests();
        $stats = $this->get_demo_stats();
        
        // Дополнительно получаем все реферальные ссылки
        $referral_links = $wpdb->get_results("
            SELECT 
                rl.*,
                u.user_login,
                u.user_email,
                COUNT(DISTINCT ru.user_id) as referrals_count
            FROM {$wpdb->prefix}cryptoschool_referral_links rl
            LEFT JOIN {$wpdb->prefix}users u ON rl.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}cryptoschool_referral_users ru ON rl.id = ru.referral_link_id
            GROUP BY rl.id
            ORDER BY rl.created_at DESC
            LIMIT 100
        ");
        
        // Получаем последние реферальные связи
        $recent_referrals = $wpdb->get_results("
            SELECT 
                ru.*,
                u_referrer.user_login as referrer_login,
                u_referred.user_login as referred_login,
                rl.link_name,
                rl.referral_code
            FROM {$wpdb->prefix}cryptoschool_referral_users ru
            LEFT JOIN {$wpdb->prefix}users u_referrer ON ru.referrer_id = u_referrer.ID
            LEFT JOIN {$wpdb->prefix}users u_referred ON ru.user_id = u_referred.ID
            LEFT JOIN {$wpdb->prefix}cryptoschool_referral_links rl ON ru.referral_link_id = rl.id
            ORDER BY ru.registration_date DESC
            LIMIT 50
        ");
        
        // Получаем последние транзакции
        $recent_transactions = $wpdb->get_results("
            SELECT 
                rt.*,
                u_referrer.user_login as referrer_login,
                u_buyer.user_login as buyer_login
            FROM {$wpdb->prefix}cryptoschool_referral_transactions rt
            LEFT JOIN {$wpdb->prefix}users u_referrer ON rt.referrer_id = u_referrer.ID
            LEFT JOIN {$wpdb->prefix}users u_buyer ON rt.user_id = u_buyer.ID
            ORDER BY rt.created_at DESC
            LIMIT 20
        ");

        // Отображение страницы с реальными данными
        $this->render_view('referrals', array(
            'influencers' => $influencers,
            'withdrawal_requests' => $withdrawal_requests,
            'stats' => $stats,
            'referral_links' => $referral_links ?: array(),
            'recent_referrals' => $recent_referrals ?: array(),
            'recent_transactions' => $recent_transactions ?: array()
        ));
    }

    /**
     * AJAX: Поиск пользователей для добавления в инфлюенсеры
     */
    public function ajax_search_users() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение поискового запроса
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (strlen($search) < 3) {
            $this->send_ajax_error('Введите не менее 3 символов для поиска.');
            return;
        }

        // Поиск пользователей в WordPress
        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 10,
            'fields' => array('ID', 'user_login', 'user_email', 'display_name')
        ));

        // Подготовка данных для ответа
        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'id' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name
            );
        }

        // Отправка ответа
        $this->send_ajax_success($results);
    }

    /**
     * AJAX: Получение списка инфлюенсеров
     */
    public function ajax_get_influencers() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение демо-данных
        $influencers = $this->get_demo_influencers();

        // Отправка ответа
        $this->send_ajax_success($influencers);
    }

    /**
     * AJAX: Добавление инфлюенсера
     */
    public function ajax_add_influencer() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $max_commission_percent = isset($_POST['max_commission_percent']) ? (float) $_POST['max_commission_percent'] : 20;
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        // Валидация
        if (!$user_id) {
            $this->send_ajax_error('Не указан пользователь.');
            return;
        }

        if ($max_commission_percent < 20 || $max_commission_percent > 50) {
            $this->send_ajax_error('Процент комиссии должен быть от 20% до 50%.');
            return;
        }

        // Проверка существования пользователя
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->send_ajax_error('Пользователь не найден.');
            return;
        }

        // Имитация добавления инфлюенсера
        // В реальной реализации здесь будет вызов сервиса
        
        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Инфлюенсер успешно добавлен.',
            'influencer' => array(
                'id' => $user_id,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'max_commission_percent' => $max_commission_percent,
                'is_influencer' => true,
                'admin_notes' => $admin_notes
            )
        ));
    }

    /**
     * AJAX: Обновление инфлюенсера
     */
    public function ajax_update_influencer() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение данных
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $max_commission_percent = isset($_POST['max_commission_percent']) ? (float) $_POST['max_commission_percent'] : 20;
        $is_influencer = isset($_POST['is_influencer']) ? (int) $_POST['is_influencer'] : 1;
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        // Валидация
        if (!$id) {
            $this->send_ajax_error('Не указан ID инфлюенсера.');
            return;
        }

        if ($max_commission_percent < 20 || $max_commission_percent > 50) {
            $this->send_ajax_error('Процент комиссии должен быть от 20% до 50%.');
            return;
        }

        // Имитация обновления инфлюенсера
        // В реальной реализации здесь будет вызов сервиса

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Данные инфлюенсера успешно обновлены.'
        ));
    }

    /**
     * AJAX: Удаление инфлюенсера
     */
    public function ajax_delete_influencer() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID инфлюенсера.');
            return;
        }

        // Имитация удаления инфлюенсера
        // В реальной реализации здесь будет вызов сервиса

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Инфлюенсер успешно удален.'
        ));
    }

    /**
     * AJAX: Получение запросов на вывод
     */
    public function ajax_get_withdrawal_requests() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение фильтров
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Получение демо-данных
        $requests = $this->get_demo_withdrawal_requests();

        // Фильтрация по статусу
        if (!empty($status)) {
            $requests = array_filter($requests, function($request) use ($status) {
                return $request->status === $status;
            });
        }

        // Отправка ответа
        $this->send_ajax_success(array_values($requests));
    }

    /**
     * AJAX: Одобрение запроса на вывод
     */
    public function ajax_approve_withdrawal() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID запроса.');
            return;
        }

        // Имитация одобрения запроса
        // В реальной реализации здесь будет вызов сервиса

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Запрос на вывод успешно одобрен.'
        ));
    }

    /**
     * AJAX: Отклонение запроса на вывод
     */
    public function ajax_reject_withdrawal() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID запроса.');
            return;
        }

        // Имитация отклонения запроса
        // В реальной реализации здесь будет вызов сервиса

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Запрос на вывод успешно отклонен.'
        ));
    }

    /**
     * AJAX: Отметка запроса как оплаченного
     */
    public function ajax_mark_withdrawal_paid() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение ID
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$id) {
            $this->send_ajax_error('Не указан ID запроса.');
            return;
        }

        // Имитация отметки как оплаченного
        // В реальной реализации здесь будет вызов сервиса

        // Отправка ответа
        $this->send_ajax_success(array(
            'message' => 'Запрос на вывод успешно отмечен как оплаченный.'
        ));
    }

    /**
     * AJAX: Получение статистики реферальной системы
     */
    public function ajax_get_referral_stats() {
        // Проверка nonce
        if (!$this->verify_ajax_nonce()) {
            $this->send_ajax_error('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
            return;
        }

        // Получение демо-статистики
        $stats = $this->get_demo_stats();

        // Отправка ответа
        $this->send_ajax_success($stats);
    }

    /**
     * Получение реальных данных инфлюенсеров из БД
     *
     * @return array
     */
    private function get_demo_influencers() {
        global $wpdb;
        
        // Получаем пользователей с максимальными комиссиями > 20%
        $influencers = $wpdb->get_results("
            SELECT DISTINCT
                rl.user_id as id,
                u.user_login,
                u.user_email,
                u.display_name,
                MAX(rl.commission_percent) as max_commission_percent,
                1 as is_influencer,
                COUNT(DISTINCT ru.user_id) as referrals_count,
                SUM(rl.total_earned) as total_earned,
                MIN(rl.created_at) as created_at
            FROM {$wpdb->prefix}cryptoschool_referral_links rl
            LEFT JOIN {$wpdb->prefix}users u ON rl.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}cryptoschool_referral_users ru ON rl.id = ru.referral_link_id
            WHERE rl.commission_percent > 20
            GROUP BY rl.user_id, u.user_login, u.user_email, u.display_name
            ORDER BY max_commission_percent DESC, total_earned DESC
            LIMIT 20
        ");
        
        // Если нет реальных инфлюенсеров, возвращаем пустой массив
        if (empty($influencers)) {
            return array();
        }
        
        // Форматируем данные
        foreach ($influencers as &$influencer) {
            $influencer->admin_notes = sprintf(
                'Рефералов: %d, Заработано: $%.2f',
                $influencer->referrals_count,
                $influencer->total_earned ?: 0
            );
        }
        
        return $influencers;
    }

    /**
     * Получение демо-данных запросов на вывод
     *
     * @return array
     */
    private function get_demo_withdrawal_requests() {
        return array(
            (object) array(
                'id' => 1,
                'user_id' => 10,
                'user_login' => 'referrer1',
                'user_email' => 'referrer1@example.com',
                'amount' => 150.00,
                'crypto_address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE',
                'crypto_currency' => 'USDT',
                'status' => 'pending',
                'request_date' => '2025-06-15 09:00:00',
                'admin_comment' => ''
            ),
            (object) array(
                'id' => 2,
                'user_id' => 15,
                'user_login' => 'referrer2',
                'user_email' => 'referrer2@example.com',
                'amount' => 75.50,
                'crypto_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'crypto_currency' => 'BTC',
                'status' => 'approved',
                'request_date' => '2025-06-14 16:45:00',
                'admin_comment' => 'Проверено, готово к выплате'
            ),
            (object) array(
                'id' => 3,
                'user_id' => 20,
                'user_login' => 'referrer3',
                'user_email' => 'referrer3@example.com',
                'amount' => 200.00,
                'crypto_address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE',
                'crypto_currency' => 'USDT',
                'status' => 'paid',
                'request_date' => '2025-06-10 11:20:00',
                'admin_comment' => 'Выплачено 16.06.2025'
            )
        );
    }

    /**
     * Получение реальной статистики из БД
     *
     * @return array
     */
    private function get_demo_stats() {
        global $wpdb;
        
        // Общая статистика за все время
        $total_stats = array(
            'referral_links' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_links"),
            'referrals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_users"),
            'purchases' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_users WHERE status = 'purchased'"),
            'commissions_amount' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}cryptoschool_referral_transactions WHERE status = 'completed'") ?: 0,
            'paid_amount' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}cryptoschool_referral_transactions WHERE status = 'paid'") ?: 0
        );
        
        // Статистика за последний месяц (30 дней)
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        $monthly_stats = array(
            'referral_links' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_links WHERE created_at > %s",
                $thirty_days_ago
            )),
            'referrals' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_users WHERE registration_date > %s",
                $thirty_days_ago
            )),
            'purchases' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cryptoschool_referral_users WHERE status = 'purchased' AND purchase_date > %s",
                $thirty_days_ago
            )),
            'commissions_amount' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}cryptoschool_referral_transactions WHERE status = 'completed' AND created_at > %s",
                $thirty_days_ago
            )) ?: 0,
            'withdrawal_requests' => 0 // Пока нет таблицы для запросов на вывод
        );
        
        // Топ рефереров
        $top_referrers = $wpdb->get_results("
            SELECT 
                u.user_login,
                u.user_email,
                COUNT(DISTINCT ru.user_id) as referrals_count,
                COALESCE(SUM(rl.total_earned), 0) as total_earned
            FROM {$wpdb->prefix}cryptoschool_referral_links rl
            LEFT JOIN {$wpdb->prefix}users u ON rl.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}cryptoschool_referral_users ru ON rl.id = ru.referral_link_id
            WHERE rl.is_active = 1
            GROUP BY u.ID, u.user_login, u.user_email
            ORDER BY total_earned DESC
            LIMIT 3
        ", ARRAY_A);
        
        // Топ реферальных ссылок
        $top_links = $wpdb->get_results("
            SELECT 
                link_name,
                clicks_count as clicks,
                conversions_count as conversions,
                conversion_rate,
                COALESCE(total_earned, 0) as total_earned
            FROM {$wpdb->prefix}cryptoschool_referral_links
            WHERE is_active = 1
            ORDER BY total_earned DESC, conversions_count DESC
            LIMIT 3
        ", ARRAY_A);
        
        return array(
            'total' => $total_stats,
            'monthly' => $monthly_stats,
            'top_referrers' => $top_referrers ?: array(),
            'top_links' => $top_links ?: array()
        );
    }
}
