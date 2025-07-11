<?php
/**
 * Сервис для работы с реферальной системой
 *
 * @package CryptoSchool
 * @subpackage Services
 */

// Если файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс сервиса реферальной системы
 */
class CryptoSchool_Service_Referral extends CryptoSchool_Service {
    /**
     * Репозиторий реферальных ссылок
     *
     * @var CryptoSchool_Repository_Referral_Link
     */
    private $referral_link_repository;

    /**
     * Конструктор класса
     *
     * @param CryptoSchool_Loader $loader Экземпляр загрузчика
     */
    public function __construct(CryptoSchool_Loader $loader) {
        parent::__construct($loader);
        $this->referral_link_repository = new CryptoSchool_Repository_Referral_Link();
    }

    /**
     * Регистрация хуков и фильтров
     *
     * @return void
     */
    protected function register_hooks() {
        // Хук для обработки реферальных ссылок
        $this->add_action('init', 'handle_referral_link');
        
        // Хук для сохранения реферального кода в сессии
        $this->add_action('wp_loaded', 'save_referral_code_to_session');
        
        // AJAX хуки для работы с реферальными ссылками
        $this->add_action('wp_ajax_create_referral_link', 'ajax_create_referral_link');
        $this->add_action('wp_ajax_update_referral_link', 'ajax_update_referral_link');
        $this->add_action('wp_ajax_delete_referral_link', 'ajax_delete_referral_link');
        $this->add_action('wp_ajax_get_referral_stats', 'ajax_get_referral_stats');
    }

    /**
     * Создание новой реферальной ссылки
     *
     * @param int   $user_id ID пользователя
     * @param array $data    Данные ссылки
     * @return CryptoSchool_Model_Referral_Link|WP_Error
     */
    public function create_referral_link($user_id, $data) {
        // Проверяем права пользователя
        if (!$this->can_user_create_referral_links($user_id)) {
            return new WP_Error('permission_denied', 'У вас нет прав для создания реферальных ссылок');
        }

        // Получаем максимально допустимый процент для пользователя
        $max_percent = $this->get_user_max_percent($user_id);

        // Проверяем лимиты процентов
        $total_percent = (float) $data['discount_percent'] + (float) $data['commission_percent'];
        if ($total_percent > $max_percent) {
            return new WP_Error('percent_limit_exceeded', 
                sprintf('Сумма скидки и комиссии не может превышать %s%%', $max_percent));
        }

        // Подготавливаем данные
        $link_data = [
            'user_id'            => $user_id,
            'link_name'          => sanitize_text_field($data['link_name'] ?? 'Реферальная ссылка'),
            'link_description'   => sanitize_textarea_field($data['link_description'] ?? ''),
            'discount_percent'   => (float) $data['discount_percent'],
            'commission_percent' => (float) $data['commission_percent']
        ];

        // Создаем ссылку
        $link = $this->referral_link_repository->create($link_data);

        if (!$link) {
            return new WP_Error('creation_failed', 'Не удалось создать реферальную ссылку');
        }

        // Логируем создание ссылки
        $this->log_info('Создана новая реферальная ссылка', [
            'user_id' => $user_id,
            'link_id' => $link->getAttribute('id'),
            'code'    => $link->get_referral_code()
        ]);

        return $link;
    }

