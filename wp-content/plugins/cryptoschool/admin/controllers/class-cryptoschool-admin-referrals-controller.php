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
        // Получение демо-данных для отображения
        $influencers = $this->get_demo_influencers();
        $withdrawal_requests = $this->get_demo_withdrawal_requests();
        $stats = $this->get_demo_stats();

        // Отображение страницы
        $this->render_view('referrals', array(
            'influencers' => $influencers,
            'withdrawal_requests' => $withdrawal_requests,
            'stats' => $stats
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
     * Получение демо-данных инфлюенсеров
     *
     * @return array
     */
    private function get_demo_influencers() {
        return array(
            (object) array(
                'id' => 1,
                'user_login' => 'influencer1',
                'user_email' => 'influencer1@example.com',
                'display_name' => 'Инфлюенсер 1',
                'max_commission_percent' => 35,
                'is_influencer' => true,
                'admin_notes' => 'YouTube блогер с 50K подписчиков',
                'created_at' => '2025-06-01 10:00:00'
            ),
            (object) array(
                'id' => 2,
                'user_login' => 'influencer2',
                'user_email' => 'influencer2@example.com',
                'display_name' => 'Инфлюенсер 2',
                'max_commission_percent' => 50,
                'is_influencer' => true,
                'admin_notes' => 'Telegram канал с 100K подписчиков',
                'created_at' => '2025-06-05 14:30:00'
            )
        );
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
     * Получение демо-статистики
     *
     * @return array
     */
    private function get_demo_stats() {
        return array(
            'total' => array(
                'referral_links' => 45,
                'referrals' => 128,
                'purchases' => 89,
                'commissions_amount' => 2450.75,
                'paid_amount' => 1890.25
            ),
            'monthly' => array(
                'referral_links' => 12,
                'referrals' => 34,
                'purchases' => 28,
                'commissions_amount' => 680.50,
                'withdrawal_requests' => 8
            ),
            'top_referrers' => array(
                array(
                    'user_login' => 'top_referrer1',
                    'user_email' => 'top1@example.com',
                    'total_earned' => 450.75,
                    'referrals_count' => 25
                ),
                array(
                    'user_login' => 'top_referrer2',
                    'user_email' => 'top2@example.com',
                    'total_earned' => 380.50,
                    'referrals_count' => 18
                ),
                array(
                    'user_login' => 'top_referrer3',
                    'user_email' => 'top3@example.com',
                    'total_earned' => 295.25,
                    'referrals_count' => 15
                )
            ),
            'top_links' => array(
                array(
                    'link_name' => 'YouTube промо',
                    'clicks' => 1250,
                    'conversions' => 45,
                    'conversion_rate' => 3.6,
                    'total_earned' => 890.50
                ),
                array(
                    'link_name' => 'Telegram канал',
                    'clicks' => 980,
                    'conversions' => 32,
                    'conversion_rate' => 3.3,
                    'total_earned' => 650.75
                ),
                array(
                    'link_name' => 'Instagram Stories',
                    'clicks' => 750,
                    'conversions' => 18,
                    'conversion_rate' => 2.4,
                    'total_earned' => 385.25
                )
            )
        );
    }
}