    /**
     * Обновление реферальной ссылки
     *
     * @param int   $link_id ID ссылки
     * @param int   $user_id ID пользователя
     * @param array $data    Новые данные
     * @return bool|WP_Error
     */
    public function update_referral_link($link_id, $user_id, $data) {
        // Проверяем, принадлежит ли ссылка пользователю
        $link = $this->referral_link_repository->find($link_id);
        if (!$link || $link->get_user_id() !== $user_id) {
            return new WP_Error('not_found', 'Реферальная ссылка не найдена');
        }

        // Получаем максимально допустимый процент для пользователя
        $max_percent = $this->get_user_max_percent($user_id);

        // Проверяем лимиты процентов
        if (isset($data['discount_percent']) || isset($data['commission_percent'])) {
            $discount = isset($data['discount_percent']) ? (float) $data['discount_percent'] : $link->get_discount_percent();
            $commission = isset($data['commission_percent']) ? (float) $data['commission_percent'] : $link->get_commission_percent();
            
            if (($discount + $commission) > $max_percent) {
                return new WP_Error('percent_limit_exceeded', 
                    sprintf('Сумма скидки и комиссии не может превышать %s%%', $max_percent));
            }
        }

        // Подготавливаем данные для обновления
        $update_data = [];
        
        if (isset($data['link_name'])) {
            $update_data['link_name'] = sanitize_text_field($data['link_name']);
        }
        
        if (isset($data['link_description'])) {
            $update_data['link_description'] = sanitize_textarea_field($data['link_description']);
        }
        
        if (isset($data['discount_percent'])) {
            $update_data['discount_percent'] = (float) $data['discount_percent'];
        }
        
        if (isset($data['commission_percent'])) {
            $update_data['commission_percent'] = (float) $data['commission_percent'];
        }
        
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int) $data['is_active'];
        }

        // Обновляем ссылку
        $result = $this->referral_link_repository->update($link_id, $update_data);

        if (!$result) {
            return new WP_Error('update_failed', 'Не удалось обновить реферальную ссылку');
        }

        // Логируем обновление
        $this->log_info('Обновлена реферальная ссылка', [
            'user_id' => $user_id,
            'link_id' => $link_id,
            'data'    => $update_data
        ]);

        return true;
    }

    /**
     * Получение реферальных ссылок пользователя
     *
     * @param int   $user_id ID пользователя
     * @param array $args    Дополнительные параметры
     * @return array
     */
    public function get_user_referral_links($user_id, $args = []) {
        return $this->referral_link_repository->get_user_links($user_id, $args);
    }

    /**
     * Получение статистики пользователя по реферальным ссылкам
     *
     * @param int $user_id ID пользователя
     * @return array
     */
    public function get_user_referral_stats($user_id) {
        return $this->referral_link_repository->get_user_stats($user_id);
    }

    /**
     * Обработка перехода по реферальной ссылке
     *
     * @return void
     */
    public function handle_referral_link() {
        // Проверяем, является ли это реферальной ссылкой
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (!preg_match('/\/ref\/([a-zA-Z0-9]+)/', $request_uri, $matches)) {
            return;
        }

        $referral_code = $matches[1];

        // Ищем реферальную ссылку
        $link = $this->referral_link_repository->find_by_code($referral_code);
        
        if (!$link) {
            // Перенаправляем на главную, если ссылка не найдена
            wp_redirect(home_url());
            exit;
        }

        // Увеличиваем счетчик переходов
        $this->referral_link_repository->increment_clicks($link->getAttribute('id'));

        // Сохраняем реферальный код в сессии
        $this->save_referral_to_session($referral_code, $link->getAttribute('id'));

        // Перенаправляем на главную страницу
        wp_redirect(home_url());
        exit;
    }

    /**
     * Сохранение реферального кода в сессии
     *
     * @return void
     */
    public function save_referral_code_to_session() {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Сохранение реферальных данных в сессии
     *
     * @param string $referral_code Реферальный код
     * @param int    $link_id       ID ссылки
     * @return void
     */
    private function save_referral_to_session($referral_code, $link_id) {
        if (!session_id()) {
            session_start();
        }

        $_SESSION['cryptoschool_referral'] = [
            'code'    => $referral_code,
            'link_id' => $link_id,
            'time'    => time()
        ];

        // Логируем переход
        $this->log_info('Переход по реферальной ссылке', [
            'code'    => $referral_code,
            'link_id' => $link_id,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    /**
     * Получение реферальных данных из сессии
     *
     * @return array|null
     */
    public function get_referral_from_session() {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['cryptoschool_referral'])) {
            return null;
        }

        $referral_data = $_SESSION['cryptoschool_referral'];

        // Проверяем, не истек ли срок действия (30 дней)
        if ((time() - $referral_data['time']) > (30 * 24 * 60 * 60)) {
            unset($_SESSION['cryptoschool_referral']);
            return null;
        }

        return $referral_data;
    }

    /**
     * Очистка реферальных данных из сессии
     *
     * @return void
     */
    public function clear_referral_from_session() {
        if (!session_id()) {
            session_start();
        }

        unset($_SESSION['cryptoschool_referral']);
    }

    /**
     * Проверка, может ли пользователь создавать реферальные ссылки
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function can_user_create_referral_links($user_id) {
        // Все зарегистрированные пользователи могут создавать ссылки
        return user_can($user_id, 'read');
    }

    /**
     * Получение максимально допустимого процента для пользователя
     *
     * @param int $user_id ID пользователя
     * @return float
     */
    public function get_user_max_percent($user_id) {
        // Проверяем, является ли пользователь инфлюенсером
        // Пока возвращаем базовый лимит 40%
        // TODO: Интеграция с таблицей influencer_settings
        return 40.0;
    }

    /**
     * AJAX обработчик создания реферальной ссылки
     *
     * @return void
     */
    public function ajax_create_referral_link() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptoschool_referral_nonce')) {
            $this->send_json_error('Ошибка безопасности');
            return;
        }

        // Проверяем авторизацию
        if (!is_user_logged_in()) {
            $this->send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();
        
        // Получаем данные из запроса
        $data = [
            'link_name'          => $_POST['link_name'] ?? '',
            'link_description'   => $_POST['link_description'] ?? '',
            'discount_percent'   => (float) ($_POST['discount_percent'] ?? 20),
            'commission_percent' => (float) ($_POST['commission_percent'] ?? 20)
        ];

        // Создаем ссылку
        $result = $this->create_referral_link($user_id, $data);

        if (is_wp_error($result)) {
            $this->send_json_error($result->get_error_message());
            return;
        }

        $this->send_json_success([
            'link' => [
                'id'                 => $result->getAttribute('id'),
                'name'               => $result->get_link_name(),
                'description'        => $result->get_link_description(),
                'code'               => $result->get_referral_code(),
                'url'                => $result->get_full_url(),
                'discount_percent'   => $result->get_discount_percent(),
                'commission_percent' => $result->get_commission_percent(),
                'clicks_count'       => $result->get_clicks_count(),
                'conversions_count'  => $result->get_conversions_count(),
                'total_earned'       => $result->get_total_earned(),
                'conversion_rate'    => $result->get_conversion_rate(),
                'is_active'          => $result->is_active(),
                'created_at'         => $result->get_created_at()
            ]
        ]);
    }

    /**
     * AJAX обработчик обновления реферальной ссылки
     *
     * @return void
     */
    public function ajax_update_referral_link() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptoschool_referral_nonce')) {
            $this->send_json_error('Ошибка безопасности');
            return;
        }

        // Проверяем авторизацию
        if (!is_user_logged_in()) {
            $this->send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();
        $link_id = (int) ($_POST['link_id'] ?? 0);

        if (!$link_id) {
            $this->send_json_error('ID ссылки не указан');
            return;
        }

        // Получаем данные для обновления
        $data = [];
        
        if (isset($_POST['link_name'])) {
            $data['link_name'] = $_POST['link_name'];
        }
        
        if (isset($_POST['link_description'])) {
            $data['link_description'] = $_POST['link_description'];
        }
        
        if (isset($_POST['discount_percent'])) {
            $data['discount_percent'] = (float) $_POST['discount_percent'];
        }
        
        if (isset($_POST['commission_percent'])) {
            $data['commission_percent'] = (float) $_POST['commission_percent'];
        }
        
        if (isset($_POST['is_active'])) {
            $data['is_active'] = (int) $_POST['is_active'];
        }

        // Обновляем ссылку
        $result = $this->update_referral_link($link_id, $user_id, $data);

        if (is_wp_error($result)) {
            $this->send_json_error($result->get_error_message());
            return;
        }

        $this->send_json_success(['message' => 'Ссылка успешно обновлена']);
    }

    /**
     * AJAX обработчик удаления реферальной ссылки
     *
     * @return void
     */
    public function ajax_delete_referral_link() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptoschool_referral_nonce')) {
            $this->send_json_error('Ошибка безопасности');
            return;
        }

        // Проверяем авторизацию
        if (!is_user_logged_in()) {
            $this->send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();
        $link_id = (int) ($_POST['link_id'] ?? 0);

        if (!$link_id) {
            $this->send_json_error('ID ссылки не указан');
            return;
        }

        // Проверяем, принадлежит ли ссылка пользователю
        $link = $this->referral_link_repository->find($link_id);
        if (!$link || $link->get_user_id() !== $user_id) {
            $this->send_json_error('Ссылка не найдена');
            return;
        }

        // Удаляем ссылку
        $result = $this->referral_link_repository->delete($link_id);

        if (!$result) {
            $this->send_json_error('Не удалось удалить ссылку');
            return;
        }

        $this->send_json_success(['message' => 'Ссылка успешно удалена']);
    }

    /**
     * AJAX обработчик получения статистики
     *
     * @return void
     */
    public function ajax_get_referral_stats() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cryptoschool_referral_nonce')) {
            $this->send_json_error('Ошибка безопасности');
            return;
        }

        // Проверяем авторизацию
        if (!is_user_logged_in()) {
            $this->send_json_error('Необходима авторизация');
            return;
        }

        $user_id = get_current_user_id();
        $stats = $this->get_user_referral_stats($user_id);

        $this->send_json_success(['stats' => $stats]);
    }
}
